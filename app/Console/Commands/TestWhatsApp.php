<?php

namespace App\Console\Commands;

use App\Services\WhatsAppService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TestWhatsApp extends Command
{
    protected $signature = 'whatsapp:test {phone? : Phone number to test with}';
    protected $description = 'Test WhatsApp message sending';

    public function handle()
    {
        $service = new WhatsAppService();

        $phone = $this->argument('phone') ?? '+917499282145';

        $this->info("🧪 WhatsApp Service Test");
        $this->line("========================\n");

        // Test 1: Template Message (RECOMMENDED)
        $this->info("Test 1️⃣: Sending TEMPLATE message (hello_world)...");
        $this->line("Phone: " . $phone);
        $this->line("Time: " . now()->format('Y-m-d H:i:s') . "\n");

        $templateResult = $service->sendTemplateMessage($phone);

        if ($templateResult) {
            $this->info("✅ Template message returned TRUE");
        } else {
            $this->error("❌ Template message returned FALSE");
        }

        $this->line("\n" . str_repeat("-", 80) . "\n");

        // Test 2: Text Message (for comparison)
        $this->info("Test 2️⃣: Sending TEXT message (for comparison)...");
        $message = 'Text message test - ' . now()->format('Y-m-d H:i:s');
        $this->line("Message: " . $message);
        $this->line("Time: " . now()->format('Y-m-d H:i:s') . "\n");

        $textResult = $service->sendTextMessage($phone, $message);

        if ($textResult) {
            $this->info("✅ Text message returned TRUE");
        } else {
            $this->error("❌ Text message returned FALSE");
        }

        $this->line("\n" . str_repeat("-", 80) . "\n");

        // Show logs
        $this->line("📋 Checking logs...");
        $this->line("==================");

        $logFile = storage_path('logs/laravel.log');
        if (file_exists($logFile)) {
            $lines = file($logFile);
            $recentLines = array_slice($lines, -60);

            $whatsappLines = array_filter($recentLines, function ($line) {
                return stripos($line, 'WhatsApp') !== false && strpos($line, 'sent') !== false;
            });

            if ($whatsappLines) {
                $this->line("\n✅ Recent WhatsApp message logs:");
                $this->line(str_repeat("-", 100));
                foreach ($whatsappLines as $line) {
                    $this->line(trim($line));
                }
                $this->line(str_repeat("-", 100));
            } else {
                $this->warn("\nNo recent WhatsApp send logs found.");
            }
        } else {
            $this->error("Log file not found: $logFile");
        }

        $this->line("\n💡 NOTE: Template messages have better delivery rates!");
        $this->line("✅ Test complete!");

        // Show usage examples
        $this->line("\n\n📚 USAGE EXAMPLES:");
        $this->line("================");
        $this->line("use App\\Services\\WhatsAppService;");
        $this->line('$service = new WhatsAppService();');
        $this->line("");
        $this->line("// 1. Send text message");
        $this->line('$service->sendTextMessage("+917499282145", "Hello World");');
        $this->line("");
        $this->line("// 2. Send template message (Pre-approved, better delivery)");
        $this->line('$service->sendTemplateMessage("+917499282145", "hello_world");');
        $this->line("");
        $this->line("// 3. Send document only");
        $this->line('$service->sendDocumentMessage("+917499282145", "/path/to/file.pdf", "Report.pdf", "Monthly Report");');
        $this->line("");
        $this->line("// 4. Send text message + document attachment (NEW!) ⭐");
        $this->line('$service->sendTextWithAttachment(');
        $this->line('    "+917499282145",');
        $this->line('    "Please find the attached report",');
        $this->line('    "/path/to/file.pdf",');
        $this->line('    "SalesReport.pdf",');
        $this->line('    "Monthly Sales Report"');
        $this->line(");");

        return 0;
    }
}
