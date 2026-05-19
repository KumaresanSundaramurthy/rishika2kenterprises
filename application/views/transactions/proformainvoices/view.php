<?php defined('BASEPATH') or exit('No direct script access allowed');
$this->load->view('common/transactions/header'); ?>

<div class="layout-wrapper layout-horizontal layout-content-navbar">
    <div class="layout-container">

        <?php $this->load->view('common/menu_view'); ?>

        <div class="layout-page">
            <div class="content-wrapper">
                <div class="container-xxl flex-grow-1 container-p-y">

                    <?php
                    $stats        = $SummaryStats ?? [];
                    $cur          = htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹');
                    $dec          = $JwtData->GenSettings->DecimalPoints ?? 2;

                    $cntAll       = array_sum(array_column(
                        array_filter($stats, fn($k) => !in_array($k, ['Draft','Cancelled','Expired']), ARRAY_FILTER_USE_KEY),
                        'count'
                    ));
                    $amtAll       = array_sum(array_column(
                        array_filter($stats, fn($k) => !in_array($k, ['Draft','Cancelled','Expired']), ARRAY_FILTER_USE_KEY),
                        'amount'
                    ));
                    $cntSent      = $stats['Sent']['count']      ?? 0;
                    $amtSent      = $stats['Sent']['amount']     ?? 0;
                    $cntConverted = $stats['Converted']['count'] ?? 0;
                    $cntDraft     = $stats['Draft']['count']     ?? 0;

                    function fmtAmt($val, $sym, $dec) {
                        return $sym . ' ' . number_format((float)$val, $dec, '.', ',');
                    }
                    ?>

                    <!-- ── Page Header ──────────────────────────────────────── -->
                    <div class="trans-page-header">
                        <div class="d-flex align-items-center gap-3">
                            <div class="trans-ph-icon" style="background:#ede9fe;">
                                <i class="bx bx-file-blank" style="color:#7c3aed;"></i>
                            </div>
                            <h5 class="trans-ph-title">Pro Forma Invoices</h5>
                        </div>
                        <a href="/proforma/create" class="btn btn-primary">
                            <i class="bx bx-plus me-1"></i>New Pro Forma
                        </a>
                    </div>

                    <!-- ── Stat Cards ─────────────────────────────────────── -->
                    <div class="trans-stats-section">
                        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;">
                            <a href="javascript:void(0);" class="trans-stat-card stat-all active-stat" data-stat-filter="All">
                                <div class="tsc-icon-wrap"><i class="bx bx-file-blank"></i></div>
                                <div class="tsc-body">
                                    <div class="trans-stat-label">All Pro Formas</div>
                                    <div class="trans-stat-count"><?php echo number_format($cntAll); ?></div>
                                    <div class="trans-stat-amount"><?php echo fmtAmt($amtAll, $cur, $dec); ?></div>
                                </div>
                            </a>
                            <a href="javascript:void(0);" class="trans-stat-card stat-active" data-stat-filter="Sent">
                                <div class="tsc-icon-wrap"><i class="bx bx-send"></i></div>
                                <div class="tsc-body">
                                    <div class="trans-stat-label">Sent</div>
                                    <div class="trans-stat-count"><?php echo number_format($cntSent); ?></div>
                                    <div class="trans-stat-amount"><?php echo fmtAmt($amtSent, $cur, $dec); ?></div>
                                </div>
                            </a>
                            <a href="javascript:void(0);" class="trans-stat-card" data-stat-filter="Converted">
                                <div class="tsc-icon-wrap"><i class="bx bx-transfer-alt"></i></div>
                                <div class="tsc-body">
                                    <div class="trans-stat-label">Converted</div>
                                    <div class="trans-stat-count"><?php echo number_format($cntConverted); ?></div>
                                    <div class="trans-stat-amount">&nbsp;</div>
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

                    <!-- ── Main Card ──────────────────────────────────────── -->
                    <div class="card">

                        <!-- Toolbar -->
                        <div class="trans-toolbar">
                            <ul class="nav trans-status-tabs gap-1" id="pfStatusTabs" role="tablist">
                                <li class="nav-item"><a class="nav-link active pf-status-tab" data-status="All" href="javascript:void(0);">All <span class="trans-tab-count ms-1"><?php echo $ModAllCount; ?></span></a></li>
                                <li class="nav-item"><a class="nav-link pf-status-tab" data-status="Sent" href="javascript:void(0);">Sent <span class="pf-tab-count trans-tab-count ms-1 d-none"></span></a></li>
                                <li class="nav-item"><a class="nav-link pf-status-tab" data-status="Converted" href="javascript:void(0);">Converted <span class="pf-tab-count trans-tab-count ms-1 d-none"></span></a></li>
                                <li class="nav-item"><a class="nav-link pf-status-tab" data-status="Expired" href="javascript:void(0);">Expired <span class="pf-tab-count trans-tab-count ms-1 d-none"></span></a></li>
                                <li class="nav-item"><a class="nav-link pf-status-tab" data-status="Cancelled" href="javascript:void(0);">Cancelled <span class="pf-tab-count trans-tab-count ms-1 d-none"></span></a></li>
                                <li class="nav-item"><a class="nav-link pf-status-tab" data-status="Draft" href="javascript:void(0);">Drafts <span class="pf-tab-count trans-tab-count ms-1 d-none"></span></a></li>
                            </ul>

                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <a href="javascript:void(0);" class="btn btn-sm btn-outline-secondary p-1 pageRefresh" title="Refresh"><i class="bx bx-refresh fs-5"></i></a>
                                <div class="input-group input-group-sm" style="width:210px">
                                    <span class="input-group-text bg-transparent border-end-0"><i class="bx bx-search text-muted"></i></span>
                                    <input type="text" class="form-control border-start-0" id="searchTransactionData" placeholder="PF # or customer...">
                                </div>
                                <div class="dropdown">
                                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
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
                                        <li><a class="dropdown-item date-option fw-bold" data-range="fy_25_26"><i class="bx bxs-star text-warning me-1"></i>FY 25-26</a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Table -->
                        <div class="table-responsive">
                            <table class="table trans-table table-hover MainviewTable mb-0" id="pfTable">
                                <thead class="r2k-thead">
                                    <tr>
                                        <th style="width:36px"><div class="form-check mb-0"><input class="form-check-input table-chkbox pfHeaderCheck" type="checkbox"></div></th>
                                        <th class="<?php echo $JwtData->GenSettings->SerialNoDisplay == 1 ? '' : 'd-none'; ?> table-serialno" style="width:44px">S.No</th>
                                        <th class="col-sortable cursor-pointer user-select-none" data-sort="Number">Pro Forma # <i class="bx bx-sort-alt-2 ms-1 sort-icon" data-col="Number"></i></th>
                                        <th class="col-sortable cursor-pointer user-select-none" data-sort="Amount">Amount <i class="bx bx-sort-alt-2 ms-1 sort-icon" data-col="Amount"></i></th>
                                        <th>Status</th>
                                        <th>Customer</th>
                                        <th class="col-sortable cursor-pointer user-select-none" data-sort="Date">Valid Until <i class="bx bx-sort-alt-2 ms-1 sort-icon" data-col="Date"></i></th>
                                        <th>Last Updated</th>
                                        <th style="width:50px"></th>
                                    </tr>
                                </thead>
                                <tbody class="r2k-tbody table-border-bottom-0">
                                    <?php echo $ModRowData; ?>
                                </tbody>
                            </table>
                        </div>

                        <hr class="my-0">
                        <div class="row mx-3 my-2 justify-content-between align-items-center pfPagination" id="pfPagination">
                            <?php echo $ModPagination ?: ''; ?>
                        </div>
                    </div>

                    <?php $this->load->view('common/transactions/print_modals'); ?>

                    <div class="card mb-0 cust-sticky-pag" id="pfStickyPagination" style="display:none;">
                        <div class="card-body p-0">
                            <div class="row mx-3 my-2 justify-content-between align-items-center pfPagination"></div>
                        </div>
                    </div>

                </div>
            </div>
            <?php $this->load->view('common/footer_desc'); ?>
        </div>
    </div>
