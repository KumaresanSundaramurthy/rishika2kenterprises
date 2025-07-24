<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php $this->load->view('common/header'); ?>

<!-- Layout wrapper -->
<div class="layout-wrapper layout-content-navbar">
    <div class="layout-container">

        <?php $this->load->view('common/menu_view'); ?>

        <!-- Layout container -->
        <div class="layout-page">

            <?php $this->load->view('common/navbar_view'); ?>

            <!-- Content wrapper -->
            <div class="content-wrapper">
                <div class="container-xxl flex-grow-1 container-p-y">

                    <div class="card">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="d-flex flex-column flex-md-row justify-content-between align-items-center p-3">
                                    <ul class="nav nav-pills nav nav-pills flex-row" role="tablist">
                                        <li class="nav-item">
                                            <a class="nav-link active TabPane" data-id="Item" data-moduleid="<?php echo $ModuleId; ?>" role="tab" data-bs-toggle="tab" data-bs-target="#NavItemPage" aria-controls="NavItemPage" aria-selected="true" href="javascript: void(0);"><i class="bx bx-server me-1"></i> Storage</a>
                                        </li>
                                    </ul>
                                    <div class="d-flex mt-2 mt-md-0">
                                        <a href="javascript: void(0);" class="btn PageRefresh p-2 me-0"><i class="bx bx-refresh fs-4"></i></a>
                                        <a href="javascript: void(0);" id="btnPageSettings" class="btn p-2"><i class="bx bx-cog fs-4"></i></a>
                                        <div class="position-relative me-2">
                                            <input type="text" class="form-control SearchDetails" name="SearchDetails" id="SearchDetails" placeholder="Search details..." data-toggle="tooltip" title="Please type at least 3 characters to search" />
                                            <i class="bx bx-x position-absolute top-50 end-0 translate-middle-y me-3 text-muted cursor-pointer d-none" id="clearSearch"></i>
                                        </div>
                                        <div class="btn-group" id="ActionsDD-Div">
                                            <button class="btn btn-label-secondary dropdown-toggle me-2" type="button" id="actionsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                                <span class="d-flex align-items-center gap-2">
                                                    <i class="icon-base bx bx-slider-alt icon-xs"></i>
                                                    <span class="d-none d-sm-inline-block">Actions</span>
                                                </span>
                                            </button>
                                            <ul class="dropdown-menu" aria-labelledby="actionsDropdown">
                                                <li class="d-none" id="CloneOption">
                                                    <a class="dropdown-item" href="javascript: void(0);" id="btnClone">
                                                        <i class="bx bx-duplicate me-1"></i> Clone
                                                    </a>
                                                </li>
                                                <li class="d-none" id="DeleteOption">
                                                    <a class="dropdown-item text-danger" href="javascript: void(0);" id="btnDelete">
                                                        <i class="bx bx-trash me-1"></i> Delete
                                                    </a>
                                                </li>
                                                <li class="dropdown-submenu">
                                                    <a class="dropdown-item" href="javascript: void(0);">
                                                        <i class="bx bx-export me-1"></i> Export
                                                    </a>
                                                    <ul class="dropdown-menu">
                                                        <li>
                                                            <a class="dropdown-item" href="javascript: void(0);" id="btnExportPrint">
                                                                <i class="bx bx-printer me-1"></i> Print
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a class="dropdown-item" href="javascript: void(0);" id="btnExportCSV">
                                                                <i class="bx bx-file me-1"></i> CSV
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a class="dropdown-item" href="javascript: void(0);" id="btnExportExcel">
                                                                <i class="bx bxs-file-export me-1"></i> Excel
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a class="dropdown-item" href="javascript: void(0);" id="btnExportPDF">
                                                                <i class="bx bxs-file-pdf me-1"></i> PDF
                                                            </a>
                                                        </li>
                                                    </ul>
                                                </li>
                                            </ul>
                                        </div>
                                        <a href="javascript: vodi(0);" class="btn btn-primary px-3 addStorage" id="addStorage"><i class='bx bx-plus'></i> New Storage</a>
                                    </div>
                                </div>
                                <div class="tab-content p-0">
                                    <div class="tab-pane fade show active" id="NavItemPage" role="tabpanel">

                                        <div class="table-responsive text-nowrap h-100 tablecard">
                                            <table class="table table-sm table-striped table-hover" id="StorageTable">
                                                <thead>
                                                    <tr>
                                                        <th class="table-checkbox">
                                                            <div class="form-check form-check-inline">
                                                                <input class="form-check-input table-chkbox storageHeaderCheck" type="checkbox">
                                                            </div>
                                                        </th>
                                                        <th class="table-serialno">S.No</th>
                                                        <?php foreach (array_column($ModuleColumns, 'DisplayName') as $ItemKey => $ItemVal) { ?>
                                                            <th <?php echo $ModuleColumns[$ItemKey]->MainPageColumnAddon; ?>><?php echo $ItemVal; ?></th>
                                                        <?php } ?>
                                                        <th class="text-center">Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="table-border-bottom-0">
                                                    <?php echo $ModDataList; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                        <hr class="my-0" />
                                        <div class="row mx-3 justify-content-between StoragePagination" id="StoragePagination">
                                            <?php echo $ModDataPagination; ?>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php $this->load->view('products/modals/storage'); ?>

                </div>
            </div>
            <!-- Content wrapper -->

            <?php $this->load->view('common/settings_modal'); ?>

            <?php $this->load->view('common/footer_desc'); ?>

        </div>

    </div>
