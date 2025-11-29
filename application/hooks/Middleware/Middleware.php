<?php defined('BASEPATH') OR exit('No direct script access allowed');

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class Middleware {
	
	public function validateJWT() {
		
		$CI = &get_instance();

		$Controller = trim($CI->router->fetch_class());  //Controller name
		$Method     = trim($CI->router->fetch_method());  //Method name

		$ExcludeController = array("website", "login");
	    
		if(in_array($Controller, $ExcludeController)) {
			return;
		}

		//check JWT
		if (empty($JwtEncoded)) {

			// $CI->session->set_flashdata('danger', 'Oops! Action not allowed. please try login.');
			// redirect('portal/login', 'refresh');
            echo "DEBUG: Cookie '$cookieName' not found or empty<br>";
            exit;

        }

		try {

			$JwtData = JWT::decode($JwtEncoded, new Key(getenv('JWT_KEY'), 'HS256'));
			echo "DEBUG: Decoded JWT<br>";
            print_r($JwtData);
            echo "<br>";

			if(!empty($JwtData->key)) {

				$RedisData = $CI->cacheservice->get($JwtData->key);
				echo "DEBUG: RedisData<br>";
                print_r($RedisData);
                echo "<br>";

				if($RedisData->Error) {

					echo "DEBUG: Redis returned error<br>";
                    exit;

					// $CI->session->set_flashdata('danger', 'Oops! Session expired. please try login.');
					// redirect('portal/login', 'refresh');

				}else{

					echo "DEBUG: JwtData set in pageData<br>";
                    print_r($CI->pageData['JwtData']);
                    echo "<br>";

					$CI->pageData['JwtData'] = json_decode($RedisData->Value);
					$CI->pageData['JwtToken'] = $JwtEncoded;
					$CI->pageData['JwtUserKey'] = $JwtData->key;
				}

			} else {

				echo "DEBUG: JWT key missing<br>";
                exit;

				// $CI->session->set_flashdata('danger', 'Oops! Session expired. please try login.');
				// redirect('portal/login', 'refresh');

			}

		} catch(\Firebase\JWT\ExpiredException $e) {

			echo "DEBUG: JWT expired - " . $e->getMessage() . "<br>";
            exit;

			// $CI->session->set_flashdata('danger', 'Oops! Session expired. please try login.');
			// redirect('portal/login', 'refresh');
			
		} catch (\Firebase\JWT\SignatureInvalidException $e) {

			echo "DEBUG: JWT signature invalid - " . $e->getMessage() . "<br>";
            exit;

			// $CI->session->set_flashdata('danger', 'Oops! Security exception. please try login.');
			// redirect('portal/login', 'refresh');
		} catch (Exception $e) {
            echo "DEBUG: General JWT error - " . $e->getMessage() . "<br>";
            exit;
        }

	}
	
}
?>