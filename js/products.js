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
                showAlertMessageSwal('error', '', response.Message);
            } else {
                showToastNotification(response.Message, 'success');
                executeProdPagnFunc(response, true, true);
            }
        }
    });
}

function getProductDetails(PageNo, RowLimit, Filter) {
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
            if (response.Error) {
                $(ProdTable + ' tbody').html('');
                $(ProdPag).html('<div class="alert alert-danger" role="alert"><strong>' + response.Message + '</strong></div>');
            } else {
                $(ProdPag).html(response.Pagination);
                $(ProdTable + ' tbody').html(response.List);
                if (typeof response.TotalCount !== 'undefined') {
                    updateProductCount(response.TotalCount);
                }
            }
            executeProdPagnFunc(response, false);
        },
    });
}

function getGroupDetails(PageNo, RowLimit, Filter) {
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
    });
}

function _prodPageSaveSuccess(response) {
    executeProdPagnFunc(response, true, true);
}
_prodPageSaveSuccess._needsList = true;

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
                Swal.fire(response.Message, "", "error");
            } else {
                if (SelectedUIDs.length > 0) {
                    SelectedUIDs = SelectedUIDs.filter(function (item) {
                        return item !== ProductUID;
                    });
                }
                executeProdPagnFunc(response, true);
            }
        },
    });
}

