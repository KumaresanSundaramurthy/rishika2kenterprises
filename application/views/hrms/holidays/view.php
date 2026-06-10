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
              <div class="trans-ph-icon" style="background:linear-gradient(135deg,#f59e0b,#d97706)">
                <i class="bx bx-calendar-event" style="color:#fff;font-size:1.4rem;"></i>
              </div>
              <div><h5 class="trans-ph-title mb-0">Holidays</h5><div class="text-muted" style="font-size:.76rem;">Manage holiday calendar</div></div>
            </div>
            <button class="btn btn-sm btn-primary" id="btnNewHoliday"><i class="bx bx-plus me-1"></i>Add Holiday</button>
          </div>

          <!-- Year filter -->
          <div class="row g-2 mb-3">
            <div class="col-auto">
              <select class="form-select form-select-sm" id="holidayYearFilter" style="min-width:110px;">
                <?php for ($y = date('Y') + 1; $y >= date('Y') - 2; $y--): ?>
                <option value="<?php echo $y; ?>" <?php echo $y == date('Y') ? 'selected' : ''; ?>><?php echo $y; ?></option>
                <?php endfor; ?>
              </select>
            </div>
            <div class="col-auto">
              <select class="form-select form-select-sm" id="holidayMonthFilter" style="min-width:130px;">
                <option value="">All Months</option>
                <?php $months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
                foreach ($months as $mi => $mn): ?>
                <option value="<?php echo $mi + 1; ?>"><?php echo $mn; ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="card">
            <div class="trans-toolbar">
              <div></div>
              <div class="trans-toolbar-actions">
                <a href="#" class="r2k-icon-btn PageRefresh"><i class="bx bx-refresh"></i></a>
                <div class="r2k-search-wrap"><i class="bx bx-search r2k-si"></i><input type="text" id="SearchDetails" placeholder="Search…"><i class="bx bx-x r2k-clear d-none" id="clearSearch"></i></div>
              </div>
            </div>
            <div class="table-responsive">
              <table class="table trans-table MainviewTable mb-0">
                <thead class="r2k-thead"><tr><th>#</th><th>Holiday Name</th><th>Date</th><th>Day</th><th>Optional</th><th class="th-act">Actions</th></tr></thead>
                <tbody class="r2k-tbody table-border-bottom-0" id="HolidayTableBody"><?php echo $ModRowData; ?></tbody>
              </table>
            </div>
            <hr class="my-0">
            <div class="row mx-3 my-2 justify-content-between align-items-center HolidaysPagination" id="HolidaysPagination"><?php echo $ModPagination; ?></div>
          </div>
        </div>
      </div>
      <?php $this->load->view('common/footer_desc'); ?>
    </div>
  </div>
</div>
<!-- Holiday Modal -->
<div class="modal fade" id="holidayModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title" id="holidayModalTitle">Add Holiday</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <input type="hidden" id="holidayUID" value="0">
        <div class="mb-3"><label class="form-label">Holiday Name <span class="text-danger">*</span></label><input type="text" class="form-control" id="holidayName" placeholder="e.g. Diwali"></div>
        <div class="mb-3"><label class="form-label">Date <span class="text-danger">*</span></label><input type="text" class="form-control flatpickr-date" id="holidayDate" placeholder="Select date"></div>
        <div class="mb-3">
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" id="holidayIsOptional">
            <label class="form-check-label" for="holidayIsOptional">Optional Holiday</label>
          </div>
        </div>
        <div class="mb-3"><label class="form-label">Description</label><textarea class="form-control" id="holidayDesc" rows="2" placeholder="Optional description"></textarea></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button><button type="button" class="btn btn-primary" id="btnSaveHoliday"><i class="bx bx-save me-1"></i>Save</button></div>
    </div>
  </div>
</div>
<?php $this->load->view('common/footer'); ?>
<script src="/js/hrms/holidays.js"></script>
