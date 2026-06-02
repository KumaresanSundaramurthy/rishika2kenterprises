<?php defined('BASEPATH') or exit('No direct script access allowed');
$this->load->view('common/transactions/header'); ?>

<div class="layout-wrapper layout-horizontal layout-content-navbar">
    <div class="layout-container">

        <?php $this->load->view('common/menu_view'); ?>

        <div class="layout-page">

            <div class="content-wrapper">
                <div class="container-xxl flex-grow-1 container-p-y">

                    <?php
                    $stats       = $SummaryStats ?? [];
                    $cur         = htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹');
                    $dec         = $JwtData->GenSettings->DecimalPoints ?? 2;

                    $activePurchStatuses = ['Received', 'Partial', 'Paid'];
                    $cntAll      = array_sum(array_map(fn($s) => $stats[$s]['count']  ?? 0, $activePurchStatuses));
                    $amtAll      = array_sum(array_map(fn($s) => $stats[$s]['amount'] ?? 0, $activePurchStatuses));
                    $cntPending  = ($stats['Received']['count']  ?? 0) + ($stats['Partial']['count']  ?? 0);
                    $amtPending  = ($stats['Received']['amount'] ?? 0) + ($stats['Partial']['amount'] ?? 0);
                    $cntPaid     = $stats['Paid']['count']   ?? 0;
                    $amtPaid     = $stats['Paid']['amount']  ?? 0;
                    $cntDraft    = $stats['Draft']['count']  ?? 0;

                    function fmtAmt($val, $sym, $dec) {
                        return $sym . ' ' . number_format((float)$val, $dec, '.', ',');
                    }
                    ?>

                    <!-- ── Page Header ──────────────────────────────────────── -->
                    <div class="trans-page-header">
                        <div class="d-flex align-items-center gap-3">
                            <div class="trans-ph-icon" style="background:#ede9fe;">
                                <i class="bx bx-store" style="color:#8b5cf6;"></i>
                            </div>
                            <div>
                                <h5 class="trans-ph-title mb-0"><?php echo htmlspecialchars($PageTitle ?? 'Purchases'); ?></h5>
                                <?php if (!empty($PageDescription)): ?>
                                <div class="text-muted" style="font-size:.76rem;"><?php echo htmlspecialchars($PageDescription); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <a href="/purchases/create" class="btn btn-primary me-1">
                            <i class="bx bx-plus me-1"></i>New Purchase Bill
                        </a>
                    </div>

                    <!-- ── Stat Cards ────────────────────────────────────── -->
                    <div class="trans-stats-section">
                        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;">
                            <a href="javascript:void(0);" class="trans-stat-card stat-all active-stat" data-stat-filter="All">
                                <div class="tsc-icon-wrap"><i class="bx bx-package"></i></div>
                                <div class="tsc-body">
                                    <div class="trans-stat-label">All Purchases</div>
                                    <div class="trans-stat-count"><?php echo number_format($cntAll); ?></div>
                                    <div class="trans-stat-amount"><?php echo fmtAmt($amtAll, $cur, $dec); ?></div>
                                </div>
                            </a>
                            <a href="javascript:void(0);" class="trans-stat-card stat-active" data-stat-filter="Pending">
                                <div class="tsc-icon-wrap"><i class="bx bx-time-five"></i></div>
                                <div class="tsc-body">
                                    <div class="trans-stat-label">Pending Payment</div>
                                    <div class="trans-stat-count"><?php echo number_format($cntPending); ?></div>
                                    <div class="trans-stat-amount"><?php echo fmtAmt($amtPending, $cur, $dec); ?></div>
                                </div>
                            </a>
                            <a href="javascript:void(0);" class="trans-stat-card stat-paid" data-stat-filter="Paid">
                                <div class="tsc-icon-wrap"><i class="bx bx-check-circle"></i></div>
                                <div class="tsc-body">
                                    <div class="trans-stat-label">Paid</div>
                                    <div class="trans-stat-count"><?php echo number_format($cntPaid); ?></div>
                                    <div class="trans-stat-amount"><?php echo fmtAmt($amtPaid, $cur, $dec); ?></div>
                                </div>
                            </a>
                            <a href="javascript:void(0);" class="trans-stat-card stat-draft" data-stat-filter="Draft">
                                <div class="tsc-icon-wrap"><i class="bx bx-pencil"></i></div>
                                <div class="tsc-body">
                                    <div class="trans-stat-label">Drafts</div>
                                    <div class="trans-stat-count"><?php echo number_format($cntDraft); ?></div>
                                    <div class="trans-stat-amount">&nbsp;</div>
                                </div>
                            </a>
                        </div>
                    </div>

                    <!-- ── Main Card ─────────────────────────────────────── -->
                    <div class="card">

                        <!-- Toolbar -->
                        <div class="trans-toolbar">

                            <!-- Status tabs -->
                            <ul class="nav trans-status-tabs gap-1" id="purchStatusTabs" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active purch-status-tab" data-status="All" href="javascript:void(0);">
                                        All <span class="trans-tab-count ms-1"><?php echo $ModAllCount; ?></span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link purch-status-tab" data-status="Pending" href="javascript:void(0);">
                                        Pending <span class="trans-tab-count ms-1 d-none"></span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link purch-status-tab" data-status="Paid" href="javascript:void(0);">
                                        Paid <span class="trans-tab-count ms-1 d-none"></span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link purch-status-tab" data-status="Cancelled" href="javascript:void(0);">
                                        Cancelled <span class="trans-tab-count ms-1 d-none"></span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link purch-status-tab" data-status="Draft" href="javascript:void(0);">
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
                                           placeholder="Bill # or vendor..." title="Search purchases">
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
                            </div>
                        </div>

                        <!-- Table -->
                        <div class="table-responsive">
                            <table class="table trans-table table-hover MainviewTable mb-0" id="purchTable">
                                <thead class="r2k-thead">
                                    <tr>
                                        <th style="width:36px">
                                            <div class="form-check mb-0">
                                                <input class="form-check-input table-chkbox purchHeaderCheck" type="checkbox">
                                            </div>
                                        </th>
                                        <th class="<?php echo $JwtData->GenSettings->SerialNoDisplay == 1 ? '' : 'd-none'; ?> table-serialno" style="width:44px">S.No</th>
                                        <th class="col-sortable cursor-pointer user-select-none" data-sort="Number">
                                            # Bill <i class="bx bx-sort-alt-2 ms-1 sort-icon" data-col="Number"></i>
                                        </th>
                                        <th class="col-sortable cursor-pointer user-select-none" data-sort="Amount">
                                            Amount <i class="bx bx-sort-alt-2 ms-1 sort-icon" data-col="Amount"></i>
                                        </th>
                                        <th>Payment Status</th>
                                        <th>Payment Mode</th>
                                        <th>Vendor</th>
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
                        <div class="row mx-3 my-2 justify-content-between align-items-center purchPagination" id="purchPagination">
                            <?php echo $ModPagination ?: ''; ?>
                        </div>

                    </div>

                    <?php $this->load->view('common/transactions/print_modals'); ?>

                    <!-- Sticky pagination -->
                    <div class="card mb-0 cust-sticky-pag" id="purchStickyPagination" style="display:none;">
                        <div class="card-body p-0">
                            <div class="row mx-3 my-2 justify-content-between align-items-center purchPagination"></div>
                        </div>
                    </div>

                </div>
            </div>

            <?php $this->load->view('common/imagepreview_modal'); ?>

            <?php
            $rpAccentColor = '#6f42c1'; $rpAccentBg = '#f0ebff';
            $rpPartyIcon   = 'bx-store'; $rpDocLabel  = 'Bill';
            $rpTotalIcon   = 'bx-cart';
            $rpNumId       = 'rpBillNum'; $rpDateId    = 'rpBillDate';
            $rpBtnLabel    = 'Issue Payment';
            $this->load->view('common/transactions/payment_modal');
            ?>

            <?php $this->load->view('common/modals/send_communication'); ?>

            <?php $this->load->view('common/footer_desc'); ?>

        </div>
    </div>
