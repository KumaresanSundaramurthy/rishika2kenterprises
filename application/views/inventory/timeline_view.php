<?php defined('BASEPATH') or exit('No direct script access allowed');
$this->load->view('common/transactions/header'); ?>

<div class="layout-wrapper layout-horizontal basic-form-page layout-content-navbar">
    <div class="layout-container">

        <?php $this->load->view('common/menu_view'); ?>

        <div class="layout-page">
            <div class="content-wrapper">
                <div class="container-xxl flex-grow-1 container-p-y">

                    <?php
                    $cur           = htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹');
                    $dec           = (int)($JwtData->GenSettings->DecimalPoints ?? 2);
                    $defaultFilter = $DefaultFilter ?? ['DateFrom' => date('Y').'-01-01', 'DateTo' => date('Y').'-12-31'];
                    $showUserBtn   = is_array($OrgUsers) && count($OrgUsers) > 1;
                    ?>

                    <!-- ── Page Header ────────────────────────────────────── -->
                    <div class="trans-page-header">
                        <div class="d-flex align-items-center gap-3">
                            <div class="trans-ph-icon" style="background:#e0f2fe;">
                                <i class="bx bx-history" style="color:#0284c7;font-size:1.3rem;"></i>
                            </div>
                            <div>
                                <h5 class="trans-ph-title mb-0"><?php echo htmlspecialchars($PageTitle); ?></h5>
                                <div class="text-muted" style="font-size:.76rem;"><?php echo htmlspecialchars($PageDescription); ?></div>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <!-- Export dropdown -->
                            <div class="dropdown">
                                <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bx bx-export me-1"></i>Export
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="javascript:void(0);" onclick="invExportTimeline('Print')"><i class="bx bx-printer me-1"></i>Print</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);" onclick="invExportTimeline('CSV')"><i class="bx bx-file me-1"></i>CSV</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);" onclick="invExportTimeline('Excel')"><i class="bx bxs-file-export me-1"></i>Excel</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);" onclick="invExportTimeline('Pdf')"><i class="bx bxs-file-pdf me-1"></i>PDF</a></li>
                                </ul>
                            </div>
                            <a href="/inventory" class="btn btn-sm btn-outline-secondary">
                                <i class="bx bx-package me-1"></i>Back to Inventory
                            </a>
                        </div>
                    </div>

                    <!-- ── Main Card ──────────────────────────────────────── -->
                    <div class="card">

                        <!-- Toolbar -->
                        <div class="trans-toolbar">
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <!-- Item search -->
                                <div class="ms-2" style="min-width: 350px;">
                                    <select id="tlProductSearch" class="form-select form-select-sm" style="width:100%;"></select>
                                </div>
                                <!-- Date range -->
                                <div class="d-flex align-items-center gap-1">
                                    <input type="text" id="tlDateFrom" class="form-control form-control-sm" style="width:120px;"
                                           placeholder="From date"
                                           value="<?php echo date('d-m-Y', strtotime($defaultFilter['DateFrom'])); ?>">
                                    <span class="text-muted px-1" style="font-size:1rem;">→</span>
                                    <input type="text" id="tlDateTo" class="form-control form-control-sm" style="width:120px;"
                                           placeholder="To date"
                                           value="<?php echo date('d-m-Y', strtotime($defaultFilter['DateTo'])); ?>">
                                </div>
                                <!-- Movement type filter -->
                                <select id="tlMovementFilter" class="form-select form-select-sm" style="width:130px;">
                                    <option value="">All Movements</option>
                                    <option value="IN">Stock In only</option>
                                    <option value="OUT">Stock Out only</option>
                                </select>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <a href="javascript:void(0);" class="btn btn-sm btn-outline-secondary p-1 pageRefresh" title="Refresh">
                                    <i class="bx bx-refresh fs-5"></i>
                                </a>
                                <span class="badge text-bg-light border" id="tlTotalCount"><?php echo number_format($ModAllCount); ?> records</span>
                            </div>
                        </div>

                        <!-- Table -->
                        <div class="table-responsive">
                            <table class="table trans-table table-hover mb-0" id="tlTable">
                                <thead class="r2k-thead">
                                    <tr>
                                        <th style="white-space:nowrap;">
                                            <span class="tl-sortable" data-sort="ItemName" style="cursor:pointer;">Item <i class="bx bx-sort tl-sort-icon" style="font-size:.8rem;opacity:.5;"></i></span>
                                        </th>
                                        <th style="text-align:right;white-space:nowrap;">Stock In</th>
                                        <th style="text-align:right;white-space:nowrap;">Stock Out</th>
                                        <th style="text-align:right;white-space:nowrap;">
                                            <span class="tl-sortable" data-sort="Price" style="cursor:pointer;">Price <i class="bx bx-sort tl-sort-icon" style="font-size:.8rem;opacity:.5;"></i></span>
                                        </th>
                                        <th style="white-space:nowrap;">
                                            Source
                                            <a href="javascript:void(0);" id="tlSourceFilterBtn" onclick="tlToggleSourceFilter()" title="Filter by Source" style="color:#64748b;margin-left:4px;font-size:.85rem;vertical-align:middle;">
                                                <i class="bx bx-filter-alt" id="tlSourceFilterIcon"></i>
                                            </a>
                                        </th>
                                        <th style="white-space:nowrap;">
                                            <span class="tl-sortable" data-sort="Category" style="cursor:pointer;">Category <i class="bx bx-sort tl-sort-icon" style="font-size:.8rem;opacity:.5;"></i></span>
                                            <a href="javascript:void(0);" id="tlCategoryFilterBtn" onclick="tlToggleCategoryFilter()" title="Filter by Category" style="color:#64748b;margin-left:4px;font-size:.85rem;vertical-align:middle;">
                                                <i class="bx bx-filter-alt" id="tlCatFilterIcon"></i>
                                            </a>
                                        </th>
                                        <th style="min-width:140px;">Remarks</th>
                                        <th style="white-space:nowrap;">
                                            Date / Updated By
                                            <?php if ($showUserBtn): ?>
                                            <a href="javascript:void(0);" id="tlUserFilterBtn" onclick="tlToggleUserFilter()" title="Filter by User" style="color:#64748b;margin-left:4px;font-size:.85rem;vertical-align:middle;">
                                                <i class="bx bx-filter-alt" id="tlUserFilterIcon"></i>
                                            </a>
                                            <?php endif; ?>
                                        </th>
                                        <th style="width:80px;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="tlTableBody">
                                    <?php echo $ModRowData; ?>
                                </tbody>
                            </table>
                        </div>

                        <hr class="my-0">
                        <div class="row mx-3 my-2 justify-content-between align-items-center tlPagination" id="tlPagination">
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
<div id="tlCategoryFilterBox" class="mp-filterbox" style="display:none;position:fixed;z-index:9999;width:240px;max-height:320px;flex-direction:column;">
    <div class="catg-filter-header">
        <span class="catg-filter-title"><i class="bx bx-layer me-1"></i>Category Filter</span>
        <button type="button" class="catg-filter-close-btn" onclick="tlToggleCategoryFilter()" title="Close">&times;</button>
    </div>
    <div class="catg-filter-search-wrap">
        <div class="input-group input-group-sm">
            <span class="input-group-text"><i class="bx bx-search"></i></span>
            <input type="text" id="tlCategorySearch" class="form-control" placeholder="Search categories..." oninput="tlFilterCategoryList(this.value)">
        </div>
    </div>
    <div class="catg-select-all-wrap">
        <input type="checkbox" class="form-check-input" id="tlSelectAllCategories" onchange="tlToggleAllCategories(this)">
        <label class="small fw-semibold mb-0" for="tlSelectAllCategories">Select All</label>
    </div>
    <div id="tlCategoryList" class="catg-list" style="flex:1;min-height:0;overflow-y:auto;">
        <?php if (!empty($Categories)): foreach ($Categories as $cat): ?>
        <label class="catg-list-item">
            <input class="form-check-input tl-category-checkbox" type="checkbox" value="<?php echo (int)$cat->CategoryUID; ?>">
            <span><?php echo htmlspecialchars($cat->Name); ?></span>
        </label>
        <?php endforeach; endif; ?>
    </div>
    <div class="catg-filter-footer">
        <button type="button" class="btn btn-primary" onclick="tlApplyCategoryFilter()"><i class="bx bx-check me-1"></i>Apply</button>
        <button type="button" class="btn btn-outline-secondary" onclick="tlResetCategoryFilter()"><i class="bx bx-reset me-1"></i>Reset</button>
    </div>
