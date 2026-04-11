<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php
$statusBadge = [
    'Draft'      => 'bg-label-secondary',
    'Confirmed'  => 'bg-label-primary',
    'Processing' => 'bg-label-warning',
    'Dispatched' => 'bg-label-info',
    'Delivered'  => 'bg-label-success',
    'Cancelled'  => 'bg-label-danger',
    'Converted'  => 'bg-label-dark',
];

// Valid next-state transitions per current DocStatus
$statusTransitions = [
    'Draft'      => [
        ['db' => 'Confirmed',  'label' => 'Confirm Order'],
    ],
    'Confirmed'  => [
        ['db' => 'Processing', 'label' => 'Mark as Processing'],
        ['db' => 'Cancelled',  'label' => 'Cancel'],
    ],
    'Processing' => [
        ['db' => 'Dispatched', 'label' => 'Mark as Dispatched'],
        ['db' => 'Cancelled',  'label' => 'Cancel'],
    ],
    'Dispatched' => [
        ['db' => 'Delivered',  'label' => 'Mark as Delivered'],
        ['db' => 'Cancelled',  'label' => 'Cancel'],
    ],
    'Delivered'  => [],
    'Cancelled'  => [],
    'Converted'  => [],
];

$currency   = htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '');
$decimals   = $JwtData->GenSettings->DecimalPoints ?? 2;
$showSerial = $JwtData->GenSettings->SerialNoDisplay == 1;

if (!empty($DataLists)) {
    foreach ($DataLists as $list) {
        $SerialNumber++;
        $isDraft     = ($list->Status === 'Draft');
        $isDelivered = ($list->Status === 'Delivered');
        $isCancelled = ($list->Status === 'Cancelled');
        $isConverted = ($list->Status === 'Converted');
        $isTerminal  = $isDelivered || $isCancelled || $isConverted;
        $badge       = $statusBadge[$list->Status]  ?? 'bg-label-secondary';
        $transitions = $statusTransitions[$list->Status] ?? [];

        $deliveryClass = '';
        if (!$isDraft && !empty($list->ValidityDate) && strtotime($list->ValidityDate) < time() && !$isTerminal) {
            $deliveryClass = 'text-danger';
        }
?>
        <tr>
            <td style="width:40px">
                <div class="form-check">
                    <input class="form-check-input table-chkbox soCheck" type="checkbox" value="<?php echo (int) $list->TransUID; ?>">
                </div>
            </td>
            <td class="<?php echo $showSerial ? '' : 'd-none'; ?> table-serialno" style="width:50px"><?php echo $SerialNumber; ?></td>

            <!-- # Sales Order -->
            <td>
                <?php if ($isDraft || empty($list->UniqueNumber)): ?>
                    <span class="text-muted fst-italic small">—</span>
                <?php else: ?>
                    <span class="fw-semibold text-primary">
                        <a href="javascript:void(0)" class="text-decoration-underline viewSalesOrder" data-uid="<?php echo (int) $list->TransUID; ?>">
                            <?php echo htmlspecialchars($list->UniqueNumber); ?>
                        </a>
                    </span>
                <?php endif; ?>
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
                            <button class="dropdown-item so-status-update"
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

            <!-- Customer + Mobile -->
            <td>
                <div class="fw-semibold lh-sm mb-1"><?php echo htmlspecialchars($list->PartyName ?? '—'); ?></div>
                <?php if (!empty($list->MobileNumber)): ?>
                    <div class="small text-muted">
                        <?php echo htmlspecialchars($list->MobileNumber); ?>
                        <a href="https://wa.me/<?php echo htmlspecialchars($list->MobileNumber); ?>?text=Hi"
                           target="_blank" class="text-success ms-1" title="WhatsApp">
                            <i class="bx bxl-whatsapp fs-6 align-middle"></i>
                        </a>
                    </div>
                <?php endif; ?>
            </td>

            <!-- Date -->
            <td><?php echo htmlspecialchars(format_datedisplay($list->TransDate, 'd M Y')); ?></td>

            <!-- Expected Delivery -->
            <td class="<?php echo $deliveryClass; ?>">
                <?php if (!$isDraft && !empty($list->ValidityDate)): ?>
                    <?php echo format_datedisplay($list->ValidityDate, 'd M Y'); ?>
                <?php else: ?>
                    <span class="text-muted">—</span>
                <?php endif; ?>
            </td>

            <!-- Last Updated -->
            <td class="small text-muted">
                <div><?php echo changeTimeZonefromDateTime($list->UpdatedOn, $JwtData->User->Timezone, 2); ?></div>
                <div style="font-size:.75rem">by <?php echo htmlspecialchars($list->UpdatedBy ?? '—'); ?></div>
            </td>

            <!-- Actions -->
            <td style="width:50px">
                <div class="d-flex align-items-center justify-content-center gap-1">

                    <!-- Edit: hidden for terminal statuses -->
                    <?php if (!$isTerminal): ?>
                    <a class="btn btn-icon btn-sm text-warning" href="/salesorders/edit/<?php echo (int) $list->TransUID; ?>" title="Edit">
                        <i class="bx bx-edit fs-6"></i>
                    </a>
                    <?php endif; ?>

                    <!-- 3-dot menu -->
                    <div class="dropdown">
                        <button class="btn btn-icon btn-sm" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bx bx-dots-vertical-rounded fs-5 text-muted"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">

                            <?php if (!$isDraft && !$isConverted): ?>
                            <li>
                                <button class="dropdown-item a4PrintSalesOrder"
                                        data-uid="<?php echo (int) $list->TransUID; ?>">
                                    <i class="bx bx-file me-2 text-primary"></i>Print (A4 / A5)
                                </button>
                            </li>
                            <?php if (!$isDelivered && !$isCancelled): ?>
                            <li>
                                <button class="dropdown-item convertSOToInvoice"
                                        data-uid="<?php echo (int) $list->TransUID; ?>"
                                        data-num="<?php echo htmlspecialchars($list->UniqueNumber ?? ''); ?>">
                                    <i class="bx bx-receipt me-2 text-success"></i>Convert to Invoice
                                </button>
                            </li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider my-1"></li>
                            <?php endif; ?>

                            <li>
                                <button class="dropdown-item duplicateSalesOrder"
                                        data-uid="<?php echo (int) $list->TransUID; ?>">
                                    <i class="bx bx-copy me-2 text-secondary"></i>Duplicate
                                </button>
                            </li>

                            <?php if (!$isTerminal): ?>
                            <li>
                                <button class="dropdown-item text-danger deleteSalesOrder"
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
        <td colspan="9">
            <div class="d-flex flex-column align-items-center py-5">
                <img src="/assets/img/elements/no-record-found.png" alt="No Records" class="img-fluid mb-3" style="max-height:160px;object-fit:contain">
                <span class="text-muted mb-3">No sales orders found</span>
                <a href="/salesorders/create" class="btn btn-primary btn-sm px-3">
                    <i class="bx bx-plus me-1"></i>Create Sales Order
                </a>
            </div>
        </td>
    </tr>
<?php } ?>
