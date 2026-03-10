<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class Seller extends Model
{
    protected $fillable = [
        'name',
        'number',
        'address',
    ];

    /**
     * Relationship: Sales created by this seller
     */
    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    /**
     * Relationship: Seller verification documents
     */
    public function documents(): HasMany
    {
        return $this->hasMany(SellerDocument::class);
    }

    // QUERY SCOPES - Reusable query filters

    /** Eager load sales + documents */
    public function scopeWithRelations(Builder $query): Builder
    {
        return $query->with('sales', 'documents');
    }

    /** Include sale counts */
    public function scopeWithSalesCount(Builder $query): Builder
    {
        return $query->withCount('sales');
    }

    /** Include sellers with sales in date range (with eager loading) */
    public function scopeWithSalesInRange(Builder $query, $from, $to): Builder
    {
        return $query->whereHas('sales', function ($q) use ($from, $to) {
            $q->whereBetween('date', [$from, $to]);
        })->with(['sales' => function ($q) use ($from, $to) {
            $q->whereBetween('date', [$from, $to])->with('item');
        }]);
    }

    /** Include sellers with recent activity (last N days) */
    public function scopeRecentActivity(Builder $query, int $days = 7): Builder
    {
        $from = now()->subDays($days)->startOfDay();
        return $query->whereHas('sales', function ($q) use ($from) {
            $q->where('created_at', '>=', $from);
        });
    }

    /** Order by name */
    public function scopeOrderByName(Builder $query): Builder
    {
        return $query->orderBy('name');
    }

    /** Order by phone number */
    public function scopeOrderByNumber(Builder $query): Builder
    {
        return $query->orderBy('number');
    }

    /** Filter by phone number */
    public function scopeByPhone(Builder $query, string $phone): Builder
    {
        return $query->where('number', $phone);
    }
}
