<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php $this->load->view('common/transactions/header'); ?>

<div class="layout-wrapper layout-content-navbar">
    <div class="layout-container">

        <?php $this->load->view('common/menu_view'); ?>

        <div class="layout-page">

            <div class="content-wrapper">
                <div class="container-xxl flex-grow-1 container-p-y">

                    <?php
                    // ── Build summary numbers ─────────────────────────
                    $stats       = $SummaryStats ?? [];
                    $cur         = htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹');
                    $dec         = $JwtData->GenSettings->DecimalPoints ?? 2;

                    $cntAll      = array_sum(array_column($stats, 'count'));
                    $cntIssued   = ($stats['Issued']['count']   ?? 0) + ($stats['Sent']['count'] ?? 0) + ($stats['Partial']['count'] ?? 0);
                    $cntPaid     = $stats['Paid']['count']      ?? 0;
                    $cntDraft    = $stats['Draft']['count']     ?? 0;
                    $cntOverdue  = $stats['Overdue']['count']   ?? 0;

                    $amtAll      = array_sum(array_column($stats, 'amount'));
                    $amtIssued   = ($stats['Issued']['amount']  ?? 0) + ($stats['Sent']['amount'] ?? 0) + ($stats['Partial']['amount'] ?? 0);
                    $amtPaid     = $stats['Paid']['amount']     ?? 0;

                    function fmtAmt($val, $sym, $dec) {
                        return $sym . ' ' . number_format((float)$val, $dec, '.', ',');
                    }
                    ?>

                    <!-- ── Stat Cards ──────────────────────────────────────── -->
                    <div class="row g-3 mb-4">
                        <div class="col-6 col-md-3">
                            <a href="javascript:void(0);" class="trans-stat-card stat-all active-stat" data-stat-filter="All">
                                <div class="trans-stat-label">All Invoices</div>
                                <div class="trans-stat-count"><?php echo number_format($cntAll); ?></div>
                                <div class="trans-stat-amount"><?php echo fmtAmt($amtAll, $cur, $dec); ?></div>
                                <i class="bx bx-receipt trans-stat-icon"></i>
                            </a>
                        </div>
                        <div class="col-6 col-md-3">
                            <a href="javascript:void(0);" class="trans-stat-card stat-active" data-stat-filter="Issued">
                                <div class="trans-stat-label">Outstanding</div>
                                <div class="trans-stat-count"><?php echo number_format($cntIssued); ?></div>
                                <div class="trans-stat-amount"><?php echo fmtAmt($amtIssued, $cur, $dec); ?></div>
                                <i class="bx bx-time-five trans-stat-icon"></i>
                            </a>
                        </div>
                        <div class="col-6 col-md-3">
                            <a href="javascript:void(0);" class="trans-stat-card stat-paid" data-stat-filter="Paid">
                                <div class="trans-stat-label">Paid</div>
                                <div class="trans-stat-count"><?php echo number_format($cntPaid); ?></div>
                                <div class="trans-stat-amount"><?php echo fmtAmt($amtPaid, $cur, $dec); ?></div>
                                <i class="bx bx-check-circle trans-stat-icon"></i>
                            </a>
                        </div>
                        <div class="col-6 col-md-3">
                            <a href="javascript:void(0);" class="trans-stat-card stat-draft" data-stat-filter="Draft">
                                <div class="trans-stat-label">Drafts</div>
                                <div class="trans-stat-count"><?php echo number_format($cntDraft); ?></div>
                                <div class="trans-stat-amount">&nbsp;</div>
                                <i class="bx bx-pencil trans-stat-icon"></i>
                            </a>
                        </div>
                    </div>

                    <!-- ── Main Card ───────────────────────────────────────── -->
                    <div class="card">

                        <!-- Toolbar -->
                        <div class="trans-toolbar">

                            <!-- Status tabs -->
                            <ul class="nav trans-status-tabs gap-1" id="invStatusTabs" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active inv-status-tab" data-status="All" href="javascript:void(0);">
                                        All <span class="trans-tab-count ms-1"><?php echo $ModAllCount; ?></span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link inv-status-tab" data-status="Issued" href="javascript:void(0);">
                                        Issued <span class="trans-tab-count ms-1 d-none"></span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link inv-status-tab" data-status="Paid" href="javascript:void(0);">
                                        Paid <span class="trans-tab-count ms-1 d-none"></span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link inv-status-tab" data-status="Cancelled" href="javascript:void(0);">
                                        Cancelled <span class="trans-tab-count ms-1 d-none"></span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link inv-status-tab" data-status="Draft" href="javascript:void(0);">
                                        Drafts <span class="trans-tab-count ms-1 d-none"></span>
                                    </a>
                                </li>
                            </ul>

                            <!-- Right controls -->
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <a href="javascript:void(0);" class="btn btn-sm btn-outline-secondary p-1 pageRefresh" title="Refresh">
                                    <i class="bx bx-refresh fs-5"></i>
                                </a>
                                <div class="input-group input-group-sm" style="width:210px">
                                    <span class="input-group-text bg-transparent border-end-0"><i class="bx bx-search text-muted"></i></span>
                                    <input type="text" class="form-control border-start-0" id="searchTransactionData"
                                           placeholder="Invoice # or customer..." title="Search invoices">
                                </div>
                                <div class="dropdown">
                                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button"
                                            id="dateFilterBtn" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="bx bx-calendar me-1"></i><span id="dateFilterLabel">All Dates</span>
                                    </button>
                                    <ul class="dropdown-menu shadow" style="width:210px;max-height:300px;overflow-y:auto;font-size:.82rem;">
                                        <li><a class="dropdown-item date-option" data-range=""><i class="bx bx-list-ul me-2"></i>All Dates</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item date-option" data-range="today">Today</a></li>
                                        <li><a class="dropdown-item date-option" data-range="yesterday">Yesterday</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item date-option" data-range="this_week">This Week</a></li>
                                        <li><a class="dropdown-item date-option" data-range="last_week">Last Week</a></li>
                                        <li><a class="dropdown-item date-option" data-range="last_7_days">Last 7 Days</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item date-option" data-range="this_month">This Month</a></li>
                                        <li><a class="dropdown-item date-option" data-range="previous_month">Previous Month</a></li>
                                        <li><a class="dropdown-item date-option" data-range="last_30_days">Last 30 Days</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item date-option" data-range="this_year">This Year</a></li>
                                        <li><a class="dropdown-item date-option" data-range="last_year">Last Year</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item date-option fw-bold" data-range="fy_25_26">
                                            <i class="bx bxs-star text-warning me-1"></i>FY 25-26
                                        </a></li>
                                    </ul>
                                </div>
                                <a href="/invoices/create" class="btn btn-primary btn-sm px-3">
                                    <i class="bx bx-plus me-1"></i>Create
                                </a>
                            </div>
                        </div>

                        <!-- Table -->
                        <div class="table-responsive">
                            <table class="table trans-table table-hover MainviewTable mb-0" id="invTable">
                                <thead class="r2k-thead">
                                    <tr>
                                        <th style="width:36px">
                                            <div class="form-check mb-0">
                                                <input class="form-check-input table-chkbox invHeaderCheck" type="checkbox">
                                            </div>
                                        </th>
                                        <th class="<?php echo $JwtData->GenSettings->SerialNoDisplay == 1 ? '' : 'd-none'; ?> table-serialno" style="width:44px">S.No</th>
                                        <th class="col-sortable cursor-pointer user-select-none" data-sort="Number">
                                            Invoice # <i class="bx bx-sort-alt-2 ms-1 sort-icon" data-col="Number"></i>
                                        </th>
                                        <th class="col-sortable cursor-pointer user-select-none" data-sort="Amount">
                                            Amount <i class="bx bx-sort-alt-2 ms-1 sort-icon" data-col="Amount"></i>
                                        </th>
                                        <th>Status</th>
                                        <th>Customer</th>
                                        <th class="col-sortable cursor-pointer user-select-none" data-sort="Date">
                                            Due Date <i class="bx bx-sort-alt-2 ms-1 sort-icon" data-col="Date"></i>
                                        </th>
                                        <th>Last Updated</th>
                                        <th style="width:50px"></th>
                                    </tr>
                                </thead>
                                <tbody class="r2k-tbody table-border-bottom-0">
                                    <?php echo $ModRowData; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <hr class="my-0">
                        <div class="row mx-3 my-2 justify-content-between align-items-center invPagination" id="invPagination">
                            <?php echo $ModPagination ?: ''; ?>
                        </div>

                    </div>

                    <?php $this->load->view('common/transactions/print_modals'); ?>

                </div>
            </div>

        <!-- ── Record Payment Offcanvas ─────────────────────────── -->
        <div class="offcanvas offcanvas-end" tabindex="-1" id="recordPaymentOffcanvas" aria-labelledby="recordPaymentLabel" style="width:480px;max-width:100vw;">
            <div class="offcanvas-header border-bottom py-3 px-4" style="background:#fff;">
                <div>
                    <h6 class="mb-0 fw-semibold" id="recordPaymentLabel">Record Payment for <span id="rpInvNum" class="text-primary">—</span></h6>
                    <div class="text-muted" style="font-size:.78rem;" id="rpInvDate"></div>
                </div>
                <button type="button" class="btn btn-primary btn-sm ms-auto me-3" id="btnSubmitPayment">
                    Record Payment <i class="bx bx-right-arrow-alt ms-1"></i>
                </button>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
            </div>

            <div class="offcanvas-body px-4 py-3" style="background:#f8f9fa;">

                <!-- Party + Balance -->
                <div class="d-flex justify-content-between align-items-center mb-3 px-1">
                    <span class="text-muted" style="font-size:.82rem;">Payment Info - <strong id="rpPartyName">—</strong></span>
                    <span class="fw-semibold text-danger" style="font-size:.85rem;">Balance &nbsp;<span id="rpBalanceDisplay">—</span></span>
                </div>

                <!-- Payment Details -->
                <div class="card mb-3 border-0 shadow-sm">
                    <div class="card-header bg-white py-2 px-3" style="cursor:pointer;" id="rpDetailsToggle">
                        <i class="bx bx-chevron-down me-1 text-muted"></i>
                        <span style="font-size:.85rem;font-weight:600;">Payment Details</span>
                    </div>
                    <div class="card-body px-3 py-3" id="rpDetailsBody">

                        <!-- Amount -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold" style="font-size:.82rem;"><span class="text-danger">*</span> Amount to be Recorded</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white" style="font-size:.9rem;" id="rpCurrencySymbol">₹</span>
                                <input type="number" class="form-control" id="rpAmount" step="0.01" min="0.01" placeholder="0.00">
                            </div>
                            <div class="d-flex justify-content-between mt-1">
                                <span class="text-muted" style="font-size:.73rem;">Total Amount <strong id="rpTotalLabel">—</strong></span>
                                <span class="text-muted" style="font-size:.73rem;">Amount Pending <strong id="rpPendingLabel">—</strong></span>
                            </div>
                        </div>

                        <!-- Payment Date -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold" style="font-size:.82rem;">Payment Date</label>
                            <input type="date" class="form-control" id="rpPaymentDate">
                        </div>

                        <!-- Payment Type -->
                        <div class="mb-2">
                            <label class="form-label fw-semibold" style="font-size:.82rem;">Payment Type</label>
                            <div class="d-flex flex-wrap gap-2" id="rpPaymentTypes">
                                <div class="text-muted" style="font-size:.8rem;"><i class="bx bx-loader-alt bx-spin"></i> Loading…</div>
                            </div>
                            <input type="hidden" id="rpPaymentTypeUID" value="">
                            <input type="hidden" id="rpIsCash" value="1">
                        </div>

                        <!-- Bank Account (shown for non-cash) -->
                        <div class="mb-2 d-none" id="rpBankRow">
                            <label class="form-label fw-semibold" style="font-size:.82rem;">Bank Account</label>
                            <select class="form-select form-select-sm" id="rpBankAccount">
                                <option value="">Select bank account…</option>
                            </select>
                        </div>

                    </div>
                </div>

                <!-- More Details -->
                <div class="card mb-3 border-0 shadow-sm">
                    <div class="card-body px-3 py-3">
                        <div class="fw-semibold mb-3" style="font-size:.85rem;">More Details</div>
                        <div class="mb-2">
                            <label class="form-label text-muted" style="font-size:.78rem;">Payment Reference ID &nbsp;<span class="text-muted fw-normal">(Optional)</span></label>
                            <input type="text" class="form-control form-control-sm" id="rpReferenceNo" placeholder="Your UTR ID for the payment">
                            <div class="text-muted mt-1" style="font-size:.72rem;">A unique ID for each payment.</div>
                        </div>
                        <div class="mb-0">
                            <label class="form-label text-muted" style="font-size:.78rem;">Notes &nbsp;<span class="text-muted fw-normal">(Optional)</span></label>
                            <textarea class="form-control form-control-sm" id="rpNotes" rows="2" placeholder="Add a note…"></textarea>
                        </div>
                    </div>
                </div>

                <input type="hidden" id="rpTransUID" value="">
            </div>
        </div>

            <?php $this->load->view('common/footer_desc'); ?>
        </div>
    </div>
