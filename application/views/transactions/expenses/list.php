<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php
$moduleContext = 'expense';
include(APPPATH . 'views/transactions/partials/status_config.php');

$currency   = htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹');
$decimals   = $JwtData->GenSettings->DecimalPoints ?? 2;
$showSerial = $JwtData->GenSettings->SerialNoDisplay == 1;

$terminalExp = ['Paid', 'Cancelled'];

if (!empty($DataLists)):
    foreach ($DataLists as $list):
        $SerialNumber++;
        $status     = $list->DocStatus ?? 'Pending';
        $isTerminal = in_array($status, $terminalExp);
        $badgeClass = $statusBadgeClass[$status] ?? 'trans-badge-Draft';
        $icon       = $statusIcon[$status]        ?? 'bx-circle';
        $paidAmt    = (float)($list->PaidAmount   ?? 0);
        $netAmt     = (float)($list->NetAmount    ?? 0);
        $pendingAmt = max(0, round($netAmt - $paidAmt, 2));
        $showPending = in_array($status, ['Pending', 'Partial']) && $netAmt > 0;
?>
    <tr>

        <td style="width:36px">
            <div class="form-check mb-0">
                <input class="form-check-input table-chkbox expCheck" type="checkbox" value="<?php echo (int)$list->ExpenseUID; ?>">
            </div>
        </td>

        <td class="<?php echo $showSerial ? '' : 'd-none'; ?> table-serialno" style="width:44px">
            <span class="text-muted" style="font-size:.78rem;"><?php echo $SerialNumber; ?></span>
        </td>

        <!-- Expense Number + Date -->
        <td>
            <?php if (empty($list->ExpenseNumber)): ?>
                <span class="trans-doc-draft"><i class="bx bx-pencil me-1" style="font-size:.8rem;"></i>—</span>
            <?php else: ?>
                <a href="javascript:void(0);" class="trans-doc-number fw-semibold expViewDetail"
                   data-uid="<?php echo (int)$list->ExpenseUID; ?>"
                   data-num="<?php echo htmlspecialchars($list->ExpenseNumber); ?>"
                   data-date="<?php echo htmlspecialchars(format_datedisplay($list->ExpenseDate)); ?>"
                   data-amount="<?php echo $currency . ' ' . smartDecimal($list->Amount, $decimals, true); ?>"
                   data-status="<?php echo htmlspecialchars($status); ?>"
                   data-badge="<?php echo htmlspecialchars($badgeClass); ?>"
                   data-icon="<?php echo htmlspecialchars($icon); ?>"
                   data-category="<?php echo htmlspecialchars($list->CategoryName ?? ''); ?>"
                   data-pmt="<?php echo htmlspecialchars($list->PaymentTypeName ?? ''); ?>">
                    <?php echo htmlspecialchars($list->ExpenseNumber); ?>
                </a>
            <?php endif; ?>
            <div class="d-flex align-items-center gap-2 mt-1">
                <?php if (!empty($list->ExpenseDate)): ?>
                    <div class="text-muted" style="font-size:.72rem;"><?php echo htmlspecialchars(format_datedisplay($list->ExpenseDate)); ?></div>
                <?php endif; ?>
                <?php if (!empty($list->AttachCount) && (int)$list->AttachCount > 0): ?>
                    <button type="button" class="btn btn-link p-0 transAttachBtn"
                            data-uid="<?php echo (int)$list->ExpenseUID; ?>"
                            data-num="<?php echo htmlspecialchars($list->ExpenseNumber ?? ''); ?>"
                            data-url="/transactions/getAttachments"
                            data-module-uid="114"
                            data-color="#d97706"
                            title="<?php echo (int)$list->AttachCount; ?> attachment(s)"
                            style="font-size:.82rem;line-height:1;color:#d97706;">
                        <i class="bx bx-paperclip"></i>
                    </button>
                <?php endif; ?>
            </div>
            <?php $createdBy = trim($list->CreatedByName ?? ''); if ($createdBy !== ''): ?>
                <div style="font-size:.68rem;color:#bbb;">by <?php echo htmlspecialchars($createdBy); ?></div>
            <?php endif; ?>
        </td>

        <!-- Amount -->
        <td>
            <div class="trans-amount-main"><?php echo $currency . ' ' . smartDecimal($list->Amount, $decimals, true); ?></div>
            <?php if ($status === 'Partial' && $pendingAmt > 0): ?>
                <div style="font-size:.7rem;color:#dc3545;margin-top:2px;">Bal <?php echo $currency . ' ' . number_format($pendingAmt, $decimals); ?></div>
            <?php endif; ?>
        </td>

        <!-- Category / Notes -->
        <td>
            <?php if (!empty($list->CategoryName)): ?>
                <span class="badge text-bg-light border" style="font-size:.72rem;font-weight:500;">
                    <?php echo htmlspecialchars($list->CategoryName); ?>
                </span>
            <?php else: ?>
                <span class="text-muted">—</span>
            <?php endif; ?>
            <?php if (!empty($list->Notes)): ?>
                <div class="text-muted" style="font-size:.72rem;margin-top:3px;"><?php echo htmlspecialchars($list->Notes); ?></div>
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
            <?php
                $payCount      = (int)($list->PaymentCount ?? 0);
                $payModes      = $payCount > 0 ? explode(',', $list->PaymentModes ?? '') : [];
                $firstMode     = isset($payModes[0]) ? htmlspecialchars(trim($payModes[0])) : '';
                $extraCnt      = max(0, $payCount - 1);
                $payBankName   = htmlspecialchars(trim($list->PayBankName      ?? ''));
                $payAccNum     = htmlspecialchars(trim($list->PayAccountNumber ?? ''));
                $isCashOnly    = $payCount > 0 && empty($payBankName);
                $hasPayAttach  = !empty($list->PaymentAttachmentCount) && (int)$list->PaymentAttachmentCount > 0;
            ?>
            <?php if ($payCount > 0 && $firstMode): ?>
                <div class="pay-mode-cell<?php echo $payCount > 1 ? ' pay-mode-clickable' : ''; ?>"
                     <?php if ($payCount > 1): ?>
                     data-trans-uid="<?php echo (int)$list->ExpenseUID; ?>"
                     data-trans-num="<?php echo htmlspecialchars($list->ExpenseNumber ?? ''); ?>"
                     data-fetch-url="/expenses/getPaymentHistory"
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
                                data-uid="<?php echo (int)$list->ExpenseUID; ?>"
                                data-num="<?php echo htmlspecialchars($list->ExpenseNumber ?? ''); ?>"
                                data-url="/expenses/getPaymentAttachments"
                                title="<?php echo (int)$list->PaymentAttachmentCount; ?> payment attachment(s)"
                                style="font-size:.82rem;line-height:1;color:#d97706;">
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

                <?php if ($showPending): ?>
                <button class="btn exp-pay-quick-btn expMarkPaid"
                        data-uid="<?php echo (int)$list->ExpenseUID; ?>"
                        data-num="<?php echo htmlspecialchars($list->ExpenseNumber ?? ''); ?>"
                        data-date="<?php echo htmlspecialchars(format_datedisplay($list->ExpenseDate)); ?>"
                        data-total="<?php echo htmlspecialchars($netAmt); ?>"
                        data-paid="<?php echo htmlspecialchars($paidAmt); ?>"
                        data-pending="<?php echo htmlspecialchars($pendingAmt); ?>"
                        title="Record Payment"><?php echo $currency; ?></button>
                <?php endif; ?>

                <?php if ($status !== 'Cancelled'): ?>
                <button class="btn btn-icon btn-sm text-warning expEdit"
                        data-uid="<?php echo (int)$list->ExpenseUID; ?>"
                        title="Edit">
                    <i class="bx bx-edit"></i>
                </button>
                <?php endif; ?>

                <div class="dropdown">
                    <button class="trans-actions-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bx bx-dots-vertical-rounded fs-5"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="font-size:.82rem;min-width:180px;">

                        <?php if ($showPending): ?>
                        <li>
                            <button class="dropdown-item expMarkPaid"
                                    data-uid="<?php echo (int)$list->ExpenseUID; ?>"
                                    data-num="<?php echo htmlspecialchars($list->ExpenseNumber ?? ''); ?>"
                                    data-date="<?php echo htmlspecialchars(format_datedisplay($list->ExpenseDate)); ?>"
                                    data-total="<?php echo htmlspecialchars($netAmt); ?>"
                                    data-paid="<?php echo htmlspecialchars($paidAmt); ?>"
                                    data-pending="<?php echo htmlspecialchars($pendingAmt); ?>">
                                <i class="bx bx-wallet me-2 text-success"></i>Record Payment
                            </button>
                        </li>
                        <li>
                            <button class="dropdown-item expCancel"
                                    data-uid="<?php echo (int)$list->ExpenseUID; ?>"
                                    data-num="<?php echo htmlspecialchars($list->ExpenseNumber ?? ''); ?>">
                                <i class="bx bx-x-circle me-2 text-warning"></i>Cancel Expense
                            </button>
                        </li>
                        <li><hr class="dropdown-divider my-1"></li>
                        <li>
                            <button class="dropdown-item text-danger expDelete"
                                    data-uid="<?php echo (int)$list->ExpenseUID; ?>"
                                    data-num="<?php echo htmlspecialchars($list->ExpenseNumber ?? 'this expense'); ?>">
                                <i class="bx bx-trash me-2"></i>Delete
                            </button>
                        </li>
                        <?php endif; ?>

                        <?php if ($status === 'Paid'): ?>
                        <li>
                            <button class="dropdown-item expClone"
                                    data-uid="<?php echo (int)$list->ExpenseUID; ?>"
                                    data-num="<?php echo htmlspecialchars($list->ExpenseNumber ?? ''); ?>">
                                <i class="bx bx-git-branch me-2 text-info"></i>Clone
                            </button>
                        </li>
                        <li><hr class="dropdown-divider my-1"></li>
                        <li>
                            <button class="dropdown-item text-warning expMarkCancelled"
                                    data-uid="<?php echo (int)$list->ExpenseUID; ?>"
                                    data-num="<?php echo htmlspecialchars($list->ExpenseNumber ?? ''); ?>">
                                <i class="bx bx-x-circle me-2"></i>Mark as Cancelled
                            </button>
                        </li>
                        <li>
                            <button class="dropdown-item text-danger expDelete"
                                    data-uid="<?php echo (int)$list->ExpenseUID; ?>"
                                    data-num="<?php echo htmlspecialchars($list->ExpenseNumber ?? 'this expense'); ?>">
                                <i class="bx bx-trash me-2"></i>Delete
                            </button>
                        </li>
                        <?php endif; ?>

                        <?php if ($status === 'Cancelled'): ?>
                        <li>
                            <button class="dropdown-item text-muted" disabled>
                                <i class="bx bx-lock-alt me-2"></i>Cancelled — No Actions
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
                <span class="text-muted mb-3" style="font-size:.9rem;">No expenses found</span>
                <button type="button" class="btn btn-primary btn-sm px-4 addExpenseBtn">
                    <i class="bx bx-plus me-1"></i>Add Expense
                </button>
            </div>
        </td>
    </tr>
<?php endif; ?>
