<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Razorpay_model — data queries for the public payment flow.
 * All reads use ReadDb; write uses the dbwrite_model via the controller.
 */
class Razorpay_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        $this->ReadDb = $this->load->database('ReadDB', TRUE);
        $this->ReadDb->db_debug = FALSE;
    }

    /**
     * Returns the party (customer) details for a transaction, plus the TransUID-level PartyUID.
     * Only works for customer transactions (PartyType = 'C').
     *
     * @param int $transUID
     * @returns ?object  {PartyUID, PartyName, MobileNumber, EmailAddress}
     */
    public function getInvoicePartyInfo(int $transUID): ?object {
        try {
            $this->ReadDb->select([
                'T.PartyUID',
                'COALESCE(C.Name, "") AS PartyName',
                'COALESCE(C.MobileNumber, "") AS MobileNumber',
                'COALESCE(C.EmailAddress, "") AS EmailAddress',
            ]);
            $this->ReadDb->from('Transaction.TransactionsTbl T');
            $this->ReadDb->join('Customers.CustomerTbl C', "C.CustomerUID = T.PartyUID AND T.PartyType = 'C'", 'left');
            $this->ReadDb->where(['T.TransUID' => $transUID, 'T.IsDeleted' => 0]);
            $this->ReadDb->limit(1);
            $q = $this->ReadDb->get();
            return ($q !== false) ? ($q->row() ?? null) : null;
        } catch (Exception $e) {
            notifyError($e, 'Razorpay_model::getInvoicePartyInfo');
            log_message('error', '[Razorpay_model::getInvoicePartyInfo] ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Checks whether a payment with the given Razorpay payment ID was already recorded.
     * Prevents duplicate recording if the customer's browser retries.
     *
     * @param string $razorpayPaymentId
     * @param int    $orgUID
     * @returns ?object  null if not found, otherwise the existing payment row
     */
    public function getPaymentByRazorpayRef(string $razorpayPaymentId, int $orgUID): ?object {
        try {
            $this->ReadDb->select('PaymentUID, ReceiptToken');
            $this->ReadDb->from('Transaction.PaymentsTbl');
            $this->ReadDb->where([
                'ReferenceNo' => $razorpayPaymentId,
                'OrgUID'      => $orgUID,
                'IsDeleted'   => 0,
            ]);
            $this->ReadDb->limit(1);
            $q = $this->ReadDb->get();
            return ($q !== false) ? ($q->row() ?? null) : null;
        } catch (Exception $e) {
            notifyError($e, 'Razorpay_model::getPaymentByRazorpayRef');
            return null;
        }
    }

    /**
     * Find the UID of a suitable "Online" / "UPI" / "Net Banking" payment type.
     * Falls back to the first available non-cash active type.
     *
     * @param int $orgUID  (unused currently — PaymentTypes are global, not org-scoped)
     * @returns int  0 if none found
     */
    public function getOnlinePaymentTypeUID(int $orgUID = 0): int {
        try {
            // Prefer explicit "online" names
            $this->ReadDb->select('PaymentTypeUID, Name');
            $this->ReadDb->from('Global.PaymentTypesTbl');
            $this->ReadDb->where(['IsDeleted' => 0, 'IsActive' => 1]);
            $this->ReadDb->order_by('PaymentTypeUID', 'ASC');
            $q = $this->ReadDb->get();
            if ($q === false) return 0;

            $rows        = $q->result();
            $onlineNames = ['online', 'upi', 'razorpay', 'net banking', 'netbanking', 'neft', 'imps'];
            $firstNonCash = 0;

            foreach ($rows as $row) {
                $lower = strtolower($row->Name ?? '');
                foreach ($onlineNames as $keyword) {
                    if (str_contains($lower, $keyword)) {
                        return (int)$row->PaymentTypeUID;
                    }
                }
                if ($firstNonCash === 0 && (!isset($row->IsCash) || !$row->IsCash)) {
                    $firstNonCash = (int)$row->PaymentTypeUID;
                }
            }

            // Fallback to any first non-cash type
            if ($firstNonCash > 0) return $firstNonCash;

            // Last resort: first available
            return isset($rows[0]) ? (int)$rows[0]->PaymentTypeUID : 0;

        } catch (Exception $e) {
            notifyError($e, 'Razorpay_model::getOnlinePaymentTypeUID');
            log_message('error', '[Razorpay_model::getOnlinePaymentTypeUID] ' . $e->getMessage());
            return 0;
        }
    }
}
