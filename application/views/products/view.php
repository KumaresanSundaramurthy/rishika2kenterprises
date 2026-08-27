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
                    'pageTitle'       => $PageTitle       ?? 'Products',
                    'pageDescription' => $PageDescription ?? '',
                ]); ?>

                <?php
                $s   = $ProductStats ?? null;
                $cur = htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹');
                                ?>

                <?php if (($JwtData->GenSettings->ShowStats ?? 1) && ($JwtData->TransSettings->ShowTransactionStats ?? 1)): ?>
                <!-- ── Stats Strip ───────────────────────────────────────────── -->
                <div class="apex-stats-strip<?php echo ($ActiveTabData == 'group' || $ActiveTabData == 'pricelist' || $ActiveTabData == 'size') ? ' d-none' : ''; ?>" id="ProductStatsRow">
                    <div class="apex-stat-item" style="--stat-color:#059669;cursor:default;pointer-events:none">
                        <div class="apex-stat-icon" style="background:#ecfdf5"><i class="bx bx-package" style="color:#059669"></i></div>
                        <div class="apex-stat-body">
                            <div class="apex-stat-label">Total Products</div>
                            <div class="apex-stat-bottom">
                                <span class="apex-stat-count"><?php echo number_format((int)($s->TotalProducts ?? 0)); ?></span>
                                <span class="apex-stat-amount" style="font-size:.72rem">
                                    <span class="text-success"><?php echo number_format((int)($s->ActiveCount ?? 0)); ?> Active</span>
                                    &nbsp;|&nbsp;
                                    <span class="text-danger"><?php echo number_format((int)($s->InActiveCount ?? 0)); ?> In-Active</span>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="apex-stat-item" style="--stat-color:#0ea5e9;cursor:default;pointer-events:none">
                        <div class="apex-stat-icon" style="background:#e0f2fe"><i class="bx bx-rupee" style="color:#0ea5e9"></i></div>
                        <div class="apex-stat-body">
                            <div class="apex-stat-label">Stock Value</div>
                            <div class="apex-stat-bottom">
                                <span class="apex-stat-count"><?php echo $cur . ' ' . smartDecimal($s->TotalStockValue ?? 0); ?></span>
                                <span class="apex-stat-amount">&nbsp;</span>
                            </div>
                        </div>
                    </div>
                    <div class="apex-stat-item" style="--stat-color:#8b5cf6;cursor:default;pointer-events:none">
                        <div class="apex-stat-icon" style="background:#ede9fe"><i class="bx bx-calendar-plus" style="color:#8b5cf6"></i></div>
                        <div class="apex-stat-body">
                            <div class="apex-stat-label">Added</div>
                            <div class="apex-stat-bottom" style="gap:8px">
                                <span style="font-size:.72rem"><span class="fw-bold"><?php echo number_format((int)($s->AddedThisMonth ?? 0)); ?></span> Month</span>
                                <span style="font-size:.72rem"><span class="fw-bold"><?php echo number_format((int)($s->AddedThisFY ?? 0)); ?></span> FY</span>
                                <span style="font-size:.72rem"><span class="fw-bold"><?php echo number_format((int)($s->RecentlyUpdated ?? 0)); ?></span> 7d</span>
                            </div>
                        </div>
                    </div>
                    <div class="apex-stat-item" style="--stat-color:#ef4444;cursor:default;pointer-events:none">
                        <div class="apex-stat-icon" style="background:#fee2e2"><i class="bx bx-error" style="color:#ef4444"></i></div>
                        <div class="apex-stat-body">
                            <div class="apex-stat-label">Low Stock</div>
                            <div class="apex-stat-bottom">
                                <span class="apex-stat-count"><?php echo number_format((int)($s->LowStockItems ?? 0)); ?></span>
                                <span class="apex-stat-amount">&nbsp;</span>
                            </div>
                        </div>
                    </div>
                    <div class="apex-stat-item" style="--stat-color:#64748b;cursor:default;pointer-events:none">
                        <div class="apex-stat-icon" style="background:#f1f5f9"><i class="bx bx-block" style="color:#64748b"></i></div>
                        <div class="apex-stat-body">
                            <div class="apex-stat-label">Not For Sale</div>
                            <div class="apex-stat-bottom">
                                <span class="apex-stat-count"><?php echo number_format((int)($s->NotForSale ?? 0)); ?></span>
                                <span class="apex-stat-amount">&nbsp;</span>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <div class="container-xxl flex-grow-1">

                    <div class="card">

                        <!-- Filter Row -->
                        <div class="apex-filter-row">
                            <div class="r2k-search-wrap<?php echo !empty($InitSearch) ? ' is-expanded r2k-search-active' : ''; ?>">
                                <i class="bx bx-search r2k-si"></i>
                                <?php
                                $searchPlaceholderMap = ['item' => 'Search items...', 'group' => 'Search groups...', 'pricelist' => 'Search price lists...', 'category' => 'Search categories...', 'brand' => 'Search brands...', 'size' => 'Search sizes...'];
                                $searchPlaceholder = $searchPlaceholderMap[$ActiveTabData] ?? 'Search items...';
                                ?>
                                <input type="text" class="SearchDetails" id="SearchDetails" placeholder="<?php echo $searchPlaceholder; ?>" value="<?php echo htmlspecialchars($InitSearch ?? ''); ?>">
                                <i class="bx bx-x r2k-clear<?php echo !empty($InitSearch) ? '' : ' d-none'; ?>" id="clearSearch"></i>
                            </div>
                            <a href="javascript:void(0);" id="productTypeFilter" class="apex-filter-btn <?php echo $ActiveTabData == 'item' ? '' : 'd-none'; ?>" title="Filter by Product Type"><i class="bx bx-box me-1"></i>Type</a>
                            <a href="javascript:void(0);" id="statusFilter" class="apex-filter-btn <?php echo ($ActiveTabData == 'item' || $ActiveTabData == 'group') ? '' : 'd-none'; ?>" title="Filter by Status"><i class="bx bx-transfer me-1"></i>Status</a>
                            <a href="javascript:void(0);" id="taxFilter" class="apex-filter-btn <?php echo ($ActiveTabData == 'item' || $ActiveTabData == 'group') ? '' : 'd-none'; ?>" title="Filter by Tax"><i class="bx bx-receipt me-1"></i>Tax</a>
                            <a href="javascript:void(0);" id="categoryFilter" class="apex-filter-btn <?php echo $ActiveTabData == 'item' ? '' : 'd-none'; ?>" title="Filter by Category"><i class="bx bx-layer me-1"></i>Category</a>
                            <a href="javascript:void(0);" id="plStatusFilter" class="apex-filter-btn <?php echo $ActiveTabData == 'pricelist' ? '' : 'd-none'; ?>" title="Filter by Status"><i class="bx bx-transfer me-1"></i>Status</a>
                            <a href="javascript:void(0);" id="plAssignedToFilter" class="apex-filter-btn <?php echo $ActiveTabData == 'pricelist' ? '' : 'd-none'; ?>" title="Filter by Assigned To"><i class="bx bx-group me-1"></i>Assigned To</a>
                            <a href="javascript:void(0);" id="plScopeFilter" class="apex-filter-btn <?php echo $ActiveTabData == 'pricelist' ? '' : 'd-none'; ?>" title="Filter by Scope"><i class="bx bx-list-ul me-1"></i>Scope</a>
                            <a href="javascript:void(0);" id="productCatgFilter" class="apex-filter-btn <?php echo $ActiveTabData == 'category' ? '' : 'd-none'; ?>" title="Filter by Product"><i class="bx bx-package me-1"></i>Product</a>
                            <a href="javascript:void(0);" id="brandProductFilter" class="apex-filter-btn <?php echo $ActiveTabData == 'brand' ? '' : 'd-none'; ?>" title="Filter by Product"><i class="bx bx-package me-1"></i>Product</a>
                            <a href="javascript:void(0);" id="lastUpdatedFilter" class="apex-filter-btn" title="Filter by Last Updated"><i class="bx bx-user me-1"></i>Last Updated</a>
                            <div class="apex-filter-spacer"></div>
                            <a href="javascript:void(0);" class="apex-filter-btn PageRefresh" data-bs-toggle="tooltip" data-bs-placement="bottom" title="<?php echo t('page_refresh', 'Page Refresh'); ?>"><i class="bx bx-refresh"></i></a>
                            <a href="javascript:void(0);" class="apex-filter-btn <?php echo ($ActiveTabData == 'item' || $ActiveTabData == 'group') ? '' : 'd-none'; ?>" id="btnSyncProductsCache" title="Sync Items Cache"><i class="bx bx-planet"></i></a>
                            <a href="javascript:void(0);" class="apex-filter-btn <?php echo $ActiveTabData == 'category' ? '' : 'd-none'; ?>" id="btnSyncCategoriesCache" title="Sync Categories Cache"><i class="bx bx-planet"></i></a>
                            <a href="javascript:void(0);" class="apex-filter-btn <?php echo $ActiveTabData == 'pricelist' ? '' : 'd-none'; ?>" id="btnSyncPriceListCache" title="Sync Price List Cache"><i class="bx bx-planet"></i></a>
                            <a href="javascript:void(0);" class="apex-filter-btn <?php echo $ActiveTabData == 'brand' ? '' : 'd-none'; ?>" id="btnSyncBrandsCache" title="Sync Brands Cache"><i class="bx bx-planet"></i></a>
                            <a href="javascript:void(0);" class="apex-filter-btn <?php echo $ActiveTabData == 'size' ? '' : 'd-none'; ?>" id="btnSyncSizesCache" title="Sync Sizes Cache"><i class="bx bx-planet"></i></a>
                            <div class="btn-group d-none" id="ActionsDD-Div">
                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="actionsDropdown" data-bs-toggle="dropdown" aria-expanded="false" title="<?php echo t('lbl_actions', 'Actions'); ?>">
                                    <i class="bx bx-menu"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end r2k-export-menu r2k-actions-menu" aria-labelledby="actionsDropdown">
                                    <li class="d-none" id="DeleteOption">
                                        <a class="dropdown-item text-danger" href="javascript:void(0);" id="btnDelete"><i class="bx bx-trash me-2"></i><?php echo t('delete', 'Delete'); ?></a>
                                    </li>
                                </ul>
                            </div>
                            <?php $this->load->view('common/partials/export_btn'); ?>
                            <a href="javascript:void(0);" class="btn btn-primary btn-sm addItem <?php echo $ActiveTabData == 'item' ? '' : 'd-none'; ?>" id="NewItem" data-bs-toggle="tooltip" data-bs-placement="bottom" title="<?php echo t('create_item', 'Create Item'); ?>"><i class="bx bx-plus me-1"></i><?php echo t('lbl_new', 'New'); ?></a>
                            <a href="javascript:void(0);" class="btn btn-primary btn-sm <?php echo $ActiveTabData == 'group' ? '' : 'd-none'; ?>" id="NewComboItem" data-bs-toggle="tooltip" data-bs-placement="bottom" title="<?php echo t('create_item_group', 'Create Group'); ?>"><i class="bx bx-git-merge me-1"></i><?php echo t('lbl_new', 'New'); ?></a>
                            <a href="javascript:void(0);" class="btn btn-primary btn-sm <?php echo $ActiveTabData == 'pricelist' ? '' : 'd-none'; ?>" id="NewPriceList" data-bs-toggle="tooltip" data-bs-placement="bottom" title="<?php echo t('create_price_list', 'Create Price List'); ?>"><i class="bx bx-plus me-1"></i><?php echo t('lbl_new', 'New'); ?></a>
                            <a href="javascript:void(0);" class="btn btn-primary btn-sm addCategory <?php echo $ActiveTabData == 'category' ? '' : 'd-none'; ?>" id="NewCategory" data-bs-toggle="tooltip" data-bs-placement="bottom" title="<?php echo t('create_category', 'Create Category'); ?>"><i class="bx bx-plus me-1"></i><?php echo t('lbl_new', 'New'); ?></a>
                            <a href="javascript:void(0);" class="btn btn-primary btn-sm addBrand <?php echo $ActiveTabData == 'brand' ? '' : 'd-none'; ?>" id="NewBrand" data-bs-toggle="tooltip" data-bs-placement="bottom" title="<?php echo t('create_brand', 'Create Brand'); ?>"><i class="bx bx-plus me-1"></i><?php echo t('lbl_new', 'New'); ?></a>
                            <a href="javascript:void(0);" class="btn btn-primary btn-sm addSize <?php echo $ActiveTabData == 'size' ? '' : 'd-none'; ?>" id="NewSize" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Create Size"><i class="bx bx-plus me-1"></i><?php echo t('lbl_new', 'New'); ?></a>
                        </div>

                        <!-- Tabs Row -->
                        <div class="apex-tabs-row">
                            <ul class="nav trans-status-tabs" role="tablist" data-trans-path="/products">
                                <li class="nav-item">
                                    <a class="nav-link <?php echo $ActiveTabData == 'item' ? 'active' : ''; ?> TabPane" data-id="Item" data-status="Item" data-url-tab="items" role="tab" data-bs-toggle="tab" data-bs-target="#NavItemPage" href="javascript:void(0);">
                                        <i class="bx bx-package me-1"></i> Items
                                        <span class="trans-tab-count<?php echo ($ActiveTabData != 'item' || $ProductTotalCount == 0) ? ' d-none' : ''; ?>" id="productTotalCount"><?php echo $ProductTotalCount > 0 ? $ProductTotalCount : ''; ?></span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link <?php echo $ActiveTabData == 'group' ? 'active' : ''; ?> TabPane" data-id="Groups" data-status="Groups" data-url-tab="groups" role="tab" data-bs-toggle="tab" data-bs-target="#NavGroupsPage" href="javascript:void(0);">
                                        <i class="bx bx-git-merge me-1"></i> Groups
                                        <span class="trans-tab-count<?php echo ($ActiveTabData != 'group' || $ModTotalCount == 0) ? ' d-none' : ''; ?>" id="groupTotalCount"><?php echo ($ActiveTabData == 'group' && $ModTotalCount > 0) ? $ModTotalCount : ''; ?></span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link <?php echo $ActiveTabData == 'pricelist' ? 'active' : ''; ?> TabPane" data-id="PriceLists" data-status="PriceLists" data-url-tab="pricelists" role="tab" data-bs-toggle="tab" data-bs-target="#NavPriceListPage" href="javascript:void(0);">
                                        <i class="bx bx-purchase-tag me-1"></i> Price Lists
                                        <span class="trans-tab-count<?php echo ($ActiveTabData != 'pricelist' || ($PriceListTotalCount ?? 0) == 0) ? ' d-none' : ''; ?>" id="priceListTotalCount"><?php echo ($ActiveTabData == 'pricelist' && ($PriceListTotalCount ?? 0) > 0) ? $PriceListTotalCount : ''; ?></span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link <?php echo $ActiveTabData == 'category' ? 'active' : ''; ?> TabPane" data-id="Categories" data-status="Categories" data-url-tab="categories" role="tab" data-bs-toggle="tab" data-bs-target="#NavCategoriesPage" href="javascript:void(0);">
                                        <i class="bx bx-layer me-1"></i> Categories
                                        <span class="trans-tab-count<?php echo ($ActiveTabData != 'category' || $ModTotalCount == 0) ? ' d-none' : ''; ?>" id="categoryTotalCount"><?php echo ($ActiveTabData == 'category' && $ModTotalCount > 0) ? $ModTotalCount : ''; ?></span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link <?php echo $ActiveTabData == 'brand' ? 'active' : ''; ?> TabPane" data-id="Brands" data-status="Brands" data-url-tab="brands" role="tab" data-bs-toggle="tab" data-bs-target="#NavBrandsPage" href="javascript:void(0);">
                                        <i class="bx bx-purchase-tag me-1"></i> Brands
                                        <span class="trans-tab-count<?php echo ($ActiveTabData != 'brand' || $ModTotalCount == 0) ? ' d-none' : ''; ?>" id="brandTotalCount"><?php echo ($ActiveTabData == 'brand' && $ModTotalCount > 0) ? $ModTotalCount : ''; ?></span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link <?php echo $ActiveTabData == 'size' ? 'active' : ''; ?> TabPane" data-id="Sizes" data-status="Sizes" data-url-tab="sizes" role="tab" data-bs-toggle="tab" data-bs-target="#NavSizesPage" href="javascript:void(0);">
                                        <i class="bx bx-ruler me-1"></i> Sizes
                                        <span class="trans-tab-count<?php echo ($ActiveTabData != 'size' || $ModTotalCount == 0) ? ' d-none' : ''; ?>" id="sizeTotalCount"><?php echo ($ActiveTabData == 'size' && $ModTotalCount > 0) ? $ModTotalCount : ''; ?></span>
                                    </a>
                                </li>
                            </ul>
                        </div>

                        <div class="tab-content p-0">

                                    <div class="tab-pane fade <?php echo $ActiveTabData == 'item' ? 'show active' : ''; ?>" id="NavItemPage" role="tabpanel">

                                        <!-- Select-all banner -->
                                        <div id="prodSelectAllBanner" class="r2k-select-all-banner d-none">
                                            <span id="prodSelectAllMsg"></span>
                                            <a href="javascript:void(0);" id="prodSelectAllLink" class="ms-2"></a>
                                            <a href="javascript:void(0);" id="prodSelectAllClear" class="ms-2 d-none">Clear selection</a>
                                        </div>

                                        <div class="table-responsive text-nowrap h-100 tablecard">
                                            <table class="table trans-table table-hover" id="ProductsTable">
                                                <thead class="r2k-thead">
                                                    <tr>
                                                        <th class="table-checkbox text-center align-middle">
                                                            <div class="form-check d-flex justify-content-center align-items-center mb-0">
                                                                <input class="form-check-input table-chkbox productsHeaderCheck" type="checkbox">
                                                            </div>
                                                        </th>
                                                        <th class="table-serialno <?php echo $JwtData->GenSettings->SerialNoDisplay == 1 ? '' : 'd-none'; ?>"><?php echo t('col_sno', 'S.No'); ?></th>
                                                        <th class="name-sortable position-relative" id="sortName" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Click for ascending order">
                                                            <span class="sort-label cursor-pointer"><?php echo t('col_item', 'Item'); ?> <i class="bx bx-sort-alt-2 sort-icon ms-1"></i></span>
                                                        </th>
                                                        <th class="col-sortable cursor-pointer position-relative" data-filterkey="StatusSorting" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Click for ascending order">
                                                            <?php echo t('col_status', 'Status'); ?> <i class="bx bx-sort-alt-2 sort-icon ms-1"></i>
                                                        </th>
                                                        <th class="col-sortable cursor-pointer position-relative" data-filterkey="CategorySorting" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Click for ascending order">
                                                            <?php echo t('col_category', 'Category'); ?> <i class="bx bx-sort-alt-2 sort-icon ms-1"></i>
                                                        </th>
                                                        <th class="col-sortable cursor-pointer" data-filterkey="QtySorting" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Click for ascending order"><?php echo t('col_qty', 'Qty'); ?> <i class="bx bx-sort-alt-2 sort-icon ms-1"></i></th>
                                                        <th class="col-sortable cursor-pointer" data-filterkey="MRPSorting" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Click for ascending order"><?php echo t('col_mrp', 'MRP'); ?> <i class="bx bx-sort-alt-2 sort-icon ms-1"></i></th>
                                                        <th class="col-sortable cursor-pointer" data-filterkey="SellingPriceSorting" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Click for ascending order"><?php echo t('col_selling_price', 'Selling Price'); ?> <i class="bx bx-sort-alt-2 sort-icon ms-1"></i></th>
                                                        <th class="col-sortable cursor-pointer" data-filterkey="PurchasePriceSorting" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Click for ascending order"><?php echo t('col_purchase_price', 'Purchase Price'); ?> <i class="bx bx-sort-alt-2 sort-icon ms-1"></i></th>
                                                        <th><?php echo t('col_last_updated', 'Last Updated'); ?></th>
                                                        <th class="text-center"><?php echo t('col_actions', 'Actions'); ?></th>
                                                    </tr>
                                                </thead>
                                                <tbody class="r2k-tbody table-border-bottom-0">
                                                    <?php if ($ActiveTabData == 'item') {
                                                        echo $ModRowData;
                                                    } else {
                                                        $PageData['DataLists'] = [];
                                                        echo $this->load->view('products/items/list', $PageData, TRUE);
                                                    } ?>
                                                </tbody>
                                            </table>
                                        </div>
                                        <div class="row mx-0 px-3 mt-1 justify-content-between ProductsPagination apex-pag-sticky" id="ProductsPagination">
                                            <?php echo $ActiveTabData == 'item' ? $ModPagination : ''; ?>
                                        </div>

                                    </div>

                                    <div class="tab-pane fade <?php echo $ActiveTabData == 'group' ? 'show active' : ''; ?>" id="NavGroupsPage" role="tabpanel">

                                        <div class="table-responsive text-nowrap h-100 tablecard">
                                            <table class="table trans-table table-hover" id="GroupsTable">
                                                <thead class="r2k-thead">
                                                    <tr>
                                                        <th class="table-checkbox text-center align-middle">
                                                            <div class="form-check d-flex justify-content-center align-items-center mb-0">
                                                                <input class="form-check-input table-chkbox groupsHeaderCheck" type="checkbox">
                                                            </div>
                                                        </th>
                                                        <th class="table-serialno <?php echo $JwtData->GenSettings->SerialNoDisplay == 1 ? '' : 'd-none'; ?>"><?php echo t('col_sno', 'S.No'); ?></th>
                                                        <th class="name-sortable position-relative" id="sortGroupName" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Click for ascending order">
                                                            <span class="sort-label cursor-pointer"><?php echo t('col_item', 'Item'); ?> <i class="bx bx-sort-alt-2 sort-icon ms-1"></i></span>
                                                        </th>
                                                        <th class="col-sortable cursor-pointer position-relative" data-filterkey="StatusSorting" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Click for ascending order">
                                                            <?php echo t('col_status', 'Status'); ?> <i class="bx bx-sort-alt-2 sort-icon ms-1"></i>
                                                        </th>
                                                        <th><?php echo t('col_unit', 'Unit'); ?></th>
                                                        <th class="col-sortable cursor-pointer position-relative" data-filterkey="MRPSorting" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Click for ascending order">
                                                            <?php echo t('col_mrp', 'MRP'); ?> <i class="bx bx-sort-alt-2 sort-icon ms-1"></i>
                                                        </th>
                                                        <th class="col-sortable cursor-pointer position-relative" data-filterkey="SellingPriceSorting" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Click for ascending order">
                                                            <?php echo t('col_selling_price', 'Selling Price'); ?> <i class="bx bx-sort-alt-2 sort-icon ms-1"></i>
                                                        </th>
                                                        <th><?php echo t('col_last_updated', 'Last Updated'); ?></th>
                                                        <th class="text-center"><?php echo t('col_actions', 'Actions'); ?></th>
                                                    </tr>
                                                </thead>
                                                <tbody class="r2k-tbody table-border-bottom-0">
                                                    <?php if ($ActiveTabData == 'group') {
                                                        echo $ModRowData;
                                                    } else {
                                                        $PageData['DataLists'] = [];
                                                        echo $this->load->view('products/items/list', $PageData, TRUE);
                                                    } ?>
                                                </tbody>
                                            </table>
                                        </div>
                                        <div class="row mx-0 px-3 mt-1 justify-content-between GroupsPagination apex-pag-sticky" id="GroupsPagination">
                                            <?php echo $ActiveTabData == 'group' ? $ModPagination : ''; ?>
                                        </div>

                                    </div>

                                    <div class="tab-pane fade <?php echo $ActiveTabData == 'pricelist' ? 'show active' : ''; ?>" id="NavPriceListPage" role="tabpanel">

                                        <div class="table-responsive text-nowrap h-100 tablecard">
                                            <table class="table trans-table table-hover" id="PriceListTable">
                                                <thead class="r2k-thead">
                                                    <tr>
                                                        <th class="table-checkbox text-center align-middle">
                                                            <div class="form-check d-flex justify-content-center align-items-center mb-0">
                                                                <input class="form-check-input table-chkbox priceListHeaderCheck" type="checkbox">
                                                            </div>
                                                        </th>
                                                        <th class="table-serialno <?php echo $JwtData->GenSettings->SerialNoDisplay == 1 ? '' : 'd-none'; ?>"><?php echo t('col_sno', 'S.No'); ?></th>
                                                        <th class="name-sortable position-relative" id="sortPLName" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Click for ascending order">
                                                            <span class="sort-label cursor-pointer"><?php echo t('col_name', 'Name'); ?> <i class="bx bx-sort-alt-2 sort-icon ms-1"></i></span>
                                                        </th>
                                                        <th><?php echo t('col_applies_to', 'Applies To'); ?></th>
                                                        <th><?php echo t('col_discount_type', 'Discount Type'); ?></th>
                                                        <th><?php echo t('col_valid_from', 'Valid From'); ?></th>
                                                        <th><?php echo t('col_valid_to', 'Valid To'); ?></th>
                                                        <th class="col-sortable cursor-pointer position-relative" data-filterkey="StatusSorting" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Click for ascending order">
                                                            <?php echo t('col_status', 'Status'); ?> <i class="bx bx-sort-alt-2 sort-icon ms-1"></i>
                                                        </th>
                                                        <th><?php echo t('col_last_updated', 'Last Updated'); ?></th>
                                                        <th class="text-center"><?php echo t('col_actions', 'Actions'); ?></th>
                                                    </tr>
                                                </thead>
                                                <tbody class="r2k-tbody table-border-bottom-0" id="PriceListTableBody">
                                                    <?php if ($ActiveTabData == 'pricelist' && !empty($ModRowData)): ?>
                                                        <?php echo $ModRowData; ?>
                                                    <?php else: ?>
                                                        <tr class="r2k-empty-row">
                                                            <td colspan="10" class="text-center py-5 text-muted">
                                                                <i class="bx bx-purchase-tag fs-1 d-block mx-auto mb-2 opacity-25"></i>
                                                                <?php echo t('empty_price_lists', 'No price lists found'); ?>. Click <strong><?php echo t('create_price_list', 'Create Price List'); ?></strong> to add one.
                                                            </td>
                                                        </tr>
                                                    <?php endif; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                        <div class="row mx-0 px-3 mt-1 justify-content-between PriceListPagination apex-pag-sticky" id="PriceListPagination">
                                            <?php echo ($ActiveTabData == 'pricelist' && !empty($ModPagination)) ? $ModPagination : ''; ?>
                                        </div>

                                    </div>

                                    <div class="tab-pane fade <?php echo $ActiveTabData == 'category' ? 'show active' : ''; ?>" id="NavCategoriesPage" role="tabpanel">

                                        <div class="table-responsive text-nowrap h-100 tablecard">
                                            <table class="table trans-table table-hover" id="CategoriesTable">
                                                <thead class="r2k-thead">
                                                    <tr>
                                                        <th class="table-checkbox">
                                                            <div class="form-check form-check-inline">
                                                                <input class="form-check-input table-chkbox categoryHeaderCheck" type="checkbox">
                                                            </div>
                                                        </th>
                                                        <th class="table-serialno <?php echo $JwtData->GenSettings->SerialNoDisplay == 1 ? '' : 'd-none'; ?>"><?php echo t('col_sno', 'S.No'); ?></th>
                                                        <th class="name-sortable position-relative" id="sortCatgName" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Click for ascending order">
                                                            <span class="sort-label cursor-pointer"><?php echo t('col_name', 'Name'); ?> <i class="bx bx-sort-alt-2 sort-icon ms-1"></i></span>
                                                        </th>
                                                        <th><?php echo t('col_products', 'Products'); ?></th>
                                                        <th><?php echo t('col_last_updated', 'Last Updated'); ?></th>
                                                        <th class="text-center"><?php echo t('col_actions', 'Actions'); ?></th>
                                                    </tr>
                                                </thead>
                                                <tbody class="r2k-tbody table-border-bottom-0">
                                                    <?php if ($ActiveTabData == 'category') {
                                                        echo $ModRowData;
                                                    } else {
                                                        $PageData['DataLists'] = [];
                                                        echo $this->load->view('products/categories/list', $PageData, TRUE);
                                                    } ?>
                                                </tbody>
                                            </table>
                                        </div>
                                        <div class="d-flex align-items-center justify-content-between mx-0 px-3 mt-1 CategoriesPagination apex-pag-sticky" id="CategoriesPagination">
                                            <?php echo $ActiveTabData == 'category' ? $ModPagination : ''; ?>
                                        </div>

                                    </div>

                                    <div class="tab-pane fade <?php echo $ActiveTabData == 'brand' ? 'show active' : ''; ?>" id="NavBrandsPage" role="tabpanel">

                                        <div class="table-responsive text-nowrap h-100 tablecard">
                                            <table class="table trans-table table-hover" id="BrandsTable">
                                                <thead class="r2k-thead">
                                                    <tr>
                                                        <th class="table-checkbox">
                                                            <div class="form-check form-check-inline">
                                                                <input class="form-check-input table-chkbox brandHeaderCheck" type="checkbox">
                                                            </div>
                                                        </th>
                                                        <th class="table-serialno <?php echo $JwtData->GenSettings->SerialNoDisplay == 1 ? '' : 'd-none'; ?>"><?php echo t('col_sno', 'S.No'); ?></th>
                                                        <th class="name-sortable position-relative" id="sortBrandName" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Click for ascending order">
                                                            <span class="sort-label cursor-pointer"><?php echo t('col_name', 'Name'); ?> <i class="bx bx-sort-alt-2 sort-icon ms-1"></i></span>
                                                        </th>
                                                        <th><?php echo t('col_products', 'Products'); ?></th>
                                                        <th><?php echo t('col_last_updated', 'Last Updated'); ?></th>
                                                        <th class="text-center"><?php echo t('col_actions', 'Actions'); ?></th>
                                                    </tr>
                                                </thead>
                                                <tbody class="r2k-tbody table-border-bottom-0">
                                                    <?php if ($ActiveTabData == 'brand') {
                                                        echo $ModRowData;
                                                    } else {
                                                        $PageData['DataLists'] = [];
                                                        echo $this->load->view('products/brands/list', $PageData, TRUE);
                                                    } ?>
                                                </tbody>
                                            </table>
                                        </div>
                                        <div class="d-flex align-items-center justify-content-between mx-0 px-3 mt-1 BrandsPagination apex-pag-sticky" id="BrandsPagination">
                                            <?php echo $ActiveTabData == 'brand' ? $ModPagination : ''; ?>
                                        </div>

                                    </div>

                                    <div class="tab-pane fade <?php echo $ActiveTabData == 'size' ? 'show active' : ''; ?>" id="NavSizesPage" role="tabpanel">

                                        <div class="table-responsive text-nowrap h-100 tablecard">
                                            <table class="table trans-table table-hover" id="SizesTable">
                                                <thead class="r2k-thead">
                                                    <tr>
                                                        <th class="table-checkbox">
                                                            <div class="form-check form-check-inline">
                                                                <input class="form-check-input table-chkbox sizeHeaderCheck" type="checkbox">
                                                            </div>
                                                        </th>
                                                        <th class="table-serialno <?php echo $JwtData->GenSettings->SerialNoDisplay == 1 ? '' : 'd-none'; ?>"><?php echo t('col_sno', 'S.No'); ?></th>
                                                        <th class="name-sortable position-relative" id="sortSizeName" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Click for ascending order">
                                                            <span class="sort-label cursor-pointer"><?php echo t('col_name', 'Name'); ?> <i class="bx bx-sort-alt-2 sort-icon ms-1"></i></span>
                                                        </th>
                                                        <th>Dimensions</th>
                                                        <th><?php echo t('col_products', 'Products'); ?></th>
                                                        <th><?php echo t('col_last_updated', 'Last Updated'); ?></th>
                                                        <th class="text-center"><?php echo t('col_actions', 'Actions'); ?></th>
                                                    </tr>
                                                </thead>
                                                <tbody class="r2k-tbody table-border-bottom-0">
                                                    <?php if ($ActiveTabData == 'size') {
                                                        echo $ModRowData;
                                                    } else {
                                                        $PageData['DataLists'] = [];
                                                        echo $this->load->view('products/sizes/list', $PageData, TRUE);
                                                    } ?>
                                                </tbody>
                                            </table>
                                        </div>
                                        <div class="d-flex align-items-center justify-content-between mx-0 px-3 mt-1 SizesPagination apex-pag-sticky" id="SizesPagination">
                                            <?php echo $ActiveTabData == 'size' ? $ModPagination : ''; ?>
                                        </div>

                                    </div>


                        </div>
                    </div>


                </div>
            </div>
            <!-- Content wrapper -->

            <?php $this->load->view('common/imagepreview_modal'); ?>
            <?php $this->load->view('common/modals/product_profile_modal'); ?>

            <?php $this->load->view('common/modals/product_form'); ?>
            <?php $this->load->view('common/modals/category_form'); ?>
            <?php $this->load->view('products/modals/combo'); ?>
            <?php $this->load->view('products/modals/category'); ?>
            <?php $this->load->view('products/modals/brand'); ?>
            <?php $this->load->view('products/modals/sizes'); ?>
            <?php $this->load->view('products/modals/barcodeprint'); ?>
            <?php $this->load->view('products/modals/pricelist'); ?>
            <?php $this->load->view('products/modals/brand_stock'); ?>

            <!-- Category Products Modal -->
            <style>
            @keyframes catgPulse { 0%,100%{opacity:1} 50%{opacity:.4} }
            .catg-skeleton { animation: catgPulse 1.4s ease-in-out infinite; }
            </style>
            <div class="modal fade" id="catgProductsModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-scrollable">
                    <div class="modal-content" style="overflow:hidden;">
                        <button type="button" class="btn-close position-absolute" data-bs-dismiss="modal"
                            style="top:14px;right:16px;z-index:10;background-color:rgba(255,255,255,.85);border-radius:50%;padding:6px;box-shadow:0 1px 4px rgba(0,0,0,.15);"
                            aria-label="Close"></button>
                        <div class="modal-body p-0">
                            <div id="catgProductsModalBody">
                                <div class="d-flex justify-content-center py-5"><div class="spinner-border text-primary"></div></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php $this->load->view('common/footer_desc'); ?>

        </div>

    </div>
