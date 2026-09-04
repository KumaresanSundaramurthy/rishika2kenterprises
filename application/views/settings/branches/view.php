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
        <div class="container-xxl flex-grow-1">
          <div class="card">
            <div class="trans-toolbar">
              <div class="r2k-search-wrap ms-2"><i class="bx bx-search r2k-si"></i><input type="text" id="SearchDetails" placeholder="<?php echo t('search_branches', 'Search branches…'); ?>"><i class="bx bx-x r2k-clear d-none" id="clearSearch"></i></div>
              <div class="trans-toolbar-actions">
                <a href="#" class="r2k-icon-btn PageRefresh"><i class="bx bx-refresh"></i></a>
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
                    <th><?php echo t('col_status', 'Status'); ?></th>
                    <th class="th-act text-center"><?php echo t('col_actions', 'Actions'); ?></th>
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
  <div class="modal-dialog modal-dialog-scrollable modal-xl modal-fullheight">
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
        <form id="branchForm" autocomplete="off">
          <input type="hidden" id="branchUID" value="0">
          <div class="row g-3">
            <!-- Name + Code -->
            <div class="col-md-8">
              <label class="form-label"><?php echo t('lbl_branch_name', 'Branch Name'); ?> <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="branchName" placeholder="e.g. Chennai Branch" maxlength="100">
              <div class="invalid-feedback"><?php echo t('err_branch_name_req', 'Branch name is required.'); ?></div>
            </div>
            <div class="col-md-4">
              <label class="form-label"><?php echo t('lbl_branch_code', 'Branch Code'); ?> <span class="text-danger">*</span></label>
              <input type="text" class="form-control text-uppercase" id="branchCode" placeholder="e.g. CHN" maxlength="20">
              <div class="form-text small branch-code-hint"><?php echo t('hint_branch_code', 'Short unique code for this branch.'); ?></div>
              <div class="invalid-feedback" id="branchCodeError"><?php echo t('err_branch_code_req', 'Branch code is required.'); ?></div>
            </div>
            <!-- Description -->
            <div class="col-12">
              <label class="form-label"><?php echo t('lbl_short_desc', 'Description'); ?></label>
              <input type="text" class="form-control" id="branchShortDesc" placeholder="Optional short description" maxlength="100">
            </div>
            <!-- Branch Type + Contact + PAN -->
            <div class="col-md-4">
              <label class="form-label"><?php echo t('lbl_branch_type', 'Branch Type'); ?></label>
              <select class="form-select" id="branchTypeUID">
                <option value="">— Select —</option>
                <?php foreach ($BranchTypes as $bt): ?>
                <option value="<?php echo $bt->BranchTypeUID; ?>"><?php echo htmlspecialchars($bt->Name); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label"><?php echo t('lbl_contact_person', 'Contact Person'); ?></label>
              <input type="text" class="form-control" id="branchContact" placeholder="Manager name" maxlength="100">
            </div>
            <div class="col-md-4">
              <label class="form-label"><?php echo t('lbl_pan', 'PAN Number'); ?></label>
              <input type="text" class="form-control text-uppercase" id="branchPAN" placeholder="e.g. ABCDE1234F" maxlength="10">
            </div>
            <!-- Mobile + Alternate Number -->
            <div class="col-md-6">
              <label class="form-label"><?php echo t('lbl_mobile', 'Mobile Number'); ?></label>
              <?php
                $bmDefaultISO2 = $JwtData->Org->OrgCountryISO2 ?? 'IN';
                $bmDefaultCode = $JwtData->Org->OrgCountryCode  ?? '+91';
              ?>
              <div class="input-group position-relative">
                <button type="button" class="btn btn-outline-secondary fw-semibold flex-shrink-0 r2k-cc-btn"
                        id="BM_MobileCCBtn" tabindex="-1"><?php echo htmlspecialchars($bmDefaultCode); ?></button>
                <div id="BM_CCDropdown" class="r2k-cc-dropdown">
                  <div class="p-2 border-bottom">
                    <input type="text" class="form-control form-control-sm" id="BM_CCSearch"
                           placeholder="<?php echo t('placeholder_search_country', 'Search country...'); ?>" autocomplete="off">
                  </div>
                  <div id="BM_CCList" class="r2k-cc-list"></div>
                </div>
                <input type="hidden" id="BM_CountryCode" value="<?php echo htmlspecialchars($bmDefaultCode); ?>">
                <input type="hidden" id="BM_CountryISO2" value="<?php echo htmlspecialchars($bmDefaultISO2); ?>">
                <input type="text" class="form-control" id="branchMobile" placeholder="<?php echo t('placeholder_branch_mobile', 'Branch phone number'); ?>" maxlength="20">
              </div>
            </div>
            <div class="col-md-6">
              <label class="form-label"><?php echo t('lbl_alt_number', 'Alternate Number'); ?></label>
              <input type="text" class="form-control" id="branchAltNumber" placeholder="Alternate phone / landline" maxlength="20">
            </div>
            <!-- Email + GSTIN -->
            <div class="col-md-6">
              <label class="form-label"><?php echo t('lbl_email', 'Email Address'); ?></label>
              <input type="email" class="form-control" id="branchEmail" placeholder="branch@company.com" maxlength="100">
            </div>
            <div class="col-md-6">
              <label class="form-label"><?php echo t('lbl_gstin', 'GSTIN'); ?></label>
              <div class="input-group">
                <input type="text" class="form-control text-uppercase" id="branchGSTIN" name="GSTIN" placeholder="e.g. 33XXXXX..." maxlength="20">
                <button type="button" class="btn btn-outline-primary" id="GSTIN_Fetch"><?php echo t('btn_fetch', 'Fetch'); ?></button>
              </div>
              <div id="branchGSTINValidatedMsg" class="gstin-validated-msg d-none">
                <i class="bx bx-check-circle"></i> <?php echo t('gstin_validated', 'GSTIN is validated'); ?>
              </div>
              <input type="hidden" name="GSTINValidated" id="branchGSTINValidated" value="0">
            </div>
            <!-- Address click-box -->
            <div class="col-12">
              <label class="form-label"><?php echo t('lbl_address', 'Address'); ?></label>
              <div id="branchAddrBox" class="form-control branch-addr-box">
                <span id="branchAddrPlaceholder" class="text-muted"><i class="bx bx-map-pin me-1"></i><?php echo t('placeholder_click_addr', 'Click to add address...'); ?></span>
                <span id="branchAddrText" class="d-none"></span>
              </div>
            </div>
            <!-- Landmark -->
            <div class="col-12">
              <label class="form-label"><?php echo t('lbl_landmark', 'Landmark'); ?></label>
              <input type="text" class="form-control" id="branchLandmark" placeholder="e.g. Near bus stand, opposite to..." maxlength="100">
            </div>
            <!-- Branch Capabilities -->
            <div class="col-12">
              <label class="form-label d-block"><?php echo t('lbl_branch_capabilities', 'Branch Capabilities'); ?></label>
              <div class="d-flex flex-wrap gap-4">
                <div class="form-check form-switch">
                  <input class="form-check-input" type="checkbox" id="branchIsWarehouse">
                  <label class="form-check-label" for="branchIsWarehouse"><?php echo t('lbl_warehouse', 'Warehouse'); ?></label>
                </div>
                <div class="form-check form-switch">
                  <input class="form-check-input" type="checkbox" id="branchIsDispatchPoint">
                  <label class="form-check-label" for="branchIsDispatchPoint"><?php echo t('lbl_dispatch_point', 'Dispatch Point'); ?></label>
                </div>
                <div class="form-check form-switch">
                  <input class="form-check-input" type="checkbox" id="branchIsSalesPoint">
                  <label class="form-check-label" for="branchIsSalesPoint"><?php echo t('lbl_sales_point', 'Sales Point'); ?></label>
                </div>
                <div class="form-check form-switch">
                  <input class="form-check-input" type="checkbox" id="branchIsServiceCenter">
                  <label class="form-check-label" for="branchIsServiceCenter"><?php echo t('lbl_service_center', 'Service Center'); ?></label>
                </div>
              </div>
            </div>
            <!-- Head Office toggle -->
            <div class="col-12">
              <div class="form-check form-switch mt-1">
                <input class="form-check-input" type="checkbox" id="branchIsHeadOffice">
                <label class="form-check-label" for="branchIsHeadOffice">
                  <?php echo t('lbl_head_office', 'Mark as Head Office'); ?>
                </label>
                <small class="text-muted d-block mt-1"><?php echo t('hint_head_office', 'Marking this as HQ will automatically unmark the current HQ.'); ?></small>
              </div>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<?php $this->load->view('common/form/address_form'); ?>
<?php $this->load->view('common/modals/gstin_confirm_modal'); ?>

<?php $this->load->view('common/footer'); ?>
<script>var _bmOrgISO2 = '<?php echo addslashes($bmDefaultISO2); ?>';</script>
<script src="<?php echo _assetV('/js/common/phone_cc_dropdown.js'); ?>"></script>
<script src="<?php echo _assetV('/js/common/address.js'); ?>"></script>
<script src="<?php echo _assetV('/js/common/gstin_fetch.js'); ?>"></script>
<script src="/js/settings/branches.js"></script>
