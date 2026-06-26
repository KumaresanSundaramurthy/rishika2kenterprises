<?php defined('BASEPATH') or exit('No direct script access allowed');
$this->load->view('common/transactions/header'); ?>

<div class="layout-wrapper layout-horizontal layout-content-navbar">
    <div class="layout-container">

        <?php $this->load->view('common/menu_view'); ?>

        <div class="layout-page">
            <div class="content-wrapper apex-content">
                <?php $this->load->view('common/apex/page_header', [
                    'pageTitle'       => $PageTitle       ?? 'Proforma Invoices',
                    'pageDescription' => $PageDescription ?? 'Create proforma invoices for customers',
                ]); ?>
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

                $statsItems = [
                    ['label' => 'All Pro Formas', 'status' => 'All',       'icon' => 'bx-file-blank',   'iconBg' => '#f5f3ff', 'iconColor' => '#8b5cf6', 'count' => $cntAll,       'amount' => $amtAll],
                    ['label' => 'Sent',           'status' => 'Sent',      'icon' => 'bx-send',         'iconBg' => '#eef2ff', 'iconColor' => '#696cff', 'count' => $cntSent,      'amount' => $amtSent],
                    ['label' => 'Converted',      'status' => 'Converted', 'icon' => 'bx-transfer-alt', 'iconBg' => '#dcfce7', 'iconColor' => '#16a34a', 'count' => $cntConverted, 'amount' => 0],
                    ['label' => 'Drafts',         'status' => 'Draft',     'icon' => 'bx-edit',          'iconBg' => '#f1f5f9', 'iconColor' => '#64748b', 'count' => $cntDraft,     'amount' => 0],
                ];
                ?>
                <div class="apex-stats-strip">
                    <?php foreach ($statsItems as $stat): ?>
                    <div class="apex-stat-item <?php echo $stat['status'] === 'All' ? 'active' : ''; ?>" data-status="<?php echo $stat['status']; ?>" data-stat-filter="<?php echo $stat['status']; ?>" style="--stat-color:<?php echo $stat['iconColor']; ?>">
                        <div class="apex-stat-icon" style="background:<?php echo $stat['iconBg']; ?>;">
                            <i class="bx <?php echo $stat['icon']; ?>" style="color:<?php echo $stat['iconColor']; ?>;"></i>
                        </div>
                        <div class="apex-stat-body">
                            <div class="apex-stat-label"><?php echo $stat['label']; ?></div>
                            <div class="apex-stat-bottom">
                                <span class="apex-stat-count"><?php echo $stat['count']; ?></span>
                                <span class="apex-stat-amount"><?php echo $cur . ' ' . number_format((float)$stat['amount'], $dec); ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="container-xxl flex-grow-1 py-3">

                    <!-- ── Main Card ──────────────────────────────────────── -->
                    <div class="card">

                        <!-- ── Filter Row ─────────────────────────────────── -->
                        <div class="apex-filter-row">
                            <div class="r2k-search-wrap">
                                <i class="bx bx-search r2k-si"></i>
                                <input type="text" id="searchTransactionData" placeholder="PF # or customer...">
                                <i class="bx bx-x r2k-clear d-none"></i>
                            </div>
                            <a href="javascript:void(0);" id="pfPartyFilterTrigger" class="apex-filter-btn" title="Filter by Customer">
                                <i class="bx bx-store me-1"></i>Customer
                            </a>
                            <?php $this->load->view('common/transactions/date_filter_btn'); ?>
                            <div class="apex-filter-spacer"></div>
                            <a href="javascript:void(0);" class="apex-filter-btn pageRefresh" title="Refresh"><i class="bx bx-refresh"></i></a>
                            <?php $this->load->view('common/partials/export_btn'); ?>
                            <a href="/proforma/create" class="btn btn-sm btn-primary"><i class="bx bx-plus me-1"></i>New Pro Forma</a>
                        </div>

                        <!-- ── Tabs Row ──────────────────────────────────── -->
                        <div class="apex-tabs-row">
                            <ul class="nav trans-status-tabs" id="pfStatusTabs" role="tablist">
                                <li class="nav-item"><a class="nav-link active pf-status-tab" data-status="All" href="javascript:void(0);">All <span class="trans-tab-count ms-1"><?php echo $ModAllCount; ?></span></a></li>
                                <li class="nav-item"><a class="nav-link pf-status-tab" data-status="Sent" href="javascript:void(0);">Sent <span class="pf-tab-count trans-tab-count ms-1 d-none"></span></a></li>
                                <li class="nav-item"><a class="nav-link pf-status-tab" data-status="Converted" href="javascript:void(0);">Converted <span class="pf-tab-count trans-tab-count ms-1 d-none"></span></a></li>
                                <li class="nav-item"><a class="nav-link pf-status-tab" data-status="Expired" href="javascript:void(0);">Expired <span class="pf-tab-count trans-tab-count ms-1 d-none"></span></a></li>
                                <li class="nav-item"><a class="nav-link pf-status-tab" data-status="Cancelled" href="javascript:void(0);">Cancelled <span class="pf-tab-count trans-tab-count ms-1 d-none"></span></a></li>
                                <li class="nav-item"><a class="nav-link pf-status-tab" data-status="Draft" href="javascript:void(0);">Drafts <span class="pf-tab-count trans-tab-count ms-1 d-none"></span></a></li>
                            </ul>
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
        $('.apex-stat-item').removeClass('active');
        $(this).addClass('active');
        $('.pf-status-tab').removeClass('active');
        $('.pf-status-tab[data-status="' + status + '"]').addClass('active');
        Filter.Status = status; PageNo = 1; getProFormaInvoicesDetails();
    });

    $(document).on('click', '.pf-status-tab', function (e) {
        e.preventDefault();
        $('.pf-status-tab').removeClass('active');
        $(this).addClass('active');
        $('.apex-stat-item').removeClass('active');
        var status = $(this).data('status') || 'All';
        $('.apex-stat-item[data-stat-filter="' + status + '"]').addClass('active');
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
