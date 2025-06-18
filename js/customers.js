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
        beforeSend: function() {
            showUIBlock();
        },
        success: function(response) {

            hideUIBlock();

            if (response.Error) {
                $('#CustomersTable tbody').html('');
                $('.CustomersPagination').html('<div class="alert alert-danger" role="alert"><strong>'+response.Message+'</strong></div>');
            } else {
                $('.CustomersPagination').html(response.Pagination);
                $('#CustomersTable tbody').html(response.List);
            }
        },
        complete: function() {
            hideUIBlock();
        }
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
            
            $('#addFormAlert').removeClass('d-none');
            if (response.Error) {

                inlineMessageAlert('#addFormAlert', 'danger', response.Message, false, false);

            } else {

                inlineMessageAlert('#addFormAlert', 'success', response.Message, false, true);
                setTimeout(function () {
                    $('#addFormAlert').fadeOut(500, function () {
                        $(this).addClass('d-none').show();

                    });
                }, 1000);

                imageChange = 0;
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

    $.ajax({
        url: '/customers/updateCustomerData',
        method: 'POST',
        data: formdata,
        cache: false,
        processData: false,
        contentType: false,
        enctype: 'multipart/form-data',
        success: function (response) {
            
            $('#editFormAlert').removeClass('d-none');
            if (response.Error) {

                inlineMessageAlert('#editFormAlert', 'danger', response.Message, false, false);

            } else {

                inlineMessageAlert('#editFormAlert', 'success', response.Message, false, true);
                setTimeout(function () {
                    $('#editFormAlert').fadeOut(500, function () {
                        $(this).addClass('d-none').show();
                    });
                }, 1000);

                imageChange = 0;
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