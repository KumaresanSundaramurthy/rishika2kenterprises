<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php $this->load->view('common/header'); ?>

<!-- Layout wrapper -->
<div class="layout-wrapper layout-horizontal layout-content-navbar">
    <div class="layout-container">

        <?php $this->load->view('common/menu_view'); ?>

        <!-- Layout container -->
        <div class="layout-page">

            <!-- Content wrapper -->
            <div class="content-wrapper apex-content">
                <?php $this->load->view('common/apex/page_header', [
                    'pageTitle'       => $PageTitle       ?? 'Vendors',
                    'pageDescription' => $PageDescription ?? '',
                ]); ?>

                <?php
                $s   = $VendStats ?? null;
                $cur = htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹');
                $dec = (int)($JwtData->GenSettings->DecimalPoints ?? 2);
                ?>

                <!-- ── Stats Strip ───────────────────────────────────────────── -->
                <div class="apex-stats-strip">
                    <a href="javascript:void(0);" class="apex-stat-item active" data-status="All" data-stat-filter="All" style="--stat-color:#ca8a04">
                        <div class="apex-stat-icon" style="background:#fef9c3"><i class="bx bxs-store" style="color:#ca8a04"></i></div>
                        <div class="apex-stat-body">
                            <div class="apex-stat-label">Total Vendors</div>
                            <div class="apex-stat-bottom">
                                <span class="apex-stat-count vend-stat-total"><?php echo number_format((int)($s->TotalCount ?? 0)); ?></span>
                                <span class="apex-stat-amount">&nbsp;</span>
                            </div>
                        </div>
                    </a>
                    <a href="javascript:void(0);" class="apex-stat-item" data-status="Active" data-stat-filter="Active" style="--stat-color:#10b981">
                        <div class="apex-stat-icon" style="background:#dcfce7"><i class="bx bx-check-circle" style="color:#10b981"></i></div>
                        <div class="apex-stat-body">
                            <div class="apex-stat-label">Active</div>
                            <div class="apex-stat-bottom">
                                <span class="apex-stat-count vend-stat-active"><?php echo number_format((int)($s->ActiveCount ?? 0)); ?></span>
                                <span class="apex-stat-amount">&nbsp;</span>
                            </div>
                        </div>
                    </a>
                    <a href="javascript:void(0);" class="apex-stat-item" data-status="ToCollect" data-stat-filter="ToCollect" style="--stat-color:#3b82f6">
                        <div class="apex-stat-icon" style="background:#eff6ff"><i class="bx bx-arrow-to-bottom" style="color:#3b82f6"></i></div>
                        <div class="apex-stat-body">
                            <div class="apex-stat-label">To Collect</div>
                            <div class="apex-stat-bottom">
                                <span class="apex-stat-count vend-stat-tocollect"><?php echo number_format((int)($s->ToCollectCount ?? 0)); ?></span>
                                <span class="apex-stat-amount"><?php echo $cur . ' ' . number_format((float)($s->ToCollectAmount ?? 0), $dec); ?></span>
                            </div>
                        </div>
                    </a>
                    <a href="javascript:void(0);" class="apex-stat-item" data-status="ToPay" data-stat-filter="ToPay" style="--stat-color:#f97316">
                        <div class="apex-stat-icon" style="background:#fff7ed"><i class="bx bx-arrow-from-bottom" style="color:#f97316"></i></div>
                        <div class="apex-stat-body">
                            <div class="apex-stat-label">To Pay</div>
                            <div class="apex-stat-bottom">
                                <span class="apex-stat-count vend-stat-topay"><?php echo number_format((int)($s->ToPayCount ?? 0)); ?></span>
                                <span class="apex-stat-amount"><?php echo $cur . ' ' . number_format((float)($s->ToPayAmount ?? 0), $dec); ?></span>
                            </div>
                        </div>
                    </a>
                    <div class="apex-stat-item" style="--stat-color:#94a3b8;cursor:default;pointer-events:none">
                        <div class="apex-stat-icon" style="background:#f8fafc"><i class="bx bx-bar-chart-alt-2" style="color:#94a3b8"></i></div>
                        <div class="apex-stat-body">
                            <div class="apex-stat-label">Combined Stats</div>
                            <div class="apex-stat-bottom" style="gap:8px">
                                <span style="font-size:.72rem"><span class="vend-stat-month fw-bold"><?php echo number_format((int)($s->MonthCount ?? 0)); ?></span> Month</span>
                                <span style="font-size:.72rem"><span class="vend-stat-lastmonth fw-bold"><?php echo number_format((int)($s->LastMonthCount ?? 0)); ?></span> Last</span>
                                <span style="font-size:.72rem"><span class="vend-stat-fy fw-bold"><?php echo number_format((int)($s->FYCount ?? 0)); ?></span> FY</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="container-xxl flex-grow-1 py-3">

                    <?php $showUserBtn = isset($OrgUsers) && is_array($OrgUsers) && count($OrgUsers) > 1; ?>

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
                            <a href="javascript:void(0);" id="vendTagFilter" class="apex-filter-btn vend-only-ctrl" title="Filter by Tag"><i class="bx bx-purchase-tag me-1"></i>Tag</a>
                            <?php endif; ?>
                            <a href="javascript:void(0);" id="vendStatusFilterBtn" class="apex-filter-btn vend-only-ctrl" title="Filter by Status"><i class="bx bx-toggle-left me-1"></i>Status</a>
                            <?php if ($showUserBtn): ?>
                            <a href="javascript:void(0);" id="vendUserFilterBtn" class="apex-filter-btn vend-only-ctrl" title="Filter by User"><i class="bx bx-user me-1"></i>Updated By</a>
                            <?php endif; ?>
                            <!-- Group-only filter -->
                            <a href="javascript:void(0);" id="vendGrpTypeFilterBtn" class="apex-filter-btn vgrp-only-ctrl d-none" title="Filter by Group Type"><i class="bx bx-category me-1"></i>Group Type</a>
                            <div class="apex-filter-spacer"></div>
                            <a href="javascript:void(0);" class="apex-icon-btn PageRefresh" title="Refresh"><i class="bx bx-refresh"></i></a>
                            <a href="javascript:void(0);" class="apex-icon-btn vend-only-ctrl" id="btnSyncVendorsCache" title="Sync Cache"><i class="bx bx-planet"></i></a>
                            <div class="btn-group d-none vend-only-ctrl" id="ActionsDD-Div">
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
                            <div class="dropdown vend-only-ctrl">
                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="vendExportDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bx bx-export me-1"></i>Export
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="vendExportDropdown">
                                    <li><a class="dropdown-item" href="javascript:void(0);" onclick="vendExport('Print')"><i class="bx bx-printer me-1"></i> Print</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);" onclick="vendExport('CSV')"><i class="bx bx-file me-1"></i> CSV</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);" onclick="vendExport('Excel')"><i class="bx bxs-file-export me-1"></i> Excel</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);" onclick="vendExport('Pdf')"><i class="bx bxs-file-pdf me-1"></i> PDF</a></li>
                                </ul>
                            </div>
                            <a href="javascript:void(0);" class="btn btn-primary vend-only-ctrl" id="btnCreateVendorHeader">
                                <i class="bx bx-plus me-1"></i>New Vendor
                            </a>
                            <!-- Group-only button -->
                            <button type="button" id="btnNewVendorGroup" class="btn btn-primary vgrp-only-ctrl d-none">
                                <i class="bx bx-plus me-1"></i>New Group
                            </button>
                        </div>

                        <!-- Tabs Row -->
                        <div class="apex-tabs-row">
                            <ul class="nav trans-status-tabs" id="vendStatusTabs" role="tablist">
                                <li class="nav-item"><a class="nav-link active vend-tab" data-status="All" href="javascript:void(0);"><i class="bx bxs-store me-1" style="font-size:.85rem;"></i>All <span class="trans-tab-count"><?php echo $VendStats->TotalCount ?? 0; ?></span></a></li>
                                <li class="nav-item">
                                    <a class="nav-link vgrp-view-tab" href="javascript:void(0);" id="vendGroupsViewTab">
                                        <i class="bx bxs-layer me-1" style="font-size:.85rem;"></i>Groups
                                        <span class="trans-tab-count d-none" id="vgrpTabCount"></span>
                                    </a>
                                </li>
                                <!-- Group stats — visible only in groups mode -->
                                <li id="vgrpTabStats" class="d-none align-items-center gap-3 ms-auto pe-2" style="font-size:.81rem;list-style:none;">
                                    <span class="text-muted">Total: <strong class="vg-stat-total text-body">—</strong></span>
                                    <span class="text-muted">Active: <strong class="vg-stat-active text-success">—</strong></span>
                                    <span class="text-muted">Inactive: <strong class="vg-stat-inactive text-danger">—</strong></span>
                                    <span class="text-muted">Members: <strong class="vg-stat-members text-body">—</strong></span>
                                </li>
                            </ul>
                        </div>

                        <!-- Vendor table section -->
                        <div id="vendTableSection">
                        <div class="table-responsive">
                            <table class="table trans-table MainviewTable mb-0" id="VendorsTable">
                                <thead class="r2k-thead">
                                    <tr>
                                        <th style="width:36px">
                                            <div class="form-check mb-0">
                                                <input class="form-check-input table-chkbox vendorHeaderCheck" type="checkbox">
                                            </div>
                                        </th>
                                        <th class="<?php echo $JwtData->GenSettings->SerialNoDisplay == 1 ? '' : 'd-none'; ?>" style="width:44px">#</th>
                                        <th class="vend-name-sortable cursor-pointer" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Click for ascending order">
                                            <span class="sort-label">Vendor <i class="bx bx-sort sort-icon ms-1"></i></span>
                                        </th>
                                        <th class="vend-area-sortable cursor-pointer" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Click for ascending order">Area <i class="bx bx-sort sort-icon ms-1"></i></th>
                                        <th>Mobile</th>
                                        <th>GSTIN / Company</th>
                                        <th class="vend-bal-sortable cursor-pointer" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Click for ascending order">Balance <i class="bx bx-sort sort-icon ms-1"></i></th>
                                        <th>Last Updated</th>
                                        <th style="width:80px">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="r2k-tbody table-border-bottom-0">
                                    <?php echo $ModRowData; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <hr class="my-0">
                        <div class="row mx-3 my-2 justify-content-between align-items-center VendorsPagination" id="VendorsPagination">
                            <?php echo $ModPagination ?: ''; ?>
                        </div>
                        </div><!-- /#vendTableSection -->

                        <!-- Vendor Groups table section (hidden by default) -->
                        <div id="vgrpTableSection" style="display:none;">
                            <div class="table-responsive">
                                <table class="table trans-table MainviewTable mb-0" id="VendorGroupsTable">
                                    <thead class="r2k-thead">
                                        <tr>
                                            <th style="width:36px">
                                                <div class="form-check mb-0">
                                                    <input class="form-check-input table-chkbox vgrpHeaderCheck" type="checkbox">
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
                                    <tbody id="VendorGroupsTableBody">
                                        <tr><td colspan="9" class="text-center py-4 text-muted">Loading groups…</td></tr>
                                    </tbody>
                                </table>
                            </div>
                            <hr class="my-0">
                            <div class="row mx-3 my-2 justify-content-between align-items-center" id="VendorGroupsPagination"></div>
                        </div><!-- /#vgrpTableSection -->

                    </div>

                    <!-- Sticky pagination -->
                    <div class="card mb-0 cust-sticky-pag" id="vendStickyPagination" style="display:none;">
                        <div class="card-body p-0">
                            <div class="row mx-3 my-2 justify-content-between align-items-center VendorsPagination"></div>
                        </div>
                    </div>

                </div>

            </div>
            <!-- Content wrapper -->
            
            <?php $this->load->view('common/imagepreview_modal'); ?>
            <?php $this->load->view('common/settings_modal'); ?>
            <?php $this->load->view('common/modals/send_communication'); ?>
            <?php $this->load->view('common/modals/vendor_group_form'); ?>

            <!-- Vendor Add / Edit / Clone Modal -->
            <div class="modal fade" id="VendorFormModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" aria-hidden="true" style="padding:0!important;">
                <div class="modal-dialog modal-xl modal-dialog-scrollable" style="height:100vh;max-height:100vh;margin:0 auto;">
                    <div class="modal-content h-100 d-flex flex-column">

                        <div class="modal-header bg-white border-bottom d-flex align-items-center justify-content-between px-3 py-2 trans-theme">
                            <div class="d-flex align-items-center gap-3">
                                <div class="modal-doc-icon bg-warning bg-opacity-10">
                                    <i class="bx bx-store text-warning modal-doc-icon-inner"></i>
                                </div>
                                <div>
                                    <h5 class="modal-title mb-0" id="VendorFormModalTitle">Vendor</h5>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <button type="button" class="btn btn-sm btn-primary" id="VendorFormSaveBtn">
                                    <i class="bx bx-check me-1"></i>Save
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger" data-bs-dismiss="modal">
                                    <i class="bx bx-x me-1"></i>Close
                                </button>
                            </div>
                        </div>

                        <div class="modal-body p-0 flex-grow-1 overflow-auto" id="VendorFormModalBody">
                            <?php $this->load->view('vendors/forms/modal_body', [
                                'FormMode'    => 'add',
                                'FormData'    => null,
                                'BankDetails' => [],
                                'BillingAddr' => null,
                                'ShippingAddr'=> null,
                                'CountryInfo' => $CountryInfo,
                                'OrgCCode'    => $OrgCCode,
                                'OrgCISO2'    => $OrgCISO2,
                                'JwtData'     => $JwtData,
                            ]); ?>
                        </div>

                    </div>
                </div>
            </div>

            <?php $this->load->view('common/form/bank_details'); ?>
            <?php $this->load->view('common/form/address_form'); ?>
            <?php $this->load->view('common/footer_desc'); ?>

        </div>

    </div>
