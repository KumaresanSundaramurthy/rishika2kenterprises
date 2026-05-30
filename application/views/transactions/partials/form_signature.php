<?php defined('BASEPATH') or exit('No direct script access allowed');
/**
 * Reusable signature picker partial — included in form_products_add.php.
 *
 * Optional variable passed via load->view second arg:
 *   $transSignatureUID  — pre-selected SignatureUID for edit mode (default 0)
 */
$_savedSigUID   = isset($transSignatureUID) ? (int)$transSignatureUID : 0;
$_sigFromJwt    = isset($transSignatures) ? $transSignatures : null; // null = use AJAX fallback
?>

<!-- ── Authorized Signatory Section ─────────────────────────────────────── -->
<div class="mt-3" id="transSignatureSection">
<input type="hidden" name="SignatureUID" id="transSignatureUID" value="<?php echo $_savedSigUID; ?>" />

<div class="p-3 rounded-3" style="background:linear-gradient(135deg,#f5f3ff 0%,#ede9fe 100%);border:1px solid #ddd6fe;">

    <!-- Header row -->
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div class="d-flex align-items-center gap-2">
            <div style="width:32px;height:32px;background:#696cff;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="bx bx-pen text-white" style="font-size:1rem;"></i>
            </div>
            <div>
                <div class="fw-bold" style="font-size:.88rem;color:#3730a3;">Authorized Signatory</div>
                <div class="text-muted" style="font-size:.72rem;">Signature will appear on the printed document</div>
            </div>
        </div>
        <a href="/settings/profile" target="_blank"
           class="d-flex align-items-center gap-1 text-decoration-none"
           style="font-size:.75rem;color:#696cff;font-weight:600;">
            <i class="bx bx-cog" style="font-size:.9rem;"></i>Manage Signatures
        </a>
    </div>

    <div class="row g-3 align-items-stretch">

        <!-- Left: selector -->
        <div class="col-md-5">
            <div class="h-100 d-flex flex-column justify-content-center gap-2">

                <!-- Loading state -->
                <div id="transSigLoading" class="text-muted d-flex align-items-center gap-2" style="font-size:.8rem;">
                    <span class="spinner-border spinner-border-sm"></span> Loading signatures...
                </div>

                <!-- Populated state -->
                <div id="transSigControls" class="d-none">
                    <label class="form-label small fw-semibold mb-1" style="color:#4c1d95;">Select Signature</label>
                    <select id="transSignatureSelect" class="form-select form-select-sm">
                        <option value="">— No signature —</option>
                    </select>
                    <div class="mt-2 d-flex align-items-center gap-2" id="transSigMeta" style="display:none!important;">
                        <span class="badge bg-label-primary" id="transSigTypeBadge" style="font-size:.68rem;"></span>
                        <span class="text-muted" style="font-size:.72rem;" id="transSigDefaultBadge"></span>
                    </div>
                </div>

                <!-- Empty state -->
                <div id="transSigEmpty" class="d-none">
                    <div class="text-muted mb-2" style="font-size:.8rem;">
                        <i class="bx bx-info-circle me-1"></i>No signatures saved yet.
                    </div>
                    <a href="/settings/profile" target="_blank" class="btn btn-sm btn-outline-primary">
                        <i class="bx bx-plus me-1"></i>Add Signature
                    </a>
                </div>

            </div>
        </div>

        <!-- Right: live document preview -->
        <div class="col-md-7">
            <div class="h-100 rounded-2 d-flex flex-column"
                 style="background:#fff;border:1px solid #e0d9ff;min-height:110px;overflow:hidden;">

                <!-- Header bar of preview -->
                <div class="px-3 py-2 d-flex align-items-center justify-content-between"
                     style="background:#f0ebff;border-bottom:1px solid #e0d9ff;">
                    <span style="font-size:.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:#7c3aed;">
                        <i class="bx bx-file-blank me-1"></i>Document Preview
                    </span>
                    <span id="transSigPreviewLabel" style="font-size:.7rem;color:#9ca3af;font-style:italic;"></span>
                </div>

                <!-- Signature display area -->
                <div class="flex-grow-1 d-flex align-items-center justify-content-center px-4 py-3"
                     id="transSigPreviewArea">

                    <!-- Placeholder -->
                    <div id="transSigPlaceholder" class="text-center">
                        <i class="bx bx-image-alt d-block mb-1" style="font-size:1.8rem;color:#d1d5db;"></i>
                        <span class="text-muted" style="font-size:.75rem;">Select a signature to preview</span>
                    </div>

                    <!-- Actual preview (hidden until signature selected) -->
                    <div id="transSigPreviewContent" class="w-100 d-none">
                        <div class="d-flex flex-column align-items-end">
                            <!-- Signature image -->
                            <div style="min-width:180px;text-align:right;">
                                <img id="transSigPreviewImg" src="" alt="Signature"
                                     style="max-height:70px;max-width:200px;object-fit:contain;display:block;margin-left:auto;" />
                                <!-- Signature line -->
                                <div style="border-top:1.5px solid #374151;margin-top:6px;padding-top:5px;">
                                    <div id="transSigPreviewName"
                                         style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:#1f2937;text-align:right;"></div>
                                    <div style="font-size:.62rem;color:#6b7280;letter-spacing:.5px;text-align:right;">
                                        AUTHORIZED SIGNATORY
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

            </div>
        </div>

    </div>
