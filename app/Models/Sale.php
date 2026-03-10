<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

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

    // QUERY SCOPES - Reusable query filters (always eager-load to prevent N+1)

    /** Filter by date range: Sale::dateRange($from, $to)->get() */
    public function scopeDateRange(Builder $query, $from, $to): Builder
    {
        return $query->whereBetween('date', [$from, $to]);
    }

    /** Filter by seller ID: Sale::bySeller($sellerId)->get() */
    public function scopeBySeller(Builder $query, int $sellerId): Builder
    {
        return $query->where('seller_id', $sellerId);
    }

    /** Filter by specific date: Sale::forDate(now())->get() */
    public function scopeForDate(Builder $query, $date): Builder
    {
        $date = $date instanceof Carbon ? $date : Carbon::parse($date);
        return $query->whereDate('date', $date);
    }

    /** Eager load item + seller: Sale::withRelations()->get() */
    public function scopeWithRelations(Builder $query): Builder
    {
        return $query->with('item', 'seller');
    }

    /** Order by most recent first: Sale::latest()->get() */
    public function scopeLatest(Builder $query): Builder
    {
        return $query->orderByDesc('date')->orderByDesc('created_at');
    }

    /** Filter by red flag status: Sale::redFlagged()->get() */
    public function scopeRedFlagged(Builder $query): Builder
    {
        return $query->where('red_flag', true);
    }

    /** Filter by creation date: Sale::createdAfter(now()->subDays(7))->get() */
    public function scopeCreatedAfter(Builder $query, $date): Builder
    {
        $date = $date instanceof Carbon ? $date : Carbon::parse($date);
        return $query->where('created_at', '>=', $date);
    }

}