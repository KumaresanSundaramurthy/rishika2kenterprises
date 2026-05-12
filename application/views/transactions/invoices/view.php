<?php defined('BASEPATH') or exit('No direct script access allowed');
$this->load->view('common/transactions/header'); ?>

<div class="layout-wrapper layout-horizontal layout-content-navbar">
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
                    $cntPending  = ($stats['Issued']['count']   ?? 0) + ($stats['Sent']['count'] ?? 0) + ($stats['Partial']['count'] ?? 0);
                    $cntPaid     = $stats['Paid']['count']      ?? 0;
                    $cntDraft    = $stats['Draft']['count']     ?? 0;
                    $cntOverdue  = $stats['Overdue']['count']   ?? 0;

                    $amtAll      = array_sum(array_column($stats, 'amount'));
                    $amtPending  = ($stats['Issued']['amount']  ?? 0) + ($stats['Sent']['amount'] ?? 0) + ($stats['Partial']['amount'] ?? 0);
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
                            <a href="javascript:void(0);" class="trans-stat-card stat-active" data-stat-filter="InvPending">
                                <div class="trans-stat-label">Pending</div>
                                <div class="trans-stat-count"><?php echo number_format($cntPending); ?></div>
                                <div class="trans-stat-amount"><?php echo fmtAmt($amtPending, $cur, $dec); ?></div>
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
                                    <a class="nav-link inv-status-tab" data-status="InvPending" href="javascript:void(0);">
                                        Pending <span class="trans-tab-count ms-1 d-none"></span>
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
                                            # Bill <i class="bx bx-sort-alt-2 ms-1 sort-icon" data-col="Number"></i>
                                        </th>
                                        <th class="col-sortable cursor-pointer user-select-none" data-sort="Amount">
                                            Amount <i class="bx bx-sort-alt-2 ms-1 sort-icon" data-col="Amount"></i>
                                        </th>
                                        <th>Payment Status</th>
                                        <th>Payment Mode</th>
                                        <th>Customer</th>
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

            <?php $this->load->view('common/imagepreview_modal'); ?>

            <?php
            $rpAccentColor = '#0d6efd'; $rpAccentBg = '#e8f0fe';
            $rpPartyIcon   = 'bx-user';  $rpDocLabel  = 'Invoice';
            $rpTotalIcon   = 'bx-receipt';
            $rpNumId       = 'rpInvNum'; $rpDateId    = 'rpInvDate';
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
<script src="/js/transactions/invoices.js"></script>

<script>
const ModuleId     = 103;
const ModuleTable  = '#invTable';
const ModulePag    = '.invPagination';
const ModuleHeader = '.invHeaderCheck';
const ModuleRow    = '.invCheck';

