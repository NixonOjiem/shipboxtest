<?php

namespace App\Http\Controllers\Product;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    //handle product storage (post)
    public function createProduct(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'current_stock' => 'required|integer|min:0',
        ]);

        //attach the users ID (relationship already in user model)
        $product = $request->user()->products()->create($validated);

        return response()->json(['message' => 'product created successfully', 'product' => $product], 201);
    }


    // update product details
    public function updateProductDetails(Request $request, Product $product)
    {
        //check if user is admin or owns the product
        if ($product->user_id !== $request->user()->id && !$request->user()->hasRole('admin')) {
            return response()->json([
                'message' => 'Unauthorized. You can only modify your own products.'
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'price' => 'sometimes|numeric|min:0',
            'current_stock' => 'sometimes|integer|min:0',
        ]);

        $product->update($validated);

        response()->json(['message' => 'product updated sucessfully', 'product' => $product], 200);

    }

    //delete the product
    public function deleteProduct(Request $request, Product $product)
    {
        // check if user owns the product or is admin
        if ($product->user_id !== $request->user()->id && !$request->user()->hasRole('admin')) {
            return response()->json([
                'message' => 'Unauthorized. You can only delete your own products.'
            ], 403);
        }

        // prevent deletion of un delivered products
        $hasUndeliveredOrders = $product->orders()->where('status', '!=', 'delivered')->exists();

        if ($hasUndeliveredOrders) {
            return response()->json([
                'message' => 'Cannot delete product. It is attached to active orders that have not been delivered yet.'
            ], 422);
        }

        // delete product
        $product->delete();

        return response()->json([
            'message' => 'Product deleted successfully'
        ], 200);
    }
}
