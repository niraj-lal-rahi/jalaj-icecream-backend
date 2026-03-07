<?php

return [
    'api_token' => env('WHATSAPP_API_TOKEN'),
    'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
    'business_account_id' => env('WHATSAPP_BUSINESS_ACCOUNT_ID'),
    'admin_phone_number' => env('WHATSAPP_ADMIN_PHONE_NUMBER'),
    'api_version' => env('WHATSAPP_API_VERSION', 'v22.0'),
    'base_url' => 'https://graph.facebook.com',
    'test_mode' => env('WHATSAPP_TEST_MODE', true),
];
