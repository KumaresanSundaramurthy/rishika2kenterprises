/**
 * VendorGroupForm — modal for add / edit Vendor Groups.
 *
 * Usage:
 *   VendorGroupForm.open('add', null, { onSaveSuccess: fn });
 *   VendorGroupForm.open('edit', groupUID, { onSaveSuccess: fn });
 *
 * Depends on: address.js (csc_loadStates, csc_loadCities), select2, showToastNotification, Swal
 */
(function (window, $) {
    'use strict';

    // ── State ────────────────────────────────────────────────────────────────
    var _editGroupUID  = 0;
    var _onSaveSuccess = null;
    var _members       = [];
    var _primaryUID    = 0;

    // ── Country name lookup (ISO2 → display name) ────────────────────────────
    var _countryMap = {
        'IN': 'India', 'US': 'United States', 'GB': 'United Kingdom',
        'AE': 'United Arab Emirates', 'SG': 'Singapore', 'AU': 'Australia',
        'CA': 'Canada', 'MY': 'Malaysia', 'NZ': 'New Zealand', 'ZA': 'South Africa',
    };

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
        _initMemberSearch();

        if (type === 'add') {
            _loadCountryAndStates(null, null);
            $('#VendorGroupFormModal').modal('show');
            return;
        }

        // edit — fetch existing data
        _editGroupUID = uid || 0;
        $.ajax({
            url   : '/vendors/getGroupForModal/' + _editGroupUID,
            method: 'GET',
            cache : false,
            success: function (res) {
                if (res.Error) {
                    showAlertMessageSwal('error', '', res.Message || 'Failed to load group.');
                    return;
                }
                _populate(res.Data, res.Members || []);
                $('#VendorGroupFormModal').modal('show');
            },
            error: function () {
                showAlertMessageSwal('error', '', 'Failed to load group.');
            }
        });
    }

    // ── Reset form to clean state ─────────────────────────────────────────────
    function _reset() {
        var $form = $('#VGroupModalForm');
        if ($form.length) $form[0].reset();
        $('#VGroupUID').val('');
        $('#VG_State').empty().append('<option value="">-- Select State --</option>');
        $('#VG_City').empty().append('<option value="">-- Select City --</option>');
        _members    = [];
        _primaryUID = 0;
        _renderMembers();
    }

    // ── Load country name and state dropdown ─────────────────────────────────
    function _loadCountryAndStates(selectedState, onStateLoaded) {
        var iso2 = $('#VG_CountryISO2').val() ||
                   (typeof OrgCountryISO2 !== 'undefined' ? OrgCountryISO2 : 'IN');

        // Country display name
        $('#VG_Country').val(_countryMap[iso2.toUpperCase()] || iso2);

        // State dropdown
        if (typeof csc_loadStates === 'function') {
            csc_loadStates('VG_State', iso2, selectedState || '', function () {
                if (typeof onStateLoaded === 'function') onStateLoaded();
            });
        }

        // City clears until state chosen
        $('#VG_State').off('change.vgrp').on('change.vgrp', function () {
            var stISO2 = $(this).find(':selected').data('iso2') || '';
            $('#VG_City').empty().append('<option value="">-- Select City --</option>');
            if (stISO2 && typeof csc_loadCities === 'function') {
                csc_loadCities('VG_City', iso2, stISO2, '', '');
            }
        });
    }

    // ── Populate form for edit ────────────────────────────────────────────────
    function _populate(d, members) {
        _editGroupUID = d.GroupUID || 0;
        $('#VGroupUID').val(_editGroupUID);
        $('#VG_GroupName').val(d.GroupName      || '');
        $('#VG_GroupCode').val(d.GroupCode      || '');
        $('#VG_GroupType').val(d.GroupType      || '');
        $('#VG_ContactPerson').val(d.ContactPerson || '');
        $('#VG_Mobile').val(d.Mobile            || '');
        $('#VG_Email').val(d.Email              || '');
        $('#VG_GSTNo').val(d.GSTNo              || '');
        $('#VG_Address').val(d.Address          || '');
        $('#VG_Notes').val(d.Notes              || '');

        // Country / State / City
        _loadCountryAndStates(d.State || '', function () {
            var $stSel  = $('#VG_State');
            var stISO2  = $stSel.find(':selected').data('iso2') || '';
            if (stISO2 && d.City && typeof csc_loadCities === 'function') {
                var iso2 = $('#VG_CountryISO2').val() || 'IN';
                csc_loadCities('VG_City', iso2, stISO2, d.City, '');
            }
        });

        // Members
        _members    = [];
        _primaryUID = 0;
        (members || []).forEach(function (m) {
            var isPri = parseInt(m.IsGroupPrimary || 0) === 1;
            _members.push({
                uid     : parseInt(m.VendorUID),
                name    : m.Name         || '',
                area    : m.Area         || '',
                mobile  : m.MobileNumber || '',
                balance : parseFloat(m.Balance   || 0),
                balType : m.BalanceType  || 'Credit',
                primary : isPri,
            });
            if (isPri) _primaryUID = parseInt(m.VendorUID);
        });
        _renderMembers();
    }

    // ── Member search (select2 AJAX) ─────────────────────────────────────────
    function _initMemberSearch() {
        var $sel = $('#VG_MemberSearch');
        if ($sel.data('select2')) { try { $sel.select2('destroy'); } catch (e) {} }
        $sel.val(null);
        $sel.select2({
            placeholder       : 'Search vendor by name, mobile...',
            minimumInputLength: 1,
            allowClear        : true,
            width             : '100%',
            dropdownParent    : $('#VendorGroupFormModal'),
            ajax: {
                url     : '/vendors/searchVendors',
                dataType: 'json',
                delay   : 300,
                data    : function (p) { return { term: p.term }; },
                processResults: function (d) {
                    return { results: (d.Lists || []).map(function (v) {
                        return { id: v.id, text: v.text, area: v.area || '', mobile: v.mobile || '' };
                    })};
                },
            },
            escapeMarkup  : function (m) { return m; },
            templateResult: function (d) {
                if (!d.id) return d.text;
                return '<div style="font-size:.85rem;font-weight:600;">' + _esc(d.text) + '</div>';
            },
        });
    }

    // ── Add member button ─────────────────────────────────────────────────────
    $(document).on('click', '#VG_BtnAddMember', function () {
        var sel  = $('#VG_MemberSearch');
        var data = sel.select2('data')[0];
        if (!data || !data.id) { showToastNotification('Please select a vendor first.', 'warning'); return; }
        var uid = parseInt(data.id);
        if (_members.some(function (m) { return m.uid === uid; })) {
            showToastNotification('Already in group.', 'info');
            return;
        }
        var isFirst = _members.length === 0;
        _members.push({
            uid: uid, name: data.text, area: data.area || '', mobile: data.mobile || '',
            balance: 0, balType: 'Credit', primary: isFirst,
        });
        if (isFirst) _primaryUID = uid;
        _renderMembers();
        sel.val(null).trigger('change');
    });

    // ── Set primary ───────────────────────────────────────────────────────────
    $(document).on('click', '.vg-set-primary', function () {
        _primaryUID = parseInt($(this).data('uid'));
        _members.forEach(function (m) { m.primary = (m.uid === _primaryUID); });
        _renderMembers();
    });

    // ── Remove member ─────────────────────────────────────────────────────────
    $(document).on('click', '.vg-remove-member', function () {
        var uid = parseInt($(this).data('uid'));
        _members = _members.filter(function (m) { return m.uid !== uid; });
        if (_primaryUID === uid) {
            _primaryUID = _members.length ? _members[0].uid : 0;
            if (_members.length) _members[0].primary = true;
        }
        _renderMembers();
    });

    // ── Render members table ─────────────────────────────────────────────────
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
                '<td><i class="bx bx-drag text-muted" style="cursor:grab;font-size:1rem;"></i></td>' +
                '<td class="fw-semibold" style="font-size:.85rem;">' + _esc(m.name) + '</td>' +
                '<td style="font-size:.8rem;">' + _esc(m.area || '—') + '</td>' +
                '<td style="font-size:.8rem;">' + _esc(m.mobile || '—') + '</td>' +
                '<td class="text-end" style="font-size:.8rem;color:' + balCol + ';">' + parseFloat(m.balance).toFixed(2) + '</td>' +
                '<td class="text-center">' +
                    '<button type="button" class="btn btn-sm btn-icon ' +
                    (isPri ? 'btn-warning' : 'btn-outline-secondary') +
                    ' vg-set-primary" data-uid="' + m.uid + '" title="Set as primary">' +
                    '<i class="bx bx-star"></i></button>' +
                '</td>' +
                '<td>' +
                    '<button type="button" class="btn btn-sm btn-icon btn-outline-danger vg-remove-member" data-uid="' + m.uid + '" title="Remove">' +
                    '<i class="bx bx-x"></i></button>' +
                '</td>' +
            '</tr>';
        }).join('');

        $('#VG_MembersBox').html(
            '<div class="table-responsive">' +
            '<table class="table table-sm align-middle mb-0">' +
            '<thead style="background:#f8f9fa;"><tr style="font-size:.76rem;text-transform:uppercase;color:#566a7f;">' +
            '<th style="width:36px;"></th><th>Vendor</th>' +
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

    // ── Save button ───────────────────────────────────────────────────────────
    $(document).on('click', '#VGroupSaveBtn', function () {
        var $form     = $('#VGroupModalForm');
        var groupName = $.trim($('#VG_GroupName').val());
        if (!groupName) {
            $('#VG_GroupName').addClass('is-invalid').focus();
            return;
        }
        $('#VG_GroupName').removeClass('is-invalid');

        var mode = $form.data('mode');
        var url  = (mode === 'edit') ? '/vendors/updateGroupData' : '/vendors/addGroupData';
        var data = $form.serializeArray();
        data.push({ name: CsrfName, value: CsrfToken });

        var $btn = $(this).prop('disabled', true);
        var $spinner = $('<span class="spinner-border spinner-border-sm me-1" role="status"></span>');
        $btn.prepend($spinner);
        AjaxLoading = 0;

        $.ajax({
            url   : url,
            method: 'POST',
            data  : data,
            success: function (res) {
                AjaxLoading = 1;
                $spinner.remove();
                $btn.prop('disabled', false);
                CsrfToken = res.NewCsrfToken || CsrfToken;
                if (res.Error) { showToastNotification(res.Message, 'error'); return; }
                showToastNotification(res.Message, 'success');
                $('#VendorGroupFormModal').modal('hide');
                if (typeof _onSaveSuccess === 'function') _onSaveSuccess(res);
            },
            error: function () {
                AjaxLoading = 1;
                $spinner.remove();
                $btn.prop('disabled', false);
                showToastNotification('Request failed. Please try again.', 'error');
            }
        });
    });

    // ── Clear invalid on input ────────────────────────────────────────────────
    $(document).on('input', '#VG_GroupName', function () {
        $(this).removeClass('is-invalid');
    });

    // ── HTML escape helper ────────────────────────────────────────────────────
    function _esc(s) {
        return String(s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

}(window, jQuery));
