<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<!-- Customer Search Modal -->
<div class="modal fade" id="customerSearchModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bx bx-search me-2"></i>Search Customers</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <!-- Search Bar -->
                <div class="p-3 border-bottom">
                    <div class="input-group">
                        <span class="input-group-text"><i class="bx bx-search"></i></span>
                        <input type="text" id="custSearchInput" class="form-control" placeholder="Search by name, mobile, area..." />
                    </div>
                </div>

                <!-- Customer List -->
                <div id="custSearchResults" style="min-height:300px;max-height:400px;overflow-y:auto;">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>

                <!-- Pagination -->
                <div class="border-top p-2" id="custSearchPagination"></div>
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
