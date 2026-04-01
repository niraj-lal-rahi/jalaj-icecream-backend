<?php

namespace App\Services;

use App\Models\Sale;
use App\Repositories\Contracts\SaleRepository;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * SalesReportExporter
 *
 * Generates and exports sales reports in multiple formats (XLSX, CSV, Summary text).
 * Reports are formatted to match the yearly sales report structure.
 *
 * Key Responsibilities:
 * - Generate XLSX sales reports for a specific date (primary format)
 * - Generate CSV sales reports for a specific date
 * - Generate text summary reports for a specific date
 * - Export reports to file storage
 * - Format sales data with item prices and profit calculations (40% salary, 60% share)
 *
 * Report Format (matches annual Excel export):
 * - Lists each sale with item name including price in parentheses
 * - Calculates net quantity (Pick - Returned) × Price
 * - Groups sales by seller with totals
 * - Shows Salary (40%) and Share (60%) for each seller
 * - Includes grand total row with overall calculations
 * - Color coding for red flag items
 *
 * Architecture:
 * - Uses SaleRepository for all database queries (never direct model calls)
 * - Fixed profit split: 40% Salary, 60% Share (matches exportYearlySales method)
 * - All formatting done in SalesReportExporter service
 * - Uses PhpOffice\PhpSpreadsheet for XLSX generation
 *
 * Dependency Injection:
 * - Accepts SaleRepository via constructor
 * - Loaded via AppServiceProvider dependency bindings
 *
 * Usage in Controllers:
 * $exporter = app(SalesReportExporter::class);
 * $filePath = $exporter->exportToXlsxFile('2024-01-15');
 * $summary = $exporter->generateSummary('2024-01-15');
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

    /**
     * Generate XLSX sales report for a specific date (matches yearly report format)
     * Returns the Spreadsheet object
     *
     * @param string $date Date for report (YYYY-MM-DD)
     * @return Spreadsheet
     */
    public function generateXlsx(string $date = ''): Spreadsheet
    {
        if (empty($date)) {
            $date = Carbon::now()->format('Y-m-d');
        }

        // Fetch sales for the given date using repository
        $sales = $this->saleRepository->getByDate($date);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle($date);

        // Set headers (matching SaleController format)
        $headers = [
            'C1' => 'Seller Name',
            'D1' => 'Item Name',
            'F1' => 'Pick',
            'G1' => 'Returned',
            'H1' => 'Total',
            'I1' => 'Sum',
            'J1' => 'Salary (40%)',
            'K1' => 'Share (60%)',
        ];

        foreach ($headers as $cell => $value) {
            $sheet->setCellValue($cell, $value);
        }

        // Style headers
        $sheet->getStyle('C1:K1')->getFont()->setBold(true);
        $sheet->getStyle('C1:K1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        if ($sales->isEmpty()) {
            // Add empty state message
            $sheet->setCellValue('C2', 'No sales data for this date');
            return $spreadsheet;
        }

        // Group by seller
        $groupedBySeller = $sales->groupBy('seller_id');
        $rowNumber = 2;
        $overallSum = 0;

        foreach ($groupedBySeller as $sellerId => $sellerSales) {
            $sellerName = $sellerSales->first()->seller->name ?? 'Unknown Seller';
            $sellerTotal = 0;
            $startRow = $rowNumber;

            // Process each sale for this seller
            foreach ($sellerSales as $sale) {
                $price = $sale->custom_price > 0 ? $sale->custom_price : ($sale->item->price ?? 0);
                $netQty = $sale->pick - ($sale->returned ?? 0);
                $rowTotal = $netQty * $price;
                $sellerTotal += $rowTotal;

                // Set cell values
                $sheet->setCellValue('C' . $rowNumber, $sellerName);
                $sheet->setCellValue('D' . $rowNumber, $sale->item->name . " ({$price})");
                $sheet->setCellValue('F' . $rowNumber, $sale->pick);
                $sheet->setCellValue('G' . $rowNumber, $sale->returned ?? 0);
                $sheet->setCellValue('H' . $rowNumber, $rowTotal);

                // Color coding - red flag or beige for normal
                $color = $sale->red_flag ? 'FFDF6D6D' : 'FFF5F5DC';
                $sheet->getStyle("F{$rowNumber}:K{$rowNumber}")
                    ->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setARGB($color);

                $rowNumber++;
            }

            $endRow = $rowNumber - 1;

            // Merge cells for seller name and totals
            $sheet->mergeCells("C{$startRow}:C{$endRow}");
            $sheet->mergeCells("I{$startRow}:I{$endRow}");
            $sheet->mergeCells("J{$startRow}:J{$endRow}");
            $sheet->mergeCells("K{$startRow}:K{$endRow}");

            // Calculate and set seller totals
            $salary = (int)($sellerTotal * 0.4);
            $share = (int)($sellerTotal * 0.6);

            $sheet->setCellValue("I{$startRow}", $sellerTotal);
            $sheet->setCellValue("J{$startRow}", $salary);
            $sheet->setCellValue("K{$startRow}", $share);

            $overallSum += $sellerTotal;
        }

        // Add grand total row
        $totalRow = $rowNumber + 2;
        $grandSalary = (int)($overallSum * 0.4);
        $grandShare = (int)($overallSum * 0.6);

        $sheet->setCellValue('C' . $totalRow, 'Total');
        $sheet->setCellValue('I' . $totalRow, $overallSum);
        $sheet->setCellValue('J' . $totalRow, $grandSalary);
        $sheet->setCellValue('K' . $totalRow, $grandShare);

        // Style grand total row
        $sheet->getStyle('C' . $totalRow . ':K' . $totalRow)->getFont()->setBold(true);

        // Freeze panes
        $sheet->freezePane('A2');

        // Auto-size columns
        foreach (range('A', 'K') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        return $spreadsheet;
    }

    /** Generate CSV sales report for a specific date (matches yearly report format) */
    public function generateCSV(string $date = ''): string
    {
        if (empty($date)) {
            $date = Carbon::now()->format('Y-m-d');
        }

        // Fetch sales for the given date using repository (not direct model call)
        $sales = $this->saleRepository->getByDate($date);

        $csv = "Date,Seller Name,Item Name,Pick,Returned,Total,Sum,Salary (40%),Share (60%)\n";

        if ($sales->isEmpty()) {
            return $csv;
        }

        // Group by seller
        $groupedBySeller = $sales->groupBy('seller_id');
        $overallSum = 0;

        foreach ($groupedBySeller as $sellerId => $sellerSales) {
            $sellerName = $sellerSales->first()->seller->name ?? 'Unknown Seller';
            $sellerTotal = 0;
            $itemRows = [];

            // Calculate individual rows and seller total
            foreach ($sellerSales as $sale) {
                $price = $sale->custom_price > 0 ? $sale->custom_price : ($sale->item->price ?? 0);
                $rowTotal = ($sale->pick - ($sale->returned ?? 0)) * $price;
                $sellerTotal += $rowTotal;

                // Escape quotes in item name for CSV
                $itemName = str_replace('"', '""', $sale->item->name ?? 'Unknown');
                $itemNameWithPrice = "{$itemName} ({$price})";

                $itemRows[] = [
                    'seller' => $sellerName,
                    'item' => $itemNameWithPrice,
                    'pick' => $sale->pick,
                    'returned' => $sale->returned ?? 0,
                    'total' => $rowTotal,
                ];
            }

            // Calculate seller percentages
            $salary = (int)($sellerTotal * 0.4);
            $share = (int)($sellerTotal * 0.6);

            // Add individual item rows
            foreach ($itemRows as $index => $row) {
                if ($index === 0) {
                    // First item row includes Sum, Salary, Share
                    $csv .= sprintf(
                        '"%s","%s","%s",%d,%d,%d,%d,%d,%d' . "\n",
                        $date,
                        $row['seller'],
                        $row['item'],
                        $row['pick'],
                        $row['returned'],
                        $row['total'],
                        $sellerTotal,
                        $salary,
                        $share
                    );
                } else {
                    // Subsequent rows keep seller name but leave Sum, Salary, Share blank
                    $csv .= sprintf(
                        '"%s","%s","%s",%d,%d,%d,,,'."\n",
                        $date,
                        $row['seller'],
                        $row['item'],
                        $row['pick'],
                        $row['returned'],
                        $row['total']
                    );
                }
            }

            $overallSum += $sellerTotal;
        }

        // Add grand total row
        $csv .= "\n";
        $grandSalary = (int)($overallSum * 0.4);
        $grandShare = (int)($overallSum * 0.6);
        $csv .= sprintf('"%s","%s","Total",,,%d,%d,%d,%d' . "\n", $date, '', '', '', '', $overallSum, $grandSalary, $grandShare);

        return $csv;
    }

    /** Generate text summary report for a specific date (matches yearly report format) */
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

        // Group by seller
        $groupedBySeller = $sales->groupBy('seller_id');
        $overallSum = 0;

        foreach ($groupedBySeller as $sellerId => $sellerSales) {
            $sellerName = $sellerSales->first()->seller->name ?? 'Unknown Seller';
            $sellerTotal = 0;
            $itemCount = 0;

            // Calculate seller total
            foreach ($sellerSales as $sale) {
                $price = $sale->custom_price > 0 ? $sale->custom_price : ($sale->item->price ?? 0);
                $rowTotal = ($sale->pick - ($sale->returned ?? 0)) * $price;
                $sellerTotal += $rowTotal;
                $itemCount++;
            }

            // Calculate 40% salary, 60% share (matches yearly report)
            $salary = (int)($sellerTotal * 0.4);
            $share = (int)($sellerTotal * 0.6);

            $summary .= "👤 {$sellerName}\n";
            $summary .= "   Items: {$itemCount}\n";
            $summary .= "   Total Sales: ₹" . number_format($sellerTotal) . "\n";
            $summary .= "   Salary (40%): ₹" . number_format($salary) . "\n";
            $summary .= "   Share (60%): ₹" . number_format($share) . "\n\n";

            $overallSum += $sellerTotal;
        }

        // Calculate grand totals
        $grandSalary = (int)($overallSum * 0.4);
        $grandShare = (int)($overallSum * 0.6);

        $summary .= "═══════════════════════════════════════════\n";
        $summary .= "Total Sales: ₹" . number_format($overallSum) . "\n";
        $summary .= "Total Salary (40%): ₹" . number_format($grandSalary) . "\n";
        $summary .= "Total Share (60%): ₹" . number_format($grandShare) . "\n";
        $summary .= "═══════════════════════════════════════════\n";

        return $summary;
    }

    /**
     * Export sales report to XLSX file
     *
     * @param string $date Date for report (YYYY-MM-DD)
     * @return string File path to the XLSX file
     */
    public function exportToXlsxFile(string $date = ''): string
    {
        if (empty($date)) {
            $date = Carbon::now()->format('Y-m-d');
        }

        $spreadsheet = $this->generateXlsx($date);
        $fileName = "sales_report_" . $date . ".xlsx";
        $filePath = storage_path("reports/{$fileName}");

        // Create directory if it doesn't exist
        if (!is_dir(dirname($filePath))) {
            mkdir(dirname($filePath), 0755, true);
        }

        // Write XLSX file
        $writer = new Xlsx($spreadsheet);
        $writer->save($filePath);

        return $filePath;
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