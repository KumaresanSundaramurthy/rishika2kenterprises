<?php defined('BASEPATH') or exit('No direct script access allowed');
$this->load->view('common/transactions/header'); ?>

<div class="layout-wrapper layout-horizontal layout-content-navbar">
    <div class="layout-container">

        <?php $this->load->view('common/menu_view'); ?>

        <div class="layout-page">
            <div class="content-wrapper apex-content">
                <?php $this->load->view('common/apex/page_header', [
                    'pageTitle'       => $PageTitle       ?? 'Inventory',
                    'pageDescription' => $PageDescription ?? 'Stock levels · Adjustments · History',
                ]); ?>
                <?php
                $stats    = $Stats ?? null;
                $cur      = htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹');
                $dec      = (int)($JwtData->GenSettings->DecimalPoints ?? 2);
                $showSno  = $JwtData->GenSettings->SerialNoDisplay == 1;

                $posCount   = (int)($stats->positiveCount  ?? 0);
                $posQty     = (float)($stats->positiveQty   ?? 0);
                $lowCount   = (int)($stats->lowStockCount  ?? 0);
                $lowQty     = (float)($stats->lowStockQty   ?? 0);
                $saleVal    = (float)($stats->saleValue     ?? 0);
                $purchVal   = (float)($stats->purchaseValue ?? 0);

                $categories = $Categories ?? [];

                function invFmt($val, $sym, $dec) {
                    return $sym . ' ' . number_format((float)$val, $dec, '.', ',');
                }
                ?>

                <!-- ── Stats Strip (outside container; visibility via StatsDefaultOpen → apex-stats-strip) ── -->
                <div class="apex-stats-strip" id="invStatsSection">
                    <div class="apex-stat-item" style="cursor:default;pointer-events:none;--stat-color:#16a34a">
                        <div class="apex-stat-icon" style="background:#dcfce7"><i class="bx bx-check-circle" style="color:#16a34a"></i></div>
                        <div class="apex-stat-body">
                            <div class="apex-stat-label">Positive Stock</div>
                            <div class="apex-stat-bottom">
                                <span class="apex-stat-count" id="statPositiveCount"><?php echo number_format($posCount); ?> Items</span>
                                <span class="apex-stat-amount" id="statPositiveQty"><?php echo number_format($posQty, $dec); ?> Qty</span>
                            </div>
                        </div>
                    </div>
                    <div class="apex-stat-item" style="cursor:default;pointer-events:none;--stat-color:#d97706">
                        <div class="apex-stat-icon" style="background:#fef3c7"><i class="bx bx-error-circle" style="color:#d97706"></i></div>
                        <div class="apex-stat-body">
                            <div class="apex-stat-label">Low / Out of Stock</div>
                            <div class="apex-stat-bottom">
                                <span class="apex-stat-count" id="statLowCount"><?php echo number_format($lowCount); ?> Items</span>
                                <span class="apex-stat-amount" id="statLowQty"><?php echo number_format($lowQty, $dec); ?> Qty</span>
                            </div>
                        </div>
                    </div>
                    <div class="apex-stat-item" style="cursor:default;pointer-events:none;--stat-color:#1d4ed8">
                        <div class="apex-stat-icon" style="background:#dbeafe"><i class="bx bx-trending-up" style="color:#1d4ed8"></i></div>
                        <div class="apex-stat-body">
                            <div class="apex-stat-label">Stock Value (Sale)</div>
                            <div class="apex-stat-bottom">
                                <span class="apex-stat-count" id="statSaleValue"><?php echo invFmt($saleVal, $cur, $dec); ?></span>
                                <span class="apex-stat-amount">at selling price</span>
                            </div>
                        </div>
                    </div>
                    <div class="apex-stat-item" style="cursor:default;pointer-events:none;--stat-color:#be185d">
                        <div class="apex-stat-icon" style="background:#fce7f3"><i class="bx bx-wallet" style="color:#be185d"></i></div>
                        <div class="apex-stat-body">
                            <div class="apex-stat-label">Stock Value (Purchase)</div>
                            <div class="apex-stat-bottom">
                                <span class="apex-stat-count" id="statPurchaseValue"><?php echo invFmt($purchVal, $cur, $dec); ?></span>
                                <span class="apex-stat-amount">at purchase price</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="container-xxl flex-grow-1 container-p-y">

                    <!-- ── Main Card ──────────────────────────────────────── -->
                    <div class="card">

                        <!-- Tabs Row -->
                        <div class="apex-tabs-row">
                            <ul class="nav trans-status-tabs gap-1" id="invStatusTabs" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active inv-status-tab" data-status="" href="javascript:void(0);">
                                        All <span class="inv-tab-count trans-tab-count ms-1"><?php echo $ModAllCount; ?></span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link inv-status-tab" data-status="Positive" href="javascript:void(0);">
                                        <i class="bx bx-check-circle me-1 text-success"></i>In Stock <span class="inv-tab-count trans-tab-count ms-1 d-none"></span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link inv-status-tab" data-status="LowStock" href="javascript:void(0);">
                                        <i class="bx bx-error-circle me-1 text-warning"></i>Low Stock <span class="inv-tab-count trans-tab-count ms-1 d-none"></span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link inv-status-tab" data-status="OutOfStock" href="javascript:void(0);">
                                        <i class="bx bx-x-circle me-1 text-danger"></i>Out of Stock <span class="inv-tab-count trans-tab-count ms-1 d-none"></span>
                                    </a>
                                </li>
                            </ul>
                        </div>

                        <!-- Filter Row -->
                        <div class="apex-filter-row">
                            <div class="r2k-search-wrap">
                                <i class="bx bx-search r2k-si"></i>
                                <input type="text" id="invSearchInput" placeholder="Item name or part #...">
                            </div>
                            <a href="javascript:void(0);" id="invItemFilterBtn" class="apex-filter-btn" onclick="invToggleItemFilter(); event.stopPropagation();" title="Filter by Item">
                                <i class="bx bx-package me-1" id="invItemFilterIcon"></i>Item
                            </a>
                            <a href="javascript:void(0);" id="invCategoryFilterBtn" class="apex-filter-btn" onclick="invToggleCategoryFilter(); event.stopPropagation();" title="Filter by Category">
                                <i class="bx bx-category me-1" id="invCatFilterIcon"></i>Category
                            </a>
                            <a href="javascript:void(0);" id="invStatusFilterBtn" class="apex-filter-btn" title="Filter by Status">
                                <i class="bx bx-transfer me-1" id="invStatusFilterIcon"></i>Status
                            </a>
                            <?php if (!empty($ShowUserFilter)): ?>
                            <a href="javascript:void(0);" id="invUpdatedByFilterBtn" class="apex-filter-btn" title="Filter by Updated By">
                                <i class="bx bx-user me-1" id="invUpdatedByFilterIcon"></i>Updated By
                            </a>
                            <?php endif; ?>
                            <div class="apex-filter-spacer"></div>
                            <a href="javascript:void(0);" class="apex-icon-btn pageRefresh" title="Refresh"><i class="bx bx-refresh"></i></a>
                            <div class="dropdown">
                                <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bx bx-export me-1"></i>Export
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="javascript:void(0);" onclick="invExport('Print')"><i class="bx bx-printer me-1"></i>Print</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);" onclick="invExport('CSV')"><i class="bx bx-file me-1"></i>CSV</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);" onclick="invExport('Excel')"><i class="bx bxs-file-export me-1"></i>Excel</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);" onclick="invExport('Pdf')"><i class="bx bxs-file-pdf me-1"></i>PDF</a></li>
                                </ul>
                            </div>
                            <a href="/inventory/timeline" class="btn btn-sm btn-outline-primary"><i class="bx bx-history me-1"></i>Timeline</a>
                            <button type="button" class="btn btn-sm btn-outline-danger" id="invBulkStockOutBtn" style="display:none;"><i class="bx bx-minus-circle me-1"></i>Bulk Stock Out</button>
                            <button type="button" class="btn btn-sm btn-outline-success" id="invBulkStockInBtn" style="display:none;"><i class="bx bx-plus-circle me-1"></i>Bulk Stock In</button>
                        </div>

                        <!-- Table -->
                        <div class="table-responsive">
                            <table class="table trans-table table-hover mb-0" id="invTable">
                                <thead class="r2k-thead">
                                    <tr>
                                        <th class="<?php echo $showSno ? '' : 'd-none'; ?> table-serialno" style="width:44px">S.No</th>
                                        <th class="inv-sort-th" data-sort="ItemName" style="cursor:pointer;white-space:nowrap;">Item <i class="bx bx-sort inv-sort-icon ms-1" data-col="ItemName"></i></th>
                                        <th class="inv-sort-th" data-sort="CategoryName" style="cursor:pointer;white-space:nowrap;">Category <i class="bx bx-sort inv-sort-icon ms-1" data-col="CategoryName"></i></th>
                                        <th class="inv-sort-th" data-sort="Qty" style="cursor:pointer;white-space:nowrap;">Qty <i class="bx bx-sort inv-sort-icon ms-1" data-col="Qty"></i></th>
                                        <th style="white-space:nowrap;">Status</th>
                                        <th class="inv-sort-th" data-sort="PurchasePrice" style="cursor:pointer;white-space:nowrap;">Purchase Price <i class="bx bx-sort inv-sort-icon ms-1" data-col="PurchasePrice"></i></th>
                                        <th class="inv-sort-th" data-sort="SellingPrice" style="cursor:pointer;white-space:nowrap;">Sale Price <i class="bx bx-sort inv-sort-icon ms-1" data-col="SellingPrice"></i></th>
                                        <th style="white-space:nowrap;">Last Updated</th>
                                        <th style="width:130px;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="invTableBody">
                                    <?php echo $ModRowData; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <hr class="my-0">
                        <div class="row mx-3 my-2 justify-content-between align-items-center invPagination" id="invPagination">
                            <?php echo $ModPagination; ?>
                        </div>

                    </div><!-- /card -->

                </div>
            </div>
            <?php $this->load->view('common/footer'); ?>
        </div>

    </div>