</div>

<!-- ── Source Filter Box ──────────────────────────────────────────────────── -->
<div id="tlSourceFilterBox" class="mp-filterbox" style="display:none;position:fixed;z-index:9999;width:200px;flex-direction:column;">
    <div class="catg-filter-header">
        <span class="catg-filter-title"><i class="bx bx-transfer me-1"></i>Source Filter</span>
        <button type="button" class="catg-filter-close-btn" onclick="tlToggleSourceFilter()" title="Close">&times;</button>
    </div>
    <div class="catg-list" style="padding:4px 0;">
        <label class="catg-list-item"><input class="form-check-input tl-source-checkbox" type="checkbox" value="103"><span>Invoice</span></label>
        <label class="catg-list-item"><input class="form-check-input tl-source-checkbox" type="checkbox" value="105"><span>Purchase</span></label>
        <label class="catg-list-item"><input class="form-check-input tl-source-checkbox" type="checkbox" value="106"><span>Sales Return</span></label>
        <label class="catg-list-item"><input class="form-check-input tl-source-checkbox" type="checkbox" value="107"><span>Credit Note</span></label>
        <label class="catg-list-item"><input class="form-check-input tl-source-checkbox" type="checkbox" value="108"><span>Purchase Return</span></label>
        <label class="catg-list-item"><input class="form-check-input tl-source-checkbox" type="checkbox" value="118"><span>Manual Adj.</span></label>
    </div>
    <div class="catg-filter-footer">
        <button type="button" class="btn btn-primary" onclick="tlApplySourceFilter()"><i class="bx bx-check me-1"></i>Apply</button>
        <button type="button" class="btn btn-outline-secondary" onclick="tlResetSourceFilter()"><i class="bx bx-reset me-1"></i>Reset</button>
    </div>
