'use strict';
(function () {

  var currentPage          = 1;
  var filterData           = { Year: new Date().getFullYear() };
  var _holidayIsDirty      = false;
  var _holidayIsCreateMode = false;

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
      Year:          $('#holidayYearFilter').val(),
      Month:         $('#holidayMonthFilter').val(),
      SearchAllData: $.trim($('#SearchDetails').val())
    };
  }

  function _resetModal() {
    _holidayIsCreateMode = false;
    _holidayIsDirty      = false;
    $('#holidayUID').val(0);
    $('#holidayName').val('').removeClass('is-invalid');
    if (fpDate) { fpDate.clear(); } else { $('#holidayDate').val(''); }
    $('#holidayDate').removeClass('is-invalid');
    $('#holidayIsOptional').prop('checked', false);
    $('#holidayDesc').val('');
  }

  // Flatpickr
  var fpDate = null;
  if (typeof flatpickr !== 'undefined') {
    fpDate = flatpickr('#holidayDate', { dateFormat: 'Y-m-d', altInput: true, altFormat: _transFormDateFormat, allowInput: false, static: true, position: "below left" });
  }

  // New
  $(document).on('click', '#btnNewHoliday', function () {
    _resetModal();
    $('#holidayModalTitle').text('Add Holiday');
    $('#holidayModal').modal('show');
    _holidayIsCreateMode = true;
    _holidayIsDirty      = false;
  });

  // Edit
  $(document).on('click', '.holiday-edit-btn', function () {
    _resetModal();
    $('#holidayUID').val($(this).data('uid'));
    $('#holidayName').val($(this).data('name'));
    if (fpDate) { fpDate.setDate($(this).data('date'), false); } else { $('#holidayDate').val($(this).data('date')); }
    $('#holidayDate').removeClass('is-invalid');
    $('#holidayIsOptional').prop('checked', $(this).data('optional') == 1);
    $('#holidayDesc').val($(this).data('desc'));
    $('#holidayModalTitle').text('Edit Holiday');
    $('#holidayModal').modal('show');
  });

  // Clear validation on input
  $(document).on('input', '#holidayName', function () { $(this).removeClass('is-invalid'); });
  $(document).on('change', '#holidayDate', function () { $(this).removeClass('is-invalid'); });

  // Save
  $(document).on('click', '#btnSaveHoliday', function () {
    var name = $.trim($('#holidayName').val());
    var date = $.trim($('#holidayDate').val());
    var valid = true;
    if (!name) { $('#holidayName').addClass('is-invalid'); valid = false; }
    if (!date) { $('#holidayDate').addClass('is-invalid'); valid = false; }
    if (!valid) return;

    var payload = {
      HolidayUID:  $('#holidayUID').val(),
      HolidayName: name,
      HolidayDate: date,
      IsOptional:  $('#holidayIsOptional').is(':checked') ? 1 : 0,
      Description: $.trim($('#holidayDesc').val()),
      CurrentPage: currentPage,
      Filter:      buildFilter()
    };

    var $btn     = $(this).prop('disabled', true);
    var $spinner = $('<span class="spinner-border spinner-border-sm me-1" role="status"></span>');
    $btn.prepend($spinner);

    $.post('/holidays/save', payload, function (r) {
      $spinner.remove();
      $btn.prop('disabled', false);
      if (!r.Error) {
        _holidayIsDirty      = false;
        _holidayIsCreateMode = false;
        $('#holidayModal').modal('hide');
        $('#HolidayTableBody').html(r.RecordHtmlData);
        $('#HolidaysPagination').html(r.Pagination);
        showToastNotification(r.Message || 'Saved.', 'success');
      } else {
        showToastNotification(r.Message || 'Error saving.', 'error');
      }
    }).fail(function () {
      $spinner.remove();
      $btn.prop('disabled', false);
      showToastNotification('Request failed. Please try again.', 'error');
    });
  });

  // Delete
  $(document).on('click', '.holiday-delete-btn', function () {
    if (!confirm('Delete this holiday?')) return;
    var payload = { HolidayUID: $(this).data('uid'), CurrentPage: currentPage, Filter: buildFilter() };
    $.post('/holidays/delete', payload, function (r) {
      if (!r.Error) {
        $('#HolidayTableBody').html(r.RecordHtmlData);
        $('#HolidaysPagination').html(r.Pagination);
        showToastNotification('Deleted.', 'success');
      } else {
        showToastNotification(r.Message || 'Error deleting.', 'error');
      }
    });
  });

  // Year / Month filter
  $(document).on('change', '#holidayYearFilter, #holidayMonthFilter', function () {
    loadPage(1, buildFilter());
  });

  // Search
  var searchTimer;
  $(document).on('input', '#SearchDetails', function () {
    clearTimeout(searchTimer);
    $('#clearSearch').toggleClass('d-none', !$.trim($(this).val()));
    searchTimer = setTimeout(function () { loadPage(1, buildFilter()); }, 350);
  });
  $(document).on('click', '#clearSearch', function () {
    $('#SearchDetails').val('');
    $(this).addClass('d-none');
    loadPage(1, buildFilter());
  });

  // Refresh & pagination
  $(document).on('click', '.PageRefresh', function (e) { e.preventDefault(); loadPage(currentPage, buildFilter()); });
  $(document).on('click', '.page-link', function (e) {
    e.preventDefault();
    var pg = $(this).data('page');
    if (pg) loadPage(pg);
  });

  // ── Dirty-tracking listener ─────────────────────────────────────────────
  $(document).on('input change', '#holidayName, #holidayDate, #holidayIsOptional, #holidayDesc', function () {
    if (_holidayIsCreateMode) _holidayIsDirty = true;
  });

  // ── Unsaved-changes guard ───────────────────────────────────────────────
  $(document).on('hide.bs.modal', '#holidayModal', function (e) {
    if (!_holidayIsDirty || !_holidayIsCreateMode) return;
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
        _holidayIsDirty      = false;
        _holidayIsCreateMode = false;
        $('#holidayModal').modal('hide');
      }
    });
  });

})();
