<?php

namespace AltDesign\AltCommerce\Actions;

use AltDesign\AltCommerce\Commerce\Payment\PaymentRequest;
use AltDesign\AltCommerce\Commerce\Payment\Transaction;
use AltDesign\AltCommerce\Contracts\PaymentProviderRepository;
use AltDesign\AltCommerce\Exceptions\PaymentGatewayException;

class AttemptPaymentAction
{
    public function __construct(
        protected PaymentProviderRepository $paymentProviderRepository,
    )
    {

    }

    public function handle(string $provider, PaymentRequest $request): Transaction
    {
        $provider = $this->paymentProviderRepository->find($provider);
        if (empty($provider)) {
            throw new PaymentGatewayException('Payment provider not found');
        }
        return $provider->attemptPayment($request);
    }

}