'use strict';
(function () {

  var currentPage = 1;
  var filterData  = {};

  function loadPage(page, filter) {
    currentPage = page || 1;
    filterData  = filter || filterData;
    $.post('/salaryadvances/getPageDetails/' + currentPage, { Filter: filterData }, function (r) {
      if (!r.Error) {
        $('#AdvTableBody').html(r.RecordHtmlData);
        $('#SalaryadvancesPagination').html(r.Pagination);
      }
    });
  }

  // Flatpickr
  if (typeof flatpickr !== 'undefined') {
    flatpickr('#advDate', { dateFormat: 'Y-m-d', allowInput: true });
  }

  // Stat card filter
  $(document).on('click', '.adv-stat-clickable', function () {
    $('.adv-stat-clickable').removeClass('stat-selected');
    $(this).addClass('stat-selected');
    var f = $(this).data('filter');
    filterData.IsSettled = f === 'Settled' ? 1 : (f === 'Pending' ? 0 : '');
    loadPage(1, filterData);
  });

  // Tab filter
  $(document).on('click', '.adv-tab', function (e) {
    e.preventDefault();
    $('.adv-tab').removeClass('active');
    $(this).addClass('active');
    filterData.IsSettled = $(this).data('filter') === 'Settled' ? 1 : ($(this).data('filter') === 'Pending' ? 0 : '');
    loadPage(1, filterData);
  });

  $(document).on('click', '#btnNewAdvance', function () {
    $('#advUID').val(0);
    $('#advEmployee').val('');
    $('#advDate').val('');
    $('#advAmount').val('');
    $('#advRemarks').val('');
    $('#advModalTitle').text('New Salary Advance');
    $('#advanceModal').modal('show');
  });

  $(document).on('click', '.adv-edit-btn', function () {
    $('#advUID').val($(this).data('uid'));
    $('#advEmployee').val($(this).data('employee'));
    $('#advDate').val($(this).data('date'));
    $('#advAmount').val($(this).data('amount'));
    $('#advRemarks').val($(this).data('remarks'));
    $('#advModalTitle').text('Edit Advance');
    $('#advanceModal').modal('show');
  });

  $(document).on('click', '#btnSaveAdvance', function () {
    var emp    = $('#advEmployee').val();
    var date   = $.trim($('#advDate').val());
    var amount = parseFloat($('#advAmount').val()) || 0;
    if (!emp)         { toastr.warning('Select an employee.'); return; }
    if (!date)        { toastr.warning('Date is required.'); return; }
    if (amount <= 0)  { toastr.warning('Enter a valid amount.'); return; }
    var payload = {
      AdvanceUID:  $('#advUID').val(),
      EmployeeUID: emp,
      AdvanceDate: date,
      AdvanceAmount: amount,
      Remarks:     $.trim($('#advRemarks').val())
    };
    $(this).prop('disabled', true);
    var self = this;
    $.post('/salaryadvances/save', payload, function (r) {
      $(self).prop('disabled', false);
      if (!r.Error) {
        toastr.success(r.Message || 'Saved.');
        $('#advanceModal').modal('hide');
        loadPage(currentPage);
      } else {
        toastr.error(r.Message || 'Error saving.');
      }
    });
  });

  $(document).on('click', '.adv-delete-btn', function () {
    if (!confirm('Delete this advance?')) return;
    $.post('/salaryadvances/delete', { AdvanceUID: $(this).data('uid') }, function (r) {
      if (!r.Error) { toastr.success('Deleted.'); loadPage(currentPage); }
      else toastr.error(r.Message || 'Cannot delete a settled advance.');
    });
  });

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
