<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php $this->load->view('common/header'); ?>
<link rel="stylesheet" href="/css/transactions-theme.css">

<!-- Layout wrapper -->
<div class="layout-wrapper layout-horizontal layout-content-navbar">
    <div class="layout-container">

        <?php $this->load->view('common/menu_view'); ?>

        <!-- Layout container -->
        <div class="layout-page">

            <!-- Content wrapper -->
            <div class="content-wrapper apex-content">
                <?php $this->load->view('common/apex/page_header', [
                    'pageIcon'        => 'bx-user-circle',
                    'pageIconBg'      => '#ede9fe',
                    'pageIconColor'   => '#7c3aed',
                    'pageTitle'       => $PageTitle       ?? 'Profile',
                    'pageDescription' => $PageDescription ?? '',
                ]); ?>
                <div class="container-xxl flex-grow-1 container-p-y">

                    <div class="d-flex justify-content-end mb-3" id="profileHeaderActions">
                        <button type="button" class="btn btn-primary" id="btnUpdateProfile">
                            <i class="bx bx-save me-1"></i>Update
                        </button>
                    </div>

                    <div class="card">

                        <!-- ── Tab Nav ── -->
                        <div class="border-bottom px-3 d-flex align-items-center justify-content-between">
                            <ul class="nav nav-tabs border-0" id="profileTabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active py-3" id="tab-profile"
                                            data-bs-toggle="tab" data-bs-target="#pane-profile"
                                            type="button" role="tab">
                                        <i class="bx bx-user me-1"></i>Profile
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link py-3" id="tab-signatures"
                                            data-bs-toggle="tab" data-bs-target="#pane-signatures"
                                            type="button" role="tab">
                                        <i class="bx bx-pen me-1"></i>Signatures
                                    </button>
                                </li>
                            </ul>
                            <a href="javascript:void(0);" class="btn btn-outline-secondary btn-sm px-3 ChangePasswordBtn" id="btnChangePassword">
                                <i class="bx bx-lock-alt me-1"></i>Change Password
                            </a>
                        </div>

                        <!-- ── Tab Content ── -->
                        <div class="tab-content">

                            <!-- ════ Profile Tab ════ -->
                            <div class="tab-pane fade show active" id="pane-profile" role="tabpanel">
                                <?php $FormAttribute = array('id' => 'profileForm', 'name' => 'profileForm', 'class' => '', 'autocomplete' => 'off');
                                    echo form_open('profile/updateProfile', $FormAttribute); ?>

                                <input type="hidden" name="userUid" id="HuserUid" value="<?php echo isset($userInfo->UserUID) ? $userInfo->UserUID : ''; ?>" />

                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-3 d-flex justify-content-center align-items-center">
                                            <div class="dropzone dropzone-main-form needsclick p-3 dz-clickable w-100" id="DropzoneOneBasic">
                                                <div class="dz-message needsclick text-center">
                                                    <i class="upload-icon mb-3"></i>
                                                    <p class="h4 needsclick mb-2">Drag and drop your image here</p>
                                                    <p class="h6 text-body-secondary fw-normal mb-0">Allowed JPG, GIF or PNG of 1 MB</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-9 d-flex flex-column justify-content-center">
                                            <div class="row">
                                                <div class="mb-3 col-md-6">
                                                    <label for="fistName" class="form-label">First Name <span style="color:red">*</span></label>
                                                    <input class="form-control" type="text" id="fistName" name="fistName" placeholder="First Name" value="<?php echo isset($userInfo->UserFirstName) ? $userInfo->UserFirstName : ''; ?>" maxlength="100" required />
                                                </div>
                                                <div class="mb-3 col-md-6">
                                                    <label for="lastName" class="form-label">Last Name</label>
                                                    <input class="form-control" type="text" id="lastName" name="lastName" maxlength="100" placeholder="Last Name" value="<?php echo isset($userInfo->UserLastName) ? $userInfo->UserLastName : ''; ?>" />
                                                </div>
                                                <div class="mb-3 col-md-6">
                                                    <label for="userName" class="form-label">User Name <span style="color:red">*</span></label>
                                                    <input class="form-control" type="text" id="userName" name="userName" maxlength="100" disabled placeholder="User Name" value="<?php echo isset($userInfo->UserName) ? $userInfo->UserName : ''; ?>" />
                                                </div>
                                                <div class="mb-3 col-md-6">
                                                    <label for="emailAddress" class="form-label">Email <span style="color:red">*</span></label>
                                                    <input class="form-control" type="email" id="emailAddress" name="emailAddress" required disabled maxlength="100" placeholder="Email Address" value="<?php echo isset($userInfo->UserEmailAddress) ? $userInfo->UserEmailAddress : ''; ?>" />
                                                </div>
                                                <div class="mb-3 col-md-6">
                                                    <label class="form-label" for="MobileNumber">Mobile Number <span style="color:red">*</span></label>
                                                    <div class="d-flex gap-2">
                                                        <select id="CountryCode" name="CountryCode" class="select2 form-select">
                                                            <option label="-- Select Country Code --"></option>
                                                            <?php if (sizeof($CountryInfo) > 0) {
                                                                foreach ($CountryInfo as $Country) { ?>
                                                                    <option
                                                                        value="<?php echo $Country->phone[0]; ?>"
                                                                        data-region="<?php echo $Country->region; ?>"
                                                                        data-ccode="<?php echo $Country->iso->{'alpha-2'}; ?>"
                                                                        <?php echo ($Country->phone[0] == $userInfo->UserCountryCode) ? 'selected' : ''; ?>>
                                                                        <?php echo '(' . $Country->phone[0] . ') ' . $Country->name; ?>
                                                                    </option>
                                                            <?php }
                                                            } ?>
                                                        </select>
                                                        <input type="number" id="MobileNumber" name="MobileNumber" class="form-control" placeholder="9790 000 0000" maxLength="20" onkeypress="return (event.charCode !=8 && event.charCode ==0 || (event.charCode >= 48 && event.charCode <= 57))" oninput="this.value=this.value.slice(0,this.maxLength)" required pattern="[0-9]*" value="<?php echo isset($userInfo->UserMobileNumber) ? $userInfo->UserMobileNumber : ''; ?>" />
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="card-body pt-0">
                                    <div class="card border mb-0">
                                        <div class="card-header modal-header-center-sticky d-flex justify-content-between align-items-center">
                                            <h6 class="mb-0">Change Password <span class="text-muted fw-normal" style="font-size:0.8rem;">(Optional)</span></h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="mb-3 col-md-4">
                                                    <label for="oldPassword" class="col-form-label">Old Password <span style="color:red">*</span></label>
                                                    <input type="password" class="form-control" name="oldPassword" id="oldPassword" maxlength="20" autocomplete="off" placeholder="Old Password">
                                                </div>
                                                <div class="mb-3 col-md-4">
                                                    <label for="newPassword" class="col-form-label">New Password <span style="color:red">*</span></label>
                                                    <input type="password" class="form-control" name="newPassword" id="newPassword" maxlength="20" autocomplete="off" placeholder="New Password">
                                                </div>
                                                <div class="mb-3 col-md-4">
                                                    <label for="confirmPassword" class="col-form-label">Confirm Password <span style="color:red">*</span></label>
                                                    <input type="password" class="form-control" name="confirmPassword" id="confirmPassword" maxlength="20" autocomplete="off" placeholder="Confirm Password">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <?php echo form_close(); ?>
                            </div>
                            <!-- / Profile Tab -->

                            <!-- ════ Signatures Tab ════ -->
                            <div class="tab-pane fade" id="pane-signatures" role="tabpanel">

                                <!-- Signatures action bar -->
                                <div class="d-flex justify-content-between align-items-center px-3 py-3 border-bottom gap-2 flex-wrap">
                                    <span class="text-muted small">Your digital signatures — used on invoices and documents. Only one can be set as default.</span>
                                    <button class="btn btn-primary btn-sm px-3" id="btnAddSignature">
                                        <i class="bx bx-plus me-1"></i>Add Signature
                                    </button>
                                </div>

                                <!-- Signature cards container (lazy loaded) -->
                                <div id="signaturesContainer">
                                    <div class="text-center py-5 text-muted">
                                        <span class="spinner-border spinner-border-sm me-2"></span>Loading signatures...
                                    </div>
                                </div>

                            </div>
                            <!-- / Signatures Tab -->

                        </div>
                        <!-- / Tab Content -->

                    </div>

                </div>
            </div>
            <!-- / Content wrapper -->

            <?php $this->load->view('common/footer_desc'); ?>

        </div>

    </div>
