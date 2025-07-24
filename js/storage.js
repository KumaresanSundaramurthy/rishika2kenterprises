/**
 * @param {*} PageNo
 * @param {*} RowLimit
 * @param {*} Filter
 */
function getStorageDetails(PageNo, RowLimit, Filter) {
    $.ajax({
        url: '/storage/getStorageDetails/' + PageNo,
        method: "POST",
        cache: false,
        data: {
            RowLimit: RowLimit,
            PageNo: PageNo,
            Filter: Filter,
            ModuleId: ModuleId
        },
        success: function (response) {
            if (response.Error) {
                $(ModuleTable + ' tbody').html('');
                $(ModulePag).html('<div class="alert alert-danger" role="alert"><strong>' + response.Message + '</strong></div>');
            } else {
                $(ModulePag).html(response.Pagination);
                $(ModuleTable + ' tbody').html(response.List);
            }
            executeCommonFunc(response, false);
        },
    });
}

function addStorageData(formdata) {
    AjaxLoading = 0;
    
    $('#storageFormAlert').removeClass('d-none');
    inlineMessageAlert('#storageFormAlert', 'info', 'Processing... Please wait', false, false);

    $('#StorageSaveButton').attr('disabled', 'disabled');

    $.ajax({
        url: '/storage/addStorageData',
        method: 'POST',
        data: formdata,
        cache: false,
        processData: false,
        contentType: false,
        enctype: 'multipart/form-data',
        success: function (response) {
            AjaxLoading = 1;
            $('#StorageSaveButton').removeAttr('disabled');
            if (response.Error) {
                inlineMessageAlert('#storageFormAlert', 'danger', response.Message, false, false);
            } else {
                inlineMessageAlert('#storageFormAlert', 'success', response.Message, false, true);
                setTimeout(function () {
                    $('#storageFormAlert').fadeOut(500, function () {
                        $(this).addClass('d-none').show();
                    });
                }, 1000);
                myOneDropzone.removeAllFiles(true);

                $('#storageForm').trigger('reset');
                $('#storageModal').modal('hide');

                executeCommonFunc(response, true);

            }
        }
    });
}

function updateStorageData(formdata) {
    AjaxLoading = 0;
    
    $('#storageFormAlert').removeClass('d-none');
    inlineMessageAlert('#storageFormAlert', 'info', 'Processing... Please wait', false, false);

    $('#StorageSaveButton').attr('disabled', 'disabled');

    $.ajax({
        url: '/storage/updateStorageData',
        method: 'POST',
        data: formdata,
        cache: false,
        processData: false,
        contentType: false,
        enctype: 'multipart/form-data',
        success: function (response) {
            AjaxLoading = 1;
            $('#StorageSaveButton').removeAttr('disabled');
            if (response.Error) {
                inlineMessageAlert('#storageFormAlert', 'danger', response.Message, false, false);
            } else {
                inlineMessageAlert('#storageFormAlert', 'success', response.Message, false, true);
                setTimeout(function () {
                    $('#storageFormAlert').fadeOut(500, function () {
                        $(this).addClass('d-none').show();
                    });
                }, 1000);
                myOneDropzone.removeAllFiles(true);

                $('#storageForm').trigger('reset');
                $('#storageModal').modal('hide');

                executeCommonFunc(response, true);

            }
        }
    });
}

function deleteStorage(StorageUID) {
    $.ajax({
        url: '/storage/deleteStorageDetails',
        method: "POST",
        cache: false,
        data: {
            RowLimit: RowLimit,
            PageNo: PageNo,
            Filter: Filter,
            StorageUID: StorageUID,
            ModuleId: ModuleId
        },
        success: function (response) {
            if (response.Error) {
                Swal.fire(response.Message, "", "error");
            } else {
                if (SelectedUIDs.length > 0) {
                    SelectedUIDs = SelectedUIDs.filter(function (item) {
                        return item !== StorageUID;
                    });
                }
                executeCommonFunc(response, true);
            }
        },
    });
}

function deleteMultipleStorage() {
    $.ajax({
        url: '/storage/deleteBulkStorage',
        method: "POST",
        cache: false,
        data: {
            RowLimit: RowLimit,
            PageNo: PageNo,
            Filter: Filter,
            StorageUIDs: SelectedUIDs,
            ModuleId: ModuleId
        },
        success: function (response) {
            if (response.Error) {
                Swal.fire(response.Message, "", "error");
            } else {
                SelectedUIDs = [];
                executeCommonFunc(response, true);
            }
        },
    });
}

function executeCommonFunc(response, tableinfo = false) {

    if(tableinfo) {
        $(ModulePag).html(response.Pagination);
        $(ModuleTable + ' tbody').html(response.List);
        Swal.fire(response.Message, "", "success");
    }

    ModuleUIDs = response.UIDs ? response.UIDs : [];
    headerCheckboxTrueFalse(ModuleUIDs, ModuleHeader);
    tableCheckboxTrueFalse(SelectedUIDs, ModuleTable, ModuleRow);
    MultipleDeleteOption();

}