<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\Seller;
use Illuminate\Support\Collection;

/**
 * SellerPerformanceService
 *
 * SINGLE SOURCE OF TRUTH for all performance calculations.
 * Any changes to the formula here automatically propagate to:
 * - Admin Dashboard (main page)
 * - Admin SellerPerformanceController (detail page)
 * - Mobile API DashboardController
 *
 * Following the principle: DRY (Don't Repeat Yourself)
 * Extract business logic to Service for reusability across controllers/APIs
 */
class SellerPerformanceService
{
    // === Constants (UPPER_SNAKE_CASE) ===
    // Profit sharing: owner keeps 60%, seller gets 40% of total sales
    private const OWNER_SHARE_PERCENTAGE = 0.6;
    private const SELLER_SHARE_PERCENTAGE = 0.4;

    // Performance score weighting: 50% volume (sales amount), 50% consistency (days active)
    private const VOLUME_WEIGHT = 0.5;
    private const CONSISTENCY_WEIGHT = 0.5;

    // Decimal precision for score calculations
    private const SCORE_PRECISION = 2;

    /**
     * Calculate performance metrics for all sellers
     * SINGLE SOURCE OF TRUTH for performance calculations
     *
     * @return Collection Sorted sellers with performance metrics
     */
    public function calculateAllSellerPerformance(): Collection
    {
        $sellers = Seller::all();
        $allSales = Sale::with('item')->get();

        // Get all unique dates in the system
        $allDates = Sale::distinct('date')->pluck('date')->toArray();
        $totalDaysInSystem = count($allDates);

        // Calculate max sales amount ONCE for all sellers (for volume normalization)
        $maxSalesAmount = $this->calculateMaxSalesAmount($allSales);

        // Calculate performance for each seller
        return $sellers->map(function ($seller) use ($allDates, $totalDaysInSystem, $allSales, $maxSalesAmount) {
            return $this->calculateSellerMetrics($seller, $allDates, $totalDaysInSystem, $allSales, $maxSalesAmount);
        })->sortByDesc('performanceScore')->values();
    }

    /**
     * Get top N performers
     *
     * @param int $limit Number of top performers to return (default: 3)
     * @return Collection Top performers
     */
    public function getTopPerformers(int $limit = 3): Collection
    {
        return $this->calculateAllSellerPerformance()->take($limit);
    }

    /**
     * Calculate metrics for a single seller
     * MASTER FORMULA - any changes here automatically reflect in:
     * - Admin Dashboard (main page)
     * - Admin SellerPerformanceController (detail page)
     * - Mobile API DashboardController
     *
     * @param Seller $seller
     * @param array $allDates All dates in system
     * @param int $totalDaysInSystem Total days in system
     * @param Collection $allSales All sales
     * @param float $maxSalesAmount Max sales amount across all sellers
     * @return array Seller performance data with volumeScore, consistencyScore, performanceScore
     */
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

        // Calculate profit shares using constants
        $ownerShare = $totalSalesAmount * self::OWNER_SHARE_PERCENTAGE;
        $sellerShare = $totalSalesAmount * self::SELLER_SHARE_PERCENTAGE;

        // Get dates without sales (absent days)
        $sellerDates = $sellerSales->pluck('date')->unique()->sort()->values()->toArray();
        $absentDays = array_diff($allDates, $sellerDates);

        // === MASTER PERFORMANCE CALCULATION FORMULA ===
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
            'volumeScore' => round($volumeScore, self::SCORE_PRECISION),
            'consistencyScore' => round($consistencyScore, self::SCORE_PRECISION),
            'performanceScore' => round($performanceScore, self::SCORE_PRECISION),
        ];
    }

    /**
     * Calculate maximum sales amount across all sellers
     *
     * Used to normalize volume scores (0-100 scale).
     * If no sales exist, returns 0 to prevent division by zero.
     *
     * @param Collection $allSales All sales
     * @return float Max sales amount (0 if no sales exist)
     */
    private function calculateMaxSalesAmount(Collection $allSales): float
    {
        return (float) $allSales->sum(function (Sale $sale) {
            $price = $sale->custom_price ?: $sale->item->price;
            return ($sale->pick - $sale->returned) * $price;
        });
    }

    /**
     * Calculate total sales amount for a specific seller
     *
     * Formula: (quantity_picked - quantity_returned) * unit_price, summed across all sales
     * Accounts for custom pricing if set, otherwise uses item's default price.
     *
     * @param Collection $sellerSales Sales for a specific seller
     * @return float Total sales amount for seller
     */
    private function calculateTotalSalesAmount(Collection $sellerSales): float
    {
        return (float) $sellerSales->sum(function (Sale $sale) {
            $price = $sale->custom_price ?: $sale->item->price;
            return ($sale->pick - $sale->returned) * $price;
        });
    }

    /**
     * Calculate volume score (sales-amount focused)
     *
     * Represents the seller's total sales as a percentage of the highest seller's sales.
     * Range: 0-100 (where 100 = seller with highest sales)
     *
     * Formula: (sellerSalesAmount / maxSalesAmount) * 100
     *
     * @param float $totalSalesAmount Seller's total sales amount
     * @param float $maxSalesAmount Highest seller's sales amount
     * @return float Volume score (0-100)
     */
    private function calculateVolumeScore(float $totalSalesAmount, float $maxSalesAmount): float
    {
        if ($maxSalesAmount <= 0) {
            return 0.0;
        }

        return ($totalSalesAmount / $maxSalesAmount) * 100;
    }

    /**
     * Calculate consistency score (presence focused)
     *
     * Represents the seller's days active as a percentage of total days in system.
     * Range: 0-100 (where 100 = seller active every day since system started)
     *
     * Formula: (daysWithSales / totalDaysInSystem) * 100
     *
     * @param int $daysWithSales Number of days seller had sales
     * @param int $totalDaysInSystem Total days in system
     * @return float Consistency score (0-100)
     */
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
     * Default weighting: 50% volume (sales amount), 50% consistency (days active)
     *
     * Formula: (volumeScore * VOLUME_WEIGHT) + (consistencyScore * CONSISTENCY_WEIGHT)
     *
     * To change weighting, update class constants:
     * - VOLUME_WEIGHT (currently 0.5 = 50%)
     * - CONSISTENCY_WEIGHT (currently 0.5 = 50%)
     * Note: Weights should sum to 1.0
     *
     * @param float $volumeScore Volume score (0-100)
     * @param float $consistencyScore Consistency score (0-100)
     * @return float Performance score (0-100)
     */
    private function calculatePerformanceScore(float $volumeScore, float $consistencyScore): float
    {
        return ($volumeScore * self::VOLUME_WEIGHT) + ($consistencyScore * self::CONSISTENCY_WEIGHT);
    }
}
