<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Auditlog {

    private $CI;

    public function __construct() {
        $this->CI =& get_instance();
    }

    /**
     * Record a user action in Security.UserAuditLogTbl.
     *
     * @param int    $orgUID
     * @param int    $userUID
     * @param string $action
     * @param string $entityType
     * @param int    $entityUID
     * @param string $entityRef
     * @param array  $details
     * @param string $summary
     * @param string $module
     * @param string $auditCategory  ENUM: SYSTEM|SECURITY|TRANSACTION|MASTER|PAYMENT|SETTINGS
     * @param string $result         SUCCESS|FAILED
     * @param string $errorMessage   Populated when result = FAILED
     * @param string $source         WEB|MOBILE|API|SYSTEM
     * @param array  $oldValues      Record state before action (NULL on CREATE)
     * @param array  $newValues      Record state after action (NULL on DELETE)
     * @return void
     */
    public function log(
        int    $orgUID,
        int    $userUID,
        string $action,
        string $entityType,
        int    $entityUID,
        string $entityRef      = '',
        array  $details        = [],
        string $summary        = '',
        string $module         = '',
        string $auditCategory  = '',
        string $result         = 'SUCCESS',
        string $errorMessage   = '',
        string $source         = 'WEB',
        array  $oldValues      = [],
        array  $newValues      = [],
        array  $formDataJson   = []
    ): void {
        try {
            $this->CI->load->model('dbwrite_model');
            $ip = $this->CI->input->ip_address();

            // Auto-resolve display name from JWT (no extra DB query)
            $userName = '';
            if (isset($this->CI->pageData['JwtData']->User)) {
                $u        = $this->CI->pageData['JwtData']->User;
                $userName = trim(($u->FirstName ?? '') . ' ' . ($u->LastName ?? ''));
            }

            // Default module to entityType when not supplied
            if ($module === '') {
                $module = $entityType;
            }

            // Auto-resolve session, user-agent, device type — no caller needed
            $sessionId = session_id();
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $ua        = strtolower($userAgent);
            if ($ua === '') {
                $deviceType = 'Unknown';
            } elseif (strpos($ua, 'bot') !== false || strpos($ua, 'crawl') !== false || strpos($ua, 'spider') !== false) {
                $deviceType = 'Bot';
            } elseif (strpos($ua, 'tablet') !== false || strpos($ua, 'ipad') !== false) {
                $deviceType = 'Tablet';
            } elseif (strpos($ua, 'mobile') !== false || strpos($ua, 'android') !== false || strpos($ua, 'iphone') !== false) {
                $deviceType = 'Mobile';
            } else {
                $deviceType = 'Desktop';
            }

            $validResults  = ['SUCCESS', 'FAILED'];
            $validSources  = ['WEB', 'MOBILE', 'API', 'SYSTEM'];
            $validDevices  = ['Desktop', 'Mobile', 'Tablet', 'Bot', 'Unknown'];
            $validCategory = ['SYSTEM', 'SECURITY', 'TRANSACTION', 'MASTER', 'PAYMENT', 'SETTINGS'];

            $this->CI->dbwrite_model->insertAuditLog([
                'OrgUID'        => $orgUID,
                'UserUID'       => $userUID,
                'UserName'      => $userName !== '' ? substr($userName, 0, 120) : NULL,
                'Action'        => substr($action, 0, 50),
                'EntityType'    => substr($entityType, 0, 30),
                'Module'        => substr($module, 0, 60),
                'EntityUID'     => $entityUID,
                'EntityRef'     => $entityRef !== '' ? substr($entityRef, 0, 100) : NULL,
                'Summary'       => $summary !== '' ? substr($summary, 0, 500) : NULL,
                'IPAddress'     => ($ip && $ip !== '0.0.0.0') ? $ip : NULL,
                'Details'       => !empty($details) ? json_encode($details) : NULL,
                'AuditCategory' => ($auditCategory !== '' && in_array($auditCategory, $validCategory)) ? $auditCategory : NULL,
                'Result'        => in_array($result, $validResults) ? $result : 'SUCCESS',
                'ErrorMessage'  => $errorMessage !== '' ? substr($errorMessage, 0, 500) : NULL,
                'Source'        => in_array($source, $validSources) ? $source : 'WEB',
                'SessionID'     => $sessionId !== '' ? substr($sessionId, 0, 100) : NULL,
                'UserAgent'     => $userAgent !== '' ? substr($userAgent, 0, 500) : NULL,
                'DeviceType'    => in_array($deviceType, $validDevices) ? $deviceType : 'Unknown',
                'OldValues'     => !empty($oldValues)    ? json_encode($oldValues)    : NULL,
                'NewValues'     => !empty($newValues)    ? json_encode($newValues)    : NULL,
                'FormDataJson'  => !empty($formDataJson) ? json_encode($formDataJson) : NULL,
            ]);
        } catch (Exception $e) {
            log_message('error', 'Auditlog::log failed: ' . $e->getMessage());
        }
    }

}
