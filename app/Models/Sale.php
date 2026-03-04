<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    protected $fillable = [
        'seller_id',
        'item_id',
        'pick',
        'returned',
        'custom_price',
        'red_flag',
        'date',
        'remarks',
    ];

    public function seller()
    {
        return $this->belongsTo(Seller::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function getTotalAttribute()
    {
        $price = $this->custom_price ?: $this->item->price;

        return ($this->pick - $this->returned) * $price;
    }
}
