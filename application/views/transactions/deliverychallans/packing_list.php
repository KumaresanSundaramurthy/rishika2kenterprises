<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Packing List — <?php echo htmlspecialchars($PackingHeader->UniqueNumber ?? 'Draft'); ?></title>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
        font-family: Arial, sans-serif;
        font-size: 11px;
        color: #111;
        background: #fff;
        padding: 5mm;
    }

    .pl-wrapper { max-width: 210mm; margin: 0 auto; }

    /* ── Header ── */
    .pl-header { border-bottom: 2px solid #111; padding-bottom: 8px; margin-bottom: 10px; }
    .pl-org-name { font-size: 16px; font-weight: 700; letter-spacing: .3px; }
    .pl-org-meta { font-size: 10px; color: #444; margin-top: 2px; }
    .pl-doc-title { font-size: 14px; font-weight: 700; text-align: right; letter-spacing: 1px; text-transform: uppercase; }
    .pl-doc-ref { font-size: 10px; color: #555; text-align: right; margin-top: 3px; }

    /* ── Info grid ── */
    .pl-info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0; margin-bottom: 10px; border: 1px solid #ccc; }
    .pl-info-box { padding: 6px 8px; }
    .pl-info-box:first-child { border-right: 1px solid #ccc; }
    .pl-info-label { font-size: 9px; font-weight: 700; text-transform: uppercase; color: #666; letter-spacing: .4px; margin-bottom: 2px; }
    .pl-info-value { font-size: 11px; font-weight: 600; }
    .pl-info-sub { font-size: 10px; color: #444; margin-top: 1px; }

    /* ── Details strip ── */
    .pl-details-strip { display: flex; gap: 0; border: 1px solid #ccc; border-top: none; margin-bottom: 10px; }
    .pl-detail-cell { flex: 1; padding: 5px 8px; border-right: 1px solid #ccc; }
    .pl-detail-cell:last-child { border-right: none; }
    .pl-detail-cell .pl-info-label { display: block; }

    /* ── Items table ── */
    .pl-table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
    .pl-table th {
        background: #1e1e2d;
        color: #fff;
        font-size: 10px;
        font-weight: 600;
        padding: 6px 7px;
        text-align: left;
        letter-spacing: .3px;
    }
    .pl-table th.text-center, .pl-table td.text-center { text-align: center; }
    .pl-table th.text-right,  .pl-table td.text-right  { text-align: right; }
    .pl-table td {
        padding: 7px 7px;
        border-bottom: 1px solid #e5e5e5;
        font-size: 11px;
        vertical-align: top;
    }
    .pl-table tr:nth-child(even) td { background: #fafafa; }
    .pl-table .item-name { font-weight: 600; }
    .pl-table .item-desc { font-size: 9.5px; color: #555; margin-top: 2px; }
    .pl-table .item-part { font-size: 9.5px; color: #777; margin-top: 1px; }
    .pl-table tfoot td {
        padding: 7px 7px;
        font-weight: 700;
        font-size: 11px;
        border-top: 2px solid #111;
        background: #f5f5f5;
    }

    /* ── Footer section ── */
    .pl-footer-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 14px; }
    .pl-sign-box { border: 1px solid #ccc; padding: 8px 10px; min-height: 70px; }
    .pl-sign-label { font-size: 9px; font-weight: 700; text-transform: uppercase; color: #666; letter-spacing: .4px; margin-bottom: 4px; }
    .pl-sign-name { font-size: 10px; margin-top: 40px; color: #333; border-top: 1px solid #aaa; padding-top: 4px; }

    /* ── Remarks ── */
    .pl-remarks { border: 1px solid #ccc; padding: 7px 10px; margin-top: 10px; font-size: 10px; }
    .pl-remarks-label { font-size: 9px; font-weight: 700; text-transform: uppercase; color: #666; letter-spacing: .4px; margin-bottom: 3px; }

    /* ── Disclaimer ── */
    .pl-disclaimer { text-align: center; font-size: 9px; color: #888; margin-top: 14px; border-top: 1px solid #ddd; padding-top: 6px; }

    /* ── Print overrides ── */
    @media print {
        body { padding: 5mm; }
        .no-print { display: none !important; }
        .pl-table th { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .pl-table tr:nth-child(even) td { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    }
</style>
</head>
<body>
<?php
$h          = $PackingHeader;
$items      = $PackingItems ?? [];
$org        = $OrgInfo;
$challanNo  = htmlspecialchars($h->UniqueNumber ?? '—');
$dispatchDt = !empty($h->TransDate)    ? format_datedisplay($h->TransDate, 'd M Y')    : '—';
$returnDt   = !empty($h->ValidityDate) ? format_datedisplay($h->ValidityDate, 'd M Y') : '—';
$challanType= htmlspecialchars($h->QuotationType ?? 'Non-Returnable');
$vehicleNo  = htmlspecialchars($h->Reference ?? '—');
$customer   = htmlspecialchars($h->PartyName ?? '—');
$totalQty   = 0;
foreach ($items as $item) $totalQty += (float)($item->Quantity ?? 0);
?>

<div class="pl-wrapper">

    <!-- ── Header ── -->
    <div class="pl-header" style="display:flex;justify-content:space-between;align-items:flex-start;">
        <div>
            <div class="pl-org-name"><?php echo htmlspecialchars($org->OrgName ?? 'Organisation'); ?></div>
            <?php if (!empty($org->AddressLine1)): ?>
            <div class="pl-org-meta"><?php echo htmlspecialchars($org->AddressLine1); ?><?php echo !empty($org->AddressLine2) ? ', ' . htmlspecialchars($org->AddressLine2) : ''; ?></div>
            <?php endif; ?>
            <?php if (!empty($org->City) || !empty($org->Pincode)): ?>
            <div class="pl-org-meta"><?php echo htmlspecialchars(trim(($org->City ?? '') . ' ' . ($org->Pincode ?? ''))); ?><?php echo !empty($org->StateName) ? ', ' . htmlspecialchars($org->StateName) : ''; ?></div>
            <?php endif; ?>
            <?php if (!empty($org->GSTIN)): ?>
            <div class="pl-org-meta">GSTIN: <?php echo htmlspecialchars($org->GSTIN); ?></div>
            <?php endif; ?>
            <?php if (!empty($org->MobileNumber)): ?>
            <div class="pl-org-meta">Ph: <?php echo htmlspecialchars($org->MobileNumber); ?></div>
            <?php endif; ?>
        </div>
        <div style="text-align:right;">
            <div class="pl-doc-title">Packing List</div>
            <div class="pl-doc-ref">Ref: <?php echo $challanNo; ?></div>
            <div class="pl-doc-ref">Date: <?php echo $dispatchDt; ?></div>
            <div style="margin-top:6px;">
                <span style="background:#1e1e2d;color:#fff;font-size:9px;padding:2px 7px;border-radius:3px;letter-spacing:.3px;"><?php echo $challanType; ?></span>
            </div>
        </div>
    </div>

    <!-- ── Consignee & Challan info ── -->
    <div class="pl-info-grid">
        <div class="pl-info-box">
            <div class="pl-info-label">Consignee (Ship To)</div>
            <div class="pl-info-value"><?php echo $customer; ?></div>
            <?php if (!empty($h->CustomerAddress)): ?>
            <div class="pl-info-sub"><?php echo htmlspecialchars($h->CustomerAddress); ?></div>
            <?php endif; ?>
        </div>
        <div class="pl-info-box">
            <div class="pl-info-label">Delivery Challan Details</div>
            <div class="pl-info-value"><?php echo $challanNo; ?></div>
            <div class="pl-info-sub">Dispatch Date: <?php echo $dispatchDt; ?></div>
            <?php if ($challanType === 'Returnable'): ?>
            <div class="pl-info-sub">Expected Return: <?php echo $returnDt; ?></div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ── Transport info strip ── -->
    <div class="pl-details-strip">
        <div class="pl-detail-cell">
            <span class="pl-info-label">Vehicle No.</span>
            <strong><?php echo $vehicleNo !== '—' ? $vehicleNo : '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'; ?></strong>
        </div>
        <div class="pl-detail-cell">
            <span class="pl-info-label">Total Packages</span>
            <strong><?php echo count($items); ?></strong>
        </div>
        <div class="pl-detail-cell">
            <span class="pl-info-label">Total Quantity</span>
            <strong><?php echo number_format($totalQty, 2); ?></strong>
        </div>
        <div class="pl-detail-cell">
            <span class="pl-info-label">Gross Weight</span>
            <strong>____________ kg</strong>
        </div>
        <div class="pl-detail-cell">
            <span class="pl-info-label">Net Weight</span>
            <strong>____________ kg</strong>
        </div>
    </div>

    <!-- ── Items table ── -->
    <table class="pl-table">
        <thead>
            <tr>
                <th style="width:36px;" class="text-center">S.No</th>
                <th>Description of Goods</th>
                <th style="width:90px;">Part No.</th>
                <th style="width:70px;" class="text-center">Qty</th>
                <th style="width:55px;" class="text-center">Unit</th>
                <th style="width:80px;">Pkg No.</th>
                <th style="width:85px;">Weight (kg)</th>
                <th style="width:70px;">Remarks</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($items)): ?>
                <?php foreach ($items as $i => $item): ?>
                <tr>
                    <td class="text-center"><?php echo $i + 1; ?></td>
                    <td>
                        <div class="item-name"><?php echo htmlspecialchars($item->ProductName ?? ''); ?></div>
                        <?php if (!empty($item->Description)): ?>
                        <div class="item-desc"><?php echo htmlspecialchars($item->Description); ?></div>
                        <?php endif; ?>
                        <?php if (!empty($item->CategoryName)): ?>
                        <div class="item-part"><?php echo htmlspecialchars($item->CategoryName); ?></div>
                        <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($item->PartNumber ?? '—'); ?></td>
                    <td class="text-center"><strong><?php echo number_format((float)($item->Quantity ?? 0), 2); ?></strong></td>
                    <td class="text-center"><?php echo htmlspecialchars($item->PrimaryUnitName ?? ''); ?></td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="8" style="text-align:center;padding:20px;color:#888;">No items found</td></tr>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" style="text-align:right;">Total</td>
                <td class="text-center"><?php echo number_format($totalQty, 2); ?></td>
                <td colspan="4"></td>
            </tr>
        </tfoot>
    </table>

    <!-- ── Notes/Remarks ── -->
    <?php if (!empty($h->Notes)): ?>
    <div class="pl-remarks">
        <div class="pl-remarks-label">Remarks / Special Instructions</div>
        <?php echo nl2br(htmlspecialchars($h->Notes)); ?>
    </div>
    <?php endif; ?>

    <!-- ── Signatures ── -->
    <div class="pl-footer-grid" style="margin-top:<?php echo !empty($h->Notes) ? '10px' : '14px'; ?>;">
        <div class="pl-sign-box">
            <div class="pl-sign-label">Packed &amp; Dispatched By</div>
            <div class="pl-sign-name">Authorised Signatory — <?php echo htmlspecialchars($org->OrgName ?? ''); ?></div>
        </div>
        <div class="pl-sign-box">
            <div class="pl-sign-label">Received By</div>
            <div class="pl-sign-name">Name &amp; Signature of Receiver</div>
        </div>
    </div>

    <!-- ── Disclaimer ── -->
    <div class="pl-disclaimer">
        This is a Packing List only. It does not constitute a Tax Invoice. &nbsp;|&nbsp;
        Goods once dispatched will not be accepted back without prior approval. &nbsp;|&nbsp;
        Generated on <?php echo date('d M Y, h:i A'); ?>
    </div>

</div>

<!-- Print button (hidden on print) -->
<div class="no-print" style="text-align:center;margin-top:20px;padding-bottom:20px;">
    <button onclick="window.print()" style="padding:8px 24px;background:#1e1e2d;color:#fff;border:none;border-radius:4px;cursor:pointer;font-size:13px;margin-right:8px;">
        🖨️ Print
    </button>
    <button onclick="window.close()" style="padding:8px 18px;background:#6c757d;color:#fff;border:none;border-radius:4px;cursor:pointer;font-size:13px;">
        Close
    </button>
</div>

<script>
    // Auto-print on load
    window.addEventListener('load', function () {
        // Small delay so the browser renders fully before print dialog opens
        setTimeout(function () { window.print(); }, 400);
    });
</script>

</body>
</html>
