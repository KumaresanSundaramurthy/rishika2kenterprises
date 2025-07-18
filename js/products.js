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
            ItemUIDs = response.UIDs ? response.UIDs : [];
            headerCheckboxTrueFalse(ItemUIDs, ProdHeader);
            tableCheckboxTrueFalse(SelectedUIDs, ProdTable, ProdRow);
            MultipleDeleteOption();
        },
    });
}

function addProductData(formdata) {

    $('.addFormAlert').removeClass('d-none');
    inlineMessageAlert('.addFormAlert', 'info', 'Processing... Please wait', false, false);

    $.ajax({
        url: '/products/addProductData',
        method: 'POST',
        data: formdata,
        cache: false,
        processData: false,
        contentType: false,
        enctype: 'multipart/form-data',
        success: function (response) {

            AjaxLoading = 1;
            $('.AddProductBtn').removeAttr('disabled');

            if (response.Error) {
                inlineMessageAlert('.addFormAlert', 'danger', response.Message, false, false);
            } else {

                inlineMessageAlert('.addFormAlert', 'success', response.Message, false, true);
                setTimeout(function () {
                    $('.addFormAlert').fadeOut(500, function () {
                        $(this).addClass('d-none').show();
                    });
                }, 1000);

                $('#AddItemForm').trigger('reset');
                myOneDropzone.removeAllFiles(true);
                window.history.back();

            }

        }
    });

}

function editProductData(formdata) {

    $('.editFormAlert').removeClass('d-none');
    inlineMessageAlert('.editFormAlert', 'info', 'Processing... Please wait', false, false);

    $.ajax({
        url: '/products/updateProductData',
        method: 'POST',
        data: formdata,
        cache: false,
        processData: false,
        contentType: false,
        enctype: 'multipart/form-data',
        success: function (response) {

            AjaxLoading = 1;
            $('.EditProductBtn').removeAttr('disabled');

            if (response.Error) {
                inlineMessageAlert('.editFormAlert', 'danger', response.Message, false, false);
            } else {

                inlineMessageAlert('.editFormAlert', 'success', response.Message, false, true);
                setTimeout(function () {
                    $('.editFormAlert').fadeOut(500, function () {
                        $(this).addClass('d-none').show();
                    });
                }, 1000);

                $('#EditItemForm').trigger('reset');
                myOneDropzone.removeAllFiles(true);
                window.history.back();

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
                ItemUIDs = response.UIDs ? response.UIDs : [];
                $(ProdPag).html(response.Pagination);
                $(ProdTable + ' tbody').html(response.List);
                Swal.fire(response.Message, "", "success");
                headerCheckboxTrueFalse(ItemUIDs, ProdHeader);
                tableCheckboxTrueFalse(SelectedUIDs, ProdTable, ProdRow);
                MultipleDeleteOption();
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
                ItemUIDs = response.UIDs ? response.UIDs : [];
                $(ProdPag).html(response.Pagination);
                $(ProdTable + ' tbody').html(response.List);
                Swal.fire(response.Message, "", "success");
                headerCheckboxTrueFalse(ItemUIDs, ProdHeader);
                tableCheckboxTrueFalse(SelectedUIDs, ProdTable, ProdRow);
                MultipleDeleteOption();
            }
        },
    });
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
        },
        success: function (response) {
            if (response.Error) {
                $(CatgTable + ' tbody').html('');
                $(CatgPag).html('<div class="alert alert-danger" role="alert"><strong>' + response.Message + '</strong></div>');
            } else {
                $(CatgPag).html(response.Pagination);
                $(CatgTable + ' tbody').html(response.List);
            }
        },
    });

}

