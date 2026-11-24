<?php

namespace AltDesign\AltCommerce\Tests\Unit\Actions;

use AltDesign\AltCommerce\Actions\AttemptPaymentAction;
use AltDesign\AltCommerce\Commerce\Order\Order;
use AltDesign\AltCommerce\Commerce\Payment\GatewayBroker;
use AltDesign\AltCommerce\Commerce\Payment\GatewayConfig;
use AltDesign\AltCommerce\Commerce\Payment\ProcessOrderRequest;
use AltDesign\AltCommerce\Commerce\Payment\Transaction;
use AltDesign\AltCommerce\Contracts\OrderRepository;
use AltDesign\AltCommerce\Contracts\PaymentGateway;
use AltDesign\AltCommerce\Enum\OrderStatus;
use AltDesign\AltCommerce\Enum\TransactionStatus;
use AltDesign\AltCommerce\Enum\TransactionType;
use AltDesign\AltCommerce\Exceptions\OrderAlreadyPlacedException;
use AltDesign\AltCommerce\Exceptions\PaymentFailedException;
use AltDesign\AltCommerce\Exceptions\PaymentOutcomeUnknownException;
use AltDesign\AltCommerce\Tests\Support\CommerceHelper;
use AltDesign\AltCommerce\Tests\Unit\TestCase;
use Closure;
use DateTimeImmutable;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;

class AttemptPaymentActionTest extends TestCase
{
    use CommerceHelper;
    use MockeryPHPUnitIntegration;

    /** @var array<int, array<int, Transaction>> */
    protected array $savedTransactions = [];

    protected bool $savingFails = false;

    /**
     * @return array<string, array{OrderStatus, array<int, TransactionStatus>}>
     */
    public static function placedOrPaidOrders(): array
    {
        return [
            'draft with a settled payment' => [OrderStatus::DRAFT, [TransactionStatus::SETTLED]],
            'draft with a payment in flight' => [OrderStatus::DRAFT, [TransactionStatus::PENDING]],
            'processing' => [OrderStatus::PROCESSING, []],
        ];
    }

    /**
     * @param array<int, TransactionStatus> $transactionStatuses
     */
    #[DataProvider('placedOrPaidOrders')]
    public function test_refuses_to_charge_an_order_that_has_been_placed_or_paid(OrderStatus $status, array $transactionStatuses): void
    {
        $action = new AttemptPaymentAction(
            orderRepository: Mockery::mock(OrderRepository::class),
            gatewayBroker: Mockery::mock(GatewayBroker::class),
        );

        $this->expectException(OrderAlreadyPlacedException::class);

        $action->handle($this->makeOrder($status, $transactionStatuses), 'nonce');
    }

    public function test_charges_a_draft_whose_payment_attempts_all_failed(): void
    {
        $order = $this->makeOrder(OrderStatus::DRAFT, [TransactionStatus::FAILED]);

        $this->attemptPayment($order, fn (ProcessOrderRequest $request) => $this->settle($request));

        $this->assertSame([TransactionStatus::FAILED, TransactionStatus::SETTLED], $this->transactionStatuses($order));
    }

    public function test_records_the_attempt_before_the_gateway_is_called(): void
    {
        $order = $this->makeOrder();
        $savedBeforeGatewayCall = null;

        $this->attemptPayment($order, function (ProcessOrderRequest $request) use (&$savedBeforeGatewayCall) {
            $savedBeforeGatewayCall = $this->savedTransactions;

            return $this->settle($request);
        });

        $this->assertCount(1, $savedBeforeGatewayCall);
        $this->assertCount(1, $savedBeforeGatewayCall[0]);

        $attempt = $savedBeforeGatewayCall[0][0];

        $this->assertSame(TransactionStatus::PENDING, $attempt->status);
        $this->assertSame(TransactionType::SALE, $attempt->type);
        $this->assertSame(1000, $attempt->amount);
        $this->assertSame('GBP', $attempt->currency);
        $this->assertSame('stripe', $attempt->gateway);
        $this->assertSame('nonce', $attempt->gatewayId);
        $this->assertSame('nonce', $attempt->additional['id']);
    }

