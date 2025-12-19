/**
 * @param {*} PageNo
 * @param {*} RowLimit
 * @param {*} Filter
 */
function getCustomersDetails(PageNo, RowLimit, Filter) {
    $.ajax({
        url: '/globally/getModPageDataDetails/' + PageNo,
        method: "POST",
        cache: false,
        data: {
            RowLimit: RowLimit,
            PageNo: PageNo,
            Filter: Filter,
            ModuleId: ModuleId
        },
        success: function(response) {
            if (response.Error) {
                $(ModuleTable + ' tbody').html('');
                $(ModulePag).html('<div class="alert alert-danger" role="alert"><strong>' + response.Message + '</strong></div>');
            } else {
                $(ModulePag).html(response.Pagination);
                $(ModuleTable + ' tbody').html(response.List);
            }
            executeTablePagnCommonFunc(response, false);
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

function deleteVendor(DeleteId) {
    $.ajax({
        url: '/customers/deleteVendorData',
        method: 'POST',
        data: {
            RowLimit: RowLimit,
            PageNo: PageNo,
            Filter: Filter,
            VendorUID: DeleteId,
            ModuleId: ModuleId
        },
        cache: false,
        success: function (response) {
            if (response.Error) {
                Swal.fire(response.Message, "", "danger");
                $(ModuleTable + ' tbody').html('');
                $(ModulePag).html('<div class="alert alert-danger" role="alert"><strong>' + response.Message + '</strong></div>');
            } else {
                Swal.fire(response.Message, "", "success");
                $(ModulePag).html(response.Pagination);
                $(ModuleTable + ' tbody').html(response.List);
            }
            executeTablePagnCommonFunc(response, true);
        }
    });
}

function deleteMultipleVendors() {
    $.ajax({
        url: '/vendors/deleteBulkVendors',
        method: "POST",
        cache: false,
        data: {
            RowLimit: RowLimit,
            PageNo: PageNo,
            Filter: Filter,
            VendorUIDs: SelectedUIDs,
            ModuleId: ModuleId
        },
        success: function (response) {
            if (response.Error) {
                Swal.fire(response.Message, "", "danger");
                $(ModuleTable + ' tbody').html('');
                $(ModulePag).html('<div class="alert alert-danger" role="alert"><strong>' + response.Message + '</strong></div>');
            } else {
                Swal.fire(response.Message, "", "success");
                $(ModulePag).html(response.Pagination);
                $(ModuleTable + ' tbody').html(response.List);
                SelectedUIDs = [];
                executeTablePagnCommonFunc(response, true);
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