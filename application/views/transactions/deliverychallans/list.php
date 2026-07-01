<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php
$cdnUrl = getenv('FILE_UPLOAD') == 'amazonaws' ? getenv('CDN_URL') : getenv('CFLARE_R2_CDN');
include_once(APPPATH . 'views/transactions/partials/party_avatar.php');
$moduleContext = 'deliverychallan';
include(APPPATH . 'views/transactions/partials/status_config.php');

$currency   = htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹');
$decimals   = $JwtData->GenSettings->DecimalPoints ?? 2;
$showSerial = $JwtData->GenSettings->SerialNoDisplay == 1;
$today      = time();

// Challan type badge colours
$challanTypeBadge = [
    'Returnable'     => 'badge text-bg-warning',
    'Non-Returnable' => 'badge text-bg-secondary',
    'Job Work'       => 'badge text-bg-info',
];

if (!empty($DataLists)):
    foreach ($DataLists as $list):
        $SerialNumber++;
        $status      = $list->Status ?? 'Draft';
        $isDraft     = $status === 'Draft';
        $isTerminal  = in_array($status, $terminalStatuses);
        $badgeClass  = $statusBadgeClass[$status]  ?? 'trans-badge-Draft';
        $icon        = $statusIcon[$status]         ?? 'bx-circle';

        $challanType  = $list->QuotationType ?? 'Non-Returnable';
        $typeBadge    = $challanTypeBadge[$challanType] ?? 'badge text-bg-secondary';

        // Contact fields
        $mobileNum   = trim($list->MobileNumber  ?? '');
        $countryCode = trim($list->CountryCode   ?? '');
        $partyEmail  = trim($list->EmailAddress  ?? '');
        $waNum       = $mobileNum ? preg_replace('/[^0-9]/', '', ($countryCode ?: '91') . $mobileNum) : '';
        $hasMobile   = $mobileNum !== '';
        $hasEmail    = $partyEmail !== '';

        // Overdue logic for Returnable challans
        $dueClass = 'trans-due-normal';
        $dueTag   = '';
        if (in_array($challanType, ['Returnable', 'Job Work']) && !$isDraft && !$isTerminal && !empty($list->ExpectedDeliveryDate)) {
            $dueTs = strtotime($list->ExpectedDeliveryDate);
            if ($dueTs < $today) {
                $dueClass = 'trans-due-overdue';
                $dueTag   = '<br><span style="font-size:.68rem;">Overdue</span>';
            } elseif ($dueTs <= strtotime('+3 days')) {
                $dueClass = 'trans-due-soon';
            }
        }
        $isOverdueRow  = ($dueClass === 'trans-due-overdue');
        $isDueSoonRow  = ($dueClass === 'trans-due-soon');
        $hasAttach     = !empty($list->AttachmentCount) && (int)$list->AttachmentCount > 0;

        // Days Out — only for Returnable/Job Work (goods expected to come back)
        // Not applicable for Non-Returnable (goods go out permanently)
        $daysOut = null;
        if (!$isDraft && in_array($challanType, ['Returnable', 'Job Work'])
            && in_array($status, ['Dispatched', 'Partially Returned']) && !empty($list->TransDate)) {
            $daysOut = (int) floor((time() - strtotime($list->TransDate)) / 86400);
        }
