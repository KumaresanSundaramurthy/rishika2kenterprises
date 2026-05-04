/**
 * country_state_city.js
 * Handles dynamic State and City dropdowns for address forms.
 *
 * Public API:
 *   csc_loadStates(selectId, countryISO2, selectedVal)
 *   csc_loadCities(selectId, countryISO2, stateISO2, selectedVal)
 */

// ── Cache: avoid duplicate AJAX calls ────────────────────────────────────────
var _cscStateCache = {};
var _cscCityCache  = {};

// ── Load states into a select ─────────────────────────────────────────────────
function csc_loadStates(selectId, countryISO2, selectedVal, callback) {
    var $sel = $('#' + selectId);
    if (!$sel.length || !countryISO2) return;

    if (_cscStateCache[countryISO2]) {
        _cscPopulateStates($sel, _cscStateCache[countryISO2], selectedVal);
        if (typeof callback === 'function') callback();
        return;
    }

    $sel.html('<option value="">Loading...</option>');
    $.ajax({
        url   : '/globally/getStateofCountry',
        method: 'POST',
        data  : { CountryCode: countryISO2 },
        success: function (resp) {
            var states = (!resp.Error && resp.Data) ? resp.Data : [];
            _cscStateCache[countryISO2] = states;
            _cscPopulateStates($sel, states, selectedVal);
            if (typeof callback === 'function') callback();
        },
        error: function () {
            $sel.html('<option value="">-- Select State --</option>');
        }
    });
}

function _cscPopulateStates($sel, states, selectedVal) {
    var html = '<option value="">-- Select State --</option>';
    states.forEach(function (s) {
        var sel = (selectedVal && String(s.id) === String(selectedVal)) ? ' selected' : '';
        html += '<option value="' + s.id + '" data-iso2="' + s.iso2 + '"' + sel + '>' + s.name + '</option>';
    });
    $sel.html(html);
    if (typeof $.fn.select2 !== 'undefined') $sel.trigger('change.select2');
}

// ── Load cities into a select ─────────────────────────────────────────────────
function csc_loadCities(selectId, countryISO2, stateISO2, selectedVal) {
    var $sel = $('#' + selectId);
    if (!$sel.length || !countryISO2 || !stateISO2) return;

    var cacheKey = countryISO2 + '_' + stateISO2;
    if (_cscCityCache[cacheKey]) {
        _cscPopulateCities($sel, _cscCityCache[cacheKey], selectedVal);
        return;
    }

    $sel.html('<option value="">Loading...</option>');
    $.ajax({
        url   : '/globally/getCitiesOfState',
        method: 'POST',
        data  : { CountryISO2: countryISO2, StateISO2: stateISO2 },
        success: function (resp) {
            var cities = (!resp.Error && resp.Data) ? resp.Data : [];
            _cscCityCache[cacheKey] = cities;
            _cscPopulateCities($sel, cities, selectedVal);
        },
        error: function () {
            $sel.html('<option value="">-- Select City --</option>');
        }
    });
}

function _cscPopulateCities($sel, cities, selectedVal) {
    var html = '<option value="">-- Select City --</option>';
    cities.forEach(function (c) {
        var sel = (selectedVal && String(c.id) === String(selectedVal)) ? ' selected' : '';
        html += '<option value="' + c.id + '"' + sel + '>' + c.name + '</option>';
    });
    $sel.html(html);
    if (typeof $.fn.select2 !== 'undefined') $sel.trigger('change.select2');
}

// ── State change → reload cities ──────────────────────────────────────────────
$(document).on('change', '#BillAddrState', function () {
    var iso2       = $(this).find('option:selected').data('iso2');
    var countryISO2 = typeof OrgCountryISO2 !== 'undefined' ? OrgCountryISO2 : 'IN';
    $('#BillAddrCity').html('<option value="">-- Select City --</option>');
    if (iso2) csc_loadCities('BillAddrCity', countryISO2, iso2, '');
});

$(document).on('change', '#ShipAddrState', function () {
    var iso2       = $(this).find('option:selected').data('iso2');
    var countryISO2 = typeof OrgCountryISO2 !== 'undefined' ? OrgCountryISO2 : 'IN';
    $('#ShipAddrCity').html('<option value="">-- Select City --</option>');
    if (iso2) csc_loadCities('ShipAddrCity', countryISO2, iso2, '');
});
