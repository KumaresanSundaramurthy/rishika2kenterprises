<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php if (!isset($RolesList)) { $RolesList = []; } ?>

<div class="modal fade" id="userModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-xl">
        <div class="modal-content">

            <!-- Header -->
            <div class="modal-header bg-white border-bottom d-flex align-items-center justify-content-between px-3 py-2 trans-theme">
                <div class="d-flex align-items-center gap-2">
                    <div class="modal-doc-icon" style="background:#ede9fe;">
                        <i class="bx bx-user modal-doc-icon-inner" style="color:#7c3aed;"></i>
                    </div>
                    <h5 class="modal-title mb-0" id="userModalTitle">Add User</h5>
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

            <!-- Body -->
            <div class="modal-body p-4">
                <div class="row g-4">

                    <!-- ══ LEFT: Personal Info + Address ══════════════════════ -->
                    <div class="col-lg-8">

                        <!-- Personal Information -->
                        <h6 class="fw-semibold mb-1" style="font-size:.88rem;">Personal Information</h6>
                        <hr class="mt-1 mb-3">

                        <div class="row g-3">

                            <!-- User Code (edit-only) -->
                            <div class="col-md-4 d-none" id="userCodeWrap">
                                <label class="form-label" style="font-size:.83rem;">User Code</label>
                                <input type="text" class="form-control form-control-sm bg-light" id="UserCodeDisplay" readonly placeholder="Auto-generated">
                            </div>

                            <!-- First Name -->
                            <div class="col-md-4" id="firstNameCol">
                                <label class="form-label" style="font-size:.83rem;">First Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-sm" id="UserFirstName" maxlength="100" placeholder="First name" autocomplete="off">
                            </div>

                            <!-- Last Name -->
                            <div class="col-md-4">
                                <label class="form-label" style="font-size:.83rem;">Last Name</label>
                                <input type="text" class="form-control form-control-sm" id="UserLastName" maxlength="100" placeholder="Last name" autocomplete="off">
                            </div>

                            <!-- Username -->
                            <div class="col-md-6">
                                <label class="form-label" style="font-size:.83rem;">Username <span class="text-danger">*</span></label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text text-muted">@</span>
                                    <input type="text" class="form-control" id="UserUsername" maxlength="100" placeholder="e.g. john.doe" autocomplete="off">
                                </div>
                            </div>

                            <!-- Email -->
                            <div class="col-md-6">
                                <label class="form-label" style="font-size:.83rem;">Email Address <span class="text-danger">*</span></label>
                                <input type="email" class="form-control form-control-sm" id="UserEmail" maxlength="200" placeholder="email@example.com" autocomplete="off">
                            </div>

                            <!-- Mobile Number with country code -->
                            <div class="col-md-6">
                                <label class="form-label" style="font-size:.83rem;">Mobile Number</label>
                                <div class="input-group input-group-sm">
                                    <input type="text" class="form-control" id="UserCountryCode" style="max-width:72px;" placeholder="+91" maxlength="6" value="+91">
                                    <input type="text" class="form-control" id="UserMobile" maxlength="15" placeholder="98765 43210">
                                </div>
                                <input type="hidden" id="UserCountryISO2" value="IN">
                            </div>

                        </div><!-- /row personal -->

                        <!-- ── Address Details ──────────────────────────────── -->
                        <h6 class="fw-semibold mb-1 mt-4" style="font-size:.88rem;">Address Details</h6>
                        <hr class="mt-1 mb-3">

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

                    </div><!-- /col-lg-8 -->

                    <!-- ══ RIGHT: Account Settings ════════════════════════════ -->
                    <div class="col-lg-4">

                        <h6 class="fw-semibold mb-1" style="font-size:.88rem;">Account Settings</h6>
                        <hr class="mt-1 mb-3">

                        <!-- Role -->
                        <div class="mb-3">
                            <label class="form-label" style="font-size:.83rem;">Role <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm" id="UserRoleUID">
                                <option value="">— Select Role —</option>
                                <?php foreach ($RolesList as $role): ?>
                                <?php if (strtolower(trim($role->Name)) === 'super admin') continue; ?>
                                <option value="<?php echo (int)$role->RoleUID; ?>"><?php echo htmlspecialchars($role->Name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Active -->
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="UserIsActive" checked>
                                <label class="form-check-label" style="font-size:.83rem;" for="UserIsActive">Active Account</label>
                            </div>
                            <div class="text-muted" style="font-size:.75rem;">Inactive users cannot log in.</div>
                        </div>

                        <!-- Locked (edit only) -->
                        <div class="mb-3 d-none" id="userLockedRow">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="UserIsLocked">
                                <label class="form-check-label" style="font-size:.83rem;" for="UserIsLocked">
                                    <span class="text-danger">Lock Account</span>
                                </label>
                            </div>
                            <div class="text-muted" style="font-size:.75rem;">Locked users are blocked from signing in.</div>
                        </div>

                        <!-- Password setup notice (create only) -->
                        <div id="pwdSetupInfo">
                            <div class="alert alert-info py-2 px-3 mb-0" style="font-size:.8rem;line-height:1.5;">
                                <i class="bx bx-envelope me-1"></i>
                                A password setup link will be emailed to the user after the account is created.
                            </div>
                        </div>

                        <!-- Last Login (edit only) -->
                        <div class="d-none mt-3 pt-2 border-top" id="lastLoginCard">
                            <div class="text-muted" style="font-size:.75rem;">Last Login</div>
                            <div id="lastLoginDisplay" class="fw-semibold" style="font-size:.82rem;">—</div>
                        </div>

                    </div><!-- /col-lg-4 -->

                </div><!-- /row -->
            </div><!-- /modal-body -->

        </div>
    </div>
</div>
