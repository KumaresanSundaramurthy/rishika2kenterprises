<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Organisation extends MY_Controller {

    public    $pageData      = array();
    protected $pageModuleUID = 1;

    public function __construct() {
        parent::__construct();
    }

    public function index() {

        $this->_loadPageTitle($this->pageModuleUID);

        $this->pageData['EditOrgData']     = null;
        $this->pageData['BillOrgAddrData'] = null;
        $this->pageData['ShipOrgAddrList'] = [];
        $this->pageData['MaxShippingAddr'] = 3;

        try {

            $orgUID   = (int) $this->pageData['JwtData']->Org->OrgUID;
            $jwtToken = $_COOKIE[getenv('JWT_COOKIE_NAME')] ?? '';

            $resp = services_get('api/organisation/getOrganisationDetails?orgId=' . $orgUID, ['Authorization: Bearer ' . $jwtToken], 10);

            if ($resp && !$resp->Error) {
                $this->pageData['EditOrgData']     = $resp->EditOrgData     ?? null;
                $this->pageData['BillOrgAddrData'] = $resp->BillOrgAddrData ?? null;
                $this->pageData['ShipOrgAddrList'] = $resp->ShipOrgAddrList ?? [];
                $this->pageData['MaxShippingAddr'] = (int)($resp->MaxShippingAddr ?? 3);
            }

            $this->load->view('organisation/view', $this->pageData);

        } catch (Exception $e) {
            $this->notifyError('Organisation::index', $e);
            $this->load->view('organisation/view', $this->pageData);
        }

    }

}