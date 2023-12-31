<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Queue Connection Name
    |--------------------------------------------------------------------------
    |
    | Lumen's queue API supports an assortment of back-ends via a single
    | API, giving you convenient access to each back-end using the same
    | syntax for every one. Here you may define a default connection.
    |
    */

    'default' => env('QUEUE_CONNECTION', 'sync'),

    /*
    |--------------------------------------------------------------------------
    | Queue Connections
    |--------------------------------------------------------------------------
    |
    | Here you may configure the connection information for each server that
    | is used by your application. A default configuration has been added
    | for each back-end shipped with Lumen. You are free to add more.
    |
    | Drivers: "sync", "database", "beanstalkd", "sqs", "redis", "null"
    |
    */

    'connections' => [
        'sync' => [
            'driver' => 'sync',
        ],

        'database' => [
            'driver' => 'database',
            'table' => env('QUEUE_TABLE', 'jobs'),
            'queue' => 'default',
            'retry_after' => 90,
        ],

        'beanstalkd' => [
            'driver' => 'beanstalkd',
            'host' => 'localhost',
            'queue' => 'default',
            'retry_after' => 90,
        ],

        'sqs' => [
            'driver' => 'sqs',
            'key' => env('SQS_KEY', 'your-public-key'),
            'secret' => env('SQS_SECRET', 'your-secret-key'),
            'prefix' => env('SQS_PREFIX', 'https://sqs.us-east-1.amazonaws.com/your-account-id'),
            'queue' => env('SQS_QUEUE', 'your-queue-name'),
            'region' => env('SQS_REGION', 'us-east-1'),
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => env('QUEUE_REDIS_CONNECTION', 'default'),
            'queue' => 'default',
            'retry_after' => 90,
            'block_for' => null,
        ],

        'tlscontact_fawry_payment_queue' => [
            'driver' => 'database',
            'connection' => 'payment_pgsql',
            'table' => 'jobs',
            'queue' => 'tlscontact_fawry_payment_queue',
            'retry_after' => 90,
        ],

        'tlscontact_transaction_sync_queue' => [
            'driver' => 'database',
            'connection' => 'payment_pgsql',
            'table' => 'jobs',
            'queue' => 'tlscontact_transaction_sync_queue',
            'retry_after' => 90,
        ],

        'payment_api_eauditor_log_queue' => [
            'driver' => 'database',
            'connection' => 'payment_pgsql',
            'table' => 'jobs',
            'queue' => 'payment_api_eauditor_log_queue',
            'retry_after' => 90,
        ],

        'tlspay_invoice_queue' => [
            'driver' => 'database',
            'connection' => 'payment_pgsql',
            'table' => 'jobs',
            'queue' => 'tlspay_invoice_queue',
            'retry_after' => 90,
        ],

        'workflow_transaction_sync_queue' => [
            'driver' => 'database',
            'connection' => 'payment_pgsql',
            'table' => 'jobs',
            'queue' => 'workflow_transaction_sync_queue',
            'retry_after' => 90,
        ],

        'ecommerce_transaction_sync_queue' => [
            'driver' => 'database',
            'connection' => 'payment_pgsql',
            'table' => 'jobs',
            'queue' => 'ecommerce_transaction_sync_queue',
            'retry_after' => 90,
        ],

        'tlspay_receipt_queue' => [
            'driver' => 'database',
            'connection' => 'payment_pgsql',
            'table' => 'jobs',
            'queue' => 'tlspay_receipt_queue',
            'retry_after' => 90,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Failed Queue Jobs
    |--------------------------------------------------------------------------
    |
    | These options configure the behavior of failed queue job logging so you
    | can control which database and table are used to store the jobs that
    | have failed. You may change them to any database / table you wish.
    |
    */

    'failed' => [
        'database' => 'payment_pgsql',
        'table' => 'failed_jobs',
    ],
];
