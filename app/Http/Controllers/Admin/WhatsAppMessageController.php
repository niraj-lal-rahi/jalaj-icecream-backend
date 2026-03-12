<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsAppMessageController extends Controller
{
    protected WhatsAppService $whatsAppService;

    public function __construct(WhatsAppService $whatsAppService)
    {
        $this->whatsAppService = $whatsAppService;
    }

    /**
     * Show the form to send a WhatsApp message
     */
    public function index()
    {
        return view('admin.whatsapp.send-message');
    }

    /**
     * Send a WhatsApp message using template-first strategy:
     * 1. Send pre-approved template to establish conversation (better delivery)
     * 2. Wait 2 seconds for delivery
     * 3. Send the actual message (text or text + attachment)
     *
     * This ensures your message is delivered by WhatsApp.
     */
    public function send(Request $request)
    {
        // Validate the input
        $validated = $request->validate([
            'phone_number' => [
                'required',
                'string',
                'regex:/^(\+?91|91)?[6-9]\d{9}$/'
            ],
            'message' => [
                'required',
                'string',
                'min:1',
                'max:4096'
            ],
            'attachment' => [
                'nullable',
                'file',
                'max:16384', // 16MB max (WhatsApp limit)
                'mimes:pdf,doc,docx,xls,xlsx,csv,txt,jpg,jpeg,png,gif,mp4,mp3,ogg',
            ]
        ], [
            'phone_number.required' => 'Phone number is required.',
            'phone_number.regex' => 'Phone number must be in valid format: +91XXXXXXXXXX or 91XXXXXXXXXX (Indian numbers)',
            'message.required' => 'Message text is required.',
            'message.min' => 'Message cannot be empty.',
            'message.max' => 'Message cannot exceed 4096 characters.',
            'attachment.max' => 'File size cannot exceed 16MB.',
            'attachment.mimes' => 'Only PDF, images, videos, and documents are allowed.'
        ]);

        try {
            // Format phone number to +91XXXXXXXXXX
            $phoneNumber = $this->formatPhoneNumber($validated['phone_number']);

            // ============================================================================
            // STEP 1: Send template message first to establish conversation
            // ============================================================================
            Log::info('WhatsApp: Step 1 - Sending template to establish conversation', [
                'phone' => $phoneNumber,
                'admin_id' => auth()->id()
            ]);

            $templateSuccess = $this->whatsAppService->sendTemplateMessage(
                $phoneNumber,
                'hello_world'
            );

            if (!$templateSuccess) {
                Log::error('WhatsApp: Failed to send template message', [
                    'phone' => $phoneNumber,
                    'admin_id' => auth()->id()
                ]);

                return redirect()
                    ->route('admin.whatsapp.send-message')
                    ->withInput()
                    ->with('error', 'Failed to establish conversation. Please try again.');
            }

            // Wait 10 seconds for template to fully deliver and activate conversation
            // This ensures WhatsApp recognizes the conversation as active on the recipient's device
            // before we send the custom message
            Log::debug('WhatsApp: Waiting 10 seconds for template delivery to activate conversation...');
            sleep(10);

            // ============================================================================
            // STEP 2: Send the actual message (text or text + attachment)
            // ============================================================================

            // Check if file attachment is provided
            if ($request->hasFile('attachment') && $request->file('attachment')->isValid()) {
                $file = $request->file('attachment');
                $filePath = $file->store('whatsapp-temp', 'local');
                // Normalize path separators for Windows compatibility
                $absolutePath = storage_path('app' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $filePath));
                $fileName = $file->getClientOriginalName();

                Log::info('WhatsApp: Step 2 - Sending text + attachment', [
                    'phone' => $phoneNumber,
                    'file_name' => $fileName,
                    'admin_id' => auth()->id()
                ]);

                // Send text message + attachment
                $success = $this->whatsAppService->sendTextWithAttachment(
                    $phoneNumber,
                    $validated['message'],
                    $absolutePath,
                    $fileName,
                    'Attachment from Admin'
                );

                // Clean up temp file
                if (file_exists($absolutePath)) {
                    unlink($absolutePath);
                }

                $logContext = [
                    'phone' => $phoneNumber,
                    'message_length' => strlen($validated['message']),
                    'file_name' => $fileName,
                    'file_size' => $file->getSize(),
                    'admin_id' => auth()->id(),
                    'strategy' => 'template_first'
                ];

                if ($success) {
                    Log::info('WhatsApp: Template + Message + Attachment sent successfully', $logContext);
                    return redirect()
                        ->route('admin.whatsapp.send-message')
                        ->with('success', "✅ Message and attachment sent successfully to $phoneNumber! (Template established conversation)");
                } else {
                    Log::error('WhatsApp: Failed to send message/attachment after template', $logContext);
                    return redirect()
                        ->route('admin.whatsapp.send-message')
                        ->withInput()
                        ->with('error', 'Template sent but failed to send message with attachment. Please try again.');
                }
            } else {
                // Send text message only (no attachment)
                Log::info('WhatsApp: Step 2 - Sending text message', [
                    'phone' => $phoneNumber,
                    'admin_id' => auth()->id()
                ]);

                $success = $this->whatsAppService->sendTextMessage(
                    $phoneNumber,
                    $validated['message']
                );

                $logContext = [
                    'phone' => $phoneNumber,
                    'message_length' => strlen($validated['message']),
                    'admin_id' => auth()->id(),
                    'strategy' => 'template_first'
                ];

                if ($success) {
                    Log::info('WhatsApp: Template + Text Message sent successfully', $logContext);

                    return redirect()
                        ->route('admin.whatsapp.send-message')
                        ->with('success', '✅ Message sent successfully to ' . $phoneNumber . '! (Template established conversation)');
                } else {
                    Log::error('WhatsApp: Failed to send text message after template', $logContext);

                    return redirect()
                        ->route('admin.whatsapp.send-message')
                        ->withInput()
                        ->with('error', 'Template sent but message delivery failed. Please try again.');
                }
            }
        } catch (\Exception $e) {
            Log::error('WhatsApp: Exception during multi-step send', [
                'exception' => $e->getMessage(),
                'admin_id' => auth()->id()
            ]);

            return redirect()
                ->route('admin.whatsapp.send-message')
                ->withInput()
                ->with('error', 'An error occurred: ' . $e->getMessage());
        }
    }

    /**
     * Format phone number to +91XXXXXXXXXX format
     */
    private function formatPhoneNumber(string $phone): string
    {
        // Remove any non-digit characters
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Remove leading 91 if present and pad to 10 digits
        if (strlen($phone) === 12 && substr($phone, 0, 2) === '91') {
            $phone = substr($phone, 2);
        }

        // Return in +91XXXXXXXXXX format
        return '+91' . $phone;
    }
}
