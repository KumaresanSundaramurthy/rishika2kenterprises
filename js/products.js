/**
 * @param {*} PageNo
 * @param {*} RowLimit
 * @param {*} Filter
 */
function getProductDetails(PageNo, RowLimit, Filter) {

    $.ajax({
        url: '/products/getProductDetails/' + PageNo,
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
                $('#ProductsTable tbody').html('');
                $('.ProductsPagination').html('<div class="alert alert-danger" role="alert"><strong>' + response.Message + '</strong></div>');
            } else {
                $('.ProductsPagination').html(response.Pagination);
                $('#ProductsTable tbody').html(response.List);
            }
        },
        complete: function () {
            hideUIBlock();
        }
    });

}

function addProductData(formdata) {

    $.ajax({
        url: '/products/addProductData',
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

                $('#AddItemForm').trigger('reset');
                myOneDropzone.removeAllFiles(true);
                window.history.back();

            }

        }
    });

}

function editProductData(formdata) {

    $.ajax({
        url: '/products/updateProductData',
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

                $('#EditItemForm').trigger('reset');
                myOneDropzone.removeAllFiles(true);
                window.history.back();

            }

        }
    });

}

/**
 * @param {*} PageNo
 * @param {*} RowLimit
 * @param {*} Filter
 */
function getCategoriesDetails(PageNo, RowLimit, Filter) {

    $.ajax({
        url: '/products/getCategoriesDetails/' + PageNo,
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
                $('#CategoriesTable tbody').html('');
                $('.CategoriesPagination').html('<div class="alert alert-danger" role="alert"><strong>' + response.Message + '</strong></div>');
            } else {
                $('.CategoriesPagination').html(response.Pagination);
                $('#CategoriesTable tbody').html(response.List);
            }
        },
        complete: function () {
            hideUIBlock();
        }
    });

}

function addCategoryDetails(formdata) {

    $.ajax({
        url: '/products/addCategoryDetails',
        method: 'POST',
        data: formdata,
        cache: false,
        processData: false,
        contentType: false,
        enctype: 'multipart/form-data',
        success: function (response) {

            $('#addCatgFormAlert').removeClass('d-none');
            if (response.Error) {

                inlineMessageAlert('#addCatgFormAlert', 'danger', response.Message, false, false);

            } else {

                inlineMessageAlert('#addCatgFormAlert', 'success', response.Message, false, true);
                setTimeout(function () {
                    $('#addCatgFormAlert').fadeOut(500, function () {
                        $(this).addClass('d-none').show();
                    });
                }, 1000);

                myDropzone.removeAllFiles(true);
                $('#AddCategoryForm').trigger('reset');
                $('#categoryModal').modal('hide');

                getCategoriesDetails(PageNo, RowLimit, Filter);

            }

        }
    });

}

$('#IsSizeApplicable').on('change', function () {
    $('#SizeDiv').addClass('d-none');
    $('#SizeUID').removeAttr('required');
    if ($(this).is(':checked')) {
        $('#SizeDiv').removeClass('d-none');
        $('#SizeDiv').attr('required', true);
    }
});

$('#DiscountOption').change(function (e) {
    e.preventDefault();
    var value = $(this).val();
    if (value == 1) {
        $('#Discount').attr('placeholder', 'Enter Discount Percentage');
        var Discount = $('#Discount').val();
        if (Discount > 0 && Discount > 100) {
            $('#Discount').val(0);
        }
    } else if (value == 2) {
        $('#Discount').attr('placeholder', 'Enter Discount Amount');
    }
});

$('#SellingTaxOption').change(function (e) {
    e.preventDefault();
    var getVal = $(this).find('option:selected').val();
    if (getVal) {
        $('#SellingPriceTaxHelp,#SellingPriceWTaxHelp').addClass('d-none');
        if (getVal == '1') {
            $('#SellingPriceTaxHelp').removeClass('d-none');
        } else if (getVal == '2') {
            $('#SellingPriceWTaxHelp').removeClass('d-none');
        }
    }
});