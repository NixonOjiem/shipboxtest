<?php

namespace App\Http\Controllers\Order;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Gate;

class OrderController extends Controller
{

    /**
     * @OA\Post(
     * path="/api/orders",
     * summary="Create a new order",
     * description="Creates an order for the authenticated user or for a specific user if the requester is an admin.",
     * tags={"Orders"},
     * security={{"bearerAuth":{}}},
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"customer_phone", "customer_address", "products"},
     * @OA\Property(property="customer_phone", type="string", example="0123456789", description="Max 20 characters"),
     * @OA\Property(property="customer_address", type="string", example="123 Main St, City"),
     * @OA\Property(property="note", type="string", nullable=true, example="Please deliver after 5 PM"),
     * @OA\Property(property="user_id", type="integer", example=5, description="Required only if requester is an admin. Specifies the owner of the order."),
     * @OA\Property(
     * property="products",
     * type="array",
     * minItems=1,
     * @OA\Items(
     * @OA\Property(property="id", type="integer", example=101),
     * @OA\Property(property="quantity", type="integer", example=2, minimum=1)
     * )
     * )
     * )
     * ),
     * @OA\Response(
     * response=201,
     * description="Order created successfully",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="Order created successfully"),
     * @OA\Property(property="order", type="object",
     * @OA\Property(property="order_id", type="string", example="ORD-ABC12345"),
     * @OA\Property(property="total_price", type="number", format="float", example=150.50),
     * @OA\Property(property="status", type="string", example="to prepare"),
     * @OA\Property(property="products", type="array", @OA\Items(type="object"))
     * )
     * )
     * ),
     * @OA\Response(
     * response=403,
     * description="Forbidden - User attempting to add products they do not own",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="Forbidden. You can only create orders for products you own.")
     * )
     * ),
     * @OA\Response(
     * response=422,
     * description="Validation Error",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="The customer phone field is required."),
     * @OA\Property(property="errors", type="object")
     * )
     * ),
     * @OA\Response(
     * response=500,
     * description="Internal Server Error",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="Failed to create order"),
     * @OA\Property(property="error", type="string")
     * )
     * )
     * )
     */

    // Handle order creation

