'use strict';
(function () {

  var currentPage = 1;
  var filterData  = {};
  var _fpAdvDate  = null;
  var _advFpCfg   = {
    dateFormat : 'Y-m-d',
    altInput   : true,
    altFormat  : (typeof _transFormDateFormat !== 'undefined') ? _transFormDateFormat : 'd-m-Y',
    allowInput : false,
    static     : true,
    position   : 'below left',
  };

  // ── Apply fresh table data from any AJAX response ─────────────────────────
  function _applyResponse(r) {
    currentPage = 1;
    $('#AdvTableBody').html(r.RecordHtmlData);
    $('#SalaryadvancesPagination').html(r.Pagination);
    if (r.Stats) _updateStats(r.Stats);
  }

  function _updateStats(s) {
    $('.adv-s-total').text(s.TotalCount       || 0);
    $('.adv-s-requested').text(s.RequestedCount  || 0);
    $('.adv-s-approved').text(s.ApprovedCount   || 0);
    $('.adv-s-settled').text(s.SettledCount    || 0);
  }

  function loadPage(page, filter) {
    currentPage = page || 1;
    filterData  = filter || filterData;
    $.post('/salaryadvances/getPageDetails/' + currentPage, { Filter: filterData, [CsrfName]: CsrfToken }, function (r) {
      CsrfToken = r.NewCsrfToken || CsrfToken;
      if (!r.Error) {
        $('#AdvTableBody').html(r.RecordHtmlData);
        $('#SalaryadvancesPagination').html(r.Pagination);
        if (r.Stats) _updateStats(r.Stats);
      }
    });
  }

  // ── Flatpickr init on modal open (once) ───────────────────────────────────
  $('#advanceModal').on('shown.bs.modal', function () {
    if (!_fpAdvDate) {
      _fpAdvDate = flatpickr('#advDate', _advFpCfg);
    }
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

    $.post('/salaryadvances/save', {
      AdvanceUID:    $('#advUID').val(),
      EmployeeUID:   emp,
      AdvanceDate:   date,
      AdvanceAmount: amount,
      Remarks:       $.trim($('#advRemarks').val()),
      Filter:        filterData,
      [CsrfName]:    CsrfToken,
    }, function (r) {
      CsrfToken = r.NewCsrfToken || CsrfToken;
      $btn.prop('disabled', false);
      $spinner.addClass('d-none'); $icon.removeClass('d-none');
      if (!r.Error) {
        $('#advanceModal').modal('hide');
        _applyResponse(r);
        showToastNotification(r.Message || 'Saved.', 'success');
      } else {
        showToastNotification(r.Message || 'Error saving.', 'error');
      }
    }).fail(function () {
      $btn.prop('disabled', false);
      $spinner.addClass('d-none'); $icon.removeClass('d-none');
      showToastNotification('Request failed. Please try again.', 'error');
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
      $.post('/salaryadvances/approve', { AdvanceUID: uid, Filter: filterData, [CsrfName]: CsrfToken }, function (r) {
        CsrfToken = r.NewCsrfToken || CsrfToken;
        if (!r.Error) {
          _applyResponse(r);
          showToastNotification(r.Message || 'Advance approved.', 'success');
        } else {
          showToastNotification(r.Message || 'Failed to approve.', 'error');
        }
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
      $.post('/salaryadvances/reject', { AdvanceUID: uid, Filter: filterData, [CsrfName]: CsrfToken }, function (r) {
        CsrfToken = r.NewCsrfToken || CsrfToken;
        if (!r.Error) {
          _applyResponse(r);
          showToastNotification(r.Message || 'Advance rejected.', 'success');
        } else {
          showToastNotification(r.Message || 'Failed to reject.', 'error');
        }
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
      $.post('/salaryadvances/delete', { AdvanceUID: uid, Filter: filterData, [CsrfName]: CsrfToken }, function (r) {
        CsrfToken = r.NewCsrfToken || CsrfToken;
        if (!r.Error) {
          _applyResponse(r);
          showToastNotification(r.Message || 'Advance deleted.', 'success');
        } else {
          showToastNotification(r.Message || 'Cannot delete this advance.', 'error');
        }
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
    var pg = parseInt($(this).data('page'));
    if (pg && pg !== currentPage) loadPage(pg);
  });

})();
