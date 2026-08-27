<?php defined('BASEPATH') or exit('No direct script access allowed');
$this->load->view('common/transactions/header'); ?>

<div class="layout-wrapper layout-horizontal layout-content-navbar">
    <div class="layout-container">

        <?php $this->load->view('common/menu_view'); ?>

        <div class="layout-page">
            <div class="content-wrapper apex-content">

                <!-- ── Apex Page Header ──────────────────────────────────── -->
                <?php $this->load->view('common/apex/page_header', [
                    'pageTitle'       => $PageTitle       ?? 'Purchase Orders',
                    'pageDescription' => $PageDescription ?? '',
                ]); ?>

                <!-- ── Apex Stats Strip ──────────────────────────────────── -->
                <?php
                $initTab    = $InitTab    ?? 'All';
                $initSearch = $InitSearch ?? '';
                $tabFilterMap = [
                    'All'       => ['poStatusFilterTrigger', 'poUserFilterBtn', 'poVendorFilterBtn'],
                    'Received'  => ['poStatusFilterTrigger', 'poUserFilterBtn', 'poVendorFilterBtn'],
                    'Closed'    => ['poStatusFilterTrigger', 'poUserFilterBtn', 'poVendorFilterBtn'],
                    'Cancelled' => ['poStatusFilterTrigger', 'poUserFilterBtn', 'poVendorFilterBtn'],
                    'Draft'     => ['poStatusFilterTrigger', 'poUserFilterBtn', 'poVendorFilterBtn'],
                ];

                if (($JwtData->GenSettings->ShowStats ?? 1) && ($JwtData->TransSettings->ShowTransactionStats ?? 1)):
                $poStats   = $SummaryStats ?? [];
                $allCount  = array_sum(array_column($poStats, 'count'));
                $allAmount = array_sum(array_column($poStats, 'amount'));
                $cur       = htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹');
                
                $statsItems = [
                    ['label' => 'All Purchase Orders', 'status' => 'All',       'icon' => 'bx-file-blank',   'iconBg' => '#eef2ff', 'iconColor' => '#696cff', 'count' => $allCount,                              'amount' => $allAmount],
                    ['label' => 'Received',            'status' => 'Received',  'icon' => 'bx-download',     'iconBg' => '#e0f5f2', 'iconColor' => '#17a2b8', 'count' => $poStats['Received']['count']  ?? 0,    'amount' => $poStats['Received']['amount']  ?? 0],
                    ['label' => 'Closed',              'status' => 'Closed',    'icon' => 'bx-check-circle', 'iconBg' => '#d1e7dd', 'iconColor' => '#198754', 'count' => $poStats['Closed']['count']    ?? 0,    'amount' => $poStats['Closed']['amount']    ?? 0],
                    ['label' => 'Cancelled',           'status' => 'Cancelled', 'icon' => 'bx-x-circle',     'iconBg' => '#f8d7da', 'iconColor' => '#dc3545', 'count' => $poStats['Cancelled']['count'] ?? 0,    'amount' => $poStats['Cancelled']['amount'] ?? 0],
                    ['label' => 'Drafts',              'status' => 'Draft',     'icon' => 'bx-edit',         'iconBg' => '#f1f3f5', 'iconColor' => '#6c757d', 'count' => $poStats['Draft']['count']     ?? 0,    'amount' => $poStats['Draft']['amount']     ?? 0],
                ];
                ?>
                <div class="apex-stats-strip">
                    <?php foreach ($statsItems as $stat): ?>
                    <div class="apex-stat-item <?php echo $stat['status'] === $initTab ? 'active' : ''; ?>" data-status="<?php echo $stat['status']; ?>" style="--stat-color:<?php echo $stat['iconColor']; ?>">
                        <div class="apex-stat-icon" style="background:<?php echo $stat['iconBg']; ?>;">
                            <i class="bx <?php echo $stat['icon']; ?>" style="color:<?php echo $stat['iconColor']; ?>;"></i>
                        </div>
                        <div class="apex-stat-body">
                            <div class="apex-stat-label"><?php echo $stat['label']; ?></div>
                            <div class="apex-stat-bottom">
                                <span class="apex-stat-count"><?php echo $stat['count']; ?></span>
                                <span class="apex-stat-amount"><?php echo $cur . ' ' . smartDecimal($stat['amount']); ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <div class="container-xxl flex-grow-1">

                    <div class="card">

                        <!-- ── Apex Filter Row ───────────────────────────── -->
                        <div class="apex-filter-row">

                            <!-- Data search -->
                            <div class="r2k-search-wrap<?php echo $initSearch ? ' is-expanded r2k-search-active' : ''; ?>" style="flex-shrink:0;">
                                <i class="bx bx-search r2k-si"></i>
                                <input type="text" id="searchTransactionData" placeholder="Search PO #, vendor..." value="<?php echo htmlspecialchars($initSearch, ENT_QUOTES); ?>">
                                <i class="bx bx-x r2k-clear<?php echo $initSearch ? '' : ' d-none'; ?>"></i>
                            </div>

                            <a href="javascript:void(0);" id="poStatusFilterTrigger" class="apex-filter-btn" title="Filter by Status">
                                <i class="bx bx-filter-alt me-1"></i>Status
                            </a>

                            <?php if (count($OrgUsers ?? []) > 1): ?>
                            <button class="apex-filter-btn" id="poUserFilterBtn">
                                <i class="bx bx-user"></i>
                                <span id="poUserFilterLabel">All Users</span>
                                <i class="bx bx-chevron-down"></i>
                            </button>
                            <?php endif; ?>

                            <button class="apex-filter-btn" id="poVendorFilterBtn">
                                <i class="bx bx-store"></i>
                                <span id="poVendorFilterLabel">All Vendors</span>
                                <i class="bx bx-chevron-down"></i>
                            </button>

                            <?php $this->load->view('common/transactions/date_filter_btn'); ?>

                            <div class="apex-filter-spacer"></div>

                            <a href="javascript:void(0);" class="apex-filter-btn pageRefresh" data-bs-toggle="tooltip" data-bs-placement="bottom" title="<?php echo t('page_refresh', 'Page Refresh'); ?>">
                                <i class="bx bx-refresh"></i>
                            </a>

                            <div class="btn-group d-none" id="ActionsDD-Div">
                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="bx bx-slider-alt"></i></button>
                                <ul class="dropdown-menu dropdown-menu-end r2k-export-menu r2k-actions-menu">
                                    <li class="d-none" id="DeleteOption"><a class="dropdown-item text-danger" href="javascript:void(0);" id="btnDelete"><i class="bx bx-trash me-2"></i><?php echo t('delete', 'Delete'); ?></a></li>
                                </ul>
                            </div>

                            <?php $this->load->view('common/partials/export_btn'); ?>

                            <a href="/purchaseorders/create" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" data-bs-placement="bottom" title="<?php echo t('create_purchase_order', 'Create Purchase Order'); ?>">
                                <i class="bx bx-plus me-1"></i><?php echo t('lbl_new', 'New'); ?>
                            </a>

                        </div>

                        <!-- ── Tabs Row ──────────────────────────────────── -->
                        <div class="apex-tabs-row">
                            <ul class="nav trans-status-tabs" id="poStatusTabs" role="tablist" data-trans-path="/purchaseorders">
                                <li class="nav-item"><a class="nav-link <?php echo $initTab === 'All' ? 'active' : ''; ?> po-status-tab" data-status="All" data-url-tab="all" href="javascript:void(0);">All <span class="trans-tab-count ms-1 po-tab-count<?php echo ($initTab !== 'All' || $ModAllCount == 0) ? ' d-none' : ''; ?>"><?php echo ($initTab === 'All' && $ModAllCount > 0) ? $ModAllCount : ''; ?></span></a></li>
                                <li class="nav-item"><a class="nav-link <?php echo $initTab === 'Received' ? 'active' : ''; ?> po-status-tab" data-status="Received" data-url-tab="received" href="javascript:void(0);">Received <span class="trans-tab-count ms-1 po-tab-count<?php echo ($initTab !== 'Received' || $ModAllCount == 0) ? ' d-none' : ''; ?>"><?php echo ($initTab === 'Received' && $ModAllCount > 0) ? $ModAllCount : ''; ?></span></a></li>
                                <li class="nav-item"><a class="nav-link <?php echo $initTab === 'Closed' ? 'active' : ''; ?> po-status-tab" data-status="Closed" data-url-tab="closed" href="javascript:void(0);">Closed <span class="trans-tab-count ms-1 po-tab-count<?php echo ($initTab !== 'Closed' || $ModAllCount == 0) ? ' d-none' : ''; ?>"><?php echo ($initTab === 'Closed' && $ModAllCount > 0) ? $ModAllCount : ''; ?></span></a></li>
                                <li class="nav-item"><a class="nav-link <?php echo $initTab === 'Cancelled' ? 'active' : ''; ?> po-status-tab" data-status="Cancelled" data-url-tab="cancelled" href="javascript:void(0);">Cancelled <span class="trans-tab-count ms-1 po-tab-count<?php echo ($initTab !== 'Cancelled' || $ModAllCount == 0) ? ' d-none' : ''; ?>"><?php echo ($initTab === 'Cancelled' && $ModAllCount > 0) ? $ModAllCount : ''; ?></span></a></li>
                                <li class="nav-item"><a class="nav-link <?php echo $initTab === 'Draft' ? 'active' : ''; ?> po-status-tab" data-status="Draft" data-url-tab="draft" href="javascript:void(0);">Drafts <span class="trans-tab-count ms-1 po-tab-count<?php echo ($initTab !== 'Draft' || $ModAllCount == 0) ? ' d-none' : ''; ?>"><?php echo ($initTab === 'Draft' && $ModAllCount > 0) ? $ModAllCount : ''; ?></span></a></li>
                            </ul>
                            <?php $this->load->view('common/transactions/filter_notice'); ?>
                        </div>

                        <!-- Select-all banner -->
                        <div id="poSelectAllBanner" class="r2k-select-all-banner d-none">
                            <span id="poSelectAllMsg"></span>
                            <a href="javascript:void(0);" id="poSelectAllLink" class="ms-2"></a>
                            <a href="javascript:void(0);" id="poSelectAllClear" class="ms-2 d-none">Clear selection</a>
                        </div>

                        <!-- ── Table ───────────────────────────────────── -->
                        <div class="table-responsive text-nowrap">
                            <table class="table table-hover MainviewTable mb-0" id="poTable">
                                <thead class="r2k-thead bg-body-tertiary">
                                    <tr>
                                        <th class="table-checkbox" style="width:40px">
                                            <div class="form-check">
                                                <input class="form-check-input table-chkbox poHeaderCheck" type="checkbox">
                                            </div>
                                        </th>
                                        <th class="<?php echo $JwtData->GenSettings->SerialNoDisplay == 1 ? '' : 'd-none'; ?> table-serialno" style="width:50px">S.No</th>
                                        <th class="col-sortable cursor-pointer user-select-none" data-sort="Number" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Click for ascending order">
                                            # PO <i class="bx bx-sort-alt-2 ms-1 sort-icon" data-col="Number"></i>
                                        </th>
                                        <th class="col-sortable cursor-pointer user-select-none" data-sort="Amount" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Click for ascending order">
                                            Amount <i class="bx bx-sort-alt-2 ms-1 sort-icon" data-col="Amount"></i>
                                        </th>
                                        <th>Status</th>
                                        <th>Vendor</th>
                                        <th class="col-sortable cursor-pointer user-select-none" data-sort="Date" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Click for ascending order">
                                            PO Date <i class="bx bx-sort-alt-2 ms-1 sort-icon" data-col="Date"></i>
                                        </th>
                                        <th>Expected Date</th>
                                        <th>Last Updated</th>
                                        <th style="width:50px">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="r2k-tbody table-border-bottom-0">
                                    <?php echo $ModRowData; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- ── Pagination ──────────────────────────────── -->
                        <div class="row mx-0 px-3 mt-1 justify-content-between align-items-center poPagination apex-pag-sticky" id="poPagination">
                            <?php echo $ModPagination ? $ModPagination : ''; ?>
                        </div>

                    </div>


                    <?php $this->load->view('common/transactions/print_modals'); ?>

                </div>

            </div>

            <?php $this->load->view('common/footer_desc'); ?>

        </div>
    </div>
