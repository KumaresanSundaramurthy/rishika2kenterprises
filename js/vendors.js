// ── List page AJAX functions ──────────────────────────────────────────────

function getVendorsDetails(PageNo, RowLimit, Filter) {
    $.ajax({
        url   : '/vendors/getVendorsPageDetails/' + (PageNo || 1),
        method: 'POST',
        cache : false,
        data  : {
            RowLimit  : RowLimit,
            PageNo    : PageNo,
            Filter    : Filter,
            ModuleId  : ModuleId,
            [CsrfName]: CsrfToken,
        },
        success: function (response) {
            if (response.Error) {
                $(ModuleTable + ' tbody').html('');
                $(ModulePag).html('<div class="alert alert-danger" role="alert"><strong>' + response.Message + '</strong></div>');
            } else {
                $(ModulePag).html(response.Pagination);
                $(ModuleTable + ' tbody').html(response.RecordHtmlData);
            }
        },
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
            $('#VendorFormSaveBtn').prop('disabled', false).html('<i class="bx bx-check me-1"></i>Save');
            if (response.Error) {
                showAlertMessageSwal('error', '', response.Message);
            } else {
                showToastNotification(response.Message, 'success');
                $('#VendorFormModal').modal('hide');
                $(ModulePag).html(response.Pagination);
                $(ModuleTable + ' tbody').html(response.List);
                updateVendorStats(response.Stats);
                executeTablePagnCommonFunc(response, false);
            }
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
            $('#VendorFormSaveBtn').prop('disabled', false).html('<i class="bx bx-check me-1"></i>Save');
            if (response.Error) {
                showAlertMessageSwal('error', '', response.Message);
            } else {
                showToastNotification(response.Message, 'success');
                $('#VendorFormModal').modal('hide');
                $(ModulePag).html(response.Pagination);
                $(ModuleTable + ' tbody').html(response.List);
                updateVendorStats(response.Stats);
                executeTablePagnCommonFunc(response, false);
            }
        }
    });
}

function searchCustomers(key) {
    $('#' + key).select2({
        placeholder: '-- Search Customers --',
        minimumInputLength: 3,
        allowClear: true,
        escapeMarkup: function (markup) { return markup; },
        ajax: {
            url: '/customers/searchCustomers',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                AjaxLoading = 0;
                return { term: params.term, type: 'public' };
            },
            processResults: function (data) {
                AjaxLoading = 1;
                return { results: data.Lists };
            },
            cache: true
        }
    }).on('select2:close', function () {
        AjaxLoading = 1;
    });
}

// ── Update vendor stat cards from response.Stats ─────────────────────────
function updateVendorStats(stats) {
    if (!stats) return;
    var s = stats;
    $('.vend-stat-total').text(Number(s.TotalCount || 0).toLocaleString());
    $('.vend-stat-active').text(Number(s.ActiveCount || 0).toLocaleString());
    $('.vend-stat-month').text(Number(s.MonthCount || 0).toLocaleString());
    $('.vend-stat-fy').text(Number(s.FYCount || 0).toLocaleString());
    $('.vend-stat-lastmonth').text(Number(s.LastMonthCount || 0).toLocaleString());
}

// ── Toggle vendor active/inactive status ─────────────────────────────────
function toggleVendorStatus(VendorUID, IsActive) {
    $.ajax({
        url   : '/vendors/toggleVendorStatus',
        method: 'POST',
        cache : false,
        data  : { VendorUID: VendorUID, IsActive: IsActive, PageNo: PageNo, [CsrfName]: CsrfToken },
        success: function (response) {
            if (response.Error) {
                showAlertMessageSwal('error', '', response.Message);
            } else {
                showToastNotification(response.Message, 'success');
                $(ModulePag).html(response.Pagination);
                $(ModuleTable + ' tbody').html(response.List);
                updateVendorStats(response.Stats);
                executeTablePagnCommonFunc(response, false);
            }
        }
    });
}

// ── Delete single vendor ──────────────────────────────────────────────────
function deleteVendor(DeleteId) {
    $.ajax({
        url   : '/vendors/deleteVendorData',
        method: 'POST',
        cache : false,
        data  : { VendorUID: DeleteId, PageNo: PageNo, [CsrfName]: CsrfToken },
        success: function (response) {
            if (response.Error) {
                showAlertMessageSwal('error', '', response.Message);
            } else {
                showToastNotification(response.Message, 'success');
                $(ModulePag).html(response.Pagination);
                $(ModuleTable + ' tbody').html(response.List);
                updateVendorStats(response.Stats);
                executeTablePagnCommonFunc(response, true);
            }
        }
    });
}

