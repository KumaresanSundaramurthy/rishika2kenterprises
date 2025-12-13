/**
 * @param {*} PageNo
 * @param {*} RowLimit
 * @param {*} Filter
 */
function getProductDetails(PageNo, RowLimit, Filter) {
    $.ajax({
        url: '/globally/getModPageDataDetails/' + PageNo,
        method: "POST",
        cache: false,
        data: {
            ModuleId: ItemModuleId,
            RowLimit: RowLimit,
            PageNo: PageNo,
            Filter: Filter,
        },
        success: function (response) {
            if (response.Error) {
                $(ProdTable + ' tbody').html('');
                $(ProdPag).html('<div class="alert alert-danger" role="alert"><strong>' + response.Message + '</strong></div>');
            } else {
                $(ProdPag).html(response.Pagination);
                $(ProdTable + ' tbody').html(response.List);
            }
            executeProdPagnFunc(response, false);
        },
    });
}

function addProductData(formdata) {
    $.ajax({
        url: '/products/addProductData',
        method: 'POST',
        data: formdata,
        cache: false,
        processData: false,
        contentType: false,
        enctype: 'multipart/form-data',
        success: function (response) {
            if (response.Error) {
                $('.addEditFormAlert').removeClass('d-none');
                inlineMessageAlert('.addEditFormAlert', 'danger', response.Message, false, false);
            } else {
                myOneDropzone.removeAllFiles(true);
                quill.setContents([]);
                $('#AddEditItemForm').trigger('reset');
                $('#itemsModal').modal('hide');
                clearItemValues();
                executeProdPagnFunc(response, true);
            }
        }
    });
}

function retrieveProductDetails(ItemUID, CloneFlag = false) {
    $.ajax({
        url: '/products/retrieveProductDetails',
        method: "POST",
        cache: false,
        data: {
            ItemUID: ItemUID,
        },
        success: function (response) {
            if (response.Error) {
                Swal.fire({
                    icon: "error",
                    title: "Oops...",
                    text: response.Message,
                });
            } else {

                $('#AddEditItemForm').trigger('reset');
                if (CloneFlag) {
                    $('#ItemModalTitle').text('Create Item');
                    $('.AddEditProductBtn').text('Save');
                } else {
                    $('#ItemModalTitle').text('Edit Item');
                    $('.AddEditProductBtn').text('Update');
                }
                clearItemValues();
                $('#itemsModal').modal('show');

                if (CloneFlag) {
                    $('#HProductUID').val(0);
                } else {
                    $('#HProductUID').val(response.Data.ProductUID);
                }

                $('#ItemName').val(response.Data.ItemName);
                $('#ProductType').val(response.Data.ProductType);
                $('#SellingPrice').val(smartDecimal(response.Data.SellingPrice));
                $('#SellingTaxOption').val(response.Data.SellingProductTaxUID).trigger('change');
                $('#TaxPercentage').val(response.Data.TaxDetailsUID).trigger('change');
                $('#PrimaryUnit').val(response.Data.PrimaryUnitUID).trigger('change');
                $('#Category').val(response.Data.CategoryUID).trigger('change');
                $('#PurchasePrice').val(smartDecimal(response.Data.PurchasePrice));
                $('#PurchaseTaxOption').val(response.Data.PurchasePriceProductTaxUID).trigger('change');
                if (EnableStorage) {
                    $('#StorageUID').val(response.Data.StorageUID).trigger('change');
                }

                $('#HSNCode').val(response.Data.HSNSACCode);
                $('#Standard').val(response.Data.Standard);
                $('#BrandUID').val(response.Data.BrandUID).trigger('change');
                $('#Model').val(response.Data.Model);
                $('#PartNumber').val(response.Data.PartNumber);
                if (response.Data.IsSizeApplicable == 1) {
                    $('#IsSizeApplicable').prop('checked', true).trigger('change');
                    $('#SizeDiv').removeClass('d-none');
                    $('#PSizeUID').val(response.Data.SizeUID).trigger('change').prop('required', true);
                }
                
                if (hasValue(response.Data.Image)) {
                    var ImageUrl = CDN_URL + response.Data.Image;
                    commonSetDropzoneImageOne(ImageUrl);
                    imgData = ImageUrl;
                }
                if (response.Data.Description != null && response.Data.Description != undefined) {
                    appendToQuill(response.Data.Description, true);
                }

                $('#OpeningQuantity').val(smartDecimal(response.Data.OpeningQuantity));
                $('#OpeningPurchasePrice').val(smartDecimal(response.Data.OpeningPurchasePrice));
                $('#OpeningStockValue').val(smartDecimal(response.Data.OpeningStockValue));

                $('#Discount').val(smartDecimal(response.Data.Discount));
                $('#DiscountOption').val(response.Data.DiscountTypeUID).trigger('change');
                $('#LowStockAlert').val(smartDecimal(response.Data.LowStockAlertAt));
                if (response.Data.NotForSale == 'Yes') {
                    $('#NotForSale').prop('checked', true);
                }

            }
        },
    });
}

