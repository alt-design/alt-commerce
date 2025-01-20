<?php

namespace AltDesign\AltCommerce\Tests\Unit;

use Mockery;

class TestCase extends \PHPUnit\Framework\TestCase
{
    public function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
    }
}