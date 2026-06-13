<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<!-- Vendor Search Modal -->
<div class="modal fade" id="vendorSearchModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable" style="height:90vh;max-height:90vh;">
        <div class="modal-content" style="height:100%;">

            <!-- Header -->
            <div class="vtm-banner flex-shrink-0" style="--vtm-color:#6f42c1;--vtm-bg:#f0ebff;--vtm-icon-bg:rgba(111,66,193,.13);">
                <div class="vtm-banner-inner">
                    <div class="vtm-banner-left">
                        <div class="vtm-banner-icon">
                            <i class="bx bx-search" style="font-size:1.7rem;color:var(--vtm-color);display:block;"></i>
                        </div>
                        <div>
                            <div style="font-size:.95rem;font-weight:700;color:var(--vtm-color);">Search Vendors</div>
                            <div style="font-size:.75rem;color:#6c757d;margin-top:2px;">Select a vendor to apply</div>
                        </div>
                    </div>
                    <div class="vtm-banner-right">
                        <div class="input-group input-group-sm" style="width:240px;">
                            <span class="input-group-text"><i class="bx bx-search"></i></span>
                            <input type="text" id="vendSearchInput" class="form-control" placeholder="Name, mobile, area..." />
                            <button type="button" id="vendSearchClear" class="btn btn-outline-secondary d-none" tabindex="-1"><i class="bx bx-x"></i></button>
                        </div>
                        <button type="button" id="btnCreateVendorFromSearch"
                                class="btn btn-sm btn-outline-primary"
                                style="white-space:nowrap;"
                                title="Create new vendor">
                            <i class="bx bx-plus me-1"></i>Create Vendor
                        </button>
                        <button type="button" class="vtm-close-btn" data-bs-dismiss="modal" aria-label="Close">
                            <i class="bx bx-x"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="modal-body p-0" style="flex:1;overflow-y:auto;">
                <div id="vendSearchResults">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <div class="border-top px-3 py-2 d-flex align-items-center justify-content-between flex-shrink-0 d-none" id="vendSearchPaginationWrap" style="background:var(--vtm-bg,#f0ebff);">
                <small style="color:var(--vtm-color,#6f42c1);font-weight:600;" id="vendSearchPageInfo"></small>
                <nav><ul class="pagination pagination-sm mb-0" id="vendSearchPagination"></ul></nav>
            </div>
        </div>
    </div>
</div>

<style>
/* ── Vendor search table ─────────────────────────────────────────────────── */
.vend-search-table {
    width: 100%;
    border-collapse: collapse;
    font-size: .85rem;
}
.vend-search-table thead tr {
    background: #f5f2ff;
    border-bottom: 2px solid #e0d8ff;
    position: sticky;
    top: 0;
    z-index: 1;
}
.vend-search-table thead th {
    padding: 9px 12px;
    font-size: .72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .04em;
    color: #6f42c1;
    white-space: nowrap;
}
.vend-search-table thead th.col-balance { text-align: right; }

.vend-search-item {
    border-bottom: 1px solid #eeebf7;
    cursor: pointer;
    transition: background .15s;
}
.vend-search-item:last-child { border-bottom: none; }
.vend-search-item:hover { background: #f9f7ff; }
.vend-search-item td { padding: 10px 12px; vertical-align: middle; }

.col-serial  { width: 42px; }
.col-mobile  { width: 130px; }
.col-balance { width: 120px; text-align: right; }

.vend-serial {
    width: 26px; height: 26px;
    background: #f0ebff; color: #6f42c1;
    border-radius: 50%;
    font-size: .7rem; font-weight: 700;
    display: flex; align-items: center; justify-content: center;
}
.vend-name    { font-size: .88rem; font-weight: 600; color: #566a7f; }
.vend-meta    { font-size: .75rem; color: #8592a3; margin-top: 2px; }
.vend-sep     { color: #c9c0e8; margin: 0 4px; }
.vend-balance { font-size: .85rem; font-weight: 600; }
.vend-balance.credit { color: #dc3545; }
.vend-balance.debit  { color: #28a745; }
</style>
