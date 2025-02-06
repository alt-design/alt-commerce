<?php

namespace AltDesign\AltCommerce\Commerce\Order;

use AltDesign\AltCommerce\Commerce\Basket\Basket;
use AltDesign\AltCommerce\Commerce\Customer\Address;
use AltDesign\AltCommerce\Contracts\Customer;
use AltDesign\AltCommerce\Enum\OrderStatus;
use Ramsey\Uuid\Uuid;

class OrderFactory
{

    /**
     * @param array<string, mixed> $additional
     */
    public function createFromBasket(
        string $orderNumber,
        Basket $basket,
        Customer $customer,
        array $additional = [],
        string|null $orderId = null,
        \DateTimeImmutable|null $orderDate = null,
    ): Order
    {
        $billingAddress = ($additional['billing_address'] ?? null) instanceof Address ?
            $additional['billing_address'] : null;

        $shippingAddress = ($additional['shipping_address'] ?? null) instanceof Address ?
            $additional['shipping_address'] : null;

        unset($additional['billing_address'], $additional['shipping_address']);

        return new Order(
            id: $orderId ?? Uuid::uuid4(),
            customer: $customer,
            status: OrderStatus::DRAFT,
            currency: $basket->currency,
            orderNumber: $orderNumber,
            lineItems: $basket->lineItems,
            taxItems: $basket->taxItems,
            discountItems: $basket->discountItems,
            deliveryItems: $basket->deliveryItems,
            feeItems: $basket->feeItems,
            billingItems: $basket->billingItems,
            subTotal: $basket->subTotal,
            taxTotal: $basket->taxTotal,
            deliveryTotal: $basket->deliveryTotal,
            discountTotal: $basket->discountTotal,
            feeTotal: $basket->feeTotal,
            total: $basket->total,
            outstanding: $basket->total,
            orderDate: $orderDate ?? new \DateTimeImmutable(),
            createdAt: new \DateTimeImmutable(),
            basketId: $basket->id,
            billingAddress: $billingAddress,
            shippingAddress: $shippingAddress,
            transactions: [],
            additional: $additional,
        );
    }
}