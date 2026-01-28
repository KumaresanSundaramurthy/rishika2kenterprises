<?php defined('BASEPATH') OR exit('No direct script access allowed');

use Predis\Client;

Class Cacheservice {

    private $Client;
    private $EndReturnData;

    function __construct() {
        
        $this->Client = new Predis\Client([
            'host' => getenv('REDIS_HOST'),
            'port' => getenv('REDIS_PORT'),
            'database' => getenv('REDIS_DATABASE'),
            'username' => getenv('REDIS_USERNAME'),
            'password'=> getenv('REDIS_PASSWORD'),
        ]);

    } 

    function set($Key, $Value, $TTL=300) {

        $this->EndReturnData = new stdClass();

        try {

            $this->Client->set($Key, json_encode($Value));
            $this->Client->expire($Key, $TTL);

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Message = 'Success';
            $this->EndReturnData->Key = $Key;
            $this->EndReturnData->TTL = $TTL;

        } catch (Exception $e) {
            
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();

        }

        return  $this->EndReturnData; 

    }

    function exists($Key) {

        $this->EndReturnData = new stdClass();

        try{

            if($this->Client->exists($Key)) {

                $this->EndReturnData->Error = FALSE;
                $this->EndReturnData->Message = 'Success';
                $this->EndReturnData->Key = $Key;

            }else{

                $this->EndReturnData->Error = TRUE;
                $this->EndReturnData->Message = 'Key Not Found';
                $this->EndReturnData->Key = $Key;
            }

        } catch (Exception $e){
            
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();

        }

        return  $this->EndReturnData; 

    }

    function get($Key) {

        $this->EndReturnData = new stdClass();
        try{

            if($this->Client->exists($Key)) {

                $this->EndReturnData->Error = FALSE;
                $this->EndReturnData->Message = 'Success';
                $this->EndReturnData->Key = $Key;
                $this->EndReturnData->Value = json_decode($this->Client->get($Key));
                $this->EndReturnData->TTL = $this->Client->ttl($Key);

            }else{

                $this->EndReturnData->Error = TRUE;
                $this->EndReturnData->Message = 'Key Not Found';
                $this->EndReturnData->Key = $Key;
            }

        } catch (Exception $e) {
            
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();

        }

        return  $this->EndReturnData; 

    }

    function delete($Key) {

        $this->EndReturnData = new stdClass();
        try {

            if($this->Client->exists($Key)) {

                $this->EndReturnData->Error = FALSE;
                $this->EndReturnData->Message = 'Success';
                $this->EndReturnData->Key = $Key;
                $this->EndReturnData->Delete = $this->Client->del($Key);

            }else{

                $this->EndReturnData->Error = TRUE;
                $this->EndReturnData->Message = 'Key Not Found';
                $this->EndReturnData->Key = $Key;
            }

        } catch (Exception $e) {
            
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();

        }

        return  $this->EndReturnData; 

    }

    function getTTL($Key) {

        $this->EndReturnData = new stdClass();

        try{

            if($this->Client->exists($Key)) {

                $this->EndReturnData->Error = FALSE;
                $this->EndReturnData->Message = 'Success';
                $this->EndReturnData->Key = $Key;
                $this->EndReturnData->TTL = $this->Client->ttl($Key);

            } else {

                $this->EndReturnData->Error = TRUE;
                $this->EndReturnData->Message = 'Key Not Found';
                $this->EndReturnData->Key = $Key;
            }

        } catch (Exception $e) {
            
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();

        }

        return  $this->EndReturnData;

    }

}