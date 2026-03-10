<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Sale;

class RedFlagController extends Controller
{
    public function index()
    {
        try {
            // Fetch all red flag sales with relationships
            $redFlagSales = Sale::where('red_flag', true)
                ->with(['seller', 'item'])
                ->orderBy('date', 'desc')
                ->orderBy('seller_id', 'asc')
                ->get();

            // Group by date
            $groupedByDate = $redFlagSales->groupBy('date');

            return view('admin.red-flags.index', compact('groupedByDate', 'redFlagSales'));
        } catch (\Exception $e) {
            return redirect()->route('dashboard')->with('error', 'Failed to load red flags: ' . $e->getMessage());
        }
    }
}
