/**
 * VendorForm — shared modal for add / edit across all purchase-side pages.
 *
 * Usage:
 *   VendorForm.open('add', null, { prefillName: 'Acme', onSaveSuccess: fn });
 *   prefillName: if all digits → fills VM_MobileNumber, otherwise → fills VM_Name
 *   VendorForm.open('edit', uid, { onSaveSuccess: fn });
 *
 * onSaveSuccess(response) fires after a successful save.
 * Vendors page: refresh list/stats.
 * Transaction pages: _refreshVendorUI(response.Vendor) to update select2 + address strip.
 */
(function (window, $) {
    'use strict';

    // ── Internal state ────────────────────────────────────────────────────────
    var _editUID       = 0;
    var _onSaveSuccess = null;
    var _bodyLoaded    = false;
    var _isDirty       = false;
    var _isCreateMode  = false;

    // ── Public API ────────────────────────────────────────────────────────────
    window.VendorForm = {
        open: openVendorModal,
    };

    // ── On DOM ready: body is pre-rendered server-side — just init plugins ────
    var _vmCCCfg = {
        btn      : '#VM_MobileCCBtn',
        dropdown : '#VM_CCDropdown',
        search   : '#VM_CCSearch',
        list     : '#VM_CCList',
        codeInput: '#VM_CountryCode',
        iso2Input: '#VM_CountryISO2',
    };

    $(function () {
        if ($('#VendorFormModalBody').children().length > 0) {
            _bodyLoaded = true;
            _initModalPlugins();
        }
        PhoneCCDropdown.init(_vmCCCfg);
    });

    // ── Open ──────────────────────────────────────────────────────────────────
    /**
     * @param {string} type
     * @param {number|null} uid
     * @param {Object} opts
     * @returns {void}
     */
    function openVendorModal(type, uid, opts) {
        opts           = opts || {};
        _onSaveSuccess = opts.onSaveSuccess || null;

        var titles = { add: 'Create Vendor', edit: 'Update Vendor', clone: 'Clone Vendor' };
        $('#VendorFormModalTitle').text(titles[type] || 'Vendor');

        _doOpen(type, uid, opts);
    }

    function _initModalPlugins() {
        if (typeof initializeFlatPickr === 'function') initializeFlatPickr('#VM_CPDateOfBirth', '#VendorFormModal');
        if (typeof _attachBindListeners === 'function') _attachBindListeners('Vendor');
    }

    // ── Salutation helpers ────────────────────────────────────────────────────

    /**
     * @param {Array} list
     * @returns {void}
     */
    function _populateSalutationDropdown(list) {
        var $sel = $('#VM_SalutationUID');
        var html = '<option value="">—</option>';
        $.each(list, function (_, s) {
            html += '<option value="' + parseInt(s.SalutationUID, 10) + '">' + $('<span>').text(s.SalutationName).html() + '</option>';
        });
        $sel.html(html);
    }

    function _applyDefaultSalutation() {
        var defaultUID = (typeof JwtData !== 'undefined' && JwtData.GenSettings && JwtData.GenSettings.DefaultSalutationUID)
            ? parseInt(JwtData.GenSettings.DefaultSalutationUID, 10) : 0;
        var $sel = $('#VM_SalutationUID');
        if (defaultUID > 0) {
            $sel.val(defaultUID);
        } else {
            var $first = $sel.find('option:not([value=""])').first();
            if ($first.length) $sel.val($first.val());
        }
    }

    /**
     * @param {Function} callback
     * @returns {void}
     */
    function _fetchSalutationsFromServer(callback) {
        $.ajax({
            url: '/settings/getSalutationList', method: 'GET', cache: false,
            success: function (resp) {
                if (!resp.Error && resp.Data && resp.Data.length) {
                    _populateSalutationDropdown(resp.Data);
                }
                callback();
            },
            error: function () { callback(); }
        });
    }

    /**
     * @param {Function} callback
     * @returns {void}
     */
    function _ensureSalutations(callback) {
        if ($('#VM_SalutationUID option').length > 1) { callback(); return; }
        if (typeof UpstashService !== 'undefined' && UpstashService.isEnabled()) {
            UpstashService.get(UpstashService.globalKey('salutation')).then(function (data) {
                if (data && Array.isArray(data) && data.length > 0) {
                    _populateSalutationDropdown(data);
                    callback();
                } else {
                    _fetchSalutationsFromServer(callback);
                }
            }).catch(function () { _fetchSalutationsFromServer(callback); });
        } else {
            _fetchSalutationsFromServer(callback);
        }
    }

    // ── Open after body is ready ──────────────────────────────────────────────
    /**
     * @param {string} type
     * @param {number|null} uid
     * @param {Object} opts
     * @returns {void}
     */
    function _doOpen(type, uid, opts) {
        $('#VendorModalForm').data('mode', type);
        _resetVendorModal();

        if (type === 'add') {
            if (opts && opts.prefillName) {
                var val = opts.prefillName;
                if (/^\d+$/.test(val)) { $('#VM_MobileNumber').val(val); }
                else                   { $('#VM_Name').val(val); }
            }
            // Show customer linking section and init select2 (only if searchCustomers is available)
            if (typeof searchCustomers === 'function') {
                $('#CustomerLinkingDiv').removeClass('d-none');
                searchCustomers('VM_Customers');
            }
            _ensureSalutations(function () {
                _applyDefaultSalutation();
                $('#VendorFormModal').modal('show');
                _isCreateMode = true;
                _isDirty      = false;
            });
            return;
        }

        // edit / clone — fetch existing data first
        _editUID = (type === 'clone') ? 0 : (uid || 0);
        var _fetchUID = uid || 0;
        $.ajax({
            url   : '/vendors/getVendorForModal/' + _fetchUID,
            method: 'GET',
            cache : false,
            success: function (response) {
                if (response.Error) {
                    showAlertMessageSwal('error', '', response.Message || 'Failed to load vendor.');
                    return;
                }
                _ensureSalutations(function () {
                    _populateVendorModal(type, response);
                    $('#VendorFormModal').modal('show');
                });
            },
            error: function () {
                showAlertMessageSwal('error', '', 'Failed to load vendor.');
            }
        });
    }

    // ── Reset modal to a clean add state ──────────────────────────────────────
    function _resetVendorModal() {
        _isCreateMode = false;
        _isDirty      = false;
        _editUID = 0;
        if (typeof delBankDataFlag !== 'undefined') delBankDataFlag = 0;
        if (typeof delBankData     !== 'undefined') delBankData     = [];

        var $form = $('#VendorModalForm');
        if ($form.length) $form[0].reset();
        if ($('#VendorUID').length) $('#VendorUID').val('');

        // Reset bank table
        $('#bankDetailsBody').empty();
        $('#appendBankDetails').addClass('d-none');
        $('#bankEmptyState').removeClass('d-none');
        $('#bankDivider').addClass('d-none');

        // Reset address
        if (typeof resetAddrData === 'function') resetAddrData();

        // Reset customer linking
        $('#CustomerLinkingDiv').addClass('d-none');
        $('#CustomerDiv').addClass('d-none');
        $('#ResetCustomerLinking').addClass('d-none');
        $('input[name="CustomerLinkingCheck"]').prop('checked', false);

        // Reset attachment zone state
        if (typeof _attachResetState === 'function') _attachResetState('Vendor');

        if (typeof initializeFlatPickr === 'function') initializeFlatPickr('#VM_CPDateOfBirth', '#VendorFormModal');
    }

    // ── Populate form fields for edit ─────────────────────────────────────────
    /**
     * @param {string} type
     * @param {Object} response
     * @returns {void}
     */
    function _populateVendorModal(type, response) {
        var d = response.Data;
        var isClone = (type === 'clone');

        _editUID = isClone ? 0 : (d.VendorUID || 0);

        // Attachments (skip for clone — fresh record)
        if (!isClone && typeof _attachResetState === 'function') {
            _attachResetState('Vendor');
            if (response.Attachments && response.Attachments.length && _attachState['Vendor']) {
                _attachState['Vendor'].existing = response.Attachments;
                if (typeof _attachRender === 'function') _attachRender('Vendor');
            }
        }

        $('#VendorUID').val(isClone ? '' : (d.VendorUID || ''));
        // Hide customer linking in edit/clone mode
        $('#CustomerLinkingDiv').addClass('d-none');
        $('#VM_SalutationUID').val(d.SalutationUID || '');
        $('#VM_Name').val(d.Name || '');
        $('#VM_Area').val(d.Area || '');
        $('#VM_MobileNumber').val(d.MobileNumber || '');
        $('#VM_MobileCCBtn').text(d.CountryCode || '');
        $('#VM_CountryCode').val(d.CountryCode || '');
        $('#VM_CountryISO2').val(d.CountryISO2 || '');
        $('#VM_EmailAddress').val(d.EmailAddress || '');
        $('#VM_DebitCreditAmount').val(_smartDecimal(d.OpeningBalance ?? d.DebitCreditAmount));
        $('#VM_DebitCreditCheck').val(d.OpeningBalType || d.DebitCreditType || 'Credit');
        $('#VM_PANNumber').val(d.PANNumber || '');
        $('#VM_ContactPerson').val(d.ContactPerson || '');
        $('#VM_CPDateOfBirth').val(d.DateOfBirth || '');
        $('#VM_GSTIN').val(d.GSTIN || '');
        $('#VM_CompanyName').val(d.CompanyName || '');
        $('#VM_Notes').val(d.Notes || '');

        // Bank details
        if (response.BankDetails && response.BankDetails.length) {
            response.BankDetails.forEach(function (b) {
                if (typeof appendBankRowToTable === 'function') appendBankRowToTable(b);
            });
            $('#appendBankDetails').removeClass('d-none');
            $('#bankEmptyState').addClass('d-none');
            $('#bankDivider').removeClass('d-none');
        }

        // Addresses — VendAddressUID (not CustAddressUID); zero UIDs for clone
        if (response.BillingAddr) {
            var ba = response.BillingAddr;
            billingAddrData = {
                UID      : isClone ? 0 : (ba.VendAddressUID || 0),
                Line1    : ba.Line1    || '',
                Line2    : ba.Line2    || '',
                Pincode  : ba.Pincode  || '',
                StateId  : ba.State    || '',
                StateName: ba.StateText || '',
                StateISO2: '',
                CityId   : ba.City     || '',
                CityName : ba.CityText || ''
            };
            if (typeof renderAddrSummary === 'function') renderAddrSummary(1, billingAddrData);
        }
        if (response.ShippingAddr) {
            var sa = response.ShippingAddr;
            shippingAddrData = {
                UID      : isClone ? 0 : (sa.VendAddressUID || 0),
                Line1    : sa.Line1    || '',
                Line2    : sa.Line2    || '',
                Pincode  : sa.Pincode  || '',
                StateId  : sa.State    || '',
                StateName: sa.StateText || '',
                StateISO2: '',
                CityId   : sa.City     || '',
                CityName : sa.CityText || ''
            };
            if (typeof renderAddrSummary === 'function') renderAddrSummary(2, shippingAddrData);
        }
        if (typeof _updateCopyButtons === 'function') _updateCopyButtons();
    }

    // ── Save button click ─────────────────────────────────────────────────────
    $(document).on('click', '#VendorFormSaveBtn', function () {
        $('#VendorModalForm').submit();
    });

    // ── Form submit ───────────────────────────────────────────────────────────
    $(document).on('submit', '#VendorModalForm', function (e) {
        e.preventDefault();

        var mode = $(this).data('mode');

        var mobileValue = $('#VM_MobileNumber').val();
        var countryCode = ($('#VM_CountryCode').val() || '91').replace('+', '');
        if (typeof validateMobileNumber === 'function') {
            var mobileValidation = validateMobileNumber(mobileValue, countryCode);
            if (!mobileValidation.isValid) { showAlertMessageSwal('error', '', mobileValidation.message); return; }
        }
        if (typeof validatePANNumber === 'function') {
            var panValidation = validatePANNumber($('#VM_PANNumber').val());
            if (!panValidation.isValid) { showAlertMessageSwal('error', '', panValidation.message); return; }
        }
        if (typeof validateGSTIN === 'function') {
            var gstinValidation = validateGSTIN($('#VM_GSTIN').val());
            if (!gstinValidation.isValid) { showAlertMessageSwal('error', '', gstinValidation.message); return; }
        }

        var formData = new FormData($('#VendorModalForm')[0]);

        if (mode === 'edit') {
            formData.set('VendorUID', _editUID || 0);
        }
        formData.set('SalutationUID', $('#VM_SalutationUID').val() || '');

        // Attachment files
        if (typeof _attachState !== 'undefined' && _attachState['Vendor']) {
            (_attachState['Vendor'].newFiles || []).forEach(function (f) {
                formData.append('VendAttachFiles[]', f, f.name);
            });
        }

        // Bank details
        if (typeof getBankRecordsFromTable === 'function') {
            var bankRecords = getBankRecordsFromTable();
            if (typeof validateBankRecords === 'function') {
                var bankValid = validateBankRecords(bankRecords);
                if (!bankValid.ok) { showAlertMessageSwal('error', '', bankValid.msg); return; }
            }
            formData.append('BankDetailsJSON', JSON.stringify(bankRecords));
            formData.append('BankDetailsCount', String(bankRecords.length));
        } else {
            formData.append('BankDetailsJSON', JSON.stringify([]));
            formData.append('BankDetailsCount', '0');
        }

        if (typeof delBankDataFlag !== 'undefined' && delBankDataFlag) {
            formData.append('delBankDataFlag', delBankDataFlag);
            if (typeof delBankData !== 'undefined') {
                delBankData.forEach(function (id) { formData.append('delBankData[]', id); });
            }
        }

        // Addresses
        if (typeof billingAddrData !== 'undefined' && billingAddrData) {
            formData.append('BillAddressUID',    billingAddrData.UID       || 0);
            formData.append('BillAddrLine1',     billingAddrData.Line1     || '');
            formData.append('BillAddrLine2',     billingAddrData.Line2     || '');
            formData.append('BillAddrPincode',   billingAddrData.Pincode   || '');
            formData.append('BillAddrState',     billingAddrData.StateId   || '');
            formData.append('BillAddrStateText', billingAddrData.StateName || '');
            formData.append('BillAddrCity',      billingAddrData.CityId    || '');
            formData.append('BillAddrCityText',  billingAddrData.CityName  || '');
        }
        if (typeof shippingAddrData !== 'undefined' && shippingAddrData) {
            formData.append('ShipAddressUID',    shippingAddrData.UID       || 0);
            formData.append('ShipAddrLine1',     shippingAddrData.Line1     || '');
            formData.append('ShipAddrLine2',     shippingAddrData.Line2     || '');
            formData.append('ShipAddrPincode',   shippingAddrData.Pincode   || '');
            formData.append('ShipAddrState',     shippingAddrData.StateId   || '');
            formData.append('ShipAddrStateText', shippingAddrData.StateName || '');
            formData.append('ShipAddrCity',      shippingAddrData.CityId    || '');
            formData.append('ShipAddrCityText',  shippingAddrData.CityName  || '');
        }
        if (typeof delAddrDetailFlag !== 'undefined' && delAddrDetailFlag) {
            formData.append('delAddrDetailFlag', delAddrDetailFlag);
            if (typeof delAddrData !== 'undefined') {
                delAddrData.forEach(function (id) { formData.append('delAddrData[]', id); });
            }
        }

        $('#VendorFormSaveBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Saving...');

        var onDone = function (response) {
            $('#VendorFormSaveBtn').prop('disabled', false).html('<i class="bx bx-check me-1"></i>Save');
            if (response.Error) {
                showAlertMessageSwal('error', '', response.Message);
                return;
            }
            _isDirty      = false;
            _isCreateMode = false;
            $('#VendorFormModal').modal('hide');
            if (typeof _onSaveSuccess === 'function') {
                _onSaveSuccess(response);
            }
        };

        if (mode === 'edit') {
            formData.append('PageNo', typeof PageNo !== 'undefined' ? PageNo : 1);
            $.ajax({ url: '/vendors/updateVendorData', method: 'POST', data: formData, cache: false, processData: false, contentType: false, success: onDone });
        } else {
            $.ajax({ url: '/vendors/addVendorData',    method: 'POST', data: formData, cache: false, processData: false, contentType: false, success: onDone });
        }
    });

    // ── Dirty-tracking ────────────────────────────────────────────────────────
    $(document).on('input change', '#VendorModalForm input, #VendorModalForm textarea, #VendorModalForm select', function () {
        if (_isCreateMode) _isDirty = true;
    });

    // ── Unsaved-changes guard ─────────────────────────────────────────────────
    $(document).on('hide.bs.modal', '#VendorFormModal', function (e) {
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
                $('#VendorFormModal').modal('hide');
            }
        });
    });

    // ── Helper ────────────────────────────────────────────────────────────────
    /**
     * @param {*} val
     * @returns {string}
     */
    function _smartDecimal(val) {
        var n = parseFloat(val);
        if (isNaN(n)) return '0';
        return n === 0 ? '0' : String(parseFloat(n.toFixed(6)));
    }

}(window, jQuery));
