<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<!-- ── Create / Edit User Modal ─────────────────────────────────────── -->
<div class="modal fade" id="userModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">

            <div class="modal-header py-2 border-bottom">
                <h6 class="modal-title mb-0" id="userModalTitle">
                    <i class="bx bx-user me-1"></i>Add User
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body p-3">
                <input type="hidden" id="UserModalUID" value="0">

                <div class="row g-3">

                    <!-- First Name -->
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold mb-1">First Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm" id="UserFirstName" maxlength="100" placeholder="First name">
                    </div>

                    <!-- Last Name -->
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold mb-1">Last Name</label>
                        <input type="text" class="form-control form-control-sm" id="UserLastName" maxlength="100" placeholder="Last name">
                    </div>

                    <!-- Username -->
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold mb-1">Username <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm" id="UserUsername" maxlength="100" placeholder="e.g. john.doe" autocomplete="off">
                    </div>

                    <!-- Email -->
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold mb-1">Email Address <span class="text-danger">*</span></label>
                        <input type="email" class="form-control form-control-sm" id="UserEmail" maxlength="200" placeholder="email@example.com">
                    </div>

                    <!-- Mobile -->
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold mb-1">Mobile Number</label>
                        <input type="text" class="form-control form-control-sm" id="UserMobile" maxlength="20" placeholder="+91 99999 99999">
                    </div>

                    <!-- Role -->
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold mb-1">Role <span class="text-danger">*</span></label>
                        <select class="form-select form-select-sm" id="UserRoleUID">
                            <option value="">-- Select Role --</option>
                            <?php foreach ($RolesList as $role): ?>
                            <option value="<?php echo $role->RoleUID; ?>"><?php echo htmlspecialchars($role->Name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Password (only shown for new users) -->
                    <div class="col-md-6" id="passwordGroup">
                        <label class="form-label small fw-semibold mb-1">Password <span class="text-danger">*</span></label>
                        <div class="input-group input-group-sm">
                            <input type="password" class="form-control form-control-sm" id="UserPassword" maxlength="100" placeholder="Password" autocomplete="new-password">
                            <button class="btn btn-outline-secondary" type="button" id="togglePwd" tabindex="-1">
                                <i class="bx bx-hide"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Confirm Password -->
                    <div class="col-md-6" id="confirmPasswordGroup">
                        <label class="form-label small fw-semibold mb-1">Confirm Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control form-control-sm" id="UserConfirmPassword" maxlength="100" placeholder="Confirm password">
                    </div>

                    <!-- Status toggle (edit mode only) -->
                    <div class="col-12 d-none" id="userStatusRow">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="UserIsActive" checked>
                            <label class="form-check-label small" for="UserIsActive">Active</label>
                        </div>
                    </div>

                </div><!-- /row -->
            </div>

            <div class="modal-footer py-2 border-top">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary btn-sm px-4" id="saveUserBtn">
                    <span class="spinner-border spinner-border-sm me-1 d-none" id="saveUserSpinner"></span>
                    Save User
                </button>
            </div>

        </div>
    </div>
</div>
