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
                    'pageIcon'        => 'bxs-store',
                    'pageIconBg'      => '#fef9c3',
                    'pageIconColor'   => '#ca8a04',
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
                            <div class="apex-search-wrap">
                                <i class="bx bx-search apex-search-icon"></i>
                                <input type="text" class="apex-search-input" id="SearchDetails" placeholder="Name, mobile, GSTIN...">
                                <i class="bx bx-x r2k-clear d-none" id="clearSearch"></i>
                            </div>
                            <div class="apex-filter-spacer"></div>
                            <a href="javascript:void(0);" class="apex-icon-btn PageRefresh" title="Refresh"><i class="bx bx-refresh"></i></a>
                            <a href="javascript:void(0);" class="apex-icon-btn" id="btnSyncVendorsCache" title="Sync Cache"><i class="bx bx-planet"></i></a>
                            <div class="btn-group d-none" id="ActionsDD-Div">
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
                            <div class="dropdown">
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
                            <a href="javascript:void(0);" class="btn btn-primary" id="btnCreateVendorHeader">
                                <i class="bx bx-plus me-1"></i>New Vendor
                            </a>
                        </div>

                        <!-- Tabs Row -->
                        <div class="apex-tabs-row">
                            <ul class="nav trans-status-tabs" id="vendStatusTabs" role="tablist">
                                <li class="nav-item"><a class="nav-link active vend-tab" data-status="All"      href="javascript:void(0);">All      <span class="trans-tab-count"><?php echo $VendStats->TotalCount ?? 0; ?></span></a></li>
                                <li class="nav-item"><a class="nav-link vend-tab"        data-status="Active"   href="javascript:void(0);">Active   <span class="trans-tab-count d-none"></span></a></li>
                                <li class="nav-item"><a class="nav-link vend-tab"        data-status="Inactive" href="javascript:void(0);">Inactive <span class="trans-tab-count d-none"></span></a></li>
                            </ul>
                        </div>

                        <!-- Table -->
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
                                        <th class="vend-name-sortable position-relative cursor-pointer" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Click for ascending order">
                                            <span class="sort-label">Vendor <i class="bx bx-sort sort-icon ms-1"></i></span>
                                            <a href="javascript:void(0);" id="vendTagFilter" class="text-body ms-1" onclick="toggleVendTagFilter(); event.stopPropagation();" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Filter by Tag"><i class="bx bx-filter-alt fs-6 align-middle"></i></a>
                                            <div id="vendTagFilterBox" class="card mp-filterbox position-absolute" style="min-width:220px;z-index:1056;display:none;top:100%;left:0;"><?php $this->load->view('vendors/tagfilter', ['Tags' => $Tags]); ?></div>
                                        </th>
                                        <th class="vend-area-sortable cursor-pointer" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Click for ascending order">Area <i class="bx bx-sort sort-icon ms-1"></i></th>
                                        <th>Mobile</th>
                                        <th>GSTIN / Company</th>
                                        <th class="vend-bal-sortable cursor-pointer" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Click for ascending order">Balance <i class="bx bx-sort sort-icon ms-1"></i></th>
                                        <th>Last Updated<?php if ($showUserBtn): ?> <a href="javascript:void(0);" id="vendUserFilterBtn" onclick="vendToggleUserFilter(); event.stopPropagation();" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Filter by User"><i class="bx bx-user-check fs-6 align-middle" id="vendUserFilterIcon"></i></a><?php endif; ?></th>
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

<!-- Filter boxes (body-level to avoid overflow clipping) -->
<div id="vendTagFilterBox" class="card mp-filterbox" style="min-width:220px;z-index:9999;display:none;position:fixed;"><?php $this->load->view('vendors/tagfilter', ['Tags' => $Tags]); ?></div>
<?php if ($showUserBtn): ?>
<?php $this->load->view('common/partials/_user_filter_box', [
    'OrgUsers'   => $OrgUsers,
    'BoxId'      => 'vendUserFilterBox',
    'CheckClass' => 'vend-user-checkbox',
    'ApplyFn'    => 'vendApplyUserFilter',
    'ResetFn'    => 'vendResetUserFilter',
]); ?>
<?php endif; ?>

<?php $this->load->view('common/footer'); ?>

