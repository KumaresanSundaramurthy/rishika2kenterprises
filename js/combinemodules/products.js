$(document).ready(function () {
    'use strict'

    // ──────────────────────────────────────────────
    // Product Type change
    // ──────────────────────────────────────────────
    $('#ProductType').on('change', function (e) {
        e.preventDefault();
        var getVal = $(this).val();
        $('#AddEditItemForm').find('#OpeningQuantity,#OpeningPurchasePrice,#OpeningStockValue').val(0);
        if (getVal == 'Product') {
            $('.OpeningStockDiv').removeClass('d-none');
        } else if (getVal == 'Service') {
            $('.OpeningStockDiv').addClass('d-none');
        }
    });

    // ──────────────────────────────────────────────
    // Is Size Applicable toggle
    // ──────────────────────────────────────────────
    $('#IsSizeApplicable').on('change', function () {
        $('#SizeDiv').addClass('d-none');
        $('#PSizeUID').removeAttr('required').val('').trigger('change');
        if ($(this).is(':checked')) {
            $('#SizeDiv').removeClass('d-none');
            $('#SizeDiv').attr('required', true);
            $('#PSizeUID').val('').trigger('change');
        }
    });

    // ──────────────────────────────────────────────
    // Discount Option change
    // ──────────────────────────────────────────────
    $('#DiscountOption').change(function (e) {
        e.preventDefault();
        $('#discTextAmountHelp,#discTextPercentHelp').addClass('d-none');
        var value = $(this).val();
        if (value == 1) {
            $('#Discount').attr('placeholder', 'Enter Discount Percentage');
            $('#discTextPercentHelp').removeClass('d-none');
            var Discount = $('#Discount').val();
            if (Discount > 0 && Discount > 100) {
                $('#Discount').val(0);
            }
        } else if (value == 2) {
            $('#discTextAmountHelp').removeClass('d-none');
            $('#Discount').attr('placeholder', 'Enter Discount Amount');
        }
    });

    // ──────────────────────────────────────────────
    // Selling Tax Option change
    // ──────────────────────────────────────────────
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

    // ──────────────────────────────────────────────
    // Customer Type Pricing — Add row
    // ──────────────────────────────────────────────
    $(document).on('click', '#AddCustomerPriceBtn', function (e) {
        e.preventDefault();
        var ctUID  = $('#CustomerTypeSelect').val();
        var ctName = $('#CustomerTypeSelect option:selected').text().trim();
        var price  = $('#CustomerTypePrice').val().trim();

        if (!ctUID) {
            Swal.fire({icon: "error", title: "Oops...", text: 'Please select a customer type.'});
            return;
        }
        if (!price || parseFloat(price) < 0) {
            Swal.fire({icon: "error", title: "Oops...", text: 'Please enter a valid selling price.'});
            return;
        }

        // Check duplicate
        var exists = false;
        $('#CustomerPricingBody tr[data-ctuid]').each(function () {
            if ($(this).data('ctuid') == ctUID) { exists = true; return false; }
        });
        if (exists) {
            Swal.fire({icon: "error", title: "Oops...", text: 'This customer type rate is already added.'});
            return;
        }

        addCustomerPriceRow(0, ctUID, ctName, price);
        $('#CustomerTypeSelect').val('').trigger('change');
        $('#CustomerTypePrice').val('');
        updateCustomerPricingData();
    });

    // ──────────────────────────────────────────────
    // Customer Type Pricing — Remove row
    // ──────────────────────────────────────────────
    $(document).on('click', '.RemoveCustomerPrice', function (e) {
        e.preventDefault();
        $(this).closest('tr').remove();
        renumberCustomerPriceRows();
        updateCustomerPricingData();
        if ($('#CustomerPricingBody tr[data-ctuid]').length === 0) {
            $('#CustomerPricingEmptyRow').show();
        }
    });

    // ──────────────────────────────────────────────
    // Customer Type Pricing — Price change inline
    // ──────────────────────────────────────────────
    $(document).on('change', '.CustomerPriceInput', function () {
        var val = parseFloat($(this).val());
        if (isNaN(val) || val < 0) $(this).val('');
        updateCustomerPricingData();
    });

    // ──────────────────────────────────────────────
    // Select2 inits
    // ──────────────────────────────────────────────
    if (EnableStorage == 1) {
        loadSelect2Field('#StorageUID', '-- Select Storage --', '#itemsModal');
    }
    loadTaxDetailOptions();
    loadSelect2Field('#PrimaryUnit',       '-- Select Primary Unit --', '#itemsModal');
    loadSelect2Field('#Category',          '-- Select Category --',     '#itemsModal');
    loadSelect2Field('#CustomerTypeSelect','-- Select Customer Type --', '#itemsModal');

    QuillEditor('.ql-toolbar', 'Enter product description...');

    // ──────────────────────────────────────────────
    // Form submit
    // ──────────────────────────────────────────────
    $('#AddEditItemForm').submit(function (e) {
        e.preventDefault();

        var formData = new FormData($('#AddEditItemForm')[0]);
        if (myOneDropzone.files.length > 0) {
            const file = myOneDropzone.files[0];
            if (!file.isStored) {
                formData.append('UploadImage', myOneDropzone.files[0]);
            }
        }
        var getProdHiddenId = $('#AddEditItemForm').find('#HProductUID').val();
        if (getProdHiddenId && hasValue(imgData) && myOneDropzone.files.length == 0) {
            formData.append('ImageRemoved', 1);
        }
        const Description = quill.getText().trim();
        if ($.trim(Description) != '') {
            formData.append('Description', $('#Description .ql-editor').html());
        }
        formData.append('PageNo',    PageNo);
        formData.append('RowLimit',  RowLimit);
        formData.append('ModuleId',  ItemModuleId);
        if (Object.keys(Filter).length > 0) {
            formData.append('Filter', JSON.stringify(Filter));
        }
        formData.append('IsSizeApplicable',  $('#IsSizeApplicable').is(':checked')  ? 1 : 0);
        formData.append('IsBrandApplicable', $('#IsBrandApplicable').is(':checked') ? 1 : 0);
        formData.append('IsSerialTracked',   $('#IsSerialTracked').is(':checked')   ? 1 : 0);
        formData.append('NotForSale',        $('#NotForSale').is(':checked')        ? 1 : 0);
        formData.append('getTableDetails', 1);

        updateCustomerPricingData();

        if (getProdHiddenId == 0) {
            addProductData(formData);
        } else {
            editProductData(formData);
        }
    });

});

