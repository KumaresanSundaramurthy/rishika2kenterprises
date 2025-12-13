function basePaginationFunc(ModulePag, callbackFn) {
    $(ModulePag).on('click', 'a', function(e) {
        e.preventDefault();
        PageNo = $(this).attr('data-ci-pagination-page');
        callbackFn(PageNo, RowLimit, Filter);
    });
}

function baseRefreshPageFunc(RefreshBtn, callbackFn) {
    $(RefreshBtn).click(function(e) {
        e.preventDefault();
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
            $headerCheckbox.prop('checked', false);
            unSelectTableRecords(TableId, TableRow);
        } else {
            $headerCheckbox.prop('checked', true);
            selectTableRecords(TableId, TableRow);
        }
    }
    MultipleDeleteOption();
}

function unSelectTableRecords(TableName, FieldName) {
    $(TableName + ' tbody ' + FieldName).each(function () {
        const val = parseInt($(this).val());
        $(this).prop('checked', false);
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
    let tabRowCount = $(TableId+' '+TableRow).map(function() {
        return parseInt($(this).val(), 10);
    }).get().length;
    if(SelectedUIDs.length === tabRowCount) {
        $(HeaderField).prop('checked', true);
    } else {
        $(HeaderField).prop('checked', false);
    }
}

function executeTablePagnCommonFunc(response, tableinfo = false) {

    if(tableinfo) {
        $(ModulePag).html(response.Pagination);
        $(ModuleTable + ' tbody').html(response.List);
        Swal.fire(response.Message, "", "success");
    }

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

function baseExportFunctions() {
    $('#btnExportPrint').click(function(e) {
        e.preventDefault();
        baseExportFunctionality(1, 'PrintPreview', 'ExportAll', ModuleFileName, ModuleSheetName);
    });

    $('#btnExportCSV').click(function(e) {
        e.preventDefault();
        baseExportFunctionality(1, 'ExportCSV', 'ExportAll', ModuleFileName, ModuleSheetName);
    });

    $('#btnExportPDF').click(function(e) {
        e.preventDefault();
        baseExportFunctionality(1, 'ExportPDF', 'ExportAll', ModuleFileName, ModuleSheetName);
    });

    $('#btnExportExcel').click(function(e) {
        e.preventDefault();
        baseExportFunctionality(1, 'ExportExcel', 'ExportAll', ModuleFileName, ModuleSheetName);
    });

    $('#exportSelectedItemsBtn').click(function(e) {
        e.preventDefault();
        baseExportFunctionality(2, expActionType, 'SelectedPage', ModuleFileName, ModuleSheetName);
    });

    $('#exportThisPageBtn').click(function(e) {
        e.preventDefault();
        baseExportFunctionality(2, expActionType, 'CurrentPage', ModuleFileName, ModuleSheetName);
    });

    $('#exportAllPagesBtn').click(function(e) {
        e.preventDefault();
        baseExportFunctionality(2, expActionType, 'AllPage', ModuleFileName, ModuleSheetName);
    });

    $('#clearExportClose').click(function(e) {
        e.preventDefault();
        exportModalCloseFunc(ModuleTable, ModuleHeader, ModuleRow, ModuleUIDs);
    });

    $('#selectThisPageBtn').click(function(e) {
        e.preventDefault();
        baseSelectFunctionality('CurrentPage');
    });

    $('#selectAllPagesBtn').click(function(e) {
        e.preventDefault();
        baseSelectFunctionality('AllPage');
    });

    $('#clearSelectAllClose').click(function(e) {
        e.preventDefault();
        selectModalCloseFunc(ModuleTable, ModuleHeader, ModuleRow, ModuleUIDs);
    });

    $('#unselectThisPageBtn').click(function(e) {
        e.preventDefault();
        baseUnSelectFunctionality('CurrentPage');
    });

    $('#unselectAllPagesBtn').click(function(e) {
        e.preventDefault();
        baseUnSelectFunctionality('AllPage');
    });
}

function baseExportFunctionality(Flag, Type, PageType, FileName, SheetName) {
    if (Flag == 2) {
        if (SelectedUIDs.length == 0) {
            Swal.fire({
                icon: "error",
                title: "Oops...",
                text: "You have not selected any items. Kindly select items.!",
            });
            return false;
        }
    }
    let ExportIds = '';
    if(PageType == 'SelectedPage') {
        ExportIds = SelectedUIDs.length > 0 ? btoa(SelectedUIDs.toString()) : '';
    } else if(PageType == 'CurrentPage') {
        let CurrentPageIds = [];
        $(ModuleTable + ' tbody ' + ModuleRow).each(function () {
            let currentVal = parseInt($(this).val());
            if (!CurrentPageIds.includes(currentVal)) {
                CurrentPageIds.push(currentVal);
            }
        });
        ExportIds = CurrentPageIds.length > 0 ? btoa(CurrentPageIds.toString()) : '';
    }
    let URLs = '';
    if (Type == 'PrintPreview') {
        URLs = "/globally/getPrintPreviewDetails?ModuleId=" + ModuleId+ "&previewName="+previewName;
        if (!$.isEmptyObject(Filter)) {
            URLs += "&Filter=" + encodeURIComponent(JSON.stringify(Filter));
        }
        if (ExportIds != '') {
            URLs += "&ExportIds=" + ExportIds;
        }
    } else if (Type == 'ExportCSV' || Type == 'ExportPDF' || Type == 'ExportExcel') {
        const TypeVal = (Type == 'ExportCSV') ? 'CSV' : ((Type == 'ExportPDF') ? 'Pdf' : ((Type == 'ExportExcel') ? 'Excel' : 'None'));
        URLs = "/globally/exportModuleDataDetails?ModuleId=" + ModuleId + "&Type=" + TypeVal + "&FileName=" + FileName + "&SheetName=" + SheetName;
        if (!$.isEmptyObject(Filter)) {
            URLs += "&Filter=" + encodeURIComponent(JSON.stringify(Filter));
        }
        if (ExportIds != '') {
            URLs += "&ExportIds=" + ExportIds;
        }
    }
    if (Flag == 1) {
        exportAllActions(ModuleId, Type, URLs, function () {
            exportModalCloseFunc(ModuleTable, ModuleHeader, ModuleRow, ModuleUIDs);
        });
    } else if (Flag == 2) {
        if (Type == 'PrintPreview') {
            printPreviewRecords(URLs, function () {
                exportModalCloseFunc(ModuleTable, ModuleHeader, ModuleRow);
            });
        } else if (Type == 'ExportCSV' || 'ExportPDF' || 'ExportExcel') {
            window.location.href = URLs;
            exportModalCloseFunc(ModuleTable, ModuleHeader, ModuleRow, ModuleUIDs);
        }
    }
}

function exportAllActions(ModuleId, ActionType, URLs, callbackFn) {
    $('#exportSelectedItemsBtn,#exportThisPageBtn,#exportThisPageCnt,#exportSelectedItemsCnt').addClass('d-none');
    exportModule = ModuleId;
    expActionType = ActionType;
    if (SelectedUIDs.length > 0) {
        $('#exportSelectedItemsBtn,#exportSelectedItemsCnt').removeClass('d-none');
        if (ItemIds.length > RowLimit) {
            $('#exportThisPageBtn,#exportThisPageCnt').removeClass('d-none');
        }
        $('#exportPagesModal').modal('show');
    } else {
        if (ActionType == 'PrintPreview') {
            printPreviewRecords(URLs, () => {
                if (typeof callbackFn === 'function') {
                    callbackFn();
                }
            });
        } else if (ActionType == 'ExportCSV' || ActionType == 'ExportPDF' || ActionType == 'ExportExcel') {
            window.location.href = URLs;
            if (typeof callbackFn === 'function') {
                callbackFn();  // or pass arguments to it if needed
            }
        }
    }
}

function selectModalCloseFunc(TableName, HeaderCheckbox, RowCheckbox, ItemIds) {
    SelectedUIDs = [];
    unSelectTableRecords(TableName, RowCheckbox);
    headerCheckboxTrueFalse(ItemIds, HeaderCheckbox);
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