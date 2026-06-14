<?php defined('BASEPATH') or exit('No direct script access allowed');
$cur  = htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹');
$tz   = $JwtData->Org->TimeZone ?? 'Asia/Kolkata';
$days = cal_days_in_month(CAL_GREGORIAN, $Month, $Year);
$statusColors = ['P'=>'#10b981','A'=>'#ef4444','H'=>'#f59e0b','L'=>'#3b82f6','Ho'=>'#94a3b8','W'=>'#94a3b8',''=>'#e2e8f0'];
$statusLabels = ['P'=>'P','A'=>'A','H'=>'H','L'=>'L','Ho'=>'Ho','W'=>'W'];
$statusMap    = ['Present'=>'P','Absent'=>'A','HalfDay'=>'H','Leave'=>'L','Holiday'=>'Ho','WeekOff'=>'W'];
?>
<?php $this->load->view('common/header'); ?>
<div class="layout-wrapper layout-horizontal layout-content-navbar">
  <div class="layout-container">
    <?php $this->load->view('common/menu_view'); ?>
    <div class="layout-page">
      <div class="content-wrapper apex-content">
        <?php $this->load->view('common/apex/page_header', [
          'pageTitle'       => 'Monthly Attendance — ' . date('F Y', mktime(0,0,0,$Month,1,$Year)),
          'pageDescription' => 'Summary grid for all employees',
        ]); ?>
        <div class="container-xxl flex-grow-1 container-p-y">

          <div class="d-flex justify-content-end mb-3 gap-2">
            <form method="get" class="d-flex gap-2">
              <select name="month" class="form-select form-select-sm" style="width:120px;">
                <?php for ($m = 1; $m <= 12; $m++): ?>
                <option value="<?php echo $m; ?>" <?php echo $m == $Month ? 'selected' : ''; ?>><?php echo date('F', mktime(0,0,0,$m,1)); ?></option>
                <?php endfor; ?>
              </select>
              <select name="year" class="form-select form-select-sm" style="width:90px;">
                <?php for ($y = date('Y') + 1; $y >= date('Y') - 2; $y--): ?>
                <option value="<?php echo $y; ?>" <?php echo $y == $Year ? 'selected' : ''; ?>><?php echo $y; ?></option>
                <?php endfor; ?>
              </select>
              <button class="btn btn-sm btn-primary" type="submit"><i class="bx bx-search"></i></button>
            </form>
            <a href="/attendance" class="btn btn-sm btn-outline-secondary"><i class="bx bx-list-ul me-1"></i>Daily View</a>
          </div>

          <!-- Legend -->
          <div class="d-flex gap-3 mb-3 flex-wrap">
            <?php $legends = ['P'=>['#10b981','Present'],'A'=>['#ef4444','Absent'],'H'=>['#f59e0b','Half Day'],'L'=>['#3b82f6','Leave'],'Ho'=>['#94a3b8','Holiday'],'W'=>['#94a3b8','WeekOff']]; ?>
            <?php foreach ($legends as $k => $v): ?>
            <div class="d-flex align-items-center gap-1"><span style="display:inline-block;width:20px;height:20px;border-radius:4px;background:<?php echo $v[0]; ?>;text-align:center;color:#fff;font-size:.65rem;line-height:20px;"><?php echo $k; ?></span><span style="font-size:.8rem;"><?php echo $v[1]; ?></span></div>
            <?php endforeach; ?>
          </div>

          <div class="card">
            <div class="table-responsive" style="overflow-x:auto;">
              <table class="table mb-0" style="font-size:.78rem;white-space:nowrap;">
                <thead class="r2k-thead">
                  <tr>
                    <th style="min-width:180px;position:sticky;left:0;background:#f8f9fa;z-index:2;">Employee</th>
                    <?php for ($d = 1; $d <= $days; $d++): $dow = date('N', mktime(0,0,0,$Month,$d,$Year)); ?>
                    <th class="text-center" style="min-width:30px;<?php echo $dow == 7 ? 'color:#94a3b8;' : ''; ?>"><?php echo $d; ?><br><span style="font-size:.65rem;"><?php echo date('D', mktime(0,0,0,$Month,$d,$Year)); ?></span></th>
                    <?php endfor; ?>
                    <th class="text-center">P</th><th class="text-center">A</th><th class="text-center">H</th><th class="text-center">L</th>
                  </tr>
                </thead>
                <tbody class="r2k-tbody">
                  <?php if (!empty($Employees)): foreach ($Employees as $emp):
                    $empUID = $emp->EmployeeUID;
                    $attMap = [];
                    if (!empty($Attendance)): foreach ($Attendance as $att): if ($att->EmployeeUID == $empUID): $day = (int)date('j', strtotime($att->AttendanceDate)); $attMap[$day] = $statusMap[$att->Status] ?? ''; endif; endforeach; endif;
                    $cntP = $cntA = $cntH = $cntL = 0;
                    foreach ($attMap as $k => $v) { if ($v==='P') $cntP++; elseif ($v==='A') $cntA++; elseif ($v==='H') $cntH++; elseif ($v==='L') $cntL++; }
                  ?>
                  <tr>
                    <td style="position:sticky;left:0;background:#fff;z-index:1;">
                      <div class="fw-semibold"><?php echo htmlspecialchars($emp->EmployeeName ?? ''); ?></div>
                      <div class="text-muted" style="font-size:.7rem;"><?php echo htmlspecialchars($emp->EmployeeCode ?? ''); ?></div>
                    </td>
                    <?php for ($d = 1; $d <= $days; $d++): $code = $attMap[$d] ?? ''; $bg = $statusColors[$code] ?? '#e2e8f0'; ?>
                    <td class="text-center p-0" style="height:32px;">
                      <span style="display:block;width:28px;height:28px;border-radius:4px;margin:2px auto;background:<?php echo $bg; ?>;color:#fff;font-size:.62rem;line-height:28px;"><?php echo $statusLabels[$code] ?? ''; ?></span>
                    </td>
                    <?php endfor; ?>
                    <td class="text-center text-success fw-semibold"><?php echo $cntP; ?></td>
                    <td class="text-center text-danger fw-semibold"><?php echo $cntA; ?></td>
                    <td class="text-center text-warning fw-semibold"><?php echo $cntH; ?></td>
                    <td class="text-center text-primary fw-semibold"><?php echo $cntL; ?></td>
                  </tr>
                  <?php endforeach; else: ?>
                  <tr><td colspan="<?php echo $days + 5; ?>" class="text-center text-muted py-4">No employees found.</td></tr>
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
