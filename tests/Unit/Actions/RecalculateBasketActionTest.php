<?php

namespace AltDesign\AltCommerce\Tests\Unit\Actions;

use AltDesign\AltCommerce\Actions\RecalculateBasketAction;
use AltDesign\AltCommerce\Commerce\Pipeline\RecalculateBasketPipeline;
use AltDesign\AltCommerce\Contracts\BasketRepository;
use AltDesign\AltCommerce\Tests\Support\CommerceHelper;
use AltDesign\AltCommerce\Tests\Unit\TestCase;
use Mockery;

class RecalculateBasketActionTest extends TestCase
{

    use CommerceHelper;

    public function test_triggers_pipeline(): void
    {
        $this->createBasket();

        $pipeline = Mockery::mock(RecalculateBasketPipeline::class);
        $pipeline->expects('handle')->once();

        $this->basketRepository->allows()->save($this->basket);

        $action = new RecalculateBasketAction(
            basketRepository: $this->basketRepository,
            recalculateBasketPipeline: $pipeline
        );

        $action->handle();

        $this->assertTrue(true);
    }



}