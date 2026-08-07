<?php

namespace AltDesign\AltCommerce\Tests\Unit\Services;

use AltDesign\AltCommerce\Contracts\ProductRepository;
use AltDesign\AltCommerce\Contracts\StockRepository;
use AltDesign\AltCommerce\Enum\StockPolicy;
use AltDesign\AltCommerce\Services\StockService;
use AltDesign\AltCommerce\Tests\Support\CommerceHelper;
use AltDesign\AltCommerce\Tests\Unit\TestCase;
use Mockery;

class StockServiceTest extends TestCase
{
    use CommerceHelper;

    protected $stockRepository;
    protected $productRepository;
    protected StockService $service;

    protected function setUp(): void
    {
        $this->stockRepository = Mockery::mock(StockRepository::class);
        $this->productRepository = Mockery::mock(ProductRepository::class);
        $this->service = new StockService($this->stockRepository, $this->productRepository);
    }

    public function test_level_is_null_for_untracked(): void
    {
        $product = $this->createProduct(id: 'p', stockPolicy: StockPolicy::UNTRACKED);
        $this->assertNull($this->service->level($product));
    }

    public function test_level_reads_available_for_tracked_and_backorder(): void
    {
        $tracked = $this->createProduct(id: 'tracked', stockPolicy: StockPolicy::TRACKED);
        $backorder = $this->createProduct(id: 'backorder', stockPolicy: StockPolicy::BACKORDER);

        $this->stockRepository->allows()->available('tracked')->andReturn(5);
        $this->stockRepository->allows()->available('backorder')->andReturn(-3);

        $this->assertEquals(5, $this->service->level($tracked));
        $this->assertEquals(-3, $this->service->level($backorder));
    }

    public function test_level_defaults_to_zero_when_available_is_null(): void
    {
        $product = $this->createProduct(id: 'p', stockPolicy: StockPolicy::TRACKED);
        $this->stockRepository->allows()->available('p')->andReturn(null);

        $this->assertEquals(0, $this->service->level($product));
    }

    public function test_purchasable_quantity_is_null_for_untracked_and_backorder(): void
    {
        $untracked = $this->createProduct(id: 'u', stockPolicy: StockPolicy::UNTRACKED);
        $backorder = $this->createProduct(id: 'b', stockPolicy: StockPolicy::BACKORDER);

        $this->assertNull($this->service->purchasableQuantity($untracked));
        $this->assertNull($this->service->purchasableQuantity($backorder));
    }

    public function test_purchasable_quantity_is_clamped_level_for_tracked(): void
    {
        $product = $this->createProduct(id: 'p', stockPolicy: StockPolicy::TRACKED);
        $this->stockRepository->allows()->available('p')->andReturn(-2);

        $this->assertEquals(0, $this->service->purchasableQuantity($product));

        $inStock = $this->createProduct(id: 'q', stockPolicy: StockPolicy::TRACKED);
        $this->stockRepository->allows()->available('q')->andReturn(7);

        $this->assertEquals(7, $this->service->purchasableQuantity($inStock));
    }

    public function test_purchasable_quantity_for_id_resolves_product(): void
    {
        $product = $this->createProduct(id: 'p', stockPolicy: StockPolicy::TRACKED);
        $this->productRepository->allows()->find('p')->andReturn($product);
        $this->stockRepository->allows()->available('p')->andReturn(4);

        $this->assertEquals(4, $this->service->purchasableQuantityForId('p'));
    }

    public function test_purchasable_quantity_for_id_is_null_when_product_missing(): void
    {
        $this->productRepository->allows()->find('missing')->andReturn(null);

        $this->assertNull($this->service->purchasableQuantityForId('missing'));
    }

    public function test_is_purchasable(): void
    {
        $untracked = $this->createProduct(id: 'u', stockPolicy: StockPolicy::UNTRACKED);
        $this->assertTrue($this->service->isPurchasable($untracked, 999));

        $product = $this->createProduct(id: 'p', stockPolicy: StockPolicy::TRACKED);
        $this->stockRepository->allows()->available('p')->andReturn(3);

        $this->assertTrue($this->service->isPurchasable($product, 3));
        $this->assertFalse($this->service->isPurchasable($product, 4));
    }

    public function test_clamp(): void
    {
        $untracked = $this->createProduct(id: 'u', stockPolicy: StockPolicy::UNTRACKED);
        $this->assertEquals(50, $this->service->clamp($untracked, 50));

        $product = $this->createProduct(id: 'p', stockPolicy: StockPolicy::TRACKED);
        $this->stockRepository->allows()->available('p')->andReturn(2);

        $this->assertEquals(2, $this->service->clamp($product, 5));
        $this->assertEquals(1, $this->service->clamp($product, 1));
    }

    public function test_is_on_backorder(): void
    {
        $backorderEmpty = $this->createProduct(id: 'b0', stockPolicy: StockPolicy::BACKORDER);
        $this->stockRepository->allows()->available('b0')->andReturn(0);
        $this->assertTrue($this->service->isOnBackorder($backorderEmpty));

        $backorderStocked = $this->createProduct(id: 'b1', stockPolicy: StockPolicy::BACKORDER);
        $this->stockRepository->allows()->available('b1')->andReturn(5);
        $this->assertFalse($this->service->isOnBackorder($backorderStocked));

        $tracked = $this->createProduct(id: 't', stockPolicy: StockPolicy::TRACKED);
        $this->stockRepository->allows()->available('t')->andReturn(0);
        $this->assertFalse($this->service->isOnBackorder($tracked));
    }
}
