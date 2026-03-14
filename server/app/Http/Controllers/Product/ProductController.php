<?php

namespace App\Http\Controllers\Product;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use App\Models\User;

class ProductController extends Controller
{
    //handle middleware for the methods
    public function __construct()
    {
        $this->middleware('can:create products')->only('createProduct');
        $this->middleware('can:update products')->only('updateProductDetails');
        $this->middleware('can:delete products')->only('deleteProduct');
        $this->middleware('can:read products')->only('fetchProducts');
    }

    /**
     * @OA\Post(
     * path="/api/products-post",
     * summary="Create a new product",
     * description="Allows sellers to create their own products and admins to create products for any user. Admins MUST provide a seller_id.",
     * operationId="createProduct",
     * tags={"Products"},
     * security={{"bearerAuth":{}}},
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"name", "price", "current_stock"},
     * @OA\Property(property="name", type="string", example="Wireless Mouse", maxLength=255),
     * @OA\Property(property="price", type="number", format="float", example=29.99, minimum=0),
     * @OA\Property(property="current_stock", type="integer", example=100, minimum=0),
     * @OA\Property(
     * property="seller_id",
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
     * @OA\Property(property="price", type="number", format="float", example=29.99),
     * @OA\Property(property="current_stock", type="integer", example=100),
     * @OA\Property(property="seller_id", type="integer", example=5),
     * @OA\Property(property="created_at", type="string", format="date-time"),
     * @OA\Property(property="updated_at", type="string", format="date-time")
     * )
     * )
     * ),
     * @OA\Response(
     * response=401,
     * description="Unauthenticated",
     * @OA\JsonContent(@OA\Property(property="message", type="string", example="Unauthenticated."))
     * ),
     * @OA\Response(
     * response=403,
     * description="Unauthorized - User does not have permission to create products",
     * @OA\JsonContent(@OA\Property(property="message", type="string", example="This action is unauthorized."))
     * ),
     * @OA\Response(
     * response=422,
     * description="Validation error",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="The seller id field is required when user is admin."),
     * @OA\Property(property="errors", type="object")
     * )
     * )
     * )
     */

    //handle product storage (post)
    public function createProduct(Request $request)
    {
        //$currentUser = $request->user();
        $currentUser = auth()->user();

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
            $rules['seller_id'] = 'required|exists:users,id'; //seller_id change
        }

        $validated = $request->validate($rules);

        // Determine user model to attach the product to
        if ($isAdmin) {
            // Fetch the selected user
            $targetSeller = User::findOrFail($validated['seller_id']);

            //prevent Mass Assignment Exceptions. relationship handles it
            unset($validated['seller_id']);
        } else {
            // Regular users create products for themselves
            $targetSeller = $currentUser;
        }

        // Attach the product to the resolved target user
        $product = $targetSeller->products()->create($validated);

