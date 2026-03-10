<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\Seller;

class SellerPerformanceController extends Controller
{
    public function index()
    {
        try {
            // Get all unique dates in the system
            $allDates = Sale::distinct('date')->pluck('date')->toArray();
            $totalDaysInSystem = count($allDates);

            // Get all sellers with their sales data
            $sellers = Seller::all();
            $allSales = Sale::with('item')->get();

            // Calculate max sales amount for volume score normalization
            $maxSalesAmount = $allSales->sum(function ($sale) {
                $price = $sale->custom_price ?: $sale->item->price;
                return ($sale->pick - $sale->returned) * $price;
            });

            $performanceData = [];

            foreach ($sellers as $seller) {
                $sellerSales = $allSales->filter(function ($sale) use ($seller) {
                    return $sale->seller_id === $seller->id;
                });

                // Calculate sales
                $totalSalesAmount = $sellerSales->sum(function ($sale) {
                    $price = $sale->custom_price ?: $sale->item->price;
                    return ($sale->pick - $sale->returned) * $price;
                });

                // Get dates
                $salesDates = $sellerSales->pluck('date')->unique()->sort();
                $presentDates = $salesDates->values()->all();
                $daysWithSales = count($presentDates);

                // Volume Score: normalized against max sales (MATCHING API)
                $volumeScore = $maxSalesAmount > 0 ? ($totalSalesAmount / $maxSalesAmount) * 100 : 0;

                // Consistency Score: days active vs total days (MATCHING API)
                $consistencyScore = $totalDaysInSystem > 0 ? ($daysWithSales / $totalDaysInSystem) * 100 : 0;

                // Final Performance Score (50% volume, 50% consistency)
                $performanceScore = ($volumeScore * 0.5) + ($consistencyScore * 0.5);

                // Get absent days (all dates where seller had no sales)
                $sellerDates = $salesDates->toArray();
                $absentDays = array_diff($allDates, $sellerDates);

                $performanceData[] = [
                    'id' => $seller->id,
                    'name' => $seller->name,
                    'number' => $seller->number,
                    'totalSalesAmount' => $totalSalesAmount,
                    'ownerShare' => $totalSalesAmount * 0.6,
                    'sellerShare' => $totalSalesAmount * 0.4,
                    'daysWithSales' => $daysWithSales,
                    'absentDays' => count($absentDays),
                    'totalDays' => $totalDaysInSystem,
                    'presentDates' => $presentDates,
                    'absentDates' => array_values($absentDays),
                    'volumeScore' => round($volumeScore, 2),
                    'consistencyScore' => round($consistencyScore, 2),
                    'performanceScore' => round($performanceScore, 2),
                ];
            }

            // Sort by performance score descending
            usort($performanceData, function ($a, $b) {
                return $b['performanceScore'] <=> $a['performanceScore'];
            });

            return view('admin.seller-performance.index', compact('performanceData'));
        } catch (\Exception $e) {
            return redirect()->route('dashboard')->with('error', 'Failed to load seller performance: ' . $e->getMessage());
        }
    }
}
