<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php $this->load->view('common/header'); ?>

<div class="layout-wrapper layout-content-navbar">
    <div class="layout-container">

        <?php $this->load->view('common/menu_view'); ?>

        <div class="layout-page">
            <div class="content-wrapper">
                <div class="container-xxl flex-grow-1 container-p-y">

                    <?php
                    $cur      = htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹');
                    $dec      = (int)($JwtData->GenSettings->DecimalPoints ?? 2);
                    $summary  = $MethodSummary ?? [];
                    $totals   = $Totals ?? (object)['TotalReceived' => 0, 'TotalPaid' => 0];
                    $received = (float)($totals->TotalReceived ?? 0);
                    $paid     = (float)($totals->TotalPaid ?? 0);
                    $net      = $received - $paid;

                    function pmtFmt($val, $sym, $dec) {
                        return $sym . ' ' . number_format((float)$val, $dec, '.', ',');
                    }
                    ?>

                    <!-- ── Balance Summary Cards ──────────────────────────── -->
                    <div class="d-flex gap-3 mb-4 flex-wrap" id="pmtSummaryCards">

                        <?php foreach ($summary as $row): ?>
                        <?php
                            $balance  = (float)$row->NetBalance;
                            $isPos    = $balance >= 0;
                            $label    = htmlspecialchars($row->AccountLabel ?? 'Cash');
                            $bankName = htmlspecialchars($row->BankName ?? '');
                        ?>
                        <div class="card border-0 shadow-sm pmt-summary-card" style="min-width:200px;flex:1 1 180px;max-width:260px;">
                            <div class="card-body py-3 px-3">
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <?php if ($row->IsCash): ?>
                                        <i class="bx bx-money fs-5 text-success"></i>
                                    <?php else: ?>
                                        <i class="bx bx-building-house fs-5 text-primary"></i>
                                    <?php endif; ?>
                                    <span class="fw-semibold" style="font-size:.82rem;"><?php echo $label; ?></span>
                                    <i class="bx <?php echo $isPos ? 'bx-up-arrow-alt text-success' : 'bx-down-arrow-alt text-danger'; ?> ms-auto fs-5"></i>
                                </div>
                                <?php if ($bankName): ?>
                                    <div class="text-muted" style="font-size:.72rem;"><?php echo $bankName; ?></div>
                                <?php endif; ?>
                                <div class="fw-bold mt-1 <?php echo $isPos ? 'text-dark' : 'text-danger'; ?>" style="font-size:1.05rem;">
                                    <?php echo pmtFmt($balance, $cur, $dec); ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>

                        <?php if (empty($summary)): ?>
                        <div class="text-muted small py-2">No payment data yet.</div>
                        <?php endif; ?>

                    </div>

                    <!-- ── Main Card ──────────────────────────────────────── -->
                    <div class="card border-0 shadow-sm">

                        <!-- Tabs + Toolbar -->
                        <div class="trans-toolbar d-flex align-items-center justify-content-between flex-wrap gap-2">

                            <!-- Status tabs -->
                            <ul class="nav trans-status-tabs gap-1" id="pmtStatusTabs">
                                <li class="nav-item">
                                    <a class="nav-link pmt-tab active fw-semibold px-3 py-1" data-tab="Success" href="javascript:void(0);">
                                        Success <span class="badge bg-primary ms-1" id="pmtTabCountSuccess"><?php echo number_format($ModAllCount); ?></span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link pmt-tab fw-semibold px-3 py-1 text-muted" data-tab="Cancelled" href="javascript:void(0);">
                                        Cancelled <span class="badge bg-secondary ms-1" id="pmtTabCountCancelled">0</span>
                                    </a>
                                </li>
                            </ul>

                            <!-- Right controls -->
                            <div class="d-flex align-items-center gap-2 flex-wrap py-2">
                                <div class="input-group input-group-sm" style="width:200px;">
                                    <span class="input-group-text bg-white border-end-0"><i class="bx bx-search text-muted"></i></span>
                                    <input type="text" class="form-control border-start-0" id="pmtSearch" placeholder="Search payment, party, amount…">
                                </div>
                                <div class="dropdown">
                                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button"
                                            id="pmtDateFilterBtn" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="bx bx-calendar me-1"></i><span id="pmtDateFilterLabel">All Dates</span>
                                    </button>
                                    <ul class="dropdown-menu shadow" id="pmtDateFilterMenu"
                                        style="width:220px;max-height:360px;overflow-y:auto;font-size:.82rem;"></ul>
                                </div>
                                <div class="dropdown">
                                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                        Actions
                                    </button>
                                    <ul class="dropdown-menu shadow-sm" style="font-size:.82rem;">
                                        <li><a class="dropdown-item" href="javascript:void(0);" id="pmtFilterSales"><i class="bx bx-upload me-2 text-success"></i>Sales (Received)</a></li>
                                        <li><a class="dropdown-item" href="javascript:void(0);" id="pmtFilterPurchases"><i class="bx bx-download me-2 text-danger"></i>Purchases (Paid)</a></li>
                                        <li><hr class="dropdown-divider my-1"></li>
                                        <li><a class="dropdown-item" href="javascript:void(0);" id="pmtClearFilter"><i class="bx bx-x me-2 text-muted"></i>Clear Filters</a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Table -->
                        <div class="table-responsive">
                            <table class="table trans-table table-hover MainviewTable mb-0" id="paymentsTable">
                                <thead class="r2k-thead">
                                    <tr>
                                        <th class="ps-3" style="width:140px;">Ref No</th>
                                        <th class="ps-3" style="width:130px;">Amount</th>
                                        <th style="width:150px;">Mode / Bank</th>
                                        <th style="width:130px;">Linked Document</th>
                                        <th>Party Name</th>
                                        <th style="width:160px;">Created By</th>
                                        <th style="width:120px;" class="text-end pe-3">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="paymentsTableBody">
                                    <?php echo $ModRowData; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Footer with totals -->
                        <div class="card-footer bg-white border-top d-flex flex-wrap justify-content-between align-items-center px-3 py-2 gap-3">
                            <div class="d-flex align-items-center gap-3">
                                <span class="text-muted" style="font-size:.8rem;">
                                    Net Balance &nbsp;<strong class="text-dark" id="pmtFooterNet"><?php echo pmtFmt($net, $cur, $dec); ?></strong>
                                </span>
                                <span class="text-muted" style="font-size:.8rem;">
                                    You Received: &nbsp;<strong class="text-success" id="pmtFooterReceived"><?php echo pmtFmt($received, $cur, $dec); ?></strong>
                                </span>
                                <span class="text-muted" style="font-size:.8rem;">
                                    You Gave: &nbsp;<strong class="text-danger" id="pmtFooterPaid"><?php echo pmtFmt($paid, $cur, $dec); ?></strong>
                                </span>
                            </div>
                            <div id="pmtPagination" class="d-flex align-items-center gap-3">
                                <span class="text-muted" style="font-size:.78rem;">Total: <strong id="pmtTotalCount"><?php echo number_format($ModAllCount); ?></strong></span>
                                <?php echo $ModPagination; ?>
                            </div>
                        </div>

                    </div>

                </div>
                <?php $this->load->view('common/footer_desc'); ?>
            </div>
        </div>

    </div>
