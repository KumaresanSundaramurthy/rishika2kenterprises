<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php
$currency = htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹');
$decimals = $JwtData->GenSettings->DecimalPoints ?? 2;
$showSerial = $JwtData->GenSettings->SerialNoDisplay == 1;

$statusBadge = [
    'Draft'             => ['class' => 'trans-badge-Draft',    'icon' => 'bx-pencil',           'label' => 'Draft'],
    'Active'            => ['class' => 'trans-badge-Active',   'icon' => 'bx-current-location',  'label' => 'Active'],
    'PartiallyReturned' => ['class' => 'trans-badge-Partial',  'icon' => 'bx-git-pull-request',  'label' => 'Partial Return'],
    'Overdue'           => ['class' => 'trans-badge-Overdue',  'icon' => 'bx-alarm-exclamation', 'label' => 'Overdue'],
    'Closed'            => ['class' => 'trans-badge-Paid',     'icon' => 'bx-check-circle',      'label' => 'Closed'],
    'Cancelled'         => ['class' => 'trans-badge-Cancelled','icon' => 'bx-x-circle',          'label' => 'Cancelled'],
];

$payBadge = [
    'Unpaid'        => ['color' => '#dc2626', 'label' => 'Unpaid'],
    'AdvancePaid'   => ['color' => '#d97706', 'label' => 'Advance Paid'],
    'PartiallyPaid' => ['color' => '#d97706', 'label' => 'Partial'],
    'Paid'          => ['color' => '#16a34a', 'label' => 'Paid'],
];

if (!empty($DataLists)):
    foreach ($DataLists as $list):
        $SerialNumber++;
        $status    = $list->RentalStatus ?? 'Active';
        $payStatus = $list->PaymentStatus ?? 'Unpaid';
        $sb        = $statusBadge[$status]    ?? $statusBadge['Active'];
        $pb        = $payBadge[$payStatus]    ?? $payBadge['Unpaid'];
        $canCancel = !in_array($status, ['Closed', 'Cancelled']);
        $canPay    = !in_array($status, ['Cancelled']) && (float)$list->BalanceAmount > 0;
        $canReturn = in_array($status, ['Active', 'PartiallyReturned', 'Overdue']);

        $now      = time();
        $dueTime  = strtotime($list->ReturnDueDateTime ?? '');
        $isOverdue = ($dueTime && $dueTime < $now && !in_array($status, ['Closed', 'Cancelled']));
