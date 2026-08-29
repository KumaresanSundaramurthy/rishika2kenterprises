<?php defined('BASEPATH') or exit('No direct script access allowed');

class Reports_model extends CI_Model
{
    private $ReadDb;

    public function __construct()
    {
        parent::__construct();
        $this->ReadDb = $this->load->database('ReadDB', TRUE);
    }

    /**
     * Returns the SQL expression to group rows by the requested period granularity.
     *
     * @param string $groupBy  'month' | 'quarter' | 'year'
     * @return string
     */
    private function _periodExpr(string $groupBy): string
    {
        switch ($groupBy) {
            case 'quarter': return "CONCAT(YEAR(TransDate),'-Q',QUARTER(TransDate))";
            case 'year':    return "CAST(YEAR(TransDate) AS CHAR)";
            default:        return "DATE_FORMAT(TransDate,'%Y-%m')";
        }
    }

    /**
     * Grouped sales data: Invoice (103) and Sales Return (106) rows per period.
     *
     * @param int    $orgUID
     * @param string $from     Y-m-d
     * @param string $to       Y-m-d
     * @param string $groupBy  'month'|'quarter'|'year'
     * @return array
     */
    public function getSalesSummaryData(int $orgUID, string $from, string $to, string $groupBy): array
    {
        $pe = $this->_periodExpr($groupBy);
        $q  = $this->ReadDb->query(
            "SELECT {$pe}                             AS Period,
                    ModuleUID,
                    COUNT(DISTINCT TransUID)           AS TxCount,
                    COALESCE(SUM(SubTotal),   0)       AS SubTotalAmt,
                    COALESCE(SUM(TaxAmount),  0)       AS TaxAmt,
                    COALESCE(SUM(NetAmount),  0)       AS NetAmt,
                    MIN(TransDate)                     AS FirstDate
             FROM Transaction.TransactionsTbl
             WHERE OrgUID      = ?
               AND ModuleUID   IN (103, 106)
               AND IsDeleted   = 0
               AND IsActive    = 1
               AND DocStatus   NOT IN ('Draft','Cancelled','Rejected')
               AND TransDate   BETWEEN ? AND ?
             GROUP BY {$pe}, ModuleUID
             ORDER BY MIN(TransDate), ModuleUID",
            [$orgUID, $from, $to]
        );
        return $q ? $q->result_array() : [];
    }

    /**
     * Grouped purchase data: Purchase (105) and Purchase Return (108) rows per period.
     *
     * @param int    $orgUID
     * @param string $from     Y-m-d
     * @param string $to       Y-m-d
     * @param string $groupBy  'month'|'quarter'|'year'
     * @return array
     */
    public function getPurchaseSummaryData(int $orgUID, string $from, string $to, string $groupBy): array
    {
        $pe = $this->_periodExpr($groupBy);
        $q  = $this->ReadDb->query(
            "SELECT {$pe}                             AS Period,
                    ModuleUID,
                    COUNT(DISTINCT TransUID)           AS TxCount,
                    COALESCE(SUM(SubTotal),   0)       AS SubTotalAmt,
                    COALESCE(SUM(TaxAmount),  0)       AS TaxAmt,
                    COALESCE(SUM(NetAmount),  0)       AS NetAmt,
                    MIN(TransDate)                     AS FirstDate
             FROM Transaction.TransactionsTbl
             WHERE OrgUID      = ?
               AND ModuleUID   IN (105, 108)
               AND IsDeleted   = 0
               AND IsActive    = 1
               AND DocStatus   NOT IN ('Draft','Cancelled','Rejected')
               AND TransDate   BETWEEN ? AND ?
             GROUP BY {$pe}, ModuleUID
             ORDER BY MIN(TransDate), ModuleUID",
            [$orgUID, $from, $to]
        );
        return $q ? $q->result_array() : [];
    }

    /**
     * All payment receipts from customers for the given date range.
     * Ordered newest-first for the detail list view.
     *
     * @param int    $orgUID
     * @param string $from    Y-m-d
     * @param string $to      Y-m-d
     * @return array
     */
    public function getPaymentReceivedData(int $orgUID, string $from, string $to): array
    {
        $q = $this->ReadDb->query(
            "SELECT P.PaymentUID,
                    P.UniqueNumber,
                    P.PaymentDate,
                    P.Amount,
                    P.ReferenceNo,
                    P.IsOnAccount,
                    P.PartyType,
                    COALESCE(Cust.Name,         '—')      AS PartyName,
                    COALESCE(PT.Name,           'Cash')   AS PaymentMode,
                    COALESCE(PT.IsCash,         1)        AS IsCash,
                    COALESCE(BA.AccountName,    'Cash')   AS AccountName
             FROM Transaction.PaymentsTbl            P
             LEFT JOIN Customers.CustomerTbl              Cust ON Cust.CustomerUID    = P.PartyUID AND P.PartyType = 'C'
             LEFT JOIN Global.PaymentTypesTbl             PT   ON PT.PaymentTypeUID   = P.PaymentTypeUID
             LEFT JOIN Organisation.OrgBankAccountsTbl    BA   ON BA.BankAccountUID   = P.BankAccountUID
             WHERE P.OrgUID     = ?
               AND P.PartyType  = 'C'
               AND P.IsDeleted  = 0
               AND P.IsActive   = 1
               AND P.IsCancelled = 0
               AND P.PaymentDate BETWEEN ? AND ?
             ORDER BY P.PaymentDate DESC, P.PaymentUID DESC
             LIMIT 1000",
            [$orgUID, $from, $to]
        );
        return $q ? $q->result_array() : [];
    }

    /**
     * All payments made to vendors for the given date range.
     * Ordered newest-first for the detail list view.
     *
     * @param int    $orgUID
     * @param string $from    Y-m-d
     * @param string $to      Y-m-d
     * @return array
     */
    public function getPaymentMadeData(int $orgUID, string $from, string $to): array
    {
        $q = $this->ReadDb->query(
            "SELECT P.PaymentUID,
                    P.UniqueNumber,
                    P.PaymentDate,
                    P.Amount,
                    P.ReferenceNo,
                    P.IsOnAccount,
                    P.PartyType,
                    COALESCE(Vend.Name,         '—')      AS PartyName,
                    COALESCE(PT.Name,           'Cash')   AS PaymentMode,
                    COALESCE(PT.IsCash,         1)        AS IsCash,
                    COALESCE(BA.AccountName,    'Cash')   AS AccountName
             FROM Transaction.PaymentsTbl            P
             LEFT JOIN Vendors.VendorTbl                  Vend ON Vend.VendorUID      = P.PartyUID AND P.PartyType = 'S'
             LEFT JOIN Global.PaymentTypesTbl             PT   ON PT.PaymentTypeUID   = P.PaymentTypeUID
             LEFT JOIN Organisation.OrgBankAccountsTbl    BA   ON BA.BankAccountUID   = P.BankAccountUID
             WHERE P.OrgUID     = ?
               AND P.PartyType  = 'S'
               AND P.IsDeleted  = 0
               AND P.IsActive   = 1
               AND P.IsCancelled = 0
               AND P.PaymentDate BETWEEN ? AND ?
             ORDER BY P.PaymentDate DESC, P.PaymentUID DESC
             LIMIT 1000",
            [$orgUID, $from, $to]
        );
        return $q ? $q->result_array() : [];
    }

    /**
     * Bank/cash statement: all payments for an account (or all accounts) in date range.
     * Ordered ASC so the JS running-balance calculation is chronological.
     *
     * @param int    $orgUID
     * @param string $from         Y-m-d
     * @param string $to           Y-m-d
     * @param int    $accountUID   0 = all accounts
     * @return array
     */
    public function getBankStatementData(int $orgUID, string $from, string $to, int $accountUID): array
    {
        $accountFilter = $accountUID > 0 ? ' AND P.BankAccountUID = ?' : '';
        $params        = $accountUID > 0
            ? [$orgUID, $from, $to, $accountUID]
            : [$orgUID, $from, $to];

        $q = $this->ReadDb->query(
            "SELECT P.PaymentUID,
                    P.UniqueNumber,
                    P.PaymentDate,
                    P.Amount,
                    P.PartyType,
                    P.ReferenceNo,
                    COALESCE(Cust.Name, Vend.Name, '—')               AS PartyName,
                    COALESCE(PT.Name,  'Cash')                        AS PaymentMode,
                    COALESCE(PT.IsCash, 1)                            AS IsCash,
                    COALESCE(BA.AccountName, 'Cash')                  AS AccountName,
                    COALESCE(BA.BankName,    '')                      AS BankName
             FROM Transaction.PaymentsTbl            P
             LEFT JOIN Customers.CustomerTbl              Cust ON Cust.CustomerUID    = P.PartyUID AND P.PartyType = 'C'
             LEFT JOIN Vendors.VendorTbl                  Vend ON Vend.VendorUID      = P.PartyUID AND P.PartyType = 'S'
             LEFT JOIN Global.PaymentTypesTbl             PT   ON PT.PaymentTypeUID   = P.PaymentTypeUID
             LEFT JOIN Organisation.OrgBankAccountsTbl    BA   ON BA.BankAccountUID   = P.BankAccountUID
             WHERE P.OrgUID    = ?
               AND P.IsDeleted = 0
               AND P.IsActive  = 1
               AND P.IsCancelled = 0
               AND P.PaymentDate BETWEEN ? AND ?
               {$accountFilter}
             ORDER BY P.PaymentDate ASC, P.PaymentUID ASC
             LIMIT 2000",
            $params
        );
        return $q ? $q->result_array() : [];
    }

