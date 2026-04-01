<?php

namespace App\Services;

use App\Repositories\Contracts\SaleRepository;
use App\Repositories\Contracts\SellerRepository;
use App\Repositories\Contracts\ItemRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * SINGLE SOURCE OF TRUTH for all dashboard statistics.
 * Uses repositories to prevent N+1 queries (admin & API use same logic).
 */
class DashboardStatisticsService
{
    public function __construct(
        private SaleRepository $saleRepository,
        private SellerRepository $sellerRepository,
        private ItemRepository $itemRepository,
    ) {}

    /** Get all dashboard statistics (sales, earnings, counts, top performers) */
    public function getAllStatistics(): array
    {
        $today = Carbon::today();
        $yesterday = $today->clone()->subDay();

        return [
            // Sales totals
            'todayTotal' => $this->calculateTodayTotal($today),
            'yesterdayTotal' => $this->calculateYesterdayTotal($yesterday),
            'monthlyTotal' => $this->calculateMonthlyTotal(),
            'grandTotal' => $this->calculateGrandTotal(),

            // Earnings breakdown
            'todayEarnings' => $this->calculateEarnings($this->calculateTodayTotal($today)),
            'yesterdayEarnings' => $this->calculateEarnings($this->calculateYesterdayTotal($yesterday)),
            'monthlyEarnings' => $this->calculateEarnings($this->calculateMonthlyTotal()),
            'allTimeEarnings' => $this->calculateEarnings($this->calculateGrandTotal()),

            // Counts
            'redFlagCount' => $this->getRedFlagCount(),
            'sellerCount' => $this->sellerRepository->count(),
            'itemCount' => $this->itemRepository->count(),
            'transactionCount' => $this->getTransactionCount(),
            'daysWithSales' => $this->getDaysWithSalesCount(),

            // Top performers
            'topPerformers' => $this->getTopPerformers(),
        ];
    }

    /** Calculate today's sales total */
    public function calculateTodayTotal(Carbon $date): float
    {
        $sales = $this->saleRepository->getByDate($date);
        return $this->sumSaleTotal($sales);
    }

    /**
     * Calculate yesterday's sales total
     */
    public function calculateYesterdayTotal(Carbon $date): float
    {
        $sales = $this->saleRepository->getByDate($date);
        return $this->sumSaleTotal($sales);
    }

    /**
     * Calculate current month's sales total
     */
    public function calculateMonthlyTotal(): float
    {
        $from = now()->startOfMonth();
        $to = now()->endOfMonth();
        $sales = $this->saleRepository->getByDateRange($from, $to);
        return $this->sumSaleTotal($sales);
    }

    /** Calculate all-time sales total */
    public function calculateGrandTotal(): float
    {
        $sales = $this->saleRepository->getAll();
        return $this->sumSaleTotal($sales);
    }

    /** Calculate owner and seller earnings from total (uses config values) */
    public function calculateEarnings(float $total): array
    {
        $ownerShare = config('profit.owner_share');
        $sellerShare = config('profit.seller_share');

        return [
            'ownerShare' => round($total * $ownerShare, 2),
            'sellerShare' => round($total * $sellerShare, 2),
        ];
    }

    /** Sum total amount from collection of sales (handles custom/default prices) */
    private function sumSaleTotal($sales): float
    {
        return $sales->sum(function ($sale) {
            $price = $sale->custom_price ?: $sale->item->price;
            return ($sale->pick - $sale->returned) * $price;
        });
    }

    /** Count unique date + seller combinations (red-flagged sales only) */
    public function getRedFlagCount(): int
    {
        $redFlagSales = $this->saleRepository->getAll()
            ->filter(fn($sale) => $sale->red_flag);

        return $redFlagSales
            ->groupBy(['date', 'seller_id'])
            ->count();
    }

    /** Get unique transaction count (seller on date = 1 transaction) */
    public function getTransactionCount(): int
    {
        $sales = $this->saleRepository->getAll();
        return $sales->groupBy(['date', 'seller_id'])->count();
    }

    /** Count distinct dates with sales */
    public function getDaysWithSalesCount(): int
    {
        $sales = $this->saleRepository->getAll();
        return $sales->groupBy('date')->count();
    }

    /** Get top N performers (uses cache to prevent expensive recalculation) */
    public function getTopPerformers(int $limit = null): array
    {
        $limit = $limit ?? config('performance.top_performers_limit');
        $cacheTTL = config('cache_config.ttl.top_performers');

        return Cache::remember(
            'top_performers_cache',
            now()->addMinutes($cacheTTL),
            function () use ($limit) {
                $performanceService = new SellerPerformanceService(
                    $this->saleRepository,
                    $this->sellerRepository,
                );
                return $performanceService
                    ->calculateAllSellerPerformance()
                    ->take($limit)
                    ->toArray();
            }
        );
    }

    /** Get red-flagged sales with full seller and item details */
    public function getRedFlagSalesWithDetails(): array
    {
        $redFlagSales = $this->saleRepository->getAll()
            ->filter(fn($sale) => $sale->red_flag)
            ->sortByDesc('date');

        return $redFlagSales->map(function ($sale) {
            return [
                'id' => $sale->id,
                'date' => $sale->date->format('Y-m-d'),
                'seller' => [
                    'id' => $sale->seller->id,
                    'name' => $sale->seller->name,
                    'number' => $sale->seller->number,
                ],
                'item' => [
                    'id' => $sale->item->id,
                    'name' => $sale->item->name,
                ],
                'quantity' => [
                    'pick' => $sale->pick,
                    'returned' => $sale->returned,
                    'net' => $sale->pick - $sale->returned,
                ],
                'pricing' => [
                    'customPrice' => $sale->custom_price,
                    'itemPrice' => $sale->item->price,
                    'actualPrice' => $sale->custom_price ?: $sale->item->price,
                ],
                'total' => $sale->total,
                'remarks' => $sale->remarks,
            ];
        })->values()->toArray();
    }

    /** Invalidate all dashboard caches (call when sales change) */
    public function invalidateCache(): void
    {
        Cache::forget('top_performers_cache');
        Cache::forget('dashboard_stats');
    }
}