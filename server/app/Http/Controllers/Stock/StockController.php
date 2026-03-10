<?php

namespace App\Http\Controllers\Stock;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Traits\ManagesStock;

class StockController extends Controller
{
    use ManagesStock;

    public function adjust(Request $request, Product $product)
    {
        // Ensure only authorized users can manually adjust stock
        $this->authorize('update products');

        $validated = $request->validate([
            'action' => 'required|in:add,subtract',
            'quantity' => 'required|integer|min:1'
        ]);

        if ($validated['action'] === 'add') {
            $this->increaseStock($product, $validated['quantity']);
        } else {
            // Prevent stock from going below 0
            if ($product->current_stock < $validated['quantity']) {
                return response()->json(['error' => 'Insufficient stock'], 422);
            }
            $this->decreaseStock($product, $validated['quantity']);
        }

        return response()->json([
            'message' => 'Stock adjusted successfully',
            'current_stock' => $product->fresh()->current_stock
        ]);
    }
}
