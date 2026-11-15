<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php $this->load->view('common/reader'); ?>

<?php
    $JwtData      = $JwtData      ?? null;
    $priceListUID = (int)($PriceListUID ?? 0);
?>

<div class="layout-wrapper layout-horizontal layout-content-navbar">
    <div class="layout-container">
        <?php $this->load->view('common/menu_view'); ?>
        <div class="layout-page">
            <div class="content-wrapper apex-content">
                <?php $this->load->view('common/apex/page_header', [
                    'pageTitle'       => 'Purchase Price Detail',
                    'pageDescription' => 'Purchase price history per vendor',
                    'pageBackUrl'     => '/purchasepricelist',
                ]); ?>

                <div class="container-xxl flex-grow-1 container-p-y">

                    <!-- ── Info Cards ──────────────────────────────────────── -->
                    <div class="row g-3 mb-3">

                        <div class="col-md-4">
                            <div class="card h-100">
                                <div class="card-body">
                                    <div class="d-flex align-items-center gap-2 mb-3">
                                        <div class="avatar avatar-sm bg-label-primary">
                                            <i class="bx bx-package fs-5"></i>
                                        </div>
                                        <span class="fw-semibold text-muted tinysmall text-uppercase">Product</span>
                                    </div>
                                    <div id="pplProductCardBody" class="placeholder-glow">
                                        <span class="placeholder col-8 d-block mb-1"></span>
                                        <span class="placeholder col-5 d-block mb-1"></span>
                                        <span class="placeholder col-6 d-block"></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="card h-100">
                                <div class="card-body">
                                    <div class="d-flex align-items-center gap-2 mb-3">
                                        <div class="avatar avatar-sm bg-label-warning">
                                            <i class="bx bx-store fs-5"></i>
                                        </div>
                                        <span class="fw-semibold text-muted tinysmall text-uppercase">Vendor</span>
                                    </div>
                                    <div id="pplVendorCardBody" class="placeholder-glow">
                                        <span class="placeholder col-7 d-block mb-1"></span>
                                        <span class="placeholder col-5 d-block"></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="card h-100">
                                <div class="card-body">
                                    <div class="d-flex align-items-center gap-2 mb-3">
                                        <div class="avatar avatar-sm bg-label-success">
                                            <i class="bx bx-rupee fs-5"></i>
                                        </div>
                                        <span class="fw-semibold text-muted tinysmall text-uppercase">Latest Price</span>
                                    </div>
                                    <div id="pplPriceCardBody" class="placeholder-glow">
                                        <span class="placeholder col-6 placeholder-lg d-block mb-2"></span>
                                        <span class="placeholder col-8 d-block mb-1"></span>
                                        <span class="placeholder col-7 d-block"></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- ── Purchase History Table ──────────────────────────── -->
                    <div class="card">
                        <div class="trans-toolbar">
                            <div class="trans-toolbar-filters">
                                <span class="fw-semibold">Purchase History</span>
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
     * @param {Object} e  entry object from API
     * @returns {void}
     */
    function _fillProductCard(e) {
        var html = '<div class="fw-bold mb-1">' + _esc(e.ItemName || '—') + '</div>';
        if (e.SKU)                html += '<div class="text-muted tinysmall mb-1">SKU: ' + _esc(e.SKU) + '</div>';
        if (e.HSNCode)            html += '<div class="text-muted tinysmall mb-1">HSN: ' + _esc(e.HSNCode) + '</div>';
        if (e.ProductDescription) html += '<div class="text-muted small mt-2">' + _esc(e.ProductDescription) + '</div>';
        $('#pplProductCardBody').removeClass('placeholder-glow').html(html);
    }

    /**
     * @param {Object} e  entry object from API
     * @returns {void}
     */
    function _fillVendorCard(e) {
        var txUID = parseInt(e.LastTransUID || 0, 10);
        var html  = '<div class="fw-bold mb-1">' + _esc(e.VendorName || '—') + '</div>';
        if (txUID > 0) {
            html += '<div class="mt-2 tinysmall text-muted">Last Bill: '
                  + '<a href="/purchases/view/' + txUID + '" class="text-primary fw-semibold" target="_blank">'
                  + _esc(e.LastUniqueNumber || '—') + '</a></div>';
            html += '<div class="tinysmall text-muted">Date: ' + _fmtDate(e.LastPurcraseDate) + '</div>';
        }
        $('#pplVendorCardBody').removeClass('placeholder-glow').html(html);
    }

    /**
     * @param {Object} e  entry object from API
     * @returns {void}
     */
    function _fillPriceCard(e) {
        var price    = parseFloat(e.PurcrasePrice || 0);
        var isIncl   = parseInt(e.IsPurcrasePriceIncl || 0, 10);
        var taxPct   = parseFloat(e.TaxPct   || 0);
        var cgstAmt  = parseFloat(e.CGSTAmt  || 0);
        var sgstAmt  = parseFloat(e.SGSTAmt  || 0);
        var igstAmt  = parseFloat(e.IGSTAmt  || 0);
        var discPct  = parseFloat(e.DiscountPct || 0);
        var qty      = parseFloat(e.Qty      || 0);
        var totalAmt = parseFloat(e.TotalAmt || 0);
        var unit     = e.Unit ? _esc(e.Unit) : '';

        var taxBadge = isIncl
            ? '<span class="badge bg-label-info">Incl. Tax</span>'
            : '<span class="badge bg-label-secondary">Excl. Tax</span>';

        var html = '<div class="d-flex align-items-center gap-2 mb-2">'
                 + '<span class="fw-bold fs-4">' + _cur + ' ' + _smartDec(price) + '</span>'
                 + taxBadge
                 + '</div>';

        if (unit) html += '<div class="text-muted tinysmall mb-1">per ' + unit + '</div>';

        html += '<hr class="my-2"><div class="row g-1 tinysmall">';
        if (taxPct > 0)  html += '<div class="col-6 text-muted">Tax</div><div class="col-6 fw-semibold text-end">' + taxPct.toFixed(2) + '%</div>';
        if (cgstAmt > 0) html += '<div class="col-6 text-muted">CGST</div><div class="col-6 fw-semibold text-end">' + _cur + ' ' + _smartDec(cgstAmt) + '</div>';
        if (sgstAmt > 0) html += '<div class="col-6 text-muted">SGST</div><div class="col-6 fw-semibold text-end">' + _cur + ' ' + _smartDec(sgstAmt) + '</div>';
        if (igstAmt > 0) html += '<div class="col-6 text-muted">IGST</div><div class="col-6 fw-semibold text-end">' + _cur + ' ' + _smartDec(igstAmt) + '</div>';
        if (discPct > 0) html += '<div class="col-6 text-muted">Discount</div><div class="col-6 fw-semibold text-end">' + discPct.toFixed(2) + '%</div>';
        html += '<div class="col-6 text-muted">Last Qty</div><div class="col-6 fw-semibold text-end">' + _smartDec(qty) + (unit ? ' ' + unit : '') + '</div>';
        html += '<div class="col-6 text-muted">Total</div><div class="col-6 fw-semibold text-end">' + _cur + ' ' + _smartDec(totalAmt) + '</div>';
        html += '<div class="col-6 text-muted">Last Updated</div><div class="col-6 fw-semibold text-end">' + _fmtDate(e.UpdatedOn) + '</div>';
        html += '</div>';

        $('#pplPriceCardBody').removeClass('placeholder-glow').html(html);
    }

    /**
     * @returns {void}
     */
    function _loadEntry() {
        $.ajax({
            url    : global_services_url + 'api/purchasepricelist/view/' + _priceListUID,
            method : 'GET',
            headers: { Authorization: 'Bearer ' + JwtToken },
            data   : { orgId: parseInt(JwtData.Org.OrgUID, 10) },
            success: function (res) {
                if (res.Error) { showToastNotification(res.Message || 'Failed to load entry.', 'error'); return; }
                var e = res.Data && res.Data.Entry ? res.Data.Entry : {};
                _fillProductCard(e);
                _fillVendorCard(e);
                _fillPriceCard(e);
            },
            error: function () {
                showToastNotification('Failed to load entry.', 'error');
            }
        });
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

    // ── Refresh ──────────────────────────────────────────────────────────────

    $(document).on('click', '.PageRefresh', function (e) {
        e.preventDefault();
        _loadEntry();
        _loadPage(1);
    });

    // ── Init ─────────────────────────────────────────────────────────────────

    _loadEntry();
    _loadPage(1);

}());
</script>
