<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env( 'POSTMARK_API_KEY' ),
    ],

    'resend' => [
        'key' => env( 'RESEND_API_KEY' ),
    ],

    'ses' => [
        'key' => env( 'AWS_ACCESS_KEY_ID' ),
        'secret' => env( 'AWS_SECRET_ACCESS_KEY' ),
        'region' => env( 'AWS_DEFAULT_REGION', 'us-east-1' ),
    ],

    'google' => [
        'service_account_json' => env( 'GOOGLE_SERVICE_ACCOUNT_JSON', base_path('storage/google/gen-lang-client-0560204066-d5fff0018963.json' ) ),
        'calendar_owner_email' => env( 'GOOGLE_CALENDAR_OWNER_EMAIL' ),
        'calendar_id' => env( 'GOOGLE_CALENDAR_ID', 'primary' ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Calendly Configuration
    |--------------------------------------------------------------------------
    |
    | Calendly API configuration for booking appointments.
    | Get your API key from: https://calendly.com/integrations/api_webhooks
    |
    */

    'calendly' => [
        'api_key' => env( 'CALENDLY_API_KEY' ),

        'event_type_mapping' => [
            'Consultation' => 'https://api.calendly.com/event_types/5c64c8b4-7c7e-4dce-95fd-53871c2cada1',
            'PRP' => 'https://api.calendly.com/event_types/5c64c8b4-7c7e-4dce-95fd-53871c2cada1',
            'Treatment' => 'https://api.calendly.com/event_types/5c64c8b4-7c7e-4dce-95fd-53871c2cada1',
        ],

        'default_event_type' => 'https://api.calendly.com/event_types/5c64c8b4-7c7e-4dce-95fd-53871c2cada1',

        'default_timezone' => env( 'APP_TIMEZONE', 'UTC' ),
        'default_duration' => env( 'BOOKING_DEFAULT_DURATION', 60 ),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env( 'SLACK_BOT_USER_OTAUTH_TOKEN' ),
            'channel' => env( 'SLACK_BOT_USER_DEFAULT_CHANNEL' ),
        ],
    ],

];