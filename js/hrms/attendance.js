'use strict';
(function () {

  var currentPage    = 1;
  var filterData     = {};
  var pendingChanges = {};
  var RowLimit       = parseInt((JwtData && JwtData.GenSettings && JwtData.GenSettings.RowLimit) || 25, 10);

  // ── Helpers ───────────────────────────────────────────────────────────────

  /**
   * @param {string} str
   * @returns {string}
   */
  function _esc(str) {
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  /**
   * @param {Array}  rows
   * @param {number} offset
   * @returns {string}
   */
  function _renderRows(rows, offset) {
    if (!rows || !rows.length) {
      return '<tr><td colspan="8" class="text-center text-muted py-4"><i class="bx bx-calendar-check me-1"></i>No employees found.</td></tr>';
    }
    var statusLabels  = { Present: 'Present', Absent: 'Absent', HalfDay: 'Half Day', Leave: 'Leave', Holiday: 'Holiday', WeekOff: 'Week Off' };
    var statusOptions = ['Present', 'Absent', 'HalfDay', 'Leave', 'Holiday', 'WeekOff'];
    var html = '';
    $.each(rows, function (i, row) {
      var sno      = offset + i + 1;
      var uid      = parseInt(row.AttendanceUID, 10) || 0;
      var empUID   = parseInt(row.EmployeeUID,   10) || 0;
      var status   = row.Status || 'Absent';
      var initial  = (row.EmployeeName || '?').charAt(0).toUpperCase();
      var checkIn  = row.CheckIn  ? String(row.CheckIn).substring(0, 5)  : '';
      var checkOut = row.CheckOut ? String(row.CheckOut).substring(0, 5) : '';
      var hours    = row.WorkingHours ? parseFloat(row.WorkingHours).toFixed(1) + 'h' : '—';
      var opts     = '';
      $.each(statusOptions, function (_, so) {
        opts += '<option value="' + so + '"' + (so === status ? ' selected' : '') + '>' + (statusLabels[so] || so) + '</option>';
      });
      html += '<tr data-emp-uid="' + empUID + '" data-att-uid="' + uid + '">';
      html += '<td class="text-muted" style="font-size:var(--r2k-text-md);">' + sno + '</td>';
      html += '<td><div class="d-flex align-items-center gap-2">';
      html += '<div class="avatar avatar-sm flex-shrink-0"><span class="avatar-initial rounded-circle" style="background:linear-gradient(135deg,#10b981,#059669);color:#fff;font-size:var(--r2k-text-75);">' + initial + '</span></div>';
      html += '<div><div class="fw-semibold" style="font-size:.875rem;">' + _esc(row.EmployeeName || '') + '</div>';
      html += '<div class="text-muted" style="font-size:var(--r2k-text-75);">' + _esc(row.EmployeeCode || '') + '</div></div>';
      html += '</div></td>';
      html += '<td class="text-muted" style="font-size:.83rem;">' + _esc(row.DepartmentName || '—') + '</td>';
      html += '<td><select class="form-select form-select-sm att-status-select" data-emp-uid="' + empUID + '" style="width:110px;">' + opts + '</select></td>';
      html += '<td><input type="time" class="form-control form-control-sm att-checkin" data-emp-uid="' + empUID + '" value="' + checkIn + '" style="width:100px;"></td>';
      html += '<td><input type="time" class="form-control form-control-sm att-checkout" data-emp-uid="' + empUID + '" value="' + checkOut + '" style="width:100px;"></td>';
      html += '<td class="text-muted att-hours">' + hours + '</td>';
      html += '<td><input type="text" class="form-control form-control-sm att-remarks" data-emp-uid="' + empUID + '" value="' + _esc(row.Remarks || '') + '" placeholder="Remarks" style="width:140px;"></td>';
      html += '</tr>';
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
    var html = '<div class="col-auto text-muted small">Showing ' + ((page - 1) * limit + 1) + '–' + Math.min(page * limit, total) + ' of ' + total + '</div>';
    html += '<nav class="col-auto"><ul class="pagination pagination-sm mb-0 gap-1">';
    html += '<li class="page-item' + (page <= 1 ? ' disabled' : '') + '"><a class="page-link" href="#" data-page="' + (page - 1) + '">&laquo;</a></li>';
    var start = Math.max(1, page - 2);
    var end   = Math.min(totalPages, start + 4);
    start     = Math.max(1, end - 4);
    for (var i = start; i <= end; i++) {
      html += '<li class="page-item' + (i === page ? ' active' : '') + '"><a class="page-link" href="#" data-page="' + i + '">' + i + '</a></li>';
    }
    html += '<li class="page-item' + (page >= totalPages ? ' disabled' : '') + '"><a class="page-link" href="#" data-page="' + (page + 1) + '">&raquo;</a></li>';
    html += '</ul></nav>';
    return html;
  }

  // ── Load page ─────────────────────────────────────────────────────────────

  /**
   * @param {number} page
   * @param {Object} filter
   * @returns {void}
   */
  function loadPage(page, filter) {
    currentPage    = page || 1;
    filterData     = filter || filterData;
    pendingChanges = {};
    $('#btnSaveAttendance').hide();
    ajaxLoading(0);
    $.ajax({
      url     : global_services_url + 'api/attendance/getPageDetails',
      method  : 'GET',
      data    : {
        orgId   : parseInt(JwtData.Org.OrgUID, 10),
        RowLimit: RowLimit,
        PageNo  : currentPage,
        Filter  : JSON.stringify(filterData)
      },
      dataType: 'json',
      success : function (r) {
        ajaxLoading(1);
        if (r.Error) { showToastNotification(r.Message || 'Failed to load.', 'error'); return; }
        var rows   = (r.Data && r.Data.Data)       ? r.Data.Data       : [];
        var total  = (r.Data && r.Data.TotalCount) ? parseInt(r.Data.TotalCount, 10) : 0;
        var offset = (currentPage - 1) * RowLimit;
        $('#AttendanceTableBody').html(_renderRows(rows, offset));
        $('#AttendancePagination').html(_buildPagination(total, currentPage, RowLimit));
      },
      error   : function () {
        ajaxLoading(1);
        showToastNotification('Failed to load attendance.', 'error');
      }
    });
  }

  // ── Stats ─────────────────────────────────────────────────────────────────

  /**
   * @returns {void}
   */
  function _loadStats() {
    $.ajax({
      url     : global_services_url + 'api/attendance/getPageData',
      method  : 'GET',
      data    : {
        orgId    : parseInt(JwtData.Org.OrgUID,    10),
        branchId : parseInt(JwtData.Org.BranchUID, 10)
      },
      dataType: 'json',
      success : function (r) {
        if (r.Error) return;
        var st = (r.Data && r.Data.DailyStats) ? r.Data.DailyStats : {};
        $('#statPresent').text(parseInt(st.Present,  10) || 0);
        $('#statAbsent').text(parseInt(st.Absent,   10) || 0);
        $('#statHalfDay').text(parseInt(st.HalfDay, 10) || 0);
        $('#statLeave').text(parseInt(st.Leave,    10) || 0);
      }
    });
  }

  // ── Init ──────────────────────────────────────────────────────────────────

  $(function () {
    if (typeof flatpickr !== 'undefined') {
      flatpickr('#attendanceDatePicker', {
        dateFormat: 'Y-m-d',
        allowInput: true,
        onChange: function (d, s) {
          filterData.AttendanceDate = s;
          loadPage(1, filterData);
        }
      });
    }
    filterData.AttendanceDate = $('#attendanceDatePicker').val();
    _loadStats();
    loadPage(1);
  });

  // Tab filter
  $(document).on('click', '.att-tab', function (e) {
    e.preventDefault();
    $('.att-tab').removeClass('active');
    $(this).addClass('active');
    filterData.Status = $(this).data('status');
    loadPage(1, filterData);
  });

  // Mark all present
  $(document).on('click', '#btnMarkAllPresent', function () {
    $('.att-status-select').val('Present').trigger('change');
  });

  // Detect changes
  $(document).on('change', '.att-status-select, .att-checkin, .att-checkout, .att-remarks', function () {
    var empUID = $(this).data('emp-uid');
    if (!pendingChanges[empUID]) pendingChanges[empUID] = {};
    var type = $(this).hasClass('att-status-select') ? 'Status'
             : $(this).hasClass('att-checkin')       ? 'CheckIn'
             : $(this).hasClass('att-checkout')      ? 'CheckOut'
             : 'Remarks';
    pendingChanges[empUID][type] = $(this).val();

    if (type === 'CheckIn' || type === 'CheckOut') {
      var row      = $(this).closest('tr');
      var checkIn  = row.find('.att-checkin').val();
      var checkOut = row.find('.att-checkout').val();
      if (checkIn && checkOut) {
        var diff = (new Date('1970-01-01T' + checkOut) - new Date('1970-01-01T' + checkIn)) / 3600000;
        row.find('.att-hours').text(diff > 0 ? diff.toFixed(1) + 'h' : '—');
      }
    }
    $('#btnSaveAttendance').show();
  });

  // Save bulk
  $(document).on('click', '#btnSaveAttendance', function () {
    var records = [];
    var date    = filterData.AttendanceDate || $('#attendanceDatePicker').val();
    $('#AttendanceTableBody tr').each(function () {
      var row    = $(this);
      var empUID = row.data('emp-uid');
      var attUID = row.data('att-uid') || 0;
      records.push({
        AttendanceUID:  attUID,
        EmployeeUID:    empUID,
        AttendanceDate: date,
        Status:   row.find('.att-status-select').val(),
        CheckIn:  row.find('.att-checkin').val(),
        CheckOut: row.find('.att-checkout').val(),
        Remarks:  row.find('.att-remarks').val()
      });
    });
    if (!records.length) return;
    var $btn = $(this);
    $btn.prop('disabled', true).text('Saving…');
    $.ajax({
      url     : global_services_url + 'api/attendance/saveBulk',
      method  : 'POST',
      data    : {
        orgId  : parseInt(JwtData.Org.OrgUID, 10),
        Records: records
      },
      dataType: 'json',
      success : function (r) {
        $btn.prop('disabled', false).text('Save Changes');
        if (!r.Error) {
          showToastNotification(r.Message || 'Attendance saved.', 'success');
          pendingChanges = {};
          $('#btnSaveAttendance').hide();
          _loadStats();
          loadPage(currentPage);
        } else {
          showToastNotification(r.Message || 'Error saving attendance.', 'error');
        }
      },
      error   : function () {
        $btn.prop('disabled', false).text('Save Changes');
        showToastNotification('Failed to save attendance.', 'error');
      }
    });
  });

  // Search
  var searchTimer;
  $(document).on('input', '#SearchDetails', function () {
    clearTimeout(searchTimer);
    filterData.SearchAllData = $.trim($(this).val());
    $('#clearSearch').toggleClass('d-none', !filterData.SearchAllData);
    searchTimer = setTimeout(function () { loadPage(1, filterData); }, 350);
  });
  $(document).on('click', '#clearSearch',  function () { $('#SearchDetails').val(''); $(this).addClass('d-none'); delete filterData.SearchAllData; loadPage(1, filterData); });
  $(document).on('click', '.PageRefresh',  function (e) { e.preventDefault(); loadPage(currentPage); });
  $(document).on('click', '.page-link',    function (e) { e.preventDefault(); var pg = $(this).data('page'); if (pg) loadPage(pg); });

}());
