<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Managecache extends CI_Controller {
    
    public function __construct() {
        parent::__construct();
        
    }

    public function clear_all() {

        $this->load->library('cacheservice');
        $keys = [
            getSiteConfiguration()->RedisName.'Redis_UserMainModule',
            'Redis_UserSubModule', 
            'Redis_UserModuleInfo',
            'Redis_UserGenSettings',
            'Redis_UserInfo',
            'Redis_UserContext_*' // Pattern for user context
        ];
        
        $deleted = 0;
        foreach ($keys as $key) {
            if (strpos($key, '*') !== false) {
                // Pattern delete (for user context)
                // You need to implement pattern delete in your cacheservice
                $pattern = str_replace('*', '', $key);
                // This depends on your Redis library
            } else {
                if ($this->cacheservice->delete($key)) {
                    $deleted++;
                }
            }
        }
        
        echo "Cleared {$deleted} cache keys<br>";
        echo '<a href="/">Go Home</a>';

    }

}