</div>

<?php $this->load->view('common/transactions/footer'); ?>

<script src="/js/common/communication.js"></script>
<script src="/js/transactions/attachments.js"></script>
<script src="/js/transactions/viewmodal.js"></script>
<script src="/js/transactions/a4_print.js"></script>
<script src="/js/transactions/purchases.js"></script>

<script>
const ModuleId     = 105;
const ModuleTable  = '#purchTable';
const ModulePag    = '.purchPagination';
const ModuleHeader = '.purchHeaderCheck';
const ModuleRow    = '.purchCheck';

$(function () {
    'use strict';

    // Bootstrap tooltips
    [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]')).map(function (el) {
        return new bootstrap.Tooltip(el, { container: 'body' });
    });

    Filter['Status'] = 'All';

    // ── Sticky pagination ──
    var $purchStaticPag = $('#purchPagination');
    var $purchStickyPag = $('#purchStickyPagination');
    function _syncPurchSticky() { $purchStickyPag.find('.purchPagination').html($purchStaticPag.html()); }
    function _togglePurchSticky() {
        if (!$purchStaticPag.length) return;
        var r = $purchStaticPag[0].getBoundingClientRect();
        var visible = r.top < $(window).height() && r.bottom > 0;
        if (visible) { $purchStickyPag.stop(true,true).fadeOut(150); }
        else { _syncPurchSticky(); $purchStickyPag.stop(true,true).fadeIn(150); }
    }
    $(window).on('scroll resize', _togglePurchSticky);
    _togglePurchSticky();

    // ── Stat card → filter ──────────────────────────────────────
    $(document).on('click', '[data-stat-filter]', function () {
        var status = $(this).data('stat-filter') || 'All';
        $('.trans-stat-card').removeClass('active-stat');
        $(this).addClass('active-stat');
        $('.purch-status-tab').removeClass('active');
        $('.purch-status-tab[data-status="' + status + '"]').addClass('active');
        Filter.Status = status;
        PageNo = 1;
        getPurchasesDetails();
    });

    // ── Status tabs ─────────────────────────────────────────────
    $(document).on('click', '.purch-status-tab', function (e) {
        e.preventDefault();
        $('.purch-status-tab').removeClass('active');
        $(this).addClass('active');
        $('.trans-stat-card').removeClass('active-stat');
        var status = $(this).data('status') || 'All';
        $('[data-stat-filter="' + status + '"]').addClass('active-stat');
        Filter.Status = status;
        PageNo = 1;
        getPurchasesDetails();
    });

    // ── Refresh ─────────────────────────────────────────────────
    $(document).on('click', '.pageRefresh', function (e) {
        e.preventDefault();
        PageNo = 1;
        getPurchasesDetails();
    });

    // ── Search ──────────────────────────────────────────────────
    $('#searchTransactionData').on('input', debounce(function () {
        Filter.Name = $.trim($(this).val());
        PageNo = 1;
        getPurchasesDetails();
    }, 1500));

    // ── Date filter ─────────────────────────────────────────────
    $(document).on('click', '.date-option', function () {
        $('.date-option').removeClass('active');
        $(this).addClass('active');
        var range = $(this).data('range') || '';
        var dates = getDateRange(range);
        $('#dateFilterLabel').text($.trim($(this).text()));
        Filter.DateFrom = dates.from;
        Filter.DateTo   = dates.to;
        PageNo = 1;
        getPurchasesDetails();
    });

    // ── Column sort ─────────────────────────────────────────────
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
        getPurchasesDetails();
    });

    // ── Pagination ──────────────────────────────────────────────
    $(document).on('click', '.purchPagination .page-link', function (e) {
        e.preventDefault();
        var match = ($(this).attr('href') || '').match(/\/(\d+)$/);
        if (match) { PageNo = parseInt(match[1]); getPurchasesDetails(); }
    });

    // ── Delete ──────────────────────────────────────────────────
    $(document).on('click', '.deletePurchase', function () {
        var uid = $(this).data('uid');
        var num = $(this).data('num') || '';
        Swal.fire({
            title: 'Delete Purchase Bill?',
            html : num ? 'Delete <strong>' + num + '</strong>? This cannot be undone.' : 'This cannot be undone.',
            icon : 'warning', showCancelButton: true,
            confirmButtonText: 'Delete', confirmButtonColor: '#d33',
        }).then(function (r) {
            if (!r.isConfirmed) return;
            $.ajax({
                url   : '/purchases/deletePurchase',
                method: 'POST',
                data  : { TransUID: uid, [CsrfName]: CsrfToken },
                success: function (resp) {
                    if (resp.Error) { Swal.fire({ icon: 'error', text: resp.Message }); }
                    else { getPurchasesDetails(); Swal.fire({ icon: 'success', text: resp.Message, timer: 1500, showConfirmButton: false }); }
                }
            });
        });
    });

    // ── Cancel ──────────────────────────────────────────────────
    $(document).on('click', '.purch-status-update', function () {
        var uid    = $(this).data('uid');
        var num    = $(this).data('num') || '';
        var status = $(this).data('status') || 'Cancelled';
        Swal.fire({
            title: 'Cancel Purchase Bill?',
            html : num ? 'Cancel <strong>' + num + '</strong>? This cannot be undone.' : 'This cannot be undone.',
            icon : 'warning', showCancelButton: true,
            confirmButtonText: 'Yes, Cancel It', confirmButtonColor: '#fd7e14',
        }).then(function (r) {
            if (!r.isConfirmed) return;
            $.ajax({
                url   : '/purchases/updatePurchaseStatus',
                method: 'POST',
                data  : { TransUID: uid, Status: status, [CsrfName]: CsrfToken },
                success: function (resp) {
                    if (resp.Error) { Swal.fire({ icon: 'error', text: resp.Message }); }
                    else { getPurchasesDetails(); Swal.fire({ icon: 'success', text: resp.Message, timer: 1500, showConfirmButton: false }); }
                }
            });
        });
    });

});

