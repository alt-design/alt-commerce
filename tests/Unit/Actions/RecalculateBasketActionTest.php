<?php

namespace AltDesign\AltCommerce\Tests\Unit\Actions;

use AltDesign\AltCommerce\Actions\RecalculateBasketAction;
use AltDesign\AltCommerce\Commerce\Pipeline\RecalculateBasket\RecalculateBasketPipeline;
use Mockery;
use AltDesign\AltCommerce\Tests\Unit\TestCase;

class RecalculateBasketActionTest extends TestCase
{

    public function test_triggers_pipeline(): void
    {
        $pipeline = Mockery::mock(RecalculateBasketPipeline::class);
        $pipeline->expects()->handle()->once();
        $action = new RecalculateBasketAction(
            recalculateBasketPipeline: $pipeline
        );

        $action->handle();

        $this->assertTrue(true);
    }



}