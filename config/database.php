<?php

use Illuminate\Support\Str;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Database Connection Name
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the database connections below you wish
    | to use as your default connection for all database work. Of course
    | you may use many connections at once using the Database library.
    |
    */

    'default' => env('DB_CONNECTION', 'mysql'),

    /*
    |--------------------------------------------------------------------------
    | Database Connections
    |--------------------------------------------------------------------------
    |
    | Here are each of the database connections setup for your application.
    | Of course, examples of configuring each database platform that is
    | supported by Laravel is shown below to make development simple.
    |
    |
    | All database work in Laravel is done through the PHP PDO facilities
    | so make sure you have the driver for your particular database of
    | choice installed on your machine before you begin development.
    |
    */

    'connections' => [

        'sqlite' => [
            'driver' => 'sqlite',
            'database' => env('DB_DATABASE', database_path('database.sqlite')),
            'prefix' => env('DB_PREFIX', ''),
        ],

        'mysql' => [
            'driver' => 'mysql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', 3306),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => env('DB_CHARSET', 'utf8mb4'),
            'collation' => env('DB_COLLATION', 'utf8mb4_unicode_ci'),
            'prefix' => env('DB_PREFIX', ''),
            'strict' => env('DB_STRICT_MODE', true),
            'engine' => env('DB_ENGINE', null),
            'timezone' => env('DB_TIMEZONE', '+00:00'),
        ],

        'pgsql' => [
            'driver' => 'pgsql',
            'host' => env('POSTGRES_DB_HOST', '127.0.0.1'),
            'port' => env('POSTGRES_DB_PORT', 5432),
            'database' => 'postgres',
            'username' => env('POSTGRES_DEPLOY_DB_USERNAME', 'forge'),
            'password' => env('POSTGRES_DEPLOY_DB_PASSWORD', ''),
            'charset' => env('DB_CHARSET', 'utf8'),
            'prefix' => env('DB_PREFIX', ''),
            'schema' => env('DB_SCHEMA', 'public'),
            'sslmode' => env('DB_SSL_MODE', 'prefer'),
        ],

        'payment_pgsql' => [
            'driver' => 'pgsql',
            'host' => env('POSTGRES_DB_HOST', '127.0.0.1'),
            'port' => env('POSTGRES_DB_PORT', '5432'),
            'database' => env('POSTGRES_PAYMENT_DB_DATABASE', 'payment_template'),
            'username' => env('POSTGRES_DB_USERNAME', 'forge'),
            'password' => env('POSTGRES_DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'schema' => 'public',
            'sslmode' => 'prefer',
        ],

        'deploy_payment_pgsql' => [
            'driver' => 'pgsql',
            'host' => env('POSTGRES_DB_HOST', '127.0.0.1'),
            'port' => env('POSTGRES_DB_PORT', '5432'),
            'database' => env('POSTGRES_PAYMENT_DB_DATABASE', 'payment_template'),
            'username' => env('POSTGRES_DEPLOY_DB_USERNAME', 'forge'),
            'password' => env('POSTGRES_DEPLOY_DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'schema' => 'public',
            'sslmode' => 'prefer',
        ],

        'unit_test_pgsql' => [
            'driver' => 'pgsql',
            'host' => env('UNIT_TEST_POSTGRES_DB_HOST', 'localhost'),
            'port' => env('UNIT_TEST_POSTGRES_DB_PORT', '5432'),
            'database' => 'postgres',
            'username' => env('UNIT_TEST_POSTGRES_DB_USERNAME', ''),
            'password' => env('UNIT_TEST_POSTGRES_DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'schema' => 'public',
            'sslmode' => 'prefer',
            'options'   => array(
                PDO::ATTR_PERSISTENT => env('DB_PERSISTENT', false),
            ),
        ],

        'unit_test_payment_pgsql' => [
            'driver' => 'pgsql',
            'host' => env('UNIT_TEST_POSTGRES_DB_HOST', 'localhost'),
            'port' => env('UNIT_TEST_POSTGRES_DB_PORT', '5432'),
            'database' => env('UNIT_TEST_PAYMENT_DB_DATABASE', 'payment_unit_test'),
            'username' => env('UNIT_TEST_POSTGRES_DB_USERNAME', ''),
            'password' => env('UNIT_TEST_POSTGRES_DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'schema' => 'public',
            'sslmode' => 'prefer',
            'options'   => array(
                PDO::ATTR_PERSISTENT => env('DB_PERSISTENT', false),
            ),
        ],

        'sqlsrv' => [
            'driver' => 'sqlsrv',
            'host' => env('DB_HOST', 'localhost'),
            'port' => env('DB_PORT', 1433),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => env('DB_CHARSET', 'utf8'),
            'prefix' => env('DB_PREFIX', ''),
        ],

        'ecommerce_pgsql' => [
            'driver' => 'pgsql',
            'host' => env('POSTGRES_DB_HOST', '127.0.0.1'),
            'port' => env('POSTGRES_DB_PORT', '5432'),
            'database' => env('POSTGRES_ECOMMERCE_DB_DATABASE', 'tlspay-e-commerce-service-db'),
            'username' => env('POSTGRES_ECOMMERCE_DB_USERNAME', 'tlspay-e-commerce-service-user'),
            'password' => env('POSTGRES_ECOMMERCE_DB_PASSWORD', ''),
            'charset' =>  env('DB_CHARSET', 'utf8'),
            'prefix' => env('DB_PREFIX', ''),
            'schema' => 'public',
            'sslmode' => 'prefer',
        ],

        'tlsconnect_pgsql' => [
            'driver' => 'pgsql',
            'host' => env('POSTGRES_DB_HOST', '127.0.0.1'),
            'port' => env('POSTGRES_DB_PORT', '5432'),
            'database' => env('POSTGRES_TLSCONNECT_DB_DATABASE', ''),
            'username' => env('POSTGRES_DB_USERNAME', 'forge'),
            'password' => env('POSTGRES_DB_PASSWORD', ''),
            'charset' =>  env('DB_CHARSET', 'utf8'),
            'prefix' => env('DB_PREFIX', ''),
            'prefix_indexes' => true,
            'schema' => 'public',
            'sslmode' => 'prefer',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Migration Repository Table
    |--------------------------------------------------------------------------
    |
    | This table keeps track of all the migrations that have already run for
    | your application. Using this information, we can determine which of
    | the migrations on disk haven't actually been run in the database.
    |
    */

    'migrations' => 'migrations',

    /*
    |--------------------------------------------------------------------------
    | Redis Databases
    |--------------------------------------------------------------------------
    |
    | Redis is an open source, fast, and advanced key-value store that also
    | provides a richer set of commands than a typical key-value systems
    | such as APC or Memcached. Laravel makes it easy to dig right in.
    |
    */

    'redis' => [

        'client' => env('REDIS_CLIENT', 'phpredis'),

        'options' => [
            'cluster' => env('REDIS_CLUSTER', 'redis'),
            'context' => (strpos(env('REDIS_HOST'), 'tls://') === 0) ? ['stream' => ['verify_peer' => false, 'verify_peer_name' => false], 'verify_peer' => false, 'verify_peer_name' => false] : null,
            'prefix' => env('REDIS_PREFIX', Str::slug(env('APP_NAME', 'lumen'), '_').'_database_'),
            'parameters' => [
                'host' => env('REDIS_HOST', '127.0.0.1'),
                'password' => env('REDIS_PASSWORD', null),
                'port' => env('REDIS_PORT', 6379),
            ]
        ],

        'clusters' => [
            'default' => [
                [
                    'url' => env('REDIS_URL'),
                    'host' => env('REDIS_HOST', '127.0.0.1'),
                    'password' => env('REDIS_PASSWORD', null),
                    'port' => env('REDIS_PORT', '6379'),
                    'database' => env('REDIS_DB', '0'),
                ]
            ],

            'cache' => [
                [
                    'url' => env('REDIS_URL'),
                    'host' => env('REDIS_HOST', '127.0.0.1'),
                    'password' => env('REDIS_PASSWORD', null),
                    'port' => env('REDIS_PORT', '6379'),
                    'database' => env('REDIS_CACHE_DB', '1'),
                ]
            ],
        ],

    ],

];
