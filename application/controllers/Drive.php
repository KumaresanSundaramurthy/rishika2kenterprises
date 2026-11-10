<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Drive extends MY_Controller {

    private const QUOTA_BYTES = 104857600; // 100 MB per user

    public function __construct() {
        parent::__construct();
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    /**
     * @return bool
     */
    private function _isSuperAdmin(): bool {
        $u = $this->pageData['JwtData']->User;
        return (int)($u->RoleUID ?? 0) === 1 || (int)($u->IsAdmin ?? 0) === 1;
    }

    // ── Page load ─────────────────────────────────────────────────────────────

    public function index(): void {
        $this->_loadPageTitle();

        $orgUID       = $this->_orgUID();
        $isSuperAdmin = $this->_isSuperAdmin();

        $this->pageData['PageTitle']       = 'My Drive';
        $this->pageData['PageDescription'] = 'Your personal cloud storage space';
        $this->pageData['PageIcon']        = 'bx-cloud';
        $this->pageData['PageIconBg']      = '#e0f2fe';
        $this->pageData['PageIconColor']   = '#0369a1';
        $this->pageData['IsAdmin']         = $isSuperAdmin;
        $userUID                           = (int)$this->pageData['JwtData']->User->UserUID;
        $this->pageData['CurrentUserUID']  = $userUID;
        $this->pageData['QuotaBytes']      = self::QUOTA_BYTES;
        $jwtToken                          = $_COOKIE[getenv('JWT_COOKIE_NAME')] ?? '';
        $resp                              = $isSuperAdmin ? services_get(
            'api/mydrive/getPageData?orgId=' . $orgUID . '&userId=' . $userUID,
            ['Authorization: Bearer ' . $jwtToken]
        ) : null;
        $this->pageData['OrgUsers']        = $isSuperAdmin ? ($resp->Data->OrgUsers ?? []) : [];
        $this->pageData['CdnUrl']          = rtrim(
            getenv('FILE_UPLOAD') === 'amazonaws' ? getenv('CDN_URL') : getenv('CFLARE_R2_CDN'), '/'
        );

        $this->load->view('drive/view', $this->pageData);
    }

}