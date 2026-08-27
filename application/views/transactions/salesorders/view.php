<?php defined('BASEPATH') or exit('No direct script access allowed');
$this->load->view('common/transactions/header'); ?>

<div class="layout-wrapper layout-horizontal layout-content-navbar">
    <div class="layout-container">

        <?php $this->load->view('common/menu_view'); ?>

        <div class="layout-page">

            <div class="content-wrapper apex-content">
                <?php $this->load->view('common/apex/page_header', [
                    'pageTitle'       => $PageTitle       ?? 'Sales Orders',
                    'pageDescription' => $PageDescription ?? '',
                ]); ?>
                <?php
                $initTab    = $InitTab    ?? 'All';
                $initSearch = $InitSearch ?? '';
                $tabFilterMap = [
                    'All'       => ['soCreatedByFilter', 'soPartyFilterTrigger'],
                    'Pending'   => ['soCreatedByFilter', 'soPartyFilterTrigger'],
                    'Completed' => ['soCreatedByFilter', 'soPartyFilterTrigger'],
                    'Cancelled' => ['soCreatedByFilter', 'soPartyFilterTrigger'],
                    'Draft'     => ['soCreatedByFilter', 'soPartyFilterTrigger'],
                ];
                $visibleFilters = $tabFilterMap[$initTab] ?? $tabFilterMap['All'];

                if (($JwtData->GenSettings->ShowStats ?? 1) && ($JwtData->TransSettings->ShowTransactionStats ?? 1)):
                $stats       = $SummaryStats ?? [];
                $cur         = htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹');
                
                $cntAll       = array_sum(array_column($stats, 'count'));
                $cntConfirmed = $stats['Pending']['count']   ?? 0;
                $cntCompleted = $stats['Completed']['count'] ?? 0;
                $cntDraft     = $stats['Draft']['count']     ?? 0;

                $amtAll       = array_sum(array_column($stats, 'amount'));
                $amtConfirmed = $stats['Pending']['amount']   ?? 0;
                $amtCompleted = $stats['Completed']['amount'] ?? 0;

                $statsItems = [
                    ['label' => 'All Orders', 'status' => 'All',       'icon' => 'bx-cart',        'iconBg' => '#eef2ff', 'iconColor' => '#696cff', 'count' => $cntAll,       'amount' => $amtAll],
                    ['label' => 'Pending',    'status' => 'Pending',   'icon' => 'bx-time',         'iconBg' => '#fff7ed', 'iconColor' => '#f97316', 'count' => $cntConfirmed, 'amount' => $amtConfirmed],
                    ['label' => 'Completed',  'status' => 'Completed', 'icon' => 'bx-check-double', 'iconBg' => '#dcfce7', 'iconColor' => '#16a34a', 'count' => $cntCompleted, 'amount' => $amtCompleted],
                    ['label' => 'Drafts',     'status' => 'Draft',     'icon' => 'bx-edit',          'iconBg' => '#f1f5f9', 'iconColor' => '#64748b', 'count' => $cntDraft,     'amount' => 0],
                ];
                ?>
                <div class="apex-stats-strip">
                    <?php foreach ($statsItems as $stat): ?>
                    <div class="apex-stat-item <?php echo $stat['status'] === $initTab ? 'active' : ''; ?>" data-status="<?php echo $stat['status']; ?>" data-stat-filter="<?php echo $stat['status']; ?>" style="--stat-color:<?php echo $stat['iconColor']; ?>">
                        <div class="apex-stat-icon" style="background:<?php echo $stat['iconBg']; ?>;">
                            <i class="bx <?php echo $stat['icon']; ?>" style="color:<?php echo $stat['iconColor']; ?>;"></i>
                        </div>
                        <div class="apex-stat-body">
                            <div class="apex-stat-label"><?php echo $stat['label']; ?></div>
                            <div class="apex-stat-bottom">
                                <span class="apex-stat-count"><?php echo $stat['count']; ?></span>
                                <span class="apex-stat-amount"><?php echo $cur . ' ' . smartDecimal((float)$stat['amount']); ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <div class="container-xxl flex-grow-1">

                    <!-- ── Main Card ─────────────────────────────────────── -->
                    <div class="card">

                        <!-- ── Filter Row ─────────────────────────────────── -->
                        <div class="apex-filter-row">
                            <div class="r2k-search-wrap<?php echo $initSearch ? ' is-expanded r2k-search-active' : ''; ?>">
                                <i class="bx bx-search r2k-si"></i>
                                <input type="text" id="searchTransactionData" placeholder="Order # or customer..." value="<?php echo htmlspecialchars($initSearch, ENT_QUOTES); ?>">
                                <i class="bx bx-x r2k-clear<?php echo $initSearch ? '' : ' d-none'; ?>"></i>
                            </div>
                            <?php if (count($OrgUsers ?? []) > 1): ?>
                            <a href="javascript:void(0);" id="soCreatedByFilter" class="apex-filter-btn<?php echo in_array('soCreatedByFilter', $visibleFilters) ? '' : ' d-none'; ?>" title="Filter by User"><i class="bx bx-user me-1"></i>Updated By</a>
                            <?php endif; ?>
                            <a href="javascript:void(0);" id="soPartyFilterTrigger" class="apex-filter-btn<?php echo in_array('soPartyFilterTrigger', $visibleFilters) ? '' : ' d-none'; ?>" title="Filter by Customer"><i class="bx bx-store me-1"></i>Customer</a>
                            <?php $this->load->view('common/transactions/date_filter_btn'); ?>
                            <div class="apex-filter-spacer"></div>
                            <a href="javascript:void(0);" class="apex-filter-btn pageRefresh" data-bs-toggle="tooltip" data-bs-placement="bottom" title="<?php echo t('page_refresh', 'Page Refresh'); ?>"><i class="bx bx-refresh"></i></a>
                            <div class="btn-group d-none" id="ActionsDD-Div">
                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="bx bx-slider-alt"></i></button>
                                <ul class="dropdown-menu dropdown-menu-end r2k-export-menu r2k-actions-menu">
                                    <li class="d-none" id="DeleteOption"><a class="dropdown-item text-danger" href="javascript:void(0);" id="btnDelete"><i class="bx bx-trash me-2"></i><?php echo t('delete', 'Delete'); ?></a></li>
                                </ul>
                            </div>
                            <?php $this->load->view('common/partials/export_btn'); ?>
                            <a href="/salesorders/create" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" data-bs-placement="bottom" title="<?php echo t('create_sales_order', 'Create Sales Order'); ?>"><i class="bx bx-plus me-1"></i><?php echo t('lbl_new', 'New'); ?></a>
                        </div>

                        <!-- ── Tabs Row ──────────────────────────────────── -->
                        <div class="apex-tabs-row">
                            <ul class="nav trans-status-tabs" id="soStatusTabs" role="tablist" data-trans-path="/salesorders">
                                <li class="nav-item"><a class="nav-link <?php echo $initTab === 'All' ? 'active' : ''; ?> so-status-tab" data-status="All" data-url-tab="all" href="javascript:void(0);">All <span class="trans-tab-count ms-1<?php echo ($initTab !== 'All' || $ModAllCount == 0) ? ' d-none' : ''; ?>"><?php echo ($initTab === 'All' && $ModAllCount > 0) ? $ModAllCount : ''; ?></span></a></li>
                                <li class="nav-item"><a class="nav-link <?php echo $initTab === 'Pending' ? 'active' : ''; ?> so-status-tab" data-status="Pending" data-url-tab="pending" href="javascript:void(0);">Pending <span class="trans-tab-count ms-1<?php echo ($initTab !== 'Pending' || $ModAllCount == 0) ? ' d-none' : ''; ?>"><?php echo ($initTab === 'Pending' && $ModAllCount > 0) ? $ModAllCount : ''; ?></span></a></li>
                                <li class="nav-item"><a class="nav-link <?php echo $initTab === 'Completed' ? 'active' : ''; ?> so-status-tab" data-status="Completed" data-url-tab="completed" href="javascript:void(0);">Completed <span class="trans-tab-count ms-1<?php echo ($initTab !== 'Completed' || $ModAllCount == 0) ? ' d-none' : ''; ?>"><?php echo ($initTab === 'Completed' && $ModAllCount > 0) ? $ModAllCount : ''; ?></span></a></li>
                                <li class="nav-item"><a class="nav-link <?php echo $initTab === 'Cancelled' ? 'active' : ''; ?> so-status-tab" data-status="Cancelled" data-url-tab="cancelled" href="javascript:void(0);">Cancelled <span class="trans-tab-count ms-1<?php echo ($initTab !== 'Cancelled' || $ModAllCount == 0) ? ' d-none' : ''; ?>"><?php echo ($initTab === 'Cancelled' && $ModAllCount > 0) ? $ModAllCount : ''; ?></span></a></li>
                                <li class="nav-item"><a class="nav-link <?php echo $initTab === 'Draft' ? 'active' : ''; ?> so-status-tab" data-status="Draft" data-url-tab="draft" href="javascript:void(0);">Drafts <span class="trans-tab-count ms-1<?php echo ($initTab !== 'Draft' || $ModAllCount == 0) ? ' d-none' : ''; ?>"><?php echo ($initTab === 'Draft' && $ModAllCount > 0) ? $ModAllCount : ''; ?></span></a></li>
                            </ul>
                            <?php $this->load->view('common/transactions/filter_notice'); ?>
                        </div>

                        <!-- Select-all banner -->
                        <div id="soSelectAllBanner" class="r2k-select-all-banner d-none">
                            <span id="soSelectAllMsg"></span>
                            <a href="javascript:void(0);" id="soSelectAllLink" class="ms-2"></a>
                            <a href="javascript:void(0);" id="soSelectAllClear" class="ms-2 d-none">Clear selection</a>
                        </div>

                        <!-- Table -->
                        <div class="table-responsive">
                            <table class="table trans-table table-hover MainviewTable mb-0" id="soTable">
                                <thead class="r2k-thead">
                                    <tr>
                                        <th style="width:36px">
                                            <div class="form-check mb-0">
                                                <input class="form-check-input table-chkbox soHeaderCheck" type="checkbox">
                                            </div>
                                        </th>
                                        <th class="<?php echo $JwtData->GenSettings->SerialNoDisplay == 1 ? '' : 'd-none'; ?> table-serialno" style="width:44px">S.No</th>
                                        <th class="col-sortable cursor-pointer user-select-none" data-sort="Number" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Click for ascending order">
                                            Order # <i class="bx bx-sort-alt-2 ms-1 sort-icon" data-col="Number"></i>
                                        </th>
                                        <th class="col-sortable cursor-pointer user-select-none" data-sort="Amount" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Click for ascending order">
                                            Amount <i class="bx bx-sort-alt-2 ms-1 sort-icon" data-col="Amount"></i>
                                        </th>
                                        <th>Status</th>
                                        <th>Customer</th>
                                        <th class="col-sortable cursor-pointer user-select-none" data-sort="Date" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Click for ascending order">
                                            Expected Delivery <i class="bx bx-sort-alt-2 ms-1 sort-icon" data-col="Date"></i>
                                        </th>
                                        <th>Last Updated</th>
                                        <th style="width:50px">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="r2k-tbody table-border-bottom-0">
                                    <?php echo $ModRowData; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="row mx-0 px-3 mt-1 justify-content-between align-items-center soPagination apex-pag-sticky" id="soPagination">
                            <?php echo $ModPagination ?: ''; ?>
                        </div>

                    </div>

                    <?php $this->load->view('common/transactions/print_modals'); ?>


                </div>
            </div>

            <?php $this->load->view('common/modals/send_communication'); ?>

            <?php $this->load->view('common/footer_desc'); ?>
        </div>
    </div>
