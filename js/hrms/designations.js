'use strict';
(function () {

  var currentPage = 1;
  var filterData  = {};

  function loadPage(page, filter) {
    currentPage = page || 1;
    filterData  = filter || filterData;
    $.post('/designations/getPageDetails/' + currentPage, { Filter: filterData }, function (r) {
      if (!r.Error) {
        $('#DesigTableBody').html(r.RecordHtmlData);
        $('#DesignationsPagination').html(r.Pagination);
      }
    });
  }

  $(document).on('click', '#btnNewDesig', function () {
    $('#desigUID').val(0);
    $('#desigName').val('');
    $('#desigDesc').val('');
    $('#desigModalTitle').text('New Designation');
    $('#desigModal').modal('show');
  });

  $(document).on('click', '.desig-edit-btn', function () {
    $('#desigUID').val($(this).data('uid'));
    $('#desigName').val($(this).data('name'));
    $('#desigDesc').val($(this).data('desc'));
    $('#desigModalTitle').text('Edit Designation');
    $('#desigModal').modal('show');
  });

  $(document).on('click', '#btnSaveDesig', function () {
    var name = $.trim($('#desigName').val());
    if (!name) { toastr.warning('Designation name is required.'); return; }
    var payload = {
      DesignationUID:  $('#desigUID').val(),
      DesignationName: name,
      Description:     $.trim($('#desigDesc').val())
    };
    $(this).prop('disabled', true);
    var self = this;
    $.post('/designations/save', payload, function (r) {
      $(self).prop('disabled', false);
      if (!r.Error) {
        toastr.success(r.Message || 'Saved.');
        $('#desigModal').modal('hide');
        loadPage(currentPage);
      } else {
        toastr.error(r.Message || 'Error saving.');
      }
    });
  });

  $(document).on('click', '.desig-delete-btn', function () {
    if (!confirm('Delete this designation?')) return;
    $.post('/designations/delete', { DesignationUID: $(this).data('uid') }, function (r) {
      if (!r.Error) { toastr.success('Deleted.'); loadPage(currentPage); }
      else toastr.error(r.Message || 'Cannot delete — it may be in use.');
    });
  });

  var searchTimer;
  $(document).on('input', '#SearchDetails', function () {
    clearTimeout(searchTimer);
    var q = $.trim($(this).val());
    $('#clearSearch').toggleClass('d-none', !q);
    searchTimer = setTimeout(function () { loadPage(1, { SearchAllData: q }); }, 350);
  });
  $(document).on('click', '#clearSearch', function () { $('#SearchDetails').val(''); $(this).addClass('d-none'); loadPage(1, {}); });
  $(document).on('click', '.PageRefresh', function (e) { e.preventDefault(); loadPage(currentPage); });
  $(document).on('click', '.page-link', function (e) { e.preventDefault(); var pg = $(this).data('page'); if (pg) loadPage(pg); });

})();
