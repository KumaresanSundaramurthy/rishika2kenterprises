window._r2kSimpleOverlay = true;

var _prodPageRefreshing = false;

// ── Request sequence counters — one per tab loader; stale responses are discarded ──
var _prodReqSeq  = 0;
var _grpReqSeq   = 0;
var _plReqSeq    = 0;
var _catgReqSeq  = 0;
var _brandReqSeq = 0;
var _sizeReqSeq  = 0;

// ── Select-all (Pattern 3) state — Items tab only ─────────────────────────
var _prodSelectAllMode = false;
var _prodTotalRecords  = 0;
var _prodPageCount     = 0;

/**
 * @returns {void}
 */
function _prodUpdateSelectAllBanner() {
    var $banner = $('#prodSelectAllBanner');
    var $msg    = $('#prodSelectAllMsg');
    var $link   = $('#prodSelectAllLink');
    var $clear  = $('#prodSelectAllClear');

    if (!_prodPageCount || !$(ProdHeader).prop('checked')) {
        $banner.addClass('d-none');
        return;
    }

    if (_prodSelectAllMode) {
        $msg.text('All ' + _prodTotalRecords + ' items are selected.');
        $link.addClass('d-none');
        $clear.removeClass('d-none');
    } else {
        $msg.text('All ' + _prodPageCount + ' items on this page are selected.');
        $clear.addClass('d-none');
        if (_prodTotalRecords > _prodPageCount) {
            $link.text('Select all ' + _prodTotalRecords + ' items?').removeClass('d-none');
        } else {
            $link.addClass('d-none');
            $banner.addClass('d-none');
            return;
        }
    }
    $banner.removeClass('d-none');
}

/**
 * @returns {void}
 */
function _prodClearSelectAll() {
    _prodSelectAllMode = false;
    $('#prodSelectAllBanner').addClass('d-none');
    $('#prodSelectAllLink').removeClass('d-none');
    $('#prodSelectAllClear').addClass('d-none');
}

/**
 * @param {number} ProductUID
 * @param {number} IsActive
 * @returns {void}
 */
function toggleProductStatus(ProductUID, IsActive) {
    $.ajax({
        url    : global_services_url + 'api/products/toggleProductStatus',
        method : 'POST',
        cache  : false,
        headers: { Authorization: 'Bearer ' + JwtToken },
        data: {
            orgId     : parseInt(JwtData.Org.OrgUID,  10),
            userId    : parseInt(JwtData.User.UserUID, 10),
            ProductUID: ProductUID,
            IsActive  : IsActive,
        },
        success: function (response) {
            if (response.Error) {
                showToastNotification(response.Message, 'error');
            } else {
                showToastNotification(response.Message, 'success');
                hideUIBlock();
                ajaxLoading(0);
                _prodPageRefreshing = true;
                if (ActiveTabId === 'Groups') {
                    getGroupDetails(PageNo, RowLimit, Filter);
                } else {
                    getProductDetails(PageNo, RowLimit, Filter);
                }
            }
        }
    });
}

function getProductDetails(PageNo, RowLimit, Filter) {
    var reqSeq = ++_prodReqSeq;
    var _overlay = _prodPageRefreshing;
    _prodPageRefreshing = false;
    if (!_overlay) {
        ajaxLoading(0);
    }
    showTabSpinner(ProdTable, ProdPag);
    $.ajax({
        url     : global_services_url + 'api/products/getProductList/' + PageNo,
        method  : 'GET',
        cache   : false,
        headers : { Authorization: 'Bearer ' + JwtToken },
        data    : {
            orgId   : parseInt(JwtData.Org.OrgUID, 10),
            RowLimit: RowLimit,
            Filter  : Filter,
        },
        success: function (response) {
            if (reqSeq !== _prodReqSeq) return;
            ajaxLoading(1);
            if (response.Error) {
                $(ProdTable + ' tbody').html('');
                $(ProdPag).html('<div class="alert alert-danger" role="alert"><strong>' + response.Message + '</strong></div>');
            } else {
                var serialStart = (Math.max(1, parseInt(PageNo, 10)) - 1) * parseInt(RowLimit, 10);
                $(ProdPag).html(generatePagination(response.TotalCount, PageNo, RowLimit));
                $(ProdTable + ' tbody').html(generateProductsHtml(response.Data || [], serialStart, false));
                _prodTotalRecords = parseInt(response.TotalCount) || 0;
                _prodPageCount    = $(ProdTable + ' tbody ' + ProdRow).length;
                if (typeof response.TotalCount !== 'undefined') {
                    updateProductCount(response.TotalCount);
                }
            }
            executeProdPagnFunc(response, false);
            _prodUpdateSelectAllBanner();
        },
        error: function () {
            if (reqSeq !== _prodReqSeq) return;
            ajaxLoading(1);
        }
    });
}

function getGroupDetails(PageNo, RowLimit, Filter) {
    var reqSeq = ++_grpReqSeq;
    var _overlay = _prodPageRefreshing;
    _prodPageRefreshing = false;
    if (!_overlay) {
        ajaxLoading(0);
    }
    showTabSpinner(GroupTable, GroupPag);
    $.ajax({
        url     : global_services_url + 'api/products/getGroupList/' + PageNo,
        method  : 'GET',
        cache   : false,
        headers : { Authorization: 'Bearer ' + JwtToken },
        data    : {
            orgId   : parseInt(JwtData.Org.OrgUID, 10),
            RowLimit: RowLimit,
            Filter  : Filter,
        },
        success: function (response) {
            if (reqSeq !== _grpReqSeq) return;
            ajaxLoading(1);
            if (response.Error) {
                $(GroupTable + ' tbody').html('');
                $(GroupPag).html('<div class="alert alert-danger" role="alert"><strong>' + response.Message + '</strong></div>');
            } else {
                var serialStart = (Math.max(1, parseInt(PageNo, 10)) - 1) * parseInt(RowLimit, 10);
                $(GroupPag).html(generatePagination(response.TotalCount, PageNo, RowLimit));
                $(GroupTable + ' tbody').html(generateProductsHtml(response.Data || [], serialStart, true));
                if (typeof response.TotalCount !== 'undefined') {
                    updateGroupCount(response.TotalCount);
                }
            }
            headerCheckboxTrueFalse(GroupTable, GroupHeader, ProdRow);
        },
        error: function () {
            if (reqSeq !== _grpReqSeq) return;
            ajaxLoading(1);
        }
    });
}

function _prodPageSaveSuccess(response) {
    hideUIBlock();
    ajaxLoading(0);
    _prodPageRefreshing = true;
    if (ActiveTabId === 'Groups') {
        getGroupDetails(PageNo, RowLimit, Filter);
    } else {
        getProductDetails(PageNo, RowLimit, Filter);
    }
}
_prodPageSaveSuccess._needsList = false;

/**
 * Open the product form modal in edit or clone mode.
 * @param {number}  ItemUID    UID of the product to load.
 * @param {boolean} CloneFlag  true = open in clone mode; false = edit mode.
 * @returns {void}
 */
function retrieveProductDetails(ItemUID, CloneFlag) {
    ProductForm.open(CloneFlag ? 'clone' : 'edit', ItemUID, { onSaveSuccess: _prodPageSaveSuccess });
}


/**
 * Soft-delete a single product via the services endpoint.
 * @param {number} ProductUID  UID of the product to delete.
 * @returns {void}
 */
function deleteProduct(ProductUID) {
    $.ajax({
        url    : global_services_url + 'api/products/deleteProductDetails',
        method : 'POST',
        cache  : false,
        headers: { Authorization: 'Bearer ' + JwtToken },
        data   : {
            orgId      : parseInt(JwtData.Org.OrgUID, 10),
            userId     : parseInt(JwtData.User.UserUID, 10),
            ProductUID : ProductUID,
        },
        success: function (response) {
            if (response.Error) {
                showToastNotification(response.Message, 'error');
            } else {
                if (SelectedUIDs.length > 0) {
                    SelectedUIDs = SelectedUIDs.filter(function (item) {
                        return item !== ProductUID;
                    });
                }
                showToastNotification(response.Message, 'success');
                hideUIBlock();
                ajaxLoading(0);
                _prodPageRefreshing = true;
                if (ActiveTabId === 'Groups') {
                    getGroupDetails(PageNo, RowLimit, Filter);
                } else {
                    getProductDetails(PageNo, RowLimit, Filter);
                }
            }
        },
    });
}

/**
 * Bulk soft-delete the selected products (or all matching the active filter) via the services endpoint.
 * @returns {void}
 */
function deleteMultipleProduct() {
    var isGroups = ActiveTabId === 'Groups';
    var postData = {
        orgId  : parseInt(JwtData.Org.OrgUID, 10),
        userId : parseInt(JwtData.User.UserUID, 10),
    };
    if (!isGroups && _prodSelectAllMode) {
        postData.SelectAll = 1;
        postData.Filter    = JSON.stringify(Filter);
    } else {
        postData.ProductUIDs = SelectedUIDs;
    }
    $.ajax({
        url    : global_services_url + 'api/products/deleteBulkProduct',
        method : 'POST',
        cache  : false,
        headers: { Authorization: 'Bearer ' + JwtToken },
        data   : postData,
        success: function (response) {
            if (response.Error) {
                showToastNotification(response.Message, 'error');
            } else {
                SelectedUIDs = [];
                _prodClearSelectAll();
                showToastNotification(response.Message, 'success');
                hideUIBlock();
                ajaxLoading(0);
                _prodPageRefreshing = true;
                if (isGroups) {
                    getGroupDetails(PageNo, RowLimit, Filter);
                } else {
                    getProductDetails(PageNo, RowLimit, Filter);
                }
            }
        },
    });
}

function executeProdPagnFunc(response, tableinfo = false, silent = false) {
    var isGroupTab = (typeof ActiveTabId !== 'undefined' && ActiveTabId === 'Groups');
    if (tableinfo) {
        if (isGroupTab) {
            $(GroupPag).html(response.Pagination);
            $(GroupTable + ' tbody').html(response.List);
            if (typeof response.TotalCount !== 'undefined') {
                updateGroupCount(response.TotalCount);
            }
        } else {
            $(ProdPag).html(response.Pagination);
            $(ProdTable + ' tbody').html(response.List);
            if (typeof response.TotalCount !== 'undefined') {
                updateProductCount(response.TotalCount);
            } else {
                var $countEl = $(ProdPag).find('.pagination-result-count, [data-total-count]');
                if ($countEl.length) updateProductCount(parseInt($countEl.data('total-count') || $countEl.text()) || 0);
            }
        }
    }
    if (response.Stats) {
        updateProductStats(response.Stats);
    }
    if (isGroupTab) {
        headerCheckboxTrueFalse(GroupTable, GroupHeader, ProdRow);
    } else {
        headerCheckboxTrueFalse(ProdTable, ProdHeader, ProdRow);
    }
    MultipleDeleteOption();
}

function updateProductCount(count) {
    var $badge = $('#productTotalCount');
    if (!$badge.length) return;
    if (count > 0) { $badge.text(count).removeClass('d-none'); }
    else           { $badge.text('').addClass('d-none'); }
}
function updateGroupCount(count) {
    var $badge = $('#groupTotalCount');
    if (!$badge.length) return;
    if (count > 0) { $badge.text(count).removeClass('d-none'); }
    else           { $badge.text('').addClass('d-none'); }
}
function updateCategoryCount(count) {
    var $badge = $('#categoryTotalCount');
    if (!$badge.length) return;
    if (count > 0) { $badge.text(count).removeClass('d-none'); }
    else           { $badge.text('').addClass('d-none'); }
}

