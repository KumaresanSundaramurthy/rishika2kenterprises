<?php defined('BASEPATH') OR exit('No direct script access allowed');

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class Middleware {
	
	public function validateJWT() {
		
		$CI = &get_instance();

		$Controller = trim($CI->router->fetch_class());  //Controller name
		$Method     = trim($CI->router->fetch_method());  //Method name

		$ExcludeController = array("website", "login", "receipt", "launch", "oauth", "doc", "signup", "subscriptionrenew", "razorpay", "pay");
	    
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
			$CI->session->set_flashdata('warning', 'Your session has expired. Please sign in to continue.');
			redirect('portal', 'refresh');
        }

		try {

			$JwtData = JWT::decode($JwtEncoded, new Key(getenv('JWT_KEY'), 'HS256'));
			if(!empty($JwtData->key)) {

				$RedisData = $CI->redisservice->getCache($JwtData->key);
				if($RedisData->Error) {

					if (!empty($RedisData->IsConnectionError)) {
						// Redis host unreachable — show friendly error, never expose host details
						if ($CI->input->is_ajax_request()) {
							$CI->output
								->set_status_header(503)
								->set_content_type('application/json', 'utf-8')
								->set_output(json_encode(['Error' => true, 'Message' => 'Service temporarily unavailable. Please try again in a few minutes.']))
								->_display();
							exit;
						}
						$CI->load->view('errors/service_unavailable');
						exit;
					}

					$CI->session->set_flashdata('warning', 'Your session has expired. Please sign in to continue.');
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
						$activeKey  = $CI->redisservice->envKey('UserActiveSession_' . $userUID);
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

					// ── Subscription expiry check ─────────────────────────────────────────────
					$sub = $CI->pageData['JwtData']->Subscription ?? null;
					if ($sub) {
						$isExpired = !empty($sub->EndDate) && strtotime($sub->EndDate) < strtotime(date('Y-m-d'));
						if (!$isExpired && isset($sub->Status)) {
							$isExpired = ($sub->Status === 'Expired' || $sub->Status === 'Cancelled' || $sub->Status === 'Suspended');
						}
						if ($isExpired && $CI->router->fetch_class() !== 'signuppayment') {
							if ($CI->input->is_ajax_request()) {
								$CI->output
									->set_status_header(402)
									->set_content_type('application/json', 'utf-8')
									->set_output(json_encode([
										'Error'               => true,
										'SubscriptionExpired' => true,
										'Message'             => 'Your subscription has expired. Please renew your plan to continue.',
									]))
									->_display();
								exit;
							}
							redirect('subscribe', 'refresh');
						}
					}

					// ── PendingPayment gate (paid-plan signup, payment not yet completed) ──
					if (($sub->Status ?? '') === 'PendingPayment') {
						$_ppController = $CI->router->fetch_class();
						if ($_ppController !== 'signuppayment') {
							if ($CI->input->is_ajax_request()) {
								$CI->output
									->set_status_header(402)
									->set_content_type('application/json', 'utf-8')
									->set_output(json_encode([
										'Error'          => true,
										'PaymentPending' => true,
										'Message'        => 'Please complete your subscription payment to continue.',
									]))
									->_display();
								exit;
							}
							redirect('subscribe', 'refresh');
						}
					}

					// ── Onboarding check (Google signup only) ──────────────────
					$isOnboardingDone = (int)($CI->pageData['JwtData']->Org->IsOnboardingComplete ?? 1);
					if ($isOnboardingDone === 0 && $CI->router->fetch_class() !== 'onboarding') {
						if ($CI->input->is_ajax_request()) {
							$CI->output
								->set_status_header(403)
								->set_content_type('application/json', 'utf-8')
								->set_output(json_encode(['Error' => true, 'OnboardingRequired' => true, 'Message' => 'Please complete your organisation setup first.']))
								->_display();
							exit;
						}
						redirect('onboarding', 'refresh');
					}

					// ── Module access check — block URLs not in user's active plan ──────
					$_currentCtrl = strtolower($CI->router->fetch_class());
					$_sysControllers = [
						'dashboard', 'settings', 'subscription', 'organisation',
						'globally', 'auth', 'setpassword', 'onboarding',
						'signuppayment', 'razorpay', 'users', 'roles',
					];
					if (!in_array($_currentCtrl, $_sysControllers, true)) {
						$_subMenus  = $CI->redisservice->getUserCache('submenus') ?? [];
						$_mainMenus = $CI->redisservice->getUserCache('menus')	?? [];
						if (!empty($_subMenus) || !empty($_mainMenus)) {
							$_activeCtrl = [];
							foreach ($_subMenus as $_sm) {
								$_p = explode('/', ltrim($_sm->UrlPath ?? '', '/'));
								if (!empty($_p[0])) $_activeCtrl[$_p[0]] = true;
							}
							foreach ($_mainMenus as $_mm) {
								if (!empty($_mm->IsDirectLink) && !empty($_mm->DirectUrl)) {
									$_p = explode('/', ltrim($_mm->DirectUrl, '/'));
									if (!empty($_p[0])) $_activeCtrl[$_p[0]] = true;
								}
							}
							if (!isset($_activeCtrl[$_currentCtrl])) {
								if ($CI->input->is_ajax_request()) {
									$CI->output
										->set_status_header(403)
										->set_content_type('application/json', 'utf-8')
										->set_output(json_encode(['Error' => true, 'Message' => 'Access denied. This module is not available in your current plan.']))
										->_display();
									exit;
								}
								redirect('dashboard', 'refresh');
							}
						}
					}
					// ─────────────────────────────────────────────────────────────────

					// ── Activate any scheduled plan whose start date has arrived ────
					$_orgUID = (int)($CI->pageData['JwtData']->Org->OrgUID ?? 0);
					if ($_orgUID > 0 && !$CI->input->is_ajax_request()) {
						$_readDbMw = $CI->load->database('ReadDB', TRUE);
						$_readDbMw->db_debug = FALSE;
						$_scRow = $_readDbMw->select('SC.ScheduledChangeUID')
							->from('Billing.ScheduledPlanChangeTbl SC')
							->where('SC.OrgUID', $_orgUID)
							->where('SC.Status', 'Pending')
							->where('SC.ScheduledStartDate <=', date('Y-m-d'))
							->order_by('SC.ScheduledStartDate', 'ASC')
							->limit(1)
							->get()->row();
						if ($_scRow) {
							$CI->load->model('billingplan_model');
							/* Find admin role for this org */
							$_adminRoleRow = $_readDbMw->select('RoleUID')
								->from('UserRole.RolesTbl')
								->where('OrgUID', $_orgUID)
								->where('IsDeleted', 0)
								->order_by('RoleUID', 'ASC')
								->limit(1)
								->get()->row();
							$_adminRoleUID = $_adminRoleRow ? (int)$_adminRoleRow->RoleUID : 0;
							if ($_adminRoleUID > 0) {
								$CI->billingplan_model->activateScheduledPlan(
									(int)$_scRow->ScheduledChangeUID,
									$_orgUID,
									$_adminRoleUID
								);
							}
						}
					}
					// ─────────────────────────────────────────────────────────

					// Load per-user language file for t() helper
					$_uiLang = $CI->pageData['JwtData']->User->UILanguage ?? 'en';
					$CI->lang->load('app', $_uiLang === 'ta' ? 'tamil' : 'english');

					$uriString = $CI->uri->uri_string;
					if ($uriString === '' || $uriString === '/') {
						redirect('dashboard', 'refresh');
					}
					
				}

			} else {
				$CI->session->set_flashdata('warning', 'Your session has expired. Please sign in to continue.');
				redirect('portal', 'refresh');
			}

		} catch(\Firebase\JWT\ExpiredException $e) {

			$CI->session->set_flashdata('warning', 'Your session has expired. Please sign in to continue.');
			redirect('portal', 'refresh');

		} catch (\Firebase\JWT\SignatureInvalidException $e) {

			$CI->session->set_flashdata('danger', 'Invalid session detected. Please sign in again.');
			redirect('portal', 'refresh');

		} catch (Exception $e) {

			$CI->session->set_flashdata('danger', 'An unexpected error occurred. Please sign in again.');
			redirect('portal', 'refresh');

        }

	}
	
}
