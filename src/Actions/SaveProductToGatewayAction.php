<?php

namespace AltDesign\AltCommerce\Actions;

use AltDesign\AltCommerce\Commerce\Billing\RecurrentBillingSchema;
use AltDesign\AltCommerce\Commerce\Payment\GatewayBroker;
use AltDesign\AltCommerce\Contracts\Product;
use AltDesign\AltCommerce\Contracts\ProductRepository;

class SaveProductToGatewayAction
{
    public function __construct(
        protected GatewayBroker $gatewayBroker,
        protected ProductRepository $productRepository,
    )
    {

    }

    public function handle(Product $product): void
    {
        $price = $product->price();
        if ($price instanceof RecurrentBillingSchema) {
            foreach ($price->getPlans() as $plan) {
                $updatedAt = $plan->updatedAt;

                foreach ($plan->prices as $price) {
                    $plan = $this->gatewayBroker
                        ->currency($price->currency)
                        ->gateway()
                        ->saveBillingPlan($plan);
                }

                if ($plan->updatedAt > $updatedAt) {
                    $this->productRepository->saveBillingPlan($product->id(), $plan);
                }

            }
        }
    }
}