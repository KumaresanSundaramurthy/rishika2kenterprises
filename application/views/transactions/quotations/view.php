<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php $this->load->view('common/transactions/header'); ?>

<div class="layout-wrapper layout-content-navbar">
    <div class="layout-container">

        <?php $this->load->view('common/menu_view'); ?>

        <div class="layout-page">

            <?php $this->load->view('common/navbar_view'); ?>

            <div class="content-wrapper">
                <div class="container-xxl flex-grow-1 container-p-y">

                    <div class="card">

                        <!-- ── Toolbar ─────────────────────────────── -->
                        <div class="card-header bg-body-tertiary d-flex flex-wrap justify-content-between align-items-center gap-2 py-2 border-bottom-0">

                            <!-- Status tabs -->
                            <ul class="nav nav-pills gap-1" id="quotStatusTabs" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link py-1 px-3 active quot-status-tab" data-status="All" href="#">
                                        All <span class="badge bg-secondary ms-1" id="quotTotalBadge"></span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link py-1 px-3 quot-status-tab" data-status="Open" href="#">Open</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link py-1 px-3 quot-status-tab" data-status="Closed" href="#">Closed</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link py-1 px-3 quot-status-tab" data-status="Partial" href="#">Partial</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link py-1 px-3 quot-status-tab" data-status="Cancelled" href="#">Cancelled</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link py-1 px-3 quot-status-tab" data-status="Draft" href="#">Drafts</a>
                                </li>
                            </ul>

                            <!-- Search + Date filter + Create -->
                            <div class="d-flex align-items-center gap-2">
                                <div class="input-group input-group-sm" style="width:220px">
                                    <span class="input-group-text" id="transSearchIcon"><i class="bx bx-search"></i></span>
                                    <input type="text" class="form-control" id="searchTransactionData" placeholder="Search..." title="Quotation Number or Customer Name">
                                </div>

                                <div class="dropdown">
                                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button"
                                            id="dateFilterBtn" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="bx bx-calendar me-1"></i><span id="dateFilterLabel">All Dates</span>
                                    </button>
                                    <ul class="dropdown-menu shadow" style="width:220px;max-height:320px;overflow-y:auto">
                                        <li><a class="dropdown-item date-option" data-range="">
                                            <i class="bx bx-list-ul me-2"></i>All Dates
                                        </a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item date-option" data-range="today"><i class="bx bx-circle me-2"></i>Today</a></li>
                                        <li><a class="dropdown-item date-option" data-range="yesterday"><i class="bx bx-circle me-2"></i>Yesterday</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item date-option" data-range="this_week"><i class="bx bx-circle me-2"></i>This Week</a></li>
                                        <li><a class="dropdown-item date-option" data-range="last_week"><i class="bx bx-circle me-2"></i>Last Week</a></li>
                                        <li><a class="dropdown-item date-option" data-range="last_7_days"><i class="bx bx-circle me-2"></i>Last 7 Days</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item date-option" data-range="this_month"><i class="bx bx-circle me-2"></i>This Month</a></li>
                                        <li><a class="dropdown-item date-option" data-range="previous_month"><i class="bx bx-circle me-2"></i>Previous Month</a></li>
                                        <li><a class="dropdown-item date-option" data-range="last_30_days"><i class="bx bx-circle me-2"></i>Last 30 Days</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item date-option" data-range="this_year"><i class="bx bx-circle me-2"></i>This Year</a></li>
                                        <li><a class="dropdown-item date-option" data-range="last_year"><i class="bx bx-circle me-2"></i>Last Year</a></li>
                                        <li><a class="dropdown-item date-option" data-range="last_quarter"><i class="bx bx-circle me-2"></i>Last Quarter</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item date-option fw-bold" data-range="fy_25_26">
                                            <i class="bx bxs-star text-warning me-2"></i>FY 25-26
                                        </a></li>
                                    </ul>
                                </div>

                                <a href="/quotations/create" class="btn btn-primary btn-sm px-3">
                                    <i class="bx bx-plus me-1"></i>Create
                                </a>
                            </div>
                        </div>

                        <!-- ── Table ───────────────────────────────── -->
                        <div class="table-responsive text-nowrap">
                            <table class="table table-hover mb-0" id="quotTable">
                                <thead class="bg-body-tertiary">
                                    <tr>
                                        <th class="table-checkbox" style="width:40px">
                                            <div class="form-check">
                                                <input class="form-check-input table-chkbox quotHeaderCheck" type="checkbox">
                                            </div>
                                        </th>
                                        <th class="<?php echo $JwtData->GenSettings->SerialNoDisplay == 1 ? '' : 'd-none'; ?> table-serialno" style="width:50px">S.No</th>
                                        <th class="col-sortable cursor-pointer user-select-none" data-sort="Number" style="width:140px">
                                            # Quotation <i class="bx bx-sort-alt-2 ms-1 sort-icon" data-col="Number"></i>
                                        </th>
                                        <th class="col-sortable cursor-pointer user-select-none text-end" data-sort="Amount" style="width:120px">
                                            Amount <i class="bx bx-sort-alt-2 ms-1 sort-icon" data-col="Amount"></i>
                                        </th>
                                        <th style="width:100px">Status</th>
                                        <th>Customer</th>
                                        <th class="col-sortable cursor-pointer user-select-none" data-sort="Date" style="width:100px">
                                            Date <i class="bx bx-sort-alt-2 ms-1 sort-icon" data-col="Date"></i>
                                        </th>
                                        <th style="width:100px">Valid Until</th>
                                        <th style="width:130px">Last Updated</th>
                                        <th style="width:50px"></th>
                                    </tr>
                                </thead>
                                <tbody class="table-border-bottom-0">
                                    <?php echo $ModRowData; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- ── Pagination ──────────────────────────── -->
                        <hr class="my-0">
                        <div class="row mx-3 my-2 justify-content-between align-items-center quotPagination" id="quotPagination">
                            <?php echo $ModPagination ? $ModPagination : ''; ?>
                        </div>

                    </div>

                </div>
            </div>

            <?php $this->load->view('common/footer_desc'); ?>

        </div>
    </div>
