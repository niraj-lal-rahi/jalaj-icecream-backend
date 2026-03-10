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
            // Get all unique dates with sales (single query)
            $datesWithSales = $this->saleRepository->getAll()
                ->distinct('date')
                ->orderBy('date', 'desc')
                ->pluck('date');

            // Fetch all sellers once (single query)
            $allSellers = $this->sellerRepository->getAll();

            $entryDays = [];

            foreach ($datesWithSales as $date) {
                // Get sellers who had sales on this date
                $sellersWithSalesOnDate = $this->saleRepository->getByDate($date)
                    ->distinct('seller_id')
                    ->pluck('seller_id')
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
            return redirect()->route('dashboard')->with('error', 'Failed to load entry days: ' . $e->getMessage());
        }
    }
}
