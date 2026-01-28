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

		$cookieName = getenv('JWT_COOKIE_NAME');
        $JwtEncoded = get_cookie($cookieName);

		//check JWT
		if (empty($JwtEncoded)) {
			$CI->session->set_flashdata('danger', 'Oops! Action not allowed. please try login.');
			redirect('portal', 'refresh');
        }

		try {

			$JwtData = JWT::decode($JwtEncoded, new Key(getenv('JWT_KEY'), 'HS256'));
			if(!empty($JwtData->key)) {

				$RedisData = $CI->cacheservice->get($JwtData->key);
				if($RedisData->Error) {

					$CI->session->set_flashdata('danger', 'Oops! Session expired. please try login.');
					redirect('portal', 'refresh');

				} else {

					$CI->pageData['JwtData'] = $RedisData->Value;
					$CI->pageData['JwtToken'] = $JwtEncoded;
					$CI->pageData['JwtUserKey'] = $JwtData->key;

					$uriString = $CI->uri->uri_string;
					if ($uriString === '' || $uriString === '/') {
						redirect('dashboard', 'refresh');
					}
					
				}

			} else {
				$CI->session->set_flashdata('danger', 'Oops! Session expired. please try login.');
				redirect('portal', 'refresh');
			}

		} catch(\Firebase\JWT\ExpiredException $e) {

			$CI->session->set_flashdata('danger', 'Oops! Session expired. please try login.');
			redirect('portal', 'refresh');
			
		} catch (\Firebase\JWT\SignatureInvalidException $e) {

			$CI->session->set_flashdata('danger', 'Oops! Security exception. please try login.');
			redirect('portal', 'refresh');

		} catch (Exception $e) {

            $CI->session->set_flashdata('danger', 'Oops! Exxception handled. please try login.');
			redirect('portal', 'refresh');
			
        }

	}
	
}
?>