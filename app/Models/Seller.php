<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Seller extends Model
{
    protected $fillable = [
        'name',
        'number',
        'address',
    ];

    public function documents()
    {
        return $this->hasMany(SellerDocument::class);
    }
}
