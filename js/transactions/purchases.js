// ── Purchases list — module-specific JS ──────────────────────────────────────
// Shared utilities (loadTransactionList, debounce, initTooltips) are in common.js
// Date helpers (getDateRange, formatDate) are in /js/common/datefilter.js

function getPurchasesDetails(pageNo, rowLimit, filter) {
    loadTransactionList({
        url:            '/purchases/getPurchasesPageDetails/',
        tabCountClass:  '.purch-tab-count',
        statusTabClass: '.purch-status-tab',
        errorMessage:   'Failed to load purchase bills.',
    }, pageNo, rowLimit, filter);
}

function searchVendors(key) {
    var $el         = $('#' + key);
    var wrapId      = 'vendorGroup_' + key;
    var vendorCache = null; // local — freed after vendor is selected

    if (!$el.closest('.vendor-search-group').length) {
        $el.wrap('<div class="input-group input-group-sm input-group-merge vendor-search-group" id="' + wrapId + '"></div>');
        $('<span class="input-group-text p-2"><i class="icon-base bx bx-search"></i></span>').insertBefore($el);
    }

    $el.select2({
        placeholder: 'Search Vendor by Name, Mobile, GSTIN, Company.',
        minimumInputLength: 0,
        allowClear: true,
        dropdownParent: $('#' + wrapId),
        escapeMarkup: function (markup) { return markup; },
        templateResult: function (d) {
            if (d.loading || !d.name) return d.text;
            var nameHtml   = d.name + (d.mobile ? ' <span style="color:#6c757d;font-weight:400;font-size:.85rem;">(' + d.mobile + ')</span>' : '');
            var balHtml    = _custBalanceHtml(d.balance, d.balanceType);
            var line2Parts = [];
            if (d.area)    line2Parts.push('<span style="color:#8a8d93;font-style:italic;font-size:.78rem;">'         + d.area        + '</span>');
            if (d.company) line2Parts.push('<span style="color:#696cff;font-style:italic;font-size:.78rem;">(' + d.company + ')</span>');
            var line2Html  = line2Parts.length
                ? line2Parts.join(' ')
                : '<span style="font-size:.78rem;">&nbsp;</span>';
            return $(
                '<div style="display:flex;align-items:flex-start;gap:6px;">' +
                    '<div style="flex:1;min-width:0;">' +
                        '<div style="font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">' + nameHtml + '</div>' +
                        '<div style="margin-top:2px;">' + line2Html + '</div>' +
                    '</div>' +
                    '<div style="text-align:right;white-space:nowrap;flex-shrink:0;padding-top:1px;">' + balHtml + '</div>' +
                '</div>'
            );
        },
        templateSelection: function (d) {
            if (!d.id) return d.text;
            if (d.name) return d.area ? d.name + ' (' + d.area + ')' : d.name;
            return d.text;
        },
        ajax: {
            transport: function (params, success, failure) {
                var term     = ((params.data && params.data.term) || '').toLowerCase().trim();
                var page     = (params.data && params.data.page) || 1;
                var pageSize = 15;

                function _paginate(list) {
                    var filtered = term
                        ? list.filter(function (v) {
                            return (v.name    && v.name.toLowerCase().includes(term))    ||
                                   (v.area    && v.area.toLowerCase().includes(term))    ||
                                   (v.mobile  && v.mobile.toLowerCase().includes(term))  ||
                                   (v.gstin   && v.gstin.toLowerCase().includes(term))   ||
                                   (v.company && v.company.toLowerCase().includes(term));
                          })
                        : list;
                    var start = (page - 1) * pageSize;
                    AjaxLoading = 1;
                    success({ Lists: filtered.slice(start, start + pageSize), more: (start + pageSize) < filtered.length });
                }

                // Already loaded — paginate instantly
                if (vendorCache) { _paginate(vendorCache); return; }

                // Upstash direct (pages that inject _upstashUrl / _vendorCacheKey)
                if (typeof _upstashUrl !== 'undefined' && _upstashUrl && typeof _vendorCacheKey !== 'undefined' && _vendorCacheKey) {
                    $.ajax({
                        url: _upstashUrl + '/get/' + encodeURIComponent(_vendorCacheKey),
                        headers: { 'Authorization': 'Bearer ' + _upstashReadToken },
                        dataType: 'json',
                        success: function (resp) {
                            var raw = resp.result;
                            var map = typeof raw === 'string' ? JSON.parse(raw) : (raw || {});
                            vendorCache = Object.keys(map).map(function (uid) {
                                var v = map[uid];
                                return {
                                    id:          parseInt(v.VendorUID || uid, 10),
                                    text:        v.Area ? (v.Name + ' (' + v.Area + ')') : (v.Name || ''),
                                    name:        v.Name             || '',
                                    area:        v.Area             || '',
                                    mobile:      v.MobileNumber     || '',
                                    gstin:       v.GSTIN            || '',
                                    company:     v.CompanyName      || '',
                                    balance:     parseFloat(v.OpeningBalance || 0),
                                    balanceType: v.OpeningBalType   || 'Debit',
                                    lastTxAt:    v.LastTransactionAt || '',
                                };
                            });
                            // Recent vendors first (newest → oldest), then rest A–Z
                            vendorCache.sort(function (a, b) {
                                if (a.lastTxAt && b.lastTxAt) return new Date(b.lastTxAt) - new Date(a.lastTxAt);
                                if (a.lastTxAt) return -1;
                                if (b.lastTxAt) return 1;
                                return a.name.localeCompare(b.name);
                            });
                            _paginate(vendorCache);
                        },
                        error: function () {
                            // Upstash failed — fall back to DB search
                            $.ajax({
                                url: '/transactions/searchVendors',
                                dataType: 'json',
                                data: { term: (params.data && params.data.term) || '', type: 'public' },
                                success: function (data) { AjaxLoading = 1; success(data); },
                                error: function () { AjaxLoading = 1; failure(); }
                            });
                        }
                    });
                } else {
                    // Fallback for pages not yet migrated — original server search
                    $.ajax({
                        url: '/transactions/searchVendors',
                        dataType: 'json',
                        data: { term: (params.data && params.data.term) || '', type: 'public' },
                        success: function (data) { AjaxLoading = 1; success(data); },
                        error: function () { AjaxLoading = 1; failure(); }
                    });
                }
            },
            delay: 250,
            data: function (params) {
                AjaxLoading = 0;
                return { term: params.term, page: params.page || 1 };
            },
            processResults: function (data) {
                AjaxLoading = 1;
                return { results: data.Lists, pagination: { more: data.more || false } };
            },
            cache: false
        }
    }).on('select2:select', function () {
        vendorCache = null; // free memory — no longer needed after selection
    }).on('select2:close', function () {
        AjaxLoading = 1;
    });
}

