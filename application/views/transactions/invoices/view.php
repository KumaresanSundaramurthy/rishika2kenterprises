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

                    $activeInvStatuses = ['Issued', 'Partial', 'Paid'];
                    $cntAll      = array_sum(array_map(fn($s) => $stats[$s]['count']  ?? 0, $activeInvStatuses));
                    $amtAll      = array_sum(array_map(fn($s) => $stats[$s]['amount'] ?? 0, $activeInvStatuses));
                    $cntPending  = ($stats['Issued']['count']   ?? 0) + ($stats['Partial']['count'] ?? 0);
                    $amtPending  = ($stats['Issued']['amount']  ?? 0) + ($stats['Partial']['amount'] ?? 0);
                    $cntPaid     = $stats['Paid']['count']      ?? 0;
                    $amtPaid     = $stats['Paid']['amount']     ?? 0;
                    $cntDraft    = $stats['Draft']['count']     ?? 0;
                    $cntOverdue  = $stats['Overdue']['count']   ?? 0;

                    function fmtAmt($val, $sym, $dec) {
                        return $sym . ' ' . number_format((float)$val, $dec, '.', ',');
                    }
                    ?>

                    <!-- ── Page Header ──────────────────────────────────────── -->
                    <div class="trans-page-header">
                        <div class="d-flex align-items-center gap-3">
                            <div class="trans-ph-icon" style="background:#dbeafe;">
                                <i class="bx bx-receipt" style="color:#3b82f6;"></i>
                            </div>
                            <div>
                                <h5 class="trans-ph-title mb-0"><?php echo htmlspecialchars($PageTitle ?? 'Invoices'); ?></h5>
                                <?php if (!empty($PageDescription)): ?>
                                <div class="text-muted" style="font-size:.76rem;"><?php echo htmlspecialchars($PageDescription); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <a href="/invoices/create" class="btn btn-primary me-1">
                            <i class="bx bx-plus me-1"></i>New Invoice
                        </a>
                    </div>

                    <!-- ── Stat Cards ──────────────────────────────────────── -->
                    <div class="trans-stats-section">
                        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;">
                            <a href="javascript:void(0);" class="trans-stat-card stat-all active-stat" data-stat-filter="All">
                                <div class="tsc-icon-wrap"><i class="bx bx-receipt"></i></div>
                                <div class="tsc-body">
                                    <div class="trans-stat-label">All Invoices</div>
                                    <div class="trans-stat-count"><?php echo number_format($cntAll); ?></div>
                                    <div class="trans-stat-amount"><?php echo fmtAmt($amtAll, $cur, $dec); ?></div>
                                </div>
                            </a>
                            <a href="javascript:void(0);" class="trans-stat-card stat-active" data-stat-filter="InvPending">
                                <div class="tsc-icon-wrap"><i class="bx bx-time-five"></i></div>
                                <div class="tsc-body">
                                    <div class="trans-stat-label">Pending</div>
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

                    <!-- ── Main Card ───────────────────────────────────────── -->
                    <div class="card">

                        <!-- Toolbar -->
                        <div class="trans-toolbar">

                            <!-- Status tabs -->
                            <ul class="nav trans-status-tabs gap-1" id="invStatusTabs" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active inv-status-tab" data-status="All" href="javascript:void(0);">
                                        All <span class="inv-tab-count ms-1"><?php echo $ModAllCount; ?></span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link inv-status-tab" data-status="InvPending" href="javascript:void(0);">
                                        Pending <span class="inv-tab-count ms-1 d-none"></span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link inv-status-tab" data-status="Paid" href="javascript:void(0);">
                                        Paid <span class="inv-tab-count ms-1 d-none"></span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link inv-status-tab" data-status="Cancelled" href="javascript:void(0);">
                                        Cancelled <span class="inv-tab-count ms-1 d-none"></span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link inv-status-tab" data-status="Draft" href="javascript:void(0);">
                                        Drafts <span class="inv-tab-count ms-1 d-none"></span>
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

                    <!-- Sticky pagination -->
                    <div class="card mb-0 cust-sticky-pag" id="invStickyPagination" style="display:none;">
                        <div class="card-body p-0">
                            <div class="row mx-3 my-2 justify-content-between align-items-center invPagination"></div>
                        </div>
                    </div>

                </div>
            </div>

            <?php $this->load->view('common/imagepreview_modal'); ?>

            <?php
            $rpAccentColor = '#0d6efd'; $rpAccentBg = '#e8f0fe';
            $rpPartyIcon   = 'bx-user';  $rpDocLabel  = 'Invoice';
            $rpTotalIcon   = 'bx-receipt';
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

    // ── Sticky pagination ──
    var $invStaticPag = $('#invPagination');
    var $invStickyPag = $('#invStickyPagination');
    function _syncInvSticky() { $invStickyPag.find('.invPagination').html($invStaticPag.html()); }
    function _toggleInvSticky() {
        if (!$invStaticPag.length) return;
        var r = $invStaticPag[0].getBoundingClientRect();
        var visible = r.top < $(window).height() && r.bottom > 0;
        if (visible) { $invStickyPag.stop(true,true).fadeOut(150); }
        else { _syncInvSticky(); $invStickyPag.stop(true,true).fadeIn(150); }
    }
    $(window).on('scroll resize', _toggleInvSticky);
    _toggleInvSticky();

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
                    if (resp.Error) {
                        Swal.fire({ icon: 'error', text: resp.Message });
                    } else {
                        getInvoicesDetails();
                        var msg = resp.Message || 'Invoice cancelled.';
                        if (resp.CustomerBalance !== undefined) {
                            msg += '<br><small class="text-muted mt-1 d-block">Customer Balance: <strong>' +
                                   resp.CustomerBalanceType + ' &#8377;' +
                                   parseFloat(resp.CustomerBalance).toLocaleString('en-IN', { minimumFractionDigits: 2 }) +
                                   '</strong></small>';
                        }
                        Swal.fire({ icon: 'success', html: msg, timer: 2500, showConfirmButton: false });
                    }
                },
                error: function () {
                    Swal.fire({ icon: 'error', text: 'Request failed. Please try again.' });
                }
            });
        });
    });

});

