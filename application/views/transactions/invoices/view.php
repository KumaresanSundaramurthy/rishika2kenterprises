<?php defined('BASEPATH') or exit('No direct script access allowed');

$this->load->view('common/transactions/header'); ?>

<div class="layout-wrapper layout-horizontal layout-content-navbar">
    <div class="layout-container">

        <?php $this->load->view('common/menu_view'); ?>

        <div class="layout-page">

            <div class="content-wrapper apex-content">
                <?php $this->load->view('common/apex/page_header', [
                    'pageTitle'       => $PageTitle       ?? 'Sales Invoices',
                    'pageDescription' => $PageDescription ?? '',
                ]); ?>

            <?php

                // ── URL tab/search state ──────────────────────────
                $initTab    = $InitTab    ?? 'All';
                $initSearch = $InitSearch ?? '';
                $tabFilterMap = [
                    'All'         => ['invPayStatusFilter', 'invPayModeFilter', 'invCreatedByFilter', 'invPartyFilterTrigger'],
                    'InvPending'  => ['invPayModeFilter', 'invCreatedByFilter', 'invPartyFilterTrigger'],
                    'Paid'        => ['invPayModeFilter', 'invCreatedByFilter', 'invPartyFilterTrigger'],
                    'Cancelled'   => ['invPayStatusFilter', 'invPayModeFilter', 'invCreatedByFilter', 'invPartyFilterTrigger'],
                    'Draft'       => ['invCreatedByFilter', 'invPartyFilterTrigger'],
                    'CreditNotes' => ['invPartyFilterTrigger', 'invCnStatusWrap'],
                ];
                $visibleFilters = $tabFilterMap[$initTab] ?? $tabFilterMap['All'];

                if ($JwtData->TransSettings->ShowTransactionStats ?? 1):
                // ── Build summary numbers ─────────────────────────
                $stats       = $SummaryStats ?? [];
                $cur         = htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹');
                $dec         = $JwtData->GenSettings->DecimalPoints ?? 2;

                $activeInvStatuses = ['Issued', 'Partial', 'Paid'];
                $cntAll     = array_sum(array_map(fn($s) => $stats[$s]['count']  ?? 0, $activeInvStatuses));
                $amtAll     = array_sum(array_map(fn($s) => $stats[$s]['amount'] ?? 0, $activeInvStatuses));
                $cntPending = ($stats['Issued']['count']   ?? 0) + ($stats['Partial']['count'] ?? 0);
                $amtPending = ($stats['Issued']['amount']  ?? 0) + ($stats['Partial']['amount'] ?? 0);
                $cntPaid    = $stats['Paid']['count']  ?? 0;
                $amtPaid    = $stats['Paid']['amount'] ?? 0;
                $cntDraft   = $stats['Draft']['count'] ?? 0;

                $statsItems = [
                    ['label' => 'All Invoices', 'status' => 'All',        'icon' => 'bx-receipt',      'iconBg' => '#fef3c7', 'iconColor' => '#f59e0b', 'count' => $cntAll,     'amount' => $amtAll],
                    ['label' => 'Pending',      'status' => 'InvPending', 'icon' => 'bx-time-five',    'iconBg' => '#fff7ed', 'iconColor' => '#f97316', 'count' => $cntPending, 'amount' => $amtPending],
                    ['label' => 'Paid',         'status' => 'Paid',       'icon' => 'bx-check-circle', 'iconBg' => '#dcfce7', 'iconColor' => '#16a34a', 'count' => $cntPaid,    'amount' => $amtPaid],
                    ['label' => 'Drafts',       'status' => 'Draft',      'icon' => 'bx-edit',          'iconBg' => '#f1f5f9', 'iconColor' => '#64748b', 'count' => $cntDraft,   'amount' => 0],
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

                    <!-- ── Main Card ───────────────────────────────────────── -->
                    <div class="card">

                        <!-- ── Filter Row ─────────────────────────────────── -->
                        <div class="apex-filter-row">
                            <!-- Fixed left: search -->
                            <div class="r2k-search-wrap<?php echo $initSearch ? ' is-expanded r2k-search-active' : ''; ?>">
                                <i class="bx bx-search r2k-si"></i>
                                <input type="text" id="searchTransactionData" placeholder="<?php echo $initTab === 'CreditNotes' ? 'CN # or customer...' : 'Invoice # or customer...'; ?>" value="<?php echo htmlspecialchars($initSearch, ENT_QUOTES); ?>">
                                <i class="bx bx-x r2k-clear<?php echo $initSearch ? '' : ' d-none'; ?>"></i>
                            </div>

                            <a href="javascript:void(0);" id="invPayStatusFilter" class="apex-filter-btn<?php echo in_array('invPayStatusFilter', $visibleFilters) ? '' : ' d-none'; ?>" title="Filter by Payment Status"><i class="bx bx-wallet-alt me-1"></i>Pay Status</a>
                            <a href="javascript:void(0);" id="invPayModeFilter" class="apex-filter-btn<?php echo in_array('invPayModeFilter', $visibleFilters) ? '' : ' d-none'; ?>" title="Filter by Payment Mode"><i class="bx bx-credit-card me-1"></i>Pay Mode</a>
                            <?php if (count($OrgUsers ?? []) > 1): ?>
                            <a href="javascript:void(0);" id="invCreatedByFilter" class="apex-filter-btn<?php echo in_array('invCreatedByFilter', $visibleFilters) ? '' : ' d-none'; ?>" title="Filter by User"><i class="bx bx-user me-1"></i>Updated By</a>
                            <?php endif; ?>
                            <a href="javascript:void(0);" id="invPartyFilterTrigger" class="apex-filter-btn<?php echo in_array('invPartyFilterTrigger', $visibleFilters) ? '' : ' d-none'; ?>" title="Filter by Customer"><i class="bx bx-store me-1"></i>Customer</a>
                            <div class="<?php echo in_array('invCnStatusWrap', $visibleFilters) ? '' : 'd-none'; ?>" id="invCnStatusWrap">
                                <a href="javascript:void(0);" class="apex-filter-btn" id="invCnStatusFilter"><i class="bx bx-transfer-alt me-1"></i>CN Status</a>
                            </div>
                            <?php $this->load->view('common/transactions/date_filter_btn'); ?>
                            <div class="apex-filter-spacer"></div>

                            <!-- Fixed right: actions -->
                            <a href="javascript:void(0);" class="apex-filter-btn pageRefresh" data-bs-toggle="tooltip" data-bs-placement="bottom" title="<?php echo t('page_refresh', 'Page Refresh'); ?>"><i class="bx bx-refresh"></i></a>
                            <div class="btn-group d-none" id="ActionsDD-Div">
                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="bx bx-slider-alt"></i></button>
                                <ul class="dropdown-menu dropdown-menu-end r2k-export-menu r2k-actions-menu">
                                    <li class="d-none" id="DeleteOption"><a class="dropdown-item text-danger" href="javascript:void(0);" id="btnDelete"><i class="bx bx-trash me-2"></i><?php echo t('delete', 'Delete'); ?></a></li>
                                </ul>
                            </div>
                            <?php $this->load->view('common/partials/export_btn'); ?>
                            <a href="/invoices/create" class="btn btn-sm btn-primary flex-shrink-0" data-bs-toggle="tooltip" data-bs-placement="bottom" title="<?php echo t('create_invoice', 'Create Invoice'); ?>"><i class="bx bx-plus me-1"></i><?php echo t('lbl_new', 'New'); ?></a>
                        </div>

                        <!-- ── Tabs Row ──────────────────────────────────── -->
                        <div class="apex-tabs-row">
                            <ul class="nav trans-status-tabs" id="invStatusTabs" role="tablist" data-trans-path="/invoices">
                                <li class="nav-item"><a class="nav-link <?php echo $initTab === 'All' ? 'active' : ''; ?> inv-status-tab" data-status="All" data-url-tab="all" href="javascript:void(0);">All <span class="trans-tab-count ms-1<?php echo ($initTab === 'All' && (int)$ModAllCount > 0) ? '' : ' d-none'; ?>"><?php echo ($initTab === 'All' && (int)$ModAllCount > 0) ? $ModAllCount : ''; ?></span></a></li>
                                <li class="nav-item"><a class="nav-link <?php echo $initTab === 'InvPending' ? 'active' : ''; ?> inv-status-tab" data-status="InvPending" data-url-tab="pending" href="javascript:void(0);">Pending <span class="trans-tab-count ms-1<?php echo ($initTab === 'InvPending' && (int)$ModAllCount > 0) ? '' : ' d-none'; ?>"><?php echo ($initTab === 'InvPending' && (int)$ModAllCount > 0) ? $ModAllCount : ''; ?></span></a></li>
                                <li class="nav-item"><a class="nav-link <?php echo $initTab === 'Paid' ? 'active' : ''; ?> inv-status-tab" data-status="Paid" data-url-tab="paid" href="javascript:void(0);">Paid <span class="trans-tab-count ms-1<?php echo ($initTab === 'Paid' && (int)$ModAllCount > 0) ? '' : ' d-none'; ?>"><?php echo ($initTab === 'Paid' && (int)$ModAllCount > 0) ? $ModAllCount : ''; ?></span></a></li>
                                <li class="nav-item"><a class="nav-link <?php echo $initTab === 'Cancelled' ? 'active' : ''; ?> inv-status-tab" data-status="Cancelled" data-url-tab="cancelled" href="javascript:void(0);">Cancelled <span class="trans-tab-count ms-1<?php echo ($initTab === 'Cancelled' && (int)$ModAllCount > 0) ? '' : ' d-none'; ?>"><?php echo ($initTab === 'Cancelled' && (int)$ModAllCount > 0) ? $ModAllCount : ''; ?></span></a></li>
                                <li class="nav-item"><a class="nav-link <?php echo $initTab === 'Draft' ? 'active' : ''; ?> inv-status-tab" data-status="Draft" data-url-tab="draft" href="javascript:void(0);">Drafts <span class="trans-tab-count ms-1<?php echo ($initTab === 'Draft' && (int)$ModAllCount > 0) ? '' : ' d-none'; ?>"><?php echo ($initTab === 'Draft' && (int)$ModAllCount > 0) ? $ModAllCount : ''; ?></span></a></li>
                                <li class="nav-item"><a class="nav-link <?php echo $initTab === 'CreditNotes' ? 'active' : ''; ?> inv-status-tab inv-cn-tab" data-status="CreditNotes" data-url-tab="creditnotes" href="javascript:void(0);">Credit Notes <span class="trans-tab-count ms-1 d-none"></span></a></li>
                            </ul>
                            <?php $this->load->view('common/transactions/filter_notice'); ?>
                        </div>

                        <!-- Select-all banner -->
                        <div id="invSelectAllBanner" class="r2k-select-all-banner d-none">
                            <span id="invSelectAllMsg"></span>
                            <a href="javascript:void(0);" id="invSelectAllLink" class="ms-2"></a>
                            <a href="javascript:void(0);" id="invSelectAllClear" class="ms-2 d-none">Clear selection</a>
                        </div>

                        <!-- Invoice Table (hidden when Credit Notes tab is active) -->
                        <div id="invTableSection"<?php echo $initTab === 'CreditNotes' ? ' style="display:none;"' : ''; ?>>
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
                                            <th class="col-sortable cursor-pointer user-select-none" data-sort="Number" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Click for ascending order">
                                                # Bill <i class="bx bx-sort-alt-2 ms-1 sort-icon" data-col="Number"></i>
                                            </th>
                                            <th class="col-sortable cursor-pointer user-select-none" data-sort="Amount" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Click for ascending order">
                                                Amount <i class="bx bx-sort-alt-2 ms-1 sort-icon" data-col="Amount"></i>
                                            </th>
                                            <th>Payment Status</th>
                                            <th>Payment Mode</th>
                                            <th>Customer</th>
                                            <th>Last Updated</th>
                                            <th class="text-center" style="width:50px">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="r2k-tbody table-border-bottom-0">
                                        <?php echo $ModRowData; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination -->
                            <div class="row mx-0 px-3 mt-1 justify-content-between align-items-center invPagination apex-pag-sticky" id="invPagination">
                                <?php echo $ModPagination ?: ''; ?>
                            </div>
                        </div>

                        <!-- Credit Notes Table (shown only when Credit Notes tab is active) -->
                        <div id="invCNSection"<?php echo $initTab === 'CreditNotes' ? '' : ' style="display:none;"'; ?>>
                            <div class="table-responsive">
                                <table class="table trans-table table-hover mb-0" id="cnTable">
                                    <thead class="r2k-thead">
                                        <tr>
                                            <th style="width:44px">S.No</th>
                                            <th>CN Number</th>
                                            <th>Customer</th>
                                            <th>Source SR</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                            <th>Created On</th>
                                        </tr>
                                    </thead>
                                    <tbody id="cnTableBody" class="r2k-tbody table-border-bottom-0">
                                        <tr><td colspan="7" class="text-center py-4 text-muted">Loading...</td></tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="row mx-0 px-3 mt-1 justify-content-between align-items-center apex-pag-sticky" id="cnPagination"></div>
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
                $this->load->view('common/transactions/payment_modal');
            ?>

            <?php $this->load->view('common/modals/send_communication'); ?>

            <?php $this->load->view('common/footer_desc'); ?>
            
        </div>
    </div>
</div>

<?php $this->load->view('common/filter_panels/col_filter_box', [
    'ColFilterConfig' => [
        'id'         => 'invPayStatusFilterBox',
        'triggerId'  => 'invPayStatusFilter',
        'title'      => 'Payment Status',
        'icon'       => 'bx-wallet-alt',
        'filterKey'  => 'PaymentStatus',
        'checkClass' => 'inv-pay-status-chk',
        'items'      => [
            ['value' => 'Pending',        'label' => 'Pending',        'icon' => 'bx-time-five',    'color' => '#e65100'],
            ['value' => 'Partially Paid', 'label' => 'Partially Paid', 'icon' => 'bx-adjust',       'color' => '#0d47a1'],
            ['value' => 'Paid',           'label' => 'Paid',           'icon' => 'bx-check-circle', 'color' => '#2e7d32'],
        ],
    ],
]); ?>

<?php $this->load->view('common/filter_panels/col_filter_box', [
    'ColFilterConfig' => [
        'id'         => 'invPayModeFilterBox',
        'triggerId'  => 'invPayModeFilter',
        'title'      => 'Payment Mode',
        'icon'       => 'bx-credit-card',
        'filterKey'  => 'PaymentMode',
        'checkClass' => 'inv-pay-mode-chk',
        'items'      => [],
    ],
]); ?>

<?php if (count($OrgUsers ?? []) > 1): ?>
<?php $this->load->view('common/filter_panels/col_user_filter_box', [
    'ColUserFilterConfig' => [
        'id'         => 'invCreatedByFilterBox',
        'triggerId'  => 'invCreatedByFilter',
        'checkClass' => 'inv-user-chk',
        'OrgUsers'   => $OrgUsers ?? [],
    ],
]); ?>
<?php endif; ?>

<?php $this->load->view('common/filter_panels/col_party_filter_box', [
    'ColPartyFilterConfig' => [
        'id'    => 'invPartyFilterBox',
        'title' => 'Filter by Customer',
        'icon'  => 'bx-user',
    ],
]); ?>

<!-- CN Status custom filter panel (same Pay Status box style, single-select) -->
<div id="invCnStatusBox" class="card mp-filterbox trans-col-filterbox"
     style="z-index:9999;display:none;position:fixed;min-width:170px;">
    <div class="catg-filter-header">
        <span class="catg-filter-title"><i class="bx bx-transfer-alt me-1"></i>CN Status</span>
        <div class="d-flex align-items-center gap-2">
            <span class="badge">5</span>
            <button type="button" class="catg-filter-close-btn tcf-close-btn" title="Close">&times;</button>
        </div>
    </div>
    <div class="catg-list" style="max-height:none;">
        <div class="catg-list-item cn-opt-active cn-status-panel-opt" data-val="All">All</div>
        <div class="catg-list-item cn-status-panel-opt" data-val="Pending"><span class="badge bg-label-warning me-1">Pending</span></div>
        <div class="catg-list-item cn-status-panel-opt" data-val="Applied"><span class="badge bg-label-success me-1">Applied</span></div>
        <div class="catg-list-item cn-status-panel-opt" data-val="Refunded"><span class="badge bg-label-secondary me-1">Refunded</span></div>
        <div class="catg-list-item cn-status-panel-opt" data-val="Cancelled"><span class="badge bg-label-danger me-1">Cancelled</span></div>
    </div>
</div>

<?php $this->load->view('common/transactions/footer'); ?>

<script src="/js/common/communication.js"></script>
<script src="/js/common/party_filter.js"></script>
<script src="/js/transactions/attachments.js"></script>
<script src="/js/transactions/viewmodal.js"></script>
<script src="/js/transactions/a4_print.js"></script>
<script src="/js/transactions/filter_bar.js"></script>
<script src="/js/transactions/col_filter.js"></script>
<script src="/js/transactions/invoices.js"></script>

<script>

const ModuleId     = 103;
const ModuleTable  = '#invTable';
const ModulePag    = '.invPagination';
const ModuleHeader = '.invHeaderCheck';
const ModuleRow    = '.invCheck';

var _invInitTab    = <?php echo json_encode($initTab    ?? 'All'); ?>;
var _invInitSearch = <?php echo json_encode($initSearch ?? ''); ?>;

// ── Tab filter visibility map ────────────────────────────────────────
const _invTabFilterMap = <?= json_encode($tabFilterMap); ?>;
const _allInvFilterEls = <?= json_encode(array_values(array_unique(array_merge(...array_values($tabFilterMap))))); ?>;
var _initPage          = <?php echo (int)($InitPage ?? 1); ?>;

$(function () {
    'use strict';

    _checkPendingToast('_invPendingToast');
    PageNo = _initPage;
    Filter['Status'] = _invInitTab === 'CreditNotes' ? 'All' : _invInitTab;
    if (_invInitSearch) { Filter.Name = _invInitSearch; }
    initExport({ moduleUID: 103, getFilters: function () { return Filter; } });

    // ── Filter bar (mode / customer / user pills) ────────────────────────
    var tfb = (typeof TransFilterBar !== 'undefined') ? new TransFilterBar({ onChange: function () { PageNo = 1; getInvoicesDetails(); } }) : null;

    // ── Column-level Payment Status filter ──────────────────────────────
    var payStatusFilter = new TransColFilter({
        boxId       : 'invPayStatusFilterBox',
        triggerId   : 'invPayStatusFilter',
        filterKey   : 'PaymentStatus',
        activeClass : 'has-filter',
        onApply     : function () { PageNo = 1; getInvoicesDetails(); }
    });

    var payModeFilter = new TransColFilter({
        boxId       : 'invPayModeFilterBox',
        triggerId   : 'invPayModeFilter',
        filterKey   : 'PaymentMode',
        activeClass : 'has-filter',
        onApply     : function () { PageNo = 1; getInvoicesDetails(); }
    });

    // Lazy-load payment modes on first click (Upstash → AJAX)
    var _payModeFilterPromise = null;
    $(document).on('click', '#invPayModeFilter', function () {
        if (_payModeFilterPromise) return;
        _payModeFilterPromise = loadCachedFilterData('payment-types', '/payments/getPaymentTypes').then(function (types) {
            payModeFilter.setItems(types.map(function (t) { return { value: t.Name, label: t.Name }; }));
        }).catch(function () { _payModeFilterPromise = null; });
    });

    var invCreatedByFilter = (document.getElementById('invCreatedByFilterBox'))
        ? new TransColFilter({
            boxId       : 'invCreatedByFilterBox',
            triggerId   : 'invCreatedByFilter',
            filterKey   : 'UpdatedByUIDs',
            activeClass : 'has-filter',
            onApply     : function () { PageNo = 1; getInvoicesDetails(); }
        })
        : null;

    var invPartyFilter = new TransPartyColFilter({
        boxId     : 'invPartyFilterBox',
        triggerId : 'invPartyFilterTrigger',
        partyType : 'customer',
        filterKey : 'PartyUID',
        onApply   : function () { PageNo = 1; getInvoicesDetails(); }
    });

    // Wrap getInvoicesDetails so every call automatically merges all filter states.
    var _origGetInvoicesDetails = getInvoicesDetails;
    getInvoicesDetails = function (pageNo, rowLimit, filter, afterLoad) {
        var f = $.extend({}, filter || Filter,
            tfb                ? tfb.getState()                : {},
            payStatusFilter    ? payStatusFilter.getState()    : {},
            payModeFilter      ? payModeFilter.getState()      : {},
            invCreatedByFilter ? invCreatedByFilter.getState() : {},
            invPartyFilter     ? invPartyFilter.getState()     : {}
        );
        _origGetInvoicesDetails(pageNo, rowLimit, f, afterLoad);
    };

    /**
     * Clears search text and resets all column/party/filterbar instances on tab switch.
     * Date filter is intentionally left untouched per user requirement.
     * @returns {void}
     */
    function _resetInvFilters() {
        var $wrap = $('#searchTransactionData').closest('.r2k-search-wrap');
        $('#searchTransactionData').val('');
        $wrap.find('.r2k-clear').addClass('d-none');
        $wrap.removeClass('is-expanded r2k-search-active');
        Filter.Name = '';
        _cnSearch   = '';

        if (payStatusFilter)    { payStatusFilter.reset(); }
        if (payModeFilter)      { payModeFilter.reset(); }
        if (invCreatedByFilter) { invCreatedByFilter.reset(); }
        if (invPartyFilter)     { invPartyFilter.reset(); }
        if (tfb)                { tfb.reset(); }

        _cnStatus = 'All';
        $('.cn-status-panel-opt').removeClass('cn-opt-active');
        $('.cn-status-panel-opt[data-val="All"]').addClass('cn-opt-active');
        $('#invCnStatusFilter').html('<i class="bx bx-transfer-alt me-1"></i>CN Status').removeClass('has-filter');
        $('#invCnStatusBox').hide();
        $('.trans-col-filterbox, .tpcf-box').hide();
    }

    // ── Create / Edit — inject returnTab + returnPage ──────────────────
    $(document).on('click', 'a[href="/invoices/create"]', function (e) {
        e.preventDefault();
        var params = new URLSearchParams();
        params.set('returnTab', Filter.Status || 'All');
        if (PageNo > 1) params.set('returnPage', PageNo);
        window.location.href = '/invoices/create?' + params.toString();
    });
    $(document).on('click', 'a[href*="/invoices/"][href*="/edit"]', function (e) {
        e.preventDefault();
        var params = new URLSearchParams();
        params.set('returnTab', Filter.Status || 'All');
        if (PageNo > 1) params.set('returnPage', PageNo);
        window.location.href = $(this).attr('href') + '?' + params.toString();
    });

    _applyTabFilters(_invInitTab || 'All', _invTabFilterMap, _allInvFilterEls);
    if (_invInitTab === 'CreditNotes') {
        _cnPageNo = 1;
        if (_invInitSearch) { _cnSearch = _invInitSearch; }
        loadCreditNotes(1);
    }

    // ── Stat card click → filter by status ─────────────────
    $(document).on('click', '[data-stat-filter]', function () {
        var status = $(this).data('stat-filter') || 'All';
        $('.apex-stat-item').removeClass('active');
        $(this).addClass('active');
        $('.inv-status-tab').removeClass('active');
        $('.inv-status-tab[data-status="' + status + '"]').addClass('active');
        _resetInvFilters();
        _applyTabFilters(status, _invTabFilterMap, _allInvFilterEls);
        _updateTransTabUrl(status, '');
        $('#invCNSection').hide();
        $('#invTableSection').show();
        $('#searchTransactionData').attr('placeholder', 'Invoice # or customer...');
        Filter.Status = status;
        PageNo = 1;
        getInvoicesDetails();
    });

    // ── Status tabs ─────────────────────────────────────────
    $(document).on('click', '.inv-status-tab', function (e) {
        e.preventDefault();
        SelectedUIDs = []; _invClearSelectAll(); MultipleDeleteOption();
        var status = $(this).data('status') || 'All';
        $('.inv-status-tab').removeClass('active');
        $(this).addClass('active');
        $('.apex-stat-item').removeClass('active');
        $('.apex-stat-item[data-stat-filter="' + status + '"]').addClass('active');
        _resetInvFilters();
        _applyTabFilters(status, _invTabFilterMap, _allInvFilterEls);
        _updateTransTabUrl(status, '');
        if (status === 'CreditNotes') {
            $('.trans-tab-count').addClass('d-none');
            $('#invTableSection').hide();
            $('#invCNSection').show();
            $('#searchTransactionData').attr('placeholder', 'CN # or customer...');
            _cnPageNo = 1;
            loadCreditNotes(1);
        } else {
            $('#invCNSection').hide();
            $('#invTableSection').show();
            $('#searchTransactionData').attr('placeholder', 'Invoice # or customer...');
            Filter.Status = status;
            PageNo = 1;
            getInvoicesDetails();
        }
    });

    $(document).on('click', '.pageRefresh', function (e) {
        e.preventDefault();
        if ($('.inv-cn-tab').hasClass('active')) {
            _cnPageNo = 1;
            loadCreditNotes(1);
        } else {
            PageNo = 1;
            getInvoicesDetails();
        }
    });

    // Search — URL updates immediately on every keystroke; data load is debounced
    $('#searchTransactionData').on('input', function () {
        var curTab = $('.inv-status-tab.active').data('status') || 'All';
        _updateTransTabUrl(curTab, $.trim($(this).val()));
    });

    $('#searchTransactionData').on('input', debounce(function () {
        var val = $.trim($(this).val());
        if ($('.inv-cn-tab').hasClass('active')) {
            _cnSearch = val;
            _cnPageNo = 1;
            loadCreditNotes(1);
        } else {
            Filter.Name = val;
            PageNo = 1;
            getInvoicesDetails();
        }
    }, 1250));

    // Date filter
    $(document).on('r2k:datechange', function (e, dr) {
        Filter.DateFrom = dr.from;
        Filter.DateTo   = dr.to;
        PageNo = 1;
        getInvoicesDetails();
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
        getInvoicesDetails();
    });

    // Pagination
    $(document).on('click', '.invPagination .page-link', function (e) {
        e.preventDefault();
        var match = ($(this).attr('href') || '').match(/\/(\d+)$/);
        if (match) { PageNo = parseInt(match[1]); _invClearSelectAll(); getInvoicesDetails(); }
    });

    // ── Delete ──────────────────────────────────────────────
    function _actionPostData(extra) {
        Filter.Status = $('.inv-status-tab.active').data('status') || 'All';
        return $.extend({ RowLimit: RowLimit, PageNo: PageNo, Filter: Filter, [CsrfName]: CsrfToken }, extra);
    }

    function _renderListResponse(resp) {
        $(ModuleTable + ' tbody').html(resp.RecordHtmlData);
        $(ModulePag).html(resp.Pagination);
        var count = resp.TotalCount || 0;
        var $badge = $('.inv-status-tab.active .trans-tab-count');
        if (count > 0) { $badge.text(count).removeClass('d-none'); } else { $badge.text('').addClass('d-none'); }
        initTooltips();
    }

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
                data  : _actionPostData({ TransUID: uid }),
                success: function (resp) {
                    if (resp.Error) { Swal.fire({ icon:'error', text:resp.Message }); return; }
                    showToastNotification(resp.Message || 'Deleted.', 'success');
                    if (PageNo > 1 && (resp.TotalCount || 0) <= (PageNo - 1) * RowLimit) {
                        PageNo--;
                        getInvoicesDetails();
                    } else {
                        _renderListResponse(resp);
                    }
                }
            });
        });
    });

    // ── Cancel Invoice ──────────────────────────────────────────────────────
    var _invCancelSetting = '<?php echo addslashes($JwtData->TransSettings->InvoiceCancelAction ?? 'ask'); ?>';

    var _cancelActionMeta = {
        credit_note : {
            label: 'Convert to Credit Note',
            desc : 'The paid amount will be converted into a <strong>Credit Note</strong> for this customer. It can be applied to a future invoice or refunded later.'
        },
        refund : {
            label: 'Mark as Refund',
            desc : 'The paid amount will be marked as a <strong>Refund</strong> due to this customer. You must physically return the money (cash / bank transfer).'
        },
        cancel_only : {
            label: 'Cancel Only',
            desc : 'The invoice will be cancelled. The paid amount remains as a <strong>credit</strong> in the customer\'s balance. Handle payment adjustments manually.'
        }
    };
    
    function _buildPaymentActionHtml(defaultAction) {
        var isAsk = (defaultAction === 'ask');
        var html  = '';

        if (isAsk) {
            // Ask mode — show dropdown directly
            html += '<div class="mt-3 text-start">';
            html += '<label class="form-label fw-semibold small mb-1">Select action for the received payment:</label>';
            html += '<select class="form-select form-select-sm" id="swalCancelAction">';
            html += '<option value="">— Choose an action —</option>';
            $.each(_cancelActionMeta, function (val, m) {
                html += '<option value="' + val + '">' + m.label + '</option>';
            });
            html += '</select>';
            html += '<div id="swalCancelDesc" class="text-muted small mt-2 p-2 rounded" style="background:#f8f9fa;min-height:36px;"></div>';
            html += '</div>';
        } else {
            // Preset mode — show description + "Click here to change"
            var meta = _cancelActionMeta[defaultAction] || {};
            html += '<div class="mt-3 text-start" id="swalPresetWrap">';
            html += '<div class="p-2 rounded small" style="background:#f0f4ff;border-left:3px solid #696cff;">';
            html += meta.desc || '';
            html += '</div>';
            html += '<a href="javascript:void(0)" class="small text-primary mt-2 d-inline-block" id="swalChangeAction">&#9998; Click here to change</a>';
            html += '</div>';
            // Hidden dropdown (shown on "Click here to change")
            html += '<div class="mt-2 text-start d-none" id="swalChangeWrap">';
            html += '<label class="form-label fw-semibold small mb-1">Select a different action:</label>';
            html += '<select class="form-select form-select-sm" id="swalCancelAction">';
            $.each(_cancelActionMeta, function (val, m) {
                html += '<option value="' + val + '"' + (val === defaultAction ? ' selected' : '') + '>' + m.label + '</option>';
            });
            html += '</select>';
            html += '<div id="swalCancelDesc" class="text-muted small mt-2 p-2 rounded" style="background:#f8f9fa;">' + meta.desc + '</div>';
            html += '</div>';
        }

        return html;
    }

    // ── Checkbox / select-all wiring ─────────────────────────────────────
    basePageHeaderFunc(ModuleHeader, ModuleTable, ModuleRow);

    $(ModuleHeader).on('click', function () {
        _invUpdateSelectAllBanner();
    });

    $(document).on('change', ModuleRow, function () {
        onClickOfCheckbox(this, ModuleTable, ModuleHeader, ModuleRow);
        _invUpdateSelectAllBanner();
        MultipleDeleteOption();
    });

    $(document).on('click', '#invSelectAllLink', function (e) {
        e.preventDefault();
        _invSelectAllMode = true;
        _invUpdateSelectAllBanner();
    });

    $(document).on('click', '#invSelectAllClear', function (e) {
        e.preventDefault();
        SelectedUIDs = [];
        unSelectTableRecords(ModuleTable, ModuleRow);
        $(ModuleHeader).prop('checked', false).prop('indeterminate', false);
        _invClearSelectAll();
        MultipleDeleteOption();
    });

    // ── Bulk delete ───────────────────────────────────────────────────────
    $('#btnDelete').on('click', function () {
        var count = _invSelectAllMode ? _invTotalRecords : SelectedUIDs.length;
        Swal.fire({
            title: 'Delete ' + count + ' invoice' + (count === 1 ? '' : 's') + '?',
            text : 'This cannot be undone.',
            icon : 'warning', showCancelButton: true,
            confirmButtonText: 'Delete', confirmButtonColor: '#d33',
        }).then(function (r) {
            if (!r.isConfirmed) return;
            deleteMultipleInvoices();
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

    $(document).on('click', '.cancelInvoice', function () {
        var uid        = $(this).attr('data-uid');
        var num        = $(this).attr('data-num') || '';
        var paidAmt    = parseFloat($(this).attr('data-paid') || 0);
        var hasPaid    = paidAmt > 0;

        var baseHtml = num
            ? 'Cancel invoice <strong>' + num + '</strong>? This cannot be undone.'
            : 'This cannot be undone.';

        if (hasPaid) {
            baseHtml += '<div class="mt-2 text-muted small">Paid amount: <strong>&#8377;' +
                paidAmt.toLocaleString('en-IN', { minimumFractionDigits: 2 }) + '</strong></div>';
            baseHtml += _buildPaymentActionHtml(_invCancelSetting);
        }

        Swal.fire({
            title             : 'Cancel Invoice?',
            html              : baseHtml,
            icon              : 'warning',
            showCancelButton  : true,
            confirmButtonText : 'Yes, Cancel It',
            confirmButtonColor: '#fd7e14',
            didOpen: function () {
                // Shrink icon only — no other side effects
                var $icon = $(Swal.getIcon());
                $icon.css({ width: '3em', height: '3em', borderWidth: '2px' });
                $icon.find('.swal2-icon-content').css({ fontSize: '1.5em' });
                // Dropdown change → update description
                $(document).on('change', '#swalCancelAction', function () {
                    var val  = $(this).val();
                    var desc = val && _cancelActionMeta[val] ? _cancelActionMeta[val].desc : '';
                    $('#swalCancelDesc').html(desc);
                });
                // "Click here to change" link
                $(document).on('click', '#swalChangeAction', function () {
                    $('#swalPresetWrap').addClass('d-none');
                    $('#swalChangeWrap').removeClass('d-none');
                });
            },
            willClose: function () {
                $(document).off('change', '#swalCancelAction');
                $(document).off('click', '#swalChangeAction');
            }
        }).then(function (r) {
            if (!r.isConfirmed) return;

            // Determine payment action to send
            var cancelPaymentAction = '';
            if (hasPaid) {
                var chosen = $('#swalCancelAction').val();
                if (_invCancelSetting === 'ask') {
                    cancelPaymentAction = chosen || '';
                    if (!cancelPaymentAction) {
                        Swal.fire({ icon: 'warning', text: 'Please select an action for the received payment.' });
                        return;
                    }
                } else {
                    cancelPaymentAction = chosen || _invCancelSetting;
                }
            }

            $.ajax({
                url   : '/invoices/updateInvoiceStatus',
                method: 'POST',
                data  : {
                    TransUID           : uid,
                    Status             : 'Cancelled',
                    CancelPaymentAction: cancelPaymentAction,
                    [CsrfName]         : CsrfToken
                },
                success: function (resp) {
                    if (resp.Error) {
                        Swal.fire({ icon: 'error', text: resp.Message });
                    } else {
                        var msg = resp.Message || 'Invoice cancelled.';
                        if (resp.CreditNoteAmount) {
                            msg += '<br><small class="text-muted mt-1 d-block">Credit Note: <strong>&#8377;' +
                                   parseFloat(resp.CreditNoteAmount).toLocaleString('en-IN', { minimumFractionDigits: 2 }) +
                                   '</strong> (' + (resp.CreditNoteStatus || 'Pending') + ')</small>';
                        }
                        if (resp.CustomerBalance !== undefined) {
                            msg += '<br><small class="text-muted mt-1 d-block">Customer Balance: <strong>' +
                                   resp.CustomerBalanceType + ' &#8377;' +
                                   parseFloat(resp.CustomerBalance).toLocaleString('en-IN', { minimumFractionDigits: 2 }) +
                                   '</strong></small>';
                        }
                        getInvoicesDetails(undefined, undefined, undefined, function () {
                            Swal.fire({ icon: 'success', html: msg, timer: 3000, showConfirmButton: false });
                        });
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
    if (!document.querySelector('.apex-stats-strip')) return;
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

    $('.apex-stat-item[data-stat-filter="All"]        .apex-stat-count').text(cntAll.toLocaleString());
    $('.apex-stat-item[data-stat-filter="All"]        .apex-stat-amount').text(fmtAmt(amtAll));
    $('.apex-stat-item[data-stat-filter="InvPending"] .apex-stat-count').text(cntPending.toLocaleString());
    $('.apex-stat-item[data-stat-filter="InvPending"] .apex-stat-amount').text(fmtAmt(amtPending));
    $('.apex-stat-item[data-stat-filter="Paid"]       .apex-stat-count').text(cntPaid.toLocaleString());
    $('.apex-stat-item[data-stat-filter="Paid"]       .apex-stat-amount').text(fmtAmt(amtPaid));
    $('.apex-stat-item[data-stat-filter="Draft"]      .apex-stat-count').text(cntDraft.toLocaleString());
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
    $row.find('.inv-paid-amt').text(currencySymbol + parseFloat(paidTotal || 0).toLocaleString('en-IN', { minimumFractionDigits: decimalPlaces, maximumFractionDigits: decimalPlaces }));

    // Update balance amount
    var balance = parseFloat(invoice.BalanceAmount || 0);
    $row.find('.inv-balance-amt').text(currencySymbol + balance.toLocaleString('en-IN', { minimumFractionDigits: decimalPlaces, maximumFractionDigits: decimalPlaces }));
    
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

// ── Credit Notes tab ─────────────────────────────────────────────────────────
var _cnPageNo    = 1;
var _cnRowLimit  = 10;
var _cnStatus    = 'All';
var _cnSearch    = '';
var _cnLoading   = false;
var _cnCdnUrl    = '<?php echo addslashes(getenv("FILE_UPLOAD") == "amazonaws" ? getenv("CDN_URL") : getenv("CFLARE_R2_CDN")); ?>';

/**
 * Formats a MySQL datetime/date string using the settings list date format.
 * @param {string} dtStr - 'YYYY-MM-DD' or 'YYYY-MM-DD HH:MM:SS'
 * @returns {string}
 */
function _cnFormatDate(dtStr) {
    if (!dtStr) return '—';
    var datePart = String(dtStr).split(' ')[0];
    return formatDateDisplay(datePart) || datePart;
}

/**
 * Returns a relative time string (e.g. "2 hrs ago") from a MySQL datetime string.
 * @param {string} dtStr - 'YYYY-MM-DD HH:MM:SS'
 * @returns {string}
 */
function _cnTimeAgo(dtStr) {
    if (!dtStr) return '';
    var d = new Date(dtStr.replace(' ', 'T'));
    if (isNaN(d)) return '';
    var diff = Math.floor((Date.now() - d.getTime()) / 1000);
    if (diff < 60)    return diff + ' sec ago';
    if (diff < 3600)  return Math.floor(diff / 60) + ' min ago';
    if (diff < 86400) return Math.floor(diff / 3600) + ' hr' + (Math.floor(diff / 3600) === 1 ? '' : 's') + ' ago';
    var days = Math.floor(diff / 86400);
    return days + ' day' + (days === 1 ? '' : 's') + ' ago';
}

/**
 * Builds party avatar HTML matching the PHP partyAvatar() pattern.
 * @param {string} name - Party display name
 * @param {string|null} image - Image path (relative, appended to CDN URL)
 * @returns {string}
 */
function _cnPartyAvatar(name, image) {
    if (image) {
        return '<div class="avatar avatar-sm flex-shrink-0">'
             + '<img src="' + _cnCdnUrl + image + '" class="rounded-circle cursor-pointer preview-image" style="width:32px;height:32px;object-fit:cover;" />'
             + '</div>';
    }
    var initials = '?';
    if (name) {
        var parts = String(name).trim().split(/\s+/);
        initials  = parts[0].charAt(0).toUpperCase();
        if (parts.length > 1) initials += parts[parts.length - 1].charAt(0).toUpperCase();
    }
    return '<div class="avatar avatar-sm flex-shrink-0">'
         + '<span class="avatar-initial rounded-circle bg-label-primary" style="font-size:.72rem;">' + initials + '</span>'
         + '</div>';
}

function loadCreditNotes(pageNo) {
    if (_cnLoading) return;
    _cnLoading = true;
    pageNo = pageNo || _cnPageNo;
    var $tbody = $('#cnTableBody');
    $tbody.html('<tr><td colspan="7" class="text-center py-4"><i class="bx bx-loader-alt bx-spin me-1"></i> Loading...</td></tr>');

    $.ajax({
        url  : '/invoices/getCreditNotesList',
        type : 'POST',
        data : {
            PageNo   : pageNo,
            RowLimit : _cnRowLimit,
            Status   : _cnStatus,
            Search   : _cnSearch,
            [CsrfName]: CsrfToken,
        },
        success: function (resp) {
            _cnLoading = false;
            if (resp.Error) {
                $tbody.html('<tr><td colspan="7" class="text-center py-3 text-danger">' + (resp.Message || 'Error loading credit notes') + '</td></tr>');
                return;
            }
            _cnPageNo = resp.PageNo || pageNo;
            var rows  = resp.Data  || [];
            var total = resp.TotalCount || 0;
            var cur   = '<?php echo addslashes($JwtData->GenSettings->CurrenySymbol ?? '₹'); ?>';
            var dec   = <?php echo (int)($JwtData->GenSettings->DecimalPoints ?? 2); ?>;

            // Update tab count badge regardless of whether rows are empty
            var $cnBadge = $('.inv-cn-tab .trans-tab-count');
            if (total > 0) {
                $cnBadge.text(total).removeClass('d-none');
            } else {
                $cnBadge.text('').addClass('d-none');
            }

            if (!rows.length) {
                $tbody.html('<tr><td colspan="7" class="text-center py-4 text-muted">No credit notes found.</td></tr>');
                $('#cnPagination').empty();
                return;
            }

            var html = '';
            $.each(rows, function (i, cn) {
                var statusBadge = '';
                if (cn.Status === 'Pending')   statusBadge = '<span class="badge bg-label-warning">Pending</span>';
                if (cn.Status === 'Applied')   statusBadge = '<span class="badge bg-label-success">Applied</span>';
                if (cn.Status === 'Refunded')  statusBadge = '<span class="badge bg-label-secondary">Refunded</span>';
                if (cn.Status === 'Cancelled') statusBadge = '<span class="badge bg-label-danger">Cancelled</span>';

                var amt         = parseFloat(cn.Amount || 0).toLocaleString('en-IN', { minimumFractionDigits: dec, maximumFractionDigits: dec });
                var cnDateFmt   = cn.CreatedOn      ? _cnFormatDate(cn.CreatedOn)      : '—';
                var srcDateFmt  = cn.SourceTransDate ? _cnFormatDate(cn.SourceTransDate) : '—';
                var creatorName = cn.CreatorName    ? cn.CreatorName                   : '';
                var timeAgoStr  = cn.CreatedOn      ? _cnTimeAgo(cn.CreatedOn)         : '';

                // ── CN Number cell ────────────────────────────────────────────
                var cnNumHtml = '<td>';
                cnNumHtml += '<span class="fw-semibold trans-doc-number">' + (cn.CreditNoteNumber || '—') + '</span>';
                if (cnDateFmt !== '—') cnNumHtml += '<div class="text-muted mt-1" style="font-size:.72rem;">' + cnDateFmt + '</div>';
                if (creatorName)       cnNumHtml += '<div style="font-size:.68rem;color:#b0b7c3;">by ' + creatorName + '</div>';
                cnNumHtml += '</td>';

                // ── Customer cell ─────────────────────────────────────────────
                var custHtml = '<td class="inv-party-td">';
                if (cn.CustomerName) {
                    custHtml += '<div class="d-flex align-items-center gap-2">';
                    custHtml += _cnPartyAvatar(cn.CustomerName, cn.CustomerImage);
                    custHtml += '<div>';
                    custHtml += '<div class="trans-party-name fw-semibold">' + cn.CustomerName + '</div>';
                    if (cn.CustomerArea) custHtml += '<div style="font-size:.7rem;color:#888;margin-top:1px;"><i class="bx bx-map" style="font-size:.72rem;"></i> ' + cn.CustomerArea + '</div>';
                    if (cn.MobileNo)     custHtml += '<div style="font-size:.72rem;color:#888;">' + cn.MobileNo + '</div>';
                    custHtml += '</div></div>';
                } else {
                    custHtml += '<span class="text-muted">—</span>';
                }
                custHtml += '</td>';

                // ── Source SR cell (number as link + date below) ───────────────
                var srHtml = '<td>';
                if (cn.SourceTransUID > 0 && cn.SourceTransNumber) {
                    srHtml += '<a href="javascript:void(0)" class="fw-semibold trans-doc-number viewTransaction"'
                            + ' data-uid="'    + cn.SourceTransUID    + '"'
                            + ' data-module="' + (cn.SourceModuleUID || 0) + '"'
                            + (cn.SourceTransToken ? ' data-token="' + cn.SourceTransToken + '"' : '')
                            + '>' + cn.SourceTransNumber + '</a>';
                } else {
                    srHtml += '<span class="fw-semibold">' + (cn.SourceTransNumber || '—') + '</span>';
                }
                if (srcDateFmt !== '—') srHtml += '<div class="text-muted mt-1" style="font-size:.72rem;">' + srcDateFmt + '</div>';
                srHtml += '</td>';

                // ── Created On cell (datetime + relative + by user) ────────────
                var createdOnHtml = '<td>';
                if (cn.CreatedOn) {
                    var createdD   = new Date(cn.CreatedOn.replace(' ', 'T'));
                    var createdFmt = isNaN(createdD) ? cn.CreatedOn
                        : cnDateFmt + ' '
                        + ('0' + createdD.getHours()).slice(-2) + ':'
                        + ('0' + createdD.getMinutes()).slice(-2) + ' '
                        + (createdD.getHours() >= 12 ? 'PM' : 'AM');
                    createdOnHtml += '<div style="font-size:.8rem;">' + createdFmt + '</div>';
                    if (timeAgoStr) createdOnHtml += '<div class="text-muted" style="font-size:.72rem;">' + timeAgoStr + '</div>';
                    if (creatorName) createdOnHtml += '<div style="font-size:.68rem;color:#b0b7c3;">by ' + creatorName + '</div>';
                } else {
                    createdOnHtml += '<span class="text-muted">—</span>';
                }
                createdOnHtml += '</td>';

                html += '<tr>';
                html += '<td class="text-muted">' + ((_cnPageNo - 1) * _cnRowLimit + i + 1) + '</td>';
                html += cnNumHtml;
                html += custHtml;
                html += srHtml;
                html += '<td class="fw-semibold">' + cur + ' ' + amt + '</td>';
                html += '<td>' + statusBadge + '</td>';
                html += createdOnHtml;
                html += '</tr>';
            });
            $tbody.html(html);

            // Build simple pagination
            var lastPage = Math.ceil(total / _cnRowLimit) || 1;
            var pagHtml  = '';
            if (lastPage > 1) {
                pagHtml += '<div class="col-auto text-muted small">Showing ' + ((_cnPageNo - 1) * _cnRowLimit + 1) + '–' + Math.min(_cnPageNo * _cnRowLimit, total) + ' of ' + total + '</div>';
                pagHtml += '<div class="col-auto"><ul class="pagination pagination-sm mb-0">';
                pagHtml += '<li class="page-item' + (_cnPageNo <= 1 ? ' disabled' : '') + '"><a class="page-link cn-page-link" href="#" data-page="' + (_cnPageNo - 1) + '">&laquo;</a></li>';
                for (var p = Math.max(1, _cnPageNo - 2); p <= Math.min(lastPage, _cnPageNo + 2); p++) {
                    pagHtml += '<li class="page-item' + (p === _cnPageNo ? ' active' : '') + '"><a class="page-link cn-page-link" href="#" data-page="' + p + '">' + p + '</a></li>';
                }
                pagHtml += '<li class="page-item' + (_cnPageNo >= lastPage ? ' disabled' : '') + '"><a class="page-link cn-page-link" href="#" data-page="' + (_cnPageNo + 1) + '">&raquo;</a></li>';
                pagHtml += '</ul></div>';
            } else {
                pagHtml = '<div class="col-auto text-muted small">' + total + ' record' + (total !== 1 ? 's' : '') + '</div>';
            }
            $('#cnPagination').html(pagHtml);

        },
        error: function () {
            _cnLoading = false;
            $('#cnTableBody').html('<tr><td colspan="7" class="text-center py-3 text-danger">Request failed.</td></tr>');
        }
    });
}


// ── CN Status filter panel (Pay Status box style) ────────────────────────────

// Open / close panel
$(document).on('click', '#invCnStatusFilter', function (e) {
    e.stopPropagation();
    var $box = $('#invCnStatusBox');
    if ($box.is(':visible')) { $box.hide(); return; }
    $('.trans-col-filterbox, .tpcf-box').not($box).hide();
    var rect = this.getBoundingClientRect();
    var boxW = $box.outerWidth() || 170;
    var left = rect.left;
    var top  = rect.bottom + 4;
    if (left + boxW + 16 > window.innerWidth) left = window.innerWidth - boxW - 16;
    $box.css({ top: top + 'px', left: left + 'px' }).show();
});

// Close button inside the panel
$(document).on('click', '#invCnStatusBox .tcf-close-btn', function () {
    $('#invCnStatusBox').hide();
});

// Option selected — single-select, auto-applies immediately
$(document).on('click', '.cn-status-panel-opt', function (e) {
    e.preventDefault();
    _cnStatus = $(this).data('val') || 'All';
    _cnPageNo = 1;
    $('.cn-status-panel-opt').removeClass('cn-opt-active');
    $(this).addClass('cn-opt-active');
    var btnLabel = _cnStatus !== 'All' ? _cnStatus : 'CN Status';
    var $trigger = $('#invCnStatusFilter');
    $trigger.html('<i class="bx bx-transfer-alt me-1"></i>' + btnLabel);
    _cnStatus !== 'All' ? $trigger.addClass('has-filter') : $trigger.removeClass('has-filter');
    $('#invCnStatusBox').hide();
    loadCreditNotes(1);
});

// CN pagination
$(document).on('click', '.cn-page-link', function (e) {
    e.preventDefault();
    var page = parseInt($(this).data('page'));
    if (page > 0) { _cnPageNo = page; loadCreditNotes(page); }
});
</script>