</div>

<?php if (count($OrgUsers ?? []) > 1): ?>
<?php $this->load->view('common/filter_panels/col_user_filter_box', [
    'ColUserFilterConfig' => [
        'id'         => 'soCreatedByFilterBox',
        'triggerId'  => 'soCreatedByFilter',
        'checkClass' => 'so-user-chk',
        'OrgUsers'   => $OrgUsers ?? [],
    ],
]); ?>
<?php endif; ?>

<?php $this->load->view('common/filter_panels/col_party_filter_box', [
    'ColPartyFilterConfig' => [
        'id'    => 'soPartyFilterBox',
        'title' => 'Filter by Customer',
        'icon'  => 'bx-user',
    ],
]); ?>

<?php $this->load->view('common/transactions/footer'); ?>

<script src="/js/common/communication.js"></script>
<script src="/js/common/party_filter.js"></script>
<script src="<?php echo _assetV('/js/transactions/attachments.js'); ?>"></script>
<script src="/js/core/viewmodal.js"></script>
<script src="/js/core/a4_print.js"></script>
<script src="/js/transactions/filter_bar.js"></script>
<script src="/js/core/col_filter.js"></script>
<script src="/js/common/pagecheckbox.js"></script>
<script src="/js/transactions/salesorders.js"></script>

