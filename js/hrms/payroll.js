'use strict';
(function () {

  var currentPage = 1;
  var filterData  = {};
  var payrollLines = [];

  /* ── List page helpers ── */
  function loadPage(page, filter) {
    currentPage = page || 1;
    filterData  = filter || filterData;
    $.post('/payroll/getPageDetails/' + currentPage, { Filter: filterData }, function (r) {
      if (!r.Error) {
        $('#PayrollTableBody').html(r.RecordHtmlData);
        $('#PayrollPagination').html(r.Pagination);
      }
    });
  }

  // Tab filter (list page)
  $(document).on('click', '.prl-tab', function (e) {
    e.preventDefault();
    $('.prl-tab').removeClass('active');
    $(this).addClass('active');
    filterData.PayrollStatus = $(this).data('status');
    loadPage(1, filterData);
  });

  // Mark as Paid
  $(document).on('click', '.prl-mark-paid', function () {
    var uid = $(this).data('uid');
    if (!confirm('Mark this payroll as Paid?')) return;
    $.post('/payroll/markPaid', { PayrollUID: uid }, function (r) {
      if (!r.Error) { toastr.success('Payroll marked as Paid.'); loadPage(currentPage); }
      else toastr.error(r.Message || 'Error.');
    });
  });

  // Mark paid on detail page
  $(document).on('click', '#btnMarkPaid', function () {
    var uid = $(this).data('uid');
    if (!confirm('Mark this payroll as Paid? This cannot be undone.')) return;
    $.post('/payroll/markPaid', { PayrollUID: uid }, function (r) {
      if (!r.Error) { toastr.success('Marked as Paid.'); window.location.reload(); }
      else toastr.error(r.Message || 'Error.');
    });
  });

  /* ── Process page helpers ── */

  function currFmt(v) {
    var sym = typeof PayrollCurrencySymbol !== 'undefined' ? PayrollCurrencySymbol : '₹';
    return sym + ' ' + parseFloat(v || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  function renderLines(lines) {
    payrollLines = lines;
    if (!lines || !lines.length) {
      $('#prlLinesCard').hide();
      $('#prlSummaryRow').css('display', 'none');
      $('#prlEmptyState').show();
      return;
    }
    $('#prlEmptyState').hide();
    $('#prlSummaryRow').css('display', '');
    $('#prlLinesCard').show();

    var totalGross = 0, totalAdv = 0, totalDed = 0, totalNet = 0;
    var html = '';
    $.each(lines, function (i, ln) {
      totalGross += parseFloat(ln.GrossSalary || 0);
      totalAdv   += parseFloat(ln.AdvanceRecovery || 0);
      totalDed   += parseFloat(ln.TotalDeductions || 0);
      totalNet   += parseFloat(ln.NetPayable || 0);
      html += '<tr data-idx="' + i + '">'
        + '<td><div class="fw-semibold">' + (ln.EmployeeName || '') + '</div>'
        + '<div class="text-muted" style="font-size:.75rem;">' + (ln.EmployeeCode || '') + '</div></td>'
        + '<td><span class="badge bg-label-secondary">' + (ln.SalaryType || '') + '</span></td>'
        + '<td>' + parseFloat(ln.WorkingDays || 0).toFixed(0) + '</td>'
        + '<td>' + parseFloat(ln.PresentDays || 0).toFixed(1) + '</td>'
        + '<td>' + parseFloat(ln.AbsentDays || 0).toFixed(1) + '</td>'
        + '<td>' + currFmt(ln.GrossSalary) + '</td>'
        + '<td class="text-warning">' + currFmt(ln.AdvanceRecovery) + '</td>'
        + '<td class="text-danger">' + currFmt(ln.OtherDeductions) + '</td>'
        + '<td class="text-success fw-semibold">' + currFmt(ln.NetPayable) + '</td>'
        + '<td><input type="number" class="form-control form-control-sm prl-adjust" data-idx="' + i + '" value="' + parseFloat(ln.Adjustment || 0).toFixed(2) + '" style="width:80px;" step="0.01"></td>'
        + '</tr>';
    });
    $('#prlLinesBody').html(html);
    $('#sumEmployees').text(lines.length);
    $('#sumGross').text(currFmt(totalGross));
    $('#sumDeductions').text(currFmt(totalDed + totalAdv));
    $('#sumNet').text(currFmt(totalNet));
  }

  // Adjustment field
  $(document).on('change', '.prl-adjust', function () {
    var idx = parseInt($(this).data('idx'));
    var adj = parseFloat($(this).val()) || 0;
    if (payrollLines[idx]) {
      payrollLines[idx].Adjustment = adj;
      var gross = parseFloat(payrollLines[idx].GrossSalary || 0);
      var ded   = parseFloat(payrollLines[idx].TotalDeductions || 0);
      payrollLines[idx].NetPayable = Math.max(0, gross - ded + adj);
      $(this).closest('tr').find('.text-success.fw-semibold').text(currFmt(payrollLines[idx].NetPayable));
    }
  });

  // Load / Recalculate
  $(document).on('click', '#btnLoadPayroll', function () {
    var month = $('#prlMonth').val();
    var year  = $('#prlYear').val();
    var wdays = $('#prlWorkingDays').val();
    if (!month || !year) { toastr.warning('Select month and year.'); return; }
    $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Loading…');
    var self = this;
    $.post('/payroll/getPayrollEmployees', { Month: month, Year: year, WorkingDays: wdays }, function (r) {
      $(self).prop('disabled', false).html('<i class="bx bx-refresh me-1"></i>Load / Recalculate');
      if (!r.Error) { renderLines(r.Employees); }
      else toastr.error(r.Message || 'Error loading employees.');
    });
  });

  function savePayroll(status) {
    if (!payrollLines.length) { toastr.warning('No payroll data loaded.'); return; }
    var payload = {
      PayrollUID:   typeof ExistingPayrollUID !== 'undefined' ? ExistingPayrollUID : 0,
      Month:        $('#prlMonth').val(),
      Year:         $('#prlYear').val(),
      WorkingDays:  $('#prlWorkingDays').val(),
      Notes:        $.trim($('#prlNotes').val()),
      Status:       status,
      Lines:        payrollLines
    };
    $.post('/payroll/savePayroll', payload, function (r) {
      if (!r.Error) {
        toastr.success(r.Message || (status === 'Processed' ? 'Payroll processed.' : 'Draft saved.'));
        setTimeout(function () { window.location.href = '/payroll'; }, 1200);
      } else {
        toastr.error(r.Message || 'Error saving payroll.');
      }
    });
  }

  $(document).on('click', '#btnSaveDraft',      function () { savePayroll('Draft'); });
  $(document).on('click', '#btnProcessPayroll', function () {
    if (!confirm('Finalize and process this payroll?')) return;
    savePayroll('Processed');
  });

  /* ── Shared ── */
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

  // Auto-load on process page if existing
  if (typeof ExistingPayrollUID !== 'undefined' && ExistingPayrollUID > 0 && ExistingPayrollStatus !== 'Paid') {
    $('#btnLoadPayroll').trigger('click');
  }

})();
