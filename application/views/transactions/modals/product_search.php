<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<!-- Product Search Modal — included via common_form.php — available on all transaction pages -->
<div class="modal fade" id="productSearchModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" style="max-height:90vh;">
        <div class="modal-content" style="height:82vh;">

            <!-- Header -->
            <div class="vtm-banner flex-shrink-0" style="--vtm-color:#28c76f;--vtm-bg:#eafff4;--vtm-icon-bg:rgba(40,199,111,.13);">
                <div class="vtm-banner-inner">
                    <div class="vtm-banner-left">
                        <div class="vtm-banner-icon">
                            <i class="bx bx-package" style="font-size:1.7rem;color:var(--vtm-color);display:block;"></i>
                        </div>
                        <div>
                            <div style="font-size:.95rem;font-weight:700;color:var(--vtm-color);">Search Products</div>
                            <div style="font-size:.75rem;color:#6c757d;margin-top:2px;">Click a row to add to bill (qty 1)</div>
                        </div>
                    </div>
                    <div class="vtm-banner-right">
                        <div class="input-group input-group-sm" style="width:260px;">
                            <span class="input-group-text"><i class="bx bx-search"></i></span>
                            <input type="text" id="prodSearchInput" class="form-control"
                                   placeholder="Name, category, HSN, part no..." autocomplete="off" />
                            <button type="button" id="prodSearchClear"
                                    class="btn btn-outline-secondary d-none" tabindex="-1">
                                <i class="bx bx-x"></i>
                            </button>
                        </div>
                        <button type="button" class="vtm-close-btn" data-bs-dismiss="modal" aria-label="Close">
                            <i class="bx bx-x"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Scrollable table body -->
            <div class="modal-body p-0" id="prodSearchScrollBody" style="flex:1;overflow-y:auto;">
                <table class="table table-hover table-sm mb-0 align-middle w-100" id="prodSearchTable">
                    <thead class="prod-search-thead">
                        <tr>
                            <th class="text-center" style="width:46px;">#</th>
                            <th>Item Name</th>
                            <th style="width:150px;">Category</th>
                            <th style="width:80px;">Unit</th>
                            <th class="text-end" style="width:110px;">Price</th>
                            <th class="text-end" style="width:90px;">Stock</th>
                        </tr>
                    </thead>
                    <tbody id="prodSearchResults">
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <div class="spinner-border text-success" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div id="prodSearchSentinel" style="height:1px;"></div>
                <div id="prodSearchLoadingMore" class="text-center py-2 d-none">
                    <div class="spinner-border spinner-border-sm text-success" role="status"></div>
                    <span class="ms-2 text-muted" style="font-size:.8rem;">Loading more…</span>
                </div>
            </div>

            <!-- Footer -->
            <div class="d-flex align-items-center justify-content-between flex-shrink-0 px-3 py-2 border-top"
                 style="background:#eafff4;min-height:36px;">
                <small id="prodSearchPageInfo" style="color:#28c76f;font-weight:600;">Loading…</small>
                <small class="text-muted">Scroll down to load more</small>
            </div>

        </div>
    </div>
</div>

<style>
.prod-search-thead th {
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
.prod-search-row { cursor: pointer; }
.prod-search-row:hover td { background-color: #eafff4; }
.prod-search-row td { padding: 9px 12px; vertical-align: middle; border-color: #f1f5f9; }
.prod-serial {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 26px;
    height: 26px;
    background: #eafff4;
    color: #28c76f;
    border-radius: 50%;
    font-size: .72rem;
    font-weight: 700;
}
.prod-name         { font-size: .88rem; font-weight: 600; color: #566a7f; }
.prod-meta         { font-size: .75rem; color: #8592a3; }
.prod-price        { font-size: .82rem; font-weight: 600; color: #566a7f; }
.prod-stock-ok     { font-size: .82rem; font-weight: 600; color: #28c76f; }
.prod-stock-zero   { font-size: .82rem; font-weight: 600; color: #ea5455; }
.prod-badge-composite {
    display: inline-block;
    font-size: .64rem;
    font-weight: 700;
    padding: 1px 6px;
    border-radius: 4px;
    background: rgba(105,108,255,.12);
    color: #696cff;
    margin-left: 5px;
    vertical-align: middle;
    letter-spacing: .2px;
}
</style>