function addCategoryDetails(formdata) {

    AjaxLoading = 0;

    $('#CatgSaveButton').attr('disabled', 'disabled');

    $('#catgFormAlert').removeClass('d-none');
    inlineMessageAlert('#catgFormAlert', 'info', 'Processing... Please wait', false, false);

    $.ajax({
        url: '/products/addCategoryDetails',
        method: 'POST',
        data: formdata,
        cache: false,
        processData: false,
        contentType: false,
        enctype: 'multipart/form-data',
        success: function (response) {

            AjaxLoading = 1;
            $('#CatgSaveButton').removeAttr('disabled');

            if (response.Error) {
                inlineMessageAlert('#catgFormAlert', 'danger', response.Message, false, false);
            } else {

                inlineMessageAlert('#catgFormAlert', 'success', response.Message, false, true);
                setTimeout(function () {
                    $('#catgFormAlert').fadeOut(500, function () {
                        $(this).addClass('d-none').show();
                    });
                }, 1000);

                myOneDropzone.removeAllFiles(true);
                $('#categoryForm').trigger('reset');
                $('#categoryModal').modal('hide');

                $(CatgPag).html(response.Pagination);
                $(CatgTable + ' tbody').html(response.List);
                Swal.fire(response.Message, "", "success");

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
                $('#CatgSaveButton').text('Update');
                $('#categoryModal').modal('show');

                $('#CategoryUID').val(response.Data.CategoryUID);
                $('#CategoryName').val(response.Data.Name);
                $('#CategoryDescription').val(response.Data.Description);

                var CatgImgURL = CDN_URL + response.Data.Image;

                if (CatgImgURL && CatgImgURL !== undefined && CatgImgURL !== null && CatgImgURL !== '') {

                    myOneDropzone.removeAllFiles(true);

                    fetch(CatgImgURL)
                        .then(res => {
                            const contentLength = res.headers.get('Content-Length');
                            return res.blob().then(blob => {
                                const fileName = decodeURIComponent(CatgImgURL.substring(CatgImgURL.lastIndexOf('/') + 1));
                                const file = new File([blob], fileName, {
                                    type: blob.type,
                                    lastModified: new Date()
                                });

                                // Manually patch the size if Dropzone doesn’t read it right
                                file.size = contentLength ? parseInt(contentLength) : blob.size;
                                file.isStored = true;

                                // Add to Dropzone
                                myOneDropzone.emit("addedfile", file);
                                myOneDropzone.emit("thumbnail", file, CatgImgURL);
                                myOneDropzone.emit("complete", file);
                                myOneDropzone.files.push(file);
                            });
                        });
                }

            }
        },
    });
}

function editCategoryDetails(formdata) {

    AjaxLoading = 0;

    $('#CatgSaveButton').attr('disabled', 'disabled');

    $('#catgFormAlert').removeClass('d-none');
    inlineMessageAlert('#catgFormAlert', 'info', 'Processing... Please wait', false, false);

    $.ajax({
        url: '/products/updateCategoryDetails',
        method: 'POST',
        data: formdata,
        cache: false,
        processData: false,
        contentType: false,
        enctype: 'multipart/form-data',
        success: function (response) {

            AjaxLoading = 1;
            $('#CatgSaveButton').removeAttr('disabled');

            if (response.Error) {
                inlineMessageAlert('#catgFormAlert', 'danger', response.Message, false, false);
            } else {

                inlineMessageAlert('#catgFormAlert', 'success', response.Message, false, true);
                setTimeout(function () {
                    $('#catgFormAlert').fadeOut(500, function () {
                        $(this).addClass('d-none').show();
                    });
                }, 1000);

                myOneDropzone.removeAllFiles(true);
                $('#categoryForm').trigger('reset');
                $('#categoryModal').modal('hide');

                $(CatgPag).html(response.Pagination);
                $(CatgTable + ' tbody').html(response.List);
                Swal.fire(response.Message, "", "success");

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
            CategoryUID: CategoryUID
        },
        success: function (response) {
            if (response.Error) {
                Swal.fire(response.Message, "", "error");
            } else {
                $(CatgPag).html(response.Pagination);
                $(CatgTable + ' tbody').html(response.List);
                Swal.fire(response.Message, "", "success");
            }
        },
    });
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
        },
        success: function (response) {
            if (response.Error) {
                $(SizeTable + ' tbody').html('');
                $(SizePag).html('<div class="alert alert-danger" role="alert"><strong>' + response.Message + '</strong></div>');
            } else {
                $(SizePag).html(response.Pagination);
                $(SizeTable + ' tbody').html(response.List);
            }
        },
    });

}

