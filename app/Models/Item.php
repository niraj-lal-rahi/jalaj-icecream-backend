<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Item extends Model
{
    protected $fillable = ['name', 'price', 'order_by'];

    /**
     * Relationship: Sales of this item
     */
    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    // QUERY SCOPES - Reusable query filters

    /** Include sale counts */
    public function scopeWithSalesCount(Builder $query): Builder
    {
        return $query->withCount('sales');
    }

    /** Order by name */
    public function scopeOrderByName(Builder $query): Builder
    {
        return $query->orderBy('name');
    }

    /** Order by custom order_by field (for display sequencing) */
    public function scopeOrderBySequence(Builder $query): Builder
    {
        return $query->orderBy('order_by')->orderBy('name');
    }

    /** Get most sold items */
    public function scopeMostSold(Builder $query, int $limit = 10): Builder
    {
        return $query->withCount('sales')
            ->orderByDesc('sales_count')
            ->limit($limit);
    }

    /** Get items with sales in date range */
    public function scopeWithSalesInRange(Builder $query, $from, $to): Builder
    {
        return $query->whereHas('sales', function ($q) use ($from, $to) {
            $q->whereBetween('date', [$from, $to]);
        });
    }

    /** Filter by price range */
    public function scopePriceRange(Builder $query, float $min, float $max): Builder
    {
        return $query->whereBetween('price', [$min, $max]);
    }

    /** Filter by minimum price */
    public function scopeMinPrice(Builder $query, float $price): Builder
    {
        return $query->where('price', '>=', $price);
    }

    /** Filter by maximum price */
    public function scopeMaxPrice(Builder $query, float $price): Builder
    {
        return $query->where('price', '<=', $price);
    }
}