</div>

<!-- ════════════════════════════════════════════════════════════
     Add Signature Modal
════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="signatureModal" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">

            <!-- Banner header — vtm-banner theme -->
            <div class="vtm-banner" style="--vtm-color:#696cff;--vtm-bg:#eff0ff;--vtm-icon-bg:rgba(105,108,255,.13);">
                <div class="vtm-banner-inner">
                    <div class="vtm-banner-left">
                        <div class="vtm-banner-icon">
                            <i class="bx bx-pen" id="sigModalIcon"></i>
                        </div>
                        <div>
                            <div class="vtm-doc-number" id="sigModalTitle">Add Signature</div>
                            <div class="vtm-doc-meta" id="sigModalMeta">Upload an image or draw your signature on a canvas</div>
                        </div>
                    </div>
                    <div class="vtm-banner-right">
                        <button type="button" class="vtm-close-btn" data-bs-dismiss="modal" aria-label="Close">
                            <i class="bx bx-x"></i>
                        </button>
                    </div>
                </div>
            </div>
            <input type="hidden" id="sigEditUID" value="0" />

            <div class="modal-body p-0">

                <!-- Label field -->
                <div class="px-4 pt-4 pb-3 border-bottom">
                    <label class="form-label fw-semibold">Signature Label</label>
                    <input type="text" id="sigLabel" class="form-control" placeholder="e.g. My Official Signature" maxlength="100" value="My Signature" />
                    <div class="form-text">A friendly name to identify this signature.</div>
                </div>

                <!-- Method Tabs -->
                <ul class="nav nav-tabs px-4 pt-3 border-bottom" id="sigMethodTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="sig-upload-tab"
                                data-bs-toggle="tab" data-bs-target="#sig-upload-pane"
                                type="button" role="tab">
                            <i class="bx bx-upload me-1"></i>Upload Image
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="sig-draw-tab"
                                data-bs-toggle="tab" data-bs-target="#sig-draw-pane"
                                type="button" role="tab">
                            <i class="bx bx-pencil me-1"></i>Draw
                        </button>
                    </li>
                </ul>

                <div class="tab-content">

                    <!-- Upload Pane -->
                    <div class="tab-pane fade show active p-4" id="sig-upload-pane" role="tabpanel">
                        <div class="mb-3">
                            <label class="form-label">Select Signature Image</label>
                            <input type="file" id="sigImageInput" class="form-control" accept="image/png,image/jpeg,image/jpg" />
                            <div class="form-text">PNG or JPG · Max 500 KB · Recommended size: 400 × 150 px · Use a white or transparent background.</div>
                        </div>
                        <!-- Upload preview -->
                        <div id="sigUploadPreview" class="d-none">
                            <div class="d-flex align-items-center justify-content-center p-3 rounded"
                                 style="background:#f8f9fc;border:2px dashed #d0d5dd;min-height:100px;">
                                <img id="sigUploadPreviewImg" src="" alt="Preview"
                                     style="max-width:100%;max-height:120px;object-fit:contain;" />
                            </div>
                            <div id="sigUploadMeta" class="text-muted small mt-2"></div>
                        </div>
                        <div id="sigUploadError" class="alert alert-danger d-none mt-2"></div>
                    </div>

                    <!-- Draw Pane -->
                    <div class="tab-pane fade p-4" id="sig-draw-pane" role="tabpanel">
                        <div class="mb-2 d-flex justify-content-between align-items-center">
                            <label class="form-label mb-0">Draw your signature below</label>
                            <div class="d-flex align-items-center gap-2">
                                <label class="form-label mb-0 small text-muted">Color</label>
                                <input type="color" id="sigPenColor" value="#000000" class="form-control form-control-color" style="width:36px;height:32px;padding:2px;" title="Pen color" />
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="btnClearCanvas">
                                    <i class="bx bx-eraser me-1"></i>Clear
                                </button>
                            </div>
                        </div>
                        <div style="border:2px solid #d0d5dd;border-radius:8px;background:#fff;overflow:hidden;touch-action:none;">
                            <canvas id="signatureCanvas" style="display:block;width:100%;height:200px;cursor:crosshair;"></canvas>
                        </div>
                        <div class="form-text mt-2">Use mouse or touch to sign. Tap Clear to start over.</div>
                        <div id="sigDrawError" class="alert alert-danger d-none mt-2"></div>
                    </div>

                </div>

            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="btnSaveSignature">
                    <i class="bx bx-save me-1"></i>Save Signature
                </button>
            </div>

        </div>
    </div>
