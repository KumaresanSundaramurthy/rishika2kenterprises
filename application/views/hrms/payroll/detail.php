<?php defined('BASEPATH') or exit('No direct script access allowed');
$cur    = htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹');
$p      = $Payroll ?? new stdClass();
$lines  = $PayrollLines ?? [];
$months = ['','January','February','March','April','May','June','July','August','September','October','November','December'];
$period = ($months[(int)($p->PayrollMonth ?? 0)] ?? '—') . ' ' . ($p->PayrollYear ?? '');
$statusColors = ['Draft'=>'secondary','Processed'=>'primary','Paid'=>'success'];
$badge = $statusColors[$p->PayrollStatus ?? 'Draft'] ?? 'secondary';
?>
<?php $this->load->view('common/header'); ?>
<div class="layout-wrapper layout-horizontal layout-content-navbar">
  <div class="layout-container">
    <?php $this->load->view('common/menu_view'); ?>
    <div class="layout-page">
      <div class="content-wrapper">
        <div class="container-xxl flex-grow-1 container-p-y">

          <div class="trans-page-header">
            <div class="d-flex align-items-center gap-3">
              <div class="trans-ph-icon" style="background:linear-gradient(135deg,#3b82f6,#1d4ed8)">
                <i class="bx bx-calculator" style="color:#fff;font-size:1.4rem;"></i>
              </div>
              <div><h5 class="trans-ph-title mb-0">Payroll — <?php echo $period; ?></h5><div class="text-muted" style="font-size:.76rem;">Payroll detail &amp; payslip generation</div></div>
            </div>
            <div class="d-flex gap-2">
              <?php if (($p->PayrollStatus ?? '') === 'Processed'): ?>
              <button class="btn btn-sm btn-success" id="btnMarkPaid" data-uid="<?php echo (int)$p->PayrollUID; ?>"><i class="bx bx-check-circle me-1"></i>Mark as Paid</button>
              <?php endif; ?>
              <a href="/payroll" class="btn btn-sm btn-outline-secondary"><i class="bx bx-arrow-back me-1"></i>Back</a>
            </div>
          </div>

          <!-- Header info -->
          <div class="card mb-3">
            <div class="card-body">
              <div class="row g-2" style="font-size:.875rem;">
                <div class="col-md-3"><div class="text-muted">Period</div><div class="fw-semibold"><?php echo $period; ?></div></div>
                <div class="col-md-2"><div class="text-muted">Status</div><span class="badge bg-label-<?php echo $badge; ?>"><?php echo $p->PayrollStatus ?? '—'; ?></span></div>
                <div class="col-md-2"><div class="text-muted">Employees</div><div class="fw-semibold"><?php echo count($lines); ?></div></div>
                <div class="col-md-2"><div class="text-muted">Gross</div><div class="fw-semibold"><?php echo $cur . ' ' . number_format((float)($p->TotalGross ?? 0), 2); ?></div></div>
                <div class="col-md-2"><div class="text-muted">Net Payable</div><div class="fw-semibold text-success"><?php echo $cur . ' ' . number_format((float)($p->TotalNetPayable ?? 0), 2); ?></div></div>
                <?php if (!empty($p->Notes)): ?><div class="col-12"><div class="text-muted">Notes</div><div><?php echo htmlspecialchars($p->Notes); ?></div></div><?php endif; ?>
              </div>
            </div>
          </div>

          <!-- Lines table -->
          <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
              <h6 class="mb-0">Employee Breakdown</h6>
              <a href="/payslips?payroll=<?php echo (int)$p->PayrollUID; ?>" class="btn btn-sm btn-outline-primary"><i class="bx bx-file me-1"></i>View Payslips</a>
            </div>
            <div class="table-responsive">
              <table class="table trans-table mb-0" style="font-size:.83rem;">
                <thead class="r2k-thead">
                  <tr><th>#</th><th>Employee</th><th>Type</th><th>Present</th><th>Absent</th><th>Gross</th><th>Adv. Rec.</th><th>Deductions</th><th class="text-success">Net</th><th>Payslip</th></tr>
                </thead>
                <tbody class="r2k-tbody">
                  <?php if (!empty($lines)): $sn = 0; foreach ($lines as $ln): $sn++; ?>
                  <tr>
                    <td class="text-muted"><?php echo $sn; ?></td>
                    <td>
                      <div class="fw-semibold"><?php echo htmlspecialchars($ln->EmployeeName ?? ''); ?></div>
                      <div class="text-muted" style="font-size:.75rem;"><?php echo htmlspecialchars($ln->EmployeeCode ?? ''); ?></div>
                    </td>
                    <td><span class="badge bg-label-secondary"><?php echo $ln->SalaryType ?? '—'; ?></span></td>
                    <td><?php echo number_format((float)($ln->PresentDays ?? 0), 1); ?></td>
                    <td><?php echo number_format((float)($ln->AbsentDays ?? 0), 1); ?></td>
                    <td><?php echo $cur . ' ' . number_format((float)($ln->GrossSalary ?? 0), 2); ?></td>
                    <td class="text-warning"><?php echo $cur . ' ' . number_format((float)($ln->AdvanceRecovery ?? 0), 2); ?></td>
                    <td class="text-danger"><?php echo $cur . ' ' . number_format((float)($ln->TotalDeductions ?? 0), 2); ?></td>
                    <td class="text-success fw-semibold"><?php echo $cur . ' ' . number_format((float)($ln->NetPayable ?? 0), 2); ?></td>
                    <td>
                      <a href="/payslips/view/<?php echo (int)$ln->PayrollLineUID; ?>" class="btn btn-icon btn-sm text-primary" title="View Payslip"><i class="bx bx-file"></i></a>
                      <a href="/payslips/print/<?php echo (int)$ln->PayrollLineUID; ?>" class="btn btn-icon btn-sm text-secondary" title="Print" target="_blank"><i class="bx bx-printer"></i></a>
                    </td>
                  </tr>
                  <?php endforeach; else: ?>
                  <tr><td colspan="10" class="text-center text-muted py-4">No payroll lines found.</td></tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>

        </div>
      </div>
      <?php $this->load->view('common/footer_desc'); ?>
    </div>
  </div>
</div>
<?php $this->load->view('common/footer'); ?>
<script src="/js/hrms/payroll.js"></script>
