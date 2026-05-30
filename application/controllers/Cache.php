<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Cache API — authenticated endpoints for reading, writing, and refreshing
 * user-scoped Redis cache entries from the frontend.
 *
 * All routes are protected by the Middleware JWT hook.
 * Responses follow the standard {Error, Message, ...} shape.
 */
class Cache extends CI_Controller {

    private $allowedTypes = ['menus', 'submenus', 'modules', 'permissions', 'settings', 'userinfo'];

    public function __construct() {
        parent::__construct();
    }

    // GET /cache/get?type=settings
    public function get() {
        $type = $this->input->get('type');
        if (!$this->_validType($type)) {
            return $this->_json(['Error' => true, 'Message' => 'Invalid cache type'], 400);
        }
        $value = $this->redisservice->getUserCache($type);
        if ($value === null) {
            return $this->_json(['Error' => true, 'Message' => 'Cache miss', 'Type' => $type]);
        }
        $this->_json(['Error' => false, 'Message' => 'Cache hit', 'Type' => $type, 'Value' => $value]);
    }

    // POST /cache/set   body: { type, value, ttl? }
    public function set() {
        $post = $this->input->post();
        $type = $post['type'] ?? null;
        if (!$this->_validType($type)) {
            return $this->_json(['Error' => true, 'Message' => 'Invalid cache type'], 400);
        }
        $org     = $this->pageData['JwtData']->Org  ?? null;
        $userUID = $this->pageData['JwtData']->User->UserUID ?? null;
        if (!$userUID) {
            return $this->_json(['Error' => true, 'Message' => 'Unauthenticated'], 401);
        }
        $orgShortCode = $org->OrgShortCode ?? '';
        $orgToken     = $org->OrgToken     ?? '';
        $value  = $post['value'] ?? null;
        $ttl    = isset($post['ttl']) ? (int)$post['ttl'] : 0;
        $result = $this->redisservice->setUserCache($type, $userUID, $value, $ttl, $orgShortCode, $orgToken);
        $this->_json(['Error' => $result->Error, 'Message' => $result->Message, 'Type' => $type]);
    }

    // DELETE /cache/delete?type=settings
    public function delete() {
        $type    = $this->input->get('type');
        $org     = $this->pageData['JwtData']->Org  ?? null;
        $userUID = $this->pageData['JwtData']->User->UserUID ?? null;
        if (!$userUID) {
            return $this->_json(['Error' => true, 'Message' => 'Unauthenticated'], 401);
        }
        $orgShortCode = $org->OrgShortCode ?? '';
        $orgToken     = $org->OrgToken     ?? '';
        if ($type === 'all') {
            $this->redisservice->deleteAllUserCache($userUID, $orgShortCode, $orgToken);
            return $this->_json(['Error' => false, 'Message' => 'All user cache cleared']);
        }
        if (!$this->_validType($type)) {
            return $this->_json(['Error' => true, 'Message' => 'Invalid cache type'], 400);
        }
        $this->redisservice->deleteUserCache($type, $userUID);
        $this->_json(['Error' => false, 'Message' => 'Deleted', 'Type' => $type]);
    }

    // POST /cache/refresh  — rebuilds user cache from DB (calls globalservice)
    public function refresh() {
        try {
            $this->globalservice->refreshUserCache();
            $this->_json(['Error' => false, 'Message' => 'Cache refreshed']);
        } catch (Exception $e) {
            $this->_json(['Error' => true, 'Message' => $e->getMessage()], 500);
        }
    }

    // ─── helpers ─────────────────────────────────────────────────────────────

    private function _validType($type) {
        return in_array($type, $this->allowedTypes, true);
    }

    private function _json($data, $status = 200) {
        $this->output
            ->set_status_header($status)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
            ->_display();
        exit;
    }

}
