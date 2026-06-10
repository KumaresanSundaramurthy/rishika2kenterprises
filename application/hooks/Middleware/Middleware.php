<?php defined('BASEPATH') OR exit('No direct script access allowed');

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class Middleware {
	
	public function validateJWT() {
		
		$CI = &get_instance();

		$Controller = trim($CI->router->fetch_class());  //Controller name
		$Method     = trim($CI->router->fetch_method());  //Method name

		$ExcludeController = array("website", "login", "receipt", "launch", "oauth");
	    
		if(in_array($Controller, $ExcludeController)) {
			return;
		}

		$cookieName = getenv('JWT_COOKIE_NAME');
        $JwtEncoded = get_cookie($cookieName);

		// Save the intended URL so the login page can redirect back after successful login
        // Only for non-AJAX, non-excluded page requests
        if (!$CI->input->is_ajax_request()) {
            $intendedUri = trim($CI->uri->uri_string(), '/');
            if (!empty($intendedUri) && !in_array(explode('/', $intendedUri)[0], $ExcludeController)) {
                $CI->session->set_userdata('intended_url', $intendedUri);
            }
        }

		//check JWT
		if (empty($JwtEncoded)) {
			$CI->session->set_flashdata('danger', 'Oops! Action not allowed. please try login.');
			redirect('portal', 'refresh');
        }

		try {

			$JwtData = JWT::decode($JwtEncoded, new Key(getenv('JWT_KEY'), 'HS256'));
			if(!empty($JwtData->key)) {

				$RedisData = $CI->redisservice->getCache($JwtData->key);
				if($RedisData->Error) {

					$CI->session->set_flashdata('danger', 'Oops! Session expired. please try login.');
					redirect('portal', 'refresh');

				} else {

					$CI->pageData['JwtData'] = $RedisData->Value;
					$CI->pageData['JwtToken'] = $JwtEncoded;
					$CI->pageData['JwtUserKey'] = $JwtData->key;

					// ── Single-session enforcement ───────────────────────────
					// Each login embeds a unique SessionToken in the Redis payload.
					// A parallel login for the same user overwrites UserActiveSession_{uid}
					// in Redis and CurrentSessionToken in DB, so this older token no longer
					// matches and the session is immediately terminated.
					$storedToken = $CI->pageData['JwtData']->User->SessionToken ?? null;
					$userUID     = $CI->pageData['JwtData']->User->UserUID ?? null;

					if ($storedToken && $userUID) {
						$activeKey  = 'UserActiveSession_' . $userUID;
						$activeData = $CI->redisservice->getCache($activeKey);

						if ($activeData->Error) {
							// Redis entry expired or missing — fall back to DB
							$CI->load->model('user_model');
							$activeToken = $CI->user_model->getCurrentSessionToken($userUID);
						} else {
							$activeToken = $activeData->Value;
						}

						if ($activeToken !== $storedToken) {
							// This session was invalidated by a newer login elsewhere
							$CI->redisservice->deleteCache($JwtData->key);
							delete_cookie(getenv('JWT_COOKIE_NAME'));

							if ($CI->input->is_ajax_request()) {
								$response = new stdClass();
								$response->Error          = true;
								$response->SessionExpired = true;
								$response->Message        = 'Your session has been terminated because your account was logged in from another device or browser.';
								$CI->output
									->set_status_header(401)
									->set_content_type('application/json', 'utf-8')
									->set_output(json_encode($response))
									->_display();
								exit;
							}

							$CI->session->set_flashdata('danger', 'Your session has been terminated as your account was logged in from another device or browser.');
							redirect('portal', 'refresh');
						}
					}
					// ────────────────────────────────────────────────────────

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