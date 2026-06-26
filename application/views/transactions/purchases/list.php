<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php
$cdnUrl = getenv('FILE_UPLOAD') == 'amazonaws' ? getenv('CDN_URL') : getenv('CFLARE_R2_CDN');
include_once(APPPATH . 'views/transactions/partials/party_avatar.php');
$moduleContext = 'purchase';
include(APPPATH . 'views/transactions/partials/status_config.php');
// For purchases, Received is a mid-state (Draft→Received→Paid/Cancelled), not terminal
$terminalStatuses = ['Cancelled'];

$currency      = htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹');
$decimals      = $JwtData->GenSettings->DecimalPoints ?? 2;
$showSerial    = $JwtData->GenSettings->SerialNoDisplay == 1;
$purchModuleUID = 105;

if (!empty($DataLists)):
    foreach ($DataLists as $list):
        $SerialNumber++;
        $status      = $list->Status ?? 'Draft';
        $isDraft     = $status === 'Draft';
        $isCancelled = $status === 'Cancelled';
        $isTerminal  = in_array($status, $terminalStatuses);
        $badgeClass  = $statusBadgeClass[$status]  ?? 'trans-badge-Draft';
        $icon        = $statusIcon[$status]         ?? 'bx-circle';
        $transitions = $moduleTransitions[$status]  ?? [];

        $mobileNum   = trim($list->MobileNumber ?? '');
        $countryCode = trim($list->CountryCode  ?? '');
        $partyEmail  = trim($list->EmailAddress ?? '');
        $waNum       = $mobileNum ? preg_replace('/[^0-9]/', '', ($countryCode ?: '91') . $mobileNum) : '';
        $hasMobile   = $mobileNum !== '';
        $hasEmail    = $partyEmail !== '';

        $paidAmt    = (float)($list->PaidAmount ?? 0);
        $netAmt     = (float)($list->NetAmount  ?? 0);
        $pendingAmt = max(0, round($netAmt - $paidAmt, 2));

        // Payment status badge
        if ($isDraft) {
            $payStatus = '';
            $payBadge  = '';
        } elseif ($paidAmt <= 0) {
            $payStatus = 'Pending';
            $payBadge  = '<span class="badge bg-label-warning" style="font-size:.68rem;">Pending</span>';
        } elseif ($pendingAmt <= 0.01) {
            $payStatus = 'Paid';
            $payBadge  = '<span class="badge bg-label-success" style="font-size:.68rem;">Paid</span>';
        } else {
            $payStatus = 'Partially Paid';
            $payBadge  = '<span class="badge bg-label-info" style="font-size:.68rem;">Partially Paid</span>';
        }

        // Build WhatsApp message
        $waTemplate = !empty($WhatsAppTemplate) && is_object($WhatsAppTemplate) ? $WhatsAppTemplate->Body : (!empty($WhatsAppTemplate) && is_string($WhatsAppTemplate) ? $WhatsAppTemplate : null);

        $orgName    = $JwtData->Org->OrgName   ?? 'Our Company';
        $orgMobile  = $JwtData->Org->OrgMobile ?? '';
        $partyName  = $list->PartyName   ?? 'Vendor';
        $billNum    = $list->UniqueNumber ?? 'Draft';
        $billLink   = (getenv('APP_URL') ?: 'http://localhost:8080') . '/invoice/' . ($list->TransToken ?? '');
        $numericAmt = smartDecimal($netAmt, $decimals, true);
        $billAmount = $currency . ' ' . $numericAmt;
        $pendingFmt = $currency . ' ' . smartDecimal($pendingAmt, $decimals, true);
        $transDate  = !empty($list->TransDate) ? format_datedisplay($list->TransDate) : '';

        if (!empty($waTemplate)) {
            $waMessage = str_replace(
                ['{{PARTY_NAME}}','{{VENDOR_NAME}}','{{DOC_NUMBER}}','{{BILL_NUMBER}}','{{BILL_AMOUNT}}','{{AMOUNT}}','{{BALANCE_AMOUNT}}','{{PENDING_AMOUNT}}','{{CURRENCY}}','{{PAYMENT_STATUS}}','{{DOC_DATE}}','{{BILL_DATE}}','{{DOC_TYPE}}','{{RECEIPT_LINK}}','{{ORG_NAME}}','{{ORG_PHONE}}','{{ORG_MOBILE}}'],
                [$partyName,$partyName,$billNum,$billNum,$billAmount,$numericAmt,$pendingFmt,$numericAmt,$currency,$payStatus,$transDate,$transDate,'Purchase',$billLink,$orgName,$orgMobile,$orgMobile],
                $waTemplate
            );
        } else {
            $waMessage  = "Hello *{$partyName}*,\n\n";
            $waMessage .= "Purchase Bill: *{$billNum}*\n";
            $waMessage .= "Bill Amount: *{$billAmount}*\n";
            $waMessage .= "Payment Status: *{$payStatus}*\n";
            $waMessage .= "\nThanks\n*{$orgName}*";
            if ($orgMobile) $waMessage .= "\n{$orgMobile}";
        }
        $waMessageEncoded = rawurlencode($waMessage);

        $showPending = !$isDraft && $pendingAmt > 0 && !in_array($status, ['Paid', 'Cancelled', 'Rejected']);
        $hasAttach   = !empty($list->AttachmentCount) && (int)$list->AttachmentCount > 0;
