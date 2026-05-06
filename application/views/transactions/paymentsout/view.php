<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php $this->load->view('common/header'); ?>

<div class="layout-wrapper layout-horizontal layout-content-navbar">
    <div class="layout-container">

        <?php $this->load->view('common/menu_view'); ?>

        <div class="layout-page">
            <div class="content-wrapper">
                <div class="container-xxl flex-grow-1 container-p-y">

                    <?php
                    $cur       = htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹');
                    $dec       = (int)($JwtData->GenSettings->DecimalPoints ?? 2);
                    $totals    = $Totals ?? (object)['TotalReceived' => 0, 'TotalPaid' => 0];
                    $totalPaid = (float)($totals->TotalPaid ?? 0);
                    $summary      = $MethodSummary ?? [];
                    $bankAccounts = $BankAccounts ?? [];

                    function poutFmt($val, $sym) {
                        return $sym . ' ' . number_format((float)$val, 2, '.', ',');
                    }
                    ?>

                    <!-- ── Balance Summary Cards ──────────────────────────── -->
                    <div class="d-flex gap-3 mb-4 flex-wrap" id="poutSummaryCards">

                        <?php if (!empty($summary)): ?>
                            <?php foreach ($summary as $row): ?>
                            <?php
                                $balance  = (float)($row->TotalPaid ?? 0);
                                $label    = htmlspecialchars($row->AccountLabel ?? 'Cash');
                                $bankName = htmlspecialchars($row->BankName ?? '');
                            ?>
                            <div class="card border-0 shadow-sm pout-summary-card" style="min-width:200px;flex:1 1 180px;max-width:260px;">
                                <div class="card-body py-3 px-3">
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <?php if ($row->IsCash): ?>
                                            <i class="bx bx-money fs-5 text-success"></i>
                                        <?php else: ?>
                                            <i class="bx bx-building-house fs-5 text-primary"></i>
                                        <?php endif; ?>
                                        <span class="fw-semibold" style="font-size:.82rem;"><?php echo $label; ?></span>
                                        <i class="bx bx-down-arrow-alt text-danger ms-auto fs-5"></i>
                                    </div>
                                    <?php if ($bankName): ?>
                                        <div class="text-muted" style="font-size:.72rem;"><?php echo $bankName; ?></div>
                                    <?php endif; ?>
                                    <div class="fw-bold mt-1 text-danger" style="font-size:1.05rem;">
                                        <?php echo poutFmt($balance, $cur); ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <!-- No payments yet — show all bank accounts with ₹0 -->
                            <!-- Cash card -->
                            <div class="card border-0 shadow-sm pout-summary-card" style="min-width:200px;flex:1 1 180px;max-width:260px;">
                                <div class="card-body py-3 px-3">
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <i class="bx bx-money fs-5 text-success"></i>
                                        <span class="fw-semibold" style="font-size:.82rem;">Cash</span>
                                        <i class="bx bx-minus text-muted ms-auto fs-5"></i>
                                    </div>
                                    <div class="fw-bold mt-1 text-muted" style="font-size:1.05rem;"><?php echo poutFmt(0, $cur); ?></div>
                                </div>
                            </div>
                            <?php foreach ($bankAccounts as $ba): ?>
                                <?php if ($ba->IsCash) continue; ?>
                                <div class="card border-0 shadow-sm pout-summary-card" style="min-width:200px;flex:1 1 180px;max-width:260px;">
                                    <div class="card-body py-3 px-3">
                                        <div class="d-flex align-items-center gap-2 mb-1">
                                            <i class="bx bx-building-house fs-5 text-primary"></i>
                                            <span class="fw-semibold" style="font-size:.82rem;"><?php echo htmlspecialchars($ba->AccountName); ?></span>
                                            <i class="bx bx-minus text-muted ms-auto fs-5"></i>
                                        </div>
                                        <div class="text-muted" style="font-size:.72rem;"><?php echo htmlspecialchars($ba->BankName); ?></div>
                                        <div class="fw-bold mt-1 text-muted" style="font-size:1.05rem;"><?php echo poutFmt(0, $cur); ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>

                    </div>

                    <!-- ── Main Card ──────────────────────────────────────── -->
                    <div class="card border-0 shadow-sm">

                        <!-- Toolbar -->
                        <div class="trans-toolbar d-flex align-items-center justify-content-between flex-wrap gap-2">

                            <!-- Tabs -->
                            <ul class="nav trans-status-tabs gap-1" id="poutStatusTabs">
                                <li class="nav-item">
                                    <a class="nav-link pout-tab active fw-semibold px-3 py-1" data-tab="Success" href="javascript:void(0);">
                                        Success <span class="badge bg-danger ms-1" id="poutTabCountSuccess"><?php echo number_format($ModAllCount); ?></span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link pout-tab fw-semibold px-3 py-1 text-muted" data-tab="Cancelled" href="javascript:void(0);">
                                        Cancelled <span class="badge bg-secondary ms-1" id="poutTabCountCancelled">0</span>
                                    </a>
                                </li>
                            </ul>

                            <!-- Right controls -->
                            <div class="d-flex align-items-center gap-2 flex-wrap py-2">
                                <div class="input-group input-group-sm" style="width:210px;">
                                    <span class="input-group-text bg-white border-end-0"><i class="bx bx-search text-muted"></i></span>
                                    <input type="text" class="form-control border-start-0" id="poutSearch"
                                           placeholder="Vendor, amount, ref…">
                                </div>
                                <div class="dropdown">
                                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button"
                                            id="poutDateFilterBtn" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="bx bx-calendar me-1"></i><span id="poutDateFilterLabel">All Dates</span>
                                    </button>
                                    <ul class="dropdown-menu shadow" id="poutDateFilterMenu"
                                        style="width:220px;max-height:360px;overflow-y:auto;font-size:.82rem;"></ul>
                                </div>
                            </div>
                        </div>

                        <!-- Table -->
                        <div class="table-responsive">
                            <table class="table trans-table table-hover MainviewTable mb-0" id="poutTable">
                                <thead class="r2k-thead">
                                    <tr>
                                        <th class="ps-3" style="width:140px;">Ref No</th>
                                        <th class="ps-3" style="width:140px;">Amount Paid</th>
                                        <th style="width:150px;">Mode / Bank</th>
                                        <th style="width:140px;">Linked Bill</th>
                                        <th>Vendor</th>
                                        <th style="width:160px;">Recorded By</th>
                                        <th style="width:80px;" class="text-end pe-3">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="poutTableBody">
                                    <?php echo $ModRowData; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Footer -->
                        <div class="card-footer bg-white border-top d-flex flex-wrap justify-content-between align-items-center px-3 py-2 gap-3">
                            <div class="d-flex align-items-center gap-3">
                                <span class="text-muted" style="font-size:.8rem;">
                                    Total Paid Out: &nbsp;
                                    <strong class="text-danger" id="poutFooterTotal"><?php echo poutFmt($totalPaid, $cur); ?></strong>
                                </span>
                            </div>
                            <div id="poutPagination" class="d-flex align-items-center gap-3">
                                <span class="text-muted" style="font-size:.78rem;">
                                    Total: <strong id="poutTotalCount"><?php echo number_format($ModAllCount); ?></strong>
                                </span>
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

