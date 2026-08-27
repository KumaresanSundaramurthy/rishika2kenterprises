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
      <div class="content-wrapper apex-content">
        <?php $this->load->view('common/apex/page_header', [
          'pageTitle'       => 'Payroll — ' . $period,
          'pageDescription' => 'Payroll detail &amp; payslip generation',
        ]); ?>
        <div class="container-xxl flex-grow-1 container-p-y">

          <div class="d-flex justify-content-end mb-3 gap-2">
            <?php if (($p->PayrollStatus ?? '') === 'Processed'): ?>
            <button class="btn btn-sm btn-success" id="btnMarkPaid" data-uid="<?php echo (int)$p->PayrollUID; ?>"><i class="bx bx-check-circle me-1"></i><?php echo t('btn_mark_paid', 'Mark as Paid'); ?></button>
            <?php endif; ?>
            <a href="/payroll" class="btn btn-sm btn-outline-secondary"><i class="bx bx-arrow-back me-1"></i><?php echo t('btn_back', 'Back'); ?></a>
          </div>

          <!-- Header info -->
          <div class="card mb-3">
            <div class="card-body">
              <div class="row g-2" style="font-size:.875rem;">
                <div class="col-md-3"><div class="text-muted"><?php echo t('lbl_period', 'Period'); ?></div><div class="fw-semibold"><?php echo $period; ?></div></div>
                <div class="col-md-2"><div class="text-muted"><?php echo t('col_status', 'Status'); ?></div><span class="badge bg-label-<?php echo $badge; ?>"><?php echo $p->PayrollStatus ?? '—'; ?></span></div>
                <div class="col-md-2"><div class="text-muted"><?php echo t('lbl_employees', 'Employees'); ?></div><div class="fw-semibold"><?php echo count($lines); ?></div></div>
                <div class="col-md-2"><div class="text-muted"><?php echo t('lbl_gross', 'Gross'); ?></div><div class="fw-semibold"><?php echo $cur . ' ' . smartDecimal((float)($p->TotalGross ?? 0)); ?></div></div>
                <div class="col-md-2"><div class="text-muted"><?php echo t('lbl_net_payable', 'Net Payable'); ?></div><div class="fw-semibold text-success"><?php echo $cur . ' ' . smartDecimal((float)($p->TotalNetPayable ?? 0)); ?></div></div>
                <?php if (!empty($p->Notes)): ?><div class="col-12"><div class="text-muted"><?php echo t('lbl_notes', 'Notes'); ?></div><div><?php echo htmlspecialchars($p->Notes); ?></div></div><?php endif; ?>
              </div>
            </div>
          </div>

          <!-- Lines table -->
          <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
              <h6 class="mb-0"><?php echo t('col_employee_breakdown', 'Employee Breakdown'); ?></h6>
              <a href="/payslips?payroll=<?php echo (int)$p->PayrollUID; ?>" class="btn btn-sm btn-outline-primary"><i class="bx bx-file me-1"></i><?php echo t('btn_view_payslips', 'View Payslips'); ?></a>
            </div>
            <div class="table-responsive">
              <table class="table trans-table mb-0" style="font-size:.83rem;">
                <thead class="r2k-thead">
                  <tr><th><?php echo t('col_sno', '#'); ?></th><th><?php echo t('col_employee_name', 'Employee'); ?></th><th><?php echo t('col_type', 'Type'); ?></th><th><?php echo t('att_present', 'Present'); ?></th><th><?php echo t('att_absent', 'Absent'); ?></th><th><?php echo t('lbl_gross', 'Gross'); ?></th><th><?php echo t('col_advance_rec', 'Adv. Rec.'); ?></th><th><?php echo t('col_deductions', 'Deductions'); ?></th><th class="text-success"><?php echo t('lbl_net_payable', 'Net'); ?></th><th><?php echo t('col_payslip', 'Payslip'); ?></th></tr>
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
                    <td><?php echo $cur . ' ' . smartDecimal((float)($ln->GrossSalary ?? 0)); ?></td>
                    <td class="text-warning"><?php echo $cur . ' ' . smartDecimal((float)($ln->AdvanceRecovery ?? 0)); ?></td>
                    <td class="text-danger"><?php echo $cur . ' ' . smartDecimal((float)($ln->TotalDeductions ?? 0)); ?></td>
                    <td class="text-success fw-semibold"><?php echo $cur . ' ' . smartDecimal((float)($ln->NetPayable ?? 0)); ?></td>
                    <td>
                      <a href="/payslips/view/<?php echo (int)$ln->PayrollLineUID; ?>" class="btn btn-icon btn-sm text-primary" title="<?php echo t('btn_view_detail', 'View Payslip'); ?>"><i class="bx bx-file"></i></a>
                      <a href="/payslips/print/<?php echo (int)$ln->PayrollLineUID; ?>" class="btn btn-icon btn-sm text-secondary" title="<?php echo t('btn_print', 'Print'); ?>" target="_blank"><i class="bx bx-printer"></i></a>
                    </td>
                  </tr>
                  <?php endforeach; else: ?>
                  <tr><td colspan="10" class="text-center text-muted py-4"><?php echo t('empty_payroll_lines', 'No payroll lines found.'); ?></td></tr>
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
