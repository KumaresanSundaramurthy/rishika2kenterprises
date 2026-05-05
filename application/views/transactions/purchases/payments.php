<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php $this->load->view('common/header'); ?>

<div class="layout-wrapper layout-horizontal layout-content-navbar">
    <div class="layout-container">

        <?php $this->load->view('common/menu_view'); ?>

        <div class="layout-page">
            <div class="content-wrapper">
                <div class="container-xxl flex-grow-1 container-p-y">

                    <?php
                    $cur      = htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹');
                    $dec      = (int)($JwtData->GenSettings->DecimalPoints ?? 2);
                    $totals   = $Totals ?? (object)['TotalReceived' => 0, 'TotalPaid' => 0];
                    $totalPaid = (float)($totals->TotalPaid ?? 0);

                    function ppFmt($val, $sym, $dec) {
                        return $sym . ' ' . number_format((float)$val, $dec, '.', ',');
                    }
                    ?>

                    <!-- ── Stat Cards ─────────────────────────────────────── -->
                    <div class="row g-3 mb-4">
                        <div class="col-6 col-md-3">
                            <div class="trans-stat-card stat-active">
                                <div class="trans-stat-label">Total Payments</div>
                                <div class="trans-stat-count"><?php echo number_format($ModAllCount); ?></div>
                                <div class="trans-stat-amount"><?php echo ppFmt($totalPaid, $cur, $dec); ?></div>
                                <i class="bx bx-money-withdraw trans-stat-icon"></i>
                            </div>
                        </div>
                    </div>

                    <!-- ── Main Card ──────────────────────────────────────── -->
                    <div class="card border-0 shadow-sm">

                        <!-- Toolbar -->
                        <div class="trans-toolbar d-flex align-items-center justify-content-between flex-wrap gap-2">

                            <!-- Tabs -->
                            <ul class="nav trans-status-tabs gap-1" id="ppStatusTabs">
                                <li class="nav-item">
                                    <a class="nav-link pp-tab active fw-semibold px-3 py-1" data-tab="Success" href="javascript:void(0);">
                                        Success <span class="badge bg-primary ms-1"><?php echo number_format($ModAllCount); ?></span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link pp-tab fw-semibold px-3 py-1 text-muted" data-tab="Cancelled" href="javascript:void(0);">
                                        Cancelled <span class="badge bg-secondary ms-1">0</span>
                                    </a>
                                </li>
                            </ul>

                            <!-- Right controls -->
                            <div class="d-flex align-items-center gap-2 flex-wrap py-2">
                                <a href="/purchases" class="btn btn-sm btn-outline-secondary">
                                    <i class="bx bx-arrow-back me-1"></i>Back to Purchases
                                </a>
                                <div class="input-group input-group-sm" style="width:210px;">
                                    <span class="input-group-text bg-white border-end-0"><i class="bx bx-search text-muted"></i></span>
                                    <input type="text" class="form-control border-start-0" id="ppSearch" placeholder="Search vendor, amount, ref…">
                                </div>
                                <div class="dropdown">
                                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button"
                                            id="ppDateFilterBtn" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="bx bx-calendar me-1"></i><span id="ppDateFilterLabel">All Dates</span>
                                    </button>
                                    <ul class="dropdown-menu shadow" id="ppDateFilterMenu"
                                        style="width:220px;max-height:360px;overflow-y:auto;font-size:.82rem;"></ul>
                                </div>
                            </div>
                        </div>

                        <!-- Table -->
                        <div class="table-responsive">
                            <table class="table trans-table table-hover MainviewTable mb-0" id="ppTable">
                                <thead class="r2k-thead">
                                    <tr>
                                        <th class="ps-3" style="width:140px;">Ref No</th>
                                        <th class="ps-3" style="width:130px;">Amount</th>
                                        <th style="width:150px;">Mode / Bank</th>
                                        <th style="width:130px;">Linked Bill</th>
                                        <th>Vendor</th>
                                        <th style="width:160px;">Recorded By</th>
                                        <th style="width:120px;" class="text-end pe-3">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="ppTableBody">
                                    <?php echo $ModRowData; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Footer -->
                        <div class="card-footer bg-white border-top d-flex flex-wrap justify-content-between align-items-center px-3 py-2 gap-3">
                            <div class="d-flex align-items-center gap-3">
                                <span class="text-muted" style="font-size:.8rem;">
                                    Total Paid Out: &nbsp;<strong class="text-danger" id="ppFooterPaid"><?php echo ppFmt($totalPaid, $cur, $dec); ?></strong>
                                </span>
                            </div>
                            <div id="ppPagination" class="d-flex align-items-center gap-3">
                                <span class="text-muted" style="font-size:.78rem;">Total: <strong id="ppTotalCount"><?php echo number_format($ModAllCount); ?></strong></span>
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

