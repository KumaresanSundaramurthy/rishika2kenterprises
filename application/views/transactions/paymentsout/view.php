<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php $this->load->view('common/header'); ?>

<div class="layout-wrapper layout-horizontal layout-content-navbar">
    <div class="layout-container">

        <?php $this->load->view('common/menu_view'); ?>

        <div class="layout-page">
            <div class="content-wrapper">
                <div class="container-xxl flex-grow-1 container-p-y">

                    <?php
                    $cur       = htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹');
                    $dec       = (int)($JwtData->GenSettings->DecimalPoints ?? 2);
                    $totals    = $Totals ?? (object)['TotalReceived' => 0, 'TotalPaid' => 0];
                    $totalPaid = (float)($totals->TotalPaid ?? 0);
                    $summary      = $MethodSummary ?? [];
                    $bankAccounts = $BankAccounts ?? [];

                    function poutFmt($val, $sym) {
                        return $sym . ' ' . number_format((float)$val, 2, '.', ',');
                    }
                    ?>

                    <!-- ── Balance Summary Cards ──────────────────────────── -->
                    <div class="d-flex gap-3 mb-4 flex-wrap" id="poutSummaryCards">

                        <?php if (!empty($summary)): ?>
                            <?php foreach ($summary as $row): ?>
                            <?php
                                $balance  = (float)($row->TotalPaid ?? 0);
                                $label    = htmlspecialchars($row->AccountLabel ?? 'Cash');
                                $bankName = htmlspecialchars($row->BankName ?? '');
                            ?>
                            <div class="card border-0 shadow-sm pout-summary-card" style="min-width:200px;flex:1 1 180px;max-width:260px;">
                                <div class="card-body py-3 px-3">
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <?php if ($row->IsCash): ?>
                                            <i class="bx bx-money fs-5 text-success"></i>
                                        <?php else: ?>
                                            <i class="bx bx-building-house fs-5 text-primary"></i>
                                        <?php endif; ?>
                                        <span class="fw-semibold" style="font-size:.82rem;"><?php echo $label; ?></span>
                                        <i class="bx bx-down-arrow-alt text-danger ms-auto fs-5"></i>
                                    </div>
                                    <?php if ($bankName): ?>
                                        <div class="text-muted" style="font-size:.72rem;"><?php echo $bankName; ?></div>
                                    <?php endif; ?>
                                    <div class="fw-bold mt-1 text-danger" style="font-size:1.05rem;">
                                        <?php echo poutFmt($balance, $cur); ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <!-- No payments yet — show all bank accounts with ₹0 -->
                            <!-- Cash card -->
                            <div class="card border-0 shadow-sm pout-summary-card" style="min-width:200px;flex:1 1 180px;max-width:260px;">
                                <div class="card-body py-3 px-3">
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <i class="bx bx-money fs-5 text-success"></i>
                                        <span class="fw-semibold" style="font-size:.82rem;">Cash</span>
                                        <i class="bx bx-minus text-muted ms-auto fs-5"></i>
                                    </div>
                                    <div class="fw-bold mt-1 text-muted" style="font-size:1.05rem;"><?php echo poutFmt(0, $cur); ?></div>
                                </div>
                            </div>
                            <?php foreach ($bankAccounts as $ba): ?>
                                <?php if ($ba->IsCash) continue; ?>
                                <div class="card border-0 shadow-sm pout-summary-card" style="min-width:200px;flex:1 1 180px;max-width:260px;">
                                    <div class="card-body py-3 px-3">
                                        <div class="d-flex align-items-center gap-2 mb-1">
                                            <i class="bx bx-building-house fs-5 text-primary"></i>
                                            <span class="fw-semibold" style="font-size:.82rem;"><?php echo htmlspecialchars($ba->AccountName); ?></span>
                                            <i class="bx bx-minus text-muted ms-auto fs-5"></i>
                                        </div>
                                        <div class="text-muted" style="font-size:.72rem;"><?php echo htmlspecialchars($ba->BankName); ?></div>
                                        <div class="fw-bold mt-1 text-muted" style="font-size:1.05rem;"><?php echo poutFmt(0, $cur); ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>

                    </div>

                    <!-- ── Main Card ──────────────────────────────────────── -->
                    <div class="card border-0 shadow-sm">

                        <!-- Toolbar -->
                        <div class="trans-toolbar d-flex align-items-center justify-content-between flex-wrap gap-2">

                            <!-- Tabs -->
                            <ul class="nav trans-status-tabs gap-1" id="poutStatusTabs">
                                <li class="nav-item">
                                    <a class="nav-link pout-tab active fw-semibold px-3 py-1" data-tab="Success" href="javascript:void(0);">
                                        Success <span class="badge bg-danger ms-1" id="poutTabCountSuccess"><?php echo number_format($ModAllCount); ?></span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link pout-tab fw-semibold px-3 py-1 text-muted" data-tab="Cancelled" href="javascript:void(0);">
                                        Cancelled <span class="badge bg-secondary ms-1" id="poutTabCountCancelled">0</span>
                                    </a>
                                </li>
                            </ul>

                            <!-- Right controls -->
                            <div class="d-flex align-items-center gap-2 flex-wrap py-2">
                                <div class="input-group input-group-sm" style="width:210px;">
                                    <span class="input-group-text bg-white border-end-0"><i class="bx bx-search text-muted"></i></span>
                                    <input type="text" class="form-control border-start-0" id="poutSearch"
                                           placeholder="Vendor, amount, ref…">
                                </div>
                                <div class="dropdown">
                                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button"
                                            id="poutDateFilterBtn" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="bx bx-calendar me-1"></i><span id="poutDateFilterLabel">All Dates</span>
                                    </button>
                                    <ul class="dropdown-menu shadow" id="poutDateFilterMenu"
                                        style="width:220px;max-height:360px;overflow-y:auto;font-size:.82rem;"></ul>
                                </div>
                            </div>
                        </div>

                        <!-- Table -->
                        <div class="table-responsive">
                            <table class="table trans-table table-hover MainviewTable mb-0" id="poutTable">
                                <thead class="r2k-thead">
                                    <tr>
                                        <th class="ps-3" style="width:140px;">Ref No</th>
                                        <th class="ps-3" style="width:140px;">Amount Paid</th>
                                        <th style="width:150px;">Mode / Bank</th>
                                        <th style="width:140px;">Linked Bill</th>
                                        <th>Vendor</th>
                                        <th style="width:160px;">Recorded By</th>
                                        <th style="width:80px;" class="text-end pe-3">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="poutTableBody">
                                    <?php echo $ModRowData; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Footer -->
                        <div class="card-footer bg-white border-top d-flex flex-wrap justify-content-between align-items-center px-3 py-2 gap-3">
                            <div class="d-flex align-items-center gap-3">
                                <span class="text-muted" style="font-size:.8rem;">
                                    Total Paid Out: &nbsp;
                                    <strong class="text-danger" id="poutFooterTotal"><?php echo poutFmt($totalPaid, $cur); ?></strong>
                                </span>
                            </div>
                            <div id="poutPagination" class="d-flex align-items-center gap-3">
                                <span class="text-muted" style="font-size:.78rem;">
                                    Total: <strong id="poutTotalCount"><?php echo number_format($ModAllCount); ?></strong>
                                </span>
                                <?php echo $ModPagination; ?>
                            </div>
                        </div>

                    </div>

                </div>
                <?php $this->load->view('common/footer_desc'); ?>
            </div>
        </div>

        <?php $this->load->view('common/transactions/print_modals'); ?>

    </div>
