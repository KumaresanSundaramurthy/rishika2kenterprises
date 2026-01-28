<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php $this->load->view('common/transactions/header'); ?>

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
                                            <a class="nav-link active" role="tab" data-bs-toggle="tab" data-bs-target="#NavAllQuotePage" aria-controls="NavAllQuotePage" aria-selected="true" href="#NavAllQuotePage">All Quotations</a>
                                        </li>
                                        <li class="nav-item dropdown">
                                            <a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="javascript: void(0);" role="button">Conversion</a>
                                            <ul class="dropdown-menu">
                                                <li><a class="dropdown-item" data-bs-toggle="tab" href="#quotation">Quotation</a></li>
                                                <li><a class="dropdown-item" data-bs-toggle="tab" href="#salesorder">Sales Order</a></li>
                                                <li><a class="dropdown-item" data-bs-toggle="tab" href="#invoice">Invoice</a></li>
                                            </ul>
                                        </li>
                                        <div id="detailedSearchDiv" class="input-group input-group-merge">
                                            <span class="input-group-text cursor-pointer" id="transSearchIcon"><i class="bx bx-search"></i></span>
                                            <input type="text" class="form-control form-control-sm searchTransaction" name="searchTransactionData" id="searchTransactionData" placeholder="Search quotation details..." data-toggle="tooltip" title="Search by Quotation Number OR Customer Name OR Mobile Number" />
                                        </div>
                                        <div class="dropdown">
                                            <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" 
                                                    id="dateFilterBtn" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="bi bi-calendar me-1"></i> Today
                                            </button>
                                            <ul class="dropdown-menu shadow" aria-labelledby="dateFilterBtn" style="width: 240px; max-height: 300px; overflow-y: auto;">
                                                <li><a class="dropdown-item date-option active" data-range="today">
                                                    <i class="bi bi-circle-fill text-primary me-2"></i>Today
                                                </a></li>
                                                <li><a class="dropdown-item date-option" data-range="yesterday">
                                                    <i class="bi bi-circle me-2"></i>Yesterday
                                                </a></li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li><a class="dropdown-item date-option" data-range="this_week">
                                                    <i class="bi bi-circle me-2"></i>This Week
                                                </a></li>
                                                <li><a class="dropdown-item date-option" data-range="last_week">
                                                    <i class="bi bi-circle me-2"></i>Last Week
                                                </a></li>
                                                <li><a class="dropdown-item date-option" data-range="last_7_days">
                                                    <i class="bi bi-circle me-2"></i>Last 7 Days
                                                </a></li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li><a class="dropdown-item date-option" data-range="this_month">
                                                    <i class="bi bi-circle me-2"></i>This Month
                                                </a></li>
                                                <li><a class="dropdown-item date-option" data-range="previous_month">
                                                    <i class="bi bi-circle me-2"></i>Previous Month
                                                </a></li>
                                                <li><a class="dropdown-item date-option" data-range="last_30_days">
                                                    <i class="bi bi-circle me-2"></i>Last 30 Days
                                                </a></li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li><a class="dropdown-item date-option" data-range="this_year">
                                                    <i class="bi bi-circle me-2"></i>This Year
                                                </a></li>
                                                <li><a class="dropdown-item date-option" data-range="last_year">
                                                    <i class="bi bi-circle me-2"></i>Last Year
                                                </a></li>
                                                <li><a class="dropdown-item date-option" data-range="last_quarter">
                                                    <i class="bi bi-circle me-2"></i>Last Quarter
                                                </a></li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li><a class="dropdown-item date-option fw-bold" data-range="fy_25_26">
                                                    <i class="bi bi-star-fill text-warning me-2"></i>FY 25-26
                                                </a></li>
                                            </ul>
                                        </div>
                                    </ul>
                                    <div class="d-flex mt-2 mt-md-0">
                                        <!-- <a href="javascript: void(0);" class="btn PageRefresh p-2 me-0" data-toggle="tooltip" data-bs-placement="top" title="Refresh Page"><i class="bx bx-refresh fs-4"></i></a> -->
                                        <!-- <a href="javascript: void(0);" id="btnPageSettings" class="btn p-2" data-toggle="tooltip" data-bs-placement="top" title="Page Column Settings"><i class="bx bx-cog fs-4"></i></a> -->
                                    <?php // $this->load->view('common/transactions/actions_bar'); ?>
                                        <a href="/quotations/create" class="btn btn-primary btn-sm px-3">Create Quotation</a>
                                    </div>
                                </div>
                                <div class="tab-content p-0">
                                    <div class="tab-pane fade show active" id="NavAllQuotePage" role="tabpanel">
                                        <div class="table-responsive text-nowrap h-100 tablecard">
                                            <table class="table table-hover" id="quotTable">
                                                <thead class="bg-body-tertiary">
                                                    <tr>
                                                        <th class="table-checkbox">
                                                            <div class="form-check">
                                                                <input class="form-check-input table-chkbox quotHeaderCheck" type="checkbox">
                                                            </div>
                                                        </th>
                                                        <th class="table-serialno <?php echo $JwtData->GenSettings->SerialNoDisplay == 1 ? '' : 'd-none'; ?>">S.No</th>
                                                        <th class="dateSortable" id="sortDate" data-toggle="tooltip" data-bs-placement="top" title="" data-bs-original-title="Click sorting ascending">Date <i class="bx bx-sort-alt-2 ms-1 cursor-pointer"></i></th>
                                                        <th># Quotation </th>
                                                        <th class="custname_sortable" id="sortCustName" data-toggle="tooltip" data-bs-placement="top" title="" data-bs-original-title="Click sorting ascending">Customer <i class="bx bx-sort-alt-2 ms-1 cursor-pointer"></i></th>
                                                        <th>Status 
                                                            <div class="dropdown d-inline">
                                                                <button class="btn btn-xs btn-outline-secondary dropdown-toggle p-1 ms-1" type="button" data-bs-toggle="dropdown">
                                                                    <i class="bx bx-filter"></i>
                                                                </button>
                                                                <ul class="dropdown-menu status-filter">
                                                                    <li><a class="dropdown-item status-option active" data-status="all">All</a></li>
                                                                    <li><a class="dropdown-item status-option" data-status="draft">Draft</a></li>
                                                                    <li><a class="dropdown-item status-option" data-status="sent">Sent</a></li>
                                                                    <li><a class="dropdown-item status-option" data-status="accepted">Accepted</a></li>
                                                                    <li><a class="dropdown-item status-option" data-status="converted">Converted</a></li>
                                                                </ul>
                                                            </div>
                                                        </th>
                                                        <th>Amount </th>
                                                        <th></th>
                                                    </tr>
                                                </thead>
                                                <tbody class="table-border-bottom-0">
                                                    <?php // echo $ModRowData; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                        <hr class="my-0" />
                                        <div class="row mx-3 justify-content-between quotPagination" id="quotPagination">
                                            <?php // echo $ModPagination; ?>
                                        </div>
                                    </div>
                                    <div class="tab-pane fade" id="quotation">
                                        <p>Quotation form fields...</p>
                                    </div>
                                    <div class="tab-pane fade" id="salesorder">
                                        <p>Sales Order form fields...</p>
                                    </div>
                                    <div class="tab-pane fade" id="invoice">
                                        <p>Invoice form fields...</p>
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