</div>

<?php $this->load->view('common/filter_panels/col_filter_box', [
    'ColFilterConfig' => [
        'id'         => 'productTypeFilterBox',
        'triggerId'  => 'productTypeFilter',
        'title'      => 'Product Type',
        'icon'       => 'bx-box',
        'filterKey'  => 'ProductType',
        'checkClass' => 'ptype-checkbox',
        'items'      => [
            ['value' => 'Product', 'label' => 'Product'],
            ['value' => 'Service', 'label' => 'Service'],
        ],
    ],
]); ?>
<?php $this->load->view('common/filter_panels/col_filter_box', [
    'ColFilterConfig' => [
        'id'         => 'statusFilterBox',
        'triggerId'  => 'statusFilter',
        'title'      => 'Status',
        'icon'       => 'bx-transfer',
        'filterKey'  => 'StatusFilter',
        'checkClass' => 'status-checkbox',
        'items'      => [
            ['value' => '1', 'label' => 'Active'],
            ['value' => '0', 'label' => 'In-Active'],
        ],
    ],
]); ?>

<?php $this->load->view('common/filter_panels/col_filter_box', [
    'ColFilterConfig' => [
        'id'         => 'taxFilterBox',
        'triggerId'  => 'taxFilter',
        'title'      => 'Tax',
        'icon'       => 'bx-receipt',
        'filterKey'  => 'Tax',
        'checkClass' => 'tax-checkbox',
        'items'      => [],
    ],
]); ?>

<!-- Category filter box — content built dynamically by CategoryAppend.filterBox() -->
<div id="categoryFilterBox" class="card mp-filterbox trans-col-filterbox"
     data-trigger-id="categoryFilter"
     data-filter-key="Category"
     data-chk-class="category-checkbox"
     style="display:none;position:fixed;z-index:9999;width:280px;"></div>

<?php $this->load->view('common/filter_panels/col_filter_box', [
    'ColFilterConfig' => [
        'id'         => 'plStatusFilterBox',
        'triggerId'  => 'plStatusFilter',
        'title'      => 'Status',
        'icon'       => 'bx-transfer',
        'filterKey'  => 'StatusFilter',
        'checkClass' => 'pl-status-checkbox',
        'items'      => [
            ['value' => '1', 'label' => 'Active'],
            ['value' => '0', 'label' => 'In-Active'],
        ],
    ],
]); ?>
<?php $this->load->view('common/filter_panels/col_filter_box', [
    'ColFilterConfig' => [
        'id'         => 'plAssignedToFilterBox',
        'triggerId'  => 'plAssignedToFilter',
        'title'      => 'Assigned To',
        'icon'       => 'bx-group',
        'filterKey'  => 'AssignedToFilter',
        'checkClass' => 'pl-assignedto-checkbox',
        'items'      => [
            ['value' => 'All',       'label' => 'All Customers'],
            ['value' => 'Groups',    'label' => 'Customer Groups'],
            ['value' => 'Customers', 'label' => 'Specific Customers'],
        ],
    ],
]); ?>
<?php $this->load->view('common/filter_panels/col_filter_box', [
    'ColFilterConfig' => [
        'id'         => 'plScopeFilterBox',
        'triggerId'  => 'plScopeFilter',
        'title'      => 'Scope',
        'icon'       => 'bx-list-ul',
        'filterKey'  => 'ScopeFilter',
        'checkClass' => 'pl-scope-checkbox',
        'items'      => [
            ['value' => 'All',      'label' => 'All Products'],
            ['value' => 'Specific', 'label' => 'Specific Products'],
        ],
    ],
]); ?>

<!-- Product filter for Categories tab — items lazy-loaded from Upstash on first click -->
<?php $this->load->view('common/filter_panels/col_filter_box', [
    'ColFilterConfig' => [
        'id'         => 'productCatgFilterBox',
        'triggerId'  => 'productCatgFilter',
        'title'      => 'Product',
        'icon'       => 'bx-package',
        'filterKey'  => 'ProductFilter',
        'checkClass' => 'prod-catg-chk',
        'items'      => [],
    ],
]); ?>

<!-- Product filter for Brands tab — items lazy-loaded from Upstash on first click -->
<?php $this->load->view('common/filter_panels/col_filter_box', [
    'ColFilterConfig' => [
        'id'         => 'brandProductFilterBox',
        'triggerId'  => 'brandProductFilter',
        'title'      => 'Product',
        'icon'       => 'bx-package',
        'filterKey'  => 'ProductFilter',
        'checkClass' => 'brand-prod-chk',
        'items'      => [],
    ],
]); ?>

<?php if (!empty($OrgUsers)): ?>
<?php $this->load->view('common/filter_panels/col_user_filter_box', [
    'ColUserFilterConfig' => [
        'id'         => 'lastUpdatedFilterBox',
        'triggerId'  => 'lastUpdatedFilter',
        'title'      => 'Last Updated',
        'checkClass' => 'last-upd-chk',
        'OrgUsers'   => $OrgUsers ?? [],
    ],
]); ?>
<?php endif; ?>

<?php $this->load->view('common/footer'); ?>

