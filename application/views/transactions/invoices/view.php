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

                // ── URL tab/search state (read from $_GET — no DB needed) ──────
                $_invTabSlugMap = ['all' => 'All', 'pending' => 'InvPending', 'paid' => 'Paid', 'cancelled' => 'Cancelled', 'draft' => 'Draft', 'creditnotes' => 'CreditNotes'];
                $initTab    = $_invTabSlugMap[strtolower($_GET['tab'] ?? 'all')] ?? 'All';
                $initSearch = trim($_GET['search'] ?? '');
                $initPage   = max(1, (int)($_GET['page'] ?? 1));
                $_invShowUserFilter = is_array($OrgUsers) && count($OrgUsers) > 1;
                $tabFilterMap = [
                    'All'         => array_merge(['invPayStatusFilter', 'invPayModeFilter', 'invPartyFilterTrigger'], $_invShowUserFilter ? ['invCreatedByFilter'] : []),
                    'InvPending'  => array_merge(['invPayModeFilter', 'invPartyFilterTrigger'],                       $_invShowUserFilter ? ['invCreatedByFilter'] : []),
                    'Paid'        => array_merge(['invPayModeFilter', 'invPartyFilterTrigger'],                       $_invShowUserFilter ? ['invCreatedByFilter'] : []),
                    'Cancelled'   => array_merge(['invPayStatusFilter', 'invPayModeFilter', 'invPartyFilterTrigger'], $_invShowUserFilter ? ['invCreatedByFilter'] : []),
                    'Draft'       => array_merge(['invPartyFilterTrigger'],                                           $_invShowUserFilter ? ['invCreatedByFilter'] : []),
                    'CreditNotes' => ['invPartyFilterTrigger', 'invCnStatusWrap'],
                ];
                $visibleFilters = $tabFilterMap[$initTab] ?? $tabFilterMap['All'];
                $cur = htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹');

                if (($JwtData->GenSettings->ShowStats ?? 1) && ($JwtData->TransSettings->ShowTransactionStats ?? 1)):
                ?>
                <div class="apex-stats-strip">
                    <div class="apex-stat-item <?php echo $initTab === 'All' ? 'active' : ''; ?>" data-status="All" data-stat-filter="All" style="--stat-color:#f59e0b">
                        <div class="apex-stat-icon" style="background:#fef3c7;"><i class="bx bx-receipt" style="color:#f59e0b;"></i></div>
                        <div class="apex-stat-body">
                            <div class="apex-stat-label">All Invoices</div>
                            <div class="apex-stat-bottom"><span class="apex-stat-count">0</span><span class="apex-stat-amount"><?php echo $cur; ?> 0</span></div>
                        </div>
                    </div>
                    <div class="apex-stat-item <?php echo $initTab === 'InvPending' ? 'active' : ''; ?>" data-status="InvPending" data-stat-filter="InvPending" style="--stat-color:#f97316">
                        <div class="apex-stat-icon" style="background:#fff7ed;"><i class="bx bx-time-five" style="color:#f97316;"></i></div>
                        <div class="apex-stat-body">
                            <div class="apex-stat-label">Pending</div>
                            <div class="apex-stat-bottom"><span class="apex-stat-count">0</span><span class="apex-stat-amount"><?php echo $cur; ?> 0</span></div>
                        </div>
                    </div>
                    <div class="apex-stat-item <?php echo $initTab === 'Paid' ? 'active' : ''; ?>" data-status="Paid" data-stat-filter="Paid" style="--stat-color:#16a34a">
                        <div class="apex-stat-icon" style="background:#dcfce7;"><i class="bx bx-check-circle" style="color:#16a34a;"></i></div>
                        <div class="apex-stat-body">
                            <div class="apex-stat-label">Paid</div>
                            <div class="apex-stat-bottom"><span class="apex-stat-count">0</span><span class="apex-stat-amount"><?php echo $cur; ?> 0</span></div>
                        </div>
                    </div>
                    <div class="apex-stat-item <?php echo $initTab === 'Draft' ? 'active' : ''; ?>" data-status="Draft" data-stat-filter="Draft" style="--stat-color:#64748b">
                        <div class="apex-stat-icon" style="background:#f1f5f9;"><i class="bx bx-edit" style="color:#64748b;"></i></div>
                        <div class="apex-stat-body">
                            <div class="apex-stat-label">Drafts</div>
                            <div class="apex-stat-bottom"><span class="apex-stat-count">0</span><span class="apex-stat-amount"></span></div>
                        </div>
                    </div>
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
                            <a href="javascript:void(0);" id="invCreatedByFilter" class="apex-filter-btn<?php echo in_array('invCreatedByFilter', $visibleFilters) ? '' : ' d-none'; ?>" title="Filter by User"><i class="bx bx-user me-1"></i>Updated By</a>
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
                            <a href="<?= site_url('invoices/pos-billing') ?>" class="btn btn-sm btn-outline-primary flex-shrink-0" data-bs-toggle="tooltip" data-bs-placement="bottom" title="POS Billing — fast counter mode"><i class="bx bx-barcode-reader me-1"></i>POS</a>
                            <a href="<?= site_url('invoices/create') ?>" class="btn btn-sm btn-primary flex-shrink-0" data-bs-toggle="tooltip" data-bs-placement="bottom" title="<?php echo t('create_invoice', 'Create Invoice'); ?>"><i class="bx bx-plus me-1"></i><?php echo t('lbl_new', 'New'); ?></a>
                        </div>

                        <!-- ── Tabs Row ──────────────────────────────────── -->
                        <div class="apex-tabs-row">
                            <ul class="nav trans-status-tabs" id="invStatusTabs" role="tablist" data-trans-path="/invoices">
                                <li class="nav-item"><a class="nav-link <?php echo $initTab === 'All' ? 'active' : ''; ?> inv-status-tab" data-status="All" data-url-tab="all" href="javascript:void(0);">All <span class="trans-tab-count ms-1 d-none"></span></a></li>
                                <li class="nav-item"><a class="nav-link <?php echo $initTab === 'InvPending' ? 'active' : ''; ?> inv-status-tab" data-status="InvPending" data-url-tab="pending" href="javascript:void(0);">Pending <span class="trans-tab-count ms-1 d-none"></span></a></li>
                                <li class="nav-item"><a class="nav-link <?php echo $initTab === 'Paid' ? 'active' : ''; ?> inv-status-tab" data-status="Paid" data-url-tab="paid" href="javascript:void(0);">Paid <span class="trans-tab-count ms-1 d-none"></span></a></li>
                                <li class="nav-item"><a class="nav-link <?php echo $initTab === 'Cancelled' ? 'active' : ''; ?> inv-status-tab" data-status="Cancelled" data-url-tab="cancelled" href="javascript:void(0);">Cancelled <span class="trans-tab-count ms-1 d-none"></span></a></li>
                                <li class="nav-item"><a class="nav-link <?php echo $initTab === 'Draft' ? 'active' : ''; ?> inv-status-tab" data-status="Draft" data-url-tab="draft" href="javascript:void(0);">Drafts <span class="trans-tab-count ms-1 d-none"></span></a></li>
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
                                    <tbody id="invTbody" class="r2k-tbody table-border-bottom-0">
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination -->
                            <div class="row mx-0 px-3 mt-1 justify-content-between align-items-center invPagination apex-pag-sticky r2k-pag-hidden" id="invPagination">
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
                                            <th style="width:56px"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="cnTableBody" class="r2k-tbody table-border-bottom-0">
                                        <tr><td colspan="8" class="text-center py-4 text-muted">Loading...</td></tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="row mx-0 px-3 mt-1 justify-content-between align-items-center cnPagination apex-pag-sticky r2k-pag-hidden" id="cnPagination"></div>
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