</div>

<!-- ── Category Filter Box ────────────────────────────────────────────────── -->
<div id="invCategoryFilterBox" class="mp-filterbox" style="display:none;position:fixed;z-index:9999;width:250px;max-height:340px;flex-direction:column;"></div>

<!-- ── Item Filter Box ─────────────────────────────────────────────────────── -->
<div id="invItemFilterBox" class="mp-filterbox" style="display:none;position:fixed;z-index:9999;width:260px;max-height:360px;flex-direction:column;"></div>

<!-- ── Status Filter Box ──────────────────────────────────────────────────── -->
<?php $this->load->view('common/transactions/col_filter_box', [
    'ColFilterConfig' => [
        'id'         => 'invStatusFilterBox',
        'triggerId'  => 'invStatusFilterBtn',
        'title'      => 'Stock Status',
        'icon'       => 'bx-transfer',
        'filterKey'  => 'StockStatus',
        'checkClass' => 'inv-status-chk',
        'items'      => [
            ['value' => 'Positive',   'label' => 'In Stock'],
            ['value' => 'LowStock',   'label' => 'Low Stock'],
            ['value' => 'OutOfStock', 'label' => 'Out of Stock'],
        ],
    ],
]); ?>

<!-- ── Updated By Filter Box ──────────────────────────────────────────────── -->
<?php if (!empty($ShowUserFilter)): ?>
<?php $this->load->view('common/partials/col_user_filter_box', [
    'ColUserFilterConfig' => [
        'id'         => 'invUpdatedByFilterBox',
        'triggerId'  => 'invUpdatedByFilterBtn',
        'checkClass' => 'inv-updatedby-checkbox',
        'title'      => 'Updated By',
        'OrgUsers'   => $OrgUsers ?? [],
    ]
]); ?>
<?php endif; ?>