function updateProductStats(stats) {
    if (!stats) return;
    var s = stats;
    // Total / Active / Inactive
    $('.stat-all .trans-stat-count').text(Number(s.TotalProducts || 0).toLocaleString());
    $('.stat-all .trans-stat-amount .text-success').html('<i class="bx bx-check-circle"></i> ' + Number(s.ActiveCount || 0).toLocaleString() + ' Active');
    $('.stat-all .trans-stat-amount .text-danger').html('<i class="bx bx-x-circle"></i> ' + Number(s.InActiveCount || 0).toLocaleString() + ' In-Active');
    // Stock Value
    $('.stat-paid .trans-stat-count').text(currencySymbol + ' ' + parseFloat(s.TotalStockValue || 0).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ','));
    // Added
    $('.stat-active .trans-stat-count').html(Number(s.AddedThisMonth || 0).toLocaleString() + ' <span style="font-size:var(--r2k-text-70);font-weight:400;">this month</span>');
    $('.stat-active .trans-stat-amount').html(Number(s.AddedThisFY || 0).toLocaleString() + ' this FY &nbsp;|&nbsp; ' + Number(s.RecentlyUpdated || 0).toLocaleString() + ' updated (7d)');
    // Low Stock
    $('.stat-draft .trans-stat-count').text(Number(s.LowStockItems || 0).toLocaleString());
    // Not For Sale
    $('.stat-converted .trans-stat-count').text(Number(s.NotForSale || 0).toLocaleString());
}

/**
 * @param {*} PageNo
 * @param {*} RowLimit
 * @param {*} Filter
 */
/**
 * @param {*} PageNo
 * @param {*} RowLimit
 * @param {*} Filter
 */
function getPriceListDetails(PageNo, RowLimit, Filter) {
    var reqSeq = ++_plReqSeq;
    var _overlay = _prodPageRefreshing;
    _prodPageRefreshing = false;
    if (!_overlay) {
        ajaxLoading(0);
    }
    showTabSpinner(PLTable, PLPag);
    $.ajax({
        url     : global_services_url + 'api/products/getPriceListData/' + PageNo,
        method  : 'GET',
        cache   : false,
        headers : { Authorization: 'Bearer ' + JwtToken },
        data    : { orgId: parseInt(JwtData.Org.OrgUID, 10), RowLimit: RowLimit, Filter: Filter },
        success: function (response) {
            if (reqSeq !== _plReqSeq) return;
            ajaxLoading(1);
            if (response.Error) {
                $(PLTable + ' tbody').html('');
                $(PLPag).html('<div class="alert alert-danger" role="alert"><strong>' + response.Message + '</strong></div>');
            } else {
                var serialStart = (Math.max(1, parseInt(PageNo, 10)) - 1) * parseInt(RowLimit, 10);
                $(PLPag).html(generatePagination(response.TotalCount, PageNo, RowLimit));
                $(PLTable + ' tbody').html(generatePriceListHtml(response.Data || [], serialStart));
                if (typeof response.TotalCount !== 'undefined' && typeof _updatePLCount === 'function') _updatePLCount(response.TotalCount);
                headerCheckboxTrueFalse(PLTable, PLHeader, PLRow);
                MultipleDeleteOption();
                $(window).trigger('scroll');
            }
        },
        error: function () {
            if (reqSeq !== _plReqSeq) return;
            ajaxLoading(1);
        }
    });
}

function getCategoriesDetails(PageNo, RowLimit, Filter) {
    var reqSeq = ++_catgReqSeq;
    var _overlay = _prodPageRefreshing;
    _prodPageRefreshing = false;
    if (!_overlay) {
        ajaxLoading(0);
    }
    showTabSpinner(CatgTable, CatgPag);
    $.ajax({
        url     : global_services_url + 'api/products/getCategoryList/' + PageNo,
        method  : 'GET',
        cache   : false,
        headers : { Authorization: 'Bearer ' + JwtToken },
        data    : {
            orgId   : parseInt(JwtData.Org.OrgUID, 10),
            RowLimit: RowLimit,
            Filter  : Filter,
        },
        success: function (response) {
            if (reqSeq !== _catgReqSeq) return;
            ajaxLoading(1);
            if (response.Error) {
                $(CatgTable + ' tbody').html('');
                $(CatgPag).html('<div class="alert alert-danger" role="alert"><strong>' + response.Message + '</strong></div>');
            } else {
                var serialStart = (Math.max(1, parseInt(PageNo, 10)) - 1) * parseInt(RowLimit, 10);
                $(CatgPag).html(generatePagination(response.TotalCount, PageNo, RowLimit));
                $(CatgTable + ' tbody').html(generateCategoriesHtml(response.Data || [], serialStart));
                if (typeof response.TotalCount !== 'undefined') {
                    updateCategoryCount(response.TotalCount);
                }
            }
            executeCatgPagnFunc(response, false);
            $(window).trigger('scroll');
        },
        error: function () {
            if (reqSeq !== _catgReqSeq) return;
            ajaxLoading(1);
        }
    });
}

/**
 * Create a new category via the services endpoint.
 * @param {FormData} formdata  Category form data (multipart).
 * @param {Function} onSuccess Callback receiving the new InsertId.
 * @returns {void}
 */
function addCategoryDetails(formdata, onSuccess) {
    formdata.append('orgId',  parseInt(JwtData.Org.OrgUID,  10));
    formdata.append('userId', parseInt(JwtData.User.UserUID, 10));
    $.ajax({
        url    : global_services_url + 'api/products/addCategoryDetails',
        method : 'POST',
        data   : formdata,
        cache  : false,
        headers: { Authorization: 'Bearer ' + JwtToken },
        processData: false,
        contentType: false,
        success: function (response) {
            if (response.Error) {
                showToastNotification(response.Message || 'Failed to save category.', 'error');
                $('#CatgSaveButton').prop('disabled', false).text('Save');
            } else {
                $('#categoryForm').trigger('reset');
                if (!$('#categoryModal').data('calledFromItemForm')) {
                    hideUIBlock();
                    ajaxLoading(0);
                    _prodPageRefreshing = true;
                    getCategoriesDetails(PageNo, RowLimit, Filter);
                }
                if (response.InsertId) {
                    var formObj = { InsertId: response.InsertId, CategoryName: formdata.get('CategoryName') };
                    updateCategoryOptions(formObj, 'insert');
                    showToastNotification(response.Message, 'success');
                    if ($('#categoryModal').data('calledFromItemForm')) {
                        $('#categoryModal').data('calledFromItemForm', false);
                        $(document).trigger('catgSavedFromItemForm', [{ id: response.InsertId, name: formdata.get('CategoryName') }]);
                    }
                    if (typeof onSuccess === 'function') onSuccess(response.InsertId);
                }
            }
        }
    });
}

/**
 * Load a single category into the edit form.
 * @param {number} CategoryUID  UID of the category to load.
 * @returns {void}
 */
function retrieveCategoryDetails(CategoryUID) {
    $.ajax({
        url    : global_services_url + 'api/products/retrieveCategoryDetails',
        method : 'GET',
        cache  : false,
        headers: { Authorization: 'Bearer ' + JwtToken },
        data   : {
            orgId      : parseInt(JwtData.Org.OrgUID, 10),
            CategoryUID: CategoryUID,
            langCode   : (JwtData.User && JwtData.User.LangCode) ? JwtData.User.LangCode : 'en',
        },
        success: function (response) {
            if (response.Error) {
                showToastNotification(response.Message, 'error');
            } else {

                $('#categoryForm').trigger('reset');
                $('#CatgModalTitle').text('Edit Category');
                $('.CatgSaveButton').text('Update');
                myTwoDropzone.removeAllFiles(true);
                $('#categoryModal').modal('show');

                $('#CategoryUID').val(response.Data.CategoryUID);
                $('#CategoryName').val(response.Data.Name);
                $('#CategoryDescription').val(response.Data.Description);

            }
        },
    });
}

/**
 * Update an existing category via the services endpoint.
 * @param {FormData} formdata  Category form data (multipart).
 * @param {Function} onSuccess Callback on success.
 * @returns {void}
 */
function editCategoryDetails(formdata, onSuccess) {
    formdata.append('orgId',  parseInt(JwtData.Org.OrgUID,  10));
    formdata.append('userId', parseInt(JwtData.User.UserUID, 10));
    $.ajax({
        url    : global_services_url + 'api/products/updateCategoryDetails',
        method : 'POST',
        data   : formdata,
        cache  : false,
        headers: { Authorization: 'Bearer ' + JwtToken },
        processData: false,
        contentType: false,
        success: function (response) {
            if (response.Error) {
                showToastNotification(response.Message || 'Failed to update category.', 'error');
                $('#CatgSaveButton').prop('disabled', false).text('Update');
            } else {
                $('#categoryForm').trigger('reset');
                hideUIBlock();
                ajaxLoading(0);
                _prodPageRefreshing = true;
                getCategoriesDetails(PageNo, RowLimit, Filter);
                if (formdata.get('CategoryUID')) {
                    updateCategoryOptions({ UpdateId: formdata.get('CategoryUID'), CategoryName: formdata.get('CategoryName') }, 'update');
                }
                showToastNotification(response.Message || 'Category updated.', 'success');
                if (typeof onSuccess === 'function') onSuccess();
            }
        }
    });
}

function catgAttachTrigger(e) { _attachZoneTrigger('Category', e); }

/**
 * Soft-delete a single category via the services endpoint.
 * @param {number} CategoryUID  UID of the category to delete.
 * @returns {void}
 */
function deleteCategory(CategoryUID) {
    $.ajax({
        url    : global_services_url + 'api/products/deleteCategoryDetails',
        method : 'POST',
        cache  : false,
        headers: { Authorization: 'Bearer ' + JwtToken },
        data   : {
            orgId      : parseInt(JwtData.Org.OrgUID,  10),
            userId     : parseInt(JwtData.User.UserUID, 10),
            CategoryUID: CategoryUID,
        },
        success: function (response) {
            if (response.Error) {
                showToastNotification(response.Message, 'error');
            } else {
                if (SelectedUIDs.length > 0) {
                    SelectedUIDs = SelectedUIDs.filter(function (item) {
                        return item !== CategoryUID;
                    });
                }
                showToastNotification(response.Message, 'success');
                hideUIBlock();
                ajaxLoading(0);
                _prodPageRefreshing = true;
                getCategoriesDetails(PageNo, RowLimit, Filter);
                var formObj = {};
                formObj.UpdateId = [CategoryUID];
                updateCategoryOptions(formObj, 'delete');
            }
        },
    });
}

/**
 * Bulk soft-delete the selected categories via the services endpoint.
 * @returns {void}
 */
function deleteMultipleCategory() {
    $.ajax({
        url    : global_services_url + 'api/products/deleteBulkCategory',
        method : 'POST',
        cache  : false,
        headers: { Authorization: 'Bearer ' + JwtToken },
        data   : {
            orgId       : parseInt(JwtData.Org.OrgUID,  10),
            userId      : parseInt(JwtData.User.UserUID, 10),
            CategoryUIDs: SelectedUIDs,
        },
        success: function (response) {
            if (response.Error) {
                showToastNotification(response.Message, 'error');
            } else {
                var formObj = {};
                formObj.UpdateId = SelectedUIDs;
                updateCategoryOptions(formObj, 'delete');
                SelectedUIDs = [];
                showToastNotification(response.Message, 'success');
                hideUIBlock();
                ajaxLoading(0);
                _prodPageRefreshing = true;
                getCategoriesDetails(PageNo, RowLimit, Filter);
            }
        },
    });
}

function executeCatgPagnFunc(response, tableinfo = false) {
    if (tableinfo) {
        var total = response.TotalCount || 0;
        var showingHtml = total > 0
            ? '<div class="col-auto text-muted" style="font-size:var(--r2k-text-82);">Showing <strong>1</strong> – <strong>' + Math.min(RowLimit, total) + '</strong> of <strong>' + total + '</strong> Results</div>'
            : '';
        $(CatgPag).html(showingHtml + '<div class="col-auto">' + response.Pagination + '</div>');
        $(CatgTable + ' tbody').html(response.List);
        if (typeof response.TotalCount !== 'undefined') {
            updateCategoryCount(response.TotalCount);
        }
        $(window).trigger('scroll');
    }
    headerCheckboxTrueFalse(CatgTable, CatgHeader, CatgRow);
    MultipleDeleteOption();
}

// ── Brands ────────────────────────────────────────────────────────────────────

/**
 * @param {number} count
 * @returns {void}
 */
function updateBrandCount(count) {
    var $badge = $('#brandTotalCount');
    if (!$badge.length) return;
    if (count > 0) { $badge.text(count).removeClass('d-none'); }
    else           { $badge.text('').addClass('d-none'); }
}

/**
 * @param {number} PageNo
 * @param {number} RowLimit
 * @param {Object} Filter
 * @returns {void}
 */
