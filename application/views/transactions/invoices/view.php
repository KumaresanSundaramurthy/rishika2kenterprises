<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php $this->load->view('common/transactions/header'); ?>

<div class="layout-wrapper layout-content-navbar">
    <div class="layout-container">

        <?php $this->load->view('common/menu_view'); ?>

        <div class="layout-page">

            <div class="content-wrapper">
                <div class="container-xxl flex-grow-1 container-p-y">

                    <?php
                    // ── Build summary numbers ─────────────────────────
                    $stats       = $SummaryStats ?? [];
                    $cur         = htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹');
                    $dec         = $JwtData->GenSettings->DecimalPoints ?? 2;

                    $cntAll      = array_sum(array_column($stats, 'count'));
                    $cntIssued   = ($stats['Issued']['count']   ?? 0) + ($stats['Sent']['count'] ?? 0) + ($stats['Partial']['count'] ?? 0);
                    $cntPaid     = $stats['Paid']['count']      ?? 0;
                    $cntDraft    = $stats['Draft']['count']     ?? 0;
                    $cntOverdue  = $stats['Overdue']['count']   ?? 0;

                    $amtAll      = array_sum(array_column($stats, 'amount'));
                    $amtIssued   = ($stats['Issued']['amount']  ?? 0) + ($stats['Sent']['amount'] ?? 0) + ($stats['Partial']['amount'] ?? 0);
                    $amtPaid     = $stats['Paid']['amount']     ?? 0;

                    function fmtAmt($val, $sym, $dec) {
                        return $sym . ' ' . number_format((float)$val, $dec, '.', ',');
                    }
                    ?>

                    <!-- ── Stat Cards ──────────────────────────────────────── -->
                    <div class="row g-3 mb-4">
                        <div class="col-6 col-md-3">
                            <a href="javascript:void(0);" class="trans-stat-card stat-all active-stat" data-stat-filter="All">
                                <div class="trans-stat-label">All Invoices</div>
                                <div class="trans-stat-count"><?php echo number_format($cntAll); ?></div>
                                <div class="trans-stat-amount"><?php echo fmtAmt($amtAll, $cur, $dec); ?></div>
                                <i class="bx bx-receipt trans-stat-icon"></i>
                            </a>
                        </div>
                        <div class="col-6 col-md-3">
                            <a href="javascript:void(0);" class="trans-stat-card stat-active" data-stat-filter="Issued">
                                <div class="trans-stat-label">Outstanding</div>
                                <div class="trans-stat-count"><?php echo number_format($cntIssued); ?></div>
                                <div class="trans-stat-amount"><?php echo fmtAmt($amtIssued, $cur, $dec); ?></div>
                                <i class="bx bx-time-five trans-stat-icon"></i>
                            </a>
                        </div>
                        <div class="col-6 col-md-3">
                            <a href="javascript:void(0);" class="trans-stat-card stat-paid" data-stat-filter="Paid">
                                <div class="trans-stat-label">Paid</div>
                                <div class="trans-stat-count"><?php echo number_format($cntPaid); ?></div>
                                <div class="trans-stat-amount"><?php echo fmtAmt($amtPaid, $cur, $dec); ?></div>
                                <i class="bx bx-check-circle trans-stat-icon"></i>
                            </a>
                        </div>
                        <div class="col-6 col-md-3">
                            <a href="javascript:void(0);" class="trans-stat-card stat-draft" data-stat-filter="Draft">
                                <div class="trans-stat-label">Drafts</div>
                                <div class="trans-stat-count"><?php echo number_format($cntDraft); ?></div>
                                <div class="trans-stat-amount">&nbsp;</div>
                                <i class="bx bx-pencil trans-stat-icon"></i>
                            </a>
                        </div>
                    </div>

                    <!-- ── Main Card ───────────────────────────────────────── -->
                    <div class="card">

                        <!-- Toolbar -->
                        <div class="trans-toolbar">

                            <!-- Status tabs -->
                            <ul class="nav trans-status-tabs gap-1" id="invStatusTabs" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active inv-status-tab" data-status="All" href="javascript:void(0);">
                                        All <span class="trans-tab-count ms-1"><?php echo $ModAllCount; ?></span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link inv-status-tab" data-status="Issued" href="javascript:void(0);">
                                        Issued <span class="trans-tab-count ms-1 d-none"></span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link inv-status-tab" data-status="Paid" href="javascript:void(0);">
                                        Paid <span class="trans-tab-count ms-1 d-none"></span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link inv-status-tab" data-status="Cancelled" href="javascript:void(0);">
                                        Cancelled <span class="trans-tab-count ms-1 d-none"></span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link inv-status-tab" data-status="Draft" href="javascript:void(0);">
                                        Drafts <span class="trans-tab-count ms-1 d-none"></span>
                                    </a>
                                </li>
                            </ul>

                            <!-- Right controls -->
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <a href="javascript:void(0);" class="btn btn-sm btn-outline-secondary p-1 pageRefresh" title="Refresh">
                                    <i class="bx bx-refresh fs-5"></i>
                                </a>
                                <div class="input-group input-group-sm" style="width:210px">
                                    <span class="input-group-text bg-transparent border-end-0"><i class="bx bx-search text-muted"></i></span>
                                    <input type="text" class="form-control border-start-0" id="searchTransactionData"
                                           placeholder="Invoice # or customer..." title="Search invoices">
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
                                <a href="/invoices/create" class="btn btn-primary btn-sm px-3">
                                    <i class="bx bx-plus me-1"></i>Create
                                </a>
                            </div>
                        </div>

                        <!-- Table -->
                        <div class="table-responsive">
                            <table class="table trans-table table-hover MainviewTable mb-0" id="invTable">
                                <thead class="r2k-thead">
                                    <tr>
                                        <th style="width:36px">
                                            <div class="form-check mb-0">
                                                <input class="form-check-input table-chkbox invHeaderCheck" type="checkbox">
                                            </div>
                                        </th>
                                        <th class="<?php echo $JwtData->GenSettings->SerialNoDisplay == 1 ? '' : 'd-none'; ?> table-serialno" style="width:44px">S.No</th>
                                        <th class="col-sortable cursor-pointer user-select-none" data-sort="Number">
                                            # Bill <i class="bx bx-sort-alt-2 ms-1 sort-icon" data-col="Number"></i>
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
                        <div class="row mx-3 my-2 justify-content-between align-items-center invPagination" id="invPagination">
                            <?php echo $ModPagination ?: ''; ?>
                        </div>

                    </div>

                    <?php $this->load->view('common/transactions/print_modals'); ?>

                </div>
            </div>

        <!-- ── Record Payment Modal ──────────────────────────────── -->
        <div class="modal fade" id="recordPaymentModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content" style="overflow:hidden;">

                    <!-- Floating close button (catgProductsModal style) -->
                    <button type="button" class="btn-close position-absolute" data-bs-dismiss="modal"
                        style="top:14px;right:16px;z-index:10;background-color:rgba(255,255,255,.85);border-radius:50%;padding:6px;box-shadow:0 1px 4px rgba(0,0,0,.15);"
                        aria-label="Close"></button>

                    <div class="modal-body p-0">

                        <!-- Banner -->
                        <div style="background:#e8f0fe;border-left:4px solid #0d6efd;padding:14px 20px;">
                            <div class="d-flex align-items-center gap-3">
                                <div style="background:#0d6efd22;border-radius:10px;padding:9px 11px;">
                                    <i class="bx bx-money-withdraw" style="font-size:1.7rem;color:#0d6efd;display:block;"></i>
                                </div>
                                <div>
                                    <div style="font-size:1.05rem;font-weight:800;color:#0d6efd;">
                                        Record Payment &mdash; <span id="rpInvNum">—</span>
                                    </div>
                                    <div style="font-size:.77rem;color:#6c757d;margin-top:3px;">
                                        <i class="bx bx-user me-1"></i><span id="rpPartyName">—</span>
                                        <span class="mx-2" style="color:#dee2e6;">|</span>
                                        <i class="bx bx-calendar me-1"></i><span id="rpInvDate">—</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Summary cards -->
                        <div style="padding:14px 20px;border-bottom:1px solid #e9ecef;">
                            <div class="d-flex align-items-center gap-2" style="padding:4px 0 10px;">
                                <i class="bx bx-bar-chart-alt-2" style="font-size:1.05rem;color:#6c757d;"></i>
                                <span style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:#6c757d;">Payment Summary</span>
                            </div>
                            <div class="row g-2">
                                <div class="col-4">
                                    <div style="background:#fafafa;border:1px solid #e9ecef;border-left:3px solid #0d6efd;border-radius:6px;padding:10px 12px;">
                                        <div style="font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.4px;color:#6c757d;margin-bottom:5px;">
                                            <i class="bx bx-receipt me-1"></i>Invoice Total
                                        </div>
                                        <div style="font-size:1.1rem;font-weight:800;color:#0d6efd;" id="rpTotalCard">—</div>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div style="background:#fafafa;border:1px solid #e9ecef;border-left:3px solid #198754;border-radius:6px;padding:10px 12px;">
                                        <div style="font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.4px;color:#6c757d;margin-bottom:5px;">
                                            <i class="bx bx-check-circle me-1"></i>Paid So Far
                                        </div>
                                        <div style="font-size:1.1rem;font-weight:800;color:#198754;" id="rpPaidCard">—</div>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div style="background:#fafafa;border:1px solid #e9ecef;border-left:3px solid #dc3545;border-radius:6px;padding:10px 12px;">
                                        <div style="font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.4px;color:#6c757d;margin-bottom:5px;">
                                            <i class="bx bx-time me-1"></i>Balance Due
                                        </div>
                                        <div style="font-size:1.1rem;font-weight:800;color:#dc3545;" id="rpBalanceCard">—</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Payment form -->
                        <div style="padding:14px 20px;">
                            <div class="d-flex align-items-center gap-2" style="padding:4px 0 10px;">
                                <i class="bx bx-edit-alt" style="font-size:1.05rem;color:#fd7e14;"></i>
                                <span style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:#fd7e14;">Payment Details</span>
                            </div>
                            <div class="row g-3">

                                <!-- Amount -->
                                <div class="col-5">
                                    <label class="form-label fw-semibold mb-1" style="font-size:.78rem;">
                                        <span class="text-danger">*</span> Amount
                                    </label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-white fw-semibold" id="rpCurrencySymbol">₹</span>
                                        <input type="number" class="form-control" id="rpAmount" step="0.01" min="0.01" placeholder="0.00">
                                    </div>
                                </div>

                                <!-- Payment Date -->
                                <div class="col-7">
                                    <label class="form-label fw-semibold mb-1" style="font-size:.78rem;">Payment Date</label>
                                    <div class="input-group input-group-sm input-group-merge">
                                        <span class="input-group-text bg-white"><i class="bx bx-calendar"></i></span>
                                        <input type="text" class="form-control" id="rpPaymentDate" placeholder="Today" readonly>
                                    </div>
                                </div>

                                <!-- Payment Type -->
                                <div class="col-12">
                                    <label class="form-label fw-semibold mb-1" style="font-size:.78rem;">Payment Type</label>
                                    <div class="d-flex flex-wrap gap-2" id="rpPaymentTypes">
                                        <div class="text-muted" style="font-size:.8rem;"><i class="bx bx-loader-alt bx-spin"></i> Loading…</div>
                                    </div>
                                    <input type="hidden" id="rpPaymentTypeUID" value="">
                                    <input type="hidden" id="rpIsCash" value="1">
                                </div>

                                <!-- Bank Account (non-cash only) -->
                                <div class="col-12 d-none" id="rpBankRow">
                                    <label class="form-label fw-semibold mb-1" style="font-size:.78rem;">Bank Account <span class="text-danger">*</span></label>
                                    <select class="form-select form-select-sm" id="rpBankAccount">
                                        <option value="">— Select bank account —</option>
                                    </select>
                                </div>

                                <!-- Reference ID -->
                                <div class="col-6">
                                    <label class="form-label mb-1" style="font-size:.75rem;color:#566a7f;font-weight:600;">
                                        Reference ID <span class="fw-normal text-muted">(Optional)</span>
                                    </label>
                                    <input type="text" class="form-control form-control-sm" id="rpReferenceNo"
                                           placeholder="UTR, Cheque No, UPI Ref…" maxlength="100">
                                </div>

                                <!-- Notes -->
                                <div class="col-6">
                                    <label class="form-label mb-1" style="font-size:.75rem;color:#566a7f;font-weight:600;">
                                        Notes <span class="fw-normal text-muted">(Optional)</span>
                                    </label>
                                    <textarea class="form-control form-control-sm" id="rpNotes" rows="1"
                                              placeholder="Add a payment note…" maxlength="255"></textarea>
                                </div>

                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="d-flex justify-content-end gap-2 px-4 py-3 border-top" style="background:#f8f9fa;">
                            <button type="button" class="btn btn-outline-secondary btn-sm px-3" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary btn-sm px-4" id="btnSubmitPayment">
                                <i class="bx bx-check me-1"></i> Record Payment
                            </button>
                        </div>

                        <input type="hidden" id="rpTransUID" value="">

                    </div>
                </div>
            </div>
        </div>

            <?php $this->load->view('common/footer_desc'); ?>
        </div>
    </div>
