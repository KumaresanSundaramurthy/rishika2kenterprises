/* global $, global_base_url, global_services_url, JwtData, RowLimit, currencySymbol, decimalPlaces, ajaxLoading */
'use strict';
(function () {

  var currentPage  = 1;
  var filterData   = {};
  var payrollLines = [];

  // ── List page helpers ──────────────────────────────────────────────────────

  /**
   * @param {string} val
   * @returns {string}
   */
  function _esc(val) {
    return $('<div>').text(val).html();
  }

  /**
   * @param {Array}  rows
   * @param {number} offset
   * @returns {string}
   */
  function _renderRows(rows, offset) {
    if (!rows || !rows.length) {
      return '<tr><td colspan="9" class="text-center text-muted py-4"><i class="bx bx-calculator me-1"></i>No payrolls found. <a href="' + global_base_url + 'payroll/process">Process your first payroll</a>.</td></tr>';
    }
    var months     = ['', 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
    var badgeClass = { Draft: 'secondary', Processed: 'primary', Paid: 'success' };
    var mon3       = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    var html = '';
    $.each(rows, function (i, row) {
      var uid    = row.TablePrimaryUID;
      var status = row.PayrollStatus || 'Draft';
      var badge  = badgeClass[status] || 'secondary';
      var mo     = parseInt(row.PayrollMonth || 0, 10);
      var yr     = parseInt(row.PayrollYear  || 0, 10);
      var period = (months[mo] || '—') + (yr ? ' ' + yr : '');
      var fmt    = function (v) { return currencySymbol + ' ' + parseFloat(v || 0).toFixed(decimalPlaces); };
      var processedOn = '—';
      if (row.ProcessedOn) {
        var d = new Date(row.ProcessedOn);
        if (!isNaN(d.getTime())) {
          processedOn = d.getDate() + ' ' + mon3[d.getMonth()] + ' ' + d.getFullYear();
        }
      }
      var actions = '<a href="' + global_base_url + 'payroll/detail/' + uid + '" class="btn btn-icon btn-sm text-primary" title="View Detail"><i class="bx bx-show"></i></a>';
      if (status !== 'Paid') {
        actions += '<a href="' + global_base_url + 'payroll/process?id=' + uid + '" class="btn btn-icon btn-sm text-warning" title="Edit/Reprocess"><i class="bx bx-edit"></i></a>';
      }
      if (status === 'Processed') {
        actions += '<button class="btn btn-icon btn-sm text-success prl-mark-paid" data-uid="' + uid + '" title="Mark as Paid"><i class="bx bx-check-circle"></i></button>';
      }
      html += '<tr>'
        + '<td class="text-muted">' + (offset + i + 1) + '</td>'
        + '<td><div class="fw-semibold">' + period + '</div>'
        + '<div class="text-muted" style="font-size:var(--r2k-text-75);">' + processedOn + '</div></td>'
        + '<td class="text-center fw-semibold">' + parseInt(row.EmployeeCount || 0, 10) + '</td>'
        + '<td>' + fmt(row.TotalGross) + '</td>'
        + '<td class="text-danger">' + fmt(row.TotalDeductions) + '</td>'
        + '<td class="text-success fw-semibold">' + fmt(row.TotalNetPayable) + '</td>'
        + '<td><span class="badge bg-label-' + badge + '">' + status + '</span></td>'
        + '<td class="text-muted">' + _esc(row.ProcessedByName || '—') + '</td>'
        + '<td><div class="d-flex align-items-center gap-1">' + actions + '</div></td>'
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

  /**
   * @param {number} [page]
   * @param {Object} [filter]
   * @returns {void}
   */
  function loadPage(page, filter) {
    currentPage = page || 1;
    filterData  = filter || filterData;
    ajaxLoading(1);
    $.ajax({
      url:      global_services_url + 'api/payroll/getPageDetails/' + currentPage,
      method:   'GET',
      data:     { orgId: parseInt(JwtData.Org.OrgUID, 10), RowLimit: RowLimit, Filter: JSON.stringify(filterData) },
      dataType: 'json',
      success:  function (r) {
        if (r.Error) { toastr.error(r.Message || 'Failed to load payrolls.'); return; }
        var rows   = (r.Data && r.Data.Data)       ? r.Data.Data                    : [];
        var total  = (r.Data && r.Data.TotalCount) ? parseInt(r.Data.TotalCount, 10) : 0;
        var offset = (currentPage - 1) * RowLimit;
        $('#PayrollTableBody').html(_renderRows(rows, offset));
        $('#PayrollPagination').html(_buildPagination(total, currentPage, RowLimit));
      },
      error:    function () { toastr.error('Failed to load payrolls.'); },
      complete: function () { ajaxLoading(0); },
    });
  }

  /**
   * Fetches payroll stats from NestJS and updates the stat cards.
   * @returns {void}
   */
  function _loadStats() {
    $.ajax({
      url:      global_services_url + 'api/payroll/getPageData',
      method:   'GET',
      data:     { orgId: parseInt(JwtData.Org.OrgUID, 10) },
      dataType: 'json',
      success:  function (r) {
        if (r.Error || !r.Data || !r.Data.PayrollStats) return;
        var s = r.Data.PayrollStats;
        $('#prlStatTotal').text(s.Total      || 0);
        $('#prlStatDraft').text(s.Draft      || 0);
        $('#prlStatProcessed').text(s.Processed || 0);
        $('#prlStatPaid').text(s.Paid        || 0);
        $('#prlStatPaidAmt').text(currencySymbol + ' ' + parseFloat(s.TotalPaid || 0).toFixed(decimalPlaces));
      },
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
    $.post(global_base_url + 'payroll/markPaid', { PayrollUID: uid }, function (r) {
      if (!r.Error) { toastr.success('Payroll marked as Paid.'); loadPage(currentPage); _loadStats(); }
      else toastr.error(r.Message || 'Error.');
    });
  });

  // Mark paid on detail page
  $(document).on('click', '#btnMarkPaid', function () {
    var uid = $(this).data('uid');
    if (!confirm('Mark this payroll as Paid? This cannot be undone.')) return;
    $.post(global_base_url + 'payroll/markPaid', { PayrollUID: uid }, function (r) {
      if (!r.Error) { toastr.success('Marked as Paid.'); window.location.reload(); }
      else toastr.error(r.Message || 'Error.');
    });
  });

  // ── Process page helpers ───────────────────────────────────────────────────

  /**
   * @param {number} v
   * @returns {string}
   */
  function currFmt(v) {
    var sym = typeof PayrollCurrencySymbol !== 'undefined' ? PayrollCurrencySymbol : currencySymbol;
    return sym + ' ' + parseFloat(v || 0).toLocaleString('en-IN', { minimumFractionDigits: decimalPlaces, maximumFractionDigits: decimalPlaces });
  }

  /**
   * @param {Array} lines
   * @returns {void}
   */
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
        + '<div class="text-muted" style="font-size:var(--r2k-text-75);">' + (ln.EmployeeCode || '') + '</div></td>'
        + '<td><span class="badge bg-label-secondary">' + (ln.SalaryType || '') + '</span></td>'
        + '<td>' + parseFloat(ln.WorkingDays || 0).toFixed(0) + '</td>'
        + '<td>' + parseFloat(ln.PresentDays || 0).toFixed(1) + '</td>'
        + '<td>' + parseFloat(ln.AbsentDays || 0).toFixed(1) + '</td>'
        + '<td>' + currFmt(ln.GrossSalary) + '</td>'
        + '<td class="text-warning">' + currFmt(ln.AdvanceRecovery) + '</td>'
        + '<td class="text-danger">' + currFmt(ln.OtherDeductions) + '</td>'
        + '<td class="text-success fw-semibold">' + currFmt(ln.NetPayable) + '</td>'
        + '<td><input type="number" class="form-control form-control-sm prl-adjust" data-idx="' + i + '" value="' + parseFloat(ln.Adjustment || 0).toFixed(2) + '" style="width:80px;" step="any"></td>'
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
    var idx = parseInt($(this).data('idx'), 10);
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
    $.post(global_base_url + 'payroll/getPayrollEmployees', { Month: month, Year: year, WorkingDays: wdays }, function (r) {
      $(self).prop('disabled', false).html('<i class="bx bx-refresh me-1"></i>Load / Recalculate');
      if (!r.Error) { renderLines(r.Employees); }
      else toastr.error(r.Message || 'Error loading employees.');
    });
  });

  /**
   * @param {string} status
   * @returns {void}
   */
  function savePayroll(status) {
    if (!payrollLines.length) { toastr.warning('No payroll data loaded.'); return; }
    var payload = {
      PayrollUID:  typeof ExistingPayrollUID !== 'undefined' ? ExistingPayrollUID : 0,
      Month:       $('#prlMonth').val(),
      Year:        $('#prlYear').val(),
      WorkingDays: $('#prlWorkingDays').val(),
      Notes:       $.trim($('#prlNotes').val()),
      Status:      status,
      Lines:       payrollLines,
    };
    $.post(global_base_url + 'payroll/savePayroll', payload, function (r) {
      if (!r.Error) {
        toastr.success(r.Message || (status === 'Processed' ? 'Payroll processed.' : 'Draft saved.'));
        setTimeout(function () { window.location.href = global_base_url + 'payroll'; }, 1200);
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

  // ── Shared ────────────────────────────────────────────────────────────────

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
  $(document).on('click', '.page-link',   function (e) { e.preventDefault(); var pg = $(this).data('page'); if (pg) loadPage(pg); });

  // Auto-load on process page if existing
  if (typeof ExistingPayrollUID !== 'undefined' && ExistingPayrollUID > 0 && ExistingPayrollStatus !== 'Paid') {
    $('#btnLoadPayroll').trigger('click');
  }

  $(function () {
    if ($('#PayrollTableBody').length) {
      _loadStats();
      loadPage(1);
    }
  });

})();