function getBrandsDetails(PageNo, RowLimit, Filter) {
    var reqSeq = ++_brandReqSeq;
    var _overlay = _prodPageRefreshing;
    _prodPageRefreshing = false;
    if (!_overlay) {
        ajaxLoading(0);
    }
    showTabSpinner(BrandTable, BrandPag);
    $.ajax({
        url     : global_services_url + 'api/products/getBrandList/' + PageNo,
        method  : 'GET',
        cache   : false,
        headers : { Authorization: 'Bearer ' + JwtToken },
        data    : { orgId: parseInt(JwtData.Org.OrgUID, 10), RowLimit: RowLimit, Filter: Filter },
        success: function (response) {
            if (reqSeq !== _brandReqSeq) return;
            ajaxLoading(1);
            if (response.Error) {
                $(BrandTable + ' tbody').html('');
                $(BrandPag).html('<div class="alert alert-danger" role="alert"><strong>' + response.Message + '</strong></div>');
            } else {
                var serialStart = (Math.max(1, parseInt(PageNo, 10)) - 1) * parseInt(RowLimit, 10);
                $(BrandPag).html(generatePagination(response.TotalCount, PageNo, RowLimit));
                $(BrandTable + ' tbody').html(generateBrandsHtml(response.Data || [], serialStart));
                if (typeof response.TotalCount !== 'undefined') {
                    updateBrandCount(response.TotalCount);
                }
                $(window).trigger('scroll');
            }
        },
        error: function () {
            if (reqSeq !== _brandReqSeq) return;
            ajaxLoading(1);
        }
    });
}

/**
 * @param {FormData} formdata
 * @param {Function} onSuccess
 * @returns {void}
 */
function addBrandDetails(formdata, onSuccess) {
    formdata.append('orgId',  parseInt(JwtData.Org.OrgUID,  10));
    formdata.append('userId', parseInt(JwtData.User.UserUID, 10));
    $.ajax({
        url    : global_services_url + 'api/products/addBrandDetails',
        method : 'POST',
        data   : formdata,
        cache  : false,
        headers: { Authorization: 'Bearer ' + JwtToken },
        processData: false,
        contentType: false,
        success: function (response) {
            if (response.Error) {
                showToastNotification(response.Message || 'Failed to save brand.', 'error');
                $('#BrandSaveButton').prop('disabled', false).text('Save');
            } else {
                $('#brandForm').trigger('reset');
                showToastNotification(response.Message || 'Brand created.', 'success');
                hideUIBlock();
                ajaxLoading(0);
                _prodPageRefreshing = true;
                getBrandsDetails(PageNo, RowLimit, Filter);
                if (typeof onSuccess === 'function') onSuccess(response.InsertId);
            }
        }
    });
}

/**
 * @param {FormData} formdata
 * @param {Function} onSuccess
 * @returns {void}
 */
function editBrandDetails(formdata, onSuccess) {
    formdata.append('orgId',  parseInt(JwtData.Org.OrgUID,  10));
    formdata.append('userId', parseInt(JwtData.User.UserUID, 10));
    $.ajax({
        url    : global_services_url + 'api/products/updateBrandDetails',
        method : 'POST',
        data   : formdata,
        cache  : false,
        headers: { Authorization: 'Bearer ' + JwtToken },
        processData: false,
        contentType: false,
        success: function (response) {
            if (response.Error) {
                showToastNotification(response.Message || 'Failed to update brand.', 'error');
                $('#BrandSaveButton').prop('disabled', false).text('Update');
            } else {
                $('#brandForm').trigger('reset');
                showToastNotification(response.Message || 'Brand updated.', 'success');
                hideUIBlock();
                ajaxLoading(0);
                _prodPageRefreshing = true;
                getBrandsDetails(PageNo, RowLimit, Filter);
                if (typeof onSuccess === 'function') onSuccess();
            }
        }
    });
}

/**
 * @param {number} BrandUID
 * @returns {void}
 */
function deleteBrand(BrandUID) {
    $.ajax({
        url    : global_services_url + 'api/products/deleteBrandDetails',
        method : 'POST',
        cache  : false,
        headers: { Authorization: 'Bearer ' + JwtToken },
        data   : {
            orgId   : parseInt(JwtData.Org.OrgUID,  10),
            userId  : parseInt(JwtData.User.UserUID, 10),
            BrandUID: BrandUID,
        },
        success: function (response) {
            if (response.Error) {
                showToastNotification(response.Message, 'error');
            } else {
                if (SelectedUIDs.length > 0) {
                    SelectedUIDs = SelectedUIDs.filter(function (item) { return item !== BrandUID; });
                }
                showToastNotification(response.Message || 'Brand deleted.', 'success');
                hideUIBlock();
                ajaxLoading(0);
                _prodPageRefreshing = true;
                getBrandsDetails(PageNo, RowLimit, Filter);
            }
        },
    });
}

/**
 * @returns {void}
 */
function deleteMultipleBrand() {
    $.ajax({
        url    : global_services_url + 'api/products/deleteBulkBrand',
        method : 'POST',
        cache  : false,
        headers: { Authorization: 'Bearer ' + JwtToken },
        data   : {
            orgId    : parseInt(JwtData.Org.OrgUID,  10),
            userId   : parseInt(JwtData.User.UserUID, 10),
            BrandUIDs: SelectedUIDs,
        },
        success: function (response) {
            if (response.Error) {
                showToastNotification(response.Message, 'error');
            } else {
                SelectedUIDs = [];
                showToastNotification(response.Message || 'Brands deleted.', 'success');
                hideUIBlock();
                ajaxLoading(0);
                _prodPageRefreshing = true;
                getBrandsDetails(PageNo, RowLimit, Filter);
            }
        },
    });
}

/**
 * @returns {void}
 */
function deleteMultiplePriceList() {
    $.ajax({
        url    : global_services_url + 'api/products/deleteBulkPriceList',
        method : 'POST',
        cache  : false,
        headers: { Authorization: 'Bearer ' + JwtToken },
        data: {
            orgId        : parseInt(JwtData.Org.OrgUID,  10),
            userId       : parseInt(JwtData.User.UserUID, 10),
            PriceListUIDs: SelectedUIDs.join(','),
        },
        success: function (response) {
            if (response.Error) {
                showToastNotification(response.Message, 'error');
            } else {
                SelectedUIDs = [];
                showToastNotification(response.Message || 'Price lists deleted.', 'success');
                _applyPLResponse(response);
            }
        },
    });
}

/**
 * @param {Object}  response
 * @param {boolean} tableinfo
 * @returns {void}
 */
function executeBrandPagnFunc(response, tableinfo = false) {
    if (tableinfo) {
        var total = response.TotalCount || 0;
        var showingHtml = total > 0
            ? '<div class="col-auto text-muted" style="font-size:var(--r2k-text-82);">Showing <strong>1</strong> – <strong>' + Math.min(RowLimit, total) + '</strong> of <strong>' + total + '</strong> Results</div>'
            : '';
        $(BrandPag).html(showingHtml + '<div class="col-auto">' + response.Pagination + '</div>');
        $(BrandTable + ' tbody').html(response.List);
        if (typeof response.TotalCount !== 'undefined') {
            updateBrandCount(response.TotalCount);
        }
        $(window).trigger('scroll');
    }
    headerCheckboxTrueFalse(BrandTable, BrandHeader, BrandRow);
    MultipleDeleteOption();
}

function brandAttachTrigger(e) { _attachZoneTrigger('Brand', e); }

// ── Sizes ──────────────────────────────────────────────────────────────────────

/**
 * @param {number} count
 * @returns {void}
 */
function updateSizeCount(count) {
    var $badge = $('#sizeTotalCount');
    if (!$badge.length) return;
    if (count > 0) { $badge.text(count).removeClass('d-none'); }
    else           { $badge.text('').addClass('d-none'); }
}

/**
 * @param {number} PageNo
 * @param {number} RowLimit
 * @param {Object} Filter
 * @returns {void}
 */
function getSizesDetails(PageNo, RowLimit, Filter) {
    var reqSeq = ++_sizeReqSeq;
    var _overlay = _prodPageRefreshing;
    _prodPageRefreshing = false;
    if (!_overlay) {
        ajaxLoading(0);
    }
    showTabSpinner(SizeTable, SizePag);
    $.ajax({
        url     : global_services_url + 'api/products/getSizeList/' + PageNo,
        method  : 'GET',
        cache   : false,
        headers : { Authorization: 'Bearer ' + JwtToken },
        data    : { orgId: parseInt(JwtData.Org.OrgUID, 10), RowLimit: RowLimit, Filter: Filter },
        success: function (response) {
            if (reqSeq !== _sizeReqSeq) return;
            ajaxLoading(1);
            if (response.Error) {
                $(SizeTable + ' tbody').html('');
                $(SizePag).html('<div class="alert alert-danger" role="alert"><strong>' + response.Message + '</strong></div>');
            } else {
                var serialStart = (Math.max(1, parseInt(PageNo, 10)) - 1) * parseInt(RowLimit, 10);
                $(SizePag).html(generatePagination(response.TotalCount, PageNo, RowLimit));
                $(SizeTable + ' tbody').html(generateSizesHtml(response.Data || [], serialStart));
                if (typeof response.TotalCount !== 'undefined') {
                    updateSizeCount(response.TotalCount);
                }
                $(window).trigger('scroll');
            }
        },
        error: function () {
            if (reqSeq !== _sizeReqSeq) return;
            ajaxLoading(1);
        }
    });
}

/**
 * @param {Object}   formdata
 * @param {Function} onSuccess
 * @returns {void}
 */
function addSizeListDetails(formdata, onSuccess) {
    formdata.orgId  = parseInt(JwtData.Org.OrgUID,  10);
    formdata.userId = parseInt(JwtData.User.UserUID, 10);
    $.ajax({
        url    : global_services_url + 'api/products/addSizeDetails',
        method : 'POST',
        cache  : false,
        headers: { Authorization: 'Bearer ' + JwtToken },
        data   : formdata,
        success: function (response) {
            if (response.Error) {
                showToastNotification(response.Message || 'Failed to save size.', 'error');
                $('#SizeSaveButton').prop('disabled', false).text('Save');
            } else {
                $('#sizeForm').trigger('reset');
                showToastNotification(response.Message || 'Size created.', 'success');
                hideUIBlock();
                ajaxLoading(0);
                _prodPageRefreshing = true;
                getSizesDetails(PageNo, RowLimit, Filter);
                if (typeof onSuccess === 'function') onSuccess(response.InsertId);
            }
        }
    });
}

/**
 * @param {Object}   formdata
 * @param {Function} onSuccess
 * @returns {void}
 */
function editSizeListDetails(formdata, onSuccess) {
    formdata.orgId  = parseInt(JwtData.Org.OrgUID,  10);
    formdata.userId = parseInt(JwtData.User.UserUID, 10);
    $.ajax({
        url    : global_services_url + 'api/products/updateSizeDetails',
        method : 'POST',
        cache  : false,
        headers: { Authorization: 'Bearer ' + JwtToken },
        data   : formdata,
        success: function (response) {
            if (response.Error) {
                showToastNotification(response.Message || 'Failed to update size.', 'error');
                $('#SizeSaveButton').prop('disabled', false).text('Update');
            } else {
                $('#sizeForm').trigger('reset');
                showToastNotification(response.Message || 'Size updated.', 'success');
                hideUIBlock();
                ajaxLoading(0);
                _prodPageRefreshing = true;
                getSizesDetails(PageNo, RowLimit, Filter);
                if (typeof onSuccess === 'function') onSuccess();
            }
        }
    });
}

/**
 * @param {number} SizeUID
 * @returns {void}
 */
function deleteSizeItem(SizeUID) {
    $.ajax({
        url    : global_services_url + 'api/products/deleteSizeDetails',
        method : 'POST',
        cache  : false,
        headers: { Authorization: 'Bearer ' + JwtToken },
        data   : {
            orgId  : parseInt(JwtData.Org.OrgUID,  10),
            userId : parseInt(JwtData.User.UserUID, 10),
            SizeUID: SizeUID,
        },
        success: function (response) {
            if (response.Error) {
                showToastNotification(response.Message, 'error');
            } else {
                if (SelectedUIDs.length > 0) {
                    SelectedUIDs = SelectedUIDs.filter(function (item) { return item !== SizeUID; });
                }
                showToastNotification(response.Message || 'Size deleted.', 'success');
                hideUIBlock();
                ajaxLoading(0);
                _prodPageRefreshing = true;
                getSizesDetails(PageNo, RowLimit, Filter);
            }
        },
    });
}

