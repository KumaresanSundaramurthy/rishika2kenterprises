<?php

use Predis\Client;

class Testing
{
    function __construct() {
        $client = new Predis\Client([
            'host' => 'redis-13443.crce182.ap-south-1-1.ec2.redns.redis-cloud.com',
            'port' => 13443,
            'database' => 0,
            'username' => 'default',
            'password'=> 'YvwDRuLu1MGM8SBqaeiwvezPA8fhatBF',
        ]);

        $client->set('foo', 'bar');
        $result = $client->get('foo');
        echo "$result\n";   // >>> bar
    }
}
