var _prodPageRefreshing = false;

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
 * Show a spinner row in the given table tbody and optionally hide pagination.
 * @param {string} tableSelector
 * @param {string} paginationSelector
 * @returns {void}
 */
function showTabSpinner(tableSelector, paginationSelector) {
    var cols = $(tableSelector + ' thead tr:first th:visible').length || 6;
    $(tableSelector + ' tbody').html(
        '<tr><td colspan="' + cols + '" class="text-center py-4">' +
        '<span class="spinner-border spinner-border-sm text-primary me-2"></span>' +
        '<span class="text-muted" style="font-size:.85rem;">Loading...</span>' +
        '</td></tr>'
    );
    if (paginationSelector) $(paginationSelector).css('visibility', 'hidden');
}

/**
 * @param {*} PageNo
 * @param {*} RowLimit
 * @param {*} Filter
 */
function toggleProductStatus(ProductUID, IsActive) {
    $.ajax({
        url: '/products/toggleProductStatus',
        method: 'POST',
        cache: false,
        data: {
            ProductUID  : ProductUID,
            IsActive    : IsActive,
            IsComposite : ActiveTabId === 'Groups' ? 1 : 0,
            PageNo      : PageNo,
            RowLimit    : RowLimit,
            Filter      : Filter,
            [CsrfName]  : CsrfToken
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
    var _overlay = _prodPageRefreshing;
    _prodPageRefreshing = false;
    if (!_overlay) {
        ajaxLoading(0);
    }
    showTabSpinner(ProdTable, ProdPag);
    $.ajax({
        url: '/products/getProductList',
        method: "POST",
        cache: false,
        data: {
            RowLimit: RowLimit,
            PageNo: PageNo,
            Filter: Filter,
            [CsrfName]: CsrfToken,
        },
        success: function (response) {
            ajaxLoading(1);
            $(ProdPag).css('visibility', '');
            if (response.Error) {
                $(ProdTable + ' tbody').html('');
                $(ProdPag).html('<div class="alert alert-danger" role="alert"><strong>' + response.Message + '</strong></div>');
            } else {
                $(ProdPag).html(response.Pagination);
                $(ProdTable + ' tbody').html(response.List);
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
            ajaxLoading(1);
            $(ProdPag).css('visibility', '');
        }
    });
}

function getGroupDetails(PageNo, RowLimit, Filter) {
    var _overlay = _prodPageRefreshing;
    _prodPageRefreshing = false;
    if (!_overlay) {
        ajaxLoading(0);
    }
    showTabSpinner(GroupTable, GroupPag);
    $.ajax({
        url: '/products/getGroupList',
        method: 'POST',
        cache: false,
        data: {
            RowLimit: RowLimit,
            PageNo: PageNo,
            Filter: Filter,
            [CsrfName]: CsrfToken,
        },
        success: function (response) {
            ajaxLoading(1);
            $(GroupPag).css('visibility', '');
            if (response.Error) {
                $(GroupTable + ' tbody').html('');
                $(GroupPag).html('<div class="alert alert-danger" role="alert"><strong>' + response.Message + '</strong></div>');
            } else {
                $(GroupPag).html(response.Pagination);
                $(GroupTable + ' tbody').html(response.List);
                if (typeof response.TotalCount !== 'undefined') {
                    updateGroupCount(response.TotalCount);
                }
            }
            headerCheckboxTrueFalse(GroupTable, GroupHeader, ProdRow);
        },
        error: function () {
            ajaxLoading(1);
            $(GroupPag).css('visibility', '');
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

function retrieveProductDetails(ItemUID, CloneFlag) {
    ProductForm.open(CloneFlag ? 'clone' : 'edit', ItemUID, { onSaveSuccess: _prodPageSaveSuccess });
}


function deleteProduct(ProductUID) {
    $.ajax({
        url: '/products/deleteProductDetails',
        method: "POST",
        cache: false,
        data: {
            RowLimit    : RowLimit,
            PageNo      : PageNo,
            Filter      : Filter,
            ProductUID  : ProductUID,
            IsComposite : ActiveTabId === 'Groups' ? 1 : 0,
            ModuleId    : ItemModuleId,
            [CsrfName]  : CsrfToken,
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

function deleteMultipleProduct() {
    var isGroups = ActiveTabId === 'Groups';
    var postData;
    if (!isGroups && _prodSelectAllMode) {
        postData = {
            SelectAll   : 1,
            Filter      : JSON.stringify(Filter),
            IsComposite : 0,
            ModuleId    : ItemModuleId,
            [CsrfName]  : CsrfToken,
        };
    } else {
        postData = {
            RowLimit    : RowLimit,
            PageNo      : PageNo,
            Filter      : Filter,
            ProductUIDs : SelectedUIDs,
            IsComposite : isGroups ? 1 : 0,
            ModuleId    : ItemModuleId,
            [CsrfName]  : CsrfToken,
        };
    }
    $.ajax({
        url: '/products/deleteBulkProduct',
        method: "POST",
        cache: false,
        data: postData,
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
    $('.stat-paid .trans-stat-count').text(currencySymbol + ' ' + parseFloat(s.TotalStockValue || 0).toFixed(typeof JwtData !== 'undefined' && JwtData.GenSettings ? JwtData.GenSettings.DecimalPoints || 2 : 2).replace(/\B(?=(\d{3})+(?!\d))/g, ','));
    // Added
    $('.stat-active .trans-stat-count').html(Number(s.AddedThisMonth || 0).toLocaleString() + ' <span style="font-size:.7rem;font-weight:400;">this month</span>');
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
    var _overlay = _prodPageRefreshing;
    _prodPageRefreshing = false;
    if (!_overlay) {
        ajaxLoading(0);
    }
    showTabSpinner(PLTable, PLPag);
    $.ajax({
        url: '/products/getPriceListData',
        method: 'POST',
        cache: false,
        data: { RowLimit: RowLimit, PageNo: PageNo, Filter: Filter, [CsrfName]: CsrfToken },
        success: function (response) {
            ajaxLoading(1);
            $(PLPag).css('visibility', '');
            if (response.Error) {
                $(PLTable + ' tbody').html('');
                $(PLPag).html('<div class="alert alert-danger" role="alert"><strong>' + response.Message + '</strong></div>');
            } else {
                _applyPLResponse(response);
            }
        },
        error: function () {
            ajaxLoading(1);
            $(PLPag).css('visibility', '');
        }
    });
}

function getCategoriesDetails(PageNo, RowLimit, Filter) {
    var _overlay = _prodPageRefreshing;
    _prodPageRefreshing = false;
    if (!_overlay) {
        ajaxLoading(0);
    }
    showTabSpinner(CatgTable, CatgPag);
    $.ajax({
        url: '/products/getCategoryList',
        method: "POST",
        cache: false,
        data: {
            RowLimit: RowLimit,
            PageNo: PageNo,
            Filter: Filter,
            [CsrfName]: CsrfToken,
        },
        success: function (response) {
            ajaxLoading(1);
            $(CatgPag).css('visibility', '');
            if (response.Error) {
                $(CatgTable + ' tbody').html('');
                $(CatgPag).html('<div class="alert alert-danger" role="alert"><strong>' + response.Message + '</strong></div>');
            } else {
                $(CatgPag).html(response.Pagination);
                $(CatgTable + ' tbody').html(response.List);
                if (typeof response.TotalCount !== 'undefined') {
                    updateCategoryCount(response.TotalCount);
                }
            }
            executeCatgPagnFunc(response, false);
            $(window).trigger('scroll');
        },
        error: function () {
            ajaxLoading(1);
            $(CatgPag).css('visibility', '');
        }
    });
}

function addCategoryDetails(formdata, onSuccess) {
    $.ajax({
        url: '/products/addCategoryDetails',
        method: 'POST',
        data: formdata,
        cache: false,
        processData: false,
        contentType: false,
        enctype: 'multipart/form-data',
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

function retrieveCategoryDetails(CategoryUID) {
    $.ajax({
        url: '/products/retrieveCategoryDetails',
        method: "POST",
        cache: false,
        data: {
            CategoryUID: CategoryUID,
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

function editCategoryDetails(formdata, onSuccess) {
    $.ajax({
        url: '/products/updateCategoryDetails',
        method: 'POST',
        data: formdata,
        cache: false,
        processData: false,
        contentType: false,
        enctype: 'multipart/form-data',
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

function deleteCategory(CategoryUID) {
    $.ajax({
        url: '/products/deleteCategoryDetails',
        method: "POST",
        cache: false,
        data: {
            RowLimit: RowLimit,
            PageNo: PageNo,
            Filter: Filter,
            CategoryUID: CategoryUID,
            ModuleId: CategoryModuleId
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

function deleteMultipleCategory() {
    $.ajax({
        url: '/products/deleteBulkCategory',
        method: "POST",
        cache: false,
        data: {
            RowLimit: RowLimit,
            PageNo: PageNo,
            Filter: Filter,
            CategoryUIDs: SelectedUIDs,
            ModuleId: CategoryModuleId
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
            ? '<div class="col-auto text-muted" style="font-size:.82rem;">Showing <strong>1</strong> – <strong>' + Math.min(RowLimit, total) + '</strong> of <strong>' + total + '</strong> Results</div>'
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
    var _overlay = _prodPageRefreshing;
    _prodPageRefreshing = false;
    if (!_overlay) {
        ajaxLoading(0);
    }
    showTabSpinner(BrandTable, BrandPag);
    $.ajax({
        url: '/products/getBrandList',
        method: 'POST',
        cache: false,
        data: { RowLimit: RowLimit, PageNo: PageNo, Filter: Filter, [CsrfName]: CsrfToken },
        success: function (response) {
            ajaxLoading(1);
            $(BrandPag).css('visibility', '');
            if (response.Error) {
                $(BrandTable + ' tbody').html('');
                $(BrandPag).html('<div class="alert alert-danger" role="alert"><strong>' + response.Message + '</strong></div>');
            } else {
                $(BrandPag).html(response.Pagination);
                $(BrandTable + ' tbody').html(response.List);
                if (typeof response.TotalCount !== 'undefined') {
                    updateBrandCount(response.TotalCount);
                }
                $(window).trigger('scroll');
            }
        },
        error: function () {
            ajaxLoading(1);
            $(BrandPag).css('visibility', '');
        }
    });
}

/**
 * @param {FormData} formdata
 * @param {Function} onSuccess
 * @returns {void}
 */
function addBrandDetails(formdata, onSuccess) {
    $.ajax({
        url: '/products/addBrandDetails',
        method: 'POST',
        data: formdata,
        cache: false,
        processData: false,
        contentType: false,
        enctype: 'multipart/form-data',
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
    $.ajax({
        url: '/products/updateBrandDetails',
        method: 'POST',
        data: formdata,
        cache: false,
        processData: false,
        contentType: false,
        enctype: 'multipart/form-data',
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
        url: '/products/deleteBrandDetails',
        method: 'POST',
        cache: false,
        data: {
            RowLimit: RowLimit,
            PageNo: PageNo,
            Filter: Filter,
            BrandUID: BrandUID,
            [CsrfName]: CsrfToken,
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
        url: '/products/deleteBulkBrand',
        method: 'POST',
        cache: false,
        data: {
            RowLimit: RowLimit,
            PageNo: PageNo,
            Filter: Filter,
            BrandUIDs: SelectedUIDs,
            [CsrfName]: CsrfToken,
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
        url: '/products/deleteBulkPriceList',
        method: 'POST',
        cache: false,
        data: {
            RowLimit: RowLimit,
            PageNo: PageNo,
            Filter: Filter,
            PriceListUIDs: SelectedUIDs.join(','),
            [CsrfName]: CsrfToken,
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
            ? '<div class="col-auto text-muted" style="font-size:.82rem;">Showing <strong>1</strong> – <strong>' + Math.min(RowLimit, total) + '</strong> of <strong>' + total + '</strong> Results</div>'
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

function commonExportFunctionality(Flag, Type, PageType) {
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

    let URLs = '';
    let TableName;
    let TableHeader;
    let TableRow;
    let ItemIds;
    let FileName;
    let SheetName;
    let previewName;
    if (ActiveTabId == 'Item') {
        TableName = ProdTable;
        TableHeader = ProdHeader;
        TableRow = ProdRow;
        FileName = 'Product_Data';
        SheetName = 'Item';
        previewName = 'Product Details';
    } else if (ActiveTabId == 'Categories') {
        TableName = CatgTable;
        TableHeader = CatgHeader;
        TableRow = CatgRow;
        FileName = 'Category_Data';
        SheetName = 'Category';
        previewName = 'Category Details';
    }

    let ExportIds = '';
    if (PageType == 'SelectedPage') {
        ExportIds = SelectedUIDs.length > 0 ? btoa(SelectedUIDs.toString()) : '';
    } else if (PageType == 'CurrentPage') {
        let CurrentPageIds = [];
        $(TableName + ' tbody ' + TableRow).each(function () {
            let currentVal = parseInt($(this).val());
            if (!CurrentPageIds.includes(currentVal)) {
                CurrentPageIds.push(currentVal);
            }
        });
        ExportIds = CurrentPageIds.length > 0 ? btoa(CurrentPageIds.toString()) : '';
    }

    if (Type == 'PrintPreview') {
        URLs = "/globally/getPrintPreviewDetails?ModuleId=" + ActiveTabModuleId+ "&previewName="+previewName;
        if (!$.isEmptyObject(Filter)) {
            URLs += "&Filter=" + encodeURIComponent(JSON.stringify(Filter));
        }
        if (ExportIds != '') {
            URLs += "&ExportIds=" + ExportIds;
        }
    } else if (Type == 'ExportCSV' || Type == 'ExportPDF' || Type == 'ExportExcel') {
        const TypeVal = (Type == 'ExportCSV') ? 'CSV' : ((Type == 'ExportPDF') ? 'Pdf' : ((Type == 'ExportExcel') ? 'Excel' : 'None'));
        URLs = "/globally/exportModuleDataDetails?ModuleId=" + ActiveTabModuleId + "&Type=" + TypeVal + "&FileName=" + FileName + "&SheetName=" + SheetName;
        if (!$.isEmptyObject(Filter)) {
            URLs += "&Filter=" + encodeURIComponent(JSON.stringify(Filter));
        }
        if (ExportIds != '') {
            URLs += "&ExportIds=" + ExportIds;
        }
    }
    console.log(URLs)
    if (Flag == 1) {
        exportAllActions(ActiveTabModuleId, Type, URLs, function () {
            exportModalCloseFunc(TableName, TableHeader, TableRow);
        });
    } else if (Flag == 2) {
        if (Type == 'PrintPreview') {
            printPreviewRecords(URLs, function () {
                exportModalCloseFunc(TableName, TableHeader, TableRow);
            });
        } else if (Type == 'ExportCSV' || 'ExportPDF' || 'ExportExcel') {
            window.location.href = URLs;
            exportModalCloseFunc(TableName, TableHeader, TableRow);
        }
    }
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
        url: '/storage/getAllStorage/',
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

function _attachLoadExisting(entityType, entityUID) {
    if (!entityUID) return;
    $.get('/products/getAttachments', { EntityType: entityType, EntityUID: entityUID }, function(resp) {
        if (resp && !resp.Error) { _attachState[entityType].existing = resp.Attachments||[]; _attachRender(entityType); }
    });
}


