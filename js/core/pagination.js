/**
 * Shared pagination HTML builder used by all list pages.
 * Mirrors the PHP buildPagePaginationHtml() output.
 *
 * @param {number|string} totalCount  Total number of records
 * @param {number|string} pageNo      Current page (1-based)
 * @param {number|string} rowLimit    Rows per page
 * @returns {string}
 */
function generatePagination(totalCount, pageNo, rowLimit) {
    totalCount = parseInt(totalCount, 10) || 0;
    pageNo     = parseInt(pageNo,     10) || 1;
    rowLimit   = parseInt(rowLimit,   10) || 10;

    if (totalCount <= 0 || rowLimit <= 0) return '';

    var totalPages = Math.ceil(totalCount / rowLimit);
    pageNo = Math.max(1, Math.min(pageNo, totalPages));

    /* 3-page window centred on current page */
    var winStart = Math.max(1, pageNo - 1);
    var winEnd   = Math.min(totalPages, winStart + 2);
    winStart     = Math.max(1, winEnd - 2);

    var start = pageNo === 1 ? 1 : ((pageNo - 1) * rowLimit) + 1;
    var end   = Math.min(pageNo * rowLimit, totalCount);
    var info  = 'Showing ' + start + ' – ' + end + ' of ' + totalCount + ' records';

    var btn = function (label, page, active, disabled, arrow) {
        var cls = 'r2k-pag-btn' + (arrow ? ' r2k-pag-arrow' : '') + (active ? ' active' : '') + (disabled ? ' disabled' : '');
        return '<a class="' + cls + '" href="javascript:void(0);" data-ci-pagination-page="' + page + '" data-page="' + page + '">' + label + '</a>';
    };

    var html = '<span class="r2k-pag-info">' + info + '</span><nav class="r2k-pag-nav">'
             + btn('&laquo;', 1,                              false, pageNo <= 1,          true)
             + btn('&lsaquo;', Math.max(1, pageNo - 1),       false, pageNo <= 1,          true);

    for (var p = winStart; p <= winEnd; p++) { html += btn(String(p), p, p === pageNo, false, false); }

    html += btn('&rsaquo;', Math.min(totalPages, pageNo + 1), false, pageNo >= totalPages, true)
          + btn('&raquo;', totalPages,                        false, pageNo >= totalPages, true)
          + '</nav>';

    return html;
}
