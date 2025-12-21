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
                                            <a class="nav-link <?php echo $ActiveTabData == 'item' ? 'active' : ''; ?> TabPane disabled" data-id="Item" data-moduleid="<?php echo $ItemModuleId; ?>" role="tab" data-bs-toggle="tab" data-bs-target="#NavItemPage" aria-controls="NavItemPage" aria-selected="true" href="javascript: void(0);"><i class="bx bx-package me-1"></i> Item</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link <?php echo $ActiveTabData == 'category' ? 'active' : ''; ?> TabPane disabled" data-id="Categories" data-moduleid="<?php echo $CategoryModuleId; ?>" role="tab" data-bs-toggle="tab" data-bs-target="#NavCategoriesPage" aria-controls="NavCategoriesPage" aria-selected="true" href="javascript: void(0);"><i class="bx bx-layer me-1"></i> Categories</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link <?php echo $ActiveTabData == 'size' ? 'active' : ''; ?> TabPane disabled" data-id="Sizes" data-moduleid="<?php echo $SizeModuleId; ?>" role="tab" data-bs-toggle="tab" data-bs-target="#NavSizesPage" aria-controls="NavSizesPage" aria-selected="true" href="javascript: void(0);"><i class="bx bx-ruler me-1"></i> Sizes</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link <?php echo $ActiveTabData == 'brand' ? 'active' : ''; ?> TabPane disabled" data-id="Brands" data-moduleid="<?php echo $BrandModuleId; ?>" role="tab" data-bs-toggle="tab" data-bs-target="#NavBrandsPage" aria-controls="NavBrandsPage" aria-selected="true" href="javascript: void(0);"><i class="bx bx-badge-check me-1"></i> Brands</a>
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
                                                    <span class="d-none d-sm-inline-block"></span>
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
                                        <a href="javascript: void(0);" class="btn btn-primary px-3 addItem <?php echo $ActiveTabData == 'item' ? '' : 'd-none'; ?>" id="NewItem"> Create Item</a>
                                        <a href="javascript: void(0);" class="btn btn-primary px-3 addCategory <?php echo $ActiveTabData == 'category' ? '' : 'd-none'; ?>" id="NewCategory"> Create Category</a>
                                        <a href="javascript: void(0);" class="btn btn-primary px-3 addSizes <?php echo $ActiveTabData == 'size' ? '' : 'd-none'; ?>" id="NewSizes"> Create Size</a>
                                        <a href="javascript: void(0);" class="btn btn-primary px-3 addBrands <?php echo $ActiveTabData == 'brand' ? '' : 'd-none'; ?>" id="NewBrands"> Create Brand</a>
                                    </div>
                                </div>
                                <div class="tab-content p-0">

                                    <div class="tab-pane fade <?php echo $ActiveTabData == 'item' ? 'show active' : ''; ?>" id="NavItemPage" role="tabpanel">

                                        <div class="table-responsive text-nowrap h-100 tablecard">
                                            <table class="table table-sm table-striped table-hover" id="ProductsTable">
                                                <thead>
                                                    <tr>
                                                        <th class="table-checkbox text-center align-middle">
                                                            <div class="form-check d-flex justify-content-center align-items-center mb-0">
                                                                <input class="form-check-input table-chkbox productsHeaderCheck" type="checkbox">
                                                            </div>
                                                        </th>
                                                        <th class="table-serialno <?php echo $JwtData->GenSettings->SerialNoDisplay == 1 ? '' : 'd-none'; ?>">S.No</th>
                                                        <?php foreach (array_column($ItemColumns, 'DisplayName') as $ItemKey => $ItemVal) {
                                                            if ($ItemVal === 'Storage' && ($JwtData->GenSettings->EnableStorage ?? 0) != 1) {
                                                                continue;
                                                            } ?>
                                                            <th <?php echo $ItemColumns[$ItemKey]->MainPageColumnAddon; ?>>

                                                                <?php echo $ItemVal; ?>

                                                                <?php if ($ItemColumns[$ItemKey]->MPSortApplicable == 1) {
                                                                    echo '<i class="bx bx-sort ms-1 cursor-pointer"></i>';
                                                                } ?>

                                                                <?php if ($ItemVal == 'Product Type' && $ItemColumns[$ItemKey]->MPFilterApplicable == 1) { ?>

                                                                    <a href="javascript:void(0);" class="text-body ms-1 filter-toggle" data-target="#prodTypeFilterBox">
                                                                        <i class="bx bx-filter-alt fs-5 align-middle"></i>
                                                                    </a>

                                                                    <div id="prodTypeFilterBox" class="card shadow mp-filterbox position-absolute p-3">

                                                                    <?php if (sizeof($fltCategoryData) > 0) {
                                                                        echo $this->load->view('products/items/prodtypefilter', ['ProdTypeInfo' => $ProdTypeInfo], TRUE);
                                                                    } ?>

                                                                    </div>

                                                                <?php } ?>

                                                                <?php if ($ItemVal == 'Category' && $ItemColumns[$ItemKey]->MPFilterApplicable == 1) { ?>

                                                                    <a href="javascript:void(0);" class="text-body ms-1 filter-toggle" data-target="#categoryFilterBox">
                                                                        <i class="bx bx-filter-alt fs-5 align-middle"></i>
                                                                    </a>

                                                                    <div id="categoryFilterBox" class="card shadow mp-filterbox position-absolute p-3">

                                                                    <?php if (sizeof($fltCategoryData) > 0) {
                                                                        echo $this->load->view('products/items/catgfilter', ['Categories' => $fltCategoryData], TRUE);
                                                                    } ?>

                                                                    </div>

                                                                <?php } ?>

                                                                <?php if ($ItemVal == 'Storage' && $JwtData->GenSettings->EnableStorage == 1 && $ItemColumns[$ItemKey]->MPFilterApplicable == 1) { ?>

                                                                    <a href="javascript:void(0);" class="text-body ms-1 filter-toggle" data-target="#storageFilterBox">
                                                                        <i class="bx bx-filter-alt fs-5 align-middle"></i>
                                                                    </a>

                                                                    <div id="storageFilterBox" class="card shadow mp-filterbox position-absolute p-3">

                                                                    <?php if (sizeof($fltStorageData) > 0) {
                                                                        echo $this->load->view('products/items/storagefilter', ['Storage' => $fltStorageData], TRUE);
                                                                    } ?>

                                                                    </div>

                                                                <?php } ?>

                                                            </th>
                                                        <?php } ?>
                                                        <th class="text-center">Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="table-border-bottom-0">
                                                    <?php if ($ActiveTabData == 'item') {
                                                        echo $ModRowData;
                                                    } else {
                                                        $PageData['DataLists'] = [];
                                                        echo $this->load->view('products/items/list', $PageData, TRUE);
                                                    } ?>
                                                </tbody>
                                            </table>
                                        </div>
                                        <hr class="my-0" />
                                        <div class="row mx-3 justify-content-between ProductsPagination" id="ProductsPagination">
                                            <?php echo $ActiveTabData == 'item' ? $ModPagination : ''; ?>
                                        </div>

                                    </div>

                                    <div class="tab-pane fade <?php echo $ActiveTabData == 'category' ? 'show active' : ''; ?>" id="NavCategoriesPage" role="tabpanel">

                                        <div class="table-responsive text-nowrap h-100 tablecard">
                                            <table class="table table-sm table-striped table-hover" id="CategoriesTable">
                                                <thead>
                                                    <tr>
                                                        <th class="table-checkbox">
                                                            <div class="form-check form-check-inline">
                                                                <input class="form-check-input table-chkbox categoryHeaderCheck" type="checkbox">
                                                            </div>
                                                        </th>
                                                        <th class="table-serialno <?php echo $JwtData->GenSettings->SerialNoDisplay == 1 ? '' : 'd-none'; ?>">S.No</th>
                                                        <?php foreach (array_column($CategoryColumns, 'DisplayName') as $CtgKey => $CtgVal) { ?>
                                                            <th <?php echo $CategoryColumns[$CtgKey]->MainPageColumnAddon; ?>>
                                                                
                                                                <?php echo $CtgVal; ?>
                                                                <?php if ($CategoryColumns[$CtgKey]->MPSortApplicable == 1) {
                                                                    echo '<i class="bx bx-sort ms-1 cursor-pointer"></i>';
                                                                } ?>

                                                            </th>
                                                        <?php } ?>
                                                        <th class="text-center">Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="table-border-bottom-0">
                                                    <?php if ($ActiveTabData == 'category') {
                                                        echo $ModRowData;
                                                    } else {
                                                        $PageData['DataLists'] = [];
                                                        echo $this->load->view('products/categories/list', $PageData, TRUE);
                                                    } ?>
                                                </tbody>
                                            </table>
                                        </div>
                                        <hr class="my-0" />
                                        <div class="row mx-3 justify-content-between CategoriesPagination" id="CategoriesPagination">
                                            <?php echo $ActiveTabData == 'category' ? $ModPagination : ''; ?>
                                        </div>

                                    </div>

                                    <div class="tab-pane fade <?php echo $ActiveTabData == 'size' ? 'show active' : ''; ?>" id="NavSizesPage" role="tabpanel">

                                        <div class="table-responsive text-nowrap h-100 tablecard">
                                            <table class="table table-sm table-striped table-hover" id="SizesTable">
                                                <thead>
                                                    <tr>
                                                        <th class="table-checkbox">
                                                            <div class="form-check form-check-inline">
                                                                <input class="form-check-input table-chkbox sizeHeaderCheck" type="checkbox">
                                                            </div>
                                                        </th>
                                                        <th class="table-serialno <?php echo $JwtData->GenSettings->SerialNoDisplay == 1 ? '' : 'd-none'; ?>">S.No</th>
                                                        <?php foreach (array_column($SizeColumns, 'DisplayName') as $SzKey => $SzVal) { ?>
                                                            <th <?php echo $SizeColumns[$SzKey]->MainPageColumnAddon; ?>>
                                                                <?php echo $SzVal; ?>
                                                                <?php if ($SizeColumns[$SzKey]->MPSortApplicable == 1) {
                                                                    echo '<i class="bx bx-sort ms-1 cursor-pointer"></i>';
                                                                } ?>
                                                            </th>
                                                        <?php } ?>
                                                        <th class="text-center">Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="table-border-bottom-0">
                                                    <?php if ($ActiveTabData == 'size') {
                                                        echo $ModRowData;
                                                    } else {
                                                        $PageData['DataLists'] = [];
                                                        echo $this->load->view('products/sizes/list', $PageData, TRUE);
                                                    } ?>
                                                </tbody>
                                            </table>
                                        </div>
                                        <hr class="my-0" />
                                        <div class="row mx-3 justify-content-between SizesPagination" id="SizesPagination">
                                            <?php echo $ActiveTabData == 'size' ? $ModPagination : ''; ?>
                                        </div>

                                    </div>

                                    <div class="tab-pane fade <?php echo $ActiveTabData == 'brand' ? 'show active' : ''; ?>" id="NavBrandsPage" role="tabpanel">

                                        <div class="table-responsive text-nowrap h-100 tablecard">
                                            <table class="table table-sm table-striped table-hover" id="BrandsTable">
                                                <thead>
                                                    <tr>
                                                        <th class="table-checkbox">
                                                            <div class="form-check form-check-inline">
                                                                <input class="form-check-input table-chkbox brandHeaderCheck" type="checkbox">
                                                            </div>
                                                        </th>
                                                        <th class="table-serialno <?php echo $JwtData->GenSettings->SerialNoDisplay == 1 ? '' : 'd-none'; ?>">S.No</th>
                                                        <?php foreach (array_column($BrandColumns, 'DisplayName') as $BrdKey => $BrdVal) { ?>
                                                            <th <?php echo $BrandColumns[$BrdKey]->MainPageColumnAddon; ?>>
                                                                <?php echo $BrdVal; ?>
                                                                <?php if ($BrandColumns[$BrdKey]->MPSortApplicable == 1) {
                                                                    echo '<i class="bx bx-sort ms-1 cursor-pointer"></i>';
                                                                } ?>
                                                            </th>
                                                        <?php } ?>
                                                        <th class="text-center">Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="table-border-bottom-0">
                                                    <?php if ($ActiveTabData == 'brand') {
                                                        echo $ModRowData;
                                                    } else {
                                                        $PageData['DataLists'] = [];
                                                        echo $this->load->view('products/brands/list', $PageData, TRUE);
                                                    } ?>
                                                </tbody>
                                            </table>
                                        </div>
                                        <hr class="my-0" />
                                        <div class="row mx-3 justify-content-between BrandsPagination" id="BrandsPagination">
                                            <?php echo $ActiveTabData == 'brand' ? $ModPagination : ''; ?>
                                        </div>

                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
            <!-- Content wrapper -->

            <?php $this->load->view('common/settings_modal'); ?>

            <?php $this->load->view('products/modals/items'); ?>
            <?php $this->load->view('products/modals/category'); ?>
            <?php $this->load->view('products/modals/sizes'); ?>
            <?php $this->load->view('products/modals/brands'); ?>

            <?php $this->load->view('common/footer_desc'); ?>

        </div>

    </div>
