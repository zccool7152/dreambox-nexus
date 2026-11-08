<?php
declare(strict_types=1);

use Nexus\Core\Env;

return [
    'default' => (string)Env::get('DB_CONNECTION', 'mysql'),

    'connections' => [
        'mysql' => [
            'driver' => 'mysql',
            'host' => (string)Env::get('DB_HOST', '127.0.0.1'),
            'port' => (int)Env::get('DB_PORT', 3306),
            'database' => (string)Env::get('DB_DATABASE', 'dreambox_nexus'),
            'username' => (string)Env::get('DB_USERNAME', 'root'),
            'password' => (string)Env::get('DB_PASSWORD', ''),
            'charset' => (string)Env::get('DB_CHARSET', 'utf8mb4'),
            'collation' => (string)Env::get('DB_COLLATION', 'utf8mb4_unicode_ci'),
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_TIMEOUT => 5,
            ],
        ],

        'sqlite' => [
            'driver' => 'sqlite',
            'database' => (string)Env::get('DB_DATABASE', __DIR__ . '/../storage/database.sqlite'),
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ],
        ],

        'mongodb' => [
            'driver' => 'mongodb',
            'host' => (string)Env::get('MONGO_HOST', '127.0.0.1'),
            'port' => (int)Env::get('MONGO_PORT', 27017),
            'database' => (string)Env::get('MONGO_DATABASE', 'dreambox_nexus'),
            'username' => (string)Env::get('MONGO_USERNAME', 'nexus_app'),
            'password' => (string)Env::get('MONGO_PASSWORD', ''),
            'auth_source' => (string)Env::get('MONGO_AUTH_SOURCE', 'dreambox_nexus'),
        ],
    ],

    'migrations' => 'migrations',
];
