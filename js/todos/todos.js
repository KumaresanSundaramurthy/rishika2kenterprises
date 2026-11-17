/* global $, Swal, flatpickr, CsrfName, CsrfToken, _todoMyUID, _todoInitStats, ajaxLoading, global_base_url, global_services_url, JwtData, RowLimit */
(function () {
    'use strict';

    // ── State ──────────────────────────────────────────────────────────────────
    var _currentPage = 1;
    var _activeTab   = 'open';
    var _activePri   = '';
    var _myOnly      = true;
    var _searchVal   = '';
    var _searchTimer = null;
    var _duePicker   = null;

    // ── Helpers ────────────────────────────────────────────────────────────────

    /**
     * @returns {{Tab:string,Priority:string,MyOnly:boolean,Search:string}}
     */
    function _buildFilter() {
        return { Tab: _activeTab, Priority: _activePri, MyOnly: _myOnly, Search: _searchVal };
    }

    /**
     * @param {string} val
     * @returns {string}
     */
    function _esc(val) {
        return $('<div>').text(val).html();
    }

    /**
     * @param {object} res
     * @returns {void}
     */
    function _refreshCsrf(res) {
        if (res.csrf_name)  CsrfName  = res.csrf_name;
        if (res.csrf_token) CsrfToken = res.csrf_token;
    }

    /**
     * @param {{active:number,overdue:number,today:number,completed:number}} stats
     * @returns {void}
     */
    function _updateStats(stats) {
        if (!stats) return;
        $('#tStatActive').text(stats.active      || 0);
        $('#tStatOverdue').text(stats.overdue    || 0);
        $('#tStatToday').text(stats.today        || 0);
        $('#tStatCompleted').text(stats.completed || 0);
        $('#tBadgeOpen').text(stats.active       || 0);
        $('#tBadgeOverdue').text(stats.overdue   || 0);
        $('#tBadgeToday').text(stats.today       || 0);
    }

    // ── Row rendering helpers ──────────────────────────────────────────────────

    /**
     * @param {string} priority
     * @returns {string}
     */
    function _todoPriBadge(priority) {
        var map = {
            Low:    'todo-pri todo-pri-low',
            Medium: 'todo-pri todo-pri-medium',
            High:   'todo-pri todo-pri-high',
            Urgent: 'todo-pri todo-pri-urgent',
        };
        return '<span class="' + (map[priority] || 'todo-pri todo-pri-low') + '">' + _esc(priority || 'Low') + '</span>';
    }

    /**
     * @param {string} status
     * @returns {string}
     */
    function _todoStatBadge(status) {
        var map = {
            Open:       ['todo-stat-badge todo-stat-open',       'Open'],
            InProgress: ['todo-stat-badge todo-stat-inprogress', 'In Progress'],
            OnHold:     ['todo-stat-badge todo-stat-onhold',     'On Hold'],
            Completed:  ['todo-stat-badge todo-stat-completed',  'Completed'],
            Cancelled:  ['todo-stat-badge todo-stat-cancelled',  'Cancelled'],
        };
        var c = map[status] || ['todo-stat-badge todo-stat-open', _esc(status || 'Open')];
        return '<span class="' + c[0] + '">' + c[1] + '</span>';
    }

    /**
     * @param {string|null} dueDate   YYYY-MM-DD
     * @param {string}      status
     * @param {string}      todayStr  YYYY-MM-DD
     * @returns {string}
     */
    function _todoDueCell(dueDate, status, todayStr) {
        if (!dueDate) return '<span class="text-muted">—</span>';
        var isDone = (status === 'Completed' || status === 'Cancelled');
        var mon    = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        var d      = new Date(dueDate);
        var fmt    = d.getDate() + ' ' + mon[d.getMonth()] + ' ' + d.getFullYear();
        if (isDone)               return '<span class="text-muted">' + fmt + '</span>';
        if (dueDate < todayStr)   return '<span class="todo-due-overdue"><i class="bx bx-error-circle me-1"></i>' + fmt + '</span>';
        if (dueDate === todayStr) return '<span class="todo-due-today"><i class="bx bx-calendar-event me-1"></i>Today</span>';
        var diff = Math.round((new Date(dueDate) - new Date(todayStr)) / 86400000);
        if (diff <= 3) return '<span class="todo-due-soon">' + fmt + '</span>';
        return '<span>' + fmt + '</span>';
    }

    /**
     * @param {Array}  rows
     * @param {string} todayStr  YYYY-MM-DD
     * @returns {string}
     */
    function _renderRows(rows, todayStr) {
        if (!rows || !rows.length) {
            return '<tr><td colspan="7"><div class="rpt-empty"><i class="bx bx-task-x"></i>'
                + '<div class="rpt-empty-title">No tasks found</div>'
                + '<div class="text-muted" style="font-size:.80rem">Create your first task using the New button above.</div>'
                + '</div></td></tr>';
        }
        var html = '';
        $.each(rows, function (i, row) {
            var uid       = row.TodoUID;
            var status    = row.Status || 'Open';
            var isDone    = (status === 'Completed' || status === 'Cancelled');
            var isPrivate = parseInt(row.IsPrivate || 0, 10) === 1;
            var isOwner   = (parseInt(row.CreatedByUID  || 0, 10) === parseInt(_todoMyUID, 10))
                         || (parseInt(row.AssignedToUID || 0, 10) === parseInt(_todoMyUID, 10));

            var checkCell = isDone
                ? '<i class="bx bx-check-circle text-success" style="font-size:1rem"></i>'
                : '<input type="checkbox" class="todo-done-cb" data-uid="' + uid + '" title="Mark as completed">';

            var titleHtml = '<span class="todo-title' + (isDone ? ' todo-title-done' : '') + '">'
                + _esc(row.Title || '')
                + (isPrivate ? '<i class="bx bx-lock ms-1 text-muted" title="Private" style="font-size:var(--r2k-text-75)"></i>' : '')
                + '</span>';
            if (row.RefLabel && row.RefType !== 'General') {
                titleHtml += '<span class="todo-ref-chip"><i class="bx bx-link-alt me-1"></i>' + _esc(row.RefType + ': ' + row.RefLabel) + '</span>';
            }
            if (row.Description) {
                titleHtml += '<span class="todo-desc">' + _esc(String(row.Description).substring(0, 80)) + '</span>';
            }

            var isMeAssigned = (parseInt(row.AssignedToUID || 0, 10) === parseInt(_todoMyUID, 10));
            var assignedCell = _esc(row.AssignedName || '—')
                + (isMeAssigned ? '<span class="badge bg-label-primary ms-1" style="font-size:var(--r2k-text-65)">Me</span>' : '');

            var statusCell;
            if (isOwner && !isDone) {
                statusCell = '<select class="todo-status-select" data-uid="' + uid + '" title="Change status">'
                    + '<option value="Open"'       + (status === 'Open'       ? ' selected' : '') + '>Open</option>'
                    + '<option value="InProgress"' + (status === 'InProgress' ? ' selected' : '') + '>In Progress</option>'
                    + '<option value="OnHold"'     + (status === 'OnHold'     ? ' selected' : '') + '>On Hold</option>'
                    + '</select>';
            } else {
                statusCell = _todoStatBadge(status);
            }

            var actions = '';
            if (isOwner) {
                actions += '<a href="#" class="r2k-icon-btn todo-edit-btn" data-uid="' + uid + '" title="Edit"><i class="bx bx-edit-alt"></i></a>';
                if (isDone) {
                    actions += '<a href="#" class="r2k-icon-btn todo-reopen-btn" data-uid="' + uid + '" title="Reopen"><i class="bx bx-refresh"></i></a>';
                }
                actions += '<a href="#" class="r2k-icon-btn text-danger todo-delete-btn" data-uid="' + uid + '" title="Delete"><i class="bx bx-trash"></i></a>';
            } else {
                actions = '<span class="text-muted" style="font-size:var(--r2k-text-75)">View only</span>';
            }

            html += '<tr data-uid="' + uid + '">'
                + '<td>' + checkCell + '</td>'
                + '<td>' + _todoPriBadge(row.Priority || 'Low') + '</td>'
                + '<td><div class="todo-title-wrap">' + titleHtml + '</div></td>'
                + '<td class="text-nowrap">' + assignedCell + '</td>'
                + '<td class="text-nowrap">' + _todoDueCell(row.DueDate || null, status, todayStr) + '</td>'
                + '<td>' + statusCell + '</td>'
                + '<td class="th-act"><div class="d-flex gap-1 align-items-center">' + actions + '</div></td>'
                + '</tr>';
        });
        return html;
    }

    /**
     * @param {number} total
     * @param {number} page
     * @param {number} limit
     * @returns {string}
     */
    function _buildPagination(total, page, limit) {
        var totalPages = Math.ceil(total / limit);
        if (totalPages <= 1) return '';
        var from = (page - 1) * limit + 1;
        var to   = Math.min(page * limit, total);
        var html = '<div class="col-auto text-muted small">Showing ' + from + '–' + to + ' of ' + total + '</div>';
        html += '<nav class="col-auto"><ul class="pagination pagination-sm mb-0 gap-1">';
        html += '<li class="page-item' + (page <= 1 ? ' disabled' : '') + '"><a class="page-link" href="#" data-page="' + (page - 1) + '">&laquo;</a></li>';
        var start = Math.max(1, page - 2);
        var end   = Math.min(totalPages, start + 4);
        start     = Math.max(1, end - 4);
        for (var p = start; p <= end; p++) {
            html += '<li class="page-item' + (p === page ? ' active' : '') + '"><a class="page-link" href="#" data-page="' + p + '">' + p + '</a></li>';
        }
        html += '<li class="page-item' + (page >= totalPages ? ' disabled' : '') + '"><a class="page-link" href="#" data-page="' + (page + 1) + '">&raquo;</a></li>';
        html += '</ul></nav>';
        return html;
    }

    // ── Fetch list ─────────────────────────────────────────────────────────────

    /**
     * @param {number} pageNo
     * @returns {void}
     */
    function _fetch(pageNo) {
        ajaxLoading(1);
        _currentPage = pageNo || 1;
        var today    = new Date();
        today.setHours(0, 0, 0, 0);
        var todayStr = today.toISOString().split('T')[0];
        $.ajax({
            url:      global_services_url + 'api/todos/getPageDetails/' + _currentPage,
            method:   'GET',
            data:     { orgId: parseInt(JwtData.Org.OrgUID, 10), RowLimit: RowLimit, Filter: JSON.stringify(_buildFilter()) },
            dataType: 'json',
            success:  function (r) {
                if (r.Error) { toastr.error(r.Message || 'Failed to load tasks.'); return; }
                var rows  = (r.Data && r.Data.Data)       ? r.Data.Data                    : [];
                var total = (r.Data && r.Data.TotalCount) ? parseInt(r.Data.TotalCount, 10) : 0;
                $('#tTableBody').html(_renderRows(rows, todayStr));
                $('#tPagination').html(_buildPagination(total, _currentPage, RowLimit));
                _fetchStats();
            },
            error:    function () { toastr.error('Network error. Please try again.'); },
            complete: function () { ajaxLoading(0); },
        });
    }

    /**
     * @returns {void}
     */
    function _fetchStats() {
        $.ajax({
            url:      global_services_url + 'api/todos/getStats',
            method:   'GET',
            data:     { orgId: parseInt(JwtData.Org.OrgUID, 10), userUID: parseInt(_todoMyUID, 10) },
            dataType: 'json',
            success:  function (r) {
                if (!r.Error && r.Data) _updateStats(r.Data);
            },
        });
    }

    /**
     * Fetches user list from NestJS and populates the assign-to dropdown in the modal.
     * @returns {void}
     */
    function _loadAssignees() {
        $.ajax({
            url:      global_services_url + 'api/todos/getPageData',
            method:   'GET',
            data:     { orgId: parseInt(JwtData.Org.OrgUID, 10) },
            dataType: 'json',
            success:  function (r) {
                var users = (r.Data && r.Data.UserList) ? r.Data.UserList : [];
                if (!users.length) return;
                var opts = '';
                $.each(users, function (i, u) {
                    var sel = (parseInt(u.UserUID, 10) === parseInt(_todoMyUID, 10)) ? ' selected' : '';
                    opts += '<option value="' + u.UserUID + '"' + sel + '>' + _esc(u.FullName) + '</option>';
                });
                $('#tAssignedTo').html(opts);
            },
        });
    }

    // ── Modal open/populate ────────────────────────────────────────────────────

    /**
     * @returns {void}
     */
    function _resetForm() {
        $('#tTodoUID').val('0');
        $('#tTitle').val('');
        $('#tDescription').val('');
        $('#tPriority').val('Medium');
        $('#tStatus').val('Open');
        $('#tAssignedTo').val(String(_todoMyUID));
        $('#tRefType').val('General');
        $('#tRefLabel').val('');
        $('#tRefUID').val('0');
        $('#tIsPrivate').prop('checked', false);
        $('#tDueDateDisplay').val('');
        $('#tDueDate').val('');
        $('#tRefLabelWrap').hide();
        if (_duePicker) _duePicker.clear();
        $('#tTitle').removeClass('is-invalid');
        $('#todoModalTitle').text('New Task');
    }

    /**
     * @param {number} todoUID
     * @returns {void}
     */
    function _openEdit(todoUID) {
        ajaxLoading(1);
        $.ajax({
            url:      global_services_url + 'api/todos/getDetail',
            method:   'GET',
            data:     { orgId: parseInt(JwtData.Org.OrgUID, 10), todoUID: todoUID },
            dataType: 'json',
            success: function (res) {
                if (res.Error || !res.Todo) { toastr.error(res.Message || 'Failed to load task.'); return; }
                var d = res.Todo;
                _resetForm();
                $('#tTodoUID').val(d.TodoUID);
                $('#tTitle').val(d.Title        || '');
                $('#tDescription').val(d.Description || '');
                $('#tPriority').val(d.Priority     || 'Medium');
                $('#tStatus').val(d.Status         || 'Open');
                $('#tAssignedTo').val(String(d.AssignedToUID || _todoMyUID));
                $('#tRefType').val(d.RefType        || 'General');
                $('#tRefLabel').val(d.RefLabel      || '');
                $('#tRefUID').val(d.RefUID          || '0');
                $('#tIsPrivate').prop('checked', d.IsPrivate == 1);
                if (d.DueDate) {
                    if (_duePicker) _duePicker.setDate(d.DueDate, true);
                    $('#tDueDate').val(d.DueDate);
                }
                $('#tRefLabelWrap').toggle(d.RefType && d.RefType !== 'General');
                $('#todoModalTitle').text('Edit Task');
                $('#todoModal').modal('show');
            },
            error:    function () { toastr.error('Network error.'); },
            complete: function () { ajaxLoading(0); },
        });
    }

    // ── Save ───────────────────────────────────────────────────────────────────

    /**
     * @returns {void}
     */
    function _save() {
        var title = $.trim($('#tTitle').val());
        if (!title) {
            $('#tTitle').addClass('is-invalid').focus();
            return;
        }
        $('#tTitle').removeClass('is-invalid');

        var postData = {
            orgId:         parseInt(JwtData.Org.OrgUID,  10),
            userId:        parseInt(JwtData.User.UserUID, 10),
            TodoUID:       parseInt($('#tTodoUID').val(), 10),
            Title:         title,
            Description:   $.trim($('#tDescription').val()),
            Priority:      $('#tPriority').val(),
            Status:        $('#tStatus').val(),
            DueDate:       $('#tDueDate').val(),
            AssignedToUID: parseInt($('#tAssignedTo').val(), 10),
            RefType:       $('#tRefType').val(),
            RefUID:        parseInt($('#tRefUID').val(), 10),
            RefLabel:      $.trim($('#tRefLabel').val()),
            IsPrivate:     $('#tIsPrivate').prop('checked') ? 1 : 0,
        };

        ajaxLoading(1);
        $.ajax({
            url:      global_services_url + 'api/todos/save',
            method:   'POST',
            data:     JSON.stringify(postData),
            dataType: 'json',
            contentType: 'application/json',
            success: function (res) {
                if (res.Error) { toastr.error(res.Message || 'Save failed.'); return; }
                toastr.success(res.Message || 'Task saved.');
                $('#todoModal').modal('hide');
                _fetch(_currentPage);
            },
            error:    function () { toastr.error('Network error.'); },
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
            url:         global_services_url + 'api/todos/changeStatus',
            method:      'POST',
            data:        JSON.stringify({ orgId: parseInt(JwtData.Org.OrgUID, 10), userId: parseInt(JwtData.User.UserUID, 10), TodoUID: todoUID, Status: 'Completed' }),
            dataType:    'json',
            contentType: 'application/json',
            success: function (res) {
                if (res.Error) { toastr.error(res.Message || 'Failed.'); return; }
                toastr.success('Task completed!');
                _fetch(_currentPage);
            },
            error:    function () { toastr.error('Network error.'); },
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
            url:         global_services_url + 'api/todos/changeStatus',
            method:      'POST',
            data:        JSON.stringify({ orgId: parseInt(JwtData.Org.OrgUID, 10), userId: parseInt(JwtData.User.UserUID, 10), TodoUID: todoUID, Status: 'Open' }),
            dataType:    'json',
            contentType: 'application/json',
            success: function (res) {
                if (res.Error) { toastr.error(res.Message || 'Failed.'); return; }
                toastr.success('Task reopened.');
                _fetch(_currentPage);
            },
            error:    function () { toastr.error('Network error.'); },
            complete: function () { ajaxLoading(0); },
        });
    }

    /**
     * @param {Element} select
     * @returns {void}
     */
    function _changeStatus(select) {
        var uid    = $(select).data('uid');
        var status = $(select).val();
        ajaxLoading(1);
        $.ajax({
            url:         global_services_url + 'api/todos/changeStatus',
            method:      'POST',
            data:        JSON.stringify({ orgId: parseInt(JwtData.Org.OrgUID, 10), userId: parseInt(JwtData.User.UserUID, 10), TodoUID: uid, Status: status }),
            dataType:    'json',
            contentType: 'application/json',
            success: function (res) {
                if (res.Error) { toastr.error(res.Message || 'Failed.'); return; }
                _fetch(_currentPage);
            },
            error:    function () { toastr.error('Network error.'); },
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
            title:              'Delete Task?',
            text:               'This task will be permanently removed.',
            icon:               'warning',
            showCancelButton:   true,
            confirmButtonText:  'Delete',
            confirmButtonColor: '#dc3545',
        }).then(function (result) {
            if (!result.isConfirmed) return;
            ajaxLoading(1);
            $.ajax({
                url:         global_services_url + 'api/todos/delete',
                method:      'POST',
                data:        JSON.stringify({ orgId: parseInt(JwtData.Org.OrgUID, 10), userId: parseInt(JwtData.User.UserUID, 10), TodoUID: todoUID }),
                dataType:    'json',
                contentType: 'application/json',
                success: function (res) {
                    if (res.Error) { toastr.error(res.Message || 'Delete failed.'); return; }
                    toastr.success('Task deleted.');
                    _fetch(_currentPage);
                },
                error:    function () { toastr.error('Network error.'); },
                complete: function () { ajaxLoading(0); },
            });
        });
    }

    // ── Init ───────────────────────────────────────────────────────────────────

    /**
     * @returns {void}
     */
    function _init() {
        _loadAssignees();

        _duePicker = flatpickr('#tDueDateDisplay', {
            static:     true,
            position:   'below left',
            altInput:   true,
            altFormat:  'd M Y',
            dateFormat: 'Y-m-d',
            onChange:   function (selectedDates, dateStr) { $('#tDueDate').val(dateStr); },
        });

        // Tab switching
        $('#tTabBar').on('click', '[data-tab]', function () {
            _activeTab   = $(this).data('tab');
            _currentPage = 1;
            $('#tTabBar .todo-tab-btn').removeClass('active');
            $(this).addClass('active');
            _fetch(1);
        });

        // Priority filter chips
        $('#tPriFilter').on('click', '[data-pri]', function () {
            _activePri   = $(this).data('pri');
            _currentPage = 1;
            $('#tPriFilter .todo-pri-chip').removeClass('active');
            $(this).addClass('active');
            _fetch(1);
        });

        // My Tasks toggle
        $('#tMyOnly').on('change', function () {
            _myOnly      = this.checked;
            _currentPage = 1;
            _fetch(1);
        });

        // Search
        $('#tSearch').on('input', function () {
            var val = $.trim($(this).val());
            $('#tClearSearch').toggleClass('d-none', !val);
            clearTimeout(_searchTimer);
            _searchTimer = setTimeout(function () {
                _searchVal   = val;
                _currentPage = 1;
                _fetch(1);
            }, 350);
        });

        $('#tClearSearch').on('click', function () {
            $('#tSearch').val('');
            $(this).addClass('d-none');
            _searchVal   = '';
            _currentPage = 1;
            _fetch(1);
        });

        // Refresh
        $('.PageRefresh').on('click', function (e) { e.preventDefault(); _fetch(_currentPage); });

        // New Task
        $('#btnNewTodo').on('click', function () { _resetForm(); $('#todoModal').modal('show'); });

        // Save
        $('#btnSaveTodo').on('click', _save);

        // RefType → show/hide ref label
        $('#tRefType').on('change', function () { $('#tRefLabelWrap').toggle($(this).val() !== 'General'); });

        // Dynamic row events
        $(document).on('change', '.todo-done-cb',      function () { if ($(this).prop('checked')) _quickComplete(parseInt($(this).data('uid'), 10)); });
        $(document).on('change', '.todo-status-select', function () { _changeStatus(this); });
        $(document).on('click',  '.todo-edit-btn',      function (e) { e.preventDefault(); _openEdit(parseInt($(this).data('uid'), 10)); });
        $(document).on('click',  '.todo-reopen-btn',    function (e) { e.preventDefault(); _reopen(parseInt($(this).data('uid'), 10)); });
        $(document).on('click',  '.todo-delete-btn',    function (e) { e.preventDefault(); _delete(parseInt($(this).data('uid'), 10)); });
        $(document).on('click',  '#tPagination [data-page]', function (e) { e.preventDefault(); _fetch(parseInt($(this).data('page'), 10)); });

        _updateStats(_todoInitStats);
        _fetch(1);
    }

    $(document).ready(_init);

})();
