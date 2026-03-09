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

            // Today's Total Sales
            $todaySales = Sale::whereDate('date', $today)->get();
            $todayTotal = $todaySales->sum(function ($sale) {
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

            // Red Flag Count
            $redFlagCount = Sale::where('red_flag', true)->count();

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

            return response()->json([
                'status' => true,
                'message' => 'Dashboard data retrieved successfully',
                'data' => [
                    'todayTotal' => $todayTotal,
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
}
