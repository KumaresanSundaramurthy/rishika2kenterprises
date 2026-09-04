<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
if (!isset($RolesList))       { $RolesList       = []; }
if (!isset($DepartmentList))  { $DepartmentList  = []; }
if (!isset($DesignationList)) { $DesignationList = []; }
if (!isset($CanSeeSalary))    { $CanSeeSalary    = false; }
if (!isset($BranchesList))    { $BranchesList    = []; }
?>

<div class="modal fade" id="userModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-xl modal-fullheight">
        <div class="modal-content">

            <!-- Header -->
            <div class="modal-header bg-white border-bottom d-flex align-items-center justify-content-between px-3 py-2 trans-theme">
                <div class="d-flex align-items-center gap-2">
                    <div class="modal-doc-icon" style="background:#ede9fe;">
                        <i class="bx bx-user modal-doc-icon-inner" style="color:#7c3aed;"></i>
                    </div>
                    <h5 class="modal-title mb-0" id="userModalTitle">Add Staff</h5>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-sm btn-primary" id="saveUserBtn">
                        <span class="spinner-border spinner-border-sm me-1 d-none" id="saveUserSpinner"></span>
                        <i class="bx bx-check me-1" id="saveUserIcon"></i>Save
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger" data-bs-dismiss="modal">
                        <i class="bx bx-x me-1"></i>Close
                    </button>
                </div>
            </div>

            <input type="hidden" id="UserModalUID" value="0">

            <!-- Tabs -->
            <div class="border-bottom px-3 pt-2 bg-white">
                <ul class="nav nav-tabs border-0 gap-1" id="staffFormTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active px-3 py-2" data-bs-toggle="tab" data-bs-target="#tabPersonal" href="#">
                            <i class="bx bx-user me-1"></i>Personal
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link px-3 py-2" data-bs-toggle="tab" data-bs-target="#tabEmployment" href="#">
                            <i class="bx bx-briefcase me-1"></i>Employment
                        </a>
                    </li>
                    <?php if ($CanSeeSalary): ?>
                    <li class="nav-item">
                        <a class="nav-link px-3 py-2" data-bs-toggle="tab" data-bs-target="#tabSalary" href="#">
                            <i class="bx bx-money me-1"></i>Salary
                        </a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link px-3 py-2" data-bs-toggle="tab" data-bs-target="#tabAddress" href="#">
                            <i class="bx bx-map me-1"></i>Address
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link px-3 py-2" data-bs-toggle="tab" data-bs-target="#tabLogin" href="#">
                            <i class="bx bx-lock me-1"></i>Login Access
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link px-3 py-2" data-bs-toggle="tab" data-bs-target="#tabBranchAccess" href="#">
                            <i class="bx bx-buildings me-1"></i>Branch Access
                        </a>
                    </li>
                    <li class="nav-item d-none" id="attachTabNavItem">
                        <a class="nav-link px-3 py-2" data-bs-toggle="tab" data-bs-target="#tabAttachments" href="#">
                            <i class="bx bx-paperclip me-1"></i>Attachments <span class="badge bg-label-secondary ms-1 d-none" id="attachTabCount" style="font-size:.7rem;"></span>
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Body -->
            <div class="modal-body p-0">
                <div class="tab-content">

                    <!-- ══ TAB 1: Personal Info ════════════════════════════════ -->
                    <div class="tab-pane fade show active" id="tabPersonal">
                        <div class="row g-3">

                            <!-- First Name -->
                            <div class="col-md-4">
                                <label class="form-label">First Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="UserFirstName" maxlength="100" placeholder="First name" autocomplete="off">
                            </div>

                            <!-- Last Name -->
                            <div class="col-md-4">
                                <label class="form-label">Last Name</label>
                                <input type="text" class="form-control" id="UserLastName" maxlength="100" placeholder="Last name" autocomplete="off">
                            </div>

                            <!-- Mobile -->
                            <div class="col-md-4">
                                <label class="form-label">Mobile Number</label>
                                <div class="input-group position-relative">
                                    <button type="button" class="btn btn-outline-secondary fw-semibold flex-shrink-0 r2k-cc-btn"
                                            id="UM_MobileCCBtn" tabindex="-1">+91</button>
                                    <div id="UM_CCDropdown" class="r2k-cc-dropdown">
                                        <div class="p-2 border-bottom">
                                            <input type="text" class="form-control form-control-sm" id="UM_CCSearch"
                                                   placeholder="Search country..." autocomplete="off">
                                        </div>
                                        <div id="UM_CCList" class="r2k-cc-list"></div>
                                    </div>
                                    <input type="hidden" id="UM_CountryCode" value="+91">
                                    <input type="hidden" id="UM_CountryISO2" value="IN">
                                    <input type="text" class="form-control" id="UserMobile" maxlength="15" placeholder="98765 43210">
                                </div>
                            </div>

                            <!-- Email -->
                            <div class="col-md-4">
                                <label class="form-label">Email Address <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="UserEmail" maxlength="200" placeholder="email@example.com" autocomplete="off">
                            </div>

                        </div>
                    </div>

                    <!-- ══ TAB 2: Employment ═══════════════════════════════════ -->
                    <div class="tab-pane fade" id="tabEmployment">
                        <div class="row g-3">

                            <!-- Employee Code -->
                            <div class="col-md-4">
                                <label class="form-label">Employee Code</label>
                                <input type="text" class="form-control bg-light" id="UserEmpCode" maxlength="50" readonly>
                                <div class="form-text">Auto-generated.</div>
                            </div>

                            <!-- Department -->
                            <div class="col-md-4">
                                <label class="form-label">Department</label>
                                <select class="form-select" id="UserDeptUID">
                                    <option value="">— Select Department —</option>
                                    <?php foreach ($DepartmentList as $dept): ?>
                                    <option value="<?php echo (int)$dept->DepartmentUID; ?>"><?php echo htmlspecialchars($dept->DepartmentName); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Designation -->
                            <div class="col-md-4">
                                <label class="form-label">Designation</label>
                                <select class="form-select" id="UserDesigUID">
                                    <option value="">— Select Designation —</option>
                                    <?php foreach ($DesignationList as $desig): ?>
                                    <option value="<?php echo (int)$desig->DesignationUID; ?>"><?php echo htmlspecialchars($desig->DesignationName); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Date of Joining -->
                            <div class="col-md-4">
                                <label class="form-label">Date of Joining</label>
                                <input type="text" class="form-control flatpickr-date" id="UserDOJ" placeholder="Select date" autocomplete="off">
                            </div>

                            <!-- Employee Status -->
                            <div class="col-md-4">
                                <label class="form-label">Employment Status</label>
                                <select class="form-select" id="UserEmpStatus">
                                    <option value="Active">Active</option>
                                    <option value="Resigned">Resigned</option>
                                    <option value="Terminated">Terminated</option>
                                    <option value="OnLeave">On Leave</option>
                                </select>
                            </div>

                        </div>
                    </div>

                    <!-- ══ TAB 3: Salary (Admin only) ═════════════════════════ -->
                    <?php if ($CanSeeSalary): ?>
                    <div class="tab-pane fade" id="tabSalary">
                        <div class="row g-3">

                            <!-- Salary Type -->
                            <div class="col-md-4">
                                <label class="form-label">Salary Type</label>
                                <select class="form-select" id="UserSalaryType">
                                    <option value="Monthly">Monthly Salary</option>
                                    <option value="Daily">Daily Wage</option>
                                    <option value="Hourly">Hourly Wage</option>
                                </select>
                            </div>

                            <!-- Basic Salary -->
                            <div class="col-md-4">
                                <label class="form-label" id="lblBasicSalary">Basic Salary</label>
                                <div class="input-group">
                                    <span class="input-group-text">₹</span>
                                    <input type="number" class="form-control" id="UserBasicSalary" min="0" step="any" placeholder="0.00">
                                </div>
                            </div>

                            <!-- Allowances -->
                            <div class="col-md-4">
                                <label class="form-label">Allowances</label>
                                <div class="input-group">
                                    <span class="input-group-text">₹</span>
                                    <input type="number" class="form-control" id="UserAllowances" min="0" step="any" placeholder="0.00">
                                </div>
                            </div>

                            <!-- Incentives -->
                            <div class="col-md-4">
                                <label class="form-label">Incentives</label>
                                <div class="input-group">
                                    <span class="input-group-text">₹</span>
                                    <input type="number" class="form-control" id="UserIncentives" min="0" step="any" placeholder="0.00">
                                </div>
                            </div>

                            <!-- Fixed Deductions -->
                            <div class="col-md-4">
                                <label class="form-label">Fixed Deductions</label>
                                <div class="input-group">
                                    <span class="input-group-text">₹</span>
                                    <input type="number" class="form-control" id="UserFixedDeductions" min="0" step="any" placeholder="0.00">
                                </div>
                                <div class="form-text">Applied every payroll cycle.</div>
                            </div>

                            <!-- Gross summary hint -->
                            <div class="col-12">
                                <div class="alert alert-light py-2 px-3 mb-0 border" style="font-size:.8rem;">
                                    <i class="bx bx-info-circle me-1 text-primary"></i>
                                    Gross = Basic + Allowances + Incentives &nbsp;|&nbsp; Net = Gross − Fixed Deductions − Absent Deductions − Advance Recovery
                                </div>
                            </div>

                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- ══ TAB 4: Address ══════════════════════════════════════ -->
                    <div class="tab-pane fade" id="tabAddress">
                        <div class="row g-3">
                            <!-- Current Address -->
                            <div class="col-md-6">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="fw-semibold text-muted text-uppercase" style="font-size:.76rem;letter-spacing:.04em;">Current Address</span>
                                        <a href="javascript:void(0)" class="btn btn-sm btn-outline-warning py-0 px-1" id="addBillingAddress" title="Add Current Address">
                                            <i class="bx bx-plus-circle fs-5"></i>
                                        </a>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-secondary d-none" id="copyToShippingBtn">
                                        <i class="bx bx-copy-alt me-1"></i>Copy to Permanent
                                    </button>
                                </div>
                                <div id="appendBillingAddress"></div>
                            </div>

                            <!-- Permanent Address -->
                            <div class="col-md-6">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="fw-semibold text-muted text-uppercase" style="font-size:.76rem;letter-spacing:.04em;">Permanent Address</span>
                                        <a href="javascript:void(0)" class="btn btn-sm btn-outline-warning py-0 px-1" id="addShippingAddress" title="Add Permanent Address">
                                            <i class="bx bx-plus-circle fs-5"></i>
                                        </a>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-secondary d-none" id="copyToBillingBtn">
                                        <i class="bx bx-copy-alt me-1"></i>Copy to Current
                                    </button>
                                </div>
                                <div id="appendShippingAddress"></div>
                            </div>
                        </div>
                    </div>

                    <!-- ══ TAB 5: Login Access ═════════════════════════════════ -->
                    <div class="tab-pane fade" id="tabLogin">

                        <!-- Toggle -->
                        <div class="d-flex align-items-center gap-3 mb-4 p-3 rounded" style="background:#f8f7ff; border:1px solid rgba(124,58,237,.15);">
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" id="UserHasLoginAccess" checked style="width:2.5em; height:1.25em;">
                                <label class="form-check-label fw-semibold ms-1" for="UserHasLoginAccess" style="font-size:.87rem;">
                                    Allow ERP Login
                                </label>
                            </div>
                            <span class="text-muted" style="font-size:.78rem;">Turn off for staff who don't need system access (field workers, delivery, etc.)</span>
                        </div>

                        <!-- Login fields — shown only when toggle is ON -->
                        <div id="loginAccessSection">

                            <!-- User Code (edit-only) -->
                            <div class="row g-3 mb-3 d-none" id="userCodeWrap">
                                <div class="col-md-4">
                                    <label class="form-label">User Code</label>
                                    <input type="text" class="form-control bg-light" id="UserCodeDisplay" readonly placeholder="Auto-generated">
                                </div>
                            </div>

                            <div class="row g-3">

                                <!-- Username -->
                                <div class="col-md-6">
                                    <label class="form-label">Username <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text text-muted">@</span>
                                        <input type="text" class="form-control" id="UserUsername" maxlength="100" placeholder="e.g. john.doe" autocomplete="off">
                                    </div>
                                    <div class="form-text">Cannot be changed after creation.</div>
                                </div>

                                <!-- Role -->
                                <div class="col-md-6">
                                    <label class="form-label">Role <span class="text-danger">*</span></label>
                                    <select class="form-select" id="UserRoleUID">
                                        <option value="">— Select Role —</option>
                                        <?php foreach ($RolesList as $role): ?>
                                        <?php if (strtolower(trim($role->Name)) === 'super admin') continue; ?>
                                        <option value="<?php echo (int)$role->RoleUID; ?>"><?php echo htmlspecialchars($role->Name); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <!-- Active -->
                                <div class="col-md-6">
                                    <div class="form-check form-switch mt-1">
                                        <input class="form-check-input" type="checkbox" id="UserIsActive" checked>
                                        <label class="form-check-label" for="UserIsActive">Active Account</label>
                                    </div>
                                    <div class="text-muted" style="font-size:.75rem;">Inactive users cannot log in.</div>
                                </div>

                                <!-- Locked (edit-only) -->
                                <div class="col-md-6 d-none" id="userLockedRow">
                                    <div class="form-check form-switch mt-1">
                                        <input class="form-check-input" type="checkbox" id="UserIsLocked">
                                        <label class="form-check-label" for="UserIsLocked">
                                            <span class="text-danger">Lock Account</span>
                                        </label>
                                    </div>
                                    <div class="text-muted" style="font-size:.75rem;">Locked users are blocked from signing in.</div>
                                </div>

                                <!-- Password notice (create-only) -->
                                <div class="col-12" id="pwdSetupInfo">
                                    <div class="alert alert-info py-2 px-3 mb-0" style="font-size:.8rem;line-height:1.5;">
                                        <i class="bx bx-envelope me-1"></i>
                                        A password setup link will be emailed to the user after the account is created.
                                    </div>
                                </div>

                                <!-- Last Login (edit-only) -->
                                <div class="col-12 d-none border-top pt-3" id="lastLoginCard">
                                    <div class="text-muted" style="font-size:.75rem;">Last Login</div>
                                    <div id="lastLoginDisplay" class="fw-semibold" style="font-size:.82rem;">—</div>
                                </div>

                            </div>
                        </div>
                        <!-- /loginAccessSection -->

                    </div>

                    <!-- ══ TAB 6: Branch Access ══════════════════════════════ -->
                    <div class="tab-pane fade" id="tabBranchAccess">
                        <?php if (empty($BranchesList)): ?>
                        <div class="text-muted text-center py-4" style="font-size:.83rem;">
                            <i class="bx bx-buildings me-1"></i>No active branches found. Add branches in <strong>Settings &gt; Branches</strong> first.
                        </div>
                        <?php else: ?>
                        <div class="text-muted mb-3" style="font-size:.78rem;">
                            <i class="bx bx-info-circle me-1"></i>Select which branches this staff member can access. Mark one branch as the default login branch.
                        </div>
                        <div id="branchAccessList">
                            <?php foreach ($BranchesList as $b): ?>
                            <div class="d-flex align-items-center gap-3 py-2 border-bottom branch-access-row" data-branch-uid="<?php echo (int)$b->BranchUID; ?>">
                                <div class="form-check mb-0">
                                    <input class="form-check-input branch-access-chk" type="radio"
                                           id="branchChk_<?php echo (int)$b->BranchUID; ?>"
                                           name="branchAccessRadio"
                                           value="<?php echo (int)$b->BranchUID; ?>">
                                </div>
                                <div class="flex-grow-1">
                                    <label class="form-check-label mb-0" for="branchChk_<?php echo (int)$b->BranchUID; ?>" style="font-size:.85rem; font-weight:500; cursor:pointer;">
                                        <?php echo htmlspecialchars($b->Name); ?>
                                    </label>
                                    <div class="text-muted" style="font-size:.74rem;"><?php echo htmlspecialchars($b->BranchCode); ?></div>
                                </div>
                                <?php if ($b->IsHeadOffice): ?>
                                <span class="badge bg-label-primary" style="font-size:.7rem;">Head Office</span>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- ══ TAB 7: Attachments ════════════════════════════════ -->
                    <div class="tab-pane fade" id="tabAttachments">

                        <!-- Upload row -->
                        <div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
                            <select class="form-select form-select-sm" id="userAttachDocType" style="width:140px;">
                                <option value="ID Proof">ID Proof</option>
                                <option value="Resume">Resume</option>
                                <option value="Certificate">Certificate</option>
                                <option value="Other">Other</option>
                            </select>
                            <label class="btn btn-sm btn-outline-primary mb-0" for="userAttachFile" style="cursor:pointer;">
                                <i class="bx bx-upload me-1"></i>Choose File
                            </label>
                            <input type="file" id="userAttachFile" class="d-none" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx">
                            <span id="userAttachFileName" class="text-muted" style="font-size:.8rem;">No file chosen</span>
                            <button type="button" class="btn btn-sm btn-success d-none" id="btnUploadAttach">
                                <span class="spinner-border spinner-border-sm me-1 d-none" id="uploadAttachSpinner"></span>
                                <i class="bx bx-check me-1" id="uploadAttachIcon"></i>Upload
                            </button>
                        </div>

                        <!-- Existing attachment list -->
                        <div id="userAttachList">
                            <div class="text-muted py-3 text-center" id="userAttachEmpty" style="font-size:.82rem;">
                                <i class="bx bx-paperclip me-1"></i>No attachments yet.
                            </div>
                            <div id="userAttachItems"></div>
                        </div>

                        <div class="text-muted mt-2" style="font-size:.74rem;">Allowed: JPG, PNG, PDF, DOC, DOCX &nbsp;·&nbsp; Max 5 MB per file</div>

                    </div>

                </div><!-- /tab-content -->
            </div><!-- /modal-body -->

        </div>
    </div>
</div>
