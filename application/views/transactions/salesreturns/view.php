<?php defined('BASEPATH') or exit('No direct script access allowed');
$this->load->view('common/transactions/header'); ?>

<div class="layout-wrapper layout-horizontal layout-content-navbar">
    <div class="layout-container">

        <?php $this->load->view('common/menu_view'); ?>

        <div class="layout-page">

            <div class="content-wrapper">
                <div class="container-xxl flex-grow-1 container-p-y">

                    <?php
                    $stats       = $SummaryStats ?? [];
                    $cur         = htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹');
                    $dec         = $JwtData->GenSettings->DecimalPoints ?? 2;

                    // All = active returns only (Approved + Partial + Paid) — excludes Draft, Cancelled, Rejected
                    $activeStatuses = ['Approved', 'Partial', 'Paid'];
                    $cntAll      = array_sum(array_map(fn($s) => $stats[$s]['count']  ?? 0, $activeStatuses));
                    $amtAll      = array_sum(array_map(fn($s) => $stats[$s]['amount'] ?? 0, $activeStatuses));

                    $cntPending  = ($stats['Approved']['count']  ?? 0) + ($stats['Partial']['count']  ?? 0);
                    $amtPending  = ($stats['Approved']['amount'] ?? 0) + ($stats['Partial']['amount'] ?? 0);

                    $cntPaid     = $stats['Paid']['count']  ?? 0;
                    $amtPaid     = $stats['Paid']['amount'] ?? 0;

                    $cntDraft    = $stats['Draft']['count']  ?? 0;
                    $amtDraft    = $stats['Draft']['amount'] ?? 0;

                    function fmtAmt($val, $sym, $dec) {
                        return $sym . ' ' . number_format((float)$val, $dec, '.', ',');
                    }
                    ?>

                    <!-- ── Page Header ──────────────────────────────────────── -->
                    <div class="trans-page-header">
                        <div class="d-flex align-items-center gap-3">
                            <div class="trans-ph-icon" style="background:#d1fae5;">
                                <i class="bx bx-undo" style="color:#10b981;"></i>
                            </div>
                            <h5 class="trans-ph-title">Sales Returns</h5>
                        </div>
                        <a href="/salesreturns/create" class="btn btn-primary">
                            <i class="bx bx-plus me-1"></i>New Sales Return
                        </a>
                    </div>

                    <!-- ── Stat Cards ────────────────────────────────────── -->
                    <div class="trans-stats-section">
                        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;">
                            <a href="javascript:void(0);" class="trans-stat-card stat-all active-stat" data-stat-filter="All">
                                <div class="tsc-icon-wrap"><i class="bx bx-undo"></i></div>
                                <div class="tsc-body">
                                    <div class="trans-stat-label">All Returns</div>
                                    <div class="trans-stat-count"><?php echo number_format($cntAll); ?></div>
                                    <div class="trans-stat-amount"><?php echo fmtAmt($amtAll, $cur, $dec); ?></div>
                                </div>
                            </a>
                            <a href="javascript:void(0);" class="trans-stat-card stat-active" data-stat-filter="SRPending">
                                <div class="tsc-icon-wrap"><i class="bx bx-check-circle"></i></div>
                                <div class="tsc-body">
                                    <div class="trans-stat-label">Pending</div>
                                    <div class="trans-stat-count"><?php echo number_format($cntPending); ?></div>
                                    <div class="trans-stat-amount"><?php echo fmtAmt($amtPending, $cur, $dec); ?></div>
                                </div>
                            </a>
                            <a href="javascript:void(0);" class="trans-stat-card stat-paid" data-stat-filter="Paid">
                                <div class="tsc-icon-wrap"><i class="bx bx-x-circle"></i></div>
                                <div class="tsc-body">
                                    <div class="trans-stat-label">Paid</div>
                                    <div class="trans-stat-count"><?php echo number_format($cntPaid); ?></div>
                                    <div class="trans-stat-amount"><?php echo fmtAmt($amtPaid, $cur, $dec); ?></div>
                                </div>
                            </a>
                            <a href="javascript:void(0);" class="trans-stat-card stat-draft" data-stat-filter="Draft">
                                <div class="tsc-icon-wrap"><i class="bx bx-pencil"></i></div>
                                <div class="tsc-body">
                                    <div class="trans-stat-label">Drafts</div>
                                    <div class="trans-stat-count"><?php echo number_format($cntDraft); ?></div>
                                    <div class="trans-stat-amount"><?php echo fmtAmt($amtDraft, $cur, $dec); ?></div>
                                </div>
                            </a>
                        </div>
                    </div>

                    <!-- ── Main Card ─────────────────────────────────────── -->
                    <div class="card">

                        <!-- Toolbar -->
                        <div class="trans-toolbar">
                            <ul class="nav trans-status-tabs gap-1" id="srStatusTabs" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active sr-status-tab" data-status="All" href="javascript:void(0);">
                                        All <span class="trans-tab-count ms-1"><?php echo $ModAllCount; ?></span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link sr-status-tab" data-status="SRPending" href="javascript:void(0);">
                                        Pending <span class="trans-tab-count ms-1 d-none"></span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link sr-status-tab" data-status="Paid" href="javascript:void(0);">
                                        Paid <span class="trans-tab-count ms-1 d-none"></span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link sr-status-tab" data-status="Cancelled" href="javascript:void(0);">
                                        Cancelled <span class="trans-tab-count ms-1 d-none"></span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link sr-status-tab" data-status="Draft" href="javascript:void(0);">
                                        Drafts <span class="trans-tab-count ms-1 d-none"></span>
                                    </a>
                                </li>
                            </ul>

                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <a href="javascript:void(0);" class="btn btn-sm btn-outline-secondary p-1 pageRefresh" title="Refresh">
                                    <i class="bx bx-refresh fs-5"></i>
                                </a>
                                <div class="input-group input-group-sm" style="width:210px">
                                    <span class="input-group-text bg-transparent border-end-0"><i class="bx bx-search text-muted"></i></span>
                                    <input type="text" class="form-control border-start-0" id="searchTransactionData" placeholder="Return # or customer...">
                                </div>
                                <div class="dropdown">
                                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button"
                                            id="dateFilterBtn" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="bx bx-calendar me-1"></i><span id="dateFilterLabel">All Dates</span>
                                    </button>
                                    <ul class="dropdown-menu shadow" style="width:210px;max-height:300px;overflow-y:auto;font-size:.82rem;">
                                        <li><a class="dropdown-item date-option" data-range=""><i class="bx bx-list-ul me-2"></i>All Dates</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item date-option" data-range="today">Today</a></li>
                                        <li><a class="dropdown-item date-option" data-range="yesterday">Yesterday</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item date-option" data-range="this_week">This Week</a></li>
                                        <li><a class="dropdown-item date-option" data-range="last_week">Last Week</a></li>
                                        <li><a class="dropdown-item date-option" data-range="last_7_days">Last 7 Days</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item date-option" data-range="this_month">This Month</a></li>
                                        <li><a class="dropdown-item date-option" data-range="previous_month">Previous Month</a></li>
                                        <li><a class="dropdown-item date-option" data-range="last_30_days">Last 30 Days</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item date-option" data-range="this_year">This Year</a></li>
                                        <li><a class="dropdown-item date-option" data-range="last_year">Last Year</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item date-option fw-bold" data-range="fy_25_26">
                                            <i class="bx bxs-star text-warning me-1"></i>FY 25-26
                                        </a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Table -->
                        <div class="table-responsive">
                            <table class="table trans-table table-hover MainviewTable mb-0" id="srTable">
                                <thead class="r2k-thead">
                                    <tr>
                                        <th style="width:36px">
                                            <div class="form-check mb-0">
                                                <input class="form-check-input table-chkbox srHeaderCheck" type="checkbox">
                                            </div>
                                        </th>
                                        <th class="<?php echo $JwtData->GenSettings->SerialNoDisplay == 1 ? '' : 'd-none'; ?> table-serialno" style="width:44px">S.No</th>
                                        <th class="col-sortable cursor-pointer user-select-none" data-sort="Number">
                                            # Bill / Return <i class="bx bx-sort-alt-2 ms-1 sort-icon" data-col="Number"></i>
                                        </th>
                                        <th class="col-sortable cursor-pointer user-select-none" data-sort="Amount">
                                            Amount <i class="bx bx-sort-alt-2 ms-1 sort-icon" data-col="Amount"></i>
                                        </th>
                                        <th>Payment Status</th>
                                        <th>Payment Mode</th>
                                        <th>Customer</th>
                                        <th>Last Updated</th>
                                        <th style="width:50px"></th>
                                    </tr>
                                </thead>
                                <tbody class="r2k-tbody table-border-bottom-0">
                                    <?php echo $ModRowData; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <hr class="my-0">
                        <div class="row mx-3 my-2 justify-content-between align-items-center srPagination" id="srPagination">
                            <?php echo $ModPagination ?: ''; ?>
                        </div>

                    </div>

                    <?php $this->load->view('common/transactions/print_modals'); ?>

                </div>
            </div>

            <?php $this->load->view('common/imagepreview_modal'); ?>

            <?php
            $rpAccentColor = '#198754'; $rpAccentBg = '#e8f5e9';
            $rpPartyIcon   = 'bx-user';  $rpDocLabel  = 'Sales Return';
            $rpTotalIcon   = 'bx-undo';
            $rpNumId       = 'rpInvNum'; $rpDateId    = 'rpInvDate';
            $rpBtnLabel    = 'Refund Payment';
            $this->load->view('common/transactions/payment_modal');
            ?>

            <!-- Apply Credit to Invoice Modal -->
            <div class="modal fade" id="applyCreditModal" tabindex="-1" aria-hidden="true" aria-labelledby="applyCreditModalLabel">
                <div class="modal-dialog modal-dialog-centered" style="max-width:480px">
                    <div class="modal-content">
                        <div class="modal-header py-3" style="background:#e3f2fd;border-bottom:1px solid #bbdefb;">
                            <div>
                                <h6 class="modal-title fw-semibold mb-0" style="color:#1565c0;" id="applyCreditModalLabel">
                                    <i class="bx bx-credit-card me-2"></i>Apply Credit to Invoice
                                </h6>
                                <div class="text-muted" style="font-size:.78rem;margin-top:2px;">
                                    Sales Return: <strong id="acSrNum">—</strong>
                                </div>
                            </div>
                            <button type="button" class="btn-close ms-2" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body p-3">
                            <input type="hidden" id="acSalesReturnUID">

                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <div class="p-2 rounded text-center" style="background:#e8f5e9;border:1px solid #c8e6c9;">
                                        <div class="text-muted" style="font-size:.7rem;">Customer</div>
                                        <div class="fw-semibold text-truncate" style="font-size:.82rem;" id="acPartyName">—</div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="p-2 rounded text-center" style="background:#fff3e0;border:1px solid #ffe0b2;">
                                        <div class="text-muted" style="font-size:.7rem;">Available Credit</div>
                                        <div class="fw-semibold" style="font-size:.88rem;color:#e65100;" id="acCreditBalance">—</div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" style="font-size:.82rem;font-weight:500;">Select Invoice <span class="text-danger">*</span></label>
                                <select id="acInvoiceUID" class="form-select form-select-sm">
                                    <option value="">— Select Invoice —</option>
                                </select>
                            </div>

                            <div id="acInvoiceInfo" class="d-none mb-3">
                                <div class="row g-2">
                                    <div class="col-4">
                                        <div class="p-2 rounded text-center" style="background:#f3e5f5;border:1px solid #e1bee7;">
                                            <div class="text-muted" style="font-size:.68rem;">Invoice Total</div>
                                            <div class="fw-semibold" style="font-size:.82rem;" id="acInvTotal">—</div>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="p-2 rounded text-center" style="background:#e8eaf6;border:1px solid #c5cae9;">
                                            <div class="text-muted" style="font-size:.68rem;">Paid</div>
                                            <div class="fw-semibold" style="font-size:.82rem;" id="acInvPaid">—</div>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="p-2 rounded text-center" style="background:#e3f2fd;border:1px solid #bbdefb;">
                                            <div class="text-muted" style="font-size:.68rem;">Pending</div>
                                            <div class="fw-semibold" style="font-size:.82rem;color:#1565c0;" id="acInvBalance">—</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" style="font-size:.82rem;font-weight:500;">Amount to Apply <span class="text-danger">*</span></label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text" id="acCurrencySymbol">₹</span>
                                    <input type="number" class="form-control" id="acAmount" min="0.01" step="0.01" placeholder="0.00">
                                </div>
                            </div>

                            <div class="mb-0">
                                <label class="form-label" style="font-size:.82rem;font-weight:500;">Notes</label>
                                <input type="text" class="form-control form-control-sm" id="acNotes" placeholder="Optional note">
                            </div>
                        </div>
                        <div class="modal-footer py-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-sm btn-primary" id="btnSubmitApplyCredit">
                                <i class="bx bx-check me-1"></i>Apply Credit
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <?php $this->load->view('common/modals/send_communication'); ?>

            <?php $this->load->view('common/footer_desc'); ?>
        </div>
    </div>
