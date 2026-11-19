/* ── Services helpers ─────────────────────────────────────────────────────
   Every call goes straight to the services API (api/storage); the Authorization header is added by the
   global AJAX prefilter. The list rows and pagination are built here (formerly storage/list/storage_list.php). */

var _storageApi = global_services_url + 'api/storage/';

/**
 * @returns {{orgId: number, userId: number, langCode: string}}
 */
function _storageCtx() {
    return {
        orgId   : parseInt(JwtData.Org.OrgUID, 10),
        userId  : parseInt(JwtData.User.UserUID, 10),
        langCode: (JwtData.User && JwtData.User.UILanguage) || 'en',
    };
}

/**
 * @param {*} str
 * @returns {string}
 */
function _storageEsc(str) {
    return $('<div>').text(str == null ? '' : String(str)).html();
}

/**
 * PHP base64_encode of the UTF-8 text (the edit handler reads it back with atob)
 * @param {*} str
 * @returns {string}
 */
function _storageB64(str) {
    try { return btoa(unescape(encodeURIComponent(str == null ? '' : String(str)))); } catch (e) { return ''; }
}

/**
 * 'YYYY-MM-DD[ HH:MM:SS]' in the org's list date format (GenSettings.ListDateFormat)
 * @param {string} value
 * @returns {string}
 */
function _storageListDate(value) {
    var m = /^(\d{4})-(\d{2})-(\d{2})/.exec(value || '');
    if (!m) return '—';
    var months     = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    var monthsFull = ['January','February','March','April','May','June','July','August','September','October','November','December'];
    var yr = parseInt(m[1], 10), mo = parseInt(m[2], 10) - 1, dy = parseInt(m[3], 10);
    if (mo < 0 || mo > 11) return value;
    var fmt = (JwtData.GenSettings && JwtData.GenSettings.ListDateFormat) || 'd M Y';
    var tokens = {
        d: String(dy).padStart(2, '0'), j: String(dy), m: String(mo + 1).padStart(2, '0'), n: String(mo + 1),
        Y: String(yr), y: String(yr).slice(-2), F: monthsFull[mo], M: months[mo],
    };
    return fmt.replace(/[djmnYyFM]/g, function (tok) { return tokens[tok] !== undefined ? tokens[tok] : tok; });
}

/**
 * @param {Array<object>} rows
 * @param {number} startFrom
 * @returns {string}
 */
function _buildStorageRows(rows, startFrom) {
    if (!rows || rows.length === 0) {
        return '<tr><td colspan="8" class="text-center text-muted py-4">No storage records found.</td></tr>';
    }
    var showSerial = JwtData.GenSettings && parseInt(JwtData.GenSettings.SerialNoDisplay, 10) === 1;
    var cdn        = (typeof CDN_URL !== 'undefined' && CDN_URL) ? CDN_URL : '';
    return rows.map(function (r, i) {
        var uid   = parseInt(r.StorageUID, 10);
        var name  = _storageEsc(r.Name);
        var img   = r.Image || '';
        var thumb = img
            ? '<img src="' + _storageEsc(cdn + img) + '" alt="' + name + '" class="rounded" width="40" height="40" style="object-fit:cover;">'
            : '<span class="avatar avatar-sm bg-label-secondary rounded">' + _storageEsc(String(r.Name || '').charAt(0).toUpperCase()) + '</span>';
        return '<tr>'
            + '<td class="table-checkbox"><div class="form-check form-check-inline">'
            +   '<input class="form-check-input storageCheck" type="checkbox" value="' + uid + '"></div></td>'
            + '<td class="table-serialno ' + (showSerial ? '' : 'd-none') + '">' + (startFrom + i + 1) + '</td>'
            + '<td>' + thumb + '</td>'
            + '<td>' + name + '</td>'
            + '<td>' + (r.ShortName ? _storageEsc(r.ShortName) : '—') + '</td>'
            + '<td>' + _storageEsc(r.StorageTypeName || '—') + '</td>'
            + '<td class="r2k-col-date">' + _storageListDate(r.UpdatedOn) + '</td>'
            + '<td class="text-center"><div class="dropdown">'
            +   '<button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown"><i class="bx bx-dots-vertical-rounded"></i></button>'
            +   '<div class="dropdown-menu">'
            +     '<a class="dropdown-item editStorage cursor-pointer" data-uid="' + uid + '"'
            +       ' data-name="' + _storageB64(r.Name) + '"'
            +       ' data-shortname="' + _storageB64(r.ShortName) + '"'
            +       ' data-description="' + _storageB64(r.Description) + '"'
            +       ' data-image="' + _storageB64(img) + '"'
            +       ' data-storagetypeuid="' + _storageB64(parseInt(r.StorageTypeUID, 10) || 0) + '">'
            +       '<i class="bx bx-edit-alt me-1"></i> Edit</a>'
            +     '<a class="dropdown-item DeleteStorage cursor-pointer" data-storageuid="' + uid + '"><i class="bx bx-trash me-1"></i> Delete</a>'
            +   '</div></div></td>'
            + '</tr>';
    }).join('');
}