        return response()->json([
            'message' => 'Product created successfully',
            'product' => $product
        ], 201);
    }

    /**
     * @OA\Patch(
     * path="/api/products/{product}",
     * summary="Update an existing product",
     * description="Allows sellers to update their own products and admins to update any product. Fields are optional (PATCH).",
     * operationId="updateProductDetails",
     * tags={"Products"},
     * security={{"bearerAuth":{}}},
     * @OA\Parameter(
     * name="product",
     * in="path",
     * description="The ID of the product to update",
     * required=true,
     * @OA\Schema(type="integer", example=1)
     * ),
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * @OA\Property(property="name", type="string", example="Updated Gaming Mouse", maxLength=255),
     * @OA\Property(property="price", type="number", format="float", example=35.50, minimum=0),
     * @OA\Property(property="current_stock", type="integer", example=150, minimum=0)
     * )
     * ),
     * @OA\Response(
     * response=200,
     * description="Product updated successfully",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="product updated sucessfully"),
     * @OA\Property(property="product", type="object",
     * @OA\Property(property="id", type="integer", example=1),
     * @OA\Property(property="name", type="string", example="Updated Gaming Mouse"),
     * @OA\Property(property="price", type="number", format="float", example=35.50),
     * @OA\Property(property="current_stock", type="integer", example=150),
     * @OA\Property(property="seller_id", type="integer", example=5),
     * @OA\Property(property="created_at", type="string", format="date-time"),
     * @OA\Property(property="updated_at", type="string", format="date-time")
     * )
     * )
     * ),
     * @OA\Response(
     * response=403,
     * description="Forbidden - Unauthorized to modify this product",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="Unauthorized. You can only modify your own products.")
     * )
     * ),
     * @OA\Response(
     * response=404,
     * description="Product not found",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="No query results for model [App\\Models\\Product] 1")
     * )
     * ),
     * @OA\Response(
     * response=422,
     * description="Validation error",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="The price must be at least 0."),
     * @OA\Property(property="errors", type="object")
     * )
     * )
     * )
     */
    // update product details
    public function updateProductDetails(Request $request, Product $product)
    {
        //check if user is admin or owns the product
        // update to product policy or use cutom middleware
        if ($product->seller_id !== $request->user()->id && !$request->user()->hasRole('admin')) {
            return response()->json([
                'message' => 'Unauthorized. You can only modify your own products.'
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'price' => 'sometimes|numeric|min:0',
            'current_stock' => 'sometimes|integer|min:0',
        ]);

        $product->update(attributes: $validated);

        return response()->json(data: [
            'message' => 'product updated sucessfully',
            'product' => $product
        ], );

    }


    /**
     * @OA\Delete(
     * path="/api/products/{product}/delete",
     * summary="Delete a product",
     * description="Permanently deletes a product record. Restrictions:
        1. Only the product owner (seller) or an Admin can delete the product.
        2. Products attached to active (undelivered) orders cannot be deleted.",
     * operationId="deleteProduct",
     * tags={"Products"},
     * security={{"bearerAuth":{}}},
     * @OA\Parameter(
     * name="product",
     * in="path",
     * description="The ID of the product to delete",
     * required=true,
     * @OA\Schema(type="integer", example=1)
     * ),
     * @OA\Response(
     * response=200,
     * description="Product deleted successfully",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="Product deleted successfully")
     * )
     * ),
     * @OA\Response(
     * response=401,
     * description="Unauthenticated",
     * @OA\JsonContent(@OA\Property(property="message", type="string", example="Unauthenticated."))
     * ),
     * @OA\Response(
     * response=403,
     * description="Forbidden - Unauthorized to delete this product",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="Unauthorized. You can only delete your own products.")
     * )
     * ),
     * @OA\Response(
     * response=404,
     * description="Product not found",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="No query results for model [App\\Models\\Product] 1")
     * )
     * ),
     * @OA\Response(
     * response=422,
     * description="Unprocessable Content - Product has active orders",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="Cannot delete product. It is attached to active orders that have not been delivered yet.")
     * )
     * )
     * )
     */

    //delete the product
    public function deleteProduct(Request $request, Product $product)
    {
        // check if user owns the product or is admin
        // update to product policy or use cutom middleware

        if ($product->seller_id !== auth()->id() && !auth()->user()->hasRole('admin')) {
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

    /**
     * @OA\Get(
     * path="/api/products",
     * summary="Fetch a list of products",
     * description="Returns a list of products. Admins can view all products with seller details, while sellers see only their own products.",
     * tags={"Products"},
     * security={{"bearerAuth":{}}},
     * @OA\Response(
     * response=200,
     * description="List of products retrieved successfully",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="successfull"),
     * @OA\Property(
     * property="data",
     * type="array",
     * @OA\Items(
     * @OA\Property(property="id", type="integer", example=1),
     * @OA\Property(property="name", type="string", example="Wireless Mouse"),
     * @OA\Property(property="price", type="number", format="float", example=29.99),
     * @OA\Property(property="current_stock", type="integer", example=100),
     * @OA\Property(property="seller_id", type="integer", example=5),
     * @OA\Property(
     * property="seller",
     * type="object",
     * description="Only returned for Admins",
     * nullable=true,
     * @OA\Property(property="id", type="integer", example=5),
     * @OA\Property(property="name", type="string", example="John Doe")
     * ),
     * @OA\Property(property="created_at", type="string", format="date-time"),
     * @OA\Property(property="updated_at", type="string", format="date-time")
     * )
     * )
     * )
     * ),
     * @OA\Response(
     * response=401,
     * description="Unauthenticated",
     * @OA\JsonContent(@OA\Property(property="message", type="string", example="Unauthenticated."))
     * )
     * )
     */

    //to fetch products
    public function fetchProducts(Request $request)
    {
        // get user
        $authUser = auth()->user();

        //check if admin
        // update to product policy or use cutom middleware
        if ($authUser->hasRole('admin')) {
            $products = Product::with('seller')->get(); //pargination 15
            return response()->json(['message' => 'successfull', 'data' => $products]);
        }

        //for sellers
        $products = $authUser->products()->latest()->get();
        return response()->json(['message' => 'sucessfull', 'data' => $products]);
    }
}