</div>

<?php $this->load->view('common/transactions/footer'); ?>


<script src="/js/common/communication.js"></script>
<script src="/js/transactions/attachments.js"></script>
<script src="/js/transactions/viewmodal.js"></script>
<script src="/js/transactions/a4_print.js"></script>
<script src="/js/transactions/salesreturns.js"></script>

<script>
const ModuleId     = 106;
const ModuleTable  = '#srTable';

function showProcessing(label) { showUIBlock(label); }
function hideProcessing()       { hideUIBlock(); }

function updateSummaryStats(stats) {
    var cur = '<?php echo htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? "₹"); ?>';
    var dec = <?php echo (int)($JwtData->GenSettings->DecimalPoints ?? 2); ?>;
    function fmt(v) { return cur + ' ' + parseFloat(v).toLocaleString('en-IN', { minimumFractionDigits: dec, maximumFractionDigits: dec }); }
    function cnt(s) { return stats[s] ? parseInt(stats[s].count  || 0) : 0; }
    function amt(s) { return stats[s] ? parseFloat(stats[s].amount || 0) : 0; }

    // All = active only (excludes Draft, Cancelled, Rejected)
    var cntAll = cnt('Approved') + cnt('Partial') + cnt('Paid');
    var amtAll = amt('Approved') + amt('Partial') + amt('Paid');

    var cntPending = cnt('Approved') + cnt('Partial');
    var amtPending = amt('Approved') + amt('Partial');

    var cntPaid = cnt('Paid'), amtPaid = amt('Paid');
    var cntDraft = cnt('Draft'), amtDraft = amt('Draft');

    $('.stat-all    .trans-stat-count').text(cntAll.toLocaleString());
    $('.stat-all    .trans-stat-amount').text(fmt(amtAll));
    $('.stat-active .trans-stat-count').text(cntPending.toLocaleString());
    $('.stat-active .trans-stat-amount').text(fmt(amtPending));
    $('.stat-paid   .trans-stat-count').text(cntPaid.toLocaleString());
    $('.stat-draft  .trans-stat-count').text(cntDraft.toLocaleString());
}
const ModulePag    = '.srPagination';
const ModuleHeader = '.srHeaderCheck';
const ModuleRow    = '.srCheck';

