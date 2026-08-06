<?php

namespace AltDesign\AltCommerce\Actions;

use AltDesign\AltCommerce\Commerce\Basket\BasketContext;
use AltDesign\AltCommerce\Commerce\Customer\Address;
use AltDesign\AltCommerce\Commerce\Order\Order;
use AltDesign\AltCommerce\Contracts\Customer;
use AltDesign\AltCommerce\Contracts\OrderFactory;
use AltDesign\AltCommerce\Contracts\OrderRepository;
use AltDesign\AltCommerce\Contracts\ProductRepository;
use AltDesign\AltCommerce\Contracts\StockRepository;
use AltDesign\AltCommerce\Enum\StockPolicy;

class CreateOrderAction
{
    public function __construct(
        protected BasketContext $context,
        protected OrderRepository $orderRepository,
        protected OrderFactory $orderFactory,
        protected ProductRepository $productRepository,
        protected StockRepository $stockRepository,
    ) {}

    /**
     * @param  array<string, mixed>  $additional
     */
    public function handle(
        Customer $customer,
        ?Address $billingAddress = null,
        ?Address $shippingAddress = null,
        array $additional = [],
        ?\DateTimeImmutable $orderDate = null
    ): Order {
        $basket = $this->context->current();

        $order = $this->orderRepository->findByBasketId($basket->id);

        $orderId = $order?->id;
        $orderNumber = $order ? $order->orderNumber : $this->orderRepository->reserveOrderNumber();

        $order = $this->orderFactory->createFromBasket(
            orderNumber: $orderNumber,
            basket: $basket,
            customer: $customer,
            billingAddress: $billingAddress,
            shippingAddress: $shippingAddress,
            additional: $additional,
            orderId: $orderId,
            orderDate: $orderDate,
        );

        $this->orderRepository->save($order);

        $this->reduceStock($order);

        return $order;
    }

    /**
     * Reduce counted stock for the order's lines. Runs once per order (guarded
     * by the movement reference) and skips untracked products. Backorder lines
     * are permitted to take the level negative.
     */
    protected function reduceStock(Order $order): void
    {
        if ($this->stockRepository->hasMovementsForReference($order->id)) {
            return;
        }

        foreach ($order->lineItems as $lineItem) {
            $product = $this->productRepository->find($lineItem->productId);

            if (! $product || $product->stockPolicy() === StockPolicy::UNTRACKED) {
                continue;
            }

            $this->stockRepository->adjust(
                productId: $lineItem->productId,
                quantity: -$lineItem->quantity,
                reason: 'order',
                reference: $order->id,
            );
        }
    }
}