    public function test_replaces_the_attempt_with_the_transaction_the_gateway_recorded(): void
    {
        $order = $this->makeOrder();

        $this->attemptPayment($order, fn (ProcessOrderRequest $request) => $this->settle($request));

        $lastSaved = end($this->savedTransactions);

        $this->assertCount(1, $lastSaved);
        $this->assertSame('gateway-transaction', $lastSaved[0]->id);
    }

    public function test_drops_the_attempt_when_the_payment_failed_so_it_can_be_tried_again(): void
    {
        $order = $this->makeOrder();

        try {
            $this->attemptPayment($order, fn () => throw new PaymentFailedException('Card declined'));
            $this->fail('The failure was not passed on');
        } catch (PaymentFailedException $exception) {
            $this->assertSame('Card declined', $exception->getMessage());
        }

        $this->assertSame([], end($this->savedTransactions));
        $this->assertTrue($order->isUnpaidDraft());
    }

    public function test_keeps_the_attempt_when_the_outcome_is_unknown_so_it_is_not_charged_again(): void
    {
        $order = $this->makeOrder();

        try {
            $this->attemptPayment($order, fn () => throw new PaymentOutcomeUnknownException('Connection lost'));
            $this->fail('The failure was not passed on');
        } catch (PaymentOutcomeUnknownException) {
        }

        $this->assertCount(1, end($this->savedTransactions));
        $this->assertFalse($order->isUnpaidDraft());

        $this->expectException(OrderAlreadyPlacedException::class);

        $this->attemptPayment($order, fn (ProcessOrderRequest $request) => $this->settle($request));
    }

    public function test_the_outcome_is_unknown_and_the_attempt_stays_on_record_when_saving_the_payment_fails(): void
    {
        $order = $this->makeOrder();

        try {
            $this->attemptPayment($order, function (ProcessOrderRequest $request) {
                $this->savingFails = true;

                return $this->settle($request);
            });
            $this->fail('The failure was not passed on');
        } catch (PaymentOutcomeUnknownException $exception) {
            $this->assertSame('Disk full', $exception->getPrevious()?->getMessage());
        }

        $this->assertCount(1, $this->savedTransactions);
        $this->assertSame(TransactionStatus::PENDING, $this->savedTransactions[0][0]->status);
    }

    protected function attemptPayment(Order $order, Closure $processOrder): void
    {
        $gateway = Mockery::mock(PaymentGateway::class);
        $gateway->allows('processOrder')->andReturnUsing($processOrder);

        $config = Mockery::mock(GatewayConfig::class, ['gateway' => $gateway, 'name' => 'stripe']);
        $gatewayBroker = Mockery::mock(GatewayBroker::class, ['currency' => $config]);

        $orderRepository = Mockery::mock(OrderRepository::class);
        $orderRepository->allows('save')->andReturnUsing(function (Order $order) {
            if ($this->savingFails) {
                throw new RuntimeException('Disk full');
            }

            $this->savedTransactions[] = $order->transactions;
        });

        (new AttemptPaymentAction($orderRepository, $gatewayBroker))->handle($order, 'nonce');
    }

    protected function settle(ProcessOrderRequest $request): Order
    {
        $request->order->transactions[] = new Transaction(
            id: 'gateway-transaction',
            type: TransactionType::SALE,
            status: TransactionStatus::SETTLED,
            currency: 'GBP',
            amount: 1000,
            createdAt: new DateTimeImmutable(),
        );

        return $request->order;
    }

    /**
     * @return array<int, TransactionStatus>
     */
    protected function transactionStatuses(Order $order): array
    {
        return array_map(fn (Transaction $transaction) => $transaction->status, $order->transactions);
    }
}
