<?php defined('BASEPATH') or exit('No direct script access allowed');

$config['redis'] = [
    'host'     => getenv('PHP_REDIS_HOST'),
    'port'     => getenv('PHP_REDIS_PORT'),
    'password' => getenv('PHP_REDIS_PASSWORD'),
    'database' => getenv('PHP_REDIS_DB'),
    'timeout'  => 1.0,   // connection timeout seconds
    'persistent' => FALSE
];