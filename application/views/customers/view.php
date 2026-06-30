<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php $this->load->view('common/header'); ?>

<div class="layout-wrapper layout-horizontal layout-content-navbar">
    <div class="layout-container">

        <?php $this->load->view('common/menu_view'); ?>

        <div class="layout-page">

            <div class="content-wrapper apex-content">
                <?php $this->load->view('common/apex/page_header', [
                    'pageTitle'       => $PageTitle       ?? 'Customers',
                    'pageDescription' => $PageDescription ?? '',
                ]); ?>
                <?php
                $s   = $CustStats ?? null;
                $cur = htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹');
                $dec = (int)($JwtData->GenSettings->DecimalPoints ?? 2);
                ?>

                <!-- ── Stats Strip ───────────────────────────────────────────── -->
                <div class="apex-stats-strip">
                    <a href="javascript:void(0);" class="apex-stat-item active" data-status="All" data-stat-filter="All" style="--stat-color:#db2777">
                        <div class="apex-stat-icon" style="background:#fce7f3"><i class="bx bxs-group" style="color:#db2777"></i></div>
                        <div class="apex-stat-body">
                            <div class="apex-stat-label">Total Customers</div>
                            <div class="apex-stat-bottom">
                                <span class="apex-stat-count cust-stat-total"><?php echo number_format((int)($s->TotalCount ?? 0)); ?></span>
                                <span class="apex-stat-amount">&nbsp;</span>
                            </div>
                        </div>
                    </a>
                    <a href="javascript:void(0);" class="apex-stat-item" data-status="Active" data-stat-filter="Active" style="--stat-color:#10b981">
                        <div class="apex-stat-icon" style="background:#dcfce7"><i class="bx bx-check-circle" style="color:#10b981"></i></div>
                        <div class="apex-stat-body">
                            <div class="apex-stat-label">Active</div>
                            <div class="apex-stat-bottom">
                                <span class="apex-stat-count cust-stat-active"><?php echo number_format((int)($s->ActiveCount ?? 0)); ?></span>
                                <span class="apex-stat-amount">&nbsp;</span>
                            </div>
                        </div>
                    </a>
                    <a href="javascript:void(0);" class="apex-stat-item" data-status="ToCollect" data-stat-filter="ToCollect" style="--stat-color:#3b82f6">
                        <div class="apex-stat-icon" style="background:#eff6ff"><i class="bx bx-arrow-to-bottom" style="color:#3b82f6"></i></div>
                        <div class="apex-stat-body">
                            <div class="apex-stat-label">To Collect</div>
                            <div class="apex-stat-bottom">
                                <span class="apex-stat-count cust-stat-tocollect"><?php echo number_format((int)($s->ToCollectCount ?? 0)); ?></span>
                                <span class="apex-stat-amount"><?php echo $cur . ' ' . number_format((float)($s->ToCollectAmount ?? 0), $dec); ?></span>
                            </div>
                        </div>
                    </a>
                    <a href="javascript:void(0);" class="apex-stat-item" data-status="ToPay" data-stat-filter="ToPay" style="--stat-color:#f97316">
                        <div class="apex-stat-icon" style="background:#fff7ed"><i class="bx bx-arrow-from-bottom" style="color:#f97316"></i></div>
                        <div class="apex-stat-body">
                            <div class="apex-stat-label">To Pay</div>
                            <div class="apex-stat-bottom">
                                <span class="apex-stat-count cust-stat-topay"><?php echo number_format((int)($s->ToPayCount ?? 0)); ?></span>
                                <span class="apex-stat-amount"><?php echo $cur . ' ' . number_format((float)($s->ToPayAmount ?? 0), $dec); ?></span>
                            </div>
                        </div>
                    </a>
                    <div class="apex-stat-item" style="--stat-color:#94a3b8;cursor:default;pointer-events:none">
                        <div class="apex-stat-icon" style="background:#f8fafc"><i class="bx bx-bar-chart-alt-2" style="color:#94a3b8"></i></div>
                        <div class="apex-stat-body">
                            <div class="apex-stat-label">Combined Stats</div>
                            <div class="apex-stat-bottom" style="gap:8px">
                                <span style="font-size:.72rem"><span class="cust-stat-month fw-bold"><?php echo number_format((int)($s->MonthCount ?? 0)); ?></span> Month</span>
                                <span style="font-size:.72rem"><span class="cust-stat-lastmonth fw-bold"><?php echo number_format((int)($s->LastMonthCount ?? 0)); ?></span> Last</span>
                                <span style="font-size:.72rem"><span class="cust-stat-fy fw-bold"><?php echo number_format((int)($s->FYCount ?? 0)); ?></span> FY</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="container-xxl flex-grow-1 py-3">

                    <?php $showUserBtn = is_array($OrgUsers) && count($OrgUsers) > 1; ?>

                    <!-- ── Main Card ── -->
                    <div class="card">

                        <!-- Filter Row -->
                        <div class="apex-filter-row">
                            <div class="r2k-search-wrap">
                                <i class="bx bx-search r2k-si"></i>
                                <input type="text" id="SearchDetails" placeholder="Name, mobile, GSTIN...">
                                <i class="bx bx-x r2k-clear d-none" id="clearSearch"></i>
                            </div>
                            <?php if (!empty($Tags)): ?>
                            <a href="javascript:void(0);" id="custTagFilterBtn" class="apex-filter-btn" title="Filter by Tag">
                                <i class="bx bx-purchase-tag"></i>Tags
                            </a>
                            <?php endif; ?>
                            <?php if (!empty($CustomerTypeList)): ?>
                            <a href="javascript:void(0);" id="custTypeFilterBtn" class="apex-filter-btn" title="Filter by Customer Type">
                                <i class="bx bx-user-pin me-1"></i>Customer Type
                            </a>
                            <?php endif; ?>
                            <?php if ($showUserBtn): ?>
                            <a href="javascript:void(0);" id="custUserFilterBtn" class="apex-filter-btn cust-only-ctrl" title="Filter by User">
                                <i class="bx bx-user"></i>Updated By
                            </a>
                            <?php endif; ?>
                            <a href="javascript:void(0);" id="custStatusFilterBtn" class="apex-filter-btn cust-only-ctrl" title="Filter by Status">
                                <i class="bx bx-toggle-left me-1"></i>Status
                            </a>
                            <!-- Group-only filter chip -->
                            <a href="javascript:void(0);" id="grpTypeFilterBtn" class="apex-filter-btn grp-only-ctrl d-none" title="Filter by Group Type">
                                <i class="bx bx-category"></i> Group Type
                            </a>
                            <div class="apex-filter-spacer"></div>
                            <a href="javascript:void(0);" class="apex-icon-btn PageRefresh" title="Refresh"><i class="bx bx-refresh"></i></a>
                            <!-- Customer-only controls -->
                            <a href="javascript:void(0);" class="apex-icon-btn cust-only-ctrl" id="btnSyncCustomersCache" title="Sync Cache"><i class="bx bx-planet"></i></a>
                            <div class="btn-group d-none cust-only-ctrl" id="ActionsDD-Div">
                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="actionsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bx bx-slider-alt"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="actionsDropdown">
                                    <li class="d-none" id="CloneOption"><a class="dropdown-item" href="javascript:void(0);" id="btnClone"><i class="bx bx-duplicate me-1"></i> Clone</a></li>
                                    <li class="d-none" id="DeleteOption"><a class="dropdown-item text-danger" href="javascript:void(0);" id="btnDelete"><i class="bx bx-trash me-1"></i> Delete</a></li>
                                    <li class="d-none" id="BulkSmsOption"><a class="dropdown-item" href="javascript:void(0);" id="btnBulkSms"><i class="bx bx-message-rounded me-1 text-info"></i> Send SMS</a></li>
                                    <li class="d-none" id="BulkEmailOption"><a class="dropdown-item" href="javascript:void(0);" id="btnBulkEmail"><i class="bx bx-envelope me-1 text-primary"></i> Send Email</a></li>
                                </ul>
                            </div>
                            <div class="dropdown cust-only-ctrl">
                                <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bx bx-export me-1"></i>Export
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="javascript:void(0);" onclick="custExport('Print')"><i class="bx bx-printer me-1"></i>Print</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);" onclick="custExport('CSV')"><i class="bx bx-file me-1"></i>CSV</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);" onclick="custExport('Excel')"><i class="bx bxs-file-export me-1"></i>Excel</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);" onclick="custExport('Pdf')"><i class="bx bxs-file-pdf me-1"></i>PDF</a></li>
                                </ul>
                            </div>
                            <a href="javascript:void(0);" class="btn btn-primary cust-only-ctrl" id="btnCreateCustomerHeader">
                                <i class="bx bx-plus me-1"></i>New Customer
                            </a>
                            <!-- Group-only button -->
                            <button type="button" id="btnNewGroup" class="btn btn-primary grp-only-ctrl d-none">
                                <i class="bx bx-plus me-1"></i>New Group
                            </button>
                        </div>

                        <!-- Tabs Row -->
                        <div class="apex-tabs-row">
                            <ul class="nav trans-status-tabs" id="custStatusTabs" role="tablist">
                                <li class="nav-item"><a class="nav-link active cust-tab" data-status="All" href="javascript:void(0);"><i class="bx bxs-group me-1" style="font-size:.85rem;"></i>All <span class="trans-tab-count"><?php echo $CustStats->TotalCount ?? 0; ?></span></a></li>
                                <li class="nav-item">
                                    <a class="nav-link grp-view-tab" href="javascript:void(0);" id="groupsViewTab">
                                        <i class="bx bxs-layer me-1" style="font-size:.85rem;"></i>Groups
                                        <span class="trans-tab-count d-none" id="grpTabCount"></span>
                                    </a>
                                </li>
                                <!-- Group stats — visible only in groups mode -->
                                <li id="grpTabStats" class="d-none align-items-center gap-3 ms-auto pe-2" style="font-size:.81rem;list-style:none;">
                                    <span class="text-muted">Total: <strong class="cg-stat-total text-body">—</strong></span>
                                    <span class="text-muted">Active: <strong class="cg-stat-active text-success">—</strong></span>
                                    <span class="text-muted">Inactive: <strong class="cg-stat-inactive text-danger">—</strong></span>
                                    <span class="text-muted">Members: <strong class="cg-stat-members text-body">—</strong></span>
                                </li>
                            </ul>
                        </div>

                        <!-- Customer table section -->
                        <div id="custTableSection">
                        <div class="table-responsive">
                            <table class="table trans-table MainviewTable mb-0" id="CustomersTable">
                                <thead class="r2k-thead">
                                    <tr>
                                        <th class="th-chk" style="width:36px">
                                            <div class="form-check mb-0">
                                                <input class="form-check-input table-chkbox customerHeaderCheck" type="checkbox">
                                            </div>
                                        </th>
                                        <th class="th-sno <?php echo $JwtData->GenSettings->SerialNoDisplay == 1 ? '' : 'd-none'; ?>" style="width:44px">#</th>
                                        <th class="cust-name-sortable cursor-pointer" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Click for ascending order">
                                            <span class="sort-label">Customer <i class="bx bx-sort sort-icon ms-1"></i></span>
                                        </th>
                                        <th class="cust-area-sortable cursor-pointer" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Click for ascending order">Area <i class="bx bx-sort sort-icon ms-1"></i></th>
                                        <th>Mobile</th>
                                        <th>GSTIN / Company</th>
                                        <th class="cust-bal-sortable cursor-pointer" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Click for ascending order">Balance <i class="bx bx-sort sort-icon ms-1"></i></th>
                                        <th>Customer Type</th>
                                        <th>Last Updated</th>
                                        <th class="th-act" style="width:80px">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="r2k-tbody table-border-bottom-0">
                                    <?php echo $ModRowData; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination (static — inside card) -->
                        <hr class="my-0">
                        <div class="row mx-3 my-2 justify-content-between align-items-center CustomersPagination" id="CustomersPagination">
                            <?php echo $ModPagination; ?>
                        </div>
                        </div><!-- /#custTableSection -->

                        <!-- Groups table section (hidden by default) -->
                        <div id="grpTableSection" style="display:none;">
                            <div class="table-responsive">
                                <table class="table trans-table MainviewTable mb-0" id="GroupsTable">
                                    <thead class="r2k-thead">
                                        <tr>
                                            <th class="th-chk" style="width:36px">
                                                <div class="form-check mb-0">
                                                    <input class="form-check-input table-chkbox grpHeaderCheck" type="checkbox">
                                                </div>
                                            </th>
                                            <th>Group Name</th>
                                            <th style="width:110px;">Code</th>
                                            <th style="width:140px;">Type</th>
                                            <th class="text-center" style="width:90px;">Members</th>
                                            <th style="width:150px;">Contact</th>
                                            <th class="text-end" style="width:140px;">Outstanding</th>
                                            <th style="width:90px;">Status</th>
                                            <th style="width:100px;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="GroupsTableBody">
                                        <tr><td colspan="9" class="text-center py-4 text-muted">Loading groups…</td></tr>
                                    </tbody>
                                </table>
                            </div>
                            <hr class="my-0">
                            <div class="row mx-3 my-2 justify-content-between align-items-center" id="GroupsPagination"></div>
                        </div><!-- /#grpTableSection -->

                    </div>

                    <!-- Sticky pagination bar — mirrors static one, fades in when static scrolls out of view -->
                    <div class="card mb-0 cust-sticky-pag" id="custStickyPagination" style="display:none;">
                        <div class="card-body p-0">
                            <div class="row mx-3 my-2 justify-content-between align-items-center CustomersPagination"></div>
                        </div>
                    </div>

                </div>
            </div>

            <?php $this->load->view('common/imagepreview_modal'); ?>
            <?php $this->load->view('common/settings_modal'); ?>
            <?php $this->load->view('common/modals/send_communication'); ?>

            <?php $this->load->view('common/modals/customer_form'); ?>
            <?php $this->load->view('common/modals/customer_group_form'); ?>
            <?php $this->load->view('common/footer_desc'); ?>
        </div>

    </div>
