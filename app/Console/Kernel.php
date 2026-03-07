<?php

namespace App\Console;

use App\Console\Commands\SendSalesReportCommand;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Send daily sales report via WhatsApp at 10 PM (22:00)
        $schedule->command(SendSalesReportCommand::class)
            ->dailyAt('22:00')
            ->runInBackground()
            ->withoutOverlapping()
            ->onFailure(function () {
                \Illuminate\Support\Facades\Log::error('Daily sales report job failed');
            })
            ->onSuccess(function () {
                \Illuminate\Support\Facades\Log::info('Daily sales report sent successfully');
            });
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