<script>
var _commOrgContext = <?php
    $org     = $CommOrgContext ?? null;
    $orgAddr = $org ? implode(', ', array_filter([
        $org->Line1 ?? '', $org->Line2 ?? '',
        $org->CityText ?? '', $org->StateText ?? '', $org->Pincode ?? '',
    ])) : '';
    echo json_encode([
        'OrgName'    => $org ? ($org->BrandName ?? $org->Name ?? '') : '',
        'OrgPhone'   => $org->MobileNumber  ?? '',
        'OrgEmail'   => $org->EmailAddress  ?? '',
        'OrgGSTIN'   => $org->GSTIN         ?? '',
        'OrgAddress' => $orgAddr,
    ]);
?>;
var _commGenSettings  = <?php echo json_encode([
    'CurrenySymbol' => $JwtData->GenSettings->CurrenySymbol ?? '₹',
    'DecimalPoints' => 9,
]); ?>;
var _rawEmailTemplate = <?php echo json_encode($CommEmailTemplate ?? null); ?>;
var _r2CdnBase        = <?php echo json_encode(rtrim(getenv('CFLARE_R2_CDN') ?: getenv('CDN_URL'), '/')); ?>;

const ModuleId     = 102;
const ModuleTable  = '#soTable';
const ModulePag    = '.soPagination';
const ModuleHeader = '.soHeaderCheck';
const ModuleRow    = '.soCheck';

