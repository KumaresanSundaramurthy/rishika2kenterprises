<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php $this->load->view('common/reader'); ?>

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

<div class="layout-wrapper layout-horizontal layout-content-navbar">
    <div class="layout-container">
        <?php $this->load->view('common/menu_view'); ?>
        <div class="layout-page">
            <div class="content-wrapper apex-content">
                <?php $this->load->view('common/apex/page_header', [
                    'pageTitle'       => $itemName,
                    'pageDescription' => 'Purchase price history with ' . $vendorName,
                    'pageBackUrl'     => '/purchasepricelist',
                ]); ?>

                <div class="container-xxl flex-grow-1 container-p-y">

                    <!-- ── Info Cards ─────────────────────────────────────────── -->
                    <div class="row g-3 mb-3">

                        <!-- Product card -->
                        <div class="col-md-4">
                            <div class="card h-100">
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
                            <div class="card h-100">
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
                                            <a href="/purchases/view/<?php echo $lastTransUID; ?>" class="text-primary fw-semibold" target="_blank">
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
                            <div class="card h-100">
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
                                <span class="badge text-bg-light border ms-2" id="pplDetailCount"></span>
                            </div>
                            <div class="trans-toolbar-actions">
                                <a href="#" class="r2k-icon-btn PageRefresh" title="Refresh"><i class="bx bx-refresh"></i></a>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table trans-table MainviewTable mb-0" id="pplDetailTable">
                                <thead class="r2k-thead">
                                    <tr>
                                        <th class="r2k-sl-col">#</th>
                                        <th>Date</th>
                                        <th class="text-end">Purchase Price</th>
                                        <th class="text-end">Previous Price</th>
                                        <th class="text-center">Change</th>
                                        <th class="text-end">Qty</th>
                                        <th>Bill</th>
                                        <th>Remarks</th>
                                    </tr>
                                </thead>
                                <tbody class="r2k-tbody table-border-bottom-0" id="pplDetailTableBody">
                                </tbody>
                            </table>
                        </div>

                        <div class="row mx-0 px-3 mt-1 justify-content-between align-items-center apex-pag-sticky d-none" id="pplDetailPagination">
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
(function () {
    'use strict';

    var _priceListUID = <?php echo $priceListUID; ?>;
    var _rowLimit     = <?php echo (int)($JwtData->GenSettings->RowLimit ?? 25); ?>;
    var _cur          = <?php echo json_encode($JwtData->GenSettings->CurrenySymbol ?? '₹'); ?>;
    var _dec          = <?php echo (int)($JwtData->GenSettings->DecimalPlaces ?? 2); ?>;

    /**
     * @param {string|null} val
     * @returns {string}
     */
    function _esc(val) {
        return $('<span>').text(val == null ? '' : String(val)).html();
    }

    /**
     * @param {number} val
     * @returns {string}
     */
    function _smartDec(val) {
        var n = parseFloat(val) || 0;
        return n % 1 === 0 ? n.toFixed(0) : n.toFixed(_dec);
    }

    /**
     * @param {string} iso  YYYY-MM-DD
     * @returns {string}
     */
    function _fmtDate(iso) {
        if (!iso) return '—';
        var p = String(iso).slice(0, 10).split('-');
        if (p.length < 3) return iso;
        var mon = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
        return parseInt(p[2], 10) + ' ' + (mon[parseInt(p[1], 10) - 1] || '') + ' ' + p[0];
    }

    /**
     * @param {Array} rows
     * @param {number} offset
     * @returns {string}
     */
    function _buildHtml(rows, offset) {
        if (!rows || !rows.length) {
            return '<tr><td colspan="8" class="text-center py-5 text-muted"><i class="bx bx-history fs-3 d-block mb-2"></i>No purchase history found.</td></tr>';
        }
        var html = '';
        $.each(rows, function (i, r) {
            var n     = offset + i + 1;
            var price = parseFloat(r.PurchasePrice  || 0);
            var prev  = parseFloat(r.PreviousPrice  || 0);
            var diff  = parseFloat(r.PriceDiff      || 0);
            var pct   = parseFloat(r.PriceDiffPct   || 0);
            var dir   = String(r.Direction || '').toLowerCase().trim();
            var txUID = parseInt(r.TransactionUID || 0, 10);

            var dirBadge;
            if (dir === 'up') {
                dirBadge = '<span class="badge bg-label-danger"><i class="bx bx-up-arrow-alt me-1"></i>' + Math.abs(pct).toFixed(2) + '%</span>';
            } else if (dir === 'down') {
                dirBadge = '<span class="badge bg-label-success"><i class="bx bx-down-arrow-alt me-1"></i>' + Math.abs(pct).toFixed(2) + '%</span>';
            } else {
                dirBadge = '<span class="badge bg-label-secondary">New</span>';
            }

            var diffHtml = diff !== 0
                ? '<div class="tinysmall text-muted">' + (diff > 0 ? '+' : '') + _cur + ' ' + _smartDec(diff) + '</div>'
                : '';

            var unitSpan = r.Unit ? ' <span class="text-muted tinysmall">/ ' + _esc(r.Unit) + '</span>' : '';

            var billHtml = txUID > 0
                ? '<a href="/purchases/view/' + txUID + '" class="text-primary fw-semibold small" target="_blank"><i class="bx bx-link-external me-1"></i>View Bill</a>'
                : '<span class="text-muted">—</span>';
            if (r.Source) { billHtml += '<div class="tinysmall text-muted">' + _esc(r.Source) + '</div>'; }

            html += '<tr>'
                + '<td class="r2k-sl-col">' + n + '</td>'
                + '<td>' + _fmtDate(r.EntryDate) + '</td>'
                + '<td class="text-end fw-semibold">' + _cur + ' ' + _smartDec(price) + unitSpan + '</td>'
                + '<td class="text-end">' + (prev > 0 ? _cur + ' ' + _smartDec(prev) : '<span class="text-muted">—</span>') + '</td>'
                + '<td class="text-center">' + dirBadge + diffHtml + '</td>'
                + '<td class="text-end">' + _smartDec(r.Qty || 0) + unitSpan + '</td>'
                + '<td>' + billHtml + '</td>'
                + '<td class="text-muted small">' + _esc(r.Remarks || '') + '</td>'
                + '</tr>';
        });
        return html;
    }

    /**
     * @param {number} pageNo
     * @returns {void}
     */
    function _loadPage(pageNo) {
        $('#pplDetailTableBody').html('<tr><td colspan="8" class="text-center py-4"><span class="spinner-border spinner-border-sm text-primary" role="status"></span></td></tr>');
        ajaxLoading(0);
        $.ajax({
            url    : global_services_url + 'api/purchasepricelist/getDetailPageData/' + _priceListUID,
            method : 'GET',
            headers: { Authorization: 'Bearer ' + JwtToken },
            data   : { orgId: parseInt(JwtData.Org.OrgUID, 10), PageNo: pageNo, RowLimit: _rowLimit },
            success: function (res) {
                ajaxLoading(1);
                if (res.Error) { showToastNotification(res.Message || 'Failed to load.', 'error'); return; }
                var rl     = parseInt(res.RowLimit || _rowLimit, 10);
                var pg     = parseInt(res.PageNo   || pageNo,    10);
                var offset = (pg - 1) * rl;
                $('#pplDetailTableBody').html(_buildHtml(res.Data, offset));
                $('#pplDetailCount').text(Number(res.TotalCount || 0).toLocaleString() + ' records');
                var pagHtml = generatePagination(res.TotalCount, pg, rl);
                if (pagHtml) {
                    $('#pplDetailPagination').html(pagHtml).removeClass('d-none');
                } else {
                    $('#pplDetailPagination').addClass('d-none');
                }
                $('[data-bs-toggle="tooltip"]').tooltip({ trigger: 'hover' });
            },
            error: function () {
                ajaxLoading(1);
                $('#pplDetailTableBody').html('');
                showToastNotification('Failed to load history.', 'error');
            }
        });
    }

    // ── Pagination ───────────────────────────────────────────────────────────

    $(document).on('click', '.r2k-pag-btn', function (e) {
        e.preventDefault();
        var page = parseInt($(this).data('page'), 10);
        if (!isNaN(page)) _loadPage(page);
    });

    // ── Refresh button ───────────────────────────────────────────────────────

    $(document).on('click', '.PageRefresh', function (e) {
        e.preventDefault();
        _loadPage(1);
    });

    // ── Init ─────────────────────────────────────────────────────────────────

    _loadPage(1);

}());
</script>
