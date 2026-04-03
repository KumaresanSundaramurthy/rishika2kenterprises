<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php if (!empty($dataLists)) {
    foreach ($dataLists as $list) {
        $SerialNumber++;

        $statusClass = [
            'Draft'     => 'bg-label-secondary',
            'Pending'   => 'bg-label-warning',
            'Accepted'  => 'bg-label-success',
            'Rejected'  => 'bg-label-danger',
            'Converted' => 'bg-label-info',
        ][$list->Status] ?? 'bg-label-secondary';
?>
        <tr>
            <td>
                <div class="form-check form-check-inline">
                    <input class="form-check-input table-chkbox quotationCheck" type="checkbox" value="<?php echo (int) $list->TransUID; ?>">
                </div>
            </td>
            <td class="<?php echo $JwtData->GenSettings->SerialNoDisplay == 1 ? '' : 'd-none'; ?>"><?php echo $SerialNumber; ?></td>
            <td><?php echo htmlspecialchars(format_datedisplay($list->TransDate, 'd M Y')); ?></td>
            <td>
                <span class="fw-semibold"><?php echo htmlspecialchars($list->UniqueNumber); ?></span>
                <?php if (!empty($list->ValidityDate)) { ?>
                    <br><small class="text-muted">Valid till: <?php echo format_datedisplay($list->ValidityDate, 'd M Y'); ?></small>
                <?php } ?>
            </td>
            <td><?php echo htmlspecialchars($list->PartyName ?? '—'); ?></td>
            <td><span class="badge <?php echo $statusClass; ?>"><?php echo htmlspecialchars($list->Status); ?></span></td>
            <td class="text-end">
                <?php echo htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? ''); ?>
                <?php echo smartDecimal($list->NetAmount, $JwtData->GenSettings->DecimalPoints, true); ?>
            </td>
            <td>
                <div class="d-flex align-items-center justify-content-center">
                    <a href="/quotations/create/<?php echo (int) $list->TransUID; ?>" class="btn btn-icon text-warning" data-bs-toggle="tooltip" data-bs-title="Edit"><i class="bx bx-edit"></i></a>
                    <button class="btn btn-icon text-danger deleteQuotation" data-uid="<?php echo (int) $list->TransUID; ?>" data-bs-toggle="tooltip" data-bs-title="Delete"><i class="bx bx-trash"></i></button>
                </div>
            </td>
        </tr>
<?php }
} else { ?>
    <tr>
        <td colspan="8">
            <div class="d-flex justify-content-center align-items-center py-5">
                <div class="d-flex flex-column align-items-center" style="max-width:400px;">
                    <img src="/assets/img/elements/no-record-found.png" alt="No Records Found" class="img-fluid mb-3" style="max-height:200px;object-fit:contain;" />
                    <span class="text-muted mb-3">No quotations found</span>
                    <a href="/quotations/create" class="btn btn-primary btn-sm px-3">
                        <i class="bx bx-plus"></i> Create Quotation
                    </a>
                </div>
            </div>
        </td>
    </tr>
<?php } ?>
