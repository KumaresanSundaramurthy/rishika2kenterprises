/**
 * VendorGroupForm — modal for add / edit Vendor Groups.
 *
 * Usage:
 *   VendorGroupForm.open('add', null, { onSaveSuccess: fn });
 *   VendorGroupForm.open('edit', groupUID, { onSaveSuccess: fn });
 *
 * Depends on: address.js (csc_loadStates, csc_loadCities), UpstashService,
 *             showToastNotification, Swal
 */
(function (window, $) {
    'use strict';

    // ── State ────────────────────────────────────────────────────────────────
    var _editGroupUID    = 0;
    var _onSaveSuccess   = null;
    var _members         = [];
    var _primaryUID      = 0;
    var _countriesCache  = null;
    var _groupTypesCache = null;
    var _vendCache       = null;
    var _vendAjaxMode    = false; // true when Upstash unavailable; use per-query AJAX
    var _vgAddrActive    = false; // intercept #AddrSaveBtn for group context
    var _vgAddrOpened    = false; // swap flag: group modal was hidden to show address modal
    var _vgAddrData      = null;  // {Line1, Line2, Pincode, StateName, StateCode, CityName}

    // ── Public API ───────────────────────────────────────────────────────────
    window.VendorGroupForm = { open: _open };

    // ── Open ─────────────────────────────────────────────────────────────────
    function _open(type, uid, opts) {
        opts           = opts  || {};
        _onSaveSuccess = opts.onSaveSuccess || null;
        _editGroupUID  = 0;

        $('#VGroupModalTitle').text(type === 'edit' ? 'Edit Vendor Group' : 'Create Vendor Group');
        $('#VGroupSaveBtnLabel').text(type === 'edit' ? 'Update' : 'Save');
        $('#VGroupModalForm').data('mode', type);

        _reset();

        if (type === 'add') {
            var iso2 = $('#VG_CountryISO2').val() || 'IN';
            _initCC(iso2);
            _buildGroupTypeSelect('');
            $('#VendorGroupFormModal').modal('show');
            return;
        }

        _editGroupUID = uid || 0;
        $.ajax({
            url   : '/vendors/getGroupForModal/' + _editGroupUID,
            method: 'GET',
            cache : false,
            success: function (res) {
                if (res.Error) { showAlertMessageSwal('error', '', res.Message || 'Failed to load group.'); return; }
                _populate(res.Data, res.Members || []);
                $('#VendorGroupFormModal').modal('show');
            },
            error: function () { showAlertMessageSwal('error', '', 'Failed to load group.'); }
        });
    }

    // ── Reset ─────────────────────────────────────────────────────────────────
    function _reset() {
        var $form = $('#VGroupModalForm');
        if ($form.length) $form[0].reset();
        $('#VGroupUID').val('');
        $('#VG_GroupType').empty().append('<option value="">Loading...</option>');
        _vgAddrData   = null;
        _vgAddrActive = false;
        _vgAddrOpened = false;
        $('#VG_AddrLine1,#VG_AddrLine2,#VG_AddrPincode,#VG_AddrState,#VG_AddrStateCode,#VG_AddrCity').val('');
        _renderAddrBox();
        // Sync CC button with form-reset hidden input value
        $('#VG_MobileCCBtn').text($('#VG_MobileCountryCode').val() || '+91');
        _members    = [];
        _primaryUID = 0;
        _renderMembers();
        $('#VG_MemberSearch').val('');
        $('#VG_CCDropdown,#VG_MemberDropdown').hide();
        // Clear validation state
        $('#VG_Email,#VG_Mobile').removeClass('is-invalid');
        $('#VG_MobileErr').hide();
    }

    // ── Country code init ─────────────────────────────────────────────────────
    function _initCC(iso2) {
        _loadCountries(function (countries) {
            var found = countries.find(function (c) { return (c.iso2 || '').toUpperCase() === iso2.toUpperCase(); });
            var code  = found ? '+' + String(found.phonecode) : $('#VG_MobileCountryCode').val() || '+91';
            $('#VG_MobileCCBtn').text(code);
            $('#VG_MobileCountryCode').val(code);
        });
    }

    // ── Load all countries (Upstash → AJAX) ─────────────────────────────────
    function _loadCountries(cb) {
        if (_countriesCache) { cb(_countriesCache); return; }
        if (!UpstashService.isEnabled()) { _fetchCountriesAjax(cb); return; }
        UpstashService.get(UpstashService.orgKey('loc-countries')).then(function (data) {
            if (Array.isArray(data) && data.length) {
                _countriesCache = data;
                cb(_countriesCache);
            } else {
                _fetchCountriesAjax(cb);
            }
        }).catch(function () { _fetchCountriesAjax(cb); });
    }

    function _fetchCountriesAjax(cb) {
        $.ajax({
            url: '/globally/getCountryInfo', dataType: 'json',
            success: function (res) {
                _countriesCache = (res && res.Data) ? res.Data : [];
                cb(_countriesCache);
            },
            error: function () { _countriesCache = []; cb([]); }
        });
    }

    // ── Group type select — load from Upstash cache → AJAX ───────────────────
    function _loadGroupTypes(cb) {
        if (_groupTypesCache) { cb(_groupTypesCache); return; }
        if (!UpstashService.isEnabled()) { _fetchGroupTypesAjax(cb); return; }
        UpstashService.get(UpstashService.orgKey('vendor-group-types')).then(function (data) {
            if (Array.isArray(data) && data.length) {
                _groupTypesCache = data; cb(data);
            } else {
                _fetchGroupTypesAjax(cb);
            }
        }).catch(function () { _fetchGroupTypesAjax(cb); });
    }

    function _fetchGroupTypesAjax(cb) {
        $.ajax({
            url: '/vendors/getGroupTypes', dataType: 'json',
            success: function (res) {
                var types = res.Data || [];
                _groupTypesCache = types;
                if (UpstashService.isEnabled() && types.length) {
                    UpstashService.set(UpstashService.orgKey('vendor-group-types'), types, 86400);
                }
                cb(types);
            },
            error: function () { cb([]); }
        });
    }

    function _buildGroupTypeSelect(selectedVal) {
        _loadGroupTypes(function (types) {
            var html = types.map(function (t) {
                return '<option value="' + _esc(t) + '"' + (t === selectedVal ? ' selected' : '') + '>' + _esc(t) + '</option>';
            }).join('');
            $('#VG_GroupType').html(html || '<option value="">No types available</option>');
        });
    }

    // ── Populate form for edit ────────────────────────────────────────────────
    function _populate(d, members) {
        _editGroupUID = d.GroupUID || 0;
        $('#VGroupUID').val(_editGroupUID);
        $('#VG_GroupName').val(d.GroupName     || '');
        $('#VG_GroupCode').val(d.GroupCode     || '');
        $('#VG_ContactPerson').val(d.ContactPerson || '');
        $('#VG_Mobile').val(d.Mobile           || '');
        $('#VG_Email').val(d.Email             || '');
        $('#VG_GSTNo').val(d.GSTNo             || '');
        $('#VG_Notes').val(d.Notes             || '');

        _buildGroupTypeSelect(d.GroupType || '');

        // Mobile country code
        var mcc  = d.MobileCountryCode || '';
        var iso2 = $('#VG_CountryISO2').val() || 'IN';
        if (mcc) {
            $('#VG_MobileCCBtn').text(mcc);
            $('#VG_MobileCountryCode').val(mcc);
        } else {
            _initCC(iso2);
        }

        // Restore address
        if (d.AddrLine1 || d.AddrLine2 || d.AddrCity || d.AddrState) {
            _vgAddrData = {
                Line1     : d.AddrLine1     || '',
                Line2     : d.AddrLine2     || '',
                Pincode   : d.AddrPincode   || '',
                StateName : d.AddrState     || '',
                StateCode : d.AddrStateCode || '',
                CityName  : d.AddrCity      || '',
            };
            $('#VG_AddrLine1').val(_vgAddrData.Line1);
            $('#VG_AddrLine2').val(_vgAddrData.Line2);
            $('#VG_AddrPincode').val(_vgAddrData.Pincode);
            $('#VG_AddrState').val(_vgAddrData.StateName);
            $('#VG_AddrStateCode').val(_vgAddrData.StateCode);
            $('#VG_AddrCity').val(_vgAddrData.CityName);
        }
        _renderAddrBox();

        // Members
        _members    = [];
        _primaryUID = 0;
        (members || []).forEach(function (m) {
            var isPri = parseInt(m.IsGroupPrimary || 0) === 1;
            _members.push({
                uid    : parseInt(m.VendorUID),
                name   : m.Name          || '',
                area   : m.Area          || '',
                mobile : m.MobileNumber  || '',
                balance: parseFloat(m.Balance || 0),
                balType: m.BalanceType   || 'Credit',
                primary: isPri,
            });
            if (isPri) _primaryUID = parseInt(m.VendorUID);
        });
        _renderMembers();
    }

    // ── Country-code dropdown ─────────────────────────────────────────────────
    $(document).on('click', '#VG_MobileCCBtn', function (e) {
        e.stopPropagation();
        var open = $('#VG_CCDropdown').is(':visible');
        $('#VG_CCDropdown').toggle(!open);
        if (!open) {
            $('#VG_CCSearch').val('').focus();
            _renderCCList('');
        }
    });

    $(document).on('input', '#VG_CCSearch', function () { _renderCCList($(this).val()); });

    function _renderCCList(query) {
        _loadCountries(function (countries) {
            var q        = $.trim(query).toLowerCase();
            var filtered = q
                ? countries.filter(function (c) {
                    return (c.name || '').toLowerCase().indexOf(q) >= 0
                        || String(c.phonecode || '').indexOf(q) >= 0;
                  })
                : countries;

            var html = filtered.map(function (c) {
                return '<div class="vg-cc-item px-3 py-1" style="cursor:pointer;font-size:.84rem;line-height:1.8;" ' +
                    'data-iso2="' + _esc(c.iso2) + '" data-code="+' + _esc(String(c.phonecode)) + '">' +
                    _esc(c.name) + ' <span class="text-muted">(+' + _esc(String(c.phonecode)) + ')</span>' +
                    '</div>';
            }).join('');

            $('#VG_CCList').html(html ||
                '<div class="px-3 py-2 text-muted" style="font-size:.8rem;">No results</div>');
        });
    }

    $(document).on('mouseenter', '.vg-cc-item', function () { $(this).css('background', '#f0f2ff'); });
    $(document).on('mouseleave', '.vg-cc-item', function () { $(this).css('background', ''); });

    $(document).on('click', '.vg-cc-item', function () {
        var iso2 = $(this).data('iso2') || '';
        var code = $(this).data('code') || '';
        $('#VG_MobileCCBtn').text(code);
        $('#VG_MobileCountryCode').val(code);
        $('#VG_CountryISO2').val(iso2);
        $('#VG_CCDropdown').hide();
    });

    $(document).on('click', function (e) {
        if (!$(e.target).closest('#VG_MobileCCBtn,#VG_CCDropdown').length) {
            $('#VG_CCDropdown').hide();
        }
    });

    // ── Address click-box → modal SWAP (hide group modal, show address modal) ──
    // Bootstrap does not support stacked modals; swap is the only reliable fix.
    $(document).on('click', '#VG_AddrBox', function () {
        var iso2 = $('#VG_CountryISO2').val() || 'IN';

        $('#addrModalTitle,#AddrModalTitle').text('Group Address');
        $('#AddrType').val(0);

        $('#ModalAddrLine1').val(_vgAddrData ? _vgAddrData.Line1   : '');
        $('#ModalAddrLine2').val(_vgAddrData ? _vgAddrData.Line2   : '');
        $('#ModalAddrPincode').val(_vgAddrData ? _vgAddrData.Pincode : '');
        $('#ModalAddrState').empty().append('<option value="">-- Select State --</option>');
        $('#ModalAddrCity').empty().append('<option value="">-- Select City --</option>');

        _vgAddrActive = true;
        _vgAddrOpened = true;

        // Start loading states while the group modal animates out
        if (_vgAddrData && _vgAddrData.StateName) {
            csc_loadStates('ModalAddrState', iso2, '', function () {
                var stNameLow = _vgAddrData.StateName.toLowerCase();
                var stCode    = '';
                $('#ModalAddrState option').each(function () {
                    if ($.trim($(this).text()).toLowerCase() === stNameLow) {
                        $('#ModalAddrState').val($(this).val());
                        stCode = $(this).data('iso2') || '';
                        return false;
                    }
                });
                if (stCode && _vgAddrData.CityName && typeof csc_loadCities === 'function') {
                    csc_loadCities('ModalAddrCity', iso2, stCode, '', _vgAddrData.CityName);
                }
            });
        } else {
            csc_loadStates('ModalAddrState', iso2, '', null);
        }

        // Hide group modal first; open address modal after hide completes
        $('#VendorGroupFormModal').one('hidden.bs.modal', function () {
            $('#addEditAddressModal').modal('show');
        }).modal('hide');
    });

    // ── Intercept address modal Save for vendor group context ─────────────────
    // Direct binding fires before address.js delegated document handler.
    $('#AddrSaveBtn').on('click', function (e) {
        if (!_vgAddrActive) return;
        e.stopImmediatePropagation();

        var line1 = $.trim($('#ModalAddrLine1').val());
        if (!line1) { showAlertMessageSwal('error', '', 'Address Line 1 is required.'); return; }
        var pincode = $.trim($('#ModalAddrPincode').val());
        if (!pincode) { showAlertMessageSwal('error', '', 'Pincode is required.'); return; }

        var $state = $('#ModalAddrState option:selected');
        var $city  = $('#ModalAddrCity option:selected');

        _vgAddrData = {
            Line1    : line1,
            Line2    : $.trim($('#ModalAddrLine2').val()),
            Pincode  : pincode,
            StateName: ($state.val() && $state.text() !== '-- Select State --') ? $state.text() : '',
            StateCode: $state.data('iso2') || '',
            CityName : ($city.val()  && $city.text()  !== '-- Select City --')  ? $city.text()  : '',
        };

        $('#VG_AddrLine1').val(_vgAddrData.Line1);
        $('#VG_AddrLine2').val(_vgAddrData.Line2);
        $('#VG_AddrPincode').val(_vgAddrData.Pincode);
        $('#VG_AddrState').val(_vgAddrData.StateName);
        $('#VG_AddrStateCode').val(_vgAddrData.StateCode);
        $('#VG_AddrCity').val(_vgAddrData.CityName);

        _renderAddrBox();
        _vgAddrActive = false;
        $('#addEditAddressModal').modal('hide');
        // hidden.bs.modal below will restore the group modal
    });

    // When address modal closes (save OR close/X): restore vendor group form modal
    $(document).on('hidden.bs.modal', '#addEditAddressModal', function () {
        _vgAddrActive = false;
        if (_vgAddrOpened) {
            _vgAddrOpened = false;
            $('#VendorGroupFormModal').modal('show');
        }
    });

    function _renderAddrBox() {
        var $box = $('#VG_AddrBox');
        if (!_vgAddrData || !_vgAddrData.Line1) {
            $box.html('<span style="color:#adb5bd;"><i class="bx bx-map-pin me-1"></i>Click to add address...</span>');
            return;
        }
        var parts = [_vgAddrData.Line1];
        if (_vgAddrData.Line2)     parts.push(_vgAddrData.Line2);
        if (_vgAddrData.CityName)  parts.push(_vgAddrData.CityName);
        if (_vgAddrData.StateName) parts.push(_vgAddrData.StateName);
        if (_vgAddrData.Pincode)   parts.push(_vgAddrData.Pincode);
        $box.html('<i class="bx bx-map-pin me-1 text-primary"></i>' + _esc(parts.join(', ')));
    }

    // ── Vendor lookup for member search (Upstash cache → per-query AJAX) ────────
    function _getVendors(q, cb) {
        if (_vendAjaxMode)   { _fetchVendorsAjax(q, cb); return; }
        if (_vendCache !== null) { cb(_vendCache); return; }
        if (!UpstashService.isEnabled()) { _vendAjaxMode = true; _fetchVendorsAjax(q, cb); return; }
        UpstashService.hgetall(UpstashService.orgKey('vendors')).then(function (map) {
            if (map && typeof map === 'object' && !Array.isArray(map) && Object.keys(map).length) {
                _vendCache = Object.values(map);
                cb(_vendCache);
            } else {
                _vendAjaxMode = true;
                _fetchVendorsAjax(q, cb);
            }
        }).catch(function () { _vendAjaxMode = true; _fetchVendorsAjax(q, cb); });
    }

    function _fetchVendorsAjax(q, cb) {
        $.ajax({
            url: '/vendors/searchVendors', dataType: 'json', data: { term: q },
            success: function (res) {
                cb((res.Lists || []).map(function (v) {
                    return { VendorUID: parseInt(v.id), Name: v.text || '', MobileNumber: v.mobile || '', Area: v.area || '' };
                }));
            },
            error: function () { cb([]); }
        });
    }

    // ── Member search input ───────────────────────────────────────────────────
    $(document).on('input', '#VG_MemberSearch', function () {
        var q = $.trim($(this).val()).toLowerCase();
        if (!q) { $('#VG_MemberDropdown').hide(); return; }

        _getVendors(q, function (list) {
            var added    = _members.map(function (m) { return m.uid; });
            var filtered = list.filter(function (v) {
                if (added.indexOf(v.VendorUID) >= 0) return false;
                if (_vendAjaxMode) return true; // AJAX already filtered by term
                return (v.Name || '').toLowerCase().indexOf(q) >= 0
                    || (v.MobileNumber || '').indexOf(q) >= 0;
            }).slice(0, 20);

            if (!filtered.length) {
                $('#VG_MemberDropdown')
                    .html('<div class="px-3 py-2 text-muted" style="font-size:.8rem;">No vendors found</div>')
                    .show();
                return;
            }

            var html = filtered.map(function (v) {
                var init = (v.Name || '?').charAt(0).toUpperCase();
                return '<div class="vg-vend-item d-flex align-items-center gap-2 px-3 py-2" ' +
                    'style="cursor:pointer;font-size:.85rem;" ' +
                    'data-uid="' + v.VendorUID + '" ' +
                    'data-name="' + _esc(v.Name) + '" ' +
                    'data-mobile="' + _esc(v.MobileNumber) + '" ' +
                    'data-area="' + _esc(v.Area || '') + '">' +
                    '<div style="width:28px;height:28px;border-radius:50%;background:#8592a3;color:#fff;' +
                    'display:flex;align-items:center;justify-content:center;font-size:.72rem;font-weight:700;flex-shrink:0;">' +
                    _esc(init) + '</div>' +
                    '<div><div class="fw-semibold">' + _esc(v.Name) + '</div>' +
                    (v.MobileNumber ? '<div class="text-muted" style="font-size:.78rem;">' + _esc(v.MobileNumber) + '</div>' : '') +
                    '</div></div>';
            }).join('');

            $('#VG_MemberDropdown').html(html).show();
        });
    });

    $(document).on('mouseenter', '.vg-vend-item', function () { $(this).css('background', '#f0f2ff'); });
    $(document).on('mouseleave', '.vg-vend-item', function () { $(this).css('background', ''); });

    $(document).on('click', '.vg-vend-item', function () {
        var uid    = parseInt($(this).data('uid'));
        var name   = $(this).data('name')   || '';
        var mobile = $(this).data('mobile') || '';
        var area   = $(this).data('area')   || '';

        if (_members.some(function (m) { return m.uid === uid; })) {
            showToastNotification('Already in group.', 'info');
            $('#VG_MemberSearch').val('').focus();
            $('#VG_MemberDropdown').hide();
            return;
        }
        var isFirst = _members.length === 0;
        _members.push({ uid: uid, name: name, area: area, mobile: mobile, balance: 0, balType: 'Credit', primary: isFirst });
        if (isFirst) _primaryUID = uid;
        _renderMembers();
        $('#VG_MemberSearch').val('');
        $('#VG_MemberDropdown').hide();
    });

    $(document).on('click', '#VG_BtnAddMember', function () {
        var $first = $('#VG_MemberDropdown .vg-vend-item:first');
        if ($first.length) { $first.trigger('click'); return; }
        showToastNotification('Search for a vendor first.', 'warning');
    });

    $(document).on('click', function (e) {
        if (!$(e.target).closest('#VG_MemberSearch,#VG_MemberDropdown,#VG_BtnAddMember').length) {
            $('#VG_MemberDropdown').hide();
        }
    });

    // ── Set primary / remove member ───────────────────────────────────────────
    $(document).on('click', '.vg-set-primary', function () {
        _primaryUID = parseInt($(this).data('uid'));
        _members.forEach(function (m) { m.primary = (m.uid === _primaryUID); });
        _renderMembers();
    });

    $(document).on('click', '.vg-remove-member', function () {
        var uid = parseInt($(this).data('uid'));
        _members = _members.filter(function (m) { return m.uid !== uid; });
        if (_primaryUID === uid) {
            _primaryUID = _members.length ? _members[0].uid : 0;
            if (_members.length) _members[0].primary = true;
        }
        _renderMembers();
    });

    // ── Render members table ──────────────────────────────────────────────────
    function _renderMembers() {
        $('#VG_MemberInputs').empty();
        if (!_members.length) {
            $('#VG_MembersBox').html(
                '<div class="d-flex flex-column align-items-center justify-content-center py-4 text-muted">' +
                '<i class="bx bx-user-plus fs-2 mb-2"></i>' +
                '<div style="font-size:.85rem;">No members yet. Search and add vendors above.</div></div>'
            );
            return;
        }

        var rows = _members.map(function (m) {
            var balCol = m.balType === 'Debit' ? '#28a745' : '#dc3545';
            var isPri  = (m.uid === _primaryUID);
            return '<tr data-uid="' + m.uid + '">' +
                '<td class="fw-semibold" style="font-size:.85rem;">' + _esc(m.name) + '</td>' +
                '<td style="font-size:.8rem;">' + _esc(m.area || '—') + '</td>' +
                '<td style="font-size:.8rem;">' + _esc(m.mobile || '—') + '</td>' +
                '<td class="text-end" style="font-size:.8rem;color:' + balCol + ';">' + parseFloat(m.balance).toFixed(2) + '</td>' +
                '<td class="text-center"><button type="button" class="btn btn-sm btn-icon ' +
                (isPri ? 'text-warning' : 'text-secondary') +
                ' vg-set-primary" data-uid="' + m.uid + '" title="' + (isPri ? 'Primary contact' : 'Set as primary') + '">' +
                '<i class="bx ' + (isPri ? 'bxs-star' : 'bx-star') + ' fs-5"></i></button></td>' +
                '<td><button type="button" class="btn btn-sm btn-icon text-danger vg-remove-member" ' +
                'data-uid="' + m.uid + '" title="Remove"><i class="bx bx-trash fs-5"></i></button></td>' +
            '</tr>';
        }).join('');

        $('#VG_MembersBox').html(
            '<div class="table-responsive">' +
            '<table class="table table-sm align-middle mb-0">' +
            '<thead style="background:#f8f9fa;"><tr style="font-size:.76rem;text-transform:uppercase;color:#566a7f;">' +
            '<th>Vendor</th>' +
            '<th style="width:120px;">Area</th><th style="width:130px;">Mobile</th>' +
            '<th style="width:130px;text-align:right;">Balance</th>' +
            '<th style="width:90px;text-align:center;">Primary</th>' +
            '<th style="width:50px;"></th>' +
            '</tr></thead><tbody>' + rows + '</tbody></table></div>'
        );

        var inputs = _members.map(function (m) {
            return '<input type="hidden" name="MemberUIDs[]" value="' + m.uid + '">';
        }).join('');
        inputs += '<input type="hidden" name="PrimaryUID" value="' + (_primaryUID || '') + '">';
        $('#VG_MemberInputs').html(inputs);
    }

    // ── Validation ───────────────────────────────────────────────────────────
    function _validateForm() {
        var valid   = true;
        var emailRe = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

        var email = $.trim($('#VG_Email').val());
        if (email && !emailRe.test(email)) {
            $('#VG_Email').addClass('is-invalid');
            valid = false;
        } else {
            $('#VG_Email').removeClass('is-invalid');
        }

        var mobile   = $.trim($('#VG_Mobile').val()).replace(/\D/g, '');
        var hasMobile = $.trim($('#VG_Mobile').val()).length > 0;
        if (hasMobile && mobile.length < 7) {
            $('#VG_Mobile').addClass('is-invalid');
            $('#VG_MobileErr').show();
            valid = false;
        } else {
            $('#VG_Mobile').removeClass('is-invalid');
            $('#VG_MobileErr').hide();
        }

        return valid;
    }

    // Clear validation on user input
    $(document).on('input', '#VG_Email',  function () { $(this).removeClass('is-invalid'); });
    $(document).on('input', '#VG_Mobile', function () {
        $(this).removeClass('is-invalid');
        $('#VG_MobileErr').hide();
    });

    // ── Save ──────────────────────────────────────────────────────────────────
    $(document).on('click', '#VGroupSaveBtn', function () {
        var $form     = $('#VGroupModalForm');
        var groupName = $.trim($('#VG_GroupName').val());
        if (!groupName) { $('#VG_GroupName').addClass('is-invalid').focus(); return; }
        $('#VG_GroupName').removeClass('is-invalid');

        if (!_validateForm()) return;

        var mode = $form.data('mode');
        var url  = (mode === 'edit') ? '/vendors/updateGroupData' : '/vendors/addGroupData';
        var data = $form.serializeArray();
        data.push({ name: CsrfName, value: CsrfToken });

        var $btn     = $(this).prop('disabled', true);
        var $spinner = $('<span class="spinner-border spinner-border-sm me-1" role="status"></span>');
        $btn.prepend($spinner);

        $.ajax({
            url   : url,
            method: 'POST',
            data  : data,
            success: function (res) {
                $spinner.remove();
                $btn.prop('disabled', false);
                CsrfToken = res.NewCsrfToken || CsrfToken;
                if (res.Error) { showToastNotification(res.Message, 'error'); return; }
                showToastNotification(res.Message, 'success');
                $('#VendorGroupFormModal').modal('hide');
                if (typeof _onSaveSuccess === 'function') _onSaveSuccess(res);
            },
            error: function () {
                $spinner.remove();
                $btn.prop('disabled', false);
                showToastNotification('Request failed. Please try again.', 'error');
            }
        });
    });

    $(document).on('input', '#VG_GroupName', function () { $(this).removeClass('is-invalid'); });

    // ── HTML escape ───────────────────────────────────────────────────────────
    function _esc(s) {
        return String(s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

}(window, jQuery));