function addSizeDetails(formdata) {

    $('#sizeFormAlert').removeClass('d-none');
    inlineMessageAlert('#sizeFormAlert', 'info', 'Processing... Please wait', false, false);

    $.ajax({
        url: '/products/addSizeDetails',
        method: 'POST',
        data: formdata,
        cache: false,
        processData: false,
        contentType: false,
        success: function (response) {

            AjaxLoading = 1;
            $('#sizeButtonName').removeAttr('disabled');

            if (response.Error) {
                inlineMessageAlert('#sizeFormAlert', 'danger', response.Message, false, false);
            } else {
                inlineMessageAlert('#sizeFormAlert', 'success', response.Message, false, true);
                setTimeout(function () {
                    $('#sizeFormAlert').fadeOut(500, function () {
                        $(this).addClass('d-none').show();
                    });
                }, 1000);

                $('#SizesForm').trigger('reset');
                $('#sizesModal').modal('hide');

                $(SizePag).html(response.Pagination);
                $(SizeTable + ' tbody').html(response.List);
                Swal.fire(response.Message, "", "success");

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

                $('#SizeUID').val(response.Data.SizeUID);
                $('#SizesName').val(response.Data.Name);
                $('#SizesDescription').val(response.Data.Description);

            }
        },
    });
}

function editSizeDetails(formdata) {

    $('#sizeFormAlert').removeClass('d-none');
    inlineMessageAlert('#sizeFormAlert', 'info', 'Processing... Please wait', false, false);

    $.ajax({
        url: '/products/updateSizeDetails',
        method: 'POST',
        data: formdata,
        cache: false,
        processData: false,
        contentType: false,
        success: function (response) {

            AjaxLoading = 1;
            $('#sizeButtonName').removeAttr('disabled');

            if (response.Error) {
                inlineMessageAlert('#sizeFormAlert', 'danger', response.Message, false, false);
            } else {
                inlineMessageAlert('#sizeFormAlert', 'success', response.Message, false, true);
                setTimeout(function () {
                    $('#sizeFormAlert').fadeOut(500, function () {
                        $(this).addClass('d-none').show();
                    });
                }, 1000);

                $('#SizesForm').trigger('reset');
                $('#sizesModal').modal('hide');

                $(SizePag).html(response.Pagination);
                $(SizeTable + ' tbody').html(response.List);
                Swal.fire(response.Message, "", "success");

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
            SizeUID: SizeUID
        },
        success: function (response) {
            if (response.Error) {
                Swal.fire(response.Message, "", "error");
            } else {
                $(SizePag).html(response.Pagination);
                $(SizeTable + ' tbody').html(response.List);
                Swal.fire(response.Message, "", "success");
            }
        },
    });
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
        },
        success: function (response) {
            if (response.Error) {
                $(BrandTable + ' tbody').html('');
                $(BrandPag).html('<div class="alert alert-danger" role="alert"><strong>' + response.Message + '</strong></div>');
            } else {
                $(BrandPag).html(response.Pagination);
                $(BrandTable + ' tbody').html(response.List);
            }
        },
    });
}

