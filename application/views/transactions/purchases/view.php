<?php defined('BASEPATH') or exit('No direct script access allowed');
$this->load->view('common/transactions/header'); ?>

<div class="layout-wrapper layout-horizontal layout-content-navbar">
    <div class="layout-container">

        <?php $this->load->view('common/menu_view'); ?>

        <div class="layout-page">

            <div class="content-wrapper apex-content">
                <?php $this->load->view('common/apex/page_header', [
                    'pageTitle'       => $PageTitle       ?? 'Purchases',
                    'pageDescription' => $PageDescription ?? '',
                ]); ?>
                <?php
                $initTab    = $InitTab    ?? 'All';
                $initSearch = $InitSearch ?? '';
                $tabFilterMap = [
                    'All'       => ['purchPayStatusFilter', 'purchPayModeFilter', 'purchCreatedByFilter', 'purchPartyFilterTrigger'],
                    'Pending'   => ['purchPayStatusFilter', 'purchPayModeFilter', 'purchCreatedByFilter', 'purchPartyFilterTrigger'],
                    'Paid'      => ['purchPayModeFilter', 'purchCreatedByFilter', 'purchPartyFilterTrigger'],
                    'Cancelled' => ['purchCreatedByFilter', 'purchPartyFilterTrigger'],
                    'Draft'     => ['purchCreatedByFilter', 'purchPartyFilterTrigger'],
                ];
                $visibleFilters = $tabFilterMap[$initTab] ?? $tabFilterMap['All'];

                if (($JwtData->GenSettings->ShowStats ?? 1) && ($JwtData->TransSettings->ShowTransactionStats ?? 1)):
                $stats       = $SummaryStats ?? [];
                $cur         = htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹');
                
                $activePurchStatuses = ['Received', 'Partial', 'Paid'];
                $cntAll     = array_sum(array_map(fn($s) => $stats[$s]['count']  ?? 0, $activePurchStatuses));
                $amtAll     = array_sum(array_map(fn($s) => $stats[$s]['amount'] ?? 0, $activePurchStatuses));
                $cntPending = ($stats['Received']['count']  ?? 0) + ($stats['Partial']['count']  ?? 0);
                $amtPending = ($stats['Received']['amount'] ?? 0) + ($stats['Partial']['amount'] ?? 0);
                $cntPaid    = $stats['Paid']['count']  ?? 0;
                $amtPaid    = $stats['Paid']['amount'] ?? 0;
                $cntDraft   = $stats['Draft']['count'] ?? 0;

                $statsItems = [
                    ['label' => 'All Purchases',    'status' => 'All',     'icon' => 'bx-package',      'iconBg' => '#eef2ff', 'iconColor' => '#696cff', 'count' => $cntAll,     'amount' => $amtAll],
                    ['label' => 'Pending Payment',  'status' => 'Pending', 'icon' => 'bx-time-five',    'iconBg' => '#fff7ed', 'iconColor' => '#f97316', 'count' => $cntPending, 'amount' => $amtPending],
                    ['label' => 'Paid',             'status' => 'Paid',    'icon' => 'bx-check-circle', 'iconBg' => '#dcfce7', 'iconColor' => '#16a34a', 'count' => $cntPaid,    'amount' => $amtPaid],
                    ['label' => 'Drafts',           'status' => 'Draft',   'icon' => 'bx-edit',          'iconBg' => '#f1f5f9', 'iconColor' => '#64748b', 'count' => $cntDraft,   'amount' => 0],
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
                                <span class="apex-stat-amount"><?php echo $cur . ' ' . smartDecimal($stat['amount']); ?></span>
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
                                <input type="text" id="searchTransactionData" placeholder="Bill # or vendor..." value="<?php echo htmlspecialchars($initSearch); ?>">
                                <i class="bx bx-x r2k-clear<?php echo $initSearch ? '' : ' d-none'; ?>"></i>
                            </div>
                            <a href="javascript:void(0);" id="purchPayStatusFilter" class="apex-filter-btn<?php echo in_array('purchPayStatusFilter', $visibleFilters) ? '' : ' d-none'; ?>" title="Filter by Payment Status"><i class="bx bx-wallet-alt me-1"></i>Pay Status</a>
                            <a href="javascript:void(0);" id="purchPayModeFilter" class="apex-filter-btn<?php echo in_array('purchPayModeFilter', $visibleFilters) ? '' : ' d-none'; ?>" title="Filter by Payment Mode"><i class="bx bx-credit-card me-1"></i>Pay Mode</a>
                            <?php if (count($OrgUsers ?? []) > 1): ?>
                            <a href="javascript:void(0);" id="purchCreatedByFilter" class="apex-filter-btn<?php echo in_array('purchCreatedByFilter', $visibleFilters) ? '' : ' d-none'; ?>" title="Filter by User"><i class="bx bx-user me-1"></i>Updated By</a>
                            <?php endif; ?>
                            <a href="javascript:void(0);" id="purchPartyFilterTrigger" class="apex-filter-btn<?php echo in_array('purchPartyFilterTrigger', $visibleFilters) ? '' : ' d-none'; ?>" title="Filter by Vendor"><i class="bx bx-store me-1"></i>Vendor</a>
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
                            <a href="/purchases/create" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" data-bs-placement="bottom" title="<?php echo t('create_purchase', 'Create Purchase Bill'); ?>"><i class="bx bx-plus me-1"></i><?php echo t('lbl_new', 'New'); ?></a>
                        </div>

                        <!-- ── Tabs Row ──────────────────────────────────── -->
                        <div class="apex-tabs-row">
                            <ul class="nav trans-status-tabs" id="purchStatusTabs" role="tablist" data-trans-path="/purchases">
                                <li class="nav-item"><a class="nav-link<?php echo $initTab === 'All' ? ' active' : ''; ?> purch-status-tab" data-status="All" data-url-tab="all" href="javascript:void(0);">All <span class="trans-tab-count ms-1<?php echo ($initTab !== 'All' || $ModAllCount == 0) ? ' d-none' : ''; ?>"><?php echo ($initTab === 'All' && $ModAllCount > 0) ? $ModAllCount : ''; ?></span></a></li>
                                <li class="nav-item"><a class="nav-link<?php echo $initTab === 'Pending' ? ' active' : ''; ?> purch-status-tab" data-status="Pending" data-url-tab="pending" href="javascript:void(0);">Pending <span class="trans-tab-count ms-1<?php echo ($initTab !== 'Pending' || $ModAllCount == 0) ? ' d-none' : ''; ?>"><?php echo ($initTab === 'Pending' && $ModAllCount > 0) ? $ModAllCount : ''; ?></span></a></li>
                                <li class="nav-item"><a class="nav-link<?php echo $initTab === 'Paid' ? ' active' : ''; ?> purch-status-tab" data-status="Paid" data-url-tab="paid" href="javascript:void(0);">Paid <span class="trans-tab-count ms-1<?php echo ($initTab !== 'Paid' || $ModAllCount == 0) ? ' d-none' : ''; ?>"><?php echo ($initTab === 'Paid' && $ModAllCount > 0) ? $ModAllCount : ''; ?></span></a></li>
                                <li class="nav-item"><a class="nav-link<?php echo $initTab === 'Cancelled' ? ' active' : ''; ?> purch-status-tab" data-status="Cancelled" data-url-tab="cancelled" href="javascript:void(0);">Cancelled <span class="trans-tab-count ms-1<?php echo ($initTab !== 'Cancelled' || $ModAllCount == 0) ? ' d-none' : ''; ?>"><?php echo ($initTab === 'Cancelled' && $ModAllCount > 0) ? $ModAllCount : ''; ?></span></a></li>
                                <li class="nav-item"><a class="nav-link<?php echo $initTab === 'Draft' ? ' active' : ''; ?> purch-status-tab" data-status="Draft" data-url-tab="draft" href="javascript:void(0);">Drafts <span class="trans-tab-count ms-1<?php echo ($initTab !== 'Draft' || $ModAllCount == 0) ? ' d-none' : ''; ?>"><?php echo ($initTab === 'Draft' && $ModAllCount > 0) ? $ModAllCount : ''; ?></span></a></li>
                                <li class="nav-item"><a class="nav-link<?php echo $initTab === 'DebitNotes' ? ' active' : ''; ?>" id="purchDnTab" href="javascript:void(0);">Debit Notes <span class="trans-tab-count ms-1 d-none" id="purchDnTabCount"></span></a></li>
                            </ul>
                            <?php $this->load->view('common/transactions/filter_notice'); ?>
                        </div>

                        <!-- Select-all banner -->
                        <div id="purchSelectAllBanner" class="r2k-select-all-banner d-none">
                            <span id="purchSelectAllMsg"></span>
                            <a href="javascript:void(0);" id="purchSelectAllLink" class="ms-2"></a>
                            <a href="javascript:void(0);" id="purchSelectAllClear" class="ms-2 d-none">Clear selection</a>
                        </div>

                        <!-- Table -->
                        <div class="table-responsive" id="purchTableWrap">
                            <table class="table trans-table table-hover MainviewTable mb-0" id="purchTable">
                                <thead class="r2k-thead">
                                    <tr>
                                        <th style="width:36px">
                                            <div class="form-check mb-0">
                                                <input class="form-check-input table-chkbox purchHeaderCheck" type="checkbox">
                                            </div>
                                        </th>
                                        <th class="<?php echo $JwtData->GenSettings->SerialNoDisplay == 1 ? '' : 'd-none'; ?> table-serialno" style="width:44px">S.No</th>
                                        <th class="col-sortable cursor-pointer user-select-none" data-sort="Number" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Click for ascending order">
                                            # Bill <i class="bx bx-sort-alt-2 ms-1 sort-icon" data-col="Number"></i>
                                        </th>
                                        <th class="col-sortable cursor-pointer user-select-none" data-sort="Amount" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Click for ascending order">
                                            Amount <i class="bx bx-sort-alt-2 ms-1 sort-icon" data-col="Amount"></i>
                                        </th>
                                        <th>Payment Status</th>
                                        <th>Payment Mode</th>
                                        <th>Vendor</th>
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
                        <div class="row mx-0 px-3 mt-1 justify-content-between align-items-center purchPagination apex-pag-sticky" id="purchPagination">
                            <?php echo $ModPagination ?: ''; ?>
                        </div>

                        <!-- ── Debit Notes section (hidden until tab clicked) ── -->
                        <div id="purchDnSection" class="d-none px-3 pb-3">
                            <div class="d-flex align-items-center justify-content-between mb-2 mt-2">
                                <div class="d-flex gap-2">
                                    <button class="btn btn-sm btn-outline-secondary dn-status-btn active" data-status="All">All</button>
                                    <button class="btn btn-sm btn-outline-warning dn-status-btn" data-status="Pending">Pending</button>
                                    <button class="btn btn-sm btn-outline-success dn-status-btn" data-status="Applied">Applied</button>
                                    <button class="btn btn-sm btn-outline-info dn-status-btn" data-status="Refunded">Refunded</button>
                                </div>
                                <input type="text" class="form-control form-control-sm dn-search-input" id="dnSearch" placeholder="Search bill # or vendor…">
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover table-sm mb-0">
                                    <thead class="r2k-thead"><tr>
                                        <th style="width:36px">#</th>
                                        <th>Source Bill / Vendor</th>
                                        <th>Status</th>
                                        <th>Amount</th>
                                        <th>Created</th>
                                        <th style="width:80px">Actions</th>
                                    </tr></thead>
                                    <tbody id="purchDnTableBody">
                                        <tr><td colspan="6" class="text-center text-muted py-4">Loading…</td></tr>
                                    </tbody>
                                </table>
                            </div>
                            <div id="purchDnPagination" class="mt-2"></div>
                        </div>

                    </div>

                    <?php $this->load->view('common/transactions/print_modals'); ?>


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

<?php $this->load->view('common/filter_panels/col_filter_box', [
    'ColFilterConfig' => [
        'id'         => 'purchPayStatusFilterBox',
        'triggerId'  => 'purchPayStatusFilter',
        'title'      => 'Payment Status',
        'icon'       => 'bx-wallet-alt',
        'filterKey'  => 'PaymentStatus',
        'checkClass' => 'purch-pay-status-chk',
        'items'      => [
            ['value' => 'Pending',        'label' => 'Pending',        'icon' => 'bx-time-five',    'color' => '#e65100'],
            ['value' => 'Partially Paid', 'label' => 'Partially Paid', 'icon' => 'bx-adjust',       'color' => '#0d47a1'],
            ['value' => 'Paid',           'label' => 'Paid',           'icon' => 'bx-check-circle', 'color' => '#2e7d32'],
        ],
    ],
]); ?>

<?php $this->load->view('common/filter_panels/col_filter_box', [
    'ColFilterConfig' => [
        'id'         => 'purchPayModeFilterBox',
        'triggerId'  => 'purchPayModeFilter',
        'title'      => 'Payment Mode',
        'icon'       => 'bx-credit-card',
        'filterKey'  => 'PaymentMode',
        'checkClass' => 'purch-pay-mode-chk',
        'items'      => array_map(function($t) {
            return ['value' => $t->Name, 'label' => $t->Name, 'icon' => 'bx-credit-card', 'color' => '#6f42c1'];
        }, $PaymentTypes ?? []),
    ],
]); ?>

<?php if (count($OrgUsers ?? []) > 1): ?>
<?php $this->load->view('common/filter_panels/col_user_filter_box', [
    'ColUserFilterConfig' => [
        'id'         => 'purchCreatedByFilterBox',
        'triggerId'  => 'purchCreatedByFilter',
        'checkClass' => 'purch-user-chk',
        'OrgUsers'   => $OrgUsers ?? [],
    ],
]); ?>
<?php endif; ?>

<?php $this->load->view('common/filter_panels/col_party_filter_box', [
    'ColPartyFilterConfig' => [
        'id'    => 'purchPartyFilterBox',
        'title' => 'Filter by Vendor',
        'icon'  => 'bx-store',
    ],
]); ?>

<?php $this->load->view('common/transactions/footer'); ?>

<script src="/js/common/communication.js"></script>
<script src="/js/common/party_filter.js"></script>
<script src="/js/transactions/attachments.js"></script>
<script src="/js/core/viewmodal.js"></script>
<script src="/js/core/a4_print.js"></script>
<script src="/js/transactions/filter_bar.js"></script>
<script src="/js/core/col_filter.js"></script>
<script src="/js/common/pagecheckbox.js"></script>
<script src="/js/transactions/purchases.js"></script>

<script>

const ModuleId     = 105;
const ModuleTable  = '#purchTable';
const ModulePag    = '.purchPagination';
const ModuleHeader = '.purchHeaderCheck';
const ModuleRow    = '.purchCheck';

var _purchInitTab    = <?php echo json_encode($InitTab    ?? 'All'); ?>;
var _purchInitSearch = <?php echo json_encode($InitSearch ?? ''); ?>;
var _initPage        = <?php echo (int)($InitPage ?? 1); ?>;

$(function () {
    'use strict';

    _checkPendingToast('_purPendingToast');
    // Bootstrap tooltips
    [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]')).map(function (el) {
        return new bootstrap.Tooltip(el, { container: 'body' });
    });

    PageNo = _initPage;
    Filter['Status'] = _purchInitTab;
    if (_purchInitSearch) { Filter.Name = _purchInitSearch; }
    initExport({ moduleUID: 105, getFilters: function () {
        return $.extend({}, Filter,
            payStatusFilter      ? payStatusFilter.getState()      : {},
            payModeFilter        ? payModeFilter.getState()        : {},
            purchCreatedByFilter ? purchCreatedByFilter.getState() : {},
            purchPartyFilter     ? purchPartyFilter.getState()     : {}
        );
    } });

    // ── Column-level Payment Status filter ──────────────────────────────
    var payStatusFilter = new TransColFilter({
        boxId       : 'purchPayStatusFilterBox',
        triggerId   : 'purchPayStatusFilter',
        filterKey   : 'PaymentStatus',
        activeClass : 'has-filter',
        onApply     : function () { PageNo = 1; getPurchasesDetails(); }
    });

    var payModeFilter = new TransColFilter({
        boxId       : 'purchPayModeFilterBox',
        triggerId   : 'purchPayModeFilter',
        filterKey   : 'PaymentMode',
        activeClass : 'has-filter',
        onApply     : function () { PageNo = 1; getPurchasesDetails(); }
    });

    var purchCreatedByFilter = (document.getElementById('purchCreatedByFilterBox'))
        ? new TransColFilter({
            boxId       : 'purchCreatedByFilterBox',
            triggerId   : 'purchCreatedByFilter',
            filterKey   : 'UpdatedByUIDs',
            activeClass : 'has-filter',
            onApply     : function () { PageNo = 1; getPurchasesDetails(); }
        })
        : null;

    var purchPartyFilter = new TransPartyColFilter({
        boxId     : 'purchPartyFilterBox',
        triggerId : 'purchPartyFilterTrigger',
        partyType : 'vendor',
        filterKey : 'PartyUID',
        onApply   : function () { PageNo = 1; getPurchasesDetails(); }
    });

    var _origGetPurchasesDetails = getPurchasesDetails;
    getPurchasesDetails = function (pageNo, rowLimit, filter, afterLoad) {
        var f = $.extend({}, filter || Filter,
            payStatusFilter      ? payStatusFilter.getState()      : {},
            payModeFilter        ? payModeFilter.getState()        : {},
            purchCreatedByFilter ? purchCreatedByFilter.getState() : {},
            purchPartyFilter     ? purchPartyFilter.getState()     : {}
        );
        _origGetPurchasesDetails(pageNo, rowLimit, f, afterLoad);
    };

    // ── Create / Edit — inject returnTab + returnPage ──────────────────
    $(document).on('click', 'a[href="/purchases/create"]', function (e) {
        e.preventDefault();
        var params = new URLSearchParams();
        params.set('returnTab', Filter.Status || 'All');
        if (PageNo > 1) params.set('returnPage', PageNo);
        window.location.href = '/purchases/create?' + params.toString();
    });
    $(document).on('click', 'a[href^="/purchases/edit/"]', function (e) {
        e.preventDefault();
        var params = new URLSearchParams();
        params.set('returnTab', Filter.Status || 'All');
        if (PageNo > 1) params.set('returnPage', PageNo);
        window.location.href = $(this).attr('href') + '?' + params.toString();
    });

    // ── Tab filter visibility ────────────────────────────────────
    var _purchTabFilterMap = <?= json_encode($tabFilterMap); ?>;
    var _allPurchFilterEls = <?= json_encode(array_values(array_unique(array_merge(...array_values($tabFilterMap))))); ?>;

    function _resetPurchFilters() {
        var $wrap = $('#searchTransactionData').closest('.r2k-search-wrap');
        $('#searchTransactionData').val('');
        $wrap.find('.r2k-clear').addClass('d-none');
        $wrap.removeClass('is-expanded r2k-search-active');
        Filter.Name = '';
        if (payStatusFilter)      { payStatusFilter.reset(); }
        if (payModeFilter)        { payModeFilter.reset(); }
        if (purchCreatedByFilter) { purchCreatedByFilter.reset(); }
        if (purchPartyFilter)     { purchPartyFilter.reset(); }
        $('.trans-col-filterbox, .tpcf-box').hide();
    }

    _applyTabFilters(_purchInitTab, _purchTabFilterMap, _allPurchFilterEls);

    // ── Stat card → filter ──────────────────────────────────────
    $(document).on('click', '[data-stat-filter]', function () {
        var status = $(this).data('stat-filter') || 'All';
        _resetPurchFilters();
        $('.apex-stat-item').removeClass('active');
        $(this).addClass('active');
        $('.purch-status-tab').removeClass('active');
        $('.purch-status-tab[data-status="' + status + '"]').addClass('active');
        _applyTabFilters(status, _purchTabFilterMap, _allPurchFilterEls);
        Filter.Status = status;
        PageNo = 1;
        _updateTransTabUrl(status, '');
        getPurchasesDetails();
    });

    // ── Status tabs ─────────────────────────────────────────────
    $(document).on('click', '.purch-status-tab', function (e) {
        e.preventDefault();
        SelectedUIDs = []; _purchClearSelectAll(); MultipleDeleteOption();
        _resetPurchFilters();
        $('.purch-status-tab').removeClass('active');
        $(this).addClass('active');
        $('.apex-stat-item').removeClass('active');
        var status = $(this).data('status') || 'All';
        $('.apex-stat-item[data-stat-filter="' + status + '"]').addClass('active');
        _applyTabFilters(status, _purchTabFilterMap, _allPurchFilterEls);
        Filter.Status = status;
        PageNo = 1;
        _updateTransTabUrl(status, '');
        getPurchasesDetails();
    });

    // ── Refresh ─────────────────────────────────────────────────
    $(document).on('click', '.pageRefresh', function (e) {
        e.preventDefault();
        PageNo = 1;
        getPurchasesDetails();
    });

    // ── Search ──────────────────────────────────────────────────
    $('#searchTransactionData').on('input', function () {
        var curTab = $('.purch-status-tab.active').data('status') || 'All';
        _updateTransTabUrl(curTab, $.trim($(this).val()));
    });
    $('#searchTransactionData').on('input', debounce(function () {
        Filter.Name = $.trim($(this).val());
        PageNo = 1;
        getPurchasesDetails();
    }, 1500));

    // ── Date filter ─────────────────────────────────────────────
    $(document).on('r2k:datechange', function (e, dr) {
        Filter.DateFrom = dr.from;
        Filter.DateTo   = dr.to;
        PageNo = 1;
        getPurchasesDetails();
    });

    // ── Column sort ─────────────────────────────────────────────
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
        getPurchasesDetails();
    });

    // ── Pagination ──────────────────────────────────────────────
    $(document).on('click', '.purchPagination .page-link', function (e) {
        e.preventDefault();
        var match = ($(this).attr('href') || '').match(/\/(\d+)$/);
        if (match) { PageNo = parseInt(match[1]); _purchClearSelectAll(); getPurchasesDetails(); }
    });

    // ── Purchase cancel-action setting (mirrors InvoiceCancelAction) ────────
    var _purchCancelSetting = '<?php echo addslashes($JwtData->TransSettings->PurchaseCancelAction ?? 'ask'); ?>';

    var _purchCancelActionMeta = {
        debit_note : {
            label: 'Convert to Debit Note',
            desc : 'The paid amount will be raised as a <strong>Debit Note</strong> against this vendor. It will sit on their account and can be offset against your next purchase from them.'
        },
        refund : {
            label: 'Mark as Refund',
            desc : 'The paid amount will be marked as a <strong>Refund</strong> due from this vendor. The actual cash or bank transfer must be arranged separately.'
        }
    };

    /**
     * @param {string} defaultAction
     * @returns {string}
     */
    function _buildPurchPaymentActionHtml(defaultAction) {
        var isAsk = (defaultAction === 'ask');
        var html  = '';
        if (isAsk) {
            html += '<div class="mt-3 text-start">';
            html += '<label class="form-label fw-semibold small mb-1">Select action for the paid amount:</label>';
            html += '<select class="form-select form-select-sm" id="swalPurchCancelAction">';
            html += '<option value="">— Choose an action —</option>';
            $.each(_purchCancelActionMeta, function (val, m) {
                html += '<option value="' + val + '">' + m.label + '</option>';
            });
            html += '</select>';
            html += '<div id="swalPurchCancelDesc" class="text-muted small mt-2 p-2 rounded" style="background:#f8f9fa;min-height:36px;"></div>';
            html += '</div>';
        } else {
            var meta = _purchCancelActionMeta[defaultAction] || {};
            html += '<div class="mt-3 text-start" id="swalPurchPresetWrap">';
            html += '<div class="p-2 rounded small" style="background:#f0f4ff;border-left:3px solid #696cff;">' + (meta.desc || '') + '</div>';
            html += '<a href="javascript:void(0)" class="small text-primary mt-2 d-inline-block" id="swalPurchChangeAction">&#9998; Click here to change</a>';
            html += '</div>';
            html += '<div class="mt-2 text-start d-none" id="swalPurchChangeWrap">';
            html += '<label class="form-label fw-semibold small mb-1">Select a different action:</label>';
            html += '<select class="form-select form-select-sm" id="swalPurchCancelAction">';
            $.each(_purchCancelActionMeta, function (val, m) {
                html += '<option value="' + val + '"' + (val === defaultAction ? ' selected' : '') + '>' + m.label + '</option>';
            });
            html += '</select>';
            html += '<div id="swalPurchCancelDesc" class="text-muted small mt-2 p-2 rounded" style="background:#f8f9fa;">' + (meta.desc || '') + '</div>';
            html += '</div>';
        }
        return html;
    }

    // ── Delete ──────────────────────────────────────────────────
    function _actionPostData(extra) {
        Filter.Status = $('.purch-status-tab.active').data('status') || 'All';
        return $.extend({ RowLimit: RowLimit, PageNo: PageNo, Filter: Filter, [CsrfName]: CsrfToken }, extra);
    }

    function _renderListResponse(resp) {
        $(ModuleTable + ' tbody').html(resp.RecordHtmlData);
        $(ModulePag).html(resp.Pagination);
        var count = resp.TotalCount || 0;
        var $badge = $('.purch-status-tab.active .trans-tab-count');
        if (count > 0) { $badge.text(count).removeClass('d-none'); } else { $badge.text('').addClass('d-none'); }
        initTooltips();
    }

    $(document).on('click', '.deletePurchase', function () {
        var uid          = $(this).data('uid');
        var num          = $(this).data('num') || '';
        var paidAmt      = parseFloat($(this).data('paid') || 0);
        var debitApplied = $(this).data('debit-applied') == '1';
        var hasPaid      = paidAmt > 0;

        // Guard: debit note already applied — block and explain
        if (debitApplied) {
            Swal.fire({
                icon : 'warning',
                title: 'Cannot Delete',
                text : 'A debit note credit has been applied to this purchase bill. Remove the debit note payment entry first, then delete this bill.',
            });
            return;
        }

        var baseHtml = num ? 'Delete <strong>' + num + '</strong>? This cannot be undone.' : 'This cannot be undone.';
        if (hasPaid) {
            baseHtml += '<div class="mt-2 text-muted small">Paid amount: <strong>&#8377;' +
                paidAmt.toLocaleString('en-IN', { minimumFractionDigits: 2 }) + '</strong></div>';
            baseHtml += _buildPurchPaymentActionHtml(_purchCancelSetting);
        }

        Swal.fire({
            title: 'Delete Purchase Bill?',
            html : baseHtml,
            icon : 'warning', showCancelButton: true,
            confirmButtonText: 'Delete', confirmButtonColor: '#d33',
            didOpen: function () {
                $(document).on('change', '#swalPurchCancelAction', function () {
                    var val = $(this).val();
                    $('#swalPurchCancelDesc').html(val && _purchCancelActionMeta[val] ? _purchCancelActionMeta[val].desc : '');
                });
                $(document).on('click', '#swalPurchChangeAction', function () {
                    $('#swalPurchPresetWrap').addClass('d-none');
                    $('#swalPurchChangeWrap').removeClass('d-none');
                });
            },
            willClose: function () {
                $(document).off('change', '#swalPurchCancelAction');
                $(document).off('click', '#swalPurchChangeAction');
            }
        }).then(function (r) {
            if (!r.isConfirmed) return;

            var cancelPaymentAction = '';
            if (hasPaid) {
                var chosen = $('#swalPurchCancelAction').val();
                if (_purchCancelSetting === 'ask') {
                    cancelPaymentAction = chosen || '';
                    if (!cancelPaymentAction) {
                        Swal.fire({ icon: 'warning', text: 'Please select an action for the paid amount.' });
                        return;
                    }
                } else {
                    cancelPaymentAction = chosen || _purchCancelSetting;
                }
            }

            ajaxLoading(1);
            $.ajax({
                url   : '/purchases/deletePurchase',
                method: 'POST',
                data  : _actionPostData({ TransUID: uid, CancelPaymentAction: cancelPaymentAction }),
                success: function (resp) {
                    ajaxLoading(0);
                    hideUIBlock();
                    if (resp.Error) { Swal.fire({ icon: 'error', text: resp.Message }); return; }
                    showToastNotification(resp.Message || 'Deleted.', 'success');
                    if (PageNo > 1 && (resp.TotalCount || 0) <= (PageNo - 1) * RowLimit) {
                        PageNo--;
                        getPurchasesDetails();
                    } else {
                        _renderListResponse(resp);
                    }
                },
                error: function () { ajaxLoading(0); hideUIBlock(); Swal.fire({ icon: 'error', text: 'Request failed. Please try again.' }); }
            });
        });
    });

    // ── Cancel ──────────────────────────────────────────────────
    $(document).on('click', '.purch-status-update', function () {
        var uid          = $(this).data('uid');
        var num          = $(this).data('num') || '';
        var status       = $(this).data('status') || 'Cancelled';
        var paidAmt      = parseFloat($(this).data('paid') || 0);
        var debitApplied = $(this).data('debit-applied') == '1';
        var hasPaid      = paidAmt > 0;

        // Guard: debit note already applied — block cancel entirely
        if (debitApplied) {
            Swal.fire({
                icon : 'warning',
                title: 'Cannot Cancel',
                text : 'A debit note credit has been applied to this purchase bill. Remove the debit note payment entry first, then cancel this bill.',
            });
            return;
        }

        var baseHtml = num ? 'Cancel <strong>' + num + '</strong>? This cannot be undone.' : 'This cannot be undone.';
        if (hasPaid) {
            baseHtml += '<div class="mt-2 text-muted small">Paid amount: <strong>&#8377;' +
                paidAmt.toLocaleString('en-IN', { minimumFractionDigits: 2 }) + '</strong></div>';
            baseHtml += _buildPurchPaymentActionHtml(_purchCancelSetting);
        }
        baseHtml += '<div class="mt-3 text-start">' +
            '<label class="form-label fw-semibold small mb-1">Reason for cancellation ' +
            '<span class="text-muted fw-normal">(optional)</span></label>' +
            '<textarea class="form-control form-control-sm" id="swalPurchCancelReason" rows="2" ' +
            'maxlength="500" placeholder="e.g. Wrong vendor, duplicate entry…"></textarea>' +
            '</div>';

        Swal.fire({
            title             : 'Cancel Purchase Bill?',
            html              : baseHtml,
            icon              : 'warning',
            showCancelButton  : true,
            confirmButtonText : 'Yes, Cancel It',
            confirmButtonColor: '#fd7e14',
            didOpen: function () {
                var $icon = $(Swal.getIcon());
                $icon.css({ width: '3em', height: '3em', borderWidth: '2px' });
                $icon.find('.swal2-icon-content').css({ fontSize: '1.5em' });
                $(document).on('change', '#swalPurchCancelAction', function () {
                    var val = $(this).val();
                    $('#swalPurchCancelDesc').html(val && _purchCancelActionMeta[val] ? _purchCancelActionMeta[val].desc : '');
                });
                $(document).on('click', '#swalPurchChangeAction', function () {
                    $('#swalPurchPresetWrap').addClass('d-none');
                    $('#swalPurchChangeWrap').removeClass('d-none');
                });
            },
            willClose: function () {
                $(document).off('change', '#swalPurchCancelAction');
                $(document).off('click', '#swalPurchChangeAction');
            }
        }).then(function (r) {
            if (!r.isConfirmed) return;

            var cancelPaymentAction = '';
            if (hasPaid) {
                var chosen = $('#swalPurchCancelAction').val();
                if (_purchCancelSetting === 'ask') {
                    cancelPaymentAction = chosen || '';
                    if (!cancelPaymentAction) {
                        Swal.fire({ icon: 'warning', text: 'Please select an action for the paid amount.' });
                        return;
                    }
                } else {
                    cancelPaymentAction = chosen || _purchCancelSetting;
                }
            }

            var cancelReason = $.trim($('#swalPurchCancelReason').val() || '');

            ajaxLoading(1);
            $.ajax({
                url   : '/purchases/updatePurchaseStatus',
                method: 'POST',
                data  : { TransUID: uid, Status: status, CancelPaymentAction: cancelPaymentAction, CancelReason: cancelReason, [CsrfName]: CsrfToken },
                success: function (resp) {
                    ajaxLoading(0);
                    hideUIBlock();
                    if (resp.Error) { Swal.fire({ icon: 'error', text: resp.Message }); return; }
                    var msg = resp.Message || 'Cancelled.';
                    if (resp.DebitNoteAmount) {
                        msg += '<br><small class="text-muted mt-1 d-block">Debit Note: <strong>&#8377;' +
                               parseFloat(resp.DebitNoteAmount).toLocaleString('en-IN', { minimumFractionDigits: 2 }) +
                               '</strong> (Pending)</small>';
                    }
                    showToastNotification(msg, 'success');
                    getPurchasesDetails();
                },
                error: function () { ajaxLoading(0); hideUIBlock(); Swal.fire({ icon: 'error', text: 'Request failed. Please try again.' }); }
            });
        });
    });

    // ── Checkbox / select-all wiring ─────────────────────────────────────
    basePageHeaderFunc(ModuleHeader, ModuleTable, ModuleRow);

    $(ModuleHeader).on('click', function () {
        _purchUpdateSelectAllBanner();
    });

    $(document).on('change', ModuleRow, function () {
        onClickOfCheckbox(this, ModuleTable, ModuleHeader, ModuleRow);
        _purchUpdateSelectAllBanner();
        MultipleDeleteOption();
    });

    $(document).on('click', '#purchSelectAllLink', function (e) {
        e.preventDefault();
        _purchSelectAllMode = true;
        _purchUpdateSelectAllBanner();
    });

    $(document).on('click', '#purchSelectAllClear', function (e) {
        e.preventDefault();
        SelectedUIDs = [];
        unSelectTableRecords(ModuleTable, ModuleRow);
        $(ModuleHeader).prop('checked', false).prop('indeterminate', false);
        _purchClearSelectAll();
        MultipleDeleteOption();
    });

    // ── Bulk delete ───────────────────────────────────────────────────────
    $('#btnDelete').on('click', function () {
        var count = _purchSelectAllMode ? _purchTotalRecords : SelectedUIDs.length;
        Swal.fire({
            title: 'Delete ' + count + ' purchase' + (count === 1 ? '' : 's') + '?',
            text : 'This cannot be undone.',
            icon : 'warning', showCancelButton: true,
            confirmButtonText: 'Delete', confirmButtonColor: '#d33',
        }).then(function (r) {
            if (!r.isConfirmed) return;
            deleteMultiplePurchases();
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
    var _currency   = '<?php echo htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹'); ?>';

    if (typeof window.initRecordPaymentModal === 'function') {
        window.initRecordPaymentModal(_payTypes, _bankAccts, _currency);
    }

    /**
     * @param {Object} resp
     * @returns {void}
     */
    window.rpAfterSuccess = function (resp) {
        if (resp.RecordHtmlData) {
            $(ModuleTable + ' tbody').html(resp.RecordHtmlData);
            $(ModulePag).html(resp.Pagination || '');
            [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]')).map(function (el) {
                return new bootstrap.Tooltip(el, { container: 'body' });
            });
            if (resp.SummaryStats) { updateSummaryStats(resp.SummaryStats); }
        }
    };

    /**
     * @param {FormData} fd
     * @returns {void}
     */
    window.rpBeforeSend = function (fd) {
        fd.append('CurrentPage', PageNo  || 1);
        fd.append('RowLimit',    RowLimit || 10);
        fd.append('Filter',      JSON.stringify(Filter || {}));
    };

    $(document).on('click', '.purchReceivePayment', function () {
        rpOpenModal({
            transUID  : $(this).data('uid'),
            docNum    : $(this).data('num')     || '',
            docDate   : $(this).data('date')    || '',
            partyName : $(this).data('party')   || '',
            vendorUID : parseInt($(this).data('vendor-uid'), 10) || 0,
            total     : parseFloat($(this).data('total'))   || 0,
            paid      : parseFloat($(this).data('paid'))    || 0,
            pending   : parseFloat($(this).data('pending')) || 0,
            submitUrl : '/purchases/recordPurchasePayment',
        });
    });

// ── Debit Notes Tab ──────────────────────────────────────────────
(function () {
    'use strict';

    var _dnPageNo  = 1;
    var _dnLimit   = 10;
    var _dnStatus  = 'All';
    var _dnSearch  = '';
    var _dnActive  = false;

    /**
     * @param {number} page
     * @returns {void}
     */
    function loadDebitNotes(page) {
        _dnPageNo = page || 1;
        ajaxLoading(0);
        $('#purchDnTableBody').html('<tr><td colspan="6" class="text-center text-muted py-3"><span class="spinner-border spinner-border-sm me-2"></span>Loading…</td></tr>');
        $.ajax({
            url    : '/purchases/getDebitNotesList',
            method : 'POST',
            data   : { PageNo: _dnPageNo, RowLimit: _dnLimit, Status: _dnStatus, Search: _dnSearch },
            beforeSend: function (xhr) { xhr.setRequestHeader(CsrfName, CsrfToken); },
            success: function (resp) {
                if (resp.Error) {
                    $('#purchDnTableBody').html('<tr><td colspan="6" class="text-center text-danger py-3">' + resp.Message + '</td></tr>');
                } else {
                    $('#purchDnTableBody').html(resp.RecordHtmlData);
                    $('#purchDnPagination').html(resp.Pagination || '');
                    var dnCount = resp.TotalCount || 0;
                    if (dnCount > 0) { $('#purchDnTabCount').text(dnCount).removeClass('d-none'); } else { $('#purchDnTabCount').text('').addClass('d-none'); }
                    [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]')).map(function (el) {
                        return new bootstrap.Tooltip(el, { container: 'body' });
                    });
                }
            },
            error: function () {
                $('#purchDnTableBody').html('<tr><td colspan="6" class="text-center text-danger py-3">Failed to load. Try again.</td></tr>');
            }
        });
    }

    // Tab click — switch between main list and DN section
    $('#purchDnTab').on('click', function () {
        _dnActive = true;
        $('#purchTableWrap, #purchPagination, #purchSelectAllBanner').addClass('d-none');
        $('#purchDnSection').removeClass('d-none');
        $('.purch-status-tab').removeClass('active');
        $('.purch-status-tab .trans-tab-count').addClass('d-none');
        $(this).addClass('active');
        loadDebitNotes(1);
    });

    // When a regular status tab is clicked, restore main list
    $(document).on('click', '.purch-status-tab', function () {
        if (_dnActive) {
            _dnActive = false;
            $('#purchDnSection').addClass('d-none');
            $('#purchTableWrap, #purchPagination').removeClass('d-none');
            $('#purchDnTab').removeClass('active');
            $('#purchDnTabCount').text('').addClass('d-none');
        }
    });

    // DN status filter buttons
    $(document).on('click', '.dn-status-btn', function () {
        $('.dn-status-btn').removeClass('active');
        $(this).addClass('active');
        _dnStatus = $(this).data('status');
        loadDebitNotes(1);
    });

    // DN search
    var _dnSearchTimer;
    $('#dnSearch').on('input', function () {
        clearTimeout(_dnSearchTimer);
        _dnSearch = $.trim($(this).val());
        _dnSearchTimer = setTimeout(function () { loadDebitNotes(1); }, 400);
    });

    // DN pagination click
    $(document).on('click', '#purchDnPagination .page-link', function (e) {
        e.preventDefault();
        var p = parseInt($(this).data('page'), 10);
        if (p > 0) loadDebitNotes(p);
    });

    // Refund button
    $(document).on('click', '.dnRefundBtn', function () {
        var uid = $(this).data('uid');
        var num = $(this).data('num') || '';
        Swal.fire({
            title: 'Mark as Refunded?',
            html : 'Mark debit note from <strong>' + num + '</strong> as refunded (vendor paid back in cash)?',
            icon : 'question',
            showCancelButton : true,
            confirmButtonText: 'Yes, Refunded',
            confirmButtonColor: '#0dcaf0',
        }).then(function (r) {
            if (!r.isConfirmed) return;
            ajaxLoading(1);
            $.ajax({
                url    : '/purchases/refundDebitNote',
                method : 'POST',
                data   : { DebitNoteUID: uid },
                beforeSend: function (xhr) { xhr.setRequestHeader(CsrfName, CsrfToken); },
                success: function (resp) {
                    ajaxLoading(0);
                    showToastNotification(resp.Message, resp.Error ? 'error' : 'success');
                    if (!resp.Error) loadDebitNotes(_dnPageNo);
                },
                error: function () { ajaxLoading(0); showToastNotification('Request failed.', 'error'); }
            });
        });
    });

    // Delete button
    $(document).on('click', '.dnDeleteBtn', function () {
        var uid = $(this).data('uid');
        var num = $(this).data('num') || '';
        Swal.fire({
            title: 'Delete Debit Note?',
            html : 'Delete debit note from <strong>' + num + '</strong>? This cannot be undone.',
            icon : 'warning',
            showCancelButton : true,
            confirmButtonText: 'Yes, Delete',
            confirmButtonColor: '#dc3545',
        }).then(function (r) {
            if (!r.isConfirmed) return;
            ajaxLoading(1);
            $.ajax({
                url    : '/purchases/deleteDebitNote',
                method : 'POST',
                data   : { DebitNoteUID: uid },
                beforeSend: function (xhr) { xhr.setRequestHeader(CsrfName, CsrfToken); },
                success: function (resp) {
                    ajaxLoading(0);
                    showToastNotification(resp.Message, resp.Error ? 'error' : 'success');
                    if (!resp.Error) loadDebitNotes(_dnPageNo);
                },
                error: function () { ajaxLoading(0); showToastNotification('Request failed.', 'error'); }
            });
        });
    });

}());

    function updateSummaryStats(stats) {
        if (!document.querySelector('.apex-stats-strip')) return;
        var cur = (typeof currencySymbol !== 'undefined' && currencySymbol) ? currencySymbol : '₹';
        var dec = (typeof decimalPlaces !== 'undefined') ? decimalPlaces : 2;
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
