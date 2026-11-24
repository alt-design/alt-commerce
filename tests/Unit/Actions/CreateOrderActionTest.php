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
use AltDesign\AltCommerce\Enum\OrderStatus;
use AltDesign\AltCommerce\Enum\StockPolicy;
use AltDesign\AltCommerce\Enum\TransactionStatus;
use AltDesign\AltCommerce\Exceptions\OrderAlreadyPlacedException;
use AltDesign\AltCommerce\Tests\Support\CommerceHelper;
use AltDesign\AltCommerce\Tests\Unit\TestCase;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;

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

        $this->orderRepository->allows()->findByBasketId('test-basket')->andReturn(null)->byDefault();
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

    /**
     * @return array<string, array{OrderStatus, array<int, TransactionStatus>}>
     */
    public static function placedOrPaidOrders(): array
    {
        return [
            'draft with a settled payment' => [OrderStatus::DRAFT, [TransactionStatus::SETTLED]],
            'draft with a payment in flight' => [OrderStatus::DRAFT, [TransactionStatus::FAILED, TransactionStatus::PENDING]],
            'processing' => [OrderStatus::PROCESSING, []],
            'pending' => [OrderStatus::PENDING, []],
        ];
    }

    /**
     * @param array<int, TransactionStatus> $transactionStatuses
     */
    #[DataProvider('placedOrPaidOrders')]
    public function test_refuses_to_rebuild_an_order_that_has_been_placed_or_paid(OrderStatus $status, array $transactionStatuses): void
    {
        $this->orderRepository->allows()->findByBasketId('test-basket')->andReturn($this->makeOrder($status, $transactionStatuses));

        try {
            $this->action->handle(customer: Mockery::mock(Customer::class));
            $this->fail('Expected the order to be refused');
        } catch (OrderAlreadyPlacedException $exception) {
            $this->assertStringContainsString('ORD-1', $exception->getMessage());
        }

        $this->orderFactory->shouldNotHaveReceived('createFromBasket');
        $this->orderRepository->shouldNotHaveReceived('save');
        $this->stockRepository->shouldNotHaveReceived('adjust');
    }

    public function test_rebuilds_a_draft_whose_payment_attempts_all_failed(): void
    {
        $this->orderRepository->allows()->findByBasketId('test-basket')->andReturn($this->makeOrder(OrderStatus::DRAFT, [TransactionStatus::FAILED]));
        $this->stockRepository->allows()->hasMovementsForReference('order-1')->andReturn(true);

        $order = $this->action->handle(customer: Mockery::mock(Customer::class));

        $this->assertSame($this->order, $order);
        $this->orderRepository->shouldHaveReceived('save')->once();
    }

    public function test_stock_reduction_is_idempotent_when_movements_already_exist(): void
    {
        $this->stockRepository->allows()->hasMovementsForReference('order-1')->andReturn(true);

        $this->stockRepository->shouldReceive('adjust')->never();

        $order = $this->action->handle(customer: Mockery::mock(Customer::class));

        $this->assertSame($this->order, $order);
    }
}