$(function () {
    'use strict';

    Filter['Status'] = 'All';

    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl, { container: 'body' });
    });

    $(document).on('click', '[data-stat-filter]', function () {
        var status = $(this).data('stat-filter') || 'All';
        $('.trans-stat-card').removeClass('active-stat');
        $(this).addClass('active-stat');
        $('.sr-status-tab').removeClass('active');
        $('.sr-status-tab[data-status="' + status + '"]').addClass('active');
        Filter.Status = status;
        PageNo = 1;
        getSalesReturnsDetails();
    });

    $(document).on('click', '.sr-status-tab', function (e) {
        e.preventDefault();
        $('.sr-status-tab').removeClass('active');
        $(this).addClass('active');
        $('.trans-stat-card').removeClass('active-stat');
        var status = $(this).data('status') || 'All';
        $('[data-stat-filter="' + status + '"]').addClass('active-stat');
        Filter.Status = status;
        PageNo = 1;
        getSalesReturnsDetails();
    });

    $(document).on('click', '.pageRefresh', function (e) { e.preventDefault(); PageNo = 1; getSalesReturnsDetails(); });

    $('#searchTransactionData').on('keyup', debounce(function () {
        Filter.Name = $.trim($(this).val()); PageNo = 1; getSalesReturnsDetails();
    }, 400));

    $(document).on('click', '.date-option', function () {
        $('.date-option').removeClass('active'); $(this).addClass('active');
        var dates = getDateRange($(this).data('range') || '');
        $('#dateFilterLabel').text($.trim($(this).text()));
        Filter.DateFrom = dates.from; Filter.DateTo = dates.to;
        PageNo = 1; getSalesReturnsDetails();
    });

    $(document).on('click', '.col-sortable', function () {
        var col = $(this).data('sort');
        Filter.SortDir = (Filter.SortBy === col && Filter.SortDir === 'ASC') ? 'DESC' : 'ASC';
        Filter.SortBy  = col;
        $('.sort-icon').removeClass('bx-sort-up bx-sort-down').addClass('bx-sort-alt-2');
        $('.sort-icon[data-col="' + col + '"]').removeClass('bx-sort-alt-2').addClass(Filter.SortDir === 'ASC' ? 'bx-sort-up' : 'bx-sort-down');
        PageNo = 1; getSalesReturnsDetails();
    });

    $(document).on('click', '.srPagination .page-link', function (e) {
        e.preventDefault();
        var match = ($(this).attr('href') || '').match(/\/(\d+)$/);
        if (match) { PageNo = parseInt(match[1]); getSalesReturnsDetails(); }
    });

    $(document).on('click', '.sr-status-update', function () {
        var uid = $(this).data('uid'), status = $(this).data('status');
        if ($(this).data('_confirmed')) {
            $(this).removeData('_confirmed');
        } else if (status === 'Cancelled') {
            var num = $(this).data('num') || '';
            var lbl = num ? '<strong>' + $('<span>').text(num).html() + '</strong>' : 'this sales return';
            var $btn = $(this);
            Swal.fire({ title: 'Cancel Sales Return?', html: 'Are you sure you want to cancel ' + lbl + '? This cannot be undone.', icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#6c757d', confirmButtonText: 'Yes, Cancel It', cancelButtonText: 'No, Keep It' }).then(function (r) { if (!r.isConfirmed) return; $btn.data('_confirmed', true).trigger('click'); });
            return;
        }
        var procMsg = status === 'Cancelled' ? 'Cancelling Sales Return…' : 'Updating Status…';
        showProcessing(procMsg);
        $.ajax({
            url: '/salesreturns/updateSalesReturnStatus', method: 'POST',
            data: { TransUID: uid, Status: status, [CsrfName]: CsrfToken },
            success: function (resp) {
                hideProcessing();
                if (resp.Error) {
                    Swal.fire({ icon: 'error', text: resp.Message });
                } else {
                    getSalesReturnsDetails();
                    if (status === 'Cancelled' && resp.CustomerBalance !== undefined) {
                        var sym = '<?php echo htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? "₹"); ?>';
                        var bal = parseFloat(resp.CustomerBalance).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                        var typ = resp.CustomerBalanceType === 'Debit' ? 'receivable <small>(customer owes you)</small>' : 'advance <small>(you owe customer)</small>';
                        Swal.fire({
                            icon: 'success',
                            title: 'Sales Return Cancelled',
                            html: 'Sales return cancelled successfully.<br><br>'
                                + '<div style="background:#f8f9fa;border-radius:10px;padding:14px 20px;margin-top:4px;display:inline-block;min-width:220px;">'
                                + '<div style="font-size:12px;color:#6c757d;margin-bottom:4px;">Updated Customer Balance</div>'
                                + '<div style="font-size:22px;font-weight:700;color:' + (resp.CustomerBalanceType === 'Debit' ? '#dc3545' : '#198754') + ';">'
                                + sym + bal + '</div>'
                                + '<div style="font-size:11px;color:#6c757d;margin-top:2px;">' + typ + '</div>'
                                + '</div>',
                            confirmButtonColor: '#0d6efd'
                        });
                    }
                }
            },
            error: function () { hideProcessing(); Swal.fire({ icon: 'error', text: 'Request failed. Try again.' }); }
        });
    });

    $(document).on('click', '.deleteSalesReturn', function () {
        var uid = $(this).data('uid'), num = $(this).data('num') || '';
        Swal.fire({ title: 'Delete Sales Return?', html: num ? 'Delete <strong>' + num + '</strong>? This cannot be undone.' : 'This cannot be undone.',
            icon: 'warning', showCancelButton: true, confirmButtonText: 'Delete', confirmButtonColor: '#d33' })
            .then(function (r) {
                if (!r.isConfirmed) return;
                showProcessing('Deleting Sales Return…');
                $.ajax({ url: '/salesreturns/deleteSalesReturn', method: 'POST', data: { TransUID: uid, [CsrfName]: CsrfToken },
                    success: function (resp) {
                        hideProcessing();
                        if (resp.Error) { Swal.fire({ icon: 'error', text: resp.Message }); } else { getSalesReturnsDetails(); Swal.fire({ icon: 'success', text: resp.Message, timer: 1500, showConfirmButton: false }); }
                    },
                    error: function () { hideProcessing(); Swal.fire({ icon: 'error', text: 'Request failed. Try again.' }); }
                });
            });
    });


    $(document).on('change', '.srHeaderCheck', function () { $('.srCheck').prop('checked', $(this).is(':checked')); });

    // ── Refund Payment (Sales Return) ──────────────────────
    $(document).on('click', '.srReceivePayment', function (e) {
        e.preventDefault();
        e.stopPropagation();
        var $el     = $(this);
        var uid     = $el.data('uid')     || 0;
        var num     = $el.data('num')     || '';
        var date    = $el.data('date')    || '';
        var party   = $el.data('party')   || '';
        var total   = parseFloat($el.data('total'))   || 0;
        var paid    = parseFloat($el.data('paid'))    || 0;
        var pending = parseFloat($el.data('pending')) || 0;
        var _cur    = '<?php echo htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹'); ?>';

        $('#rpTransUID').val(uid);
        $('#rpInvNum').text(num || '—');
        $('#rpInvDate').text(date || '—');
        $('#rpPartyName').text(party || '—');
        $('#rpTotalCard').text(_cur + ' ' + total.toFixed(2));
        $('#rpPaidCard').text(_cur + ' ' + paid.toFixed(2));
        $('#rpBalanceCard').text(_cur + ' ' + pending.toFixed(2));
        $('#rpAmount').val(pending.toFixed(2)).attr('max', pending);
        $('#rpCurrencySymbol').text(_cur);
        $('#rpReferenceNo').val('');
        $('#rpNotes').val('');
        $('#rpBankAccount').val('');
        bootstrap.Modal.getOrCreateInstance(document.getElementById('recordPaymentModal')).show();
    });

});

