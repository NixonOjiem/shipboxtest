<?php

namespace App\Http\Controllers\Product;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use App\Models\User;

class ProductController extends Controller
{
    /**
     * @OA\Post(
     * path="/api/products-post",
     * summary="Create a new product",
     * description="Allows sellers to create their own products and admins to create products for any user. Admins MUST provide a user_id.",
     * operationId="createProduct",
     * tags={"Products"},
     * security={{ "sanctum": {} }},
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"name", "price", "current_stock"},
     * @OA\Property(property="name", type="string", example="Wireless Mouse"),
     * @OA\Property(property="price", type="number", format="float", example=29.99),
     * @OA\Property(property="current_stock", type="integer", example=100),
     * @OA\Property(
     * property="user_id",
     * type="integer",
     * description="Required only if the authenticated user is an admin. The ID of the user who will own the product.",
     * example=5
     * )
     * )
     * ),
     * @OA\Response(
     * response=201,
     * description="Product created successfully",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="Product created successfully"),
     * @OA\Property(
     * property="product",
     * type="object",
     * @OA\Property(property="id", type="integer", example=1),
     * @OA\Property(property="name", type="string", example="Wireless Mouse"),
     * @OA\Property(property="price", type="number", example=29.99),
     * @OA\Property(property="current_stock", type="integer", example=100),
     * @OA\Property(property="user_id", type="integer", example=5),
     * @OA\Property(property="created_at", type="string", format="date-time"),
     * @OA\Property(property="updated_at", type="string", format="date-time")
     * )
     * )
     * ),
     * @OA\Response(
     * response=422,
     * description="Validation error (e.g., missing fields or invalid user_id for admin)"
     * ),
     * @OA\Response(
     * response=401,
     * description="Unauthenticated"
     * ),
     * @OA\Response(
     * response=403,
     * description="Unauthorized - User does not have permission to create products"
     * )
     * )
     */
    //handle product storage (post)
    public function createProduct(Request $request)
    {
        $currentUser = $request->user();

        // check if admin
        $isAdmin = $currentUser->hasRole('admin');

        // base  rules
        $rules = [
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'current_stock' => 'required|integer|min:0',
        ];

        // If an admin, FORCE them to provide a valid user_id
        if ($isAdmin) {
            $rules['user_id'] = 'required|exists:users,id';
        }

        $validated = $request->validate($rules);

        // Determine user model to attach the product to
        if ($isAdmin) {
            // Fetch the selected user
            $targetUser = User::findOrFail($validated['user_id']);

            //prevent Mass Assignment Exceptions. relationship handles it
            unset($validated['user_id']);
        } else {
            // Regular users create products for themselves
            $targetUser = $currentUser;
        }

        // Attach the product to the resolved target user
        $product = $targetUser->products()->create($validated);

        return response()->json([
            'message' => 'Product created successfully',
            'product' => $product
        ], 201);
    }

    /**
     * @OA\Patch(
     * path="/api/products/{product}",
     * summary="Update an existing product",
     * description="Allows owners to update their own products and admins to update any product. Fields are optional (PATCH).",
     * operationId="updateProductDetails",
     * tags={"Products"},
     * security={{ "sanctum": {} }},
     * @OA\Parameter(
     * name="product",
     * in="path",
     * description="ID of the product to update",
     * required=true,
     * @OA\Schema(type="integer")
     * ),
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * @OA\Property(property="name", type="string", example="Updated Gaming Mouse"),
     * @OA\Property(property="price", type="number", format="float", example=35.50),
     * @OA\Property(property="current_stock", type="integer", example=150)
     * )
     * ),
     * @OA\Response(
     * response=200,
     * description="Product updated successfully",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="product updated successfully"),
     * @OA\Property(property="product", type="object",
     * @OA\Property(property="id", type="integer", example=1),
     * @OA\Property(property="name", type="string", example="Updated Gaming Mouse"),
     * @OA\Property(property="price", type="number", example=35.50),
     * @OA\Property(property="current_stock", type="integer", example=150),
     * @OA\Property(property="user_id", type="integer", example=5)
     * )
     * )
     * ),
     * @OA\Response(
     * response=403,
     * description="Unauthorized - You do not own this product and are not an admin"
     * ),
     * @OA\Response(
     * response=404,
     * description="Product not found"
     * ),
     * @OA\Response(
     * response=422,
     * description="Validation error"
     * )
     * )
     */

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

        return response()->json(['message' => 'product updated sucessfully', 'product' => $product], 200);

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


    //to fetch products
    public function fetchProducts(Request $request)
    {
        // get user
        $authUser = auth()->user();

        //check if admin
        if ($authUser->hasRole('admin')) {
            $products = Product::with('user')->get();
            return response()->json(['message' => 'successfull', 'data' => $products]);
        }

        //for sellers
        $products = $authUser->products()->get();
        return response()->json(['message' => 'sucessfull', 'data' => $products]);
    }
}
