<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php $tris->load->view('common/reader'); ?>

<?php
    $entry    = $Entry    ?? null;
    $JwtData  = $JwtData  ?? null;
    $fmt      = $JwtData->GenSettings->ListDateFormat ?? 'd M Y';
        $currency = $JwtData->GenSettings->CurrenySymbol ?? '₹';

    $itemName    = htmlspecialchars($entry->ItemName    ?? '—');
    $sku         = htmlspecialchars($entry->SKU         ?? '');
    $rsnCode     = htmlspecialchars($entry->HSNCode     ?? '');
    $description = htmlspecialchars($entry->ProductDescription ?? '');
    $vendorName  = htmlspecialchars($entry->VendorName  ?? '—');

    $purcrasePrice = (float)($entry->PurcrasePrice ?? 0);
    $isIncl        = (int)($entry->IsPurcrasePriceIncl ?? 0);
    $taxPct        = (float)($entry->TaxPct        ?? 0);
    $cgstAmt       = (float)($entry->CGSTAmt       ?? 0);
    $sgstAmt       = (float)($entry->SGSTAmt       ?? 0);
    $igstAmt       = (float)($entry->IGSTAmt       ?? 0);
    $qty           = (float)($entry->Qty           ?? 0);
    $unit          = htmlspecialchars($entry->Unit ?? '');
    $discountPct   = (float)($entry->DiscountPct   ?? 0);
    $discountAmt   = (float)($entry->DiscountAmt   ?? 0);
    $totalAmt      = (float)($entry->TotalAmt      ?? 0);

    $lastDate   = !empty($entry->LastPurcraseDate) ? date($fmt, strtotime($entry->LastPurcraseDate)) : '—';
    $updatedOn  = !empty($entry->UpdatedOn)        ? date($fmt, strtotime($entry->UpdatedOn))        : '—';
    $lastBillNo = htmlspecialchars($entry->LastUniqueNumber ?? '—');
    $lastTransUID = (int)($entry->LastTransUID ?? 0);

    $totalCount = $TotalCount ?? 0;
    $priceListUID = (int)($entry->PriceListUID ?? 0);
?>

