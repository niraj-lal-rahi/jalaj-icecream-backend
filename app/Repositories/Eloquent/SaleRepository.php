<?php

namespace App\Repositories\Eloquent;

use App\Models\Sale;
use App\Repositories\Contracts\SaleRepository as SaleRepositoryContract;
use Illuminate\Database\Eloquent\Collection;
use Carbon\Carbon;

/**
 * All sale database queries (always includes eager loading to prevent N+1).
 */
class SaleRepository implements SaleRepositoryContract
{
    /** Get all sales with eager-loaded item + seller relationships */
    public function getAll(): Collection
    {
        return Sale::with('item', 'seller')
            ->latest()
            ->get();
    }

    /** Get sales in date range (with eager loading to prevent N+1) */
    public function getByDateRange($from, $to): Collection
    {
        return Sale::whereBetween('date', [$from, $to])
            ->with('item', 'seller')  // ✅ Eager load - prevents N+1
            ->orderByDesc('date')
            ->get();
    }

    /** Get sales for specific seller on a date */
    public function getBySellerOnDate(int $sellerId, $date): Collection
    {
        $date = $date instanceof Carbon ? $date : Carbon::parse($date);

        return Sale::where('seller_id', $sellerId)
            ->whereDate('date', $date)
            ->with('item')  // ✅ Eager load
            ->get();
    }

    /** Get all sales by seller for date range */
    public function getBySellerDateRange(int $sellerId, $from, $to): Collection
    {
        return Sale::where('seller_id', $sellerId)
            ->whereBetween('date', [$from, $to])
            ->with('item')  // ✅ Eager load
            ->orderByDesc('date')
            ->get();
    }

    /** Get top performing sales by amount */
    public function getTopByAmount(int $limit = 10): Collection
    {
        return Sale::with('item', 'seller')  // ✅ Eager load
            ->orderByDesc('total')
            ->limit($limit)
            ->get();
    }

    /** Count sales in date range */
    public function countByDateRange($from, $to): int
    {
        return Sale::whereBetween('date', [$from, $to])->count();
    }

    /** Get total sale amount for date range */
    public function getTotalAmountByDateRange($from, $to): float
    {
        $total = Sale::whereBetween('date', [$from, $to])
            ->sum('total');

        return (float) ($total ?? 0);
    }

    /** Get sales for a specific date (with eager loading) */
    public function getByDate($date): Collection
    {
        $date = $date instanceof Carbon ? $date : Carbon::parse($date);

        return Sale::whereDate('date', $date)
            ->with('item', 'seller')  // ✅ Eager load
            ->orderByDesc('created_at')
            ->get();
    }

    /** Get sales grouped by date for dashboard statistics */
    public function getGroupedByDate($from, $to): array
    {
        $sales = $this->getByDateRange($from, $to);

        // Group by date (maintain eager-loaded relationships)
        return $sales->groupBy(function ($sale) {
            return $sale->date->format('Y-m-d');
        })->toArray();
    }
}
