<?php defined('BASEPATH') or exit('No direct script access allowed');
$this->load->view('common/transactions/header'); ?>

<div class="layout-wrapper layout-horizontal layout-content-navbar">
    <div class="layout-container">

        <?php $this->load->view('common/menu_view'); ?>

        <div class="layout-page">
            <div class="content-wrapper apex-content">
                <?php $this->load->view('common/apex/page_header', [
                    'pageIcon'        => 'bx-package',
                    'pageIconBg'      => '#e0f2fe',
                    'pageIconColor'   => '#0284c7',
                    'pageTitle'       => $PageTitle       ?? 'Inventory',
                    'pageDescription' => $PageDescription ?? 'Stock levels · Adjustments · History',
                ]); ?>
                <div class="container-xxl flex-grow-1 container-p-y">

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

                    <div class="d-flex justify-content-end mb-3 gap-2">
                        <!-- Export dropdown -->
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
                        <a href="/inventory/timeline" class="btn btn-sm btn-outline-primary me-1">
                            <i class="bx bx-history me-1"></i>Timeline
                        </a>
                        <button type="button" class="btn btn-sm btn-outline-danger" id="invBulkStockOutBtn" style="display:none;">
                            <i class="bx bx-minus-circle me-1"></i>Bulk Stock Out
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-success" id="invBulkStockInBtn" style="display:none;">
                            <i class="bx bx-plus-circle me-1"></i>Bulk Stock In
                        </button>
                    </div>

                    <!-- ── Stat Cards ─────────────────────────────────────── -->
                    <div class="trans-stats-section">
                        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;">

                            <div class="trans-stat-card inv-stat-positive" id="statPositive" style="cursor:default;">
                                <div class="tsc-icon-wrap" style="background:#dcfce7;color:#16a34a;">
                                    <i class="bx bx-check-circle"></i>
                                </div>
                                <div class="tsc-body">
                                    <div class="trans-stat-label">Positive Stock</div>
                                    <div class="trans-stat-count" id="statPositiveCount"><?php echo number_format($posCount); ?> Items</div>
                                    <div class="trans-stat-amount" id="statPositiveQty"><?php echo number_format($posQty, $dec); ?> Qty</div>
                                </div>
                            </div>

                            <div class="trans-stat-card inv-stat-low" id="statLow" style="cursor:default;">
                                <div class="tsc-icon-wrap" style="background:#fef3c7;color:#d97706;">
                                    <i class="bx bx-error-circle"></i>
                                </div>
                                <div class="tsc-body">
                                    <div class="trans-stat-label">Low / Out of Stock</div>
                                    <div class="trans-stat-count" id="statLowCount"><?php echo number_format($lowCount); ?> Items</div>
                                    <div class="trans-stat-amount" id="statLowQty"><?php echo number_format($lowQty, $dec); ?> Qty</div>
                                </div>
                            </div>

                            <div class="trans-stat-card" style="cursor:default;">
                                <div class="tsc-icon-wrap" style="background:#dbeafe;color:#1d4ed8;">
                                    <i class="bx bx-trending-up"></i>
                                </div>
                                <div class="tsc-body">
                                    <div class="trans-stat-label">Stock Value (Sale)</div>
                                    <div class="trans-stat-count" id="statSaleValue"><?php echo invFmt($saleVal, $cur, $dec); ?></div>
                                    <div class="trans-stat-amount">at selling price</div>
                                </div>
                            </div>

                            <div class="trans-stat-card" style="cursor:default;">
                                <div class="tsc-icon-wrap" style="background:#fce7f3;color:#be185d;">
                                    <i class="bx bx-wallet"></i>
                                </div>
                                <div class="tsc-body">
                                    <div class="trans-stat-label">Stock Value (Purchase)</div>
                                    <div class="trans-stat-count" id="statPurchaseValue"><?php echo invFmt($purchVal, $cur, $dec); ?></div>
                                    <div class="trans-stat-amount">at purchase price</div>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- ── Main Card ──────────────────────────────────────── -->
                    <div class="card">

                        <!-- Toolbar -->
                        <div class="trans-toolbar">
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

                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <a href="javascript:void(0);" class="btn btn-sm btn-outline-secondary p-1 pageRefresh" title="Refresh">
                                    <i class="bx bx-refresh fs-5"></i>
                                </a>
                                <!-- Search -->
                                <div class="input-group input-group-sm" style="width:210px;">
                                    <span class="input-group-text bg-transparent border-end-0">
                                        <i class="bx bx-search text-muted"></i>
                                    </span>
                                    <input type="text" class="form-control border-start-0" id="invSearchInput" placeholder="Item name or part #...">
                                </div>
                            </div>
                        </div>

                        <!-- Table -->
                        <div class="table-responsive">
                            <table class="table trans-table table-hover mb-0" id="invTable">
                                <thead class="r2k-thead">
                                    <tr>
                                        <th class="<?php echo $showSno ? '' : 'd-none'; ?> table-serialno" style="width:44px">S.No</th>
                                        <th style="white-space:nowrap;">
                                            <span class="inv-sort-th" data-sort="ItemName" style="cursor:pointer;">Item <i class="bx bx-sort inv-sort-icon ms-1" data-col="ItemName"></i></span>
                                            <a href="javascript:void(0);" id="invProdTypeFilterBtn" onclick="invToggleProdTypeFilter()" title="Filter by Item Type" style="color:#64748b;margin-left:4px;font-size:.8rem;vertical-align:middle;">
                                                <i class="bx bx-filter-alt" id="invProdTypeFilterIcon"></i>
                                            </a>
                                        </th>
                                        <th style="white-space:nowrap;">
                                            <span class="inv-sort-th" data-sort="CategoryName" style="cursor:pointer;">Category <i class="bx bx-sort inv-sort-icon ms-1" data-col="CategoryName"></i></span>
                                            <a href="javascript:void(0);" id="invCategoryFilterBtn" onclick="invToggleCategoryFilter()" title="Filter by Category" style="color:#64748b;margin-left:4px;font-size:.8rem;vertical-align:middle;">
                                                <i class="bx bx-filter-alt" id="invCatFilterIcon"></i>
                                            </a>
                                        </th>
                                        <th class="inv-sort-th" data-sort="Qty" style="cursor:pointer;white-space:nowrap;">Qty <i class="bx bx-sort inv-sort-icon ms-1" data-col="Qty"></i></th>
                                        <th style="white-space:nowrap;">
                                            Status
                                            <a href="javascript:void(0);" id="invStatusFilterBtn" onclick="invToggleStatusFilter()" title="Filter by Status" style="color:#64748b;margin-left:4px;font-size:.8rem;vertical-align:middle;">
                                                <i class="bx bx-filter-alt" id="invStatusFilterIcon"></i>
                                            </a>
                                        </th>
                                        <th class="inv-sort-th" data-sort="PurchasePrice" style="cursor:pointer;white-space:nowrap;">Purchase Price <i class="bx bx-sort inv-sort-icon ms-1" data-col="PurchasePrice"></i></th>
                                        <th class="inv-sort-th" data-sort="SellingPrice" style="cursor:pointer;white-space:nowrap;">Sale Price <i class="bx bx-sort inv-sort-icon ms-1" data-col="SellingPrice"></i></th>
                                        <th style="white-space:nowrap;">
                                            Last Updated
                                            <a href="javascript:void(0);" id="invUpdatedByFilterBtn" onclick="invToggleUpdatedByFilter()" title="Filter by User" style="color:#64748b;margin-left:4px;font-size:.8rem;vertical-align:middle;">
                                                <i class="bx bx-filter-alt" id="invUpdatedByFilterIcon"></i>
                                            </a>
                                        </th>
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

