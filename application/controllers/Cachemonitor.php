<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Cachemonitor extends MY_Controller {

    public $pageData = [];
    private $EndReturnData;

    public function __construct() {
        parent::__construct();
    }

    // ── Page ─────────────────────────────────────────────────────────────────

    public function index() {
        if ((int)($this->pageData['JwtData']->User->RoleUID ?? 0) !== 1) {
            redirect('dashboard', 'refresh');
            return;
        }
        $this->pageData['PageTitle'] = 'Cache Monitor';
        $this->pageData['IsDevEnv']  = (ENVIRONMENT === 'development');
        $this->load->view('cachemonitor/view', $this->pageData);
    }

    // ── AJAX: verify dev password ─────────────────────────────────────────────

    public function verifyPassword() {
        $this->EndReturnData = new stdClass();
        try {
            if ((int)($this->pageData['JwtData']->User->RoleUID ?? 0) !== 1) {
                throw new Exception('Access denied.');
            }
            $entered = trim($this->input->post('password') ?? '');
            if (empty($entered)) throw new Exception('Password is required.');

            $orgUID = (int)$this->pageData['JwtData']->User->OrgUID;
            $this->load->model('organisation_model');
            $orgResult = $this->organisation_model->getOrgInfoCached($orgUID);
            $stored    = ($orgResult->Error === FALSE && !empty($orgResult->Data->DevPassword))
                         ? $orgResult->Data->DevPassword
                         : '';

            if (empty($stored)) throw new Exception('Developer password is not configured.');

            $decoded = base64_decode($stored, true);
            if ($decoded === false || $decoded !== $entered) {
                throw new Exception('Invalid password.');
            }

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Verified';

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── AJAX: Redis cache data ────────────────────────────────────────────────

    public function getRedisData() {
        $this->EndReturnData = new stdClass();
        try {
            if ((int)($this->pageData['JwtData']->User->RoleUID ?? 0) !== 1) {
                throw new Exception('Access denied.');
            }
            $keys = $this->redisservice->getAllKeysData();
            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Data    = $keys;
            $this->EndReturnData->Count   = count($keys);
        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── AJAX: delete a Redis key ──────────────────────────────────────────────

    public function deleteRedisKey() {
        $this->EndReturnData = new stdClass();
        try {
            if ((int)($this->pageData['JwtData']->User->RoleUID ?? 0) !== 1) {
                throw new Exception('Access denied.');
            }
            $key = trim($this->input->post('key') ?? '');
            if (empty($key)) throw new Exception('Key name is required.');

            $deleted = $this->redisservice->deleteExactKey($key);
            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Deleted = $deleted;
            $this->EndReturnData->Message = $deleted ? 'Key deleted.' : 'Key not found (already expired?).';
        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── AJAX: delete an Upstash key ───────────────────────────────────────────

    public function deleteUpstashKey() {
        $this->EndReturnData = new stdClass();
        try {
            if ((int)($this->pageData['JwtData']->User->RoleUID ?? 0) !== 1) {
                throw new Exception('Access denied.');
            }
            $key = trim($this->input->post('key') ?? '');
            if (empty($key)) throw new Exception('Key name is required.');

            $count = $this->upstashservice->del($key);
            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Deleted = $count > 0;
            $this->EndReturnData->Message = $count > 0 ? 'Key deleted.' : 'Key not found (already expired?).';
        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── AJAX: Upstash cache data ──────────────────────────────────────────────

    public function getUpstashData() {
        $this->EndReturnData = new stdClass();
        try {
            if ((int)($this->pageData['JwtData']->User->RoleUID ?? 0) !== 1) {
                throw new Exception('Access denied.');
            }
            if (!$this->upstashservice->isEnabled()) {
                $this->EndReturnData->Error   = FALSE;
                $this->EndReturnData->Data    = [];
                $this->EndReturnData->Count   = 0;
                $this->EndReturnData->Message = 'Upstash is not configured.';
            } else {
                $keys = $this->upstashservice->getAllKeysData();
                $this->EndReturnData->Error   = FALSE;
                $this->EndReturnData->Data    = $keys;
                $this->EndReturnData->Count   = count($keys);
            }
        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

}
