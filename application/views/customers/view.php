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
                                            <a class="nav-link active" role="tab" ata-bs-toggle="tab" data-bs-toggle="tab" data-bs-target="#NavAccountPage" aria-controls="NavAccountPage" aria-selected="true" href="javascript: void(0);"><i class="bx bx-user me-1"></i> Account</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" role="tab" ata-bs-toggle="tab" data-bs-toggle="tab" data-bs-target="#NavGroupPage" aria-controls="NavGroupPage" aria-selected="true" href="javascript: void(0);"><i class="bx bx-group me-1"></i> Groups</a>
                                        </li>
                                    </ul>
                                    <div class="d-flex mt-2 mt-md-0">
                                        <a href="javascript: void(0);" class="btn me-2"><i class='bx bx-plus'></i> New Customer</a>
                                        <div class="me-2"><input type="text" class="form-control" name="CustomerSearch" id="CustomerSearch" placeholder="Search customer..." /></div>
                                        <a href="/customers/add" class="btn btn-primary px-3"><i class='bx bx-plus'></i> New Customer</a>
                                    </div>
                                </div>
                                <div class="tab-content p-0">

                                    <div class="tab-pane fade show active" id="NavAccountPage" role="tabpanel">

                                        <div class="table-responsive text-nowrap h-100 tablecard">
                                            <table class="table table-sm table-striped table-hover" id="CustomersTable">
                                                <thead>
                                                    <tr>
                                                        <th></th>
                                                        <th>Name</th>
                                                        <th>Village</th>
                                                        <th>Contact Info</th>
                                                        <th class="text-end">Closing Balance</th>
                                                        <th class="text-end">Last Updated</th>
                                                        <th class="text-center">Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="table-border-bottom-0"></tbody>
                                            </table>
                                        </div>
                                        <hr class="my-0" />
                                        <div class="row mx-3 justify-content-between CustomersPagination" id="CustomersPagination"></div>

                                    </div>


                                    <div class="tab-pane fade" id="NavGroupPage" role="tabpanel">
                                        <p>
                                            Donut dragée jelly pie halvah. Danish gingerbread bonbon cookie wafer candy oat cake ice
                                            cream. Gummies halvah tootsie roll muffin biscuit icing dessert gingerbread. Pastry ice cream
                                            cheesecake fruitcake.
                                        </p>
                                        <p class="mb-0">
                                            Jelly-o jelly beans icing pastry cake cake lemon drops. Muffin muffin pie tiramisu halvah
                                            cotton candy liquorice caramels.
                                        </p>
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

<script src="/js/customers.js"></script>

<script>
    $(function() {
        'use strict'

        $('.CustomersPagination').on('click', 'a', function(e) {
            e.preventDefault();
            PageNo = $(this).attr('data-ci-pagination-page');
            getCustomersDetails(PageNo, RowLimit, Filter);
        });

        $(document).on('click', '.DeleteCustomer', function(e) {
            e.preventDefault();
            var GetId = $(this).data('customeruid');
            if (GetId) {
                Swal.fire({
                    title: "Do you want to delete the customer?",
                    text: "You won't be able to revert this!",
                    icon: "info",
                    showCancelButton: true,
                    confirmButtonColor: "#d33",
                    confirmButtonText: "Yes, delete it!",
                    cancelButtonColor: "#3085d6",
                }).then((result) => {
                    if (result.isConfirmed) {
                        deleteCustomer(GetId);
                    }
                });
            }
        });

    });
    $(window).on('load', function() {

        getCustomersDetails(PageNo, RowLimit, Filter);

    });
</script>