<!-- ── Item Type Filter Box ───────────────────────────────────────────────── -->
<div id="invProdTypeFilterBox" class="mp-filterbox" style="display:none;position:fixed;z-index:9999;width:190px;flex-direction:column;">
    <div class="catg-filter-header">
        <span class="catg-filter-title"><i class="bx bx-category me-1"></i>Item Type</span>
        <button type="button" class="catg-filter-close-btn" onclick="invToggleProdTypeFilter()" title="Close">&times;</button>
    </div>
    <div class="catg-list" style="padding:4px 0;">
        <label class="catg-list-item">
            <input class="form-check-input inv-prodtype-checkbox" type="checkbox" value="Product">
            <span><span class="badge bg-label-primary" style="font-size:.7rem;">Product</span></span>
        </label>
        <label class="catg-list-item">
            <input class="form-check-input inv-prodtype-checkbox" type="checkbox" value="Service">
            <span><span class="badge bg-label-info" style="font-size:.7rem;">Service</span></span>
        </label>
    </div>
    <div class="catg-filter-footer">
        <button type="button" class="btn btn-primary" onclick="invApplyProdTypeFilter()"><i class="bx bx-check me-1"></i>Apply</button>
        <button type="button" class="btn btn-outline-secondary" onclick="invResetProdTypeFilter()"><i class="bx bx-reset me-1"></i>Reset</button>
    </div>
