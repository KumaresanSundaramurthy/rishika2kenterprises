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

			//No auth check

		} else {

			//check JWT

			$JwtEncoded = get_cookie(getenv('JWT_COOKIE_NAME'));
			print_r($JwtEncoded); print_r('</br>');
			if(isset($JwtEncoded)) {

				try{

					$JwtData = JWT::decode($JwtEncoded, new Key(getenv('JWT_KEY'), 'HS256'));
					print_r($JwtData); print_r('</br>');
					if(!empty($JwtData->key)) {

						$RedisData = $CI->cacheservice->get($JwtData->key);
						print_r($RedisData); print_r('</br>');

						if($RedisData->Error){

							$CI->session->set_flashdata('danger', 'Oops! Session expired. please try login.');

							redirect('portal/login', 'refresh');

						}else{

							print_r(json_decode($RedisData->Value)); die();

							$CI->pageData['JwtData'] = json_decode($RedisData->Value);
							$CI->pageData['JwtToken'] = $JwtEncoded;
							$CI->pageData['JwtUserKey'] = $JwtData->key;
						}

					} else {

						$CI->session->set_flashdata('danger', 'Oops! Session expired. please try login.');
						redirect('portal/login', 'refresh');

					}

				} catch(\Firebase\JWT\ExpiredException $e) {

					$CI->session->set_flashdata('danger', 'Oops! Session expired. please try login.');
					redirect('portal/login', 'refresh');
					
				} catch (\Firebase\JWT\SignatureInvalidException $e) {

					$CI->session->set_flashdata('danger', 'Oops! Security exception. please try login.');
					redirect('portal/login', 'refresh');
				}

			} else {

				$CI->session->set_flashdata('danger', 'Oops! Action not allowed. please try login.');
				redirect('portal/login', 'refresh');

			}
		}
	}
	
}
?>