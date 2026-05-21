<?php defined('BASEPATH') or exit('No direct script access allowed');
$this->load->view('common/transactions/header'); ?>

<div class="layout-wrapper layout-horizontal layout-content-navbar">
    <div class="layout-container">

        <?php $this->load->view('common/menu_view'); ?>

        <div class="layout-page">
            <div class="content-wrapper">
                <div class="container-xxl flex-grow-1 container-p-y">

                    <?php
                    $stats   = $SummaryStats ?? [];
                    $cur     = htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹');
                    $dec     = (int)($JwtData->GenSettings->DecimalPoints ?? 2);

                    $cntPending   = $stats['Pending']['count']   ?? 0;
                    $amtPending   = $stats['Pending']['amount']  ?? 0;
                    $cntReceived  = $stats['Received']['count']  ?? 0;
                    $amtReceived  = $stats['Received']['amount'] ?? 0;
                    $cntCancelled = $stats['Cancelled']['count'] ?? 0;
                    $cntAll       = $cntPending + $cntReceived + $cntCancelled;
                    $amtAll       = $amtPending + $amtReceived;

                    $categories   = $Categories   ?? [];
                    $paymentTypes = $PaymentTypes ?? [];
                    $bankAccounts = $BankAccounts ?? [];

                    function incFmt($val, $sym, $dec) {
                        return $sym . ' ' . number_format((float)$val, $dec, '.', ',');
                    }
                    ?>

                    <!-- ── Page Header ────────────────────────────────────── -->
                    <div class="trans-page-header">
                        <div class="d-flex align-items-center gap-3">
                            <div class="trans-ph-icon" style="background:#d1fae5;">
                                <i class="bx bx-trending-up" style="color:#059669;"></i>
                            </div>
                            <h5 class="trans-ph-title"><?php echo htmlspecialchars($PageTitle ?? 'Indirect Income'); ?></h5>
                        </div>
                        <button type="button" class="btn btn-primary" id="addIncomeBtn">
                            <i class="bx bx-plus me-1"></i>Add Income
                        </button>
                    </div>

                    <!-- ── Stat Cards ─────────────────────────────────────── -->
                    <div class="trans-stats-section">
                        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;">
                            <a href="javascript:void(0);" class="trans-stat-card stat-all active-stat" data-stat-filter="All">
                                <div class="tsc-icon-wrap"><i class="bx bx-trending-up"></i></div>
                                <div class="tsc-body">
                                    <div class="trans-stat-label">All Income</div>
                                    <div class="trans-stat-count"><?php echo number_format($cntAll); ?></div>
                                    <div class="trans-stat-amount"><?php echo incFmt($amtAll, $cur, $dec); ?></div>
                                </div>
                            </a>
                            <a href="javascript:void(0);" class="trans-stat-card stat-active" data-stat-filter="Pending">
                                <div class="tsc-icon-wrap"><i class="bx bx-time"></i></div>
                                <div class="tsc-body">
                                    <div class="trans-stat-label">Pending</div>
                                    <div class="trans-stat-count"><?php echo number_format($cntPending); ?></div>
                                    <div class="trans-stat-amount"><?php echo incFmt($amtPending, $cur, $dec); ?></div>
                                </div>
                            </a>
                            <a href="javascript:void(0);" class="trans-stat-card" data-stat-filter="Received">
                                <div class="tsc-icon-wrap"><i class="bx bx-check-circle"></i></div>
                                <div class="tsc-body">
                                    <div class="trans-stat-label">Received</div>
                                    <div class="trans-stat-count"><?php echo number_format($cntReceived); ?></div>
                                    <div class="trans-stat-amount"><?php echo incFmt($amtReceived, $cur, $dec); ?></div>
                                </div>
                            </a>
                            <a href="javascript:void(0);" class="trans-stat-card stat-draft" data-stat-filter="Cancelled">
                                <div class="tsc-icon-wrap"><i class="bx bx-x-circle"></i></div>
                                <div class="tsc-body">
                                    <div class="trans-stat-label">Cancelled</div>
                                    <div class="trans-stat-count"><?php echo number_format($cntCancelled); ?></div>
                                    <div class="trans-stat-amount">&nbsp;</div>
                                </div>
                            </a>
                        </div>
                    </div>

                    <!-- ── Main Card ──────────────────────────────────────── -->
                    <div class="card">

                        <!-- Toolbar -->
                        <div class="trans-toolbar">
                            <ul class="nav trans-status-tabs gap-1" id="incStatusTabs" role="tablist">
                                <li class="nav-item"><a class="nav-link active inc-status-tab" data-status="All"       href="javascript:void(0);">All      <span class="trans-tab-count ms-1"><?php echo $ModAllCount; ?></span></a></li>
                                <li class="nav-item"><a class="nav-link inc-status-tab"       data-status="Pending"   href="javascript:void(0);">Pending   <span class="inc-tab-count trans-tab-count ms-1 d-none"></span></a></li>
                                <li class="nav-item"><a class="nav-link inc-status-tab"       data-status="Received"  href="javascript:void(0);">Received  <span class="inc-tab-count trans-tab-count ms-1 d-none"></span></a></li>
                                <li class="nav-item"><a class="nav-link inc-status-tab"       data-status="Cancelled" href="javascript:void(0);">Cancelled <span class="inc-tab-count trans-tab-count ms-1 d-none"></span></a></li>
                            </ul>

                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <a href="javascript:void(0);" class="btn btn-sm btn-outline-secondary p-1 pageRefresh" title="Refresh"><i class="bx bx-refresh fs-5"></i></a>
                                <div class="input-group input-group-sm" style="width:210px">
                                    <span class="input-group-text bg-transparent border-end-0"><i class="bx bx-search text-muted"></i></span>
                                    <input type="text" class="form-control border-start-0" id="searchIncomeData" placeholder="Income # or category...">
                                </div>
                                <div class="dropdown">
                                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
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
                                        <li><a class="dropdown-item date-option fw-bold" data-range="fy_25_26"><i class="bx bxs-star text-warning me-1"></i>FY 25-26</a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Table -->
                        <div class="table-responsive">
                            <table class="table trans-table table-hover MainviewTable mb-0" id="incTable">
                                <thead class="r2k-thead">
                                    <tr>
                                        <th style="width:36px"><div class="form-check mb-0"><input class="form-check-input table-chkbox incHeaderCheck" type="checkbox"></div></th>
                                        <th class="<?php echo $JwtData->GenSettings->SerialNoDisplay == 1 ? '' : 'd-none'; ?> table-serialno" style="width:44px">S.No</th>
                                        <th class="col-sortable cursor-pointer user-select-none" data-sort="Number">Income # <i class="bx bx-sort-alt-2 ms-1 sort-icon" data-col="Number"></i></th>
                                        <th class="col-sortable cursor-pointer user-select-none" data-sort="Amount">Amount <i class="bx bx-sort-alt-2 ms-1 sort-icon" data-col="Amount"></i></th>
                                        <th>Category</th>
                                        <th>Status</th>
                                        <th>Mode</th>
                                        <th class="col-sortable cursor-pointer user-select-none" data-sort="Date">Date <i class="bx bx-sort-alt-2 ms-1 sort-icon" data-col="Date"></i></th>
                                        <th>Last Updated</th>
                                        <th style="width:50px"></th>
                                    </tr>
                                </thead>
                                <tbody class="r2k-tbody table-border-bottom-0">
                                    <?php echo $ModRowData; ?>
                                </tbody>
                            </table>
                        </div>

                        <hr class="my-0">
                        <div class="row mx-3 my-2 justify-content-between align-items-center incPagination" id="incPagination">
                            <?php echo $ModPagination ?: ''; ?>
                        </div>
                    </div>

                    <div class="card mb-0 cust-sticky-pag" id="incStickyPagination" style="display:none;">
                        <div class="card-body p-0">
                            <div class="row mx-3 my-2 justify-content-between align-items-center incPagination"></div>
                        </div>
                    </div>

                </div>
            </div>
            <?php $this->load->view('common/footer_desc'); ?>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════════════════════
     Income Add / Edit Modal
