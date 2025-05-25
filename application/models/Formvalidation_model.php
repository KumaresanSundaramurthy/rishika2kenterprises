<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Formvalidation_model extends CI_Model {

	function __construct() {
        parent::__construct();
		
		$this->load->library('form_validation');
        $this->load->helper('security');
		
    }

    public function validateForm($data) {

        $this->form_validation->set_data($data);

        $dd['FirstName'] = array('field' => 'FirstName', 'label' => 'First Name', 'rules' => 'trim|required');
        $dd['LastName'] = array('field' => 'LastName', 'label' => 'Last Name', 'rules' => 'trim|alpha_numeric');
        $dd['MobileNumber'] = array('field' => 'MobileNumber', 'label' => 'Mobile Number', 'rules' => 'trim|regex_match[/^[0-9]{10}$/]');
        $dd['EmailAddress'] = array('field' => 'EmailAddress', 'label' => 'Email Address', 'rules' => 'trim|valid_email');
        $dd['UserName'] = array('field' => 'UserName', 'label' => 'User Name', 'rules' => 'trim|required');
        $dd['Password'] = array('field' => 'Password', 'label' => 'Password', 'rules' => 'trim|required|min_length[6]');

        $dd['UserUID'] = array('field' => 'UserUID', 'label' => 'User Information', 'rules' => 'required|xss_clean|trim|numeric');
        $dd['NewPassword'] = array('field' => 'NewPassword', 'label' => 'New Password', 'rules' => 'trim|required|min_length[6]');
        $dd['ConfirmPassword'] = array('field' => 'ConfirmPassword', 'label' => 'Confirm Password', 'rules' => 'trim|required|matches[NewPassword]');

        $config = array();

        foreach($data as $key=>$value) {
            if (array_key_exists($key, $dd)) {
                array_push($config, $dd[$key]);
            }
        }

        $this->form_validation->set_rules($config);

        if ($this->form_validation->run() == FALSE) {

            return validation_errors();
            
        } else {

            return '';
        }

    }

    public function orgValidateForm($data) {

        $this->form_validation->set_data($data);

        $dd['OrgUID'] = array('field' => 'OrgUID', 'label' => 'Organisation UID', 'rules' => 'required|xss_clean|trim|numeric');
        $dd['Name'] = array('field' => 'Name', 'label' => 'Company Name', 'rules' => 'trim|required|xss_clean|min_length[6]|max_length[100]');
        $dd['BrandName'] = array('field' => 'BrandName', 'label' => 'Brand Name', 'rules' => 'trim|required|xss_clean|min_length[6]|max_length[100]');
        $dd['Description'] = array('field' => 'Description', 'label' => 'Description', 'rules' => 'trim|xss_clean|max_length[100]');
        $dd['CountryCode'] = array('field' => 'CountryCode', 'label' => 'Country', 'rules' => 'trim|required|xss_clean');
        $dd['MobileNumber'] = array('field' => 'MobileNumber', 'label' => 'Mobile Number', 'rules' => 'trim|required|xss_clean');
        $dd['EmailAddress'] = array('field' => 'EmailAddress', 'label' => 'Email Address', 'rules' => 'trim|required|xss_clean|min_length[6]|max_length[100]');
        $dd['GSTIN'] = array('field' => 'GSTIN', 'label' => 'GSTIN', 'rules' => 'trim|xss_clean|max_length[50]');

        $dd['OrgBussTypeUID'] = array('field' => 'OrgBussTypeUID', 'label' => 'Business Type', 'rules' => 'required|xss_clean|trim|numeric');
        $dd['AlternateNumber'] = array('field' => 'AlternateNumber', 'label' => 'Alternate Number', 'rules' => 'xss_clean|trim');
        $dd['TimezoneUID'] = array('field' => 'TimezoneUID', 'label' => 'Timezone', 'rules' => 'xss_clean|trim|numeric');
        $dd['PANNumber'] = array('field' => 'PANNumber', 'label' => 'PAN Number', 'rules' => 'trim|xss_clean|max_length[100]');
        $dd['Website'] = array('field' => 'Website', 'label' => 'Website', 'rules' => 'trim|xss_clean|max_length[255]');

        $config = array();

        foreach($data as $key=>$value) {
            if (array_key_exists($key, $dd)) {
                array_push($config, $dd[$key]);
            }
        }

        $this->form_validation->set_rules($config);

        if ($this->form_validation->run() == FALSE) {

            return validation_errors();
            
        } else {

            return '';
        }

    }

}