<?php
/**
 * Quick WhatsApp test script
 * Run with: php test_whatsapp.php
 */

define('LARAVEL_START', microtime(true));

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\WhatsAppService;

echo "🧪 WhatsApp Service Test\n";
echo "========================\n\n";

// Initialize service
$whatsappService = new WhatsAppService();

// Test with the admin phone number from config
$testPhone = '+917499282145';
$testMessage = 'Test message from WhatsApp Service - ' . date('Y-m-d H:i:s');

echo "Sending test message...\n";
echo "Phone: $testPhone\n";
echo "Message: $testMessage\n";
echo "Time: " . date('Y-m-d H:i:s') . "\n\n";

// Send the message
$result = $whatsappService->sendTextMessage($testPhone, $testMessage);

if ($result) {
    echo "✅ Message send method returned true!\n";
    echo "Check WhatsApp within 2-3 seconds.\n\n";
} else {
    echo "❌ Message send method returned false (check logs for details).\n\n";
}

echo "📋 Checking logs...\n";
echo "==================\n";

// Read the last 40 lines of the log file
$logPath = __DIR__ . '/storage/logs/laravel.log';
if (file_exists($logPath)) {
    $lines = file($logPath);
    $lastLines = array_slice($lines, -40);

    // Filter for WhatsApp entries
    $whatsappLines = array_filter($lastLines, function($line) {
        return strpos($line, 'WhatsApp') !== false;
    });

    if ($whatsappLines) {
        echo "Recent WhatsApp log entries:\n";
        echo str_repeat("-", 80) . "\n";
        foreach ($whatsappLines as $line) {
            echo $line;
        }
        echo str_repeat("-", 80) . "\n";
    } else {
        echo "No WhatsApp entries found in recent logs.\n";
        echo "Last 15 log lines:\n";
        echo str_repeat("-", 80) . "\n";
        $last15 = array_slice($lastLines, -15);
        foreach ($last15 as $line) {
            echo $line;
        }
        echo str_repeat("-", 80) . "\n";
    }
} else {
    echo "Log file not found at: $logPath\n";
}

echo "\n✅ Test complete!\n";
