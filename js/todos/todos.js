/* global $, Swal, flatpickr, CsrfName, CsrfToken, _todoMyUID, _todoInitStats, _transListDateFormat, ajaxLoading */
(function () {
    'use strict';

    // ── State ──────────────────────────────────────────────────────────────────
    var _currentPage   = 1;
    var _activeTab     = 'open';
    var _activePri     = '';
    var _myOnly        = true;
    var _searchVal     = '';
    var _searchTimer   = null;
    var _duePicker     = null;

    // ── Helpers ────────────────────────────────────────────────────────────────

    /**
     * Returns current filter object sent to server.
     * @returns {{Tab:string,Priority:string,MyOnly:boolean,Search:string}}
     */
    function _buildFilter() {
        return {
            Tab:      _activeTab,
            Priority: _activePri,
            MyOnly:   _myOnly,
            Search:   _searchVal,
        };
    }

    /**
     * @param {string} val
     * @returns {string}
     */
    function _esc(val) {
        var d = document.createElement('div');
        d.textContent = val;
        return d.innerHTML;
    }

    /**
     * Refreshes CSRF token pair from response if server sends updated tokens.
     * @param {object} res
     * @returns {void}
     */
    function _refreshCsrf(res) {
        if (res.csrf_name)  CsrfName  = res.csrf_name;
        if (res.csrf_token) CsrfToken = res.csrf_token;
    }

    // ── Tab badge counts ───────────────────────────────────────────────────────

    /**
     * Updates stat cards and tab badges from server stats object.
     * @param {{active:number,overdue:number,today:number,completed:number}} stats
     * @returns {void}
     */
    function _updateStats(stats) {
        if (!stats) return;
        document.getElementById('tStatActive').textContent    = stats.active    || 0;
        document.getElementById('tStatOverdue').textContent   = stats.overdue   || 0;
        document.getElementById('tStatToday').textContent     = stats.today     || 0;
        document.getElementById('tStatCompleted').textContent = stats.completed || 0;
        document.getElementById('tBadgeOpen').textContent     = stats.active    || 0;
        document.getElementById('tBadgeOverdue').textContent  = stats.overdue   || 0;
        document.getElementById('tBadgeToday').textContent    = stats.today     || 0;
    }

    // ── Fetch list ─────────────────────────────────────────────────────────────

    /**
     * @param {number} pageNo
     * @returns {void}
     */
    function _fetch(pageNo) {
        ajaxLoading(1);
        _currentPage = pageNo || 1;
        var postData = { Filter: _buildFilter(), [CsrfName]: CsrfToken };
        $.ajax({
            url:  '/todos/getPageDetails/' + _currentPage,
            type: 'POST',
            data: postData,
            success: function (res) {
                _refreshCsrf(res);
                if (res.Error) { toastr.error(res.Message || 'Failed to load tasks.'); return; }
                document.getElementById('tTableBody').innerHTML = res.RecordHtmlData;
                document.getElementById('tPagination').innerHTML = res.Pagination || '';
                _bindRowActions();
                _fetchStats();
            },
            error: function () { toastr.error('Network error. Please try again.'); },
            complete: function () { ajaxLoading(0); },
        });
    }

    /**
     * @returns {void}
     */
    function _fetchStats() {
        $.ajax({
            url:  '/todos/getStats',
            type: 'POST',
            data: { [CsrfName]: CsrfToken },
            success: function (res) {
                _refreshCsrf(res);
                if (!res.Error) _updateStats(res.Data);
            },
        });
    }

    // ── Modal open/populate ────────────────────────────────────────────────────

    /**
     * Resets the modal form to blank state for creating a new task.
     * @returns {void}
     */
    function _resetForm() {
        document.getElementById('tTodoUID').value     = '0';
        document.getElementById('tTitle').value       = '';
        document.getElementById('tDescription').value = '';
        document.getElementById('tPriority').value    = 'Medium';
        document.getElementById('tStatus').value      = 'Open';
        document.getElementById('tAssignedTo').value  = String(_todoMyUID);
        document.getElementById('tRefType').value     = 'General';
        document.getElementById('tRefLabel').value    = '';
        document.getElementById('tRefUID').value      = '0';
        document.getElementById('tIsPrivate').checked = false;
        document.getElementById('tDueDateDisplay').value = '';
        document.getElementById('tDueDate').value         = '';
        document.getElementById('tRefLabelWrap').style.display = 'none';
        if (_duePicker) _duePicker.clear();
        document.getElementById('tTitle').classList.remove('is-invalid');
        document.getElementById('todoModalTitle').textContent = 'New Task';
    }

    /**
     * Opens the modal pre-filled with an existing task's data.
     * @param {number} todoUID
     * @returns {void}
     */
    function _openEdit(todoUID) {
        ajaxLoading(1);
        $.ajax({
            url:  '/todos/getDetail',
            type: 'POST',
            data: { TodoUID: todoUID, [CsrfName]: CsrfToken },
            success: function (res) {
                _refreshCsrf(res);
                if (res.Error || !res.Data) { toastr.error(res.Message || 'Failed to load task.'); return; }
                var d = res.Data;
                _resetForm();
                document.getElementById('tTodoUID').value    = d.TodoUID;
                document.getElementById('tTitle').value      = d.Title        || '';
                document.getElementById('tDescription').value = d.Description || '';
                document.getElementById('tPriority').value   = d.Priority     || 'Medium';
                document.getElementById('tStatus').value     = d.Status       || 'Open';
                document.getElementById('tAssignedTo').value = String(d.AssignedToUID || _todoMyUID);
                document.getElementById('tRefType').value    = d.RefType      || 'General';
                document.getElementById('tRefLabel').value   = d.RefLabel     || '';
                document.getElementById('tRefUID').value     = d.RefUID       || '0';
                document.getElementById('tIsPrivate').checked = d.IsPrivate == 1;
                if (d.DueDate) {
                    if (_duePicker) _duePicker.setDate(d.DueDate, true);
                    document.getElementById('tDueDate').value = d.DueDate;
                }
                document.getElementById('tRefLabelWrap').style.display =
                    (d.RefType && d.RefType !== 'General') ? '' : 'none';
                document.getElementById('todoModalTitle').textContent = 'Edit Task';
                $('#todoModal').modal('show');
            },
            error: function () { toastr.error('Network error.'); },
            complete: function () { ajaxLoading(0); },
        });
    }

    // ── Save ───────────────────────────────────────────────────────────────────

    /**
     * @returns {void}
     */
    function _save() {
        var title = document.getElementById('tTitle').value.trim();
        if (!title) {
            document.getElementById('tTitle').classList.add('is-invalid');
            document.getElementById('tTitle').focus();
            return;
        }
        document.getElementById('tTitle').classList.remove('is-invalid');

        var postData = {
            TodoUID:     document.getElementById('tTodoUID').value,
            Title:       title,
            Description: document.getElementById('tDescription').value.trim(),
            Priority:    document.getElementById('tPriority').value,
            Status:      document.getElementById('tStatus').value,
            DueDate:     document.getElementById('tDueDate').value,
            AssignedToUID: document.getElementById('tAssignedTo').value,
            RefType:     document.getElementById('tRefType').value,
            RefUID:      document.getElementById('tRefUID').value,
            RefLabel:    document.getElementById('tRefLabel').value.trim(),
            IsPrivate:   document.getElementById('tIsPrivate').checked ? 1 : 0,
            CurrentPage: _currentPage,
            Filter:      _buildFilter(),
            [CsrfName]:  CsrfToken,
        };

        ajaxLoading(1);
        $.ajax({
            url:  '/todos/save',
            type: 'POST',
            data: postData,
            success: function (res) {
                _refreshCsrf(res);
                if (res.Error) { toastr.error(res.Message || 'Save failed.'); return; }
                toastr.success(res.Message || 'Task saved.');
                $('#todoModal').modal('hide');
                document.getElementById('tTableBody').innerHTML  = res.RecordHtmlData;
                document.getElementById('tPagination').innerHTML = res.Pagination || '';
                _bindRowActions();
                _fetchStats();
            },
            error: function () { toastr.error('Network error.'); },
            complete: function () { ajaxLoading(0); },
        });
    }

    // ── Quick-complete ─────────────────────────────────────────────────────────

    /**
     * @param {number} todoUID
     * @returns {void}
     */
    function _quickComplete(todoUID) {
        ajaxLoading(1);
        $.ajax({
            url:  '/todos/changeStatus',
            type: 'POST',
            data: { TodoUID: todoUID, Status: 'Completed', CurrentPage: _currentPage, Filter: _buildFilter(), [CsrfName]: CsrfToken },
            success: function (res) {
                _refreshCsrf(res);
                if (res.Error) { toastr.error(res.Message || 'Failed.'); return; }
                toastr.success('Task completed!');
                document.getElementById('tTableBody').innerHTML  = res.RecordHtmlData;
                document.getElementById('tPagination').innerHTML = res.Pagination || '';
                _bindRowActions();
                _fetchStats();
            },
            error: function () { toastr.error('Network error.'); },
            complete: function () { ajaxLoading(0); },
        });
    }

    /**
     * @param {number} todoUID
     * @returns {void}
     */
    function _reopen(todoUID) {
        ajaxLoading(1);
        $.ajax({
            url:  '/todos/changeStatus',
            type: 'POST',
            data: { TodoUID: todoUID, Status: 'Open', CurrentPage: _currentPage, Filter: _buildFilter(), [CsrfName]: CsrfToken },
            success: function (res) {
                _refreshCsrf(res);
                if (res.Error) { toastr.error(res.Message || 'Failed.'); return; }
                toastr.success('Task reopened.');
                document.getElementById('tTableBody').innerHTML  = res.RecordHtmlData;
                document.getElementById('tPagination').innerHTML = res.Pagination || '';
                _bindRowActions();
                _fetchStats();
            },
            error: function () { toastr.error('Network error.'); },
            complete: function () { ajaxLoading(0); },
        });
    }

    /**
     * @param {Element} select
     * @returns {void}
     */
    function _changeStatus(select) {
        var uid    = select.getAttribute('data-uid');
        var status = select.value;
        ajaxLoading(1);
        $.ajax({
            url:  '/todos/changeStatus',
            type: 'POST',
            data: { TodoUID: uid, Status: status, CurrentPage: _currentPage, Filter: _buildFilter(), [CsrfName]: CsrfToken },
            success: function (res) {
                _refreshCsrf(res);
                if (res.Error) { toastr.error(res.Message || 'Failed.'); return; }
                document.getElementById('tTableBody').innerHTML  = res.RecordHtmlData;
                document.getElementById('tPagination').innerHTML = res.Pagination || '';
                _bindRowActions();
                _fetchStats();
            },
            error: function () { toastr.error('Network error.'); },
            complete: function () { ajaxLoading(0); },
        });
    }

    // ── Delete ─────────────────────────────────────────────────────────────────

    /**
     * @param {number} todoUID
     * @returns {void}
     */
    function _delete(todoUID) {
        Swal.fire({
            title: 'Delete Task?',
            text:  'This task will be permanently removed.',
            icon:  'warning',
            showCancelButton:  true,
            confirmButtonText: 'Delete',
            confirmButtonColor: '#dc3545',
        }).then(function (result) {
            if (!result.isConfirmed) return;
            ajaxLoading(1);
            $.ajax({
                url:  '/todos/delete',
                type: 'POST',
                data: { TodoUID: todoUID, CurrentPage: _currentPage, Filter: _buildFilter(), [CsrfName]: CsrfToken },
                success: function (res) {
                    _refreshCsrf(res);
                    if (res.Error) { toastr.error(res.Message || 'Delete failed.'); return; }
                    toastr.success('Task deleted.');
                    document.getElementById('tTableBody').innerHTML  = res.RecordHtmlData;
                    document.getElementById('tPagination').innerHTML = res.Pagination || '';
                    _bindRowActions();
                    _fetchStats();
                },
                error: function () { toastr.error('Network error.'); },
                complete: function () { ajaxLoading(0); },
            });
        });
    }

    // ── Row action binding ─────────────────────────────────────────────────────

    /**
     * Binds click/change handlers to dynamically rendered table rows.
     * @returns {void}
     */
    function _bindRowActions() {
        // Quick-complete checkbox
        document.querySelectorAll('.todo-done-cb').forEach(function (cb) {
            cb.addEventListener('change', function () {
                if (this.checked) _quickComplete(parseInt(this.getAttribute('data-uid'), 10));
            });
        });

        // Status dropdown
        document.querySelectorAll('.todo-status-select').forEach(function (sel) {
            sel.addEventListener('change', function () { _changeStatus(this); });
        });

        // Edit button
        document.querySelectorAll('.todo-edit-btn').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                _openEdit(parseInt(this.getAttribute('data-uid'), 10));
            });
        });

        // Reopen button
        document.querySelectorAll('.todo-reopen-btn').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                _reopen(parseInt(this.getAttribute('data-uid'), 10));
            });
        });

        // Delete button
        document.querySelectorAll('.todo-delete-btn').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                _delete(parseInt(this.getAttribute('data-uid'), 10));
            });
        });

        // Pagination links (delegated)
        document.getElementById('tPagination').querySelectorAll('[data-page]').forEach(function (a) {
            a.addEventListener('click', function (e) {
                e.preventDefault();
                _fetch(parseInt(this.getAttribute('data-page'), 10));
            });
        });
    }

    // ── Init ───────────────────────────────────────────────────────────────────

    /**
     * @returns {void}
     */
    function _init() {
        // Flatpickr for due date
        _duePicker = flatpickr('#tDueDateDisplay', {
            static:    true,
            position:  'below left',
            altInput:  true,
            altFormat: 'd M Y',
            dateFormat: 'Y-m-d',
            onChange: function (selectedDates, dateStr) {
                document.getElementById('tDueDate').value = dateStr;
            },
        });

        // Tab switching
        document.getElementById('tTabBar').addEventListener('click', function (e) {
            var btn = e.target.closest('[data-tab]');
            if (!btn) return;
            _activeTab  = btn.getAttribute('data-tab');
            _currentPage = 1;
            document.querySelectorAll('#tTabBar .todo-tab-btn').forEach(function (b) {
                b.classList.toggle('active', b === btn);
            });
            _fetch(1);
        });

        // Priority filter chips
        document.getElementById('tPriFilter').addEventListener('click', function (e) {
            var chip = e.target.closest('[data-pri]');
            if (!chip) return;
            _activePri   = chip.getAttribute('data-pri');
            _currentPage = 1;
            document.querySelectorAll('#tPriFilter .todo-pri-chip').forEach(function (c) {
                c.classList.toggle('active', c === chip);
            });
            _fetch(1);
        });

        // My Tasks toggle
        document.getElementById('tMyOnly').addEventListener('change', function () {
            _myOnly      = this.checked;
            _currentPage = 1;
            _fetch(1);
        });

        // Search
        document.getElementById('tSearch').addEventListener('input', function () {
            var val = this.value.trim();
            document.getElementById('tClearSearch').classList.toggle('d-none', !val);
            clearTimeout(_searchTimer);
            _searchTimer = setTimeout(function () {
                _searchVal   = val;
                _currentPage = 1;
                _fetch(1);
            }, 350);
        });
        document.getElementById('tClearSearch').addEventListener('click', function () {
            document.getElementById('tSearch').value = '';
            this.classList.add('d-none');
            _searchVal   = '';
            _currentPage = 1;
            _fetch(1);
        });

        // Page refresh button
        document.querySelector('.PageRefresh').addEventListener('click', function (e) {
            e.preventDefault();
            _fetch(_currentPage);
        });

        // New Task button
        document.getElementById('btnNewTodo').addEventListener('click', function () {
            _resetForm();
            $('#todoModal').modal('show');
        });

        // Save button in modal
        document.getElementById('btnSaveTodo').addEventListener('click', _save);

        // RefType changes → show/hide ref label
        document.getElementById('tRefType').addEventListener('change', function () {
            document.getElementById('tRefLabelWrap').style.display =
                (this.value !== 'General') ? '' : 'none';
        });

        // Seed stats from PHP-injected initial values
        _updateStats(_todoInitStats);

        // Bind initial row actions
        _bindRowActions();
    }

    $(document).ready(_init);

})();
