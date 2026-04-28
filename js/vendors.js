/**
 * @param {*} PageNo
 * @param {*} RowLimit
 * @param {*} Filter
 */
function getVendorsDetails(PageNo, RowLimit, Filter) {
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
            if (response.Error) {
                $(ModuleTable + ' tbody').html('');
                $(ModulePag).html('<div class="alert alert-danger" role="alert"><strong>' + response.Message + '</strong></div>');
            } else {
                $(ModulePag).html(response.Pagination);
                $(ModuleTable + ' tbody').html(response.RecordHtmlData);
            }
        },
    });
}

function addVendorData(formdata) {
    $.ajax({
        url: '/vendors/addVendorData',
        method: 'POST',
        data: formdata,
        cache: false,
        processData: false,
        contentType: false,
        enctype: 'multipart/form-data',
        success: function (response) {
            if (response.Error) {
                $('.addEditFormAlert').removeClass('d-none');
                $('.addEditFormAlert').find('.alert-message').text(response.Message);
            } else {
                $('#AddVendorForm').trigger('reset');
                Swal.fire(response.Message, "", "success");
                setTimeout(function () {                    
                    window.history.back();
                }, 250);
            }
        }
    });
}

function searchCustomers(key) {
    $("#"+key).select2({
        placeholder: "-- Search Customers --",
        minimumInputLength: 3,
        allowClear: true,
        escapeMarkup: function (markup) { return markup; },
        ajax: {
            url: '/customers/searchCustomers',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                AjaxLoading = 0;
                return { term: params.term, type: 'public' };
            },
            processResults: function (data) {
                AjaxLoading = 1;
                return { results: data.Lists };
            },
            cache: true
        }
    }).on('select2:close', function() {
        AjaxLoading = 1;
    });
}

function editVendorData(formdata) {
    $.ajax({
        url: '/vendors/updateVendorData',
        method: 'POST',
        data: formdata,
        cache: false,
        processData: false,
        contentType: false,
        enctype: 'multipart/form-data',
        success: function (response) {
            if (response.Error) {
                $('.addEditFormAlert').removeClass('d-none');
                $('.addEditFormAlert').find('.alert-message').text(response.Message);
            } else {
                $('#EditVendorForm').trigger('reset');
                Swal.fire(response.Message, "", "success");
                setTimeout(function () {                    
                    window.history.back();
                }, 250);
            }
        }
    });
}

// ── Refresh vendor stats cards ──────────────────────────────────────────
function refreshVendorStats() {
    $.ajax({
        url   : '/vendors/getStats',
        method: 'POST',
        data  : { [CsrfName]: CsrfToken },
        success: function (resp) {
            if (resp.Error || !resp.Stats) return;
            var s = resp.Stats;
            $('.vend-stat-total').text(Number(s.TotalCount  || 0).toLocaleString('en-IN'));
            $('.vend-stat-active').text(Number(s.ActiveCount || 0).toLocaleString('en-IN'));
            $('.vend-stat-month').text(Number(s.MonthCount  || 0).toLocaleString('en-IN'));
            $('.vend-stat-fy').text(Number(s.FYCount        || 0).toLocaleString('en-IN'));
            $('.vend-stat-lastmonth').text(Number(s.LastMonthCount || 0).toLocaleString('en-IN'));
        }
    });
}

// ── Toggle vendor active/inactive status ────────────────────────────────
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
                showAlertMessageSwal('success', '', response.Message, true, 1500);
                getVendorsDetails(PageNo, RowLimit, Filter);
                refreshVendorStats();
            }
        }
    });
}

// ── Delete single vendor ─────────────────────────────────────────────────
function deleteVendor(DeleteId) {
    $.ajax({
        url   : '/vendors/deleteVendorData',
        method: 'POST',
        cache : false,
        data  : { VendorUID: DeleteId, PageNo: PageNo, [CsrfName]: CsrfToken },
        success: function (response) {
            if (response.Error) {
                Swal.fire({ icon: 'error', text: response.Message });
            } else {
                Swal.fire({ icon: 'success', text: response.Message, timer: 1500, showConfirmButton: false });
                $(ModulePag).html(response.Pagination);
                $(ModuleTable + ' tbody').html(response.List);
                refreshVendorStats();
            }
        }
    });
}

// ── Delete multiple vendors ──────────────────────────────────────────────
function deleteMultipleVendors() {
    $.ajax({
        url   : '/vendors/deleteMultipleVendors',
        method: 'POST',
        cache : false,
        data  : { 'VendorUIDs[]': SelectedUIDs, PageNo: PageNo, [CsrfName]: CsrfToken },
        success: function (response) {
            if (response.Error) {
                Swal.fire({ icon: 'error', text: response.Message });
            } else {
                Swal.fire({ icon: 'success', text: response.Message, timer: 1500, showConfirmButton: false });
                $(ModulePag).html(response.Pagination);
                $(ModuleTable + ' tbody').html(response.List);
                SelectedUIDs = [];
                refreshVendorStats();
            }
        }
    });
}
        },
    });
}

$(document).on('click', '#ResetCustomerLinking', function () {
    $(this).addClass('d-none');
    $('input[name="CustomerLinkingCheck"]').prop('checked', false);
    $('#CustomerDiv').addClass('d-none');
    $('#Customer').removeAttr('required');
});

$('input[name="CustomerLinkingCheck"]').change(function() {
    $('#CustomerDiv').addClass('d-none')
    $('#Customer').removeAttr('required');
    var selectedValue = $(this).val();
    if (selectedValue == 'OldCustomer') {
        $('#CustomerDiv').removeClass('d-none');
        $('#Customer').prop('required', true);
    }
    $('#ResetCustomerLinking').removeClass('d-none');
});