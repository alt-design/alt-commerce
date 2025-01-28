<?php

namespace AltDesign\AltCommerce\Tests\Unit\Actions;

use AltDesign\AltCommerce\Actions\EmptyBasketAction;
use AltDesign\AltCommerce\Actions\RecalculateBasketAction;
use AltDesign\AltCommerce\Tests\Support\CommerceHelper;
use Mockery;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\TestCase;

class EmptyBasketActionTest extends TestCase
{
    use CommerceHelper;

    protected $recalculateBasketAction;

    public function setup(): void
    {
        $this->createBasket();

        $this->recalculateBasketAction = Mockery::mock(RecalculateBasketAction::class);
        $this->action = new EmptyBasketAction($this->basketRepository, $this->recalculateBasketAction);
    }

    #[DoesNotPerformAssertions]
    public function test_clears_basket_and_recalculates_total()
    {
        $this->basketRepository->expects()->delete()->once();
        $this->recalculateBasketAction->expects()->handle()->once();
        $this->action->handle();
    }

}