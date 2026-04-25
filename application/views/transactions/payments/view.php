<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php $this->load->view('common/header'); ?>

<div class="layout-wrapper layout-content-navbar">
    <div class="layout-container">

        <?php $this->load->view('common/menu_view'); ?>

        <div class="layout-page">
            <div class="content-wrapper">
                <div class="container-xxl flex-grow-1 container-p-y">

                    <?php
                    $cur      = htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹');
                    $dec      = (int)($JwtData->GenSettings->DecimalPoints ?? 2);
                    $summary  = $MethodSummary ?? [];
                    $totals   = $Totals ?? (object)['TotalReceived' => 0, 'TotalPaid' => 0];
                    $received = (float)($totals->TotalReceived ?? 0);
                    $paid     = (float)($totals->TotalPaid ?? 0);
                    $net      = $received - $paid;

                    function pmtFmt($val, $sym, $dec) {
                        return $sym . ' ' . number_format((float)$val, $dec, '.', ',');
                    }
                    ?>

                    <!-- ── Balance Summary Cards ──────────────────────────── -->
                    <div class="d-flex gap-3 mb-4 flex-wrap" id="pmtSummaryCards">

                        <?php foreach ($summary as $row): ?>
                        <?php
                            $balance  = (float)$row->NetBalance;
                            $isPos    = $balance >= 0;
                            $label    = htmlspecialchars($row->AccountLabel ?? 'Cash');
                            $bankName = htmlspecialchars($row->BankName ?? '');
                        ?>
                        <div class="card border-0 shadow-sm pmt-summary-card" style="min-width:200px;flex:1 1 180px;max-width:260px;">
                            <div class="card-body py-3 px-3">
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <?php if ($row->IsCash): ?>
                                        <i class="bx bx-money fs-5 text-success"></i>
                                    <?php else: ?>
                                        <i class="bx bx-building-house fs-5 text-primary"></i>
                                    <?php endif; ?>
                                    <span class="fw-semibold" style="font-size:.82rem;"><?php echo $label; ?></span>
                                    <i class="bx <?php echo $isPos ? 'bx-up-arrow-alt text-success' : 'bx-down-arrow-alt text-danger'; ?> ms-auto fs-5"></i>
                                </div>
                                <?php if ($bankName): ?>
                                    <div class="text-muted" style="font-size:.72rem;"><?php echo $bankName; ?></div>
                                <?php endif; ?>
                                <div class="fw-bold mt-1 <?php echo $isPos ? 'text-dark' : 'text-danger'; ?>" style="font-size:1.05rem;">
                                    <?php echo pmtFmt($balance, $cur, $dec); ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>

                        <?php if (empty($summary)): ?>
                        <div class="text-muted small py-2">No payment data yet.</div>
                        <?php endif; ?>

                    </div>

                    <!-- ── Main Card ──────────────────────────────────────── -->
                    <div class="card border-0 shadow-sm">

                        <!-- Tabs + Toolbar -->
                        <div class="card-header bg-white border-bottom px-3 py-0 d-flex align-items-center justify-content-between flex-wrap gap-2">

                            <!-- Status tabs -->
                            <ul class="nav pmt-status-tabs gap-1 py-2" style="border:none;">
                                <li class="nav-item">
                                    <a class="nav-link pmt-tab active fw-semibold px-3 py-1" data-tab="Success" href="javascript:void(0);">
                                        Success <span class="badge bg-primary ms-1" id="pmtTabCountSuccess"><?php echo number_format($ModAllCount); ?></span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link pmt-tab fw-semibold px-3 py-1 text-muted" data-tab="Cancelled" href="javascript:void(0);">
                                        Cancelled <span class="badge bg-secondary ms-1" id="pmtTabCountCancelled">0</span>
                                    </a>
                                </li>
                            </ul>

                            <!-- Right controls -->
                            <div class="d-flex align-items-center gap-2 flex-wrap py-2">
                                <div class="input-group input-group-sm" style="width:200px;">
                                    <span class="input-group-text bg-white border-end-0"><i class="bx bx-search text-muted"></i></span>
                                    <input type="text" class="form-control border-start-0" id="pmtSearch" placeholder="Search payment, party, amount…">
                                </div>
                                <div class="d-flex align-items-center gap-1 border rounded px-2 py-1 bg-white" style="font-size:.8rem;cursor:pointer;" id="pmtDateRangeWrap">
                                    <input type="date" class="border-0 bg-transparent p-0" id="pmtDateFrom" style="font-size:.78rem;width:108px;" title="From date">
                                    <span class="text-muted mx-1">→</span>
                                    <input type="date" class="border-0 bg-transparent p-0" id="pmtDateTo" style="font-size:.78rem;width:108px;" title="To date">
                                    <i class="bx bx-calendar text-muted ms-1"></i>
                                </div>
                                <div class="dropdown">
                                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                        Actions
                                    </button>
                                    <ul class="dropdown-menu shadow-sm" style="font-size:.82rem;">
                                        <li><a class="dropdown-item" href="javascript:void(0);" id="pmtFilterSales"><i class="bx bx-upload me-2 text-success"></i>Sales (Received)</a></li>
                                        <li><a class="dropdown-item" href="javascript:void(0);" id="pmtFilterPurchases"><i class="bx bx-download me-2 text-danger"></i>Purchases (Paid)</a></li>
                                        <li><hr class="dropdown-divider my-1"></li>
                                        <li><a class="dropdown-item" href="javascript:void(0);" id="pmtClearFilter"><i class="bx bx-x me-2 text-muted"></i>Clear Filters</a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Table -->
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" style="font-size:.82rem;">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3" style="width:130px;">Amount</th>
                                        <th style="width:100px;">Mode</th>
                                        <th style="width:130px;">Linked Documents</th>
                                        <th>Party Name</th>
                                        <th style="width:150px;">Date / Created Time</th>
                                        <th style="width:180px;">Bank Details</th>
                                        <th style="width:130px;">Created By</th>
                                        <th style="width:120px;" class="text-end pe-3">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="paymentsTableBody">
                                    <?php echo $ModRowData; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Footer with totals -->
                        <div class="card-footer bg-white border-top d-flex flex-wrap justify-content-between align-items-center px-3 py-2 gap-3">
                            <div class="d-flex align-items-center gap-3">
                                <span class="text-muted" style="font-size:.8rem;">
                                    Net Balance &nbsp;<strong class="text-dark" id="pmtFooterNet"><?php echo pmtFmt($net, $cur, $dec); ?></strong>
                                </span>
                                <span class="text-muted" style="font-size:.8rem;">
                                    You Received: &nbsp;<strong class="text-success" id="pmtFooterReceived"><?php echo pmtFmt($received, $cur, $dec); ?></strong>
                                </span>
                                <span class="text-muted" style="font-size:.8rem;">
                                    You Gave: &nbsp;<strong class="text-danger" id="pmtFooterPaid"><?php echo pmtFmt($paid, $cur, $dec); ?></strong>
                                </span>
                            </div>
                            <div id="pmtPagination" class="d-flex align-items-center gap-3">
                                <span class="text-muted" style="font-size:.78rem;">Total: <strong id="pmtTotalCount"><?php echo number_format($ModAllCount); ?></strong></span>
                                <?php echo $ModPagination; ?>
                            </div>
                        </div>

                    </div>

                </div>
                <?php $this->load->view('common/footer_desc'); ?>
            </div>
        </div>

    </div>
