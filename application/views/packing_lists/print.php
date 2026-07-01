<?php defined('BASEPATH') or exit('No direct script access allowed');
/**
 * Packing List — print view
 * Variables: $DCHeader, $PL (may be null), $PLItems, $OrgInfo, $JwtData
 */
$dc      = $DCHeader;
$org     = $OrgInfo;
$pl      = $PL ?? null;
$items   = $PLItems ?? [];

$_fmt       = $JwtData->GenSettings->PrintDateFormat ?? 'd M Y';
$dcNum      = htmlspecialchars($dc->UniqueNumber     ?? '—');
$dcDate     = !empty($dc->TransDate)  ? format_datedisplay($dc->TransDate,  $_fmt) : '—';
$customer   = htmlspecialchars($dc->PartyName        ?? '—');
$challanType= htmlspecialchars($dc->QuotationType    ?? 'Non-Returnable');

$plNum      = $pl ? htmlspecialchars($pl->UniqueNumber)    : '—';
$plDate     = $pl && !empty($pl->PLDate) ? format_datedisplay($pl->PLDate, $_fmt) : $dcDate;
$vehicleNo  = htmlspecialchars($pl->VehicleNumber    ?? ($dc->Reference ?? ''));
$lrNum      = htmlspecialchars($pl->LRNumber         ?? '');
$transporter= htmlspecialchars($pl->TransporterName  ?? '');
$notes      = $pl->Notes ?? ($dc->Notes ?? '');

// Totals
$totalQty   = 0; $totalPkgs = 0; $totalNet = 0.0; $totalGross = 0.0;
foreach ($items as $item) {
    $totalQty   += (float) ($item->Quantity         ?? 0);
    $totalPkgs  += (int)   ($item->NumberOfPackages ?? 0);
    $totalNet   += (float) ($item->NetWeight        ?? 0);
    $totalGross += (float) ($item->GrossWeight      ?? 0);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Packing List <?php echo $plNum !== '—' ? $plNum : ''; ?> | <?php echo $dcNum; ?></title>
<style>
/* ── Reset ── */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

/* ── Base ── */
body {
    font-family: 'Arial', sans-serif;
    font-size: 11px;
    color: #1a1a1a;
    background: #e8e8e8;
    padding: 20px;
    line-height: 1.4;
}

/* ── A4 Page wrapper ── */
.page {
    background: #fff;
    width: 210mm;
    min-height: 297mm;
    margin: 0 auto;
    padding: 14mm 14mm 12mm;
    box-shadow: 0 4px 24px rgba(0,0,0,.18);
    position: relative;
}

/* ══════════════════════════════════════════════
   HEADER
═══════════════════════════════════════════════ */
.doc-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding-bottom: 10px;
    border-bottom: 3px solid #1a1a1a;
    margin-bottom: 10px;
}
.org-block {}
.org-name {
    font-size: 18px;
    font-weight: 800;
    color: #1a1a1a;
    letter-spacing: -.2px;
    line-height: 1.2;
}
.org-meta {
    font-size: 9.5px;
    color: #555;
    margin-top: 2px;
    line-height: 1.5;
}
.doc-title-block { text-align: right; }
.doc-title {
    font-size: 22px;
    font-weight: 800;
    letter-spacing: 2px;
    text-transform: uppercase;
    color: #1a1a1a;
    line-height: 1.1;
}
.doc-pl-num {
    font-size: 13px;
    font-weight: 700;
    color: #1a1a1a;
    margin-top: 4px;
}
.doc-pl-date {
    font-size: 10px;
    color: #555;
    margin-top: 2px;
}