$(function () {
    'use strict';

    // Initialize Bootstrap tooltips — container:'body' prevents tooltip div from
    // firing mouseleave on the icon, which caused the heartbeat flicker.
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl, { container: 'body' });
    });

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

    // ── Cancel Invoice ──────────────────────────────────────
    $(document).on('click', '.cancelInvoice', function () {
        var uid = $(this).data('uid');
        var num = $(this).data('num') || '';
        Swal.fire({
            title: 'Cancel Invoice?',
            html : num ? 'Cancel <strong>' + num + '</strong>? This cannot be undone.' : 'This cannot be undone.',
            icon : 'warning', showCancelButton: true,
            confirmButtonText: 'Yes, Cancel It', confirmButtonColor: '#fd7e14',
        }).then(function (r) {
            if (!r.isConfirmed) return;
            $.ajax({
                url   : '/invoices/updateInvoiceStatus',
                method: 'POST',
                data  : { TransUID: uid, Status: 'Cancelled', [CsrfName]: CsrfToken },
                success: function (resp) {
                    if (resp.Error) { Swal.fire({ icon:'error', text: resp.Message }); }
                    else { getInvoicesDetails(); Swal.fire({ icon:'success', text: resp.Message, timer:1500, showConfirmButton:false }); }
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

// ── Record Payment Modal ──────────────────────────────────────────
(function () {
    'use strict';

    // Data injected at page render — zero extra requests
    var _payTypes  = <?php echo json_encode(array_map(function($t) {
        return ['PaymentTypeUID' => (int)$t->PaymentTypeUID, 'Name' => (string)$t->Name, 'IsCash' => (int)$t->IsCash];
    }, $PaymentTypes ?? [])); ?>;
    var _bankAccts = <?php echo json_encode(array_values(array_map(function($b) {
        return ['BankAccountUID' => (int)$b->BankAccountUID, 'BankName' => (string)$b->BankName, 'AccountName' => (string)$b->AccountName, 'IsDefault' => (int)$b->IsDefault];
    }, array_filter($BankAccounts ?? [], function($b) { return !(int)$b->IsCash; })))); ?>;
    var _fpInstance = null;
    var _rpDropzone  = null;
    var _currency   = '<?php echo htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹'); ?>';

    // Populate bank account select once from pre-loaded data
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

    // Init flatpickr once modal is fully visible; reset to today on each open
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
                        if (file.size > 3 * 1024 * 1024) {
                            this.removeFile(file);
                            Swal.fire({ icon: 'warning', text: 'Each file must be 3 MB or smaller.' });
                        }
                    });
                }
            });
        }
    });

    // Open modal when "Receive Payment" clicked
    $(document).on('click', '.invReceivePayment', function () {
        var uid     = $(this).data('uid');
        var num     = $(this).data('num')     || '';
        var date    = $(this).data('date')    || '';
        var party   = $(this).data('party')   || '';
        var total   = parseFloat($(this).data('total'))   || 0;
        var paid    = parseFloat($(this).data('paid'))    || 0;
        var pending = parseFloat($(this).data('pending')) || 0;

        $('#rpTransUID').val(uid);
        $('#rpInvNum').text(num);
        $('#rpInvDate').text(date);
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
        var paymentDate    = $('#rpPaymentDate').val() || new Date().toISOString().split('T')[0];
        var bankAccountUID = parseInt($('#rpBankAccount').val(), 10) || 0;
        var referenceNo    = $.trim($('#rpReferenceNo').val());
        var notes          = $.trim($('#rpNotes').val());

        if (!transUID)       { Swal.fire({ icon: 'warning', text: 'Invalid invoice.' }); return; }
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
            url         : '/invoices/recordInvoicePayment',
            method      : 'POST',
            data        : fd,
            processData : false,
            contentType : false,
            success: function (resp) {
                $btn.prop('disabled', false).html('<i class="bx bx-check me-1"></i> Record Payment');
                if (resp.Error) {
                    showToastNotification(resp.Message, 'error');
                } else {
                    $('#recordPaymentModal').modal('hide');
                    if (resp.RecordHtmlData) {
                        $(ModuleTable + ' tbody').html(resp.RecordHtmlData);
                        $(ModulePag).html(resp.Pagination || '');
                        
                        // Reinitialize tooltips for new content
                        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                        tooltipTriggerList.map(function (tooltipTriggerEl) {
                            return new bootstrap.Tooltip(tooltipTriggerEl, { container: 'body' });
                        });
                        
                        if (resp.SummaryStats) {
                            updateSummaryStats(resp.SummaryStats);
                        }
                    }
                    showToastNotification(resp.Message, 'success');
                }
            },
            error: function () {
                $btn.prop('disabled', false).html('<i class="bx bx-check me-1"></i> Record Payment');
                showToastNotification('Request failed. Try again.', 'error');
            }
        });
    });

    // Update summary stats cards
    function updateSummaryStats(stats) {
        var cur = (typeof CurrencySymbol !== 'undefined' && CurrencySymbol) ? CurrencySymbol : '₹';
        var dec = 2;
        
        var cntAll = 0, amtAll = 0;
        var cntPending = 0, amtPending = 0;
        var cntPaid = 0, amtPaid = 0;
        var cntDraft = 0;
        
        if (stats) {
            for (var key in stats) {
                if (stats.hasOwnProperty(key)) {
                    cntAll += parseInt(stats[key].count || 0);
                    amtAll += parseFloat(stats[key].amount || 0);
                }
            }
            cntIssued = (stats.Issued ? parseInt(stats.Issued.count || 0) : 0) + 
                        (stats.Sent ? parseInt(stats.Sent.count || 0) : 0) + 
                        (stats.Partial ? parseInt(stats.Partial.count || 0) : 0);
            amtIssued = (stats.Issued ? parseFloat(stats.Issued.amount || 0) : 0) + 
                        (stats.Sent ? parseFloat(stats.Sent.amount || 0) : 0) + 
                        (stats.Partial ? parseFloat(stats.Partial.amount || 0) : 0);
            cntPaid = stats.Paid ? parseInt(stats.Paid.count || 0) : 0;
            amtPaid = stats.Paid ? parseFloat(stats.Paid.amount || 0) : 0;
            cntDraft = stats.Draft ? parseInt(stats.Draft.count || 0) : 0;
        }
        
        function fmtAmt(val) {
            return cur + ' ' + parseFloat(val).toLocaleString('en-IN', { minimumFractionDigits: dec, maximumFractionDigits: dec });
        }
        
        $('.stat-all .trans-stat-count').text(cntAll.toLocaleString());
        $('.stat-all .trans-stat-amount').text(fmtAmt(amtAll));
        
        $('.stat-active .trans-stat-count').text(cntIssued.toLocaleString());
        $('.stat-active .trans-stat-amount').text(fmtAmt(amtIssued));
        
        $('.stat-paid .trans-stat-count').text(cntPaid.toLocaleString());
        $('.stat-paid .trans-stat-amount').text(fmtAmt(amtPaid));
        
        $('.stat-draft .trans-stat-count').text(cntDraft.toLocaleString());
    }

}());

