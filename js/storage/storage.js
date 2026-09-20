/**
 * @param {number} PageNo
 * @param {number} RowLimit
 * @param {object} Filter
 * @returns {void}
 */
function getStorageDetails(PageNo, RowLimit, Filter) {
    $.ajax({
        url: global_base_url + 'storage/getStorageList/' + (PageNo || 1),
        method: 'POST',
        cache: false,
        data: {
            RowLimit: RowLimit,
            Filter: Filter,
        },
        success: function(response) {
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

var _storageIsDirty      = false;
var _storageIsCreateMode = false;

/**
 * @returns {void}
 */
function formOpenCloseDefActions() {
    _storageIsCreateMode = false;
    _storageIsDirty      = false;
    $('#storageForm').trigger('reset');
    $('#StorageModalTitle').text('Add Storage');
    $('.storageButtonName').text('Save');
    $('#storageForm').find('#StorageUID').val(0);
    myOneDropzone.removeAllFiles(true);
    quill.setContents([]);
    imgData = '';
}

/**
 * @param {FormData} formdata
 * @returns {void}
 */
function addStorageData(formdata) {
    $.ajax({
        url: global_base_url + 'storage/addStorageData',
        method: 'POST',
        data: formdata,
        cache: false,
        processData: false,
        contentType: false,
        enctype: 'multipart/form-data',
        success: function(response) {
            if (response.Error) {
                showToastNotification(response.Message || 'Failed to save storage.', 'error');
            } else {
                _storageIsDirty      = false;
                _storageIsCreateMode = false;
                formOpenCloseDefActions();
                $('#storageModal').modal('hide');
                executeTablePagnCommonFunc(response, true);
            }
        },
    });
}

/**
 * @param {FormData} formdata
 * @returns {void}
 */
function updateStorageData(formdata) {
    $.ajax({
        url: global_base_url + 'storage/updateStorageData',
        method: 'POST',
        data: formdata,
        cache: false,
        processData: false,
        contentType: false,
        enctype: 'multipart/form-data',
        success: function(response) {
            if (response.Error) {
                showToastNotification(response.Message || 'Failed to update storage.', 'error');
            } else {
                formOpenCloseDefActions();
                $('#storageModal').modal('hide');
                executeTablePagnCommonFunc(response, true);
            }
        },
    });
}

/**
 * @param {number} StorageUID
 * @returns {void}
 */
function deleteStorage(StorageUID) {
    $.ajax({
        url: global_base_url + 'storage/deleteStorageDetails',
        method: 'POST',
        cache: false,
        data: {
            RowLimit: RowLimit,
            PageNo: PageNo,
            Filter: Filter,
            StorageUID: StorageUID,
        },
        success: function(response) {
            if (response.Error) {
                Swal.fire(response.Message, '', 'error');
            } else {
                if (SelectedUIDs.length > 0) {
                    SelectedUIDs = SelectedUIDs.filter(function(item) {
                        return item !== StorageUID;
                    });
                }
                executeTablePagnCommonFunc(response, true);
            }
        },
    });
}

/**
 * @returns {void}
 */
function deleteMultipleStorage() {
    $.ajax({
        url: global_base_url + 'storage/deleteBulkStorage',
        method: 'POST',
        cache: false,
        data: {
            RowLimit: RowLimit,
            PageNo: PageNo,
            Filter: Filter,
            StorageUIDs: SelectedUIDs,
        },
        success: function(response) {
            if (response.Error) {
                Swal.fire(response.Message, '', 'error');
            } else {
                SelectedUIDs = [];
                executeTablePagnCommonFunc(response, true);
            }
        },
    });
}

/**
 * @returns {void}
 */
function resetStorageTypeFilter() {
    $('.storagetype-checkbox').prop('checked', false);
    if (Filter.StorageType) {
        applyStorageTypeFilter();
    }
}

/**
 * @returns {void}
 */
function applyStorageTypeFilter() {
    PageNo = 0;
    delete Filter['StorageType'];
    let selStrgTypeIds = $('.storagetype-checkbox:checked').map(function() {
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

/**
 * @returns {void}
 */
function closeStorageTypeFilter() {
    $('#storageTypeFilterBox').hide();
}

// ── Arm on open (add mode only) ───────────────────────────────────────────
$(document).on('shown.bs.modal', '#storageModal', function() {
    if (parseInt($('#StorageUID').val(), 10) === 0) {
        _storageIsCreateMode = true;
        _storageIsDirty      = false;
    }
});

// ── Dirty-tracking listener ───────────────────────────────────────────────
$(document).on('input change', '#storageForm input, #storageForm textarea, #storageForm select', function() {
    if (_storageIsCreateMode) _storageIsDirty = true;
});

// ── Unsaved-changes guard ─────────────────────────────────────────────────
$(document).on('hide.bs.modal', '#storageModal', function(e) {
    if (!_storageIsDirty || !_storageIsCreateMode) return;
    e.preventDefault();
    Swal.fire({
        title             : t('swal_unsaved_title',   'Unsaved Changes'),
        text              : t('swal_unsaved_msg',     'Your changes will be lost if you close now.'),
        icon              : 'warning',
        showCancelButton  : true,
        confirmButtonText : t('swal_unsaved_confirm', 'Close Anyway'),
        cancelButtonText  : t('swal_unsaved_cancel',  'Stay'),
        confirmButtonColor: '#d33',
        cancelButtonColor : '#3085d6',
    }).then(function(result) {
        if (result.isConfirmed) {
            _storageIsDirty      = false;
            _storageIsCreateMode = false;
            $('#storageModal').modal('hide');
        }
    });
});
