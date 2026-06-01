<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php
$cdnUrl = getenv('FILE_UPLOAD') == 'amazonaws' ? getenv('CDN_URL') : getenv('CFLARE_R2_CDN');
include_once(APPPATH . 'views/transactions/partials/party_avatar.php');
$moduleContext = 'quotation';
include(APPPATH . 'views/transactions/partials/status_config.php');

$currency      = htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹');
$decimals      = $JwtData->GenSettings->DecimalPoints ?? 2;
$showSerial    = $JwtData->GenSettings->SerialNoDisplay == 1;
$quotModuleUID = 101;
$today         = time();
$soonDays      = 3;

if (!empty($DataLists)):
    foreach ($DataLists as $list):
        $SerialNumber++;
        $status      = $list->Status ?? 'Draft';
        $isDraft     = $status === 'Draft';
        $isTerminal  = in_array($status, $terminalStatuses);
        $badgeClass  = $statusBadgeClass[$status]  ?? 'trans-badge-Draft';
        $icon        = $statusIcon[$status]         ?? 'bx-circle';
        $transitions = $moduleTransitions[$status]  ?? [];

        $mobileNum   = trim($list->MobileNumber ?? '');
        $countryCode = trim($list->CountryCode ?? '');
        $partyEmail  = trim($list->EmailAddress ?? '');
        $waNum       = $mobileNum ? preg_replace('/[^0-9]/', '', ($countryCode ?: '91') . $mobileNum) : '';
        $hasMobile   = $mobileNum !== '';
        $hasEmail    = $partyEmail !== '';

        $dueClass = 'trans-due-normal';
        $validityHtml = '';
        $showValidity = in_array($status, ['Pending', 'Accepted']) && !empty($list->ValidityDate);
        if ($showValidity) {
            $dueTs    = strtotime($list->ValidityDate);
            $todayTs  = strtotime(date('Y-m-d'));
            $diffDays = (int) round(($dueTs - $todayTs) / 86400);

            // Row 1: validity date
            $validityHtml .= '<div style="font-size:.68rem;color:#6c757d;">Due: ' . format_datedisplay($list->ValidityDate) . '</div>';

            // Row 2: since / in N days
            if ($diffDays < 0) {
                $dueClass      = 'trans-due-overdue';
                $absDays       = abs($diffDays);
                $validityHtml .= '<div style="font-size:.68rem;color:#e67e22;font-weight:600;margin-top:3px;"><i class="bx bx-time-five" style="font-size:.72rem;"></i> since ' . $absDays . ' day' . ($absDays !== 1 ? 's' : '') . '</div>';
                // Row 3: Expired badge
                $validityHtml .= '<div style="margin-top:2px;"><span class="badge bg-label-danger" style="font-size:.65rem;">Expired</span></div>';
            } elseif ($diffDays === 0) {
                $dueClass      = 'trans-due-soon';
                $validityHtml .= '<div style="font-size:.68rem;color:#e67e22;font-weight:600;margin-top:3px;"><i class="bx bx-time-five" style="font-size:.72rem;"></i> expires today</div>';
            } elseif ($diffDays <= 7) {
                $dueClass      = 'trans-due-soon';
                $validityHtml .= '<div style="font-size:.68rem;color:#e67e22;font-weight:600;margin-top:3px;"><i class="bx bx-time-five" style="font-size:.72rem;"></i> in ' . $diffDays . ' day' . ($diffDays !== 1 ? 's' : '') . '</div>';
            } else {
                $validityHtml .= '<div style="font-size:.68rem;color:#6c757d;margin-top:3px;"><i class="bx bx-time-five" style="font-size:.72rem;"></i> in ' . $diffDays . ' days</div>';
            }
        }
        $isOverdueRow = ($dueClass === 'trans-due-overdue');

        // Build WhatsApp message
        $waTemplate = !empty($WhatsAppTemplate) && is_object($WhatsAppTemplate) ? $WhatsAppTemplate->Body : (!empty($WhatsAppTemplate) && is_string($WhatsAppTemplate) ? $WhatsAppTemplate : null);

        $orgName    = $JwtData->Org->OrgName   ?? 'Our Company';
        $orgMobile  = $JwtData->Org->OrgMobile ?? '';
        $partyName  = $list->PartyName   ?? 'Customer';
        $quotNum    = $list->UniqueNumber ?? 'Draft';
        $quotLink   = (getenv('APP_URL') ?: 'http://localhost:8080') . '/quotation/' . ($list->TransToken ?? '');
        $netAmt     = (float)($list->NetAmount ?? 0);
        $numericAmt = smartDecimal($netAmt, $decimals, true);
        $billAmount = $currency . ' ' . $numericAmt;
        $transDate  = !empty($list->TransDate) ? format_datedisplay($list->TransDate) : '';
        $quotStatus = $list->Status ?? '';

        if (!empty($waTemplate)) {
            $waMessage = str_replace(
                [
                    '{{PARTY_NAME}}',    '{{CUSTOMER_NAME}}',
                    '{{DOC_NUMBER}}',    '{{INVOICE_NUMBER}}',
                    '{{BILL_AMOUNT}}',   '{{AMOUNT}}',
                    '{{BALANCE_AMOUNT}}','{{PENDING_AMOUNT}}',
                    '{{CURRENCY}}',
                    '{{PAYMENT_STATUS}}',
                    '{{DOC_DATE}}',      '{{INVOICE_DATE}}',
                    '{{DOC_TYPE}}',
                    '{{RECEIPT_LINK}}',  '{{INVOICE_LINK}}',
                    '{{ORG_NAME}}',
                    '{{ORG_PHONE}}',     '{{ORG_MOBILE}}',
                ],
                [
                    $partyName,   $partyName,
                    $quotNum,     $quotNum,
                    $billAmount,  $numericAmt,
                    $billAmount,  $numericAmt,
                    $currency,
                    $quotStatus,
                    $transDate,   $transDate,
                    'Quotation',
                    $quotLink,    $quotLink,
                    $orgName,
                    $orgMobile,   $orgMobile,
                ],
                $waTemplate
            );
        } else {
            $waMessage  = "Hello *{$partyName}*,\n\n";
            $waMessage .= "Please find the quotation from *{$orgName}*.\n\n";
            $waMessage .= "Quotation: *{$quotNum}*\n";
            $waMessage .= "Amount: *{$billAmount}*\n";
            if (!$isDraft) {
                $waMessage .= "Link: {$quotLink}\n";
            }
            $waMessage .= "\nThanks\n*{$orgName}*";
            if ($orgMobile) {
                $waMessage .= "\n{$orgMobile}";
            }
        }
        $waMessageEncoded = rawurlencode($waMessage);
