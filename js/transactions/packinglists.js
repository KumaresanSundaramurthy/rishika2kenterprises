// Packing Lists module JS
// Shared utilities (debounce, initTooltips, generatePagination, timeAgo) are in common/core files

var _plReqSeq = 0;

/**
 * @param {*} s
 * @returns {string}
 */
function _plEsc(s) {
    return String(s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

/**
 * @param {string} dt
 * @returns {string}
 */
function _plTimeAgo(dt) { return timeAgo(dt); }

/**
 * @param {Array}  rows
 * @param {number} offset
 * @returns {string}
 */
function _buildPlHtml(rows, offset) {
    if (!rows || !rows.length) {
        return '<tr><td colspan="8"><div class="d-flex flex-column align-items-center py-5">' +
            '<img src="/assets/img/elements/no-record-found.png" alt="No Records" class="img-fluid mb-3" style="max-height:150px;object-fit:contain;">' +
            '<span class="text-muted mb-3" style="font-size:var(--r2k-text-90);">No packing lists found</span>' +
            '</div></td></tr>';
    }

    var html = '';

    $.each(rows, function (i, r) {
        var serial      = offset + i + 1;
        var transUID    = parseInt(r.TransUID  || 0, 10);
        var moduleUID   = parseInt(r.ModuleUID || 0, 10);
        var srcLabel    = moduleUID === 103 ? 'Invoice' : (moduleUID === 112 ? 'DC' : 'Txn');
        var srcBadge    = moduleUID === 103 ? 'text-bg-primary' : 'text-bg-secondary';
        var uniqueNum   = _plEsc(r.UniqueNumber    || '—');
        var transNum    = _plEsc(r.TransNumber     || r.UniqueNumber || '—');
        var partyName   = _plEsc(r.PartyName       || '—');
        var plDate      = r.TransDate ? formatDateDisplay(r.TransDate) : '—';
        var transporter = _plEsc(r.TransporterName || '—');
        var vehicle     = _plEsc(r.VehicleNumber   || '—');
        var updatedAgo  = _plTimeAgo(r.UpdatedOn);
        var updatedBy   = _plEsc(r.UpdatedBy || '');

        html += '<tr>';

        /* Serial */
        html += '<td class="text-center text-muted" style="font-size:var(--r2k-text-78);">' + serial + '</td>';

        /* PL Number */
        html += '<td><a href="/packing-list/' + transUID + '" class="trans-doc-number fw-semibold">' + uniqueNum + '</a>' +
            (r.TransDate ? '<div class="text-muted" style="font-size:var(--r2k-text-sm);">' + plDate + '</div>' : '') +
            '</td>';

        /* Source Document */
        html += '<td><span class="badge ' + srcBadge + '" style="font-size:var(--r2k-text-xs);">' + srcLabel + '</span>' +
            ' <span style="font-size:var(--r2k-text-82);">' + transNum + '</span></td>';

        /* Party */
        html += '<td style="font-size:var(--r2k-text-85);">' + partyName + '</td>';

        /* PL Date */
        html += '<td style="font-size:var(--r2k-text-82);white-space:nowrap;">' + plDate + '</td>';

        /* Transporter */
        html += '<td style="font-size:var(--r2k-text-82);">' + transporter + '</td>';

        /* Vehicle */
        html += '<td style="font-size:var(--r2k-text-82);">' + vehicle + '</td>';

        /* Actions */
        html += '<td class="text-end"><div class="d-flex gap-1 justify-content-end">' +
            '<a href="/packing-list/' + transUID + '" class="btn btn-sm btn-outline-secondary" ' +
                'data-bs-toggle="tooltip" title="Edit"><i class="bx bx-edit-alt"></i></a>' +
            '<a href="/packing-list/' + transUID + '/print" class="btn btn-sm btn-outline-primary" ' +
                'data-bs-toggle="tooltip" title="Print" target="_blank"><i class="bx bx-printer"></i></a>' +
            '</div></td>';

        html += '</tr>';
    });

    return html;
}

/**
 * @param {number}   [pageNo]
 * @param {number}   [rowLimit]
 * @param {string}   [filter]
 * @param {Function} [afterLoad]
 * @returns {void}
 */
function getPLDetails(pageNo, rowLimit, filter, afterLoad) {
    pageNo   = pageNo   || PageNo;
    rowLimit = rowLimit || RowLimit;
    filter   = filter !== undefined ? filter : Filter;

    var reqSeq = ++_plReqSeq;
    $('#plTbody').html(
        '<tr><td colspan="8" class="text-center py-4">' +
        '<div class="spinner-border spinner-border-sm text-primary" role="status"></div>' +
        '<span class="ms-2 text-muted">Loading...</span></td></tr>'
    );
    $('#plPagination').addClass('d-none').empty();

    $.ajax({
        url     : global_services_url + 'api/transactions/getPageDetails/119/' + pageNo,
        method  : 'GET',
        headers : { Authorization: 'Bearer ' + JwtToken },
        data    : {
            orgId    : parseInt(JwtData.Org.OrgUID,    10),
            branchId : parseInt(JwtData.Org.BranchUID, 10) || 1,
            RowLimit : rowLimit,
            PageNo   : pageNo,
            Filter   : filter
        },
        success : function (resp) {
            if (reqSeq !== _plReqSeq) return;
            ajaxLoading(1);
            var rows   = resp.Data       || [];
            var total  = parseInt(resp.TotalCount || 0,       10);
            var pg     = parseInt(resp.PageNo     || pageNo,  10);
            var rl     = parseInt(resp.RowLimit   || rowLimit, 10);
            var offset = (pg - 1) * rl;

            $('#plTbody').html(_buildPlHtml(rows, offset));
            $('#plTotalCount').text(total);

            var pagHtml = generatePagination(total, pg, rl);
            if (pagHtml) { $('#plPagination').html(pagHtml).removeClass('d-none'); }
            else         { $('#plPagination').addClass('d-none').empty(); }

            initTooltips();
            ajaxLoading(0);
            if (typeof afterLoad === 'function') afterLoad(resp);
        },
        error : function () {
            if (reqSeq !== _plReqSeq) return;
            ajaxLoading(0);
            $('#plTbody').html(
                '<tr><td colspan="8" class="text-center py-4 text-danger">Failed to load packing lists. Please try again.</td></tr>'
            );
        }
    });
}

$(function () {
    getPLDetails();

    // Search
    var _plSearchTimer;
    $('#plSearch').on('input', function () {
        var q = $(this).val().trim();
        $('.r2k-clear').toggleClass('d-none', q === '');
        clearTimeout(_plSearchTimer);
        _plSearchTimer = setTimeout(function () {
            Filter = q;
            PageNo = 1;
            getPLDetails();
        }, 400);
    });

    $('.r2k-clear').on('click', function () {
        $('#plSearch').val('').trigger('input');
    });

    // Pagination
    $(document).on('click', '#plPagination .r2k-pag-btn:not(.disabled)', function (e) {
        e.preventDefault();
        var pg = parseInt($(this).data('page') || $(this).data('ci-pagination-page'), 10);
        if (pg && pg !== PageNo) {
            PageNo = pg;
            getPLDetails();
        }
    });

    // Refresh
    $('.pageRefresh').on('click', function () { getPLDetails(); });
});
