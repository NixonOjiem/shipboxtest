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

    // Update order (Handles status changes that trigger the Observer)
    public function updateOrder(Request $request, Order $order)
    {
        //sellers update their own orders and admin updates all
        if (auth()->id() !== $order->user_id && !auth()->user()->hasRole('Admin')) {
            return response()->json(['message' => 'Unauthorized to update this order.'], 403);
        }

        // Define the exact statuses allowed by requirements
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
