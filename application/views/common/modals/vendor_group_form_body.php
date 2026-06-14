<?php
defined('BASEPATH') or exit('No direct script access allowed');
/*
 * Self-contained Vendor Group modal form body.
 * Loaded by vendor_group_form.php — no external variables required.
 * Reads org data directly from CI instance.
 */
$CI        =& get_instance();
$_orgCCode = $CI->pageData['JwtData']->Org->OrgCCode  ?? '+91';
$_orgCISO2 = $CI->pageData['JwtData']->Org->OrgCISO2  ?? 'IN';

$_groupTypes = [
    'Business Group', 'Branch Group', 'Family Group',
    'Corporate Group', 'Dealer Network', 'Franchise Group', 'Custom',
];
?>

<form id="VGroupModalForm" data-mode="add" autocomplete="off" novalidate>
    <input type="hidden" id="VGroupUID"      name="GroupUID" value="">
    <input type="hidden" id="VG_CountryISO2" value="<?php echo htmlspecialchars($_orgCISO2); ?>">

    <div class="p-4">

        <!-- ── Group Details ──────────────────────────────────── -->
        <div class="card-header modal-header-center-sticky p-1 mb-3">
            <h5 class="modal-title mb-0">Group Details</h5>
        </div>
        <div class="row">

            <div class="mb-3 col-md-5">
                <label class="form-label">Group Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="VG_GroupName" name="GroupName"
                       maxlength="200" placeholder="e.g. ABC Suppliers Group" required>
                <div class="invalid-feedback">Group name is required.</div>
            </div>
            <div class="mb-3 col-md-3">
                <label class="form-label">Group Code</label>
                <input type="text" class="form-control" id="VG_GroupCode" name="GroupCode"
                       maxlength="50" placeholder="e.g. ABC-GRP">
            </div>
            <div class="mb-3 col-md-4">
                <label class="form-label">Group Type</label>
                <select class="form-select" id="VG_GroupType" name="GroupType">
                    <?php foreach ($_groupTypes as $t): ?>
                    <option value="<?php echo htmlspecialchars($t); ?>"><?php echo htmlspecialchars($t); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3 col-md-4">
                <label class="form-label">Contact Person</label>
                <input type="text" class="form-control" id="VG_ContactPerson" name="ContactPerson"
                       maxlength="150" placeholder="Name">
            </div>
            <div class="mb-3 col-md-4">
                <label class="form-label">Mobile</label>
                <div class="input-group">
                    <span class="input-group-text fw-semibold" id="VG_MobilePrefix"><?php echo htmlspecialchars($_orgCCode); ?></span>
                    <input type="hidden" name="MobileCountryCode" id="VG_MobileCountryCode" value="<?php echo htmlspecialchars($_orgCCode); ?>">
                    <input type="text" class="form-control" id="VG_Mobile" name="Mobile"
                           maxlength="15" placeholder="9999 000 000"
                           onkeypress="return (event.charCode!=8 && event.charCode==0 || (event.charCode>=48 && event.charCode<=57))"
                           oninput="this.value=this.value.slice(0,this.maxLength)">
                </div>
            </div>
            <div class="mb-3 col-md-4">
                <label class="form-label">Email</label>
                <input type="email" class="form-control" id="VG_Email" name="Email"
                       maxlength="150" placeholder="email@example.com">
            </div>

            <div class="mb-3 col-md-3">
                <label class="form-label">GST No</label>
                <input type="text" class="form-control" id="VG_GSTNo" name="GSTNo"
                       maxlength="20" placeholder="27XXXXX...">
            </div>
            <div class="mb-3 col-md-3">
                <label class="form-label">Country</label>
                <input type="text" class="form-control bg-body-secondary" id="VG_Country" name="Country"
                       maxlength="100" readonly placeholder="Loading...">
            </div>
            <div class="mb-3 col-md-3">
                <label class="form-label">State</label>
                <select class="form-select" id="VG_State" name="State">
                    <option value="">-- Select State --</option>
                </select>
            </div>
            <div class="mb-3 col-md-3">
                <label class="form-label">City</label>
                <select class="form-select" id="VG_City" name="City">
                    <option value="">-- Select City --</option>
                </select>
            </div>

            <div class="mb-3 col-md-12">
                <label class="form-label">Address</label>
                <textarea class="form-control" id="VG_Address" name="Address" rows="2"
                          placeholder="Group head office address"></textarea>
            </div>
            <div class="mb-3 col-md-12">
                <label class="form-label">Notes</label>
                <textarea class="form-control" id="VG_Notes" name="Notes" rows="2"
                          placeholder="Internal notes about this group"></textarea>
            </div>

        </div>

        <hr>

        <!-- ── Group Members ──────────────────────────────────── -->
        <div class="card-header modal-header-center-sticky p-1 mb-3 d-flex align-items-center justify-content-between">
            <h5 class="modal-title mb-0">Group Members</h5>
            <small class="text-muted">
                <i class="bx bx-star" style="color:#f59e0b;"></i> marks primary contact
            </small>
        </div>

        <div class="row g-2 mb-3">
            <div class="col-md-9">
                <select id="VG_MemberSearch" class="form-select" style="width:100%;">
                    <option value="">Search &amp; add vendor...</option>
                </select>
            </div>
            <div class="col-md-3">
                <button type="button" class="btn btn-outline-primary w-100" id="VG_BtnAddMember">
                    <i class="bx bx-user-plus me-1"></i>Add to Group
                </button>
            </div>
        </div>

        <div id="VG_MemberInputs"></div>

        <div id="VG_MembersBox">
            <div class="text-center py-4 text-muted">
                <i class="bx bx-user-plus fs-2 d-block mb-2"></i>
                <div style="font-size:.85rem;">No members yet. Search and add vendors above.</div>
            </div>
        </div>

    </div><!-- /p-4 -->

</form>
