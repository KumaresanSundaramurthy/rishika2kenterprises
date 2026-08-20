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
    var _pfOrigHsnCode = '';
    var _isDirty       = false;
    var _isCreateMode  = false;
    var _isPopulating  = false;

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

            DropdownCache.openForProductForm({

                // Case A: all 6 keys were in Upstash — populate first, then show modal.
                // User sees the form already fully loaded on open.
                onReady: function (data) {
                    _isPopulating = true;
                    DropdownCache.populateProductModal(data);
                    _pfApplyDefaults();
                    _isPopulating = false;
                    $('#ProductFormModal').modal('show');
                    _isCreateMode = true;
                    _isDirty      = false;
                    setTimeout(function () { $('#ItemName').focus(); }, 300);
                },

                // Case B: some/none in Upstash — show modal now, populate whatever was found.
                onPartial: function (foundData) {
                    if (Object.keys(foundData).length > 0) {
                        _isPopulating = true;
                        DropdownCache.populateProductModal(foundData);
                        _pfApplyDefaults();
                        _isPopulating = false;
                    }
                    $('#ProductFormModal').modal('show');
                    _isCreateMode = true;
                    _isDirty      = false;
                    setTimeout(function () { $('#ItemName').focus(); }, 300);
                },

                // Case B continued: missing fields arrived from server — fill the blanks.
                // populateProductModal's "no options yet" guards prevent double-populating
                // the fields that were already filled in onPartial.
                onMissingReady: function (allData) {
                    _isPopulating = true;
                    DropdownCache.populateProductModal(allData);
                    _pfApplyDefaults();
                    _isPopulating = false;
                }
            });
        }
    }

    // ── Re-apply default selections after dropdowns are populated ────────
    // _resetProductModal() fires .trigger('change') before options exist, so
    // label helpers (Inclusive/Exclusive, discount label) don't render then.
    // Call this after populateProductModal() so the triggers have options to act on.
    function _pfApplyDefaults() {
        var defTax  = (typeof _pfDefProdTaxUID   !== 'undefined' && _pfDefProdTaxUID)   ? _pfDefProdTaxUID   : 1;
        var defDisc = (typeof _pfDefDiscTypeUID  !== 'undefined' && _pfDefDiscTypeUID)  ? _pfDefDiscTypeUID  : 1;
        var defTaxD = (typeof _pfDefTaxDetailUID !== 'undefined' && _pfDefTaxDetailUID) ? _pfDefTaxDetailUID : null;
        $('#SellingTaxOption,#PurchaseTaxOption').val(defTax).trigger('change');
        $('#DiscountOption').val(defDisc).trigger('change');
        if (defTaxD) { $('#TaxPercentage').val(defTaxD).trigger('change'); }
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

        // Unit
        loadSelect2Field('#PrimaryUnit', '-- Select Primary Unit --', '#ProductFormModal');

        // Category with inline "+ Create"
        _initPFCategorySelect2();

        // Quill
        QuillEditor('.ql-toolbar', 'Enter product description...');

        // Bind attachment zone listeners once (immediately — zone HTML is already in the DOM)
        if (typeof _attachBindListeners === 'function') _attachBindListeners('Product');

        $modal.one('shown.bs.modal', function () {
            // Focus first field — listeners are already bound above
            setTimeout(function () { $('#ItemName').focus(); }, 100);
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
        _isCreateMode = false;
        _isDirty      = false;
        _pfImgData     = '';
        _pfOrigHsnCode = '';
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
        $('#PrimaryUnit,#Category,#StorageUID,#BrandUID').val(null).trigger('change');
        _variantRows = [];
        $('#VariantSizeSelect,#VariantBrandSelect').val(null).trigger('change.select2');
        $('#variantSection,#variantSizeRow,#variantBrandRow,#variantTableWrap').addClass('d-none');
        $('#variantEmptyHint').removeClass('d-none');
        $('#VariantData').val('[]');
        $('#IsSizeApplicable,#IsBrandApplicable,#IsSerialTracked,#NotForSale,#IsRentable').prop('checked', false).trigger('change');
        $('#rentalConfigSection').addClass('d-none');

        if (typeof myOneDropzone !== 'undefined') { myOneDropzone.removeAllFiles(true); }
        if (typeof quill        !== 'undefined') { quill.setContents([]); }
        // Reset state only — listeners are already bound from _pfInit, never re-bind
        if (typeof _attachResetState === 'function') _attachResetState('Product');
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
                DropdownCache.ready().then(function (data) {
                    if (data) DropdownCache.populateProductModal(data);
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
        _pfOrigHsnCode = d.HSNSACCode || '';
        $('#PartNumber').val(d.PartNumber);
        $('#SKU').val(d.SKU);

        var _isSizeApp  = d.IsSizeApplicable  == 1;
        var _isBrandApp = d.IsBrandApplicable == 1;
        if (_isSizeApp)  { $('#IsSizeApplicable').prop('checked', true); }
        if (_isBrandApp) { $('#IsBrandApplicable').prop('checked', true); }
        if (_isSizeApp || _isBrandApp) {
            _updateVariantSectionVisibility();
            _applyVariants(response.Variants || [], _isSizeApp, _isBrandApp);
        }
        if (d.IsSerialTracked   == 1) { $('#IsSerialTracked').prop('checked', true); }

        // Reset state, load existing attachments from response (no AJAX — already in response)
        if (!isClone) {
            if (typeof _attachResetState === 'function') _attachResetState('Product');
            if (response.Attachments && response.Attachments.length && _attachState['Product']) {
                _attachState['Product'].existing = response.Attachments;
                if (typeof _attachRender === 'function') _attachRender('Product');
            }
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
    });

    $(document).on('hidden.bs.modal', '#ProductFormModal', function () {
        _resetProductModal();
    });

    // ── Field toggle handlers ─────────────────────────────────────────────
    $(document).on('change', '#ProductType', function () {
        var val = $(this).val();
        $('#AddEditItemForm').find('#OpeningQuantity,#OpeningPurchasePrice,#OpeningStockValue').val(0);
        if (val === 'Product') {
            $('.OpeningStockDiv').removeClass('d-none');
            _updateVariantSectionVisibility();
        } else {
            $('.OpeningStockDiv').addClass('d-none');
            $('#variantSection').addClass('d-none');
        }
    });

    $(document).on('change', '#IsRentable', function () {
        var $m = $(this).closest('.modal');
        if ($(this).is(':checked')) { $m.find('#rentalConfigSection').removeClass('d-none'); }
        else                        { $m.find('#rentalConfigSection').addClass('d-none'); }
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

    /**
     * @param {number} selling
     * @param {string} sellingTaxText  - selected option label, e.g. "With Tax"
     * @param {number} purchase
     * @param {string} purchaseTaxText
     * @param {number} taxPct          - tax rate 0–100
     * @returns {boolean} true if selling is effectively below purchase after normalising to inclusive
     */
    function _isBelowPurchasePrice(selling, sellingTaxText, purchase, purchaseTaxText, taxPct) {
        var factor   = 1 + (taxPct / 100);
        var sellInc  = sellingTaxText.toLowerCase().indexOf('with tax')  !== -1 ? selling  : selling  * factor;
        var purchInc = purchaseTaxText.toLowerCase().indexOf('with tax') !== -1 ? purchase : purchase * factor;
        return sellInc < purchInc;
    }

    // ── Form submit ───────────────────────────────────────────────────────
    $(document).on('submit', '#AddEditItemForm', function (e) {
        e.preventDefault();

        var formData   = new FormData(this);
        var productUID = parseInt($('#HProductUID').val()) || 0;

        // Append new attachment files directly into this request — no separate upload round trip
        if (typeof _attachState !== 'undefined' && _attachState['Product']) {
            (_attachState['Product'].newFiles || []).forEach(function(f) {
                formData.append('ProdAttachFiles[]', f, f.name);
            });
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
            if (typeof PageNo       !== 'undefined') { formData.append('PageNo',   PageNo); }
            if (typeof RowLimit     !== 'undefined') { formData.append('RowLimit', RowLimit); }
            if (typeof ItemModuleId !== 'undefined') { formData.append('ModuleId', ItemModuleId); }
            if (typeof Filter !== 'undefined' && Object.keys(Filter).length > 0) {
                formData.append('Filter', JSON.stringify(Filter));
            }
        }

        /**
         * @param {boolean} skipConfirm - when true, skips the "Confirm Update" Swal on edit (merged into price warning)
         * @returns {void}
         */
        function _doSaveFlow(skipConfirm) {
            if (productUID > 0) {
                if (skipConfirm) { _doProductSave(formData, productUID); return; }
                var currentHsn = $.trim($('#HSNCode').val());
                var hsnChanged = ($.trim(_pfOrigHsnCode) !== currentHsn);
                Swal.fire({
                    title             : t('swal_confirm_update', 'Update Item'),
                    html              : '<div class="alert alert-info d-flex align-items-start gap-2 text-start mb-0" style="font-size:.875rem;">'
                                      + '<i class="bx bx-info-circle fs-5 mt-1 flex-shrink-0"></i>'
                                      + '<span>Note: Your changes will apply only to new documents and will not affect older documents.</span>'
                                      + '</div>',
                    showCancelButton  : true,
                    confirmButtonText : t('btn_save_changes', 'Save Changes'),
                    cancelButtonText  : t('btn_cancel', 'Cancel'),
                    position          : 'top',
                    buttonsStyling    : false,
                    customClass       : {
                        container     : 'swal-top-pad',
                        confirmButton : 'btn btn-primary me-2',
                        cancelButton  : 'btn btn-outline-secondary',
                    }
                }).then(function (result) {
                    if (result.isConfirmed) { _doProductSave(formData, productUID); }
                });
            } else {
                _doProductSave(formData, productUID);
            }
        }

        var _sp = parseFloat($('#SellingPrice').val())  || 0;
        var _pp = parseFloat($('#PurchasePrice').val()) || 0;
        if (_sp > 0 && _pp > 0 && _isBelowPurchasePrice(
                _sp, $('#SellingTaxOption option:selected').text(),
                _pp, $('#PurchaseTaxOption option:selected').text(),
                parseFloat($('#TaxPercentage option:selected').data('left')) || 0)) {
            var _swalTitle, _swalText, _swalOk, _swalCancel;
            if (productUID > 0) {
                var _chsn    = $.trim($('#HSNCode').val());
                var _hsnNote = ($.trim(_pfOrigHsnCode) !== _chsn)
                    ? ' Also, the HSN/SAC code change will be reflected in all future transactions.'
                    : '';
                _swalTitle  = t('swal_confirm_update',     'Confirm Update');
                _swalText   = t('swal_below_purchase_msg', 'The selling price is lower than the purchase price. Do you want to proceed anyway?') + _hsnNote;
                _swalOk     = t('btn_yes_save', 'Yes, Save');
                _swalCancel = t('btn_cancel',   'Cancel');
            } else {
                _swalTitle  = t('swal_below_purchase_title', 'Selling Price Alert');
                _swalText   = t('swal_below_purchase_msg',   'The selling price is lower than the purchase price. Do you want to proceed anyway?');
                _swalOk     = t('btn_proceed', 'Proceed');
                _swalCancel = t('btn_back',    'Back');
            }
            Swal.fire({
                icon              : 'warning',
                title             : _swalTitle,
                text              : _swalText,
                showCancelButton  : true,
                confirmButtonText : _swalOk,
                cancelButtonText  : _swalCancel
            }).then(function (res) {
                if (res.isConfirmed) { _doSaveFlow(productUID > 0); }
            });
            return;
        }
        _doSaveFlow(false);
    });

    function _doProductSave(formData, productUID) {
        var $btn   = $('.AddEditProductBtn');
        var isEdit = productUID > 0;
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Saving...');

        var url = isEdit ? '/products/updateProductData' : '/products/addProductData';

        if (isEdit) { window._gpoUsingManualProgress = true; }
        $.ajax({
            url         : url,
            method      : 'POST',
            data        : formData,
            processData : false,
            contentType : false,
            success: function (response) {
                $btn.prop('disabled', false).html('<i class="bx bx-check me-1"></i>Save');
                if (response.Error) {
                    showToastNotification(response.Message || 'Failed to save product.', 'error');
                    return;
                }
                _isDirty      = false;
                _isCreateMode = false;
                showToastNotification(response.Message, 'success');
                $('#ProductFormModal').modal('hide');
                if (typeof _onSaveSuccess === 'function') _onSaveSuccess(response);
            },
            error: function () {
                $btn.prop('disabled', false).html('<i class="bx bx-check me-1"></i>Save');
                Swal.fire({ icon: 'error', title: t('swal_oops', 'Oops...'), text: t('swal_save_product_failed', 'Failed to save product.') });
            },
            complete: function () {
                if (isEdit) {
                    window._gpoUsingManualProgress = false;
                    $('#globalProcOverlay').removeClass('gpo-wait-only');
                }
            }
        });
        if (isEdit) { $('#globalProcOverlay').addClass('gpo-wait-only'); }
    }

    // ── Dirty-tracking: flag changes while in create mode ────────────────────
    $(document).on('input change', '#AddEditItemForm input, #AddEditItemForm textarea, #AddEditItemForm select', function () {
        if (_isCreateMode && !_isPopulating) _isDirty = true;
    });

    // ── Unsaved-changes guard ─────────────────────────────────────────────────
    $(document).on('hide.bs.modal', '#ProductFormModal', function (e) {
        if (!_isDirty || !_isCreateMode) return;
        e.preventDefault();
        Swal.fire({
            title             : t('swal_unsaved_title',   'Unsaved Changes'),
            text              : t('swal_unsaved_msg',     'Your changes will be lost if you close now.'),
            icon              : 'warning',
            showCancelButton  : true,
            confirmButtonText : t('swal_unsaved_confirm', 'Close Anyway'),
            cancelButtonText  : t('swal_unsaved_cancel',  'Stay'),
            confirmButtonColor: '#d33',
            cancelButtonColor : '#3085d6',
        }).then(function (result) {
            if (result.isConfirmed) {
                _isDirty      = false;
                _isCreateMode = false;
                $('#ProductFormModal').modal('hide');
            }
        });
    });

    // ── Variant Builder ──────────────────────────────────────────────────────────

    var _variantRows = [];

    /**
     * Build tax <option> HTML for a variant row's price tax selector.
     * Reads options from the already-populated #SellingTaxOption in the form.
     * @param {number|string} selectedUID
     * @returns {string}
     */
    function _buildTaxOptions(selectedUID) {
        var html = '';
        $('#SellingTaxOption option').each(function () {
            var val = $(this).val();
            if (!val) return;
            var text  = $(this).text().trim();
            var short = text.toLowerCase().indexOf('without') !== -1 ? 'W/o Tax'
                      : text.toLowerCase().indexOf('with')    !== -1 ? 'W/ Tax'
                      : text;
            var sel = (String(val) === String(selectedUID)) ? ' selected' : '';
            html += '<option value="' + val + '"' + sel + '>' + short + '</option>';
        });
        return html;
    }

    /**
     * @param {number} sizeUID
     * @param {number} brandUID
     * @returns {Object|undefined}
     */
    function _findVariantRow(sizeUID, brandUID) {
        return _variantRows.find(function (r) {
            return r.SizeUID === sizeUID && r.BrandUID === brandUID;
        });
    }

    /**
     * @returns {Array<{SizeUID:number, SizeName:string}>}
     */
    function _getSelectedSizes() {
        var data = $('#VariantSizeSelect').select2('data') || [];
        return data.map(function (o) {
            return { SizeUID: parseInt(o.id, 10), SizeName: o.text };
        }).filter(function (o) { return o.SizeUID > 0; });
    }

    /**
     * @returns {Array<{BrandUID:number, BrandName:string}>}
     */
    function _getSelectedBrands() {
        var data = $('#VariantBrandSelect').select2('data') || [];
        return data.map(function (o) {
            return { BrandUID: parseInt(o.id, 10), BrandName: o.text };
        }).filter(function (o) { return o.BrandUID > 0; });
    }

    /**
     * Build variant matrix from selected sizes × brands, preserving existing input values.
     * @returns {void}
     */
    function _buildVariantMatrix() {
        var isSizeApp  = $('#IsSizeApplicable').is(':checked');
        var isBrandApp = $('#IsBrandApplicable').is(':checked');
        var sizes      = isSizeApp  ? _getSelectedSizes()  : [{ SizeUID: 0, SizeName: '' }];
        var brands     = isBrandApp ? _getSelectedBrands() : [{ BrandUID: 0, BrandName: '' }];

        // An active dimension with no selections yet means the user hasn't picked items.
        // Show the hint without wiping existing rows — matrix rebuilds when they select.
        if (!sizes.length || !brands.length) {
            $('#variantTableWrap').addClass('d-none');
            $('#variantEmptyHint').removeClass('d-none');
            return;
        }

        var newRows   = [];
        var defTaxUID = parseInt($('#SellingTaxOption').val()) || 0;

        sizes.forEach(function (s) {
            brands.forEach(function (b) {
                var existing = _findVariantRow(s.SizeUID, b.BrandUID);
                newRows.push({
                    SizeUID:        s.SizeUID,
                    SizeName:       s.SizeName,
                    BrandUID:       b.BrandUID,
                    BrandName:      b.BrandName,
                    PartNumber:     existing ? existing.PartNumber     : '',
                    PurchasePrice:  existing ? existing.PurchasePrice  : 0,
                    PurchaseTaxUID: existing ? existing.PurchaseTaxUID : defTaxUID,
                    SellingPrice:   existing ? existing.SellingPrice   : 0,
                    SellingTaxUID:  existing ? existing.SellingTaxUID  : defTaxUID,
                    OpeningQty:     existing ? existing.OpeningQty     : 0
                });
            });
        });

        _variantRows = newRows;
        _renderVariantTable(isSizeApp, isBrandApp);
        _updateVariantHidden();
    }

    /**
     * @param {boolean} isSizeApp
     * @param {boolean} isBrandApp
     * @returns {void}
     */
    function _renderVariantTable(isSizeApp, isBrandApp) {
        if (!_variantRows.length) {
            $('#variantTableWrap').addClass('d-none');
            $('#variantEmptyHint').removeClass('d-none');
            return;
        }
        $('#variantTableWrap').removeClass('d-none');
        $('#variantEmptyHint').addClass('d-none');
        $('.var-col-size').toggleClass('d-none', !isSizeApp);
        $('.var-col-brand').toggleClass('d-none', !isBrandApp);

        var html     = '';
        var defTaxUID = parseInt($('#SellingTaxOption').val()) || 0;
        _variantRows.forEach(function (row, idx) {
            var esc = function (s) { return $('<span>').text(s || '').html(); };
            var sc  = isSizeApp  ? '<td class="var-col-size">'  + esc(row.SizeName)  + '</td>'
                                 : '<td class="var-col-size d-none"></td>';
            var bc  = isBrandApp ? '<td class="var-col-brand">' + esc(row.BrandName) + '</td>'
                                 : '<td class="var-col-brand d-none"></td>';
            html += '<tr class="var-row" data-idx="' + idx + '">' + sc + bc +
                '<td><input type="text" class="form-control form-control-sm var-part-no" placeholder="Part No" value="' +
                    esc(row.PartNumber) + '" maxlength="100"></td>' +
                '<td><div class="input-group input-group-sm">' +
                    '<input type="number" class="form-control form-control-sm var-purchase-price text-end" placeholder="0.00" min="0" step="any" value="' +
                        (row.PurchasePrice || '') + '">' +
                    '<select class="form-select form-select-sm var-tax-sel var-purchase-tax">' +
                        _buildTaxOptions(row.PurchaseTaxUID || defTaxUID) +
                    '</select>' +
                '</div></td>' +
                '<td><div class="input-group input-group-sm">' +
                    '<input type="number" class="form-control form-control-sm var-selling-price text-end" placeholder="0.00" min="0" step="any" value="' +
                        (row.SellingPrice || '') + '">' +
                    '<select class="form-select form-select-sm var-tax-sel var-selling-tax">' +
                        _buildTaxOptions(row.SellingTaxUID || defTaxUID) +
                    '</select>' +
                '</div></td>' +
                '<td><input type="number" class="form-control form-control-sm var-opening-qty text-end" placeholder="0" min="0" step="1" value="' +
                    (row.OpeningQty || 0) + '"></td>' +
                '</tr>';
        });
        $('#variantTableBody').html(html);
    }

    /**
     * Sync _variantRows from DOM inputs, update hidden field + total badge.
     * @returns {void}
     */
    function _updateVariantHidden() {
        var total = 0;
        var dec   = (typeof decimalPlaces !== 'undefined') ? decimalPlaces : 2;
        $('#variantTableBody .var-row').each(function () {
            var idx = parseInt($(this).data('idx'), 10);
            if (!_variantRows[idx]) return;
            _variantRows[idx].PartNumber     = $(this).find('.var-part-no').val()                    || '';
            _variantRows[idx].PurchasePrice  = parseFloat($(this).find('.var-purchase-price').val()) || 0;
            _variantRows[idx].PurchaseTaxUID = parseInt($(this).find('.var-purchase-tax').val())     || 0;
            _variantRows[idx].SellingPrice   = parseFloat($(this).find('.var-selling-price').val())  || 0;
            _variantRows[idx].SellingTaxUID  = parseInt($(this).find('.var-selling-tax').val())      || 0;
            _variantRows[idx].OpeningQty     = parseFloat($(this).find('.var-opening-qty').val())    || 0;
            total += _variantRows[idx].OpeningQty;
        });
        $('#VariantData').val(JSON.stringify(_variantRows));
        $('#OpeningQuantity').val(total > 0 ? total : 0);
        var $badge = $('#variantTotalQty');
        $badge.text('Total Qty: ' + total.toFixed(dec));
        if (total > 0) { $badge.removeClass('bg-label-secondary').addClass('bg-label-primary'); }
        else            { $badge.removeClass('bg-label-primary').addClass('bg-label-secondary'); }
    }

    /**
     * Populate #VariantBrandSelect and (re)initialise its select2.
     * Uses DropdownCache data when already in memory (no extra round-trip).
     * Falls back to a direct Upstash HGETALL when the cache is cold.
     * ALWAYS destroys the old select2 and re-inits after options are in the DOM
     * so select2 never shows "No results found" on a stale empty list.
     * @returns {Promise<void>}
     */
    function _loadBrandOptions() {
        var $sel   = $('#VariantBrandSelect');
        var $modal = $('#ProductFormModal');

        /**
         * @param {Array<{BrandUID:number, BrandName:string}>} brands
         * @returns {void}
         */
        function _populate(brands) {
            if ($sel.hasClass('select2-hidden-accessible')) { $sel.select2('destroy'); }
            $sel.empty();
            brands
                .slice()
                .sort(function (a, b) { return (a.BrandName || '').localeCompare(b.BrandName || ''); })
                .forEach(function (b) { $sel.append(new Option(b.BrandName, b.BrandUID, false, false)); });
            $sel.select2({ placeholder: 'Select brands...', allowClear: true, dropdownParent: $modal });
        }

        // Use DropdownCache in-memory brands when available (avoids a second Upstash call)
        var cached = (typeof DropdownCache !== 'undefined') ? DropdownCache.getBrands() : null;
        if (cached) {
            _populate(cached);
            return Promise.resolve();
        }

        var brandKey = UpstashService.orgKey('brands');
        return UpstashService.hgetall(brandKey).then(function (map) {
            _populate(Object.values(map));
        });
    }

    /**
     * Load org sizes into #VariantSizeSelect from Upstash cache (orgKey 'sizes').
     * Falls back to AJAX /products/getSizes when the cache is cold or empty.
     * Optionally pre-selects entries after populating.
     * @param {Array<{SizeUID:number, SizeName:string}>} [preselect]
     * @returns {Promise<void>}
     */
    function _loadSizeOptions(preselect) {
        var $sel   = $('#VariantSizeSelect');
        var $modal = $('#ProductFormModal');

        /**
         * @param {Array<{SizeUID:number, SizeName:string}>} sizes
         * @returns {void}
         */
        function _populate(sizes) {
            if ($sel.hasClass('select2-hidden-accessible')) { $sel.select2('destroy'); }
            $sel.empty();
            sizes
                .slice()
                .sort(function (a, b) { return (a.SizeName || '').localeCompare(b.SizeName || ''); })
                .forEach(function (s) { $sel.append(new Option(s.SizeName, s.SizeUID, false, false)); });
            $sel.select2({ placeholder: 'Select sizes...', allowClear: true, dropdownParent: $modal });
            if (preselect && preselect.length) {
                var ids = preselect.map(function (s) { return String(s.SizeUID); });
                $sel.val(ids).trigger('change.select2');
            }
        }

        var sizeKey = UpstashService.orgKey('sizes');
        return UpstashService.hgetall(sizeKey).then(function (map) {
            var items = map ? Object.values(map) : [];
            if (items.length) {
                _populate(items);
                return;
            }
            // Cache cold — fall back to AJAX
            return $.ajax({
                url    : '/products/getSizes',
                method : 'POST',
                data   : { [CsrfName]: CsrfToken }
            }).then(function (res) {
                if (!res.Error && res.Data) { _populate(res.Data); }
            });
        });
    }

    /**
     * Populate the variant section from an already-fetched variants array (from retrieveProductDetails).
     * @param {Array}   variants   response.Variants from retrieveProductDetails
     * @param {boolean} isSizeApp
     * @param {boolean} isBrandApp
     * @returns {void}
     */
    function _applyVariants(variants, isSizeApp, isBrandApp) {
        var $modal = $('#ProductFormModal');

        // Size select2 — init here (sizes have no separate loader that handles init)
        if (isSizeApp && !$('#VariantSizeSelect').hasClass('select2-hidden-accessible')) {
            $('#VariantSizeSelect').select2({ placeholder: 'Select sizes...', allowClear: true, dropdownParent: $modal });
        }
        // Brand select2 is owned by _loadBrandOptions() — do NOT pre-init here

        if (!variants || !variants.length) {
            if (isBrandApp) {
                _loadBrandOptions().then(function () { _buildVariantMatrix(); });
            } else {
                _buildVariantMatrix();
            }
            return;
        }

        _variantRows = variants.map(function (v) {
            return {
                SizeUID:        parseInt(v.SizeUID,       10) || 0,
                SizeName:       v.SizeName                    || '',
                BrandUID:       parseInt(v.BrandUID,      10) || 0,
                BrandName:      v.BrandName                   || '',
                PartNumber:     v.PartNumber                  || '',
                PurchasePrice:  parseFloat(v.PurchasePrice)   || 0,
                PurchaseTaxUID: parseInt(v.PurchaseTaxUID, 10) || 0,
                SellingPrice:   parseFloat(v.SellingPrice)    || 0,
                SellingTaxUID:  parseInt(v.SellingTaxUID,  10) || 0,
                OpeningQty:     parseFloat(v.OpeningQty)      || 0
            };
        });

        var uniqueSizes = [], seenS = {};
        _variantRows.forEach(function (r) {
            if (r.SizeUID > 0 && !seenS[r.SizeUID]) {
                seenS[r.SizeUID] = true;
                uniqueSizes.push({ SizeUID: r.SizeUID, SizeName: r.SizeName });
            }
        });
        var uniqueBrands = [], seenB = {};
        _variantRows.forEach(function (r) {
            if (r.BrandUID > 0 && !seenB[r.BrandUID]) {
                seenB[r.BrandUID] = true;
                uniqueBrands.push({ BrandUID: r.BrandUID, BrandName: r.BrandName });
            }
        });

        function _finishWithBrands() {
            if (isBrandApp && uniqueBrands.length) {
                _loadBrandOptions().then(function () {
                    var ids = uniqueBrands.map(function (b) { return String(b.BrandUID); });
                    $('#VariantBrandSelect').val(ids).trigger('change.select2');
                    _renderVariantTable(isSizeApp, isBrandApp);
                    _updateVariantHidden();
                });
            } else {
                _renderVariantTable(isSizeApp, isBrandApp);
                _updateVariantHidden();
            }
        }

        if (isSizeApp && uniqueSizes.length) {
            _loadSizeOptions(uniqueSizes).then(_finishWithBrands);
        } else {
            _finishWithBrands();
        }
    }

    /**
     * Show/hide #variantSection and its sub-rows based on current flag state.
     * @returns {void}
     */
    function _updateVariantSectionVisibility() {
        var isSizeApp  = $('#IsSizeApplicable').is(':checked');
        var isBrandApp = $('#IsBrandApplicable').is(':checked');
        var isProduct  = $('#ProductType').val() === 'Product';

        if ((isSizeApp || isBrandApp) && isProduct) {
            $('#openingQtyDiv').addClass('d-none');
            $('#variantSection').removeClass('d-none');
            $('#variantSizeRow').toggleClass('d-none', !isSizeApp);
            $('#variantBrandRow').toggleClass('d-none', !isBrandApp);
        } else {
            $('#openingQtyDiv').removeClass('d-none');
            $('#variantSection').addClass('d-none');
        }
    }

    $(document).on('change', '#IsSizeApplicable', function () {
        if (!$('#VariantSizeSelect').hasClass('select2-hidden-accessible')) {
            $('#VariantSizeSelect').select2({ placeholder: 'Select sizes...', allowClear: true, dropdownParent: $('#ProductFormModal') });
        }
        _updateVariantSectionVisibility();
        if ($(this).is(':checked')) {
            _loadSizeOptions([]).then(function () { _buildVariantMatrix(); });
        } else {
            _buildVariantMatrix();
        }
    });

    $(document).on('change', '#IsBrandApplicable', function () {
        _updateVariantSectionVisibility();
        if ($(this).is(':checked')) {
            // _loadBrandOptions handles select2 destroy + populate + re-init
            _loadBrandOptions().then(function () { _buildVariantMatrix(); });
        } else {
            if ($('#VariantBrandSelect').hasClass('select2-hidden-accessible')) {
                $('#VariantBrandSelect').select2('destroy');
            }
            $('#VariantBrandSelect').empty();
            _buildVariantMatrix();
        }
    });

    $(document).on('change', '#VariantSizeSelect',  function () { _buildVariantMatrix(); });
    $(document).on('change', '#VariantBrandSelect', function () { _buildVariantMatrix(); });

    $(document).on('input change', '#variantTableBody input, #variantTableBody select', function () { _updateVariantHidden(); });

    $(document).on('click', '#AddNewSizeBtn', function () {
        if ($('#sizeModal').length) {
            // Full size management modal is available — open it directly
            $('.addSize').first().trigger('click');
            return;
        }
        // Fallback for pages where #sizeModal is not present
        Swal.fire({
            title            : 'Add New Size',
            input            : 'text',
            inputLabel       : 'Size Name',
            inputPlaceholder : 'e.g. Large, XL, 500ml',
            showCancelButton : true,
            confirmButtonText: 'Add',
            /**
             * @param {string} v
             * @returns {string|undefined}
             */
            inputValidator: function (v) {
                if (!v || !v.trim()) { return 'Please enter a size name.'; }
            }
        }).then(function (res) {
            if (!res.isConfirmed) return;
            var sizeName = res.value.trim();
            $.ajax({
                url    : '/products/addSize',
                method : 'POST',
                data   : { SizeName: sizeName, [CsrfName]: CsrfToken }
            }).done(function (resp) {
                if (resp.Error) { showToastNotification(resp.Message || 'Failed to add size', 'error'); return; }
                $(document).trigger('r2k:sizeAdded', { SizeUID: resp.SizeUID, SizeName: sizeName });
            }).fail(function () {
                showToastNotification('Failed to add size', 'error');
            });
        });
    });

    /**
     * When a new size is created (from #sizeModal or quick-add fallback),
     * append it to #VariantSizeSelect and select it.
     * @param {jQuery.Event} e
     * @param {{SizeUID:number, SizeName:string}} data
     * @returns {void}
     */
    $(document).on('r2k:sizeAdded', function (e, data) {
        var $sel = $('#VariantSizeSelect');
        if (!$sel.length || !data || !data.SizeUID) return;
        if (!$sel.find('option[value="' + data.SizeUID + '"]').length) {
            $sel.append(new Option(data.SizeName, data.SizeUID, false, false));
        }
        var current = $sel.val() || [];
        current.push(String(data.SizeUID));
        $sel.val(current).trigger('change');
    });

    $(document).on('submit', '#AddEditItemForm', function () {
        _updateVariantHidden();
    });

})(window, jQuery);