// ── Detail HTML builder ─────────────────────────────────────────
function _buildSrDetailHtml(resp) {
    window._srLastPrintData = resp;
    return _buildTransDetailHtml(resp, {
        partyLabel  : 'Customer',
        typeIcon    : 'bx-undo',
        typeColor   : '#198754',
        typeBg      : '#e8f5e9',
        hasPayments : true,
    });
}

// ── Payment Modal Init & Submit ──────────────────────────────────
(function () {
    'use strict';

    var _payTypes  = <?php echo json_encode(array_map(function($t) {
        return ['PaymentTypeUID' => (int)$t->PaymentTypeUID, 'Name' => (string)$t->Name, 'IsCash' => (int)$t->IsCash];
    }, $PaymentTypes ?? [])); ?>;
    var _bankAccts = <?php echo json_encode(array_values(array_map(function($b) {
        return ['BankAccountUID' => (int)$b->BankAccountUID, 'BankName' => (string)$b->BankName, 'AccountName' => (string)$b->AccountName, 'IsDefault' => (int)$b->IsDefault];
    }, array_filter($BankAccounts ?? [], function($b) { return !(int)$b->IsCash; })))); ?>;
    var _fpInstance = null;
    var _rpDropzone = null;
    var _currency   = '<?php echo htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹'); ?>';

    (function () {
        var $sel = $('#rpBankAccount').empty().append('<option value="">— Select bank account —</option>');
        $.each(_bankAccts, function (i, b) {
            $sel.append('<option value="' + b.BankAccountUID + '">' + _esc(b.BankName) + ' — ' + _esc(b.AccountName) + '</option>');
        });
    }());

    function renderPaymentTypes() {
        var $wrap = $('#rpPaymentTypes').empty();
        $.each(_payTypes, function (i, t) {
            var active = (i === 0) ? ' active' : '';
            if (i === 0) { $('#rpPaymentTypeUID').val(t.PaymentTypeUID); $('#rpIsCash').val(t.IsCash); }
            $wrap.append('<button type="button" class="rp-type-pill btn btn-sm btn-outline-secondary' + active + '" data-uid="' + t.PaymentTypeUID + '" data-iscash="' + t.IsCash + '">' + _esc(t.Name) + '</button>');
        });
        toggleBankRow();
    }

    function toggleBankRow() {
        var isCash = parseInt($('#rpIsCash').val(), 10);
        $('#rpBankRow').toggleClass('d-none', !!isCash);
        if (!isCash && !$('#rpBankAccount').val()) {
            var def = $.grep(_bankAccts, function(b) { return b.IsDefault === 1; });
            if (def.length) { $('#rpBankAccount').val(def[0].BankAccountUID); }
        }
    }

    $('#recordPaymentModal').on('shown.bs.modal', function () {
        if (!_fpInstance) {
            _fpInstance = flatpickr('#rpPaymentDate', {
                dateFormat: 'Y-m-d', altInput: true, altFormat: 'd M Y',
                maxDate: 'today', disableMobile: true, defaultDate: 'today',
                appendTo: document.querySelector('#recordPaymentModal .modal-dialog'),
            });
        } else {
            _fpInstance.setDate(new Date(), false);
        }
        if (!_rpDropzone && typeof Dropzone !== 'undefined') {
            Dropzone.autoDiscover = false;
            _rpDropzone = new Dropzone('#rpAttachDropzone', {
                url: '#', autoProcessQueue: false, maxFiles: 3, maxFilesize: 3,
                acceptedFiles: '.pdf,.jpg,.jpeg,.png', parallelUploads: 3, clickable: true,
                previewTemplate: '<div class="dz-preview dz-file-preview"><div class="dz-details"><div class="dz-filename"><span data-dz-name></span></div><div class="dz-size"><span data-dz-size></span></div></div><div class="dz-error-message"><span data-dz-errormessage></span></div><a class="dz-remove" href="javascript:undefined;" data-dz-remove>Remove</a></div>',
                init: function () {
                    this.on('maxfilesexceeded', function (file) { this.removeFile(file); Swal.fire({ icon: 'warning', text: 'Maximum 3 attachments allowed.' }); });
                }
            });
        }
        renderPaymentTypes();
    });

    $(document).on('click', '.rp-type-pill', function () {
        $('.rp-type-pill').removeClass('active btn-primary').addClass('btn-outline-secondary');
        $(this).addClass('active btn-primary').removeClass('btn-outline-secondary');
        $('#rpPaymentTypeUID').val($(this).data('uid'));
        $('#rpIsCash').val($(this).data('iscash'));
        toggleBankRow();
    });

    $('#btnSubmitPayment').on('click', function () {
        var transUID       = parseInt($('#rpTransUID').val(), 10);
        var paymentTypeUID = parseInt($('#rpPaymentTypeUID').val(), 10);
        var amount         = parseFloat($('#rpAmount').val()) || 0;
        var paymentDate    = $('#rpPaymentDate').val() || new Date().toISOString().split('T')[0];
        var bankAccountUID = parseInt($('#rpBankAccount').val(), 10) || 0;
        var referenceNo    = $.trim($('#rpReferenceNo').val());
        var notes          = $.trim($('#rpNotes').val());

        if (!transUID)       { Swal.fire({ icon: 'warning', text: 'Invalid sales return.' }); return; }
        if (!paymentTypeUID) { Swal.fire({ icon: 'warning', text: 'Please select a payment type.' }); return; }
        if (amount <= 0)     { Swal.fire({ icon: 'warning', text: 'Enter a valid amount.' }); return; }
        var isCash = parseInt($('#rpIsCash').val(), 10);
        if (!isCash && !bankAccountUID) { Swal.fire({ icon: 'warning', text: 'Please select a bank account.' }); return; }

        var $btn = $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Saving…');
        var fd = new FormData();
        fd.append('TransUID',       transUID);
        fd.append('PaymentTypeUID', paymentTypeUID);
        fd.append('Amount',         amount);
        fd.append('PaymentDate',    paymentDate);
        fd.append('BankAccountUID', bankAccountUID || '');
        fd.append('ReferenceNo',    referenceNo);
        fd.append('Notes',          notes);
        fd.append(CsrfName,         CsrfToken);
        if (_rpDropzone) { _rpDropzone.files.forEach(function(file) { fd.append('PaymentFiles[]', file); }); }

        $.ajax({
            url: '/salesreturns/recordPayment', method: 'POST',
            data: fd, processData: false, contentType: false,
            success: function (resp) {
                $btn.prop('disabled', false).html('<i class="bx bx-check me-1"></i> Refund Payment');
                if (resp.Error) {
                    showToastNotification(resp.Message, 'error');
                } else {
                    bootstrap.Modal.getInstance(document.getElementById('recordPaymentModal')).hide();
                    getSalesReturnsDetails();
                    showToastNotification(resp.Message, 'success');
                }
            },
            error: function () {
                $btn.prop('disabled', false).html('<i class="bx bx-check me-1"></i> Refund Payment');
                showToastNotification('Request failed. Try again.', 'error');
            }
        });
    });

}());

