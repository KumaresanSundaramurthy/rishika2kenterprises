function basePaginationFunc(ModulePag, callbackFn) {
    $(ModulePag).on('click', 'a', function(e) {
        e.preventDefault();
        SelectedUIDs = [];
        PageNo = $(this).attr('data-ci-pagination-page');
        callbackFn(PageNo, RowLimit, Filter);
    });
}

function baseRefreshPageFunc(RefreshBtn, callbackFn) {
    $(RefreshBtn).click(function(e) {
        e.preventDefault();
        SelectedUIDs = [];
        callbackFn(PageNo, RowLimit, Filter);
    });
}

function basePageHeaderFunc(ModuleHeader, ModuleTable, ModuleRow) {
    $(ModuleHeader).click(function() {
        allTableHeadersCheckbox($(this), ModuleTable, ModuleRow);
    });
}

function allTableHeadersCheckbox(thisField, TableId, TableRow) {
    const $headerCheckbox = thisField;
    const isCurrentlyChecked = $headerCheckbox.prop('checked');
    let tabRowCount = $(TableId+' '+TableRow).map(function() {
        return parseInt($(this).val(), 10);
    }).get().length;
    if (tabRowCount > 0) {
        if (!isCurrentlyChecked) {
            $headerCheckbox.prop('checked', false).prop('indeterminate', false);
            unSelectTableRecords(TableId, TableRow);
        } else {
            $headerCheckbox.prop('checked', true).prop('indeterminate', false);
            selectTableRecords(TableId, TableRow);
        }
    }
    MultipleDeleteOption();
}

function unSelectTableRecords(TableName, FieldName) {
    $(TableName + ' tbody ' + FieldName).each(function () {
        const val = parseInt($(this).val());
        $(this).prop('checked', false).closest('tr').removeClass('row-sel');
        SelectedUIDs = SelectedUIDs.filter(function (item) {
            return item !== val;
        });
    });
}

function selectTableRecords(TableId, FieldName) {
    $(TableId + ' tbody ' + FieldName).each(function () {
        $(this).prop('checked', true);
        var fieldVal = parseInt($(this).val());
        if (!SelectedUIDs.includes(fieldVal)) {
            SelectedUIDs.push(fieldVal);
        }
    });
}

function MultipleDeleteOption() {
    $('#DeleteOption').addClass('d-none');
    if (SelectedUIDs.length > 0) {
        $('#DeleteOption').removeClass('d-none');
    }
}

function onClickOfCheckbox($this, TableId, HeaderField, TableRow) {
    const isChecked = $this.is(':checked');
    const value = parseInt($this.val());
    if (isChecked) {
        if (!SelectedUIDs.includes(value)) {
            SelectedUIDs.push(value);
        }
    } else {
        SelectedUIDs = SelectedUIDs.filter(function (item) {
            return item !== value;
        });
    }
    headerCheckboxTrueFalse(TableId, HeaderField, TableRow);
}

function headerCheckboxTrueFalse(TableId, HeaderField, TableRow) {
    var $rows        = $(TableId + ' tbody ' + TableRow);
    var total        = $rows.length;
    var checkedCount = $rows.filter(':checked').length;
    if (total > 0 && checkedCount === total) {
        $(HeaderField).prop('checked', true).prop('indeterminate', false);
    } else if (checkedCount > 0) {
        $(HeaderField).prop('checked', false).prop('indeterminate', true);
    } else {
        $(HeaderField).prop('checked', false).prop('indeterminate', false);
    }
}

function executeTablePagnCommonFunc(response, tableinfo = false) {
    if(tableinfo) {
        $(ModulePag).html(response.Pagination);
        $(ModuleTable + ' tbody').html(response.List);
    }
    $(window).trigger('scroll');
    headerCheckboxTrueFalse(ModuleTable, ModuleHeader, ModuleRow);
    MultipleDeleteOption();
}

function tableCheckboxTrueFalse(SelectedId, TableName, FieldName) {
    $(TableName + ' tbody ' + FieldName).each(function () {
        let currentVal = parseInt($(this).val());
        if (SelectedId.includes(currentVal)) {
            $(this).prop('checked', true);
        }
    });
}

function selectModalCloseFunc(TableName, HeaderCheckbox, RowCheckbox) {
    SelectedUIDs = [];
    unSelectTableRecords(TableName, RowCheckbox);
    headerCheckboxTrueFalse(TableName, HeaderCheckbox, RowCheckbox);
}

function exportModalCloseFunc(TableName, HeaderCheckbox, RowCheckbox) {
    SelectedUIDs = [];
    unSelectTableRecords(TableName, RowCheckbox);
    headerCheckboxTrueFalse(TableName, HeaderCheckbox, RowCheckbox);
    MultipleDeleteOption();
}

async function printPreviewRecords(getFuncName, callbackFn) {

    showUIBlock();

    const response = await fetch(getFuncName);
    const result = await response.json();

    hideUIBlock();

    if (result.Error === false) {

        const printWindow = window.open('', '_blank');
        printWindow.document.write(result.HtmlData);
        printWindow.document.close();
        printWindow.focus();
        printWindow.print();

        if (typeof callbackFn === 'function') {
            callbackFn();  // or pass arguments to it if needed
        }

    } else {
        Swal.fire(result.Message, "", "error");
    }

}

function exportRecords(UrlData, Filter = {}) {
    $.ajax({
        url: UrlData,
        method: 'POST',
        data: {
            Type: "CSV",
            Filter: Filter,
        },
        cache: false,
        success: function (response) {
            if (response.Error) {
                Swal.fire({
                    icon: "error",
                    title: "Oops...",
                    text: response.Message,
                });
            }
        }
    });
}