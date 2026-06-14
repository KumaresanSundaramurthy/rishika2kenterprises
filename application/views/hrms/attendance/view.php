<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php $this->load->view('common/header'); ?>
<div class="layout-wrapper layout-horizontal layout-content-navbar">
  <div class="layout-container">
    <?php $this->load->view('common/menu_view'); ?>
    <div class="layout-page">
      <div class="content-wrapper apex-content">
        <?php $this->load->view('common/apex/page_header', [
          'pageTitle'       => 'Daily Attendance',
          'pageDescription' => 'Mark &amp; view day-wise attendance',
        ]); ?>
        <div class="container-xxl flex-grow-1 container-p-y">

          <div class="d-flex justify-content-end mb-3 gap-2">
            <input type="text" class="form-control form-control-sm flatpickr-date" id="attendanceDatePicker" value="<?php echo date('Y-m-d'); ?>" style="width:140px;">
            <a href="/attendance/monthly" class="btn btn-sm btn-outline-secondary"><i class="bx bx-calendar me-1"></i>Monthly View</a>
          </div>

          <!-- Stat cards -->
          <?php $st = $DailyStats ?? null; ?>
          <div class="row g-3 mb-3">
            <div class="col-6 col-md"><div class="trans-stat-card stat-all"><div class="trans-stat-label">Present</div><div class="trans-stat-count"><?php echo (int)($st->Present ?? 0); ?></div><div class="trans-stat-amount">&nbsp;</div><i class="bx bx-check-circle trans-stat-icon"></i></div></div>
            <div class="col-6 col-md"><div class="trans-stat-card stat-draft"><div class="trans-stat-label">Absent</div><div class="trans-stat-count"><?php echo (int)($st->Absent ?? 0); ?></div><div class="trans-stat-amount">&nbsp;</div><i class="bx bx-x-circle trans-stat-icon"></i></div></div>
            <div class="col-6 col-md"><div class="trans-stat-card stat-active"><div class="trans-stat-label">Half Day</div><div class="trans-stat-count"><?php echo (int)($st->HalfDay ?? 0); ?></div><div class="trans-stat-amount">&nbsp;</div><i class="bx bx-adjust trans-stat-icon"></i></div></div>
            <div class="col-6 col-md"><div class="trans-stat-card stat-converted"><div class="trans-stat-label">Leave</div><div class="trans-stat-count"><?php echo (int)($st->Leave ?? 0); ?></div><div class="trans-stat-amount">&nbsp;</div><i class="bx bx-calendar-minus trans-stat-icon"></i></div></div>
          </div>

          <div class="card">
            <div class="trans-toolbar">
              <div class="trans-toolbar-tabs">
                <ul class="nav trans-status-tabs" role="tablist">
                  <li class="nav-item"><a class="nav-link active att-tab" data-status="" href="#">All</a></li>
                  <li class="nav-item"><a class="nav-link att-tab" data-status="Present"  href="#">Present</a></li>
                  <li class="nav-item"><a class="nav-link att-tab" data-status="Absent"   href="#">Absent</a></li>
                  <li class="nav-item"><a class="nav-link att-tab" data-status="HalfDay"  href="#">Half Day</a></li>
                  <li class="nav-item"><a class="nav-link att-tab" data-status="Leave"    href="#">Leave</a></li>
                </ul>
              </div>
              <div class="trans-toolbar-actions">
                <button class="btn btn-sm btn-success" id="btnSaveAttendance" style="display:none;"><i class="bx bx-save me-1"></i>Save Changes</button>
                <button class="btn btn-sm btn-outline-primary" id="btnMarkAllPresent"><i class="bx bx-check-double me-1"></i>Mark All Present</button>
                <a href="#" class="r2k-icon-btn PageRefresh"><i class="bx bx-refresh"></i></a>
                <div class="r2k-search-wrap"><i class="bx bx-search r2k-si"></i><input type="text" id="SearchDetails" placeholder="Search employee…"><i class="bx bx-x r2k-clear d-none" id="clearSearch"></i></div>
              </div>
            </div>
            <div class="table-responsive">
              <table class="table trans-table MainviewTable mb-0">
                <thead class="r2k-thead"><tr><th>#</th><th>Employee</th><th>Department</th><th>Status</th><th>Check In</th><th>Check Out</th><th>Hours</th><th>Remarks</th></tr></thead>
                <tbody class="r2k-tbody table-border-bottom-0" id="AttendanceTableBody"><?php echo $ModRowData; ?></tbody>
              </table>
            </div>
            <hr class="my-0">
            <div class="row mx-3 my-2 justify-content-between align-items-center AttendancePagination" id="AttendancePagination"><?php echo $ModPagination; ?></div>
          </div>
        </div>
      </div>
      <?php $this->load->view('common/footer_desc'); ?>
    </div>
  </div>
</div>
<?php $this->load->view('common/footer'); ?>
<script src="/js/hrms/attendance.js"></script>