// Update invoice row after payment without full page reload
function updateInvoiceRow(invoice, payments, paidTotal) {
    var $row = $('tr[data-trans-uid="' + invoice.TransUID + '"]');
    if (!$row.length) return;
    
    // Update paid amount
    $row.find('.inv-paid-amt').text('₹' + parseFloat(paidTotal || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
    
    // Update balance amount
    var balance = parseFloat(invoice.BalanceAmount || 0);
    $row.find('.inv-balance-amt').text('₹' + balance.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
    
    // Update status badge
    var statusBadge = '';
    if (invoice.DocStatus === 'Paid') {
        statusBadge = '<span class="badge bg-label-success">Paid</span>';
    } else if (invoice.DocStatus === 'Partial') {
        statusBadge = '<span class="badge bg-label-warning">Partial</span>';
    } else if (invoice.DocStatus === 'Issued') {
        statusBadge = '<span class="badge bg-label-primary">Issued</span>';
    } else if (invoice.DocStatus === 'Draft') {
        statusBadge = '<span class="badge bg-label-secondary">Draft</span>';
    }
    $row.find('.inv-status-badge').html(statusBadge);
    
    // Update payment mode badges
    var paymentHtml = '';
    if (payments && payments.length > 0) {
        payments.forEach(function(p) {
            paymentHtml += '<span class="badge bg-label-info me-1">' + (p.PaymentTypeName || 'Payment') + '</span>';
        });
        var hasAttach = invoice.PaymentAttachmentCount > 0;
        if (hasAttach) {
            paymentHtml += '<button type="button" class="btn btn-icon btn-sm transPayAttachBtn" data-uid="' + invoice.TransUID + '" data-num="' + (invoice.UniqueNumber || '') + '" data-url="/invoices/getPaymentAttachments" title="View Payment Attachments"><i class="bx bx-paperclip text-primary"></i></button>';
        }
    } else {
        paymentHtml = '<span class="text-muted">—</span>';
    }
    $row.find('.inv-payment-mode').html(paymentHtml);

}
</script>