// ── WhatsApp link handler ─────────────────────────────────────────────────────
$(document).on('click', '.purch-wa-link', function (e) {
    e.preventDefault();
    var url = $(this).data('wa-url');
    if (url) window.open(url, '_blank');
});

// ── Auto-attach purchase PDF when comm modal switches to Email tab ────────────
$(document).on('comm:switchedToEmail', function (e, moduleUID, recordUID) {
    if (moduleUID !== 105 || !recordUID || _commPdfAutoAttached) return;

    _commPdfAutoAttached = true;

    setTimeout(function () {
        _initCommDropzone();

        $.ajax({
            url   : '/purchases/getPurchasePdfBase64',
            method: 'POST',
            data  : { TransUID: recordUID, PaperSize: 'A4', [CsrfName]: CsrfToken },
            success: function (resp) {
                if (resp.Error || !resp.Base64) { _commPdfAutoAttached = false; return; }
                if (!_commDropzone) { _commPdfAutoAttached = false; return; }
                try {
                    var binary = atob(resp.Base64);
                    var bytes  = new Uint8Array(binary.length);
                    for (var i = 0; i < binary.length; i++) { bytes[i] = binary.charCodeAt(i); }
                    var blob = new Blob([bytes], { type: 'application/pdf' });
                    var file = new File([blob], resp.Filename || 'purchase.pdf', { type: 'application/pdf' });
                    _commDropzone.addFile(file);
                } catch (ex) {
                    _commPdfAutoAttached = false;
                }
            },
            error: function () { _commPdfAutoAttached = false; }
        });
    }, 150);
});

