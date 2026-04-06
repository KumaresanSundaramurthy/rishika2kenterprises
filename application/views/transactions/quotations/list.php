<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php
$statusBadge = [
    'Pending'   => 'bg-label-warning',
    'Accepted'  => 'bg-label-success',
    'Partial'   => 'bg-label-info',
    'Rejected'  => 'bg-label-danger',
    'Converted' => 'bg-label-primary',
    'Draft'     => 'bg-label-secondary',
];
$statusLabel = [
    'Pending'  => 'Open',
    'Accepted' => 'Closed',
    'Rejected' => 'Cancelled',
];
// Valid next-state transitions per current DocStatus
$statusTransitions = [
    'Pending'   => [
        ['db' => 'Accepted', 'label' => 'Mark as Closed'],
        ['db' => 'Partial',  'label' => 'Mark as Partial'],
        ['db' => 'Rejected', 'label' => 'Cancel'],
    ],
    'Accepted'  => [
        ['db' => 'Pending',  'label' => 'Reopen'],
        ['db' => 'Partial',  'label' => 'Mark as Partial'],
        ['db' => 'Rejected', 'label' => 'Cancel'],
    ],
    'Partial'   => [
        ['db' => 'Accepted', 'label' => 'Mark as Closed'],
        ['db' => 'Pending',  'label' => 'Mark as Open'],
        ['db' => 'Rejected', 'label' => 'Cancel'],
    ],
    'Draft'     => [
        ['db' => 'Pending', 'label' => 'Finalize (Mark Open)'],
    ],
    'Rejected'  => [],
    'Converted' => [],
];

$currency   = htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '');
$decimals   = $JwtData->GenSettings->DecimalPoints ?? 2;
$showSerial = $JwtData->GenSettings->SerialNoDisplay == 1;