    public function createOrder(Request $request)
    {
        $currentUser = $request->user();
        $isAdmin = $currentUser->hasRole('admin');

        //base validation rules
        $rules = [
            'customer_phone' => 'required|string|max:20',
            'customer_address' => 'required|string',
            'note' => 'nullable|string',
            'products' => 'required|array|min:1',
            'products.*.id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1',
        ];
        // if they are admin
        if ($isAdmin) {
            $rules['user_id'] = 'required|exists:users,id';
        }

        $validated = $request->validate($rules);
        $targetUserId = $isAdmin ? $validated['user_id'] : $currentUser->id;

        DB::beginTransaction();

        try {

            $totalPrice = 0;
            $totalQuantity = 0;
            $productAttachments = [];

            //  Optimization: Fetch all products in one query to improve performance
            $productIds = collect($validated['products'])->pluck('id')->unique();

            //get products that belong to the target seller
            $products = Product::whereIn('id', $productIds)
                ->where('user_id', $targetUserId)
                ->get()
                ->keyBy('id');

            // If the counts don't match seller added products they do not own.
            if ($products->count() !== $productIds->count()) {
                return response()->json([
                    'message' => 'Forbidden. You can only create orders for products you own.'
                ], 403);
            }

            // Loop through the input data
            foreach ($validated['products'] as $productData) {
                $product = $products->get($productData['id']);

                if ($product) {
                    $quantity = $productData['quantity'];

                    // Calculate cumulative totals
                    // Ensure price is treated as a numeric value
                    $totalPrice += ($product->price * $quantity);
                    $totalQuantity += $quantity;

                    // Prepare data for the pivot table
                    $productAttachments[$product->id] = ['quantity' => $quantity];
                }
            }

            // 4. Create the Order
            $order = Order::create([
                'order_id' => 'ORD-' . strtoupper(Str::random(8)),
                'user_id' => $targetUserId,
                'customer_phone' => $validated['customer_phone'],
                'customer_address' => $validated['customer_address'],
                'status' => 'to prepare',
                'note' => $validated['note'] ?? null,
                'total_price' => $totalPrice,
                'quantity' => $totalQuantity,
            ]);

            // 5. Attach products
            $order->products()->attach($productAttachments);
            DB::commit();

            return response()->json([
                'message' => 'Order created successfully',
                'order' => $order->load('products') // Load relationship to return in response
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in order creation', [
                'exception' => $e,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'message' => 'Failed to create order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     * path="/api/orders",
     * summary="Fetch a list of orders",
     * description="Returns a paginated list of orders. Admins can view all orders, while regular users see only their own.",
     * tags={"Orders"},
     * security={{"bearerAuth":{}}},
     * @OA\Parameter(
     * name="page",
     * in="query",
     * description="The page number for pagination",
     * required=false,
     * @OA\Schema(type="integer", example=1)
     * ),
     * @OA\Response(
     * response=200,
     * description="A paginated list of orders",
     * @OA\JsonContent(
     * @OA\Property(property="current_page", type="integer", example=1),
     * @OA\Property(property="data", type="array",
     * @OA\Items(
     * @OA\Property(property="id", type="integer", example=1),
     * @OA\Property(property="order_id", type="string", example="ORD-XYZ123"),
     * @OA\Property(property="user_id", type="integer", example=5),
     * @OA\Property(property="customer_phone", type="string", example="0123456789"),
     * @OA\Property(property="total_price", type="number", format="float", example=250.00),
     * @OA\Property(property="status", type="string", example="to prepare"),
     * @OA\Property(property="seller", type="object", description="The user who owns the order"),
     * @OA\Property(property="products", type="array", @OA\Items(type="object"), description="List of products in the order"),
     * @OA\Property(property="created_at", type="string", format="date-time"),
     * @OA\Property(property="updated_at", type="string", format="date-time")
     * )
     * ),
     * @OA\Property(property="first_page_url", type="string", example="http://api.test/orders?page=1"),
     * @OA\Property(property="last_page", type="integer", example=10),
     * @OA\Property(property="last_page_url", type="string", example="http://api.test/orders?page=10"),
     * @OA\Property(property="next_page_url", type="string", nullable=true, example="http://api.test/orders?page=2"),
     * @OA\Property(property="path", type="string", example="http://api.test/orders"),
     * @OA\Property(property="per_page", type="integer", example=15),
     * @OA\Property(property="prev_page_url", type="string", nullable=true, example=null),
     * @OA\Property(property="to", type="integer", example=15),
     * @OA\Property(property="total", type="integer", example=150)
     * )
     * ),
     * @OA\Response(
     * response=401,
     * description="Unauthenticated",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="Unauthenticated.")
     * )
     * )
     * )
     */

    //Fetch orders both admin and sellers
    public function fetchOrders(Request $request)
    {
        $query = Order::with(['products', 'seller'])->latest();

        if (!auth()->user()->hasRole('admin')) {
            $query->where('user_id', auth()->id());
        }
        $orders = $query->paginate(15);

        return response()->json($orders);
    }

    /**
     * @OA\Put(
     * path="/api/orders/{id}",
     * summary="Update an existing order",
     * description="Allows a seller to update their own order or an Admin to update any order. Triggers OrderObserver on status change.",
     * tags={"Orders"},
     * security={{"bearerAuth":{}}},
     * @OA\Parameter(
     * name="id",
     * in="path",
     * description="ID of the order to update",
     * required=true,
     * @OA\Schema(type="integer", example=123)
     * ),
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * @OA\Property(
     * property="status",
     * type="string",
     * enum={"onhold", "returned", "delivered", "refunded", "outofstock", "cancelled", "shipped", "to prepare"},
     * example="shipped"
     * ),
     * @OA\Property(property="note", type="string", nullable=true, example="Customer requested delivery to back door.")
     * )
     * ),
     * @OA\Response(
     * response=200,
     * description="Order updated successfully",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="Order updated successfully"),
     * @OA\Property(property="order", type="object",
     * @OA\Property(property="id", type="integer", example=123),
     * @OA\Property(property="status", type="string", example="shipped"),
     * @OA\Property(property="products", type="array", @OA\Items(type="object"))
     * )
     * )
     * ),
     * @OA\Response(
     * response=403,
     * description="Forbidden - Unauthorized to update this order",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="Unauthorized to update this order.")
     * )
     * ),
     * @OA\Response(
     * response=404,
     * description="Order not found",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="No query results for model [App\\Models\\Order] 123")
     * )
     * ),
     * @OA\Response(
     * response=422,
     * description="Validation Error",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="The selected status is invalid."),
     * @OA\Property(property="errors", type="object")
     * )
     * )
     * )
     */


    // Update order (Handles status changes that trigger the Observer)
    public function updateOrder(Request $request, Order $order)
    {
        //sellers update their own orders and admin updates all
        if (auth()->id() !== $order->user_id && !auth()->user()->hasRole('Admin')) {
            return response()->json(['message' => 'Unauthorized to update this order.'], 403);
        }

        // exact statuses allowed by requirements
        $validated = $request->validate([
            'status' => 'sometimes|required|in:onhold,returned,delivered,refunded,outofstock,cancelled,shipped,to prepare',
            'note' => 'nullable|string',
        ]);

        // if 'status' is changed OrderObserver is triggered
        $order->update($validated);

        return response()->json([
            'message' => 'Order updated successfully',
            'order' => $order->fresh('products')
        ]);
    }

    /**
     * @OA\Delete(
     * path="/api/orders/{id}",
     * summary="Delete an order",
     * description="Permanently deletes an order and removes its associations with products. Sellers can delete their own orders, while Admins can delete any order.",
     * tags={"Orders"},
     * security={{"bearerAuth":{}}},
     * @OA\Parameter(
     * name="id",
     * in="path",
     * description="ID of the order to delete",
     * required=true,
     * @OA\Schema(type="integer", example=123)
     * ),
     * @OA\Response(
     * response=200,
     * description="Order deleted successfully",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="Order deleted successfully")
     * )
     * ),
     * @OA\Response(
     * response=401,
     * description="Unauthenticated",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="Unauthenticated.")
     * )
     * ),
     * @OA\Response(
     * response=403,
     * description="Forbidden - Unauthorized to delete this order",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="Unauthorized to delete this order.")
     * )
     * ),
     * @OA\Response(
     * response=404,
     * description="Order not found",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="No query results for model [App\\Models\\Order] 123")
     * )
     * )
     * )
     */


    // Delete an order
    public function deleteOrder(Request $request, Order $order)
    {
        //sellers delete their order and admin any
        if (auth()->id() !== $order->user_id && !auth()->user()->hasRole('Admin')) {
            return response()->json(['message' => 'Unauthorized to delete this order.'], 403);
        }
        //deletes order and safely remove from pivot
        $order->products()->detach();
        $order->delete()->detach;

        return response()->json([
            'message' => 'Order deleted successfully'
        ]);
    }
}
