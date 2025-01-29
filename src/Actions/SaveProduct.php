<?php

namespace AltDesign\AltCommerce\Actions;

use AltDesign\AltCommerce\Commerce\Billing\RecurrentBillingSchema;
use AltDesign\AltCommerce\Commerce\Payment\GatewayBroker;
use AltDesign\AltCommerce\Contracts\Product;
use AltDesign\AltCommerce\Contracts\ProductRepository;

class SaveProduct
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
                foreach ($plan->prices as $price) {

                    $config = $this->gatewayBroker->currency($price->currency);

                    $gatewayPlanId = $this->productRepository->getGatewayIdForBillingPlan(
                        productId: $product->id(),
                        planId: $plan->id,
                        currency: $price->currency,
                        gateway: $config->driver(),
                    );

                    if ($gatewayPlanId) {
                        $config->gateway()->updateBillingPlan($gatewayPlanId, $plan);
                    } else {

                        $gatewayPlanId = $config->gateway()->createBillingPlan($plan);
                        $this->productRepository->saveGatewayIdForBillingPlan(
                            productId: $product->id(),
                            planId: $plan->id,
                            currency: $price->currency,
                            gateway: $config->driver(),
                            gatewayId: $gatewayPlanId
                        );
                    }
                }
            }
        }
    }
}