<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<!-- Customer Search Modal — included via common_form.php — available on all pages -->
<div class="modal fade" id="customerSearchModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable" style="max-height:90vh;">
        <div class="modal-content" style="height:82vh;">

            <!-- Header -->
            <div class="vtm-banner flex-shrink-0" style="--vtm-color:#696cff;--vtm-bg:#f0efff;--vtm-icon-bg:rgba(105,108,255,.13);">
                <div class="vtm-banner-inner">
                    <div class="vtm-banner-left">
                        <div class="vtm-banner-icon">
                            <i class="bx bx-user-search" style="font-size:1.7rem;color:var(--vtm-color);display:block;"></i>
                        </div>
                        <div>
                            <div style="font-size:.95rem;font-weight:700;color:var(--vtm-color);">Search Customers</div>
                            <div style="font-size:.75rem;color:#6c757d;margin-top:2px;">Select a customer to apply</div>
                        </div>
                    </div>
                    <div class="vtm-banner-right">
                        <div class="input-group input-group-sm" style="width:240px;">
                            <span class="input-group-text"><i class="bx bx-search"></i></span>
                            <input type="text" id="custSearchInput" class="form-control"
                                   placeholder="Name, mobile, area..." autocomplete="off" />
                            <button type="button" id="custSearchClear"
                                    class="btn btn-outline-secondary d-none" tabindex="-1">
                                <i class="bx bx-x"></i>
                            </button>
                        </div>
                        <button type="button" id="btnCreateCustomerFromSearch"
                                class="btn btn-sm btn-outline-primary"
                                style="white-space:nowrap;" title="Create new customer">
                            <i class="bx bx-plus me-1"></i>Create Customer
                        </button>
                        <button type="button" class="vtm-close-btn" data-bs-dismiss="modal" aria-label="Close">
                            <i class="bx bx-x"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Scrollable table body -->
            <div class="modal-body p-0" id="custSearchScrollBody" style="flex:1;overflow-y:auto;">
                <table class="table table-hover table-sm mb-0 align-middle w-100" id="custSearchTable">
                    <thead class="cust-search-thead">
                        <tr>
                            <th class="text-center" style="width:46px;">#</th>
                            <th>Customer Name</th>
                            <th style="width:140px;">Area</th>
                            <th style="width:130px;">Mobile</th>
                            <th class="text-end" style="width:130px;">Balance</th>
                        </tr>
                    </thead>
                    <tbody id="custSearchResults">
                        <tr>
                            <td colspan="5" class="text-center py-5">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <!-- Infinite scroll sentinel (watched by IntersectionObserver) -->
                <div id="custSearchSentinel" style="height:1px;"></div>
                <div id="custSearchLoadingMore" class="text-center py-2 d-none">
                    <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                    <span class="ms-2 text-muted" style="font-size:.8rem;">Loading more...</span>
                </div>
            </div>

            <!-- Footer: result count -->
            <div class="d-flex align-items-center justify-content-between flex-shrink-0 px-3 py-2 border-top"
                 style="background:#f0efff;min-height:36px;">
                <small id="custSearchPageInfo" style="color:#696cff;font-weight:600;">Loading…</small>
                <small class="text-muted">Scroll down to load more</small>
            </div>

        </div>
    </div>
</div>

<style>
.cust-search-thead th {
    position: sticky;
    top: 0;
    background: #fff;
    z-index: 2;
    font-size: .76rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .3px;
    color: #566a7f;
    padding: 10px 12px;
    border-bottom: 2px solid #e7e9ed;
    white-space: nowrap;
}
.cust-search-row { cursor: pointer; }
.cust-search-row:hover td { background-color: #f0efff; }
.cust-search-row td { padding: 9px 12px; vertical-align: middle; border-color: #f1f5f9; }
.cust-serial {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 26px;
    height: 26px;
    background: #f0efff;
    color: #696cff;
    border-radius: 50%;
    font-size: .72rem;
    font-weight: 700;
}
.cust-name     { font-size: .88rem; font-weight: 600; color: #566a7f; }
.cust-meta     { font-size: .75rem; color: #8592a3; }
.cust-bal-debit  { font-size: .82rem; font-weight: 600; color: #28a745; }
.cust-bal-credit { font-size: .82rem; font-weight: 600; color: #dc3545; }
</style>
