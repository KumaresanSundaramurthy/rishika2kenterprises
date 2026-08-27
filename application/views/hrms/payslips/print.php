<?php defined('BASEPATH') or exit('No direct script access allowed');
$cur  = htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹');
$s    = $Slip ?? new stdClass();
$org  = $OrgInfo ?? new stdClass();
$months = ['','January','February','March','April','May','June','July','August','September','October','November','December'];
$period = ($months[(int)($s->PayrollMonth ?? 0)] ?? '—') . ' ' . ($s->PayrollYear ?? '');
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Payslip — <?php echo htmlspecialchars($s->EmployeeName ?? ''); ?> — <?php echo $period; ?></title>
  <style>
    *{box-sizing:border-box;margin:0;padding:0;}
    body{font-family:'Segoe UI',Arial,sans-serif;font-size:12px;color:#1e293b;background:#fff;padding:24px;}
    .slip-wrap{max-width:680px;margin:0 auto;border:1px solid #e2e8f0;border-radius:8px;padding:24px;}
    .org-header{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:16px;}
    .org-name{font-size:16px;font-weight:700;}
    .org-addr{font-size:11px;color:#64748b;margin-top:2px;}
    .slip-title{text-align:right;}.slip-title .label{font-size:10px;color:#64748b;text-transform:uppercase;letter-spacing:.5px;}
    .slip-title .period{font-size:14px;font-weight:600;margin-top:2px;}
    .slip-title .badge{display:inline-block;padding:2px 8px;border-radius:12px;font-size:10px;margin-top:4px;background:#dbeafe;color:#1d4ed8;}
    hr{border:none;border-top:1px solid #e2e8f0;margin:12px 0;}
    .info-grid{display:grid;grid-template-columns:1fr 1fr;gap:6px 16px;margin-bottom:12px;}
    .info-item .lbl{font-size:10px;color:#64748b;text-transform:uppercase;letter-spacing:.3px;}
    .info-item .val{font-weight:600;margin-top:1px;}
    .earn-ded{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:12px;}
    .section-title{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px;color:#475569;}
    table.breakdown{width:100%;border-collapse:collapse;}
    table.breakdown td{padding:3px 0;border-bottom:1px solid #f1f5f9;}
    table.breakdown tr:last-child td{border-bottom:none;font-weight:700;}
    table.breakdown td:last-child{text-align:right;}
    .net-row{display:flex;justify-content:space-between;align-items:center;background:#f0fdf4;border-radius:6px;padding:10px 14px;margin-top:12px;}
    .net-label{font-size:13px;font-weight:700;}
    .net-value{font-size:18px;font-weight:800;color:#16a34a;}
    .footer-note{font-size:10px;color:#94a3b8;text-align:center;margin-top:16px;}
    @media print{body{padding:0;}.slip-wrap{border:none;padding:16px;max-width:100%;}@page{size:A4 portrait;margin:10mm;}}
  </style>
</head>
<body>
<div class="slip-wrap">
  <div class="org-header">
    <div>
      <div class="org-name"><?php echo htmlspecialchars($org->OrgName ?? 'Organisation'); ?></div>
      <div class="org-addr"><?php echo htmlspecialchars($org->Address ?? ''); ?></div>
    </div>
    <div class="slip-title">
      <div class="label"><?php echo t('lbl_salary_slip', 'Salary Slip'); ?></div>
      <div class="period"><?php echo $period; ?></div>
      <div class="badge"><?php echo $s->PayrollStatus ?? '—'; ?></div>
    </div>
  </div>
  <hr>
  <div class="info-grid">
    <div class="info-item"><div class="lbl"><?php echo t('col_employee_name', 'Employee Name'); ?></div><div class="val"><?php echo htmlspecialchars($s->EmployeeName ?? ''); ?></div></div>
    <div class="info-item"><div class="lbl"><?php echo t('col_employee_code', 'Employee Code'); ?></div><div class="val"><?php echo htmlspecialchars($s->EmployeeCode ?? ''); ?></div></div>
    <div class="info-item"><div class="lbl"><?php echo t('col_department', 'Department'); ?></div><div class="val"><?php echo htmlspecialchars($s->DepartmentName ?? '—'); ?></div></div>
    <div class="info-item"><div class="lbl"><?php echo t('col_designation', 'Designation'); ?></div><div class="val"><?php echo htmlspecialchars($s->DesignationName ?? '—'); ?></div></div>
    <div class="info-item"><div class="lbl"><?php echo t('col_salary_type', 'Salary Type'); ?></div><div class="val"><?php echo htmlspecialchars($s->SalaryType ?? '—'); ?></div></div>
    <div class="info-item"><div class="lbl"><?php echo t('col_working_days', 'Working Days'); ?></div><div class="val"><?php echo number_format((float)($s->WorkingDays ?? 0)); ?></div></div>
    <div class="info-item"><div class="lbl"><?php echo t('col_present_days', 'Present Days'); ?></div><div class="val"><?php echo number_format((float)($s->PresentDays ?? 0), 1); ?></div></div>
    <div class="info-item"><div class="lbl"><?php echo t('col_absent_days', 'Absent Days'); ?></div><div class="val"><?php echo number_format((float)($s->AbsentDays ?? 0), 1); ?></div></div>
  </div>
  <hr>
  <div class="earn-ded">
    <div>
      <div class="section-title"><?php echo t('lbl_earnings', 'Earnings'); ?></div>
      <table class="breakdown">
        <tr><td><?php echo t('lbl_basic_salary', 'Basic Salary'); ?></td><td><?php echo $cur . ' ' . smartDecimal((float)($s->BasicSalary ?? 0)); ?></td></tr>
        <tr><td><?php echo t('lbl_allowances', 'Allowances'); ?></td><td><?php echo $cur . ' ' . smartDecimal((float)($s->Allowances ?? 0)); ?></td></tr>
        <tr><td><?php echo t('lbl_incentives', 'Incentives'); ?></td><td><?php echo $cur . ' ' . smartDecimal((float)($s->Incentives ?? 0)); ?></td></tr>
        <?php if ((float)($s->OtherEarnings ?? 0) > 0): ?><tr><td><?php echo t('lbl_other_earnings', 'Other Earnings'); ?></td><td><?php echo $cur . ' ' . smartDecimal((float)$s->OtherEarnings); ?></td></tr><?php endif; ?>
        <tr><td><?php echo t('lbl_gross_salary', 'Gross Salary'); ?></td><td style="color:#16a34a;"><?php echo $cur . ' ' . smartDecimal((float)($s->GrossSalary ?? 0)); ?></td></tr>
      </table>
    </div>
    <div>
      <div class="section-title"><?php echo t('lbl_deductions', 'Deductions'); ?></div>
      <table class="breakdown">
        <tr><td><?php echo t('lbl_absent_deduction', 'Absent Deduction'); ?></td><td style="color:#dc2626;"><?php echo $cur . ' ' . smartDecimal((float)($s->AbsentDeduction ?? 0)); ?></td></tr>
        <tr><td><?php echo t('lbl_fixed_deductions', 'Fixed Deductions'); ?></td><td style="color:#dc2626;"><?php echo $cur . ' ' . smartDecimal((float)($s->FixedDeductions ?? 0)); ?></td></tr>
        <tr><td><?php echo t('lbl_advance_recovery', 'Advance Recovery'); ?></td><td style="color:#d97706;"><?php echo $cur . ' ' . smartDecimal((float)($s->AdvanceRecovery ?? 0)); ?></td></tr>
        <?php if ((float)($s->OtherDeductions ?? 0) > 0): ?><tr><td><?php echo t('lbl_other_deductions', 'Other Deductions'); ?></td><td style="color:#dc2626;"><?php echo $cur . ' ' . smartDecimal((float)$s->OtherDeductions); ?></td></tr><?php endif; ?>
        <tr><td><?php echo t('lbl_total_deductions', 'Total Deductions'); ?></td><td style="color:#dc2626;"><?php echo $cur . ' ' . smartDecimal((float)($s->TotalDeductions ?? 0)); ?></td></tr>
      </table>
    </div>
  </div>
  <div class="net-row">
    <div class="net-label"><?php echo strtoupper(t('lbl_net_payable', 'Net Payable')); ?></div>
    <div class="net-value"><?php echo $cur . ' ' . smartDecimal((float)($s->NetPayable ?? 0)); ?></div>
  </div>
  <div class="footer-note">This is a computer-generated payslip and does not require a signature.</div>
</div>
<script>window.onload = function(){ window.print(); };</script>
</body>
</html>
