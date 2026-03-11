<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Services\DashboardStatisticsService;
use App\Services\SellerPerformanceService;
use App\Repositories\Contracts\SaleRepository;
use App\Repositories\Contracts\SellerRepository;
use App\Repositories\Contracts\ItemRepository;

class DashboardController extends Controller
{
    use ApiResponse;

    /** Get all dashboard statistics (sales, earnings, counts, performers) */
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

            // Format for mobile app (flatten nested earnings)
            $formattedData = [
                'todayTotal' => $stats['todayTotal'],
                'yesterdayTotal' => $stats['yesterdayTotal'],
                'yesterdayOwnerShare' => $stats['yesterdayEarnings']['ownerShare'],
                'yesterdaySellerShare' => $stats['yesterdayEarnings']['sellerShare'],
                'monthlyTotal' => $stats['monthlyTotal'],
                'grandTotal' => $stats['grandTotal'],
                'ownerEarning' => $stats['allTimeEarnings']['ownerShare'],
                'sellerEarning' => $stats['allTimeEarnings']['sellerShare'],
                'redFlagCount' => $stats['redFlagCount'],
                'sellerCount' => $stats['sellerCount'],
                'itemCount' => $stats['itemCount'],
                'transactionCount' => $stats['transactionCount'],
                'daysWithSales' => $stats['daysWithSales'],
            ];

            return $this->success($formattedData, 'Dashboard data retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve dashboard data', 500, $e->getMessage());
        }
    }

    /** Get all red flag sales with seller and item details */
    public function getRedFlagSales(
        SaleRepository $saleRepository,
        SellerRepository $sellerRepository,
        ItemRepository $itemRepository,
    ) {
        try {
            $statisticsService = new DashboardStatisticsService(
                $saleRepository,
                $sellerRepository,
                $itemRepository,
            );

            $redFlagSales = $statisticsService->getRedFlagSalesWithDetails();

            return $this->success(
                $redFlagSales,
                'Red flag sales retrieved successfully',
                200,
                ['count' => count($redFlagSales)]
            );
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve red flag sales', 500, $e->getMessage());
        }
    }

    /** Get entry days with present and absent sellers per date (uses repositories) */
    public function getEntryDays(
        SaleRepository $saleRepository,
        SellerRepository $sellerRepository,
    ) {
        try {
            // Get all unique dates with sales
            $allSales = $saleRepository->getAll();
            $allDates = $allSales->pluck('date')->unique()->values()->toArray();

            // Get all sellers
            $allSellers = $sellerRepository->getAll();

            // Build entry days with present and absent sellers
            $entryDays = collect($allDates)->map(function ($date) use ($saleRepository, $allSellers) {
                // Get sellers with sales on this date (present)
                $salesToday = $saleRepository->getByDate($date);
                $sellerIdsOnDate = $salesToday->pluck('seller_id')->unique()->toArray();

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

    /** Get all sellers ranked by performance score (uses centralized service) */
    public function getSellerPerformance(
        SaleRepository $saleRepository,
        SellerRepository $sellerRepository,
    ) {
        try {
            // Get all seller performance data using centralized service (SINGLE SOURCE OF TRUTH)
            $performanceService = new SellerPerformanceService(
                $saleRepository,
                $sellerRepository,
            );
            $sellerPerformance = $performanceService->calculateAllSellerPerformance();

            return $this->success($sellerPerformance, 'Seller performance data retrieved successfully', 200, [
                'count' => $sellerPerformance->count(),
            ]);
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve seller performance', 500, $e->getMessage());
        }
    }
}
