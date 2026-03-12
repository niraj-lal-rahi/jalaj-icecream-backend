<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected string $apiToken;
    protected string $phoneNumberId;
    protected string $baseUrl;
    protected string $apiVersion;
    protected bool $testMode;

    public function __construct()
    {
        $this->apiToken = config('whatsapp.api_token');
        $this->phoneNumberId = config('whatsapp.phone_number_id');
        $this->baseUrl = config('whatsapp.base_url');
        $this->apiVersion = config('whatsapp.api_version');
        $this->testMode = config('whatsapp.test_mode', false);
    }

    /**
     * Send a message with a file (document) to WhatsApp
     *
     * @param string $toPhoneNumber Phone number in format: +919876543210
     * @param string $filePath Local file path to send
     * @param string $fileName Display name of the file
     * @param string $caption Optional caption for the file
     * @return bool
     */
    public function sendDocumentMessage(
        string $toPhoneNumber,
        string $filePath,
        string $fileName,
        string $caption = ''
    ): bool {
        try {
            // Test mode: skip actual API calls
            if ($this->testMode) {
                Log::info('WhatsApp (TEST MODE): Document would be sent', [
                    'to' => $toPhoneNumber,
                    'file' => $fileName,
                    'caption' => $caption,
                ]);
                return true;
            }

            // First, upload the document to WhatsApp
            $mediaId = $this->uploadDocument($filePath, $fileName);

            if (!$mediaId) {
                Log::error('WhatsApp: Failed to upload document', [
                    'file' => $fileName,
                    'phone' => $toPhoneNumber,
                ]);
                return false;
            }

            // Send message with document
            $url = "{$this->baseUrl}/{$this->apiVersion}/{$this->phoneNumberId}/messages";

            $payload = [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => str_replace('+', '', $toPhoneNumber),
                'type' => 'document',
                'document' => [
                    'id' => $mediaId,
                    'filename' => $fileName,
                ],
            ];

            if (!empty($caption)) {
                $payload['document']['caption'] = $caption;
            }

            // Log the request for debugging
            Log::debug('WhatsApp: Sending document message', [
                'url' => $url,
                'payload' => $payload,
                'phone_number_id' => $this->phoneNumberId,
            ]);

            $response = Http::withToken($this->apiToken)
                ->post($url, $payload);

            if ($response->successful()) {
                Log::info('WhatsApp: Document sent successfully', [
                    'to' => $toPhoneNumber,
                    'file' => $fileName,
                    'message_id' => $response->json('messages.0.id'),
                    'status_code' => $response->status(),
                    'response_body' => $response->json(),
                ]);
                return true;
            }

            Log::error('WhatsApp: Failed to send document', [
                'to' => $toPhoneNumber,
                'file' => $fileName,
                'response' => $response->json(),
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('WhatsApp: Exception while sending document', [
                'exception' => $e->getMessage(),
                'file' => $fileName,
                'phone' => $toPhoneNumber,
            ]);
            return false;
        }
    }

    /**
     * Upload a document to WhatsApp
     *
     * @param string $filePath Local file path
     * @param string $fileName Display name
     * @return string|null Media ID if successful, null otherwise
     */
    protected function uploadDocument(string $filePath, string $fileName): ?string
    {
        try {
            if (!file_exists($filePath)) {
                Log::error('WhatsApp: File not found', ['path' => $filePath]);
                return null;
            }

            $url = "{$this->baseUrl}/{$this->apiVersion}/{$this->phoneNumberId}/media";

            $response = Http::withToken($this->apiToken)
                ->attach(
                    'file',
                    fopen($filePath, 'r'),
                    $fileName
                )
                ->post($url, [
                    'messaging_product' => 'whatsapp',
                    'type' => 'application/pdf',
                ]);

            if ($response->successful()) {
                $mediaId = $response->json('id');
                Log::info('WhatsApp: Document uploaded successfully', [
                    'media_id' => $mediaId,
                    'file' => $fileName,
                ]);
                return $mediaId;
            }

            Log::error('WhatsApp: Failed to upload document', [
                'file' => $fileName,
                'response' => $response->json(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('WhatsApp: Exception during document upload', [
                'exception' => $e->getMessage(),
                'file' => $fileName,
            ]);
            return null;
        }
    }

    /**
     * Send a text message
     *
     * @param string $toPhoneNumber
     * @param string $message
     * @return bool
     */
    public function sendTextMessage(string $toPhoneNumber, string $message): bool
    {
        try {
            // Test mode: skip actual API calls
            if ($this->testMode) {
                Log::info('WhatsApp (TEST MODE): Text message would be sent', [
                    'to' => $toPhoneNumber,
                    'message' => $message,
                ]);
                return true;
            }

            $url = "{$this->baseUrl}/{$this->apiVersion}/{$this->phoneNumberId}/messages";

            $payload = [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => str_replace('+', '', $toPhoneNumber),
                'type' => 'text',
                'text' => ['body' => $message],
            ];

            // Log the request for debugging
            Log::debug('WhatsApp: Sending text message', [
                'url' => $url,
                'payload' => $payload,
                'phone_number_id' => $this->phoneNumberId,
            ]);

            $response = Http::withToken($this->apiToken)
                ->post($url, $payload);

            if ($response->successful()) {
                Log::info('WhatsApp: Text message sent successfully', [
                    'to' => $toPhoneNumber,
                    'message_length' => strlen($message),
                    'message_id' => $response->json('messages.0.id'),
                    'status_code' => $response->status(),
                    'response_body' => $response->json(),
                ]);
                return true;
            }

            Log::error('WhatsApp: Failed to send text message', [
                'to' => $toPhoneNumber,
                'response' => $response->json(),
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('WhatsApp: Exception while sending text', [
                'exception' => $e->getMessage(),
                'phone' => $toPhoneNumber,
            ]);
            return false;
        }
    }

    /**
     * Send a pre-approved template message (RECOMMENDED - best delivery rate)
     *
     * @param string $toPhoneNumber Phone number in format: +919876543210
     * @param string $templateName Template name (e.g., 'hello_world')
     * @param array $parameters Optional template parameters
     * @param string $languageCode Language code (default: en_US)
     * @return bool
     */
    public function sendTemplateMessage(
        string $toPhoneNumber,
        string $templateName = 'hello_world',
        array $parameters = [],
        string $languageCode = 'en_US'
    ): bool {
        try {
            // Test mode: skip actual API calls
            if ($this->testMode) {
                Log::info('WhatsApp (TEST MODE): Template message would be sent', [
                    'to' => $toPhoneNumber,
                    'template' => $templateName,
                    'language' => $languageCode,
                ]);
                return true;
            }

            $url = "{$this->baseUrl}/{$this->apiVersion}/{$this->phoneNumberId}/messages";

            $payload = [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => str_replace('+', '', $toPhoneNumber),
                'type' => 'template',
                'template' => [
                    'name' => $templateName,
                    'language' => [
                        'code' => $languageCode,
                    ],
                ],
            ];

            // Add parameters if provided
            if (!empty($parameters)) {
                $payload['template']['components'] = [
                    [
                        'type' => 'body',
                        'parameters' => array_map(function ($param) {
                            return ['type' => 'text', 'text' => $param];
                        }, $parameters),
                    ],
                ];
            }

            // Log the request for debugging
            Log::debug('WhatsApp: Sending template message', [
                'url' => $url,
                'payload' => $payload,
                'phone_number_id' => $this->phoneNumberId,
            ]);

            $response = Http::withToken($this->apiToken)
                ->post($url, $payload);

            if ($response->successful()) {
                Log::info('WhatsApp: Template message sent successfully', [
                    'to' => $toPhoneNumber,
                    'template' => $templateName,
                    'message_id' => $response->json('messages.0.id'),
                    'status_code' => $response->status(),
                    'response_body' => $response->json(),
                ]);
                return true;
            }

            Log::error('WhatsApp: Failed to send template message', [
                'to' => $toPhoneNumber,
                'template' => $templateName,
                'response' => $response->json(),
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('WhatsApp: Exception while sending template', [
                'exception' => $e->getMessage(),
                'phone' => $toPhoneNumber,
                'template' => $templateName,
            ]);
            return false;
        }
    }

    /**
     * Send a text message followed by a document attachment
     * (Sends two separate messages: text first, then document)
     *
     * @param string $toPhoneNumber Phone number in format: +919876543210
     * @param string $message Main message text
     * @param string $filePath Local file path to send as attachment
     * @param string $fileName Display name of the file
     * @param string $caption Optional caption for the attachment
     * @return bool True if both messages sent, false if any failed
     */
    public function sendTextWithAttachment(
        string $toPhoneNumber,
        string $message,
        string $filePath,
        string $fileName,
        string $caption = ''
    ): bool {
        try {
            if ($this->testMode) {
                Log::info('WhatsApp (TEST MODE): Text + attachment would be sent', [
                    'to' => $toPhoneNumber,
                    'message' => $message,
                    'file' => $fileName,
                    'caption' => $caption,
                ]);
                return true;
            }

            // Step 1: Send the text message first
            $textSuccess = $this->sendTextMessage($toPhoneNumber, $message);
            if (!$textSuccess) {
                Log::error('WhatsApp: Failed to send text message (Step 1)', [
                    'to' => $toPhoneNumber,
                ]);
                return false;
            }

            // Small delay to ensure message order
            usleep(500000); // 0.5 second delay

            // Step 2: Send the document attachment
            $documentSuccess = $this->sendDocumentMessage(
                $toPhoneNumber,
                $filePath,
                $fileName,
                $caption
            );

            if (!$documentSuccess) {
                Log::error('WhatsApp: Text sent but document failed (Step 2)', [
                    'to' => $toPhoneNumber,
                    'file' => $fileName,
                ]);
                return false;
            }

            Log::info('WhatsApp: Text + attachment sent successfully', [
                'to' => $toPhoneNumber,
                'message_length' => strlen($message),
                'file' => $fileName,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('WhatsApp: Exception while sending text with attachment', [
                'exception' => $e->getMessage(),
                'phone' => $toPhoneNumber,
                'file' => $fileName,
            ]);
            return false;
        }
    }
}