<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script src="<?php echo _assetV('/js/common/address.js'); ?>"></script>
<script src="<?php echo _assetV('/js/common/bankdetails.js'); ?>"></script>
<script src="<?php echo _assetV('/js/common/gstin_fetch.js'); ?>"></script>
<link rel="stylesheet" href="<?php echo _assetV('/assets/vendor/css/attachments.css'); ?>">
<script src="<?php echo _assetV('/js/common/attachments.js'); ?>"></script>
<script src="<?php echo _assetV('/js/common/phone_cc_dropdown.js'); ?>"></script>
<script src="<?php echo _assetV('/js/common/customer_form.js'); ?>"></script>
<script src="<?php echo _assetV('/js/common/category_form.js'); ?>"></script>
<script src="<?php echo _assetV('/js/common/product_form.js'); ?>"></script>
<script src="<?php echo _assetV('/js/core/col_filter.js'); ?>"></script>
<script src="<?php echo _assetV('/js/products/products.js'); ?>"></script>
<script src="<?php echo _assetV('/js/common/productappend.js'); ?>"></script>
<script src="<?php echo _assetV('/js/combinemodules/combo.js'); ?>"></script>
<script src="<?php echo _assetV('/js/common/pagecheckbox.js'); ?>"></script>
<script src="<?php echo _assetV('/js/products/barcodeprint.js'); ?>"></script>

<script>

