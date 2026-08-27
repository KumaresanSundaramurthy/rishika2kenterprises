<?php defined('BASEPATH') or exit('No direct script access allowed');
$cur  = htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹');
$s    = $Slip ?? new stdClass();
$org  = $OrgInfo ?? new stdClass();
$months = ['','January','February','March','April','May','June','July','August','September','October','November','December'];
$period = ($months[(int)($s->PayrollMonth ?? 0)] ?? '—') . ' ' . ($s->PayrollYear ?? '');
?>
<?php $this->load->view('common/header'); ?>
<div class="layout-wrapper layout-horizontal layout-content-navbar">
  <div class="layout-container">
    <?php $this->load->view('common/menu_view'); ?>
    <div class="layout-page">
      <div class="content-wrapper apex-content">
        <?php $this->load->view('common/apex/page_header', [
          'pageTitle'       => 'Payslip — ' . htmlspecialchars($s->EmployeeName ?? ''),
          'pageDescription' => $period,
        ]); ?>
        <div class="container-xxl flex-grow-1 container-p-y">
          <div class="d-flex justify-content-end mb-3 gap-2">
            <a href="/payslips/print/<?php echo (int)($s->PayrollLineUID ?? 0); ?>" class="btn btn-sm btn-outline-secondary" target="_blank"><i class="bx bx-printer me-1"></i><?php echo t('btn_print', 'Print'); ?></a>
            <a href="/payslips" class="btn btn-sm btn-outline-secondary"><i class="bx bx-arrow-back me-1"></i><?php echo t('btn_back', 'Back'); ?></a>
          </div>

          <div class="card" style="max-width:720px;margin:0 auto;">
            <div class="card-body p-4">
              <!-- Org header -->
              <div class="d-flex justify-content-between align-items-start mb-4">
                <div>
                  <h5 class="fw-bold mb-0"><?php echo htmlspecialchars($org->OrgName ?? 'Organisation'); ?></h5>
                  <div class="text-muted" style="font-size:.83rem;"><?php echo htmlspecialchars($org->Address ?? ''); ?></div>
                </div>
                <div class="text-end">
                  <div class="text-muted" style="font-size:.8rem;"><?php echo strtoupper(t('lbl_salary_slip', 'Salary Slip')); ?></div>
                  <div class="fw-semibold"><?php echo $period; ?></div>
                  <span class="badge bg-label-<?php echo ($s->PayrollStatus ?? '') === 'Paid' ? 'success' : 'primary'; ?>"><?php echo $s->PayrollStatus ?? '—'; ?></span>
                </div>
              </div>
              <hr>
              <!-- Employee info -->
              <div class="row g-2 mb-3" style="font-size:.875rem;">
                <div class="col-md-6"><div class="text-muted"><?php echo t('col_employee_name', 'Employee Name'); ?></div><div class="fw-semibold"><?php echo htmlspecialchars($s->EmployeeName ?? ''); ?></div></div>
                <div class="col-md-6"><div class="text-muted"><?php echo t('col_employee_code', 'Employee Code'); ?></div><div class="fw-semibold"><?php echo htmlspecialchars($s->EmployeeCode ?? ''); ?></div></div>
                <div class="col-md-6"><div class="text-muted"><?php echo t('col_department', 'Department'); ?></div><div><?php echo htmlspecialchars($s->DepartmentName ?? '—'); ?></div></div>
                <div class="col-md-6"><div class="text-muted"><?php echo t('col_designation', 'Designation'); ?></div><div><?php echo htmlspecialchars($s->DesignationName ?? '—'); ?></div></div>
                <div class="col-md-6"><div class="text-muted"><?php echo t('col_salary_type', 'Salary Type'); ?></div><div><?php echo htmlspecialchars($s->SalaryType ?? '—'); ?></div></div>
                <div class="col-md-6"><div class="text-muted"><?php echo t('col_working_days', 'Working Days'); ?></div><div><?php echo number_format((float)($s->WorkingDays ?? 0)); ?></div></div>
              </div>
              <hr>
              <!-- Earnings & Deductions -->
              <div class="row g-3">
                <div class="col-md-6">
                  <h6 class="fw-semibold mb-2"><?php echo t('lbl_earnings', 'Earnings'); ?></h6>
                  <table class="table table-sm mb-0" style="font-size:.875rem;">
                    <tr><td><?php echo t('lbl_basic_salary', 'Basic Salary'); ?></td><td class="text-end"><?php echo $cur . ' ' . smartDecimal((float)($s->BasicSalary ?? 0)); ?></td></tr>
                    <tr><td><?php echo t('lbl_allowances', 'Allowances'); ?></td><td class="text-end"><?php echo $cur . ' ' . smartDecimal((float)($s->Allowances ?? 0)); ?></td></tr>
                    <tr><td><?php echo t('lbl_incentives', 'Incentives'); ?></td><td class="text-end"><?php echo $cur . ' ' . smartDecimal((float)($s->Incentives ?? 0)); ?></td></tr>
                    <?php if ((float)($s->OtherEarnings ?? 0) > 0): ?><tr><td><?php echo t('lbl_other_earnings', 'Other Earnings'); ?></td><td class="text-end"><?php echo $cur . ' ' . smartDecimal((float)$s->OtherEarnings); ?></td></tr><?php endif; ?>
                    <tr class="fw-semibold table-light"><td><?php echo t('lbl_gross_salary', 'Gross Salary'); ?></td><td class="text-end text-success"><?php echo $cur . ' ' . smartDecimal((float)($s->GrossSalary ?? 0)); ?></td></tr>
                  </table>
                </div>
                <div class="col-md-6">
                  <h6 class="fw-semibold mb-2"><?php echo t('lbl_deductions', 'Deductions'); ?></h6>
                  <table class="table table-sm mb-0" style="font-size:.875rem;">
                    <tr><td><?php echo t('lbl_absent_deduction', 'Absent Days Deduction'); ?></td><td class="text-end text-danger"><?php echo $cur . ' ' . smartDecimal((float)($s->AbsentDeduction ?? 0)); ?></td></tr>
                    <tr><td><?php echo t('lbl_fixed_deductions', 'Fixed Deductions'); ?></td><td class="text-end text-danger"><?php echo $cur . ' ' . smartDecimal((float)($s->FixedDeductions ?? 0)); ?></td></tr>
                    <tr><td><?php echo t('lbl_advance_recovery', 'Advance Recovery'); ?></td><td class="text-end text-warning"><?php echo $cur . ' ' . smartDecimal((float)($s->AdvanceRecovery ?? 0)); ?></td></tr>
                    <?php if ((float)($s->OtherDeductions ?? 0) > 0): ?><tr><td><?php echo t('lbl_other_deductions', 'Other Deductions'); ?></td><td class="text-end text-danger"><?php echo $cur . ' ' . smartDecimal((float)$s->OtherDeductions); ?></td></tr><?php endif; ?>
                    <tr class="fw-semibold table-light"><td><?php echo t('lbl_total_deductions', 'Total Deductions'); ?></td><td class="text-end text-danger"><?php echo $cur . ' ' . smartDecimal((float)($s->TotalDeductions ?? 0)); ?></td></tr>
                  </table>
                </div>
              </div>
              <hr>
              <div class="d-flex justify-content-between align-items-center">
                <span class="fw-bold" style="font-size:1.05rem;"><?php echo strtoupper(t('lbl_net_payable', 'Net Payable')); ?></span>
                <span class="fw-bold text-success" style="font-size:1.2rem;"><?php echo $cur . ' ' . smartDecimal((float)($s->NetPayable ?? 0)); ?></span>
              </div>
            </div>
          </div>

        </div>
      </div>
      <?php $this->load->view('common/footer_desc'); ?>
    </div>
  </div>
</div>
<?php $this->load->view('common/footer'); ?>
