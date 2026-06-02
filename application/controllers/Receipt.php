<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Receipt extends CI_Controller {

    public function __construct() {
        parent::__construct();
        // No JWT / auth middleware — this is a public page
    }

    public function index($token = '') {

        $token = trim($token);

        if (strlen($token) !== 10) {
            $this->_showError('Invalid receipt link.');
            return;
        }

        $this->load->database();
        $this->load->model('transactions_model');
        $this->load->model('organisation_model');

        // Fetch payment by token
        $this->db->select([
            'P.PaymentUID', 'P.OrgUID', 'P.TransUID', 'P.PartyType',
            'P.Amount', 'P.ExcessAmount', 'P.IsFullyPaid',
            'P.ReferenceNo', 'P.Notes', 'P.PaymentDate', 'P.CreatedOn',
            'P.UniqueNumber', 'P.PaymentNumber', 'P.ReceiptToken',
            'PT.Name AS PaymentTypeName', 'PT.IsCash',
            'T.UniqueNumber AS TransNumber', 'T.TransDate', 'T.NetAmount AS BillAmount',
            "CASE WHEN P.PartyType = 'C' THEN C.Name ELSE V.Name END AS PartyName",
            "CASE WHEN P.PartyType = 'C' THEN C.MobileNumber ELSE V.MobileNumber END AS PartyMobile",
            "CASE WHEN P.PartyType = 'C' THEN C.EmailAddress ELSE V.EmailAddress END AS PartyEmail",
            'BA.AccountName', 'BA.BankName', 'BA.AccountNumber', 'BA.IFSC', 'BA.BranchName',
            "CONCAT(CrUser.FirstName, ' ', CrUser.LastName) AS CreatedByName",
        ]);
        $this->db->from('Transaction.PaymentsTbl AS P');
        $this->db->join('Global.PaymentTypesTbl AS PT', 'PT.PaymentTypeUID = P.PaymentTypeUID', 'LEFT');
        $this->db->join('Transaction.TransactionsTbl AS T', 'T.TransUID = P.TransUID AND T.IsDeleted = 0', 'LEFT');
        $this->db->join('Customers.CustomerTbl AS C', "C.CustomerUID = P.PartyUID AND P.PartyType = 'C'", 'LEFT');
        $this->db->join('Vendors.VendorTbl AS V', "V.VendorUID = P.PartyUID AND P.PartyType = 'S'", 'LEFT');
        $this->db->join('Organisation.OrgBankAccountsTbl AS BA', 'BA.BankAccountUID = P.BankAccountUID', 'LEFT');
        $this->db->join('Users.UserTbl AS CrUser', 'CrUser.UserUID = P.CreatedBy', 'LEFT');
        $this->db->where(['P.ReceiptToken' => $token, 'P.IsDeleted' => 0]);
        $this->db->limit(1);
        $payment = $this->db->get()->row();

        if (!$payment) {
            $this->_showError('Receipt not found or link has expired.');
            return;
        }

        // Fetch org info
        $orgInfo = $this->organisation_model->getOrgInfoCached($payment->OrgUID);
        $org     = $orgInfo->Data ?? null;

        $this->load->view('receipt/payment', [
            'payment' => $payment,
            'org'     => $org,
        ]);
    }

    private function _showError($message) {
        $this->load->view('receipt/error', ['message' => $message]);
    }

}