</div>

<!-- Filter panels (body-level to avoid overflow clipping) -->
<?php if (!empty($Tags)): ?>
<?php $this->load->view('common/filter_panels/checklist_filter', [
    'ChecklistFilterConfig' => [
        'id'                => 'custTagFilterBox',
        'triggerId'         => 'custTagFilterBtn',
        'checkClass'        => 'cust-tag-chk',
        'title'             => 'Tag Filter',
        'icon'              => 'bx-purchase-tag',
        'searchPlaceholder' => 'Search tags...',
        'items'             => array_map(function ($t) { return ['value' => $t, 'label' => $t]; }, $Tags),
    ],
]); ?>
<?php endif; ?>
<?php if (!empty($CustomerTypeList)): ?>
<?php $this->load->view('common/filter_panels/checklist_filter', [
    'ChecklistFilterConfig' => [
        'id'                => 'custTypeFilterBox',
        'triggerId'         => 'custTypeFilterBtn',
        'checkClass'        => 'cust-type-chk',
        'title'             => 'Customer Type',
        'icon'              => 'bx-user-pin',
        'searchPlaceholder' => 'Search types...',
        'items'             => array_map(function ($t) { return ['value' => $t->CustomerTypeUID, 'label' => $t->TypeName]; }, $CustomerTypeList),
    ],
]); ?>
<?php endif; ?>
<?php if ($showUserBtn): ?>
<?php $this->load->view('common/partials/col_user_filter_box', [
    'ColUserFilterConfig' => [
        'id'         => 'custUserFilterBox',
        'triggerId'  => 'custUserFilterBtn',
        'checkClass' => 'cust-user-chk',
        'title'      => 'Updated By',
        'OrgUsers'   => $OrgUsers,
    ],
]); ?>
<?php endif; ?>
<?php $this->load->view('common/filter_panels/checklist_filter', [
    'ChecklistFilterConfig' => [
        'id'                => 'grpTypeFilterBox',
        'triggerId'         => 'grpTypeFilterBtn',
        'checkClass'        => 'grp-type-chk',
        'title'             => 'Group Type',
        'icon'              => 'bx-category',
        'searchPlaceholder' => 'Search types...',
        'items'             => array_map(function ($t) { return ['value' => $t, 'label' => $t]; }, $GroupTypes ?? []),
    ],
]); ?>
<?php $this->load->view('common/filter_panels/checklist_filter', [
    'ChecklistFilterConfig' => [
        'id'                => 'custStatusFilterBox',
        'triggerId'         => 'custStatusFilterBtn',
        'checkClass'        => 'cust-status-chk',
        'title'             => 'Status',
        'icon'              => 'bx-toggle-left',
        'searchPlaceholder' => 'Search...',
        'items'             => [
            ['value' => '1', 'label' => 'Active'],
            ['value' => '0', 'label' => 'Inactive'],
        ],
    ],
]); ?>

