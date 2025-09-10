<?php

namespace AltDesign\AltCommerce\PaymentGateways\Stripe;


use AltDesign\AltCommerce\Contracts\PaymentGateway;
use AltDesign\AltCommerce\Contracts\PaymentGatewayFactory;
use AltDesign\AltCommerce\Contracts\Resolver;
use AltDesign\AltCommerce\Exceptions\InvalidConfigException;
use Stripe\StripeClient;

class StripeGatewayFactory implements PaymentGatewayFactory
{
    protected Resolver $resolver;

    public function __construct(Resolver $resolver)
    {
        $this->resolver = $resolver;
    }

    public function create(string $name, string $currency, array $config): PaymentGateway
    {
        if (empty($config['public_key']) || empty($config['secret_key'])) {
            throw new InvalidConfigException('Payment provider requires public_key and secret_key to be set.');
        }

        $client = new StripeClient($config['secret_key']);
        return new StripeGateway(
            name: $name,
            client: $client
        );
    }
}