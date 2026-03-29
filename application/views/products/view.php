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
                                            <a class="nav-link <?php echo $ActiveTabData == 'item' ? 'active' : ''; ?> TabPane disabled" data-id="Item" role="tab" data-bs-toggle="tab" data-bs-target="#NavItemPage" aria-controls="NavItemPage" aria-selected="true" href="javascript: void(0);"><i class="bx bx-package me-1"></i> Item</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link <?php echo $ActiveTabData == 'category' ? 'active' : ''; ?> TabPane disabled" data-id="Categories" role="tab" data-bs-toggle="tab" data-bs-target="#NavCategoriesPage" aria-controls="NavCategoriesPage" aria-selected="true" href="javascript: void(0);"><i class="bx bx-layer me-1"></i> Categories</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link <?php echo $ActiveTabData == 'size' ? 'active' : ''; ?> TabPane disabled" data-id="Sizes" role="tab" data-bs-toggle="tab" data-bs-target="#NavSizesPage" aria-controls="NavSizesPage" aria-selected="true" href="javascript: void(0);"><i class="bx bx-ruler me-1"></i> Sizes</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link <?php echo $ActiveTabData == 'brand' ? 'active' : ''; ?> TabPane disabled" data-id="Brands" role="tab" data-bs-toggle="tab" data-bs-target="#NavBrandsPage" aria-controls="NavBrandsPage" aria-selected="true" href="javascript: void(0);"><i class="bx bx-badge-check me-1"></i> Brands</a>
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
                                        <a href="javascript: void(0);" class="btn btn-outline-primary px-3 ms-2 <?php echo $ActiveTabData == 'item' ? '' : 'd-none'; ?>" id="NewComboItem"><i class="bx bx-git-merge me-1"></i> Add Combo Item</a>
                                        <a href="javascript: void(0);" class="btn btn-primary px-3 addCategory <?php echo $ActiveTabData == 'category' ? '' : 'd-none'; ?>" id="NewCategory"> Create Category</a>
                                        <a href="javascript: void(0);" class="btn btn-primary px-3 addSizes <?php echo $ActiveTabData == 'size' ? '' : 'd-none'; ?>" id="NewSizes"> Create Size</a>
                                        <a href="javascript: void(0);" class="btn btn-primary px-3 addBrands <?php echo $ActiveTabData == 'brand' ? '' : 'd-none'; ?>" id="NewBrands"> Create Brand</a>
                                    </div>
                                </div>
                                <div class="tab-content p-0">

                                    <div class="tab-pane fade <?php echo $ActiveTabData == 'item' ? 'show active' : ''; ?>" id="NavItemPage" role="tabpanel">

                                        <div class="table-responsive text-nowrap h-100 tablecard">
                                            <table class="table table-hover" id="ProductsTable">
                                                <thead class="bg-body-tertiary">
                                                    <tr>
                                                        <th class="table-checkbox text-center align-middle">
                                                            <div class="form-check d-flex justify-content-center align-items-center mb-0">
                                                                <input class="form-check-input table-chkbox productsHeaderCheck" type="checkbox">
                                                            </div>
                                                        </th>
                                                        <th class="table-serialno <?php echo $JwtData->GenSettings->SerialNoDisplay == 1 ? '' : 'd-none'; ?>">S.No</th>
                                                        <th class="name-sortable position-relative" id="sortName" title="Sort by Name">
                                                            <span class="cursor-pointer">Item <i class="bx bx-sort ms-1"></i></span>
                                                            <a href="javascript:void(0);" id="productTypeFilter" class="text-body ms-1" onclick="toggleProductTypeFilter(); event.stopPropagation();" title="Filter by Product Type"><i class="bx bx-filter-alt fs-6 align-middle"></i></a>
                                                            <div id="productTypeFilterBox" class="card mp-filterbox position-absolute" style="min-width:200px; z-index:1056; display:none; top:100%; left:0;"><?php $this->load->view('products/items/ptypefilter'); ?></div>
                                                        </th>
                                                        <th class="position-relative">
                                                            Category
                                                            <span id="ItemCategory-Div" class="<?php echo $ActiveTabData == 'item' ? '' : 'd-none'; ?>">
                                                                <a href="javascript:void(0);" id="categoryFilter" class="text-body ms-1" onclick="toggleCategoryFilter()" title="Filter by Category">
                                                                    <i class="bx bx-filter-alt fs-6 align-middle"></i>
                                                                </a>
                                                                <div id="categoryFilterBox" class="card shadow mp-filterbox position-absolute" style="min-width:260px; z-index:1055; display:none;"></div>
                                                            </span>
                                                        </th>
                                                        <th class="col-sortable cursor-pointer" data-filterkey="QtySorting" data-sortcol="Products.AvailableQuantity" title="Sort by Quantity">Qty <i class="bx bx-sort ms-1"></i></th>
                                                        <th class="col-sortable cursor-pointer" data-filterkey="MRPSorting" data-sortcol="Products.MRP" title="Sort by MRP">MRP <i class="bx bx-sort ms-1"></i></th>
                                                        <th class="col-sortable cursor-pointer" data-filterkey="SellingPriceSorting" data-sortcol="Products.SellingPrice" title="Sort by Selling Price">Selling Price <i class="bx bx-sort ms-1"></i></th>
                                                        <th class="col-sortable cursor-pointer" data-filterkey="PurchasePriceSorting" data-sortcol="Products.PurchasePrice" title="Sort by Purchase Price">Purchase Price <i class="bx bx-sort ms-1"></i></th>
                                                        <th>Last Updated</th>
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
                                            <table class="table table-hover" id="CategoriesTable">
                                                <thead class="bg-body-tertiary">
                                                    <tr>
                                                        <th class="table-checkbox">
                                                            <div class="form-check form-check-inline">
                                                                <input class="form-check-input table-chkbox categoryHeaderCheck" type="checkbox">
                                                            </div>
                                                        </th>
                                                        <th class="table-serialno <?php echo $JwtData->GenSettings->SerialNoDisplay == 1 ? '' : 'd-none'; ?>">S.No</th>
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
                                            <table class="table table-hover" id="SizesTable">
                                                <thead class="bg-body-tertiary">
                                                    <tr>
                                                        <th class="table-checkbox">
                                                            <div class="form-check form-check-inline">
                                                                <input class="form-check-input table-chkbox sizeHeaderCheck" type="checkbox">
                                                            </div>
                                                        </th>
                                                        <th class="table-serialno <?php echo $JwtData->GenSettings->SerialNoDisplay == 1 ? '' : 'd-none'; ?>">S.No</th>
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
                                            <table class="table table-hover" id="BrandsTable">
                                                <thead class="bg-body-tertiary">
                                                    <tr>
                                                        <th class="table-checkbox">
                                                            <div class="form-check form-check-inline">
                                                                <input class="form-check-input table-chkbox brandHeaderCheck" type="checkbox">
                                                            </div>
                                                        </th>
                                                        <th class="table-serialno <?php echo $JwtData->GenSettings->SerialNoDisplay == 1 ? '' : 'd-none'; ?>">S.No</th>
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

            <?php $this->load->view('common/imagepreview_modal'); ?>

            <?php $this->load->view('products/modals/items'); ?>
            <?php $this->load->view('products/modals/combo'); ?>
            <?php $this->load->view('products/modals/category'); ?>
            <?php $this->load->view('products/modals/sizes'); ?>
            <?php $this->load->view('products/modals/brands'); ?>

            <?php $this->load->view('common/footer_desc'); ?>

        </div>

    </div>
