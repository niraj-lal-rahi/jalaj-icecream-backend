<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\Seller;
use App\Models\Item;
use App\Services\SellerPerformanceService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    // === Constants (UPPER_SNAKE_CASE) ===
    // Profit distribution percentages (must sum to 1.0)
    private const OWNER_SHARE_PERCENTAGE = 0.6;  // 60% to owner
    private const SELLER_SHARE_PERCENTAGE = 0.4; // 40% to sellers

    // Top performers display limit
    private const TOP_PERFORMERS_LIMIT = 3;

    /**
     * Display admin dashboard with sales metrics and top performers
     *
     * Shows:
     * - Today's, yesterday's, monthly, and all-time sales totals
     * - Owner and seller earnings breakdown
     * - Red flag transaction count
     * - Seller and item counts
     * - Top N performers by performance score
     *
     * @return \Illuminate\View\View Dashboard view with compact data
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

            // Calculate earnings
            $ownerEarning = $grandTotal * self::OWNER_SHARE_PERCENTAGE;
            $sellerEarning = $grandTotal * self::SELLER_SHARE_PERCENTAGE;
            $todayOwnerShare = $todayTotal * self::OWNER_SHARE_PERCENTAGE;
            $todaySellerShare = $todayTotal * self::SELLER_SHARE_PERCENTAGE;
            $yesterdayOwnerShare = $yesterdayTotal * self::OWNER_SHARE_PERCENTAGE;
            $yesterdaySellerShare = $yesterdayTotal * self::SELLER_SHARE_PERCENTAGE;
            $monthlyOwnerShare = $monthlyTotal * self::OWNER_SHARE_PERCENTAGE;
            $monthlySellerShare = $monthlyTotal * self::SELLER_SHARE_PERCENTAGE;

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

            // Get top 3 performers using cached centralized service (SINGLE SOURCE OF TRUTH)
            // Cache expensive calculation for 1 hour to improve dashboard load time
            $topPerformers = Cache::remember(
                'top_performers',
                now()->addHours(1),
                function () {
                    $performanceService = new SellerPerformanceService();
                    return $performanceService->getTopPerformers(self::TOP_PERFORMERS_LIMIT)->toArray();
                }
            );

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
