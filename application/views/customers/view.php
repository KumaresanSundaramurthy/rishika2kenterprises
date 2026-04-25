<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php $this->load->view('common/header'); ?>

<div class="layout-wrapper layout-content-navbar">
    <div class="layout-container">

        <?php $this->load->view('common/menu_view'); ?>

        <div class="layout-page">

            <div class="content-wrapper">
                <div class="container-xxl flex-grow-1 container-p-y">

                    <!-- ── Stat Cards ── -->
                    <?php $s = $CustStats ?? null; ?>
                    <div class="row g-3 mb-3">
                        <div class="col-6 col-md">
                            <div class="trans-stat-card stat-all">
                                <div class="trans-stat-label">Total Customers</div>
                                <div class="trans-stat-count"><?php echo number_format((int)($s->TotalCount ?? 0)); ?></div>
                                <div class="trans-stat-amount">&nbsp;</div>
                                <i class="bx bxs-group trans-stat-icon"></i>
                            </div>
                        </div>
                        <div class="col-6 col-md">
                            <div class="trans-stat-card stat-active">
                                <div class="trans-stat-label">Active</div>
                                <div class="trans-stat-count"><?php echo number_format((int)($s->ActiveCount ?? 0)); ?></div>
                                <div class="trans-stat-amount">&nbsp;</div>
                                <i class="bx bx-check-circle trans-stat-icon"></i>
                            </div>
                        </div>
                        <div class="col-6 col-md">
                            <div class="trans-stat-card stat-paid">
                                <div class="trans-stat-label">This Month</div>
                                <div class="trans-stat-count"><?php echo number_format((int)($s->MonthCount ?? 0)); ?></div>
                                <div class="trans-stat-amount">&nbsp;</div>
                                <i class="bx bx-calendar trans-stat-icon"></i>
                            </div>
                        </div>
                        <div class="col-6 col-md">
                            <div class="trans-stat-card stat-converted">
                                <div class="trans-stat-label">This Financial Year</div>
                                <div class="trans-stat-count"><?php echo number_format((int)($s->FYCount ?? 0)); ?></div>
                                <div class="trans-stat-amount">&nbsp;</div>
                                <i class="bx bx-trending-up trans-stat-icon"></i>
                            </div>
                        </div>
                        <div class="col-6 col-md">
                            <div class="trans-stat-card stat-draft">
                                <div class="trans-stat-label">Last Month</div>
                                <div class="trans-stat-count"><?php echo number_format((int)($s->LastMonthCount ?? 0)); ?></div>
                                <div class="trans-stat-amount">&nbsp;</div>
                                <i class="bx bx-history trans-stat-icon"></i>
                            </div>
                        </div>
                    </div>

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
                                <a href="javascript:void(0);" id="btnPageSettings" class="r2k-icon-btn" title="Column Settings"><i class="bx bx-cog"></i></a>
                                <div class="r2k-search-wrap">
                                    <i class="bx bx-search r2k-si"></i>
                                    <input type="text" id="SearchDetails" placeholder="Name, mobile, GSTIN...">
                                    <i class="bx bx-x r2k-clear d-none" id="clearSearch"></i>
                                </div>
                                <div class="btn-group r2k-toolbar-actions" id="ActionsDD-Div">
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
                                        <li class="dropdown-submenu">
                                            <a class="dropdown-item" href="javascript:void(0);"><i class="bx bx-export me-1"></i> Export</a>
                                            <ul class="dropdown-menu">
                                                <li><a class="dropdown-item" href="javascript:void(0);" id="btnExportPrint"><i class="bx bx-printer me-1"></i> Print</a></li>
                                                <li><a class="dropdown-item" href="javascript:void(0);" id="btnExportCSV"><i class="bx bx-file me-1"></i> CSV</a></li>
                                                <li><a class="dropdown-item" href="javascript:void(0);" id="btnExportExcel"><i class="bx bxs-file-export me-1"></i> Excel</a></li>
                                                <li><a class="dropdown-item" href="javascript:void(0);" id="btnExportPDF"><i class="bx bxs-file-pdf me-1"></i> PDF</a></li>
                                            </ul>
                                        </li>
                                    </ul>
                                </div>
                                <a href="/customers/create" class="r2k-create-btn"><i class="bx bx-plus"></i> Create</a>
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
                                            <div id="custTagFilterBox" class="card mp-filterbox position-absolute" style="min-width:220px;z-index:1056;display:none;top:100%;left:0;"><?php $this->load->view('customers/tagfilter', ['Tags' => $Tags]); ?></div>
                                        </th>
                                        <th class="cust-area-sortable cursor-pointer" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Click for ascending order">Area <i class="bx bx-sort sort-icon ms-1"></i></th>
                                        <th>Mobile</th>
                                        <th>GSTIN / Company</th>
                                        <th class="cust-bal-sortable cursor-pointer" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Click for ascending order">Balance <i class="bx bx-sort sort-icon ms-1"></i></th>
                                        <th class="position-relative">
                                            Customer Type
                                            <a href="javascript:void(0);" id="custTypeFilter" class="text-body ms-1" onclick="toggleCustTypeFilter(); event.stopPropagation();" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Filter by Customer Type"><i class="bx bx-filter-alt fs-6 align-middle"></i></a>
                                            <div id="custTypeFilterBox" class="card mp-filterbox position-absolute" style="min-width:220px;z-index:1056;display:none;top:100%;left:0;"><?php $this->load->view('customers/typefilter', ['CustomerTypeList' => $CustomerTypeList]); ?></div>
                                        </th>
                                        <th>Last Updated</th>
                                        <th class="th-act" style="width:80px">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="r2k-tbody table-border-bottom-0">
                                    <?php echo $ModRowData; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <hr class="my-0">
                        <div class="row mx-3 my-2 justify-content-between align-items-center CustomersPagination" id="CustomersPagination">
                            <?php echo $ModPagination; ?>
                        </div>

                    </div>

                </div>
            </div>

            <?php $this->load->view('common/imagepreview_modal'); ?>
            <?php $this->load->view('common/settings_modal'); ?>
            <?php $this->load->view('common/footer_desc'); ?>
        </div>

    </div>
</div>

<?php $this->load->view('common/footer'); ?>

<script src="/js/customers.js"></script>

<script src="/js/common/pagecheckbox.js"></script>

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

$(function () {
    'use strict';

    $('#SearchDetails').val('');
    $(ModuleRow).prop('checked', false).trigger('change');

    baseExportFunctions();
    basePaginationFunc(ModulePag, getCustomersDetails);
    baseRefreshPageFunc('.PageRefresh', getCustomersDetails);
    basePageHeaderFunc(ModuleHeader, ModuleTable, ModuleRow);

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
        if (SelectedUIDs.length === 1) window.location.href = '/customers/' + SelectedUIDs[0] + '/clone';
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
        $('#custTypeFilterBox').hide();
        $box.toggle();
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
        $('#custTagFilterBox').hide();
        $box.toggle();
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

    // ── Close filter boxes on outside click ──
    $(document).on('click', function (e) {
        if (!$(e.target).closest('#custTagFilterBox, #custTagFilter').length)  $('#custTagFilterBox').hide();
        if (!$(e.target).closest('#custTypeFilterBox, #custTypeFilter').length) $('#custTypeFilterBox').hide();
    });

});
</script>
