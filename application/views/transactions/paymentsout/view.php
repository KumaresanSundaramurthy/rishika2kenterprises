<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php $this->load->view('common/header'); ?>

<div class="layout-wrapper layout-horizontal layout-content-navbar">
    <div class="layout-container">

        <?php $this->load->view('common/menu_view'); ?>

        <div class="layout-page">
            <div class="content-wrapper apex-content">
                <?php $this->load->view('common/apex/page_header', [
                    'pageIcon'        => 'bx-credit-card-front',
                    'pageIconBg'      => '#fee2e2',
                    'pageIconColor'   => '#ef4444',
                    'pageTitle'       => $PageTitle       ?? 'Payments Out',
                    'pageDescription' => $PageDescription ?? '',
                ]); ?>
                <div class="container-xxl flex-grow-1 py-3">

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
                    <!-- ── Balance Summary ─────────────────────────────────── -->
                    <div class="trans-stats-section mb-4">
                        <div class="d-flex gap-3 flex-wrap" id="poutSummaryCards">

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
                    </div>

                    <!-- ── Main Card ──────────────────────────────────────── -->
                    <div class="card border-0 shadow-sm">

                        <!-- Filter Row -->
                        <div class="apex-filter-row">
                            <div class="r2k-search-wrap">
                                <i class="bx bx-search r2k-si"></i>
                                <input type="text" id="poutSearch" placeholder="Vendor, amount, ref…">
                            </div>
                            <div class="dropdown">
                                <button class="apex-filter-btn dropdown-toggle" type="button" id="poutDateFilterBtn" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bx bx-calendar"></i><span id="poutDateFilterLabel" class="ms-1">All Dates</span>
                                </button>
                                <ul class="dropdown-menu shadow" id="poutDateFilterMenu" style="width:220px;max-height:360px;overflow-y:auto;font-size:.82rem;"></ul>
                            </div>
                            <div class="apex-filter-spacer"></div>
                        </div>

                        <!-- Tabs Row -->
                        <div class="apex-tabs-row">
                            <ul class="nav trans-status-tabs gap-1" id="poutStatusTabs">
                                <li class="nav-item"><a class="nav-link pout-tab active" data-tab="Success"   href="javascript:void(0);">Success   <span class="badge bg-danger    ms-1" id="poutTabCountSuccess"><?php echo number_format($ModAllCount); ?></span></a></li>
                                <li class="nav-item"><a class="nav-link pout-tab"        data-tab="Cancelled" href="javascript:void(0);">Cancelled <span class="badge bg-secondary ms-1" id="poutTabCountCancelled">0</span></a></li>
                            </ul>
                        </div>

                        <!-- Table -->
                        <div class="table-responsive">
                            <table class="table trans-table table-hover MainviewTable mb-0" id="poutTable">
                                <thead class="r2k-thead">
                                    <tr>
                                        <th class="ps-3" style="width:140px;">Ref No</th>
                                        <th class="ps-3" style="width:140px;">Amount Paid</th>
                                        <th style="width:150px;">
                                            Mode / Bank
                                            <a href="javascript:void(0);" id="poutPayModeFilter" class="text-body ms-1"
                                               data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Filter by Payment Mode"
                                               style="font-size:.85rem;">
                                                <i class="bx bx-filter-alt align-middle"></i>
                                            </a>
                                        </th>
                                        <th style="width:140px;">Linked Bill</th>
                                        <th>Vendor</th>
                                        <th style="width:160px;">
                                            Recorded By
                                            <?php if (count($OrgUsers ?? []) > 1): ?>
                                            <a href="javascript:void(0);" id="poutCreatedByFilter" class="text-body ms-1"
                                               data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Filter by User"
                                               style="font-size:.85rem;">
                                                <i class="bx bx-filter-alt align-middle"></i>
                                            </a>
                                            <?php endif; ?>
                                        </th>
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

<?php $this->load->view('common/transactions/col_filter_box', [
    'ColFilterConfig' => [
        'id'         => 'poutPayModeFilterBox',
        'triggerId'  => 'poutPayModeFilter',
        'title'      => 'Payment Mode',
        'icon'       => 'bx-credit-card',
        'filterKey'  => 'PaymentMode',
        'checkClass' => 'pout-pay-mode-chk',
        'items'      => array_map(function($t) {
            return ['value' => $t->Name, 'label' => $t->Name, 'icon' => 'bx-credit-card', 'color' => '#ef4444'];
        }, $PaymentTypes ?? []),
    ],
]); ?>

