/**
 * @param {*} PageNo
 * @param {*} RowLimit
 * @param {*} Filter
 */
function getStorageDetails(PageNo, RowLimit, Filter) {
    $.ajax({
        url: '/globally/getModPageDataDetails/' + PageNo,
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
            executeTablePagnCommonFunc(response, false);
        },
    });
}

function formOpenCloseDefActions() {
    $('#storageForm').trigger('reset');
    $('#StorageModalTitle').text('Add Storage');
    $('.storageButtonName').text('Save');
    $('#storageForm').find('#StorageUID').val(0);
    myOneDropzone.removeAllFiles(true);
    quill.setContents([]);
    imgData = '';
}

function addStorageData(formdata) {
    $.ajax({
        url: '/storage/addStorageData',
        method: 'POST',
        data: formdata,
        cache: false,
        processData: false,
        contentType: false,
        enctype: 'multipart/form-data',
        success: function (response) {
            if (response.Error) {
                $('.storageFormAlert').removeClass('d-none');
                inlineMessageAlert('.storageFormAlert', 'danger', response.Message, false, false);
            } else {
                formOpenCloseDefActions();
                $('#storageModal').modal('hide');
                executeTablePagnCommonFunc(response, true);
            }
        }
    });
}

function updateStorageData(formdata) {
    $.ajax({
        url: '/storage/updateStorageData',
        method: 'POST',
        data: formdata,
        cache: false,
        processData: false,
        contentType: false,
        enctype: 'multipart/form-data',
        success: function (response) {
            if (response.Error) {
                $('.storageFormAlert').removeClass('d-none');
                inlineMessageAlert('.storageFormAlert', 'danger', response.Message, false, false);
            } else {
                formOpenCloseDefActions();
                $('#storageModal').modal('hide');
                executeTablePagnCommonFunc(response, true);
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
                executeTablePagnCommonFunc(response, true);
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
                executeTablePagnCommonFunc(response, true);
            }
        },
    });
}

function resetStorageTypeFilter() {
    $('.storagetype-checkbox').prop('checked', false);
    if (Filter.StorageType) {
        applyStorageTypeFilter();
    }
}

function applyStorageTypeFilter() {
    PageNo = 0;
    delete Filter['StorageType'];
    let selStrgTypeIds = $('.storagetype-checkbox:checked').map(function () {
        return $(this).val();
    }).get();
    $('#storageTypeFilter').removeClass('text-primary');
    if (selStrgTypeIds.length > 0) {
        Filter['StorageType'] = selStrgTypeIds;
        $('#storageTypeFilter').addClass('text-primary');
    }
    $('#storageTypeFilterBox').hide();
    getStorageDetails(PageNo, RowLimit, Filter);
}

function closeStorageTypeFilter() {
    $('#storageTypeFilterBox').hide();
}