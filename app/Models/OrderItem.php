<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'food_order_id',
        'menu_item_name',
        'item_price',
        'quantity',
    ];

    protected function casts(): array
    {
        return [
            'item_price' => 'decimal:2',
            'quantity' => 'integer',
        ];
    }

    public function foodOrder(): BelongsTo
    {
        return $this->belongsTo(FoodOrder::class);
    }
}