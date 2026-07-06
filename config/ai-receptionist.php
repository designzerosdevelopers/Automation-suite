<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Clinic Information
    |--------------------------------------------------------------------------
    */
    'clinic' => [
        'name' => env('CLINIC_NAME', 'Aesthetic Clinic'),
        'address' => env('CLINIC_ADDRESS', '123 Main Street'),
        'phone' => env('CLINIC_PHONE', '+1234567890'),
        'email' => env('CLINIC_EMAIL', 'info@clinic.com'),
        'services' => explode(',', env('CLINIC_SERVICES', 'Botox,Fillers,Laser,Consultation')),
    ],

    /*
    |--------------------------------------------------------------------------
    | Vapi Settings
    |--------------------------------------------------------------------------
    */
    'vapi' => [
        'api_key' => env('VAPI_API_KEY'),
        'assistant_id' => env('VAPI_ASSISTANT_ID'),
        'phone_number_id' => env('VAPI_PHONE_NUMBER_ID'),
        'api_url' => env('VAPI_API_URL', 'https://api.vapi.ai'),
        'webhook_secret' => env('VAPI_WEBHOOK_SECRET'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Gemini AI Settings
    |--------------------------------------------------------------------------
    */
    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-1.5-pro'),
        'temperature' => env('GEMINI_TEMPERATURE', 0.3),
        'max_tokens' => env('GEMINI_MAX_TOKENS', 1000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Calendly Settings
    |--------------------------------------------------------------------------
    */
    'calendly' => [
        'api_key' => env('CALENDLY_API_KEY'),
        'url' => env('CALENDLY_URL'),
        'event_type_id' => env('CALENDLY_EVENT_TYPE_ID'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Twilio Settings (SMS)
    |--------------------------------------------------------------------------
    */
    'twilio' => [
        'sid' => env('TWILIO_SID'),
        'token' => env('TWILIO_TOKEN'),
        'phone' => env('TWILIO_PHONE'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Booking Rules
    |--------------------------------------------------------------------------
    */
    'booking' => [
        // Gap between bookings (buffer)
        'min_buffer_minutes' => env('BOOKING_BUFFER_MINUTES', 60),

        // Minimum notice for booking
        'min_advance_notice_hours' => env('BOOKING_MIN_ADVANCE_HOURS', 2),

        // Maximum days ahead for booking
        'max_advance_days' => env('BOOKING_MAX_ADVANCE_DAYS', 30),

        // Default service duration
        'default_duration_minutes' => env('BOOKING_DEFAULT_DURATION', 60),

        // Operating hours
        'operating_hours' => [
            'start' => env('BOOKING_OP_HOUR_START', 9),
            'end' => env('BOOKING_OP_HOUR_END', 20),
        ],

        // Days of week (0 = Sunday, 1 = Monday, etc.)
        'operating_days' => [1, 2, 3, 4, 5, 6], // Mon-Sat
    ],

    /*
    |--------------------------------------------------------------------------
    | Reminders
    |--------------------------------------------------------------------------
    */
    'reminders' => [
        'enabled' => env('REMINDERS_ENABLED', true),
        'hours_before' => [2], // Only 2-hour reminders
        'channels' => ['email', 'sms'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    */
    'notifications' => [
        'owner_email' => env('CLINIC_EMAIL'),
        'from_email' => env('MAIL_FROM_ADDRESS', 'noreply@clinic.com'),
        'from_name' => env('MAIL_FROM_NAME', 'AI Receptionist'),
    ],
];