// Shared attachment handling for all transaction edit forms.
// Requires: jQuery, SweetAlert2, CDN_URL global (optional).

var _removedAttachIDs = [];

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
        var encUrl   = encodeURIComponent(fullUrl);
        var $item    = $('<div class="d-flex align-items-center gap-1 border rounded px-2 py-1 bg-light existing-attach-item" style="font-size:.78rem;max-width:220px;" data-uid="' + uid + '">' +
            '<i class="bx ' + iconCls + '" style="font-size:1rem;flex-shrink:0;cursor:pointer;" onclick="_openAttachPreview(\'' + encUrl + '\',\'' + (isImg ? 'img' : (isPdf ? 'pdf' : 'file')) + '\',\'' + safeName.replace(/'/g, "\\'") + '\')"></i>' +
            '<span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;flex:1;cursor:pointer;" title="' + safeName + '" onclick="_openAttachPreview(\'' + encUrl + '\',\'' + (isImg ? 'img' : (isPdf ? 'pdf' : 'file')) + '\',\'' + safeName.replace(/'/g, "\\'") + '\')">' + safeName + '</span>' +
            '<button type="button" class="btn-close btn-close-sm ms-1 remove-attach-btn" style="font-size:.6rem;" title="Remove" data-uid="' + uid + '"></button>' +
        '</div>');
        $container.append($item);
    });
    $('#existingAttachList').removeClass('d-none');
    $('#existingAttachCount').text(attachments.length).removeClass('d-none');
    $('#accordionUploadFiles').addClass('show');
}

function _bindRemoveHandler() {
    $(document).on('click', '.remove-attach-btn', function() {
        var attachUID = parseInt($(this).data('uid'), 10);
        $(this).closest('.existing-attach-item').remove();
        if (_removedAttachIDs.indexOf(attachUID) === -1) _removedAttachIDs.push(attachUID);
        var remaining = $('#existingAttachItems .existing-attach-item').length;
        if (remaining === 0) $('#existingAttachList').addClass('d-none');
        if (remaining > 0) $('#existingAttachCount').text(remaining);
        else $('#existingAttachCount').addClass('d-none');
    });
}

// Call this when attachment data is already available from PHP (no AJAX needed).
function renderTransAttachmentsFromData(attachments) {
    _removedAttachIDs = [];
    if (attachments && attachments.length) {
        _renderAttachmentChips(attachments);
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
            if (resp.Error || !resp.Attachments || !resp.Attachments.length) return;
            _renderAttachmentChips(resp.Attachments);
        }
    });
    _bindRemoveHandler();
}

function _openAttachPreview(encUrl, type, name) {
    var url      = decodeURIComponent(encUrl);
    var safeName = $('<span>').text(name).html();
    $('#attachPreviewTitle').text(name || 'Preview');
    var body = '';
    if (type === 'img') {
        body = '<div class="text-center p-3"><img src="' + $('<span>').text(url).html() + '" class="img-fluid rounded" style="max-height:70vh;" alt="' + safeName + '"></div>';
    } else if (type === 'pdf') {
        body = '<iframe src="' + $('<span>').text(url).html() + '" style="width:100%;height:70vh;border:none;"></iframe>';
    } else {
        body = '<div class="text-center py-5">' +
            '<i class="bx bx-file-blank text-secondary" style="font-size:4rem;display:block;margin-bottom:12px;"></i>' +
            '<div style="font-size:.9rem;font-weight:600;margin-bottom:16px;">' + safeName + '</div>' +
            '<a href="' + $('<span>').text(url).html() + '" download="' + safeName + '" class="btn btn-primary px-4"><i class="bx bx-download me-2"></i>Download File</a>' +
            '</div>';
    }
    $('#attachPreviewBody').html(body);
    var previewModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('attachPreviewModal'));
    previewModal.show();
}