══════════════════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="incomeModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-xl">
        <div class="modal-content">

            <!-- Header -->
            <div class="modal-header bg-white border-bottom d-flex align-items-center justify-content-between px-3 py-2 modal-header-center-sticky trans-theme">
                <div class="d-flex align-items-center gap-3">
                    <div class="modal-doc-icon" style="background:#d1fae5;">
                        <i class="bx bx-trending-up modal-doc-icon-inner" style="color:#059669;"></i>
                    </div>
                    <h5 class="modal-title mb-0" id="incModalTitle">Add Income</h5>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-sm btn-primary" id="incSaveBtn">
                        <i class="bx bx-check me-1"></i>Save
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger" data-bs-dismiss="modal">
                        <i class="bx bx-x me-1"></i>Close
                    </button>
                </div>
            </div>

            <!-- Hidden state -->
            <input type="hidden" id="imUID" value="0">

            <!-- Body -->
            <div class="modal-body p-3">
                <div class="row g-3">

                    <!-- ── Left Column ──────────────────────────────────── -->
                    <div class="col-lg-8">

                        <!-- Basic Details -->
                        <div class="card mb-3">
                            <div class="card-header py-2">
                                <h6 class="mb-0">Basic Details</h6>
                            </div>
                            <div class="card-body">

                                <!-- Amount -->
                                <div id="imAmountAddWrap" class="mb-3">
                                    <label class="form-label fw-semibold">Income Amount <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text"><?php echo $cur; ?></span>
                                        <input type="number" class="form-control form-control-lg" id="imAmount"
                                               min="0.01" step="0.01" placeholder="0.00">
                                    </div>
                                </div>
                                <div id="imAmountEditWrap" class="mb-3" style="display:none;">
                                    <label class="form-label fw-semibold" style="font-size:.82rem;color:#6b7280;">Income Amount</label>
                                    <div id="imAmountDisplay" class="fw-bold" style="font-size:1.6rem;color:#111827;letter-spacing:-.5px;"></div>
                                </div>

                                <!-- Date + Category -->
                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Income Date</label>
                                        <input type="date" class="form-control" id="imDate">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">
                                            Category
                                            <button type="button" class="btn btn-link p-0 ms-1 text-primary" id="imAddCategoryBtn"
                                                    style="font-size:.75rem;vertical-align:baseline;" title="Add new category">
                                                <i class="bx bx-plus-circle"></i> New
                                            </button>
                                        </label>
                                        <select class="form-select" id="imCategory">
                                            <option value="">Select Category</option>
                                            <?php foreach ($categories as $cat): ?>
                                                <option value="<?php echo (int)$cat->CategoryUID; ?>">
                                                    <?php echo htmlspecialchars($cat->CategoryName); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <!-- Notes -->
                                <div class="mb-0">
                                    <label class="form-label fw-semibold">Notes</label>
                                    <textarea class="form-control" id="imNotes" rows="3"
                                              placeholder="Notes or description..."></textarea>
                                </div>

                            </div>
                        </div>

                        <!-- Attachments -->
                        <div class="card mb-0">
                            <div class="card-body p-3">
                                <h6 class="mb-2 fw-semibold">
                                    <i class="bx bx-paperclip me-1 text-muted"></i>Attach Files
                                    <small class="text-muted fw-normal ms-1">(Max 3, 3 MB each)</small>
                                </h6>
                                <div class="dropzone needsclick dz-clickable w-100" id="incAttachDropzone">
                                    <div class="dz-message needsclick text-center">
                                        <p class="h5 needsclick mb-2">Drag and drop files here</p>
                                        <p class="h4 text-body-secondary fw-normal mb-0">or click to browse (max 3 MB per file)</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- ── Right Column ─────────────────────────────────── -->
                    <div class="col-lg-4" id="imPaymentColumn">

                        <!-- Payment -->
                        <div class="card mb-0">
                            <div class="card-header py-2">
                                <div class="d-flex align-items-center justify-content-between">
                                    <h6 class="mb-0">Payment</h6>
                                    <button type="button" id="imMarkReceivedBtn"
                                            class="btn btn-sm btn-outline-secondary" style="min-width:140px;">
                                        <i class="bx bx-credit-card me-1"></i>Mark as Received
                                    </button>
                                </div>
                            </div>

                            <div id="imPaymentSection" style="display:none;">
                                <div class="card-body">

                                    <!-- Payment Date -->
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold" style="font-size:.85rem;">Payment Date</label>
                                        <input type="date" class="form-control" id="imPmtDate">
                                    </div>

                                    <!-- Payment Type pills -->
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold" style="font-size:.85rem;">Payment Type</label>
                                        <div class="d-flex flex-wrap gap-2" id="imPmtTypePills">
                                            <?php if (!empty($paymentTypes)): ?>
                                                <?php foreach ($paymentTypes as $pt): ?>
                                                    <button type="button" class="btn btn-sm inc-pmt-pill btn-outline-secondary"
                                                            data-uid="<?php echo (int)$pt->PaymentTypeUID; ?>"
                                                            data-name="<?php echo htmlspecialchars($pt->PaymentTypeName); ?>">
                                                        <?php echo htmlspecialchars($pt->PaymentTypeName); ?>
                                                    </button>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <?php foreach (['UPI','Cash','Card','Net Banking','Cheque','EMI'] as $i => $pn): ?>
                                                    <button type="button" class="btn btn-sm inc-pmt-pill btn-outline-secondary"
                                                            data-uid="<?php echo $i + 1; ?>"
                                                            data-name="<?php echo $pn; ?>">
                                                        <?php echo $pn; ?>
                                                    </button>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>
                                        <input type="hidden" id="imPmtTypeUID" value="">
                                    </div>

                                    <!-- Bank Account -->
                                    <div class="mb-3" id="imBankSection">
                                        <label class="form-label fw-semibold" style="font-size:.85rem;">Bank / Account</label>
                                        <select class="form-select" id="imBankUID">
                                            <option value="">None / Not Applicable</option>
                                            <?php foreach ($bankAccounts as $ba): ?>
                                                <option value="<?php echo (int)$ba->BankAccountUID; ?>">
                                                    <?php echo htmlspecialchars($ba->AccountName); ?>
                                                    <?php echo !empty($ba->BankName) ? ' — ' . htmlspecialchars($ba->BankName) : ''; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <!-- Payment Notes -->
                                    <div class="mb-0">
                                        <label class="form-label fw-semibold" style="font-size:.85rem;">Payment Notes</label>
                                        <textarea class="form-control" id="imPmtNotes" rows="2"
                                                  placeholder="Reference, cheque no., UTR..."></textarea>
                                    </div>

                                </div>
                            </div>
                        </div>

                    </div>
                </div><!-- /row -->
            </div><!-- /modal-body -->

        </div>
    </div>