</div>

<!-- Payment Details Panel -->
<div id="payDetailPanel" style="
    display:none;position:fixed;z-index:1080;
    background:#fff;border-radius:10px;
    border:1px solid rgba(0,0,0,.1);
    width:290px;
    box-shadow:0 6px 24px rgba(0,0,0,.13);
    overflow:hidden;
">
    <div style="background:#f8f9fa;padding:10px 14px 8px;border-bottom:1px solid #eee;">
        <div class="d-flex justify-content-between align-items-center">
            <span style="font-size:.8rem;font-weight:600;color:#566a7f;">
                <i class="bx bx-credit-card me-1 text-primary"></i>
                <span id="payPanelTitle">Payments</span>
            </span>
            <button type="button" id="payPanelClose"
                    style="background:none;border:none;padding:0 2px;line-height:1;font-size:1rem;color:#aaa;cursor:pointer;">
                <i class="bx bx-x"></i>
            </button>
        </div>
    </div>
    <div id="payDetailBody" style="padding:10px 14px;max-height:300px;overflow-y:auto;"></div>
</div>

<?php $this->load->view('common/transactions/footer'); ?>

<script src="/js/transactions/viewmodal.js"></script>
<script src="/js/transactions/a4_print.js"></script>
<script src="/js/transactions/invoices.js"></script>

