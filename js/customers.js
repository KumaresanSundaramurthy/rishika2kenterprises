// ── Export ────────────────────────────────────────────────────────────────

function custExport(type) {
    var url = '/customers/exportCustomers?Type=' + encodeURIComponent(type);
    if (typeof Filter !== 'undefined' && !$.isEmptyObject(Filter)) {
        url += '&Filter=' + encodeURIComponent(JSON.stringify(Filter));
    }
    if (type === 'Print') {
        printPreviewRecords(url, function () {});
    } else {
        window.location.href = url;
    }
}

// ── List page AJAX functions ──────────────────────────────────────────────

function _smartDecimal(val) {
    var n = parseFloat(val);
    if (isNaN(n)) return '0';
    return n === 0 ? '0' : String(parseFloat(n.toFixed(6)));
}

function getCustomersDetails(PageNo, RowLimit, Filter) {
    $('#custStickyPagination').stop(true, true).hide();
    $.ajax({
        url: '/customers/getCustomersPageDetails/' + PageNo,
        method: 'POST',
        cache: false,
        data: { RowLimit: RowLimit, Filter: Filter },
        success: function (response) {
            if (response.Error) {
                $(ModuleTable + ' tbody').html('');
                $(ModulePag).html('<div class="alert alert-danger" role="alert"><strong>' + response.Message + '</strong></div>');
            } else {
                $(ModulePag).html(response.Pagination);
                $(ModuleTable + ' tbody').html(response.RecordHtmlData);
                $('#custStickyPagination .CustomersPagination').html(response.Pagination);
                $(window).trigger('scroll');
            }
            executeTablePagnCommonFunc(response, false);
        },
    });
}

function deleteCustomer(DeleteId) {
    $.ajax({
        url: '/customers/deleteCustomerData',
        method: 'POST',
        data: { RowLimit: RowLimit, PageNo: PageNo, Filter: Filter, CustomerUID: DeleteId, ModuleId: ModuleId },
        cache: false,
        success: function (response) {
            if (response.Error) {
                showAlertMessageSwal('error', '', response.Message);
            } else {
                showToastNotification(response.Message, 'success');
                $(ModulePag).html(response.Pagination);
                $(ModuleTable + ' tbody').html(response.List);
                updateCustomerStats(response.Stats);
            }
            executeTablePagnCommonFunc(response, true);
        }
    });
}

function deleteMultipleCustomers() {
    $.ajax({
        url: '/customers/deleteBulkCustomers',
        method: 'POST',
        cache: false,
        data: { RowLimit: RowLimit, PageNo: PageNo, Filter: Filter, CustomerUIDs: SelectedUIDs, ModuleId: ModuleId },
        success: function (response) {
            if (response.Error) {
                showAlertMessageSwal('error', '', response.Message);
            } else {
                showToastNotification(response.Message, 'success');
                $(ModulePag).html(response.Pagination);
                $(ModuleTable + ' tbody').html(response.List);
                SelectedUIDs = [];
                updateCustomerStats(response.Stats);
                executeTablePagnCommonFunc(response, true);
            }
        },
    });
}

function toggleCustomerStatus(CustomerUID, IsActive) {
    $.ajax({
        url: '/customers/toggleCustomerStatus',
        method: 'POST',
        cache: false,
        data: { CustomerUID: CustomerUID, IsActive: IsActive, PageNo: PageNo, [CsrfName]: CsrfToken },
        success: function (response) {
            if (response.Error) {
                showAlertMessageSwal('error', '', response.Message);
            } else {
                showToastNotification(response.Message, 'success');
                $(ModulePag).html(response.Pagination);
                $(ModuleTable + ' tbody').html(response.List);
                updateCustomerStats(response.Stats);
                executeTablePagnCommonFunc(response, false);
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

// ── Page-level callback: refresh list/stats after any save ───────────────
function _custPageSaveSuccess(response) {
    if (response.List)       $(ModuleTable + ' tbody').html(response.List);
    if (response.Pagination) $(ModulePag).html(response.Pagination);
    if (response.Stats)      updateCustomerStats(response.Stats);
    if (typeof executeTablePagnCommonFunc === 'function') executeTablePagnCommonFunc(response, false);
}
_custPageSaveSuccess._needsList = true; // signals backend to return List/Pagination/Stats

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


