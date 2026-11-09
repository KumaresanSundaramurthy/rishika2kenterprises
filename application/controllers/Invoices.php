<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Invoices extends MY_Controller {

    public $pageData = array();
    protected $EndReturnData;
    protected $pageModuleUID;

    public function __construct() {
        parent::__construct();

        $this->pageModuleUID = 103;
        $this->load->helper('transaction');

    }

    public function index(): void {
        if (!$this->_loadPageTitle($this->pageModuleUID)) {
            $this->load->view('common/module_error', $this->pageData);
            return;
        }
        try {
            $this->pageData['JwtData']->ModuleUID = $this->pageModuleUID;
            $orgUID = (int) $this->pageData['JwtData']->Org->OrgUID;
            $this->load->model('organisation_model');
            $templates = $this->organisation_model->getModuleMessageTemplates($orgUID, $this->pageModuleUID);
            $this->_loadTransactionIndexPage([
                'datePrefKey'       => 'invoices',
                'skipList'          => true,
                'tabSlugMap'        => ['all' => 'All', 'pending' => 'InvPending', 'paid' => 'Paid', 'cancelled' => 'Cancelled', 'draft' => 'Draft', 'creditnotes' => 'CreditNotes'],
                'listViewPath'      => 'transactions/invoices/list',
                'paginationUrl'     => '/transactions/getPageDetails/103',
                'skipListForTabs'   => ['CreditNotes'],
                'listViewExtraData' => ['WhatsAppTemplate' => $templates['WhatsApp'] ?? null],
            ]);

            $this->pageData['CnInitHtml']       = '';
            $this->pageData['CnInitPagination'] = '';
            $this->pageData['CnInitCount']      = 0;
            if ($this->pageData['InitTab'] === 'CreditNotes') {
                try {
                    $cnSearch  = trim($this->input->get('search') ?: '');
                    $cnLimit   = (int)($this->pageData['JwtData']->GenSettings->RowLimit ?? 10);
                    $this->load->model('transactions_model');
                    $cnData    = $this->transactions_model->getCreditNotesList((int)$orgUID, $cnLimit, 0, '', $cnSearch);
                    $cnTotal   = $cnData->TotalCount;
                    $cnRows    = $cnData->Rows;

                    $this->pageData['CnInitHtml'] = $this->load->view(
                        'transactions/invoices/creditnotes_list',
                        ['DataLists' => $cnRows, 'SerialNumber' => 0, 'JwtData' => $this->pageData['JwtData']],
                        true
                    );
                    $this->pageData['CnInitPagination'] = $this->globalservice->buildPagePaginationHtml(
                        '/invoices/getCreditNotesList', $cnTotal, 1, $cnLimit
                    );
                    $this->pageData['CnInitCount'] = $cnTotal;
                } catch (Exception $e) {
                    // Fail silently — JS loadCreditNotes() fires as fallback
                }
            }

            $this->load->view('transactions/invoices/view', $this->pageData);
        } catch (Exception $e) {
            notifyError('Invoices::index', $e);
            redirect('dashboard', 'refresh');
        }
    }

    public function create() {

        try {

            $orgUID = $this->pageData['JwtData']->Org->OrgUID;
            $this->pageData['JwtData']->ModuleUID = $this->pageModuleUID;

            $this->load->model('transactions_model');

            $prefixResult                    = $this->transactions_model->getTransactionsPrefixDetails(['Prefix.OrgUID' => $orgUID, 'Prefix.ModuleUID' => $this->pageModuleUID]);
            $this->pageData['PrefixData']    = $prefixResult->Data ?? [];

            $nextNumberMap = [];
            foreach ($this->pageData['PrefixData'] as $pd) {
                $nextNumberMap[(int)$pd->PrefixUID] = $this->transactions_model->getNextTransactionNumber(
                    $pd->PrefixUID, $orgUID, $this->pageModuleUID
                );
            }
            $this->pageData['NextNumberMap'] = $nextNumberMap;

            // Pre-fill from Sales Order if converting
            $fromSOUID = (int) $this->input->get('fromSalesOrder');
            $this->pageData['FromSalesOrderUID'] = $fromSOUID;
            $this->pageData['SalesOrderData']    = null;
            $this->pageData['SalesOrderItems']   = [];
            if ($fromSOUID > 0) {
                $soData  = $this->transactions_model->getTransactionById($fromSOUID, $orgUID, 102);
                $soItems = $soData ? $this->transactions_model->getTransactionItems($fromSOUID, $orgUID) : [];
                $this->pageData['SalesOrderData']  = $soData;
                $this->pageData['SalesOrderItems'] = $soItems;
            }

            // Pre-fill from Quotation if converting directly
            $fromQuotationUID = (int) $this->input->get('fromQuotation');
            $this->pageData['FromQuotationUID'] = $fromQuotationUID;
            $this->pageData['QuotationData']    = null;
            $this->pageData['QuotationItems']   = [];
            if ($fromQuotationUID > 0) {
                $quotData  = $this->transactions_model->getTransactionById($fromQuotationUID, $orgUID, 101);
                $quotItems = $quotData ? $this->transactions_model->getTransactionItems($fromQuotationUID, $orgUID) : [];
                $this->pageData['QuotationData']  = $quotData;
                $this->pageData['QuotationItems'] = $quotItems;
            }

            // Pre-fill from Pro Forma Invoice if converting
            $fromProFormaUID = (int) $this->input->get('fromProForma');
            $this->pageData['FromProFormaUID'] = $fromProFormaUID;
            $this->pageData['ProFormaData']    = null;
            $this->pageData['ProFormaItems']   = [];
            if ($fromProFormaUID > 0) {
                $pfData  = $this->transactions_model->getTransactionById($fromProFormaUID, $orgUID, 113);
                $pfItems = $pfData ? $this->transactions_model->getTransactionItems($fromProFormaUID, $orgUID) : [];
                $this->pageData['ProFormaData']  = $pfData;
                $this->pageData['ProFormaItems'] = $pfItems;
            }

            // Pre-fill from Delivery Challan if converting
            $fromChallanUID = (int) $this->input->get('fromChallan');
            $this->pageData['FromChallanUID'] = $fromChallanUID;
            $this->pageData['ChallanData']    = null;
            $this->pageData['ChallanItems']   = [];
            if ($fromChallanUID > 0) {
                $challanData  = $this->transactions_model->getTransactionById($fromChallanUID, $orgUID, 112);
                $challanItems = $challanData ? $this->transactions_model->getTransactionItems($fromChallanUID, $orgUID) : [];
                $this->pageData['ChallanData']  = $challanData;
                $this->pageData['ChallanItems'] = $challanItems;
            }

            $this->_getDispatchAddresses($orgUID);

            $this->pageData['PaymentTypes']       = $this->transactions_model->getPaymentTypesList();
            $this->pageData['BankAccounts']       = $this->transactions_model->getOrgBankAccounts($orgUID);
            $this->pageData['AdditionalCharges']  = $this->_getAdditionalChargesForOrg((int)$orgUID, true);
            $this->pageData['TaxList']            = $this->_getTaxList();
            $this->pageData['TransactionCharges'] = [];
            $this->pageData['IsEditMode']         = false;

            $this->load->view('transactions/invoices/forms/form', $this->pageData);

        } catch (Exception $e) {
            notifyError('Invoices::create', $e);
            redirect('invoices', 'refresh');
        }

    }

    public function edit($token = '') {

        try {

            $token = trim((string) $token);
            if (empty($token)) redirect('invoices');

            $orgUID = $this->pageData['JwtData']->Org->OrgUID;

            $this->load->model('transactions_model');
            $invData = $this->transactions_model->getTransactionByToken($token, $orgUID, $this->pageModuleUID, $this->_uiLang());
            if (!$invData) redirect('invoices');

            $transUID = (int) $invData->TransUID;

            $this->pageData['JwtData']->ModuleUID = $this->pageModuleUID;

            $invItems = $this->transactions_model->getTransactionItems($transUID, $orgUID);

            $_transProdUIDs = array_map(fn($i) => (int)$i->TransProdUID, $invItems);
            $this->pageData['ReturnedQtyMap'] = !empty($_transProdUIDs)
                ? $this->transactions_model->getReturnedQtyMapForItems($_transProdUIDs, $orgUID)
                : [];
            $this->pageData['HasSalesReturns'] = $this->transactions_model->hasActiveSalesReturns($transUID, (int)$orgUID);

            $this->load->model('customers_model');
            $custAddr = $this->customers_model->getCustomerAddress(['CustAddress.CustomerUID' => $invData->PartyUID, 'CustAddress.OrgUID' => $orgUID], $this->_uiLang());
            $shipping = current(array_filter($custAddr, fn($a) => $a->AddressType === 'Shipping'));
            $billing  = current(array_filter($custAddr, fn($a) => $a->AddressType === 'Billing'));
            $this->pageData['CustAddr'] = $shipping ?: ($billing ?: ($custAddr[0] ?? null));

            $this->pageData['InvData']  = $invData;
            $this->pageData['InvItems'] = $invItems;

            $this->pageData['DraftReservedCN'] = null;
            if ($invData->DocStatus === 'Draft') {
                $detail       = $this->transactions_model->getTransDetailRow($transUID);
                $pendingCnUID = (int)($detail->PendingCreditNoteUID ?? 0);
                if ($pendingCnUID > 0) {
                    $this->pageData['DraftReservedCN'] = $this->transactions_model->getCreditNoteByUID($orgUID, $pendingCnUID);
                }
            }

            $prefixResult                    = $this->transactions_model->getTransactionsPrefixDetails(['Prefix.OrgUID' => $orgUID, 'Prefix.ModuleUID' => $this->pageModuleUID]);
            $this->pageData['PrefixData']    = $prefixResult->Data ?? [];

            $nextNumberMap = [];
            foreach ($this->pageData['PrefixData'] as $pd) {
                $nextNumberMap[(int)$pd->PrefixUID] = $this->transactions_model->getNextTransactionNumber(
                    $pd->PrefixUID, $orgUID, $this->pageModuleUID
                );
            }
            $this->pageData['NextNumberMap'] = $nextNumberMap;

            $this->_getDispatchAddresses($orgUID);

            $this->pageData['PaymentTypes']       = $this->transactions_model->getPaymentTypesList();
            $this->pageData['BankAccounts']       = $this->transactions_model->getOrgBankAccounts($orgUID);
            $this->pageData['InvAttachments']     = $this->transactions_model->getTransactionAttachments($transUID, $orgUID);
            $this->pageData['AdditionalCharges']  = $this->_getAdditionalChargesForOrg((int)$orgUID, true);
            $this->pageData['TransactionCharges'] = $this->transactions_model->getTransactionCharges($transUID, (int)$orgUID);
            $this->pageData['TaxList']            = $this->_getTaxList();
            $this->pageData['IsEditMode']         = true;
            $this->pageData['InvSerialsByProd']   = $this->_getTransSerialsGrouped($transUID, $orgUID, 'Invoice');

            $this->load->view('transactions/invoices/forms/form', $this->pageData);

        } catch (Exception $e) {
            notifyError('Invoices::edit', $e);
            redirect('invoices', 'refresh');
        }

    }


    public function getInvoicePdfBase64() {

        $this->EndReturnData = new stdClass();
        try {

            $transUID  = (int) $this->input->post('TransUID');
            $paperSize = strtoupper(trim($this->input->post('PaperSize') ?: 'A4'));
            $orgUID    = $this->pageData['JwtData']->Org->OrgUID;

            if ($transUID <= 0) throw new ValidationException('Invalid invoice.');

            $this->load->model('transactions_model');
            $invoice = $this->transactions_model->getTransactionById($transUID, $orgUID, $this->pageModuleUID);
            if (!$invoice) throw new ValidationException('Invoice not found.');

            $pdfBytes = $this->transactions_model->generateInvoicePdfBytes($transUID, $orgUID, $paperSize);
            if (!$pdfBytes) throw new Exception('Failed to generate PDF.');

            $filename = preg_replace('/[^A-Za-z0-9_\-]/', '_', $invoice->UniqueNumber ?? ('Invoice_' . $transUID)) . '.pdf';

            $this->EndReturnData->Error    = FALSE;
            $this->EndReturnData->Base64   = base64_encode($pdfBytes);
            $this->EndReturnData->Filename = $filename;
            $this->EndReturnData->Size     = strlen($pdfBytes);

        } catch (ValidationException $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        } catch (Exception $e) {
            notifyError('Invoices::getInvoicePdfBase64', $e);
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }
}
