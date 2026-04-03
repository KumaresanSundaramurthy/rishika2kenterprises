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
$currency   = htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '');
$decimals   = $JwtData->GenSettings->DecimalPoints ?? 2;
$showSerial = $JwtData->GenSettings->SerialNoDisplay == 1;

if (!empty($DataLists)) {
    foreach ($DataLists as $list) {
        $SerialNumber++;
        $isDraft = ($list->Status === 'Draft');
        $badge   = $statusBadge[$list->Status] ?? 'bg-label-secondary';
        $label   = $statusLabel[$list->Status] ?? $list->Status;

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
            <td style="width:140px">
                <?php if ($isDraft || empty($list->UniqueNumber)): ?>
                    <span class="text-muted fst-italic small">—</span>
                <?php else: ?>
                    <span class="fw-semibold text-primary"><?php echo htmlspecialchars($list->UniqueNumber); ?></span>
                <?php endif; ?>
            </td>

            <!-- Amount -->
            <td class="text-end fw-bold" style="width:120px">
                <?php if ($isDraft && $list->NetAmount == 0): ?>
                    <span class="text-muted">—</span>
                <?php else: ?>
                    <?php echo $currency; ?><?php echo smartDecimal($list->NetAmount, $decimals, true); ?>
                <?php endif; ?>
            </td>

            <!-- Status -->
            <td style="width:100px">
                <span class="badge <?php echo $badge; ?>"><?php echo htmlspecialchars($label); ?></span>
            </td>

            <!-- Customer + Mobile -->
            <td>
                <div class="fw-semibold lh-sm"><?php echo htmlspecialchars($list->PartyName ?? '—'); ?></div>
                <?php if (!empty($list->MobileNumber)): ?>
                    <div class="small text-muted"><?php echo htmlspecialchars($list->MobileNumber); ?></div>
                <?php endif; ?>
            </td>

            <!-- Date -->
            <td class="small" style="width:100px">
                <?php echo htmlspecialchars(format_datedisplay($list->TransDate, 'd M Y')); ?>
            </td>

            <!-- Valid Until -->
            <td class="small <?php echo $validityClass; ?>" style="width:100px">
                <?php if (!$isDraft && !empty($list->ValidityDate)): ?>
                    <?php echo format_datedisplay($list->ValidityDate, 'd M Y'); ?>
                <?php else: ?>
                    <span class="text-muted">—</span>
                <?php endif; ?>
            </td>

            <!-- Last Updated -->
            <td class="small text-muted" style="width:130px">
                <?php echo !empty($list->UpdatedOn) ? htmlspecialchars(format_datedisplay($list->UpdatedOn, 'd M Y')) : '—'; ?>
            </td>

            <!-- Actions (⋮ menu) -->
            <td class="text-center" style="width:50px">
                <div class="dropdown">
                    <button class="btn btn-icon btn-sm" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bx bx-dots-vertical-rounded fs-5 text-muted"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                        <li>
                            <a class="dropdown-item" href="/quotations/view/<?php echo (int) $list->TransUID; ?>">
                                <i class="bx bx-show me-2 text-info"></i>View
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="/quotations/edit/<?php echo (int) $list->TransUID; ?>">
                                <i class="bx bx-edit-alt me-2 text-warning"></i>Edit
                            </a>
                        </li>
                        <?php if (!$isDraft && $list->Status !== 'Converted'): ?>
                        <li><hr class="dropdown-divider my-1"></li>
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
                        <?php endif; ?>
                        <li><hr class="dropdown-divider my-1"></li>
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
                    </ul>
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