</div>

<!-- ── User Filter Box (common partial, server-side rendered) ─────────────── -->
<?php if ($showUserBtn): ?>
<?php $this->load->view('common/partials/_user_filter_box', [
    'OrgUsers'   => $OrgUsers,
    'BoxId'      => 'tlUserFilterBox',
    'CheckClass' => 'tl-user-checkbox',
    'ApplyFn'    => 'tlApplyUserFilter',
    'ResetFn'    => 'tlResetUserFilter',
]); ?>
<?php endif; ?>

<!-- ── Edit Inventory Remarks Modal ───────────────────────────────────────── -->
<div class="modal fade" id="tlEditAdjModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-top" style="max-width:480px;">
        <div class="modal-content" style="overflow:hidden;">
            <!-- vtm-banner header — matches viewTransModal theme -->
            <div class="vtm-banner" style="--vtm-color:#0284c7;--vtm-bg:#e0f2fe;--vtm-icon-bg:rgba(2,132,199,.13);">
                <div class="vtm-banner-inner">
                    <div class="vtm-banner-left">
                        <div class="vtm-banner-icon">
                            <i class="bx bx-edit-alt"></i>
                        </div>
                        <div>
                            <div class="vtm-doc-number">Edit Inventory</div>
                            <div class="vtm-doc-meta" id="tlEditAdjProductName"></div>
                        </div>
                    </div>
                    <div class="vtm-banner-right">
                        <button type="button" class="btn btn-primary btn-sm" id="tlEditAdjSaveBtn">
                            <i class="bx bx-save me-1"></i>Save Changes
                        </button>
                        <button type="button" class="vtm-close-btn" data-bs-dismiss="modal" aria-label="Close">
                            <i class="bx bx-x"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="modal-body px-4 pt-3 pb-4">
                <input type="hidden" id="tlEditLedgerUID">
                <!-- Quantity (display only) -->
                <div class="mb-3">
                    <label class="form-label fw-medium" style="font-size:.85rem;">Quantity</label>
                    <input type="text" class="form-control" id="tlEditAdjQtyDisplay" disabled>
                </div>
                <!-- Remarks (editable) -->
                <div class="mb-3">
                    <label class="form-label fw-medium" style="font-size:.85rem;">Remarks</label>
                    <textarea class="form-control" id="tlEditAdjNotes" rows="2"
                              placeholder="Optional remarks" style="resize:none;"></textarea>
                </div>
                <!-- Record Time (display only) -->
                <div class="mb-1">
                    <label class="form-label fw-medium" style="font-size:.85rem;">Record Time</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="tlEditAdjDateDisplay" disabled>
                        <span class="input-group-text"><i class="bx bx-calendar"></i></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ── Shared transaction view modal ─────────────────────────────────────── -->
<?php $this->load->view('common/transactions/print_modals'); ?>

<script src="/js/common/pagecheckbox.js"></script>
<script src="/js/inventory.js"></script>
<script src="/js/transactions/viewmodal.js"></script>
<script src="/js/inventory_timeline.js"></script>

<script>
var TlCurrency        = <?php echo json_encode($cur); ?>;
var TlDecimals        = <?php echo (int)$dec; ?>;
var TlDefaultDateFrom = <?php echo json_encode($defaultFilter['DateFrom']); ?>;
var TlDefaultDateTo   = <?php echo json_encode($defaultFilter['DateTo']); ?>;
var TlShowUserFilter  = <?php echo $showUserBtn ? 'true' : 'false'; ?>;
</script>
