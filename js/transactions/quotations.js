function getQuotationsDetails(PageNo, RowLimit, Filter) {
    $.ajax({
        url: '/quotations/getQuotationsPageDetails/' + PageNo,
        method: "POST",
        cache: false,
        data: {
            RowLimit: RowLimit,
            PageNo: PageNo,
            Filter: Filter,
            ModuleId: ModuleId
        },
        success: function(response) {
            if (response.Error) {
                $(ModuleTable + ' tbody').html('');
                $(ModulePag).html('<div class="alert alert-danger" role="alert"><strong>' + response.Message + '</strong></div>');
            } else {
                $(ModulePag).html(response.pagination);
                $(ModuleTable + ' tbody').html(response.dataList);
            }
        },
    });
}