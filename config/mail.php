<?php

return [
    /*
     * Defaults to 'log' — emails are written to storage/logs/laravel.log
     * instead of actually sending, until real SMTP (or Postmark/Resend/SES/
     * Mailgun) credentials are set. Deliberate: a half-configured mailer
     * that silently fails or hangs trying to reach a server that was never
     * set up is worse than one that visibly logs instead. See PROGRESS.md.
     */
    'default' => env('MAIL_MAILER', 'log'),

    'mailers' => [
        'smtp' => [
            'transport' => 'smtp',
            'scheme' => env('MAIL_SCHEME'),
            'url' => env('MAIL_URL'),
            'host' => env('MAIL_HOST', '127.0.0.1'),
            'port' => env('MAIL_PORT', 2525),
            'username' => env('MAIL_USERNAME'),
            'password' => env('MAIL_PASSWORD'),
            'timeout' => null,
        ],

        'log' => [
            'transport' => 'log',
            'channel' => env('MAIL_LOG_CHANNEL'),
        ],

        'array' => [
            'transport' => 'array',
        ],
    ],

    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'orders@farmtech.co.za'),
        'name' => env('MAIL_FROM_NAME', 'Farmtech'),
    ],
];
