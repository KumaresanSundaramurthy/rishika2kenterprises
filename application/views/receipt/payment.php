<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Payment Receipt<?php echo !empty($payment->UniqueNumber) ? ' — ' . htmlspecialchars($payment->UniqueNumber) : ''; ?></title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family: Arial, Helvetica, sans-serif; background:#f0f2f5; color:#222; font-size:14px; }
.page { max-width:560px; margin:32px auto; background:#fff; border-radius:12px; box-shadow:0 4px 24px rgba(0,0,0,.10); overflow:hidden; }

/* Header */
.receipt-header { background:#1a3c6e; color:#fff; padding:24px 28px 20px; }
.receipt-header .org-name { font-size:20px; font-weight:700; }
.receipt-header .org-sub  { font-size:12px; opacity:.8; margin-top:3px; }
.receipt-header .doc-info  { text-align:right; }
.receipt-header .doc-type  { font-size:18px; font-weight:700; }
.receipt-header .doc-num   { font-size:13px; opacity:.85; margin-top:2px; }
.receipt-header .doc-date  { font-size:12px; opacity:.7; margin-top:1px; }
.header-row { display:flex; justify-content:space-between; align-items:flex-start; }

/* Amount box */
.amount-box { background:#f59e0b; color:#fff; text-align:center; padding:20px; }
.amount-label { font-size:11px; text-transform:uppercase; letter-spacing:.6px; opacity:.9; }
.amount-val   { font-size:32px; font-weight:800; margin-top:4px; }

/* Info sections */
.section { padding:18px 28px; border-bottom:1px solid #f0f0f0; }
.section:last-child { border-bottom:none; }
.section-title { font-size:10px; text-transform:uppercase; color:#888; letter-spacing:.5px; margin-bottom:10px; font-weight:600; }
.info-row { display:flex; justify-content:space-between; margin-bottom:6px; font-size:13px; }
.info-key { color:#666; }
.info-val { font-weight:600; text-align:right; max-width:60%; }
.mode-badge {
    display:inline-block;
    background:#1a3c6e22;
    color:#1a3c6e;
    border:1px solid #1a3c6e44;
    border-radius:4px;
    padding:2px 10px;
    font-size:12px;
    font-weight:600;
}

/* Linked doc note */
.linked-note { background:#f8f9ff; border-left:3px solid #1a3c6e; padding:10px 14px; font-size:12px; color:#444; font-style:italic; margin:0 28px 0; border-radius:0 4px 4px 0; }

/* Footer */
.receipt-footer { text-align:center; padding:18px 28px; font-size:12px; color:#999; }
.receipt-footer .brand { font-size:11px; margin-top:6px; color:#bbb; }

/* Logo */
.org-logo { max-height:48px; max-width:100px; object-fit:contain; margin-bottom:8px; display:block; }

@media (max-width:600px) {
    .page { margin:0; border-radius:0; box-shadow:none; }
    .receipt-header { padding:18px 18px 16px; }
    .section { padding:14px 18px; }
    .linked-note { margin:0 18px 0; }
    .receipt-footer { padding:14px 18px; }
    .amount-val { font-size:26px; }
}
@media print {
    body { background:#fff; }
    .page { box-shadow:none; border-radius:0; margin:0; max-width:100%; }
    .no-print { display:none; }
}
</style>
</head>
<body>

<?php
$e      = fn($v) => htmlspecialchars((string)($v ?? ''), ENT_QUOTES);
$fmt    = function($d) { if (!$d) return '—'; $dt = date_create($d); return $dt ? date_format($dt, 'd M Y') : $d; };
$cur    = '₹';
$fmtAmt = fn($v) => $cur . ' ' . number_format((float)$v, 2, '.', ',');

$direction  = ($payment->PartyType === 'C') ? 'Payment Received' : 'Payment Made';
$partyLabel = ($payment->PartyType === 'C') ? 'Customer' : 'Vendor';
$orgAddr    = implode(', ', array_filter([
    $org->Line1 ?? '', $org->Line2 ?? '',
    $org->CityText ?? '', $org->StateText ?? '', $org->Pincode ?? ''
]));
?>

<div class="page">

    <!-- Header -->
    <div class="receipt-header">
        <div class="header-row">
            <div>
                <?php if (!empty($org->Logo)): ?>
                <img src="<?php echo $e($org->Logo); ?>" class="org-logo" alt="Logo">
                <?php endif; ?>
                <div class="org-name"><?php echo $e($org->BrandName ?? $org->Name ?? ''); ?></div>
                <?php if ($orgAddr): ?>
                <div class="org-sub"><?php echo $e($orgAddr); ?></div>
                <?php endif; ?>
                <?php if (!empty($org->MobileNumber)): ?>
                <div class="org-sub">Ph: <?php echo $e($org->MobileNumber); ?></div>
                <?php endif; ?>
                <?php if (!empty($org->GSTIN)): ?>
                <div class="org-sub">GSTIN: <?php echo $e($org->GSTIN); ?></div>
                <?php endif; ?>
            </div>
            <div class="doc-info">
                <div class="doc-type"><?php echo $e($direction); ?></div>
                <div class="doc-num"><?php echo $e($payment->UniqueNumber ?? ('PMT-' . $payment->PaymentUID)); ?></div>
                <div class="doc-date"><?php echo $fmt($payment->PaymentDate ?? $payment->CreatedOn); ?></div>
            </div>
        </div>
    </div>

    <!-- Amount -->
    <div class="amount-box">
        <div class="amount-label">Amount <?php echo $payment->PartyType === 'C' ? 'Received' : 'Paid'; ?></div>
        <div class="amount-val"><?php echo $fmtAmt($payment->Amount); ?></div>
    </div>

    <!-- Party Details -->
    <div class="section">
        <div class="section-title"><?php echo $e($partyLabel); ?> Details</div>
        <div class="info-row">
            <span class="info-key"><?php echo $e($partyLabel); ?></span>
            <span class="info-val"><?php echo $e($payment->PartyName ?? '—'); ?></span>
        </div>
        <?php if (!empty($payment->PartyMobile)): ?>
        <div class="info-row">
            <span class="info-key">Mobile</span>
            <span class="info-val"><?php echo $e($payment->PartyMobile); ?></span>
        </div>
        <?php endif; ?>
        <?php if (!empty($payment->TransNumber)): ?>
        <div class="info-row">
            <span class="info-key">Linked Document</span>
            <span class="info-val"><?php echo $e($payment->TransNumber); ?></span>
        </div>
        <?php endif; ?>
        <?php if (!empty($payment->BillAmount)): ?>
        <div class="info-row">
            <span class="info-key">Bill Amount</span>
            <span class="info-val"><?php echo $fmtAmt($payment->BillAmount); ?></span>
        </div>
        <?php endif; ?>
    </div>

    <!-- Payment Details -->
    <div class="section">
        <div class="section-title">Payment Details</div>
        <div class="info-row">
            <span class="info-key">Mode</span>
            <span class="info-val"><span class="mode-badge"><?php echo $e($payment->PaymentTypeName ?? '—'); ?></span></span>
        </div>
        <?php if (!$payment->IsCash && !empty($payment->BankName)): ?>
        <div class="info-row">
            <span class="info-key">Bank</span>
            <span class="info-val"><?php echo $e($payment->BankName); ?><?php echo !empty($payment->AccountName) ? ' (' . $e($payment->AccountName) . ')' : ''; ?></span>
        </div>
        <?php if (!empty($payment->AccountNumber)): ?>
        <div class="info-row">
            <span class="info-key">A/C No</span>
            <span class="info-val"><?php echo $e($payment->AccountNumber); ?></span>
        </div>
        <?php endif; ?>
        <?php if (!empty($payment->IFSC)): ?>
        <div class="info-row">
            <span class="info-key">IFSC</span>
            <span class="info-val"><?php echo $e($payment->IFSC); ?></span>
        </div>
        <?php endif; ?>
        <?php endif; ?>
        <?php if (!empty($payment->ReferenceNo)): ?>
        <div class="info-row">
            <span class="info-key">Reference</span>
            <span class="info-val"><?php echo $e($payment->ReferenceNo); ?></span>
        </div>
        <?php endif; ?>
        <div class="info-row">
            <span class="info-key">Recorded By</span>
            <span class="info-val"><?php echo $e($payment->CreatedByName ?? '—'); ?></span>
        </div>
    </div>

    <!-- Linked doc note -->
    <?php if (!empty($payment->TransNumber)): ?>
    <div style="padding:0 0 16px;">
        <div class="linked-note">
            Amount received against the linked document as &quot;<?php echo $e($payment->TransNumber); ?>&quot;.
        </div>
    </div>
    <?php else: ?>
    <div style="padding:0 0 16px;">
        <div class="linked-note">
            Advance amount received without any linked document reference.
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($payment->Notes)): ?>
    <div class="section" style="padding-top:0;">
        <div class="info-row">
            <span class="info-key">Notes</span>
            <span class="info-val"><?php echo $e($payment->Notes); ?></span>
        </div>
    </div>
    <?php endif; ?>

    <!-- Footer -->
    <div class="receipt-footer">
        <div>Thank you for your business!</div>
        <div class="brand">This is a computer-generated receipt.</div>
    </div>

</div>

</body>
</html>
