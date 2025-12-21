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

    private function buildCustomerFormData($postData, $isCreate = false) {
        $data = [
            'Name'             => getPostValue($postData, 'Name'),
            'Area'             => getPostValue($postData, 'Area'),
            'OrgUID'           => $this->pageData['JwtData']->User->OrgUID,
            'EmailAddress'     => getPostValue($postData, 'EmailAddress'),
            'CountryCode'      => getPostValue($postData, 'CountryCode'),
            'CountryISO2'      => getPostValue($postData, 'CountryISO2', '', 'IN'),
            'MobileNumber'     => getPostValue($postData, 'MobileNumber'),
            'DebitCreditType'  => getPostValue($postData, 'DebitCreditCheck', '', 'Debit'),
            'DebitCreditAmount'=> getPostValue($postData, 'DebitCreditAmount', '', 0),
            'PANNumber'        => getPostValue($postData, 'PANNumber'),
            'ContactPerson'    => getPostValue($postData, 'ContactPerson'),
            'DateOfBirth'      => getPostValue($postData, 'CPDateOfBirth'),
            'GSTIN'            => getPostValue($postData, 'GSTIN'),
            'CompanyName'      => getPostValue($postData, 'CompanyName'),
            'DiscountPercent'  => getPostValue($postData, 'DiscountPercent', '', 0),
            'CreditPeriod'     => getPostValue($postData, 'CreditPeriod', '', 30),
            'CreditLimit'      => getPostValue($postData, 'CreditLimit', '', 0),
            'Notes'            => getPostValue($postData, 'Notes'),
            'Tags'             => getPostValue($postData, 'Tags', 'Comma'),
            'CCEmails'         => getPostValue($postData, 'CCEmails', 'Comma'),
            'UpdatedBy'        => $this->pageData['JwtData']->User->UserUID,
            'UpdatedOn'        => time(),
        ];
        if ($isCreate) {
            $data['CreatedBy'] = $this->pageData['JwtData']->User->UserUID;
            $data['CreatedOn'] = time();
        }
        return $data;
    }

    public function addCustomerData() {

        $this->EndReturnData = new stdClass();
		try {

            $PostData = $this->input->post();

            $this->load->model('formvalidation_model');
            $ErrorInForm = $this->formvalidation_model->custValidateForm($PostData);
            if(empty($ErrorInForm)) {

                $customerFormData = $this->buildCustomerFormData($PostData, true);
                
                $this->load->model('dbwrite_model');
                $InsertDataResp = $this->dbwrite_model->insertData('Customers', 'CustomerTbl', $customerFormData);
                if ($InsertDataResp->Error) throw new Exception($InsertDataResp->Message);
                
                $CustomerUID = $InsertDataResp->ID;

                foreach ([['Bill','Billing'], ['Ship','Shipping']] as [$prefix,$type]) {
                    $this->globalservice->saveAddressInfo($PostData, $CustomerUID, $prefix, $type, 'Customers', 'CustAddressTbl', 'CustAddressUID', 'CustomerUID');
                }

                $this->load->model('transactions_model');
                $customersData = $this->transactions_model->getCustomersDetails('', ['Customers.CustomerUID' => $CustomerUID]);
                $cust_Data = [];
                foreach ($customersData as $value) {
                    $cust_Data = [
                        'id'   => $value->CustomerUID,
                        'text' => $value->Area 
                            ? $value->Name . ' (' . $value->Area . ')' 
                            : $value->Name,
                    ];
                    if($value->AddrUID) {
                        $cust_Data['address'] = [
                            'Line1' => $value->Line1,
                            'Line2' => $value->Line2,
                            'Pincode' => $value->Pincode,
                            'City' => $value->CityText,
                            'State' => $value->StateText,
                        ];
                    }
                }

                $this->EndReturnData->Error = FALSE;
                $this->EndReturnData->Message = 'Created Successfully';
                $this->EndReturnData->Customer = $cust_Data;

            } else {
                throw new Exception($ErrorInForm);
            }

        } catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

		$this->globalservice->sendJsonResponse($this->EndReturnData);

    }

}