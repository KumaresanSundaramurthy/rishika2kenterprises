// ── Invoices list — module-specific JS ───────────────────────────────────────
// Shared utilities (loadTransactionList, debounce, initTooltips) are in common.js
// Date helpers (getDateRange, formatDate) are in /js/common/datefilter.js

function getInvoicesDetails(pageNo, rowLimit, filter) {
    loadTransactionList({
        url:            '/invoices/getInvoicesPageDetails/',
        tabCountClass:  '.inv-tab-count',
        statusTabClass: '.inv-status-tab',
        errorMessage:   'Failed to load invoices.',
    }, pageNo, rowLimit, filter);
}

// ── Payment Details Panel ─────────────────────────────────────────────────────
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

        $.ajax({
            url     : '/payments/getPaymentsByTransaction',
            type    : 'GET',
            data    : { TransUID: transUID },
            success : function (resp) {
                if (resp && !resp.Error && resp.Payments && resp.Payments.length) {
                    $body.html(buildPaymentHtml(resp.Payments));
                } else {
                    $body.html('<p class="text-muted mb-0" style="font-size:.8rem;">No payments found.</p>');
                }
            },
            error   : function () {
                $body.html('<p class="text-danger mb-0" style="font-size:.8rem;">Failed to load payments.</p>');
            }
        });
    }

    function closePanel() {
        $panel.hide();
        openUID = null;
    }

    $(document).on('click', '.pay-mode-clickable', function (e) {
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
            html += '    <div style="font-size:.83rem;font-weight:600;color:#696cff;">₹' + amt + '</div>';
            html += '    <div style="font-size:.75rem;color:#566a7f;">' + mode + '</div>';
            if (date || ref) {
                html += '  <div style="font-size:.72rem;color:#aaa;margin-top:1px;">';
                if (date) html += date;
                if (date && ref) html += '&nbsp;&nbsp;';
                if (ref)  html += ref;
                html += '  </div>';
            }
            html += '  </div>';
            html += '  <a href="/payments" class="btn btn-icon btn-sm" style="color:#696cff;flex-shrink:0;" title="View Payments"><i class="bx bx-show fs-6"></i></a>';
            html += '</div>';
        });
        return html;
    }
}());
