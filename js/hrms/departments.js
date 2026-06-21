'use strict';
(function () {

  var currentPage = 1;
  var filterData  = {};

  function loadPage(page, filter) {
    currentPage = page || 1;
    filterData  = filter || filterData;
    $.post('/departments/getPageDetails/' + currentPage, { Filter: filterData }, function (r) {
      if (!r.Error) {
        $('#DeptTableBody').html(r.RecordHtmlData);
        $('#DepartmentsPagination').html(r.Pagination);
      }
    });
  }

  function _resetModal() {
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
  });

  // Edit
  $(document).on('click', '.dept-edit-btn', function () {
    var isSystem = parseInt($(this).data('is-system') || 0);
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

  // Clear validation on input
  $(document).on('input', '#deptName', function () { $(this).removeClass('is-invalid'); });

  // Save
  $(document).on('click', '#btnSaveDept', function () {
    var isSystem = parseInt($('#deptIsSystem').val() || 0);
    var name     = $.trim($('#deptName').val());
    if (!isSystem && !name) { $('#deptName').addClass('is-invalid').focus(); return; }
    $('#deptName').removeClass('is-invalid');

    var payload = {
      DepartmentUID:  $('#deptUID').val(),
      DepartmentName: name,
      Description:    $.trim($('#deptDesc').val()),
      IsSystem:       isSystem,
      CurrentPage:    currentPage,
      Filter:         filterData
    };

    var $btn     = $(this).prop('disabled', true);
    var $spinner = $('<span class="spinner-border spinner-border-sm me-1" role="status"></span>');
    $btn.prepend($spinner);

    $.post('/departments/save', payload, function (r) {
      $spinner.remove();
      $btn.prop('disabled', false);
      if (!r.Error) {
        if (r.RecordHtmlData !== undefined) {
          $('#DeptTableBody').html(r.RecordHtmlData);
          $('#DepartmentsPagination').html(r.Pagination);
        }
        $('#deptModal').modal('hide');
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
    var uid = $(this).data('uid');
    if (!confirm('Delete this department?')) return;
    $.post('/departments/delete', { DepartmentUID: uid }, function (r) {
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

})();
