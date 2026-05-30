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

                    $cntPending   = ($stats['Pending']['count']  ?? 0) + ($stats['Partial']['count']  ?? 0);
                    $amtPending   = ($stats['Pending']['amount'] ?? 0) + ($stats['Partial']['amount'] ?? 0);
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
                            <div>
                                <h5 class="trans-ph-title mb-0"><?php echo htmlspecialchars($PageTitle ?? 'Indirect Income'); ?></h5>
                                <?php if (!empty($PageDescription)): ?>
                                <div class="text-muted" style="font-size:.76rem;"><?php echo htmlspecialchars($PageDescription); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="incManageCatBtn">
                                <i class="bx bx-category me-1"></i>Categories
                            </button>
                            <button type="button" class="btn btn-primary" id="addIncomeBtn">
                                <i class="bx bx-plus me-1"></i>Add Income
                            </button>
                        </div>
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
                                        <th class="position-relative">Category / Notes <a href="javascript:void(0);" id="incCatFilterBtn" class="text-body ms-1" onclick="toggleIncCatFilter();event.stopPropagation();" title="Filter by Category"><i class="bx bx-filter-alt fs-6 align-middle"></i></a></th>
                                        <th class="position-relative">Status <a href="javascript:void(0);" id="incStatusFilterBtn" class="text-body ms-1" onclick="toggleIncStatusFilter();event.stopPropagation();" title="Filter by Status"><i class="bx bx-filter-alt fs-6 align-middle"></i></a></th>
                                        <th class="position-relative">Mode <a href="javascript:void(0);" id="incModeFilterBtn" class="text-body ms-1" onclick="toggleIncModeFilter();event.stopPropagation();" title="Filter by Mode"><i class="bx bx-filter-alt fs-6 align-middle"></i></a></th>
                                        <th class="position-relative">Last Updated <?php if (count($OrgUsers ?? []) > 1): ?><a href="javascript:void(0);" id="incUserFilterBtn" class="text-body ms-1" onclick="toggleIncUserFilter();event.stopPropagation();" title="Filter by User"><i class="bx bx-filter-alt fs-6 align-middle"></i></a><?php endif; ?></th>
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
                    <div class="col-lg-8" id="imFormColumn">

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
                                <div class="dropzone needsclick p-3 dz-clickable w-100" id="incAttachDropzone">
                                    <div class="dz-message needsclick text-center">
                                        <i class="upload-icon mb-3"></i>
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

<!-- Income Category Manager Modal -->
<div class="modal fade" id="incCatManagerModal" tabindex="-1" aria-hidden="true"
     style="backdrop-filter:blur(6px);-webkit-backdrop-filter:blur(6px);">
    <div class="modal-dialog modal-dialog-scrollable" style="max-width:480px;">
        <div class="modal-content">
            <div class="modal-header bg-white border-bottom d-flex align-items-center justify-content-between px-3 py-2 trans-theme">
                <div class="d-flex align-items-center gap-2">
                    <div class="modal-doc-icon" style="background:#dcfce7;">
                        <i class="bx bx-category modal-doc-icon-inner" style="color:#059669;"></i>
                    </div>
                    <h6 class="modal-title mb-0">Manage Categories</h6>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-sm btn-outline-primary" id="incAddNewCatFromMgr">
                        <i class="bx bx-plus me-1"></i>Add New
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger" data-bs-dismiss="modal">
                        <i class="bx bx-x me-1"></i>Close
                    </button>
                </div>
            </div>
            <div class="modal-body p-0">
                <div class="px-3 pt-3 pb-2">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text"><i class="bx bx-search"></i></span>
                        <input type="text" id="incCatMgrSearch" class="form-control" placeholder="Search categories...">
                    </div>
                </div>
                <ul class="list-group list-group-flush" id="incCatMgrList" style="min-height:150px;max-height:380px;overflow-y:auto;">
                    <li class="list-group-item text-center py-4">
                        <span class="spinner-border spinner-border-sm text-success"></span>
                    </li>
                </ul>
                <div class="d-flex justify-content-center py-2" id="incCatMgrPagination"></div>
            </div>
        </div>
    </div>
</div>

<?php $this->load->view('transactions/indirectincome/partials/_add_category_modal'); ?>

<?php
$rpAccentColor = '#059669'; $rpAccentBg = '#d1fae5';
$rpPartyIcon   = 'bx-trending-up'; $rpDocLabel = 'Income';
$rpTotalIcon   = 'bx-trending-up'; $rpBtnLabel = 'Mark as Received';
$this->load->view('common/transactions/payment_modal');
?>

<?php $this->load->view('common/transactions/footer'); ?>

<!-- ══════════════════════════════════════════════════════════════════════════
     Income Detail View Modal
══════════════════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="incDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-lg">
        <div class="modal-content">

            <div class="modal-header border-bottom px-3 py-2" id="incDetailHeader" style="background:#f0fdf4;">
                <div class="d-flex align-items-center gap-3 flex-grow-1">
                    <div class="modal-doc-icon" style="background:#dcfce7;">
                        <i class="bx bx-trending-up modal-doc-icon-inner" style="color:#059669;"></i>
                    </div>
                    <div>
                        <div class="fw-semibold" id="incDetailNum" style="font-size:1rem;color:#111;letter-spacing:.2px;"></div>
                        <div style="font-size:.72rem;color:#6b7280;" id="incDetailDate"></div>
                    </div>
                    <span class="trans-badge ms-1" id="incDetailBadge"></span>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-0" id="incDetailBody">
                <div class="d-flex justify-content-center align-items-center py-5">
                    <div class="spinner-border spinner-border-sm text-success"></div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- ── Status Filter Box ─────────────────────────────────────────────────── -->
