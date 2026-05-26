// ── Export ────────────────────────────────────────────────────────────────

function custExport(type) {
    var url = '/customers/exportCustomers?Type=' + encodeURIComponent(type);
    if (typeof Filter !== 'undefined' && !$.isEmptyObject(Filter)) {
        url += '&Filter=' + encodeURIComponent(JSON.stringify(Filter));
    }
    if (type === 'Print') {
        printPreviewRecords(url, function () {});
    } else {
        window.location.href = url;
    }
}

// ── List page AJAX functions ──────────────────────────────────────────────

function _smartDecimal(val) {
    var n = parseFloat(val);
    if (isNaN(n)) return '0';
    return n === 0 ? '0' : String(parseFloat(n.toFixed(6)));
}

function getCustomersDetails(PageNo, RowLimit, Filter) {
    $.ajax({
        url: '/customers/getCustomersPageDetails/' + PageNo,
        method: 'POST',
        cache: false,
        data: { RowLimit: RowLimit, Filter: Filter },
        success: function (response) {
            if (response.Error) {
                $(ModuleTable + ' tbody').html('');
                $(ModulePag).html('<div class="alert alert-danger" role="alert"><strong>' + response.Message + '</strong></div>');
            } else {
                $(ModulePag).html(response.Pagination);
                $(ModuleTable + ' tbody').html(response.RecordHtmlData);
                // Keep sticky pagination in sync with the updated static one
                $('#custStickyPagination .CustomersPagination').html(response.Pagination);
                $(window).trigger('scroll');
            }
            executeTablePagnCommonFunc(response, false);
        },
    });
}

function addCustomerData(formdata) {
    $.ajax({
        url: '/customers/addCustomerData',
        method: 'POST',
        data: formdata,
        cache: false,
        processData: false,
        contentType: false,
        enctype: 'multipart/form-data',
        success: function (response) {
            $('#CustomerFormSaveBtn').prop('disabled', false).html('<i class="bx bx-check me-1"></i>Save');
            if (response.Error) {
                showAlertMessageSwal('error', '', response.Message);
            } else {
                showToastNotification(response.Message, 'success');
                $('#CustomerFormModal').modal('hide');
                $(ModulePag).html(response.Pagination);
                $(ModuleTable + ' tbody').html(response.List);
                updateCustomerStats(response.Stats);
                executeTablePagnCommonFunc(response, false);
            }
        }
    });
}

function editCustomerData(formdata) {
    $.ajax({
        url: '/customers/updateCustomerData',
        method: 'POST',
        data: formdata,
        cache: false,
        processData: false,
        contentType: false,
        enctype: 'multipart/form-data',
        success: function (response) {
            $('#CustomerFormSaveBtn').prop('disabled', false).html('<i class="bx bx-check me-1"></i>Save');
            if (response.Error) {
                showAlertMessageSwal('error', '', response.Message);
            } else {
                showToastNotification(response.Message, 'success');
                $('#CustomerFormModal').modal('hide');
                $(ModulePag).html(response.Pagination);
                $(ModuleTable + ' tbody').html(response.List);
                updateCustomerStats(response.Stats);
                executeTablePagnCommonFunc(response, false);
            }
        }
    });
}

function deleteCustomer(DeleteId) {
    $.ajax({
        url: '/customers/deleteCustomerData',
        method: 'POST',
        data: { RowLimit: RowLimit, PageNo: PageNo, Filter: Filter, CustomerUID: DeleteId, ModuleId: ModuleId },
        cache: false,
        success: function (response) {
            if (response.Error) {
                showAlertMessageSwal('error', '', response.Message);
            } else {
                showToastNotification(response.Message, 'success');
                $(ModulePag).html(response.Pagination);
                $(ModuleTable + ' tbody').html(response.List);
                updateCustomerStats(response.Stats);
            }
            executeTablePagnCommonFunc(response, true);
        }
    });
}

