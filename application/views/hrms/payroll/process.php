<?php defined('BASEPATH') or exit('No direct script access allowed');
$cur    = htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹');
$months = ['','January','February','March','April','May','June','July','August','September','October','November','December'];
$existingUID    = (int)($ExistingPayroll->PayrollUID ?? 0);
$existingStatus = $ExistingPayroll->PayrollStatus ?? '';
?>
<?php $this->load->view('common/header'); ?>
<div class="layout-wrapper layout-horizontal layout-content-navbar">
  <div class="layout-container">
    <?php $this->load->view('common/menu_view'); ?>
    <div class="layout-page">
      <div class="content-wrapper apex-content">
        <?php $this->load->view('common/apex/page_header', [
          'pageTitle'       => 'Process Payroll',
          'pageDescription' => 'Calculate &amp; finalize monthly salaries',
        ]); ?>
        <div class="container-xxl flex-grow-1 container-p-y">

          <div class="d-flex justify-content-end mb-3">
            <a href="/payroll" class="btn btn-sm btn-outline-secondary"><i class="bx bx-arrow-back me-1"></i><?php echo t('btn_back', 'Back to Payroll'); ?></a>
          </div>

          <?php if ($existingStatus === 'Paid'): ?>
          <div class="alert alert-warning"><i class="bx bx-info-circle me-1"></i>This payroll is already <strong>Paid</strong>. You cannot reprocess it.</div>
          <?php endif; ?>

          <!-- Month/Year Selector -->
          <div class="card mb-3">
            <div class="card-body">
              <div class="row g-3 align-items-end">
                <div class="col-md-3">
                  <label class="form-label fw-semibold"><?php echo t('lbl_payroll_month', 'Payroll Month'); ?> <span class="text-danger">*</span></label>
                  <select class="form-select" id="prlMonth">
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                    <option value="<?php echo $m; ?>" <?php echo $m == ($ExistingPayroll->PayrollMonth ?? date('n')) ? 'selected' : ''; ?>><?php echo $months[$m]; ?></option>
                    <?php endfor; ?>
                  </select>
                </div>
                <div class="col-md-2">
                  <label class="form-label fw-semibold"><?php echo t('lbl_year', 'Year'); ?> <span class="text-danger">*</span></label>
                  <select class="form-select" id="prlYear">
                    <?php for ($y = date('Y') + 1; $y >= date('Y') - 3; $y--): ?>
                    <option value="<?php echo $y; ?>" <?php echo $y == ($ExistingPayroll->PayrollYear ?? date('Y')) ? 'selected' : ''; ?>><?php echo $y; ?></option>
                    <?php endfor; ?>
                  </select>
                </div>
                <div class="col-md-2">
                  <label class="form-label fw-semibold"><?php echo t('col_working_days', 'Working Days'); ?></label>
                  <input type="number" class="form-control" id="prlWorkingDays" value="<?php echo $WorkingDays ?? 26; ?>" min="1" max="31">
                </div>
                <div class="col-auto">
                  <button class="btn btn-outline-primary" id="btnLoadPayroll" <?php echo $existingStatus === 'Paid' ? 'disabled' : ''; ?>>
                    <i class="bx bx-refresh me-1"></i><?php echo t('btn_load_recalculate', 'Load / Recalculate'); ?>
                  </button>
                </div>
              </div>
              <div class="mt-2"><label class="form-label"><?php echo t('lbl_notes', 'Notes'); ?></label><input type="text" class="form-control" id="prlNotes" value="<?php echo htmlspecialchars($ExistingPayroll->Notes ?? ''); ?>" placeholder="Optional notes for this payroll run"></div>
            </div>
          </div>

          <!-- Summary banner -->
          <div class="row g-3 mb-3" id="prlSummaryRow" style="display:none!important;">
            <div class="col-6 col-md"><div class="trans-stat-card stat-all"><div class="trans-stat-label"><?php echo t('lbl_employees', 'Employees'); ?></div><div class="trans-stat-count" id="sumEmployees">0</div><div class="trans-stat-amount">&nbsp;</div></div></div>
            <div class="col-6 col-md"><div class="trans-stat-card stat-active"><div class="trans-stat-label"><?php echo t('lbl_gross', 'Gross Amount'); ?></div><div class="trans-stat-count" id="sumGross">—</div><div class="trans-stat-amount">&nbsp;</div></div></div>
            <div class="col-6 col-md"><div class="trans-stat-card stat-draft"><div class="trans-stat-label"><?php echo t('col_deductions', 'Deductions'); ?></div><div class="trans-stat-count" id="sumDeductions">—</div><div class="trans-stat-amount">&nbsp;</div></div></div>
            <div class="col-6 col-md"><div class="trans-stat-card stat-converted"><div class="trans-stat-label"><?php echo t('lbl_net_payable', 'Net Payable'); ?></div><div class="trans-stat-count" id="sumNet">—</div><div class="trans-stat-amount">&nbsp;</div></div></div>
          </div>

          <!-- Employee lines table -->
          <div class="card" id="prlLinesCard" style="display:none;">
            <div class="card-header d-flex justify-content-between align-items-center">
              <h6 class="mb-0"><?php echo t('col_employee_breakdown', 'Employee Salary Breakdown'); ?></h6>
              <div class="d-flex gap-2">
                <button class="btn btn-sm btn-outline-primary" id="btnSaveDraft" <?php echo $existingStatus === 'Paid' ? 'disabled' : ''; ?>><i class="bx bx-save me-1"></i><?php echo t('btn_save_draft', 'Save Draft'); ?></button>
                <button class="btn btn-sm btn-primary" id="btnProcessPayroll" <?php echo $existingStatus === 'Paid' ? 'disabled' : ''; ?>><i class="bx bx-check-circle me-1"></i><?php echo t('btn_process_finalize', 'Process &amp; Finalize'); ?></button>
              </div>
            </div>
            <div class="table-responsive">
              <table class="table trans-table mb-0" style="font-size:.82rem;">
                <thead class="r2k-thead">
                  <tr>
                    <th><?php echo t('col_employee_name', 'Employee'); ?></th><th><?php echo t('col_type', 'Type'); ?></th><th><?php echo t('col_working_days', 'Working Days'); ?></th><th><?php echo t('att_present', 'Present'); ?></th><th><?php echo t('att_absent', 'Absent'); ?></th><th><?php echo t('lbl_gross', 'Gross'); ?></th><th><?php echo t('col_advance_rec', 'Advance Rec.'); ?></th><th><?php echo t('col_other_ded', 'Other Ded.'); ?></th><th class="text-success"><?php echo t('lbl_net_payable', 'Net Payable'); ?></th><th><?php echo t('col_adjust', 'Adjust'); ?></th>
                  </tr>
                </thead>
                <tbody id="prlLinesBody"></tbody>
              </table>
            </div>
          </div>

          <div id="prlEmptyState" class="text-center text-muted py-5" style="display:none;"><i class="bx bx-group" style="font-size:2.5rem;"></i><p class="mt-2"><?php echo t('msg_payroll_empty', 'Click "Load / Recalculate" to generate payroll lines.'); ?></p></div>

        </div>
      </div>
      <?php $this->load->view('common/footer_desc'); ?>
    </div>
  </div>
</div>
<?php $this->load->view('common/footer'); ?>
<script>
var PayrollCurrencySymbol = <?php echo json_encode($cur); ?>;
var ExistingPayrollUID    = <?php echo $existingUID; ?>;
var ExistingPayrollStatus = <?php echo json_encode($existingStatus); ?>;
</script>
<script src="/js/hrms/payroll.js"></script>
