<?php

namespace AltDesign\AltCommerce\Tests\Unit\Actions;

use AltDesign\AltCommerce\Actions\EmptyBasketAction;
use AltDesign\AltCommerce\Tests\Support\CommerceHelper;
use AltDesign\AltCommerce\Tests\Unit\TestCase;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;

class EmptyBasketActionTest extends TestCase
{
    use CommerceHelper;

    public function setup(): void
    {
        $this->createBasket();
        $this->action = new EmptyBasketAction($this->basketContext);
    }

    #[DoesNotPerformAssertions]
    public function test_clears_basket()
    {
        $this->basketContext->expects()->clear()->once();
        $this->action->handle();
    }

}