?>
    <tr class="<?php echo $isOverdueRow ? 'trans-row-overdue' : ''; ?>">

        <td style="width:36px">
            <div class="form-check mb-0">
                <input class="form-check-input table-chkbox quotationCheck" type="checkbox" value="<?php echo (int)$list->TransUID; ?>">
            </div>
        </td>

        <td class="<?php echo $showSerial ? '' : 'd-none'; ?> table-serialno" style="width:44px">
            <span class="text-muted" style="font-size:.78rem;"><?php echo $SerialNumber; ?></span>
        </td>

        <!-- Quotation Number -->
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
                   data-type="quotation"
                   data-number="<?php echo htmlspecialchars($list->UniqueNumber ?? ''); ?>"
                   data-date="<?php echo htmlspecialchars($list->TransDate ?? ''); ?>"
                   data-status="<?php echo htmlspecialchars($list->Status ?? ''); ?>">
                    <?php echo htmlspecialchars($list->UniqueNumber); ?>
                </a>
                <div class="text-muted" style="font-size:.72rem;"><?php echo htmlspecialchars(format_datedisplay($list->TransDate)); ?></div>
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
            <?php endif; ?>
        </td>

        <!-- Status -->
        <td>
            <?php if (!empty($transitions)): ?>
            <div class="dropdown">
                <span class="trans-badge <?php echo $badgeClass; ?>" data-bs-toggle="dropdown"
                      data-uid="<?php echo (int)$list->TransUID; ?>"
                      data-current="<?php echo htmlspecialchars($status); ?>">
                    <i class="bx <?php echo $icon; ?>" style="font-size:.8rem;"></i>
                    <?php echo htmlspecialchars($status); ?>
                    <i class="bx bx-chevron-down" style="font-size:.7rem;"></i>
                </span>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="min-width:170px;font-size:.82rem;">
                    <?php foreach ($transitions as $t): ?>
                    <li>
                        <button class="dropdown-item quot-status-update"
                                data-uid="<?php echo (int)$list->TransUID; ?>"
                                data-status="<?php echo htmlspecialchars($t['db']); ?>"
                                data-target="<?php echo htmlspecialchars($t['target'] ?? ''); ?>">
                            <?php echo htmlspecialchars($t['label']); ?>
                        </button>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php else: ?>
                <span class="trans-badge <?php echo $badgeClass; ?>">
                    <i class="bx <?php echo $icon; ?>" style="font-size:.8rem;"></i>
                    <?php echo htmlspecialchars($status); ?>
                </span>
            <?php endif; ?>
        </td>

        <!-- Customer -->
        <td class="inv-party-td">
            <div class="d-flex align-items-center gap-2">
                <?php partyAvatar($list->PartyName, $list->PartyImage ?? null, $cdnUrl); ?>
                <div>
                    <div class="trans-party-name"><?php echo htmlspecialchars($list->PartyName ?? '—'); ?></div>
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
                <a href="javascript:void(0)" class="wa inv-wa-link"
                   data-wa-url="https://wa.me/<?php echo $waNum; ?>?text=<?php echo $waMessageEncoded; ?>"
                   data-bs-toggle="tooltip"
                   data-bs-trigger="hover"
                   title="WhatsApp">
                    <i class="bx bxl-whatsapp"></i>
                </a>
                <button class="comm-send-single sms"
                    data-commtype="SMS"
                    data-recipienttype="Customer"
                    data-uid="<?php echo (int)$list->PartyUID; ?>"
                    data-name="<?php echo htmlspecialchars($list->PartyName ?? ''); ?>"
                    data-mobile="<?php echo htmlspecialchars($mobileNum); ?>"
                    data-email="<?php echo htmlspecialchars($partyEmail); ?>"
                    data-module-uid="<?php echo $quotModuleUID; ?>"
                    data-bs-toggle="tooltip"
                    data-bs-trigger="hover"
                    title="Send SMS">
                    <i class="bx bx-message-dots"></i>
                </button>
                <?php endif; ?>
                <?php if ($hasEmail): ?>
                <button class="comm-send-single em"
                    data-commtype="Email"
                    data-recipienttype="Customer"
                    data-uid="<?php echo (int)$list->PartyUID; ?>"
                    data-trans-uid="<?php echo (int)$list->TransUID; ?>"
                    data-name="<?php echo htmlspecialchars($list->PartyName ?? ''); ?>"
                    data-mobile="<?php echo htmlspecialchars($mobileNum); ?>"
                    data-email="<?php echo htmlspecialchars($partyEmail); ?>"
                    data-module-uid="<?php echo $quotModuleUID; ?>"
                    data-bs-toggle="tooltip"
                    data-bs-trigger="hover"
                    title="Send Email">
                    <i class="bx bx-envelope"></i>
                </button>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </td>

        <!-- Valid Until -->
        <td>
            <?php if ($showValidity): ?>
                <?php echo $validityHtml; ?>
            <?php elseif (!$isDraft && !empty($list->ValidityDate)): ?>
                <div style="font-size:.68rem;color:#6c757d;">Due: <?php echo format_datedisplay($list->ValidityDate); ?></div>
            <?php else: ?>
                <span class="text-muted">&mdash;</span>
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
            <div style="font-size:.68rem;color:#0d6efd;font-weight:500;"><?php echo $agoText; ?></div>
            <?php endif; ?>
            <div class="text-muted" style="font-size:.7rem;">by <?php echo htmlspecialchars($list->UpdatedBy ?? '—'); ?></div>
        </td>

        <!-- Actions -->
        <td style="width:50px">
            <div class="d-flex align-items-center justify-content-end gap-1">

                <?php if (!$isTerminal): ?>
                <a class="btn btn-icon btn-sm text-warning" href="/quotations/edit/<?php echo (int)$list->TransUID; ?>" title="Edit">
                    <i class="bx bx-edit"></i>
                </a>
                <?php endif; ?>

                <div class="dropdown">
                    <button class="trans-actions-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bx bx-dots-vertical-rounded fs-5"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="font-size:.82rem;min-width:180px;">

                        <?php if (!$isDraft): ?>
                        <li>
                            <button class="dropdown-item a4PrintTransaction" data-uid="<?php echo (int)$list->TransUID; ?>" data-module="<?php echo (int)$list->ModuleUID; ?>">
                                <i class="bx bx-printer me-2 text-dark"></i>Print / Download
                            </button>
                        </li>
                        <li>
                            <button class="dropdown-item downloadPdfQuotation"
                                data-uid="<?php echo (int)$list->TransUID; ?>"
                                data-module="<?php echo (int)$list->ModuleUID; ?>"
                                data-num="<?php echo htmlspecialchars($list->UniqueNumber ?? ''); ?>">
                                <i class="bx bx-download me-2 text-primary"></i>Download PDF
                            </button>
                        </li>
                        <li>
                            <button class="dropdown-item thermalPrintTransaction" data-uid="<?php echo (int)$list->TransUID; ?>" data-module="<?php echo (int)$list->ModuleUID; ?>">
                                <i class="bx bx-receipt me-2 text-dark"></i>Thermal Print
                            </button>
                        </li>
                        <li><hr class="dropdown-divider my-1"></li>
                        <?php endif; ?>

                        <?php if (!$isDraft && ($hasMobile || $hasEmail)): ?>
                        <?php if ($hasMobile): ?>
                        <li>
                            <a class="dropdown-item inv-wa-link"
                               href="javascript:void(0)"
                               data-wa-url="https://wa.me/<?php echo $waNum; ?>?text=<?php echo $waMessageEncoded; ?>"
                               style="color:#25d366;">
                                <i class="bx bxl-whatsapp me-2"></i>Share via WhatsApp
                            </a>
                        </li>
                        <li>
                            <button class="dropdown-item comm-send-single"
                                    data-commtype="SMS"
                                    data-recipienttype="Customer"
                                    data-uid="<?php echo (int)$list->PartyUID; ?>"
                                    data-name="<?php echo htmlspecialchars($list->PartyName ?? ''); ?>"
                                    data-mobile="<?php echo htmlspecialchars($mobileNum); ?>"
                                    data-email="<?php echo htmlspecialchars($partyEmail); ?>"
                                    data-module-uid="<?php echo $quotModuleUID; ?>"
                                    style="color:#0097a7;">
                                <i class="bx bx-message-dots me-2"></i>Send SMS
                            </button>
                        </li>
                        <?php endif; ?>
                        <?php if ($hasEmail): ?>
                        <li>
                            <button class="dropdown-item comm-send-single"
                                    data-commtype="Email"
                                    data-recipienttype="Customer"
                                    data-uid="<?php echo (int)$list->PartyUID; ?>"
                                    data-trans-uid="<?php echo (int)$list->TransUID; ?>"
                                    data-name="<?php echo htmlspecialchars($list->PartyName ?? ''); ?>"
                                    data-mobile="<?php echo htmlspecialchars($mobileNum); ?>"
                                    data-email="<?php echo htmlspecialchars($partyEmail); ?>"
                                    data-module-uid="<?php echo $quotModuleUID; ?>"
                                    style="color:#1565c0;">
                                <i class="bx bx-envelope me-2"></i>Send Email
                            </button>
                        </li>
                        <?php endif; ?>
                        <li><hr class="dropdown-divider my-1"></li>
                        <?php endif; ?>

                        <?php if (!$isTerminal): ?>
                        <?php if (!$isDraft): ?>
                        <?php if (in_array($status, ['Pending', 'Accepted'])): ?>
                        <li>
                            <button class="dropdown-item quot-status-update"
                                    data-uid="<?php echo (int)$list->TransUID; ?>"
                                    data-status="Converted"
                                    data-target="Invoice"
                                    style="color:#0891b2;">
                                <i class="bx bx-transfer-alt me-2"></i>Convert to Invoice
                            </button>
                        </li>
                        <li>
                            <button class="dropdown-item quot-status-update"
                                    data-uid="<?php echo (int)$list->TransUID; ?>"
                                    data-status="Converted"
                                    data-target="SalesOrder"
                                    style="color:#7c3aed;">
                                <i class="bx bx-transfer-alt me-2"></i>Convert to Sales Order
                            </button>
                        </li>
                        <li><hr class="dropdown-divider my-1"></li>
                        <?php endif; ?>
                        <li>
                            <button class="dropdown-item quot-status-update text-warning"
                                    data-uid="<?php echo (int)$list->TransUID; ?>"
                                    data-status="Cancelled">
                                <i class="bx bx-x-circle me-2"></i>Cancel Quotation
                            </button>
                        </li>
                        <?php endif; ?>
                        <li>
                            <button class="dropdown-item text-danger deleteQuotation"
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
                <span class="text-muted mb-3" style="font-size:.9rem;">No quotations found</span>
                <a href="/quotations/create" class="btn btn-primary btn-sm px-4">
                    <i class="bx bx-plus me-1"></i>Create Quotation
                </a>
            </div>
        </td>
    </tr>
<?php endif; ?>
