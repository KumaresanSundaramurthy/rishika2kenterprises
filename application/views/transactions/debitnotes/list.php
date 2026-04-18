<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php
$moduleContext = 'debitnote';
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
                <input class="form-check-input table-chkbox dnCheck" type="checkbox" value="<?php echo (int)$list->TransUID; ?>">
            </div>
        </td>

        <td class="<?php echo $showSerial ? '' : 'd-none'; ?> table-serialno" style="width:44px">
            <span class="text-muted" style="font-size:.78rem;"><?php echo $SerialNumber; ?></span>
        </td>

        <!-- Debit Note Number -->
        <td>
            <?php if ($isDraft || empty($list->UniqueNumber)): ?>
                <span class="trans-doc-draft"><i class="bx bx-pencil me-1" style="font-size:.8rem;"></i>Draft</span>
                <?php if (!empty($list->TransDate)): ?>
                    <div class="text-muted" style="font-size:.72rem;"><?php echo htmlspecialchars(format_datedisplay($list->TransDate, 'd M Y')); ?></div>
                <?php endif; ?>
            <?php else: ?>
                <a href="javascript:void(0)" class="trans-doc-number viewDebitNote" data-uid="<?php echo (int)$list->TransUID; ?>">
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
                        <button class="dropdown-item dn-status-update"
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
            <div class="trans-party-name"><?php echo htmlspecialchars($list->PartyName ?? '—'); ?></div>
            <?php if (!empty($list->MobileNumber)): ?>
            <div class="trans-party-mobile"><?php echo htmlspecialchars($list->MobileNumber); ?></div>
            <?php endif; ?>
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
            <div class="text-muted" style="font-size:.78rem;">
                <?php echo changeTimeZonefromDateTime($list->UpdatedOn, $JwtData->User->Timezone, 2); ?>
            </div>
            <div style="font-size:.71rem; color:#bbb;">by <?php echo htmlspecialchars($list->UpdatedBy ?? '—'); ?></div>
        </td>

        <!-- Actions -->
        <td style="width:50px">
            <div class="d-flex align-items-center justify-content-center gap-1">

                <?php if (!$isTerminal): ?>
                <a class="btn btn-icon btn-sm text-warning" href="/debitnotes/edit/<?php echo (int)$list->TransUID; ?>" title="Edit">
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
                            <button class="dropdown-item duplicateDebitNote" data-uid="<?php echo (int)$list->TransUID; ?>">
                                <i class="bx bx-copy me-2 text-secondary"></i>Duplicate
                            </button>
                        </li>

                        <?php if (!$isTerminal): ?>
                        <li><hr class="dropdown-divider my-1"></li>
                        <li>
                            <button class="dropdown-item text-danger deleteDebitNote"
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
                <span class="text-muted mb-3" style="font-size:.9rem;">No debit notes found</span>
                <a href="/debitnotes/create" class="btn btn-primary btn-sm px-4">
                    <i class="bx bx-plus me-1"></i>Create Debit Note
                </a>
            </div>
        </td>
    </tr>
<?php endif; ?>
