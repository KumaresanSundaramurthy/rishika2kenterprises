// -- Invoices list — module-specific JS --------------------------------------
// Shared utilities (loadTransactionList, debounce, initTooltips) are in common.js
// Date helpers (getDateRange, formatDate) are in /js/common/datefilter.js

// ── Select-all (Pattern 3) state ────────────────────────────────────────────
var _invSelectAllMode = false;
var _invTotalRecords  = 0;
var _invPageCount     = 0;

/**
 * @returns {void}
 */
function _invUpdateSelectAllBanner() {
    var $banner = $('#invSelectAllBanner');
    var $msg    = $('#invSelectAllMsg');
    var $link   = $('#invSelectAllLink');
    var $clear  = $('#invSelectAllClear');

    if (!_invPageCount || !$(ModuleHeader).prop('checked')) {
        $banner.addClass('d-none');
        return;
    }

    if (_invSelectAllMode) {
        $msg.text('All ' + _invTotalRecords + ' invoices are selected.');
        $link.addClass('d-none');
        $clear.removeClass('d-none');
    } else {
        $msg.text('All ' + _invPageCount + ' invoices on this page are selected.');
        $clear.addClass('d-none');
        if (_invTotalRecords > _invPageCount) {
            $link.text('Select all ' + _invTotalRecords + ' invoices?').removeClass('d-none');
        } else {
            $link.addClass('d-none');
            $banner.addClass('d-none');
            return;
        }
    }
    $banner.removeClass('d-none');
}

/**
 * @returns {void}
 */
function _invClearSelectAll() {
    _invSelectAllMode = false;
    $('#invSelectAllBanner').addClass('d-none');
    $('#invSelectAllLink').removeClass('d-none');
    $('#invSelectAllClear').addClass('d-none');
}

/**
 * @returns {void}
 */
function deleteMultipleInvoices() {
    var postData = _invSelectAllMode
        ? { SelectAll: 1, Filter: JSON.stringify(Filter), [CsrfName]: CsrfToken }
        : { 'TransUIDs[]': SelectedUIDs, [CsrfName]: CsrfToken };
    $.ajax({
        url   : '/transactions/deleteMultipleTransactions/103',
        method: 'POST',
        cache : false,
        data  : postData,
        success: function (response) {
            if (response.Error) {
                showAlertMessageSwal('error', '', response.Message);
            } else {
                showToastNotification(response.Message, 'success');
                SelectedUIDs = [];
                _invClearSelectAll();
                hideUIBlock();
                ajaxLoading(0);
                getInvoicesDetails(PageNo, RowLimit, Filter);
            }
        }
    });
}

// -- WhatsApp link handler ---------------------------------------------------
$(document).on('click', '.inv-wa-link', function (e) {
    e.preventDefault();
    var url = $(this).data('wa-url');
    if (url) window.open(url, '_blank');
});

// -- Register smart PDF attach alert when comm modal opens in Email tab -------
$(document).on('comm:switchedToEmail', function (e, moduleUID, recordUID) {
    if (moduleUID !== 103 || !recordUID) return;
    if (typeof _setupCommPdfAlert !== 'function') return;

    var rowData   = (typeof _commRowData !== 'undefined') ? _commRowData : null;
    var docNumber = rowData ? (rowData.docNumber || '') : '';
    var filename  = (docNumber || 'invoice').replace(/[^A-Za-z0-9_.\-]/g, '_') + '.pdf';

    _setupCommPdfAlert(docNumber, function (onSuccess) {
        $.ajax({
            url   : '/invoices/getInvoicePdfBase64',
            method: 'POST',
            data  : { TransUID: recordUID, PaperSize: 'A4', [CsrfName]: CsrfToken },
            success: function (resp) {
                if (resp.Error || !resp.Base64) { onSuccess(null); return; }
                try {
                    var binary = atob(resp.Base64);
                    var bytes  = new Uint8Array(binary.length);
                    for (var i = 0; i < binary.length; i++) { bytes[i] = binary.charCodeAt(i); }
                    var blob = new Blob([bytes], { type: 'application/pdf' });
                    onSuccess(new File([blob], resp.Filename || filename, { type: 'application/pdf' }));
                } catch (ex) {
                    onSuccess(null);
                }
            },
            error: function () { onSuccess(null); }
        });
    });
});

function getInvoicesDetails(pageNo, rowLimit, filter, afterLoad) {
    loadTransactionList({
        url:            '/transactions/getPageDetails/103/',
        tabCountClass:  '.trans-tab-count',
        statusTabClass: '.inv-status-tab',
        errorMessage:   'Failed to load invoices.',
        onSuccess:      function (resp) {
            _invTotalRecords = parseInt(resp.TotalCount) || 0;
            _invPageCount    = $(ModuleTable + ' tbody ' + ModuleRow).length;
            _invUpdateSelectAllBanner();
            if (typeof updateSummaryStats === 'function' && resp.SummaryStats) updateSummaryStats(resp.SummaryStats);
            if (typeof afterLoad === 'function') afterLoad(resp);
        },
    }, pageNo, rowLimit, filter);
}

