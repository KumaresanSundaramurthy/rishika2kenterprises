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
        },
        success: function(response) {
            if (response.Error) {
                $(ModuleTable + ' tbody').html('');
                $(ModulePag).html('<div class="alert alert-danger" role="alert"><strong>' + response.Message + '</strong></div>');
            } else {
                $(ModulePag).html(response.Pagination);
                $(ModuleTable + ' tbody').html(response.List);
            }
            executeCommonFunc(response, false);
        },
    });
}

function addCustomerData(formdata) {

    AjaxLoading = 0;

    $('.addFormAlert').removeClass('d-none');
    inlineMessageAlert('.addFormAlert', 'info', 'Processing... Please wait', false, false);

    $('.AddCustomerBtn').attr('disabled', 'disabled');

    $.ajax({
        url: '/customers/addCustomerData',
        method: 'POST',
        data: formdata,
        cache: false,
        processData: false,
        contentType: false,
        enctype: 'multipart/form-data',
        success: function (response) {
            AjaxLoading = 1;
            $('.AddCustomerBtn').removeAttr('disabled');
            if (response.Error) {
                inlineMessageAlert('.addFormAlert', 'danger', response.Message, false, false);
            } else {
                inlineMessageAlert('.addFormAlert', 'success', response.Message, false, true);
                setTimeout(function () {
                    $('.addFormAlert').fadeOut(500, function () {
                        $(this).addClass('d-none').show();
                    });
                }, 1000);
                $('#AddCustomerForm').trigger('reset');
                window.history.back();
            }
        }
    });

}

function showAddressInfo(formData, BtnId, DivId) {
    $.ajax({
        url: '/customers/addAddressInfo',
        method: 'POST',
        data: formData,
        cache: false,
        processData: false,
        contentType: false,
        success: function (response) {
            $('#' + DivId).removeClass('d-none');
            if (response.Error) {
                inlineMessageAlert('#' + DivId, 'danger', response.Message, false, false);
            } else {
                $('#' + BtnId).addClass('d-none');
                $('#' + DivId).html(response.HtmlData);
                if (BtnId == 'addBillingAddress') {
                    $('#BillAddrState').select2({
                        placeholder: '-- Select State --',
                        allowClear: true,
                    });
                    $('#BillAddrCity').select2({
                        placeholder: '-- Select City --',
                        allowClear: true,
                    });
                } else if(BtnId == 'addShippingAddress') {
                    $('#ShipAddrState').select2({
                        placeholder: '-- Select State --',
                        allowClear: true,
                    });
                    $('#ShipAddrCity').select2({
                        placeholder: '-- Select City --',
                        allowClear: true,
                    });
                }
            }
        }
    });
}

function editCustomerData(formdata) {

    AjaxLoading = 0;

    $('.editFormAlert').removeClass('d-none');
    inlineMessageAlert('.editFormAlert', 'info', 'Processing... Please wait', false, false);

    $('.EditCustomerBtn').attr('disabled', 'disabled');

    $.ajax({
        url: '/customers/updateCustomerData',
        method: 'POST',
        data: formdata,
        cache: false,
        processData: false,
        contentType: false,
        enctype: 'multipart/form-data',
        success: function (response) {
            $('.editFormAlert').removeClass('d-none');
            if (response.Error) {
                inlineMessageAlert('.editFormAlert', 'danger', response.Message, false, false);
            } else {
                inlineMessageAlert('.editFormAlert', 'success', response.Message, false, true);
                setTimeout(function () {
                    $('.editFormAlert').fadeOut(500, function () {
                        $(this).addClass('d-none').show();
                    });
                }, 1000);
                $('#EditCustomerForm').trigger('reset');
                window.history.back();
            }
        }
    });

}

function deleteCustomer(DeleteId) {
    $.ajax({
        url: '/customers/deleteCustomerData',
        method: 'POST',
        data: {
            CustomerUID: DeleteId
        },
        cache: false,
        success: function (response) {
            if (response.Error) {
                Swal.fire(response.Message, "", "danger");
            } else {
                getCustomersDetails(0, RowLimit, Filter);
                Swal.fire(response.Message, "", "success");
            }
        }
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

$('#addBillingAddress').click(function(e) {
    e.preventDefault();

    var DivId = $(this).data('divid');
    $('#'+DivId).addClass('d-none').html('');
    
    var formData = new FormData();
    formData.append('AddressType', 1);
    formData.append('CountryCode', $('#CountryCode').find('option:selected').data('ccode'));
    showAddressInfo(formData, 'addBillingAddress', DivId);

});

$('#addShippingAddress').click(function(e) {
    e.preventDefault();

    var DivId = $(this).data('divid');
    $('#'+DivId).addClass('d-none').html('');
    
    var formData = new FormData();
    formData.append('AddressType', 2);
    formData.append('CountryCode', $('#CountryCode').find('option:selected').data('ccode'));
    showAddressInfo(formData, 'addShippingAddress', DivId);

});

$(document).on('click', '#deleteBillingAddress', function(e) {
    e.preventDefault();
    $('#appendBillingAddress').addClass('d-none').html(' ');
    $('#addBillingAddress').removeClass('d-none');
});

$(document).on('click', '#deleteShippingAddress', function(e) {
    e.preventDefault();
    $('#appendShippingAddress').addClass('d-none').html(' ');
    $('#addShippingAddress').removeClass('d-none');
});