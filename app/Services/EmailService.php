<?php

namespace App\Services;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class EmailService
{
    /**
     * Send sales report via email with XLSX attachment
     *
     * @param string $recipientEmail Email address to send to
     * @param string $filePath Path to the XLSX file to attach
     * @param string $fileName Name of the file for attachment
     * @param string $subject Email subject
     * @param string|null $bodyText Email body text
     * @return bool True if email was sent successfully, false otherwise
     */
    public function sendSalesReport(
        string $recipientEmail,
        string $filePath,
        string $fileName,
        string $subject,
        string $bodyText = null
    ): bool {
        try {
            Mail::raw($bodyText ?? 'Please find the attached sales report.', function ($message) use (
                $recipientEmail,
                $filePath,
                $fileName,
                $subject
            ) {
                $message->to($recipientEmail)
                    ->subject($subject)
                    ->attach($filePath, [
                        'as' => $fileName,
                        'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    ]);
            });

            Log::info('Sales report email sent successfully', [
                'recipient' => $recipientEmail,
                'file' => $fileName,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send sales report email', [
                'recipient' => $recipientEmail,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }

    /**
     * Send sales report via email with custom HTML body
     *
     * @param string $recipientEmail Email address to send to
     * @param string $filePath Path to the file to attach
     * @param string $fileName Name of the file for attachment
     * @param string $subject Email subject
     * @param string $htmlBody HTML content for email body
     * @return bool True if email was sent successfully, false otherwise
     */
    public function sendSalesReportWithHtml(
        string $recipientEmail,
        string $filePath,
        string $fileName,
        string $subject,
        string $htmlBody
    ): bool {
        try {
            Mail::html($htmlBody, function ($message) use (
                $recipientEmail,
                $filePath,
                $fileName,
                $subject
            ) {
                $message->to($recipientEmail)
                    ->subject($subject)
                    ->attach($filePath, [
                        'as' => $fileName,
                        'mime' => 'text/csv',
                    ]);
            });

            Log::info('Sales report email (HTML) sent successfully', [
                'recipient' => $recipientEmail,
                'file' => $fileName,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send sales report email (HTML)', [
                'recipient' => $recipientEmail,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }
}
