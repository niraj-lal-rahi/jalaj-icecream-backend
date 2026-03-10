<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\SaleRepository;
use App\Repositories\Contracts\SellerRepository;
use App\Services\SellerPerformanceService;

class SellerPerformanceController extends Controller
{
    public function __construct(
        private SaleRepository $saleRepository,
        private SellerRepository $sellerRepository,
    ) {}

    public function index()
    {
        try {
            // Get all seller performance data using centralized service (SINGLE SOURCE OF TRUTH)
            $performanceService = new SellerPerformanceService($this->saleRepository, $this->sellerRepository);
            $performanceData = $performanceService->calculateAllSellerPerformance()->toArray();

            return view('admin.seller-performance.index', compact('performanceData'));
        } catch (\Exception $e) {
            return redirect()->route('dashboard')->with('error', 'Failed to load seller performance: ' . $e->getMessage());
        }
    }
}