</div>

<?php $this->load->view('transactions/indirectincome/partials/_add_category_modal'); ?>

<?php $this->load->view('common/transactions/footer'); ?>

<script src="/js/transactions/indirectincome.js"></script>

<script>
const ModuleId     = 115;
const ModuleTable  = '#incTable';
const ModulePag    = '.incPagination';
const ModuleHeader = '.incHeaderCheck';
const ModuleRow    = '.incCheck';

$(function () {
    'use strict';

    Filter['Status'] = 'All';

    var incCurSymbol    = '<?php echo addslashes($cur); ?>';
    var incDecPoints    = <?php echo (int)$dec; ?>;
    var incDropzone     = null;
    var _incIsEdit      = false;
    var _noBankTypes    = ['Cash'];
    var fpIncDate       = null;
    var fpIncPmtDate    = null;
    var _incPickersInit = false;

    // ── Sticky pagination ────────────────────────────────────
    var $staticPag = $('#incPagination');
    var $stickyPag = $('#incStickyPagination');
    function _syncSticky()   { $stickyPag.find('.incPagination').html($staticPag.html()); }
    function _toggleSticky() {
        if (!$staticPag.length) return;
        var r = $staticPag[0].getBoundingClientRect();
        var visible = r.top < $(window).height() && r.bottom > 0;
        if (visible) $stickyPag.stop(true, true).fadeOut(150);
        else { _syncSticky(); $stickyPag.stop(true, true).fadeIn(150); }
    }
    $(window).on('scroll resize', _toggleSticky);
    _toggleSticky();

    // ── List helpers ─────────────────────────────────────────
    function _renderList(resp) {
        $(ModuleTable + ' tbody').html(resp.RecordHtmlData);
        $('.incPagination').html(resp.Pagination);
        var count = resp.TotalCount || 0;
        $('.inc-status-tab.active .trans-tab-count').text(count > 0 ? count : '').removeClass('d-none');
        if (resp.SummaryStats) _updateStatCards(resp.SummaryStats);
        initTooltips();
        _syncSticky();
    }

    function _updateStatCards(stats) {
        var pending   = stats.Pending   || { count: 0, amount: 0 };
        var received  = stats.Received  || { count: 0, amount: 0 };
        var cancelled = stats.Cancelled || { count: 0, amount: 0 };
        var allCount  = pending.count + received.count + cancelled.count;
        var allAmount = pending.amount + received.amount;

        $('[data-stat-filter="All"]      .trans-stat-count').text(allCount);
        $('[data-stat-filter="All"]      .trans-stat-amount').text(incCurSymbol + ' ' + _fmtNum(allAmount));
        $('[data-stat-filter="Pending"]  .trans-stat-count').text(pending.count);
        $('[data-stat-filter="Pending"]  .trans-stat-amount').text(incCurSymbol + ' ' + _fmtNum(pending.amount));
        $('[data-stat-filter="Received"] .trans-stat-count').text(received.count);
        $('[data-stat-filter="Received"] .trans-stat-amount').text(incCurSymbol + ' ' + _fmtNum(received.amount));
        $('[data-stat-filter="Cancelled"].trans-stat-count').text(cancelled.count);
    }

    function _fmtNum(val) {
        return parseFloat(val || 0).toLocaleString('en-IN', { minimumFractionDigits: incDecPoints, maximumFractionDigits: incDecPoints });
    }

    function _postData(extra) {
        Filter.Status = $('.inc-status-tab.active').data('status') || 'All';
        return $.extend({ RowLimit: RowLimit, PageNo: PageNo, Filter: Filter, [CsrfName]: CsrfToken }, extra);
    }

    function _postStatusUpdate(uid, status) {
        $.ajax({
            url: '/indirectincome/updateIncomeStatus', method: 'POST',
            data: _postData({ IncomeUID: uid, Status: status }),
            success: function (resp) {
                if (resp.Error) { showToastNotification(resp.Message, 'error'); return; }
                _renderList(resp);
                showToastNotification(resp.Message, 'success');
            }
        });
    }

    // ── Stat card click ──────────────────────────────────────
    $(document).on('click', '[data-stat-filter]', function () {
        var status = $(this).data('stat-filter') || 'All';
        $('.trans-stat-card').removeClass('active-stat');
        $(this).addClass('active-stat');
        $('.inc-status-tab').removeClass('active');
        $('.inc-status-tab[data-status="' + status + '"]').addClass('active');
        Filter.Status = status; PageNo = 1; getIncomeDetails();
    });

    // ── Status tab click ─────────────────────────────────────
    $(document).on('click', '.inc-status-tab', function (e) {
        e.preventDefault();
        $('.inc-status-tab').removeClass('active');
        $(this).addClass('active');
        $('.trans-stat-card').removeClass('active-stat');
        var status = $(this).data('status') || 'All';
        $('[data-stat-filter="' + status + '"]').addClass('active-stat');
        Filter.Status = status; PageNo = 1; getIncomeDetails();
    });

    $(document).on('click', '.pageRefresh', function (e) {
        e.preventDefault(); PageNo = 1; getIncomeDetails();
    });

    $('#searchIncomeData').on('keyup', debounce(function () {
        Filter.Name = $.trim($(this).val()); PageNo = 1; getIncomeDetails();
    }, 400));

    $(document).on('click', '.date-option', function () {
        $('.date-option').removeClass('active');
        $(this).addClass('active');
        var dates = getDateRange($(this).data('range') || '');
        $('#dateFilterLabel').text($.trim($(this).text()));
        Filter.DateFrom = dates.from; Filter.DateTo = dates.to;
        PageNo = 1; getIncomeDetails();
    });

    $(document).on('click', '.col-sortable', function () {
        var col = $(this).data('sort');
        if (Filter.SortBy === col) Filter.SortDir = Filter.SortDir === 'ASC' ? 'DESC' : 'ASC';
        else { Filter.SortBy = col; Filter.SortDir = 'DESC'; }
        $('.sort-icon').removeClass('bx-sort-up bx-sort-down').addClass('bx-sort-alt-2');
        $('.sort-icon[data-col="' + col + '"]').removeClass('bx-sort-alt-2').addClass(Filter.SortDir === 'ASC' ? 'bx-sort-up' : 'bx-sort-down');
        PageNo = 1; getIncomeDetails();
    });

    $(document).on('click', '.incPagination .page-link', function (e) {
        e.preventDefault();
        var match = ($(this).attr('href') || '').match(/\/(\d+)$/);
        if (match) { PageNo = parseInt(match[1]); getIncomeDetails(); }
    });

    $(document).on('change', '.incHeaderCheck', function () {
        $('.incCheck').prop('checked', $(this).is(':checked'));
    });

    // ── Row actions ──────────────────────────────────────────
    $(document).on('click', '.incMarkReceived', function () {
        var uid = $(this).data('uid'), num = $(this).data('num') || '';
        Swal.fire({
            title: 'Mark as Received?',
            html: num ? 'Mark <strong>' + $('<span>').text(num).html() + '</strong> as received?' : 'Mark this income as received?',
            icon: 'question', showCancelButton: true,
            confirmButtonColor: '#198754', cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Mark Received'
        }).then(function (r) { if (r.isConfirmed) _postStatusUpdate(uid, 'Received'); });
    });

    $(document).on('click', '.incCancel', function () {
        var uid = $(this).data('uid'), num = $(this).data('num') || '';
        Swal.fire({
            title: 'Cancel Income?',
            html: num ? 'Cancel <strong>' + $('<span>').text(num).html() + '</strong>? This cannot be undone.' : 'This cannot be undone.',
            icon: 'warning', showCancelButton: true,
            confirmButtonColor: '#d33', confirmButtonText: 'Yes, Cancel'
        }).then(function (r) { if (r.isConfirmed) _postStatusUpdate(uid, 'Cancelled'); });
    });

    $(document).on('click', '.incDelete', function () {
        var uid = $(this).data('uid'), num = $(this).data('num') || '';
        Swal.fire({
            title: 'Delete Income?',
            html: num ? 'Delete <strong>' + $('<span>').text(num).html() + '</strong>? This cannot be undone.' : 'This cannot be undone.',
            icon: 'warning', showCancelButton: true,
            confirmButtonText: 'Delete', confirmButtonColor: '#d33'
        }).then(function (r) {
            if (!r.isConfirmed) return;
            $.ajax({
                url: '/indirectincome/deleteIncome', method: 'POST',
                data: _postData({ IncomeUID: uid }),
                success: function (resp) {
                    if (resp.Error) { showToastNotification(resp.Message, 'error'); return; }
                    _renderList(resp);
                    showToastNotification(resp.Message || 'Income deleted.', 'success');
                }
            });
        });
    });

    // ══════════════════════════════════════════════════════════
    // Modal — Dropzone init
    // ══════════════════════════════════════════════════════════
    function initIncDropzone() {
        var el = document.querySelector('#incAttachDropzone');
        if (!el) return;
        if (incDropzone) { try { incDropzone.destroy(); } catch (e) {} incDropzone = null; }
        Dropzone.instances = Dropzone.instances.filter(function (d) { return d.element !== el; });
        el.classList.remove('dz-started', 'dropzone');

        incDropzone = new Dropzone(el, {
            url: '#',
            autoProcessQueue: false,
            parallelUploads: 3,
            maxFilesize: 3,
            maxFiles: 3,
            acceptedFiles: '.pdf,.png,.jpg,.jpeg',
            addRemoveLinks: true,
            previewTemplate: `
                <div class="dz-preview dz-file-preview">
                    <div class="dz-details">
                        <div class="dz-thumbnail">
                            <img data-dz-thumbnail>
                            <span class="dz-nopreview">No preview</span>
                            <div class="dz-success-mark"></div>
                            <div class="dz-error-mark"></div>
                            <div class="dz-error-message"><span data-dz-errormessage></span></div>
                            <div class="progress">
                                <div class="progress-bar progress-bar-primary" role="progressbar" aria-valuemin="0" aria-valuemax="100" data-dz-uploadprogress></div>
                            </div>
                        </div>
                        <div class="dz-filename" data-dz-name></div>
                        <div class="dz-size" data-dz-size></div>
                    </div>
                </div>`,
            init: function () {
                var dz = this;
                dz.on('addedfile', function (file) {
                    var totalSize = 0;
                    dz.files.forEach(function (f) { totalSize += f.size; });
                    if (totalSize > 9 * 1024 * 1024) {
                        dz.removeFile(file);
                        Swal.fire({ icon: 'error', title: 'File too large', text: 'Total upload size cannot exceed 9 MB (3 files × 3 MB).' });
                    }
                });
                dz.on('error', function (file, message) {
                    if (file.size > dz.options.maxFilesize * 1024 * 1024) {
                        Swal.fire({ icon: 'error', title: 'File too large', text: 'Each file must be 3 MB or smaller.' });
                        dz.removeFile(file);
                    }
                });
                dz.on('maxfilesexceeded', function (file) {
                    dz.removeFile(file);
                    Swal.fire({ icon: 'error', title: 'Limit Reached', text: 'Maximum 3 files allowed.' });
                });
            }
        });
    }

    // ══════════════════════════════════════════════════════════
    // Modal — Reset & Populate
    // ══════════════════════════════════════════════════════════
    function _todayStr() {
        return new Date().toISOString().slice(0, 10);
    }

    function _resetIncModal() {
        _incIsEdit = false;
        $('#imUID').val('0');
        $('#incModalTitle').text('Add Income');
        $('#imAmountAddWrap').show();
        $('#imAmountEditWrap').hide();
        $('#imAmountDisplay').text('');
        $('#imPaymentColumn').show();
        $('#imAmount').val('');
        if (fpIncDate) fpIncDate.setDate(_todayStr(), false); else $('#imDate').val(_todayStr());
        $('#imCategory').val('');
        if ($.fn.select2 && $('#imCategory').data('select2')) $('#imCategory').trigger('change');
        $('#imNotes').val('');
        $('#imMarkReceivedBtn').removeClass('btn-success').addClass('btn-outline-secondary')
            .html('<i class="bx bx-credit-card me-1"></i>Mark as Received');
        $('#imPaymentSection').hide();
        if (fpIncPmtDate) fpIncPmtDate.setDate(_todayStr(), false); else $('#imPmtDate').val(_todayStr());
        $('#imPmtTypeUID').val('');
        $('.inc-pmt-pill').removeClass('btn-primary').addClass('btn-outline-secondary');
        $('#imBankUID').val('');
        $('#imBankSection').show();
        $('#imPmtNotes').val('');
        if (incDropzone) incDropzone.removeAllFiles(true);
    }

    function _populateIncModal(d) {
        _incIsEdit = true;
        $('#imUID').val(d.IncomeUID);
        $('#incModalTitle').text('Edit Income' + (d.IncomeNumber ? ' — ' + d.IncomeNumber : ''));
        $('#imAmountAddWrap').hide();
        $('#imAmountEditWrap').show();
        var fmt = parseFloat(d.Amount || 0).toLocaleString('en-IN', {
            minimumFractionDigits: incDecPoints, maximumFractionDigits: incDecPoints
        });
        $('#imAmountDisplay').text(incCurSymbol + ' ' + fmt);
        $('#imAmount').val(d.Amount || '');
        $('#imPaymentColumn').hide();
        if (fpIncDate) fpIncDate.setDate(d.IncomeDate || _todayStr(), false); else $('#imDate').val(d.IncomeDate || _todayStr());
        $('#imCategory').val(d.CategoryUID || '');
        if ($.fn.select2 && $('#imCategory').data('select2')) $('#imCategory').trigger('change');
        $('#imNotes').val(d.Notes || '');
    }

    function _updateBankVisibility(pmtName) {
        if (_noBankTypes.indexOf(pmtName) !== -1) {
            $('#imBankSection').hide();
            $('#imBankUID').val('');
        } else {
            $('#imBankSection').show();
        }
    }

    function _ensureIncDatePickers() {
        if (_incPickersInit) return;
        var modalBody = document.querySelector('#incomeModal .modal-body');
        fpIncDate    = flatpickr('#imDate',    { dateFormat: 'Y-m-d', altInput: true, altFormat: 'd M Y', appendTo: modalBody });
        fpIncPmtDate = flatpickr('#imPmtDate', { dateFormat: 'Y-m-d', altInput: true, altFormat: 'd M Y', appendTo: modalBody });
        _incPickersInit = true;
    }

    document.getElementById('incomeModal').addEventListener('shown.bs.modal', function () {
        _ensureIncDatePickers();
    });

    // ── Open for Add ─────────────────────────────────────────
    $(document).on('click', '#addIncomeBtn', function () {
        _resetIncModal();
        initIncDropzone();
        new bootstrap.Modal(document.getElementById('incomeModal')).show();
        setTimeout(function () { $('#imAmount').focus(); }, 400);
    });

    // ── Open for Edit ─────────────────────────────────────────
    $(document).on('click', '.incEdit', function () {
        var uid = $(this).data('uid');
        $.ajax({
            url: '/indirectincome/getIncomeDetail', method: 'POST',
            data: { IncomeUID: uid, [CsrfName]: CsrfToken },
            success: function (resp) {
                if (resp.Error) { showToastNotification(resp.Message, 'error'); return; }
                _resetIncModal();
                initIncDropzone();
                _populateIncModal(resp.Data);
                new bootstrap.Modal(document.getElementById('incomeModal')).show();
            }
        });
    });

    // ── Mark as Received toggle ──────────────────────────────
    $('#imMarkReceivedBtn').on('click', function () {
        var isReceived = $(this).hasClass('btn-success');
        isReceived = !isReceived;
        if (isReceived) {
            $(this).removeClass('btn-outline-secondary').addClass('btn-success')
                   .html('<i class="bx bx-check me-1"></i>Received');
            $('#imPaymentSection').slideDown(200);
        } else {
            $(this).removeClass('btn-success').addClass('btn-outline-secondary')
                   .html('<i class="bx bx-credit-card me-1"></i>Mark as Received');
            $('#imPaymentSection').slideUp(200);
        }
    });

    // ── Payment type pills ───────────────────────────────────
    $(document).on('click', '.inc-pmt-pill', function () {
        $('.inc-pmt-pill').removeClass('btn-primary').addClass('btn-outline-secondary');
        $(this).removeClass('btn-outline-secondary').addClass('btn-primary');
        $('#imPmtTypeUID').val($(this).data('uid'));
        _updateBankVisibility($(this).data('name') || '');
    });

    // ── Add Category (nested) ────────────────────────────────
    $('#imAddCategoryBtn').on('click', function () {
        $('#newIncomeCategoryName').val('').removeClass('is-invalid');
        new bootstrap.Modal(document.getElementById('addIncomeCategoryModal')).show();
        setTimeout(function () { $('#newIncomeCategoryName').focus(); }, 350);
    });

    $('#saveIncomeCategoryBtn').on('click', function () {
        var name = $.trim($('#newIncomeCategoryName').val());
        if (!name) { $('#newIncomeCategoryName').addClass('is-invalid'); return; }
        $('#newIncomeCategoryName').removeClass('is-invalid');
        var $btn = $(this).prop('disabled', true).text('Adding…');

        $.ajax({
            url: '/indirectincome/addCategory', method: 'POST',
            data: { CategoryName: name, [CsrfName]: CsrfToken },
            success: function (resp) {
                $btn.prop('disabled', false).text('Add');
                if (resp.Error) { showToastNotification(resp.Message, 'error'); return; }
                var $sel = $('#imCategory');
                $sel.append(new Option(resp.CategoryName, resp.CategoryUID, true, true));
                if ($.fn.select2 && $sel.data('select2')) $sel.trigger('change');
                bootstrap.Modal.getInstance(document.getElementById('addIncomeCategoryModal')).hide();
                showToastNotification('Category "' + resp.CategoryName + '" added.', 'success');
            }
        });
    });

    // ── Save income ──────────────────────────────────────────
    $('#incSaveBtn').on('click', function () {
        var amount = parseFloat($('#imAmount').val()) || 0;
        if (amount <= 0) {
            showToastNotification('Income amount must be greater than 0.', 'error');
            if (!_incIsEdit) $('#imAmount').focus();
            return;
        }
        var isReceived = _incIsEdit ? 0 : ($('#imMarkReceivedBtn').hasClass('btn-success') ? 1 : 0);
        if (!_incIsEdit && isReceived && !$('#imPmtTypeUID').val()) {
            showToastNotification('Please select a payment type.', 'error'); return;
        }

        var $btn = $(this).prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Saving…');
        var url  = _incIsEdit ? '/indirectincome/updateIncome' : '/indirectincome/addIncome';

        var fd = new FormData();
        fd.append(CsrfName,          CsrfToken);
        fd.append('IncomeUID',       $('#imUID').val());
        fd.append('Amount',          amount);
        fd.append('IncomeDate',      $('#imDate').val());
        fd.append('CategoryUID',     $('#imCategory').val());
        fd.append('Notes',           $('#imNotes').val());
        fd.append('IsReceived',      isReceived ? 1 : 0);
        fd.append('PaymentDate',     $('#imPmtDate').val());
        fd.append('PaymentTypeUID',  $('#imPmtTypeUID').val());
        fd.append('BankAccountUID',  $('#imBankUID').val());
        fd.append('PaymentNotes',    $('#imPmtNotes').val());
        fd.append('Filter',          JSON.stringify(Filter));
        fd.append('RowLimit',        RowLimit);

        if (incDropzone) {
            incDropzone.files.forEach(function (f) { fd.append('Attachments[]', f); });
        }

        $.ajax({
            url: url, method: 'POST', data: fd, processData: false, contentType: false,
            success: function (resp) {
                $btn.prop('disabled', false).html('<i class="bx bx-check me-1"></i>Save');
                if (resp.Error) { showToastNotification(resp.Message, 'error'); return; }
                bootstrap.Modal.getInstance(document.getElementById('incomeModal')).hide();
                _renderList(resp);
                showToastNotification(resp.Message, 'success');
            },
            error: function () {
                $btn.prop('disabled', false).html('<i class="bx bx-check me-1"></i>Save');
                showToastNotification('An error occurred. Please try again.', 'error');
            }
        });
    });

    // ── Select2 on category ──────────────────────────────────
    if ($.fn.select2) {
        $('#imCategory').select2({
            dropdownParent: $('#incomeModal'),
            placeholder: 'Select Category',
            allowClear: true,
            width: '100%',
        });
    }

});
</script>