</div>

<?php $this->load->view('common/transactions/footer'); ?>

<script src="/js/transactions/viewmodal.js"></script>
<script src="/js/transactions/a4_print.js"></script>
<script src="/js/transactions/invoices.js"></script>

<script>
const ModuleId     = 103;
const ModuleTable  = '#invTable';
const ModulePag    = '.invPagination';
const ModuleHeader = '.invHeaderCheck';
const ModuleRow    = '.invCheck';

$(function () {
    'use strict';

    Filter['Status'] = 'All';

    // ── Stat card click → filter by status ─────────────────
    $(document).on('click', '[data-stat-filter]', function () {
        var status = $(this).data('stat-filter') || 'All';
        $('.trans-stat-card').removeClass('active-stat');
        $(this).addClass('active-stat');
        // Sync tabs
        $('.inv-status-tab').removeClass('active');
        $('.inv-status-tab[data-status="' + status + '"]').addClass('active');
        Filter.Status = status;
        PageNo = 1;
        getInvoicesDetails();
    });

    // ── Status tabs ─────────────────────────────────────────
    $(document).on('click', '.inv-status-tab', function (e) {
        e.preventDefault();
        $('.inv-status-tab').removeClass('active');
        $(this).addClass('active');
        $('.trans-stat-card').removeClass('active-stat');
        var status = $(this).data('status') || 'All';
        $('[data-stat-filter="' + status + '"]').addClass('active-stat');
        Filter.Status = status;
        PageNo = 1;
        getInvoicesDetails();
    });

    $(document).on('click', '.pageRefresh', function (e) {
        e.preventDefault();
        PageNo = 1;
        getInvoicesDetails();
    });

    // Search
    $('#searchTransactionData').on('keyup', debounce(function () {
        Filter.Name = $.trim($(this).val());
        PageNo = 1;
        getInvoicesDetails();
    }, 400));

    // Date filter
    $(document).on('click', '.date-option', function () {
        $('.date-option').removeClass('active');
        $(this).addClass('active');
        var range = $(this).data('range') || '';
        var dates = getDateRange(range);
        $('#dateFilterLabel').text($.trim($(this).text()));
        Filter.DateFrom = dates.from;
        Filter.DateTo   = dates.to;
        PageNo = 1;
        getInvoicesDetails();
    });

    // Column sorting
    $(document).on('click', '.col-sortable', function () {
        var col = $(this).data('sort');
        if (Filter.SortBy === col) {
            Filter.SortDir = (Filter.SortDir === 'ASC') ? 'DESC' : 'ASC';
        } else {
            Filter.SortBy  = col;
            Filter.SortDir = 'DESC';
        }
        $('.sort-icon').removeClass('bx-sort-up bx-sort-down').addClass('bx-sort-alt-2');
        var icon = Filter.SortDir === 'ASC' ? 'bx-sort-up' : 'bx-sort-down';
        $('.sort-icon[data-col="' + col + '"]').removeClass('bx-sort-alt-2').addClass(icon);
        PageNo = 1;
        getInvoicesDetails();
    });

    // Pagination
    $(document).on('click', '.invPagination .page-link', function (e) {
        e.preventDefault();
        var match = ($(this).attr('href') || '').match(/\/(\d+)$/);
        if (match) { PageNo = parseInt(match[1]); getInvoicesDetails(); }
    });

    // ── Inline status update ────────────────────────────────
    $(document).on('click', '.inv-status-update', function () {
        var uid    = $(this).data('uid');
        var status = $(this).data('status');
        $.ajax({
            url   : '/invoices/updateInvoiceStatus',
            method: 'POST',
            data  : { TransUID: uid, Status: status, [CsrfName]: CsrfToken },
            success: function (resp) {
                if (resp.Error) { Swal.fire({ icon:'error', text:resp.Message }); }
                else            { getInvoicesDetails(); }
            }
        });
    });

    // View modal — handled by /js/transactions/viewmodal.js (.viewTransaction)

    // ── A4 Print — handled by /js/transactions/a4_print.js ──

    // ── Delete ──────────────────────────────────────────────
    $(document).on('click', '.deleteInvoice', function () {
        var uid = $(this).data('uid');
        var num = $(this).data('num') || '';
        Swal.fire({
            title: 'Delete Invoice?',
            html : num ? 'Delete <strong>' + num + '</strong>? This cannot be undone.' : 'This cannot be undone.',
            icon : 'warning', showCancelButton: true,
            confirmButtonText: 'Delete', confirmButtonColor: '#d33',
        }).then(function (r) {
            if (!r.isConfirmed) return;
            $.ajax({
                url   : '/invoices/deleteInvoice',
                method: 'POST',
                data  : { TransUID: uid, [CsrfName]: CsrfToken },
                success: function (resp) {
                    if (resp.Error) { Swal.fire({ icon:'error', text:resp.Message }); }
                    else { getInvoicesDetails(); Swal.fire({ icon:'success', text:resp.Message, timer:1500, showConfirmButton:false }); }
                }
            });
        });
    });

    // ── Duplicate ───────────────────────────────────────────
    $(document).on('click', '.duplicateInvoice', function () {
        var uid = $(this).data('uid');
        Swal.fire({
            title: 'Duplicate Invoice?', text: 'A new draft copy will be created.',
            icon : 'question', showCancelButton: true, confirmButtonText: 'Duplicate',
        }).then(function (r) {
            if (!r.isConfirmed) return;
            $.ajax({
                url   : '/invoices/duplicateInvoice',
                method: 'POST',
                data  : { TransUID: uid, [CsrfName]: CsrfToken },
                success: function (resp) {
                    if (resp.Error) { Swal.fire({ icon:'error', text:resp.Message }); }
                    else {
                        getInvoicesDetails();
                        Swal.fire({ icon:'success', text:resp.Message, showCancelButton:true, confirmButtonText:'Edit Now', cancelButtonText:'Stay Here' })
                            .then(function (r2) { if (r2.isConfirmed && resp.EditURL) window.location.href = resp.EditURL; });
                    }
                }
            });
        });
    });
});

