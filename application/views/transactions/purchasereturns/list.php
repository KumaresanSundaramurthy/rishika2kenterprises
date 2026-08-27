<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php
$cdnUrl = getenv('FILE_UPLOAD') == 'amazonaws' ? getenv('CDN_URL') : getenv('CFLARE_R2_CDN');
include_once(APPPATH . 'views/transactions/partials/party_avatar.php');
$moduleContext = 'purchasereturn';
include(APPPATH . 'views/transactions/partials/status_config.php');

$currency   = htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹');
$showSerial = $JwtData->GenSettings->SerialNoDisplay == 1;

if (!empty($DataLists)):
    foreach ($DataLists as $list):
        $SerialNumber++;
        $status      = $list->Status ?? 'Draft';
        $isDraft     = $status === 'Draft';
        $isCancelled = $status === 'Cancelled';
        $isTerminal  = in_array($status, ['Paid', 'Cancelled']);
        $badgeClass  = $statusBadgeClass[$status]  ?? 'trans-badge-Draft';
        $icon        = $statusIcon[$status]         ?? 'bx-circle';
        $transitions = $moduleTransitions[$status]  ?? [];

        $netAmt     = (float)($list->NetAmount  ?? 0);
        $paidAmt    = (float)($list->PaidAmount ?? 0);
        $pendingAmt = max(0, round($netAmt - $paidAmt));

        if ($isDraft) {
            $refundBadge = '';
        } elseif ($paidAmt <= 0) {
            $refundBadge = '<span class="badge bg-label-warning" style="font-size:.68rem;">' . t('status_pending', 'Pending') . '</span>';
        } elseif ($pendingAmt <= 0.01) {
            $refundBadge = '<span class="badge bg-label-success" style="font-size:.68rem;">' . t('status_settled', 'Settled') . '</span>';
        } else {
            $refundBadge = '<span class="badge bg-label-info" style="font-size:.68rem;">' . t('status_partial', 'Partial') . '</span>';
        }

        $showPending = !$isDraft && $pendingAmt > 0 && !in_array($status, ['Cancelled', 'Rejected']);

        $mobileNum        = trim($list->MobileNumber ?? '');
        $countryCode      = trim($list->CountryCode ?? '');
        $partyEmail       = trim($list->EmailAddress ?? '');
        $waNum            = $mobileNum ? preg_replace('/[^0-9]/', '', ($countryCode ?: '91') . $mobileNum) : '';
        $hasMobile        = $mobileNum !== '';
        $hasEmail         = $partyEmail !== '';
        $prPartyName      = $list->PartyName ?? 'Vendor';
        $prDocNum         = $list->UniqueNumber ?? '';
        $waMsg            = "Hello *{$prPartyName}*,\n\nRegarding Purchase Return *{$prDocNum}*.\n\nThanks";
        $waMessageEncoded = rawurlencode($waMsg);
        $hasAttach        = !empty($list->AttachmentCount) && (int)$list->AttachmentCount > 0;
