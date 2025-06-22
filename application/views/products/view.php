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
                                            <a class="nav-link active TabPane" data-id="Item" role="tab" data-bs-toggle="tab" data-bs-target="#NavItemPage" aria-controls="NavItemPage" aria-selected="true" href="javascript: void(0);"><i class="bx bx-package me-1"></i> Item</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link TabPane" data-id="Categories" role="tab" data-bs-toggle="tab" data-bs-target="#NavCategoriesPage" aria-controls="NavCategoriesPage" aria-selected="true" href="javascript: void(0);"><i class="bx bx-layer me-1"></i> Categories</a>
                                        </li>
                                    </ul>
                                    <div class="d-flex mt-2 mt-md-0">
                                        <a href="javascript: void(0);" class="btn PageRefresh"><i class='bx bx-refresh' style="font-size: 22px;"></i> </a>
                                        <div class="me-2"><input type="text" class="form-control SearchDetails" name="SearchDetails" id="SearchDetails" placeholder="Search details..." /></div>
                                        <a href="/products/add" class="btn btn-primary px-3" id="NewItem"><i class='bx bx-plus'></i> New Item</a>
                                        <a href="javascript: void(0);" class="btn btn-primary px-3 addCategory d-none" id="NewCategory"><i class='bx bx-plus'></i> New Category</a>
                                    </div>
                                </div>
                                <div class="tab-content p-0">

                                    <div class="tab-pane fade show active" id="NavItemPage" role="tabpanel">

                                        <div class="table-responsive text-nowrap h-100 tablecard">
                                            <table class="table table-sm table-striped table-hover" id="ProductsTable">
                                                <thead>
                                                    <tr>
                                                        <th></th>
                                                        <th>Name</th>
                                                        <th>Category</th>
                                                        <th class="text-center">Qty</th>
                                                        <th class="text-end">Selling Price</th>
                                                        <th class="text-end">Purchase Price</th>
                                                        <th class="text-end">Last Updated On</th>
                                                        <th class="text-center">Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="table-border-bottom-0"></tbody>
                                            </table>
                                        </div>
                                        <hr class="my-0" />
                                        <div class="row mx-3 justify-content-between ProductsPagination" id="ProductsPagination"></div>

                                    </div>

                                    <div class="tab-pane fade" id="NavCategoriesPage" role="tabpanel">

                                        <div class="table-responsive text-nowrap h-100 tablecard">
                                            <table class="table table-sm table-striped table-hover" id="CategoriesTable">
                                                <thead>
                                                    <tr>
                                                        <th></th>
                                                        <th>Name</th>
                                                        <th>Description</th>
                                                        <th class="text-end">Last Updated</th>
                                                        <th class="text-center">Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="table-border-bottom-0">
                                                    <?php $PageData['CategoriesList'] = [];
                                                    echo $this->load->view('products/categories/list', $PageData, TRUE); ?>
                                                </tbody>
                                            </table>
                                        </div>
                                        <hr class="my-0" />
                                        <div class="row mx-3 justify-content-between CategoriesPagination" id="CategoriesPagination"></div>

                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
            <!-- Content wrapper -->

            <!-- Product Category Form -->
            <div class="modal fade" id="categoryModal" tabindex="-1" aria-labelledby="categoryModal" data-bs-backdrop="static" data-bs-keyboard="false" aria-hidden="true" aria-modal="true" role="dialog">
                <div class="modal-dialog modal-dialog-scrollable modal-fullscreen-sm-down modal-dialog-right modal-lg">
                    <div class="modal-content h-100 d-flex flex-column">

                        <?php $FormAttribute = array('id' => 'AddCategoryForm', 'name' => 'AddCategoryForm', 'class' => '', 'autocomplete' => 'off');
                        echo form_open('products/addCategory', $FormAttribute); ?>

                        <div class="modal-header">
                            <h5 class="modal-title">Add Category</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>

                        <div class="modal-body flex-grow-1 overflow-auto">
                            <div class="mb-3">
                                <label class="form-label" for="CategoryName">Name <span style="color:red">*</span></label>
                                <input type="text" class="form-control" id="CategoryName" placeholder="Enter category name" name="CategoryName" maxlength="100" required />
                            </div>
                            <div class="mb-3">
                                <label for="CategoryDescription" class="form-label">Description </label>
                                <textarea class="form-control" rows="2" name="CategoryDescription" id="CategoryDescription" placeholder="Description"></textarea>
                            </div>

                            <div class="mb-3 dropzone needsclick p-3 dz-clickable" id="DropzoneOneBasic">
                                <div class="dz-message needsclick">
                                    <p class="h4 needsclick pt-4 mb-2">Drag and drop your image here</p>
                                    <p class="h6 text-body-secondary d-block fw-normal mb-3">or</p>
                                    <span class="needsclick btn btn-sm btn-label-primary" id="btnBrowse">Browse image</span>
                                </div>
                            </div>

                        </div>

                        <div id="addCatgFormAlert" class="d-none col-lg-12 px-4 pt-4" role="alert"></div>

                        <div class="modal-footer border-top d-flex justify-content-end">
                            <button type="button" class="btn btn-label-danger">Cancel</button>
                            <button type="submit" class="btn btn-primary">Save changes</button>
                        </div>

                        <?php echo form_close(); ?>

                    </div>
                </div>
            </div>

            <?php $this->load->view('common/footer_desc'); ?>

        </div>

    </div>