<div id="incStatusFilterBox" class="card mp-filterbox" style="min-width:200px;z-index:9999;display:none;position:fixed;">
    <div class="catg-filter-header">
        <span class="catg-filter-title"><i class="bx bx-check-circle me-1"></i> Status</span>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-primary" id="incStatusFilterCount" style="display:none;"></span>
            <button type="button" class="catg-filter-close-btn" onclick="closeIncStatusFilter()" title="Close">&times;</button>
        </div>
    </div>
    <div class="catg-select-all-wrap">
        <input type="checkbox" class="form-check-input" id="incStatusSelectAll" onchange="toggleAllIncStatuses(this)">
        <label class="small fw-semibold mb-0" for="incStatusSelectAll" id="incStatusSelectAllLabel">Select All</label>
    </div>
    <div id="incStatusList" class="catg-list" style="max-height:160px;overflow-y:auto;">
        <label class="catg-list-item"><input class="form-check-input inc-sf-chk" type="checkbox" value="Pending"><span>Pending</span></label>
        <label class="catg-list-item"><input class="form-check-input inc-sf-chk" type="checkbox" value="Partial"><span>Partial</span></label>
        <label class="catg-list-item"><input class="form-check-input inc-sf-chk" type="checkbox" value="Received"><span>Received</span></label>
        <label class="catg-list-item"><input class="form-check-input inc-sf-chk" type="checkbox" value="Cancelled"><span>Cancelled</span></label>
    </div>
    <div class="catg-filter-footer">
        <button type="button" class="btn btn-primary btn-sm" onclick="applyIncStatusFilter()"><i class="bx bx-check me-1"></i>Apply</button>
        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="resetIncStatusFilter()"><i class="bx bx-reset me-1"></i>Reset</button>
    </div>
</div>

<!-- ── Mode Filter Box ───────────────────────────────────────────────────── -->
<div id="incModeFilterBox" class="card mp-filterbox" style="min-width:200px;z-index:9999;display:none;position:fixed;">
    <div class="catg-filter-header">
        <span class="catg-filter-title"><i class="bx bx-credit-card me-1"></i> Mode</span>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-primary" id="incModeFilterCount" style="display:none;"></span>
            <button type="button" class="catg-filter-close-btn" onclick="closeIncModeFilter()" title="Close">&times;</button>
        </div>
    </div>
    <div class="catg-select-all-wrap">
        <input type="checkbox" class="form-check-input" id="incModeSelectAll" onchange="toggleAllIncModes(this)">
        <label class="small fw-semibold mb-0" for="incModeSelectAll" id="incModeSelectAllLabel">Select All</label>
    </div>
    <div id="incModeList" class="catg-list" style="max-height:180px;overflow-y:auto;"></div>
    <div class="catg-filter-footer">
        <button type="button" class="btn btn-primary btn-sm" onclick="applyIncModeFilter()"><i class="bx bx-check me-1"></i>Apply</button>
        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="resetIncModeFilter()"><i class="bx bx-reset me-1"></i>Reset</button>
    </div>
</div>

<!-- ── User Filter Box ───────────────────────────────────────────────────── -->
<?php if (count($OrgUsers ?? []) > 1): ?>
<div id="incUserFilterBox" class="card mp-filterbox" style="min-width:200px;z-index:9999;display:none;position:fixed;">
    <div class="catg-filter-header">
        <span class="catg-filter-title"><i class="bx bx-user me-1"></i> User</span>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-primary" id="incUserFilterCount" style="display:none;"></span>
            <button type="button" class="catg-filter-close-btn" onclick="closeIncUserFilter()" title="Close">&times;</button>
        </div>
    </div>
    <div class="catg-select-all-wrap">
        <input type="checkbox" class="form-check-input" id="incUserSelectAll" onchange="toggleAllIncUsers(this)">
        <label class="small fw-semibold mb-0" for="incUserSelectAll" id="incUserSelectAllLabel">Select All</label>
    </div>
    <div id="incUserList" class="catg-list" style="max-height:180px;overflow-y:auto;"></div>
    <div class="catg-filter-footer">
        <button type="button" class="btn btn-primary btn-sm" onclick="applyIncUserFilter()"><i class="bx bx-check me-1"></i>Apply</button>
        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="resetIncUserFilter()"><i class="bx bx-reset me-1"></i>Reset</button>
    </div>
</div>
<?php endif; ?>

<!-- ── Category Filter Box ──────────────────────────────────────────────── -->
<div id="incCatFilterBox" class="card mp-filterbox" style="min-width:220px;z-index:9999;display:none;position:fixed;">
    <div class="catg-filter-header">
        <span class="catg-filter-title"><i class="bx bx-category me-1"></i> Category</span>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-primary" id="incCatFilterCount" style="display:none;"></span>
            <button type="button" class="catg-filter-close-btn" onclick="closeIncCatFilter()" title="Close">&times;</button>
        </div>
    </div>
    <div class="catg-filter-search-wrap">
        <div class="input-group input-group-sm">
            <span class="input-group-text"><i class="bx bx-search"></i></span>
            <input type="text" id="incCatFilterSearch" class="form-control" placeholder="Search..." oninput="_filterIncCatList(this.value)">
        </div>
    </div>
    <div class="catg-select-all-wrap">
        <input type="checkbox" class="form-check-input" id="incCatSelectAll" onchange="toggleAllIncCats(this)">
        <label class="small fw-semibold mb-0" for="incCatSelectAll" id="incCatSelectAllLabel">Select All</label>
    </div>
    <div id="incCatFilterList" class="catg-list" style="max-height:200px;overflow-y:auto;">
        <?php if (!empty($categories)): foreach ($categories as $cat): ?>
        <label class="catg-list-item">
            <input class="form-check-input inc-cf-chk" type="checkbox" value="<?php echo (int)$cat->CategoryUID; ?>" data-name="<?php echo htmlspecialchars($cat->CategoryName); ?>">
            <span><?php echo htmlspecialchars($cat->CategoryName); ?></span>
        </label>
        <?php endforeach; else: ?>
        <div class="text-muted text-center py-3" style="font-size:.8rem;">No categories found</div>
        <?php endif; ?>
    </div>
    <div class="catg-filter-footer">
        <button type="button" class="btn btn-primary btn-sm" onclick="applyIncCatFilter()"><i class="bx bx-check me-1"></i>Apply</button>
        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="resetIncCatFilter()"><i class="bx bx-reset me-1"></i>Reset</button>
    </div>
