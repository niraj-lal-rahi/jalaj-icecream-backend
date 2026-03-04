<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use Carbon\Carbon;

class DashboardController extends Controller
{
    // public function index()
    // {
    //     $today = Carbon::today();

    //     // Today Sales
    //     $todaySales = Sale::whereDate('date', $today)->get();

    //     $todayTotal = $todaySales->sum(function ($sale) {
    //         $price = $sale->custom_price ?: $sale->item->price;

    //         return ($sale->pick - $sale->returned) * $price;
    //     });

    //     // Monthly Sales
    //     $monthlySales = Sale::whereMonth('date', now()->month)
    //         ->whereYear('date', now()->year)
    //         ->get();

    //     $monthlyTotal = $monthlySales->sum(function ($sale) {
    //         $price = $sale->custom_price ?: $sale->item->price;

    //         return ($sale->pick - $sale->returned) * $price;
    //     });

    //     // Overall Sales
    //     $allSales = Sale::with('item')->get();

    //     $grandTotal = $allSales->sum(function ($sale) {
    //         $price = $sale->custom_price ?: $sale->item->price;

    //         return ($sale->pick - $sale->returned) * $price;
    //     });

    //     // Red Flag Count
    //     $redFlags = Sale::where('red_flag', true)->count();

    //     return view('admin.dashboard', compact(
    //         'todayTotal',
    //         'monthlyTotal',
    //         'grandTotal',
    //         'redFlags'
    //     ));
    // }

    public function index()
    {
        $today = Carbon::today();

        // Today Sales
        $todayTotal = Sale::whereDate('date', $today)
            ->get()
            ->sum(function ($sale) {
                $price = $sale->custom_price ?: $sale->item->price;

                return ($sale->pick - $sale->returned) * $price;
            });

        // Monthly Sales
        $monthlyTotal = Sale::whereMonth('date', now()->month)
            ->whereYear('date', now()->year)
            ->get()
            ->sum(function ($sale) {
                $price = $sale->custom_price ?: $sale->item->price;

                return ($sale->pick - $sale->returned) * $price;
            });

        // Grand Total
        $grandTotal = Sale::with('item')->get()
            ->sum(function ($sale) {
                $price = $sale->custom_price ?: $sale->item->price;

                return ($sale->pick - $sale->returned) * $price;
            });

        // Red Flags
        $redFlags = Sale::where('red_flag', 1)->count();

        return view('admin.dashboard', compact(
            'todayTotal',
            'monthlyTotal',
            'grandTotal',
            'redFlags'
        ));
    }
}
