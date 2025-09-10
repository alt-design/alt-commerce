<?php

namespace AltDesign\AltCommerce\Commerce\Payment;

use AltDesign\AltCommerce\Contracts\PaymentGatewayDriver;
use AltDesign\AltCommerce\Contracts\Resolver;
use AltDesign\AltCommerce\Exceptions\PaymentGatewayException;
use AltDesign\AltCommerce\PaymentGateways\Braintree\BraintreeGatewayDriver;
use AltDesign\AltCommerce\PaymentGateways\Stripe\StripeGatewayDriver;

class GatewayBroker
{

    protected bool $loaded = false;

    /**
     * @var GatewayConfig[]
     */
    protected array $gateways = [];

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(protected Resolver $resolver, protected array $config = [])
    {

    }

    public function currency(string $currency): GatewayConfig
    {
        $this->load();

        $currency = strtoupper($currency);

        return $this->gateways[$currency] ?? throw new PaymentGatewayException("No payment gateway found for currency {$currency}");
    }


    protected function load(): void
    {
        if ($this->loaded) {
            return;
        }

        foreach ($this->config['available'] as $name => $config) {

            if (!in_array($name, $this->config['enabled'] ?? [])) {
                continue;
            }

            if (empty($config['driver']) || !array_key_exists($config['driver'], self::drivers())) {
                throw new PaymentGatewayException("No driver found for payment gateway {$name}");
            }

            if (empty($config['currency'])) {
                throw new PaymentGatewayException("No currency found for payment gateway {$name}");
            }

            $currencies = is_array($config['currency']) ? $config['currency'] : [$config['currency']];

            /**
             * @var PaymentGatewayDriver $driver
             */
            $driver = new (self::drivers()[$config['driver']]);
            foreach ($currencies as $currency) {
                $this->gateways[$currency] = new GatewayConfig(
                    name: $name,
                    driver: $config['driver'],
                    gateway: $driver->factory($this->resolver)->create($name, $currency, $config)
                );
            }
        }

        $this->loaded = true;
    }

    /**
     * @return array<string, string>
     */
    public static function drivers(): array
    {
        return [
            'braintree' => BraintreeGatewayDriver::class,
            'stripe' => StripeGatewayDriver::class,
        ];
    }
}