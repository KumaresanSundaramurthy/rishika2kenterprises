<?php defined('BASEPATH') or exit('No direct script access allowed');
/** @var array $EmployeeList */ $EmployeeList = $EmployeeList ?? [];
/** @var object|null $AdvanceStats */ $AdvanceStats = $AdvanceStats ?? null;
?>
<?php $this->load->view('common/header'); ?>
<div class="layout-wrapper layout-horizontal layout-content-navbar">
  <div class="layout-container">
    <?php $this->load->view('common/menu_view'); ?>
    <div class="layout-page">
      <div class="content-wrapper apex-content">
        <?php $this->load->view('common/apex/page_header', [
          'pageTitle'       => 'Salary Advances',
          'pageDescription' => 'Track advance payments &amp; recoveries',
        ]); ?>

        <?php $st = $AdvanceStats ?? null; $cur = htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹'); ?>

        <!-- ── Stats Strip ──────────────────────────────────────────────────── -->
        <div class="apex-stats-strip">

          <a href="javascript:void(0);" class="apex-stat-item adv-stat-clickable active" data-filter="All" style="--stat-color:#7367f0">
            <div class="apex-stat-icon" style="background:#ede9ff"><i class="bx bx-money" style="color:#7367f0"></i></div>
            <div class="apex-stat-body">
              <div class="apex-stat-label">Total Advances</div>
              <div class="apex-stat-bottom">
                <span class="apex-stat-count adv-s-total"><?php echo number_format((int)($st->TotalCount ?? 0)); ?></span>
                <span class="apex-stat-amount adv-s-total-amt"><?php echo $cur . ' ' . number_format((float)($st->TotalAmount ?? 0), 2); ?></span>
              </div>
            </div>
          </a>

          <a href="javascript:void(0);" class="apex-stat-item adv-stat-clickable" data-filter="Requested" style="--stat-color:#3b82f6">
            <div class="apex-stat-icon" style="background:#eff6ff"><i class="bx bx-hourglass" style="color:#3b82f6"></i></div>
            <div class="apex-stat-body">
              <div class="apex-stat-label">Requested</div>
              <div class="apex-stat-bottom">
                <span class="apex-stat-count adv-s-requested"><?php echo number_format((int)($st->RequestedCount ?? 0)); ?></span>
                <span class="apex-stat-amount adv-s-requested-amt"><?php echo $cur . ' ' . number_format((float)($st->RequestedAmount ?? 0), 2); ?></span>
              </div>
            </div>
          </a>

          <a href="javascript:void(0);" class="apex-stat-item adv-stat-clickable" data-filter="Approved" style="--stat-color:#f59e0b">
            <div class="apex-stat-icon" style="background:#fef3c7"><i class="bx bx-time" style="color:#f59e0b"></i></div>
            <div class="apex-stat-body">
              <div class="apex-stat-label">Approved (Pending Recovery)</div>
              <div class="apex-stat-bottom">
                <span class="apex-stat-count adv-s-approved"><?php echo number_format((int)($st->ApprovedCount ?? 0)); ?></span>
                <span class="apex-stat-amount adv-s-approved-amt"><?php echo $cur . ' ' . number_format((float)($st->ApprovedAmount ?? 0), 2); ?></span>
              </div>
            </div>
          </a>

          <a href="javascript:void(0);" class="apex-stat-item adv-stat-clickable" data-filter="Settled" style="--stat-color:#10b981">
            <div class="apex-stat-icon" style="background:#dcfce7"><i class="bx bx-check-circle" style="color:#10b981"></i></div>
            <div class="apex-stat-body">
              <div class="apex-stat-label">Settled</div>
              <div class="apex-stat-bottom">
                <span class="apex-stat-count adv-s-settled"><?php echo number_format((int)($st->SettledCount ?? 0)); ?></span>
                <span class="apex-stat-amount adv-s-settled-amt"><?php echo $cur . ' ' . number_format((float)($st->SettledAmount ?? 0), 2); ?></span>
              </div>
            </div>
          </a>

        </div>

        <!-- ── Main Content ─────────────────────────────────────────────────── -->
        <div class="container-xxl flex-grow-1 py-3">
          <div class="card">

            <!-- Filter Row -->
            <div class="apex-filter-row">
              <div class="r2k-search-wrap">
                <i class="bx bx-search r2k-si"></i>
                <input type="text" id="SearchDetails" placeholder="Employee, remarks…">
                <i class="bx bx-x r2k-clear d-none" id="clearSearch"></i>
              </div>
              <div class="apex-filter-spacer"></div>
              <a href="javascript:void(0);" class="apex-icon-btn PageRefresh" title="Refresh"><i class="bx bx-refresh"></i></a>
              <button class="btn btn-primary btn-sm" id="btnNewAdvance">
                <i class="bx bx-plus me-1"></i>New Advance
              </button>
            </div>

            <!-- Tabs Row -->
            <div class="apex-tabs-row">
              <ul class="nav trans-status-tabs" role="tablist">
                <li class="nav-item"><a class="nav-link active adv-tab" data-filter="All"       href="javascript:void(0);">All</a></li>
                <li class="nav-item"><a class="nav-link adv-tab"        data-filter="Requested" href="javascript:void(0);">Requested</a></li>
                <li class="nav-item"><a class="nav-link adv-tab"        data-filter="Approved"  href="javascript:void(0);">Approved</a></li>
                <li class="nav-item"><a class="nav-link adv-tab"        data-filter="Settled"   href="javascript:void(0);">Settled</a></li>
                <li class="nav-item"><a class="nav-link adv-tab"        data-filter="Rejected"  href="javascript:void(0);">Rejected</a></li>
              </ul>
            </div>

            <!-- Table -->
            <div class="table-responsive">
              <table class="table trans-table MainviewTable mb-0">
                <thead class="r2k-thead">
                  <tr>
                    <th>#</th>
                    <th>Employee</th>
                    <th>Advance Date</th>
                    <th>Amount</th>
                    <th>Recovered</th>
                    <th>Balance</th>
                    <th>Status</th>
                    <th>Remarks</th>
                    <th class="th-act text-center">Actions</th>
                  </tr>
                </thead>
                <tbody class="r2k-tbody table-border-bottom-0" id="AdvTableBody"><?php echo $ModRowData; ?></tbody>
              </table>
            </div>

            <hr class="my-0">
            <div class="row mx-3 my-2 justify-content-between align-items-center SalaryadvancesPagination" id="SalaryadvancesPagination">
              <?php echo $ModPagination; ?>
            </div>

          </div>
        </div>

      </div>
      <?php $this->load->view('common/footer_desc'); ?>
    </div>
  </div>
