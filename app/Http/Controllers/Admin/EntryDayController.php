<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\Seller;
use Carbon\Carbon;

class EntryDayController extends Controller
{
    public function index()
    {
        try {
            // Get all unique dates with sales
            $datesWithSales = Sale::distinct('date')
                ->orderBy('date', 'desc')
                ->pluck('date');

            $entryDays = [];

            foreach ($datesWithSales as $date) {
                // Get all sellers
                $allSellers = Seller::all();

                // Get sellers who had sales on this date
                $sellersWithSalesOnDate = Sale::whereDate('date', $date)
                    ->distinct('seller_id')
                    ->pluck('seller_id')
                    ->toArray();

                // Present sellers
                $presentSellers = Seller::whereIn('id', $sellersWithSalesOnDate)
                    ->get(['id', 'name', 'number']);

                // Absent sellers
                $absentSellers = Seller::whereNotIn('id', $sellersWithSalesOnDate)
                    ->get(['id', 'name', 'number']);

                $entryDays[] = [
                    'date' => $date,
                    'presentCount' => $presentSellers->count(),
                    'absentCount' => $absentSellers->count(),
                    'presentSellers' => $presentSellers,
                    'absentSellers' => $absentSellers,
                ];
            }

            return view('admin.entry-days.index', compact('entryDays'));
        } catch (\Exception $e) {
            return redirect()->route('dashboard')->with('error', 'Failed to load entry days: ' . $e->getMessage());
        }
    }
}
