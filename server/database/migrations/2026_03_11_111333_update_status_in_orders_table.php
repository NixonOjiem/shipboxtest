<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // status column to an ENUM with your specific list
            $table->enum('status', [
                'onhold',
                'returned',
                'delivered',
                'refunded',
                'outofstock',
                'cancelled',
                'shipped',
                'to prepare'
            ])->default('to prepare')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Revert back to a simple string
            $table->string('status')->default('pending')->change();
        });
    }
};