</div>

<!-- ── Payment Detail Modal ──────────────────────────────────── -->
<div class="modal fade" id="paymentDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:540px;">
        <div class="modal-content border-0 shadow position-relative">

            <!-- Close -->
            <button type="button" class="btn-close position-absolute top-0 end-0 m-3 z-3" data-bs-dismiss="modal"
                    style="background-color:#fff;border-radius:50%;padding:8px;box-shadow:0 2px 6px rgba(0,0,0,.15);"></button>

            <!-- Banner -->
            <div style="background:#e8f0fe;border-left:4px solid #0d6efd;border-radius:8px 8px 0 0;padding:16px 20px 14px;">
                <div class="d-flex align-items-center gap-3">
                    <div style="width:44px;height:44px;border-radius:10px;background:rgba(13,110,253,.12);display:flex;align-items:center;justify-content:center;">
                        <i class="bx bx-receipt fs-4 text-primary"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="fw-bold text-dark" style="font-size:1rem;" id="pdUniqueNumber">—</div>
                        <div class="text-muted" style="font-size:.78rem;" id="pdDateLabel">—</div>
                    </div>
                    <div class="text-end">
                        <div class="fw-bold text-primary" style="font-size:1.2rem;" id="pdAmount">—</div>
                        <div id="pdModeBadge" class="mt-1"></div>
                    </div>
                </div>
            </div>

            <div class="modal-body px-4 py-3">

                <!-- Party + Transaction -->
                <div style="background:#f8f9fa;border-radius:8px;padding:12px 14px;margin-bottom:12px;">
                    <div class="row g-2">
                        <div class="col-6">
                            <div class="text-muted" style="font-size:.7rem;text-transform:uppercase;letter-spacing:.05em;font-weight:600;">Party</div>
                            <div class="fw-semibold" style="font-size:.85rem;" id="pdParty">—</div>
                            <div class="text-muted" style="font-size:.72rem;" id="pdPartyMobile"></div>
                        </div>
                        <div class="col-6">
                            <div class="text-muted" style="font-size:.7rem;text-transform:uppercase;letter-spacing:.05em;font-weight:600;">Linked Document</div>
                            <div class="fw-semibold text-primary" style="font-size:.85rem;" id="pdTransNumber">—</div>
                        </div>
                    </div>
                </div>

                <!-- Bank Details (hidden when cash) -->
                <div id="pdBankSection" style="display:none;background:#f8f9fa;border-radius:8px;padding:12px 14px;margin-bottom:12px;">
                    <div class="text-muted mb-2" style="font-size:.7rem;text-transform:uppercase;letter-spacing:.05em;font-weight:600;">
                        <i class="bx bx-building-house me-1"></i>Bank Details
                    </div>
                    <div class="row g-2">
                        <div class="col-7">
                            <div class="text-muted" style="font-size:.7rem;">Bank / Account Name</div>
                            <div class="fw-semibold" style="font-size:.82rem;" id="pdBankName">—</div>
                        </div>
                        <div class="col-5">
                            <div class="text-muted" style="font-size:.7rem;">Account Number</div>
                            <div class="fw-semibold" style="font-size:.82rem;font-family:monospace;" id="pdAccountNumber">—</div>
                        </div>
                        <div class="col-6" id="pdIfscWrap" style="display:none;">
                            <div class="text-muted" style="font-size:.7rem;">IFSC</div>
                            <div class="fw-semibold" style="font-size:.82rem;" id="pdIfsc">—</div>
                        </div>
                        <div class="col-6" id="pdBranchWrap" style="display:none;">
                            <div class="text-muted" style="font-size:.7rem;">Branch</div>
                            <div class="fw-semibold" style="font-size:.82rem;" id="pdBranch">—</div>
                        </div>
                    </div>
                </div>

                <!-- Reference / Created By / Notes -->
                <div class="row g-3">
                    <div class="col-6">
                        <div class="text-muted" style="font-size:.7rem;text-transform:uppercase;letter-spacing:.05em;font-weight:600;">Reference No</div>
                        <div style="font-size:.85rem;" id="pdReference">—</div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted" style="font-size:.7rem;text-transform:uppercase;letter-spacing:.05em;font-weight:600;">Recorded By</div>
                        <div style="font-size:.85rem;" id="pdCreatedBy">—</div>
                    </div>
                    <div class="col-12" id="pdNotesWrap" style="display:none;">
                        <div class="text-muted" style="font-size:.7rem;text-transform:uppercase;letter-spacing:.05em;font-weight:600;">Notes</div>
                        <div style="font-size:.85rem;color:#444;" id="pdNotes"></div>
                    </div>
                </div>

            </div><!-- /modal-body -->
        </div>
    </div>
