<?php defined('BASEPATH') or exit('No direct script access allowed');
/**
 * Vendor Profile Modal — Statement Tab (Payables Ledger)
 *
 * @var object      $Vend
 * @var array       $Statement  rows from getVendorStatementData()
 * @var object|null $OpeningBal
 * @var string      $FromDate
 * @var string      $ToDate
 * @var object      $JwtData
 * @var string      $Cur
 * @var int         $Dec
 * @var string      $DateFormat
 * @var int         $VendorUID
 * @var object|null $OrgInfo
 */

$obVal    = (float) ($OpeningBal?->OpeningBalance ?? 0);
$obType   = $OpeningBal?->OpeningBalType ?? 'Credit';
// For vendors: Credit balance = we owe them (positive = payable). Debit = advance paid.
$obSigned = ($obType === 'Credit') ? $obVal : -$obVal;

$runningBalance = $obSigned;
$stmtRows = [];
foreach ($Statement as $row) {
    $debit          = (float) ($row['Debit']  ?? 0);
    $credit         = (float) ($row['Credit'] ?? 0);
    $runningBalance += $credit - $debit;   // credit (purchase) increases payable; debit (payment/return) decreases it
    $stmtRows[]     = array_merge($row, ['RunningBalance' => $runningBalance]);
}
$closingBalance = $runningBalance;

$org         = $OrgInfo ?? null;
$_orgName    = htmlspecialchars($org->Name     ?? '');
$_orgBrand   = htmlspecialchars($org->BrandName ?? $org->Name ?? '');
$_orgLogo    = $org->Logo  ?? '';
$_orgGSTIN   = htmlspecialchars($org->GSTIN    ?? '');
$_addrParts  = array_filter([
    $org->Line1    ?? '',
    $org->Line2    ?? '',
    $org->CityText ?? '',
    $org->StateText ? ($org->StateText . ($org->Pincode ? ' - ' . $org->Pincode : '')) : ($org->Pincode ?? ''),
]);
$_orgAddr    = htmlspecialchars(implode(', ', $_addrParts));

$_fromDisp   = !empty($FromDate) ? date($DateFormat, strtotime($FromDate)) : '';
$_toDisp     = !empty($ToDate)   ? date($DateFormat, strtotime($ToDate))   : '';

$_vendName   = htmlspecialchars($Vend->Name ?? '');
$_vendMobile = htmlspecialchars((!empty($Vend->CountryCode) ? $Vend->CountryCode . ' ' : '') . ($Vend->MobileNumber ?? ''));
$_vendEmail  = htmlspecialchars($Vend->EmailAddress ?? '');
$_vendArea   = htmlspecialchars($Vend->Area ?? '');
?>

<!-- ── Date Range Controls ────────────────────────────────────────────────── -->
<div class="px-3 pt-3 d-flex align-items-end gap-2 flex-wrap" id="vpStmtControls">
    <div>
        <label class="form-label mb-1 cp-stmt-label">From Date</label>
        <input type="text" id="vpStmtFrom" class="form-control form-control-sm cp-stmt-input"
               placeholder="Select date" readonly>
    </div>
    <div>
        <label class="form-label mb-1 cp-stmt-label">To Date</label>
        <input type="text" id="vpStmtTo" class="form-control form-control-sm cp-stmt-input"
               placeholder="Select date" readonly>
    </div>
    <button type="button" class="btn btn-sm btn-warning cp-stmt-btn" id="vpStmtApplyBtn">
        <i class="bx bx-filter-alt me-1"></i>Apply
    </button>
    <button type="button" class="btn btn-sm btn-outline-secondary ms-auto cp-stmt-btn" id="vpStmtPrintBtn">
        <i class="bx bx-printer me-1"></i>Print
    </button>
</div>