?>
    <tr>

        <td style="width:36px;">
            <div class="form-check mb-0">
                <input class="form-check-input rntCheck" type="checkbox" value="<?php echo (int)$list->RentalUID; ?>">
            </div>
        </td>

        <td class="<?php echo $showSerial ? '' : 'd-none'; ?> table-serialno" style="width:44px;">
            <span class="text-muted" style="font-size:.78rem;"><?php echo $SerialNumber; ?></span>
        </td>

        <!-- Rental # / Date -->
        <td>
            <div class="trans-doc-number fw-semibold"><?php echo htmlspecialchars($list->RentalNumber ?? '—'); ?></div>
            <div class="text-muted" style="font-size:.72rem;">
                <?php echo $list->RentalStartDateTime ? date('d M Y', strtotime($list->RentalStartDateTime)) : '—'; ?>
            </div>
        </td>

        <!-- Customer -->
        <td>
            <div style="font-weight:500;font-size:.85rem;"><?php echo htmlspecialchars($list->CustomerName ?? '—'); ?></div>
            <?php if (!empty($list->CustomerMobile)): ?>
                <div class="text-muted" style="font-size:.72rem;"><?php echo htmlspecialchars($list->CustomerMobile); ?></div>
            <?php endif; ?>
        </td>

        <!-- Machines -->
        <td>
            <?php if (!empty($list->Machines)): ?>
                <?php foreach ($list->Machines as $m): ?>
                    <div style="font-size:.8rem;line-height:1.5;">
                        <span style="font-weight:500;"><?php echo htmlspecialchars($m->ItemName); ?></span>
                        <span class="text-muted"> × <?php echo (int)$m->Qty; ?></span>
                        <span class="badge ms-1" style="background:#f1f5f9;color:#475569;font-size:.65rem;font-weight:500;"><?php echo htmlspecialchars($m->RentalType); ?></span>
                        <?php if ($m->ItemStatus === 'Returned'): ?>
                            <span class="badge ms-1" style="background:#dcfce7;color:#16a34a;font-size:.65rem;">Returned</span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <span class="text-muted">—</span>
            <?php endif; ?>
        </td>

        <!-- Period -->
        <td style="white-space:nowrap;">
            <div style="font-size:.78rem;">
                <i class="bx bx-calendar me-1 text-muted"></i><?php echo $list->RentalStartDateTime ? date('d M Y', strtotime($list->RentalStartDateTime)) : '—'; ?>
            </div>
            <div style="font-size:.78rem;<?php echo $isOverdue ? 'color:#dc2626;font-weight:600;' : 'color:#64748b;'; ?>">
                <i class="bx bx-calendar-exclamation me-1"></i>Due: <?php echo $list->ReturnDueDateTime ? date('d M Y', strtotime($list->ReturnDueDateTime)) : '—'; ?>
                <?php if ($isOverdue): ?><span class="ms-1">⚠</span><?php endif; ?>
            </div>
        </td>

        <!-- Status -->
        <td>
            <span class="trans-badge <?php echo $sb['class']; ?>">
                <i class="bx <?php echo $sb['icon']; ?>" style="font-size:.8rem;"></i>
                <?php echo $sb['label']; ?>
            </span>
            <div class="mt-1">
                <span style="font-size:.68rem;font-weight:600;color:<?php echo $pb['color']; ?>;">
                    <?php echo $pb['label']; ?>
                </span>
            </div>
        </td>

        <!-- Total -->
        <td style="text-align:right;">
            <div class="trans-amount-main"><?php echo $currency . ' ' . number_format((float)$list->GrandTotal, $decimals, '.', ','); ?></div>
            <?php if ((float)$list->ExtraCharges > 0): ?>
                <div class="text-muted" style="font-size:.7rem;">+<?php echo $currency; ?> <?php echo number_format((float)$list->ExtraCharges, $decimals, '.', ','); ?> extra</div>
            <?php endif; ?>
        </td>

        <!-- Paid -->
        <td style="text-align:right;">
            <div style="font-weight:500;color:#16a34a;font-size:.85rem;"><?php echo $currency . ' ' . number_format((float)$list->TotalPaid, $decimals, '.', ','); ?></div>
            <?php if ((float)$list->DepositCollected > 0): ?>
                <div class="text-muted" style="font-size:.7rem;"><?php echo $currency; ?> <?php echo number_format((float)$list->DepositCollected, $decimals, '.', ','); ?> deposit</div>
            <?php endif; ?>
        </td>

        <!-- Balance -->
        <td style="text-align:right;">
            <?php $bal = (float)$list->BalanceAmount; ?>
            <div style="font-weight:600;color:<?php echo $bal > 0 ? '#dc2626' : '#16a34a'; ?>;font-size:.85rem;">
                <?php echo $currency . ' ' . number_format($bal, $decimals, '.', ','); ?>
            </div>
        </td>

        <!-- Actions -->
        <td style="width:50px;">
            <div class="d-flex align-items-center justify-content-end gap-1">

                <?php if ($canPay): ?>
                <button class="btn btn-icon btn-sm text-success rntRecordPayBtn"
                        data-uid="<?php echo (int)$list->RentalUID; ?>"
                        data-num="<?php echo htmlspecialchars($list->RentalNumber ?? ''); ?>"
                        data-date="<?php echo $list->RentalStartDateTime ? date('d M Y', strtotime($list->RentalStartDateTime)) : ''; ?>"
                        data-customer="<?php echo htmlspecialchars($list->CustomerName ?? ''); ?>"
                        data-total="<?php echo (float)$list->GrandTotal; ?>"
                        data-paid="<?php echo (float)$list->TotalPaid; ?>"
                        data-balance="<?php echo (float)$list->BalanceAmount; ?>"
                        title="Record Payment">
                    <span style="font-size:.9rem;font-weight:700;line-height:1;"><?php echo $currency; ?></span>
                </button>
                <?php endif; ?>

                <div class="dropdown">
                    <button class="trans-actions-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bx bx-dots-vertical-rounded fs-5"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="font-size:.82rem;min-width:190px;">

                        <?php if ($canReturn && !empty($list->Machines)): ?>
                            <?php foreach ($list->Machines as $m): ?>
                                <?php if ($m->ItemStatus !== 'Returned'): ?>
                                <li>
                                    <button class="dropdown-item rntReturnBtn"
                                            data-uid="<?php echo (int)$list->RentalUID; ?>"
                                            data-num="<?php echo htmlspecialchars($list->RentalNumber ?? ''); ?>"
                                            data-item-uid="<?php echo (int)$m->RentalItemUID; ?>"
                                            data-item-name="<?php echo htmlspecialchars($m->ItemName); ?>"
                                            data-item-status="<?php echo htmlspecialchars($m->ItemStatus); ?>">
                                        <i class="bx bx-undo me-2 text-primary"></i>Return: <?php echo htmlspecialchars($m->ItemName); ?>
                                    </button>
                                </li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                            <li><hr class="dropdown-divider my-1"></li>
                        <?php endif; ?>

                        <?php if ($canCancel): ?>
                        <li>
                            <button class="dropdown-item text-danger rntCancelBtn"
                                    data-uid="<?php echo (int)$list->RentalUID; ?>"
                                    data-num="<?php echo htmlspecialchars($list->RentalNumber ?? ''); ?>">
                                <i class="bx bx-x-circle me-2"></i>Cancel Rental
                            </button>
                        </li>
                        <?php endif; ?>

                        <?php if (!$canCancel): ?>
                        <li>
                            <button class="dropdown-item text-muted" disabled>
                                <i class="bx bx-lock-alt me-2"></i><?php echo $status; ?> — No Actions
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
        <td colspan="11">
            <div class="d-flex flex-column align-items-center py-5">
                <img src="/assets/img/elements/no-record-found.png" alt="No Records" class="img-fluid mb-3" style="max-height:150px;object-fit:contain;">
                <span class="text-muted mb-3" style="font-size:.9rem;">No rental records found</span>
                <button type="button" class="btn btn-primary btn-sm px-4" id="rntNewBtnEmpty">
                    <i class="bx bx-plus me-1"></i>New Rental
                </button>
            </div>
        </td>
    </tr>
<?php endif; ?>
