<?php

namespace AltDesign\AltCommerce\Tests\Unit\Actions;

use AltDesign\AltCommerce\Actions\AttemptPaymentAction;
use AltDesign\AltCommerce\Commerce\Payment\GatewayBroker;
use AltDesign\AltCommerce\Commerce\Payment\GatewayConfig;
use AltDesign\AltCommerce\Commerce\Payment\ProcessOrderRequest;
use AltDesign\AltCommerce\Contracts\OrderRepository;
use AltDesign\AltCommerce\Contracts\PaymentGateway;
use AltDesign\AltCommerce\Enum\OrderStatus;
use AltDesign\AltCommerce\Enum\TransactionStatus;
use AltDesign\AltCommerce\Exceptions\OrderAlreadyPlacedException;
use AltDesign\AltCommerce\Tests\Support\CommerceHelper;
use AltDesign\AltCommerce\Tests\Unit\TestCase;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\Attributes\DataProvider;

class AttemptPaymentActionTest extends TestCase
{
    use CommerceHelper;
    use MockeryPHPUnitIntegration;

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

        $gateway = Mockery::mock(PaymentGateway::class);
        $gateway->expects()->processOrder(Mockery::type(ProcessOrderRequest::class))->once()->andReturn($order);

        $config = Mockery::mock(GatewayConfig::class);
        $config->allows()->gateway()->andReturn($gateway);
        $config->allows()->name()->andReturn('stripe');

        $gatewayBroker = Mockery::mock(GatewayBroker::class);
        $gatewayBroker->allows()->currency('GBP')->andReturn($config);

        $orderRepository = Mockery::mock(OrderRepository::class);
        $orderRepository->expects()->save($order)->once();

        (new AttemptPaymentAction($orderRepository, $gatewayBroker))->handle($order, 'nonce');
    }
}