</div>
<!-- / Signature Modal -->

<?php $this->load->view('common/footer'); ?>

<!-- Signature Pad library (canvas drawing) -->
<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>

<script src="/js/profile.js"></script>

<script>
var imgData  = '<?php echo isset($userInfo->UserImage) ? $userInfo->UserImage : ''; ?>';
var CsrfName = '<?php echo $this->security->get_csrf_token_name(); ?>';
var CsrfToken = '<?php echo $this->security->get_csrf_hash(); ?>';

var signaturePad    = null;
var sigListLoaded   = false;
var pendingDrawData = null;

$(function() {
    'use strict';

    // ── Profile Image ──────────────────────────────────────────────────────
    if (hasValue(imgData)) {
        commonSetDropzoneImageOne(CDN_URL + imgData);
    }
    loadCountrySelect2Field('#CountryCode', 'Select Country');

    // ── Profile Form Submit ────────────────────────────────────────────────
    $('#btnUpdateProfile').on('click', function() {
        if ($('#pane-profile').hasClass('active')) {
            $('#profileForm').trigger('submit');
        }
    });

    $('#profileForm').submit(function(e) {
        e.preventDefault();

        var Ccode = $('#CountryCode').find('option:selected').data('ccode');
        var MobNum = $('#MobileNumber').val();
        var Status = validateMobileNumber(Ccode, MobNum);
        if (Status === false) {
            Swal.fire('Enter valid Phone Number', '', 'error');
            return false;
        }

        var oldPwd = $('#oldPassword').val().trim();
        var newPwd = $('#newPassword').val().trim();
        var confirmPwd = $('#confirmPassword').val().trim();
        var isPasswordUpdate = false;
        if (oldPwd !== '' || newPwd !== '' || confirmPwd !== '') {
            isPasswordUpdate = true;
            if (oldPwd === '' || newPwd === '' || confirmPwd === '') {
                Swal.fire('Please fill all password fields to change your password', '', 'error');
                return false;
            }
            if (newPwd !== confirmPwd) {
                Swal.fire('New Password and Confirm Password do not match', '', 'error');
                return false;
            }
            if (oldPwd === newPwd) {
                Swal.fire('Old Password and New Password cannot be the same', '', 'error');
                return false;
            }
        }

        var formData = new FormData($('#profileForm')[0]);
        formData.append('CountryISO2', $('#CountryCode').find('option:selected').data('ccode'));
        formData.append('IsPasswordUpdate', isPasswordUpdate ? 1 : 0);
        if (hasValue(imgData) && myOneDropzone.files.length === 0) {
            formData.append('ImageRemoved', 1);
            imgData = '';
        }
        if (myOneDropzone.files.length > 0) {
            var file = myOneDropzone.files[0];
            if (!file.isStored) {
                formData.append('UploadImage', myOneDropzone.files[0]);
                imgData = 1;
            }
        }
        updateProfileForm(formData);
    });

    // ── Tab: show/hide Update button ──────────────────────────────────────
    $('#tab-signatures').on('shown.bs.tab', function() {
        $('#profileHeaderActions').hide();
        if (!sigListLoaded) {
            loadSignatureList();
            sigListLoaded = true;
        }
    });
    $('#tab-profile').on('shown.bs.tab', function() {
        $('#profileHeaderActions').show();
    });

    // ── Signatures: Load list ─────────────────────────────────────────────
    function loadSignatureList() {
        ajaxLoading(0);
        $('#signaturesContainer').html(
            '<div class="text-center py-5 text-muted"><span class="spinner-border spinner-border-sm me-2"></span>Loading signatures...</div>'
        );
        $.post('/settings/profile/getSignatureList', {
            [CsrfName]: CsrfToken
        }).done(function(html) {
            CsrfToken = getNewCsrfFromHtml(html) || CsrfToken;
            $('#signaturesContainer').html(html);
        }).fail(function(xhr, status, error) {
            $('#signaturesContainer').html(
                '<div class="text-danger text-center py-5">Unable to load signatures.</div>'
            );
        }).always(function() {
            ajaxLoading(1);
        });
    }

    // ── Open signature modal (add or edit) ────────────────────────────────
    function openSigModal(editUID, label, type, imgSrc) {
        resetSigModal();
        editUID = parseInt(editUID) || 0;
        $('#sigEditUID').val(editUID);

        if (editUID > 0) {
            $('#sigModalTitle').text('Edit Signature');
            $('#sigModalMeta').text('Update the label or replace the signature content');
            $('#sigModalIcon').removeClass('bx-pen').addClass('bx-edit');
            $('#btnSaveSignature').html('<i class="bx bx-save me-1"></i>Update Signature');
            $('#sigLabel').val(label || '');

            // Show only the relevant tab and pre-fill content
            if (type === 'Draw') {
                $('#sig-upload-tab').parent().hide();
                pendingDrawData = imgSrc || null;
                $('#sig-draw-tab').tab('show');
            } else {
                $('#sig-draw-tab').parent().hide();
                if (imgSrc) {
                    $('#sigUploadPreviewImg').attr('src', imgSrc);
                    $('#sigUploadMeta').text('Current signature — choose a new file to replace it');
                    $('#sigUploadPreview').removeClass('d-none');
                }
            }
        } else {
            $('#sigModalTitle').text('Add Signature');
            $('#sigModalMeta').text('Upload an image or draw your signature on a canvas');
            $('#sigModalIcon').removeClass('bx-edit').addClass('bx-pen');
            $('#btnSaveSignature').html('<i class="bx bx-save me-1"></i>Save Signature');
        }

        $('#signatureModal').modal('show');
    }

    $(document).on('click', '#btnAddSignature, #btnAddSigEmpty', function() { openSigModal(0); });

    // ── Edit button ───────────────────────────────────────────────────────
    $(document).on('click', '.editSigBtn', function() {
        openSigModal(
            $(this).data('uid'),
            $(this).data('label'),
            $(this).data('type'),
            $(this).data('imgsrc')
        );
    });

    // ── Signature Pad: init on draw tab shown ──────────────────────────────
    $('#sig-draw-tab').on('shown.bs.tab', function() {
        initSignaturePad();
    });

    function initSignaturePad() {
        var canvas = document.getElementById('signatureCanvas');
        if (!canvas) return;
        // Scale canvas to device pixel ratio for crisp rendering
        var ratio  = Math.max(window.devicePixelRatio || 1, 1);
        var rect   = canvas.getBoundingClientRect();
        canvas.width  = rect.width  * ratio;
        canvas.height = rect.height * ratio;
        canvas.getContext('2d').scale(ratio, ratio);

        if (signaturePad) signaturePad.clear();
        signaturePad = new SignaturePad(canvas, {
            backgroundColor: 'rgb(255,255,255)',
            penColor: $('#sigPenColor').val() || '#000000',
            minWidth: 1,
            maxWidth: 3,
        });

        // Restore existing drawing when editing a Draw signature
        if (pendingDrawData) {
            signaturePad.fromDataURL(pendingDrawData);
            pendingDrawData = null;
        }
    }

    $('#sigPenColor').on('input', function() {
        if (signaturePad) signaturePad.penColor = $(this).val();
    });

    $('#btnClearCanvas').on('click', function() {
        if (signaturePad) signaturePad.clear();
        $('#sigDrawError').addClass('d-none');
    });

    // ── Upload: file preview ───────────────────────────────────────────────
    $('#sigImageInput').on('change', function() {
        var file = this.files[0];
        $('#sigUploadError').addClass('d-none');
        if (!file) { $('#sigUploadPreview').addClass('d-none'); return; }

        if (!['image/png','image/jpeg','image/jpg'].includes(file.type)) {
            $('#sigUploadError').removeClass('d-none').text('Only PNG and JPG files are allowed.');
            $('#sigUploadPreview').addClass('d-none');
            this.value = '';
            return;
        }
        if (file.size > 500 * 1024) {
            $('#sigUploadError').removeClass('d-none').text('File size exceeds 500 KB. Please use a smaller image.');
            $('#sigUploadPreview').addClass('d-none');
            this.value = '';
            return;
        }

        var reader = new FileReader();
        reader.onload = function(e) {
            $('#sigUploadPreviewImg').attr('src', e.target.result);
            $('#sigUploadMeta').text(file.name + ' · ' + (file.size / 1024).toFixed(1) + ' KB');
            $('#sigUploadPreview').removeClass('d-none');
        };
        reader.readAsDataURL(file);
    });

    // ── Save signature ─────────────────────────────────────────────────────
    $('#btnSaveSignature').on('click', function() {
        var isDrawTab = $('#sig-draw-tab').hasClass('active');
        var label     = $('#sigLabel').val().trim() || 'My Signature';

        if (isDrawTab) {
            saveSigDraw(label);
        } else {
            saveSigUpload(label);
        }
    });

    function saveSigUpload(label) {
        var editUID   = parseInt($('#sigEditUID').val()) || 0;
        var fileInput = document.getElementById('sigImageInput');
        var hasFile   = fileInput.files && fileInput.files.length > 0;

        // New sig: file required. Edit mode: file optional (keep existing if none chosen)
        if (!editUID && !hasFile) {
            $('#sigUploadError').removeClass('d-none').text('Please select an image file.');
            return;
        }

        var url  = editUID > 0 ? '/settings/profile/updateSignature' : '/settings/profile/saveSignature';
        var $btn = $('#btnSaveSignature').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Saving...');
        var fd   = new FormData();
        fd.append('SignatureType', 'Upload');
        fd.append('Label',         label);
        if (editUID > 0) fd.append('SignatureUID', editUID);
        if (hasFile)     fd.append('SignatureImage', fileInput.files[0]);
        fd.append(CsrfName, CsrfToken);

        $.ajax({
            url: url, method: 'POST', data: fd,
            cache: false, processData: false, contentType: false,
            success: function(resp) {
                CsrfToken = resp.NewCsrfToken || CsrfToken;
                $btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i>' + (editUID > 0 ? 'Update Signature' : 'Save Signature'));
                if (resp.Error) {
                    $('#sigUploadError').removeClass('d-none').text(resp.Message);
                } else {
                    $('#signatureModal').modal('hide');
                    sigListLoaded = false;
                    loadSignatureList();
                    Swal.fire(editUID > 0 ? 'Signature updated!' : 'Signature saved!', '', 'success');
                }
            },
            error: function() {
                $btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i>' + (editUID > 0 ? 'Update Signature' : 'Save Signature'));
                $('#sigUploadError').removeClass('d-none').text('Network error. Please try again.');
            }
        });
    }

    function saveSigDraw(label) {
        var editUID  = parseInt($('#sigEditUID').val()) || 0;
        var hasDrawn = signaturePad && !signaturePad.isEmpty();

        // New sig: drawing required. Edit mode: optional (keep existing if canvas empty)
        if (!editUID && !hasDrawn) {
            $('#sigDrawError').removeClass('d-none').text('Please draw your signature before saving.');
            return;
        }

        var url      = editUID > 0 ? '/settings/profile/updateSignature' : '/settings/profile/saveSignature';
        var drawData = hasDrawn ? signaturePad.toDataURL('image/png') : '';
        var $btn = $('#btnSaveSignature').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Saving...');
        var postData = { SignatureType: 'Draw', Label: label, [CsrfName]: CsrfToken };
        if (editUID > 0)  postData.SignatureUID = editUID;
        if (drawData)     postData.DrawData     = drawData;

        $.ajax({
            url: url, method: 'POST', data: postData,
            success: function(resp) {
                CsrfToken = resp.NewCsrfToken || CsrfToken;
                $btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i>' + (editUID > 0 ? 'Update Signature' : 'Save Signature'));
                if (resp.Error) {
                    $('#sigDrawError').removeClass('d-none').text(resp.Message);
                } else {
                    $('#signatureModal').modal('hide');
                    sigListLoaded = false;
                    loadSignatureList();
                    Swal.fire(editUID > 0 ? 'Signature updated!' : 'Signature saved!', '', 'success');
                }
            },
            error: function() {
                $btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i>' + (editUID > 0 ? 'Update Signature' : 'Save Signature'));
                $('#sigDrawError').removeClass('d-none').text('Network error. Please try again.');
            }
        });
    }

    // ── Set default ────────────────────────────────────────────────────────
    $(document).on('click', '.setDefaultSigBtn', function() {
        var uid = $(this).data('uid');
        $.post('/settings/profile/setDefaultSignature', { SignatureUID: uid, [CsrfName]: CsrfToken }, function(resp) {
            CsrfToken = resp.NewCsrfToken || CsrfToken;
            if (resp.Error) {
                Swal.fire(resp.Message, '', 'error');
            } else {
                sigListLoaded = false;
                loadSignatureList();
            }
        });
    });

    // ── Delete ────────────────────────────────────────────────────────────
    $(document).on('click', '.deleteSigBtn', function() {
        var uid   = $(this).data('uid');
        var label = $(this).data('label');
        Swal.fire({
            title: 'Delete Signature?',
            text: '"' + label + '" will be permanently removed.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete it',
            confirmButtonColor: '#d33',
        }).then(function(result) {
            if (!result.isConfirmed) return;
            $.post('/settings/profile/deleteSignature', { SignatureUID: uid, [CsrfName]: CsrfToken }, function(resp) {
                CsrfToken = resp.NewCsrfToken || CsrfToken;
                if (resp.Error) {
                    Swal.fire(resp.Message, '', 'error');
                } else {
                    $('#sigCard_' + uid).fadeOut(250, function() { $(this).remove(); });
                    // If container now empty, reload to show empty state
                    setTimeout(function() {
                        if ($('#signaturesContainer .col-xl-4').length === 0) {
                            sigListLoaded = false;
                            loadSignatureList();
                        }
                    }, 300);
                }
            });
        });
    });

    // ── Reset modal ───────────────────────────────────────────────────────
    function resetSigModal() {
        $('#sigEditUID').val(0);
        $('#sigLabel').val('My Signature');
        $('#sigImageInput').val('');
        $('#sigUploadPreview').addClass('d-none');
        $('#sigUploadError, #sigDrawError').addClass('d-none');
        // Restore both tabs for add mode
        $('#sig-upload-tab').parent().show();
        $('#sig-draw-tab').parent().show();
        $('#sig-upload-tab').tab('show');
        $('#sigModalTitle').text('Add Signature');
        $('#sigModalMeta').text('Upload an image or draw your signature on a canvas');
        $('#sigModalIcon').removeClass('bx-edit').addClass('bx-pen');
        $('#btnSaveSignature').html('<i class="bx bx-save me-1"></i>Save Signature');
        pendingDrawData = null;
        if (signaturePad) signaturePad.clear();
    }

    $('#signatureModal').on('hidden.bs.modal', resetSigModal);

    // ── Resize canvas when modal opens on draw tab ─────────────────────────
    $('#signatureModal').on('shown.bs.modal', function() {
        if ($('#sig-draw-tab').hasClass('active')) {
            initSignaturePad();
        }
    });

    // ── CSRF helper: extract new token from HTML response ─────────────────
    function getNewCsrfFromHtml(html) {
        var match = html.match(/data-csrf-token="([^"]+)"/);
        return match ? match[1] : null;
    }

});
</script>
