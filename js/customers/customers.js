// ── List page AJAX functions ──────────────────────────────────────────────

/**
 * @param {string} tableSelector
 * @param {string} paginationSelector
 * @returns {void}
 */
function showTabSpinner(tableSelector, paginationSelector) {
    var cols = $(tableSelector + ' thead tr:first th:visible').length || 6;
    $(tableSelector + ' tbody').html(
        '<tr><td colspan="' + cols + '" class="text-center py-4">' +
        '<span class="spinner-border spinner-border-sm text-primary me-2"></span>' +
        '<span class="text-muted" style="font-size:var(--r2k-text-85);">Loading...</span>' +
        '</td></tr>'
    );
    if (paginationSelector) $(paginationSelector).empty();
}

function _smartDecimal(val) {
    var n = parseFloat(val);
    if (isNaN(n)) return '0';
    return n === 0 ? '0' : String(parseFloat(n.toFixed(6)));
}

// ── Select-all (Pattern 3) state ──────────────────────────────────────────
var _custSelectAllMode = false;
var _custTotalRecords  = 0;
var _custPageCount     = 0;
var _custReqSeq        = 0;

/**
 * @returns {void}
 */
function _custUpdateSelectAllBanner() {
    var $banner = $('#custSelectAllBanner');
    var $msg    = $('#custSelectAllMsg');
    var $link   = $('#custSelectAllLink');
    var $clear  = $('#custSelectAllClear');

    if (!_custPageCount || !$(ModuleHeader).prop('checked')) {
        $banner.addClass('d-none');
        return;
    }

    if (_custSelectAllMode) {
        $msg.text('All ' + _custTotalRecords + ' customers are selected.');
        $link.addClass('d-none');
        $clear.removeClass('d-none');
    } else {
        $msg.text('All ' + _custPageCount + ' customers on this page are selected.');
        $clear.addClass('d-none');
        if (_custTotalRecords > _custPageCount) {
            $link.text('Select all ' + _custTotalRecords + ' customers?').removeClass('d-none');
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
function _custClearSelectAll() {
    _custSelectAllMode = false;
    $('#custSelectAllBanner').addClass('d-none');
    $('#custSelectAllLink').removeClass('d-none');
    $('#custSelectAllClear').addClass('d-none');
}

function getCustomersDetails_bkp(PageNo, RowLimit, Filter, onDone) {
    var reqSeq = ++_custReqSeq;
    ajaxLoading(0);
    showTabSpinner(ModuleTable, ModulePag);
    $.ajax({
        url: global_base_url + 'customers/getCustomersPageDetails/' + PageNo,
        method: 'POST',
        cache: false,
        data: { RowLimit: RowLimit, Filter: Filter },
        success: function (response) {
            if (reqSeq !== _custReqSeq) return;
            ajaxLoading(1);
            if (response.Error) {
                $(ModuleTable + ' tbody').html('');
                $(ModulePag).html('<div class="alert alert-danger" role="alert"><strong>' + response.Message + '</strong></div>');
            } else {
                $(ModulePag).html(response.Pagination);
                $(ModuleTable + ' tbody').html(response.RecordHtmlData);
                $('#custStickyPagination .CustomersPagination').html(response.Pagination);
                _custTotalRecords = parseInt(response.TotalCount) || 0;
                _custPageCount    = $(ModuleTable + ' tbody ' + ModuleRow).length;
                var cnt = _custTotalRecords;
                $('.cust-tab .trans-tab-count').text(cnt > 0 ? cnt : '').toggleClass('d-none', cnt === 0);
                $(window).trigger('scroll');
            }
            executeTablePagnCommonFunc(response, false);
            if (typeof onDone === 'function') onDone();
        },
        error: function () {
            if (reqSeq !== _custReqSeq) return;
            ajaxLoading(1);
        },
    });
}

/**
 * @param {number|string} PageNo
 * @param {number|string} RowLimit
 * @param {object}        Filter
 * @param {Function}      [onDone]
 * @returns {void}
 */
function getCustomersDetails(PageNo, RowLimit, Filter, onDone) {
    var reqSeq      = ++_custReqSeq;
    var serialStart = (parseInt(PageNo, 10) - 1) * parseInt(RowLimit, 10);
    ajaxLoading(0);
    showTabSpinner(ModuleTable, ModulePag);
    $.ajax({
        url      : global_nestjs_url + 'api/customers/getCustomersPageDetails/' + JwtData.Org.OrgUID,
        method   : 'GET',
        dataType : 'json',
        headers  : { Authorization: 'Bearer ' + JwtToken },
        data     : {
            orgId    : JwtData.Org.OrgUID,
            RowLimit : RowLimit,
            langCode : (JwtData.User && JwtData.User.LangCode) ? JwtData.User.LangCode : 'en',
            pageNo   : PageNo,
            Filter   : JSON.stringify(Filter)
        },
        success: function (response) {
            if (reqSeq !== _custReqSeq) return;
            ajaxLoading(1);
            if (response.Error) {
                $(ModuleTable + ' tbody').html('');
                $(ModulePag).html('<div class="alert alert-danger" role="alert"><strong>' + response.Message + '</strong></div>');
            } else {
                var pagHtml = generateCustomersPagination(response.TotalCount, PageNo, RowLimit);
                $(ModulePag).html(pagHtml);
                $(ModuleTable + ' tbody').html(generateCustomersHtml(response.Data, serialStart));
                $('#custStickyPagination .CustomersPagination').html(pagHtml);
                _custTotalRecords = parseInt(response.TotalCount) || 0;
                _custPageCount    = $(ModuleTable + ' tbody ' + ModuleRow).length;
                var cnt = _custTotalRecords;
                $('.cust-tab .trans-tab-count').text(cnt > 0 ? cnt : '').toggleClass('d-none', cnt === 0);
                $(window).trigger('scroll');
            }
            executeTablePagnCommonFunc(response, false);
            if (typeof onDone === 'function') onDone();
        },
        error: function () {
            if (reqSeq !== _custReqSeq) return;
            ajaxLoading(1);
        },
    });
}

/* ── Services lazy-load helpers ─────────────────────────────────────────── */

/**
 * Fetch customer tags from services and invoke callback with the response.
 * Caller is responsible for calling custTagFilter.setItems() and showing the button.
 * @param {Function} onLoaded  Called with the raw response object on success
 * @returns {void}
 */
function loadCustomerTags(onLoaded) {
    $.ajax({
        url     : global_nestjs_url + 'api/customers/getCustomerTags',
        method  : 'GET',
        headers : { Authorization: 'Bearer ' + JwtToken },
        data    : { orgId: JwtData.Org.OrgUID },
        success : function (response) {
            if (typeof onLoaded === 'function') onLoaded(response);
        }
    });
}

/**
 * Fetch customer stats from services and populate the stat card elements.
 * Respects _custShowStats gate — call this only when stats are enabled.
 * @returns {void}
 */
function loadCustomerStats() {
    $.ajax({
        url     : global_nestjs_url + 'api/customers/getCustomerStats',
        method  : 'GET',
        headers : { Authorization: 'Bearer ' + JwtToken },
        data    : { orgId: JwtData.Org.OrgUID },
        success : function (response) {
            if (!response.Error && response.Data) updateCustomerStats(response.Data);
        }
    });
}

/**
 * Fetch group stats from services and invoke callback with the response.
 * @param {Function} onLoaded  Called with the raw response object on success
 * @returns {void}
 */
function loadGroupStats(onLoaded) {
    $.ajax({
        url     : global_nestjs_url + 'api/customers/toggleGroupStatus',
        method  : 'GET',
        headers : { Authorization: 'Bearer ' + JwtToken },
        data    : { orgId: JwtData.Org.OrgUID },
        success : function (response) {
            if (typeof onLoaded === 'function') onLoaded(response);
        }
    });
}

/* ── HTML generation helpers ────────────────────────────────────────────── */

/**
 * Escape text content for safe insertion as HTML text.
 * @param {*} str
 * @returns {string}
 */
function _escHtml(str) {
    return $('<div>').text(String(str == null ? '' : str)).html();
}

/**
 * Escape a value for use inside an HTML attribute (double-quoted).
 * @param {*} str
 * @returns {string}
 */
function _escHtmlAttr(str) {
    return String(str == null ? '' : str)
        .replace(/&/g, '&amp;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');
}

/**
 * Parse a "YYYY-MM-DD HH:MM:SS" datetime string and return display info.
 * @param {string|null} dateTimeStr
 * @returns {{ formatted: string, w24: boolean, ago: string|null }}
 */
function _custFormatDateTime(dateTimeStr) {
    if (!dateTimeStr) return { formatted: '—', w24: false, ago: null };

    /* Parse "YYYY-MM-DD HH:MM:SS" — treat as UTC to match PHP server time */
    var parsed = new Date(dateTimeStr.replace(' ', 'T') + 'Z');
    if (isNaN(parsed.getTime())) return { formatted: dateTimeStr, w24: false, ago: null };

    var dy  = parsed.getUTCDate();
    var mo  = parsed.getUTCMonth();       // 0-based
    var yr  = parsed.getUTCFullYear();
    var hr  = parsed.getUTCHours();
    var min = parsed.getUTCMinutes();
    var sec = parsed.getUTCSeconds();

    var months     = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    var monthsFull = ['January','February','March','April','May','June','July','August','September','October','November','December'];
    var ampm       = hr >= 12 ? 'PM' : 'AM';
    var h12        = hr % 12 || 12;

    /* Use ListDateTimeFormat (same setting PHP uses for FormatType=2) */
    var fmt = (typeof _transListDateTimeFormat !== 'undefined' && _transListDateTimeFormat)
              ? _transListDateTimeFormat : 'd M Y h:i A';

    var tokens = {
        'd': String(dy).padStart(2, '0'),
        'j': String(dy),
        'm': String(mo + 1).padStart(2, '0'),
        'n': String(mo + 1),
        'Y': String(yr),
        'y': String(yr).slice(-2),
        'F': monthsFull[mo],
        'M': months[mo],
        'H': String(hr).padStart(2, '0'),
        'G': String(hr),
        'h': String(h12).padStart(2, '0'),
        'g': String(h12),
        'i': String(min).padStart(2, '0'),
        's': String(sec).padStart(2, '0'),
        'A': ampm,
        'a': ampm.toLowerCase()
    };
    var formatted = fmt.replace(/[djmnYyFMHGhgisAa]/g, function (tok) {
        return tokens[tok] !== undefined ? tokens[tok] : tok;
    });

    /* Compute "ago" text when within 24 hours */
    var secsAgo = Math.floor((Date.now() - parsed.getTime()) / 1000);
    var w24 = secsAgo >= 0 && secsAgo < 86400;
    var ago = null;
    if (w24) {
        if (secsAgo < 60) {
            ago = 'just now';
        } else if (secsAgo < 3600) {
            var m = Math.floor(secsAgo / 60);
            ago = m + ' min' + (m > 1 ? 's' : '') + ' ago';
        } else {
            var h = Math.floor(secsAgo / 3600);
            ago = h + ' hr' + (h > 1 ? 's' : '') + ' ago';
        }
    }
    return { formatted: formatted, w24: w24, ago: ago };
}

/**
 * Build table row HTML for the customer list from Nest.js JSON data.
 * @param {Array}  data         Array of customer record objects from Nest.js response
 * @param {number} serialStart  Zero-based offset for the S.No column
 * @returns {string}
 */
function generateCustomersHtml(data, serialStart) {
    if (!data || data.length === 0) {
        var cols = $(ModuleTable + ' thead tr:first th:visible').length || 6;
        return '<tr><td colspan="' + cols + '" class="text-center py-4 text-muted">No records found.</td></tr>';
    }

    var showSerial = $(ModuleTable + ' thead .th-sno').is(':not(.d-none)');
    var sym = (typeof currencySymbol !== 'undefined') ? currencySymbol : '₹';
    var html = '';

    $.each(data, function (i, list) {
        var sno = serialStart + i + 1;
        var uid = parseInt(list.TablePrimaryUID, 10);
        var sal = list.SalutationName ? _escHtml(list.SalutationName) + '. ' : '';
        var name = sal + _escHtml(list.Name || '—');

        /* Avatar */
        var imgSrc = list.PrimaryImageUrl || (list.Image ? CDN_URL + list.Image : null);
        var imagesJson = _escHtmlAttr(list.AttachmentsJson || '[]');
        var words = (list.Name || '').trim().split(/\s+/);
        var initials = (words[0] ? words[0][0] : '').toUpperCase();
        if (words[1]) initials += words[1][0].toUpperCase();

        var avatarHtml = imgSrc
            ? '<img src="' + _escHtmlAttr(imgSrc) + '" alt="' + name + '" class="rounded-circle cursor-pointer cust-list-img" data-images="' + imagesJson + '" style="width:36px;height:36px;object-fit:cover;" />'
            : '<span class="avatar-initial rounded-circle bg-label-primary">' + initials + '</span>';

        /* Status badge */
        var isActive   = parseInt(list.IsActive, 10) === 1;
        var badgeClass = isActive ? 'bg-label-success' : 'bg-label-danger';
        var badgeText  = isActive ? 'Active' : 'In-Active';
        var toggleSt   = isActive ? 0 : 1;
        var toggleIcon = isActive
            ? '<i class="bx bx-x-circle me-2 text-danger"></i>Mark In-Active'
            : '<i class="bx bx-check-circle me-2 text-success"></i>Mark Active';

        /* Party name span (mirrors r2k_party_name PHP helper) */
        var partyHtml = '<span class="chc-trigger" style="cursor:default;"'
            + ' data-name="'   + _escHtmlAttr(list.Name || '')        + '"'
            + ' data-mobile="' + _escHtmlAttr(list.MobileNumber || '') + '"'
            + ' data-code="'   + _escHtmlAttr(list.CountryCode || '')  + '"'
            + (list.Area   ? ' data-area="' + _escHtmlAttr(list.Area)   + '"' : '')
            + (imgSrc      ? ' data-img="'  + _escHtmlAttr(imgSrc)      + '"' : '')
            + '>' + _escHtml(list.Name || '') + '</span>';

        /* Mobile */
        var mobileHtml;
        if (list.MobileNumber) {
            var fullMobile = (list.CountryCode ? list.CountryCode + ' ' : '') + list.MobileNumber;
            var waNum = (list.CountryCode + list.MobileNumber).replace(/[^0-9]/g, '');
            mobileHtml = '<div class="d-flex align-items-center gap-1">'
                + '<span class="copy-mobile cursor-pointer" data-mobile="' + _escHtmlAttr(list.MobileNumber) + '" data-bs-toggle="tooltip" data-bs-placement="top" title="Click to copy mobile number" style="font-size:var(--r2k-text-82);">'
                + _escHtml(fullMobile) + '</span></div>'
                + '<a href="https://wa.me/' + _escHtmlAttr(waNum) + '?text=Hi" target="_blank" class="text-success" style="font-size:var(--r2k-text-75);"><i class="bx bxl-whatsapp me-1"></i>WhatsApp</a>';
        } else {
            mobileHtml = '<span class="text-muted">—</span>';
        }

        /* GSTIN / Company */
        var gstinHtml = '';
        if (list.GSTIN) {
            gstinHtml += '<div style="font-size:var(--r2k-text-82);font-family:monospace;">' + _escHtml(list.GSTIN) + '</div>';
        }
        if (list.CompanyName) {
            gstinHtml += '<div class="text-muted" style="font-size:var(--r2k-text-75);">' + _escHtml(list.CompanyName) + '</div>';
        } else if (!list.GSTIN) {
            gstinHtml = '<span class="text-muted">—</span>';
        }

        /* Closing Balance */
        var bal      = Math.round(parseFloat(list.ClosingBalance) || 0);
        var balType  = list.ClosingBalanceType || 'Debit';
        var balClass = bal === 0 ? 'text-muted' : (balType === 'Debit' ? 'text-success' : 'text-danger');
        var balLabel = bal === 0 ? '' : (balType === 'Debit' ? 'To Collect' : 'To Pay');
        var balHtml  = '<div class="fw-semibold ' + balClass + '">' + sym + ' ' + (typeof smartDecimal === 'function' ? smartDecimal(bal) : bal) + '</div>'
                     + '<div style="font-size:var(--r2k-text-sm);color:#aaa;">' + balLabel + '</div>';

        /* Updated On */
        var dtObj    = _custFormatDateTime(list.UpdatedOn);
        var dateHtml = '<div class="r2k-col-date">' + dtObj.formatted + '</div>'
                     + (dtObj.w24 ? '<div class="r2k-col-date-ago">' + dtObj.ago + '</div>' : '')
                     + '<div class="text-muted r2k-col-date-by">by ' + _escHtml(list.UpdatedBy || '—') + '</div>';

        /* Customer Type badge */
        var custTypeHtml = list.CustomerTypeName
            ? '<span class="badge bg-label-primary">' + _escHtml(list.CustomerTypeName) + '</span>'
            : '<span class="text-muted">—</span>';

        /* Action dropdown extra items */
        var waItem = '';
        var emailItem = '';
        if (list.MobileNumber) {
            var waMobileNum = list.MobileNumber.replace(/[^0-9]/g, '');
            waItem = '<li><a class="dropdown-item" href="https://wa.me/' + _escHtmlAttr(waMobileNum) + '?text=Hi" target="_blank"><i class="bx bxl-whatsapp me-2 text-success"></i>Send WhatsApp</a></li>'
                   + '<li><button class="dropdown-item comm-send-single" data-commtype="SMS" data-recipienttype="Customer"'
                   + ' data-uid="' + uid + '" data-name="' + _escHtmlAttr(list.Name || '') + '" data-mobile="' + _escHtmlAttr(list.MobileNumber || '') + '" data-email="' + _escHtmlAttr(list.EmailAddress || '') + '">'
                   + '<i class="bx bx-message-rounded me-2 text-info"></i>Send SMS</button></li>';
        }
        if (list.EmailAddress) {
            emailItem = '<li><button class="dropdown-item comm-send-single" data-commtype="Email" data-recipienttype="Customer"'
                      + ' data-uid="' + uid + '" data-name="' + _escHtmlAttr(list.Name || '') + '" data-mobile="' + _escHtmlAttr(list.MobileNumber || '') + '" data-email="' + _escHtmlAttr(list.EmailAddress || '') + '">'
                      + '<i class="bx bx-envelope me-2 text-primary"></i>Send Email</button></li>';
        }

        html += '<tr>'
            + '<td><div class="form-check mb-0"><input class="form-check-input table-chkbox customerCheck" type="checkbox" value="' + uid + '"></div></td>'
            + '<td class="' + (showSerial ? '' : 'd-none') + ' table-serialno"><span class="text-muted" style="font-size:var(--r2k-text-78);">' + sno + '</span></td>'
            + '<td>'
            +   '<div class="d-flex align-items-center gap-2">'
            +     '<div class="avatar avatar-sm me-1">' + avatarHtml + '</div>'
            +     '<div>'
            +       '<a href="javascript:void(0);" class="cust-profile-link r2k-profile-link fw-semibold text-body text-decoration-none" data-uid="' + uid + '" title="View Profile">' + partyHtml + '</a>'
            +       '<div class="mt-1"><div class="dropdown d-inline-block">'
            +         '<span class="badge ' + badgeClass + ' cursor-pointer" style="font-size:var(--r2k-text-xs);" data-bs-toggle="dropdown">' + badgeText + ' <i class="bx bx-chevron-down" style="font-size:var(--r2k-text-65);"></i></span>'
            +         '<ul class="dropdown-menu r2k-action-menu"><li><button class="dropdown-item cust-status-toggle" data-uid="' + uid + '" data-newstatus="' + toggleSt + '">' + toggleIcon + '</button></li></ul>'
            +       '</div></div>'
            +     '</div>'
            +   '</div>'
            + '</td>'
            + '<td>' + (list.Area ? '<span style="font-size:var(--r2k-text-82);">' + _escHtml(list.Area) + '</span>' : '<span class="text-muted">—</span>') + '</td>'
            + '<td>' + mobileHtml + '</td>'
            + '<td>' + gstinHtml + '</td>'
            + '<td>' + balHtml + '</td>'
            + '<td>' + custTypeHtml + '</td>'
            + '<td>' + dateHtml + '</td>'
            + '<td>'
            +   '<div class="d-flex align-items-center justify-content-end gap-1">'
            +     '<button class="btn btn-icon btn-sm text-warning cust-edit-btn" data-uid="' + uid + '" title="Edit"><i class="bx bx-edit"></i></button>'
            +     '<div class="dropdown">'
            +       '<button class="trans-actions-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="bx bx-dots-vertical-rounded fs-5"></i></button>'
            +       '<ul class="dropdown-menu dropdown-menu-end r2k-action-menu">'
            +         '<li><button class="dropdown-item cust-clone-btn" data-uid="' + uid + '"><i class="bx bx-copy me-2 text-secondary"></i>Clone</button></li>'
            +         waItem
            +         emailItem
            +         '<li><hr class="dropdown-divider my-1"></li>'
            +         '<li><button class="dropdown-item text-danger DeleteCustomer" data-customeruid="' + uid + '" data-name="' + _escHtmlAttr(name) + '"><i class="bx bx-trash me-2"></i>Delete</button></li>'
            +       '</ul>'
            +     '</div>'
            +   '</div>'
            + '</td>'
            + '</tr>';
    });

    return html;
}

/**
 * Build pagination HTML matching the PHP buildPagePaginationHtml output.
 * @param {number|string} totalCount
 * @param {number|string} pageNo
 * @param {number|string} rowLimit
 * @returns {string}
 */
function generateCustomersPagination(totalCount, pageNo, rowLimit) {
    totalCount = parseInt(totalCount, 10) || 0;
    pageNo     = parseInt(pageNo,     10) || 1;
    rowLimit   = parseInt(rowLimit,   10) || 10;

    if (totalCount <= 0 || rowLimit <= 0) return '';

    var totalPages = Math.ceil(totalCount / rowLimit);
    pageNo = Math.max(1, Math.min(pageNo, totalPages));

    /* Page window: up to 3 numbers centred on current page (mirrors PHP logic) */
    var winStart = Math.max(1, pageNo - 1);
    var winEnd   = Math.min(totalPages, winStart + 2);
    winStart     = Math.max(1, winEnd - 2);

    /* Info text: "Showing X - Y of Z Results" */
    var start = pageNo === 1 ? 1 : ((pageNo - 1) * rowLimit) + 1;
    var end   = pageNo === totalPages ? totalCount : pageNo * rowLimit;
    if (end > totalCount) end = totalCount;
    var info = 'Showing ' + start + ' - ' + end + ' of ' + totalCount + ' Results';

    var btn = function (label, page, active, disabled, arrow) {
        var cls = 'r2k-pag-btn';
        if (arrow)    cls += ' r2k-pag-arrow';
        if (active)   cls += ' active';
        if (disabled) cls += ' disabled';
        return '<a class="' + cls + '" href="javascript:void(0);" data-ci-pagination-page="' + page + '" data-page="' + page + '">' + label + '</a>';
    };

    var html = '<span class="r2k-pag-info">' + info + '</span>'
             + '<nav class="r2k-pag-nav">'
             + btn('&laquo;', 1, false, pageNo <= 1, true)
             + btn('&lsaquo;', Math.max(1, pageNo - 1), false, pageNo <= 1, true);

    for (var p = winStart; p <= winEnd; p++) {
        html += btn(String(p), p, p === pageNo, false, false);
    }

    html += btn('&rsaquo;', Math.min(totalPages, pageNo + 1), false, pageNo >= totalPages, true)
          + btn('&raquo;', totalPages, false, pageNo >= totalPages, true)
          + '</nav>';

    return html;
}

function deleteCustomer(DeleteId) {
    $.ajax({
        url: global_base_url + 'customers/deleteCustomerData',
        method: 'POST',
        data: { CustomerUID: DeleteId },
        cache: false,
        success: function (response) {
            if (response.Error) {
                showAlertMessageSwal('error', '', response.Message);
            } else {
                showToastNotification(response.Message, 'success');
                hideUIBlock();
                ajaxLoading(0);
                getCustomersDetails(PageNo, RowLimit, Filter);
            }
        }
    });
}

function deleteMultipleCustomers() {
    var postData = _custSelectAllMode
        ? { SelectAll: 1, Filter: JSON.stringify(Filter), [CsrfName]: CsrfToken }
        : { CustomerUIDs: SelectedUIDs, [CsrfName]: CsrfToken };
    $.ajax({
        url: global_base_url + 'customers/deleteBulkCustomers',
        method: 'POST',
        cache: false,
        data: postData,
        success: function (response) {
            if (response.Error) {
                showAlertMessageSwal('error', '', response.Message);
            } else {
                showToastNotification(response.Message, 'success');
                SelectedUIDs = [];
                _custClearSelectAll();
                hideUIBlock();
                ajaxLoading(0);
                getCustomersDetails(PageNo, RowLimit, Filter);
            }
        },
    });
}

function toggleCustomerStatus(CustomerUID, IsActive) {
    $.ajax({
        url: global_base_url + 'customers/toggleCustomerStatus',
        method: 'POST',
        cache: false,
        data: { CustomerUID: CustomerUID, IsActive: IsActive, [CsrfName]: CsrfToken },
        success: function (response) {
            if (response.Error) {
                showAlertMessageSwal('error', '', response.Message);
            } else {
                showToastNotification(response.Message, 'success');
                hideUIBlock();
                ajaxLoading(0);
                getCustomersDetails(PageNo, RowLimit, Filter);
            }
        }
    });
}

function updateCustomerStats(stats) {
    if (!stats) return;
    var s = stats;
    $('.cust-stat-total').text(Number(s.TotalCount || 0).toLocaleString());
    $('.cust-stat-active').text(Number(s.ActiveCount || 0).toLocaleString());
    $('.cust-stat-month').text(Number(s.MonthCount || 0).toLocaleString());
    $('.cust-stat-fy').text(Number(s.FYCount || 0).toLocaleString());
    $('.cust-stat-lastmonth').text(Number(s.LastMonthCount || 0).toLocaleString());
}

// ── Page-level callback: refresh list after any save ─────────────────────
/**
 * @param {Object} response
 * @returns {void}
 */
function _custPageSaveSuccess(response) {
    hideUIBlock();
    ajaxLoading(0);
    var msg = response ? response.Message : '';
    if (msg) showToastNotification(msg, 'success');
    getCustomersDetails(PageNo, RowLimit, Filter);
}

// ── Customer list image → open gallery from data-images (no AJAX) ────────────
$(document).on('click', '.cust-list-img', function(e) {
    e.stopPropagation();
    var raw = $(this).data('images');
    try {
        var imgs = typeof raw === 'string' ? JSON.parse(raw) : raw;
        if (imgs && imgs.length) { openImageGallery(imgs, 0); return; }
    } catch(err) {}
    var src = this.src;
    if (src) openImageGallery([{ url: src, name: '' }], 0);
});

// ── Open modal triggers ───────────────────────────────────────────────────
$(document).on('click', '.cust-edit-btn', function () {
    CustomerForm.open('edit', $(this).data('uid'), { onSaveSuccess: _custPageSaveSuccess });
});

$(document).on('click', '.cust-clone-btn', function () {
    CustomerForm.open('clone', $(this).data('uid'), { onSaveSuccess: _custPageSaveSuccess });
});

// ── Communication: single send ────────────────────────────────────────────
$(document).on('click', '.comm-send-single', function () {
    var $btn = $(this);
    openCommModal(
        $btn.data('commtype'),
        $btn.data('recipienttype'),
        [$btn.data('uid')],
        [$btn.data('name') || ''],
        [$btn.data('mobile') || ''],
        [$btn.data('email') || '']
    );
});

// ── Communication: bulk show/hide ─────────────────────────────────────────
function _updateBulkCommOptions() {
    var checked = $('.customerCheck:checked').length > 0;
    $('#BulkSmsOption').toggleClass('d-none', !checked);
    $('#BulkEmailOption').toggleClass('d-none', !checked);
}

$(document).on('change', '.customerCheck', function () {
    _updateBulkCommOptions();
});

$(document).on('click', '#btnBulkSms', function () {
    var uids = [], names = [], mobiles = [], emails = [];
    $('.customerCheck:checked').each(function () {
        var $row = $(this).closest('tr');
        var $ref = $row.find('.comm-send-single[data-commtype="Email"]');
        if (!$ref.length) $ref = $row.find('.comm-send-single');
        uids.push($(this).val());
        names.push($ref.data('name') || '');
        mobiles.push($ref.data('mobile') || '');
        emails.push($ref.data('email') || '');
    });
    if (uids.length) openCommModal('SMS', 'Customer', uids, names, mobiles, emails);
});

$(document).on('click', '#btnBulkEmail', function () {
    var uids = [], names = [], mobiles = [], emails = [];
    $('.customerCheck:checked').each(function () {
        var $row = $(this).closest('tr');
        var $ref = $row.find('.comm-send-single[data-commtype="Email"]');
        if (!$ref.length) $ref = $row.find('.comm-send-single');
        uids.push($(this).val());
        names.push($ref.data('name') || '');
        mobiles.push($ref.data('mobile') || '');
        emails.push($ref.data('email') || '');
    });
    if (uids.length) openCommModal('Email', 'Customer', uids, names, mobiles, emails);
});


