<?php

namespace App\Console\Commands;

use App\Services\SalesReportExporter;
use App\Services\WhatsAppService;
use App\Services\EmailService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendSalesReportCommand extends Command
{
    /**
     * The name and signature of the console command.
     * # Use current date
     * php artisan send:sales-report --via=email
     *
     * # Use specific date
     * php artisan send:sales-report --date=2024-01-15 --via=email
     *
     * # Send to custom email
     * php artisan send:sales-report --via=email --email=user@example.com
     *
     * # Both WhatsApp and Email with specific date
     * php artisan send:sales-report --date=2024-01-15
     * @var string
     */
    protected $signature = 'send:sales-report {--date= : Date for report (YYYY-MM-DD, default: today)} {--via=both : Send via whatsapp, email, or both (default: both)} {--email= : Email address to send to (default: admin email)}';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Generate and send daily sales report via WhatsApp to admin';

    /**
     * Execute the console command.
     */
    public function handle(SalesReportExporter $exporter, WhatsAppService $whatsApp, EmailService $emailService): int
    {
        try {
            $date = $this->option('date') ?? Carbon::now()->format('Y-m-d');
            $via = $this->option('via') ?? 'both';

            $this->info("Generating sales report for {$date}...");

            // Generate XLSX file
            $filePath = $exporter->exportToXlsxFile($date);
            $fileName = "sales_report_{$date}.xlsx";

            // Generate summary text
            $summary = $exporter->generateSummary($date);

            $this->info("XLSX file created: {$filePath}");

            $whatsappSent = false;
            $emailSent = false;

            // Send via WhatsApp if requested
            if (in_array($via, ['whatsapp', 'both'])) {
                $this->info("Sending via WhatsApp...");
                $adminPhone = config('whatsapp.admin_phone_number');
                $caption = "📊 Sales Report for {$date}";

                $whatsappSent = $whatsApp->sendDocumentMessage(
                    $adminPhone,
                    $filePath,
                    $fileName,
                    $caption
                );

                if ($whatsappSent) {
                    $this->info("✅ Report sent successfully to WhatsApp ({$adminPhone})");
                } else {
                    $this->error("❌ Failed to send report via WhatsApp");
                }
            }

            // Send via Email if requested
            if (in_array($via, ['email', 'both'])) {
                $this->info("Sending via Email...");
                $adminEmail = $this->option('email') ?? config('mail.from.address') ?? config('app.email_admin');
                $subject = "📊 Sales Report for {$date}";

                $emailSent = $emailService->sendSalesReport(
                    $adminEmail,
                    $filePath,
                    $fileName,
                    $subject,
                    $summary
                );

                if ($emailSent) {
                    $this->info("✅ Report sent successfully via Email ({$adminEmail})");
                } else {
                    $this->error("❌ Failed to send report via Email");
                }
            }

            // Log results
            if (($via === 'both' && ($whatsappSent || $emailSent)) ||
                ($via === 'whatsapp' && $whatsappSent) ||
                ($via === 'email' && $emailSent)) {

                Log::info('Sales report sent successfully', [
                    'date' => $date,
                    'via' => $via,
                    'whatsapp_sent' => $whatsappSent,
                    'email_sent' => $emailSent,
                ]);
                return Command::SUCCESS;
            } else {
                $this->error("❌ Failed to send report via all channels");
                Log::error('Failed to send sales report', [
                    'date' => $date,
                    'via' => $via,
                    'whatsapp_sent' => $whatsappSent,
                    'email_sent' => $emailSent,
                ]);
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