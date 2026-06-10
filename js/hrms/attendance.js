'use strict';
(function () {

  var currentPage = 1;
  var filterData  = {};
  var pendingChanges = {};

  function loadPage(page, filter) {
    currentPage = page || 1;
    filterData  = filter || filterData;
    pendingChanges = {};
    $('#btnSaveAttendance').hide();
    $.post('/attendance/getPageDetails/' + currentPage, { Filter: filterData }, function (r) {
      if (!r.Error) {
        $('#AttendanceTableBody').html(r.RecordHtmlData);
        $('#AttendancePagination').html(r.Pagination);
      }
    });
  }

  // Flatpickr for date picker
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

    // Auto-calc hours for CheckIn/CheckOut
    if (type === 'CheckIn' || type === 'CheckOut') {
      var row     = $(this).closest('tr');
      var checkIn = row.find('.att-checkin').val();
      var checkOut= row.find('.att-checkout').val();
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
        AttendanceUID: attUID,
        EmployeeUID:   empUID,
        AttendanceDate: date,
        Status:    row.find('.att-status-select').val(),
        CheckIn:   row.find('.att-checkin').val(),
        CheckOut:  row.find('.att-checkout').val(),
        Remarks:   row.find('.att-remarks').val()
      });
    });
    if (!records.length) return;
    $(this).prop('disabled', true).text('Saving…');
    var self = this;
    $.post('/attendance/saveBulk', { Records: records }, function (r) {
      $(self).prop('disabled', false).text('Save Changes');
      if (!r.Error) {
        toastr.success(r.Message || 'Attendance saved.');
        pendingChanges = {};
        $('#btnSaveAttendance').hide();
        loadPage(currentPage);
      } else {
        toastr.error(r.Message || 'Error saving attendance.');
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
  $(document).on('click', '#clearSearch', function () { $('#SearchDetails').val(''); $(this).addClass('d-none'); delete filterData.SearchAllData; loadPage(1, filterData); });
  $(document).on('click', '.PageRefresh', function (e) { e.preventDefault(); loadPage(currentPage); });
  $(document).on('click', '.page-link', function (e) { e.preventDefault(); var pg = $(this).data('page'); if (pg) loadPage(pg); });

})();