function addBrandDetails(formdata) {

    $('#brandFormAlert').removeClass('d-none');
    inlineMessageAlert('#brandFormAlert', 'info', 'Processing... Please wait', false, false);

    $.ajax({
        url: '/products/addBrandDetails',
        method: 'POST',
        data: formdata,
        cache: false,
        processData: false,
        contentType: false,
        success: function (response) {
            AjaxLoading = 1;
            $('#brandButtonName').removeAttr('disabled');
            if (response.Error) {
                inlineMessageAlert('#brandFormAlert', 'danger', response.Message, false, false);
            } else {

                inlineMessageAlert('#brandFormAlert', 'success', response.Message, false, true);
                setTimeout(function () {
                    $('#brandFormAlert').fadeOut(500, function () {
                        $(this).addClass('d-none').show();
                    });
                }, 1000);

                $('#BrandsForm').trigger('reset');
                $('#brandsModal').modal('hide');

                $(BrandPag).html(response.Pagination);
                $(BrandTable + ' tbody').html(response.List);
                Swal.fire(response.Message, "", "success");

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

                $('#BrandUID').val(response.Data.BrandUID);
                $('#BrandsName').val(response.Data.Name);
                $('#BrandsDescription').val(response.Data.Description);

            }
        }
    });
}

function editBrandDetails(formdata) {

    $('#brandFormAlert').removeClass('d-none');
    inlineMessageAlert('#brandFormAlert', 'info', 'Processing... Please wait', false, false);

    $.ajax({
        url: '/products/updateBrandDetails',
        method: 'POST',
        data: formdata,
        cache: false,
        processData: false,
        contentType: false,
        success: function (response) {
            AjaxLoading = 1;
            $('#brandButtonName').removeAttr('disabled');
            if (response.Error) {
                inlineMessageAlert('#brandFormAlert', 'danger', response.Message, false, false);
            } else {

                inlineMessageAlert('#brandFormAlert', 'success', response.Message, false, true);
                setTimeout(function () {
                    $('#brandFormAlert').fadeOut(500, function () {
                        $(this).addClass('d-none').show();
                    });
                }, 1000);

                $('#BrandsForm').trigger('reset');
                $('#brandsModal').modal('hide');

                $(BrandPag).html(response.Pagination);
                $(BrandTable + ' tbody').html(response.List);
                Swal.fire(response.Message, "", "success");

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
            BrandUID: BrandUID
        },
        success: function (response) {
            if (response.Error) {
                Swal.fire(response.Message, "", "error");
            } else {
                $(BrandPag).html(response.Pagination);
                $(BrandTable + ' tbody').html(response.List);
                Swal.fire(response.Message, "", "success");
            }
        },
    });
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

function commonExportFunctionality(Flag, Type) {
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
    const ExportIds = SelectedUIDs.length > 0 ? btoa(SelectedUIDs.toString()) : '';
    let URLs = '';
    let TableName;
    let TableHeader;
    let TableRow;
    let ItemIds;
    if (ActiveTabId == 'Item') {
        TableName = ProdTable;
        TableHeader = ProdHeader;
        TableRow = ProdRow;
        ItemIds = ItemUIDs;
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
            URLs = "/globally/exportModuleDataDetails?ModuleId=" + ActiveTabModuleId + "&Type=" + TypeVal+"&FileName=Product_Data"+"&SheetName=Products_Details";
            if (!$.isEmptyObject(Filter)) {
                URLs += "&Filter=" + encodeURIComponent(JSON.stringify(Filter));
            }
            if (ExportIds != '') {
                URLs += "&ExportIds=" + ExportIds;
            }
        }
    } else if (ActiveTabId == 'Categories') {
        URLs = '';
    } else if (ActiveTabId == 'Sizes') {
        URLs = '';
    } else if (ActiveTabId == 'Brands') {
        URLs = '';
    }
    if (Flag == 1) {
        exportAllActions(ActiveTabModuleId, ActiveTabId, Type, ItemIds, URLs, function () {
            exportModalCloseFunc(TableName, TableHeader, TableRow, ItemIds)
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
    $('#SizeUID').removeAttr('required');
    if ($(this).is(':checked')) {
        $('#SizeDiv').removeClass('d-none');
        $('#SizeDiv').attr('required', true);
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