<script>
const ModuleId     = 103;
const ModuleTable  = '#invTable';
const ModulePag    = '.invPagination';
const ModuleHeader = '.invHeaderCheck';
const ModuleRow    = '.invCheck';

$(function () {
    'use strict';

    Filter['Status'] = 'All';

    // ── Stat card click → filter by status ─────────────────
    $(document).on('click', '[data-stat-filter]', function () {
        var status = $(this).data('stat-filter') || 'All';
        $('.trans-stat-card').removeClass('active-stat');
        $(this).addClass('active-stat');
        // Sync tabs
        $('.inv-status-tab').removeClass('active');
        $('.inv-status-tab[data-status="' + status + '"]').addClass('active');
        Filter.Status = status;
        PageNo = 1;
        getInvoicesDetails();
    });

    // ── Status tabs ─────────────────────────────────────────
    $(document).on('click', '.inv-status-tab', function (e) {
        e.preventDefault();
        $('.inv-status-tab').removeClass('active');
        $(this).addClass('active');
        $('.trans-stat-card').removeClass('active-stat');
        var status = $(this).data('status') || 'All';
        $('[data-stat-filter="' + status + '"]').addClass('active-stat');
        Filter.Status = status;
        PageNo = 1;
        getInvoicesDetails();
    });

    $(document).on('click', '.pageRefresh', function (e) {
        e.preventDefault();
        PageNo = 1;
        getInvoicesDetails();
    });

    // Search
    $('#searchTransactionData').on('keyup', debounce(function () {
        Filter.Name = $.trim($(this).val());
        PageNo = 1;
        getInvoicesDetails();
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
        getInvoicesDetails();
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
        $('.sort-icon').removeClass('bx-sort-up bx-sort-down').addClass('bx-sort-alt-2');
        var icon = Filter.SortDir === 'ASC' ? 'bx-sort-up' : 'bx-sort-down';
        $('.sort-icon[data-col="' + col + '"]').removeClass('bx-sort-alt-2').addClass(icon);
        PageNo = 1;
        getInvoicesDetails();
    });

    // Pagination
    $(document).on('click', '.invPagination .page-link', function (e) {
        e.preventDefault();
        var match = ($(this).attr('href') || '').match(/\/(\d+)$/);
        if (match) { PageNo = parseInt(match[1]); getInvoicesDetails(); }
    });

    // View modal — handled by /js/transactions/viewmodal.js (.viewTransaction)

    // ── A4 Print — handled by /js/transactions/a4_print.js ──

    // ── Delete ──────────────────────────────────────────────
    $(document).on('click', '.deleteInvoice', function () {
        var uid = $(this).data('uid');
        var num = $(this).data('num') || '';
        Swal.fire({
            title: 'Delete Invoice?',
            html : num ? 'Delete <strong>' + num + '</strong>? This cannot be undone.' : 'This cannot be undone.',
            icon : 'warning', showCancelButton: true,
            confirmButtonText: 'Delete', confirmButtonColor: '#d33',
        }).then(function (r) {
            if (!r.isConfirmed) return;
            $.ajax({
                url   : '/invoices/deleteInvoice',
                method: 'POST',
                data  : { TransUID: uid, [CsrfName]: CsrfToken },
                success: function (resp) {
                    if (resp.Error) { Swal.fire({ icon:'error', text:resp.Message }); }
                    else { getInvoicesDetails(); Swal.fire({ icon:'success', text:resp.Message, timer:1500, showConfirmButton:false }); }
                }
            });
        });
    });

    // ── Cancel Invoice ──────────────────────────────────────
    $(document).on('click', '.cancelInvoice', function () {
        var uid = $(this).data('uid');
        var num = $(this).data('num') || '';
        Swal.fire({
            title: 'Cancel Invoice?',
            html : num ? 'Cancel <strong>' + num + '</strong>? This cannot be undone.' : 'This cannot be undone.',
            icon : 'warning', showCancelButton: true,
            confirmButtonText: 'Yes, Cancel It', confirmButtonColor: '#fd7e14',
        }).then(function (r) {
            if (!r.isConfirmed) return;
            $.ajax({
                url   : '/invoices/updateInvoiceStatus',
                method: 'POST',
                data  : { TransUID: uid, Status: 'Cancelled', [CsrfName]: CsrfToken },
                success: function (resp) {
                    if (resp.Error) { Swal.fire({ icon:'error', text: resp.Message }); }
                    else { getInvoicesDetails(); Swal.fire({ icon:'success', text: resp.Message, timer:1500, showConfirmButton:false }); }
                }
            });
        });
    });

    // ── Duplicate ───────────────────────────────────────────
    $(document).on('click', '.duplicateInvoice', function () {
        var uid = $(this).data('uid');
        Swal.fire({
            title: 'Duplicate Invoice?', text: 'A new draft copy will be created.',
            icon : 'question', showCancelButton: true, confirmButtonText: 'Duplicate',
        }).then(function (r) {
            if (!r.isConfirmed) return;
            $.ajax({
                url   : '/invoices/duplicateInvoice',
                method: 'POST',
                data  : { TransUID: uid, [CsrfName]: CsrfToken },
                success: function (resp) {
                    if (resp.Error) { Swal.fire({ icon:'error', text:resp.Message }); }
                    else {
                        getInvoicesDetails();
                        Swal.fire({ icon:'success', text:resp.Message, showCancelButton:true, confirmButtonText:'Edit Now', cancelButtonText:'Stay Here' })
                            .then(function (r2) { if (r2.isConfirmed && resp.EditURL) window.location.href = resp.EditURL; });
                    }
                }
            });
        });
    });
});

// ── Detail HTML builder ─────────────────────────────────────────
function _buildInvDetailHtml(resp) {
    window._invLastPrintData = resp;
    return _buildTransDetailHtml(resp, {
        partyLabel  : 'Customer',
        typeIcon    : 'bx-receipt',
        typeColor   : '#0d6efd',
        typeBg      : '#e8f0fe',
        hasPayments : true,
    });
}



function _esc(v) {
    if (v === null || v === undefined) return '—';
    return $('<span>').text(String(v)).html();
}

// ── Record Payment Modal ──────────────────────────────────────────
(function () {
    'use strict';

    // Data injected at page render — zero extra requests
    var _payTypes  = <?php echo json_encode(array_map(function($t) {
        return ['PaymentTypeUID' => (int)$t->PaymentTypeUID, 'Name' => (string)$t->Name, 'IsCash' => (int)$t->IsCash];
    }, $PaymentTypes ?? [])); ?>;
    var _bankAccts = <?php echo json_encode(array_map(function($b) {
        return ['BankAccountUID' => (int)$b->BankAccountUID, 'BankName' => (string)$b->BankName, 'AccountName' => (string)$b->AccountName];
    }, $BankAccounts ?? [])); ?>;
    var _fpInstance = null;
    var _currency   = '<?php echo htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹'); ?>';

    // Populate bank account select once from pre-loaded data
    (function () {
        var $sel = $('#rpBankAccount').empty().append('<option value="">— Select bank account —</option>');
        $.each(_bankAccts, function (i, b) {
            $sel.append('<option value="' + b.BankAccountUID + '">' + _esc(b.BankName) + ' — ' + _esc(b.AccountName) + '</option>');
        });
    }());

    function renderPaymentTypes() {
        var $wrap = $('#rpPaymentTypes').empty();
        if (!_payTypes.length) {
            $wrap.html('<div class="text-muted" style="font-size:.8rem;"><i class="bx bx-loader-alt bx-spin"></i> Loading…</div>');
            return;
        }
        $.each(_payTypes, function (i, t) {
            var active = (i === 0) ? ' active' : '';
            if (i === 0) { $('#rpPaymentTypeUID').val(t.PaymentTypeUID); $('#rpIsCash').val(t.IsCash); }
            $wrap.append(
                '<button type="button" class="rp-type-pill btn btn-sm btn-outline-secondary' + active + '" ' +
                'data-uid="' + t.PaymentTypeUID + '" data-iscash="' + t.IsCash + '">' + _esc(t.Name) + '</button>'
            );
        });
        toggleBankRow();
    }

    function toggleBankRow() {
        var isCash = parseInt($('#rpIsCash').val(), 10);
        $('#rpBankRow').toggleClass('d-none', !!isCash);
    }

    // Init flatpickr once modal is fully visible; reset to today on each open
    $('#recordPaymentModal').on('shown.bs.modal', function () {
        if (!_fpInstance) {
            _fpInstance = flatpickr('#rpPaymentDate', {
                dateFormat   : 'Y-m-d',
                altInput     : true,
                altFormat    : 'd M Y',
                maxDate      : 'today',
                disableMobile: true,
                defaultDate  : 'today',
                appendTo: document.querySelector('#recordPaymentModal .modal-body'),
            });
        } else {
            _fpInstance.setDate(new Date(), false);
        }
    });

    // Open modal when "Receive Payment" clicked
    $(document).on('click', '.invReceivePayment', function () {
        var uid     = $(this).data('uid');
        var num     = $(this).data('num')     || '';
        var date    = $(this).data('date')    || '';
        var party   = $(this).data('party')   || '';
        var total   = parseFloat($(this).data('total'))   || 0;
        var paid    = parseFloat($(this).data('paid'))    || 0;
        var pending = parseFloat($(this).data('pending')) || 0;

        $('#rpTransUID').val(uid);
        $('#rpInvNum').text(num);
        $('#rpInvDate').text(date);
        $('#rpPartyName').text(party);
        $('#rpTotalCard').text(_currency + ' ' + total.toFixed(2));
        $('#rpPaidCard').text(_currency + ' ' + paid.toFixed(2));
        $('#rpBalanceCard').text(_currency + ' ' + pending.toFixed(2));
        $('#rpAmount').val(pending.toFixed(2)).attr('max', pending);
        $('#rpCurrencySymbol').text(_currency);
        $('#rpReferenceNo').val('');
        $('#rpNotes').val('');
        $('#rpBankAccount').val('');

        renderPaymentTypes();
        $('#recordPaymentModal').modal('show');
    });

    // Payment type pill toggle
    $(document).on('click', '.rp-type-pill', function () {
        $('.rp-type-pill').removeClass('active btn-primary').addClass('btn-outline-secondary');
        $(this).addClass('active btn-primary').removeClass('btn-outline-secondary');
        $('#rpPaymentTypeUID').val($(this).data('uid'));
        $('#rpIsCash').val($(this).data('iscash'));
        toggleBankRow();
    });

    // Submit payment
    $('#btnSubmitPayment').on('click', function () {
        var transUID       = parseInt($('#rpTransUID').val(), 10);
        var paymentTypeUID = parseInt($('#rpPaymentTypeUID').val(), 10);
        var amount         = parseFloat($('#rpAmount').val()) || 0;
        var paymentDate    = $('#rpPaymentDate').val() || new Date().toISOString().split('T')[0];
        var bankAccountUID = parseInt($('#rpBankAccount').val(), 10) || 0;
        var referenceNo    = $.trim($('#rpReferenceNo').val());
        var notes          = $.trim($('#rpNotes').val());

        if (!transUID)       { Swal.fire({ icon: 'warning', text: 'Invalid invoice.' }); return; }
        if (!paymentTypeUID) { Swal.fire({ icon: 'warning', text: 'Please select a payment type.' }); return; }
        if (amount <= 0)     { Swal.fire({ icon: 'warning', text: 'Enter a valid amount.' }); return; }
        var isCash = parseInt($('#rpIsCash').val(), 10);
        if (!isCash && !bankAccountUID) { Swal.fire({ icon: 'warning', text: 'Please select a bank account for this payment type.' }); return; }

        var $btn = $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Saving…');

        $.ajax({
            url   : '/invoices/recordInvoicePayment',
            method: 'POST',
            data  : {
                TransUID       : transUID,
                PaymentTypeUID : paymentTypeUID,
                Amount         : amount,
                PaymentDate    : paymentDate,
                BankAccountUID : bankAccountUID || '',
                ReferenceNo    : referenceNo,
                Notes          : notes,
                [CsrfName]     : CsrfToken,
            },
            success: function (resp) {
                $btn.prop('disabled', false).html('<i class="bx bx-check me-1"></i> Record Payment');
                if (resp.Error) {
                    Swal.fire({ icon: 'error', title: 'Error', text: resp.Message });
                } else {
                    $('#recordPaymentModal').modal('hide');
                    getInvoicesDetails();
                    Swal.fire({ icon: 'success', text: resp.Message, timer: 1800, showConfirmButton: false });
                }
            },
            error: function () {
                $btn.prop('disabled', false).html('<i class="bx bx-check me-1"></i> Record Payment');
                Swal.fire({ icon: 'error', text: 'Request failed. Try again.' });
            }
        });
    });

}());

