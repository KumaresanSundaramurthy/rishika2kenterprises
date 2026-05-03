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
                if ($('#VendorFormModal').hasClass('show')) {
                    $('#VendorFormModal').modal('hide');
                    if (typeof getVendorsDetails === 'function') getVendorsDetails(PageNo, RowLimit, Filter);
                } else {
                    $('#AddVendorForm').trigger('reset');
                    setTimeout(function () { window.history.back(); }, 250);
                }
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
                if ($('#VendorFormModal').hasClass('show')) {
                    $('#VendorFormModal').modal('hide');
                    if (typeof getVendorsDetails === 'function') getVendorsDetails(PageNo, RowLimit, Filter);
                } else {
                    $('#EditVendorForm').trigger('reset');
                    setTimeout(function () { window.history.back(); }, 250);
                }
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
        data  : { VendorUID: VendorUID, IsActive: IsActive, [CsrfName]: CsrfToken },
        success: function (response) {
            if (response.Error) {
                showAlertMessageSwal('error', '', response.Message);
            } else {
                showToastNotification(response.Message, 'success');
                updateVendorStats(response.Stats);
                getVendorsDetails(PageNo, RowLimit, Filter);
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
                $(ModulePag).html(response.Pagination);
                $(ModuleTable + ' tbody').html(response.List);
                SelectedUIDs = [];
                updateVendorStats(response.Stats);
            }
        }
    });
}

// ── Add / Edit / Clone modal ──────────────────────────────────────────────

function openVendorModal(type, uid) {
    var titles = { add: 'Create Vendor', edit: 'Update Vendor', clone: 'Clone Vendor' };
    $('#VendorFormModalTitle').text(titles[type] || 'Vendor');
    $('#VendorFormModalBody').html('<div class="text-center py-5"><span class="spinner-border text-primary"></span></div>');
    $('#VendorFormModal').modal('show');

    $.ajax({
        url: '/vendors/modal/' + type + (uid ? '/' + uid : ''),
        method: 'GET',
        cache: false,
        success: function (response) {
            if (response.Error) {
                $('#VendorFormModal').modal('hide');
                showAlertMessageSwal('error', '', response.Message || 'Failed to load form.');
                return;
            }
            window.StateInfo = response.StateData;
            window.CityInfo  = response.CityData;
            $('#VendorFormModalBody').html(response.Html);
            _initVendorModalPlugins(response);
        },
        error: function () {
            $('#VendorFormModal').modal('hide');
            showAlertMessageSwal('error', '', 'Failed to load form.');
        }
    });
}

function _initVendorModalPlugins(response) {
    delBankDataFlag   = 0;
    delBankData       = [];
    delAddrDetailFlag = 0;
    delAddrData       = [];
    hasRemovedStoredImage = false;

    initializeFlatPickr('#VM_CPDateOfBirth', '#VendorFormModal');

    reinitDropzoneOne('#VendorFormModalBody #DropzoneOneBasic');
    if (response.ImgData) {
        commonSetDropzoneImageOne(CDN_URL + response.ImgData);
    }

    if (response.FormMode !== 'edit') {
        searchCustomers('VM_Customers');
    }

    if (response.BillingAddr) {
        creationBilngAddrActions();
        var b = response.BillingAddr;
        $('#BillAddressUID').val(b.VendAddressUID || 0);
        $('#BillAddrLine1').val(b.Line1 || '');
        $('#BillAddrLine2').val(b.Line2 || '');
        $('#BillAddrPincode').val(b.Pincode || '');
        $('#BillAddrState').val(b.State || '').trigger('change');
        setTimeout(function () { $('#BillAddrCity').val(b.City || '').trigger('change'); }, 300);
    }
    if (response.ShippingAddr) {
        creationShipAddrActions();
        var s = response.ShippingAddr;
        $('#ShipAddressUID').val(s.VendAddressUID || 0);
        $('#ShipAddrLine1').val(s.Line1 || '');
        $('#ShipAddrLine2').val(s.Line2 || '');
        $('#ShipAddrPincode').val(s.Pincode || '');
        $('#ShipAddrState').val(s.State || '').trigger('change');
        setTimeout(function () { $('#ShipAddrCity').val(s.City || '').trigger('change'); }, 300);
    }
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

    var billLine1 = $('#BillAddrLine1').val();
    if (hasValue(billLine1)) {
        var bc = $('#BillAddrCity').find('option:selected');
        var bs = $('#BillAddrState').find('option:selected');
        if (hasValue(bc.val()) && $.isNumeric(bc.val())) formData.append('BillAddrCityText', bc.text());
        if (hasValue(bs.val()) && $.isNumeric(bs.val())) formData.append('BillAddrStateText', bs.text());
    }
    var shipLine1 = $('#ShipAddrLine1').val();
    if (hasValue(shipLine1)) {
        var sc = $('#ShipAddrCity').find('option:selected');
        var ss = $('#ShipAddrState').find('option:selected');
        if (hasValue(sc.val()) && $.isNumeric(sc.val())) formData.append('ShipAddrCityText', sc.text());
        if (hasValue(ss.val()) && $.isNumeric(ss.val())) formData.append('ShipAddrStateText', ss.text());
    }
    if (delAddrDetailFlag) {
        formData.append('delAddrDetailFlag', delAddrDetailFlag);
        delAddrData.forEach(function (id) { formData.append('delAddrData[]', id); });
    }

    $('#VendorFormSaveBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Saving...');

    if (mode === 'edit') {
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