// ── Detail HTML builder ─────────────────────────────────────────
function _buildInvDetailHtml(resp) {
    window._invLastPrintData = resp;
    return _buildTransDetailHtml(resp, {
        partyLabel  : 'Customer',
        typeIcon    : 'bx-receipt',
        typeColor   : '#0d6efd',
        typeBg      : '#e8f0fe',
        hasPayments : true,
    });
}



function _esc(v) {
    if (v === null || v === undefined) return '—';
    return $('<span>').text(String(v)).html();
}

// ── Record Payment Offcanvas ──────────────────────────────────────
(function () {
    'use strict';

    var _payTypes    = [];
    var _bankAccts   = [];
    var _typesLoaded = false;
    var _banksLoaded = false;
    var _currency    = '<?php echo htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹'); ?>';

    function loadPaymentTypes() {
        if (_typesLoaded) return;
        $.get('/payments/getPaymentTypes', function (resp) {
            if (resp.Error) return;
            _payTypes    = resp.Data || [];
            _typesLoaded = true;
            renderPaymentTypes();
        });
    }

    function loadBankAccounts() {
        if (_banksLoaded) return;
        $.get('/payments/getBankAccounts', function (resp) {
            if (resp.Error) return;
            _bankAccts   = resp.Data || [];
            _banksLoaded = true;
            var $sel = $('#rpBankAccount').empty().append('<option value="">Select bank account…</option>');
            $.each(_bankAccts, function (i, b) {
                $sel.append('<option value="' + b.BankAccountUID + '">' + _esc(b.BankName) + ' — ' + _esc(b.AccountName) + '</option>');
            });
        });
    }

    function renderPaymentTypes() {
        var $wrap = $('#rpPaymentTypes').empty();
        $.each(_payTypes, function (i, t) {
            var active = (i === 0) ? ' active' : '';
            if (i === 0) { $('#rpPaymentTypeUID').val(t.PaymentTypeUID); $('#rpIsCash').val(t.IsCash); }
            $wrap.append(
                '<button type="button" class="rp-type-pill btn btn-sm btn-outline-secondary' + active + '" ' +
                'data-uid="' + t.PaymentTypeUID + '" data-iscash="' + t.IsCash + '">' + _esc(t.Name) + '</button>'
            );
        });
        toggleBankRow();
    }

    function toggleBankRow() {
        var isCash = parseInt($('#rpIsCash').val(), 10);
        if (isCash) { $('#rpBankRow').addClass('d-none'); } else { $('#rpBankRow').removeClass('d-none'); loadBankAccounts(); }
    }

    // Open offcanvas when "Receive Payment" clicked
    $(document).on('click', '.invReceivePayment', function () {
        var uid     = $(this).data('uid');
        var num     = $(this).data('num')     || '';
        var date    = $(this).data('date')    || '';
        var party   = $(this).data('party')   || '';
        var total   = parseFloat($(this).data('total'))   || 0;
        var pending = parseFloat($(this).data('pending')) || 0;

        $('#rpTransUID').val(uid);
        $('#rpInvNum').text(num);
        $('#rpInvDate').text(date);
        $('#rpPartyName').text(party);
        $('#rpBalanceDisplay').text(_currency + ' ' + pending.toFixed(2));
        $('#rpTotalLabel').text(_currency + ' ' + total.toFixed(2));
        $('#rpPendingLabel').text(_currency + ' ' + pending.toFixed(2));
        $('#rpAmount').val(pending.toFixed(2)).attr('max', pending);
        $('#rpCurrencySymbol').text(_currency);
        $('#rpPaymentDate').val(new Date().toISOString().split('T')[0]);
        $('#rpReferenceNo').val('');
        $('#rpNotes').val('');
        $('#rpBankAccount').val('');

        loadPaymentTypes();
        var oc = new bootstrap.Offcanvas(document.getElementById('recordPaymentOffcanvas'));
        oc.show();
    });

    // Payment type pill toggle
    $(document).on('click', '.rp-type-pill', function () {
        $('.rp-type-pill').removeClass('active btn-primary').addClass('btn-outline-secondary');
        $(this).addClass('active btn-primary').removeClass('btn-outline-secondary');
        $('#rpPaymentTypeUID').val($(this).data('uid'));
        $('#rpIsCash').val($(this).data('iscash'));
        toggleBankRow();
    });

    // Submit payment
    $('#btnSubmitPayment').on('click', function () {
        var transUID       = parseInt($('#rpTransUID').val(), 10);
        var paymentTypeUID = parseInt($('#rpPaymentTypeUID').val(), 10);
        var amount         = parseFloat($('#rpAmount').val()) || 0;
        var bankAccountUID = parseInt($('#rpBankAccount').val(), 10) || 0;
        var referenceNo    = $.trim($('#rpReferenceNo').val());
        var notes          = $.trim($('#rpNotes').val());

        if (!transUID)       { Swal.fire({ icon: 'warning', text: 'Invalid invoice.' }); return; }
        if (!paymentTypeUID) { Swal.fire({ icon: 'warning', text: 'Please select a payment type.' }); return; }
        if (amount <= 0)     { Swal.fire({ icon: 'warning', text: 'Enter a valid amount.' }); return; }

        var $btn = $(this).prop('disabled', true).text('Saving…');

        $.ajax({
            url   : '/invoices/recordInvoicePayment',
            method: 'POST',
            data  : {
                TransUID       : transUID,
                PaymentTypeUID : paymentTypeUID,
                Amount         : amount,
                BankAccountUID : bankAccountUID || '',
                ReferenceNo    : referenceNo,
                Notes          : notes,
                [CsrfName]     : CsrfToken,
            },
            success: function (resp) {
                $btn.prop('disabled', false).html('Record Payment <i class="bx bx-right-arrow-alt ms-1"></i>');
                if (resp.Error) {
                    Swal.fire({ icon: 'error', title: 'Error', text: resp.Message });
                } else {
                    bootstrap.Offcanvas.getInstance(document.getElementById('recordPaymentOffcanvas')).hide();
                    getInvoicesDetails();
                    Swal.fire({ icon: 'success', text: resp.Message, timer: 1800, showConfirmButton: false });
                }
            },
            error: function () {
                $btn.prop('disabled', false).html('Record Payment <i class="bx bx-right-arrow-alt ms-1"></i>');
                Swal.fire({ icon: 'error', text: 'Request failed. Try again.' });
            }
        });
    });

}());
</script>
