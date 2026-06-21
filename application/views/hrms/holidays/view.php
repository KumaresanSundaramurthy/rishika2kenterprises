<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php $this->load->view('common/header'); ?>
<div class="layout-wrapper layout-horizontal layout-content-navbar">
  <div class="layout-container">
    <?php $this->load->view('common/menu_view'); ?>
    <div class="layout-page">
      <div class="content-wrapper apex-content">
        <?php $this->load->view('common/apex/page_header', [
          'pageTitle'       => 'Holidays',
          'pageDescription' => 'Manage holiday calendar',
        ]); ?>
        <div class="container-xxl flex-grow-1 container-p-y">
          <div class="card">
            <div class="trans-toolbar">
              <div class="d-flex align-items-center gap-2 ms-2">
                <select class="form-select form-select-sm" id="holidayYearFilter" style="min-width:110px;">
                  <?php for ($y = date('Y') + 1; $y >= date('Y') - 2; $y--): ?>
                  <option value="<?php echo $y; ?>" <?php echo $y == date('Y') ? 'selected' : ''; ?>><?php echo $y; ?></option>
                  <?php endfor; ?>
                </select>
                <select class="form-select form-select-sm" id="holidayMonthFilter" style="min-width:130px;">
                  <option value="">All Months</option>
                  <?php $months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
                  foreach ($months as $mi => $mn): ?>
                  <option value="<?php echo $mi + 1; ?>"><?php echo $mn; ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="trans-toolbar-actions">
                <a href="#" class="r2k-icon-btn PageRefresh"><i class="bx bx-refresh"></i></a>
                <div class="r2k-search-wrap"><i class="bx bx-search r2k-si"></i><input type="text" id="SearchDetails" placeholder="Search…"><i class="bx bx-x r2k-clear d-none" id="clearSearch"></i></div>
                <button class="btn btn-sm btn-primary" id="btnNewHoliday"><i class="bx bx-plus me-1"></i>Add Holiday</button>
              </div>
            </div>
            <div class="table-responsive">
              <table class="table trans-table MainviewTable mb-0">
                <thead class="r2k-thead"><tr>
                  <th class="<?php echo ($JwtData->GenSettings->SerialNoDisplay ?? 0) == 1 ? '' : 'd-none'; ?>">S.No</th>
                  <th>Holiday Name</th><th>Date</th><th>Day</th><th>Type</th><th class="th-act">Actions</th>
                </tr></thead>
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
<div class="modal fade" id="holidayModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" aria-hidden="true">
  <div class="modal-dialog modal-dialog-top">
    <div class="modal-content">
      <div class="modal-header border-bottom d-flex align-items-center justify-content-between px-3 py-2 trans-theme">
        <div class="d-flex align-items-center gap-3">
          <div class="modal-doc-icon bg-primary bg-opacity-10">
            <i class="bx bx-calendar-star text-primary modal-doc-icon-inner"></i>
          </div>
          <h5 class="modal-title mb-0" id="holidayModalTitle">Add Holiday</h5>
        </div>
        <div class="d-flex align-items-center gap-2">
          <button type="button" class="btn btn-sm btn-primary" id="btnSaveHoliday">
            <i class="bx bx-check me-1"></i>Save
          </button>
          <button type="button" class="btn btn-sm btn-outline-danger" data-bs-dismiss="modal">
            <i class="bx bx-x me-1"></i>Close
          </button>
        </div>
      </div>
      <div class="modal-body p-4">
        <input type="hidden" id="holidayUID" value="0">
        <div class="mb-3">
          <label class="form-label">Holiday Name <span class="text-danger">*</span></label>
          <input type="text" class="form-control" id="holidayName" placeholder="e.g. Diwali" maxlength="150">
          <div class="invalid-feedback">Holiday name is required.</div>
        </div>
        <div class="mb-3">
          <label class="form-label">Date <span class="text-danger">*</span></label>
          <input type="text" class="form-control flatpickr-date" id="holidayDate" placeholder="Select date" readonly>
          <div class="invalid-feedback">Date is required.</div>
        </div>
        <div class="mb-3">
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" id="holidayIsOptional">
            <label class="form-check-label" for="holidayIsOptional">Optional Holiday</label>
          </div>
        </div>
        <div class="mb-0">
          <label class="form-label">Description</label>
          <textarea class="form-control" id="holidayDesc" rows="2" placeholder="Optional description"></textarea>
        </div>
      </div>
    </div>
  </div>
</div>
<?php $this->load->view('common/footer'); ?>
<script src="/js/hrms/holidays.js"></script>
