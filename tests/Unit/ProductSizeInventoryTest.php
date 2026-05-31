<?php

namespace Tests\Unit;

use App\Support\ProductSizeInventory;
use PHPUnit\Framework\TestCase;

class ProductSizeInventoryTest extends TestCase
{
    public function test_it_uses_medium_as_the_legacy_fallback_stock(): void
    {
        $sizeStock = ProductSizeInventory::parseSizeStock(null, 7);

        $this->assertSame(7, $sizeStock['medium']);
        $this->assertSame(0, $sizeStock['small']);
        $this->assertSame(7, ProductSizeInventory::totalStock($sizeStock));
    }

    public function test_it_keeps_explicit_variant_stock_values(): void
    {
        $sizeStock = ProductSizeInventory::parseSizeStock([
            'small' => 2,
            'medium' => 4,
            'xxl' => 1,
        ], 99);

        $this->assertSame(2, $sizeStock['small']);
        $this->assertSame(4, $sizeStock['medium']);
        $this->assertSame(1, $sizeStock['xxl']);
        $this->assertSame(0, $sizeStock['large']);
        $this->assertSame(7, ProductSizeInventory::totalStock($sizeStock));
    }

    public function test_it_adjusts_stock_for_a_single_size(): void
    {
        $updated = ProductSizeInventory::adjustStockValues([
            'stock' => 5,
            'size_stock' => ProductSizeInventory::encodeSizeStock([
                'small' => 1,
                'medium' => 3,
                'large' => 1,
            ]),
        ], 'medium', -2);

        $this->assertNotNull($updated);
        $this->assertSame(3, $updated['stock']);

        $adjustedSizeStock = ProductSizeInventory::parseSizeStock($updated['size_stock']);
        $this->assertSame(1, $adjustedSizeStock['small']);
        $this->assertSame(1, $adjustedSizeStock['medium']);
        $this->assertSame(1, $adjustedSizeStock['large']);
    }
}