// ── Delete multiple vendors ───────────────────────────────────────────────
function deleteMultipleVendors() {
    $.ajax({
        url   : '/vendors/deleteMultipleVendors',
        method: 'POST',
        cache : false,
        data  : { 'VendorUIDs[]': SelectedUIDs, PageNo: PageNo, [CsrfName]: CsrfToken },
        success: function (response) {
            if (response.Error) {
                showAlertMessageSwal('error', '', response.Message);
            } else {
                showToastNotification(response.Message, 'success');
                SelectedUIDs = [];
                $(ModulePag).html(response.Pagination);
                $(ModuleTable + ' tbody').html(response.List);
                updateVendorStats(response.Stats);
                executeTablePagnCommonFunc(response, true);
            }
        }
    });
}

// ── Add / Edit / Clone modal ──────────────────────────────────────────────

var _editVendorUID = 0;

function _smartDecimalV(val) {
    var n = parseFloat(val);
    if (isNaN(n)) return '0';
    return n === 0 ? '0' : String(parseFloat(n.toFixed(6)));
}

function openVendorModal(type, uid) {
    var titles = { add: 'Create Vendor', edit: 'Update Vendor', clone: 'Clone Vendor' };
    $('#VendorFormModalTitle').text(titles[type] || 'Vendor');
    $('#VendorModalForm').data('mode', type);
    _resetVendorModal();
    if (type === 'add') {
        $('#CustomerLinkingDiv').removeClass('d-none');
        searchCustomers('VM_Customers');
        $('#VendorFormModal').modal('show');
        return;
    }
    // edit / clone â€” fetch data first, show modal only after populated
    $.ajax({
        url: '/vendors/getVendorForModal/' + uid,
        method: 'GET', cache: false,
        success: function (response) {
            if (response.Error) {
                showAlertMessageSwal('error', '', response.Message || 'Failed to load vendor.');
                return;
            }
            _populateVendorModal(type, response);
            $('#VendorFormModal').modal('show');
        },
        error: function () {
            showAlertMessageSwal('error', '', 'Failed to load vendor.');
        }
    });
}

function _resetVendorModal() {
    _editVendorUID = 0; delBankDataFlag = 0; delBankData = []; hasRemovedStoredImage = false;
    $('#VendorModalForm')[0].reset();
    $('#VendorUID').val('');
    $('#bankDetailsBody').empty();
    $('#appendBankDetails').addClass('d-none');
    $('#bankDivider').addClass('d-none');
    $('#bankEmptyState').removeClass('d-none');
    resetAddrData();
    $('#CustomerLinkingDiv').addClass('d-none');
    $('#CustomerDiv').addClass('d-none');
    $('#ResetCustomerLinking').addClass('d-none');
    $('input[name="CustomerLinkingCheck"]').prop('checked', false);
    reinitDropzoneOne('#VendorFormModalBody #DropzoneOneBasic');
    initializeFlatPickr('#VM_CPDateOfBirth', '#VendorFormModal');
}

function _populateVendorModal(type, response) {
    var d = response.Data; var isClone = (type === 'clone');
    _editVendorUID = isClone ? 0 : (d.VendorUID || 0);
    $('#VM_Name').val(d.Name || '');
    $('#VM_Area').val(d.Area || '');
    $('#VM_MobileNumber').val(d.MobileNumber || '');
    $('#VM_CountryCode').val(d.CountryCode || '');
    $('#VM_CountryISO2').val(d.CountryISO2 || '');
    $('#VM_EmailAddress').val(d.EmailAddress || '');
    $('#VM_DebitCreditAmount').val(_smartDecimalV(d.DebitCreditAmount));
    $('#VM_DebitCreditCheck').val(d.DebitCreditType || 'Credit').trigger('change');
    $('#VM_PANNumber').val(d.PANNumber || '');
    $('#VM_ContactPerson').val(d.ContactPerson || '');
    $('#VM_CPDateOfBirth').val(d.DateOfBirth || '');
    $('#VM_GSTIN').val(d.GSTIN || '');
    $('#VM_CompanyName').val(d.CompanyName || '');
    $('#VM_Notes').val(d.Notes || '');
    $('#CustomerLinkingDiv').addClass('d-none');
    if (d.Image && !isClone) commonSetDropzoneImageOne(CDN_URL + d.Image);
    if (response.BankDetails && response.BankDetails.length) {
        response.BankDetails.forEach(function (b) { appendBankRowToTable(b); });
        $('#bankEmptyState').addClass('d-none');
        $('#appendBankDetails').removeClass('d-none');
        $('#bankDivider').removeClass('d-none');
    }
    if (response.BillingAddr) {
        var ba = response.BillingAddr;
        billingAddrData = { UID: isClone?0:(ba.VendAddressUID||0), Line1:ba.Line1||'', Line2:ba.Line2||'', Pincode:ba.Pincode||'', StateId:ba.State||'', StateName:ba.StateText||'', StateISO2:'', CityId:ba.City||'', CityName:ba.CityText||'' };
        renderAddrSummary(1, billingAddrData);
    }
    if (response.ShippingAddr) {
        var sa = response.ShippingAddr;
        shippingAddrData = { UID: isClone?0:(sa.VendAddressUID||0), Line1:sa.Line1||'', Line2:sa.Line2||'', Pincode:sa.Pincode||'', StateId:sa.State||'', StateName:sa.StateText||'', StateISO2:'', CityId:sa.City||'', CityName:sa.CityText||'' };
        renderAddrSummary(2, shippingAddrData);
    }
    _updateCopyButtons();
}