// ── Detail HTML builder ─────────────────────────────────────────
function _buildPurchDetailHtml(resp) {
    window._purchLastPrintData = resp;
    return _buildTransDetailHtml(resp, {
        partyLabel  : 'Vendor',
        typeIcon    : 'bx-cart',
        typeColor   : '#6f42c1',
        typeBg      : '#f0ebff',
        hasPayments : true,
    });
}

// ── Record Payment Modal ────────────────────────────────────────
(function () {
    'use strict';

    var _payTypes  = <?php echo json_encode(array_map(function($t) {
        return ['PaymentTypeUID' => (int)$t->PaymentTypeUID, 'Name' => (string)$t->Name, 'IsCash' => (int)$t->IsCash];
    }, $PaymentTypes ?? [])); ?>;
    var _bankAccts = <?php echo json_encode(array_values(array_map(function($b) {
        return ['BankAccountUID' => (int)$b->BankAccountUID, 'BankName' => (string)$b->BankName, 'AccountName' => (string)$b->AccountName, 'IsDefault' => (int)$b->IsDefault];
    }, array_filter($BankAccounts ?? [], function($b) { return !(int)$b->IsCash; })))); ?>;
    var _fpInstance = null;
    var _rpDropzone = null;
    var _currency   = '<?php echo htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹'); ?>';

    (function () {
        var $sel = $('#rpBankAccount').empty().append('<option value="">— Select bank account —</option>');
        $.each(_bankAccts, function (i, b) {
            $sel.append('<option value="' + b.BankAccountUID + '">' + _esc(b.BankName) + ' — ' + _esc(b.AccountName) + '</option>');
        });
    }());

    function renderPaymentTypes() {
        var $wrap = $('#rpPaymentTypes').empty();
        if (!_payTypes.length) {
            $wrap.html('<div class="text-muted" style="font-size:.8rem;"><i class="bx bx-loader-alt bx-spin"></i> Loading…</div>');
            return;
        }
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
        $('#rpBankRow').toggleClass('d-none', !!isCash);
        if (!isCash && !$('#rpBankAccount').val()) {
            var def = $.grep(_bankAccts, function(b) { return b.IsDefault === 1; });
            if (def.length) { $('#rpBankAccount').val(def[0].BankAccountUID); }
        }
    }

    $('#recordPaymentModal').on('shown.bs.modal', function () {
        if (!_fpInstance) {
            _fpInstance = flatpickr('#rpPaymentDate', {
                dateFormat   : 'Y-m-d',
                altInput     : true,
                altFormat    : 'd M Y',
                maxDate      : 'today',
                disableMobile: true,
                defaultDate  : 'today',
                appendTo: document.querySelector('#recordPaymentModal .modal-dialog'),
            });
        } else {
            _fpInstance.setDate(new Date(), false);
        }
        if (!_rpDropzone && typeof Dropzone !== 'undefined') {
            Dropzone.autoDiscover = false;
            _rpDropzone = new Dropzone('#rpAttachDropzone', {
                url              : '#',
                autoProcessQueue : false,
                maxFiles         : 3,
                maxFilesize      : 3,
                acceptedFiles    : '.pdf,.jpg,.jpeg,.png',
                parallelUploads  : 3,
                clickable        : true,
                previewTemplate  : '<div class="dz-preview dz-file-preview"><div class="dz-details"><div class="dz-filename"><span data-dz-name></span></div><div class="dz-size"><span data-dz-size></span></div></div><div class="dz-error-message"><span data-dz-errormessage></span></div><a class="dz-remove" href="javascript:undefined;" data-dz-remove>Remove</a></div>',
                init: function () {
                    this.on('maxfilesexceeded', function (file) { this.removeFile(file); Swal.fire({ icon: 'warning', text: 'Maximum 3 attachments allowed.' }); });
                    this.on('error', function (file, msg) {
                        if (file.size > 3 * 1024 * 1024) { this.removeFile(file); Swal.fire({ icon: 'warning', text: 'Each file must be 3 MB or smaller.' }); }
                    });
                }
            });
        }
    });

    $(document).on('click', '.purchReceivePayment', function () {
        var uid     = $(this).data('uid');
        var num     = $(this).data('num')     || '';
        var date    = $(this).data('date')    || '';
        var party   = $(this).data('party')   || '';
        var total   = parseFloat($(this).data('total'))   || 0;
        var paid    = parseFloat($(this).data('paid'))    || 0;
        var pending = parseFloat($(this).data('pending')) || 0;

        $('#rpTransUID').val(uid);
        $('#rpBillNum').text(num);
        $('#rpBillDate').text(date);
        $('#rpPartyName').text(party);
        $('#rpTotalCard').text(_currency + ' ' + total.toFixed(2));
        $('#rpPaidCard').text(_currency + ' ' + paid.toFixed(2));
        $('#rpBalanceCard').text(_currency + ' ' + pending.toFixed(2));
        $('#rpAmount').val(pending.toFixed(2)).attr('max', pending);
        $('#rpCurrencySymbol').text(_currency);
        $('#rpReferenceNo').val('');
        $('#rpNotes').val('');
        $('#rpBankAccount').val('');

        if (_rpDropzone) { _rpDropzone.removeAllFiles(true); }
        renderPaymentTypes();
        $('#recordPaymentModal').modal('show');
    });

    $(document).on('click', '.rp-type-pill', function () {
        $('.rp-type-pill').removeClass('active btn-primary').addClass('btn-outline-secondary');
        $(this).addClass('active btn-primary').removeClass('btn-outline-secondary');
        $('#rpPaymentTypeUID').val($(this).data('uid'));
        $('#rpIsCash').val($(this).data('iscash'));
        toggleBankRow();
    });

    $('#btnSubmitPayment').on('click', function () {
        var transUID       = parseInt($('#rpTransUID').val(), 10);
        var paymentTypeUID = parseInt($('#rpPaymentTypeUID').val(), 10);
        var amount         = parseFloat($('#rpAmount').val()) || 0;
        var paymentDate    = $('#rpPaymentDate').val() || new Date().toISOString().split('T')[0];
        var bankAccountUID = parseInt($('#rpBankAccount').val(), 10) || 0;
        var referenceNo    = $.trim($('#rpReferenceNo').val());
        var notes          = $.trim($('#rpNotes').val());

        if (!transUID)       { Swal.fire({ icon: 'warning', text: 'Invalid purchase bill.' }); return; }
        if (!paymentTypeUID) { Swal.fire({ icon: 'warning', text: 'Please select a payment type.' }); return; }
        if (amount <= 0)     { Swal.fire({ icon: 'warning', text: 'Enter a valid amount.' }); return; }
        var isCash = parseInt($('#rpIsCash').val(), 10);
        if (!isCash && !bankAccountUID) { Swal.fire({ icon: 'warning', text: 'Please select a bank account for this payment type.' }); return; }

        var $btn = $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Saving…');

        var fd = new FormData();
        fd.append('TransUID',       transUID);
        fd.append('PaymentTypeUID', paymentTypeUID);
        fd.append('Amount',         amount);
        fd.append('PaymentDate',    paymentDate);
        fd.append('BankAccountUID', bankAccountUID || '');
        fd.append('ReferenceNo',    referenceNo);
        fd.append('Notes',          notes);
        fd.append('CurrentPage',    PageNo || 1);
        fd.append('RowLimit',       RowLimit || 10);
        fd.append('Filter',         JSON.stringify(Filter || {}));
        fd.append(CsrfName,         CsrfToken);
        if (_rpDropzone) { _rpDropzone.files.forEach(function(file) { fd.append('PaymentFiles[]', file); }); }

        $.ajax({
            url         : '/purchases/recordPurchasePayment',
            method      : 'POST',
            data        : fd,
            processData : false,
            contentType : false,
            success: function (resp) {
                $btn.prop('disabled', false).html('<i class="bx bx-check me-1"></i> Issue Payment');
                if (resp.Error) {
                    showToastNotification(resp.Message, 'error');
                } else {
                    $('#recordPaymentModal').modal('hide');
                    if (resp.RecordHtmlData) {
                        $(ModuleTable + ' tbody').html(resp.RecordHtmlData);
                        $(ModulePag).html(resp.Pagination || '');
                        [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]')).map(function (el) {
                            return new bootstrap.Tooltip(el, { container: 'body' });
                        });
                        if (resp.SummaryStats) { updateSummaryStats(resp.SummaryStats); }
                    }
                    showToastNotification(resp.Message, 'success');
                }
            },
            error: function () {
                $btn.prop('disabled', false).html('<i class="bx bx-check me-1"></i> Issue Payment');
                showToastNotification('Request failed. Try again.', 'error');
            }
        });
    });

    function updateSummaryStats(stats) {
        var cur = (typeof CurrencySymbol !== 'undefined' && CurrencySymbol) ? CurrencySymbol : '₹';
        var dec = 2;
        var cntAll = 0, amtAll = 0, cntPending = 0, amtPending = 0, cntPaid = 0, amtPaid = 0, cntDraft = 0;
        if (stats) {
            for (var key in stats) {
                if (stats.hasOwnProperty(key)) { cntAll += parseInt(stats[key].count || 0); amtAll += parseFloat(stats[key].amount || 0); }
            }
            cntPending = (stats.Received ? parseInt(stats.Received.count || 0) : 0) + (stats.Partial ? parseInt(stats.Partial.count || 0) : 0);
            amtPending = (stats.Received ? parseFloat(stats.Received.amount || 0) : 0) + (stats.Partial ? parseFloat(stats.Partial.amount || 0) : 0);
            cntPaid  = stats.Paid  ? parseInt(stats.Paid.count   || 0) : 0;
            amtPaid  = stats.Paid  ? parseFloat(stats.Paid.amount || 0) : 0;
            cntDraft = stats.Draft ? parseInt(stats.Draft.count   || 0) : 0;
        }
        function fmtAmt(val) {
            return cur + ' ' + parseFloat(val).toLocaleString('en-IN', { minimumFractionDigits: dec, maximumFractionDigits: dec });
        }
        $('.stat-all    .trans-stat-count').text(cntAll.toLocaleString());
        $('.stat-all    .trans-stat-amount').text(fmtAmt(amtAll));
        $('.stat-active .trans-stat-count').text(cntPending.toLocaleString());
        $('.stat-active .trans-stat-amount').text(fmtAmt(amtPending));
        $('.stat-paid   .trans-stat-count').text(cntPaid.toLocaleString());
        $('.stat-paid   .trans-stat-amount').text(fmtAmt(amtPaid));
        $('.stat-draft  .trans-stat-count').text(cntDraft.toLocaleString());
    }
}());
</script>
