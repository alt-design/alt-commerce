<?php

namespace AltDesign\AltCommerce\Tests\Unit\Commerce\Payment;

use AltDesign\AltCommerce\Commerce\Payment\BraintreePaymentProvider;
use AltDesign\AltCommerce\Commerce\Payment\PaymentProviderRepositoryFactory;
use AltDesign\AltCommerce\Commerce\Settings\Settings;
use AltDesign\AltCommerce\Contracts\SettingsRepository;
use Mockery;
use AltDesign\AltCommerce\Tests\Unit\TestCase;

class PaymentProviderRepositoryFactoryTest extends TestCase
{

    protected PaymentProviderRepositoryFactory $paymentProviderRepositoryFactory;

    protected $settingsRepository;

    protected $settings;

    protected function setUp(): void
    {
        parent::setUp();

        $this->settings = Mockery::mock(Settings::class);

        $this->settingsRepository = Mockery::mock(SettingsRepository::class);
        $this->settingsRepository->allows()->get()->andReturn($this->settings);

        $this->paymentProviderRepositoryFactory = new PaymentProviderRepositoryFactory(
            settingsRepository: $this->settingsRepository,
        );
    }

    public function test_create_from_config(): void
    {

        $config = [
            'enabled' => ['braintree_gbp', 'braintree_usd'],
            'available' => [
                'braintree_gbp' => [
                    'driver' => 'braintree',
                    'mode' => 'sandbox',
                    'currency' => 'GBP',
                    'merchant_id' => '...merchant_id...',
                    'public_key' => '...public_key...',
                    'private_key' => '...private_key...'
                ],
                'braintree_gbp_1' => [
                    'driver' => 'braintree',
                    'mode' => 'sandbox',
                    'currency' => 'GBP',
                    'merchant_id' => '...merchant_id...',
                    'public_key' => '...public_key...',
                    'private_key' => '...private_key...'
                ],
                'braintree_usd' => [
                    'driver' => 'braintree',
                    'mode' => 'sandbox',
                    'currency' => 'USD',
                    'merchant_id' => '...merchant_id...',
                    'public_key' => '...public_key...',
                    'private_key' => '...private_key...'
                ],
            ]
        ];

        $repository = $this->paymentProviderRepositoryFactory->createFromConfig($config);

        $provider = $repository->find('braintree_gbp');
        $this->assertEquals(BraintreePaymentProvider::class, get_class($provider));
        $this->assertEquals('braintree_gbp', $provider->name());

        $provider = $repository->find('braintree_gbp_1');
        $this->assertNull($provider);

        $provider = $repository->find('braintree_usd');
        $this->assertEquals(BraintreePaymentProvider::class, get_class($provider));
        $this->assertEquals('braintree_usd', $provider->name());


        $this->assertEquals('braintree_gbp', $repository->findSuitable('GB', 'GBP')->name());
        $this->assertEquals('braintree_usd', $repository->findSuitable('US', 'USD')->name());
        $this->assertNull($repository->findSuitable('IT', 'EUR'));



    }
}