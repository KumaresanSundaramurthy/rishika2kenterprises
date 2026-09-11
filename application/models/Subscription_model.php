<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Subscription_model extends CI_Model {

    private $ReadDb;

    public function __construct() {
        parent::__construct();
        $this->ReadDb = $this->load->database('ReadDB', TRUE);
    }

    // ── User subscription info (Billing.OrgSubscriptionTbl) ─────────────────────
    public function getUserSubscription(int $userUID): object {
        $result = new stdClass();
        try {
            $this->ReadDb->db_debug = FALSE;
            /* Aliases preserve the field names the Subscription library depends on */
            $this->ReadDb->select('U.UserUID, U.OrgUID,
                OS.OrgSubUID,
                OS.SectorPlanUID,
                OS.Status                               AS SubscriptionStatus,
                COALESCE(SP.PlanName, \'Trial\')        AS SubscriptionPlan,
                OS.StartDate                            AS SubscriptionStartDate,
                OS.EndDate                              AS SubscriptionEndDate,
                OS.GracePeriodDays');
            $this->ReadDb->from('Users.UserTbl AS U');
            $this->ReadDb->join('Billing.OrgSubscriptionTbl AS OS',       'OS.OrgUID = U.OrgUID AND OS.Status != \'Cancelled\'', 'left');
            $this->ReadDb->join('Billing.SectorPlanTbl AS SPT',           'SPT.SectorPlanUID = OS.SectorPlanUID',                'left');
            $this->ReadDb->join('Billing.SubscriptionPlansTbl AS SP',     'SP.PlanUID = SPT.PlanUID',                            'left');
            $this->ReadDb->where('U.UserUID', (int)$userUID);
            $this->ReadDb->order_by('OS.StartDate', 'DESC');
            $this->ReadDb->limit(1);
            $query = $this->ReadDb->get();
            $result->Error = FALSE;
            $result->Data  = ($query && $query->num_rows() > 0) ? $query->row() : null;
        } catch (Exception $e) {
            notifyError('Subscription_model::getUserSubscription', $e);
            $result->Error   = TRUE;
            $result->Message = $e->getMessage();
            $result->Data    = null;
        }
        return $result;
    }

    // ── Get OrgUID for a user (used when writing back to OrgSubscriptionTbl) ──
    public function getOrgUIDByUser(int $userUID): int {
        $this->ReadDb->db_debug = FALSE;
        $row = $this->ReadDb->select('OrgUID')
            ->from('Users.UserTbl')
            ->where('UserUID', (int)$userUID)
            ->limit(1)
            ->get()->row();
        return $row ? (int)$row->OrgUID : 0;
    }

    // ── User email info for notifications ─────────────────────────────────────
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
            notifyError('Subscription_model::getUserEmailInfo', $e);
            $result->Error   = TRUE;
            $result->Message = $e->getMessage();
            $result->Data    = null;
        }
        return $result;
    }

    // ── Check if an expiry-warning notification was already sent today ─────────
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
            notifyError('Subscription_model::isNotificationSentToday', $e);
            $result->Error      = TRUE;
            $result->Message    = $e->getMessage();
            $result->AlreadySent = false;
        }
        return $result;
    }

    // ── All subscription plans (Billing.SubscriptionPlansTbl) ───────────────────
    public function getSubscriptionPlans(bool $activeOnly = true): object {
        $result = new stdClass();
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select('*');
            $this->ReadDb->from('Billing.SubscriptionPlansTbl');
            if ($activeOnly) {
                $this->ReadDb->where('IsActive', 1);
            }
            $this->ReadDb->order_by('PlanUID', 'ASC');
            $query = $this->ReadDb->get();
            $result->Error = FALSE;
            $result->Data  = $query ? $query->result() : [];
        } catch (Exception $e) {
            notifyError('Subscription_model::getSubscriptionPlans', $e);
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
            notifyError('Subscription_model::getUserSubscriptionHistory', $e);
            return [];
        }
    }

    // ── SectorPlan by UID — joins Plan + Sector details ──────────────────────────
    public function getSectorPlanByUID(int $sectorPlanUID): object {
        $result = new stdClass();
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select('SPT.SectorPlanUID, SPT.SectorUID, SPT.PlanUID,
                SPT.Price, SPT.DurationDays, SPT.MaxUsers, SPT.MaxBranches,
                SP.PlanName, SP.PlanCode, SP.BillingCycle,
                S.SectorCode, S.SectorName');
            $this->ReadDb->from('Billing.SectorPlanTbl AS SPT');
            $this->ReadDb->join('Billing.SubscriptionPlansTbl AS SP', 'SP.PlanUID = SPT.PlanUID');
            $this->ReadDb->join('Billing.SectorsTbl AS S',            'S.SectorUID = SPT.SectorUID');
            $this->ReadDb->where('SPT.SectorPlanUID', (int)$sectorPlanUID);
            $this->ReadDb->where('SPT.IsActive', 1);
            $this->ReadDb->limit(1);
            $query = $this->ReadDb->get();
            $result->Error = FALSE;
            $result->Data  = ($query && $query->num_rows() > 0) ? $query->row() : null;
        } catch (Exception $e) {
            notifyError('Subscription_model::getSectorPlanByUID', $e);
            $result->Error   = TRUE;
            $result->Message = $e->getMessage();
            $result->Data    = null;
        }
        return $result;
    }
}
