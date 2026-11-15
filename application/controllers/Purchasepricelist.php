<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Purchasepricelist extends MY_Controller {

    public    $pageData = [];

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
            if ($priceListUID <= 0) throw new Exception('Invalid entry.');
            $this->pageData['PriceListUID'] = $priceListUID;
            $this->load->view('purchasepricelist/detail_view', $this->pageData);
        } catch (Exception $e) {
            notifyError('Purchasepricelist::view', $e);
            redirect('purchasepricelist', 'refresh');
        }
    }

}