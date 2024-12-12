<?php

namespace AltDesign\AltCommerce\Commerce\Order;

use AltDesign\AltCommerce\Commerce\Basket\Basket;
use AltDesign\AltCommerce\Contracts\Customer;
use AltDesign\AltCommerce\Contracts\OrderNumberGenerator;
use AltDesign\AltCommerce\Enum\OrderStatus;

class OrderFactory
{
    public function __construct(protected OrderNumberGenerator $orderNumberGenerator)
    {

    }

    /**
     * @param Basket $basket
     * @param Customer $customer
     * @param array<string, string> $additional
     * @return Order
     */
    public function createFromBasket(Basket $basket, Customer $customer, array $additional = []): Order
    {

        $billingAddress = $customer->billingAddress();
        $shippingAddress = $customer->shippingAddress();

        return new Order(
            status: OrderStatus::DRAFT,
            currency: $basket->currency,
            orderNumber: $this->orderNumberGenerator->reserve(),
            lineItems: $basket->lineItems,
            taxItems: $basket->taxItems,
            discountItems: $basket->discountItems,
            deliveryItems: $basket->deliveryItems,
            feeItems: $basket->feeItems,
            subTotal: $basket->subTotal,
            taxTotal: $basket->taxTotal,
            deliveryTotal: $basket->deliveryTotal,
            discountTotal: $basket->discountTotal,
            feeTotal: $basket->feeTotal,
            total: $basket->total,
            outstanding: $basket->total,
            billingAddress: $billingAddress ? clone $billingAddress : null,
            shippingAddress: $shippingAddress ? clone $shippingAddress : null,
            transactions: [],
            additional: $additional,
        );

    }
}