</div>

<?php $this->load->view('common/footer'); ?>

<script src="/js/products.js"></script>
<script src="/js/common/pagecheckbox.js"></script>

<script>
let ItemModuleId = <?php echo $ItemModuleId; ?>;
const ProdTable = '#ProductsTable';
const ProdPag = '.ProductsPagination';
const ProdHeader = '.productsHeaderCheck';
const ProdRow = '.productsCheck';
let CategoryModuleId = <?php echo $CategoryModuleId; ?>;
const CatgTable = '#CategoriesTable';
const CatgPag = '.CategoriesPagination';
const CatgHeader = '.categoryHeaderCheck';
const CatgRow = '.categoryCheck';
let SizeModuleId = <?php echo $SizeModuleId; ?>;
const SizeTable = '#SizesTable';
const SizePag = '.SizesPagination';
const SizeHeader = '.sizeHeaderCheck';
const SizeRow = '.sizesCheck';
let BrandModuleId = <?php echo $BrandModuleId; ?>;
const BrandTable = '#BrandsTable';
const BrandPag = '.BrandsPagination';
const BrandHeader = '.brandHeaderCheck';
const BrandRow = '.brandsCheck';
let ActiveTabId = '<?php echo $ActiveTabName; ?>';
let ActiveTabModuleId = <?php echo $ActiveModuleId; ?>;
var EnableStorage = <?php echo $JwtData->GenSettings->EnableStorage; ?>;
var CommonRowColumnDisp = 1;
let imgData;
let sortState = 0;
let catgSortState = 0;
let sizeSortState = 0;
let brandSortState = 0;
$(function() {
    'use strict'

    $('#SearchDetails').val('');
    $(ProdHeader + ',' + ProdRow).prop('checked', false).trigger('change');

    $('.TabPane').click(function(e) {
        e.preventDefault();
        var TabValue = $(this).data('id');
        if (TabValue) {
            SelectedUIDs = [];
            ActiveTabId = TabValue;
            ActiveTabModuleId = $(this).data('moduleid');
            $('#NewItem,#NewCategory,#NewSizes,#NewBrands,#CloneOption,#ItemCategory-Div').addClass('d-none');
            $('#SearchDetails').val('');
            PageNo = 0;
            Filter = {};
            if (ActiveTabId == 'Item') {
                $('#NewItem,#ItemCategory-Div').removeClass('d-none');
                var itemLen = $(ProdTable + ' ' + ProdRow).length;
                if (itemLen == 0) {
                    getProductDetails(PageNo, RowLimit, Filter);
                } else {
                    $(ProdHeader).prop('checked', false);
                    unSelectTableRecords(ProdTable, ProdRow);
                }
            } else if (ActiveTabId == 'Categories') {
                $('#NewCategory').removeClass('d-none');
                var catgLen = $(CatgTable + ' ' + CatgRow).length;
                if (catgLen == 0) {
                    getCategoriesDetails(PageNo, RowLimit, Filter);
                } else {
                    $(CatgHeader).prop('checked', false);
                    unSelectTableRecords(CatgTable, CatgRow);
                }
            } else if (ActiveTabId == 'Sizes') {
                $('#NewSizes').removeClass('d-none');
                var sizLen = $(SizeTable + ' ' + SizeRow).length;
                if (sizLen == 0) {
                    getSizesDetails(PageNo, RowLimit, Filter);
                } else {
                    $(SizeHeader).prop('checked', false);
                    unSelectTableRecords(SizeTable, SizeRow);
                }
            } else if (ActiveTabId == 'Brands') {
                $('#NewBrands').removeClass('d-none');
                var brndLen = $(BrandTable + ' ' + BrandRow).length;
                if (brndLen == 0) {
                    getBrandsDetails(PageNo, RowLimit, Filter);
                } else {
                    $(BrandHeader).prop('checked', false);
                    unSelectTableRecords(BrandTable, BrandRow);
                }
            }
        }
    });

    $('.SearchDetails').keyup(inputDelay(function(e) {
        PageNo = 0;
        let searchText = $('#SearchDetails').val();
        if (searchText.length >= 3) {
            SelectedUIDs = [];
            delete Filter['SearchAllData'];
            $('#clearSearch').removeClass('d-none');
            if (searchText) {
                Filter['SearchAllData'] = searchText;
            }
            $('#SearchDetails').blur();
            showProductPageDetails();
        }
    }, 500));

    $('#clearSearch').click(function(e) {
        e.preventDefault();
        var searchText = $('#SearchDetails').val();
        $('#SearchDetails').val('');
        $('#clearSearch').addClass('d-none');
        if ($.trim(searchText) != '') {
            PageNo = 0;
            SelectedUIDs = [];
            delete Filter['SearchAllData'];
            $('#SearchDetails').blur();
            showProductPageDetails();
        }
    });

    $('#btnDelete').click(function(e) {
        e.preventDefault();
        if (SelectedUIDs.length > 0) {
            let DeleteContent;
            if (ActiveTabId == 'Item') {
                DeleteContent = 'Do you want to delete all the selected product?';
            } else if (ActiveTabId == 'Categories') {
                DeleteContent = 'Do you want to delete all the selected category?';
            } else if (ActiveTabId == 'Sizes') {
                DeleteContent = 'Do you want to delete all the selected size?';
            } else if (ActiveTabId == 'Brands') {
                DeleteContent = 'Do you want to delete all the selected brand?';
            }
            Swal.fire({
                title: DeleteContent,
                text: "You won't be able to revert this!",
                icon: "info",
                showCancelButton: true,
                confirmButtonColor: "#d33",
                confirmButtonText: "Yes, delete it!",
                cancelButtonColor: "#3085d6",
            }).then((result) => {
                if (result.isConfirmed) {
                    if (ActiveTabId == 'Item') {
                        deleteMultipleProduct();
                    } else if (ActiveTabId == 'Categories') {
                        deleteMultipleCategory();
                    } else if (ActiveTabId == 'Sizes') {
                        deleteMultipleSize();
                    } else if (ActiveTabId == 'Brands') {
                        deleteMultipleBrand();
                    }
                }
            });
        }
    });

    commonExportFunctions();

    $('#selectAllCategories').on('change', function() {
        const isChecked = $(this).is(':checked');
        $('.category-checkbox').prop('checked', isChecked);
    });

    $('#categorySearch').on('input', function() {
        const searchTerm = $(this).val().toLowerCase();
        $('#categoryList .form-check').each(function() {
            const labelText = $(this).text().toLowerCase();
            $(this).toggle(labelText.includes(searchTerm));
        });
    });

    $('.category-checkbox').on('change', function() {
        const total = $('.category-checkbox').length;
        const checked = $('.category-checkbox:checked').length;
        $('#selectAllCategories').prop('checked', total === checked);
        $('#selectAllLabel').text(total === checked ? 'Clear All' : 'Select All');
    });

    $('#selectAllStorage').on('change', function() {
        const isChecked = $(this).is(':checked');
        $('.storage-checkbox').prop('checked', isChecked);
    });

    $('#storageSearch').on('input', function() {
        const searchTerm = $(this).val().toLowerCase();
        $('#storageList .form-check').each(function() {
            const labelText = $(this).text().toLowerCase();
            $(this).toggle(labelText.includes(searchTerm));
        });
    });

    $('.storage-checkbox').on('change', function() {
        const total = $('.storage-checkbox').length;
        const checked = $('.storage-checkbox:checked').length;
        $('#selectAllStorage').prop('checked', total === checked);
        $('#str_selectAllLabel').text(total === checked ? 'Clear All' : 'Select All');
    });

    /** sorting opeartions */
    $(document).on('click', '.name-sortable', function(e) {
        e.preventDefault();
        let defSortState = 0;
        let defFieldName;
        if (ActiveTabId == 'Item') {
            sortState = (sortState + 1) % 3;
            defSortState = sortState;
            defFieldName = '#sortName';
        } else if (ActiveTabId == 'Categories') {
            catgSortState = (catgSortState + 1) % 3;
            defSortState = catgSortState;
            defFieldName = '#sortCatgName';
        } else if (ActiveTabId == 'Sizes') {
            sizeSortState = (sizeSortState + 1) % 3;
            defSortState = sizeSortState;
            defFieldName = '#sortSizeName';
        } else if (ActiveTabId == 'Brands') {
            brandSortState = (brandSortState + 1) % 3;
            defSortState = brandSortState;
            defFieldName = '#sortBrandName';
        }
        const icon = $(this).find('i');
        icon.removeClass('bx-sort-alt-2 bx-up-arrow-alt bx-down-arrow-alt text-primary');
        $(defFieldName).removeClass('text-primary');
        if (defSortState == 1) {
            icon.addClass('bx-up-arrow-alt text-primary');
            $(defFieldName).addClass('text-primary');
            $(this).attr('title', 'Click sorting descending');
            Filter['NameSorting'] = 1;
        } else if (defSortState === 2) {
            icon.addClass('bx-down-arrow-alt text-primary');
            $(defFieldName).addClass('text-primary');
            $(this).attr('title', 'Remove sorting');
            Filter['NameSorting'] = 2;
        } else {
            icon.addClass('bx-sort-alt-2');
            $(this).attr('title', 'Click sorting ascending');
            delete Filter['NameSorting'];
        }
        $(this).tooltip('dispose').tooltip();
        showProductPageDetails();
    });

    /** Product-Item Related Coding */
    $(document).on('click', '.addItem', function(e) {
        e.preventDefault();
        formOpenCloseDefActions();
        $('#itemsModal').modal('show');
    });

    $('#itemsModal').on('shown.bs.modal', function() {
        $('#AddEditItemForm #ItemName').trigger('focus');
        $('.addEditFormAlert').addClass('d-none');
    });

    $('#itemsModal').on('hide.bs.modal', function() {
        formOpenCloseDefActions();
    });

    if (EnableStorage == 1) {
        loadSelect2Field('#StorageUID', '-- Select Storage --', '#itemsModal');
    }

    loadTaxDetailOptions();
    loadSelect2Field('#PrimaryUnit', '-- Select Primary Unit --', '#itemsModal');
    loadSelect2Field('#Category', '-- Select Category --', '#itemsModal');

    QuillEditor('.ql-toolbar', 'Enter product description...');
    
    basePaginationFunc(ProdPag, getProductDetails);
    baseRefreshPageFunc('.PageRefresh', showProductPageDetails);
    basePageHeaderFunc(ProdHeader, ProdTable, ProdRow);

    $(document).on('click', ProdRow, function() {
        onClickOfCheckbox($(this), ProdTable, ProdHeader, ProdRow);
        $('#CloneOption').addClass('d-none');
        if (SelectedUIDs.length == 1) {
            $('#CloneOption').removeClass('d-none');
        }
        MultipleDeleteOption();
    });

    $(document).on('click', '.EditProduct', function(e) {
        e.preventDefault();
        var getValue = $(this).data('uid');
        if (getValue) {
            retrieveProductDetails(getValue, false);
        }
    });

    $('#AddEditItemForm').submit(function(e) {
        e.preventDefault();

        var formData = new FormData($('#AddEditItemForm')[0]);
        if (myOneDropzone.files.length > 0) {
            const file = myOneDropzone.files[0];
            if (!file.isStored) {
                formData.append('UploadImage', myOneDropzone.files[0]);
            }
        }
        var getProdHiddenId = $('#AddEditItemForm').find('#HProductUID').val();
        if(getProdHiddenId && hasValue(imgData) && myOneDropzone.files.length == 0) {
            formData.append('ImageRemoved', 1);
        }
        const Description = quill.getText().trim(); // quill.root.innerHTML;
        if ($.trim(Description) != '') {
            formData.append('Description', $('#Description .ql-editor').html());
        }
        formData.append('PageNo', PageNo);
        formData.append('RowLimit', RowLimit);
        formData.append('ModuleId', ItemModuleId);
        if (Object.keys(Filter).length > 0) {
            formData.append('Filter', JSON.stringify(Filter));
        }
        formData.append('IsSizeApplicable', $('#IsSizeApplicable').is(':checked') ? 1 : 0);
        formData.append('NotForSale', $('#NotForSale').is(':checked') ? 1 : 0);
        
        if (getProdHiddenId == 0) {
            addProductData(formData);
        } else {
            editProductData(formData);
        }

    });

    $('#btnClone').click(function(e) {
        e.preventDefault();
        if (SelectedUIDs.length == 1 && ActiveTabId == 'Item') {
            var getSelectedId = SelectedUIDs[0];
            $(ProdTable + ' tbody ' + ProdRow).each(function() {
                const val = parseInt($(this).val());
                $(this).prop('checked', false);
            });
            SelectedUIDs = [];
            retrieveProductDetails(getSelectedId, true);
        }
    });

    $(document).on('click', '.DeleteProduct', function(e) {
        e.preventDefault();
        var GetId = $(this).data('productuid');
        if (GetId) {
            Swal.fire({
                title: "Do you want to delete the product?",
                text: "You won't be able to revert this!",
                icon: "info",
                showCancelButton: true,
                confirmButtonColor: "#d33",
                confirmButtonText: "Yes, delete it!",
                cancelButtonColor: "#3085d6",
            }).then((result) => {
                if (result.isConfirmed) {
                    deleteProduct(GetId);
                }
            });
        }
    });

    // Categories Page Coding Starts Here
    $(document).on('click', '.addCategory', function(e) {
        e.preventDefault();
        formOpenCloseDefActions();
        $('#categoryModal').modal('show');
    });

    $('#categoryModal').on('shown.bs.modal', function() {
        $('#CategoryName').trigger('focus');
        $('.catgFormAlert').addClass('d-none');
    });

    $('#categoryModal').on('hide.bs.modal', function() {
        formOpenCloseDefActions();
    });
    
    basePaginationFunc(CatgPag, getCategoriesDetails);
    basePageHeaderFunc(CatgHeader, CatgTable, CatgRow);

    $(document).on('click', CatgRow, function() {
        onClickOfCheckbox($(this), CatgTable, CatgHeader, CatgRow);
        MultipleDeleteOption();
    });

    $(document).on('click', '.editCategory', function(e) {
        e.preventDefault();
        var getVal = $(this).data('uid');
        if (getVal) {

            var getName = $(this).data('name');
            var getDesc = $(this).data('description');
            var getImage = $(this).data('image');

            $('#categoryForm').trigger('reset');
            $('#CatgModalTitle').text('Edit Category');
            $('#CatgSaveButton').text('Update');
            myTwoDropzone.removeAllFiles(true);
            $('#categoryModal').modal('show');

            $('#CategoryUID').val(getVal);
            $('#CategoryName').val(getName ? atob(getName) : '');
            $('#CategoryDescription').val(getDesc ? atob(getDesc) : '');
            if(hasValue(getImage)) {
                var ImageUrl = CDN_URL + atob(getImage);
                commonSetDropzoneImageTwo(ImageUrl);
                imgData = ImageUrl;
            }

            // retrieveCategoryDetails(getVal);
        }
    });

    $('#categoryForm').submit(function(e) {
        e.preventDefault();

        var formData = new FormData($('#categoryForm')[0]);
        var CategoryUID = $('#categoryForm').find('#CategoryUID').val();

        if(CategoryUID && hasValue(imgData) && myTwoDropzone.files.length == 0) {
            formData.append('ImageRemoved', 1);
        }
        if (myTwoDropzone.files.length > 0) {
            const file = myTwoDropzone.files[0];
            if (!file.isStored) {
                formData.append('UploadImage', myTwoDropzone.files[0]);
            }
        }
        formData.append('PageNo', PageNo);
        formData.append('RowLimit', RowLimit);
        formData.append('ModuleId', CategoryModuleId);
        if (Object.keys(Filter).length > 0) {
            formData.append('Filter', JSON.stringify(Filter));
        }
        
        if (CategoryUID == 0) {
            addCategoryDetails(formData);
        } else {
            editCategoryDetails(formData);
        }

    });

    $(document).on('click', '.DeleteCategory', function(e) {
        e.preventDefault();
        var GetId = $(this).data('categoryuid');
        if (GetId) {
            var ProductUID = $(this).data('productuid');
            if (ProductUID && ProductUID !== undefined && ProductUID !== null && ProductUID !== '') {
                Swal.fire("Category is linked to Product.", "", "error");
                return false;
            } else {
                Swal.fire({
                    title: "Do you want to delete the category?",
                    text: "You won't be able to revert this!",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#d33",
                    confirmButtonText: "Yes, delete it!",
                    cancelButtonColor: "#3085d6",
                }).then((result) => {
                    if (result.isConfirmed) {
                        deleteCategory(GetId);
                    }
                });
            }
        }
    });

    // Sizes Page Coding Starts Here
    $(document).on('click', '.addSizes', function(e) {
        e.preventDefault();
        formOpenCloseDefActions();
        $('#sizesModal').modal('show');
    });

    $('#sizesModal').on('shown.bs.modal', function() {
        $('#SizesName').trigger('focus');
        $('.sizeFormAlert').addClass('d-none');
    });

    $('#sizesModal').on('hide.bs.modal', function() {
        formOpenCloseDefActions();
    });
    
    basePaginationFunc(SizePag, getSizesDetails);
    basePageHeaderFunc(SizeHeader, SizeTable, SizeRow);

    $(document).on('click', SizeRow, function() {
        onClickOfCheckbox($(this), SizeTable, SizeHeader, SizeRow);
        MultipleDeleteOption();
    });

    $('#SizesForm').submit(function(e) {
        e.preventDefault();

        var formData = new FormData($('#SizesForm')[0]);
        formData.append('PageNo', PageNo);
        formData.append('RowLimit', RowLimit);
        formData.append('ModuleId', SizeModuleId);

        if (Object.keys(Filter).length > 0) {
            formData.append('Filter', JSON.stringify(Filter));
        }

        var getMode = $('#SizesForm').find('#HSizeUID').val();
        if (getMode == 0) {
            addSizeDetails(formData);
        } else if (getMode > 0) {
            editSizeDetails(formData);
        }
    });

    $(document).on('click', '.editSize', function(e) {
        e.preventDefault();
        var getVal = $(this).data('uid');
        if (getVal) {

            var getName = $(this).data('name');
            var getDesc = $(this).data('description');

            $('#SizesForm').trigger('reset');
            $('#SizeModalTitle').text('Edit Size');
            $('#sizeButtonName').text('Update');
            $('#sizesModal').modal('show');

            $('#HSizeUID').val(getVal);
            $('#SizesName').val(getName ? atob(getName) : '');
            $('#SizesDescription').val(getDesc ? atob(getDesc) : '');

            // retrieveSizeDetails(getVal);

        }
    });

    $(document).on('click', '.DeleteSize', function(e) {
        e.preventDefault();
        var GetId = $(this).data('sizeuid');
        if (GetId) {
            var ProductUID = $(this).data('productuid');
            if (ProductUID && ProductUID !== undefined && ProductUID !== null && ProductUID !== '') {
                Swal.fire("Size is linked to Product.", "", "error");
                return false;
            } else {
                Swal.fire({
                    title: "Do you want to delete the size?",
                    text: "You won't be able to revert this!",
                    icon: "info",
                    showCancelButton: true,
                    confirmButtonColor: "#d33",
                    confirmButtonText: "Yes, delete it!",
                    cancelButtonColor: "#3085d6",
                }).then((result) => {
                    if (result.isConfirmed) {
                        deleteSize(GetId);
                    }
                });
            }
        }
    });

    /** Brands Page Coding Starts Here */
    $(document).on('click', '.addBrands', function(e) {
        e.preventDefault();
        formOpenCloseDefActions();
        $('#brandsModal').modal('show');
    });

    $('#brandsModal').on('shown.bs.modal', function() {
        $('#BrandsName').trigger('focus');
        $('.brandFormAlert').addClass('d-none');
    });

    $('#brandsModal').on('hide.bs.modal', function() {
        formOpenCloseDefActions();
    });
    
    basePaginationFunc(BrandPag, getBrandsDetails);
    basePageHeaderFunc(BrandHeader, BrandTable, BrandRow);

    $(document).on('click', BrandRow, function() {
        onClickOfCheckbox($(this), BrandTable, BrandHeader, BrandRow);
        MultipleDeleteOption();
    });

    $('#BrandsForm').submit(function(e) {
        e.preventDefault();

        var formData = new FormData($('#BrandsForm')[0]);
        formData.append('PageNo', PageNo);
        formData.append('RowLimit', RowLimit);
        formData.append('ModuleId', BrandModuleId);

        if (Object.keys(Filter).length > 0) {
            formData.append('Filter', JSON.stringify(Filter));
        }

        var getMode = $('#BrandsForm').find('#HBrandUID').val();
        if (getMode == 0) {
            addBrandDetails(formData);
        } else if (getMode > 0) {
            editBrandDetails(formData);
        }

    });

    $(document).on('click', '.editBrand', function(e) {
        e.preventDefault();
        var getVal = $(this).data('uid');
        if (getVal) {

            var getName = $(this).data('name');
            var getDesc = $(this).data('description');

            $('#BrandsForm').trigger('reset');
            $('#BrandsModalTitle').text('Edit Brand');
            $('#brandButtonName').text('Update');
            $('#brandsModal').modal('show');

            $('#HBrandUID').val(getVal);
            $('#BrandsName').val(getName ? atob(getName) : '');
            $('#BrandsDescription').val(getDesc ? atob(getDesc) : '');

            // retrieveBrandDetails(getVal);

        }
    });

    $(document).on('click', '.DeleteBrand', function(e) {
        e.preventDefault();
        var GetId = $(this).data('branduid');
        if (GetId) {
            var ProductUID = $(this).data('productuid');
            if (ProductUID && ProductUID !== undefined && ProductUID !== null && ProductUID !== '') {
                Swal.fire("Brand is linked to Product.", "", "error");
                return false;
            } else {
                Swal.fire({
                    title: "Do you want to delete the brand?",
                    text: "You won't be able to revert this!",
                    icon: "info",
                    showCancelButton: true,
                    confirmButtonColor: "#d33",
                    confirmButtonText: "Yes, delete it!",
                    cancelButtonColor: "#3085d6",
                }).then((result) => {
                    if (result.isConfirmed) {
                        deleteBrand(GetId);
                    }
                });
            }
        }
    });

});
$(window).on('load', function() {
    $('.nav-item .TabPane').removeClass('disabled');
});
</script>