// ── Attachment Viewer ─────────────────────────────────────────────
$(document).on('click', '.invAttachBtn', function () {
    var uid = $(this).data('uid');
    var num = $(this).data('num') || ('Invoice #' + uid);
    $('#invAttachModalTitle').text('Attachments — ' + num);
    $('#invAttachGallery').html('<div class="text-center py-4"><span class="spinner-border spinner-border-sm text-primary"></span></div>');
    var modal = new bootstrap.Modal(document.getElementById('invAttachModal'));
    modal.show();
    $.ajax({
        url    : '/invoices/getAttachments',
        method : 'POST',
        data   : { TransUID: uid, [CsrfName]: CsrfToken },
        success: function (resp) {
            if (resp.Error || !resp.Attachments || !resp.Attachments.length) {
                $('#invAttachGallery').html('<div class="text-center py-5 text-muted"><i class="bx bx-paperclip fs-2 d-block mb-2"></i>No attachments found.</div>');
                return;
            }
            var cdnUrl = (typeof CDN_URL !== 'undefined' && CDN_URL) ? CDN_URL : '';
            var html = '<div class="row g-2">';
            resp.Attachments.forEach(function (a, idx) {
                var fullUrl = cdnUrl + (a.FilePath || '');
                var safeName = $('<span>').text(a.FileName || '').html();
                var isImg = /image\//i.test(a.FileType || '') || /\.(jpg|jpeg|png|gif|webp|bmp|svg)$/i.test(a.FileName || '');
                var isPdf = /pdf/i.test(a.FileType || '') || /\.pdf$/i.test(a.FileName || '');
                var isMedia = /^video\//i.test(a.FileType || '') || /\.(mp4|webm|ogg|mov)$/i.test(a.FileName || '');
                var encUrl = encodeURIComponent(fullUrl);

                if (isImg) {
                    html += '<div class="col-6 col-md-4">' +
                        '<div class="attach-thumb-wrap border rounded overflow-hidden" style="cursor:pointer;height:120px;background:#f8f9fa;" ' +
                        'onclick="_openAttachPreview(\'' + encUrl + '\',\'img\',\'' + safeName + '\')">' +
                        '<img src="' + $('<span>').text(fullUrl).html() + '" style="width:100%;height:100%;object-fit:cover;" loading="lazy" alt="' + safeName + '">' +
                        '</div>' +
                        '<div class="text-muted mt-1 d-flex align-items-center gap-1" style="font-size:.72rem;">' +
                        '<i class="bx bx-image-alt"></i>' +
                        '<span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="' + safeName + '">' + safeName + '</span></div>' +
                        '</div>';
                } else if (isPdf) {
                    html += '<div class="col-6 col-md-4">' +
                        '<div class="attach-thumb-wrap border rounded d-flex flex-column align-items-center justify-content-center gap-1" style="cursor:pointer;height:120px;background:#fff5f5;" ' +
                        'onclick="_openAttachPreview(\'' + encUrl + '\',\'pdf\',\'' + safeName + '\')">' +
                        '<i class="bx bxs-file-pdf text-danger" style="font-size:2.5rem;"></i>' +
                        '<span style="font-size:.72rem;color:#dc3545;font-weight:600;">PDF</span>' +
                        '</div>' +
                        '<div class="text-muted mt-1 d-flex align-items-center gap-1" style="font-size:.72rem;">' +
                        '<i class="bx bx-file-blank"></i>' +
                        '<span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="' + safeName + '">' + safeName + '</span></div>' +
                        '</div>';
                } else {
                    var icon = isMedia ? 'bxs-videos text-primary' : 'bx-file text-secondary';
                    html += '<div class="col-6 col-md-4">' +
                        '<div class="attach-thumb-wrap border rounded d-flex flex-column align-items-center justify-content-center gap-1" style="cursor:pointer;height:120px;background:#f8f9fa;" ' +
                        'onclick="_openAttachPreview(\'' + encUrl + '\',\'file\',\'' + safeName + '\')">' +
                        '<i class="bx ' + icon + '" style="font-size:2.5rem;"></i>' +
                        '<span style="font-size:.72rem;color:#6c757d;font-weight:500;text-align:center;padding:0 6px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:90%;">' + safeName + '</span>' +
                        '</div>' +
                        '<div class="text-muted mt-1 d-flex align-items-center gap-1" style="font-size:.72rem;">' +
                        '<i class="bx bx-file-blank"></i>' +
                        '<span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="' + safeName + '">' + safeName + '</span></div>' +
                        '</div>';
                }
            });
            html += '</div>';
            $('#invAttachGallery').html(html);
        },
        error: function () {
            $('#invAttachGallery').html('<div class="text-center py-4 text-danger">Failed to load attachments.</div>');
        }
    });
});

