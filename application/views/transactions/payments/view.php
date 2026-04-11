<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php $this->load->view('common/header'); ?>

<div class="layout-wrapper layout-content-navbar">
    <div class="layout-container">

        <?php $this->load->view('common/menu_view'); ?>

        <div class="layout-page">
            <div class="content-wrapper">
                <div class="container-xxl flex-grow-1 container-p-y">

                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="bx bx-credit-card me-2"></i> Payments</h5>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="filterAllPayments">All</button>
                                <button type="button" class="btn btn-sm btn-outline-primary" id="filterSalePayments">Sales (Received)</button>
                                <button type="button" class="btn btn-sm btn-outline-info" id="filterPurchasePayments">Purchases (Made)</button>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover table-sm align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width:4%;">#</th>
                                        <th style="width:15%;">Transaction</th>
                                        <th style="width:15%;">Party</th>
                                        <th style="width:15%;">Payment Type</th>
                                        <th style="width:12%;">Reference</th>
                                        <th style="width:12%;" class="text-end">Amount</th>
                                        <th style="width:9%;" class="text-center">Status</th>
                                        <th style="width:11%;">Date</th>
                                        <th style="width:7%;" class="text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="paymentsTableBody">
                                    <?php echo $ModRowData; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="card-footer d-flex justify-content-between align-items-center">
                            <small class="text-muted">Total: <strong id="paymentsTotalCount"><?php echo number_format($ModAllCount); ?></strong> records</small>
                            <div id="paymentsPagination"><?php echo $ModPagination; ?></div>
                        </div>
                    </div>

                </div>
                <?php $this->load->view('common/footer_desc'); ?>
            </div>
        </div>

    </div>
</div>

<?php $this->load->view('common/footer'); ?>

<script>
const ModuleId = <?php echo $JwtData->ModuleUID; ?>;

$(function() {
    'use strict';

    function getPaymentsDetails(pageNo, filter) {
        pageNo = pageNo || 1;
        filter = filter || {};
        $.ajax({
            url    : '/payments/getPaymentsPageDetails/' + pageNo,
            method : 'POST',
            data   : { RowLimit: 10, Filter: filter },
            success: function(resp) {
                if (!resp.Error) {
                    $('#paymentsTableBody').html(resp.RecordHtmlData);
                    $('#paymentsPagination').html(resp.Pagination);
                    $('#paymentsTotalCount').text(Number(resp.TotalCount).toLocaleString());
                }
            }
        });
    }

    $(document).on('click', '.page-link[data-page]', function(e) {
        e.preventDefault();
        getPaymentsDetails($(this).data('page'));
    });

    $(document).on('click', '#filterAllPayments', function() {
        $(this).addClass('btn-secondary').removeClass('btn-outline-secondary');
        $('#filterSalePayments, #filterPurchasePayments').removeClass('btn-primary btn-info').addClass('btn-outline-primary btn-outline-info');
        getPaymentsDetails(1, {});
    });

    $(document).on('click', '#filterSalePayments', function() {
        $(this).addClass('btn-primary').removeClass('btn-outline-primary');
        $('#filterAllPayments').removeClass('btn-secondary').addClass('btn-outline-secondary');
        $('#filterPurchasePayments').removeClass('btn-info').addClass('btn-outline-info');
        getPaymentsDetails(1, { PartyType: 'C' });
    });

    $(document).on('click', '#filterPurchasePayments', function() {
        $(this).addClass('btn-info').removeClass('btn-outline-info');
        $('#filterAllPayments').removeClass('btn-secondary').addClass('btn-outline-secondary');
        $('#filterSalePayments').removeClass('btn-primary').addClass('btn-outline-primary');
        getPaymentsDetails(1, { PartyType: 'V' });
    });

    $(document).on('click', '.deletePayment', function() {
        var paymentUID = $(this).data('payment-uid');
        var $row = $(this).closest('tr');
        Swal.fire({
            title: 'Delete Payment?',
            text: 'This will permanently remove this payment record.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Delete',
            confirmButtonColor: '#d33',
        }).then(function(result) {
            if (result.isConfirmed) {
                $.ajax({
                    url   : '/payments/deletePayment',
                    method: 'POST',
                    data  : { PaymentUID: paymentUID },
                    success: function(resp) {
                        if (!resp.Error) {
                            $row.fadeOut(300, function() { $(this).remove(); });
                        } else {
                            Swal.fire('Error', resp.Message, 'error');
                        }
                    }
                });
            }
        });
    });

});
</script>
