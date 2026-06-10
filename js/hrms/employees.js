'use strict';
(function () {

  var currentPage  = 1;
  var filterData   = {};

  function loadPage(page, filter) {
    currentPage = page || 1;
    filterData  = filter || filterData;
    $.post('/employees/getPageDetails/' + currentPage, { Filter: filterData }, function (r) {
      if (!r.Error) {
        $('#EmployeeTableBody').html(r.RecordHtmlData);
        $('#EmployeesPagination').html(r.Pagination);
      }
    });
  }

  // Flatpickr
  if (typeof flatpickr !== 'undefined') {
    flatpickr('#empDOJ', { dateFormat: 'Y-m-d', allowInput: true });
  }

  // Salary type label update
  var salaryLabels = { Monthly: 'Basic Salary (₹/month)', Daily: 'Daily Wage (₹/day)', Hourly: 'Hourly Rate (₹/hr)' };
  $(document).on('change', '#empSalaryType', function () {
    $('#empBasicLabel').text(salaryLabels[$(this).val()] || 'Basic');
  });

  // Stat card filter
  $(document).on('click', '.emp-stat-clickable', function () {
    $('.emp-stat-clickable').removeClass('stat-selected');
    $(this).addClass('stat-selected');
    var f = $(this).data('filter');
    filterData.EmployeeStatus = f === 'All' ? '' : f;
    loadPage(1, filterData);
  });

  // Tab filter
  $(document).on('click', '.emp-tab', function (e) {
    e.preventDefault();
    $('.emp-tab').removeClass('active');
    $(this).addClass('active');
    var st = $(this).data('status');
    filterData.EmployeeStatus = st === 'All' ? '' : st;
    loadPage(1, filterData);
  });

  // New employee
  $(document).on('click', '#btnNewEmployee', function (e) {
    e.preventDefault();
    $('#empUID').val(0);
    $('#empCode').val(typeof EmpNextCode !== 'undefined' ? EmpNextCode : '');
    $('#empName,#empMobile,#empEmail').val('');
    $('#empDept,#empDesig').val('');
    $('#empDOJ').val('');
    $('#empStatus').val('Active');
    $('#empSalaryType').val('Monthly');
    $('#empBasic,#empAllowances,#empIncentives,#empDeductions').val('');
    $('#empAddress').val('');
    $('#empBasicLabel').text('Basic Salary (₹/month)');
    $('#empModalTitle').text('New Employee');
    $('#employeeModal').modal('show');
  });

  // Edit
  $(document).on('click', '.emp-edit-btn', function () {
    var uid = $(this).data('uid');
    $.post('/employees/getEmployee/' + uid, {}, function (r) {
      if (r.Error) { toastr.error('Could not load employee.'); return; }
      var d = r.Data;
      $('#empUID').val(d.EmployeeUID);
      $('#empCode').val(d.EmployeeCode);
      $('#empName').val(d.EmployeeName);
      $('#empMobile').val(d.Mobile);
      $('#empEmail').val(d.Email);
      $('#empDept').val(d.DepartmentUID || '');
      $('#empDesig').val(d.DesignationUID || '');
      $('#empDOJ').val(d.DateOfJoining || '');
      $('#empStatus').val(d.EmployeeStatus);
      $('#empSalaryType').val(d.SalaryType);
      $('#empBasicLabel').text(salaryLabels[d.SalaryType] || 'Basic');
      $('#empBasic').val(d.BasicSalary);
      $('#empAllowances').val(d.Allowances);
      $('#empIncentives').val(d.Incentives);
      $('#empDeductions').val(d.FixedDeductions);
      $('#empAddress').val(d.Address || '');
      $('#empModalTitle').text('Edit Employee');
      $('#employeeModal').modal('show');
    });
  });

  // Save
  $(document).on('click', '#btnSaveEmployee', function () {
    var code = $.trim($('#empCode').val());
    var name = $.trim($('#empName').val());
    if (!code) { toastr.warning('Employee code is required.'); return; }
    if (!name) { toastr.warning('Employee name is required.'); return; }
    var payload = {
      EmployeeUID:     $('#empUID').val(),
      EmployeeCode:    code,
      EmployeeName:    name,
      Mobile:          $.trim($('#empMobile').val()),
      Email:           $.trim($('#empEmail').val()),
      DepartmentUID:   $('#empDept').val() || 0,
      DesignationUID:  $('#empDesig').val() || 0,
      DateOfJoining:   $('#empDOJ').val(),
      EmployeeStatus:  $('#empStatus').val(),
      SalaryType:      $('#empSalaryType').val(),
      BasicSalary:     parseFloat($('#empBasic').val()) || 0,
      Allowances:      parseFloat($('#empAllowances').val()) || 0,
      Incentives:      parseFloat($('#empIncentives').val()) || 0,
      FixedDeductions: parseFloat($('#empDeductions').val()) || 0,
      Address:         $.trim($('#empAddress').val())
    };
    $(this).prop('disabled', true);
    var self = this;
    $.post('/employees/save', payload, function (r) {
      $(self).prop('disabled', false);
      if (!r.Error) {
        toastr.success(r.Message || 'Saved.');
        $('#employeeModal').modal('hide');
        loadPage(currentPage);
      } else {
        toastr.error(r.Message || 'Error saving.');
      }
    });
  });

  // Inline status change
  $(document).on('click', '.emp-status-change', function () {
    var uid    = $(this).data('uid');
    var status = $(this).data('status');
    $.post('/employees/toggleStatus', { EmployeeUID: uid, EmployeeStatus: status }, function (r) {
      if (!r.Error) { toastr.success('Status updated.'); loadPage(currentPage); }
      else toastr.error(r.Message || 'Error updating status.');
    });
  });

  // Delete
  $(document).on('click', '.emp-delete-btn', function () {
    if (!confirm('Delete this employee? This cannot be undone.')) return;
    $.post('/employees/delete', { EmployeeUID: $(this).data('uid') }, function (r) {
      if (!r.Error) { toastr.success('Deleted.'); loadPage(currentPage); }
      else toastr.error(r.Message || 'Error deleting.');
    });
  });

  // Header checkbox
  $(document).on('change', '.empHeaderCheck', function () {
    $('.empCheck').prop('checked', $(this).is(':checked'));
  });

  // Search
  var searchTimer;
  $(document).on('input', '#SearchDetails', function () {
    clearTimeout(searchTimer);
    filterData.SearchAllData = $.trim($(this).val());
    $('#clearSearch').toggleClass('d-none', !filterData.SearchAllData);
    searchTimer = setTimeout(function () { loadPage(1, filterData); }, 350);
  });
  $(document).on('click', '#clearSearch', function () {
    $('#SearchDetails').val('');
    $(this).addClass('d-none');
    delete filterData.SearchAllData;
    loadPage(1, filterData);
  });

  $(document).on('click', '.PageRefresh', function (e) { e.preventDefault(); loadPage(currentPage); });
  $(document).on('click', '.page-link', function (e) { e.preventDefault(); var pg = $(this).data('page'); if (pg) loadPage(pg); });

})();