</div>

<?php $this->load->view('common/footer'); ?>

<script src="/js/storage.js"></script>

<script>
    let ModuleUIDs = <?php echo json_encode($ModDataUIDs ?: []); ?>;
    let ModuleId = <?php echo $ModuleId; ?>;
    const ModuleTable = '#StorageTable';
    const ModulePag = '.StoragePagination';
    const ModuleHeader = '.storageHeaderCheck';
    const ModuleRow = '.storageCheck';
    let editImageRemoved = 0;
    $(function() {
        'use strict'

        $('#SearchDetails').val('');
        $(ModuleHeader + ',' + ModuleRow).prop('checked', false).trigger('change');

        QuillEditor('.ql-toolbar', 'Enter storage description...');

        $(ModulePag).on('click', 'a', function(e) {
            e.preventDefault();
            PageNo = $(this).attr('data-ci-pagination-page');
            getStorageDetails(PageNo, RowLimit, Filter);
        });

        $(ModuleHeader).click(function() {
            allTableHeadersCheckbox($(this), ModuleUIDs, ModuleTable, ModuleHeader, ModuleRow);
        });

        $(document).on('click', ModuleRow, function() {
            onClickOfCheckbox($(this), ModuleUIDs, ModuleHeader);
            MultipleDeleteOption();
        });

        $('.SearchDetails').keyup(inputDelay(function(e) {
            PageNo = 0;
            let searchText = $('#SearchDetails').val();
            if (searchText.length >= 3) {
                delete Filter['SearchAllData'];
                $('#clearSearch').removeClass('d-none');
                if (searchText) {
                    Filter['SearchAllData'] = searchText;
                }
                $('#SearchDetails').blur();
                getStorageDetails(PageNo, RowLimit, Filter);
            }
        }, 500));

        $('#clearSearch').click(function(e) {
            e.preventDefault();
            var searchText = $('#SearchDetails').val();
            $('#SearchDetails').val('');
            $('#clearSearch').addClass('d-none');
            if ($.trim(searchText) != '') {
                delete Filter['SearchAllData'];
                $('#SearchDetails').blur();
                getStorageDetails(PageNo, RowLimit, Filter);
            }
        });

        $(document).on('click', '.PageRefresh', function(e) {
            e.preventDefault();
            getStorageDetails(PageNo, RowLimit, Filter);
        });

        $('#btnExportPrint').click(function(e) {
            e.preventDefault();
            baseExportFunctionality(1, 'PrintPreview', 'Storage_Data', 'Storage');
        });

        $('#btnExportCSV').click(function(e) {
            e.preventDefault();
            baseExportFunctionality(1, 'ExportCSV', 'Storage_Data', 'Storage');
        });

        $('#btnExportPDF').click(function(e) {
            e.preventDefault();
            baseExportFunctionality(1, 'ExportPDF', 'Storage_Data', 'Storage');
        });

        $('#btnExportExcel').click(function(e) {
            e.preventDefault();
            baseExportFunctionality(1, 'ExportExcel', 'Storage_Data', 'Storage');
        });

        $('#exportSelectedItemsBtn').click(function(e) {
            e.preventDefault();
            baseExportFunctionality(2, expActionType, 'Storage_Data', 'Storage');
        });

        $('#clearExportClose').click(function(e) {
            e.preventDefault();
            exportModalCloseFunc(ModuleTable, ModuleHeader, ModuleRow, ModuleUIDs);
        });

        $(document).on('click', '.addStorage', function(e) {
            e.preventDefault();
            hasRemovedStoredImage = false;
            $('#storageForm').trigger('reset');
            $('#StorageModalTitle').text('Add Storage');
            $('#StorageSaveButton').text('Save');
            $('#storageModal').modal('show');
            $('#storageForm').find('#StorageUID').val(0);
            quill.setContents([]);
        });

        $('#storageModal').on('shown.bs.modal', function() {
            $('#Name').trigger('focus');
        });

        $('#storageForm').on('submit', function(e) {
            e.preventDefault();

            var formData = new FormData($('#storageForm')[0]);
            if (myOneDropzone.files.length > 0) {
                formData.append('UploadImage', myOneDropzone.files[0]);
            }

            const Description = quill.getText().trim();
            if ($.trim(Description) != '') {
                formData.append('Description', $('#Description .ql-editor').html());
            }
            formData.append('PageNo', PageNo);
            formData.append('RowLimit', RowLimit);
            if (Object.keys(Filter).length > 0) {
                formData.append('Filter', JSON.stringify(Filter));
            }
            formData.append('ModuleId', ModuleId);

            var StorageUID = $('#storageForm').find('#StorageUID').val();
            if (StorageUID == 0) {
                addStorageData(formData);
            } else {
                updateStorageData(formData);
            }

        });

        $('#storageModal').on('hide.bs.modal', function() {
            quill.setContents([]);
            myOneDropzone.removeAllFiles(true);
        });

        $(document).on('click', '.editStorage', function(e) {
            e.preventDefault();
            var getVal = $(this).data('uid');
            if (getVal) {

                var getName = $(this).data('name');
                var getSName = $(this).data('shortname');
                var getDesc = $(this).data('description');
                var getImg = $(this).data('image');

                $('#storageForm').trigger('reset');
                $('#StorageModalTitle').text('Edit Storage');
                $('#StorageSaveButton').text('Update');
                $('#storageModal').modal('show');

                $('#StorageUID').val(getVal);
                $('#Name').val(getName ? atob(getName) : '');
                $('#ShortName').val(getSName ? atob(getSName) : '');
                $('#StorageTypeUID').val($(this).data('strtype')).trigger('change');

                if (getDesc) {
                    appendToQuill(atob(getDesc), true);
                }

                getImg = getImg ? atob(getImg) : '';

                if (getImg && getImg !== undefined && getImg !== null && getImg !== '') {
                    // var StorImgURL = CDN_URL + getImg;
                    var StorImgURL = getImg;

                    if (StorImgURL && getImg !== undefined && getImg !== null && getImg !== '') {

                        myOneDropzone.removeAllFiles(true);

                        fetch(getImg)
                            .then(res => res.blob().then(blob => {
                                const fileName = decodeURIComponent(getImg.substring(getImg.lastIndexOf('/') + 1));
                                const file = new File([blob], fileName, {
                                    type: blob.type,
                                    lastModified: new Date()
                                });

                                file.isStored = true;

                                myOneDropzone.emit("addedfile", file);
                                myOneDropzone.emit("thumbnail", file, getImg);
                                myOneDropzone.emit("complete", file);
                                myOneDropzone.files.push(file);
                            }));
                            
                    }
                }

            }
        });

        $(document).on('click', '.DeleteStorage', function(e) {
            e.preventDefault();
            var GetId = $(this).data('storageuid');
            if (GetId) {
                var ProductUID = $(this).data('productuid');
                if (ProductUID && ProductUID !== undefined && ProductUID !== null && ProductUID !== '') {
                    Swal.fire("Storage is linked to Product.", "", "error");
                    return false;
                } else {
                    Swal.fire({
                        title: "Do you want to delete the storage?",
                        text: "You won't be able to revert this!",
                        icon: "warning",
                        showCancelButton: true,
                        confirmButtonColor: "#d33",
                        confirmButtonText: "Yes, delete it!",
                        cancelButtonColor: "#3085d6",
                    }).then((result) => {
                        if (result.isConfirmed) {
                            deleteStorage(GetId);
                        }
                    });
                }
            }
        });

        $('#btnDelete').click(function(e) {
            e.preventDefault();
            if (SelectedUIDs.length > 0) {
                let DeleteContent = 'Do you want to delete all the selected storage?';
                Swal.fire({
                    title: DeleteContent,
                    text: "You won't be able to revert this!",
                    icon: "info",
                    showCancelButton: true,
                    confirmButtonColor: "#d33",
                    confirmButtonText: "Yes, delete it!",
                    cancelButtonColor: "#3085d6",
                }).then((result) => {
                    if (result.isConfirmed) {
                        deleteMultipleStorage();
                    }
                });
            }
        });

    });
</script>