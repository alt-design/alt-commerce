<?php

namespace AltDesign\AltCommerce\Commerce\Order;

use AltDesign\AltCommerce\Commerce\Basket\BillingItem;
use AltDesign\AltCommerce\Commerce\Basket\DeliveryItem;
use AltDesign\AltCommerce\Commerce\Basket\FeeItem;
use AltDesign\AltCommerce\Commerce\Basket\LineItem;
use AltDesign\AltCommerce\Commerce\Basket\TaxItem;
use AltDesign\AltCommerce\Commerce\Billing\Subscription;
use AltDesign\AltCommerce\Commerce\Customer\Address;
use AltDesign\AltCommerce\Commerce\Payment\Transaction;
use AltDesign\AltCommerce\Contracts\Customer;
use AltDesign\AltCommerce\Contracts\DiscountItem;
use AltDesign\AltCommerce\Enum\OrderStatus;
use DateTimeImmutable;

class Order
{

    /**
     * @param LineItem[] $lineItems
     * @param TaxItem[] $taxItems
     * @param DiscountItem[] $discountItems
     * @param DeliveryItem[] $deliveryItems
     * @param FeeItem[] $feeItems
     * @param BillingItem[] $billingItems
     * @param Transaction[] $transactions
     * @param Subscription[] $subscriptions
     * @param array<string, string> $additional
     */
    public function __construct(
        public string $id,
        public Customer $customer,
        public OrderStatus $status,
        public string $currency,
        public string $orderNumber,
        public array $lineItems,
        public array $taxItems,
        public array $discountItems,
        public array $deliveryItems,
        public array $feeItems,
        public array $billingItems,
        public int $subTotal,
        public int $taxTotal,
        public int $deliveryTotal,
        public int $discountTotal,
        public int $feeTotal,
        public int $total,
        public int $outstanding,
        public DateTimeImmutable $createdAt,
        public string|null $basketId = null,
        public Address|null $billingAddress = null,
        public Address|null $shippingAddress = null,
        public array $transactions = [],
        public array $subscriptions = [],
        public array $additional = [],
    )
    {

    }
}