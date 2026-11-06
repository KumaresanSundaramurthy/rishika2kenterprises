<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Purchasepricelist extends MY_Controller {

    public    $pageData = [];
    protected $EndReturnData;

    public function __construct() {
        parent::__construct();
        $this->load->model('purchasepricelist_model');
    }

    // ── Pages ─────────────────────────────────────────────────────────────────

    /**
     * @returns void
     */
    public function index(): void {
        try {
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

}