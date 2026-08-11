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
    var _editUID             = 0;
    var _onSaveSuccess       = null;
    var _bodyLoaded          = false;
    var _customerTypesCache  = null;
    var _customerGroupsCache = null;
    var _isDirty             = false;   // true when user has changed a field in create mode
    var _isCreateMode        = false;   // true only while form is open in add mode
    var _gstinValidated      = 0;       // 1 when GSTIN was API-verified this session

    // ── Public API ────────────────────────────────────────────────────────────
    window.CustomerForm = {
        open:            openCustomerModal,
        clearGroupsCache: function () { _customerGroupsCache = null; },
    };

    // ── On DOM ready: body is pre-rendered server-side — just init plugins ────
    var _cmCCCfg = {
        btn      : '#CM_MobileCCBtn',
        dropdown : '#CM_CCDropdown',
        search   : '#CM_CCSearch',
        list     : '#CM_CCList',
        codeInput: '#CM_CountryCode',
        iso2Input: '#CM_CountryISO2',
    };

    $(function () {
        if ($('#CustomerFormModalBody').children().length > 0) {
            _bodyLoaded = true;
            _initModalPlugins();
        }
        PhoneCCDropdown.init(_cmCCCfg);
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
        // Bind attachment zone listeners once (shared attachments.js)
        if (typeof _attachBindListeners === 'function') _attachBindListeners('Customer');
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
            var $first = $sel.find('option:not([value=""])').first();
            if ($first.length) $sel.val($first.val());
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

    // ── Customer type helpers ─────────────────────────────────────────────────

    function _populateCustomerTypeSelect(types) {
        var $sel = $('#CM_CustomerTypeUID');
        if (!$sel.length || $sel.find('option[value!=""]').length) return;
        var html = '<option value="">-- Select Customer Type --</option>';
        $.each(types, function (_, t) {
            html += '<option value="' + parseInt(t.CustomerTypeUID) + '">' + $('<span>').text(t.TypeName || '').html() + '</option>';
        });
        $sel.html(html);
    }

    function _applyDefaultCustomerType() {
        if (!_customerTypesCache || !_customerTypesCache.length) return;
        var $sel = $('#CM_CustomerTypeUID');
        var defUID = null;
        $.each(_customerTypesCache, function (_, t) {
            if (t.IsDefault) { defUID = parseInt(t.CustomerTypeUID); return false; }
        });
        if (!defUID) defUID = parseInt(_customerTypesCache[0].CustomerTypeUID);
        $sel.val(defUID);
    }

    function _ensureCustomerTypes(callback) {
        if (_customerTypesCache !== null) {
            _populateCustomerTypeSelect(_customerTypesCache);
            callback();
            return;
        }
        if (typeof UpstashService !== 'undefined' && UpstashService.isEnabled()) {
            UpstashService.get(UpstashService.globalKey('customer-types')).then(function (data) {
                if (data && Array.isArray(data) && data.length > 0) {
                    _customerTypesCache = data;
                    _populateCustomerTypeSelect(data);
                    callback();
                } else {
                    _fetchCustomerTypesFromServer(callback);
                }
            }).catch(function () { _fetchCustomerTypesFromServer(callback); });
        } else {
            _fetchCustomerTypesFromServer(callback);
        }
    }

    function _fetchCustomerTypesFromServer(callback) {
        $.ajax({
            url: '/customers/getCustomerTypes', method: 'GET', cache: false,
            success: function (resp) {
                var types = (!resp.Error && resp.Data) ? resp.Data : [];
                _customerTypesCache = types;
                _populateCustomerTypeSelect(types);
                callback();
            },
            error: function () { callback(); }
        });
    }

    // ── Customer group helpers ────────────────────────────────────────────────

    function _populateCustomerGroupSelect(groups) {
        var $sel = $('#CM_GroupUID');
        $sel.find('option:not([value=""])').remove();
        $.each(groups, function (_, g) {
            $sel.append(new Option(g.GroupName, g.GroupUID));
        });
    }

    /**
     * @param {Function} callback
     * @returns {void}
     */
    function _ensureCustomerGroups(callback) {
        if (_customerGroupsCache !== null) {
            _populateCustomerGroupSelect(_customerGroupsCache);
            callback();
            return;
        }
        if (typeof UpstashService !== 'undefined' && UpstashService.isEnabled()) {
            UpstashService.hgetall(UpstashService.orgKey('customer-groups')).then(function (map) {
                if (map && Object.keys(map).length > 0) {
                    var groups = Object.values(map)
                        .map(function (v) { return typeof v === 'string' ? JSON.parse(v) : v; })
                        .sort(function (a, b) { return (a.GroupName || '').localeCompare(b.GroupName || ''); });
                    _customerGroupsCache = groups;
                    _populateCustomerGroupSelect(groups);
                    callback();
                } else {
                    _fetchCustomerGroupsFromServer(callback);
                }
            }).catch(function () { _fetchCustomerGroupsFromServer(callback); });
        } else {
            _fetchCustomerGroupsFromServer(callback);
        }
    }

    /**
     * @param {Function} callback
     * @returns {void}
     */
    function _fetchCustomerGroupsFromServer(callback) {
        $.ajax({
            url: '/customers/getGroupsForDropdown', method: 'GET', cache: false,
            success: function (resp) {
                var groups = (!resp.Error && resp.Groups) ? resp.Groups : [];
                _customerGroupsCache = groups;
                _populateCustomerGroupSelect(groups);
                callback();
            },
            error: function () { callback(); }
        });
    }

    // ── Open after body is ready ──────────────────────────────────────────────
    function _doOpen(type, uid, opts) {
        $('#CustomerModalForm').data('mode', type);
        _resetCustomerModal();

        // Register GSTIN fetch config so the shared confirmation overlay shows
        // Company Name, PAN, Status, and address before auto-filling the form.
        window.gstinFetchConfig = {
            /**
             * @param {Object} resp
             * @returns {string}
             */
            confirmRows: function (resp) {
                var rows = '';
                var _row = function (label, val) {
                    if (!val) return '';
                    return '<div class="gstin-confirm-row">' +
                        '<span class="gstin-confirm-row-label">' + label + '</span>' +
                        '<span class="gstin-confirm-row-value">' + $('<span>').text(val).html() + '</span>' +
                        '</div>';
                };
                rows += _row('Company Name', resp.TradeName || resp.LegalName || '');
                rows += _row('PAN',          resp.PAN       || '');
                rows += _row('State',        resp.StateName || '');
                if (resp.AddressLine1 || resp.City || resp.Pincode) {
                    var addr = [resp.AddressLine1, resp.City, resp.Pincode].filter(Boolean).join(', ');
                    rows += _row('Address', addr);
                }
                return rows;
            },
            /** @returns {void} */
            onValidated: function () {
                _gstinValidated = 1;
                $('#CM_GSTINValidated').val('1');
                $('#CM_GSTINValidatedMsg').addClass('show');
            },
            /**
             * @param {Object} resp
             * @returns {void}
             */
            onConfirm: function (resp) {
                if (resp.TradeName) $('#CM_CompanyName').val(resp.TradeName);
                if (resp.LegalName && !$.trim($('#CM_Name').val())) $('#CM_Name').val(resp.LegalName);
                if (resp.PAN       && !$.trim($('#CM_PANNumber').val())) $('#CM_PANNumber').val(resp.PAN);

                // Directly populate billingAddrData and render the summary card —
                // no address modal is opened; the section updates instantly in place.
                if (resp.AddressLine1 || resp.City || resp.Pincode) {
                    billingAddrData = {
                        UID      : 0,
                        Line1    : resp.AddressLine1 || '',
                        Line2    : resp.AddressLine2 || '',
                        Pincode  : resp.Pincode      || '',
                        StateId  : '',
                        StateName: resp.StateName    || '',
                        StateISO2: '',
                        CityId   : '',
                        CityName : resp.City         || '',
                    };
                    if (typeof renderAddrSummary   === 'function') renderAddrSummary(1, billingAddrData);
                    if (typeof _updateCopyButtons  === 'function') _updateCopyButtons();
                }
            },
        };

        if (type === 'add') {
            if (opts && opts.prefillName) {
                var val = opts.prefillName;
                if (/^\d+$/.test(val)) { $('#CM_MobileNumber').val(val); }
                else                   { $('#CM_Name').val(val); }
            }
            _ensureSalutations(function () {
                _ensureCustomerTypes(function () {
                    _ensureCustomerGroups(function () {
                        _applyDefaultSalutation();
                        _applyDefaultCustomerType();
                        $('#CustomerFormModal').modal('show');
                        _isCreateMode = true;
                        _isDirty      = false;
                        _loadNextCustomerNumber();
                    });
                });
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
                    showToastNotification(response.Message || 'Failed to load customer.', 'error');
                    return;
                }
                _ensureSalutations(function () {
                    _ensureCustomerTypes(function () {
                        _ensureCustomerGroups(function () {
                            _populateCustomerModal(type, response);
                            $('#CustomerFormModal').modal('show');
                        });
                    });
                });
            },
            error: function () {
                showAlertMessageSwal('error', '', 'Failed to load customer.');
            }
        });
    }

    // ── Next customer number — read from Upstash cache, AJAX fallback ─────────
    /**
     * @returns {void}
     */
    function _loadNextCustomerNumber() {
        var $field = $('#CM_CustomerNumber');
        if (!$field.length) return;
        if (typeof UpstashService !== 'undefined' && UpstashService.isEnabled()) {
            UpstashService.get(UpstashService.orgKey('credit-settings')).then(function (data) {
                if (data && data.cust_next_number) {
                    $field.val(data.cust_next_number);
                } else {
                    _fetchCustomerNumberFallback($field);
                }
            });
        } else {
            _fetchCustomerNumberFallback($field);
        }
    }

    /**
     * @param {jQuery} $field
     * @returns {void}
     */
    function _fetchCustomerNumberFallback($field) {
        $.ajax({
            url   : '/customers/getNextCustomerNumber',
            method: 'GET',
            cache : false,
            success: function (resp) {
                if (!resp.Error && resp.CustomerNumber) {
                    $field.val(resp.CustomerNumber);
                }
            }
        });
    }

    // ── Reset modal to a clean add state ──────────────────────────────────────
    function _resetCustomerModal() {
        _isCreateMode   = false;
        _isDirty        = false;
        _editUID        = 0;
        _gstinValidated = 0;
        _clearFieldErrors();
        if (typeof delBankDataFlag !== 'undefined') delBankDataFlag = 0;
        if (typeof delBankData     !== 'undefined') delBankData     = [];

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

        // Reset GSTIN validated tag
        $('#CM_GSTINValidated').val('0');
        $('#CM_GSTINValidatedMsg').removeClass('show');

        // Reset attachment zone state (listeners stay bound)
        if (typeof _attachResetState === 'function') _attachResetState('Customer');

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

        // Load existing attachments from response (no AJAX — already in response)
        if (!isClone && typeof _attachResetState === 'function') {
            _attachResetState('Customer');
            if (response.Attachments && response.Attachments.length && _attachState['Customer']) {
                _attachState['Customer'].existing = response.Attachments;
                if (typeof _attachRender === 'function') _attachRender('Customer');
            }
        }

        $('#CM_CustomerNumber').val(d.CustomerNumber || '');
        $('#CM_SalutationUID').val(d.SalutationUID || '');
        $('#CM_Name').val(d.Name || '');
        $('#CM_Area').val(d.Area || '');
        $('#CM_MobileNumber').val(d.MobileNumber || '');
        $('#CM_MobileCCBtn').text(d.CountryCode || '');
        $('#CM_CountryCode').val(d.CountryCode || '');
        $('#CM_CountryISO2').val(d.CountryISO2 || '');
        $('#CM_EmailAddress').val(d.EmailAddress || '');
        // Use OpeningBalance from CustOpeningBalanceTbl (source of truth),
        // fallback to DebitCreditAmount from CustomerTbl for legacy compatibility
        $('#CM_DebitCreditAmount').val(_smartDecimal(d.OpeningBalance ?? d.DebitCreditAmount));
        $('#CM_DebitCreditCheck').val(d.OpeningBalType || d.DebitCreditType || 'Debit').trigger('change');
        $('#CM_PANNumber').val(d.PANNumber || '');
        $('#CM_ContactPerson').val(d.ContactPerson || '');
        $('#CM_CPDateOfBirth').val(d.DateOfBirth || '');
        $('#CM_CustomerTypeUID').val(d.CustomerTypeUID || '').trigger('change');
        $('#CM_GroupUID').val(d.GroupUID || '');
        $('#CM_AllowPortalAccess').prop('checked', !!d.AllowPortalAccess);
        $('#CM_GSTIN').val(d.GSTIN || '');
        // Restore GSTIN validated state
        if (parseInt(d.GSTINValidated || 0, 10) === 1) {
            _gstinValidated = 1;
            $('#CM_GSTINValidated').val('1');
            $('#CM_GSTINValidatedMsg').addClass('show');
        } else {
            _gstinValidated = 0;
            $('#CM_GSTINValidated').val('0');
            $('#CM_GSTINValidatedMsg').removeClass('show');
        }
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

    // ── Portal access: require a valid email before allowing the checkbox ────────
    $(document).on('change', '#CM_AllowPortalAccess', function () {
        if (!$(this).is(':checked')) return;
        var email = $.trim($('#CM_EmailAddress').val());
        var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!email) {
            $(this).prop('checked', false);
            showToastNotification('Please enter an email address before enabling portal access.', 'error');
            $('#CM_EmailAddress').trigger('focus');
            return;
        }
        if (!emailRegex.test(email)) {
            $(this).prop('checked', false);
            showToastNotification('The email address is invalid. Please correct it before enabling portal access.', 'error');
            $('#CM_EmailAddress').trigger('focus');
        }
    });

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
        var emailValue = $.trim($('#CM_EmailAddress').val());
        if (emailValue && typeof validateEmail === 'function' && !validateEmail(emailValue)) {
            showAlertMessageSwal('error', '', 'Invalid email address format. Please enter a valid email.');
            return;
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
        // Explicitly read the current select value — FormData from reset+programmatic .val() can be unreliable
        formData.set('SalutationUID', $('#CM_SalutationUID').val() || '');

        // Append new attachment files (multi-image zone replaces old single Dropzone)
        if (typeof _attachState !== 'undefined' && _attachState['Customer']) {
            (_attachState['Customer'].newFiles || []).forEach(function(f) {
                formData.append('CustAttachFiles[]', f, f.name);
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

        _clearFieldErrors();
        $('#CustomerFormSaveBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Saving...');

        var onDone = function (response) {
            $('#CustomerFormSaveBtn').prop('disabled', false).html('<i class="bx bx-check me-1"></i>Save');
            if (response.Error) {
                if (response.FieldErrors && Object.keys(response.FieldErrors).length) {
                    _showFieldErrors(response.FieldErrors);
                } else {
                    showToastNotification(response.Message || 'An error occurred. Please try again.', 'error');
                }
                return;
            }
            _clearFieldErrors();
            _isDirty      = false;
            _isCreateMode = false;
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

    // ── GSTIN edit: clear validated badge whenever the user changes the field ──
    $(document).on('input', '#CM_GSTIN', function () {
        if (_gstinValidated) {
            _gstinValidated = 0;
            $('#CM_GSTINValidated').val('0');
            $('#CM_GSTINValidatedMsg').removeClass('show');
        }
    });

    // ── Dirty-tracking: flag any user change while in create mode ────────────
    $(document).on('input change', '#CustomerModalForm input, #CustomerModalForm textarea, #CustomerModalForm select', function () {
        if (_isCreateMode) _isDirty = true;
    });

    // ── Unsaved-changes guard: intercept close in create mode only ────────────
    $(document).on('hide.bs.modal', '#CustomerFormModal', function (e) {
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
                $('#CustomerFormModal').modal('hide');
            }
        });
    });

    // ── Helper ────────────────────────────────────────────────────────────────
    function _smartDecimal(val) {
        var n = parseFloat(val);
        if (isNaN(n)) return '0';
        return n === 0 ? '0' : String(parseFloat(n.toFixed(6)));
    }

    /**
     * Remove all inline field error messages from the customer form.
     * @returns {void}
     */
    function _clearFieldErrors() {
        $('#CustomerModalForm .r2k-field-error').remove();
        $('#CustomerModalForm .is-invalid').removeClass('is-invalid');
    }

    /**
     * Display backend validation errors inline below their corresponding fields.
     * Uses the #CM_FieldName ID convention (e.g. MobileNumber → #CM_MobileNumber).
     * @param {Object} fieldErrors - map of field name → error message
     * @returns {void}
     */
    function _showFieldErrors(fieldErrors) {
        var firstField = null;
        $.each(fieldErrors, function (field, msg) {
            var $input = $('#CM_' + field);
            if (!$input.length) return;
            $input.addClass('is-invalid');
            $input.closest('.mb-3').find('.r2k-field-error').remove();
            $input.closest('.mb-3').append('<div class="r2k-field-error">' + $('<span>').text(msg).html() + '</div>');
            if (!firstField) firstField = $input;
        });
        if (firstField) {
            firstField[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }

})(window, jQuery);
