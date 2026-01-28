<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

if (!function_exists('getUserMainModules')) {
    function getUserMainModules() {

        $CI =& get_instance();
        
        $context = new stdClass();
        $context->user_id = $CI->session->userdata('UserUID');
        $context->org_id = $CI->session->userdata('CurrentOrgUID');
        $context->branch_id = $CI->session->userdata('CurrentBranchUID');
        $context->org_name = $CI->session->userdata('CurrentOrgName');
        $context->branch_name = $CI->session->userdata('CurrentBranchName');
        
        return $context;
        
    }
}

if (!function_exists('set_user_context')) {
    function set_user_context($user_data, $org_data = null, $branch_data = null) {
        $CI =& get_instance();
        
        $session_data = [
            'UserUID' => $user_data->UserUID,
            'UserName' => $user_data->UserName,
            'UserEmail' => $user_data->EmailAddress
        ];
        
        if ($org_data) {
            $session_data['CurrentOrgUID'] = $org_data->OrgUID;
            $session_data['CurrentOrgName'] = $org_data->Name;
        }
        
        if ($branch_data) {
            $session_data['CurrentBranchUID'] = $branch_data->BranchUID;
            $session_data['CurrentBranchName'] = $branch_data->Name;
            $session_data['CurrentBranchCode'] = $branch_data->BranchCode;
        }
        
        $CI->session->set_userdata($session_data);
    }
}

?>