<!-- ── Payment Detail Modal (reused from payments page) ──────── -->
<div class="modal fade" id="paymentDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:540px;">
        <div class="modal-content border-0 shadow position-relative">
            <button type="button" class="btn-close position-absolute top-0 end-0 m-3 z-3" data-bs-dismiss="modal"
                    style="background-color:#fff;border-radius:50%;padding:8px;box-shadow:0 2px 6px rgba(0,0,0,.15);"></button>
            <div style="background:#fff3e0;border-left:4px solid #f57c00;border-radius:8px 8px 0 0;padding:16px 20px 14px;">
                <div class="d-flex align-items-center gap-3">
                    <div style="width:44px;height:44px;border-radius:10px;background:rgba(245,124,0,.12);display:flex;align-items:center;justify-content:center;">
                        <i class="bx bx-money-withdraw fs-4" style="color:#f57c00;"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="fw-bold text-dark" style="font-size:1rem;" id="pdUniqueNumber">—</div>
                        <div class="text-muted" style="font-size:.78rem;" id="pdDateLabel">—</div>
                    </div>
                    <div class="text-end">
                        <div class="fw-bold" style="font-size:1.2rem;color:#f57c00;" id="pdAmount">—</div>
                        <div id="pdModeBadge" class="mt-1"></div>
                    </div>
                </div>
            </div>
            <div class="modal-body px-4 py-3">
                <div style="background:#f8f9fa;border-radius:8px;padding:12px 14px;margin-bottom:12px;">
                    <div class="row g-2">
                        <div class="col-6">
                            <div class="text-muted" style="font-size:.7rem;text-transform:uppercase;letter-spacing:.05em;font-weight:600;">Vendor</div>
                            <div class="fw-semibold" style="font-size:.85rem;" id="pdParty">—</div>
                            <div class="text-muted" style="font-size:.72rem;" id="pdPartyMobile"></div>
                        </div>
                        <div class="col-6">
                            <div class="text-muted" style="font-size:.7rem;text-transform:uppercase;letter-spacing:.05em;font-weight:600;">Linked Bill</div>
                            <div class="fw-semibold text-primary" style="font-size:.85rem;" id="pdTransNumber">—</div>
                        </div>
                    </div>
                </div>
                <div id="pdBankSection" style="display:none;background:#f8f9fa;border-radius:8px;padding:12px 14px;margin-bottom:12px;">
                    <div class="text-muted mb-2" style="font-size:.7rem;text-transform:uppercase;letter-spacing:.05em;font-weight:600;">
                        <i class="bx bx-building-house me-1"></i>Bank Details
                    </div>
                    <div class="row g-2">
                        <div class="col-7">
                            <div class="text-muted" style="font-size:.7rem;">Bank / Account Name</div>
                            <div class="fw-semibold" style="font-size:.82rem;" id="pdBankName">—</div>
                        </div>
                        <div class="col-5">
                            <div class="text-muted" style="font-size:.7rem;">Account Number</div>
                            <div class="fw-semibold" style="font-size:.82rem;font-family:monospace;" id="pdAccountNumber">—</div>
                        </div>
                    </div>
                </div>
                <div class="row g-3">
                    <div class="col-6">
                        <div class="text-muted" style="font-size:.7rem;text-transform:uppercase;letter-spacing:.05em;font-weight:600;">Reference No</div>
                        <div style="font-size:.85rem;" id="pdReference">—</div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted" style="font-size:.7rem;text-transform:uppercase;letter-spacing:.05em;font-weight:600;">Recorded By</div>
                        <div style="font-size:.85rem;" id="pdCreatedBy">—</div>
                    </div>
                    <div class="col-12" id="pdNotesWrap" style="display:none;">
                        <div class="text-muted" style="font-size:.7rem;text-transform:uppercase;letter-spacing:.05em;font-weight:600;">Notes</div>
                        <div style="font-size:.85rem;color:#444;" id="pdNotes"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php $this->load->view('common/footer'); ?>

