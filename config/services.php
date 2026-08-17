<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],

    'paypal' => [
        'client_id' => env('PAYPAL_CLIENT_ID'),
        'secret' => env('PAYPAL_SECRET'),
        'mode' => env('PAYPAL_MODE', 'sandbox'), // sandbox | live
    ],

    /*
    | Notificación al taller por WhatsApp/push cuando llega un pedido del
    | catálogo público. Sin claves, el aviso queda en el log (modo seguro)
    | y la campana interna del panel sigue siendo la fuente de verdad.
    |
    | - WHATSAPP_ENABLED: true solo cuando haya un proveedor configurado.
    | - WHATSAPP_WEBHOOK_URL: endpoint del proveedor (ej. Twilio Messages
    |   API, CallMeBot o un webhook propio que dispare el push).
    | - WHATSAPP_TOKEN: secreto del proveedor.
    | - NOTIFY_TALLER_PHONE: número del taller que recibe el aviso.
    |
    | El payload enviado es {"to", "text", "token"}.
    */
    'whatsapp' => [
        'enabled' => (bool) env('WHATSAPP_ENABLED', false),
        'url' => env('WHATSAPP_WEBHOOK_URL'),
        'token' => env('WHATSAPP_TOKEN'),
        'to' => env('NOTIFY_TALLER_PHONE'),
    ],

];
