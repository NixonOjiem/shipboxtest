<?php
namespace App\Traits;

use App\Models\Product;
use Illuminate\Support\Facades\DB;

trait ManagesStock
{
    // decrease the stock in DB
    public function decreaseStock(Product $product, int $quantity)
    {
        $product->decrement('current_stock', $quantity);
    }

    //increase product
    public function increaseStock(Product $product, int $quantity)
    {
        $product->increment('current_stock', $quantity);
    }
}
