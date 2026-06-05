/**
 * ProductForm — shared modal for adding/editing a product from any page.
 *
 * Usage:
 *   ProductForm.open('add', null, { prefillName: 'Widget', onSaveSuccess: fn });
 *   ProductForm.open('edit', uid, { onSaveSuccess: fn });
 *   ProductForm.open('clone', uid, { onSaveSuccess: fn });
 *
 * onSaveSuccess(response) fires after a successful save.
 *   response.Product  — product data object for auto-select on transaction pages
 *   response.List / response.Pagination / response.Stats — only when _needsList = true
 *
 * Products page:
 *   var cb = function(r) { executeProdPagnFunc(r, true, true); };
 *   cb._needsList = true;
 *   ProductForm.open('add', null, { onSaveSuccess: cb });
 */
(function (window, $) {
    'use strict';

    var _onSaveSuccess = null;
    var _pfImgData     = '';
    var _pfInitDone    = false;

    window.ProductForm = { open: openProductModal };

    // ── Open ─────────────────────────────────────────────────────────────────
    function openProductModal(type, uid, opts) {
        opts           = opts || {};
        _onSaveSuccess = opts.onSaveSuccess || null;

        if (!_pfInitDone) {
            _pfInit();
        }

        if ((type === 'edit' || type === 'clone') && uid) {
            _loadForEdit(uid, type === 'clone');
        } else {
            _resetProductModal();
            if (opts.prefillName) { $('#ItemName').val(opts.prefillName); }
            CategoryAppend.populateSelect('#Category', function () {
                $('#ProductFormModal').modal('show');
                setTimeout(function () { $('#ItemName').focus(); }, 300);
            });
        }
    }

    // ── Tax % Select2 (always scoped to #ProductFormModal) ───────────────
    function _pfInitTaxSelect2($modal) {
        var $el = $modal.find('#TaxPercentage');
        if (!$el.length) return;
        if ($el.hasClass('select2-hidden-accessible')) $el.select2('destroy');
        $el.select2({
            placeholder              : '-- Select Tax Percentage --',
            allowClear               : true,
            width                    : 'resolve',
            minimumResultsForSearch  : Infinity,
            dropdownParent           : $modal,
            templateResult: function (data) {
                if (!data.id) return data.text;
                var el    = $(data.element);
                var left  = el.data('left');
                var right = el.data('right');
                if (left == null || left === '') return data.text;
                return $('<div class="d-flex justify-content-between align-items-center">' +
                        '<span class="fw-semibold">' + left + '</span>' +
                        '<span class="text-muted small">' + right + '</span>' +
                        '</div>');
            },
            templateSelection: function (data) {
                if (!data.id) return data.text;
                var el    = $(data.element);
                var left  = el.data('left');
                var right = el.data('right');
                if (left == null || left === '') return data.text;
                return $('<span style="display:flex;align-items:center;width:100%;min-width:0;padding-right:20px;">' +
                        '<span style="flex-shrink:0;font-weight:600;margin-right:8px;white-space:nowrap;">' + left + '</span>' +
                        '<span style="flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:#6c757d;font-size:.82em;">' + right + '</span>' +
                        '</span>');
            }
        });

        $el.data('select2').$container.addClass('r2k-tax-pct-s2');
        if (!$('#r2k-tax-pct-sel2-style').length) {
            $('<style id="r2k-tax-pct-sel2-style">' +
                '#select2-TaxPercentage-container{display:flex!important;align-items:center!important;overflow:hidden!important;}' +
            '</style>').appendTo('head');
        }
    }

    // ── Initialise Select2s, Dropzone, Quill (once) ───────────────────────
    function _pfInit() {
        _pfInitDone = true;

        var $modal = $('#ProductFormModal');

        // Storage
        if (typeof _pfEnableStorage !== 'undefined' && _pfEnableStorage == 1) {
            loadSelect2Field('#StorageUID', '-- Select Storage --', '#ProductFormModal');
        }

        // Tax % — inline init so this always uses ProductFormModal as parent
        _pfInitTaxSelect2($modal);

        // Unit / CustomerType
        loadSelect2Field('#PrimaryUnit',        '-- Select Primary Unit --',   '#ProductFormModal');
        loadSelect2Field('#CustomerTypeSelect', '-- Select Customer Type --',  '#ProductFormModal');

        // Product Type
        $('#ProductType').select2({ width: '100%', minimumResultsForSearch: Infinity, dropdownParent: $modal });

        // Category with inline "+ Create"
        _initPFCategorySelect2();

        // Quill
        QuillEditor('.ql-toolbar', 'Enter product description...');

        // Dropzone — initialise on first modal show if not already
        $modal.one('shown.bs.modal', function () {
            if (typeof Dropzone !== 'undefined' && !Dropzone.instances.some(function(d){ return d.element.id === 'DropzoneOneBasic'; })) {
                commonDropzoneOne('DropzoneOneBasic');
            }
        });
    }

    // ── Category Select2 with inline "+ Create" ───────────────────────────
    function _initPFCategorySelect2() {
        $('#Category').select2({
            placeholder : '-- Select Category --',
            allowClear  : true,
            width       : '100%',
            dropdownParent: $('#ProductFormModal'),
            tags        : true,
            createTag   : function (params) {
                var term = $.trim(params.term);
                if (!term) return null;
                var matched = false;
                $('#Category option').each(function () {
                    if ($(this).text().toLowerCase() === term.toLowerCase()) { matched = true; return false; }
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
                if (typeof CategoryForm !== 'undefined') {
                    CategoryForm.open({
                        prefillName   : name,
                        onSaveSuccess : function (resp) {
                            if ($('#Category').find('option[value="' + resp.InsertId + '"]').length === 0) {
                                $('#Category').append(new Option(resp.CategoryName, resp.InsertId, true, true)).trigger('change');
                            } else {
                                $('#Category').val(resp.InsertId).trigger('change');
                            }
                            // Also update category options cache so products-page filter stays in sync
                            if (typeof updateCategoryOptions === 'function') {
                                updateCategoryOptions({ InsertId: resp.InsertId, CategoryName: resp.CategoryName }, 'insert');
                            }
                            if (!$('#ProductFormModal').hasClass('show')) {
                                $('#ProductFormModal').modal('show');
                            }
                        }
                    });
                }
            }
        });
    }

    // ── Reset form to blank / defaults ────────────────────────────────────
    function _resetProductModal() {
        _pfImgData = '';
        $('#AddEditItemForm')[0].reset();
        $('#ItemModalTitle').text('Create Item');
        $('.AddEditProductBtn').text('Save');
        $('#HProductUID').val(0);

        var defType = (typeof _pfDefProductType  !== 'undefined') ? _pfDefProductType  : 'Product';
        var defTax  = (typeof _pfDefProdTaxUID   !== 'undefined' && _pfDefProdTaxUID)   ? _pfDefProdTaxUID   : 1;
        var defDisc = (typeof _pfDefDiscTypeUID  !== 'undefined' && _pfDefDiscTypeUID)  ? _pfDefDiscTypeUID  : 1;
        var defTaxD = (typeof _pfDefTaxDetailUID !== 'undefined' && _pfDefTaxDetailUID) ? _pfDefTaxDetailUID : null;

        $('#ProductType').val(defType).trigger('change');
        $('#SellingTaxOption,#PurchaseTaxOption').val(defTax).trigger('change');
        $('#DiscountOption').val(defDisc).trigger('change');
        $('#TaxPercentage').val(defTaxD).trigger('change');
        $('#PrimaryUnit,#Category,#StorageUID,#BrandUID,#PSizeUID').val(null).trigger('change');
        $('#IsSizeApplicable,#IsBrandApplicable,#IsSerialTracked,#NotForSale,#IsRentable').prop('checked', false).trigger('change');
        $('#SizeDiv,#rentalConfigSection').addClass('d-none');

        if (typeof myOneDropzone !== 'undefined') { myOneDropzone.removeAllFiles(true); }
        if (typeof quill        !== 'undefined') { quill.setContents([]); }
        if (typeof loadCustomerPricingRows === 'function') { loadCustomerPricingRows([]); }
        $('#CustomerTypeSelect').val('').trigger('change');
        $('#CustomerTypePrice').val('');
        $('#CustomerPricingData').val('[]');
        $('.addEditFormAlert').addClass('d-none');
    }

    // ── Load for edit/clone ───────────────────────────────────────────────
    function _loadForEdit(uid, isClone) {
        $.ajax({
            url    : '/products/retrieveProductDetails',
            method : 'POST',
            data   : { ItemUID: uid, [CsrfName]: CsrfToken },
            success: function (response) {
                if (response.Error) {
                    Swal.fire({ icon: 'error', title: 'Oops...', text: response.Message });
                    return;
                }
                CategoryAppend.populateSelect('#Category', function () {
                    _resetProductModal();
                    _pfFillForm(response, isClone);
                    $('#ProductFormModal').modal('show');
                });
            }
        });
    }

    // ── Populate form from retrieved data ─────────────────────────────────
    function _pfFillForm(response, isClone) {
        var d = response.Data;
        if (isClone) {
            $('#ItemModalTitle').text('Create Item');
            $('.AddEditProductBtn').text('Save');
            $('#HProductUID').val(0);
        } else {
            $('#ItemModalTitle').text('Edit Item');
            $('.AddEditProductBtn').text('Update');
            $('#HProductUID').val(d.ProductUID);
        }

        $('#ItemName').val(d.ItemName);
        $('#ProductType').val(d.ProductType).trigger('change');
        $('#SellingPrice').val(smartDecimal(d.SellingPrice));
        $('#MRP').val(smartDecimal(d.MRP));
        $('#SellingTaxOption').val(d.SellingProductTaxUID).trigger('change');
        $('#TaxPercentage').val(d.TaxDetailsUID).trigger('change');
        $('#PrimaryUnit').val(d.PrimaryUnitUID).trigger('change');
        $('#Category').val(d.CategoryUID).trigger('change');
        $('#PurchasePrice').val(smartDecimal(d.PurchasePrice));
        $('#PurchaseTaxOption').val(d.PurchasePriceProductTaxUID).trigger('change');

        if (typeof _pfEnableStorage !== 'undefined' && _pfEnableStorage == 1) {
            $('#StorageUID').val(d.StorageUID).trigger('change');
        }
        $('#HSNCode').val(d.HSNSACCode);
        $('#PartNumber').val(d.PartNumber);
        $('#SKU').val(d.SKU);

        if (d.IsSizeApplicable == 1) {
            $('#IsSizeApplicable').prop('checked', true).trigger('change');
            $('#SizeDiv').removeClass('d-none');
            $('#PSizeUID').val(d.SizeUID).trigger('change').prop('required', true);
        }
        if (d.IsBrandApplicable == 1) { $('#IsBrandApplicable').prop('checked', true); }
        if (d.IsSerialTracked   == 1) { $('#IsSerialTracked').prop('checked', true); }

        if (typeof loadCustomerPricingRows === 'function') {
            loadCustomerPricingRows(response.CustomerPricing || []);
        }
        if (hasValue(d.Image)) {
            var imgUrl = CDN_URL + d.Image;
            commonSetDropzoneImageOne(imgUrl);
            _pfImgData = imgUrl;
        }
        if (d.Description) { appendToQuill(d.Description, true); }

        $('#OpeningQuantity').val(smartDecimal(d.OpeningQuantity));
        $('#OpeningPurchasePrice').val(smartDecimal(d.OpeningPurchasePrice));
        $('#OpeningStockValue').val(smartDecimal(d.OpeningStockValue));
        $('#Discount').val(smartDecimal(d.Discount));
        $('#DiscountOption').val(d.DiscountTypeUID).trigger('change');
        $('#LowStockAlert').val(smartDecimal(d.LowStockAlertAt));

        if (d.NotForSale === 'Yes') { $('#NotForSale').prop('checked', true); }
        if (d.IsRentable == 1) {
            $('#IsRentable').prop('checked', true).trigger('change');
            if (response.RentalConfig) {
                var rc = response.RentalConfig;
                $('#rc_SecurityDeposit').val(smartDecimal(rc.SecurityDeposit));
                $('#rc_HourlyRate').val(smartDecimal(rc.HourlyRate));
                $('#rc_HalfDayRate').val(smartDecimal(rc.HalfDayRate));
                $('#rc_FullDayRate').val(smartDecimal(rc.FullDayRate));
                $('#rc_FixedPackageRate').val(smartDecimal(rc.FixedPackageRate));
                $('#rc_ExtraHourRate').val(smartDecimal(rc.ExtraHourRate));
                $('#rc_LateReturnCharge').val(smartDecimal(rc.LateReturnChargePerHour));
                $('#rc_DamagePenaltyRate').val(smartDecimal(rc.DamagePenaltyRate));
                $('#rc_MinRentalHours').val(rc.MinRentalHours || 1);
            }
        }
    }

    // ── Modal events ──────────────────────────────────────────────────────
    $(document).on('shown.bs.modal', '#ProductFormModal', function () {
        $('#AddEditItemForm #ItemName').trigger('focus');
        $('.addEditFormAlert').addClass('d-none');
    });

    $(document).on('hide.bs.modal', '#ProductFormModal', function () {
        _resetProductModal();
    });

    // ── Field toggle handlers ─────────────────────────────────────────────
    $(document).on('change', '#ProductType', function () {
        var val = $(this).val();
        $('#AddEditItemForm').find('#OpeningQuantity,#OpeningPurchasePrice,#OpeningStockValue').val(0);
        if (val === 'Product') {
            $('.OpeningStockDiv').removeClass('d-none');
        } else {
            $('.OpeningStockDiv').addClass('d-none');
        }
    });

    $(document).on('change', '#IsRentable', function () {
        var $m = $(this).closest('.modal');
        if ($(this).is(':checked')) { $m.find('#rentalConfigSection').removeClass('d-none'); }
        else                        { $m.find('#rentalConfigSection').addClass('d-none'); }
    });

    $(document).on('change', '#IsSizeApplicable', function () {
        var $m = $(this).closest('.modal');
        $m.find('#SizeDiv').addClass('d-none');
        $m.find('#PSizeUID').removeAttr('required').val('').trigger('change');
        if ($(this).is(':checked')) {
            $m.find('#SizeDiv').removeClass('d-none').attr('required', true);
            $m.find('#PSizeUID').val('').trigger('change');
        }
    });

    $(document).on('change', '#DiscountOption', function () {
        var $m  = $(this).closest('.modal');
        $m.find('#discTextAmountHelp,#discTextPercentHelp').addClass('d-none');
        var val = $(this).val();
        if (val == 1) {
            $m.find('#Discount').attr('placeholder', 'Enter Discount Percentage');
            $m.find('#discTextPercentHelp').removeClass('d-none');
            if ($m.find('#Discount').val() > 100) { $m.find('#Discount').val(0); }
        } else if (val == 2) {
            $m.find('#discTextAmountHelp').removeClass('d-none');
            $m.find('#Discount').attr('placeholder', 'Enter Discount Amount');
        }
    });

    $(document).on('change', '#SellingTaxOption', function () {
        var $m  = $(this).closest('.modal');
        var txt = $(this).find('option:selected').text().toLowerCase();
        $m.find('#SellingPriceTaxHelp,#SellingPriceWTaxHelp').addClass('d-none');
        if (txt.includes('with tax'))     { $m.find('#SellingPriceTaxHelp').removeClass('d-none'); }
        else if (txt.includes('without')) { $m.find('#SellingPriceWTaxHelp').removeClass('d-none'); }
    });

    // ── Customer Type Pricing ─────────────────────────────────────────────
    $(document).on('click', '#AddCustomerPriceBtn', function (e) {
        e.preventDefault();
        var $m     = $(this).closest('.modal');
        var $ctSel = $m.find('#CustomerTypeSelect');
        var ctUID  = $ctSel.val();
        var ctName = $ctSel.find('option:selected').text().trim();
        var price  = $m.find('#CustomerTypePrice').val().trim();
        if (!ctUID)                          { Swal.fire({ icon: 'error', title: 'Oops...', text: 'Please select a customer type.' }); return; }
        if (!price || parseFloat(price) < 0) { Swal.fire({ icon: 'error', title: 'Oops...', text: 'Please enter a valid selling price.' }); return; }
        var exists = false;
        $m.find('#CustomerPricingBody tr[data-ctuid]').each(function () { if ($(this).data('ctuid') == ctUID) { exists = true; return false; } });
        if (exists) { Swal.fire({ icon: 'error', title: 'Oops...', text: 'This customer type rate is already added.' }); return; }
        addCustomerPriceRow(0, ctUID, ctName, price);
        $ctSel.val('').trigger('change');
        $m.find('#CustomerTypePrice').val('');
        updateCustomerPricingData();
    });

    $(document).on('click', '.RemoveCustomerPrice', function (e) {
        e.preventDefault();
        $(this).closest('tr').remove();
        renumberCustomerPriceRows();
        updateCustomerPricingData();
        if ($('#CustomerPricingBody tr[data-ctuid]').length === 0) { $('#CustomerPricingEmptyRow').show(); }
    });

    $(document).on('change', '.CustomerPriceInput', function () {
        var val = parseFloat($(this).val());
        if (isNaN(val) || val < 0) { $(this).val(''); }
        updateCustomerPricingData();
    });

    // ── Form submit ───────────────────────────────────────────────────────
    $(document).on('submit', '#AddEditItemForm', function (e) {
        e.preventDefault();

        var formData = new FormData(this);
        var productUID = $('#HProductUID').val();

        if (typeof myOneDropzone !== 'undefined' && myOneDropzone.files.length > 0) {
            var file = myOneDropzone.files[0];
            if (!file.isStored) { formData.append('UploadImage', file); }
        }
        if (productUID && hasValue(_pfImgData) && (typeof myOneDropzone === 'undefined' || myOneDropzone.files.length === 0)) {
            formData.append('ImageRemoved', 1);
        }
        if (typeof quill !== 'undefined') {
            var desc = quill.getText().trim();
            if (desc) { formData.append('Description', $('#Description .ql-editor').html()); }
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

        if (_onSaveSuccess && _onSaveSuccess._needsList) {
            formData.append('returnList', 1);
            if (typeof PageNo    !== 'undefined') { formData.append('PageNo',   PageNo); }
            if (typeof RowLimit  !== 'undefined') { formData.append('RowLimit', RowLimit); }
            if (typeof ItemModuleId !== 'undefined') { formData.append('ModuleId', ItemModuleId); }
            if (typeof Filter !== 'undefined' && Object.keys(Filter).length > 0) {
                formData.append('Filter', JSON.stringify(Filter));
            }
        }

        updateCustomerPricingData();

        var $btn = $('.AddEditProductBtn');
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Saving...');

        var url = (productUID == 0) ? '/products/addProductData' : '/products/editProductData';

        $.ajax({
            url         : url,
            method      : 'POST',
            data        : formData,
            processData : false,
            contentType : false,
            success: function (response) {
                $btn.prop('disabled', false).html('<i class="bx bx-check me-1"></i>Save');
                if (response.Error) {
                    $('.addEditFormAlert').removeClass('d-none');
                    Swal.fire({ icon: 'error', title: 'Oops...', text: response.Message });
                    return;
                }
                showToastNotification(response.Message, 'success');
                $('#ProductFormModal').modal('hide');
                if (typeof _onSaveSuccess === 'function') {
                    _onSaveSuccess(response);
                }
            },
            error: function () {
                $btn.prop('disabled', false).html('<i class="bx bx-check me-1"></i>Save');
                Swal.fire({ icon: 'error', title: 'Oops...', text: 'Failed to save product.' });
            }
        });
    });

})(window, jQuery);

// ── Customer Pricing helpers (global, shared with combinemodules) ─────────────
function addCustomerPriceRow(rateUID, ctUID, ctName, price) {
    $('#CustomerPricingEmptyRow').hide();
    var count = $('#CustomerPricingBody tr[data-ctuid]').length + 1;
    var sym   = (typeof currencySymbol !== 'undefined') ? currencySymbol : '';
    var row = '<tr data-ctuid="' + ctUID + '" data-rateuid="' + rateUID + '">' +
              '<td>' + count + '</td>' +
              '<td>' + ctName + '</td>' +
              '<td><div class="input-group input-group-merge"><span class="input-group-text">' + sym + '</span>' +
              '<input type="text" class="form-control form-control-sm CustomerPriceInput" min="0" placeholder="Enter Price" ' +
              'onkeydown="return handleDotOnly(event)" ' +
              'oninput="this.value=this.value.slice(0,this.maxLength); validatePriceInput(this, 12, 2)" maxLength="12" ' +
              'pattern="^\\d{1,12}(\\.\\d{0,2})?$" ' +
              'onpaste="handlePricePaste(event, 12, 2)" ondrop="handlePriceDrop(event, 12, 2)" ' +
              'value="' + smartDecimal(price) + '" style="width:50px !important;" /></div></td>' +
              '<td><button type="button" class="btn btn-sm btn-danger RemoveCustomerPrice"><i class="bx bx-trash"></i></button></td>' +
              '</tr>';
    $('#CustomerPricingBody').append(row);
}

function renumberCustomerPriceRows() {
    $('#CustomerPricingBody tr[data-ctuid]').each(function (i) { $(this).find('td:first').text(i + 1); });
}

function updateCustomerPricingData() {
    var rates = [];
    $('#CustomerPricingBody tr[data-ctuid]').each(function () {
        rates.push({
            RateUID        : $(this).data('rateuid'),
            CustomerTypeUID: $(this).data('ctuid'),
            SellingPrice   : $(this).find('.CustomerPriceInput').val()
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
