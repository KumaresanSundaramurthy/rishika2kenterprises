/**
 * @param {*} PageNo
 * @param {*} RowLimit
 * @param {*} Filter
 */
function getVendorsDetails(PageNo, RowLimit, Filter) {

    $.ajax({
        url: '/vendors/getVendorsDetails/' + PageNo,
        method: "POST",
        cache: false,
        data: {
            RowLimit: RowLimit,
            PageNo: PageNo,
            Filter: Filter,
        },
        beforeSend: function () {
            showUIBlock();
        },
        success: function (response) {

            hideUIBlock();

            if (response.Error) {
                $('#VendorsTable tbody').html('');
                $('.VendorsPagination').html('<div class="alert alert-danger" role="alert"><strong>' + response.Message + '</strong></div>');
            } else {
                $('.VendorsPagination').html(response.Pagination);
                $('#VendorsTable tbody').html(response.List);
            }
        },
        complete: function () {
            hideUIBlock();
        }
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
                $('#AddVendorForm').trigger('reset');
                window.history.back();

            }

        }
    });

}

function searchCustomers(key) {
    $("#"+key).select2({
        placeholder: "-- Search Customers --",
        minimumInputLength: 3,
        allowClear: true,
        "escapeMarkup": function (markup) {
            return markup;
        }, // let our custom formatter work
        ajax: {
            url: '/customers/searchCustomers',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                var query = {
                    term: params.term,
                    type: 'public'
                };
                return query;
            },
            processResults: function (data) {
                return {
                    results: data.Lists
                };
            },
            cache: true
        }
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
                $('#EditVendorForm').trigger('reset');
                window.history.back();

            }

        }
    });

}

$('#BillAddrState').select2({
    placeholder: '-- Select State --',
    allowClear: true,
});

$('#BillAddrCity').select2({
    placeholder: '-- Select City --',
    allowClear: true,
});