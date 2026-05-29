<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$currency = htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹');
$decimals = (int)($JwtData->GenSettings->DecimalPoints ?? 2);

$moduleMap = [
    103 => ['label' => 'Invoice',          'color' => '#2563eb'],
    105 => ['label' => 'Purchase',         'color' => '#16a34a'],
    106 => ['label' => 'Sales Return',     'color' => '#0891b2'],
    107 => ['label' => 'Credit Note',      'color' => '#0891b2'],
    108 => ['label' => 'Purchase Return',  'color' => '#d97706'],
    118 => ['label' => 'Manual Adj.',      'color' => '#7c3aed'],
];

// Maps ModuleUID → viewTransModal type string (used by viewmodal.js _typeConfig)
$moduleTypeMap = [
    103 => 'invoice',
    105 => 'purchase',
    106 => 'salesreturn',
    107 => 'creditnote',
    108 => 'purchasereturn',
];

if (!empty($DataLists)):
    foreach ($DataLists as $row):
        $moduleUID = (int)$row->ModuleUID;
        $isIN      = $row->MovementType === 'IN';
        $qty       = (float)$row->Quantity;
        $cost      = isset($row->SellingPrice) && $row->SellingPrice !== null ? (float)$row->SellingPrice : (float)($row->UnitCost ?? 0);
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
            $refNum = !empty($row->UniqueNumber) ? $row->UniqueNumber : '';
        }

        $remarks          = !empty($row->Remarks) ? htmlspecialchars($row->Remarks) : '';
        $createdOnDisplay = !empty($row->CreatedOn) ? date('d-m-Y H:i', strtotime($row->CreatedOn)) : '';
        $byName           = trim($row->CreatedByName ?? '');

        // viewTransModal support: module must be in type map and have a TransactionUID
        $txType    = $moduleTypeMap[$moduleUID] ?? null;
        $txUID     = !empty($row->TransactionUID) ? (int)$row->TransactionUID : 0;
        $canView   = $txType !== null && $txUID > 0;
        $transDate = htmlspecialchars($row->TransDate ?? '');
        $docStatus = htmlspecialchars($row->DocStatus ?? '');
