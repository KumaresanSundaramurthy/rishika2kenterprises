<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Reports extends MY_Controller {

    public function __construct() {
        parent::__construct();
    }

    public function index(): void {
        if (empty($this->pageData['JwtData'])) {
            redirect('portal');
            return;
        }
        $this->pageData['PageTitle'] = 'Reports';
        $this->load->view('reports/index', $this->pageData);
    }

    public function daybook(): void {
        if (empty($this->pageData['JwtData'])) {
            redirect('portal');
            return;
        }
        $this->pageData['PageTitle'] = 'Day Book';
        $rawDate = $this->input->get('date') ?? '';
        $this->pageData['_initDate']   = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawDate) ? $rawDate : date('Y-m-d');
        $this->pageData['_initSearch'] = $this->input->get('search') ?? '';
        $this->load->view('reports/daybook', $this->pageData);
    }

    public function getDayBookData(): void {
        if (empty($this->pageData['JwtData'])) {
            $this->globalservice->sendJsonResponse((object)['Status' => 'Error', 'Message' => 'Unauthorised']);
            return;
        }

        $this->EndReturnData = new stdClass();
        try {
            $date   = $this->input->get('date') ?: date('Y-m-d');
            $orgUID = (int) $this->pageData['JwtData']->Org->OrgUID;

            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                throw new Exception('Invalid date format.');
            }

            $this->load->model('transactions_model');
            $entries  = $this->transactions_model->getDayBookEntries($date, $orgUID);
            $timezone = $this->pageData['JwtData']->GenSettings->Timezone ?? 'Asia/Kolkata';
            $utcTz    = new DateTimeZone('UTC');
            $orgTz    = new DateTimeZone($timezone);

            foreach ($entries as &$entry) {
                if (!empty($entry['EntryTime'])) {
                    $dt = new DateTime($entry['EntryTime'], $utcTz);
                    $dt->setTimezone($orgTz);
                    $entry['EntryTime'] = $dt->format('H:i:s');
                }
            }
            unset($entry);

            $this->EndReturnData->Status  = 'Success';
            $this->EndReturnData->entries = $entries;

        } catch (Exception $e) {
            $this->EndReturnData->Status  = 'Error';
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }
}
