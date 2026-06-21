<?php defined('BASEPATH') or exit('No direct script access allowed');
$this->load->view('common/transactions/header'); ?>

<div class="layout-wrapper layout-horizontal basic-form-page transactionPage layout-content-navbar">
    <div class="layout-container">

        <?php $this->load->view('common/menu_view'); ?>

        <div class="layout-page">
            <div class="content-wrapper apex-content">
                <?php $this->load->view('common/apex/page_header', [
                    'pageTitle'       => $PageTitle,
                    'pageDescription' => $PageDescription ?? '',
                ]); ?>
                <div class="container-xxl flex-grow-1 container-p-y">

                    <?php
                        $cur           = htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹');
                        $dec           = (int)($JwtData->GenSettings->DecimalPoints ?? 2);
                        $defaultFilter = $DefaultFilter ?? ['DateFrom' => date('Y-m-01'), 'DateTo' => date('Y-m-t')];
                        $categories    = $Categories ?? [];
                        $orgUsers      = $OrgUsers ?? [];
                        $showUserBtn   = !empty($orgUsers) && count($orgUsers) > 1;
                    ?>

                    <!-- ── Main Card ──────────────────────────────────────── -->
                    <div class="card">

                        <!-- ── Action Row: filter buttons left, export right ── -->
                        <div class="p-2 d-flex align-items-center justify-content-between mb-0 gap-2 flex-wrap">
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <button type="button" id="tlSourceFilterBtn" class="btn btn-sm btn-outline-secondary">
                                    <i class="bx bx-transfer me-1"></i>Source
                                </button>
                                <button type="button" id="tlCategoryFilterBtn" class="btn btn-sm btn-outline-secondary">
                                    <i class="bx bx-category me-1"></i>Category
                                </button>
                                <?php if ($showUserBtn): ?>
                                <button type="button" id="tlUserFilterBtn" class="btn btn-sm btn-outline-secondary">
                                    <i class="bx bx-user me-1"></i>Updated By
                                </button>
                                <?php endif; ?>
                            </div>
                            <div class="d-flex align-items-center gap-2">
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

                        <!-- Toolbar: Items | Date Range | Movement — left; Refresh | Count — right -->
                        <div class="trans-toolbar p-2">
                            <div class="d-flex align-items-center gap-2">
                                <button type="button" id="tlItemFilterBtn" class="btn btn-sm btn-outline-secondary">
                                    <i class="bx bx-package me-1"></i>Items
                                </button>
                                <input type="text" id="tlDateRange" class="form-control form-control-sm"
                                       placeholder="Date range">
                                <select id="tlMovementFilter" class="form-select form-select-sm" style="width:140px;">
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
                                        <th style="white-space:nowrap;">Source</th>
                                        <th style="white-space:nowrap;">
                                            <span class="tl-sortable" data-sort="Category" style="cursor:pointer;">Category <i class="bx bx-sort tl-sort-icon" style="font-size:.8rem;opacity:.5;"></i></span>
                                        </th>
                                        <th style="min-width:140px;">Remarks</th>
                                        <th style="white-space:nowrap;">Date / Updated By</th>
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

<!-- ── Item Filter Box (dynamic — populated from Upstash cache via JS) ──────────── -->
<div id="tlItemFilterBox"
     class="card mp-filterbox trans-col-filterbox"
     data-trigger-id="tlItemFilterBtn"
     data-chk-class="tl-item-chk"
     style="z-index:9999;display:none;position:fixed;width:280px;">
    <div class="catg-filter-header">
        <span class="catg-filter-title"><i class="bx bx-package me-1"></i>Items</span>
        <div class="d-flex align-items-center gap-2">
            <button type="button" class="catg-filter-close-btn tcf-close-btn" title="Close">&times;</button>
        </div>
    </div>
    <div class="catg-filter-search-wrap">
        <div class="catg-search-inner">
            <input type="text" class="form-control form-control-sm tcf-search-input" placeholder="Search items...">
            <button type="button" class="catg-search-clear" title="Clear" style="display:none;">
                <i class="bx bx-x"></i>
            </button>
        </div>
    </div>
    <div class="catg-select-all-wrap">
        <input type="checkbox" class="form-check-input tcf-select-all" id="tlItemFilterBoxSelectAll">
        <label class="small fw-semibold mb-0" for="tlItemFilterBoxSelectAll">Select All</label>
    </div>
    <div class="catg-list" style="max-height:220px;overflow-y:auto;" id="tlItemList">
        <div class="text-center py-3 text-muted" style="font-size:.8rem;">Loading items…</div>
    </div>
    <div class="catg-filter-footer">
        <button type="button" class="btn btn-primary btn-sm tcf-apply-btn">
            <i class="bx bx-check me-1"></i>Apply
        </button>
        <button type="button" class="btn btn-outline-secondary btn-sm tcf-reset-btn">
            <i class="bx bx-reset me-1"></i>Reset
        </button>
    </div>
</div>

<!-- ── Source Filter Box ──────────────────────────────────────────────────────── -->
<?php $this->load->view('common/transactions/col_filter_box', [
    'ColFilterConfig' => [
        'id'         => 'tlSourceFilterBox',
        'triggerId'  => 'tlSourceFilterBtn',
        'title'      => 'Source',
        'icon'       => 'bx-transfer',
        'filterKey'  => 'ModuleUID',
        'checkClass' => 'tl-src-chk',
        'items'      => [
            ['value' => '103', 'label' => 'Invoice'],
            ['value' => '105', 'label' => 'Purchase'],
            ['value' => '106', 'label' => 'Sales Return'],
            ['value' => '107', 'label' => 'Credit Note'],
            ['value' => '108', 'label' => 'Purchase Return'],
            ['value' => '118', 'label' => 'Manual Adj.'],
        ],
    ],
]); ?>

