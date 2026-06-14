<?php defined('BASEPATH') or exit('No direct script access allowed');
$this->load->view('common/transactions/header'); ?>

<div class="layout-wrapper layout-horizontal layout-content-navbar">
    <div class="layout-container">

        <?php $this->load->view('common/menu_view'); ?>

        <div class="layout-page">
            <div class="content-wrapper apex-content">
                <?php $this->load->view('common/apex/page_header', [
                    'pageTitle'       => $PageTitle       ?? 'Expenses',
                    'pageDescription' => $PageDescription ?? 'Track and manage business expenses',
                ]); ?>
                <?php
                $stats   = $SummaryStats ?? [];
                $cur     = htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹');
                $dec     = (int)($JwtData->GenSettings->DecimalPoints ?? 2);

                $cntPending   = ($stats['Pending']['count']  ?? 0) + ($stats['Partial']['count']  ?? 0);
                $amtPending   = ($stats['Pending']['amount'] ?? 0) + ($stats['Partial']['amount'] ?? 0);
                $cntPaid      = $stats['Paid']['count']      ?? 0;
                $amtPaid      = $stats['Paid']['amount']     ?? 0;
                $cntCancelled = $stats['Cancelled']['count'] ?? 0;
                $cntAll       = $cntPending + $cntPaid + $cntCancelled;
                $amtAll       = $amtPending + $amtPaid;

                $categories   = $Categories   ?? [];
                $paymentTypes = $PaymentTypes ?? [];
                $bankAccounts = $BankAccounts ?? [];
                ?>

                <!-- ── Stats Strip ───────────────────────────────────────────── -->
                <div class="apex-stats-strip">
                    <a href="javascript:void(0);" class="apex-stat-item active" data-status="All" data-stat-filter="All" style="--stat-color:#a855f7">
                        <div class="apex-stat-icon" style="background:#fdf4ff"><i class="bx bx-receipt" style="color:#a855f7"></i></div>
                        <div class="apex-stat-body">
                            <div class="apex-stat-label">All Expenses</div>
                            <div class="apex-stat-bottom">
                                <span class="apex-stat-count"><?php echo number_format($cntAll); ?></span>
                                <span class="apex-stat-amount"><?php echo $cur . ' ' . number_format((float)$amtAll, $dec, '.', ','); ?></span>
                            </div>
                        </div>
                    </a>
                    <a href="javascript:void(0);" class="apex-stat-item" data-status="Pending" data-stat-filter="Pending" style="--stat-color:#f97316">
                        <div class="apex-stat-icon" style="background:#fff7ed"><i class="bx bx-time" style="color:#f97316"></i></div>
                        <div class="apex-stat-body">
                            <div class="apex-stat-label">Pending</div>
                            <div class="apex-stat-bottom">
                                <span class="apex-stat-count"><?php echo number_format($cntPending); ?></span>
                                <span class="apex-stat-amount"><?php echo $cur . ' ' . number_format((float)$amtPending, $dec, '.', ','); ?></span>
                            </div>
                        </div>
                    </a>
                    <a href="javascript:void(0);" class="apex-stat-item" data-status="Paid" data-stat-filter="Paid" style="--stat-color:#16a34a">
                        <div class="apex-stat-icon" style="background:#dcfce7"><i class="bx bx-check-circle" style="color:#16a34a"></i></div>
                        <div class="apex-stat-body">
                            <div class="apex-stat-label">Paid</div>
                            <div class="apex-stat-bottom">
                                <span class="apex-stat-count"><?php echo number_format($cntPaid); ?></span>
                                <span class="apex-stat-amount"><?php echo $cur . ' ' . number_format((float)$amtPaid, $dec, '.', ','); ?></span>
                            </div>
                        </div>
                    </a>
                    <a href="javascript:void(0);" class="apex-stat-item" data-status="Cancelled" data-stat-filter="Cancelled" style="--stat-color:#64748b">
                        <div class="apex-stat-icon" style="background:#f1f5f9"><i class="bx bx-x-circle" style="color:#64748b"></i></div>
                        <div class="apex-stat-body">
                            <div class="apex-stat-label">Cancelled</div>
                            <div class="apex-stat-bottom">
                                <span class="apex-stat-count"><?php echo number_format($cntCancelled); ?></span>
                                <span class="apex-stat-amount">&nbsp;</span>
                            </div>
                        </div>
                    </a>
                </div>

                <div class="container-xxl flex-grow-1 py-3">

                    <!-- ── Main Card ──────────────────────────────────────── -->
                    <div class="card">

                        <!-- Filter Row -->
                        <div class="apex-filter-row">
                            <div class="r2k-search-wrap">
                                <i class="bx bx-search r2k-si"></i>
                                <input type="text" id="searchExpenseData" placeholder="Expense # or category...">
                            </div>
                            <a href="javascript:void(0);" id="expCatFilterBtn" class="apex-filter-btn" onclick="toggleExpCatFilter(); event.stopPropagation();" title="Filter by Category"><i class="bx bx-category me-1"></i>Category</a>
                            <a href="javascript:void(0);" id="expStatusFilterBtn" class="apex-filter-btn" onclick="toggleExpStatusFilter(); event.stopPropagation();" title="Filter by Status"><i class="bx bx-transfer me-1"></i>Status</a>
                            <a href="javascript:void(0);" id="expModeFilterBtn" class="apex-filter-btn" onclick="toggleExpModeFilter(); event.stopPropagation();" title="Filter by Mode"><i class="bx bx-credit-card me-1"></i>Mode</a>
                            <?php if (count($OrgUsers ?? []) > 1): ?>
                            <a href="javascript:void(0);" id="expUserFilterBtn" class="apex-filter-btn" onclick="toggleExpUserFilter(); event.stopPropagation();" title="Filter by User"><i class="bx bx-user me-1"></i>Updated By</a>
                            <?php endif; ?>
                            <div class="dropdown">
                                <button class="apex-filter-btn dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bx bx-calendar"></i><span id="dateFilterLabel" class="ms-1">This Month</span>
                                </button>
                                <ul class="dropdown-menu shadow" style="width:210px;max-height:300px;overflow-y:auto;font-size:.82rem;">
                                    <li><a class="dropdown-item date-option" data-range="today">Today</a></li>
                                    <li><a class="dropdown-item date-option" data-range="yesterday">Yesterday</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item date-option" data-range="this_week">This Week</a></li>
                                    <li><a class="dropdown-item date-option" data-range="last_week">Last Week</a></li>
                                    <li><a class="dropdown-item date-option" data-range="last_7_days">Last 7 Days</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item date-option active" data-range="this_month">This Month</a></li>
                                    <li><a class="dropdown-item date-option" data-range="previous_month">Previous Month</a></li>
                                    <li><a class="dropdown-item date-option" data-range="last_30_days">Last 30 Days</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item date-option" data-range="this_year">This Year</a></li>
                                    <li><a class="dropdown-item date-option" data-range="last_year">Last Year</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item date-option fw-bold" data-range="fy_25_26"><i class="bx bxs-star text-warning me-1"></i>FY 25-26</a></li>
                                </ul>
                            </div>
                            <div class="apex-filter-spacer"></div>
                            <a href="javascript:void(0);" class="apex-icon-btn pageRefresh" title="Refresh"><i class="bx bx-refresh"></i></a>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="expManageCatBtn">
                                <i class="bx bx-category me-1"></i>Categories
                            </button>
                            <button type="button" class="btn btn-primary addExpenseBtn">
                                <i class="bx bx-plus me-1"></i>Add Expense
                            </button>
                        </div>

                        <!-- Tabs Row -->
                        <div class="apex-tabs-row">
                            <ul class="nav trans-status-tabs gap-1" id="expStatusTabs" role="tablist">
                                <li class="nav-item"><a class="nav-link active exp-status-tab" data-status="All"       href="javascript:void(0);">All       <span class="trans-tab-count ms-1"><?php echo $ModAllCount; ?></span></a></li>
                                <li class="nav-item"><a class="nav-link exp-status-tab"        data-status="Pending"   href="javascript:void(0);">Pending   <span class="exp-tab-count trans-tab-count ms-1 d-none"></span></a></li>
                                <li class="nav-item"><a class="nav-link exp-status-tab"        data-status="Paid"      href="javascript:void(0);">Paid      <span class="exp-tab-count trans-tab-count ms-1 d-none"></span></a></li>
                                <li class="nav-item"><a class="nav-link exp-status-tab"        data-status="Cancelled" href="javascript:void(0);">Cancelled <span class="exp-tab-count trans-tab-count ms-1 d-none"></span></a></li>
                            </ul>
                        </div>

                        <!-- Table -->
                        <div class="table-responsive">
                            <table class="table trans-table table-hover MainviewTable mb-0" id="expTable">
                                <thead class="r2k-thead">
                                    <tr>
                                        <th style="width:36px"><div class="form-check mb-0"><input class="form-check-input table-chkbox expHeaderCheck" type="checkbox"></div></th>
                                        <th class="<?php echo $JwtData->GenSettings->SerialNoDisplay == 1 ? '' : 'd-none'; ?> table-serialno" style="width:44px">S.No</th>
                                        <th class="col-sortable cursor-pointer user-select-none" data-sort="Number">Expense # <i class="bx bx-sort-alt-2 ms-1 sort-icon" data-col="Number"></i></th>
                                        <th class="col-sortable cursor-pointer user-select-none" data-sort="Amount">Amount <i class="bx bx-sort-alt-2 ms-1 sort-icon" data-col="Amount"></i></th>
                                        <th>Category / Notes</th>
                                        <th>Status</th>
                                        <th>Mode</th>
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
                        <div class="row mx-3 my-2 justify-content-between align-items-center expPagination" id="expPagination">
                            <?php echo $ModPagination ?: ''; ?>
                        </div>
                    </div>

                    <div class="card mb-0 cust-sticky-pag" id="expStickyPagination" style="display:none;">
                        <div class="card-body p-0">
                            <div class="row mx-3 my-2 justify-content-between align-items-center expPagination"></div>
                        </div>
                    </div>

                </div>
            </div>
            <?php $this->load->view('common/footer_desc'); ?>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════════════════════
     Expense Add / Edit Modal
