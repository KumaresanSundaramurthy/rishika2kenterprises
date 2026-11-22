/* global $, global_services_url, JwtData, JwtToken, RowLimit, currencySymbol, decimalPlaces, ajaxLoading, _transFormDateFormat, showToastNotification, t, Swal, flatpickr */
'use strict';
(function () {

  var currentPage      = 1;
  var filterData       = {};
  var _fpAdvDate       = null;
  var _advIsDirty      = false;
  var _advIsCreateMode = false;
  var _advFpCfg = {
    dateFormat : 'Y-m-d',
    altInput   : true,
    altFormat  : (typeof _transFormDateFormat !== 'undefined') ? _transFormDateFormat : 'd-m-Y',
    allowInput : false,
    static     : true,
    position   : 'below left',
  };

  /**
   * @param {string} val
   * @returns {string}
   */
  function _esc(val) {
    return $('<div>').text(val).html();
  }

  /**
   * @param {number|string} v
   * @returns {string}
   */
  function _fmt(v) {
    return currencySymbol + ' ' + parseFloat(v || 0).toFixed(decimalPlaces);
  }

  /**
   * @param {Array}  rows
   * @param {number} offset
   * @returns {string}
   */
  function _renderRows(rows, offset) {
    if (!rows || !rows.length) {
      return '<tr><td colspan="9" class="text-center text-muted py-4"><i class="bx bx-money-withdraw me-1"></i>No salary advances found.</td></tr>';
    }
    var statusBadge = { Requested: 'primary', Approved: 'warning', Settled: 'success', Rejected: 'danger' };
    var html = '';
    $.each(rows, function (i, row) {
      var uid      = row.TablePrimaryUID;
      var status   = row.AdvanceStatus || 'Requested';
      var badge    = statusBadge[status] || 'secondary';
      var empName  = _esc(row.EmployeeName || '—');
      var empCode  = row.EmployeeCode ? ' (' + _esc(row.EmployeeCode) + ')' : '';
      var pending  = parseFloat(row.BalancePending || 0);
      var recovered = parseFloat(row.AmountRecovered || 0) || (parseFloat(row.AdvanceAmount || 0) - pending);

      var actions = '';
      if (status === 'Requested') {
        actions += '<button class="btn btn-icon btn-sm text-primary adv-edit-btn"'
          + ' data-uid="' + uid + '"'
          + ' data-employee="' + parseInt(row.UserUID || 0, 10) + '"'
          + ' data-date="' + _esc(row.AdvanceDate || '') + '"'
          + ' data-amount="' + parseFloat(row.AdvanceAmount || 0).toFixed(decimalPlaces) + '"'
          + ' data-remarks="' + _esc(row.Reason || '') + '"'
          + ' title="Edit"><i class="bx bx-edit"></i></button>';
        actions += '<button class="btn btn-icon btn-sm text-success adv-approve-btn" data-uid="' + uid + '" title="Approve"><i class="bx bx-check-circle"></i></button>';
        actions += '<button class="btn btn-icon btn-sm text-warning adv-reject-btn" data-uid="' + uid + '" title="Reject"><i class="bx bx-x-circle"></i></button>';
        actions += '<button class="btn btn-icon btn-sm text-danger adv-delete-btn" data-uid="' + uid + '" title="Delete"><i class="bx bx-trash"></i></button>';
      }

      html += '<tr>'
        + '<td class="text-muted">' + (offset + i + 1) + '</td>'
        + '<td><div class="fw-semibold">' + empName + empCode + '</div></td>'
        + '<td>' + _esc(row.AdvanceDate || '—') + '</td>'
        + '<td class="fw-semibold">' + _fmt(row.AdvanceAmount) + '</td>'
        + '<td>' + _fmt(recovered) + '</td>'
        + '<td class="fw-semibold ' + (pending > 0 ? 'text-danger' : 'text-muted') + '">' + _fmt(pending) + '</td>'
        + '<td><span class="badge bg-label-' + badge + '">' + status + '</span></td>'
        + '<td class="text-muted">' + _esc(row.Reason || '—') + '</td>'
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
   * @param {Object} s
   * @returns {void}
   */
  function _updateStats(s) {
    if (!s) return;
    $('.adv-s-total').text(s.TotalCount || 0);
    $('.adv-s-requested').text(s.RequestedCount || 0);
    $('.adv-s-approved').text(s.ApprovedCount || 0);
    $('.adv-s-settled').text(s.SettledCount || 0);
    if (s.TotalAmount)     $('.adv-s-total-amt').text(_fmt(s.TotalAmount));
    if (s.RequestedAmount) $('.adv-s-requested-amt').text(_fmt(s.RequestedAmount));
    if (s.ApprovedAmount)  $('.adv-s-approved-amt').text(_fmt(s.ApprovedAmount));
    if (s.SettledAmount)   $('.adv-s-settled-amt').text(_fmt(s.SettledAmount));
  }

  /**
   * @param {number} [page]
   * @param {Object} [filter]
   * @returns {void}
   */
  function loadPage(page, filter) {
    currentPage = page || 1;
    if (filter !== undefined) filterData = filter;
    ajaxLoading(1);
    $.ajax({
      url:      global_services_url + 'api/salaryadvances/getPageDetails/' + currentPage,
      method:   'GET',
      data:     { orgId: parseInt(JwtData.Org.OrgUID, 10), RowLimit: RowLimit, Filter: JSON.stringify(filterData) },
      dataType: 'json',
      success:  function (r) {
        if (r.Error) { showToastNotification(r.Message || 'Failed to load advances.', 'error'); return; }
        var rows   = (r.Data && r.Data.Data)       ? r.Data.Data                     : [];
        var total  = (r.Data && r.Data.TotalCount) ? parseInt(r.Data.TotalCount, 10) : 0;
        var offset = (currentPage - 1) * RowLimit;
        $('#AdvTableBody').html(_renderRows(rows, offset));
        $('#SalaryadvancesPagination').html(_buildPagination(total, currentPage, RowLimit));
        if (r.Data && r.Data.Stats) _updateStats(r.Data.Stats);
      },
      error:    function () { showToastNotification('Failed to load advances.', 'error'); },
      complete: function () { ajaxLoading(0); },
    });
  }

  /**
   * Fetches employee list from NestJS and populates the advance modal dropdown.
   * @returns {void}
   */
  function _loadEmployeeFilter() {
    $.ajax({
      url:      global_services_url + 'api/salaryadvances/getPageData',
      method:   'GET',
      data:     { orgId: parseInt(JwtData.Org.OrgUID, 10) },
      dataType: 'json',
      success:  function (r) {
        if (r.Error || !r.Data || !r.Data.EmployeeList) return;
        var html = '<option value="">— Select Employee —</option>';
        $.each(r.Data.EmployeeList, function (_, e) {
          var code = e.EmployeeCode ? ' (' + _esc(e.EmployeeCode) + ')' : '';
          html += '<option value="' + parseInt(e.EmployeeUID, 10) + '">' + _esc(e.EmployeeName) + code + '</option>';
        });
        $('#advEmployee').html(html);
      },
    });
  }

  // ── Flatpickr init on modal open (once) ───────────────────────────────────
  $('#advanceModal').on('shown.bs.modal', function () {
    if (!_fpAdvDate) _fpAdvDate = flatpickr('#advDate', _advFpCfg);
  });

  // ── Stat strip clicks ──────────────────────────────────────────────────────
  $(document).on('click', '.adv-stat-clickable', function () {
    $('.adv-stat-clickable').removeClass('active');
    $(this).addClass('active');
    var f = $(this).data('filter');
    $('.adv-tab').removeClass('active');
    $('.adv-tab[data-filter="' + f + '"]').addClass('active');
    delete filterData.AdvanceStatus;
    if (f !== 'All') filterData.AdvanceStatus = f;
    loadPage(1, filterData);
  });

  // ── Tab clicks ────────────────────────────────────────────────────────────
  $(document).on('click', '.adv-tab', function (e) {
    e.preventDefault();
    $('.adv-tab').removeClass('active');
    $(this).addClass('active');
    var f = $(this).data('filter');
    $('.adv-stat-clickable').removeClass('active');
    $('.adv-stat-clickable[data-filter="' + f + '"]').addClass('active');
    delete filterData.AdvanceStatus;
    if (f !== 'All') filterData.AdvanceStatus = f;
    loadPage(1, filterData);
  });

  // ── New Advance ───────────────────────────────────────────────────────────
  $(document).on('click', '#btnNewAdvance', function () {
    $('#advUID').val(0);
    $('#advEmployee').val('');
    if (_fpAdvDate) _fpAdvDate.clear(); else $('#advDate').val('');
    $('#advAmount').val('');
    $('#advRemarks').val('');
    $('#advModalTitle').text('New Salary Advance');
    $('#advModalMeta').text('Submit a new advance request');
    $('#advanceModal').modal('show');
    _advIsCreateMode = true;
    _advIsDirty      = false;
  });

  // ── Edit Advance ──────────────────────────────────────────────────────────
  $(document).on('click', '.adv-edit-btn', function () {
    $('#advUID').val($(this).data('uid'));
    $('#advEmployee').val($(this).data('employee'));
    var d = $(this).data('date');
    if (_fpAdvDate) _fpAdvDate.setDate(d); else $('#advDate').val(d);
    $('#advAmount').val($(this).data('amount'));
    $('#advRemarks').val($(this).data('remarks'));
    $('#advModalTitle').text('Edit Advance Request');
    $('#advModalMeta').text('Update the advance details');
    $('#advanceModal').modal('show');
  });

  // ── Save Advance ──────────────────────────────────────────────────────────
  $(document).on('click', '#btnSaveAdvance', function () {
    var emp    = $('#advEmployee').val();
    var date   = $.trim($('#advDate').val());
    var amount = parseFloat($('#advAmount').val()) || 0;
    if (!emp)        { showToastNotification('Select an employee.', 'warning'); return; }
    if (!date)       { showToastNotification('Date is required.', 'warning'); return; }
    if (amount <= 0) { showToastNotification('Enter a valid amount.', 'warning'); return; }

    var $btn     = $(this).prop('disabled', true);
    var $spinner = $('#spinnerAdv').removeClass('d-none');
    var $icon    = $('#iconAdv').addClass('d-none');

    $.ajax({
      url:         global_services_url + 'api/salaryadvances/save',
      method:      'POST',
      headers:     { Authorization: 'Bearer ' + JwtToken },
      contentType: 'application/json',
      data:        JSON.stringify({
        orgId:         parseInt(JwtData.Org.OrgUID, 10),
        userId:        parseInt(JwtData.User.UserUID, 10),
        AdvanceUID:    parseInt($('#advUID').val() || 0, 10),
        EmployeeUID:   parseInt(emp, 10),
        AdvanceDate:   date,
        AdvanceAmount: amount,
        Remarks:       $.trim($('#advRemarks').val()),
      }),
      dataType: 'json',
      success: function (r) {
        $btn.prop('disabled', false);
        $spinner.addClass('d-none'); $icon.removeClass('d-none');
        if (!r.Error) {
          _advIsDirty      = false;
          _advIsCreateMode = false;
          $('#advanceModal').modal('hide');
          loadPage(1);
          showToastNotification(r.Message || 'Saved.', 'success');
        } else {
          showToastNotification(r.Message || 'Error saving.', 'error');
        }
      },
      error: function () {
        $btn.prop('disabled', false);
        $spinner.addClass('d-none'); $icon.removeClass('d-none');
        showToastNotification('Request failed. Please try again.', 'error');
      },
    });
  });

  // ── Approve ───────────────────────────────────────────────────────────────
  $(document).on('click', '.adv-approve-btn', function () {
    var uid = $(this).data('uid');
    Swal.fire({
      title: 'Approve this advance?',
      text: 'The advance will be marked as approved and ready for disbursement.',
      icon: 'question', showCancelButton: true,
      confirmButtonColor: '#10b981', cancelButtonColor: '#64748b',
      confirmButtonText: 'Yes, approve',
    }).then(function (res) {
      if (!res.isConfirmed) return;
      $.ajax({
        url:         global_services_url + 'api/salaryadvances/approve',
        method:      'POST',
        headers:     { Authorization: 'Bearer ' + JwtToken },
        contentType: 'application/json',
        data:        JSON.stringify({ orgId: parseInt(JwtData.Org.OrgUID, 10), userId: parseInt(JwtData.User.UserUID, 10), AdvanceUID: parseInt(uid, 10) }),
        dataType:    'json',
        success: function (r) {
          if (!r.Error) { loadPage(currentPage); showToastNotification(r.Message || 'Advance approved.', 'success'); }
          else showToastNotification(r.Message || 'Failed to approve.', 'error');
        },
        error: function () { showToastNotification('Request failed. Please try again.', 'error'); },
      });
    });
  });

  // ── Reject ────────────────────────────────────────────────────────────────
  $(document).on('click', '.adv-reject-btn', function () {
    var uid = $(this).data('uid');
    Swal.fire({
      title: 'Reject this advance?',
      text: 'The request will be marked as rejected.',
      icon: 'warning', showCancelButton: true,
      confirmButtonColor: '#dc2626', cancelButtonColor: '#64748b',
      confirmButtonText: 'Yes, reject',
    }).then(function (res) {
      if (!res.isConfirmed) return;
      $.ajax({
        url:         global_services_url + 'api/salaryadvances/reject',
        method:      'POST',
        headers:     { Authorization: 'Bearer ' + JwtToken },
        contentType: 'application/json',
        data:        JSON.stringify({ orgId: parseInt(JwtData.Org.OrgUID, 10), userId: parseInt(JwtData.User.UserUID, 10), AdvanceUID: parseInt(uid, 10) }),
        dataType:    'json',
        success: function (r) {
          if (!r.Error) { loadPage(currentPage); showToastNotification(r.Message || 'Advance rejected.', 'success'); }
          else showToastNotification(r.Message || 'Failed to reject.', 'error');
        },
        error: function () { showToastNotification('Request failed. Please try again.', 'error'); },
      });
    });
  });

  // ── Delete ────────────────────────────────────────────────────────────────
  $(document).on('click', '.adv-delete-btn', function () {
    var uid = $(this).data('uid');
    Swal.fire({
      title: 'Delete this advance?',
      text: 'This cannot be undone.',
      icon: 'warning', showCancelButton: true,
      confirmButtonColor: '#dc2626', cancelButtonColor: '#64748b',
      confirmButtonText: 'Yes, delete',
    }).then(function (res) {
      if (!res.isConfirmed) return;
      $.ajax({
        url:         global_services_url + 'api/salaryadvances/delete',
        method:      'POST',
        headers:     { Authorization: 'Bearer ' + JwtToken },
        contentType: 'application/json',
        data:        JSON.stringify({ orgId: parseInt(JwtData.Org.OrgUID, 10), userId: parseInt(JwtData.User.UserUID, 10), AdvanceUID: parseInt(uid, 10) }),
        dataType:    'json',
        success: function (r) {
          if (!r.Error) { loadPage(currentPage); showToastNotification(r.Message || 'Advance deleted.', 'success'); }
          else showToastNotification(r.Message || 'Cannot delete this advance.', 'error');
        },
        error: function () { showToastNotification('Request failed. Please try again.', 'error'); },
      });
    });
  });

  // ── Search ────────────────────────────────────────────────────────────────
  var _searchTimer;
  $(document).on('input', '#SearchDetails', function () {
    clearTimeout(_searchTimer);
    var val = $.trim($(this).val());
    $('#clearSearch').toggleClass('d-none', !val);
    if (val) filterData.SearchAllData = val; else delete filterData.SearchAllData;
    _searchTimer = setTimeout(function () { loadPage(1, filterData); }, 400);
  });
  $(document).on('click', '#clearSearch', function () {
    $('#SearchDetails').val('');
    $(this).addClass('d-none');
    delete filterData.SearchAllData;
    loadPage(1, filterData);
  });

  // ── Refresh & Pagination ──────────────────────────────────────────────────
  $(document).on('click', '.PageRefresh', function (e) { e.preventDefault(); loadPage(currentPage); });
  $(document).on('click', '#SalaryadvancesPagination .page-link', function (e) {
    e.preventDefault();
    var pg = parseInt($(this).data('page'), 10);
    if (pg && pg !== currentPage) loadPage(pg);
  });

  // ── Dirty-tracking ────────────────────────────────────────────────────────
  $(document).on('input change', '#advEmployee, #advDate, #advAmount, #advRemarks', function () {
    if (_advIsCreateMode) _advIsDirty = true;
  });

  // ── Unsaved-changes guard ─────────────────────────────────────────────────
  $(document).on('hide.bs.modal', '#advanceModal', function (e) {
    if (!_advIsDirty || !_advIsCreateMode) return;
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
        _advIsDirty      = false;
        _advIsCreateMode = false;
        $('#advanceModal').modal('hide');
      }
    });
  });

  $(function () {
    _loadEmployeeFilter();
    loadPage(1);
  });

})();