<?php if ($_invShowUserFilter): ?>
<?php $this->load->view('common/filter_panels/col_user_filter_box', [
    'ColUserFilterConfig' => [
        'id'         => 'invCreatedByFilterBox',
        'triggerId'  => 'invCreatedByFilter',
        'checkClass' => 'inv-user-chk',
        'OrgUsers'   => $OrgUsers,
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
<script src="/js/core/viewmodal.js"></script>
<script src="/js/core/a4_print.js"></script>
<script src="/js/transactions/filter_bar.js"></script>
<script src="/js/core/col_filter.js"></script>
<script src="/js/common/pagecheckbox.js"></script>
<script src="/js/core/pagination.js"></script>
<script src="/js/core/time_ago.js"></script>
<script src="/js/transactions/invoices.js"></script>

<script>

const ModuleId     = 103;
const ModuleTable  = '#invTable';
const ModulePag    = '.invPagination';
const ModuleHeader = '.invHeaderCheck';
const ModuleRow    = '.invCheck';

var _invInitTab    = <?php echo json_encode($initTab); ?>;
var _invInitSearch = <?php echo json_encode($initSearch); ?>;

// ── Tab filter visibility map ────────────────────────────────────────
const _invTabFilterMap = { All: ['invPayStatusFilter','invPayModeFilter','invCreatedByFilter','invPartyFilterTrigger'], InvPending: ['invPayModeFilter','invCreatedByFilter','invPartyFilterTrigger'], Paid: ['invPayModeFilter','invCreatedByFilter','invPartyFilterTrigger'], Cancelled: ['invPayStatusFilter','invPayModeFilter','invCreatedByFilter','invPartyFilterTrigger'], Draft: ['invCreatedByFilter','invPartyFilterTrigger'], CreditNotes: ['invPartyFilterTrigger','invCnStatusWrap'] };
const _allInvFilterEls = ['invPayStatusFilter','invPayModeFilter','invCreatedByFilter','invPartyFilterTrigger','invCnStatusWrap'];
var _initPage          = <?php echo $initPage; ?>;

$(function () {
    'use strict';

    _checkPendingToast('_invPendingToast');
    _checkPendingToast('_invPendingCnWarn');
    PageNo = _initPage;
    Filter['Status'] = _invInitTab === 'CreditNotes' ? 'All' : _invInitTab;
    if (_invInitSearch) { Filter.Name = _invInitSearch; }
    initExport({ moduleUID: 103, getFilters: function () {
        return $.extend({}, Filter,
            tfb                ? tfb.getState()                : {},
            payStatusFilter    ? payStatusFilter.getState()    : {},
            payModeFilter      ? payModeFilter.getState()      : {},
            invCreatedByFilter ? invCreatedByFilter.getState() : {},
            invPartyFilter     ? invPartyFilter.getState()     : {}
        );
    } });

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

    var invCreatedByFilter = new TransColFilter({
        boxId       : 'invCreatedByFilterBox',
        triggerId   : 'invCreatedByFilter',
        filterKey   : 'UpdatedByUIDs',
        activeClass : 'has-filter',
        onApply     : function () { PageNo = 1; getInvoicesDetails(); }
    });

    // Lazy-load org users on first click
    var _invUsersLoaded = false;
    $(document).on('click', '#invCreatedByFilter', function () {
        if (_invUsersLoaded) return;
        $.ajax({
            url    : global_services_url + 'api/users/getOrgUsers',
            method : 'GET',
            headers: { Authorization: 'Bearer ' + JwtToken },
            data   : { orgId: parseInt(JwtData.Org.OrgUID, 10) },
            success: function (resp) {
                if (resp.Error || !resp.Users || !resp.Users.length) return;
                _invUsersLoaded = true;
                invCreatedByFilter.setItems(resp.Users.map(function (u) {
                    return { value: u.UserUID, label: u.FullName || (u.FirstName + ' ' + u.LastName) };
                }));
            }
        });
    });

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

    // Initial page load — fetch from services (PHP no longer pre-renders the list)
    if (_invInitTab !== 'CreditNotes') {
        getInvoicesDetails();
    }

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
    $(document).on('click', 'a[href="<?= site_url('invoices/create') ?>"]', function (e) {
        e.preventDefault();
        var params = new URLSearchParams();
        params.set('returnTab', Filter.Status || 'All');
        if (PageNo > 1) params.set('returnPage', PageNo);
        window.location.href = global_base_url + 'invoices/create?' + params.toString();
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
        if (_invInitSearch) { _cnSearch = _invInitSearch; }
        if (_cnPreloaded) {
            if (_cnInitCount > 0) {
                $('.inv-cn-tab .trans-tab-count').text(_cnInitCount).removeClass('d-none');
            }
        } else {
            loadCreditNotes(1);
        }
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
        return $.extend({ RowLimit: RowLimit, PageNo: PageNo, Filter: Filter, orgId: parseInt(JwtData.Org.OrgUID, 10), userId: parseInt(JwtData.User.UserUID, 10), branchId: parseInt(JwtData.Org.BranchUID, 10) || 1 }, extra);
    }

    function _renderListResponse() {
        getInvoicesDetails();
    }

    $(document).on('click', '.deleteInvoice', function () {
        var uid           = $(this).data('uid');
        var num           = $(this).data('num') || '';
        var advanceIn     = parseInt($(this).data('advance-in')     || 0, 10);
        var advanceOut    = parseInt($(this).data('advance-out')    || 0, 10);
        var onAccountIn   = parseInt($(this).data('onaccount-in')   || 0, 10);
        var creditNoteIn  = parseInt($(this).data('creditnote-in')  || 0, 10);
        var salesReturn   = parseInt($(this).data('sales-return')   || 0, 10);

        if (salesReturn) {
            Swal.fire({ icon: 'warning', title: 'Cannot Delete', text: 'A sales return has been raised against this invoice. Cancel or delete the return first, then delete this invoice.' });
            return;
        }
        if (advanceOut) {
            Swal.fire({ icon: 'warning', title: 'Cannot Delete', text: 'This invoice has excess credit applied to another invoice. Delete that advance entry first, then try again.' });
            return;
        }
        if (advanceIn) {
            Swal.fire({ icon: 'warning', title: 'Cannot Delete', text: 'This invoice was paid using advance credit. Delete the advance payment entry first, then try again.' });
            return;
        }
        if (onAccountIn) {
            Swal.fire({ icon: 'warning', title: 'Cannot Delete', text: 'This invoice has an On-Account credit applied to it. Delete the on-account payment entry first, then try again.' });
            return;
        }
        if (creditNoteIn) {
            Swal.fire({ icon: 'warning', title: 'Cannot Delete', text: 'This invoice has a Credit Note applied to it. Please remove the credit note payment entry first, then try again.' });
            return;
        }

        Swal.fire({
            title: 'Delete Invoice?',
            html : num ? 'Delete <strong>' + num + '</strong>? This cannot be undone.' : 'This cannot be undone.',
            icon : 'warning', showCancelButton: true,
            confirmButtonText: 'Delete', confirmButtonColor: '#d33',
        }).then(function (r) {
            if (!r.isConfirmed) return;
            ajaxLoading(1);
            $.ajax({
                url    : global_services_url + 'api/invoices/deleteInvoice',
                method : 'POST',
                headers: { Authorization: 'Bearer ' + JwtToken },
                data   : _actionPostData({ TransUID: uid }),
                success: function (resp) {
                    ajaxLoading(0);
                    hideUIBlock();
                    if (resp.Error) { Swal.fire({ icon:'error', text:resp.Message }); return; }
                    showToastNotification(resp.Message || 'Deleted.', 'success');
                    getInvoicesDetails();
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
        var uid          = $(this).attr('data-uid');
        var num          = $(this).attr('data-num') || '';
        var paidAmt      = parseFloat($(this).attr('data-paid') || 0);
        var hasPaid      = paidAmt > 0;
        var advanceIn    = parseInt($(this).attr('data-advance-in')    || 0, 10);
        var advanceOut   = parseInt($(this).attr('data-advance-out')   || 0, 10);
        var onAccountIn  = parseInt($(this).attr('data-onaccount-in')  || 0, 10);
        var creditNoteIn = parseInt($(this).attr('data-creditnote-in') || 0, 10);
        var salesReturn  = parseInt($(this).attr('data-sales-return')  || 0, 10);

        if (salesReturn) {
            Swal.fire({ icon: 'warning', title: 'Cannot Cancel', text: 'A sales return has been raised against this invoice. Cancel or delete the return first, then cancel this invoice.' });
            return;
        }
        if (advanceOut) {
            Swal.fire({ icon: 'warning', title: 'Cannot Cancel', text: 'This invoice has excess credit applied to another invoice. Delete that advance entry first, then try again.' });
            return;
        }
        if (advanceIn) {
            Swal.fire({ icon: 'warning', title: 'Cannot Cancel', text: 'This invoice was paid using advance credit. Delete the advance payment entry first, then try again.' });
            return;
        }
        if (onAccountIn) {
            Swal.fire({ icon: 'warning', title: 'Cannot Cancel', text: 'This invoice has an On-Account credit applied to it. Delete the on-account payment entry first, then try again.' });
            return;
        }
        if (creditNoteIn) {
            Swal.fire({ icon: 'warning', title: 'Cannot Cancel', text: 'This invoice has a Credit Note applied to it. Please remove the credit note payment entry first, then try again.' });
            return;
        }

        var baseHtml = num
            ? 'Cancel invoice <strong>' + num + '</strong>? This cannot be undone.'
            : 'This cannot be undone.';

        if (hasPaid) {
            baseHtml += '<div class="mt-2 text-muted small">Paid amount: <strong>&#8377;' +
                paidAmt.toLocaleString('en-IN', { minimumFractionDigits: 2 }) + '</strong></div>';
            baseHtml += _buildPaymentActionHtml(_invCancelSetting);
        }

        baseHtml += '<div class="mt-3 text-start">' +
            '<label class="form-label fw-semibold small mb-1">Reason for cancellation ' +
            '<span class="text-muted fw-normal">(optional)</span></label>' +
            '<textarea class="form-control form-control-sm" id="swalCancelReason" rows="2" ' +
            'maxlength="500" placeholder="e.g. Customer requested cancellation, duplicate entry…"></textarea>' +
            '</div>';

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

            var cancelReason = $.trim($('#swalCancelReason').val() || '');

            ajaxLoading(1);
            $.ajax({
                url    : global_services_url + 'api/invoices/updateInvoiceStatus',
                method : 'POST',
                headers: { Authorization: 'Bearer ' + JwtToken },
                data   : {
                    TransUID           : uid,
                    Status             : 'Cancelled',
                    CancelPaymentAction: cancelPaymentAction,
                    CancelReason       : cancelReason,
                    orgId              : parseInt(JwtData.Org.OrgUID, 10),
                    userId             : parseInt(JwtData.User.UserUID, 10),
                    branchId           : parseInt(JwtData.Org.BranchUID, 10) || 1,
                },
                success: function (resp) {
                    ajaxLoading(0);
                    hideUIBlock();
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
                        showToastNotification(msg, 'success');
                        getInvoicesDetails();
                    }
                },
                error: function () {
                    ajaxLoading(0);
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
    function cnt(s) { return (stats[s] && stats[s].count)  ? parseInt(stats[s].count)   : 0; }
    function amt(s) { return (stats[s] && stats[s].amount) ? parseFloat(stats[s].amount) : 0; }
    function fmtAmt(v) {
        return cur + ' ' + smartDecimal(parseFloat(v));
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

// ── Init shared payment modal (invoices) — data loaded lazily ────────────────
var _invRpCur = '<?php echo addslashes(htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹')); ?>';
initRecordPaymentModal([], [], _invRpCur);

var _invPayModalReady = false;
/**
 * Fetches PaymentTypes + BankAccounts from NestJS, inits modal, then opens it.
 * @param {Object} opts
 * @returns {void}
 */
function _invOpenPaymentModal(opts) {
    if (_invPayModalReady) { window.rpOpenModal(opts); return; }
    var orgId = parseInt(JwtData.Org.OrgUID, 10);
    $.when(
        $.ajax({ url: global_services_url + 'api/payments/getPaymentTypes', method: 'GET', headers: { Authorization: 'Bearer ' + JwtToken } }),
        $.ajax({ url: global_services_url + 'api/payments/getBankAccounts', method: 'GET', headers: { Authorization: 'Bearer ' + JwtToken }, data: { orgId: orgId } })
    ).done(function (ptResp, baResp) {
        var pt = ptResp[0], ba = baResp[0];
        var payTypes  = (!pt.Error  && pt.PaymentTypes)  ? pt.PaymentTypes  : [];
        var bankAccts = (!ba.Error  && ba.BankAccounts)  ? $.grep(ba.BankAccounts, function (b) { return !parseInt(b.IsCash, 10); }) : [];
        initRecordPaymentModal(payTypes, bankAccts, _invRpCur);
        _invPayModalReady = true;
        window.rpOpenModal(opts);
    }).fail(function () { window.rpOpenModal(opts); });
}

window.rpAfterSuccess = function (resp) {
    getInvoicesDetails(PageNo, RowLimit, Filter);
};

// Open modal when "Receive Payment" clicked on an invoice row
$(document).on('click', '.invReceivePayment', function () {
    _invOpenPaymentModal({
        transUID  : $(this).data('uid'),
        submitUrl : global_services_url + 'api/invoices/recordInvoicePayment',
        docNum    : $(this).data('num')   || '',
        docDate   : $(this).data('date')  || '',
        partyName : $(this).data('party') || '',
        partyUID  : parseInt($(this).data('party-uid'), 10) || 0,
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
            var isCN       = (p.SourceType === 'CreditNote');
            var modeName   = isCN ? 'Credit Note' : (p.PaymentTypeName || 'Payment');
            var modeClass  = isCN ? 'bg-label-success' : 'bg-label-info';
            paymentHtml += '<span class="badge ' + modeClass + ' me-1">' + modeName + '</span>';
        });
        var hasAttach = invoice.PaymentAttachmentCount > 0;
        if (hasAttach) {
            paymentHtml += '<button type="button" class="btn btn-icon btn-sm transPayAttachBtn" data-uid="' + invoice.TransUID + '" data-num="' + (invoice.UniqueNumber || '') + '" data-url="' + global_services_url + 'api/invoices/getPaymentAttachments" title="View Payment Attachments"><i class="bx bx-paperclip text-primary"></i></button>';
        }
    } else {
        paymentHtml = '<span class="text-muted">—</span>';
    }
    $row.find('.inv-payment-mode').html(paymentHtml);

}

// ── Credit Notes tab ─────────────────────────────────────────────────────────
var _cnPageNo     = 1;
var _cnStatus     = 'All';
var _cnSearch     = '';
var _cnLoading    = false;
var _cnPreloaded  = false;
var _cnInitCount  = 0;

/**
 * Build credit-notes tbody HTML from NestJS getCreditNotesList response data.
 * @param {Array}  rows    CN row objects from NestJS
 * @param {number} offset  Zero-based serial number offset
 * @returns {string}
 */
function _buildCnRows(rows, offset) {
    if (!rows || !rows.length) {
        return '<tr><td colspan="8" class="text-center py-4 text-muted">No credit notes found.</td></tr>';
    }
    var statusBadge = { Pending: 'bg-label-warning', Applied: 'bg-label-success', Refunded: 'bg-label-secondary', Cancelled: 'bg-label-danger' };
    var dec = decimalPlaces || 2;
    var cur = currencySymbol || '₹';
    var html = '';
    $.each(rows, function (i, r) {
        var amt      = parseFloat(r.Amount || 0).toFixed(dec);
        var statusCls = statusBadge[r.Status] || 'bg-label-secondary';
        var isPending = r.Status === 'Pending';

        var custName = r.CustomerName || '';
        var words    = custName ? custName.trim().split(/\s+/) : [];
        var initials = words.length ? (words[0].charAt(0) + (words[1] ? words[1].charAt(0) : '')).toUpperCase() : '';

        var createdRaw = r.CreatedOn || '';
        var createdFmt = createdRaw ? new Date(createdRaw).toLocaleDateString('en-IN') : '—';
        var within24h  = createdRaw && (Date.now() - new Date(createdRaw).getTime()) < 86400000;
        var agoText    = (within24h && typeof time_ago === 'function') ? time_ago(createdRaw) : '';
        var srcDate    = r.SourceTransDate ? new Date(r.SourceTransDate).toLocaleDateString('en-IN') : '—';

        var esc = function (v) { return $('<span>').text(v).html(); };

        var cnCell = '<span class="fw-semibold trans-doc-number">' + esc(r.CreditNoteNumber || '—') + '</span>';
        if (createdFmt !== '—') cnCell += '<div class="text-muted mt-1" style="font-size:var(--r2k-text-sm);">' + createdFmt + '</div>';
        if (r.CreatorName)      cnCell += '<div style="font-size:var(--r2k-text-xs);color:#b0b7c3;">by ' + esc(r.CreatorName) + '</div>';

        var custCell = custName
            ? '<div class="d-flex align-items-center gap-2">'
                + '<div class="trans-party-avatar" style="background:#696cff22;color:#696cff;font-size:var(--r2k-text-75);">' + initials + '</div>'
                + '<div><div class="trans-party-name fw-semibold">' + esc(custName) + '</div>'
                + (r.CustomerArea ? '<div style="font-size:var(--r2k-text-70);color:#888;margin-top:1px;"><i class="bx bx-map" style="font-size:var(--r2k-text-sm);"></i> ' + esc(r.CustomerArea) + '</div>' : '')
                + (r.MobileNo    ? '<div style="font-size:var(--r2k-text-sm);color:#888;">' + esc(r.MobileNo) + '</div>' : '')
                + '</div></div>'
            : '<span class="text-muted">—</span>';

        var srcCell = (r.SourceTransUID > 0 && r.SourceTransNumber)
            ? '<a href="javascript:void(0)" class="fw-semibold trans-doc-number viewTransaction"'
                + ' data-uid="'    + parseInt(r.SourceTransUID)    + '"'
                + ' data-module="' + parseInt(r.SourceModuleUID || 0) + '"'
                + (r.SourceTransToken ? ' data-token="' + esc(r.SourceTransToken) + '"' : '')
                + '>' + esc(r.SourceTransNumber) + '</a>'
            : '<span class="text-muted">' + esc(r.SourceTransNumber || '—') + '</span>';
        if (srcDate !== '—') srcCell += '<div class="text-muted mt-1" style="font-size:var(--r2k-text-sm);">' + srcDate + '</div>';

        var createdCell = createdFmt !== '—'
            ? '<div style="font-size:var(--r2k-text-md);">' + createdFmt + '</div>'
                + (within24h && agoText ? '<div style="font-size:var(--r2k-text-xs);color:#0d6efd;font-weight:500;">' + agoText + '</div>' : '')
                + (r.CreatorName ? '<div class="text-muted" style="font-size:var(--r2k-text-70);">by ' + esc(r.CreatorName) + '</div>' : '')
            : '<span class="text-muted">—</span>';

        var actCell = '';
        if (isPending) {
            actCell = '<div class="dropdown">'
                + '<button class="trans-actions-btn" type="button" data-bs-toggle="dropdown"><i class="bx bx-dots-vertical-rounded fs-5"></i></button>'
                + '<ul class="dropdown-menu dropdown-menu-end shadow-sm" style="font-size:var(--r2k-text-82);min-width:160px;">'
                + '<li><a class="dropdown-item cn-cancel-btn" href="javascript:void(0)" data-uid="' + parseInt(r.CreditNoteUID) + '" data-number="' + esc(r.CreditNoteNumber || '—') + '" data-amount="' + esc(amt) + '"><i class="bx bx-x-circle me-2 text-warning"></i>Cancel</a></li>'
                + '<li><a class="dropdown-item cn-delete-btn" href="javascript:void(0)" data-uid="' + parseInt(r.CreditNoteUID) + '" data-number="' + esc(r.CreditNoteNumber || '—') + '" data-amount="' + esc(amt) + '"><i class="bx bx-trash me-2 text-danger"></i>Delete</a></li>'
                + '</ul></div>';
        }

        html += '<tr>'
            + '<td class="text-muted">'  + (offset + i + 1) + '</td>'
            + '<td>'                     + cnCell            + '</td>'
            + '<td class="inv-party-td">'+ custCell          + '</td>'
            + '<td>'                     + srcCell           + '</td>'
            + '<td class="fw-semibold">' + cur + ' ' + amt   + '</td>'
            + '<td><span class="badge '  + statusCls + '">'  + esc(r.Status || '') + '</span></td>'
            + '<td>'                     + createdCell       + '</td>'
            + '<td class="text-center">' + actCell           + '</td>'
            + '</tr>';
    });
    return html;
}

/**
 * Build pagination HTML for the Credit Notes tab.
 * @param {number} total  Total record count
 * @param {number} page   Current page (1-based)
 * @param {number} limit  Rows per page
 * @returns {string}
 */
function _buildCnPagination(total, page, limit) {
    if (!total || total <= limit) return '';
    var pages = Math.ceil(total / limit);
    var from  = (page - 1) * limit + 1;
    var to    = Math.min(page * limit, total);
    var html  = '<div class="d-flex align-items-center gap-1 text-muted small"><span>' + from + '–' + to + ' of ' + total + '</span></div>'
              + '<nav><ul class="pagination pagination-sm mb-0">';
    html += '<li class="page-item' + (page <= 1 ? ' disabled' : '') + '"><a class="page-link" href="javascript:void(0)" data-page="' + (page - 1) + '">‹</a></li>';
    var start = Math.max(1, page - 2);
    var end   = Math.min(pages, start + 4);
    if (end - start < 4) start = Math.max(1, end - 4);
    for (var p = start; p <= end; p++) {
        html += '<li class="page-item' + (p === page ? ' active' : '') + '"><a class="page-link" href="javascript:void(0)" data-page="' + p + '">' + p + '</a></li>';
    }
    html += '<li class="page-item' + (page >= pages ? ' disabled' : '') + '"><a class="page-link" href="javascript:void(0)" data-page="' + (page + 1) + '">›</a></li>';
    html += '</ul></nav>';
    return html;
}

/**
 * Loads the Credit Notes tab from NestJS and builds rows via _buildCnRows.
 * @param {number} pageNo
 * @returns {void}
 */
function loadCreditNotes(pageNo) {
    if (_cnLoading) return;
    _cnLoading = true;
    pageNo = pageNo || _cnPageNo;
    $('#cnTableBody').html('<tr><td colspan="8" class="text-center py-4"><i class="bx bx-loader-alt bx-spin me-1"></i> Loading...</td></tr>');

    $.ajax({
        url    : global_services_url + 'api/invoices/getCreditNotesList',
        method : 'GET',
        headers: { Authorization: 'Bearer ' + JwtToken },
        data   : {
            PageNo   : pageNo,
            RowLimit : RowLimit,
            Status   : _cnStatus,
            Search   : _cnSearch,
            orgId    : parseInt(JwtData.Org.OrgUID, 10),
            userId   : parseInt(JwtData.User.UserUID, 10),
            branchId : parseInt(JwtData.Org.BranchUID, 10) || 1,
        },
        success: function (resp) {
            _cnLoading = false;
            if (resp.Error) {
                $('#cnTableBody').html('<tr><td colspan="8" class="text-center py-3 text-danger">' + (resp.Message || 'Error loading credit notes') + '</td></tr>');
                return;
            }
            _cnPageNo = pageNo;
            var limit  = resp.RowLimit || RowLimit || 10;
            var offset = (pageNo - 1) * limit;
            $('#cnTableBody').html(_buildCnRows(resp.Data || [], offset));
            $('.cnPagination').html(_buildCnPagination(resp.TotalCount || 0, pageNo, limit));
            var total    = resp.TotalCount || 0;
            var $cnBadge = $('.inv-cn-tab .trans-tab-count');
            if (total > 0) { $cnBadge.text(total).removeClass('d-none'); } else { $cnBadge.text('').addClass('d-none'); }
            initTooltips();
        },
        error: function () {
            _cnLoading = false;
            $('#cnTableBody').html('<tr><td colspan="8" class="text-center py-3 text-danger">Request failed.</td></tr>');
        }
    });
}

// ── Cancel Credit Note ────────────────────────────────────────────────────────

$(document).on('click', '.cn-cancel-btn', function () {
    var uid    = $(this).data('uid');
    var number = $(this).data('number');
    var amount = $(this).data('amount');
    var cur    = '<?php echo addslashes($JwtData->GenSettings->CurrenySymbol ?? '₹'); ?>';

    var swalHtml = 'Cancel Credit Note <strong>' + number + '</strong>? This cannot be undone.'
        + '<div class="mt-2 text-muted small">Linked payment of <strong>' + cur + ' ' + amount + '</strong> will also be cancelled and the customer balance will be updated.</div>'
        + '<div class="mt-3 text-start">'
        + '<label class="form-label fw-semibold small mb-1">Reason for cancellation <span class="text-muted fw-normal">(optional)</span></label>'
        + '<textarea class="form-control form-control-sm" id="cnSwalCancelReason" rows="2" maxlength="500" placeholder="e.g. Customer requested cancellation, duplicate entry…"></textarea>'
        + '</div>';

    Swal.fire({
        title             : 'Cancel Credit Note?',
        html              : swalHtml,
        icon              : 'warning',
        showCancelButton  : true,
        confirmButtonText : 'Yes, Cancel It',
        confirmButtonColor: '#fd7e14',
        didOpen: function () {
            var $icon = $(Swal.getIcon());
            $icon.css({ width: '3em', height: '3em', borderWidth: '2px' });
            $icon.find('.swal2-icon-content').css({ fontSize: '1.5em' });
        }
    }).then(function (r) {
        if (!r.isConfirmed) return;
        var notes = $('#cnSwalCancelReason').val().trim();
        ajaxLoading(1);
        $.ajax({
            url    : global_services_url + 'api/invoices/cancelCreditNote',
            type   : 'POST',
            headers: { Authorization: 'Bearer ' + JwtToken },
            data   : { CreditNoteUID: uid, Notes: notes, orgId: parseInt(JwtData.Org.OrgUID, 10), userId: parseInt(JwtData.User.UserUID, 10), branchId: parseInt(JwtData.Org.BranchUID, 10) || 1 },
            success: function (resp) {
                ajaxLoading(0);
                if (resp.Error) { Swal.fire({ icon: 'error', text: resp.Message || 'Failed to cancel Credit Note.' }); return; }
                loadCreditNotes(_cnPageNo);
            },
            error: function () {
                ajaxLoading(0);
                Swal.fire({ icon: 'error', text: 'Request failed. Please try again.' });
            }
        });
    });
});

// ── Delete Credit Note ────────────────────────────────────────────────────────

$(document).on('click', '.cn-delete-btn', function () {
    var uid    = $(this).data('uid');
    var number = $(this).data('number');
    var amount = $(this).data('amount');
    var cur    = '<?php echo addslashes($JwtData->GenSettings->CurrenySymbol ?? '₹'); ?>';

    if (!confirm(
        'Delete Credit Note ' + number + '?\n\n' +
        'This will also delete the linked payment of ' + cur + ' ' + amount + ' and revert the customer balance.\n\n' +
        'This action cannot be undone.'
    )) return;

    var $item = $(this);
    ajaxLoading(1);
    $.ajax({
        url    : global_services_url + 'api/invoices/deleteCreditNote',
        type   : 'POST',
        headers: { Authorization: 'Bearer ' + JwtToken },
        data   : { CreditNoteUID: uid, orgId: parseInt(JwtData.Org.OrgUID, 10), userId: parseInt(JwtData.User.UserUID, 10), branchId: parseInt(JwtData.Org.BranchUID, 10) || 1 },
        success: function (resp) {
            ajaxLoading(0);
            if (resp.Error) { alert(resp.Message || 'Failed to delete Credit Note.'); return; }
            loadCreditNotes(_cnPageNo);
        },
        error: function () {
            ajaxLoading(0);
            alert('Request failed. Please try again.');
        }
    });
});

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
$(document).on('click', '.cnPagination .page-link', function (e) {
    e.preventDefault();
    var pg = $(this).data('page');
    if (pg) { _cnPageNo = parseInt(pg, 10); loadCreditNotes(_cnPageNo); }
});
</script>
