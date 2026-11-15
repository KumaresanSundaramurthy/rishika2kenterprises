/* global $, global_base_url, global_services_url, JwtData, RowLimit, currencySymbol, decimalPlaces, ajaxLoading */
'use strict';
(function () {

  var currentPage = 1;
  var filterData  = {};

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
      return '<tr><td colspan="8" class="text-center text-muted py-4"><i class="bx bx-file me-1"></i>No payslips found.</td></tr>';
    }
    var months     = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    var badgeClass = { Draft: 'secondary', Processed: 'primary', Paid: 'success' };
    var html = '';
    $.each(rows, function (i, row) {
      var uid    = row.TablePrimaryUID;
      var status = row.PayrollStatus || 'Draft';
      var badge  = badgeClass[status] || 'secondary';
      var mo     = parseInt(row.PayrollMonth || 0, 10);
      var yr     = parseInt(row.PayrollYear  || 0, 10);
      var period = (months[mo] || '—') + (yr ? ' ' + yr : '');
      var fmt    = function (v) { return currencySymbol + ' ' + parseFloat(v || 0).toFixed(decimalPlaces); };
      html += '<tr>'
        + '<td class="text-muted">' + (offset + i + 1) + '</td>'
        + '<td><div class="fw-semibold">' + _esc(row.EmployeeName || '') + '</div>'
        + '<div class="text-muted" style="font-size:var(--r2k-text-75);">' + _esc(row.EmployeeCode || '') + '</div></td>'
        + '<td class="fw-semibold">' + period + '</td>'
        + '<td>' + fmt(row.GrossSalary) + '</td>'
        + '<td class="text-danger">' + fmt(row.TotalDeductions) + '</td>'
        + '<td class="text-success fw-semibold">' + fmt(row.NetPayable) + '</td>'
        + '<td><span class="badge bg-label-' + badge + '">' + status + '</span></td>'
        + '<td><div class="d-flex align-items-center gap-1">'
        + '<a href="' + global_base_url + 'payslips/view/' + uid + '" class="btn btn-icon btn-sm text-primary" title="View"><i class="bx bx-file"></i></a>'
        + '<a href="' + global_base_url + 'payslips/print/' + uid + '" class="btn btn-icon btn-sm text-secondary" title="Print" target="_blank"><i class="bx bx-printer"></i></a>'
        + '</div></td>'
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
      url:      global_services_url + 'api/payslips/getPageDetails/' + currentPage,
      method:   'GET',
      data:     { orgId: parseInt(JwtData.Org.OrgUID, 10), RowLimit: RowLimit, Filter: JSON.stringify(filterData) },
      dataType: 'json',
      success:  function (r) {
        if (r.Error) { toastr.error(r.Message || 'Failed to load payslips.'); return; }
        var rows   = (r.Data && r.Data.Data)       ? r.Data.Data                    : [];
        var total  = (r.Data && r.Data.TotalCount) ? parseInt(r.Data.TotalCount, 10) : 0;
        var offset = (currentPage - 1) * RowLimit;
        $('#PayslipTableBody').html(_renderRows(rows, offset));
        $('#PayslipsPagination').html(_buildPagination(total, currentPage, RowLimit));
      },
      error:    function () { toastr.error('Failed to load payslips.'); },
      complete: function () { ajaxLoading(0); },
    });
  }

  /**
   * Fetches employee list from NestJS and populates the employee filter dropdown.
   * @returns {void}
   */
  function _loadEmployeeFilter() {
    $.ajax({
      url:      global_services_url + 'api/payslips/getPageData',
      method:   'GET',
      data:     { orgId: parseInt(JwtData.Org.OrgUID, 10) },
      dataType: 'json',
      success:  function (r) {
        var employees = (r.Data && r.Data.EmployeeList) ? r.Data.EmployeeList : [];
        var opts = '<option value="">All Employees</option>';
        $.each(employees, function (i, e) {
          opts += '<option value="' + e.EmployeeUID + '">' + _esc(e.EmployeeName + ' (' + e.EmployeeCode + ')') + '</option>';
        });
        $('#psEmpFilter').html(opts);
      },
    });
  }

  // Filter button
  $(document).on('click', '#btnPsFilter', function () {
    filterData.EmployeeUID   = $('#psEmpFilter').val();
    filterData.Month         = $('#psMonthFilter').val();
    filterData.Year          = $('#psYearFilter').val();
    loadPage(1, filterData);
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
  $(document).on('click', '.page-link',   function (e) { e.preventDefault(); var pg = $(this).data('page'); if (pg) loadPage(pg); });

  $(function () {
    _loadEmployeeFilter();
    loadPage(1);
  });

})();
