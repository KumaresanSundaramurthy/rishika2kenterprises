/**
 * Transaction attachment helpers — thin wrappers over js/common/attachments.js.
 *
 * Existing form.php callers are preserved:
 *   renderTransAttachmentsFromData(attachments)  — load from PHP-provided array
 *   initTransAttachments(uid, url, moduleUID)     — load via AJAX
 *   collectTransAttachData(fd)                    — call before form submit (replaces multiDropzone pattern)
 *   openTransAttachModal(uid,num,fetchUrl,color,title,moduleUID)  — view-only gallery modal
 */

// ── Load existing attachments from PHP-provided data (edit/clone mode) ───────
function renderTransAttachmentsFromData(attachments) {
    _attachResetState('Transaction');
    if (!attachments || !attachments.length) return;
    var baseUrl = (typeof CDN_URL !== 'undefined' && CDN_URL) ? CDN_URL : '';
    attachments.forEach(function (a) {
        if (!a.Url && a.FilePath) {
            a.Url = baseUrl + (a.FilePath.charAt(0) === '/' ? '' : '/') + a.FilePath;
        }
    });
    _attachState['Transaction'].existing = attachments;
    _attachRender('Transaction');
}

// ── Load existing attachments via AJAX (pages that don't pre-load them) ──────
function initTransAttachments(transUID, getUrl, moduleUID) {
    _attachResetState('Transaction');
    $.ajax({
        url    : getUrl,
        method : 'POST',
        data   : { TransUID: transUID, ModuleUID: moduleUID, [CsrfName]: CsrfToken },
        success: function (resp) {
            if (!resp.Error && resp.Attachments && resp.Attachments.length) {
                var baseUrl = (typeof CDN_URL !== 'undefined' && CDN_URL) ? CDN_URL : '';
                resp.Attachments.forEach(function (a) {
                    if (!a.Url && a.FilePath) {
                        a.Url = baseUrl + (a.FilePath.charAt(0) === '/' ? '' : '/') + a.FilePath;
                    }
                });
                _attachState['Transaction'].existing = resp.Attachments;
                _attachRender('Transaction');
            }
        },
        error: function () {}
    });
}

// ── Collect files + delete UIDs into FormData before submit ───────────────────
// Replaces the old multiDropzone.files pattern in every form.php.
// Call once in the form submit handler: collectTransAttachData(formData);
function collectTransAttachData(fd) {
    var state = (_attachState && _attachState['Transaction']) || {};
    (state.newFiles || []).forEach(function (f) { fd.append('AttachFiles[]', f, f.name); });
    fd.set('RemovedAttachIDs', JSON.stringify((state.toDelete || []).map(Number)));
}

// ── View-only gallery modal (unchanged) ───────────────────────────────────────
function openTransAttachModal(uid, num, fetchUrl, accentColor, title, moduleUID) {
    var $modal = $('#transAttachModal');
    if (!$modal.length) return;

    // Banner — neutral slate header that works on every page
    var $banner = $modal.find('#transAttachModalBanner');
    $banner.css({ background: 'linear-gradient(135deg, #334155 0%, #475569 100%)', padding: '14px 20px' });
    $modal.find('#transAttachModalIconWrap').css({ background: 'rgba(255,255,255,.15)', borderRadius: '10px', padding: '9px 11px' });

    // Update title
    var $title = $modal.find('#transAttachModalTitle');
    $title.text((title || 'Attachments') + (num ? ' — ' + num : '')).css('color', '#fff');
    $modal.find('#transAttachModalBanner .bx-paperclip').css('color', '#fff');

    // Show loading spinner in gallery
    var $gallery = $modal.find('#transAttachGallery');
    $gallery.html('<div class="text-center py-4"><div class="spinner-border text-primary"></div></div>');

    $modal.modal('show');

    $.ajax({
        url   : fetchUrl,
        method: 'POST',
        data  : { TransUID: uid, ModuleUID: moduleUID, [CsrfName]: CsrfToken },
        success: function (resp) {
            if (resp.Error || !resp.Attachments || !resp.Attachments.length) {
                $gallery.html('<p class="text-muted text-center py-3">No attachments found.</p>');
                return;
            }
            $gallery.html(_buildAttachGalleryHtml(resp.Attachments));
        },
        error: function () {
            $gallery.html('<p class="text-danger text-center py-3">Failed to load attachments.</p>');
        }
    });
}

// ── Delegated click handler for the paperclip button on all list pages ────────
$(document).on('click', '.transAttachBtn', function () {
    var uid       = $(this).data('uid');
    var num       = $(this).data('num')        || '';
    var url       = $(this).data('url')        || '/transactions/getAttachments';
    var moduleUID = $(this).data('module-uid') || 0;
    openTransAttachModal(uid, num, url, null, 'Attachments', moduleUID);
});

function _buildAttachGalleryHtml(attachments) {
    var cdnUrl = (typeof CDN_URL !== 'undefined' && CDN_URL) ? CDN_URL : '';
    var html   = '<div class="row g-2 p-2">';
    attachments.forEach(function (a) {
        var url    = a.Url || (cdnUrl + (a.FilePath || ''));
        var name   = a.FileName || '';
        var isImg  = /image\//i.test(a.FileType || '') || /\.(jpg|jpeg|png|gif|webp)$/i.test(name);
        var isPdf  = /pdf/i.test(a.FileType || '') || /\.pdf$/i.test(name);
        var encUrl = encodeURIComponent(url);
        if (isImg) {
            html += '<div class="col-4 col-md-3"><img src="' + url + '" class="img-fluid rounded" style="cursor:pointer;object-fit:cover;height:90px;width:100%;" title="' + name + '" onclick="_openAttachPreview(\'' + encUrl + '\',\'img\',\'' + name + '\')" /></div>';
        } else {
            var icon = isPdf ? 'bxs-file-pdf text-danger' : 'bx-file text-secondary';
            html += '<div class="col-4 col-md-3 d-flex flex-column align-items-center justify-content-center border rounded p-2" style="cursor:pointer;min-height:90px;" onclick="_openAttachPreview(\'' + encUrl + '\',\'' + (isPdf ? 'pdf' : 'file') + '\',\'' + name + '\')"><i class="bx ' + icon + '" style="font-size:2rem;"></i><span class="text-truncate w-100 text-center mt-1" style="font-size:.7rem;">' + name + '</span></div>';
        }
    });
    html += '</div>';
    return html;
}

function _openAttachPreview(encUrl, type, name) {
    var url    = decodeURIComponent(encUrl);
    var $prev  = $('#attachPreviewModal');
    if (!$prev.length) return;
    $prev.find('#attachPreviewTitle').text(name || 'Preview');
    var $body = $prev.find('#attachPreviewBody').empty();
    if (type === 'img') {
        $body.html(
            '<div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;padding:20px;box-sizing:border-box;">' +
                '<img src="' + url + '" style="max-width:100%;max-height:100%;object-fit:contain;border-radius:8px;box-shadow:0 4px 24px rgba(0,0,0,.5);">' +
            '</div>'
        );
    } else if (type === 'pdf') {
        $body.html('<iframe src="' + url + '" width="100%" height="100%" style="border:none;display:block;"></iframe>');
    } else {
        $body.html(
            '<div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;">' +
                '<a href="' + url + '" target="_blank" class="btn btn-primary btn-lg"><i class="bx bx-download me-2"></i>Download ' + (name || 'File') + '</a>' +
            '</div>'
        );
    }
    $('#transAttachModal').modal('hide');
    $prev.modal('show');
    $prev.one('hidden.bs.modal', function () { $('#transAttachModal').modal('show'); });
}
