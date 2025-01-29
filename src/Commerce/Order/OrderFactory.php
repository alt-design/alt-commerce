<?php

namespace AltDesign\AltCommerce\Commerce\Order;

use AltDesign\AltCommerce\Commerce\Basket\Basket;
use AltDesign\AltCommerce\Commerce\Customer\Address;
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
     * @param array<string, mixed> $additional
     * @return Order
     */
    public function createFromBasket(
        Basket $basket,
        Customer $customer,
        array $additional = []
    ): Order
    {
        $billingAddress = $additional['billing_address'] instanceof Address ?
            $additional['billing_address'] : null;

        $shippingAddress = $additional['shipping_address'] instanceof Address ?
            $additional['shipping_address'] : null;

        unset($additional['billing_address'], $additional['shipping_address']);

        return new Order(
            customer: $customer,
            status: OrderStatus::DRAFT,
            currency: $basket->currency,
            orderNumber: $this->orderNumberGenerator->reserve(),
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
            basketId: $basket->id,
            billingAddress: $billingAddress,
            shippingAddress: $shippingAddress,
            transactions: [],
            additional: $additional,
        );
    }
}