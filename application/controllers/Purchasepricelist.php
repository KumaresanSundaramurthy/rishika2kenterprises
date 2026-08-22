<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Purchasepricelist extends MY_Controller {

    public    $pageData = [];
    protected $EndReturnData;

    public function __construct() {
        parent::__construct();
        $this->load->model('purchasepricelist_model');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * @param int   $pageNo
     * @param int   $limit
     * @param array $filter
     * @returns object
     */
    private function _fetchTableData(int $pageNo, int $limit, array $filter = []): object {
        $offset    = max(0, ($pageNo - 1) * $limit);
        $orgUID    = $this->_orgUID();
        $branchUID = $this->_branchUID();
        $result    = $this->purchasepricelist_model->getPriceList($orgUID, $branchUID, $limit, $offset, $filter);
        $jwtData   = $this->pageData['JwtData'];

        $r                 = new stdClass();
        $r->RecordHtmlData = $this->load->view('purchasepricelist/list', [
            'DataLists'    => $result->rows,
            'SerialNumber' => $offset,
            'JwtData'      => $jwtData,
        ], TRUE);
        $r->Pagination     = $this->globalservice->buildPagePaginationHtml(
            '/purchasepricelist/getPageDetails',
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
            $this->load->view('purchasepricelist/view', $this->pageData);
        } catch (Exception $e) {
            notifyError('Purchasepricelist::index', $e);
            redirect('dashboard', 'refresh');
        }
    }

    // ── Detail page ───────────────────────────────────────────────────────────

    /**
     * @param int $priceListUID
     * @returns void
     */
    public function view(int $priceListUID = 0): void {
        try {
            $orgUID = $this->_orgUID();
            if ($priceListUID <= 0) throw new Exception('Invalid entry.');

            $entry = $this->purchasepricelist_model->getDetailEntry($orgUID, $priceListUID);
            if (!$entry) throw new Exception('Entry not found.');

            $limit   = $this->_rowLimit();
            $history = $this->purchasepricelist_model->getPriceHistory(
                $orgUID, (int)$entry->ProductUID, (int)$entry->VendorUID, $limit, 0
            );

            $this->pageData['Entry']         = $entry;
            $this->pageData['ModRowData']    = $this->load->view('purchasepricelist/detail_list', [
                'DataLists'    => $history->rows,
                'SerialNumber' => 0,
                'JwtData'      => $this->pageData['JwtData'],
            ], TRUE);
            $this->pageData['ModPagination'] = $this->globalservice->buildPagePaginationHtml(
                '/purchasepricelist/getDetailPageData/' . $priceListUID,
                $history->totalCount,
                1,
                $limit
            );
            $this->pageData['TotalCount'] = $history->totalCount;
            $this->load->view('purchasepricelist/detail_view', $this->pageData);

        } catch (Exception $e) {
            notifyError('Purchasepricelist::view', $e);
            redirect('purchasepricelist', 'refresh');
        }
    }

    /**
     * @param int $priceListUID
     * @returns void
     */
    public function getDetailPageData(int $priceListUID = 0): void {
        $this->EndReturnData = new stdClass();
        try {
            $orgUID = $this->_orgUID();
            if ($priceListUID <= 0) throw new Exception('Invalid entry.');

            $entry = $this->purchasepricelist_model->getDetailEntry($orgUID, $priceListUID);
            if (!$entry) throw new Exception('Entry not found.');

            $pageNo = max(1, (int)($this->input->post('PageNo') ?: 1));
            $limit  = (int)($this->input->post('RowLimit') ?: $this->_rowLimit());
            $offset = ($pageNo - 1) * $limit;

            $history = $this->purchasepricelist_model->getPriceHistory(
                $orgUID, (int)$entry->ProductUID, (int)$entry->VendorUID, $limit, $offset
            );

            $this->EndReturnData->Error          = FALSE;
            $this->EndReturnData->RecordHtmlData = $this->load->view('purchasepricelist/detail_list', [
                'DataLists'    => $history->rows,
                'SerialNumber' => $offset,
                'JwtData'      => $this->pageData['JwtData'],
            ], TRUE);
            $this->EndReturnData->Pagination     = $this->globalservice->buildPagePaginationHtml(
                '/purchasepricelist/getDetailPageData/' . $priceListUID,
                $history->totalCount,
                $pageNo,
                $limit
            );
            $this->EndReturnData->TotalCount     = $history->totalCount;
        } catch (Exception $e) {
            notifyError('Purchasepricelist::getDetailPageData', $e);
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
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
            notifyError('Purchasepricelist::getPageDetails', $e);
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }
}
