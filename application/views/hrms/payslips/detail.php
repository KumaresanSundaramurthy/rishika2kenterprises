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
          'pageIcon'        => 'bx-file',
          'pageIconBg'      => '#ecfeff',
          'pageIconColor'   => '#0891b2',
          'pageTitle'       => 'Payslip — ' . htmlspecialchars($s->EmployeeName ?? ''),
          'pageDescription' => $period,
        ]); ?>
        <div class="container-xxl flex-grow-1 container-p-y">
          <div class="d-flex justify-content-end mb-3 gap-2">
            <a href="/payslips/print/<?php echo (int)($s->PayrollLineUID ?? 0); ?>" class="btn btn-sm btn-outline-secondary" target="_blank"><i class="bx bx-printer me-1"></i>Print</a>
            <a href="/payslips" class="btn btn-sm btn-outline-secondary"><i class="bx bx-arrow-back me-1"></i>Back</a>
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
                  <div class="text-muted" style="font-size:.8rem;">PAYSLIP</div>
                  <div class="fw-semibold"><?php echo $period; ?></div>
                  <span class="badge bg-label-<?php echo ($s->PayrollStatus ?? '') === 'Paid' ? 'success' : 'primary'; ?>"><?php echo $s->PayrollStatus ?? '—'; ?></span>
                </div>
              </div>
              <hr>
              <!-- Employee info -->
              <div class="row g-2 mb-3" style="font-size:.875rem;">
                <div class="col-md-6"><div class="text-muted">Employee Name</div><div class="fw-semibold"><?php echo htmlspecialchars($s->EmployeeName ?? ''); ?></div></div>
                <div class="col-md-6"><div class="text-muted">Employee Code</div><div class="fw-semibold"><?php echo htmlspecialchars($s->EmployeeCode ?? ''); ?></div></div>
                <div class="col-md-6"><div class="text-muted">Department</div><div><?php echo htmlspecialchars($s->DepartmentName ?? '—'); ?></div></div>
                <div class="col-md-6"><div class="text-muted">Designation</div><div><?php echo htmlspecialchars($s->DesignationName ?? '—'); ?></div></div>
                <div class="col-md-6"><div class="text-muted">Salary Type</div><div><?php echo htmlspecialchars($s->SalaryType ?? '—'); ?></div></div>
                <div class="col-md-6"><div class="text-muted">Working Days</div><div><?php echo number_format((float)($s->WorkingDays ?? 0)); ?></div></div>
              </div>
              <hr>
              <!-- Earnings & Deductions -->
              <div class="row g-3">
                <div class="col-md-6">
                  <h6 class="fw-semibold mb-2">Earnings</h6>
                  <table class="table table-sm mb-0" style="font-size:.875rem;">
                    <tr><td>Basic Salary</td><td class="text-end"><?php echo $cur . ' ' . number_format((float)($s->BasicSalary ?? 0), 2); ?></td></tr>
                    <tr><td>Allowances</td><td class="text-end"><?php echo $cur . ' ' . number_format((float)($s->Allowances ?? 0), 2); ?></td></tr>
                    <tr><td>Incentives</td><td class="text-end"><?php echo $cur . ' ' . number_format((float)($s->Incentives ?? 0), 2); ?></td></tr>
                    <?php if ((float)($s->OtherEarnings ?? 0) > 0): ?><tr><td>Other Earnings</td><td class="text-end"><?php echo $cur . ' ' . number_format((float)$s->OtherEarnings, 2); ?></td></tr><?php endif; ?>
                    <tr class="fw-semibold table-light"><td>Gross Salary</td><td class="text-end text-success"><?php echo $cur . ' ' . number_format((float)($s->GrossSalary ?? 0), 2); ?></td></tr>
                  </table>
                </div>
                <div class="col-md-6">
                  <h6 class="fw-semibold mb-2">Deductions</h6>
                  <table class="table table-sm mb-0" style="font-size:.875rem;">
                    <tr><td>Absent Days Deduction</td><td class="text-end text-danger"><?php echo $cur . ' ' . number_format((float)($s->AbsentDeduction ?? 0), 2); ?></td></tr>
                    <tr><td>Fixed Deductions</td><td class="text-end text-danger"><?php echo $cur . ' ' . number_format((float)($s->FixedDeductions ?? 0), 2); ?></td></tr>
                    <tr><td>Advance Recovery</td><td class="text-end text-warning"><?php echo $cur . ' ' . number_format((float)($s->AdvanceRecovery ?? 0), 2); ?></td></tr>
                    <?php if ((float)($s->OtherDeductions ?? 0) > 0): ?><tr><td>Other Deductions</td><td class="text-end text-danger"><?php echo $cur . ' ' . number_format((float)$s->OtherDeductions, 2); ?></td></tr><?php endif; ?>
                    <tr class="fw-semibold table-light"><td>Total Deductions</td><td class="text-end text-danger"><?php echo $cur . ' ' . number_format((float)($s->TotalDeductions ?? 0), 2); ?></td></tr>
                  </table>
                </div>
              </div>
              <hr>
              <div class="d-flex justify-content-between align-items-center">
                <span class="fw-bold" style="font-size:1.05rem;">NET PAYABLE</span>
                <span class="fw-bold text-success" style="font-size:1.2rem;"><?php echo $cur . ' ' . number_format((float)($s->NetPayable ?? 0), 2); ?></span>
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
