/**
 * CustomerForm — shared modal for add / edit / clone across all pages.
 *
 * Usage:
 *   CustomerForm.open('add', null, { prefillName: 'Ravi', onSaveSuccess: fn });
 *   prefillName: if all digits → fills CM_MobileNumber, otherwise → fills CM_Name
 *   CustomerForm.open('edit', uid, { onSaveSuccess: fn });
 *   CustomerForm.open('clone', uid, { onSaveSuccess: fn });
 *
 * onSaveSuccess(response) fires after a successful save.
 * Customers page: refresh list/stats.
 * Transaction pages: auto-select the new customer into Select2.
 */
(function (window, $) {
    'use strict';

    // ── Internal state ────────────────────────────────────────────────────────
    var _editUID       = 0;
    var _onSaveSuccess = null;
    var _bodyLoaded    = false;

    // ── Public API ────────────────────────────────────────────────────────────
    window.CustomerForm = { open: openCustomerModal };

    // ── On DOM ready: body is pre-rendered server-side — just init plugins ────
    $(function () {
        if ($('#CustomerFormModalBody').children().length > 0) {
            _bodyLoaded = true;
            _initModalPlugins();
        }
    });

    // ── Open ──────────────────────────────────────────────────────────────────
    function openCustomerModal(type, uid, opts) {
        opts           = opts || {};
        _onSaveSuccess = opts.onSaveSuccess || null;

        var titles = { add: 'Create Customer', edit: 'Update Customer', clone: 'Clone Customer' };
        $('#CustomerFormModalTitle').text(titles[type] || 'Customer');

        _doOpen(type, uid, opts);
    }

    function _initModalPlugins() {
        if (typeof initializeFlatPickr  === 'function') initializeFlatPickr('#CM_CPDateOfBirth', '#CustomerFormModal');
        if (typeof initializeSelect2Tags === 'function') {
            initializeSelect2Tags('#CM_Tags',     'Type and press enter...');
            initializeSelect2Tags('#CM_CCEmails', 'Type and press enter...');
        }
        if (typeof reinitDropzoneOne === 'function') reinitDropzoneOne('#CustomerFormModalBody #DropzoneOneBasic');
    }

    // ── Salutation helpers ────────────────────────────────────────────────────

    function _populateSalutationDropdown(list) {
        var $sel = $('#CM_SalutationUID');
        var html = '<option value="">—</option>';
        $.each(list, function (_, s) {
            html += '<option value="' + parseInt(s.SalutationUID, 10) + '">' + $('<span>').text(s.SalutationName).html() + '</option>';
        });
        $sel.html(html);
    }

    function _applyDefaultSalutation() {
        var defaultUID = (typeof JwtData !== 'undefined' && JwtData.GenSettings && JwtData.GenSettings.DefaultSalutationUID)
            ? parseInt(JwtData.GenSettings.DefaultSalutationUID, 10) : 0;
        var $sel = $('#CM_SalutationUID');
        if (defaultUID > 0) {
            $sel.val(defaultUID);
        } else {
            $sel.find('option:not([value=""])').first().prop('selected', true);
        }
    }

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

    function _ensureSalutations(callback) {
        if ($('#CM_SalutationUID option').length > 1) { callback(); return; }
        if (typeof UpstashService !== 'undefined' && UpstashService.isEnabled()) {
            UpstashService.get(UpstashService.orgKey('salutation')).then(function (data) {
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
    function _doOpen(type, uid, opts) {
        $('#CustomerModalForm').data('mode', type);
        _resetCustomerModal();

        if (type === 'add') {
            if (opts && opts.prefillName) {
                var val = opts.prefillName;
                if (/^\d+$/.test(val)) { $('#CM_MobileNumber').val(val); }
                else                   { $('#CM_Name').val(val); }
            }
            _ensureSalutations(function () {
                _applyDefaultSalutation();
                $('#CustomerFormModal').modal('show');
            });
            return;
        }

        // edit / clone — fetch existing data first
        _editUID = uid || 0;
        $.ajax({
            url   : '/customers/getCustomerForModal/' + _editUID,
            method: 'GET',
            cache : false,
            success: function (response) {
                if (response.Error) {
                    showAlertMessageSwal('error', '', response.Message || 'Failed to load customer.');
                    return;
                }
                _ensureSalutations(function () {
                    _populateCustomerModal(type, response);
                    $('#CustomerFormModal').modal('show');
                });
            },
            error: function () {
                showAlertMessageSwal('error', '', 'Failed to load customer.');
            }
        });
    }

    // ── Reset modal to a clean add state ──────────────────────────────────────
    function _resetCustomerModal() {
        _editUID = 0;
        if (typeof delBankDataFlag       !== 'undefined') delBankDataFlag       = 0;
        if (typeof delBankData           !== 'undefined') delBankData           = [];
        if (typeof hasRemovedStoredImage !== 'undefined') hasRemovedStoredImage = false;

        var $form = $('#CustomerModalForm');
        if ($form.length) $form[0].reset();
        if ($('#CustomerUID').length) $('#CustomerUID').val('');

        // Reset bank table
        $('#bankDetailsBody').empty();
        $('#appendBankDetails').addClass('d-none');
        $('#bankEmptyState').removeClass('d-none');
        $('#bankDivider').addClass('d-none');

        // Reset address
        if (typeof resetAddrData === 'function') resetAddrData();

        // Re-init plugins
        if (typeof reinitDropzoneOne    === 'function') reinitDropzoneOne('#CustomerFormModalBody #DropzoneOneBasic');
        if (typeof initializeFlatPickr  === 'function') initializeFlatPickr('#CM_CPDateOfBirth', '#CustomerFormModal');
        if (typeof initializeSelect2Tags === 'function') {
            initializeSelect2Tags('#CM_Tags',     'Type and press enter...');
            initializeSelect2Tags('#CM_CCEmails', 'Type and press enter...');
        }
    }

    // ── Populate form fields for edit / clone ─────────────────────────────────
    function _populateCustomerModal(type, response) {
        var d       = response.Data;
        var isClone = (type === 'clone');

        _editUID = isClone ? 0 : (d.CustomerUID || 0);

        $('#CM_SalutationUID').val(d.SalutationUID || '');
        $('#CM_Name').val(d.Name || '');
        $('#CM_Area').val(d.Area || '');
        $('#CM_MobileNumber').val(d.MobileNumber || '');
        $('#CM_CountryCode').val(d.CountryCode || '');
        $('#CM_CountryISO2').val(d.CountryISO2 || '');
        $('#CM_EmailAddress').val(d.EmailAddress || '');
        $('#CM_DebitCreditAmount').val(_smartDecimal(d.DebitCreditAmount));
        $('#CM_DebitCreditCheck').val(d.DebitCreditType || 'Debit').trigger('change');
        $('#CM_PANNumber').val(d.PANNumber || '');
        $('#CM_ContactPerson').val(d.ContactPerson || '');
        $('#CM_CPDateOfBirth').val(d.DateOfBirth || '');
        $('#CM_CustomerTypeUID').val(d.CustomerTypeUID || '').trigger('change');
        $('#CM_GSTIN').val(d.GSTIN || '');
        $('#CM_CompanyName').val(d.CompanyName || '');
        $('#CM_DiscountPercent').val(_smartDecimal(d.DiscountPercent));
        $('#CM_CreditPeriod').val(d.CreditPeriod || '30');
        $('#CM_CreditLimit').val(_smartDecimal(d.CreditLimit));
        $('#CM_Notes').val(d.Notes || '');

        // Tags
        var $tags = $('#CM_Tags');
        $tags.empty();
        if (d.Tags) {
            d.Tags.split(',').forEach(function (t) {
                t = t.trim();
                if (t) $tags.append(new Option(t, t, true, true));
            });
            $tags.trigger('change');
        }

        // CC Emails
        var $cc = $('#CM_CCEmails');
        $cc.empty();
        if (d.CCEmails) {
            d.CCEmails.split(',').forEach(function (e) {
                e = e.trim();
                if (e) $cc.append(new Option(e, e, true, true));
            });
            $cc.trigger('change');
        }

        // Image
        if (d.Image && !isClone) {
            if (typeof commonSetDropzoneImageOne === 'function' && typeof CDN_URL !== 'undefined') {
                commonSetDropzoneImageOne(CDN_URL + d.Image);
            }
        }

        // Bank details
        if (response.BankDetails && response.BankDetails.length) {
            response.BankDetails.forEach(function (b) {
                if (typeof appendBankRowToTable === 'function') appendBankRowToTable(b);
            });
            $('#appendBankDetails').removeClass('d-none');
            $('#bankEmptyState').addClass('d-none');
            $('#bankDivider').removeClass('d-none');
        }

        // Addresses
        if (response.BillingAddr) {
            var ba = response.BillingAddr;
            billingAddrData = {
                UID      : isClone ? 0 : (ba.CustAddressUID || 0),
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
                UID      : isClone ? 0 : (sa.CustAddressUID || 0),
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
    $(document).on('click', '#CustomerFormSaveBtn', function () {
        $('#CustomerModalForm').submit();
    });

    // ── Form submit ───────────────────────────────────────────────────────────
    $(document).on('submit', '#CustomerModalForm', function (e) {
        e.preventDefault();

        var mode = $(this).data('mode');

        // Validations
        var mobileValue      = $('#CM_MobileNumber').val();
        var countryCode      = ($('#CM_CountryCode').val() || '91').replace('+', '');
        if (typeof validateMobileNumber === 'function') {
            var mobileValidation = validateMobileNumber(mobileValue, countryCode);
            if (!mobileValidation.isValid) { showAlertMessageSwal('error', '', mobileValidation.message); return; }
        }
        if (typeof validatePANNumber === 'function') {
            var panValidation = validatePANNumber($('#CM_PANNumber').val());
            if (!panValidation.isValid) { showAlertMessageSwal('error', '', panValidation.message); return; }
        }
        if (typeof validateGSTIN === 'function') {
            var gstinValidation = validateGSTIN($('#CM_GSTIN').val());
            if (!gstinValidation.isValid) { showAlertMessageSwal('error', '', gstinValidation.message); return; }
        }

        var formData = new FormData($('#CustomerModalForm')[0]);

        if (mode === 'edit') {
            formData.set('CustomerUID', _editUID || 0);
        }

        if (mode === 'edit' && typeof hasRemovedStoredImage !== 'undefined' && hasRemovedStoredImage
            && typeof myOneDropzone !== 'undefined' && myOneDropzone && myOneDropzone.files.length === 0) {
            formData.append('ImageRemoved', 1);
        }
        if (typeof myOneDropzone !== 'undefined' && myOneDropzone && myOneDropzone.files.length > 0 && !myOneDropzone.files[0].isStored) {
            formData.append('UploadImage', myOneDropzone.files[0]);
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

        $('#CustomerFormSaveBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Saving...');

        var onDone = function (response) {
            $('#CustomerFormSaveBtn').prop('disabled', false).html('<i class="bx bx-check me-1"></i>Save');
            if (response.Error) {
                showAlertMessageSwal('error', '', response.Message);
                return;
            }
            showToastNotification(response.Message, 'success');
            $('#CustomerFormModal').modal('hide');
            if (typeof _onSaveSuccess === 'function') {
                _onSaveSuccess(response);
            }
        };

        // returnList=1 tells the backend to render the updated list + stats.
        // Only the customers list page needs this — transaction pages skip it.
        if (_onSaveSuccess && _onSaveSuccess._needsList) {
            formData.append('returnList', 1);
        }

        if (mode === 'edit') {
            formData.append('PageNo', typeof PageNo !== 'undefined' ? PageNo : 1);
            $.ajax({ url: '/customers/updateCustomerData', method: 'POST', data: formData, cache: false, processData: false, contentType: false, success: onDone });
        } else {
            $.ajax({ url: '/customers/addCustomerData', method: 'POST', data: formData, cache: false, processData: false, contentType: false, success: onDone });
        }
    });

    // ── Helper ────────────────────────────────────────────────────────────────
    function _smartDecimal(val) {
        var n = parseFloat(val);
        if (isNaN(n)) return '0';
        return n === 0 ? '0' : String(parseFloat(n.toFixed(6)));
    }

})(window, jQuery);
