<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Main extends CI_Controller {
	
	public function __construct() {
        parent::__construct();
        
    }

    public function index() {

        $file_path = FCPATH . 'content/countrydetails.json';

        $json_data = file_get_contents($file_path);

        $Data['CountryInfo'] = json_decode($json_data, true);

        $this->load->view('main/dashboardview', $Data);

    }

}

?>