    /**
     * List of active bank/cash accounts for the organisation.
     *
     * @param int $orgUID
     * @return array
     */
    public function getBankAccounts(int $orgUID): array
    {
        $q = $this->ReadDb->query(
            "SELECT BankAccountUID, AccountName, BankName, IsCash
             FROM Organisation.OrgBankAccountsTbl
             WHERE OrgUID    = ?
               AND IsDeleted = 0
               AND IsActive  = 1
             ORDER BY IsCash DESC, AccountName ASC",
            [$orgUID]
        );
        return $q ? $q->result_array() : [];
    }

    /**
     * Monthly pivot data for the combined summary.
     * Returns Invoice (103), Sales Return (106), Purchase (105), Purchase Return (108),
     * always grouped by calendar month — the JS builds the 12-row ledger from this.
     *
     * @param int    $orgUID
     * @param string $from   Y-m-d  (first day of chosen year)
     * @param string $to     Y-m-d  (last day of chosen year)
     * @return array
     */
    public function getMonthlySummaryData(int $orgUID, string $from, string $to): array
    {
        $q = $this->ReadDb->query(
            "SELECT DATE_FORMAT(TransDate,'%Y-%m')    AS Period,
                    MONTH(TransDate)                  AS Mo,
                    ModuleUID,
                    COUNT(DISTINCT TransUID)           AS TxCount,
                    COALESCE(SUM(NetAmount), 0)        AS NetAmt
             FROM Transaction.TransactionsTbl
             WHERE OrgUID      = ?
               AND ModuleUID   IN (103, 105, 106, 108)
               AND IsDeleted   = 0
               AND IsActive    = 1
               AND DocStatus   NOT IN ('Draft','Cancelled','Rejected')
               AND TransDate   BETWEEN ? AND ?
             GROUP BY DATE_FORMAT(TransDate,'%Y-%m'), MONTH(TransDate), ModuleUID
             ORDER BY Period, ModuleUID",
            [$orgUID, $from, $to]
        );
        return $q ? $q->result_array() : [];
    }

    // ── P&L / Balance Sheet / Trial Balance ───────────────────────────────────

    /**
     * P&L Statement: Income and Expense ledger movements in a date range.
     * Returns one row per active Income/Expense ledger (including zero-activity ones).
     *
     * @param int    $orgUID
     * @param string $from   Y-m-d
     * @param string $to     Y-m-d
     * @return array
     */
    public function getPLStatementData(int $orgUID, string $from, string $to): array
    {
        $q = $this->ReadDb->query(
            "SELECT ca.LedgerUID, ca.LedgerCode, ca.LedgerName, ca.LedgerType,
                    COALESCE(SUM(CASE WHEN je.TransactionType='Debit'  THEN je.Amount ELSE 0 END),0) AS PeriodDebit,
                    COALESCE(SUM(CASE WHEN je.TransactionType='Credit' THEN je.Amount ELSE 0 END),0) AS PeriodCredit
             FROM Accounting.ChartOfAccounts ca
             LEFT JOIN Accounting.JournalEntries je ON je.LedgerUID  = ca.LedgerUID  AND je.IsDeleted = 0
             LEFT JOIN Accounting.GeneralJournal gj ON gj.JournalUID = je.JournalUID AND gj.IsDeleted = 0
                                                    AND gj.JournalDate BETWEEN ? AND ?
             WHERE ca.OrgUID    = ?
               AND ca.IsDeleted = 0
               AND ca.LedgerType IN ('Income','Expense')
             GROUP BY ca.LedgerUID
             ORDER BY ca.LedgerType, ca.LedgerName",
            [$from, $to, $orgUID]
        );
        return $q ? $q->result_array() : [];
    }

    /**
     * Balance Sheet: all ledger balances (opening + cumulative movements) up to asOf date.
     *
     * @param int    $orgUID
     * @param string $asOf   Y-m-d
     * @return array
     */
    public function getBalanceSheetData(int $orgUID, string $asOf): array
    {
        $q = $this->ReadDb->query(
            "SELECT ca.LedgerUID, ca.LedgerCode, ca.LedgerName, ca.LedgerType,
                    ca.OpeningBalance, ca.OpeningBalanceType,
                    COALESCE(SUM(CASE WHEN je.TransactionType='Debit'  THEN je.Amount ELSE 0 END),0) AS TotalDebit,
                    COALESCE(SUM(CASE WHEN je.TransactionType='Credit' THEN je.Amount ELSE 0 END),0) AS TotalCredit
             FROM Accounting.ChartOfAccounts ca
             LEFT JOIN Accounting.JournalEntries je ON je.LedgerUID  = ca.LedgerUID  AND je.IsDeleted = 0
             LEFT JOIN Accounting.GeneralJournal gj ON gj.JournalUID = je.JournalUID AND gj.IsDeleted = 0
                                                    AND gj.JournalDate <= ?
             WHERE ca.OrgUID    = ?
               AND ca.IsDeleted = 0
               AND ca.LedgerType IN ('Asset','Liability','Income','Expense','Bank','Cash','Customer','Vendor')
             GROUP BY ca.LedgerUID
             ORDER BY ca.LedgerType, ca.LedgerName",
            [$asOf, $orgUID]
        );
        return $q ? $q->result_array() : [];
    }

    // ── Party Reports ─────────────────────────────────────────────────────────

    /**
     * All customers with outstanding invoice balance (Issued/Partial invoices with BalanceAmount > 0).
     *
     * @param int $orgUID
     * @return array
     */
    public function getCustomerOutstandingData(int $orgUID): array
    {
        $q = $this->ReadDb->query(
            "SELECT
                Cust.CustomerUID,
                Cust.Name          AS CustomerName,
                Cust.MobileNumber,
                Cust.Area,
                COUNT(DISTINCT Ts.TransUID)                                 AS InvoiceCount,
                COALESCE(SUM(Ts.NetAmount), 0)                              AS TotalBilled,
                COALESCE(SUM(COALESCE(Ts.PaidAmount, 0)), 0)               AS TotalPaid,
                COALESCE(SUM(COALESCE(Ts.BalanceAmount, Ts.NetAmount)), 0) AS TotalOutstanding
             FROM Customers.CustomerTbl Cust
             JOIN Transaction.TransactionsTbl Ts
                  ON  Ts.PartyUID  = Cust.CustomerUID
                  AND Ts.PartyType = 'C'
                  AND Ts.ModuleUID = 103
                  AND Ts.IsDeleted = 0
                  AND Ts.IsActive  = 1
                  AND Ts.DocStatus NOT IN ('Draft','Cancelled','Rejected','Paid')
                  AND COALESCE(Ts.BalanceAmount, Ts.NetAmount) > 0.01
             WHERE Cust.OrgUID    = ?
               AND Cust.IsDeleted = 0
               AND Cust.IsActive  = 1
             GROUP BY Cust.CustomerUID
             HAVING TotalOutstanding > 0.01
             ORDER BY TotalOutstanding DESC
             LIMIT 500",
            [$orgUID]
        );
        return $q ? $q->result_array() : [];
    }

    /**
     * All vendors with outstanding purchase bill balance.
     *
     * @param int $orgUID
     * @return array
     */
    public function getSupplierOutstandingData(int $orgUID): array
    {
        $q = $this->ReadDb->query(
            "SELECT
                Vend.VendorUID,
                Vend.Name          AS VendorName,
                Vend.MobileNumber,
                Vend.Area,
                COUNT(DISTINCT Ts.TransUID)                                 AS BillCount,
                COALESCE(SUM(Ts.NetAmount), 0)                              AS TotalBilled,
                COALESCE(SUM(COALESCE(Ts.PaidAmount, 0)), 0)               AS TotalPaid,
                COALESCE(SUM(COALESCE(Ts.BalanceAmount, Ts.NetAmount)), 0) AS TotalOutstanding
             FROM Vendors.VendorTbl Vend
             JOIN Transaction.TransactionsTbl Ts
                  ON  Ts.PartyUID  = Vend.VendorUID
                  AND Ts.PartyType = 'S'
                  AND Ts.ModuleUID = 105
                  AND Ts.IsDeleted = 0
                  AND Ts.IsActive  = 1
                  AND Ts.DocStatus NOT IN ('Draft','Cancelled','Rejected','Paid')
                  AND COALESCE(Ts.BalanceAmount, Ts.NetAmount) > 0.01
             WHERE Vend.OrgUID    = ?
               AND Vend.IsDeleted = 0
               AND Vend.IsActive  = 1
             GROUP BY Vend.VendorUID
             HAVING TotalOutstanding > 0.01
             ORDER BY TotalOutstanding DESC
             LIMIT 500",
            [$orgUID]
        );
        return $q ? $q->result_array() : [];
    }

