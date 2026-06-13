<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php $this->load->view('common/header'); ?>
<div class="layout-wrapper layout-horizontal layout-content-navbar">
  <div class="layout-container">
    <?php $this->load->view('common/menu_view'); ?>
    <div class="layout-page">
      <div class="content-wrapper apex-content">
        <?php $this->load->view('common/apex/page_header', [
          'pageIcon'        => 'bx-badge',
          'pageIconBg'      => '#f5f3ff',
          'pageIconColor'   => '#7c3aed',
          'pageTitle'       => 'Designations',
          'pageDescription' => 'Manage job designations',
        ]); ?>
        <div class="container-xxl flex-grow-1 container-p-y">
          <div class="d-flex justify-content-end mb-3">
            <button class="btn btn-sm btn-primary" id="btnNewDesig"><i class="bx bx-plus me-1"></i>New Designation</button>
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
                <thead class="r2k-thead"><tr><th>#</th><th>Designation Name</th><th>Description</th><th>Source</th><th class="th-act">Actions</th></tr></thead>
                <tbody class="r2k-tbody table-border-bottom-0" id="DesigTableBody"><?php echo $ModRowData; ?></tbody>
              </table>
            </div>
            <hr class="my-0">
            <div class="row mx-3 my-2 justify-content-between align-items-center DesignationsPagination" id="DesignationsPagination"><?php echo $ModPagination; ?></div>
          </div>
        </div>
      </div>
      <?php $this->load->view('common/footer_desc'); ?>
    </div>
  </div>
</div>
<!-- Designation Modal -->
<div class="modal fade" id="desigModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title" id="desigModalTitle">New Designation</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <input type="hidden" id="desigUID" value="0">
        <div class="mb-3"><label class="form-label">Designation Name <span class="text-danger">*</span></label><input type="text" class="form-control" id="desigName" placeholder="e.g. Manager"></div>
        <div class="mb-3"><label class="form-label">Description</label><textarea class="form-control" id="desigDesc" rows="2" placeholder="Optional description"></textarea></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button><button type="button" class="btn btn-primary" id="btnSaveDesig"><i class="bx bx-save me-1"></i>Save</button></div>
    </div>
  </div>
</div>
<?php $this->load->view('common/footer'); ?>
<script src="/js/hrms/designations.js"></script>