══════════════════════════════════════════════════════════════════════════ -->
<div class="modal fade modal-select2-search" id="expenseModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-xl">
        <div class="modal-content">

            <!-- Header -->
            <div class="modal-header bg-white border-bottom d-flex align-items-center justify-content-between px-3 py-2 modal-header-center-sticky trans-theme">
                <div class="d-flex align-items-center gap-3">
                    <div class="modal-doc-icon" style="background:#fef3c7;">
                        <i class="bx bx-receipt modal-doc-icon-inner" style="color:#d97706;"></i>
                    </div>
                    <h5 class="modal-title mb-0" id="expModalTitle">Add Expense</h5>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-sm btn-primary" id="expSaveBtn">
                        <i class="bx bx-check me-1"></i>Save
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger" data-bs-dismiss="modal">
                        <i class="bx bx-x me-1"></i>Close
                    </button>
                </div>
            </div>

            <!-- Hidden state -->
            <input type="hidden" id="emUID" value="0">

            <!-- Body -->
            <div class="modal-body p-3">
                <div class="row g-3">

                    <!-- ── Left Column ──────────────────────────────────── -->
                    <div class="col-lg-8" id="emFormColumn">

                        <!-- Basic Details -->
                        <div class="card mb-3">
                            <div class="card-header py-2">
                                <h6 class="mb-0">Basic Details</h6>
                            </div>
                            <div class="card-body">

                                <!-- Amount -->
                                <div id="emAmountAddWrap" class="mb-3">
                                    <label class="form-label fw-semibold">Expense Amount <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text"><?php echo $cur; ?></span>
                                        <input type="number" class="form-control form-control-lg" id="emAmount" placeholder="0">
                                    </div>
                                </div>
                                <div id="emAmountEditWrap" class="mb-3" style="display:none;">
                                    <label class="form-label fw-semibold" style="font-size:.82rem;color:#6b7280;">Expense Amount</label>
                                    <div id="emAmountDisplay" class="fw-bold" style="font-size:1.6rem;color:#111827;letter-spacing:-.5px;"></div>
                                </div>

                                <!-- Date + Category -->
                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Expense Date</label>
                                        <input type="date" class="form-control" id="emDate">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">
                                            Category
                                            <button type="button" class="btn btn-link p-0 ms-1 text-primary" id="emAddCategoryBtn"
                                                    style="font-size:.75rem;vertical-align:baseline;" title="Add new category">
                                                <i class="bx bx-plus-circle"></i> New
                                            </button>
                                        </label>
                                        <select class="form-select" id="emCategory">
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
                                    <textarea class="form-control" id="emNotes" rows="3" placeholder="Notes or description..."></textarea>
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
                                <div class="dropzone needsclick p-3 dz-clickable w-100" id="expAttachDropzone">
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
                    <div class="col-lg-4" id="emPaymentColumn">

                        <!-- Payment -->
                        <div class="card mb-0">
                            <div class="card-header py-2">
                                <div class="d-flex align-items-center justify-content-between">
                                    <h6 class="mb-0">Payment</h6>
                                    <button type="button" id="emMarkPaidBtn"
                                            class="btn btn-sm btn-outline-secondary" style="min-width:130px;">
                                        <i class="bx bx-credit-card me-1"></i>Mark as Paid
                                    </button>
                                </div>
                            </div>

                            <div id="emPaymentSection" style="display:none;">
                                <div class="card-body">

                                    <!-- Payment Date -->
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold" style="font-size:.85rem;">Payment Date</label>
                                        <input type="date" class="form-control" id="emPmtDate">
                                    </div>

                                    <!-- Payment Type pills -->
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold" style="font-size:.85rem;">Payment Type</label>
                                        <div class="d-flex flex-wrap gap-2" id="emPmtTypePills">
                                            <?php if (!empty($paymentTypes)): ?>
                                                <?php foreach ($paymentTypes as $pt): ?>
                                                    <button type="button" class="btn btn-sm pmt-pill btn-outline-secondary"
                                                            data-uid="<?php echo (int)$pt->PaymentTypeUID; ?>"
                                                            data-name="<?php echo htmlspecialchars($pt->PaymentTypeName); ?>">
                                                        <?php echo htmlspecialchars($pt->PaymentTypeName); ?>
                                                    </button>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <?php foreach (['UPI','Cash','Card','Net Banking','Cheque','EMI'] as $i => $pn): ?>
                                                    <button type="button" class="btn btn-sm pmt-pill btn-outline-secondary"
                                                            data-uid="<?php echo $i + 1; ?>"
                                                            data-name="<?php echo $pn; ?>">
                                                        <?php echo $pn; ?>
                                                    </button>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>
                                        <input type="hidden" id="emPmtTypeUID" value="">
                                    </div>

                                    <!-- Bank Account (hidden for Cash) -->
                                    <div class="mb-3" id="emBankSection">
                                        <label class="form-label fw-semibold" style="font-size:.85rem;">Bank / Account</label>
                                        <select class="form-select" id="emBankUID">
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
                                        <textarea class="form-control" id="emPmtNotes" rows="2"
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

<!-- ══════════════════════════════════════════════════════════════════════════
     Expense Detail View Modal
══════════════════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="expDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-white border-bottom d-flex align-items-center justify-content-between px-3 py-2 modal-header-center-sticky trans-theme">
                <div class="d-flex align-items-center gap-3 flex-wrap flex-grow-1 me-2">
                    <div class="modal-doc-icon" style="background:#fef3c7;">
                        <i class="bx bx-receipt modal-doc-icon-inner" style="color:#d97706;"></i>
                    </div>
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <span class="fw-bold" id="edDocNumber" style="font-size:.95rem;"></span>
                        <span class="text-muted" style="font-size:.82rem;" id="edDocDate"></span>
                        <span class="fw-semibold" style="font-size:.9rem;" id="edDocAmount"></span>
                        <span id="edDocBadge"></span>
                        <span class="text-muted" style="font-size:.8rem;" id="edDocPmt"></span>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0" id="edBody">
                <div class="text-center py-5"><span class="spinner-border text-warning"></span></div>
            </div>
        </div>
    </div>
</div>

<!-- Category Manager Modal -->
<div class="modal fade" id="expCatManagerModal" tabindex="-1" aria-hidden="true"
     style="backdrop-filter:blur(6px);-webkit-backdrop-filter:blur(6px);">
    <div class="modal-dialog modal-dialog-scrollable" style="max-width:480px;">
        <div class="modal-content">
            <div class="modal-header bg-white border-bottom d-flex align-items-center justify-content-between px-3 py-2 trans-theme">
                <div class="d-flex align-items-center gap-2">
                    <div class="modal-doc-icon" style="background:#dcfce7;">
                        <i class="bx bx-category modal-doc-icon-inner" style="color:#16a34a;"></i>
                    </div>
                    <h6 class="modal-title mb-0">Manage Categories</h6>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-sm btn-outline-primary" id="expAddNewCatFromMgr">
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
                        <input type="text" id="catMgrSearch" class="form-control" placeholder="Search categories...">
                    </div>
                </div>
                <ul class="list-group list-group-flush" id="catMgrList" style="min-height:150px;max-height:380px;overflow-y:auto;">
                    <li class="list-group-item text-center py-4">
                        <span class="spinner-border spinner-border-sm text-success"></span>
                    </li>
                </ul>
                <div class="d-flex justify-content-center py-2" id="catMgrPagination"></div>
            </div>
        </div>
    </div>
