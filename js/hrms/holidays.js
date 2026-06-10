'use strict';
(function () {

  var currentPage = 1;
  var filterData  = {};

  function loadPage(page, filter) {
    currentPage = page || 1;
    filterData  = filter || filterData;
    $.post('/holidays/getPageDetails/' + currentPage, { Filter: filterData }, function (r) {
      if (!r.Error) {
        $('#HolidayTableBody').html(r.RecordHtmlData);
        $('#HolidaysPagination').html(r.Pagination);
      }
    });
  }

  function buildFilter() {
    return {
      Year:           $('#holidayYearFilter').val(),
      Month:          $('#holidayMonthFilter').val(),
      SearchAllData:  $.trim($('#SearchDetails').val())
    };
  }

  // Flatpickr
  if (typeof flatpickr !== 'undefined') {
    flatpickr('#holidayDate', { dateFormat: 'Y-m-d', allowInput: true });
  }

  $(document).on('click', '#btnNewHoliday', function () {
    $('#holidayUID').val(0);
    $('#holidayName').val('');
    $('#holidayDate').val('');
    $('#holidayIsOptional').prop('checked', false);
    $('#holidayDesc').val('');
    $('#holidayModalTitle').text('Add Holiday');
    $('#holidayModal').modal('show');
  });

  $(document).on('click', '.holiday-edit-btn', function () {
    $('#holidayUID').val($(this).data('uid'));
    $('#holidayName').val($(this).data('name'));
    $('#holidayDate').val($(this).data('date'));
    $('#holidayIsOptional').prop('checked', $(this).data('optional') == 1);
    $('#holidayDesc').val($(this).data('desc'));
    $('#holidayModalTitle').text('Edit Holiday');
    $('#holidayModal').modal('show');
  });

  $(document).on('click', '#btnSaveHoliday', function () {
    var name = $.trim($('#holidayName').val());
    var date = $.trim($('#holidayDate').val());
    if (!name) { toastr.warning('Holiday name is required.'); return; }
    if (!date) { toastr.warning('Date is required.');        return; }
    var payload = {
      HolidayUID:  $('#holidayUID').val(),
      HolidayName: name,
      HolidayDate: date,
      IsOptional:  $('#holidayIsOptional').is(':checked') ? 1 : 0,
      Description: $.trim($('#holidayDesc').val())
    };
    $(this).prop('disabled', true);
    var self = this;
    $.post('/holidays/save', payload, function (r) {
      $(self).prop('disabled', false);
      if (!r.Error) {
        toastr.success(r.Message || 'Saved.');
        $('#holidayModal').modal('hide');
        loadPage(currentPage);
      } else {
        toastr.error(r.Message || 'Error saving.');
      }
    });
  });

  $(document).on('click', '.holiday-delete-btn', function () {
    if (!confirm('Delete this holiday?')) return;
    $.post('/holidays/delete', { HolidayUID: $(this).data('uid') }, function (r) {
      if (!r.Error) { toastr.success('Deleted.'); loadPage(currentPage); }
      else toastr.error(r.Message || 'Error deleting.');
    });
  });

  // Year/Month filter
  $(document).on('change', '#holidayYearFilter, #holidayMonthFilter', function () { loadPage(1, buildFilter()); });

  var searchTimer;
  $(document).on('input', '#SearchDetails', function () {
    clearTimeout(searchTimer);
    $('#clearSearch').toggleClass('d-none', !$.trim($(this).val()));
    searchTimer = setTimeout(function () { loadPage(1, buildFilter()); }, 350);
  });
  $(document).on('click', '#clearSearch', function () { $('#SearchDetails').val(''); $(this).addClass('d-none'); loadPage(1, buildFilter()); });
  $(document).on('click', '.PageRefresh', function (e) { e.preventDefault(); loadPage(currentPage); });
  $(document).on('click', '.page-link', function (e) { e.preventDefault(); var pg = $(this).data('page'); if (pg) loadPage(pg); });

})();
