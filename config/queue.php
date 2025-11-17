<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Queue Connection Name
    |--------------------------------------------------------------------------
    |
    | Laravel's queue supports a variety of backends via a single, unified
    | API, giving you convenient access to each backend using identical
    | syntax for each. The default queue connection is defined below.
    |
    */

    'default' => env('QUEUE_CONNECTION', 'database'),

    /*
    |--------------------------------------------------------------------------
    | Queue Connections
    |--------------------------------------------------------------------------
    |
    | Here you may configure the connection options for every queue backend
    | used by your application. An example configuration is provided for
    | each backend supported by Laravel. You're also free to add more.
    |
    | Drivers: "sync", "database", "beanstalkd", "sqs", "redis", "null"
    |
    */

    'connections' => [

        'sync' => [
            'driver' => 'sync',
        ],

        // 'rabbitmq' => [
        //     'driver' => 'rabbitmq',
        //     'queue' => env('RABBITMQ_QUEUE', 'default'),
        //     'hosts' => [
        //         [
        //             'host' => env('RABBITMQ_HOST', '127.0.0.1'),
        //             'port' => env('RABBITMQ_PORT', 5672),
        //             'user' => env('RABBITMQ_USER', 'guest'),
        //             'password' => env('RABBITMQ_PASSWORD', 'guest'),
        //             'vhost' => env('RABBITMQ_VHOST', '/'),
        //         ],
        //     ],
        //     'options' => [
        //         'queue' => [
        //             'dead_letter_exchange' => 'dlx_exchange',  // Dead-Letter Exchange (DLX)
        //             'retry_after' => 90,  // Time before message is retried
        //         ],
        //         'exchange' => [
        //             'name' => env('RABBITMQ_EXCHANGE_NAME', 'application'),
        //             'type' => env('RABBITMQ_EXCHANGE_TYPE', 'direct'),
        //         ],
        //     ],
        // ],

        'rabbitmq' => [
            'driver' => 'rabbitmq',
            'queue' => env('RABBITMQ_QUEUE', 'default'),
            'hosts' => [
                [
                    'host' => env('RABBITMQ_HOST', '127.0.0.1'),
                    'port' => env('RABBITMQ_PORT', 5672),
                    'user' => env('RABBITMQ_USER', 'guest'),
                    'password' => env('RABBITMQ_PASSWORD', 'guest'),
                    'vhost' => env('RABBITMQ_VHOST', '/'),
                ],
            ],
            'options' => [
                'queue' => [
                    'x-queue-mode' => 'lazy',  // Store messages on disk instead of RAM
                    'durable' => true,  // Queue survives restarts
                    'dead_letter_exchange' => 'dlx_exchange',  // Dead-Letter Exchange (DLX)
                    'retry_after' => 90,  // Time before message is retried
                ],
                'exchange' => [
                    'name' => env('RABBITMQ_EXCHANGE_NAME', 'application'),
                    'type' => env('RABBITMQ_EXCHANGE_TYPE', 'direct'),
                    'durable' => true,  // Exchange survives restarts
                ],
                'message' => [
                    'delivery_mode' => 2,  // Persistent messages (stored on disk)
                    'priority' => [
                        'enabled' => true,
                        'levels' => [
                            'high' => 10,  // Highest priority
                            'normal' => 5,  // Normal priority
                            'low' => 1,  // Lowest priority
                        ],
                    ],
                ],
                'queue_options' => [
                    'arguments' => [
                        'x-ha-policy' => 'all',  // High availability (mirrored queues)
                        'x-message-ttl' => 86400000,  // 24-hour message TTL (prevents message buildup)
                        'x-max-priority' => 10,  // Enable priority levels (1-10)
                        'x-dead-letter-exchange' => 'dlx_exchange', // Send failed messages to DLX
                    ],
                ],
            ],
        ],

        'database' => [
            'driver' => 'database',
            'connection' => env('DB_QUEUE_CONNECTION'),
            'table' => env('DB_QUEUE_TABLE', 'jobs'),
            'queue' => env('DB_QUEUE', 'default'),
            'retry_after' => (int) env('DB_QUEUE_RETRY_AFTER', 90),
            'after_commit' => false,
        ],

        'beanstalkd' => [
            'driver' => 'beanstalkd',
            'host' => env('BEANSTALKD_QUEUE_HOST', 'localhost'),
            'queue' => env('BEANSTALKD_QUEUE', 'default'),
            'retry_after' => (int) env('BEANSTALKD_QUEUE_RETRY_AFTER', 90),
            'block_for' => 0,
            'after_commit' => false,
        ],

        'sqs' => [
            'driver' => 'sqs',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'prefix' => env('SQS_PREFIX', 'https://sqs.us-east-1.amazonaws.com/your-account-id'),
            'queue' => env('SQS_QUEUE', 'default'),
            'suffix' => env('SQS_SUFFIX'),
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'after_commit' => false,
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => env('REDIS_QUEUE_CONNECTION', 'default'),
            'queue' => env('REDIS_QUEUE', 'default'),
            'retry_after' => (int) env('REDIS_QUEUE_RETRY_AFTER', 90),
            'block_for' => null,
            'after_commit' => false,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Job Batching
    |--------------------------------------------------------------------------
    |
    | The following options configure the database and table that store job
    | batching information. These options can be updated to any database
    | connection and table which has been defined by your application.
    |
    */

    'batching' => [
        'database' => env('DB_CONNECTION', 'sqlite'),
        'table' => 'job_batches',
    ],

    /*
    |--------------------------------------------------------------------------
    | Failed Queue Jobs
    |--------------------------------------------------------------------------
    |
    | These options configure the behavior of failed queue job logging so you
    | can control how and where failed jobs are stored. Laravel ships with
    | support for storing failed jobs in a simple file or in a database.
    |
    | Supported drivers: "database-uuids", "dynamodb", "file", "null"
    |
    */

    'failed' => [
        'driver' => env('QUEUE_FAILED_DRIVER', 'database-uuids'),
        'database' => env('DB_CONNECTION', 'sqlite'),
        'table' => 'failed_jobs',
    ],

    'worker' => [
        'tries' => 3,
        'memory' => 2048, // Set memory limit to 2GB
    ],

    'queues' => [

        // General Purpose Queues
        'high_priority_queue'      => 'high-priority',         // High-priority queue (critical or time-sensitive tasks)
        'default_queue'            => 'default',               // Normal/default queue for general background jobs
        'error_reporting_queue'    => 'error-reporting',       // For error report email jobs

        // Export Queues (based on size)
        'tiny_export'              => 'tiny-exports',          // Very small exports: ~1,000 records
        'small_export'             => 'small-exports',         // Small exports: ~10,000 records
        'medium_export'            => 'medium-exports',        // Medium exports: ~100,000 records
        'large_export'             => 'large-exports',         // Large exports: ~500,000 records
        'very_large_export'        => 'very-large-exports',    // Very large exports: ~1,000,000 records
        'extreme_large_export'     => 'extreme-large-exports', // Extremely large exports: ~5,000,000 records
        'massive_export'           => 'massive-exports',       // Massive exports: 5M+ records, multi-GB data
    ],

    'workers' => [
        [
            'name' => 'high-priority',
            'memory' => 4096,
            'timeout' => 3600,
        ],
        [
            'name' => 'default',
            'memory' => 4096,
            'timeout' => 3600,
        ],
        [
            'name' => 'error-reporting',
            'memory' => 4096,
            'timeout' => 3600,
        ],
        [
            'name' => 'tiny-exports',
            'memory' => 1024,
            'timeout' => 3600,
        ],
        [
            'name' => 'small-exports',
            'memory' => 2048,
            'timeout' => 7200,
        ],
        [
            'name' => 'medium-exports',
            'memory' => 4096,
            'timeout' => 14400,
        ],
        [
            'name' => 'large-exports',
            'memory' => 4096,
            'timeout' => 28800,
        ],
        [
            'name' => 'very-large-exports',
            'memory' => 5120,
            'timeout' => 57600,
        ],
        [
            'name' => 'extreme-large-exports',
            'memory' => 6144,
            'timeout' => 172800,
        ],
        [
            'name' => 'massive-exports',
            'memory' => 8192,
            'timeout' => 0,
        ],
    ],
];