</div>

<?php $this->load->view('transactions/expenses/partials/_add_category_modal'); ?>

<?php
$rpAccentColor = '#d97706'; $rpAccentBg = '#fef3c7';
$rpPartyIcon   = 'bx-receipt'; $rpDocLabel = 'Expense';
$rpTotalIcon   = 'bx-receipt'; $rpBtnLabel = 'Mark as Paid';
$this->load->view('common/transactions/payment_modal');
?>

<?php $this->load->view('common/transactions/footer'); ?>

<div id="expCatFilterBox" class="card mp-filterbox" style="min-width:230px;z-index:9999;display:none;position:fixed;">
    <div class="catg-filter-header">
        <span class="catg-filter-title"><i class="bx bx-category me-1"></i> Category</span>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-primary" id="expCatFilterCount" style="display:none;"></span>
            <button type="button" class="catg-filter-close-btn" onclick="closeExpCatFilter()" title="Close">&times;</button>
        </div>
    </div>
    <div class="catg-filter-search-wrap">
        <div class="input-group input-group-sm">
            <span class="input-group-text"><i class="bx bx-search"></i></span>
            <input type="text" id="expCatFilterSearch" class="form-control" placeholder="Search categories...">
        </div>
    </div>
    <div class="catg-select-all-wrap">
        <input type="checkbox" class="form-check-input" id="expCatSelectAll" onchange="toggleAllExpCats(this)">
        <label class="small fw-semibold mb-0" for="expCatSelectAll" id="expCatSelectAllLabel">Select All</label>
    </div>
    <div id="expCatList" class="catg-list" style="max-height:180px;overflow-y:auto;"></div>
    <div class="catg-filter-footer">
        <button type="button" class="btn btn-primary btn-sm" onclick="applyExpCatFilter()"><i class="bx bx-check me-1"></i>Apply</button>
        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="resetExpCatFilter()"><i class="bx bx-reset me-1"></i>Reset</button>
    </div>
</div>

<!-- ── Status Filter Box ─────────────────────────────────────────────────── -->
<div id="expStatusFilterBox" class="card mp-filterbox" style="min-width:200px;z-index:9999;display:none;position:fixed;">
    <div class="catg-filter-header">
        <span class="catg-filter-title"><i class="bx bx-check-circle me-1"></i> Status</span>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-primary" id="expStatusFilterCount" style="display:none;"></span>
            <button type="button" class="catg-filter-close-btn" onclick="closeExpStatusFilter()" title="Close">&times;</button>
        </div>
    </div>
    <div class="catg-select-all-wrap">
        <input type="checkbox" class="form-check-input" id="expStatusSelectAll" onchange="toggleAllExpStatuses(this)">
        <label class="small fw-semibold mb-0" for="expStatusSelectAll" id="expStatusSelectAllLabel">Select All</label>
    </div>
    <div id="expStatusList" class="catg-list" style="max-height:160px;overflow-y:auto;">
        <label class="catg-list-item"><input class="form-check-input exp-sf-chk" type="checkbox" value="Pending"><span>Pending</span></label>
        <label class="catg-list-item"><input class="form-check-input exp-sf-chk" type="checkbox" value="Partial"><span>Partial</span></label>
        <label class="catg-list-item"><input class="form-check-input exp-sf-chk" type="checkbox" value="Paid"><span>Paid</span></label>
        <label class="catg-list-item"><input class="form-check-input exp-sf-chk" type="checkbox" value="Cancelled"><span>Cancelled</span></label>
    </div>
    <div class="catg-filter-footer">
        <button type="button" class="btn btn-primary btn-sm" onclick="applyExpStatusFilter()"><i class="bx bx-check me-1"></i>Apply</button>
        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="resetExpStatusFilter()"><i class="bx bx-reset me-1"></i>Reset</button>
    </div>
</div>

<!-- ── Mode Filter Box ───────────────────────────────────────────────────── -->
<div id="expModeFilterBox" class="card mp-filterbox" style="min-width:200px;z-index:9999;display:none;position:fixed;">
    <div class="catg-filter-header">
        <span class="catg-filter-title"><i class="bx bx-credit-card me-1"></i> Mode</span>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-primary" id="expModeFilterCount" style="display:none;"></span>
            <button type="button" class="catg-filter-close-btn" onclick="closeExpModeFilter()" title="Close">&times;</button>
        </div>
    </div>
    <div class="catg-select-all-wrap">
        <input type="checkbox" class="form-check-input" id="expModeSelectAll" onchange="toggleAllExpModes(this)">
        <label class="small fw-semibold mb-0" for="expModeSelectAll" id="expModeSelectAllLabel">Select All</label>
    </div>
    <div id="expModeList" class="catg-list" style="max-height:180px;overflow-y:auto;"></div>
    <div class="catg-filter-footer">
        <button type="button" class="btn btn-primary btn-sm" onclick="applyExpModeFilter()"><i class="bx bx-check me-1"></i>Apply</button>
        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="resetExpModeFilter()"><i class="bx bx-reset me-1"></i>Reset</button>
    </div>
</div>

<!-- ── User Filter Box ───────────────────────────────────────────────────── -->
<?php if (count($OrgUsers ?? []) > 1): ?>
<div id="expUserFilterBox" class="card mp-filterbox" style="min-width:200px;z-index:9999;display:none;position:fixed;">
    <div class="catg-filter-header">
        <span class="catg-filter-title"><i class="bx bx-user me-1"></i> User</span>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-primary" id="expUserFilterCount" style="display:none;"></span>
            <button type="button" class="catg-filter-close-btn" onclick="closeExpUserFilter()" title="Close">&times;</button>
        </div>
    </div>
    <div class="catg-select-all-wrap">
        <input type="checkbox" class="form-check-input" id="expUserSelectAll" onchange="toggleAllExpUsers(this)">
        <label class="small fw-semibold mb-0" for="expUserSelectAll" id="expUserSelectAllLabel">Select All</label>
    </div>
    <div id="expUserList" class="catg-list" style="max-height:180px;overflow-y:auto;"></div>
    <div class="catg-filter-footer">
        <button type="button" class="btn btn-primary btn-sm" onclick="applyExpUserFilter()"><i class="bx bx-check me-1"></i>Apply</button>
        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="resetExpUserFilter()"><i class="bx bx-reset me-1"></i>Reset</button>
    </div>
</div>
<?php endif; ?>

<script src="/js/transactions/expenses.js"></script>

<script>
const ModuleId     = 114;
const ModuleTable  = '#expTable';
const ModulePag    = '.expPagination';
const ModuleHeader = '.expHeaderCheck';
const ModuleRow    = '.expCheck';

