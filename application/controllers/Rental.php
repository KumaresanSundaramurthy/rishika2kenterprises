<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Rental extends MY_Controller {

    public  $pageData      = [];
    private $EndReturnData;
    private $pageModuleUID = 116;

    public function __construct() {
        parent::__construct();
        $this->load->model(['rental_model', 'dbwrite_model', 'transactions_model']);
        $this->load->helper('transaction');
    }

    // ── Main page ─────────────────────────────────────────────────────────────

    public function index() {
        if (!$this->_loadPageTitle($this->pageModuleUID)) {
            $this->load->view('common/module_error', $this->pageData);
            return;
        }
        try {
            $orgUID = (int) $this->pageData['JwtData']->Org->OrgUID;
            $GeneralSettings = $this->pageData['JwtData']->GenSettings ?? new stdClass();
            $limit = (int)($GeneralSettings->RowLimit ?? 10);

            $filter = ['Status' => 'All'];

            $listData   = $this->rental_model->getRentalList($orgUID, $filter, $limit, 0);
            $totalCount = $this->rental_model->getRentalCount($orgUID, $filter);
            $stats      = $this->rental_model->getRentalStats($orgUID);

            $this->pageData['ModRowData']    = $this->load->view('transactions/rental/list', [
                'DataLists'    => $listData,
                'SerialNumber' => 0,
                'JwtData'      => $this->pageData['JwtData'],
            ], TRUE);
            $this->pageData['ModPagination'] = $this->globalservice->buildPagePaginationHtml('/rental/getPageDetails', $totalCount, 1, $limit);
            $this->pageData['ModAllCount']   = $totalCount;
            $this->pageData['Stats']         = $stats;
            $this->pageData['PaymentTypes']  = $this->rental_model->getPaymentTypes();
            $this->pageData['BankAccounts']  = $this->rental_model->getBankAccounts($orgUID);

            $this->load->view('transactions/rental/view', $this->pageData);

        } catch (Throwable $e) {
            log_message('error', 'Rental::index — ' . $e->getMessage());
            redirect('dashboard', 'refresh');
        }
    }

    // ── AJAX pagination ───────────────────────────────────────────────────────

    public function getPageDetails($pageNo = 1) {
        $this->EndReturnData = new stdClass();
        try {
            $pageNo = max(1, (int)$pageNo);
            $limit  = (int)($this->input->post('RowLimit') ?: 10);
            $offset = ($pageNo - 1) * $limit;
            $filter = $this->input->post('Filter') ?: ['Status' => 'All'];
            if (is_string($filter)) {
                $filter = json_decode($filter, true) ?: ['Status' => 'All'];
            }

            $orgUID = (int) $this->pageData['JwtData']->Org->OrgUID;

            $listData   = $this->rental_model->getRentalList($orgUID, $filter, $limit, $offset);
            $totalCount = $this->rental_model->getRentalCount($orgUID, $filter);

            $rowHtml = $this->load->view('transactions/rental/list', [
                'DataLists'    => $listData,
                'SerialNumber' => $offset,
                'JwtData'      => $this->pageData['JwtData'],
            ], TRUE);

            $this->EndReturnData->Error          = FALSE;
            $this->EndReturnData->RecordHtmlData = $rowHtml;
            $this->EndReturnData->Pagination     = $this->globalservice->buildPagePaginationHtml('/rental/getPageDetails', $totalCount, $pageNo, $limit);
            $this->EndReturnData->TotalCount     = $totalCount;
            $this->EndReturnData->Stats          = $this->rental_model->getRentalStats($orgUID);

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── Create rental ─────────────────────────────────────────────────────────

    public function createRental() {
        $this->EndReturnData = new stdClass();
        try {
            $orgUID  = (int) $this->pageData['JwtData']->Org->OrgUID;
            $userUID = (int) $this->pageData['JwtData']->User->UserUID;

            $PostData = $this->input->post();

            $customerUID      = (int)   getPostValue($PostData, 'CustomerUID');
            $startDateTime    =         getPostValue($PostData, 'RentalStartDateTime') ?: date('Y-m-d H:i:s');
            $returnDue        =         getPostValue($PostData, 'ReturnDueDateTime');
            $depositCollected = (float) getPostValue($PostData, 'DepositCollected');
            $depositPayType   = (int)   getPostValue($PostData, 'DepositPaymentTypeUID') ?: null;
            $depositBankUID   = (int)   getPostValue($PostData, 'DepositBankAccountUID') ?: null;
            $notes            =         getPostValue($PostData, 'Notes') ?: null;

            $itemsJson = getPostValue($PostData, 'Items');
            $items     = $itemsJson ? json_decode($itemsJson, true) : [];

            if ($customerUID <= 0)  throw new Exception('Please select a customer.');
            if (empty($returnDue))  throw new Exception('Return due date/time is required.');
            if (empty($items))      throw new Exception('Please add at least one machine.');

            $totalRentalAmount = 0;
            $totalDeposit      = 0;
            foreach ($items as $item) {
                $qty = max(1, (int)($item['Qty'] ?? 1));
                $totalRentalAmount += (float)($item['BaseRentalCharge'] ?? 0) * $qty;
                $totalDeposit      += (float)($item['SecurityDeposit']  ?? 0) * $qty;
            }

            $this->dbwrite_model->startTransaction();

            $masterData = [
                'OrgUID'              => $orgUID,
                'ModuleUID'           => $this->pageModuleUID,
                'CustomerUID'         => $customerUID,
                'RentalStartDateTime' => $startDateTime,
                'ReturnDueDateTime'   => $returnDue,
                'RentalStatus'        => 'Active',
                'PaymentStatus'       => $depositCollected > 0 ? 'AdvancePaid' : 'Unpaid',
                'TotalRentalAmount'   => round($totalRentalAmount, 2),
                'ExtraCharges'        => 0,
                'GrandTotal'          => round($totalRentalAmount, 2),
                'DepositAmount'       => round($totalDeposit, 2),
                'DepositCollected'    => round($depositCollected, 2),
                'DepositRefunded'     => 0,
                'TotalPaid'           => round($depositCollected, 2),
                'BalanceAmount'       => round($totalRentalAmount - $depositCollected, 2),
                'Notes'               => $notes,
                'IsActive'            => 1,
                'IsDeleted'           => 0,
                'CreatedBy'           => $userUID,
                'UpdatedBy'           => $userUID,
                'CreatedOn'           => date('Y-m-d H:i:s'),
                'UpdatedOn'           => date('Y-m-d H:i:s'),
            ];

            $resp = $this->dbwrite_model->insertData('Transaction', 'RentalMasterTbl', $masterData);
            if ($resp->Error) throw new Exception($resp->Message);

            $rentalUID    = (int)$resp->ID;
            $rentalNumber = 'RNT-' . str_pad($rentalUID, 4, '0', STR_PAD_LEFT);

            $this->dbwrite_model->updateData(
                'Transaction', 'RentalMasterTbl',
                ['RentalNumber' => $rentalNumber],
                ['RentalUID' => $rentalUID, 'OrgUID' => $orgUID]
            );

            foreach ($items as $item) {
                $qty = max(1, (int)($item['Qty'] ?? 1));
                $rentalType = in_array($item['RentalType'] ?? '', ['Hourly','HalfDay','FullDay','Fixed'])
                    ? $item['RentalType'] : 'Hourly';

                $itemData = [
                    'RentalUID'               => $rentalUID,
                    'ProductUID'              => (int)($item['ProductUID'] ?? 0),
                    'Qty'                     => $qty,
                    'RentalType'              => $rentalType,
                    'SecurityDeposit'         => round((float)($item['SecurityDeposit'] ?? 0), 2),
                    'HourlyRate'              => round((float)($item['HourlyRate'] ?? 0), 2),
                    'HalfDayRate'             => round((float)($item['HalfDayRate'] ?? 0), 2),
                    'FullDayRate'             => round((float)($item['FullDayRate'] ?? 0), 2),
                    'FixedPackageRate'        => round((float)($item['FixedPackageRate'] ?? 0), 2),
                    'ExtraHourRate'           => round((float)($item['ExtraHourRate'] ?? 0), 2),
                    'LateReturnChargePerHour' => round((float)($item['LateReturnChargePerHour'] ?? 0), 2),
                    'BaseRentalCharge'        => round((float)($item['BaseRentalCharge'] ?? 0) * $qty, 2),
                    'ReturnedQty'             => 0,
                    'DamagedQty'              => 0,
                    'TotalCharge'             => round((float)($item['BaseRentalCharge'] ?? 0) * $qty, 2),
                    'ItemStatus'              => 'Rented',
                    'IsActive'                => 1,
                    'IsDeleted'               => 0,
                    'CreatedBy'               => $userUID,
                    'UpdatedBy'               => $userUID,
                    'CreatedOn'               => date('Y-m-d H:i:s'),
                    'UpdatedOn'               => date('Y-m-d H:i:s'),
                ];
                $itemResp = $this->dbwrite_model->insertData('Transaction', 'RentalItemsTbl', $itemData);
                if ($itemResp->Error) throw new Exception($itemResp->Message);
            }

            if ($depositCollected > 0) {
                $pmtResp = $this->dbwrite_model->insertData('Transaction', 'RentalPaymentsTbl', [
                    'RentalUID'      => $rentalUID,
                    'OrgUID'         => $orgUID,
                    'PaymentType'    => 'Deposit',
                    'Amount'         => round($depositCollected, 2),
                    'PaymentDate'    => date('Y-m-d', strtotime($startDateTime)),
                    'PaymentTypeUID' => $depositPayType,
                    'BankAccountUID' => $depositBankUID,
                    'Notes'          => 'Security deposit collected on booking',
                    'IsActive'       => 1,
                    'IsDeleted'      => 0,
                    'CreatedBy'      => $userUID,
                    'UpdatedBy'      => $userUID,
                    'CreatedOn'      => date('Y-m-d H:i:s'),
                    'UpdatedOn'      => date('Y-m-d H:i:s'),
                ]);
                if ($pmtResp->Error) throw new Exception($pmtResp->Message);
            }

            $this->dbwrite_model->commitTransaction();

            $this->EndReturnData->Error        = FALSE;
            $this->EndReturnData->Message      = $rentalNumber . ' created successfully.';
            $this->EndReturnData->RentalUID    = $rentalUID;
            $this->EndReturnData->RentalNumber = $rentalNumber;

        } catch (Exception $e) {
            $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        if (!$this->EndReturnData->Error) {
            $this->_appendListResponse($orgUID);
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── Get rental detail (AJAX) ──────────────────────────────────────────────

    public function getRentalDetail() {
        $this->EndReturnData = new stdClass();
        try {
            $rentalUID = (int) $this->input->post('RentalUID');
            $orgUID    = (int) $this->pageData['JwtData']->Org->OrgUID;

            if ($rentalUID <= 0) throw new Exception('Invalid rental record.');

            $rental = $this->rental_model->getRentalById($rentalUID, $orgUID);
            if (!$rental) throw new Exception('Rental not found.');

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Data  = $rental;

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── Process return ────────────────────────────────────────────────────────

    public function processReturn() {
        $this->EndReturnData = new stdClass();
        try {
            $orgUID  = (int) $this->pageData['JwtData']->Org->OrgUID;
            $userUID = (int) $this->pageData['JwtData']->User->UserUID;

            $PostData      = $this->input->post();
            $rentalUID     = (int)   getPostValue($PostData, 'RentalUID');
            $rentalItemUID = (int)   getPostValue($PostData, 'RentalItemUID');
            $returnedQty   = (int)   getPostValue($PostData, 'ReturnedQty');
            $damagedQty    = (int)   getPostValue($PostData, 'DamagedQty');
            $actualReturn  =         getPostValue($PostData, 'ActualReturnDateTime') ?: date('Y-m-d H:i:s');
            $actualHours   = (float) getPostValue($PostData, 'ActualHours');
            $extraHrCharge = (float) getPostValue($PostData, 'ExtraHourCharge');
            $lateCharge    = (float) getPostValue($PostData, 'LateReturnCharge');
            $damageCharge  = (float) getPostValue($PostData, 'DamageCharge');
            $returnNotes   =         getPostValue($PostData, 'ReturnNotes') ?: null;

            if ($rentalUID <= 0)     throw new Exception('Invalid rental record.');
            if ($rentalItemUID <= 0) throw new Exception('Invalid rental item.');
            if ($returnedQty <= 0)   throw new Exception('Returned quantity must be greater than zero.');

            $rental = $this->rental_model->getRentalById($rentalUID, $orgUID);
            if (!$rental) throw new Exception('Rental not found.');
            if (in_array($rental->RentalStatus, ['Closed', 'Cancelled'])) {
                throw new Exception('This rental is already ' . $rental->RentalStatus . '.');
            }

            $totalChargeForReturn = round($extraHrCharge + $lateCharge + $damageCharge, 2);

            $this->dbwrite_model->startTransaction();

            $this->dbwrite_model->updateData(
                'Transaction', 'RentalItemsTbl',
                [
                    'ReturnedQty'          => $returnedQty,
                    'DamagedQty'           => $damagedQty,
                    'ActualReturnDateTime' => $actualReturn,
                    'ActualHours'          => $actualHours,
                    'ExtraHourCharge'      => round($extraHrCharge, 2),
                    'LateReturnCharge'     => round($lateCharge, 2),
                    'DamageCharge'         => round($damageCharge, 2),
                    'ItemStatus'           => 'Returned',
                    'ReturnNotes'          => $returnNotes,
                    'UpdatedBy'            => $userUID,
                    'UpdatedOn'            => date('Y-m-d H:i:s'),
                ],
                ['RentalItemUID' => $rentalItemUID, 'RentalUID' => $rentalUID, 'IsDeleted' => 0]
            );

            $this->dbwrite_model->insertData('Transaction', 'RentalReturnsTbl', [
                'RentalUID'            => $rentalUID,
                'RentalItemUID'        => $rentalItemUID,
                'OrgUID'               => $orgUID,
                'ReturnedQty'          => $returnedQty,
                'DamagedQty'           => $damagedQty,
                'ActualReturnDateTime' => $actualReturn,
                'ActualHours'          => $actualHours,
                'BaseRentalCharge'     => 0,
                'ExtraHourCharge'      => round($extraHrCharge, 2),
                'LateReturnCharge'     => round($lateCharge, 2),
                'DamageCharge'         => round($damageCharge, 2),
                'TotalChargeForReturn' => $totalChargeForReturn,
                'Notes'                => $returnNotes,
                'CreatedBy'            => $userUID,
                'UpdatedBy'            => $userUID,
                'CreatedOn'            => date('Y-m-d H:i:s'),
                'UpdatedOn'            => date('Y-m-d H:i:s'),
            ]);

            $newExtraCharges = round((float)$rental->ExtraCharges + $totalChargeForReturn, 2);
            $newGrandTotal   = round((float)$rental->TotalRentalAmount + $newExtraCharges, 2);
            $newBalance      = round($newGrandTotal - (float)$rental->TotalPaid, 2);

            // Check if all items are returned
            $items      = $this->rental_model->getRentalItems($rentalUID, $orgUID);
            $allDone    = true;
            foreach ($items as $item) {
                if ($item->RentalItemUID == $rentalItemUID) continue;
                if ($item->ItemStatus !== 'Returned') { $allDone = false; break; }
            }
            $newStatus = $allDone ? 'Closed' : 'PartiallyReturned';

            $this->dbwrite_model->updateData(
                'Transaction', 'RentalMasterTbl',
                [
                    'ExtraCharges'         => $newExtraCharges,
                    'GrandTotal'           => $newGrandTotal,
                    'BalanceAmount'        => $newBalance,
                    'RentalStatus'         => $newStatus,
                    'ActualReturnDateTime' => $allDone ? $actualReturn : null,
                    'UpdatedBy'            => $userUID,
                    'UpdatedOn'            => date('Y-m-d H:i:s'),
                ],
                ['RentalUID' => $rentalUID, 'OrgUID' => $orgUID, 'IsDeleted' => 0]
            );

            $this->dbwrite_model->commitTransaction();

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Return recorded. Status: ' . $newStatus . '.';

        } catch (Exception $e) {
            $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        if (!$this->EndReturnData->Error) {
            $this->_appendListResponse($orgUID);
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── Record payment (called by shared #recordPaymentModal) ─────────────────

    public function recordPayment() {
        $this->EndReturnData = new stdClass();
        try {
            $PostData  = $this->input->post();
            $rentalUID = (int)getPostValue($PostData, 'TransUID');
            $orgUID    = (int) $this->pageData['JwtData']->Org->OrgUID;
            $userUID   = (int) $this->pageData['JwtData']->User->UserUID;

            if ($rentalUID <= 0) throw new Exception('Invalid rental record.');

            $rental = $this->rental_model->getRentalById($rentalUID, $orgUID);
            if (!$rental) throw new Exception('Rental not found.');

            $paymentTypeUID = (int)getPostValue($PostData, 'PaymentTypeUID') ?: null;
            $bankAccountUID = (int)getPostValue($PostData, 'BankAccountUID') ?: null;
            $paymentDate    =     getPostValue($PostData, 'PaymentDate') ?: date('Y-m-d');
            $amount         = (float)getPostValue($PostData, 'Amount');
            $referenceNo    =     getPostValue($PostData, 'ReferenceNo') ?: null;
            $notes          =     getPostValue($PostData, 'Notes') ?: null;

            if (!$paymentTypeUID) throw new Exception('Please select a payment method.');
            if ($amount <= 0)     throw new Exception('Amount must be greater than zero.');

            $this->dbwrite_model->startTransaction();

            $pmtResp = $this->dbwrite_model->insertData('Transaction', 'RentalPaymentsTbl', [
                'RentalUID'      => $rentalUID,
                'OrgUID'         => $orgUID,
                'PaymentType'    => 'RentalCharge',
                'Amount'         => round($amount, 2),
                'PaymentDate'    => $paymentDate,
                'PaymentTypeUID' => $paymentTypeUID,
                'BankAccountUID' => $bankAccountUID,
                'ReferenceNo'    => $referenceNo,
                'Notes'          => $notes,
                'IsActive'       => 1,
                'IsDeleted'      => 0,
                'CreatedBy'      => $userUID,
                'UpdatedBy'      => $userUID,
                'CreatedOn'      => date('Y-m-d H:i:s'),
                'UpdatedOn'      => date('Y-m-d H:i:s'),
            ]);
            if ($pmtResp->Error) throw new Exception($pmtResp->Message);

            $newTotalPaid = round((float)$rental->TotalPaid + $amount, 2);
            $newBalance   = round((float)$rental->GrandTotal - $newTotalPaid, 2);
            $newPayStatus = $newBalance <= 0
                ? 'Paid'
                : ($newTotalPaid > 0 ? 'PartiallyPaid' : 'Unpaid');

            $this->dbwrite_model->updateData(
                'Transaction', 'RentalMasterTbl',
                [
                    'TotalPaid'     => $newTotalPaid,
                    'BalanceAmount' => $newBalance,
                    'PaymentStatus' => $newPayStatus,
                    'UpdatedBy'     => $userUID,
                    'UpdatedOn'     => date('Y-m-d H:i:s'),
                ],
                ['RentalUID' => $rentalUID, 'OrgUID' => $orgUID, 'IsDeleted' => 0]
            );

            $this->dbwrite_model->commitTransaction();

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Payment recorded successfully.';

        } catch (Exception $e) {
            $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        if (!$this->EndReturnData->Error) {
            $this->_appendListResponse($orgUID);
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── Cancel rental ─────────────────────────────────────────────────────────

    public function cancelRental() {
        $this->EndReturnData = new stdClass();
        try {
            $PostData  = $this->input->post();
            $rentalUID = (int)getPostValue($PostData, 'RentalUID');
            $orgUID    = (int) $this->pageData['JwtData']->Org->OrgUID;
            $userUID   = (int) $this->pageData['JwtData']->User->UserUID;

            if ($rentalUID <= 0) throw new Exception('Invalid rental record.');

            $rental = $this->rental_model->getRentalById($rentalUID, $orgUID);
            if (!$rental) throw new Exception('Rental not found.');
            if (in_array($rental->RentalStatus, ['Closed', 'Cancelled'])) {
                throw new Exception('This rental cannot be cancelled.');
            }

            $this->dbwrite_model->updateData(
                'Transaction', 'RentalMasterTbl',
                ['RentalStatus' => 'Cancelled', 'UpdatedBy' => $userUID, 'UpdatedOn' => date('Y-m-d H:i:s')],
                ['RentalUID' => $rentalUID, 'OrgUID' => $orgUID, 'IsDeleted' => 0]
            );

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = ($rental->RentalNumber ?? 'Rental') . ' cancelled.';

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        if (!$this->EndReturnData->Error) {
            $this->_appendListResponse($orgUID);
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── Search rentable products (Select2 AJAX) ───────────────────────────────

    public function searchRentableProducts() {
        $this->EndReturnData = new stdClass();
        try {
            $term   = trim($this->input->post('term') ?: '');
            $orgUID = (int) $this->pageData['JwtData']->Org->OrgUID;
            $this->EndReturnData->Error    = FALSE;
            $this->EndReturnData->Products = $this->rental_model->searchRentableProducts($orgUID, $term);
        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function _appendListResponse($orgUID) {
        $GeneralSettings = $this->pageData['JwtData']->GenSettings ?? new stdClass();

        $filterRaw = $this->input->post('Filter');
        $filter = is_array($filterRaw)
            ? $filterRaw
            : (($filterRaw && ($d = json_decode($filterRaw, true))) ? $d : ['Status' => 'All']);
        $limit = (int)($this->input->post('RowLimit') ?: ($GeneralSettings->RowLimit ?? 10));

        $listData = $this->rental_model->getRentalList($orgUID, $filter, $limit, 0);
        $allCount = $this->rental_model->getRentalCount($orgUID, $filter);

        $rowHtml = $this->load->view('transactions/rental/list', [
            'DataLists'    => $listData,
            'SerialNumber' => 0,
            'JwtData'      => $this->pageData['JwtData'],
        ], TRUE);

        $this->EndReturnData->RecordHtmlData = $rowHtml;
        $this->EndReturnData->TotalCount     = $allCount;
        $this->EndReturnData->Pagination     = $this->globalservice->buildPagePaginationHtml('/rental/getPageDetails', $allCount, 1, $limit);
        $this->EndReturnData->Stats          = $this->rental_model->getRentalStats($orgUID);
    }

}
