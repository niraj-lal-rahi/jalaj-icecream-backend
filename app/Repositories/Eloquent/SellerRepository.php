<?php

namespace App\Repositories\Eloquent;

use App\Models\Seller;
use App\Repositories\Contracts\SellerRepository as SellerRepositoryContract;
use Illuminate\Database\Eloquent\Collection;
use Carbon\Carbon;

/** All seller database queries (always includes eager loading) */
class SellerRepository implements SellerRepositoryContract
{
    /** Get all sellers with relationships (sales, documents) */
    public function getAll(): Collection
    {
        return Seller::with('sales', 'documents')
            ->orderBy('name')
            ->get();
    }

    /** Get seller by ID with relationships */
    public function getById(int $id)
    {
        return Seller::with('sales', 'documents')
            ->find($id);
    }

    /** Get seller by phone number */
    public function getByPhone(string $phone)
    {
        return Seller::where('number', $phone)
            ->with('sales')
            ->first();
    }

    /** Get all sellers with sale counts (efficient: single query + aggregate) */
    public function getAllWithSaleCount(): Collection
    {
        return Seller::withCount('sales')  // ✅ Efficient: Uses single query + aggregate
            ->orderBy('name')
            ->get();
    }

    /** Get sellers that have sales in date range (with eager loading) */
    public function getWithSalesByDateRange($from, $to): Collection
    {
        return Seller::whereHas('sales', function ($query) use ($from, $to) {
            $query->whereBetween('date', [$from, $to]);
        })
        ->with(['sales' => function ($query) use ($from, $to) {
            $query->whereBetween('date', [$from, $to])
                ->with('item');  // ✅ Eager load nested relationships
        }])
        ->orderBy('name')
        ->get();
    }

    /** Get seller statistics */
    public function getStatistics(int $sellerId, $from = null, $to = null): array
    {
        $query = Seller::find($sellerId);

        if (!$query) {
            return [];
        }

        $salesQuery = $query->sales();

        if ($from && $to) {
            $salesQuery->whereBetween('date', [$from, $to]);
        }

        $sales = $salesQuery->with('item')->get();

        return [
            'seller_id' => $sellerId,
            'seller_name' => $query->name,
            'total_sales' => $sales->count(),
            'total_amount' => $sales->sum('total'),
            'average_sale' => $sales->count() > 0 ? $sales->avg('total') : 0,
            'days_with_sales' => $sales->groupBy(fn($s) => $s->date->format('Y-m-d'))->count(),
        ];
    }

    /**
     * Count total sellers
     */
    public function count(): int
    {
        return Seller::count();
    }
}