</div>

<?php if (count($OrgUsers ?? []) > 1): ?>
<?php $this->load->view('common/filter_panels/col_user_filter_box', [
    'ColUserFilterConfig' => [
        'id'         => 'poCreatedByFilterBox',
        'triggerId'  => 'poUserFilterBtn',
        'checkClass' => 'po-user-chk',
        'OrgUsers'   => $OrgUsers ?? [],
    ],
]); ?>
<?php endif; ?>

<?php $this->load->view('common/filter_panels/col_filter_box', [
    'ColFilterConfig' => [
        'id'         => 'poStatusFilterBox',
        'triggerId'  => 'poStatusFilterTrigger',
        'title'      => 'Status',
        'icon'       => 'bx-filter-alt',
        'filterKey'  => 'Status',
        'checkClass' => 'po-status-chk',
        'items'      => [
            ['value' => 'Received',  'label' => 'Received'],
            ['value' => 'Closed',    'label' => 'Closed'],
            ['value' => 'Cancelled', 'label' => 'Cancelled'],
            ['value' => 'Draft',     'label' => 'Draft'],
        ],
    ],
]); ?>

<?php $this->load->view('common/filter_panels/col_party_filter_box', [
    'ColPartyFilterConfig' => [
        'id'    => 'poPartyFilterBox',
        'title' => 'Filter by Vendor',
        'icon'  => 'bx-store',
    ],
]); ?>