function deleteMultipleCustomers() {
    $.ajax({
        url: '/customers/deleteBulkCustomers',
        method: 'POST',
        cache: false,
        data: { RowLimit: RowLimit, PageNo: PageNo, Filter: Filter, CustomerUIDs: SelectedUIDs, ModuleId: ModuleId },
        success: function (response) {
            if (response.Error) {
                showAlertMessageSwal('error', '', response.Message);
            } else {
                showToastNotification(response.Message, 'success');
                $(ModulePag).html(response.Pagination);
                $(ModuleTable + ' tbody').html(response.List);
                SelectedUIDs = [];
                updateCustomerStats(response.Stats);
                executeTablePagnCommonFunc(response, true);
            }
        },
    });
}

function toggleCustomerStatus(CustomerUID, IsActive) {
    $.ajax({
        url: '/customers/toggleCustomerStatus',
        method: 'POST',
        cache: false,
        data: { CustomerUID: CustomerUID, IsActive: IsActive, PageNo: PageNo, [CsrfName]: CsrfToken },
        success: function (response) {
            if (response.Error) {
                showAlertMessageSwal('error', '', response.Message);
            } else {
                showToastNotification(response.Message, 'success');
                $(ModulePag).html(response.Pagination);
                $(ModuleTable + ' tbody').html(response.List);
                updateCustomerStats(response.Stats);
                executeTablePagnCommonFunc(response, false);
            }
        }
    });
}

function updateCustomerStats(stats) {
    if (!stats) return;
    var s = stats;
    $('.cust-stat-total').text(Number(s.TotalCount || 0).toLocaleString());
    $('.cust-stat-active').text(Number(s.ActiveCount || 0).toLocaleString());
    $('.cust-stat-month').text(Number(s.MonthCount || 0).toLocaleString());
    $('.cust-stat-fy').text(Number(s.FYCount || 0).toLocaleString());
    $('.cust-stat-lastmonth').text(Number(s.LastMonthCount || 0).toLocaleString());
}

// ── Add / Edit / Clone modal ──────────────────────────────────────────────

var _editCustomerUID = 0;

function openCustomerModal(type, uid) {
    var titles = { add: 'Create Customer', edit: 'Update Customer', clone: 'Clone Customer' };
    $('#CustomerFormModalTitle').text(titles[type] || 'Customer');
    $('#CustomerModalForm').data('mode', type);

    _resetCustomerModal();

    if (type === 'add') {
        $('#CustomerFormModal').modal('show');
        return;
    }

    // edit / clone — fetch data first, show modal only after populated
    $.ajax({
        url: '/customers/getCustomerForModal/' + uid,
        method: 'GET',
        cache: false,
        success: function (response) {
            if (response.Error) {
                showAlertMessageSwal('error', '', response.Message || 'Failed to load customer.');
                return;
            }
            _populateCustomerModal(type, response);
            $('#CustomerFormModal').modal('show');
        },
        error: function () {
            showAlertMessageSwal('error', '', 'Failed to load customer.');
        }
    });
}

function _resetCustomerModal() {
    _editCustomerUID  = 0;
    delBankDataFlag   = 0;
    delBankData       = [];
    hasRemovedStoredImage = false;

    // Reset form fields
    var $form = $('#CustomerModalForm');
    $form[0].reset();
    $('#CustomerUID').val('');

    // Reset bank table
    $('#bankDetailsBody').empty();
    $('#appendBankDetails').addClass('d-none');
    $('#bankDivider').addClass('d-none');

    // Reset address
    resetAddrData();

    // Reset dropzone
    reinitDropzoneOne('#CustomerFormModalBody #DropzoneOneBasic');

    // Re-init plugins
    initializeFlatPickr('#CM_CPDateOfBirth', '#CustomerFormModal');
    initializeSelect2Tags('#CM_Tags', 'Type and press enter...');
    initializeSelect2Tags('#CM_CCEmails', 'Type and press enter...');
}

function _populateCustomerModal(type, response) {
    var d = response.Data;
    var isClone = (type === 'clone');

    if (!isClone) {
        _editCustomerUID = d.CustomerUID || 0;
    } else {
        _editCustomerUID = 0;
    }

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
        commonSetDropzoneImageOne(CDN_URL + d.Image);
    }

    // Bank details
    if (response.BankDetails && response.BankDetails.length) {
        response.BankDetails.forEach(function (b) {
            appendBankRowToTable(b);
        });
        $('#appendBankDetails').removeClass('d-none');
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
        renderAddrSummary(1, billingAddrData);
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
        renderAddrSummary(2, shippingAddrData);
    }
    _updateCopyButtons();
}

