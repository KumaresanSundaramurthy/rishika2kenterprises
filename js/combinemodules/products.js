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
    // Is Rentable toggle
    // ──────────────────────────────────────────────
    $('#IsRentable').on('change', function () {
        if ($(this).is(':checked')) {
            $('#rentalConfigSection').removeClass('d-none');
        } else {
            $('#rentalConfigSection').addClass('d-none');
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
    // Select2 inits
    // ──────────────────────────────────────────────
    if (EnableStorage == 1) {
        loadSelect2Field('#StorageUID', '-- Select Storage --', '#itemsModal');
    }
    loadTaxDetailOptions();
    loadSelect2Field('#PrimaryUnit',        '-- Select Primary Unit --', '#itemsModal');
    initCategorySelect2();
    QuillEditor('.ql-toolbar', 'Enter product description...');

    // ──────────────────────────────────────────────
    // Form submit
    // ──────────────────────────────────────────────
    $('#AddEditItemForm').submit(function (e) {
        e.preventDefault();

        var formData = new FormData($('#AddEditItemForm')[0]);
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
        formData.append('IsRentable',        $('#IsRentable').is(':checked')        ? 1 : 0);
        if ($('#IsRentable').is(':checked')) {
            formData.append('rc_SecurityDeposit',   $('#rc_SecurityDeposit').val()   || 0);
            formData.append('rc_HourlyRate',        $('#rc_HourlyRate').val()        || 0);
            formData.append('rc_HalfDayRate',       $('#rc_HalfDayRate').val()       || 0);
            formData.append('rc_FullDayRate',       $('#rc_FullDayRate').val()       || 0);
            formData.append('rc_FixedPackageRate',  $('#rc_FixedPackageRate').val()  || 0);
            formData.append('rc_ExtraHourRate',     $('#rc_ExtraHourRate').val()     || 0);
            formData.append('rc_LateReturnCharge',  $('#rc_LateReturnCharge').val()  || 0);
            formData.append('rc_DamagePenaltyRate', $('#rc_DamagePenaltyRate').val() || 0);
            formData.append('rc_MinRentalHours',    $('#rc_MinRentalHours').val()    || 1);
        }
        formData.append('getTableDetails', 1);

        if (getProdHiddenId == 0) {
            addProductData(formData);
        } else {
            editProductData(formData);
        }
    });

});

// ── Category Select2 with inline "+ Create" option ────────────────────
function initCategorySelect2() {
    $('#Category').select2({
        placeholder: '-- Select Category --',
        allowClear: true,
        width: '100%',
        dropdownParent: $('#itemsModal'),
        tags: true,
        createTag: function (params) {
            var term = $.trim(params.term);
            if (!term) return null;
            // Check if exact match exists in options
            var matched = false;
            $('#Category option').each(function () {
                if ($(this).text().toLowerCase() === term.toLowerCase()) {
                    matched = true;
                    return false;
                }
            });
            if (matched) return null;
            return { id: '__new__' + term, text: term, newTag: true };
        },
        templateResult: function (data) {
            if (data.newTag) {
                return $('<span class="text-primary fw-semibold"><i class="bx bx-plus-circle me-1"></i> Create "' + $('<span>').text(data.text).html() + '"</span>');
            }
            return data.text;
        },
        templateSelection: function (data) {
            if (data.newTag) return '';
            return data.text;
        }
    });

    $('#Category').on('select2:select', function (e) {
        if (e.params.data.newTag) {
            var name = e.params.data.text;
            $('#Category').val(null).trigger('change');
            _openCatgModal(name);
        }
    });
}

function _openCatgModal(name) {
    $('#categoryForm').trigger('reset');
    $('#CatgModalTitle').text('Add Category');
    $('#CatgSaveButton').text('Save');
    $('#categoryForm #CategoryUID').val(0);
    if (typeof myTwoDropzone !== 'undefined') myTwoDropzone.removeAllFiles(true);
    $('#CategoryName').val(name);
    $('#categoryModal').data('calledFromItemForm', true);
    $('#categoryModalDialog').addClass('modal-md').removeClass('modal-lg');
    $('#categoryModal').modal('show');
}

$(document).on('catgSavedFromItemForm', function (e, data) {
    if (!data || !data.id || !data.name) return;
    $('#categoryModalDialog').removeClass('modal-md').addClass('modal-lg');
    // Option already added by updateCategoryOptions — just select it
    $('#Category').val(data.id).trigger('change');
    // Re-open the item modal if it was hidden
    if (!$('#itemsModal').hasClass('show')) {
        $('#itemsModal').modal('show');
    }
});
