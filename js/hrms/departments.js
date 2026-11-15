/* global $, global_base_url, global_services_url, JwtData, RowLimit, ajaxLoading, CsrfName, CsrfToken, showToastNotification, t, Swal */
'use strict';
(function () {

  var currentPage       = 1;
  var filterData        = {};
  var _deptIsDirty      = false;
  var _deptIsCreateMode = false;

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
      return '<tr><td colspan="5" class="text-center text-muted py-4"><i class="bx bx-buildings me-1"></i>No departments found.</td></tr>';
    }
    var snoClass = (JwtData && JwtData.GenSettings && parseInt(JwtData.GenSettings.SerialNoDisplay || 0, 10) === 1) ? '' : 'd-none';
    var html = '';
    $.each(rows, function (i, row) {
      var uid      = row.TablePrimaryUID;
      var isSystem = parseInt(row.IsSystem || 0, 10);
      html += '<tr>'
        + '<td class="' + snoClass + '">' + (offset + i + 1) + '</td>'
        + '<td class="fw-semibold">' + _esc(row.DepartmentName || '—') + '</td>'
        + '<td class="text-muted">' + _esc(row.Description || '—') + '</td>'
        + '<td><span class="badge bg-label-' + (isSystem ? 'info' : 'secondary') + '">' + (isSystem ? 'System' : 'Custom') + '</span></td>'
        + '<td><div class="d-flex align-items-center gap-1">'
        + '<button class="btn btn-icon btn-sm text-primary dept-edit-btn"'
        + ' data-uid="' + uid + '"'
        + ' data-is-system="' + isSystem + '"'
        + ' data-name="' + _esc(row.DepartmentName || '') + '"'
        + ' data-desc="' + _esc(row.Description || '') + '"'
        + ' title="' + (isSystem ? 'Edit Description' : 'Edit') + '"><i class="bx bx-edit"></i></button>'
        + (isSystem ? '' : '<button class="btn btn-icon btn-sm text-danger dept-delete-btn" data-uid="' + uid + '" title="Delete"><i class="bx bx-trash"></i></button>')
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
    if (filter !== undefined) filterData = filter;
    ajaxLoading(1);
    $.ajax({
      url:      global_services_url + 'api/departments/getPageDetails/' + currentPage,
      method:   'GET',
      data:     { orgId: parseInt(JwtData.Org.OrgUID, 10), RowLimit: RowLimit, Filter: JSON.stringify(filterData) },
      dataType: 'json',
      success:  function (r) {
        if (r.Error) { showToastNotification(r.Message || 'Failed to load departments.', 'error'); return; }
        var rows   = (r.Data && r.Data.Data)       ? r.Data.Data                     : [];
        var total  = (r.Data && r.Data.TotalCount) ? parseInt(r.Data.TotalCount, 10) : 0;
        var offset = (currentPage - 1) * RowLimit;
        $('#DeptTableBody').html(_renderRows(rows, offset));
        $('#DepartmentsPagination').html(_buildPagination(total, currentPage, RowLimit));
      },
      error:    function () { showToastNotification('Failed to load departments.', 'error'); },
      complete: function () { ajaxLoading(0); },
    });
  }

  /**
   * @returns {void}
   */
  function _resetModal() {
    _deptIsCreateMode = false;
    _deptIsDirty      = false;
    $('#deptUID').val(0);
    $('#deptIsSystem').val(0);
    $('#deptName').val('').removeClass('is-invalid').prop('readonly', false).removeClass('bg-light');
    $('#deptDesc').val('');
  }

  // New
  $(document).on('click', '#btnNewDept', function () {
    _resetModal();
    $('#deptModalTitle').text('New Department');
    $('#deptModal').modal('show');
    _deptIsCreateMode = true;
    _deptIsDirty      = false;
  });

  // Edit
  $(document).on('click', '.dept-edit-btn', function () {
    var isSystem = parseInt($(this).data('is-system') || 0, 10);
    _resetModal();
    $('#deptUID').val($(this).data('uid'));
    $('#deptIsSystem').val(isSystem);
    $('#deptName').val($(this).data('name'));
    $('#deptDesc').val($(this).data('desc'));
    if (isSystem) {
      $('#deptName').prop('readonly', true).addClass('bg-light');
      $('#deptModalTitle').text('Edit Description');
    } else {
      $('#deptModalTitle').text('Edit Department');
    }
    $('#deptModal').modal('show');
  });

  $(document).on('input', '#deptName', function () { $(this).removeClass('is-invalid'); });

  // Save
  $(document).on('click', '#btnSaveDept', function () {
    var isSystem = parseInt($('#deptIsSystem').val() || 0, 10);
    var name     = $.trim($('#deptName').val());
    if (!isSystem && !name) { $('#deptName').addClass('is-invalid').focus(); return; }
    $('#deptName').removeClass('is-invalid');

    var payload = {
      DepartmentUID:  $('#deptUID').val(),
      DepartmentName: name,
      Description:    $.trim($('#deptDesc').val()),
      IsSystem:       isSystem,
    };
    payload[CsrfName] = CsrfToken;

    var $btn     = $(this).prop('disabled', true);
    var $spinner = $('<span class="spinner-border spinner-border-sm me-1" role="status"></span>');
    $btn.prepend($spinner);

    $.post(global_base_url + 'departments/save', payload, function (r) {
      CsrfToken = r.NewCsrfToken || CsrfToken;
      $spinner.remove();
      $btn.prop('disabled', false);
      if (!r.Error) {
        _deptIsDirty      = false;
        _deptIsCreateMode = false;
        $('#deptModal').modal('hide');
        loadPage(currentPage);
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
  $(document).on('click', '.dept-delete-btn', function () {
    if (!confirm('Delete this department?')) return;
    var payload = { DepartmentUID: $(this).data('uid') };
    payload[CsrfName] = CsrfToken;
    $.post(global_base_url + 'departments/delete', payload, function (r) {
      CsrfToken = r.NewCsrfToken || CsrfToken;
      if (!r.Error) { showToastNotification('Deleted.', 'success'); loadPage(currentPage); }
      else showToastNotification(r.Message || 'Cannot delete — it may be in use.', 'error');
    });
  });

  // Search
  var searchTimer;
  $(document).on('input', '#SearchDetails', function () {
    clearTimeout(searchTimer);
    var q = $.trim($(this).val());
    $('#clearSearch').toggleClass('d-none', !q);
    searchTimer = setTimeout(function () { loadPage(1, { SearchAllData: q }); }, 350);
  });
  $(document).on('click', '#clearSearch', function () {
    $('#SearchDetails').val('');
    $(this).addClass('d-none');
    loadPage(1, {});
  });

  // Refresh & pagination
  $(document).on('click', '.PageRefresh', function (e) { e.preventDefault(); loadPage(currentPage); });
  $(document).on('click', '.page-link', function (e) {
    e.preventDefault();
    var pg = $(this).data('page');
    if (pg) loadPage(pg);
  });

  // ── Dirty-tracking ────────────────────────────────────────────────────────
  $(document).on('input change', '#deptName, #deptDesc', function () {
    if (_deptIsCreateMode) _deptIsDirty = true;
  });

  // ── Unsaved-changes guard ─────────────────────────────────────────────────
  $(document).on('hide.bs.modal', '#deptModal', function (e) {
    if (!_deptIsDirty || !_deptIsCreateMode) return;
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
        _deptIsDirty      = false;
        _deptIsCreateMode = false;
        $('#deptModal').modal('hide');
      }
    });
  });

  $(function () { loadPage(1); });

})();
