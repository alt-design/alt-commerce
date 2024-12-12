<?php

namespace AltDesign\AltCommerce\Tests\Unit\Actions;

use AltDesign\AltCommerce\Actions\EmptyBasketAction;
use AltDesign\AltCommerce\Actions\RecalculateBasketAction;
use AltDesign\AltCommerce\Commerce\Basket\Basket;
use AltDesign\AltCommerce\Commerce\Basket\LineItem;
use AltDesign\AltCommerce\Contracts\BasketRepository;
use Mockery;
use PHPUnit\Framework\TestCase;

class EmptyBasketActionTest extends TestCase
{
    public function test_clears_basket_and_recalculates_total()
    {

        $basketMock = Mockery::mock(Basket::class);
        $basketMock->lineItems = [
            Mockery::mock(LineItem::class),
            Mockery::mock(LineItem::class),
            Mockery::mock(LineItem::class),
        ];

        $basketRepositoryMock = Mockery::mock(BasketRepository::class);
        $basketRepositoryMock->allows()->get()->andReturn($basketMock);
        $basketRepositoryMock->allows()->save($basketMock);

        $recalculateBasketActionMock = Mockery::mock(RecalculateBasketAction::class);
        $recalculateBasketActionMock->allows('handle')->once();


        $action = new EmptyBasketAction($basketRepositoryMock, $recalculateBasketActionMock);
        $action->handle();

        $this->assertCount(0, $basketMock->lineItems);
    }
}