// ── Save button in modal header ───────────────────────────────────────────
$(document).on('click', '#CustomerFormSaveBtn', function () {
    $('#CustomerModalForm').submit();
});

// ── Modal form submit ─────────────────────────────────────────────────────
$(document).on('submit', '#CustomerModalForm', function (e) {
    e.preventDefault();

    var mode = $(this).data('mode');

    var mobileValue      = $('#CM_MobileNumber').val();
    var countryCode      = ($('#CM_CountryCode').val() || '91').replace('+', '');
    var mobileValidation = validateMobileNumber(mobileValue, countryCode);
    if (!mobileValidation.isValid) { showAlertMessageSwal('error', '', mobileValidation.message); return; }

    var panValidation = validatePANNumber($('#CM_PANNumber').val());
    if (!panValidation.isValid) { showAlertMessageSwal('error', '', panValidation.message); return; }

    var gstinValidation = validateGSTIN($('#CM_GSTIN').val());
    if (!gstinValidation.isValid) { showAlertMessageSwal('error', '', gstinValidation.message); return; }

    var formData = new FormData($('#CustomerModalForm')[0]);

    // Ensure CustomerUID is included for edit mode
    if (mode === 'edit') {
        formData.set('CustomerUID', _editCustomerUID || 0);
    }

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

    $('#CustomerFormSaveBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Saving...');

    if (mode === 'edit') {
        formData.append('PageNo', PageNo);
        editCustomerData(formData);
    } else {
        formData.append('transCustomer', 0);
        addCustomerData(formData);
    }
});

// ── Open modal triggers ───────────────────────────────────────────────────
$(document).on('click', '.cust-edit-btn', function () {
    openCustomerModal('edit', $(this).data('uid'));
});

$(document).on('click', '.cust-clone-btn', function () {
    openCustomerModal('clone', $(this).data('uid'));
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
    var checked = $('.customerCheck:checked').length > 0;
    $('#BulkSmsOption').toggleClass('d-none', !checked);
    $('#BulkEmailOption').toggleClass('d-none', !checked);
}

$(document).on('change', '.customerCheck', function () {
    _updateBulkCommOptions();
});

$(document).on('click', '#btnBulkSms', function () {
    var uids = [], names = [], mobiles = [], emails = [];
    $('.customerCheck:checked').each(function () {
        var $row = $(this).closest('tr');
        var $ref = $row.find('.comm-send-single[data-commtype="Email"]');
        if (!$ref.length) $ref = $row.find('.comm-send-single');
        uids.push($(this).val());
        names.push($ref.data('name') || '');
        mobiles.push($ref.data('mobile') || '');
        emails.push($ref.data('email') || '');
    });
    if (uids.length) openCommModal('SMS', 'Customer', uids, names, mobiles, emails);
});

$(document).on('click', '#btnBulkEmail', function () {
    var uids = [], names = [], mobiles = [], emails = [];
    $('.customerCheck:checked').each(function () {
        var $row = $(this).closest('tr');
        var $ref = $row.find('.comm-send-single[data-commtype="Email"]');
        if (!$ref.length) $ref = $row.find('.comm-send-single');
        uids.push($(this).val());
        names.push($ref.data('name') || '');
        mobiles.push($ref.data('mobile') || '');
        emails.push($ref.data('email') || '');
    });
    if (uids.length) openCommModal('Email', 'Customer', uids, names, mobiles, emails);
});

// ── Init modal plugins once on page load ─────────────────────────────────────
$(function () {
    initializeFlatPickr('#CM_CPDateOfBirth', '#CustomerFormModal');
    initializeSelect2Tags('#CM_Tags', 'Type and press enter...');
    initializeSelect2Tags('#CM_CCEmails', 'Type and press enter...');
    reinitDropzoneOne('#CustomerFormModalBody #DropzoneOneBasic');
});

