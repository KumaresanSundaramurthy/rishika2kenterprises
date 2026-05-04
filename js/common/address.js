// ── In-memory cache — per country for states, per state ISO2 for cities ──────
window._stateCache = window._stateCache || {};
window._cityCache  = window._cityCache  || {};

// ── Address data variables (reset per customer/vendor modal session) ───────
var billingAddrData  = null;
var shippingAddrData = null;
var delAddrDetailFlag = 0;
var delAddrData       = [];

function resetAddrData() {
    billingAddrData   = null;
    shippingAddrData  = null;
    delAddrDetailFlag = 0;
    delAddrData       = [];
    $('#appendBillingAddress').empty();
    $('#appendShippingAddress').empty();
    $('#addBillingAddress').removeClass('d-none');
    $('#addShippingAddress').removeClass('d-none');
    $('#copyToShippingBtn').addClass('d-none');
    $('#copyToBillingBtn').addClass('d-none');
}

// ── Populate state dropdown ───────────────────────────────────────────────
function csc_loadStates(selectId, countryISO2, selectedVal, onDone) {
    var iso2 = (countryISO2 || 'IN').toUpperCase();

    function _render(states) {
        var $sel = $('#' + selectId);
        $sel.empty().append('<option value="">-- Select State --</option>');
        states.forEach(function (s) {
            $sel.append(
                $('<option></option>').val(s.id).text(s.name).attr('data-iso2', s.iso2 || '')
            );
        });
        if ($sel.hasClass('select2'))
            $sel.select2({
                width: '100%',
                dropdownParent: $('#AddEditAddressForm .modal-body'),
            });
        if (selectedVal) $sel.val(String(selectedVal));
        if (typeof onDone === 'function') onDone();
    }

    if (window._stateCache[iso2]) { _render(window._stateCache[iso2]); return; }

    $.ajax({
        url: '/globally/getStateCityOfCountry', method: 'POST', data: { CountryCode: iso2 },
        success: function (resp) {
            window._stateCache[iso2] = (!resp.Error && resp.StateInfo) ? resp.StateInfo : [];
            _render(window._stateCache[iso2]);
        },
        error: function () { window._stateCache[iso2] = []; _render([]); }
    });
}

// ── Populate city dropdown per state (cached by state ISO2) ───────────────
function csc_loadCities(selectId, countryISO2, stateISO2, selectedVal) {
    var $sel = $('#' + selectId);
    if (!stateISO2) {
        $sel.empty().append('<option value="">-- Select City --</option>');
        if ($sel.hasClass('select2'))
            $sel.select2({
                width: '100%',
                dropdownParent: $('#AddEditAddressForm .modal-body'),
            });
        return;
    }
    var cISO2 = (countryISO2 || 'IN').toUpperCase();
    var sISO2 = stateISO2.toUpperCase();

    function _render(cities) {
        $sel.empty().append('<option value="">-- Select City --</option>');
        cities.forEach(function (c) {
            $sel.append($('<option></option>').val(c.id).text(c.name));
        });
        if ($sel.hasClass('select2'))
            $sel.select2({
                width: '100%',
                dropdownParent: $('#AddEditAddressForm .modal-body'),
            });
        if (selectedVal) $sel.val(String(selectedVal));
    }

    if (window._cityCache[sISO2] !== undefined) { _render(window._cityCache[sISO2]); return; }

    $sel.empty().append('<option value="">Loading cities...</option>');
    if ($sel.hasClass('select2'))
        $sel.select2({
            width: '100%',
            dropdownParent: $('#AddEditAddressForm .modal-body'),
        });

    $.ajax({
        url: '/globally/getCitiesOfState', method: 'POST',
        data: { CountryISO2: cISO2, StateISO2: sISO2 },
        success: function (resp) {
            window._cityCache[sISO2] = (!resp.Error && resp.Data) ? resp.Data : [];
            _render(window._cityCache[sISO2]);
        },
        error: function () { window._cityCache[sISO2] = []; _render([]); }
    });
}

// ── Open address modal ────────────────────────────────────────────────────
function openAddressModal(addrType) {
    var iso2     = typeof OrgCountryISO2 !== 'undefined' ? OrgCountryISO2 : 'IN';
    var isBilling = (addrType == 1);
    var existing  = isBilling ? billingAddrData : shippingAddrData;

    $('#addrModalTitle').text(isBilling ? 'Billing Address' : 'Shipping Address');
    $('#AddrType').val(addrType);
    $('#ModalAddrLine1').val('');
    $('#ModalAddrLine2').val('');
    $('#ModalAddrPincode').val('');
    $('#AddrUID').val(0);
    $('#ModalAddrCity').empty().append('<option value="">-- Select City --</option>');
    if ($('#ModalAddrCity').hasClass('select2')) $('#ModalAddrCity').select2({ width: '100%' });

    if (existing) {
        $('#AddrUID').val(existing.UID || 0);
        $('#ModalAddrLine1').val(existing.Line1 || '');
        $('#ModalAddrLine2').val(existing.Line2 || '');
        $('#ModalAddrPincode').val(existing.Pincode || '');

        csc_loadStates('ModalAddrState', iso2, existing.StateId || '', function () {
            var stateISO2 = existing.StateISO2 || $('#ModalAddrState').find('option:selected').data('iso2') || '';
            if (stateISO2) {
                csc_loadCities('ModalAddrCity', iso2, stateISO2, existing.CityId || '');
            }
        });
    } else {
        csc_loadStates('ModalAddrState', iso2, '');
    }

    $('#addEditAddressModal').modal('show');
}

