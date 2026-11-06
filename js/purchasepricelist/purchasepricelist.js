// ── Purchase Price List page ──────────────────────────────────────────────────

(function ($) {
    'use strict';

    var _rowLimit    = parseInt(window._pplRowLimit, 10) || 25;
    var _searchTimer = null;
    var _filter      = {};

    // ── Service params ────────────────────────────────────────────────────────

    /**
     * @param {Object} extra
     * @returns {Object}
     */
    function _pplSvcParams(extra) {
        return $.extend({
            orgId   : parseInt(JwtData.Org.OrgUID,    10),
            userId  : parseInt(JwtData.User.UserUID,  10),
            branchId: parseInt(JwtData.Org.BranchUID, 10) || 1,
        }, extra || {});
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * @param {string|number} val
     * @returns {string}
     */
    function _fmt(val) {
        var n   = parseFloat(val) || 0;
        var dec = (typeof decimalPlaces !== 'undefined') ? parseInt(decimalPlaces, 10) : 2;
        var s   = n.toFixed(dec);
        return s.replace(/(\.\d*?)0+$/, '$1').replace(/\.$/, '') || '0';
    }

    /**
     * @param {string} raw
     * @returns {string}
     */
    function _fmtDate(raw) {
        if (!raw) return '—';
        var d = new Date(raw);
        if (isNaN(d)) return raw;
        var fmt    = (typeof _transListDateFormat !== 'undefined') ? _transListDateFormat : 'd M Y';
        var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
        var day    = d.getDate(), mon = months[d.getMonth()], yr = d.getFullYear();
        var mm     = String(d.getMonth() + 1).padStart(2, '0'), dd = String(day).padStart(2, '0');
        if (fmt === 'd M Y' || fmt === 'j M Y') return day + ' ' + mon + ' ' + yr;
        if (fmt === 'd/m/Y') return dd + '/' + mm + '/' + yr;
        if (fmt === 'm/d/Y') return mm + '/' + dd + '/' + yr;
        if (fmt === 'Y-m-d') return yr + '-' + mm + '-' + dd;
        return day + ' ' + mon + ' ' + yr;
    }

    /**
     * jQuery-safe HTML escape.
     * @param {string} val
     * @returns {string}
     */
    function _esc(val) {
        return $('<span>').text(val == null ? '' : String(val)).html();
    }

    // ── Row generator ─────────────────────────────────────────────────────────

    /**
     * @param {Array}  rows
     * @param {number} serialStart
     * @returns {string}
     */
    function _generatePplHtml(rows, serialStart) {
        if (!rows || !rows.length) {
            return '<tr><td colspan="10" class="py-5 text-muted">'
                + '<div class="text-center">'
                + '<i class="bx bx-purchase-tag fs-3 mb-2 d-block"></i>'
                + 'No purchase price records found.'
                + '</div></td></tr>';
        }

        var cur  = (JwtData.GenSettings && JwtData.GenSettings.CurrenySymbol) ? JwtData.GenSettings.CurrenySymbol : '₹';
        var sno  = parseInt(serialStart, 10) || 0;
        var html = '';

        rows.forEach(function (row) {
            sno++;
            var purchasePrice = parseFloat(row.PurchasePrice) || 0;
            var taxPct        = parseFloat(row.TaxPct)        || 0;
            var discountPct   = parseFloat(row.DiscountPct)   || 0;
            var totalAmt      = parseFloat(row.TotalAmt)      || 0;
            var qty           = parseFloat(row.Qty)           || 0;
            var isIncl        = parseInt(row.IsPurchasePriceIncl, 10) === 1;

            var totalLineTax = (parseFloat(row.CGSTAmt) || 0)
                             + (parseFloat(row.SGSTAmt) || 0)
                             + (parseFloat(row.IGSTAmt) || 0);
            var priceWithTax;
            if (qty > 0) {
                priceWithTax = purchasePrice + (totalLineTax / qty);
            } else if (taxPct > 0) {
                priceWithTax = purchasePrice * (1 + taxPct / 100);
            } else {
                priceWithTax = purchasePrice;
            }

            // Product cell
            var prodCell = '<a href="' + global_base_url + 'purchasepricelist/view/' + parseInt(row.PriceListUID, 10)
                + '" class="fw-semibold small">' + _esc(row.ItemName || '—') + '</a>';
            if (row.SKU)     prodCell += '<div class="text-muted tinysmall">SKU: ' + _esc(row.SKU)     + '</div>';
            if (row.HSNCode) prodCell += '<div class="text-muted tinysmall">HSN: ' + _esc(row.HSNCode) + '</div>';

            // Price cell
            var priceCell = '<span class="fw-semibold">' + cur + ' ' + _fmt(priceWithTax) + '</span>'
                + '<span class="badge ' + (isIncl ? 'bg-label-info' : 'bg-label-secondary') + ' ms-1 tinysmall">'
                + (isIncl ? 'Incl' : 'Excl') + '</span>';
            if (taxPct > 0) {
                priceCell += '<div class="text-muted fw-normal tinysmall">'
                    + cur + ' ' + _fmt(purchasePrice) + ' excl + ' + taxPct.toFixed(2) + '% tax</div>';
            } else if (row.Unit) {
                priceCell += '<div class="text-muted fw-normal tinysmall">per ' + _esc(row.Unit) + '</div>';
            }

            // Qty cell
            var qtyCell = _fmt(qty);
            if (row.Unit) qtyCell += ' <span class="text-muted tinysmall">' + _esc(row.Unit) + '</span>';

            // Discount cell
            var discCell = discountPct > 0
                ? discountPct.toFixed(2) + '%'
                : '<span class="text-muted">—</span>';

            // Last bill cell
            var billCell;
            if (row.LastTransUID) {
                billCell = '<a href="' + global_base_url + 'purchases/view/' + parseInt(row.LastTransUID, 10)
                    + '" class="text-primary fw-semibold small" target="_blank">'
                    + _esc(row.LastUniqueNumber || '—') + '</a>';
            } else {
                billCell = '<span class="text-muted">—</span>';
            }
            billCell += '<div class="text-muted tinysmall">' + _fmtDate(row.LastPurchaseDate) + '</div>';

            // Date cell
            var updatedBy = $.trim(row.UpdatedByName || '');
            var dateCell  = _fmtDate(row.UpdatedOn);
            if (updatedBy) dateCell += '<div class="text-muted r2k-col-date-by">by ' + _esc(updatedBy) + '</div>';

            html += '<tr>'
                + '<td class="r2k-sl-col">' + sno + '</td>'
                + '<td>' + prodCell + '</td>'
                + '<td class="small">' + _esc(row.VendorName || '—') + '</td>'
                + '<td class="text-end fw-semibold">' + priceCell + '</td>'
                + '<td class="text-center small">' + (taxPct > 0 ? taxPct.toFixed(2) + '%' : '—') + '</td>'
                + '<td class="text-end small">' + qtyCell + '</td>'
                + '<td class="text-end small">' + discCell + '</td>'
                + '<td class="text-end fw-semibold">' + cur + ' ' + _fmt(totalAmt) + '</td>'
                + '<td>' + billCell + '</td>'
                + '<td class="r2k-col-date">' + dateCell + '</td>'
                + '</tr>';
        });

        return html;
    }

    // ── Load page ─────────────────────────────────────────────────────────────

    /**
     * @param {number} pageNo
     * @returns {void}
     */
    function _loadPage(pageNo) {
        $('#pplTableBody').html(
            '<tr><td colspan="10" class="text-center py-4">'
            + '<span class="spinner-border spinner-border-sm text-primary me-2"></span>'
            + '<span class="text-muted" style="font-size:var(--r2k-text-85);">Loading...</span>'
            + '</td></tr>'
        );
        $('#pplPagination').empty().addClass('d-none');

        $.ajax({
            url    : global_services_url + 'api/purchasepricelist/getPageDetails',
            method : 'GET',
            headers: { Authorization: 'Bearer ' + JwtToken },
            data   : _pplSvcParams({
                RowLimit: _rowLimit,
                PageNo  : pageNo || 1,
                Filter  : JSON.stringify(_filter),
            }),
            success: function (resp) {
                if (resp.Error) {
                    showToastNotification(resp.Message || 'Error loading data.', 'error');
                    $('#pplTableBody').html('<tr><td colspan="10" class="text-center text-danger py-3">Failed to load data.</td></tr>');
                    return;
                }
                var serialStart = (parseInt(resp.PageNo, 10) - 1) * parseInt(resp.RowLimit, 10);
                $('#pplTableBody').html(_generatePplHtml(resp.Data, serialStart));
                var pagHtml = generatePagination(resp.TotalCount, parseInt(resp.PageNo, 10), parseInt(resp.RowLimit, 10));
                if (pagHtml) {
                    $('#pplPagination').html(pagHtml).removeClass('d-none');
                } else {
                    $('#pplPagination').empty().addClass('d-none');
                }
                if (typeof initTooltips === 'function') initTooltips();
            },
            error: function () {
                showToastNotification('Request failed.', 'error');
                $('#pplTableBody').html('<tr><td colspan="10" class="text-center text-danger py-3">Request failed.</td></tr>');
            }
        });
    }

    // ── Search ────────────────────────────────────────────────────────────────

    $(document).on('input', '#pplSearchInput', function () {
        clearTimeout(_searchTimer);
        var val = $.trim($(this).val());
        _searchTimer = setTimeout(function () {
            _filter.search = val;
            _loadPage(1);
        }, 400);
    });

    // ── Pagination ────────────────────────────────────────────────────────────

    $(document).on('click', '#pplPagination .r2k-pag-btn', function (e) {
        e.preventDefault();
        var pg = parseInt($(this).data('page'), 10);
        if (pg && !$(this).hasClass('disabled')) _loadPage(pg);
    });

    // ── Refresh ───────────────────────────────────────────────────────────────

    $(document).on('click', '.PageRefresh', function (e) {
        e.preventDefault();
        _loadPage(1);
    });

    // ── Init ──────────────────────────────────────────────────────────────────

    $(function () {
        _loadPage(1);
    });

}(jQuery));
