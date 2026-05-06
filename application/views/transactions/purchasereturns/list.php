<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php
$cdnUrl = getenv('FILE_UPLOAD') == 'amazonaws' ? getenv('CDN_URL') : getenv('CFLARE_R2_CDN');
include_once(APPPATH . 'views/transactions/partials/party_avatar.php');
$moduleContext = 'purchasereturn';
include(APPPATH . 'views/transactions/partials/status_config.php');

$currency   = htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹');
$decimals   = $JwtData->GenSettings->DecimalPoints ?? 2;
$showSerial = $JwtData->GenSettings->SerialNoDisplay == 1;

if (!empty($DataLists)):
    foreach ($DataLists as $list):
        $SerialNumber++;
        $status      = $list->Status ?? 'Draft';
        $isDraft     = $status === 'Draft';
        $isTerminal  = in_array($status, $terminalStatuses);
        $badgeClass  = $statusBadgeClass[$status]  ?? 'trans-badge-Draft';
        $icon        = $statusIcon[$status]         ?? 'bx-circle';
        $transitions = $moduleTransitions[$status]  ?? [];
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
                <span class="trans-doc-draft"><i class="bx bx-pencil me-1" style="font-size:.8rem;"></i>Draft</span>
                <?php if (!empty($list->TransDate)): ?>
                    <div class="text-muted" style="font-size:.72rem;"><?php echo htmlspecialchars(format_datedisplay($list->TransDate, 'd M Y')); ?></div>
                <?php endif; ?>
            <?php else: ?>
                <a href="javascript:void(0)" class="trans-doc-number viewTransaction" data-uid="<?php echo (int)$list->TransUID; ?>" data-module="<?php echo (int)$list->ModuleUID; ?>" data-type="purchasereturn" data-number="<?php echo htmlspecialchars($list->UniqueNumber ?? ''); ?>" data-date="<?php echo htmlspecialchars($list->TransDate ?? ''); ?>" data-status="<?php echo htmlspecialchars($list->Status ?? ''); ?>">
                    <?php echo htmlspecialchars($list->UniqueNumber); ?>
                </a>
                <div class="text-muted" style="font-size:.72rem;"><?php echo htmlspecialchars(format_datedisplay($list->TransDate, 'd M Y')); ?></div>
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
                        <button class="dropdown-item pr-status-update"
                                data-uid="<?php echo (int)$list->TransUID; ?>"
                                data-status="<?php echo htmlspecialchars($t['db']); ?>">
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
        <td>
            <div class="d-flex align-items-center gap-2">
                <?php partyAvatar($list->PartyName, $list->PartyImage ?? null, $cdnUrl); ?>
                <div>
                    <div class="trans-party-name"><?php echo htmlspecialchars($list->PartyName ?? '—'); ?></div>
                    <?php if (!empty($list->MobileNumber)): ?>
                    <div class="trans-party-mobile d-flex align-items-center gap-1 mt-1">
                        <span class="copy-mobile cursor-pointer" data-mobile="<?php echo htmlspecialchars($list->MobileNumber); ?>" title="Click to copy">
                            <?php echo ($list->CountryCode ? htmlspecialchars($list->CountryCode) . ' ' : '') . htmlspecialchars($list->MobileNumber); ?>
                        </span>
                        <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', ($list->CountryCode ?? '') . $list->MobileNumber); ?>?text=Hi"
                           target="_blank" class="text-success" title="WhatsApp" style="line-height:1;">
                            <i class="bx bxl-whatsapp fs-6"></i>
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </td>

        <!-- Date -->
        <td>
            <?php if (!$isDraft && !empty($list->TransDate)): ?>
                <span class="text-muted" style="font-size:.82rem;"><?php echo format_datedisplay($list->TransDate, 'd M Y'); ?></span>
            <?php else: ?>
                <span class="text-muted">—</span>
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
                <a class="btn btn-icon btn-sm text-warning" href="/purchasereturns/edit/<?php echo (int)$list->TransUID; ?>" title="Edit">
                    <i class="bx bx-edit"></i>
                </a>
                <?php endif; ?>

                <div class="dropdown">
                    <button class="trans-actions-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bx bx-dots-vertical-rounded fs-5"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="font-size:.82rem;min-width:160px;">

                        <?php if (!$isDraft): ?>
                        <li>
                            <button class="dropdown-item thermalPrintTransaction" data-uid="<?php echo (int)$list->TransUID; ?>" data-module="<?php echo (int)$list->ModuleUID; ?>">
                                <i class="bx bx-receipt me-2 text-dark"></i>Thermal Print
                            </button>
                        </li>
                        <li><hr class="dropdown-divider my-1"></li>
                        <?php endif; ?>

                        <li>
                            <button class="dropdown-item duplicatePurchaseReturn" data-uid="<?php echo (int)$list->TransUID; ?>">
                                <i class="bx bx-copy me-2 text-secondary"></i>Duplicate
                            </button>
                        </li>

                        <?php if (!$isTerminal): ?>
                        <li><hr class="dropdown-divider my-1"></li>
                        <?php if (!$isDraft): ?>
                        <li>
                            <button class="dropdown-item text-warning pr-status-update"
                                    data-uid="<?php echo (int)$list->TransUID; ?>"
                                    data-num="<?php echo htmlspecialchars($list->UniqueNumber ?? ''); ?>"
                                    data-status="Cancelled">
                                <i class="bx bx-x-circle me-2"></i>Cancel
                            </button>
                        </li>
                        <?php endif; ?>
                        <li>
                            <button class="dropdown-item text-danger deletePurchaseReturn"
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
                <span class="text-muted mb-3" style="font-size:.9rem;">No purchase returns found</span>
                <a href="/purchasereturns/create" class="btn btn-primary btn-sm px-4">
                    <i class="bx bx-plus me-1"></i>Create Purchase Return
                </a>
            </div>
        </td>
    </tr>
<?php endif; ?>
