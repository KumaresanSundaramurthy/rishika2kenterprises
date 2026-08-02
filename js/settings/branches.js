'use strict';

(function () {

    var _currentPage   = 1;
    var _currentFilter = {};
    var _modal              = null;
    var _searchTimer        = null;
    var _branchIsDirty      = false;
    var _branchIsCreateMode = false;

    /**
     * @returns {void}
     */
    function _initModal() {
        _modal = new bootstrap.Modal(document.getElementById('branchModal'));
    }

    /**
     * @param {number} page
     * @param {Object} filter
     * @returns {void}
     */
    function _loadPage(page, filter) {
        ajaxLoading(1);
        _currentPage   = page;
        _currentFilter = filter || {};
        $.ajax({
            url    : '/settings/branches/getPageDetails/' + page,
            method : 'POST',
            data   : { Filter: _currentFilter },
            success: function (res) {
                if (res.Error) { toastr.error(res.Message); return; }
                $('#BranchTableBody').html(res.RecordHtmlData);
                $('#BranchesPagination').html(res.Pagination);
            },
            error  : function () { toastr.error('Failed to load branches.'); },
            complete: function () { ajaxLoading(0); }
        });
    }

    /**
     * @returns {void}
     */
    function _resetForm() {
        _branchIsCreateMode = false;
        _branchIsDirty      = false;
        $('#branchUID').val(0);
        $('#branchModalTitle').text('New Branch');
        $('#branchName').val('').removeClass('is-invalid');
        $('#branchCode').val('').removeClass('is-invalid');
        $('#branchShortDesc').val('');
        $('#branchContact').val('');
        $('#branchMobile').val('');
        $('#branchEmail').val('');
        $('#branchGSTIN').val('');
        $('#branchAddr1').val('');
        $('#branchAddr2').val('');
        $('#branchPincode').val('');
        $('#branchIsHeadOffice').prop('checked', false);
    }

    /**
     * @param {Element} btn
     * @returns {void}
     */
    function _populateForm(btn) {
        var $b = $(btn);
        $('#branchUID').val($b.data('uid'));
        $('#branchModalTitle').text('Edit Branch');
        $('#branchName').val($b.data('name')).removeClass('is-invalid');
        $('#branchCode').val($b.data('code')).removeClass('is-invalid');
        $('#branchShortDesc').val($b.data('desc'));
        $('#branchContact').val($b.data('contact'));
        $('#branchMobile').val($b.data('mobile'));
        $('#branchEmail').val($b.data('email'));
        $('#branchGSTIN').val($b.data('gstin'));
        $('#branchAddr1').val($b.data('addr1'));
        $('#branchAddr2').val($b.data('addr2'));
        $('#branchPincode').val($b.data('pincode'));
        $('#branchIsHeadOffice').prop('checked', $b.data('hq') == 1);
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
        if (!code) { $('#branchCode').addClass('is-invalid'); valid = false; }
        else        { $('#branchCode').removeClass('is-invalid'); }
        if (!valid) return;

        ajaxLoading(1);
        $.ajax({
            url   : '/settings/branches/save',
            method: 'POST',
            data  : {
                BranchUID       : $('#branchUID').val(),
                Name            : name,
                BranchCode      : code,
                ShortDescription: $.trim($('#branchShortDesc').val()),
                ContactPerson   : $.trim($('#branchContact').val()),
                MobileNumber    : $.trim($('#branchMobile').val()),
                EmailAddress    : $.trim($('#branchEmail').val()),
                GSTIN           : $.trim($('#branchGSTIN').val()),
                AddressLine1    : $.trim($('#branchAddr1').val()),
                AddressLine2    : $.trim($('#branchAddr2').val()),
                Pincode         : $.trim($('#branchPincode').val()),
                IsHeadOffice    : $('#branchIsHeadOffice').is(':checked') ? 1 : 0,
                CurrentPage     : _currentPage,
                Filter          : _currentFilter,
            },
            success: function (res) {
                if (res.Error) { toastr.error(res.Message); return; }
                toastr.success(res.Message);
                _branchIsDirty      = false;
                _branchIsCreateMode = false;
                _modal.hide();
                $('#BranchTableBody').html(res.RecordHtmlData);
                $('#BranchesPagination').html(res.Pagination);
            },
            error  : function () { toastr.error('Failed to save branch.'); },
            complete: function () { ajaxLoading(0); }
        });
    }

    /**
     * @param {number} uid
     * @param {string} name
     * @returns {void}
     */
    function _deleteBranch(uid, name) {
        if (!confirm('Delete branch "' + name + '"? This cannot be undone.')) return;
        ajaxLoading(1);
        $.ajax({
            url   : '/settings/branches/delete',
            method: 'POST',
            data  : { BranchUID: uid },
            success: function (res) {
                if (res.Error) { toastr.error(res.Message); return; }
                toastr.success(res.Message);
                _loadPage(_currentPage, _currentFilter);
            },
            error  : function () { toastr.error('Failed to delete branch.'); },
            complete: function () { ajaxLoading(0); }
        });
    }

    $(document).ready(function () {

        _initModal();

        // New branch
        $('#btnNewBranch').on('click', function () {
            _resetForm();
            _modal.show();
            _branchIsCreateMode = true;
            _branchIsDirty      = false;
        });

        // Save
        $('#btnSaveBranch').on('click', _saveBranch);

        // Edit (delegated — rows are re-rendered after every load)
        $(document).on('click', '.branch-edit-btn', function () {
            _populateForm(this);
            _modal.show();
        });

        // Delete (delegated)
        $(document).on('click', '.branch-delete-btn', function () {
            _deleteBranch($(this).data('uid'), $(this).data('name'));
        });

        // Search
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

        // Pagination (delegated)
        $(document).on('click', '.PageRefresh', function (e) {
            e.preventDefault();
            _loadPage(_currentPage, _currentFilter);
        });

        // Branch code — force uppercase as user types
        $('#branchCode').on('input', function () {
            var pos = this.selectionStart;
            this.value = this.value.toUpperCase();
            this.setSelectionRange(pos, pos);
        });

        // ── Dirty-tracking listener ───────────────────────────────────────────
        $(document).on('input change', '#branchName, #branchCode, #branchShortDesc, #branchContact, #branchMobile, #branchEmail, #branchGSTIN, #branchAddr1, #branchAddr2, #branchPincode, #branchIsHeadOffice', function () {
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
                    _modal.hide();
                }
            });
        });

    });

})();
