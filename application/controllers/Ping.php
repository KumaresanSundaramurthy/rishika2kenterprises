<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Ping extends CI_Controller {

    public function __construct() {
        parent::__construct();
    }

    public function check() {
        $this->output
            ->set_status_header(200)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode(['status' => 'ok', 'timestamp' => time()]))
            ->_display();
        exit;
    }

}
