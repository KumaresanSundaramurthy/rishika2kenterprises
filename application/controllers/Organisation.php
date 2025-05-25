<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Organisation extends CI_Controller {

    public $pageData = array();
    private $EndReturnData;

    public function __construct() {
        parent::__construct();

    }

    public function index() {
        
        $this->load->library('curlservice');
        $CountryResp = $this->curlservice->retrieve(getenv('CDN_URL').'/global/countrydetails.json', 'GET', []);

        $Countries = $CountryResp->Data;
        usort($Countries, function ($a, $b) {
            return strcmp($a['name'], $b['name']);
        });

        $this->load->model('organisation_model');
        $this->pageData['OrgBussType'] = [];
        $OrgBussTypeData = $this->organisation_model->getOrgBusinessTypeDetails();
        if($OrgBussTypeData->Error === FALSE) {
            $this->pageData['OrgBussType'] = $OrgBussTypeData->Data;
        }

        $this->pageData['EditOrgData'] = [];
        $OrganisationData = $this->organisation_model->getOrganisationDetails(['Org.OrgUID' => $this->pageData['JwtData']->User->OrgUID]);
        if($OrganisationData->Error === FALSE) {
            $this->pageData['EditOrgData'] = $OrganisationData->Data[0];
        }
        

        $this->pageData['CountryInfo'] = $Countries;

        $this->load->model('global_model');
        $this->pageData['TimezoneInfo'] = [];
        $TimezoneInfo = $this->global_model->getTimezoneDetails([]);
        if($TimezoneInfo->Error === FALSE) {
            $this->pageData['TimezoneInfo'] = $TimezoneInfo->Data;
        }
        
        $this->pageData['ControllerName'] = get_class($this);
        
        $this->load->view('organisation/view', $this->pageData);

    }

    public function updateOrgForm() {

        $this->EndReturnData = new stdClass();
		try {

            $this->load->model('formvalidation_model');

            $PostData = $this->input->post();
            $ErrorInForm = $this->formvalidation_model->validateForm($PostData);
            if(empty($ErrorInForm)) {

                $this->load->model('dbwrite_model');

                $updateOrgData = [
                    'Name' => $PostData['Name'],
                    'ShortDescription' => $PostData['Description'] ? $PostData['Description'] : null,
                    'BrandName' => $PostData['BrandName'],
                    'CountryCode' => $PostData['CountryCode'],
                    'MobileNumber' => $PostData['MobileNumber'],
                    'GSTIN' => $PostData['GSTIN'] ? $PostData['GSTIN'] : null,
                    'OrgBussTypeUID' => $PostData['OrgBussTypeUID'],
                    'AlternateNumber' => $PostData['AlternateNumber'] ? $PostData['AlternateNumber'] : null,
                    'Website' => $PostData['Website'] ? $PostData['Website'] : null,
                    'PANNumber' => $PostData['PANNumber'] ? $PostData['PANNumber'] : null,
                    'TimezoneUID' => $PostData['TimezoneUID'] ? $PostData['TimezoneUID'] : null,
                ];

                $UpdateDataResp = $this->dbwrite_model->updateData('Organisation', 'OrganisationTbl', $updateOrgData, array('OrgUID' => $PostData['OrgUID']));

                if($UpdateDataResp->Error === FALSE) {
                    $this->EndReturnData->Error = FALSE;
                    $this->EndReturnData->Message = 'Updated Successfully';
                } else {
                    $this->EndReturnData->Error = TRUE;
                    $this->EndReturnData->Message = 'Error occured';
                }

            } else {
                $this->EndReturnData->Error = TRUE;
                $this->EndReturnData->Message = $ErrorInForm;
            }

        } catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

		$this->output->set_status_header(200)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($this->EndReturnData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
            ->_display();
        exit;

    }

}