function deleteMultipleProduct() {
    $.ajax({
        url: '/products/deleteBulkProduct',
        method: "POST",
        cache: false,
        data: {
            RowLimit    : RowLimit,
            PageNo      : PageNo,
            Filter      : Filter,
            ProductUIDs : SelectedUIDs,
            IsComposite : ActiveTabId === 'Groups' ? 1 : 0,
            ModuleId    : ItemModuleId,
            [CsrfName]  : CsrfToken,
        },
        success: function (response) {
            if (response.Error) {
                Swal.fire(response.Message, "", "error");
            } else {
                SelectedUIDs = [];
                executeProdPagnFunc(response, true);
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
    if ($badge.length) { $badge.text(count); }
}
function updateGroupCount(count) {
    var $badge = $('#groupTotalCount');
    if ($badge.length) { $badge.text(count).removeClass('d-none'); }
}
function updateCategoryCount(count) {
    var $badge = $('#categoryTotalCount');
    if ($badge.length) { $badge.text(count).removeClass('d-none'); }
}
function updateSizeCount(count) {
    var $badge = $('#sizeTotalCount');
    if ($badge.length) { $badge.text(count).removeClass('d-none'); }
}
function updateBrandCount(count) {
    var $badge = $('#brandTotalCount');
    if ($badge.length) { $badge.text(count).removeClass('d-none'); }
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
function getCategoriesDetails(PageNo, RowLimit, Filter) {
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
            if (response.Error) {
                $(CatgTable + ' tbody').html('');
                $(CatgPag).html('<div class="alert alert-danger" role="alert"><strong>' + response.Message + '</strong></div>');
            } else {
                var total = response.TotalCount || 0;
                var from  = Math.min((PageNo - 1) * RowLimit + 1, total);
                var to    = Math.min(PageNo * RowLimit, total);
                var showingHtml = total > 0
                    ? '<div class="col-auto text-muted" style="font-size:.82rem;">Showing <strong>' + from + '</strong> – <strong>' + to + '</strong> of <strong>' + total + '</strong> Results</div>'
                    : '';
                $(CatgPag).html(showingHtml + '<div class="col-auto">' + response.Pagination + '</div>');
                $(CatgTable + ' tbody').html(response.List);
                if (typeof response.TotalCount !== 'undefined') {
                    updateCategoryCount(response.TotalCount);
                }
            }
            executeCatgPagnFunc(response, false);
            // Refresh sticky pagination bar after content loads
            $(window).trigger('scroll');
        },
    });
}

function addCategoryDetails(formdata, onSuccess) {
    formdata.append('returnList', 1);
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
                $('.catgFormAlert').removeClass('d-none');
                inlineMessageAlert('.catgFormAlert', 'danger', response.Message, false, false);
                $('#CatgSaveButton').prop('disabled', false).text('Save');
            } else {
                $('#categoryForm').trigger('reset');
                if (!$('#categoryModal').data('calledFromItemForm')) {
                    executeCatgPagnFunc(response, true);
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
                Swal.fire({icon: "error", title: "Oops...", text: response.Message});
            } else {

                $('#categoryForm').trigger('reset');
                $('#CatgModalTitle').text('Edit Category');
                $('.CatgSaveButton').text('Update');
                myTwoDropzone.removeAllFiles(true);
                $('#categoryModal').modal('show');

                $('#CategoryUID').val(response.Data.CategoryUID);
                $('#CategoryName').val(response.Data.Name);
                $('#CategoryDescription').val(response.Data.Description);
                if (hasValue(response.Data.Image)) {
                    var ImageUrl = CDN_URL + response.Data.Image;
                    commonSetDropzoneImageTwo(ImageUrl);
                }

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
                $('.catgFormAlert').removeClass('d-none');
                inlineMessageAlert('.catgFormAlert', 'danger', response.Message, false, false);
                $('#CatgSaveButton').prop('disabled', false).text('Update');
            } else {
                $('#categoryForm').trigger('reset');
                executeCatgPagnFunc(response, true);
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
                executeCatgPagnFunc(response, true);
                var formObj = {};
                formObj.UpdateId = [CategoryUID]
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
                Swal.fire(response.Message, "", "error");
            } else {
                var formObj = {};
                formObj.UpdateId = SelectedUIDs;
                updateCategoryOptions(formObj, 'delete');
                SelectedUIDs = [];
                showToastNotification(response.Message, 'success');
                executeCatgPagnFunc(response, true);
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

/**
 * @param {*} PageNo
 * @param {*} RowLimit
 * @param {*} Filter
 */
function getSizesDetails(PageNo, RowLimit, Filter) {
    $.ajax({
        url: '/products/getSizeList',
        method: "POST",
        cache: false,
        data: {
            RowLimit: RowLimit,
            PageNo: PageNo,
            Filter: Filter,
        },
        success: function (response) {
            if (response.Error) {
                $(SizeTable + ' tbody').html('');
                $(SizePag).html('<div class="alert alert-danger" role="alert"><strong>' + response.Message + '</strong></div>');
            } else {
                $(SizePag).html(response.Pagination);
                $(SizeTable + ' tbody').html(response.List);
                if (typeof response.TotalCount !== 'undefined') {
                    updateSizeCount(response.TotalCount);
                }
            }
            executeSizePagnFunc(response, false);
        },
    });

}

function addSizeDetails(formdata) {
    $.ajax({
        url: '/products/addSizeDetails',
        method: 'POST',
        data: formdata,
        cache: false,
        processData: false,
        contentType: false,
        success: function (response) {
            if (response.Error) {
                $('.sizeFormAlert').removeClass('d-none');
                inlineMessageAlert('.sizeFormAlert', 'danger', response.Message, false, false);
            } else {
                $('#SizesForm').trigger('reset');
                $('#sizesModal').modal('hide');
                executeSizePagnFunc(response, true);
                if(response.InsertId) {
                    var formObj = {};
                    formObj.InsertId = response.InsertId;
                    formObj.SizesName = formdata.get('SizesName');
                    updateSizeOptions(formObj, 'insert');
                }
            }
        }
    });
}

function retrieveSizeDetails(SizeUID) {
    $.ajax({
        url: '/products/retrieveSizeDetails',
        method: "POST",
        cache: false,
        data: {
            SizeUID: SizeUID,
        },
        success: function (response) {
            if (response.Error) {
                Swal.fire({icon: "error", title: "Oops...", text: response.Message});
            } else {

                $('#SizesForm').trigger('reset');
                $('#SizeModalTitle').text('Edit Size');
                $('#sizeButtonName').text('Update');
                $('#sizesModal').modal('show');

                $('#HSizeUID').val(response.Data.SizeUID);
                $('#SizesName').val(response.Data.Name);
                $('#SizesDescription').val(response.Data.Description);

            }
        },
    });
}

function editSizeDetails(formdata) {
    $.ajax({
        url: '/products/updateSizeDetails',
        method: 'POST',
        data: formdata,
        cache: false,
        processData: false,
        contentType: false,
        success: function (response) {
            if (response.Error) {
                $('.sizeFormAlert').removeClass('d-none');
                inlineMessageAlert('.sizeFormAlert', 'danger', response.Message, false, false);
            } else {
                $('#SizesForm').trigger('reset');
                $('#sizesModal').modal('hide');
                executeSizePagnFunc(response, true);
                if(formdata.get('HSizeUID')) {
                    var formObj = {};
                    formObj.UpdateId = formdata.get('HSizeUID');
                    formObj.SizesName = formdata.get('SizesName');
                    updateSizeOptions(formObj, 'update');
                }
            }
        }
    });
}

function deleteSize(SizeUID) {
    $.ajax({
        url: '/products/deleteSizeDetails',
        method: "POST",
        cache: false,
        data: {
            RowLimit: RowLimit,
            PageNo: PageNo,
            Filter: Filter,
            SizeUID: SizeUID,
            ModuleId: SizeModuleId,
        },
        success: function (response) {
            if (response.Error) {
                Swal.fire(response.Message, "", "error");
            } else {
                if (SelectedUIDs.length > 0) {
                    SelectedUIDs = SelectedUIDs.filter(function (item) {
                        return item !== SizeUID;
                    });
                }
                executeSizePagnFunc(response, true);
                var formObj = {};
                formObj.UpdateId = [SizeUID]
                updateSizeOptions(formObj, 'delete');
            }
        },
    });
}

function deleteMultipleSize() {
    $.ajax({
        url: '/products/deleteBulkSize',
        method: "POST",
        cache: false,
        data: {
            RowLimit: RowLimit,
            PageNo: PageNo,
            Filter: Filter,
            SizeUIDs: SelectedUIDs,
            ModuleId: SizeModuleId
        },
        success: function (response) {
            if (response.Error) {
                Swal.fire(response.Message, "", "error");
            } else {
                var formObj = {};
                formObj.UpdateId = SelectedUIDs;
                updateSizeOptions(formObj, 'delete');
                SelectedUIDs = [];
                executeSizePagnFunc(response, true);
            }
        },
    });
}

function executeSizePagnFunc(response, tableinfo = false) {
    if (tableinfo) {
        $(SizePag).html(response.Pagination);
        $(SizeTable + ' tbody').html(response.List);
        if (typeof response.TotalCount !== 'undefined') {
            updateSizeCount(response.TotalCount);
        }
        showToastNotification(response.Message, 'success');
    }
    headerCheckboxTrueFalse(SizeTable, SizeHeader, SizeRow);
    MultipleDeleteOption();
}

/**
 * @param {*} PageNo
 * @param {*} RowLimit
 * @param {*} Filter
 */
function getBrandsDetails(PageNo, RowLimit, Filter) {
    $.ajax({
        url: '/products/getBrandList',
        method: "POST",
        cache: false,
        data: {
            RowLimit: RowLimit,
            PageNo: PageNo,
            Filter: Filter,
        },
        success: function (response) {
            if (response.Error) {
                $(BrandTable + ' tbody').html('');
                $(BrandPag).html('<div class="alert alert-danger" role="alert"><strong>' + response.Message + '</strong></div>');
            } else {
                $(BrandPag).html(response.Pagination);
                $(BrandTable + ' tbody').html(response.List);
                if (typeof response.TotalCount !== 'undefined') {
                    updateBrandCount(response.TotalCount);
                }
            }
            executeBrandPagnFunc(response, false);
        },
    });
}

function addBrandDetails(formdata) {
    $.ajax({
        url: '/products/addBrandDetails',
        method: 'POST',
        data: formdata,
        cache: false,
        processData: false,
        contentType: false,
        success: function (response) {
            if (response.Error) {
                $('.brandFormAlert').removeClass('d-none');
                inlineMessageAlert('.brandFormAlert', 'danger', response.Message, false, false);
            } else {
                $('#BrandsForm').trigger('reset');
                $('#brandsModal').modal('hide');
                executeBrandPagnFunc(response, true);
                if(response.InsertId) {
                    var formObj = {};
                    formObj.InsertId = response.InsertId;
                    formObj.SizesName = formdata.get('BrandsName');
                    updateBrandOptions(formObj, 'insert');
                }
            }
        }
    });
}

function retrieveBrandDetails(BrandUID) {
    $.ajax({
        url: '/products/retrieveBrandDetails',
        method: "POST",
        cache: false,
        data: {
            BrandUID: BrandUID,
        },
        success: function (response) {
            if (response.Error) {
                Swal.fire({icon: "error", title: "Oops...", text: response.Message});
            } else {

                $('#BrandsForm').trigger('reset');
                $('#BrandsModalTitle').text('Edit Brand');
                $('#brandButtonName').text('Update');
                $('#brandsModal').modal('show');

                $('#HBrandUID').val(response.Data.BrandUID);
                $('#BrandsName').val(response.Data.Name);
                $('#BrandsDescription').val(response.Data.Description);

            }
        }
    });
}

function editBrandDetails(formdata) {
    $.ajax({
        url: '/products/updateBrandDetails',
        method: 'POST',
        data: formdata,
        cache: false,
        processData: false,
        contentType: false,
        success: function (response) {
            if (response.Error) {
                $('.brandFormAlert').removeClass('d-none');
                inlineMessageAlert('.brandFormAlert', 'danger', response.Message, false, false);
            } else {
                $('#BrandsForm').trigger('reset');
                $('#brandsModal').modal('hide');
                executeBrandPagnFunc(response, true);
                if(formdata.get('HBrandUID')) {
                    var formObj = {};
                    formObj.UpdateId = formdata.get('HBrandUID');
                    formObj.BrandsName = formdata.get('BrandsName');
                    updateBrandOptions(formObj, 'update');
                }
            }
        }
    });
}

function deleteBrand(BrandUID) {
    $.ajax({
        url: '/products/deleteBrandDetails',
        method: "POST",
        cache: false,
        data: {
            RowLimit: RowLimit,
            PageNo: PageNo,
            Filter: Filter,
            BrandUID: BrandUID,
            ModuleId: BrandModuleId
        },
        success: function (response) {
            if (response.Error) {
                Swal.fire(response.Message, "", "error");
            } else {
                if (SelectedUIDs.length > 0) {
                    SelectedUIDs = SelectedUIDs.filter(function (item) {
                        return item !== BrandUID;
                    });
                }
                executeBrandPagnFunc(response, true);
                var formObj = {};
                formObj.UpdateId = [BrandUID]
                updateBrandOptions(formObj, 'delete');
            }
        },
    });
}

function deleteMultipleBrand() {
    $.ajax({
        url: '/products/deleteBulkBrand',
        method: "POST",
        cache: false,
        data: {
            RowLimit: RowLimit,
            PageNo: PageNo,
            Filter: Filter,
            BrandUIDs: SelectedUIDs,
            ModuleId: BrandModuleId
        },
        success: function (response) {
            if (response.Error) {
                Swal.fire(response.Message, "", "error");
            } else {
                var formObj = {};
                formObj.UpdateId = SelectedUIDs;
                updateBrandOptions(formObj, 'delete');
                SelectedUIDs = [];
                executeBrandPagnFunc(response, true);
            }
        },
    });
}

function executeBrandPagnFunc(response, tableinfo = false) {
    if (tableinfo) {
        $(BrandPag).html(response.Pagination);
        $(BrandTable + ' tbody').html(response.List);
        if (typeof response.TotalCount !== 'undefined') {
            updateBrandCount(response.TotalCount);
        }
        showToastNotification(response.Message, 'success');
    }
    headerCheckboxTrueFalse(BrandTable, BrandHeader, BrandRow);
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
    } else if (ActiveTabId == 'Sizes') {
        if (PageSelcType == 'AllPage') {
            CopyAllDatatoSelectItems(SizeUIDs);
        }
        selectTableRecords(SizeTable, SizeRow);
        headerCheckboxTrueFalse(SizeUIDs, SizeHeader);
    } else if (ActiveTabId == 'Brands') {
        if (PageSelcType == 'AllPage') {
            CopyAllDatatoSelectItems(BrandUIDs);
        }
        selectTableRecords(BrandTable, BrandRow);
        headerCheckboxTrueFalse(BrandUIDs, BrandHeader);
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
    } else if (ActiveTabId == 'Sizes') {
        if (PageSelcType == 'AllPage') {
            removeAllDatatoSelectItems(SizeUIDs);
        }
        unSelectTableRecords(SizeTable, SizeRow);
        headerCheckboxTrueFalse(SizeUIDs, SizeHeader);
    } else if (ActiveTabId == 'Brands') {
        if (PageSelcType == 'AllPage') {
            removeAllDatatoSelectItems(BrandUIDs);
        }
        unSelectTableRecords(BrandTable, BrandRow);
        headerCheckboxTrueFalse(BrandUIDs, BrandHeader);
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
    } else if (ActiveTabId == 'Sizes') {
        TableName = SizeTable;
        TableHeader = SizeHeader;
        TableRow = SizeRow;
        FileName = 'Size_Data';
        SheetName = 'Size';
        previewName = 'Size Details';
    } else if (ActiveTabId == 'Brands') {
        TableName = BrandTable;
        TableHeader = BrandHeader;
        TableRow = BrandRow;
        FileName = 'Brand_Data';
        SheetName = 'Brand';
        previewName = 'Brand Details';
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
    } else if (ActiveTabId == 'Sizes') {
        $('#SizesForm').trigger('reset');
        $('#SizeModalTitle').text('Add Size');
        $('.sizeButtonName').text('Save');
        $('#sizesModal').find('#HSizeUID').val(0);
    } else if (ActiveTabId == 'Brands') {
        $('#BrandsForm').trigger('reset');
        $('#BrandsModalTitle').text('Add Brand');
        $('.brandButtonName').text('Save');
        $('#brandsModal').find('#HBrandUID').val(0);
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
    } else if (ActiveTabId == 'Sizes') {
        getSizesDetails(PageNo, RowLimit, Filter);
    } else if (ActiveTabId == 'Brands') {
        getBrandsDetails(PageNo, RowLimit, Filter);
    }
}


function commonExportFunctions() {
    $('#btnExportPrint').click(function (e) {
        e.preventDefault();
        commonExportFunctionality(1, 'PrintPreview', 'ExportAll');
    });

    $('#btnExportCSV').click(function (e) {
        e.preventDefault();
        commonExportFunctionality(1, 'ExportCSV', 'ExportAll');
    });

    $('#btnExportPDF').click(function (e) {
        e.preventDefault();
        commonExportFunctionality(1, 'ExportPDF', 'ExportAll');
    });

    $('#btnExportExcel').click(function (e) {
        e.preventDefault();
        commonExportFunctionality(1, 'ExportExcel', 'ExportAll');
    });

    $('#exportSelectedItemsBtn').click(function (e) {
        e.preventDefault();
        commonExportFunctionality(2, expActionType, 'SelectedPage');
    });

    $('#exportThisPageBtn').click(function (e) {
        e.preventDefault();
        commonExportFunctionality(2, expActionType, 'CurrentPage');
    });

    $('#exportAllPagesBtn').click(function (e) {
        e.preventDefault();
        commonExportFunctionality(2, expActionType, 'AllPage');
    });

    $('#clearExportClose').click(function (e) {
        e.preventDefault();
        if (ActiveTabId == 'Item') {
            exportModalCloseFunc(ProdTable, ProdHeader, ProdRow, ItemUIDs);
        } else if (ActiveTabId == 'Groups') {
            exportModalCloseFunc(GroupTable, GroupHeader, ProdRow, ItemUIDs);
        } else if (ActiveTabId == 'Categories') {
            exportModalCloseFunc(CatgTable, CatgHeader, CatgRow, CategoryUIDs);
        } else if (ActiveTabId == 'Sizes') {
            exportModalCloseFunc(SizeTable, SizeHeader, SizeRow, SizeUIDs);
        } else if (ActiveTabId == 'Brands') {
            exportModalCloseFunc(BrandTable, BrandHeader, BrandRow, BrandUIDs);
        }
    });

    $('#selectThisPageBtn').click(function (e) {
        e.preventDefault();
        commonSelectFunctionality('CurrentPage');
    });

    $('#selectAllPagesBtn').click(function (e) {
        e.preventDefault();
        commonSelectFunctionality('AllPage');
    });

    $('#clearSelectAllClose').click(function (e) {
        e.preventDefault();
        if (ActiveTabId == 'Item') {
            selectModalCloseFunc(ProdTable, ProdHeader, ProdRow, ItemUIDs);
        } else if (ActiveTabId == 'Groups') {
            selectModalCloseFunc(GroupTable, GroupHeader, ProdRow, ItemUIDs);
        } else if (ActiveTabId == 'Categories') {
            selectModalCloseFunc(CatgTable, CatgHeader, CatgRow, CategoryUIDs);
        } else if (ActiveTabId == 'Sizes') {
            selectModalCloseFunc(SizeTable, SizeHeader, SizeRow, SizeUIDs);
        } else if (ActiveTabId == 'Brands') {
            selectModalCloseFunc(BrandTable, BrandHeader, BrandRow, BrandUIDs);
        }
    });

    $('#unselectThisPageBtn').click(function (e) {
        e.preventDefault();
        commonUnSelectFunctionality('CurrentPage');
    });

    $('#unselectAllPagesBtn').click(function (e) {
        e.preventDefault();
        commonUnSelectFunctionality('AllPage');
    });

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

function updateSizeOptions(fields, type) {
    if(type == 'insert') {
        $('#AddEditItemForm #PSizeUID').append(`<option value="${fields.InsertId}">${fields.SizesName}</option>`);
    } else if(type == 'update') {
        var idStr = String(fields.UpdateId).trim();
        $("#AddEditItemForm #PSizeUID option[value='"+idStr+"']").text(fields.SizesName);
    } else if(type == 'delete') {
        $.each(fields.UpdateId, function(index, id) {
            $('#AddEditItemForm #PSizeUID option[value="' + id + '"]').remove();
        });
    }
}

function setSizeOption(SizeList) {
    $('#AddEditItemForm #PSizeUID').empty();
    $('#AddEditItemForm #PSizeUID').append('<option label="-- Select Size --"></option>');
    SizeList.forEach(size => {
        $('#AddEditItemForm #PSizeUID').append(`<option value="${size.SizeUID}">${size.Name}</option>`);
    });
}

function updateBrandOptions(fields, type) {
    if(type == 'insert') {
        $('#AddEditItemForm #BrandUID').append(`<option value="${fields.InsertId}">${fields.BrandsName}</option>`);
    } else if(type == 'update') {
        var idStr = String(fields.UpdateId).trim();
        $("#AddEditItemForm #BrandUID option[value='"+idStr+"']").text(fields.BrandsName);
    } else if(type == 'delete') {
        $.each(fields.UpdateId, function(index, id) {
            $('#AddEditItemForm #BrandUID option[value="' + id + '"]').remove();
        });
    }
}

function setBrandOption(BrandList) {
    $('#AddEditItemForm #BrandUID').empty();
    $('#AddEditItemForm #BrandUID').append('<option label="-- Select Brand --"></option>');
    BrandList.forEach(brand => {
        $('#AddEditItemForm #BrandUID').append(`<option value="${brand.BrandUID}">${brand.Name}</option>`);
    });
}

function refreshSearchStorage($this) {
    $('#storageFilterBox').show();
    AjaxLoading = 0;
    $('#storageFilterBox').html('<div class="d-flex justify-content-center align-items-center p-3"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>');
    $.ajax({
        url: '/storage/getAllStorage/',
        method: "POST",
        cache: false,
        success: function (response) {
            AjaxLoading = 1;
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


