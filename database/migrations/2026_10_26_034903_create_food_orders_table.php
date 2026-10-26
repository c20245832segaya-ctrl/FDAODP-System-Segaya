<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('food_orders', function (Blueprint $table) {
            $table->id();
            $table->string('dispatch_id')->unique();

            $table->foreignId('customer_id')
                ->constrained('customers')
                ->restrictOnDelete();

            $table->foreignId('restaurant_id')
                ->constrained('restaurants')
                ->restrictOnDelete();

            $table->foreignId('rider_id')
                ->nullable()
                ->constrained('riders')
                ->nullOnDelete();

            $table->decimal('delivery_fee', 10, 2)->default(0);
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('total_bill', 10, 2)->default(0);

            $table->string('dispatch_status')->default('pending')->index();
            $table->timestamps();

            $table->index(['rider_id', 'dispatch_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('food_orders');
    }
};