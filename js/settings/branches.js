'use strict';

(function () {

    var _currentPage   = 1;
    var _currentFilter = {};
    var _searchTimer        = null;
    var _branchIsDirty      = false;
    var _branchIsCreateMode = false;
    var _branchAddr         = { Line1: '', Line2: '', Pincode: '', StateId: '', StateName: '', StateISO2: '', CityId: '', CityName: '' };
    var _branchAddrOpen     = false;
    var _justSaved          = false; // flag: modal was hidden after a successful save

    // ── Phone CC picker config (reused on init / reset / populate) ────────────
    var _bmCCCfg = {
        btn      : '#BM_MobileCCBtn',
        dropdown : '#BM_CCDropdown',
        search   : '#BM_CCSearch',
        list     : '#BM_CCList',
        codeInput: '#BM_CountryCode',
        iso2Input: '#BM_CountryISO2',
    };

    /**
     * @returns {void}
     */
    function _initModal() {
        PhoneCCDropdown.init(_bmCCCfg);
    }

    /**
     * Render the address click-box from _branchAddr state.
     * @returns {void}
     */
    function _renderAddrBox() {
        var hasAddr = _branchAddr.Line1 || _branchAddr.Line2 || _branchAddr.Pincode;
        if (hasAddr) {
            var lines = [];
            if (_branchAddr.Line1) lines.push(_branchAddr.Line1);
            if (_branchAddr.Line2) lines.push(_branchAddr.Line2);
            var loc = [_branchAddr.CityName, _branchAddr.StateName, _branchAddr.Pincode].filter(Boolean).join(', ');
            if (loc) lines.push(loc);
            $('#branchAddrText').text(lines.join(', ')).removeClass('d-none');
            $('#branchAddrPlaceholder').addClass('d-none');
        } else {
            $('#branchAddrText').text('').addClass('d-none');
            $('#branchAddrPlaceholder').removeClass('d-none');
        }
    }

    /**
     * Show a spinner row inside the branch table while data is loading.
     * @returns {void}
     */
    function _showTableSpinner() {
        var cols = $('#BranchTableBody').closest('table').find('thead tr:first th:visible').length || 6;
        $('#BranchTableBody').html(
            '<tr><td colspan="' + cols + '" class="text-center py-4">' +
            '<span class="spinner-border spinner-border-sm text-primary me-2"></span>' +
            '<span class="text-muted" style="font-size:.85rem;">Loading...</span>' +
            '</td></tr>'
        );
        $('#BranchesPagination').empty();
    }

    /**
     * @param {number} page
     * @param {Object} filter
     * @returns {void}
     */
    function _loadPage(page, filter) {
        _currentPage   = page;
        _currentFilter = filter || {};
        ajaxLoading(0);
        _showTableSpinner();
        $.ajax({
            url    : '/settings/branches/getPageDetails/' + page,
            method : 'POST',
            data   : { Filter: _currentFilter },
            success: function (res) {
                if (res.Error) { showToastNotification(res.Message, 'error'); return; }
                $('#BranchTableBody').html(res.RecordHtmlData);
                $('#BranchesPagination').html(res.Pagination);
            },
            error  : function () { showToastNotification('Failed to load branches.', 'error'); },
            complete: function () { ajaxLoading(1); }
        });
    }

    /**
     * @returns {void}
     */
    function _resetForm() {
        _branchIsCreateMode = false;
        _branchIsDirty      = false;
        _branchAddr         = { Line1: '', Line2: '', Pincode: '', StateId: '', StateName: '', StateISO2: '', CityId: '', CityName: '' };
        $('#branchUID').val(0);
        $('#branchModalTitle').text('New Branch');
        $('#branchName').val('').removeClass('is-invalid');
        $('#branchCode').val('').removeClass('is-invalid');
        $('#branchShortDesc').val('');
        $('#branchTypeUID').val('');
        $('#branchContact').val('');
        $('#branchPAN').val('');
        $('#branchMobile').val('');
        $('#branchAltNumber').val('');
        PhoneCCDropdown.setFromISO2(_bmCCCfg, (typeof _bmOrgISO2 !== 'undefined' ? _bmOrgISO2 : 'IN'));
        $('#branchEmail').val('');
        $('#branchGSTIN').val('');
        $('#branchGSTINValidatedMsg').addClass('d-none');
        $('#branchGSTINValidated').val(0);
        $('#branchLandmark').val('');
        $('#branchIsWarehouse').prop('checked', false);
        $('#branchIsDispatchPoint').prop('checked', true);
        $('#branchIsSalesPoint').prop('checked', true);
        $('#branchIsServiceCenter').prop('checked', false);
        $('#branchIsHeadOffice').prop('checked', false);
        _renderAddrBox();
    }

    /**
     * @param {Element} btn
     * @returns {void}
     */
    function _populateForm(btn) {
        var $b = $(btn);
        _branchAddr = {
            Line1    : $b.data('addr1')     || '',
            Line2    : $b.data('addr2')     || '',
            Pincode  : $b.data('pincode')   || '',
            StateId  : $b.data('stateid')   || '',
            StateName: $b.data('statetext') || '',
            StateISO2: '',
            CityId   : $b.data('cityid')    || '',
            CityName : $b.data('citytext')  || '',
        };
        var storedCode = $b.data('countrycode') || '';
        var storedISO2 = $b.data('countryiso2') || '';
        $('#branchUID').val($b.data('uid'));
        $('#branchModalTitle').text('Edit Branch');
        $('#branchName').val($b.data('name')).removeClass('is-invalid');
        $('#branchCode').val($b.data('code')).removeClass('is-invalid');
        $('#branchShortDesc').val($b.data('desc'));
        $('#branchTypeUID').val($b.data('branchtypeuid') || '');
        $('#branchContact').val($b.data('contact'));
        $('#branchPAN').val($b.data('pan') || '');
        $('#branchMobile').val($b.data('mobile'));
        $('#branchAltNumber').val($b.data('altno') || '');
        // Restore CC button: use stored ISO2 when available for accuracy, else set text directly
        if (storedISO2) {
            PhoneCCDropdown.setFromISO2(_bmCCCfg, storedISO2);
        } else if (storedCode) {
            $('#BM_MobileCCBtn').text(storedCode);
            $('#BM_CountryCode').val(storedCode);
        } else {
            PhoneCCDropdown.setFromISO2(_bmCCCfg, (typeof _bmOrgISO2 !== 'undefined' ? _bmOrgISO2 : 'IN'));
        }
        $('#branchEmail').val($b.data('email'));
        $('#branchGSTIN').val($b.data('gstin'));
        $('#branchLandmark').val($b.data('landmark') || '');
        $('#branchIsWarehouse').prop('checked',     $b.data('warehouse') == 1);
        $('#branchIsDispatchPoint').prop('checked', $b.data('dispatch')  == 1);
        $('#branchIsSalesPoint').prop('checked',    $b.data('sales')     == 1);
        $('#branchIsServiceCenter').prop('checked', $b.data('service')   == 1);
        $('#branchIsHeadOffice').prop('checked', $b.data('hq') == 1);
        _renderAddrBox();
        // Show validated badge if GSTIN is already set
        if ($.trim($b.data('gstin') || '')) {
            $('#branchGSTINValidatedMsg').removeClass('d-none');
            $('#branchGSTINValidated').val(1);
        } else {
            $('#branchGSTINValidatedMsg').addClass('d-none');
            $('#branchGSTINValidated').val(0);
        }
    }

    /**
     * @returns {void}
     */
    function _saveBranch() {
        var name = $.trim($('#branchName').val());
        var code = $.trim($('#branchCode').val());
        var valid = true;

        if (!name) { $('#branchName').addClass('is-invalid'); valid = false; }
        else        { $('#branchName').removeClass('is-invalid'); }
        if (!code) {
            $('#branchCodeError').text('Branch code is required.');
            $('#branchCode').addClass('is-invalid');
            valid = false;
        } else {
            $('#branchCode').removeClass('is-invalid');
        }
        if (!valid) return;

        var $saveBtn = $('#btnSaveBranch');
        $saveBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Saving...');

        $.ajax({
            url   : '/settings/branches/save',
            method: 'POST',
            data  : {
                BranchUID       : $('#branchUID').val(),
                Name            : name,
                BranchCode      : code,
                ShortDescription: $.trim($('#branchShortDesc').val()),
                BranchTypeUID   : $('#branchTypeUID').val() || '',
                ContactPerson   : $.trim($('#branchContact').val()),
                PANNumber       : $.trim($('#branchPAN').val()).toUpperCase(),
                MobileNumber    : $.trim($('#branchMobile').val()),
                AlternateNumber : $.trim($('#branchAltNumber').val()),
                CountryCode     : $('#BM_CountryCode').val(),
                CountryISO2     : $('#BM_CountryISO2').val(),
                EmailAddress    : $.trim($('#branchEmail').val()),
                GSTIN           : $.trim($('#branchGSTIN').val()),
                AddressLine1    : _branchAddr.Line1,
                AddressLine2    : _branchAddr.Line2,
                Pincode         : _branchAddr.Pincode,
                StateId         : _branchAddr.StateId,
                StateText       : _branchAddr.StateName,
                CityId          : _branchAddr.CityId,
                CityText        : _branchAddr.CityName,
                Landmark        : $.trim($('#branchLandmark').val()),
                IsWarehouse     : $('#branchIsWarehouse').is(':checked')     ? 1 : 0,
                IsDispatchPoint : $('#branchIsDispatchPoint').is(':checked') ? 1 : 0,
                IsSalesPoint    : $('#branchIsSalesPoint').is(':checked')    ? 1 : 0,
                IsServiceCenter : $('#branchIsServiceCenter').is(':checked') ? 1 : 0,
                IsHeadOffice    : $('#branchIsHeadOffice').is(':checked') ? 1 : 0,
            },
            success: function (res) {
                $saveBtn.prop('disabled', false).html('<i class="bx bx-check me-1"></i>Save');
                if (res.Error) {
                    if (res.Message && res.Message.indexOf('already in use') !== -1) {
                        $('#branchCodeError').text(res.Message);
                        $('#branchCode').addClass('is-invalid').focus();
                    } else {
                        showToastNotification(res.Message, 'error');
                    }
                    return;
                }
                _justSaved          = true;
                _branchIsDirty      = false;
                _branchIsCreateMode = false;
                bootstrap.Modal.getOrCreateInstance(document.getElementById('branchModal')).hide();
                showToastNotification(res.Message, 'success');
            },
            error: function () {
                $saveBtn.prop('disabled', false).html('<i class="bx bx-check me-1"></i>Save');
                showToastNotification('Failed to save branch.', 'error');
            }
        });
    }

    /**
     * @param {number} uid
     * @param {string} name
     * @returns {void}
     */
    function _deleteBranch(uid, name) {
        if (!confirm('Delete branch "' + name + '"? This cannot be undone.')) return;
        $.ajax({
            url   : '/settings/branches/delete',
            method: 'POST',
            data  : { BranchUID: uid },
            success: function (res) {
                if (res.Error) { showToastNotification(res.Message, 'error'); return; }
                hideUIBlock();
                showToastNotification(res.Message, 'success');
                _loadPage(_currentPage, _currentFilter);
            },
            error  : function () { showToastNotification('Failed to delete branch.', 'error'); },
        });
    }

    /**
     * Immediately patch the HQ badge and button visibility in the DOM so
     * the user sees the change without waiting for the replica to catch up.
     * @param {number} newHqUid
     * @returns {void}
     */
    function _patchHQInDOM(newHqUid) {
        var hqBadge = '<span class="badge bg-label-primary ms-1" style="font-size:.7rem;">HQ</span>';
        $('#BranchTableBody tr[data-uid]').each(function () {
            var rowUid = parseInt($(this).data('uid'), 10);
            var isNew  = rowUid === newHqUid;
            // Toggle HQ badge in name cell
            $(this).find('.badge.bg-label-primary').remove();
            if (isNew) { $(this).find('td:eq(1) .d-flex').append(hqBadge); }
            // Toggle delete + divider + set-hq buttons (hidden for HQ branch)
            var $deleteLi = $(this).find('.branch-delete-btn').closest('li');
            $deleteLi.toggleClass('d-none', isNew);
            $deleteLi.next('li').toggleClass('d-none', isNew); // divider <li>
            $(this).find('.branch-set-hq-btn').closest('li').toggleClass('d-none', isNew);
        });
    }

    /**
     * @param {number} uid
     * @returns {void}
     */
    function _setHeadOffice(uid) {
        $.ajax({
            url   : '/settings/branches/setHeadOffice',
            method: 'POST',
            data  : { BranchUID: uid },
            success: function (res) {
                if (res.Error) { showToastNotification(res.Message, 'error'); return; }
                hideUIBlock();
                showToastNotification(res.Message, 'success');
                // Patch the DOM immediately so user sees the HQ change right away
                _patchHQInDOM(uid);
                // Reload from server after a short pause (lets the read replica catch up)
                setTimeout(function () {
                    _loadPage(_currentPage, _currentFilter);
                }, 600);
            },
            error  : function () { showToastNotification('Failed to update head office.', 'error'); },
        });
    }

    // ── GSTIN Fetch Config — branch-specific confirm overlay ─────────────────
    window.gstinFetchConfig = {
        /**
         * @param {Object} resp
         * @returns {string}
         */
        confirmRows: function (resp) {
            var rows = '';
            var r = function (lbl, val) {
                return '<div class="gstin-confirm-row"><span class="gstin-confirm-lbl">' + lbl + '</span><span>' + val + '</span></div>';
            };
            if (resp.LegalName)    rows += r('Legal Name', resp.LegalName);
            if (resp.TradeName)    rows += r('Trade Name', resp.TradeName);
            if (resp.PAN)          rows += r('PAN',        resp.PAN);
            if (resp.Status)       rows += r('Status',     resp.Status);
            if (resp.StateName)    rows += r('State',      resp.StateName);
            if (resp.AddressLine1) rows += r('Address',    resp.AddressLine1 + (resp.AddressLine2 ? ', ' + resp.AddressLine2 : '') + (resp.Pincode ? ' - ' + resp.Pincode : ''));
            return rows || '<div class="text-muted small">No additional details available.</div>';
        },
        /**
         * @param {Object} resp
         * @returns {void}
         */
        onConfirm: function (resp) {
            if ((resp.TradeName || resp.LegalName) && !$.trim($('#branchName').val())) {
                $('#branchName').val(resp.TradeName || resp.LegalName);
            }
            if (resp.AddressLine1 || resp.AddressLine2 || resp.Pincode) {
                _branchAddr.Line1   = resp.AddressLine1 || '';
                _branchAddr.Line2   = resp.AddressLine2 || '';
                _branchAddr.Pincode = resp.Pincode      || '';
                // Keep billingAddrData in sync so the addr modal shows this data when opened
                billingAddrData = { Line1: _branchAddr.Line1, Line2: _branchAddr.Line2, Pincode: _branchAddr.Pincode, UID: 0 };
                _renderAddrBox();
            }
        },
        /**
         * @returns {void}
         */
        onValidated: function () {
            $('#branchGSTINValidatedMsg').removeClass('d-none');
            $('#branchGSTINValidated').val(1);
        },
    };

    $(document).ready(function () {

        _initModal();

        // ── New branch ────────────────────────────────────────────────────────
        $('#btnNewBranch').on('click', function () {
            _resetForm();
            bootstrap.Modal.getOrCreateInstance(document.getElementById('branchModal')).show();
            _branchIsCreateMode = true;
            _branchIsDirty      = false;
        });

        // ── Save branch ───────────────────────────────────────────────────────
        $('#btnSaveBranch').on('click', _saveBranch);

        // ── Edit (delegated) ──────────────────────────────────────────────────
        $(document).on('click', '.branch-edit-btn', function () {
            _resetForm();
            _populateForm(this);
            bootstrap.Modal.getOrCreateInstance(document.getElementById('branchModal')).show();
        });

        // ── Delete (delegated) ────────────────────────────────────────────────
        $(document).on('click', '.branch-delete-btn', function () {
            _deleteBranch($(this).data('uid'), $(this).data('name'));
        });

        // ── Toggle status (delegated) ─────────────────────────────────────────
        $(document).on('click', '.branch-toggle-status-btn', function () {
            var uid      = $(this).data('uid');
            var isActive = parseInt($(this).data('active'));
            var name     = $(this).data('name');
            var action   = isActive ? 'deactivate' : 'activate';
            Swal.fire({
                title            : isActive ? 'Set Inactive?' : 'Set Active?',
                text             : 'Are you sure you want to ' + action + ' "' + name + '"?',
                icon             : 'warning',
                showCancelButton : true,
                confirmButtonText: isActive ? 'Yes, Deactivate' : 'Yes, Activate',
                cancelButtonText : 'Cancel',
                confirmButtonColor: isActive ? '#d33' : '#3085d6',
                cancelButtonColor : '#6c757d',
            }).then(function (result) {
                if (!result.isConfirmed) return;
                $.ajax({
                    url   : '/settings/branches/toggleStatus',
                    method: 'POST',
                    data  : { BranchUID: uid, IsActive: isActive },
                    success: function (res) {
                        if (res.Error) { showToastNotification(res.Message, 'error'); return; }
                        hideUIBlock();
                        showToastNotification(res.Message, 'success');
                        _loadPage(_currentPage, _currentFilter);
                    },
                    error  : function () { showToastNotification('Failed to update branch status.', 'error'); },
                });
            });
        });

        // ── Set as Head Office (delegated) ────────────────────────────────────
        $(document).on('click', '.branch-set-hq-btn', function () {
            _setHeadOffice($(this).data('uid'));
        });

        // ── Address box click → open common address modal ─────────────────────
        $(document).on('click', '#branchAddrBox', function () {
            billingAddrData = (_branchAddr.Line1 || _branchAddr.Line2 || _branchAddr.Pincode)
                ? {
                    UID      : 0,
                    Line1    : _branchAddr.Line1,
                    Line2    : _branchAddr.Line2,
                    Pincode  : _branchAddr.Pincode,
                    StateId  : _branchAddr.StateId,
                    StateName: _branchAddr.StateName,
                    StateISO2: _branchAddr.StateISO2,
                    CityId   : _branchAddr.CityId,
                    CityName : _branchAddr.CityName,
                }
                : null;
            _branchAddrOpen = true;
            openAddressModal(1);
        });

        // ── After save: reload table once branch modal is fully hidden ─────────
        $(document).on('hidden.bs.modal', '#branchModal', function () {
            if (!_justSaved) return;
            _justSaved = false;
            _loadPage(_currentPage, _currentFilter);
        });

        // ── Read back from common addr modal when it closes ───────────────────
        $(document).on('hidden.bs.modal', '#addEditAddressModal', function () {
            if (!_branchAddrOpen) return;
            _branchAddrOpen = false;
            if (billingAddrData) {
                _branchAddr.Line1     = billingAddrData.Line1     || '';
                _branchAddr.Line2     = billingAddrData.Line2     || '';
                _branchAddr.Pincode   = billingAddrData.Pincode   || '';
                _branchAddr.StateId   = billingAddrData.StateId   || '';
                _branchAddr.StateName = billingAddrData.StateName || '';
                _branchAddr.StateISO2 = billingAddrData.StateISO2 || '';
                _branchAddr.CityId    = billingAddrData.CityId    || '';
                _branchAddr.CityName  = billingAddrData.CityName  || '';
                _renderAddrBox();
                if (_branchIsCreateMode) _branchIsDirty = true;
            }
        });

        // ── Search ────────────────────────────────────────────────────────────
        $('#SearchDetails').on('input', function () {
            clearTimeout(_searchTimer);
            var val = $.trim($(this).val());
            $('#clearSearch').toggleClass('d-none', !val);
            _searchTimer = setTimeout(function () {
                _loadPage(1, val ? { Search: val } : {});
            }, 400);
        });

        $('#clearSearch').on('click', function () {
            $('#SearchDetails').val('');
            $(this).addClass('d-none');
            _loadPage(1, {});
        });

        // ── Pagination / refresh (delegated) ─────────────────────────────────
        $(document).on('click', '.PageRefresh', function (e) {
            e.preventDefault();
            _loadPage(_currentPage, _currentFilter);
        });

        // ── Branch code — force uppercase ─────────────────────────────────────
        $('#branchCode').on('input', function () {
            var pos = this.selectionStart;
            this.value = this.value.toUpperCase();
            this.setSelectionRange(pos, pos);
        });

        // ── Dirty-tracking ────────────────────────────────────────────────────
        $(document).on('input change', '#branchName, #branchCode, #branchShortDesc, #branchTypeUID, #branchContact, #branchPAN, #branchMobile, #branchAltNumber, #branchEmail, #branchGSTIN, #branchLandmark, #branchIsWarehouse, #branchIsDispatchPoint, #branchIsSalesPoint, #branchIsServiceCenter, #branchIsHeadOffice', function () {
            if (_branchIsCreateMode) _branchIsDirty = true;
        });
        $(document).on('click', '#branchAddrBox', function () {
            if (_branchIsCreateMode) _branchIsDirty = true;
        });

        // ── Unsaved-changes guard ─────────────────────────────────────────────
        $(document).on('hide.bs.modal', '#branchModal', function (e) {
            if (!_branchIsDirty || !_branchIsCreateMode) return;
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
                    _branchIsDirty      = false;
                    _branchIsCreateMode = false;
                    bootstrap.Modal.getOrCreateInstance(document.getElementById('branchModal')).hide();
                }
            });
        });

    });

})();