?>
    <tr>

        <!-- Checkbox -->
        <td style="width:36px">
            <div class="form-check mb-0">
                <input class="form-check-input table-chkbox purchCheck" type="checkbox" value="<?php echo (int)$list->TransUID; ?>">
            </div>
        </td>

        <!-- S.No -->
        <td class="<?php echo $showSerial ? '' : 'd-none'; ?> table-serialno" style="width:44px">
            <span class="text-muted" style="font-size:.78rem;"><?php echo $SerialNumber; ?></span>
        </td>

        <!-- Bill Number -->
        <td>
            <?php if ($isDraft || empty($list->UniqueNumber)): ?>
                <span class="trans-doc-draft"><i class="bx bx-pencil me-1" style="font-size:.8rem;"></i>Draft</span>
                <?php if (!empty($list->TransDate)): ?>
                    <div class="text-muted" style="font-size:.72rem;"><?php echo htmlspecialchars(format_datedisplay($list->TransDate)); ?></div>
                <?php endif; ?>
            <?php else: ?>
                <a href="javascript:void(0)" class="trans-doc-number viewTransaction"
                   data-uid="<?php echo (int)$list->TransUID; ?>"
                   data-module="<?php echo (int)$list->ModuleUID; ?>"
                   data-type="purchase"
                   data-number="<?php echo htmlspecialchars($list->UniqueNumber ?? ''); ?>"
                   data-date="<?php echo htmlspecialchars($list->TransDate ?? ''); ?>"
                   data-status="<?php echo htmlspecialchars($list->Status ?? ''); ?>">
                    <?php echo htmlspecialchars($list->UniqueNumber); ?>
                </a>
                <div class="d-flex align-items-center gap-2 mt-1">
                    <div class="text-muted" style="font-size:.72rem;"><?php echo htmlspecialchars(format_datedisplay($list->TransDate)); ?></div>
                    <?php if ($hasAttach): ?>
                    <button type="button" class="btn btn-link p-0 transAttachBtn"
                            data-uid="<?php echo (int)$list->TransUID; ?>"
                            data-num="<?php echo htmlspecialchars($list->UniqueNumber ?? ''); ?>"
                            data-url="/transactions/getAttachments"
                            data-module-uid="105"
                            title="<?php echo (int)$list->AttachmentCount; ?> attachment(s)"
                            style="font-size:.82rem;line-height:1;color:#6f42c1;">
                        <i class="bx bx-paperclip"></i>
                    </button>
                    <?php endif; ?>
                </div>
                <?php if (!empty($list->CreatedBy)): ?>
                <div style="font-size:.68rem;color:#bbb;">by <?php echo htmlspecialchars($list->CreatedBy); ?></div>
                <?php endif; ?>
            <?php endif; ?>
        </td>

        <!-- Amount -->
        <td>
            <?php if ($isDraft && $netAmt == 0): ?>
                <span class="text-muted">—</span>
            <?php else: ?>
                <div class="trans-amount-main"><?php echo $currency . ' ' . smartDecimal($netAmt, $decimals, true); ?></div>
                <?php if ($showPending): ?>
                    <div style="font-size:.68rem;color:#d33;font-weight:500;">
                        Bal <?php echo $currency . ' ' . smartDecimal($pendingAmt, $decimals, true); ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </td>

        <!-- Payment Status -->
        <td>
            <?php echo $payBadge; ?>
            <?php if (($payStatus === 'Pending' || $payStatus === 'Partially Paid') && !$isDraft): ?>
                <?php $daysPending = (int) floor((time() - strtotime($list->TransDate)) / 86400); ?>
                <div style="font-size:.68rem;color:#e67e22;font-weight:600;margin-top:3px;">
                    <i class="bx bx-time-five" style="font-size:.72rem;"></i>
                    since <?php echo $daysPending; ?> day<?php echo $daysPending !== 1 ? 's' : ''; ?>
                </div>
                <?php if (!empty($list->ValidityDate)): ?>
                <div style="font-size:.68rem;color:#6c757d;margin-top:1px;">
                    Due: <?php echo format_datedisplay($list->ValidityDate); ?>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </td>

        <!-- Payment Mode -->
        <td>
            <?php
            $payCount     = (int)($list->PaymentCount ?? 0);
            $payModes     = $payCount > 0 ? explode(',', $list->PaymentModes ?? '') : [];
            $firstMode    = isset($payModes[0]) ? htmlspecialchars(trim($payModes[0])) : '';
            $extraCnt     = max(0, $payCount - 1);
            $payBankName  = htmlspecialchars(trim($list->PayBankName ?? ''));
            $payAccNum    = htmlspecialchars(trim($list->PayAccountNumber ?? ''));
            $isCashOnly   = $payCount > 0 && empty($payBankName);
            $hasPayAttach = !empty($list->PaymentAttachmentCount) && (int)$list->PaymentAttachmentCount > 0;
            ?>
            <?php if ($payCount > 0 && $firstMode): ?>
                <div class="pay-mode-cell<?php echo $payCount > 1 ? ' pay-mode-clickable' : ''; ?>"
                     <?php if ($payCount > 1): ?>
                     data-trans-uid="<?php echo (int)$list->TransUID; ?>"
                     data-trans-num="<?php echo htmlspecialchars($list->UniqueNumber ?? ''); ?>"
                     style="cursor:pointer;"
                     <?php endif; ?>>
                    <div class="d-flex align-items-center gap-1 flex-wrap">
                        <span class="badge bg-label-primary" style="font-size:.68rem;">
                            <i class="bx bx-credit-card me-1"></i><?php echo $firstMode; ?>
                        </span>
                        <?php if ($extraCnt > 0): ?>
                            <span class="badge bg-label-secondary" style="font-size:.68rem;">+<?php echo $extraCnt; ?></span>
                        <?php endif; ?>
                        <?php if ($hasPayAttach): ?>
                        <button type="button" class="btn btn-link p-0 transPayAttachBtn"
                                data-uid="<?php echo (int)$list->TransUID; ?>"
                                data-num="<?php echo htmlspecialchars($list->UniqueNumber ?? ''); ?>"
                                data-url="/purchases/getPaymentAttachments"
                                title="<?php echo (int)$list->PaymentAttachmentCount; ?> payment attachment(s)"
                                style="font-size:.82rem;line-height:1;color:#0d6efd;">
                            <i class="bx bx-paperclip"></i>
                        </button>
                        <?php endif; ?>
                    </div>
                    <?php if (!$isCashOnly && ($payBankName || $payAccNum)): ?>
                    <div style="font-size:.68rem;color:#6c757d;margin-top:3px;line-height:1.4;">
                        <?php if ($payBankName): ?>
                        <div><i class="bx bx-building-house me-1" style="font-size:.7rem;"></i><?php echo $payBankName; ?></div>
                        <?php endif; ?>
                        <?php if ($payAccNum): ?>
                        <div style="font-family:monospace;letter-spacing:.03em;"><?php echo $payAccNum; ?></div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <span class="text-muted" style="font-size:.78rem;">—</span>
            <?php endif; ?>
        </td>

        <!-- Vendor -->
        <td class="purch-party-td">
            <div class="d-flex align-items-center gap-2">
                <?php partyAvatar($list->PartyName, $list->PartyImage ?? null, $cdnUrl); ?>
                <div>
                    <div class="trans-party-name"><?php echo r2k_party_name($list->PartyName ?? '', $list->MobileNumber ?? '', $list->CountryCode ?? '', $list->PartyArea ?? '', !empty($list->PartyImage) ? $cdnUrl . $list->PartyImage : ''); ?></div>
                    <?php if (!empty($list->PartyArea)): ?>
                    <div style="font-size:.7rem;color:#888;margin-top:1px;">
                        <i class="bx bx-map" style="font-size:.72rem;"></i> <?php echo htmlspecialchars($list->PartyArea); ?>
                    </div>
                    <?php endif; ?>
                    <?php if ($hasMobile): ?>
                    <div class="trans-party-mobile" style="font-size:.72rem;color:#666;margin-top:1px;">
                        <?php echo ($countryCode ? htmlspecialchars($countryCode) . ' ' : '') . htmlspecialchars($mobileNum); ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php if ($hasMobile || $hasEmail): ?>
            <div class="inv-contact-icons">
                <?php if ($hasMobile): ?>
                <a href="javascript:void(0)" class="wa purch-wa-link"
                   data-wa-url="https://wa.me/<?php echo $waNum; ?>?text=<?php echo $waMessageEncoded; ?>"
                   data-bs-toggle="tooltip"
                   data-bs-trigger="hover"
                   title="WhatsApp">
                    <i class="bx bxl-whatsapp"></i>
                </a>
                <button class="comm-send-single sms"
                    data-commtype="SMS"
                    data-recipienttype="Vendor"
                    data-uid="<?php echo (int)$list->PartyUID; ?>"
                    data-name="<?php echo htmlspecialchars($list->PartyName ?? ''); ?>"
                    data-mobile="<?php echo htmlspecialchars($mobileNum); ?>"
                    data-email="<?php echo htmlspecialchars($partyEmail); ?>"
                    data-module-uid="<?php echo $purchModuleUID; ?>"
                    data-trans-uid="<?php echo (int)$list->TransUID; ?>"
                    data-bs-toggle="tooltip"
                    data-bs-trigger="hover"
                    title="Send SMS">
                    <i class="bx bx-message-dots"></i>
                </button>
                <?php endif; ?>
                <?php if ($hasEmail): ?>
                <button class="comm-send-single em"
                    data-commtype="Email"
                    data-recipienttype="Vendor"
                    data-uid="<?php echo (int)$list->PartyUID; ?>"
                    data-trans-uid="<?php echo (int)$list->TransUID; ?>"
                    data-name="<?php echo htmlspecialchars($list->PartyName ?? ''); ?>"
                    data-mobile="<?php echo htmlspecialchars($mobileNum); ?>"
                    data-email="<?php echo htmlspecialchars($partyEmail); ?>"
                    data-module-uid="<?php echo $purchModuleUID; ?>"
                    data-bs-toggle="tooltip"
                    data-bs-trigger="hover"
                    title="Send Email">
                    <i class="bx bx-envelope"></i>
                </button>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </td>

        <!-- Last Updated -->
        <td>
            <?php
                $updatedOn  = $list->UpdatedOn ?? null;
                $secondsAgo = $updatedOn ? (time() - strtotime($updatedOn)) : null;
                $within24h  = $secondsAgo !== null && $secondsAgo < 86400;
                if ($within24h) {
                    if ($secondsAgo < 60)        $agoText = 'just now';
                    elseif ($secondsAgo < 3600)  $agoText = (int)($secondsAgo / 60) . ' min' . ((int)($secondsAgo / 60) > 1 ? 's' : '') . ' ago';
                    else                         $agoText = (int)($secondsAgo / 3600) . ' hr' . ((int)($secondsAgo / 3600) > 1 ? 's' : '') . ' ago';
                }
            ?>
            <div style="font-size:.78rem;"><?php echo $updatedOn ? changeTimeZonefromDateTime($updatedOn, $JwtData->User->Timezone, 2) : '—'; ?></div>
            <?php if ($within24h): ?>
            <div style="font-size:.68rem;color:#6f42c1;font-weight:500;"><?php echo $agoText; ?></div>
            <?php endif; ?>
            <div class="text-muted" style="font-size:.7rem;">by <?php echo htmlspecialchars($list->UpdatedBy ?? '—'); ?></div>
        </td>

        <!-- Actions -->
        <td style="width:110px">
            <div class="d-flex align-items-center justify-content-end gap-1">

                <!-- Quick: Issue Payment (pending only) -->
                <?php if ($showPending): ?>
                <button type="button"
                        class="btn inv-pay-quick-btn purchReceivePayment"
                        data-uid="<?php echo (int)$list->TransUID; ?>"
                        data-num="<?php echo htmlspecialchars($list->UniqueNumber ?? ''); ?>"
                        data-date="<?php echo htmlspecialchars(format_datedisplay($list->TransDate ?? '')); ?>"
                        data-party="<?php echo htmlspecialchars($list->PartyName ?? ''); ?>"
                        data-total="<?php echo $netAmt; ?>"
                        data-paid="<?php echo $paidAmt; ?>"
                        data-pending="<?php echo $pendingAmt; ?>"
                        data-bs-toggle="tooltip"
                        data-bs-trigger="hover"
                        title="Issue Payment — <?php echo $currency . ' ' . smartDecimal($pendingAmt, $decimals, true); ?> pending">
                    <?php echo $currency; ?>
                </button>
                <?php endif; ?>

                <!-- Edit (always visible) -->
                <?php if (!$isTerminal): ?>
                <a class="btn btn-icon btn-sm text-warning inv-row-action"
                   href="/purchases/edit/<?php echo (int)$list->TransUID; ?>"
                   data-bs-toggle="tooltip" data-bs-trigger="hover" title="Edit">
                    <i class="bx bx-edit"></i>
                </a>
                <?php endif; ?>

                <div class="dropdown">
                    <button class="trans-actions-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bx bx-dots-vertical-rounded fs-5"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="font-size:.82rem;min-width:170px;">

                        <!-- Print section -->
                        <?php if (!$isDraft): ?>
                        <li>
                            <button class="dropdown-item thermalPrintTransaction" data-uid="<?php echo (int)$list->TransUID; ?>" data-module="<?php echo (int)$list->ModuleUID; ?>">
                                <i class="bx bx-receipt me-2 text-dark"></i>Thermal Print
                            </button>
                        </li>
                        <li>
                            <button class="dropdown-item a4PrintTransaction" data-uid="<?php echo (int)$list->TransUID; ?>" data-module="<?php echo (int)$list->ModuleUID; ?>">
                                <i class="bx bx-printer me-2 text-primary"></i>Print / Download
                            </button>
                        </li>
                        <li>
                            <button class="dropdown-item downloadPdfQuotation" data-uid="<?php echo (int)$list->TransUID; ?>" data-module="<?php echo (int)$list->ModuleUID; ?>">
                                <i class="bx bx-download me-2 text-success"></i>Download PDF
                            </button>
                        </li>
                        <li><hr class="dropdown-divider my-1"></li>
                        <?php endif; ?>

                        <!-- Payment -->
                        <?php if ($showPending): ?>
                        <li>
                            <button class="dropdown-item purchReceivePayment"
                                    data-uid="<?php echo (int)$list->TransUID; ?>"
                                    data-num="<?php echo htmlspecialchars($list->UniqueNumber ?? ''); ?>"
                                    data-date="<?php echo htmlspecialchars(format_datedisplay($list->TransDate ?? '')); ?>"
                                    data-party="<?php echo htmlspecialchars($list->PartyName ?? ''); ?>"
                                    data-total="<?php echo $netAmt; ?>"
                                    data-paid="<?php echo $paidAmt; ?>"
                                    data-pending="<?php echo $pendingAmt; ?>">
                                <i class="bx bx-money-withdraw me-2 text-success"></i>Issue Payment
                            </button>
                        </li>
                        <li><hr class="dropdown-divider my-1"></li>
                        <?php endif; ?>

                        <!-- Duplicate -->
                        <?php if (!$isCancelled): ?>
                        <?php if (!$isDraft && ($hasMobile || $hasEmail)): ?>
                        <li><hr class="dropdown-divider my-1"></li>
                        <?php if ($hasMobile): ?>
                        <li>
                            <a class="dropdown-item purch-wa-link"
                               href="javascript:void(0)"
                               data-wa-url="https://wa.me/<?php echo $waNum; ?>?text=<?php echo $waMessageEncoded; ?>"
                               style="color:#25d366;">
                                <i class="bx bxl-whatsapp me-2"></i>Share via WhatsApp
                            </a>
                        </li>
                        <li>
                            <button class="dropdown-item comm-send-single"
                                    data-commtype="SMS"
                                    data-recipienttype="Vendor"
                                    data-uid="<?php echo (int)$list->PartyUID; ?>"
                                    data-trans-uid="<?php echo (int)$list->TransUID; ?>"
                                    data-name="<?php echo htmlspecialchars($list->PartyName ?? ''); ?>"
                                    data-mobile="<?php echo htmlspecialchars($mobileNum); ?>"
                                    data-email="<?php echo htmlspecialchars($partyEmail); ?>"
                                    data-module-uid="<?php echo $purchModuleUID; ?>"
                                    style="color:#0097a7;">
                                <i class="bx bx-message-dots me-2"></i>Send SMS
                            </button>
                        </li>
                        <?php endif; ?>
                        <?php if ($hasEmail): ?>
                        <li>
                            <button class="dropdown-item comm-send-single"
                                    data-commtype="Email"
                                    data-recipienttype="Vendor"
                                    data-uid="<?php echo (int)$list->PartyUID; ?>"
                                    data-trans-uid="<?php echo (int)$list->TransUID; ?>"
                                    data-name="<?php echo htmlspecialchars($list->PartyName ?? ''); ?>"
                                    data-mobile="<?php echo htmlspecialchars($mobileNum); ?>"
                                    data-email="<?php echo htmlspecialchars($partyEmail); ?>"
                                    data-module-uid="<?php echo $purchModuleUID; ?>"
                                    style="color:#1565c0;">
                                <i class="bx bx-envelope me-2"></i>Send Email
                            </button>
                        </li>
                        <?php endif; ?>
                        <?php endif; ?>
                        <?php endif; ?>

                        <!-- Cancel & Delete — separate section -->
                        <?php if (!$isCancelled): ?>
                        <li><hr class="dropdown-divider my-1"></li>
                        <?php if (!$isDraft): ?>
                        <li>
                            <button class="dropdown-item text-warning purch-status-update"
                                    data-uid="<?php echo (int)$list->TransUID; ?>"
                                    data-num="<?php echo htmlspecialchars($list->UniqueNumber ?? 'Draft'); ?>"
                                    data-status="Cancelled">
                                <i class="bx bx-x-circle me-2"></i>Cancel Bill
                            </button>
                        </li>
                        <?php endif; ?>
                        <li>
                            <button class="dropdown-item text-danger deletePurchase"
                                    data-uid="<?php echo (int)$list->TransUID; ?>"
                                    data-num="<?php echo htmlspecialchars($list->UniqueNumber ?? 'Draft'); ?>">
                                <i class="bx bx-trash me-2"></i>Delete
                            </button>
                        </li>
                        <?php endif; ?>

                    </ul>
                </div>
            </div>
        </td>

    </tr>
<?php
    endforeach;
else:
?>
    <tr>
        <td colspan="9">
            <div class="d-flex flex-column align-items-center py-5">
                <img src="/assets/img/elements/no-record-found.png" alt="No Records" class="img-fluid mb-3" style="max-height:150px;object-fit:contain;">
                <span class="text-muted mb-3" style="font-size:.9rem;">No purchase bills found</span>
                <a href="/purchases/create" class="btn btn-primary btn-sm px-4">
                    <i class="bx bx-plus me-1"></i>Record Purchase Bill
                </a>
            </div>
        </td>
    </tr>
<?php endif; ?>
