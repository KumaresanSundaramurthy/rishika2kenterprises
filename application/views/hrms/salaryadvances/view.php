<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
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
        <div class="container-xxl flex-grow-1 container-p-y">
          <div class="d-flex justify-content-end mb-3">
            <button class="btn btn-sm btn-primary" id="btnNewAdvance"><i class="bx bx-plus me-1"></i>New Advance</button>
          </div>

          <!-- Stat cards -->
          <?php $st = $AdvanceStats ?? null; $cur = htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹'); ?>
          <div class="row g-3 mb-3">
            <div class="col-6 col-md">
              <div class="trans-stat-card stat-all adv-stat-clickable" data-filter="All" style="cursor:pointer">
                <div class="trans-stat-label">Total Advances</div>
                <div class="trans-stat-count"><?php echo number_format((int)($st->TotalCount ?? 0)); ?></div>
                <div class="trans-stat-amount"><?php echo $cur . ' ' . number_format((float)($st->TotalAmount ?? 0), 2); ?></div>
                <i class="bx bx-money trans-stat-icon"></i>
              </div>
            </div>
            <div class="col-6 col-md">
              <div class="trans-stat-card stat-draft adv-stat-clickable" data-filter="Pending" style="cursor:pointer">
                <div class="trans-stat-label">Pending Balance</div>
                <div class="trans-stat-count"><?php echo number_format((int)($st->PendingCount ?? 0)); ?></div>
                <div class="trans-stat-amount"><?php echo $cur . ' ' . number_format((float)($st->PendingAmount ?? 0), 2); ?></div>
                <i class="bx bx-time trans-stat-icon"></i>
              </div>
            </div>
            <div class="col-6 col-md">
              <div class="trans-stat-card stat-converted adv-stat-clickable" data-filter="Settled" style="cursor:pointer">
                <div class="trans-stat-label">Settled</div>
                <div class="trans-stat-count"><?php echo number_format((int)($st->SettledCount ?? 0)); ?></div>
                <div class="trans-stat-amount"><?php echo $cur . ' ' . number_format((float)($st->SettledAmount ?? 0), 2); ?></div>
                <i class="bx bx-check-circle trans-stat-icon"></i>
              </div>
            </div>
          </div>

          <div class="card">
            <div class="trans-toolbar">
              <div class="trans-toolbar-tabs">
                <ul class="nav trans-status-tabs" role="tablist">
                  <li class="nav-item"><a class="nav-link active adv-tab" data-filter="All"     href="#">All</a></li>
                  <li class="nav-item"><a class="nav-link adv-tab"        data-filter="Pending"  href="#">Pending</a></li>
                  <li class="nav-item"><a class="nav-link adv-tab"        data-filter="Settled"  href="#">Settled</a></li>
                </ul>
              </div>
              <div class="trans-toolbar-actions">
                <a href="#" class="r2k-icon-btn PageRefresh"><i class="bx bx-refresh"></i></a>
                <div class="r2k-search-wrap"><i class="bx bx-search r2k-si"></i><input type="text" id="SearchDetails" placeholder="Employee, remarks…"><i class="bx bx-x r2k-clear d-none" id="clearSearch"></i></div>
              </div>
            </div>
            <div class="table-responsive">
              <table class="table trans-table MainviewTable mb-0">
                <thead class="r2k-thead"><tr><th>#</th><th>Employee</th><th>Advance Date</th><th>Amount</th><th>Recovered</th><th>Balance</th><th>Status</th><th>Remarks</th><th class="th-act">Actions</th></tr></thead>
                <tbody class="r2k-tbody table-border-bottom-0" id="AdvTableBody"><?php echo $ModRowData; ?></tbody>
              </table>
            </div>
            <hr class="my-0">
            <div class="row mx-3 my-2 justify-content-between align-items-center SalaryadvancesPagination" id="SalaryadvancesPagination"><?php echo $ModPagination; ?></div>
          </div>
        </div>
      </div>
      <?php $this->load->view('common/footer_desc'); ?>
    </div>
  </div>
</div>
<!-- Advance Modal -->
<div class="modal fade" id="advanceModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title" id="advModalTitle">New Salary Advance</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <input type="hidden" id="advUID" value="0">
        <div class="mb-3">
          <label class="form-label">Employee <span class="text-danger">*</span></label>
          <select class="form-select" id="advEmployee">
            <option value="">— Select Employee —</option>
            <?php foreach ($EmployeeList as $e): ?>
            <option value="<?php echo $e->EmployeeUID; ?>"><?php echo htmlspecialchars($e->EmployeeName . ' (' . $e->EmployeeCode . ')'); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Advance Date <span class="text-danger">*</span></label>
            <input type="text" class="form-control flatpickr-date" id="advDate" placeholder="Select date">
          </div>
          <div class="col-md-6">
            <label class="form-label">Amount (<?php echo $cur; ?>) <span class="text-danger">*</span></label>
            <input type="number" class="form-control" id="advAmount" min="1" step="0.01" placeholder="0.00">
          </div>
        </div>
        <div class="mt-3"><label class="form-label">Remarks</label><textarea class="form-control" id="advRemarks" rows="2" placeholder="Reason / notes"></textarea></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button><button type="button" class="btn btn-primary" id="btnSaveAdvance"><i class="bx bx-save me-1"></i>Save</button></div>
    </div>
  </div>
</div>
<?php $this->load->view('common/footer'); ?>
<script src="/js/hrms/salaryadvances.js"></script>
