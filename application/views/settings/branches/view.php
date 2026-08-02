<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php $this->load->view('common/header'); ?>
<div class="layout-wrapper layout-horizontal layout-content-navbar">
  <div class="layout-container">
    <?php $this->load->view('common/menu_view'); ?>
    <div class="layout-page">
      <div class="content-wrapper apex-content">
        <?php $this->load->view('common/apex/page_header', [
          'pageTitle'       => $PageTitle       ?? '',
          'pageDescription' => $PageDescription ?? '',
        ]); ?>
        <div class="container-xxl flex-grow-1 container-p-y">
          <div class="card">
            <div class="trans-toolbar">
              <div></div>
              <div class="trans-toolbar-actions">
                <a href="#" class="r2k-icon-btn PageRefresh"><i class="bx bx-refresh"></i></a>
                <div class="r2k-search-wrap"><i class="bx bx-search r2k-si"></i><input type="text" id="SearchDetails" placeholder="<?php echo t('search_branches', 'Search branches…'); ?>"><i class="bx bx-x r2k-clear d-none" id="clearSearch"></i></div>
                <button class="btn btn-sm btn-primary" id="btnNewBranch"
                  data-bs-toggle="tooltip" data-bs-placement="bottom"
                  title="<?php echo t('tooltip_new_branch', 'Create a new branch'); ?>">
                  <i class="bx bx-plus me-1"></i><?php echo t('lbl_new', 'New'); ?>
                </button>
              </div>
            </div>
            <div class="table-responsive">
              <table class="table trans-table MainviewTable mb-0">
                <thead class="r2k-thead">
                  <tr>
                    <th class="<?php echo ($JwtData->GenSettings->SerialNoDisplay ?? 0) == 1 ? '' : 'd-none'; ?>"><?php echo t('col_sno', 'S.No'); ?></th>
                    <th><?php echo t('col_branch_name', 'Branch Name'); ?></th>
                    <th><?php echo t('col_branch_code', 'Code'); ?></th>
                    <th><?php echo t('col_contact', 'Contact'); ?></th>
                    <th><?php echo t('col_gstin', 'GSTIN'); ?></th>
                    <th><?php echo t('col_type', 'Type'); ?></th>
                    <th><?php echo t('col_status', 'Status'); ?></th>
                    <th class="th-act"><?php echo t('col_actions', 'Actions'); ?></th>
                  </tr>
                </thead>
                <tbody class="r2k-tbody table-border-bottom-0" id="BranchTableBody">
                  <?php echo $ModRowData; ?>
                </tbody>
              </table>
            </div>
            <hr class="my-0">
            <div class="row mx-3 my-2 justify-content-between align-items-center" id="BranchesPagination">
              <?php echo $ModPagination; ?>
            </div>
          </div>
        </div>
      </div>
      <?php $this->load->view('common/footer_desc'); ?>
    </div>
  </div>
</div>

<!-- Branch Modal -->
<div class="modal fade" id="branchModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" aria-hidden="true">
  <div class="modal-dialog modal-dialog-top modal-lg">
    <div class="modal-content">
      <div class="modal-header border-bottom d-flex align-items-center justify-content-between px-3 py-2 trans-theme">
        <div class="d-flex align-items-center gap-3">
          <div class="modal-doc-icon bg-primary bg-opacity-10">
            <i class="bx bx-store text-primary modal-doc-icon-inner"></i>
          </div>
          <h5 class="modal-title mb-0" id="branchModalTitle"><?php echo t('lbl_new_branch', 'New Branch'); ?></h5>
        </div>
        <div class="d-flex align-items-center gap-2">
          <button type="button" class="btn btn-sm btn-primary" id="btnSaveBranch">
            <i class="bx bx-check me-1"></i><?php echo t('btn_save', 'Save'); ?>
          </button>
          <button type="button" class="btn btn-sm btn-outline-danger" data-bs-dismiss="modal">
            <i class="bx bx-x me-1"></i><?php echo t('btn_cancel', 'Close'); ?>
          </button>
        </div>
      </div>
      <div class="modal-body p-4">
        <input type="hidden" id="branchUID" value="0">
        <div class="row g-3">
          <div class="col-md-8">
            <label class="form-label"><?php echo t('lbl_branch_name', 'Branch Name'); ?> <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="branchName" placeholder="e.g. Chennai Branch" maxlength="100">
            <div class="invalid-feedback"><?php echo t('err_branch_name_req', 'Branch name is required.'); ?></div>
          </div>
          <div class="col-md-4">
            <label class="form-label"><?php echo t('lbl_branch_code', 'Branch Code'); ?> <span class="text-danger">*</span></label>
            <input type="text" class="form-control text-uppercase" id="branchCode" placeholder="e.g. CHN" maxlength="20">
            <div class="form-text"><?php echo t('hint_branch_code', 'Short unique code for this branch.'); ?></div>
            <div class="invalid-feedback"><?php echo t('err_branch_code_req', 'Branch code is required.'); ?></div>
          </div>
          <div class="col-12">
            <label class="form-label"><?php echo t('lbl_short_desc', 'Description'); ?></label>
            <input type="text" class="form-control" id="branchShortDesc" placeholder="Optional short description" maxlength="100">
          </div>
          <div class="col-md-6">
            <label class="form-label"><?php echo t('lbl_contact_person', 'Contact Person'); ?></label>
            <input type="text" class="form-control" id="branchContact" placeholder="Manager name" maxlength="100">
          </div>
          <div class="col-md-6">
            <label class="form-label"><?php echo t('lbl_mobile', 'Mobile Number'); ?></label>
            <input type="text" class="form-control" id="branchMobile" placeholder="Branch phone number" maxlength="20">
          </div>
          <div class="col-md-6">
            <label class="form-label"><?php echo t('lbl_email', 'Email Address'); ?></label>
            <input type="email" class="form-control" id="branchEmail" placeholder="branch@company.com" maxlength="100">
          </div>
          <div class="col-md-6">
            <label class="form-label"><?php echo t('lbl_gstin', 'GSTIN'); ?></label>
            <input type="text" class="form-control text-uppercase" id="branchGSTIN" placeholder="e.g. 33XXXXX..." maxlength="20">
          </div>
          <div class="col-12">
            <label class="form-label"><?php echo t('lbl_address_line1', 'Address Line 1'); ?></label>
            <input type="text" class="form-control" id="branchAddr1" placeholder="Street / Building" maxlength="255">
          </div>
          <div class="col-md-8">
            <label class="form-label"><?php echo t('lbl_address_line2', 'Address Line 2'); ?></label>
            <input type="text" class="form-control" id="branchAddr2" placeholder="Area / Landmark" maxlength="255">
          </div>
          <div class="col-md-4">
            <label class="form-label"><?php echo t('lbl_pincode', 'Pincode'); ?></label>
            <input type="text" class="form-control" id="branchPincode" placeholder="600001" maxlength="10">
          </div>
          <div class="col-12">
            <div class="form-check form-switch mt-1">
              <input class="form-check-input" type="checkbox" id="branchIsHeadOffice">
              <label class="form-check-label" for="branchIsHeadOffice">
                <?php echo t('lbl_head_office', 'Mark as Head Office'); ?>
              </label>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php $this->load->view('common/footer'); ?>
<script src="/js/settings/branches.js"></script>