/**
 * @returns {void}
 */
function deleteMultipleSize() {
    $.ajax({
        url    : global_services_url + 'api/products/deleteBulkSize',
        method : 'POST',
        cache  : false,
        headers: { Authorization: 'Bearer ' + JwtToken },
        data   : {
            orgId   : parseInt(JwtData.Org.OrgUID,  10),
            userId  : parseInt(JwtData.User.UserUID, 10),
            SizeUIDs: SelectedUIDs,
        },
        success: function (response) {
            if (response.Error) {
                showToastNotification(response.Message, 'error');
            } else {
                SelectedUIDs = [];
                showToastNotification(response.Message || 'Sizes deleted.', 'success');
                hideUIBlock();
                ajaxLoading(0);
                _prodPageRefreshing = true;
                getSizesDetails(PageNo, RowLimit, Filter);
            }
        },
    });
}

/**
 * @param {Object}  response
 * @param {boolean} tableinfo
 * @returns {void}
 */
function executeSizePagnFunc(response, tableinfo) {
    tableinfo = tableinfo || false;
    if (tableinfo) {
        var total = response.TotalCount || 0;
        var showingHtml = total > 0
            ? '<div class="col-auto text-muted" style="font-size:var(--r2k-text-82);">Showing <strong>1</strong> – <strong>' + Math.min(RowLimit, total) + '</strong> of <strong>' + total + '</strong> Results</div>'
            : '';
        $(SizePag).html(showingHtml + '<div class="col-auto">' + response.Pagination + '</div>');
        $(SizeTable + ' tbody').html(response.List);
        if (typeof response.TotalCount !== 'undefined') {
            updateSizeCount(response.TotalCount);
        }
        $(window).trigger('scroll');
    }
    headerCheckboxTrueFalse(SizeTable, SizeHeader, SizeRow);
    MultipleDeleteOption();
}


function commonSelectFunctionality(PageSelcType) {
    if (ActiveTabId == 'Item') {
        if (PageSelcType == 'AllPage') {
            CopyAllDatatoSelectItems(ItemUIDs);
        }
        selectTableRecords(ProdTable, ProdRow);
        headerCheckboxTrueFalse(ItemUIDs, ProdHeader);
    } else if (ActiveTabId == 'Categories') {
        if (PageSelcType == 'AllPage') {
            CopyAllDatatoSelectItems(CategoryUIDs);
        }
        selectTableRecords(CatgTable, CatgRow);
        headerCheckboxTrueFalse(CategoryUIDs, CatgHeader);
    }
    $('#selectPagesModal').modal('hide');
}

function commonUnSelectFunctionality(PageSelcType) {
    if (ActiveTabId == 'Item') {
        if (PageSelcType == 'AllPage') {
            removeAllDatatoSelectItems(ItemUIDs);
        }
        unSelectTableRecords(ProdTable, ProdRow);
        headerCheckboxTrueFalse(ItemUIDs, ProdHeader);
    } else if (ActiveTabId == 'Categories') {
        if (PageSelcType == 'AllPage') {
            removeAllDatatoSelectItems(CategoryUIDs);
        }
        unSelectTableRecords(CatgTable, CatgRow);
        headerCheckboxTrueFalse(CategoryUIDs, CatgHeader);
    }
    $('#unSelectPagesModal').modal('hide');
}

function formOpenCloseDefActions() {
    imgData = '';
    if (ActiveTabId == 'Item') {
        // product form reset handled by ProductFormModal hide event in product_form.js
    } else if (ActiveTabId == 'Categories') {
        $('#categoryForm').trigger('reset');
        $('#CatgModalTitle').text('Add Category');
        $('.CatgSaveButton').text('Save');
        $('#categoryForm').find('#CategoryUID').val(0);
        // Replaced old dropzone with attachment zone — reset state instead
        if (typeof _attachResetState === 'function') _attachResetState('Category');
    }
}

function showProductPageDetails() {
    if (typeof updateColumnHighlights === 'function') updateColumnHighlights();
    if (ActiveTabId == 'Item') {
        getProductDetails(PageNo, RowLimit, Filter);
    } else if (ActiveTabId == 'Groups') {
        getGroupDetails(PageNo, RowLimit, Filter);
    } else if (ActiveTabId == 'Categories') {
        getCategoriesDetails(PageNo, RowLimit, Filter);
    } else if (ActiveTabId == 'Brands') {
        getBrandsDetails(PageNo, RowLimit, Filter);
    } else if (ActiveTabId == 'Sizes') {
        getSizesDetails(PageNo, RowLimit, Filter);
    } else if (ActiveTabId == 'PriceLists') {
        getPriceListDetails(PageNo, RowLimit, Filter);
    }
}



function resetProdTypeFilter() {
    $('.prodtype-checkbox').prop('checked', false);
    if (Filter.ProductType) {
        applyProdTypeFilter();
    }
}

function applyProdTypeFilter() {
    PageNo = 0;
    delete Filter['ProductType'];
    let prodTypeIds = $('.prodtype-checkbox:checked').map(function () {
        return $(this).val();
    }).get();
    $('#prodTypeFilter').removeClass('text-primary');
    if (prodTypeIds.length > 0) {
        Filter['ProductType'] = prodTypeIds;
        $('#prodTypeFilter').addClass('text-primary');
    }
    $('#prodTypeFilterBox').hide();
    showProductPageDetails();
}

function closeProdTypeFilter() {
    $('#prodTypeFilterBox').hide();
}

// ── Shared category cache helpers ────────────────────────────────────────────

var _cfbConfig = {
    checkClass : 'category-checkbox',
    applyFn    : 'applyCategoryFilter',
    resetFn    : 'resetCategoryFilter',
    uid        : 'products'
};


// ── Category filter box ──────────────────────────────────────────────────────

function toggleCategoryFilter() {
    var $target = $('#categoryFilterBox');
    $('.mp-filterbox').not($target).hide();
    if ($target.is(':visible')) { $target.hide(); return; }
    var rect = document.getElementById('categoryFilter').getBoundingClientRect();
    $target.css({ top: (rect.bottom + 4) + 'px', left: rect.left + 'px' }).show();
    CategoryAppend.filterBox('#categoryFilterBox', _cfbConfig, Filter.Category || []);
}

function closeCategoryFilter() {
    $('#categoryFilterBox').hide();
}

function resetCategoryFilter() {
    $('.category-checkbox').prop('checked', false);
    $('#selectAllCategories').prop('checked', false);
    $('#selectAllLabel').text('Select All');
    if (Filter.Category) {
        delete Filter['Category'];
        $('#categoryFilter').removeClass('text-primary');
        PageNo = 0;
        showProductPageDetails();
    }
    $('#categoryFilterBox').hide();
}

function applyCategoryFilter() {
    PageNo = 0;
    delete Filter['Category'];
    let selectedCategoryIds = $('.category-checkbox:checked').map(function () {
        return $(this).val();
    }).get();
    $('#categoryFilter').removeClass('text-primary');
    if (selectedCategoryIds.length > 0) {
        Filter['Category'] = selectedCategoryIds;
        $('#categoryFilter').addClass('text-primary');
    }
    $('#categoryFilterBox').hide();
    showProductPageDetails();
}

function updateCategoryOptions(fields, type) {
    if (type == 'insert') {
        $('#AddEditItemForm #Category').append('<option value="' + fields.InsertId + '">' + fields.CategoryName + '</option>');
        if (typeof DropdownCache !== 'undefined') DropdownCache.patchCategories('insert', fields);
    } else if (type == 'update') {
        var idStr = String(fields.UpdateId).trim();
        $("#AddEditItemForm #Category option[value='" + idStr + "']").text(fields.CategoryName);
        if (typeof DropdownCache !== 'undefined') DropdownCache.patchCategories('update', fields);
    } else if (type == 'delete') {
        $.each(fields.UpdateId, function (i, id) {
            $('#AddEditItemForm #Category option[value="' + id + '"]').remove();
        });
        if (typeof DropdownCache !== 'undefined') DropdownCache.patchCategories('delete', fields);
    }
    $('#categoryFilterBox').empty(); // force re-render on next open
    window._catgListDirty = true;   // mark category tab stale so next switch refreshes
}


function refreshSearchStorage($this) {
    $('#storageFilterBox').show();
    ajaxLoading(0);
    $('#storageFilterBox').html('<div class="d-flex justify-content-center align-items-center p-3"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>');
    $.ajax({
        url: global_base_url + 'storage/getAllStorage/',
        method: "POST",
        cache: false,
        success: function (response) {
            ajaxLoading(1);
            if (response.Error) {
                $('#storageFilterBox').html('');
                $('#storageFilterBox').html('<div class="alert alert-danger" role="alert"><strong>' + response.Message + '</strong></div>');
            } else {
                $('#storageFilterBox').html(response.HtmlData);
            }
        },
    });
}

function toggleStorageFilter() {
    const $target = $('#storageFilterBox');
    $('.mp-filterbox').not($target).hide();
    $target.toggle();
    // $('#storageFilterBox').toggle();
    // ($('#storageFilterBox .storage-checkbox').length == 0)
}

function closeStorageFilter() {
    $('#storageFilterBox').hide();
}

function resetStorageFilter() {
    $('.storage-checkbox').prop('checked', false);
    $('#selectAllStorage').prop('checked', false);
    if (Filter.Storage) {
        applyStorageFilter();
    }
}

function applyStorageFilter() {
    PageNo = 0;
    delete Filter['Storage'];
    let selectedStorageIds = $('.storage-checkbox:checked').map(function () {
        return $(this).val();
    }).get();
    $('#storageFilter').removeClass('text-primary');
    if (selectedStorageIds.length > 0) {
        Filter['Storage'] = selectedStorageIds;
        $('#storageFilter').addClass('text-primary');
    }
    $('#storageFilterBox').hide();
    showProductPageDetails();
}

function toggleAllStorage(main) {
    var isChecked = $(main).prop('checked');
    $('.storage-checkbox').prop('checked', isChecked);
    $('#str_selectAllLabel').text(isChecked ? 'Clear All' : 'Select All');
}

function loadTaxDetailOptions() {
    $('#TaxPercentage').select2({
        placeholder: '-- Select Tax Percentage --',
        allowClear: true,
        width: 'resolve',
        templateResult: function (data) {
            if (!data.id) return data.text;
            const el    = $(data.element);
            const left  = el.data('left');
            const right = el.data('right');
            // Use null/undefined check — NOT !left, because 0 is a valid falsy value
            if (left == null || left === '') return data.text;
            return $('<div class="d-flex justify-content-between align-items-center">' +
                    '<span class="fw-semibold">' + left + '</span>' +
                    '<span class="text-muted small">' + right + '</span>' +
                    '</div>');
        },
        templateSelection: function (data) {
            if (!data.id) return data.text;
            const el    = $(data.element);
            const left  = el.data('left');
            const right = el.data('right');
            // Use null/undefined check — NOT !left, because 0 is a valid falsy value
            if (left == null || left === '') return data.text;
            // Two-column: percentage fixed left, breakdown truncates right,
            // padding-right leaves room for the clear (×) button
            return $('<span style="display:flex;align-items:center;width:100%;min-width:0;padding-right:20px;">' +
                    '<span style="flex-shrink:0;font-weight:600;margin-right:8px;white-space:nowrap;">' + left + '</span>' +
                    '<span style="flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:#6c757d;font-size:.82em;">' + right + '</span>' +
                    '</span>');
        },
        dropdownParent: $('#ProductFormModal'),
    });

    // Tag the Select2 container so we can scope CSS to this field only
    $('#TaxPercentage').data('select2').$container.addClass('r2k-tax-pct-s2');

    if (!$('#r2k-tax-pct-sel2-style').length) {
        $('<style id="r2k-tax-pct-sel2-style">' +
            // Make __rendered flex so our two-column templateSelection works
            '#select2-TaxPercentage-container{display:flex!important;align-items:center!important;overflow:hidden!important;}' +
        '</style>').appendTo('head');
    }
}
// ── Product & Category Attachment Zone ───────────────────────────────────────
// Core logic is in js/common/attachments.js (shared with Customers & Vendors).
// _attachCfg, _attachState, _attachBlobUrls and all generic functions live there.
// Product/Category-specific trigger helpers are below.







