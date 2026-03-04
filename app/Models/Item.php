<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    protected $fillable = ['name', 'price', 'order_by'];

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }
}
