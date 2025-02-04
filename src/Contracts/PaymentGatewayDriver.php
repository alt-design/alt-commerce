<?php

namespace AltDesign\AltCommerce\Contracts;

interface PaymentGatewayDriver
{
    public function name(): string;

    public function factory(Resolver $resolver): PaymentGatewayFactory;
}