function editProductData(formdata) {
    $.ajax({
        url: '/products/updateProductData',
        method: 'POST',
        data: formdata,
        cache: false,
        processData: false,
        contentType: false,
        enctype: 'multipart/form-data',
        success: function (response) {
            if (response.Error) {
                $('.addEditFormAlert').removeClass('d-none');
                inlineMessageAlert('.addEditFormAlert', 'danger', response.Message, false, false);
            } else {
                myOneDropzone.removeAllFiles(true);
                quill.setContents([]);
                $('#AddEditItemForm').trigger('reset');
                $('#itemsModal').modal('hide');
                clearItemValues();
                executeProdPagnFunc(response, true);
            }
        }
    });
}

function deleteProduct(ProductUID) {
    $.ajax({
        url: '/products/deleteProductDetails',
        method: "POST",
        cache: false,
        data: {
            RowLimit: RowLimit,
            PageNo: PageNo,
            Filter: Filter,
            ProductUID: ProductUID,
            ModuleId: ItemModuleId
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
            RowLimit: RowLimit,
            PageNo: PageNo,
            Filter: Filter,
            ProductUIDs: SelectedUIDs,
            ModuleId: ItemModuleId
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

function executeProdPagnFunc(response, tableinfo = false) {
    if (tableinfo) {
        $(ProdPag).html(response.Pagination);
        $(ProdTable + ' tbody').html(response.List);
        Swal.fire(response.Message, "", "success");
    }
    headerCheckboxTrueFalse(ProdTable, ProdHeader, ProdRow);
    MultipleDeleteOption();
}

/**
 * @param {*} PageNo
 * @param {*} RowLimit
 * @param {*} Filter
 */
function getCategoriesDetails(PageNo, RowLimit, Filter) {
    $.ajax({
        url: '/globally/getModPageDataDetails/' + PageNo,
        method: "POST",
        cache: false,
        data: {
            RowLimit: RowLimit,
            PageNo: PageNo,
            Filter: Filter,
            ModuleId: CategoryModuleId
        },
        success: function (response) {
            if (response.Error) {
                $(CatgTable + ' tbody').html('');
                $(CatgPag).html('<div class="alert alert-danger" role="alert"><strong>' + response.Message + '</strong></div>');
            } else {
                $(CatgPag).html(response.Pagination);
                $(CatgTable + ' tbody').html(response.List);
            }
            executeCatgPagnFunc(response, false);
        },
    });
}

function addCategoryDetails(formdata) {
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
            } else {
                myTwoDropzone.removeAllFiles(true);
                $('#categoryForm').trigger('reset');
                $('#categoryModal').modal('hide');
                executeCatgPagnFunc(response, true);
                if(response.InsertId) {
                    var formObj = {};
                    formObj.InsertId = response.InsertId;
                    formObj.CategoryName = formdata.get('CategoryName');
                    updateCategoryOptions(formObj, 'insert');
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

function editCategoryDetails(formdata) {
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
            } else {
                myTwoDropzone.removeAllFiles(true);
                $('#categoryForm').trigger('reset');
                $('#categoryModal').modal('hide');
                executeCatgPagnFunc(response, true);
                if(formdata.get('CategoryUID')) {
                    var formObj = {};
                    formObj.UpdateId = formdata.get('CategoryUID');
                    formObj.CategoryName = formdata.get('CategoryName');
                    updateCategoryOptions(formObj, 'update');
                }
            }
        }
    });
}

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
                Swal.fire(response.Message, "", "error");
            } else {
                if (SelectedUIDs.length > 0) {
                    SelectedUIDs = SelectedUIDs.filter(function (item) {
                        return item !== CategoryUID;
                    });
                }
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
                executeCatgPagnFunc(response, true);
            }
        },
    });
}

