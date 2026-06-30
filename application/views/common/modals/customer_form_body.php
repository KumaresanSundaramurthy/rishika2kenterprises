<?php defined('BASEPATH') or exit('No direct script access allowed');
/*
 * Self-contained customer form body partial.
 * Loads its own data via the CI instance so any controller can include it
 * without needing to pass CustomerTypeList / OrgCCode / OrgCISO2 explicitly.
 */
$CI =& get_instance();
$CI->load->model('customers_model');

$_orgUID      = $CI->pageData['JwtData']->Org->OrgUID  ?? 0;
$_orgCCode    = $CI->pageData['JwtData']->Org->OrgCCode  ?? '';
$_orgCISO2    = $CI->pageData['JwtData']->Org->OrgCISO2  ?? '';
$_typeList    = $CI->customers_model->getCustomerTypeList($_orgUID);
$_groupList   = $CI->customers_model->getActiveGroupsForDropdown($_orgUID);

$CI->load->view('customers/forms/modal_body', [
    'FormMode'          => 'add',
    'FormData'          => null,
    'BankDetails'       => [],
    'BillingAddr'       => null,
    'ShippingAddr'      => null,
    'CustomerTypeList'  => $_typeList,
    'CustomerGroupList' => $_groupList,
    'OrgCCode'          => $_orgCCode,
    'OrgCISO2'          => $_orgCISO2,
    'JwtData'           => $CI->pageData['JwtData'],
]);