</div>

<!-- Filter panels (body-level to avoid overflow clipping) -->
<?php if (!empty($Tags)): ?>
<?php $this->load->view('common/filter_panels/checklist_filter', [
    'ChecklistFilterConfig' => [
        'id'                => 'vendTagFilterBox',
        'triggerId'         => 'vendTagFilter',
        'checkClass'        => 'vend-tag-chk',
        'title'             => 'Tag Filter',
        'icon'              => 'bx-purchase-tag',
        'searchPlaceholder' => 'Search tags...',
        'items'             => array_map(function ($t) { return ['value' => $t, 'label' => $t]; }, $Tags),
    ],
]); ?>
<?php endif; ?>
<?php if ($showUserBtn): ?>
<?php $this->load->view('common/partials/col_user_filter_box', [
    'ColUserFilterConfig' => [
        'id'         => 'vendUserFilterBox',
        'triggerId'  => 'vendUserFilterBtn',
        'checkClass' => 'vend-user-chk',
        'title'      => 'Updated By',
        'OrgUsers'   => $OrgUsers,
    ],
]); ?>
<?php endif; ?>
<?php $this->load->view('common/filter_panels/checklist_filter', [
    'ChecklistFilterConfig' => [
        'id'                => 'vendStatusFilterBox',
        'triggerId'         => 'vendStatusFilterBtn',
        'checkClass'        => 'vend-status-chk',
        'title'             => 'Status',
        'icon'              => 'bx-toggle-left',
        'searchPlaceholder' => 'Search...',
        'items'             => [
            ['value' => '1', 'label' => 'Active'],
            ['value' => '0', 'label' => 'Inactive'],
        ],
    ],
]); ?>
<?php $this->load->view('common/filter_panels/checklist_filter', [
    'ChecklistFilterConfig' => [
        'id'                => 'vendGrpTypeFilterBox',
        'triggerId'         => 'vendGrpTypeFilterBtn',
        'checkClass'        => 'vgrp-type-chk',
        'title'             => 'Group Type',
        'icon'              => 'bx-category',
        'searchPlaceholder' => 'Search types...',
        'items'             => [
            ['value' => 'Business Group',  'label' => 'Business Group'],
            ['value' => 'Branch Group',    'label' => 'Branch Group'],
            ['value' => 'Family Group',    'label' => 'Family Group'],
            ['value' => 'Corporate Group', 'label' => 'Corporate Group'],
            ['value' => 'Dealer Network',  'label' => 'Dealer Network'],
            ['value' => 'Franchise Group', 'label' => 'Franchise Group'],
            ['value' => 'Custom',          'label' => 'Custom'],
        ],
    ],
]); ?>

