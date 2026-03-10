<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\Seller;
use App\Models\Item;
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

            // Calculate earnings
            $ownerEarning = $grandTotal * 0.6;  // 60% to owner
            $sellerEarning = $grandTotal * 0.4;  // 40% to sellers
            $todayOwnerShare = $todayTotal * 0.6;
            $todaySellerShare = $todayTotal * 0.4;
            $yesterdayOwnerShare = $yesterdayTotal * 0.6;
            $yesterdaySellerShare = $yesterdayTotal * 0.4;
            $monthlyOwnerShare = $monthlyTotal * 0.6;
            $monthlySellerShare = $monthlyTotal * 0.4;

            // Red Flag Count - unique combinations of date and seller_id
            $redFlagCount = Sale::where('red_flag', true)
                ->get()
                ->groupBy(['date', 'seller_id'])
                ->count();

            // Counts
            $sellerCount = Seller::count();
            $itemCount = Item::count();
            // Count unique transactions: 1 transaction = 1 seller per date
            $transactionCount = Sale::selectRaw('DISTINCT date, seller_id')->count('seller_id');
            // Count unique days with sales
            $daysWithSales = Sale::distinct('date')->count('date');

            // Calculate top 3 performers
            $allSellers = Seller::all();
            $performanceData = [];

            foreach ($allSellers as $seller) {
                $sellerSales = Sale::where('seller_id', $seller->id)
                    ->with('item')
                    ->get();

                $totalSalesAmount = $sellerSales->sum(function ($sale) {
                    $price = $sale->custom_price ?: $sale->item->price;
                    return ($sale->pick - $sale->returned) * $price;
                });

                $salesDates = $sellerSales->pluck('date')->unique();
                $daysWithSales =count($salesDates);
                $allBusinessDays = Sale::distinct('date')->count('date');
                $absentDays = max(0, $allBusinessDays - $daysWithSales);

                $volumeScore = $allBusinessDays > 0 ? ($daysWithSales / $allBusinessDays) * 100 : 0;
                $consistencyScore = $daysWithSales > 0 ? ($daysWithSales / ($daysWithSales + abs($absentDays))) * 100 : 0;
                $performanceScore = ($volumeScore * 0.5 + $consistencyScore * 0.5);

                if ($totalSalesAmount > 0 || $daysWithSales > 0) {
                    $performanceData[] = [
                        'id' => $seller->id,
                        'name' => $seller->name,
                        'number' => $seller->number,
                        'totalSalesAmount' => $totalSalesAmount,
                        'daysWithSales' => $daysWithSales,
                        'performanceScore' => round($performanceScore, 2),
                    ];
                }
            }

            usort($performanceData, function ($a, $b) {
                return $b['performanceScore'] <=> $a['performanceScore'];
            });

            $topPerformers = array_slice($performanceData, 0, 3);

            return view('admin.dashboard', compact(
                'todayTotal',
                'todayOwnerShare',
                'todaySellerShare',
                'yesterdayTotal',
                'yesterdayOwnerShare',
                'yesterdaySellerShare',
                'monthlyTotal',
                'monthlyOwnerShare',
                'monthlySellerShare',
                'grandTotal',
                'ownerEarning',
                'sellerEarning',
                'redFlagCount',
                'sellerCount',
                'itemCount',
                'transactionCount',
                'daysWithSales',
                'topPerformers'
            ));
        } catch (\Exception $e) {
            return view('admin.dashboard')->with('error', 'Failed to load dashboard data');
        }
    }
}
