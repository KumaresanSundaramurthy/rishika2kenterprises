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

    $('#AddCustomerBtn').prop('disabled', 'disabled');

    $.ajax({
        url: '/customers/addCustomerData',
        method: 'POST',
        data: formdata,
        cache: false,
        processData: false,
        contentType: false,
        enctype: 'multipart/form-data',
        success: function (response) {

            $('#AddCustomerBtn').removeAttr('disabled');
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
                        selectOnClose: true,
                        allowClear: true,
                    });

                    $('#BillAddrCity').select2({
                        placeholder: '-- Select City --',
                        selectOnClose: true,
                        allowClear: true,
                    });
                } else if(BtnId == 'addBillingAddress') {
                    $('#ShipAddrState').select2({
                        placeholder: '-- Select State --',
                        selectOnClose: true,
                        allowClear: true,
                    });

                    $('#ShipAddrCity').select2({
                        placeholder: '-- Select City --',
                        selectOnClose: true,
                        allowClear: true,
                    });
                }
            }

        }
    });

}