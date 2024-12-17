<?php

namespace AltDesign\AltCommerce\Tests\Unit\Actions;

use AltDesign\AltCommerce\Actions\AttemptPaymentAction;
use AltDesign\AltCommerce\Actions\CreateOrderFromBasketAction;
use AltDesign\AltCommerce\Actions\EmptyBasketAction;
use AltDesign\AltCommerce\Commerce\Basket\Basket;
use AltDesign\AltCommerce\Commerce\Order\Order;
use AltDesign\AltCommerce\Commerce\Order\OrderFactory;
use AltDesign\AltCommerce\Commerce\Payment\PaymentRequest;
use AltDesign\AltCommerce\Commerce\Payment\PaymentRequestFactory;
use AltDesign\AltCommerce\Commerce\Payment\Transaction;
use AltDesign\AltCommerce\Contracts\BasketRepository;
use AltDesign\AltCommerce\Contracts\Customer;
use AltDesign\AltCommerce\Contracts\OrderRepository;
use AltDesign\AltCommerce\Enum\OrderStatus;
use AltDesign\AltCommerce\Enum\TransactionStatus;
use AltDesign\AltCommerce\Exceptions\PaymentFailedException;
use Mockery;
use PHPUnit\Framework\TestCase;

class CreateOrderFromBasketTest extends TestCase
{
    protected $order;
    protected $basket;
    protected $transaction;
    protected $attemptPaymentAction;
    protected $emptyBasketAction;
    protected $basketRepository;
    protected $orderRepository;
    protected $orderFactory;
    protected $paymentRequest;
    protected $paymentRequestFactory;
    protected $customer;
    protected CreateOrderFromBasketAction $action;

    protected function setUp(): void
    {
        parent::setUp();

        $this->order = Mockery::mock(Order::class);
        $this->order->status = OrderStatus::DRAFT;
        $this->basket = Mockery::mock(Basket::class);
        $this->basket->id = 'basket-1';
        $this->transaction = Mockery::mock(Transaction::class);
        $this->transaction->status = TransactionStatus::SETTLED;
        $this->attemptPaymentAction = \Mockery::mock(AttemptPaymentAction::class);
        $this->attemptPaymentAction->allows('handle')->andReturn($this->transaction);
        $this->emptyBasketAction = \Mockery::mock(EmptyBasketAction::class);
        $this->emptyBasketAction->allows('handle')->once();
        $this->basketRepository = \Mockery::mock(BasketRepository::class);
        $this->basketRepository->allows()->get()->andReturn($this->basket);
        $this->orderRepository = \Mockery::mock(OrderRepository::class);
        $this->orderFactory = \Mockery::mock(OrderFactory::class);
        $this->paymentRequest = Mockery::mock(PaymentRequest::class);
        $this->paymentRequestFactory = \Mockery::mock(PaymentRequestFactory::class);
        $this->paymentRequestFactory->allows('createFromOrder')->andReturn($this->paymentRequest);
        $this->customer = \Mockery::mock(Customer::class);

        $this->action = new CreateOrderFromBasketAction(
            attemptPaymentAction: $this->attemptPaymentAction,
            emptyBasketAction: $this->emptyBasketAction,
            basketRepository: $this->basketRepository,
            orderRepository: $this->orderRepository,
            orderFactory: $this->orderFactory,
            paymentRequestFactory: $this->paymentRequestFactory
        );
    }

    public function test_existing_order_is_used(): void
    {
        $this->orderRepository->allows()->findByBasketId('basket-1')->andReturn($this->order);
        $this->orderRepository->allows()->save($this->order)->once();

        $order = $this->action->handle('payment-provider', 'payment-token', $this->customer);

        $this->assertSame($this->order, $order);
    }

    public function test_new_order_is_created(): void
    {
        $this->orderRepository->allows()->findByBasketId('basket-1')->andReturn(null);
        $this->orderRepository->allows()->save($this->order)->once();
        $this->orderFactory->expects()->createFromBasket($this->basket, $this->customer, null, null, [])->once()->andReturn($this->order);
        $order = $this->action->handle('payment-provider', 'payment-token', $this->customer);
        $this->assertSame($this->order, $order);
    }

    public function test_exception_is_thrown_with_non_draft_orders(): void
    {
        $this->expectException(PaymentFailedException::class);

        $this->order->status = OrderStatus::PROCESSING;

        $this->orderRepository->allows('findByBasketId')->andReturn($this->order);
        $this->action->handle('payment-provider', 'payment-token', $this->customer);
    }

    public function test_exception_is_thrown_with_failed_transaction(): void
    {
        $this->expectException(PaymentFailedException::class);

        $this->orderRepository->allows('findByBasketId')->andReturn($this->order);
        $this->orderRepository->allows()->save($this->order)->once();

        $this->transaction->status = TransactionStatus::FAILED;

        $this->action->handle('payment-provider', 'payment-token', $this->customer);
    }

    public function test_order_is_marked_as_processing(): void
    {
        $this->orderRepository->allows('findByBasketId')->andReturn($this->order);
        $this->orderRepository->allows()->save($this->order)->once();

        $this->transaction->status = TransactionStatus::SETTLED;

        $order = $this->action->handle('payment-provider', 'payment-token', $this->customer);

        $this->assertEquals(OrderStatus::PROCESSING, $order->status);
        $this->assertEquals(0, $order->outstanding);

    }
}