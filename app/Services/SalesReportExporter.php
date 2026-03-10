<?php

namespace App\Services;

use App\Models\Sale;
use App\Repositories\Contracts\SaleRepository;
use Carbon\Carbon;

/**
 * SalesReportExporter
 *
 * Generates and exports sales reports in multiple formats (CSV, Summary text).
 *
 * Key Responsibilities:
 * - Generate CSV sales reports for a specific date
 * - Generate text summary reports for a specific date
 * - Export reports to file storage
 * - Format sales data including profit calculations
 *
 * Architecture:
 * - Uses SaleRepository for all database queries (never direct model calls)
 * - All profit sharing percentages loaded from config/profit.php
 * - Reports include seller-wise breakdown and grand totals
 *
 * Dependency Injection:
 * - Accepts SaleRepository via constructor
 * - Loaded via AppServiceProvider dependency bindings
 *
 * Configuration:
 * - config/profit.php: owner_share, seller_share (profit split percentages)
 *
 * Usage in Controllers:
 * $exporter = app(SalesReportExporter::class);
 * $csv = $exporter->generateCSV('2024-01-15');
 * $filePath = $exporter->exportToFile('2024-01-15');
 */
class SalesReportExporter
{
    /** SaleRepository for all sale queries (never use Sale::query() directly) */
    private SaleRepository $saleRepository;

    /** Constructor - accepts SaleRepository via DI (bound in AppServiceProvider) */
    public function __construct(SaleRepository $saleRepository)
    {
        $this->saleRepository = $saleRepository;
    }
    /** Generate CSV sales report for a specific date (uses repository, dynamic config) */
    public function generateCSV(string $date = ''): string
    {
        if (empty($date)) {
            $date = Carbon::now()->format('Y-m-d');
        }

        // Fetch sales for the given date using repository (not direct model call)
        $sales = $this->saleRepository->getByDate($date);

        $csv = "Date,Seller Name,Item Name,Pick,Returned,Net Qty,Price,Total\n";

        if ($sales->isEmpty()) {
            return $csv;
        }

        // Group by seller for summary calculations
        $groupedBySeller = $sales->groupBy('seller_id');

        $grandTotal = 0;
        $grandShare = 0;
        $ownerSharePercentage = config('profit.owner_share');

        foreach ($groupedBySeller as $sellerId => $sellerSales) {
            $sellerName = $sellerSales->first()->seller->name ?? 'Unknown Seller';
            $sellerTotal = 0;

            // Add individual sale rows
            foreach ($sellerSales as $sale) {
                $netQty = $sale->pick - ($sale->returned ?? 0);
                $price = $sale->custom_price ?? $sale->item->price ?? 0;
                $itemTotal = $netQty * $price;
                $sellerTotal += $itemTotal;

                // Escape quotes in item name for CSV
                $itemName = str_replace('"', '""', $sale->item->name ?? 'Unknown');

                $csv .= sprintf(
                    '"%s","%s","%s",%d,%d,%d,%d,%d' . "\n",
                    $date,
                    $sellerName,
                    $itemName,
                    $sale->pick,
                    $sale->returned ?? 0,
                    $netQty,
                    $price,
                    $itemTotal
                );
            }

            // Add seller summary rows
            // Owner share calculation using config value (not hardcoded 0.6)
            $ownerShare = (int)($sellerTotal * $ownerSharePercentage);
            $sharePercentage = (int)($ownerSharePercentage * 100);

            $csv .= sprintf('"%s","%s - TOTAL","","","","","","%d"' . "\n", $date, $sellerName, $sellerTotal);
            $csv .= sprintf('"%s","%s - SHARE (%d%%)","","","","","","%d"' . "\n", $date, $sellerName, $sharePercentage, $ownerShare);

            $grandTotal += $sellerTotal;
            $grandShare += $ownerShare;
        }

        // Add grand total rows
        $csv .= "\n";
        $csv .= sprintf('"","GRAND TOTAL","","","","","","%d"' . "\n", $grandTotal);
        $sharePercentage = (int)(config('profit.owner_share') * 100);
        $csv .= sprintf('"","TOTAL SHARE (%d%%)","","","","","","%d"' . "\n", $sharePercentage, $grandShare);

        return $csv;
    }

    /** Generate text summary report for a specific date (uses repository, dynamic config) */
    public function generateSummary(string $date = ''): string
    {
        if (empty($date)) {
            $date = Carbon::now()->format('Y-m-d');
        }

        // Fetch sales for the given date using repository (not direct model call)
        $sales = $this->saleRepository->getByDate($date);

        $summary = "═══════════════════════════════════════════\n";
        $summary .= "📊 SALES REPORT - " . strtoupper($date) . "\n";
        $summary .= "═══════════════════════════════════════════\n";
        $summary .= "Generated: " . Carbon::now()->format('d-m-Y H:i:s') . "\n\n";

        if ($sales->isEmpty()) {
            $summary .= "No sales data for this date.\n";
            return $summary;
        }

        // Group by seller for itemized breakdown
        $groupedBySeller = $sales->groupBy('seller_id');
        $grandTotal = 0;
        $grandShare = 0;
        $ownerSharePercentage = config('profit.owner_share');
        $sharePercentageLabel = (int)($ownerSharePercentage * 100) . '%';

        foreach ($groupedBySeller as $sellerId => $sellerSales) {
            $sellerName = $sellerSales->first()->seller->name ?? 'Unknown Seller';
            $sellerTotal = 0;
            $itemCount = 0;

            foreach ($sellerSales as $sale) {
                $netQty = $sale->pick - ($sale->returned ?? 0);
                $price = $sale->custom_price ?? $sale->item->price ?? 0;
                $itemTotal = $netQty * $price;
                $sellerTotal += $itemTotal;
                $itemCount++;
            }

            // Calculate owner share using config value (not hardcoded 0.6)
            $ownerShare = (int)($sellerTotal * $ownerSharePercentage);
            $summary .= "👤 {$sellerName}\n";
            $summary .= "   Items: {$itemCount}\n";
            $summary .= "   Total: ₹" . number_format($sellerTotal) . "\n";
            $summary .= "   Share ({$sharePercentageLabel}): ₹" . number_format($ownerShare) . "\n\n";

            $grandTotal += $sellerTotal;
            $grandShare += $ownerShare;
        }

        $summary .= "═══════════════════════════════════════════\n";
        $summary .= "Total Sales: ₹" . number_format($grandTotal) . "\n";
        $summary .= "Total Share ({$sharePercentageLabel}): ₹" . number_format($grandShare) . "\n";
        $summary .= "═══════════════════════════════════════════\n";

        return $summary;
    }

    /**
     * Save CSV to file
     *
     * @param string $date Date for report
     * @return string File path
     */
    public function exportToFile(string $date = ''): string
    {
        if (empty($date)) {
            $date = Carbon::now()->format('Y-m-d');
        }

        $csvContent = $this->generateCSV($date);
        $fileName = "sales_report_" . $date . ".csv";
        $filePath = storage_path("reports/{$fileName}");

        // Create directory if it doesn't exist
        if (!is_dir(dirname($filePath))) {
            mkdir(dirname($filePath), 0755, true);
        }

        file_put_contents($filePath, $csvContent);

        return $filePath;
    }
}
