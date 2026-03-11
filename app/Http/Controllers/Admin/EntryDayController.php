<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\SaleRepository;
use App\Repositories\Contracts\SellerRepository;

class EntryDayController extends Controller
{
    public function __construct(
        private SaleRepository $saleRepository,
        private SellerRepository $sellerRepository,
    ) {}

    public function index()
    {
        try {
            // Get all sales with relationships
            $allSales = $this->saleRepository->getAll();

            // Group sales by date
            $datesWithSales = $allSales->groupBy('date')
                ->keys()
                ->sort()
                ->reverse();

            // Fetch all sellers once
            $allSellers = $this->sellerRepository->getAll();

            $entryDays = [];

            foreach ($datesWithSales as $date) {
                // Get sellers who had sales on this date
                $sellersWithSalesOnDate = $allSales
                    ->where('date', $date)
                    ->pluck('seller_id')
                    ->unique()
                    ->toArray();

                // Filter in-memory from already-fetched sellers
                $presentSellers = $allSellers->filter(function ($seller) use ($sellersWithSalesOnDate) {
                    return in_array($seller->id, $sellersWithSalesOnDate);
                })->values();

                // Absent sellers
                $absentSellers = $allSellers->filter(function ($seller) use ($sellersWithSalesOnDate) {
                    return !in_array($seller->id, $sellersWithSalesOnDate);
                })->values();

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
            return redirect()->route('admin.dashboard')->with('error', 'Failed to load entry days: ' . $e->getMessage());
        }
    }

    public function show($date)
    {
        try {
            // Get all sales for the specific date
            $sales = $this->saleRepository->getByDate($date);

            if ($sales->isEmpty()) {
                return redirect()->route('admin.entry-days.index')->with('warning', 'No sales found for this date.');
            }

            // Get unique sellers for this date
            $sellersWithSales = $sales->unique('seller_id')->pluck('seller');
            $date = \Carbon\Carbon::parse($date)->format('Y-m-d');

            // Calculate summary stats
            $totalSales = $sales->sum(function ($sale) {
                return ($sale->pick - $sale->returned) * ($sale->custom_price ?: $sale->item->price);
            });

            $totalItems = $sales->sum('pick');
            $totalReturned = $sales->sum('returned');

            return view('admin.entry-days.show', compact('date', 'sales', 'sellersWithSales', 'totalSales', 'totalItems', 'totalReturned'));
        } catch (\Exception $e) {
            return redirect()->route('admin.entry-days.index')->with('error', 'Failed to load sales data: ' . $e->getMessage());
        }
    }
}
