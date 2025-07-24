<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php $this->load->view('common/header'); ?>

<!-- Layout wrapper -->
<div class="layout-wrapper layout-content-navbar">
    <div class="layout-container">

        <?php $this->load->view('common/menu_view'); ?>

        <!-- Layout container -->
        <div class="layout-page">

            <?php $this->load->view('common/navbar_view'); ?>

            <!-- Content wrapper -->
            <div class="content-wrapper">

                <div class="container-xxl flex-grow-1 container-p-y">

                    <div class="card">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="d-flex flex-column flex-md-row justify-content-between align-items-center p-3">
                                    <ul class="nav nav-pills nav nav-pills flex-row" role="tablist">
                                        <li class="nav-item">
                                            <a class="nav-link active" role="tab" data-bs-toggle="tab" data-bs-target="#NavAccountPage" aria-controls="NavAccountPage" aria-selected="true" href="javascript: void(0);"><i class="bx bx-user me-1"></i> Account</a>
                                        </li>
                                        <li class="nav-item d-none">
                                            <a class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#NavGroupPage" aria-controls="NavGroupPage" aria-selected="true" href="javascript: void(0);"><i class="bx bx-group me-1"></i> Groups</a>
                                        </li>
                                    </ul>
                                    <div class="d-flex mt-2 mt-md-0">
                                        <a href="javascript: void(0);" class="btn PageRefresh p-2 me-0"><i class="bx bx-refresh fs-4"></i></a>
                                        <a href="javascript: void(0);" id="btnPageSettings" class="btn p-2"><i class="bx bx-cog fs-4"></i></a>
                                        <div class="position-relative me-2">
                                            <input type="text" class="form-control SearchDetails" name="SearchDetails" id="SearchDetails" placeholder="Search details..." data-toggle="tooltip" title="Please type at least 3 characters to search" />
                                            <i class="bx bx-x position-absolute top-50 end-0 translate-middle-y me-3 text-muted cursor-pointer d-none" id="clearSearch"></i>
                                        </div>
                                        <div class="btn-group" id="ActionsDD-Div">
                                            <button class="btn btn-label-secondary dropdown-toggle me-2" type="button" id="actionsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                                <span class="d-flex align-items-center gap-2">
                                                    <i class="icon-base bx bx-slider-alt icon-xs"></i>
                                                    <span class="d-none d-sm-inline-block">Actions</span>
                                                </span>
                                            </button>
                                            <ul class="dropdown-menu" aria-labelledby="actionsDropdown">
                                                <li class="d-none" id="CloneOption">
                                                    <a class="dropdown-item" href="javascript: void(0);" id="btnClone">
                                                        <i class="bx bx-duplicate me-1"></i> Clone
                                                    </a>
                                                </li>
                                                <li class="d-none" id="DeleteOption">
                                                    <a class="dropdown-item text-danger" href="javascript: void(0);" id="btnDelete">
                                                        <i class="bx bx-trash me-1"></i> Delete
                                                    </a>
                                                </li>
                                                <li class="dropdown-submenu">
                                                    <a class="dropdown-item" href="javascript: void(0);">
                                                        <i class="bx bx-export me-1"></i> Export
                                                    </a>
                                                    <ul class="dropdown-menu">
                                                        <li>
                                                            <a class="dropdown-item" href="javascript: void(0);" id="btnExportPrint">
                                                                <i class="bx bx-printer me-1"></i> Print
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a class="dropdown-item" href="javascript: void(0);" id="btnExportCSV">
                                                                <i class="bx bx-file me-1"></i> CSV
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a class="dropdown-item" href="javascript: void(0);" id="btnExportExcel">
                                                                <i class="bx bxs-file-export me-1"></i> Excel
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a class="dropdown-item" href="javascript: void(0);" id="btnExportPDF">
                                                                <i class="bx bxs-file-pdf me-1"></i> PDF
                                                            </a>
                                                        </li>
                                                    </ul>
                                                </li>
                                            </ul>
                                        </div>
                                        <a href="/customers/add" class="btn btn-primary px-3"><i class='bx bx-plus'></i> New Customer</a>
                                    </div>
                                </div>
                                <div class="tab-content p-0">

                                    <div class="tab-pane fade show active" id="NavAccountPage" role="tabpanel">

                                        <div class="table-responsive text-nowrap h-100 tablecard">
                                            <table class="table table-sm table-striped table-hover" id="CustomersTable">
                                                <thead>
                                                    <tr>
                                                        <th class="table-checkbox">
                                                            <div class="form-check form-check-inline">
                                                                <input class="form-check-input table-chkbox customerHeaderCheck" type="checkbox">
                                                            </div>
                                                        </th>
                                                        <th class="table-serialno">S.No</th>
                                                        <?php foreach (array_column($ModuleColumns, 'DisplayName') as $ItemKey => $ItemVal) { ?>
                                                            <th <?php echo $ModuleColumns[$ItemKey]->MainPageColumnAddon; ?>><?php echo $ItemVal; ?></th>
                                                        <?php } ?>
                                                        <th class="text-center">Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="table-border-bottom-0">
                                                    <?php echo $ModDataList; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                        <hr class="my-0" />
                                        <div class="row mx-3 justify-content-between CustomersPagination" id="CustomersPagination">
                                            <?php echo $ModDataPagination; ?>
                                        </div>

                                    </div>

                                    <div class="tab-pane fade" id="NavGroupPage" role="tabpanel">
                                        <p>
                                            Donut dragée jelly pie halvah. Danish gingerbread bonbon cookie wafer candy oat cake ice
                                            cream. Gummies halvah tootsie roll muffin biscuit icing dessert gingerbread. Pastry ice cream
                                            cheesecake fruitcake.
                                        </p>
                                        <p class="mb-0">
                                            Jelly-o jelly beans icing pastry cake cake lemon drops. Muffin muffin pie tiramisu halvah
                                            cotton candy liquorice caramels.
                                        </p>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>

                </div>

            </div>
            <!-- Content wrapper -->

            <?php $this->load->view('common/settings_modal'); ?>

            <?php $this->load->view('common/footer_desc'); ?>

        </div>

    </div>
