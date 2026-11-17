<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Todos extends MY_Controller {

    protected $pageModuleUID = 20;

    public function __construct() {
        parent::__construct();
    }

    // ── My Tasks list view ────────────────────────────────────────────────────
    public function index(): void {
        try {
            $this->_loadPageTitle($this->pageModuleUID);
        } catch (Exception $e) {
            notifyError('Todos::index', $e);
        }
        try {
            $this->load->view('todos/index', $this->pageData);
        } catch (Exception $e) {
            notifyError('Todos::index', $e);
            redirect('dashboard', 'refresh');
        }
    }
}