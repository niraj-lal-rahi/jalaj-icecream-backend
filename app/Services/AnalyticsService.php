<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\Seller;
use App\Models\Item;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AnalyticsService
{
    /**
     * Get monthly sales trend (last 12 months)
     * OR if filtered, get daily sales trend for that period
     */
    public function getMonthlySalesTrend($startDate = null, $endDate = null)
    {
        if ($startDate && $endDate) {
            $start = Carbon::parse($startDate)->startOfDay();
            $end = Carbon::parse($endDate)->endOfDay();
            $sales = Sale::with(['item'])
                ->whereBetween('date', [$start, $end])
                ->get()
                ->groupBy(function($sale) {
                    return Carbon::parse($sale->date)->format('Y-m-d');
                })
                ->map(function ($sales) {
                    $total = 0;
                    foreach ($sales as $sale) {
                        if ($sale->item) {
                            $price = $sale->custom_price ?: $sale->item->price;
                            $total += ($sale->pick - $sale->returned) * $price;
                        }
                    }
                    return $total;
                });

            $labels = [];
            $data = [];
            $months = [];
            $currentDate = $start->copy();
            while ($currentDate <= $end) {
                $dateStr = $currentDate->format('Y-m-d');
                $labels[] = $currentDate->format('M d');
                $data[] = $sales->get($dateStr, 0);
                $months[] = [ 'key' => $currentDate->format('Y-m'), 'month' => $currentDate->format('F Y') ];
                $currentDate->addDay();
            }
            return [ 'labels' => $labels, 'data' => $data, 'months' => $months ];
        }

        $months = collect();
        $today = Carbon::now();

        // Generate last 12 months
        for ($i = 11; $i >= 0; $i--) {
            $date = $today->copy()->subMonths($i);
            $monthKey = $date->format('Y-m');
            $monthLabel = $date->format('M Y');

            $total = 0;
            $sales = Sale::with(['item', 'seller'])
                ->whereYear('date', $date->year)
                ->whereMonth('date', $date->month)
                ->get();

            foreach ($sales as $sale) {
                if ($sale->item) {
                    $price = $sale->custom_price ?: $sale->item->price;
                    $total += ($sale->pick - $sale->returned) * $price;
                }
            }

            $months->push([
                'month' => $monthLabel,
                'total' => $total,
                'key' => $monthKey,
            ]);
        }

        return [
            'labels' => $months->pluck('month')->toArray(),
            'data' => $months->pluck('total')->toArray(),
            'months' => $months->toArray(),
        ];
    }

    /**
     * Get top sellers by total sales amount
     * @param int $limit
     * @param string|null $startDate
     * @param string|null $endDate
     * @return array
     */
    public function getTopSellersByTotal($limit = 10, $startDate = null, $endDate = null)
    {
        $sellers = Seller::with(['sales' => function($q) use ($startDate, $endDate) {
            if ($startDate && $endDate) {
                $q->whereBetween('date', [$startDate, $endDate]);
            }
            $q->with('item');
        }])
            ->get()
            ->map(function ($seller) {
                $totalSales = 0;
                foreach ($seller->sales as $sale) {
                    $price = $sale->custom_price ?: ($sale->item ? $sale->item->price : 0);
                    $totalSales += ($sale->pick - $sale->returned) * $price;
                }

                return [
                    'id' => $seller->id,
                    'name' => $seller->name,
                    'number' => $seller->number,
                    'total_sales' => $totalSales,
                    'transactions' => $seller->sales->count(),
                ];
            })
            ->sortByDesc('total_sales')
            ->take($limit)
            ->values();

        $maxSales = $sellers->pluck('total_sales')->max();

        return [
            'labels' => $sellers->pluck('name')->toArray(),
            'data' => $sellers->pluck('total_sales')->toArray(),
            'transactions' => $sellers->pluck('transactions')->toArray(),
            'sellers' => $sellers->toArray(),
            'maxSales' => $maxSales,
        ];
    }

    /**
     * Get top sellers by average sale amount
     * @param int $limit
     * @param int $minTransactions
     * @param string|null $startDate
     * @param string|null $endDate
     * @return array
     */
    public function getTopSellersByAvgSale($limit = 10, $minTransactions = 3, $startDate = null, $endDate = null)
    {
        $sellers = Seller::with(['sales' => function($q) use ($startDate, $endDate) {
            if ($startDate && $endDate) {
                $q->whereBetween('date', [$startDate, $endDate]);
            }
            $q->with('item');
        }])
            ->get()
            ->map(function ($seller) {
                $sales = $seller->sales;
                $totalTransactions = $sales->count();

                if ($totalTransactions < 1) {
                    return null;
                }

                $totalSalesAmount = 0;
                foreach ($sales as $sale) {
                    $price = $sale->custom_price ?: ($sale->item ? $sale->item->price : 0);
                    $totalSalesAmount += ($sale->pick - $sale->returned) * $price;
                }

                $avgSale = $totalSalesAmount / $totalTransactions;

                return [
                    'id' => $seller->id,
                    'name' => $seller->name,
                    'number' => $seller->number,
                    'avg_sale' => $avgSale,
                    'total_sales' => $totalSalesAmount,
                    'transactions' => $totalTransactions,
                ];
            })
            ->filter(function ($seller) use ($minTransactions) {
                return $seller !== null && $seller['transactions'] >= $minTransactions;
            })
            ->sortByDesc('avg_sale')
            ->take($limit)
            ->values();

        $maxAvg = $sellers->pluck('avg_sale')->max();

        return [
            'labels' => $sellers->pluck('name')->toArray(),
            'data' => $sellers->pluck('avg_sale')->toArray(),
            'sellers' => $sellers->toArray(),
            'maxAvg' => $maxAvg,
        ];
    }

    /**
     * Get item popularity by quantity sold
     * @param int $limit
     * @param string|null $startDate
     * @param string|null $endDate
     * @return array
     */
    public function getItemPopularity($limit = 8, $startDate = null, $endDate = null)
    {
        try {
            $items = Item::with(['sales' => function($q) use ($startDate, $endDate) {
                if ($startDate && $endDate) {
                    $q->whereBetween('date', [$startDate, $endDate]);
                }
            }])
                ->get()
                ->map(function ($item) {
                    $qtySold = 0;
                    $revenue = 0;

                    if (!$item->sales || $item->sales->isEmpty()) {
                        return null;
                    }

                    foreach ($item->sales as $sale) {
                        $qty = max(0, $sale->pick - $sale->returned);
                        $qtySold += $qty;

                        $price = $sale->custom_price ?: $item->price;
                        $revenue += $qty * $price;
                    }

                    if ($qtySold == 0) {
                        return null;
                    }

                    return [
                        'id' => $item->id,
                        'name' => $item->name ?? 'Unknown Item',
                        'qty_sold' => $qtySold,
                        'revenue' => $revenue,
                    ];
                })
                ->filter(function ($item) {
                    return $item !== null;
                })
                ->sortByDesc('qty_sold')
                ->take($limit)
                ->values();

            $colors = [
                '#FF6384',
                '#36A2EB',
                '#FFCE56',
                '#4BC0C0',
                '#9966FF',
                '#FF9F40',
                '#FF6384',
                '#C9CBCF',
            ];

            return [
                'labels' => $items->pluck('name')->toArray(),
                'data' => $items->pluck('qty_sold')->toArray(),
                'revenue' => $items->pluck('revenue')->toArray(),
                'items' => $items->toArray(),
                'colors' => array_slice($colors, 0, max(1, count($items))),
            ];
        } catch (\Exception $e) {
            \Log::error('Error in getItemPopularity: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get daily sales breakdown for a specific month
     * @param string $monthKey (format: YYYY-MM)
     * @return array
     */
    public function getDailySalesByMonth($monthKey)
    {
        $dates = collect();

        try {
            [$year, $month] = explode('-', $monthKey);
            $year = (int) $year;
            $month = (int) $month;
        } catch (\Exception $e) {
            return ['labels' => [], 'data' => []];
        }

        $startDate = Carbon::createFromDate($year, $month, 1)->startOfDay();
        $endDate = $startDate->copy()->endOfMonth()->endOfDay();

        // Get all dates in the month that have sales
        $salesByDate = Sale::with(['item', 'seller'])
            ->whereBetween('date', [$startDate, $endDate])
            ->get()
            ->groupBy(function($sale) {
                return Carbon::parse($sale->date)->format('Y-m-d');
            })
            ->map(function ($sales) {
                $total = 0;
                foreach ($sales as $sale) {
                    if ($sale->item) {
                        $price = $sale->custom_price ?: $sale->item->price;
                        $total += ($sale->pick - $sale->returned) * $price;
                    }
                }
                return $total;
            });

        // Create array for all dates in month
        $currentDate = $startDate->copy()->startOfDay();
        while ($currentDate <= $endDate) {
            $dateStr = $currentDate->format('Y-m-d');
            $displayDate = $currentDate->format('d M');

            $total = $salesByDate->get($dateStr, 0);

            $dates->push([
                'date' => $dateStr,
                'label' => $displayDate,
                'total' => $total,
            ]);

            $currentDate->addDay();
        }

        return [
            'labels' => $dates->pluck('label')->toArray(),
            'data' => $dates->pluck('total')->toArray(),
            'dates' => $dates->toArray(),
        ];
    }

    /**
 * Get sales summary stats
 * @return array
 */
public function getSalesSummary()
{
        $today = Carbon::now();
        $thisMonth = $today->copy()->startOfMonth();
        $lastMonth = $today->copy()->subMonth()->startOfMonth();

        // This month sales
        $thisMonthTotal = Sale::with('item')
            ->whereDate('date', '>=', $thisMonth)
            ->get()
            ->sum(function ($sale) {
                $price = $sale->custom_price ?: $sale->item->price;
                return ($sale->pick - $sale->returned) * $price;
            });

        // Last month sales
        $lastMonthTotal = Sale::with('item')
            ->whereBetween('date', [$lastMonth, $lastMonth->copy()->endOfMonth()])
            ->get()
            ->sum(function ($sale) {
                $price = $sale->custom_price ?: $sale->item->price;
                return ($sale->pick - $sale->returned) * $price;
            });

        // Growth percentage
        $growth = $lastMonthTotal > 0 ? (($thisMonthTotal - $lastMonthTotal) / $lastMonthTotal) * 100 : 0;

        // Average sale
        $allSales = Sale::with('item')->get();
        $totalSalesAmount = $allSales->sum(function ($sale) {
            $price = $sale->custom_price ?: $sale->item->price;
            return ($sale->pick - $sale->returned) * $price;
        });
        $avgSale = $allSales->count() > 0 ? $totalSalesAmount / $allSales->count() : 0;

        return [
        'this_month_total' => $thisMonthTotal,
        'last_month_total' => $lastMonthTotal,
        'growth_percentage' => round($growth, 2),
        'avg_sale' => round($avgSale, 2),
        'total_transactions' => $allSales->count(),
        'active_sellers' => Seller::has('sales')->count(),
    ];
}

    /**
     * Get sales breakdown by day of the week
     * @param string|null $startDate
     * @param string|null $endDate
     * @return array
     */
    public function getSalesByDayOfWeek($startDate = null, $endDate = null)
    {
        $query = Sale::with('item');
        
        if ($startDate && $endDate) {
            $query->whereBetween('date', [$startDate, $endDate]);
        }

        $sales = $query->get();
        // Array to hold sums for days: 1 (Mon) to 7 (Sun)
        $days = [
            1 => ['label' => 'Monday', 'total' => 0],
            2 => ['label' => 'Tuesday', 'total' => 0],
            3 => ['label' => 'Wednesday', 'total' => 0],
            4 => ['label' => 'Thursday', 'total' => 0],
            5 => ['label' => 'Friday', 'total' => 0],
            6 => ['label' => 'Saturday', 'total' => 0],
            7 => ['label' => 'Sunday', 'total' => 0],
        ];

        foreach ($sales as $sale) {
            if ($sale->item) {
                $dayOfWeek = Carbon::parse($sale->date)->dayOfWeekIso; // 1 = Monday, 7 = Sunday
                $price = $sale->custom_price ?: $sale->item->price;
                $total = ($sale->pick - $sale->returned) * $price;
                
                $days[$dayOfWeek]['total'] += $total;
            }
        }

        // Return sorted arrays for chart usage
        return [
            'labels' => array_column($days, 'label'),
            'data'   => array_column($days, 'total'),
        ];
    }
}