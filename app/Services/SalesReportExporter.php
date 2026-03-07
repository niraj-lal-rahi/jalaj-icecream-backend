<?php

namespace App\Services;

use App\Models\Sale;
use Carbon\Carbon;

class SalesReportExporter
{
    /**
     * Generate CSV content for sales report
     *
     * @param string $date Date in format YYYY-MM-DD (default: today)
     * @return string CSV content
     */
    public function generateCSV(string $date = ''): string
    {
        if (empty($date)) {
            $date = Carbon::now()->format('Y-m-d');
        }

        // Fetch sales for the given date
        $sales = Sale::where('date', $date)
            ->with(['seller', 'item'])
            ->get();

        $csv = "Date,Seller Name,Item Name,Pick,Returned,Net Qty,Price,Total\n";

        if ($sales->isEmpty()) {
            return $csv;
        }

        // Group by seller
        $groupedBySeller = $sales->groupBy('seller_id');

        $grandTotal = 0;
        $grandShare = 0;

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
            $sellerShare = (int)($sellerTotal * 0.6);
            $csv .= sprintf('"%s","%s - TOTAL","","","","","","%d"' . "\n", $date, $sellerName, $sellerTotal);
            $csv .= sprintf('"%s","%s - SHARE (60%%)","","","","","","%d"' . "\n", $date, $sellerName, $sellerShare);

            $grandTotal += $sellerTotal;
            $grandShare += $sellerShare;
        }

        // Add grand total rows
        $csv .= "\n";
        $csv .= sprintf('"","GRAND TOTAL","","","","","","%d"' . "\n", $grandTotal);
        $csv .= sprintf('"","TOTAL SHARE (60%%)","","","","","","%d"' . "\n", $grandShare);

        return $csv;
    }

    /**
     * Generate summary text for the sales report
     *
     * @param string $date Date in format YYYY-MM-DD (default: today)
     * @return string Summary text
     */
    public function generateSummary(string $date = ''): string
    {
        if (empty($date)) {
            $date = Carbon::now()->format('Y-m-d');
        }

        $sales = Sale::where('date', $date)
            ->with(['seller', 'item'])
            ->get();

        $summary = "═══════════════════════════════════════════\n";
        $summary .= "📊 SALES REPORT - " . strtoupper($date) . "\n";
        $summary .= "═══════════════════════════════════════════\n";
        $summary .= "Generated: " . Carbon::now()->format('d-m-Y H:i:s') . "\n\n";

        if ($sales->isEmpty()) {
            $summary .= "No sales data for this date.\n";
            return $summary;
        }

        // Group by seller
        $groupedBySeller = $sales->groupBy('seller_id');
        $grandTotal = 0;
        $grandShare = 0;

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

            $sellerShare = (int)($sellerTotal * 0.6);
            $summary .= "👤 {$sellerName}\n";
            $summary .= "   Items: {$itemCount}\n";
            $summary .= "   Total: ₹" . number_format($sellerTotal) . "\n";
            $summary .= "   Share (60%): ₹" . number_format($sellerShare) . "\n\n";

            $grandTotal += $sellerTotal;
            $grandShare += $sellerShare;
        }

        $summary .= "═══════════════════════════════════════════\n";
        $summary .= "Total Sales: ₹" . number_format($grandTotal) . "\n";
        $summary .= "Total Share (60%): ₹" . number_format($grandShare) . "\n";
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