<!-- ── Payment Detail Modal ───────────────────────────────────── -->
<div class="modal fade" id="paymentDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:540px;">
        <div class="modal-content border-0 shadow position-relative">
            <button type="button" class="btn-close position-absolute top-0 end-0 m-3 z-3" data-bs-dismiss="modal"
                    style="background-color:#fff;border-radius:50%;padding:8px;box-shadow:0 2px 6px rgba(0,0,0,.15);"></button>

            <!-- Banner — orange theme for payment out -->
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
                        <div class="fw-bold text-danger" style="font-size:1.2rem;" id="pdAmount">—</div>
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
                        <div class="col-6" id="pdIfscWrap" style="display:none;">
                            <div class="text-muted" style="font-size:.7rem;">IFSC</div>
                            <div class="fw-semibold" style="font-size:.82rem;" id="pdIfsc">—</div>
                        </div>
                        <div class="col-6" id="pdBranchWrap" style="display:none;">
                            <div class="text-muted" style="font-size:.7rem;">Branch</div>
                            <div class="fw-semibold" style="font-size:.82rem;" id="pdBranch">—</div>
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

<script>
var PoutFilter = {};
var PoutPageNo = 1;
var PoutLimit  = 10;

$(function () {
    'use strict';

    var sym = '<?php echo addslashes($cur); ?>';
    var dec = <?php echo $dec; ?>;
    var fmt = function (v) {
        return sym + ' ' + parseFloat(v || 0).toLocaleString('en-IN', { minimumFractionDigits: dec, maximumFractionDigits: dec });
    };

    function getPaymentsOut(pageNo) {
        pageNo     = pageNo || 1;
        PoutPageNo = pageNo;
        $.ajax({
            url   : '/paymentsout/getPageDetails/' + pageNo,
            method: 'POST',
            data  : { RowLimit: PoutLimit, Filter: PoutFilter, [CsrfName]: CsrfToken },
            success: function (resp) {
                if (!resp.Error) {
                    $('#poutTableBody').html(resp.RecordHtmlData);
                    $('#poutPagination').html(
                        '<span class="text-muted" style="font-size:.78rem;">Total: <strong>' +
                        Number(resp.TotalCount).toLocaleString() + '</strong></span>' +
                        (resp.Pagination || '')
                    );
                    if (resp.Totals) {
                        $('#poutFooterTotal').text(fmt(resp.Totals.TotalPaid || 0));
                    }
                }
            }
        });
    }

    // Tab switch
    $(document).on('click', '.pout-tab', function (e) {
        e.preventDefault();
        $('.pout-tab').removeClass('active');
        $(this).addClass('active');
        PoutFilter.Status = $(this).data('tab') === 'Cancelled' ? 'Cancelled' : '';
        getPaymentsOut(1);
    });

    // Pagination
    $(document).on('click', '#poutPagination .page-link', function (e) {
        e.preventDefault();
        var m = ($(this).attr('href') || '').match(/\/(\d+)$/);
        if (m) getPaymentsOut(parseInt(m[1]));
    });

    // Search
    var poutSearchTimer;
    $('#poutSearch').on('input', function () {
        clearTimeout(poutSearchTimer);
        var v = $.trim($(this).val());
        poutSearchTimer = setTimeout(function () {
            PoutFilter.Search = v;
            getPaymentsOut(1);
        }, 400);
    });

    // Date filter
    $('#poutDateFilterMenu').html(buildDateFilterHtml('poutCustomDateFrom', 'poutCustomDateTo'));
    initDateFilter({
        btnId  : 'poutDateFilterBtn',
        labelId: 'poutDateFilterLabel',
        fromId : 'poutCustomDateFrom',
        toId   : 'poutCustomDateTo',
        onApply: function (from, to) {
            PoutFilter.DateFrom = from;
            PoutFilter.DateTo   = to;
            getPaymentsOut(1);
        }
    });
    $(document).on('shown.bs.dropdown', '#poutDateFilterBtn', function () {
        if (!$('#poutCustomDateFrom').data('fpInit')) {
            flatpickr('#poutCustomDateFrom', { dateFormat: 'Y-m-d', altInput: true, altFormat: 'd M Y', maxDate: 'today', disableMobile: true });
            flatpickr('#poutCustomDateTo',   { dateFormat: 'Y-m-d', altInput: true, altFormat: 'd M Y', maxDate: 'today', disableMobile: true });
            $('#poutCustomDateFrom').data('fpInit', true);
        }
    });

    // View payment detail — reads from data-* on the <tr>, zero AJAX
    $(document).on('click', '.viewPaymentDetail', function () {
        var $row = $(this).closest('tr.pmt-row');
        var fmt  = function (v) {
            return sym + ' ' + parseFloat(v || 0).toLocaleString('en-IN', { minimumFractionDigits: dec, maximumFractionDigits: dec });
        };

        // Banner
        $('#pdUniqueNumber').text($row.data('unique-number') || '—');
        var dateStr = ($row.data('payment-date') || '').toString().slice(0, 10);
        if (dateStr) {
            var p = dateStr.split('-'), mo = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
            dateStr = p[2] + ' ' + mo[parseInt(p[1], 10) - 1] + ' ' + p[0];
        }
        $('#pdDateLabel').text(dateStr || '—');
        $('#pdAmount').text(fmt($row.data('raw-amount')));

        // Mode badge
        var modeMap = { 'cash':'#e8f5e9|#2e7d32','upi':'#ede7f6|#4527a0','card':'#e3f2fd|#1565c0','net banking':'#fff8e1|#f57f17','cheque':'#fce4ec|#880e4f','emi':'#e0f7fa|#00695c','tds':'#f3e5f5|#6a1b9a' };
        var modeKey = ($row.data('payment-type') || '').toLowerCase().trim();
        var mc = modeMap[modeKey] ? modeMap[modeKey].split('|') : ['#f0f0f0','#555'];
        $('#pdModeBadge').html('<span class="pmt-mode-badge" style="background:' + mc[0] + ';color:' + mc[1] + ';">' + ($row.data('payment-type') || '—') + '</span>');

        // Party
        var mobile = $row.data('party-mobile') || '';
        $('#pdParty').text($row.data('party-name') || '—');
        $('#pdPartyMobile').text(mobile).toggle(!!mobile);
        $('#pdTransNumber').text($row.data('trans-number') || '—');

        // Bank
        var bankName = $row.data('bank-name') || '';
        if (bankName && !$row.data('is-cash')) {
            var acctName = $row.data('account-name') || '';
            $('#pdBankName').text(bankName + (acctName ? ' (' + acctName + ')' : ''));
            $('#pdAccountNumber').text($row.data('account-number') || '—');
            var ifsc = $row.data('ifsc') || '', branch = $row.data('branch') || '';
            $('#pdIfsc').text(ifsc);   $('#pdIfscWrap').toggle(!!ifsc);
            $('#pdBranch').text(branch); $('#pdBranchWrap').toggle(!!branch);
            $('#pdBankSection').show();
        } else {
            $('#pdBankSection').hide();
        }

        // Reference / By / Notes
        $('#pdReference').text($row.data('reference') || '—');
        $('#pdCreatedBy').text($row.data('created-by') || '—');
        var notes = $row.data('notes') || '';
        $('#pdNotes').text(notes);
        $('#pdNotesWrap').toggle(!!notes);

        $('#paymentDetailModal').modal('show');
    });

    // Delete payment
    $(document).on('click', '.deletePaymentOut', function () {
        var paymentUID = $(this).data('payment-uid');
        var amount     = $(this).data('amount');
        var $row       = $(this).closest('tr');
        Swal.fire({
            title: 'Delete Payment?',
            html : 'Delete payment of <strong>' + sym + ' ' + amount + '</strong>?<br>The purchase bill balance will be restored.',
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
                        getPaymentsOut(PoutPageNo);
                    } else {
                        Swal.fire('Error', resp.Message, 'error');
                    }
                }
            });
        });
    });

});
</script>
