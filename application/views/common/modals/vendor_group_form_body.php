<?php
defined('BASEPATH') or exit('No direct script access allowed');
/*
 * Self-contained Vendor Group modal form body.
 * Loaded by vendor_group_form.php — no external variables required.
 * Reads org data directly from CI instance.
 */
$CI        =& get_instance();
$_orgCCode = $CI->pageData['JwtData']->Org->OrgCCode ?? '+91';
$_orgCISO2 = $CI->pageData['JwtData']->Org->OrgCISO2 ?? 'IN';
?>

<form id="VGroupModalForm" data-mode="add" autocomplete="off" novalidate>
    <input type="hidden" id="VGroupUID"          name="GroupUID"          value="">
    <input type="hidden" id="VG_CountryISO2"                              value="<?php echo htmlspecialchars($_orgCISO2); ?>">
    <input type="hidden" name="MobileCountryCode" id="VG_MobileCountryCode" value="<?php echo htmlspecialchars($_orgCCode); ?>">
    <!-- Address hidden fields — populated by address modal save -->
    <input type="hidden" name="AddrLine1"    id="VG_AddrLine1"    value="">
    <input type="hidden" name="AddrLine2"    id="VG_AddrLine2"    value="">
    <input type="hidden" name="AddrPincode"  id="VG_AddrPincode"  value="">
    <input type="hidden" name="AddrState"    id="VG_AddrState"    value="">
    <input type="hidden" name="AddrStateCode" id="VG_AddrStateCode" value="">
    <input type="hidden" name="AddrCity"     id="VG_AddrCity"     value="">

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
                    <option value="">Loading...</option>
                </select>
            </div>

            <div class="mb-3 col-md-4">
                <label class="form-label">Contact Person</label>
                <input type="text" class="form-control" id="VG_ContactPerson" name="ContactPerson"
                       maxlength="150" placeholder="Name">
            </div>

            <!-- Mobile with searchable country-code dropdown -->
            <div class="mb-3 col-md-4">
                <label class="form-label">Mobile</label>
                <div class="input-group position-relative">
                    <button type="button" class="btn btn-outline-secondary fw-semibold flex-shrink-0"
                            id="VG_MobileCCBtn" tabindex="-1"
                            style="border-top-right-radius:0;border-bottom-right-radius:0;min-width:60px;">
                        <?php echo htmlspecialchars($_orgCCode); ?>
                    </button>
                    <!-- Country-code dropdown panel -->
                    <div id="VG_CCDropdown"
                         style="display:none;position:absolute;top:100%;left:0;min-width:270px;z-index:9999;
                                background:#fff;border:1px solid #dee2e6;border-radius:4px;
                                box-shadow:0 4px 14px rgba(0,0,0,.12);">
                        <div class="p-2 border-bottom">
                            <input type="text" class="form-control form-control-sm" id="VG_CCSearch"
                                   placeholder="Search country..." autocomplete="off">
                        </div>
                        <div id="VG_CCList" style="max-height:180px;overflow-y:auto;"></div>
                    </div>
                    <input type="text" class="form-control" id="VG_Mobile" name="Mobile"
                           maxlength="15" placeholder="9999 000 000"
                           onkeypress="return (event.charCode!=8 && event.charCode==0 || (event.charCode>=48 && event.charCode<=57))"
                           oninput="this.value=this.value.slice(0,this.maxLength)">
                </div>
                <div class="text-danger small mt-1" id="VG_MobileErr" style="display:none;">Enter a valid mobile number (min 7 digits).</div>
            </div>

            <div class="mb-3 col-md-4">
                <label class="form-label">Email</label>
                <input type="email" class="form-control" id="VG_Email" name="Email"
                       maxlength="150" placeholder="email@example.com">
                <div class="invalid-feedback">Enter a valid email address.</div>
            </div>

            <div class="mb-3 col-md-6">
                <label class="form-label">GST No</label>
                <div class="input-group">
                    <input type="text" class="form-control" id="VG_GSTNo" name="GSTNo"
                           maxlength="20" placeholder="27XXXXX...">
                    <button type="button" class="btn btn-outline-secondary" id="VG_GSTFetchBtn">Fetch</button>
                </div>
            </div>

            <!-- Address click-box — opens address modal -->
            <div class="mb-3 col-md-12">
                <label class="form-label">Address</label>
                <div id="VG_AddrBox" class="form-control"
                     style="cursor:pointer;min-height:58px;display:flex;align-items:center;font-size:.875rem;line-height:1.4;user-select:none;">
                    <span style="color:#adb5bd;"><i class="bx bx-map-pin me-1"></i>Click to add address...</span>
                </div>
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
                <div class="position-relative">
                    <input type="text" class="form-control" id="VG_MemberSearch"
                           placeholder="Search &amp; add vendor..." autocomplete="off">
                    <div id="VG_MemberDropdown"
                         style="display:none;position:absolute;top:100%;left:0;width:100%;max-height:220px;
                                overflow-y:auto;z-index:1055;background:#fff;border:1px solid #dee2e6;
                                border-radius:4px;box-shadow:0 4px 8px rgba(0,0,0,.1);margin-top:2px;">
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <button type="button" class="btn btn-outline-primary w-100" id="VG_BtnAddMember">
                    <i class="bx bx-user-plus me-1"></i>Add
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