</div>

<?php
$pdtTheme       = 'out';
$pdtPartyLabel  = 'Vendor';
$pdtLinkedLabel = 'Linked Bill';
$this->load->view('common/transactions/payment_modal');
?>

<?php $this->load->view('common/footer'); ?>

<link rel="stylesheet" href="/assets/vendor/css/transactions.css">
<link rel="stylesheet" href="/css/transactions-theme.css">
<script type="text/javascript" src="/js/common/datefilter.js"></script>
<script type="text/javascript" src="/js/transactions/attachments.js"></script>
<script type="text/javascript" src="/js/transactions/viewmodal.js"></script>
<script type="text/javascript" src="/js/transactions/a4_print.js"></script>
<script type="text/javascript" src="/js/transactions/thermal_print.js"></script>

<script>
$('#viewTransEditBtn').data('hide-edit', true);
var PoutFilter = {};
var PoutPageNo = 1;
var PoutLimit  = 10;

$(function () {
    'use strict';

    var sym = '<?php echo addslashes($cur); ?>';
    var dec = <?php echo $dec; ?>;
    var fmt = function (v) {
        return sym + ' ' + parseFloat(v || 0).toLocaleString('en-IN', { minimumFractionDigits: dec, maximumFractionDigits: dec });
    };

    function getPaymentsOut(pageNo) {
        pageNo     = pageNo || 1;
        PoutPageNo = pageNo;
        $.ajax({
            url   : '/paymentsout/getPageDetails/' + pageNo,
            method: 'POST',
            data  : { RowLimit: PoutLimit, Filter: PoutFilter, [CsrfName]: CsrfToken },
            success: function (resp) {
                if (!resp.Error) {
                    $('#poutTableBody').html(resp.RecordHtmlData);
                    $('#poutPagination').html(
                        '<span class="text-muted" style="font-size:.78rem;">Total: <strong>' +
                        Number(resp.TotalCount).toLocaleString() + '</strong></span>' +
                        (resp.Pagination || '')
                    );
                    if (resp.Totals) {
                        $('#poutFooterTotal').text(fmt(resp.Totals.TotalPaid || 0));
                    }
                }
            }
        });
    }

    // Tab switch
    $(document).on('click', '.pout-tab', function (e) {
        e.preventDefault();
        $('.pout-tab').removeClass('active');
        $(this).addClass('active');
        PoutFilter.Status = $(this).data('tab') === 'Cancelled' ? 'Cancelled' : '';
        getPaymentsOut(1);
    });

    // Pagination
    $(document).on('click', '#poutPagination .page-link', function (e) {
        e.preventDefault();
        var m = ($(this).attr('href') || '').match(/\/(\d+)$/);
        if (m) getPaymentsOut(parseInt(m[1]));
    });

    // Search
    var poutSearchTimer;
    $('#poutSearch').on('input', function () {
        clearTimeout(poutSearchTimer);
        var v = $.trim($(this).val());
        poutSearchTimer = setTimeout(function () {
            PoutFilter.Search = v;
            getPaymentsOut(1);
        }, 400);
    });

    // Date filter
    $('#poutDateFilterMenu').html(buildDateFilterHtml('poutCustomDateFrom', 'poutCustomDateTo'));
    initDateFilter({
        btnId  : 'poutDateFilterBtn',
        labelId: 'poutDateFilterLabel',
        fromId : 'poutCustomDateFrom',
        toId   : 'poutCustomDateTo',
        onApply: function (from, to) {
            PoutFilter.DateFrom = from;
            PoutFilter.DateTo   = to;
            getPaymentsOut(1);
        }
    });
    $(document).on('shown.bs.dropdown', '#poutDateFilterBtn', function () {
        if (!$('#poutCustomDateFrom').data('fpInit')) {
            flatpickr('#poutCustomDateFrom', { dateFormat: 'Y-m-d', altInput: true, altFormat: 'd M Y', maxDate: 'today', disableMobile: true });
            flatpickr('#poutCustomDateTo',   { dateFormat: 'Y-m-d', altInput: true, altFormat: 'd M Y', maxDate: 'today', disableMobile: true });
            $('#poutCustomDateFrom').data('fpInit', true);
        }
    });

    // ── Payment A4 Print ─────────────────────────────────────────────────────
    $(document).on('click', '.pmtA4Print', function () {
        var paymentUID = $(this).data('payment-uid');
        _pmtLoadPrintData(paymentUID, function (resp) {
            _a4Html  = resp.PrintHtml;
            _a4Title = resp.Payment.UniqueNumber || ('PMT-' + paymentUID);
            _a4DownloadUid       = paymentUID;
            _a4DownloadModuleUID = 0;
            $('#a4ModalTitle').text('Payment Receipt — ' + _a4Title);
            _a4SetLoading(false);
            _a4ShowPreview();
        });
        _a4Html = null;
        $('#a4PrintModal').modal('show');
        _a4SetLoading(true);
    });

    // ── Payment Download PDF (direct) ────────────────────────────────────────
    $(document).on('click', '.pmtDownloadPdf', function () {
        var paymentUID = $(this).data('payment-uid');
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = '/payments/downloadPaymentPdf';
        form.style.display = 'none';
        var fields = { PaymentUID: paymentUID, PaperSize: 'A4', [CsrfName]: CsrfToken };
        Object.keys(fields).forEach(function (k) {
            var inp = document.createElement('input');
            inp.type = 'hidden'; inp.name = k; inp.value = fields[k];
            form.appendChild(inp);
        });
        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
    });

    $('#a4DownloadBtn').off('click.pmt').on('click.pmt', function () {
        if (!_a4Html || !_a4DownloadUid) return;
        var size = $('input[name="a4PaperSize"]:checked').val() || 'A4';
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = '/payments/downloadPaymentPdf';
        form.style.display = 'none';
        var fields = { PaymentUID: _a4DownloadUid, PaperSize: size, [CsrfName]: CsrfToken };
        Object.keys(fields).forEach(function (k) {
            var inp = document.createElement('input');
            inp.type = 'hidden'; inp.name = k; inp.value = fields[k];
            form.appendChild(inp);
        });
        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
    });

    // ── Payment Thermal Print ────────────────────────────────────────────────
    $(document).on('click', '.pmtThermalPrint', function () {
        var paymentUID = $(this).data('payment-uid');
        $('#thermalPrintBtn').addClass('d-none');
        $('#thermalPrintBody').html('<div class="d-flex justify-content-center py-5"><div class="spinner-border text-primary"></div></div>');
        new bootstrap.Modal(document.getElementById('thermalPrintModal')).show();
        _pmtLoadPrintData(paymentUID, function (resp) {
            $('#thermalPrintBody').html(_buildPmtThermalHtml(resp, 0));
            $('#thermalPrintBtn').off('click.pmt').on('click.pmt', function () {
                var html = _buildPmtThermalHtml(resp, 1);
                var cfg  = resp.ThermalConfig || {};
                var pw   = cfg.PaperWidth || '80mm';
                var win  = window.open('', '_blank', 'width=400,height=600');
                win.document.write('<!DOCTYPE html><html><head><title>Payment Receipt</title><style>*{margin:0;padding:0;box-sizing:border-box;}body{font-family:Arial,sans-serif;font-size:12px;width:' + pw + ';padding:4px;}.tp-hr{border:none;border-top:1px dashed #000;margin:4px 0;}@media print{@page{margin:0;size:' + pw + ' auto;}body{width:' + pw + ';padding:0 4px 20px 4px;}}</style></head><body>' + html + '</body></html>');
                win.document.close();
                win.focus();
                win.addEventListener('afterprint', function () { win.close(); });
                setTimeout(function () { win.print(); }, 300);
            });
            $('#thermalPrintBtn').removeClass('d-none');
        });
    });

    // View payment detail — reads from data-* on the <tr>, zero AJAX
    $(document).on('click', '.viewPaymentDetail', function () {
        var $row = $(this).closest('tr.pmt-row');
        var fmt  = function (v) {
            return sym + ' ' + parseFloat(v || 0).toLocaleString('en-IN', { minimumFractionDigits: dec, maximumFractionDigits: dec });
        };

        // Banner
        $('#pdUniqueNumber').text($row.data('unique-number') || '—');
        var dateStr = ($row.data('payment-date') || '').toString().slice(0, 10);
        if (dateStr) {
            var p = dateStr.split('-'), mo = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
            dateStr = p[2] + ' ' + mo[parseInt(p[1], 10) - 1] + ' ' + p[0];
        }
        $('#pdDateLabel').text(dateStr || '—');
        $('#pdAmount').text(fmt($row.data('raw-amount')));

        // Mode badge
        var modeMap = { 'cash':'#e8f5e9|#2e7d32','upi':'#ede7f6|#4527a0','card':'#e3f2fd|#1565c0','net banking':'#fff8e1|#f57f17','cheque':'#fce4ec|#880e4f','emi':'#e0f7fa|#00695c','tds':'#f3e5f5|#6a1b9a' };
        var modeKey = ($row.data('payment-type') || '').toLowerCase().trim();
        var mc = modeMap[modeKey] ? modeMap[modeKey].split('|') : ['#f0f0f0','#555'];
        $('#pdModeBadge').html('<span class="pmt-mode-badge" style="background:' + mc[0] + ';color:' + mc[1] + ';">' + ($row.data('payment-type') || '—') + '</span>');

        // Party
        var mobile = $row.data('party-mobile') || '';
        $('#pdParty').text($row.data('party-name') || '—');
        $('#pdPartyMobile').text(mobile).toggle(!!mobile);
        $('#pdTransNumber').text($row.data('trans-number') || '—');

        // Bank
        var bankName = $row.data('bank-name') || '';
        if (bankName && !$row.data('is-cash')) {
            var acctName = $row.data('account-name') || '';
            $('#pdBankName').text(bankName + (acctName ? ' (' + acctName + ')' : ''));
            $('#pdAccountNumber').text($row.data('account-number') || '—');
            var ifsc = $row.data('ifsc') || '', branch = $row.data('branch') || '';
            $('#pdIfsc').text(ifsc);   $('#pdIfscWrap').toggle(!!ifsc);
            $('#pdBranch').text(branch); $('#pdBranchWrap').toggle(!!branch);
            $('#pdBankSection').show();
        } else {
            $('#pdBankSection').hide();
        }

        // Reference / By / Notes
        $('#pdReference').text($row.data('reference') || '—');
        $('#pdCreatedBy').text($row.data('created-by') || '—');
        var notes = $row.data('notes') || '';
        $('#pdNotes').text(notes);
        $('#pdNotesWrap').toggle(!!notes);

        $('#paymentDetailModal').modal('show');
    });

    // Delete payment
    $(document).on('click', '.deletePaymentOut', function () {
        var paymentUID = $(this).data('payment-uid');
        var amount     = $(this).data('amount');
        var $row       = $(this).closest('tr');
        Swal.fire({
            title: 'Delete Payment?',
            html : 'Delete payment of <strong>' + sym + ' ' + amount + '</strong>?<br>The purchase bill balance will be restored.',
            icon : 'warning', showCancelButton: true,
            confirmButtonText: 'Delete', confirmButtonColor: '#d33',
        }).then(function (r) {
            if (!r.isConfirmed) return;
            $.ajax({
                url   : '/payments/deletePayment',
                method: 'POST',
                data  : { PaymentUID: paymentUID, [CsrfName]: CsrfToken },
                success: function (resp) {
                    if (!resp.Error) {
                        $row.fadeOut(300, function () { $(this).remove(); });
                        Swal.fire({ icon: 'success', text: 'Payment deleted.', timer: 1500, showConfirmButton: false });
                        getPaymentsOut(PoutPageNo);
                    } else {
                        Swal.fire('Error', resp.Message, 'error');
                    }
                }
            });
        });
    });

});

