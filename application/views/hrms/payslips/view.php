<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php $this->load->view('common/header'); ?>
<div class="layout-wrapper layout-horizontal layout-content-navbar">
  <div class="layout-container">
    <?php $this->load->view('common/menu_view'); ?>
    <div class="layout-page">
      <div class="content-wrapper">
        <div class="container-xxl flex-grow-1 container-p-y">
          <div class="trans-page-header">
            <div class="d-flex align-items-center gap-3">
              <div class="trans-ph-icon" style="background:linear-gradient(135deg,#06b6d4,#0e7490)">
                <i class="bx bx-file" style="color:#fff;font-size:1.4rem;"></i>
              </div>
              <div><h5 class="trans-ph-title mb-0">Payslips</h5><div class="text-muted" style="font-size:.76rem;">Employee salary slips</div></div>
            </div>
          </div>

          <!-- Filters -->
          <div class="row g-2 mb-3">
            <div class="col-auto">
              <select class="form-select form-select-sm" id="psEmpFilter" style="min-width:200px;">
                <option value="">All Employees</option>
                <?php foreach ($EmployeeList as $e): ?>
                <option value="<?php echo $e->EmployeeUID; ?>"><?php echo htmlspecialchars($e->EmployeeName . ' (' . $e->EmployeeCode . ')'); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-auto">
              <select class="form-select form-select-sm" id="psMonthFilter" style="min-width:120px;">
                <option value="">All Months</option>
                <?php $mn = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
                foreach ($mn as $mi => $mm): ?>
                <option value="<?php echo $mi + 1; ?>"><?php echo $mm; ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-auto">
              <select class="form-select form-select-sm" id="psYearFilter" style="min-width:90px;">
                <?php for ($y = date('Y') + 1; $y >= date('Y') - 3; $y--): ?>
                <option value="<?php echo $y; ?>" <?php echo $y == date('Y') ? 'selected' : ''; ?>><?php echo $y; ?></option>
                <?php endfor; ?>
              </select>
            </div>
            <div class="col-auto"><button class="btn btn-sm btn-outline-primary" id="btnPsFilter"><i class="bx bx-filter-alt me-1"></i>Filter</button></div>
          </div>

          <div class="card">
            <div class="trans-toolbar">
              <div></div>
              <div class="trans-toolbar-actions">
                <a href="#" class="r2k-icon-btn PageRefresh"><i class="bx bx-refresh"></i></a>
                <div class="r2k-search-wrap"><i class="bx bx-search r2k-si"></i><input type="text" id="SearchDetails" placeholder="Employee…"><i class="bx bx-x r2k-clear d-none" id="clearSearch"></i></div>
              </div>
            </div>
            <div class="table-responsive">
              <table class="table trans-table MainviewTable mb-0">
                <thead class="r2k-thead"><tr><th>#</th><th>Employee</th><th>Period</th><th>Gross</th><th>Deductions</th><th>Net Payable</th><th>Status</th><th class="th-act">Actions</th></tr></thead>
                <tbody class="r2k-tbody table-border-bottom-0" id="PayslipTableBody"><?php echo $ModRowData; ?></tbody>
              </table>
            </div>
            <hr class="my-0">
            <div class="row mx-3 my-2 justify-content-between align-items-center PayslipsPagination" id="PayslipsPagination"><?php echo $ModPagination; ?></div>
          </div>
        </div>
      </div>
      <?php $this->load->view('common/footer_desc'); ?>
    </div>
  </div>
</div>
<?php $this->load->view('common/footer'); ?>
<script src="/js/hrms/payslips.js"></script>
