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
use Ramsey\Uuid\Uuid;

class Order
{

    public readonly string $id;
    
    public readonly DateTimeImmutable $createdAt;

    /**
     * @param Customer $customer
     * @param OrderStatus $status
     * @param string $currency
     * @param string $orderNumber
     * @param LineItem[] $lineItems
     * @param TaxItem[] $taxItems
     * @param DiscountItem[] $discountItems
     * @param DeliveryItem[] $deliveryItems
     * @param FeeItem[] $feeItems
     * @param BillingItem[] $billingItems
     * @param int $subTotal
     * @param int $taxTotal
     * @param int $deliveryTotal
     * @param int $discountTotal
     * @param int $feeTotal
     * @param int $total
     * @param int $outstanding
     * @param string|null $basketId
     * @param Address|null $billingAddress
     * @param Address|null $shippingAddress
     * @param Transaction[] $transactions
     * @param Subscription[] $subscriptions
     * @param array<string, string> $additional
     */
    public function __construct(
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
        public string|null $basketId = null,
        public Address|null $billingAddress = null,
        public Address|null $shippingAddress = null,
        public array $transactions = [],
        public array $subscriptions = [],
        public array $additional = [],
    )
    {
        $this->id = Uuid::uuid4()->toString();
        $this->createdAt = new DateTimeImmutable();
    }
}