<!-- ── Category Filter Box ───────────────────────────────────────────────────── -->
<?php $this->load->view('common/transactions/col_filter_box', [
    'ColFilterConfig' => [
        'id'         => 'tlCategoryFilterBox',
        'triggerId'  => 'tlCategoryFilterBtn',
        'title'      => 'Category',
        'icon'       => 'bx-category',
        'filterKey'  => 'CategoryUID',
        'checkClass' => 'tl-cat-chk',
        'items'      => array_map(function($cat) {
            return ['value' => (string)$cat->CategoryUID, 'label' => $cat->CategoryName];
        }, $categories),
    ],
]); ?>

<!-- ── User Filter Box ────────────────────────────────────────────────────────── -->
<?php if ($showUserBtn): ?>
<?php $this->load->view('common/partials/col_user_filter_box', [
    'ColUserFilterConfig' => [
        'id'         => 'tlUserFilterBox',
        'triggerId'  => 'tlUserFilterBtn',
        'checkClass' => 'tl-user-chk',
        'title'      => 'Updated By',
        'OrgUsers'   => $orgUsers,
    ],
]); ?>
<?php endif; ?>

<!-- ── Edit Inventory Remarks Modal ──────────────────────────────────────────── -->
<div class="modal fade" id="tlEditAdjModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-top" style="max-width:480px;">
        <div class="modal-content" style="overflow:hidden;">
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
                <div class="mb-3">
                    <label class="form-label fw-medium" style="font-size:.85rem;">Quantity</label>
                    <input type="text" class="form-control" id="tlEditAdjQtyDisplay" disabled>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-medium" style="font-size:.85rem;">Remarks</label>
                    <textarea class="form-control" id="tlEditAdjNotes" rows="2"
                              placeholder="Optional remarks" style="resize:none;"></textarea>
                </div>
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

<!-- ── Shared transaction view modal ─────────────────────────────────────────── -->
<?php $this->load->view('common/transactions/print_modals'); ?>

<style>
/* Multi-month flatpickr: month headers side-by-side */
.flatpickr-calendar.multiMonth .flatpickr-months {
    display: flex;
}
.flatpickr-calendar.multiMonth .flatpickr-months .flatpickr-month {
    flex: 1;
}
/* Widen the alt-input created by flatpickr for the date range field */
#tlDateRange + .flatpickr-input {
    width: 240px !important;
}
</style>

<script src="/js/common/pagecheckbox.js"></script>
<script src="/js/transactions/col_filter.js"></script>
<script src="/js/transactions/viewmodal.js"></script>
<script src="/js/inventory_timeline.js"></script>

<script>
var TlCurrency        = <?php echo json_encode($cur); ?>;
var TlDecimals        = <?php echo (int)$dec; ?>;
var TlDefaultDateFrom = <?php echo json_encode($defaultFilter['DateFrom']); ?>;
var TlDefaultDateTo   = <?php echo json_encode($defaultFilter['DateTo']); ?>;
</script>

<script>
var tlItemFilter = new TransColFilter({
    boxId: 'tlItemFilterBox', triggerId: 'tlItemFilterBtn',
    filterKey: 'ProductUID', activeClass: 'has-filter',
    onApply: function () {
        var vals = tlItemFilter.getState()['ProductUID'] || [];
        if (vals.length) _tlFilter['ProductUID'] = vals.map(Number);
        else delete _tlFilter['ProductUID'];
        tlLoadPage(1);
    }
});

var tlSourceFilter = new TransColFilter({
    boxId: 'tlSourceFilterBox', triggerId: 'tlSourceFilterBtn',
    filterKey: 'ModuleUID', activeClass: 'has-filter',
    onApply: function () {
        var vals = tlSourceFilter.getState()['ModuleUID'] || [];
        if (vals.length) _tlFilter['ModuleUID'] = vals.map(Number);
        else delete _tlFilter['ModuleUID'];
        tlLoadPage(1);
    }
});

var tlCategoryFilter = new TransColFilter({
    boxId: 'tlCategoryFilterBox', triggerId: 'tlCategoryFilterBtn',
    filterKey: 'CategoryUID', activeClass: 'has-filter',
    onApply: function () {
        var vals = tlCategoryFilter.getState()['CategoryUID'] || [];
        if (vals.length) _tlFilter['CategoryUID'] = vals.map(Number);
        else delete _tlFilter['CategoryUID'];
        tlLoadPage(1);
    }
});

<?php if ($showUserBtn): ?>
var tlUserFilter = new TransColFilter({
    boxId: 'tlUserFilterBox', triggerId: 'tlUserFilterBtn',
    filterKey: 'CreatedByUID', activeClass: 'has-filter',
    onApply: function () {
        var vals = tlUserFilter.getState()['CreatedByUID'] || [];
        if (vals.length) _tlFilter['CreatedByUID'] = vals;
        else delete _tlFilter['CreatedByUID'];
        tlLoadPage(1);
    }
});
<?php endif; ?>
</script>
