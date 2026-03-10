<?php

namespace App\Http\Controllers\Product;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    //handle product storage (post)
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'current_stock' => 'required|integer|min:0',
        ]);

        $product = Product::create($validated);
        return response()->json($product, 201);
    }
    // modifying the price
    public function modifyPrice(Request $request, Product $product)
    {
        $validated = $request->validate([
            'price' => 'required|numeric|min:0',
        ]);

        $product->update([
            'price' => $validated['price'],
        ]);

        return response()->json([
            'message' => 'Price updated successfully',
            'product' => $product,
        ], 200);
    }
    // modify the name of the product
    public function modifyName(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $product->update([
            'name' => $validated['name'],
        ]);

        return response()->json([
            'message' => 'Name updated successfully',
            'product' => $product,
        ], 200);
    }
    //delete the product
    public function destroy(Request $request, Product $product)
    {
        // Delete the product from the database
        $product->delete();

        return response()->json([
            'message' => 'Product deleted successfully'
        ], 200);
    }
}