var _soInitTab    = <?php echo json_encode($InitTab    ?? 'All'); ?>;
var _soInitSearch = <?php echo json_encode($InitSearch ?? ''); ?>;

var _soTabFilterMap = <?= json_encode($tabFilterMap); ?>;
var _allSoFilterEls = <?= json_encode(array_values(array_unique(array_merge(...array_values($tabFilterMap))))); ?>;
var _initPage       = <?php echo (int)($InitPage ?? 1); ?>;

$(function () {
    'use strict';

    _checkPendingToast('_soPendingToast');
    PageNo = _initPage;
    Filter['Status'] = _soInitTab;
    if (_soInitSearch) { Filter.Name = _soInitSearch; }
    initExport({ moduleUID: 102, getFilters: function () {
        return $.extend({}, Filter,
            tfb               ? tfb.getState()               : {},
            soCreatedByFilter ? soCreatedByFilter.getState() : {},
            soPartyFilter     ? soPartyFilter.getState()     : {}
        );
    } });
    _applyTabFilters(_soInitTab, _soTabFilterMap, _allSoFilterEls);

    // ── Filter bar ──────────────────────────────────────────────────────
    var tfb = (typeof TransFilterBar !== 'undefined')
        ? new TransFilterBar({ onChange: function () { PageNo = 1; getSalesOrdersDetails(); } })
        : null;

    var soCreatedByFilter = (document.getElementById('soCreatedByFilterBox'))
        ? new TransColFilter({
            boxId       : 'soCreatedByFilterBox',
            triggerId   : 'soCreatedByFilter',
            filterKey   : 'UpdatedByUIDs',
            activeClass : 'has-filter',
            onApply     : function () { PageNo = 1; getSalesOrdersDetails(); }
        })
        : null;

    var soPartyFilter = new TransPartyColFilter({
        boxId     : 'soPartyFilterBox',
        triggerId : 'soPartyFilterTrigger',
        partyType : 'customer',
        filterKey : 'PartyUID',
        onApply   : function () { PageNo = 1; getSalesOrdersDetails(); }
    });

    var _origGetSalesOrdersDetails = getSalesOrdersDetails;
    getSalesOrdersDetails = function (pageNo, rowLimit, filter, afterLoad) {
        var f = $.extend({}, filter || Filter,
            tfb               ? tfb.getState()               : {},
            soCreatedByFilter ? soCreatedByFilter.getState() : {},
            soPartyFilter     ? soPartyFilter.getState()     : {}
        );
        _origGetSalesOrdersDetails(pageNo, rowLimit, f, afterLoad);
    };

    // ── Create / Edit — inject returnTab + returnPage ──────────────────
    $(document).on('click', 'a[href="/salesorders/create"]', function (e) {
        e.preventDefault();
        var params = new URLSearchParams();
        params.set('returnTab', Filter.Status || 'All');
        if (PageNo > 1) params.set('returnPage', PageNo);
        window.location.href = '/salesorders/create?' + params.toString();
    });
    $(document).on('click', 'a[href^="/salesorders/edit/"]', function (e) {
        e.preventDefault();
        var params = new URLSearchParams();
        params.set('returnTab', Filter.Status || 'All');
        if (PageNo > 1) params.set('returnPage', PageNo);
        window.location.href = $(this).attr('href') + '?' + params.toString();
    });

    // ── Stat card click ─────────────────────────────────────
    $(document).on('click', '[data-stat-filter]', function () {
        var status = $(this).data('stat-filter') || 'All';
        $('.apex-stat-item').removeClass('active');
        $(this).addClass('active');
        $('.so-status-tab').removeClass('active');
        $('.so-status-tab[data-status="' + status + '"]').addClass('active');
        _resetSoFilters();
        _applyTabFilters(status, _soTabFilterMap, _allSoFilterEls);
        _updateTransTabUrl(status, '');
        Filter.Status = status;
        PageNo = 1;
        getSalesOrdersDetails();
    });

    // ── Status tabs ─────────────────────────────────────────
    $(document).on('click', '.so-status-tab', function (e) {
        e.preventDefault();
        SelectedUIDs = []; _soClearSelectAll(); MultipleDeleteOption();
        var status = $(this).data('status') || 'All';
        $('.so-status-tab').removeClass('active');
        $(this).addClass('active');
        $('.apex-stat-item').removeClass('active');
        $('.apex-stat-item[data-stat-filter="' + status + '"]').addClass('active');
        _resetSoFilters();
        _applyTabFilters(status, _soTabFilterMap, _allSoFilterEls);
        _updateTransTabUrl(status, '');
        Filter.Status = status;
        PageNo = 1;
        getSalesOrdersDetails();
    });

    $(document).on('click', '.pageRefresh', function (e) {
        e.preventDefault();
        PageNo = 1;
        getSalesOrdersDetails();
    });

    $('#searchTransactionData').on('input', function () {
        var curTab = $('.so-status-tab.active').data('status') || 'All';
        _updateTransTabUrl(curTab, $.trim($(this).val()));
    });
    $('#searchTransactionData').on('input', debounce(function () {
        Filter.Name = $.trim($(this).val());
        PageNo = 1;
        getSalesOrdersDetails();
    }, 1500));

    $(document).on('r2k:datechange', function (e, dr) {
        Filter.DateFrom = dr.from;
        Filter.DateTo   = dr.to;
        PageNo = 1;
        getSalesOrdersDetails();
    });

    $(document).on('click', '.col-sortable', function () {
        var col = $(this).data('sort');
        var $th = $(this);
        if (Filter.SortBy !== col) {
            Filter.SortBy  = col;
            Filter.SortDir = 'ASC';
        } else if (Filter.SortDir === 'ASC') {
            Filter.SortDir = 'DESC';
        } else {
            delete Filter.SortBy;
            delete Filter.SortDir;
        }
        $('.col-sortable').each(function () {
            $(this).attr('data-bs-title', 'Click for ascending order');
            var tt = bootstrap.Tooltip.getInstance(this);
            if (tt) { tt.dispose(); new bootstrap.Tooltip(this); }
        });
        $('.sort-icon').removeClass('bx-sort-up bx-sort-down').addClass('bx-sort-alt-2');
        if (Filter.SortBy) {
            var icon    = Filter.SortDir === 'ASC' ? 'bx-sort-up' : 'bx-sort-down';
            var tipText = Filter.SortDir === 'ASC' ? 'Click for descending order' : 'Click to remove sorting';
            $('.sort-icon[data-col="' + col + '"]').removeClass('bx-sort-alt-2').addClass(icon);
            $th.attr('data-bs-title', tipText);
            var tt = bootstrap.Tooltip.getInstance($th[0]);
            if (tt) { tt.dispose(); new bootstrap.Tooltip($th[0]); }
        }
        PageNo = 1;
        getSalesOrdersDetails();
    });

    $(document).on('click', '.soPagination .page-link', function (e) {
        e.preventDefault();
        var match = ($(this).attr('href') || '').match(/\/(\d+)$/);
        if (match) { PageNo = parseInt(match[1]); _soClearSelectAll(); getSalesOrdersDetails(); }
    });

    function _resetSoFilters() {
        var $wrap = $('#searchTransactionData').closest('.r2k-search-wrap');
        $('#searchTransactionData').val('');
        $wrap.find('.r2k-clear').addClass('d-none');
        $wrap.removeClass('is-expanded r2k-search-active');
        Filter.Name = '';
        if (soCreatedByFilter) { soCreatedByFilter.reset(); }
        if (soPartyFilter)     { soPartyFilter.reset(); }
        if (tfb)               { tfb.reset(); }
        $('.trans-col-filterbox, .tpcf-box').hide();
    }

    // helper: sync active tab status into Filter then build POST data
    function _actionPostData(extra) {
        Filter.Status = $('.so-status-tab.active').data('status') || 'All';
        return $.extend({ RowLimit: RowLimit, PageNo: PageNo, Filter: Filter, [CsrfName]: CsrfToken }, extra);
    }

    // helper: render list response returned by cancel/delete directly into the table
    function _renderListResponse(resp) {
        $(ModuleTable + ' tbody').html(resp.RecordHtmlData);
        $(ModulePag).html(resp.Pagination);
        var count = resp.TotalCount || 0;
        var $soBadge = $('.so-status-tab.active .trans-tab-count');
        if (count > 0) { $soBadge.text(count).removeClass('d-none'); } else { $soBadge.text('').addClass('d-none'); }
        initTooltips();
    }

    // ── Inline status update (Cancel from 3-dot or status badge) ──
    $(document).on('click', '.so-status-update', function () {
        var $btn   = $(this);
        var uid    = $btn.data('uid');
        var status = $btn.data('status');

        if (status === 'Cancelled' && !$btn.data('_confirmed')) {
            var num = $btn.data('num') || '';
            var lbl = num ? '<strong>' + $('<span>').text(num).html() + '</strong>' : 'this sales order';
            Swal.fire({
                title: 'Cancel Sales Order?',
                html : 'Are you sure you want to cancel ' + lbl + '? This cannot be undone.',
                icon : 'warning', showCancelButton: true,
                confirmButtonColor: '#d33', cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Cancel It', cancelButtonText: 'No, Keep It'
            }).then(function (r) {
                if (!r.isConfirmed) return;
                $btn.data('_confirmed', true).trigger('click');
            });
            return;
        }

        $btn.removeData('_confirmed');
        ajaxLoading(1);
        $.ajax({
            url   : '/salesorders/updateSalesOrderStatus',
            method: 'POST',
            data  : _actionPostData({ TransUID: uid, Status: status }),
            success: function (resp) {
                ajaxLoading(0);
                hideUIBlock();
                if (resp.Error) { Swal.fire({ icon: 'error', text: resp.Message }); return; }
                showToastNotification(resp.Message || 'Status updated.', 'success');
                getSalesOrdersDetails();
            },
            error: function () { ajaxLoading(0); hideUIBlock(); Swal.fire({ icon: 'error', text: 'Request failed. Please try again.' }); }
        });
    });

    // ── Delete ───────────────────────────────────────────────
    $(document).on('click', '.deleteSalesOrder', function () {
        var uid = $(this).data('uid'), num = $(this).data('num') || '';
        Swal.fire({
            title: 'Delete Sales Order?',
            html : num ? 'Delete <strong>' + num + '</strong>? This cannot be undone.' : 'This cannot be undone.',
            icon : 'warning', showCancelButton: true, confirmButtonText: 'Delete', confirmButtonColor: '#d33',
        }).then(function (r) {
            if (!r.isConfirmed) return;
            ajaxLoading(1);
            $.ajax({
                url   : '/salesorders/deleteSalesOrder',
                method: 'POST',
                data  : _actionPostData({ TransUID: uid }),
                success: function (resp) {
                    ajaxLoading(0);
                    hideUIBlock();
                    if (resp.Error) { Swal.fire({ icon: 'error', text: resp.Message }); return; }
                    showToastNotification(resp.Message || 'Deleted.', 'success');
                    if (PageNo > 1 && (resp.TotalCount || 0) <= (PageNo - 1) * RowLimit) {
                        PageNo--;
                        getSalesOrdersDetails();
                    } else {
                        _renderListResponse(resp);
                    }
                },
                error: function () { ajaxLoading(0); hideUIBlock(); Swal.fire({ icon: 'error', text: 'Request failed. Please try again.' }); }
            });
        });
    });

    // ── Convert to Invoice ───────────────────────────────────
    $(document).on('click', '.convertSOToInvoice', function () {
        var uid = $(this).data('uid'), num = $(this).data('num') || '';
        Swal.fire({
            title: 'Convert to Invoice?',
            html : num ? 'Convert <strong>' + num + '</strong> to an Invoice?' : 'Convert this sales order to an Invoice?',
            icon : 'question', showCancelButton: true,
            confirmButtonColor: '#198754', cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Convert', cancelButtonText: 'Cancel'
        }).then(function (r) {
            if (!r.isConfirmed) return;
            $.ajax({
                url   : '/salesorders/convertSalesOrderToInvoice',
                method: 'POST',
                data  : { TransUID: uid, [CsrfName]: CsrfToken },
                success: function (resp) {
                    if (resp.Error) { Swal.fire({ icon: 'error', text: resp.Message }); return; }
                    window.location.href = resp.RedirectURL;
                }
            });
        });
    });

    // ── Convert to Delivery Challan ──────────────────────────
    $(document).on('click', '.convertSOToChallan', function () {
        var uid = $(this).data('uid'), num = $(this).data('num') || '';
        Swal.fire({
            title: 'Convert to Delivery Challan?',
            html : num ? 'Convert <strong>' + num + '</strong> to a Delivery Challan?' : 'Convert this sales order to a Delivery Challan?',
            icon : 'question', showCancelButton: true,
            confirmButtonColor: '#0dcaf0', cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Convert', cancelButtonText: 'Cancel'
        }).then(function (r) {
            if (!r.isConfirmed) return;
            $.ajax({
                url   : '/salesorders/convertSalesOrderToDeliveryChallan',
                method: 'POST',
                data  : { TransUID: uid, [CsrfName]: CsrfToken },
                success: function (resp) {
                    if (resp.Error) { Swal.fire({ icon: 'error', text: resp.Message }); return; }
                    window.location.href = resp.RedirectURL;
                }
            });
        });
    });


    // ── Checkbox / select-all wiring ─────────────────────────────────────
    basePageHeaderFunc(ModuleHeader, ModuleTable, ModuleRow);

    $(ModuleHeader).on('click', function () {
        _soUpdateSelectAllBanner();
    });

    $(document).on('change', ModuleRow, function () {
        onClickOfCheckbox(this, ModuleTable, ModuleHeader, ModuleRow);
        _soUpdateSelectAllBanner();
        MultipleDeleteOption();
    });

    $(document).on('click', '#soSelectAllLink', function (e) {
        e.preventDefault();
        _soSelectAllMode = true;
        _soUpdateSelectAllBanner();
    });

    $(document).on('click', '#soSelectAllClear', function (e) {
        e.preventDefault();
        SelectedUIDs = [];
        unSelectTableRecords(ModuleTable, ModuleRow);
        $(ModuleHeader).prop('checked', false).prop('indeterminate', false);
        _soClearSelectAll();
        MultipleDeleteOption();
    });

    // ── Bulk delete ───────────────────────────────────────────────────────
    $('#btnDelete').on('click', function () {
        var count = _soSelectAllMode ? _soTotalRecords : SelectedUIDs.length;
        Swal.fire({
            title: 'Delete ' + count + ' sales order' + (count === 1 ? '' : 's') + '?',
            text : 'This cannot be undone.',
            icon : 'warning', showCancelButton: true,
            confirmButtonText: 'Delete', confirmButtonColor: '#d33',
        }).then(function (r) {
            if (!r.isConfirmed) return;
            deleteMultipleSalesOrders();
        });
    });

    // ── syncDD: show/hide ActionsDD-Div when DeleteOption visibility changes ──
    (function syncDD() {
        var $div = $('#ActionsDD-Div');
        var $del = $('#DeleteOption');
        if (!$div.length || !$del.length) return;
        new MutationObserver(function () {
            $div.toggleClass('d-none', $del.hasClass('d-none'));
        }).observe($del[0], { attributes: true, attributeFilter: ['class'] });
    })();

});

function _buildSODetailHtml(resp) {
    window._soLastPrintData = resp;
    return _buildTransDetailHtml(resp, {
        partyLabel  : 'Customer',
        typeIcon    : 'bx-store-alt',
        typeColor   : '#d97706',
        typeBg      : '#fff8e1',
        hasPayments : false,
        validLabel  : 'Expected Delivery',
    });
}

</script>
