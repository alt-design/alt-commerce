<?php

namespace AltDesign\AltCommerce\Tests\Unit\Commerce\Billing;

use AltDesign\AltCommerce\Commerce\Billing\BillingPlan;
use AltDesign\AltCommerce\Commerce\Billing\RecurrentBillingSchema;
use AltDesign\AltCommerce\Enum\DurationUnit;
use AltDesign\AltCommerce\Support\Duration;
use AltDesign\AltCommerce\Support\Money;
use AltDesign\AltCommerce\Support\PriceCollection;
use AltDesign\AltCommerce\Tests\Unit\TestCase;

class RecurrentBillingSchemaTest extends TestCase
{
    public function test_schema()
    {
        $schema = new RecurrentBillingSchema(
            plans: [
                new BillingPlan(
                    id: '1-month',
                    name: 'monthly plan',
                    prices: new PriceCollection([
                        new Money(1000, 'GBP'),
                        new Money(1500, 'USD'),
                        new Money(1300, 'EUR')
                    ]),
                    billingInterval: new Duration(1, DurationUnit::MONTH)
                ),
                new BillingPlan(
                    id: '6-month',
                    name: '6 month plan',
                    prices: new PriceCollection([
                        new Money(5000, 'GBP'),
                        new Money(7500, 'USD'),
                        new Money(6500, 'EUR')
                    ]),
                    billingInterval: new Duration(6, DurationUnit::MONTH)
                ),
                new BillingPlan(
                    id: '1-year',
                    name: '1 year plan',
                    prices: new PriceCollection([
                        new Money(10000, 'GBP'),
                        new Money(15000, 'USD'),
                        new Money(12000, 'EUR')
                    ]),
                    billingInterval: new Duration(1, DurationUnit::YEAR)
                ),
                new BillingPlan(
                    id: '3-year',
                    name: '3 year plan',
                    prices: new PriceCollection([
                        new Money(25200, 'GBP'),
                    ]),
                    billingInterval: new Duration(3, DurationUnit::YEAR)
                ),
                new BillingPlan(
                    id: '5-year',
                    name: '5 year plan',
                    prices: new PriceCollection([
                        new Money(100000, 'AUD'),
                    ]),
                    billingInterval: new Duration(5, DurationUnit::YEAR)
                ),
            ]
        );

        $this->assertEquals(1000, $schema->getAmount('GBP', ['plan' => '1-month']));
        $this->assertEquals(1500, $schema->getAmount( 'USD', ['plan' =>  '1-month']));
        $this->assertEquals(1300, $schema->getAmount( 'EUR', ['plan' => '1-month']));
        $this->assertEquals(5000, $schema->getAmount('GBP', ['plan' => '6-month']));
        $this->assertEquals(7500, $schema->getAmount( 'USD', ['plan' => '6-month']));
        $this->assertEquals(6500, $schema->getAmount( 'EUR', ['plan' => '6-month']));
        $this->assertEquals(10000, $schema->getAmount('GBP', ['plan' => '1-year']));
        $this->assertEquals(15000, $schema->getAmount( 'USD',['plan' =>  '1-year']));
        $this->assertEquals(12000, $schema->getAmount( 'EUR',['plan' =>  '1-year']));
        $this->assertEquals(25200, $schema->getAmount('GBP', ['plan' => '3-year']));
        $this->assertEquals(100000, $schema->getAmount('AUD', ['plan' => '5-year']));

        $this->assertEquals('1:month', (string)$schema->getBillingPlan('GBP', ['plan' => '1-month'])->billingInterval);
        $this->assertEquals('6:month', (string)$schema->getBillingPlan('GBP',['plan' => '6-month'])->billingInterval);
        $this->assertEquals('1:year', (string)$schema->getBillingPlan('GBP', ['plan' => '1-year'])->billingInterval);
        $this->assertEquals('3:year', (string)$schema->getBillingPlan('GBP', ['plan' => '3-year'])->billingInterval);
        $this->assertEquals('5:year', (string)$schema->getBillingPlan('AUD', ['plan' => '5-year'])->billingInterval);

        $this->assertEquals('1:month', $schema->minimumBillingInterval('GBP'));
        $this->assertEquals('3-year', $schema->cheapest('GBP')->id);
        $this->assertEquals('1-month', $schema->mostExpensive('GBP')->id);
        $this->assertEquals('1:month', $schema->minimumBillingInterval('USD'));
        $this->assertEquals('1-year', $schema->cheapest('USD')->id);
        $this->assertEquals('1-month', $schema->mostExpensive('USD')->id);
        $this->assertEquals('1:month', $schema->minimumBillingInterval('EUR'));
        $this->assertEquals('1-year', $schema->cheapest('EUR')->id);
        $this->assertEquals('1-month', $schema->mostExpensive('EUR')->id);
        $this->assertEquals('5:year', $schema->minimumBillingInterval('AUD'));
        $this->assertEquals('5-year', $schema->cheapest('AUD')->id);
        $this->assertEquals('5-year', $schema->mostExpensive('AUD')->id);

    }


    public function test_currency_support()
    {
        $schema = new RecurrentBillingSchema(
            plans: [
                new BillingPlan(
                    id: '1-month',
                    name: 'monthly plan',
                    prices: new PriceCollection([
                        new Money(1000, 'GBP'),
                        new Money(1300, 'EUR')
                    ]),
                    billingInterval: new Duration(1, DurationUnit::MONTH)
                ),
                new BillingPlan(
                    id: '6-month',
                    name: '6 monthly plan',
                    prices: new PriceCollection([
                        new Money(7500, 'USD'),
                    ]),
                    billingInterval: new Duration(6, DurationUnit::MONTH)
                ),
            ]
        );

        $this->assertTrue($schema->isCurrencySupported('GbP'));
        $this->assertTrue($schema->isCurrencySupported('USd'));
        $this->assertTrue($schema->isCurrencySupported('EUR'));
        $this->assertFalse($schema->isCurrencySupported('AUD'));
    }

    public function test_get_supported_plans()
    {

        $plan1 = new BillingPlan(
            id: 'plan-1',
            name: 'monthly plan',
            prices: new PriceCollection([
                new Money(1000, 'GBP'),
            ]),
            billingInterval: new Duration(1, DurationUnit::MONTH)
        );

        $plan2 = new BillingPlan(
            id: 'plan-2',
            name: 'monthly plan',
            prices: new PriceCollection([
                new Money(1000, 'GBP'),
                new Money(1000, 'EUR')
            ]),
            billingInterval: new Duration(1, DurationUnit::MONTH)
        );

        $plan3 = new BillingPlan(
            id: 'plan-3',
            name: 'monthly plan',
            prices: new PriceCollection([
                new Money(1000, 'USD')
            ]),
            billingInterval: new Duration(1, DurationUnit::MONTH)
        );

        $schema = new RecurrentBillingSchema(plans: [$plan1, $plan2, $plan3]);

        $this->assertEquals([$plan1, $plan2], $schema->getSupportedPlans('GBP'));
        $this->assertEquals([$plan2], $schema->getSupportedPlans('EUR'));
        $this->assertEquals([$plan3], $schema->getSupportedPlans('USD'));


    }
}