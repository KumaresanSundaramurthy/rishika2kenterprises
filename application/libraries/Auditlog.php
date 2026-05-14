<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Auditlog {

    private $CI;

    public function __construct() {
        $this->CI =& get_instance();
    }

    /**
     * Record a user action in Security.UserAuditLogTbl.
     *
     * @param int    $orgUID      Organisation UID
     * @param int    $userUID     User who performed the action
     * @param string $action      Action constant e.g. CREATE_INVOICE, CANCEL_INVOICE
     * @param string $entityType  Entity name e.g. Invoice, Customer
     * @param int    $entityUID   Primary key of the affected record
     * @param string $entityRef   Human-readable reference (invoice number, customer name, etc.)
     * @param array  $details     Optional extra key/value pairs stored as JSON
     */
    public function log($orgUID, $userUID, $action, $entityType, $entityUID, $entityRef = '', array $details = []) {
        try {
            $this->CI->load->model('dbwrite_model');
            $ip = $this->CI->input->ip_address();
            $this->CI->dbwrite_model->insertAuditLog([
                'OrgUID'     => (int) $orgUID,
                'UserUID'    => (int) $userUID,
                'Action'     => substr((string) $action, 0, 50),
                'EntityType' => substr((string) $entityType, 0, 30),
                'EntityUID'  => (int) $entityUID,
                'EntityRef'  => $entityRef !== '' ? substr((string) $entityRef, 0, 100) : NULL,
                'IPAddress'  => ($ip && $ip !== '0.0.0.0') ? $ip : NULL,
                'Details'    => !empty($details) ? json_encode($details) : NULL,
            ]);
        } catch (Exception $e) {
            log_message('error', 'Auditlog::log failed: ' . $e->getMessage());
        }
    }

}