// ── Apply Credit to Invoice ──────────────────────────────────────
(function () {
    'use strict';
    var _acCur     = '<?php echo htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹'); ?>';
    var _acDec     = <?php echo (int)($JwtData->GenSettings->DecimalPoints ?? 2); ?>;
    var _acBalance = 0;
    var _acSelect2 = null;

    $(document).on('click', '.srApplyCredit', function (e) {
        e.preventDefault();
        e.stopPropagation();
        var $el     = $(this);
        var srUID   = $el.data('uid')     || 0;
        var num     = $el.data('num')     || '—';
        var party   = $el.data('party')   || '—';
        var balance = parseFloat($el.data('balance')) || 0;

        _acBalance = balance;
        $('#acSalesReturnUID').val(srUID);
        $('#acSrNum').text(num);
        $('#acPartyName').text(party);
        $('#acCreditBalance').text(_acCur + ' ' + balance.toFixed(_acDec));
        $('#acCurrencySymbol').text(_acCur);
        $('#acAmount').val(balance.toFixed(_acDec)).attr('max', balance);
        $('#acNotes').val('');
        $('#acInvoiceInfo').addClass('d-none');

        var $sel = $('#acInvoiceUID').empty().append('<option value="">— Loading… —</option>');
        if (_acSelect2) { try { _acSelect2.destroy(); } catch(ex){} _acSelect2 = null; }

        $.ajax({
            url: '/salesreturns/getPendingInvoices', method: 'POST',
            data: { SalesReturnUID: srUID, [CsrfName]: CsrfToken },
            success: function (resp) {
                $sel.empty().append('<option value="">— Select Invoice —</option>');
                if (!resp.Error && resp.Invoices && resp.Invoices.length) {
                    $.each(resp.Invoices, function (i, inv) {
                        var bal = parseFloat(inv.BalanceAmount) || 0;
                        $sel.append('<option value="' + inv.TransUID
                            + '" data-total="'   + inv.NetAmount
                            + '" data-paid="'    + inv.PaidAmount
                            + '" data-balance="' + bal + '">'
                            + _esc(inv.UniqueNumber) + ' — ' + _acCur + ' ' + bal.toFixed(_acDec) + ' pending'
                            + '</option>');
                    });
                } else {
                    $sel.append('<option value="" disabled>No pending invoices found</option>');
                }
                if (typeof $.fn.select2 !== 'undefined') {
                    _acSelect2 = $sel.select2({ dropdownParent: $('#applyCreditModal'), placeholder: '— Select Invoice —' });
                }
            }
        });

        bootstrap.Modal.getOrCreateInstance(document.getElementById('applyCreditModal')).show();
    });

    $(document).on('change', '#acInvoiceUID', function () {
        var $opt    = $(this).find('option:selected');
        var invUID  = parseInt($(this).val(), 10) || 0;
        if (!invUID) { $('#acInvoiceInfo').addClass('d-none'); return; }
        var total   = parseFloat($opt.data('total'))   || 0;
        var paid    = parseFloat($opt.data('paid'))    || 0;
        var balance = parseFloat($opt.data('balance')) || 0;
        $('#acInvTotal').text(_acCur + ' ' + total.toFixed(_acDec));
        $('#acInvPaid').text(_acCur + ' ' + paid.toFixed(_acDec));
        $('#acInvBalance').text(_acCur + ' ' + balance.toFixed(_acDec));
        $('#acInvoiceInfo').removeClass('d-none');
        var maxApply = Math.min(_acBalance, balance);
        $('#acAmount').val(maxApply.toFixed(_acDec)).attr('max', maxApply);
    });

    $('#btnSubmitApplyCredit').on('click', function () {
        var srUID      = parseInt($('#acSalesReturnUID').val(), 10) || 0;
        var invoiceUID = parseInt($('#acInvoiceUID').val(), 10) || 0;
        var amount     = parseFloat($('#acAmount').val()) || 0;
        var notes      = $.trim($('#acNotes').val());

        if (!srUID)      { Swal.fire({ icon: 'warning', text: 'Invalid sales return.' }); return; }
        if (!invoiceUID) { Swal.fire({ icon: 'warning', text: 'Please select an invoice.' }); return; }
        if (amount <= 0) { Swal.fire({ icon: 'warning', text: 'Enter a valid amount.' }); return; }

        var $btn = $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Applying…');
        $.ajax({
            url: '/salesreturns/applyCredit', method: 'POST',
            data: { SalesReturnUID: srUID, InvoiceUID: invoiceUID, Amount: amount, Notes: notes, [CsrfName]: CsrfToken },
            success: function (resp) {
                $btn.prop('disabled', false).html('<i class="bx bx-check me-1"></i> Apply Credit');
                if (resp.Error) {
                    Swal.fire({ icon: 'error', text: resp.Message });
                } else {
                    bootstrap.Modal.getInstance(document.getElementById('applyCreditModal')).hide();
                    getSalesReturnsDetails();
                    showToastNotification(resp.Message, 'success');
                }
            },
            error: function () {
                $btn.prop('disabled', false).html('<i class="bx bx-check me-1"></i> Apply Credit');
                showToastNotification('Request failed. Try again.', 'error');
            }
        });
    });

    $('#applyCreditModal').on('hidden.bs.modal', function () {
        if (_acSelect2) { try { _acSelect2.destroy(); } catch(ex){} _acSelect2 = null; }
        $('#acInvoiceUID').empty().append('<option value="">— Select Invoice —</option>');
        $('#acInvoiceInfo').addClass('d-none');
    });

}());
</script>
