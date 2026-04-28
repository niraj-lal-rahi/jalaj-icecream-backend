<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Services\AnalyticsService;

class AnalyticsController extends Controller
{
    use ApiResponse;

    protected AnalyticsService $analyticsService;

    public function __construct(AnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    /**
     * Get monthly sales trend (last 12 months)
     */
    public function getMonthlySales()
    {
        try {
            $startDate = request('start_date');
            $endDate = request('end_date');
            $data = $this->analyticsService->getMonthlySalesTrend($startDate, $endDate);
            return $this->success($data, 'Monthly sales trend retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve monthly sales', 500, $e->getMessage());
        }
    }

    /**
     * Get top sellers by total sales
     */
    public function getTopSellers()
    {
        try {
            $startDate = request('start_date');
            $endDate = request('end_date');
            $data = $this->analyticsService->getTopSellersByTotal(10, $startDate, $endDate);
            return $this->success($data, 'Top sellers retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve top sellers', 500, $e->getMessage());
        }
    }

    /**
     * Get top performers based on performance score
     */
    public function getTopSellersByAvgSale(\App\Services\SellerPerformanceService $performanceService)
    {
        try {
            $startDate = request('start_date');
            $endDate = request('end_date');
            
            if ($startDate && $endDate) {
                $performanceService->setGlobalDateFilter(\Carbon\Carbon::parse($startDate)->startOfDay(), \Carbon\Carbon::parse($endDate)->endOfDay());
            }
            
            $performers = $performanceService->getTopPerformers(10);
            
            $data = [
                'labels' => $performers->pluck('name')->toArray(),
                'data' => $performers->pluck('performanceScore')->toArray(),
                'sellers' => $performers->map(function ($p) {
                    return [
                        'total_sales' => $p['totalSalesAmount'],
                        'transactions' => $p['daysWithSales'],
                    ];
                })->toArray(),
            ];
            
            return $this->success($data, 'Top performers retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve top performers', 500, $e->getMessage());
        }
    }

    /**
     * Get item popularity
     */
    public function getItemPopularity()
    {
        try {
            $startDate = request('start_date');
            $endDate = request('end_date');
            $data = $this->analyticsService->getItemPopularity(8, $startDate, $endDate);
            return $this->success($data, 'Item popularity retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve item popularity', 500, $e->getMessage());
        }
    }

    /**
     * Get daily sales for a specific month
     */
    public function getDailySales()
    {
        try {
            $month = request('month');
            if (!$month) {
                return $this->error('Month parameter required (format: YYYY-MM)', 400);
            }

            $data = $this->analyticsService->getDailySalesByMonth($month);
            return $this->success($data, 'Daily sales retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve daily sales', 500, $e->getMessage());
        }
    }

    /**
     * Get sales by day of week
     */
    public function getSalesByDayOfWeek()
    {
        try {
            $startDate = request('start_date');
            $endDate = request('end_date');
            $data = $this->analyticsService->getSalesByDayOfWeek($startDate, $endDate);
            return $this->success($data, 'Day of week sales retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve day of week sales', 500, $e->getMessage());
        }
    }

    /**
     * Get dashboard summary
     */
    public function getDashboardSummary()
    {
        try {
            $data = $this->analyticsService->getDashboardSummary();
            return $this->success($data, 'Dashboard summary retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve dashboard summary', 500, $e->getMessage());
        }
    }
}
