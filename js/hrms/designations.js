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

  function _resetModal() {
    $('#desigUID').val(0);
    $('#desigName').val('').removeClass('is-invalid');
    $('#desigDesc').val('');
  }

  // New
  $(document).on('click', '#btnNewDesig', function () {
    _resetModal();
    $('#desigModalTitle').text('New Designation');
    $('#desigModal').modal('show');
  });

  // Edit
  $(document).on('click', '.desig-edit-btn', function () {
    _resetModal();
    $('#desigUID').val($(this).data('uid'));
    $('#desigName').val($(this).data('name'));
    $('#desigDesc').val($(this).data('desc'));
    $('#desigModalTitle').text('Edit Designation');
    $('#desigModal').modal('show');
  });

  // Clear validation on input
  $(document).on('input', '#desigName', function () { $(this).removeClass('is-invalid'); });

  // Save
  $(document).on('click', '#btnSaveDesig', function () {
    var name = $.trim($('#desigName').val());
    if (!name) { $('#desigName').addClass('is-invalid').focus(); return; }
    $('#desigName').removeClass('is-invalid');

    var payload = {
      DesignationUID:  $('#desigUID').val(),
      DesignationName: name,
      Description:     $.trim($('#desigDesc').val())
    };

    var $btn     = $(this).prop('disabled', true);
    var $spinner = $('<span class="spinner-border spinner-border-sm me-1" role="status"></span>');
    $btn.prepend($spinner);

    $.post('/designations/save', payload, function (r) {
      $spinner.remove();
      $btn.prop('disabled', false);
      if (!r.Error) {
        showToastNotification(r.Message || 'Saved.', 'success');
        $('#desigModal').modal('hide');
        loadPage(currentPage);
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
  $(document).on('click', '.desig-delete-btn', function () {
    if (!confirm('Delete this designation?')) return;
    $.post('/designations/delete', { DesignationUID: $(this).data('uid') }, function (r) {
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