</div>

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

    // Init shared payment modal
    initRecordPaymentModal(
        <?php echo json_encode(array_map(function($t) {
            return ['PaymentTypeUID' => (int)$t->PaymentTypeUID, 'Name' => (string)$t->PaymentTypeName, 'IsCash' => (int)$t->IsCash];
        }, $paymentTypes)); ?>,
        <?php echo json_encode(array_values(array_map(function($b) {
            return ['BankAccountUID' => (int)$b->BankAccountUID, 'BankName' => (string)$b->BankName, 'AccountName' => (string)$b->AccountName, 'IsDefault' => (int)$b->IsDefault];
        }, $bankAccounts))); ?>,
        incCurSymbol
    );
    window.rpAfterSuccess = function (resp) {
        if (resp.SummaryStats) _updateStatCards(resp.SummaryStats);
        _syncSticky();
    };

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
        var partial   = stats.Partial   || { count: 0, amount: 0 };
        var received  = stats.Received  || { count: 0, amount: 0 };
        var cancelled = stats.Cancelled || { count: 0, amount: 0 };
        var pendingCount  = pending.count  + partial.count;
        var pendingAmount = pending.amount + partial.amount;
        var allCount  = pendingCount + received.count + cancelled.count;
        var allAmount = pendingAmount + received.amount;

        $('[data-stat-filter="All"]      .trans-stat-count').text(allCount);
        $('[data-stat-filter="All"]      .trans-stat-amount').text(incCurSymbol + ' ' + _fmtNum(allAmount));
        $('[data-stat-filter="Pending"]  .trans-stat-count').text(pendingCount);
        $('[data-stat-filter="Pending"]  .trans-stat-amount').text(incCurSymbol + ' ' + _fmtNum(pendingAmount));
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

    // ── Income Status Filter ─────────────────────────────────────────────────
    window.toggleIncStatusFilter = function () {
        var $box = $('#incStatusFilterBox');
        if ($box.is(':visible')) { $box.hide(); return; }
        var rect = document.getElementById('incStatusFilterBtn').getBoundingClientRect();
        $box.css({ top: (rect.bottom + 4) + 'px', left: Math.max(4, rect.right - 200) + 'px' }).show();
    };
    window.closeIncStatusFilter = function () { $('#incStatusFilterBox').hide(); };
    window.toggleAllIncStatuses = function (el) {
        var checked = $(el).is(':checked');
        $('.inc-sf-chk').prop('checked', checked);
        $('#incStatusSelectAllLabel').text(checked ? 'Deselect All' : 'Select All');
    };
    window.applyIncStatusFilter = function () {
        var sel = $('.inc-sf-chk:checked').map(function () { return $(this).val(); }).get();
        if (sel.length) { Filter['StatusList'] = sel; } else { delete Filter['StatusList']; }
        $('#incStatusFilterBox').hide();
        var active = sel.length > 0;
        $('#incStatusFilterBtn').toggleClass('text-primary', active);
        $('#incStatusFilterCount').text(sel.length).toggle(active);
        PageNo = 1; getIncomeDetails();
    };
    window.resetIncStatusFilter = function () {
        $('.inc-sf-chk').prop('checked', false);
        $('#incStatusSelectAll').prop('checked', false);
        $('#incStatusSelectAllLabel').text('Select All');
        delete Filter['StatusList'];
        $('#incStatusFilterBox').hide();
        $('#incStatusFilterBtn').removeClass('text-primary');
        $('#incStatusFilterCount').hide().text('');
        PageNo = 1; getIncomeDetails();
    };
    $(document).on('change', '.inc-sf-chk', function () {
        var total = $('.inc-sf-chk').length, chkd = $('.inc-sf-chk:checked').length;
        $('#incStatusSelectAll').prop('checked', total > 0 && total === chkd);
        $('#incStatusSelectAllLabel').text(total > 0 && total === chkd ? 'Deselect All' : 'Select All');
    });
    $(document).on('click', function (e) {
        if (!$(e.target).closest('#incStatusFilterBox, #incStatusFilterBtn').length) $('#incStatusFilterBox').hide();
    });
    // ────────────────────────────────────────────────────────────────────────

    // ── Income Mode Filter ───────────────────────────────────────────────────
    var _incModeData = <?php echo json_encode(array_map(function($t) {
        return ['uid' => (int)$t->PaymentTypeUID, 'name' => (string)$t->PaymentTypeName];
    }, $paymentTypes ?? [])); ?>;

    (function () {
        if (!_incModeData.length) {
            $('#incModeList').html('<div class="text-muted text-center py-3" style="font-size:.8rem;">No data</div>');
            return;
        }
        var html = '';
        _incModeData.forEach(function (m) {
            html += '<label class="catg-list-item">' +
                        '<input class="form-check-input inc-mf-chk" type="checkbox" value="' + m.uid + '">' +
                        '<span>' + $('<span>').text(m.name).html() + '</span>' +
                    '</label>';
        });
        $('#incModeList').html(html);
    })();

    window.toggleIncModeFilter = function () {
        var $box = $('#incModeFilterBox');
        if ($box.is(':visible')) { $box.hide(); return; }
        var rect = document.getElementById('incModeFilterBtn').getBoundingClientRect();
        $box.css({ top: (rect.bottom + 4) + 'px', left: Math.max(4, rect.right - 200) + 'px' }).show();
    };
    window.closeIncModeFilter = function () { $('#incModeFilterBox').hide(); };
    window.toggleAllIncModes  = function (el) {
        var checked = $(el).is(':checked');
        $('.inc-mf-chk').prop('checked', checked);
        $('#incModeSelectAllLabel').text(checked ? 'Deselect All' : 'Select All');
    };
    window.applyIncModeFilter = function () {
        var sel = $('.inc-mf-chk:checked').map(function () { return $(this).val(); }).get();
        if (sel.length) { Filter['PaymentTypeUIDs'] = sel; } else { delete Filter['PaymentTypeUIDs']; }
        $('#incModeFilterBox').hide();
        var active = sel.length > 0;
        $('#incModeFilterBtn').toggleClass('text-primary', active);
        $('#incModeFilterCount').text(sel.length).toggle(active);
        PageNo = 1; getIncomeDetails();
    };
    window.resetIncModeFilter = function () {
        $('.inc-mf-chk').prop('checked', false);
        $('#incModeSelectAll').prop('checked', false);
        $('#incModeSelectAllLabel').text('Select All');
        delete Filter['PaymentTypeUIDs'];
        $('#incModeFilterBox').hide();
        $('#incModeFilterBtn').removeClass('text-primary');
        $('#incModeFilterCount').hide().text('');
        PageNo = 1; getIncomeDetails();
    };
    $(document).on('change', '.inc-mf-chk', function () {
        var total = $('.inc-mf-chk').length, chkd = $('.inc-mf-chk:checked').length;
        $('#incModeSelectAll').prop('checked', total > 0 && total === chkd);
        $('#incModeSelectAllLabel').text(total > 0 && total === chkd ? 'Deselect All' : 'Select All');
    });
    $(document).on('click', function (e) {
        if (!$(e.target).closest('#incModeFilterBox, #incModeFilterBtn').length) $('#incModeFilterBox').hide();
    });
    // ────────────────────────────────────────────────────────────────────────

    // ── Income User Filter ───────────────────────────────────────────────────
    var _incUserData = <?php echo json_encode(array_map(function($u) {
        return ['uid' => (int)$u->UserUID, 'name' => (string)$u->FullName];
    }, $OrgUsers ?? [])); ?>;

    if (_incUserData.length > 1) {
        (function () {
            var html = '';
            _incUserData.forEach(function (u) {
                html += '<label class="catg-list-item">' +
                            '<input class="form-check-input inc-uf-chk" type="checkbox" value="' + u.uid + '">' +
                            '<span>' + $('<span>').text(u.name).html() + '</span>' +
                        '</label>';
            });
            $('#incUserList').html(html);
        })();
    }

    window.toggleIncUserFilter = function () {
        var $box = $('#incUserFilterBox');
        if (!$box.length || $box.is(':visible')) { if ($box.length) $box.hide(); return; }
        var rect = document.getElementById('incUserFilterBtn').getBoundingClientRect();
        $box.css({ top: (rect.bottom + 4) + 'px', left: Math.max(4, rect.right - 200) + 'px' }).show();
    };
    window.closeIncUserFilter = function () { $('#incUserFilterBox').hide(); };
    window.toggleAllIncUsers  = function (el) {
        var checked = $(el).is(':checked');
        $('.inc-uf-chk').prop('checked', checked);
        $('#incUserSelectAllLabel').text(checked ? 'Deselect All' : 'Select All');
    };
    window.applyIncUserFilter = function () {
        var sel = $('.inc-uf-chk:checked').map(function () { return $(this).val(); }).get();
        if (sel.length) { Filter['UpdatedByUIDs'] = sel; } else { delete Filter['UpdatedByUIDs']; }
        $('#incUserFilterBox').hide();
        var active = sel.length > 0;
        $('#incUserFilterBtn').toggleClass('text-primary', active);
        $('#incUserFilterCount').text(sel.length).toggle(active);
        PageNo = 1; getIncomeDetails();
    };
    window.resetIncUserFilter = function () {
        $('.inc-uf-chk').prop('checked', false);
        $('#incUserSelectAll').prop('checked', false);
        $('#incUserSelectAllLabel').text('Select All');
        delete Filter['UpdatedByUIDs'];
        $('#incUserFilterBox').hide();
        $('#incUserFilterBtn').removeClass('text-primary');
        $('#incUserFilterCount').hide().text('');
        PageNo = 1; getIncomeDetails();
    };
    $(document).on('change', '.inc-uf-chk', function () {
        var total = $('.inc-uf-chk').length, chkd = $('.inc-uf-chk:checked').length;
        $('#incUserSelectAll').prop('checked', total > 0 && total === chkd);
        $('#incUserSelectAllLabel').text(total > 0 && total === chkd ? 'Deselect All' : 'Select All');
    });
    $(document).on('click', function (e) {
        if (!$(e.target).closest('#incUserFilterBox, #incUserFilterBtn').length) $('#incUserFilterBox').hide();
    });
    // ────────────────────────────────────────────────────────────────────────

    // ── Income Category Filter ────────────────────────────────────────────────
    window._filterIncCatList = function (term) {
        var t = (term || '').toLowerCase();
        $('#incCatFilterList .catg-list-item').each(function () {
            var name = $(this).find('span').text().toLowerCase();
            $(this).toggle(!t || name.indexOf(t) !== -1);
        });
    };

    window.toggleIncCatFilter = function () {
        var $box = $('#incCatFilterBox');
        if ($box.is(':visible')) { $box.hide(); return; }
        var rect = document.getElementById('incCatFilterBtn').getBoundingClientRect();
        $box.css({ top: (rect.bottom + 4) + 'px', left: Math.max(4, rect.right - 220) + 'px' }).show();
        $('#incCatFilterSearch').val('');
        _filterIncCatList('');
    };
    window.closeIncCatFilter = function () { $('#incCatFilterBox').hide(); };
    window.toggleAllIncCats  = function (el) {
        var checked = $(el).is(':checked');
        $('#incCatFilterList .catg-list-item:visible .inc-cf-chk').prop('checked', checked);
        $('#incCatSelectAllLabel').text(checked ? 'Deselect All' : 'Select All');
    };
    window.applyIncCatFilter = function () {
        var sel = $('.inc-cf-chk:checked').map(function () { return $(this).val(); }).get();
        if (sel.length) { Filter['CategoryUIDs'] = sel; } else { delete Filter['CategoryUIDs']; }
        $('#incCatFilterBox').hide();
        var active = sel.length > 0;
        $('#incCatFilterBtn').toggleClass('text-primary', active);
        $('#incCatFilterCount').text(sel.length).toggle(active);
        PageNo = 1; getIncomeDetails();
    };
    window.resetIncCatFilter = function () {
        $('.inc-cf-chk').prop('checked', false);
        $('#incCatSelectAll').prop('checked', false);
        $('#incCatSelectAllLabel').text('Select All');
        $('#incCatFilterSearch').val('');
        _filterIncCatList('');
        delete Filter['CategoryUIDs'];
        $('#incCatFilterBox').hide();
        $('#incCatFilterBtn').removeClass('text-primary');
        $('#incCatFilterCount').hide().text('');
        PageNo = 1; getIncomeDetails();
    };
    $(document).on('change', '.inc-cf-chk', function () {
        var visible = $('#incCatFilterList .catg-list-item:visible .inc-cf-chk');
        var chkd    = visible.filter(':checked').length;
        $('#incCatSelectAll').prop('checked', visible.length > 0 && visible.length === chkd);
        $('#incCatSelectAllLabel').text(visible.length > 0 && visible.length === chkd ? 'Deselect All' : 'Select All');
    });
    $(document).on('click', function (e) {
        if (!$(e.target).closest('#incCatFilterBox, #incCatFilterBtn').length) $('#incCatFilterBox').hide();
    });

    // Rebuild the category filter list whenever _incCatData changes
    function _rebuildIncCatFilterList() {
        var selected = $('.inc-cf-chk:checked').map(function () { return $(this).val(); }).get();
        var html = '';
        if (_incCatData.length) {
            _incCatData.forEach(function (c) {
                var isChk = selected.indexOf(String(c.uid)) !== -1 ? 'checked' : '';
                html += '<label class="catg-list-item">' +
                    '<input class="form-check-input inc-cf-chk" type="checkbox" value="' + c.uid + '" data-name="' + $('<span>').text(c.name).html() + '" ' + isChk + '>' +
                    '<span>' + $('<span>').text(c.name).html() + '</span>' +
                    '</label>';
            });
        } else {
            html = '<div class="text-muted text-center py-3" style="font-size:.8rem;">No categories found</div>';
        }
        $('#incCatFilterList').html(html);
        _filterIncCatList($('#incCatFilterSearch').val());
    }
    // ────────────────────────────────────────────────────────────────────────

    function _clearIncStatusFilter() {
        delete Filter['StatusList'];
        $('.inc-sf-chk').prop('checked', false);
        $('#incStatusSelectAll').prop('checked', false);
        $('#incStatusSelectAllLabel').text('Select All');
        $('#incStatusFilterBtn').removeClass('text-primary');
        $('#incStatusFilterCount').hide().text('');
    }

    // ── Stat card click ──────────────────────────────────────
    $(document).on('click', '[data-stat-filter]', function () {
        var status = $(this).data('stat-filter') || 'All';
        $('.trans-stat-card').removeClass('active-stat');
        $(this).addClass('active-stat');
        $('.inc-status-tab').removeClass('active');
        $('.inc-status-tab[data-status="' + status + '"]').addClass('active');
        _clearIncStatusFilter();
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
        _clearIncStatusFilter();
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

    // ── Payment history panel ────────────────────────────────────────────────
    var _incPayPanelUID = null;
    var $incPayPanel    = $('#payDetailPanel');
    var $incPayBody     = $('#payDetailBody');
    var $incPayTitle    = $('#payPanelTitle');

    function _openIncPayPanel($trigger) {
        var transUID = $trigger.data('trans-uid');
        var transNum = $trigger.data('trans-num') || '';
        var fetchUrl = $trigger.data('fetch-url') || '/indirectincome/getPaymentHistory';

        var rect   = $trigger[0].getBoundingClientRect();
        var panelW = 290;
        var left   = rect.left;
        var top    = rect.bottom + 6;
        if (left + panelW + 16 > window.innerWidth) left = window.innerWidth - panelW - 16;

        $incPayTitle.text(transNum ? 'Payments — ' + transNum : 'Payments');
        $incPayBody.html('<div class="text-center py-3"><span class="spinner-border spinner-border-sm text-success"></span></div>');
        $incPayPanel.css({ top: top, left: left }).show();
        _incPayPanelUID = transUID;

        $.ajax({
            url: fetchUrl, method: 'POST',
            data: { TransUID: transUID, [CsrfName]: CsrfToken },
            success: function (resp) {
                if (resp && !resp.Error && resp.Payments && resp.Payments.length) {
                    var html = '';
                    resp.Payments.forEach(function (p, i) {
                        if (i > 0) html += '<hr style="margin:8px 0;border-color:#f0f0f0;">';
                        var amt  = parseFloat(p.Amount || 0).toLocaleString('en-IN', { minimumFractionDigits: incDecPoints, maximumFractionDigits: incDecPoints });
                        var date = '';
                        if (p.CreatedOn) {
                            var d = new Date(p.CreatedOn.replace(' ', 'T'));
                            date  = ('0' + d.getDate()).slice(-2) + '-' + ('0' + (d.getMonth() + 1)).slice(-2) + '-' + d.getFullYear();
                        }
                        html += '<div class="d-flex justify-content-between align-items-start gap-2">';
                        html += '  <div style="min-width:0;">';
                        html += '    <div style="font-size:.83rem;font-weight:600;color:#059669;">' + incCurSymbol + ' ' + amt + '</div>';
                        html += '    <div style="font-size:.75rem;color:#566a7f;">' + (p.PaymentTypeName || '—') + '</div>';
                        if (date) html += '  <div style="font-size:.72rem;color:#aaa;margin-top:1px;">' + date + '</div>';
                        html += '  </div>';
                        html += '</div>';
                    });
                    $incPayBody.html(html);
                } else {
                    $incPayBody.html('<p class="text-muted mb-0" style="font-size:.8rem;">No payments found.</p>');
                }
            },
            error: function () {
                $incPayBody.html('<p class="text-danger mb-0" style="font-size:.8rem;">Failed to load payments.</p>');
            }
        });
    }

    $(document).on('click', '.pay-mode-clickable', function (e) {
        if ($(e.target).closest('.transPayAttachBtn').length) return;
        e.stopPropagation();
        var transUID = $(this).data('trans-uid');
        if (_incPayPanelUID === transUID && $incPayPanel.is(':visible')) { $incPayPanel.hide(); _incPayPanelUID = null; return; }
        _openIncPayPanel($(this));
    });
    $(document).on('click', '#payPanelClose', function (e) { e.stopPropagation(); $incPayPanel.hide(); _incPayPanelUID = null; });
    $(document).on('click', function (e) {
        if ($incPayPanel.is(':visible') && !$(e.target).closest('#payDetailPanel, .pay-mode-clickable').length) {
            $incPayPanel.hide(); _incPayPanelUID = null;
        }
    });
    $(document).on('keydown', function (e) { if (e.key === 'Escape') { $incPayPanel.hide(); _incPayPanelUID = null; } });
    // ────────────────────────────────────────────────────────────────────────

    // ── Row actions ──────────────────────────────────────────
    $(document).on('click', '.incMarkReceived', function () {
        var uid     = $(this).data('uid');
        var num     = $(this).data('num')     || '';
        var date    = $(this).data('date')    || '';
        var total   = parseFloat($(this).data('total'))   || 0;
        var paid    = parseFloat($(this).data('paid'))    || 0;
        var pending = parseFloat($(this).data('pending')) || total;
        window.rpOpenModal({
            transUID  : uid,
            submitUrl : '/indirectincome/recordPayment',
            docNum    : num,
            docDate   : date,
            total     : total,
            paid      : paid,
            pending   : pending,
        });
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
        if (el.dropzone)  { try { el.dropzone.destroy();  } catch (e) {} }
        if (incDropzone)  { try { incDropzone.destroy();  } catch (e) {} incDropzone = null; }
        Dropzone.instances = Dropzone.instances.filter(function (d) { return d.element !== el; });
        el.classList.remove('dz-started');

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

    var _incCatData = <?php echo json_encode(array_map(function($c) {
        return ['uid' => (int)$c->CategoryUID, 'name' => $c->CategoryName];
    }, $categories)); ?>;

    $('#saveIncomeCategoryBtn').on('click', function () {
        var name = $.trim($('#newIncomeCategoryName').val());
        if (!name) { $('#newIncomeCategoryName').addClass('is-invalid'); return; }
        $('#newIncomeCategoryName').removeClass('is-invalid');
        var uid    = parseInt($('#incCatModalUID').val()) || 0;
        var isEdit = uid > 0;
        var $btn   = $(this).prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>' + (isEdit ? 'Saving…' : 'Adding…'));

        $.ajax({
            url: isEdit ? '/indirectincome/updateCategory' : '/indirectincome/addCategory',
            method: 'POST',
            data: { CategoryUID: uid, CategoryName: name, [CsrfName]: CsrfToken },
            success: function (resp) {
                $btn.prop('disabled', false).html('<i class="bx bx-check me-1"></i><span id="incCatSaveBtnLabel">' + (isEdit ? 'Save' : 'Add') + '</span>');
                if (resp.Error) {
                    $('#incCatSaveError').text(resp.Message).show();
                    return;
                }
                $('#incCatSaveError').hide();
                var $sel = $('#imCategory');
                if (isEdit) {
                    $sel.find('option[value="' + uid + '"]').text(resp.CategoryName);
                    if ($.fn.select2 && $sel.data('select2')) $sel.trigger('change.select2');
                    _incCatData = _incCatData.map(function (c) { return c.uid === uid ? { uid: uid, name: resp.CategoryName } : c; });
                } else {
                    $sel.append(new Option(resp.CategoryName, resp.CategoryUID, true, true));
                    if ($.fn.select2 && $sel.data('select2')) $sel.trigger('change');
                    _incCatData.push({ uid: parseInt(resp.CategoryUID), name: resp.CategoryName });
                }
                _incCatData.sort(function (a, b) { return a.name.localeCompare(b.name); });
                if (typeof _rebuildIncCatFilterList === 'function') _rebuildIncCatFilterList();
                var $mgr = document.getElementById('incCatManagerModal');
                if ($mgr && bootstrap.Modal.getInstance($mgr)) _loadIncCatMgr(1);
                bootstrap.Modal.getInstance(document.getElementById('addIncomeCategoryModal')).hide();
                showToastNotification(resp.Message || (isEdit ? 'Category updated.' : 'Category added.'), 'success');
            }
        });
    });

    // ── Income Category Manager ──────────────────────────────
    function _loadIncCatMgr(pageNo) {
        $.ajax({
            url: '/indirectincome/getCategoryList', method: 'POST',
            data: { PageNo: pageNo || 1, Search: $.trim($('#incCatMgrSearch').val()), [CsrfName]: CsrfToken },
            success: function (resp) {
                if (resp.Error) {
                    $('#incCatMgrList').html('<li class="list-group-item text-danger py-3 text-center">' + resp.Message + '</li>');
                    return;
                }
                $('#incCatMgrList').html(resp.RecordHtmlData);
                $('#incCatMgrPagination').html(resp.Pagination || '');
            }
        });
    }

    $('#incManageCatBtn').on('click', function () {
        $('#incCatMgrSearch').val('');
        new bootstrap.Modal(document.getElementById('incCatManagerModal')).show();
        _loadIncCatMgr(1);
    });

    $('#incAddNewCatFromMgr').on('click', function () {
        $('#incCatModalTitle').text('Add Category');
        $('#incCatSaveBtnLabel').text('Add');
        $('#incCatModalUID').val('0');
        $('#newIncomeCategoryName').val('').removeClass('is-invalid');
        $('#incCatSaveError').hide();
        new bootstrap.Modal(document.getElementById('addIncomeCategoryModal')).show();
        setTimeout(function () { $('#newIncomeCategoryName').focus(); }, 350);
    });

    $('#incCatMgrSearch').on('keyup', debounce(function () { _loadIncCatMgr(1); }, 350));

    $(document).on('click', '#incCatMgrPagination .page-link', function (e) {
        e.preventDefault();
        var match = ($(this).attr('href') || '').match(/\/(\d+)$/);
        if (match) _loadIncCatMgr(parseInt(match[1]));
    });

    $(document).on('click', '.incCatEditBtn', function () {
        $('#incCatModalTitle').text('Edit Category');
        $('#incCatSaveBtnLabel').text('Save');
        $('#incCatModalUID').val($(this).data('uid'));
        $('#newIncomeCategoryName').val($(this).data('name')).removeClass('is-invalid');
        $('#incCatSaveError').hide();
        new bootstrap.Modal(document.getElementById('addIncomeCategoryModal')).show();
        setTimeout(function () { $('#newIncomeCategoryName').focus(); }, 350);
    });

    $(document).on('click', '.incCatDeleteBtn', function () {
        var uid  = parseInt($(this).data('uid'));
        var name = $(this).data('name');
        Swal.fire({
            title: 'Delete Category?',
            html: 'Delete <strong>' + $('<span>').text(name).html() + '</strong>? This cannot be undone.',
            icon: 'warning', showCancelButton: true,
            confirmButtonColor: '#d33', confirmButtonText: 'Delete'
        }).then(function (r) {
            if (!r.isConfirmed) return;
            $.ajax({
                url: '/indirectincome/deleteCategory', method: 'POST',
                data: { CategoryUID: uid, [CsrfName]: CsrfToken },
                success: function (resp) {
                    if (resp.Error) { showToastNotification(resp.Message, 'error'); return; }
                    _incCatData = _incCatData.filter(function (c) { return c.uid !== uid; });
                    if (typeof _rebuildIncCatFilterList === 'function') _rebuildIncCatFilterList();
                    var $opt = $('#imCategory option[value="' + uid + '"]');
                    if ($opt.length) { $opt.remove(); if ($.fn.select2 && $('#imCategory').data('select2')) $('#imCategory').trigger('change'); }
                    _loadIncCatMgr(1);
                    showToastNotification(resp.Message || 'Category deleted.', 'success');
                }
            });
        });
    });

    // ── Save income ──────────────────────────────────────────
    $('#incSaveBtn').on('click', function () {
        var $btn = $(this);

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

        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Saving…');
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

    // ── Income Detail View Modal ──────────────────────────────
    var _incDetailBadgeMap = {
        'Pending':   'trans-badge-Pending',
        'Received':  'trans-badge-Paid',
        'Cancelled': 'trans-badge-Cancelled'
    };
    var _incDetailIconMap = {
        'Pending':   'bx-time',
        'Received':  'bx-check-circle',
        'Cancelled': 'bx-x-circle'
    };

    function _buildIncDetailBody(d) {
        function fmtDate(val) {
            if (!val) return 'N/A';
            var dt = new Date(val);
            return isNaN(dt.getTime()) ? val : dt.toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' });
        }
        function esc(s) { return $('<span>').text(s || '').html(); }

        var pmtHtml = '';
        if (d.PaymentTypeName) {
            pmtHtml += '<div class="mb-2"><span class="badge bg-label-success" style="font-size:.75rem;padding:3px 10px;"><i class="bx bx-credit-card me-1"></i>' + esc(d.PaymentTypeName) + '</span></div>';
        }
        var pmtRows = [];
        if (d.PaymentDate)      pmtRows.push('<i class="bx bx-calendar me-1" style="color:#94a3b8;"></i>' + esc(fmtDate(d.PaymentDate)));
        if (d.BankName)         pmtRows.push('<i class="bx bx-building-house me-1" style="color:#94a3b8;font-size:.7rem;"></i>' + esc(d.BankName));
        if (d.BankAccountName)  pmtRows.push('<span style="padding-left:14px;">' + esc(d.BankAccountName) + '</span>');
        if (d.AccountNumber)    pmtRows.push('<span style="padding-left:14px;font-family:monospace;letter-spacing:.05em;">' + esc(d.AccountNumber) + '</span>');
        if (d.PaymentReference) pmtRows.push('<i class="bx bx-hash me-1" style="color:#94a3b8;"></i>Ref: <span style="font-weight:600;">' + esc(d.PaymentReference) + '</span>');
        if (pmtRows.length) pmtHtml += '<div style="font-size:.78rem;color:#475569;line-height:2.1;">' + pmtRows.join('<br>') + '</div>';

        var taxHtml = '';
        if (parseInt(d.TaxApplicable) && parseFloat(d.TaxAmount) > 0) {
            taxHtml += ' <span class="badge bg-label-info" style="font-size:.65rem;">Tax ' + esc(d.TaxPercentage) + '%</span>';
        }

        var boxS = 'background:#f8fafc;border:1px solid #e9ecef;border-radius:8px;padding:12px 14px;height:100%;';
        var lb   = 'font-size:.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.7px;color:#94a3b8;margin-bottom:6px;';
        var valS = 'font-size:.93rem;font-weight:600;color:#1e293b;line-height:1.3;';

        function field(label, content, extraValStyle) {
            return '<div class="col-sm-6"><div style="' + boxS + '">' +
                       '<div style="' + lb + '">' + label + '</div>' +
                       '<div style="' + valS + (extraValStyle || '') + '">' + content + '</div>' +
                   '</div></div>';
        }
        function field12(label, content) {
            return '<div class="col-12"><div style="' + boxS + '">' +
                       '<div style="' + lb + '">' + label + '</div>' +
                       '<div style="font-size:.88rem;color:#374151;line-height:1.6;">' + content + '</div>' +
                   '</div></div>';
        }

        var html = '<div class="p-3"><div class="row g-2">';
        html += field('Income Date', esc(fmtDate(d.IncomeDate)));
        html += field('Amount', incCurSymbol + ' ' + parseFloat(d.Amount || 0).toFixed(incDecPoints) + taxHtml, 'font-size:1.05rem;');
        html += field('Category', d.CategoryName
            ? '<span class="badge text-bg-light border" style="font-size:.78rem;font-weight:600;padding:5px 10px;">' + esc(d.CategoryName) + '</span>'
            : '<span class="text-muted" style="font-size:.85rem;font-weight:400;">Not set</span>');
        html += field('Created On', esc(fmtDate(d.CreatedOn)));
        if (pmtHtml) {
            html += '<div class="col-12"><div style="' + boxS + 'border-left:3px solid #059669;">' +
                        '<div style="' + lb + '">Payment Details</div>' + pmtHtml +
                    '</div></div>';
        } else {
            html += field('Payment', '<span class="text-muted" style="font-weight:400;font-size:.85rem;">No payment recorded</span>');
        }
        if (d.Notes)        html += field12('Notes', esc(d.Notes));
        if (d.PaymentNotes) html += field12('Payment Notes', esc(d.PaymentNotes));
        html += '<div class="col-12"><div style="font-size:.75rem;color:#94a3b8;padding:8px 2px 0;border-top:1px solid #f1f5f9;">' +
                    'Last updated' +
                    (d.UpdatedByName ? ' by <span style="color:#475569;font-weight:600;">' + esc(d.UpdatedByName) + '</span>' : '') +
                    (d.UpdatedOn ? ' &middot; ' + esc(fmtDate(d.UpdatedOn)) : '') +
                '</div></div>';
        html += '</div></div>';
        return html;
    }

    $(document).on('click', '.incViewDetail', function () {
        var uid = $(this).data('uid');
        var $modal = $('#incDetailModal');

        $('#incDetailNum').text('Loading…');
        $('#incDetailDate').text('');
        $('#incDetailBadge').attr('class', 'trans-badge ms-1').text('');
        $('#incDetailBody').html('<div class="d-flex justify-content-center align-items-center py-5"><div class="spinner-border spinner-border-sm text-success"></div></div>');

        new bootstrap.Modal($modal[0]).show();

        $.ajax({
            url: '/indirectincome/getIncomeDetail', method: 'POST',
            data: { IncomeUID: uid, [CsrfName]: CsrfToken },
            success: function (resp) {
                if (resp.Error) {
                    $('#incDetailBody').html('<div class="text-danger text-center py-4">' + resp.Message + '</div>');
                    return;
                }
                var d = resp.Data;
                var status = d.DocStatus || 'Pending';
                var badgeClass = _incDetailBadgeMap[status] || 'trans-badge-Draft';
                var icon = _incDetailIconMap[status] || 'bx-circle';

                $('#incDetailNum').text(d.IncomeNumber || '—');
                if (d.IncomeDate) {
                    var dt = new Date(d.IncomeDate);
                    $('#incDetailDate').text(dt.toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' }));
                }
                $('#incDetailBadge').attr('class', 'trans-badge ms-1 ' + badgeClass)
                    .html('<i class="bx ' + icon + '" style="font-size:.8rem;"></i> ' + status);

                $('#incDetailBody').html(_buildIncDetailBody(d));
            },
            error: function () {
                $('#incDetailBody').html('<div class="text-danger text-center py-4">Failed to load income details.</div>');
            }
        });
    });

});
</script>

<?php $this->load->view('common/transactions/print_modals'); ?>
<script src="/js/transactions/attachments.js"></script>
