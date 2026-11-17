<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Storage page shell. The list, add / update / delete and the products page's storage filter call the
 * services API (api/storage) directly from js/storage/storage.js and js/products/products.js.
 */
class Storage extends MY_Controller {

    public $pageData = array();

    public function __construct() {
        parent::__construct();
    }

    public function index(): void {

        if (!$this->_loadPageTitle()) {
            $this->load->view('common/module_error', $this->pageData);
            return;
        }

        try {

            $GeneralSettings = $this->pageData['JwtData']->GenSettings ?? new stdClass();
            if ($GeneralSettings->EnableStorage != 1) {
                redirect('dashboard', 'refresh');
                return;
            }

            $orgUID = (int) $this->pageData['JwtData']->Org->OrgUID;
            $limit  = (int) ($GeneralSettings->RowLimit ?? 10);

            // Storage types and page 1 (Data, TotalCount, PageNo, RowLimit) — the JS draws the rows and pagination
            $resp = services_get('api/storage/getPageData?' . http_build_query([
                'orgId'    => $orgUID,
                'RowLimit' => $limit,
                'langCode' => $this->_uiLang(),
            ]), withJwt: true);
            $ok = $resp && empty($resp->Error);

            $this->pageData['StorageTypeInfo']  = $ok ? ($resp->StorageTypeInfo ?? []) : [];
            $this->pageData['StorageFirstPage'] = $ok ? [
                'Data'       => $resp->Data       ?? [],
                'TotalCount' => (int)($resp->TotalCount ?? 0),
                'PageNo'     => (int)($resp->PageNo     ?? 1),
                'RowLimit'   => (int)($resp->RowLimit   ?? $limit),
            ] : null;
            $this->pageData['ModRowData']    = '';
            $this->pageData['ModPagination'] = '';
            $this->pageData['ModAllCount']   = $ok ? (int)($resp->TotalCount ?? 0) : 0;

            $this->load->view('storage/view', $this->pageData);

        } catch (Exception $e) {
            notifyError('Storage::index', $e);
            redirect('dashboard', 'refresh');
        }

    }

}