function prodAttachTrigger(e) { _attachZoneTrigger('Product', e); }





function _attachRender(entityType) {
    var cfg   = _attachCfg[entityType];
    var state = _attachState[entityType];
    if (!cfg || !state) return;

    var list  = document.getElementById(cfg.listId);
    var label = document.getElementById(cfg.emptyId.replace('Empty','Label')) || document.getElementById(entityType === 'Product' ? 'prodAttachLabel' : 'catgAttachLabel');
    var hint  = document.getElementById(entityType === 'Product' ? 'prodAttachHint' : 'catgAttachHint');
    var icon  = document.getElementById(entityType === 'Product' ? 'prodAttachIcon' : 'catgAttachIcon');
    if (!list) return;

    list.innerHTML = '';
    var activeEx = (state.existing||[]).filter(function(x){ return !(state.toDelete||[]).includes(x.AttachUID); });
    var total = activeEx.length + (state.newFiles||[]).length;
    var remaining = cfg.maxFiles - total;

    // Zone text changes contextually — zone itself never hides
    if (total === 0) {
        if (icon)  { icon.className = 'bx bx-image-add'; icon.style.color = '#9ca3af'; }
        if (label) label.textContent = 'Drag & drop images';
        if (hint)  hint.textContent  = 'JPG, GIF or PNG · Max ' + cfg.maxFiles + ' · ' + cfg.maxTotalMB + ' MB total';
        list.style.display = 'none';
        return;
    } else if (remaining > 0) {
        if (icon)  { icon.className = 'bx bx-plus'; icon.style.color = '#6366f1'; }
        if (label) label.textContent = 'Add more images';
        if (hint)  hint.textContent  = remaining + ' slot' + (remaining > 1 ? 's' : '') + ' remaining';
    } else {
        if (icon)  { icon.className = 'bx bx-check-circle'; icon.style.color = '#10b981'; }
        if (label) label.textContent = 'Maximum reached';
        if (hint)  hint.textContent  = cfg.maxFiles + ' of ' + cfg.maxFiles + ' images added';
    }
    list.style.display = '';

    // Build gallery arrays once for this render pass
    var existingGallery = (state.existing||[]).map(function(a){
        return { url: a.Url || a.FilePath, name: a.FileName };
    });

    // Ensure stable blob URLs for new files (don't re-create on every render)
    if (!_attachBlobUrls[entityType]) _attachBlobUrls[entityType] = [];
    var blobUrls = _attachBlobUrls[entityType];
    (state.newFiles||[]).forEach(function(f, i){
        if (!blobUrls[i]) blobUrls[i] = URL.createObjectURL(f);
    });
    // Trim stale entries if files were removed
    blobUrls.length = (state.newFiles||[]).length;

    var newGallery = (state.newFiles||[]).map(function(f, i){
        return { url: blobUrls[i], name: f.name };
    });

    // ── Render existing saved attachments ─────────────────────────────────
    (state.existing||[]).forEach(function(att, exIdx) {
        var deleted = (state.toDelete||[]).includes(att.AttachUID);

        var item = document.createElement('div');
        item.className = 'prod-attach-item is-existing' + (deleted ? ' pending-delete' : '');

        // Thumbnail — set src via JS property (safe for any URL type)
        var thumb = document.createElement('img');
        thumb.alt   = att.FileName || '';
        thumb.title = 'Click to preview';
        thumb.src   = att.Url || att.FilePath || '';
        (function(gallery, idx){ thumb.addEventListener('click', function(e){ e.stopPropagation(); openImageGallery(gallery, idx); }); })(existingGallery, exIdx);
        item.appendChild(thumb);

        // Name
        var name = document.createElement('span');
        name.className = 'attach-name';
        name.title     = att.FileName || '';
        name.textContent = att.FileName || '';
        item.appendChild(name);

        // Size
        var size = document.createElement('span');
        size.className = 'attach-size';
        size.textContent = _attachFmtSize(att.FileSize || 0);
        item.appendChild(size);

        // Remove / Undo button
        var btn = document.createElement('button');
        btn.className = 'attach-remove';
        btn.type  = 'button';
        btn.title = deleted ? 'Undo remove' : 'Remove';
        btn.innerHTML = deleted ? '<i class="bx bx-undo"></i>' : '<i class="bx bx-x"></i>';
        if (deleted) {
            (function(et, uid){ btn.addEventListener('click', function(e){ e.stopPropagation(); _attachUndoDelete(et, uid); }); })(entityType, att.AttachUID);
        } else {
            (function(et, uid){ btn.addEventListener('click', function(e){ e.stopPropagation(); _attachRemoveExisting(et, uid); }); })(entityType, att.AttachUID);
        }
        item.appendChild(btn);
        list.appendChild(item);
    });

    // ── Render new (not yet uploaded) files ───────────────────────────────
    (state.newFiles||[]).forEach(function(file, idx) {
        var item = document.createElement('div');
        item.className = 'prod-attach-item';

        var thumb = document.createElement('img');
        thumb.alt   = file.name;
        thumb.title = 'Click to preview';
        thumb.src   = blobUrls[idx];   // set via property — safe for blob: URLs
        (function(gallery, i){ thumb.addEventListener('click', function(e){ e.stopPropagation(); openImageGallery(gallery, i); }); })(newGallery, idx);
        item.appendChild(thumb);

        var name = document.createElement('span');
        name.className = 'attach-name';
        name.title     = file.name;
        name.textContent = file.name;
        item.appendChild(name);

        var size = document.createElement('span');
        size.className = 'attach-size';
        size.textContent = _attachFmtSize(file.size);
        item.appendChild(size);

        var btn = document.createElement('button');
        btn.className = 'attach-remove';
        btn.type  = 'button';
        btn.title = 'Remove';
        btn.innerHTML = '<i class="bx bx-x"></i>';
        (function(et, i){ btn.addEventListener('click', function(e){ e.stopPropagation(); _attachRemoveNew(et, i); }); })(entityType, idx);
        item.appendChild(btn);
        list.appendChild(item);
    });
}