<?php if (count($OrgUsers ?? []) > 1): ?>
<?php $this->load->view('common/transactions/col_user_filter_box', [
    'ColUserFilterConfig' => [
        'id'         => 'poutCreatedByFilterBox',
        'triggerId'  => 'poutCreatedByFilter',
        'checkClass' => 'pout-user-chk',
        'OrgUsers'   => $OrgUsers ?? [],
    ],
]); ?>
<?php endif; ?>

<link rel="stylesheet" href="/assets/vendor/css/transactions.css">
<link rel="stylesheet" href="/css/transactions-theme.css">
<script type="text/javascript" src="/js/common/datefilter.js"></script>
<script src="/js/transactions/filter_bar.js"></script>
<script src="/js/transactions/col_filter.js"></script>
<script type="text/javascript" src="/js/transactions/attachments.js"></script>
<script type="text/javascript" src="/js/transactions/viewmodal.js"></script>
<script type="text/javascript" src="/js/transactions/a4_print.js"></script>
<script type="text/javascript" src="/js/transactions/thermal_print.js"></script>

<script>
$('#viewTransEditBtn').data('hide-edit', true);
var PoutFilter = {};
var PoutPageNo = 1;
var PoutLimit  = 10;

var poutPayModeFilter = new TransColFilter({
    boxId     : 'poutPayModeFilterBox',
    triggerId : 'poutPayModeFilter',
    filterKey : 'PaymentMode',
    onApply   : function () { getPaymentsOut(1); }
});

var poutCreatedByFilter = (document.getElementById('poutCreatedByFilterBox'))
    ? new TransColFilter({
        boxId     : 'poutCreatedByFilterBox',
        triggerId : 'poutCreatedByFilter',
        filterKey : 'UpdatedByUIDs',
        onApply   : function () { getPaymentsOut(1); }
    })
    : null;

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
        var f = $.extend({}, PoutFilter,
            poutPayModeFilter    ? poutPayModeFilter.getState()    : {},
            poutCreatedByFilter  ? poutCreatedByFilter.getState()  : {}
        );
        $.ajax({
            url   : '/paymentsout/getPageDetails/' + pageNo,
            method: 'POST',
            data  : { RowLimit: PoutLimit, Filter: f, [CsrfName]: CsrfToken },
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
        }, 1500);
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
        _pmtLoadPrintData(paymentUID, 'a4', function (resp) {
            if (!resp.PrintHtml) {
                $('#a4PrintModal').modal('hide');
                Swal.fire({ icon: 'warning', title: 'No Print Template', text: 'No print template is configured for Payments. Please set one up in Settings > Print Themes.' });
                return;
            }
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

    // ── Payment Thermal Print — handled by thermal_print.js ─────────────────

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

    // Cancel payment
    $(document).on('click', '.cancelPaymentOut', function () {
        var paymentUID = $(this).data('payment-uid');
        var num        = $(this).data('num') || '';
        var amount     = $(this).data('amount');
        Swal.fire({
            title: 'Cancel Payment?',
            html : 'Cancel payment <strong>' + (num || '#' + paymentUID) + '</strong> of <strong>' + sym + ' ' + amount + '</strong>?<br>The sales return balance will be restored.',
            icon : 'warning', showCancelButton: true,
            confirmButtonText: 'Yes, Cancel It', confirmButtonColor: '#f59e0b',
            cancelButtonText : 'No, Keep It',
        }).then(function (r) {
            if (!r.isConfirmed) return;
            $.ajax({
                url   : '/paymentsout/cancelPayment',
                method: 'POST',
                data  : { PaymentUID: paymentUID, [CsrfName]: CsrfToken },
                success: function (resp) {
                    if (!resp.Error) {
                        getPaymentsOut(PoutPageNo);
                        Swal.fire({ icon: 'success', text: resp.Message, timer: 1800, showConfirmButton: false });
                    } else {
                        Swal.fire('Error', resp.Message, 'error');
                    }
                }
            });
        });
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

</script>
