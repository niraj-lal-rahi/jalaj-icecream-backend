<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\Seller;
use Carbon\Carbon;

class SellerPerformanceController extends Controller
{
    public function index()
    {
        try {
            $sellers = Seller::all();
            $allSales = Sale::with('item')->get();
            $today = Carbon::today();

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

                // Get all business days (days with any sales)
                $allBusinessDays = $allSales->pluck('date')->unique()->count();
                $absentDays = max(0, $allBusinessDays - $daysWithSales);
                $totalDays = $allBusinessDays;

                // Volume score (% of business days seller participated)
                $volumeScore = $totalDays > 0 ? ($daysWithSales / $totalDays) * 100 : 0;

                // Consistency score (consistency in appearing on available days)
                $consistencyScore = $daysWithSales > 0 ? ($daysWithSales / ($daysWithSales + abs($absentDays))) * 100 : 0;

                // Performance score (weighted average)
                $performanceScore = ($volumeScore * 0.5 + $consistencyScore * 0.5);

                $performanceData[] = [
                    'id' => $seller->id,
                    'name' => $seller->name,
                    'number' => $seller->number,
                    'totalSalesAmount' => $totalSalesAmount,
                    'ownerShare' => $totalSalesAmount * 0.6,
                    'sellerShare' => $totalSalesAmount * 0.4,
                    'daysWithSales' => $daysWithSales,
                    'absentDays' => $absentDays,
                    'totalDays' => $totalDays,
                    'presentDates' => $presentDates,
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
