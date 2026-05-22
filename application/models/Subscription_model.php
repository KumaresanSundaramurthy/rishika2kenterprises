<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Subscription_model extends CI_Model {

    private $ReadDb;

    public function __construct() {
        parent::__construct();
        $this->ReadDb = $this->load->database('ReadDB', TRUE);
    }

    // ── User subscription info (UserTbl) ──────────────────────────────────────
    public function getUserSubscription($userUID) {
        $result = new stdClass();
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select('UserUID, OrgUID, SubscriptionStatus, SubscriptionPlan,
                                   SubscriptionStartDate, SubscriptionEndDate, GracePeriodDays');
            $this->ReadDb->from('Users.UserTbl');
            $this->ReadDb->where('UserUID', (int)$userUID);
            $this->ReadDb->limit(1);
            $query = $this->ReadDb->get();
            $result->Error = FALSE;
            $result->Data  = ($query && $query->num_rows() > 0) ? $query->row() : null;
        } catch (Exception $e) {
            log_message('error', 'Subscription_model::getUserSubscription — ' . $e->getMessage());
            $result->Error   = TRUE;
            $result->Message = $e->getMessage();
            $result->Data    = null;
        }
        return $result;
    }

    // ── User email info for notifications ─────────────────────────────────────
    public function getUserEmailInfo($userUID) {
        $result = new stdClass();
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select('EmailAddress, FirstName, LastName');
            $this->ReadDb->from('Users.UserTbl');
            $this->ReadDb->where('UserUID', (int)$userUID);
            $this->ReadDb->limit(1);
            $query = $this->ReadDb->get();
            $result->Error = FALSE;
            $result->Data  = ($query && $query->num_rows() > 0) ? $query->row() : null;
        } catch (Exception $e) {
            log_message('error', 'Subscription_model::getUserEmailInfo — ' . $e->getMessage());
            $result->Error   = TRUE;
            $result->Message = $e->getMessage();
            $result->Data    = null;
        }
        return $result;
    }

    // ── Check if an expiry-warning notification was already sent today ─────────
    public function isNotificationSentToday($userUID, $notificationType, $today) {
        $result = new stdClass();
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select('NotificationUID');
            $this->ReadDb->from('Users.SubscriptionNotificationTbl');
            $this->ReadDb->where('UserUID',          (int)$userUID);
            $this->ReadDb->where('NotificationType', $notificationType);
            $this->ReadDb->where('SentOn >=',        $today . ' 00:00:00');
            $this->ReadDb->where('SentOn <=',        $today . ' 23:59:59');
            $this->ReadDb->limit(1);
            $query = $this->ReadDb->get();
            $result->Error    = FALSE;
            $result->AlreadySent = ($query && $query->num_rows() > 0);
        } catch (Exception $e) {
            log_message('error', 'Subscription_model::isNotificationSentToday — ' . $e->getMessage());
            $result->Error      = TRUE;
            $result->Message    = $e->getMessage();
            $result->AlreadySent = false;
        }
        return $result;
    }

    // ── All subscription plans ────────────────────────────────────────────────
    public function getSubscriptionPlans($activeOnly = true) {
        $result = new stdClass();
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select('*');
            $this->ReadDb->from('Users.SubscriptionPlanTbl');
            if ($activeOnly) {
                $this->ReadDb->where('IsActive', 1);
            }
            $this->ReadDb->order_by('Price', 'ASC');
            $query = $this->ReadDb->get();
            $result->Error = FALSE;
            $result->Data  = $query ? $query->result() : [];
        } catch (Exception $e) {
            log_message('error', 'Subscription_model::getSubscriptionPlans — ' . $e->getMessage());
            $result->Error   = TRUE;
            $result->Message = $e->getMessage();
            $result->Data    = [];
        }
        return $result;
    }

    // ── Single active plan by plan code ──────────────────────────────────────
    public function getPlanByCode($planCode) {
        $result = new stdClass();
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select('*');
            $this->ReadDb->from('Users.SubscriptionPlanTbl');
            $this->ReadDb->where('PlanCode', $planCode);
            $this->ReadDb->where('IsActive', 1);
            $this->ReadDb->limit(1);
            $query = $this->ReadDb->get();
            $result->Error = FALSE;
            $result->Data  = ($query && $query->num_rows() > 0) ? $query->row() : null;
        } catch (Exception $e) {
            log_message('error', 'Subscription_model::getPlanByCode — ' . $e->getMessage());
            $result->Error   = TRUE;
            $result->Message = $e->getMessage();
            $result->Data    = null;
        }
        return $result;
    }
}
