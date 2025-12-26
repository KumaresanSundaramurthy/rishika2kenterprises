<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Transactions extends CI_Controller {

    public $pageData = array();
    private $EndReturnData;

    public function __construct() {
        parent::__construct();

    }

    public function addTransactionPrefix() {

        $this->EndReturnData = new stdClass();
		try {
            
            $PostData = $this->input->post();

            $this->load->model('formvalidation_model');
            $ErrorInForm = $this->formvalidation_model->transPrefixValidateForm($PostData);
            if (!empty($ErrorInForm)) {
                throw new Exception($ErrorInForm);
            }

            $userUID = $this->pageData['JwtData']->User->UserUID;
            $now     = time();

            $addFormData = [
                'OrgUID'            => $this->pageData['JwtData']->User->OrgUID,
                'ModuleUID'         => getPostValue($PostData, 'preModuleUID'),
                'Name'              => getPostValue($PostData, 'transPrefixName'),
                'CreatedBy'         => $userUID,
                'CreatedOn'         => $now,
                'UpdatedBy'         => $userUID,
                'UpdatedOn'         => $now,
            ];

            $this->load->model('dbwrite_model');
            $getResp = $this->dbwrite_model->insertData('Transaction', 'TransactionPrefixTbl', $addFormData);
            if ($getResp->Error) {
                throw new Exception($getResp->Message);
            }
            
            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Message = 'Added Successfully';

        } catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
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
                    'unitPrice' => (float) $unitPrice,
                    'taxAmount' => (float) $taxAmount,
                    'sellingPrice' => (float) smartDecimal($sellingPrice, $GeneralSettings->DecimalPoints, true),
                    'purchasePrice' => (float) smartDecimal($value->PurchasePrice, $GeneralSettings->DecimalPoints, true),
                    "availableQuantity" => (int) $value->AvailableQuantity,
                    "hsnCode" => $value->HSNSACCode,
                    "primaryUnit" => $value->PrimaryUnitShortName,
                    "category" => $value->CatgName,
                    "taxPercent" => (float) $taxPercent,
                    "cgstPercent" => (float) $value->CGST,
                    "sgstPercent" => (float) $value->SGST,
                    "igstPercent" => (float) $value->IGST,
                    "discount" => (float) smartDecimal($value->Discount),
                    "discountType" => $value->DiscountTypeName,
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