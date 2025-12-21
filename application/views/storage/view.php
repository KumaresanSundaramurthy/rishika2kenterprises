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
                                <?php echo $this->load->view('common/view/action_details', ['redirectUrl' => 'javascript: void(0);', 'clsInfo' => 'addStorage', 'addActionName' => 'Create Storage'], TRUE); ?>
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
                                                        <th class="table-serialno <?php echo $JwtData->GenSettings->SerialNoDisplay == 1 ? '' : 'd-none'; ?>">S.No</th>
                                                        <?php foreach (array_column($ModColumnData, 'DisplayName') as $ItemKey => $ItemVal) { ?>

                                                            <th <?php echo $ModColumnData[$ItemKey]->MainPageColumnAddon; ?>>
                                                                <?php echo $ItemVal; ?> 
                                                            <?php if ($ModColumnData[$ItemKey]->MPSortApplicable == 1) {
                                                                    echo '<i class="bx bx-sort-alt-2 ms-1 cursor-pointer"></i>';
                                                                } ?>

                                                            <?php if ($ItemVal == 'Storage Type' && $ModColumnData[$ItemKey]->MPFilterApplicable == 1) { ?>

                                                                <a href="javascript:void(0);" class="text-body ms-1 filter-toggle" data-target="#storageTypeFilterBox">
                                                                    <i class="bx bx-filter-alt fs-5 align-middle"></i>
                                                                </a>

                                                                <div id="storageTypeFilterBox" class="card shadow mp-filterbox position-absolute p-3">

                                                                <?php if (sizeof($StorageTypeInfo) > 0) {
                                                                    echo $this->load->view('storage/storagetypefilter', ['StorageTypeInfo' => $StorageTypeInfo], TRUE);
                                                                } ?>
                                                                
                                                                </div>

                                                            <?php } ?>

                                                            </th>
                                                        <?php } ?>

                                                        <th class="text-center">Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="table-border-bottom-0">
                                                    <?php echo $ModRowData; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                        <hr class="my-0" />
                                        <div class="row mx-3 justify-content-between StoragePagination" id="StoragePagination">
                                            <?php echo $ModPagination; ?>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php $this->load->view('storage/modals/storage'); ?>

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
<script src="/js/common/pagecheckbox.js"></script>

<script>
let ModuleId = <?php echo $ModuleId; ?>;
const ModuleTable = '#StorageTable';
const ModulePag = '.StoragePagination';
const ModuleHeader = '.storageHeaderCheck';
const ModuleRow = '.storageCheck';
const ModuleFileName = 'Storage_Data';
const ModuleSheetName = 'Storage';
const previewName = 'Storage Details';
let imgData;
let sortState = 0;
$(function() {
    'use strict'

    $('#SearchDetails').val('');
    $(ModuleRow).prop('checked', false).trigger('change');

    baseExportFunctions();
    basePaginationFunc(ModulePag, getStorageDetails);
    baseRefreshPageFunc('.PageRefresh', getStorageDetails);
    basePageHeaderFunc(ModuleHeader, ModuleTable, ModuleRow);

    $(document).on('click', ModuleRow, function() {
        onClickOfCheckbox($(this), ModuleTable, ModuleHeader, ModuleRow);
        MultipleDeleteOption();
    });

    QuillEditor('.ql-toolbar', 'Enter storage description...');

    $('.SearchDetails').keyup(inputDelay(function(e) {
        PageNo = 0;
        let searchText = $('#SearchDetails').val();
        if (searchText.length >= 3) {
            SelectedUIDs = [];
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
            PageNo = 0;
            SelectedUIDs = [];
            delete Filter['SearchAllData'];
            $('#SearchDetails').blur();
            getStorageDetails(PageNo, RowLimit, Filter);
        }
    });

    $(document).on('click', '.DeleteStorage', function(e) {
        e.preventDefault();
        var GetId = $(this).data('storageuid');
        if (GetId) {
            var ProductUID = $(this).data('productuid');
            if (hasValue(ProductUID)) {
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

    /** sorting opeartions */
    $(document).on('click', '.name-sortable', function(e) {
        e.preventDefault();
        sortState = (sortState + 1) % 3;
        const icon = $(this).find('i');
        icon.removeClass('bx-sort-alt-2 bx-up-arrow-alt bx-down-arrow-alt text-primary');
        $('#sortName').removeClass('text-primary');
        if (sortState == 1) {
            icon.addClass('bx-up-arrow-alt text-primary');
            $('#sortName').addClass('text-primary');
            $(this).attr('title', 'Click sorting descending');
            Filter['NameSorting'] = 1;
        } else if (sortState === 2) {
            icon.addClass('bx-down-arrow-alt text-primary');
            $('#sortName').addClass('text-primary');
            $(this).attr('title', 'Remove sorting');
            Filter['NameSorting'] = 2;
        } else {
            icon.addClass('bx-sort-alt-2');
            $(this).attr('title', 'Click sorting ascending');
            delete Filter['NameSorting'];
        }
        $(this).tooltip('dispose').tooltip();
        getStorageDetails(PageNo, RowLimit, Filter);
    });

    $(document).on('click', '.addStorage', function(e) {
        e.preventDefault();
        formOpenCloseDefActions();
        $('#storageModal').modal('show');
    });

    $('#storageModal').on('shown.bs.modal', function() {
        $('#Name').trigger('focus');
    });

    $('#storageForm').on('submit', function(e) {
        e.preventDefault();

        var formData = new FormData($('#storageForm')[0]);
        var StorageUID = $('#storageForm').find('#StorageUID').val();

        if(StorageUID && hasValue(imgData) && myOneDropzone.files.length == 0) {
            formData.append('ImageRemoved', 1);
        }
        if (myOneDropzone.files.length > 0) {
            const file = myOneDropzone.files[0];
            if (!file.isStored) {
                formData.append('UploadImage', myOneDropzone.files[0]);
            }
        }

        const Description = quill.getText().trim();
        if ($.trim(Description) != '') {
            formData.append('Description', $('#Description .ql-editor').html());
        }
        formData.append('PageNo', PageNo);
        formData.append('RowLimit', RowLimit);
        formData.append('ModuleId', ModuleId);
        if (Object.keys(Filter).length > 0) {
            formData.append('Filter', JSON.stringify(Filter));
        }
        
        if (StorageUID == 0) {
            addStorageData(formData);
        } else {
            updateStorageData(formData);
        }

    });

    $('#storageModal').on('hide.bs.modal', function() {
        formOpenCloseDefActions();
    });

    $(document).on('click', '.editStorage', function(e) {
        e.preventDefault();
        var getVal = $(this).data('uid');
        if (getVal) {

            var getName = $(this).data('name');
            var getSName = $(this).data('shortname');
            var getDesc = $(this).data('description');
            var getImg = $(this).data('image');
            var getStrTypeUid = $(this).data('storagetypeuid');

            $('#storageForm').trigger('reset');
            $('#StorageModalTitle').text('Edit Storage');
            $('#StorageSaveButton').text('Update');
            $('#storageModal').modal('show');

            $('#StorageUID').val(getVal);
            $('#Name').val(getName ? atob(getName) : '');
            $('#ShortName').val(getSName ? atob(getSName) : '');
            $('#StorageTypeUID').val(atob(getStrTypeUid)).trigger('change');
            if (getDesc) {
                appendToQuill(atob(getDesc), true);
            }
            if(hasValue(getImg)) {
                var ImageUrl = CDN_URL + atob(getImg);
                commonSetDropzoneImageOne(ImageUrl);
                imgData = ImageUrl;
            }

        }
    });

});
</script>