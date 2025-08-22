<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php $this->load->view('common/header'); ?>

<style>
    th.sortable i {
        margin-left: 5px;
        vertical-align: middle;
    }
</style>

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
                                            <a class="nav-link <?php echo $ActiveTabData == 'item' ? 'active' : ''; ?> TabPane" data-id="Item" data-moduleid="<?php echo $ItemModuleId; ?>" role="tab" data-bs-toggle="tab" data-bs-target="#NavItemPage" aria-controls="NavItemPage" aria-selected="true" href="javascript: void(0);"><i class="bx bx-package me-1"></i> Item</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link <?php echo $ActiveTabData == 'category' ? 'active' : ''; ?> TabPane" data-id="Categories" data-moduleid="<?php echo $CategoryModuleId; ?>" role="tab" data-bs-toggle="tab" data-bs-target="#NavCategoriesPage" aria-controls="NavCategoriesPage" aria-selected="true" href="javascript: void(0);"><i class="bx bx-layer me-1"></i> Categories</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link <?php echo $ActiveTabData == 'size' ? 'active' : ''; ?> TabPane" data-id="Sizes" data-moduleid="<?php echo $SizeModuleId; ?>" role="tab" data-bs-toggle="tab" data-bs-target="#NavSizesPage" aria-controls="NavSizesPage" aria-selected="true" href="javascript: void(0);"><i class="bx bx-ruler me-1"></i> Sizes</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link <?php echo $ActiveTabData == 'brand' ? 'active' : ''; ?> TabPane" data-id="Brands" data-moduleid="<?php echo $BrandModuleId; ?>" role="tab" data-bs-toggle="tab" data-bs-target="#NavBrandsPage" aria-controls="NavBrandsPage" aria-selected="true" href="javascript: void(0);"><i class="bx bx-badge-check me-1"></i> Brands</a>
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
                                        <a href="javascript: void(0);" class="btn btn-primary px-3 addItem <?php echo $ActiveTabData == 'item' ? '' : 'd-none'; ?>" id="NewItem"><i class='bx bx-plus'></i> New Item</a>
                                        <a href="javascript: void(0);" class="btn btn-primary px-3 addCategory <?php echo $ActiveTabData == 'category' ? '' : 'd-none'; ?>" id="NewCategory"><i class='bx bx-plus'></i> New Category</a>
                                        <a href="javascript: void(0);" class="btn btn-primary px-3 addSizes <?php echo $ActiveTabData == 'size' ? '' : 'd-none'; ?>" id="NewSizes"><i class='bx bx-plus'></i> New Size</a>
                                        <a href="javascript: void(0);" class="btn btn-primary px-3 addBrands <?php echo $ActiveTabData == 'brand' ? '' : 'd-none'; ?>" id="NewBrands"><i class='bx bx-plus'></i> New Brand</a>
                                    </div>
                                </div>
                                <div class="tab-content p-0">

                                    <div class="tab-pane fade <?php echo $ActiveTabData == 'item' ? 'show active' : ''; ?>" id="NavItemPage" role="tabpanel">

                                        <div class="table-responsive text-nowrap h-100 tablecard">
                                            <table class="table table-sm table-striped table-hover" id="ProductsTable">
                                                <thead>
                                                    <tr>
                                                        <th class="table-checkbox">
                                                            <div class="form-check form-check-inline">
                                                                <input class="form-check-input table-chkbox productsHeaderCheck" type="checkbox">
                                                            </div>
                                                        </th>
                                                        <th class="table-serialno">S.No</th>
                                                        <?php foreach (array_column($ItemColumns, 'DisplayName') as $ItemKey => $ItemVal) {
                                                            if ($ItemVal === 'Storage' && ($JwtData->GenSettings->EnableStorage ?? 0) != 1) {
                                                                continue;
                                                            } ?>
                                                            <th <?php echo updateAttributeString($ItemColumns[$ItemKey]->MainPageColumnAddon, $ItemColumns[$ItemKey]->MPSortApplicable); ?>>

                                                                <?php echo $ItemVal; ?>

                                                                <?php if ($ItemColumns[$ItemKey]->MPSortApplicable == 1) {
                                                                    echo '<i class="bx bx-sort ms-1 cursor-pointer"></i>';
                                                                } ?>

                                                                <?php if ($ItemVal == 'Category' && $ItemColumns[$ItemKey]->MPFilterApplicable == 1) { ?>

                                                                    <a href="javascript:void(0);" class="text-body ms-1" onclick="toggleCategoryFilter()">
                                                                        <i class="bx bx-filter-alt fs-5 align-middle"></i>
                                                                    </a>

                                                                    <div id="categoryFilterBox" class="card shadow position-absolute p-3" style="min-width: 270px; max-height: 350px; top: 100%; z-index: 1055; display: none;">

                                                                        <!-- Search Box -->
                                                                        <input type="text" id="categorySearch" class="form-control form-control-sm mb-4" placeholder="Search category...">

                                                                        <div class="form-check mb-2">
                                                                            <label class="form-check-label w-100 d-flex align-items-center">
                                                                                <input type="checkbox" class="form-check-input me-2 my-1" id="selectAllCategories" onchange="toggleAllCategories(this)">
                                                                                <label class="form-check-label" for="selectAllCategories" id="selectAllLabel">Select All</label>
                                                                            </label>
                                                                        </div>

                                                                        <!-- Category List -->
                                                                        <div id="categoryList" style="max-height: 180px; overflow-y: auto;">
                                                                            <?php if (sizeof($Categories) > 0) {
                                                                                foreach ($Categories as $catg) { ?>
                                                                                    <div class="form-check mb-2 my-1 list-hover-bg">
                                                                                        <label class="form-check-label w-100 d-flex align-items-center">
                                                                                            <input class="form-check-input me-2 category-checkbox" type="checkbox" value="<?php echo $catg->CategoryUID; ?>">
                                                                                            <span><?php echo $catg->Name; ?></span>
                                                                                        </label>
                                                                                    </div>
                                                                            <?php }
                                                                            } ?>
                                                                        </div>

                                                                        <div class="border-top pt-2 mt-2 d-flex justify-content-between gap-2">
                                                                            <button type="button" class="btn btn-sm btn-outline-secondary w-100" onclick="resetCategoryFilter()">Reset</button>
                                                                            <button type="button" class="btn btn-sm btn-primary w-100" onclick="applyCategoryFilter()">Search</button>
                                                                            <button type="button" class="btn btn-sm btn-outline-dark w-100" onclick="closeCategoryFilter();">Close</button>
                                                                        </div>
                                                                    </div>

                                                                <?php } ?>

                                                                <?php if ($ItemVal == 'Storage' && $JwtData->GenSettings->EnableStorage == 1 && $ItemColumns[$ItemKey]->MPFilterApplicable == 1) { ?>

                                                                    <a href="javascript:void(0);" class="text-body ms-1" onclick="toggleStorageFilter()">
                                                                        <i class="bx bx-filter-alt fs-5 align-middle"></i>
                                                                    </a>

                                                                    <div id="storageFilterBox" class="card shadow position-absolute p-3" style="min-width: 270px; max-height: 350px; top: 100%; z-index: 1055; display: none;">

                                                                        <!-- Search Box -->
                                                                        <input type="text" id="storageSearch" class="form-control form-control-sm mb-4" placeholder="Search storage...">

                                                                        <div class="form-check mb-2">
                                                                            <label class="form-check-label w-100 d-flex align-items-center">
                                                                                <input type="checkbox" class="form-check-input me-2 my-1" id="selectAllStorage" onchange="toggleAllStorage(this)">
                                                                                <label class="form-check-label" for="selectAllStorage" id="str_selectAllLabel">Select All</label>
                                                                            </label>
                                                                        </div>

                                                                        <!-- Storage List -->
                                                                        <div id="storageList" style="max-height: 180px; overflow-y: auto;">
                                                                            <?php if (sizeof($Storage) > 0) {
                                                                                foreach ($Storage as $strg) { ?>
                                                                                    <div class="form-check mb-2 my-1 list-hover-bg">
                                                                                        <label class="form-check-label w-100 d-flex align-items-center">
                                                                                            <input class="form-check-input me-2 storage-checkbox" type="checkbox" value="<?php echo $strg->StorageUID; ?>">
                                                                                            <span><?php echo $strg->Name; ?></span>
                                                                                        </label>
                                                                                    </div>
                                                                            <?php }
                                                                            } ?>
                                                                        </div>

                                                                        <div class="border-top pt-2 mt-2 d-flex justify-content-between gap-2">
                                                                            <button type="button" class="btn btn-sm btn-outline-secondary w-100" onclick="resetStorageFilter()">Reset</button>
                                                                            <button type="button" class="btn btn-sm btn-primary w-100" onclick="applyStorageFilter()">Search</button>
                                                                            <button type="button" class="btn btn-sm btn-outline-dark w-100" onclick="closeStorageFilter();">Close</button>
                                                                        </div>
                                                                    </div>

                                                                <?php } ?>

                                                            </th>
                                                        <?php } ?>
                                                        <th class="text-center">Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="table-border-bottom-0">
                                                    <?php if ($ActiveTabData == 'item') {
                                                        echo $ModActiveList;
                                                    } else {
                                                        $PageData['DataLists'] = [];
                                                        echo $this->load->view('products/items/list', $PageData, TRUE);
                                                    } ?>
                                                </tbody>
                                            </table>
                                        </div>
                                        <hr class="my-0" />
                                        <div class="row mx-3 justify-content-between ProductsPagination" id="ProductsPagination">
                                            <?php echo $ActiveTabData == 'item' ? $ModActivePagination : ''; ?>
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
                                                        <th class="table-serialno">S.No</th>
                                                        <?php foreach (array_column($CategoryColumns, 'DisplayName') as $CtgKey => $CtgVal) { ?>
                                                            <th <?php echo $CategoryColumns[$CtgKey]->MainPageColumnAddon; ?>><?php echo $CtgVal; ?></th>
                                                        <?php } ?>
                                                        <th class="text-center">Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="table-border-bottom-0">
                                                    <?php if ($ActiveTabData == 'category') {
                                                        echo $ModActiveList;
                                                    } else {
                                                        $PageData['DataLists'] = [];
                                                        echo $this->load->view('products/categories/list', $PageData, TRUE);
                                                    } ?>
                                                </tbody>
                                            </table>
                                        </div>
                                        <hr class="my-0" />
                                        <div class="row mx-3 justify-content-between CategoriesPagination" id="CategoriesPagination">
                                            <?php echo $ActiveTabData == 'category' ? $ModActivePagination : ''; ?>
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
                                                        <th class="table-serialno">S.No</th>
                                                        <?php foreach (array_column($SizeColumns, 'DisplayName') as $SzKey => $SzVal) { ?>
                                                            <th <?php echo $SizeColumns[$SzKey]->MainPageColumnAddon; ?>><?php echo $SzVal; ?></th>
                                                        <?php } ?>
                                                        <th class="text-center">Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="table-border-bottom-0">
                                                    <?php if ($ActiveTabData == 'size') {
                                                        echo $ModActiveList;
                                                    } else {
                                                        $PageData['DataLists'] = [];
                                                        echo $this->load->view('products/sizes/list', $PageData, TRUE);
                                                    } ?>
                                                </tbody>
                                            </table>
                                        </div>
                                        <hr class="my-0" />
                                        <div class="row mx-3 justify-content-between SizesPagination" id="SizesPagination">
                                            <?php echo $ActiveTabData == 'size' ? $ModActivePagination : ''; ?>
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
                                                        <th class="table-serialno">S.No</th>
                                                        <?php foreach (array_column($BrandColumns, 'DisplayName') as $BrdKey => $BrdVal) { ?>
                                                            <th <?php echo $BrandColumns[$BrdKey]->MainPageColumnAddon; ?>><?php echo $BrdVal; ?></th>
                                                        <?php } ?>
                                                        <th class="text-center">Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="table-border-bottom-0">
                                                    <?php if ($ActiveTabData == 'brand') {
                                                        echo $ModActiveList;
                                                    } else {
                                                        $PageData['DataLists'] = [];
                                                        echo $this->load->view('products/brands/list', $PageData, TRUE);
                                                    } ?>
                                                </tbody>
                                            </table>
                                        </div>
                                        <hr class="my-0" />
                                        <div class="row mx-3 justify-content-between BrandsPagination" id="BrandsPagination">
                                            <?php echo $ActiveTabData == 'brand' ? $ModActivePagination : ''; ?>
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

