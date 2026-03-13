<?php

namespace App\Http\Controllers\Stock;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\Product;
// use App\Traits\ManagesStock;
use App\Helpers\StockHelper;

class StockController extends Controller
{
    // use ManagesStock;

    public function adjust(Request $request, Product $product)
    {
        $authUser = auth()->user();

        if (!$authUser->hasRole('admin') && $authUser->id !== $product->user_id) {
            return response()->json(['message' => 'you are not authorized to ajust this products stock'], 403);
        }
        $validated = $request->validate([
            'action' => 'required|in:add,subtract',
            'quantity' => 'required|integer|min:1'
        ]);

        if ($validated['action'] === 'add') {
            StockHelper::increase($product, $validated['quantity']);
        } else {
            // Prevent stock from going below 0
            if ($product->current_stock < $validated['quantity']) {
                return response()->json(['error' => 'Insufficient stock'], 422);
            }
            StockHelper::decrease($product, $validated['quantity']);
        }

        return response()->json([
            'message' => 'Stock adjusted successfully',
            'current_stock' => $product->fresh()->current_stock
        ]);
    }
}
