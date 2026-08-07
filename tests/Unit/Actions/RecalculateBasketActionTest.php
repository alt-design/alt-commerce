<?php

namespace AltDesign\AltCommerce\Tests\Unit\Actions;

use AltDesign\AltCommerce\Actions\RecalculateBasketAction;
use AltDesign\AltCommerce\Commerce\Pipeline\RecalculateBasketPipeline;
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

        $this->basketContext->expects()->save($this->basket)->once();

        $action = new RecalculateBasketAction(
            context: $this->basketContext,
            recalculateBasketPipeline: $pipeline
        );

        $action->handle();

        $this->assertTrue(true);
    }

}
