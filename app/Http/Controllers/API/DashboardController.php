<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use App\Http\Traits\ApiResponse;
use App\Models\Item;
use App\Models\Sale;
use App\Models\Seller;
use App\Services\SellerPerformanceService;
use Carbon\Carbon;

class DashboardController extends Controller
{
    use ApiResponse;

    // === Constants (UPPER_SNAKE_CASE) ===
    // Profit distribution percentages (must sum to 1.0)
    private const OWNER_SHARE_PERCENTAGE = 0.6;  // 60% to owner
    private const SELLER_SHARE_PERCENTAGE = 0.4; // 40% to sellers

    /**
     * Get dashboard metrics including sales totals and counts
     *
     * Returns:
     * - Today's, yesterday's, monthly, and all-time sales totals
     * - Owner and seller earnings breakdown
     * - Red flag transaction count
     * - Seller and item counts
     * - Unique transaction and sales day counts
     *
     * @return \Illuminate\Http\JsonResponse Dashboard metrics
     */
    public function index()
    {
        try {
            $today = Carbon::today();
            $yesterday = $today->clone()->subDay();

            // Today's Total Sales
            $todaySales = Sale::whereDate('date', $today)->get();
            $todayTotal = $todaySales->sum(function ($sale) {
                $price = $sale->custom_price ?: $sale->item->price;
                return ($sale->pick - $sale->returned) * $price;
            });

            // Yesterday's Total Sales
            $yesterdaySales = Sale::whereDate('date', $yesterday)->get();
            $yesterdayTotal = $yesterdaySales->sum(function ($sale) {
                $price = $sale->custom_price ?: $sale->item->price;
                return ($sale->pick - $sale->returned) * $price;
            });

            // Monthly Total Sales
            $monthlySales = Sale::whereMonth('date', now()->month)
                ->whereYear('date', now()->year)
                ->get();
            $monthlyTotal = $monthlySales->sum(function ($sale) {
                $price = $sale->custom_price ?: $sale->item->price;
                return ($sale->pick - $sale->returned) * $price;
            });

            // Grand Total Sales (All Time)
            $allSales = Sale::with('item')->get();
            $grandTotal = $allSales->sum(function ($sale) {
                $price = $sale->custom_price ?: $sale->item->price;
                return ($sale->pick - $sale->returned) * $price;
            });

            // Red Flag Count - unique combinations of date and seller_id (count 1 per seller per date)
            $redFlagCount = Sale::where('red_flag', true)
                ->get()
                ->groupBy(['date', 'seller_id'])
                ->count();

            // Counts
            $sellerCount = Seller::count();
            $itemCount = Item::count();
            // Count unique transactions: 1 transaction = 1 seller per date (unique date + seller_id combination)
            $transactionCount = Sale::selectRaw('DISTINCT date, seller_id')->count('seller_id');

            // Count unique days with sales (distinct dates only)
            $daysWithSales = Sale::distinct('date')->count('date');

            // Calculate earnings
            $ownerEarning = $grandTotal * self::OWNER_SHARE_PERCENTAGE;
            $sellerEarning = $grandTotal * self::SELLER_SHARE_PERCENTAGE;
            $yesterdayOwnerShare = $yesterdayTotal * self::OWNER_SHARE_PERCENTAGE;
            $yesterdaySellerShare = $yesterdayTotal * self::SELLER_SHARE_PERCENTAGE;

            return $this->success([
                'todayTotal' => $todayTotal,
                'yesterdayTotal' => $yesterdayTotal,
                'yesterdayOwnerShare' => $yesterdayOwnerShare,
                'yesterdaySellerShare' => $yesterdaySellerShare,
                'monthlyTotal' => $monthlyTotal,
                'grandTotal' => $grandTotal,
                'ownerEarning' => $ownerEarning,
                'sellerEarning' => $sellerEarning,
                'redFlagCount' => $redFlagCount,
                'sellerCount' => $sellerCount,
                'itemCount' => $itemCount,
                'transactionCount' => $transactionCount,
                'daysWithSales' => $daysWithSales,
            ], 'Dashboard data retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve dashboard data', 500, $e->getMessage());
        }
    }