<script type="text/javascript" src="/js/common/datefilter.js"></script>

<style>
.pp-tab { border-radius: 6px; font-size: .82rem; transition: background .15s; }
.pp-tab.active { background: #fff3e0; color: #f57c00 !important; }
.pp-tab:not(.active):hover { background: #f5f5f5; }
.pmt-mode-badge { font-size: .7rem; padding: 2px 8px; border-radius: 20px; }
</style>

<script>
var PpFilter = {};
var PpPageNo = 1;
var PpLimit  = 10;

$(function () {
    'use strict';

    var sym = '<?php echo addslashes($cur); ?>';
    var dec = <?php echo $dec; ?>;
    var fmt = function (v) {
        return sym + ' ' + parseFloat(v || 0).toLocaleString('en-IN', { minimumFractionDigits: dec, maximumFractionDigits: dec });
    };

    function getPurchasePayments(pageNo) {
        pageNo   = pageNo || 1;
        PpPageNo = pageNo;
        $.ajax({
            url   : '/purchases/getPurchasePaymentsPageDetails/' + pageNo,
            method: 'POST',
            data  : { RowLimit: PpLimit, Filter: PpFilter, [CsrfName]: CsrfToken },
            success: function (resp) {
                if (!resp.Error) {
                    $('#ppTableBody').html(resp.RecordHtmlData);
                    $('#ppPagination').html(
                        '<span class="text-muted" style="font-size:.78rem;">Total: <strong>' +
                        Number(resp.TotalCount).toLocaleString() + '</strong></span>' +
                        (resp.Pagination || '')
                    );
                    if (resp.Totals) {
                        $('#ppFooterPaid').text(fmt(resp.Totals.TotalPaid || 0));
                    }
                }
            }
        });
    }

    // Tab switch
    $(document).on('click', '.pp-tab', function (e) {
        e.preventDefault();
        $('.pp-tab').removeClass('active');
        $(this).addClass('active');
        PpFilter.Status = $(this).data('tab') === 'Cancelled' ? 'Cancelled' : '';
        getPurchasePayments(1);
    });

    // Pagination
    $(document).on('click', '#ppPagination .page-link', function (e) {
        e.preventDefault();
        var m = ($(this).attr('href') || '').match(/\/(\d+)$/);
        if (m) getPurchasePayments(parseInt(m[1]));
    });

    // Search
    var ppSearchTimer;
    $('#ppSearch').on('input', function () {
        clearTimeout(ppSearchTimer);
        var v = $.trim($(this).val());
        ppSearchTimer = setTimeout(function () {
            PpFilter.Search = v;
            getPurchasePayments(1);
        }, 400);
    });

    // Date filter
    $('#ppDateFilterMenu').html(buildDateFilterHtml('ppCustomDateFrom', 'ppCustomDateTo'));
    initDateFilter({
        btnId  : 'ppDateFilterBtn',
        labelId: 'ppDateFilterLabel',
        fromId : 'ppCustomDateFrom',
        toId   : 'ppCustomDateTo',
        onApply: function (from, to) {
            PpFilter.DateFrom = from;
            PpFilter.DateTo   = to;
            getPurchasePayments(1);
        }
    });
    $(document).on('shown.bs.dropdown', '#ppDateFilterBtn', function () {
        if (!$('#ppCustomDateFrom').data('fpInit')) {
            flatpickr('#ppCustomDateFrom', { dateFormat: 'Y-m-d', altInput: true, altFormat: 'd M Y', maxDate: 'today', disableMobile: true });
            flatpickr('#ppCustomDateTo',   { dateFormat: 'Y-m-d', altInput: true, altFormat: 'd M Y', maxDate: 'today', disableMobile: true });
            $('#ppCustomDateFrom').data('fpInit', true);
        }
    });

    // View payment detail
    $(document).on('click', '.viewPaymentDetail', function () {
        var paymentUID = $(this).data('payment-uid');
        $.ajax({
            url   : '/payments/getPaymentDetail',
            method: 'POST',
            data  : { PaymentUID: paymentUID, [CsrfName]: CsrfToken },
            success: function (resp) {
                if (resp.Error) { Swal.fire('Error', resp.Message || 'Could not load payment.', 'error'); return; }
                var d = resp.Data;
                $('#pdUniqueNumber').text(d.UniqueNumber || ('Payment #' + d.PaymentUID));
                var dateStr = d.PaymentDate || (d.CreatedOn ? d.CreatedOn.slice(0, 10) : '');
                if (dateStr) {
                    var p = dateStr.split('-'), mo = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
                    dateStr = p[2] + ' ' + mo[parseInt(p[1], 10) - 1] + ' ' + p[0];
                }
                $('#pdDateLabel').text(dateStr || '—');
                $('#pdAmount').text(fmt(d.Amount));
                var modeMap = { 'cash':'#e8f5e9|#2e7d32','upi':'#ede7f6|#4527a0','card':'#e3f2fd|#1565c0','net banking':'#fff8e1|#f57f17','cheque':'#fce4ec|#880e4f' };
                var mk = (d.PaymentTypeName || '').toLowerCase().trim();
                var mc = modeMap[mk] ? modeMap[mk].split('|') : ['#f0f0f0','#555'];
                $('#pdModeBadge').html('<span class="pmt-mode-badge" style="background:' + mc[0] + ';color:' + mc[1] + ';">' + (d.PaymentTypeName || '—') + '</span>');
                $('#pdParty').text(d.PartyName || '—');
                $('#pdPartyMobile').text(d.PartyMobile || '').toggle(!!d.PartyMobile);
                $('#pdTransNumber').text(d.TransNumber || '—');
                if (d.BankName) {
                    $('#pdBankName').text(d.BankName + (d.AccountName ? ' (' + d.AccountName + ')' : ''));
                    $('#pdAccountNumber').text(d.AccountNumber || '—');
                    $('#pdBankSection').show();
                } else {
                    $('#pdBankSection').hide();
                }
                $('#pdReference').text(d.ReferenceNo || '—');
                $('#pdCreatedBy').text(d.CreatedByName || '—');
                $('#pdNotes').text(d.Notes || '');
                $('#pdNotesWrap').toggle(!!d.Notes);
                $('#paymentDetailModal').modal('show');
            }
        });
    });

    // Delete payment
    $(document).on('click', '.deletePayment', function () {
        var paymentUID = $(this).data('payment-uid');
        var $row = $(this).closest('tr');
        Swal.fire({
            title: 'Delete Payment?',
            text : 'This will permanently remove the payment and restore the purchase bill balance.',
            icon : 'warning', showCancelButton: true,
            confirmButtonText: 'Delete', confirmButtonColor: '#d33',
        }).then(function (r) {
            if (!r.isConfirmed) return;
            $.ajax({
                url   : '/payments/deletePayment',
                method: 'POST',
                data  : { PaymentUID: paymentUID, [CsrfName]: CsrfToken },
                success: function (resp) {
                    if (!resp.Error) {
                        $row.fadeOut(300, function () { $(this).remove(); });
                        Swal.fire({ icon: 'success', text: 'Payment deleted.', timer: 1500, showConfirmButton: false });
                    } else {
                        Swal.fire('Error', resp.Message, 'error');
                    }
                }
            });
        });
    });

});
</script>
