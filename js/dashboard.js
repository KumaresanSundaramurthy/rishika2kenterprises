// Dashboard — all widgets loaded via 8 parallel API calls on page load

var _dashChart      = null;
var _dashActive     = 'sales';
var _dashChartSeq   = 0;

/* ── Helpers ──────────────────────────────────────────────────────────── */

/**
 * @param {*} s
 * @returns {string}
 */
function _dEsc(s) {
    return String(s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

/**
 * @param {number} v
 * @returns {string}
 */
function _dFmt(v) {
    var cur = (JwtData.GenSettings && JwtData.GenSettings.CurrenySymbol) || currencySymbol || '₹';
    var dec = parseInt((JwtData.GenSettings && JwtData.GenSettings.DecimalPoints) || decimalPlaces || 2, 10);
    return cur + ' ' + Number(v || 0).toLocaleString('en-IN', {
        minimumFractionDigits: dec,
        maximumFractionDigits: dec
    });
}

/**
 * @param {number} curr
 * @param {number} prev
 * @returns {string}
 */
function _dPctBadge(curr, prev) {
    var pct;
    if (!prev || prev === 0) {
        pct = curr > 0 ? 100 : 0;
    } else {
        pct = Math.round(((curr - prev) / prev) * 1000) / 10;
    }
    if (pct > 0) {
        return '<span class="kpi-badge up"><i class="bx bx-up-arrow-alt"></i>' + pct + '%</span>' +
               '<span class="text-muted ms-1">vs last month</span>';
    }
    if (pct < 0) {
        return '<span class="kpi-badge down"><i class="bx bx-down-arrow-alt"></i>' + Math.abs(pct) + '%</span>' +
               '<span class="text-muted ms-1">vs last month</span>';
    }
    return '<span class="kpi-badge flat">—</span><span class="text-muted ms-1">same as last month</span>';
}

/**
 * @param {number} offsetDays
 * @returns {string}
 */
function _dDayStr(offsetDays) {
    var d = new Date();
    d.setDate(d.getDate() + offsetDays);
    return d.toISOString().slice(0, 10);
}

/**
 * @param {number} offsetMonths
 * @returns {string}
 */
function _dMonthStr(offsetMonths) {
    var d = new Date();
    d.setDate(1);
    d.setMonth(d.getMonth() + offsetMonths);
    return d.toISOString().slice(0, 10);
}

/**
 * @param {string} isoDate
 * @returns {string}
 */
function _dFmtMonth(isoDate) {
    var months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    var d = new Date(isoDate + 'T00:00:00');
    return months[d.getMonth()] + ' ' + d.getFullYear();
}

/**
 * @param {string}  hex
 * @param {number}  alpha
 * @returns {string}
 */
function _dHexRgba(hex, alpha) {
    var r = parseInt(hex.slice(1, 3), 16);
    var g = parseInt(hex.slice(3, 5), 16);
    var b = parseInt(hex.slice(5, 7), 16);
    return 'rgba(' + r + ',' + g + ',' + b + ',' + alpha + ')';
}

/**
 * Build labels + values arrays for the chart from ChartData rows.
 *
 * @param {Array}  rows
 * @param {string} type
 * @returns {{ labels: string[], values: number[], label: string, color: string, link: string }}
 */
function _dBuildChartData(rows, type) {
    var map = {};
    $.each(rows || [], function (i, row) { map[row.sale_date] = parseFloat(row.total) || 0; });

    var configs = {
        sales      : { label: 'Sales — Last 30 Days',           color: '#0d6efd', link: '/invoices'  },
        receivable : { label: 'Open Receivable — Last 30 Days', color: '#198754', link: '/invoices'  },
        payable    : { label: 'Purchases — Last 30 Days',       color: '#dc3545', link: '/purchases' },
        monthly    : { label: 'Monthly Sales — Last 12 Months', color: '#f59e0b', link: '/invoices'  }
    };
    var cfg    = configs[type] || configs.sales;
    var labels = [];
    var values = [];

    if (type === 'monthly') {
        for (var m = 11; m >= 0; m--) {
            var ms = _dMonthStr(-m);
            labels.push(_dFmtMonth(ms));
            values.push(map[ms] || 0);
        }
    } else {
        for (var i = 29; i >= 0; i--) {
            var ds = _dDayStr(-i);
            labels.push(formatDateDisplay(ds));
            values.push(map[ds] || 0);
        }
    }

    return { labels: labels, values: values, label: cfg.label, color: cfg.color, link: cfg.link };
}

/* ── Chart init ───────────────────────────────────────────────────────── */

/**
 * @returns {void}
 */
function _dInitChart() {
    var cur = (JwtData.GenSettings && JwtData.GenSettings.CurrenySymbol) || currencySymbol || '₹';
    var dec = parseInt((JwtData.GenSettings && JwtData.GenSettings.DecimalPoints) || decimalPlaces || 2, 10);
    var ctx = document.getElementById('salesChart');
    if (!ctx || typeof Chart === 'undefined') { $('#dashChartOverlay').hide(); return; }
    _dashChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels  : [],
            datasets: [{
                label                       : 'Sales',
                data                        : [],
                borderColor                 : '#0d6efd',
                backgroundColor             : _dHexRgba('#0d6efd', .08),
                borderWidth                 : 2.5,
                pointRadius                 : 2,
                pointHoverRadius            : 6,
                pointHoverBackgroundColor   : '#0d6efd',
                pointHoverBorderColor       : '#fff',
                pointHoverBorderWidth       : 2,
                fill                        : true,
                tension                     : 0.4
            }]
        },
        options: {
            responsive          : true,
            maintainAspectRatio : true,
            animation           : { duration: 350, easing: 'easeInOutQuart' },
            plugins: {
                legend : { display: false },
                tooltip: {
                    backgroundColor : 'rgba(15,23,42,.9)',
                    titleColor      : '#94a3b8',
                    bodyColor       : '#f1f5f9',
                    padding         : 10,
                    cornerRadius    : 8,
                    callbacks       : {
                        label: function (c) {
                            return ' ' + cur + ' ' + Number(c.parsed.y).toLocaleString('en-IN', {
                                minimumFractionDigits: dec,
                                maximumFractionDigits: dec
                            });
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid  : { display: false },
                    ticks : { maxTicksLimit: 7, font: { size: 10 }, color: '#94a3b8' }
                },
                y: {
                    grid  : { color: 'rgba(0,0,0,0)', drawBorder: false },
                    ticks : {
                        font: { size: 10 }, color: '#94a3b8',
                        callback: function (v) {
                            return cur + Number(v).toLocaleString('en-IN');
                        }
                    }
                }
            }
        }
    });
}

/**
 * @param {string} type
 * @returns {void}
 */
function _dApplyChart(type) {
    var reqSeq = ++_dashChartSeq;
    $('#dashChartOverlay').show();

    $.ajax({
        url      : global_services_url + 'api/dashboard/getChart',
        method   : 'GET',
        dataType : 'json',
        headers  : { Authorization: 'Bearer ' + JwtToken },
        data     : {
            orgId    : parseInt(JwtData.Org.OrgUID,    10),
            branchId : parseInt(JwtData.Org.BranchUID, 10) || 1,
            type     : type
        },
        success : function (r) {
            if (reqSeq !== _dashChartSeq) return;
            if (r.Error) { $('#dashChartOverlay').hide(); return; }

            var cd    = _dBuildChartData(r.ChartData || [], type);
            var color = cd.color;

            if (_dashChart) {
                _dashChart.data.labels                                = cd.labels;
                _dashChart.data.datasets[0].data                      = cd.values;
                _dashChart.data.datasets[0].borderColor               = color;
                _dashChart.data.datasets[0].backgroundColor           = _dHexRgba(color, .08);
                _dashChart.data.datasets[0].pointHoverBackgroundColor = color;
                _dashChart.update();
            }

            $('#chartTitle').text(cd.label);
            $('#chartDot').css('background', color);
            $('#chartViewAllLink').attr('href', cd.link);
            $('#dashChartOverlay').hide();
        },
        error : function () {
            if (reqSeq !== _dashChartSeq) return;
            $('#dashChartOverlay').hide();
        }
    });
}

/* ── Widget fill functions ────────────────────────────────────────────── */

/**
 * Response keys live at root level: r.TodaySales, r.MonthlyComparison, etc.
 *
 * @param {object} r
 * @returns {void}
 */
function _dFillKpis(r) {
    var ts = r.TodaySales              || {};
    var mc = r.MonthlyComparison       || {};
    var rc = parseFloat(r.TotalReceivable || 0);
    var py = parseFloat(r.TotalPayable    || 0);
    var tp = r.TodayPurchases          || {};
    var mp = r.MonthlyPurchasesComparison || {};

    var tsTotal = parseFloat(ts.total || ts.Total || 0);
    var tsCount = parseInt(ts.count   || ts.Count || 0, 10);
    var mcThis  = parseFloat(mc.this_month || mc.ThisMonth || 0);
    var mcLast  = parseFloat(mc.last_month || mc.LastMonth || 0);
    var tpTotal = parseFloat(tp.total || tp.Total || 0);
    var tpCount = parseInt(tp.count   || tp.Count || 0, 10);
    var mpThis  = parseFloat(mp.this_month || mp.ThisMonth || 0);
    var mpLast  = parseFloat(mp.last_month || mp.LastMonth || 0);

    $('#kpiTodaySalesVal').text(_dFmt(tsTotal));
    $('#kpiTodaySalesSub').text(tsCount + ' invoice' + (tsCount !== 1 ? 's' : '') + ' today');
    $('#kpiMonthSalesVal').text(_dFmt(mcThis));
    $('#kpiMonthSalesSub').html(_dPctBadge(mcThis, mcLast));
    $('#kpiReceivableVal').text(_dFmt(rc));
    $('#kpiPayableVal').text(_dFmt(py));
    $('#kpiTodayPurchVal').text(_dFmt(tpTotal));
    $('#kpiTodayPurchSub').text(tpCount + ' bill' + (tpCount !== 1 ? 's' : '') + ' today');
    $('#kpiMonthPurchVal').text(_dFmt(mpThis));
    $('#kpiMonthPurchSub').html(_dPctBadge(mpThis, mpLast));
}

/**
 * @param {object} r
 * @returns {void}
 */
function _dFillPending(r) {
    var pc = r.PendingCounts || r;
    $('#pendDraftCount').text(pc.draft_count   || pc.DraftCount   || 0);
    $('#pendOpenInv').text(pc.open_invoices     || pc.OpenInvoices || 0);
    $('#pendOpenPOs').text(pc.open_pos          || pc.OpenPOs      || 0);
    $('#pendOpenPurch').text(pc.open_purchases  || pc.OpenPurchases|| 0);
}

/**
 * @param {Array} rows
 * @returns {void}
 */
function _dFillOverdue(rows) {
    $('#overdueCount').text(rows.length);
    if (!rows.length) {
        $('#overdueBody').html(
            '<div class="text-center text-muted py-4" style="font-size:var(--r2k-text-82);">No overdue invoices</div>'
        );
        return;
    }
    var html = '';
    $.each(rows, function (i, inv) {
        html += '<div class="d-flex align-items-center justify-content-between px-3 py-2" style="border-bottom:1px solid #f0f0f0;">' +
            '<div>' +
                '<div style="font-size:var(--r2k-text-82);font-weight:600;color:#0d6efd;">' + _dEsc(inv.UniqueNumber || '—') + '</div>' +
                '<div style="font-size:var(--r2k-text-sm);color:#6c757d;">' + _dEsc(inv.PartyName || '—') + '</div>' +
                '<div style="font-size:var(--r2k-text-xs);color:#dc3545;">Due: ' + (inv.ValidityDate ? formatDateDisplay(inv.ValidityDate) : '—') + '</div>' +
            '</div>' +
            '<div class="text-end">' +
                '<div style="font-size:var(--r2k-text-82);font-weight:700;color:#dc3545;">' + _dFmt(inv.BalanceAmount) + '</div>' +
            '</div>' +
        '</div>';
    });
    $('#overdueBody').html(html);
}

/**
 * @param {Array} customers
 * @param {Array} vendors
 * @returns {void}
 */
function _dFillParties(customers, vendors) {
    if (!customers.length) {
        $('#topCustBody').html('<div class="text-center text-muted py-4" style="font-size:var(--r2k-text-82);">No outstanding customers</div>');
    } else {
        var ch = '';
        $.each(customers, function (i, c) {
            var initial = (c.Name || 'X').trim().charAt(0).toUpperCase();
            ch += '<div class="d-flex align-items-center gap-2 px-3 py-2" style="border-bottom:1px solid #f0f0f0;">' +
                '<div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" ' +
                     'style="width:32px;height:32px;background:#e8f0fe;color:#0d6efd;font-size:var(--r2k-text-sm);font-weight:700;">' + _dEsc(initial) + '</div>' +
                '<div class="flex-grow-1 min-width-0">' +
                    '<div style="font-size:var(--r2k-text-82);font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">' + _dEsc(c.Name || '—') + '</div>' +
                    (c.MobileNumber ? '<div style="font-size:var(--r2k-text-70);color:#6c757d;">' + _dEsc(c.MobileNumber) + '</div>' : '') +
                '</div>' +
                '<div class="fw-bold text-end flex-shrink-0" style="font-size:var(--r2k-text-82);color:#198754;">' + _dFmt(c.PendingBalance) + '</div>' +
            '</div>';
        });
        $('#topCustBody').html(ch);
    }

    if (!vendors.length) {
        $('#topVendBody').html('<div class="text-center text-muted py-4" style="font-size:var(--r2k-text-82);">No outstanding vendors</div>');
    } else {
        var vh = '';
        $.each(vendors, function (i, v) {
            var initial = (v.Name || 'X').trim().charAt(0).toUpperCase();
            vh += '<div class="d-flex align-items-center gap-2 px-3 py-2" style="border-bottom:1px solid #f0f0f0;">' +
                '<div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 vend-avatar">' + _dEsc(initial) + '</div>' +
                '<div class="flex-grow-1 min-width-0">' +
                    '<div style="font-size:var(--r2k-text-82);font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">' + _dEsc(v.Name || '—') + '</div>' +
                    (v.MobileNumber ? '<div style="font-size:var(--r2k-text-70);color:#6c757d;">' + _dEsc(v.MobileNumber) + '</div>' : '') +
                '</div>' +
                '<div class="fw-bold text-end flex-shrink-0 vend-balance">' + _dFmt(v.PendingBalance) + '</div>' +
            '</div>';
        });
        $('#topVendBody').html(vh);
    }
}

/**
 * @param {Array} rows
 * @returns {void}
 */
function _dFillRecentTxns(rows) {
    if (!rows.length) {
        $('#recentTxnBody').html('<tr><td colspan="6" class="text-center text-muted py-4">No transactions yet</td></tr>');
        return;
    }
    var statusColors = {
        Issued   : '#0d6efd', Paid      : '#198754',
        Partial  : '#f59e0b', Pending   : '#6c757d',
        Accepted : '#198754', Converted : '#6f42c1'
    };
    var html = '';
    $.each(rows, function (i, txn) {
        var color = statusColors[txn.DocStatus] || '#6c757d';
        html += '<tr>' +
            '<td class="ps-3" style="white-space:nowrap;">' + (txn.TransDate ? formatDateDisplay(txn.TransDate) : '—') + '</td>' +
            '<td style="color:#0d6efd;font-weight:600;">' + _dEsc(txn.UniqueNumber || '—') + '</td>' +
            '<td style="color:#6c757d;">' + _dEsc(txn.TransType || '—') + '</td>' +
            '<td style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:150px;">' + _dEsc(txn.PartyName || '—') + '</td>' +
            '<td class="text-end pe-3 fw-semibold">' + _dFmt(txn.NetAmount) + '</td>' +
            '<td><span class="badge" style="background:' + color + '1a;color:' + color + ';font-size:var(--r2k-text-xs);">' + _dEsc(txn.DocStatus) + '</span></td>' +
        '</tr>';
    });
    $('#recentTxnBody').html(html);
}

/**
 * @param {Array} rows
 * @returns {void}
 */
function _dFillExpenses(rows) {
    if (!rows.length) {
        $('#expSummaryBody').html('<div class="text-center text-muted py-2" style="font-size:var(--r2k-text-82);">No expenses this month</div>');
        return;
    }
    var totals   = $.map(rows, function (r) { return parseFloat(r.total || r.Total || 0); });
    var expTotal = totals.reduce(function (a, b) { return a + b; }, 0);
    var expMax   = Math.max.apply(null, totals) || 1;

    var html = '<div class="d-flex align-items-center gap-3 mb-3">' +
        '<div>' +
            '<div class="exp-total-lbl text-muted">Total Spent</div>' +
            '<div class="exp-total-val">' + _dFmt(expTotal) + '</div>' +
        '</div>' +
        '<div class="text-muted" style="font-size:var(--r2k-text-75);">| ' + rows.length + ' categor' + (rows.length === 1 ? 'y' : 'ies') + '</div>' +
    '</div>' +
    '<div class="d-flex flex-column gap-2">';

    $.each(rows, function (i, exp) {
        var val = parseFloat(exp.total || exp.Total || 0);
        var pct = Math.round((val / expMax) * 100);
        html += '<div class="d-flex align-items-center gap-2">' +
            '<span class="exp-cat-name">' + _dEsc(exp.category || exp.Category || exp.CategoryName || '—') + '</span>' +
            '<div class="exp-cat-bar"><div class="exp-cat-fill" style="width:' + pct + '%;"></div></div>' +
            '<span class="exp-cat-amt">' + _dFmt(val) + '</span>' +
        '</div>';
    });
    html += '</div>';
    $('#expSummaryBody').html(html);
}

/**
 * @param {Array} rows
 * @returns {void}
 */
function _dFillTopProducts(rows) {
    if (!rows.length) {
        $('#topProductsBody').html('<div class="text-center text-muted py-2" style="font-size:var(--r2k-text-82);">No sales this month</div>');
        return;
    }
    var revenues = $.map(rows, function (r) { return parseFloat(r.revenue || r.Revenue || 0); });
    var revMax   = Math.max.apply(null, revenues) || 1;

    var html = '<div class="d-flex flex-column gap-2">';
    $.each(rows, function (i, prod) {
        var rev = parseFloat(prod.revenue || prod.Revenue || 0);
        var qty = parseFloat(prod.qty_sold || prod.QtySold || prod.Qty || 0);
        var pct = Math.round((rev / revMax) * 100);
        html += '<div class="d-flex align-items-center gap-2">' +
            '<span class="prod-rank">' + (i + 1) + '</span>' +
            '<div class="flex-grow-1 min-width-0">' +
                '<div class="prod-name">' + _dEsc(prod.ProductName || prod.Name || '—') + '</div>' +
                '<div class="prod-bar mt-1"><div class="prod-bar-fill" style="width:' + pct + '%;"></div></div>' +
            '</div>' +
            '<span class="prod-qty">' + Number(qty).toLocaleString('en-IN', { maximumFractionDigits: 0 }) + ' qty</span>' +
            '<span class="prod-rev">' + _dFmt(rev) + '</span>' +
        '</div>';
    });
    html += '</div>';
    $('#topProductsBody').html(html);
}

/* ── Page load ────────────────────────────────────────────────────────── */

$(function () {
    var orgId    = parseInt(JwtData.Org.OrgUID,    10);
    var branchId = parseInt(JwtData.Org.BranchUID, 10) || 1;
    var base     = global_services_url + 'api/dashboard/';
    var auth     = { Authorization: 'Bearer ' + JwtToken };
    var params   = { orgId: orgId, branchId: branchId };

    try { _dInitChart(); } catch (e) { $('#dashChartOverlay').hide(); }

    /* Inject "Last updated" badge into the page-header-left title block. */
    $('.apex-page-header-left > div').last().append(
        '<span class="dash-lu-badge loading" id="dashLastUpdated">' +
        '<i class="bx bx-time-five"></i> Loading&hellip;</span>'
    );

    /* Suppress the global "Processing… Please wait…" overlay — dashboard
       uses its own per-widget spinners instead. */
    ajaxLoading(0);

    var _r1 = $.ajax({
        url: base + 'getKpis', method: 'GET', dataType: 'json', headers: auth, data: params,
        success: function (r) { if (!r.Error) _dFillKpis(r); }
    });

    var _r2 = $.ajax({
        url: base + 'getChart', method: 'GET', dataType: 'json', headers: auth,
        data: $.extend({ type: 'sales' }, params),
        success: function (r) {
            if (!r.Error && _dashChart) {
                var cd = _dBuildChartData(r.ChartData || [], 'sales');
                _dashChart.data.labels           = cd.labels;
                _dashChart.data.datasets[0].data = cd.values;
                _dashChart.update();
            }
            $('#dashChartOverlay').hide();
        },
        error: function () { $('#dashChartOverlay').hide(); }
    });

    var _r3 = $.ajax({
        url: base + 'getPendingCounts', method: 'GET', dataType: 'json', headers: auth, data: params,
        success: function (r) { if (!r.Error) _dFillPending(r); }
    });

    var _r4 = $.ajax({
        url: base + 'getOverdueInvoices', method: 'GET', dataType: 'json', headers: auth, data: params,
        success: function (r) { _dFillOverdue((!r.Error && r.OverdueInvoices) || []); },
        error:   function ()  { _dFillOverdue([]); }
    });

    var _r5 = $.ajax({
        url: base + 'getTopParties', method: 'GET', dataType: 'json', headers: auth, data: params,
        success: function (r) {
            _dFillParties(
                (!r.Error && r.TopCustomers) || [],
                (!r.Error && r.TopVendors)   || []
            );
        },
        error: function () { _dFillParties([], []); }
    });

    var _r6 = $.ajax({
        url: base + 'getRecentTransactions', method: 'GET', dataType: 'json', headers: auth, data: params,
        success: function (r) { _dFillRecentTxns((!r.Error && r.RecentTransactions) || []); },
        error:   function ()  { _dFillRecentTxns([]); }
    });

    var _r7 = $.ajax({
        url: base + 'getExpenseSummary', method: 'GET', dataType: 'json', headers: auth, data: params,
        success: function (r) { _dFillExpenses((!r.Error && r.ExpenseSummary) || []); },
        error:   function ()  { _dFillExpenses([]); }
    });

    var _r8 = $.ajax({
        url: base + 'getTopProducts', method: 'GET', dataType: 'json', headers: auth, data: params,
        success: function (r) { _dFillTopProducts((!r.Error && r.TopProducts) || []); },
        error:   function ()  { _dFillTopProducts([]); }
    });

    /* Restore global overlay + stamp "Last updated" once all 8 calls settle. */
    $.when(_r1, _r2, _r3, _r4, _r5, _r6, _r7, _r8).always(function () {
        ajaxLoading(1);
        $('#dashLastUpdated')
            .removeClass('loading')
            .html('<i class="bx bx-time-five"></i> Last updated: ' + new Date().toLocaleString());
    });

    /* Restore global overlay once all 8 calls settle (success or error). */

    $(document).on('click', '.kpi-tab', function () {
        var metric = $(this).data('metric');
        var color  = $(this).data('color');
        if (metric === _dashActive) return;
        _dashActive = metric;

        $('.kpi-tab').removeClass('active');
        $(this).addClass('active');

        if (_dashChart) {
            _dashChart.data.datasets[0].borderColor               = color;
            _dashChart.data.datasets[0].backgroundColor           = _dHexRgba(color, .08);
            _dashChart.data.datasets[0].pointHoverBackgroundColor = color;
        }

        _dApplyChart(metric);
    });
});