?>
<tr>

    <!-- Item — same style as inventory list -->
    <td>
        <div class="fw-semibold" style="font-size:.85rem;"><?php echo htmlspecialchars($row->ItemName); ?></div>
        <?php if (!empty($row->SnapItemName) && $row->SnapItemName !== $row->ItemName): ?>
        <div class="text-muted" style="font-size:.68rem;">
            <i class="bx bx-history me-1" style="color:#94a3b8;"></i>At movement: <?php echo htmlspecialchars($row->SnapItemName); ?>
        </div>
        <?php endif; ?>
        <?php if (!empty($row->PartNumber)): ?>
        <div class="text-muted" style="font-size:.7rem;">Part# <?php echo htmlspecialchars($row->PartNumber); ?></div>
        <?php endif; ?>
        <div class="d-flex align-items-center flex-wrap gap-1 mt-1" style="font-size:.72rem;">
            <?php if (($row->ProductType ?? '') === 'Service'): ?>
                <span class="badge bg-label-info" style="font-size:.65rem;">Service</span>
            <?php else: ?>
                <span class="badge bg-label-primary" style="font-size:.65rem;">Product</span>
            <?php endif; ?>
            <?php if (!empty($row->HSNSACCode)): ?>
                <span class="text-muted" style="font-size:.68rem;"><strong>HSN:</strong> <?php echo htmlspecialchars($row->HSNSACCode); ?></span>
            <?php endif; ?>
            <?php if (!empty($row->PartNumber)): ?>
                <span title="Barcode — <?php echo htmlspecialchars($row->PartNumber); ?>" style="cursor:default;line-height:1;">
                    <i class="bx bx-barcode text-primary" style="font-size:1.1rem;vertical-align:middle;"></i>
                </span>
                <span title="QR Code — <?php echo htmlspecialchars($row->PartNumber); ?>" style="cursor:default;line-height:1;">
                    <i class="bx bx-qr text-info" style="font-size:.95rem;vertical-align:middle;"></i>
                </span>
            <?php endif; ?>
            <?php if (!empty($row->Description)):
                $descPlain = strip_tags($row->Description);
                $descTip   = htmlspecialchars(mb_strimwidth($descPlain, 0, 160, '…')); ?>
                <i class="bx bx-info-circle text-warning"
                   style="font-size:.95rem;cursor:pointer;vertical-align:middle;"
                   data-bs-toggle="tooltip" data-bs-placement="top"
                   data-bs-title="<?php echo $descTip; ?>"
                   title="<?php echo $descTip; ?>"></i>
            <?php endif; ?>
        </div>
    </td>

    <!-- Stock In -->
    <td style="text-align:right;">
        <?php if ($isIN): ?>
        <span class="fw-bold" style="color:#16a34a;font-size:.92rem;"><?php echo number_format($qty, 0); ?></span>
        <span style="font-size:.78rem;color:#6c757d;margin-left:3px;"><?php echo htmlspecialchars($row->UnitName ?? 'PCS'); ?></span>
        <?php else: ?>
        <span class="text-muted">—</span>
        <?php endif; ?>
    </td>

    <!-- Stock Out -->
    <td style="text-align:right;">
        <?php if (!$isIN): ?>
        <span class="fw-bold" style="color:#dc2626;font-size:.92rem;"><?php echo number_format($qty, 0); ?></span>
        <span style="font-size:.78rem;color:#6c757d;margin-left:3px;"><?php echo htmlspecialchars($row->UnitName ?? 'PCS'); ?></span>
        <?php else: ?>
        <span class="text-muted">—</span>
        <?php endif; ?>
    </td>

    <!-- Price -->
    <td style="text-align:right;font-size:.85rem;font-weight:600;">
        <?php echo $cost > 0 ? ($currency . ' ' . number_format($cost, $decimals)) : '<span class="text-muted" style="font-weight:400;">—</span>'; ?>
    </td>

    <!-- Source: click opens viewTransModal; Manual Adj. shown as plain text -->
    <td>
        <?php if ($refNum && $canView): ?>
        <a href="javascript:void(0);"
           class="viewTransaction"
           data-uid="<?php echo $txUID; ?>"
           data-module="<?php echo $moduleUID; ?>"
           data-type="<?php echo $txType; ?>"
           data-number="<?php echo htmlspecialchars($refNum); ?>"
           data-date="<?php echo $transDate; ?>"
           data-status="<?php echo $docStatus; ?>"
           style="font-weight:600;font-size:.82rem;color:<?php echo $modMeta['color']; ?>;text-decoration:none;"
           title="Click to view details"
           onmouseover="this.style.textDecoration='underline'"
           onmouseout="this.style.textDecoration='none'">
            <?php echo htmlspecialchars($refNum); ?>
        </a>
        <?php elseif ($refNum): ?>
        <div style="font-weight:600;font-size:.82rem;color:<?php echo $modMeta['color']; ?>;"><?php echo htmlspecialchars($refNum); ?></div>
        <?php else: ?>
        <div style="font-weight:600;font-size:.82rem;color:<?php echo $modMeta['color']; ?>;"><?php echo $modMeta['label']; ?></div>
        <?php endif; ?>
        <div style="font-size:.72rem;color:#6c757d;"><?php echo $modMeta['label']; ?></div>
    </td>

    <!-- Category -->
    <td>
        <?php if (!empty($row->CategoryName)): ?>
        <span class="badge text-bg-light border" style="font-size:.7rem;font-weight:500;"><?php echo htmlspecialchars($row->CategoryName); ?></span>
        <?php else: ?>
        <span class="text-muted">—</span>
        <?php endif; ?>
    </td>

    <!-- Remarks -->
    <td style="max-width:160px;">
        <?php if ($remarks): ?>
        <div style="font-size:.8rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"
             title="<?php echo htmlspecialchars($row->Remarks ?? ''); ?>"><?php echo $remarks; ?></div>
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
        <button class="btn btn-icon btn-sm tlEditAdjBtn"
                data-ledger-uid="<?php echo (int)$row->LedgerUID; ?>"
                data-product-name="<?php echo htmlspecialchars($row->ItemName); ?>"
                data-qty="<?php echo $qty; ?>"
                data-unit="<?php echo htmlspecialchars($row->UnitName ?? ''); ?>"
                data-notes="<?php echo htmlspecialchars($row->Remarks ?? ''); ?>"
                data-created-on="<?php echo htmlspecialchars($createdOnDisplay); ?>"
                title="Edit Remarks">
            <i class="bx bx-edit"></i>
        </button>
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
