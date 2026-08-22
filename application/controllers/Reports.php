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
            $this->notifyError('Reports::getDayBookData', $e);
            $this->EndReturnData->Status  = 'Error';
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── Sales Summary ─────────────────────────────────────────────────────────

    public function salesSummary(): void
    {
        if (empty($this->pageData['JwtData'])) { redirect('portal'); return; }
        $rawFrom = $this->input->get('from') ?? '';
        $rawTo   = $this->input->get('to')   ?? '';
        $rawGrp  = $this->input->get('groupby') ?? '';
        $this->pageData['_initFrom']    = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawFrom) ? $rawFrom : date('Y-01-01');
        $this->pageData['_initTo']      = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawTo)   ? $rawTo   : date('Y-m-d');
        $this->pageData['_initGroupBy'] = in_array($rawGrp, ['month','quarter','year'])  ? $rawGrp  : 'month';
        $this->load->view('reports/sales_summary', $this->pageData);
    }

    public function getSalesSummaryData(): void
    {
        $this->EndReturnData = new stdClass();
        try {
            if (empty($this->pageData['JwtData'])) { throw new Exception('Unauthorised'); }
            $orgUID  = (int) $this->pageData['JwtData']->Org->OrgUID;
            $from    = $this->input->get('from')    ?: date('Y-01-01');
            $to      = $this->input->get('to')      ?: date('Y-m-d');
            $groupBy = $this->input->get('groupby') ?: 'month';
            if (!in_array($groupBy, ['month','quarter','year'])) { $groupBy = 'month'; }
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
                throw new Exception('Invalid date format.');
            }
            $this->load->model('reports_model');
            $rows = $this->reports_model->getSalesSummaryData($orgUID, $from, $to, $groupBy);
            $this->EndReturnData->Status = 'Success';
            $this->EndReturnData->rows   = $rows;
        } catch (Exception $e) {
            $this->notifyError('Reports::getSalesSummaryData', $e);
            $this->EndReturnData->Status  = 'Error';
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── Purchase Summary ──────────────────────────────────────────────────────

    public function purchaseSummary(): void
    {
        if (empty($this->pageData['JwtData'])) { redirect('portal'); return; }
        $rawFrom = $this->input->get('from') ?? '';
        $rawTo   = $this->input->get('to')   ?? '';
        $rawGrp  = $this->input->get('groupby') ?? '';
        $this->pageData['_initFrom']    = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawFrom) ? $rawFrom : date('Y-01-01');
        $this->pageData['_initTo']      = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawTo)   ? $rawTo   : date('Y-m-d');
        $this->pageData['_initGroupBy'] = in_array($rawGrp, ['month','quarter','year'])  ? $rawGrp  : 'month';
        $this->load->view('reports/purchase_summary', $this->pageData);
    }

    public function getPurchaseSummaryData(): void
    {
        $this->EndReturnData = new stdClass();
        try {
            if (empty($this->pageData['JwtData'])) { throw new Exception('Unauthorised'); }
            $orgUID  = (int) $this->pageData['JwtData']->Org->OrgUID;
            $from    = $this->input->get('from')    ?: date('Y-01-01');
            $to      = $this->input->get('to')      ?: date('Y-m-d');
            $groupBy = $this->input->get('groupby') ?: 'month';
            if (!in_array($groupBy, ['month','quarter','year'])) { $groupBy = 'month'; }
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
                throw new Exception('Invalid date format.');
            }
            $this->load->model('reports_model');
            $rows = $this->reports_model->getPurchaseSummaryData($orgUID, $from, $to, $groupBy);
            $this->EndReturnData->Status = 'Success';
            $this->EndReturnData->rows   = $rows;
        } catch (Exception $e) {
            $this->notifyError('Reports::getPurchaseSummaryData', $e);
            $this->EndReturnData->Status  = 'Error';
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── Monthly Summary ───────────────────────────────────────────────────────

    public function monthlySummary(): void
    {
        if (empty($this->pageData['JwtData'])) { redirect('portal'); return; }
        $rawYear = $this->input->get('year') ?? '';
        $initYear = (preg_match('/^\d{4}$/', $rawYear) && (int)$rawYear >= 2000 && (int)$rawYear <= 2099)
                    ? (int)$rawYear : (int)date('Y');
        $this->pageData['_initYear']    = $initYear;
        $this->pageData['_currentYear'] = (int)date('Y');
        $this->load->view('reports/monthly_summary', $this->pageData);
    }

    public function getMonthlySummaryData(): void
    {
        $this->EndReturnData = new stdClass();
        try {
            if (empty($this->pageData['JwtData'])) { throw new Exception('Unauthorised'); }
            $orgUID = (int) $this->pageData['JwtData']->Org->OrgUID;
            $year   = $this->input->get('year') ?: date('Y');
            if (!preg_match('/^\d{4}$/', $year)) { throw new Exception('Invalid year.'); }
            $from = $year . '-01-01';
            $to   = $year . '-12-31';
            $this->load->model('reports_model');
            $rows = $this->reports_model->getMonthlySummaryData($orgUID, $from, $to);
            $this->EndReturnData->Status = 'Success';
            $this->EndReturnData->rows   = $rows;
        } catch (Exception $e) {
            $this->notifyError('Reports::getMonthlySummaryData', $e);
            $this->EndReturnData->Status  = 'Error';
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── Payment Received ──────────────────────────────────────────────────────

    public function paymentReceived(): void
    {
        if (empty($this->pageData['JwtData'])) { redirect('portal'); return; }
        $rawFrom = $this->input->get('from') ?? '';
        $rawTo   = $this->input->get('to')   ?? '';
        $this->pageData['_initFrom'] = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawFrom) ? $rawFrom : date('Y-01-01');
        $this->pageData['_initTo']   = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawTo)   ? $rawTo   : date('Y-m-d');
        $this->load->view('reports/payment_received', $this->pageData);
    }

    public function getPaymentReceivedData(): void
    {
        $this->EndReturnData = new stdClass();
        try {
            if (empty($this->pageData['JwtData'])) { throw new Exception('Unauthorised'); }
            $orgUID = (int) $this->pageData['JwtData']->Org->OrgUID;
            $from   = $this->input->get('from') ?: date('Y-01-01');
            $to     = $this->input->get('to')   ?: date('Y-m-d');
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
                throw new Exception('Invalid date format.');
            }
            $this->load->model('reports_model');
            $rows = $this->reports_model->getPaymentReceivedData($orgUID, $from, $to);
            $this->EndReturnData->Status = 'Success';
            $this->EndReturnData->rows   = $rows;
        } catch (Exception $e) {
            $this->notifyError('Reports::getPaymentReceivedData', $e);
            $this->EndReturnData->Status  = 'Error';
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── Payment Made ──────────────────────────────────────────────────────────

    public function paymentMade(): void
    {
        if (empty($this->pageData['JwtData'])) { redirect('portal'); return; }
        $rawFrom = $this->input->get('from') ?? '';
        $rawTo   = $this->input->get('to')   ?? '';
        $this->pageData['_initFrom'] = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawFrom) ? $rawFrom : date('Y-01-01');
        $this->pageData['_initTo']   = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawTo)   ? $rawTo   : date('Y-m-d');
        $this->load->view('reports/payment_made', $this->pageData);
    }

    public function getPaymentMadeData(): void
    {
        $this->EndReturnData = new stdClass();
        try {
            if (empty($this->pageData['JwtData'])) { throw new Exception('Unauthorised'); }
            $orgUID = (int) $this->pageData['JwtData']->Org->OrgUID;
            $from   = $this->input->get('from') ?: date('Y-01-01');
            $to     = $this->input->get('to')   ?: date('Y-m-d');
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
                throw new Exception('Invalid date format.');
            }
            $this->load->model('reports_model');
            $rows = $this->reports_model->getPaymentMadeData($orgUID, $from, $to);
            $this->EndReturnData->Status = 'Success';
            $this->EndReturnData->rows   = $rows;
        } catch (Exception $e) {
            $this->notifyError('Reports::getPaymentMadeData', $e);
            $this->EndReturnData->Status  = 'Error';
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── P&L Statement ────────────────────────────────────────────────────────

    public function plStatement(): void
    {
        if (empty($this->pageData['JwtData'])) { redirect('portal'); return; }
        $rawFrom = $this->input->get('from') ?? '';
        $rawTo   = $this->input->get('to')   ?? '';
        $this->pageData['_initFrom'] = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawFrom) ? $rawFrom : date('Y-01-01');
        $this->pageData['_initTo']   = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawTo)   ? $rawTo   : date('Y-m-d');
        $this->load->view('reports/pl_statement', $this->pageData);
    }

    public function getPLStatementData(): void
    {
        $this->EndReturnData = new stdClass();
        try {
            if (empty($this->pageData['JwtData'])) { throw new Exception('Unauthorised'); }
            $orgUID = (int) $this->pageData['JwtData']->Org->OrgUID;
            $from   = $this->input->get('from') ?: date('Y-01-01');
            $to     = $this->input->get('to')   ?: date('Y-m-d');
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
                throw new Exception('Invalid date format.');
            }
            $this->load->model('reports_model');
            $rows = $this->reports_model->getPLStatementData($orgUID, $from, $to);
            $this->EndReturnData->Status = 'Success';
            $this->EndReturnData->rows   = $rows;
        } catch (Exception $e) {
            $this->notifyError('Reports::getPLStatementData', $e);
            $this->EndReturnData->Status  = 'Error';
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── Balance Sheet ─────────────────────────────────────────────────────────

    public function balanceSheet(): void
    {
        if (empty($this->pageData['JwtData'])) { redirect('portal'); return; }
        $rawAsOf = $this->input->get('asof') ?? '';
        $this->pageData['_initAsOf'] = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawAsOf) ? $rawAsOf : date('Y-m-d');
        $this->load->view('reports/balance_sheet', $this->pageData);
    }

    public function getBalanceSheetData(): void
    {
        $this->EndReturnData = new stdClass();
        try {
            if (empty($this->pageData['JwtData'])) { throw new Exception('Unauthorised'); }
            $orgUID = (int) $this->pageData['JwtData']->Org->OrgUID;
            $asOf   = $this->input->get('asof') ?: date('Y-m-d');
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $asOf)) {
                throw new Exception('Invalid date format.');
            }
            $this->load->model('reports_model');
            $rows = $this->reports_model->getBalanceSheetData($orgUID, $asOf);
            $this->EndReturnData->Status = 'Success';
            $this->EndReturnData->rows   = $rows;
        } catch (Exception $e) {
            $this->notifyError('Reports::getBalanceSheetData', $e);
            $this->EndReturnData->Status  = 'Error';
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── Trial Balance ─────────────────────────────────────────────────────────

    public function trialBalance(): void
    {
        if (empty($this->pageData['JwtData'])) { redirect('portal'); return; }
        $rawFrom = $this->input->get('from') ?? '';
        $rawTo   = $this->input->get('to')   ?? '';
        $this->pageData['_initFrom'] = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawFrom) ? $rawFrom : date('Y-01-01');
        $this->pageData['_initTo']   = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawTo)   ? $rawTo   : date('Y-m-d');
        $this->load->view('reports/trial_balance', $this->pageData);
    }

    public function getTrialBalanceData(): void
    {
        $this->EndReturnData = new stdClass();
        try {
            if (empty($this->pageData['JwtData'])) { throw new Exception('Unauthorised'); }
            $orgUID = (int) $this->pageData['JwtData']->Org->OrgUID;
            $from   = $this->input->get('from') ?: date('Y-01-01');
            $to     = $this->input->get('to')   ?: date('Y-m-d');
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
                throw new Exception('Invalid date format.');
            }
            $this->load->model('reports_model');
            $rows = $this->reports_model->getTrialBalanceReportData($orgUID, $from, $to);
            $this->EndReturnData->Status = 'Success';
            $this->EndReturnData->rows   = $rows;
        } catch (Exception $e) {
            $this->notifyError('Reports::getTrialBalanceData', $e);
            $this->EndReturnData->Status  = 'Error';
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── Customer Outstanding ─────────────────────────────────────────────────

    public function customerOutstanding(): void
    {
        if (empty($this->pageData['JwtData'])) { redirect('portal'); return; }
        $this->load->view('reports/customer_outstanding', $this->pageData);
    }

    public function getCustomerOutstandingData(): void
    {
        $this->EndReturnData = new stdClass();
        try {
            if (empty($this->pageData['JwtData'])) { throw new Exception('Unauthorised'); }
            $orgUID = (int) $this->pageData['JwtData']->Org->OrgUID;
            $this->load->model('reports_model');
            $rows = $this->reports_model->getCustomerOutstandingData($orgUID);
            $this->EndReturnData->Status = 'Success';
            $this->EndReturnData->rows   = $rows;
        } catch (Exception $e) {
            $this->notifyError('Reports::getCustomerOutstandingData', $e);
            $this->EndReturnData->Status  = 'Error';
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── Customer Ledger ───────────────────────────────────────────────────────

    public function customerLedger(): void
    {
        if (empty($this->pageData['JwtData'])) { redirect('portal'); return; }
        $rawFrom = $this->input->get('from') ?? '';
        $rawTo   = $this->input->get('to')   ?? '';
        $this->pageData['_initFrom'] = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawFrom) ? $rawFrom : date('Y-01-01');
        $this->pageData['_initTo']   = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawTo)   ? $rawTo   : date('Y-m-d');
        $this->load->view('reports/customer_ledger', $this->pageData);
    }

    public function getCustomerLedgerData(): void
    {
        $this->EndReturnData = new stdClass();
        try {
            if (empty($this->pageData['JwtData'])) { throw new Exception('Unauthorised'); }
            $orgUID      = (int) $this->pageData['JwtData']->Org->OrgUID;
            $customerUID = (int) ($this->input->get('customerUID') ?: 0);
            $from        = $this->input->get('from') ?: date('Y-01-01');
            $to          = $this->input->get('to')   ?: date('Y-m-d');
            if ($customerUID <= 0) { throw new Exception('Please select a customer.'); }
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
                throw new Exception('Invalid date format.');
            }
            $this->load->model('reports_model');
            $rows    = $this->reports_model->getCustomerLedgerData($orgUID, $customerUID, $from, $to);
            $opening = $this->reports_model->getCustomerLedgerOpening($orgUID, $customerUID, $from);
            $this->EndReturnData->Status  = 'Success';
            $this->EndReturnData->rows    = $rows;
            $this->EndReturnData->opening = $opening;
        } catch (Exception $e) {
            $this->notifyError('Reports::getCustomerLedgerData', $e);
            $this->EndReturnData->Status  = 'Error';
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── Customer Ageing ───────────────────────────────────────────────────────

    public function customerAgeing(): void
    {
        if (empty($this->pageData['JwtData'])) { redirect('portal'); return; }
        $this->load->view('reports/customer_ageing', $this->pageData);
    }

    public function getCustomerAgeingData(): void
    {
        $this->EndReturnData = new stdClass();
        try {
            if (empty($this->pageData['JwtData'])) { throw new Exception('Unauthorised'); }
            $orgUID = (int) $this->pageData['JwtData']->Org->OrgUID;
            $this->load->model('reports_model');
            $rows = $this->reports_model->getCustomerAgeingData($orgUID);
            $this->EndReturnData->Status = 'Success';
            $this->EndReturnData->rows   = $rows;
        } catch (Exception $e) {
            $this->notifyError('Reports::getCustomerAgeingData', $e);
            $this->EndReturnData->Status  = 'Error';
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── Supplier Outstanding ──────────────────────────────────────────────────

    public function supplierOutstanding(): void
    {
        if (empty($this->pageData['JwtData'])) { redirect('portal'); return; }
        $this->load->view('reports/supplier_outstanding', $this->pageData);
    }

    public function getSupplierOutstandingData(): void
    {
        $this->EndReturnData = new stdClass();
        try {
            if (empty($this->pageData['JwtData'])) { throw new Exception('Unauthorised'); }
            $orgUID = (int) $this->pageData['JwtData']->Org->OrgUID;
            $this->load->model('reports_model');
            $rows = $this->reports_model->getSupplierOutstandingData($orgUID);
            $this->EndReturnData->Status = 'Success';
            $this->EndReturnData->rows   = $rows;
        } catch (Exception $e) {
            $this->notifyError('Reports::getSupplierOutstandingData', $e);
            $this->EndReturnData->Status  = 'Error';
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── Supplier Ledger ───────────────────────────────────────────────────────

    public function supplierLedger(): void
    {
        if (empty($this->pageData['JwtData'])) { redirect('portal'); return; }
        $rawFrom = $this->input->get('from') ?? '';
        $rawTo   = $this->input->get('to')   ?? '';
        $this->pageData['_initFrom'] = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawFrom) ? $rawFrom : date('Y-01-01');
        $this->pageData['_initTo']   = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawTo)   ? $rawTo   : date('Y-m-d');
        $this->load->view('reports/supplier_ledger', $this->pageData);
    }

    public function getSupplierLedgerData(): void
    {
        $this->EndReturnData = new stdClass();
        try {
            if (empty($this->pageData['JwtData'])) { throw new Exception('Unauthorised'); }
            $orgUID    = (int) $this->pageData['JwtData']->Org->OrgUID;
            $vendorUID = (int) ($this->input->get('vendorUID') ?: 0);
            $from      = $this->input->get('from') ?: date('Y-01-01');
            $to        = $this->input->get('to')   ?: date('Y-m-d');
            if ($vendorUID <= 0) { throw new Exception('Please select a supplier.'); }
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
                throw new Exception('Invalid date format.');
            }
            $this->load->model('reports_model');
            $rows    = $this->reports_model->getSupplierLedgerData($orgUID, $vendorUID, $from, $to);
            $opening = $this->reports_model->getSupplierLedgerOpening($orgUID, $vendorUID, $from);
            $this->EndReturnData->Status  = 'Success';
            $this->EndReturnData->rows    = $rows;
            $this->EndReturnData->opening = $opening;
        } catch (Exception $e) {
            $this->notifyError('Reports::getSupplierLedgerData', $e);
            $this->EndReturnData->Status  = 'Error';
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── Item Wise Sales ───────────────────────────────────────────────────────

    public function itemWiseSales(): void
    {
        if (empty($this->pageData['JwtData'])) { redirect('portal'); return; }
        $rawFrom = $this->input->get('from') ?? '';
        $rawTo   = $this->input->get('to')   ?? '';
        $this->pageData['_iwsInitFrom'] = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawFrom) ? $rawFrom : date('Y-01-01');
        $this->pageData['_iwsInitTo']   = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawTo)   ? $rawTo   : date('Y-m-d');
        $this->load->view('reports/item_wise_sales', $this->pageData);
    }

    public function getItemWiseSalesData(): void
    {
        $this->EndReturnData = new stdClass();
        try {
            if (empty($this->pageData['JwtData'])) { throw new Exception('Unauthorised'); }
            $orgUID = (int) $this->pageData['JwtData']->Org->OrgUID;
            $from   = $this->input->get('from') ?: date('Y-01-01');
            $to     = $this->input->get('to')   ?: date('Y-m-d');
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
                throw new Exception('Invalid date format.');
            }
            $this->load->model('reports_model');
            $rows = $this->reports_model->getItemWiseSalesData($orgUID, $from, $to);
            $this->EndReturnData->Status = 'Success';
            $this->EndReturnData->rows   = $rows;
        } catch (Exception $e) {
            $this->notifyError('Reports::getItemWiseSalesData', $e);
            $this->EndReturnData->Status  = 'Error';
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── Item Wise Purchase ────────────────────────────────────────────────────

    public function itemWisePurchase(): void
    {
        if (empty($this->pageData['JwtData'])) { redirect('portal'); return; }
        $rawFrom = $this->input->get('from') ?? '';
        $rawTo   = $this->input->get('to')   ?? '';
        $this->pageData['_iwpInitFrom'] = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawFrom) ? $rawFrom : date('Y-01-01');
        $this->pageData['_iwpInitTo']   = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawTo)   ? $rawTo   : date('Y-m-d');
        $this->load->view('reports/item_wise_purchase', $this->pageData);
    }

    public function getItemWisePurchaseData(): void
    {
        $this->EndReturnData = new stdClass();
        try {
            if (empty($this->pageData['JwtData'])) { throw new Exception('Unauthorised'); }
            $orgUID = (int) $this->pageData['JwtData']->Org->OrgUID;
            $from   = $this->input->get('from') ?: date('Y-01-01');
            $to     = $this->input->get('to')   ?: date('Y-m-d');
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
                throw new Exception('Invalid date format.');
            }
            $this->load->model('reports_model');
            $rows = $this->reports_model->getItemWisePurchaseData($orgUID, $from, $to);
            $this->EndReturnData->Status = 'Success';
            $this->EndReturnData->rows   = $rows;
        } catch (Exception $e) {
            $this->notifyError('Reports::getItemWisePurchaseData', $e);
            $this->EndReturnData->Status  = 'Error';
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── Low Stock Alert ───────────────────────────────────────────────────────

    public function lowStockAlert(): void
    {
        if (empty($this->pageData['JwtData'])) { redirect('portal'); return; }
        $this->load->view('reports/low_stock_alert', $this->pageData);
    }

    public function getLowStockAlertData(): void
    {
        $this->EndReturnData = new stdClass();
        try {
            if (empty($this->pageData['JwtData'])) { throw new Exception('Unauthorised'); }
            $orgUID = (int) $this->pageData['JwtData']->Org->OrgUID;
            $this->load->model('reports_model');
            $rows = $this->reports_model->getLowStockAlertData($orgUID);
            $this->EndReturnData->Status = 'Success';
            $this->EndReturnData->rows   = $rows;
        } catch (Exception $e) {
            $this->notifyError('Reports::getLowStockAlertData', $e);
            $this->EndReturnData->Status  = 'Error';
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── Stock Summary ─────────────────────────────────────────────────────────

    public function stockSummary(): void
    {
        if (empty($this->pageData['JwtData'])) { redirect('portal'); return; }
        $this->load->view('reports/stock_summary', $this->pageData);
    }

    public function getStockSummaryData(): void
    {
        $this->EndReturnData = new stdClass();
        try {
            if (empty($this->pageData['JwtData'])) { throw new Exception('Unauthorised'); }
            $orgUID = (int) $this->pageData['JwtData']->Org->OrgUID;
            $this->load->model('reports_model');
            $rows = $this->reports_model->getStockSummaryData($orgUID);
            $this->EndReturnData->Status = 'Success';
            $this->EndReturnData->rows   = $rows;
        } catch (Exception $e) {
            $this->notifyError('Reports::getStockSummaryData', $e);
            $this->EndReturnData->Status  = 'Error';
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── Invoice Item-wise ─────────────────────────────────────────────────────

    public function invoiceItemwise(): void
    {
        if (empty($this->pageData['JwtData'])) { redirect('portal'); return; }
        $rawFrom = $this->input->get('from') ?? '';
        $rawTo   = $this->input->get('to')   ?? '';
        $this->pageData['_iiInitFrom'] = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawFrom) ? $rawFrom : date('Y-01-01');
        $this->pageData['_iiInitTo']   = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawTo)   ? $rawTo   : date('Y-m-d');
        $this->load->view('reports/invoice_itemwise', $this->pageData);
    }

    public function getInvoiceItemwiseData(): void
    {
        $this->EndReturnData = new stdClass();
        try {
            if (empty($this->pageData['JwtData'])) { throw new Exception('Unauthorised'); }
            $orgUID = (int) $this->pageData['JwtData']->Org->OrgUID;
            $from   = $this->input->get('from') ?: date('Y-01-01');
            $to     = $this->input->get('to')   ?: date('Y-m-d');
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
                throw new Exception('Invalid date format.');
            }
            $this->load->model('reports_model');
            $this->EndReturnData->Status = 'Success';
            $this->EndReturnData->rows   = $this->reports_model->getInvoiceItemwiseData($orgUID, $from, $to);
        } catch (Exception $e) {
            $this->notifyError('Reports::getInvoiceItemwiseData', $e);
            $this->EndReturnData->Status  = 'Error';
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── Purchase Item-wise ────────────────────────────────────────────────────

    public function purchaseItemwise(): void
    {
        if (empty($this->pageData['JwtData'])) { redirect('portal'); return; }
        $rawFrom = $this->input->get('from') ?? '';
        $rawTo   = $this->input->get('to')   ?? '';
        $this->pageData['_piInitFrom'] = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawFrom) ? $rawFrom : date('Y-01-01');
        $this->pageData['_piInitTo']   = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawTo)   ? $rawTo   : date('Y-m-d');
        $this->load->view('reports/purchase_itemwise', $this->pageData);
    }

    public function getPurchaseItemwiseData(): void
    {
        $this->EndReturnData = new stdClass();
        try {
            if (empty($this->pageData['JwtData'])) { throw new Exception('Unauthorised'); }
            $orgUID = (int) $this->pageData['JwtData']->Org->OrgUID;
            $from   = $this->input->get('from') ?: date('Y-01-01');
            $to     = $this->input->get('to')   ?: date('Y-m-d');
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
                throw new Exception('Invalid date format.');
            }
            $this->load->model('reports_model');
            $this->EndReturnData->Status = 'Success';
            $this->EndReturnData->rows   = $this->reports_model->getPurchaseItemwiseData($orgUID, $from, $to);
        } catch (Exception $e) {
            $this->notifyError('Reports::getPurchaseItemwiseData', $e);
            $this->EndReturnData->Status  = 'Error';
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── Sales Return Item-wise ────────────────────────────────────────────────

    public function salesReturnItemwise(): void
    {
        if (empty($this->pageData['JwtData'])) { redirect('portal'); return; }
        $rawFrom = $this->input->get('from') ?? '';
        $rawTo   = $this->input->get('to')   ?? '';
        $this->pageData['_srInitFrom'] = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawFrom) ? $rawFrom : date('Y-01-01');
        $this->pageData['_srInitTo']   = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawTo)   ? $rawTo   : date('Y-m-d');
        $this->load->view('reports/sales_return_itemwise', $this->pageData);
    }

    public function getSalesReturnItemwiseData(): void
    {
        $this->EndReturnData = new stdClass();
        try {
            if (empty($this->pageData['JwtData'])) { throw new Exception('Unauthorised'); }
            $orgUID = (int) $this->pageData['JwtData']->Org->OrgUID;
            $from   = $this->input->get('from') ?: date('Y-01-01');
            $to     = $this->input->get('to')   ?: date('Y-m-d');
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
                throw new Exception('Invalid date format.');
            }
            $this->load->model('reports_model');
            $this->EndReturnData->Status = 'Success';
            $this->EndReturnData->rows   = $this->reports_model->getSalesReturnItemwiseData($orgUID, $from, $to);
        } catch (Exception $e) {
            $this->notifyError('Reports::getSalesReturnItemwiseData', $e);
            $this->EndReturnData->Status  = 'Error';
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── Purchase Return Item-wise ─────────────────────────────────────────────

    public function purchaseReturnItemwise(): void
    {
        if (empty($this->pageData['JwtData'])) { redirect('portal'); return; }
        $rawFrom = $this->input->get('from') ?? '';
        $rawTo   = $this->input->get('to')   ?? '';
        $this->pageData['_prInitFrom'] = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawFrom) ? $rawFrom : date('Y-01-01');
        $this->pageData['_prInitTo']   = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawTo)   ? $rawTo   : date('Y-m-d');
        $this->load->view('reports/purchase_return_itemwise', $this->pageData);
    }

    public function getPurchaseReturnItemwiseData(): void
    {
        $this->EndReturnData = new stdClass();
        try {
            if (empty($this->pageData['JwtData'])) { throw new Exception('Unauthorised'); }
            $orgUID = (int) $this->pageData['JwtData']->Org->OrgUID;
            $from   = $this->input->get('from') ?: date('Y-01-01');
            $to     = $this->input->get('to')   ?: date('Y-m-d');
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
                throw new Exception('Invalid date format.');
            }
            $this->load->model('reports_model');
            $this->EndReturnData->Status = 'Success';
            $this->EndReturnData->rows   = $this->reports_model->getPurchaseReturnItemwiseData($orgUID, $from, $to);
        } catch (Exception $e) {
            $this->notifyError('Reports::getPurchaseReturnItemwiseData', $e);
            $this->EndReturnData->Status  = 'Error';
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── Sales Register ───────────────────────────────────────────────────────

    public function salesRegister(): void
    {
        if (empty($this->pageData['JwtData'])) { redirect('portal'); return; }
        $rawFrom = $this->input->get('from') ?? '';
        $rawTo   = $this->input->get('to')   ?? '';
        $this->pageData['_sregInitFrom'] = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawFrom) ? $rawFrom : date('Y-01-01');
        $this->pageData['_sregInitTo']   = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawTo)   ? $rawTo   : date('Y-m-d');
        $this->load->view('reports/sales_register', $this->pageData);
    }

    public function getSalesRegisterData(): void
    {
        $this->EndReturnData = new stdClass();
        try {
            if (empty($this->pageData['JwtData'])) { throw new Exception('Unauthorised'); }
            $orgUID = (int) $this->pageData['JwtData']->Org->OrgUID;
            $from   = $this->input->get('from') ?: date('Y-01-01');
            $to     = $this->input->get('to')   ?: date('Y-m-d');
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
                throw new Exception('Invalid date format.');
            }
            $this->load->model('reports_model');
            $this->EndReturnData->Status = 'Success';
            $this->EndReturnData->rows   = $this->reports_model->getSalesRegisterData($orgUID, $from, $to);
        } catch (Exception $e) {
            $this->notifyError('Reports::getSalesRegisterData', $e);
            $this->EndReturnData->Status  = 'Error';
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── Purchase Register ─────────────────────────────────────────────────────

    public function purchaseRegister(): void
    {
        if (empty($this->pageData['JwtData'])) { redirect('portal'); return; }
        $rawFrom = $this->input->get('from') ?? '';
        $rawTo   = $this->input->get('to')   ?? '';
        $this->pageData['_pregInitFrom'] = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawFrom) ? $rawFrom : date('Y-01-01');
        $this->pageData['_pregInitTo']   = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawTo)   ? $rawTo   : date('Y-m-d');
        $this->load->view('reports/purchase_register', $this->pageData);
    }

    public function getPurchaseRegisterData(): void
    {
        $this->EndReturnData = new stdClass();
        try {
            if (empty($this->pageData['JwtData'])) { throw new Exception('Unauthorised'); }
            $orgUID = (int) $this->pageData['JwtData']->Org->OrgUID;
            $from   = $this->input->get('from') ?: date('Y-01-01');
            $to     = $this->input->get('to')   ?: date('Y-m-d');
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
                throw new Exception('Invalid date format.');
            }
            $this->load->model('reports_model');
            $this->EndReturnData->Status = 'Success';
            $this->EndReturnData->rows   = $this->reports_model->getPurchaseRegisterData($orgUID, $from, $to);
        } catch (Exception $e) {
            $this->notifyError('Reports::getPurchaseRegisterData', $e);
            $this->EndReturnData->Status  = 'Error';
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── Sales Return Register ─────────────────────────────────────────────────

    public function salesReturnRegister(): void
    {
        if (empty($this->pageData['JwtData'])) { redirect('portal'); return; }
        $rawFrom = $this->input->get('from') ?? '';
        $rawTo   = $this->input->get('to')   ?? '';
        $this->pageData['_srrInitFrom'] = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawFrom) ? $rawFrom : date('Y-01-01');
        $this->pageData['_srrInitTo']   = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawTo)   ? $rawTo   : date('Y-m-d');
        $this->load->view('reports/sales_return_register', $this->pageData);
    }

    public function getSalesReturnRegisterData(): void
    {
        $this->EndReturnData = new stdClass();
        try {
            if (empty($this->pageData['JwtData'])) { throw new Exception('Unauthorised'); }
            $orgUID = (int) $this->pageData['JwtData']->Org->OrgUID;
            $from   = $this->input->get('from') ?: date('Y-01-01');
            $to     = $this->input->get('to')   ?: date('Y-m-d');
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
                throw new Exception('Invalid date format.');
            }
            $this->load->model('reports_model');
            $this->EndReturnData->Status = 'Success';
            $this->EndReturnData->rows   = $this->reports_model->getSalesReturnRegisterData($orgUID, $from, $to);
        } catch (Exception $e) {
            $this->notifyError('Reports::getSalesReturnRegisterData', $e);
            $this->EndReturnData->Status  = 'Error';
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── Purchase Return Register ──────────────────────────────────────────────

    public function purchaseReturnRegister(): void
    {
        if (empty($this->pageData['JwtData'])) { redirect('portal'); return; }
        $rawFrom = $this->input->get('from') ?? '';
        $rawTo   = $this->input->get('to')   ?? '';
        $this->pageData['_prrInitFrom'] = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawFrom) ? $rawFrom : date('Y-01-01');
        $this->pageData['_prrInitTo']   = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawTo)   ? $rawTo   : date('Y-m-d');
        $this->load->view('reports/purchase_return_register', $this->pageData);
    }

    public function getPurchaseReturnRegisterData(): void
    {
        $this->EndReturnData = new stdClass();
        try {
            if (empty($this->pageData['JwtData'])) { throw new Exception('Unauthorised'); }
            $orgUID = (int) $this->pageData['JwtData']->Org->OrgUID;
            $from   = $this->input->get('from') ?: date('Y-01-01');
            $to     = $this->input->get('to')   ?: date('Y-m-d');
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
                throw new Exception('Invalid date format.');
            }
            $this->load->model('reports_model');
            $this->EndReturnData->Status = 'Success';
            $this->EndReturnData->rows   = $this->reports_model->getPurchaseReturnRegisterData($orgUID, $from, $to);
        } catch (Exception $e) {
            $this->notifyError('Reports::getPurchaseReturnRegisterData', $e);
            $this->EndReturnData->Status  = 'Error';
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── Delivery Challan Register ─────────────────────────────────────────────

    public function deliveryChallanRegister(): void
    {
        if (empty($this->pageData['JwtData'])) { redirect('portal'); return; }
        $rawFrom = $this->input->get('from') ?? '';
        $rawTo   = $this->input->get('to')   ?? '';
        $this->pageData['_dcrInitFrom'] = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawFrom) ? $rawFrom : date('Y-01-01');
        $this->pageData['_dcrInitTo']   = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawTo)   ? $rawTo   : date('Y-m-d');
        $this->load->view('reports/delivery_challan_register', $this->pageData);
    }

    public function getDeliveryChallanRegisterData(): void
    {
        $this->EndReturnData = new stdClass();
        try {
            if (empty($this->pageData['JwtData'])) { throw new Exception('Unauthorised'); }
            $orgUID = (int) $this->pageData['JwtData']->Org->OrgUID;
            $from   = $this->input->get('from') ?: date('Y-01-01');
            $to     = $this->input->get('to')   ?: date('Y-m-d');
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
                throw new Exception('Invalid date format.');
            }
            $this->load->model('reports_model');
            $this->EndReturnData->Status = 'Success';
            $this->EndReturnData->rows   = $this->reports_model->getDeliveryChallanRegisterData($orgUID, $from, $to);
        } catch (Exception $e) {
            $this->notifyError('Reports::getDeliveryChallanRegisterData', $e);
            $this->EndReturnData->Status  = 'Error';
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── Expense Register ──────────────────────────────────────────────────────

    public function expenseRegister(): void
    {
        if (empty($this->pageData['JwtData'])) { redirect('portal'); return; }
        $rawFrom = $this->input->get('from') ?? '';
        $rawTo   = $this->input->get('to')   ?? '';
        $this->pageData['_exrInitFrom'] = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawFrom) ? $rawFrom : date('Y-01-01');
        $this->pageData['_exrInitTo']   = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawTo)   ? $rawTo   : date('Y-m-d');
        $this->load->view('reports/expense_register', $this->pageData);
    }

    public function getExpenseRegisterData(): void
    {
        $this->EndReturnData = new stdClass();
        try {
            if (empty($this->pageData['JwtData'])) { throw new Exception('Unauthorised'); }
            $orgUID = (int) $this->pageData['JwtData']->Org->OrgUID;
            $from   = $this->input->get('from') ?: date('Y-01-01');
            $to     = $this->input->get('to')   ?: date('Y-m-d');
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
                throw new Exception('Invalid date format.');
            }
            $this->load->model('reports_model');
            $this->EndReturnData->Status = 'Success';
            $this->EndReturnData->rows   = $this->reports_model->getExpenseRegisterData($orgUID, $from, $to);
        } catch (Exception $e) {
            $this->notifyError('Reports::getExpenseRegisterData', $e);
            $this->EndReturnData->Status  = 'Error';
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── Tax Reports ───────────────────────────────────────────────────────────

    public function gstr1(): void
    {
        if (empty($this->pageData['JwtData'])) { redirect('portal'); return; }
        $orgUID = (int) $this->pageData['JwtData']->Org->OrgUID;
        $month  = (int) ($this->input->get('month') ?? date('n'));
        $year   = (int) ($this->input->get('year')  ?? date('Y'));
        $month  = ($month >= 1 && $month <= 12) ? $month : (int) date('n');
        $year   = ($year  >= 2000 && $year <= 2099) ? $year : (int) date('Y');
        $this->pageData['_gstrMonth'] = $month;
        $this->pageData['_gstrYear']  = $year;
        $this->load->view('reports/gstr1', $this->pageData);
    }

    public function getGstr1Data(): void
    {
        $this->EndReturnData = new stdClass();
        try {
            if (empty($this->pageData['JwtData'])) { throw new Exception('Unauthorised'); }
            $orgUID = (int) $this->pageData['JwtData']->Org->OrgUID;
            $month  = (int) ($this->input->get('month') ?? date('n'));
            $year   = (int) ($this->input->get('year')  ?? date('Y'));
            $month  = ($month >= 1 && $month <= 12) ? $month : (int) date('n');
            $year   = ($year  >= 2000 && $year <= 2099) ? $year : (int) date('Y');
            $this->load->model('reports_model');
            $data   = $this->reports_model->getGstr1Data($orgUID, $month, $year);
            $this->EndReturnData->Status = 'Success';
            $this->EndReturnData->b2b    = $data['b2b']  ?? [];
            $this->EndReturnData->b2cs   = $data['b2cs'] ?? [];
            $this->EndReturnData->cdnr   = $data['cdnr'] ?? [];
        } catch (Exception $e) {
            $this->notifyError('Reports::getGstr1Data', $e);
            $this->EndReturnData->Status  = 'Error';
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    public function gstr2b(): void
    {
        if (empty($this->pageData['JwtData'])) { redirect('portal'); return; }
        $orgUID = (int) $this->pageData['JwtData']->Org->OrgUID;
        $month  = (int) ($this->input->get('month') ?? date('n'));
        $year   = (int) ($this->input->get('year')  ?? date('Y'));
        $month  = ($month >= 1 && $month <= 12) ? $month : (int) date('n');
        $year   = ($year  >= 2000 && $year <= 2099) ? $year : (int) date('Y');
        $this->pageData['_gstrMonth'] = $month;
        $this->pageData['_gstrYear']  = $year;
        $this->load->view('reports/gstr2b', $this->pageData);
    }

    public function getGstr2bData(): void
    {
        $this->EndReturnData = new stdClass();
        try {
            if (empty($this->pageData['JwtData'])) { throw new Exception('Unauthorised'); }
            $orgUID = (int) $this->pageData['JwtData']->Org->OrgUID;
            $month  = (int) ($this->input->get('month') ?? date('n'));
            $year   = (int) ($this->input->get('year')  ?? date('Y'));
            $month  = ($month >= 1 && $month <= 12) ? $month : (int) date('n');
            $year   = ($year  >= 2000 && $year <= 2099) ? $year : (int) date('Y');
            $this->load->model('reports_model');
            $rows   = $this->reports_model->getGstr2bData($orgUID, $month, $year);
            $this->EndReturnData->Status = 'Success';
            $this->EndReturnData->rows   = $rows;
        } catch (Exception $e) {
            $this->notifyError('Reports::getGstr2bData', $e);
            $this->EndReturnData->Status  = 'Error';
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    public function gstr3b(): void
    {
        if (empty($this->pageData['JwtData'])) { redirect('portal'); return; }
        $orgUID = (int) $this->pageData['JwtData']->Org->OrgUID;
        $month  = (int) ($this->input->get('month') ?? date('n'));
        $year   = (int) ($this->input->get('year')  ?? date('Y'));
        $month  = ($month >= 1 && $month <= 12) ? $month : (int) date('n');
        $year   = ($year  >= 2000 && $year <= 2099) ? $year : (int) date('Y');
        $this->pageData['_gstrMonth'] = $month;
        $this->pageData['_gstrYear']  = $year;
        $this->load->view('reports/gstr3b', $this->pageData);
    }

    public function getGstr3bData(): void
    {
        $this->EndReturnData = new stdClass();
        try {
            if (empty($this->pageData['JwtData'])) { throw new Exception('Unauthorised'); }
            $orgUID = (int) $this->pageData['JwtData']->Org->OrgUID;
            $month  = (int) ($this->input->get('month') ?? date('n'));
            $year   = (int) ($this->input->get('year')  ?? date('Y'));
            $month  = ($month >= 1 && $month <= 12) ? $month : (int) date('n');
            $year   = ($year  >= 2000 && $year <= 2099) ? $year : (int) date('Y');
            $this->load->model('reports_model');
            $data   = $this->reports_model->getGstr3bData($orgUID, $month, $year);
            $this->EndReturnData->Status  = 'Success';
            $this->EndReturnData->outward = $data['outward'] ?? [];
            $this->EndReturnData->itc     = $data['itc']     ?? [];
        } catch (Exception $e) {
            $this->notifyError('Reports::getGstr3bData', $e);
            $this->EndReturnData->Status  = 'Error';
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    public function gstr7(): void
    {
        if (empty($this->pageData['JwtData'])) { redirect('portal'); return; }
        $orgUID  = (int) $this->pageData['JwtData']->Org->OrgUID;
        $rawFrom = $this->input->get('from') ?? '';
        $rawTo   = $this->input->get('to')   ?? '';
        $_from   = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawFrom) ? $rawFrom : date('Y-m-01');
        $_to     = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawTo)   ? $rawTo   : date('Y-m-d');
        $this->pageData['_from'] = $_from;
        $this->pageData['_to']   = $_to;
        $this->load->view('reports/gstr7', $this->pageData);
    }

    public function getGstr7Data(): void
    {
        $this->EndReturnData = new stdClass();
        try {
            if (empty($this->pageData['JwtData'])) { throw new Exception('Unauthorised'); }
            $orgUID  = (int) $this->pageData['JwtData']->Org->OrgUID;
            $rawFrom = $this->input->get('from') ?? '';
            $rawTo   = $this->input->get('to')   ?? '';
            $from    = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawFrom) ? $rawFrom : date('Y-m-01');
            $to      = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawTo)   ? $rawTo   : date('Y-m-d');
            $this->load->model('reports_model');
            $rows    = $this->reports_model->getGstr7Data($orgUID, $from, $to);
            $this->EndReturnData->Status = 'Success';
            $this->EndReturnData->rows   = $rows;
        } catch (Exception $e) {
            $this->notifyError('Reports::getGstr7Data', $e);
            $this->EndReturnData->Status  = 'Error';
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    public function hsnSummary(): void
    {
        if (empty($this->pageData['JwtData'])) { redirect('portal'); return; }
        $rawFrom = $this->input->get('from') ?? '';
        $rawTo   = $this->input->get('to')   ?? '';
        $_from   = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawFrom) ? $rawFrom : date('Y-m-01');
        $_to     = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawTo)   ? $rawTo   : date('Y-m-d');
        $this->pageData['_from'] = $_from;
        $this->pageData['_to']   = $_to;
        $this->load->view('reports/hsn_summary', $this->pageData);
    }

    public function getHsnData(): void
    {
        $this->EndReturnData = new stdClass();
        try {
            if (empty($this->pageData['JwtData'])) { throw new Exception('Unauthorised'); }
            $orgUID  = (int) $this->pageData['JwtData']->Org->OrgUID;
            $rawFrom = $this->input->get('from') ?? '';
            $rawTo   = $this->input->get('to')   ?? '';
            $from    = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawFrom) ? $rawFrom : date('Y-m-01');
            $to      = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawTo)   ? $rawTo   : date('Y-m-d');
            $this->load->model('reports_model');
            $rows    = $this->reports_model->getHsnSummaryData($orgUID, $from, $to);
            $this->EndReturnData->Status = 'Success';
            $this->EndReturnData->rows   = $rows;
        } catch (Exception $e) {
            $this->notifyError('Reports::getHsnData', $e);
            $this->EndReturnData->Status  = 'Error';
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    public function tdsReceivable(): void
    {
        if (empty($this->pageData['JwtData'])) { redirect('portal'); return; }
        $rawFrom = $this->input->get('from') ?? '';
        $rawTo   = $this->input->get('to')   ?? '';
        $_from   = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawFrom) ? $rawFrom : date('Y-m-01');
        $_to     = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawTo)   ? $rawTo   : date('Y-m-d');
        $this->pageData['_from'] = $_from;
        $this->pageData['_to']   = $_to;
        $this->load->view('reports/tds_receivable', $this->pageData);
    }

    public function getTdsReceivableData(): void
    {
        if (empty($this->pageData['JwtData'])) { $this->EndReturnData['Status'] = 'Unauthorized'; $this->globalservice->sendJsonResponse($this->EndReturnData); return; }
        $orgUID  = (int) $this->pageData['JwtData']->Org->OrgUID;
        $rawFrom = $this->input->get('from') ?? '';
        $rawTo   = $this->input->get('to')   ?? '';
        $from    = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawFrom) ? $rawFrom : date('Y-m-01');
        $to      = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawTo)   ? $rawTo   : date('Y-m-d');
        $rows    = $this->Reports_model->getTdsReceivableData($orgUID, $from, $to);
        $this->EndReturnData['Status'] = 'Success';
        $this->EndReturnData['rows']   = $rows;
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    public function tdsPayable(): void
    {
        if (empty($this->pageData['JwtData'])) { redirect('portal'); return; }
        $rawFrom = $this->input->get('from') ?? '';
        $rawTo   = $this->input->get('to')   ?? '';
        $_from   = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawFrom) ? $rawFrom : date('Y-m-01');
        $_to     = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawTo)   ? $rawTo   : date('Y-m-d');
        $this->pageData['_from'] = $_from;
        $this->pageData['_to']   = $_to;
        $this->load->view('reports/tds_payable', $this->pageData);
    }

    public function getTdsPayableData(): void
    {
        if (empty($this->pageData['JwtData'])) { $this->EndReturnData['Status'] = 'Unauthorized'; $this->globalservice->sendJsonResponse($this->EndReturnData); return; }
        $orgUID  = (int) $this->pageData['JwtData']->Org->OrgUID;
        $rawFrom = $this->input->get('from') ?? '';
        $rawTo   = $this->input->get('to')   ?? '';
        $from    = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawFrom) ? $rawFrom : date('Y-m-01');
        $to      = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawTo)   ? $rawTo   : date('Y-m-d');
        $rows    = $this->Reports_model->getTdsPayableData($orgUID, $from, $to);
        $this->EndReturnData['Status'] = 'Success';
        $this->EndReturnData['rows']   = $rows;
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    public function tcsReceivable(): void
    {
        if (empty($this->pageData['JwtData'])) { redirect('portal'); return; }
        $rawFrom = $this->input->get('from') ?? '';
        $rawTo   = $this->input->get('to')   ?? '';
        $_from   = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawFrom) ? $rawFrom : date('Y-m-01');
        $_to     = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawTo)   ? $rawTo   : date('Y-m-d');
        $this->pageData['_from'] = $_from;
        $this->pageData['_to']   = $_to;
        $this->load->view('reports/tcs_receivable', $this->pageData);
    }

    public function getTcsReceivableData(): void
    {
        if (empty($this->pageData['JwtData'])) { $this->EndReturnData['Status'] = 'Unauthorized'; $this->globalservice->sendJsonResponse($this->EndReturnData); return; }
        $this->EndReturnData['Status'] = 'Success';
        $this->EndReturnData['rows']   = [];
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    public function tcsPayable(): void
    {
        if (empty($this->pageData['JwtData'])) { redirect('portal'); return; }
        $rawFrom = $this->input->get('from') ?? '';
        $rawTo   = $this->input->get('to')   ?? '';
        $_from   = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawFrom) ? $rawFrom : date('Y-m-01');
        $_to     = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawTo)   ? $rawTo   : date('Y-m-d');
        $this->pageData['_from'] = $_from;
        $this->pageData['_to']   = $_to;
        $this->load->view('reports/tcs_payable', $this->pageData);
    }

    public function getTcsPayableData(): void
    {
        if (empty($this->pageData['JwtData'])) { $this->EndReturnData['Status'] = 'Unauthorized'; $this->globalservice->sendJsonResponse($this->EndReturnData); return; }
        $this->EndReturnData['Status'] = 'Success';
        $this->EndReturnData['rows']   = [];
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── Bank Statement ────────────────────────────────────────────────────────

    public function bankStatement(): void
    {
        if (empty($this->pageData['JwtData'])) { redirect('portal'); return; }
        $orgUID  = (int) $this->pageData['JwtData']->Org->OrgUID;
        $rawFrom = $this->input->get('from')    ?? '';
        $rawTo   = $this->input->get('to')      ?? '';
        $rawAcct = $this->input->get('account') ?? '';
        $this->pageData['_initFrom']       = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawFrom) ? $rawFrom : date('Y-01-01');
        $this->pageData['_initTo']         = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawTo)   ? $rawTo   : date('Y-m-d');
        $this->pageData['_initAccountUID'] = (preg_match('/^\d+$/', $rawAcct) && (int)$rawAcct > 0) ? (int)$rawAcct : 0;
        $this->load->model('reports_model');
        $this->pageData['_bankAccounts']   = $this->reports_model->getBankAccounts($orgUID);
        $this->load->view('reports/bank_statement', $this->pageData);
    }

    public function getBankStatementData(): void
    {
        $this->EndReturnData = new stdClass();
        try {
            if (empty($this->pageData['JwtData'])) { throw new Exception('Unauthorised'); }
            $orgUID     = (int) $this->pageData['JwtData']->Org->OrgUID;
            $from       = $this->input->get('from')    ?: date('Y-01-01');
            $to         = $this->input->get('to')      ?: date('Y-m-d');
            $accountUID = (int) ($this->input->get('account') ?: 0);
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
                throw new Exception('Invalid date format.');
            }
            $this->load->model('reports_model');
            $rows = $this->reports_model->getBankStatementData($orgUID, $from, $to, $accountUID);
            $this->EndReturnData->Status = 'Success';
            $this->EndReturnData->rows   = $rows;
        } catch (Exception $e) {
            $this->notifyError('Reports::getBankStatementData', $e);
            $this->EndReturnData->Status  = 'Error';
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }
}
