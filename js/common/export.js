/**
 * js/common/export.js
 * Shared export handler for all transaction list pages.
 *
 * Usage on any transaction page:
 *   initExport({ moduleUID: 103, getFilters: function() { return Filter; } });
 *
 * The export button partial (views/common/partials/export_btn.php) renders the
 * dropdown with the four .r2k-export-btn items this file listens for.
 */
function initExport(opts) {
    opts       = opts || {};
    var mid    = opts.moduleUID;
    var getFlt = opts.getFilters || function () { return {}; };

    $(document).on('click', '.r2k-export-btn[data-format]', function () {
        var format = $(this).data('format');
        var filter = getFlt();

        if (format === 'print') {
            _exportPrint(mid, filter);
        } else {
            _exportDownload(mid, format, filter);
        }
    });
}

// ── Print: fetch HTML from server, open in new tab, trigger print dialog ─────
function _exportPrint(moduleUID, filter) {
    var payload = _buildPayload(moduleUID, 'print', filter);
    $.ajax({
        url    : '/exports/exportData',
        method : 'POST',
        data   : payload,
    }).done(function (resp) {
        if (resp.Error) { _exportAlert(resp.Message); return; }
        var win = window.open('', '_blank');
        if (!win) { _exportAlert('Pop-up blocked. Please allow pop-ups for this site.'); return; }
        win.document.open();
        win.document.write(resp.Html);
        win.document.close();
    }).fail(function () {
        _exportAlert('Print failed. Please try again.');
    });
}

// ── CSV / Excel / PDF: POST via hidden form so browser triggers download ──────
function _exportDownload(moduleUID, format, filter) {
    var $form = $('<form method="POST" style="display:none">')
        .attr('action', '/exports/exportData');

    $form.append(_hidden('moduleUID', moduleUID));
    $form.append(_hidden('format',    format));
    $form.append(_hidden(window.CsrfName, window.CsrfToken));

    $.each(filter, function (key, val) {
        if (val !== null && val !== undefined && val !== '') {
            $form.append(_hidden('Filter[' + key + ']', val));
        }
    });

    $('body').append($form);
    $form[0].submit();
    setTimeout(function () { $form.remove(); }, 3000);
}

// ── Helpers ───────────────────────────────────────────────────────────────────
function _buildPayload(moduleUID, format, filter) {
    var data = { moduleUID: moduleUID, format: format };
    data[window.CsrfName] = window.CsrfToken;
    $.each(filter, function (k, v) {
        if (v !== null && v !== undefined && v !== '') {
            data['Filter[' + k + ']'] = v;
        }
    });
    return data;
}

function _hidden(name, value) {
    return $('<input type="hidden">').attr('name', name).val(value);
}

function _exportAlert(msg) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({ icon: 'error', title: 'Export Error', text: msg, confirmButtonColor: '#1a73e8' });
    } else {
        alert(msg);
    }
}
