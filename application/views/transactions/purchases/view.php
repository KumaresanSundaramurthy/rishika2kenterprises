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

                if ($JwtData->TransSettings->ShowTransactionStats ?? 1):
                $stats       = $SummaryStats ?? [];
                $cur         = htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹');
                $dec         = $JwtData->GenSettings->DecimalPoints ?? 2;

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
                                <span class="apex-stat-amount"><?php echo $cur . ' ' . number_format((float)$stat['amount'], $dec); ?></span>
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
<script src="/js/transactions/viewmodal.js"></script>
<script src="/js/transactions/a4_print.js"></script>
<script src="/js/transactions/filter_bar.js"></script>
<script src="/js/transactions/col_filter.js"></script>
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

    // ── Delete ──────────────────────────────────────────────────
    function _actionPostData(extra) {
        Filter.Status = $('.purch-status-tab.active').data('status') || 'All';
        return $.extend({ RowLimit: RowLimit, PageNo: PageNo, Filter: Filter, [CsrfName]: CsrfToken }, extra);
    }

    function _renderListResponse(resp) {
        $(ModuleTable + ' tbody').html(resp.RecordHtmlData);
        $(ModulePag).html(resp.Pagination);
        var count = resp.TotalCount || 0;
        var $badge = $('.purch-status-tab.active .purch-tab-count');
        if (count > 0) { $badge.text(count).removeClass('d-none'); } else { $badge.text('').addClass('d-none'); }
        initTooltips();
    }

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
                data  : _actionPostData({ TransUID: uid }),
                success: function (resp) {
                    if (resp.Error) { Swal.fire({ icon: 'error', text: resp.Message }); return; }
                    showToastNotification(resp.Message || 'Deleted.', 'success');
                    if (PageNo > 1 && (resp.TotalCount || 0) <= (PageNo - 1) * RowLimit) {
                        PageNo--;
                        getPurchasesDetails();
                    } else {
                        _renderListResponse(resp);
                    }
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
                    if (resp.Error) { Swal.fire({ icon: 'error', text: resp.Message }); return; }
                    var _msg = resp.Message || 'Cancelled.';
                    getPurchasesDetails(undefined, undefined, undefined, function () {
                        showToastNotification(_msg, 'success');
                    });
                }
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
    var _fpInstance = null;
    var _currency   = '<?php echo htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹'); ?>';
    var _vDec       = <?php echo (int)($JwtData->GenSettings->DecimalPoints ?? 2); ?>;

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
                altFormat    : _transFormDateFormat,
                maxDate      : 'today',
                disableMobile: true,
                defaultDate  : 'today',
                static       : true,
                position     : 'below left',
            });
        } else {
            _fpInstance.setDate(new Date(), false);
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
        $('#rpTotalCard').text(_currency + ' ' + total.toFixed(_vDec));
        $('#rpPaidCard').text(_currency + ' ' + paid.toFixed(_vDec));
        $('#rpBalanceCard').text(_currency + ' ' + pending.toFixed(_vDec));
        $('#rpAmount').val(pending.toFixed(_vDec)).attr('max', pending);
        $('#rpCurrencySymbol').text(_currency);
        $('#rpReferenceNo').val('');
        $('#rpNotes').val('');
        $('#rpBankAccount').val('');

        if (typeof _attachResetState === 'function') { _attachResetState('Payment'); }
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
        (_attachState && _attachState['Payment'] ? (_attachState['Payment'].newFiles || []) : []).forEach(function (f) { fd.append('PaymentFiles[]', f, f.name); });

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
