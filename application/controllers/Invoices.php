<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Invoices extends MY_Controller {

    public $pageData = array();
    protected $EndReturnData;
    protected $pageModuleUID;

    public function __construct() {
        parent::__construct();

        $this->pageModuleUID = 103;
        $this->load->helper('transaction');

    }

    public function index(): void {
        if (!$this->_loadPageTitle($this->pageModuleUID)) {
            $this->load->view('common/module_error', $this->pageData);
            return;
        }
        try {
            $this->pageData['JwtData']->ModuleUID = $this->pageModuleUID;
            $orgUID = (int) $this->pageData['JwtData']->Org->OrgUID;
            $this->load->model('organisation_model');
            $templates = $this->organisation_model->getModuleMessageTemplates($orgUID, $this->pageModuleUID);
            $this->_loadTransactionIndexPage([
                'datePrefKey'       => 'invoices',
                'tabSlugMap'        => ['all' => 'All', 'pending' => 'InvPending', 'paid' => 'Paid', 'cancelled' => 'Cancelled', 'draft' => 'Draft', 'creditnotes' => 'CreditNotes'],
                'listViewPath'      => 'transactions/invoices/list',
                'paginationUrl'     => '/transactions/getPageDetails/103',
                'skipListForTabs'   => ['CreditNotes'],
                'listViewExtraData' => ['WhatsAppTemplate' => $templates['WhatsApp'] ?? null],
            ]);

            $this->pageData['CnInitHtml']       = '';
            $this->pageData['CnInitPagination'] = '';
            $this->pageData['CnInitCount']      = 0;
            if ($this->pageData['InitTab'] === 'CreditNotes') {
                try {
                    $cnSearch  = trim($this->input->get('search') ?: '');
                    $cnLimit   = (int)($this->pageData['JwtData']->GenSettings->RowLimit ?? 10);
                    $readDb    = $this->load->database('ReadDB', TRUE);
                    $readDb->db_debug = FALSE;
                    $baseWhere = ['CN.OrgUID' => $orgUID, 'CN.IsDeleted' => 0, 'CN.IsCancelled' => 0];

                    $readDb->select('COUNT(*) AS total');
                    $readDb->from('Transaction.TransCreditNoteTbl CN');
                    $readDb->join('Customers.CustomerTbl C', 'C.CustomerUID = CN.PartyUID', 'left');
                    $readDb->where($baseWhere);
                    if ($cnSearch !== '') {
                        $readDb->group_start();
                        $readDb->like('CN.CreditNoteNumber', $cnSearch);
                        $readDb->or_like('C.Name', $cnSearch);
                        $readDb->or_like('CN.SourceTransNumber', $cnSearch);
                        $readDb->group_end();
                    }
                    $cnTotal = (int)(($readDb->get()->row()->total) ?? 0);

                    $readDb->select([
                        'CN.CreditNoteUID', 'CN.CreditNoteNumber', 'CN.CreditNoteToken',
                        'CN.CreditNoteType', 'CN.SourceTransUID', 'CN.SourceTransNumber',
                        'CN.SourceModuleUID', 'CN.Amount', 'CN.Status', 'CN.Notes', 'CN.CreatedOn',
                        'C.CustomerUID', 'C.Name AS CustomerName', 'C.MobileNumber AS MobileNo',
                        'C.Area AS CustomerArea', 'C.Image AS CustomerImage',
                        'T.TransDate AS SourceTransDate', 'T.TransToken AS SourceTransToken',
                        "CONCAT(U.FirstName, ' ', U.LastName) AS CreatorName",
                    ]);
                    $readDb->from('Transaction.TransCreditNoteTbl CN');
                    $readDb->join('Customers.CustomerTbl C',       'C.CustomerUID = CN.PartyUID',                        'left');
                    $readDb->join('Transaction.TransactionsTbl T', 'T.TransUID = CN.SourceTransUID AND T.IsDeleted = 0', 'left');
                    $readDb->join('Users.UserTbl U',               'U.UserUID = CN.CreatedBy',                          'left');
                    $readDb->where($baseWhere);
                    if ($cnSearch !== '') {
                        $readDb->group_start();
                        $readDb->like('CN.CreditNoteNumber', $cnSearch);
                        $readDb->or_like('C.Name', $cnSearch);
                        $readDb->or_like('CN.SourceTransNumber', $cnSearch);
                        $readDb->group_end();
                    }
                    $readDb->order_by('CN.CreatedOn', 'DESC');
                    $readDb->limit($cnLimit, 0);
                    $cnRows = $readDb->get()->result();

                    $this->pageData['CnInitHtml'] = $this->load->view(
                        'transactions/invoices/creditnotes_list',
                        ['DataLists' => $cnRows, 'SerialNumber' => 0, 'JwtData' => $this->pageData['JwtData']],
                        true
                    );
                    $this->pageData['CnInitPagination'] = $this->globalservice->buildPagePaginationHtml(
                        '/invoices/getCreditNotesList', $cnTotal, 1, $cnLimit
                    );
                    $this->pageData['CnInitCount'] = $cnTotal;
                } catch (Exception $e) {
                    // Fail silently — JS loadCreditNotes() fires as fallback
                }
            }

            $this->load->view('transactions/invoices/view', $this->pageData);
        } catch (Exception $e) {
            notifyError('Invoices::index', $e);
            redirect('dashboard', 'refresh');
        }
    }

    public function addInvoice() {

        $this->EndReturnData = new stdClass();
        $ErrorInForm = '';
        try {

            $this->load->model('dbwrite_model');
            $this->dbwrite_model->startTransaction();

            $PostData = $this->input->post();
            $userUID  = $this->pageData['JwtData']->User->UserUID;
            $orgUID   = $this->pageData['JwtData']->Org->OrgUID;

            $itemsJson     = $this->_validateTransForm($PostData);
            $amounts       = $this->_extractTransAmounts($PostData, $itemsJson);
            $isDraft       = $amounts['isDraft'];
            $items         = $amounts['items'];
            $netAmount     = $amounts['netAmount'];
            $financialYear = $amounts['financialYear'];
            $subTotal      = $amounts['subTotal'];
            $cgstAmount    = $amounts['cgstAmount'];
            $sgstAmount    = $amounts['sgstAmount'];
            $igstAmount    = $amounts['igstAmount'];
            $transDate     = $amounts['transDate'];
            $customerUID   = (int) getPostValue($PostData, 'customerSearch');

            $resolved = $this->_resolveTransPrefix(
                $isDraft, $amounts['prefixUID'], $amounts['transNumber'], $transDate, $orgUID
            );

            $amounts['moduleUID']    = $this->pageModuleUID;
            $amounts['prefixUID']    = $resolved['prefixUID'];
            $amounts['transNumber']  = $resolved['transNumber'];
            $amounts['uniqueNumber'] = $resolved['uniqueNumber'];

            $this->load->model('dbwrite_model');
            $headerData = $this->_buildTransHeader(
                [
                    'TransType'       => 'Invoice',
                    'PartyType'       => 'C',
                    'PartyUID'        => $customerUID,
                    'DocTypePostKey'  => 'invoiceType',
                    'DispatchPostKey' => 'dispatchFrom',
                    'InitialStatus'   => 'Issued',
                    'hasPaidAmount'   => true,
                ],
                $amounts, $PostData, $orgUID, $userUID
            );

            $insertResp = $this->_insertTransactionWithRetry($headerData, $resolved['prefixUID'], $orgUID, $resolved['prefix'], $transDate);
            if ($insertResp->Error) throw new Exception($insertResp->Message);

            $transUID     = $insertResp->ID;
            $transNumber  = $headerData['TransNumber'];
            $uniqueNumber = $headerData['UniqueNumber'];

            $this->_saveTransCharges($transUID, $orgUID, $userUID, $PostData);

            $detailData = $this->_buildTransDetail(
                [
                    'PartyType'          => 'C',
                    'PartyUID'           => $customerUID,
                    'ValidityDatePostKey' => 'dueDate',
                ],
                $amounts, $PostData, $transUID
            );
            $detailResp = $this->dbwrite_model->insertData('Transaction', 'TransDetailTbl', $detailData);
            if ($detailResp->Error) throw new Exception($detailResp->Message);

            $this->_insertTransItems($transUID, $financialYear, $orgUID, $userUID, $items);

            if (!$isDraft) {
                $this->dbwrite_model->saveStockMovements($transUID, $this->pageModuleUID, $orgUID, $userUID, $items, $this->_branchUID());
            }

            // Record payment DB rows inside the transaction; ledger entries applied after commit
            $paidAmountForLedger = 0;
            $firstPaymentUID     = null;
            if (!$isDraft && (int) getPostValue($PostData, 'RecordPayment') === 1) {
                $payResult           = $this->_savePaymentRecord($transUID, $orgUID, $userUID, 'C', $customerUID, $netAmount, $PostData, 'In', $transDate);
                $paidAmountForLedger = $payResult['totalPaid'];
                $firstPaymentUID     = $payResult['firstPaymentUID'];
                if ($paidAmountForLedger > 0) {
                    $this->_updateTransactionBalance($transUID, $netAmount, $paidAmountForLedger, $userUID);
                }
            }

            // Apply On Account payments to this invoice
            // JSON format: [{"PaymentUID":101,"ApplyAmount":100}, ...]
            if (!$isDraft) {
                $onAccountJson = trim(getPostValue($PostData, 'OnAccountApplyJson') ?? '');
                if ($onAccountJson) {
                    $onAccountItems = json_decode($onAccountJson, true);
                    if (is_array($onAccountItems) && count($onAccountItems) > 0) {
                        $onAccountAppliedTotal = 0;
                        $now = time();

                        foreach ($onAccountItems as $item) {
                            $sourceUID   = (int)($item['PaymentUID']  ?? 0);
                            $applyAmount = round((float)($item['ApplyAmount'] ?? 0), $this->_decimals());
                            if ($sourceUID <= 0 || $applyAmount <= 0) continue;

                            $source = $this->dbwrite_model->getOnAccountPayment($sourceUID, $orgUID);
                            if (!$source) continue;

                            $sourceAmount   = round((float)$source->Amount, $this->_decimals());
                            $applyAmount    = min($applyAmount, $sourceAmount);
                            $isFullyApplied = ($applyAmount >= $sourceAmount);

                            $this->dbwrite_model->insertData('Transaction', 'PaymentsTbl', [
                                'OrgUID'                    => $orgUID,
                                'TransUID'                  => $transUID,
                                'PartyUID'                  => $customerUID,
                                'PartyType'                 => 'C',
                                'PaymentDirection'          => 'In',
                                'Amount'                    => $applyAmount,
                                'Source'                    => 'OnAccount',
                                'IsOnAccount'               => 0,
                                'OnAccountSourcePaymentUID' => $sourceUID,
                                'IsTransferredToCreditNote' => 0,
                                'IsCancelled'               => 0,
                                'IsDeleted'                 => 0,
                                'IsActive'                  => 1,
                                'CreatedBy'                 => $userUID,
                                'UpdatedBy'                 => $userUID,
                            ]);

                            if ($isFullyApplied) {
                                $this->dbwrite_model->updateData('Transaction', 'PaymentsTbl',
                                    ['OnAccountAppliedTransUID' => $transUID, 'UpdatedBy' => $userUID],
                                    ['PaymentUID' => $sourceUID, 'OrgUID' => $orgUID]
                                );
                            } else {
                                $this->dbwrite_model->updateData('Transaction', 'PaymentsTbl',
                                    ['Amount' => round($sourceAmount - $applyAmount, $this->_decimals()), 'UpdatedBy' => $userUID],
                                    ['PaymentUID' => $sourceUID, 'OrgUID' => $orgUID]
                                );
                            }

                            $onAccountAppliedTotal += $applyAmount;
                        }

                        if ($onAccountAppliedTotal > 0) {
                            $this->_updateTransactionBalance($transUID, $netAmount, $paidAmountForLedger + $onAccountAppliedTotal, $userUID);
                            $paidAmountForLedger += $onAccountAppliedTotal;
                        }
                    }
                }
            }

            // Conversion tracking
            if (!$isDraft) {
                $fromSalesOrderUID = (int) getPostValue($PostData, 'fromSalesOrderUID');
                if ($fromSalesOrderUID > 0) {
                    $this->dbwrite_model->updateTransDocStatus($fromSalesOrderUID, $orgUID, 'Converted', $userUID);
                    $this->dbwrite_model->insertConversionRecord(
                        $orgUID, $fromSalesOrderUID, 102, $transUID, $this->pageModuleUID, 'OrderToInvoice', $userUID
                    );
                }
                $fromQuotationUID = (int) getPostValue($PostData, 'fromQuotationUID');
                if ($fromQuotationUID > 0) {
                    $this->dbwrite_model->updateTransDocStatus($fromQuotationUID, $orgUID, 'Converted', $userUID);
                    $this->dbwrite_model->insertConversionRecord(
                        $orgUID, $fromQuotationUID, 101, $transUID, $this->pageModuleUID, 'QuotToInvoice', $userUID
                    );
                }
            }

            try { $this->load->library('auditlog'); $this->auditlog->log($orgUID, $userUID, 'CREATE_INVOICE', 'Invoice', $transUID, $uniqueNumber ?? 'Draft', ['status' => ($isDraft ? 'Draft' : 'Issued'), 'netAmount' => $netAmount, 'customerUID' => $customerUID], ($isDraft ? 'Saved draft invoice' : 'Created invoice') . ' ' . ($uniqueNumber ?? 'Draft'), 'Invoices', 'TRANSACTION', 'SUCCESS', '', 'WEB', [], [], $PostData); } catch (Exception $auditEx) { log_message('error', 'Audit log failed: ' . $auditEx->getMessage()); }

            if (!$isDraft) {
                try {
                    $this->load->library('accountledger');
                    $this->accountledger->applyLedgerEntry($customerUID, 'Customer', $netAmount, 'Debit', $transUID);
                    if ($paidAmountForLedger > 0) {
                        $this->accountledger->applyLedgerEntry($customerUID, 'Customer', $paidAmountForLedger, 'Credit', $transUID);
                    }
                    $this->accountledger->postSaleJournal(
                        $transUID, $transDate, $uniqueNumber, $financialYear,
                        $netAmount, $subTotal, $cgstAmount, $sgstAmount, $igstAmount,
                        $customerUID, $userUID
                    );
                    if ($paidAmountForLedger > 0) {
                        $this->accountledger->postPaymentJournal(
                            'received', $transUID, $transDate, $financialYear,
                            $paidAmountForLedger, $customerUID, 'Customer', $userUID
                        );
                    }
                } catch (Exception $ledgerEx) {
                    log_message('error', 'Ledger update failed after invoice creation: ' . $ledgerEx->getMessage());
                }
            }

            $this->_saveAttachments($transUID);
            if (!empty($firstPaymentUID)) $this->_savePaymentAttachments($firstPaymentUID);

            $this->dbwrite_model->commitTransaction();

            if (!$isDraft) { $this->_syncProductCacheFromItems($items); }

            if (!$isDraft) {
                $this->_recalcCustomerBalance($orgUID, $customerUID, $userUID);
            }

            $this->EndReturnData->Error    = FALSE;
            $this->EndReturnData->Message  = 'Invoice created successfully.';
            $this->EndReturnData->TransUID = $transUID;
            $this->EndReturnData->Token    = $headerData['TransToken'];

        } catch (Exception $e) {
            $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    public function updateInvoice() {

        $this->EndReturnData = new stdClass();
        try {

            $this->load->model('dbwrite_model');
            $this->dbwrite_model->startTransaction();

            $PostData = $this->input->post();
            $userUID  = $this->pageData['JwtData']->User->UserUID;
            $orgUID   = $this->pageData['JwtData']->Org->OrgUID;

            $transUID = (int) getPostValue($PostData, 'TransUID');
            if ($transUID <= 0) throw new Exception('Invoice ID is required.');

            $itemsJson = $this->_validateTransForm($PostData);
            $amounts   = $this->_extractTransAmounts($PostData, $itemsJson);

            $amounts['moduleUID'] = $this->pageModuleUID;
            $customerUID = (int) getPostValue($PostData, 'customerSearch');
            $prefixUID   = $amounts['prefixUID'];
            $transNumber = $amounts['transNumber'];
            $isDraft     = $amounts['isDraft'];
            $items       = $amounts['items'];
            $dueDate     = getPostValue($PostData, 'dueDate');
            $netAmount   = $amounts['netAmount'];

            $cfg = [
                'TransType'       => 'Invoice',
                'PartyType'       => 'C',
                'PartyUID'        => $customerUID,
                'DocTypePostKey'  => 'invoiceType',
                'DispatchPostKey' => 'dispatchFrom',
                'InitialStatus'   => 'Issued',
            ];

            $this->load->model('transactions_model');
            $existing = $this->transactions_model->getTransactionById($transUID, $orgUID, $this->pageModuleUID);
            if (!$existing) throw new Exception('Invoice not found.');

            $wasNonDraft  = ($existing->DocStatus !== 'Draft');
            $existingPaid = (float)($existing->PaidAmount ?? 0);
            $newBalance   = max(0, round($netAmount - $existingPaid, $this->_decimals()));

            // Recalculate status based on payment state when editing a live (non-draft) invoice
            if (!$isDraft && $wasNonDraft && $existingPaid > 0) {
                if ($netAmount > 0 && $existingPaid >= $netAmount) {
                    $computedStatus = 'Paid';
                    $newIsFullyPaid = 1;
                } else {
                    $computedStatus = 'Partial';
                    $newIsFullyPaid = 0;
                }
            } else {
                $computedStatus = $isDraft ? 'Draft' : 'Issued';
                $newIsFullyPaid = 0;
            }

            $uniqueNumber = NULL;
            if ($existing->DocStatus === 'Draft' && !$isDraft) {
                if ($prefixUID <= 0) throw new Exception('Please select a prefix to finalise this invoice.');
                if ($transNumber <= 0) throw new Exception('Transaction number must be greater than 0.');

                $prefixData = $this->transactions_model->getTransactionsPrefixDetails(['Prefix.PrefixUID' => $prefixUID, 'Prefix.OrgUID' => $orgUID]);
                if (empty($prefixData->Data)) throw new Exception('Invalid prefix selected.');
                $prefix = $prefixData->Data[0];

                $dupCheck = $this->transactions_model->getTransactionByPrefixAndNumber($prefixUID, $transNumber, $orgUID, $this->pageModuleUID);
                if ($dupCheck) {
                    $nextSuggested = $this->transactions_model->getNextTransactionNumber($prefixUID, $orgUID, $this->pageModuleUID);
                    throw new Exception('Transaction number ' . $transNumber . ' already exists. Next available: ' . $nextSuggested . '.');
                }

                [$uniqueNumber] = $this->buildUniqueNumber($prefix, $transNumber, $amounts['transDate']);
            }

            $activeTransUID = $transUID; // tracks the final transUID (may change for draftÃ¢â€ â€™issued with newer transactions)

            $updateHeader = $this->_buildTransUpdateHeader($cfg, $amounts, $PostData, $orgUID, $userUID);
            $updateHeader['BalanceAmount'] = $newBalance;
            $updateHeader['IsFullyPaid']   = $newIsFullyPaid;
            $updateHeader['DocStatus']     = $computedStatus;

            $rawIS             = getPostValue($PostData, 'isInterState');
            $isInterState      = ($rawIS !== null && $rawIS !== '') ? (int)$rawIS : null;
            $_cc               = $this->transactions_model->getCustomerCountryCode($customerUID);
            $isForeignCustomer = $_cc !== NULL ? ($_cc === 'IN' ? 0 : 1) : NULL;
            $commonDetail = [
                'ValidityDays'      => NULL,
                'ValidityDate'      => $dueDate ?: NULL,
                'Reference'         => getPostValue($PostData, 'referenceDetails') ?: NULL,
                'Notes'             => getPostValue($PostData, 'transNotes') ?: NULL,
                'TermsConditions'   => getPostValue($PostData, 'transTermsCond') ?: NULL,
                'SignatureUID'      => (int)getPostValue($PostData, 'SignatureUID') ?: NULL,
                'PlaceOfSupplyCode' => getPostValue($PostData, 'placeOfSupplyCode') ?: NULL,
                'PlaceOfSupplyName' => getPostValue($PostData, 'placeOfSupplyName') ?: NULL,
                'IsInterState'      => $isInterState,
                'IsForeignCustomer' => $isForeignCustomer,
                'PriceListUID'      => (int)getPostValue($PostData, 'PriceListUID') ?: NULL,
                'PriceListData'     => getPostValue($PostData, 'PriceListData') ?: NULL,
            ];

            // Reverse stock if existing doc was already non-draft (edit of live invoice)
            if ($wasNonDraft) {
                $this->dbwrite_model->reverseStockMovements($transUID, $orgUID, $userUID);
            }

            if ($existing->DocStatus === 'Draft' && !$isDraft
                && $this->transactions_model->hasNewerTransactions($transUID, $orgUID, $this->pageModuleUID)) {

                $insertHeader = $this->_buildTransHeader($cfg, $amounts, $PostData, $orgUID, $userUID);
                $insertHeader['BalanceAmount'] = $newBalance;
                $insertHeader['IsFullyPaid']   = $newIsFullyPaid;
                $insertResp = $this->dbwrite_model->insertData('Transaction', 'TransactionsTbl', $insertHeader);
                if ($insertResp->Error) throw new Exception($insertResp->Message);
                $newTransUID    = $insertResp->ID;
                $activeTransUID = $newTransUID;

                $this->dbwrite_model->insertData('Transaction', 'TransDetailTbl', array_merge($commonDetail, [
                    'FinancialYear' => $amounts['financialYear'],
                    'TransUID'      => $newTransUID,
                ]));

                $this->dbwrite_model->updateData(
                    'Transaction', 'TransProductsTbl',
                    ['IsDeleted' => 1, 'IsActive' => 0, 'UpdatedBy' => $userUID],
                    ['TransUID' => $transUID, 'IsDeleted' => 0]
                );
                $this->_insertTransItems($newTransUID, $amounts['financialYear'], $orgUID, $userUID, $items);

                if (!$isDraft) {
                    $this->dbwrite_model->saveStockMovements($newTransUID, $this->pageModuleUID, $orgUID, $userUID, $items, $this->_branchUID());
                }

                $this->dbwrite_model->deleteInTransaction('Transaction', 'TransactionsTbl', ['TransUID' => $transUID]);
                $this->dbwrite_model->deleteInTransaction('Transaction', 'TransDetailTbl',  ['TransUID' => $transUID]);

            } else {
                if ($uniqueNumber !== NULL) {
                    $updateHeader['PrefixUID']    = $prefixUID;
                    $updateHeader['TransNumber']  = $transNumber;
                    $updateHeader['UniqueNumber'] = $uniqueNumber;
                }

                $updateResp = $this->dbwrite_model->updateData(
                    'Transaction', 'TransactionsTbl',
                    $updateHeader,
                    ['TransUID' => $transUID, 'OrgUID' => $orgUID, 'IsDeleted' => 0]
                );
                if ($updateResp->Error) throw new Exception($updateResp->Message);

                $this->dbwrite_model->updateData(
                    'Transaction', 'TransDetailTbl', $commonDetail,
                    ['FinancialYear' => $amounts['financialYear'], 'TransUID' => $transUID]
                );

                $this->_updateTransItems($transUID, $items, $orgUID, $amounts['financialYear'], $userUID);

                if (!$isDraft) {
                    $this->dbwrite_model->saveStockMovements($transUID, $this->pageModuleUID, $orgUID, $userUID, $items, $this->_branchUID());
                }
            }

            $this->_saveTransCharges($activeTransUID, $orgUID, $userUID, $PostData);

            // Record payment DB rows inside the transaction; ledger entries applied after commit
            $paidAmountForLedger = 0;
            $firstPaymentUID     = null;
            if (!$isDraft && (int) getPostValue($PostData, 'RecordPayment') === 1) {
                $payResult           = $this->_savePaymentRecord($activeTransUID, $orgUID, $userUID, 'C', $customerUID, $netAmount, $PostData, 'In', $amounts['transDate']);
                $paidAmountForLedger = $payResult['totalPaid'];
                $firstPaymentUID     = $payResult['firstPaymentUID'];
                if ($paidAmountForLedger > 0) {
                    $this->_updateTransactionBalance($activeTransUID, $netAmount, $paidAmountForLedger, $userUID);
                }
            }

            $this->dbwrite_model->commitTransaction();

            if (!$isDraft) { $this->_syncProductCacheByTransUID($activeTransUID); }

            try { $this->load->library('auditlog'); $this->auditlog->log($orgUID, $userUID, 'UPDATE_INVOICE', 'Invoice', $activeTransUID, $uniqueNumber ?? ($existing->UniqueNumber ?? 'Draft'), ['status' => $computedStatus, 'netAmount' => $netAmount, 'customerUID' => $customerUID], ($isDraft ? 'Updated draft invoice' : 'Updated invoice') . ' ' . ($uniqueNumber ?? ($existing->UniqueNumber ?? 'Draft')), 'Invoices', 'TRANSACTION', 'SUCCESS', '', 'WEB', [], [], $PostData); } catch (Exception $auditEx) { log_message('error', 'Audit log failed: ' . $auditEx->getMessage()); }

            // Apply ledger entries after commit so each ReadDb read sees the prior committed write
            if (!$isDraft) {
                try {
                    $this->load->library('accountledger');
                    if ($wasNonDraft) {
                        $this->accountledger->applyLedgerEntry($customerUID, 'Customer', (float) $existing->NetAmount, 'Credit', $activeTransUID);
                        $this->accountledger->reverseJournal('Invoice', $transUID, $userUID);
                    }
                    $this->accountledger->applyLedgerEntry($customerUID, 'Customer', $netAmount, 'Debit', $activeTransUID);
                    if ($paidAmountForLedger > 0) {
                        $this->accountledger->applyLedgerEntry($customerUID, 'Customer', $paidAmountForLedger, 'Credit', $activeTransUID);
                    }
                    $activeUniqueNumber = $uniqueNumber ?? ($existing->UniqueNumber ?? null);
                    $this->accountledger->postSaleJournal(
                        $activeTransUID, $amounts['transDate'], $activeUniqueNumber, $amounts['financialYear'],
                        $netAmount, $amounts['subTotal'], $amounts['cgstAmount'], $amounts['sgstAmount'], $amounts['igstAmount'],
                        $customerUID, $userUID
                    );
                    if ($paidAmountForLedger > 0) {
                        $this->accountledger->postPaymentJournal(
                            'received', $activeTransUID, $amounts['transDate'], $amounts['financialYear'],
                            $paidAmountForLedger, $customerUID, 'Customer', $userUID
                        );
                    }
                } catch (Exception $ledgerEx) {
                    log_message('error', 'Ledger update failed after invoice update: ' . $ledgerEx->getMessage());
                }
            }

            $this->transactions_model->generateAndStorePdf($activeTransUID, $orgUID, $this->pageModuleUID);

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Invoice updated successfully.';
            $this->EndReturnData->Token   = $this->_getOrCreateTransToken($activeTransUID);
            $this->_saveAttachments($activeTransUID);
            $this->_softDeleteAttachments($this->input->post('RemovedAttachIDs') ?? '');
            if (!empty($firstPaymentUID)) $this->_savePaymentAttachments($firstPaymentUID);
            $this->_recalcCustomerBalance($orgUID, $customerUID, $userUID);

        } catch (Exception $e) {
            $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }





    public function recordInvoicePayment() {

        $this->EndReturnData = new stdClass();
        try {

            $this->load->model('dbwrite_model');
            $this->dbwrite_model->startTransaction();

            $PostData = $this->input->post();
            $userUID  = $this->pageData['JwtData']->User->UserUID;
            $orgUID   = $this->pageData['JwtData']->Org->OrgUID;

            $transUID                  = (int)   getPostValue($PostData, 'TransUID');
            $paymentTypeUID            = (int)   getPostValue($PostData, 'PaymentTypeUID');
            $amount                    = (float) getPostValue($PostData, 'Amount', 'Array', 0);
            $advanceAmount             = (float) getPostValue($PostData, 'AdvanceAmount', 'Array', 0);
            $excessSourcePaymentUID    = (int)   getPostValue($PostData, 'ExcessSourcePaymentUID');
            $onAccountAmount           = (float) getPostValue($PostData, 'OnAccountAmount', 'Array', 0);
            $onAccountSourcePaymentUID = (int)   getPostValue($PostData, 'OnAccountSourcePaymentUID');
            $paymentDate               =         getPostValue($PostData, 'PaymentDate') ?: date('Y-m-d');
            $bankAccountUID            = (int)   getPostValue($PostData, 'BankAccountUID') ?: NULL;
            $referenceNo               =         getPostValue($PostData, 'ReferenceNo') ?: NULL;
            $notes                     =         getPostValue($PostData, 'Notes') ?: NULL;

            if ($transUID <= 0) throw new Exception('Invalid transaction.');
            if ($amount <= 0 && $advanceAmount <= 0 && $onAccountAmount <= 0) throw new Exception('Payment amount must be greater than 0.');
            if ($amount > 0 && $paymentTypeUID <= 0) throw new Exception('Please select a payment type.');
            if ($advanceAmount > 0 && $excessSourcePaymentUID <= 0) throw new Exception('Invalid advance payment source.');
            if ($onAccountAmount > 0 && $onAccountSourcePaymentUID <= 0) throw new Exception('Invalid on-account payment source.');

            $this->load->model('transactions_model');
            $existing = $this->transactions_model->getTransactionById($transUID, $orgUID, $this->pageModuleUID);
            if (!$existing) throw new Exception('Invoice not found.');
            if ($existing->DocStatus === 'Draft')                               throw new Exception('Cannot record payment for a Draft invoice.');
            if (in_array($existing->DocStatus, ['Cancelled', 'Rejected']))      throw new Exception('Invoice is cancelled.');

            // Lock on-account source row first (before invoice lock) to prevent race conditions
            $lockedOnAccountSource = null;
            if ($onAccountAmount > 0 && $onAccountSourcePaymentUID > 0) {
                $lockedOnAccountSource = $this->transactions_model->lockOnAccountSourcePayment(
                    $onAccountSourcePaymentUID, $orgUID, $existing->PartyUID
                );
                if (!$lockedOnAccountSource) throw new Exception('On-account payment source not found.', 1001);
                $availableOnAccount = round((float)($lockedOnAccountSource->Amount ?? 0), $this->_decimals());
                if ($availableOnAccount <= 0) throw new Exception('No on-account balance available — it may have been used by another user. Please refresh and try again.', 1001);
                $onAccountAmount = round($onAccountAmount, $this->_decimals());
                if ($onAccountAmount > $availableOnAccount) throw new Exception('On-account amount exceeds available balance.');
            }

            // Lock advance source row first (before invoice lock) to prevent race conditions
            $lockedSource = null;
            if ($advanceAmount > 0 && $excessSourcePaymentUID > 0) {
                $wdb = $this->dbwrite_model->getWriteDb();
                $wdb->db_debug = FALSE;
                $srcResult = $wdb->query(
                    'SELECT PaymentUID, ExcessAmount, IsDeleted, IsCancelled
                     FROM Transaction.PaymentsTbl
                     WHERE PaymentUID = ? AND OrgUID = ? AND PartyUID = ? AND PartyType = ?
                     FOR UPDATE',
                    [$excessSourcePaymentUID, $orgUID, $existing->PartyUID, 'C']
                );
                $lockedSource = $srcResult ? $srcResult->row() : null;
                if (!$lockedSource) throw new Exception('Advance payment source not found.');
                if ((int)($lockedSource->IsDeleted  ?? 0) === 1) throw new Exception('The advance payment source has been deleted.');
                if ((int)($lockedSource->IsCancelled ?? 0) === 1) throw new Exception('The advance payment source has been cancelled.');
                $availableExcess = round((float)($lockedSource->ExcessAmount ?? 0), $this->_decimals());
                if ($availableExcess <= 0) throw new Exception('No advance balance available — it may have been used by another user. Please refresh and try again.', 1001);
                $advanceAmount = round($advanceAmount, $this->_decimals());
                if ($advanceAmount > $availableExcess) throw new Exception('Advance amount exceeds available balance.');
            }

            // Lock the row so concurrent requests block here until we commit;
            // then re-read the paid total on WriteDB to get the authoritative current value.
            if (!$this->dbwrite_model->lockTransactionRow($transUID, $orgUID)) {
                throw new Exception('Invoice not found.');
            }
            $alreadyPaid      = $this->dbwrite_model->sumTransactionPayments($transUID, $orgUID);
            $pending          = max(0, round((float)$existing->NetAmount - $alreadyPaid, $this->_decimals()));
            $totalNewPayment  = round($amount + $advanceAmount + $onAccountAmount, $this->_decimals());

            if ($pending <= 0) {
                throw new Exception('This invoice has already been fully paid. No further payment is needed.', 1002);
            }
            if ($totalNewPayment > $pending + 0.01) {
                throw new Exception('Total payment (' . $totalNewPayment . ') exceeds remaining balance (' . $pending . '). A concurrent payment may have just been recorded.');
            }

            $newTotalPaid = round($alreadyPaid + $amount + $advanceAmount + $onAccountAmount, $this->_decimals());
            $isFullyPaid  = ($existing->NetAmount > 0 && round((float)$existing->NetAmount - $newTotalPaid, 4) <= 0) ? 1 : 0;
            $excessAmount = max(0, round((float)$amount - $pending, $this->_decimals()));
            $newStatus    = $isFullyPaid ? 'Paid' : 'Partial';

            // Resolve payment prefix + unique number (module 110)
            $payTransYear   = (int) date('Y', strtotime($paymentDate));
            $payPrefixData  = $this->transactions_model->getTransactionsPrefixDetails(['Prefix.OrgUID' => $orgUID, 'Prefix.ModuleUID' => 110]);
            $payPrefix      = !empty($payPrefixData->Data) ? $payPrefixData->Data[0] : null;
            $payPrefixUID   = $payPrefix ? (int) $payPrefix->PrefixUID : null;
            $paymentNumber  = $payPrefixUID ? $this->transactions_model->getNextPaymentNumber($payPrefixUID, $orgUID, $payTransYear) : 0;
            $payUniqueNum   = ($payPrefix && $paymentNumber > 0) ? $this->_buildPaymentUniqueNumber($payPrefix, $paymentDate, $paymentNumber) : null;
            $receiptToken   = $this->transactions_model->_generateReceiptToken();

            $freshPaymentUID = null;
            if ($amount > 0) {
                $paymentData = [
                    'OrgUID'                 => $orgUID,
                    'PaymentDate'            => $paymentDate,
                    'PaymentModuleUID'       => 110,
                    'PrefixUID'              => $payPrefixUID,
                    'PaymentNumber'          => $paymentNumber,
                    'UniqueNumber'           => $payUniqueNum,
                    'ReceiptToken'           => $receiptToken,
                    'TransYear'              => $payTransYear,
                    'TransUID'               => $transUID,
                    'ModuleUID'              => $this->pageModuleUID,
                    'PartyType'              => 'C',
                    'PartyUID'               => $existing->PartyUID,
                    'PaymentTypeUID'         => $paymentTypeUID,
                    'Amount'                 => $amount,
                    'BankAccountUID'         => $bankAccountUID,
                    'ReferenceNo'            => $referenceNo,
                    'Notes'                  => $notes,
                    'PaymentSource'          => 'Record',
                    'PaymentDirection'       => 'In',
                    'IsFullyPaid'            => ($advanceAmount > 0) ? 0 : $isFullyPaid,
                    'ExcessAmount'           => $excessAmount,
                    'IsExcessApplied'        => 0,
                    'ExcessSourcePaymentUID' => NULL,
                    'IsActive'               => 1,
                    'IsDeleted'              => 0,
                    'CreatedBy'              => $userUID,
                    'UpdatedBy'              => $userUID,
                ];
                $resp = $this->dbwrite_model->insertData('Transaction', 'PaymentsTbl', $paymentData);
                if ($resp->Error) throw new Exception($resp->Message);
                $freshPaymentUID = $resp->ID;
            }

            // Insert advance allocation memo row and reduce source ExcessAmount
            if ($advanceAmount > 0 && $lockedSource !== null) {
                // Payment number for advance memo:
                // - advance-only (amount=0): cash row was skipped so the already-generated number is unused → use it
                // - cash + advance: cash row consumed the first number → pass it as floor so ReadDB
                //   (which cannot see the uncommitted cash row) does not issue the same number again
                if ($amount > 0) {
                    $advPaymentNumber = $payPrefixUID ? $this->transactions_model->getNextPaymentNumber($payPrefixUID, $orgUID, $payTransYear, $paymentNumber) : 0;
                    $advPayUniqueNum  = ($payPrefix && $advPaymentNumber > 0) ? $this->_buildPaymentUniqueNumber($payPrefix, $paymentDate, $advPaymentNumber) : null;
                } else {
                    $advPaymentNumber = $paymentNumber;
                    $advPayUniqueNum  = $payUniqueNum;
                }

                $advPaymentData = [
                    'OrgUID'                 => $orgUID,
                    'PaymentDate'            => $paymentDate,
                    'PaymentModuleUID'       => 110,
                    'PrefixUID'              => $payPrefixUID,
                    'PaymentNumber'          => $advPaymentNumber,
                    'UniqueNumber'           => $advPayUniqueNum,
                    'ReceiptToken'           => $this->transactions_model->_generateReceiptToken(),
                    'TransYear'              => $payTransYear,
                    'TransUID'               => $transUID,
                    'ModuleUID'              => $this->pageModuleUID,
                    'PartyType'              => 'C',
                    'PartyUID'               => $existing->PartyUID,
                    'PaymentTypeUID'         => $paymentTypeUID ?: NULL,
                    'Amount'                 => $advanceAmount,
                    'Notes'                  => 'Advance credit from Payment #' . $excessSourcePaymentUID,
                    'PaymentSource'          => 'Record',
                    'PaymentDirection'       => 'In',
                    'IsFullyPaid'            => $isFullyPaid,
                    'ExcessAmount'           => 0,
                    'IsExcessApplied'        => 1,
                    'ExcessSourcePaymentUID' => $excessSourcePaymentUID,
                    'IsActive'               => 1,
                    'IsDeleted'              => 0,
                    'CreatedBy'              => $userUID,
                    'UpdatedBy'              => $userUID,
                ];
                $advResp = $this->dbwrite_model->insertData('Transaction', 'PaymentsTbl', $advPaymentData);
                if ($advResp->Error) throw new Exception($advResp->Message);

                $newExcess = round((float)$lockedSource->ExcessAmount - $advanceAmount, $this->_decimals());
                if (!isset($wdb)) { $wdb = $this->dbwrite_model->getWriteDb(); $wdb->db_debug = FALSE; }
                $wdb->query(
                    'UPDATE Transaction.PaymentsTbl SET ExcessAmount = ?, UpdatedBy = ? WHERE PaymentUID = ? AND OrgUID = ?',
                    [$newExcess, $userUID, $excessSourcePaymentUID, $orgUID]
                );
            }

            // Insert on-account allocation memo row and reduce source Amount
            $oaResp = null;
            if ($onAccountAmount > 0 && $lockedOnAccountSource !== null) {
                if ($amount > 0 || $advanceAmount > 0) {
                    // Pass the highest number used so far as the floor so ReadDB cannot
                    // re-issue a number already consumed by an uncommitted row above
                    $oaFloor         = max($paymentNumber, $advPaymentNumber ?? 0);
                    $oaPaymentNumber = $payPrefixUID ? $this->transactions_model->getNextPaymentNumber($payPrefixUID, $orgUID, $payTransYear, $oaFloor) : 0;
                    $oaPayUniqueNum  = ($payPrefix && $oaPaymentNumber > 0) ? $this->_buildPaymentUniqueNumber($payPrefix, $paymentDate, $oaPaymentNumber) : null;
                } else {
                    $oaPaymentNumber = $paymentNumber;
                    $oaPayUniqueNum  = $payUniqueNum;
                }

                $oaPaymentData = [
                    'OrgUID'                    => $orgUID,
                    'PaymentDate'               => $paymentDate,
                    'PaymentModuleUID'          => 110,
                    'PrefixUID'                 => $payPrefixUID,
                    'PaymentNumber'             => $oaPaymentNumber,
                    'UniqueNumber'              => $oaPayUniqueNum,
                    'ReceiptToken'              => $this->transactions_model->_generateReceiptToken(),
                    'TransYear'                 => $payTransYear,
                    'TransUID'                  => $transUID,
                    'ModuleUID'                 => $this->pageModuleUID,
                    'PartyType'                 => 'C',
                    'PartyUID'                  => $existing->PartyUID,
                    'PaymentTypeUID'            => $paymentTypeUID ?: NULL,
                    'Amount'                    => $onAccountAmount,
                    'Notes'                     => 'On-account credit from Payment #' . $onAccountSourcePaymentUID,
                    'PaymentSource'             => 'Record',
                    'PaymentDirection'          => 'In',
                    'IsFullyPaid'               => $isFullyPaid,
                    'ExcessAmount'              => 0,
                    'IsExcessApplied'           => 0,
                    'OnAccountSourcePaymentUID' => $onAccountSourcePaymentUID,
                    'IsActive'                  => 1,
                    'IsDeleted'                 => 0,
                    'CreatedBy'                 => $userUID,
                    'UpdatedBy'                 => $userUID,
                ];
                $oaResp = $this->dbwrite_model->insertData('Transaction', 'PaymentsTbl', $oaPaymentData);
                if ($oaResp->Error) throw new Exception($oaResp->Message);

                $newOnAccountAmt = round((float)$lockedOnAccountSource->Amount - $onAccountAmount, $this->_decimals());
                $this->transactions_model->reduceOnAccountAmount($onAccountSourcePaymentUID, $orgUID, $newOnAccountAmt, $userUID);
            }

            $resp = (object)['ID' => $freshPaymentUID ?? ($advResp->ID ?? ($oaResp->ID ?? null))];

            // Update IsFullyPaid + PaidAmount + BalanceAmount + DocStatus on the transaction
            $balanceAmount = max(0, round((float) $existing->NetAmount - $newTotalPaid, $this->_decimals()));
            $ok = $this->dbwrite_model->updateTransIsFullyPaid($transUID, $isFullyPaid, $newTotalPaid, $balanceAmount, $userUID);
            if ($ok === false) throw new Exception('Failed to update transaction balance.');

            $this->dbwrite_model->updateTransDocStatus($transUID, $orgUID, $newStatus, $userUID);

            $this->dbwrite_model->commitTransaction();

            // Ledger entries only for real cash — advance memo rows carry no new money
            if ($amount > 0) {
                try {
                    $this->load->library('accountledger');
                    $this->accountledger->applyLedgerEntry($existing->PartyUID, 'Customer', $amount, 'Credit', $transUID);
                    $this->accountledger->postPaymentJournal(
                        'received', $transUID, $paymentDate, $payTransYear,
                        $amount, $existing->PartyUID, 'Customer', $userUID
                    );
                } catch (Exception $ledgerEx) {
                    log_message('error', 'Ledger credit failed after invoice payment: ' . $ledgerEx->getMessage());
                }

                $this->_writeBankLedgerEntry(
                    $orgUID, $bankAccountUID, 'CR', $amount,
                    'Invoice', $transUID, $this->pageModuleUID,
                    $referenceNo, 'Payment received — ' . ($payUniqueNum ?? $existing->UniqueNumber ?? '#' . $transUID),
                    $paymentDate, $userUID
                );
            }

            $summaryParts = [];
            if ($amount > 0)          $summaryParts[] = 'Cash ' . $amount;
            if ($advanceAmount > 0)   $summaryParts[] = 'advance ' . $advanceAmount;
            if ($onAccountAmount > 0) $summaryParts[] = 'on-account ' . $onAccountAmount;
            $paymentSummary = implode(' + ', $summaryParts) ?: '0';
            $this->EndReturnData->Error      = FALSE;
            $this->EndReturnData->Message    = 'Payment of ' . $paymentSummary . ' recorded successfully.';
            $this->auditlog->log(
                (int) $orgUID, (int) $userUID,
                'RECORD_INVOICE_PAYMENT', 'Invoice', (int) $transUID, (string) ($existing->UniqueNumber ?? ''),
                ['amount' => $amount, 'paymentStatus' => $newStatus],
                "Recorded payment â‚¹{$amount} for invoice " . ($existing->UniqueNumber ?? "#{$transUID}"), 'Invoices',
                'PAYMENT'
            );
            $this->_recalcCustomerBalance($orgUID, $existing->PartyUID, $userUID);

            // Save any attached files
            $this->_savePaymentAttachments($resp->ID);

        } catch (Exception $e) {
            $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error     = TRUE;
            $this->EndReturnData->Message   = $e->getMessage();
            $this->EndReturnData->ErrorCode = $e->getCode() ?: 0;
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    public function deleteInvoice() {

        $this->EndReturnData = new stdClass();
        try {

            $this->load->model('dbwrite_model');
            $this->dbwrite_model->startTransaction();

            $PostData = $this->input->post();
            $userUID  = $this->pageData['JwtData']->User->UserUID;
            $orgUID   = $this->pageData['JwtData']->Org->OrgUID;

            $transUID = (int) getPostValue($PostData, 'TransUID');
            if ($transUID <= 0) throw new Exception('Invoice ID is required.');

            $this->load->model('transactions_model');
            $existing = $this->transactions_model->getTransactionById($transUID, $orgUID, $this->pageModuleUID);
            if (!$existing) throw new Exception('Invoice not found.');

            // Guard — On-Account credit applied: block delete
            $readDb = $this->load->database('ReadDB', TRUE);
            $readDb->db_debug = FALSE;
            $onAccountCheck = $readDb->query(
                'SELECT PaymentUID FROM Transaction.PaymentsTbl
                 WHERE TransUID = ? AND OrgUID = ?
                   AND OnAccountSourcePaymentUID > 0
                   AND IsDeleted = 0 AND IsCancelled = 0
                 LIMIT 1',
                [$transUID, $orgUID]
            )->row();
            if ($onAccountCheck) {
                throw new Exception(
                    'This invoice has an On-Account credit applied to it. ' .
                    'Please delete the on-account payment entry first, then delete this invoice.'
                );
            }

            // Reverse stock movements (no-op if it was a draft)
            $this->dbwrite_model->reverseStockMovements($transUID, $orgUID, $userUID);

            $this->dbwrite_model->softDeleteTransactionItems($transUID, $userUID);

            $this->dbwrite_model->softDeleteTransaction($transUID, $orgUID, $userUID);

            $this->dbwrite_model->commitTransaction();

            $this->_syncProductCacheByTransUID($transUID); // after commit — ReadDB now sees reverted stock

            // Reverse customer ledger AFTER commit â€” runs in auto-commit mode so
            // any audit-log failure cannot roll back the already-committed delete.
            if ($existing->DocStatus !== 'Draft' && $existing->PartyType === 'C' && $existing->PartyUID > 0) {
                $netAmount = (float) $existing->NetAmount;
                if ($netAmount > 0) {
                    // Only reverse the UNPAID balance. Payments were already credited to
                    // the ledger when they were recorded; reversing them again would
                    // double-subtract and corrupt the customer balance.
                    $payments    = $this->transactions_model->getTransactionPayments($transUID, $orgUID);
                    $alreadyPaid = array_sum(array_column((array) $payments, 'Amount'));
                    $remaining   = max(0, round($netAmount - $alreadyPaid, $this->_decimals()));

                    try {
                        $this->load->library('accountledger');
                        if ($remaining > 0) {
                            $this->accountledger->applyLedgerEntry($existing->PartyUID, 'Customer', $remaining, 'Credit', $transUID);
                        }
                        $this->accountledger->reverseJournal('Invoice', $transUID, $userUID);
                    } catch (Exception $ledgerEx) {
                        log_message('error', 'Ledger reversal failed after invoice delete: ' . $ledgerEx->getMessage());
                    }
                }
            }

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Invoice deleted successfully.';

            // â”€â”€ Payment handling for deleted invoices â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
            // No DocStatus gate â€” invoice can be 'Issued' and still have payments.
            // If no payments exist the UPDATEs affect 0 rows, which is harmless.
            if ($existing->PartyType === 'C' && $existing->PartyUID > 0) {

                $this->load->library('customerbalance');

                // Read action from TransSettings (allow POST override for 'ask' case)
                $transSettings = $this->pageData['JwtData']->TransSettings ?? null;
                $cancelAction  = $transSettings->InvoiceCancelAction ?? 'ask';
                $postAction    = trim($this->input->post('CancelPaymentAction') ?? '');
                if (in_array($postAction, ['credit_note', 'refund', 'cancel_only'])) {
                    $cancelAction = $postAction;
                }

                // For credit_note: create the credit note BEFORE marking IsDeleted=1 so that
                // createCreditNote() can still find payments (it filters IsDeleted=0).
                // For refund / cancel_only: no credit note â€” IsDeleted=1 alone is sufficient.
                if ($cancelAction === 'credit_note') {
                    $this->customerbalance->createCreditNote(
                        $orgUID, (int)$existing->PartyUID, $transUID, $userUID, $existing->UniqueNumber ?? ''
                    );
                }

                // DELETE rule: mark all payments as IsDeleted = 1
                $this->dbwrite_model->markPaymentsDeletedForTrans($transUID, $orgUID, $userUID);
            }

            if ($existing->PartyType === 'C') {
                $this->_recalcCustomerBalance($orgUID, $existing->PartyUID, $userUID);
            }
            try { $this->load->library('auditlog'); $this->auditlog->log($orgUID, $userUID, 'DELETE_INVOICE', 'Invoice', $transUID, $existing->UniqueNumber ?? '', ['status' => $existing->DocStatus, 'netAmount' => $existing->NetAmount], 'Deleted invoice ' . ($existing->UniqueNumber ?? "#{$transUID}"), 'Invoices', 'TRANSACTION'); } catch (Exception $auditEx) { log_message('error', 'Audit log failed: ' . $auditEx->getMessage()); }

            $this->_buildListResponse('transactions/invoices/list', '/transactions/getPageDetails/103');

        } catch (Exception $e) {
            $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    public function duplicateInvoice() {

        $this->EndReturnData = new stdClass();
        try {

            $this->load->model('dbwrite_model');
            $this->dbwrite_model->startTransaction();

            $PostData = $this->input->post();
            $srcUID   = (int) getPostValue($PostData, 'TransUID');
            $userUID  = $this->pageData['JwtData']->User->UserUID;
            $orgUID   = $this->pageData['JwtData']->Org->OrgUID;

            if ($srcUID <= 0) throw new Exception('Invalid invoice.');

            $this->load->model('transactions_model');
            $src = $this->transactions_model->getTransactionById($srcUID, $orgUID, $this->pageModuleUID);
            if (!$src) throw new Exception('Invoice not found.');

            $nextNumber   = $this->transactions_model->getNextTransactionNumber($src->PrefixUID, $orgUID, $this->pageModuleUID);
            $prefixResult = $this->transactions_model->getTransactionsPrefixDetails(['Prefix.PrefixUID' => $src->PrefixUID, 'Prefix.OrgUID' => $orgUID]);
            $prefix       = $prefixResult->Data[0] ?? null;
            if (!$prefix) throw new Exception('Prefix not found.');

            $sep   = $prefix->Separator ?? '-';
            $parts = [strtoupper($prefix->Name)];
            if (!empty($prefix->IncludeShortName) && !empty($prefix->ShortName)) {
                $parts[] = strtoupper($prefix->ShortName);
            }
            if (!empty($prefix->IncludeFiscalYear)) {
                $m  = (int) date('m');
                $yr = (int) date('Y');
                $fy = $m >= 4 ? $yr : $yr - 1;
                $parts[] = ($prefix->FiscalYearFormat ?? 'SHORT') === 'LONG'
                    ? $fy . '-' . ($fy + 1)
                    : str_pad($fy % 100, 2, '0', STR_PAD_LEFT) . '-' . str_pad(($fy + 1) % 100, 2, '0', STR_PAD_LEFT);
            }
            $pad     = (int)($prefix->NumberPadding ?? 1);
            $parts[] = $pad > 1 ? str_pad($nextNumber, $pad, '0', STR_PAD_LEFT) : (string) $nextNumber;
            $uniqueNumber = implode($sep, $parts);

            $today = date('Y-m-d');

            $headerData = [
                'OrgUID'            => $orgUID,
                'ModuleUID'         => $this->pageModuleUID,
                'PrefixUID'         => $src->PrefixUID,
                'UniqueNumber'      => $uniqueNumber,
                'TransType'         => 'Invoice',
                'TransNumber'       => $nextNumber,
                'PartyType'         => $src->PartyType,
                'PartyUID'          => $src->PartyUID,
                'TransDate'         => $today,
                'DocType'     => $src->DocType,
                'DispatchFrom'      => $src->DispatchFrom ?? NULL,
                'TotalQuantity'     => (float)($src->TotalQuantity ?? 0),
                'TotalItems'        => (int)($src->TotalItems ?? 0),
                'GrossAmount'       => $src->GrossAmount,
                'SubTotal'          => $src->SubTotal,
                'DiscountAmount'    => $src->DiscountAmount,
                'AdditionalCharges' => $src->AdditionalCharges,
                'TaxAmount'         => $src->TaxAmount,
                'CgstAmount'        => $src->CgstAmount,
                'SgstAmount'        => $src->SgstAmount,
                'IgstAmount'        => $src->IgstAmount,
                'RoundOff'          => $src->RoundOff,
                'GlobalDiscPercent' => (float) $src->GlobalDiscPercent,
                'ExtraDiscApplied'  => $src->ExtraDiscApplied,
                'ExtraDiscAmount'   => $src->ExtraDiscAmount,
                'ExtraDiscType'     => $src->ExtraDiscType,
                'NetAmount'         => $src->NetAmount,
                'DocStatus'         => 'Draft',
                'IsActive'          => 1,
                'IsDeleted'         => 0,
                'CreatedBy'         => $userUID,
                'UpdatedBy'         => $userUID,
            ];
            $insertResp = $this->dbwrite_model->insertData('Transaction', 'TransactionsTbl', $headerData);
            if ($insertResp->Error) throw new Exception($insertResp->Message);
            $newTransUID = $insertResp->ID;

            $_srcCC            = $src->PartyCountryCode ?? NULL;
            $detailData = [
                'FinancialYear'     => (int) date('Y'),
                'TransUID'          => $newTransUID,
                'ValidityDays'      => NULL,
                'ValidityDate'      => NULL,
                'Reference'         => $src->Reference       ?? NULL,
                'Notes'             => $src->Notes           ?? NULL,
                'TermsConditions'   => $src->TermsConditions ?? NULL,
                'SignatureUID'      => $src->SignatureUID     ?? NULL,
                'AdditionalCharges' => $src->AdditionalChargesJson ?? NULL,
                'IsInterState'      => ($src->IgstAmount ?? 0) > 0 ? 1 : (($src->CgstAmount ?? 0) > 0 || ($src->SgstAmount ?? 0) > 0 ? 0 : NULL),
                'IsForeignCustomer' => $_srcCC !== NULL ? ($_srcCC === 'IN' ? 0 : 1) : NULL,
            ];
            $this->dbwrite_model->insertData('Transaction', 'TransDetailTbl', $detailData);

            $srcItems = $this->transactions_model->getTransactionItems($srcUID, $orgUID);
            $now      = time();
            foreach ($srcItems as $seq => $item) {
                $itemRow = [
                    'OrgUID'            => $orgUID,
                    'FinancialYear'     => (int) date('Y'),
                    'TransUID'          => $newTransUID,
                    'ItemSequence'      => $seq + 1,
                    'ProductUID'        => $item->ProductUID,
                    'ProductName'       => $item->ProductName,
                    'PartNumber'        => $item->PartNumber,
                    'CategoryUID'       => $item->CategoryUID,
                    'StorageUID'        => $item->StorageUID,
                    'Quantity'          => $item->Quantity,
                    'PrimaryUnitName'   => $item->PrimaryUnitName,
                    'TaxDetailsUID'     => $item->TaxDetailsUID,
                    'TaxPercentage'     => $item->TaxPercentage,
                    'CGST'              => $item->CGST,
                    'SGST'              => $item->SGST,
                    'IGST'              => $item->IGST,
                    'DiscountTypeUID'   => $item->DiscountTypeUID,
                    'Discount'          => $item->Discount,
                    'UnitPrice'         => $item->UnitPrice,
                    'SellingPrice'      => $item->SellingPrice,
                    'TaxableAmount'     => $item->TaxableAmount,
                    'CgstAmount'        => $item->CgstAmount,
                    'SgstAmount'        => $item->SgstAmount,
                    'IgstAmount'        => $item->IgstAmount,
                    'TaxAmount'         => $item->TaxAmount,
                    'DiscountAmount'    => $item->DiscountAmount,
                    'NetAmount'         => $item->NetAmount,
                    'QuantityConverted' => 0,
                    'IsActive'          => 1,
                    'IsDeleted'         => 0,
                    'CreatedBy'         => $userUID,
                    'UpdatedBy'         => $userUID,
                ];
                $this->dbwrite_model->insertData('Transaction', 'TransProductsTbl', $itemRow);
            }

            $this->dbwrite_model->commitTransaction();

            $this->EndReturnData->Error    = FALSE;
            $this->EndReturnData->Message  = 'Invoice duplicated as ' . $uniqueNumber . '.';
            $this->EndReturnData->TransUID = $newTransUID;
            $this->EndReturnData->EditURL  = '/invoices/' . $headerData['TransToken'] . '/edit';

        } catch (Exception $e) {
            $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    public function updateInvoiceStatus() {

        $this->EndReturnData = new stdClass();
        try {

            $this->load->model('dbwrite_model');
            $PostData  = $this->input->post();
            $transUID  = (int) getPostValue($PostData, 'TransUID');
            $newStatus = trim(getPostValue($PostData, 'Status'));
            $userUID   = $this->pageData['JwtData']->User->UserUID;
            $orgUID    = $this->pageData['JwtData']->Org->OrgUID;

            if ($transUID <= 0) throw new Exception('Invalid invoice.');

            $validTransitions = [
                'Draft'     => ['Issued', 'Cancelled'],
                'Issued'    => ['Paid', 'Partial', 'Cancelled'],
                'Partial'   => ['Paid', 'Cancelled'],
                'Paid'      => ['Cancelled'],
                'Cancelled' => [],
            ];

            $this->load->model('transactions_model');
            $existing = $this->transactions_model->getTransactionById($transUID, $orgUID, $this->pageModuleUID);
            if (!$existing) throw new Exception('Invoice not found.');

            $current = $existing->DocStatus;
            if (!in_array($newStatus, $validTransitions[$current] ?? [])) {
                throw new Exception("Cannot change status from {$current} to {$newStatus}.");
            }

            // ── Advance payment guards (runs before the write transaction) ────────
            if ($newStatus === 'Cancelled') {
                $readDb = $this->load->database('ReadDB', TRUE);
                $readDb->db_debug = FALSE;

                // Guard A — Invoice 2 case: this invoice has an advance allocation row applied TO it
                $advOnThis = $readDb->query(
                    'SELECT p.PaymentUID, src.TransUID AS SourceTransUID
                     FROM Transaction.PaymentsTbl p
                     LEFT JOIN Transaction.PaymentsTbl src ON src.PaymentUID = p.ExcessSourcePaymentUID
                     WHERE p.TransUID = ? AND p.OrgUID = ? AND p.IsExcessApplied = 1
                       AND p.IsDeleted = 0 AND p.IsCancelled = 0
                     LIMIT 1',
                    [$transUID, $orgUID]
                )->row();
                if ($advOnThis) {
                    throw new Exception(
                        'This invoice has an advance payment applied to it (from a previous overpayment). ' .
                        'Please go to the Payments module and delete that advance payment first, then cancel this invoice.'
                    );
                }

                // Guard B — Invoice 1 case: a payment on this invoice has its excess applied elsewhere
                $advFromThis = $readDb->query(
                    'SELECT linked.TransUID AS LinkedTransUID
                     FROM Transaction.PaymentsTbl src
                     INNER JOIN Transaction.PaymentsTbl linked
                             ON linked.ExcessSourcePaymentUID = src.PaymentUID
                     WHERE src.TransUID = ? AND src.OrgUID = ?
                       AND src.IsDeleted = 0 AND src.IsCancelled = 0
                       AND linked.IsDeleted = 0 AND linked.IsCancelled = 0
                     LIMIT 1',
                    [$transUID, $orgUID]
                )->row();
                if ($advFromThis) {
                    throw new Exception(
                        'This invoice\'s payment has advance credit currently applied to another invoice. ' .
                        'Please go to the Payments module and delete that advance payment first, then cancel this invoice.'
                    );
                }

                // Guard C — Invoice has an On-Account credit applied to it
                $onAccountOnThis = $readDb->query(
                    'SELECT PaymentUID FROM Transaction.PaymentsTbl
                     WHERE TransUID = ? AND OrgUID = ?
                       AND OnAccountSourcePaymentUID > 0
                       AND IsDeleted = 0 AND IsCancelled = 0
                     LIMIT 1',
                    [$transUID, $orgUID]
                )->row();
                if ($onAccountOnThis) {
                    throw new Exception(
                        'This invoice has an On-Account credit applied to it. ' .
                        'Please delete the on-account payment entry first, then cancel this invoice.'
                    );
                }
            }

            $this->dbwrite_model->startTransaction();
            $updateData = ['DocStatus' => $newStatus, 'UpdatedBy' => $userUID];
            if ($newStatus === 'Cancelled') {
                $updateData['IsCancelled'] = 1;
                $cancelReason = trim(getPostValue($PostData, 'CancelReason') ?? '');
                if ($cancelReason !== '') {
                    $updateData['CancelReason'] = $cancelReason;
                }
            }
            $resp = $this->dbwrite_model->updateData(
                'Transaction', 'TransactionsTbl',
                $updateData,
                ['TransUID' => $transUID, 'OrgUID' => $orgUID, 'IsDeleted' => 0]
            );
            if ($resp->Error) throw new Exception($resp->Message);

            // Cascade IsCancelled = 1 to all child records
            if ($newStatus === 'Cancelled') {
                $this->dbwrite_model->cancelTransactionChildRecords($transUID, $userUID);

                // Reverse stock movements (no-op if the invoice was a draft)
                $this->dbwrite_model->reverseStockMovements($transUID, $orgUID, $userUID);

                // Mark payments IsCancelled = 1 when "Mark Refund" is selected
                $cancelPaymentAction = trim($this->input->post('CancelPaymentAction') ?? '');
                if ($cancelPaymentAction === 'refund') {
                    $this->dbwrite_model->updateData('Transaction', 'PaymentsTbl',
                        ['IsCancelled' => 1, 'UpdatedBy' => $userUID],
                        ['TransUID' => $transUID, 'IsDeleted' => 0]
                    );
                }
            }

            $this->dbwrite_model->commitTransaction();

            if ($newStatus === 'Cancelled') {
                $this->_syncProductCacheByTransUID($transUID); // after commit — ReadDB now sees reverted stock
            }

            $this->EndReturnData->Error     = FALSE;
            $this->EndReturnData->Message   = 'Invoice cancelled successfully.';
            $this->EndReturnData->NewStatus = $newStatus;

            try { $this->load->library('auditlog'); $this->auditlog->log($orgUID, $userUID, strtoupper($newStatus) . '_INVOICE', 'Invoice', $transUID, $existing->UniqueNumber ?? '', ['fromStatus' => $current, 'toStatus' => $newStatus], ucfirst($newStatus) . ' invoice ' . ($existing->UniqueNumber ?? "#{$transUID}"), 'Invoices', 'TRANSACTION'); } catch (Exception $auditEx) { log_message('error', 'Audit log failed: ' . $auditEx->getMessage()); }

            if ($newStatus === 'Cancelled' && $existing->PartyType === 'C') {
                $this->load->library('customerbalance');

                // â”€â”€ Determine cancel action â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
                // Priority: explicit POST param (user made decision) â†’ JWT setting â†’ default 'ask'
                $transSettings = $this->pageData['JwtData']->TransSettings ?? null;
                $cancelAction  = $transSettings->InvoiceCancelAction ?? 'ask';

                $postAction = trim($this->input->post('CancelPaymentAction') ?? '');
                if (in_array($postAction, ['credit_note', 'refund', 'cancel_only'])) {
                    $cancelAction = $postAction; // user explicitly chose via 'ask' modal
                }

                // â”€â”€ Handle payments for every cancelled invoice â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
                // Do NOT gate on DocStatus â€” an invoice can be 'Issued' and still
                // have payments recorded. If no payments exist, the UPDATE affects
                // 0 rows which is harmless.

                if ($cancelAction === 'cancel_only') {
                    // Mark payments as On Account â€” money held by org, reusable on a future invoice
                    $this->dbwrite_model->markPaymentsOnAccount($transUID, $orgUID, $userUID);

                } elseif ($cancelAction === 'refund') {
                    // Directly set IsCancelled = 1 on all payments for this invoice.
                    // Excludes them from TotalReceived â†’ balance returns to pre-invoice state.
                    $this->dbwrite_model->markPaymentsRefunded($transUID, $orgUID, $userUID);

                } else {
                    // credit_note / ask â†’ create a Pending credit note for the paid portion
                    $cnResult = $this->customerbalance->createCreditNote(
                        $orgUID, (int)$existing->PartyUID, $transUID, $userUID, $existing->UniqueNumber ?? ''
                    );

                    if ($cnResult) {
                        $this->EndReturnData->CreditNote       = $cnResult['creditNoteUID'];
                        $this->EndReturnData->CreditNoteAmount = $cnResult['amount'];

                        if ($cancelAction === 'ask') {
                            $this->EndReturnData->CreditNoteStatus = 'Pending';
                            $this->EndReturnData->NeedsDecision    = true;
                        } else {
                            $this->EndReturnData->CreditNoteStatus = 'Pending';
                        }
                    }
                }

                // â”€â”€ Recalculate and sync balance for all cases â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
                $balResult = $this->customerbalance->recalcAndSync($orgUID, (int)$existing->PartyUID, $userUID);
                if ($balResult) {
                    $this->EndReturnData->CustomerBalance     = $balResult['balance'];
                    $this->EndReturnData->CustomerBalanceType = $balResult['type'];
                }
            }

        } catch (Exception $e) {
            $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }







    public function getPaymentAttachments() {

        $this->EndReturnData = new stdClass();
        try {

            $transUID = (int) $this->input->post('TransUID');
            $orgUID   = $this->pageData['JwtData']->Org->OrgUID;
            if ($transUID <= 0) throw new Exception('Invalid transaction.');

            $this->load->model('transactions_model');
            $payments = $this->transactions_model->getTransactionPayments($transUID, $orgUID);
            
            $attachments = [];
            foreach ($payments as $payment) {
                $paymentAttachments = $this->transactions_model->getPaymentAttachments($payment->PaymentUID, $orgUID);
                foreach ($paymentAttachments as $attach) {
                    $attach->PaymentTypeName = $payment->PaymentTypeName;
                    $attach->PaymentAmount = $payment->Amount;
                    $attach->PaymentUniqueNumber = $payment->UniqueNumber ?? null;
                    $attachments[] = $attach;
                }
            }

            $this->EndReturnData->Error       = FALSE;
            $this->EndReturnData->Attachments = $attachments;

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    public function create() {

        try {

            $orgUID = $this->pageData['JwtData']->Org->OrgUID;
            $this->pageData['JwtData']->ModuleUID = $this->pageModuleUID;

            $this->load->model('transactions_model');

            $prefixResult                    = $this->transactions_model->getTransactionsPrefixDetails(['Prefix.OrgUID' => $orgUID, 'Prefix.ModuleUID' => $this->pageModuleUID]);
            $this->pageData['PrefixData']    = $prefixResult->Data ?? [];

            $nextNumberMap = [];
            foreach ($this->pageData['PrefixData'] as $pd) {
                $nextNumberMap[(int)$pd->PrefixUID] = $this->transactions_model->getNextTransactionNumber(
                    $pd->PrefixUID, $orgUID, $this->pageModuleUID
                );
            }
            $this->pageData['NextNumberMap'] = $nextNumberMap;

            // Pre-fill from Sales Order if converting
            $fromSOUID = (int) $this->input->get('fromSalesOrder');
            $this->pageData['FromSalesOrderUID'] = $fromSOUID;
            $this->pageData['SalesOrderData']    = null;
            $this->pageData['SalesOrderItems']   = [];
            if ($fromSOUID > 0) {
                $soData  = $this->transactions_model->getTransactionById($fromSOUID, $orgUID, 102);
                $soItems = $soData ? $this->transactions_model->getTransactionItems($fromSOUID, $orgUID) : [];
                $this->pageData['SalesOrderData']  = $soData;
                $this->pageData['SalesOrderItems'] = $soItems;
            }

            // Pre-fill from Quotation if converting directly
            $fromQuotationUID = (int) $this->input->get('fromQuotation');
            $this->pageData['FromQuotationUID'] = $fromQuotationUID;
            $this->pageData['QuotationData']    = null;
            $this->pageData['QuotationItems']   = [];
            if ($fromQuotationUID > 0) {
                $quotData  = $this->transactions_model->getTransactionById($fromQuotationUID, $orgUID, 101);
                $quotItems = $quotData ? $this->transactions_model->getTransactionItems($fromQuotationUID, $orgUID) : [];
                $this->pageData['QuotationData']  = $quotData;
                $this->pageData['QuotationItems'] = $quotItems;
            }

            // Pre-fill from Pro Forma Invoice if converting
            $fromProFormaUID = (int) $this->input->get('fromProForma');
            $this->pageData['FromProFormaUID'] = $fromProFormaUID;
            $this->pageData['ProFormaData']    = null;
            $this->pageData['ProFormaItems']   = [];
            if ($fromProFormaUID > 0) {
                $pfData  = $this->transactions_model->getTransactionById($fromProFormaUID, $orgUID, 113);
                $pfItems = $pfData ? $this->transactions_model->getTransactionItems($fromProFormaUID, $orgUID) : [];
                $this->pageData['ProFormaData']  = $pfData;
                $this->pageData['ProFormaItems'] = $pfItems;
            }

            // Pre-fill from Delivery Challan if converting
            $fromChallanUID = (int) $this->input->get('fromChallan');
            $this->pageData['FromChallanUID'] = $fromChallanUID;
            $this->pageData['ChallanData']    = null;
            $this->pageData['ChallanItems']   = [];
            if ($fromChallanUID > 0) {
                $challanData  = $this->transactions_model->getTransactionById($fromChallanUID, $orgUID, 112);
                $challanItems = $challanData ? $this->transactions_model->getTransactionItems($fromChallanUID, $orgUID) : [];
                $this->pageData['ChallanData']  = $challanData;
                $this->pageData['ChallanItems'] = $challanItems;
            }

            $this->_getDispatchAddresses($orgUID);

            $this->pageData['PaymentTypes']       = $this->transactions_model->getPaymentTypesList();
            $this->pageData['BankAccounts']       = $this->transactions_model->getOrgBankAccounts($orgUID);
            $this->pageData['AdditionalCharges']  = $this->_getAdditionalChargesForOrg((int)$orgUID, true);
            $this->pageData['TaxList']            = $this->_getTaxList();
            $this->pageData['TransactionCharges'] = [];
            $this->pageData['IsEditMode']         = false;

            $this->load->view('transactions/invoices/forms/form', $this->pageData);

        } catch (Exception $e) {
            redirect('invoices', 'refresh');
        }

    }

    public function edit($token = '') {

        try {

            $token = trim((string) $token);
            if (empty($token)) redirect('invoices');

            $orgUID = $this->pageData['JwtData']->Org->OrgUID;

            $this->load->model('transactions_model');
            $invData = $this->transactions_model->getTransactionByToken($token, $orgUID, $this->pageModuleUID);
            if (!$invData) redirect('invoices');

            $transUID = (int) $invData->TransUID;

            $this->pageData['JwtData']->ModuleUID = $this->pageModuleUID;

            $invItems = $this->transactions_model->getTransactionItems($transUID, $orgUID);

            $this->load->model('customers_model');
            $custAddr = $this->customers_model->getCustomerAddress(['CustAddress.CustomerUID' => $invData->PartyUID, 'CustAddress.OrgUID' => $orgUID]);
            $shipping = current(array_filter($custAddr, fn($a) => $a->AddressType === 'Shipping'));
            $billing  = current(array_filter($custAddr, fn($a) => $a->AddressType === 'Billing'));
            $this->pageData['CustAddr'] = $shipping ?: ($billing ?: ($custAddr[0] ?? null));

            $this->pageData['InvData']  = $invData;
            $this->pageData['InvItems'] = $invItems;

            $prefixResult                    = $this->transactions_model->getTransactionsPrefixDetails(['Prefix.OrgUID' => $orgUID, 'Prefix.ModuleUID' => $this->pageModuleUID]);
            $this->pageData['PrefixData']    = $prefixResult->Data ?? [];

            $nextNumberMap = [];
            foreach ($this->pageData['PrefixData'] as $pd) {
                $nextNumberMap[(int)$pd->PrefixUID] = $this->transactions_model->getNextTransactionNumber(
                    $pd->PrefixUID, $orgUID, $this->pageModuleUID
                );
            }
            $this->pageData['NextNumberMap'] = $nextNumberMap;

            $this->_getDispatchAddresses($orgUID);

            $this->pageData['PaymentTypes']       = $this->transactions_model->getPaymentTypesList();
            $this->pageData['BankAccounts']       = $this->transactions_model->getOrgBankAccounts($orgUID);
            $this->pageData['InvAttachments']     = $this->transactions_model->getTransactionAttachments($transUID, $orgUID);
            $this->pageData['AdditionalCharges']  = $this->_getAdditionalChargesForOrg((int)$orgUID, true);
            $this->pageData['TransactionCharges'] = $this->transactions_model->getTransactionCharges($transUID, (int)$orgUID);
            $this->pageData['TaxList']            = $this->_getTaxList();
            $this->pageData['IsEditMode']         = true;

            $this->load->view('transactions/invoices/forms/form', $this->pageData);

        } catch (Exception $e) {
            redirect('invoices', 'refresh');
        }

    }


    public function getInvoicePdfBase64() {

        $this->EndReturnData = new stdClass();
        try {

            $transUID  = (int) $this->input->post('TransUID');
            $paperSize = strtoupper(trim($this->input->post('PaperSize') ?: 'A4'));
            $orgUID    = $this->pageData['JwtData']->Org->OrgUID;

            if ($transUID <= 0) throw new Exception('Invalid invoice.');

            $this->load->model('transactions_model');
            $invoice = $this->transactions_model->getTransactionById($transUID, $orgUID, $this->pageModuleUID);
            if (!$invoice) throw new Exception('Invoice not found.');

            $pdfBytes = $this->transactions_model->generateInvoicePdfBytes($transUID, $orgUID, $paperSize);
            if (!$pdfBytes) throw new Exception('Failed to generate PDF.');

            $filename = preg_replace('/[^A-Za-z0-9_\-]/', '_', $invoice->UniqueNumber ?? ('Invoice_' . $transUID)) . '.pdf';

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

    // â”€â”€ Apply a pending credit note to a future invoice â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    public function applyCreditNote() {
        $this->EndReturnData = new stdClass();
        try {
            $orgUID        = $this->pageData['JwtData']->Org->OrgUID;
            $userUID       = $this->pageData['JwtData']->User->UserUID;
            $creditNoteUID = (int) $this->input->post('CreditNoteUID');
            $targetTransUID= (int) $this->input->post('TransUID');

            if ($creditNoteUID <= 0) throw new Exception('Credit note ID is required.');
            if ($targetTransUID <= 0) throw new Exception('Target invoice ID is required.');

            $this->load->library('customerbalance');
            $result = $this->customerbalance->applyCreditNote($orgUID, $creditNoteUID, $targetTransUID, $userUID);

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Credit note applied successfully.';
            $this->EndReturnData->Data    = $result;

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // â”€â”€ Refund a pending credit note (org returns money to customer) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    public function refundCreditNote() {
        $this->EndReturnData = new stdClass();
        try {
            $orgUID        = $this->pageData['JwtData']->Org->OrgUID;
            $userUID       = $this->pageData['JwtData']->User->UserUID;
            $creditNoteUID = (int) $this->input->post('CreditNoteUID');

            if ($creditNoteUID <= 0) throw new Exception('Credit note ID is required.');

            $this->load->library('customerbalance');
            $this->customerbalance->refundCreditNote($orgUID, $creditNoteUID, $userUID);

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Credit note marked as refunded.';

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── Cancel a pending credit note + its linked payment (reversible flag only) ─────────────────

    public function cancelCreditNote(): void {
        $this->EndReturnData = new stdClass();
        try {
            $orgUID        = $this->pageData['JwtData']->Org->OrgUID;
            $userUID       = $this->pageData['JwtData']->User->UserUID;
            $creditNoteUID = (int) $this->input->post('CreditNoteUID');
            $notes         = trim($this->input->post('Notes') ?: '');

            if ($creditNoteUID <= 0) throw new Exception('Invalid Credit Note.');

            $readDb = $this->load->database('ReadDB', TRUE);
            $readDb->db_debug = FALSE;
            $readDb->from('Transaction.TransCreditNoteTbl');
            $readDb->where(['CreditNoteUID' => $creditNoteUID, 'OrgUID' => (int)$orgUID, 'IsDeleted' => 0, 'IsCancelled' => 0]);
            $cn = $readDb->get()->row();

            if (!$cn) throw new Exception('Credit Note not found.');
            if ($cn->Status !== 'Pending') throw new Exception('Only Pending Credit Notes can be cancelled. This Credit Note is ' . $cn->Status . '.');

            $this->load->model('dbwrite_model');
            $this->dbwrite_model->startTransaction();

            $wdb = $this->dbwrite_model->getWriteDb();
            $wdb->db_debug = FALSE;

            // Cancel the linked payment
            $wdb->where([
                'TransUID'                  => (int)$cn->SourceTransUID,
                'OrgUID'                    => (int)$orgUID,
                'IsTransferredToCreditNote' => 1,
                'IsDeleted'                 => 0,
            ]);
            $wdb->update('Transaction.PaymentsTbl', ['IsCancelled' => 1, 'IsActive' => 0, 'UpdatedBy' => $userUID]);

            // Cancel the credit note
            $cnUpdate = ['IsCancelled' => 1, 'IsActive' => 0, 'Status' => 'Cancelled', 'UpdatedBy' => $userUID];
            if ($notes !== '') $cnUpdate['CancelReason'] = $notes;
            $wdb->where(['CreditNoteUID' => $creditNoteUID, 'OrgUID' => (int)$orgUID]);
            $wdb->update('Transaction.TransCreditNoteTbl', $cnUpdate);

            $this->dbwrite_model->commitTransaction();

            if ((int)($cn->PartyUID ?? 0) > 0) {
                $this->load->library('customerbalance');
                $balResult = $this->customerbalance->recalcAndSync($orgUID, (int)$cn->PartyUID, $userUID);
                if ($balResult) {
                    $this->EndReturnData->CustomerBalance     = $balResult['balance'];
                    $this->EndReturnData->CustomerBalanceType = $balResult['type'];
                }
            }

            try {
                $cnNumber = $cn->CreditNoteNumber ?: '';
                $cnLabel  = $cnNumber ?: '#' . $creditNoteUID;
                $this->load->library('auditlog');
                $this->auditlog->log(
                    $orgUID, $userUID, 'CANCEL_CREDIT_NOTE', 'CreditNote', $creditNoteUID,
                    $cnNumber,
                    ['amount' => $cn->Amount, 'notes' => $notes],
                    'Cancelled Credit Note ' . $cnLabel,
                    'Invoices', 'TRANSACTION'
                );
            } catch (Exception $auditEx) {
                log_message('error', 'Audit log failed: ' . $auditEx->getMessage());
            }

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Credit Note cancelled successfully.';

        } catch (Exception $e) {
            $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── Delete a pending credit note + its linked payment ────────────────────────────────────────

    public function deleteCreditNote(): void {
        $this->EndReturnData = new stdClass();
        try {
            $orgUID        = $this->pageData['JwtData']->Org->OrgUID;
            $userUID       = $this->pageData['JwtData']->User->UserUID;
            $creditNoteUID = (int) $this->input->post('CreditNoteUID');

            if ($creditNoteUID <= 0) throw new Exception('Invalid Credit Note.');

            $readDb = $this->load->database('ReadDB', TRUE);
            $readDb->db_debug = FALSE;
            $readDb->from('Transaction.TransCreditNoteTbl');
            $readDb->where(['CreditNoteUID' => $creditNoteUID, 'OrgUID' => (int)$orgUID, 'IsDeleted' => 0]);
            $cn = $readDb->get()->row();

            if (!$cn) throw new Exception('Credit Note not found.');
            if ($cn->Status !== 'Pending') throw new Exception('Only Pending Credit Notes can be deleted. This Credit Note is ' . $cn->Status . '.');

            $this->load->model('dbwrite_model');
            $this->dbwrite_model->startTransaction();

            $wdb = $this->dbwrite_model->getWriteDb();
            $wdb->db_debug = FALSE;

            // Soft-delete the linked payment that funded this credit note
            $wdb->where([
                'TransUID'                  => (int)$cn->SourceTransUID,
                'OrgUID'                    => (int)$orgUID,
                'IsTransferredToCreditNote' => 1,
                'IsDeleted'                 => 0,
            ]);
            $wdb->update('Transaction.PaymentsTbl', ['IsDeleted' => 1, 'IsActive' => 0, 'UpdatedBy' => $userUID]);

            // Soft-delete the credit note
            $wdb->where(['CreditNoteUID' => $creditNoteUID, 'OrgUID' => (int)$orgUID]);
            $wdb->update('Transaction.TransCreditNoteTbl', ['IsDeleted' => 1, 'IsActive' => 0, 'UpdatedBy' => $userUID]);

            $this->dbwrite_model->commitTransaction();

            // Recalculate customer balance after commit
            if ((int)($cn->PartyUID ?? 0) > 0) {
                $this->load->library('customerbalance');
                $balResult = $this->customerbalance->recalcAndSync($orgUID, (int)$cn->PartyUID, $userUID);
                if ($balResult) {
                    $this->EndReturnData->CustomerBalance     = $balResult['balance'];
                    $this->EndReturnData->CustomerBalanceType = $balResult['type'];
                }
            }

            try {
                $cnNumber  = $cn->CreditNoteNumber  ?: '';
                $cnSrcNum  = $cn->SourceTransNumber ?: '';
                $cnLabel   = $cnNumber ?: '#' . $creditNoteUID;
                $this->load->library('auditlog');
                $this->auditlog->log(
                    $orgUID, $userUID, 'DELETE_CREDIT_NOTE', 'CreditNote', $creditNoteUID,
                    $cnNumber,
                    ['amount' => $cn->Amount, 'sourceTransUID' => (int)$cn->SourceTransUID, 'sourceTransNumber' => $cnSrcNum],
                    'Deleted Credit Note ' . $cnLabel . ' and linked payment',
                    'Invoices', 'TRANSACTION'
                );
            } catch (Exception $auditEx) {
                log_message('error', 'Audit log failed: ' . $auditEx->getMessage());
            }

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Credit Note and linked payment deleted successfully.';

        } catch (Exception $e) {
            $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // â”€â”€ Get pending credit notes for a customer â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    public function getCustomerCreditNotes() {
        $this->EndReturnData = new stdClass();
        try {
            $orgUID      = $this->pageData['JwtData']->Org->OrgUID;
            $customerUID = (int) $this->input->post('CustomerUID');

            if ($customerUID <= 0) throw new Exception('Customer ID is required.');

            $this->load->library('customerbalance');
            $notes = $this->customerbalance->getPendingCreditNotes($orgUID, $customerUID);

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Data  = $notes;

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // â”€â”€ Paginated credit notes list for the invoice page tab â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    public function getCreditNotesList() {
        $this->EndReturnData = new stdClass();
        try {
            $orgUID  = $this->pageData['JwtData']->Org->OrgUID;
            $pageNo  = max(1, (int)($this->input->post('PageNo') ?: 1));
            $limit   = max(1, (int)($this->input->post('RowLimit') ?: 10));
            $offset  = ($pageNo - 1) * $limit;
            $status  = trim($this->input->post('Status') ?: '');
            $search  = trim($this->input->post('Search') ?: '');

            $this->load->model('transactions_model');
            $readDb = $this->load->database('ReadDB', TRUE);
            $readDb->db_debug = FALSE;

            $baseWhere = ['CN.OrgUID' => (int)$orgUID, 'CN.IsDeleted' => 0, 'CN.IsCancelled' => 0];
            if ($status !== '' && $status !== 'All') {
                $baseWhere['CN.Status'] = $status;
            }

            // Count
            $readDb->select('COUNT(*) AS total');
            $readDb->from('Transaction.TransCreditNoteTbl CN');
            $readDb->join('Customers.CustomerTbl C', 'C.CustomerUID = CN.PartyUID', 'left');
            $readDb->where($baseWhere);
            if ($search !== '') {
                $readDb->group_start();
                $readDb->like('CN.CreditNoteNumber', $search);
                $readDb->or_like('C.Name', $search);
                $readDb->or_like('CN.SourceTransNumber', $search);
                $readDb->group_end();
            }
            $countResult = $readDb->get();
            if ($countResult === false) {
                throw new RuntimeException('Credit notes count query failed: ' . ($readDb->error()['message'] ?? 'unknown error'));
            }
            $totalCount = (int)($countResult->row()->total ?? 0);

            // Data
            $readDb->select([
                'CN.CreditNoteUID',
                'CN.CreditNoteNumber',
                'CN.CreditNoteToken',
                'CN.CreditNoteType',
                'CN.SourceTransUID',
                'CN.SourceTransNumber',
                'CN.SourceModuleUID',
                'CN.Amount',
                'CN.Status',
                'CN.Notes',
                'CN.CreatedOn',
                'C.CustomerUID',
                'C.Name AS CustomerName',
                'C.MobileNumber AS MobileNo',
                'C.Area AS CustomerArea',
                'C.Image AS CustomerImage',
                'T.TransDate AS SourceTransDate',
                'T.TransToken AS SourceTransToken',
                "CONCAT(U.FirstName, ' ', U.LastName) AS CreatorName",
            ]);
            $readDb->from('Transaction.TransCreditNoteTbl CN');
            $readDb->join('Customers.CustomerTbl C',       'C.CustomerUID = CN.PartyUID',    'left');
            $readDb->join('Transaction.TransactionsTbl T', 'T.TransUID = CN.SourceTransUID AND T.IsDeleted = 0', 'left');
            $readDb->join('Users.UserTbl U',               'U.UserUID = CN.CreatedBy',       'left');
            $readDb->where($baseWhere);
            if ($search !== '') {
                $readDb->group_start();
                $readDb->like('CN.CreditNoteNumber', $search);
                $readDb->or_like('C.Name', $search);
                $readDb->or_like('CN.SourceTransNumber', $search);
                $readDb->group_end();
            }
            $readDb->order_by('CN.CreatedOn', 'DESC');
            $readDb->limit($limit, $offset);
            $dataResult = $readDb->get();
            if ($dataResult === false) {
                throw new RuntimeException('Credit notes data query failed: ' . ($readDb->error()['message'] ?? 'unknown error'));
            }
            $rows = $dataResult->result();

            $this->EndReturnData->Error          = FALSE;
            $this->EndReturnData->TotalCount     = $totalCount;
            $this->EndReturnData->RecordHtmlData = $this->load->view(
                'transactions/invoices/creditnotes_list',
                ['DataLists' => $rows, 'SerialNumber' => $offset, 'JwtData' => $this->pageData['JwtData']],
                true
            );
            $this->EndReturnData->Pagination     = $this->globalservice->buildPagePaginationHtml(
                '/invoices/getCreditNotesList', $totalCount, $pageNo, $limit
            );

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }


}