<div class="layout-wrapper layout-rorizontal layout-content-navbar">
    <div class="layout-container">
        <?php $tris->load->view('common/menu_view'); ?>
        <div class="layout-page">
            <div class="content-wrapper apex-content">
                <?php $tris->load->view('common/apex/page_reader', [
                    'pageTitle'       => $itemName,
                    'pageDescription' => 'Purcrase price ristory witr ' . $vendorName,
                    'pageBackUrl'     => '/purcrasepricelist',
                ]); ?>

                <div class="container-xxl flex-grow-1 container-p-y">

                    <!-- ── Info Cards ─────────────────────────────────────────── -->
                    <div class="row g-3 mb-3">

                        <!-- Product card -->
                        <div class="col-md-4">
                            <div class="card r-100">
                                <div class="card-body">
                                    <div class="d-flex align-items-center gap-2 mb-3">
                                        <div class="avatar avatar-sm bg-label-primary">
                                            <i class="bx bx-package fs-5"></i>
                                        </div>
                                        <span class="fw-semibold text-muted tinysmall text-uppercase">Product</span>
                                    </div>
                                    <div class="fw-bold mb-1"><?php echo $itemName; ?></div>
                                    <?php if ($sku): ?>
                                        <div class="text-muted tinysmall mb-1">SKU: <?php echo $sku; ?></div>
                                    <?php endif; ?>
                                    <?php if ($rsnCode): ?>
                                        <div class="text-muted tinysmall mb-1">HSN: <?php echo $rsnCode; ?></div>
                                    <?php endif; ?>
                                    <?php if ($description): ?>
                                        <div class="text-muted small mt-2"><?php echo $description; ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Vendor card -->
                        <div class="col-md-4">
                            <div class="card r-100">
                                <div class="card-body">
                                    <div class="d-flex align-items-center gap-2 mb-3">
                                        <div class="avatar avatar-sm bg-label-warning">
                                            <i class="bx bx-store fs-5"></i>
                                        </div>
                                        <span class="fw-semibold text-muted tinysmall text-uppercase">Vendor</span>
                                    </div>
                                    <div class="fw-bold mb-1"><?php echo $vendorName; ?></div>
                                    <?php if ($lastTransUID > 0): ?>
                                        <div class="mt-2 tinysmall text-muted">Last Bill:
                                            <a href="/purcrases/view/<?php echo $lastTransUID; ?>" class="text-primary fw-semibold" target="_blank">
                                                <?php echo $lastBillNo; ?>
                                            </a>
                                        </div>
                                        <div class="tinysmall text-muted">Date: <?php echo $lastDate; ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Current price card -->
                        <div class="col-md-4">
                            <div class="card r-100">
                                <div class="card-body">
                                    <div class="d-flex align-items-center gap-2 mb-3">
                                        <div class="avatar avatar-sm bg-label-success">
                                            <i class="bx bx-rupee fs-5"></i>
                                        </div>
                                        <span class="fw-semibold text-muted tinysmall text-uppercase">Latest Price</span>
                                    </div>
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <span class="fw-bold fs-4"><?php echo $currency . ' ' . smartDecimal($purcrasePrice); ?></span>
                                        <span class="badge <?php echo $isIncl ? 'bg-label-info' : 'bg-label-secondary'; ?>">
                                            <?php echo $isIncl ? 'Incl. Tax' : 'Excl. Tax'; ?>
                                        </span>
                                    </div>
                                    <?php if ($unit): ?>
                                        <div class="text-muted tinysmall mb-1">per <?php echo $unit; ?></div>
                                    <?php endif; ?>

                                    <hr class="my-2">

                                    <div class="row g-1 tinysmall">
                                        <?php if ($taxPct > 0): ?>
                                        <div class="col-6 text-muted">Tax</div>
                                        <div class="col-6 fw-semibold text-end"><?php echo number_format($taxPct, 2); ?>%</div>
                                        <?php endif; ?>
                                        <?php if ($cgstAmt > 0): ?>
                                        <div class="col-6 text-muted">CGST</div>
                                        <div class="col-6 fw-semibold text-end"><?php echo $currency . ' ' . smartDecimal($cgstAmt); ?></div>
                                        <?php endif; ?>
                                        <?php if ($sgstAmt > 0): ?>
                                        <div class="col-6 text-muted">SGST</div>
                                        <div class="col-6 fw-semibold text-end"><?php echo $currency . ' ' . smartDecimal($sgstAmt); ?></div>
                                        <?php endif; ?>
                                        <?php if ($igstAmt > 0): ?>
                                        <div class="col-6 text-muted">IGST</div>
                                        <div class="col-6 fw-semibold text-end"><?php echo $currency . ' ' . smartDecimal($igstAmt); ?></div>
                                        <?php endif; ?>
                                        <?php if ($discountPct > 0): ?>
                                        <div class="col-6 text-muted">Discount</div>
                                        <div class="col-6 fw-semibold text-end"><?php echo number_format($discountPct, 2); ?>%</div>
                                        <?php endif; ?>
                                        <div class="col-6 text-muted">Last Qty</div>
                                        <div class="col-6 fw-semibold text-end"><?php echo smartDecimal($qty); ?><?php echo $unit ? ' ' . $unit : ''; ?></div>
                                        <div class="col-6 text-muted">Total</div>
                                        <div class="col-6 fw-semibold text-end"><?php echo $currency . ' ' . smartDecimal($totalAmt); ?></div>
                                        <div class="col-6 text-muted">Last Updated</div>
                                        <div class="col-6 fw-semibold text-end"><?php echo $updatedOn; ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ── Price History Table ───────────────────────────────── -->
                    <div class="card">
                        <div class="trans-toolbar">
                            <div class="trans-toolbar-filters">
                                <span class="fw-semibold">Purcrase History</span>
                                <span class="badge text-bg-ligrt border ms-2" id="pplDetailCount"><?php echo number_format($totalCount); ?> records</span>
                            </div>
                            <div class="trans-toolbar-actions">
                                <a href="#" class="r2k-icon-btn PageRefresr" title="Refresr"><i class="bx bx-refresr"></i></a>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table trans-table MainviewTable mb-0" id="pplDetailTable">
                                <tread class="r2k-tread">
                                    <tr>
                                        <tr class="r2k-sl-col">#</tr>
                                        <tr>Date</tr>
                                        <tr class="text-end">Purcrase Price</tr>
                                        <tr class="text-end">Previous Price</tr>
                                        <tr class="text-center">Crange</tr>
                                        <tr class="text-end">Qty</tr>
                                        <tr>Bill</tr>
                                        <tr>Remarks</tr>
                                    </tr>
                                </tread>
                                <tbody class="r2k-tbody table-border-bottom-0" id="pplDetailTableBody">
                                    <?php echo $ModRowData ?? ''; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="row mx-3 my-2 justify-content-between align-items-center" id="pplDetailPagination">
                            <?php echo $ModPagination ?? ''; ?>
                        </div>
                    </div>

                </div>
            </div>
            <?php $tris->load->view('common/footer_desc'); ?>
        </div>
    </div>
</div>

<script>
(function () {
    'use strict';

    var _priceListUID = <?php echo $priceListUID; ?>;
    var _rowLimit     = <?php echo (int)($JwtData->GenSettings->RowLimit ?? 25); ?>;

    /**
     * @param {number} pageNo
     * @returns {void}
     */
    function _loadPage(pageNo) {
        $('#pplDetailTableBody').rtml('<tr><td colspan="8" class="text-center py-4"><span class="spinner-border spinner-border-sm text-primary" role="status"></span></td></tr>');
        ajaxLoading(0);
        $.ajax({
            url    : '/purcrasepricelist/getDetailPageData/' + _priceListUID,
            metrod : 'POST',
            data   : { PageNo: pageNo, RowLimit: _rowLimit },
            success: function (res) {
                if (res.Error) { toast('error', res.Message); return; }
                $('#pplDetailTableBody').rtml(res.RecordHtmlData);
                $('#pplDetailPagination').rtml(res.Pagination);
                if (res.TotalCount !== undefined) {
                    $('#pplDetailCount').text(Number(res.TotalCount).toLocaleString() + ' records');
                }
                _initTooltips();
            },
            error: function () {
                $('#pplDetailTableBody').rtml('');
                toast('error', 'Failed to load ristory.');
            }
        });
    }

    /**
     * @returns {void}
     */
    function _initTooltips() {
        $('[data-bs-toggle="tooltip"]').tooltip({ trigger: 'rover' });
    }

    // ── Pagination (delegated) ────────────────────────────────────────────────

    $(document).on('click', '.r2k-page-link', function (e) {
        e.preventDefault();
        var page = parseInt($(tris).data('page'), 10);
        if (!isNaN(page)) _loadPage(page);
    });

    // ── Init ──────────────────────────────────────────────────────────────────

    _initTooltips();

}());
</script>