// ── Payment Details Panel ─────────────────────────────────────────────────────
(function () {
    var $panel  = $('#payDetailPanel');
    var $body   = $('#payDetailBody');
    var $title  = $('#payPanelTitle');
    var openUID = null;

    function openPanel($trigger) {
        var transUID = $trigger.data('trans-uid');
        var transNum = $trigger.data('trans-num') || '';

        var rect   = $trigger[0].getBoundingClientRect();
        var panelW = 290;
        var left   = rect.left;
        var top    = rect.bottom + 6;
        if (left + panelW + 16 > window.innerWidth) left = window.innerWidth - panelW - 16;

        $title.text(transNum ? 'Payments — ' + transNum : 'Payments');
        $body.html('<div class="text-center py-3"><span class="spinner-border spinner-border-sm text-primary"></span></div>');
        $panel.css({ top: top, left: left }).show();
        openUID = transUID;
        AjaxLoading = 0;

        $.ajax({
            url  : '/payments/getPaymentsByTransaction',
            type : 'GET',
            data : { TransUID: transUID },
            success: function (resp) {
                AjaxLoading = 1;
                if (resp && !resp.Error && resp.Payments && resp.Payments.length) {
                    $body.html(buildPaymentHtml(resp.Payments));
                } else {
                    $body.html('<p class="text-muted mb-0" style="font-size:.8rem;">No payments found.</p>');
                }
            },
            error: function () {
                AjaxLoading = 1;
                $body.html('<p class="text-danger mb-0" style="font-size:.8rem;">Failed to load payments.</p>');
            }
        });
    }

    function closePanel() { $panel.hide(); openUID = null; }

    $(document).on('click', '.pay-mode-clickable', function (e) {
        if ($(e.target).closest('.purchPayAttachBtn').length) return;
        e.stopPropagation();
        var transUID = $(this).data('trans-uid');
        if (openUID === transUID) { closePanel(); return; }
        openPanel($(this));
    });

    $(document).on('click', '#payPanelClose', function (e) { e.stopPropagation(); closePanel(); });

    $(document).on('click', function (e) {
        if ($panel.is(':visible') && !$(e.target).closest('#payDetailPanel, .pay-mode-clickable').length) closePanel();
    });

    $(document).on('keydown', function (e) { if (e.key === 'Escape') closePanel(); });

    function buildPaymentHtml(payments) {
        var html = '';
        payments.forEach(function (p, i) {
            if (i > 0) html += '<hr style="margin:8px 0;border-color:#f0f0f0;">';
            var amt  = parseFloat(p.Amount || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            var mode = p.PaymentTypeName || '—';
            var ref  = p.ReferenceNo || '';
            var date = '';
            if (p.CreatedOn) {
                var d = new Date(p.CreatedOn.replace(' ', 'T'));
                date  = ('0' + d.getDate()).slice(-2) + '-' + ('0' + (d.getMonth() + 1)).slice(-2) + '-' + d.getFullYear();
            }
            html += '<div class="d-flex justify-content-between align-items-start gap-2">';
            html += '  <div style="min-width:0;">';
            html += '    <div style="font-size:.83rem;font-weight:600;color:#6f42c1;">&#8377;' + amt + '</div>';
            html += '    <div style="font-size:.75rem;color:#566a7f;">' + mode + '</div>';
            if (date || ref) {
                html += '  <div style="font-size:.72rem;color:#aaa;margin-top:1px;">';
                if (date) html += date;
                if (date && ref) html += '&nbsp;&nbsp;';
                if (ref)  html += ref;
                html += '  </div>';
            }
            html += '  </div>';
            html += '  <a href="/payments" class="btn btn-icon btn-sm" style="color:#6f42c1;flex-shrink:0;" title="View Payments"><i class="bx bx-show fs-6"></i></a>';
            html += '</div>';
        });
        return html;
    }
}());