// ── Save button in modal header ───────────────────────────────────────────
$(document).on('click', '#VendorFormSaveBtn', function () {
    $('#VendorModalForm').submit();
});

// ── Modal form submit ─────────────────────────────────────────────────────
$(document).on('submit', '#VendorModalForm', function (e) {
    e.preventDefault();

    var mode = $(this).data('mode');

    var mobileValue      = $('#VM_MobileNumber').val();
    var countryCode      = ($('#VM_CountryCode').val() || '91').replace('+', '');
    var mobileValidation = validateMobileNumber(mobileValue, countryCode);
    if (!mobileValidation.isValid) { showAlertMessageSwal('error', '', mobileValidation.message); return; }

    var panValidation = validatePANNumber($('#VM_PANNumber').val());
    if (!panValidation.isValid) { showAlertMessageSwal('error', '', panValidation.message); return; }

    var gstinValidation = validateGSTIN($('#VM_GSTIN').val());
    if (!gstinValidation.isValid) { showAlertMessageSwal('error', '', gstinValidation.message); return; }

    var formData = new FormData($('#VendorModalForm')[0]);
    if (mode === 'edit') { formData.set('VendorUID', _editVendorUID || 0); }

    if (mode === 'edit' && hasRemovedStoredImage && myOneDropzone && myOneDropzone.files.length === 0) {
        formData.append('ImageRemoved', 1);
    }
    if (myOneDropzone && myOneDropzone.files.length > 0 && !myOneDropzone.files[0].isStored) {
        formData.append('UploadImage', myOneDropzone.files[0]);
    }

    var bankRecords = getBankRecordsFromTable();
    var bankValid   = validateBankRecords(bankRecords);
    if (!bankValid.ok) { showAlertMessageSwal('error', '', bankValid.msg); return; }
    formData.append('BankDetailsJSON', JSON.stringify(bankRecords));
    formData.append('BankDetailsCount', String(bankRecords.length));

    if (delBankDataFlag) {
        formData.append('delBankDataFlag', delBankDataFlag);
        delBankData.forEach(function (id) { formData.append('delBankData[]', id); });
    }

    if (billingAddrData) {
        formData.append('BillAddressUID',    billingAddrData.UID      || 0);
        formData.append('BillAddrLine1',     billingAddrData.Line1    || '');
        formData.append('BillAddrLine2',     billingAddrData.Line2    || '');
        formData.append('BillAddrPincode',   billingAddrData.Pincode  || '');
        formData.append('BillAddrState',     billingAddrData.StateId  || '');
        formData.append('BillAddrStateText', billingAddrData.StateName|| '');
        formData.append('BillAddrCity',      billingAddrData.CityId   || '');
        formData.append('BillAddrCityText',  billingAddrData.CityName || '');
    }
    if (shippingAddrData) {
        formData.append('ShipAddressUID',    shippingAddrData.UID      || 0);
        formData.append('ShipAddrLine1',     shippingAddrData.Line1    || '');
        formData.append('ShipAddrLine2',     shippingAddrData.Line2    || '');
        formData.append('ShipAddrPincode',   shippingAddrData.Pincode  || '');
        formData.append('ShipAddrState',     shippingAddrData.StateId  || '');
        formData.append('ShipAddrStateText', shippingAddrData.StateName|| '');
        formData.append('ShipAddrCity',      shippingAddrData.CityId   || '');
        formData.append('ShipAddrCityText',  shippingAddrData.CityName || '');
    }
    if (delAddrDetailFlag) {
        formData.append('delAddrDetailFlag', delAddrDetailFlag);
        delAddrData.forEach(function (id) { formData.append('delAddrData[]', id); });
    }

    $('#VendorFormSaveBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Saving...');

    if (mode === 'edit') {
        formData.append('PageNo', PageNo);
        editVendorData(formData);
    } else {
        addVendorData(formData);
    }
});