/* ══════════════════════════════════════════════
   DC REFERENCE STRIP
═══════════════════════════════════════════════ */
.dc-strip {
    display: grid;
    grid-template-columns: 1fr auto 1fr;
    align-items: center;
    border: 1px solid #ccc;
    border-radius: 4px;
    overflow: hidden;
    margin-bottom: 8px;
}
.dc-strip-left  { padding: 8px 12px; }
.dc-strip-center { border-left: 1px solid #ccc; border-right: 1px solid #ccc; padding: 8px 14px; text-align: center; }
.dc-strip-right { padding: 8px 12px; text-align: right; }
.strip-label {
    font-size: 8.5px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .5px;
    color: #888;
    margin-bottom: 2px;
}
.strip-value {
    font-size: 11.5px;
    font-weight: 700;
    color: #1a1a1a;
}
.strip-value.sm { font-size: 10.5px; font-weight: 600; }
.challan-badge {
    display: inline-block;
    border: 1.5px solid #1a1a1a;
    color: #1a1a1a;
    font-size: 9px;
    font-weight: 700;
    letter-spacing: .5px;
    text-transform: uppercase;
    padding: 2px 8px;
    border-radius: 2px;
    margin-top: 2px;
}

/* ══════════════════════════════════════════════
   TRANSPORT INFO STRIP
═══════════════════════════════════════════════ */
.transport-strip {
    display: flex;
    border: 1px solid #ccc;
    border-top: none;
    border-radius: 0 0 4px 4px;
    overflow: hidden;
    margin-bottom: 12px;
}
.t-cell {
    flex: 1;
    padding: 6px 10px;
    border-right: 1px solid #ccc;
}
.t-cell:last-child { border-right: none; }
.t-cell.highlight { background: #f5f5f5; }
.t-label {
    font-size: 8px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .5px;
    color: #999;
    margin-bottom: 2px;
}
.t-value {
    font-size: 11px;
    font-weight: 700;
    color: #1a1a1a;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.t-value.empty { color: #bbb; font-weight: 400; font-style: italic; }

/* ══════════════════════════════════════════════
   ITEMS TABLE
═══════════════════════════════════════════════ */
.items-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 12px;
    font-size: 10.5px;
}
.items-table thead tr {
    background: #1a1a1a;
}
.items-table thead th {
    color: #fff;
    font-size: 9.5px;
    font-weight: 700;
    padding: 7px 7px;
    text-align: left;
    letter-spacing: .3px;
    text-transform: uppercase;
    white-space: nowrap;
}
.items-table thead th.tc { text-align: center; }
.items-table thead th.tr { text-align: right; }

.items-table tbody td {
    padding: 7px 7px;
    border-bottom: 1px solid #eee;
    vertical-align: top;
    color: #1a1a1a;
}
.items-table tbody tr:nth-child(even) td { background: #f9f9f9; }
.items-table tbody tr:last-child td { border-bottom: none; }

.items-table tbody td.tc { text-align: center; }
.items-table tbody td.tr { text-align: right; }

.prod-name { font-weight: 700; font-size: 11px; }
.prod-part { font-size: 9px; color: #777; margin-top: 2px; font-style: italic; }

.items-table tfoot tr {
    background: #f0f0f0;
}
.items-table tfoot td {
    padding: 7px 7px;
    font-weight: 800;
    font-size: 11px;
    border-top: 2px solid #1a1a1a;
    color: #1a1a1a;
}
.items-table tfoot td.tc { text-align: center; }
.items-table tfoot td.tr { text-align: right; }
.items-table tfoot .foot-label { text-align: right; color: #555; font-weight: 600; font-size: 10px; }

/* ══════════════════════════════════════════════
   NOTES
═══════════════════════════════════════════════ */
.notes-block {
    border: 1px solid #ddd;
    border-left: 3px solid #1a1a1a;
    padding: 7px 10px;
    margin-bottom: 14px;
    border-radius: 0 3px 3px 0;
}
.notes-label {
    font-size: 8.5px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .5px;
    color: #888;
    margin-bottom: 4px;
}
.notes-text { font-size: 10.5px; color: #333; line-height: 1.5; }

/* ══════════════════════════════════════════════
   SIGNATURE SECTION
═══════════════════════════════════════════════ */
.sig-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
    margin-top: 14px;
}
.sig-box {
    border: 1px solid #ccc;
    border-radius: 3px;
    padding: 8px 10px;
    min-height: 80px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}
.sig-title {
    font-size: 8.5px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .5px;
    color: #888;
    margin-bottom: 2px;
}
.sig-org { font-size: 10px; color: #1a1a1a; font-weight: 600; }
.sig-line {
    border-top: 1px solid #aaa;
    padding-top: 4px;
    margin-top: 10px;
    font-size: 9.5px;
    color: #555;
}

/* ══════════════════════════════════════════════
   FOOTER
═══════════════════════════════════════════════ */
.doc-footer {
    margin-top: 16px;
    padding-top: 7px;
    border-top: 1px solid #ddd;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.doc-footer-note { font-size: 8.5px; color: #999; }
.doc-footer-gen  { font-size: 8px; color: #bbb; }

/* ══════════════════════════════════════════════
   SCREEN-ONLY CONTROLS
═══════════════════════════════════════════════ */
.screen-controls {
    display: flex;
    justify-content: center;
    gap: 10px;
    margin: 20px auto 10px;
    max-width: 210mm;
}
.ctrl-btn {
    padding: 9px 26px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 13px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 7px;
    transition: opacity .15s;
}
.ctrl-btn:hover { opacity: .85; }
.ctrl-print { background: #1a1a1a; color: #fff; }
.ctrl-close { background: #6c757d; color: #fff; }

/* ══════════════════════════════════════════════
   PRINT OVERRIDES
═══════════════════════════════════════════════ */
@media print {
    body { background: #fff; padding: 0; }
    .page { box-shadow: none; padding: 10mm 12mm; width: 100%; min-height: unset; }
    .screen-controls { display: none !important; }
    .items-table thead tr     { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .items-table tbody tr:nth-child(even) td { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .items-table tfoot tr     { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    @page { size: A4 portrait; margin: 0; }
}
</style>
</head>
<body>

<!-- ── Screen controls (hidden on print) ── -->
<div class="screen-controls">
    <button class="ctrl-btn ctrl-print" onclick="window.print()">
        &#128438; Print / Save PDF
    </button>
    <button class="ctrl-btn ctrl-close" onclick="window.close()">
        &#10005; Close
    </button>
</div>

<div class="page">

    <!-- ══ HEADER ════════════════════════════════════════════════ -->
    <div class="doc-header">
        <div class="org-block">
            <div class="org-name"><?php echo htmlspecialchars($org->OrgName ?? 'Organisation'); ?></div>
            <?php if (!empty($org->AddressLine1)): ?>
            <div class="org-meta"><?php echo htmlspecialchars($org->AddressLine1); ?><?php echo !empty($org->AddressLine2) ? ', ' . htmlspecialchars($org->AddressLine2) : ''; ?></div>
            <?php endif; ?>
            <?php if (!empty($org->City)): ?>
            <div class="org-meta"><?php echo htmlspecialchars(trim(($org->City ?? '') . (!empty($org->Pincode) ? ' – ' . $org->Pincode : ''))); ?><?php echo !empty($org->StateName) ? ', ' . htmlspecialchars($org->StateName) : ''; ?></div>
            <?php endif; ?>
            <?php if (!empty($org->GSTIN)): ?>
            <div class="org-meta"><strong>GSTIN:</strong> <?php echo htmlspecialchars($org->GSTIN); ?></div>
            <?php endif; ?>
            <?php if (!empty($org->MobileNumber)): ?>
            <div class="org-meta"><strong>Ph:</strong> <?php echo htmlspecialchars($org->MobileNumber); ?></div>
            <?php endif; ?>
        </div>
        <div class="doc-title-block">
            <div class="doc-title">Packing List</div>
            <?php if ($plNum !== '—'): ?>
            <div class="doc-pl-num"><?php echo $plNum; ?></div>
            <?php endif; ?>
            <div class="doc-pl-date">Date: <?php echo $plDate; ?></div>
        </div>
    </div>

    <!-- ══ DC REFERENCE STRIP ════════════════════════════════════ -->
    <div class="dc-strip">
        <div class="dc-strip-left">
            <div class="strip-label">Consignee (Ship To)</div>
            <div class="strip-value"><?php echo $customer; ?></div>
        </div>
        <div class="dc-strip-center">
            <div class="strip-label">Delivery Challan</div>
            <div class="strip-value"><?php echo $dcNum; ?></div>
            <div class="doc-pl-date" style="margin-top:3px;">Dispatch Date: <?php echo $dcDate; ?></div>
        </div>
        <div class="dc-strip-right">
            <div class="strip-label">Challan Type</div>
            <div><span class="challan-badge"><?php echo $challanType; ?></span></div>
        </div>
    </div>

    <!-- ══ TRANSPORT INFO STRIP ══════════════════════════════════ -->
    <div class="transport-strip">
        <div class="t-cell">
            <div class="t-label">Vehicle No.</div>
            <div class="t-value <?php echo empty($vehicleNo) ? 'empty' : ''; ?>">
                <?php echo $vehicleNo ?: 'Not specified'; ?>
            </div>
        </div>
        <div class="t-cell">
            <div class="t-label">LR Number</div>
            <div class="t-value <?php echo empty($lrNum) ? 'empty' : ''; ?>">
                <?php echo $lrNum ?: '—'; ?>
            </div>
        </div>
        <div class="t-cell">
            <div class="t-label">Transporter</div>
            <div class="t-value <?php echo empty($transporter) ? 'empty' : ''; ?>">
                <?php echo $transporter ?: '—'; ?>
            </div>
        </div>
        <div class="t-cell highlight">
            <div class="t-label">Total Pkgs</div>
            <div class="t-value"><?php echo $totalPkgs > 0 ? $totalPkgs : '—'; ?></div>
        </div>
        <div class="t-cell highlight">
            <div class="t-label">Net Weight</div>
            <div class="t-value"><?php echo $totalNet > 0 ? number_format($totalNet, 3) . ' kg' : '—'; ?></div>
        </div>
        <div class="t-cell highlight" style="border-right:none;">
            <div class="t-label">Gross Weight</div>
            <div class="t-value"><?php echo $totalGross > 0 ? number_format($totalGross, 3) . ' kg' : '—'; ?></div>
        </div>
    </div>

    <!-- ══ ITEMS TABLE ════════════════════════════════════════════ -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width:30px;" class="tc">#</th>
                <th>Description of Goods</th>
                <th style="width:68px;" class="tc">Qty / Unit</th>
                <th style="width:72px;">Pkg Kind</th>
                <th style="width:52px;" class="tc">Pkgs</th>
                <th style="width:72px;" class="tr">Net Wt (kg)</th>
                <th style="width:72px;" class="tr">Gross Wt (kg)</th>
                <th style="width:56px;" class="tr">CBM (m³)</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($items)): ?>
                <?php foreach ($items as $i => $item):
                    $qty     = (float) ($item->Quantity         ?? 0);
                    $pkgs    = (int)   ($item->NumberOfPackages ?? 0);
                    $netWt   = (float) ($item->NetWeight        ?? 0);
                    $grossWt = (float) ($item->GrossWeight      ?? 0);
                    $cbm     = (float) ($item->CBM              ?? 0);
                    $unit    = htmlspecialchars($item->PrimaryUnitName ?? '');
                    $pkgKind = htmlspecialchars($item->PackageKind    ?? '');
                ?>
                <tr>
                    <td class="tc" style="color:#999;"><?php echo $i + 1; ?></td>
                    <td>
                        <div class="prod-name"><?php echo htmlspecialchars($item->ProductName ?? ''); ?></div>
                        <?php if (!empty($item->PartNumber)): ?>
                        <div class="prod-part">Part#: <?php echo htmlspecialchars($item->PartNumber); ?></div>
                        <?php endif; ?>
                        <?php if (!empty($item->Description)): ?>
                        <div class="prod-part"><?php echo htmlspecialchars($item->Description); ?></div>
                        <?php endif; ?>
                    </td>
                    <td class="tc">
                        <strong><?php echo number_format($qty, 2); ?></strong>
                        <?php if ($unit): ?><br><span style="font-size:9px;color:#777;"><?php echo $unit; ?></span><?php endif; ?>
                    </td>
                    <td style="color:#444;"><?php echo $pkgKind ?: '<span style="color:#ccc;">—</span>'; ?></td>
                    <td class="tc"><strong><?php echo $pkgs > 0 ? $pkgs : '<span style="color:#ccc;">—</span>'; ?></strong></td>
                    <td class="tr"><?php echo $netWt > 0 ? number_format($netWt, 3) : '<span style="color:#ccc;">—</span>'; ?></td>
                    <td class="tr"><?php echo $grossWt > 0 ? number_format($grossWt, 3) : '<span style="color:#ccc;">—</span>'; ?></td>
                    <td class="tr"><?php echo $cbm > 0 ? number_format($cbm, 4) : '<span style="color:#ccc;">—</span>'; ?></td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8" style="text-align:center;padding:18px;color:#aaa;font-style:italic;">No items found</td>
                </tr>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2" class="foot-label">Totals</td>
                <td class="tc"><?php echo number_format($totalQty, 2); ?></td>
                <td></td>
                <td class="tc"><?php echo $totalPkgs > 0 ? $totalPkgs : '—'; ?></td>
                <td class="tr"><?php echo $totalNet > 0   ? number_format($totalNet, 3)   : '—'; ?></td>
                <td class="tr"><?php echo $totalGross > 0 ? number_format($totalGross, 3) : '—'; ?></td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    <!-- ══ NOTES ══════════════════════════════════════════════════ -->
    <?php if (!empty($notes)): ?>
    <div class="notes-block">
        <div class="notes-label">Notes / Special Instructions</div>
        <div class="notes-text"><?php echo nl2br(htmlspecialchars($notes)); ?></div>
    </div>
    <?php endif; ?>

    <!-- ══ SIGNATURES ════════════════════════════════════════════ -->
    <div class="sig-row">
        <div class="sig-box">
            <div>
                <div class="sig-title">Packed &amp; Dispatched By</div>
                <div class="sig-org"><?php echo htmlspecialchars($org->OrgName ?? ''); ?></div>
            </div>
            <div class="sig-line">Authorised Signatory &amp; Stamp</div>
        </div>
        <div class="sig-box">
            <div>
                <div class="sig-title">Received By (Consignee)</div>
                <div style="font-size:9.5px;color:#aaa;margin-top:2px;">Name &amp; Signature</div>
            </div>
            <div class="sig-line">Date of Receipt: ___________________</div>
        </div>
    </div>

    <!-- ══ FOOTER ═════════════════════════════════════════════════ -->
    <div class="doc-footer">
        <div class="doc-footer-note">
            This is a Packing List only &mdash; it does not constitute a Tax Invoice or e-Way Bill.
        </div>
        <div class="doc-footer-gen">Generated <?php echo date('d M Y, h:i A'); ?></div>
    </div>

</div><!-- /.page -->

<script>
window.addEventListener('load', function () {
    setTimeout(function () { window.print(); }, 350);
});
</script>

</body>
</html>