$(function () {
    'use strict';

    Filter['Status'] = 'All';

    // ── Expense Category Filter ──────────────────────────────────────────────
    var _expCatData = <?php echo json_encode(array_map(function($c) {
        return ['uid' => (int)$c->CategoryUID, 'name' => $c->CategoryName];
    }, $categories)); ?>;

    function _buildExpCatList(cats) {
        if (!cats.length) {
            $('#expCatList').html('<div class="text-muted text-center py-3" style="font-size:.8rem;">No categories</div>');
            return;
        }
        var html = '';
        cats.forEach(function (c) {
            html += '<label class="catg-list-item">' +
                        '<input class="form-check-input exp-cat-chk" type="checkbox" value="' + c.uid + '">' +
                        '<span>' + $('<span>').text(c.name).html() + '</span>' +
                    '</label>';
        });
        $('#expCatList').html(html);
    }

    function _rebuildExpCatFilter() {
        var checked = $('.exp-cat-chk:checked').map(function () { return $(this).val(); }).get();
        _buildExpCatList(_expCatData);
        checked.forEach(function (v) { $('.exp-cat-chk[value="' + v + '"]').prop('checked', true); });
        var total = $('.exp-cat-chk').length, chkd = $('.exp-cat-chk:checked').length;
        $('#expCatSelectAll').prop('checked', total > 0 && total === chkd);
        $('#expCatSelectAllLabel').text(total > 0 && total === chkd ? 'Deselect All' : 'Select All');
    }

    window.toggleExpCatFilter = function () {
        var $box = $('#expCatFilterBox');
        if ($box.is(':visible')) { $box.hide(); return; }
        var rect = document.getElementById('expCatFilterBtn').getBoundingClientRect();
        $box.css({ top: (rect.bottom + 4) + 'px', left: Math.max(4, rect.right - 230) + 'px' }).show();
    };
    window.closeExpCatFilter = function () { $('#expCatFilterBox').hide(); };
    window.toggleAllExpCats  = function (el) {
        var checked = $(el).is(':checked');
        $('#expCatList .exp-cat-chk').prop('checked', checked);
        $('#expCatSelectAllLabel').text(checked ? 'Deselect All' : 'Select All');
    };
    window.applyExpCatFilter = function () {
        var sel = $('.exp-cat-chk:checked').map(function () { return $(this).val(); }).get();
        if (sel.length) Filter['CategoryUIDs'] = sel; else delete Filter['CategoryUIDs'];
        $('#expCatFilterBox').hide();
        $('#expCatFilterBtn').toggleClass('text-primary', !!sel.length);
        PageNo = 1; getExpensesDetails();
    };
    window.resetExpCatFilter = function () {
        $('.exp-cat-chk').prop('checked', false);
        $('#expCatSelectAll').prop('checked', false);
        $('#expCatSelectAllLabel').text('Select All');
        delete Filter['CategoryUIDs'];
        $('#expCatFilterBox').hide();
        $('#expCatFilterBtn').removeClass('text-primary');
        PageNo = 1; getExpensesDetails();
    };

    $(document).on('input', '#expCatFilterSearch', function () {
        var term = $(this).val().toLowerCase();
        $('#expCatList .catg-list-item').each(function () {
            $(this).toggle($(this).text().toLowerCase().includes(term));
        });
    });
    $(document).on('change', '.exp-cat-chk', function () {
        var total = $('.exp-cat-chk').length, chkd = $('.exp-cat-chk:checked').length;
        $('#expCatSelectAll').prop('checked', total > 0 && total === chkd);
        $('#expCatSelectAllLabel').text(total > 0 && total === chkd ? 'Deselect All' : 'Select All');
    });
    $(document).on('click', function (e) {
        if (!$(e.target).closest('#expCatFilterBox, #expCatFilterBtn').length) $('#expCatFilterBox').hide();
    });

    _buildExpCatList(_expCatData);
    // ────────────────────────────────────────────────────────────────────────

    // ── Expense Status Filter ────────────────────────────────────────────────
    window.toggleExpStatusFilter = function () {
        var $box = $('#expStatusFilterBox');
        if ($box.is(':visible')) { $box.hide(); return; }
        var rect = document.getElementById('expStatusFilterBtn').getBoundingClientRect();
        $box.css({ top: (rect.bottom + 4) + 'px', left: Math.max(4, rect.right - 200) + 'px' }).show();
    };
    window.closeExpStatusFilter = function () { $('#expStatusFilterBox').hide(); };
    window.toggleAllExpStatuses = function (el) {
        var checked = $(el).is(':checked');
        $('.exp-sf-chk').prop('checked', checked);
        $('#expStatusSelectAllLabel').text(checked ? 'Deselect All' : 'Select All');
    };
    window.applyExpStatusFilter = function () {
        var sel = $('.exp-sf-chk:checked').map(function () { return $(this).val(); }).get();
        if (sel.length) { Filter['StatusList'] = sel; } else { delete Filter['StatusList']; }
        $('#expStatusFilterBox').hide();
        var active = sel.length > 0;
        $('#expStatusFilterBtn').toggleClass('text-primary', active);
        $('#expStatusFilterCount').text(sel.length).toggle(active);
        PageNo = 1; getExpensesDetails();
    };
    window.resetExpStatusFilter = function () {
        $('.exp-sf-chk').prop('checked', false);
        $('#expStatusSelectAll').prop('checked', false);
        $('#expStatusSelectAllLabel').text('Select All');
        delete Filter['StatusList'];
        $('#expStatusFilterBox').hide();
        $('#expStatusFilterBtn').removeClass('text-primary');
        $('#expStatusFilterCount').hide().text('');
        PageNo = 1; getExpensesDetails();
    };
    $(document).on('change', '.exp-sf-chk', function () {
        var total = $('.exp-sf-chk').length, chkd = $('.exp-sf-chk:checked').length;
        $('#expStatusSelectAll').prop('checked', total > 0 && total === chkd);
        $('#expStatusSelectAllLabel').text(total > 0 && total === chkd ? 'Deselect All' : 'Select All');
    });
    $(document).on('click', function (e) {
        if (!$(e.target).closest('#expStatusFilterBox, #expStatusFilterBtn').length) $('#expStatusFilterBox').hide();
    });
    // ────────────────────────────────────────────────────────────────────────

    // ── Expense Mode Filter ──────────────────────────────────────────────────
    var _expModeData = <?php echo json_encode(array_map(function($t) {
        return ['uid' => (int)$t->PaymentTypeUID, 'name' => (string)$t->PaymentTypeName];
    }, $paymentTypes ?? [])); ?>;

    (function () {
        if (!_expModeData.length) {
            $('#expModeList').html('<div class="text-muted text-center py-3" style="font-size:.8rem;">No data</div>');
            return;
        }
        var html = '';
        _expModeData.forEach(function (m) {
            html += '<label class="catg-list-item">' +
                        '<input class="form-check-input exp-mf-chk" type="checkbox" value="' + m.uid + '">' +
                        '<span>' + $('<span>').text(m.name).html() + '</span>' +
                    '</label>';
        });
        $('#expModeList').html(html);
    })();

    window.toggleExpModeFilter = function () {
        var $box = $('#expModeFilterBox');
        if ($box.is(':visible')) { $box.hide(); return; }
        var rect = document.getElementById('expModeFilterBtn').getBoundingClientRect();
        $box.css({ top: (rect.bottom + 4) + 'px', left: Math.max(4, rect.right - 200) + 'px' }).show();
    };
    window.closeExpModeFilter = function () { $('#expModeFilterBox').hide(); };
    window.toggleAllExpModes  = function (el) {
        var checked = $(el).is(':checked');
        $('.exp-mf-chk').prop('checked', checked);
        $('#expModeSelectAllLabel').text(checked ? 'Deselect All' : 'Select All');
    };
    window.applyExpModeFilter = function () {
        var sel = $('.exp-mf-chk:checked').map(function () { return $(this).val(); }).get();
        if (sel.length) { Filter['PaymentTypeUIDs'] = sel; } else { delete Filter['PaymentTypeUIDs']; }
        $('#expModeFilterBox').hide();
        var active = sel.length > 0;
        $('#expModeFilterBtn').toggleClass('text-primary', active);
        $('#expModeFilterCount').text(sel.length).toggle(active);
        PageNo = 1; getExpensesDetails();
    };
    window.resetExpModeFilter = function () {
        $('.exp-mf-chk').prop('checked', false);
        $('#expModeSelectAll').prop('checked', false);
        $('#expModeSelectAllLabel').text('Select All');
        delete Filter['PaymentTypeUIDs'];
        $('#expModeFilterBox').hide();
        $('#expModeFilterBtn').removeClass('text-primary');
        $('#expModeFilterCount').hide().text('');
        PageNo = 1; getExpensesDetails();
    };
    $(document).on('change', '.exp-mf-chk', function () {
        var total = $('.exp-mf-chk').length, chkd = $('.exp-mf-chk:checked').length;
        $('#expModeSelectAll').prop('checked', total > 0 && total === chkd);
        $('#expModeSelectAllLabel').text(total > 0 && total === chkd ? 'Deselect All' : 'Select All');
    });
    $(document).on('click', function (e) {
        if (!$(e.target).closest('#expModeFilterBox, #expModeFilterBtn').length) $('#expModeFilterBox').hide();
    });
    // ────────────────────────────────────────────────────────────────────────

    // ── Expense User Filter ──────────────────────────────────────────────────
    var _expUserData = <?php echo json_encode(array_map(function($u) {
        return ['uid' => (int)$u->UserUID, 'name' => (string)$u->FullName];
    }, $OrgUsers ?? [])); ?>;

    if (_expUserData.length > 1) {
        (function () {
            var html = '';
            _expUserData.forEach(function (u) {
                html += '<label class="catg-list-item">' +
                            '<input class="form-check-input exp-uf-chk" type="checkbox" value="' + u.uid + '">' +
                            '<span>' + $('<span>').text(u.name).html() + '</span>' +
                        '</label>';
            });
            $('#expUserList').html(html);
        })();
    }

    window.toggleExpUserFilter = function () {
        var $box = $('#expUserFilterBox');
        if (!$box.length || $box.is(':visible')) { if ($box.length) $box.hide(); return; }
        var rect = document.getElementById('expUserFilterBtn').getBoundingClientRect();
        $box.css({ top: (rect.bottom + 4) + 'px', left: Math.max(4, rect.right - 200) + 'px' }).show();
    };
    window.closeExpUserFilter = function () { $('#expUserFilterBox').hide(); };
    window.toggleAllExpUsers  = function (el) {
        var checked = $(el).is(':checked');
        $('.exp-uf-chk').prop('checked', checked);
        $('#expUserSelectAllLabel').text(checked ? 'Deselect All' : 'Select All');
    };
    window.applyExpUserFilter = function () {
        var sel = $('.exp-uf-chk:checked').map(function () { return $(this).val(); }).get();
        if (sel.length) { Filter['UpdatedByUIDs'] = sel; } else { delete Filter['UpdatedByUIDs']; }
        $('#expUserFilterBox').hide();
        var active = sel.length > 0;
        $('#expUserFilterBtn').toggleClass('text-primary', active);
        $('#expUserFilterCount').text(sel.length).toggle(active);
        PageNo = 1; getExpensesDetails();
    };
    window.resetExpUserFilter = function () {
        $('.exp-uf-chk').prop('checked', false);
        $('#expUserSelectAll').prop('checked', false);
        $('#expUserSelectAllLabel').text('Select All');
        delete Filter['UpdatedByUIDs'];
        $('#expUserFilterBox').hide();
        $('#expUserFilterBtn').removeClass('text-primary');
        $('#expUserFilterCount').hide().text('');
        PageNo = 1; getExpensesDetails();
    };
    $(document).on('change', '.exp-uf-chk', function () {
        var total = $('.exp-uf-chk').length, chkd = $('.exp-uf-chk:checked').length;
        $('#expUserSelectAll').prop('checked', total > 0 && total === chkd);
        $('#expUserSelectAllLabel').text(total > 0 && total === chkd ? 'Deselect All' : 'Select All');
    });
    $(document).on('click', function (e) {
        if (!$(e.target).closest('#expUserFilterBox, #expUserFilterBtn').length) $('#expUserFilterBox').hide();
    });
    // ────────────────────────────────────────────────────────────────────────

    var expCurSymbol    = '<?php echo addslashes($cur); ?>';
    var expDecPoints    = <?php echo (int)$dec; ?>;
    var expDropzone     = null;
    var _expIsEdit      = false;
    var _noBankTypes    = ['Cash'];
    var fpExpDate       = null;
    var fpExpPmtDate    = null;
    var _expPickersInit = false;

    // Init shared payment modal
    initRecordPaymentModal(
        <?php echo json_encode(array_map(function($t) {
            return ['PaymentTypeUID' => (int)$t->PaymentTypeUID, 'Name' => (string)$t->PaymentTypeName, 'IsCash' => (int)$t->IsCash];
        }, $paymentTypes)); ?>,
        <?php echo json_encode(array_values(array_map(function($b) {
            return ['BankAccountUID' => (int)$b->BankAccountUID, 'BankName' => (string)$b->BankName, 'AccountName' => (string)$b->AccountName, 'IsDefault' => (int)$b->IsDefault];
        }, $bankAccounts))); ?>,
        expCurSymbol
    );
    window.rpAfterSuccess = function (resp) {
        if (resp.SummaryStats) _updateStatCards(resp.SummaryStats);
        _syncSticky();
    };

    // ── Sticky pagination ────────────────────────────────────
    var $staticPag = $('#expPagination');
    var $stickyPag = $('#expStickyPagination');
    function _syncSticky()   { $stickyPag.find('.expPagination').html($staticPag.html()); }
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
        $('.expPagination').html(resp.Pagination);
        var count = resp.TotalCount || 0;
        var $expBadge = $('.exp-status-tab.active .trans-tab-count');
        if (count > 0) { $expBadge.text(count).removeClass('d-none'); } else { $expBadge.text('').addClass('d-none'); }
        if (resp.SummaryStats) _updateStatCards(resp.SummaryStats);
        initTooltips();
        _syncSticky();
    }

    function _updateStatCards(stats) {
        var pending   = stats.Pending   || { count: 0, amount: 0 };
        var partial   = stats.Partial   || { count: 0, amount: 0 };
        var paid      = stats.Paid      || { count: 0, amount: 0 };
        var cancelled = stats.Cancelled || { count: 0, amount: 0 };
        var pendingCount  = pending.count  + partial.count;
        var pendingAmount = pending.amount + partial.amount;
        var allCount  = pendingCount + paid.count + cancelled.count;
        var allAmount = pendingAmount + paid.amount;

        $('[data-stat-filter="All"]       .apex-stat-count').text(allCount);
        $('[data-stat-filter="All"]       .apex-stat-amount').text(expCurSymbol + ' ' + _fmtNum(allAmount));
        $('[data-stat-filter="Pending"]   .apex-stat-count').text(pendingCount);
        $('[data-stat-filter="Pending"]   .apex-stat-amount').text(expCurSymbol + ' ' + _fmtNum(pendingAmount));
        $('[data-stat-filter="Paid"]      .apex-stat-count').text(paid.count);
        $('[data-stat-filter="Paid"]      .apex-stat-amount').text(expCurSymbol + ' ' + _fmtNum(paid.amount));
        $('[data-stat-filter="Cancelled"] .apex-stat-count').text(cancelled.count);
    }

    function _fmtNum(val) {
        return parseFloat(val || 0).toLocaleString('en-IN', { minimumFractionDigits: expDecPoints, maximumFractionDigits: expDecPoints });
    }

    function _postData(extra) {
        Filter.Status = $('.exp-status-tab.active').data('status') || 'All';
        return $.extend({ RowLimit: RowLimit, PageNo: PageNo, Filter: Filter, [CsrfName]: CsrfToken }, extra);
    }

    function _postStatusUpdate(uid, status) {
        $.ajax({
            url: '/expenses/updateExpenseStatus', method: 'POST',
            data: _postData({ ExpenseUID: uid, Status: status }),
            success: function (resp) {
                if (resp.Error) { showToastNotification(resp.Message, 'error'); return; }
                _renderList(resp);
                showToastNotification(resp.Message, 'success');
            }
        });
    }

    function _clearExpStatusFilter() {
        delete Filter['StatusList'];
        $('.exp-sf-chk').prop('checked', false);
        $('#expStatusSelectAll').prop('checked', false);
        $('#expStatusSelectAllLabel').text('Select All');
        $('#expStatusFilterBtn').removeClass('text-primary');
        $('#expStatusFilterCount').hide().text('');
    }

    // ── Stat card click ──────────────────────────────────────
    $(document).on('click', '.apex-stat-item[data-stat-filter]', function () {
        var status = $(this).data('stat-filter') || 'All';
        $('.apex-stat-item').removeClass('active');
        $(this).addClass('active');
        $('.exp-status-tab').removeClass('active');
        $('.exp-status-tab[data-status="' + status + '"]').addClass('active');
        _clearExpStatusFilter();
        Filter.Status = status; PageNo = 1; getExpensesDetails();
    });

    // ── Status tab click ─────────────────────────────────────
    $(document).on('click', '.exp-status-tab', function (e) {
        e.preventDefault();
        var status = $(this).data('status') || 'All';
        $('.exp-status-tab').removeClass('active');
        $(this).addClass('active');
        $('.apex-stat-item').removeClass('active');
        $('.apex-stat-item[data-stat-filter="' + status + '"]').addClass('active');
        _clearExpStatusFilter();
        Filter.Status = status; PageNo = 1; getExpensesDetails();
    });

    $(document).on('click', '.pageRefresh', function (e) {
        e.preventDefault(); PageNo = 1; getExpensesDetails();
    });

    $('#searchExpenseData').on('input', debounce(function () {
        Filter.Name = $.trim($(this).val()); PageNo = 1; getExpensesDetails();
    }, 1500));

    // Seed filter with This Month for initial AJAX calls
    var _expInitDr = getDateRange('this_month');
    Filter.DateFrom = _expInitDr.from;
    Filter.DateTo   = _expInitDr.to;

    $(document).on('click', '.date-option', function () {
        var range = $(this).data('range') || '';
        if (range === 'custom') return;
        var dates = getDateRange(range);
        Filter.DateFrom = dates.from; Filter.DateTo = dates.to;
        var _lbl = $.trim($(this).clone().children('.df-date-preview').remove().end().text());
        if (_lbl) $('#dateFilterLabel').text(_lbl);
        $('.date-option').removeClass('active'); $(this).addClass('active');
        PageNo = 1; getExpensesDetails();
    });

    $(document).on('click', '.col-sortable', function () {
        var col = $(this).data('sort');
        if (Filter.SortBy === col) Filter.SortDir = Filter.SortDir === 'ASC' ? 'DESC' : 'ASC';
        else { Filter.SortBy = col; Filter.SortDir = 'DESC'; }
        $('.sort-icon').removeClass('bx-sort-up bx-sort-down').addClass('bx-sort-alt-2');
        $('.sort-icon[data-col="' + col + '"]').removeClass('bx-sort-alt-2').addClass(Filter.SortDir === 'ASC' ? 'bx-sort-up' : 'bx-sort-down');
        PageNo = 1; getExpensesDetails();
    });

    $(document).on('click', '.expPagination .page-link', function (e) {
        e.preventDefault();
        var match = ($(this).attr('href') || '').match(/\/(\d+)$/);
        if (match) { PageNo = parseInt(match[1]); getExpensesDetails(); }
    });

    $(document).on('change', '.expHeaderCheck', function () {
        $('.expCheck').prop('checked', $(this).is(':checked'));
    });

    // ── Row actions ──────────────────────────────────────────
    // ── Payment history panel ────────────────────────────────────────────────
    var _expPayPanelUID = null;
    var $expPayPanel    = $('#payDetailPanel');
    var $expPayBody     = $('#payDetailBody');
    var $expPayTitle    = $('#payPanelTitle');

    function _openExpPayPanel($trigger) {
        var transUID = $trigger.data('trans-uid');
        var transNum = $trigger.data('trans-num') || '';
        var fetchUrl = $trigger.data('fetch-url') || '/expenses/getPaymentHistory';

        var rect   = $trigger[0].getBoundingClientRect();
        var panelW = 290;
        var left   = rect.left;
        var top    = rect.bottom + 6;
        if (left + panelW + 16 > window.innerWidth) left = window.innerWidth - panelW - 16;

        $expPayTitle.text(transNum ? 'Payments — ' + transNum : 'Payments');
        $expPayBody.html('<div class="text-center py-3"><span class="spinner-border spinner-border-sm text-primary"></span></div>');
        $expPayPanel.css({ top: top, left: left }).show();
        _expPayPanelUID = transUID;

        $.ajax({
            url: fetchUrl, method: 'POST',
            data: { TransUID: transUID, [CsrfName]: CsrfToken },
            success: function (resp) {
                if (resp && !resp.Error && resp.Payments && resp.Payments.length) {
                    var html = '';
                    resp.Payments.forEach(function (p, i) {
                        if (i > 0) html += '<hr style="margin:8px 0;border-color:#f0f0f0;">';
                        var amt  = parseFloat(p.Amount || 0).toLocaleString('en-IN', { minimumFractionDigits: expDecPoints, maximumFractionDigits: expDecPoints });
                        var date = '';
                        if (p.CreatedOn) {
                            var d = new Date(p.CreatedOn.replace(' ', 'T'));
                            date  = ('0' + d.getDate()).slice(-2) + '-' + ('0' + (d.getMonth() + 1)).slice(-2) + '-' + d.getFullYear();
                        }
                        html += '<div class="d-flex justify-content-between align-items-start gap-2">';
                        html += '  <div style="min-width:0;">';
                        html += '    <div style="font-size:.83rem;font-weight:600;color:#d97706;">' + expCurSymbol + ' ' + amt + '</div>';
                        html += '    <div style="font-size:.75rem;color:#566a7f;">' + (p.PaymentTypeName || '—') + '</div>';
                        if (date) html += '  <div style="font-size:.72rem;color:#aaa;margin-top:1px;">' + date + '</div>';
                        html += '  </div>';
                        html += '</div>';
                    });
                    $expPayBody.html(html);
                } else {
                    $expPayBody.html('<p class="text-muted mb-0" style="font-size:.8rem;">No payments found.</p>');
                }
            },
            error: function () {
                $expPayBody.html('<p class="text-danger mb-0" style="font-size:.8rem;">Failed to load payments.</p>');
            }
        });
    }

    $(document).on('click', '.pay-mode-clickable', function (e) {
        if ($(e.target).closest('.transPayAttachBtn').length) return;
        e.stopPropagation();
        var transUID = $(this).data('trans-uid');
        if (_expPayPanelUID === transUID && $expPayPanel.is(':visible')) { $expPayPanel.hide(); _expPayPanelUID = null; return; }
        _openExpPayPanel($(this));
    });
    $(document).on('click', '#payPanelClose', function (e) { e.stopPropagation(); $expPayPanel.hide(); _expPayPanelUID = null; });
    $(document).on('click', function (e) {
        if ($expPayPanel.is(':visible') && !$(e.target).closest('#payDetailPanel, .pay-mode-clickable').length) {
            $expPayPanel.hide(); _expPayPanelUID = null;
        }
    });
    $(document).on('keydown', function (e) { if (e.key === 'Escape') { $expPayPanel.hide(); _expPayPanelUID = null; } });
    // ────────────────────────────────────────────────────────────────────────

    $(document).on('click', '.expMarkPaid', function () {
        var uid     = $(this).data('uid');
        var num     = $(this).data('num')     || '';
        var date    = $(this).data('date')    || '';
        var total   = parseFloat($(this).data('total'))   || 0;
        var paid    = parseFloat($(this).data('paid'))    || 0;
        var pending = parseFloat($(this).data('pending')) || total;
        window.rpOpenModal({
            transUID  : uid,
            submitUrl : '/expenses/recordPayment',
            docNum    : num,
            docDate   : date,
            total     : total,
            paid      : paid,
            pending   : pending,
        });
    });

    $(document).on('click', '.expCancel', function () {
        var uid = $(this).data('uid'), num = $(this).data('num') || '';
        Swal.fire({
            title: 'Cancel Expense?',
            html: num ? 'Cancel <strong>' + $('<span>').text(num).html() + '</strong>? This cannot be undone.' : 'This cannot be undone.',
            icon: 'warning', showCancelButton: true,
            confirmButtonColor: '#d33', confirmButtonText: 'Yes, Cancel'
        }).then(function (r) { if (r.isConfirmed) _postStatusUpdate(uid, 'Cancelled'); });
    });

    $(document).on('click', '.expDelete', function () {
        var uid = $(this).data('uid'), num = $(this).data('num') || '';
        Swal.fire({
            title: 'Delete Expense?',
            html: num ? 'Delete <strong>' + $('<span>').text(num).html() + '</strong>? This cannot be undone.' : 'This cannot be undone.',
            icon: 'warning', showCancelButton: true,
            confirmButtonText: 'Delete', confirmButtonColor: '#d33'
        }).then(function (r) {
            if (!r.isConfirmed) return;
            $.ajax({
                url: '/expenses/deleteExpense', method: 'POST',
                data: _postData({ ExpenseUID: uid }),
                success: function (resp) {
                    if (resp.Error) { showToastNotification(resp.Message, 'error'); return; }
                    _renderList(resp);
                    showToastNotification(resp.Message || 'Expense deleted.', 'success');
                }
            });
        });
    });

    // ── View Detail (expense number click) ───────────────────
    $(document).on('click', '.expViewDetail', function () {
        var $el    = $(this);
        var uid    = $el.data('uid');
        var num    = $el.data('num')    || '';
        var date   = $el.data('date')   || '';
        var amount = $el.data('amount') || '';
        var status = $el.data('status') || '';
        var badge  = $el.data('badge')  || '';
        var icon   = $el.data('icon')   || 'bx-circle';
        var pmt    = $el.data('pmt')    || '';

        $('#edDocNumber').text(num);
        $('#edDocDate').text(date);
        $('#edDocAmount').text(amount);
        $('#edDocBadge').html('<span class="trans-badge ' + badge + '"><i class="bx ' + icon + '" style="font-size:.8rem;"></i> ' + status + '</span>');
        $('#edDocPmt').text(pmt ? '· ' + pmt : '');

        $('#edBody').html('<div class="text-center py-5"><span class="spinner-border text-warning"></span></div>');
        new bootstrap.Modal(document.getElementById('expDetailModal')).show();

        $.ajax({
            url: '/expenses/getExpenseDetail', method: 'POST',
            data: { ExpenseUID: uid, [CsrfName]: CsrfToken },
            success: function (resp) {
                if (resp.Error) {
                    $('#edBody').html('<div class="alert alert-danger m-3">' + resp.Message + '</div>');
                    return;
                }
                var d = resp.Data;

                function fmtDate(val) {
                    if (!val) return 'N/A';
                    var dt = new Date(val);
                    return isNaN(dt.getTime()) ? val : dt.toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' });
                }
                function esc(v) { return $('<span>').text(v || '').html(); }

                var pmtHtml = '';
                if (d.PaymentTypeName) {
                    pmtHtml += '<div class="mb-2"><span class="badge bg-label-primary" style="font-size:.75rem;padding:3px 10px;"><i class="bx bx-credit-card me-1"></i>' + esc(d.PaymentTypeName) + '</span></div>';
                }
                var pmtRows = [];
                if (d.PaymentDate)      pmtRows.push('<i class="bx bx-calendar me-1" style="color:#94a3b8;"></i>' + esc(fmtDate(d.PaymentDate)));
                if (d.BankName)         pmtRows.push('<i class="bx bx-building-house me-1" style="color:#94a3b8;font-size:.7rem;"></i>' + esc(d.BankName));
                if (d.BankAccountName)  pmtRows.push('<span style="padding-left:14px;">' + esc(d.BankAccountName) + '</span>');
                if (d.AccountNumber)    pmtRows.push('<span style="padding-left:14px;font-family:monospace;letter-spacing:.05em;">' + esc(d.AccountNumber) + '</span>');
                if (d.PaymentReference) pmtRows.push('<i class="bx bx-hash me-1" style="color:#94a3b8;"></i>Ref: <span style="font-weight:600;">' + esc(d.PaymentReference) + '</span>');
                if (pmtRows.length) pmtHtml += '<div style="font-size:.78rem;color:#475569;line-height:2.1;">' + pmtRows.join('<br>') + '</div>';

                var taxHtml = '';
                if (parseInt(d.TaxApplicable))  taxHtml += ' <span class="badge bg-label-info" style="font-size:.65rem;">Tax ' + esc(d.TaxPercentage) + '%</span>';
                if (parseInt(d.TDSApplicable))  taxHtml += ' <span class="badge bg-label-warning" style="font-size:.65rem;">TDS ' + esc(d.TDSPercentage) + '%</span>';

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
                html += field('Expense Date', esc(fmtDate(d.ExpenseDate)));
                html += field('Amount', expCurSymbol + ' ' + parseFloat(d.Amount || 0).toFixed(expDecPoints) + taxHtml, 'font-size:1.05rem;');
                html += field('Category', d.CategoryName
                    ? '<span class="badge text-bg-light border" style="font-size:.78rem;font-weight:600;padding:5px 10px;">' + esc(d.CategoryName) + '</span>'
                    : '<span class="text-muted" style="font-size:.85rem;font-weight:400;">Not set</span>');
                html += field('Created On', esc(fmtDate(d.CreatedOn)));
                if (pmtHtml) {
                    html += '<div class="col-12"><div style="' + boxS + 'border-left:3px solid #6366f1;">' +
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
                $('#edBody').html(html);
            },
            error: function () {
                $('#edBody').html('<div class="alert alert-danger m-3">Failed to load expense details.</div>');
            }
        });
    });

    // ── Mark as Cancelled (from Paid dropdown) ───────────────
    $(document).on('click', '.expMarkCancelled', function () {
        var uid = $(this).data('uid'), num = $(this).data('num') || '';
        Swal.fire({
            title: 'Mark as Cancelled?',
            html: num ? 'Cancel <strong>' + $('<span>').text(num).html() + '</strong>? This cannot be undone.' : 'This cannot be undone.',
            icon: 'warning', showCancelButton: true,
            confirmButtonColor: '#d33', confirmButtonText: 'Yes, Cancel'
        }).then(function (r) { if (r.isConfirmed) _postStatusUpdate(uid, 'Cancelled'); });
    });

    // ── Clone expense ────────────────────────────────────────
    $(document).on('click', '.expClone', function () {
        var uid = $(this).data('uid');
        $.ajax({
            url: '/expenses/getExpenseDetail', method: 'POST',
            data: { ExpenseUID: uid, [CsrfName]: CsrfToken },
            success: function (resp) {
                if (resp.Error) { showToastNotification(resp.Message, 'error'); return; }
                var d = resp.Data;
                _resetExpModal();
                initExpDropzone();
                $('#expModalTitle').text('Clone Expense');
                $('#emAmount').val(d.Amount || '');
                if (fpExpDate) fpExpDate.setDate(new Date().toISOString().slice(0, 10), false);
                else $('#emDate').val(new Date().toISOString().slice(0, 10));
                $('#emCategory').val(d.CategoryUID || '');
                if ($.fn.select2 && $('#emCategory').data('select2')) $('#emCategory').trigger('change');
                $('#emNotes').val(d.Notes || '');
                new bootstrap.Modal(document.getElementById('expenseModal')).show();
                setTimeout(function () { $('#emAmount').focus(); }, 400);
            }
        });
    });

    // ══════════════════════════════════════════════════════════
    // Modal — Dropzone init
    // ══════════════════════════════════════════════════════════
    function initExpDropzone() {
        var el = document.querySelector('#expAttachDropzone');
        if (!el) return;
        if (el.dropzone)  { try { el.dropzone.destroy();  } catch (e) {} }
        if (expDropzone)  { try { expDropzone.destroy();  } catch (e) {} expDropzone = null; }
        Dropzone.instances = Dropzone.instances.filter(function (d) { return d.element !== el; });
        el.classList.remove('dz-started');

        expDropzone = new Dropzone(el, {
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

    function _resetExpModal() {
        _expIsEdit = false;
        $('#emUID').val('0');
        $('#expModalTitle').text('Add Expense');
        // Add mode: show editable amount, show payment column
        $('#emAmountAddWrap').show();
        $('#emAmountEditWrap').hide();
        $('#emAmountDisplay').text('');
        $('#emFormColumn').removeClass('col-12').addClass('col-lg-8');
        $('#emPaymentColumn').show();
        $('#emAmount').val('');
        if (fpExpDate) fpExpDate.setDate(_todayStr(), false); else $('#emDate').val(_todayStr());
        $('#emCategory').val('');
        if ($.fn.select2 && $('#emCategory').data('select2')) $('#emCategory').trigger('change');
        $('#emNotes').val('');
        // Payment
        $('#emMarkPaidBtn').removeClass('btn-success').addClass('btn-outline-secondary')
            .html('<i class="bx bx-credit-card me-1"></i>Mark as Paid');
        $('#emPaymentSection').hide();
        if (fpExpPmtDate) fpExpPmtDate.setDate(_todayStr(), false); else $('#emPmtDate').val(_todayStr());
        $('#emPmtTypeUID').val('');
        $('.pmt-pill').removeClass('btn-primary').addClass('btn-outline-secondary');
        $('#emBankUID').val('');
        $('#emBankSection').show();
        $('#emPmtNotes').val('');
        // Dropzone
        if (expDropzone) expDropzone.removeAllFiles(true);
    }

    function _populateExpModal(d) {
        _expIsEdit = true;
        $('#emUID').val(d.ExpenseUID);
        $('#expModalTitle').text('Edit Expense' + (d.ExpenseNumber ? ' — ' + d.ExpenseNumber : ''));

        // Edit mode: show readonly amount display, hide payment column
        $('#emAmountAddWrap').hide();
        $('#emAmountEditWrap').show();
        var fmt = parseFloat(d.Amount || 0).toLocaleString('en-IN', {
            minimumFractionDigits: expDecPoints, maximumFractionDigits: expDecPoints
        });
        $('#emAmountDisplay').text(expCurSymbol + ' ' + fmt);
        $('#emAmount').val(d.Amount || ''); // kept for FormData submission
        $('#emFormColumn').removeClass('col-lg-8').addClass('col-12');
        $('#emPaymentColumn').hide();

        if (fpExpDate) fpExpDate.setDate(d.ExpenseDate || _todayStr(), false); else $('#emDate').val(d.ExpenseDate || _todayStr());
        $('#emCategory').val(d.CategoryUID || '');
        if ($.fn.select2 && $('#emCategory').data('select2')) $('#emCategory').trigger('change');
        $('#emNotes').val(d.Notes || '');
    }

    function _updateBankVisibility(pmtName) {
        if (_noBankTypes.indexOf(pmtName) !== -1) {
            $('#emBankSection').hide();
            $('#emBankUID').val('');
        } else {
            $('#emBankSection').show();
        }
    }

    function _ensureExpDatePickers() {
        if (_expPickersInit) return;
        var modalBody = document.querySelector('#expenseModal');
        fpExpDate    = flatpickr('#emDate',    { dateFormat: 'Y-m-d', altInput: true, altFormat: 'd M Y', appendTo: modalBody, static: true });
        fpExpPmtDate = flatpickr('#emPmtDate', { dateFormat: 'Y-m-d', altInput: true, altFormat: 'd M Y', appendTo: modalBody, static: true });
        _expPickersInit = true;
    }

    document.getElementById('expenseModal').addEventListener('shown.bs.modal', function () {
        _ensureExpDatePickers();
    });

    // ══════════════════════════════════════════════════════════
    // Modal — Event handlers
    // ══════════════════════════════════════════════════════════

    // Open for Add
    $('.addExpenseBtn').on('click', function () {
        _resetExpModal();
        initExpDropzone();
        new bootstrap.Modal(document.getElementById('expenseModal')).show();
        setTimeout(function () { $('#emAmount').focus(); }, 400);
    });

    // Open for Edit
    $(document).on('click', '.expEdit', function () {
        var uid = $(this).data('uid');
        $.ajax({
            url: '/expenses/getExpenseDetail', method: 'POST',
            data: { ExpenseUID: uid, [CsrfName]: CsrfToken },
            success: function (resp) {
                if (resp.Error) { showToastNotification(resp.Message, 'error'); return; }
                _resetExpModal();
                initExpDropzone();
                _populateExpModal(resp.Data);
                new bootstrap.Modal(document.getElementById('expenseModal')).show();
            }
        });
    });

    // Mark as Paid toggle
    $('#emMarkPaidBtn').on('click', function () {
        var isPaid = $(this).hasClass('btn-success');
        isPaid = !isPaid;
        if (isPaid) {
            $(this).removeClass('btn-outline-secondary').addClass('btn-success')
                   .html('<i class="bx bx-check me-1"></i>Paid');
            $('#emPaymentSection').slideDown(200);
        } else {
            $(this).removeClass('btn-success').addClass('btn-outline-secondary')
                   .html('<i class="bx bx-credit-card me-1"></i>Mark as Paid');
            $('#emPaymentSection').slideUp(200);
        }
    });

    // Payment type pills
    $(document).on('click', '.pmt-pill', function () {
        $('.pmt-pill').removeClass('btn-primary').addClass('btn-outline-secondary');
        $(this).removeClass('btn-outline-secondary').addClass('btn-primary');
        $('#emPmtTypeUID').val($(this).data('uid'));
        _updateBankVisibility($(this).data('name') || '');
    });

    // Add Category (nested)
    $('#emAddCategoryBtn').on('click', function () {
        $('#newCategoryName').val('').removeClass('is-invalid');
        new bootstrap.Modal(document.getElementById('addCategoryModal')).show();
        setTimeout(function () { $('#newCategoryName').focus(); }, 350);
    });

    $('#saveCategoryBtn').on('click', function () {
        var name = $.trim($('#newCategoryName').val());
        if (!name) { $('#newCategoryName').addClass('is-invalid'); return; }
        $('#newCategoryName').removeClass('is-invalid');
        var uid    = parseInt($('#catModalUID').val()) || 0;
        var isEdit = uid > 0;
        var $btn   = $(this).prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>' + (isEdit ? 'Saving…' : 'Adding…'));

        $.ajax({
            url: isEdit ? '/expenses/updateCategory' : '/expenses/addCategory',
            method: 'POST',
            data: { CategoryUID: uid, CategoryName: name, [CsrfName]: CsrfToken },
            success: function (resp) {
                $btn.prop('disabled', false).html('<i class="bx bx-check me-1"></i><span id="catSaveBtnLabel">' + (isEdit ? 'Save' : 'Add') + '</span>');
                if (resp.Error) { showToastNotification(resp.Message, 'error'); return; }
                var $sel = $('#emCategory');
                if (isEdit) {
                    $sel.find('option[value="' + uid + '"]').text(resp.CategoryName);
                    if ($.fn.select2 && $sel.data('select2')) $sel.trigger('change.select2');
                    _expCatData = _expCatData.map(function (c) { return c.uid === uid ? { uid: uid, name: resp.CategoryName } : c; });
                } else {
                    $sel.append(new Option(resp.CategoryName, resp.CategoryUID, true, true));
                    if ($.fn.select2 && $sel.data('select2')) $sel.trigger('change');
                    _expCatData.push({ uid: parseInt(resp.CategoryUID), name: resp.CategoryName });
                }
                _expCatData.sort(function (a, b) { return a.name.localeCompare(b.name); });
                _rebuildExpCatFilter();
                var $mgr = document.getElementById('expCatManagerModal');
                if ($mgr && bootstrap.Modal.getInstance($mgr)) _loadCatMgr(1);
                bootstrap.Modal.getInstance(document.getElementById('addCategoryModal')).hide();
                showToastNotification(resp.Message || (isEdit ? 'Category updated.' : 'Category added.'), 'success');
            }
        });
    });

    // ── Category Manager ─────────────────────────────────────
    function _loadCatMgr(pageNo) {
        ajaxLoading(0);
        $.ajax({
            url: '/expenses/getCategoryList', method: 'POST',
            data: { PageNo: pageNo || 1, Search: $.trim($('#catMgrSearch').val()), [CsrfName]: CsrfToken },
            success: function (resp) {
                ajaxLoading(1);
                if (resp.Error) {
                    $('#catMgrList').html('<li class="list-group-item text-danger py-3 text-center">' + resp.Message + '</li>');
                    return;
                }
                $('#catMgrList').html(resp.RecordHtmlData);
                $('#catMgrPagination').html(resp.Pagination || '');
            }
        });
    }

    $('#expManageCatBtn').on('click', function () {
        $('#catMgrSearch').val('');
        new bootstrap.Modal(document.getElementById('expCatManagerModal')).show();
        _loadCatMgr(1);
    });

    $('#expAddNewCatFromMgr').on('click', function () {
        $('#catModalTitle').text('Add Category');
        $('#catSaveBtnLabel').text('Add');
        $('#catModalUID').val('0');
        $('#newCategoryName').val('').removeClass('is-invalid');
        new bootstrap.Modal(document.getElementById('addCategoryModal')).show();
        setTimeout(function () { $('#newCategoryName').focus(); }, 350);
    });

    $('#catMgrSearch').on('input', debounce(function () { _loadCatMgr(1); }, 1500));

    $(document).on('click', '#catMgrPagination .page-link', function (e) {
        e.preventDefault();
        var match = ($(this).attr('href') || '').match(/\/(\d+)$/);
        if (match) _loadCatMgr(parseInt(match[1]));
    });

    $(document).on('click', '.catEditBtn', function () {
        $('#catModalTitle').text('Edit Category');
        $('#catSaveBtnLabel').text('Save');
        $('#catModalUID').val($(this).data('uid'));
        $('#newCategoryName').val($(this).data('name')).removeClass('is-invalid');
        new bootstrap.Modal(document.getElementById('addCategoryModal')).show();
        setTimeout(function () { $('#newCategoryName').focus(); }, 350);
    });

    $(document).on('click', '.catDeleteBtn', function () {
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
                url: '/expenses/deleteCategory', method: 'POST',
                data: { CategoryUID: uid, [CsrfName]: CsrfToken },
                success: function (resp) {
                    if (resp.Error) { showToastNotification(resp.Message, 'error'); return; }
                    _expCatData = _expCatData.filter(function (c) { return c.uid !== uid; });
                    _rebuildExpCatFilter();
                    var $opt = $('#emCategory option[value="' + uid + '"]');
                    if ($opt.length) { $opt.remove(); if ($.fn.select2 && $('#emCategory').data('select2')) $('#emCategory').trigger('change'); }
                    _loadCatMgr(1);
                    showToastNotification(resp.Message || 'Category deleted.', 'success');
                }
            });
        });
    });

    // Save expense
    $('#expSaveBtn').on('click', function () {
        var amount = parseFloat($('#emAmount').val()) || 0;
        if (amount <= 0) {
            showToastNotification('Expense amount must be greater than 0.', 'error');
            if (!_expIsEdit) $('#emAmount').focus();
            return;
        }
        var isPaid = _expIsEdit ? 0 : ($('#emMarkPaidBtn').hasClass('btn-success') ? 1 : 0);
        if (!_expIsEdit && isPaid && !$('#emPmtTypeUID').val()) {
            showToastNotification('Please select a payment type.', 'error'); return;
        }

        var $btn = $(this).prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Saving…');
        var url  = _expIsEdit ? '/expenses/updateExpense' : '/expenses/addExpense';

        var fd = new FormData();
        fd.append(CsrfName,        CsrfToken);
        fd.append('ExpenseUID',    $('#emUID').val());
        fd.append('Amount',        amount);
        fd.append('ExpenseDate',   $('#emDate').val());
        fd.append('CategoryUID',   $('#emCategory').val());
        fd.append('Notes',         $('#emNotes').val());
        fd.append('IsPaid',        isPaid ? 1 : 0);
        fd.append('PaymentDate',   $('#emPmtDate').val());
        fd.append('PaymentTypeUID', $('#emPmtTypeUID').val());
        fd.append('BankAccountUID', $('#emBankUID').val());
        fd.append('PaymentNotes',  $('#emPmtNotes').val());
        fd.append('Filter',        JSON.stringify(Filter));
        fd.append('RowLimit',      RowLimit);

        if (expDropzone) {
            expDropzone.files.forEach(function (f) { fd.append('Attachments[]', f); });
        }

        $.ajax({
            url: url, method: 'POST', data: fd, processData: false, contentType: false,
            success: function (resp) {
                $btn.prop('disabled', false).html('<i class="bx bx-check me-1"></i>Save');
                if (resp.Error) { showToastNotification(resp.Message, 'error'); return; }
                bootstrap.Modal.getInstance(document.getElementById('expenseModal')).hide();
                _renderList(resp);
                showToastNotification(resp.Message, 'success');
            },
            error: function () {
                $btn.prop('disabled', false).html('<i class="bx bx-check me-1"></i>Save');
                showToastNotification('An error occurred. Please try again.', 'error');
            }
        });
    });

    // Init Select2 on category (if library loaded)
    if ($.fn.select2) {
        $('#emCategory').select2({
            dropdownParent: $('#expenseModal'),
            placeholder: 'Select Category',
            allowClear: true,
            width: '100%',
        });
    }

});
</script>

<?php $this->load->view('common/transactions/print_modals'); ?>
<script src="/js/transactions/attachments.js"></script>
