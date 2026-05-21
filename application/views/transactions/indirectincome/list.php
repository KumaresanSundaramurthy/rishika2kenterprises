<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php
$moduleContext = 'indirectincome';
include(APPPATH . 'views/transactions/partials/status_config.php');

$currency   = htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹');
$decimals   = $JwtData->GenSettings->DecimalPoints ?? 2;
$showSerial = $JwtData->GenSettings->SerialNoDisplay == 1;

$terminalInc = ['Received', 'Cancelled'];

if (!empty($DataLists)):
    foreach ($DataLists as $list):
        $SerialNumber++;
        $status     = $list->DocStatus ?? 'Pending';
        $isTerminal = in_array($status, $terminalInc);
        $badgeClass = $statusBadgeClass[$status] ?? 'trans-badge-Draft';
        $icon       = $statusIcon[$status]        ?? 'bx-circle';
?>
    <tr>

        <td style="width:36px">
            <div class="form-check mb-0">
                <input class="form-check-input table-chkbox incCheck" type="checkbox" value="<?php echo (int)$list->IncomeUID; ?>">
            </div>
        </td>

        <td class="<?php echo $showSerial ? '' : 'd-none'; ?> table-serialno" style="width:44px">
            <span class="text-muted" style="font-size:.78rem;"><?php echo $SerialNumber; ?></span>
        </td>

        <!-- Income Number + Date -->
        <td>
            <?php if (empty($list->IncomeNumber)): ?>
                <span class="trans-doc-draft"><i class="bx bx-pencil me-1" style="font-size:.8rem;"></i>—</span>
            <?php else: ?>
                <span class="trans-doc-number fw-semibold"><?php echo htmlspecialchars($list->IncomeNumber); ?></span>
            <?php endif; ?>
            <?php if (!empty($list->IncomeDate)): ?>
                <div class="text-muted" style="font-size:.72rem;"><?php echo htmlspecialchars(format_datedisplay($list->IncomeDate, 'd M Y')); ?></div>
            <?php endif; ?>
        </td>

        <!-- Amount -->
        <td>
            <div class="trans-amount-main"><?php echo $currency . ' ' . smartDecimal($list->Amount, $decimals, true); ?></div>
        </td>

        <!-- Category -->
        <td>
            <?php if (!empty($list->CategoryName)): ?>
                <span class="badge text-bg-light border" style="font-size:.72rem;font-weight:500;">
                    <?php echo htmlspecialchars($list->CategoryName); ?>
                </span>
            <?php else: ?>
                <span class="text-muted">—</span>
            <?php endif; ?>
        </td>

        <!-- Status -->
        <td>
            <span class="trans-badge <?php echo $badgeClass; ?>">
                <i class="bx <?php echo $icon; ?>" style="font-size:.8rem;"></i>
                <?php echo htmlspecialchars($status); ?>
            </span>
        </td>

        <!-- Payment Mode -->
        <td>
            <?php if (!empty($list->PaymentTypeName)): ?>
                <span style="font-size:.82rem;"><?php echo htmlspecialchars($list->PaymentTypeName); ?></span>
                <?php if (!empty($list->BankAccountName)): ?>
                    <div class="text-muted" style="font-size:.7rem;"><?php echo htmlspecialchars($list->BankAccountName); ?></div>
                <?php endif; ?>
            <?php else: ?>
                <span class="text-muted">—</span>
            <?php endif; ?>
        </td>

        <!-- Date -->
        <td>
            <?php if (!empty($list->IncomeDate)): ?>
                <?php echo htmlspecialchars(format_datedisplay($list->IncomeDate, 'd M Y')); ?>
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
                $agoText    = '';
                if ($within24h) {
                    if ($secondsAgo < 60)       $agoText = 'just now';
                    elseif ($secondsAgo < 3600) $agoText = (int)($secondsAgo / 60) . ' min' . ((int)($secondsAgo / 60) > 1 ? 's' : '') . ' ago';
                    else                        $agoText = (int)($secondsAgo / 3600) . ' hr' . ((int)($secondsAgo / 3600) > 1 ? 's' : '') . ' ago';
                }
            ?>
            <div style="font-size:.78rem;"><?php echo $updatedOn ? changeTimeZonefromDateTime($updatedOn, $JwtData->User->Timezone, 2) : '—'; ?></div>
            <?php if ($within24h): ?>
                <div style="font-size:.68rem;color:#0d6efd;font-weight:500;"><?php echo $agoText; ?></div>
            <?php endif; ?>
            <div class="text-muted" style="font-size:.7rem;">by <?php echo htmlspecialchars(trim($list->UpdatedBy ?? '—')); ?></div>
        </td>

        <!-- Actions -->
        <td style="width:50px">
            <div class="d-flex align-items-center justify-content-end gap-1">

                <?php if (!$isTerminal): ?>
                <button class="btn btn-icon btn-sm text-warning incEdit"
                        data-uid="<?php echo (int)$list->IncomeUID; ?>"
                        title="Edit">
                    <i class="bx bx-edit"></i>
                </button>
                <?php endif; ?>

                <div class="dropdown">
                    <button class="trans-actions-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bx bx-dots-vertical-rounded fs-5"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="font-size:.82rem;min-width:185px;">

                        <?php if ($status === 'Pending'): ?>
                        <li>
                            <button class="dropdown-item incMarkReceived"
                                    data-uid="<?php echo (int)$list->IncomeUID; ?>"
                                    data-num="<?php echo htmlspecialchars($list->IncomeNumber ?? ''); ?>">
                                <i class="bx bx-check-circle me-2 text-success"></i>Mark as Received
                            </button>
                        </li>
                        <li>
                            <button class="dropdown-item incCancel"
                                    data-uid="<?php echo (int)$list->IncomeUID; ?>"
                                    data-num="<?php echo htmlspecialchars($list->IncomeNumber ?? ''); ?>">
                                <i class="bx bx-x-circle me-2 text-warning"></i>Cancel Income
                            </button>
                        </li>
                        <li><hr class="dropdown-divider my-1"></li>
                        <?php endif; ?>

                        <?php if (!$isTerminal): ?>
                        <li>
                            <button class="dropdown-item text-danger incDelete"
                                    data-uid="<?php echo (int)$list->IncomeUID; ?>"
                                    data-num="<?php echo htmlspecialchars($list->IncomeNumber ?? 'this income'); ?>">
                                <i class="bx bx-trash me-2"></i>Delete
                            </button>
                        </li>
                        <?php endif; ?>

                        <?php if ($isTerminal && $status === 'Received'): ?>
                        <li>
                            <button class="dropdown-item text-muted" disabled>
                                <i class="bx bx-lock-alt me-2"></i>Received — No Actions
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
                <span class="text-muted mb-3" style="font-size:.9rem;">No income records found</span>
                <button type="button" class="btn btn-primary btn-sm px-4" id="addIncomeBtn">
                    <i class="bx bx-plus me-1"></i>Add Income
                </button>
            </div>
        </td>
    </tr>
<?php endif; ?>
