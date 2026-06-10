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

  // New / open modal
  $(document).on('click', '#btnNewDept', function () {
    $('#deptUID').val(0);
    $('#deptName').val('');
    $('#deptDesc').val('');
    $('#deptModalTitle').text('New Department');
    $('#deptModal').modal('show');
  });

  // Edit
  $(document).on('click', '.dept-edit-btn', function () {
    var uid  = $(this).data('uid');
    var name = $(this).data('name');
    var desc = $(this).data('desc');
    $('#deptUID').val(uid);
    $('#deptName').val(name);
    $('#deptDesc').val(desc);
    $('#deptModalTitle').text('Edit Department');
    $('#deptModal').modal('show');
  });

  // Save
  $(document).on('click', '#btnSaveDept', function () {
    var name = $.trim($('#deptName').val());
    if (!name) { toastr.warning('Department name is required.'); return; }
    var payload = {
      DepartmentUID:  $('#deptUID').val(),
      DepartmentName: name,
      Description:    $.trim($('#deptDesc').val())
    };
    $(this).prop('disabled', true);
    var self = this;
    $.post('/departments/save', payload, function (r) {
      $(self).prop('disabled', false);
      if (!r.Error) {
        toastr.success(r.Message || 'Saved.');
        $('#deptModal').modal('hide');
        loadPage(currentPage);
      } else {
        toastr.error(r.Message || 'Error saving.');
      }
    });
  });

  // Delete
  $(document).on('click', '.dept-delete-btn', function () {
    var uid = $(this).data('uid');
    if (!confirm('Delete this department?')) return;
    $.post('/departments/delete', { DepartmentUID: uid }, function (r) {
      if (!r.Error) { toastr.success('Deleted.'); loadPage(currentPage); }
      else toastr.error(r.Message || 'Cannot delete — it may be in use.');
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

  // Refresh
  $(document).on('click', '.PageRefresh', function (e) { e.preventDefault(); loadPage(currentPage); });

  // Pagination
  $(document).on('click', '.page-link', function (e) {
    e.preventDefault();
    var pg = $(this).data('page');
    if (pg) loadPage(pg);
  });

})();