// ── Render address summary card ───────────────────────────────────────────
function renderAddrSummary(addrType, data) {
    var divId    = addrType == 1 ? 'appendBillingAddress'  : 'appendShippingAddress';
    var addBtnId = addrType == 1 ? 'addBillingAddress'     : 'addShippingAddress';

    var lines = [];
    if (data.Line1) lines.push(data.Line1);
    if (data.Line2) lines.push(data.Line2);
    var loc = [data.CityName, data.StateName, data.Pincode].filter(Boolean).join(', ');
    if (loc) lines.push(loc);

    var textHtml = lines.map(function (l) {
        return '<div>' + $('<div>').text(l).html() + '</div>';
    }).join('');

    var html = '<div class="border rounded p-3 d-flex justify-content-between align-items-start gap-2">'
             +   '<div class="small text-muted lh-base">' + textHtml + '</div>'
             +   '<div class="d-flex gap-1 flex-shrink-0">'
             +     '<button type="button" class="btn btn-sm btn-outline-primary editAddrBtn" data-addrtype="' + addrType + '" title="Edit"><i class="bx bx-edit-alt"></i></button>'
             +     '<button type="button" class="btn btn-sm btn-outline-danger deleteAddrBtn" data-addrtype="' + addrType + '" title="Delete"><i class="bx bx-trash"></i></button>'
             +   '</div>'
             + '</div>';

    $('#' + divId).html(html);
    $('#' + addBtnId).addClass('d-none');
}

// ── Modal state change → load cities ─────────────────────────────────────
$(document).on('change', '#ModalAddrState', function () {
    var iso2      = typeof OrgCountryISO2 !== 'undefined' ? OrgCountryISO2 : 'IN';
    var stateISO2 = $(this).find('option:selected').data('iso2') || '';
    csc_loadCities('ModalAddrCity', iso2, stateISO2, '');
});

// ── Address modal form submit ─────────────────────────────────────────────
$(document).on('submit', '#AddEditAddressForm', function (e) {
    e.preventDefault();

    var line1 = $.trim($('#ModalAddrLine1').val());
    if (!line1) { showAlertMessageSwal('error', '', 'Address Line 1 is required.'); return; }

    var pincode = $.trim($('#ModalAddrPincode').val());
    if (!pincode) { showAlertMessageSwal('error', '', 'Pincode is required.'); return; }

    var $state    = $('#ModalAddrState').find('option:selected');
    var $city     = $('#ModalAddrCity').find('option:selected');
    var stateISO2 = $state.data('iso2') || '';

    var data = {
        UID      : parseInt($('#AddrUID').val()) || 0,
        Line1    : line1,
        Line2    : $.trim($('#ModalAddrLine2').val()),
        Pincode  : pincode,
        StateId  : $state.val() || '',
        StateName: ($state.val() && $state.text() !== '-- Select State --') ? $state.text() : '',
        StateISO2: stateISO2,
        CityId   : $city.val()  || '',
        CityName : ($city.val()  && $city.text()  !== '-- Select City --')  ? $city.text()  : ''
    };

    var addrType = parseInt($('#AddrType').val());
    if (addrType === 1) { billingAddrData  = data; }
    else                { shippingAddrData = data; }

    renderAddrSummary(addrType, data);
    _updateCopyButtons();
    $('#addEditAddressModal').modal('hide');
});

// ── Edit button in summary card ───────────────────────────────────────────
$(document).on('click', '.editAddrBtn', function () {
    openAddressModal(parseInt($(this).data('addrtype')));
});

// ── Delete button in summary card ─────────────────────────────────────────
$(document).on('click', '.deleteAddrBtn', function () {
    var addrType  = parseInt($(this).data('addrtype'));
    var existing  = addrType === 1 ? billingAddrData : shippingAddrData;
    var divId     = addrType === 1 ? 'appendBillingAddress'  : 'appendShippingAddress';
    var addBtnId  = addrType === 1 ? 'addBillingAddress'     : 'addShippingAddress';

    if (existing && existing.UID > 0) {
        delAddrDetailFlag = 1;
        delAddrData.push(existing.UID);
    }
    if (addrType === 1) { billingAddrData  = null; }
    else                { shippingAddrData = null; }

    $('#' + divId).empty();
    $('#' + addBtnId).removeClass('d-none');
    _updateCopyButtons();
});

// ── Copy button visibility ───────────────────────────────────────────────
function _updateCopyButtons() {
    var hasBill = billingAddrData  !== null;
    var hasShip = shippingAddrData !== null;
    // Both present — hide both copy buttons
    if (hasBill && hasShip) {
        $('#copyToShippingBtn').addClass('d-none');
        $('#copyToBillingBtn').addClass('d-none');
    } else {
        // Billing added, shipping not — show "Copy to Shipping" on billing side
        $('#copyToShippingBtn').toggleClass('d-none', !hasBill);
        // Shipping added, billing not — show "Copy to Billing" on shipping side
        $('#copyToBillingBtn').toggleClass('d-none', !hasShip);
    }
}

// ── Copy to Shipping ──────────────────────────────────────────────────────
$(document).on('click', '#copyToShippingBtn', function () {
    if (!billingAddrData) return;
    shippingAddrData = $.extend({}, billingAddrData, { UID: 0 });
    renderAddrSummary(2, shippingAddrData);
    _updateCopyButtons();
});

// ── Copy to Billing ───────────────────────────────────────────────────────
$(document).on('click', '#copyToBillingBtn', function () {
    if (!shippingAddrData) return;
    billingAddrData = $.extend({}, shippingAddrData, { UID: 0 });
    renderAddrSummary(1, billingAddrData);
    _updateCopyButtons();
});

// ── Add button click — open modal ─────────────────────────────────────────
$(document).on('click', '#addBillingAddress',  function (e) { e.preventDefault(); openAddressModal(1); });
$(document).on('click', '#addShippingAddress', function (e) { e.preventDefault(); openAddressModal(2); });