function executeCatgPagnFunc(response, tableinfo = false) {
    if (tableinfo) {
        $(CatgPag).html(response.Pagination);
        $(CatgTable + ' tbody').html(response.List);
        Swal.fire(response.Message, "", "success");
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
        url: '/globally/getModPageDataDetails/' + PageNo,
        method: "POST",
        cache: false,
        data: {
            RowLimit: RowLimit,
            PageNo: PageNo,
            Filter: Filter,
            ModuleId: SizeModuleId
        },
        success: function (response) {
            if (response.Error) {
                $(SizeTable + ' tbody').html('');
                $(SizePag).html('<div class="alert alert-danger" role="alert"><strong>' + response.Message + '</strong></div>');
            } else {
                $(SizePag).html(response.Pagination);
                $(SizeTable + ' tbody').html(response.List);
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
        Swal.fire(response.Message, "", "success");
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
        url: '/globally/getModPageDataDetails/' + PageNo,
        method: "POST",
        cache: false,
        data: {
            RowLimit: RowLimit,
            PageNo: PageNo,
            Filter: Filter,
            ModuleId: BrandModuleId
        },
        success: function (response) {
            if (response.Error) {
                $(BrandTable + ' tbody').html('');
                $(BrandPag).html('<div class="alert alert-danger" role="alert"><strong>' + response.Message + '</strong></div>');
            } else {
                $(BrandPag).html(response.Pagination);
                $(BrandTable + ' tbody').html(response.List);
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
        Swal.fire(response.Message, "", "success");
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
        $('#AddEditItemForm').trigger('reset');
        $('#ItemModalTitle').text('Create Item');
        $('.AddEditProductBtn').text('Save');
        $('#AddEditItemForm').find('#HProductUID').val(0);
        myOneDropzone.removeAllFiles(true);
    } else if (ActiveTabId == 'Categories') {
        $('#categoryForm').trigger('reset');
        $('#CatgModalTitle').text('Add Category');
        $('.CatgSaveButton').text('Save');
        $('#categoryForm').find('#CategoryUID').val(0);
        myTwoDropzone.removeAllFiles(true);
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
    clearItemValues();
}

function showProductPageDetails() {
    if (ActiveTabId == 'Item') {
        getProductDetails(PageNo, RowLimit, Filter);
    } else if (ActiveTabId == 'Categories') {
        getCategoriesDetails(PageNo, RowLimit, Filter);
    } else if (ActiveTabId == 'Sizes') {
        getSizesDetails(PageNo, RowLimit, Filter);
    } else if (ActiveTabId == 'Brands') {
        getBrandsDetails(PageNo, RowLimit, Filter);
    }
}

function clearItemValues() {
    $('#HProductUID').val(0);
    $('#ProductType').val('Product').trigger('change');
    $('#SellingTaxOption,#PurchaseTaxOption,#DiscountOption').val(1).trigger('change');
    $('#TaxPercentage,#PrimaryUnit,#Category,#StorageUID,#BrandUID,#PSizeUID').val(null).trigger('change');
    $('#IsSizeApplicable,#NotForSale').prop('checked', false).trigger('change');
    $('#SizeDiv').addClass('d-none');
    myOneDropzone.removeAllFiles(true);
    quill.setContents([]);
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

function toggleCategoryFilter() {
    $('#categoryFilterBox').toggle();
    if ($('#categoryFilterBox .category-checkbox').length == 0) {
        AjaxLoading = 0;
        $('#categoryFilterBox').html('<div class="d-flex justify-content-center align-items-center p-3"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>');
        $.ajax({
            url: '/products/getAllCategories/',
            method: "POST",
            cache: false,
            success: function (response) {
                AjaxLoading = 1;
                if (response.Error) {
                    $('#categoryFilterBox').html('');
                    $('#categoryFilterBox').html('<div class="alert alert-danger" role="alert"><strong>' + response.Message + '</strong></div>');
                } else {
                    $('#categoryFilterBox').html(response.HtmlData);
                }
            },
        });   
    }
}

function closeCategoryFilter() {
    $('#categoryFilterBox').hide();
}

function resetCategoryFilter() {
    $('.category-checkbox').prop('checked', false);
    $('#selectAllCategories').prop('checked', false);
}

function applyCategoryFilter() {
    delete Filter['Category'];
    let selectedCategoryIds = $('.category-checkbox:checked').map(function () {
        return $(this).val();
    }).get();
    if (selectedCategoryIds.length > 0) {
        Filter['Category'] = selectedCategoryIds;
    }
    $('#categoryFilterBox').hide();
    showProductPageDetails();
}

function toggleAllCategories(main) {
    var isChecked = $(main).prop('checked');
    $('.category-checkbox').prop('checked', isChecked);
    $('#selectAllLabel').text(isChecked ? 'Clear All' : 'Select All');
}

function updateCategoryOptions(fields, type) {
    if(type == 'insert') {
        $('#AddEditItemForm #Category').append(`<option value="${fields.InsertId}">${fields.CategoryName}</option>`);
        const categoryHtml = `
                <div class="form-check mb-2 my-1 list-hover-bg">
                <label class="form-check-label w-100 d-flex align-items-center">
                    <input class="form-check-input me-2 category-checkbox" type="checkbox" value="${fields.InsertId}">
                    <span>${fields.CategoryName}</span>
                </label>
                </div>`;
            $('#categoryList').append(categoryHtml);
    } else if(type == 'update') {
        var idStr = String(fields.UpdateId).trim();
        $("#AddEditItemForm #Category option[value='"+idStr+"']").text(fields.CategoryName);
        $("#ProductsTable #categoryList .category-checkbox[value='" + idStr + "']")
                .closest('label')
                .find('span:last')
                .text(fields.CategoryName);
    } else if(type == 'delete') {
        $.each(fields.UpdateId, function(index, id) {
            $('#AddEditItemForm #Category option[value="' + id + '"]').remove();
            $('#ProductsTable #categoryList .category-checkbox[value="' + id + '"]')
                .closest('.form-check')
                .remove();
        });
    }
}

function setFilterCategoryOption(CatgList) {
    $('#categoryList').empty();
    $('#AddEditItemForm #Category').empty();
    $('#AddEditItemForm #Category').append('<option label="-- Select Category --"></option>');
    CatgList.forEach(cat => {
        $('#AddEditItemForm #Category').append(`<option value="${cat.CategoryUID}">${cat.Name}</option>`);
        const categoryHtml = `
            <div class="form-check mb-2 my-1 list-hover-bg">
            <label class="form-check-label w-100 d-flex align-items-center">
                <input class="form-check-input me-2 category-checkbox" type="checkbox" value="${cat.CategoryUID}">
                <span>${cat.Name}</span>
            </label>
            </div>`;
        $('#categoryList').append(categoryHtml);
    });
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

function toggleStorageFilter() {
    $('#storageFilterBox').toggle();
    if ($('#storageFilterBox .storage-checkbox').length == 0) {
        
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
}

function closeStorageFilter() {
    $('#storageFilterBox').hide();
}

function resetStorageFilter() {
    $('.storage-checkbox').prop('checked', false);
    $('#selectAllStorage').prop('checked', false);
}

function applyStorageFilter() {
    delete Filter['Storage'];
    let selectedStorageIds = $('.storage-checkbox:checked').map(function () {
        return $(this).val();
    }).get();
    if (selectedStorageIds.length > 0) {
        Filter['Storage'] = selectedStorageIds;
    }
    $('#storageFilterBox').hide();
    showProductPageDetails();
}

function toggleAllStorage(main) {
    var isChecked = $(main).prop('checked');
    $('.storage-checkbox').prop('checked', isChecked);
    $('#str_selectAllLabel').text(isChecked ? 'Clear All' : 'Select All');
}

$('#ProductType').on('change', function (e) {
    e.preventDefault();
    var getVal = $(this).val();
    if (getVal == 'Product') {
        $('#OpeningStockDiv').removeClass('d-none');
    } else if (getVal == 'Service') {
        $('#OpeningStockDiv').addClass('d-none');
    }
});

$('#IsSizeApplicable').on('change', function () {
    $('#SizeDiv').addClass('d-none');
    $('#PSizeUID').removeAttr('required').val('').trigger('change');
    if ($(this).is(':checked')) {
        $('#SizeDiv').removeClass('d-none');
        $('#SizeDiv').attr('required', true);
        $('#PSizeUID').val('').trigger('change');
    }
});

$('#DiscountOption').change(function (e) {
    e.preventDefault();
    var value = $(this).val();
    if (value == 1) {
        $('#Discount').attr('placeholder', 'Enter Discount Percentage');
        var Discount = $('#Discount').val();
        if (Discount > 0 && Discount > 100) {
            $('#Discount').val(0);
        }
    } else if (value == 2) {
        $('#Discount').attr('placeholder', 'Enter Discount Amount');
    }
});

$('#SellingTaxOption').change(function (e) {
    e.preventDefault();
    var getVal = $(this).find('option:selected').val();
    if (getVal) {
        $('#SellingPriceTaxHelp,#SellingPriceWTaxHelp').addClass('d-none');
        if (getVal == '1') {
            $('#SellingPriceTaxHelp').removeClass('d-none');
        } else if (getVal == '2') {
            $('#SellingPriceWTaxHelp').removeClass('d-none');
        }
    }
});

function loadTaxDetailOptions() {
    $('#TaxPercentage').select2({
        placeholder: '-- Select Tax Percentage --',
        allowClear: false,
        width: 'resolve',
        templateResult: function (data) {
            if (!data.id) return data.text;
            const el = $(data.element);
            const left  = el.data('left');
            const right = el.data('right');
            return $('<div class="d-flex justify-content-between">' +
                    '<span class="fw-semibold">' + left + '</span>' +
                    '<span class="text-muted small">' + right + '</span>' +
                    '</div>');
        },
        templateSelection: function (data) {
            if (!data.id) return data.text;
            const el = $(data.element);
            const left  = el.data('left');
            const right = el.data('right');
            return $('<div class="d-flex justify-content-between">' +
                    '<span class="fw-semibold">' + left + '</span>' +
                    '<span class="text-muted small">' + right + '</span>' +
                    '</div>');
        },
        dropdownParent: '#itemsModal',
    });
}