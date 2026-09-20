<?php

return [
    'debug'      => true,
    'view_path'  => __DIR__ . '/../views',
    'cache_path' => __DIR__ . '/../storage/cache',
    'cache_ttl'  => 3600,

    // 数据库配置
    'db' => [
        'driver'  => 'mysql',
        'host'    => 'localhost',
        'port'    => 3306,
        'database'=> '',
        'username'=> '',
        'password'=> '',
        'charset' => 'utf8mb4',
        'options' => [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ],
    ],
];