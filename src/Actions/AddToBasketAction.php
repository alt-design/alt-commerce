<?php

namespace AltDesign\AltCommerce\Actions;

use AltDesign\AltCommerce\Commerce\Basket\BasketContext;
use AltDesign\AltCommerce\Commerce\Basket\BillingItem;
use AltDesign\AltCommerce\Commerce\Basket\LineItem;
use AltDesign\AltCommerce\Contracts\ProductRepository;
use AltDesign\AltCommerce\Contracts\Settings;
use AltDesign\AltCommerce\Exceptions\BasketException;
use AltDesign\AltCommerce\Exceptions\CurrencyNotSupportedException;
use AltDesign\AltCommerce\Exceptions\ProductNotFoundException;
use AltDesign\AltCommerce\Services\PriceCalculatorService\Service as PriceCalculationService;
use AltDesign\AltCommerce\Traits\InteractWithBasket;
use Ramsey\Uuid\Uuid;

class AddToBasketAction
{
    use InteractWithBasket;

    public function __construct(
        protected BasketContext           $context,
        protected ProductRepository       $productRepository,
        protected Settings $settings,
        protected PriceCalculationService $priceCalculatorService,
    )
    {

    }

    /**
     * @param array<string, string> $options
     */
    public function handle(string $productId, int $quantity = 1, int $price = null, array $options = []): void
    {

        $basket = $this->context->current();

        $existing = $this->find($basket, $productId, $options);

        if ($existing instanceof BillingItem) {
            throw new BasketException('Billing item is already in basket');
        }

        if ($existing instanceof LineItem) {
            $existing->quantity += $quantity;
            $this->context->recalculateBasket();
            return;
        }

        $product = $this->productRepository->find($productId);

        if (empty($product)) {
            throw new ProductNotFoundException($productId);
        }

        if (!$product->price()->isCurrencySupported($basket->currency)) {
            throw new CurrencyNotSupportedException();
        }

        if ($product->price()->hasBillingPlan()) {
            $billingPlan = $product->price()->getBillingPlan($basket->currency, ['plan' => $options['plan'] ?? null]);
            $basket->billingItems[] = new BillingItem(
                id: Uuid::uuid4(),
                productId: $product->id(),
                billingPlanId: $billingPlan->id,
                productName: $product->name(),
                amount: $billingPlan->prices->getAmount($basket->currency),
                billingInterval: $billingPlan->billingInterval,
                trialPeriod: $billingPlan->trialPeriod,
                additional: $billingPlan->data,
                gatewayEntities: $billingPlan->gatewayEntities,
            );

        } else {
            
            $amount = $price !== null ? $price : $product->price()->getAmount($basket->currency, ['quantity' => $quantity]);

            $response = $this->priceCalculatorService->calculate(
                currency: $basket->currency,
                amount: $amount,
                amountInclusive: $this->settings->pricesInclusive(),
                countryCode: $basket->countryCode,
                taxRules: $product->taxRules(),
            );

            $basket->lineItems[] = new LineItem(
                id: Uuid::uuid4(),
                productId: $product->id(),
                productName: $product->name(),
                amount: $response->exclusiveAmount,
                quantity: $quantity,
                taxable: $product->taxable(),
                taxRules: $product->taxRules(),
                options: $options,
                productData: $product->data(),
            );
        }
    }
}