</div>
</div>
<!-- / Authorized Signatory Section -->

<script>
(function () {
    'use strict';

    var _sigMap    = {};   // SignatureUID → signature object
    var _savedUID  = <?php echo $_savedSigUID; ?>;

    function _populateSigSelect(data) {
        var $sel = $('#transSignatureSelect');
        $sel.find('option:not(:first)').remove();
        _sigMap = {};

        if (!data || data.length === 0) {
            $('#transSigLoading').addClass('d-none');
            $('#transSigEmpty').removeClass('d-none');
            return;
        }

        var defaultUID = 0;
        $.each(data, function (_, sig) {
            _sigMap[sig.SignatureUID] = sig;
            if (sig.IsDefault && !defaultUID) defaultUID = sig.SignatureUID;
            $sel.append(
                $('<option>', { value: sig.SignatureUID, text: sig.Label })
                    .attr('data-is-default', sig.IsDefault)
            );
        });

        $('#transSigLoading').addClass('d-none');
        $('#transSigControls').removeClass('d-none');

        // Pre-select: saved UID (edit mode) → default → first
        var selectUID = _savedUID || defaultUID || data[0].SignatureUID;
        $sel.val(selectUID);
        _renderPreview(selectUID);
    }

    function _renderPreview(uid) {
        $('#transSignatureUID').val(uid || '');

        if (!uid || !_sigMap[uid]) {
            $('#transSigPlaceholder').removeClass('d-none');
            $('#transSigPreviewContent').addClass('d-none');
            $('#transSigPreviewLabel').text('');
            $('#transSigMeta').css('display', 'none');
            return;
        }

        var sig   = _sigMap[uid];
        var label = sig.Label;
        var type  = sig.SignatureType;

        $('#transSigPreviewImg').attr('src', sig.ImgSrc);
        $('#transSigPreviewName').text(label);
        $('#transSigPreviewLabel').text(label);

        var badgeClass = type === 'Draw' ? 'bg-label-info' : 'bg-label-secondary';
        var badgeText  = type === 'Draw' ? '<i class="bx bx-pencil me-1"></i>Drawn' : '<i class="bx bx-image me-1"></i>Uploaded';
        $('#transSigTypeBadge').removeClass('bg-label-info bg-label-secondary').addClass(badgeClass).html(badgeText);
        $('#transSigDefaultBadge').html(sig.IsDefault ? '<i class="bx bx-star-filled me-1" style="color:#f59e0b;"></i>Default' : '');
        $('#transSigMeta').css('display', '');

        $('#transSigPlaceholder').addClass('d-none');
        $('#transSigPreviewContent').removeClass('d-none');
    }

    document.addEventListener('DOMContentLoaded', function () {

        <?php if ($_sigFromJwt !== null): ?>
        // ── Signatures from JWT — no AJAX needed ─────────────────────────────
        _populateSigSelect(<?php echo json_encode(array_values((array)$_sigFromJwt)); ?>);
        <?php else: ?>
        // ── Fallback: load via AJAX (pages not yet passing JWT signatures) ────
        var CsrfName  = typeof window.CsrfName  !== 'undefined' ? window.CsrfName  : '';
        var CsrfToken = typeof window.CsrfToken !== 'undefined' ? window.CsrfToken : '';
        ajaxLoading(0);
        $.ajax({
            url: '/settings/profile/getSignaturesJson',
            method: 'POST',
            data: { [CsrfName]: CsrfToken },
            success: function (resp) {
                ajaxLoading(1);
                if (window.CsrfToken !== undefined && resp.NewCsrfToken) window.CsrfToken = resp.NewCsrfToken;
                _populateSigSelect(resp.Error ? [] : resp.Data);
            },
            error: function () {
                ajaxLoading(1);
                $('#transSigLoading').addClass('d-none');
            }
        });
        <?php endif; ?>

        $(document).on('change', '#transSignatureSelect', function () {
            _renderPreview(parseInt($(this).val()) || 0);
        });
    });

}());
</script>