<?php $this->load->view('common/footer'); ?>

<script src="<?php echo _assetV('/js/transactions/col_filter.js'); ?>"></script>
<link rel="stylesheet" href="<?php echo _assetV('/assets/vendor/css/attachments.css'); ?>">
<script src="<?php echo _assetV('/js/common/attachments.js'); ?>"></script>
<script src="<?php echo _assetV('/js/vendors.js'); ?>"></script>
<script src="<?php echo _assetV('/js/common/pagecheckbox.js'); ?>"></script>
<script src="<?php echo _assetV('/js/common/communication.js'); ?>"></script>
<script src="<?php echo _assetV('/js/common/gstin_fetch.js'); ?>"></script>
<script src="<?php echo _assetV('/js/common/bankdetails.js'); ?>"></script>
<script src="<?php echo _assetV('/js/common/address.js'); ?>"></script>
<script src="/js/common/vendor_group_form.js"></script>

<script>
let ModuleId = <?php echo $ModuleId; ?>;
const ModuleTable = '#VendorsTable';
const ModulePag = '.VendorsPagination';
const ModuleHeader = '.vendorHeaderCheck';
const ModuleRow = '.vendorsCheck';
const ModuleFileName = 'Vendor_Data';
const ModuleSheetName = 'Vendor';
const previewName = 'Vendor Details';
let nameSortState = 0;
let balSortState  = 0;
let areaSortState = 0;
var StateInfo = [];
var CityInfo  = [];
var OrgCountryISO2 = <?php echo json_encode($OrgCISO2 ?? 'IN'); ?>;
var VendShowUserFilter = <?php echo $showUserBtn ? 'true' : 'false'; ?>;