<?php $this->load->view('common/footer'); ?>

<script src="<?php echo _assetV('/js/common/address.js'); ?>"></script>
<script src="<?php echo _assetV('/js/common/bankdetails.js'); ?>"></script>
<script src="<?php echo _assetV('/js/common/gstin_fetch.js'); ?>"></script>
<link rel="stylesheet" href="<?php echo _assetV('/assets/vendor/css/attachments.css'); ?>">
<script src="<?php echo _assetV('/js/common/attachments.js'); ?>"></script>
<script src="<?php echo _assetV('/js/common/customer_form.js'); ?>"></script>
<script src="<?php echo _assetV('/js/common/customer_group_form.js'); ?>"></script>
<script src="<?php echo _assetV('/js/transactions/col_filter.js'); ?>"></script>
<script src="<?php echo _assetV('/js/customers.js'); ?>"></script>
<script src="<?php echo _assetV('/js/common/pagecheckbox.js'); ?>"></script>
<script src="/js/common/communication.js"></script>

<script>
let ModuleId = 2;
const ModuleTable  = '#CustomersTable';
const ModulePag    = '.CustomersPagination';
const ModuleHeader = '.customerHeaderCheck';
const ModuleRow    = '.customerCheck';
const ModuleFileName  = 'Customer_Data';
const ModuleSheetName = 'Customer';
const previewName     = 'Customer Details';
let nameSortState = 0;
let balSortState  = 0;
let areaSortState = 0;
var StateInfo = [];
var CityInfo  = [];
var OrgCountryISO2 = <?php echo json_encode($JwtData->Org->OrgCISO2 ?? 'IN'); ?>;
var CustShowUserFilter = <?php echo $showUserBtn ? 'true' : 'false'; ?>;

