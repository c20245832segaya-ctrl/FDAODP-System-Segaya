<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('food_order_id')
                ->constrained('food_orders')
                ->cascadeOnDelete();

            $table->string('menu_item_name');
            $table->decimal('item_price', 10, 2);
            $table->unsignedInteger('quantity');

            $table->timestamps();
            $table->index(['food_order_id', 'menu_item_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};