function _openAttachPreview(encUrl, type, name) {
    var url = decodeURIComponent(encUrl);
    var safeName = $('<span>').text(name).html();
    $('#attachPreviewTitle').text(name || 'Preview');
    var body = '';
    if (type === 'img') {
        body = '<div class="text-center p-3"><img src="' + $('<span>').text(url).html() + '" class="img-fluid rounded" style="max-height:70vh;" alt="' + safeName + '"></div>';
    } else if (type === 'pdf') {
        body = '<iframe src="' + $('<span>').text(url).html() + '" style="width:100%;height:70vh;border:none;"></iframe>';
    } else {
        body = '<div class="text-center py-5">' +
            '<i class="bx bx-file-blank text-secondary" style="font-size:4rem;display:block;margin-bottom:12px;"></i>' +
            '<div style="font-size:.9rem;font-weight:600;margin-bottom:16px;">' + safeName + '</div>' +
            '<button class="btn btn-primary px-4" onclick="(function(u,n){var a=document.createElement(\'a\');a.href=u;a.download=n;a.style.display=\'none\';document.body.appendChild(a);a.click();document.body.removeChild(a);})(decodeURIComponent(\'' + encUrl + '\'),\'' + safeName.replace(/'/g, "\\'") + '\'))"><i class="bx bx-download me-2"></i>Download File</button>' +
            '</div>';
    }
    $('#attachPreviewBody').html(body);
    var previewModal = new bootstrap.Modal(document.getElementById('attachPreviewModal'));
    previewModal.show();
}

</script>

<!-- ── Invoice Attachment Viewer Modal ──────────────────────────── -->
<div class="modal fade" id="invAttachModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content" style="overflow:hidden;">
            <button type="button" class="btn-close position-absolute" data-bs-dismiss="modal"
                style="top:14px;right:16px;z-index:10;background-color:rgba(255,255,255,.85);border-radius:50%;padding:6px;box-shadow:0 1px 4px rgba(0,0,0,.15);"
                aria-label="Close"></button>
            <div class="modal-body p-0">
                <div style="background:#e8f0fe;border-left:4px solid #0d6efd;padding:14px 20px;">
                    <div class="d-flex align-items-center gap-3">
                        <div style="background:#0d6efd22;border-radius:10px;padding:9px 11px;">
                            <i class="bx bx-paperclip" style="font-size:1.7rem;color:#0d6efd;display:block;"></i>
                        </div>
                        <div>
                            <div style="font-size:1rem;font-weight:800;color:#0d6efd;" id="invAttachModalTitle">Attachments</div>
                            <div style="font-size:.77rem;color:#6c757d;margin-top:3px;">Click any file to preview</div>
                        </div>
                    </div>
                </div>
                <div style="padding:16px 20px;" id="invAttachGallery">
                    <div class="text-center py-4"><span class="spinner-border spinner-border-sm text-primary"></span></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ── Attachment Preview Modal ──────────────────────────────────── -->
<div class="modal fade" id="attachPreviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header py-2 px-3">
                <h6 class="modal-title d-flex align-items-center gap-2 mb-0" style="font-size:.88rem;font-weight:700;max-width:90%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                    <i class="bx bx-file text-primary"></i>
                    <span id="attachPreviewTitle">Preview</span>
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0" id="attachPreviewBody" style="min-height:200px;background:#1a1a2e;">
                <div class="text-center py-5"><span class="spinner-border text-light"></span></div>
            </div>
        </div>
    </div>
</div>