?>
    <tr class="<?php echo $isOverdueRow ? 'trans-row-overdue' : ($isDueSoonRow ? 'trans-row-due-soon' : ''); ?>">

        <td style="width:36px">
            <div class="form-check mb-0">
                <input class="form-check-input table-chkbox dcCheck" type="checkbox" value="<?php echo (int)$list->TransUID; ?>">
            </div>
        </td>

        <td class="<?php echo $showSerial ? '' : 'd-none'; ?> table-serialno" style="width:44px">
            <span class="text-muted" style="font-size:.78rem;"><?php echo $SerialNumber; ?></span>
        </td>

        <!-- Challan Number -->
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
                   data-type="deliverychallan"
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
                            data-module-uid="<?php echo (int)$list->ModuleUID; ?>"
                            title="<?php echo (int)$list->AttachmentCount; ?> attachment(s)"
                            style="font-size:.82rem;line-height:1;color:#0d6efd;">
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
            <?php if ($isDraft && (float)$list->NetAmount == 0): ?>
                <span class="text-muted">—</span>
            <?php else: ?>
                <div class="trans-amount-main"><?php echo $currency . ' ' . smartDecimal($list->NetAmount, $decimals, true); ?></div>
            <?php endif; ?>
        </td>

        <!-- Status -->
        <td>
            <span class="trans-badge <?php echo $badgeClass; ?>">
                <i class="bx <?php echo $icon; ?>" style="font-size:.8rem;"></i>
                <?php echo htmlspecialchars($status); ?>
            </span>
        </td>

        <!-- Challan Type -->
        <td>
            <span class="<?php echo $typeBadge; ?>" style="font-size:.68rem;">
                <?php echo htmlspecialchars($challanType); ?>
            </span>
        </td>

        <!-- Customer -->
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
                   data-wa-url="https://wa.me/<?php echo $waNum; ?>?text=Hi"
                   data-bs-toggle="tooltip" data-bs-trigger="hover" title="WhatsApp">
                    <i class="bx bxl-whatsapp"></i>
                </a>
                <button class="comm-send-single sms"
                    data-commtype="SMS"
                    data-recipienttype="Customer"
                    data-uid="<?php echo (int)$list->PartyUID; ?>"
                    data-name="<?php echo htmlspecialchars($list->PartyName ?? ''); ?>"
                    data-mobile="<?php echo htmlspecialchars($mobileNum); ?>"
                    data-email="<?php echo htmlspecialchars($partyEmail); ?>"
                    data-module-uid="<?php echo (int)$list->ModuleUID; ?>"
                    data-bs-toggle="tooltip" data-bs-trigger="hover" title="Send SMS">
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
                    data-module-uid="<?php echo (int)$list->ModuleUID; ?>"
                    data-bs-toggle="tooltip" data-bs-trigger="hover" title="Send Email">
                    <i class="bx bx-envelope"></i>
                </button>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </td>

        <!-- Expected Return Date + Days Out -->
        <td class="<?php echo $dueClass; ?>">
            <?php if (!$isDraft && in_array($challanType, ['Returnable', 'Job Work']) && !empty($list->ExpectedDeliveryDate)): ?>
                <?php echo format_datedisplay($list->ExpectedDeliveryDate); ?>
                <?php echo $dueTag; ?>
            <?php else: ?>
                <span class="text-muted">—</span>
            <?php endif; ?>
            <?php if ($daysOut !== null): ?>
                <div style="margin-top:3px;">
                    <?php
                        if ($daysOut > 7) {
                            $pillColor  = '#dc3545'; $pillBg = '#fde8ea'; $pillBorder = '#f5c2c7';
                        } elseif ($daysOut > 3) {
                            $pillColor  = '#cc5500'; $pillBg = '#ffe8cc'; $pillBorder = '#ffbb80';
                        } else {
                            $pillColor  = '#495057'; $pillBg = '#dee2e6'; $pillBorder = '#ced4da';
                        }
                    ?>
                    <span style="font-size:.68rem;font-weight:600;color:<?php echo $pillColor; ?>;background:<?php echo $pillBg; ?>;border:1px solid <?php echo $pillBorder; ?>;padding:1px 7px;border-radius:10px;display:inline-block;">
                        <?php echo $daysOut === 0 ? 'Today' : $daysOut . ' day' . ($daysOut > 1 ? 's' : '') . ' out'; ?>
                    </span>
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
            <div style="font-size:.68rem;color:#0d6efd;font-weight:500;"><?php echo $agoText; ?></div>
            <?php endif; ?>
            <div class="text-muted" style="font-size:.7rem;">by <?php echo htmlspecialchars($list->UpdatedBy ?? '—'); ?></div>
        </td>

        <!-- Actions -->
        <td style="width:50px">
            <div class="d-flex align-items-center justify-content-end gap-1">

                <?php if (!$isTerminal && ($isDraft || $status === 'Dispatched')): ?>
                <a class="btn btn-icon btn-sm text-warning" href="/deliverychallan/<?php echo (int)$list->TransUID; ?>/edit" title="Edit">
                    <i class="bx bx-edit"></i>
                </a>
                <?php endif; ?>

                <div class="dropdown">
                    <button class="trans-actions-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bx bx-dots-vertical-rounded fs-5"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="font-size:.82rem;min-width:175px;">

                        <!-- § Print -->
                        <?php if (!$isDraft): ?>
                        <li>
                            <button class="dropdown-item a4PrintTransaction" data-uid="<?php echo (int)$list->TransUID; ?>" data-module="<?php echo (int)$list->ModuleUID; ?>">
                                <i class="bx bx-printer me-2 text-primary"></i>Print / Download
                            </button>
                        </li>
                        <li>
                            <button class="dropdown-item downloadPdfTransaction" data-uid="<?php echo (int)$list->TransUID; ?>" data-module="<?php echo (int)$list->ModuleUID; ?>">
                                <i class="bx bx-download me-2 text-success"></i>Download PDF
                            </button>
                        </li>
                        <li>
                            <button class="dropdown-item thermalPrintTransaction" data-uid="<?php echo (int)$list->TransUID; ?>" data-module="<?php echo (int)$list->ModuleUID; ?>">
                                <i class="bx bx-receipt me-2 text-dark"></i>Thermal Print
                            </button>
                        </li>
                        <?php endif; ?>

                        <!-- § Communication (owns top divider; hidden when no mobile + no email) -->
                        <?php if (!$isDraft && ($hasMobile || $hasEmail)): ?>
                        <li><hr class="dropdown-divider my-1"></li>
                        <?php if ($hasMobile): ?>
                        <li>
                            <a class="dropdown-item inv-wa-link" href="javascript:void(0)"
                               data-wa-url="https://wa.me/<?php echo $waNum; ?>?text=Hi"
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
                                    data-module-uid="<?php echo (int)$list->ModuleUID; ?>"
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
                                    data-module-uid="<?php echo (int)$list->ModuleUID; ?>"
                                    style="color:#1565c0;">
                                <i class="bx bx-envelope me-2"></i>Send Email
                            </button>
                        </li>
                        <?php endif; ?>
                        <?php endif; ?>

                        <!-- § Row actions (owns top divider; hidden when draft) -->
                        <?php if (!$isDraft): ?>
                        <li><hr class="dropdown-divider my-1"></li>
                        <?php if ($status !== 'Cancelled'): ?>
                        <li>
                            <a class="dropdown-item" href="/packing-list/<?php echo (int)$list->TransUID; ?>">
                                <i class="bx bx-list-ul me-2 text-secondary"></i>Packing List
                            </a>
                        </li>
                        <?php endif; ?>
                        <?php if (in_array($status, ['Dispatched', 'Partially Returned']) && in_array($challanType, ['Returnable', 'Job Work'])): ?>
                        <li>
                            <button class="dropdown-item dc-partial-return-btn"
                                    data-uid="<?php echo (int)$list->TransUID; ?>"
                                    data-num="<?php echo htmlspecialchars($list->UniqueNumber ?? ''); ?>">
                                <i class="bx bx-adjust me-2 text-info"></i>Partial / Full Return
                            </button>
                        </li>
                        <?php endif; ?>
                        <?php if ($status === 'Dispatched' && !in_array($challanType, ['Returnable', 'Job Work'])): ?>
                        <li>
                            <button class="dropdown-item dc-status-update"
                                    data-uid="<?php echo (int)$list->TransUID; ?>"
                                    data-num="<?php echo htmlspecialchars($list->UniqueNumber ?? ''); ?>"
                                    data-status="Delivered">
                                <i class="bx bx-check-circle me-2 text-success"></i>Mark as Delivered
                            </button>
                        </li>
                        <?php endif; ?>
                        <?php if ($status === 'Delivered'): ?>
                        <li>
                            <button class="dropdown-item convertChallanToInvoice"
                                    data-uid="<?php echo (int)$list->TransUID; ?>"
                                    data-num="<?php echo htmlspecialchars($list->UniqueNumber ?? ''); ?>">
                                <i class="bx bx-receipt me-2 text-success"></i>Convert to Invoice
                            </button>
                        </li>
                        <?php endif; ?>
                        <?php endif; ?>

                        <!-- § Clone (owns top divider; hidden when draft) -->
                        <?php if (!$isDraft): ?>
                        <li><hr class="dropdown-divider my-1"></li>
                        <li>
                            <button class="dropdown-item duplicateDeliveryChallan"
                                    data-uid="<?php echo (int)$list->TransUID; ?>"
                                    data-num="<?php echo htmlspecialchars($list->UniqueNumber ?? ''); ?>">
                                <i class="bx bx-copy me-2 text-info"></i>Clone
                            </button>
                        </li>
                        <?php endif; ?>

                        <!-- § Danger (owns top divider; hidden when terminal) -->
                        <?php if (!$isTerminal): ?>
                        <?php if (!$isDraft): ?><li><hr class="dropdown-divider my-1"></li><?php endif; ?>
                        <?php if (!$isDraft): ?>
                        <li>
                            <button class="dropdown-item text-warning dc-status-update"
                                    data-uid="<?php echo (int)$list->TransUID; ?>"
                                    data-num="<?php echo htmlspecialchars($list->UniqueNumber ?? ''); ?>"
                                    data-status="Cancelled">
                                <i class="bx bx-x-circle me-2"></i>Cancel
                            </button>
                        </li>
                        <?php endif; ?>
                        <li>
                            <button class="dropdown-item text-danger deleteDeliveryChallan"
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
        <td colspan="10">
            <div class="d-flex flex-column align-items-center py-5">
                <img src="/assets/img/elements/no-record-found.png" alt="No Records" class="img-fluid mb-3" style="max-height:150px;object-fit:contain;">
                <span class="text-muted mb-3" style="font-size:.9rem;">No delivery challans found</span>
                <a href="/deliverychallan/create" class="btn btn-primary btn-sm px-4">
                    <i class="bx bx-plus me-1"></i>Create Delivery Challan
                </a>
            </div>
        </td>
    </tr>
<?php endif; ?>