if (!empty($DataLists)) {
    foreach ($DataLists as $list) {
        $SerialNumber++;
        $isDraft     = ($list->Status === 'Draft');
        $isClosed    = ($list->Status === 'Accepted');
        $isCancelled = ($list->Status === 'Rejected');
        $badge       = $statusBadge[$list->Status]  ?? 'bg-label-secondary';
        $label       = $statusLabel[$list->Status]   ?? $list->Status;
        $transitions = $statusTransitions[$list->Status] ?? [];

        $validityClass = '';
        if (!$isDraft && !empty($list->ValidityDate) && strtotime($list->ValidityDate) < time()) {
            $validityClass = 'text-danger';
        }
?>
        <tr>
            <td style="width:40px">
                <div class="form-check">
                    <input class="form-check-input table-chkbox quotationCheck" type="checkbox" value="<?php echo (int) $list->TransUID; ?>">
                </div>
            </td>
            <td class="<?php echo $showSerial ? '' : 'd-none'; ?> table-serialno" style="width:50px"><?php echo $SerialNumber; ?></td>

            <!-- # Quotation -->
            <td>
                <?php if ($isDraft || empty($list->UniqueNumber)): ?>
                    <span class="text-muted fst-italic small">—</span>
                <?php else: ?>
                    <span class="fw-semibold text-primary"><a href="javascript: void(0)" class="text-decoration-underline viewQuotation" data-uid="<?php echo (int) $list->TransUID; ?>"><?php echo htmlspecialchars($list->UniqueNumber); ?></a></span>
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

            <!-- Status — clickable badge with transition dropdown -->
            <td>
                <?php if (!empty($transitions)): ?>
                <div class="dropdown">
                    <span class="badge <?php echo $badge; ?> cursor-pointer"
                          data-bs-toggle="dropdown"
                          data-uid="<?php echo (int) $list->TransUID; ?>"
                          data-current="<?php echo htmlspecialchars($list->Status); ?>"
                          title="Click to change status">
                        <?php echo htmlspecialchars($label); ?> <i class="bx bx-chevron-down" style="font-size:.7rem;vertical-align:middle"></i>
                    </span>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                        <?php foreach ($transitions as $t): ?>
                        <li>
                            <button class="dropdown-item quot-status-update"
                                    data-uid="<?php echo (int) $list->TransUID; ?>"
                                    data-status="<?php echo htmlspecialchars($t['db']); ?>">
                                <?php echo htmlspecialchars($t['label']); ?>
                            </button>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php else: ?>
                    <span class="badge <?php echo $badge; ?>"><?php echo htmlspecialchars($label); ?></span>
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
            <td>
                <?php echo htmlspecialchars(format_datedisplay($list->TransDate, 'd M Y')); ?>
            </td>

            <!-- Valid Until -->
            <td class="<?php echo $validityClass; ?>">
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

                    <!-- View: always visible -->
                    <!-- <button class="btn btn-icon btn-sm text-info viewQuotation" data-uid="<?php echo (int) $list->TransUID; ?>" title="View">
                        <i class="bx bx-show fs-6"></i>
                    </button> -->

                    <!-- Edit: hidden for Closed and Cancelled -->
                    <?php if (!$isClosed && !$isCancelled): ?>
                    <a class="btn btn-icon btn-sm text-warning" href="/quotations/edit/<?php echo (int) $list->TransUID; ?>" title="Edit">
                        <i class="bx bx-edit fs-6"></i>
                    </a>
                    <?php endif; ?>

                    <!-- 3-dot menu -->
                    <div class="dropdown">
                        <button class="btn btn-icon btn-sm" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bx bx-dots-vertical-rounded fs-5 text-muted"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">

                            <?php if ($isCancelled): ?>
                            <!-- Cancelled: View (icon above) + Duplicate only -->
                            <li>
                                <button class="dropdown-item duplicateQuotation"
                                        data-uid="<?php echo (int) $list->TransUID; ?>">
                                    <i class="bx bx-copy me-2 text-secondary"></i>Duplicate
                                </button>
                            </li>

                            <?php elseif ($isClosed): ?>
                            <!-- Closed: View (icon above) + Thermal Print + A4 Print + Duplicate only -->
                            <li>
                                <button class="dropdown-item thermalPrintQuotation"
                                        data-uid="<?php echo (int) $list->TransUID; ?>">
                                    <i class="bx bx-receipt me-2 text-dark"></i>Thermal Print
                                </button>
                            </li>
                            <li>
                                <button class="dropdown-item a4PrintQuotation"
                                        data-uid="<?php echo (int) $list->TransUID; ?>">
                                    <i class="bx bx-file me-2 text-primary"></i>Print (A4 / A5)
                                </button>
                            </li>
                            <li><hr class="dropdown-divider my-1"></li>
                            <li>
                                <button class="dropdown-item duplicateQuotation"
                                        data-uid="<?php echo (int) $list->TransUID; ?>">
                                    <i class="bx bx-copy me-2 text-secondary"></i>Duplicate
                                </button>
                            </li>

                            <?php else: ?>
                            <!-- All other statuses: full menu -->
                            <?php if (!$isDraft && $list->Status !== 'Converted'): ?>
                            <li>
                                <button class="dropdown-item convertToQuot"
                                        data-uid="<?php echo (int) $list->TransUID; ?>"
                                        data-num="<?php echo htmlspecialchars($list->UniqueNumber ?? ''); ?>"
                                        data-target="SalesOrder">
                                    <i class="bx bx-cart me-2 text-primary"></i>Convert to Sales Order
                                </button>
                            </li>
                            <li>
                                <button class="dropdown-item convertToQuot"
                                        data-uid="<?php echo (int) $list->TransUID; ?>"
                                        data-num="<?php echo htmlspecialchars($list->UniqueNumber ?? ''); ?>"
                                        data-target="Invoice">
                                    <i class="bx bx-receipt me-2 text-success"></i>Convert to Invoice
                                </button>
                            </li>
                            <li><hr class="dropdown-divider my-1"></li>
                            <?php endif; ?>
                            <?php if (!$isDraft): ?>
                            <li>
                                <button class="dropdown-item thermalPrintQuotation"
                                        data-uid="<?php echo (int) $list->TransUID; ?>">
                                    <i class="bx bx-receipt me-2 text-dark"></i>Thermal Print
                                </button>
                            </li>
                            <li>
                                <button class="dropdown-item a4PrintQuotation"
                                        data-uid="<?php echo (int) $list->TransUID; ?>">
                                    <i class="bx bx-file me-2 text-primary"></i>Print (A4 / A5)
                                </button>
                            </li>
                            <li><hr class="dropdown-divider my-1"></li>
                            <?php endif; ?>
                            <li>
                                <button class="dropdown-item duplicateQuotation"
                                        data-uid="<?php echo (int) $list->TransUID; ?>">
                                    <i class="bx bx-copy me-2 text-secondary"></i>Duplicate
                                </button>
                            </li>
                            <li>
                                <button class="dropdown-item text-danger deleteQuotation"
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
                <span class="text-muted mb-3">No quotations found</span>
                <a href="/quotations/create" class="btn btn-primary btn-sm px-3">
                    <i class="bx bx-plus me-1"></i>Create Quotation
                </a>
            </div>
        </td>
    </tr>
<?php } ?>