</div>

<!-- ── Advance Modal ─────────────────────────────────────────────────────── -->
<div class="modal fade" id="advanceModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="vtm-banner" style="--vtm-color:#10b981;--vtm-bg:#ecfdf5;--vtm-icon-bg:rgba(16,185,129,.12);">
        <div class="vtm-banner-inner">
          <div class="vtm-banner-left">
            <div class="vtm-banner-icon"><i class="bx bx-money-withdraw"></i></div>
            <div>
              <div class="vtm-doc-number" id="advModalTitle">New Salary Advance</div>
              <div class="vtm-doc-meta" id="advModalMeta">Fill in the advance details below</div>
            </div>
          </div>
          <div class="vtm-banner-right">
            <button type="button" class="vtm-close-btn" data-bs-dismiss="modal" aria-label="Close"><i class="bx bx-x"></i></button>
          </div>
        </div>
      </div>
      <div class="modal-body p-4">
        <input type="hidden" id="advUID" value="0">
        <div class="mb-3">
          <label class="form-label">Employee <span class="text-danger">*</span></label>
          <select class="form-select" id="advEmployee">
            <option value="">— Select Employee —</option>
            <?php foreach ($EmployeeList as $e): ?>
            <option value="<?php echo $e->EmployeeUID; ?>"><?php $code = trim($e->EmployeeCode ?? ''); echo htmlspecialchars($e->EmployeeName . ($code ? ' (' . $code . ')' : '')); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Advance Date <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="advDate" placeholder="Select date" autocomplete="off">
          </div>
          <div class="col-md-6">
            <label class="form-label">Amount (<?php echo $cur; ?>) <span class="text-danger">*</span></label>
            <input type="number" class="form-control" id="advAmount" min="1" step="0.01" placeholder="0.00">
          </div>
        </div>
        <div class="mt-3">
          <label class="form-label">Remarks</label>
          <textarea class="form-control" id="advRemarks" rows="2" placeholder="Reason / notes"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" id="btnSaveAdvance">
          <span class="spinner-border spinner-border-sm me-1 d-none" id="spinnerAdv"></span>
          <i class="bx bx-save me-1" id="iconAdv"></i>Save
        </button>
      </div>
    </div>
  </div>
</div>

<?php $this->load->view('common/footer'); ?>
<script src="/js/hrms/salaryadvances.js"></script>
