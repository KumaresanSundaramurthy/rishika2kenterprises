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
    opts        = opts || {};
    var _getMid = opts.getModuleUID
        ? opts.getModuleUID
        : function () { return opts.moduleUID; };
    var getFlt  = opts.getFilters || function () { return {}; };

    $(document).on('click', '.r2k-export-btn[data-format]', function () {
        var format = $(this).data('format');
        var mid    = _getMid();
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
        url    : global_base_url + 'exports/exportData',
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

// ── CSV / Excel / PDF: AJAX blob download with Bearer auth ────────────────────
function _exportDownload(moduleUID, format, filter) {
    var payload = _buildPayload(moduleUID, format, filter);

    var xhr = new XMLHttpRequest();
    xhr.open('POST', global_base_url + 'exports/exportData', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.responseType = 'blob';

    xhr.onload = function () {
        if (xhr.status !== 200) { _exportAlert('Export failed. Please try again.'); return; }
        var disposition = xhr.getResponseHeader('Content-Disposition') || '';
        var fileMatch   = disposition.match(/filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/);
        var fileName    = fileMatch ? fileMatch[1].replace(/['"]/g, '') : ('export.' + format);
        var url  = URL.createObjectURL(xhr.response);
        var link = document.createElement('a');
        link.href = url; link.download = fileName;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        setTimeout(function () { URL.revokeObjectURL(url); }, 3000);
    };
    xhr.onerror = function () { _exportAlert('Export failed. Please try again.'); };

    /* Serialize payload to form-encoded string */
    var body = Object.keys(payload).map(function (k) {
        return encodeURIComponent(k) + '=' + encodeURIComponent(payload[k]);
    }).join('&');
    xhr.send(body);
}

// ── Helpers ───────────────────────────────────────────────────────────────────
function _buildPayload(moduleUID, format, filter) {
    var data = {
        moduleUID: moduleUID,
        format   : format,
        orgId    : parseInt(JwtData.Org.OrgUID, 10),
        userId   : parseInt(JwtData.User.UserUID, 10),
        branchId : parseInt(JwtData.Org.BranchUID, 10),
    };
    $.each(filter, function (k, v) {
        if (v !== null && v !== undefined && v !== '') {
            data['Filter[' + k + ']'] = v;
        }
    });
    return data;
}


function _exportAlert(msg) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({ icon: 'error', title: t('swal_export_error', 'Export Error'), text: msg, confirmButtonColor: '#1a73e8' });
    } else {
        alert(msg);
    }
}