function updateSummaryStats(stats) {
    if (!stats) return;
    var cur = '<?php echo addslashes(htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹')); ?>';
    var dec = <?php echo (int)($JwtData->GenSettings->DecimalPoints ?? 2); ?>;
    function cnt(s) { return (stats[s] && stats[s].count)  ? parseInt(stats[s].count)   : 0; }
    function amt(s) { return (stats[s] && stats[s].amount) ? parseFloat(stats[s].amount) : 0; }
    function fmtAmt(v) {
        return cur + ' ' + parseFloat(v).toLocaleString('en-IN', { minimumFractionDigits: dec, maximumFractionDigits: dec });
    }
    var cntAll     = cnt('Issued') + cnt('Partial') + cnt('Paid');
    var amtAll     = amt('Issued') + amt('Partial') + amt('Paid');
    var cntPending = cnt('Issued') + cnt('Partial');
    var amtPending = amt('Issued') + amt('Partial');
    var cntPaid    = cnt('Paid'),  amtPaid  = amt('Paid');
    var cntDraft   = cnt('Draft');

    $('.stat-all    .trans-stat-count').text(cntAll.toLocaleString());
    $('.stat-all    .trans-stat-amount').text(fmtAmt(amtAll));
    $('.stat-active .trans-stat-count').text(cntPending.toLocaleString());
    $('.stat-active .trans-stat-amount').text(fmtAmt(amtPending));
    $('.stat-paid   .trans-stat-count').text(cntPaid.toLocaleString());
    $('.stat-paid   .trans-stat-amount').text(fmtAmt(amtPaid));
    $('.stat-draft  .trans-stat-count').text(cntDraft.toLocaleString());
}

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

// ── Init shared payment modal (invoices) ─────────────────────────────────────
initRecordPaymentModal(
    <?php echo json_encode(array_map(function($t) {
        return ['PaymentTypeUID' => (int)$t->PaymentTypeUID, 'Name' => (string)$t->Name, 'IsCash' => (int)$t->IsCash];
    }, $PaymentTypes ?? [])); ?>,
    <?php echo json_encode(array_values(array_map(function($b) {
        return ['BankAccountUID' => (int)$b->BankAccountUID, 'BankName' => (string)$b->BankName, 'AccountName' => (string)$b->AccountName, 'IsDefault' => (int)$b->IsDefault];
    }, array_filter($BankAccounts ?? [], function($b) { return !(int)$b->IsCash; })))); ?>,
    '<?php echo addslashes(htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹')); ?>'
);

window.rpAfterSuccess = function (resp) {
    if (resp.SummaryStats) updateSummaryStats(resp.SummaryStats);
};

// Open modal when "Receive Payment" clicked on an invoice row
$(document).on('click', '.invReceivePayment', function () {
    window.rpOpenModal({
        transUID  : $(this).data('uid'),
        submitUrl : '/invoices/recordInvoicePayment',
        docNum    : $(this).data('num')   || '',
        docDate   : $(this).data('date')  || '',
        partyName : $(this).data('party') || '',
        total     : parseFloat($(this).data('total'))   || 0,
        paid      : parseFloat($(this).data('paid'))    || 0,
        pending   : parseFloat($(this).data('pending')) || 0,
    });
});

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
