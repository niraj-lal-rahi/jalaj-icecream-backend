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
     * Send a WhatsApp text message
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
            ]
        ], [
            'phone_number.required' => 'Phone number is required.',
            'phone_number.regex' => 'Phone number must be in valid format: +91XXXXXXXXXX or 91XXXXXXXXXX (Indian numbers)',
            'message.required' => 'Message text is required.',
            'message.min' => 'Message cannot be empty.',
            'message.max' => 'Message cannot exceed 4096 characters.'
        ]);

        try {
            // Format phone number to +91XXXXXXXXXX
            $phoneNumber = $this->formatPhoneNumber($validated['phone_number']);

            // Send the message using WhatsAppService
            $success = $this->whatsAppService->sendTextMessage(
                $phoneNumber,
                $validated['message']
            );

            if ($success) {
                Log::info('WhatsApp message sent successfully', [
                    'phone' => $phoneNumber,
                    'message_length' => strlen($validated['message']),
                    'admin_id' => auth()->id()
                ]);

                return redirect()
                    ->route('admin.whatsapp.send-message')
                    ->with('success', 'WhatsApp message sent successfully to ' . $phoneNumber);
            } else {
                Log::error('WhatsApp message failed to send', [
                    'phone' => $phoneNumber,
                    'admin_id' => auth()->id()
                ]);

                return redirect()
                    ->route('admin.whatsapp.send-message')
                    ->withInput()
                    ->with('error', 'Failed to send message. Please check the phone number and try again.');
            }
        } catch (\Exception $e) {
            Log::error('WhatsApp message exception', [
                'exception' => $e->getMessage(),
                'admin_id' => auth()->id()
            ]);

            return redirect()
                ->route('admin.whatsapp.send-message')
                ->withInput()
                ->with('error', 'An error occurred while sending the message: ' . $e->getMessage());
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
