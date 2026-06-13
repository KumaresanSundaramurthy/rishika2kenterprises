<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php $this->load->view('common/header'); ?>

<div class="layout-wrapper layout-horizontal layout-content-navbar">
  <div class="layout-container">
    <?php $this->load->view('common/menu_view'); ?>
    <div class="layout-page">
      <div class="content-wrapper apex-content">
        <?php $this->load->view('common/apex/page_header', [
          'pageIcon'        => 'bx-group',
          'pageIconBg'      => '#ede9fe',
          'pageIconColor'   => '#7c3aed',
          'pageTitle'       => $PageTitle ?? 'Employees',
          'pageDescription' => 'Manage your team members',
        ]); ?>
        <div class="container-xxl flex-grow-1 container-p-y">

          <div class="d-flex justify-content-end mb-3">
            <a href="/employees" class="btn btn-sm btn-primary" id="btnNewEmployee">
              <i class="bx bx-plus me-1"></i>New Employee
            </a>
          </div>

          <!-- Stat Cards -->
          <?php $s = $EmpStats ?? null; ?>
          <div class="row g-3 mb-3">
            <div class="col-6 col-md">
              <div class="trans-stat-card stat-all emp-stat-clickable" data-filter="All" style="cursor:pointer">
                <div class="trans-stat-label">Total Employees</div>
                <div class="trans-stat-count"><?php echo number_format((int)($s->Total ?? 0)); ?></div>
                <div class="trans-stat-amount">&nbsp;</div>
                <i class="bx bx-group trans-stat-icon"></i>
              </div>
            </div>
            <div class="col-6 col-md">
              <div class="trans-stat-card stat-active emp-stat-clickable" data-filter="Active" style="cursor:pointer">
                <div class="trans-stat-label">Active</div>
                <div class="trans-stat-count"><?php echo number_format((int)($s->Active ?? 0)); ?></div>
                <div class="trans-stat-amount">&nbsp;</div>
                <i class="bx bx-check-circle trans-stat-icon"></i>
              </div>
            </div>
            <div class="col-6 col-md">
              <div class="trans-stat-card stat-draft emp-stat-clickable" data-filter="Resigned" style="cursor:pointer">
                <div class="trans-stat-label">Resigned</div>
                <div class="trans-stat-count"><?php echo number_format((int)($s->Resigned ?? 0)); ?></div>
                <div class="trans-stat-amount">&nbsp;</div>
                <i class="bx bx-log-out trans-stat-icon"></i>
              </div>
            </div>
            <div class="col-6 col-md">
              <div class="trans-stat-card stat-converted emp-stat-clickable" data-filter="Terminated" style="cursor:pointer">
                <div class="trans-stat-label">Terminated</div>
                <div class="trans-stat-count"><?php echo number_format((int)($s->Terminated ?? 0)); ?></div>
                <div class="trans-stat-amount">&nbsp;</div>
                <i class="bx bx-x-circle trans-stat-icon"></i>
              </div>
            </div>
          </div>

          <!-- Main Card -->
          <div class="card">
            <div class="trans-toolbar">
              <div class="trans-toolbar-tabs">
                <ul class="nav trans-status-tabs" role="tablist">
                  <li class="nav-item"><a class="nav-link active emp-tab" data-status="All"        href="#">All <span class="trans-tab-count"><?php echo $EmpStats->Total ?? 0; ?></span></a></li>
                  <li class="nav-item"><a class="nav-link emp-tab"        data-status="Active"      href="#">Active</a></li>
                  <li class="nav-item"><a class="nav-link emp-tab"        data-status="Resigned"    href="#">Resigned</a></li>
                  <li class="nav-item"><a class="nav-link emp-tab"        data-status="Terminated"  href="#">Terminated</a></li>
                </ul>
              </div>
              <div class="trans-toolbar-actions">
                <a href="#" class="r2k-icon-btn PageRefresh"><i class="bx bx-refresh"></i></a>
                <div class="r2k-search-wrap">
                  <i class="bx bx-search r2k-si"></i>
                  <input type="text" id="SearchDetails" placeholder="Name, code, mobile…">
                  <i class="bx bx-x r2k-clear d-none" id="clearSearch"></i>
                </div>
              </div>
            </div>

            <div class="table-responsive">
              <table class="table trans-table MainviewTable mb-0" id="EmployeesTable">
                <thead class="r2k-thead">
                  <tr>
                    <th class="th-chk" style="width:36px"><div class="form-check mb-0"><input class="form-check-input table-chkbox empHeaderCheck" type="checkbox"></div></th>
                    <th class="th-sno <?php echo ($JwtData->GenSettings->SerialNoDisplay ?? 0) == 1 ? '' : 'd-none'; ?>" style="width:40px">#</th>
                    <th>Employee</th>
                    <th>Department</th>
                    <th>Designation</th>
                    <th>Mobile</th>
                    <th>Salary Type</th>
                    <th>Basic Salary</th>
                    <th>Status</th>
                    <th>Joining Date</th>
                    <th class="th-act" style="width:80px">Actions</th>
                  </tr>
                </thead>
                <tbody class="r2k-tbody table-border-bottom-0" id="EmployeeTableBody">
                  <?php echo $ModRowData; ?>
                </tbody>
              </table>
            </div>
            <hr class="my-0">
            <div class="row mx-3 my-2 justify-content-between align-items-center EmployeesPagination" id="EmployeesPagination">
              <?php echo $ModPagination; ?>
            </div>
          </div>

        </div>
      </div>
      <?php $this->load->view('common/footer_desc'); ?>
    </div>
  </div>
