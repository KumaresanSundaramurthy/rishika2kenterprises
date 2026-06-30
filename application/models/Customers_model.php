<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Customers_model extends CI_Model {
    
    private $ReadDb;
    private $WriteDb;

	function __construct() {
        parent::__construct();

        $this->ReadDb  = $this->load->database('ReadDB',  TRUE);
        $this->WriteDb = $this->load->database('WriteDB', TRUE);

    }

    public function getReadDb()  { return $this->ReadDb;  }
    public function getWriteDb() { return $this->WriteDb; }

    public function getCustomerPendingNoteTotals($orgUID, $customerUID) {
        try {
            $this->WriteDb->db_debug = FALSE;
            $result = $this->WriteDb->query(
                "SELECT
                    COALESCE((SELECT SUM(Amount) FROM Transaction.TransCreditNoteTbl
                              WHERE OrgUID=? AND PartyUID=? AND PartyType='C' AND Status='Pending' AND IsCancelled=0 AND IsDeleted=0 AND PaymentCleared=0), 0) AS CreditTotal,
                    COALESCE((SELECT SUM(Amount) FROM Transaction.TransDebitNoteTbl
                              WHERE OrgUID=? AND PartyUID=? AND PartyType='C' AND Status='Pending' AND IsDeleted=0), 0) AS DebitTotal",
                [(int)$orgUID, (int)$customerUID, (int)$orgUID, (int)$customerUID]
            );
            $row = $result ? $result->row() : null;
            return [(float)($row->CreditTotal ?? 0), (float)($row->DebitTotal ?? 0)];
        } catch (Exception $e) {
            log_message('error', 'Customers_model::getCustomerPendingNoteTotals failed: ' . $e->getMessage());
            return [0.0, 0.0];
        }
    }

    public function getCustomers($FilterArray) {

        try {

            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select([
                'Customers.CustomerUID AS CustomerUID',
                'Customers.OrgUID AS OrgUID',
                'Customers.SalutationUID AS SalutationUID',
                'Customers.Name AS Name',
                'Customers.Area AS Area',
                'Customers.CountryISO2 as CountryISO2',
                'Customers.CountryCode as CountryCode',
                'Customers.MobileNumber as MobileNumber',
                'Customers.EmailAddress as EmailAddress',
                'Customers.GSTIN as GSTIN',
                'Customers.CompanyName as CompanyName',
                'Customers.DebitCreditType as DebitCreditType',
                'Customers.DebitCreditAmount as DebitCreditAmount',
                'Customers.Image as Image',
                'Customers.PANNumber as PANNumber',
                'Customers.ContactPerson as ContactPerson',
                'Customers.DateOfBirth as DateOfBirth',
                'Customers.DiscountPercent as DiscountPercent',
                'Customers.CreditPeriod as CreditPeriod',
                'Customers.CreditLimit as CreditLimit',
                'Customers.Notes as Notes',
                'Customers.Tags as Tags',
                'Customers.CCEmails as CCEmails',
                'Customers.CustomerTypeUID as CustomerTypeUID',
                'Customers.GroupUID as GroupUID',
                'Customers.CreatedOn as CreatedOn',
                'Customers.UpdatedOn as UpdatedOn',
                // Actual opening balance from CustOpeningBalanceTbl (source of truth)
                'COALESCE(COB.OpeningBalance, Customers.DebitCreditAmount) as OpeningBalance',
                'COALESCE(COB.OpeningBalType, Customers.DebitCreditType, \'Debit\') as OpeningBalType',
            ]);
            $this->ReadDb->from('Customers.CustomerTbl as Customers');
            $this->ReadDb->join(
                'Customers.CustOpeningBalanceTbl as COB',
                'COB.CustomerUID = Customers.CustomerUID AND COB.OrgUID = Customers.OrgUID AND COB.IsDeleted = 0',
                'left'
            );
            $this->ReadDb->where(['Customers.IsDeleted' => 0, 'Customers.IsActive' => 1]);
            if(sizeof($FilterArray) > 0) {
                $this->ReadDb->where($FilterArray);
            }
            $query = $this->ReadDb->get();
            if (!$query) {
                $error = $this->ReadDb->error();
                throw new Exception($error['message'] ?? 'Database error occurred');
            }

            return $query->result();

        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

    }

    public function getCustomerAddress($FilterArray) {

        try {

            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select([
                'CustAddress.CustAddressUID AS CustAddressUID',
                'CustAddress.OrgUID AS OrgUID',
                'CustAddress.CustomerUID AS CustomerUID',
                'CustAddress.AddressType as AddressType',
                'CustAddress.Line1 as Line1',
                'CustAddress.Line2 as Line2',
                'CustAddress.Pincode as Pincode',
                'CustAddress.City as City',
                'CustAddress.CityText as CityText',
                'CustAddress.State as State',
                'CustAddress.StateText as StateText',
            ]);
            $this->ReadDb->from('Customers.CustAddressTbl as CustAddress');
            $this->ReadDb->where(['CustAddress.IsDeleted' => 0, 'CustAddress.IsActive' => 1]);
            if(sizeof($FilterArray) > 0) {
                $this->ReadDb->where($FilterArray);
            }
            $query = $this->ReadDb->get();
            if (!$query) {
                $error = $this->ReadDb->error();
                throw new Exception($error['message'] ?? 'Database error occurred');
            }
            
            return $query->result();

        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

    }

    public function getCustomerBankInfo($FilterArray) {

        try {

            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select([
                'CustBankDetails.CustBankDetUID AS CustBankDetUID',
                'CustBankDetails.CustomerUID AS CustomerUID',
                'CustBankDetails.Type as Type',
                'CustBankDetails.BankAccountNumber as BankAccountNumber',
                'CustBankDetails.BankIFSC_Code as BankIFSC_Code',
                'CustBankDetails.BankBranchName as BankBranchName',
                'CustBankDetails.BankAccountHolderName as BankAccountHolderName',
                'CustBankDetails.UPI_Id as UPI_Id',
            ]);
            $this->ReadDb->from('Customers.CustBankDetailsTbl as CustBankDetails');
            $this->ReadDb->where(['CustBankDetails.IsDeleted' => 0, 'CustBankDetails.IsActive' => 1]);
            if(sizeof($FilterArray) > 0) {
                $this->ReadDb->where($FilterArray);
            }
            $query = $this->ReadDb->get();
            if (!$query) {
                $error = $this->ReadDb->error();
                throw new Exception($error['message'] ?? 'Database error occurred');
            }
            
            return $query->result();

        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

    }

    public function getCustomersDetails(string $Term, $WhereCondition = []) {
        
        try {

            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select([
                'Customers.CustomerUID AS CustomerUID',
                'Customers.Name AS Name',
                'Customers.Area AS Area',
            ]);
            $this->ReadDb->from('Customers.CustomerTbl as Customers');
            if($Term) {
                $this->ReadDb->group_start();
                $this->ReadDb->or_like('Customers.Name', $Term, 'both');
                $this->ReadDb->or_like('Customers.Area', $Term, 'both');
                $this->ReadDb->or_like('Customers.MobileNumber', $Term, 'both');
                $this->ReadDb->group_end();
            }
            $this->ReadDb->where(['Customers.IsDeleted' => 0, 'Customers.IsActive' => 1]);
            if(sizeof($WhereCondition) > 0) {
                $this->ReadDb->where($WhereCondition);
            }
            $this->ReadDb->limit(10);

            $query = $this->ReadDb->get();
            if (!$query) {
                $error = $this->ReadDb->error();
                throw new Exception($error['message'] ?? 'Database error occurred');
            }
            return $query->result();

        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

    }

    public function getCustomerStats($OrgUID) {

        try {
            $this->ReadDb->db_debug = FALSE;

            // Range conditions so idx_customers_created is usable (not MONTH()/YEAR())
            $thisMonthStart = date('Y-m-01');
            $nextMonthStart = date('Y-m-01', strtotime('+1 month'));
            $lastMonthStart = date('Y-m-01', strtotime('-1 month'));
            $fyStart        = (date('n') >= 4)
                              ? date('Y') . '-04-01'
                              : (date('Y') - 1) . '-04-01';

            $this->ReadDb->select([
                'COUNT(C.CustomerUID) AS TotalCount',
                'SUM(CASE WHEN C.IsActive = 1 THEN 1 ELSE 0 END) AS ActiveCount',
                "SUM(CASE WHEN C.CreatedOn >= '{$thisMonthStart}' AND C.CreatedOn < '{$nextMonthStart}' THEN 1 ELSE 0 END) AS MonthCount",
                "SUM(CASE WHEN C.CreatedOn >= '{$fyStart}' THEN 1 ELSE 0 END) AS FYCount",
                "SUM(CASE WHEN C.CreatedOn >= '{$lastMonthStart}' AND C.CreatedOn < '{$thisMonthStart}' THEN 1 ELSE 0 END) AS LastMonthCount",
                // To Collect: customers who owe us (Debit balance)
                "SUM(CASE WHEN COB.PendingBalType = 'Debit' AND COB.PendingBalance > 0 THEN 1 ELSE 0 END) AS ToCollectCount",
                "COALESCE(SUM(CASE WHEN COB.PendingBalType = 'Debit' AND COB.PendingBalance > 0 THEN COB.PendingBalance ELSE 0 END), 0) AS ToCollectAmount",
                // To Pay: we owe customers (Credit balance)
                "SUM(CASE WHEN COB.PendingBalType = 'Credit' AND COB.PendingBalance > 0 THEN 1 ELSE 0 END) AS ToPayCount",
                "COALESCE(SUM(CASE WHEN COB.PendingBalType = 'Credit' AND COB.PendingBalance > 0 THEN COB.PendingBalance ELSE 0 END), 0) AS ToPayAmount",
            ]);
            $this->ReadDb->from('Customers.CustomerTbl C');
            $this->ReadDb->join(
                'Customers.CustOpeningBalanceTbl COB',
                'COB.CustomerUID = C.CustomerUID AND COB.OrgUID = C.OrgUID AND COB.IsDeleted = 0',
                'left'
            );
            $this->ReadDb->where(['C.IsDeleted' => 0, 'C.OrgUID' => (int)$OrgUID]);
            $query = $this->ReadDb->get();
            if (!$query) throw new Exception('DB error');
            return $query->row();
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

    }

    public function getCustomerListPaginated($orgUID, $limit, $offset, $filter = []) {

        try {
            $this->ReadDb->db_debug = FALSE;

            $baseWhere = ['Customers.IsDeleted' => 0, 'Customers.OrgUID' => $orgUID];
            if (isset($filter['IsActive'])) {
                $baseWhere['Customers.IsActive'] = (int) $filter['IsActive'];
            }

            // ToCollect / ToPay filter — subquery approach works for both count and data queries
            $balanceSubquery = null;
            if (!empty($filter['BalanceType'])) {
                $balType        = ($filter['BalanceType'] === 'Credit') ? 'Credit' : 'Debit';
                $balanceSubquery = "Customers.CustomerUID IN (
                    SELECT CustomerUID FROM Customers.CustOpeningBalanceTbl
                    WHERE OrgUID = {$orgUID} AND PendingBalType = '{$balType}'
                      AND PendingBalance > 0 AND IsDeleted = 0
                )";
            }

            // Count query
            $this->ReadDb->select('COUNT(*) AS cnt');
            $this->ReadDb->from('Customers.CustomerTbl as Customers');
            $this->ReadDb->where($baseWhere);
            if (!empty($filter['SearchAllData'])) {
                $s = $filter['SearchAllData'];
                $this->ReadDb->group_start();
                $this->ReadDb->or_like('Customers.Name', $s, 'both');
                $this->ReadDb->or_like('Customers.Area', $s, 'both');
                $this->ReadDb->or_like('Customers.MobileNumber', $s, 'both');
                $this->ReadDb->or_like('Customers.ContactPerson', $s, 'both');
                $this->ReadDb->group_end();
            }
            if (!empty($filter['Tags'])) {
                $tags = is_array($filter['Tags']) ? $filter['Tags'] : [$filter['Tags']];
                $this->ReadDb->group_start();
                foreach ($tags as $tag) { $this->ReadDb->or_like('Customers.Tags', $tag, 'both'); }
                $this->ReadDb->group_end();
            }
            if (!empty($filter['CustomerTypeUIDs'])) {
                $typeUIDs = array_filter(array_map('intval', (array)$filter['CustomerTypeUIDs']));
                if (!empty($typeUIDs)) $this->ReadDb->where_in('Customers.CustomerTypeUID', $typeUIDs);
            }
            if ($balanceSubquery) $this->ReadDb->where($balanceSubquery, null, false);
            $cntQuery = $this->ReadDb->get();
            if (!$cntQuery) throw new Exception($this->ReadDb->error()['message'] ?? 'DB error');
            $totalCount = (int) $cntQuery->row()->cnt;

            // Data query
            $this->ReadDb->select([
                'Customers.CustomerUID AS TablePrimaryUID',
                'Customers.CustomerUID AS CustomerUID',
                'Customers.OrgUID AS OrgUID',
                'Customers.SalutationUID AS SalutationUID',
                'Sal.SalutationName AS SalutationName',
                'Customers.Name AS Name',
                'Customers.Area AS Area',
                'Customers.CountryISO2 AS CountryISO2',
                'Customers.CountryCode AS CountryCode',
                'Customers.MobileNumber AS MobileNumber',
                'Customers.EmailAddress AS EmailAddress',
                'Customers.GSTIN AS GSTIN',
                'Customers.CompanyName AS CompanyName',
                'Customers.DebitCreditType AS DebitCreditType',
                'Customers.DebitCreditAmount AS DebitCreditAmount',
                'Customers.Image AS Image',
                'Customers.PANNumber AS PANNumber',
                'Customers.ContactPerson AS ContactPerson',
                'Customers.DateOfBirth AS DateOfBirth',
                'Customers.DiscountPercent AS DiscountPercent',
                'Customers.CreditPeriod AS CreditPeriod',
                'Customers.CreditLimit AS CreditLimit',
                'Customers.Notes AS Notes',
                'Customers.Tags AS Tags',
                'Customers.CCEmails AS CCEmails',
                'Customers.CustomerTypeUID AS CustomerTypeUID',
                'Customers.IsActive AS IsActive',
                'Customers.CreatedOn AS CreatedOn',
                'Customers.UpdatedOn AS UpdatedOn',
                "CONCAT(User.FirstName, ' ', User.LastName) AS UpdatedBy",
                'IFNULL(COB.PendingBalance, 0.00) AS ClosingBalance',
                "IFNULL(COB.PendingBalType, 'Debit') AS ClosingBalanceType",
                'CT.TypeName AS CustomerTypeName',
                'ShipAddr.Line1 AS ShipLine1',
                'ShipAddr.Line2 AS ShipLine2',
                'ShipAddr.CityText AS ShipCity',
                'ShipAddr.StateText AS ShipState',
                'ShipAddr.Pincode AS ShipPincode',
            ]);
            $this->ReadDb->from('Customers.CustomerTbl as Customers');
            $this->ReadDb->join('Users.UserTbl as User', 'User.UserUID = Customers.UpdatedBy', 'left');
            $this->ReadDb->join(
                'Accounting.EntityLedgerMap as ELM',
                "ELM.CustomerUID = Customers.CustomerUID AND ELM.EntityType = 'Customer' AND ELM.IsDeleted = 0",
                'left'
            );
            $this->ReadDb->join(
                'Accounting.ChartOfAccounts as COA',
                'COA.LedgerUID = ELM.LedgerUID AND COA.IsDeleted = 0',
                'left'
            );
            $this->ReadDb->join(
                'Customers.CustomerTypeTbl as CT',
                'CT.CustomerTypeUID = Customers.CustomerTypeUID AND CT.IsDeleted = 0',
                'left'
            );
            $this->ReadDb->join(
                'Customers.CustAddressTbl as ShipAddr',
                "ShipAddr.CustomerUID = Customers.CustomerUID AND ShipAddr.AddressType = 'Shipping' AND ShipAddr.IsDeleted = 0 AND ShipAddr.IsActive = 1",
                'left'
            );
            $this->ReadDb->join(
                'Customers.CustOpeningBalanceTbl as COB',
                'COB.CustomerUID = Customers.CustomerUID AND COB.OrgUID = Customers.OrgUID AND COB.IsDeleted = 0',
                'left'
            );
            $this->ReadDb->join(
                'Global.SalutationTbl as Sal',
                'Sal.SalutationUID = Customers.SalutationUID AND Sal.IsDeleted = 0',
                'left'
            );
            $this->ReadDb->where($baseWhere);
            if (!empty($filter['SearchAllData'])) {
                $s = $filter['SearchAllData'];
                $this->ReadDb->group_start();
                $this->ReadDb->or_like('Customers.Name', $s, 'both');
                $this->ReadDb->or_like('Customers.Area', $s, 'both');
                $this->ReadDb->or_like('Customers.MobileNumber', $s, 'both');
                $this->ReadDb->or_like('Customers.ContactPerson', $s, 'both');
                $this->ReadDb->group_end();
            }
            if (!empty($filter['Tags'])) {
                $tags = is_array($filter['Tags']) ? $filter['Tags'] : [$filter['Tags']];
                $this->ReadDb->group_start();
                foreach ($tags as $tag) {
                    $this->ReadDb->or_like('Customers.Tags', $tag, 'both');
                }
                $this->ReadDb->group_end();
            }
            if (!empty($filter['CustomerTypeUIDs'])) {
                $typeUIDs = array_filter(array_map('intval', (array)$filter['CustomerTypeUIDs']));
                if (!empty($typeUIDs)) $this->ReadDb->where_in('Customers.CustomerTypeUID', $typeUIDs);
            }
            if (!empty($filter['UpdatedByUIDs'])) {
                $uids = array_filter(array_map('intval', (array)$filter['UpdatedByUIDs']));
                if (!empty($uids)) $this->ReadDb->where_in('Customers.UpdatedBy', $uids);
            }
            if ($balanceSubquery) $this->ReadDb->where($balanceSubquery, null, false);
            if (!empty($filter['NameSorting'])) {
                $this->ReadDb->order_by('Customers.Name', (int)$filter['NameSorting'] === 1 ? 'ASC' : 'DESC');
            } elseif (!empty($filter['AreaSorting'])) {
                $this->ReadDb->order_by('Customers.Area', (int)$filter['AreaSorting'] === 1 ? 'ASC' : 'DESC');
            } elseif (!empty($filter['BalanceSorting'])) {
                $this->ReadDb->order_by('ClosingBalance', (int)$filter['BalanceSorting'] === 1 ? 'ASC' : 'DESC');
            } else {
                $this->ReadDb->order_by('Customers.CustomerUID', 'DESC');
            }
            if ($limit > 0) {
                $this->ReadDb->limit($limit, $offset);
            }
            $dataQuery = $this->ReadDb->get();
            if (!$dataQuery) throw new Exception($this->ReadDb->error()['message'] ?? 'DB error');

            $rows = $dataQuery->result();

            // Batch-fetch first attachment per customer for thumbnail in list view
            if (!empty($rows)) {
                $custUIDs = array_column((array)$rows, 'CustomerUID');
                $cdnUrl   = rtrim(getenv('FILE_UPLOAD') == 'amazonaws' ? getenv('CDN_URL') : getenv('CFLARE_R2_CDN'), '/');
                $ph       = implode(',', array_fill(0, count($custUIDs), '?'));
                $attQ     = $this->ReadDb->query(
                    "SELECT CustomerUID, FilePath, FileName FROM Customers.CustomerAttachmentsTbl
                      WHERE CustomerUID IN ({$ph}) AND IsDeleted = 0
                      ORDER BY CustomerUID, SortOrder ASC",
                    $custUIDs
                );
                $attMap = [];
                if ($attQ) {
                    foreach ($attQ->result() as $att) {
                        $uid = (int)$att->CustomerUID;
                        if (!isset($attMap[$uid])) {
                            $attMap[$uid] = ['url' => $cdnUrl . '/' . ltrim($att->FilePath, '/'), 'name' => $att->FileName];
                        }
                    }
                }
                // Full gallery per customer for data-images attribute
                $galleryMap = [];
                if ($attQ) {
                    $this->ReadDb->query("SELECT 1"); // reset
                    $attQ2 = $this->ReadDb->query(
                        "SELECT CustomerUID, FilePath, FileName FROM Customers.CustomerAttachmentsTbl
                          WHERE CustomerUID IN ({$ph}) AND IsDeleted = 0
                          ORDER BY CustomerUID, SortOrder ASC",
                        $custUIDs
                    );
                    if ($attQ2) {
                        foreach ($attQ2->result() as $att) {
                            $uid = (int)$att->CustomerUID;
                            $galleryMap[$uid][] = ['url' => $cdnUrl . '/' . ltrim($att->FilePath, '/'), 'name' => $att->FileName];
                        }
                    }
                }
                foreach ($rows as $row) {
                    $uid = (int)$row->CustomerUID;
                    $row->PrimaryImageUrl  = isset($attMap[$uid]) ? $attMap[$uid]['url'] : null;
                    $row->AttachmentsJson  = json_encode($galleryMap[$uid] ?? [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                }
            }

            $result             = new stdClass();
            $result->rows       = $rows;
            $result->totalCount = $totalCount;
            return $result;

        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

    }

    public function getCustomerTypeList($OrgUID) {

        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select([
                'CT.CustomerTypeUID AS CustomerTypeUID',
                'CT.TypeName AS TypeName',
                'CT.IsDefault AS IsDefault',
            ]);
            $this->ReadDb->from('Customers.CustomerTypeTbl as CT');
            $this->ReadDb->where([
                'CT.OrgUID'     => (int) $OrgUID,
                'CT.IsDeleted'  => 0,
                'CT.IsActive'   => 1,
            ]);
            $this->ReadDb->order_by('CT.CustomerTypeUID', 'ASC');
            $query = $this->ReadDb->get();
            $error = $this->ReadDb->error();
            if ($error['code']) throw new Exception($error['message']);
            return $query->result();
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

    }

    public function getCustomerTags($OrgUID) {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select('Customers.Tags AS Tags');
            $this->ReadDb->from('Customers.CustomerTbl as Customers');
            $this->ReadDb->where(['Customers.OrgUID' => (int)$OrgUID, 'Customers.IsDeleted' => 0, 'Customers.IsActive' => 1]);
            $this->ReadDb->where('Customers.Tags IS NOT NULL');
            $this->ReadDb->where('Customers.Tags !=', '');
            $query = $this->ReadDb->get();
            if (!$query) throw new Exception($this->ReadDb->error()['message'] ?? 'DB error');
            $tags = [];
            foreach ($query->result() as $row) {
                foreach (explode(',', $row->Tags) as $t) {
                    $t = trim($t);
                    if ($t !== '') $tags[$t] = true;
                }
            }
            return array_keys($tags);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    // ── Balance recalculation queries ─────────────────────────────────────────

    public function getCustomersWithLedgerForBalance($orgUID, $customerUID = 0) {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select([
                'C.CustomerUID',
                'IFNULL(COB.OpeningBalance, 0.00)   AS OpeningBalance',
                "IFNULL(COB.OpeningBalType, 'Debit') AS OpeningBalType",
                'ELM.LedgerUID',
                'COA.CurrentBalance     AS LedgerCurrentBalance',
                'COA.CurrentBalanceType AS LedgerCurrentType',
            ]);
            $this->ReadDb->from('Customers.CustomerTbl C');
            $this->ReadDb->join(
                'Customers.CustOpeningBalanceTbl COB',
                'COB.CustomerUID = C.CustomerUID AND COB.IsDeleted = 0',
                'left'
            );
            $this->ReadDb->join(
                'Accounting.EntityLedgerMap ELM',
                "ELM.CustomerUID = C.CustomerUID AND ELM.EntityType = 'Customer' AND ELM.IsDeleted = 0",
                'left'
            );
            $this->ReadDb->join(
                'Accounting.ChartOfAccounts COA',
                'COA.LedgerUID = ELM.LedgerUID AND COA.IsDeleted = 0',
                'left'
            );
            $this->ReadDb->where(['C.OrgUID' => (int)$orgUID, 'C.IsDeleted' => 0, 'C.IsActive' => 1]);
            if ($customerUID > 0) {
                $this->ReadDb->where('C.CustomerUID', (int)$customerUID);
            }
            $query = $this->ReadDb->get();
            if (!$query) throw new Exception($this->ReadDb->error()['message'] ?? 'DB error');
            return $query->result();
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function getCustomerTotalInvoiced($orgUID, $customerUID) {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select('COALESCE(SUM(NetAmount), 0) AS total');
            $this->ReadDb->from('`Transaction`.TransactionsTbl');
            $this->ReadDb->where([
                'OrgUID'    => (int)$orgUID,
                'PartyUID'  => (int)$customerUID,
                'PartyType' => 'C',
                'ModuleUID' => 103,
                'IsDeleted' => 0,
            ]);
            // Draft invoices do not affect customer balance — exclude along with Cancelled/Rejected
            $this->ReadDb->where_not_in('DocStatus', ['Draft', 'Cancelled', 'Rejected']);
            $query = $this->ReadDb->get();
            if (!$query) throw new Exception($this->ReadDb->error()['message'] ?? 'DB error');
            return (float) $query->row()->total;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function getCustomerTotalReceived($orgUID, $customerUID) {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select('COALESCE(SUM(Amount), 0) AS total');
            $this->ReadDb->from('`Transaction`.PaymentsTbl');
            $this->ReadDb->where([
                'OrgUID'                       => (int)$orgUID,
                'PartyUID'                     => (int)$customerUID,
                'PartyType'                    => 'C',
                'PaymentDirection'             => 'In',
                'IsDeleted'                    => 0,
                'IsTransferredToCreditNote'    => 0,   // exclude payments moved to credit note
                'IsCancelled'                  => 0,   // exclude voided/reversed payments
            ]);
            $query = $this->ReadDb->get();
            if (!$query) throw new Exception($this->ReadDb->error()['message'] ?? 'DB error');
            return (float) $query->row()->total;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function getCustomerTotalReturned($orgUID, $customerUID) {
        try {
            $this->ReadDb->db_debug = FALSE;
            // Use BalanceAmount (outstanding after partial refunds) so that paying back 100 of a 500
            // return correctly reduces the outstanding liability to 400, not the full 500.
            $this->ReadDb->select('COALESCE(SUM(COALESCE(BalanceAmount, NetAmount)), 0) AS total');
            $this->ReadDb->from('`Transaction`.TransactionsTbl');
            $this->ReadDb->where([
                'OrgUID'    => (int)$orgUID,
                'PartyUID'  => (int)$customerUID,
                'PartyType' => 'C',
                'IsDeleted' => 0,
            ]);
            $this->ReadDb->where_in('ModuleUID', [106, 107]);
            $this->ReadDb->where_not_in('DocStatus', ['Cancelled', 'Rejected']);
            $query = $this->ReadDb->get();
            if (!$query) throw new Exception($this->ReadDb->error()['message'] ?? 'DB error');
            return (float) $query->row()->total;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function getCustomerSRCoveredByCreditNote($orgUID, $customerUID) {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select('COALESCE(SUM(COALESCE(T.BalanceAmount, T.NetAmount)), 0) AS total');
            $this->ReadDb->from('`Transaction`.TransactionsTbl T');
            $this->ReadDb->join(
                '`Transaction`.TransCreditNoteTbl CN',
                'CN.SourceTransUID = T.TransUID AND CN.SourceModuleUID = 106 AND CN.IsDeleted = 0 AND CN.OrgUID = ' . (int)$orgUID,
                'inner'
            );
            $this->ReadDb->where([
                'T.OrgUID'    => (int)$orgUID,
                'T.PartyUID'  => (int)$customerUID,
                'T.PartyType' => 'C',
                'T.IsDeleted' => 0,
                'T.ModuleUID' => 106,
            ]);
            $this->ReadDb->where_not_in('T.DocStatus', ['Cancelled', 'Rejected']);
            $this->ReadDb->where('CN.IsCancelled', 0);
            $query = $this->ReadDb->get();
            if (!$query) throw new Exception($this->ReadDb->error()['message'] ?? 'DB error');
            return (float)$query->row()->total;
        } catch (Exception $e) {
            log_message('error', 'Customers_model::getCustomerSRCoveredByCreditNote failed: ' . $e->getMessage());
            return 0.0;
        }
    }

    public function updateCustomerBalanceInLedger($ledgerUID, $balance, $balanceType, $userUID) {
        try {
            $this->WriteDb->db_debug = FALSE;
            $this->WriteDb->where('LedgerUID', (int)$ledgerUID);
            $res = $this->WriteDb->update('Accounting.ChartOfAccounts', [
                'CurrentBalance'     => $balance,
                'CurrentBalanceType' => $balanceType,
                'UpdatedBy'          => (int)$userUID,
            ]);
            if ($res === false) throw new Exception('Ledger update failed.');
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function updateCustomerBalanceInCustomerTbl($customerUID, $balance, $balanceType, $userUID) {
        try {
            $this->WriteDb->db_debug = FALSE;
            $this->WriteDb->where('CustomerUID', (int)$customerUID);
            $res = $this->WriteDb->update('Customers.CustomerTbl', [
                'DebitCreditAmount' => $balance,
                'DebitCreditType'   => $balanceType,
                'UpdatedBy'         => (int)$userUID,
            ]);
            if ($res === false) throw new Exception('Customer balance update failed.');
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }


    // â”€â”€ CustOpeningBalanceTbl (one row per customer, no year) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    public function getCustomerOpeningBalance($orgUID, $customerUID) {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select([
                'OpeningBalUID', 'OpeningBalance', 'OpeningBalType',
                'PendingBalance', 'PendingBalType', 'Notes',
            ]);
            $this->ReadDb->from('Customers.CustOpeningBalanceTbl');
            $this->ReadDb->where([
                'OrgUID'      => (int)$orgUID,
                'CustomerUID' => (int)$customerUID,
                'IsDeleted'   => 0,
            ]);
            $this->ReadDb->limit(1);
            $query = $this->ReadDb->get();
            if (!$query) throw new Exception($this->ReadDb->error()['message'] ?? 'DB error');
            return $query->row();
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function saveCustomerOpeningBalance($orgUID, $customerUID, $openingBalance, $openingBalType, $notes, $userUID, $isNew = false) {
        try {
            if (!$isNew) {
                $this->ReadDb->db_debug = FALSE;
                $this->ReadDb->select('OpeningBalUID');
                $this->ReadDb->from('Customers.CustOpeningBalanceTbl');
                $this->ReadDb->where(['OrgUID' => (int)$orgUID, 'CustomerUID' => (int)$customerUID, 'IsDeleted' => 0]);
                $existing = $this->ReadDb->get()->row();

                if ($existing) {
                    $this->WriteDb->db_debug = FALSE;
                    $this->WriteDb->where('OpeningBalUID', (int)$existing->OpeningBalUID);
                    $this->WriteDb->update('Customers.CustOpeningBalanceTbl', [
                        'OpeningBalance' => (float)$openingBalance,
                        'OpeningBalType' => $openingBalType,
                        'Notes'          => $notes ?: NULL,
                        'UpdatedBy'      => (int)$userUID,
                    ]);
                    return (int)$existing->OpeningBalUID;
                }
            }

            // CustomerUID was inserted in the caller's open transaction (different connection).
            // FK check would wait 50s for the uncommitted row → disable for this insert only.
            $this->WriteDb->db_debug = FALSE;
            $this->WriteDb->query('SET FOREIGN_KEY_CHECKS = 0');
            $this->WriteDb->insert('Customers.CustOpeningBalanceTbl', [
                'OrgUID'         => (int)$orgUID,
                'CustomerUID'    => (int)$customerUID,
                'OpeningBalance' => (float)$openingBalance,
                'OpeningBalType' => $openingBalType,
                'PendingBalance' => (float)$openingBalance,
                'PendingBalType' => $openingBalType,
                'Notes'          => $notes ?: NULL,
                'IsActive'       => 1,
                'IsDeleted'      => 0,
                'CreatedBy'      => (int)$userUID,
                'UpdatedBy'      => (int)$userUID,
            ]);
            $this->WriteDb->query('SET FOREIGN_KEY_CHECKS = 1');
            return (int)$this->WriteDb->insert_id();

        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function updateCustomerPendingBalance($orgUID, $customerUID, $pendingBalance, $pendingBalType, $userUID) {
        try {
            $this->WriteDb->db_debug = FALSE;
            // UPSERT: inserts if no row exists, updates if it does (handles "no change" case without duplicate key error)
            $this->WriteDb->query(
                "INSERT INTO Customers.CustOpeningBalanceTbl
                    (OrgUID, CustomerUID, OpeningBalance, OpeningBalType, PendingBalance, PendingBalType, IsActive, IsDeleted, CreatedBy, UpdatedBy)
                 VALUES (?, ?, 0.00, 'Debit', ?, ?, 1, 0, ?, ?)
                 ON DUPLICATE KEY UPDATE
                    PendingBalance = VALUES(PendingBalance),
                    PendingBalType = VALUES(PendingBalType),
                    UpdatedBy      = VALUES(UpdatedBy)",
                [
                    (int)$orgUID,
                    (int)$customerUID,
                    (float)$pendingBalance,
                    $pendingBalType,
                    (int)$userUID,
                    (int)$userUID,
                ]
            );
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function getCustomerOpeningBalanceSigned($orgUID, $customerUID) {
        // Returns opening balance as signed float: Debit=+, Credit=-
        $row = $this->getCustomerOpeningBalance($orgUID, $customerUID);
        if (!$row) return 0.0;
        $amt = (float)$row->OpeningBalance;
        return ($row->OpeningBalType === 'Debit') ? $amt : -$amt;
    }

    public function getCustomerDebitCreditRaw($customerUID) {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select(['Name', 'DebitCreditAmount', 'DebitCreditType']);
            $this->ReadDb->from('Customers.CustomerTbl');
            $this->ReadDb->where(['CustomerUID' => (int)$customerUID, 'IsDeleted' => 0]);
            $this->ReadDb->limit(1);
            return $this->ReadDb->get()->row();
        } catch (Exception $e) {
            return null;
        }
    }

    // Applies a signed numeric delta (+/-) to the customer running opening balance.
    // Returns ['balance' => float, 'type' => 'Debit'|'Credit'].
    // ── CustYearOpeningBalanceTbl (year-wise opening balance snapshot) ─────────

    // $onlyIfNew=true: insert-only, preserving the year-start snapshot.
    public function saveCustomerYearOpening($orgUID, $customerUID, $financialYear, $openingBalance, $openingBalType, $userUID, $onlyIfNew = false, $isNew = false) {
        try {
            if (!$isNew) {
                $this->ReadDb->db_debug = FALSE;
                $this->ReadDb->select('YearBalUID');
                $this->ReadDb->from('Customers.CustYearOpeningBalanceTbl');
                $this->ReadDb->where([
                    'OrgUID'        => (int)$orgUID,
                    'CustomerUID'   => (int)$customerUID,
                    'FinancialYear' => (int)$financialYear,
                    'IsDeleted'     => 0,
                ]);
                $existing = $this->ReadDb->get()->row();

                if ($existing) {
                    if ($onlyIfNew) return (int)$existing->YearBalUID;
                    $this->WriteDb->db_debug = FALSE;
                    $this->WriteDb->where('YearBalUID', (int)$existing->YearBalUID);
                    $this->WriteDb->update('Customers.CustYearOpeningBalanceTbl', [
                        'OpeningBalance' => (float)$openingBalance,
                        'OpeningBalType' => $openingBalType,
                        'UpdatedBy'      => (int)$userUID,
                    ]);
                    return (int)$existing->YearBalUID;
                }
            }

            $this->WriteDb->db_debug = FALSE;
            $this->WriteDb->query('SET FOREIGN_KEY_CHECKS = 0');
            $this->WriteDb->insert('Customers.CustYearOpeningBalanceTbl', [
                'OrgUID'         => (int)$orgUID,
                'CustomerUID'    => (int)$customerUID,
                'FinancialYear'  => (int)$financialYear,
                'OpeningBalance' => (float)$openingBalance,
                'OpeningBalType' => $openingBalType,
                'IsActive'       => 1,
                'IsDeleted'      => 0,
                'CreatedBy'      => (int)$userUID,
                'UpdatedBy'      => (int)$userUID,
            ]);
            $this->WriteDb->query('SET FOREIGN_KEY_CHECKS = 1');
            return (int)$this->WriteDb->insert_id();
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function getCustomerYearOpening($orgUID, $customerUID, $financialYear) {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select(['YearBalUID', 'FinancialYear', 'OpeningBalance', 'OpeningBalType']);
            $this->ReadDb->from('Customers.CustYearOpeningBalanceTbl');
            $this->ReadDb->where([
                'OrgUID'        => (int)$orgUID,
                'CustomerUID'   => (int)$customerUID,
                'FinancialYear' => (int)$financialYear,
                'IsDeleted'     => 0,
            ]);
            $this->ReadDb->limit(1);
            $query = $this->ReadDb->get();
            if (!$query) throw new Exception($this->ReadDb->error()['message'] ?? 'DB error');
            return $query->row();
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    // â”€â”€ CustBalanceHistoryTbl (year-wise snapshots) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€


    public function getCustomerOnAccountPayments($orgUID, $customerUID) {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select('P.PaymentUID, P.Amount, P.CreatedOn, P.Notes,
                T.UniqueNumber AS SourceInvoiceNumber, T.NetAmount AS SourceInvoiceAmount,
                T.TransDate AS SourceInvoiceDate');
            $this->ReadDb->from('Transaction.PaymentsTbl P');
            $this->ReadDb->join('Transaction.TransactionsTbl T', 'T.TransUID = P.TransUID', 'left');
            $this->ReadDb->where([
                'P.OrgUID'           => (int)$orgUID,
                'P.PartyUID'         => (int)$customerUID,
                'P.PartyType'        => 'C',
                'P.PaymentDirection' => 'In',
                'P.IsOnAccount'      => 1,
                'P.IsDeleted'        => 0,
                'P.IsCancelled'      => 0,
            ]);
            // FIFO — oldest first
            $this->ReadDb->order_by('P.CreatedOn', 'ASC');
            $result = $this->ReadDb->get()->result_array();
            return $result ?: [];
        } catch (Exception $e) {
            return [];
        }
    }

    // ══════════════════════════════════════════════════════════════════
    // Customer Group methods
    // ══════════════════════════════════════════════════════════════════

    public function getGroupTypes($module = 'customers') {
        try {
            $query = $this->ReadDb->query(
                "SELECT TypeName FROM Global.GroupTypesTbl WHERE Module=? AND IsActive=1 ORDER BY SortOrder",
                [$module]
            );
            $rows = $query ? $query->result_array() : [];
            if ($rows) return array_column($rows, 'TypeName');
        } catch (Exception $e) {}
        return ['Business Group', 'Branch Group', 'Family Group',
                'Corporate Group', 'Dealer Network', 'Franchise Group', 'Custom'];
    }

    public function getGroupListPaginated($orgUID, $limit, $offset, $filter = []) {
        try {
            $this->ReadDb->db_debug = false;

            // ── Count (no joins needed; all filters are on CG) ──
            $this->ReadDb->select('COUNT(*) AS cnt', false);
            $this->ReadDb->from('Customers.CustomerGroupTbl CG');
            $this->ReadDb->where(['CG.OrgUID' => (int)$orgUID, 'CG.IsDeleted' => 0]);
            if (!empty($filter['SearchAllData'])) {
                $s = $filter['SearchAllData'];
                $this->ReadDb->group_start();
                $this->ReadDb->like('CG.GroupName', $s);
                $this->ReadDb->or_like('CG.GroupCode', $s);
                $this->ReadDb->or_like('CG.GroupType', $s);
                $this->ReadDb->or_like('CG.Mobile', $s);
                $this->ReadDb->group_end();
            }
            if (isset($filter['IsActive']) && $filter['IsActive'] !== '') {
                $this->ReadDb->where('CG.IsActive', (int)$filter['IsActive']);
            }
            if (!empty($filter['GroupType'])) {
                $this->ReadDb->where_in('CG.GroupType', (array)$filter['GroupType']);
            }
            $countRow   = $this->ReadDb->get()->row();
            $totalCount = (int)($countRow->cnt ?? 0);

            // ── Data ──
            $this->ReadDb->select(
                'CG.GroupUID, CG.GroupCode, CG.GroupName, CG.GroupType,
                 CG.ContactPerson, CG.Mobile, CG.Email, CG.IsActive, CG.CreatedOn,
                 COUNT(C.CustomerUID) AS MemberCount,
                 COALESCE(SUM(CASE WHEN COB.PendingBalType = \'Debit\'  AND COB.PendingBalance > 0 THEN COB.PendingBalance ELSE 0 END), 0) AS TotalReceivable,
                 COALESCE(SUM(CASE WHEN COB.PendingBalType = \'Credit\' AND COB.PendingBalance > 0 THEN COB.PendingBalance ELSE 0 END), 0) AS TotalPayable,
                 MAX(CASE WHEN C.IsGroupPrimary = 1 THEN C.Name ELSE NULL END) AS PrimaryName',
                false
            );
            $this->ReadDb->from('Customers.CustomerGroupTbl CG');
            $this->ReadDb->join('Customers.CustomerTbl C', 'C.GroupUID = CG.GroupUID AND C.IsDeleted = 0', 'left');
            $this->ReadDb->join('Customers.CustOpeningBalanceTbl COB', 'COB.CustomerUID = C.CustomerUID AND COB.OrgUID = C.OrgUID AND COB.IsDeleted = 0', 'left');
            $this->ReadDb->where(['CG.OrgUID' => (int)$orgUID, 'CG.IsDeleted' => 0]);
            if (!empty($filter['SearchAllData'])) {
                $s = $filter['SearchAllData'];
                $this->ReadDb->group_start();
                $this->ReadDb->like('CG.GroupName', $s);
                $this->ReadDb->or_like('CG.GroupCode', $s);
                $this->ReadDb->or_like('CG.GroupType', $s);
                $this->ReadDb->or_like('CG.Mobile', $s);
                $this->ReadDb->group_end();
            }
            if (isset($filter['IsActive']) && $filter['IsActive'] !== '') {
                $this->ReadDb->where('CG.IsActive', (int)$filter['IsActive']);
            }
            if (!empty($filter['GroupType'])) {
                $this->ReadDb->where_in('CG.GroupType', (array)$filter['GroupType']);
            }
            $this->ReadDb->group_by('CG.GroupUID');
            $this->ReadDb->order_by('CG.GroupName', 'ASC');
            $this->ReadDb->limit($limit, $offset);
            $query  = $this->ReadDb->get();

            $result             = new stdClass();
            $result->rows       = $query ? $query->result() : [];
            $result->totalCount = $totalCount;
            return $result;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function getGroupStats($orgUID) {
        try {
            $this->ReadDb->db_debug = false;
            $query = $this->ReadDb->query(
                "SELECT COUNT(*) AS TotalCount, SUM(IsActive=1) AS ActiveCount, SUM(IsActive=0) AS InactiveCount,
                        (SELECT COUNT(*) FROM Customers.CustomerTbl WHERE OrgUID=? AND GroupUID IS NOT NULL AND IsDeleted=0) AS TotalMembers
                 FROM Customers.CustomerGroupTbl WHERE OrgUID=? AND IsDeleted=0",
                [(int)$orgUID, (int)$orgUID]
            );
            return $query ? $query->row() : new stdClass();
        } catch (Exception $e) {
            return new stdClass();
        }
    }

    public function getGroupByUID($orgUID, $groupUID) {
        try {
            $this->ReadDb->db_debug = false;
            $this->ReadDb->select('CG.*');
            $this->ReadDb->from('Customers.CustomerGroupTbl CG');
            $this->ReadDb->where(['CG.OrgUID' => (int)$orgUID, 'CG.GroupUID' => (int)$groupUID, 'CG.IsDeleted' => 0]);
            $query = $this->ReadDb->get();
            return $query ? $query->row() : null;
        } catch (Exception $e) {
            return null;
        }
    }

    public function getGroupMembers($orgUID, $groupUID) {
        try {
            $this->ReadDb->db_debug = false;
            $this->ReadDb->select([
                'C.CustomerUID', 'C.Name', 'C.Area', 'C.MobileNumber', 'C.IsGroupPrimary',
                "IFNULL(COB.PendingBalance, 0)       AS Balance",
                "IFNULL(COB.PendingBalType, 'Debit') AS BalanceType",
            ]);
            $this->ReadDb->from('Customers.CustomerTbl C');
            $this->ReadDb->join('Customers.CustOpeningBalanceTbl COB', 'COB.CustomerUID = C.CustomerUID AND COB.OrgUID = C.OrgUID AND COB.IsDeleted = 0', 'left');
            $this->ReadDb->where(['C.OrgUID' => (int)$orgUID, 'C.GroupUID' => (int)$groupUID, 'C.IsDeleted' => 0]);
            $this->ReadDb->order_by('C.IsGroupPrimary', 'DESC');
            $this->ReadDb->order_by('C.Name', 'ASC');
            $query = $this->ReadDb->get();
            return $query ? $query->result() : [];
        } catch (Exception $e) {
            return [];
        }
    }

    public function getGroupOverview($orgUID, $groupUID) {
        try {
            $this->ReadDb->db_debug = false;
            $query = $this->ReadDb->query(
                "SELECT COUNT(C.CustomerUID) AS MemberCount,
                        COALESCE(SUM(CASE WHEN COB.PendingBalType='Debit'  THEN COB.PendingBalance END),0) AS TotalReceivable,
                        COALESCE(SUM(CASE WHEN COB.PendingBalType='Credit' THEN COB.PendingBalance END),0) AS TotalPayable
                 FROM Customers.CustomerTbl C
                 LEFT JOIN Customers.CustOpeningBalanceTbl COB ON COB.CustomerUID=C.CustomerUID AND COB.OrgUID=C.OrgUID AND COB.IsDeleted=0
                 WHERE C.GroupUID=? AND C.OrgUID=? AND C.IsDeleted=0",
                [(int)$groupUID, (int)$orgUID]
            );
            return $query ? $query->row() : new stdClass();
        } catch (Exception $e) {
            return new stdClass();
        }
    }

    public function getGroupOutstanding($orgUID, $groupUID) {
        try {
            $this->ReadDb->db_debug = false;
            $query = $this->ReadDb->query(
                "SELECT C.CustomerUID, C.Name, C.Area, C.MobileNumber, C.IsGroupPrimary,
                        IFNULL(COB.PendingBalance,0)       AS Balance,
                        IFNULL(COB.PendingBalType,'Debit') AS BalanceType
                 FROM Customers.CustomerTbl C
                 LEFT JOIN Customers.CustOpeningBalanceTbl COB ON COB.CustomerUID=C.CustomerUID AND COB.OrgUID=C.OrgUID AND COB.IsDeleted=0
                 WHERE C.GroupUID=? AND C.OrgUID=? AND C.IsDeleted=0
                 ORDER BY C.IsGroupPrimary DESC, C.Name ASC",
                [(int)$groupUID, (int)$orgUID]
            );
            return $query ? $query->result() : [];
        } catch (Exception $e) {
            return [];
        }
    }

    public function getActiveGroupsForDropdown($orgUID) {
        try {
            $this->ReadDb->db_debug = false;
            $this->ReadDb->select(['GroupUID', 'GroupName', 'GroupCode', 'GroupType']);
            $this->ReadDb->from('Customers.CustomerGroupTbl');
            $this->ReadDb->where(['OrgUID' => (int)$orgUID, 'IsActive' => 1, 'IsDeleted' => 0]);
            $this->ReadDb->order_by('GroupName', 'ASC');
            $query = $this->ReadDb->get();
            return $query ? $query->result() : [];
        } catch (Exception $e) {
            return [];
        }
    }

    public function assignGroupMembers($orgUID, $groupUID, array $memberUIDs, $primaryUID, $userUID) {
        if (empty($memberUIDs)) return;
        foreach ($memberUIDs as $custUID) {
            $this->WriteDb->where(['CustomerUID' => (int)$custUID, 'OrgUID' => (int)$orgUID]);
            $this->WriteDb->update('Customers.CustomerTbl', [
                'GroupUID'       => (int)$groupUID,
                'IsGroupPrimary' => ((int)$custUID === (int)$primaryUID) ? 1 : 0,
                'UpdatedBy'      => (int)$userUID,
            ]);
        }
    }

    public function syncGroupMembers($orgUID, $groupUID, array $newMemberUIDs, $primaryUID, $userUID) {
        $this->WriteDb->where('OrgUID', (int)$orgUID);
        $this->WriteDb->where('GroupUID', (int)$groupUID);
        $this->WriteDb->where('IsDeleted', 0);
        if (!empty($newMemberUIDs)) {
            $this->WriteDb->where_not_in('CustomerUID', array_map('intval', $newMemberUIDs));
        }
        $this->WriteDb->update('Customers.CustomerTbl', [
            'GroupUID' => null, 'IsGroupPrimary' => 0, 'UpdatedBy' => (int)$userUID,
        ]);
        if (!empty($newMemberUIDs)) {
            $this->assignGroupMembers($orgUID, $groupUID, $newMemberUIDs, $primaryUID, $userUID);
        }
    }

    public function unlinkAllGroupMembers($orgUID, $groupUID, $userUID) {
        $this->WriteDb->where(['OrgUID' => (int)$orgUID, 'GroupUID' => (int)$groupUID]);
        $this->WriteDb->update('Customers.CustomerTbl', [
            'GroupUID' => null, 'IsGroupPrimary' => 0, 'UpdatedBy' => (int)$userUID,
        ]);
    }

    // ── Customer Attachments ──────────────────────────────────────────────────

    public function getCustomerAttachments(int $customerUID, int $orgUID): array {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select('AttachUID, FileName, FilePath, FileSize, SortOrder, CreatedOn');
            $this->ReadDb->from('Customers.CustomerAttachmentsTbl');
            $this->ReadDb->where(['CustomerUID' => $customerUID, 'OrgUID' => $orgUID, 'IsDeleted' => 0]);
            $this->ReadDb->order_by('SortOrder', 'ASC');
            $query = $this->ReadDb->get();
            return $query ? $query->result_array() : [];
        } catch (Exception $e) {
            log_message('error', 'getCustomerAttachments failed: ' . $e->getMessage());
            return [];
        }
    }

    public function getCustomerPrimaryImage(int $customerUID, int $orgUID): ?string {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select('FilePath');
            $this->ReadDb->from('Customers.CustomerAttachmentsTbl');
            $this->ReadDb->where(['CustomerUID' => $customerUID, 'OrgUID' => $orgUID, 'IsDeleted' => 0]);
            $this->ReadDb->order_by('SortOrder', 'ASC');
            $this->ReadDb->limit(1);
            $row = $this->ReadDb->get()->row();
            return $row ? $row->FilePath : null;
        } catch (Exception $e) { return null; }
    }
}