    /**
     * Customer ageing: outstanding invoices grouped into age buckets based on CURDATE().
     * Age = days since invoice TransDate.
     *
     * @param int $orgUID
     * @return array
     */
    public function getCustomerAgeingData(int $orgUID): array
    {
        $q = $this->ReadDb->query(
            "SELECT
                Cust.CustomerUID,
                Cust.Name          AS CustomerName,
                Cust.MobileNumber,
                Cust.Area,
                COUNT(DISTINCT Ts.TransUID)                                                                                    AS InvoiceCount,
                COALESCE(SUM(COALESCE(Ts.BalanceAmount, Ts.NetAmount)), 0)                                                     AS TotalOutstanding,
                COALESCE(SUM(CASE WHEN DATEDIFF(CURDATE(), Ts.TransDate) BETWEEN 0 AND 30
                                   THEN COALESCE(Ts.BalanceAmount, Ts.NetAmount) ELSE 0 END), 0)                               AS Bucket0_30,
                COALESCE(SUM(CASE WHEN DATEDIFF(CURDATE(), Ts.TransDate) BETWEEN 31 AND 60
                                   THEN COALESCE(Ts.BalanceAmount, Ts.NetAmount) ELSE 0 END), 0)                               AS Bucket31_60,
                COALESCE(SUM(CASE WHEN DATEDIFF(CURDATE(), Ts.TransDate) BETWEEN 61 AND 90
                                   THEN COALESCE(Ts.BalanceAmount, Ts.NetAmount) ELSE 0 END), 0)                               AS Bucket61_90,
                COALESCE(SUM(CASE WHEN DATEDIFF(CURDATE(), Ts.TransDate) BETWEEN 91 AND 120
                                   THEN COALESCE(Ts.BalanceAmount, Ts.NetAmount) ELSE 0 END), 0)                               AS Bucket91_120,
                COALESCE(SUM(CASE WHEN DATEDIFF(CURDATE(), Ts.TransDate) > 120
                                   THEN COALESCE(Ts.BalanceAmount, Ts.NetAmount) ELSE 0 END), 0)                               AS Bucket120Plus
             FROM Customers.CustomerTbl Cust
             JOIN Transaction.TransactionsTbl Ts
                  ON  Ts.PartyUID  = Cust.CustomerUID
                  AND Ts.PartyType = 'C'
                  AND Ts.ModuleUID = 103
                  AND Ts.IsDeleted = 0
                  AND Ts.IsActive  = 1
                  AND Ts.DocStatus NOT IN ('Draft','Cancelled','Rejected','Paid')
                  AND COALESCE(Ts.BalanceAmount, Ts.NetAmount) > 0.01
             WHERE Cust.OrgUID    = ?
               AND Cust.IsDeleted = 0
               AND Cust.IsActive  = 1
             GROUP BY Cust.CustomerUID
             HAVING TotalOutstanding > 0.01
             ORDER BY TotalOutstanding DESC
             LIMIT 500",
            [$orgUID]
        );
        return $q ? $q->result_array() : [];
    }

    /**
     * Opening balance for a customer ledger: net of invoices, returns, and payments before the from date.
     * Returns ['debit' => float, 'credit' => float].
     *
     * @param int    $orgUID
     * @param int    $customerUID
     * @param string $before   Y-m-d (exclusive)
     * @return array
     */
    public function getCustomerLedgerOpening(int $orgUID, int $customerUID, string $before): array
    {
        $q1 = $this->ReadDb->query(
            "SELECT COALESCE(SUM(NetAmount), 0) AS total FROM Transaction.TransactionsTbl
             WHERE OrgUID=? AND PartyUID=? AND PartyType='C' AND ModuleUID=103
               AND IsDeleted=0 AND IsActive=1
               AND DocStatus NOT IN ('Draft','Cancelled','Rejected')
               AND TransDate < ?",
            [$orgUID, $customerUID, $before]
        );
        $invBefore = (float)($q1 ? $q1->row()->total : 0);

        $q2 = $this->ReadDb->query(
            "SELECT COALESCE(SUM(NetAmount), 0) AS total FROM Transaction.TransactionsTbl
             WHERE OrgUID=? AND PartyUID=? AND PartyType='C' AND ModuleUID=106
               AND IsDeleted=0 AND IsActive=1
               AND DocStatus NOT IN ('Draft','Cancelled','Rejected')
               AND TransDate < ?",
            [$orgUID, $customerUID, $before]
        );
        $retBefore = (float)($q2 ? $q2->row()->total : 0);

        $q3 = $this->ReadDb->query(
            "SELECT COALESCE(SUM(Amount), 0) AS total FROM Transaction.PaymentsTbl
             WHERE OrgUID=? AND PartyUID=? AND PartyType='C'
               AND IsDeleted=0 AND IsActive=1 AND IsCancelled=0 AND PaymentDate < ?",
            [$orgUID, $customerUID, $before]
        );
        $payBefore = (float)($q3 ? $q3->row()->total : 0);

        $net = $invBefore - $retBefore - $payBefore;
        return [
            'debit'  => $net > 0 ? round($net, 4) : 0.0,
            'credit' => $net < 0 ? round(abs($net), 4) : 0.0,
        ];
    }

