<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$currency = htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹');
$decimals = (int)($JwtData->GenSettings->DecimalPoints ?? 2);

$moduleMap = [
    103 => ['label' => 'invoice',          'color' => '#2563eb'],
    105 => ['label' => 'purchase',         'color' => '#16a34a'],
    106 => ['label' => 'Sales Return',     'color' => '#0891b2'],
    107 => ['label' => 'Credit Note',      'color' => '#0891b2'],
    108 => ['label' => 'Purchase Return',  'color' => '#d97706'],
    118 => ['label' => 'Manual Adj.',      'color' => '#7c3aed'],
];

if (!empty($DataLists)):
    foreach ($DataLists as $row):
        $moduleUID = (int)$row->ModuleUID;
        $isIN      = $row->MovementType === 'IN';
        $qty       = (float)$row->Quantity;
        $cost      = (float)($row->UnitCost ?? 0);
        $modMeta   = $moduleMap[$moduleUID] ?? ['label' => 'Unknown', 'color' => '#64748b'];

        // Effective date
        if ($moduleUID === 118 && !empty($row->AdjDate)) {
            $effDate = date('d M Y', strtotime($row->AdjDate));
        } elseif (!empty($row->TransDate)) {
            $effDate = date('d M Y', strtotime($row->TransDate));
        } else {
            $effDate = date('d M Y', strtotime($row->CreatedOn));
        }

        // Reference number
        if ($moduleUID === 118) {
            $refNum = !empty($row->AdjUID) ? 'ADJ-' . (int)$row->AdjUID : '';
        } else {
            $refNum = !empty($row->TransNumber) ? $row->TransNumber : '';
        }

        $category = ($moduleUID === 118 && !empty($row->AdjCategory)) ? htmlspecialchars($row->AdjCategory) : '';
        $remarks  = ($moduleUID === 118 && !empty($row->AdjNotes))    ? htmlspecialchars($row->AdjNotes)    : '';
        $byName   = trim($row->CreatedByName ?? '');
?>
<tr>

    <!-- Item -->
    <td>
        <div class="fw-semibold" style="font-size:.85rem;"><?php echo htmlspecialchars($row->ItemName); ?></div>
        <?php if (!empty($row->CategoryName)): ?>
        <div style="font-size:.7rem;color:#e11d48;font-weight:500;"><?php echo htmlspecialchars($row->CategoryName); ?></div>
        <?php endif; ?>
    </td>

    <!-- Stock In -->
    <td style="text-align:right;">
        <?php if ($isIN): ?>
        <div class="fw-bold" style="color:#16a34a;font-size:.92rem;"><?php echo number_format($qty, 0); ?></div>
        <div style="font-size:.7rem;color:#6c757d;"><?php echo htmlspecialchars($row->UnitName ?? 'PCS'); ?></div>
        <?php else: ?>
        <span class="text-muted">—</span>
        <?php endif; ?>
    </td>

    <!-- Stock Out -->
    <td style="text-align:right;">
        <?php if (!$isIN): ?>
        <div class="fw-bold" style="color:#dc2626;font-size:.92rem;"><?php echo number_format($qty, 0); ?></div>
        <div style="font-size:.7rem;color:#6c757d;"><?php echo htmlspecialchars($row->UnitName ?? 'PCS'); ?></div>
        <?php else: ?>
        <span class="text-muted">—</span>
        <?php endif; ?>
    </td>

    <!-- Price -->
    <td style="text-align:right;font-size:.85rem;">
        <?php echo $cost > 0 ? ($currency . ' ' . number_format($cost, $decimals)) : '<span class="text-muted">—</span>'; ?>
    </td>

    <!-- Source -->
    <td>
        <div style="color:<?php echo $modMeta['color']; ?>;font-weight:600;font-size:.82rem;"><?php echo $modMeta['label']; ?></div>
        <?php if ($refNum): ?>
        <div style="font-size:.72rem;color:#6c757d;"><?php echo htmlspecialchars($refNum); ?></div>
        <?php endif; ?>
    </td>

    <!-- Category -->
    <td style="font-size:.82rem;">
        <?php echo $category ?: '<span class="text-muted">—</span>'; ?>
    </td>

    <!-- Remarks -->
    <td style="max-width:160px;">
        <?php if ($remarks): ?>
        <div style="font-size:.8rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"
             title="<?php echo htmlspecialchars($row->AdjNotes ?? ''); ?>"><?php echo $remarks; ?></div>
        <?php else: ?>
        <span class="text-muted" style="font-size:.8rem;">—</span>
        <?php endif; ?>
    </td>

    <!-- Date / Updated By -->
    <td>
        <div style="font-size:.82rem;"><?php echo $effDate; ?></div>
        <?php if ($byName): ?>
        <div style="font-size:.7rem;color:#6c757d;">by <?php echo htmlspecialchars($byName); ?></div>
        <?php endif; ?>
    </td>

    <!-- Actions -->
    <td>
        <?php if ($moduleUID === 118): ?>
        <button class="btn btn-sm btn-outline-secondary tlEditAdjBtn"
                data-adj-uid="<?php echo (int)$row->AdjUID; ?>"
                data-product-uid="<?php echo (int)$row->ProductUID; ?>"
                data-product-name="<?php echo htmlspecialchars($row->ItemName); ?>"
                data-movement="<?php echo htmlspecialchars($row->MovementType); ?>"
                data-qty="<?php echo $qty; ?>"
                data-cost="<?php echo $cost; ?>"
                data-category="<?php echo htmlspecialchars($row->AdjCategory ?? ''); ?>"
                data-notes="<?php echo htmlspecialchars($row->AdjNotes ?? ''); ?>"
                data-date="<?php echo htmlspecialchars($row->AdjDate ?? ''); ?>"
                data-unit="<?php echo htmlspecialchars($row->UnitName ?? ''); ?>"
                style="font-size:.75rem;">
            <i class="bx bx-edit me-1"></i>Edit
        </button>
        <?php elseif ($moduleUID === 103): ?>
        <a href="/invoices/<?php echo (int)$row->TransactionUID; ?>/edit"
           class="btn btn-sm btn-outline-secondary" style="font-size:.75rem;" title="Open Invoice">
            <i class="bx bx-edit me-1"></i>Edit
        </a>
        <?php elseif ($moduleUID === 105): ?>
        <a href="/purchases/<?php echo (int)$row->TransactionUID; ?>/edit"
           class="btn btn-sm btn-outline-secondary" style="font-size:.75rem;" title="Open Purchase">
            <i class="bx bx-edit me-1"></i>Edit
        </a>
        <?php else: ?>
        <span class="text-muted" style="font-size:.75rem;">—</span>
        <?php endif; ?>
    </td>

</tr>
<?php
    endforeach;
else:
?>
<tr>
    <td colspan="9">
        <div class="d-flex flex-column align-items-center py-5">
            <img src="/assets/img/elements/no-record-found.png" alt="No Records"
                 class="img-fluid mb-3" style="max-height:150px;object-fit:contain;">
            <span class="text-muted" style="font-size:.9rem;">No stock movements found for this period</span>
        </div>
    </td>
</tr>
<?php endif; ?>
