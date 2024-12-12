<?php

namespace AltDesign\AltCommerce\Commerce\Shipping;
use AltDesign\AltCommerce\Commerce\Customer\Address;
use AltDesign\AltCommerce\Contracts\BasketRepository;
use AltDesign\AltCommerce\Contracts\ShippingMethodRepository;
use AltDesign\AltCommerce\RuleEngine\RuleManager;
use AltDesign\AltCommerce\Support\Price;

class ShippingManager
{
    public function __construct(
        protected ShippingMethodRepository $shippingMethodRepository,
        protected BasketRepository $basketRepository,
        protected RuleManager $ruleManager,
    )
    {

    }

    /**
     * @param Address $shippingAddress
     * @return ShippingRate[]
     */
    public function getAvailableRates(Address $shippingAddress): array
    {

        $methods = $this->shippingMethodRepository->get();

        $basket = $this->basketRepository->get();

        $rates = [];

        foreach ($methods as $method) {

            if ($basket->currency !== $method->currency()) {
                continue;
            }
            $evaluation = $this->ruleManager->evaluate($method->ruleGroup(), [
                'basket' => $basket,
                'shippingAddress' => $shippingAddress,
            ]);

            if (!$evaluation->result) {
                continue;
            }

            $rates[] = new ShippingRate(
                id: $method->id(),
                name: $method->name(),
                price: new Price($method->calculatePrice($basket, $shippingAddress), $method->currency()),
            );

        }

        return $rates;
    }


}