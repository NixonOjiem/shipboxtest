<?php

namespace App\Helpers;

use \App\Models\Product;

class StockHelper
{
    // Decrease the stock in DB
    public static function decrease(Product $product, int $quantity): void
    {
        $product->decrement('current_stock', $quantity);
    }

    // Increase product stock
    public static function increase(Product $product, int $quantity): void
    {
        $product->increment('current_stock', $quantity);
    }
}
