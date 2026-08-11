// ── List page AJAX functions ──────────────────────────────────────────────

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
        '<span class="text-muted" style="font-size:.85rem;">Loading...</span>' +
        '</td></tr>'
    );
    if (paginationSelector) $(paginationSelector).empty();
}

function getVendorsDetails(PageNo, RowLimit, Filter, onDone) {
    var reqSeq = ++_vendReqSeq;
    ajaxLoading(0);
    showTabSpinner(ModuleTable, ModulePag);
    $.ajax({
        url   : '/vendors/getVendorsPageDetails/' + (PageNo || 1),
        method: 'POST',
        cache : false,
        data  : {
            RowLimit  : RowLimit,
            PageNo    : PageNo,
            Filter    : Filter,
            ModuleId  : ModuleId,
            [CsrfName]: CsrfToken,
        },
        success: function (response) {
            if (reqSeq !== _vendReqSeq) return;
            ajaxLoading(1);
            if (response.Error) {
                $(ModuleTable + ' tbody').html('');
                $(ModulePag).html('<div class="alert alert-danger" role="alert"><strong>' + response.Message + '</strong></div>');
            } else {
                $(ModulePag).html(response.Pagination);
                $(ModuleTable + ' tbody').html(response.RecordHtmlData);
                $('#vendStickyPagination .VendorsPagination').html(response.Pagination);
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
    getVendorsDetails(PageNo, RowLimit, Filter, function () {
        if (msg) showToastNotification(msg, 'success');
    });
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
            url: '/customers/searchCustomers',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                ajaxLoading(0);
                return { term: params.term, type: 'public' };
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
        url   : '/vendors/toggleVendorStatus',
        method: 'POST',
        cache : false,
        data  : { VendorUID: VendorUID, IsActive: IsActive, [CsrfName]: CsrfToken },
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
        url   : '/vendors/deleteVendorData',
        method: 'POST',
        cache : false,
        data  : { VendorUID: DeleteId, [CsrfName]: CsrfToken },
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
        ? { SelectAll: 1, Filter: JSON.stringify(Filter), [CsrfName]: CsrfToken }
        : { 'VendorUIDs[]': SelectedUIDs, [CsrfName]: CsrfToken };
    $.ajax({
        url   : '/vendors/deleteMultipleVendors',
        method: 'POST',
        cache : false,
        data  : postData,
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

