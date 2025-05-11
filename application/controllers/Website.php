<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Website extends CI_Controller {
	
	public function __construct() {
        parent::__construct();
        
    }

    public function index() {

        $this->load->library('curlservice');
        $CountryResp = $this->curlservice->retrieve('https://r2k-enterprises.s3.ap-south-1.amazonaws.com/global/countrydetails.json', 'GET', []);

        // $file_path = FCPATH . 'content/countrydetails.json';

        // $json_data = file_get_contents($file_path);

        // $Countries = json_decode($json_data, true);

        $Countries = $CountryResp->Data;
        usort($Countries, function ($a, $b) {
            return strcmp($a['name'], $b['name']);
        });

        $Data['CountryInfo'] = $Countries;
        
        $this->load->view('website/view', $Data);

    }

}