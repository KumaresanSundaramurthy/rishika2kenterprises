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
.vend-search-item {
    padding: 12px 16px;
    border-bottom: 1px solid #e7e9ed;
    cursor: pointer;
    transition: background-color 0.2s;
}
.vend-search-item:hover { background-color: #f8f9fa; }
.vend-search-item:last-child { border-bottom: none; }
.vend-serial {
    min-width: 26px;
    height: 26px;
    background: #f0ebff;
    color: #6f42c1;
    border-radius: 50%;
    font-size: .72rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    margin-top: 2px;
}
.vend-name  { font-size: 0.9rem;  font-weight: 600; color: #566a7f; }
.vend-meta  { font-size: 0.75rem; color: #8592a3;   margin-top: 2px; }
.vend-balance { font-size: 0.85rem; font-weight: 600; }
.vend-balance.credit { color: #dc3545; }
.vend-balance.debit  { color: #28a745; }
</style>
