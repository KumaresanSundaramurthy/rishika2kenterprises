<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Purchasepricehistory extends MY_Controller {

    public  $pageData = [];
    protected $EndReturnData;

    public function __construct() {
        parent::__construct();
        $this->load->model('purchasepricehistory_model');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * @param int   $pageNo
     * @param int   $limit
     * @param array $filter
     * @returns object
     */
    private function _fetchTableData(int $pageNo, int $limit, array $filter = []): object {
        $offset  = max(0, ($pageNo - 1) * $limit);
        $orgUID  = $this->_orgUID();
        $result  = $this->purchasepricehistory_model->getLogList($orgUID, $limit, $offset, $filter);
        $jwtData = $this->pageData['JwtData'];

        $r                 = new stdClass();
        $r->RecordHtmlData = $this->load->view('purchasepricehistory/list', [
            'DataLists'    => $result->rows,
            'SerialNumber' => $offset,
            'JwtData'      => $jwtData,
        ], TRUE);
        $r->Pagination     = $this->globalservice->buildPagePaginationHtml(
            '/purchasepricehistory/getPageDetails',
            $result->totalCount,
            $pageNo,
            $limit
        );
        $r->TotalCount = $result->totalCount;
        return $r;
    }

    // ── Pages ─────────────────────────────────────────────────────────────────

    /**
     * @returns void
     */
    public function index(): void {
        try {
            $limit  = $this->_rowLimit();
            $pd     = $this->_fetchTableData(1, $limit);
            $this->pageData['ModRowData']    = $pd->RecordHtmlData;
            $this->pageData['ModPagination'] = $pd->Pagination;
            $this->pageData['TotalCount']    = $pd->TotalCount;
            $this->load->view('purchasepricehistory/view', $this->pageData);
        } catch (Exception $e) {
            redirect('dashboard', 'refresh');
        }
    }

    // ── AJAX endpoints ────────────────────────────────────────────────────────

    /**
     * @param int $pageNo
     * @returns void
     */
    public function getPageDetails(int $pageNo = 1): void {
        $this->EndReturnData = new stdClass();
        try {
            $pageNo = max(1, (int)$pageNo);
            $limit  = (int)($this->input->post('RowLimit') ?: $this->_rowLimit());
            $filter = $this->input->post('Filter') ?: [];
            $pd     = $this->_fetchTableData($pageNo, $limit, $filter);

            $this->EndReturnData->Error          = FALSE;
            $this->EndReturnData->RecordHtmlData = $pd->RecordHtmlData;
            $this->EndReturnData->Pagination     = $pd->Pagination;
            $this->EndReturnData->TotalCount     = $pd->TotalCount;
        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    /**
     * Check if a date would be backdated for a product+vendor combination.
     * Returns IsBackdated: true|false.
     *
     * @returns void
     */
    public function checkBackdated(): void {
        $this->EndReturnData = new stdClass();
        try {
            $orgUID     = $this->_orgUID();
            $productUID = (int)($this->input->post('ProductUID') ?: 0);
            $vendorUID  = (int)($this->input->post('VendorUID')  ?: 0);
            $entryDate  = (string)($this->input->post('EntryDate') ?: '');
            $logUID     = (int)($this->input->post('PriceLogUID') ?: 0);

            $this->EndReturnData->Error      = FALSE;
            $this->EndReturnData->IsBackdated = ($productUID > 0 && $vendorUID > 0 && !empty($entryDate))
                ? $this->purchasepricehistory_model->isBackdated($orgUID, $productUID, $vendorUID, $entryDate, $logUID)
                : false;
        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    /**
     * Return product info (selling price, unit) for the manual entry form auto-fill.
     *
     * @returns void
     */
    public function getProductInfo(): void {
        $this->EndReturnData = new stdClass();
        try {
            $orgUID     = $this->_orgUID();
            $productUID = (int)($this->input->post('ProductUID') ?: 0);
            if ($productUID <= 0) throw new Exception('Invalid product.');

            $info = $this->purchasepricehistory_model->getProductInfoForForm($orgUID, $productUID);
            if (!$info) throw new Exception('Product not found.');

            $this->EndReturnData->Error        = FALSE;
            $this->EndReturnData->SellingPrice = (float)($info->SellingPrice ?? 0);
            $this->EndReturnData->Unit         = (string)($info->PrimaryUnitName ?? '');
        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    /**
     * Fetch a single manual entry for the edit form.
     *
     * @param int $logUID
     * @returns void
     */
    public function getEntry(int $logUID = 0): void {
        $this->EndReturnData = new stdClass();
        try {
            $orgUID = $this->_orgUID();
            $logUID = (int)$logUID ?: (int)($this->input->post('PriceLogUID') ?: 0);
            if ($logUID <= 0) throw new Exception('Invalid entry.');

            $entry = $this->purchasepricehistory_model->getLogByUID($orgUID, $logUID);
            if (!$entry)            throw new Exception('Entry not found.');
            if ((int)$entry->Source !== 2) throw new Exception('Auto entries cannot be edited.');

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Entry = $entry;
        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    /**
     * Save a manual price log entry (insert or update).
     *
     * @returns void
     */
    public function save(): void {
        $this->EndReturnData = new stdClass();
        try {
            $orgUID  = $this->_orgUID();
            $userUID = $this->_userUID();
            $post    = $this->input->post();
            $logUID  = (int)($post['PriceLogUID'] ?? 0);

            if ($logUID > 0) {
                $result = $this->purchasepricehistory_model->updateManual($orgUID, $userUID, $logUID, $post);
            } else {
                $result = $this->purchasepricehistory_model->saveManual($orgUID, $userUID, $post);
            }

            if ($result['Error']) {
                $this->EndReturnData->Error   = TRUE;
                $this->EndReturnData->Message = $result['Message'];
                if ($result['Message'] === 'BACKDATED_REQUIRES_REMARKS') {
                    $this->EndReturnData->IsBackdated = TRUE;
                }
                $this->globalservice->sendJsonResponse($this->EndReturnData);
                return;
            }

            $limit  = $this->_rowLimit();
            $filter = json_decode((string)($post['Filter'] ?? '{}'), true) ?: [];
            $pd     = $this->_fetchTableData(1, $limit, $filter);

            $this->EndReturnData->Error          = FALSE;
            $this->EndReturnData->Message        = $result['Message'];
            $this->EndReturnData->RecordHtmlData = $pd->RecordHtmlData;
            $this->EndReturnData->Pagination     = $pd->Pagination;
            $this->EndReturnData->TotalCount     = $pd->TotalCount;
        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }
}
