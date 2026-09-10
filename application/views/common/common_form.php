<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<style>
/* ── Change Password modal — strength bar + criteria ── */
.cp-strength { display:flex; gap:3px; margin-top:6px; }
.cp-strength-seg { flex:1; height:3px; border-radius:2px; background:rgba(0,0,0,.08); transition:background .3s; }
.cp-strength-seg.cp-weak   { background:#dc3545; }
.cp-strength-seg.cp-fair   { background:#fd7e14; }
.cp-strength-seg.cp-good   { background:#ffc107; }
.cp-strength-seg.cp-strong { background:#198754; }
.cp-strength-lbl { font-size:11px; color:#6c757d; margin-top:2px; }
.cp-criteria { padding:6px 0 0; margin:0; list-style:none; }
.cp-crit { font-size:11.5px; color:#6c757d; display:flex; align-items:center; gap:5px; margin-bottom:2px; transition:color .2s; }
.cp-crit i { font-size:14px; flex-shrink:0; }
.cp-crit.cp-pass { color:#198754; }
.cp-crit.cp-fail { color:#dc3545; }
.cp-match { font-size:11.5px; margin-top:4px; }
.cp-match.cp-ok  { color:#198754; }
.cp-match.cp-err { color:#dc3545; }
</style>

<div class="modal fade" id="ChangePasswordModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="ChangePasswordLabel">
    <div class="modal-dialog">
        <div class="modal-content">

            <div class="vtm-banner" style="--vtm-color:#696cff;--vtm-bg:#eff0ff;--vtm-icon-bg:rgba(105,108,255,.13);">
                <div class="vtm-banner-inner">
                    <div class="vtm-banner-left">
                        <div class="vtm-banner-icon">
                            <i class="bx bx-lock-alt"></i>
                        </div>
                        <div>
                            <div class="vtm-doc-number">Change Password</div>
                            <div class="vtm-doc-meta">Update your account password securely</div>
                        </div>
                    </div>
                    <div class="vtm-banner-right">
                        <button type="button" class="vtm-close-btn" data-bs-dismiss="modal" aria-label="Close">
                            <i class="bx bx-x"></i>
                        </button>
                    </div>
                </div>
            </div>

            <?php $FormAttribute = array('id' => 'ResetPasswordForm', 'name' => 'ResetPasswordForm', 'class' => 'd-flex flex-column justify-content-end h-100', 'autocomplete' => 'off');
            echo form_open('login/resetPassword', $FormAttribute); ?>

            <div class="modal-body">
                <input type="hidden" name="UserUID" id="UserUID" value="<?php echo $JwtData->User->UserUID; ?>" />
                <div class="row mb-3">
                    <div class="col-4">
                        <label for="OldPassword" class="col-form-label">Old Password <span style="color:red">*</span> </label>
                    </div>
                    <div class="col-8">
                        <input type="password" class="form-control" name="OldPassword" id="OldPassword" required maxlength="20" autocomplete="off" placeholder="Old Password">
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-4">
                        <label for="NewPassword" class="col-form-label">New Password <span style="color:red">*</span> </label>
                    </div>
                    <div class="col-8">
                        <input type="password" class="form-control" name="NewPassword" id="NewPassword" required maxlength="64" autocomplete="off" placeholder="New Password">
                        <div class="cp-strength" id="cpStrengthBar">
                            <div class="cp-strength-seg" id="cpSeg1"></div>
                            <div class="cp-strength-seg" id="cpSeg2"></div>
                            <div class="cp-strength-seg" id="cpSeg3"></div>
                            <div class="cp-strength-seg" id="cpSeg4"></div>
                        </div>
                        <div class="cp-strength-lbl" id="cpStrengthLbl"></div>
                        <ul class="cp-criteria" id="cpCriteria" hidden>
                            <li class="cp-crit" id="cpCritLen"><i class="bx bx-minus"></i>Min 8 characters, max 64</li>
                            <li class="cp-crit" id="cpCritUpper"><i class="bx bx-minus"></i>At least 1 uppercase letter (A–Z)</li>
                            <li class="cp-crit" id="cpCritLower"><i class="bx bx-minus"></i>At least 1 lowercase letter (a–z)</li>
                            <li class="cp-crit" id="cpCritNum"><i class="bx bx-minus"></i>At least 1 number (0–9)</li>
                            <li class="cp-crit" id="cpCritSpecial"><i class="bx bx-minus"></i>At least 1 special character (!&nbsp;@&nbsp;#&nbsp;$&nbsp;%&nbsp;^&nbsp;&amp;&nbsp;*)</li>
                        </ul>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-4">
                        <label for="ConfirmPassword" class="col-form-label">Confirm Password <span style="color:red">*</span> </label>
                    </div>
                    <div class="col-8">
                        <input type="password" class="form-control" name="ConfirmPassword" id="ConfirmPassword" required maxlength="64" autocomplete="off" placeholder="Confirm Password">
                        <div class="cp-match" id="cpMatch"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Discard</button>
                <button type="submit" id="ResetPasswordSubBtn" class="btn btn-primary">Reset</button>
            </div>

            <?php echo form_close(); ?>

        </div>
    </div>
</div>

<script>
(function () {
    var pwInp   = document.getElementById('NewPassword');
    var cfInp   = document.getElementById('ConfirmPassword');
    var oldInp  = document.getElementById('OldPassword');
    var saveBtn = document.getElementById('ResetPasswordSubBtn');
    var matchEl = document.getElementById('cpMatch');
    var critEl  = document.getElementById('cpCriteria');
    var lblEl   = document.getElementById('cpStrengthLbl');

    var colors = ['', 'cp-weak', 'cp-fair', 'cp-good', 'cp-strong'];
    var labels = ['', 'Weak',   'Fair',   'Good',   'Strong'];

    function setCrit(id, pass) {
        var el = document.getElementById(id);
        if (!el) return;
        var ic = el.querySelector('i');
        if (pass === null) {
            el.className = 'cp-crit';
            if (ic) ic.className = 'bx bx-minus';
        } else if (pass) {
            el.className = 'cp-crit cp-pass';
            if (ic) ic.className = 'bx bx-check';
        } else {
            el.className = 'cp-crit cp-fail';
            if (ic) ic.className = 'bx bx-x';
        }
    }

    function calcScore(pw) {
        var s = 0;
        if (pw.length >= 8 && pw.length <= 64) s++;
        if (/[A-Z]/.test(pw))                  s++;
        if (/[a-z]/.test(pw))                  s++;
        if (/[0-9]/.test(pw))                  s++;
        if (/[!@#$%^&*]/.test(pw))             s++;
        return s;
    }

    function allPass(pw) {
        return pw.length >= 8 && pw.length <= 64 &&
               /[A-Z]/.test(pw) && /[a-z]/.test(pw) &&
               /[0-9]/.test(pw) && /[!@#$%^&*]/.test(pw);
    }

    function updateStrength() {
        var pw  = pwInp ? pwInp.value : '';
        var raw = pw ? calcScore(pw) : 0;
        var s   = raw === 0 ? 0 : raw <= 1 ? 1 : raw <= 2 ? 2 : raw <= 3 ? 3 : 4;

        for (var i = 1; i <= 4; i++) {
            var seg = document.getElementById('cpSeg' + i);
            if (seg) seg.className = 'cp-strength-seg' + (i <= s ? ' ' + colors[s] : '');
        }
        if (lblEl) lblEl.textContent = pw ? labels[s] : '';

        if (critEl) {
            critEl.hidden = !pw;
            if (pw) {
                setCrit('cpCritLen',     pw.length >= 8 && pw.length <= 64);
                setCrit('cpCritUpper',   /[A-Z]/.test(pw));
                setCrit('cpCritLower',   /[a-z]/.test(pw));
                setCrit('cpCritNum',     /[0-9]/.test(pw));
                setCrit('cpCritSpecial', /[!@#$%^&*]/.test(pw));
            } else {
                ['cpCritLen','cpCritUpper','cpCritLower','cpCritNum','cpCritSpecial']
                    .forEach(function (id) { setCrit(id, null); });
            }
        }
    }

    function updateMatch() {
        var pw = pwInp ? pwInp.value : '';
        var cf = cfInp ? cfInp.value : '';
        if (!matchEl) return;
        if (!cf) { matchEl.textContent = ''; matchEl.className = 'cp-match'; return; }
        if (pw === cf) {
            matchEl.className = 'cp-match cp-ok';
            matchEl.innerHTML = '<i class="bx bx-check"></i> Passwords match';
        } else {
            matchEl.className = 'cp-match cp-err';
            matchEl.innerHTML = '<i class="bx bx-x"></i> Passwords do not match';
        }
    }

    function syncBtn() {
        if (!saveBtn) return;
        var pw  = pwInp  ? pwInp.value  : '';
        var cf  = cfInp  ? cfInp.value  : '';
        var old = oldInp ? oldInp.value : '';
        saveBtn.disabled = !(old && allPass(pw) && pw === cf);
    }

    function resetCpForm() {
        if (pwInp)  pwInp.value  = '';
        if (cfInp)  cfInp.value  = '';
        if (oldInp) oldInp.value = '';
        for (var i = 1; i <= 4; i++) {
            var seg = document.getElementById('cpSeg' + i);
            if (seg) seg.className = 'cp-strength-seg';
        }
        if (lblEl)   lblEl.textContent   = '';
        if (critEl)  critEl.hidden        = true;
        if (matchEl) { matchEl.textContent = ''; matchEl.className = 'cp-match'; }
        ['cpCritLen','cpCritUpper','cpCritLower','cpCritNum','cpCritSpecial']
            .forEach(function (id) { setCrit(id, null); });
        if (saveBtn) saveBtn.disabled = true;
    }

    if (pwInp)  pwInp.addEventListener('input',  function () { updateStrength(); updateMatch(); syncBtn(); });
    if (cfInp)  cfInp.addEventListener('input',  function () { updateMatch(); syncBtn(); });
    if (oldInp) oldInp.addEventListener('input',  syncBtn);

    var modal = document.getElementById('ChangePasswordModal');
    if (modal) {
        modal.addEventListener('hidden.bs.modal', resetCpForm);
        modal.addEventListener('show.bs.modal',   syncBtn);
    }

    /* Initial state — button disabled until criteria pass */
    if (saveBtn) saveBtn.disabled = true;
})();
</script>

<!-- Page Selection Modal -->
<div class="modal fade" id="selectPagesModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="selectPagesModal">
    <div class="modal-dialog"> <!-- modal-dialog-centered -->
        <div class="modal-content border-0 shadow-lg">

            <div class="modal-header">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body text-center py-5 mb-5">
                <div class="text-primary mb-3">
                    <i class="bx bx-select-multiple bx-lg"></i>
                </div>
                <h4 class="mb-3">Bulk Selection</h4>
                <p class="mb-4 px-3">
                    Do you want to select items <strong>only on this page</strong> or <strong>across all pages</strong>?
                </p>

                <div class="d-grid gap-2 col-10 mx-auto">
                    <button type="button" class="btn btn-outline-primary btn-lg" id="selectThisPageBtn">
                        <i class="bx bx-grid-small me-2"></i> This Page Only
                    </button>
                    <button type="button" class="btn btn-primary btn-lg" id="selectAllPagesBtn">
                        <i class="bx bx-layer me-2"></i> All Pages
                    </button>
                    <button type="button" class="btn btn-outline-danger btn-lg" id="clearSelectAllClose">
                        <i class="bx bx-reset me-2"></i> Clear & Close
                    </button>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Unselect Items Modal -->
<div class="modal fade" id="unSelectPagesModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="unSelectPagesModal">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow-lg">

            <div class="modal-header">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body text-center py-5">
                <div class="text-danger mb-3">
                    <i class="bx bx-unlink bx-lg"></i>
                </div>
                <h4 class="mb-3">Cancel Bulk Selection</h4>
                <p class="mb-4 px-3">
                    Do you want to unselect items <strong>only on this page</strong> or <strong>across all pages</strong>?
                </p>
                <div class="d-grid gap-2 col-10 mx-auto">
                    <button type="button" class="btn btn-outline-primary btn-lg" id="unselectThisPageBtn">
                        <i class="bx bx-grid-small me-2"></i> This Page Only
                    </button>
                    <button type="button" class="btn btn-primary btn-lg" id="unselectAllPagesBtn">
                        <i class="bx bx-layer me-2"></i> All Pages
                    </button>
                </div>
            </div>

        </div>
    </div>
</div>

<?php $this->load->view('transactions/modals/customer_search'); ?>
<?php $this->load->view('transactions/modals/product_search'); ?>

<!-- Export modal -->
<div class="modal fade" id="exportPagesModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="exportPagesModal">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow-lg">

            <div class="modal-header">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body text-center py-5">
                <div class="text-danger mb-3">
                    <i class="bx bx-unlink bx-lg"></i>
                </div>
                <h4 class="mb-3">Export Bulk Selection</h4>
                <p class="mb-4 px-3">
                    Do you want to select items <span id="exportThisPageCnt"><strong>only on this page</strong>,</span> <span id="exportSelectedItemsCnt"><strong>only selected items</strong>, </span> or <strong>across all pages</strong>?
                </p>
                <div class="d-grid gap-2 col-10 mx-auto">
                    <button type="button" class="btn btn-outline-warning btn-lg" id="exportSelectedItemsBtn">
                        <i class="bx bx-check-square me-2"></i> Selected Items Only
                    </button>
                    <button type="button" class="btn btn-outline-primary btn-lg" id="exportThisPageBtn">
                        <i class="bx bx-grid-small me-2"></i> This Page Only
                    </button>
                    <button type="button" class="btn btn-outline-success btn-lg" id="exportAllPagesBtn">
                        <i class="bx bx-layer me-2"></i> All Pages
                    </button>
                    <button type="button" class="btn btn-outline-danger btn-lg" id="clearExportClose">
                        <i class="bx bx-reset me-2"></i> Clear & Close
                    </button>
                </div>
            </div>

        </div>
    </div>
</div>