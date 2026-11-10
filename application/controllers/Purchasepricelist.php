<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Purchasepricelist extends MY_Controller {

    public    $pageData = [];
    protected $EndReturnData;

    public function __construct() {
        parent::__construct();
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

            $jwtToken = $_COOKIE[getenv('JWT_COOKIE_NAME')] ?? '';
            $resp     = services_get(
                'api/purchasepricelist/view/' . $priceListUID . '?orgId=' . $orgUID,
                ['Authorization: Bearer ' . $jwtToken]
            );
            $entry = $resp->Data->Entry ?? null;
            if (!$entry) throw new Exception('Entry not found.');

            $rows  = (array)($resp->Data->Data ?? []);
            $total = (int)($resp->Data->TotalCount ?? 0);
            $limit = (int)($resp->Data->RowLimit ?? $this->_rowLimit());

            $this->pageData['Entry']         = $entry;
            $this->pageData['ModRowData']    = $this->load->view('purchasepricelist/detail_list', [
                'DataLists'    => $rows,
                'SerialNumber' => 0,
                'JwtData'      => $this->pageData['JwtData'],
            ], TRUE);
            $this->pageData['ModPagination'] = $this->globalservice->buildPagePaginationHtml(
                '/purchasepricelist/getDetailPageData/' . $priceListUID,
                $total,
                1,
                $limit
            );
            $this->pageData['TotalCount'] = $total;
            $this->load->view('purchasepricelist/detail_view', $this->pageData);

        } catch (Exception $e) {
            notifyError('Purchasepricelist::view', $e);
            redirect('purchasepricelist', 'refresh');
        }
    }

}