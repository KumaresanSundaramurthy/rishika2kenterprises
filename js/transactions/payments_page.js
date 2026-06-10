/**
 * PaymentsPage — OOP controller for the unified Payments list page.
 *
 * Responsibilities:
 *   - Merge filter state from multiple sources (date, search, direction,
 *     payment-mode TransColFilter, created-by TransColFilter)
 *   - Load paginated list data and live balance stats via AJAX
 *   - Render the 3 stats cards (Balance / In / Out) and footer totals
 *   - Expose clearFilters() so the toolbar "Clear" button can call it
 */
var PaymentsPage = (function () {
    'use strict';

    /**
     * @param {object} cfg
     *   .sym            {string}  Currency symbol, e.g. '₹'
     *   .dec            {number}  Decimal places, e.g. 2
     *   .limit          {number}  Rows per page
     *   .initStats      {object}  Initial stats from PHP {CashIn,CashOut,BankIn,BankOut}
     *   .payModeFilter  {TransColFilter|null}
     *   .createdByFilter{TransColFilter|null}
     */
    function PaymentsPage(cfg) {
        this._sym             = cfg.sym             || '₹';
        this._dec             = cfg.dec             || 2;
        this._limit           = cfg.limit           || 10;
        this._filter          = {};
        this._pageNo          = 1;
        this._dir             = 'All';   // 'All' | 'In' | 'Out'
        this._payModeFilter   = cfg.payModeFilter   || null;
        this._createdByFilter = cfg.createdByFilter || null;

        if (cfg.initStats) {
            this._renderStats(cfg.initStats);
        }
    }

    // ── Format a numeric value as currency ──────────────────────────────────
    PaymentsPage.prototype.fmt = function (v) {
        return this._sym + ' ' + parseFloat(v || 0).toLocaleString('en-IN', {
            minimumFractionDigits: this._dec,
            maximumFractionDigits: this._dec
        });
    };

    // ── Build merged filter object from all active sources ──────────────────
    PaymentsPage.prototype.getFilter = function () {
        var f = $.extend(
            {},
            this._filter,
            this._payModeFilter   ? this._payModeFilter.getState()   : {},
            this._createdByFilter ? this._createdByFilter.getState() : {}
        );
        if (this._dir !== 'All') {
            f.PaymentDirection = this._dir;
        }
        return f;
    };

    // ── Switch direction pill (All / In / Out) ───────────────────────────────
    PaymentsPage.prototype.setDir = function (dir) {
        this._dir    = dir;
        this._pageNo = 1;
        this.loadData(1);
    };

    // ── Load (or reload) a page of list data ─────────────────────────────────
    PaymentsPage.prototype.loadData = function (pageNo) {
        pageNo       = pageNo || this._pageNo;
        this._pageNo = pageNo;

        var self = this;
        var f    = self.getFilter();

        $.ajax({
            url    : '/payments/getPaymentsPageDetails/' + pageNo,
            method : 'POST',
            data   : { RowLimit: self._limit, Filter: f, [CsrfName]: CsrfToken },
            beforeSend: function () {
                $('#allPaymentsTableBody').html(
                    '<tr><td colspan="8" class="text-center py-4">' +
                    '<span class="spinner-border spinner-border-sm text-primary me-2"></span>' +
                    'Loading…</td></tr>'
                );
            },
            success: function (resp) {
                if (resp.Error) {
                    $('#allPaymentsTableBody').html(
                        '<tr><td colspan="8" class="text-center py-4 text-danger">' +
                        (resp.Message || 'Error loading data') + '</td></tr>'
                    );
                    return;
                }
                $('#allPaymentsTableBody').html(resp.RecordHtmlData);
                $('#allPmtPagination').html(
                    '<span class="text-muted" style="font-size:.78rem;">Total: <strong id="allPmtTotalCount">' +
                    Number(resp.TotalCount).toLocaleString() + '</strong></span>' +
                    (resp.Pagination || '')
                );
                if (resp.Totals) { self._renderFooterTotals(resp.Totals); }
            }
        });

        // Always refresh stats in parallel with the list
        self.loadStats(f);
    };

    // ── Fetch and render live balance stats ───────────────────────────────────
    PaymentsPage.prototype.loadStats = function (f) {
        var self = this;
        f = f || self.getFilter();
        $.ajax({
            url    : '/payments/getStats',
            method : 'POST',
            data   : { Filter: f, [CsrfName]: CsrfToken },
            success: function (resp) {
                if (!resp.Error && resp.Stats) {
                    self._renderStats(resp.Stats);
                }
            }
        });
    };

    // ── Update footer totals row ──────────────────────────────────────────────
    PaymentsPage.prototype._renderFooterTotals = function (totals) {
        var net = (parseFloat(totals.TotalReceived) || 0) - (parseFloat(totals.TotalPaid) || 0);
        $('#allPmtFooterIn').text(this.fmt(totals.TotalReceived || 0));
        $('#allPmtFooterOut').text(this.fmt(totals.TotalPaid    || 0));
        $('#allPmtFooterNet').text(this.fmt(net));
    };

    // ── Render the 3 stats cards ──────────────────────────────────────────────
    PaymentsPage.prototype._renderStats = function (stats) {
        var ci = parseFloat(stats.CashIn  || 0);
        var co = parseFloat(stats.CashOut || 0);
        var bi = parseFloat(stats.BankIn  || 0);
        var bo = parseFloat(stats.BankOut || 0);

        // Balance card
        $('#statCashBalance').text(this.fmt(ci - co));
        $('#statBankBalance').text(this.fmt(bi - bo));
        $('#statNetBalance').text(this.fmt((ci + bi) - (co + bo)));

        // In card
        $('#statCashIn').text(this.fmt(ci));
        $('#statBankIn').text(this.fmt(bi));
        $('#statTotalIn').text(this.fmt(ci + bi));

        // Out card
        $('#statCashOut').text(this.fmt(co));
        $('#statBankOut').text(this.fmt(bo));
        $('#statTotalOut').text(this.fmt(co + bo));
    };

    // ── Reset all filters and reload ──────────────────────────────────────────
    PaymentsPage.prototype.clearFilters = function () {
        this._filter  = {};
        this._dir     = 'All';
        this._pageNo  = 1;
        if (this._payModeFilter)   { this._payModeFilter.reset(); }
        if (this._createdByFilter) { this._createdByFilter.reset(); }
    };

    return PaymentsPage;

}());
