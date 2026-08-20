<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<?php $this->load->view('common/header'); ?>

<div class="layout-wrapper layout-horizontal layout-content-navbar">
    <div class="layout-container">
        <?php $this->load->view('common/menu_view'); ?>
        <div class="layout-page">
            <div class="content-wrapper apex-content">
                <?php $this->load->view('common/apex/page_header', [
                    'pageTitle'       => 'Purchase Price List',
                    'pageDescription' => 'Latest purchase price per product per vendor — updated on every purchase',
                ]); ?>
                <div class="container-xxl flex-grow-1">
                    <div class="card">
                        <div class="trans-toolbar">
                            <div class="trans-toolbar-filters d-flex align-items-center gap-2 flex-wrap ms-2">
                                <div class="r2k-search-wrap is-expanded">
                                    <i class="bx bx-search r2k-si"></i>
                                    <input type="text" id="pplSearchInput"
                                           placeholder="Search product, SKU, vendor…">
                                </div>
                            </div>
                            <div class="trans-toolbar-actions">
                                <a href="#" class="r2k-icon-btn PageRefresh" title="Refresh"><i class="bx bx-refresh"></i></a>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table trans-table MainviewTable mb-0" id="pplTable">
                                <thead class="r2k-thead">
                                    <tr>
                                        <th class="r2k-sl-col">#</th>
                                        <th>Product</th>
                                        <th>Vendor</th>
                                        <th class="text-end">Purchase Price</th>
                                        <th class="text-center">Tax %</th>
                                        <th class="text-end">Qty</th>
                                        <th class="text-end">Discount</th>
                                        <th class="text-end">Total</th>
                                        <th>Last Bill</th>
                                        <th class="r2k-col-date-header">Last Updated</th>
                                    </tr>
                                </thead>
                                <tbody class="r2k-tbody table-border-bottom-0" id="pplTableBody">
                                    <?php echo $ModRowData ?? ''; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="row mx-0 px-3 mt-1 justify-content-between align-items-center apex-pag-sticky" id="pplPagination">
                            <?php echo $ModPagination ?? ''; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php $this->load->view('common/footer'); ?>
        </div>
    </div>
</div>

<script>
(function () {
    'use strict';

    var _currentFilter = {};
    var _searchTimer   = null;

    /**
     * @param {number} pageNo
     * @param {Object} filter
     * @returns {void}
     */
    function _loadPage(pageNo, filter) {
        ajaxLoading(1);
        $.ajax({
            url    : '/purchasepricelist/getPageDetails/' + pageNo,
            method : 'POST',
            data   : { Filter: filter, RowLimit: _rowLimit() },
            success: function (res) {
                ajaxLoading(0);
                if (res.Error) { toast('error', res.Message); return; }
                $('#pplTableBody').html(res.RecordHtmlData);
                $('#pplPagination').html(res.Pagination);
                _initTooltips();
            },
            error: function () {
                ajaxLoading(0);
                toast('error', 'Failed to load data.');
            }
        });
    }

    /**
     * @returns {void}
     */
    function _initTooltips() {
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
            if (!bootstrap.Tooltip.getInstance(el)) {
                new bootstrap.Tooltip(el, { trigger: 'hover' });
            }
        });
    }

    // ── Search ────────────────────────────────────────────────────────────────

    $('#pplSearchInput').on('input', function () {
        clearTimeout(_searchTimer);
        var val = $(this).val().trim();
        _searchTimer = setTimeout(function () {
            _currentFilter.search = val;
            _loadPage(1, _currentFilter);
        }, 400);
    });

    // ── Pagination (delegated) ────────────────────────────────────────────────

    $(document).on('click', '.r2k-page-link', function (e) {
        e.preventDefault();
        var page = parseInt($(this).data('page'), 10);
        if (!isNaN(page)) _loadPage(page, _currentFilter);
    });

    // ── Init ──────────────────────────────────────────────────────────────────

    _initTooltips();

}());
</script>