function _attachRemoveExisting(entityType, attachUID) {
    Swal.fire({ title: 'Remove this image?', text: 'It will be deleted when you save.', icon: 'warning', showCancelButton: true, confirmButtonColor: '#ef4444', confirmButtonText: 'Yes, remove', cancelButtonColor: '#6b7280' })
    .then(function(r) {
        if (!r.isConfirmed) return;
        var state = _attachState[entityType];
        if (!(state.toDelete||[]).includes(attachUID)) state.toDelete.push(attachUID);
        _attachRender(entityType);
        var cfg = _attachCfg[entityType];
        if (cfg) document.getElementById(cfg.deleteField).value = state.toDelete.join(',');
    });
}
function _attachUndoDelete(entityType, attachUID) {
    var state = _attachState[entityType];
    state.toDelete = (state.toDelete||[]).filter(function(id){ return id !== attachUID; });
    _attachRender(entityType);
    var cfg = _attachCfg[entityType];
    if (cfg) document.getElementById(cfg.deleteField).value = state.toDelete.join(',');
}
function _attachRemoveNew(entityType, idx) { _attachState[entityType].newFiles.splice(idx,1); _attachRender(entityType); }
function _attachFmtSize(b){ if(!b) return ''; if(b<1024) return b+' B'; if(b<1048576) return (b/1024).toFixed(1)+' KB'; return (b/1048576).toFixed(1)+' MB'; }
function _escHtml(s){ return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

/* ── List render helpers ─────────────────────────────────────────────────── */

/**
 * Format a "YYYY-MM-DD HH:MM:SS" UTC string for list display.
 * @param {string|null} str
 * @returns {{ formatted: string, ago: string|null }}
 */
function _prodFormatDateTime(str) {
    if (!str) return { formatted: '—', ago: null };
    var d = new Date(str.replace(' ', 'T') + 'Z');
    if (isNaN(d.getTime())) return { formatted: str, ago: null };
    var months     = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    var monthsFull = ['January','February','March','April','May','June','July','August','September','October','November','December'];
    var dy  = d.getUTCDate(),  mo  = d.getUTCMonth(), yr  = d.getUTCFullYear();
    var hr  = d.getUTCHours(), min = d.getUTCMinutes();
    var ampm = hr >= 12 ? 'PM' : 'AM', h12 = hr % 12 || 12;
    var fmt = (typeof _transListDateTimeFormat !== 'undefined' && _transListDateTimeFormat) ? _transListDateTimeFormat : 'd M Y h:i A';
    var tok = { d: String(dy).padStart(2,'0'), j: String(dy), m: String(mo+1).padStart(2,'0'), n: String(mo+1), Y: String(yr), y: String(yr).slice(-2), F: monthsFull[mo], M: months[mo], H: String(hr).padStart(2,'0'), G: String(hr), h: String(h12).padStart(2,'0'), g: String(h12), i: String(min).padStart(2,'0'), s: String(d.getUTCSeconds()).padStart(2,'0'), A: ampm, a: ampm.toLowerCase() };
    var formatted = fmt.replace(/[djmnYyFMHGhgisAa]/g, function(t) { return tok[t] !== undefined ? tok[t] : t; });
    var ago = timeAgo(str);
    return { formatted: formatted, ago: ago };
}

/**
 * Format a date string with ListDateFormat (date only).
 * @param {string|null} str
 * @returns {string}
 */
function _prodFormatDate(str) {
    if (!str) return '';
    var d = new Date(str.replace(' ', 'T') + 'Z');
    if (isNaN(d.getTime())) return str;
    var months     = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    var monthsFull = ['January','February','March','April','May','June','July','August','September','October','November','December'];
    var dy = d.getUTCDate(), mo = d.getUTCMonth(), yr = d.getUTCFullYear();
    var fmt = (typeof _transListDateFormat !== 'undefined' && _transListDateFormat) ? _transListDateFormat : 'd M Y';
    var tok = { d: String(dy).padStart(2,'0'), j: String(dy), m: String(mo+1).padStart(2,'0'), n: String(mo+1), Y: String(yr), y: String(yr).slice(-2), F: monthsFull[mo], M: months[mo] };
    return fmt.replace(/[djmnYyFM]/g, function(t) { return tok[t] !== undefined ? tok[t] : t; });
}

/**
 * Format a number to decimalPlaces, trimming trailing zeros (mirrors PHP smartDecimal).
 * @param {*} n
 * @returns {string}
 */
function _prodFmtNum(n) {
    var dec = (typeof decimalPlaces !== 'undefined') ? decimalPlaces : 2;
    return (parseFloat(n) || 0).toFixed(dec).replace(/(\.\d*?)0+$/, '$1').replace(/\.$/, '');
}

/**
 * Build the "Last Updated" cell HTML from timestamp + author name.
 * @param {string|null} updatedOn
 * @param {string|null} updatedBy
 * @returns {string}
 */
function _prodDateCell(updatedOn, updatedBy) {
    var ts = _prodFormatDateTime(updatedOn);
    return '<div class="r2k-col-date">' + _escHtml(ts.formatted) + '</div>'
        + (ts.ago ? '<div class="r2k-col-date-ago">' + _escHtml(ts.ago) + '</div>' : '')
        + '<div class="text-muted r2k-col-date-by">by ' + _escHtml(updatedBy || '—') + '</div>';
}

/**
 * Build tbody HTML for the Items tab (isGroup=false) or Groups tab (isGroup=true).
 * @param {Array}   data         Array of row objects from services response.Data
 * @param {number}  serialStart  0-based S.No offset
 * @param {boolean} isGroup      true = Groups tab (Unit col, no Category/Qty/PurchasePrice)
 * @returns {string}
 */
function generateProductsHtml(data, serialStart, isGroup) {
    var sym     = (typeof currencySymbol !== 'undefined') ? currencySymbol : '₹';
    var table   = isGroup ? GroupTable : ProdTable;
    var showSno = $(table).find('thead .table-serialno').is(':not(.d-none)');
    var colspan = isGroup ? 9 : 11;
    if (!data || !data.length) {
        return '<tr><td colspan="' + colspan + '"><div class="d-flex justify-content-center align-items-center vh-50"><div class="d-flex flex-column align-items-center w-100" style="max-width:500px;"><div class="w-100 mb-3 d-flex justify-content-center"><img src="/assets/img/elements/no-record-found.png" class="img-fluid" style="max-height:40vh;object-fit:contain;" /></div><div class="flex-grow-1 d-flex flex-column align-items-center justify-content-center"><span class="mb-2">Add a Product Now</span><a href="javascript:void(0);" class="btn btn-primary px-3 addItem"><i class="bx bx-plus"></i> Create Item</a></div></div></div></td></tr>';
    }
    var html = '';
    $.each(data, function(i, r) {
        var uid       = parseInt(r.ProductUID, 10);
        var sno       = serialStart + i + 1;
        var name      = _escHtml(r.ItemName || '—');
        var isActive  = parseInt(r.IsActive, 10) === 1;
        var isCombo   = parseInt(r.IsComposite, 10) === 1;
        var hasVar    = parseInt(r.IsBrandApplicable, 10) === 1 || parseInt(r.IsSizeApplicable, 10) === 1;
        var atts = []; try { atts = JSON.parse(r.AttachmentsJson || '[]'); } catch(e) {}
        var imgSrc = (atts[0] && atts[0].url) ? atts[0].url : null;
        var words = (r.ItemName || '').trim().split(/\s+/);
        var initials = (words[0] ? words[0][0] : '').toUpperCase() + (words[1] ? words[1][0].toUpperCase() : '');
        var avatarHtml = imgSrc
            ? '<img src="' + _escHtml(imgSrc) + '" alt="' + name + '" class="rounded cursor-pointer prod-list-img" data-images="' + _escHtml(r.AttachmentsJson || '[]') + '" style="width:40px;height:40px;object-fit:cover;" />'
            : '<span class="avatar-initial rounded ' + (isCombo ? 'bg-label-warning' : 'bg-label-secondary') + '">' + initials + '</span>';
        var typeBadge  = (r.ProductType === 'Service') ? '<span class="badge bg-label-info">Service</span>' : '<span class="badge bg-label-primary">Product</span>';
        var comboBadge = isCombo ? ' <span class="badge bg-label-warning ms-1">Combo</span>' : '';
        var selTaxStr = r.TaxPercentage ? '<div class="text-muted tinysmall mt-1">' + _escHtml(r.TaxPercentage) + '% ' + (r.SellingTaxType === 'With Tax' ? 'incl.' : 'excl.') + ' tax</div>' : '';
        var purTaxStr = r.PurchaseTaxType ? '<div class="text-muted tinysmall mt-1">' + _escHtml(r.TaxPercentage) + '% ' + (r.PurchaseTaxType === 'With Tax' ? 'incl.' : 'excl.') + ' tax</div>' : '';
        var pn = _escHtml(r.PartNumber || ''), iname = _escHtml(r.ItemName || ''), hsn = _escHtml(r.HSNSACCode || '');
        var bcAttrs = 'data-uid="' + uid + '" data-partnumber="' + pn + '" data-itemname="' + iname + '" data-price="' + _escHtml(_prodFmtNum(r.SellingPrice)) + '" data-mrp="' + _escHtml(_prodFmtNum(r.MRP)) + '" data-purchaseprice="' + _escHtml(_prodFmtNum(r.PurchasePrice)) + '" data-category="' + _escHtml(r.CategoryName || '') + '" data-hsncode="' + hsn + '"';

        /* Qty cell (items tab only) */
        var qtyHtml = '';
        if (!isGroup) {
            if (isCombo || r.ProductType === 'Service') {
                qtyHtml = '<span class="text-muted">—</span>';
            } else {
                var qty      = parseFloat(r.AvailableQuantity) || 0;
                var lowStock = r.LowStockAlertAt && qty <= parseFloat(r.LowStockAlertAt) && qty > 0;
                var unit     = _escHtml(r.PUShortName || '');
                qtyHtml = qty > 0
                    ? '<span class="' + (lowStock ? 'text-warning' : 'text-dark') + ' fw-semibold">' + _prodFmtNum(qty) + ' <span class="text-primary">' + unit + '</span></span>'
                    : '<span class="text-danger fw-semibold">' + _prodFmtNum(qty) + ' <span class="text-primary">' + unit + '</span></span>';
            }
        }

        html += '<tr id="product-row-' + uid + '"' + (isCombo ? ' class="combo-parent-row"' : '') + '>'
            + '<td><div class="form-check form-check-inline"><input class="form-check-input table-chkbox productsCheck" type="checkbox" value="' + uid + '"></div></td>'
            + '<td class="' + (showSno ? '' : 'd-none') + '"><span class="text-muted" style="font-size:var(--r2k-text-78);">' + sno + '</span></td>'
            + '<td><div class="d-flex align-items-center gap-2">'
            + (isCombo ? '<button type="button" class="btn btn-icon btn-sm text-warning ComboExpandBtn p-0 me-1" data-uid="' + uid + '" data-loaded="0" title="View Components"><i class="bx bx-chevron-right fs-5"></i></button>' : '<span style="display:inline-block;"></span>')
            + '<div class="avatar avatar-sm me-2">' + avatarHtml + '</div>'
            + '<div><a href="javascript:void(0);" class="prod-profile-link r2k-profile-link text-dark fw-semibold text-decoration-none" data-uid="' + uid + '" title="View Profile">' + name + '</a>' + comboBadge
            + (hasVar ? '<i class="bx bx-grid-alt BrandStockModalBtn bsm-name-tag" data-uid="' + uid + '" data-name="' + iname + '" data-unit="' + _escHtml(r.PUShortName || '') + '" title="Variant stock breakdown"></i>' : '')
            + '<div class="d-flex align-items-center gap-2 mt-1 prod-item-meta">'
            + (!isGroup ? typeBadge : '') + (r.HSNSACCode ? '<span class="prod-hsn-code">' + hsn + '</span>' : '')
            + (!isCombo && r.PartNumber ? '<button type="button" class="btn p-0 border-0 bg-transparent BarcodeOnlyBtn" ' + bcAttrs + ' title="Print Barcode — ' + pn + '"><i class="bx bx-barcode text-primary" style="font-size:1.35rem;vertical-align:middle;"></i></button><button type="button" class="btn p-0 border-0 bg-transparent QROnlyBtn" ' + bcAttrs + ' title="Print QR Code — ' + pn + '"><i class="bx bx-qr text-info" style="font-size:1.1rem;vertical-align:middle;"></i></button>' : '')
            + '</div></div></div></td>'
            + '<td><div class="dropdown d-inline-block"><span class="badge ' + (isActive ? 'bg-label-success' : 'bg-label-danger') + ' cursor-pointer" style="font-size:var(--r2k-text-xs);" data-bs-toggle="dropdown">' + (isActive ? 'Active' : 'In-Active') + '<i class="bx bx-chevron-down" style="font-size:var(--r2k-text-65);"></i></span><ul class="dropdown-menu r2k-action-menu"><li><button class="dropdown-item prod-status-toggle" data-uid="' + uid + '" data-newstatus="' + (isActive ? 0 : 1) + '">' + (isActive ? '<i class="bx bx-x-circle me-2 text-danger"></i>Mark In-Active' : '<i class="bx bx-check-circle me-2 text-success"></i>Mark Active') + '</button></li></ul></div></td>'
            + '<td>' + (isGroup ? _escHtml(r.PUShortName || '—') : _escHtml(r.CategoryName || '—')) + '</td>'
            + (!isGroup ? '<td>' + qtyHtml + '</td>' : '')
            + '<td>' + (r.MRP && parseFloat(r.MRP) > 0 ? '<div class="text-muted" style="font-size:var(--r2k-text-md);">' + sym + ' ' + _prodFmtNum(r.MRP) + '</div>' : '<span class="text-muted">—</span>') + '</td>'
            + '<td><div class="text-dark fw-semibold">' + sym + ' ' + _prodFmtNum(r.SellingPrice) + '</div>' + selTaxStr + '</td>'
            + (!isGroup ? '<td>' + (isCombo ? '<span class="text-muted">—</span>' : '<div class="text-dark fw-semibold">' + sym + ' ' + _prodFmtNum(r.PurchasePrice) + '</div>' + purTaxStr) + '</td>' : '')
            + '<td>' + _prodDateCell(r.UpdatedOn, r.UpdatedBy) + '</td>'
            + '<td><div class="d-flex align-items-center justify-content-center gap-1"><a href="javascript:void(0);" class="btn btn-icon btn-sm text-warning EditProduct" data-uid="' + uid + '" data-iscomposite="' + (isCombo ? 1 : 0) + '" title="Edit"><i class="bx bx-edit"></i></a><div class="dropdown"><button class="trans-actions-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="bx bx-dots-vertical-rounded fs-5"></i></button><ul class="dropdown-menu dropdown-menu-end r2k-action-menu">'
            + (!isCombo && r.PartNumber ? '<li><span class="dropdown-header text-uppercase" style="font-size:var(--r2k-text-xs);letter-spacing:.4px;color:#adb5bd;padding:4px 12px 2px;">Print Label</span></li><li><button class="dropdown-item BarcodeOnlyBtn" ' + bcAttrs + '><i class="bx bx-barcode me-2 text-primary"></i>Print Barcode</button></li><li><button class="dropdown-item QROnlyBtn" ' + bcAttrs + '><i class="bx bx-qr me-2 text-info"></i>Print QR Code</button></li><li><hr class="dropdown-divider my-1"></li>' : '')
            + '<li><button class="dropdown-item CloneProduct" data-uid="' + uid + '"><i class="bx bx-copy me-2 text-secondary"></i>Clone</button></li><li><hr class="dropdown-divider my-1"></li><li><button class="dropdown-item text-danger DeleteProduct" data-productuid="' + uid + '"><i class="bx bx-trash me-2"></i>Delete</button></li></ul></div></div></td>'
            + '</tr>';

        if (isCombo) {
            html += '<tr id="combo-bom-row-' + uid + '" class="d-none combo-bom-row"><td colspan="' + colspan + '" class="p-0"><div class="combo-bom-content px-3 py-0" style="border-left:4px solid #fd7e14;background:linear-gradient(to right,rgba(253,126,20,.06),transparent 60%);"><div class="combo-bom-loading text-muted small py-2 ps-1"><i class="bx bx-loader-alt bx-spin me-1"></i> Loading components...</div></div></td></tr>';
        }
    });
    return html;
}

/**
 * Build tbody HTML for the Categories tab.
 * @param {Array}  data         Array of row objects from services response.Data
 * @param {number} serialStart  0-based S.No offset
 * @returns {string}
 */
function generateCategoriesHtml(data, serialStart) {
    var showSno = $(CatgTable).find('thead .table-serialno').is(':not(.d-none)');
    if (!data || !data.length) {
        return '<tr><td colspan="6"><div class="d-flex justify-content-center align-items-center" style="height:57vh;"><div class="d-flex flex-column align-items-center w-100" style="max-width:500px;padding:1rem;"><div class="w-100 mb-3" style="flex:3;display:flex;justify-content:center;align-items:center;"><img src="/assets/img/elements/no-record-found.png" class="img-fluid" style="max-height:40vh;object-fit:contain;" /></div><div style="flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;"><span class="mb-2">Add a Category Now</span><a href="javascript:void(0);" class="btn btn-primary px-3 addCategory"><i class="bx bx-plus"></i> Create Category</a></div></div></div></td></tr>';
    }
    var html = '';
    $.each(data, function(i, r) {
        var sno       = serialStart + i + 1;
        var uid       = _escHtml(r.CategoryUID);
        var atts = []; try { atts = JSON.parse(r.AttachmentsJson || '[]'); } catch(e) {}
        var imgSrc    = (atts[0] && atts[0].url) ? atts[0].url : null;
        var words     = (r.Name || '').trim().split(/\s+/);
        var initials  = (words[0] ? words[0][0] : '').toUpperCase() + (words[1] ? words[1][0].toUpperCase() : '');
        var prodCount = parseInt(r.ProductCount, 10) || 0;
        var avatarHtml = imgSrc
            ? '<img src="' + _escHtml(imgSrc) + '" alt="' + _escHtml(r.Name) + '" class="rounded cursor-pointer catg-list-img" data-images="' + _escHtml(r.AttachmentsJson || '[]') + '" style="width:40px;height:40px;object-fit:cover;" />'
            : '<span class="avatar-initial rounded bg-label-secondary">' + initials + '</span>';
        var nameB64 = btoa(unescape(encodeURIComponent(r.Name || '')));
        var descB64 = btoa(unescape(encodeURIComponent(r.Description || '')));
        html += '<tr>'
            + '<td><div class="form-check form-check-inline"><input class="form-check-input categoryCheck" type="checkbox" value="' + uid + '"></div></td>'
            + '<td class="' + (showSno ? '' : 'd-none') + '">' + sno + '</td>'
            + '<td><div class="d-flex align-items-center gap-2"><div class="avatar avatar-sm me-2">' + avatarHtml + '</div><span class="fw-medium">' + _escHtml(r.Name) + '</span></div></td>'
            + '<td>' + (prodCount > 0 ? '<a href="javascript:void(0);" class="badge bg-label-primary catg-prod-count-btn" data-catguid="' + uid + '" data-catgname="' + _escHtml(r.Name) + '" title="View products in this category">' + prodCount + ' item' + (prodCount > 1 ? 's' : '') + '</a>' : '<span class="text-muted small">—</span>') + '</td>'
            + '<td>' + _prodDateCell(r.UpdatedOn, r.UpdatedBy) + '</td>'
            + '<td class="text-center align-middle"><div class="d-flex align-items-center justify-content-center gap-1">'
            + '<a href="javascript:void(0);" class="btn btn-icon btn-sm text-warning editCategory" data-uid="' + uid + '" data-name="' + nameB64 + '" data-description="' + descB64 + '" data-attachments="' + _escHtml(r.AttachmentsJson || '[]') + '" title="Edit"><i class="bx bx-edit"></i></a>'
            + '<div class="dropdown"><button class="trans-actions-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="bx bx-dots-vertical-rounded fs-5"></i></button><ul class="dropdown-menu dropdown-menu-end shadow-sm" style="font-size:var(--r2k-text-82);min-width:160px;"><li><button class="dropdown-item text-danger DeleteCategory" data-categoryuid="' + uid + '" data-productcount="' + prodCount + '" data-categoryname="' + _escHtml(r.Name) + '"><i class="bx bx-trash me-2"></i>Delete</button></li></ul></div></div></td>'
            + '</tr>';
    });
    return html;
}

/**
 * Build tbody HTML for the Brands tab.
 * @param {Array}  data         Array of row objects from services response.Data
 * @param {number} serialStart  0-based S.No offset
 * @returns {string}
 */
function generateBrandsHtml(data, serialStart) {
    var showSno = $(BrandTable).find('thead .table-serialno').is(':not(.d-none)');
    if (!data || !data.length) {
        return '<tr><td colspan="6"><div class="r2k-list-empty"><div class="r2k-list-empty-inner"><div class="r2k-list-empty-img"><img src="/assets/img/elements/no-record-found.png" class="img-fluid" /></div><div class="r2k-list-empty-action"><span class="mb-2">Add a Brand Now</span><a href="javascript:void(0);" class="btn btn-primary px-3 addBrand"><i class="bx bx-plus"></i> Create Brand</a></div></div></div></td></tr>';
    }
    var html = '';
    $.each(data, function(i, r) {
        var sno       = serialStart + i + 1;
        var uid       = _escHtml(r.BrandUID);
        var atts = []; try { atts = JSON.parse(r.AttachmentsJson || '[]'); } catch(e) {}
        var imgSrc    = (atts[0] && atts[0].url) ? atts[0].url : null;
        var words     = (r.BrandName || '').trim().split(/\s+/);
        var initials  = (words[0] ? words[0][0] : '').toUpperCase() + (words[1] ? words[1][0].toUpperCase() : '');
        var prodCount = parseInt(r.ProductCount, 10) || 0;
        var avatarHtml = imgSrc
            ? '<img src="' + _escHtml(imgSrc) + '" alt="' + _escHtml(r.BrandName) + '" class="rounded cursor-pointer catg-list-img" data-images="' + _escHtml(r.AttachmentsJson || '[]') + '" style="width:40px;height:40px;object-fit:cover;" />'
            : '<span class="avatar-initial rounded bg-label-info">' + initials + '</span>';
        var nameB64 = btoa(unescape(encodeURIComponent(r.BrandName || '')));
        var codeB64 = btoa(unescape(encodeURIComponent(r.BrandCode || '')));
        var descB64 = btoa(unescape(encodeURIComponent(r.Description || '')));
        html += '<tr>'
            + '<td><div class="form-check form-check-inline"><input class="form-check-input brandCheck" type="checkbox" value="' + uid + '"></div></td>'
            + '<td class="' + (showSno ? '' : 'd-none') + '">' + sno + '</td>'
            + '<td><div class="d-flex align-items-center gap-2"><div class="avatar avatar-sm me-2">' + avatarHtml + '</div><div><span class="fw-medium">' + _escHtml(r.BrandName) + '</span>' + (r.BrandCode ? '<div class="text-muted r2k-item-subtext">' + _escHtml(r.BrandCode) + '</div>' : '') + '</div></div></td>'
            + '<td>' + (prodCount > 0 ? '<span class="badge bg-label-primary">' + prodCount + ' item' + (prodCount > 1 ? 's' : '') + '</span>' : '<span class="text-muted small">—</span>') + '</td>'
            + '<td>' + _prodDateCell(r.UpdatedOn, r.UpdatedBy) + '</td>'
            + '<td class="text-center align-middle"><div class="d-flex align-items-center justify-content-center gap-1">'
            + '<a href="javascript:void(0);" class="btn btn-icon btn-sm text-warning editBrand" data-uid="' + uid + '" data-name="' + nameB64 + '" data-code="' + codeB64 + '" data-description="' + descB64 + '" data-attachments="' + _escHtml(r.AttachmentsJson || '[]') + '" title="Edit"><i class="bx bx-edit"></i></a>'
            + '<div class="dropdown"><button class="trans-actions-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="bx bx-dots-vertical-rounded fs-5"></i></button><ul class="dropdown-menu dropdown-menu-end shadow-sm r2k-actions-menu"><li><button class="dropdown-item text-danger DeleteBrand" data-branduid="' + uid + '" data-productcount="' + prodCount + '" data-brandname="' + _escHtml(r.BrandName) + '"><i class="bx bx-trash me-2"></i>Delete</button></li></ul></div></div></td>'
            + '</tr>';
    });
    return html;
}

/**
 * Build a compact dimension summary string for the sizes list.
 * @param {Object} r  Row object with dimension fields
 * @returns {string}
 */
function _prodSizeDimSummary(r) {
    var uom = r.DimensionUOM || '', wuom = r.WeightUOM || '', parts = [], lwh = [];
    if (r.Length    && parseFloat(r.Length)    > 0) lwh.push(_prodFmtNum(r.Length));
    if (r.Width     && parseFloat(r.Width)     > 0) lwh.push(_prodFmtNum(r.Width));
    if (r.Height    && parseFloat(r.Height)    > 0) lwh.push(_prodFmtNum(r.Height));
    if (lwh.length)  parts.push(lwh.join(' × ') + (uom  ? ' ' + uom  : ''));
    if (r.Depth     && parseFloat(r.Depth)     > 0) parts.push('D ' + _prodFmtNum(r.Depth)     + (uom  ? ' ' + uom  : ''));
    if (r.Diameter  && parseFloat(r.Diameter)  > 0) parts.push('Ø ' + _prodFmtNum(r.Diameter)  + (uom  ? ' ' + uom  : ''));
    if (r.Thickness && parseFloat(r.Thickness) > 0) parts.push('T ' + _prodFmtNum(r.Thickness) + (uom  ? ' ' + uom  : ''));
    if (r.Weight    && parseFloat(r.Weight)    > 0) parts.push(_prodFmtNum(r.Weight) + (wuom ? ' ' + wuom : ''));
    return parts.join(' · ');
}

/**
 * Build tbody HTML for the Sizes tab.
 * @param {Array}  data         Array of row objects from services response.Data
 * @param {number} serialStart  0-based S.No offset
 * @returns {string}
 */
function generateSizesHtml(data, serialStart) {
    var showSno = $(SizeTable).find('thead .table-serialno').is(':not(.d-none)');
    if (!data || !data.length) {
        return '<tr><td colspan="7"><div class="r2k-list-empty"><div class="r2k-list-empty-inner"><div class="r2k-list-empty-img"><img src="/assets/img/elements/no-record-found.png" class="img-fluid" /></div><div class="r2k-list-empty-action"><span class="mb-2">Add a Size Now</span><a href="javascript:void(0);" class="btn btn-primary px-3 addSize"><i class="bx bx-plus"></i> Create Size</a></div></div></div></td></tr>';
    }
    var html = '';
    $.each(data, function(i, r) {
        var sno       = serialStart + i + 1;
        var uid       = _escHtml(r.SizeUID);
        var words     = (r.SizeName || '').trim().split(/\s+/);
        var initial   = (words[0] ? words[0][0] : '').toUpperCase() + (words[1] ? words[1][0].toUpperCase() : '');
        var dimSummary = _prodSizeDimSummary(r);
        var prodCount  = parseInt(r.ProductCount, 10) || 0;
        var nameB64 = btoa(unescape(encodeURIComponent(r.SizeName || '')));
        var codeB64 = btoa(unescape(encodeURIComponent(r.SizeCode || '')));
        var descB64 = btoa(unescape(encodeURIComponent(r.Description || '')));
        html += '<tr>'
            + '<td><div class="form-check form-check-inline"><input class="form-check-input sizeCheck" type="checkbox" value="' + uid + '"></div></td>'
            + '<td class="' + (showSno ? '' : 'd-none') + '">' + sno + '</td>'
            + '<td><div class="d-flex align-items-center gap-2"><div class="avatar avatar-sm me-2"><span class="avatar-initial rounded bg-label-warning">' + initial + '</span></div><div><span class="fw-medium">' + _escHtml(r.SizeName) + '</span>' + (r.SizeCode ? '<div class="text-muted r2k-item-subtext">' + _escHtml(r.SizeCode) + '</div>' : '') + '</div></div></td>'
            + '<td>' + (dimSummary ? '<span class="text-muted r2k-dim-text">' + _escHtml(dimSummary) + '</span>' : '<span class="text-muted small">—</span>') + '</td>'
            + '<td>' + (prodCount > 0 ? '<span class="badge bg-label-primary">' + prodCount + ' item' + (prodCount > 1 ? 's' : '') + '</span>' : '<span class="text-muted small">—</span>') + '</td>'
            + '<td>' + _prodDateCell(r.UpdatedOn, r.UpdatedBy) + '</td>'
            + '<td class="text-center align-middle"><div class="d-flex align-items-center justify-content-center gap-1">'
            + '<a href="javascript:void(0);" class="btn btn-icon btn-sm text-warning editSize" data-uid="' + uid + '" data-name="' + nameB64 + '" data-code="' + codeB64 + '" data-description="' + descB64 + '" data-length="' + (parseFloat(r.Length)||0) + '" data-width="' + (parseFloat(r.Width)||0) + '" data-height="' + (parseFloat(r.Height)||0) + '" data-depth="' + (parseFloat(r.Depth)||0) + '" data-diameter="' + (parseFloat(r.Diameter)||0) + '" data-thickness="' + (parseFloat(r.Thickness)||0) + '" data-weight="' + (parseFloat(r.Weight)||0) + '" data-dimensionuom="' + _escHtml(r.DimensionUOM||'') + '" data-weightuom="' + _escHtml(r.WeightUOM||'') + '" title="Edit"><i class="bx bx-edit"></i></a>'
            + '<div class="dropdown"><button class="trans-actions-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="bx bx-dots-vertical-rounded fs-5"></i></button><ul class="dropdown-menu dropdown-menu-end shadow-sm r2k-actions-menu"><li><button class="dropdown-item text-danger DeleteSize" data-sizeuid="' + uid + '" data-productcount="' + prodCount + '" data-sizename="' + _escHtml(r.SizeName) + '"><i class="bx bx-trash me-2"></i>Delete</button></li></ul></div></div></td>'
            + '</tr>';
    });
    return html;
}

/**
 * Build tbody HTML for the Price Lists tab.
 * @param {Array}  data         Array of row objects from services response.Data
 * @param {number} serialStart  0-based S.No offset
 * @returns {string}
 */
function generatePriceListHtml(data, serialStart) {
    var showSno = $(PLTable).find('thead .table-serialno').is(':not(.d-none)');
    if (!data || !data.length) {
        return '<tr><td colspan="10"><div class="d-flex justify-content-center align-items-center" style="height:57vh;"><div class="d-flex flex-column align-items-center w-100" style="max-width:500px;padding:1rem;"><div class="w-100 mb-3" style="flex:3;display:flex;justify-content:center;align-items:center;"><img src="/assets/img/elements/no-record-found.png" class="img-fluid" style="max-height:40vh;object-fit:contain;" /></div><div style="flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;"><span class="mb-2">No price lists found. Create one to get started.</span><a href="javascript:void(0);" class="btn btn-primary px-3" id="NewPriceListEmpty"><i class="bx bx-plus"></i> Create Price List</a></div></div></div></td></tr>';
    }
    var _aLabels = { All: 'All Customers', Groups: 'Customer Groups', Customers: 'Specific Customers' };
    var _aColors = { All: 'bg-label-primary', Groups: 'bg-label-info', Customers: 'bg-label-warning' };
    var _sLabels = { All: 'All Products', Specific: 'Specific Products' };
    var _sColors = { All: 'bg-label-secondary', Specific: 'bg-label-success' };
    var _bLabels = { SellingPrice: 'Selling Price', UnitPrice: 'Unit Price', PurchasePrice: 'Purchase Price' };
    var html = '';
    $.each(data, function(i, r) {
        var sno  = serialStart + i + 1;
        var uid  = parseInt(r.PriceListUID, 10);
        var at   = r.AssignedToType || 'All';
        var sc   = r.Scope || 'All';
        var ts   = _prodFormatDateTime(r.UpdatedAt || r.UpdatedOn);
        html += '<tr class="pl-list-row">'
            + '<td class="table-checkbox text-center align-middle"><div class="form-check d-flex justify-content-center align-items-center mb-0"><input class="form-check-input table-chkbox priceListCheck" type="checkbox" value="' + uid + '"></div></td>'
            + '<td class="' + (showSno ? '' : 'd-none') + '">' + sno + '</td>'
            + '<td><div class="fw-medium">' + _escHtml(r.Name) + '</div><div class="text-muted" style="font-size:.74rem;">Priority: ' + (parseInt(r.Priority,10)||1) + '</div></td>'
            + '<td><span class="badge ' + (_aColors[at]||'bg-label-secondary') + '" style="font-size:.74rem;">' + (_aLabels[at]||at) + '</span></td>'
            + '<td><span class="badge ' + (_sColors[sc]||'bg-label-secondary') + '" style="font-size:.74rem;">' + (_sLabels[sc]||sc) + '</span>' + (sc === 'All' ? '<div class="text-muted" style="font-size:var(--r2k-text-sm);">' + (_bLabels[r.GlobalBasedOn||'SellingPrice']||'') + '</div>' : '') + '</td>'
            + '<td class="text-nowrap" style="font-size:var(--r2k-text-82);">' + (r.ValidFrom ? _prodFormatDate(r.ValidFrom) : '<span class="text-muted">Permanent</span>') + '</td>'
            + '<td class="text-nowrap" style="font-size:var(--r2k-text-82);">' + (r.ValidTo   ? _prodFormatDate(r.ValidTo)   : '<span class="text-muted">No expiry</span>')   + '</td>'
            + '<td>' + (parseInt(r.Status,10) === 1 ? '<span class="badge bg-label-success" style="font-size:.74rem;">Active</span>' : '<span class="badge bg-label-danger" style="font-size:.74rem;">In-Active</span>') + '</td>'
            + '<td><div class="r2k-col-date">' + _escHtml(ts.formatted) + '</div>' + (ts.ago ? '<div class="r2k-col-date-ago">' + _escHtml(ts.ago) + '</div>' : '') + '<div class="text-muted r2k-col-date-by">by ' + _escHtml(r.UpdatedByName || r.UpdatedBy || '—') + '</div></td>'
            + '<td class="text-center align-middle"><div class="d-flex align-items-center justify-content-center gap-1"><a href="javascript:void(0);" class="btn btn-icon btn-sm text-warning editPriceList" data-uid="' + uid + '" title="Edit"><i class="bx bx-edit"></i></a><div class="dropdown"><button class="trans-actions-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="bx bx-dots-vertical-rounded fs-5"></i></button><ul class="dropdown-menu dropdown-menu-end shadow-sm" style="font-size:var(--r2k-text-82);min-width:160px;"><li><button class="dropdown-item text-danger deletePriceList" data-uid="' + uid + '" data-name="' + _escHtml(r.Name) + '"><i class="bx bx-trash me-2"></i>Delete</button></li></ul></div></div></td>'
            + '</tr>';
    });
    return html;
}

/**
 * @param {string} entityType
 * @param {number} entityUID
 * @returns {void}
 */
function _attachLoadExisting(entityType, entityUID) {
    if (!entityUID) return;
    $.ajax({
        url    : global_services_url + 'api/products/getAttachments',
        method : 'GET',
        headers: { Authorization: 'Bearer ' + JwtToken },
        data   : {
            orgId     : parseInt(JwtData.Org.OrgUID, 10),
            EntityType: entityType,
            EntityUID : entityUID,
        },
        success: function (resp) {
            if (resp && !resp.Error) { _attachState[entityType].existing = resp.Attachments || []; _attachRender(entityType); }
        }
    });
}

// ── Variant stock modal ───────────────────────────────────────────────────────

var _bsmPalette = [
    'bp-avatar--0','bp-avatar--1','bp-avatar--2','bp-avatar--3',
    'bp-avatar--4','bp-avatar--5','bp-avatar--6','bp-avatar--7',
];

/**
 * Render fetched variant stock data into the modal body.
 * @param {Object} res  - AJAX response from /products/getVariantStock
 * @param {string} unit - short unit name (e.g. "PCS")
 * @returns {void}
 */
function _bsmRender(res, unit) {
    var dec = (typeof decimalPlaces !== 'undefined') ? decimalPlaces : 2;

    if (res.Error || !res.Data || !res.Data.length) {
        $('#bsmList').html(
            '<div class="bsm-empty">' +
            '<i class="bx bx-purchase-tag-x"></i>' +
            '<span>No variant stock recorded yet.<br>' +
            '<small>Set variant opening quantities when editing this product.</small></span>' +
            '</div>'
        );
        $('#bsmTotalAvail').text('0');
        $('#bsmTotalOpen').text('0');
        $('#bsmUnit').text('');
        $('#bsmTotalBar').css('--bsm-pct', '0%');
        return;
    }

    var totalAvail = 0;
    var totalOpen  = 0;
    res.Data.forEach(function (v) {
        totalAvail += parseFloat(v.AvailableQty) || 0;
        totalOpen  += parseFloat(v.OpeningQty)   || 0;
    });

    var pct      = totalOpen > 0 ? Math.min(totalAvail / totalOpen, 1) * 100 : (totalAvail > 0 ? 100 : 0);
    var barColor = pct > 50 ? '#71dd37' : (pct > 15 ? '#ffab00' : '#ff3e1d');

    $('#bsmTotalAvail').text(totalAvail.toFixed(dec));
    $('#bsmTotalOpen').text(totalOpen.toFixed(dec));
    $('#bsmUnit').text(unit || '');
    $('#bsmTotalBar').css({ '--bsm-pct': pct.toFixed(1) + '%', '--bsm-bar-color': barColor });

    var rows = res.Data.map(function (v) {
        var sizeName  = v.SizeName  || '';
        var brandName = v.BrandName || '';
        var brandCode = v.BrandCode || '';
        var openQty   = parseFloat(v.OpeningQty)  || 0;
        var availQty  = parseFloat(v.AvailableQty) || 0;

        // Avatar letter: prefer brand initial, fall back to size initial
        var avatarLetter = (brandName || sizeName || '?')[0].toUpperCase();
        var avatarIdx    = (avatarLetter.charCodeAt(0) || 0) % 8;
        var bPct         = openQty > 0 ? Math.min(availQty / openQty, 1) * 100 : (availQty > 0 ? 100 : 0);
        var bClr         = bPct > 50 ? '#71dd37' : (bPct > 15 ? '#ffab00' : '#ff3e1d');
        var aClr         = availQty > 0 ? 'text-success' : (availQty < 0 ? 'text-danger' : 'text-muted');

        // Build label line: "Large · SBJ" or just "SBJ" or just "Large"
        var labelParts = [];
        if (sizeName)  { labelParts.push(_escHtml(sizeName)); }
        if (brandName) { labelParts.push(_escHtml(brandName)); }
        var label    = labelParts.join(' <span class="text-muted">·</span> ');
        var codeHtml = brandCode
            ? '<span class="badge bg-label-secondary bsm-code ms-1">' + _escHtml(brandCode) + '</span>'
            : '';

        return '<div class="bsm-row">' +
               '<div class="bp-avatar bsm-avatar ' + _bsmPalette[avatarIdx] + '">' +
               avatarLetter +
               '</div>' +
               '<div class="bsm-info">' +
               '<div class="bsm-brand-name fw-semibold">' + label + codeHtml + '</div>' +
               '<div class="bsm-bar-wrap">' +
               '<div class="bsm-bar" style="--bsm-pct:' + bPct.toFixed(1) + '%;--bsm-bar-color:' + bClr + '"></div>' +
               '</div>' +
               '</div>' +
               '<div class="bsm-figures">' +
               '<div class="bsm-fig">' +
               '<span class="bsm-fig-label">Opening</span>' +
               '<span class="bsm-fig-val text-muted">' + openQty.toFixed(dec) + '</span>' +
               '</div>' +
               '<div class="bsm-fig-div"></div>' +
               '<div class="bsm-fig">' +
               '<span class="bsm-fig-label">Available</span>' +
               '<span class="bsm-fig-val fw-semibold ' + aClr + '">' + availQty.toFixed(dec) + '</span>' +
               '</div>' +
               '</div>' +
               '</div>';
    });

    $('#bsmList').html(rows.join(''));
}

/**
 * Open the variant stock modal for a product.
 * @param {number} uid  - ProductUID
 * @param {string} name - product name
 * @param {string} unit - unit short name
 * @returns {void}
 */
$(document).on('click', '.BrandStockModalBtn', function (e) {
    e.stopPropagation();
    var $btn = $(this);
    var uid  = $btn.data('uid');
    var name = String($btn.data('name') || '');
    var unit = String($btn.data('unit') || '');

    $('#bsmProductName').text(name);
    $('#bsmUnit').text('');
    $('#bsmTotalAvail').text('—');
    $('#bsmTotalOpen').text('—');
    $('#bsmTotalBar').css('--bsm-pct', '0%');
    $('#bsmList').html(
        '<div class="bsm-loading"><i class="bx bx-loader-alt bx-spin me-2"></i>Loading variant stock…</div>'
    );
    $('#brandStockModal').modal('show');

    ajaxLoading(0);
    $.ajax({
        url    : global_services_url + 'api/products/getVariantStock',
        method : 'GET',
        cache  : false,
        headers: { Authorization: 'Bearer ' + JwtToken },
        data   : {
            orgId     : parseInt(JwtData.Org.OrgUID, 10),
            ProductUID: uid,
        },
        /**
         * @param {Object} res
         * @returns {void}
         */
        success: function (res) { _bsmRender(res, unit); },
        /**
         * @returns {void}
         */
        error: function () {
            $('#bsmList').html(
                '<div class="bsm-empty">' +
                '<i class="bx bx-error-circle text-danger"></i>' +
                '<span class="text-danger">Failed to load variant stock.</span>' +
                '</div>'
            );
        },
        /**
         * @returns {void}
         */
        complete: function () { ajaxLoading(1); }
    });
});

