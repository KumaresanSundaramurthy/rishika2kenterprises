<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php
$cdnUrl = getenv('FILE_UPLOAD') == 'amazonaws' ? getenv('CDN_URL') : getenv('CFLARE_R2_CDN');
include_once(APPPATH . 'views/transactions/partials/party_avatar.php');
$statusBadge = [
    'Draft'     => 'bg-label-secondary',
    'Received'  => 'bg-label-info',
    'Closed'    => 'bg-label-success',
    'Cancelled' => 'bg-label-danger',
];

$statusTransitions = [
    'Draft'     => [
        ['db' => 'Received', 'label' => 'Receive PO'],
    ],
    'Received'  => [
        ['db' => 'Closed',    'label' => 'Close'],
        ['db' => 'Cancelled', 'label' => 'Cancel'],
    ],
    'Closed'    => [],
    'Cancelled' => [],
];

$currency   = htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '');
$decimals   = $JwtData->GenSettings->DecimalPoints ?? 2;
$showSerial = $JwtData->GenSettings->SerialNoDisplay == 1;

if (!empty($DataLists)) {
    foreach ($DataLists as $list) {
        $SerialNumber++;
        $isDraft     = ($list->Status === 'Draft');
        $isClosed    = ($list->Status === 'Closed');
        $isCancelled = ($list->Status === 'Cancelled');
        $isTerminal  = $isClosed || $isCancelled;
        $badge       = $statusBadge[$list->Status] ?? 'bg-label-secondary';
        $transitions = $statusTransitions[$list->Status] ?? [];

        $edLabel = '';
        $edClass = '';
        if (!$isDraft && !empty($list->ValidityDate)) {
            $valTs    = strtotime($list->ValidityDate);
            $todayTs  = strtotime(date('Y-m-d'));
            $diffDays = (int)(($valTs - $todayTs) / 86400);
            if ($isClosed) {
                $edLabel = 'Completed'; $edClass = 'text-success';
            } elseif ($isCancelled) {
                $edLabel = 'Cancelled'; $edClass = 'text-danger';
            } elseif ($diffDays < 0) {
                $edLabel = 'Overdue'; $edClass = 'text-danger';
            } elseif ($diffDays === 0) {
                $edLabel = 'Today'; $edClass = 'text-warning';
            } else {
                $edLabel = 'in ' . $diffDays . ' day' . ($diffDays > 1 ? 's' : '');
                $edClass = 'text-primary';
            }
        }

        $mobileNum        = trim($list->MobileNumber ?? '');
        $countryCode      = trim($list->CountryCode ?? '');
        $partyEmail       = trim($list->EmailAddress ?? '');
        $waNum            = $mobileNum ? preg_replace('/[^0-9]/', '', ($countryCode ?: '91') . $mobileNum) : '';
        $hasMobile        = $mobileNum !== '';
        $hasEmail         = $partyEmail !== '';
        $poPartyName      = $list->PartyName ?? 'Vendor';
        $poDocNum         = $list->UniqueNumber ?? '';
        $waMsg            = "Hello *{$poPartyName}*,\n\nRegarding Purchase Order *{$poDocNum}*.\n\nThanks";
        $waMessageEncoded = rawurlencode($waMsg);
        $hasAttach        = !empty($list->AttachmentCount) && (int)$list->AttachmentCount > 0;
?>
        <tr>
            <td style="width:40px">
                <div class="form-check">
                    <input class="form-check-input table-chkbox poCheck" type="checkbox" value="<?php echo (int) $list->TransUID; ?>">
                </div>
            </td>
            <td class="<?php echo $showSerial ? '' : 'd-none'; ?> table-serialno" style="width:50px"><?php echo $SerialNumber; ?></td>

            <!-- # PO Number -->
            <td>
                <?php if (!$isDraft && !empty($list->UniqueNumber)): ?>
                    <a href="javascript:void(0)" class="fw-semibold text-primary text-decoration-underline viewTransaction d-block lh-sm"
                       data-uid="<?php echo (int) $list->TransUID; ?>"
                       data-module="<?php echo (int) $list->ModuleUID; ?>"
                       data-type="purchaseorder"
                       data-number="<?php echo htmlspecialchars($list->UniqueNumber ?? ''); ?>"
                       data-date="<?php echo htmlspecialchars($list->TransDate ?? ''); ?>"
                       data-status="<?php echo htmlspecialchars($list->Status ?? ''); ?>">
                        <?php echo htmlspecialchars($list->UniqueNumber); ?>
                    </a>
                <?php else: ?>
                    <span class="text-muted fst-italic" style="font-size:.82rem;">Draft</span>
                <?php endif; ?>
                <div class="d-flex align-items-center gap-2">
                    <div class="apex-doc-meta"><?php echo htmlspecialchars(format_datedisplay($list->TransDate)); ?></div>
                    <?php if (!$isDraft && $hasAttach): ?>
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
                <div class="apex-doc-meta">by <?php echo htmlspecialchars($list->CreatedBy ?? '—'); ?></div>
            </td>

            <!-- Amount -->
            <td>
                <?php if ($isDraft && $list->NetAmount == 0): ?>
                    <span class="text-muted">—</span>
                <?php else: ?>
                    <div class="text-dark fw-semibold"><?php echo $currency . ' ' . smartDecimal($list->NetAmount, $decimals, true); ?></div>
                <?php endif; ?>
            </td>

            <!-- Status — clickable badge -->
            <td>
                <?php if (!empty($transitions)): ?>
                <div class="dropdown">
                    <span class="badge <?php echo $badge; ?> cursor-pointer"
                          data-bs-toggle="dropdown"
                          data-uid="<?php echo (int) $list->TransUID; ?>"
                          data-current="<?php echo htmlspecialchars($list->Status); ?>"
                          title="Click to change status">
                        <?php echo htmlspecialchars($list->Status); ?> <i class="bx bx-chevron-down" style="font-size:.7rem;vertical-align:middle"></i>
                    </span>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                        <?php foreach ($transitions as $t): ?>
                        <li>
                            <button class="dropdown-item po-status-update"
                                    data-uid="<?php echo (int) $list->TransUID; ?>"
                                    data-status="<?php echo htmlspecialchars($t['db']); ?>">
                                <?php echo htmlspecialchars($t['label']); ?>
                            </button>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php else: ?>
                    <span class="badge <?php echo $badge; ?>"><?php echo htmlspecialchars($list->Status); ?></span>
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

            <!-- PO Date -->
            <td><?php echo htmlspecialchars(format_datedisplay($list->TransDate)); ?></td>

            <!-- Expected Date -->
            <td>
                <?php if (!$isDraft && !empty($list->ValidityDate)): ?>
                    <div style="font-size:.82rem;"><?php echo format_datedisplay($list->ValidityDate); ?></div>
                    <?php if ($edLabel): ?>
                    <div class="apex-ed-label <?php echo $edClass; ?>"><?php echo $edLabel; ?></div>
                    <?php endif; ?>
                <?php else: ?>
                    <span class="text-muted">—</span>
                <?php endif; ?>
            </td>

            <!-- Last Updated -->
            <td class="small text-muted">
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
                <div class="r2k-col-date"><?php echo $updatedOn ? changeTimeZonefromDateTime($updatedOn, $JwtData->User->Timezone, 2) : '—'; ?></div>
                <?php if ($within24h): ?>
                <div class="r2k-col-date-ago"><?php echo $agoText; ?></div>
                <?php endif; ?>
                <div class="text-muted r2k-col-date-by">by <?php echo htmlspecialchars($list->UpdatedBy ?? '—'); ?></div>
            </td>

            <!-- Actions -->
            <td style="width:50px">
                <div class="d-flex align-items-center justify-content-end gap-1">

                    <?php if (!$isTerminal): ?>
                    <a class="btn btn-icon btn-sm text-warning" href="/purchaseorders/edit/<?php echo (int) $list->TransUID; ?>" title="Edit">
                        <i class="bx bx-edit fs-6"></i>
                    </a>
                    <?php endif; ?>

                    <div class="dropdown">
                        <button class="btn btn-icon btn-sm" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bx bx-dots-vertical-rounded fs-5 text-muted"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">

                            <?php if (!$isDraft): ?>
                            <li>
                                <button class="dropdown-item a4PrintTransaction"
                                    data-uid="<?php echo (int) $list->TransUID; ?>" data-module="<?php echo (int) $list->ModuleUID; ?>">
                                    <i class="bx bx-file me-2 text-primary"></i>Print (A4 / A5)
                            </button>
                            </li>
                            <li><hr class="dropdown-divider my-1"></li>
                            <?php endif; ?>

                            <?php if (!$isTerminal): ?>
                            <li><hr class="dropdown-divider my-1"></li>
                            <?php if (!$isDraft): ?>
                            <li>
                                <button class="dropdown-item text-warning po-status-update"
                                        data-uid="<?php echo (int) $list->TransUID; ?>"
                                        data-num="<?php echo htmlspecialchars($list->UniqueNumber ?? ''); ?>"
                                        data-status="Cancelled">
                                    <i class="bx bx-x-circle me-2"></i>Cancel
                                </button>
                            </li>
                            <?php endif; ?>
                            <li>
                                <button class="dropdown-item text-danger deletePO"
                                        data-uid="<?php echo (int) $list->TransUID; ?>"
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
<?php }
} else { ?>
    <tr>
        <td colspan="10">
            <div class="d-flex flex-column align-items-center py-5">
                <img src="/assets/img/elements/no-record-found.png" alt="No Records" class="img-fluid mb-3" style="max-height:160px;object-fit:contain">
                <span class="text-muted mb-3">No purchase orders found</span>
                <a href="/purchaseorders/create" class="btn btn-primary btn-sm px-3">
                    <i class="bx bx-plus me-1"></i>Create Purchase Order
                </a>
            </div>
        </td>
    </tr>
<?php } ?>
