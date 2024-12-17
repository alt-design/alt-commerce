<?php

namespace AltDesign\AltCommerce\Actions;

use AltDesign\AltCommerce\Commerce\Customer\Address;
use AltDesign\AltCommerce\Commerce\Order\Order;
use AltDesign\AltCommerce\Commerce\Order\OrderFactory;
use AltDesign\AltCommerce\Commerce\Payment\PaymentRequestFactory;
use AltDesign\AltCommerce\Contracts\BasketRepository;
use AltDesign\AltCommerce\Contracts\Customer;
use AltDesign\AltCommerce\Contracts\OrderRepository;
use AltDesign\AltCommerce\Enum\OrderStatus;
use AltDesign\AltCommerce\Enum\TransactionStatus;
use AltDesign\AltCommerce\Exceptions\PaymentFailedException;

class CreateOrderFromBasketAction
{
    public function __construct(
        protected AttemptPaymentAction $attemptPaymentAction,
        protected EmptyBasketAction $emptyBasketAction,
        protected BasketRepository $basketRepository,
        protected OrderRepository $orderRepository,
        protected OrderFactory $orderFactory,
        protected PaymentRequestFactory $paymentRequestFactory
    )
    {

    }

    public function handle(
        string $paymentProvider,
        string $paymentToken,
        Customer $customer,
        Address|null $billingAddress = null,
        Address|null $shippingAddress = null
    ): Order
    {
        $basket = $this->basketRepository->get();

        // find existing or create from basket
        $order = $this->orderRepository->findByBasketId($basket->id) ??
            $this->orderFactory->createFromBasket($basket, $customer, $billingAddress, $shippingAddress);

        // ensure order is correct status for attempting payment
        if ($order->status !== OrderStatus::DRAFT) {
            throw new PaymentFailedException('Unable to take payment for non draft orders');
        }

        $transaction = $this->attemptPaymentAction->handle(
            provider: $paymentProvider,
            request: $this->paymentRequestFactory->createFromOrder($order, $paymentToken),
        );

        $order->transactions[] = $transaction;
        $this->orderRepository->save($order);

        if ($transaction->status === TransactionStatus::FAILED) {
            throw new PaymentFailedException($transaction->rejectionReason ?? 'Unknown transaction failure');
        }

        if ($transaction->status === TransactionStatus::SETTLED) {
            $order->outstanding = 0;
            $order->status = OrderStatus::PROCESSING;
            $this->orderRepository->save($order);
        }

        $this->emptyBasketAction->handle();

        return $order;
    }
}