</div>

<?php $this->load->view('common/footer'); ?>

<script src="/js/customers.js"></script>

<script>
let ModuleUIDs = <?php echo json_encode($ModDataUIDs ?: []); ?>;
let ModuleId = <?php echo $ModuleId; ?>;
const ModuleTable = '#CustomersTable';
const ModulePag = '.CustomersPagination';
const ModuleHeader = '.customerHeaderCheck';
const ModuleRow = '.customerCheck';
$(function() {
    'use strict'

    $('#SearchDetails').val('');
    $(ModuleHeader + ',' + ModuleRow).prop('checked', false).trigger('change');

    $(ModulePag).on('click', 'a', function(e) {
        e.preventDefault();
        PageNo = $(this).attr('data-ci-pagination-page');
        getCustomersDetails(PageNo, RowLimit, Filter);
    });

    $(document).on('click', '.PageRefresh', function(e) {
        e.preventDefault();
        getCustomersDetails(0, RowLimit, Filter);
    });

    $(ModuleHeader).click(function() {
        allTableHeadersCheckbox($(this), ModuleUIDs, ModuleTable, ModuleHeader, ModuleRow);
    });

    $(document).on('click', ModuleRow, function() {
        onClickOfCheckbox($(this), ModuleUIDs, ModuleHeader);
        MultipleDeleteOption();
    });

    $('.SearchDetails').keyup(inputDelay(function(e) {
        PageNo = 0;
        let searchText = $('#SearchDetails').val();
        if (searchText.length >= 3) {
            delete Filter['SearchAllData'];
            $('#clearSearch').removeClass('d-none');
            if (searchText) {
                Filter['SearchAllData'] = searchText;
            }
            $('#SearchDetails').blur();
            getCustomersDetails(PageNo, RowLimit, Filter);
        }
    }, 500));

    $('#clearSearch').click(function(e) {
            e.preventDefault();
        var searchText = $('#SearchDetails').val();
        $('#SearchDetails').val('');
        $('#clearSearch').addClass('d-none');
        if ($.trim(searchText) != '') {
            delete Filter['SearchAllData'];
            $('#SearchDetails').blur();
            getCustomersDetails(PageNo, RowLimit, Filter);
        }
    });

    $('#btnExportPrint').click(function(e) {
        e.preventDefault();
        baseExportFunctionality(1, 'PrintPreview', 'Customer_Data', 'Customer');
    });

    $('#btnExportCSV').click(function(e) {
        e.preventDefault();
        baseExportFunctionality(1, 'ExportCSV', 'Customer_Data', 'Customer');
    });

    $('#btnExportPDF').click(function(e) {
        e.preventDefault();
        baseExportFunctionality(1, 'ExportPDF', 'Customer_Data', 'Customer');
    });

    $('#btnExportExcel').click(function(e) {
        e.preventDefault();
        baseExportFunctionality(1, 'ExportExcel', 'Customer_Data', 'Customer');
    });

    $('#exportSelectedItemsBtn').click(function(e) {
        e.preventDefault();
        baseExportFunctionality(2, expActionType, 'Customer_Data', 'Customer');
    });

    $('#clearExportClose').click(function(e) {
        e.preventDefault();
        exportModalCloseFunc(ModuleTable, ModuleHeader, ModuleRow, ModuleUIDs);
    });

    $(document).on('click', '.DeleteCustomer', function(e) {
        e.preventDefault();
        var GetId = $(this).data('customeruid');
        if (GetId) {
            Swal.fire({
                title: "Do you want to delete the customer?",
                text: "You won't be able to revert this!",
                icon: "info",
                showCancelButton: true,
                confirmButtonColor: "#d33",
                confirmButtonText: "Yes, delete it!",
                cancelButtonColor: "#3085d6",
            }).then((result) => {
                if (result.isConfirmed) {
                    deleteCustomer(GetId);
                }
            });
        }
    });

});
</script>