<!-- ── Modals ─────────────────────────────────────────────────────────────── -->
<?php
$this->load->view('inventory/partials/_stock_in_modal');
$this->load->view('inventory/partials/_stock_out_modal');
$this->load->view('inventory/partials/_timeline_modal');
?>

<!-- ── Description Modal ─────────────────────────────────────────────────── -->
<div class="modal fade" id="invDescModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:420px;">
        <div class="modal-content">
            <div class="modal-header bg-white border-bottom d-flex align-items-center justify-content-between px-3 py-2">
                <div class="d-flex align-items-center gap-2">
                    <div class="modal-doc-icon" style="background:#fef9c3;">
                        <i class="bx bx-info-circle modal-doc-icon-inner" style="color:#ca8a04;"></i>
                    </div>
                    <h6 class="modal-title mb-0" id="invDescModalTitle" style="font-size:.88rem;font-weight:600;"></h6>
                </div>
                <button type="button" class="btn-close btn-sm" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body py-3 px-3" id="invDescModalBody" style="font-size:.84rem;line-height:1.65;color:#374151;max-height:340px;overflow-y:auto;"></div>
        </div>
    </div>
</div>

<script src="/js/common/pagecheckbox.js"></script>
<script src="/js/common/productappend.js"></script>
<script src="/js/common/item_filter.js"></script>
<script src="/js/transactions/col_filter.js"></script>
<script src="/js/inventory.js"></script>

<script>
const InvCurrency   = <?php echo json_encode($cur); ?>;
const InvDecimals   = <?php echo (int)$dec; ?>;
const InvShowSerial = <?php echo $showSno ? 'true' : 'false'; ?>;

// ── Status filter (TransColFilter) ───────────────────────────────────────────
var invStatusFilter = new TransColFilter({
    boxId       : 'invStatusFilterBox',
    triggerId   : 'invStatusFilterBtn',
    filterKey   : 'StockStatus',
    activeClass : 'has-filter',
    onApply     : function () {
        var vals = invStatusFilter.getState()['StockStatus'] || [];
        if (vals.length === 1) {
            _invFilter['StockStatus'] = vals[0];
        } else {
            delete _invFilter['StockStatus'];
        }
        invLoadPage(1);
    }
});

// Close old-style item/category boxes when status filter opens
$('#invStatusFilterBtn').on('click', function () {
    $('#invCategoryFilterBox, #invItemFilterBox').hide();
});

<?php if (!empty($ShowUserFilter)): ?>
// Close custom boxes when Updated By filter opens (TransColFilter handles invStatusFilterBox automatically)
$('#invUpdatedByFilterBtn').on('click', function () {
    $('#invCategoryFilterBox, #invItemFilterBox').hide();
});

// ── Updated By filter (TransColFilter) ───────────────────────────────────────
var invUpdatedByFilter = new TransColFilter({
    boxId    : 'invUpdatedByFilterBox',
    triggerId: 'invUpdatedByFilterBtn',
    filterKey: 'UpdatedBy',
    onApply  : function() {
        var state = invUpdatedByFilter.getState();
        if (state.UpdatedBy && state.UpdatedBy.length) {
            _invFilter.UpdatedBy = state.UpdatedBy;
        } else {
            delete _invFilter.UpdatedBy;
        }
        invLoadPage(1);
    }
});
<?php endif; ?>
</script>
