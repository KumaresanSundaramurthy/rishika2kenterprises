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
        $dd['CountryISO2'] = array('field' => 'CountryISO2', 'label' => 'Country ISO2', 'rules' => 'trim|required|xss_clean');
        // Mobile validation
        $dd['MobileNumber'] = array('field' => 'MobileNumber', 'label' => 'Mobile Number', 'rules' => ['trim', 'xss_clean', ['validate_mobile_number', [$this, 'validate_mobile_number']]]);

        $dd['EmailAddress'] = array('field' => 'EmailAddress', 'label' => 'Email Address', 'rules' => 'trim|required|xss_clean|min_length[6]|max_length[100]');
        
        // GSTIN validation
        $dd['GSTIN'] = array('field' => 'GSTIN', 'label' => 'GSTIN', 'rules' => ['trim', 'xss_clean', 'max_length[15]', ['validate_gstin_number', [$this, 'validate_gstin_number']]]);
        
        // Upload Image
        $dd['UploadImage'] = array('field' => 'UploadImage', 'label' => 'Upload Image', 'rules' => [['checkImageType', [$this, 'checkImageType']]]);

        $dd['OrgBussTypeUID'] = array('field' => 'OrgBussTypeUID', 'label' => 'Business Type', 'rules' => 'required|xss_clean|trim|numeric');
        $dd['AlternateNumber'] = array('field' => 'AlternateNumber', 'label' => 'Alternate Number', 'rules' => 'xss_clean|trim');
        $dd['TimezoneUID'] = array('field' => 'TimezoneUID', 'label' => 'Timezone', 'rules' => 'xss_clean|trim|numeric');

        // PAN validation
        $dd['PANNumber'] = array('field' => 'PANNumber', 'label' => 'PAN Number', 'rules' => ['trim', 'xss_clean', ['validate_pan_number', [$this, 'validate_pan_number']]]);
        
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

    public function custValidateForm($data) {

        $this->form_validation->set_data($data);

        $dd['CustomerUID'] = ['field' => 'CustomerUID', 'label' => 'Customer UID', 'rules' => 'required|xss_clean|trim|numeric'];

        $dd['Name'] = array('field' => 'Name', 'label' => 'Name', 'rules' => 'trim|required|xss_clean|min_length[3]|max_length[100]');
        $dd['Area'] = array('field' => 'Area', 'label' => 'Area', 'rules' => 'trim|xss_clean|min_length[3]|max_length[100]');
        $dd['CountryCode'] = array('field' => 'CountryCode', 'label' => 'Country', 'rules' => 'trim|required|xss_clean');
        $dd['CountryISO2'] = array('field' => 'CountryISO2', 'label' => 'Country ISO2', 'rules' => 'trim|xss_clean');
        
        // Mobile validation
        $dd['MobileNumber'] = array('field' => 'MobileNumber', 'label' => 'Mobile Number', 'rules' => ['trim', 'xss_clean', ['validate_mobile_number', [$this, 'validate_mobile_number']]]);

        $dd['EmailAddress'] = array('field' => 'EmailAddress', 'label' => 'Email Address', 'rules' => 'trim|xss_clean|min_length[6]|max_length[100]');
        $dd['DebitCreditAmount'] = array('field' => 'DebitCreditAmount', 'label' => 'Debit Credit Amount', 'rules' => 'trim|xss_clean|numeric');
        $dd['DebitCreditCheck'] = array('field' => 'DebitCreditCheck', 'label' => 'Debit Credit Check', 'rules' => 'trim|required|xss_clean|in_list[Debit,Credit]');

        // PAN validation
        $dd['PANNumber'] = array('field' => 'PANNumber', 'label' => 'PAN Number', 'rules' => ['trim', 'xss_clean', ['validate_pan_number', [$this, 'validate_pan_number']]]);

        $dd['ContactPerson'] = array('field' => 'ContactPerson', 'label' => 'Contact Person', 'rules' => 'trim|xss_clean|min_length[3]|max_length[100]');
        // Validate Date Format
        $dd['CPDateOfBirth'] = array('field' => 'CPDateOfBirth', 'label' => 'Date of Birth', 'rules' => ['trim', 'xss_clean', 'regex_match[/^\d{4}-\d{2}-\d{2}$/]', ['validateDateofBirthFormat', [$this, 'validateDateofBirthFormat']]]);
        
        // GSTIN validation
        $dd['GSTIN'] = array('field' => 'GSTIN', 'label' => 'GSTIN', 'rules' => ['trim', 'xss_clean', 'max_length[15]', ['validate_gstin_number', [$this, 'validate_gstin_number']]]);
        $dd['CompanyName'] = array('field' => 'CompanyName', 'label' => 'Company Name', 'rules' => 'trim|xss_clean|min_length[6]|max_length[100]');
        // Upload Image
        $dd['UploadImage'] = array('field' => 'UploadImage', 'label' => 'Upload Image', 'rules' => [['checkImageType', [$this, 'checkImageType']]]);

        $dd['DiscountPercent'] = array('field' => 'DiscountPercent', 'label' => 'Discount Percent', 'rules' => 'trim|xss_clean|numeric|greater_than_equal_to[0]|less_than_equal_to[100]');
        $dd['CreditPeriod'] = array('field' => 'CreditPeriod', 'label' => 'Credit Period', 'rules' => 'trim|xss_clean|numeric');
        $dd['CreditLimit'] = array('field' => 'CreditLimit', 'label' => 'Credit Limit', 'rules' => 'trim|xss_clean|numeric');

        $dd['Notes'] = array('field' => 'Notes', 'label' => 'Notes', 'rules' => 'trim|xss_clean|max_length[250]');
        $dd['Tags'] = array('field' => 'Tags', 'label' => 'Tags', 'rules' => 'trim|xss_clean');
        $dd['CCEmails'] = array('field' => 'CCEmails', 'label' => 'CCEmails', 'rules' => 'trim|xss_clean');

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

    public function vendorValidateForm($data) {

        $this->form_validation->set_data($data);

        $dd['VendorUID'] = array('field' => 'VendorUID', 'label' => 'Vendor UID', 'rules' => 'required|xss_clean|trim|numeric');

        $dd['Name'] = array('field' => 'Name', 'label' => 'Name', 'rules' => 'trim|required|xss_clean|min_length[3]|max_length[100]');
        $dd['Area'] = array('field' => 'Area', 'label' => 'Area', 'rules' => 'trim|xss_clean|min_length[3]|max_length[100]');

        $dd['CountryCode'] = array('field' => 'CountryCode', 'label' => 'Country', 'rules' => 'trim|required|xss_clean');
        $dd['CountryISO2'] = array('field' => 'CountryISO2', 'label' => 'Country ISO2', 'rules' => 'trim|xss_clean');
        // Mobile validation
        $dd['MobileNumber'] = array('field' => 'MobileNumber', 'label' => 'Mobile Number', 'rules' => ['trim', 'xss_clean', ['validate_mobile_number', [$this, 'validate_mobile_number']]]);

        $dd['EmailAddress'] = array('field' => 'EmailAddress', 'label' => 'Email Address', 'rules' => 'trim|xss_clean|min_length[6]|max_length[100]');

        $dd['DebitCreditAmount'] = array('field' => 'DebitCreditAmount', 'label' => 'Debit Credit Amount', 'rules' => 'trim|xss_clean|numeric');
        $dd['DebitCreditCheck'] = array('field' => 'DebitCreditCheck', 'label' => 'Debit Credit Check', 'rules' => 'trim|required|xss_clean|in_list[Debit,Credit]');

        // PAN validation
        $dd['PANNumber'] = array('field' => 'PANNumber', 'label' => 'PAN Number', 'rules' => ['trim', 'xss_clean', ['validate_pan_number', [$this, 'validate_pan_number']]]);

        $dd['ContactPerson'] = array('field' => 'ContactPerson', 'label' => 'Contact Person', 'rules' => 'trim|xss_clean|min_length[3]|max_length[100]');
        // Validate Date Format
        $dd['CPDateOfBirth'] = array('field' => 'CPDateOfBirth', 'label' => 'Date of Birth', 'rules' => ['trim', 'xss_clean', 'regex_match[/^\d{4}-\d{2}-\d{2}$/]', ['validateDateofBirthFormat', [$this, 'validateDateofBirthFormat']]]);

        // GSTIN validation
        $dd['GSTIN'] = array('field' => 'GSTIN', 'label' => 'GSTIN', 'rules' => ['trim', 'xss_clean', 'max_length[15]', ['validate_gstin_number', [$this, 'validate_gstin_number']]]);
        $dd['CompanyName'] = array('field' => 'CompanyName', 'label' => 'Company Name', 'rules' => 'trim|xss_clean|min_length[6]|max_length[100]');

        // Upload Image
        $dd['UploadImage'] = array('field' => 'UploadImage', 'label' => 'Upload Image', 'rules' => [['checkImageType', [$this, 'checkImageType']]]);
        
        $dd['Notes'] = array('field' => 'Notes', 'label' => 'Notes', 'rules' => 'trim|xss_clean|max_length[250]');

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

    public function productValidateForm($data) {

        $this->form_validation->set_data($data);

        $dd['ProductUID'] = array('field' => 'ProductUID', 'label' => 'Product UID', 'rules' => 'required|xss_clean|trim|numeric');
        $dd['ItemName'] = array('field' => 'ItemName', 'label' => 'Item Name', 'rules' => 'trim|required|xss_clean|min_length[3]|max_length[100]');
        $dd['ProductType'] = array('field' => 'ProductType', 'label' => 'Product Type', 'rules' => 'trim|xss_clean|required|in_list[Product,Service]');
        $dd['SellingPrice'] = array('field' => 'SellingPrice', 'label' => 'Selling Price', 'rules' => 'trim|required|xss_clean|numeric|greater_than[0]');
        $dd['SellingTaxOption'] = array('field' => 'SellingTaxOption', 'label' => 'Selling Tax Option', 'rules' => 'trim|required|xss_clean|numeric');
        $dd['TaxPercentage'] = array('field' => 'TaxPercentage', 'label' => 'Tax Percentage', 'rules' => 'trim|xss_clean|required|numeric');
        $dd['PrimaryUnit'] = array('field' => 'PrimaryUnit', 'label' => 'Primary Unit', 'rules' => 'trim|xss_clean|required|numeric');
        $dd['Category'] = array('field' => 'Category', 'label' => 'Category', 'rules' => 'trim|xss_clean|required|numeric');

        $dd['HSNCode'] = array('field' => 'HSNCode', 'label' => 'HSN/ SAC Code', 'rules' => 'trim|xss_clean|max_length[100]');
        $dd['PurchasePrice'] = array('field' => 'PurchasePrice', 'label' => 'Purchase Price', 'rules' => 'trim|xss_clean|numeric|greater_than[0]');
        $dd['PurchaseTaxOption'] = array('field' => 'PurchaseTaxOption', 'label' => 'Purchase Tax Option', 'rules' => 'trim|xss_clean|numeric');
        $dd['PartNumber'] = array('field' => 'PartNumber', 'label' => 'Part Number', 'rules' => 'trim|xss_clean|max_length[25]');

        $dd['OpeningQuantity'] = array('field' => 'OpeningQuantity', 'label' => 'Opening Quantity', 'rules' => 'trim|xss_clean|numeric');
        $dd['OpeningPurchasePrice'] = array('field' => 'OpeningPurchasePrice', 'label' => 'Opening Purchase Price', 'rules' => 'trim|xss_clean|numeric');
        $dd['OpeningStockValue'] = array('field' => 'OpeningStockValue', 'label' => 'Opening Stock Value', 'rules' => 'trim|xss_clean|numeric');
        
        // Upload Image
        $dd['UploadImage'] = array('field' => 'UploadImage', 'label' => 'Upload Image', 'rules' => [['checkImageType', [$this, 'checkImageType']]]);
        
        $dd['Discount'] = array('field' => 'Discount', 'label' => 'Discount', 'rules' => 'trim|xss_clean|numeric');
        $dd['DiscountOption'] = array('field' => 'DiscountOption', 'label' => 'Discount Option', 'rules' => 'trim|required|xss_clean|numeric');
        $dd['LowStockAlert'] = array('field' => 'LowStockAlert', 'label' => 'Low Stock Alert at', 'rules' => 'trim|xss_clean|numeric');

        $dd['BrandUID'] = array('field' => 'BrandUID', 'label' => 'Brand UID', 'rules' => 'xss_clean|trim|numeric');
        $dd['Standard'] = array('field' => 'Standard', 'label' => 'Standard', 'rules' => 'xss_clean|trim|max_length[100]');
        $dd['IsSizeApplicable'] = array('field' => 'IsSizeApplicable', 'label' => 'Is Size Applicable', 'rules' => ['trim', 'xss_clean', ['checkSizeRequired', [$this, 'checkSizeRequired']]]);

        if($this->pageData['JwtData']->GenSettings->EnableStorage == 1) {
            if($this->pageData['JwtData']->GenSettings->MandatoryStorage == 1) {
                $dd['StorageUID'] = array('field' => 'StorageUID', 'label' => 'Storage UID', 'rules' => 'required|xss_clean|trim|numeric');
            } else {
                $dd['StorageUID'] = array('field' => 'StorageUID', 'label' => 'Storage UID', 'rules' => 'xss_clean|trim|numeric');
            }
        }

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

    public function categoryValidateForm($data) {

        $this->form_validation->set_data($data);

        $dd['CategoryUID'] = array('field' => 'CategoryUID', 'label' => 'Category UID', 'rules' => 'required|xss_clean|trim|numeric');
        $dd['Name'] = array('field' => 'Name', 'label' => 'Name', 'rules' => 'trim|required|xss_clean|min_length[3]|max_length[100]');
        $dd['Description'] = array('field' => 'Description', 'label' => 'Description', 'rules' => 'trim|xss_clean|max_length[250]');
        // Upload Image
        $dd['UploadImage'] = array('field' => 'UploadImage', 'label' => 'Upload Image', 'rules' => [['checkImageType', [$this, 'checkImageType']]]);

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

    public function sizesValidateForm($data) {

        $this->form_validation->set_data($data);

        $dd['SizeUID'] = array('field' => 'SizeUID', 'label' => 'Size UID', 'rules' => 'required|xss_clean|trim|numeric');
        $dd['Name'] = array('field' => 'Name', 'label' => 'Name', 'rules' => 'trim|required|xss_clean|min_length[3]|max_length[100]');
        $dd['Description'] = array('field' => 'Description', 'label' => 'Description', 'rules' => 'trim|xss_clean|max_length[100]');

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

    public function brandsValidateForm($data) {

        $this->form_validation->set_data($data);

        $dd['BrandUID'] = array('field' => 'BrandUID', 'label' => 'Brand UID', 'rules' => 'required|xss_clean|trim|numeric');
        $dd['Name'] = array('field' => 'Name', 'label' => 'Name', 'rules' => 'trim|required|xss_clean|min_length[3]|max_length[100]');
        $dd['Description'] = array('field' => 'Description', 'label' => 'Description', 'rules' => 'trim|xss_clean|max_length[100]');

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

    public function storageValidateForm($data) {

        $this->form_validation->set_data($data);

        $dd['StorageUID'] = array('field' => 'StorageUID', 'label' => 'Category UID', 'rules' => 'required|xss_clean|trim|numeric');
        $dd['Name'] = array('field' => 'Name', 'label' => 'Name', 'rules' => 'trim|required|xss_clean|min_length[3]|max_length[100]');
        $dd['ShortName'] = array('field' => 'ShortName', 'label' => 'Short Name', 'rules' => 'trim|xss_clean|max_length[50]');
        $dd['StorageTypeUID'] = array('field' => 'StorageTypeUID', 'label' => 'Storage Type', 'rules' => 'trim|required|xss_clean|numeric');
        // Upload Image
        $dd['UploadImage'] = array('field' => 'UploadImage', 'label' => 'Upload Image', 'rules' => [['checkImageType', [$this, 'checkImageType']]]);

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

    public function profValidateForm($data) {

        $this->form_validation->set_data($data);

        $dd['userUid'] = array('field' => 'userUid', 'label' => 'User UID', 'rules' => 'required|xss_clean|trim|numeric');
        $dd['fistName'] = array('field' => 'fistName', 'label' => 'First Name', 'rules' => 'trim|required|xss_clean|min_length[3]|max_length[100]');
        $dd['CountryCode'] = array('field' => 'CountryCode', 'label' => 'Country', 'rules' => 'trim|required|xss_clean');
        $dd['CountryISO2'] = array('field' => 'CountryISO2', 'label' => 'Country ISO2', 'rules' => 'trim|required|xss_clean');
        $dd['MobileNumber'] = array('field' => 'MobileNumber', 'label' => 'Mobile Number', 'rules' => 'trim|required|xss_clean');
        // Upload Image
        $dd['UploadImage'] = array('field' => 'UploadImage', 'label' => 'Upload Image', 'rules' => [['checkImageType', [$this, 'checkImageType']]]);

        $dd['oldPassword']     = array('field' => 'oldPassword', 'label' => 'Old Password', 'rules' => 'trim|xss_clean|min_length[6]|max_length[20]');
        $dd['newPassword'] = array('field' => 'newPassword', 'label' => 'New Password', 'rules' => ['trim', 'xss_clean', 'min_length[6]', 'max_length[20]', ['check_new_password', [$this, 'check_new_password']]]);
        $dd['confirmPassword'] = array('field' => 'confirmPassword', 'label' => 'Confirm Password', 'rules' => 'trim|xss_clean|matches[newPassword]');

        $config = array();
        
        foreach($data as $key) {
            if (array_key_exists($key, $dd)) {
                array_push($config, $dd[$key]);
            }
        }

        if (!empty($data['oldPassword']) || !empty($data['newPassword']) || !empty($data['confirmPassword'])) {
            $dd['oldPassword']['rules']     .= '|required';
            $dd['newPassword']['rules']     .= '|required';
            $dd['confirmPassword']['rules'] .= '|required';
            $config[] = $dd['oldPassword'];
            $config[] = $dd['newPassword'];
            $config[] = $dd['confirmPassword'];
        }

        $this->form_validation->set_rules($config);
        if ($this->form_validation->run() == FALSE) {
            return validation_errors();
        } else {
            return '';
        }

    }

    public function quotationValidateForm($data) {

        $this->form_validation->set_data($data);

        $dd['customerSearch']    = ['field' => 'customerSearch',    'label' => 'Customer',          'rules' => 'required|xss_clean|trim|numeric|greater_than[0]'];
        $dd['transPrefixSelect'] = ['field' => 'transPrefixSelect', 'label' => 'Prefix',            'rules' => 'required|xss_clean|trim|numeric|greater_than[0]'];
        $dd['transNumber']       = ['field' => 'transNumber',       'label' => 'Transaction Number', 'rules' => 'required|xss_clean|trim|numeric|greater_than[0]'];
        $dd['transDate']         = ['field' => 'transDate',         'label' => 'Transaction Date',   'rules' => ['trim', 'required', 'xss_clean', 'regex_match[/^\d{4}-\d{2}-\d{2}$/]']];
        $dd['validityDate']      = ['field' => 'validityDate',      'label' => 'Validity Date',      'rules' => ['trim', 'xss_clean', 'regex_match[/^\d{4}-\d{2}-\d{2}$/]']];
        $dd['globalDiscount']    = ['field' => 'globalDiscount',    'label' => 'Global Discount',    'rules' => ['trim', 'xss_clean', 'numeric', 'greater_than_equal_to[0]', ['validateGlobalDiscount', [$this, 'validateGlobalDiscount']]]];
        $dd['extraDiscount']     = ['field' => 'extraDiscount',     'label' => 'Extra Discount',     'rules' => 'trim|xss_clean|numeric|greater_than_equal_to[0]'];
        $dd['extDiscountType']   = ['field' => 'extDiscountType',   'label' => 'Extra Discount Type','rules' => 'trim|xss_clean'];
        $dd['NetAmount']         = ['field' => 'NetAmount',         'label' => 'Net Amount',         'rules' => 'trim|xss_clean|numeric|greater_than_equal_to[0]'];
        $dd['transNotes']        = ['field' => 'transNotes',        'label' => 'Notes',              'rules' => 'trim|xss_clean|max_length[500]'];
        $dd['transTermsCond']    = ['field' => 'transTermsCond',    'label' => 'Terms & Conditions', 'rules' => 'trim|xss_clean|max_length[1000]'];
        $dd['referenceDetails']  = ['field' => 'referenceDetails',  'label' => 'Reference',          'rules' => 'trim|xss_clean|max_length[100]'];
        $dd['action']            = ['field' => 'action',            'label' => 'Action',             'rules' => 'trim|xss_clean|in_list[save,draft]'];

        $config = [];
        foreach ($data as $key => $value) {
            if (array_key_exists($key, $dd)) {
                $config[] = $dd[$key];
            }
        }

        $this->form_validation->set_rules($config);
        if ($this->form_validation->run() == FALSE) {
            return validation_errors();
        }
        return '';

    }

    public function validateQuotationItems($itemsJson) {

        if (empty($itemsJson)) return 'Items are required.';

        $items = json_decode($itemsJson, true);
        if (!is_array($items) || count($items) === 0) return 'At least one line item is required.';

        $validTaxPercents = [0, 5, 12, 18, 28]; // GST slabs — extend if needed

        foreach ($items as $index => $item) {
            $row = $index + 1;

            // Product UID
            $productUID = isset($item['id']) ? (int) $item['id'] : 0;
            if ($productUID <= 0) return "Row {$row}: Invalid product.";

            // Item name
            if (empty(trim($item['itemName'] ?? ''))) return "Row {$row}: Item name is required.";

            // Quantity — must be positive integer
            $qty = $item['quantity'] ?? '';
            if (!ctype_digit((string) $qty) || (int) $qty <= 0) {
                return "Row {$row}: Quantity must be a positive whole number.";
            }

            // Unit price (SellingPrice) — must be >= 0
            $unitPrice = isset($item['unitPrice']) ? $item['unitPrice'] : null;
            if ($unitPrice === null || !is_numeric($unitPrice) || (float) $unitPrice < 0) {
                return "Row {$row}: Selling price must be zero or greater.";
            }

            // Tax percent — must be numeric, >= 0, <= 100
            $taxPercent = isset($item['taxPercent']) ? $item['taxPercent'] : null;
            if ($taxPercent === null || !is_numeric($taxPercent)) {
                return "Row {$row}: Tax percentage is invalid.";
            }
            $taxPct = (float) $taxPercent;
            if ($taxPct < 0 || $taxPct > 100) {
                return "Row {$row}: Tax percentage must be between 0 and 100.";
            }
            if (!in_array($taxPercent, $validTaxPercents, true)) {
                return "Row {$row}: Tax percentage {$taxPct}% is not a valid GST slab.";
            }

            // CGST + SGST must sum to tax percent (for intra-state); skip if IGST > 0
            $cgst = (float) ($item['cgstPercent'] ?? 0);
            $sgst = (float) ($item['sgstPercent'] ?? 0);
            $igst = (float) ($item['igstPercent'] ?? 0);
            if ($igst == 0 && round($cgst + $sgst, 4) != round($taxPct, 4)) {
                return "Row {$row}: CGST + SGST must equal total tax percentage.";
            }

            // Per-item discount — must be >= 0
            $discountAmt = isset($item['discountAmount']) ? (float) $item['discountAmount'] : 0;
            if ($discountAmt < 0) {
                return "Row {$row}: Discount amount cannot be negative.";
            }

            // Discount cannot exceed line subtotal
            $lineSubtotal = (float) $qty * (float) $unitPrice;
            if ($discountAmt > $lineSubtotal) {
                return "Row {$row}: Discount cannot exceed line total ({$lineSubtotal}).";
            }

            // Line total — must be >= 0
            $lineTotal = isset($item['line_total']) ? (float) $item['line_total'] : 0;
            if ($lineTotal < 0) {
                return "Row {$row}: Line total cannot be negative.";
            }
        }

        return '';

    }

    /* ===== QUOTATION CUSTOM RULES ===== */
    public function validateGlobalDiscount($value) {
        if (empty($value) || (float) $value == 0) return true;

        $discount = (float) $value;
        if ($discount > 100) {
            $this->form_validation->set_message('validateGlobalDiscount', 'Global discount cannot exceed 100%.');
            return false;
        }
        return true;
    }

    public function transPrefixValidateForm($data) {

        $this->form_validation->set_data($data);

        $validSeparators = ['-', '/', '|', '_', '.'];
        $validPaddings   = ['1', '3', '5'];

        $dd['preModuleUID']      = ['field' => 'preModuleUID',      'label' => 'Module UID',   'rules' => 'required|xss_clean|trim|numeric|greater_than[0]'];
        $dd['transPrefixName']   = ['field' => 'transPrefixName',   'label' => 'Prefix Name',  'rules' => 'trim|required|xss_clean|min_length[2]|max_length[7]'];
        $dd['fiscalYearFormat']  = ['field' => 'fiscalYearFormat',  'label' => 'Year Format',  'rules' => 'trim|xss_clean|in_list[SHORT,LONG]'];
        $dd['prefixSeparator']   = ['field' => 'prefixSeparator',   'label' => 'Separator',    'rules' => 'trim|xss_clean|max_length[5]'];
        $dd['numberPadding']     = ['field' => 'numberPadding',     'label' => 'Number Pad',   'rules' => 'trim|xss_clean|numeric'];

        // companyShortName only required when includeShortName is checked
        if (!empty($data['includeShortName'])) {
            $dd['companyShortName'] = ['field' => 'companyShortName', 'label' => 'Short Name', 'rules' => 'trim|required|xss_clean|min_length[1]|max_length[10]'];
        }

        $config = [];
        foreach ($data as $key => $value) {
            if (array_key_exists($key, $dd)) {
                $config[] = $dd[$key];
            }
        }

        // Manual checks not easily expressed in CI rules
        if (!empty($data['prefixSeparator']) && !in_array($data['prefixSeparator'], $validSeparators)) {
            return 'Invalid separator selected.';
        }
        if (!empty($data['numberPadding']) && !in_array((string)$data['numberPadding'], $validPaddings)) {
            return 'Invalid number padding selected.';
        }

        $this->form_validation->set_rules($config);
        if ($this->form_validation->run() == FALSE) {
            return validation_errors();
        }
        return '';

    }

    /* ================= MOBILE ================= */
    public function validate_mobile_number($mobile) {

        if (empty($mobile)) return true;

        $post = $this->input->post();
        $countryCode = isset($post['CountryCode']) ? str_replace('+', '', $post['CountryCode']) : '91';

        $mobile = preg_replace('/[^0-9]/', '', $mobile);
        $mobile = ltrim($mobile, '0');

        $valid = false;
        switch ($countryCode) {
            case '91':  $valid = preg_match('/^[6-9]\d{9}$/', $mobile); break;
            case '1':   $valid = preg_match('/^\d{10}$/', $mobile); break;
            case '44':  $valid = preg_match('/^\d{10,11}$/', $mobile); break;
            case '61':  $valid = preg_match('/^\d{9}$/', $mobile); break;
            case '971': $valid = preg_match('/^\d{9}$/', $mobile); break;
            default:    $valid = preg_match('/^\d{5,15}$/', $mobile); break;
        }

        if (!$valid) {
            $messages = [
                '91'  => 'Invalid Indian mobile number. Must be 10 digits starting with 6–9.',
                '1'   => 'Invalid US/Canada mobile number.',
                '44'  => 'Invalid UK mobile number.',
                '61'  => 'Invalid Australian mobile number.',
                '971' => 'Invalid UAE mobile number.'
            ];
            $this->form_validation->set_message('validate_mobile_number', $messages[$countryCode] ?? 'Invalid mobile number.');
            return false;
        }
        return true;
    }

    /* ================= PAN ================= */
    public function validate_pan_number($pan) {
        if (empty($pan)) return true;

        $pan = strtoupper(trim($pan));
        if (!preg_match('/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/', $pan)) {
            $this->form_validation->set_message('validate_pan_number', 'Invalid PAN format (ABCDE1234F).');
            return false;
        }
        return true;
    }

    /* ================= GSTIN ================= */
    public function validate_gstin_number($gstin) {
        if (empty($gstin)) return true;

        $gstin = strtoupper(trim($gstin));
        if (strlen($gstin) !== 15 ||
            !preg_match('/^\d{2}[A-Z]{5}\d{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/', $gstin)) {
            $this->form_validation->set_message('validate_gstin_number', 'Invalid GSTIN format (15 characters).');
            return false;
        }
        return true;
    }

    /* ================= DATE ================= */
    public function validateDateofBirthFormat($date) {
        if (empty($date)) return true;

        $d = DateTime::createFromFormat('Y-m-d', $date);
        if ($d && $d->format('Y-m-d') === $date) return true;

        $this->form_validation->set_message('validateDateofBirthFormat', 'The {field} must be in YYYY-MM-DD format.');
        return false;
    }

    /* ================= IMAGE ================= */
    public function checkImageType($str = '') {
        return $this->globalservice->checkImageType($str);
    }

    /* ================= PASSWORD ================= */
    public function check_new_password($password) {
        $oldPassword = $this->input->post('oldPassword');
        if ($oldPassword === $password) {
            $this->form_validation->set_message('check_new_password', 'Old Password and New Password cannot be the same');
            return false;
        }
        if (strlen($password) < 6) {
            $this->form_validation->set_message('check_new_password', 'Password must be at least 6 characters.');
            return false;
        }
        return true;
    }

    /* ================= CHECK SIZE ================= */
    public function checkSizeRequired($IsSizeApplicable) {
        $SizeUID = $this->input->post('SizeUID', true) ?? NULL;
        if ($IsSizeApplicable && empty($SizeUID)) {
            $this->form_validation->set_message('checkSizeRequired', 'The Size field is required when Size Applicable is checked.');
            return false;
        }
        return true;

    }

}