<?php

namespace AltDesign\AltCommerce\PaymentGateways\Braintree;

use AltDesign\AltCommerce\Exceptions\PaymentFailedException;
use AltDesign\AltCommerce\Exceptions\PaymentGatewayException;
use Braintree\Gateway;
use Braintree\Result\Error;

class BraintreeApiClient
{
    public function __construct(protected Gateway $gateway)
    {

    }

    public function request(callable $func): mixed
    {
        $response = $func($this->gateway);

        if ($response instanceof Error) {

            $status = $response->creditCardVerification?->status ;
            if ($status === 'processor_declined') {
                throw new PaymentFailedException();
            }

            // todo better exception that can take multiple errors.
            throw new PaymentGatewayException($response->message);
        }

        return $response;
    }

}