<!-- ── Printable Statement Document ──────────────────────────────────────── -->
<div class="p-3" id="vpStmtPrintArea">

    <!-- Letterhead header -->
    <div class="stmt-header">
        <div class="stmt-header-left">
            <?php if (!empty($_orgLogo)): ?>
            <img src="<?php echo $_orgLogo; ?>" class="stmt-logo" alt="Logo">
            <?php endif; ?>
            <div class="stmt-org-name"><?php echo $_orgBrand ?: $_orgName; ?></div>
            <?php if ($_orgAddr): ?>
            <div class="stmt-org-addr"><?php echo $_orgAddr; ?></div>
            <?php endif; ?>
            <?php if ($_orgGSTIN): ?>
            <div class="stmt-org-gstin">GSTIN: <?php echo $_orgGSTIN; ?></div>
            <?php endif; ?>
        </div>
        <div class="stmt-header-right">
            <div class="stmt-title">Payables Statement</div>
            <div class="stmt-period"><?php echo $_fromDisp; ?> &ndash; <?php echo $_toDisp; ?></div>
        </div>
    </div>

    <hr class="stmt-divider">

    <!-- Vendor info -->
    <div class="stmt-bill-to">
        <div class="stmt-bill-label">Vendor</div>
        <div class="stmt-cust-name"><?php echo $_vendName; ?></div>
        <?php if ($_vendArea): ?>
        <div class="stmt-cust-meta"><?php echo $_vendArea; ?></div>
        <?php endif; ?>
        <?php if ($_vendMobile): ?>
        <div class="stmt-cust-meta"><?php echo $_vendMobile; ?></div>
        <?php endif; ?>
        <?php if ($_vendEmail): ?>
        <div class="stmt-cust-meta"><?php echo $_vendEmail; ?></div>
        <?php endif; ?>
    </div>

    <!-- Statement Table -->
    <div class="table-responsive">
        <table class="table table-sm table-hover table-bordered mb-0 cp-stmt-table">
            <thead class="table-light">
                <tr>
                    <th class="px-2">Date</th>
                    <th>Type</th>
                    <th>Ref No</th>
                    <th class="text-end">Payment/Return (<?php echo $Cur; ?>)</th>
                    <th class="text-end">Purchase (<?php echo $Cur; ?>)</th>
                    <th class="text-end">Balance (<?php echo $Cur; ?>)</th>
                </tr>
            </thead>
            <tbody>
                <!-- Opening Balance Row -->
                <tr class="table-light">
                    <td colspan="5" class="px-2 fw-semibold">Opening Balance</td>
                    <td class="text-end fw-semibold <?php echo $obSigned >= 0 ? 'text-danger' : 'text-success'; ?>">
                        <?php echo number_format(abs($obSigned), $Dec); ?>
                        <span class="badge bg-label-secondary ms-1 cp-stmt-badge-dr">
                            <?php echo $obSigned >= 0 ? 'Payable' : 'Advance'; ?>
                        </span>
                    </td>
                </tr>

                <?php if (empty($stmtRows)): ?>
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">No transactions in this period.</td>
                </tr>
                <?php else: ?>
                <?php foreach ($stmtRows as $row):
                    $debit       = (float) ($row['Debit']  ?? 0);
                    $credit      = (float) ($row['Credit'] ?? 0);
                    $bal         = (float) $row['RunningBalance'];
                    $typeLabels  = ['Purchase' => 'Purchase', 'Payment' => 'Payment', 'Return' => 'Return'];
                    $typeBadges  = ['Purchase' => 'danger',   'Payment' => 'success', 'Return' => 'primary'];
                    $type        = $row['TxType'] ?? '—';
                ?>
                <tr>
                    <td class="px-2">
                        <?php echo !empty($row['TxDate']) ? date($DateFormat, strtotime($row['TxDate'])) : '—'; ?>
                    </td>
                    <td>
                        <span class="badge bg-label-<?php echo $typeBadges[$type] ?? 'secondary'; ?>">
                            <?php echo htmlspecialchars($typeLabels[$type] ?? $type); ?>
                        </span>
                    </td>
                    <td class="fw-semibold"><?php echo htmlspecialchars($row['RefNo'] ?? '—'); ?></td>
                    <td class="text-end <?php echo $debit > 0 ? 'text-success' : 'text-muted'; ?>">
                        <?php echo $debit > 0 ? number_format($debit, $Dec) : '—'; ?>
                    </td>
                    <td class="text-end <?php echo $credit > 0 ? 'text-danger' : 'text-muted'; ?>">
                        <?php echo $credit > 0 ? number_format($credit, $Dec) : '—'; ?>
                    </td>
                    <td class="text-end fw-semibold <?php echo $bal >= 0 ? 'text-danger' : 'text-success'; ?>">
                        <?php echo number_format(abs($bal), $Dec); ?>
                        <span class="text-muted cp-stmt-badge-bal"><?php echo $bal >= 0 ? 'Payable' : 'Advance'; ?></span>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>

                <!-- Closing Balance Row -->
                <tr class="table-active fw-bold">
                    <td colspan="5" class="px-2">Closing Balance</td>
                    <td class="text-end <?php echo $closingBalance >= 0 ? 'text-danger' : 'text-success'; ?>">
                        <?php echo number_format(abs($closingBalance), $Dec); ?>
                        <span class="badge ms-1 cp-stmt-badge-dr <?php echo $closingBalance >= 0 ? 'bg-danger' : 'bg-success'; ?>">
                            <?php echo $closingBalance >= 0 ? 'To Pay' : 'Advance'; ?>
                        </span>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<script>
