<?php

namespace AltDesign\AltCommerce\Tests\Unit\PaymentGateways;

use AltDesign\AltCommerce\Commerce\Basket\BasketManager;
use AltDesign\AltCommerce\Commerce\Order\Order;
use AltDesign\AltCommerce\Commerce\Payment\ProcessOrderRequest;
use AltDesign\AltCommerce\Enum\OrderStatus;
use AltDesign\AltCommerce\Exceptions\PaymentFailedException;
use AltDesign\AltCommerce\PaymentGateways\Stripe\StripeGateway;
use AltDesign\AltCommerce\Tests\Unit\TestCase;
use DateTimeImmutable;
use Mockery;
use Stripe\PaymentIntent;
use Stripe\StripeClient;

class StripeGatewayTest extends TestCase
{
    protected $paymentIntents;
    protected $gateway;

    public function setUp(): void
    {
        $this->paymentIntents = Mockery::mock();

        $client = Mockery::mock(StripeClient::class);
        $client->paymentIntents = $this->paymentIntents;

        $this->gateway = new StripeGateway(
            name: 'stripe',
            basketManager: Mockery::mock(BasketManager::class),
            client: $client,
        );
    }

    public function test_captures_when_the_intent_matches_the_order()
    {
        $this->paymentIntents->allows()->retrieve('pi_correct')->andReturn(
            PaymentIntent::constructFrom(['id' => 'pi_correct', 'amount' => 10000, 'currency' => 'gbp'])
        );

        $this->paymentIntents->expects()->capture('pi_correct')->once()->andReturn(
            PaymentIntent::constructFrom([
                'id' => 'pi_correct',
                'amount' => 10000,
                'currency' => 'gbp',
                'status' => 'succeeded',
                'cancellation_reason' => null,
            ])
        );

        $order = $this->createOrder(total: 10000, currency: 'GBP');

        $result = $this->gateway->processOrder(new ProcessOrderRequest(
            order: $order,
            gatewayName: 'stripe',
            gatewayPaymentNonce: 'pi_correct',
        ));

        $this->assertCount(1, $result->transactions);
        $this->assertEquals(10000, $result->transactions[0]->amount);
    }

    public function test_refuses_to_capture_an_intent_raised_for_a_smaller_amount()
    {
        $this->paymentIntents->allows()->retrieve('pi_cheap')->andReturn(
            PaymentIntent::constructFrom(['id' => 'pi_cheap', 'amount' => 100, 'currency' => 'gbp'])
        );

        $this->paymentIntents->expects()->capture()->never();

        $this->expectException(PaymentFailedException::class);

        $this->gateway->processOrder(new ProcessOrderRequest(
            order: $this->createOrder(total: 10000, currency: 'GBP'),
            gatewayName: 'stripe',
            gatewayPaymentNonce: 'pi_cheap',
        ));
    }

    public function test_refuses_to_capture_an_intent_in_another_currency()
    {
        $this->paymentIntents->allows()->retrieve('pi_usd')->andReturn(
            PaymentIntent::constructFrom(['id' => 'pi_usd', 'amount' => 10000, 'currency' => 'usd'])
        );

        $this->paymentIntents->expects()->capture()->never();

        $this->expectException(PaymentFailedException::class);

        $this->gateway->processOrder(new ProcessOrderRequest(
            order: $this->createOrder(total: 10000, currency: 'GBP'),
            gatewayName: 'stripe',
            gatewayPaymentNonce: 'pi_usd',
        ));
    }

    /**
     * Order is abstract, so the concrete class lives in the adapter package.
     */
    protected function createOrder(int $total, string $currency): Order
    {
        return new class(
            id: 'order-id',
            customerId: 'customer-id',
            customerName: 'Test Customer',
            customerEmail: 'customer@example.com',
            status: OrderStatus::DRAFT,
            currency: $currency,
            orderNumber: 'TEST-00001',
            lineItems: [],
            taxItems: [],
            discountItems: [],
            deliveryItems: [],
            feeItems: [],
            billingItems: [],
            subTotal: $total,
            taxTotal: 0,
            deliveryTotal: 0,
            discountTotal: 0,
            feeTotal: 0,
            total: $total,
            outstanding: $total,
            orderDate: new DateTimeImmutable(),
            createdAt: new DateTimeImmutable(),
        ) extends Order {};
    }
}