let ItemModuleId = 4;
const ProdTable = '#ProductsTable';
const ProdPag = '.ProductsPagination';
const ProdHeader = '.productsHeaderCheck';
const ProdRow = '.productsCheck';
const GroupTable = '#GroupsTable';
const GroupPag = '.GroupsPagination';
const GroupHeader = '.groupsHeaderCheck';
let CategoryModuleId = 5;
const CatgTable = '#CategoriesTable';
const CatgPag = '.CategoriesPagination';
const CatgHeader = '.categoryHeaderCheck';
const CatgRow = '.categoryCheck';
const BrandTable = '#BrandsTable';
const BrandPag = '.BrandsPagination';
const BrandHeader = '.brandHeaderCheck';
const BrandRow = '.brandCheck';
const SizeTable = '#SizesTable';
const SizePag = '.SizesPagination';
const SizeHeader = '.sizeHeaderCheck';
const SizeRow = '.sizeCheck';
const PLTable  = '#PriceListTable';
const PLPag    = '#PriceListPagination';
const PLHeader = '.priceListHeaderCheck';
const PLRow    = '.priceListCheck';
let ActiveTabId = '<?php echo $ActiveTabName; ?>';
let ActiveTabModuleId = 4
var EnableStorage = <?php echo $JwtData->GenSettings->EnableStorage; ?>;
var CommonRowColumnDisp = 1;
let imgData;
var _prodInitSearch = <?php echo json_encode($InitSearch ?? ''); ?>;
let sortState = 0;
let catgSortState = 0;
let brandSortState = 0;
let sizeSortState = 0;
let groupSortState = 0;
let plNameSortState = 0;
var _catgListDirty = false;
var _brandListDirty = false;
var _sizeListDirty = false;
let colSortStates = {};
$(function() {
    'use strict';

    // Auto-show/hide Actions gear button when options become visible or hidden
    (function () {
        var $dd = $('#ActionsDD-Div');
        function syncDD() {
            $dd.toggleClass('d-none', $('#DeleteOption').hasClass('d-none'));
        }
        var el = document.getElementById('DeleteOption');
        if (el) new MutationObserver(syncDD).observe(el, { attributes: true, attributeFilter: ['class'] });
    })();

    $('#SearchDetails').val(_prodInitSearch || '');
    $(ProdHeader + ',' + ProdRow).prop('checked', false).trigger('change');

    if (_prodInitSearch && _prodInitSearch.length >= 3) {
        Filter['SearchAllData'] = _prodInitSearch;
        $('#clearSearch').removeClass('d-none');
        PageNo = 0;
    }

    $('.TabPane').click(function(e) {
        e.preventDefault();
        var TabValue = $(this).data('id');
        if (TabValue) {
            SelectedUIDs = [];
            _prodClearSelectAll();
            ActiveTabId = TabValue;
            ActiveTabModuleId = $(this).data('moduleid');
            $('.trans-tab-count').addClass('d-none');
            _pushTabUrl(TabValue, '');
            $('#ProductStatsRow').toggleClass('d-none', TabValue === 'Groups' || TabValue === 'PriceLists' || TabValue === 'Sizes');
            $('#NewItem,#NewComboItem,#NewPriceList,#NewCategory,#NewBrand,#NewSize,#CloneOption,#DeleteOption,#ItemCategory-Div').addClass('d-none');
            $('#ActionsDD-Div').addClass('d-none');
            $('#productTypeFilter,#categoryFilter').toggleClass('d-none', TabValue !== 'Item');
            $('#statusFilter,#taxFilter').toggleClass('d-none', TabValue !== 'Item' && TabValue !== 'Groups');
            $('#plStatusFilter,#plAssignedToFilter,#plScopeFilter').toggleClass('d-none', TabValue !== 'PriceLists');
            $('#productCatgFilter').toggleClass('d-none', TabValue !== 'Categories');
            $('#brandProductFilter').toggleClass('d-none', TabValue !== 'Brands');
            $('#btnSyncSizesCache').toggleClass('d-none', TabValue !== 'Sizes');
            var _prodSearchPlaceholders = { Item: 'Search items...', Groups: 'Search groups...', PriceLists: 'Search price lists...', Categories: 'Search categories...', Brands: 'Search brands...', Sizes: 'Search sizes...' };
            $('#SearchDetails').val('').attr('placeholder', _prodSearchPlaceholders[TabValue] || 'Search...');
            PageNo = 0;
            Filter = {};
            // Reset all sort visual states
            sortState = 0; catgSortState = 0; brandSortState = 0; sizeSortState = 0; groupSortState = 0; plNameSortState = 0; colSortStates = {};
            $('.name-sortable .sort-icon, .col-sortable .sort-icon').removeClass('bx-sort-up bx-sort-down text-primary').addClass('bx-sort-alt-2');
            $('.name-sortable, .col-sortable').removeClass('col-active').attr('data-bs-title', 'Click for ascending order');
            $('.mp-filterbox').hide();
            $('#categoryFilter, #productTypeFilter, #statusFilter, #taxFilter').removeClass('text-primary');
            $('#plStatusFilter, #plAssignedToFilter, #plScopeFilter').removeClass('text-primary has-filter');
            $('#productCatgFilter, #brandProductFilter, #lastUpdatedFilter').removeClass('has-filter');
            if (lastUpdatedFilter) lastUpdatedFilter.reset();
            if (typeof brandProductFilter !== 'undefined') brandProductFilter.reset();
            if (typeof prodCatgFilter !== 'undefined') prodCatgFilter.reset();
            $('#ProductCountWrap').addClass('d-none');
            $('#btnSyncProductsCache,#btnSyncCategoriesCache,#btnSyncPriceListCache,#btnSyncBrandsCache,#btnSyncSizesCache').addClass('d-none');
            if (ActiveTabId == 'Item') {
                $('#NewItem,#ItemCategory-Div,#ProductCountWrap').removeClass('d-none');
                $('#btnSyncProductsCache').removeClass('d-none');
                var itemLen = $(ProdTable + ' ' + ProdRow).length;
                if (itemLen == 0) {
                    getProductDetails(PageNo, RowLimit, Filter);
                } else {
                    $(ProdHeader).prop('checked', false).prop('indeterminate', false);
                    unSelectTableRecords(ProdTable, ProdRow);
                    updateProductCount(parseInt($('#productTotalCount').text(), 10) || 0);
                }
            } else if (ActiveTabId == 'Groups') {
                $('#NewComboItem').removeClass('d-none');
                $('#btnSyncProductsCache').removeClass('d-none');
                var grpLen = $(GroupTable + ' ' + ProdRow).length;
                if (grpLen == 0) {
                    getGroupDetails(PageNo, RowLimit, Filter);
                } else {
                    $(GroupHeader).prop('checked', false).prop('indeterminate', false);
                    unSelectTableRecords(GroupTable, ProdRow);
                    updateGroupCount(parseInt($('#groupTotalCount').text(), 10) || 0);
                }
            } else if (ActiveTabId == 'PriceLists') {
                $('#NewPriceList,#btnSyncPriceListCache').removeClass('d-none');
                var plLen = $(PLTable + ' tr.pl-list-row').length;
                if (plLen == 0) {
                    getPriceListDetails(PageNo, RowLimit, Filter);
                } else {
                    $(PLHeader).prop('checked', false).prop('indeterminate', false);
                    unSelectTableRecords(PLTable, PLRow);
                    _updatePLCount(parseInt($('#priceListTotalCount').text(), 10) || 0);
                }
            } else if (ActiveTabId == 'Categories') {
                $('#NewCategory').removeClass('d-none');
                $('#btnSyncCategoriesCache').removeClass('d-none');
                var catgLen = $(CatgTable + ' ' + CatgRow).length;
                if (catgLen == 0 || _catgListDirty) {
                    _catgListDirty = false;
                    getCategoriesDetails(PageNo, RowLimit, Filter);
                } else {
                    $(CatgHeader).prop('checked', false).prop('indeterminate', false);
                    unSelectTableRecords(CatgTable, CatgRow);
                    updateCategoryCount(parseInt($('#categoryTotalCount').text(), 10) || 0);
                }
            } else if (ActiveTabId == 'Brands') {
                $('#NewBrand').removeClass('d-none');
                $('#btnSyncBrandsCache').removeClass('d-none');
                var brandLen = $(BrandTable + ' ' + BrandRow).length;
                if (brandLen == 0 || _brandListDirty) {
                    _brandListDirty = false;
                    getBrandsDetails(PageNo, RowLimit, Filter);
                } else {
                    $(BrandHeader).prop('checked', false).prop('indeterminate', false);
                    unSelectTableRecords(BrandTable, BrandRow);
                    updateBrandCount(parseInt($('#brandTotalCount').text(), 10) || 0);
                }
            } else if (ActiveTabId == 'Sizes') {
                $('#NewSize').removeClass('d-none');
                $('#btnSyncSizesCache').removeClass('d-none');
                var sizeLen = $(SizeTable + ' ' + SizeRow).length;
                if (sizeLen == 0 || _sizeListDirty) {
                    _sizeListDirty = false;
                    getSizesDetails(PageNo, RowLimit, Filter);
                } else {
                    $(SizeHeader).prop('checked', false).prop('indeterminate', false);
                    unSelectTableRecords(SizeTable, SizeRow);
                    updateSizeCount(parseInt($('#sizeTotalCount').text(), 10) || 0);
                }
            }
        }
    });

    $('.SearchDetails').on('input', inputDelay(function(e) {
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
            _pushTabUrl(ActiveTabId, searchText);
        }
    }, 1500));

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
            _pushTabUrl(ActiveTabId, '');
        }
    });

    $('#btnDelete').click(function(e) {
        e.preventDefault();
        if (SelectedUIDs.length > 0 || (ActiveTabId === 'Item' && _prodSelectAllMode)) {
            let DeleteContent;
            if (ActiveTabId == 'Item' || ActiveTabId == 'Groups') {
                var delCount = (ActiveTabId === 'Item' && _prodSelectAllMode) ? _prodTotalRecords : SelectedUIDs.length;
                DeleteContent = 'Do you want to delete ' + delCount + ' selected product(s)?';
            } else if (ActiveTabId == 'Categories') {
                DeleteContent = 'Do you want to delete all the selected category?';
            } else if (ActiveTabId == 'Brands') {
                DeleteContent = 'Do you want to delete all the selected brand?';
            } else if (ActiveTabId == 'Sizes') {
                DeleteContent = 'Do you want to delete all the selected size?';
            } else if (ActiveTabId == 'PriceLists') {
                DeleteContent = 'Do you want to delete all the selected price list?';
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
                    if (ActiveTabId == 'Item' || ActiveTabId == 'Groups') {
                        deleteMultipleProduct();
                    } else if (ActiveTabId == 'Categories') {
                        deleteMultipleCategory();
                    } else if (ActiveTabId == 'Brands') {
                        deleteMultipleBrand();
                    } else if (ActiveTabId == 'Sizes') {
                        deleteMultipleSize();
                    } else if (ActiveTabId == 'PriceLists') {
                        deleteMultiplePriceList();
                    }
                }
            });
        }
    });

    initExport({ moduleUID: 203, getFilters: function () { return Filter; } });

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
        } else if (ActiveTabId == 'Groups') {
            groupSortState = (groupSortState + 1) % 3;
            defSortState = groupSortState;
            defFieldName = '#sortGroupName';
        } else if (ActiveTabId == 'PriceLists') {
            plNameSortState = (plNameSortState + 1) % 3;
            defSortState = plNameSortState;
            defFieldName = '#sortPLName';
        } else if (ActiveTabId == 'Categories') {
            catgSortState = (catgSortState + 1) % 3;
            defSortState = catgSortState;
            defFieldName = '#sortCatgName';
        } else if (ActiveTabId == 'Brands') {
            brandSortState = (brandSortState + 1) % 3;
            defSortState = brandSortState;
            defFieldName = '#sortBrandName';
        } else if (ActiveTabId == 'Sizes') {
            sizeSortState = (sizeSortState + 1) % 3;
            defSortState = sizeSortState;
            defFieldName = '#sortSizeName';
        }
        const icon = $(this).find('.sort-icon');
        icon.removeClass('bx-sort-alt-2 bx-sort-up bx-sort-down text-primary');
        if (defSortState == 1) {
            icon.addClass('bx-sort-up text-primary');
            $(this).attr('data-bs-title', 'Click for descending order');
            Filter['NameSorting'] = 1;
        } else if (defSortState === 2) {
            icon.addClass('bx-sort-down text-primary');
            $(this).attr('data-bs-title', 'Click to remove sorting');
            Filter['NameSorting'] = 2;
        } else {
            icon.addClass('bx-sort-alt-2');
            $(this).attr('data-bs-title', 'Click for ascending order');
            delete Filter['NameSorting'];
        }
        var _ttEl = this;
        var _tt = bootstrap.Tooltip.getInstance(_ttEl);
        if (_tt) _tt.dispose();
        new bootstrap.Tooltip(_ttEl);
        showProductPageDetails();
    });

    /** Column Asc/Desc Sorting — multi-column allowed */
    $(document).on('click', '.col-sortable', function(e) {
        e.preventDefault();
        if (ActiveTabId !== 'Item' && ActiveTabId !== 'Groups' && ActiveTabId !== 'PriceLists') return;
        const filterKey = $(this).data('filterkey');
        // Cycle this column independently (multi-sort)
        colSortStates[filterKey] = ((colSortStates[filterKey] || 0) + 1) % 3;
        const state = colSortStates[filterKey];
        const icon = $(this).find('.sort-icon');
        icon.removeClass('bx-sort-alt-2 bx-sort-up bx-sort-down text-primary');
        if (state === 1) {
            icon.addClass('bx-sort-up text-primary');
            $(this).attr('data-bs-title', 'Click for descending order');
            Filter[filterKey] = 1;
        } else if (state === 2) {
            icon.addClass('bx-sort-down text-primary');
            $(this).attr('data-bs-title', 'Click to remove sorting');
            Filter[filterKey] = 2;
        } else {
            icon.addClass('bx-sort-alt-2');
            $(this).attr('data-bs-title', 'Click for ascending order');
            delete Filter[filterKey];
        }
        var _ttEl2 = this;
        var _tt2 = bootstrap.Tooltip.getInstance(_ttEl2);
        if (_tt2) _tt2.dispose();
        new bootstrap.Tooltip(_ttEl2);
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

        // Category col — active if filter OR sort applied
        const catgActive = (Filter.Category && Filter.Category.length > 0) || (colSortStates['CategorySorting'] || 0) > 0;
        $('#ItemCategory-Div').closest('th').toggleClass('col-active', catgActive);
        $('#categoryFilter').toggleClass('text-primary', !!(Filter.Category && Filter.Category.length > 0));

        // Status col — active if filter OR sort applied
        const statusActive = (Filter.StatusFilter && Filter.StatusFilter.length > 0) || (colSortStates['StatusSorting'] || 0) > 0;
        $('#statusFilter').closest('th').toggleClass('col-active', statusActive);
        $('#statusFilter').toggleClass('text-primary', !!(Filter.StatusFilter && Filter.StatusFilter.length > 0));

        // Tax filter active
        $('#taxFilter').toggleClass('text-primary', !!(Filter.Tax && Filter.Tax.length > 0));

        // Each col-sortable (skip CategorySorting — already handled above)
        $('.col-sortable').each(function() {
            const k = $(this).data('filterkey');
            if (k === 'CategorySorting') return; // handled above
            $(this).toggleClass('col-active', (colSortStates[k] || 0) > 0);
        });
    };

    var prodTypeFilter = new TransColFilter({
        boxId       : 'productTypeFilterBox',
        triggerId   : 'productTypeFilter',
        filterKey   : 'ProductType',
        activeClass : 'has-filter',
        onApply     : function () {
            var vals = prodTypeFilter.getState()['ProductType'] || [];
            if (vals.length) Filter['ProductType'] = vals; else delete Filter['ProductType'];
            updateColumnHighlights();
            PageNo = 0;
            showProductPageDetails();
        }
    });

    var prodStatusFilter = new TransColFilter({
        boxId       : 'statusFilterBox',
        triggerId   : 'statusFilter',
        filterKey   : 'StatusFilter',
        activeClass : 'has-filter',
        onApply     : function () {
            var vals = prodStatusFilter.getState()['StatusFilter'] || [];
            if (vals.length) Filter['StatusFilter'] = vals; else delete Filter['StatusFilter'];
            updateColumnHighlights();
            PageNo = 0;
            showProductPageDetails();
        }
    });

    // Lazy-load tax items into filter box on first click (DropdownCache)
    var _taxFilterPromise = null;
    $(document).on('click', '#taxFilter', function () {
        if (_taxFilterPromise) return;
        _taxFilterPromise = DropdownCache.ready().then(function (data) {
            prodTaxFilter.setItems(
                (data.taxDetails || []).map(function (t) {
                    return {
                        value: parseInt(t.TaxDetailsUID || 0, 10),
                        label: t.Percentage ? smartDecimal(t.Percentage) + '%' : ''
                    };
                })
            );
        }).catch(function () { _taxFilterPromise = null; });
    });

    var prodTaxFilter = new TransColFilter({
        boxId       : 'taxFilterBox',
        triggerId   : 'taxFilter',
        filterKey   : 'Tax',
        activeClass : 'has-filter',
        onApply     : function () {
            var vals = prodTaxFilter.getState()['Tax'] || [];
            if (vals.length) Filter['Tax'] = vals; else delete Filter['Tax'];
            updateColumnHighlights();
            PageNo = 0;
            showProductPageDetails();
        }
    });

    // ── Category filter (TransColFilter) ────────────────────────────────────
    var categoryColFilter = new TransColFilter({
        boxId        : 'categoryFilterBox',
        triggerId    : 'categoryFilter',
        filterKey    : 'Category',
        activeClass  : 'text-primary',
        onBeforeShow : function () {
            CategoryAppend.filterBox('#categoryFilterBox', _cfbConfig, Filter.Category || []);
        }
    });

    $(document).on('click', '.prod-status-toggle', function(e) {
        e.preventDefault();
        var uid       = $(this).data('uid');
        var newStatus = $(this).data('newstatus');
        var label     = newStatus == 1 ? 'Active' : 'In-Active';
        var icon      = newStatus == 1 ? 'bx-check-circle' : 'bx-x-circle';
        var color     = newStatus == 1 ? '#198754' : '#dc3545';
        Swal.fire({
            title: 'Change Status?',
            html: 'Mark this product as <strong style="color:' + color + ';">' + label + '</strong>?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: color,
            confirmButtonText: 'Yes, mark ' + label,
            cancelButtonColor: '#6c757d',
            cancelButtonText: 'Cancel',
        }).then(function(result) {
            if (result.isConfirmed) {
                toggleProductStatus(uid, newStatus);
            }
        });
    });

    $(document).on('click', '.addItem', function(e) {
        e.preventDefault();
        ProductForm.open('add', null, { onSaveSuccess: _prodPageSaveSuccess });
    });
    
    basePaginationFunc(ProdPag, function (pg, rl, f) { _prodClearSelectAll(); getProductDetails(pg, rl, f); });
    basePaginationFunc(GroupPag, getGroupDetails);
    baseRefreshPageFunc('.PageRefresh', showProductPageDetails);
    $('.PageRefresh').off('click').on('click', function(e) {
        e.preventDefault();
        SelectedUIDs = [];
        showProductPageDetails();
    });
    
    basePageHeaderFunc(ProdHeader, ProdTable, ProdRow);
    $(ProdHeader).on('click', function () { _prodUpdateSelectAllBanner(); });
    basePageHeaderFunc(GroupHeader, GroupTable, ProdRow);

    // ── Banner interactions (Items tab) ──
    $(document).on('click', '#prodSelectAllLink', function (e) {
        e.preventDefault();
        _prodSelectAllMode = true;
        _prodUpdateSelectAllBanner();
    });
    $(document).on('click', '#prodSelectAllClear', function (e) {
        e.preventDefault();
        SelectedUIDs = [];
        unSelectTableRecords(ProdTable, ProdRow);
        $(ProdHeader).prop('checked', false).prop('indeterminate', false);
        _prodClearSelectAll();
        MultipleDeleteOption();
    });

    $(document).on('change', ProdRow, function() {
        $(this).closest('tr').toggleClass('row-sel', $(this).is(':checked'));
        var activeTbl = (ActiveTabId === 'Groups') ? GroupTable : ProdTable;
        var activeHdr = (ActiveTabId === 'Groups') ? GroupHeader : ProdHeader;
        onClickOfCheckbox($(this), activeTbl, activeHdr, ProdRow);
        if (ActiveTabId === 'Item') _prodClearSelectAll();
        MultipleDeleteOption();
    });

    $(document).on('change', CatgRow, function() {
        $(this).closest('tr').toggleClass('row-sel', $(this).is(':checked'));
        onClickOfCheckbox($(this), CatgTable, CatgHeader, CatgRow);
        MultipleDeleteOption();
    });

    $(document).on('change', BrandRow, function() {
        $(this).closest('tr').toggleClass('row-sel', $(this).is(':checked'));
        onClickOfCheckbox($(this), BrandTable, BrandHeader, BrandRow);
        MultipleDeleteOption();
    });

    $(document).on('change', SizeRow, function() {
        $(this).closest('tr').toggleClass('row-sel', $(this).is(':checked'));
        onClickOfCheckbox($(this), SizeTable, SizeHeader, SizeRow);
        MultipleDeleteOption();
    });

    $(document).on('change', PLRow, function() {
        $(this).closest('tr').toggleClass('row-sel', $(this).is(':checked'));
        onClickOfCheckbox($(this), PLTable, PLHeader, PLRow);
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

    $(document).on('click', '.CloneProduct', function(e) {
        e.preventDefault();
        var uid = $(this).data('uid');
        if (uid) { retrieveProductDetails(uid, true); }
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
        // Explicitly reset all fields for Add mode
        $('#categoryForm').trigger('reset');
        $('#CatgModalTitle').text('Add Category');
        $('.CatgSaveButton').text('Save');
        $('#CategoryUID').val(0);
        $('#CategoryName').val('');
        $('#CategoryDescription').val('');
        // Bind listeners once (no-op if already bound), then reset attachment state
        if (typeof _attachBindListeners === 'function') _attachBindListeners('Category');
        if (typeof _attachResetState    === 'function') _attachResetState('Category');
        $('#categoryModal').modal('show');
    });

    $('#categoryModal').on('shown.bs.modal', function() {
        $('#CategoryName').trigger('focus');
    });

    $('#categoryModal').on('hide.bs.modal', function() {
        $('#categoryModalDialog').removeClass('modal-md').addClass('modal-lg');
        if (!$(this).data('calledFromItemForm')) {
            formOpenCloseDefActions();
        }
    });
    
    basePaginationFunc(CatgPag, getCategoriesDetails);
    basePageHeaderFunc(CatgHeader, CatgTable, CatgRow);
    basePaginationFunc(BrandPag, getBrandsDetails);
    basePageHeaderFunc(BrandHeader, BrandTable, BrandRow);
    basePaginationFunc(SizePag, getSizesDetails);
    basePaginationFunc(PLPag, getPriceListDetails);
    basePageHeaderFunc(PLHeader, PLTable, PLRow);

    // ── Price List filter instances ───────────────────────────────────────────

    var plStatusColFilter = new TransColFilter({
        boxId      : 'plStatusFilterBox',
        triggerId  : 'plStatusFilter',
        filterKey  : 'StatusFilter',
        activeClass: 'has-filter',
        onApply    : function () {
            var vals = plStatusColFilter.getState()['StatusFilter'] || [];
            if (vals.length) Filter['StatusFilter'] = vals; else delete Filter['StatusFilter'];
            PageNo = 1;
            getPriceListDetails(PageNo, RowLimit, Filter);
        }
    });

    var plAssignedToColFilter = new TransColFilter({
        boxId      : 'plAssignedToFilterBox',
        triggerId  : 'plAssignedToFilter',
        filterKey  : 'AssignedToFilter',
        activeClass: 'has-filter',
        onApply    : function () {
            var vals = plAssignedToColFilter.getState()['AssignedToFilter'] || [];
            if (vals.length) Filter['AssignedToFilter'] = vals; else delete Filter['AssignedToFilter'];
            PageNo = 1;
            getPriceListDetails(PageNo, RowLimit, Filter);
        }
    });

    var plScopeColFilter = new TransColFilter({
        boxId      : 'plScopeFilterBox',
        triggerId  : 'plScopeFilter',
        filterKey  : 'ScopeFilter',
        activeClass: 'has-filter',
        onApply    : function () {
            var vals = plScopeColFilter.getState()['ScopeFilter'] || [];
            if (vals.length) Filter['ScopeFilter'] = vals; else delete Filter['ScopeFilter'];
            PageNo = 1;
            getPriceListDetails(PageNo, RowLimit, Filter);
        }
    });

    // ── Product filter for Categories tab ────────────────────────────────────
    var _prodCatgFilterLoaded = false;

    $(document).on('click', '#productCatgFilter', function () {
        if (_prodCatgFilterLoaded) return;
        ProductAppend.load(
            function (products) {
                _prodCatgFilterLoaded = true;
                prodCatgFilter.setItems(
                    products.map(function (p) {
                        return { value: String(p.id), label: p.text || p.itemName || '' };
                    })
                );
            },
            function () { _prodCatgFilterLoaded = false; }
        );
    });

    var prodCatgFilter = new TransColFilter({
        boxId      : 'productCatgFilterBox',
        triggerId  : 'productCatgFilter',
        filterKey  : 'ProductFilter',
        activeClass: 'has-filter',
        onApply    : function () {
            var vals = prodCatgFilter.getState()['ProductFilter'] || [];
            if (vals.length) Filter['ProductFilter'] = vals; else delete Filter['ProductFilter'];
            PageNo = 0;
            getCategoriesDetails(PageNo, RowLimit, Filter);
        }
    });

    // ── Product filter for Brands tab ────────────────────────────────────────
    var _brandProdFilterLoaded = false;

    $(document).on('click', '#brandProductFilter', function () {
        if (_brandProdFilterLoaded) return;
        ProductAppend.load(
            function (products) {
                _brandProdFilterLoaded = true;
                brandProductFilter.setItems(
                    products.map(function (p) {
                        return { value: String(p.id), label: p.text || p.itemName || '' };
                    })
                );
            },
            function () { _brandProdFilterLoaded = false; }
        );
    });

    var brandProductFilter = new TransColFilter({
        boxId      : 'brandProductFilterBox',
        triggerId  : 'brandProductFilter',
        filterKey  : 'ProductFilter',
        activeClass: 'has-filter',
        onApply    : function () {
            var vals = brandProductFilter.getState()['ProductFilter'] || [];
            if (vals.length) Filter['ProductFilter'] = vals; else delete Filter['ProductFilter'];
            PageNo = 0;
            getBrandsDetails(PageNo, RowLimit, Filter);
        }
    });

    // ── Last Updated filter — visible on all tabs ────────────────────────────
    var lastUpdatedFilter = document.getElementById('lastUpdatedFilterBox')
        ? new TransColFilter({
            boxId      : 'lastUpdatedFilterBox',
            triggerId  : 'lastUpdatedFilter',
            filterKey  : 'LastUpdatedFilter',
            activeClass: 'has-filter',
            onApply    : function () {
                var vals = lastUpdatedFilter.getState()['LastUpdatedFilter'] || [];
                if (vals.length) Filter['LastUpdatedFilter'] = vals; else delete Filter['LastUpdatedFilter'];
                PageNo = 0;
                showProductPageDetails();
            }
        })
        : null;


    $(document).on('click', '.editCategory', function(e) {
        e.preventDefault();
        var getVal   = $(this).data('uid');
        if (!getVal) return;

        var getName       = $(this).data('name');
        var getDesc       = $(this).data('description');
        var getAttachRaw  = $(this).data('attachments');   // already in DOM — no AJAX needed

        $('#categoryForm').trigger('reset');
        $('#CatgModalTitle').text('Edit Category');
        $('#CatgSaveButton').text('Update');

        $('#CategoryUID').val(getVal);
        $('#CategoryName').val(getName ? atob(getName) : '');
        $('#CategoryDescription').val(getDesc ? atob(getDesc) : '');

        // Bind listeners once, reset state, then load existing from DOM data — no AJAX
        if (typeof _attachBindListeners === 'function') _attachBindListeners('Category');
        if (typeof _attachResetState    === 'function') _attachResetState('Category');

        try {
            var attachments = typeof getAttachRaw === 'string' ? JSON.parse(getAttachRaw) : (getAttachRaw || []);
            if (attachments.length && _attachState['Category']) {
                // Map to the shape _attachRender expects: AttachUID, FileName, FileSize, Url
                _attachState['Category'].existing = attachments.map(function(a, i) {
                    return {
                        AttachUID : a.AttachUID || (1000 + i),
                        FileName  : a.name  || a.FileName  || '',
                        FilePath  : a.url   || a.FilePath  || '',
                        FileSize  : a.FileSize || 0,
                        Url       : a.url   || a.Url       || '',
                    };
                });
                if (typeof _attachRender === 'function') _attachRender('Category');
            }
        } catch(err) {}

        $('#categoryModal').modal('show');
    });

    $('#categoryForm').submit(function(e) {
        e.preventDefault();

        var formData    = new FormData($('#categoryForm')[0]);
        var CategoryUID = parseInt($('#CategoryUID').val() || 0);

        formData.append('PageNo',    PageNo);
        formData.append('RowLimit',  RowLimit);
        formData.append('ModuleId',  CategoryModuleId);
        if (Object.keys(Filter).length > 0) formData.append('Filter', JSON.stringify(Filter));

        // Append new attachment files directly into this request — no separate upload round trip
        if (typeof _attachState !== 'undefined' && _attachState['Category']) {
            (_attachState['Category'].newFiles || []).forEach(function(f) {
                formData.append('CatgAttachFiles[]', f, f.name);
            });
        }

        var $btn = $('#CatgSaveButton');
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Saving...');

        var onDone = function() {
            $btn.prop('disabled', false).text(CategoryUID ? 'Update' : 'Save');
            $('#categoryModal').modal('hide');
        };

        if (CategoryUID === 0) {
            addCategoryDetails(formData, onDone);
        } else {
            editCategoryDetails(formData, onDone);
        }

    });

    $(document).on('click', '.DeleteCategory', function(e) {
        e.preventDefault();
        var GetId        = $(this).data('categoryuid');
        var productCount = parseInt($(this).data('productcount') || 0, 10);
        var catName      = $(this).data('categoryname') || 'this category';

        if (!GetId) return;

        // Block immediately — no server call needed, count is already in the DOM
        if (productCount > 0) {
            showToastNotification(
                '"' + catName + '" has ' + productCount + ' linked product' + (productCount > 1 ? 's' : '') + '. Remove the product link first before deleting.',
                'error'
            );
            return false;
        }

        Swal.fire({
            title: "Delete category?",
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
    });

    // ── Brands Page ─────────────────────────────────────────────────────────────
    var _brandIsDirty      = false;
    var _brandIsCreateMode = false;

    $(document).on('click', '.addBrand', function(e) {
        e.preventDefault();
        $('#brandForm').trigger('reset');
        $('#BrandModalTitle').text('Add Brand');
        $('#BrandSaveButton').text('Save');
        $('#BrandUID').val(0);
        $('#BrandName').val('');
        $('#BrandCode').val('');
        $('#BrandDescription').val('');
        if (typeof _attachBindListeners === 'function') _attachBindListeners('Brand');
        if (typeof _attachResetState    === 'function') _attachResetState('Brand');
        $('#brandModal').modal('show');
        _brandIsCreateMode = true;
        _brandIsDirty      = false;
    });

    $('#brandModal').on('shown.bs.modal', function() {
        $('#BrandName').trigger('focus');
    });

    $('#brandModal').on('hide.bs.modal', function(e) {
        if (_brandIsDirty && _brandIsCreateMode) {
            e.preventDefault();
            Swal.fire({
                title             : t('swal_unsaved_title',   'Unsaved Changes'),
                text              : t('swal_unsaved_msg',     'Your changes will be lost if you close now.'),
                icon              : 'warning',
                showCancelButton  : true,
                confirmButtonText : t('swal_unsaved_confirm', 'Close Anyway'),
                cancelButtonText  : t('swal_unsaved_cancel',  'Stay'),
                confirmButtonColor: '#d33',
                cancelButtonColor : '#3085d6',
            }).then(function (result) {
                if (result.isConfirmed) {
                    _brandIsDirty      = false;
                    _brandIsCreateMode = false;
                    $('#brandModal').modal('hide');
                }
            });
            return;
        }
        _brandIsCreateMode = false;
        _brandIsDirty      = false;
        formOpenCloseDefActions();
    });

    $(document).on('input change', '#brandForm input, #brandForm textarea, #brandForm select', function () {
        if (_brandIsCreateMode) _brandIsDirty = true;
    });


    $(document).on('click', '.editBrand', function(e) {
        e.preventDefault();
        var getVal = $(this).data('uid');
        if (!getVal) return;

        var getName      = $(this).data('name');
        var getCode      = $(this).data('code');
        var getDesc      = $(this).data('description');
        var getAttachRaw = $(this).data('attachments');

        $('#brandForm').trigger('reset');
        $('#BrandModalTitle').text('Edit Brand');
        $('#BrandSaveButton').text('Update');

        $('#BrandUID').val(getVal);
        $('#BrandName').val(getName ? atob(getName) : '');
        $('#BrandCode').val(getCode ? atob(getCode) : '');
        $('#BrandDescription').val(getDesc ? atob(getDesc) : '');

        if (typeof _attachBindListeners === 'function') _attachBindListeners('Brand');
        if (typeof _attachResetState    === 'function') _attachResetState('Brand');

        try {
            var attachments = typeof getAttachRaw === 'string' ? JSON.parse(getAttachRaw) : (getAttachRaw || []);
            if (attachments.length && _attachState['Brand']) {
                _attachState['Brand'].existing = attachments.map(function(a, i) {
                    return {
                        AttachUID : a.AttachUID || (1000 + i),
                        FileName  : a.name  || a.FileName  || '',
                        FilePath  : a.url   || a.FilePath  || '',
                        FileSize  : a.FileSize || 0,
                        Url       : a.url   || a.Url       || '',
                    };
                });
                if (typeof _attachRender === 'function') _attachRender('Brand');
            }
        } catch(err) {}

        $('#brandModal').modal('show');
    });

    $('#brandForm').submit(function(e) {
        e.preventDefault();

        var formData = new FormData($('#brandForm')[0]);
        var BrandUID = parseInt($('#BrandUID').val() || 0);

        formData.append('PageNo',   PageNo);
        formData.append('RowLimit', RowLimit);
        if (Object.keys(Filter).length > 0) formData.append('Filter', JSON.stringify(Filter));

        if (typeof _attachState !== 'undefined' && _attachState['Brand']) {
            (_attachState['Brand'].newFiles || []).forEach(function(f) {
                formData.append('BrandAttachFiles[]', f, f.name);
            });
        }

        var $btn = $('#BrandSaveButton');
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Saving...');

        var onDone = function() {
            $btn.prop('disabled', false).text(BrandUID ? 'Update' : 'Save');
            _brandIsDirty      = false;
            _brandIsCreateMode = false;
            $('#brandModal').modal('hide');
        };

        if (BrandUID === 0) {
            addBrandDetails(formData, onDone);
        } else {
            editBrandDetails(formData, onDone);
        }
    });

    $(document).on('click', '.DeleteBrand', function(e) {
        e.preventDefault();
        var GetId        = $(this).data('branduid');
        var productCount = parseInt($(this).data('productcount') || 0, 10);
        var brandName    = $(this).data('brandname') || 'this brand';

        if (!GetId) return;

        if (productCount > 0) {
            showToastNotification(
                '"' + brandName + '" has ' + productCount + ' linked product' + (productCount > 1 ? 's' : '') + '. Remove the product link first before deleting.',
                'error'
            );
            return false;
        }

        Swal.fire({
            title: "Delete brand?",
            text: "You won't be able to revert this!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#d33",
            confirmButtonText: "Yes, delete it!",
            cancelButtonColor: "#3085d6",
        }).then((result) => {
            if (result.isConfirmed) {
                deleteBrand(GetId);
            }
        });
    });

    $('#btnSyncBrandsCache').click(function(e) {
        e.preventDefault();
        $.ajax({
            url: '/products/syncBrandsCache', method: 'POST', cache: false,
            data: { [CsrfName]: CsrfToken },
            success: function(r) {
                showToastNotification(r.Message || 'Brands cache synced.', r.Error ? 'error' : 'success');
            }
        });
    });

    // ── Sizes Page ─────────────────────────────────────────────────────────────
    var _sizeIsDirty      = false;
    var _sizeIsCreateMode = false;

    $(document).on('click', '.addSize', function(e) {
        e.preventDefault();
        $('#sizeForm').trigger('reset');
        $('#SizeModalTitle').text('Add Size');
        $('#SizeSaveButton').text('Save');
        $('#SizeUID').val(0);
        $('#SizeDimensionUOM').val('');
        $('#SizeWeightUOM').val('');
        $('#sizeModal').modal('show');
        _sizeIsCreateMode = true;
        _sizeIsDirty      = false;
    });

    $('#sizeModal').on('shown.bs.modal', function() {
        $('#SizeName').trigger('focus');
    });

    $('#sizeModal').on('hide.bs.modal', function(e) {
        if (_sizeIsDirty && _sizeIsCreateMode) {
            e.preventDefault();
            Swal.fire({
                title             : t('swal_unsaved_title',   'Unsaved Changes'),
                text              : t('swal_unsaved_msg',     'Your changes will be lost if you close now.'),
                icon              : 'warning',
                showCancelButton  : true,
                confirmButtonText : t('swal_unsaved_confirm', 'Close Anyway'),
                cancelButtonText  : t('swal_unsaved_cancel',  'Stay'),
                confirmButtonColor: '#d33',
                cancelButtonColor : '#3085d6',
            }).then(function (result) {
                if (result.isConfirmed) {
                    _sizeIsDirty      = false;
                    _sizeIsCreateMode = false;
                    $('#sizeModal').modal('hide');
                }
            });
            return;
        }
        _sizeIsCreateMode = false;
        _sizeIsDirty      = false;
        formOpenCloseDefActions();
    });

    $(document).on('input change', '#sizeForm input', function () {
        if (_sizeIsCreateMode) _sizeIsDirty = true;
    });

    $(document).on('click', '.editSize', function(e) {
        e.preventDefault();
        var $el = $(this);
        var getVal = $el.data('uid');
        if (!getVal) return;

        $('#sizeForm').trigger('reset');
        $('#SizeModalTitle').text('Edit Size');
        $('#SizeSaveButton').text('Update');

        $('#SizeUID').val(getVal);
        $('#SizeName').val($el.data('name')        ? atob($el.data('name'))        : '');
        $('#SizeCode').val($el.data('code')        ? atob($el.data('code'))        : '');
        $('#SizeDescription').val($el.data('description') ? atob($el.data('description')) : '');
        $('#SizeLength').val($el.data('length')    || '');
        $('#SizeWidth').val($el.data('width')      || '');
        $('#SizeHeight').val($el.data('height')    || '');
        $('#SizeDepth').val($el.data('depth')      || '');
        $('#SizeDiameter').val($el.data('diameter') || '');
        $('#SizeThickness').val($el.data('thickness') || '');
        $('#SizeWeight').val($el.data('weight')    || '');
        $('#SizeDimensionUOM').val($el.data('dimensionuom') || '');
        $('#SizeWeightUOM').val($el.data('weightuom') || '');

        $('#sizeModal').modal('show');
    });

    $('#sizeForm').submit(function(e) {
        e.preventDefault();

        var SizeUID  = parseInt($('#SizeUID').val() || 0);
        var formData = {};
        $.each($(this).serializeArray(), function(_, f) { formData[f.name] = f.value; });
        formData.SizeUID   = SizeUID;
        formData.PageNo    = PageNo;
        formData.RowLimit  = RowLimit;
        formData[CsrfName] = CsrfToken;
        if (Object.keys(Filter).length > 0) formData.Filter = JSON.stringify(Filter);

        var $btn = $('#SizeSaveButton');
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Saving...');

        var onDone = function(insertId) {
            $btn.prop('disabled', false).text(SizeUID ? 'Update' : 'Save');
            _sizeIsDirty      = false;
            _sizeIsCreateMode = false;
            if (SizeUID === 0 && insertId) {
                $(document).trigger('r2k:sizeAdded', { SizeUID: insertId, SizeName: formData.SizeName });
            }
            $('#sizeModal').modal('hide');
        };

        if (SizeUID === 0) {
            addSizeListDetails(formData, onDone);
        } else {
            editSizeListDetails(formData, onDone);
        }
    });

    $(document).on('click', '.DeleteSize', function(e) {
        e.preventDefault();
        var GetId        = $(this).data('sizeuid');
        var productCount = parseInt($(this).data('productcount') || 0, 10);
        var sizeName     = $(this).data('sizename') || 'this size';

        if (!GetId) return;

        if (productCount > 0) {
            showToastNotification(
                '"' + sizeName + '" has ' + productCount + ' linked product' + (productCount > 1 ? 's' : '') + '. Remove the product link first before deleting.',
                'error'
            );
            return false;
        }

        Swal.fire({
            title: 'Delete size?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonColor: '#3085d6',
        }).then((result) => {
            if (result.isConfirmed) {
                deleteSizeItem(GetId);
            }
        });
    });

    $('#btnSyncSizesCache').click(function(e) {
        e.preventDefault();
        var $icon = $(this).find('i');
        $icon.removeClass('bx-planet').addClass('bx-loader-alt bx-spin');
        $.ajax({
            url: '/products/syncSizesCache', method: 'POST', cache: false,
            data: { [CsrfName]: CsrfToken },
            success: function(r) {
                showToastNotification(r.Message || 'Sizes cache synced.', r.Error ? 'error' : 'success');
            },
            error: function() {
                showToastNotification('Failed to sync sizes cache.', 'error');
            },
            complete: function() {
                $icon.removeClass('bx-loader-alt bx-spin').addClass('bx-planet');
            }
        });
    });

    if (window.location.search.indexOf('action=create') !== -1) {
        $('#NewItem').trigger('click');
    }

    /**
     * Called by the Quick Create handler in default.js when the user is already
     * on this page. Returns true if the create modal was opened, false if the
     * caller should navigate instead (e.g. we're on Categories/Brands tab).
     * @returns {boolean}
     */
    window._qcPageCreate = function () {
        if ($('#NewItem').hasClass('d-none')) return false;
        $('#NewItem').trigger('click');
        return true;
    };

});

// ── Price List Modal ────────────────────────────────────────────────────────

var _plFpFrom, _plFpTo;
var _plRuleSeq        = 0;
var _plCustomerTypes  = null; // null = not yet fetched; Array = cached (may be empty)
var _plGroupsCache    = null; // null = not yet fetched; Array = cached
var _plProductCache   = null; // null = not yet loaded; Array = full product list from Upstash/AJAX
var _plTierHeadHtml   = '';   // cached thead HTML for tier sub-tables inside product blocks
var _plCurSym              = '<?php echo htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹'); ?>';
var _plBelowPurchaseAction = '<?php echo addslashes($JwtData->TransSettings->BelowPurchasePriceAction ?? 'warn'); ?>';

/**
 * Load all products via ProductAppend (Upstash → AJAX fallback), cached after first load.
 * @param {Function} callback  receives Array of product objects
 * @returns {void}
 */
function _loadPlProducts(callback) {
    if (_plProductCache) { callback(_plProductCache); return; }
    ProductAppend.load(
        function (products) { _plProductCache = products; callback(products); },
        function ()         { callback([]); }
    );
}

/**
 * Fetch variant list for a product (used in price list rule blocks).
 * @param {number}   productUID
 * @param {Function} cb  Called with array of {VariantUID:number, Label:string}
 * @returns {void}
 */
function _fetchPLVariants(productUID, cb) {
    if (_plProductCache) {
        for (var _vi = 0; _vi < _plProductCache.length; _vi++) {
            if (_plProductCache[_vi].id === productUID) {
                cb(_plProductCache[_vi].variants || []);
                return;
            }
        }
    }
    $.ajax({
        url   : '/products/getProductVariantsForPricelist',
        method: 'POST',
        data  : { ProductUID: productUID },
        success: function (r) { cb(r.Error ? [] : (r.Variants || [])); },
        error  : function ()  { cb([]); }
    });
}

/**
 * Update the Price Lists tab count badge.
 * @param {number} n
 * @returns {void}
 */
function _updatePLCount(n) {
    var $badge = $('#priceListTotalCount');
    if (!$badge.length) return;
    if (n > 0) $badge.text(n).removeClass('d-none');
    else       $badge.text('').addClass('d-none');
}

/**
 * Apply a price list AJAX response — refreshes table, pagination, and count badge.
 * @param {Object} response
 * @returns {void}
 */
function _applyPLResponse(response) {
    $(PLPag).html(response.Pagination);
    $(PLTable + ' tbody').html(response.List);
    if (typeof response.TotalCount !== 'undefined') _updatePLCount(response.TotalCount);
    headerCheckboxTrueFalse(PLTable, PLHeader, PLRow);
    MultipleDeleteOption();
    $(window).trigger('scroll');
}

// ── Date pickers ─────────────────────────────────────────────────────────────

/**
 * @returns {void}
 */
function _initPLDatePickers() {
    if (typeof flatpickr === 'undefined') return;
    var _fmt = (typeof _transFormDateFormat !== 'undefined' && _transFormDateFormat) ? _transFormDateFormat : 'd-m-Y';
    if (_plFpFrom) _plFpFrom.destroy();
    if (_plFpTo)   _plFpTo.destroy();
    _plFpFrom = flatpickr('#PLValidFrom', {
        dateFormat: 'Y-m-d', altInput: true, altFormat: _fmt,
        static: true, position: 'below left',
        onChange: function (s) { $('#PLValidFromRaw').val(s.length ? s[0].toISOString().slice(0, 10) : ''); }
    });
    _plFpTo = flatpickr('#PLValidTo', {
        dateFormat: 'Y-m-d', altInput: true, altFormat: _fmt,
        static: true, position: 'below left',
        onChange: function (s) { $('#PLValidToRaw').val(s.length ? s[0].toISOString().slice(0, 10) : ''); }
    });
}

// ── Customer Groups loader ────────────────────────────────────────────────────

/**
 * Load customer groups from /customers/getGroupsForDropdown and populate
 * the #PLCustomerGroups Select2. Cached for the lifetime of the page.
 * @param {function} [callback]  called once options are ready (or immediately if already cached)
 * @returns {void}
 */
function _loadPLGroups(callback) {
    if (_plGroupsCache !== null) {
        if (typeof callback === 'function') callback();
        return;
    }

    var $sel = $('#PLCustomerGroups');
    $sel.prop('disabled', true).next('.select2-container').css('opacity', .5);

    function _applyGroups(groups) {
        _plGroupsCache = groups;
        $sel.empty();
        $.each(groups, function (_, g) {
            $sel.append($('<option>').val(g.GroupUID).text(g.GroupName));
        });
        $sel.prop('disabled', false).next('.select2-container').css('opacity', '');
        $sel.trigger('change.select2');
        if (typeof callback === 'function') callback();
    }

    function _fetchFromServer() {
        $.ajax({
            url: '/customers/getGroupsForDropdown', method: 'GET', cache: false,
            success: function (r) {
                _applyGroups((!r.Error && r.Groups && r.Groups.length) ? r.Groups : []);
            },
            error: function () {
                _plGroupsCache = [];
                $sel.prop('disabled', false).next('.select2-container').css('opacity', '');
                showToastNotification('Failed to load customer groups.', 'error');
                if (typeof callback === 'function') callback();
            }
        });
    }

    if (typeof UpstashService !== 'undefined' && UpstashService.isEnabled()) {
        UpstashService.hgetall(UpstashService.orgKey('customer-groups')).then(function (map) {
            if (map && Object.keys(map).length > 0) {
                var groups = Object.values(map)
                    .map(function (v) { return typeof v === 'string' ? JSON.parse(v) : v; })
                    .sort(function (a, b) { return (a.GroupName || '').localeCompare(b.GroupName || ''); });
                _applyGroups(groups);
            } else {
                _fetchFromServer();
            }
        }).catch(function () { _fetchFromServer(); });
    } else {
        _fetchFromServer();
    }
}

// ── Customer loader for #PLCustomers Select2 ─────────────────────────────────

/**
 * Load customers into #PLCustomers Select2.
 * Tries Upstash orgKey('customers') first; falls back to AJAX.
 * Page-level cache: _plCustomersCache persists across modal opens.
 * @param {function} [callback]  called once options are ready (or immediately if already cached)
 * @returns {void}
 */
var _plCustomersCache = null;

function _loadPLCustomers(callback) {
    if (_plCustomersCache !== null) {
        if (typeof callback === 'function') callback();
        return;
    }

    var $sel = $('#PLCustomers');
    $sel.prop('disabled', true).next('.select2-container').css('opacity', .5);

    /**
     * @param {Array<{id: number, text: string}>} items
     * @returns {void}
     */
    function _populate(items) {
        _plCustomersCache = items;
        $sel.empty();
        $.each(items, function (_, c) {
            $sel.append($('<option>').val(c.id).text(c.text));
        });
        $sel.prop('disabled', false).next('.select2-container').css('opacity', '');
        $sel.trigger('change.select2');
        if (typeof callback === 'function') callback();
    }

    /**
     * @returns {void}
     */
    function _fallback() {
        $.ajax({
            url: '/customers/getCustomerSearchList', method: 'GET', cache: false,
            data: { limit: 500, page: 1, search: '' },
            success: function (r) {
                var rows = (!r.Error && r.Data && r.Data.length) ? r.Data : [];
                _populate(rows.map(function (c) {
                    return { id: c.CustomerUID, text: c.Name || c.CustomerName || '' };
                }));
            },
            error: function () {
                _plCustomersCache = [];
                $sel.prop('disabled', false).next('.select2-container').css('opacity', '');
                showToastNotification('Failed to load customers.', 'error');
                if (typeof callback === 'function') callback();
            }
        });
    }

    if (typeof UpstashService === 'undefined' || !UpstashService.isEnabled()) {
        _fallback(); return;
    }

    UpstashService.hgetall(UpstashService.orgKey('customers'))
        .then(function (map) {
            if (!map || typeof map !== 'object' || !Object.keys(map).length) {
                _fallback(); return;
            }
            var items = [];
            Object.keys(map).forEach(function (uid) {
                var c = map[uid];
                var name = c.Name || c.CustomerName || '';
                if (name) items.push({ id: parseInt(uid, 10), text: name });
            });
            items.sort(function (a, b) { return a.text.localeCompare(b.text); });
            _populate(items);
        })
        .catch(_fallback);
}

// ── Customer Types loader ─────────────────────────────────────────────────────

/**
 * Load customer types from Upstash (r2k-customer-types) with AJAX fallback.
 * Calls callback immediately when already cached.
 * @param {function(Array): void} callback
 * @returns {void}
 */
function _loadCustTypes(callback) {
    if (_plCustomerTypes !== null) { callback(_plCustomerTypes); return; }

    /**
     * @param {Array} types
     * @returns {void}
     */
    function _done(types) {
        _plCustomerTypes = types;
        callback(types);
    }

    /**
     * @returns {void}
     */
    function _fallback() {
        $.ajax({ url: '/customers/getCustomerTypes', method: 'GET', cache: false,
            success: function (r) { _done(!r.Error && r.Data ? r.Data : []); },
            error:   function ()  { _done([]); }
        });
    }

    UpstashService.get(UpstashService.globalKey('customer-types'))
        .then(function (d) { (d && Array.isArray(d) && d.length) ? _done(d) : _fallback(); })
        .catch(_fallback);
}

// ── Build Global Discount table (All Products scope) ─────────────────────────

/**
 * Renders one row per customer type (or one row for Specific Customers).
 * @param {Array}   types
 * @param {boolean} isSpecific  true = "Specific Customers" selected
 * @returns {void}
 */
function _buildGlobalTable(types, isSpecific) {
    var curSym   = $('<span>').text(_plCurSym).html();
    var typeOpts = '<option value="Percentage">% Discount</option>'
        + '<option value="Fixed">Fixed Discount (' + curSym + ')</option>'
        + '<option value="NoDiscount">No Discount</option>';
    var html = '';

    if (isSpecific || !types || !types.length) {
        html = '<tr data-ctuid="0">'
            + '<td class="text-muted" style="font-size:.82rem;">All Selected Customers</td>'
            + '<td><select class="form-select form-select-sm pl-global-type">' + typeOpts + '</select></td>'
            + '<td><div class="input-group input-group-sm">'
            +   '<span class="input-group-text pl-global-sym">%</span>'
            +   '<input type="number" class="form-control pl-global-val" min="0" step="' + _plDecStep() + '" placeholder="0" value="0">'
            + '</div></td></tr>';
    } else {
        $.each(types, function (_, t) {
            var uid = parseInt(t.CustomerTypeUID);
            var lbl = $('<span>').text(t.TypeName).html();
            html += '<tr data-ctuid="' + uid + '">'
                + '<td class="fw-semibold">' + lbl + '</td>'
                + '<td><select class="form-select form-select-sm pl-global-type">' + typeOpts + '</select></td>'
                + '<td><div class="input-group input-group-sm">'
                +   '<span class="input-group-text pl-global-sym">%</span>'
                +   '<input type="number" class="form-control pl-global-val" min="0" step="' + _plDecStep() + '" placeholder="0" value="0">'
                + '</div></td></tr>';
        });
    }

    $('#PLGlobalBody').html(html);
    $('#PLGlobalLoading').addClass('d-none');
    $('#PLGlobalTableWrap').removeClass('d-none');
}

// ── Build Price Rules table header (Specific Products scope) ─────────────────

/**
 * Generates CustomerType columns dynamically in the price rules thead.
 * @param {Array}   types
 * @param {boolean} isSpecific  true = "Specific Customers" selected
 * @returns {void}
 */
/**
 * Build the thead HTML for a tier sub-table (MinQty | MaxQty | CT prices | Remove).
 * @param {Array}   types
 * @param {boolean} isSpecific
 * @returns {string}
 */
function _buildTierHead(types, isSpecific) {
    var curSym = $('<span>').text(_plCurSym).html();
    var th = '<tr>'
        + '<th style="width:80px;">Min Qty</th>'
        + '<th style="width:80px;">Max Qty</th>';
    if (isSpecific || !types || !types.length) {
        th += '<th style="min-width:100px;">Price (' + curSym + ')</th>';
    } else {
        $.each(types, function (_, t) {
            th += '<th style="min-width:100px;">'
                + $('<span>').text(t.TypeName).html()
                + '<br><small style="font-weight:400;font-size:.7rem;">price (' + curSym + ')</small>'
                + '</th>';
        });
    }
    th += '<th style="width:32px;"></th></tr>';
    return th;
}

/**
 * Cache the tier thead HTML and push it into all existing product block tables.
 * Variant-mode blocks get the variant thead; tier-mode blocks get the tier thead.
 * @param {Array}   types
 * @param {boolean} isSpecific
 * @returns {void}
 */
function _buildRulesHeader(types, isSpecific) {
    _plTierHeadHtml = _buildTierHead(types, isSpecific);
    $('.pl-prod-block[data-mode="tier"] .pl-tiers-thead').html(_plTierHeadHtml);
    $('.pl-prod-block[data-mode="variant"] .pl-var-section .pl-tiers-thead').html(_plTierHeadHtml);
    $('#PLRulesLoading').addClass('d-none');
}

// ── Scope toggle ─────────────────────────────────────────────────────────────

$(document).on('change', 'input[name="PLScope"]', function () {
    var isSpecific = $(this).val() === 'Specific';
    $('#PLGlobalSection').toggleClass('d-none', isSpecific);
    $('#PLRulesSection').toggleClass('d-none', !isSpecific);
    if (isSpecific && _plCustomerTypes === null) {
        $('#PLRulesLoading').removeClass('d-none');
        $('#PLRulesTableWrap').addClass('d-none');
    }
});

// ── Assigned To toggle ────────────────────────────────────────────────────────

$(document).on('change', 'input[name="PLAssignedTo"]', function () {
    var val        = $(this).val();
    var isSpecific = val === 'Customers';
    $('#PLGroupsRow').toggleClass('d-none', val !== 'Groups');
    $('#PLSpecificCustRow').toggleClass('d-none', val !== 'Customers');
    if (val === 'Groups')     _loadPLGroups();
    if (val === 'Customers')  _loadPLCustomers();
    if (_plCustomerTypes !== null) {
        _buildGlobalTable(_plCustomerTypes, isSpecific);
        _buildRulesHeader(_plCustomerTypes, isSpecific);
        $('#PLProductBlocksWrap .pl-prod-block').remove();
        _syncRulesEmpty();
    }
});

// ── Global discount symbol update ─────────────────────────────────────────────

$(document).on('change', '.pl-global-type', function () {
    var val = $(this).val();
    var sym = val === 'Percentage' ? '%' : (val === 'NoDiscount' ? '—' : _plCurSym);
    var $tr = $(this).closest('tr');
    $tr.find('.pl-global-sym').text(sym);
    $tr.find('.pl-global-val').prop('disabled', val === 'NoDiscount');
    if (val === 'NoDiscount') $tr.find('.pl-global-val').val('0');
});
$(document).on('keydown', '.pl-global-val, .pl-ct-price', function (e) {
    if (!/^\d$/.test(e.key)) return;
    var dec = (typeof JwtData !== 'undefined' && JwtData.GenSettings)
        ? 2;
    var raw = this.value;
    var dot = raw.indexOf('.');
    if (dot === -1) return;
    if ((raw.length - dot - 1) >= dec) e.preventDefault();
});
$(document).on('blur', '.pl-global-val', function () {
    var v = parseFloat(this.value);
    if (isNaN(v) || v < 0) v = 0;
    this.value = _plSmartDec(v);
});
$(document).on('blur', '.pl-ct-price', function () {
    var self = this;
    var v    = parseFloat(this.value);
    if (isNaN(v) || v < 0) { this.value = ''; return; }
    this.value  = _plSmartDec(v);
    var entered = parseFloat(this.value);
    if (!entered || !_plProductCache || (_plBelowPurchaseAction !== 'strict' && _plBelowPurchaseAction !== 'warn')) return;
    var $block  = $(this).closest('.pl-prod-block');
    var prodUID = parseInt($block.find('.pl-prod-uid').val() || 0, 10);
    if (!prodUID) return;
    var prodObj = null;
    for (var _bi = 0; _bi < _plProductCache.length; _bi++) {
        if (parseInt(_plProductCache[_bi].id, 10) === prodUID) { prodObj = _plProductCache[_bi]; break; }
    }
    if (!prodObj || !(prodObj.purchasePrice > 0)) return;
    var dec   = (typeof JwtData !== 'undefined' && JwtData.GenSettings) ? 2;
    var effPP = (prodObj.purchasePriceTaxUID === 1)
        ? prodObj.purchasePrice
        : prodObj.purchasePrice * (1 + (prodObj.taxPercent || 0) / 100);
    if (entered < effPP) {
        var msg = 'Price (' + _plCurSym + entered.toFixed(dec) + ') is below purchase cost (' + _plCurSym + effPP.toFixed(dec) + ').';
        if (_plBelowPurchaseAction === 'strict') {
            self.value = '';
            showToastNotification(msg + ' Value cleared.', 'error');
        } else {
            showToastNotification(msg, 'warning');
        }
    }
});

// ── Price Rules table ─────────────────────────────────────────────────────────

/**
 * @returns {void}
 */
function _syncRulesEmpty() {
    $('#PLRulesEmpty').toggleClass('d-none', $('#PLProductBlocksWrap .pl-prod-block').length > 0);
}

$(document).on('focusin', '.pl-prod-block .pl-max-qty, .pl-prod-block .pl-ct-price', function () {
    var $block = $(this).closest('.pl-prod-block');
    if (!parseInt($block.find('.pl-prod-uid').val() || 0, 10)) {
        var self = this;
        setTimeout(function () { $(self).blur(); }, 0);
        showToastNotification('Select a product before entering prices.', 'warning');
    }
});

/**
 * Build one price-input cell (shared by tier rows and variant-tier rows).
 * @param {number} ctUID
 * @param {string} pv        saved price string (empty = blank)
 * @param {string} curSym    HTML-escaped currency symbol
 * @param {number} dec       decimal places
 * @param {number} maxLen    max price length
 * @returns {string}
 */
function _plBuildPriceCell(ctUID, pv, curSym, dec, maxLen) {
    return '<td><div class="input-group input-group-sm">'
        + '<span class="input-group-text">' + curSym + '</span>'
        + '<input type="text" class="form-control form-control-sm pl-ct-price" data-ctuid="' + ctUID + '"'
        + ' value="' + (pv || '') + '" placeholder="0.00"'
        + ' onkeydown="return handleDotOnly(event)"'
        + ' oninput="validatePriceInput(this,' + maxLen + ',' + dec + ')"'
        + ' onpaste="handlePricePaste(event,' + maxLen + ',' + dec + ')"'
        + ' ondrop="handlePriceDrop(event,' + maxLen + ',' + dec + ')">'
        + '</div></td>';
}

/**
 * Add one price-tier row to a product block's tier tbody.
 * @param {jQuery} $tbody
 * @param {Object} [tierData]  {MinQty, MaxQty, Prices:{ctUID:value}}
 * @returns {void}
 */
function _plAddTierRow($tbody, tierData) {
    var types      = _plCustomerTypes || [];
    var isSpecific = $('input[name="PLAssignedTo"]:checked').val() === 'Customers';
    var t          = tierData || {};
    var maxVal     = (t.MaxQty !== null && t.MaxQty !== undefined && t.MaxQty !== 0) ? t.MaxQty : '';
    var curSym     = $('<span>').text(_plCurSym).html();
    var dec        = (typeof JwtData !== 'undefined' && JwtData.GenSettings) ? 2;
    var maxLen     = (typeof JwtData !== 'undefined' && JwtData.GenSettings) ? (parseInt(JwtData.GenSettings.PriceMaxLength, 10) || 15) : 15;

    var priceCells = '';
    if (isSpecific || !types.length) {
        var pv0 = (t.Prices && t.Prices[0] !== undefined) ? t.Prices[0] : '';
        priceCells = _plBuildPriceCell(0, pv0, curSym, dec, maxLen);
    } else {
        $.each(types, function (_, ct) {
            var uid = parseInt(ct.CustomerTypeUID);
            var pv  = (t.Prices && t.Prices[uid] !== undefined) ? t.Prices[uid] : '';
            priceCells += _plBuildPriceCell(uid, pv, curSym, dec, maxLen);
        });
    }

    $tbody.append(
        '<tr class="pl-tier-row">'
        + '<td><input type="number" class="form-control form-control-sm pl-min-qty bg-light" value="' + (t.MinQty || 1) + '" min="1" step="1" readonly tabindex="-1"></td>'
        + '<td><input type="number" class="form-control form-control-sm pl-max-qty" value="' + maxVal + '" min="1" step="1" placeholder="—"'
        +     ' onblur="var v=parseInt(this.value,10);if(isNaN(v)||v<=0)this.value=\'\';">'
        + '</td>'
        + priceCells
        + '<td class="text-center"><button type="button" class="btn btn-sm btn-link text-danger pl-remove-tier-btn p-0" title="Remove tier"><i class="bx bx-minus-circle"></i></button></td>'
        + '</tr>'
    );
}

/**
 * Switch a product block to variant mode — one sub-section per variant.
 * @param {jQuery} $block
 * @param {Array<{VariantUID:number,Label:string,Tiers?:Array}>} variants
 * @returns {void}
 */
function _activateVariantMode($block, variants) {
    var types    = _plCustomerTypes || [];
    var isSpec   = $('input[name="PLAssignedTo"]:checked').val() === 'Customers';
    var headHtml = _buildTierHead(types, isSpec);

    $block.find('.pl-block-header .pl-add-tier-btn').addClass('d-none');
    $block.find('.pl-tier-table-wrap').addClass('d-none');
    var $sections = $block.find('.pl-variant-sections').empty().removeClass('d-none');

    $.each(variants, function (_, v) {
        var labelHtml = $('<span>').text(v.Label).html();
        var $section = $(
            '<div class="pl-var-section border-bottom" data-variant-uid="' + v.VariantUID + '">'
            + '<div class="pl-var-section-header d-flex align-items-center justify-content-between px-2 py-1">'
            +   '<span class="fw-semibold small text-muted">' + labelHtml + '</span>'
            +   '<button type="button" class="btn btn-sm btn-outline-primary pl-add-tier-btn py-0">'
            +     '<i class="bx bx-plus me-1"></i>Add Tier'
            +   '</button>'
            + '</div>'
            + '<div class="table-responsive">'
            +   '<table class="table table-sm table-bordered align-middle mb-0">'
            +     '<thead class="r2k-thead pl-tiers-thead" style="font-size:.74rem;">' + headHtml + '</thead>'
            +     '<tbody class="pl-tiers-body"></tbody>'
            +   '</table>'
            + '</div>'
            + '</div>'
        );
        $sections.append($section);
        var $tbody = $section.find('.pl-tiers-body');
        var tiers  = (v.Tiers && v.Tiers.length) ? v.Tiers : [{ MinQty: 1, Prices: {} }];
        $.each(tiers, function (_, tier) { _plAddTierRow($tbody, tier); });
    });

    $block.data('mode', 'variant');
}

/**
 * Switch a product block to tier mode — single flat table with block-level Add Tier.
 * @param {jQuery} $block
 * @returns {void}
 */
function _activateTierMode($block) {
    $block.find('.pl-block-header .pl-add-tier-btn').removeClass('d-none');
    $block.find('.pl-tier-table-wrap').removeClass('d-none');
    $block.find('.pl-variant-sections').addClass('d-none').empty();
    $block.data('mode', 'tier');
}

/**
 * Add a product block card to the rules section.
 * Product is always pre-selected (from search panel or edit restore).
 * @param {Object} data  {ProductUID:number, ProductName:string, variants?:Array, VariantRows?:Array, Tiers?:Array}
 * @returns {void}
 */
function plAddProductBlock(data) {
    if (_plCustomerTypes === null) {
        showToastNotification('Customer types are still loading. Please wait a moment.', 'warning');
        return;
    }

    _plRuleSeq++;
    var d        = data || {};
    var nameHtml = d.ProductName
        ? $('<span>').text(d.ProductName).html()
        : '<em class="text-muted">Unknown product</em>';

    var $block = $(
        '<div class="pl-prod-block card border mb-2" data-seq="' + _plRuleSeq + '" data-mode="tier">'
        + '<div class="pl-block-header d-flex align-items-center gap-2 p-2 border-bottom">'
        +   '<input type="hidden" class="pl-prod-uid" value="' + (d.ProductUID || 0) + '">'
        +   '<span class="pl-prod-name fw-semibold flex-grow-1 text-truncate">' + nameHtml + '</span>'
        +   '<button type="button" class="btn btn-sm btn-outline-secondary pl-pick-product-btn flex-shrink-0" title="Change product">'
        +     '<i class="bx bx-pencil"></i>'
        +   '</button>'
        +   '<button type="button" class="btn btn-sm btn-outline-primary pl-add-tier-btn flex-shrink-0">'
        +     '<i class="bx bx-plus me-1"></i>Add Tier'
        +   '</button>'
        +   '<button type="button" class="btn btn-sm btn-link text-danger p-0 pl-remove-block-btn flex-shrink-0" title="Remove product">'
        +     '<i class="bx bx-trash"></i>'
        +   '</button>'
        + '</div>'
        + '<div class="pl-tier-table-wrap table-responsive">'
        +   '<table class="table table-sm table-bordered align-middle mb-0">'
        +     '<thead class="r2k-thead pl-tiers-thead" style="font-size:.74rem;">' + _plTierHeadHtml + '</thead>'
        +     '<tbody class="pl-tiers-body"></tbody>'
        +   '</table>'
        + '</div>'
        + '<div class="pl-variant-sections d-none"></div>'
        + '</div>'
    );

    $('#PLProductBlocksWrap').append($block);

    var variantList = d.VariantRows || d.variants || [];

    if (variantList.length) {
        _activateVariantMode($block, variantList);
    } else if (d.Tiers && d.Tiers.length) {
        $.each(d.Tiers, function (_, tier) { _plAddTierRow($block.find('.pl-tiers-body'), tier); });
    } else {
        _plAddTierRow($block.find('.pl-tiers-body'), null);
    }

    _syncRulesEmpty();
}

// Remove an entire product block
$(document).on('click', '.pl-remove-block-btn', function () {
    $(this).closest('.pl-prod-block').remove();
    _syncRulesEmpty();
});

// Remove a single tier row
$(document).on('click', '.pl-remove-tier-btn', function () {
    var $block   = $(this).closest('.pl-prod-block');
    var $section = $(this).closest('.pl-var-section');
    $(this).closest('.pl-tier-row').remove();
    if ($section.length) {
        // Variant sub-section: always keep at least one empty row per variant
        if (!$section.find('.pl-tier-row').length) {
            _plAddTierRow($section.find('.pl-tiers-body'), null);
        }
    } else {
        // Tier mode: remove the whole block when its last row is gone
        if (!$block.find('.pl-tier-row').length) {
            $block.remove();
            _syncRulesEmpty();
        }
    }
});

// Add a new tier row — validate existing rows in this context (section or block) first
$(document).on('click', '.pl-add-tier-btn', function () {
    var $btn     = $(this);
    var $section = $btn.closest('.pl-var-section');
    var $block   = $btn.closest('.pl-prod-block');
    var blockNo  = $('#PLProductBlocksWrap .pl-prod-block').index($block) + 1;

    var $contextTiers, $targetTbody, errorPrefix;
    if ($section.length) {
        // Variant sub-section button: validate only this section's tiers
        $contextTiers = $section.find('.pl-tier-row');
        $targetTbody  = $section.find('.pl-tiers-body');
        var varLabel  = $.trim($section.find('.pl-var-section-header span').text());
        errorPrefix   = 'Product ' + blockNo + ' — ' + varLabel + ': ';
    } else {
        // Item-level button: tier mode
        $contextTiers = $block.find('.pl-tier-row');
        $targetTbody  = $block.find('.pl-tiers-body');
        errorPrefix   = 'Product ' + blockNo + ': ';
    }

    var error      = null;
    var nextMinQty = 1;

    $contextTiers.each(function (ti) {
        if (error) return false;
        var $tr    = $(this);
        var tierNo = ti + 1;
        var minQty = parseInt($tr.find('.pl-min-qty').val() || 1, 10);
        var maxRaw = $.trim($tr.find('.pl-max-qty').val());
        var maxQty = maxRaw !== '' ? parseInt(maxRaw, 10) : null;

        if (maxQty === null || maxQty <= 0) {
            error = 'Tier ' + tierNo + ': Set Max Qty before adding another tier.';
            $tr.find('.pl-max-qty').focus();
            return false;
        }
        if (maxQty <= minQty) {
            error = 'Tier ' + tierNo + ': Max Qty (' + maxQty + ') must be greater than Min Qty (' + minQty + ').';
            $tr.find('.pl-max-qty').focus();
            return false;
        }
        nextMinQty = maxQty + 1;

        $tr.find('.pl-ct-price').each(function () {
            if (error) return false;
            var raw = $.trim($(this).val());
            var v   = parseFloat(raw);
            if (raw === '' || isNaN(v) || v <= 0) {
                error = 'Tier ' + tierNo + ': Fill all price fields with a value greater than zero.';
                $(this).focus();
                return false;
            }
        });
    });

    if (error) { showToastNotification(errorPrefix + error, 'warning'); return; }
    _plAddTierRow($targetTbody, { MinQty: nextMinQty });
});

// ── PL Product Search Panel ───────────────────────────────────────────────────

/** null = new block add; jQuery block element = re-pick product for existing block */
var _plCurrentPickTarget = null;

/**
 * Open the inline product search panel.
 * @param {jQuery|null} target  null to add a new block; block jQuery to re-pick
 * @returns {void}
 */
function _openPLProductSearch(target) {
    _plCurrentPickTarget = target || null;
    $('#plProdSearchInput').val('');
    $('#plProdSearchClear').addClass('d-none');
    $('#PLProductSearchPanel').removeClass('d-none');
    $('#plProdSearchInput').trigger('focus');
    if (_plProductCache) {
        _renderPLSearchResults('');
    } else {
        $('#plProdSearchResults').html(
            '<tr><td colspan="2" class="text-center py-4 text-muted"><i class="bx bx-loader-alt bx-spin me-1"></i>Loading products…</td></tr>'
        );
        $('#plProdSearchPageInfo').text('');
        _loadPlProducts(function () { _renderPLSearchResults(''); });
    }
}

/**
 * Filter and render rows in the PL product search panel.
 * @param {string} term
 * @returns {void}
 */
function _renderPLSearchResults(term) {
    var products = _plProductCache || [];
    var t        = $.trim(term).toLowerCase();
    var filtered = t
        ? products.filter(function (p) {
            return (p.text + ' ' + (p.categoryName || '') + ' ' + (p.partNumber || '')).toLowerCase().indexOf(t) !== -1;
          })
        : products;

    $('#plProdSearchPageInfo').text(filtered.length + ' product' + (filtered.length !== 1 ? 's' : ''));

    if (!filtered.length) {
        $('#plProdSearchResults').html(
            '<tr><td colspan="2" class="text-center py-4 text-muted">No products found.</td></tr>'
        );
        return;
    }

    var limit = Math.min(filtered.length, 150);
    var rows  = '';
    for (var _ri = 0; _ri < limit; _ri++) {
        var p        = filtered[_ri];
        var varCount = (p.variants && p.variants.length) ? p.variants.length : 0;
        var varBadge = varCount > 0
            ? ' <span class="badge bg-label-info" style="font-size:.62rem;">' + varCount + ' variant' + (varCount > 1 ? 's' : '') + '</span>'
            : '';
        rows += '<tr class="pl-prod-search-row" data-uid="' + p.id + '">'
            + '<td>'
            +   '<div class="fw-semibold" style="font-size:.84rem;">' + $('<span>').text(p.text).html() + varBadge + '</div>'
            +   (p.categoryName ? '<div class="text-muted" style="font-size:.72rem;">' + $('<span>').text(p.categoryName).html() + '</div>' : '')
            + '</td>'
            + '<td class="text-muted" style="font-size:.78rem;">' + $('<span>').text(p.primaryUnit || '').html() + '</td>'
            + '</tr>';
    }
    if (filtered.length > limit) {
        rows += '<tr><td colspan="2" class="text-center py-2 text-muted" style="font-size:.74rem;">'
              + 'Showing first ' + limit + ' — refine your search to see more.'
              + '</td></tr>';
    }
    $('#plProdSearchResults').html(rows);
}

var _plSearchDebounce;
$(document).on('input', '#plProdSearchInput', function () {
    clearTimeout(_plSearchDebounce);
    var t = $(this).val();
    $('#plProdSearchClear').toggleClass('d-none', t === '');
    _plSearchDebounce = setTimeout(function () { _renderPLSearchResults(t); }, 280);
});

$(document).on('click', '#plProdSearchClear', function () {
    $('#plProdSearchInput').val('').trigger('input').trigger('focus');
});

$(document).on('click', '#plProdSearchCancel', function () {
    _plCurrentPickTarget = null;
    $('#PLProductSearchPanel').addClass('d-none');
});

// Product row click — pick product
$(document).on('click', '.pl-prod-search-row', function () {
    var uid     = parseInt($(this).data('uid'), 10);
    var product = null;
    var cache   = _plProductCache || [];
    for (var _pi = 0; _pi < cache.length; _pi++) {
        if (cache[_pi].id === uid) { product = cache[_pi]; break; }
    }
    if (!product) return;

    var variants = product.variants || [];
    $('#PLProductSearchPanel').addClass('d-none');

    if (_plCurrentPickTarget) {
        var $block = _plCurrentPickTarget;
        _plCurrentPickTarget = null;
        $block.find('.pl-prod-uid').val(uid);
        $block.find('.pl-prod-name').text(product.text);
        $block.find('.pl-tiers-body').empty();
        $block.find('.pl-variant-sections').empty();
        if (variants.length) {
            _activateVariantMode($block, variants);
        } else {
            _activateTierMode($block);
            _plAddTierRow($block.find('.pl-tiers-body'), null);
        }
    } else {
        plAddProductBlock({ ProductUID: uid, ProductName: product.text, variants: variants });
    }
});

// Pencil icon in block header — re-pick product
$(document).on('click', '.pl-pick-product-btn', function () {
    _openPLProductSearch($(this).closest('.pl-prod-block'));
});

// ── Open / Reset ──────────────────────────────────────────────────────────────

/**
 * Open the Price List modal. Loads customer types first (cached after first fetch).
 * Tables are built after types are available; modal opens immediately.
 * @returns {void}
 */
function openPriceListModal() {
    $('#PLUID').val(0);
    $('#PLModalTitle').text('Create Price List');
    $('#PLName').val('');
    $('#PLPriority').val('1');
    $('#PLStatus').val('1');
    $('#PLDescription').val('');
    $('#PLValidFromRaw,#PLValidToRaw').val('');
    $('input[name="PLAssignedTo"][value="All"]').prop('checked', true);
    $('input[name="PLScope"][value="All"]').prop('checked', true);
    $('#PLGroupsRow,#PLSpecificCustRow').addClass('d-none');
    if ($.fn.select2) {
        var $modal = $('#priceListModal');
        if (!$('#PLCustomerGroups').hasClass('select2-hidden-accessible')) {
            $('#PLCustomerGroups').select2({
                placeholder: 'Search and select customer groups…',
                allowClear: true,
                dropdownParent: $modal,
                width: '100%'
            });
        }
        if (!$('#PLCustomers').hasClass('select2-hidden-accessible')) {
            $('#PLCustomers').select2({
                placeholder: 'Search and select customers…',
                allowClear: true,
                dropdownParent: $modal,
                width: '100%'
            });
        }
        $('#PLCustomerGroups,#PLCustomers').val(null).trigger('change.select2');
    } else {
        $('#PLCustomerGroups,#PLCustomers').val(null);
    }
    $('#PLGlobalSection').removeClass('d-none');
    $('#PLRulesSection').addClass('d-none');
    $('#PLGlobalBasedOn').val('SellingPrice');
    $('#PLGlobalBody').empty();
    $('#PLGlobalLoading').removeClass('d-none');
    $('#PLGlobalTableWrap').addClass('d-none');
    $('#PLProductBlocksWrap .pl-prod-block').remove();
    _plTierHeadHtml = '';
    $('#PLRulesLoading').addClass('d-none');
    _syncRulesEmpty();
    _plRuleSeq = 0;
    $('#PLProductSearchPanel').addClass('d-none');
    _loadPlProducts(function () {});
    _initPLDatePickers();
    $('#priceListModal').modal('show');

    _loadCustTypes(function (types) {
        var isSpecific = $('input[name="PLAssignedTo"]:checked').val() === 'Customers';
        _buildGlobalTable(types, isSpecific);
        _buildRulesHeader(types, isSpecific);
    });
}

$(document).on('click', '#NewPriceList, #NewPriceListEmpty', function (e) {
    e.preventDefault();
    openPriceListModal();
});

// ── Edit ──────────────────────────────────────────────────────────────────────

/**
 * Populate the price list modal form with data fetched for editing.
 * Call this after openPriceListModal() has already reset/shown the modal.
 * @param {Object} data  response from getPriceListForEdit (header fields + Assignments/Discounts/Rules)
 * @returns {void}
 */
/**
 * Format a number using org decimal settings, stripping unnecessary trailing zeros.
 * @param {number|string} v
 * @returns {number}
 */
function _plSmartDec(v) {
    var dec = (typeof JwtData !== 'undefined' && JwtData.GenSettings) ? 2;
    return parseFloat(parseFloat(v || 0).toFixed(dec));
}
function _plDecStep() {
    var dec = (typeof JwtData !== 'undefined' && JwtData.GenSettings) ? 2;
    return (1 / Math.pow(10, dec)).toFixed(dec);
}

/**
 * @param {Object} data  response from getPriceListForEdit (header fields + Assignments/Discounts/Rules)
 * @returns {void}
 */
function _fillPriceListForm(data) {
    var assignedTo = data.AssignedToType || 'All';
    var scope      = data.Scope || 'All';
    var isSpecific = assignedTo === 'Customers';

    // Basic fields
    $('#PLName').val(data.Name || '');
    $('#PLStatus').val(String(data.Status != null ? data.Status : 1));
    $('#PLPriority').val(data.Priority || 1);
    $('#PLDescription').val(data.Description || '');

    // Dates
    if (data.ValidFrom && _plFpFrom) _plFpFrom.setDate(data.ValidFrom);
    if (data.ValidTo   && _plFpTo)   _plFpTo.setDate(data.ValidTo);

    // Assigned To — set radio + visibility WITHOUT triggering change (avoids double-loading)
    $('input[name="PLAssignedTo"][value="' + assignedTo + '"]').prop('checked', true);
    $('#PLGroupsRow').toggleClass('d-none', assignedTo !== 'Groups');
    $('#PLSpecificCustRow').toggleClass('d-none', assignedTo !== 'Customers');

    // Scope — set radio + section visibility
    $('input[name="PLScope"][value="' + scope + '"]').prop('checked', true);
    $('#PLGlobalSection').toggleClass('d-none', scope === 'Specific');
    $('#PLRulesSection').toggleClass('d-none', scope !== 'Specific');
    $('#PLGlobalBasedOn').val(data.GlobalBasedOn || 'SellingPrice');

    // Build tables with correct isSpecific, then fill saved values
    _loadCustTypes(function (types) {
        _buildGlobalTable(types, isSpecific);
        _buildRulesHeader(types, isSpecific);

        // Fill All Products discount rows (smart decimal on value)
        if (scope === 'All' && data.Discounts && data.Discounts.length) {
            $.each(data.Discounts, function (_, d) {
                var $tr = $('#PLGlobalBody tr[data-ctuid="' + d.CustomerTypeUID + '"]');
                if (!$tr.length) return;
                $tr.find('.pl-global-type').val(d.DiscountType).trigger('change');
                $tr.find('.pl-global-val').val(_plSmartDec(d.DiscountValue));
            });
        }

        // Fill Specific Products — group by ProductUID; detect variant vs tier mode per product
        if (scope === 'Specific' && data.Rules && data.Rules.length) {
            var prodMap   = {};
            var prodOrder = [];
            $.each(data.Rules, function (_, r) {
                var pKey = r.ProductUID;
                if (!prodMap[pKey]) {
                    prodMap[pKey] = {
                        ProductUID:   r.ProductUID,
                        ProductName:  r.ProductName,
                        HasVariants:  false,
                        VariantMap:   {},
                        VariantOrder: [],
                        Tiers:        {},
                        TierOrder:    []
                    };
                    prodOrder.push(pKey);
                }
                var varUID = r.VariantUID || 0;
                if (varUID > 0) {
                    prodMap[pKey].HasVariants = true;
                    if (!prodMap[pKey].VariantMap[varUID]) {
                        var varParts = [r.BrandName, r.SizeName].filter(Boolean);
                        prodMap[pKey].VariantMap[varUID] = {
                            VariantUID: varUID,
                            BrandName:  r.BrandName || '',
                            SizeName:   r.SizeName  || '',
                            Label:      varParts.length ? varParts.join(' / ') : ('Variant #' + varUID),
                            Tiers:      {},
                            TierOrder:  []
                        };
                        prodMap[pKey].VariantOrder.push(varUID);
                    }
                    // Group tiers within this variant
                    var vData = prodMap[pKey].VariantMap[varUID];
                    var tKey2 = r.MinQty + '_' + (r.MaxQty !== null && r.MaxQty !== undefined ? r.MaxQty : '');
                    if (!vData.Tiers[tKey2]) {
                        vData.Tiers[tKey2] = { MinQty: r.MinQty, MaxQty: r.MaxQty, Prices: {} };
                        vData.TierOrder.push(tKey2);
                    }
                    vData.Tiers[tKey2].Prices[r.CustomerTypeUID] = _plSmartDec(r.Price);
                } else {
                    var tKey = r.MinQty + '_' + (r.MaxQty !== null && r.MaxQty !== undefined ? r.MaxQty : '');
                    if (!prodMap[pKey].Tiers[tKey]) {
                        prodMap[pKey].Tiers[tKey] = { MinQty: r.MinQty, MaxQty: r.MaxQty, Prices: {} };
                        prodMap[pKey].TierOrder.push(tKey);
                    }
                    prodMap[pKey].Tiers[tKey].Prices[r.CustomerTypeUID] = _plSmartDec(r.Price);
                }
            });
            $.each(prodOrder, function (_, pKey) {
                var prod = prodMap[pKey];
                if (prod.HasVariants) {
                    plAddProductBlock({
                        ProductUID:  prod.ProductUID,
                        ProductName: prod.ProductName,
                        VariantRows: prod.VariantOrder.map(function (vUID) {
                            var vm = prod.VariantMap[vUID];
                            return {
                                VariantUID: vm.VariantUID,
                                BrandName:  vm.BrandName,
                                SizeName:   vm.SizeName,
                                Label:      vm.Label,
                                Tiers:      vm.TierOrder.map(function (tk) { return vm.Tiers[tk]; })
                            };
                        })
                    });
                } else {
                    plAddProductBlock({
                        ProductUID:  prod.ProductUID,
                        ProductName: prod.ProductName,
                        Tiers:       prod.TierOrder.map(function (k) { return prod.Tiers[k]; })
                    });
                }
            });
        }
    });

    // Set assignment selections after their options are populated
    var refUIDs = (data.Assignments || []).map(function (a) { return String(a.RefUID); });
    if (assignedTo === 'Groups' && refUIDs.length) {
        _loadPLGroups(function () { $('#PLCustomerGroups').val(refUIDs).trigger('change.select2'); });
    } else if (assignedTo === 'Customers' && refUIDs.length) {
        _loadPLCustomers(function () { $('#PLCustomers').val(refUIDs).trigger('change.select2'); });
    }
}

// Fetch data first, open modal only after data is ready
$(document).on('click', '.editPriceList', function () {
    var uid = parseInt($(this).data('uid'), 10);
    if (!uid) return;

    var $btn = $(this).prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i>');

    $.ajax({
        url: '/products/getPriceListForEdit', method: 'GET', cache: false,
        data: { uid: uid },
        success: function (r) {
            $btn.prop('disabled', false).html('<i class="bx bx-edit"></i>');
            if (r.Error) { showToastNotification(r.Message || 'Failed to load price list.', 'error'); return; }
            openPriceListModal();
            $('#PLModalTitle').text('Edit Price List');
            $('#PLUID').val(uid);
            _fillPriceListForm(r.Data);
        },
        error: function () {
            $btn.prop('disabled', false).html('<i class="bx bx-edit"></i>');
            showToastNotification('Failed to load price list data. Please try again.', 'error');
        }
    });
});

// ── Save ──────────────────────────────────────────────────────────────────────

/**
 * Collect discount rows from the All Products global table.
 * @returns {Array<{CustomerTypeUID:number,DiscountType:string,DiscountValue:number}>}
 */
function _collectDiscounts() {
    var rows = [];
    $('#PLGlobalBody tr').each(function () {
        var $tr   = $(this);
        var uid   = parseInt($tr.data('ctuid') || 0, 10);
        var dtype = $tr.find('.pl-global-type').val() || 'Percentage';
        var val   = parseFloat($tr.find('.pl-global-val').val()) || 0;
        rows.push({ CustomerTypeUID: uid, DiscountType: dtype, DiscountValue: val });
    });
    return rows;
}

/**
 * Collect rule rows from the Specific Products table.
 * In variant mode: one rule per variant row per customer type (MinQty=1, MaxQty=null).
 * In tier mode: one rule per tier row per customer type (existing behaviour).
 * @returns {Array<{ProductUID:number,VariantUID:number,MinQty:number,MaxQty:number|null,CustomerTypeUID:number,Price:number|null}>}
 */
function _collectRules() {
    var rules = [];
    $('#PLProductBlocksWrap .pl-prod-block').each(function () {
        var $block  = $(this);
        var prodUID = parseInt($block.find('.pl-prod-uid').val() || 0, 10);

        if ($block.data('mode') === 'variant') {
            $block.find('.pl-var-section').each(function () {
                var $section   = $(this);
                var variantUID = parseInt($section.data('variant-uid') || 0, 10);
                $section.find('.pl-tier-row').each(function () {
                    var $tr       = $(this);
                    var minQty    = parseInt($tr.find('.pl-min-qty').val() || 1, 10);
                    var maxQtyRaw = $.trim($tr.find('.pl-max-qty').val());
                    var maxQty    = maxQtyRaw !== '' ? parseInt(maxQtyRaw, 10) : null;
                    $tr.find('.pl-ct-price').each(function () {
                        var raw = $.trim($(this).val());
                        rules.push({
                            ProductUID:      prodUID,
                            VariantUID:      variantUID,
                            MinQty:          minQty,
                            MaxQty:          maxQty,
                            CustomerTypeUID: parseInt($(this).data('ctuid') || 0, 10),
                            Price:           raw !== '' ? (parseFloat(raw) || 0) : null
                        });
                    });
                });
            });
        } else {
            $block.find('.pl-tier-row').each(function () {
                var $tr       = $(this);
                var minQty    = parseInt($tr.find('.pl-min-qty').val() || 1, 10);
                var maxQtyRaw = $.trim($tr.find('.pl-max-qty').val());
                var maxQty    = maxQtyRaw !== '' ? parseInt(maxQtyRaw, 10) : null;
                $tr.find('.pl-ct-price').each(function () {
                    var raw = $.trim($(this).val());
                    rules.push({
                        ProductUID:      prodUID,
                        VariantUID:      0,
                        MinQty:          minQty,
                        MaxQty:          maxQty,
                        CustomerTypeUID: parseInt($(this).data('ctuid') || 0, 10),
                        Price:           raw !== '' ? (parseFloat(raw) || 0) : null
                    });
                });
            });
        }
    });
    return rules;
}

/**
 * Validate all product blocks + tiers for Specific Products scope.
 * @returns {string|null}  error message, or null if all valid
 */
function _validateAllRules() {
    var $blocks = $('#PLProductBlocksWrap .pl-prod-block');
    if (!$blocks.length) return 'Add at least one product rule, or switch Scope to All Products.';

    var errorMsg = null;

    $blocks.each(function (bi) {
        if (errorMsg) return false;
        var $block  = $(this);
        var blockNo = bi + 1;

        if (!parseInt($block.find('.pl-prod-uid').val() || 0, 10)) {
            errorMsg = 'Product ' + blockNo + ': Please select a product.';
            return false;
        }

        if ($block.data('mode') === 'variant') {
            // Validate each variant sub-section independently
            $block.find('.pl-var-section').each(function () {
                if (errorMsg) return false;
                var $section = $(this);
                var varLabel = $.trim($section.find('.pl-var-section-header span').text());
                var prefix   = 'Product ' + blockNo + ' — ' + varLabel + ': ';
                var $tiers   = $section.find('.pl-tier-row');

                if (!$tiers.length) {
                    errorMsg = prefix + 'Add at least one price tier.';
                    return false;
                }

                $tiers.each(function (ti) {
                    if (errorMsg) return false;
                    var $tr    = $(this);
                    var tierNo = ti + 1;
                    var isLast = ti === $tiers.length - 1;
                    var minQty = parseInt($tr.find('.pl-min-qty').val() || 1, 10);
                    var maxRaw = $.trim($tr.find('.pl-max-qty').val());
                    var maxQty = maxRaw !== '' ? parseInt(maxRaw, 10) : null;

                    if (!isLast) {
                        if (maxQty === null || maxQty <= 0) {
                            errorMsg = prefix + 'Tier ' + tierNo + ': Max Qty is required (only the last tier can be open-ended).';
                            $tr.find('.pl-max-qty').focus(); return false;
                        }
                        if (maxQty <= minQty) {
                            errorMsg = prefix + 'Tier ' + tierNo + ': Max Qty (' + maxQty + ') must be greater than Min Qty (' + minQty + ').';
                            $tr.find('.pl-max-qty').focus(); return false;
                        }
                    } else if (maxQty !== null && maxQty > 0 && maxQty <= minQty) {
                        errorMsg = prefix + 'Tier ' + tierNo + ': Max Qty must be greater than Min Qty (' + minQty + ').';
                        $tr.find('.pl-max-qty').focus(); return false;
                    }

                    if (ti > 0) {
                        var prevMaxRaw = $.trim($tiers.eq(ti - 1).find('.pl-max-qty').val());
                        var prevMax    = prevMaxRaw !== '' ? parseInt(prevMaxRaw, 10) : null;
                        if (prevMax !== null && minQty !== prevMax + 1) {
                            errorMsg = prefix + 'Tier ' + tierNo + ': Min Qty (' + minQty + ') must equal previous tier\'s Max Qty + 1 (' + (prevMax + 1) + ').';
                            $tr.find('.pl-min-qty').closest('td').addClass('table-warning'); return false;
                        }
                    }

                    $tr.find('.pl-ct-price').each(function () {
                        if (errorMsg) return false;
                        var raw = $.trim($(this).val());
                        var v   = parseFloat(raw);
                        if (raw === '' || isNaN(v) || v <= 0) {
                            errorMsg = prefix + 'Tier ' + tierNo + ': Fill all price fields with a value greater than zero.';
                            $(this).focus(); return false;
                        }
                    });
                });
            });
        } else {
            // Tier mode validation
            var $tiers = $block.find('.pl-tier-row');
            if (!$tiers.length) {
                errorMsg = 'Product ' + blockNo + ': Add at least one price tier.';
                return false;
            }

            $tiers.each(function (ti) {
                if (errorMsg) return false;
                var $tr    = $(this);
                var tierNo = ti + 1;
                var isLast = ti === $tiers.length - 1;
                var minQty = parseInt($tr.find('.pl-min-qty').val() || 1, 10);
                var maxRaw = $.trim($tr.find('.pl-max-qty').val());
                var maxQty = maxRaw !== '' ? parseInt(maxRaw, 10) : null;

                if (!isLast) {
                    if (maxQty === null || maxQty <= 0) {
                        errorMsg = 'Product ' + blockNo + ', Tier ' + tierNo + ': Max Qty is required (only the last tier can be open-ended).';
                        $tr.find('.pl-max-qty').focus(); return false;
                    }
                    if (maxQty <= minQty) {
                        errorMsg = 'Product ' + blockNo + ', Tier ' + tierNo + ': Max Qty (' + maxQty + ') must be greater than Min Qty (' + minQty + ').';
                        $tr.find('.pl-max-qty').focus(); return false;
                    }
                } else if (maxQty !== null && maxQty > 0 && maxQty <= minQty) {
                    errorMsg = 'Product ' + blockNo + ', Tier ' + tierNo + ': Max Qty must be greater than Min Qty (' + minQty + ').';
                    $tr.find('.pl-max-qty').focus(); return false;
                }

                if (ti > 0) {
                    var prevMaxRaw = $.trim($tiers.eq(ti - 1).find('.pl-max-qty').val());
                    var prevMax    = prevMaxRaw !== '' ? parseInt(prevMaxRaw, 10) : null;
                    if (prevMax !== null && minQty !== prevMax + 1) {
                        errorMsg = 'Product ' + blockNo + ', Tier ' + tierNo + ': Min Qty (' + minQty + ') must equal previous tier\'s Max Qty + 1 (' + (prevMax + 1) + ').';
                        $tr.find('.pl-min-qty').closest('td').addClass('table-warning'); return false;
                    }
                }

                $tr.find('.pl-ct-price').each(function () {
                    if (errorMsg) return false;
                    var raw = $.trim($(this).val());
                    var v   = parseFloat(raw);
                    if (raw === '' || isNaN(v) || v <= 0) {
                        errorMsg = 'Product ' + blockNo + ', Tier ' + tierNo + ': Fill all price fields with a value greater than zero.';
                        $(this).focus(); return false;
                    }
                });
                if (errorMsg) return false;

                if (_plProductCache && (_plBelowPurchaseAction === 'strict' || _plBelowPurchaseAction === 'warn')) {
                    var prodUID2 = parseInt($block.find('.pl-prod-uid').val() || 0, 10);
                    var prodObj  = null;
                    for (var _pi = 0; _pi < _plProductCache.length; _pi++) {
                        if (parseInt(_plProductCache[_pi].id, 10) === prodUID2) { prodObj = _plProductCache[_pi]; break; }
                    }
                    if (prodObj && prodObj.purchasePrice > 0) {
                        var dec2  = (typeof JwtData !== 'undefined' && JwtData.GenSettings) ? 2;
                        var effPP = (prodObj.purchasePriceTaxUID === 1)
                            ? prodObj.purchasePrice
                            : prodObj.purchasePrice * (1 + (prodObj.taxPercent || 0) / 100);
                        $tr.find('.pl-ct-price').each(function () {
                            if (errorMsg) return false;
                            var raw2    = $.trim($(this).val());
                            if (raw2 === '') return;
                            var entered = parseFloat(raw2);
                            if (!isNaN(entered) && entered > 0 && entered < effPP) {
                                var msg = 'Product ' + blockNo + ', Tier ' + tierNo + ': Price (' + _plCurSym + entered.toFixed(dec2) + ') is below purchase cost (' + _plCurSym + effPP.toFixed(dec2) + ').';
                                if (_plBelowPurchaseAction === 'strict') {
                                    errorMsg = msg + ' Cannot save.'; $(this).focus();
                                } else {
                                    showToastNotification(msg, 'warning');
                                }
                            }
                        });
                    }
                }
            });
        }
    });

    return errorMsg;
}

/**
 * Validate the All Products global discount table.
 * @returns {string|null}
 */
function _validateGlobalDiscounts() {
    var errorMsg = null;
    $('#PLGlobalBody tr').each(function (i) {
        if (errorMsg) return false;
        var $tr   = $(this);
        var dtype = $tr.find('.pl-global-type').val() || 'Percentage';
        if (dtype === 'NoDiscount') return;
        var val   = parseFloat($tr.find('.pl-global-val').val());
        var label = $.trim($tr.find('td:first').text()) || ('Row ' + (i + 1));
        if (isNaN(val) || val <= 0) {
            errorMsg = 'Discount for "' + label + '": Enter a value greater than zero, or select No Discount.';
            $tr.find('.pl-global-val').focus();
            return false;
        }
    });
    return errorMsg;
}

/**
 * @returns {void}
 */
function savePriceListForm() {
    var name = $.trim($('#PLName').val());
    if (!name) {
        showToastNotification('Please enter a price list name.', 'error');
        $('#PLName').focus();
        return;
    }

    var assignedTo  = $('input[name="PLAssignedTo"]:checked').val() || 'All';
    var scope       = $('input[name="PLScope"]:checked').val()       || 'All';
    var assignments = [];

    if (assignedTo === 'Groups') {
        assignments = $('#PLCustomerGroups').val() || [];
        if (!assignments.length) {
            showToastNotification('Please select at least one customer group.', 'error');
            return;
        }
    } else if (assignedTo === 'Customers') {
        assignments = $('#PLCustomers').val() || [];
        if (!assignments.length) {
            showToastNotification('Please select at least one customer.', 'error');
            return;
        }
    }

    var rules = [];
    if (scope === 'All') {
        var discErr = _validateGlobalDiscounts();
        if (discErr) { showToastNotification(discErr, 'error'); return; }
    }
    if (scope === 'Specific') {
        var ruleErr = _validateAllRules();
        if (ruleErr) { showToastNotification(ruleErr, 'error'); return; }
        rules = _collectRules();
    }

    var isEdit  = parseInt($('#PLUID').val(), 10) > 0;
    var payload = {
        PLUID:          parseInt($('#PLUID').val(), 10) || 0,
        PageNo:         isEdit ? PageNo : 1,
        RowLimit:       RowLimit,
        Filter:         JSON.stringify(Filter),
        Name:           name,
        Status:         $('#PLStatus').val(),
        Priority:       $('#PLPriority').val() || 1,
        ValidFrom:      $('#PLValidFromRaw').val(),
        ValidTo:        $('#PLValidToRaw').val(),
        Description:    $.trim($('#PLDescription').val()),
        AssignedToType: assignedTo,
        Scope:          scope,
        GlobalBasedOn:  $('#PLGlobalBasedOn').val() || 'SellingPrice',
        Assignments:    JSON.stringify(assignments),
        Discounts:      JSON.stringify(scope === 'All' ? _collectDiscounts() : []),
        Rules:          JSON.stringify(rules)
    };

    function _submitPL() {
        var $btn = $('#priceListModal .btn-primary').first().prop('disabled', true).text('Saving…');
        $.ajax({
            url: '/products/savePriceList', method: 'POST', data: payload, cache: false,
            success: function (r) {
                $btn.prop('disabled', false).html('<i class="bx bx-check me-1"></i>Save');
                if (r.Error) { showToastNotification(r.Message, 'error'); return; }
                $('#priceListModal').modal('hide');
                showToastNotification(r.Message, 'success');
                _applyPLResponse(r);
            },
            error: function () {
                $btn.prop('disabled', false).html('<i class="bx bx-check me-1"></i>Save');
                showToastNotification('Server error. Please try again.', 'error');
            }
        });
    }

    if (!isEdit) {
        Swal.fire({
            title: 'Create Price List?',
            html: 'Once created, this price list will be <strong>applied to matching products</strong> and will <strong>affect customer pricing</strong> on the transaction page.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#0d6efd',
            confirmButtonText: 'Yes, Create',
            cancelButtonColor: '#6c757d',
            cancelButtonText: 'Cancel',
        }).then(function (r) {
            if (r.isConfirmed) _submitPL();
        });
    } else {
        _submitPL();
    }
}

// ── Delete Price List ─────────────────────────────────────────────────────────

$(document).on('click', '.deletePriceList', function () {
    var uid  = parseInt($(this).data('uid'), 10);
    var name = $(this).data('name') || 'this price list';
    if (!uid) return;

    Swal.fire({
        title: 'Delete Price List?',
        html: 'Are you sure you want to delete <strong>' + $('<span>').text(name).html() + '</strong>? This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        confirmButtonText: 'Yes, delete',
        cancelButtonColor: '#6c757d',
        cancelButtonText: 'Cancel',
    }).then(function (result) {
        if (!result.isConfirmed) return;
        $.ajax({
            url: '/products/deletePriceList', method: 'POST', cache: false,
            data: { PriceListUID: uid, PageNo: PageNo, RowLimit: RowLimit, Filter: JSON.stringify(Filter), [CsrfName]: CsrfToken },
            success: function (r) {
                if (r.Error) { showToastNotification(r.Message, 'error'); return; }
                showToastNotification(r.Message, 'success');
                _applyPLResponse(r);
            },
            error: function () { showToastNotification('Failed to delete. Please try again.', 'error'); }
        });
    });
});

// Category product count click
$(document).on('click', '.catg-prod-count-btn', function () {
    var catgUID  = $(this).data('catguid');
    var catgName = $(this).data('catgname');
    var prodCount = parseInt($(this).text()) || 0;
    var sym = (typeof currencySymbol !== 'undefined') ? currencySymbol : '\u20b9';
    var dec = typeof JwtData !== 'undefined' && JwtData.GenSettings ? 2;

    // Show banner + skeleton immediately — no blank page
    var skeleton =
        '<div style="background:#e8f0fe;border-left:4px solid #0d6efd;padding:14px 20px;">'
        + '<div class="d-flex align-items-center gap-3">'
        + '<div style="background:#0d6efd22;border-radius:10px;padding:9px 11px;">'
        + '<i class="bx bx-layer" style="font-size:1.7rem;color:#0d6efd;display:block;"></i></div>'
        + '<div>'
        + '<div style="font-size:1.05rem;font-weight:800;color:#0d6efd;">' + $('<span>').text(catgName).html() + '</div>'
        + '<div style="font-size:.77rem;color:#6c757d;margin-top:3px;">'
        + '<i class="bx bx-package me-1"></i>' + prodCount + ' active product' + (prodCount !== 1 ? 's' : '')
        + '</div></div></div></div>'
        // skeleton summary cards
        + '<div style="padding:14px 20px;border-bottom:1px solid #e9ecef;">'
        + '<div class="row g-2">'
        + '<div class="col-6"><div style="background:#f0f0f0;border-radius:6px;height:60px;" class="catg-skeleton"></div></div>'
        + '<div class="col-6"><div style="background:#f0f0f0;border-radius:6px;height:60px;" class="catg-skeleton"></div></div>'
        + '</div></div>'
        // skeleton rows
        + '<div style="padding:14px 20px;">'
        + [1,2,3].map(function(){ return '<div style="background:#f0f0f0;border-radius:4px;height:32px;margin-bottom:8px;" class="catg-skeleton"></div>'; }).join('')
        + '</div>';

    $('#catgProductsModalBody').html(skeleton);
    $('#catgProductsModal').modal('show');
    ajaxLoading(0);
    $.ajax({
        url    : '/products/getProductsByCategory',
        method : 'POST',
        data   : { CategoryUID: catgUID, [CsrfName]: CsrfToken },
        success: function (res) {
            ajaxLoading(1);
            function _amt(n) { return sym + ' ' + parseFloat(n || 0).toFixed(dec); }
            function _infoCard(content, borderColor) {
                return '<div style="background:#fafafa;border:1px solid #e9ecef;border-left:3px solid '
                    + borderColor + ';border-radius:6px;padding:10px 12px;height:100%;">' + content + '</div>';
            }
            function _secHdr(icon, label, color) {
                return '<div class="d-flex align-items-center gap-2" style="padding:4px 0 10px;">'
                    + '<i class="bx ' + icon + '" style="font-size:1.05rem;color:' + color + ';"></i>'
                    + '<span style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:' + color + ';">' + label + '</span></div>';
            }

            // Banner always stays — update product count with real value
            var realCount = (res.Products && res.Products.length) ? res.Products.length : 0;
            var html = '<div style="background:#e8f0fe;border-left:4px solid #0d6efd;padding:14px 20px;">'
                + '<div class="d-flex align-items-center gap-3">'
                + '<div style="background:#0d6efd22;border-radius:10px;padding:9px 11px;">'
                + '<i class="bx bx-layer" style="font-size:1.7rem;color:#0d6efd;display:block;"></i></div>'
                + '<div>'
                + '<div style="font-size:1.05rem;font-weight:800;color:#0d6efd;">' + _esc(catgName) + '</div>'
                + '<div style="font-size:.77rem;color:#6c757d;margin-top:3px;">'
                + '<i class="bx bx-package me-1"></i>' + realCount + ' active product' + (realCount !== 1 ? 's' : '')
                + '</div></div></div></div>';

            if (res.Error || !res.Products || res.Products.length === 0) {
                html += '<div class="d-flex flex-column align-items-center py-5 text-muted">'
                    + '<i class="bx bx-package" style="font-size:2.5rem;color:#dee2e6;"></i>'
                    + '<p class="mt-2 mb-0" style="font-size:.88rem;">No active products in this category.</p>'
                    + '</div>';
                $('#catgProductsModalBody').html(html);
                return;
            }

            // Summary cards
            var totalStockVal = 0;
            res.Products.forEach(function(p) {
                totalStockVal += parseFloat(p.AvailableQuantity || 0) * parseFloat(p.PurchasePrice || 0);
            });
            var card1 = '<div style="font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.4px;color:#6c757d;margin-bottom:5px;"><i class="bx bx-package me-1"></i>Total Items</div>'
                + '<div style="font-size:1.3rem;font-weight:800;color:#0d6efd;">' + res.Products.length + '</div>';
            var card2 = '<div style="font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.4px;color:#6c757d;margin-bottom:5px;"><i class="bx bx-rupee me-1"></i>Stock Value</div>'
                + '<div style="font-size:1.1rem;font-weight:800;color:#198754;">' + sym + ' ' + totalStockVal.toFixed(dec) + '</div>';
            html += '<div style="padding:14px 20px;border-bottom:1px solid #e9ecef;">'
                + _secHdr('bx-bar-chart-alt-2', 'Summary', '#6c757d')
                + '<div class="row g-2">'
                + '<div class="col-6">' + _infoCard(card1, '#0d6efd') + '</div>'
                + '<div class="col-6">' + _infoCard(card2, '#198754') + '</div>'
                + '</div></div>';

            // Product table
            var rows = '';
            res.Products.forEach(function (p, i) {
                var qty = parseFloat(p.AvailableQuantity || 0);
                var qtyHtml = qty > 0
                    ? '<span style="color:#198754;font-weight:600;">' + qty + '</span>'
                    : (qty === 0
                        ? '<span style="color:#dc3545;font-weight:600;">0</span>'
                        : '<span style="color:#dc3545;font-weight:600;">' + qty + '</span><span class="badge bg-label-danger ms-1" style="font-size:.62rem;">Out</span>');
                var mrp = parseFloat(p.MRP || 0);
                rows += '<tr>'
                    + '<td class="text-muted text-center" style="width:32px;">' + (i + 1) + '</td>'
                    + '<td><span style="font-weight:500;">' + _esc(p.ItemName) + '</span></td>'
                    + '<td class="text-end">' + _amt(p.SellingPrice) + '</td>'
                    + '<td class="text-end">' + (mrp > 0 ? _amt(mrp) : '<span class="text-muted">—</span>') + '</td>'
                    + '<td class="text-end">' + _amt(p.PurchasePrice) + '</td>'
                    + '<td class="text-center">' + qtyHtml + '</td>'
                    + '</tr>';
            });
            html += '<div style="padding:14px 20px;">'
                + _secHdr('bx-package', 'Product List', '#fd7e14')
                + '<div class="table-responsive">'
                + '<table class="table table-sm table-hover mb-0" style="font-size:.82rem;">'
                + '<thead><tr style="background:#fff3e0;">'
                + '<th class="text-center">#</th><th>Item Name</th>'
                + '<th class="text-end">Selling Price</th><th class="text-end">MRP</th>'
                + '<th class="text-end">Purchase Price</th><th class="text-center">Avail. Qty</th>'
                + '</tr></thead><tbody>' + rows + '</tbody></table></div></div>';

            $('#catgProductsModalBody').html(html);
        },
        error: function () {
            ajaxLoading(1);
            $('#catgProductsModalBody').html('<div class="alert alert-danger m-3">Failed to load products.</div>');
        }
    });
});

// ── Client-side tab switching (menu interceptor + browser back/forward) ───────
var _paramToTabId = { 'item': 'Item', 'group': 'Groups', 'category': 'Categories' };

$(document).on('samePageTabSwitch', function (e, tabParam) {
    var tabId = _paramToTabId[(tabParam || '').toLowerCase()];
    if (!tabId) return;
    var $tab = $('.TabPane[data-id="' + tabId + '"]');
    if ($tab.length && !$tab.hasClass('active') && $tab[0]) $tab[0].click();
});

$(window).on('popstate', function () {
    var m = window.location.search.match(/[?&]tab=([^&]+)/i);
    var tabParam = m ? m[1].toLowerCase() : 'item';
    $(document).trigger('samePageTabSwitch', [tabParam]);
});

// Sync Items Cache
$(document).on('click', '#btnSyncProductsCache', function () {
    var $btn = $(this);
    $btn.find('i').removeClass('bx-planet').addClass('bx-loader-alt bx-spin');
    $.ajax({
        url    : '/products/syncProductsCache',
        method : 'POST',
        data   : { [CsrfName]: CsrfToken },
        success: function (resp) {
            CsrfToken = resp.NewCsrfToken || CsrfToken;
            $btn.find('i').removeClass('bx-loader-alt bx-spin').addClass('bx-planet');
            if (resp.Error) { showToastNotification(resp.Message, 'error'); }
            else { showToastNotification(resp.Message, 'success'); }
        },
        error: function () {
            $btn.find('i').removeClass('bx-loader-alt bx-spin').addClass('bx-planet');
            showToastNotification('Sync failed. Please try again.', 'error');
        }
    });
});

// Sync Categories Cache
$(document).on('click', '#btnSyncCategoriesCache', function () {
    var $btn = $(this);
    $btn.find('i').removeClass('bx-planet').addClass('bx-loader-alt bx-spin');
    $.ajax({
        url    : '/products/syncCategoriesCache',
        method : 'POST',
        data   : { [CsrfName]: CsrfToken },
        success: function (resp) {
            CsrfToken = resp.NewCsrfToken || CsrfToken;
            $btn.find('i').removeClass('bx-loader-alt bx-spin').addClass('bx-planet');
            if (resp.Error) { showToastNotification(resp.Message, 'error'); }
            else { showToastNotification(resp.Message, 'success'); }
        },
        error: function () {
            $btn.find('i').removeClass('bx-loader-alt bx-spin').addClass('bx-planet');
            showToastNotification('Sync failed. Please try again.', 'error');
        }
    });
});

// Sync Price List Cache
$(document).on('click', '#btnSyncPriceListCache', function () {
    var $btn = $(this);
    $btn.find('i').removeClass('bx-planet').addClass('bx-loader-alt bx-spin');
    $.ajax({
        url    : '/products/syncPriceListCache',
        method : 'POST',
        data   : { [CsrfName]: CsrfToken },
        success: function (resp) {
            CsrfToken = resp.NewCsrfToken || CsrfToken;
            $btn.find('i').removeClass('bx-loader-alt bx-spin').addClass('bx-planet');
            if (resp.Error) { showToastNotification(resp.Message, 'error'); }
            else { showToastNotification(resp.Message, 'success'); }
        },
        error: function () {
            $btn.find('i').removeClass('bx-loader-alt bx-spin').addClass('bx-planet');
            showToastNotification('Sync failed. Please try again.', 'error');
        }
    });
});

// ── Product Profile Modal ──────────────────────────────────────────────────
var _ppCurrentUID = 0;
var _ppTabLoaded  = {};

/**
 * Opens the product profile modal and loads the overview tab.
 * @param {number} uid - ProductUID
 * @returns {void}
 */
window.openProductProfile = function (uid) {
    _ppCurrentUID = uid;
    _ppTabLoaded  = {};
    $('#ppTabContent .pp-tab-pane').empty().removeClass('d-block').addClass('d-none');
    $('#ppTabContent_overview').removeClass('d-none').addClass('d-block');
    $('#ppTabNav .pp-tab-link').removeClass('active');
    $('#ppTab_overview').addClass('active');
    $('#productProfileModal').modal('show');
    _loadPPTab('overview');
};

/**
 * Loads a product profile tab via AJAX (fetched once, cached thereafter).
 * @param {string} tab - Tab name: overview | transactions | stock | history
 * @returns {void}
 */
function _loadPPTab(tab) {
    var $pane = $('#ppTabContent_' + tab);
    if (_ppTabLoaded[tab]) return;

    $pane.html(
        '<div class="d-flex justify-content-center align-items-center py-5">' +
        '<div class="spinner-border text-success" role="status"></div></div>'
    );

    ajaxLoading(0);
    $.ajax({
        url      : '/products/getProductProfileTab/' + _ppCurrentUID + '/' + tab,
        type     : 'GET',
        dataType : 'json',
        success: function (res) {
            if (!res || res.Error) {
                $pane.html('<div class="alert alert-danger m-4">' + (res && res.Message ? res.Message : 'Failed to load.') + '</div>');
                return;
            }
            $pane.html(res.Html);
            _ppTabLoaded[tab] = true;
        },
        error: function () {
            $pane.html('<div class="alert alert-danger m-4">An error occurred. Please try again.</div>');
        }
    });
}

// Tab click handler
$(document).on('click', '.pp-tab-link', function () {
    var tab = $(this).data('tab');
    $('#ppTabNav .pp-tab-link').removeClass('active');
    $(this).addClass('active');
    $('#ppTabContent .pp-tab-pane').removeClass('d-block').addClass('d-none');
    $('#ppTabContent_' + tab).removeClass('d-none').addClass('d-block');
    _loadPPTab(tab);
});

// Open from product name link in the list
$(document).on('click', '.prod-profile-link', function (e) {
    e.preventDefault();
    var uid = parseInt($(this).data('uid'), 10);
    if (uid > 0) window.openProductProfile(uid);
});

// Edit button inside modal — close modal, then open edit form
$(document).on('click', '#ppBtnEdit', function () {
    var uid = $(this).data('uid');
    $('#productProfileModal').modal('hide');
    setTimeout(function () {
        $('[data-uid="' + uid + '"].editItem').first().trigger('click');
    }, 350);
});

// Transaction tab: client-side module + status filter
$(document).on('change', '#ppTxModuleFilter, #ppTxStatusFilter', function () {
    var moduleVal = $('#ppTxModuleFilter').val();
    var statusVal = $('#ppTxStatusFilter').val();
    var $rows     = $('#ppTxTable tbody tr');
    var visible   = 0;
    $rows.each(function () {
        var $tr        = $(this);
        var rowModule  = $tr.data('module') ? String($tr.data('module')) : '';
        var rowStatus  = $tr.data('status') || '';
        var matchMod   = !moduleVal || rowModule === moduleVal;
        var matchStat  = !statusVal || rowStatus === statusVal;
        if (matchMod && matchStat) {
            $tr.show();
            visible++;
        } else {
            $tr.hide();
        }
    });
    $('#ppTxVisibleCount').text(visible + ' record' + (visible !== 1 ? 's' : ''));
});

// Reset modal state when fully hidden
$('#productProfileModal').on('hidden.bs.modal', function () {
    _ppTabLoaded  = {};
    _ppCurrentUID = 0;
    $('#ppTabContent .pp-tab-pane').empty();
});
</script>