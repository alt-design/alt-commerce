<?php

namespace AltDesign\AltCommerce\Tests\Unit\Actions;

use AltDesign\AltCommerce\Actions\CreateOrderAction;
use AltDesign\AltCommerce\Commerce\Basket\LineItem;
use AltDesign\AltCommerce\Commerce\Order\Order;
use AltDesign\AltCommerce\Contracts\Customer;
use AltDesign\AltCommerce\Contracts\OrderFactory;
use AltDesign\AltCommerce\Contracts\OrderRepository;
use AltDesign\AltCommerce\Contracts\ProductRepository;
use AltDesign\AltCommerce\Contracts\StockRepository;
use AltDesign\AltCommerce\Enum\StockPolicy;
use AltDesign\AltCommerce\Tests\Support\CommerceHelper;
use AltDesign\AltCommerce\Tests\Unit\TestCase;
use Mockery;

class CreateOrderActionTest extends TestCase
{
    use CommerceHelper;

    protected $orderRepository;
    protected $orderFactory;
    protected $productRepository;
    protected $stockRepository;
    protected $order;
    protected CreateOrderAction $action;

    protected function setUp(): void
    {
        $this->createBasket();

        $this->orderRepository = Mockery::mock(OrderRepository::class);
        $this->orderFactory = Mockery::mock(OrderFactory::class);
        $this->productRepository = Mockery::mock(ProductRepository::class);
        $this->stockRepository = Mockery::mock(StockRepository::class);

        $this->order = Mockery::mock(Order::class);
        $this->order->id = 'order-1';
        $this->order->orderNumber = 'ORD-1';
        $this->order->lineItems = [
            new LineItem(id: 'l1', productId: 'tracked', productName: 'Tracked', amount: 100, quantity: 2),
            new LineItem(id: 'l2', productId: 'backorder', productName: 'Backorder', amount: 100, quantity: 3),
            new LineItem(id: 'l3', productId: 'untracked', productName: 'Untracked', amount: 100, quantity: 4),
        ];

        $this->orderRepository->allows()->findByBasketId('test-basket')->andReturn(null);
        $this->orderRepository->allows()->reserveOrderNumber()->andReturn('ORD-1');
        $this->orderRepository->allows('save');
        $this->orderFactory->allows('createFromBasket')->andReturn($this->order);

        $this->productRepository->allows()->find('tracked')->andReturn($this->createProduct(id: 'tracked', stockPolicy: StockPolicy::TRACKED));
        $this->productRepository->allows()->find('backorder')->andReturn($this->createProduct(id: 'backorder', stockPolicy: StockPolicy::BACKORDER));
        $this->productRepository->allows()->find('untracked')->andReturn($this->createProduct(id: 'untracked', stockPolicy: StockPolicy::UNTRACKED));

        $this->action = new CreateOrderAction(
            context: $this->basketContext,
            orderRepository: $this->orderRepository,
            orderFactory: $this->orderFactory,
            productRepository: $this->productRepository,
            stockRepository: $this->stockRepository,
        );
    }

    public function test_reduces_stock_for_tracked_and_backorder_lines_only(): void
    {
        $this->stockRepository->allows()->hasMovementsForReference('order-1')->andReturn(false);

        $this->stockRepository->expects()->adjust('tracked', -2, 'order', 'order-1')->once();
        $this->stockRepository->expects()->adjust('backorder', -3, 'order', 'order-1')->once();
        // Untracked line is skipped entirely.
        $this->stockRepository->shouldReceive('adjust')->with('untracked', Mockery::any(), Mockery::any(), Mockery::any())->never();

        $order = $this->action->handle(customer: Mockery::mock(Customer::class));

        $this->assertSame($this->order, $order);
    }

    public function test_stock_reduction_is_idempotent_when_movements_already_exist(): void
    {
        $this->stockRepository->allows()->hasMovementsForReference('order-1')->andReturn(true);

        $this->stockRepository->shouldReceive('adjust')->never();

        $order = $this->action->handle(customer: Mockery::mock(Customer::class));

        $this->assertSame($this->order, $order);
    }
}