// ── Open modal triggers ───────────────────────────────────────────────────
$(document).on('click', '#btnCreateVendor', function () {
    openVendorModal('add');
});

$(document).on('click', '.vend-edit-btn', function () {
    openVendorModal('edit', $(this).data('uid'));
});

$(document).on('click', '.vend-clone-btn', function () {
    openVendorModal('clone', $(this).data('uid'));
});

// ── Customer linking (modal context) ─────────────────────────────────────
$(document).on('change', 'input[name="CustomerLinkingCheck"]', function () {
    var inModal = $('#VendorFormModal').hasClass('show');
    var $custDiv    = $('#CustomerDiv');
    var $custSelect = inModal ? $('#VM_Customers') : $('#Customers');

    $custDiv.addClass('d-none');
    $custSelect.prop('required', false);

    if ($(this).val() === 'OldCustomer') {
        $custDiv.removeClass('d-none');
        $custSelect.prop('required', true);
        if (inModal && !$('#VM_Customers').data('select2')) {
            searchCustomers('VM_Customers');
        }
    }
    $('#ResetCustomerLinking').removeClass('d-none');
});

$(document).on('click', '#ResetCustomerLinking', function () {
    $(this).addClass('d-none');
    $('input[name="CustomerLinkingCheck"]').prop('checked', false);
    $('#CustomerDiv').addClass('d-none');
    var inModal = $('#VendorFormModal').hasClass('show');
    (inModal ? $('#VM_Customers') : $('#Customers')).prop('required', false);
});

// ── Communication: single send ────────────────────────────────────────────
$(document).on('click', '.comm-send-single', function () {
    var $btn = $(this);
    openCommModal(
        $btn.data('commtype'),
        $btn.data('recipienttype'),
        [$btn.data('uid')],
        [$btn.data('name') || ''],
        [$btn.data('mobile') || ''],
        [$btn.data('email') || '']
    );
});

// ── Communication: bulk show/hide ─────────────────────────────────────────
function _updateBulkCommOptions() {
    var checked = $('.vendorsCheck:checked').length > 0;
    $('#BulkSmsOption').toggleClass('d-none', !checked);
    $('#BulkEmailOption').toggleClass('d-none', !checked);
}

$(document).on('change', '.vendorsCheck', function () {
    _updateBulkCommOptions();
});

// ── Bulk SMS ──────────────────────────────────────────────────────────────
$(document).on('click', '#btnBulkSms', function () {
    var uids = [], names = [], mobiles = [], emails = [];
    $('.vendorsCheck:checked').each(function () {
        var $row = $(this).closest('tr');
        var $ref = $row.find('.comm-send-single[data-commtype="Email"]');
        if (!$ref.length) $ref = $row.find('.comm-send-single');
        uids.push($(this).val());
        names.push($ref.data('name') || '');
        mobiles.push($ref.data('mobile') || '');
        emails.push($ref.data('email') || '');
    });
    if (uids.length) openCommModal('SMS', 'Vendor', uids, names, mobiles, emails);
});

// ── Bulk Email ────────────────────────────────────────────────────────────
$(document).on('click', '#btnBulkEmail', function () {
    var uids = [], names = [], mobiles = [], emails = [];
    $('.vendorsCheck:checked').each(function () {
        var $row = $(this).closest('tr');
        var $ref = $row.find('.comm-send-single[data-commtype="Email"]');
        if (!$ref.length) $ref = $row.find('.comm-send-single');
        uids.push($(this).val());
        names.push($ref.data('name') || '');
        mobiles.push($ref.data('mobile') || '');
        emails.push($ref.data('email') || '');
    });
    if (uids.length) openCommModal('Email', 'Vendor', uids, names, mobiles, emails);
});

// ── Init vendor modal plugins once on page load ───────────────────────────────
$(function () {
    initializeFlatPickr('#VM_CPDateOfBirth', '#VendorFormModal');
    reinitDropzoneOne('#VendorFormModalBody #DropzoneOneBasic');
});