</div>

<?php $this->load->view('common/footer'); ?>

<style>
.pmt-tab { border-radius: 6px; font-size: .82rem; transition: background .15s; }
.pmt-tab.active { background: #f0f4ff; color: #0d6efd !important; }
.pmt-tab:not(.active):hover { background: #f5f5f5; }
.pmt-summary-card { transition: box-shadow .15s; }
.pmt-summary-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,.10) !important; }
.pmt-mode-badge { font-size: .7rem; padding: 2px 8px; border-radius: 20px; }
</style>

<script>
const PmtModuleId = <?php echo (int)$JwtData->ModuleUID; ?>;
var PmtFilter  = {};
var PmtPageNo  = 1;
var PmtLimit   = 10;

$(function () {
    'use strict';

    function getPaymentsDetails(pageNo, append) {
        pageNo = pageNo || 1;
        PmtPageNo = pageNo;
        $.ajax({
            url    : '/payments/getPaymentsPageDetails/' + pageNo,
            method : 'POST',
            data   : { RowLimit: PmtLimit, Filter: PmtFilter, [CsrfName]: CsrfToken },
            success: function (resp) {
                if (!resp.Error) {
                    $('#paymentsTableBody').html(resp.RecordHtmlData);
                    $('#pmtPagination').html(
                        '<span class="text-muted" style="font-size:.78rem;">Total: <strong id="pmtTotalCount">' +
                        Number(resp.TotalCount).toLocaleString() + '</strong></span>' +
                        (resp.Pagination || '')
                    );
                    if (resp.Totals) {
                        var sym = '<?php echo addslashes($cur); ?>';
                        var dec = <?php echo $dec; ?>;
                        var fmt = function(v) { return sym + ' ' + parseFloat(v).toLocaleString('en-IN', {minimumFractionDigits: dec, maximumFractionDigits: dec}); };
                        $('#pmtFooterReceived').text(fmt(resp.Totals.TotalReceived || 0));
                        $('#pmtFooterPaid').text(fmt(resp.Totals.TotalPaid || 0));
                        $('#pmtFooterNet').text(fmt((resp.Totals.TotalReceived || 0) - (resp.Totals.TotalPaid || 0)));
                    }
                }
            }
        });
    }

    // Tab switch
    $(document).on('click', '.pmt-tab', function (e) {
        e.preventDefault();
        $('.pmt-tab').removeClass('active');
        $(this).addClass('active');
        PmtFilter.Status = $(this).data('tab') === 'Cancelled' ? 'Cancelled' : '';
        PmtPageNo = 1;
        getPaymentsDetails(1);
    });

    // Pagination
    $(document).on('click', '#pmtPagination .page-link', function (e) {
        e.preventDefault();
        var m = ($(this).attr('href') || '').match(/\/(\d+)$/);
        if (m) getPaymentsDetails(parseInt(m[1]));
    });

    // Search
    var pmtSearchTimer;
    $('#pmtSearch').on('input', function () {
        clearTimeout(pmtSearchTimer);
        var v = $.trim($(this).val());
        pmtSearchTimer = setTimeout(function () {
            PmtFilter.Search = v;
            PmtPageNo = 1;
            getPaymentsDetails(1);
        }, 400);
    });

    // Date range
    $('#pmtDateFrom, #pmtDateTo').on('change', function () {
        PmtFilter.DateFrom = $('#pmtDateFrom').val() || '';
        PmtFilter.DateTo   = $('#pmtDateTo').val()   || '';
        PmtPageNo = 1;
        getPaymentsDetails(1);
    });

    // Actions dropdown filters
    $('#pmtFilterSales').on('click', function () {
        PmtFilter.PartyType = 'C';
        PmtPageNo = 1;
        getPaymentsDetails(1);
    });
    $('#pmtFilterPurchases').on('click', function () {
        PmtFilter.PartyType = 'V';
        PmtPageNo = 1;
        getPaymentsDetails(1);
    });
    $('#pmtClearFilter').on('click', function () {
        PmtFilter = {};
        $('#pmtSearch').val('');
        $('#pmtDateFrom, #pmtDateTo').val('');
        $('.pmt-tab').removeClass('active');
        $('.pmt-tab[data-tab="Success"]').addClass('active');
        PmtPageNo = 1;
        getPaymentsDetails(1);
    });

    // Delete payment
    $(document).on('click', '.deletePayment', function () {
        var paymentUID = $(this).data('payment-uid');
        var $row = $(this).closest('tr');
        Swal.fire({
            title: 'Delete Payment?',
            text : 'This will remove the payment record.',
            icon : 'warning',
            showCancelButton: true,
            confirmButtonText: 'Delete',
            confirmButtonColor: '#d33',
        }).then(function (result) {
            if (!result.isConfirmed) return;
            $.ajax({
                url   : '/payments/deletePayment',
                method: 'POST',
                data  : { PaymentUID: paymentUID, [CsrfName]: CsrfToken },
                success: function (resp) {
                    if (!resp.Error) {
                        $row.fadeOut(300, function () { $(this).remove(); });
                    } else {
                        Swal.fire('Error', resp.Message, 'error');
                    }
                }
            });
        });
    });

});
</script>
