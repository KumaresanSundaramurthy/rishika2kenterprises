// ── Session-level in-memory cache ─────────────────────────────────────────────
var _orgStateLoaded = false;
var _orgCityCache   = {};   // keyed by "{country_lower}-{state_lower}"

// ── Open Select2 and scroll results list to the currently selected option ─────
function _openAndScroll($sel) {
    $sel.one('select2:open', function () {
        requestAnimationFrame(function () {
            var $opt = $('.select2-dropdown .select2-results__option[aria-selected="true"]');
            if ($opt.length) $opt[0].scrollIntoView({ block: 'nearest' });
        });
    });
    $sel.select2('open');
}

// ── Render states into BillAddrState + ShipAddrState ──────────────────────────
function _renderOrgStates(states) {
    _orgStateLoaded = true;
    $('#BillAddrState, #ShipAddrState').each(function () {
        var $sel = $(this);
        var prev = $sel.val();
        $sel.empty().append('<option value="">-- Select State --</option>');
        states.forEach(function (s) {
            $sel.append($('<option>').val(s.id).text(s.name).attr('data-iso2', s.iso2 || ''));
        });
        if (prev) $sel.val(prev).trigger('change.select2');
    });
}

// ── Render cities into a single city select ────────────────────────────────────
function _renderOrgCities($selCity, cities, selectedVal) {
    $selCity.empty().append('<option value="">-- Select City --</option>');
    cities.forEach(function (c) {
        $selCity.append($('<option>').val(c.id).text(c.name));
    });
    if (selectedVal) $selCity.val(String(selectedVal)).trigger('change.select2');
    else $selCity.val(null).trigger('change');
}

// ── Load states: Upstash → AJAX fallback ──────────────────────────────────────
function _loadOrgStates(onDone) {
    var iso2  = (typeof defaultIso2 !== 'undefined' ? defaultIso2 : 'IN').toUpperCase();
    var liso2 = iso2.toLowerCase();

    UpstashService.get(UpstashService.orgKey('loc-states')).then(function (allStates) {
        if (allStates && allStates[liso2] && allStates[liso2].length > 0) {
            _renderOrgStates(allStates[liso2]);
        } else {
            $.ajax({
                url: '/globally/getStateCityOfCountry', method: 'POST',
                data: { CountryCode: iso2 },
                success:  function (r) { _renderOrgStates(!r.Error && r.StateInfo ? r.StateInfo : []); },
                error:    function ()  { _renderOrgStates([]); },
                complete: function ()  { if (typeof onDone === 'function') onDone(); }
            });
            return; // onDone called in complete
        }
        if (typeof onDone === 'function') onDone();
    });
}

// ── Load cities: Upstash → AJAX fallback ──────────────────────────────────────
function _loadOrgCities($selCity, stateISO2, selectedVal, onDone) {
    if (!stateISO2) {
        $selCity.empty().append('<option value="">-- Select City --</option>').val(null).trigger('change');
        if (typeof onDone === 'function') onDone();
        return;
    }
    var cISO2  = (typeof defaultIso2 !== 'undefined' ? defaultIso2 : 'IN').toUpperCase();
    var sISO2  = stateISO2.toUpperCase();
    var subKey = cISO2.toLowerCase() + '-' + sISO2.toLowerCase();

    // Session cache hit — instant
    if (_orgCityCache[subKey] !== undefined) {
        _renderOrgCities($selCity, _orgCityCache[subKey], selectedVal);
        if (typeof onDone === 'function') onDone();
        return;
    }

    UpstashService.get(UpstashService.orgKey('loc-cities-by-state')).then(function (allCities) {
        if (allCities && allCities[subKey] && allCities[subKey].length > 0) {
            _orgCityCache[subKey] = allCities[subKey];
            _renderOrgCities($selCity, _orgCityCache[subKey], selectedVal);
            if (typeof onDone === 'function') onDone();
        } else {
            $.ajax({
                url: '/globally/getCitiesOfState', method: 'POST',
                data: { CountryISO2: cISO2, StateISO2: sISO2 },
                success: function (r) {
                    _orgCityCache[subKey] = (!r.Error && r.Data) ? r.Data : [];
                    _renderOrgCities($selCity, _orgCityCache[subKey], selectedVal);
                },
                error:    function ()  { _orgCityCache[subKey] = []; _renderOrgCities($selCity, [], selectedVal); },
                complete: function ()  { if (typeof onDone === 'function') onDone(); }
            });
        }
    });
}

// ── State click: lazy-load full state list ─────────────────────────────────────
$(document).on('select2:opening', '#BillAddrState, #ShipAddrState', function (e) {
    if (_orgStateLoaded) return;
    e.preventDefault();
    var $sel = $(this);
    _loadOrgStates(function () { _openAndScroll($sel); });
});

// ── City click: check Upstash for selected state's cities ─────────────────────
$(document).on('select2:opening', '#BillAddrCity, #ShipAddrCity', function (e) {
    var $citysel  = $(this);
    var isBill    = $citysel.attr('id') === 'BillAddrCity';
    var $stateSel = isBill ? $('#BillAddrState') : $('#ShipAddrState');
    var stateISO2 = $stateSel.find('option:selected').data('iso2') || '';

    var cISO2  = (typeof defaultIso2 !== 'undefined' ? defaultIso2 : 'IN').toUpperCase();
    var subKey = stateISO2 ? cISO2.toLowerCase() + '-' + stateISO2.toLowerCase() : '';

    // Already populated this session — open normally
    if (subKey && _orgCityCache[subKey] !== undefined) return;

    e.preventDefault();

    function _openWithCities(iso2) {
        _loadOrgCities($citysel, iso2, null, function () {
            _openAndScroll($citysel);
        });
    }

    if (stateISO2) {
        // State ISO2 known — go straight to city lookup
        _openWithCities(stateISO2);
    } else {
        // States not loaded yet — load them first (instant from Upstash if cached),
        // then extract the now-available ISO2 and load cities
        _loadOrgStates(function () {
            var iso2 = $stateSel.find('option:selected').data('iso2') || '';
            if (iso2) {
                _openWithCities(iso2);
            } else {
                _openAndScroll($citysel); // no state selected — open empty
            }
        });
    }
});

// ── State change: reload cities ────────────────────────────────────────────────
$(document).on('change', '#BillAddrState', function () {
    var iso2 = $(this).find('option:selected').data('iso2') || '';
    _loadOrgCities($('#BillAddrCity'), iso2, null);
});

$(document).on('change', '#ShipAddrState', function () {
    var iso2 = $(this).find('option:selected').data('iso2') || '';
    _loadOrgCities($('#ShipAddrCity'), iso2, null);
});

// ── Form submit ────────────────────────────────────────────────────────────────
function updateOrgForm(formdata) {
    $('.OrgSubBtn').attr('disabled', 'disabled');
    $.ajax({
        url: '/organisation/updateOrgForm',
        method: 'POST',
        data: formdata,
        cache: false,
        processData: false,
        contentType: false,
        enctype: 'multipart/form-data',
        success: function (response) {
            $('.OrgSubBtn').removeAttr('disabled');
            if (response.Error) {
                showToastNotification(response.Message, 'error');
            } else {
                showToastNotification(response.Message, 'success');
                imageChange = 0;
                if ($('#BillOrgAddressUID').val() == 0) $('#BillOrgAddressUID').val(response.BillOrgAddressUID);
                if ($('#ShipOrgAddressUID').val() == 0) $('#ShipOrgAddressUID').val(response.ShipOrgAddressUID);
            }
        }
    });
}