/**
 * Services list page ({ Data, TotalCount, PageNo, RowLimit }) → the { List, Pagination } the page helpers expect
 * @param {object} response
 * @returns {object}
 */
function _storageListResponse(response) {
    var page  = parseInt(response.PageNo, 10) || 1;
    var limit = parseInt(response.RowLimit, 10) || RowLimit;
    response.List       = _buildStorageRows(response.Data || [], (page - 1) * limit);
    response.Pagination = generatePagination(response.TotalCount || 0, page, limit);
    return response;
}

/**
 * @param {number} PageNo
 * @param {number} RowLimit
 * @param {object} Filter
 * @returns {void}
 */
function getStorageDetails(PageNo, RowLimit, Filter) {
    var ctx = _storageCtx();
    $.ajax({
        url: _storageApi + 'getStorageList/' + Math.max(1, parseInt(PageNo, 10) || 1),
        method: 'GET',
        cache: false,
        data: {
            orgId   : ctx.orgId,
            langCode: ctx.langCode,
            RowLimit: RowLimit,
            Filter  : JSON.stringify(Filter || {}),
        },
        success: function(response) {
            if (response.Error) {
                $(ModuleTable + ' tbody').html('');
                $(ModulePag).html('<div class="alert alert-danger" role="alert"><strong>' + _storageEsc(response.Message) + '</strong></div>');
            } else {
                _storageListResponse(response);
                $(ModulePag).html(response.Pagination);
                $(ModuleTable + ' tbody').html(response.List);
            }
            executeTablePagnCommonFunc(response, false);
        },
    });
}

/**
 * Adds orgId / userId / langCode to a write's FormData
 * @param {FormData} formdata
 * @returns {FormData}
 */
function _storageFormCtx(formdata) {
    var ctx = _storageCtx();
    formdata.append('orgId', ctx.orgId);
    formdata.append('userId', ctx.userId);
    formdata.append('langCode', ctx.langCode);
    return formdata;
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
        url: _storageApi + 'addStorageData',
        method: 'POST',
        data: _storageFormCtx(formdata),
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
                executeTablePagnCommonFunc(_storageListResponse(response), true);
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
        url: _storageApi + 'updateStorageData',
        method: 'POST',
        data: _storageFormCtx(formdata),
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
                executeTablePagnCommonFunc(_storageListResponse(response), true);
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
        url: _storageApi + 'deleteStorageDetails',
        method: 'POST',
        cache: false,
        data: $.extend(_storageCtx(), {
            RowLimit: RowLimit,
            PageNo: PageNo,
            Filter: JSON.stringify(Filter || {}),
            StorageUID: StorageUID,
        }),
        success: function(response) {
            if (response.Error) {
                Swal.fire(response.Message, '', 'error');
            } else {
                if (SelectedUIDs.length > 0) {
                    SelectedUIDs = SelectedUIDs.filter(function(item) {
                        return item !== StorageUID;
                    });
                }
                executeTablePagnCommonFunc(_storageListResponse(response), true);
            }
        },
    });
}

/**
 * @returns {void}
 */
function deleteMultipleStorage() {
    $.ajax({
        url: _storageApi + 'deleteBulkStorage',
        method: 'POST',
        cache: false,
        data: $.extend(_storageCtx(), {
            RowLimit: RowLimit,
            PageNo: PageNo,
            Filter: JSON.stringify(Filter || {}),
            StorageUIDs: SelectedUIDs,
        }),
        success: function(response) {
            if (response.Error) {
                Swal.fire(response.Message, '', 'error');
            } else {
                SelectedUIDs = [];
                executeTablePagnCommonFunc(_storageListResponse(response), true);
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
    PageNo = 1;
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