$(function() {
    'use strict'

    $('#SearchDetails').val('');
    $(ModuleRow).prop('checked', false).trigger('change');

    basePaginationFunc(ModulePag, getVendorsDetails);
    baseRefreshPageFunc('.PageRefresh', getVendorsDetails);

    // ── Sync vendors to Upstash cache ───────────────────────────────────────
    $(document).on('click', '#btnSyncVendorsCache', function () {
        var $btn = $(this);
        $btn.find('i').removeClass('bx-planet').addClass('bx-loader-alt bx-spin');
        $.ajax({
            url    : '/vendors/syncVendorsCache',
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
    basePageHeaderFunc(ModuleHeader, ModuleTable, ModuleRow);

    $(document).on('click', '#btnCreateVendorHeader', function () { openVendorModal('add'); });

    // ── Auto-hide ActionsDD until options are visible ──
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

    // ── Sticky pagination ──
    var $vStaticPag = $('#VendorsPagination');
    var $vStickyPag = $('#vendStickyPagination');
    function _syncVendSticky() { $vStickyPag.find('.VendorsPagination').html($vStaticPag.html()); }
    function _toggleVendSticky() {
        if (_inVgrpMode) { $vStickyPag.stop(true, true).hide(); return; }
        if (!$vStaticPag.length) return;
        var r = $vStaticPag[0].getBoundingClientRect();
        var visible = r.top < $(window).height() && r.bottom > 0;
        if (visible) { $vStickyPag.stop(true,true).fadeOut(150); }
        else { _syncVendSticky(); $vStickyPag.stop(true,true).fadeIn(150); }
    }
    $(window).on('scroll resize', _toggleVendSticky);
    _toggleVendSticky();

    // ── Stat card clicks ──
    $(document).on('click', '.apex-stat-item[data-stat-filter]', function () {
        var filterType = $(this).data('stat-filter');
        delete Filter['IsActive'];
        delete Filter['BalanceType'];
        $('.apex-stat-item').removeClass('active');
        $(this).addClass('active');

        if (filterType === 'Active') {
            Filter['IsActive'] = 1;
            if (vendStatusFilter) vendStatusFilter.setState(['1']);
        } else if (filterType === 'ToCollect') {
            Filter['BalanceType'] = 'Debit';
            if (vendStatusFilter) vendStatusFilter.reset();
        } else if (filterType === 'ToPay') {
            Filter['BalanceType'] = 'Credit';
            if (vendStatusFilter) vendStatusFilter.reset();
        } else {
            if (vendStatusFilter) vendStatusFilter.reset();
        }

        PageNo = 0;
        getVendorsDetails(PageNo, RowLimit, Filter);
    });

    // ── All tab click (also exits groups mode) ──
    $(document).on('click', '.vend-tab', function (e) {
        e.preventDefault();

        if (_inVgrpMode) {
            // Switching Groups → All: restore UI, do NOT reload (data already in table)
            _inVgrpMode = false;
            $('.vend-only-ctrl').removeClass('d-none');
            $('.vgrp-only-ctrl').addClass('d-none');
            $('#SearchDetails').attr('placeholder', 'Name, mobile, GSTIN...').val('');
            delete _vgrpFilter['SearchAllData'];
            $('#clearSearch').addClass('d-none');
            $('#vendTableSection').show();
            $('#vgrpTableSection').hide();
            $('#vgrpTabStats').removeClass('d-flex').addClass('d-none');
            $('.vgrp-view-tab').removeClass('active');
            $('.vend-tab').removeClass('active');
            $(this).addClass('active');
            _toggleVendSticky();
            return;
        }

        // Already in vendor mode — reset filters and reload
        $('.vend-tab').removeClass('active');
        $(this).addClass('active');
        $('.apex-stat-item').removeClass('active');
        $('.apex-stat-item[data-stat-filter="All"]').addClass('active');
        delete Filter['IsActive'];
        delete Filter['BalanceType'];
        if (vendStatusFilter) vendStatusFilter.reset();
        PageNo = 0;
        getVendorsDetails(PageNo, RowLimit, Filter);
    });

    // ── Search ──
    $('#SearchDetails').on('input', inputDelay(function () {
        var val = $.trim($(this).val());
        $('#clearSearch').toggleClass('d-none', !val);
        delete Filter['SearchAllData'];
        if (val.length >= 3) Filter['SearchAllData'] = val;
        if (val.length === 0 || val.length >= 3) { PageNo = 0; getVendorsDetails(PageNo, RowLimit, Filter); }
    }, 1500));

    $('#clearSearch').on('click', function () {
        $('#SearchDetails').val('');
        $(this).addClass('d-none');
        delete Filter['SearchAllData'];
        PageNo = 0; getVendorsDetails(PageNo, RowLimit, Filter);
    });

    // ── Row checkbox ──
    $(document).on('click', ModuleRow, function () {
        onClickOfCheckbox($(this), ModuleTable, ModuleHeader, ModuleRow);
        $('#CloneOption').addClass('d-none');
        if (SelectedUIDs.length === 1) $('#CloneOption').removeClass('d-none');
        MultipleDeleteOption();
    });
    $('#btnClone').on('click', function (e) {
        e.preventDefault();
        if (SelectedUIDs.length === 1) openVendorModal('clone', SelectedUIDs[0]);
    });

    // ── Delete single ──
    $(document).on('click', '.DeleteVendor', function (e) {
        e.preventDefault();
        var id = $(this).data('vendoruid');
        if (!id) return;
        Swal.fire({
            title: 'Delete this vendor?', text: 'This action cannot be undone.',
            icon: 'warning', showCancelButton: true,
            confirmButtonColor: '#dc2626', cancelButtonColor: '#64748b',
            confirmButtonText: 'Yes, delete',
        }).then(function (r) { if (r.isConfirmed) deleteVendor(id); });
    });

    // ── Delete bulk ──
    $('#btnDelete').on('click', function (e) {
        e.preventDefault();
        if (!SelectedUIDs.length) return;
        Swal.fire({
            title: 'Delete ' + SelectedUIDs.length + ' vendor(s)?', text: 'This action cannot be undone.',
            icon: 'warning', showCancelButton: true,
            confirmButtonColor: '#dc2626', cancelButtonColor: '#64748b',
            confirmButtonText: 'Yes, delete all',
        }).then(function (r) { if (r.isConfirmed) deleteMultipleVendors(); });
    });

    // ── Name sort ──
    $(document).on('click', '.vend-name-sortable', function (e) {
        e.preventDefault();
        nameSortState = (nameSortState + 1) % 3;
        if (nameSortState !== 0) {
            areaSortState = 0; balSortState = 0;
            delete Filter['AreaSorting']; delete Filter['BalanceSorting'];
            $('.vend-area-sortable .sort-icon, .vend-bal-sortable .sort-icon').removeClass('bx-up-arrow-alt bx-down-arrow-alt text-primary').addClass('bx-sort');
            $('.vend-area-sortable, .vend-bal-sortable').attr('data-bs-title', 'Click for ascending order');
        }
        var icon = $(this).find('.sort-icon');
        icon.removeClass('bx-sort bx-up-arrow-alt bx-down-arrow-alt text-primary');
        if (nameSortState === 1)      { icon.addClass('bx-up-arrow-alt text-primary');   $(this).attr('data-bs-title', 'Click for descending order'); Filter['NameSorting'] = 1; }
        else if (nameSortState === 2) { icon.addClass('bx-down-arrow-alt text-primary'); $(this).attr('data-bs-title', 'Click to remove sorting');   Filter['NameSorting'] = 2; }
        else                          { icon.addClass('bx-sort'); $(this).attr('data-bs-title', 'Click for ascending order'); delete Filter['NameSorting']; }
        var _tt = bootstrap.Tooltip.getInstance(this); if (_tt) { _tt.hide(); _tt.dispose(); } new bootstrap.Tooltip(this, { container: 'body', trigger: 'hover' });
        PageNo = 0; getVendorsDetails(PageNo, RowLimit, Filter);
    });

    // ── Area sort ──
    $(document).on('click', '.vend-area-sortable', function (e) {
        e.preventDefault();
        areaSortState = (areaSortState + 1) % 3;
        if (areaSortState !== 0) {
            nameSortState = 0; balSortState = 0;
            delete Filter['NameSorting']; delete Filter['BalanceSorting'];
            $('.vend-name-sortable .sort-icon, .vend-bal-sortable .sort-icon').removeClass('bx-up-arrow-alt bx-down-arrow-alt text-primary').addClass('bx-sort');
            $('.vend-name-sortable, .vend-bal-sortable').attr('data-bs-title', 'Click for ascending order');
        }
        var icon = $(this).find('.sort-icon');
        icon.removeClass('bx-sort bx-up-arrow-alt bx-down-arrow-alt text-primary');
        if (areaSortState === 1)      { icon.addClass('bx-up-arrow-alt text-primary');   $(this).attr('data-bs-title', 'Click for descending order'); Filter['AreaSorting'] = 1; }
        else if (areaSortState === 2) { icon.addClass('bx-down-arrow-alt text-primary'); $(this).attr('data-bs-title', 'Click to remove sorting');   Filter['AreaSorting'] = 2; }
        else                          { icon.addClass('bx-sort'); $(this).attr('data-bs-title', 'Click for ascending order'); delete Filter['AreaSorting']; }
        var _tt = bootstrap.Tooltip.getInstance(this); if (_tt) { _tt.hide(); _tt.dispose(); } new bootstrap.Tooltip(this, { container: 'body', trigger: 'hover' });
        PageNo = 0; getVendorsDetails(PageNo, RowLimit, Filter);
    });

    // ── Balance sort ──
    $(document).on('click', '.vend-bal-sortable', function (e) {
        e.preventDefault();
        balSortState = (balSortState + 1) % 3;
        if (balSortState !== 0) {
            nameSortState = 0; areaSortState = 0;
            delete Filter['NameSorting']; delete Filter['AreaSorting'];
            $('.vend-name-sortable .sort-icon, .vend-area-sortable .sort-icon').removeClass('bx-up-arrow-alt bx-down-arrow-alt text-primary').addClass('bx-sort');
            $('.vend-name-sortable, .vend-area-sortable').attr('data-bs-title', 'Click for ascending order');
        }
        var icon = $(this).find('.sort-icon');
        icon.removeClass('bx-sort bx-up-arrow-alt bx-down-arrow-alt text-primary');
        if (balSortState === 1)      { icon.addClass('bx-up-arrow-alt text-primary');   $(this).attr('data-bs-title', 'Click for descending order'); Filter['BalanceSorting'] = 1; }
        else if (balSortState === 2) { icon.addClass('bx-down-arrow-alt text-primary'); $(this).attr('data-bs-title', 'Click to remove sorting');   Filter['BalanceSorting'] = 2; }
        else                         { icon.addClass('bx-sort'); $(this).attr('data-bs-title', 'Click for ascending order'); delete Filter['BalanceSorting']; }
        var _tt = bootstrap.Tooltip.getInstance(this); if (_tt) { _tt.hide(); _tt.dispose(); } new bootstrap.Tooltip(this, { container: 'body', trigger: 'hover' });
        PageNo = 0; getVendorsDetails(PageNo, RowLimit, Filter);
    });

    // ── Tag filter (TransColFilter) ──
    <?php if (!empty($Tags)): ?>
    var vendTagFilter = new TransColFilter({
        boxId      : 'vendTagFilterBox',
        triggerId  : 'vendTagFilter',
        filterKey  : 'Tags',
        activeClass: 'has-filter',
        onApply    : function () {
            var state = vendTagFilter.getState();
            if (state.Tags && state.Tags.length) Filter['Tags'] = state.Tags;
            else delete Filter['Tags'];
            PageNo = 0; getVendorsDetails(PageNo, RowLimit, Filter);
        }
    });
    <?php endif; ?>

    // ── Status toggle ──
    $(document).on('click', '.vend-status-toggle', function (e) {
        e.preventDefault();
        var uid = $(this).data('uid');
        var newStatus = $(this).data('newstatus');
        var label = newStatus == 1 ? 'Active' : 'In-Active';
        Swal.fire({
            title: 'Change status to ' + label + '?',
            icon: 'question', showCancelButton: true,
            confirmButtonColor: '#0d6efd', cancelButtonColor: '#64748b',
            confirmButtonText: 'Yes, change it',
        }).then(function (r) {
            if (!r.isConfirmed) return;
            toggleVendorStatus(uid, newStatus);
        });
    });

    // ── User filter (TransColFilter) ──
    var vendUserFilter = null;
    if (VendShowUserFilter) {
        vendUserFilter = new TransColFilter({
            boxId      : 'vendUserFilterBox',
            triggerId  : 'vendUserFilterBtn',
            filterKey  : 'UpdatedByUIDs',
            activeClass: 'has-filter',
            onApply    : function () {
                var state = vendUserFilter.getState();
                if (state.UpdatedByUIDs && state.UpdatedByUIDs.length) Filter['UpdatedByUIDs'] = state.UpdatedByUIDs;
                else delete Filter['UpdatedByUIDs'];
                PageNo = 0; getVendorsDetails(PageNo, RowLimit, Filter);
            }
        });
    }

    // ── Status filter (TransColFilter) ──
    var vendStatusFilter = new TransColFilter({
        boxId      : 'vendStatusFilterBox',
        triggerId  : 'vendStatusFilterBtn',
        filterKey  : 'IsActive',
        activeClass: 'has-filter',
        onApply    : function () {
            var state = vendStatusFilter.getState();
            delete Filter['IsActive'];
            if (state.IsActive && state.IsActive.length === 1) {
                Filter['IsActive'] = parseInt(state.IsActive[0], 10);
            }
            PageNo = 0; getVendorsDetails(PageNo, RowLimit, Filter);
        }
    });

    // ══════════════════════════════════════════════════════════════
    // VENDOR GROUPS TAB
    // ══════════════════════════════════════════════════════════════
    var _inVgrpMode  = false;
    var _vgrpPageNo  = 1;
    var _vgrpFilter  = {};
    var _vgrpLoaded  = false;

    // ── Groups tab click ──
    $(document).on('click', '.vgrp-view-tab', function (e) {
        e.preventDefault();
        if (_inVgrpMode) return;
        _inVgrpMode = true;
        _toggleVendSticky();
        $('.vend-tab').removeClass('active');
        $('.vgrp-view-tab').addClass('active');
        $('.vend-only-ctrl').addClass('d-none');
        $('.vgrp-only-ctrl').removeClass('d-none');
        $('#SearchDetails').attr('placeholder', 'Group name, code, type...').val('');
        delete _vgrpFilter['SearchAllData'];
        $('#clearSearch').addClass('d-none');
        $('#vendTableSection').hide();
        $('#vgrpTableSection').show();
        $('#vgrpTabStats').removeClass('d-none').addClass('d-flex');
        if (!_vgrpLoaded) { _vgrpLoaded = true; _vgrpReload(1); }
    });

    // ── Extend search to groups mode ──
    $('#SearchDetails').on('input.vgrp', function () {
        if (!_inVgrpMode) return;
        var val = $.trim($(this).val());
        $('#clearSearch').toggleClass('d-none', !val);
        delete _vgrpFilter['SearchAllData'];
        if (val.length >= 3) _vgrpFilter['SearchAllData'] = val;
        if (val.length === 0 || val.length >= 3) _vgrpReload(1);
    });

    // ── Apply groups response data to DOM ──
    function _applyVgrpData(res) {
        _vgrpPageNo = 1;
        $('#VendorGroupsTableBody').html(res.RecordHtmlData);
        $('#VendorGroupsPagination').html(res.Pagination);
        _updateVgrpStats(res.Stats);
        var cnt = res.TotalCount || 0;
        $('#vgrpTabCount').text(cnt > 0 ? cnt : '').toggleClass('d-none', cnt === 0);
    }

    // ── Groups AJAX reload ──
    function _vgrpReload(page) {
        _vgrpPageNo = page || 1;
        $('#VendorGroupsTableBody').html('<tr><td colspan="9" class="text-center py-4"><span class="spinner-border spinner-border-sm text-primary" role="status"></span></td></tr>');
        $.ajax({
            url   : '/vendors/getGroupsData/' + _vgrpPageNo,
            method: 'POST',
            data  : { Filter: _vgrpFilter, [CsrfName]: CsrfToken },
            success: function (res) {
                CsrfToken = res.NewCsrfToken || CsrfToken;
                if (res.Error) { showToastNotification(res.Message, 'error'); return; }
                $('#VendorGroupsTableBody').html(res.RecordHtmlData);
                $('#VendorGroupsPagination').html(res.Pagination);
                _updateVgrpStats(res.Stats);
                var cnt = res.TotalCount || 0;
                $('#vgrpTabCount').text(cnt > 0 ? cnt : '').toggleClass('d-none', cnt === 0);
            },
        });
    }

    function _updateVgrpStats(s) {
        if (!s) return;
        $('.vg-stat-total').text(s.TotalCount    || 0);
        $('.vg-stat-active').text(s.ActiveCount   || 0);
        $('.vg-stat-inactive').text(s.InactiveCount || 0);
        $('.vg-stat-members').text(s.TotalMembers  || 0);
    }

    // ── Groups pagination ──
    $(document).on('click', '#VendorGroupsPagination .pagination .page-link', function (e) {
        e.preventDefault();
        var pg = parseInt($(this).data('page'), 10);
        if (!isNaN(pg) && pg !== _vgrpPageNo) _vgrpReload(pg);
    });

    // ── New Group button ──
    $(document).on('click', '#btnNewVendorGroup, .vbtn-new-group', function () {
        VendorGroupForm.open('add', null, { onSaveSuccess: function (res) { _applyVgrpData(res); } });
    });

    // ── Edit Group button ──
    $(document).on('click', '.vgrp-edit-btn', function () {
        var uid = parseInt($(this).data('uid'));
        if (!uid) return;
        VendorGroupForm.open('edit', uid, { onSaveSuccess: function (res) { _applyVgrpData(res); } });
    });

    // ── Group Type filter (groups mode only) ──
    var vendGrpTypeFilter = new TransColFilter({
        boxId       : 'vendGrpTypeFilterBox',
        triggerId   : 'vendGrpTypeFilterBtn',
        filterKey   : 'GroupType',
        activeClass : 'has-filter',
        onApply     : function () {
            var state = vendGrpTypeFilter.getState();
            if (state.GroupType && state.GroupType.length) _vgrpFilter.GroupType = state.GroupType;
            else delete _vgrpFilter.GroupType;
            _vgrpReload(1);
        }
    });

    // ── Group status toggle ──
    $(document).on('click', '.vgrp-status-toggle', function (e) {
        e.preventDefault();
        var uid       = $(this).data('uid');
        var newStatus = $(this).data('newstatus');
        var label     = newStatus == 1 ? 'Active' : 'Inactive';
        Swal.fire({
            title: 'Change group status to ' + label + '?',
            icon: 'question', showCancelButton: true,
            confirmButtonColor: '#0d6efd', cancelButtonColor: '#64748b',
            confirmButtonText: 'Yes, change it',
        }).then(function (r) {
            if (!r.isConfirmed) return;
            $('#VendorGroupsTableBody').html('<tr><td colspan="9" class="text-center py-4"><span class="spinner-border spinner-border-sm text-primary" role="status"></span></td></tr>');
            AjaxLoading = 0;
            $.ajax({
                url   : '/vendors/toggleGroupStatus',
                method: 'POST',
                data  : { GroupUID: uid, IsActive: newStatus, [CsrfName]: CsrfToken },
                success: function (res) {
                    CsrfToken = res.NewCsrfToken || CsrfToken;
                    if (res.Error) { showToastNotification(res.Message, 'error'); return; }
                    showToastNotification(res.Message, 'success');
                    $('#VendorGroupsTableBody').html(res.RecordHtmlData);
                    $('#VendorGroupsPagination').html(res.Pagination);
                    _updateVgrpStats(res.Stats);
                },
                error: function () { showToastNotification('Failed to update status.', 'error'); }
            });
        });
    });

    // ── Group delete ──
    $(document).on('click', '.vgrp-delete-btn', function (e) {
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
            $('#VendorGroupsTableBody').html('<tr><td colspan="9" class="text-center py-4"><span class="spinner-border spinner-border-sm text-danger" role="status"></span></td></tr>');
            AjaxLoading = 0;
            $.ajax({
                url   : '/vendors/deleteGroup',
                method: 'POST',
                data  : { GroupUID: uid, [CsrfName]: CsrfToken },
                success: function (res) {
                    CsrfToken = res.NewCsrfToken || CsrfToken;
                    if (res.Error) { showToastNotification(res.Message, 'error'); return; }
                    showToastNotification(res.Message, 'success');
                    $('#VendorGroupsTableBody').html(res.RecordHtmlData);
                    $('#VendorGroupsPagination').html(res.Pagination);
                    _updateVgrpStats(res.Stats);
                    var cnt = res.TotalCount || 0;
                    $('#vgrpTabCount').text(cnt > 0 ? cnt : '').toggleClass('d-none', cnt === 0);
                },
                error: function () { showToastNotification('Delete failed.', 'error'); }
            });
        });
    });

});
</script>