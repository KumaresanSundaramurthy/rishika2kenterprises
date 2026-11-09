// ── Select-all (Pattern 3) state ─────────────────────────────────────────
var _vendSelectAllMode = false;
var _vendTotalRecords  = 0;
var _vendPageCount     = 0;
var _vendReqSeq        = 0;

/**
 * @returns {void}
 */
function _vendUpdateSelectAllBanner() {
    var $banner = $('#vendSelectAllBanner');
    var $msg    = $('#vendSelectAllMsg');
    var $link   = $('#vendSelectAllLink');
    var $clear  = $('#vendSelectAllClear');

    if (!_vendPageCount || !$(ModuleHeader).prop('checked')) {
        $banner.addClass('d-none');
        return;
    }

    if (_vendSelectAllMode) {
        $msg.text('All ' + _vendTotalRecords + ' vendors are selected.');
        $link.addClass('d-none');
        $clear.removeClass('d-none');
    } else {
        $msg.text('All ' + _vendPageCount + ' vendors on this page are selected.');
        $clear.addClass('d-none');
        if (_vendTotalRecords > _vendPageCount) {
            $link.text('Select all ' + _vendTotalRecords + ' vendors?').removeClass('d-none');
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
function _vendClearSelectAll() {
    _vendSelectAllMode = false;
    $('#vendSelectAllBanner').addClass('d-none');
    $('#vendSelectAllLink').removeClass('d-none');
    $('#vendSelectAllClear').addClass('d-none');
}

function getVendorsDetails(PageNo, RowLimit, Filter, onDone) {
    var reqSeq      = ++_vendReqSeq;
    var serialStart = (parseInt(PageNo, 10) - 1) * parseInt(RowLimit, 10);
    ajaxLoading(0);
    showTabSpinner(ModuleTable, ModulePag);
    $.ajax({
        url    : global_services_url + 'api/vendors/getVendorsPageDetails/' + (PageNo || 1),
        method : 'GET',
        cache  : false,
        headers: { Authorization: 'Bearer ' + JwtToken },
        data   : {
            RowLimit: RowLimit,
            PageNo  : PageNo,
            Filter  : JSON.stringify(Filter),
            orgId   : JwtData.Org.OrgUID,
        },
        success: function (response) {
            if (reqSeq !== _vendReqSeq) return;
            ajaxLoading(1);
            if (response.Error) {
                $(ModuleTable + ' tbody').html('');
                $(ModulePag).html('<div class="alert alert-danger" role="alert"><strong>' + response.Message + '</strong></div>');
            } else {
                var pagHtml = generateVendorsPagination(response.TotalCount, PageNo, RowLimit);
                $(ModulePag).html(pagHtml);
                $(ModuleTable + ' tbody').html(generateVendorsHtml(response.Data, serialStart));
                $('#vendStickyPagination .VendorsPagination').html(pagHtml);
                _vendTotalRecords = parseInt(response.TotalCount) || 0;
                _vendPageCount    = $(ModuleTable + ' tbody ' + ModuleRow).length;
                var cnt = _vendTotalRecords;
                $('.vend-tab .trans-tab-count').text(cnt > 0 ? cnt : '').toggleClass('d-none', cnt === 0);
                $(window).trigger('scroll');
            }
            executeTablePagnCommonFunc(response, false);
            _vendUpdateSelectAllBanner();
            if (typeof onDone === 'function') onDone();
        },
        error: function () {
            if (reqSeq !== _vendReqSeq) return;
            ajaxLoading(1);
        },
    });
}

// ── Shared callback fired by VendorForm after any successful save ─────────────
/**
 * @param {Object} response
 * @returns {void}
 */
function _onVendorFormSaved(response) {
    hideUIBlock();
    ajaxLoading(0);
    var msg = response ? response.Message : '';
    if (msg) showToastNotification(msg, 'success');
    getVendorsDetails(PageNo, RowLimit, Filter);
}

// ── Open VendorForm modal triggers ────────────────────────────────────────────
$(document).on('click', '#btnCreateVendorHeader', function () {
    VendorForm.open('add', null, { onSaveSuccess: _onVendorFormSaved });
});

function searchCustomers(key) {
    $('#' + key).select2({
        placeholder: '-- Search Customers --',
        minimumInputLength: 3,
        allowClear: true,
        escapeMarkup: function (markup) { return markup; },
        ajax: {
            url      : global_services_url + 'api/customers/searchCustomers',
            dataType : 'json',
            delay    : 250,
            headers  : { Authorization: 'Bearer ' + JwtToken },
            data: function (params) {
                ajaxLoading(0);
                return { term: params.term, type: 'public', orgId: parseInt(JwtData.Org.OrgUID, 10) };
            },
            processResults: function (data) {
                ajaxLoading(1);
                return { results: data.Lists };
            },
            cache: true
        }
    }).on('select2:close', function () {
        ajaxLoading(1);
    });
}

// ── Update vendor stat cards from response.Stats ─────────────────────────
function updateVendorStats(stats) {
    if (!stats) return;
    var s = stats;
    $('.vend-stat-total').text(Number(s.TotalCount || 0).toLocaleString());
    $('.vend-stat-active').text(Number(s.ActiveCount || 0).toLocaleString());
    $('.vend-stat-month').text(Number(s.MonthCount || 0).toLocaleString());
    $('.vend-stat-fy').text(Number(s.FYCount || 0).toLocaleString());
    $('.vend-stat-lastmonth').text(Number(s.LastMonthCount || 0).toLocaleString());
}

// ── Toggle vendor active/inactive status ─────────────────────────────────
function toggleVendorStatus(VendorUID, IsActive) {
    $.ajax({
        url    : global_services_url + 'api/vendors/toggleVendorStatus',
        method : 'POST',
        cache  : false,
        headers: { Authorization: 'Bearer ' + JwtToken },
        data   : { VendorUID: VendorUID, IsActive: IsActive, orgId: JwtData.Org.OrgUID },
        success: function (response) {
            if (response.Error) {
                showAlertMessageSwal('error', '', response.Message);
            } else {
                showToastNotification(response.Message, 'success');
                hideUIBlock();
                ajaxLoading(0);
                getVendorsDetails(PageNo, RowLimit, Filter);
            }
        }
    });
}

// ── Delete single vendor ──────────────────────────────────────────────────
function deleteVendor(DeleteId) {
    $.ajax({
        url    : global_services_url + 'api/vendors/deleteVendorData',
        method : 'POST',
        cache  : false,
        headers: { Authorization: 'Bearer ' + JwtToken },
        data   : { VendorUID: parseInt(DeleteId, 10), orgId: parseInt(JwtData.Org.OrgUID, 10), userId: parseInt(JwtData.User.UserUID, 10) },
        success: function (response) {
            if (response.Error) {
                showAlertMessageSwal('error', '', response.Message);
            } else {
                showToastNotification(response.Message, 'success');
                hideUIBlock();
                ajaxLoading(0);
                getVendorsDetails(PageNo, RowLimit, Filter);
            }
        }
    });
}

// ── Delete multiple vendors ───────────────────────────────────────────────
function deleteMultipleVendors() {
    var postData = _vendSelectAllMode
        ? { SelectAll: 1, Filter: JSON.stringify(Filter), orgId: JwtData.Org.OrgUID }
        : { 'VendorUIDs[]': SelectedUIDs, orgId: JwtData.Org.OrgUID };
    $.ajax({
        url    : global_services_url + 'api/vendors/deleteMultipleVendors',
        method : 'POST',
        cache  : false,
        headers: { Authorization: 'Bearer ' + JwtToken },
        data   : postData,
        success: function (response) {
            if (response.Error) {
                showAlertMessageSwal('error', '', response.Message);
            } else {
                showToastNotification(response.Message, 'success');
                SelectedUIDs = [];
                _vendClearSelectAll();
                hideUIBlock();
                ajaxLoading(0);
                getVendorsDetails(PageNo, RowLimit, Filter);
            }
        }
    });
}



// ── Vendor list image → open gallery from data-images (no AJAX) ──────────────
$(document).on('click', '.vend-list-img', function(e) {
    e.stopPropagation();
    var raw = $(this).data('images');
    try {
        var imgs = typeof raw === 'string' ? JSON.parse(raw) : raw;
        if (imgs && imgs.length) { openImageGallery(imgs, 0); return; }
    } catch(err) {}
    var src = this.src;
    if (src) openImageGallery([{ url: src, name: '' }], 0);
});

// ── Open VendorForm for edit / clone ─────────────────────────────────────
$(document).on('click', '.vend-edit-btn', function () {
    VendorForm.open('edit', $(this).data('uid'), { onSaveSuccess: _onVendorFormSaved });
});

$(document).on('click', '.vend-clone-btn', function () {
    VendorForm.open('clone', $(this).data('uid'), { onSaveSuccess: _onVendorFormSaved });
});

// ── Customer linking (modal context) ─────────────────────────────────────
$(document).on('change', 'input[name="CustomerLinkingCheck"]', function () {
    var inModal = $('#VendorFormModal').hasClass('show');
    var $custDiv    = $('#CustomerDiv');
    var $custSelect = inModal ? $('#VM_Customers') : $('#Customers');

    $custDiv.addClass('d-none');
    $custSelect.prop('required', false);

    if ($(this).val() === 'OldCustomer') {
        $custDiv.removeClass('d-none');
        $custSelect.prop('required', true);
        if (inModal && !$('#VM_Customers').data('select2')) {
            searchCustomers('VM_Customers');
        }
    }
    $('#ResetCustomerLinking').removeClass('d-none');
});

$(document).on('click', '#ResetCustomerLinking', function () {
    $(this).addClass('d-none');
    $('input[name="CustomerLinkingCheck"]').prop('checked', false);
    $('#CustomerDiv').addClass('d-none');
    var inModal = $('#VendorFormModal').hasClass('show');
    (inModal ? $('#VM_Customers') : $('#Customers')).prop('required', false);
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
    var checked = $('.vendorsCheck:checked').length > 0;
    $('#BulkSmsOption').toggleClass('d-none', !checked);
    $('#BulkEmailOption').toggleClass('d-none', !checked);
}

$(document).on('change', '.vendorsCheck', function () {
    _updateBulkCommOptions();
});

// ── Bulk SMS ──────────────────────────────────────────────────────────────
$(document).on('click', '#btnBulkSms', function () {
    var uids = [], names = [], mobiles = [], emails = [];
    $('.vendorsCheck:checked').each(function () {
        var $row = $(this).closest('tr');
        var $ref = $row.find('.comm-send-single[data-commtype="Email"]');
        if (!$ref.length) $ref = $row.find('.comm-send-single');
        uids.push($(this).val());
        names.push($ref.data('name') || '');
        mobiles.push($ref.data('mobile') || '');
        emails.push($ref.data('email') || '');
    });
    if (uids.length) openCommModal('SMS', 'Vendor', uids, names, mobiles, emails);
});

/* ── HTML generation helpers ────────────────────────────────────────────── */

/**
 * @param {*} str
 * @returns {string}
 */
function _escHtml(str) {
    return $('<div>').text(String(str == null ? '' : str)).html();
}

/**
 * @param {*} str
 * @returns {string}
 */
function _escHtmlAttr(str) {
    return String(str == null ? '' : str)
        .replace(/&/g, '&amp;').replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}

/**
 * @param {string|null} dateTimeStr
 * @returns {{ formatted: string, ago: string|null }}
 */
function _vendFormatDateTime(dateTimeStr) {
    if (!dateTimeStr) return { formatted: '—', ago: null };
    var parsed = new Date(dateTimeStr.replace(' ', 'T') + 'Z');
    if (isNaN(parsed.getTime())) return { formatted: dateTimeStr, ago: null };
    var dy = parsed.getUTCDate(), mo = parsed.getUTCMonth(), yr = parsed.getUTCFullYear();
    var hr = parsed.getUTCHours(), min = parsed.getUTCMinutes(), sec = parsed.getUTCSeconds();
    var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    var monthsFull = ['January','February','March','April','May','June','July','August','September','October','November','December'];
    var ampm = hr >= 12 ? 'PM' : 'AM', h12 = hr % 12 || 12;
    var fmt = (typeof _transListDateTimeFormat !== 'undefined' && _transListDateTimeFormat) ? _transListDateTimeFormat : 'd M Y h:i A';
    var tokens = { 'd': String(dy).padStart(2,'0'), 'j': String(dy), 'm': String(mo+1).padStart(2,'0'), 'n': String(mo+1), 'Y': String(yr), 'y': String(yr).slice(-2), 'F': monthsFull[mo], 'M': months[mo], 'H': String(hr).padStart(2,'0'), 'G': String(hr), 'h': String(h12).padStart(2,'0'), 'g': String(h12), 'i': String(min).padStart(2,'0'), 's': String(sec).padStart(2,'0'), 'A': ampm, 'a': ampm.toLowerCase() };
    var formatted = fmt.replace(/[djmnYyFMHGhgisAa]/g, function (tok) { return tokens[tok] !== undefined ? tokens[tok] : tok; });
    var ago = timeAgo(dateTimeStr);
    return { formatted: formatted, ago: ago };
}

/**
 * Build tbody rows for the vendor list from services JSON.
 * @param {Array}  data
 * @param {number} serialStart  Zero-based offset for S.No
 * @returns {string}
 */
function generateVendorsHtml(data, serialStart) {
    if (!data || !data.length) {
        return '<tr><td colspan="9" style="padding:0;border:none;">'
             + '<div class="d-flex flex-column align-items-center py-5">'
             + '<img src="/assets/img/elements/no-record-found.png" alt="No Records" class="img-fluid mb-3" style="max-height:150px;object-fit:contain;">'
             + '<span class="text-muted mb-3" style="font-size:var(--r2k-text-90);">No vendors found</span>'
             + '<button type="button" class="btn btn-primary btn-sm px-4" id="btnCreateVendorHeader"><i class="bx bx-plus me-1"></i>Create Vendor</button>'
             + '</div></td></tr>';
    }

    var showSerial = $(ModuleTable + ' thead .th-sno').is(':not(.d-none)');
    var sym = (typeof currencySymbol !== 'undefined') ? currencySymbol : '₹';
    var html = '';

    $.each(data, function (i, list) {
        var sno = serialStart + i + 1;
        var uid = parseInt(list.TablePrimaryUID || list.VendorUID, 10);
        var sal = list.SalutationName ? _escHtml(list.SalutationName) + '. ' : '';
        var name = sal + _escHtml(list.Name || '—');

        var imgSrc     = list.PrimaryImageUrl || (list.Image ? CDN_URL + list.Image : null);
        var imagesJson = _escHtmlAttr(list.AttachmentsJson || '[]');
        var words    = (list.Name || '').trim().split(/\s+/);
        var initials = (words[0] ? words[0][0] : '').toUpperCase();
        if (words[1]) initials += words[1][0].toUpperCase();

        var avatarHtml = imgSrc
            ? '<img src="' + _escHtmlAttr(imgSrc) + '" alt="' + name + '" class="rounded-circle cursor-pointer vend-list-img" data-images="' + imagesJson + '" style="width:36px;height:36px;object-fit:cover;" />'
            : '<span class="avatar-initial rounded-circle bg-label-warning">' + initials + '</span>';

        var isActive   = parseInt(list.IsActive, 10) === 1;
        var badgeClass = isActive ? 'bg-label-success' : 'bg-label-danger';
        var badgeText  = isActive ? 'Active' : 'In-Active';
        var toggleSt   = isActive ? 0 : 1;
        var toggleIcon = isActive
            ? '<i class="bx bx-x-circle me-2 text-danger"></i>Mark In-Active'
            : '<i class="bx bx-check-circle me-2 text-success"></i>Mark Active';

        var mobileHtml;
        if (list.MobileNumber) {
            var fullMobile = (list.CountryCode ? list.CountryCode + ' ' : '') + list.MobileNumber;
            var waNum = ((list.CountryCode || '') + list.MobileNumber).replace(/[^0-9]/g, '');
            mobileHtml = '<div class="d-flex align-items-center gap-1"><span class="copy-mobile cursor-pointer" data-mobile="' + _escHtmlAttr(list.MobileNumber) + '" data-bs-toggle="tooltip" data-bs-placement="top" title="Click to copy mobile number" style="font-size:var(--r2k-text-82);">' + _escHtml(fullMobile) + '</span></div>'
                       + '<a href="https://wa.me/' + _escHtmlAttr(waNum) + '?text=Hi" target="_blank" class="text-success" style="font-size:var(--r2k-text-75);"><i class="bx bxl-whatsapp me-1"></i>WhatsApp</a>';
        } else {
            mobileHtml = '<span class="text-muted">—</span>';
        }

        var gstinHtml = '';
        if (list.GSTIN)       { gstinHtml += '<div style="font-size:var(--r2k-text-82);font-family:monospace;">' + _escHtml(list.GSTIN) + '</div>'; }
        if (list.CompanyName) { gstinHtml += '<div class="text-muted" style="font-size:var(--r2k-text-75);">' + _escHtml(list.CompanyName) + '</div>'; }
        if (!gstinHtml)       { gstinHtml  = '<span class="text-muted">—</span>'; }

        /* Credit = To Pay (danger), Debit = To Collect (success) */
        var bal      = Math.round(parseFloat(list.ClosingBalance) || 0);
        var balType  = list.ClosingBalanceType || 'Credit';
        var balClass = bal === 0 ? 'text-muted' : (balType === 'Credit' ? 'text-danger' : 'text-success');
        var balLabel = bal === 0 ? '' : (balType === 'Credit' ? 'To Pay' : 'To Collect');
        var balHtml  = '<div class="fw-semibold ' + balClass + '">' + sym + ' ' + (typeof smartDecimal === 'function' ? smartDecimal(bal) : bal) + '</div>'
                     + '<div style="font-size:var(--r2k-text-sm);color:#aaa;">' + balLabel + '</div>';

        var dtObj    = _vendFormatDateTime(list.UpdatedOn);
        var dateHtml = '<div class="r2k-col-date">' + dtObj.formatted + '</div>'
                     + (dtObj.ago ? '<div class="r2k-col-date-ago">' + dtObj.ago + '</div>' : '')
                     + '<div class="text-muted r2k-col-date-by">by ' + _escHtml(list.UpdatedBy || '—') + '</div>';

        var waItem = '', emailItem = '';
        if (list.MobileNumber) {
            var waMob = list.MobileNumber.replace(/[^0-9]/g, '');
            waItem = '<li><a class="dropdown-item" href="https://wa.me/' + _escHtmlAttr(waMob) + '?text=Hi" target="_blank"><i class="bx bxl-whatsapp me-2 text-success"></i>Send WhatsApp</a></li>'
                   + '<li><button class="dropdown-item comm-send-single" data-commtype="SMS" data-recipienttype="Vendor" data-uid="' + uid + '" data-name="' + _escHtmlAttr(list.Name || '') + '" data-mobile="' + _escHtmlAttr(list.MobileNumber || '') + '" data-email="' + _escHtmlAttr(list.EmailAddress || '') + '"><i class="bx bx-message-rounded me-2 text-info"></i>Send SMS</button></li>';
        }
        if (list.EmailAddress) {
            emailItem = '<li><button class="dropdown-item comm-send-single" data-commtype="Email" data-recipienttype="Vendor" data-uid="' + uid + '" data-name="' + _escHtmlAttr(list.Name || '') + '" data-mobile="' + _escHtmlAttr(list.MobileNumber || '') + '" data-email="' + _escHtmlAttr(list.EmailAddress || '') + '"><i class="bx bx-envelope me-2 text-primary"></i>Send Email</button></li>';
        }

        html += '<tr>'
            + '<td><div class="form-check mb-0"><input class="form-check-input table-chkbox vendorsCheck" type="checkbox" value="' + uid + '"></div></td>'
            + '<td class="' + (showSerial ? '' : 'd-none') + ' table-serialno"><span class="text-muted" style="font-size:var(--r2k-text-78);">' + sno + '</span></td>'
            + '<td><div class="d-flex align-items-center gap-2"><div class="avatar avatar-sm me-1">' + avatarHtml + '</div><div>'
            +   '<a href="javascript:void(0);" class="vend-profile-link r2k-profile-link fw-semibold text-body text-decoration-none" data-uid="' + uid + '" title="View Profile">' + name + '</a>'
            +   '<div class="mt-1"><div class="dropdown d-inline-block"><span class="badge ' + badgeClass + ' cursor-pointer" style="font-size:var(--r2k-text-xs);" data-bs-toggle="dropdown">' + badgeText + ' <i class="bx bx-chevron-down" style="font-size:var(--r2k-text-65);"></i></span>'
            +   '<ul class="dropdown-menu r2k-action-menu"><li><button class="dropdown-item vend-status-toggle" data-uid="' + uid + '" data-newstatus="' + toggleSt + '">' + toggleIcon + '</button></li></ul></div></div>'
            + '</div></div></td>'
            + '<td>' + (list.Area ? '<span style="font-size:var(--r2k-text-82);">' + _escHtml(list.Area) + '</span>' : '<span class="text-muted">—</span>') + '</td>'
            + '<td>' + mobileHtml + '</td>'
            + '<td>' + gstinHtml + '</td>'
            + '<td>' + balHtml + '</td>'
            + '<td>' + dateHtml + '</td>'
            + '<td><div class="d-flex align-items-center justify-content-end gap-1">'
            +   '<button class="btn btn-icon btn-sm text-warning vend-edit-btn" data-uid="' + uid + '" title="Edit"><i class="bx bx-edit"></i></button>'
            +   '<div class="dropdown"><button class="trans-actions-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="bx bx-dots-vertical-rounded fs-5"></i></button>'
            +   '<ul class="dropdown-menu dropdown-menu-end r2k-action-menu">'
            +     '<li><button class="dropdown-item vend-clone-btn" data-uid="' + uid + '"><i class="bx bx-copy me-2 text-secondary"></i>Clone</button></li>'
            +     waItem + emailItem
            +     '<li><hr class="dropdown-divider my-1"></li>'
            +     '<li><button class="dropdown-item text-danger DeleteVendor" data-vendoruid="' + uid + '" data-name="' + _escHtmlAttr(name) + '"><i class="bx bx-trash me-2"></i>Delete</button></li>'
            +   '</ul></div>'
            + '</div></td>'
            + '</tr>';
    });
    return html;
}

/**
 * @param {number|string} totalCount
 * @param {number|string} pageNo
 * @param {number|string} rowLimit
 * @returns {string}
 */
function generateVendorsPagination(totalCount, pageNo, rowLimit) {
    return generatePagination(totalCount, pageNo, rowLimit);
}

/**
 * Build tbody rows for the Vendor Groups table from services JSON.
 * @param {Array} data
 * @returns {string}
 */
function generateVendorGroupsHtml(data) {
    if (!Array.isArray(data) || !data.length) {
        return '<tr><td colspan="9"><div class="d-flex flex-column align-items-center justify-content-center py-5 text-muted">'
             + '<i class="bx bxs-layer mb-2" style="font-size:3rem;color:#e2e8f0;"></i>'
             + '<div style="font-size:var(--r2k-text-90);">No vendor groups found.</div>'
             + '<button type="button" class="btn btn-sm btn-primary mt-2 vbtn-new-group"><i class="bx bx-plus me-1"></i>Create First Group</button>'
             + '</div></td></tr>';
    }
    var typeColors = {
        'Business Group':  { bg: '#f0efff', c: '#696cff' },
        'Branch Group':    { bg: '#e0f7fa', c: '#0097a7' },
        'Family Group':    { bg: '#fce8ff', c: '#9333ea' },
        'Corporate Group': { bg: '#e8f5e9', c: '#2e7d32' },
        'Dealer Network':  { bg: '#fff3e0', c: '#ef6c00' },
        'Franchise Group': { bg: '#fce4ec', c: '#c62828' },
        'Custom':          { bg: '#f5f5f5', c: '#616161' }
    };
    var sym  = (typeof currencySymbol !== 'undefined') ? currencySymbol : '₹';
    var html = '';
    $.each(data, function (i, row) {
        var uid        = parseInt(row.GroupUID, 10) || 0;
        var isActive   = parseInt(row.IsActive, 10) === 1;
        var payable    = parseFloat(row.TotalPayable)    || 0;
        var receivable = parseFloat(row.TotalReceivable) || 0;
        var members    = parseInt(row.MemberCount, 10)   || 0;
        var name       = _escHtml(row.GroupName  || '—');
        var code       = _escHtml(row.GroupCode  || '');
        var primary    = _escHtml(row.PrimaryName || '');
        var groupType  = _escHtml(row.GroupType  || 'Business Group');
        var tc         = typeColors[row.GroupType] || { bg: '#f5f5f5', c: '#616161' };

        var outstandingHtml;
        if (payable > 0) {
            outstandingHtml = '<div style="font-size:var(--r2k-text-82);font-weight:600;color:#dc3545;">' + sym + ' ' + (typeof smartDecimal === 'function' ? smartDecimal(payable) : payable) + '</div><div class="text-muted" style="font-size:var(--r2k-text-70);">Payable</div>';
        } else if (receivable > 0) {
            outstandingHtml = '<div style="font-size:var(--r2k-text-82);font-weight:600;color:#28a745;">' + sym + ' ' + (typeof smartDecimal === 'function' ? smartDecimal(receivable) : receivable) + '</div><div class="text-muted" style="font-size:var(--r2k-text-70);">Receivable</div>';
        } else {
            outstandingHtml = '<span class="text-muted" style="font-size:var(--r2k-text-md);">Settled</span>';
        }

        var contactHtml = '';
        if (row.ContactPerson) contactHtml += '<div style="font-size:var(--r2k-text-82);">' + _escHtml(row.ContactPerson) + '</div>';
        if (row.Mobile)        contactHtml += '<div class="text-muted" style="font-size:.74rem;">' + _escHtml(row.Mobile) + '</div>';
        if (!contactHtml)      contactHtml  = '<span class="text-muted">—</span>';

        html +=
            '<tr>' +
            '<td><div class="form-check mb-0"><input class="form-check-input table-chkbox vgrpCheck" type="checkbox" value="' + uid + '"></div></td>' +
            '<td><div class="fw-semibold"><button type="button" class="btn btn-link p-0 text-body fw-semibold vgrp-view-btn" data-uid="' + uid + '" style="text-decoration:none;font-size:inherit;">' + name + '</button></div>' +
                (primary ? '<div class="text-muted" style="font-size:.74rem;"><i class="bx bx-star" style="color:#f59e0b;font-size:var(--r2k-text-sm);"></i> ' + primary + '</div>' : '') +
            '</td>' +
            '<td>' + (code ? '<span style="font-size:var(--r2k-text-md);font-family:monospace;">' + code + '</span>' : '<span class="text-muted">—</span>') + '</td>' +
            '<td><span class="badge" style="background:' + tc.bg + ';color:' + tc.c + ';font-size:var(--r2k-text-sm);font-weight:600;">' + groupType + '</span></td>' +
            '<td class="text-center"><span class="badge bg-label-primary" style="font-size:var(--r2k-text-78);">' + members + '</span></td>' +
            '<td>' + contactHtml + '</td>' +
            '<td class="text-end">' + outstandingHtml + '</td>' +
            '<td><div class="dropdown d-inline-block">' +
                '<span class="badge ' + (isActive ? 'bg-label-success' : 'bg-label-danger') + ' cursor-pointer" style="font-size:var(--r2k-text-xs);" data-bs-toggle="dropdown">' +
                    (isActive ? 'Active' : 'Inactive') + ' <i class="bx bx-chevron-down" style="font-size:var(--r2k-text-65);"></i></span>' +
                '<ul class="dropdown-menu shadow-sm" style="min-width:150px;font-size:var(--r2k-text-82);">' +
                    '<li><button class="dropdown-item vgrp-status-toggle" data-uid="' + uid + '" data-newstatus="' + (isActive ? 0 : 1) + '">' +
                        (isActive ? '<i class="bx bx-x-circle me-2 text-danger"></i>Mark Inactive' : '<i class="bx bx-check-circle me-2 text-success"></i>Mark Active') +
                    '</button></li></ul></div></td>' +
            '<td><div class="d-flex align-items-center gap-1">' +
                '<button type="button" class="btn btn-icon btn-sm text-info vgrp-view-btn" data-uid="' + uid + '" title="View Detail"><i class="bx bx-show"></i></button>' +
                '<button type="button" class="btn btn-icon btn-sm text-warning vgrp-edit-btn" data-uid="' + uid + '" title="Edit"><i class="bx bx-edit"></i></button>' +
                '<div class="dropdown"><button type="button" class="btn btn-icon btn-sm text-secondary" data-bs-toggle="dropdown" aria-expanded="false" title="More options"><i class="bx bx-dots-vertical-rounded"></i></button>' +
                '<ul class="dropdown-menu dropdown-menu-end shadow-sm" style="min-width:140px;font-size:var(--r2k-text-82);">' +
                    '<li><button class="dropdown-item text-danger vgrp-delete-btn" data-uid="' + uid + '"><i class="bx bx-trash me-2"></i>Delete</button></li>' +
                '</ul></div></div></td>' +
            '</tr>';
    });
    return html;
}

// ── Bulk Email ────────────────────────────────────────────────────────────
$(document).on('click', '#btnBulkEmail', function () {
    var uids = [], names = [], mobiles = [], emails = [];
    $('.vendorsCheck:checked').each(function () {
        var $row = $(this).closest('tr');
        var $ref = $row.find('.comm-send-single[data-commtype="Email"]');
        if (!$ref.length) $ref = $row.find('.comm-send-single');
        uids.push($(this).val());
        names.push($ref.data('name') || '');
        mobiles.push($ref.data('mobile') || '');
        emails.push($ref.data('email') || '');
    });
    if (uids.length) openCommModal('Email', 'Vendor', uids, names, mobiles, emails);
});

