<?php defined('BASEPATH') or exit('No direct script access allowed');

if (empty($DataLists)) { ?>
    <tr>
        <td colspan="7" class="text-center py-5 text-muted">
            <i class="bx bx-receipt fs-1 d-block mb-2"></i>
            No additional charges defined yet.
        </td>
    </tr>
<?php return; }

$idx = 1;
foreach ($DataLists as $row):
    $isSystem    = (int)($row->IsSystem    ?? 0) === 1;
    $showDefault = (int)($row->ShowByDefault ?? 0) === 1;
    $taxLabel    = '';
    if (!empty($row->TaxPercentage) || isset($row->DefaultTaxUID) && $row->DefaultTaxUID) {
        $pct      = floatval($row->TaxPercentage ?? 0);
        $taxLabel = $pct . '%';
    }

    $editData = htmlspecialchars(json_encode([
        'ChargeUID'     => (int)$row->ChargeUID,
        'Name'          => $row->Name          ?? '',
        'DisplayName'   => $row->DisplayName   ?? '',
        'DefaultTaxUID' => (int)($row->DefaultTaxUID ?? 0),
        'Description'   => $row->Description   ?? '',
        'ShowByDefault' => (int)($row->ShowByDefault ?? 0),
        'IsSystem'      => (int)($row->IsSystem ?? 0),
        'SortOrder'     => (int)($row->SortOrder ?? 0),
    ]), ENT_QUOTES);
?>
<tr>
    <td class="text-center align-middle" style="width:50px"><?php echo $idx++; ?></td>

    <td class="align-middle">
        <div class="fw-semibold"><?php echo htmlspecialchars($row->DisplayName ?? ''); ?></div>
        <?php if ($isSystem): ?>
            <span class="badge bg-label-secondary mt-1" style="font-size:.68rem;">System</span>
        <?php endif; ?>
    </td>

    <td class="align-middle">
        <?php if ($taxLabel): ?>
            <span class="badge bg-label-info"><?php echo $taxLabel; ?></span>
        <?php else: ?>
            <span class="text-muted small">— No default —</span>
        <?php endif; ?>
    </td>

    <td class="align-middle" style="max-width:220px;white-space:normal;font-size:.78rem;color:#555;">
        <?php echo htmlspecialchars($row->Description ?? '—'); ?>
    </td>

    <td class="text-center align-middle">
        <?php if ($showDefault): ?>
            <span class="badge bg-success">Yes</span>
        <?php else: ?>
            <span class="badge bg-label-secondary">No</span>
        <?php endif; ?>
    </td>

    <td class="text-center align-middle" style="width:60px;">
        <?php echo (int)($row->SortOrder ?? 0); ?>
    </td>

    <td class="text-center align-middle" style="width:90px;">
        <div class="d-flex align-items-center justify-content-center gap-1">
            <button type="button"
                    class="btn btn-icon btn-sm text-warning EditAdditionalCharge"
                    data-charge='<?php echo $editData; ?>'
                    title="Edit">
                <i class="bx bx-edit"></i>
            </button>
            <?php if (!$isSystem): ?>
            <button type="button"
                    class="btn btn-icon btn-sm text-danger DeleteAdditionalCharge"
                    data-uid="<?php echo (int)$row->ChargeUID; ?>"
                    data-name="<?php echo htmlspecialchars($row->DisplayName ?? ''); ?>"
                    title="Delete">
                <i class="bx bx-trash"></i>
            </button>
            <?php endif; ?>
        </div>
    </td>
</tr>
<?php endforeach; ?>
