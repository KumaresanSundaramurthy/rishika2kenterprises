<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php $this->load->view('common/header'); ?>
<div class="layout-wrapper layout-horizontal layout-content-navbar">
  <div class="layout-container">
    <?php $this->load->view('common/menu_view'); ?>
    <div class="layout-page">
      <div class="content-wrapper apex-content">
        <?php $this->load->view('common/apex/page_header', [
          'pageTitle'       => 'Departments',
          'pageDescription' => 'Manage departments',
        ]); ?>
        <div class="container-xxl flex-grow-1 container-p-y">
          <div class="card">
            <div class="trans-toolbar">
              <div></div>
              <div class="trans-toolbar-actions">
                <a href="#" class="r2k-icon-btn PageRefresh"><i class="bx bx-refresh"></i></a>
                <div class="r2k-search-wrap"><i class="bx bx-search r2k-si"></i><input type="text" id="SearchDetails" placeholder="Search…"><i class="bx bx-x r2k-clear d-none" id="clearSearch"></i></div>
                <button class="btn btn-sm btn-primary" id="btnNewDept"><i class="bx bx-plus me-1"></i>New Department</button>
              </div>
            </div>
            <div class="table-responsive">
              <table class="table trans-table MainviewTable mb-0">
                <thead class="r2k-thead"><tr><th class="<?php echo ($JwtData->GenSettings->SerialNoDisplay ?? 0) == 1 ? '' : 'd-none'; ?>">S.No</th><th>Department Name</th><th>Description</th><th>Source</th><th class="th-act">Actions</th></tr></thead>
                <tbody class="r2k-tbody table-border-bottom-0" id="DeptTableBody"><?php echo $ModRowData; ?></tbody>
              </table>
            </div>
            <hr class="my-0">
            <div class="row mx-3 my-2 justify-content-between align-items-center DepartmentsPagination" id="DepartmentsPagination"><?php echo $ModPagination; ?></div>
          </div>
        </div>
      </div>
      <?php $this->load->view('common/footer_desc'); ?>
    </div>
  </div>
</div>
<!-- Department Modal -->
<div class="modal fade" id="deptModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" aria-hidden="true">
  <div class="modal-dialog modal-dialog-top">
    <div class="modal-content">
      <div class="modal-header border-bottom d-flex align-items-center justify-content-between px-3 py-2 trans-theme">
        <div class="d-flex align-items-center gap-3">
          <div class="modal-doc-icon bg-primary bg-opacity-10">
            <i class="bx bx-buildings text-primary modal-doc-icon-inner"></i>
          </div>
          <h5 class="modal-title mb-0" id="deptModalTitle">New Department</h5>
        </div>
        <div class="d-flex align-items-center gap-2">
          <button type="button" class="btn btn-sm btn-primary" id="btnSaveDept">
            <i class="bx bx-check me-1"></i>Save
          </button>
          <button type="button" class="btn btn-sm btn-outline-danger" data-bs-dismiss="modal">
            <i class="bx bx-x me-1"></i>Close
          </button>
        </div>
      </div>
      <div class="modal-body p-4">
        <input type="hidden" id="deptUID" value="0">
        <input type="hidden" id="deptIsSystem" value="0">
        <div class="mb-3">
          <label class="form-label">Department Name <span class="text-danger">*</span></label>
          <input type="text" class="form-control" id="deptName" placeholder="e.g. Sales" maxlength="200">
          <div class="invalid-feedback">Department name is required.</div>
        </div>
        <div class="mb-0">
          <label class="form-label">Description</label>
          <textarea class="form-control" id="deptDesc" rows="3" placeholder="Optional description"></textarea>
        </div>
      </div>
    </div>
  </div>
</div>
<?php $this->load->view('common/footer'); ?>
<script src="/js/hrms/departments.js"></script>
