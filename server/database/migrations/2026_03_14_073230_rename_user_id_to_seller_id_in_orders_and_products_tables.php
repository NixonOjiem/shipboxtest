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
        if (Schema::hasColumn('products', 'user_id')) {
            Schema::table('products', function (Blueprint $table) {
                //drop foreigkey
                //$table->dropForeign(['user_id']);
                $table->renameColumn('user_id', 'seller_id');
                //create the forignkey
                $table->foreign('seller_id')->references('id')->on('users')->cascadeOnDelete();
            });

        }

        if (Schema::hasColumn('orders', 'user_id')) {
            Schema::table('orders', function (Blueprint $table) {
                //drop foreignkey
                //$table->dropForeign('user_id');
                //renamecolumn
                $table->renameColumn('user_id', 'seller_id');
                //create foreign key
                $table->foreign('seller_id')->references('id')->on('users')->cascadeOnDelete();
            });
        }

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            //drop foreign key
            $table->dropForeign(['seller_id']);
            //rename collumn
            $table->renameColumn('seller_id', 'user_id');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['seller_id']);
            $table->renameColumn('seller_id', 'user_id');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};
