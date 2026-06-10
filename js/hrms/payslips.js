'use strict';
(function () {

  var currentPage = 1;
  var filterData  = {};

  function loadPage(page, filter) {
    currentPage = page || 1;
    filterData  = filter || filterData;
    $.post('/payslips/getPageDetails/' + currentPage, { Filter: filterData }, function (r) {
      if (!r.Error) {
        $('#PayslipTableBody').html(r.RecordHtmlData);
        $('#PayslipsPagination').html(r.Pagination);
      }
    });
  }

  // Filter button
  $(document).on('click', '#btnPsFilter', function () {
    filterData.EmployeeUID = $('#psEmpFilter').val();
    filterData.Month       = $('#psMonthFilter').val();
    filterData.Year        = $('#psYearFilter').val();
    loadPage(1, filterData);
  });

  // Search
  var searchTimer;
  $(document).on('input', '#SearchDetails', function () {
    clearTimeout(searchTimer);
    filterData.SearchAllData = $.trim($(this).val());
    $('#clearSearch').toggleClass('d-none', !filterData.SearchAllData);
    searchTimer = setTimeout(function () { loadPage(1, filterData); }, 350);
  });
  $(document).on('click', '#clearSearch', function () {
    $('#SearchDetails').val('');
    $(this).addClass('d-none');
    delete filterData.SearchAllData;
    loadPage(1, filterData);
  });

  $(document).on('click', '.PageRefresh', function (e) { e.preventDefault(); loadPage(currentPage); });
  $(document).on('click', '.page-link',   function (e) { e.preventDefault(); var pg = $(this).data('page'); if (pg) loadPage(pg); });

})();