</div>

<!-- Employee Form Modal -->
<div class="modal fade" id="employeeModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="empModalTitle">New Employee</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="empUID" value="0">
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label">Employee Code <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="empCode" placeholder="EMP0001">
          </div>
          <div class="col-md-8">
            <label class="form-label">Employee Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="empName" placeholder="Full name">
          </div>
          <div class="col-md-6">
            <label class="form-label">Mobile</label>
            <input type="text" class="form-control" id="empMobile" placeholder="Mobile number">
          </div>
          <div class="col-md-6">
            <label class="form-label">Email</label>
            <input type="email" class="form-control" id="empEmail" placeholder="Email address">
          </div>
          <div class="col-md-6">
            <label class="form-label">Department</label>
            <select class="form-select" id="empDept">
              <option value="">— Select —</option>
              <?php foreach ($DepartmentList as $d): ?>
              <option value="<?php echo $d->DepartmentUID; ?>"><?php echo htmlspecialchars($d->DepartmentName); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Designation</label>
            <select class="form-select" id="empDesig">
              <option value="">— Select —</option>
              <?php foreach ($DesignationList as $d): ?>
              <option value="<?php echo $d->DesignationUID; ?>"><?php echo htmlspecialchars($d->DesignationName); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Date of Joining</label>
            <input type="text" class="form-control flatpickr-date" id="empDOJ" placeholder="Select date">
          </div>
          <div class="col-md-6">
            <label class="form-label">Status</label>
            <select class="form-select" id="empStatus">
              <option value="Active">Active</option>
              <option value="Resigned">Resigned</option>
              <option value="Terminated">Terminated</option>
              <option value="OnLeave">On Leave</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">Salary Type <span class="text-danger">*</span></label>
            <select class="form-select" id="empSalaryType">
              <option value="Monthly">Monthly Salary</option>
              <option value="Daily">Daily Wage</option>
              <option value="Hourly">Hourly Wage</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label" id="empBasicLabel">Basic Salary (₹/month)</label>
            <input type="number" class="form-control" id="empBasic" min="0" step="0.01" placeholder="0.00">
          </div>
          <div class="col-md-4">
            <label class="form-label">Allowances</label>
            <input type="number" class="form-control" id="empAllowances" min="0" step="0.01" placeholder="0.00">
          </div>
          <div class="col-md-4">
            <label class="form-label">Incentives</label>
            <input type="number" class="form-control" id="empIncentives" min="0" step="0.01" placeholder="0.00">
          </div>
          <div class="col-md-4">
            <label class="form-label">Fixed Deductions</label>
            <input type="number" class="form-control" id="empDeductions" min="0" step="0.01" placeholder="0.00">
          </div>
          <div class="col-12">
            <label class="form-label">Address</label>
            <textarea class="form-control" id="empAddress" rows="2" placeholder="Address"></textarea>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" id="btnSaveEmployee"><i class="bx bx-save me-1"></i>Save</button>
      </div>
    </div>
  </div>
</div>

<?php $this->load->view('common/footer'); ?>
<script>
var EmpNextCode = <?php echo json_encode($NextEmpCode ?? 'EMP0001'); ?>;
</script>
<script src="/js/hrms/employees.js"></script>
<script src="/js/common/pagecheckbox.js"></script>
