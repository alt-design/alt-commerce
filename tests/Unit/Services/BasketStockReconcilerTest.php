<?php

namespace AltDesign\AltCommerce\Tests\Unit\Services;

use AltDesign\AltCommerce\Commerce\Basket\BasketManager;
use AltDesign\AltCommerce\Commerce\Basket\LineItem;
use AltDesign\AltCommerce\Contracts\ProductRepository;
use AltDesign\AltCommerce\Services\BasketStockReconciler;
use AltDesign\AltCommerce\Services\StockService;
use AltDesign\AltCommerce\Tests\Support\CommerceHelper;
use AltDesign\AltCommerce\Tests\Unit\TestCase;
use Mockery;

class BasketStockReconcilerTest extends TestCase
{
    use CommerceHelper;

    protected $basketManager;
    protected $products;
    protected $stock;
    protected BasketStockReconciler $reconciler;

    protected function setUp(): void
    {
        $this->createBasket();

        $this->basketManager = Mockery::mock(BasketManager::class);
        $this->basketManager->allows()->context('default')->andReturn($this->basketContext);

        $this->products = Mockery::mock(ProductRepository::class);
        $this->stock = Mockery::mock(StockService::class);

        $this->reconciler = new BasketStockReconciler($this->basketManager, $this->products, $this->stock);
    }

    private function lineItem(string $id, string $productId, string $name, int $quantity): LineItem
    {
        $item = new LineItem(id: $id, productId: $productId, productName: $name, amount: 100, quantity: $quantity);
        $this->basket->lineItems[] = $item;

        return $item;
    }

    public function test_reconciles_lines_against_stock(): void
    {
        $within = $this->lineItem('line-within', 'within', 'Within Stock', 2);
        $over = $this->lineItem('line-over', 'over', 'Over Stock', 5);
        $zero = $this->lineItem('line-zero', 'zero', 'Out Of Stock', 1);
        $this->lineItem('line-missing', 'missing', 'Gone', 3);

        $withinProduct = $this->createProduct(id: 'within');
        $overProduct = $this->createProduct(id: 'over');
        $zeroProduct = $this->createProduct(id: 'zero');

        $this->products->allows()->find('within')->andReturn($withinProduct);
        $this->products->allows()->find('over')->andReturn($overProduct);
        $this->products->allows()->find('zero')->andReturn($zeroProduct);
        $this->products->allows()->find('missing')->andReturn(null);

        $this->stock->allows()->clamp($withinProduct, 2)->andReturn(2);
        $this->stock->allows()->clamp($overProduct, 5)->andReturn(3);
        $this->stock->allows()->clamp($zeroProduct, 1)->andReturn(0);

        // Over-quantity line is reduced, zero-availability line is removed.
        $this->basketContext->expects()->updateBasketQuantity('over', 3)->once();
        $this->basketContext->expects()->removeFromBasket($zero->id)->once();

        $changes = $this->reconciler->reconcile();

        $this->assertEquals([
            ['product_id' => 'over', 'name' => 'Over Stock', 'from' => 5, 'to' => 3, 'removed' => false],
            ['product_id' => 'zero', 'name' => 'Out Of Stock', 'from' => 1, 'to' => 0, 'removed' => true],
        ], $changes);

        // Within-stock and unfound lines are left in place, untouched.
        $this->assertSame($within, $this->basket->lineItems[0]);
    }
}