</div>

<?php $this->load->view('common/footer'); ?>

<script type="text/javascript" src="/js/common/datefilter.js"></script>

<style>
.pmt-tab { border-radius: 6px; font-size: .82rem; transition: background .15s; }
.pmt-tab.active { background: #f0f4ff; color: #0d6efd !important; }
.pmt-tab:not(.active):hover { background: #f5f5f5; }
.pmt-summary-card { transition: box-shadow .15s; }
.pmt-summary-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,.10) !important; }
.pmt-mode-badge { font-size: .7rem; padding: 2px 8px; border-radius: 20px; }
</style>

<script>
const PmtModuleId = <?php echo (int)$JwtData->ModuleUID; ?>;
var PmtFilter  = {};
var PmtPageNo  = 1;
var PmtLimit   = 10;

$(function () {
    'use strict';

    function getPaymentsDetails(pageNo, append) {
        pageNo = pageNo || 1;
        PmtPageNo = pageNo;
        $.ajax({
            url    : '/payments/getPaymentsPageDetails/' + pageNo,
            method : 'POST',
            data   : { RowLimit: PmtLimit, Filter: PmtFilter, [CsrfName]: CsrfToken },
            success: function (resp) {
                if (!resp.Error) {
                    $('#paymentsTableBody').html(resp.RecordHtmlData);
                    $('#pmtPagination').html(
                        '<span class="text-muted" style="font-size:.78rem;">Total: <strong id="pmtTotalCount">' +
                        Number(resp.TotalCount).toLocaleString() + '</strong></span>' +
                        (resp.Pagination || '')
                    );
                    if (resp.Totals) {
                        var sym = '<?php echo addslashes($cur); ?>';
                        var dec = <?php echo $dec; ?>;
                        var fmt = function(v) { return sym + ' ' + parseFloat(v).toLocaleString('en-IN', {minimumFractionDigits: dec, maximumFractionDigits: dec}); };
                        $('#pmtFooterReceived').text(fmt(resp.Totals.TotalReceived || 0));
                        $('#pmtFooterPaid').text(fmt(resp.Totals.TotalPaid || 0));
                        $('#pmtFooterNet').text(fmt((resp.Totals.TotalReceived || 0) - (resp.Totals.TotalPaid || 0)));
                    }
                }
            }
        });
    }

    // Tab switch
    $(document).on('click', '.pmt-tab', function (e) {
        e.preventDefault();
        $('.pmt-tab').removeClass('active');
        $(this).addClass('active');
        PmtFilter.Status = $(this).data('tab') === 'Cancelled' ? 'Cancelled' : '';
        PmtPageNo = 1;
        getPaymentsDetails(1);
    });

    // Pagination
    $(document).on('click', '#pmtPagination .page-link', function (e) {
        e.preventDefault();
        var m = ($(this).attr('href') || '').match(/\/(\d+)$/);
        if (m) getPaymentsDetails(parseInt(m[1]));
    });

    // Search
    var pmtSearchTimer;
    $('#pmtSearch').on('input', function () {
        clearTimeout(pmtSearchTimer);
        var v = $.trim($(this).val());
        pmtSearchTimer = setTimeout(function () {
            PmtFilter.Search = v;
            PmtPageNo = 1;
            getPaymentsDetails(1);
        }, 400);
    });

    // Date filter — dropdown with range presets + custom flatpickr pickers
    $('#pmtDateFilterMenu').html(buildDateFilterHtml('pmtCustomDateFrom', 'pmtCustomDateTo'));

    initDateFilter({
        btnId  : 'pmtDateFilterBtn',
        labelId: 'pmtDateFilterLabel',
        fromId : 'pmtCustomDateFrom',
        toId   : 'pmtCustomDateTo',
        onApply: function (from, to) {
            PmtFilter.DateFrom = from;
            PmtFilter.DateTo   = to;
            PmtPageNo = 1;
            getPaymentsDetails(1);
        }
    });

    // Attach flatpickr to the custom range inputs once they are rendered in the dropdown
    $(document).on('shown.bs.dropdown', '#pmtDateFilterBtn', function () {
        if (!$('#pmtCustomDateFrom').data('fpInit')) {
            flatpickr('#pmtCustomDateFrom', { dateFormat: 'Y-m-d', altInput: true, altFormat: 'd M Y', maxDate: 'today', disableMobile: true });
            flatpickr('#pmtCustomDateTo',   { dateFormat: 'Y-m-d', altInput: true, altFormat: 'd M Y', maxDate: 'today', disableMobile: true });
            $('#pmtCustomDateFrom').data('fpInit', true);
        }
    });

    // Actions dropdown filters
    $('#pmtFilterSales').on('click', function () {
        PmtFilter.PartyType = 'C';
        PmtPageNo = 1;
        getPaymentsDetails(1);
    });
    $('#pmtFilterPurchases').on('click', function () {
        PmtFilter.PartyType = 'V';
        PmtPageNo = 1;
        getPaymentsDetails(1);
    });
    $('#pmtClearFilter').on('click', function () {
        PmtFilter = {};
        $('#pmtSearch').val('');
        $('#pmtDateFilterLabel').text('All Dates');
        $('.date-option').removeClass('active');
        $('.date-option[data-range=""]').addClass('active');
        $('.pmt-tab').removeClass('active');
        $('.pmt-tab[data-tab="Success"]').addClass('active');
        PmtPageNo = 1;
        getPaymentsDetails(1);
    });

    // View payment detail
    $(document).on('click', '.viewPaymentDetail', function () {
        var paymentUID = $(this).data('payment-uid');
        var sym = '<?php echo addslashes($cur); ?>';
        var dec = <?php echo $dec; ?>;
        var fmt = function (v) {
            return sym + ' ' + parseFloat(v || 0).toLocaleString('en-IN', { minimumFractionDigits: dec, maximumFractionDigits: dec });
        };

        $.ajax({
            url   : '/payments/getPaymentDetail',
            method: 'POST',
            data  : { PaymentUID: paymentUID, [CsrfName]: CsrfToken },
            success: function (resp) {
                if (resp.Error) { Swal.fire('Error', resp.Message || 'Could not load payment.', 'error'); return; }
                var d = resp.Data;

                // Banner
                $('#pdUniqueNumber').text(d.UniqueNumber || ('Payment #' + d.PaymentUID));
                var dateStr = d.PaymentDate || (d.CreatedOn ? d.CreatedOn.slice(0, 10) : '');
                if (dateStr) {
                    var parts = dateStr.split('-');
                    var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
                    dateStr = parts[2] + ' ' + months[parseInt(parts[1], 10) - 1] + ' ' + parts[0];
                }
                $('#pdDateLabel').text(dateStr || '—');
                $('#pdAmount').text(fmt(d.Amount));

                // Mode badge
                var modeMap = { 'cash': '#e8f5e9|#2e7d32', 'upi': '#ede7f6|#4527a0', 'card': '#e3f2fd|#1565c0', 'net banking': '#fff8e1|#f57f17', 'cheque': '#fce4ec|#880e4f', 'emi': '#e0f7fa|#00695c' };
                var modeKey = (d.PaymentTypeName || '').toLowerCase().trim();
                var mc = modeMap[modeKey] ? modeMap[modeKey].split('|') : ['#f0f0f0', '#555'];
                $('#pdModeBadge').html('<span class="pmt-mode-badge" style="background:' + mc[0] + ';color:' + mc[1] + ';">' + (d.PaymentTypeName || '—') + '</span>');

                // Party
                $('#pdParty').text(d.PartyName || '—');
                $('#pdPartyMobile').text(d.PartyMobile || '').toggle(!!d.PartyMobile);
                $('#pdTransNumber').text(d.TransNumber || '—');

                // Bank
                if (d.BankName) {
                    var bankLabel = d.BankName + (d.AccountName ? ' (' + d.AccountName + ')' : '');
                    $('#pdBankName').text(bankLabel);
                    $('#pdAccountNumber').text(d.AccountNumber || '—');
                    $('#pdIfsc').text(d.IFSC || '');
                    $('#pdBranch').text(d.BranchName || '');
                    $('#pdIfscWrap').toggle(!!d.IFSC);
                    $('#pdBranchWrap').toggle(!!d.BranchName);
                    $('#pdBankSection').show();
                } else {
                    $('#pdBankSection').hide();
                }

                // Reference / By / Notes
                $('#pdReference').text(d.ReferenceNo || '—');
                $('#pdCreatedBy').text(d.CreatedByName || '—');
                $('#pdNotes').text(d.Notes || '');
                $('#pdNotesWrap').toggle(!!d.Notes);

                $('#paymentDetailModal').modal('show');
            },
            error: function () {
                Swal.fire('Error', 'Failed to load payment details.', 'error');
            }
        });
    });

    // Shared helper: call deletePayment endpoint and handle response
    function doPaymentRemove(paymentUID, $row) {
        var sym = '<?php echo addslashes($cur); ?>';
        var dec = <?php echo $dec; ?>;
        var fmt = function (v) {
            return sym + ' ' + parseFloat(v || 0).toLocaleString('en-IN', { minimumFractionDigits: dec, maximumFractionDigits: dec });
        };
        $.ajax({
            url   : '/payments/deletePayment',
            method: 'POST',
            data  : { PaymentUID: paymentUID, [CsrfName]: CsrfToken },
            success: function (resp) {
                if (!resp.Error) {
                    $row.fadeOut(300, function () { $(this).remove(); });
                    if (resp.NewBalanceAmount !== undefined) {
                        var statusColor = resp.NewStatus === 'Unpaid' ? 'secondary' : 'warning';
                        Swal.fire({
                            icon : 'success',
                            title: 'Done',
                            html : 'Transaction balance updated.<br>' +
                                   '<strong>Remaining Balance:</strong> ' + fmt(resp.NewBalanceAmount) +
                                   ' &nbsp;|&nbsp; Status: <span class="badge bg-label-' + statusColor + '">' +
                                   resp.NewStatus + '</span>',
                            timer: 3000,
                            showConfirmButton: false,
                        });
                    }
                } else {
                    Swal.fire('Error', resp.Message, 'error');
                }
            }
        });
    }

    // Cancel payment
    $(document).on('click', '.cancelPayment', function () {
        var paymentUID = $(this).data('payment-uid');
        var $row = $(this).closest('tr');
        Swal.fire({
            title: 'Cancel Payment?',
            text : 'This payment will be marked as cancelled and the invoice balance will be restored.',
            icon : 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, Cancel it',
            confirmButtonColor: '#f59e0b',
        }).then(function (result) {
            if (result.isConfirmed) doPaymentRemove(paymentUID, $row);
        });
    });

    // Delete payment
    $(document).on('click', '.deletePayment', function () {
        var paymentUID = $(this).data('payment-uid');
        var $row = $(this).closest('tr');
        Swal.fire({
            title: 'Delete Payment?',
            text : 'This will permanently remove the payment record and restore the invoice balance.',
            icon : 'warning',
            showCancelButton: true,
            confirmButtonText: 'Delete',
            confirmButtonColor: '#d33',
        }).then(function (result) {
            if (result.isConfirmed) doPaymentRemove(paymentUID, $row);
        });
    });

});
</script>