<?php $this->load->view('common/transactions/footer'); ?>

<script src="/js/common/party_filter.js"></script>
<script src="/js/core/viewmodal.js"></script>
<script src="/js/core/a4_print.js"></script>
<script src="/js/transactions/filter_bar.js"></script>
<script src="/js/core/col_filter.js"></script>
<script src="/js/common/pagecheckbox.js"></script>
<script src="/js/transactions/purchaseorders.js"></script>

<script>

const  ModuleId     = 104;
const  ModuleTable  = '#poTable';
const  ModulePag    = '.poPagination';
const  ModuleHeader = '.poHeaderCheck';
const  ModuleRow    = '.poCheck';

var _poInitTab    = <?php echo json_encode($InitTab    ?? 'All'); ?>;
var _poInitSearch = <?php echo json_encode($InitSearch ?? ''); ?>;

var _poTabFilterMap = <?= json_encode($tabFilterMap); ?>;
var _allPoFilterEls = <?= json_encode(array_values(array_unique(array_merge(...array_values($tabFilterMap))))); ?>;
var _initPage       = <?php echo (int)($InitPage ?? 1); ?>;

$(function () {
    'use strict'

    _checkPendingToast('_poPendingToast');
    PageNo = _initPage;
    Filter['Status'] = _poInitTab;
    if (_poInitSearch) { Filter.Name = _poInitSearch; }
    initExport({ moduleUID: 104, getFilters: function () {
        return $.extend({}, Filter,
            poCreatedByFilter ? poCreatedByFilter.getState() : {},
            poPartyFilter     ? poPartyFilter.getState()     : {}
        );
    } });
    _applyTabFilters(_poInitTab, _poTabFilterMap, _allPoFilterEls);

    var tfb = null; // TransFilterBar not used for PO (all options disabled)

    var poStatusFilter = new TransColFilter({
        boxId       : 'poStatusFilterBox',
        triggerId   : 'poStatusFilterTrigger',
        filterKey   : 'Status',
        activeClass : 'has-filter',
        onApply     : function () {
            var vals = poStatusFilter.getState()['Status'] || [];
            // Backend expects a single string; use first selection or 'All'
            Filter.Status = (vals.length === 1) ? vals[0] : 'All';
            $('.po-status-tab').removeClass('active');
            $('.po-status-tab[data-status="' + Filter.Status + '"]').addClass('active');
            $('.apex-stat-item').removeClass('active');
            $('.apex-stat-item[data-status="' + Filter.Status + '"]').addClass('active');
            PageNo = 1;
            getPurchaseOrdersDetails();
        }
    });

    var poCreatedByFilter = (document.getElementById('poCreatedByFilterBox'))
        ? new TransColFilter({
            boxId       : 'poCreatedByFilterBox',
            triggerId   : 'poUserFilterBtn',
            filterKey   : 'UpdatedByUIDs',
            activeClass : 'has-filter',
            onApply     : function () { PageNo = 1; getPurchaseOrdersDetails(); }
        })
        : null;

    var poPartyFilter = new TransPartyColFilter({
        boxId     : 'poPartyFilterBox',
        triggerId : 'poVendorFilterBtn',
        partyType : 'vendor',
        filterKey : 'PartyUID',
        onApply   : function () { PageNo = 1; getPurchaseOrdersDetails(); }
    });

    var _origGetPurchaseOrdersDetails = getPurchaseOrdersDetails;
    getPurchaseOrdersDetails = function (pageNo, rowLimit, filter, afterLoad) {
        var f = $.extend({}, filter || Filter,
            poCreatedByFilter ? poCreatedByFilter.getState() : {},
            poPartyFilter     ? poPartyFilter.getState()     : {}
        );
        _origGetPurchaseOrdersDetails(pageNo, rowLimit, f, afterLoad);
    };

    // ── Create / Edit — inject returnTab + returnPage ──────────────────
    $(document).on('click', 'a[href="/purchaseorders/create"]', function (e) {
        e.preventDefault();
        var params = new URLSearchParams();
        params.set('returnTab', Filter.Status || 'All');
        if (PageNo > 1) params.set('returnPage', PageNo);
        window.location.href = '/purchaseorders/create?' + params.toString();
    });
    $(document).on('click', 'a[href^="/purchaseorders/edit/"]', function (e) {
        e.preventDefault();
        var params = new URLSearchParams();
        params.set('returnTab', Filter.Status || 'All');
        if (PageNo > 1) params.set('returnPage', PageNo);
        window.location.href = $(this).attr('href') + '?' + params.toString();
    });

    // ── Status tabs ─────────────────────────────────────────────────────────
    $(document).on('click', '.po-status-tab', function (e) {
        e.preventDefault();
        SelectedUIDs = []; _poClearSelectAll(); MultipleDeleteOption();
        var s = $(this).data('status') || 'All';
        $('.po-status-tab').removeClass('active');
        $(this).addClass('active');
        $('.apex-stat-item').removeClass('active');
        $('.apex-stat-item[data-status="' + s + '"]').addClass('active');
        _resetPoFilters();
        _applyTabFilters(s, _poTabFilterMap, _allPoFilterEls);
        _updateTransTabUrl(s, '');
        Filter.Status = s;
        PageNo = 1;
        getPurchaseOrdersDetails();
    });

    // ── Stat strip click → trigger corresponding tab ─────────────────────────
    $(document).on('click', '.apex-stat-item', function () {
        $('.po-status-tab[data-status="' + $(this).data('status') + '"]').trigger('click');
    });

    $(document).on('click', '.pageRefresh', function (e) {
        e.preventDefault();
        PageNo = 1;
        getPurchaseOrdersDetails();
    });

    // Data search (filter row)
    $('#searchTransactionData').on('input', function () {
        var curTab = $('.po-status-tab.active').data('status') || 'All';
        _updateTransTabUrl(curTab, $.trim($(this).val()));
    });
    $('#searchTransactionData').on('input', debounce(function () {
        Filter.Name = $.trim($(this).val());
        PageNo = 1;
        getPurchaseOrdersDetails();
    }, 500));

    // Date filter
    $(document).on('r2k:datechange', function (e, dr) {
        Filter.DateFrom = dr.from;
        Filter.DateTo   = dr.to;
        PageNo = 1;
        getPurchaseOrdersDetails();
    });

    // Column sorting
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
        getPurchaseOrdersDetails();
    });

    // Pagination
    $(document).on('click', '.poPagination .page-link', function (e) {
        e.preventDefault();
        var href  = $(this).attr('href') || '';
        var match = href.match(/\/(\d+)$/);
        if (match) {
            PageNo = parseInt(match[1]);
            _poClearSelectAll();
            getPurchaseOrdersDetails();
        }
    });

    function _resetPoFilters() {
        var $wrap = $('#searchTransactionData').closest('.r2k-search-wrap');
        $('#searchTransactionData').val('');
        $wrap.find('.r2k-clear').addClass('d-none');
        $wrap.removeClass('is-expanded r2k-search-active');
        Filter.Name = '';
        if (poStatusFilter)    { poStatusFilter.reset(); }
        if (poCreatedByFilter) { poCreatedByFilter.reset(); }
        if (poPartyFilter)     { poPartyFilter.reset(); }
        $('.trans-col-filterbox, .tpcf-box').hide();
    }

    // ── Inline status update ─────────────────────────────────────────────────
    $(document).on('click', '.po-status-update', function () {
        var uid    = $(this).data('uid');
        var status = $(this).data('status');
        if ($(this).data('_confirmed')) { $(this).removeData('_confirmed'); return; }
        if (status === 'Cancelled') {
            var num = $(this).data('num') || '';
            var lbl = num ? '<strong>' + $('<span>').text(num).html() + '</strong>' : 'this purchase order';
            var $btn = $(this);
            Swal.fire({ title: 'Cancel Purchase Order?', html: 'Are you sure you want to cancel ' + lbl + '? This cannot be undone.', icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#6c757d', confirmButtonText: 'Yes, Cancel It', cancelButtonText: 'No, Keep It' }).then(function (r) { if (!r.isConfirmed) return; $btn.data('_confirmed', true).trigger('click'); });
            return;
        }
        ajaxLoading(1);
        $.ajax({
            url   : '/purchaseorders/updatePurchaseOrderStatus',
            method: 'POST',
            data  : { TransUID: uid, Status: status, [CsrfName]: CsrfToken },
            success: function (resp) {
                ajaxLoading(0);
                hideUIBlock();
                if (resp.Error) { Swal.fire({ icon: 'error', text: resp.Message }); return; }
                showToastNotification(resp.Message || 'Status updated.', 'success');
                getPurchaseOrdersDetails();
            },
            error: function () { ajaxLoading(0); hideUIBlock(); Swal.fire({ icon: 'error', text: 'Request failed. Please try again.' }); }
        });
    });

    // ── Delete ────────────────────────────────────────────────────────────────
    function _actionPostData(extra) {
        Filter.Status = $('.po-status-tab.active').data('status') || 'All';
        return $.extend({ RowLimit: RowLimit, PageNo: PageNo, Filter: Filter, [CsrfName]: CsrfToken }, extra);
    }

    function _renderListResponse(resp) {
        $(ModuleTable + ' tbody').html(resp.RecordHtmlData);
        $(ModulePag).html(resp.Pagination);
        var count = resp.TotalCount || 0;
        var $badge = $('.po-status-tab.active .po-tab-count');
        if (count > 0) { $badge.text(count).removeClass('d-none'); } else { $badge.text('').addClass('d-none'); }
        initTooltips();
    }

    $(document).on('click', '.deletePO', function () {
        var uid = $(this).data('uid');
        var num = $(this).data('num') || '';
        Swal.fire({
            title            : 'Delete Purchase Order?',
            html             : num ? 'Delete <strong>' + num + '</strong>? This cannot be undone.' : 'This cannot be undone.',
            icon             : 'warning',
            showCancelButton : true,
            confirmButtonText: 'Delete',
            confirmButtonColor: '#d33',
        }).then(function (result) {
            if (!result.isConfirmed) return;
            ajaxLoading(1);
            $.ajax({
                url   : '/purchaseorders/deletePurchaseOrder',
                method: 'POST',
                data  : _actionPostData({ TransUID: uid }),
                success: function (resp) {
                    ajaxLoading(0);
                    hideUIBlock();
                    if (resp.Error) { Swal.fire({ icon: 'error', text: resp.Message }); return; }
                    showToastNotification(resp.Message || 'Deleted.', 'success');
                    if (PageNo > 1 && (resp.TotalCount || 0) <= (PageNo - 1) * RowLimit) {
                        PageNo--;
                        getPurchaseOrdersDetails();
                    } else {
                        _renderListResponse(resp);
                    }
                },
                error: function () { ajaxLoading(0); hideUIBlock(); Swal.fire({ icon: 'error', text: 'Request failed. Please try again.' }); }
            });
        });
    });

    // ── Checkbox / select-all wiring ─────────────────────────────────────
    basePageHeaderFunc(ModuleHeader, ModuleTable, ModuleRow);

    $(ModuleHeader).on('click', function () {
        _poUpdateSelectAllBanner();
    });

    $(document).on('change', ModuleRow, function () {
        onClickOfCheckbox(this, ModuleTable, ModuleHeader, ModuleRow);
        _poUpdateSelectAllBanner();
        MultipleDeleteOption();
    });

    $(document).on('click', '#poSelectAllLink', function (e) {
        e.preventDefault();
        _poSelectAllMode = true;
        _poUpdateSelectAllBanner();
    });

    $(document).on('click', '#poSelectAllClear', function (e) {
        e.preventDefault();
        SelectedUIDs = [];
        unSelectTableRecords(ModuleTable, ModuleRow);
        $(ModuleHeader).prop('checked', false).prop('indeterminate', false);
        _poClearSelectAll();
        MultipleDeleteOption();
    });

    // ── Bulk delete ───────────────────────────────────────────────────────
    $('#btnDelete').on('click', function () {
        var count = _poSelectAllMode ? _poTotalRecords : SelectedUIDs.length;
        Swal.fire({
            title: 'Delete ' + count + ' purchase order' + (count === 1 ? '' : 's') + '?',
            text : 'This cannot be undone.',
            icon : 'warning', showCancelButton: true,
            confirmButtonText: 'Delete', confirmButtonColor: '#d33',
        }).then(function (r) {
            if (!r.isConfirmed) return;
            deleteMultiplePurchaseOrders();
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


</script>