// ──────────────────────────────────────────────────
// Customer Pricing helpers
// ──────────────────────────────────────────────────
function addCustomerPriceRow(rateUID, ctUID, ctName, price) {
    $('#CustomerPricingEmptyRow').hide();
    var count = $('#CustomerPricingBody tr[data-ctuid]').length + 1;
    var row = '<tr data-ctuid="' + ctUID + '" data-rateuid="' + rateUID + '">' +
                '<td>' + count + '</td>' +
                '<td>' + ctName + '</td>' +
                '<td><div class="input-group input-group-merge"><span class="input-group-text">' + currencySymbol + '</span><input type="text" class="form-control form-control-sm CustomerPriceInput" min="0" placeholder="Enter Price" onkeydown="return handleDotOnly(event)" oninput="this.value=this.value.slice(0,this.maxLength); validatePriceInput(this, 12, 2)" maxLength="12" pattern="^\\d{1,12}(\\.\\d{0,2})?$" onpaste="handlePricePaste(event, 12, 2)" ondrop="handlePriceDrop(event, 12, 2)" value="' + smartDecimal(price) + '" style="width: 50px !important;" /></div></td>' +
                '<td><button type="button" class="btn btn-sm btn-danger RemoveCustomerPrice"><i class="bx bx-trash"></i></button></td>' +
              '</tr>';
    $('#CustomerPricingBody').append(row);
}

function renumberCustomerPriceRows() {
    $('#CustomerPricingBody tr[data-ctuid]').each(function (i) {
        $(this).find('td:first').text(i + 1);
    });
}

function updateCustomerPricingData() {
    var rates = [];
    $('#CustomerPricingBody tr[data-ctuid]').each(function () {
        rates.push({
            RateUID: $(this).data('rateuid'),
            CustomerTypeUID: $(this).data('ctuid'),
            SellingPrice: $(this).find('.CustomerPriceInput').val()
        });
    });
    $('#CustomerPricingData').val(JSON.stringify(rates));
}

function loadCustomerPricingRows(pricingData) {
    $('#CustomerPricingBody tr[data-ctuid]').remove();
    $('#CustomerPricingEmptyRow').show();
    if (!pricingData || pricingData.length === 0) return;
    $.each(pricingData, function (i, row) {
        addCustomerPriceRow(row.RateUID, row.CustomerTypeUID, row.TypeName, row.SellingPrice);
    });
    updateCustomerPricingData();
}
