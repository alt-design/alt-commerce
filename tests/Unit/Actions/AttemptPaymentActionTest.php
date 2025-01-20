<?php

namespace AltDesign\AltCommerce\Tests\Unit\Actions;

use AltDesign\AltCommerce\Actions\AttemptPaymentAction;
use AltDesign\AltCommerce\Commerce\Customer\Address;
use AltDesign\AltCommerce\Commerce\Payment\PaymentRequest;
use AltDesign\AltCommerce\Commerce\Payment\Transaction;
use AltDesign\AltCommerce\Contracts\PaymentProvider;
use AltDesign\AltCommerce\Contracts\PaymentProviderRepository;
use Mockery;
use AltDesign\AltCommerce\Tests\Unit\TestCase;

class AttemptPaymentActionTest extends TestCase
{
    protected $paymentProviderRepository;
    protected $action;

    protected function setUp(): void
    {
        parent::setUp();

        $this->paymentProviderRepository = \Mockery::mock(PaymentProviderRepository::class);

        $this->action = new AttemptPaymentAction(
            paymentProviderRepository: $this->paymentProviderRepository
        );
    }

    public function test_attempt_payment()
    {
        $transaction = Mockery::mock(Transaction::class);
        $request = new PaymentRequest(
            token: 'payment-token',
            currency: 'USD',
            orderNumber: 'order-1',
            billingAddress: Mockery::mock(Address::class),
            total: 5000,
        );

        $paymentProvider = \Mockery::mock(PaymentProvider::class);
        $paymentProvider->allows()->attemptPayment($request)->andReturn($transaction);
        $this->paymentProviderRepository->allows()->find('test-provider')->andReturn($paymentProvider);

        $this->assertSame($transaction, $this->action->handle('test-provider', $request));
    }
}