<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Transactions extends CI_Controller {

    public $pageData = array();
    private $EndReturnData;

    public function __construct() {
        parent::__construct();

    }

    // ----------------------------------------------------------------
    // GET  /transactions/getTransactionPrefixes
    // Returns all org-level prefixes (shared across all transaction types)
    // ----------------------------------------------------------------
    public function getTransactionPrefixes() {

        $this->EndReturnData = new stdClass();
        try {

            $orgUID = $this->pageData['JwtData']->User->OrgUID;

            $this->load->model('transactions_model');
            $result = $this->transactions_model->getTransactionsPrefixDetails(['Prefix.OrgUID' => $orgUID]);

            $this->EndReturnData->Data  = $result->Data ?? [];
            $this->EndReturnData->Error = FALSE;

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    // ----------------------------------------------------------------
    // POST /transactions/addTransactionPrefix
    // ----------------------------------------------------------------
    public function addTransactionPrefix() {

        $this->EndReturnData = new stdClass();
        try {

            $this->load->model('dbwrite_model');
            $this->dbwrite_model->startTransaction();

            $PostData = $this->input->post();

            $this->load->model('formvalidation_model');
            $ErrorInForm = $this->formvalidation_model->transPrefixValidateForm($PostData);
            if (!empty($ErrorInForm)) throw new Exception($ErrorInForm);

            $userUID = $this->pageData['JwtData']->User->UserUID;
            $now     = time();

            $addFormData = [
                'OrgUID'           => $this->pageData['JwtData']->User->OrgUID,
                'Name'             => strtoupper(getPostValue($PostData, 'transPrefixName')),
                'IncludeFiscalYear'=> getPostValue($PostData, 'includeFiscalYear') ? 1 : 0,
                'FiscalYearFormat' => in_array(getPostValue($PostData, 'fiscalYearFormat'), ['SHORT','LONG'])
                                        ? getPostValue($PostData, 'fiscalYearFormat') : 'SHORT',
                'IncludeShortName' => getPostValue($PostData, 'includeShortName') ? 1 : 0,
                'ShortName'        => strtoupper(substr(getPostValue($PostData, 'companyShortName') ?? '', 0, 20)),
                'Separator'        => getPostValue($PostData, 'prefixSeparator') ?: '-',
                'NumberPadding'    => (int)(getPostValue($PostData, 'numberPadding') ?: 1),
                'CreatedBy'        => $userUID,
                'CreatedOn'        => $now,
                'UpdatedBy'        => $userUID,
                'UpdatedOn'        => $now,
            ];

            $this->load->model('dbwrite_model');
            $getResp = $this->dbwrite_model->insertData('Transaction', 'TransactionPrefixTbl', $addFormData);
            if ($getResp->Error) throw new Exception($getResp->Message);

            // Return the new prefix data so the caller can update the UI
            $this->load->model('transactions_model');
            $newPrefix = $this->transactions_model->getTransactionsPrefixDetails(['Prefix.PrefixUID' => $getResp->ID]);

            $this->dbwrite_model->commitTransaction();

            $this->EndReturnData->Error      = FALSE;
            $this->EndReturnData->Message    = 'Prefix added successfully.';
            $this->EndReturnData->PrefixUID  = $getResp->ID;
            $this->EndReturnData->PrefixData = $newPrefix->Data[0] ?? null;

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    // ----------------------------------------------------------------
    // POST /transactions/updateTransactionPrefix
    // ----------------------------------------------------------------
    public function updateTransactionPrefix() {

        $this->EndReturnData = new stdClass();
        try {

            $this->load->model('dbwrite_model');
            $this->dbwrite_model->startTransaction();

            $PostData  = $this->input->post();
            $prefixUID = (int) getPostValue($PostData, 'prePrefixUID');
            if ($prefixUID <= 0) throw new Exception('Invalid prefix.');

            $this->load->model('formvalidation_model');
            $ErrorInForm = $this->formvalidation_model->transPrefixValidateForm($PostData);
            if (!empty($ErrorInForm)) throw new Exception($ErrorInForm);

            $userUID = $this->pageData['JwtData']->User->UserUID;
            $orgUID  = $this->pageData['JwtData']->User->OrgUID;

            $updateData = [
                'Name'             => strtoupper(getPostValue($PostData, 'transPrefixName')),
                'IncludeFiscalYear'=> getPostValue($PostData, 'includeFiscalYear') ? 1 : 0,
                'FiscalYearFormat' => in_array(getPostValue($PostData, 'fiscalYearFormat'), ['SHORT','LONG'])
                                        ? getPostValue($PostData, 'fiscalYearFormat') : 'SHORT',
                'IncludeShortName' => getPostValue($PostData, 'includeShortName') ? 1 : 0,
                'ShortName'        => strtoupper(substr(getPostValue($PostData, 'companyShortName') ?? '', 0, 20)),
                'Separator'        => getPostValue($PostData, 'prefixSeparator') ?: '-',
                'NumberPadding'    => (int)(getPostValue($PostData, 'numberPadding') ?: 1),
                'UpdatedBy'        => $userUID,
            ];

            $this->load->model('dbwrite_model');
            $resp = $this->dbwrite_model->updateData(
                'Transaction', 'TransactionPrefixTbl',
                $updateData,
                ['PrefixUID' => $prefixUID, 'OrgUID' => $orgUID]
            );
            if ($resp->Error) throw new Exception($resp->Message);

            $this->dbwrite_model->commitTransaction();

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Prefix updated successfully.';

        } catch (Exception $e) {
            $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    // ----------------------------------------------------------------
    // POST /transactions/deleteTransactionPrefix
    // ----------------------------------------------------------------
    public function deleteTransactionPrefix() {

        $this->EndReturnData = new stdClass();
        try {

            $this->load->model('dbwrite_model');
            $this->dbwrite_model->startTransaction();

            $PostData  = $this->input->post();
            $prefixUID = (int) getPostValue($PostData, 'prePrefixUID');
            if ($prefixUID <= 0) throw new Exception('Invalid prefix.');

            $userUID = $this->pageData['JwtData']->User->UserUID;
            $orgUID  = $this->pageData['JwtData']->User->OrgUID;

            $this->load->model('dbwrite_model');
            $resp = $this->dbwrite_model->updateData(
                'Transaction', 'TransactionPrefixTbl',
                ['IsDeleted' => 1, 'IsActive' => 0, 'UpdatedBy' => $userUID, 'UpdatedOn' => time()],
                ['PrefixUID' => $prefixUID, 'OrgUID' => $orgUID]
            );
            if ($resp->Error) throw new Exception($resp->Message);

            $this->dbwrite_model->commitTransaction();

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Prefix deleted.';

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    // ----------------------------------------------------------------
    // POST /transactions/setDefaultTransactionPrefix
    // Updates TransPageSettingsTbl.DefaultPrefix for this module/org
    // ----------------------------------------------------------------
    public function setDefaultTransactionPrefix() {

        $this->EndReturnData = new stdClass();
        try {

            $this->load->model('dbwrite_model');
            $this->dbwrite_model->startTransaction();

            $PostData  = $this->input->post();
            $prefixUID = (int) getPostValue($PostData, 'prePrefixUID');
            if ($prefixUID <= 0) throw new Exception('Invalid prefix.');

            $orgUID  = $this->pageData['JwtData']->User->OrgUID;
            $userUID = $this->pageData['JwtData']->User->UserUID;

            $this->load->model('transactions_model');

            // Clear default flag for all org prefixes, then set the chosen one
            $resp = $this->dbwrite_model->updateData(
                'Transaction', 'TransactionPrefixTbl',
                ['IsDefault' => 0, 'UpdatedBy' => $userUID, 'UpdatedOn' => time()],
                ['OrgUID' => $orgUID]
            );
            if ($resp->Error) throw new Exception($resp->Message);

            $updresp = $this->dbwrite_model->updateData(
                'Transaction', 'TransactionPrefixTbl',
                ['IsDefault' => 1, 'UpdatedBy' => $userUID, 'UpdatedOn' => time()],
                ['PrefixUID' => $prefixUID, 'OrgUID' => $orgUID]
            );
            if ($updresp->Error) throw new Exception($updresp->Message);

            $allResults = $this->transactions_model->getTransactionsPrefixDetails(['Prefix.OrgUID' => $orgUID]);

            $this->dbwrite_model->commitTransaction();

            $this->EndReturnData->Error         = FALSE;
            $this->EndReturnData->Message       = 'Default prefix updated.';
            $this->EndReturnData->PrefixUID     = $prefixUID;
            // $this->EndReturnData->PrefixData    = $prefixResult->Data[0];
            $this->EndReturnData->AllPrefixData = $allResults;

        } catch (Exception $e) {
            $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    public function searchCustomers() {

        $this->EndReturnData = new stdClass();
		try {

            $term = $this->input->get('term') ? trim($this->input->get('term')) : '';

            $this->load->model('transactions_model');
            $customersData = $this->transactions_model->getCustomersDetails($term, []);

            $customersDetails = [];
            foreach ($customersData as $value) {
                $formData = [
                    'id'   => $value->CustomerUID,
                    'text' => $value->Area 
                        ? $value->Name . ' (' . $value->Area . ')' 
                        : $value->Name,
                ];
                if($value->AddrUID) {
                    $formData['address'] = [
                        'Line1' => $value->Line1,
                        'Line2' => $value->Line2,
                        'Pincode' => $value->Pincode,
                        'City' => $value->CityText,
                        'State' => $value->StateText,
                    ];
                }
                $customersDetails[] = $formData;
            }
            $this->EndReturnData->Lists = $customersDetails;
            $this->EndReturnData->Error = false;

        } catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);
        
    }

    public function searchTransProducts() {

        $this->EndReturnData = new stdClass();
		try {

            $term = $this->input->get('term') ? trim($this->input->get('term')) : '';
            $catgUid = $this->input->get('categuid') ? (int) $this->input->get('categuid') : 0;
            $whereArr = [];
            if($catgUid) {
                $whereArr['product.CategoryUID'] = $catgUid;
            }

            $this->load->model('transactions_model');
            $productData = $this->transactions_model->getTransProductsDetails($term, $whereArr);

            $GeneralSettings = $this->redis_cache->get('Redis_UserGenSettings')->Value ?? NULL;

            $retProdDetails = [];
            foreach ($productData as $value) {

                $sellingPrice = (float) $value->SellingPrice;
                $taxPercent = (float) $value->TaxPercentage;

                $unitPrice = smartDecimal($sellingPrice / (1 + ($taxPercent / 100)), 8);
                $taxAmount = smartDecimal($sellingPrice - $unitPrice, $GeneralSettings->DecimalPoints, true);

                $formData = [
                    'id'   => (int) $value->ProductUID,
                    'text' => $value->ItemName,
                    'itemName' => $value->ItemName,
                    'productType' => $value->ProductType,
                    'unitPrice' => (float) $unitPrice,
                    'taxAmount' => (float) $taxAmount,
                    'sellingPrice' => (float) smartDecimal($sellingPrice, $GeneralSettings->DecimalPoints, true),
                    'purchasePrice' => (float) smartDecimal($value->PurchasePrice, $GeneralSettings->DecimalPoints, true),
                    "availableQuantity" => (float) $value->AvailableQuantity,
                    "hsnCode" => $value->HSNSACCode,
                    "category" => $value->CatgName,
                    "taxPercent" => (float) $taxPercent,
                    "cgstPercent" => (float) $value->CGST,
                    "sgstPercent" => (float) $value->SGST,
                    "igstPercent" => (float) $value->IGST,
                    "discount" => (float) smartDecimal($value->Discount),
                    "discountType" => $value->DiscountTypeName,
                    "primaryUnit" => $value->priUnitShortName,
                ];

                $retProdDetails[] = $formData;

            }
            $this->EndReturnData->Lists = $retProdDetails;
            $this->EndReturnData->Error = false;

        } catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);
        
    }

}