?>
    <tr>

        <td style="width:36px">
            <div class="form-check mb-0">
                <input class="form-check-input table-chkbox prCheck" type="checkbox" value="<?php echo (int)$list->TransUID; ?>">
            </div>
        </td>

        <td class="<?php echo $showSerial ? '' : 'd-none'; ?> table-serialno" style="width:44px">
            <span class="text-muted" style="font-size:.78rem;"><?php echo $SerialNumber; ?></span>
        </td>

        <!-- Return Number -->
        <td>
            <?php if ($isDraft || empty($list->UniqueNumber)): ?>
                <span class="trans-doc-draft"><i class="bx bx-pencil me-1" style="font-size:.8rem;"></i><?php echo t('status_draft', 'Draft'); ?></span>
                <?php if (!empty($list->TransDate)): ?>
                    <div class="text-muted" style="font-size:.72rem;"><?php echo htmlspecialchars(format_datedisplay($list->TransDate)); ?></div>
                <?php endif; ?>
            <?php else: ?>
                <a href="javascript:void(0)" class="trans-doc-number viewTransaction" data-uid="<?php echo (int)$list->TransUID; ?>" data-module="<?php echo (int)$list->ModuleUID; ?>" data-type="purchasereturn" data-number="<?php echo htmlspecialchars($list->UniqueNumber ?? ''); ?>" data-date="<?php echo htmlspecialchars($list->TransDate ?? ''); ?>" data-status="<?php echo htmlspecialchars($list->Status ?? ''); ?>">
                    <?php echo htmlspecialchars($list->UniqueNumber); ?>
                </a>
                <div class="d-flex align-items-center gap-2 mt-1">
                    <div class="text-muted" style="font-size:.72rem;"><?php echo htmlspecialchars(format_datedisplay($list->TransDate)); ?></div>
                    <?php if ($hasAttach): ?>
                    <button type="button" class="btn btn-link p-0 transAttachBtn"
                            data-uid="<?php echo (int)$list->TransUID; ?>"
                            data-num="<?php echo htmlspecialchars($list->UniqueNumber ?? ''); ?>"
                            data-url="/transactions/getAttachments"
                            data-module-uid="<?php echo (int)$list->ModuleUID; ?>"
                            title="<?php echo (int)$list->AttachmentCount; ?> attachment(s)"
                            style="font-size:.82rem;line-height:1;color:#0d6efd;">
                        <i class="bx bx-paperclip"></i>
                    </button>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </td>

        <!-- Amount -->
        <td>
            <?php if ($isDraft && $netAmt == 0): ?>
                <span class="text-muted">—</span>
            <?php else: ?>
                <div class="trans-amount-main"><?php echo $currency . ' ' . smartDecimal($netAmt); ?></div>
                <?php if (!$isDraft && $pendingAmt > 0 && $pendingAmt < $netAmt): ?>
                <div class="text-muted" style="font-size:.7rem;">Pending: <?php echo $currency . ' ' . smartDecimal($pendingAmt); ?></div>
                <?php endif; ?>
            <?php endif; ?>
        </td>

        <!-- Refund Status -->
        <td><?php echo $refundBadge; ?></td>

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
                <ul class="dropdown-menu dropdown-menu-end r2k-action-menu">
                    <?php foreach ($transitions as $t): ?>
                    <li>
                        <button class="dropdown-item pr-status-update"
                                data-uid="<?php echo (int)$list->TransUID; ?>"
                                data-status="<?php echo htmlspecialchars($t['db']); ?>"
                                <?php if ($t['db'] === 'Cancelled'): ?>
                                data-num="<?php echo htmlspecialchars($list->UniqueNumber ?? ''); ?>"
                                data-refund="<?php echo (float)($list->CancelCashRefunded ?? 0); ?>"
                                <?php endif; ?>>
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

        <!-- Vendor -->
        <td class="inv-party-td">
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
                <a href="javascript:void(0)" class="wa inv-wa-link"
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
                    data-module-uid="<?php echo (int)$list->ModuleUID; ?>"
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
                    data-name="<?php echo htmlspecialchars($list->PartyName ?? ''); ?>"
                    data-mobile="<?php echo htmlspecialchars($mobileNum); ?>"
                    data-email="<?php echo htmlspecialchars($partyEmail); ?>"
                    data-module-uid="<?php echo (int)$list->ModuleUID; ?>"
                    data-bs-toggle="tooltip"
                    data-bs-trigger="hover"
                    title="Send Email">
                    <i class="bx bx-envelope"></i>
                </button>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </td>

        <!-- Date -->
        <td>
            <?php if (!$isDraft && !empty($list->TransDate)): ?>
                <span class="text-muted" style="font-size:.82rem;"><?php echo format_datedisplay($list->TransDate); ?></span>
            <?php else: ?>
                <span class="text-muted">—</span>
            <?php endif; ?>
        </td>

        <!-- Last Updated -->
        <td>
            <?php $updatedTs = viewPageDateTimeFormat($list->UpdatedOn ?? null, $JwtData->User->Timezone ?? 'UTC', 2); ?>
            <div class="r2k-col-date"><?php echo $updatedTs->formatted; ?></div>
            <?php if ($updatedTs->ago): ?>
            <div class="r2k-col-date-ago"><?php echo $updatedTs->ago; ?></div>
            <?php endif; ?>
            <div class="text-muted r2k-col-date-by">by <?php echo htmlspecialchars($list->UpdatedBy ?? '—'); ?></div>
        </td>

        <!-- Actions -->
        <td style="width:110px">
            <div class="d-flex align-items-center justify-content-end gap-1">

                <?php if ($showPending): ?>
                <button type="button"
                        class="btn inv-pay-quick-btn prReceivePayment"
                        data-uid="<?php echo (int)$list->TransUID; ?>"
                        data-num="<?php echo htmlspecialchars($list->UniqueNumber ?? ''); ?>"
                        data-date="<?php echo htmlspecialchars(format_datedisplay($list->TransDate ?? '')); ?>"
                        data-party="<?php echo htmlspecialchars($list->PartyName ?? ''); ?>"
                        data-total="<?php echo $netAmt; ?>"
                        data-paid="<?php echo $paidAmt; ?>"
                        data-pending="<?php echo $pendingAmt; ?>"
                        title="Record Refund — <?php echo $currency . ' ' . smartDecimal($pendingAmt); ?> pending">
                    <?php echo $currency; ?>
                </button>
                <?php endif; ?>

                <?php if (!$isTerminal): ?>
                <a class="btn btn-icon btn-sm text-warning inv-row-action"
                   href="/purchasereturns/edit/<?php echo (int)$list->TransUID; ?>"
                   data-bs-toggle="tooltip" data-bs-trigger="hover" title="Edit">
                    <i class="bx bx-edit"></i>
                </a>
                <?php endif; ?>

                <div class="dropdown">
                    <button class="trans-actions-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bx bx-dots-vertical-rounded fs-5"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end r2k-action-menu">

                        <?php if (!$isDraft): ?>
                        <li>
                            <button class="dropdown-item a4PrintTransaction" data-uid="<?php echo (int)$list->TransUID; ?>" data-module="<?php echo (int)$list->ModuleUID; ?>">
                                <i class="bx bx-printer me-2 text-primary"></i><?php echo t('act_print_download', 'Print / Download'); ?>
                            </button>
                        </li>
                        <li>
                            <button class="dropdown-item downloadPdfTransaction" data-uid="<?php echo (int)$list->TransUID; ?>" data-module="<?php echo (int)$list->ModuleUID; ?>">
                                <i class="bx bx-download me-2 text-success"></i><?php echo t('act_download_pdf', 'Download PDF'); ?>
                            </button>
                        </li>
                        <li>
                            <button class="dropdown-item thermalPrintTransaction" data-uid="<?php echo (int)$list->TransUID; ?>" data-module="<?php echo (int)$list->ModuleUID; ?>">
                                <i class="bx bx-receipt me-2 text-dark"></i><?php echo t('act_thermal_print', 'Thermal Print'); ?>
                            </button>
                        </li>
                        <li><hr class="dropdown-divider my-1"></li>
                        <?php endif; ?>

                        <?php if ($showPending): ?>
                        <li>
                            <button class="dropdown-item prReceivePayment"
                                    data-uid="<?php echo (int)$list->TransUID; ?>"
                                    data-num="<?php echo htmlspecialchars($list->UniqueNumber ?? ''); ?>"
                                    data-date="<?php echo htmlspecialchars(format_datedisplay($list->TransDate ?? '')); ?>"
                                    data-party="<?php echo htmlspecialchars($list->PartyName ?? ''); ?>"
                                    data-total="<?php echo $netAmt; ?>"
                                    data-paid="<?php echo $paidAmt; ?>"
                                    data-pending="<?php echo $pendingAmt; ?>">
                                <i class="bx bx-transfer me-2 text-success"></i><?php echo t('act_record_refund', 'Record Refund'); ?>
                            </button>
                        </li>
                        <li>
                            <button class="dropdown-item prApplyDebit"
                                    data-uid="<?php echo (int)$list->TransUID; ?>"
                                    data-num="<?php echo htmlspecialchars($list->UniqueNumber ?? ''); ?>"
                                    data-party="<?php echo htmlspecialchars($list->PartyName ?? ''); ?>"
                                    data-partyuid="<?php echo (int)$list->PartyUID; ?>"
                                    data-balance="<?php echo $pendingAmt; ?>">
                                <i class="bx bx-credit-card me-2 text-primary"></i><?php echo t('act_apply_debit', 'Apply Debit to Purchase'); ?>
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
                                <i class="bx bxl-whatsapp me-2"></i><?php echo t('act_share_whatsapp', 'Share via WhatsApp'); ?>
                            </a>
                        </li>
                        <li>
                            <button class="dropdown-item comm-send-single"
                                    data-commtype="SMS"
                                    data-recipienttype="Vendor"
                                    data-uid="<?php echo (int)$list->PartyUID; ?>"
                                    data-name="<?php echo htmlspecialchars($list->PartyName ?? ''); ?>"
                                    data-mobile="<?php echo htmlspecialchars($mobileNum); ?>"
                                    data-email="<?php echo htmlspecialchars($partyEmail); ?>"
                                    data-module-uid="<?php echo (int)$list->ModuleUID; ?>"
                                    style="color:#0097a7;">
                                <i class="bx bx-message-dots me-2"></i><?php echo t('act_send_sms', 'Send SMS'); ?>
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
                                    data-module-uid="<?php echo (int)$list->ModuleUID; ?>"
                                    style="color:#1565c0;">
                                <i class="bx bx-envelope me-2"></i><?php echo t('act_send_email', 'Send Email'); ?>
                            </button>
                        </li>
                        <?php endif; ?>
                        <?php endif; ?>

                        <?php if (!$isCancelled): ?>
                        <li><hr class="dropdown-divider my-1"></li>
                        <?php if (!$isDraft): ?>
                        <li>
                            <button class="dropdown-item text-warning pr-status-update"
                                    data-uid="<?php echo (int)$list->TransUID; ?>"
                                    data-num="<?php echo htmlspecialchars($list->UniqueNumber ?? ''); ?>"
                                    data-status="Cancelled">
                                <i class="bx bx-x-circle me-2"></i><?php echo t('cancel', 'Cancel'); ?>
                            </button>
                        </li>
                        <?php endif; ?>
                        <li>
                            <button class="dropdown-item text-danger deletePurchaseReturn"
                                    data-uid="<?php echo (int)$list->TransUID; ?>"
                                    data-num="<?php echo htmlspecialchars($list->UniqueNumber ?? 'Draft'); ?>">
                                <i class="bx bx-trash me-2"></i><?php echo t('delete', 'Delete'); ?>
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
        <td colspan="10">
            <div class="d-flex flex-column align-items-center py-5">
                <img src="/assets/img/elements/no-record-found.png" alt="No Records" class="img-fluid mb-3" style="max-height:150px;object-fit:contain;">
                <span class="text-muted mb-3" style="font-size:.9rem;"><?php echo t('empty_purchase_returns', 'No purchase returns found'); ?></span>
                <a href="/purchasereturns/create" class="btn btn-primary btn-sm px-4">
                    <i class="bx bx-plus me-1"></i><?php echo t('create_purchase_return', 'Create Purchase Return'); ?>
                </a>
            </div>
        </td>
    </tr>
<?php endif; ?>
