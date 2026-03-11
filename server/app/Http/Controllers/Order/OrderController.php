<?php

namespace App\Http\Controllers\Order;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Log;

class OrderController extends Controller
{

    // Handle order creation

    public function createOrder(Request $request)
    {
        $validated = $request->validate([
            'customer_phone' => 'required|string|max:20',
            'customer_address' => 'required|string',
            'note' => 'nullable|string',
            'products' => 'required|array|min:1',
            'products.*.id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1',
        ]);

        DB::beginTransaction();

        try {

            $totalPrice = 0;
            $totalQuantity = 0;
            $productAttachments = [];

            //  Optimization: Fetch all products in one query to improve performance
            $productIds = collect($validated['products'])->pluck('id');
            $products = Product::whereIn('id', $productIds)->get()->keyBy('id');

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
                'user_id' => auth()->id(),
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

    // Fetch all orders for admins

    public function fetchAllOrders()
    {
        // Use eager loading ('with')
        $orders = Order::with(['products', 'seller'])->latest()->paginate(15);

        return response()->json($orders);
    }


    // Update order (Handles status changes that trigger the Observer)

    public function updateOrder(Request $request, Order $order)
    {
        // Define the exact statuses allowed by your requirements
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
        $order->delete();

        return response()->json([
            'message' => 'Order deleted successfully'
        ]);
    }

    // fetch sellers orders
    public function fetchSellerOrders(Request $request, User $user)
    {
        // Authorization: the user or the Admin
        if (auth()->id() !== $user->id && !auth()->user()->hasRole('Admin')) {
            return response()->json(['message' => 'Unauthorized to view these orders.'], 403);
        }

        // Fetch the orders using the Eloquent relationship
        $orders = $user->orders()
            ->with('products')
            ->latest() // Orders by created_at descending
            ->paginate(15);

        // Return the response
        return response()->json([
            'seller' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'orders' => $orders
        ]);
    }
}