<script>
    let ItemUIDs = <?php echo json_encode($ActiveTabData == 'item' ? ($ModActiveUIDs ?? []) : []); ?>;
    let ItemModuleId = <?php echo $ItemModuleId; ?>;
    const ProdTable = '#ProductsTable';
    const ProdPag = '.ProductsPagination';
    const ProdHeader = '.productsHeaderCheck';
    const ProdRow = '.productsCheck';
    let CategoryUIDs = <?php echo json_encode($ActiveTabData == 'category' ? ($ModActiveUIDs ?? []) : []); ?>;
    let CategoryModuleId = <?php echo $CategoryModuleId; ?>;
    const CatgTable = '#CategoriesTable';
    const CatgPag = '.CategoriesPagination';
    const CatgHeader = '.categoryHeaderCheck';
    const CatgRow = '.categoryCheck';
    let SizeUIDs = <?php echo json_encode($ActiveTabData == 'size' ? ($ModActiveUIDs ?? []) : []); ?>;
    let SizeModuleId = <?php echo $SizeModuleId; ?>;
    const SizeTable = '#SizesTable';
    const SizePag = '.SizesPagination';
    const SizeHeader = '.sizeHeaderCheck';
    const SizeRow = '.sizesCheck';
    let BrandUIDs = <?php echo json_encode($ActiveTabData == 'brand' ? ($ModActiveUIDs ?? []) : []); ?>;
    let BrandModuleId = <?php echo $BrandModuleId; ?>;
    const BrandTable = '#BrandsTable';
    const BrandPag = '.BrandsPagination';
    const BrandHeader = '.brandHeaderCheck';
    const BrandRow = '.brandsCheck';
    let ActiveTabId = '<?php echo $ActiveTabName; ?>';
    let ActiveTabModuleId = <?php echo $ActiveModuleId; ?>;
    let Modules = <?php echo json_encode($ModuleInfo ?: []); ?>;
    var EnableStorage = <?php echo $JwtData->GenSettings->EnableStorage; ?>;
    $(function() {
        'use strict'

        /** Common Details */
        $('#SearchDetails').val('');
        $(ProdHeader + ',' + ProdRow).prop('checked', false).trigger('change');

        $(".sortable").css("cursor", "pointer").append(' <i class="bx bx-sort-alt-2"></i>');

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
                    }
                } else if (ActiveTabId == 'Categories') {
                    $('#NewCategory').removeClass('d-none');
                    var catgLen = $(CatgTable + ' ' + CatgRow).length;
                    if (catgLen == 0) {
                        getCategoriesDetails(PageNo, RowLimit, Filter);
                    }
                } else if (ActiveTabId == 'Sizes') {
                    $('#NewSizes').removeClass('d-none');
                    var sizLen = $(SizeTable + ' ' + SizeRow).length;
                    if (sizLen == 0) {
                        getSizesDetails(PageNo, RowLimit, Filter);
                    }
                } else if (ActiveTabId == 'Brands') {
                    $('#NewBrands').removeClass('d-none');
                    var brndLen = $(BrandTable + ' ' + BrandRow).length;
                    if (brndLen == 0) {
                        getBrandsDetails(PageNo, RowLimit, Filter);
                    }
                }
            }
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
                delete Filter['SearchAllData'];
                $('#SearchDetails').blur();
                showProductPageDetails();
            }
        });

        $(document).on('click', '.PageRefresh', function(e) {
            e.preventDefault();
            showProductPageDetails();
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

        $(document).on('click', function(e) {
            const $filterBox = $('#categoryFilterBox, #storageFilterBox');
            const $toggleIcon = $('.bx-filter-alt');

            if (!$filterBox.is(e.target) && $filterBox.has(e.target).length === 0 && !$toggleIcon.is(e.target) && $toggleIcon.has(e.target).length === 0) {
                $filterBox.hide();
            }
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

        /** Product-Item Related Coding */
        $(document).on('click', '.addItem', function(e) {
            e.preventDefault();
            $('#AddEditItemForm').trigger('reset');
            $('#ItemModalTitle').text('Add Item');
            $('.AddEditProductBtn').text('Save');
            clearItemValues();
            $('#itemsModal').modal('show');
        });

        $('#itemsModal').on('shown.bs.modal', function() {
            $('#AddEditItemForm #ItemName').trigger('focus');
            $('.addEditFormAlert').addClass('d-none');
        });

        $('#itemsModal').on('hide.bs.modal', function() {
            $('#AddEditItemForm').trigger('reset');
            $('#ItemModalTitle').text('Add Item');
            $('.AddEditProductBtn').text('Save');
            clearItemValues();
        });

        if (EnableStorage == 1) {
            loadSelect2Field('#StorageUID', '-- Select Storage --', '#itemsModal');
        }

        loadSelect2Field('#TaxPercentage', '-- Select Tax Percentage --', '#itemsModal');
        loadSelect2Field('#PrimaryUnit', '-- Select Primary Unit --', '#itemsModal');
        loadSelect2Field('#Category', '-- Select Category --', '#itemsModal');

        QuillEditor('.ql-toolbar', 'Enter product description...');

        $(ProdHeader).click(function() {
            allTableHeadersCheckbox($(this), ItemUIDs, ProdTable, ProdHeader, ProdRow);
        });

        $(document).on('click', ProdRow, function() {
            onClickOfCheckbox($(this), ItemUIDs, ProdHeader);
            $('#CloneOption').addClass('d-none');
            if (SelectedUIDs.length == 1) {
                $('#CloneOption').removeClass('d-none');
            }
            MultipleDeleteOption();
        });

        $(ProdPag).on('click', 'a', function(e) {
            e.preventDefault();
            PageNo = $(this).attr('data-ci-pagination-page');
            getProductDetails(PageNo, RowLimit, Filter);
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

            var getHItemUID = $('#AddEditItemForm').find('#HProductUID').val();
            if (getHItemUID == 0) {
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
            $('#categoryForm').trigger('reset');
            $('#CatgModalTitle').text('Add Category');
            $('.CatgSaveButton').text('Save');
            $('#categoryForm').find('#CategoryUID').val(0);
            myOneDropzone.removeAllFiles(true);
            $('#categoryModal').modal('show');
        });

        $('#categoryModal').on('shown.bs.modal', function() {
            $('#CategoryName').trigger('focus');
            $('.catgFormAlert').addClass('d-none');
        });

        $('#categoryModal').on('hide.bs.modal', function() {
            $('#categoryForm').trigger('reset');
            $('#CatgModalTitle').text('Add Category');
            $('.CatgSaveButton').text('Save');
        });

        $(CatgHeader).click(function() {
            allTableHeadersCheckbox($(this), CategoryUIDs, CatgTable, CatgHeader, CatgRow);
        });

        $(document).on('click', CatgRow, function() {
            onClickOfCheckbox($(this), CategoryUIDs, CatgHeader);
            MultipleDeleteOption();
        });

        $(CatgPag).on('click', 'a', function(e) {
            e.preventDefault();
            PageNo = $(this).attr('data-ci-pagination-page');
            getCategoriesDetails(PageNo, RowLimit, Filter);
        });

        $(document).on('click', '.editCategory', function(e) {
            e.preventDefault();
            var getVal = $(this).data('uid');
            if (getVal) {
                retrieveCategoryDetails(getVal);
            }
        });

        $('#categoryForm').submit(function(e) {
            e.preventDefault();

            var formData = new FormData($('#categoryForm')[0]);
            if (myOneDropzone.files.length > 0) {
                const file = myOneDropzone.files[0];
                if (!file.isStored) {
                    formData.append('UploadImage', myOneDropzone.files[0]);
                }
            }
            formData.append('PageNo', PageNo);
            formData.append('RowLimit', RowLimit);
            formData.append('ModuleId', CategoryModuleId);
            if (Object.keys(Filter).length > 0) {
                formData.append('Filter', JSON.stringify(Filter));
            }

            var CategoryUID = $('#categoryForm').find('#CategoryUID').val();
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
            $('#SizesForm').trigger('reset');
            $('#SizeModalTitle').text('Add Size');
            $('.sizeButtonName').text('Save');
            $('#sizesModal').find('#HSizeUID').val(0);
            $('#sizesModal').modal('show');
        });

        $('#sizesModal').on('shown.bs.modal', function() {
            $('#SizesName').trigger('focus');
            $('.sizeFormAlert').addClass('d-none');
        });

        $('#sizesModal').on('hide.bs.modal', function() {
            $('#SizesForm').trigger('reset');
            $('#SizeModalTitle').text('Add Size');
            $('.sizeButtonName').text('Save');
        });

        $(SizeHeader).click(function() {
            allTableHeadersCheckbox($(this), SizeUIDs, SizeTable, SizeHeader, SizeRow);
        });

        $(document).on('click', SizeRow, function() {
            onClickOfCheckbox($(this), SizeUIDs, SizeHeader);
            MultipleDeleteOption();
        });

        $(SizePag).on('click', 'a', function(e) {
            e.preventDefault();
            PageNo = $(this).attr('data-ci-pagination-page');
            getSizesDetails(PageNo, RowLimit, Filter);
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
            $('#BrandsForm').trigger('reset');
            $('#BrandsModalTitle').text('Add Brand');
            $('.brandButtonName').text('Save');
            $('#brandsModal').find('#HBrandUID').val(0);
            $('#brandsModal').modal('show');
        });

        $('#brandsModal').on('shown.bs.modal', function() {
            $('#BrandsName').trigger('focus');
            $('.brandFormAlert').addClass('d-none');
        });

        $('#brandsModal').on('hide.bs.modal', function() {
            $('#BrandsForm').trigger('reset');
            $('#BrandsModalTitle').text('Add Brand');
            $('.brandButtonName').text('Save');
        });

        $(BrandHeader).click(function() {
            allTableHeadersCheckbox($(this), BrandUIDs, BrandTable, BrandHeader, BrandRow);
        });

        $(document).on('click', BrandRow, function() {
            onClickOfCheckbox($(this), BrandUIDs, BrandHeader);
            MultipleDeleteOption();
        });

        $(BrandPag).on('click', 'a', function(e) {
            e.preventDefault();
            PageNo = $(this).attr('data-ci-pagination-page');
            getBrandsDetails(PageNo, RowLimit, Filter);
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

        // getProductDetails(PageNo, RowLimit, Filter);

    });
</script>