    /**
     * Customer ledger: all invoices, sales returns, and payments in date range.
     * Ordered ascending for running balance computation.
     *
     * @param int    $orgUID
     * @param int    $customerUID
     * @param string $from   Y-m-d
     * @param string $to     Y-m-d
     * @return array
     */
    public function getCustomerLedgerData(int $orgUID, int $customerUID, string $from, string $to): array
    {
        $q = $this->ReadDb->query(
            "(SELECT Ts.TransDate AS TxDate, 'Invoice' AS TxType, Ts.UniqueNumber AS RefNo,
                     Ts.NetAmount AS Debit, 0.00 AS Credit
              FROM Transaction.TransactionsTbl Ts
              WHERE Ts.OrgUID=? AND Ts.PartyUID=? AND Ts.PartyType='C' AND Ts.ModuleUID=103
                AND Ts.IsDeleted=0 AND Ts.IsActive=1
                AND Ts.DocStatus NOT IN ('Draft','Cancelled','Rejected')
                AND Ts.TransDate BETWEEN ? AND ?)
             UNION ALL
             (SELECT Ts.TransDate AS TxDate, 'Sales Return' AS TxType, Ts.UniqueNumber AS RefNo,
                     0.00 AS Debit, Ts.NetAmount AS Credit
              FROM Transaction.TransactionsTbl Ts
              WHERE Ts.OrgUID=? AND Ts.PartyUID=? AND Ts.PartyType='C' AND Ts.ModuleUID=106
                AND Ts.IsDeleted=0 AND Ts.IsActive=1
                AND Ts.DocStatus NOT IN ('Draft','Cancelled','Rejected')
                AND Ts.TransDate BETWEEN ? AND ?)
             UNION ALL
             (SELECT P.PaymentDate AS TxDate, 'Payment Received' AS TxType, P.UniqueNumber AS RefNo,
                     0.00 AS Debit, P.Amount AS Credit
              FROM Transaction.PaymentsTbl P
              WHERE P.OrgUID=? AND P.PartyUID=? AND P.PartyType='C'
                AND P.IsDeleted=0 AND P.IsActive=1 AND P.IsCancelled=0
                AND P.PaymentDate BETWEEN ? AND ?)
             ORDER BY TxDate ASC, TxType ASC
             LIMIT 1000",
            [$orgUID, $customerUID, $from, $to,
             $orgUID, $customerUID, $from, $to,
             $orgUID, $customerUID, $from, $to]
        );
        return $q ? $q->result_array() : [];
    }

    /**
     * Opening balance for a supplier ledger: net of purchase bills, purchase returns, and payments before the from date.
     * Returns ['debit' => float, 'credit' => float].
     * Credit > Debit means we still owe the vendor; Debit > Credit means vendor owes us.
     *
     * @param int    $orgUID
     * @param int    $vendorUID
     * @param string $before   Y-m-d (exclusive)
     * @return array
     */
    public function getSupplierLedgerOpening(int $orgUID, int $vendorUID, string $before): array
    {
        $q1 = $this->ReadDb->query(
            "SELECT COALESCE(SUM(NetAmount), 0) AS total FROM Transaction.TransactionsTbl
             WHERE OrgUID=? AND PartyUID=? AND PartyType='S' AND ModuleUID=105
               AND IsDeleted=0 AND IsActive=1
               AND DocStatus NOT IN ('Draft','Cancelled','Rejected')
               AND TransDate < ?",
            [$orgUID, $vendorUID, $before]
        );
        $billBefore = (float)($q1 ? $q1->row()->total : 0);

        $q2 = $this->ReadDb->query(
            "SELECT COALESCE(SUM(NetAmount), 0) AS total FROM Transaction.TransactionsTbl
             WHERE OrgUID=? AND PartyUID=? AND PartyType='S' AND ModuleUID=108
               AND IsDeleted=0 AND IsActive=1
               AND DocStatus NOT IN ('Draft','Cancelled','Rejected')
               AND TransDate < ?",
            [$orgUID, $vendorUID, $before]
        );
        $retBefore = (float)($q2 ? $q2->row()->total : 0);

        $q3 = $this->ReadDb->query(
            "SELECT COALESCE(SUM(Amount), 0) AS total FROM Transaction.PaymentsTbl
             WHERE OrgUID=? AND PartyUID=? AND PartyType='S'
               AND IsDeleted=0 AND IsActive=1 AND IsCancelled=0 AND PaymentDate < ?",
            [$orgUID, $vendorUID, $before]
        );
        $payBefore = (float)($q3 ? $q3->row()->total : 0);

        // Credit (Bills) − Debit (Returns + Payments) = net payable to vendor
        $net = $billBefore - $retBefore - $payBefore;
        return [
            'debit'  => $net < 0 ? round(abs($net), 4) : 0.0,  // vendor owes us
            'credit' => $net > 0 ? round($net, 4) : 0.0,        // we owe vendor
        ];
    }

    /**
     * Supplier ledger: all purchase bills, purchase returns, and payments in date range.
     * Ordered ascending for running balance computation.
     *
     * @param int    $orgUID
     * @param int    $vendorUID
     * @param string $from   Y-m-d
     * @param string $to     Y-m-d
     * @return array
     */
    public function getSupplierLedgerData(int $orgUID, int $vendorUID, string $from, string $to): array
    {
        $q = $this->ReadDb->query(
            "(SELECT Ts.TransDate AS TxDate, 'Purchase Bill' AS TxType, Ts.UniqueNumber AS RefNo,
                     0.00 AS Debit, Ts.NetAmount AS Credit
              FROM Transaction.TransactionsTbl Ts
              WHERE Ts.OrgUID=? AND Ts.PartyUID=? AND Ts.PartyType='S' AND Ts.ModuleUID=105
                AND Ts.IsDeleted=0 AND Ts.IsActive=1
                AND Ts.DocStatus NOT IN ('Draft','Cancelled','Rejected')
                AND Ts.TransDate BETWEEN ? AND ?)
             UNION ALL
             (SELECT Ts.TransDate AS TxDate, 'Purchase Return' AS TxType, Ts.UniqueNumber AS RefNo,
                     Ts.NetAmount AS Debit, 0.00 AS Credit
              FROM Transaction.TransactionsTbl Ts
              WHERE Ts.OrgUID=? AND Ts.PartyUID=? AND Ts.PartyType='S' AND Ts.ModuleUID=108
                AND Ts.IsDeleted=0 AND Ts.IsActive=1
                AND Ts.DocStatus NOT IN ('Draft','Cancelled','Rejected')
                AND Ts.TransDate BETWEEN ? AND ?)
             UNION ALL
             (SELECT P.PaymentDate AS TxDate, 'Payment Made' AS TxType, P.UniqueNumber AS RefNo,
                     P.Amount AS Debit, 0.00 AS Credit
              FROM Transaction.PaymentsTbl P
              WHERE P.OrgUID=? AND P.PartyUID=? AND P.PartyType='S'
                AND P.IsDeleted=0 AND P.IsActive=1 AND P.IsCancelled=0
                AND P.PaymentDate BETWEEN ? AND ?)
             ORDER BY TxDate ASC, TxType ASC
             LIMIT 1000",
            [$orgUID, $vendorUID, $from, $to,
             $orgUID, $vendorUID, $from, $to,
             $orgUID, $vendorUID, $from, $to]
        );
        return $q ? $q->result_array() : [];
    }

    // ── Item Wise Sales ───────────────────────────────────────────────────────

    /**
     * Item-wise sales totals for a date range (Invoice ModuleUID=103).
     *
     * @param int    $orgUID
     * @param string $from   Y-m-d
     * @param string $to     Y-m-d
     * @return array
     */
    public function getItemWiseSalesData(int $orgUID, string $from, string $to): array
    {
        $q = $this->ReadDb->query(
            "SELECT Prod.ProductUID,
                    Prod.ItemName,
                    Prod.SKU,
                    Cat.Name                              AS CategoryName,
                    Unit.ShortName                        AS UnitName,
                    SUM(Tprod.Quantity)                   AS TotalQty,
                    SUM(Tprod.Quantity * Tprod.UnitPrice) AS TotalValue,
                    AVG(Tprod.UnitPrice)                  AS AvgPrice,
                    COUNT(DISTINCT Ts.TransUID)           AS InvoiceCount
             FROM Transaction.TransProductsTbl AS Tprod
             JOIN Transaction.TransactionsTbl  AS Ts
                  ON  Ts.TransUID  = Tprod.TransUID
                  AND Ts.OrgUID    = Tprod.OrgUID
                  AND Ts.ModuleUID = 103
                  AND Ts.IsDeleted = 0
                  AND Ts.IsActive  = 1
                  AND Ts.DocStatus NOT IN ('Draft','Cancelled','Rejected')
                  AND Ts.TransDate BETWEEN ? AND ?
             JOIN Products.ProductTbl AS Prod
                  ON Prod.ProductUID = Tprod.ProductUID
             LEFT JOIN Products.CategoryTbl AS Cat
                  ON Cat.CategoryUID = Prod.CategoryUID
             LEFT JOIN Global.PrimaryUnitTbl AS Unit
                  ON Unit.PrimaryUnitUID = Prod.PrimaryUnitUID
             WHERE Tprod.OrgUID    = ?
               AND Tprod.IsDeleted = 0
               AND Tprod.IsActive  = 1
             GROUP BY Prod.ProductUID
             ORDER BY TotalValue DESC
             LIMIT 1000",
            [$from, $to, $orgUID]
        );
        return $q ? $q->result_array() : [];
    }

    // ── Item Wise Purchase ────────────────────────────────────────────────────

    /**
     * Item-wise purchase totals for a date range (Purchase Bill ModuleUID=105).
     *
     * @param int    $orgUID
     * @param string $from   Y-m-d
     * @param string $to     Y-m-d
     * @return array
     */
    public function getItemWisePurchaseData(int $orgUID, string $from, string $to): array
    {
        $q = $this->ReadDb->query(
            "SELECT Prod.ProductUID,
                    Prod.ItemName,
                    Prod.SKU,
                    Cat.Name                              AS CategoryName,
                    Unit.ShortName                        AS UnitName,
                    SUM(Tprod.Quantity)                   AS TotalQty,
                    SUM(Tprod.Quantity * Tprod.UnitPrice) AS TotalCost,
                    AVG(Tprod.UnitPrice)                  AS AvgCost,
                    COUNT(DISTINCT Ts.TransUID)           AS BillCount
             FROM Transaction.TransProductsTbl AS Tprod
             JOIN Transaction.TransactionsTbl  AS Ts
                  ON  Ts.TransUID  = Tprod.TransUID
                  AND Ts.OrgUID    = Tprod.OrgUID
                  AND Ts.ModuleUID = 105
                  AND Ts.IsDeleted = 0
                  AND Ts.IsActive  = 1
                  AND Ts.DocStatus NOT IN ('Draft','Cancelled','Rejected')
                  AND Ts.TransDate BETWEEN ? AND ?
             JOIN Products.ProductTbl AS Prod
                  ON Prod.ProductUID = Tprod.ProductUID
             LEFT JOIN Products.CategoryTbl AS Cat
                  ON Cat.CategoryUID = Prod.CategoryUID
             LEFT JOIN Global.PrimaryUnitTbl AS Unit
                  ON Unit.PrimaryUnitUID = Prod.PrimaryUnitUID
             WHERE Tprod.OrgUID    = ?
               AND Tprod.IsDeleted = 0
               AND Tprod.IsActive  = 1
             GROUP BY Prod.ProductUID
             ORDER BY TotalCost DESC
             LIMIT 1000",
            [$from, $to, $orgUID]
        );
        return $q ? $q->result_array() : [];
    }

    // ── Low Stock Alert ───────────────────────────────────────────────────────

    /**
     * Products at or below their low-stock threshold.
     *
     * @param int $orgUID
     * @return array
     */
    public function getLowStockAlertData(int $orgUID): array
    {
        $q = $this->ReadDb->query(
            "SELECT Prod.ProductUID,
                    Prod.ItemName,
                    Prod.SKU,
                    Cat.Name                            AS CategoryName,
                    Unit.ShortName                      AS UnitName,
                    COALESCE(PS.AvailableQty, 0)        AS AvailableQty,
                    Prod.LowStockAlertAt,
                    Prod.PurchasePrice,
                    (Prod.LowStockAlertAt - COALESCE(PS.AvailableQty, 0)) AS ShortfallQty
             FROM Products.ProductTbl AS Prod
             LEFT JOIN Products.ProductStockTbl AS PS
                  ON PS.ProductUID = Prod.ProductUID
             LEFT JOIN Products.CategoryTbl AS Cat
                  ON Cat.CategoryUID = Prod.CategoryUID
             LEFT JOIN Global.PrimaryUnitTbl AS Unit
                  ON Unit.PrimaryUnitUID = Prod.PrimaryUnitUID
             WHERE Prod.OrgUID        = ?
               AND Prod.IsDeleted     = 0
               AND Prod.IsActive      = 1
               AND Prod.ProductType   = 'Product'
               AND Prod.IsComposite   = 0
               AND Prod.LowStockAlertAt > 0
               AND COALESCE(PS.AvailableQty, 0) <= Prod.LowStockAlertAt
             ORDER BY ShortfallQty DESC
             LIMIT 500",
            [$orgUID]
        );
        return $q ? $q->result_array() : [];
    }

    // ── Stock Summary ─────────────────────────────────────────────────────────

    /**
     * Current stock snapshot for all physical products.
     *
     * @param int $orgUID
     * @return array
     */
    public function getStockSummaryData(int $orgUID): array
    {
        $q = $this->ReadDb->query(
            "SELECT Prod.ProductUID,
                    Prod.ItemName,
                    Prod.SKU,
                    Cat.Name                                           AS CategoryName,
                    Unit.ShortName                                     AS UnitName,
                    Prod.PurchasePrice,
                    Prod.SellingPrice,
                    Prod.LowStockAlertAt,
                    COALESCE(PS.AvailableQty, 0)                       AS AvailableQty,
                    COALESCE(PS.AvailableQty, 0) * Prod.PurchasePrice  AS StockValue
             FROM Products.ProductTbl AS Prod
             LEFT JOIN Products.ProductStockTbl AS PS
                  ON PS.ProductUID = Prod.ProductUID
             LEFT JOIN Products.CategoryTbl AS Cat
                  ON Cat.CategoryUID = Prod.CategoryUID
             LEFT JOIN Global.PrimaryUnitTbl AS Unit
                  ON Unit.PrimaryUnitUID = Prod.PrimaryUnitUID
             WHERE Prod.OrgUID      = ?
               AND Prod.IsDeleted   = 0
               AND Prod.IsActive    = 1
               AND Prod.ProductType = 'Product'
               AND Prod.IsComposite = 0
             ORDER BY Prod.ItemName ASC
             LIMIT 2000",
            [$orgUID]
        );
        return $q ? $q->result_array() : [];
    }

    // ── Bill-wise Item Reports (shared private helper) ────────────────────────

    /**
     * Shared query for bill-wise line-item reports.
     * Joins TransProductsTbl × TransactionsTbl and the appropriate party table.
     *
     * @param int    $orgUID
     * @param int    $moduleUID  103=Invoice, 105=Purchase, 106=SalesReturn, 108=PurchaseReturn
     * @param string $partyType  'C' or 'S'
     * @param string $from       Y-m-d
     * @param string $to         Y-m-d
     * @return array
     */
    private function _getBillwiseItemData(int $orgUID, int $moduleUID, string $partyType, string $from, string $to): array
    {
        $partyJoin = ($partyType === 'C')
            ? "LEFT JOIN Customers.CustomerTbl AS Party ON Party.CustomerUID = Ts.PartyUID AND Ts.PartyType = 'C'"
            : "LEFT JOIN Vendors.VendorTbl     AS Party ON Party.VendorUID   = Ts.PartyUID AND Ts.PartyType = 'S'";

        $q = $this->ReadDb->query(
            "SELECT
                 Ts.UniqueNumber                                                  AS DocNo,
                 Ts.TransDate,
                 Party.Name                                                       AS PartyName,
                 Tprod.ProductName                                                AS ItemName,
                 Tprod.Quantity,
                 Tprod.PrimaryUnitName                                            AS UnitName,
                 Tprod.UnitPrice,
                 COALESCE(Tprod.TaxableAmount, Tprod.Quantity * Tprod.UnitPrice) AS TaxableAmount,
                 COALESCE(Tprod.TaxPercentage, 0)                                AS TaxPercentage,
                 COALESCE(Tprod.TaxAmount,
                     COALESCE(Tprod.CgstAmount,0) + COALESCE(Tprod.SgstAmount,0) + COALESCE(Tprod.IgstAmount,0)
                 )                                                                AS TaxAmount,
                 COALESCE(Tprod.DiscountAmount, 0)                               AS DiscountAmount,
                 COALESCE(Tprod.NetAmount,
                     COALESCE(Tprod.TaxableAmount, Tprod.Quantity * Tprod.UnitPrice)
                     + COALESCE(Tprod.TaxAmount,
                         COALESCE(Tprod.CgstAmount,0)+COALESCE(Tprod.SgstAmount,0)+COALESCE(Tprod.IgstAmount,0))
                     - COALESCE(Tprod.DiscountAmount,0)
                 )                                                                AS NetAmount
             FROM Transaction.TransProductsTbl AS Tprod
             JOIN Transaction.TransactionsTbl  AS Ts
                  ON  Ts.TransUID  = Tprod.TransUID
                  AND Ts.OrgUID    = Tprod.OrgUID
                  AND Ts.ModuleUID = ?
                  AND Ts.IsDeleted = 0
                  AND Ts.IsActive  = 1
                  AND Ts.DocStatus NOT IN ('Draft','Cancelled','Rejected')
                  AND Ts.TransDate BETWEEN ? AND ?
             {$partyJoin}
             WHERE Tprod.OrgUID    = ?
               AND Tprod.IsDeleted = 0
               AND Tprod.IsActive  = 1
             ORDER BY Ts.TransDate DESC, Ts.TransUID DESC, Tprod.ItemSequence ASC
             LIMIT 3000",
            [$moduleUID, $from, $to, $orgUID]
        );
        return $q ? $q->result_array() : [];
    }

    /**
     * Invoice item-wise detail (ModuleUID=103).
     *
     * @param int    $orgUID
     * @param string $from   Y-m-d
     * @param string $to     Y-m-d
     * @return array
     */
    public function getInvoiceItemwiseData(int $orgUID, string $from, string $to): array
    {
        return $this->_getBillwiseItemData($orgUID, 103, 'C', $from, $to);
    }

    /**
     * Purchase item-wise detail (ModuleUID=105).
     *
     * @param int    $orgUID
     * @param string $from   Y-m-d
     * @param string $to     Y-m-d
     * @return array
     */
    public function getPurchaseItemwiseData(int $orgUID, string $from, string $to): array
    {
        return $this->_getBillwiseItemData($orgUID, 105, 'S', $from, $to);
    }

    /**
     * Sales return item-wise detail (ModuleUID=106).
     *
     * @param int    $orgUID
     * @param string $from   Y-m-d
     * @param string $to     Y-m-d
     * @return array
     */
    public function getSalesReturnItemwiseData(int $orgUID, string $from, string $to): array
    {
        return $this->_getBillwiseItemData($orgUID, 106, 'C', $from, $to);
    }

    /**
     * Purchase return item-wise detail (ModuleUID=108).
     *
     * @param int    $orgUID
     * @param string $from   Y-m-d
     * @param string $to     Y-m-d
     * @return array
     */
    public function getPurchaseReturnItemwiseData(int $orgUID, string $from, string $to): array
    {
        return $this->_getBillwiseItemData($orgUID, 108, 'S', $from, $to);
    }

    /**
     * Shared bill-level register query for TransactionsTbl-based modules.
     *
     * @param int    $orgUID
     * @param int    $moduleUID
     * @param string $partyType  'C' or 'S'
     * @param string $from       Y-m-d
     * @param string $to         Y-m-d
     * @return array
     */
    private function _getTransRegisterData(int $orgUID, int $moduleUID, string $partyType, string $from, string $to): array
    {
        $partyJoin = ($partyType === 'C')
            ? "LEFT JOIN Customers.CustomerTbl AS Party ON Party.CustomerUID = Ts.PartyUID AND Ts.PartyType = 'C'"
            : "LEFT JOIN Vendors.VendorTbl     AS Party ON Party.VendorUID   = Ts.PartyUID AND Ts.PartyType = 'S'";

        $q = $this->ReadDb->query(
            "SELECT
                 Ts.UniqueNumber                  AS DocNo,
                 Ts.TransDate,
                 COALESCE(Party.Name, '—')        AS PartyName,
                 COALESCE(Ts.SubTotal,   0)       AS Taxable,
                 COALESCE(Ts.CgstAmount, 0)       AS CgstAmount,
                 COALESCE(Ts.SgstAmount, 0)       AS SgstAmount,
                 COALESCE(Ts.IgstAmount, 0)       AS IgstAmount,
                 COALESCE(Ts.TaxAmount,  0)       AS TaxAmount,
                 COALESCE(Ts.RoundOff,   0)       AS RoundOff,
                 COALESCE(Ts.NetAmount,  0)       AS NetAmount,
                 Ts.DocStatus
             FROM Transaction.TransactionsTbl AS Ts
             {$partyJoin}
             WHERE Ts.OrgUID    = ?
               AND Ts.ModuleUID = ?
               AND Ts.IsDeleted = 0
               AND Ts.IsActive  = 1
               AND Ts.DocStatus NOT IN ('Draft','Cancelled','Rejected')
               AND Ts.TransDate BETWEEN ? AND ?
             ORDER BY Ts.TransDate DESC, Ts.TransUID DESC
             LIMIT 2000",
            [$orgUID, $moduleUID, $from, $to]
        );
        return $q ? $q->result_array() : [];
    }

    /**
     * Sales Register: invoice-wise with tax breakup (ModuleUID=103).
     *
     * @param int    $orgUID
     * @param string $from   Y-m-d
     * @param string $to     Y-m-d
     * @return array
     */
    public function getSalesRegisterData(int $orgUID, string $from, string $to): array
    {
        return $this->_getTransRegisterData($orgUID, 103, 'C', $from, $to);
    }

    /**
     * Purchase Register: bill-wise with tax breakup (ModuleUID=105).
     *
     * @param int    $orgUID
     * @param string $from   Y-m-d
     * @param string $to     Y-m-d
     * @return array
     */
    public function getPurchaseRegisterData(int $orgUID, string $from, string $to): array
    {
        return $this->_getTransRegisterData($orgUID, 105, 'S', $from, $to);
    }

    /**
     * Sales Return Register: credit note-wise with tax breakup (ModuleUID=106).
     *
     * @param int    $orgUID
     * @param string $from   Y-m-d
     * @param string $to     Y-m-d
     * @return array
     */
    public function getSalesReturnRegisterData(int $orgUID, string $from, string $to): array
    {
        return $this->_getTransRegisterData($orgUID, 106, 'C', $from, $to);
    }

    /**
     * Purchase Return Register: debit note-wise with tax breakup (ModuleUID=108).
     *
     * @param int    $orgUID
     * @param string $from   Y-m-d
     * @param string $to     Y-m-d
     * @return array
     */
    public function getPurchaseReturnRegisterData(int $orgUID, string $from, string $to): array
    {
        return $this->_getTransRegisterData($orgUID, 108, 'S', $from, $to);
    }

    /**
     * Delivery Challan Register: dispatch-wise (ModuleUID=112).
     *
     * @param int    $orgUID
     * @param string $from   Y-m-d
     * @param string $to     Y-m-d
     * @return array
     */
    public function getDeliveryChallanRegisterData(int $orgUID, string $from, string $to): array
    {
        return $this->_getTransRegisterData($orgUID, 112, 'C', $from, $to);
    }

    /**
     * Expense Register: all committed expenses in a date range.
     *
     * @param int    $orgUID
     * @param string $from   Y-m-d
     * @param string $to     Y-m-d
     * @return array
     */
    public function getExpenseRegisterData(int $orgUID, string $from, string $to): array
    {
        $q = $this->ReadDb->query(
            "SELECT
                 e.ExpenseNumber                    AS DocNo,
                 e.ExpenseDate                      AS TransDate,
                 COALESCE(v.Name,        '—')       AS VendorName,
                 COALESCE(ec.CategoryName, '—')     AS CategoryName,
                 COALESCE(e.Amount,      0)         AS Amount,
                 COALESCE(e.TaxAmount,   0)         AS TaxAmount,
                 COALESCE(e.NetAmount,   0)         AS NetAmount,
                 e.DocStatus,
                 COALESCE(e.IsPaid, 0)              AS IsPaid
             FROM Transaction.ExpensesTbl e
             LEFT JOIN Transaction.ExpenseCategoryTbl ec
                   ON  ec.CategoryUID = e.CategoryUID AND ec.IsDeleted = 0
             LEFT JOIN Vendors.VendorTbl v
                   ON  v.VendorUID = e.VendorUID
             WHERE e.OrgUID    = ?
               AND e.IsDeleted = 0
               AND e.DocStatus NOT IN ('Draft','Cancelled')
               AND e.ExpenseDate BETWEEN ? AND ?
             ORDER BY e.ExpenseDate DESC, e.ExpenseUID DESC
             LIMIT 2000",
            [$orgUID, $from, $to]
        );
        return $q ? $q->result_array() : [];
    }

    // ── Tax Reports ───────────────────────────────────────────────────────────

    /**
     * GSTR-1: Outward supplies for a given month — B2B, B2CS, and CDNR sub-sets.
     * Returns ['b2b' => [...], 'b2cs' => [...], 'cdnr' => [...]].
     *
     * @param int $orgUID
     * @param int $month  1-12
     * @param int $year   e.g. 2026
     * @return array
     */
    public function getGstr1Data(int $orgUID, int $month, int $year): array
    {
        $b2bQ = $this->ReadDb->query(
            "SELECT Ts.UniqueNumber AS InvNo, Ts.TransDate,
                    COALESCE(Cust.GSTIN, '') AS RecipientGSTIN,
                    COALESCE(Cust.Name,  '—') AS RecipientName,
                    COALESCE(Td.PlaceOfSupplyCode, '') AS PlaceOfSupply,
                    COALESCE(Ts.SubTotal,    0) AS Taxable,
                    COALESCE(Ts.CgstAmount,  0) AS CgstAmount,
                    COALESCE(Ts.SgstAmount,  0) AS SgstAmount,
                    COALESCE(Ts.IgstAmount,  0) AS IgstAmount,
                    COALESCE(Ts.TaxAmount,   0) AS TaxAmount,
                    COALESCE(Ts.NetAmount,   0) AS NetAmount
             FROM Transaction.TransactionsTbl Ts
             JOIN Customers.CustomerTbl Cust
                  ON Cust.CustomerUID = Ts.PartyUID AND Ts.PartyType = 'C'
                  AND Cust.GSTIN IS NOT NULL AND Cust.GSTIN != ''
             LEFT JOIN Transaction.TransDetailTbl Td
                  ON Td.TransUID = Ts.TransUID AND Td.FinancialYear = YEAR(Ts.TransDate)
             WHERE Ts.OrgUID = ? AND Ts.ModuleUID = 103 AND Ts.IsDeleted = 0 AND Ts.IsActive = 1
               AND Ts.DocStatus NOT IN ('Draft','Cancelled','Rejected')
               AND MONTH(Ts.TransDate) = ? AND YEAR(Ts.TransDate) = ?
             ORDER BY Ts.TransDate DESC, Ts.TransUID DESC LIMIT 2000",
            [$orgUID, $month, $year]
        );

        $b2csQ = $this->ReadDb->query(
            "SELECT Ts.UniqueNumber AS InvNo, Ts.TransDate,
                    COALESCE(Cust.Name,  '—') AS CustomerName,
                    COALESCE(Td.PlaceOfSupplyCode, '') AS PlaceOfSupply,
                    COALESCE(Ts.SubTotal,    0) AS Taxable,
                    COALESCE(Ts.CgstAmount,  0) AS CgstAmount,
                    COALESCE(Ts.SgstAmount,  0) AS SgstAmount,
                    COALESCE(Ts.IgstAmount,  0) AS IgstAmount,
                    COALESCE(Ts.TaxAmount,   0) AS TaxAmount,
                    COALESCE(Ts.NetAmount,   0) AS NetAmount
             FROM Transaction.TransactionsTbl Ts
             LEFT JOIN Customers.CustomerTbl Cust
                  ON Cust.CustomerUID = Ts.PartyUID AND Ts.PartyType = 'C'
             LEFT JOIN Transaction.TransDetailTbl Td
                  ON Td.TransUID = Ts.TransUID AND Td.FinancialYear = YEAR(Ts.TransDate)
             WHERE Ts.OrgUID = ? AND Ts.ModuleUID = 103 AND Ts.IsDeleted = 0 AND Ts.IsActive = 1
               AND Ts.DocStatus NOT IN ('Draft','Cancelled','Rejected')
               AND (Cust.GSTIN IS NULL OR Cust.GSTIN = '')
               AND MONTH(Ts.TransDate) = ? AND YEAR(Ts.TransDate) = ?
             ORDER BY Ts.TransDate DESC, Ts.TransUID DESC LIMIT 2000",
            [$orgUID, $month, $year]
        );

        $cdnrQ = $this->ReadDb->query(
            "SELECT Ts.UniqueNumber AS DocNo, Ts.TransDate,
                    COALESCE(Cust.GSTIN, '') AS RecipientGSTIN,
                    COALESCE(Cust.Name,  '—') AS RecipientName,
                    COALESCE(Ts.SubTotal,    0) AS Taxable,
                    COALESCE(Ts.CgstAmount,  0) AS CgstAmount,
                    COALESCE(Ts.SgstAmount,  0) AS SgstAmount,
                    COALESCE(Ts.IgstAmount,  0) AS IgstAmount,
                    COALESCE(Ts.TaxAmount,   0) AS TaxAmount,
                    COALESCE(Ts.NetAmount,   0) AS NetAmount
             FROM Transaction.TransactionsTbl Ts
             JOIN Customers.CustomerTbl Cust
                  ON Cust.CustomerUID = Ts.PartyUID AND Ts.PartyType = 'C'
                  AND Cust.GSTIN IS NOT NULL AND Cust.GSTIN != ''
             WHERE Ts.OrgUID = ? AND Ts.ModuleUID = 106 AND Ts.IsDeleted = 0 AND Ts.IsActive = 1
               AND Ts.DocStatus NOT IN ('Draft','Cancelled','Rejected')
               AND MONTH(Ts.TransDate) = ? AND YEAR(Ts.TransDate) = ?
             ORDER BY Ts.TransDate DESC, Ts.TransUID DESC LIMIT 1000",
            [$orgUID, $month, $year]
        );

        return [
            'b2b'  => $b2bQ  ? $b2bQ->result_array()  : [],
            'b2cs' => $b2csQ ? $b2csQ->result_array() : [],
            'cdnr' => $cdnrQ ? $cdnrQ->result_array() : [],
        ];
    }

    /**
     * GSTR-2B: Inward supplies (purchase bills) from GSTIN-registered vendors for a given month.
     *
     * @param int $orgUID
     * @param int $month  1-12
     * @param int $year
     * @return array
     */
    public function getGstr2bData(int $orgUID, int $month, int $year): array
    {
        $q = $this->ReadDb->query(
            "SELECT Ts.UniqueNumber AS BillNo, Ts.TransDate,
                    COALESCE(Vend.GSTIN, '') AS SupplierGSTIN,
                    COALESCE(Vend.Name,  '—') AS SupplierName,
                    COALESCE(Td.PlaceOfSupplyCode, '') AS PlaceOfSupply,
                    COALESCE(Ts.SubTotal,    0) AS Taxable,
                    COALESCE(Ts.CgstAmount,  0) AS CgstAmount,
                    COALESCE(Ts.SgstAmount,  0) AS SgstAmount,
                    COALESCE(Ts.IgstAmount,  0) AS IgstAmount,
                    COALESCE(Ts.TaxAmount,   0) AS TaxAmount,
                    COALESCE(Ts.NetAmount,   0) AS NetAmount,
                    Ts.DocStatus
             FROM Transaction.TransactionsTbl Ts
             JOIN Vendors.VendorTbl Vend
                  ON Vend.VendorUID = Ts.PartyUID AND Ts.PartyType = 'S'
                  AND Vend.GSTIN IS NOT NULL AND Vend.GSTIN != ''
             LEFT JOIN Transaction.TransDetailTbl Td
                  ON Td.TransUID = Ts.TransUID AND Td.FinancialYear = YEAR(Ts.TransDate)
             WHERE Ts.OrgUID = ? AND Ts.ModuleUID = 105 AND Ts.IsDeleted = 0 AND Ts.IsActive = 1
               AND Ts.DocStatus NOT IN ('Draft','Cancelled','Rejected')
               AND MONTH(Ts.TransDate) = ? AND YEAR(Ts.TransDate) = ?
             ORDER BY Ts.TransDate DESC, Ts.TransUID DESC LIMIT 2000",
            [$orgUID, $month, $year]
        );
        return $q ? $q->result_array() : [];
    }

    /**
     * GSTR-3B: Monthly consolidated summary — outward supplies and ITC available.
     * Returns ['outward' => {...}, 'itc' => {...}].
     *
     * @param int $orgUID
     * @param int $month  1-12
     * @param int $year
     * @return array
     */
    public function getGstr3bData(int $orgUID, int $month, int $year): array
    {
        $outQ = $this->ReadDb->query(
            "SELECT
                 SUM(CASE WHEN ModuleUID=103 THEN COALESCE(SubTotal,0)    ELSE 0 END) AS SalesTaxable,
                 SUM(CASE WHEN ModuleUID=103 THEN COALESCE(CgstAmount,0)  ELSE 0 END) AS SalesCgst,
                 SUM(CASE WHEN ModuleUID=103 THEN COALESCE(SgstAmount,0)  ELSE 0 END) AS SalesSgst,
                 SUM(CASE WHEN ModuleUID=103 THEN COALESCE(IgstAmount,0)  ELSE 0 END) AS SalesIgst,
                 SUM(CASE WHEN ModuleUID=103 THEN COALESCE(TaxAmount,0)   ELSE 0 END) AS SalesTax,
                 SUM(CASE WHEN ModuleUID=103 THEN COALESCE(NetAmount,0)   ELSE 0 END) AS SalesNet,
                 COUNT(CASE WHEN ModuleUID=103 THEN 1 END)                             AS SalesCount,
                 SUM(CASE WHEN ModuleUID=106 THEN COALESCE(SubTotal,0)    ELSE 0 END) AS ReturnTaxable,
                 SUM(CASE WHEN ModuleUID=106 THEN COALESCE(CgstAmount,0)  ELSE 0 END) AS ReturnCgst,
                 SUM(CASE WHEN ModuleUID=106 THEN COALESCE(SgstAmount,0)  ELSE 0 END) AS ReturnSgst,
                 SUM(CASE WHEN ModuleUID=106 THEN COALESCE(IgstAmount,0)  ELSE 0 END) AS ReturnIgst,
                 SUM(CASE WHEN ModuleUID=106 THEN COALESCE(TaxAmount,0)   ELSE 0 END) AS ReturnTax,
                 COUNT(CASE WHEN ModuleUID=106 THEN 1 END)                             AS ReturnCount
             FROM Transaction.TransactionsTbl
             WHERE OrgUID=? AND ModuleUID IN (103,106) AND IsDeleted=0 AND IsActive=1
               AND DocStatus NOT IN ('Draft','Cancelled','Rejected')
               AND MONTH(TransDate)=? AND YEAR(TransDate)=?",
            [$orgUID, $month, $year]
        );
        $itcQ = $this->ReadDb->query(
            "SELECT
                 SUM(CASE WHEN ModuleUID=105 THEN COALESCE(SubTotal,0)    ELSE 0 END) AS PurchaseTaxable,
                 SUM(CASE WHEN ModuleUID=105 THEN COALESCE(CgstAmount,0)  ELSE 0 END) AS PurchaseCgst,
                 SUM(CASE WHEN ModuleUID=105 THEN COALESCE(SgstAmount,0)  ELSE 0 END) AS PurchaseSgst,
                 SUM(CASE WHEN ModuleUID=105 THEN COALESCE(IgstAmount,0)  ELSE 0 END) AS PurchaseIgst,
                 SUM(CASE WHEN ModuleUID=105 THEN COALESCE(TaxAmount,0)   ELSE 0 END) AS PurchaseTax,
                 SUM(CASE WHEN ModuleUID=105 THEN COALESCE(NetAmount,0)   ELSE 0 END) AS PurchaseNet,
                 COUNT(CASE WHEN ModuleUID=105 THEN 1 END)                             AS PurchaseCount,
                 SUM(CASE WHEN ModuleUID=108 THEN COALESCE(CgstAmount,0)  ELSE 0 END) AS PRCgst,
                 SUM(CASE WHEN ModuleUID=108 THEN COALESCE(SgstAmount,0)  ELSE 0 END) AS PRSgst,
                 SUM(CASE WHEN ModuleUID=108 THEN COALESCE(IgstAmount,0)  ELSE 0 END) AS PRIgst
             FROM Transaction.TransactionsTbl
             WHERE OrgUID=? AND ModuleUID IN (105,108) AND IsDeleted=0 AND IsActive=1
               AND DocStatus NOT IN ('Draft','Cancelled','Rejected')
               AND MONTH(TransDate)=? AND YEAR(TransDate)=?",
            [$orgUID, $month, $year]
        );
        return [
            'outward' => $outQ ? ($outQ->row_array() ?: []) : [],
            'itc'     => $itcQ ? ($itcQ->row_array() ?: []) : [],
        ];
    }

    /**
     * GSTR-7: TDS deducted at source — returns expenses with TDSApplicable=1 in date range.
     *
     * @param int    $orgUID
     * @param string $from   Y-m-d
     * @param string $to     Y-m-d
     * @return array
     */
    public function getGstr7Data(int $orgUID, string $from, string $to): array
    {
        $q = $this->ReadDb->query(
            "SELECT e.ExpenseNumber AS DocNo, e.ExpenseDate AS TransDate,
                    COALESCE(v.Name,        '—')   AS VendorName,
                    COALESCE(v.GSTIN,       '')    AS VendorGSTIN,
                    COALESCE(ts.SectionCode,'—')   AS TdsSection,
                    COALESCE(ts.Description,'—')   AS TdsSectionDesc,
                    COALESCE(e.Amount,       0)    AS BaseAmount,
                    COALESCE(e.TDSPercentage,0)    AS TdsPct,
                    COALESCE(e.TDSAmount,    0)    AS TdsAmount,
                    COALESCE(e.NetAmount,    0)    AS NetAmount,
                    e.DocStatus, COALESCE(e.IsPaid,0) AS IsPaid
             FROM Transaction.ExpensesTbl e
             LEFT JOIN Vendors.VendorTbl v         ON v.VendorUID   = e.VendorUID
             LEFT JOIN Global.TdsSectionTbl ts     ON ts.TdsSectionUID = e.TdsSectionUID
             WHERE e.OrgUID=? AND e.IsDeleted=0 AND e.TDSApplicable=1
               AND e.DocStatus NOT IN ('Draft','Cancelled')
               AND e.ExpenseDate BETWEEN ? AND ?
             ORDER BY e.ExpenseDate DESC, e.ExpenseUID DESC LIMIT 1000",
            [$orgUID, $from, $to]
        );
        return $q ? $q->result_array() : [];
    }

    /**
     * HSN Summary: outward supplies grouped by HSN/SAC code + tax rate for a date range.
     *
     * @param int    $orgUID
     * @param string $from   Y-m-d
     * @param string $to     Y-m-d
     * @return array
     */
    public function getHsnSummaryData(int $orgUID, string $from, string $to): array
    {
        $q = $this->ReadDb->query(
            "SELECT
                 COALESCE(Prod.HSNSACCode, 'N/A')                                        AS HsnCode,
                 COALESCE(Unit.ShortName,  'PCS')                                        AS UOM,
                 COALESCE(Tprod.TaxPercentage, 0)                                        AS TaxRate,
                 SUM(Tprod.Quantity)                                                      AS TotalQty,
                 SUM(COALESCE(Tprod.TaxableAmount, Tprod.Quantity * Tprod.UnitPrice))    AS TaxableValue,
                 SUM(COALESCE(Tprod.CgstAmount, 0))                                      AS CgstAmount,
                 SUM(COALESCE(Tprod.SgstAmount, 0))                                      AS SgstAmount,
                 SUM(COALESCE(Tprod.IgstAmount, 0))                                      AS IgstAmount,
                 SUM(COALESCE(Tprod.TaxAmount,
                     COALESCE(Tprod.CgstAmount,0)+COALESCE(Tprod.SgstAmount,0)+COALESCE(Tprod.IgstAmount,0)
                 ))                                                                       AS TaxAmount,
                 COUNT(DISTINCT Ts.TransUID)                                              AS InvCount
             FROM Transaction.TransProductsTbl AS Tprod
             JOIN Transaction.TransactionsTbl  AS Ts
                  ON  Ts.TransUID  = Tprod.TransUID AND Ts.OrgUID = Tprod.OrgUID
                  AND Ts.ModuleUID = 103 AND Ts.IsDeleted=0 AND Ts.IsActive=1
                  AND Ts.DocStatus NOT IN ('Draft','Cancelled','Rejected')
                  AND Ts.TransDate BETWEEN ? AND ?
             JOIN Products.ProductTbl Prod ON Prod.ProductUID = Tprod.ProductUID
             LEFT JOIN Global.PrimaryUnitTbl Unit ON Unit.PrimaryUnitUID = Prod.PrimaryUnitUID
             WHERE Tprod.OrgUID=? AND Tprod.IsDeleted=0 AND Tprod.IsActive=1
             GROUP BY COALESCE(Prod.HSNSACCode,'N/A'), COALESCE(Tprod.TaxPercentage,0), COALESCE(Unit.ShortName,'PCS')
             ORDER BY TaxableValue DESC
             LIMIT 1000",
            [$from, $to, $orgUID]
        );
        return $q ? $q->result_array() : [];
    }

    /**
     * TDS Receivable: invoices where TDS may be deducted by customers.
     * Since TDS deduction by customers is not yet tracked in this schema,
     * returns empty array — data endpoint reserved for future implementation.
     *
     * @param int    $orgUID
     * @param string $from   Y-m-d
     * @param string $to     Y-m-d
     * @return array
     */
    public function getTdsReceivableData(int $orgUID, string $from, string $to): array
    {
        $q = $this->ReadDb->query(
            "SELECT Ts.UniqueNumber AS InvNo, Ts.TransDate,
                    COALESCE(Cust.Name, '—') AS CustomerName,
                    COALESCE(Cust.GSTIN, '') AS CustomerGSTIN,
                    COALESCE(Ts.SubTotal,  0) AS Taxable,
                    COALESCE(Ts.TaxAmount, 0) AS TaxAmount,
                    COALESCE(Ts.NetAmount, 0) AS NetAmount,
                    Ts.DocStatus
             FROM Transaction.TransactionsTbl Ts
             LEFT JOIN Customers.CustomerTbl Cust
                  ON Cust.CustomerUID = Ts.PartyUID AND Ts.PartyType = 'C'
             WHERE Ts.OrgUID=? AND Ts.ModuleUID=103 AND Ts.IsDeleted=0 AND Ts.IsActive=1
               AND Ts.DocStatus NOT IN ('Draft','Cancelled','Rejected')
               AND Ts.NetAmount >= 30000
               AND Ts.TransDate BETWEEN ? AND ?
             ORDER BY Ts.TransDate DESC, Ts.TransUID DESC LIMIT 1000",
            [$orgUID, $from, $to]
        );
        return $q ? $q->result_array() : [];
    }

    /**
     * TDS Payable: expenses with TDSApplicable=1 — TDS deducted by the org on vendor payments.
     *
     * @param int    $orgUID
     * @param string $from   Y-m-d
     * @param string $to     Y-m-d
     * @return array
     */
    public function getTdsPayableData(int $orgUID, string $from, string $to): array
    {
        $q = $this->ReadDb->query(
            "SELECT e.ExpenseNumber AS DocNo, e.ExpenseDate AS TransDate,
                    COALESCE(v.Name,        '—')   AS VendorName,
                    COALESCE(v.GSTIN,       '')    AS VendorGSTIN,
                    COALESCE(ts.SectionCode,'—')   AS TdsSection,
                    COALESCE(ts.Description,'—')   AS TdsSectionDesc,
                    COALESCE(e.Amount,       0)    AS BaseAmount,
                    COALESCE(e.TDSPercentage,0)    AS TdsPct,
                    COALESCE(e.TDSAmount,    0)    AS TdsAmount,
                    COALESCE(e.NetAmount,    0)    AS NetAmount,
                    e.DocStatus, COALESCE(e.IsPaid,0) AS IsPaid
             FROM Transaction.ExpensesTbl e
             LEFT JOIN Vendors.VendorTbl v         ON v.VendorUID      = e.VendorUID
             LEFT JOIN Global.TdsSectionTbl ts     ON ts.TdsSectionUID = e.TdsSectionUID
             WHERE e.OrgUID=? AND e.IsDeleted=0 AND e.TDSApplicable=1
               AND e.DocStatus NOT IN ('Draft','Cancelled')
               AND e.ExpenseDate BETWEEN ? AND ?
             ORDER BY e.ExpenseDate DESC, e.ExpenseUID DESC LIMIT 1000",
            [$orgUID, $from, $to]
        );
        return $q ? $q->result_array() : [];
    }

    /**
     * TCS Receivable: TCS collected from buyers (Section 206C).
     * Not yet tracked in this schema — returns empty array.
     *
     * @param int    $orgUID
     * @param string $from   Y-m-d
     * @param string $to     Y-m-d
     * @return array
     */
    public function getTcsReceivableData(int $orgUID, string $from, string $to): array
    {
        return [];
    }

    /**
     * TCS Payable: TCS collected that must be remitted to the government (Section 206C).
     * Not yet tracked in this schema — returns empty array.
     *
     * @param int    $orgUID
     * @param string $from   Y-m-d
     * @param string $to     Y-m-d
     * @return array
     */
    public function getTcsPayableData(int $orgUID, string $from, string $to): array
    {
        return [];
    }

    /**
     * Trial Balance: all ledger opening balances + period movements for a date range.
     * Only returns ledgers with non-zero opening balance or period activity.
     *
     * @param int    $orgUID
     * @param string $from   Y-m-d
     * @param string $to     Y-m-d
     * @return array
     */
    public function getTrialBalanceReportData(int $orgUID, string $from, string $to): array
    {
        $q = $this->ReadDb->query(
            "SELECT ca.LedgerUID, ca.LedgerCode, ca.LedgerName, ca.LedgerType,
                    ca.OpeningBalance, ca.OpeningBalanceType,
                    COALESCE(SUM(CASE WHEN je.TransactionType='Debit'  THEN je.Amount ELSE 0 END),0) AS PeriodDebit,
                    COALESCE(SUM(CASE WHEN je.TransactionType='Credit' THEN je.Amount ELSE 0 END),0) AS PeriodCredit
             FROM Accounting.ChartOfAccounts ca
             LEFT JOIN Accounting.JournalEntries je ON je.LedgerUID  = ca.LedgerUID  AND je.IsDeleted = 0
             LEFT JOIN Accounting.GeneralJournal gj ON gj.JournalUID = je.JournalUID AND gj.IsDeleted = 0
                                                    AND gj.JournalDate BETWEEN ? AND ?
             WHERE ca.OrgUID    = ?
               AND ca.IsDeleted = 0
             GROUP BY ca.LedgerUID
             HAVING PeriodDebit > 0 OR PeriodCredit > 0 OR ca.OpeningBalance > 0
             ORDER BY ca.LedgerType, ca.LedgerName",
            [$from, $to, $orgUID]
        );
        return $q ? $q->result_array() : [];
    }

    // ── Brand Wise Sales ──────────────────────────────────────────────────────

    /**
     * Sales totals grouped by brand for a date range (Invoices ModuleUID=103).
     *
     * @param int    $orgUID
     * @param string $from   Y-m-d
     * @param string $to     Y-m-d
     * @return array
     */
    public function getBrandWiseSalesData(int $orgUID, string $from, string $to): array
    {
        $q = $this->ReadDb->query(
            "SELECT
                 Tprod.BrandUID,
                 COALESCE(b.Name, 'No Brand')           AS BrandName,
                 COUNT(DISTINCT Tprod.ProductUID)        AS ProductCount,
                 COUNT(DISTINCT Ts.TransUID)             AS InvoiceCount,
                 SUM(Tprod.Quantity)                     AS TotalQty,
                 SUM(Tprod.Quantity * Tprod.UnitPrice)   AS TotalValue,
                 AVG(Tprod.UnitPrice)                    AS AvgPrice
             FROM Transaction.TransProductsTbl AS Tprod
             JOIN Transaction.TransactionsTbl  AS Ts
                  ON  Ts.TransUID  = Tprod.TransUID
                  AND Ts.OrgUID    = Tprod.OrgUID
                  AND Ts.ModuleUID = 103
                  AND Ts.IsDeleted = 0
                  AND Ts.IsActive  = 1
                  AND Ts.DocStatus NOT IN ('Draft','Cancelled','Rejected')
                  AND Ts.TransDate BETWEEN ? AND ?
             LEFT JOIN Products.BrandTbl b ON b.BrandUID = Tprod.BrandUID
             WHERE Tprod.OrgUID    = ?
               AND Tprod.IsDeleted = 0
               AND Tprod.IsActive  = 1
               AND Tprod.BrandUID IS NOT NULL
             GROUP BY Tprod.BrandUID
             ORDER BY TotalValue DESC
             LIMIT 500",
            [$from, $to, $orgUID]
        );
        return $q ? $q->result_array() : [];
    }

    // ── Variant Stock Summary ─────────────────────────────────────────────────

    /**
     * Current stock position for every active brand × size variant.
     *
     * @param int $orgUID
     * @return array
     */
    public function getVariantStockData(int $orgUID): array
    {
        $q = $this->ReadDb->query(
            "SELECT
                 pv.VariantUID,
                 pv.ProductUID,
                 p.ItemName,
                 COALESCE(b.Name, '—') AS BrandName,
                 COALESCE(s.Name, '—') AS SizeName,
                 pv.PartNumber,
                 pv.PurchasePrice,
                 pv.SellingPrice,
                 COALESCE(pvs.AvailableQty, 0)                         AS StockQty,
                 COALESCE(pvs.AvailableQty, 0) * pv.PurchasePrice      AS StockValue
             FROM Products.ProductVariantTbl pv
             JOIN Products.ProductTbl p
                  ON p.ProductUID = pv.ProductUID AND p.OrgUID = pv.OrgUID AND p.IsDeleted = 0
             LEFT JOIN Products.BrandTbl b  ON b.BrandUID = pv.BrandUID
             LEFT JOIN Products.SizeTbl  s  ON s.SizeUID  = pv.SizeUID
             LEFT JOIN Products.ProductVariantStockTbl pvs
                  ON pvs.VariantUID = pv.VariantUID AND pvs.OrgUID = pv.OrgUID
             WHERE pv.OrgUID    = ?
               AND pv.IsActive  = 1
             ORDER BY b.Name ASC, s.Name ASC, p.ItemName ASC
             LIMIT 2000",
            [$orgUID]
        );
        return $q ? $q->result_array() : [];
    }
}
