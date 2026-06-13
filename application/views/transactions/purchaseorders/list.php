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
                <div class="apex-doc-meta"><?php echo htmlspecialchars(format_datedisplay($list->TransDate)); ?></div>
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
            <td>
                <div class="apex-party-cell">
                    <div class="d-flex align-items-center gap-2">
                        <?php partyAvatar($list->PartyName, $list->PartyImage ?? null, $cdnUrl); ?>
                        <div class="apex-party-info">
                            <div class="apex-party-name"><?php echo htmlspecialchars($list->PartyName ?? '—'); ?></div>
                            <?php if (!empty($list->PartyArea)): ?>
                            <div class="apex-party-sub">
                                <i class="bx bx-map-pin text-muted"></i>
                                <?php echo htmlspecialchars($list->PartyArea); ?>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($list->MobileNumber)): ?>
                            <div class="apex-party-sub">
                                <span class="copy-mobile cursor-pointer" data-mobile="<?php echo htmlspecialchars($list->MobileNumber); ?>" title="Click to copy">
                                    <?php echo ($list->CountryCode ? htmlspecialchars($list->CountryCode) . ' ' : '') . htmlspecialchars($list->MobileNumber); ?>
                                </span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <!-- Hover actions -->
                    <?php $hasMobile = !empty($list->MobileNumber); $hasEmail = !empty($list->EmailAddress); ?>
                    <?php if ($hasMobile || $hasEmail): ?>
                    <div class="apex-party-actions">
                        <?php if ($hasMobile): ?>
                        <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', ($list->CountryCode ?? '') . $list->MobileNumber); ?>?text=Hi"
                           target="_blank" class="apex-party-action wa" title="WhatsApp">
                            <i class="bx bxl-whatsapp"></i>
                        </a>
                        <button class="apex-party-action sms comm-send-single"
                                data-commtype="SMS"
                                data-recipienttype="Vendor"
                                data-uid="<?php echo (int) $list->PartyUID; ?>"
                                data-name="<?php echo htmlspecialchars($list->PartyName ?? ''); ?>"
                                data-mobile="<?php echo htmlspecialchars($list->MobileNumber); ?>"
                                data-email="<?php echo htmlspecialchars($list->EmailAddress ?? ''); ?>"
                                title="Send SMS">
                            <i class="bx bx-message-rounded"></i>
                        </button>
                        <?php endif; ?>
                        <?php if ($hasEmail): ?>
                        <a href="mailto:<?php echo htmlspecialchars($list->EmailAddress); ?>"
                           class="apex-party-action em" title="Send Email">
                            <i class="bx bx-envelope"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
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
                <div style="font-size:.78rem;"><?php echo $updatedOn ? changeTimeZonefromDateTime($updatedOn, $JwtData->User->Timezone, 2) : '—'; ?></div>
                <?php if ($within24h): ?>
                <div style="font-size:.68rem;color:#0d6efd;font-weight:500;"><?php echo $agoText; ?></div>
                <?php endif; ?>
                <div style="font-size:.7rem;">by <?php echo htmlspecialchars($list->UpdatedBy ?? '—'); ?></div>
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