<?php $this->load->view('common/transactions/footer'); ?>

<script src="/js/transactions/quotations.js"></script>

<script>
let ModuleId = 101;
const ModuleTable = '#quotTable';
const ModulePag = '.quotPagination';
const ModuleHeader = '.quotHeaderCheck';
const ModuleRow = '.quotationCheck';
const ModuleFileName = 'Quotation_Data';
const ModuleSheetName = 'Quotation';
const previewName = 'Quotation Details';
let sortState = 0;
$(function() {
    'use strict'

    getQuotationsDetails(PageNo, RowLimit, Filter);

    $('.date-option').click(function() {
        getQuotationsDetails();
    });

    $('#searchTransactionData').on('keyup', function() {
        getQuotationsDetails();
    });

    $('.PageRefresh').click(function() {
        getQuotationsDetails();
    });

    $(document).on('click', '.date-option', function () {

        $('.date-option').removeClass('active');
        $(this).addClass('active');

        let range = $(this).data('range');
        let today = new Date();

        let fromDate = '';
        let toDate = '';

        switch (range) {

            case 'today':
                fromDate = toDate = formatDate(today);
                break;

            case 'yesterday':
                let y = new Date();
                y.setDate(today.getDate() - 1);
                fromDate = toDate = formatDate(y);
                break;

            case 'this_week':
                let startWeek = new Date(today);
                startWeek.setDate(today.getDate() - today.getDay());
                fromDate = formatDate(startWeek);
                toDate = formatDate(today);
                break;

            case 'last_week':
                let lwStart = new Date(today);
                lwStart.setDate(today.getDate() - today.getDay() - 7);
                let lwEnd = new Date(lwStart);
                lwEnd.setDate(lwStart.getDate() + 6);
                fromDate = formatDate(lwStart);
                toDate = formatDate(lwEnd);
                break;

            case 'last_7_days':
                let last7 = new Date(today);
                last7.setDate(today.getDate() - 6);
                fromDate = formatDate(last7);
                toDate = formatDate(today);
                break;

            case 'this_month':
                fromDate = formatDate(new Date(today.getFullYear(), today.getMonth(), 1));
                toDate = formatDate(today);
                break;

            case 'previous_month':
                fromDate = formatDate(new Date(today.getFullYear(), today.getMonth() - 1, 1));
                toDate = formatDate(new Date(today.getFullYear(), today.getMonth(), 0));
                break;
        }

        $('#dateFilterBtn').text($(this).text());
        $('#fromDate').val(fromDate);
        $('#toDate').val(toDate);

        console.log(fromDate, toDate);

        // 🔹 Call your CI3 filter here
        // loadData(fromDate, toDate);
    });

    function formatDate(date) {
        let d = String(date.getDate()).padStart(2, '0');
        let m = String(date.getMonth() + 1).padStart(2, '0');
        let y = date.getFullYear();
        return y + '-' + m + '-' + d;
    }

});
</script>