$(function () {
    'use strict';

    // ── Groups mode state ──
    var _inGroupsMode = false;
    var _grpPageNo    = 1;
    var _grpFilter    = {};
    var _grpLoaded    = false;

    $('#SearchDetails').val('');
    $(ModuleRow).prop('checked', false).trigger('change');

    // Auto-show/hide the Actions gear button based on whether any option is visible
    (function () {
        var $dd = $('#ActionsDD-Div');
        function syncDD() {
            var anyVisible = $('#CloneOption, #DeleteOption, #BulkSmsOption, #BulkEmailOption')
                .filter(function () { return !$(this).hasClass('d-none'); }).length > 0;
            $dd.toggleClass('d-none', !anyVisible);
        }
        var observer = new MutationObserver(syncDD);
        ['CloneOption', 'DeleteOption', 'BulkSmsOption', 'BulkEmailOption'].forEach(function (id) {
            var el = document.getElementById(id);
            if (el) observer.observe(el, { attributes: true, attributeFilter: ['class'] });
        });
    })();

    basePaginationFunc(ModulePag, getCustomersDetails);
    baseRefreshPageFunc('.PageRefresh', getCustomersDetails);
    basePageHeaderFunc(ModuleHeader, ModuleTable, ModuleRow);

    // ── Sync customers to Upstash cache ───────────────────────────────────────
    $(document).on('click', '#btnSyncCustomersCache', function () {
        var $btn = $(this);
        $btn.find('i').removeClass('bx-planet').addClass('bx-loader-alt bx-spin');
        $.ajax({
            url    : '/customers/syncCustomersCache',
            method : 'POST',
            data   : { [CsrfName]: CsrfToken },
            success: function (resp) {
                CsrfToken = resp.NewCsrfToken || CsrfToken;
                $btn.find('i').removeClass('bx-loader-alt bx-spin').addClass('bx-planet');
                if (resp.Error) {
                    showToastNotification(resp.Message, 'error');
                } else {
                    showToastNotification(resp.Message, 'success');
                }
            },
            error: function () {
                $btn.find('i').removeClass('bx-loader-alt bx-spin').addClass('bx-planet');
                showToastNotification('Sync failed. Please try again.', 'error');
            }
        });
    });
    // ────────────────────────────────────────────────────────────────────────

    // Header "New Customer" button
    $(document).on('click', '#btnCreateCustomerHeader', function () {
        CustomerForm.open('add', null, { onSaveSuccess: _custPageSaveSuccess });
    });

    // ── Sticky pagination ──
    var $staticPag = $('#CustomersPagination');
    var $stickyPag = $('#custStickyPagination');

    function syncStickyPagination() {
        // Copy current pagination HTML into the sticky bar so page numbers stay in sync
        $stickyPag.find('.CustomersPagination').html($staticPag.html());
    }

    function toggleStickyPagination() {
        if (_inGroupsMode) { $stickyPag.stop(true, true).hide(); return; }
        if (!$staticPag.length) return;
        var rect = $staticPag[0].getBoundingClientRect();
        var windowHeight = $(window).height();
        var staticVisible = rect.top < windowHeight && rect.bottom > 0;
        if (staticVisible) {
            $stickyPag.stop(true, true).fadeOut(150);
        } else {
            syncStickyPagination();
            $stickyPag.stop(true, true).fadeIn(150);
        }
    }

    $(window).on('scroll resize', toggleStickyPagination);
    toggleStickyPagination();

    // ── Stat card clicks ──
    $(document).on('click', '.apex-stat-item[data-stat-filter]', function () {
        var filterType = $(this).data('stat-filter');
        delete Filter['IsActive'];
        delete Filter['BalanceType'];
        $('.apex-stat-item').removeClass('active');
        $(this).addClass('active');

        if (filterType === 'Active') {
            Filter['IsActive'] = 1;
            if (custStatusFilter) custStatusFilter.setState(['1']);
        } else if (filterType === 'ToCollect') {
            Filter['BalanceType'] = 'Debit';
            if (custStatusFilter) custStatusFilter.reset();
        } else if (filterType === 'ToPay') {
            Filter['BalanceType'] = 'Credit';
            if (custStatusFilter) custStatusFilter.reset();
        } else {
            if (custStatusFilter) custStatusFilter.reset();
        }

        PageNo = 0;
        getCustomersDetails(PageNo, RowLimit, Filter);
    });

    // ── All tab click (also exits groups mode) ──
    $(document).on('click', '.cust-tab', function (e) {
        e.preventDefault();

        if (_inGroupsMode) {
            // Switching Groups → All: restore UI, do NOT reload (data already in table)
            _inGroupsMode = false;
            $('.cust-only-ctrl').removeClass('d-none');
            $('.grp-only-ctrl').addClass('d-none');
            $('#SearchDetails').attr('placeholder', 'Name, mobile, GSTIN...').val('');
            delete _grpFilter['SearchAllData'];
            $('#clearSearch').addClass('d-none');
            $('#custTableSection').show();
            $('#grpTableSection').hide();
            $('#grpTabStats').removeClass('d-flex').addClass('d-none');
            $('.grp-view-tab').removeClass('active');
            $('.cust-tab').removeClass('active');
            $(this).addClass('active');
            toggleStickyPagination();
            return;
        }

        // Already in customer mode — reset filters and reload
        $('.cust-tab').removeClass('active');
        $(this).addClass('active');
        $('.apex-stat-item').removeClass('active');
        $('.apex-stat-item[data-stat-filter="All"]').addClass('active');
        delete Filter['IsActive'];
        delete Filter['BalanceType'];
        if (custStatusFilter) custStatusFilter.reset();
        PageNo = 0;
        getCustomersDetails(PageNo, RowLimit, Filter);
    });

    // ── Search ──
    $('#SearchDetails').on('input', inputDelay(function () {
        var val = $.trim($(this).val());
        $('#clearSearch').toggleClass('d-none', !val);
        if (_inGroupsMode) {
            delete _grpFilter['SearchAllData'];
            if (val.length >= 3) _grpFilter['SearchAllData'] = val;
            if (val.length === 0 || val.length >= 3) { _grpReload(1); }
            return;
        }
        delete Filter['SearchAllData'];
        if (val.length >= 3) Filter['SearchAllData'] = val;
        if (val.length === 0 || val.length >= 3) { PageNo = 0; getCustomersDetails(PageNo, RowLimit, Filter); }
    }, 1500));

    $('#clearSearch').on('click', function () {
        $('#SearchDetails').val('');
        $(this).addClass('d-none');
        if (_inGroupsMode) {
            delete _grpFilter['SearchAllData'];
            _grpReload(1);
            return;
        }
        delete Filter['SearchAllData'];
        PageNo = 0; getCustomersDetails(PageNo, RowLimit, Filter);
    });

    // ── Row checkbox ──
    $(document).on('change', ModuleRow, function () {
        $(this).closest('tr').toggleClass('row-sel', $(this).is(':checked'));
    });
    $(document).on('click', ModuleRow, function () {
        onClickOfCheckbox($(this), ModuleTable, ModuleHeader, ModuleRow);
        $('#CloneOption').addClass('d-none');
        if (SelectedUIDs.length === 1) $('#CloneOption').removeClass('d-none');
        MultipleDeleteOption();
    });
    $('#btnClone').on('click', function (e) {
        e.preventDefault();
        if (SelectedUIDs.length === 1) CustomerForm.open('clone', SelectedUIDs[0], { onSaveSuccess: _custPageSaveSuccess });
    });

    // ── Delete single ──
    $(document).on('click', '.DeleteCustomer', function (e) {
        e.preventDefault();
        var id = $(this).data('customeruid');
        if (!id) return;
        Swal.fire({
            title: 'Delete this customer?',
            text: 'This action cannot be undone.',
            icon: 'warning', showCancelButton: true,
            confirmButtonColor: '#dc2626', cancelButtonColor: '#64748b',
            confirmButtonText: 'Yes, delete',
        }).then(function (r) { if (r.isConfirmed) deleteCustomer(id); });
    });

    // ── Delete bulk ──
    $('#btnDelete').on('click', function (e) {
        e.preventDefault();
        if (!SelectedUIDs.length) return;
        Swal.fire({
            title: 'Delete ' + SelectedUIDs.length + ' customer(s)?',
            text: 'This action cannot be undone.',
            icon: 'warning', showCancelButton: true,
            confirmButtonColor: '#dc2626', cancelButtonColor: '#64748b',
            confirmButtonText: 'Yes, delete all',
        }).then(function (r) { if (r.isConfirmed) deleteMultipleCustomers(); });
    });

    // ── Name sort ──
    $(document).on('click', '.cust-name-sortable', function (e) {
        e.preventDefault();
        nameSortState = (nameSortState + 1) % 3;
        // reset other sort columns
        if (nameSortState !== 0) {
            areaSortState = 0; balSortState = 0;
            delete Filter['AreaSorting']; delete Filter['BalanceSorting'];
            $('.cust-area-sortable .sort-icon, .cust-bal-sortable .sort-icon').removeClass('bx-up-arrow-alt bx-down-arrow-alt text-primary').addClass('bx-sort');
            $('.cust-area-sortable').attr('data-bs-title', 'Click for ascending order');
            $('.cust-bal-sortable').attr('data-bs-title', 'Click for ascending order');
        }
        var icon = $(this).find('.sort-icon');
        icon.removeClass('bx-sort bx-up-arrow-alt bx-down-arrow-alt text-primary');
        if (nameSortState === 1) {
            icon.addClass('bx-up-arrow-alt text-primary');
            $(this).attr('data-bs-title', 'Click for descending order');
            Filter['NameSorting'] = 1;
        } else if (nameSortState === 2) {
            icon.addClass('bx-down-arrow-alt text-primary');
            $(this).attr('data-bs-title', 'Click to remove sorting');
            Filter['NameSorting'] = 2;
        } else {
            icon.addClass('bx-sort');
            $(this).attr('data-bs-title', 'Click for ascending order');
            delete Filter['NameSorting'];
        }
        var _tt = bootstrap.Tooltip.getInstance(this); if (_tt) { _tt.hide(); _tt.dispose(); } new bootstrap.Tooltip(this, { container: 'body', trigger: 'hover' });
        PageNo = 0; getCustomersDetails(PageNo, RowLimit, Filter);
    });

    // ── Balance sort ──
    $(document).on('click', '.cust-bal-sortable', function (e) {
        e.preventDefault();
        balSortState = (balSortState + 1) % 3;
        // reset other sort columns
        if (balSortState !== 0) {
            nameSortState = 0; areaSortState = 0;
            delete Filter['NameSorting']; delete Filter['AreaSorting'];
            $('.cust-name-sortable .sort-icon, .cust-area-sortable .sort-icon').removeClass('bx-up-arrow-alt bx-down-arrow-alt text-primary').addClass('bx-sort');
            $('.cust-name-sortable').attr('data-bs-title', 'Click for ascending order');
            $('.cust-area-sortable').attr('data-bs-title', 'Click for ascending order');
        }
        var icon = $(this).find('.sort-icon');
        icon.removeClass('bx-sort bx-up-arrow-alt bx-down-arrow-alt text-primary');
        if (balSortState === 1) {
            icon.addClass('bx-up-arrow-alt text-primary');
            $(this).attr('data-bs-title', 'Click for descending order');
            Filter['BalanceSorting'] = 1;
        } else if (balSortState === 2) {
            icon.addClass('bx-down-arrow-alt text-primary');
            $(this).attr('data-bs-title', 'Click to remove sorting');
            Filter['BalanceSorting'] = 2;
        } else {
            icon.addClass('bx-sort');
            $(this).attr('data-bs-title', 'Click for ascending order');
            delete Filter['BalanceSorting'];
        }
        var _tt2 = bootstrap.Tooltip.getInstance(this); if (_tt2) _tt2.dispose(); new bootstrap.Tooltip(this);
        PageNo = 0; getCustomersDetails(PageNo, RowLimit, Filter);
    });

    // ── Area sort ──
    $(document).on('click', '.cust-area-sortable', function (e) {
        e.preventDefault();
        areaSortState = (areaSortState + 1) % 3;
        // reset other sort columns
        if (areaSortState !== 0) {
            nameSortState = 0; balSortState = 0;
            delete Filter['NameSorting']; delete Filter['BalanceSorting'];
            $('.cust-name-sortable .sort-icon, .cust-bal-sortable .sort-icon').removeClass('bx-up-arrow-alt bx-down-arrow-alt text-primary').addClass('bx-sort');
            $('.cust-name-sortable').attr('data-bs-title', 'Click for ascending order');
            $('.cust-bal-sortable').attr('data-bs-title', 'Click for ascending order');
        }
        var icon = $(this).find('.sort-icon');
        icon.removeClass('bx-sort bx-up-arrow-alt bx-down-arrow-alt text-primary');
        if (areaSortState === 1) {
            icon.addClass('bx-up-arrow-alt text-primary');
            $(this).attr('data-bs-title', 'Click for descending order');
            Filter['AreaSorting'] = 1;
        } else if (areaSortState === 2) {
            icon.addClass('bx-down-arrow-alt text-primary');
            $(this).attr('data-bs-title', 'Click to remove sorting');
            Filter['AreaSorting'] = 2;
        } else {
            icon.addClass('bx-sort');
            $(this).attr('data-bs-title', 'Click for ascending order');
            delete Filter['AreaSorting'];
        }
        var _tt3 = bootstrap.Tooltip.getInstance(this); if (_tt3) _tt3.dispose(); new bootstrap.Tooltip(this);
        PageNo = 0; getCustomersDetails(PageNo, RowLimit, Filter);
    });

    // ── Tag filter ──
    <?php if (!empty($Tags)): ?>
    var custTagFilter = new TransColFilter({
        boxId       : 'custTagFilterBox',
        triggerId   : 'custTagFilterBtn',
        filterKey   : 'Tags',
        activeClass : 'has-filter',
        onApply     : function () {
            var state = custTagFilter.getState();
            if (state.Tags && state.Tags.length) Filter.Tags = state.Tags;
            else delete Filter.Tags;
            PageNo = 0; getCustomersDetails(PageNo, RowLimit, Filter);
        }
    });
    <?php endif; ?>

    // ── Customer Type filter ──
    <?php if (!empty($CustomerTypeList)): ?>
    var custTypeFilter = new TransColFilter({
        boxId       : 'custTypeFilterBox',
        triggerId   : 'custTypeFilterBtn',
        filterKey   : 'CustomerTypeUIDs',
        activeClass : 'has-filter',
        onApply     : function () {
            var state = custTypeFilter.getState();
            if (state.CustomerTypeUIDs && state.CustomerTypeUIDs.length) Filter.CustomerTypeUIDs = state.CustomerTypeUIDs;
            else delete Filter.CustomerTypeUIDs;
            PageNo = 0; getCustomersDetails(PageNo, RowLimit, Filter);
        }
    });
    <?php endif; ?>

    // ── Status toggle ──
    $(document).on('click', '.cust-status-toggle', function (e) {
        e.preventDefault();
        var $btn = $(this);
        var uid = $btn.data('uid');
        var newStatus = $btn.data('newstatus');
        var label = newStatus == 1 ? 'Active' : 'In-Active';
        Swal.fire({
            title: 'Change status to ' + label + '?',
            icon: 'question', showCancelButton: true,
            confirmButtonColor: '#0d6efd', cancelButtonColor: '#64748b',
            confirmButtonText: 'Yes, change it',
        }).then(function (r) {
            if (!r.isConfirmed) return;
            toggleCustomerStatus(uid, newStatus);
        });
    });

    // ── User filter ──
    if (CustShowUserFilter) {
        var custUserFilter = new TransColFilter({
            boxId       : 'custUserFilterBox',
            triggerId   : 'custUserFilterBtn',
            filterKey   : 'UpdatedByUIDs',
            activeClass : 'has-filter',
            onApply     : function () {
                var state = custUserFilter.getState();
                if (state.UpdatedByUIDs && state.UpdatedByUIDs.length) Filter.UpdatedByUIDs = state.UpdatedByUIDs;
                else delete Filter.UpdatedByUIDs;
                PageNo = 0;
                getCustomersDetails(PageNo, RowLimit, Filter);
            }
        });
    }

    // ── Status filter ──
    var custStatusFilter = new TransColFilter({
        boxId      : 'custStatusFilterBox',
        triggerId  : 'custStatusFilterBtn',
        filterKey  : 'IsActive',
        activeClass: 'has-filter',
        onApply    : function () {
            var state = custStatusFilter.getState();
            delete Filter['IsActive'];
            if (state.IsActive && state.IsActive.length === 1) {
                Filter['IsActive'] = parseInt(state.IsActive[0], 10);
            }
            PageNo = 0; getCustomersDetails(PageNo, RowLimit, Filter);
        }
    });

    // ══════════════════════════════════════════════════════════════
    // GROUPS TAB
    // ══════════════════════════════════════════════════════════════

    // ── Groups tab click ──
    $(document).on('click', '.grp-view-tab', function (e) {
        e.preventDefault();
        if (_inGroupsMode) return;
        _inGroupsMode = true;
        toggleStickyPagination();
        $('.cust-tab').removeClass('active');
        $('.grp-view-tab').addClass('active');
        $('.cust-only-ctrl').addClass('d-none');
        $('.grp-only-ctrl').removeClass('d-none');
        $('#SearchDetails').attr('placeholder', 'Group name, code, type...');
        $('#custTableSection').hide();
        $('#grpTableSection').show();
        $('#grpTabStats').removeClass('d-none').addClass('d-flex');
        if (!_grpLoaded) { _grpLoaded = true; _grpReload(1); }
    });

    // ── Apply groups response data to DOM ──
    function _applyGrpData(res) {
        _grpPageNo = 1;
        $('#GroupsTableBody').html(res.RecordHtmlData);
        $('#GroupsPagination').html(res.Pagination);
        _updateGrpStats(res.Stats);
        var cnt = res.TotalCount || 0;
        $('#grpTabCount').text(cnt > 0 ? cnt : '').toggleClass('d-none', cnt === 0);

        // Refresh the Customer Group dropdown in the customer form so newly created/updated groups appear
        _refreshCustomerGroupDropdown();
    }

    function _refreshCustomerGroupDropdown() {
        $.get('/customers/getGroupsForDropdown', function (res) {
            if (res && !res.Error && res.Groups && res.Groups.length) {
                var $sel = $('#CM_GroupUID');
                var selected = $sel.val();
                $sel.find('option:not([value=""])').remove();
                $.each(res.Groups, function (_, g) {
                    $sel.append(new Option(g.GroupName, g.GroupUID, false, String(g.GroupUID) === String(selected)));
                });
            }
        });
    }

    // ── Groups AJAX reload ──
    function _grpReload(page) {
        _grpPageNo = page || 1;
        $('#GroupsTableBody').html('<tr><td colspan="9" class="text-center py-4"><span class="spinner-border spinner-border-sm text-primary" role="status"></span></td></tr>');
        $.ajax({
            url   : '/customers/getGroupsData/' + _grpPageNo,
            method: 'POST',
            data  : { Filter: _grpFilter, [CsrfName]: CsrfToken },
            success: function (res) {
                CsrfToken = res.NewCsrfToken || CsrfToken;
                if (res.Error) { showToastNotification(res.Message, 'error'); return; }
                $('#GroupsTableBody').html(res.RecordHtmlData);
                $('#GroupsPagination').html(res.Pagination);
                _updateGrpStats(res.Stats);
                var cnt = res.TotalCount || 0;
                $('#grpTabCount').text(cnt > 0 ? cnt : '').toggleClass('d-none', cnt === 0);
            },
            error: function () { AjaxLoading = 1; }
        });
    }

    // ── Groups stats update ──
    function _updateGrpStats(s) {
        if (!s) return;
        $('.cg-stat-total').text(s.TotalCount   || 0);
        $('.cg-stat-active').text(s.ActiveCount  || 0);
        $('.cg-stat-inactive').text(s.InactiveCount || 0);
        $('.cg-stat-members').text(s.TotalMembers || 0);
    }

    // ── Groups pagination ──
    $(document).on('click', '#GroupsPagination .pagination .page-link', function (e) {
        e.preventDefault();
        var pg = parseInt($(this).data('page'), 10);
        if (!isNaN(pg) && pg !== _grpPageNo) _grpReload(pg);
    });

    // ── Groups header checkbox ──
    $(document).on('change', '.grpHeaderCheck', function () {
        $('#GroupsTable tbody .grpRowCheck').prop('checked', $(this).is(':checked'))
            .closest('tr').toggleClass('row-sel', $(this).is(':checked'));
    });
    $(document).on('change', '.grpRowCheck', function () {
        $(this).closest('tr').toggleClass('row-sel', $(this).is(':checked'));
        var total   = $('#GroupsTable tbody .grpRowCheck').length;
        var checked = $('#GroupsTable tbody .grpRowCheck:checked').length;
        $('.grpHeaderCheck').prop('checked', total > 0 && checked === total)
                            .prop('indeterminate', checked > 0 && checked < total);
    });

    // ── Group status toggle ──
    $(document).on('click', '.grp-status-toggle', function (e) {
        e.preventDefault();
        var $btn      = $(this);
        var uid       = $btn.data('uid');
        var newStatus = $btn.data('newstatus');
        var label     = newStatus == 1 ? 'Active' : 'Inactive';
        Swal.fire({
            title: 'Change group status to ' + label + '?',
            icon: 'question', showCancelButton: true,
            confirmButtonColor: '#0d6efd', cancelButtonColor: '#64748b',
            confirmButtonText: 'Yes, change it',
        }).then(function (r) {
            if (!r.isConfirmed) return;
            $('#GroupsTableBody').html('<tr><td colspan="9" class="text-center py-4"><span class="spinner-border spinner-border-sm text-primary" role="status"></span></td></tr>');
            AjaxLoading = 0;
            $.ajax({
                url   : '/customers/toggleGroupStatus',
                method: 'POST',
                data  : { GroupUID: uid, IsActive: newStatus, [CsrfName]: CsrfToken },
                success: function (res) {
                    CsrfToken = res.NewCsrfToken || CsrfToken;
                    if (res.Error) { showToastNotification(res.Message, 'error'); return; }
                    showToastNotification(res.Message, 'success');
                    $('#GroupsTableBody').html(res.RecordHtmlData);
                    $('#GroupsPagination').html(res.Pagination);
                    _updateGrpStats(res.Stats);
                },
                error: function () { showToastNotification('Failed to update status.', 'error'); }
            });
        });
    });

    // ── Group delete ──
    $(document).on('click', '.grp-delete-btn', function (e) {
        e.preventDefault();
        var uid = $(this).data('uid');
        if (!uid) return;
        Swal.fire({
            title: 'Delete this group?',
            text : 'Members will be unlinked. This cannot be undone.',
            icon : 'warning', showCancelButton: true,
            confirmButtonColor: '#dc2626', cancelButtonColor: '#64748b',
            confirmButtonText : 'Yes, delete',
        }).then(function (r) {
            if (!r.isConfirmed) return;
            $('#GroupsTableBody').html('<tr><td colspan="9" class="text-center py-4"><span class="spinner-border spinner-border-sm text-danger" role="status"></span></td></tr>');
            AjaxLoading = 0;
            $.ajax({
                url   : '/customers/deleteGroup',
                method: 'POST',
                data  : { GroupUID: uid, [CsrfName]: CsrfToken },
                success: function (res) {
                    CsrfToken = res.NewCsrfToken || CsrfToken;
                    if (res.Error) { showToastNotification(res.Message, 'error'); return; }
                    showToastNotification(res.Message, 'success');
                    $('#GroupsTableBody').html(res.RecordHtmlData);
                    $('#GroupsPagination').html(res.Pagination);
                    _updateGrpStats(res.Stats);
                    var cnt = res.TotalCount || 0;
                    $('#grpTabCount').text(cnt > 0 ? cnt : '').toggleClass('d-none', cnt === 0);
                },
                error: function () { showToastNotification('Delete failed.', 'error'); }
            });
        });
    });

    // ── New Group button (toolbar) ──
    $(document).on('click', '#btnNewGroup, .btn-new-group', function () {
        CustomerGroupForm.open('add', null, { onSaveSuccess: function (res) { _applyGrpData(res); } });
    });

    // ── Edit Group button (from groups list) ──
    $(document).on('click', '.grp-edit-btn', function () {
        var uid = parseInt($(this).data('uid'));
        if (!uid) return;
        CustomerGroupForm.open('edit', uid, { onSaveSuccess: function (res) { _applyGrpData(res); } });
    });

    // ── Group Type filter ──
    var GrpTypeFilter = new TransColFilter({
        boxId       : 'grpTypeFilterBox',
        triggerId   : 'grpTypeFilterBtn',
        filterKey   : 'GroupType',
        activeClass : 'has-filter',
        onApply     : function () {
            var state = GrpTypeFilter.getState();
            if (state.GroupType && state.GroupType.length) _grpFilter.GroupType = state.GroupType;
            else delete _grpFilter.GroupType;
            _grpReload(1);
        }
    });

});
</script>