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
        \Illuminate\Http\Request $request,
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

            $startDate = $request->query('start_date');
            $endDate = $request->query('end_date');

            $stats = $statisticsService->getAllStatistics($startDate, $endDate);

            return view('admin.dashboard', [
                'startDate' => $startDate,
                'endDate' => $endDate,
                'isFiltered' => ($startDate && $endDate),
                'todayTotal' => $stats['todayTotal'],
                'todayOwnerShare' => $stats['todayEarnings']['ownerShare'],
                'todaySellerShare' => $stats['todayEarnings']['sellerShare'],
                'yesterdayTotal' => $stats['yesterdayTotal'],
                'yesterdayOwnerShare' => $stats['yesterdayEarnings']['ownerShare'],
                'yesterdaySellerShare' => $stats['yesterdayEarnings']['sellerShare'],
                'monthlyTotal' => $stats['monthlyTotal'],
                'monthlyOwnerShare' => $stats['monthlyEarnings']['ownerShare'],
                'monthlySellerShare' => $stats['monthlyEarnings']['sellerShare'],
                'grandTotal' => $stats['grandTotal'],
                'ownerEarning' => $stats['allTimeEarnings']['ownerShare'],
                'sellerEarning' => $stats['allTimeEarnings']['sellerShare'],
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