    /**
     * Get all red flag sales with seller and item details
     *
     * Red flags indicate sales that have been manually reviewed/audited.
     * Results are ordered by date (most recent first).
     *
     * @return \Illuminate\Http\JsonResponse Paginated red flag sales with count
     */
    public function getRedFlagSales()
    {
        try {
            $redFlagSales = Sale::with(['seller', 'item'])
                ->where('red_flag', true)
                ->orderByDesc('date')
                ->get()
                ->map(function ($sale) {
                    return [
                        'id' => $sale->id,
                        'date' => $sale->date,
                        'seller' => [
                            'id' => $sale->seller->id,
                            'name' => $sale->seller->name,
                            'number' => $sale->seller->number,
                        ],
                        'item' => [
                            'id' => $sale->item->id,
                            'name' => $sale->item->name,
                        ],
                        'pick' => $sale->pick,
                        'returned' => $sale->returned,
                        'netQty' => $sale->pick - $sale->returned,
                        'customPrice' => $sale->custom_price,
                        'itemPrice' => $sale->item->price,
                        'total' => $sale->total,
                        'remarks' => $sale->remarks,
                    ];
                });

            return $this->success($redFlagSales, 'Red flag sales retrieved successfully', 200, [
                'count' => $redFlagSales->count(),
            ]);
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve red flag sales', 500, $e->getMessage());
        }
    }

    /**
     * Get all entry days with present and absent sellers per date
     *
     * For each sales day in the system, shows which sellers were present
     * (had sales) and which were absent (no sales). Results are sorted
     * by date (most recent first).
     *
     * @return \Illuminate\Http\JsonResponse Entry days with present/absent sellers and count
     */
    public function getEntryDays()
    {
        try {
            // Get all unique dates and all sellers
            $allDates = Sale::distinct('date')->pluck('date')->toArray();
            $allSellers = Seller::all();

            // Build entry days with present and absent sellers
            $entryDays = collect($allDates)->map(function ($date) use ($allSellers) {
                // Get sellers with sales on this date (present)
                $sellerIdsOnDate = Sale::where('date', $date)
                    ->distinct('seller_id')
                    ->pluck('seller_id')
                    ->toArray();

                $presentSellers = $allSellers->whereIn('id', $sellerIdsOnDate)->map(function ($seller) {
                    return [
                        'id' => $seller->id,
                        'name' => $seller->name,
                        'number' => $seller->number,
                    ];
                })->values();

                // Get sellers without sales on this date (absent)
                $absentSellerIds = $allSellers->pluck('id')->toArray();
                $absentSellerIds = array_diff($absentSellerIds, $sellerIdsOnDate);
                $absentSellers = $allSellers->whereIn('id', $absentSellerIds)->map(function ($seller) {
                    return [
                        'id' => $seller->id,
                        'name' => $seller->name,
                        'number' => $seller->number,
                    ];
                })->values();

                return [
                    'date' => $date,
                    'presentCount' => count($sellerIdsOnDate),
                    'absentCount' => count($absentSellerIds),
                    'presentSellers' => $presentSellers,
                    'absentSellers' => $absentSellers,
                ];
            })->sortByDesc('date')->values();

            return $this->success($entryDays, 'Entry days retrieved successfully', 200, [
                'count' => $entryDays->count(),
            ]);
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve entry days', 500, $e->getMessage());
        }
    }

    /**
     * Get seller performance metrics with ranking
     *
     * Returns all sellers ranked by their performance score (highest first).
     * Performance score is calculated based on:
     * - Volume Score (50%): Total sales as % of highest seller
     * - Consistency Score (50%): Days active as % of total days in system
     *
     * Uses SellerPerformanceService (SINGLE SOURCE OF TRUTH) to ensure
     * mobile API rankings match the web admin dashboard.
     *
     * @return \Illuminate\Http\JsonResponse Ranked sellers with performance metrics and count
     */
    public function getSellerPerformance()
    {
        try {
            // Get all seller performance data using centralized service (SINGLE SOURCE OF TRUTH)
            $performanceService = new SellerPerformanceService();
            $sellerPerformance = $performanceService->calculateAllSellerPerformance();

            return $this->success($sellerPerformance, 'Seller performance data retrieved successfully', 200, [
                'count' => $sellerPerformance->count(),
            ]);
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve seller performance', 500, $e->getMessage());
        }
    }
}
