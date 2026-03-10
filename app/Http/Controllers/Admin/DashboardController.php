<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DashboardStatisticsService;
use App\Repositories\Contracts\SaleRepository;
use App\Repositories\Contracts\SellerRepository;
use App\Repositories\Contracts\ItemRepository;

class DashboardController extends Controller
{
    /** Display admin dashboard with statistics and top performers (uses service + repositories) */
    public function index(
        SaleRepository $saleRepository,
        SellerRepository $sellerRepository,
        ItemRepository $itemRepository,
    ) {
        try {
            // Single service call gets all statistics
            $statisticsService = new DashboardStatisticsService(
                $saleRepository,
                $sellerRepository,
                $itemRepository,
            );

            $stats = $statisticsService->getAllStatistics();

            return view('admin.dashboard', [
                'todayTotal' => $stats['todayTotal'],
                'todayEarnings' => $stats['todayEarnings'],
                'yesterdayTotal' => $stats['yesterdayTotal'],
                'yesterdayEarnings' => $stats['yesterdayEarnings'],
                'monthlyTotal' => $stats['monthlyTotal'],
                'monthlyEarnings' => $stats['monthlyEarnings'],
                'grandTotal' => $stats['grandTotal'],
                'allTimeEarnings' => $stats['allTimeEarnings'],
                'redFlagCount' => $stats['redFlagCount'],
                'sellerCount' => $stats['sellerCount'],
                'itemCount' => $stats['itemCount'],
                'transactionCount' => $stats['transactionCount'],
                'daysWithSales' => $stats['daysWithSales'],
                'topPerformers' => $stats['topPerformers'],
            ]);
        } catch (\Exception $e) {
            return view('admin.dashboard')->with('error', 'Failed to load dashboard data');
        }
    }
}