<script src="/js/vendors.js"></script>
<script src="/js/common/pagecheckbox.js"></script>
<script src="/js/common/communication.js"></script>
<script src="/js/common/gstin_fetch.js"></script>
<script src="/js/common/bankdetails.js"></script>
<script src="/js/common/address.js"></script>

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

        if (filterType === 'All')            { /* no filter */ }
        else if (filterType === 'Active')    { Filter['IsActive'] = 1; }
        else if (filterType === 'ToCollect') { Filter['BalanceType'] = 'Debit'; }
        else if (filterType === 'ToPay')     { Filter['BalanceType'] = 'Credit'; }

        $('.vend-tab').removeClass('active');
        if (filterType === 'All')          { $('.vend-tab[data-status="All"]').addClass('active'); }
        else if (filterType === 'Active')  { $('.vend-tab[data-status="Active"]').addClass('active'); }

        PageNo = 0;
        getVendorsDetails(PageNo, RowLimit, Filter);
    });

    // ── Status tabs ──
    $(document).on('click', '.vend-tab', function (e) {
        e.preventDefault();
        $('.vend-tab').removeClass('active');
        $(this).addClass('active');
        $('.apex-stat-item').removeClass('active');
        var status = $(this).data('status') || 'All';
        $('.apex-stat-item[data-stat-filter="' + status + '"]').addClass('active');
        delete Filter['IsActive'];
        delete Filter['BalanceType'];
        if (status === 'All')           { /* no filter */ }
        else if (status === 'Active')   { Filter['IsActive'] = 1; }
        else if (status === 'Inactive') { Filter['IsActive'] = 0; }
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
        }
        var icon = $(this).find('.sort-icon');
        icon.removeClass('bx-sort bx-up-arrow-alt bx-down-arrow-alt text-primary');
        if (nameSortState === 1)      { icon.addClass('bx-up-arrow-alt text-primary');   Filter['NameSorting'] = 1; }
        else if (nameSortState === 2) { icon.addClass('bx-down-arrow-alt text-primary'); Filter['NameSorting'] = 2; }
        else                          { icon.addClass('bx-sort'); delete Filter['NameSorting']; }
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
        }
        var icon = $(this).find('.sort-icon');
        icon.removeClass('bx-sort bx-up-arrow-alt bx-down-arrow-alt text-primary');
        if (areaSortState === 1)      { icon.addClass('bx-up-arrow-alt text-primary');   Filter['AreaSorting'] = 1; }
        else if (areaSortState === 2) { icon.addClass('bx-down-arrow-alt text-primary'); Filter['AreaSorting'] = 2; }
        else                          { icon.addClass('bx-sort'); delete Filter['AreaSorting']; }
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
        }
        var icon = $(this).find('.sort-icon');
        icon.removeClass('bx-sort bx-up-arrow-alt bx-down-arrow-alt text-primary');
        if (balSortState === 1)      { icon.addClass('bx-up-arrow-alt text-primary');   Filter['BalanceSorting'] = 1; }
        else if (balSortState === 2) { icon.addClass('bx-down-arrow-alt text-primary'); Filter['BalanceSorting'] = 2; }
        else                         { icon.addClass('bx-sort'); delete Filter['BalanceSorting']; }
        PageNo = 0; getVendorsDetails(PageNo, RowLimit, Filter);
    });

    // ── Tag filter ──
    window.toggleVendTagFilter = function () {
        var $box = $('#vendTagFilterBox');
        if ($box.is(':visible')) { $box.hide(); return; }
        var rect = document.getElementById('vendTagFilter').getBoundingClientRect();
        $box.css({ top: (rect.bottom + 4) + 'px', left: rect.left + 'px' }).show();
    };
    window.closeVendTagFilter = function () { $('#vendTagFilterBox').hide(); };
    window.toggleAllVendTags = function (el) {
        var checked = $(el).is(':checked');
        $('#vendTagList .vend-tag-chk').prop('checked', checked);
        $('#vendTagSelectAllLabel').text(checked ? 'Deselect All' : 'Select All');
    };
    window.applyVendTagFilter = function () {
        var selected = $('.vend-tag-chk:checked').map(function () { return $(this).val(); }).get();
        if (selected.length) Filter['Tags'] = selected; else delete Filter['Tags'];
        $('#vendTagFilterBox').hide();
        $('#vendTagFilter').toggleClass('text-primary', !!selected.length);
        PageNo = 0; getVendorsDetails(PageNo, RowLimit, Filter);
    };
    window.resetVendTagFilter = function () {
        $('.vend-tag-chk').prop('checked', false);
        $('#selectAllVendTags').prop('checked', false);
        $('#vendTagSelectAllLabel').text('Select All');
        delete Filter['Tags'];
        $('#vendTagFilterBox').hide();
        $('#vendTagFilter').removeClass('text-primary');
        PageNo = 0; getVendorsDetails(PageNo, RowLimit, Filter);
    };
    $(document).on('input', '#vendTagSearch', function () {
        var term = $(this).val().toLowerCase();
        $('#vendTagList .catg-list-item').each(function () {
            $(this).toggle($(this).text().toLowerCase().includes(term));
        });
    });
    $(document).on('change', '.vend-tag-chk', function () {
        var total = $('.vend-tag-chk').length, checked = $('.vend-tag-chk:checked').length;
        $('#selectAllVendTags').prop('checked', total === checked && total > 0);
        $('#vendTagSelectAllLabel').text(total === checked && total > 0 ? 'Deselect All' : 'Select All');
    });

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

    // ── User filter (Last Updated) ──
    if (VendShowUserFilter) {
        window.vendToggleUserFilter = function () {
            var $box = $('#vendUserFilterBox');
            if ($box.is(':visible')) { $box.hide(); return; }
            var rect = document.getElementById('vendUserFilterBtn').getBoundingClientRect();
            $box.css({ top: (rect.bottom + 4) + 'px', left: rect.left + 'px', display: 'flex' });
        };
        window.vendApplyUserFilter = function () {
            var selected = $('.vend-user-checkbox:checked').map(function () { return parseInt($(this).val(), 10); }).get();
            if (selected.length) Filter['UpdatedByUIDs'] = selected; else delete Filter['UpdatedByUIDs'];
            $('#vendUserFilterBox').hide();
            $('#vendUserFilterIcon').css('color', selected.length ? '#0d6efd' : '');
            PageNo = 0; getVendorsDetails(PageNo, RowLimit, Filter);
        };
        window.vendResetUserFilter = function () {
            $('.vend-user-checkbox').prop('checked', false);
            delete Filter['UpdatedByUIDs'];
            $('#vendUserFilterBox').hide();
            $('#vendUserFilterIcon').css('color', '');
            PageNo = 0; getVendorsDetails(PageNo, RowLimit, Filter);
        };
    }

    // ── Close filter boxes on outside click ──
    $(document).on('click', function (e) {
        if (!$(e.target).closest('#vendTagFilterBox, #vendTagFilter').length) $('#vendTagFilterBox').hide();
        if (!$(e.target).closest('#vendUserFilterBox, #vendUserFilterBtn').length) $('#vendUserFilterBox').hide();
    });

});
</script>