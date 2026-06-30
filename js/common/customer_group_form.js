/**
 * CustomerGroupForm — modal for add / edit Customer Groups.
 *
 * Usage:
 *   CustomerGroupForm.open('add', null, { onSaveSuccess: fn });
 *   CustomerGroupForm.open('edit', groupUID, { onSaveSuccess: fn });
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
    var _custCache       = null;
    var _custAjaxMode    = false; // true when Upstash unavailable; use per-query AJAX
    var _cgAddrActive    = false; // intercept #AddrSaveBtn for group context
    var _cgAddrOpened    = false; // swap flag: group modal was hidden to show address modal
    var _cgAddrData      = null;  // {Line1, Line2, Pincode, StateName, StateCode, CityName}

    // ── Public API ───────────────────────────────────────────────────────────
    window.CustomerGroupForm = { open: _open };

    // ── Open ─────────────────────────────────────────────────────────────────
    function _open(type, uid, opts) {
        opts           = opts  || {};
        _onSaveSuccess = opts.onSaveSuccess || null;
        _editGroupUID  = 0;

        $('#CGroupModalTitle').text(type === 'edit' ? 'Edit Customer Group' : 'Create Customer Group');
        $('#CGroupSaveBtnLabel').text(type === 'edit' ? 'Update' : 'Save');
        $('#CGroupModalForm').data('mode', type);

        _reset();

        // Hide Group Members section when opened from customer form context
        // Also store the context so the save handler can send a slim response flag
        $('#CGroupModalForm').data('context', opts.hideMembers ? 'customer_form' : 'groups_tab');
        if (opts.hideMembers) {
            $('#cgGroupMembersSection').hide();
        } else {
            $('#cgGroupMembersSection').show();
        }

        if (type === 'add') {
            var iso2 = $('#CG_CountryISO2').val() || 'IN';
            _initCC(iso2);
            _buildGroupTypeSelect('');
            $('#CustomerGroupFormModal').modal('show');
            return;
        }

        _editGroupUID = uid || 0;
        $.ajax({
            url   : '/customers/getGroupForModal/' + _editGroupUID,
            method: 'GET',
            cache : false,
            success: function (res) {
                if (res.Error) { showAlertMessageSwal('error', '', res.Message || 'Failed to load group.'); return; }
                _populate(res.Data, res.Members || []);
                $('#CustomerGroupFormModal').modal('show');
            },
            error: function () { showAlertMessageSwal('error', '', 'Failed to load group.'); }
        });
    }

    // ── Reset ─────────────────────────────────────────────────────────────────
    function _reset() {
        var $form = $('#CGroupModalForm');
        if ($form.length) $form[0].reset();
        $('#CGroupUID').val('');
        $('#CG_GroupType').empty().append('<option value="">Loading...</option>');
        _cgAddrData   = null;
        _cgAddrActive = false;
        _cgAddrOpened = false;
        $('#CG_AddrLine1,#CG_AddrLine2,#CG_AddrPincode,#CG_AddrState,#CG_AddrStateCode,#CG_AddrCity').val('');
        _renderAddrBox();
        // Sync CC button with form-reset hidden input value
        $('#CG_MobileCCBtn').text($('#CG_MobileCountryCode').val() || '+91');
        _members    = [];
        _primaryUID = 0;
        _renderMembers();
        $('#CG_MemberSearch').val('');
        $('#CG_CCDropdown,#CG_MemberDropdown').hide();
        // Clear validation state
        $('#CG_Email,#CG_Mobile').removeClass('is-invalid');
        $('#CG_MobileErr').hide();
    }

    // ── Country code init — set button text from iso2 ─────────────────────────
    function _initCC(iso2) {
        _loadCountries(function (countries) {
            var found = countries.find(function (c) { return (c.iso2 || '').toUpperCase() === iso2.toUpperCase(); });
            var code  = found ? '+' + String(found.phonecode) : $('#CG_MobileCountryCode').val() || '+91';
            $('#CG_MobileCCBtn').text(code);
            $('#CG_MobileCountryCode').val(code);
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
        UpstashService.get(UpstashService.orgKey('customer-group-types')).then(function (data) {
            if (Array.isArray(data) && data.length) {
                _groupTypesCache = data; cb(data);
            } else {
                _fetchGroupTypesAjax(cb);
            }
        }).catch(function () { _fetchGroupTypesAjax(cb); });
    }

    function _fetchGroupTypesAjax(cb) {
        $.ajax({
            url: '/customers/getGroupTypes', dataType: 'json',
            success: function (res) {
                var types = res.Data || [];
                _groupTypesCache = types;
                if (UpstashService.isEnabled() && types.length) {
                    UpstashService.set(UpstashService.orgKey('customer-group-types'), types, 86400);
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
            $('#CG_GroupType').html(html || '<option value="">No types available</option>');
        });
    }

    // ── Populate form for edit ────────────────────────────────────────────────
    function _populate(d, members) {
        _editGroupUID = d.GroupUID || 0;
        $('#CGroupUID').val(_editGroupUID);
        $('#CG_GroupName').val(d.GroupName     || '');
        $('#CG_GroupCode').val(d.GroupCode     || '');
        $('#CG_ContactPerson').val(d.ContactPerson || '');
        $('#CG_Mobile').val(d.Mobile           || '');
        $('#CG_Email').val(d.Email             || '');
        $('#CG_GSTNo').val(d.GSTNo             || '');
        $('#CG_Notes').val(d.Notes             || '');

        _buildGroupTypeSelect(d.GroupType || '');

        // Mobile country code
        var mcc  = d.MobileCountryCode || '';
        var iso2 = $('#CG_CountryISO2').val() || 'IN';
        if (mcc) {
            $('#CG_MobileCCBtn').text(mcc);
            $('#CG_MobileCountryCode').val(mcc);
        } else {
            _initCC(iso2);
        }

        // Restore address
        if (d.AddrLine1 || d.AddrLine2 || d.AddrCity || d.AddrState) {
            _cgAddrData = {
                Line1     : d.AddrLine1     || '',
                Line2     : d.AddrLine2     || '',
                Pincode   : d.AddrPincode   || '',
                StateName : d.AddrState     || '',
                StateCode : d.AddrStateCode || '',
                CityName  : d.AddrCity      || '',
            };
            $('#CG_AddrLine1').val(_cgAddrData.Line1);
            $('#CG_AddrLine2').val(_cgAddrData.Line2);
            $('#CG_AddrPincode').val(_cgAddrData.Pincode);
            $('#CG_AddrState').val(_cgAddrData.StateName);
            $('#CG_AddrStateCode').val(_cgAddrData.StateCode);
            $('#CG_AddrCity').val(_cgAddrData.CityName);
        }
        _renderAddrBox();

        // Members
        _members    = [];
        _primaryUID = 0;
        (members || []).forEach(function (m) {
            var isPri = parseInt(m.IsGroupPrimary || 0) === 1;
            _members.push({
                uid    : parseInt(m.CustomerUID),
                name   : m.Name          || '',
                area   : m.Area          || '',
                mobile : m.MobileNumber  || '',
                balance: parseFloat(m.Balance || 0),
                balType: m.BalanceType   || 'Debit',
                primary: isPri,
            });
            if (isPri) _primaryUID = parseInt(m.CustomerUID);
        });
        _renderMembers();
    }

    // ── Country-code dropdown — toggle ────────────────────────────────────────
    $(document).on('click', '#CG_MobileCCBtn', function (e) {
        e.stopPropagation();
        var open = $('#CG_CCDropdown').is(':visible');
        $('#CG_CCDropdown').toggle(!open);
        if (!open) {
            $('#CG_CCSearch').val('').focus();
            _renderCCList('');
        }
    });

    $(document).on('input', '#CG_CCSearch', function () { _renderCCList($(this).val()); });

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
                return '<div class="cg-cc-item px-3 py-1" style="cursor:pointer;font-size:.84rem;line-height:1.8;" ' +
                    'data-iso2="' + _esc(c.iso2) + '" data-code="+' + _esc(String(c.phonecode)) + '">' +
                    _esc(c.name) + ' <span class="text-muted">(+' + _esc(String(c.phonecode)) + ')</span>' +
                    '</div>';
            }).join('');

            $('#CG_CCList').html(html ||
                '<div class="px-3 py-2 text-muted" style="font-size:.8rem;">No results</div>');
        });
    }

    // Hover highlight for CC items
    $(document).on('mouseenter', '.cg-cc-item', function () { $(this).css('background', '#f0f2ff'); });
    $(document).on('mouseleave', '.cg-cc-item', function () { $(this).css('background', ''); });

    $(document).on('click', '.cg-cc-item', function () {
        var iso2 = $(this).data('iso2') || '';
        var code = $(this).data('code') || '';
        $('#CG_MobileCCBtn').text(code);
        $('#CG_MobileCountryCode').val(code);
        $('#CG_CountryISO2').val(iso2);
        $('#CG_CCDropdown').hide();
    });

    // Close CC dropdown on outside click
    $(document).on('click', function (e) {
        if (!$(e.target).closest('#CG_MobileCCBtn,#CG_CCDropdown').length) {
            $('#CG_CCDropdown').hide();
        }
    });

    // ── Address click-box → modal SWAP (hide group modal, show address modal) ──
    // Bootstrap does not support stacked modals; swap is the only reliable fix.
    $(document).on('click', '#CG_AddrBox', function () {
        var iso2 = $('#CG_CountryISO2').val() || 'IN';

        $('#addrModalTitle,#AddrModalTitle').text('Group Address');
        $('#AddrType').val(0);

        $('#ModalAddrLine1').val(_cgAddrData ? _cgAddrData.Line1   : '');
        $('#ModalAddrLine2').val(_cgAddrData ? _cgAddrData.Line2   : '');
        $('#ModalAddrPincode').val(_cgAddrData ? _cgAddrData.Pincode : '');
        $('#ModalAddrState').empty().append('<option value="">-- Select State --</option>');
        $('#ModalAddrCity').empty().append('<option value="">-- Select City --</option>');

        _cgAddrActive = true;
        _cgAddrOpened = true;

        // Start loading states while the group modal animates out
        if (_cgAddrData && _cgAddrData.StateName) {
            csc_loadStates('ModalAddrState', iso2, '', function () {
                var stNameLow = _cgAddrData.StateName.toLowerCase();
                var stCode    = '';
                $('#ModalAddrState option').each(function () {
                    if ($.trim($(this).text()).toLowerCase() === stNameLow) {
                        $('#ModalAddrState').val($(this).val());
                        stCode = $(this).data('iso2') || '';
                        return false;
                    }
                });
                if (stCode && _cgAddrData.CityName && typeof csc_loadCities === 'function') {
                    csc_loadCities('ModalAddrCity', iso2, stCode, '', _cgAddrData.CityName);
                }
            });
        } else {
            csc_loadStates('ModalAddrState', iso2, '', null);
        }

        // Hide group modal first; open address modal after hide completes
        $('#CustomerGroupFormModal').one('hidden.bs.modal', function () {
            $('#addEditAddressModal').modal('show');
        }).modal('hide');
    });

    // ── Intercept address modal Save for group context ────────────────────────
    // Direct binding fires before address.js delegated document handler.
    $('#AddrSaveBtn').on('click', function (e) {
        if (!_cgAddrActive) return;
        e.stopImmediatePropagation();

        var line1 = $.trim($('#ModalAddrLine1').val());
        if (!line1) { showAlertMessageSwal('error', '', 'Address Line 1 is required.'); return; }
        var pincode = $.trim($('#ModalAddrPincode').val());
        if (!pincode) { showAlertMessageSwal('error', '', 'Pincode is required.'); return; }

        var $state = $('#ModalAddrState option:selected');
        var $city  = $('#ModalAddrCity option:selected');

        _cgAddrData = {
            Line1    : line1,
            Line2    : $.trim($('#ModalAddrLine2').val()),
            Pincode  : pincode,
            StateName: ($state.val() && $state.text() !== '-- Select State --') ? $state.text() : '',
            StateCode: $state.data('iso2') || '',
            CityName : ($city.val()  && $city.text()  !== '-- Select City --')  ? $city.text()  : '',
        };

        $('#CG_AddrLine1').val(_cgAddrData.Line1);
        $('#CG_AddrLine2').val(_cgAddrData.Line2);
        $('#CG_AddrPincode').val(_cgAddrData.Pincode);
        $('#CG_AddrState').val(_cgAddrData.StateName);
        $('#CG_AddrStateCode').val(_cgAddrData.StateCode);
        $('#CG_AddrCity').val(_cgAddrData.CityName);

        _renderAddrBox();
        _cgAddrActive = false;
        $('#addEditAddressModal').modal('hide');
        // hidden.bs.modal below will restore the group modal
    });

    // When address modal closes (save OR close/X): restore group form modal
    $(document).on('hidden.bs.modal', '#addEditAddressModal', function () {
        _cgAddrActive = false;
        if (_cgAddrOpened) {
            _cgAddrOpened = false;
            $('#CustomerGroupFormModal').modal('show');
        }
    });

    function _renderAddrBox() {
        var $box = $('#CG_AddrBox');
        if (!_cgAddrData || !_cgAddrData.Line1) {
            $box.html('<span style="color:#adb5bd;"><i class="bx bx-map-pin me-1"></i>Click to add address...</span>');
            return;
        }
        var parts = [_cgAddrData.Line1];
        if (_cgAddrData.Line2)     parts.push(_cgAddrData.Line2);
        if (_cgAddrData.CityName)  parts.push(_cgAddrData.CityName);
        if (_cgAddrData.StateName) parts.push(_cgAddrData.StateName);
        if (_cgAddrData.Pincode)   parts.push(_cgAddrData.Pincode);
        $box.html('<i class="bx bx-map-pin me-1 text-primary"></i>' + _esc(parts.join(', ')));
    }

    // ── Customer lookup for member search (Upstash cache → per-query AJAX) ──────
    function _getCustomers(q, cb) {
        if (_custAjaxMode)  { _fetchCustomersAjax(q, cb); return; }
        if (_custCache !== null) { cb(_custCache); return; }
        if (!UpstashService.isEnabled()) { _custAjaxMode = true; _fetchCustomersAjax(q, cb); return; }
        UpstashService.hgetall(UpstashService.orgKey('customers')).then(function (map) {
            if (map && typeof map === 'object' && !Array.isArray(map) && Object.keys(map).length) {
                _custCache = Object.values(map);
                cb(_custCache);
            } else {
                _custAjaxMode = true;
                _fetchCustomersAjax(q, cb);
            }
        }).catch(function () { _custAjaxMode = true; _fetchCustomersAjax(q, cb); });
    }

    function _fetchCustomersAjax(q, cb) {
        $.ajax({
            url: '/customers/searchCustomers', dataType: 'json', data: { term: q },
            success: function (res) {
                cb((res.Lists || []).map(function (c) {
                    return { CustomerUID: parseInt(c.id), Name: c.text || '', MobileNumber: c.mobile || '', Area: c.area || '' };
                }));
            },
            error: function () { cb([]); }
        });
    }

    // ── Member search input ───────────────────────────────────────────────────
    $(document).on('input', '#CG_MemberSearch', function () {
        var q = $.trim($(this).val()).toLowerCase();
        if (!q) { $('#CG_MemberDropdown').hide(); return; }

        _getCustomers(q, function (list) {
            var added    = _members.map(function (m) { return m.uid; });
            var filtered = list.filter(function (c) {
                if (added.indexOf(c.CustomerUID) >= 0) return false;
                if (_custAjaxMode) return true; // AJAX already filtered by term
                return (c.Name || '').toLowerCase().indexOf(q) >= 0
                    || (c.MobileNumber || '').indexOf(q) >= 0;
            }).slice(0, 20);

            if (!filtered.length) {
                $('#CG_MemberDropdown')
                    .html('<div class="px-3 py-2 text-muted" style="font-size:.8rem;">No customers found</div>')
                    .show();
                return;
            }

            var html = filtered.map(function (c) {
                var init = (c.Name || '?').charAt(0).toUpperCase();
                return '<div class="cg-cust-item d-flex align-items-center gap-2 px-3 py-2" ' +
                    'style="cursor:pointer;font-size:.85rem;" ' +
                    'data-uid="' + c.CustomerUID + '" ' +
                    'data-name="' + _esc(c.Name) + '" ' +
                    'data-mobile="' + _esc(c.MobileNumber) + '" ' +
                    'data-area="' + _esc(c.Area || '') + '">' +
                    '<div style="width:28px;height:28px;border-radius:50%;background:#4154f1;color:#fff;' +
                    'display:flex;align-items:center;justify-content:center;font-size:.72rem;font-weight:700;flex-shrink:0;">' +
                    _esc(init) + '</div>' +
                    '<div><div class="fw-semibold">' + _esc(c.Name) + '</div>' +
                    (c.MobileNumber ? '<div class="text-muted" style="font-size:.78rem;">' + _esc(c.MobileNumber) + '</div>' : '') +
                    '</div></div>';
            }).join('');

            $('#CG_MemberDropdown').html(html).show();
        });
    });

    // Hover highlight for customer items
    $(document).on('mouseenter', '.cg-cust-item', function () { $(this).css('background', '#f0f2ff'); });
    $(document).on('mouseleave', '.cg-cust-item', function () { $(this).css('background', ''); });

    $(document).on('click', '.cg-cust-item', function () {
        var uid    = parseInt($(this).data('uid'));
        var name   = $(this).data('name')   || '';
        var mobile = $(this).data('mobile') || '';
        var area   = $(this).data('area')   || '';

        if (_members.some(function (m) { return m.uid === uid; })) {
            showToastNotification('Already in group.', 'info');
            $('#CG_MemberSearch').val('').focus();
            $('#CG_MemberDropdown').hide();
            return;
        }
        var isFirst = _members.length === 0;
        _members.push({ uid: uid, name: name, area: area, mobile: mobile, balance: 0, balType: 'Debit', primary: isFirst });
        if (isFirst) _primaryUID = uid;
        _renderMembers();
        $('#CG_MemberSearch').val('');
        $('#CG_MemberDropdown').hide();
    });

    // Add button — adds top result in dropdown
    $(document).on('click', '#CG_BtnAddMember', function () {
        var $first = $('#CG_MemberDropdown .cg-cust-item:first');
        if ($first.length) { $first.trigger('click'); return; }
        showToastNotification('Search for a customer first.', 'warning');
    });

    // Close member dropdown on outside click
    $(document).on('click', function (e) {
        if (!$(e.target).closest('#CG_MemberSearch,#CG_MemberDropdown,#CG_BtnAddMember').length) {
            $('#CG_MemberDropdown').hide();
        }
    });

    // ── Set primary / remove member ───────────────────────────────────────────
    $(document).on('click', '.cg-set-primary', function () {
        _primaryUID = parseInt($(this).data('uid'));
        _members.forEach(function (m) { m.primary = (m.uid === _primaryUID); });
        _renderMembers();
    });

    $(document).on('click', '.cg-remove-member', function () {
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
        $('#CG_MemberInputs').empty();
        if (!_members.length) {
            $('#CG_MembersBox').html(
                '<div class="d-flex flex-column align-items-center justify-content-center py-4 text-muted">' +
                '<i class="bx bx-user-plus fs-2 mb-2"></i>' +
                '<div style="font-size:.85rem;">No members yet. Search and add customers above.</div></div>'
            );
            return;
        }

        var rows = _members.map(function (m) {
            var balCol = m.balType === 'Credit' ? '#dc3545' : '#28a745';
            var isPri  = (m.uid === _primaryUID);
            return '<tr data-uid="' + m.uid + '">' +
                '<td class="fw-semibold" style="font-size:.85rem;">' + _esc(m.name) + '</td>' +
                '<td style="font-size:.8rem;">' + _esc(m.area || '—') + '</td>' +
                '<td style="font-size:.8rem;">' + _esc(m.mobile || '—') + '</td>' +
                '<td class="text-end" style="font-size:.8rem;color:' + balCol + ';">' + parseFloat(m.balance).toFixed(2) + '</td>' +
                '<td class="text-center">' +
                    '<button type="button" class="btn btn-sm btn-icon ' + (isPri ? 'text-warning' : 'text-secondary') +
                    ' cg-set-primary" data-uid="' + m.uid + '" title="' + (isPri ? 'Primary contact' : 'Set as primary') + '">' +
                    '<i class="bx ' + (isPri ? 'bxs-star' : 'bx-star') + ' fs-5"></i></button></td>' +
                '<td><button type="button" class="btn btn-sm btn-icon text-danger cg-remove-member" ' +
                    'data-uid="' + m.uid + '" title="Remove"><i class="bx bx-trash fs-5"></i></button></td>' +
            '</tr>';
        }).join('');

        $('#CG_MembersBox').html(
            '<div class="table-responsive">' +
            '<table class="table table-sm align-middle mb-0">' +
            '<thead style="background:#f8f9fa;"><tr style="font-size:.76rem;text-transform:uppercase;color:#566a7f;">' +
            '<th>Customer</th>' +
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
        $('#CG_MemberInputs').html(inputs);
    }

    // ── Validation ───────────────────────────────────────────────────────────
    function _validateForm() {
        var valid  = true;
        var emailRe = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

        var email  = $.trim($('#CG_Email').val());
        if (email && !emailRe.test(email)) {
            $('#CG_Email').addClass('is-invalid');
            valid = false;
        } else {
            $('#CG_Email').removeClass('is-invalid');
        }

        var mobile = $.trim($('#CG_Mobile').val()).replace(/\D/g, '');
        var hasMobile = $.trim($('#CG_Mobile').val()).length > 0;
        if (hasMobile && mobile.length < 7) {
            $('#CG_Mobile').addClass('is-invalid');
            $('#CG_MobileErr').show();
            valid = false;
        } else {
            $('#CG_Mobile').removeClass('is-invalid');
            $('#CG_MobileErr').hide();
        }

        return valid;
    }

    // Clear validation on user input
    $(document).on('input', '#CG_Email',  function () { $(this).removeClass('is-invalid'); });
    $(document).on('input', '#CG_Mobile', function () {
        $(this).removeClass('is-invalid');
        $('#CG_MobileErr').hide();
    });

    // ── Save ──────────────────────────────────────────────────────────────────
    $(document).on('click', '#CGroupSaveBtn', function () {
        var $form     = $('#CGroupModalForm');
        var groupName = $.trim($('#CG_GroupName').val());
        if (!groupName) { $('#CG_GroupName').addClass('is-invalid').focus(); return; }
        $('#CG_GroupName').removeClass('is-invalid');

        if (!_validateForm()) return;

        var mode    = $form.data('mode');
        var context = $form.data('context') || 'groups_tab';
        var url  = (mode === 'edit') ? '/customers/updateGroupData' : '/customers/addGroupData';
        var data = $form.serializeArray();
        data.push({ name: CsrfName,    value: CsrfToken });
        data.push({ name: 'context',   value: context   });

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
                $('#CustomerGroupFormModal').modal('hide');
                if (typeof _onSaveSuccess === 'function') _onSaveSuccess(res);
            },
            error: function () {
                $spinner.remove();
                $btn.prop('disabled', false);
                showToastNotification('Request failed. Please try again.', 'error');
            }
        });
    });

    $(document).on('input', '#CG_GroupName', function () { $(this).removeClass('is-invalid'); });

    // ── HTML escape ───────────────────────────────────────────────────────────
    function _esc(s) {
        return String(s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

}(window, jQuery));
