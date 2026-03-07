<?php

namespace App\Console\Commands;

use App\Services\SalesReportExporter;
use App\Services\WhatsAppService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendSalesReportCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:sales-report {--date= : Date for report (YYYY-MM-DD, default: today)}';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Generate and send daily sales report via WhatsApp to admin';

    /**
     * Execute the console command.
     */
    public function handle(SalesReportExporter $exporter, WhatsAppService $whatsApp): int
    {
        try {
            $date = $this->option('date') ?? Carbon::now()->format('Y-m-d');

            $this->info("Generating sales report for {$date}...");

            // Generate CSV file
            $filePath = $exporter->exportToFile($date);
            $fileName = "sales_report_{$date}.csv";

            // Generate summary text
            $summary = $exporter->generateSummary($date);

            $this->info("CSV file created: {$filePath}");
            $this->info("Sending via WhatsApp...");

            // Send to admin
            $adminPhone = config('whatsapp.admin_phone_number');
            $caption = "📊 Sales Report for {$date}";

            $sent = $whatsApp->sendDocumentMessage(
                $adminPhone,
                $filePath,
                $fileName,
                $caption
            );

            if ($sent) {
                $this->info("✅ Report sent successfully to {$adminPhone}");
                Log::info('Sales report sent via WhatsApp', [
                    'date' => $date,
                    'recipient' => $adminPhone,
                    'file' => $fileName,
                ]);
                return Command::SUCCESS;
            } else {
                $this->error("❌ Failed to send report to WhatsApp");
                Log::error('Failed to send sales report', ['date' => $date]);
                return Command::FAILURE;
            }
        } catch (\Exception $e) {
            $this->error("Exception: {$e->getMessage()}");
            Log::error('Exception in SendSalesReportCommand', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return Command::FAILURE;
        }
    }
}