</div>

<?php $this->load->view('common/footer'); ?>

<style>
    /* ── Products Table Header ── */
    #ProductsTable thead.bg-body-tertiary {
        background: linear-gradient(90deg, #eff0ff 0%, #f7f5ff 100%) !important;
    }
    #ProductsTable thead.bg-body-tertiary tr th {
        padding: 10px 12px;
        font-size: 0.71rem;
        font-weight: 700;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        color: #3e3fa8;
        background: transparent !important;
        border-top: none;
        border-bottom: 3px solid #696cff !important;
        white-space: nowrap;
    }
    #ProductsTable thead.bg-body-tertiary tr th.col-sortable:hover,
    #ProductsTable thead.bg-body-tertiary tr th.name-sortable:hover {
        color: #696cff;
        background: rgba(105,108,255,0.06) !important;
    }
    /* Active/filtered column highlight */
    #ProductsTable thead.bg-body-tertiary tr th.col-active {
        background: linear-gradient(180deg, rgba(105,108,255,0.10) 0%, rgba(105,108,255,0.04) 100%) !important;
        color: #4547c0 !important;
        border-bottom: 3px solid #9155fd !important;
        position: relative;
    }
    #ProductsTable thead.bg-body-tertiary tr th.col-active::after {
        content: '';
        position: absolute;
        bottom: -3px;
        left: 0;
        right: 0;
        height: 3px;
        background: linear-gradient(90deg, #696cff, #9155fd);
        border-radius: 2px;
    }
    #ProductsTable thead.bg-body-tertiary tr th.col-active i {
        color: #696cff;
    }

    /* ── Common Filter Box (shared by all filter dropdowns) ── */
    .mp-filterbox {
        padding: 0 !important;
        border-radius: 12px !important;
        border: 1px solid #c4c6f8 !important;
        box-shadow: 0 8px 24px rgba(105,108,255,0.18), 0 2px 8px rgba(0,0,0,0.08) !important;
        overflow: hidden;
        transform-origin: top left;
        animation: filterBoxIn 0.2s cubic-bezier(0.34, 1.4, 0.64, 1) both;
    }
    @keyframes filterBoxIn {
        from { opacity: 0; transform: translateY(-8px) scale(0.96); }
        to   { opacity: 1; transform: translateY(0) scale(1); }
    }
    .catg-filter-header {
        background: linear-gradient(135deg, #696cff 0%, #9155fd 100%);
        color: #fff;
        padding: 10px 14px 9px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-shrink: 0;
    }
    .catg-filter-title {
        font-size: 0.71rem;
        font-weight: 700;
        letter-spacing: 0.07em;
        text-transform: uppercase;
    }
    .catg-filter-header .badge {
        font-size: 0.65rem;
        padding: 2px 6px;
        background: rgba(255,255,255,0.25) !important;
        color: #fff !important;
        border-radius: 10px;
    }
    .catg-filter-close-btn {
        background: rgba(255,255,255,0.18);
        border: none;
        color: #fff;
        border-radius: 50%;
        width: 20px;
        height: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
        font-weight: 300;
        cursor: pointer;
        line-height: 1;
        transition: background 0.15s;
        flex-shrink: 0;
    }
    .catg-filter-close-btn:hover {
        background: rgba(255,255,255,0.35);
    }
    .catg-filter-search-wrap {
        padding: 8px 10px;
        background: #f5f5ff;
        border-bottom: 1px solid #e4e5ff;
        flex-shrink: 0;
    }
    .catg-filter-search-wrap .input-group-text {
        background: #fff;
        border-color: #d4d6ff;
        border-right: none;
        color: #9899c8;
        padding: 4px 8px;
    }
    .catg-filter-search-wrap .form-control {
        border-color: #d4d6ff;
        border-left: none;
        font-size: 0.8rem;
        padding: 4px 8px;
        background: #fff;
    }
    .catg-filter-search-wrap .form-control:focus {
        border-color: #696cff;
        box-shadow: none;
    }
    .catg-filter-search-wrap .form-control:focus + .input-group-text,
    .catg-filter-search-wrap .input-group:focus-within .input-group-text {
        border-color: #696cff;
    }
    .catg-select-all-wrap {
        padding: 6px 12px;
        background: #f0f1ff;
        border-bottom: 1px dashed #d8daff;
        display: flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
        transition: background 0.12s;
        flex-shrink: 0;
    }
    .catg-select-all-wrap:hover { background: #e6e8ff; }
    .catg-select-all-wrap label { cursor: pointer; }
    .catg-list {
        overflow-y: auto;
        padding: 6px 8px;
        background: #fff;
        flex: 1;
        min-height: 0;
    }
    .catg-list-item {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 5px 8px;
        border-radius: 7px;
        margin-bottom: 1px;
        cursor: pointer;
        font-size: 0.82rem;
        color: #555;
        transition: background 0.12s, color 0.12s;
        user-select: none;
    }
    .catg-list-item:hover {
        background: rgba(105,108,255,0.08);
        color: #4547c0;
    }
    .catg-list-item input[type="checkbox"] { flex-shrink: 0; }
    .catg-list-item input[type="checkbox"]:checked { background-color: #696cff; border-color: #696cff; }
    .catg-list-item:has(input:checked) {
        background: rgba(105,108,255,0.06);
        color: #696cff;
        font-weight: 600;
    }
    .catg-filter-footer {
        padding: 8px 10px;
        background: #f5f5ff;
        border-top: 1px solid #e4e5ff;
        display: flex;
        gap: 6px;
        flex-shrink: 0;
    }
    .catg-filter-footer .btn {
        flex: 1;
        font-size: 0.75rem;
        padding: 5px 0;
        border-radius: 7px;
        font-weight: 600;
    }

    /* ── Combo Row Styles ── */
    .combo-parent-row {
        background-color: rgba(253, 126, 20, 0.04) !important;
        border-left: 3px solid #fd7e14;
    }
    .combo-parent-row:hover {
        background-color: rgba(253, 126, 20, 0.08) !important;
    }
    .combo-bom-row td {
        padding: 0 !important;
    }
    .combo-bom-row .combo-bom-content table thead th {
        border-bottom: 1px solid rgba(253, 126, 20, 0.2);
        padding-bottom: 4px;
    }
    .combo-bom-row .combo-bom-content table tbody tr:last-child {
        border-bottom: none !important;
    }
</style>

<script src="/js/products.js"></script>
<script src="/js/combinemodules/products.js"></script>
<script src="/js/combinemodules/combo.js"></script>
<script src="/js/common/pagecheckbox.js"></script>

<script>
let ItemModuleId = 4;
const ProdTable = '#ProductsTable';
const ProdPag = '.ProductsPagination';
const ProdHeader = '.productsHeaderCheck';
const ProdRow = '.productsCheck';
let CategoryModuleId = 5;
const CatgTable = '#CategoriesTable';
const CatgPag = '.CategoriesPagination';
const CatgHeader = '.categoryHeaderCheck';
const CatgRow = '.categoryCheck';
let SizeModuleId = 6;
const SizeTable = '#SizesTable';
const SizePag = '.SizesPagination';
const SizeHeader = '.sizeHeaderCheck';
const SizeRow = '.sizesCheck';
let BrandModuleId = 7;
const BrandTable = '#BrandsTable';
const BrandPag = '.BrandsPagination';
const BrandHeader = '.brandHeaderCheck';
const BrandRow = '.brandsCheck';
let ActiveTabId = '<?php echo $ActiveTabName; ?>';
let ActiveTabModuleId = 4
var EnableStorage = <?php echo $JwtData->GenSettings->EnableStorage; ?>;
var CommonRowColumnDisp = 1;
let imgData;
let sortState = 0;
let catgSortState = 0;
let sizeSortState = 0;
let brandSortState = 0;
let colSortStates = {};
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
            $('#NewItem,#NewComboItem,#NewCategory,#NewSizes,#NewBrands,#CloneOption,#ItemCategory-Div').addClass('d-none');
            $('#SearchDetails').val('');
            PageNo = 0;
            Filter = {};
            if (ActiveTabId == 'Item') {
                $('#NewItem,#NewComboItem,#ItemCategory-Div').removeClass('d-none');
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

    $(document).on('change', '#selectAllCategories', function() {
        const isChecked = $(this).is(':checked');
        $('.category-checkbox').prop('checked', isChecked);
        $('#selectAllLabel').text(isChecked ? 'Deselect All' : 'Select All');
    });

    $(document).on('input', '#categorySearch', function() {
        const searchTerm = $(this).val().toLowerCase();
        $('#categoryList .catg-list-item').each(function() {
            const labelText = $(this).text().toLowerCase();
            $(this).toggle(labelText.includes(searchTerm));
        });
    });

    $(document).on('change', '.category-checkbox', function() {
        const total = $('.category-checkbox').length;
        const checked = $('.category-checkbox:checked').length;
        const allChecked = total === checked && total > 0;
        $('#selectAllCategories').prop('checked', allChecked);
        $('#selectAllLabel').text(allChecked ? 'Deselect All' : 'Select All');
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

    /** sorting operations */
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
            $(this).attr('title', 'Click for descending order');
            Filter['NameSorting'] = 1;
        } else if (defSortState === 2) {
            icon.addClass('bx-down-arrow-alt text-primary');
            $(defFieldName).addClass('text-primary');
            $(this).attr('title', 'Click to remove sorting');
            Filter['NameSorting'] = 2;
        } else {
            icon.addClass('bx-sort-alt-2');
            $(this).attr('title', 'Click for ascending order');
            delete Filter['NameSorting'];
        }
        $(this).tooltip('dispose').tooltip();
        showProductPageDetails();
    });

    /** Column Asc/Desc Sorting — multi-column allowed */
    $(document).on('click', '.col-sortable', function(e) {
        e.preventDefault();
        if (ActiveTabId !== 'Item') return;
        const filterKey = $(this).data('filterkey');
        // Cycle this column independently (multi-sort)
        colSortStates[filterKey] = ((colSortStates[filterKey] || 0) + 1) % 3;
        const state = colSortStates[filterKey];
        const icon = $(this).find('i');
        icon.removeClass('bx-sort bx-up-arrow-alt bx-down-arrow-alt text-primary');
        if (state === 1) {
            icon.addClass('bx-up-arrow-alt text-primary');
            $(this).attr('title', 'Click for descending order');
            Filter[filterKey] = 1;
        } else if (state === 2) {
            icon.addClass('bx-down-arrow-alt text-primary');
            $(this).attr('title', 'Click to remove sorting');
            Filter[filterKey] = 2;
        } else {
            icon.addClass('bx-sort');
            $(this).attr('title', 'Click for ascending order');
            delete Filter[filterKey];
        }
        showProductPageDetails();
    });

    /** Column active highlight updater */
    window.updateColumnHighlights = function() {
        // Name sort
        const nameSortActive = sortState > 0;
        // ProductType filter
        const ptypeActive = Filter.ProductType && Filter.ProductType.length > 0;
        if (nameSortActive || ptypeActive) {
            $('#sortName').addClass('col-active');
        } else {
            $('#sortName').removeClass('col-active');
        }
        $('#productTypeFilter').toggleClass('text-primary', !!ptypeActive);

        // Category filter
        const catgActive = Filter.Category && Filter.Category.length > 0;
        $('#ItemCategory-Div').closest('th').toggleClass('col-active', catgActive);
        $('#categoryFilter').toggleClass('text-primary', catgActive);

        // Each col-sortable
        $('.col-sortable').each(function() {
            const k = $(this).data('filterkey');
            $(this).toggleClass('col-active', (colSortStates[k] || 0) > 0);
        });
    };

    /** ProductType filter functions */
    window.toggleProductTypeFilter = function() {
        const $target = $('#productTypeFilterBox');
        $('.mp-filterbox').not($target).hide();
        $target.toggle();
    };
    window.closeProductTypeFilter = function() {
        $('#productTypeFilterBox').hide();
    };
    window.applyProductTypeFilter = function() {
        delete Filter['ProductType'];
        let selected = $('.ptype-checkbox:checked').map(function() { return $(this).val(); }).get();
        if (selected.length > 0) {
            Filter['ProductType'] = selected;
        }
        $('#productTypeFilterBox').hide();
        PageNo = 0;
        showProductPageDetails();
    };
    window.resetProductTypeFilter = function() {
        $('.ptype-checkbox').prop('checked', false);
        if (Filter.ProductType) {
            delete Filter['ProductType'];
            PageNo = 0;
            showProductPageDetails();
        } else {
            $('#productTypeFilterBox').hide();
        }
    };

    // Close filter boxes on outside click
    $(document).on('click', function(e) {
        if (!$(e.target).closest('#categoryFilterBox, #categoryFilter').length) {
            $('#categoryFilterBox').hide();
        }
        if (!$(e.target).closest('#productTypeFilterBox, #productTypeFilter').length) {
            $('#productTypeFilterBox').hide();
        }
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
        var getValue      = $(this).data('uid');
        var isComposite   = parseInt($(this).data('iscomposite')) || 0;
        if (getValue) {
            if (isComposite === 1) {
                loadComboForEdit(getValue);
            } else {
                retrieveProductDetails(getValue, false);
            }
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