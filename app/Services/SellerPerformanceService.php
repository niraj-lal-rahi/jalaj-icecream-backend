<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\Seller;
use App\Repositories\Contracts\SaleRepository;
use App\Repositories\Contracts\SellerRepository;
use Illuminate\Support\Collection;

/**
 * SINGLE SOURCE OF TRUTH for seller performance calculations.
 * Uses repositories instead of direct model queries.
 */
class SellerPerformanceService
{
    /** SaleRepository for all sale queries (never use Sale::query() directly) */
    private SaleRepository $saleRepository;

    /** SellerRepository for all seller queries (never use Seller::query() directly) */
    private SellerRepository $sellerRepository;

    /** Constructor - accepts repositories via DI (bound in AppServiceProvider) */
    public function __construct(
        SaleRepository $saleRepository,
        SellerRepository $sellerRepository
    ) {
        $this->saleRepository = $saleRepository;
        $this->sellerRepository = $sellerRepository;
    }

    /** Calculate performance metrics for all sellers (uses repositories, prevents N+1) */
    public function calculateAllSellerPerformance(): Collection
    {
        // Get all sellers and sales from repositories (not models)
        $sellers = $this->sellerRepository->getAll();
        $allSales = $this->saleRepository->getAll();

        // Get all unique dates in the system
        $allDates = $allSales->pluck('date')->unique()->sort()->values()->toArray();
        $totalDaysInSystem = count($allDates);

        // Calculate total sales amount for each seller first
        $sellerTotals = [];
        foreach ($sellers as $seller) {
            $sellerSales = $allSales->filter(fn($sale) => $sale->seller_id === $seller->id);
            $sellerTotals[$seller->id] = $this->calculateTotalSalesAmount($sellerSales);
        }

        // Find max sales amount from all sellers (highest individual seller total, for volume score normalization)
        $maxSalesAmount = max($sellerTotals) ?: 0;

        // Calculate performance metrics for each seller
        return $sellers->map(function ($seller) use ($allDates, $totalDaysInSystem, $allSales, $maxSalesAmount) {
            return $this->calculateSellerMetrics($seller, $allDates, $totalDaysInSystem, $allSales, $maxSalesAmount);
        })->sortByDesc('performanceScore')->values();
    }

    /** Get top N performers */
    public function getTopPerformers(int $limit = 3): Collection
    {
        return $this->calculateAllSellerPerformance()->take($limit);
    }

    /** Calculate metrics for a single seller (MASTER FORMULA for performance calculation) */
    private function calculateSellerMetrics(
        Seller $seller,
        array $allDates,
        int $totalDaysInSystem,
        Collection $allSales,
        float $maxSalesAmount
    ): array {
        // Filter sales for this seller
        $sellerSales = $allSales->filter(function ($sale) use ($seller) {
            return $sale->seller_id === $seller->id;
        });

        // Calculate total sales amount
        $totalSalesAmount = $this->calculateTotalSalesAmount($sellerSales);

        // Get unique dates this seller has sales
        $daysWithSales = $sellerSales->pluck('date')->unique()->count();

        // Calculate profit shares using config values (not hardcoded constants)
        $ownerShare = $totalSalesAmount * config('profit.owner_share');
        $sellerShare = $totalSalesAmount * config('profit.seller_share');

        // Get dates without sales (absent days)
        $sellerDates = $sellerSales->pluck('date')->unique()->sort()->values()->toArray();
        $absentDays = array_diff($allDates, $sellerDates);

        // === MASTER PERFORMANCE CALCULATION FORMULA ===
        // Uses weights from config/performance.php (volume_weight and consistency_weight)
        $volumeScore = $this->calculateVolumeScore($totalSalesAmount, $maxSalesAmount);
        $consistencyScore = $this->calculateConsistencyScore($daysWithSales, $totalDaysInSystem);
        $performanceScore = $this->calculatePerformanceScore($volumeScore, $consistencyScore);

        return [
            'id' => $seller->id,
            'name' => $seller->name,
            'number' => $seller->number,
            'totalSalesAmount' => $totalSalesAmount,
            'ownerShare' => $ownerShare,
            'sellerShare' => $sellerShare,
            'daysWithSales' => $daysWithSales,
            'absentDays' => count($absentDays),
            'totalDays' => $totalDaysInSystem,
            'presentDates' => $sellerDates,
            'absentDates' => array_values($absentDays),
            'volumeScore' => round($volumeScore, config('performance.score_precision')),
            'consistencyScore' => round($consistencyScore, config('performance.score_precision')),
            'performanceScore' => round($performanceScore, config('performance.score_precision')),
        ];
    }

    /** Calculate total sales amount for a specific seller (accounts for custom pricing) */
    private function calculateTotalSalesAmount(Collection $sellerSales): float
    {
        return (float) $sellerSales->sum(function (Sale $sale) {
            $price = $sale->custom_price ?: $sale->item->price;
            return ($sale->pick - $sale->returned) * $price;
        });
    }

    /** Volume score: seller's total sales as % of highest seller (range 0-100) */
    private function calculateVolumeScore(float $totalSalesAmount, float $maxSalesAmount): float
    {
        if ($maxSalesAmount <= 0) {
            return 0.0;
        }

        return ($totalSalesAmount / $maxSalesAmount) * 100;
    }

    /** Consistency score: seller's active days as % of total system days (range 0-100) */
    private function calculateConsistencyScore(int $daysWithSales, int $totalDaysInSystem): float
    {
        if ($totalDaysInSystem <= 0) {
            return 0.0;
        }

        return ($daysWithSales / $totalDaysInSystem) * 100;
    }

    /**
     * Calculate final performance score
     *
     * Weighted average of volume and consistency scores.
     * Weighting values loaded from config/performance.php
     *
     * Formula: (volumeScore * volume_weight) + (consistencyScore * consistency_weight)
     *
     * To change weighting, update config/performance.php:
     * - 'volume_weight' (currently 0.5 = 50%)
     * - 'consistency_weight' (currently 0.5 = 50%)
     * Note: Weights should sum to 1.0
     *
     * @param float $volumeScore Volume score (0-100)
     * @param float $consistencyScore Consistency score (0-100)
     * @return float Performance score (0-100)
     */
    private function calculatePerformanceScore(float $volumeScore, float $consistencyScore): float
    {
        return ($volumeScore * config('performance.volume_weight')) + ($consistencyScore * config('performance.consistency_weight'));
    }
}
