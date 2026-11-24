<?php

namespace AltDesign\AltCommerce\Tests\Unit\PaymentGateways;

use AltDesign\AltCommerce\Commerce\Basket\BasketManager;
use AltDesign\AltCommerce\Commerce\Order\Order;
use AltDesign\AltCommerce\Commerce\Payment\ProcessOrderRequest;
use AltDesign\AltCommerce\Enum\OrderStatus;
use AltDesign\AltCommerce\Enum\TransactionStatus;
use AltDesign\AltCommerce\Exceptions\PaymentFailedException;
use AltDesign\AltCommerce\Exceptions\PaymentOutcomeUnknownException;
use AltDesign\AltCommerce\PaymentGateways\Stripe\StripeGateway;
use AltDesign\AltCommerce\Tests\Unit\TestCase;
use DateTimeImmutable;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\Attributes\DataProvider;
use Stripe\Exception\ApiConnectionException;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\CardException;
use Stripe\Exception\IdempotencyException;
use Stripe\Exception\InvalidRequestException;
use Stripe\Exception\RateLimitException;
use Stripe\Exception\UnexpectedValueException;
use Stripe\Exception\UnknownApiErrorException;
use Stripe\PaymentIntent;
use Stripe\StripeClient;
use Throwable;

class StripeGatewayTest extends TestCase
{
    use MockeryPHPUnitIntegration;

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
            PaymentIntent::constructFrom(['id' => 'pi_correct', 'amount' => 10000, 'currency' => 'gbp', 'status' => 'requires_capture'])
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
     * @return array<string, array{Throwable}>
     */
    public static function capturesWithNoAnswer(): array
    {
        return [
            'connection lost' => [ApiConnectionException::factory('Operation timed out')],
            'server error' => [UnknownApiErrorException::factory('Something went wrong', 500)],
            'same request still in progress' => [IdempotencyException::factory('Another request is in progress', 409)],
            'unreadable response' => [new UnexpectedValueException('Invalid response body')],
        ];
    }

    #[DataProvider('capturesWithNoAnswer')]
    public function test_records_the_payment_when_stripe_confirms_a_capture_it_did_not_answer(Throwable $captureError): void
    {
        $this->paymentIntents->allows('retrieve')->with('pi_1')->andReturn(
            $this->paymentIntent('requires_capture'),
            $this->paymentIntent('succeeded'),
        );
        $this->paymentIntents->expects('capture')->with('pi_1')->once()->andThrow($captureError);

        $order = $this->processOrder();

        $this->assertCount(1, $order->transactions);
        $this->assertSame(TransactionStatus::SETTLED, $order->transactions[0]->status);
        $this->assertSame('pi_1', $order->transactions[0]->gatewayId);
    }

    #[DataProvider('capturesWithNoAnswer')]
    public function test_the_outcome_is_unknown_when_an_unanswered_capture_is_not_confirmed(Throwable $captureError): void
    {
        $this->paymentIntents->allows('retrieve')->with('pi_1')->andReturn($this->paymentIntent('requires_capture'));
        $this->paymentIntents->expects('capture')->with('pi_1')->once()->andThrow($captureError);

        try {
            $this->processOrder();
            $this->fail('The outcome was treated as known');
        } catch (PaymentOutcomeUnknownException $exception) {
            $this->assertSame($captureError, $exception->getPrevious());
        }
    }

    public function test_the_outcome_is_unknown_when_stripe_cannot_be_asked_about_an_unanswered_capture(): void
    {
        $intents = [$this->paymentIntent('requires_capture')];

        $this->paymentIntents->allows('retrieve')->with('pi_1')->andReturnUsing(
            fn () => array_shift($intents) ?? throw ApiConnectionException::factory('Operation timed out')
        );
        $this->paymentIntents->expects('capture')->with('pi_1')->once()->andThrow(
            ApiConnectionException::factory('Operation timed out')
        );

        $this->expectException(PaymentOutcomeUnknownException::class);

        $this->processOrder();
    }

    /**
     * @return array<string, array{ApiErrorException}>
     */
    public static function rejectedCaptures(): array
    {
        return [
            'declined card' => [CardException::factory('Your card was declined', 402)],
            'intent not capturable' => [InvalidRequestException::factory('This PaymentIntent could not be captured', 400)],
            'rate limited' => [RateLimitException::factory('Too many requests', 429)],
        ];
    }

    #[DataProvider('rejectedCaptures')]
    public function test_passes_on_a_rejected_capture_as_a_plain_failure(ApiErrorException $captureError): void
    {
        $this->paymentIntents->expects('retrieve')->with('pi_1')->once()->andReturn($this->paymentIntent('requires_capture'));
        $this->paymentIntents->expects('capture')->with('pi_1')->once()->andThrow($captureError);

        $this->expectExceptionObject($captureError);

        $this->processOrder();
    }

    public function test_the_outcome_is_unknown_when_a_capture_returns_an_unrecognised_status(): void
    {
        $this->paymentIntents->allows('retrieve')->with('pi_1')->andReturn($this->paymentIntent('requires_capture'));
        $this->paymentIntents->expects('capture')->with('pi_1')->once()->andReturn($this->paymentIntent('something_new'));

        $this->expectException(PaymentOutcomeUnknownException::class);

        $this->processOrder();
    }

    public function test_refuses_an_intent_that_has_already_been_captured(): void
    {
        $this->paymentIntents->allows('retrieve')->with('pi_1')->andReturn($this->paymentIntent('succeeded'));
        $this->paymentIntents->expects('capture')->never();

        $this->expectException(PaymentFailedException::class);

        $this->processOrder();
    }

    protected function processOrder(): Order
    {
        return $this->gateway->processOrder(new ProcessOrderRequest(
            order: $this->createOrder(total: 10000, currency: 'GBP'),
            gatewayName: 'stripe',
            gatewayPaymentNonce: 'pi_1',
        ));
    }

    protected function paymentIntent(string $status): PaymentIntent
    {
        return PaymentIntent::constructFrom([
            'id' => 'pi_1',
            'amount' => 10000,
            'currency' => 'gbp',
            'status' => $status,
            'cancellation_reason' => null,
        ]);
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