(function () {
    var vendUID = <?php echo (int)$VendorUID; ?>;
    var _altFmt = (typeof _transFormDateFormat !== 'undefined' && _transFormDateFormat)
                  ? _transFormDateFormat : 'd-m-Y';

    // ── Flatpickr date pickers ─────────────────────────────────────────────
    flatpickr('#vpStmtFrom', {
        dateFormat  : 'Y-m-d',
        altInput    : true,
        altFormat   : _altFmt,
        defaultDate : '<?php echo addslashes($FromDate); ?>',
        static      : true,
        position    : 'below left',
        allowInput  : false
    });
    flatpickr('#vpStmtTo', {
        dateFormat  : 'Y-m-d',
        altInput    : true,
        altFormat   : _altFmt,
        defaultDate : '<?php echo addslashes($ToDate); ?>',
        static      : true,
        position    : 'below left',
        allowInput  : false
    });

    // ── Apply button ───────────────────────────────────────────────────────
    document.getElementById('vpStmtApplyBtn').addEventListener('click', function () {
        var from = document.getElementById('vpStmtFrom').value;
        var to   = document.getElementById('vpStmtTo').value;
        if (!from || !to) return;
        var $body = $('#vpTabContent_statement');
        $body.html('<div class="d-flex justify-content-center align-items-center py-5"><div class="spinner-border text-warning" role="status"></div></div>');
        ajaxLoading(0);
        $.ajax({
            url  : '/vendors/getVendorStatementTab',
            type : 'POST',
            data : {
                VendorUID : vendUID,
                FromDate  : from,
                ToDate    : to,
                <?php echo $this->security->get_csrf_token_name(); ?> : '<?php echo $this->security->get_csrf_hash(); ?>'
            },
            success : function (res) {
                if (res && !res.Error) {
                    $body.html(res.Html);
                } else {
                    $body.html('<div class="alert alert-danger m-4">' + (res && res.Message ? res.Message : 'Error loading statement.') + '</div>');
                }
            },
            error   : function () {
                $body.html('<div class="alert alert-danger m-4">An error occurred.</div>');
            },
            complete: function () { ajaxLoading(1); }
        });
    });

    // ── Print button ───────────────────────────────────────────────────────
    document.getElementById('vpStmtPrintBtn').addEventListener('click', function () {
        var area = document.getElementById('vpStmtPrintArea');
        if (!area) return;
        var win = window.open('', '_blank', 'width=900,height=700');
        win.document.write('<!DOCTYPE html><html lang="en"><head>');
        win.document.write('<meta charset="UTF-8">');
        win.document.write('<title><?php echo addslashes($_orgBrand ?: $_orgName); ?> &mdash; Payables Statement</title>');
        win.document.write('<style>');
        win.document.write('*{box-sizing:border-box;margin:0;padding:0}');
        win.document.write('body{font-family:Arial,sans-serif;font-size:13px;color:#222;padding:32px}');
        win.document.write('.stmt-header{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:16px;gap:12px}');
        win.document.write('.stmt-header-left{flex:1}.stmt-logo{max-height:56px;max-width:150px;object-fit:contain;display:block;margin-bottom:7px}');
        win.document.write('.stmt-org-name{font-size:15px;font-weight:700;color:#333;margin-bottom:3px}');
        win.document.write('.stmt-org-addr,.stmt-org-gstin{font-size:11px;color:#666;margin-top:2px}');
        win.document.write('.stmt-header-right{text-align:right;flex-shrink:0}');
        win.document.write('.stmt-title{font-size:18px;font-weight:700;color:#444}');
        win.document.write('.stmt-period{font-size:12px;color:#555;margin-top:4px}');
        win.document.write('.stmt-divider{border:none;border-top:1.5px solid #ddd;margin:16px 0}');
        win.document.write('.stmt-bill-to{background:#f7f7f9;border-radius:6px;padding:12px 16px;margin-bottom:20px;display:inline-block;min-width:220px}');
        win.document.write('.stmt-bill-label{font-size:10px;text-transform:uppercase;letter-spacing:.05em;color:#888;margin-bottom:4px}');
        win.document.write('.stmt-cust-name{font-size:14px;font-weight:700;color:#333}');
        win.document.write('.stmt-cust-meta{font-size:11px;color:#555;margin-top:2px}');
        win.document.write('table{width:100%;border-collapse:collapse;font-size:12px;margin-top:8px}');
        win.document.write('th{background:#f0f0f4;padding:7px 8px;border:1px solid #ddd;font-weight:600;font-size:11px}');
        win.document.write('td{padding:6px 8px;border:1px solid #eee;vertical-align:middle}');
        win.document.write('.text-end{text-align:right}.fw-semibold{font-weight:600}.fw-bold{font-weight:700}');
        win.document.write('.text-success{color:#28a745}.text-danger{color:#dc3545}.text-muted{color:#888}');
        win.document.write('.px-2{padding-left:8px}.table-responsive{overflow:auto}');
        win.document.write('.table-light td,.table-light th{background:#f8f8f8}');
        win.document.write('.table-active td{background:#e9ecef;font-weight:700}');
        win.document.write('.badge{display:inline-block;padding:2px 6px;border-radius:4px;font-size:10px;font-weight:600}');
        win.document.write('.bg-label-danger{background:#fff0f0;color:#dc3545}.bg-label-success{background:#e0f5ea;color:#28a745}');
        win.document.write('.bg-label-primary{background:#e7e7ff;color:#5c5cff}.bg-label-secondary{background:#f0f0f0;color:#666}');
        win.document.write('.bg-success{background:#28a745;color:#fff}.bg-danger{background:#dc3545;color:#fff}');
        win.document.write('.ms-1{margin-left:4px}.cp-stmt-badge-dr,.cp-stmt-badge-bal{font-size:10px}');
        win.document.write('.py-4{padding:24px 0}.table-responsive table td.text-center{text-align:center}');
        win.document.write('@media print{body{padding:18px}}');
        win.document.write('</style></head><body>');
        win.document.write(area.innerHTML);
        win.document.write('</body></html>');
        win.document.close();
        win.focus();
        setTimeout(function () { win.print(); }, 400);
    });
})();
</script>