</div>

<?php $this->load->view('common/transactions/footer'); ?>

<script src="/js/transactions/quotations.js"></script>

<script>
const  ModuleId     = 101;
const  ModuleTable  = '#quotTable';
const  ModulePag    = '.quotPagination';
const  ModuleHeader = '.quotHeaderCheck';
const  ModuleRow    = '.quotationCheck';
$(function () {
    'use strict'

    Filter['Status'] = 'All';

    // Status tabs
    $(document).on('click', '.quot-status-tab', function (e) {
        e.preventDefault();
        $('.quot-status-tab').removeClass('active');
        $(this).addClass('active');
        Filter.Status = $(this).data('status') || '';
        PageNo = 1;
        getQuotationsDetails();
    });

    // Search
    $('#searchTransactionData').on('keyup', debounce(function () {
        Filter.Name = $.trim($(this).val());
        PageNo = 1;
        getQuotationsDetails();
    }, 400));

    // Date filter
    $(document).on('click', '.date-option', function () {
        $('.date-option').removeClass('active');
        $(this).addClass('active');
        var range = $(this).data('range') || '';
        var dates = getDateRange(range);
        $('#dateFilterLabel').text($.trim($(this).text()));
        Filter.DateFrom = dates.from;
        Filter.DateTo   = dates.to;
        PageNo = 1;
        getQuotationsDetails();
    });

    // Column sorting
    $(document).on('click', '.col-sortable', function () {
        var col = $(this).data('sort');
        if (Filter.SortBy === col) {
            Filter.SortDir = (Filter.SortDir === 'ASC') ? 'DESC' : 'ASC';
        } else {
            Filter.SortBy  = col;
            Filter.SortDir = 'DESC';
        }
        // Update sort icons
        $('.sort-icon').removeClass('bx-sort-up bx-sort-down').addClass('bx-sort-alt-2');
        var icon = Filter.SortDir === 'ASC' ? 'bx-sort-up' : 'bx-sort-down';
        $('.sort-icon[data-col="' + col + '"]').removeClass('bx-sort-alt-2').addClass(icon);
        PageNo = 1;
        getQuotationsDetails();
    });

    // Pagination
    $(document).on('click', '.quotPagination .page-link', function (e) {
        e.preventDefault();
        var href  = $(this).attr('href') || '';
        var match = href.match(/\/(\d+)$/);
        if (match) {
            PageNo = parseInt(match[1]);
            getQuotationsDetails();
        }
    });

    // Delete
    $(document).on('click', '.deleteQuotation', function () {
        var uid = $(this).data('uid');
        var num = $(this).data('num') || '';
        Swal.fire({
            title            : 'Delete Quotation?',
            html             : num ? 'Delete <strong>' + num + '</strong>? This cannot be undone.' : 'This cannot be undone.',
            icon             : 'warning',
            showCancelButton : true,
            confirmButtonText: 'Delete',
            confirmButtonColor: '#d33',
        }).then(function (result) {
            if (!result.isConfirmed) return;
            $.ajax({
                url    : '/quotations/deleteQuotation',
                method : 'POST',
                data   : { TransUID: uid, [CsrfName]: CsrfToken },
                success: function (resp) {
                    if (resp.Error) {
                        Swal.fire({ icon: 'error', text: resp.Message });
                    } else {
                        getQuotationsDetails();
                    }
                }
            });
        });
    });

    // Duplicate
    $(document).on('click', '.duplicateQuotation', function () {
        var uid = $(this).data('uid');
        Swal.fire({
            title            : 'Duplicate Quotation?',
            text             : 'A new draft copy will be created.',
            icon             : 'question',
            showCancelButton : true,
            confirmButtonText: 'Duplicate',
        }).then(function (result) {
            if (!result.isConfirmed) return;
            $.ajax({
                url    : '/quotations/duplicateQuotation',
                method : 'POST',
                data   : { TransUID: uid, [CsrfName]: CsrfToken },
                success: function (resp) {
                    if (resp.Error) {
                        Swal.fire({ icon: 'error', text: resp.Message });
                    } else {
                        Swal.fire({
                            icon             : 'success',
                            text             : resp.Message,
                            showCancelButton : true,
                            confirmButtonText: 'Edit Now',
                            cancelButtonText : 'Stay Here',
                        }).then(function (r) {
                            if (r.isConfirmed && resp.EditURL) {
                                window.location.href = resp.EditURL;
                            } else {
                                getQuotationsDetails();
                            }
                        });
                    }
                }
            });
        });
    });

    // Convert to Sales Order / Invoice
    $(document).on('click', '.convertToQuot', function () {
        var uid    = $(this).data('uid');
        var num    = $(this).data('num') || '';
        var target = $(this).data('target') || 'Invoice';
        var label  = target === 'SalesOrder' ? 'Sales Order' : 'Invoice';
        Swal.fire({
            title            : 'Convert to ' + label + '?',
            html             : num ? '<strong>' + num + '</strong> will be marked as Converted.' : '',
            icon             : 'question',
            showCancelButton : true,
            confirmButtonText: 'Convert',
            confirmButtonColor: '#28a745',
        }).then(function (result) {
            if (!result.isConfirmed) return;
            $.ajax({
                url    : '/quotations/convertQuotationToInvoice',
                method : 'POST',
                data   : { TransUID: uid, ConvertTarget: target, [CsrfName]: CsrfToken },
                success: function (resp) {
                    if (resp.Error) {
                        Swal.fire({ icon: 'error', text: resp.Message });
                    } else {
                        getQuotationsDetails();
                        Swal.fire({ icon: 'success', text: resp.Message, timer: 1800, showConfirmButton: false });
                    }
                }
            });
        });
    });

    // ── Add Quotation form submit ─────────────────────────
    var $form = $('#addQuotationForm');
    if ($form.length) {

        $form.on('submit', function (e) {
            e.preventDefault();

            var $btn     = $('button[type="submit"][name="action"]:focus, button[type="submit"][name="action"].active-submit', $form);
            var action   = $btn.val() || 'save';
            var csrfName = $form.data('csrf');
            var csrfVal  = $form.data('csrf-value');

            // ── Client-side validation ──────────────────
            var customerUID = parseInt($('#customerSearch').val(), 10);
            if (!customerUID || customerUID <= 0) {
                return showFormError('Please select a customer.');
            }

            var prefixUID = parseInt($('#transPrefixSelect').val(), 10);
            if (!prefixUID || prefixUID <= 0) {
                return showFormError('Please select a quotation prefix.');
            }

            var transNumber = $.trim($('#transNumber').val());
            if (!transNumber || parseInt(transNumber, 10) <= 0) {
                return showFormError('Transaction number must be greater than 0.');
            }

            var transDate = $.trim($('#transDate').val());
            if (!transDate || !/^\d{4}-\d{2}-\d{2}$/.test(transDate)) {
                return showFormError('Please enter a valid transaction date.');
            }

            var validityDate = $.trim($('#validityDate').val());
            if (validityDate && !/^\d{4}-\d{2}-\d{2}$/.test(validityDate)) {
                return showFormError('Validity date format is invalid.');
            }

            var items = typeof billManager !== 'undefined' ? billManager.getAllItems() : [];
            if (!items || items.length === 0) {
                return showFormError('Please add at least one product.');
            }

            // Validate each item row
            for (var i = 0; i < items.length; i++) {
                var item = items[i];
                var qty  = parseFloat(item.quantity);
                if (!qty || qty <= 0 || !Number.isInteger(qty)) {
                    return showFormError('Row ' + (i + 1) + ': Quantity must be a positive whole number.');
                }
                if (parseFloat(item.unitPrice) < 0) {
                    return showFormError('Row ' + (i + 1) + ': Selling price cannot be negative.');
                }
                var tax = parseFloat(item.taxPercent || 0);
                if (tax < 0 || tax > 100) {
                    return showFormError('Row ' + (i + 1) + ': Tax percentage must be between 0 and 100.');
                }
            }

            var extraDiscount = parseFloat($('#extraDiscount').val()) || 0;
            if (extraDiscount < 0) {
                return showFormError('Extra discount cannot be negative.');
            }

            // ── Build summary from BillManager ─────────
            var bm       = typeof billManager !== 'undefined' ? billManager : null;
            var summary  = bm ? bm.summary : {};
            var netAmount = summary.totals ? (summary.totals.grandTotal || 0) : 0;

            var subTotal          = summary.items       ? (summary.items.taxableAmount    || 0) : 0;
            var discountAmtTotal  = summary.items       ? (summary.items.discountTotal    || 0) : 0;
            var taxAmtTotal       = summary.taxTotals   ? (summary.taxTotals.totalTax     || 0) : 0;
            var cgstAmtTotal      = summary.taxTotals   ? (summary.taxTotals.cgstTotal    || 0) : 0;
            var sgstAmtTotal      = summary.taxTotals   ? (summary.taxTotals.sgstTotal    || 0) : 0;
            var igstAmtTotal      = summary.taxTotals   ? (summary.taxTotals.igstTotal    || 0) : 0;
            var addChargesTotal   = (summary.additionalCharges && summary.additionalCharges.total)
                                        ? (summary.additionalCharges.total.grossAmount || 0) : 0;
            var globalDiscPct     = bm ? (bm.globalDiscountPercent || 0) : 0;
            var roundOff          = summary.extra       ? (summary.extra.roundOff          || 0) : 0;

            // ── Build POST data ─────────────────────────
            var charges = {};
            if (summary.additionalCharges) {
                ['shipping', 'handling', 'packing', 'other'].forEach(function (t) {
                    var c = summary.additionalCharges[t];
                    if (c && c.grossAmount > 0) {
                        charges[t + 'Amount'] = c.grossAmount;
                        charges[t + 'Tax']    = c.taxPercent || 0;
                    }
                });
            }

            var postData = $.extend({
                transPrefixSelect      : prefixUID,
                transNumber            : transNumber,
                transDate              : transDate,
                validityDate           : validityDate,
                validityDays           : parseInt($('#validityDays').val(), 10) || 0,
                customerSearch         : customerUID,
                quotationType          : $('#quotationType').val() || '',
                dispatchFrom           : $('#dispatchFrom').val() || '',
                referenceDetails       : $.trim($('#referenceDetails').val()),
                transNotes             : $.trim($('#transNotes').val()),
                transTermsCond         : $.trim($('#transTermsCond').val()),
                extraDiscount          : extraDiscount,
                extDiscountType        : $('#extDiscountType').val() || '',
                SubTotal               : subTotal,
                DiscountAmount         : discountAmtTotal,
                TaxAmount              : taxAmtTotal,
                CgstAmount             : cgstAmtTotal,
                SgstAmount             : sgstAmtTotal,
                IgstAmount             : igstAmtTotal,
                AdditionalChargesTotal : addChargesTotal,
                GlobalDiscPercent      : globalDiscPct,
                RoundOff               : roundOff,
                NetAmount              : netAmount,
                Items                  : JSON.stringify(items),
                action                 : action,
                [csrfName]             : csrfVal,
            }, charges);

            setFormLoading(true);

            $.ajax({
                url     : '/quotations/addQuotation',
                method  : 'POST',
                data    : postData,
                cache   : false,
                success : function (response) {
                    setFormLoading(false);
                    if (response.Error) {
                        showFormError(response.Message);
                    } else {
                        Swal.fire({
                            icon             : 'success',
                            title            : 'Quotation Saved',
                            text             : response.Message || 'Quotation created successfully.',
                            confirmButtonText: 'OK',
                        }).then(function () {
                            window.location.href = '/quotations';
                        });
                    }
                },
                error: function () {
                    setFormLoading(false);
                    showFormError('Server error. Please try again.');
                }
            });
        });

        // Track which submit button was clicked
        $form.on('click', 'button[type="submit"][name="action"]', function () {
            $form.find('button[type="submit"][name="action"]').removeClass('active-submit');
            $(this).addClass('active-submit');
        });

    }

});
</script>