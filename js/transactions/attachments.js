// Shared attachment handling for all transaction edit forms.
// Requires: jQuery, SweetAlert2, CDN_URL global (optional), multiDropzone global.

var _removedAttachIDs = [];
var _MAX_ATTACH = 5;

// After rendering existing chips, update the dropzone's available slots.
function _syncDropzoneLimit(existingCount) {
    if (typeof multiDropzone === 'undefined' || !multiDropzone) return;
    var remaining = Math.max(0, _MAX_ATTACH - existingCount);
    multiDropzone.options.maxFiles = remaining;
    var $dz = $('#multipleDropzone');
    if (remaining === 0) {
        $dz.addClass('dz-max-files-reached').attr('title', 'Maximum ' + _MAX_ATTACH + ' files already uploaded.');
    } else {
        $dz.removeClass('dz-max-files-reached').removeAttr('title');
    }
}

function _renderAttachmentChips(attachments) {
    var cdnUrl     = (typeof CDN_URL !== 'undefined' && CDN_URL) ? CDN_URL : '';
    var $container = $('#existingAttachItems').empty();
    attachments.forEach(function(a) {
        var uid      = a.AttachUID;
        var name     = a.FileName || '';
        var safeName = $('<span>').text(name).html();
        var fullUrl  = cdnUrl + (a.FilePath || '');
        var isImg    = /image\//i.test(a.FileType || '') || /\.(jpg|jpeg|png|gif|webp|bmp|svg)$/i.test(name);
        var isPdf    = /pdf/i.test(a.FileType || '') || /\.pdf$/i.test(name);
        var iconCls  = isImg ? 'bx-image-alt text-success' : (isPdf ? 'bxs-file-pdf text-danger' : 'bx-file text-secondary');
        var type     = isImg ? 'img' : (isPdf ? 'pdf' : 'file');
        var encUrl   = encodeURIComponent(fullUrl);
        var $item    = $('<div class="d-flex align-items-center gap-1 border rounded px-2 py-1 bg-light existing-attach-item" style="font-size:.78rem;max-width:220px;" data-uid="' + uid + '">' +
            '<i class="bx ' + iconCls + '" style="font-size:1rem;flex-shrink:0;cursor:pointer;" onclick="_openAttachPreview(\'' + encUrl + '\',\'' + type + '\',\'' + safeName.replace(/'/g, "\\'") + '\')"></i>' +
            '<span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;flex:1;cursor:pointer;" title="' + safeName + '" onclick="_openAttachPreview(\'' + encUrl + '\',\'' + type + '\',\'' + safeName.replace(/'/g, "\\'") + '\')">' + safeName + '</span>' +
            '<button type="button" class="btn-close btn-close-sm ms-1 remove-attach-btn" style="font-size:.6rem;" title="Delete" data-uid="' + uid + '"></button>' +
        '</div>');
        $container.append($item);
    });
    $('#existingAttachList').removeClass('d-none');
    $('#existingAttachCount').text(attachments.length).removeClass('d-none');
    $('#accordionUploadFiles').addClass('show');
    _syncDropzoneLimit(attachments.length);
}

function _bindRemoveHandler() {
    $(document).off('click.attachRemove').on('click.attachRemove', '.remove-attach-btn', function() {
        var $btn      = $(this);
        var attachUID = parseInt($btn.data('uid'), 10);
        var $item     = $btn.closest('.existing-attach-item');
        var fileName  = $item.find('span[title]').attr('title') || 'this file';

        Swal.fire({
            title             : 'Delete Attachment?',
            html              : '<div style="text-align:center;margin-bottom:8px;"><i class="bx bx-error-circle" style="font-size:2.5rem;color:#f59e0b;"></i></div>' +
                                'Are you sure you want to remove <strong>' + $('<span>').text(fileName).html() + '</strong>? This cannot be undone.',
            showCancelButton  : true,
            confirmButtonColor: '#d33',
            confirmButtonText : 'Yes, Delete',
            cancelButtonText  : 'Cancel',
        }).then(function(result) {
            if (!result.isConfirmed) return;
            $item.remove();
            if (_removedAttachIDs.indexOf(attachUID) === -1) _removedAttachIDs.push(attachUID);
            var remaining = $('#existingAttachItems .existing-attach-item').length;
            if (remaining === 0) {
                $('#existingAttachList').addClass('d-none');
                $('#existingAttachCount').addClass('d-none');
            } else {
                $('#existingAttachCount').text(remaining);
            }
            _syncDropzoneLimit(remaining);
        });
    });
}

// Call this when attachment data is already available from PHP (no AJAX needed).
function renderTransAttachmentsFromData(attachments) {
    _removedAttachIDs = [];
    if (attachments && attachments.length) {
        _renderAttachmentChips(attachments);
    } else {
        _syncDropzoneLimit(0);
    }
    _bindRemoveHandler();
}

// Call this when attachment data must be fetched via AJAX on page load.
function initTransAttachments(transUID, getUrl) {
    if (!transUID || transUID <= 0) return;
    _removedAttachIDs = [];
    var $form     = $('[data-csrf]').first();
    var csrfName  = $form.data('csrf');
    var csrfToken = $form.data('csrf-value');
    $.ajax({
        url   : getUrl,
        method: 'POST',
        data  : { TransUID: transUID, [csrfName]: csrfToken },
        success: function(resp) {
            if (resp.Error || !resp.Attachments || !resp.Attachments.length) {
                _syncDropzoneLimit(0);
                return;
            }
            _renderAttachmentChips(resp.Attachments);
        }
    });
    _bindRemoveHandler();
}

// ── Shared attachment gallery builder ─────────────────────────────────────────
function _buildAttachGalleryHtml(attachments, cdnUrl) {
    var html = '<div class="row g-2">';
    attachments.forEach(function (a) {
        var fullUrl  = (cdnUrl || '') + (a.FilePath || '');
        var safeName = $('<span>').text(a.FileName || '').html();
        var isImg    = /image\//i.test(a.FileType || '') || /\.(jpg|jpeg|png|gif|webp|bmp|svg)$/i.test(a.FileName || '');
        var isPdf    = /pdf/i.test(a.FileType || '')    || /\.pdf$/i.test(a.FileName || '');
        var encUrl   = encodeURIComponent(fullUrl);
        var thumb, icon;
        if (isImg) {
            thumb = '<div class="attach-thumb-wrap border rounded overflow-hidden" style="cursor:pointer;height:120px;background:#f8f9fa;" onclick="_openAttachPreview(\'' + encUrl + '\',\'img\',\'' + safeName.replace(/'/g, "\\'") + '\')">' +
                    '<img src="' + $('<span>').text(fullUrl).html() + '" style="width:100%;height:100%;object-fit:cover;" loading="lazy" alt="' + safeName + '"></div>';
            icon = '<i class="bx bx-image-alt"></i>';
        } else if (isPdf) {
            thumb = '<div class="attach-thumb-wrap border rounded d-flex flex-column align-items-center justify-content-center gap-1" style="cursor:pointer;height:120px;background:#fff5f5;" onclick="_openAttachPreview(\'' + encUrl + '\',\'pdf\',\'' + safeName.replace(/'/g, "\\'") + '\')">' +
                    '<i class="bx bxs-file-pdf text-danger" style="font-size:2.5rem;"></i>' +
                    '<span style="font-size:.72rem;color:#dc3545;font-weight:600;">PDF</span></div>';
            icon = '<i class="bx bx-file-blank"></i>';
        } else {
            thumb = '<div class="attach-thumb-wrap border rounded d-flex flex-column align-items-center justify-content-center gap-1" style="cursor:pointer;height:120px;background:#f8f9fa;" onclick="_openAttachPreview(\'' + encUrl + '\',\'file\',\'' + safeName.replace(/'/g, "\\'") + '\')">' +
                    '<i class="bx bx-file text-secondary" style="font-size:2.5rem;"></i>' +
                    '<span style="font-size:.72rem;color:#6c757d;font-weight:500;text-align:center;padding:0 6px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:90%;">' + safeName + '</span></div>';
            icon = '<i class="bx bx-file-blank"></i>';
        }
        html += '<div class="col-6 col-md-4">' + thumb +
                '<div class="text-muted mt-1 d-flex align-items-center gap-1" style="font-size:.72rem;">' + icon +
                '<span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="' + safeName + '">' + safeName + '</span></div></div>';
    });
    html += '</div>';
    return html;
}

// ── Open transAttachModal ──────────────────────────────────────────────────────
// uid        — TransUID
// num        — display number e.g. 'INV-001'
// fetchUrl   — AJAX endpoint
// accentColor — e.g. '#0d6efd'
// title      — optional title prefix, defaults to 'Attachments'
function openTransAttachModal(uid, num, fetchUrl, accentColor, title) {
    accentColor = accentColor || '#0d6efd';
    title       = (title || 'Attachments') + ' — ' + (num || ('Ref #' + uid));
    $('#transAttachModalTitle').text(title).css('color', accentColor);
    $('#transAttachModalBanner').css({ 'background': accentColor + '18', 'border-left': '4px solid ' + accentColor });
    $('#transAttachModalIconWrap').css('background', accentColor + '22');
    $('#transAttachModalIconWrap i').css('color', accentColor);
    $('#transAttachGallery').html('<div class="text-center py-4"><span class="spinner-border spinner-border-sm" style="color:' + accentColor + ';"></span></div>');
    bootstrap.Modal.getOrCreateInstance(document.getElementById('transAttachModal')).show();
    AjaxLoading = 0;
    $.ajax({
        url    : fetchUrl,
        method : 'POST',
        data   : { TransUID: uid, [CsrfName]: CsrfToken },
        success: function (resp) {
            AjaxLoading = 1;
            if (resp.Error || !resp.Attachments || !resp.Attachments.length) {
                $('#transAttachGallery').html('<div class="text-center py-5 text-muted"><i class="bx bx-paperclip fs-2 d-block mb-2"></i>No attachments found.</div>');
                return;
            }
            var cdnUrl = (typeof CDN_URL !== 'undefined' && CDN_URL) ? CDN_URL : '';
            $('#transAttachGallery').html(_buildAttachGalleryHtml(resp.Attachments, cdnUrl));
        },
        error: function () {
            AjaxLoading = 1;
            $('#transAttachGallery').html('<div class="text-center py-4 text-danger">Failed to load attachments.</div>');
        }
    });
}

// ── Sequential swap: transAttachModal → attachPreviewModal → transAttachModal ──
var _attachPreviewOpener = null;

function _openAttachPreview(encUrl, type, name) {
    var url      = decodeURIComponent(encUrl);
    var safeName = $('<span>').text(name).html();
    $('#attachPreviewTitle').text(name || 'Preview');
    var body = '';
    if (type === 'img') {
        body = '<div class="d-flex align-items-center justify-content-center" style="height:100%;padding:16px;"><img src="' + $('<span>').text(url).html() + '" class="img-fluid rounded" style="max-height:calc(92vh - 48px);object-fit:contain;" alt="' + safeName + '"></div>';
    } else if (type === 'pdf') {
        body = '<iframe src="' + $('<span>').text(url).html() + '" style="width:100%;height:calc(92vh - 48px);border:none;display:block;"></iframe>';
    } else {
        body = '<div class="d-flex flex-column align-items-center justify-content-center" style="height:calc(92vh - 48px);">' +
               '<i class="bx bx-file-blank text-secondary" style="font-size:4rem;display:block;margin-bottom:12px;"></i>' +
               '<div style="font-size:.9rem;font-weight:600;color:#fff;margin-bottom:16px;">' + safeName + '</div>' +
               '<a href="' + $('<span>').text(url).html() + '" download="' + safeName + '" class="btn btn-primary px-4"><i class="bx bx-download me-2"></i>Download File</a>' +
               '</div>';
    }
    $('#attachPreviewBody').html(body);

    var previewEl     = document.getElementById('attachPreviewModal');
    var transAttachEl = document.getElementById('transAttachModal');
    var previewModal  = bootstrap.Modal.getOrCreateInstance(previewEl);

    if (transAttachEl && transAttachEl.classList.contains('show')) {
        _attachPreviewOpener = 'transAttach';
        $(transAttachEl).one('hidden.bs.modal', function () { previewModal.show(); });
        bootstrap.Modal.getInstance(transAttachEl).hide();
    } else {
        previewModal.show();
    }
}

// Return to transAttachModal when preview is closed
$(document).on('hidden.bs.modal', '#attachPreviewModal', function () {
    if (_attachPreviewOpener === 'transAttach') {
        _attachPreviewOpener = null;
        bootstrap.Modal.getOrCreateInstance(document.getElementById('transAttachModal')).show();
    }
});

// ── Common attachment button handlers (used across all transaction pages) ────
$(document).on('click', '.transAttachBtn', function () {
    openTransAttachModal($(this).data('uid'), $(this).data('num'), $(this).data('url'), $(this).data('color') || '#0d6efd');
});

$(document).on('click', '.transPayAttachBtn', function (e) {
    e.stopPropagation();
    openTransAttachModal($(this).data('uid'), $(this).data('num'), $(this).data('url'), $(this).data('color') || '#0d6efd', 'Payment Attachments');
});
