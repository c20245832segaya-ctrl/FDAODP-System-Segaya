<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Restaurant extends Model
{
    use HasFactory;

    protected $fillable = ['restaurant_name'];

    public function foodOrders(): HasMany
    {
        return $this->hasMany(FoodOrder::class);
    }
}