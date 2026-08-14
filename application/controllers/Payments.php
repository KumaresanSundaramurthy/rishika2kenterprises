<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Payments extends MY_Controller {

    public $pageData = array();
    protected $EndReturnData;
    protected $pageModuleUID;

    public function __construct() {
        parent::__construct();
        $this->pageModuleUID = 110;
    }

    public function index() {

        if (!$this->_loadPageTitle($this->pageModuleUID)) {
            $this->load->view('common/module_error', $this->pageData);
            return;
        }

        try {

            $this->pageData['JwtData']->ModuleUID = $this->pageModuleUID;

            $GeneralSettings = $this->pageData['JwtData']->GenSettings ?? new stdClass();
            $limit = $GeneralSettings->RowLimit ?? 10;

            $orgUID = $this->pageData['JwtData']->Org->OrgUID;

            $datePref = $this->getDateFilterPreference('payments');
            $filter   = [
                'DateFrom' => $datePref['from'],
                'DateTo'   => $datePref['to'],
            ];

            $this->load->model('transactions_model');
            $allData      = $this->transactions_model->getPaymentsList($limit, 0, $orgUID, $filter);
            $allDataCount = $this->transactions_model->getPaymentsCount($orgUID, $filter);

            $this->load->model('organisation_model');
            $this->pageData['ModRowData']    = $this->load->view('transactions/payments/list', [
                'DataLists'    => $allData,
                'SerialNumber' => 0,
                'JwtData'      => $this->pageData['JwtData'],
                'OrgInfo'      => $this->organisation_model->getOrgInfoCached($orgUID)->Data ?? null,
                'PmtModuleUID' => (int)($this->pageData['JwtData']->ModuleUID ?? 0),
            ], TRUE);
            $this->pageData['ModPagination']  = $this->globalservice->buildPagePaginationHtml('/payments/getPaymentsPageDetails', $allDataCount, 1, $limit);
            $this->pageData['ModAllCount']    = $allDataCount;
            $this->pageData['BalanceStats']   = $this->transactions_model->getPaymentsBalanceStats($orgUID);
            $this->pageData['BankAccounts']   = $this->transactions_model->getOrgBankAccounts($orgUID);
            $this->pageData['PaymentTypes']   = $this->transactions_model->getPaymentTypesList();
            $this->pageData['SavedDateRange'] = $datePref['range'];
            $this->pageData['SavedDateLabel'] = $datePref['label'];
            $this->pageData['InitDateFrom']   = $datePref['from'];
            $this->pageData['InitDateTo']     = $datePref['to'];
            $this->load->model('users_model');
            $this->pageData['OrgUsers']       = $this->users_model->getOrgUsersForCache($orgUID);

            $this->load->view('transactions/payments/view', $this->pageData);

        } catch (Exception $e) {
            redirect('dashboard', 'refresh');
        }

    }

    public function getPaymentsPageDetails($pageNo = 0) {

        $this->EndReturnData = new stdClass();
        try {

            $pageNo = (int) $pageNo;
            if ($pageNo < 1) $pageNo = 1;

            $limit  = (int) $this->input->post('RowLimit') ?: 10;
            $offset = ($pageNo - 1) * $limit;
            $filter = $this->input->post('Filter') ?: [];

            // Unified: show both In and Out — direction filter comes from client if set
            $orgUID = $this->pageData['JwtData']->Org->OrgUID;

            $this->load->model('transactions_model');
            $this->load->model('organisation_model');
            $allData      = $this->transactions_model->getPaymentsList($limit, $offset, $orgUID, $filter);
            $allDataCount = $this->transactions_model->getPaymentsCount($orgUID, $filter);

            $rowHtml = $this->load->view('transactions/payments/list', [
                'DataLists'    => $allData,
                'SerialNumber' => ($pageNo - 1) * $limit,
                'JwtData'      => $this->pageData['JwtData'],
                'OrgInfo'      => $this->organisation_model->getOrgInfoCached($orgUID)->Data ?? null,
                'PmtModuleUID' => (int)($this->pageData['JwtData']->ModuleUID ?? 0),
            ], true);

            $this->EndReturnData->Error          = FALSE;
            $this->EndReturnData->RecordHtmlData = $rowHtml;
            $this->EndReturnData->Pagination     = $this->globalservice->buildPagePaginationHtml('/payments/getPaymentsPageDetails', $allDataCount, $pageNo, $limit);

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    public function getStats() {

        $this->EndReturnData = new stdClass();
        try {

            $filter = $this->input->post('Filter') ?: [];
            $orgUID = $this->pageData['JwtData']->Org->OrgUID;

            $this->load->model('transactions_model');
            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Stats = $this->transactions_model->getPaymentsBalanceStats($orgUID, $filter);

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    public function addPayment() {

        $this->EndReturnData = new stdClass();
        try {

            $this->load->model('dbwrite_model');
            $this->load->model('transactions_model');
            $this->dbwrite_model->startTransaction();

            $PostData = $this->input->post();
            $userUID  = $this->pageData['JwtData']->User->UserUID;
            $orgUID   = $this->pageData['JwtData']->Org->OrgUID;

            $transUID              = (int)   getPostValue($PostData, 'TransUID');
            $moduleUID             = (int)   getPostValue($PostData, 'ModuleUID');
            $paymentTypeUID        = (int)   getPostValue($PostData, 'PaymentTypeUID');
            $amount                = (float) getPostValue($PostData, 'Amount', 'Array', 0);
            $billTotal             = (float) getPostValue($PostData, 'BillTotal', 'Array', 0);
            $bankAccountUID        = (int)   getPostValue($PostData, 'BankAccountUID') ?: NULL;
            $referenceNo           =         getPostValue($PostData, 'ReferenceNo') ?: NULL;
            $notes                 =         getPostValue($PostData, 'Notes') ?: NULL;
            $isFullyPaid           = (int)   getPostValue($PostData, 'IsFullyPaid') === 1 ? 1 : 0;
            $partyType                 =         getPostValue($PostData, 'PartyType') ?: 'C';
            $partyUID                  = (int)   getPostValue($PostData, 'PartyUID');
            $advanceAmount             = (float) getPostValue($PostData, 'AdvanceAmount', 'Array', 0);
            $excessSourcePaymentUID    = (int)   getPostValue($PostData, 'ExcessSourcePaymentUID');
            $onAccountAmount           = (float) getPostValue($PostData, 'OnAccountAmount', 'Array', 0);
            $onAccountSourcePaymentUID = (int)   getPostValue($PostData, 'OnAccountSourcePaymentUID');

            if ($transUID <= 0) throw new Exception('Invalid transaction.');
            if ($amount <= 0 && $advanceAmount <= 0 && $onAccountAmount <= 0) throw new Exception('Payment amount must be greater than 0.');
            if ($amount > 0 && $paymentTypeUID <= 0) throw new Exception('Please select a payment type.');
            if ($advanceAmount > 0 && $excessSourcePaymentUID <= 0) throw new Exception('Invalid advance payment source.');
            if ($onAccountAmount > 0 && $onAccountSourcePaymentUID <= 0) throw new Exception('Invalid on-account payment source.');

            // Lock the invoice row to serialise concurrent payment recordings.
            // A second request reaching this point while the first is inside the
            // transaction blocks here until the first commits or rolls back.
            // After unblocking it sees the updated PaidAmount, so the
            // overpayment check below catches the duplicate before the INSERT.
            $freshNetAmount = $billTotal;
            if ($transUID > 0) {
                $lockedInv = $this->transactions_model->lockInvoiceForUpdate($transUID, $orgUID);

                if (!$lockedInv) {
                    throw new Exception('Invoice not found.');
                }
                if ((int)($lockedInv->IsDeleted ?? 0) === 1) {
                    throw new Exception('This invoice has been deleted and cannot accept payments.');
                }
                if (in_array($lockedInv->DocStatus, ['Cancelled', 'Rejected'], true)) {
                    throw new Exception('This invoice is ' . $lockedInv->DocStatus . ' and cannot accept payments.');
                }

                $freshNetAmount  = (float)($lockedInv->NetAmount  ?? 0);
                $freshPaidAmount = (float)($lockedInv->PaidAmount ?? 0);

                if ($freshNetAmount > 0) {
                    if ($freshPaidAmount >= $freshNetAmount) {
                        throw new Exception(
                            'This invoice has already been fully paid. No further payment is needed.',
                            1002
                        );
                    }
                    $totalAfterPayment = round($freshPaidAmount + $amount, $this->_decimals());
                    if ($totalAfterPayment > round($freshNetAmount, $this->_decimals())) {
                        $remaining = max(0, round($freshNetAmount - $freshPaidAmount, $this->_decimals()));
                        throw new Exception(
                            'Payment exceeds the invoice balance. ' .
                            'Remaining: ' . number_format($remaining, $this->_decimals()) . '. ' .
                            'Another payment may have been recorded simultaneously — please refresh and try again.'
                        );
                    }
                }
            }

            $excessAmount = $freshNetAmount > 0 ? max(0, $amount - $freshNetAmount) : 0;

            // ── On Account allocation guard (race-condition safe) ───────────────
            $lockedOnAccountSource = null;
            if ($onAccountAmount > 0 && $onAccountSourcePaymentUID > 0) {
                $lockedOnAccountSource = $this->transactions_model->lockOnAccountSourcePayment(
                    $onAccountSourcePaymentUID, $orgUID, $partyUID
                );
                if (!$lockedOnAccountSource) throw new Exception('On-account payment source not found.', 1001);
                $availableOnAccount = round((float)($lockedOnAccountSource->Amount ?? 0), $this->_decimals());
                if ($availableOnAccount <= 0) throw new Exception('No on-account balance available — it may have been used by another user. Please refresh and try again.', 1001);
                $onAccountAmount = round($onAccountAmount, $this->_decimals());
                if ($onAccountAmount > $availableOnAccount) {
                    throw new Exception('On-account amount (' . number_format($onAccountAmount, $this->_decimals()) . ') exceeds available balance (' . number_format($availableOnAccount, $this->_decimals()) . ').');
                }
            }

            // ── Advance allocation guard (race-condition safe) ──────────────────
            // Lock the source payment row with FOR UPDATE so concurrent requests
            // queue here. After the lock, re-read ExcessAmount to ensure the advance
            // is still available and hasn't been consumed by another user.
            $lockedSource = null;
            if ($advanceAmount > 0 && $excessSourcePaymentUID > 0) {
                $lockedSource = $this->transactions_model->lockExcessSourcePayment(
                    $excessSourcePaymentUID, $orgUID, $partyUID, $partyType
                );

                if (!$lockedSource) {
                    throw new Exception('Advance payment source not found.');
                }
                if ((int)($lockedSource->IsDeleted  ?? 0) === 1) {
                    throw new Exception('The advance payment source has been deleted.');
                }
                if ((int)($lockedSource->IsCancelled ?? 0) === 1) {
                    throw new Exception('The advance payment source has been cancelled.');
                }
                $availableExcess = round((float)($lockedSource->ExcessAmount ?? 0), $this->_decimals());
                if ($availableExcess <= 0) {
                    throw new Exception('No advance balance available — it may have been used by another user. Please refresh and try again.', 1001);
                }
                $advanceAmount = round($advanceAmount, $this->_decimals());
                if ($advanceAmount > $availableExcess) {
                    throw new Exception('Advance amount (' . number_format($advanceAmount, $this->_decimals()) . ') exceeds available balance (' . number_format($availableExcess, $this->_decimals()) . ').');
                }
            }

            // ── Insert fresh cash payment (if amount > 0) ──────────────────────
            $freshPaymentUID = null;
            if ($amount > 0) {
                $paymentData = [
                    'OrgUID'            => $orgUID,
                    'BranchUID'         => $this->_branchUID(),
                    'TransUID'          => $transUID,
                    'ModuleUID'         => $moduleUID > 0 ? $moduleUID : $this->pageModuleUID,
                    'PartyType'         => $partyType,
                    'PartyUID'          => $partyUID,
                    'PaymentTypeUID'    => $paymentTypeUID,
                    'Amount'            => $amount,
                    'BankAccountUID'    => $bankAccountUID,
                    'ReferenceNo'       => $referenceNo,
                    'Notes'             => $notes,
                    'IsFullyPaid'       => ($advanceAmount > 0) ? 0 : $isFullyPaid,
                    'ExcessAmount'      => $excessAmount,
                    'AppliedToTransUID' => NULL,
                    'IsExcessApplied'   => 0,
                    'ExcessSourcePaymentUID' => NULL,
                    'ReceiptToken'      => $this->transactions_model->_generateReceiptToken(),
                    'IsActive'          => 1,
                    'IsDeleted'         => 0,
                    'CreatedBy'         => $userUID,
                    'UpdatedBy'         => $userUID,
                ];
                $resp = $this->dbwrite_model->insertData('Transaction', 'PaymentsTbl', $paymentData);
                if ($resp->Error) throw new Exception($resp->Message);
                $freshPaymentUID = $resp->ID;
            }

            // ── Insert advance allocation memo row (if advanceAmount > 0) ───────
            // This row is excluded from customer balance (IsExcessApplied = 1).
            // It only increments the invoice PaidAmount so the invoice shows correctly.
            $advancePaymentUID = null;
            if ($advanceAmount > 0 && $lockedSource !== null) {
                $advPaymentData = [
                    'OrgUID'                 => $orgUID,
                    'BranchUID'              => $this->_branchUID(),
                    'TransUID'               => $transUID,
                    'ModuleUID'              => $moduleUID > 0 ? $moduleUID : $this->pageModuleUID,
                    'PartyType'              => $partyType,
                    'PartyUID'               => $partyUID,
                    'PaymentTypeUID'         => $paymentTypeUID,
                    'Amount'                 => $advanceAmount,
                    'BankAccountUID'         => $bankAccountUID,
                    'ReferenceNo'            => $referenceNo,
                    'Notes'                  => 'Advance credit from Payment #' . $excessSourcePaymentUID,
                    'IsFullyPaid'            => $isFullyPaid,
                    'ExcessAmount'           => 0,
                    'AppliedToTransUID'      => NULL,
                    'IsExcessApplied'        => 1,
                    'ExcessSourcePaymentUID' => $excessSourcePaymentUID,
                    'ReceiptToken'           => $this->transactions_model->_generateReceiptToken(),
                    'IsActive'               => 1,
                    'IsDeleted'              => 0,
                    'CreatedBy'              => $userUID,
                    'UpdatedBy'              => $userUID,
                ];
                $advResp = $this->dbwrite_model->insertData('Transaction', 'PaymentsTbl', $advPaymentData);
                if ($advResp->Error) throw new Exception($advResp->Message);
                $advancePaymentUID = $advResp->ID;

                // Reduce ExcessAmount on the source payment by the advance used
                $newExcess = round((float)$lockedSource->ExcessAmount - $advanceAmount, $this->_decimals());
                $this->transactions_model->reduceExcessAmount($excessSourcePaymentUID, $orgUID, $newExcess, $userUID);
            }

            // ── Insert on-account allocation memo row (if onAccountAmount > 0) ──
            $onAccountPaymentUID = null;
            if ($onAccountAmount > 0 && $lockedOnAccountSource !== null) {
                $oaPaymentData = [
                    'OrgUID'                    => $orgUID,
                    'BranchUID'                 => $this->_branchUID(),
                    'TransUID'                  => $transUID,
                    'ModuleUID'                 => $moduleUID > 0 ? $moduleUID : $this->pageModuleUID,
                    'PartyType'                 => $partyType,
                    'PartyUID'                  => $partyUID,
                    'PaymentTypeUID'            => $paymentTypeUID,
                    'Amount'                    => $onAccountAmount,
                    'Notes'                     => 'On-account credit from Payment #' . $onAccountSourcePaymentUID,
                    'IsFullyPaid'               => $isFullyPaid,
                    'ExcessAmount'              => 0,
                    'IsExcessApplied'           => 0,
                    'OnAccountSourcePaymentUID' => $onAccountSourcePaymentUID,
                    'ReceiptToken'              => $this->transactions_model->_generateReceiptToken(),
                    'IsActive'                  => 1,
                    'IsDeleted'                 => 0,
                    'CreatedBy'                 => $userUID,
                    'UpdatedBy'                 => $userUID,
                ];
                $oaResp = $this->dbwrite_model->insertData('Transaction', 'PaymentsTbl', $oaPaymentData);
                if ($oaResp->Error) throw new Exception($oaResp->Message);
                $onAccountPaymentUID = $oaResp->ID;

                $newOnAccountAmt = round((float)$lockedOnAccountSource->Amount - $onAccountAmount, $this->_decimals());
                $this->transactions_model->reduceOnAccountAmount($onAccountSourcePaymentUID, $orgUID, $newOnAccountAmt, $userUID);
            }

            $resp = (object)['ID' => $freshPaymentUID ?? $advancePaymentUID ?? $onAccountPaymentUID];
            $this->dbwrite_model->commitTransaction();

            // Bank ledger entry only for real cash (not the advance memo row)
            if ($amount > 0 && $freshPaymentUID) {
                $ledgerDirection = ($partyType === 'C') ? 'CR' : 'DR';
                $ledgerNarration = ($partyType === 'C')
                    ? 'Payment received from customer — #' . (int)$freshPaymentUID
                    : 'Payment made to vendor — #' . (int)$freshPaymentUID;
                $this->_writeBankLedgerEntry(
                    $orgUID, $bankAccountUID, $ledgerDirection, $amount,
                    'Payment', (int)$freshPaymentUID, $moduleUID ?: $this->pageModuleUID,
                    $referenceNo, $ledgerNarration,
                    date('Y-m-d'), $userUID
                );
            }

            $this->EndReturnData->Error             = false;
            $this->EndReturnData->Message           = 'Payment recorded successfully.';
            $this->EndReturnData->PaymentUID        = $freshPaymentUID;
            $this->EndReturnData->AdvanceUID        = $advancePaymentUID;
            $this->EndReturnData->AdvanceApplied    = $advanceAmount > 0;
            $this->EndReturnData->OnAccountUID      = $onAccountPaymentUID;
            $this->EndReturnData->OnAccountApplied  = $onAccountAmount > 0;

            $auditMeta = ['TransUID' => $transUID, 'Amount' => $amount, 'AdvanceAmount' => $advanceAmount, 'OnAccountAmount' => $onAccountAmount];
            $auditDesc = 'Recorded payment for transaction #' . $transUID
                . ($advanceAmount > 0 ? ' (includes ₹' . $advanceAmount . ' advance from Payment #' . $excessSourcePaymentUID . ')' : '')
                . ($onAccountAmount > 0 ? ' (includes ₹' . $onAccountAmount . ' on-account from Payment #' . $onAccountSourcePaymentUID . ')' : '');
            $this->auditlog->log(
                (int)$orgUID, (int)$userUID,
                'ADD_PAYMENT', 'Payment', (int)($freshPaymentUID ?? $advancePaymentUID), '',
                $auditMeta, $auditDesc, 'Payments', 'PAYMENT', 'SUCCESS', '', 'WEB', [], [], $PostData
            );

        } catch (Exception $e) {
            $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error     = TRUE;
            $this->EndReturnData->Message   = $e->getMessage();
            $this->EndReturnData->ErrorCode = $e->getCode() ?: 0;
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    public function getPaymentsByTransaction() {

        $this->EndReturnData = new stdClass();
        try {

            $transUID = (int) $this->input->get_post('TransUID');
            $orgUID   = $this->pageData['JwtData']->Org->OrgUID;

            if ($transUID <= 0) throw new Exception('Invalid transaction.');

            $this->load->model('transactions_model');
            $payments    = $this->transactions_model->getTransactionPayments($transUID, $orgUID);
            $paidTotal   = array_sum(array_column((array) $payments, 'Amount'));

            $this->EndReturnData->Error      = FALSE;
            $this->EndReturnData->Payments   = $payments;
            $this->EndReturnData->PaidTotal  = $paidTotal;

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    public function getPaymentDetail() {

        $this->EndReturnData = new stdClass();
        try {

            $PostData   = $this->input->post();
            $paymentUID = (int) getPostValue($PostData, 'PaymentUID');
            $orgUID     = $this->pageData['JwtData']->Org->OrgUID;

            if ($paymentUID <= 0) throw new Exception('Invalid payment record.');

            $this->load->model('transactions_model');
            $record = $this->transactions_model->getPaymentDetailById($paymentUID, $orgUID);
            if (!$record) throw new Exception('Payment record not found.');

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Data  = $record;

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    public function deletePayment() {

        $this->EndReturnData = new stdClass();
        try {

            $this->load->model('dbwrite_model');
            $this->load->model('transactions_model');

            $PostData   = $this->input->post();
            $paymentUID = (int) getPostValue($PostData, 'PaymentUID');
            $userUID    = $this->pageData['JwtData']->User->UserUID;
            $orgUID     = $this->pageData['JwtData']->Org->OrgUID;

            if ($paymentUID <= 0) throw new Exception('Invalid payment record.');

            // 1. Fetch payment + existing paid total BEFORE deletion (avoids read-replica lag)
            $payment = $this->transactions_model->getPaymentRow($paymentUID, $orgUID);
            if (!$payment) throw new Exception('Payment record not found or already deleted.');

            // Guard 1 — Block cancellation of source On Account payment that was already applied
            $appliedTransUID = (int)($payment->OnAccountAppliedTransUID ?? 0);
            if ($appliedTransUID > 0 && (int)($payment->IsOnAccount ?? 1) === 0) {
                $linkedInv = $this->transactions_model->getTransactionBasicInfo($appliedTransUID, $orgUID);
                if ($linkedInv && !in_array($linkedInv->DocStatus, ['Cancelled', 'Rejected'])) {
                    throw new Exception(
                        'This payment is adjusted against Invoice ' .
                        ($linkedInv->UniqueNumber ?: '#' . $appliedTransUID) .
                        '. You cannot delete this payment until that invoice is fully cancelled.'
                    );
                }
            }

            // Guard 2 — Block cancellation of source On Account if applied child payments exist
            $appliedChild = $this->dbwrite_model->getAppliedChildPayment($paymentUID, $orgUID);
            if ($appliedChild) {
                throw new Exception(
                    'This On Account payment has been applied to Invoice ' .
                    ($appliedChild->UniqueNumber ?: '#' . $appliedChild->PaymentUID) .
                    '. Cancel that payment first before deleting this record.'
                );
            }

            // Guard — Block if this payment is itself a source whose advance is still in use
            if ((int)($payment->IsExcessApplied ?? 0) === 0 && (float)($payment->ExcessAmount ?? 0) == 0) {
                // ExcessAmount may be 0 because advance was consumed — check for active links
                $activeAdvLink = $this->transactions_model->getActiveAdvanceLink($paymentUID);
                if ($activeAdvLink) {
                    throw new Exception(
                        'This payment has an advance credit used in another invoice. Remove that advance entry first, then delete this payment.'
                    );
                }
            }

            // Guard 3 — Block if payment was transferred to a Credit Note (invoice cancellation flow)
            if ((int)($payment->IsTransferredToCreditNote ?? 0) === 1) {
                throw new Exception(
                    'This payment has been converted to a Credit Note. ' .
                    'To remove it, delete the linked Credit Note from the Credit Notes tab — ' .
                    'that will automatically delete this payment and revert the customer balance.'
                );
            }

            // Guard 4 (On Account) — Block if payment is held as on-account customer credit
            if ((int)($payment->IsOnAccount ?? 0) === 1) {
                throw new Exception(
                    'This payment is held as an on-account credit for the customer (from a cancelled invoice). ' .
                    'It cannot be cancelled or deleted directly — apply it to a new invoice to use the credit.'
                );
            }

            $transUID     = (int) $payment->TransUID;
            $existingPaid = ($transUID > 0)
                ? $this->transactions_model->getSumPaidForTransaction($transUID, $orgUID)
                : 0;

            // Guard 4 — SR payment: block if linked Credit Note is already Applied to an invoice
            $srCN = null;
            if ($transUID > 0 && (int)($payment->ModuleUID ?? 0) === 106) {
                $srCN = $this->transactions_model->getSRCreditNoteBySourceTrans($transUID);
                if ($srCN && $srCN->Status === 'Applied') {
                    throw new Exception(
                        'This credit note has been applied to an invoice. ' .
                        'Please reverse the credit allocation before deleting this payment.'
                    );
                }
            }

            $action = getPostValue($PostData, 'Action') === 'cancel' ? 'cancel' : 'delete';

            $this->dbwrite_model->startTransaction();

            // Re-check payment state from WriteDB with FOR UPDATE — locks the row so
            // concurrent delete/cancel requests queue up rather than racing each other.
            // Any commit by the first request makes this row visible as already processed
            // to every subsequent request waiting on the lock.
            $freshPayment = $this->transactions_model->lockPaymentForDelete($paymentUID, $orgUID);

            if (!$freshPayment) {
                throw new Exception('Payment record not found.');
            }
            if ((int)($freshPayment->IsDeleted ?? 0) === 1) {
                throw new Exception('This payment has already been deleted by another user.');
            }
            if ((int)($freshPayment->IsCancelled ?? 0) === 1) {
                throw new Exception('This payment has already been cancelled by another user.');
            }
            if ((int)($freshPayment->IsTransferredToCreditNote ?? 0) === 1) {
                throw new Exception(
                    'This payment has been converted to a Credit Note. ' .
                    'To remove it, delete the linked Credit Note from the Credit Notes tab — ' .
                    'that will automatically delete this payment and revert the customer balance.'
                );
            }
            if ((int)($freshPayment->IsOnAccount ?? 0) === 1) {
                throw new Exception(
                    'This payment is held as an on-account credit for the customer (from a cancelled invoice). ' .
                    'It cannot be cancelled or deleted directly — apply it to a new invoice to use the credit.'
                );
            }

            // 2a. If this is an advance allocation row, restore ExcessAmount on the source
            if ((int)($freshPayment->IsExcessApplied ?? 0) === 1) {
                $srcUID = (int)($payment->ExcessSourcePaymentUID ?? 0);
                if ($srcUID > 0) {
                    $srcRow = $this->transactions_model->lockAndGetExcessSource($srcUID, $orgUID);
                    if ($srcRow) {
                        $restoredExcess = round((float)$srcRow->ExcessAmount + (float)$payment->Amount, $this->_decimals());
                        $this->transactions_model->restoreExcessAmount($srcUID, $orgUID, $restoredExcess, $userUID);
                    }
                }
            }

            // 2. Mark payment based on action: cancel → IsCancelled = 1, delete → IsDeleted = 1
            $updateFields = $action === 'cancel'
                ? ['IsCancelled' => 1, 'IsActive' => 0, 'UpdatedBy' => $userUID]
                : ['IsDeleted'   => 1, 'IsActive' => 0, 'UpdatedBy' => $userUID];

            $resp = $this->dbwrite_model->updateData(
                'Transaction', 'PaymentsTbl',
                $updateFields,
                ['PaymentUID' => $paymentUID, 'OrgUID' => $orgUID, 'IsDeleted' => 0]
            );
            if ($resp->Error) throw new Exception($resp->Message);

            // 3. Recalculate and persist updated invoice balance
            if ($transUID > 0) {
                $newTotalPaid = max(0, round($existingPaid - (float) $payment->Amount, $this->_decimals()));

                $trans = $this->transactions_model->getTransactionBasicInfo($transUID, $orgUID);
                if ($trans) {
                    $netAmount     = (float) $trans->NetAmount;
                    $balanceAmount = max(0, round($netAmount - $newTotalPaid, $this->_decimals()));
                    $isFullyPaid   = ($netAmount > 0 && $balanceAmount <= 0) ? 1 : 0;

                    $this->dbwrite_model->updateTransIsFullyPaid($transUID, $isFullyPaid, $newTotalPaid, $balanceAmount, $userUID);

                    if ($newTotalPaid <= 0)  $newStatus = ((int)$trans->ModuleUID === 106) ? 'Approved' : 'Issued';
                    elseif ($isFullyPaid)    $newStatus = 'Paid';
                    else                     $newStatus = 'Partial';

                    $this->dbwrite_model->updateTransDocStatus($transUID, $orgUID, $newStatus, $userUID);

                    $this->EndReturnData->NewPaidAmount    = round($newTotalPaid, $this->_decimals());
                    $this->EndReturnData->NewBalanceAmount = $balanceAmount;
                    $this->EndReturnData->NewStatus        = $newStatus;
                }
            }

            // 3b. SR-specific: restore linked Credit Note amount when a payment is cancelled
            if ($srCN && $srCN->Status === 'Pending') {
                $newCNAmount = round((float)$srCN->Amount + (float)$payment->Amount, $this->_decimals());
                $this->transactions_model->updateSRCreditNoteAmount((int)$srCN->CreditNoteUID, $newCNAmount, $userUID);
            }

            // 3c. If this payment was created from an On Account source, restore the source
            $sourceOAUID = (int)($payment->OnAccountSourcePaymentUID ?? 0);
            if ($sourceOAUID > 0) {
                $sourceOA = $this->dbwrite_model->getOnAccountSourcePayment($sourceOAUID, $orgUID);
                if ($sourceOA) {
                    $restoredAmount = round((float)$sourceOA->Amount + (float)$payment->Amount, $this->_decimals());
                    $this->dbwrite_model->restoreOnAccountPayment($sourceOAUID, $orgUID, $restoredAmount, $userUID);
                }
            }

            $this->dbwrite_model->commitTransaction();

            // 4. Reverse customer ledger entry (non-fatal)
            // Skip for advance memo rows (IsExcessApplied = 1) and on-account applied rows
            // (OnAccountSourcePaymentUID > 0) — neither carries real new cash.
            if ($transUID > 0 && $payment->PartyType === 'C' && (int)$payment->PartyUID > 0
                && (int)($payment->IsExcessApplied ?? 0) === 0
                && (int)($payment->OnAccountSourcePaymentUID ?? 0) === 0) {
                try {
                    $this->load->library('accountledger');
                    $this->accountledger->applyLedgerEntry(
                        (int) $payment->PartyUID, 'Customer', (float) $payment->Amount, 'Debit', $transUID
                    );
                } catch (Exception $ledgerEx) {
                    log_message('error', 'Ledger reversal failed after payment delete PaymentUID=' . $paymentUID . ': ' . $ledgerEx->getMessage());
                }
            }

            // 5. Recalculate customer closing balance and sync Upstash cache
            if ($payment->PartyType === 'C' && (int)$payment->PartyUID > 0) {
                try {
                    $this->load->library('customerbalance');
                    $balResult = $this->customerbalance->recalcAndSync($orgUID, (int)$payment->PartyUID, $userUID);
                    if ($balResult) {
                        $this->EndReturnData->CustomerBalance     = $balResult['balance'];
                        $this->EndReturnData->CustomerBalanceType = $balResult['type'];
                    }
                } catch (Exception $balEx) {
                    log_message('error', 'Customer balance recalc failed after payment delete PaymentUID=' . $paymentUID . ': ' . $balEx->getMessage());
                }
            }

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = $action === 'cancel' ? 'Payment cancelled.' : 'Payment deleted.';
            $auditAction = $action === 'cancel' ? 'CANCEL_PAYMENT' : 'DELETE_PAYMENT';
            $auditDesc   = $action === 'cancel' ? 'Cancelled payment #' . $paymentUID : 'Deleted payment #' . $paymentUID;
            $this->auditlog->log(
                (int) $orgUID, (int) $userUID,
                $auditAction, 'Payment', (int) $paymentUID, '',
                ['TransUID' => $transUID], $auditDesc, 'Payments', 'PAYMENT'
            );

        } catch (Exception $e) {
            if (isset($this->dbwrite_model)) $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    public function getCustomerCreditsDetail(): void {
        $this->EndReturnData = new stdClass();
        try {
            $partyUID = (int) $this->input->get_post('PartyUID');
            $orgUID   = (int) $this->pageData['JwtData']->Org->OrgUID;

            if ($partyUID <= 0) throw new Exception('Invalid customer.');

            $this->load->model('transactions_model');
            $rows = $this->transactions_model->getCustomerCreditsDetail($partyUID, $orgUID);

            $onAccount = [];
            $advance   = [];
            foreach ($rows as $row) {
                if ($row->CreditType === 'on_account') {
                    $onAccount[] = $row;
                } else {
                    $advance[] = $row;
                }
            }

            $dec = $this->_decimals();
            $this->EndReturnData->Error          = false;
            $this->EndReturnData->OnAccount      = $onAccount;
            $this->EndReturnData->Advance        = $advance;
            $this->EndReturnData->OnAccountTotal = round((float) array_sum(array_column($onAccount, 'Amount')), $dec);
            $this->EndReturnData->AdvanceTotal   = round((float) array_sum(array_column($advance,   'Amount')), $dec);

        } catch (Exception $e) {
            $this->EndReturnData->Error   = true;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    public function getCustomerCreditNoteDetail(): void {
        $this->EndReturnData = new stdClass();
        try {
            $partyUID = (int) $this->input->get_post('PartyUID');
            $orgUID   = (int) $this->pageData['JwtData']->Org->OrgUID;

            if ($partyUID <= 0) throw new Exception('Invalid customer.');

            $this->load->model('transactions_model');
            $rows = $this->transactions_model->getCustomerCreditNoteDetail($partyUID, $orgUID);
            $dec  = $this->_decimals();

            $this->EndReturnData->Error       = false;
            $this->EndReturnData->CreditNotes = $rows;
            $this->EndReturnData->Total       = round((float) array_sum(array_column($rows, 'Amount')), $dec);

        } catch (Exception $e) {
            $this->EndReturnData->Error   = true;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    public function getCustomerExcessBalance(): void {

        $this->EndReturnData = new stdClass();
        try {

            $partyUID = (int) $this->input->get_post('PartyUID');
            $orgUID   = (int) $this->pageData['JwtData']->Org->OrgUID;

            if ($partyUID <= 0) throw new Exception('Invalid customer.');

            $this->load->model('transactions_model');
            $rows        = $this->transactions_model->getCustomerAvailableCredits($partyUID, $orgUID);
            $totalCredit = array_sum(array_column((array)$rows, 'CreditAmount'));

            $this->EndReturnData->Error        = false;
            $this->EndReturnData->TotalCredit  = round((float)$totalCredit, $this->_decimals());
            $this->EndReturnData->Sources      = $rows;

        } catch (Exception $e) {
            $this->EndReturnData->Error   = true;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    public function getPaymentTypes() {

        $this->EndReturnData = new stdClass();
        try {

            $this->load->model('transactions_model');
            $types = $this->transactions_model->getPaymentTypesList();

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Data  = $types;

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    public function getBankAccounts() {

        $this->EndReturnData = new stdClass();
        try {

            $orgUID = $this->pageData['JwtData']->Org->OrgUID;
            $this->load->model('transactions_model');
            $accounts = $this->transactions_model->getOrgBankAccounts($orgUID);

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Data  = $accounts;

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    public function saveBankAccount() {

        $this->EndReturnData = new stdClass();
        try {

            $this->load->model('dbwrite_model');

            $PostData    = $this->input->post();
            $orgUID      = $this->pageData['JwtData']->Org->OrgUID;
            $userUID     = $this->pageData['JwtData']->User->UserUID;
            $accountUID  = (int) getPostValue($PostData, 'BankAccountUID');

            $isDefault     = (int) getPostValue($PostData, 'IsDefault') === 1 ? 1 : 0;
            $accountNumber = trim(getPostValue($PostData, 'AccountNumber'));
            $confirmNumber = trim(getPostValue($PostData, 'ConfirmAccountNumber'));

            $accountData = [
                'OrgUID'        => $orgUID,
                'AccountName'   => trim(getPostValue($PostData, 'AccountName')),
                'BankName'      => trim(getPostValue($PostData, 'BankName')),
                'AccountNumber' => $accountNumber,
                'IFSC'          => strtoupper(trim(getPostValue($PostData, 'IFSC'))) ?: NULL,
                'BranchName'    => trim(getPostValue($PostData, 'BranchName')) ?: NULL,
                'UPIId'         => trim(getPostValue($PostData, 'UPIId')) ?: NULL,
                'UPINumber'     => trim(getPostValue($PostData, 'UPINumber')) ?: NULL,
                'IsDefault'     => $isDefault,
                'IsActive'      => 1,
                'IsDeleted'     => 0,
                'UpdatedBy'     => $userUID,
            ];

            if (empty($accountData['AccountName']))   throw new Exception('Account holder name is required.');
            if (empty($accountData['BankName']))      throw new Exception('Bank name is required.');
            if (empty($accountData['AccountNumber'])) throw new Exception('Account number is required.');
            if ($accountUID <= 0 && $accountNumber !== $confirmNumber) throw new Exception('Account number and confirmation do not match.');

            $this->dbwrite_model->startTransaction();

            // If set as default, clear other defaults first
            if ($isDefault) {
                $this->dbwrite_model->updateData(
                    'Organisation', 'OrgBankAccountsTbl',
                    ['IsDefault' => 0, 'UpdatedBy' => $userUID],
                    ['OrgUID' => $orgUID, 'IsDeleted' => 0]
                );
            }

            if ($accountUID > 0) {
                $resp = $this->dbwrite_model->updateData(
                    'Organisation', 'OrgBankAccountsTbl', $accountData,
                    ['BankAccountUID' => $accountUID, 'OrgUID' => $orgUID, 'IsDeleted' => 0]
                );
            } else {
                $accountData['CreatedBy'] = $userUID;
                $resp = $this->dbwrite_model->insertData('Organisation', 'OrgBankAccountsTbl', $accountData);
                if (!$resp->Error) $this->EndReturnData->BankAccountUID = $resp->ID;
            }

            if ($resp->Error) throw new Exception($resp->Message);

            $this->dbwrite_model->commitTransaction();

            $this->upstashservice->del($this->redisservice->orgKey('org-bank-accounts'));

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = $accountUID > 0 ? 'Bank account updated.' : 'Bank account added.';
            $this->auditlog->log(
                (int) $orgUID, (int) $userUID,
                'SAVE_BANK_ACCOUNT', 'BankAccount', $accountUID > 0 ? (int) $accountUID : (int) ($resp->ID ?? 0), (string) ($accountData['AccountName'] ?? ''),
                ['IsUpdate' => $accountUID > 0], ($accountUID > 0 ? 'Updated' : 'Added') . ' bank account ' . ($accountData['AccountName'] ?? ''), 'Payments', 'SETTINGS'
            );

        } catch (Exception $e) {
            $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    public function getBankDetails() {

        $this->EndReturnData = new stdClass();
        try {

            $PostData      = $this->input->post();
            $orgUID        = $this->pageData['JwtData']->Org->OrgUID;
            $bankAccountUID = (int) getPostValue($PostData, 'BankAccountUID');
            if ($bankAccountUID <= 0) throw new Exception('Bank account ID is required.');

            $this->load->model('transactions_model');

            $this->transactions_model->ReadDb->db_debug = FALSE;
            $this->transactions_model->ReadDb->select('BankAccountUID, AccountName, BankName, AccountNumber, IFSC, BranchName, UPIId, UPINumber, IsDefault');
            $this->transactions_model->ReadDb->from('Organisation.OrgBankAccountsTbl');
            $this->transactions_model->ReadDb->where(['BankAccountUID' => $bankAccountUID, 'OrgUID' => $orgUID, 'IsDeleted' => 0]);
            $query  = $this->transactions_model->ReadDb->get();
            $record = $query ? $query->row() : null;
            if (!$record) throw new Exception('Bank account not found.');

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Data  = $record;

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    public function setDefaultBank() {

        $this->EndReturnData = new stdClass();
        try {

            $this->load->model('dbwrite_model');

            $PostData       = $this->input->post();
            $orgUID         = $this->pageData['JwtData']->Org->OrgUID;
            $userUID        = $this->pageData['JwtData']->User->UserUID;
            $bankAccountUID = (int) getPostValue($PostData, 'BankAccountUID');
            if ($bankAccountUID <= 0) throw new Exception('Bank account ID is required.');

            $this->dbwrite_model->startTransaction();

            // Clear all defaults
            $this->dbwrite_model->updateData(
                'Organisation', 'OrgBankAccountsTbl',
                ['IsDefault' => 0, 'UpdatedBy' => $userUID],
                ['OrgUID' => $orgUID, 'IsDeleted' => 0]
            );

            // Set the selected one
            $resp = $this->dbwrite_model->updateData(
                'Organisation', 'OrgBankAccountsTbl',
                ['IsDefault' => 1, 'UpdatedBy' => $userUID],
                ['BankAccountUID' => $bankAccountUID, 'OrgUID' => $orgUID, 'IsDeleted' => 0]
            );
            if ($resp->Error) throw new Exception($resp->Message);

            $this->dbwrite_model->commitTransaction();

            $this->upstashservice->del($this->redisservice->orgKey('org-bank-accounts'));

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Default bank updated.';

        } catch (Exception $e) {
            $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    public function deleteBankAccount() {

        $this->EndReturnData = new stdClass();
        try {

            $this->load->model('dbwrite_model');

            $PostData       = $this->input->post();
            $orgUID         = $this->pageData['JwtData']->Org->OrgUID;
            $userUID        = $this->pageData['JwtData']->User->UserUID;
            $bankAccountUID = (int) getPostValue($PostData, 'BankAccountUID');
            if ($bankAccountUID <= 0) throw new Exception('Bank account ID is required.');

            $deleteData = $this->globalservice->baseDeleteArrayDetails();
            $deleteData['IsActive'] = 0;

            $resp = $this->dbwrite_model->updateData(
                'Organisation', 'OrgBankAccountsTbl', $deleteData,
                ['BankAccountUID' => $bankAccountUID, 'OrgUID' => $orgUID, 'IsDeleted' => 0]
            );
            if ($resp->Error) throw new Exception($resp->Message);

            $this->upstashservice->del($this->redisservice->orgKey('org-bank-accounts'));

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Bank account deleted.';
            $this->auditlog->log(
                (int) $orgUID, (int) $userUID,
                'DELETE_BANK_ACCOUNT', 'BankAccount', (int) $bankAccountUID, '',
                [], 'Deleted bank account #' . $bankAccountUID, 'Payments', 'SETTINGS'
            );

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    public function getPaymentPrintDetail() {

        $this->EndReturnData = new stdClass();
        try {

            $paymentUID = (int) $this->input->get_post('PaymentUID');
            $printType  = $this->input->get_post('PrintType') ?: 'a4'; // 'thermal' | 'a4'
            $orgUID     = $this->pageData['JwtData']->Org->OrgUID;
            $isThermal  = $printType == 'thermal' ? 1 : 0;

            if ($paymentUID <= 0) throw new Exception('Invalid payment.');

            $this->load->model('transactions_model');
            $payment     = $this->transactions_model->getPaymentDetailById($paymentUID, $orgUID);
            if (!$payment) throw new Exception('Payment not found.');
                        $this->load->model('organisation_model');
            $orgInfo    = $this->organisation_model->getOrgInfoCached($orgUID);
            $org        = $orgInfo->Data ?? null;

            $this->EndReturnData->Error         = FALSE;
            $this->EndReturnData->Payment       = $payment;
            $this->EndReturnData->OrgInfo       = $org;
            if ($isThermal) {
                $thermalCfg = $this->organisation_model->getThermalPrintConfigByModule($orgUID, $this->pageModuleUID);
                $this->EndReturnData->ThermalConfig = $thermalCfg->Data ?? null;
            }

            // PrintTheme and PrintHtml are only needed for A4 preview — skip for thermal
            if (!$isThermal) {
                $printTheme = $this->organisation_model->getPrintThemeByType($orgUID, 'Payment');
                $themeData  = $printTheme->Data ?? null;
                $this->EndReturnData->PrintTheme = $themeData;
                $this->EndReturnData->PrintHtml  = $this->transactions_model->_renderPaymentReceiptHtml($payment, $org, $themeData);
            }

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    public function downloadPaymentPdf() {

        try {

            $paymentUID = (int) $this->input->get_post('PaymentUID');
            $paperSize  = strtoupper(trim($this->input->get_post('PaperSize') ?: 'A4'));
            $orgUID     = $this->pageData['JwtData']->Org->OrgUID;

            if ($paymentUID <= 0) throw new Exception('Invalid payment.');

            $this->load->model('transactions_model');
            $payment = $this->transactions_model->getPaymentDetailById($paymentUID, $orgUID);
            if (!$payment) throw new Exception('Payment not found.');

            $pdfBytes = $this->transactions_model->generatePaymentReceiptPdfBytes($paymentUID, $orgUID, $paperSize);
            if (!$pdfBytes) throw new Exception('Failed to generate PDF.');

            $filename = preg_replace('/[^A-Za-z0-9_\-]/', '_', $payment->UniqueNumber ?? ('Payment_' . $paymentUID)) . '.pdf';

            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Cache-Control: private, max-age=0, must-revalidate');
            echo $pdfBytes;
            exit;

        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['Error' => true, 'Message' => $e->getMessage()]);
            exit;
        }

    }

    public function getBanksList() {

        $this->EndReturnData = new stdClass();
        try {

            $orgUID = $this->pageData['JwtData']->Org->OrgUID;
            $this->load->model('transactions_model');
            $accounts = $this->transactions_model->getOrgBankAccounts($orgUID);

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Data  = $accounts;

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    /**
     * Generate payment receipt PDF in memory and return as base64.
     * Used for email attachment without writing to disk.
     */
    public function getPaymentPdfBase64() {

        $this->EndReturnData = new stdClass();
        try {

            $paymentUID = (int) $this->input->post('PaymentUID');
            $paperSize  = strtoupper(trim($this->input->post('PaperSize') ?: 'A4'));
            $orgUID     = $this->pageData['JwtData']->Org->OrgUID;

            if ($paymentUID <= 0) throw new Exception('Invalid payment.');

            $this->load->model('transactions_model');
            $payment = $this->transactions_model->getPaymentDetailById($paymentUID, $orgUID);
            if (!$payment) throw new Exception('Payment not found.');

            $pdfBytes = $this->transactions_model->generatePaymentReceiptPdfBytes($paymentUID, $orgUID, $paperSize);
            if (!$pdfBytes) throw new Exception('Failed to generate PDF.');

            $filename = preg_replace('/[^A-Za-z0-9_\-]/', '_', $payment->UniqueNumber ?? ('Receipt_' . $paymentUID)) . '.pdf';

            $this->EndReturnData->Error    = FALSE;
            $this->EndReturnData->Base64   = base64_encode($pdfBytes);
            $this->EndReturnData->Filename = $filename;
            $this->EndReturnData->Size     = strlen($pdfBytes);

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

}