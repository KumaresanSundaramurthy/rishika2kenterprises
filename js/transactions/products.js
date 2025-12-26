$(document).ready(function () {
    'use strict'

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

    $('#IsSizeApplicable').on('change', function () {
        $('#SizeDiv').addClass('d-none');
        $('#PSizeUID').removeAttr('required').val('').trigger('change');
        if ($(this).is(':checked')) {
            $('#SizeDiv').removeClass('d-none');
            $('#SizeDiv').attr('required', true);
            $('#PSizeUID').val('').trigger('change');
        }
    });

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

    $(document).on('click', '#addTransProduct', function(e) {
        e.preventDefault();
        prodOpenCloseDefActions();
        $('#itemsModal').modal('show');
    });

    $('#itemsModal').on('shown.bs.modal', function() {
        $('#AddEditItemForm #ItemName').trigger('focus');
    });

    $('#itemsModal').on('hide.bs.modal', function() {
        prodOpenCloseDefActions();
    });

    if (EnableStorage == 1) {
        loadSelect2Field('#StorageUID', '-- Select Storage --', '#itemsModal');
    }

    loadTaxDetailOptions();
    loadSelect2Field('#PrimaryUnit', '-- Select Primary Unit --', '#itemsModal');
    loadSelect2Field('#Category', '-- Select Category --', '#itemsModal');

    QuillEditor('.ql-toolbar', 'Enter product description...');

    $('#AddEditItemForm').submit(function(e) {
        e.preventDefault();

        var formData = new FormData($('#AddEditItemForm')[0]);
        if (myOneDropzone.files.length > 0) {
            const file = myOneDropzone.files[0];
            if (!file.isStored) {
                formData.append('UploadImage', myOneDropzone.files[0]);
            }
        }
        var getProdHiddenId = $('#AddEditItemForm').find('#HProductUID').val();
        if(getProdHiddenId && hasValue(imgData) && myOneDropzone.files.length == 0) {
            formData.append('ImageRemoved', 1);
        }
        const Description = quill.getText().trim(); // quill.root.innerHTML;
        if ($.trim(Description) != '') {
            formData.append('Description', $('#Description .ql-editor').html());
        }
        if (Object.keys(Filter).length > 0) {
            formData.append('Filter', JSON.stringify(Filter));
        }
        formData.append('IsSizeApplicable', $('#IsSizeApplicable').is(':checked') ? 1 : 0);
        formData.append('NotForSale', $('#NotForSale').is(':checked') ? 1 : 0);
        formData.append('getTableDetails', 0);
        
        if (getProdHiddenId == 0) {
            addProductData(formData);
        } else {
            editProductData(formData);
        }

    });

});

function prodOpenCloseDefActions() {
    $('#AddEditItemForm').trigger('reset');
    $('#ItemModalTitle').text('Create Item');
    $('.AddEditProductBtn').text('Save');
    $('#AddEditItemForm').find('#HProductUID').val(0);
    $('#ProductType').val('Product').trigger('change');
    $('#SellingTaxOption,#PurchaseTaxOption,#DiscountOption').val(1).trigger('change');
    $('#TaxPercentage,#PrimaryUnit,#Category,#StorageUID,#BrandUID,#PSizeUID').val(null).trigger('change');
    $('#IsSizeApplicable,#NotForSale').prop('checked', false).trigger('change');
    $('#SizeDiv').addClass('d-none');
    myOneDropzone.removeAllFiles(true);
    quill.setContents([]);
}

function loadTaxDetailOptions() {
    $('#TaxPercentage').select2({
        placeholder: '-- Select Tax Percentage --',
        allowClear: false,
        width: 'resolve',
        templateResult: function (data) {
            if (!data.id) return data.text;
            const el = $(data.element);
            const left  = el.data('left');
            const right = el.data('right');
            return $('<div class="d-flex justify-content-between">' +
                    '<span class="fw-semibold">' + left + '</span>' +
                    '<span class="text-muted small">' + right + '</span>' +
                    '</div>');
        },
        templateSelection: function (data) {
            if (!data.id) return data.text;
            const el = $(data.element);
            const left  = el.data('left');
            const right = el.data('right');
            return $('<div class="d-flex justify-content-between">' +
                    '<span class="fw-semibold">' + left + '</span>' +
                    '<span class="text-muted small">' + right + '</span>' +
                    '</div>');
        },
        dropdownParent: '#itemsModal',
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
            if (response.Error) {
                Swal.fire({icon: "error", title: "Oops..", text: response.Message});
            } else {
                prodOpenCloseDefActions();
                $('#itemsModal').modal('hide');
            }
        }
    });
}

