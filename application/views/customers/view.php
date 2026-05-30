<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php $this->load->view('common/header'); ?>

<div class="layout-wrapper layout-horizontal layout-content-navbar">
    <div class="layout-container">

        <?php $this->load->view('common/menu_view'); ?>

        <div class="layout-page">

            <div class="content-wrapper">
                <div class="container-xxl flex-grow-1 container-p-y">

                    <!-- ── Page Header ── -->
                    <div class="trans-page-header">
                        <div class="d-flex align-items-center gap-3">
                            <div class="trans-ph-icon ph-icon-customers">
                                <i class="bx bxs-group"></i>
                            </div>
                            <div>
                                <h5 class="trans-ph-title mb-0"><?php echo htmlspecialchars($PageTitle ?? 'Customers'); ?></h5>
                                <?php if (!empty($PageDescription)): ?>
                                <div class="text-muted" style="font-size:.76rem;"><?php echo htmlspecialchars($PageDescription); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <!-- Export dropdown -->
                            <div class="dropdown">
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
                            <a href="javascript:void(0);" class="btn btn-primary" id="btnCreateCustomerHeader">
                                <i class="bx bx-plus me-1"></i>New Customer
                            </a>
                        </div>
                    </div>

                    <!-- ── Stat Cards ── -->
                    <?php $s = $CustStats ?? null; ?>
                    <div class="row g-3 mb-3">
                        <div class="col-6 col-md">
                            <div class="trans-stat-card stat-all">
                                <div class="trans-stat-label">Total Customers</div>
                                <div class="trans-stat-count cust-stat-total"><?php echo number_format((int)($s->TotalCount ?? 0)); ?></div>
                                <div class="trans-stat-amount">&nbsp;</div>
                                <i class="bx bxs-group trans-stat-icon"></i>
                            </div>
                        </div>
                        <div class="col-6 col-md">
                            <div class="trans-stat-card stat-active">
                                <div class="trans-stat-label">Active</div>
                                <div class="trans-stat-count cust-stat-active"><?php echo number_format((int)($s->ActiveCount ?? 0)); ?></div>
                                <div class="trans-stat-amount">&nbsp;</div>
                                <i class="bx bx-check-circle trans-stat-icon"></i>
                            </div>
                        </div>
                        <div class="col-6 col-md">
                            <div class="trans-stat-card stat-paid">
                                <div class="trans-stat-label">This Month</div>
                                <div class="trans-stat-count cust-stat-month"><?php echo number_format((int)($s->MonthCount ?? 0)); ?></div>
                                <div class="trans-stat-amount">&nbsp;</div>
                                <i class="bx bx-calendar trans-stat-icon"></i>
                            </div>
                        </div>
                        <div class="col-6 col-md">
                            <div class="trans-stat-card stat-converted">
                                <div class="trans-stat-label">This Financial Year</div>
                                <div class="trans-stat-count cust-stat-fy"><?php echo number_format((int)($s->FYCount ?? 0)); ?></div>
                                <div class="trans-stat-amount">&nbsp;</div>
                                <i class="bx bx-trending-up trans-stat-icon"></i>
                            </div>
                        </div>
                        <div class="col-6 col-md">
                            <div class="trans-stat-card stat-draft">
                                <div class="trans-stat-label">Last Month</div>
                                <div class="trans-stat-count cust-stat-lastmonth"><?php echo number_format((int)($s->LastMonthCount ?? 0)); ?></div>
                                <div class="trans-stat-amount">&nbsp;</div>
                                <i class="bx bx-history trans-stat-icon"></i>
                            </div>
                        </div>
                    </div>

                    <?php $showUserBtn = is_array($OrgUsers) && count($OrgUsers) > 1; ?>

                    <!-- ── Main Card ── -->
                    <div class="card">

                        <!-- Toolbar -->
                        <div class="trans-toolbar">
                            <div class="trans-toolbar-tabs">
                                <ul class="nav trans-status-tabs" id="custStatusTabs" role="tablist">
                                    <li class="nav-item"><a class="nav-link active cust-tab" data-status="All" href="javascript:void(0);">All <span class="trans-tab-count"><?php echo $CustStats->TotalCount ?? 0; ?></span></a></li>
                                    <li class="nav-item"><a class="nav-link cust-tab" data-status="Active" href="javascript:void(0);">Active <span class="trans-tab-count d-none"></span></a></li>
                                    <li class="nav-item"><a class="nav-link cust-tab" data-status="Inactive" href="javascript:void(0);">Inactive <span class="trans-tab-count d-none"></span></a></li>
                                </ul>
                            </div>
                            <div class="trans-toolbar-actions">
                                <a href="javascript:void(0);" class="r2k-icon-btn PageRefresh" title="Refresh"><i class="bx bx-refresh"></i></a>
                                <a href="javascript:void(0);" class="r2k-icon-btn" id="btnSyncCustomersCache" title="☉"><i class="bx bx-planet"></i></a>
                                <!-- <a href="javascript:void(0);" id="btnPageSettings" class="r2k-icon-btn" title="Column Settings"><i class="bx bx-cog"></i></a> -->
                                <div class="r2k-search-wrap">
                                    <i class="bx bx-search r2k-si"></i>
                                    <input type="text" id="SearchDetails" placeholder="Name, mobile, GSTIN...">
                                    <i class="bx bx-x r2k-clear d-none" id="clearSearch"></i>
                                </div>
                                <div class="btn-group r2k-toolbar-actions d-none" id="ActionsDD-Div">
                                    <button class="r2k-dd-btn dropdown-toggle" type="button" id="actionsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="bx bx-slider-alt"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="actionsDropdown">
                                        <li class="d-none" id="CloneOption">
                                            <a class="dropdown-item" href="javascript:void(0);" id="btnClone"><i class="bx bx-duplicate me-1"></i> Clone</a>
                                        </li>
                                        <li class="d-none" id="DeleteOption">
                                            <a class="dropdown-item text-danger" href="javascript:void(0);" id="btnDelete"><i class="bx bx-trash me-1"></i> Delete</a>
                                        </li>
                                        <li class="d-none" id="BulkSmsOption">
                                            <a class="dropdown-item" href="javascript:void(0);" id="btnBulkSms"><i class="bx bx-message-rounded me-1 text-info"></i> Send SMS</a>
                                        </li>
                                        <li class="d-none" id="BulkEmailOption">
                                            <a class="dropdown-item" href="javascript:void(0);" id="btnBulkEmail"><i class="bx bx-envelope me-1 text-primary"></i> Send Email</a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Table -->
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
                                        <th class="cust-name-sortable position-relative cursor-pointer" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Click for ascending order">
                                            <span class="sort-label">Customer <i class="bx bx-sort sort-icon ms-1"></i></span>
                                            <a href="javascript:void(0);" id="custTagFilter" class="text-body ms-1" onclick="toggleCustTagFilter(); event.stopPropagation();" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Filter by Tag"><i class="bx bx-filter-alt fs-6 align-middle"></i></a>
                                        </th>
                                        <th class="cust-area-sortable cursor-pointer" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Click for ascending order">Area <i class="bx bx-sort sort-icon ms-1"></i></th>
                                        <th>Mobile</th>
                                        <th>GSTIN / Company</th>
                                        <th class="cust-bal-sortable cursor-pointer" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Click for ascending order">Balance <i class="bx bx-sort sort-icon ms-1"></i></th>
                                        <th class="position-relative">
                                            Customer Type
                                            <a href="javascript:void(0);" id="custTypeFilter" class="text-body ms-1" onclick="toggleCustTypeFilter(); event.stopPropagation();" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Filter by Customer Type"><i class="bx bx-filter-alt fs-6 align-middle"></i></a>
                                        </th>
                                        <th>
                                            Last Updated
                                            <?php if ($showUserBtn): ?>
                                            <a href="javascript:void(0);" id="custUserFilterBtn" onclick="custToggleUserFilter(); event.stopPropagation();" style="color:#64748b;margin-left:4px;font-size:.85rem;vertical-align:middle;">
                                                <i class="bx bx-filter-alt" id="custUserFilterIcon"></i>
                                            </a>
                                            <?php endif; ?>
                                        </th>
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

            <!-- Customer Add / Edit / Clone Modal -->
            <div class="modal fade" id="CustomerFormModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" aria-hidden="true" style="padding:0!important;">
                <div class="modal-dialog modal-xl modal-dialog-scrollable" style="height:100vh;max-height:100vh;margin:0 auto;">
                    <div class="modal-content h-100 d-flex flex-column">

                        <div class="modal-header bg-white border-bottom d-flex align-items-center justify-content-between px-3 py-2 trans-theme">
                            <div class="d-flex align-items-center gap-3">
                                <div class="modal-doc-icon bg-primary bg-opacity-10">
                                    <i class="bx bx-user text-primary modal-doc-icon-inner"></i>
                                </div>
                                <div>
                                    <h5 class="modal-title mb-0" id="CustomerFormModalTitle">Customer</h5>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <button type="button" class="btn btn-sm btn-primary" id="CustomerFormSaveBtn">
                                    <i class="bx bx-check me-1"></i>Save
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger" data-bs-dismiss="modal">
                                    <i class="bx bx-x me-1"></i>Close
                                </button>
                            </div>
                        </div>

                        <div class="modal-body p-0 flex-grow-1 overflow-auto" id="CustomerFormModalBody">
                            <?php $this->load->view('customers/forms/modal_body', [
                                'FormMode'         => 'add',
                                'FormData'         => null,
                                'BankDetails'      => [],
                                'BillingAddr'      => null,
                                'ShippingAddr'     => null,
                                'CustomerTypeList' => $CustomerTypeList,
                                'OrgCCode'         => $OrgCCode,
                                'OrgCISO2'         => $OrgCISO2,
                                'JwtData'          => $JwtData,
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
<div id="custTagFilterBox" class="card mp-filterbox" style="min-width:220px;z-index:9999;display:none;position:fixed;"><?php $this->load->view('customers/tagfilter', ['Tags' => $Tags]); ?></div>
<div id="custTypeFilterBox" class="card mp-filterbox" style="min-width:220px;z-index:9999;display:none;position:fixed;"><?php $this->load->view('customers/typefilter', ['CustomerTypeList' => $CustomerTypeList]); ?></div>

<?php if ($showUserBtn): ?>
<?php $this->load->view('common/partials/_user_filter_box', [
    'OrgUsers'   => $OrgUsers,
    'BoxId'      => 'custUserFilterBox',
    'CheckClass' => 'cust-user-checkbox',
    'ApplyFn'    => 'custApplyUserFilter',
    'ResetFn'    => 'custResetUserFilter',
]); ?>
<?php endif; ?>

<?php $this->load->view('common/footer'); ?>

<script src="/js/customers.js"></script>
<script src="/js/common/pagecheckbox.js"></script>
<script src="/js/common/communication.js"></script>
<script src="/js/common/gstin_fetch.js"></script>
<script src="/js/common/bankdetails.js"></script>
<script src="/js/common/address.js"></script>

<script>
let ModuleId = <?php echo $ModuleId; ?>;
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
var OrgCountryISO2 = <?php echo json_encode($JwtData->User->OrgCISO2 ?? 'IN'); ?>;
var CustShowUserFilter = <?php echo $showUserBtn ? 'true' : 'false'; ?>;

$(function () {
    'use strict';

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

    // Header "New Customer" button mirrors the toolbar Create button
    $(document).on('click', '#btnCreateCustomerHeader', function () {
        openCustomerModal('add');
    });

    // ── Sticky pagination ──
    var $staticPag = $('#CustomersPagination');
    var $stickyPag = $('#custStickyPagination');

    function syncStickyPagination() {
        // Copy current pagination HTML into the sticky bar so page numbers stay in sync
        $stickyPag.find('.CustomersPagination').html($staticPag.html());
    }

    function toggleStickyPagination() {
        if (!$staticPag.length) return;
        var rect = $staticPag[0].getBoundingClientRect();
        var windowHeight = $(window).height();
        // Show sticky bar when the static pagination is fully below the visible viewport
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

    // ── Status tabs ──
    $(document).on('click', '.cust-tab', function (e) {
        e.preventDefault();
        $('.cust-tab').removeClass('active');
        $(this).addClass('active');
        var status = $(this).data('status') || 'All';
        if (status === 'All')           { delete Filter['IsActive']; }
        else if (status === 'Active')   { Filter['IsActive'] = 1; }
        else if (status === 'Inactive') { Filter['IsActive'] = 0; }
        PageNo = 0;
        getCustomersDetails(PageNo, RowLimit, Filter);
    });

    // ── Search ──
    $('#SearchDetails').on('keyup', inputDelay(function () {
        var val = $.trim($(this).val());
        $('#clearSearch').toggleClass('d-none', !val);
        delete Filter['SearchAllData'];
        if (val.length >= 3) Filter['SearchAllData'] = val;
        if (val.length === 0 || val.length >= 3) { PageNo = 0; getCustomersDetails(PageNo, RowLimit, Filter); }
    }, 400));

    $('#clearSearch').on('click', function () {
        $('#SearchDetails').val('');
        $(this).addClass('d-none');
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
        if (SelectedUIDs.length === 1) openCustomerModal('clone', SelectedUIDs[0]);
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
        var _tt = bootstrap.Tooltip.getInstance(this); if (_tt) _tt.dispose(); new bootstrap.Tooltip(this);
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
    window.toggleCustTagFilter = function () {
        var $box = $('#custTagFilterBox');
        if ($box.is(':visible')) { $box.hide(); return; }
        $('#custTypeFilterBox').hide();
        var rect = document.getElementById('custTagFilter').getBoundingClientRect();
        $box.css({ top: (rect.bottom + 4) + 'px', left: rect.left + 'px' }).show();
    };
    window.closeCustTagFilter = function () { $('#custTagFilterBox').hide(); };
    window.toggleAllCustTags = function (el) {
        var checked = $(el).is(':checked');
        $('#custTagList .cust-tag-chk').prop('checked', checked);
        $('#custTagSelectAllLabel').text(checked ? 'Deselect All' : 'Select All');
    };
    window.applyCustTagFilter = function () {
        var selected = $('.cust-tag-chk:checked').map(function () { return $(this).val(); }).get();
        if (selected.length) Filter['Tags'] = selected; else delete Filter['Tags'];
        $('#custTagFilterBox').hide();
        $('#custTagFilter').toggleClass('text-primary', !!selected.length);
        PageNo = 0; getCustomersDetails(PageNo, RowLimit, Filter);
    };
    window.resetCustTagFilter = function () {
        $('.cust-tag-chk').prop('checked', false);
        $('#selectAllCustTags').prop('checked', false);
        $('#custTagSelectAllLabel').text('Select All');
        delete Filter['Tags'];
        $('#custTagFilterBox').hide();
        $('#custTagFilter').removeClass('text-primary');
        PageNo = 0; getCustomersDetails(PageNo, RowLimit, Filter);
    };
    $(document).on('input', '#custTagSearch', function () {
        var term = $(this).val().toLowerCase();
        $('#custTagList .catg-list-item').each(function () {
            $(this).toggle($(this).text().toLowerCase().includes(term));
        });
    });
    $(document).on('change', '.cust-tag-chk', function () {
        var total = $('.cust-tag-chk').length, checked = $('.cust-tag-chk:checked').length;
        $('#selectAllCustTags').prop('checked', total === checked && total > 0);
        $('#custTagSelectAllLabel').text(total === checked && total > 0 ? 'Deselect All' : 'Select All');
    });

    // ── Customer Type filter ──
    window.toggleCustTypeFilter = function () {
        var $box = $('#custTypeFilterBox');
        if ($box.is(':visible')) { $box.hide(); return; }
        $('#custTagFilterBox').hide();
        var rect = document.getElementById('custTypeFilter').getBoundingClientRect();
        $box.css({ top: (rect.bottom + 4) + 'px', left: rect.left + 'px' }).show();
    };
    window.closeCustTypeFilter = function () { $('#custTypeFilterBox').hide(); };
    window.toggleAllCustTypes = function (el) {
        var checked = $(el).is(':checked');
        $('#custTypeList .cust-type-chk').prop('checked', checked);
        $('#custTypeSelectAllLabel').text(checked ? 'Deselect All' : 'Select All');
    };
    window.applyCustTypeFilter = function () {
        var selected = $('.cust-type-chk:checked').map(function () { return $(this).val(); }).get();
        if (selected.length) Filter['CustomerTypeUIDs'] = selected; else delete Filter['CustomerTypeUIDs'];
        $('#custTypeFilterBox').hide();
        $('#custTypeFilter').toggleClass('text-primary', !!selected.length);
        PageNo = 0; getCustomersDetails(PageNo, RowLimit, Filter);
    };
    window.resetCustTypeFilter = function () {
        $('.cust-type-chk').prop('checked', false);
        $('#selectAllCustTypes').prop('checked', false);
        $('#custTypeSelectAllLabel').text('Select All');
        delete Filter['CustomerTypeUIDs'];
        $('#custTypeFilterBox').hide();
        $('#custTypeFilter').removeClass('text-primary');
        PageNo = 0; getCustomersDetails(PageNo, RowLimit, Filter);
    };
    $(document).on('input', '#custTypeSearch', function () {
        var term = $(this).val().toLowerCase();
        $('#custTypeList .catg-list-item').each(function () {
            $(this).toggle($(this).text().toLowerCase().includes(term));
        });
    });
    $(document).on('change', '.cust-type-chk', function () {
        var total = $('.cust-type-chk').length, checked = $('.cust-type-chk:checked').length;
        $('#selectAllCustTypes').prop('checked', total === checked && total > 0);
        $('#custTypeSelectAllLabel').text(total === checked && total > 0 ? 'Deselect All' : 'Select All');
    });

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
        window.custToggleUserFilter = function () {
            var $box = $('#custUserFilterBox');
            if ($box.is(':visible')) { $box.hide(); return; }
            $('#custTagFilterBox, #custTypeFilterBox').hide();
            var btn  = document.getElementById('custUserFilterBtn');
            var rect = btn.getBoundingClientRect();
            $box.css({
                top:  (rect.bottom + window.scrollY + 4) + 'px',
                left: Math.max(4, rect.left + window.scrollX - 80) + 'px',
                display: 'flex',
            });
        };
        window.custApplyUserFilter = function () {
            var uids = [];
            $('.cust-user-checkbox:checked').each(function () { uids.push($(this).val()); });
            if (uids.length) {
                Filter['UpdatedByUIDs'] = uids;
                $('#custUserFilterIcon').css('color', '#0d6efd');
            } else {
                delete Filter['UpdatedByUIDs'];
                $('#custUserFilterIcon').css('color', '');
            }
            $('#custUserFilterBox').hide();
            PageNo = 0; getCustomersDetails(PageNo, RowLimit, Filter);
        };
        window.custResetUserFilter = function () {
            $('.cust-user-checkbox').prop('checked', false);
            delete Filter['UpdatedByUIDs'];
            $('#custUserFilterIcon').css('color', '');
            $('#custUserFilterBox').hide();
            PageNo = 0; getCustomersDetails(PageNo, RowLimit, Filter);
        };
    }

    // ── Close filter boxes on outside click ──
    $(document).on('click', function (e) {
        if (!$(e.target).closest('#custTagFilterBox, #custTagFilter').length)     $('#custTagFilterBox').hide();
        if (!$(e.target).closest('#custTypeFilterBox, #custTypeFilter').length)   $('#custTypeFilterBox').hide();
        if (!$(e.target).closest('#custUserFilterBox, #custUserFilterBtn').length) $('#custUserFilterBox').hide();
    });

});
</script>