// -- Payment Details Panel ---------------------------------------------------
(function () {
    var $panel      = $('#payDetailPanel');
    var $body       = $('#payDetailBody');
    var $title      = $('#payPanelTitle');
    var openUID     = null;

    function openPanel($trigger) {
        var transUID = $trigger.data('trans-uid');
        var transNum = $trigger.data('trans-num') || '';

        var rect = $trigger[0].getBoundingClientRect();
        var panelW = 290;
        var left = rect.left;
        var top  = rect.bottom + 6;

        if (left + panelW + 16 > window.innerWidth) {
            left = window.innerWidth - panelW - 16;
        }

        $title.text(transNum ? 'Payments — ' + transNum : 'Payments');
        $body.html('<div class="text-center py-3"><span class="spinner-border spinner-border-sm text-primary"></span></div>');
        $panel.css({ top: top, left: left }).show();
        openUID = transUID;

        ajaxLoading(0);

        $.ajax({
            url     : '/payments/getPaymentsByTransaction',
            type    : 'GET',
            data    : { TransUID: transUID },
            success : function (resp) {
                ajaxLoading(1);
                if (resp && !resp.Error && resp.Payments && resp.Payments.length) {
                    $body.html(buildPaymentHtml(resp.Payments));
                } else {
                    $body.html('<p class="text-muted mb-0" style="font-size:.8rem;">No payments found.</p>');
                }
            },
            error   : function () {
                ajaxLoading(1);
                $body.html('<p class="text-danger mb-0" style="font-size:.8rem;">Failed to load payments.</p>');
            }
        });
    }

    function closePanel() {
        $panel.hide();
        openUID = null;
    }

    $(document).on('click', '.pay-mode-clickable', function (e) {
        if ($(e.target).closest('.transPayAttachBtn').length) return;
        e.stopPropagation();
        var transUID = $(this).data('trans-uid');
        if (openUID === transUID) { closePanel(); return; }
        openPanel($(this));
    });

    $(document).on('click', '#payPanelClose', function (e) {
        e.stopPropagation();
        closePanel();
    });

    $(document).on('click', function (e) {
        if ($panel.is(':visible') && !$(e.target).closest('#payDetailPanel, .pay-mode-clickable').length) {
            closePanel();
        }
    });

    $(document).on('keydown', function (e) {
        if (e.key === 'Escape') closePanel();
    });

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
                date  = ('0' + d.getDate()).slice(-2) + '-' +
                        ('0' + (d.getMonth() + 1)).slice(-2) + '-' +
                        d.getFullYear();
            }

            html += '<div class="d-flex justify-content-between align-items-start gap-2">';
            html += '  <div style="min-width:0;">';
            html += '    <div style="font-size:.83rem;font-weight:600;color:#696cff;">&#8377;' + amt + '</div>';
            html += '    <div style="font-size:.75rem;color:#566a7f;">' + mode + '</div>';
            if (date || ref) {
                html += '  <div style="font-size:.72rem;color:#aaa;margin-top:1px;">';
                if (date) html += date;
                if (date && ref) html += '&nbsp;&nbsp;';
                if (ref)  html += ref;
                html += '  </div>';
            }
            html += '  </div>';
            html += '  <a href="/payments" class="btn btn-icon btn-sm" style="color:#696cff;flex-shrink:0;" title="' + t('vm_view_payments', 'View Payments') + '"><i class="bx bx-show fs-6"></i></a>';
            html += '</div>';
        });
        return html;
    }
}());

// ── Auto-print after Save & Print from the invoice form ───────────────────────
(function () {
    var raw = null;
    try { raw = sessionStorage.getItem('r2k_pendingPrint'); } catch (e) {}
    if (!raw) return;
    try { sessionStorage.removeItem('r2k_pendingPrint'); } catch (e) {}
    var data = null;
    try { data = JSON.parse(raw); } catch (e) {}
    if (!data || !data.transUID || !data.moduleUID) return;
    $(function () {
        setTimeout(function () {
            var fmt = data.format || 'a4';
            if (fmt === 'thermal') {
                if (typeof openThermalPrintByUID === 'function') {
                    openThermalPrintByUID(data.transUID, data.moduleUID, null);
                }
            } else {
                if (typeof openA4PrintByUID === 'function') {
                    openA4PrintByUID(data.transUID, data.moduleUID, fmt, null);
                }
            }
        }, 600);
    });
}());