function retrieveProductDetails(ItemUID, CloneFlag = false) {
    $.ajax({
        url: '/products/retrieveProductDetails',
        method: "POST",
        cache: false,
        data: {
            ItemUID: ItemUID,
        },
        success: function (response) {
            if (response.Error) {
                Swal.fire({icon: "error", title: "Oops...", text: response.Message});
            } else {

                $('#AddEditItemForm').trigger('reset');
                if (CloneFlag) {
                    $('#ItemModalTitle').text('Create Item');
                    $('.AddEditProductBtn').text('Save');
                } else {
                    $('#ItemModalTitle').text('Edit Item');
                    $('.AddEditProductBtn').text('Update');
                }
                clearItemValues();
                $('#itemsModal').modal('show');

                if (CloneFlag) {
                    $('#HProductUID').val(0);
                } else {
                    $('#HProductUID').val(response.Data.ProductUID);
                }

                $('#ItemName').val(response.Data.ItemName);
                $('#ProductType').val(response.Data.ProductType);
                $('#SellingPrice').val(smartDecimal(response.Data.SellingPrice));
                $('#SellingTaxOption').val(response.Data.SellingProductTaxUID).trigger('change');
                $('#TaxPercentage').val(response.Data.TaxDetailsUID).trigger('change');
                $('#PrimaryUnit').val(response.Data.PrimaryUnitUID).trigger('change');
                $('#Category').val(response.Data.CategoryUID).trigger('change');
                $('#PurchasePrice').val(smartDecimal(response.Data.PurchasePrice));
                $('#PurchaseTaxOption').val(response.Data.PurchasePriceProductTaxUID).trigger('change');
                if (EnableStorage) {
                    $('#StorageUID').val(response.Data.StorageUID).trigger('change');
                }

                $('#HSNCode').val(response.Data.HSNSACCode);
                $('#Standard').val(response.Data.Standard);
                $('#BrandUID').val(response.Data.BrandUID).trigger('change');
                $('#Model').val(response.Data.Model);
                $('#PartNumber').val(response.Data.PartNumber);
                if (response.Data.IsSizeApplicable == 1) {
                    $('#IsSizeApplicable').prop('checked', true).trigger('change');
                    $('#SizeDiv').removeClass('d-none');
                    $('#PSizeUID').val(response.Data.SizeUID).trigger('change').prop('required', true);
                }
                
                if (hasValue(response.Data.Image)) {
                    var ImageUrl = CDN_URL + response.Data.Image;
                    commonSetDropzoneImageOne(ImageUrl);
                    imgData = ImageUrl;
                }
                if (response.Data.Description != null && response.Data.Description != undefined) {
                    appendToQuill(response.Data.Description, true);
                }

                $('#OpeningQuantity').val(smartDecimal(response.Data.OpeningQuantity));
                $('#OpeningPurchasePrice').val(smartDecimal(response.Data.OpeningPurchasePrice));
                $('#OpeningStockValue').val(smartDecimal(response.Data.OpeningStockValue));

                $('#Discount').val(smartDecimal(response.Data.Discount));
                $('#DiscountOption').val(response.Data.DiscountTypeUID).trigger('change');
                $('#LowStockAlert').val(smartDecimal(response.Data.LowStockAlertAt));
                if (response.Data.NotForSale == 'Yes') {
                    $('#NotForSale').prop('checked', true);
                }

            }
        },
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
            if (response.Error) {
                $('.addEditFormAlert').removeClass('d-none');
                inlineMessageAlert('.addEditFormAlert', 'danger', response.Message, false, false);
            } else {
                myOneDropzone.removeAllFiles(true);
                quill.setContents([]);
                $('#AddEditItemForm').trigger('reset');
                $('#itemsModal').modal('hide');
                clearItemValues();
                executeProdPagnFunc(response, true);
            }
        }
    });
}