</div>

<?php $this->load->view('common/transactions/footer'); ?>

<script src="/js/transactions/viewmodal.js"></script>
<script src="/js/transactions/a4_print.js"></script>
<script src="/js/transactions/proformainvoices.js"></script>

<script>
const ModuleId     = 113;
const ModuleTable  = '#pfTable';
const ModulePag    = '.pfPagination';
const ModuleHeader = '.pfHeaderCheck';
const ModuleRow    = '.pfCheck';

$(function () {
    'use strict';

    Filter['Status'] = 'All';

    var $pfStaticPag = $('#pfPagination');
    var $pfStickyPag = $('#pfStickyPagination');
    function _syncPfSticky() { $pfStickyPag.find('.pfPagination').html($pfStaticPag.html()); }
    function _togglePfSticky() {
        if (!$pfStaticPag.length) return;
        var r = $pfStaticPag[0].getBoundingClientRect();
        var visible = r.top < $(window).height() && r.bottom > 0;
        if (visible) $pfStickyPag.stop(true,true).fadeOut(150);
        else { _syncPfSticky(); $pfStickyPag.stop(true,true).fadeIn(150); }
    }
    $(window).on('scroll resize', _togglePfSticky);
    _togglePfSticky();

    $(document).on('click', '[data-stat-filter]', function () {
        var status = $(this).data('stat-filter') || 'All';
        $('.trans-stat-card').removeClass('active-stat');
        $(this).addClass('active-stat');
        $('.pf-status-tab').removeClass('active');
        $('.pf-status-tab[data-status="' + status + '"]').addClass('active');
        Filter.Status = status; PageNo = 1; getProFormaInvoicesDetails();
    });

    $(document).on('click', '.pf-status-tab', function (e) {
        e.preventDefault();
        $('.pf-status-tab').removeClass('active');
        $(this).addClass('active');
        $('.trans-stat-card').removeClass('active-stat');
        var status = $(this).data('status') || 'All';
        $('[data-stat-filter="' + status + '"]').addClass('active-stat');
        Filter.Status = status; PageNo = 1; getProFormaInvoicesDetails();
    });

    $(document).on('click', '.pageRefresh', function (e) { e.preventDefault(); PageNo = 1; getProFormaInvoicesDetails(); });

    $('#searchTransactionData').on('keyup', debounce(function () {
        Filter.Name = $.trim($(this).val()); PageNo = 1; getProFormaInvoicesDetails();
    }, 400));

    $(document).on('click', '.date-option', function () {
        $('.date-option').removeClass('active'); $(this).addClass('active');
        var dates = getDateRange($(this).data('range') || '');
        $('#dateFilterLabel').text($.trim($(this).text()));
        Filter.DateFrom = dates.from; Filter.DateTo = dates.to;
        PageNo = 1; getProFormaInvoicesDetails();
    });

    $(document).on('click', '.col-sortable', function () {
        var col = $(this).data('sort');
        if (Filter.SortBy === col) Filter.SortDir = (Filter.SortDir === 'ASC') ? 'DESC' : 'ASC';
        else { Filter.SortBy = col; Filter.SortDir = 'DESC'; }
        $('.sort-icon').removeClass('bx-sort-up bx-sort-down').addClass('bx-sort-alt-2');
        $('.sort-icon[data-col="' + col + '"]').removeClass('bx-sort-alt-2').addClass(Filter.SortDir === 'ASC' ? 'bx-sort-up' : 'bx-sort-down');
        PageNo = 1; getProFormaInvoicesDetails();
    });

    $(document).on('click', '.pfPagination .page-link', function (e) {
        e.preventDefault();
        var match = ($(this).attr('href') || '').match(/\/(\d+)$/);
        if (match) { PageNo = parseInt(match[1]); getProFormaInvoicesDetails(); }
    });

    function _actionPostData(extra) {
        Filter.Status = $('.pf-status-tab.active').data('status') || 'All';
        return $.extend({ RowLimit: RowLimit, PageNo: PageNo, Filter: Filter, [CsrfName]: CsrfToken }, extra);
    }

    function _renderListResponse(resp) {
        $(ModuleTable + ' tbody').html(resp.RecordHtmlData);
        $(ModulePag).html(resp.Pagination);
        var count = resp.TotalCount || 0;
        $('.pf-status-tab.active .trans-tab-count').text(count > 0 ? count : '').removeClass('d-none');
        initTooltips();
    }

    // ── Inline status update ─────────────────────────────────
    $(document).on('click', '.pf-status-update', function () {
        var $btn = $(this), uid = $btn.data('uid'), status = $btn.data('status'), num = $btn.data('num') || '';
        var confirmMap = {
            'Sent'      : { title: 'Send Pro Forma?',    html: num ? 'Mark <strong>' + $('<span>').text(num).html() + '</strong> as sent?' : 'Mark this Pro Forma as sent?',       color: '#7c3aed', text: 'Yes, Send It' },
            'Expired'   : { title: 'Mark as Expired?',   html: num ? 'Mark <strong>' + $('<span>').text(num).html() + '</strong> as expired?' : 'Mark as expired?',                color: '#d97706', text: 'Yes, Expire It' },
            'Cancelled' : { title: 'Cancel Pro Forma?',  html: num ? 'Cancel <strong>' + $('<span>').text(num).html() + '</strong>? This cannot be undone.' : 'Cannot be undone.', color: '#d33',    text: 'Yes, Cancel' },
            'Reactivate': { title: 'Reactivate?',        html: 'Reactivate this Pro Forma Invoice?',                                                                               color: '#0d6efd', text: 'Yes, Reactivate' },
        };
        var cfg = confirmMap[status] || confirmMap['Reactivate'];
        if (cfg && !$btn.data('_confirmed')) {
            Swal.fire({ title: cfg.title, html: cfg.html, icon: 'warning', showCancelButton: true,
                confirmButtonColor: cfg.color, cancelButtonColor: '#6c757d',
                confirmButtonText: cfg.text, cancelButtonText: 'No, Keep It'
            }).then(function (r) { if (!r.isConfirmed) return; $btn.data('_confirmed', true).trigger('click'); });
            return;
        }
        $btn.removeData('_confirmed');
        $.ajax({
            url: '/proforma/updateProFormaStatus', method: 'POST',
            data: _actionPostData({ TransUID: uid, Status: status }),
            success: function (resp) {
                if (resp.Error) { showToastNotification(resp.Message, 'error'); return; }
                _renderListResponse(resp);
                showToastNotification(resp.Message, 'success');
            }
        });
    });

    // ── Convert to Invoice ───────────────────────────────────
    $(document).on('click', '.convertPFToInvoice', function () {
        var uid = $(this).data('uid'), num = $(this).data('num') || '';
        Swal.fire({
            title: 'Convert to Invoice?',
            html : num ? 'Convert <strong>' + num + '</strong> to a Tax Invoice?' : 'Convert this Pro Forma to a Tax Invoice?',
            icon : 'question', showCancelButton: true,
            confirmButtonColor: '#198754', cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Convert', cancelButtonText: 'Cancel'
        }).then(function (r) {
            if (!r.isConfirmed) return;
            $.ajax({
                url: '/proforma/convertProFormaToInvoice', method: 'POST',
                data: { TransUID: uid, [CsrfName]: CsrfToken },
                success: function (resp) {
                    if (resp.Error) { Swal.fire({ icon: 'error', text: resp.Message }); return; }
                    window.location.href = resp.RedirectURL;
                }
            });
        });
    });

    // ── Duplicate ────────────────────────────────────────────
    $(document).on('click', '.duplicateProForma', function () {
        var uid = $(this).data('uid'), num = $(this).data('num') || '';
        Swal.fire({
            title: 'Duplicate Pro Forma?', html: num ? 'Create a copy of <strong>' + num + '</strong>?' : 'Duplicate this Pro Forma?',
            icon: 'question', showCancelButton: true,
            confirmButtonColor: '#7c3aed', cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Duplicate', cancelButtonText: 'Cancel'
        }).then(function (r) {
            if (!r.isConfirmed) return;
            $.ajax({
                url: '/proforma/duplicateProFormaInvoice', method: 'POST',
                data: { TransUID: uid, [CsrfName]: CsrfToken },
                success: function (resp) {
                    if (resp.Error) { Swal.fire({ icon: 'error', text: resp.Message }); return; }
                    Swal.fire({ icon: 'success', text: resp.Message, timer: 2000, showConfirmButton: false })
                        .then(function () { if (resp.EditURL) window.location.href = resp.EditURL; else getProFormaInvoicesDetails(); });
                }
            });
        });
    });

    // ── Delete ───────────────────────────────────────────────
    $(document).on('click', '.deleteProForma', function () {
        var uid = $(this).data('uid'), num = $(this).data('num') || '';
        Swal.fire({
            title: 'Delete Pro Forma?',
            html: num ? 'Delete <strong>' + num + '</strong>? This cannot be undone.' : 'This cannot be undone.',
            icon: 'warning', showCancelButton: true, confirmButtonText: 'Delete', confirmButtonColor: '#d33',
        }).then(function (r) {
            if (!r.isConfirmed) return;
            $.ajax({
                url: '/proforma/deleteProFormaInvoice', method: 'POST',
                data: _actionPostData({ TransUID: uid }),
                success: function (resp) {
                    if (resp.Error) { Swal.fire({ icon: 'error', text: resp.Message }); return; }
                    _renderListResponse(resp);
                    Swal.fire({ icon: 'success', text: resp.Message, timer: 1500, showConfirmButton: false });
                }
            });
        });
    });

    $(document).on('change', '.pfHeaderCheck', function () { $('.pfCheck').prop('checked', $(this).is(':checked')); });

});

function _buildPFDetailHtml(resp) {
    window._pfLastPrintData = resp;
    return _buildTransDetailHtml(resp, {
        partyLabel  : 'Customer',
        typeIcon    : 'bx-file-blank',
        typeColor   : '#7c3aed',
        typeBg      : '#ede9fe',
        hasPayments : false,
        validLabel  : 'Valid Until',
    });
}
</script>
