<?php

return [
    // Defaults to 'local' — the whole pipeline (worker image download, this
    // config, ProductImage's URL builder) works out of the box with zero
    // cloud setup. Set FILESYSTEM_DISK=s3 plus the AWS_*/R2 credentials
    // below to move product images to S3 or Cloudflare R2 instead. See
    // PROGRESS.md — written to spec, not verified against a real bucket
    // (no credentials available in this environment).
    'default' => env('FILESYSTEM_DISK', 'local'),

    'disks' => [
        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => public_path('uploads'),
            'url' => env('APP_URL').'/uploads',
            'visibility' => 'public',
            'throw' => false,
        ],

        // Works for both AWS S3 and Cloudflare R2 (R2 is S3-API-compatible)
        // — for R2, set AWS_ENDPOINT to your account's R2 endpoint
        // (https://<account_id>.r2.cloudflarestorage.com), AWS_DEFAULT_REGION=auto,
        // and AWS_USE_PATH_STYLE_ENDPOINT=true. Requires
        // league/flysystem-aws-s3-v3 (installed as a Composer dependency).
        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION', 'auto'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'visibility' => 'public',
            'throw' => false,
        ],
    ],

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],
];
