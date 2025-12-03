<?php defined('BASEPATH') or exit('No direct script access allowed');

$config['redis'] = [
    'host'     => 'r2kportal-redis',
    'port'     => 6379,
    'password' => NULL,
    'database' => 0,
    'timeout'  => 1.0,   // connection timeout seconds
    'persistent' => FALSE
];