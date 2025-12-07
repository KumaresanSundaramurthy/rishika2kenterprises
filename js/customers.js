/**
 * @param {*} PageNo
 * @param {*} RowLimit
 * @param {*} Filter
 */
function getCustomersDetails(PageNo, RowLimit, Filter) {
    $.ajax({
        url: '/customers/getCustomersDetails/'+PageNo,
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
        },
    });
}

function addCustomerData(formdata) {
    $.ajax({
        url: '/customers/addCustomerData',
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
                $('#AddCustomerForm').trigger('reset');
                Swal.fire(response.Message, "", "success");
                setTimeout(function () {                    
                    window.history.back();
                }, 500);
            }
        }
    });
}

function editCustomerData(formdata) {
    $.ajax({
        url: '/customers/updateCustomerData',
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
                $('#EditCustomerForm').trigger('reset');
                Swal.fire(response.Message, "", "success");
                setTimeout(function () {                    
                    window.history.back();
                }, 500);
            }
        }
    });
}

function deleteCustomer(DeleteId) {
    $.ajax({
        url: '/customers/deleteCustomerData',
        method: 'POST',
        data: {
            RowLimit: RowLimit,
            PageNo: PageNo,
            Filter: Filter,
            CustomerUID: DeleteId,
            ModuleId: ModuleId
        },
        cache: false,
        success: function (response) {
            if (response.Error) {
                Swal.fire(response.Message, "", "danger");
            } else {
                Swal.fire(response.Message, "", "success");
                executeTablePagnCommonFunc(response, true);
            }
        }
    });
}

function deleteMultipleCustomers() {
    $.ajax({
        url: '/customers/deleteBulkCustomers',
        method: "POST",
        cache: false,
        data: {
            RowLimit: RowLimit,
            PageNo: PageNo,
            Filter: Filter,
            CustomerUIDs: SelectedUIDs,
            ModuleId: ModuleId
        },
        success: function (response) {
            if (response.Error) {
                Swal.fire(response.Message, "", "error");
            } else {
                SelectedUIDs = [];
                executeTablePagnCommonFunc(response, true);
            }
        },
    });
}

function enableBillingAddress() {
    $('#BillAddrState').select2({
        placeholder: '-- Select State --',
        allowClear: true,
    });
    $('#BillAddrCity').select2({
        placeholder: '-- Select City --',
        allowClear: true,
    });
}

function enableShippingAddress() {
    $('#ShipAddrState').select2({
        placeholder: '-- Select State --',
        allowClear: true,
    });
    $('#ShipAddrCity').select2({
        placeholder: '-- Select City --',
        allowClear: true,
    });
}

function select2CountryCode() {
    $('#CountryCode').select2({
        placeholder: '-- Select Country --',
        allowClear: true,
    });
}

function select2TagEmail() {
    $("#Tags,#CCEmails").select2({
        tags: "true",
        placeholder: "Type and press enter...",
        allowClear: true
    });
}

function creationBilngAddrActions() {
    $('#addBillingAddress').addClass('d-none');
    var DivId = $('#addBillingAddress').data('divid');
    $('#'+DivId).removeClass('d-none').html('');
    $('#'+DivId).html(baseAddressCreation(1, StateInfo, CityInfo, $('#CountryCode').find('option:selected').data('ccode')));
    $('#addShippingAddress,#AddressDivider').removeClass('d-none');
}

function creationShipAddrActions() {
    $('#addShippingAddress').addClass('d-none');
    $('#addrCopyToShipping').removeClass('d-none');
    var DivId = $('#addShippingAddress').data('divid');
    $('#'+DivId).removeClass('d-none').html('');
    $('#'+DivId).html(baseAddressCreation(2, StateInfo, CityInfo, $('#CountryCode').find('option:selected').data('ccode')));
}

$('#addBillingAddress').click(function(e) {
    e.preventDefault();
    creationBilngAddrActions();
});

$('#addShippingAddress').click(function(e) {
    e.preventDefault();
    creationShipAddrActions();
});

$('#addrCopyToShipping').click(function(e) {
    e.preventDefault();
    $('#ShipAddrLine1').val($('#BillAddrLine1').val());
    $('#ShipAddrLine2').val($('#BillAddrLine2').val());
    $('#ShipAddrPincode').val($('#BillAddrPincode').val());
    $('#ShipAddrState').val($('#BillAddrState').find('option:selected').val()).trigger('change');
    $('#ShipAddrCity').val($('#BillAddrCity').find('option:selected').val()).trigger('change');
});