</div>

<!-- ── Status Filter Box ──────────────────────────────────────────────────── -->
<div id="invStatusFilterBox" class="mp-filterbox" style="display:none;position:fixed;z-index:9999;width:210px;flex-direction:column;">
    <div class="catg-filter-header">
        <span class="catg-filter-title"><i class="bx bx-signal-5 me-1"></i>Status Filter</span>
        <button type="button" class="catg-filter-close-btn" onclick="invToggleStatusFilter()" title="Close">&times;</button>
    </div>
    <div class="catg-list" style="flex:1;min-height:0;overflow-y:auto;padding:4px 0;">
        <label class="catg-list-item">
            <input class="form-check-input inv-status-radio" type="radio" name="invStatusRadio" value="">
            <span>All</span>
        </label>
        <label class="catg-list-item">
            <input class="form-check-input inv-status-radio" type="radio" name="invStatusRadio" value="Positive">
            <span>In Stock</span>
        </label>
        <label class="catg-list-item">
            <input class="form-check-input inv-status-radio" type="radio" name="invStatusRadio" value="LowStock">
            <span>Low Stock</span>
        </label>
        <label class="catg-list-item">
            <input class="form-check-input inv-status-radio" type="radio" name="invStatusRadio" value="OutOfStock">
            <span>Out of Stock</span>
        </label>
    </div>
    <div class="catg-filter-footer">
        <button type="button" class="btn btn-primary" onclick="invApplyStatusFilter()"><i class="bx bx-check me-1"></i>Apply</button>
        <button type="button" class="btn btn-outline-secondary" onclick="invResetStatusFilter()"><i class="bx bx-reset me-1"></i>Reset</button>
    </div>
</div>

<!-- ── Updated By Filter Box ──────────────────────────────────────────────── -->
<div id="invUpdatedByFilterBox" class="mp-filterbox" style="display:none;position:fixed;z-index:9999;width:230px;max-height:320px;flex-direction:column;">
    <div class="catg-filter-header">
        <span class="catg-filter-title"><i class="bx bx-user me-1"></i>Updated By</span>
        <button type="button" class="catg-filter-close-btn" onclick="invToggleUpdatedByFilter()" title="Close">&times;</button>
    </div>
    <div id="invUpdatedByList" class="catg-list" style="flex:1;min-height:0;overflow-y:auto;">
        <div class="text-muted small text-center py-2">Loading...</div>
    </div>
    <div class="catg-filter-footer">
        <button type="button" class="btn btn-primary" onclick="invApplyUpdatedByFilter()"><i class="bx bx-check me-1"></i>Apply</button>
        <button type="button" class="btn btn-outline-secondary" onclick="invResetUpdatedByFilter()"><i class="bx bx-reset me-1"></i>Reset</button>
    </div>
</div>

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
<script src="/js/inventory.js"></script>

<script>
const InvCurrency   = <?php echo json_encode($cur); ?>;
const InvDecimals   = <?php echo (int)$dec; ?>;
const InvShowSerial = <?php echo $showSno ? 'true' : 'false'; ?>;
</script>
