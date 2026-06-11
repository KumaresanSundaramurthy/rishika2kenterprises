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
                            <div>
                                <h5 class="trans-ph-title mb-0"><?php echo htmlspecialchars($PageTitle ?? 'Pro Forma Invoices'); ?></h5>
                                <?php if (!empty($PageDescription)): ?>
                                <div class="text-muted" style="font-size:.76rem;"><?php echo htmlspecialchars($PageDescription); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <?php $this->load->view('common/partials/export_btn'); ?>
                            <a href="/proforma/create" class="btn btn-primary">
                                <i class="bx bx-plus me-1"></i>New Pro Forma
                            </a>
                        </div>
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
                            <div class="trans-toolbar-tabs">
                                <ul class="nav trans-status-tabs" id="pfStatusTabs" role="tablist">
                                    <li class="nav-item"><a class="nav-link active pf-status-tab" data-status="All" href="javascript:void(0);">All <span class="trans-tab-count ms-1"><?php echo $ModAllCount; ?></span></a></li>
                                    <li class="nav-item"><a class="nav-link pf-status-tab" data-status="Sent" href="javascript:void(0);">Sent <span class="pf-tab-count trans-tab-count ms-1 d-none"></span></a></li>
                                    <li class="nav-item"><a class="nav-link pf-status-tab" data-status="Converted" href="javascript:void(0);">Converted <span class="pf-tab-count trans-tab-count ms-1 d-none"></span></a></li>
                                    <li class="nav-item"><a class="nav-link pf-status-tab" data-status="Expired" href="javascript:void(0);">Expired <span class="pf-tab-count trans-tab-count ms-1 d-none"></span></a></li>
                                    <li class="nav-item"><a class="nav-link pf-status-tab" data-status="Cancelled" href="javascript:void(0);">Cancelled <span class="pf-tab-count trans-tab-count ms-1 d-none"></span></a></li>
                                    <li class="nav-item"><a class="nav-link pf-status-tab" data-status="Draft" href="javascript:void(0);">Drafts <span class="pf-tab-count trans-tab-count ms-1 d-none"></span></a></li>
                                </ul>
                            </div>
                            <div class="trans-toolbar-actions">
                                <a href="javascript:void(0);" class="r2k-icon-btn pageRefresh" title="Refresh"><i class="bx bx-refresh"></i></a>
                                <?php $this->load->view('common/transactions/date_filter_btn'); ?>
                                <div class="r2k-search-wrap">
                                    <i class="bx bx-search r2k-si"></i>
                                    <input type="text" id="searchTransactionData" placeholder="PF # or customer...">
                                    <i class="bx bx-x r2k-clear d-none"></i>
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
                                        <th>
                                            Customer
                                            <a href="javascript:void(0);" id="pfPartyFilterTrigger" class="text-body ms-1"
                                               data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Filter by Customer"
                                               style="font-size:.85rem;">
                                                <i class="bx bx-filter-alt align-middle"></i>
                                            </a>
                                        </th>
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

<?php $this->load->view('common/transactions/col_party_filter_box', [
    'ColPartyFilterConfig' => [
        'id'    => 'pfPartyFilterBox',
        'title' => 'Filter by Customer',
        'icon'  => 'bx-user',
    ],
]); ?>

<?php $this->load->view('common/transactions/footer'); ?>

<script src="/js/common/party_filter.js"></script>
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
    initExport({ moduleUID: 113, getFilters: function () { return Filter; } });

    var pfPartyFilter = new TransPartyColFilter({
        boxId     : 'pfPartyFilterBox',
        triggerId : 'pfPartyFilterTrigger',
        partyType : 'customer',
        filterKey : 'PartyUID',
        onApply   : function () { PageNo = 1; getProFormaInvoicesDetails(); }
    });

    var _origGetProFormaInvoicesDetails = getProFormaInvoicesDetails;
    getProFormaInvoicesDetails = function (pageNo, rowLimit, filter) {
        var f = $.extend({}, filter || Filter,
            pfPartyFilter ? pfPartyFilter.getState() : {}
        );
        _origGetProFormaInvoicesDetails(pageNo, rowLimit, f);
    };

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

    $('#searchTransactionData').on('input', debounce(function () {
        Filter.Name = $.trim($(this).val()); PageNo = 1; getProFormaInvoicesDetails();
    }, 1500));

    $(document).on('r2k:datechange', function (e, dr) {
        Filter.DateFrom = dr.from; Filter.DateTo = dr.to;
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
        var $pfBadge = $('.pf-status-tab.active .trans-tab-count');
        if (count > 0) { $pfBadge.text(count).removeClass('d-none'); } else { $pfBadge.text('').addClass('d-none'); }
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