</div>

<?php $this->load->view('common/footer'); ?>

<script src="/js/products.js"></script>

<script>
    $(function() {
        'use strict'

        $('#SearchDetails').val('');

        $('.TabPane').click(function(e) {
            e.preventDefault();
            var TabValue = $(this).data('id');
            if (TabValue) {
                $('#NewItem').addClass('d-none');
                $('#NewCategory').addClass('d-none');
                $('#SearchDetails').val('');
                PageNo = 0;
                Filter = {};
                if (TabValue == 'Item') {
                    $('#NewItem').removeClass('d-none');
                    getProductDetails(PageNo, RowLimit, Filter);
                } else if (TabValue == 'Categories') {
                    $('#NewCategory').removeClass('d-none');
                    getCategoriesDetails(PageNo, RowLimit, Filter);
                }
            }
        });

        $('.ProductsPagination').on('click', 'a', function(e) {
            e.preventDefault();
            PageNo = $(this).attr('data-ci-pagination-page');
            getProductDetails(PageNo, RowLimit, Filter);
        });

        $(document).on('click', '.PageRefresh', function(e) {
            e.preventDefault();
            let activeTab = $('.nav-pills .nav-link.active').attr('data-id');
            if (activeTab == 'Item') {
                getProductDetails(PageNo, RowLimit, Filter);
            } else if (activeTab == 'Categories') {
                getCategoriesDetails(PageNo, RowLimit, Filter);
            }
        });

        $('.SearchDetails').keyup(inputDelay(function(e) {
            PageNo = 0;
            if ($('#SearchDetails').val()) {
                Filter['Name'] = $('#SearchDetails').val();
            }
            let activeTab = $('.nav-pills .nav-link.active').attr('data-id');
            if (activeTab == 'Item') {
                getProductDetails(PageNo, RowLimit, Filter);
            } else if (activeTab == 'Categories') {
                getCategoriesDetails(PageNo, RowLimit, Filter);
            }
        }, 500));

        $(document).on('click', '.DeleteProduct', function(e) {
            e.preventDefault();
            var GetId = $(this).data('productuid');
            if (GetId) {
                Swal.fire({
                    title: "Do you want to delete the product?",
                    text: "You won't be able to revert this!",
                    icon: "info",
                    showCancelButton: true,
                    confirmButtonColor: "#d33",
                    confirmButtonText: "Yes, delete it!",
                    cancelButtonColor: "#3085d6",
                }).then((result) => {
                    if (result.isConfirmed) {
                        deleteProduct(GetId);
                    }
                });
            }
        });

        // Categories Page Coding Starts Here
        $(document).on('click', '.addCategory', function(e) {
            e.preventDefault();
            $('#AddCategoryForm').trigger('reset');
            $('#categoryModal').modal('show');
        });

        $('#AddCategoryForm').submit(function(e) {
            e.preventDefault();
            var formData = new FormData($('#AddCategoryForm')[0]);
            if (myDropzone.files.length > 0) {
                formData.append('UploadImage', myDropzone.files[0]);
            }
            addCategoryDetails(formData);
        });

    });
    $(window).on('load', function() {

        getProductDetails(PageNo, RowLimit, Filter);

    });
</script>