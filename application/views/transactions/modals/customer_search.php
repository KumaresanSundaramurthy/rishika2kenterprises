<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<!-- Customer Search Modal -->
<div class="modal fade" id="customerSearchModal" tabindex="-1" aria-hidden="true"<?php if (!empty($hideCreate)): ?> data-hide-create="1"<?php endif; ?>>
    <div class="modal-dialog modal-lg modal-dialog-scrollable" style="height:90vh;max-height:90vh;">
        <div class="modal-content" style="height:100%;">

            <!-- Header — vtm-banner style -->
            <div class="vtm-banner flex-shrink-0" style="--vtm-color:#696cff;--vtm-bg:#f0efff;--vtm-icon-bg:rgba(105,108,255,.13);">
                <div class="vtm-banner-inner">
                    <div class="vtm-banner-left">
                        <div class="vtm-banner-icon">
                            <i class="bx bx-search" style="font-size:1.7rem;color:var(--vtm-color);display:block;"></i>
                        </div>
                        <div>
                            <div style="font-size:.95rem;font-weight:700;color:var(--vtm-color);">Search Customers</div>
                            <div style="font-size:.75rem;color:#6c757d;margin-top:2px;">Select a customer to apply</div>
                        </div>
                    </div>
                    <div class="vtm-banner-right">
                        <div class="input-group input-group-sm" style="width:240px;">
                            <span class="input-group-text"><i class="bx bx-search"></i></span>
                            <input type="text" id="custSearchInput" class="form-control" placeholder="Name, mobile, area..." />
                            <button type="button" id="custSearchClear" class="btn btn-outline-secondary d-none" tabindex="-1"><i class="bx bx-x"></i></button>
                        </div>
                        <button type="button" id="btnCreateCustomerFromSearch"
                                class="btn btn-sm btn-outline-primary"
                                style="white-space:nowrap;"
                                title="Create new customer">
                            <i class="bx bx-plus me-1"></i>Create Customer
                        </button>
                        <button type="button" class="vtm-close-btn" data-bs-dismiss="modal" aria-label="Close">
                            <i class="bx bx-x"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="modal-body p-0" style="flex:1;overflow-y:auto;">
                <!-- Customer List -->
                <div id="custSearchResults">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pagination — pinned at bottom -->
            <div class="border-top px-3 py-2 d-flex align-items-center justify-content-between flex-shrink-0 d-none" id="custSearchPaginationWrap" style="background:var(--vtm-bg,#f0efff);">
                <small style="color:var(--vtm-color,#696cff);font-weight:600;" id="custSearchPageInfo"></small>
                <nav><ul class="pagination pagination-sm mb-0" id="custSearchPagination"></ul></nav>
            </div>
        </div>
    </div>
</div>

<style>
.cust-search-item {
    padding: 12px 16px;
    border-bottom: 1px solid #e7e9ed;
    cursor: pointer;
    transition: background-color 0.2s;
}
.cust-search-item:hover {
    background-color: #f8f9fa;
}
.cust-search-item:last-child {
    border-bottom: none;
}
.cust-serial {
    min-width: 26px;
    height: 26px;
    background: #f0efff;
    color: #696cff;
    border-radius: 50%;
    font-size: .72rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    margin-top: 2px;
}
.cust-name {
    font-size: 0.9rem;
    font-weight: 600;
    color: #566a7f;
}
.cust-meta {
    font-size: 0.75rem;
    color: #8592a3;
    margin-top: 2px;
}
.cust-balance {
    font-size: 0.85rem;
    font-weight: 600;
}
.cust-balance.debit {
    color: #28a745;
}
.cust-balance.credit {
    color: #dc3545;
}
</style>
