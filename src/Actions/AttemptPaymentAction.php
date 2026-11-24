<?php

namespace AltDesign\AltCommerce\Actions;

use AltDesign\AltCommerce\Commerce\Order\Order;
use AltDesign\AltCommerce\Commerce\Payment\GatewayBroker;
use AltDesign\AltCommerce\Commerce\Payment\ProcessOrderRequest;
use AltDesign\AltCommerce\Commerce\Payment\Transaction;
use AltDesign\AltCommerce\Contracts\OrderRepository;
use AltDesign\AltCommerce\Enum\TransactionStatus;
use AltDesign\AltCommerce\Enum\TransactionType;
use AltDesign\AltCommerce\Exceptions\OrderAlreadyPlacedException;
use AltDesign\AltCommerce\Exceptions\PaymentOutcomeUnknownException;
use DateTimeImmutable;
use Ramsey\Uuid\Uuid;
use Throwable;

class AttemptPaymentAction
{

    public function __construct(
        protected OrderRepository    $orderRepository,
        protected GatewayBroker      $gatewayBroker,
    )
    {

    }

    /**
     * The attempt is saved before the gateway is called, so a payment that is
     * taken and then not recorded still stops the order being charged again.
     *
     * @throws OrderAlreadyPlacedException
     * @throws PaymentOutcomeUnknownException
     */
    public function handle(Order $order, string $paymentNonce): void
    {
        if (! $order->isUnpaidDraft()) {
            throw new OrderAlreadyPlacedException("Order {$order->orderNumber} has already been placed or paid");
        }

        $config = $this->gatewayBroker->currency($order->currency);
        $gateway = $config->gateway();
        $attempt = $this->recordAttempt($order, $config->name(), $paymentNonce);

        try {
            $order = $gateway->processOrder(
                new ProcessOrderRequest(
                    order: $order,
                    gatewayName: $config->name(),
                    gatewayPaymentNonce: $paymentNonce,
                )
            );
        } catch (PaymentOutcomeUnknownException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            $this->removeAttempt($order, $attempt);

            throw $exception;
        }

        try {
            $this->removeAttempt($order, $attempt);
        } catch (Throwable $exception) {
            throw new PaymentOutcomeUnknownException("The payment for order {$order->orderNumber} could not be recorded", previous: $exception);
        }
    }

    protected function recordAttempt(Order $order, string $gatewayName, string $paymentNonce): Transaction
    {
        $attempt = new Transaction(
            id: Uuid::uuid4()->toString(),
            type: TransactionType::SALE,
            status: TransactionStatus::PENDING,
            currency: $order->currency,
            amount: $order->total,
            createdAt: new DateTimeImmutable(),
            additional: ['id' => $paymentNonce],
            gateway: $gatewayName,
            gatewayId: $paymentNonce,
        );

        $order->transactions[] = $attempt;
        $this->orderRepository->save($order);

        return $attempt;
    }

    protected function removeAttempt(Order $order, Transaction $attempt): void
    {
        $order->transactions = array_values(array_filter(
            $order->transactions,
            fn (Transaction $transaction) => $transaction->id !== $attempt->id
        ));

        $this->orderRepository->save($order);
    }
}
