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
                                </tbody>
                            </table>
                        </div>

                        <div class="row mx-0 px-3 mt-1 justify-content-between align-items-center apex-pag-sticky d-none" id="pplPagination">
                        </div>
                    </div>
                </div>
            </div>
            <?php $this->load->view('common/footer'); ?>
        </div>
    </div>
</div>

<script src="<?php echo _assetV('/js/core/pagination.js'); ?>"></script>
<script>
window._pplRowLimit = <?php echo (int)($JwtData->GenSettings->RowLimit ?? 25); ?>;
</script>
<script src="/js/purchasepricelist/purchasepricelist.js"></script>
