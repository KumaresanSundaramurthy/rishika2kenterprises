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
                                            <a class="nav-link active" role="tab" data-bs-toggle="tab" data-bs-target="#NavVendorPage" aria-controls="NavVendorPage" aria-selected="true" href="javascript: void(0);"><i class="bx bx-id-card me-1"></i> Vendor</a>
                                        </li>
                                    </ul>
                                    <div class="d-flex mt-2 mt-md-0">
                                        <a href="javascript: void(0);" class="btn PageRefresh"><i class='bx bx-refresh' style="font-size: 22px;"></i> </a>
                                        <div class="me-2"><input type="text" class="form-control VendorSearch" name="VendorSearch" id="VendorSearch" placeholder="Search vendor details..." /></div>
                                        <a href="/vendors/add" class="btn btn-primary px-3"><i class='bx bx-plus'></i> New Vendor</a>
                                    </div>
                                </div>
                                <div class="tab-content p-0">

                                    <div class="tab-pane fade show active" id="NavVendorPage" role="tabpanel">

                                        <div class="table-responsive text-nowrap h-100 tablecard">
                                            <table class="table table-sm table-striped table-hover" id="VendorsTable">
                                                <thead>
                                                    <tr>
                                                        <th></th>
                                                        <th>Name</th>
                                                        <th>Area</th>
                                                        <th class="text-center">Contact Info</th>
                                                        <th class="text-end">Closing Balance</th>
                                                        <th class="text-end">Last Updated</th>
                                                        <th class="text-center">Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="table-border-bottom-0">
                                                    <?php $PageData['VendorsList'] = [];
                                                    echo $this->load->view('vendors/list', $PageData, TRUE); ?>
                                                </tbody>
                                            </table>
                                        </div>
                                        <hr class="my-0" />
                                        <div class="row mx-3 justify-content-between VendorsPagination" id="VendorsPagination"></div>

                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>

                </div>

            </div>
            <!-- Content wrapper -->

            <?php $this->load->view('common/footer_desc'); ?>

        </div>

    </div>
</div>

<?php $this->load->view('common/footer'); ?>

<script src="/js/vendors.js"></script>

<script>
    $(function() {
        'use strict'

        $('#VendorSearch').val('');

        $('.VendorsPagination').on('click', 'a', function(e) {
            e.preventDefault();
            PageNo = $(this).attr('data-ci-pagination-page');
            getVendorsDetails(PageNo, RowLimit, Filter);
        });

        $(document).on('click', '.PageRefresh', function(e) {
            e.preventDefault();
            getVendorsDetails(0, RowLimit, Filter);
        });

        $('.VendorSearch').keyup(inputDelay(function(e) {
            PageNo = 0;
            if ($('#VendorSearch').val()) {
                Filter['Name'] = $('#VendorSearch').val();
            }
            getVendorsDetails(PageNo, RowLimit, Filter);
        }, 500));

        $(document).on('click', '.DeleteVendor', function(e) {
            e.preventDefault();
            var GetId = $(this).data('vendoruid');
            if (GetId) {
                Swal.fire({
                    title: "Do you want to delete the vendor?",
                    text: "You won't be able to revert this!",
                    icon: "info",
                    showCancelButton: true,
                    confirmButtonColor: "#d33",
                    confirmButtonText: "Yes, delete it!",
                    cancelButtonColor: "#3085d6",
                }).then((result) => {
                    if (result.isConfirmed) {
                        deleteVendor(GetId);
                    }
                });
            }
        });

    });
    $(window).on('load', function() {

        getVendorsDetails(PageNo, RowLimit, Filter);

    });
</script>