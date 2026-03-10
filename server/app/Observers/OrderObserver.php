<?php

namespace App\Observers;

use App\Models\Order;
// use App\Traits\ManagesStock;
use App\Traits\ManagesStock;
use Illuminate\Support\Facades\DB;

class OrderObserver
{
    use ManagesStock;
    /**
     * Handle the Order "created" event.
     */
    public function created(Order $order): void
    {
        //
    }

    /**
     * Handle the Order "updated" event.
     */
    public function updated(Order $order): void
    {
        // Check if the status attribute specifically was changed
        if ($order->isDirty('status')) {
            $newStatus = $order->status;
            $oldStatus = $order->getOriginal('status');

            // all products update together
            DB::transaction(function () use ($order, $newStatus, $oldStatus) {
                foreach ($order->products as $product) {
                    $quantity = $product->pivot->quantity;

                    // Logic: Status changes to 'delivered'
                    if ($newStatus === 'delivered' && $oldStatus !== 'delivered') {
                        $this->decreaseStock($product, $quantity);
                    }

                    // Logic: Status changes from 'delivered' to 'returned'
                    if ($newStatus === 'returned' && $oldStatus === 'delivered') {
                        $this->increaseStock($product, $quantity);
                    }
                }
            });
        }
    }

    /**
     * Handle the Order "deleted" event.
     */
    public function deleted(Order $order): void
    {
        //
    }

    /**
     * Handle the Order "restored" event.
     */
    public function restored(Order $order): void
    {
        //
    }

    /**
     * Handle the Order "force deleted" event.
     */
    public function forceDeleted(Order $order): void
    {
        //
    }
}
