<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Subscription_model extends CI_Model {

    private $ReadDb;

    public function __construct() {
        parent::__construct();
        $this->ReadDb = $this->load->database('ReadDB', TRUE);
    }

    // â”€â”€ User subscription info (UserTbl) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    public function getUserSubscription(int $userUID): object {
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
            notifyError($e, 'Subscription_model::getUserSubscription');
            $result->Error   = TRUE;
            $result->Message = $e->getMessage();
            $result->Data    = null;
        }
        return $result;
    }

    // â”€â”€ User email info for notifications â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    public function getUserEmailInfo(int $userUID): object {
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
            notifyError($e, 'Subscription_model::getUserEmailInfo');
            $result->Error   = TRUE;
            $result->Message = $e->getMessage();
            $result->Data    = null;
        }
        return $result;
    }

    // â”€â”€ Check if an expiry-warning notification was already sent today â”€â”€â”€â”€â”€â”€â”€â”€â”€
    public function isNotificationSentToday(int $userUID, string $notificationType, string $today): object {
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
            notifyError($e, 'Subscription_model::isNotificationSentToday');
            $result->Error      = TRUE;
            $result->Message    = $e->getMessage();
            $result->AlreadySent = false;
        }
        return $result;
    }

    // â”€â”€ All subscription plans â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    public function getSubscriptionPlans(bool $activeOnly = true): object {
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
            notifyError($e, 'Subscription_model::getSubscriptionPlans');
            $result->Error   = TRUE;
            $result->Message = $e->getMessage();
            $result->Data    = [];
        }
        return $result;
    }

    public function getUserSubscriptionHistory(int $userUID, int $limit = 50): array {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select('*');
            $this->ReadDb->from('Users.SubscriptionHistoryTbl');
            $this->ReadDb->where('UserUID', (int)$userUID);
            $this->ReadDb->order_by('CreatedOn', 'DESC');
            $this->ReadDb->limit((int)$limit);
            $query = $this->ReadDb->get();
            return $query ? $query->result() : [];
        } catch (Exception $e) {
            notifyError($e, 'Subscription_model::getUserSubscriptionHistory');
            return [];
        }
    }

    // â”€â”€ Single active plan by plan code â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    public function getPlanByCode(string $planCode): object {
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
            notifyError($e, 'Subscription_model::getPlanByCode');
            $result->Error   = TRUE;
            $result->Message = $e->getMessage();
            $result->Data    = null;
        }
        return $result;
    }
}
