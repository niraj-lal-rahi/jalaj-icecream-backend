<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\Sale;
use App\Models\Seller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use App\Http\Requests\AdminCreateSaleRequest;

class SaleController extends Controller
{
    public function index(Request $request)
    {
        // Build query for sales
        $query = Sale::with(['seller', 'item']);

        // Check if any filters are being applied
        $hasAnyFilter = $request->filled('seller_id') || $request->filled('seller_name') || $request->filled('date');

        // Filter by seller_id (takes precedence over seller_name)
        if ($request->filled('seller_id')) {
            $query->where('seller_id', $request->seller_id);
        } elseif ($request->filled('seller_name')) {
            // Filter by seller name using partial match
            $query->whereHas('seller', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->seller_name . '%');
            });
        }

        // Filter by exact date
        if ($request->filled('date')) {
            $query->whereDate('date', $request->date);
        } elseif (!$hasAnyFilter) {
            // Default to current date if NO filters are applied
            $query->whereDate('date', Carbon::today());
        }

        // Get sales and group them
        $sales = $query->latest()
            ->get()
            ->groupBy(['date', 'seller_id']);

        // Get all sellers for filter dropdown
        $sellers = Seller::orderBy('name')->get();

        // Get current filter values for display
        $filters = [
            'seller_id' => $request->get('seller_id'),
            'seller_name' => $request->get('seller_name'),
            'date' => $request->get('date'),
        ];

        return view('admin.sales.index', compact('sales', 'sellers', 'filters'));
    }

    public function create()
    {
        $today = Carbon::today();
        $sellerIdsWithSalesToday = Sale::whereDate('date', $today)
            ->pluck('seller_id')
            ->unique()
            ->toArray();
        $sellers = Seller::whereNotIn('id', $sellerIdsWithSalesToday)
            ->orderBy('name')
            ->get();
        $items = Item::orderBy('order_by')->get();

        return view('admin.sales.create', compact('sellers', 'items'));
    }

    public function store(AdminCreateSaleRequest $request)
    {
        $validated = $request->validated();

        DB::transaction(function () use ($request, $validated) {

            foreach ($request->taken as $item_id => $taken) {

                if ($taken > 0) {

                    Sale::create([
                        'seller_id' => $validated['seller_id'],
                        'item_id' => $item_id,
                        'pick' => $taken,
                        'returned' => $request->returned[$item_id] ?? 0,
                        'custom_price' => $request->price[$item_id] ?? 0,
                        'remarks' => $request->remarks[$item_id] ?? null,
                        'red_flag' => $request->red_flag ?? false,
                        'date' => $validated['date'],
                    ]);
                }
            }
        });

        // Invalidate top performers cache as seller performance has changed
        Cache::forget('top_performers');

        return redirect()
            ->route('admin.sales.index')
            ->with('success', 'Sales saved successfully');
    }

    public function destroy(Sale $sale)
    {
        $sale->delete();

        // Invalidate top performers cache as seller performance has changed
        Cache::forget('top_performers');

        return back()->with('success', 'Sale deleted');
    }

    public function editGroup($sellerId, $date)
    {
        $seller = Seller::findOrFail($sellerId);

        $items = Item::orderBy('order_by')->get();

        $sales = Sale::where('seller_id', $sellerId)
            ->whereDate('date', $date)
            ->get()
            ->keyBy('item_id');

        return view('admin.sales.edit', compact(
            'seller',
            'items',
            'sales',
            'date'
        ));
    }

    public function updateGroup(Request $request, $sellerId, $date)
    {
        DB::transaction(function () use ($request, $sellerId, $date) {

            foreach ($request->taken as $itemId => $taken) {

                $returned = $request->returned[$itemId] ?? 0;
                $price = $request->price[$itemId] ?? 0;
                $remark = $request->remarks[$itemId] ?? null;

                $existing = Sale::where('seller_id', $sellerId)
                    ->whereDate('date', $date)
                    ->where('item_id', $itemId)
                    ->first();

                // If taken > 0 → update or create
                if ($taken > 0) {

                    if ($existing) {

                        $existing->update([
                            'pick' => $taken,
                            'returned' => $returned,
                            'custom_price' => $price,
                            'remarks' => $remark,
                            'red_flag' => $request->red_flag ?? false,
                        ]);

                    } else {

                        Sale::create([
                            'seller_id' => $sellerId,
                            'item_id' => $itemId,
                            'pick' => $taken,
                            'returned' => $returned,
                            'custom_price' => $price,
                            'remarks' => $remark,
                            'red_flag' => $request->red_flag ?? false,
                            'date' => $date,
                        ]);
                    }

                }
                // If taken = 0 → delete existing row
                elseif ($existing) {
                    $existing->delete();
                }
            }

        });

        // Invalidate top performers cache as seller performance has changed
        Cache::forget('top_performers');

        return redirect()
            ->route('admin.sales.index')
            ->with('success', 'Sales updated successfully');
    }

    public function exportYearlySales()
    {
        $year = Carbon::now()->year;

        $dates = Sale::whereYear('created_at', $year)
            ->select('date')
            ->groupBy('date')
            ->orderByDesc('date')
            ->pluck('date');

        $spreadsheet = new Spreadsheet;
        $spreadsheet->removeSheetByIndex(0); // remove default sheet

        foreach ($dates as $date) {

            $sheet = $spreadsheet->createSheet();
            $sheet->setTitle($date);

            // Header
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

            $sales = Sale::with(['seller', 'item'])
                ->whereDate('date', $date)
                ->get()
                ->groupBy('seller_id');

            $rowNumber = 2;
            $overallSum = 0;

            foreach ($sales as $sellerId => $records) {

                $startRow = $rowNumber;
                $sellerTotal = 0;

                foreach ($records as $sale) {

                    $price = $sale->custom_price > 0
                        ? $sale->custom_price
                        : $sale->item->price;

                    $rowTotal = ($sale->pick - $sale->returned) * $price;
                    $sellerTotal += $rowTotal;

                    $sheet->setCellValue('C'.$rowNumber, $sale->seller->name);
                    $sheet->setCellValue('D'.$rowNumber, $sale->item->name." ({$price})");
                    $sheet->setCellValue('F'.$rowNumber, $sale->pick);
                    $sheet->setCellValue('G'.$rowNumber, $sale->returned);
                    $sheet->setCellValue('H'.$rowNumber, $rowTotal);

                    // Row Color
                    $color = $sale->red_flag ? 'FFDF6D6D' : 'FFF5F5DC';

                    $sheet->getStyle("F{$rowNumber}:K{$rowNumber}")
                        ->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()
                        ->setARGB($color);

                    $rowNumber++;
                }

                $endRow = $rowNumber - 1;

                // Merge & totals
                $sheet->mergeCells("C{$startRow}:C{$endRow}");
                $sheet->mergeCells("I{$startRow}:I{$endRow}");
                $sheet->mergeCells("J{$startRow}:J{$endRow}");
                $sheet->mergeCells("K{$startRow}:K{$endRow}");

                $sheet->setCellValue("I{$startRow}", $sellerTotal);
                $sheet->setCellValue("J{$startRow}", $sellerTotal * 0.4);
                $sheet->setCellValue("K{$startRow}", $sellerTotal * 0.6);

                $overallSum += $sellerTotal;
            }

            // Overall total
            $sheet->setCellValue('C'.($rowNumber + 2), 'Total');
            $sheet->setCellValue('I'.($rowNumber + 2), $overallSum);
            $sheet->setCellValue('J'.($rowNumber + 2), $overallSum * 0.4);
            $sheet->setCellValue('K'.($rowNumber + 2), $overallSum * 0.6);

            // Styling
            $sheet->getStyle('C1:K1')->getFont()->setBold(true);
            $sheet->getStyle('C1:K1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->freezePane('A2');

            foreach (range('A', 'K') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }
        }

        $fileName = 'sales_data_'.now()->format('Y-m-d_H-i-s').'.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $fileName);
    }

    /**
     * Send sales report manually via WhatsApp or Email with optional cover message
     */
    public function sendManualReport(Request $request)
    {
        try {
            $validated = $request->validate([
                'date' => 'required|date',
                'cover_message' => 'nullable|string|max:4096',
                'via' => 'nullable|in:whatsapp,email,both',
            ], [
                'date.required' => 'Date is required.',
                'date.date' => 'Please provide a valid date.',
                'cover_message.max' => 'Cover message cannot exceed 4096 characters.',
                'via.in' => 'Send via must be whatsapp, email, or both.',
            ]);

            $date = $validated['date'];
            $coverMessage = $validated['cover_message'] ?? null;
            $via = $validated['via'] ?? 'whatsapp';

            $exporter = app('App\Services\SalesReportExporter');
            $whatsApp = app('App\Services\WhatsAppService');
            $email = app('App\Services\EmailService');

            // Generate XLSX file and summary
            $filePath = $exporter->exportToXlsxFile($date);
            $fileName = "sales_report_{$date}.xlsx";
            $summary = $exporter->generateSummary($date);

            $whatsappSent = false;
            $emailSent = false;

            // Send via WhatsApp if requested
            if (in_array($via, ['whatsapp', 'both'])) {
                $adminPhone = config('whatsapp.admin_phone_number');
                $reportCaption = "📊 Sales Report for {$date}";

                if (!empty($coverMessage)) {
                    $whatsappSent = $whatsApp->sendTextWithAttachment(
                        $adminPhone,
                        $coverMessage,
                        $filePath,
                        $fileName,
                        $reportCaption
                    );
                } else {
                    $whatsappSent = $whatsApp->sendDocumentMessage(
                        $adminPhone,
                        $filePath,
                        $fileName,
                        $reportCaption
                    );
                }
            }

            // Send via Email if requested
            if (in_array($via, ['email', 'both'])) {
                $adminEmail = config('mail.from.address') ?? config('app.email_admin');
                $emailSubject = "📊 Sales Report for {$date}";
                $emailBody = !empty($coverMessage) ? $coverMessage . "\n\n" . $summary : $summary;

                $emailSent = $email->sendSalesReport(
                    $adminEmail,
                    $filePath,
                    $fileName,
                    $emailSubject,
                    $emailBody
                );
            }

            // Determine success/failure
            $success = false;
            $message = '';

            if ($via === 'both') {
                $success = ($whatsappSent || $emailSent);
                $message = "Report sent via ";
                if ($whatsappSent) $message .= "WhatsApp ";
                if ($whatsappSent && $emailSent) $message .= "and ";
                if ($emailSent) $message .= "Email";
            } elseif ($via === 'whatsapp') {
                $success = $whatsappSent;
                $message = "Report sent via WhatsApp";
            } elseif ($via === 'email') {
                $success = $emailSent;
                $message = "Report sent via Email";
            }

            if ($success) {
                \Log::info("Manual report sent successfully", [
                    'date' => $date,
                    'via' => $via,
                    'whatsapp_sent' => $whatsappSent,
                    'email_sent' => $emailSent,
                    'admin_id' => auth()->id(),
                ]);
                return redirect()->back()->with('success', "✅ {$message}!");
            } else {
                \Log::error("Manual report sending failed", [
                    'date' => $date,
                    'via' => $via,
                    'whatsapp_sent' => $whatsappSent,
                    'email_sent' => $emailSent,
                    'admin_id' => auth()->id(),
                ]);
                return redirect()->back()->with('error', 'Failed to send report. Please check logs.');
            }
        } catch (\Exception $e) {
            \Log::error('Manual report sending failed', [
                'exception' => $e->getMessage(),
                'date' => $request->input('date'),
                'admin_id' => auth()->id(),
            ]);
            return redirect()->back()->with('error', 'Error: ' . $e->getMessage());
        }
    }
}
