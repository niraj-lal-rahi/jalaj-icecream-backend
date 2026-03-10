<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\Sale;
use App\Models\Seller;
use Carbon\Carbon;

class DashboardController extends Controller
{
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
            $ownerEarning = $grandTotal * 0.6;  // 60% to owner
            $sellerEarning = $grandTotal * 0.4;  // 40% to sellers
            $yesterdayOwnerShare = $yesterdayTotal * 0.6;
            $yesterdaySellerShare = $yesterdayTotal * 0.4;

            return response()->json([
                'status' => true,
                'message' => 'Dashboard data retrieved successfully',
                'data' => [
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
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve dashboard data',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all red flag sales with seller and item details
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

            return response()->json([
                'status' => true,
                'message' => 'Red flag sales retrieved successfully',
                'data' => $redFlagSales,
                'count' => $redFlagSales->count(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve red flag sales',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all entry days with present and absent sellers per date
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

            return response()->json([
                'status' => true,
                'message' => 'Entry days retrieved successfully',
                'data' => $entryDays,
                'count' => $entryDays->count(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve entry days',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get seller performance metrics with ranking
     */
    public function getSellerPerformance()
    {
        try {
            // Get all unique dates in the system
            $allDates = Sale::distinct('date')->pluck('date')->toArray();
            $totalDaysInSystem = count($allDates);

            // Get all sellers with their sales data
            $sellers = Seller::all();

            $sellerPerformance = $sellers->map(function ($seller) use ($allDates, $totalDaysInSystem) {
                // Get all sales for this seller
                $sellerSales = Sale::where('seller_id', $seller->id)->get();

                // Calculate total sales amount
                $totalSalesAmount = $sellerSales->sum(function ($sale) {
                    $price = $sale->custom_price ?: $sale->item->price;
                    return ($sale->pick - $sale->returned) * $price;
                });

                // Get unique dates this seller has sales
                $daysWithSales = $sellerSales->pluck('date')->unique()->count();

                // Calculate shares
                $ownerShare = $totalSalesAmount * 0.6;
                $sellerShare = $totalSalesAmount * 0.4;

                // Get dates without sales (absent days)
                $sellerDates = $sellerSales->pluck('date')->unique()->toArray();
                $absentDays = array_diff($allDates, $sellerDates);

                // Calculate performance score
                // Volume Score: normalized against max sales
                $maxSalesAmount = Sale::all()->sum(function ($sale) {
                    $price = $sale->custom_price ?: $sale->item->price;
                    return ($sale->pick - $sale->returned) * $price;
                });
                $volumeScore = $maxSalesAmount > 0 ? ($totalSalesAmount / $maxSalesAmount) * 100 : 0;

                // Consistency Score: days active vs total days
                $consistencyScore = $totalDaysInSystem > 0 ? ($daysWithSales / $totalDaysInSystem) * 100 : 0;

                // Final Performance Score (50% volume, 50% consistency)
                $performanceScore = ($volumeScore * 0.5) + ($consistencyScore * 0.5);

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
                    'volumeScore' => round($volumeScore, 2),
                    'consistencyScore' => round($consistencyScore, 2),
                    'performanceScore' => round($performanceScore, 2),
                ];
            })->sortByDesc('performanceScore')->values();

            return response()->json([
                'status' => true,
                'message' => 'Seller performance data retrieved successfully',
                'data' => $sellerPerformance,
                'count' => $sellerPerformance->count(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve seller performance',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
