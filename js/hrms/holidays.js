/* global $, global_services_url, JwtData, JwtToken, RowLimit, ajaxLoading, _transFormDateFormat, showToastNotification, t, Swal, flatpickr */
'use strict';
(function () {

  var currentPage          = 1;
  var filterData           = { Year: new Date().getFullYear() };
  var _holidayIsDirty      = false;
  var _holidayIsCreateMode = false;

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
      return '<tr><td colspan="6" class="text-center text-muted py-4"><i class="bx bx-calendar me-1"></i>No holidays found for this period.</td></tr>';
    }
    var days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    var snoClass = (JwtData && JwtData.GenSettings && parseInt(JwtData.GenSettings.SerialNoDisplay || 0, 10) === 1) ? '' : 'd-none';
    var html = '';
    $.each(rows, function (i, row) {
      var uid       = row.TablePrimaryUID;
      var rawDate   = row.HolidayDate || '';
      var dayOfWeek = '';
      if (rawDate) {
        var d = new Date(rawDate);
        if (!isNaN(d.getTime())) dayOfWeek = days[d.getDay()];
      }
      var isOptional = parseInt(row.IsOptional || 0, 10);
      html += '<tr>'
        + '<td class="' + snoClass + '">' + (offset + i + 1) + '</td>'
        + '<td class="fw-semibold">' + _esc(row.HolidayName || '—') + '</td>'
        + '<td>' + _esc(rawDate) + '</td>'
        + '<td>' + dayOfWeek + '</td>'
        + '<td><span class="badge bg-label-' + (isOptional ? 'warning' : 'primary') + '">' + (isOptional ? 'Optional' : 'Mandatory') + '</span></td>'
        + '<td><div class="d-flex align-items-center gap-1">'
        + '<button class="btn btn-icon btn-sm text-primary holiday-edit-btn"'
        + ' data-uid="' + uid + '"'
        + ' data-name="' + _esc(row.HolidayName || '') + '"'
        + ' data-date="' + _esc(rawDate) + '"'
        + ' data-optional="' + isOptional + '"'
        + ' data-desc="' + _esc(row.Description || '') + '"'
        + ' title="Edit"><i class="bx bx-edit"></i></button>'
        + '<button class="btn btn-icon btn-sm text-danger holiday-delete-btn" data-uid="' + uid + '" title="Delete"><i class="bx bx-trash"></i></button>'
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
   * @returns {Object}
   */
  function buildFilter() {
    return {
      Year:          $('#holidayYearFilter').val(),
      Month:         $('#holidayMonthFilter').val() || undefined,
      SearchAllData: $.trim($('#SearchDetails').val()) || undefined,
    };
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
      url:      global_services_url + 'api/holidays/getPageDetails/' + currentPage,
      method:   'GET',
      data:     { orgId: parseInt(JwtData.Org.OrgUID, 10), RowLimit: RowLimit, Filter: JSON.stringify(filterData) },
      dataType: 'json',
      success:  function (r) {
        if (r.Error) { showToastNotification(r.Message || 'Failed to load holidays.', 'error'); return; }
        var rows   = (r.Data && r.Data.Data)       ? r.Data.Data                     : [];
        var total  = (r.Data && r.Data.TotalCount) ? parseInt(r.Data.TotalCount, 10) : 0;
        var offset = (currentPage - 1) * RowLimit;
        $('#HolidayTableBody').html(_renderRows(rows, offset));
        $('#HolidaysPagination').html(_buildPagination(total, currentPage, RowLimit));
      },
      error:    function () { showToastNotification('Failed to load holidays.', 'error'); },
      complete: function () { ajaxLoading(0); },
    });
  }

  /**
   * @returns {void}
   */
  function _resetModal() {
    _holidayIsCreateMode = false;
    _holidayIsDirty      = false;
    $('#holidayUID').val(0);
    $('#holidayName').val('').removeClass('is-invalid');
    if (fpDate) fpDate.clear(); else $('#holidayDate').val('');
    $('#holidayDate').removeClass('is-invalid');
    $('#holidayIsOptional').prop('checked', false);
    $('#holidayDesc').val('');
  }

  // Flatpickr init
  var fpDate = null;
  if (typeof flatpickr !== 'undefined') {
    fpDate = flatpickr('#holidayDate', {
      dateFormat: 'Y-m-d',
      altInput:   true,
      altFormat:  (typeof _transFormDateFormat !== 'undefined') ? _transFormDateFormat : 'd-m-Y',
      allowInput: false,
      static:     true,
      position:   'below left',
    });
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
    if (fpDate) fpDate.setDate($(this).data('date'), false); else $('#holidayDate').val($(this).data('date'));
    $('#holidayDate').removeClass('is-invalid');
    $('#holidayIsOptional').prop('checked', $(this).data('optional') == 1);
    $('#holidayDesc').val($(this).data('desc'));
    $('#holidayModalTitle').text('Edit Holiday');
    $('#holidayModal').modal('show');
  });

  $(document).on('input', '#holidayName',   function () { $(this).removeClass('is-invalid'); });
  $(document).on('change', '#holidayDate',  function () { $(this).removeClass('is-invalid'); });

  // Save
  $(document).on('click', '#btnSaveHoliday', function () {
    var name  = $.trim($('#holidayName').val());
    var date  = $.trim($('#holidayDate').val());
    var valid = true;
    if (!name) { $('#holidayName').addClass('is-invalid'); valid = false; }
    if (!date) { $('#holidayDate').addClass('is-invalid'); valid = false; }
    if (!valid) return;

    var payload = {
      orgId:       parseInt(JwtData.Org.OrgUID, 10),
      userId:      parseInt(JwtData.User.UserUID, 10),
      HolidayUID:  parseInt($('#holidayUID').val() || 0, 10),
      HolidayName: name,
      HolidayDate: date,
      IsOptional:  $('#holidayIsOptional').is(':checked') ? 1 : 0,
      Description: $.trim($('#holidayDesc').val()),
    };

    var $btn     = $(this).prop('disabled', true);
    var $spinner = $('<span class="spinner-border spinner-border-sm me-1" role="status"></span>');
    $btn.prepend($spinner);

    $.ajax({
      url:         global_services_url + 'api/holidays/save',
      method:      'POST',
      headers:     { Authorization: 'Bearer ' + JwtToken },
      contentType: 'application/json',
      data:        JSON.stringify(payload),
      dataType:    'json',
      success: function (r) {
        $spinner.remove();
        $btn.prop('disabled', false);
        if (!r.Error) {
          _holidayIsDirty      = false;
          _holidayIsCreateMode = false;
          $('#holidayModal').modal('hide');
          loadPage(currentPage, buildFilter());
          showToastNotification(r.Message || 'Saved.', 'success');
        } else {
          showToastNotification(r.Message || 'Error saving.', 'error');
        }
      },
      error: function () {
        $spinner.remove();
        $btn.prop('disabled', false);
        showToastNotification('Request failed. Please try again.', 'error');
      },
    });
  });

  // Delete
  $(document).on('click', '.holiday-delete-btn', function () {
    if (!confirm('Delete this holiday?')) return;
    var payload = {
      orgId:      parseInt(JwtData.Org.OrgUID, 10),
      userId:     parseInt(JwtData.User.UserUID, 10),
      HolidayUID: parseInt($(this).data('uid') || 0, 10),
    };
    $.ajax({
      url:         global_services_url + 'api/holidays/delete',
      method:      'POST',
      headers:     { Authorization: 'Bearer ' + JwtToken },
      contentType: 'application/json',
      data:        JSON.stringify(payload),
      dataType:    'json',
      success: function (r) {
        if (!r.Error) { loadPage(currentPage, buildFilter()); showToastNotification('Deleted.', 'success'); }
        else showToastNotification(r.Message || 'Error deleting.', 'error');
      },
      error: function () { showToastNotification('Request failed. Please try again.', 'error'); },
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

  // ── Dirty-tracking ────────────────────────────────────────────────────────
  $(document).on('input change', '#holidayName, #holidayDate, #holidayIsOptional, #holidayDesc', function () {
    if (_holidayIsCreateMode) _holidayIsDirty = true;
  });

  // ── Unsaved-changes guard ─────────────────────────────────────────────────
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

  $(function () { loadPage(1); });

})();
