<script>
var JwtToken = '<?php echo $JwtToken; ?>';
var JwtData = <?php echo json_encode($JwtData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
var CsrfName = '<?php echo $this->security->get_csrf_token_name(); ?>';
var CsrfToken = '<?php echo $this->security->get_csrf_hash(); ?>';
const defaultIso2 = '<?php echo $JwtData->Org->OrgCISO2 ?? 'IN'; ?>';
const defaultCCode = '+91';
var RowLimit = <?php echo isset($JwtData->GenSettings->RowLimit) ? $JwtData->GenSettings->RowLimit : 10; ?>;
// Date format settings — available on all pages (customers, vendors, etc.)
var _transFormDateFormat      = '<?php echo addslashes($JwtData->GenSettings->FormDateFormat      ?? 'd-m-Y'); ?>';
var _transListDateFormat      = '<?php echo addslashes($JwtData->GenSettings->ListDateFormat      ?? 'd-m-Y'); ?>';
var _transPrintDateFormat     = '<?php echo addslashes($JwtData->GenSettings->PrintDateFormat     ?? 'd-m-Y'); ?>';
var _transFormDateTimeFormat  = '<?php echo addslashes($JwtData->GenSettings->FormDateTimeFormat  ?? 'd-m-Y H:i'); ?>';
var _transListDateTimeFormat  = '<?php echo addslashes($JwtData->GenSettings->ListDateTimeFormat  ?? 'd-m-Y H:i'); ?>';
var _transPrintDateTimeFormat = '<?php echo addslashes($JwtData->GenSettings->PrintDateTimeFormat ?? 'd-m-Y H:i'); ?>';
var UserRoleUID = <?php echo isset($JwtData->User->RoleUID) ? $JwtData->User->RoleUID : 0; ?>;
var PageNo = 0;
var Filter = {};
var AjaxLoading = 1;
var global_base_url = '<?php echo base_url(); ?>';
var CDN_URL = '<?php echo getenv('FILE_UPLOAD') == 'amazonaws' ? getenv('CDN_URL') : getenv('CFLARE_R2_CDN') ?>';
var defUserImg = '<?php echo '/website/images/logo/avathar_user.png'; ?>';
var myOneDropzone;
var myTwoDropzone;
var quill;
var hasRemovedStoredImage = false;
var SelectedUIDs = [];
var exportModule;
var expActionType;
var delBankDataFlag = 0;
var delBankData = [];
var delAddrDetailFlag = 0;
var delAddrData = [];
const currencySymbol = '<?php echo $JwtData->GenSettings->CurrenySymbol ?? '₹'; ?>';
$(function() {
	'use strict'

    // Bootstrap tooltips — init all [data-bs-toggle="tooltip"] elements on page
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
        new bootstrap.Tooltip(el, { trigger: 'hover' });
    });

    // Settings menu toggle
    $('#SettingsMenuBarBtn').click(function() {
        $('#ModulesMenuBar').addClass('d-none');
        $('#SettingsMenuBar').removeClass('d-none');
    });
    $('#SettingsBackMenuBarBtn').click(function() {
        $('#SettingsMenuBar').addClass('d-none');
        $('#ModulesMenuBar').removeClass('d-none');
    });

    // Settings sidebar accordion — handle nested menu-toggle clicks independently
    // stopPropagation prevents Sneat Menu.js (bound on #layout-menu) from double-toggling
    $('#SettingsMenuBar').on('click', '.menu-link.menu-toggle', function(e) {
        e.preventDefault();
        e.stopPropagation();
        var $li = $(this).closest('li.menu-item');
        var $sub = $li.children('ul.menu-sub');
        if (!$sub.length) return;
        if ($li.hasClass('open')) {
            $li.removeClass('open');
        } else {
            $li.siblings('li.menu-item.open').removeClass('open');
            $li.addClass('open');
        }
    });


    $('.ChangePasswordBtn').click(function(e) {
        e.preventDefault();
        $('#ChangePasswordModal').modal('show');
        $('#ChangePasswordModal #ResetPasswordForm').trigger('reset');
        $('#ResetPasswordSubBtn').removeAttr('disabled');
        $('#ChangePasswordAlert').html('');
        $('#ChangePasswordAlert').addClass('d-none');
    });

    $('#ResetPasswordForm').submit(function(e) {
        e.preventDefault();
        
        $('#ChangePasswordAlert').html('');
        $('#ChangePasswordAlert').addClass('d-none');

        var newPassword = $('#NewPassword').val();        
        var confirmPassword = $('#ConfirmPassword').val();
        if (newPassword !== confirmPassword) {
            inlineMessageAlert('#ChangePasswordAlert', 'danger', 'Passwords do not match!', false, false);
            $('#ChangePasswordAlert').removeClass('d-none');
            return false;
        }

        var oldPassword = $('#OldPassword').val();
        if(oldPassword === newPassword) {
            inlineMessageAlert('#ChangePasswordAlert', 'danger', 'Old & New Passwords are same!', false, false);
            $('#ChangePasswordAlert').removeClass('d-none');
            return false;
        }
        
        resetUserPassword($('#ResetPasswordForm').serializeArray());

    });

    // Initialize Dropzone for file upload - Only One will be allowed
    let dropzoneElement = document.querySelector("#DropzoneOneBasic");
    if (dropzoneElement) {
        myOneDropzone = new Dropzone(dropzoneElement, {
            url: "#",
            autoProcessQueue: false,
            previewTemplate: `
                <div class="dz-preview dz-file-preview">
                    <div class="dz-details">
                        <div class="dz-thumbnail">
                            <img data-dz-thumbnail>
                            <span class="dz-nopreview">No preview</span>
                            <div class="dz-success-mark"></div>
                            <div class="dz-error-mark"></div>
                            <div class="dz-error-message"><span data-dz-errormessage></span></div>
                            <div class="progress">
                                <div class="progress-bar progress-bar-primary" role="progressbar" aria-valuemin="0" aria-valuemax="100" data-dz-uploadprogress></div>
                            </div>
                        </div>
                        <div class="dz-filename" data-dz-name></div>
                        <div class="dz-size" data-dz-size></div>
                    </div>
                </div>
            `,
            parallelUploads: 1,
            maxFilesize: 1, // In MB
            acceptedFiles: ".jpg,.jpeg,.png",
            addRemoveLinks: true,
            maxFiles: 1,
            init: function() {
                this.on("addedfile", function(file) {
                    if (this.files.length > 1) {
                        this.removeFile(this.files[1]); // Only allow one
                    }
                });
                this.on("error", function(file, message) {
                    if (file.size > this.options.maxFilesize * 1024 * 1024) {
                        Swal.fire({
                            icon: "error",
                            title: "File too large",
                            text: "Maximum allowed size is 1 MB.",
                        });
                        this.removeFile(file);
                    }
                });
                this.on("removedfile", function(file) {
                    if (file.isStored) {
                        hasRemovedStoredImage = true;
                    }
                });
                this.on("maxfilesexceeded", function(file) {
                    this.removeFile(file);
                    Swal.fire({
                        icon: "error",
                        title: "Oops...",
                        text: "Only one image is allowed.",
                    });
                });
            }
        });

        // Optional: Disable click if one file already added
        dropzoneElement.addEventListener("click", function(e) {
            if (myOneDropzone.files.length >= 1) {
                e.preventDefault();
                e.stopPropagation();
                Swal.fire({
                    icon: "error",
                    title: "Oops...",
                    text: "Only one image is allowed.",
                });
            }
        });

    }

    let dzElement = document.querySelector("#DropzoneTwoBasic");
    if (dzElement) {
        myTwoDropzone = new Dropzone(dzElement, {
            url: "#",
            autoProcessQueue: false,
            previewTemplate: `
                <div class="dz-preview dz-file-preview">
                    <div class="dz-details">
                        <div class="dz-thumbnail">
                            <img data-dz-thumbnail>
                            <span class="dz-nopreview">No preview</span>
                            <div class="dz-success-mark"></div>
                            <div class="dz-error-mark"></div>
                            <div class="dz-error-message"><span data-dz-errormessage></span></div>
                            <div class="progress">
                                <div class="progress-bar progress-bar-primary" role="progressbar" aria-valuemin="0" aria-valuemax="100" data-dz-uploadprogress></div>
                            </div>
                        </div>
                        <div class="dz-filename" data-dz-name></div>
                        <div class="dz-size" data-dz-size></div>
                    </div>
                </div>
            `,
            parallelUploads: 1,
            maxFilesize: 1, // In MB
            acceptedFiles: ".jpg,.jpeg,.png",
            addRemoveLinks: true,
            maxFiles: 1,
            init: function() {
                this.on("addedfile", function(file) {
                    if (this.files.length > 1) {
                        this.removeFile(this.files[1]); // Only allow one
                    }
                });
                this.on("error", function(file, message) {
                    if (file.size > this.options.maxFilesize * 1024 * 1024) {
                        Swal.fire({icon: "error", title: "File too large", text: "Maximum allowed size is 1 MB."});
                        this.removeFile(file);
                    }
                });
                this.on("removedfile", function(file) {
                    if (file.isStored) {
                        hasRemovedStoredImage = true;
                    }
                });
                this.on("maxfilesexceeded", function(file) {
                    this.removeFile(file);
                    Swal.fire({icon: "error", title: "Oops...", text: "Only one image is allowed."});
                });
            }
        });

        // Optional: Disable click if one file already added
        dzElement.addEventListener("click", function(e) {
            if (myTwoDropzone.files.length >= 1) {
                e.preventDefault();
                e.stopPropagation();
                Swal.fire({icon: "error", title: "Oops...", text: "Only one image is allowed."});
            }
        });

    }

    // ── Party hover card ──────────────────────────────────────────────────
    (function () {
        var $pop   = null;
        var _timer = null;

        function _esc(str) { return $('<s>').text(str).html(); }

        function _getOrCreate() {
            if (!$pop) $pop = $('<div class="chc-pop"><div class="chc-card"></div></div>').appendTo('body');
            return $pop;
        }

        $(document).on('mouseenter', '.chc-trigger', function () {
            clearTimeout(_timer);
            var el     = this;
            var $el    = $(el);
            var name   = $el.data('name')   || '';
            var mobile = String($el.data('mobile') || '');
            var code   = String($el.data('code')   || '');
            var area   = $el.data('area')   || '';
            var img    = $el.data('img')    || '';

            var words    = name.trim().split(/\s+/);
            var initials = ((words[0] || '')[0] || '') + ((words[1] || '')[0] || '');
            initials = initials.toUpperCase();

            var avatarHtml = img
                ? '<img class="chc-img" src="' + img + '" alt="">'
                : '<div class="chc-avatar">' + _esc(initials || '?') + '</div>';

            var mobileHtml = '';
            if (mobile) {
                var display = (code ? code + ' ' : '') + mobile;
                var waNum   = (code + mobile).replace(/\D/g, '');
                mobileHtml  = '<div class="chc-mobile">' + _esc(display) +
                    ' <a href="https://wa.me/' + waNum + '?text=Hi" target="_blank" rel="noopener" onclick="event.stopPropagation()">' +
                    '<i class="bx bxl-whatsapp chc-wa"></i></a></div>';
            }

            var areaHtml = area ? '<div class="chc-area">' + _esc(area) + '</div>' : '';

            var html = '<div class="chc-inner">' + avatarHtml +
                '<div><div class="chc-name">' + _esc(name) + '</div>' + mobileHtml + areaHtml + '</div>' +
                '</div>';

            var $card = _getOrCreate();
            $card.find('.chc-card').html(html);
            $card.css({ display:'block', top:-999, left:-999 });

            var rect  = el.getBoundingClientRect();
            var cardW = $card.outerWidth();
            var cardH = $card.outerHeight();
            var vpW   = $(window).width();
            var vpH   = $(window).height();
            // position:fixed — viewport-relative coords, no scrollY offset
            var top  = rect.bottom + 6;
            var left = rect.left;

            if (left + cardW > vpW - 8) left = vpW - cardW - 8;
            if (top  + cardH > vpH - 8) top  = rect.top - cardH - 6;

            $card.css({ top: top, left: left });
        });

        $(document).on('mouseleave', '.chc-trigger', function () {
            _timer = setTimeout(function () { if ($pop) $pop.hide(); }, 150);
        });
    }());

});

</script>