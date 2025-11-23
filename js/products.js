/**
 * @param {*} PageNo
 * @param {*} RowLimit
 * @param {*} Filter
 */
function getProductDetails(PageNo, RowLimit, Filter) {
    $.ajax({
        url: '/products/getProductDetails/' + PageNo,
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
    $('.AddEditProductBtn').attr('disabled', 'disabled');
    $.ajax({
        url: '/products/addProductData',
        method: 'POST',
        data: formdata,
        cache: false,
        processData: false,
        contentType: false,
        enctype: 'multipart/form-data',
        success: function (response) {
            $('.AddEditProductBtn').removeAttr('disabled');
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
                    $('#ItemModalTitle').text('Add Item');
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
                if (response.Data.Image && response.Data.Image !== undefined && response.Data.Image !== null && response.Data.Image !== '') {
                    // var ImageUrl = CDN_URL + response.Data.Image;
                    var ImageUrl = response.Data.Image;
                    if (ImageUrl && ImageUrl !== undefined && ImageUrl !== null && ImageUrl !== '') {
                        commonSetDropzoneImageOne(ImageUrl);
                    }
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

    $('.AddEditProductBtn').attr('disabled', 'disabled');
    $.ajax({
        url: '/products/updateProductData',
        method: 'POST',
        data: formdata,
        cache: false,
        processData: false,
        contentType: false,
        enctype: 'multipart/form-data',
        success: function (response) {

            $('.AddEditProductBtn').removeAttr('disabled');
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

    ItemUIDs = response.UIDs ? response.UIDs : [];
    headerCheckboxTrueFalse(ItemUIDs, ProdHeader);
    tableCheckboxTrueFalse(SelectedUIDs, ProdTable, ProdRow);
    MultipleDeleteOption();

}

/**
 * @param {*} PageNo
 * @param {*} RowLimit
 * @param {*} Filter
 */
function getCategoriesDetails(PageNo, RowLimit, Filter) {
    $.ajax({
        url: '/products/getCategoriesDetails/' + PageNo,
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
    $('.CatgSaveButton').attr('disabled', 'disabled');
    $.ajax({
        url: '/products/addCategoryDetails',
        method: 'POST',
        data: formdata,
        cache: false,
        processData: false,
        contentType: false,
        enctype: 'multipart/form-data',
        success: function (response) {
            $('.CatgSaveButton').removeAttr('disabled');
            if (response.Error) {
                $('.catgFormAlert').removeClass('d-none');
                inlineMessageAlert('.catgFormAlert', 'danger', response.Message, false, false);
            } else {
                myOneDropzone.removeAllFiles(true);
                $('#categoryForm').trigger('reset');
                $('#categoryModal').modal('hide');

                executeCatgPagnFunc(response, true);
                setFilterCategoryOption(response.CatgList);

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
                Swal.fire({
                    icon: "error",
                    title: "Oops...",
                    text: response.Message,
                });
            } else {

                $('#categoryForm').trigger('reset');
                $('#CatgModalTitle').text('Edit Category');
                $('.CatgSaveButton').text('Update');
                myOneDropzone.removeAllFiles(true);
                $('#categoryModal').modal('show');

                $('#CategoryUID').val(response.Data.CategoryUID);
                $('#CategoryName').val(response.Data.Name);
                $('#CategoryDescription').val(response.Data.Description);

                if (response.Data.Image && response.Data.Image !== undefined && response.Data.Image !== null && response.Data.Image !== '') {
                    // var CatgImgURL = CDN_URL + response.Data.Image;
                    var ImageUrl = response.Data.Image;
                    if (ImageUrl && ImageUrl !== undefined && ImageUrl !== null && ImageUrl !== '') {
                        commonSetDropzoneImageOne(ImageUrl);
                    }
                }

            }
        },
    });
}

function editCategoryDetails(formdata) {
    $('.CatgSaveButton').attr('disabled', 'disabled');
    $.ajax({
        url: '/products/updateCategoryDetails',
        method: 'POST',
        data: formdata,
        cache: false,
        processData: false,
        contentType: false,
        enctype: 'multipart/form-data',
        success: function (response) {
            $('.CatgSaveButton').removeAttr('disabled');
            if (response.Error) {
                $('.catgFormAlert').removeClass('d-none');
                inlineMessageAlert('.catgFormAlert', 'danger', response.Message, false, false);
            } else {
                myOneDropzone.removeAllFiles(true);
                $('#categoryForm').trigger('reset');
                $('#categoryModal').modal('hide');

                executeCatgPagnFunc(response, true);
                setFilterCategoryOption(response.CatgList);
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
                setFilterCategoryOption(response.CatgList);
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
                SelectedUIDs = [];
                executeCatgPagnFunc(response, true);
                setFilterCategoryOption(response.CatgList);
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

    CategoryUIDs = response.UIDs ? response.UIDs : [];
    headerCheckboxTrueFalse(CategoryUIDs, CatgHeader);
    tableCheckboxTrueFalse(SelectedUIDs, CatgTable, CatgRow);
    MultipleDeleteOption();

}

/**
 * @param {*} PageNo
 * @param {*} RowLimit
 * @param {*} Filter
 */
function getSizesDetails(PageNo, RowLimit, Filter) {
    $.ajax({
        url: '/products/getSizesDetails/' + PageNo,
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
    $('.sizeButtonName').attr('disabled', 'disabled');
    $.ajax({
        url: '/products/addSizeDetails',
        method: 'POST',
        data: formdata,
        cache: false,
        processData: false,
        contentType: false,
        success: function (response) {
            $('.sizeButtonName').removeAttr('disabled');
            if (response.Error) {
                $('.sizeFormAlert').removeClass('d-none');
                inlineMessageAlert('.sizeFormAlert', 'danger', response.Message, false, false);
            } else {
                $('#SizesForm').trigger('reset');
                $('#sizesModal').modal('hide');
                executeSizePagnFunc(response, true);
                setSizeOption(response.SizeList);
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
                Swal.fire({
                    icon: "error",
                    title: "Oops...",
                    text: response.Message,
                });
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
    $('.sizeButtonName').attr('disabled', 'disabled');
    $.ajax({
        url: '/products/updateSizeDetails',
        method: 'POST',
        data: formdata,
        cache: false,
        processData: false,
        contentType: false,
        success: function (response) {
            $('.sizeButtonName').removeAttr('disabled');
            if (response.Error) {
                $('.sizeFormAlert').removeClass('d-none');
                inlineMessageAlert('.sizeFormAlert', 'danger', response.Message, false, false);
            } else {
                $('#SizesForm').trigger('reset');
                $('#sizesModal').modal('hide');
                executeSizePagnFunc(response, true);
                setSizeOption(response.SizeList);
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
                setSizeOption(response.SizeList);
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
                SelectedUIDs = [];
                executeSizePagnFunc(response, true);
                setSizeOption(response.SizeList);
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

    SizeUIDs = response.UIDs ? response.UIDs : [];
    headerCheckboxTrueFalse(SizeUIDs, SizeHeader);
    tableCheckboxTrueFalse(SelectedUIDs, SizeTable, SizeRow);
    MultipleDeleteOption();

}

/**
 * @param {*} PageNo
 * @param {*} RowLimit
 * @param {*} Filter
 */
function getBrandsDetails(PageNo, RowLimit, Filter) {
    $.ajax({
        url: '/products/getBrandsDetails/' + PageNo,
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
    $('.brandButtonName').attr('disabled', 'disabled');
    $.ajax({
        url: '/products/addBrandDetails',
        method: 'POST',
        data: formdata,
        cache: false,
        processData: false,
        contentType: false,
        success: function (response) {
            $('.brandButtonName').removeAttr('disabled');
            if (response.Error) {
                $('.brandFormAlert').removeClass('d-none');
                inlineMessageAlert('.brandFormAlert', 'danger', response.Message, false, false);
            } else {
                $('#BrandsForm').trigger('reset');
                $('#brandsModal').modal('hide');
                executeBrandPagnFunc(response, true);
                setBrandOption(response.BrandList);
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
                Swal.fire({
                    icon: "error",
                    title: "Oops...",
                    text: response.Message,
                });
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
    $('.brandButtonName').attr('disabled', 'disabled');
    $.ajax({
        url: '/products/updateBrandDetails',
        method: 'POST',
        data: formdata,
        cache: false,
        processData: false,
        contentType: false,
        success: function (response) {
            $('.brandButtonName').removeAttr('disabled');
            if (response.Error) {
                $('.brandFormAlert').removeClass('d-none');
                inlineMessageAlert('.brandFormAlert', 'danger', response.Message, false, false);
            } else {
                $('#BrandsForm').trigger('reset');
                $('#brandsModal').modal('hide');
                executeBrandPagnFunc(response, true);
                setBrandOption(response.BrandList);
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
                setBrandOption(response.BrandList);
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
                SelectedUIDs = [];
                executeBrandPagnFunc(response, true);
                setBrandOption(response.BrandList);
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

    BrandUIDs = response.UIDs ? response.UIDs : [];
    headerCheckboxTrueFalse(BrandUIDs, BrandHeader);
    tableCheckboxTrueFalse(SelectedUIDs, BrandTable, BrandRow);
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
    // const ExportIds = SelectedUIDs.length > 0 ? btoa(SelectedUIDs.toString()) : '';

    let URLs = '';
    let TableName;
    let TableHeader;
    let TableRow;
    let ItemIds;
    let FileName;
    let SheetName;
    if (ActiveTabId == 'Item') {
        TableName = ProdTable;
        TableHeader = ProdHeader;
        TableRow = ProdRow;
        ItemIds = ItemUIDs;
        FileName = 'Product_Data';
        SheetName = 'Item';
    } else if (ActiveTabId == 'Categories') {
        TableName = CatgTable;
        TableHeader = CatgHeader;
        TableRow = CatgRow;
        ItemIds = CategoryUIDs;
        FileName = 'Category_Data';
        SheetName = 'Category';
    } else if (ActiveTabId == 'Sizes') {
        TableName = SizeTable;
        TableHeader = SizeHeader;
        TableRow = SizeRow;
        ItemIds = SizeUIDs;
        FileName = 'Size_Data';
        SheetName = 'Size';
    } else if (ActiveTabId == 'Brands') {
        TableName = BrandTable;
        TableHeader = BrandHeader;
        TableRow = BrandRow;
        ItemIds = BrandUIDs;
        FileName = 'Brand_Data';
        SheetName = 'Brand';
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
        URLs = "/globally/getPrintPreviewDetails?ModuleId=" + ActiveTabModuleId;
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
    if (Flag == 1) {
        exportAllActions(ActiveTabModuleId, Type, ItemIds, URLs, function () {
            exportModalCloseFunc(TableName, TableHeader, TableRow, ItemIds);
        });
    } else if (Flag == 2) {
        if (Type == 'PrintPreview') {
            printPreviewRecords(URLs, function () {
                exportModalCloseFunc(TableName, TableHeader, TableRow, ItemIds);
            });
        } else if (Type == 'ExportCSV' || 'ExportPDF' || 'ExportExcel') {
            window.location.href = URLs;
            exportModalCloseFunc(TableName, TableHeader, TableRow, ItemIds);
        }
    }
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

function setFilterCategoryOption(CatgList) {
    $('#categoryList').empty();
    $('#AddEditItemForm #Category').empty();
    $('#AddEditItemForm #Category').append('<option label="-- Select Category --"></option>');
    CatgList.forEach(cat => {
        const categoryHtml = `
        <div class="form-check mb-2 my-1 list-hover-bg">
          <label class="form-check-label w-100 d-flex align-items-center">
            <input class="form-check-input me-2 category-checkbox" type="checkbox" value="${cat.CategoryUID}">
            <span>${cat.Name}</span>
          </label>
        </div>
      `;
        $('#AddEditItemForm #Category').append(`<option value="${cat.CategoryUID}">${cat.Name}</option>`);
        $('#categoryList').append(categoryHtml);
    });
}

function setSizeOption(SizeList) {
    $('#AddEditItemForm #PSizeUID').empty();
    $('#AddEditItemForm #PSizeUID').append('<option label="-- Select Size --"></option>');
    SizeList.forEach(size => {
        $('#AddEditItemForm #PSizeUID').append(`<option value="${size.SizeUID}">${size.Name}</option>`);
    });
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