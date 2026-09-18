<?php

namespace Tests\Unit;

use App\Enums\LocationType;
use PHPUnit\Framework\TestCase;

class LocationTypeTest extends TestCase
{
    public function test_only_boutique_stock_is_sellable(): void
    {
        $this->assertTrue(LocationType::Boutique->isSellable());
        $this->assertFalse(LocationType::Depot->isSellable());
    }
}
