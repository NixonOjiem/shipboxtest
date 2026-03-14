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
    /**
     * @OA\Patch(
     * path="/api/products/{product}/adjust",
     * summary="Adjust product stock level",
     * description="Increases or decreases the stock of a product. Sellers can adjust their own products, while Admins can adjust any product. Prevents stock from going below zero.",
     * tags={"Products"},
     * security={{"bearerAuth":{}}},
     * @OA\Parameter(
     * name="product",
     * in="path",
     * description="The ID of the product",
     * required=true,
     * @OA\Schema(type="integer", example=1)
     * ),
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"action", "quantity"},
     * @OA\Property(property="action", type="string", enum={"add", "subtract"}, example="add", description="Direction of stock adjustment"),
     * @OA\Property(property="quantity", type="integer", example=10, minimum=1, description="The amount to add or subtract")
     * )
     * ),
     * @OA\Response(
     * response=200,
     * description="Stock adjusted successfully",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="Stock adjusted successfully"),
     * @OA\Property(property="current_stock", type="integer", example=110)
     * )
     * ),
     * @OA\Response(
     * response=403,
     * description="Forbidden - Unauthorized to adjust this product",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="you are not authorized to ajust this products stock")
     * )
     * ),
     * @OA\Response(
     * response=422,
     * description="Unprocessable Content - Insufficient stock or validation error",
     * @OA\JsonContent(
     * @OA\Property(property="error", type="string", example="Insufficient stock")
     * )
     * ),
     * @OA\Response(
     * response=404,
     * description="Product not found",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="No query results for model [App\\Models\\Product] 1")
     * )
     * )
     * )
     */
    public function adjust(Request $request, Product $product)
    {
        $authUser = auth()->user();

        if (!$authUser->hasRole('admin') && $authUser->id !== $product->seller_id) {
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
