<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Quotations extends CI_Controller {

    public $pageData = array();
    private $EndReturnData;
    private $pageModuleUID;

    public function __construct() {
        parent::__construct();

        $this->pageModuleUID = 10;
        $this->load->helper('transaction');

    }

    public function index() {

        try {

            $GeneralSettings = ($this->redis_cache->get('Redis_UserGenSettings')->Value) ?? new stdClass();
            $limit = $GeneralSettings->RowLimit ?? 10;
            $this->pageData['JwtData']->GenSettings = $GeneralSettings;

            $this->load->view('transactions/quotations/view', $this->pageData);

        } catch (Exception $e) {
            redirect('dashboard', 'refresh');
        }
        
    }

    public function create() {

        try {

            $GeneralSettings = $this->redis_cache->get('Redis_UserGenSettings')->Value ?? NULL;
            $this->pageData['JwtData']->GenSettings = $GeneralSettings;

            $this->load->model('global_model');
            $GetCountryInfo = $this->global_model->getCountryInfo();
            $this->pageData['CountryInfo'] = $GetCountryInfo->Error === FALSE ? $GetCountryInfo->Data : [];

            $this->pageData['StateData'] = [];
            $this->pageData['CityData'] = [];

            $OrgCountryISO2 = $this->pageData['JwtData']->User->OrgCISO2;
            if(!empty($OrgCountryISO2)) {
                $StateInfo = $this->global_model->getStateofCountry($OrgCountryISO2);
                if($StateInfo->Error === FALSE) {
                    $this->pageData['StateData'] = $StateInfo->Data;
                }
                $CityInfo = $this->global_model->getCityofCountry($OrgCountryISO2);
                if($CityInfo->Error === FALSE) {
                    $this->pageData['CityData'] = $CityInfo->Data;
                }
            }

            $this->pageData['JwtData']->ModuleUID = $this->pageModuleUID;

            $this->load->model('transactions_model');
            $this->pageData['PrefixData'] = $this->transactions_model->getTransactionsPrefixDetails(['Prefix.ModuleUID' => $this->pageModuleUID])->Data;
            $this->pageData['TransPageSettings'] = $this->transactions_model->getTransPageSettings(['pageSettings.ModuleUID' => $this->pageModuleUID]);

            /** Product Details */
            $this->pageData['PrimaryUnitInfo'] = $this->global_model->getPrimaryUnitInfo()->Data ?? [];
            $this->pageData['DiscTypeInfo']    = $this->global_model->getDiscountTypeInfo()->Data ?? [];
            $this->pageData['ProdTypeInfo']    = $this->global_model->getProductTypeInfo()->Data ?? [];
            $this->pageData['ProdTaxInfo']     = $this->global_model->getProductTaxInfo()->Data ?? [];
            $this->pageData['TaxDetInfo']      = $this->global_model->getTaxDetailsInfo()->Data ?? [];
            
            $this->load->model('products_model');
            $this->pageData['SizeInfo']   = $this->products_model->getSizeDetails([]) ?? [];
            $this->pageData['BrandInfo']  = $this->products_model->getBrandDetails([]) ?? [];
            $this->pageData['fltCategoryData'] = $this->products_model->getCategoriesDetails([]) ?? [];

            $this->pageData['fltStorageData'] = [];
            if (!empty($this->pageData['JwtData']->GenSettings->EnableStorage)) {
                $this->load->model('storage_model');
                $this->pageData['fltStorageData'] = $this->storage_model->getStorageDetails([]) ?? [];
            }
            
            $this->load->view('transactions/quotations/forms/add', $this->pageData);

        } catch (Exception $e) {
            redirect('dashboard', 'refresh');
        }

    }

}