// ── Shared payment print helpers ─────────────────────────────────────────────────────
function _pmtLoadPrintData(paymentUID, cb) {
    $.ajax({
        url   : '/payments/getPaymentPrintDetail',
        method: 'GET',
        data  : { PaymentUID: paymentUID },
        success: function (resp) {
            AjaxLoading = 1;
            if (resp.Error) { Swal.fire({ icon: 'error', text: resp.Message }); return; }
            cb(resp);
        },
        error: function () {
            AjaxLoading = 1;
            Swal.fire({ icon: 'error', text: 'Failed to load payment data.' });
        }
    });
    AjaxLoading = 0;
}

function _buildPmtThermalHtml(resp, forPrint) {
    var p   = resp.Payment       || {};
    var org = resp.OrgInfo       || {};
    var cfg = resp.ThermalConfig || {};
    var sym = (typeof genSettings !== 'undefined' && genSettings.CurrenySymbol) ? genSettings.CurrenySymbol : '₹';

    var fmtAmt  = function (v) { return sym + ' ' + parseFloat(v || 0).toFixed(2); };
    var fmtDate = function (v) {
        if (!v) return '—';
        var d = new Date(v);
        return isNaN(d) ? v : ('0' + d.getDate()).slice(-2) + '-' + ('0' + (d.getMonth() + 1)).slice(-2) + '-' + d.getFullYear();
    };

    // ── Same cfg flags as _buildThermalHtml ──────────────────────────────
    var showLogo   = cfg.ShowLogo             !== undefined ? parseInt(cfg.ShowLogo)           : 0;
    var showCo     = cfg.ShowCompanyDetails   !== undefined ? parseInt(cfg.ShowCompanyDetails) : 1;
    var showMobile = cfg.ShowMobile           !== undefined ? parseInt(cfg.ShowMobile)         : 1;
    var showGSTIN  = cfg.ShowGSTIN            !== undefined ? parseInt(cfg.ShowGSTIN)          : 1;

    var orgFontSize  = Math.max(10, Math.min(40, parseInt(cfg.OrgNameFontSize)     || 22));
    var coFontSize   = Math.max(8,  Math.min(40, parseInt(cfg.CompanyNameFontSize) || 18));
    var prodFontSize = Math.max(8,  Math.min(40, parseInt(cfg.ProductInfoFontSize) || 12));

    var line1  = org.BrandName || org.Name || '';
    var line3  = [org.Line1, org.Line2, org.CityText, org.StateText, org.Pincode].filter(Boolean).join(', ');
    var footer = cfg.FooterMessage || 'Thank you for your business!';

    var direction  = (p.PartyType === 'C') ? 'Payment Received' : 'Payment Made';
    var partyLabel = (p.PartyType === 'C') ? 'Customer'         : 'Vendor';

    var html = '';

    // Logo
    if (showLogo && org.Logo) {
        var logoUrl = (/^https?:\/\//i.test(org.Logo) ? '' : (typeof CDN_URL !== 'undefined' ? CDN_URL : '')) + org.Logo;
        html += '<div style="text-align:center;margin-bottom:4px;"><img src="' + _esc(logoUrl) + '" style="max-width:80px;max-height:60px;object-fit:contain;" alt="Logo" /></div>';
    }

    // Org name
    html += '<div style="text-align:center;font-weight:bold;font-size:' + orgFontSize + 'px;">' + _esc(line1) + '</div>';

    // Address / phone / GSTIN
    if (showCo) {
        if (line3)                          html += '<div style="text-align:center;font-size:' + coFontSize + 'px;">' + _esc(line3) + '</div>';
        if (showMobile && org.MobileNumber) html += '<div style="text-align:center;font-size:' + coFontSize + 'px;">Ph: ' + _esc(org.MobileNumber) + '</div>';
        if (showGSTIN  && org.GSTIN)        html += '<div style="text-align:center;font-size:' + coFontSize + 'px;">GSTIN: ' + _esc(org.GSTIN) + '</div>';
    }

    html += '<hr class="tp-hr my-1">';

    // Receipt header
    html += '<div style="font-size:' + prodFontSize + 'px;">';
    html += '<div style="display:flex;justify-content:space-between;"><span style="font-weight:bold;">' + _esc(direction) + ':</span><span style="font-weight:bold;">' + _esc(p.UniqueNumber || ('PMT-' + p.PaymentUID)) + '</span></div>';
    html += '<div style="display:flex;justify-content:space-between;"><span>Date:</span><span>' + fmtDate(p.PaymentDate || p.CreatedOn) + '</span></div>';
    html += '<div style="display:flex;justify-content:space-between;"><span>' + _esc(partyLabel) + ':</span><span style="text-align:right;max-width:60%;">' + _esc(p.PartyName || '—') + '</span></div>';
    if (p.PartyMobile) html += '<div style="display:flex;justify-content:space-between;"><span>Phone:</span><span>' + _esc(p.PartyMobile) + '</span></div>';
    html += '</div>';

    html += '<hr class="tp-hr my-1">';

    // Amount + payment details
    html += '<div style="display:flex;justify-content:space-between;font-weight:bold;font-size:' + (prodFontSize + 2) + 'px;border-bottom:1px solid #000;padding-bottom:3px;margin-bottom:3px;"><span>Amount:</span><span>' + fmtAmt(p.Amount) + '</span></div>';
    html += '<div style="font-size:' + prodFontSize + 'px;">';
    html += '<div style="display:flex;justify-content:space-between;"><span>Mode:</span><span>' + _esc(p.PaymentTypeName || '—') + '</span></div>';
    if (!p.IsCash && p.BankName) html += '<div style="display:flex;justify-content:space-between;"><span>Bank:</span><span>' + _esc(p.BankName) + '</span></div>';
    if (p.ReferenceNo)           html += '<div style="display:flex;justify-content:space-between;"><span>Ref:</span><span>' + _esc(p.ReferenceNo) + '</span></div>';
    if (p.TransNumber)           html += '<div style="display:flex;justify-content:space-between;"><span>Linked Doc:</span><span>' + _esc(p.TransNumber) + '</span></div>';
    html += '</div>';

    html += '<hr class="tp-hr my-1">';

    // Footer
    html += '<div style="text-align:center;font-size:' + prodFontSize + 'px;margin-top:6px;">' + _esc(footer) + '</div>';
    html += '<div style="margin-bottom:24px"></div>';

    return forPrint === 0
        ? '<div style="font-family:\'Courier New\',Courier,monospace;font-size:13px;padding:8px;max-width:580px;margin:0 auto;">' + html + '</div>'
        : html;
}
</script>
