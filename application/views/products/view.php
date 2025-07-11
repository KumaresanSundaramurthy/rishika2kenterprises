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
                                            <a class="nav-link active TabPane" data-id="Item" data-moduleid="<?php echo $ItemModuleId; ?>" role="tab" data-bs-toggle="tab" data-bs-target="#NavItemPage" aria-controls="NavItemPage" aria-selected="true" href="javascript: void(0);"><i class="bx bx-package me-1"></i> Item</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link TabPane" data-id="Categories" data-moduleid="<?php echo $CategoryModuleId; ?>" role="tab" data-bs-toggle="tab" data-bs-target="#NavCategoriesPage" aria-controls="NavCategoriesPage" aria-selected="true" href="javascript: void(0);"><i class="bx bx-layer me-1"></i> Categories</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link TabPane" data-id="Sizes" data-moduleid="<?php echo $SizeModuleId; ?>" role="tab" data-bs-toggle="tab" data-bs-target="#NavSizesPage" aria-controls="NavSizesPage" aria-selected="true" href="javascript: void(0);"><i class="bx bx-ruler me-1"></i> Sizes</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link TabPane" data-id="Brands" data-moduleid="<?php echo $BrandModuleId; ?>" role="tab" data-bs-toggle="tab" data-bs-target="#NavBrandsPage" aria-controls="NavBrandsPage" aria-selected="true" href="javascript: void(0);"><i class="bx bx-badge-check me-1"></i> Brands</a>
                                        </li>
                                    </ul>
                                    <div class="d-flex mt-2 mt-md-0">
                                        <a href="javascript: void(0);" class="btn PageRefresh"><i class='bx bx-refresh' style="font-size: 22px;"></i> </a>
                                        <div class="position-relative me-2">
                                            <input type="text" class="form-control SearchDetails" name="SearchDetails" id="SearchDetails" placeholder="Search details..." data-toggle="tooltip" title="Please type at least 3 characters to search" />
                                            <i class="bx bx-x position-absolute top-50 end-0 translate-middle-y me-3 text-muted cursor-pointer" id="clearSearch"></i>
                                        </div>
                                        <div class="btn-group" id="ActionsDD-Div">
                                            <button class="btn btn-label-secondary dropdown-toggle me-2" type="button" id="actionsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                                <span class="d-flex align-items-center gap-2">
                                                    <i class="icon-base bx bx-cog icon-xs"></i>
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
                                        <a href="/products/add" class="btn btn-primary px-3" id="NewItem"><i class='bx bx-plus'></i> New Item</a>
                                        <a href="javascript: void(0);" class="btn btn-primary px-3 addCategory d-none" id="NewCategory"><i class='bx bx-plus'></i> New Category</a>
                                        <a href="javascript: void(0);" class="btn btn-primary px-3 addSizes d-none" id="NewSizes"><i class='bx bx-plus'></i> New Size</a>
                                        <a href="javascript: void(0);" class="btn btn-primary px-3 addBrands d-none" id="NewBrands"><i class='bx bx-plus'></i> New Brand</a>
                                    </div>
                                </div>
                                <div class="tab-content p-0">

                                    <div class="tab-pane fade show active" id="NavItemPage" role="tabpanel">

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
                                                        <th>Name</th>
                                                        <th>Category</th>
                                                        <th class="text-center">Qty</th>
                                                        <th class="text-end">Selling Price</th>
                                                        <th class="text-end">Purchase Price</th>
                                                        <th class="text-end">Last Updated On</th>
                                                        <th class="text-center">Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="table-border-bottom-0">
                                                    <?php echo $ItemList; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                        <hr class="my-0" />
                                        <div class="row mx-3 justify-content-between ProductsPagination" id="ProductsPagination">
                                            <?php echo $ItemPagination; ?>
                                        </div>

                                    </div>

                                    <div class="tab-pane fade" id="NavCategoriesPage" role="tabpanel">

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
                                                        <th>Name</th>
                                                        <th>Description</th>
                                                        <th class="text-end">Last Updated</th>
                                                        <th class="text-center">Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="table-border-bottom-0">
                                                    <?php $PageData['CategoriesList'] = [];
                                                    echo $this->load->view('products/categories/list', $PageData, TRUE); ?>
                                                </tbody>
                                            </table>
                                        </div>
                                        <hr class="my-0" />
                                        <div class="row mx-3 justify-content-between CategoriesPagination" id="CategoriesPagination"></div>

                                    </div>

                                    <div class="tab-pane fade" id="NavSizesPage" role="tabpanel">

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
                                                        <th>Name</th>
                                                        <th>Description</th>
                                                        <th class="text-end">Last Updated</th>
                                                        <th class="text-center">Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="table-border-bottom-0">
                                                    <?php $PageData['SizesList'] = [];
                                                    echo $this->load->view('products/sizes/list', $PageData, TRUE); ?>
                                                </tbody>
                                            </table>
                                        </div>
                                        <hr class="my-0" />
                                        <div class="row mx-3 justify-content-between SizesPagination" id="SizesPagination"></div>

                                    </div>

                                    <div class="tab-pane fade" id="NavBrandsPage" role="tabpanel">

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
                                                        <th>Name</th>
                                                        <th>Description</th>
                                                        <th class="text-end">Last Updated</th>
                                                        <th class="text-center">Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="table-border-bottom-0">
                                                    <?php $PageData['BrandsList'] = [];
                                                    echo $this->load->view('products/brands/list', $PageData, TRUE); ?>
                                                </tbody>
                                            </table>
                                        </div>
                                        <hr class="my-0" />
                                        <div class="row mx-3 justify-content-between BrandsPagination" id="BrandsPagination"></div>

                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
            <!-- Content wrapper -->

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
    let ItemUIDs = <?php echo json_encode($ItemUIDs ?: []); ?>;
    let ItemModuleId = <?php echo $ItemModuleId; ?>;
    const ProdTable = '#ProductsTable';
    const ProdPag = '.ProductsPagination';
    const ProdHeader = '.productsHeaderCheck';
    const ProdRow = '.productsCheck';
    let CategoryUIDs;
    let CategoryModuleId = <?php echo $CategoryModuleId; ?>;
    const CatgTable = '#CategoriesTable';
    const CatgPag = '.CategoriesPagination';
    const CatgHeader = '.categoryHeaderCheck';
    const CatgRow = '.categoryCheck';
    let SizeUIDs;
    let SizeModuleId = <?php echo $SizeModuleId; ?>;
    const SizeTable = '#SizesTable';
    const SizePag = '.SizesPagination';
    const SizeHeader = '.sizeHeaderCheck';
    const SizeRow = '.sizesCheck';
    let BrandUIDs;
    let BrandModuleId = <?php echo $BrandModuleId; ?>;
    const BrandTable = '#BrandsTable';
    const BrandPag = '.BrandsPagination';
    const BrandHeader = '.brandHeaderCheck';
    const BrandRow = '.brandsCheck';
    let ActiveTabId = 'Item';
    let ActiveTabModuleId = <?php echo $ItemModuleId; ?>;
    let Modules = <?php echo json_encode($ModuleInfo ?: []); ?>;
    $(function() {
        'use strict'

        $('#SearchDetails').val('');
        $(ProdHeader + ',' + ProdRow).prop('checked', false).trigger('change');
        $('[data-toggle="tooltip"]').tooltip();
        $('#clearSearch').addClass('d-none');

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

        $('#btnClone').click(function(e) {
            e.preventDefault();
            if (SelectedUIDs.length == 1 && ActiveTabId == 'Item') {
                window.location.href = '/products/' + SelectedUIDs[0] + '/clone';
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
                            deleteMultipleProduct(SelectedUIDs);
                        } else if (ActiveTabId == 'Categories') {
                            deleteMultipleCategory(SelectedUIDs);
                        } else if (ActiveTabId == 'Sizes') {
                            deleteMultipleSize(SelectedUIDs);
                        } else if (ActiveTabId == 'Brands') {
                            deleteMultipleBrand(SelectedUIDs);
                        }
                    }
                });
            }
        });

        $('.TabPane').click(function(e) {
            e.preventDefault();
            var TabValue = $(this).data('id');
            if (TabValue) {
                ActiveTabId = TabValue;
                ActiveTabModuleId = $(this).data('moduleid');
                $('#NewItem,#NewCategory,#NewSizes,#NewBrands,#CloneOption').addClass('d-none');
                $('#SearchDetails').val('');
                PageNo = 0;
                Filter = {};
                if (ActiveTabId == 'Item') {
                    $('#NewItem').removeClass('d-none');
                    getProductDetails(PageNo, RowLimit, Filter);
                } else if (ActiveTabId == 'Categories') {
                    $('#NewCategory').removeClass('d-none');
                    getCategoriesDetails(PageNo, RowLimit, Filter);
                } else if (ActiveTabId == 'Sizes') {
                    $('#NewSizes').removeClass('d-none');
                    getSizesDetails(PageNo, RowLimit, Filter);
                } else if (ActiveTabId == 'Brands') {
                    $('#NewBrands').removeClass('d-none');
                    getBrandsDetails(PageNo, RowLimit, Filter);
                }
            }
        });

        $(ProdPag).on('click', 'a', function(e) {
            e.preventDefault();
            PageNo = $(this).attr('data-ci-pagination-page');
            getProductDetails(PageNo, RowLimit, Filter);
        });

        $(document).on('click', '.PageRefresh', function(e) {
            e.preventDefault();
            if (ActiveTabId == 'Item') {
                getProductDetails(PageNo, RowLimit, Filter);
            } else if (ActiveTabId == 'Categories') {
                getCategoriesDetails(PageNo, RowLimit, Filter);
            } else if (ActiveTabId == 'Sizes') {
                getSizesDetails(PageNo, RowLimit, Filter);
            } else if (ActiveTabId == 'Brands') {
                getBrandsDetails(PageNo, RowLimit, Filter);
            }
        });

        $('#clearSearch').click(function(e) {
            e.preventDefault();
            var searchText = $('#SearchDetails').val();
            $('#SearchDetails').val('');
            $('#clearSearch').addClass('d-none');
            if ($.trim(searchText) != '') {
                Filter = {};
                if (ActiveTabId == 'Item') {
                    getProductDetails(PageNo, RowLimit, Filter);
                } else if (ActiveTabId == 'Categories') {
                    getCategoriesDetails(PageNo, RowLimit, Filter);
                } else if (ActiveTabId == 'Sizes') {
                    getSizesDetails(PageNo, RowLimit, Filter);
                } else if (ActiveTabId == 'Brands') {
                    getBrandsDetails(PageNo, RowLimit, Filter);
                }
            }
        });

        $('.SearchDetails').keyup(inputDelay(function(e) {
            PageNo = 0;
            let searchText = $('#SearchDetails').val();
            if (searchText.length >= 3) {
                $('#clearSearch').removeClass('d-none');
                if (searchText) {
                    Filter['Name'] = searchText;
                }
                if (ActiveTabId == 'Item') {
                    getProductDetails(PageNo, RowLimit, Filter);
                } else if (ActiveTabId == 'Categories') {
                    getCategoriesDetails(PageNo, RowLimit, Filter);
                } else if (ActiveTabId == 'Sizes') {
                    getSizesDetails(PageNo, RowLimit, Filter);
                } else if (ActiveTabId == 'Brands') {
                    getBrandsDetails(PageNo, RowLimit, Filter);
                }
            }
        }, 500));

        $('#selectThisPageBtn').click(function(e) {
            e.preventDefault();
            commonSelectFunctionality('CurrentPage');
        });

        $('#selectAllPagesBtn').click(function(e) {
            e.preventDefault();
            commonSelectFunctionality('AllPage');
        });

        $('#unselectThisPageBtn').click(function(e) {
            e.preventDefault();
            commonUnSelectFunctionality('CurrentPage');
        });

        $('#unselectAllPagesBtn').click(function(e) {
            e.preventDefault();
            commonUnSelectFunctionality('AllPage');
        });

        $('#clearSelectAllClose').click(function(e) {
            e.preventDefault();
            if (ActiveTabId == 'Item') {
                selectModalCloseFunc(ProdTable, ProdHeader, ProdRow, ItemUIDs);
            } else if (ActiveTabId == 'Categories') {
                selectModalCloseFunc(CatgTable, CatgHeader, CatgRow, CategoryUIDs);
            } else if (ActiveTabId == 'Sizes') {
                selectModalCloseFunc(SizeTable, SizeHeader, SizeRow, SizeUIDs);
            } else if (ActiveTabId == 'Brands') {
                selectModalCloseFunc(BrandTable, BrandHeader, BrandRow, BrandUIDs);
            }
        });

        $('#btnExportPrint').click(function(e) {
            e.preventDefault();
            commonExportFunctionality(1, 'PrintPreview');
        });

        $('#btnExportCSV').click(function(e) {
            e.preventDefault();
            commonExportFunctionality(1, 'ExportCSV');
        });

        $('#btnExportPDF').click(function(e) {
            e.preventDefault();
            commonExportFunctionality(1, 'ExportPDF');
        });

        $('#btnExportExcel').click(function(e) {
            e.preventDefault();
            commonExportFunctionality(1, 'ExportExcel');
        });

        $('#exportSelectedItemsBtn').click(function(e) {
            e.preventDefault();
            commonExportFunctionality(2, expActionType);
        });

        $('#clearExportClose').click(function(e) {
            e.preventDefault();
            if (ActiveTabId == 'Item') {
                exportModalCloseFunc(ProdTable, ProdHeader, ProdRow, ItemUIDs);
            } else if (ActiveTabId == 'Categories') {
                exportModalCloseFunc(CatgTable, CatgHeader, CatgRow, CategoryUIDs);
            } else if (ActiveTabId == 'Sizes') {
                exportModalCloseFunc(SizeTable, SizeHeader, SizeRow, SizeUIDs);
            } else if (ActiveTabId == 'Brands') {
                exportModalCloseFunc(BrandTable, BrandHeader, BrandRow, BrandUIDs);
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
        $(CatgPag).on('click', 'a', function(e) {
            e.preventDefault();
            PageNo = $(this).attr('data-ci-pagination-page');
            getCategoriesDetails(PageNo, RowLimit, Filter);
        });

        $(document).on('click', '.addCategory', function(e) {
            e.preventDefault();
            hasRemovedStoredImage = false;
            $('#categoryForm').trigger('reset');
            $('#CatgModalTitle').text('Add Category');
            $('#CatgSaveButton').text('Save');
            $('#categoryModal').modal('show');
        });

        $('#categoryModal').on('shown.bs.modal', function() {
            $('#CategoryName').trigger('focus');
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
            if (Object.keys(Filter).length > 0) {
                formData.append('Filter', JSON.stringify(Filter));
            }

            var CategoryUID = $('#categoryForm').find('#CategoryUID').val();
            if (CategoryUID == 0) {
                addCategoryDetails(formData);
            } else {
                if (hasRemovedStoredImage === true) {
                    formData.append('RemovedImage', true);
                }
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
        $(SizePag).on('click', 'a', function(e) {
            e.preventDefault();
            PageNo = $(this).attr('data-ci-pagination-page');
            getSizesDetails(PageNo, RowLimit, Filter);
        });

        $(document).on('click', '.addSizes', function(e) {
            e.preventDefault();
            $('#SizesForm').trigger('reset');
            $('#SizeModalTitle').text('Add Size');
            $('#sizeButtonName').text('Save');
            $('#sizesModal').modal('show');
        });

        $('#sizesModal').on('shown.bs.modal', function() {
            $('#SizesName').trigger('focus');
        });

        $(document).on('click', '.editSize', function(e) {
            e.preventDefault();
            var getVal = $(this).data('uid');
            if (getVal) {
                retrieveSizeDetails(getVal);
            }
        });

        $('#SizesForm').submit(function(e) {
            e.preventDefault();

            var formData = new FormData($('#SizesForm')[0]);
            formData.append('PageNo', PageNo);
            formData.append('RowLimit', RowLimit);

            if (Object.keys(Filter).length > 0) {
                formData.append('Filter', JSON.stringify(Filter));
            }

            AjaxLoading = 0;
            $('#sizeButtonName').attr('disabled', 'disabled');

            var getMode = $('#SizesForm').find('#SizeUID').val();
            if (getMode == 0) {
                addSizeDetails(formData);
            } else if (getMode > 0) {
                editSizeDetails(formData);
            }
        });

        $(document).on('click', '.DeleteSize', function(e) {
            e.preventDefault();
            var GetId = $(this).data('sizeuid');
            if (GetId) {
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
        });

        /** Brands Page Coding Starts Here */
        $(BrandPag).on('click', 'a', function(e) {
            e.preventDefault();
            PageNo = $(this).attr('data-ci-pagination-page');
            getBrandsDetails(PageNo, RowLimit, Filter);
        });

        $(document).on('click', '.addBrands', function(e) {
            e.preventDefault();
            $('#BrandsForm').trigger('reset');
            $('#BrandsModalTitle').text('Add Brand');
            $('#brandButtonName').text('Save');
            $('#brandsModal').modal('show');
        });

        $('#brandsModal').on('shown.bs.modal', function() {
            $('#BrandsName').trigger('focus');
        });

        $(document).on('click', '.editBrand', function(e) {
            e.preventDefault();
            var getVal = $(this).data('uid');
            if (getVal) {
                retrieveBrandDetails(getVal);
            }
        });

        $('#BrandsForm').submit(function(e) {
            e.preventDefault();

            var formData = new FormData($('#BrandsForm')[0]);
            formData.append('PageNo', PageNo);
            formData.append('RowLimit', RowLimit);

            if (Object.keys(Filter).length > 0) {
                formData.append('Filter', JSON.stringify(Filter));
            }

            AjaxLoading = 0;
            $('#brandButtonName').attr('disabled', 'disabled');

            var getMode = $('#BrandsForm').find('#BrandUID').val();
            if (getMode == 0) {
                addBrandDetails(formData);
            } else if (getMode > 0) {
                editBrandDetails(formData);
            }

        });

        $(document).on('click', '.DeleteBrand', function(e) {
            e.preventDefault();
            var GetId = $(this).data('branduid');
            if (GetId) {
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
        });

    });
    $(window).on('load', function() {

        // getProductDetails(PageNo, RowLimit, Filter);

    });
</script>