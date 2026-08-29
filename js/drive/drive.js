/* global $, CsrfName, CsrfToken, showToastNotification, ajaxLoading, bootstrap, DriveConfig, Swal */
(function () {
    'use strict';

    // ── State ──────────────────────────────────────────────────────────────────

    var CurrentFolderUID = 0;
    var ViewUserUID      = DriveConfig.currentUserUID;
    var ViewMode         = 'grid';   // 'grid' | 'list'
    var DragItemUID      = null;     // UID of item being dragged (drive-to-drive)
    var ItemData         = {};       // uid → item object (populated on each load)
    var CurrentItems     = [];       // last-loaded items list (for view toggle re-render without refetch)
    var _dropCount       = 0;        // nested dragenter/dragleave counter

    // Move modal state
    var _moveItemUID    = 0;
    var _moveFolderUID  = 0;
    var _moveFolderStack = [];

    // Cached Bootstrap modal instances
    var _bsNewFolder, _bsRename, _bsMove;

    // ── Bootstrap modal helpers ────────────────────────────────────────────────

    /**
     * @param {string} id
     * @returns {bootstrap.Modal}
     */
    function _modal(id) {
        var el = document.getElementById(id);
        if (!el) return null;
        return bootstrap.Modal.getInstance(el) || new bootstrap.Modal(el);
    }

    // ── Icon helper ────────────────────────────────────────────────────────────

    /**
     * @param {string|null} mime
     * @param {string}      type  'Folder' | 'File'
     * @returns {{icon: string, color: string}}
     */
    function fileIcon(mime, type) {
        if (type === 'Folder') return { icon: 'bxs-folder',  color: '#f59e0b' };
        var m = (mime || '').toLowerCase();
        if (m.indexOf('image/')       === 0) return { icon: 'bx-image',   color: '#0ea5e9' };
        if (m === 'application/pdf')         return { icon: 'bx-file',    color: '#dc2626' };
        if (m.indexOf('video/')       === 0) return { icon: 'bx-video',   color: '#7c3aed' };
        if (m.indexOf('audio/')       === 0) return { icon: 'bxs-music',  color: '#16a34a' };
        if (m.indexOf('text/')        === 0) return { icon: 'bx-code-alt',color: '#475569' };
        if (m.indexOf('spreadsheet') !== -1 ||
            m.indexOf('excel')       !== -1 ||
            m === 'text/csv')                return { icon: 'bx-table',   color: '#16a34a' };
        if (m.indexOf('document')    !== -1 ||
            m.indexOf('word')        !== -1) return { icon: 'bxs-file',   color: '#2563eb' };
        if (m.indexOf('zip')         !== -1 ||
            m.indexOf('compressed') !== -1 ||
            m.indexOf('archive')    !== -1)  return { icon: 'bx-archive', color: '#ea580c' };
        return { icon: 'bx-file', color: '#64748b' };
    }

    /**
     * @param {number|null} bytes
     * @returns {string}
     */
    function fmtSize(bytes) {
        if (!bytes || bytes <= 0) return '0 B';
        var u = ['B', 'KB', 'MB', 'GB'];
        var i = Math.floor(Math.log(bytes) / Math.log(1024));
        return (bytes / Math.pow(1024, i)).toFixed(i === 0 ? 0 : 1) + ' ' + u[i];
    }

    /**
     * @param {string|null} dateStr
     * @returns {string}
     */
    function fmtDate(dateStr) {
        if (!dateStr) return '';
        try {
            var d = new Date(dateStr.replace(' ', 'T'));
            return d.toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' });
        } catch (e) { return dateStr.slice(0, 10); }
    }

    /**
     * @param {string} text
     * @returns {string}
     */
    function esc(text) {
        return $('<span>').text(String(text || '')).html();
    }

    // ── Usage meter ────────────────────────────────────────────────────────────

    /**
     * @param {number} usedBytes
     * @param {number} quotaBytes
     * @returns {void}
     */
    function updateUsage(usedBytes, quotaBytes) {
        var pct     = quotaBytes > 0 ? Math.min(100, (usedBytes / quotaBytes) * 100) : 0;
        var usedMB  = (usedBytes  / 1048576).toFixed(1);
        var quotaMB = Math.round(quotaBytes / 1048576);
        $('#driveUsageFill').css('width', pct.toFixed(1) + '%');
        $('#driveUsageLabel').text(usedMB + ' MB of ' + quotaMB + ' MB used');
        if (pct > 90) {
            $('#driveUsageFill').css('background', 'linear-gradient(90deg,#fb923c,#dc2626)');
        } else {
            $('#driveUsageFill').css('background', 'linear-gradient(90deg,#38bdf8,#0369a1)');
        }
    }

    // ── Breadcrumb ─────────────────────────────────────────────────────────────

    /**
     * @param {Array<{DriveItemUID: number, ItemName: string}>} bc
     * @returns {void}
     */
    function updateBreadcrumb(bc) {
        var html = '<a data-uid="0">My Drive</a>';
        if (bc && bc.length) {
            bc.forEach(function (b, i) {
                html += '<span class="bc-sep">›</span>';
                if (i < bc.length - 1) {
                    html += '<a data-uid="' + b.DriveItemUID + '">' + esc(b.ItemName) + '</a>';
                } else {
                    html += '<span class="bc-current">' + esc(b.ItemName) + '</span>';
                }
            });
        }
        $('#driveBreadcrumb').html(html);
    }

    // ── Load folder ────────────────────────────────────────────────────────────

    /**
     * @param {number} folderUID
     * @returns {void}
     */
    function loadItems(folderUID) {
        CurrentFolderUID = folderUID;
        ajaxLoading(0);
        $.ajax({
            url:    '/settings/mydrive/getItems',
            method: 'POST',
            data:   { ParentUID: folderUID, ViewUserUID: ViewUserUID, [CsrfName]: CsrfToken },
            success: function (resp) {
                ajaxLoading(1);
                if (resp.Error) {
                    showToastNotification(resp.Message || 'Failed to load.', 'error');
                    return;
                }
                ItemData = {};
                CurrentItems = resp.Items || [];
                CurrentItems.forEach(function (item) { ItemData[item.uid] = item; });
                updateBreadcrumb(resp.Breadcrumb);
                updateUsage(resp.UsedBytes, resp.QuotaBytes || DriveConfig.quotaBytes);
                renderItems(CurrentItems);
            },
            error: function () {
                ajaxLoading(1);
                showToastNotification('Failed to load drive contents.', 'error');
            }
        });
    }

    // ── Render ─────────────────────────────────────────────────────────────────

    /**
     * @param {Array} items
     * @returns {void}
     */
    function renderItems(items) {
        if (!items.length) {
            $('#driveContent').html(
                '<div class="drive-empty">' +
                '<i class="bx bx-folder-open"></i>' +
                '<p>This folder is empty</p>' +
                '<p class="sub">Drop files here or click Upload</p>' +
                '</div>' +
                '<div class="drive-drop-overlay" id="driveDropOverlay"><div class="drive-drop-label"><i class="bx bx-cloud-upload" style="font-size:1.3rem;"></i> Drop files here to upload</div></div>'
            );
            return;
        }
        if (ViewMode === 'list') {
            _renderList(items);
        } else {
            _renderGrid(items);
        }
    }

    /**
     * @param {Array} items
     * @returns {void}
     */
    function _renderGrid(items) {
        var html = '<div class="drive-grid">';
        items.forEach(function (item) { html += _gridItem(item); });
        html += '</div><div class="drive-drop-overlay" id="driveDropOverlay"><div class="drive-drop-label"><i class="bx bx-cloud-upload" style="font-size:1.3rem;"></i> Drop files here to upload</div></div>';
        $('#driveContent').html(html);
    }

    /**
     * @param {Object} item
     * @returns {string}
     */
    function _gridItem(item) {
        var fi       = fileIcon(item.mime, item.type);
        var isFolder = item.type === 'Folder';
        var meta     = isFolder ? '' : fmtSize(item.size);
        return '<div class="drive-item" data-uid="' + item.uid + '" data-type="' + item.type + '"' +
               ' draggable="true">' +
               '<div class="drive-item-icon"><i class="bx ' + fi.icon + '" style="color:' + fi.color + ';"></i></div>' +
               '<div class="drive-item-name" title="' + esc(item.name) + '">' + esc(item.name) + '</div>' +
               (meta ? '<div class="drive-item-meta">' + meta + '</div>' : '') +
               '<div class="drive-item-actions">' +
               (isFolder ? '' : '<button class="drive-act-btn" data-da="preview" data-uid="' + item.uid + '" title="Preview"><i class="bx bx-show-alt"></i></button>') +
               (isFolder ? '' : '<button class="drive-act-btn" data-da="download" data-uid="' + item.uid + '" title="Download"><i class="bx bx-download"></i></button>') +
               '<button class="drive-act-btn" data-da="rename" data-uid="' + item.uid + '" title="Rename"><i class="bx bx-rename"></i></button>' +
               '<button class="drive-act-btn" data-da="move" data-uid="' + item.uid + '" title="Move to…"><i class="bx bx-move"></i></button>' +
               '<button class="drive-act-btn del" data-da="delete" data-uid="' + item.uid + '" title="Delete"><i class="bx bx-trash"></i></button>' +
               '</div>' +
               '</div>';
    }

    /**
     * @param {Array} items
     * @returns {void}
     */
    function _renderList(items) {
        var html = '<div class="table-responsive"><table class="drive-list-tbl">' +
            '<thead><tr><th style="width:32px;"></th><th>Name</th><th style="width:90px;">Size</th><th style="width:110px;">Created</th><th style="width:120px;"></th></tr></thead><tbody>';
        items.forEach(function (item) {
            var fi       = fileIcon(item.mime, item.type);
            var isFolder = item.type === 'Folder';
            html += '<tr data-uid="' + item.uid + '" data-type="' + item.type + '"' + (isFolder ? ' style="cursor:pointer;"' : '') + '>' +
                '<td><i class="bx ' + fi.icon + '" style="color:' + fi.color + ';font-size:1.1rem;"></i></td>' +
                '<td><span style="font-weight:600;">' + esc(item.name) + '</span></td>' +
                '<td style="color:#94a3b8;">' + (item.size ? fmtSize(item.size) : '-') + '</td>' +
                '<td style="color:#94a3b8;">' + fmtDate(item.createdOn) + '</td>' +
                '<td class="row-acts">' +
                (isFolder ? '' : '<button class="drive-act-btn me-1" data-da="preview"  data-uid="' + item.uid + '" title="Preview"><i class="bx bx-show-alt"></i></button>') +
                (isFolder ? '' : '<button class="drive-act-btn me-1" data-da="download" data-uid="' + item.uid + '" title="Download"><i class="bx bx-download"></i></button>') +
                '<button class="drive-act-btn me-1" data-da="rename"  data-uid="' + item.uid + '" title="Rename"><i class="bx bx-rename"></i></button>' +
                '<button class="drive-act-btn me-1" data-da="move"    data-uid="' + item.uid + '" title="Move to…"><i class="bx bx-move"></i></button>' +
                '<button class="drive-act-btn del"  data-da="delete"  data-uid="' + item.uid + '" title="Delete"><i class="bx bx-trash"></i></button>' +
                '</td></tr>';
        });
        html += '</tbody></table></div>' +
            '<div class="drive-drop-overlay" id="driveDropOverlay"><div class="drive-drop-label"><i class="bx bx-cloud-upload" style="font-size:1.3rem;"></i> Drop files here to upload</div></div>';
        $('#driveContent').html(html);
    }

    /**
     * @param {DragEvent} e
     * @returns {boolean}
     */
    function _isFileDrag(e) {
        var dt = (e.originalEvent && e.originalEvent.dataTransfer) || e.dataTransfer;
        return !!(dt && dt.types && (dt.types.indexOf('Files') !== -1 || dt.types.indexOf('application/x-moz-file') !== -1));
    }

    // ── Upload ─────────────────────────────────────────────────────────────────

    /**
     * @param {FileList} files
     * @returns {void}
     */
    function uploadFiles(files) {
        if (!files || !files.length) return;

        var fd = new FormData();
        for (var i = 0; i < files.length; i++) fd.append('Files[]', files[i]);
        fd.append('ParentUID',   CurrentFolderUID);
        fd.append('ViewUserUID', ViewUserUID);
        fd.append(CsrfName,      CsrfToken);

        var $toast = $('#driveUploadToast');
        $('#uploadBar').css('width', '0%');
        $('#uploadPct').text('0%');
        $toast.addClass('show');

        $.ajax({
            url: '/settings/mydrive/uploadFile',
            method: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            xhr: function () {
                var x = new XMLHttpRequest();
                x.upload.addEventListener('progress', function (e) {
                    if (e.lengthComputable) {
                        var pct = Math.round((e.loaded / e.total) * 100);
                        $('#uploadBar').css('width', pct + '%');
                        $('#uploadPct').text(pct + '%');
                    }
                });
                return x;
            },
            success: function (resp) {
                $toast.removeClass('show');
                $('#driveFileInput').val('');
                if (resp.Error) { showToastNotification(resp.Message || 'Upload failed.', 'error'); return; }
                if (resp.Added > 0) showToastNotification(resp.Added + ' file' + (resp.Added > 1 ? 's' : '') + ' uploaded.', 'success');
                if (resp.Errors && resp.Errors.length) showToastNotification(resp.Errors[0], 'warning');
                if (resp.UsedBytes !== undefined) updateUsage(resp.UsedBytes, DriveConfig.quotaBytes);
                loadItems(CurrentFolderUID);
            },
            error: function () {
                $toast.removeClass('show');
                showToastNotification('Upload failed. Please try again.', 'error');
            }
        });
    }

    // ── Create folder ──────────────────────────────────────────────────────────

    /**
     * @returns {void}
     */
    function saveNewFolder() {
        var name = $.trim($('#newFolderName').val());
        if (!name) { $('#newFolderName').focus(); return; }
        ajaxLoading(0);
        $.ajax({
            url: '/settings/mydrive/createFolder',
            method: 'POST',
            data: { FolderName: name, ParentUID: CurrentFolderUID, ViewUserUID: ViewUserUID, [CsrfName]: CsrfToken },
            success: function (resp) {
                ajaxLoading(1);
                if (resp.Error) { showToastNotification(resp.Message, 'error'); return; }
                (_bsNewFolder = _bsNewFolder || _modal('driveNewFolderModal')).hide();
                showToastNotification('Folder created.', 'success');
                loadItems(CurrentFolderUID);
            },
            error: function () { ajaxLoading(1); showToastNotification('Failed to create folder.', 'error'); }
        });
    }

    // ── Rename ─────────────────────────────────────────────────────────────────

    /**
     * @param {number} uid
     * @param {string} currentName
     * @returns {void}
     */
    function openRename(uid, currentName) {
        $('#renameUID').val(uid);
        $('#renameInput').val(currentName);
        (_bsRename = _bsRename || _modal('driveRenameModal')).show();
        setTimeout(function () { $('#renameInput').select(); }, 300);
    }

    /**
     * @returns {void}
     */
    function saveRename() {
        var uid     = parseInt($('#renameUID').val(), 10);
        var newName = $.trim($('#renameInput').val());
        if (!newName) return;
        ajaxLoading(0);
        $.ajax({
            url: '/settings/mydrive/renameItem',
            method: 'POST',
            data: { DriveItemUID: uid, NewName: newName, ViewUserUID: ViewUserUID, [CsrfName]: CsrfToken },
            success: function (resp) {
                ajaxLoading(1);
                if (resp.Error) { showToastNotification(resp.Message, 'error'); return; }
                (_bsRename || _modal('driveRenameModal')).hide();
                showToastNotification('Renamed successfully.', 'success');
                loadItems(CurrentFolderUID);
            },
            error: function () { ajaxLoading(1); showToastNotification('Failed to rename.', 'error'); }
        });
    }

    // ── Delete ─────────────────────────────────────────────────────────────────

    /**
     * @param {number} uid
     * @param {string} name
     * @param {string} type
     * @returns {void}
     */
    function confirmDelete(uid, name, type) {
        var isFolder = type === 'Folder';
        Swal.fire({
            title:             'Delete ' + (isFolder ? 'Folder' : 'File') + '?',
            html:              '"<strong>' + esc(name) + '</strong>"' + (isFolder ? ' and all its contents' : '') + ' will be permanently deleted.',
            icon:              'warning',
            showCancelButton:  true,
            confirmButtonText: 'Delete',
            confirmButtonColor: '#dc2626',
            cancelButtonText:  'Cancel',
        }).then(function (r) {
            if (!r.isConfirmed) return;
            ajaxLoading(0);
            $.ajax({
                url: '/settings/mydrive/deleteItem',
                method: 'POST',
                data: { DriveItemUID: uid, ViewUserUID: ViewUserUID, [CsrfName]: CsrfToken },
                success: function (resp) {
                    ajaxLoading(1);
                    if (resp.Error) { showToastNotification(resp.Message, 'error'); return; }
                    showToastNotification(resp.Message || 'Deleted.', 'success');
                    if (resp.UsedBytes !== undefined) updateUsage(resp.UsedBytes, DriveConfig.quotaBytes);
                    loadItems(CurrentFolderUID);
                },
                error: function () { ajaxLoading(1); showToastNotification('Delete failed.', 'error'); }
            });
        });
    }

    // ── Preview ────────────────────────────────────────────────────────────────

    /**
     * @param {number} uid
     * @returns {void}
     */
    function openPreview(uid) {
        var item = ItemData[uid];
        if (!item || item.type !== 'File') return;

        var fileUrl = DriveConfig.cdnUrl + (item.path || '');
        var mime    = (item.mime || '').toLowerCase();
        var body;

        if (mime.indexOf('image/') === 0) {
            body = '<img src="' + fileUrl + '" style="max-width:100%;max-height:calc(92vh - 44px);display:block;margin:auto;object-fit:contain;">';
        } else if (mime === 'application/pdf') {
            body = '<embed src="' + fileUrl + '" type="application/pdf" style="width:100%;height:calc(92vh - 44px);border:none;">';
        } else if (mime.indexOf('video/') === 0) {
            body = '<video src="' + fileUrl + '" controls style="max-width:100%;max-height:calc(92vh - 44px);display:block;margin:auto;"></video>';
        } else if (mime.indexOf('audio/') === 0) {
            body = '<div style="padding:40px;text-align:center;"><audio src="' + fileUrl + '" controls style="width:100%;"></audio></div>';
        } else {
            body = '<div style="text-align:center;padding:60px 20px;">' +
                '<i class="bx bx-file" style="font-size:3rem;color:rgba(255,255,255,.4);display:block;margin-bottom:16px;"></i>' +
                '<p style="color:rgba(255,255,255,.6);margin-bottom:16px;">' + esc(item.name) + '</p>' +
                '<a href="/settings/mydrive/downloadFile/' + uid + '" class="btn btn-sm btn-light"><i class="bx bx-download me-1"></i>Download</a>' +
                '</div>';
        }

        $('#attachPreviewTitle').text(item.name);
        $('#attachPreviewBody').html(body);
        bootstrap.Modal.getOrCreateInstance(document.getElementById('attachPreviewModal')).show();
    }

    // ── Move modal ─────────────────────────────────────────────────────────────

    /**
     * @param {number} uid
     * @param {string} name
     * @returns {void}
     */
    function openMoveModal(uid, name) {
        _moveItemUID    = uid;
        _moveFolderUID  = 0;
        _moveFolderStack = [];
        $('#moveItemLabel').text('Moving: ' + name);
        _loadMoveFolder(0);
        (_bsMove = _bsMove || _modal('driveMoveModal')).show();
    }

    /**
     * @param {number} parentUID
     * @returns {void}
     */
    function _loadMoveFolder(parentUID) {
        _moveFolderUID = parentUID;
        $('#moveFolderList').html('<div class="text-center py-3"><span class="spinner-border spinner-border-sm text-primary"></span></div>');
        ajaxLoading(0);
        $.ajax({
            url: '/settings/mydrive/getItems',
            method: 'POST',
            data: { ParentUID: parentUID, ViewUserUID: ViewUserUID, [CsrfName]: CsrfToken },
            success: function (resp) {
                ajaxLoading(1);
                if (resp.Error) return;
                var folders = (resp.Items || []).filter(function (i) { return i.type === 'Folder' && i.uid !== _moveItemUID; });
                var html = '';
                if (!folders.length) {
                    html = '<p class="text-muted text-center py-3 mb-0" style="font-size:.8rem;">No subfolders here</p>';
                } else {
                    folders.forEach(function (f) {
                        html += '<div class="move-folder-item" data-mfuid="' + f.uid + '" data-mfname="' + esc(f.name) + '">' +
                            '<i class="bx bxs-folder"></i><span class="mf-name">' + esc(f.name) + '</span>' +
                            '<i class="bx bx-chevron-right mf-chev"></i></div>';
                    });
                }
                $('#moveFolderList').html(html);
                var bc  = resp.Breadcrumb || [];
                var lbl = parentUID === 0 ? 'My Drive (root)' : bc.map(function (b) { return b.ItemName; }).join(' / ');
                $('#moveBcLabel').text(lbl);
                $('#moveFolderBackBtn').toggle(parentUID !== 0);
            }
        });
    }

    /**
     * @returns {void}
     */
    function executeMoveHere() {
        ajaxLoading(0);
        $.ajax({
            url: '/settings/mydrive/moveItem',
            method: 'POST',
            data: { DriveItemUID: _moveItemUID, NewParentUID: _moveFolderUID, ViewUserUID: ViewUserUID, [CsrfName]: CsrfToken },
            success: function (resp) {
                ajaxLoading(1);
                if (resp.Error) { showToastNotification(resp.Message, 'error'); return; }
                (_bsMove || _modal('driveMoveModal')).hide();
                showToastNotification('Moved successfully.', 'success');
                loadItems(CurrentFolderUID);
            },
            error: function () { ajaxLoading(1); showToastNotification('Move failed.', 'error'); }
        });
    }

    // ── Desktop file drag & drop (bound once — #driveContent persists across renders) ──

    $(document).on('dragenter', '#driveContent', function (e) {
        if (!_isFileDrag(e)) return;
        _dropCount++;
        var ov = document.getElementById('driveDropOverlay');
        if (ov) ov.classList.add('show');
    });

    $(document).on('dragleave', '#driveContent', function (e) {
        if (!_isFileDrag(e)) return;
        _dropCount--;
        if (_dropCount <= 0) {
            _dropCount = 0;
            var ov = document.getElementById('driveDropOverlay');
            if (ov) ov.classList.remove('show');
        }
    });

    $(document).on('dragover', '#driveContent', function (e) {
        if (!_isFileDrag(e)) return;
        e.preventDefault();
    });

    $(document).on('drop', '#driveContent', function (e) {
        _dropCount = 0;
        var ov = document.getElementById('driveDropOverlay');
        if (ov) ov.classList.remove('show');
        if (!_isFileDrag(e)) return;
        e.preventDefault();
        var files = e.originalEvent.dataTransfer.files;
        if (files && files.length) uploadFiles(files);
    });

    // ── Drive-to-drive drag & drop ─────────────────────────────────────────────

    $(document).on('dragstart', '#driveContent .drive-item', function (e) {
        DragItemUID = parseInt($(this).data('uid'), 10);
        if (e.originalEvent.dataTransfer) {
            e.originalEvent.dataTransfer.effectAllowed = 'move';
            e.originalEvent.dataTransfer.setData('text/plain', String(DragItemUID));
        }
        $(this).css('opacity', '.45');
    });

    $(document).on('dragend', '#driveContent .drive-item', function () {
        DragItemUID = null;
        $(this).css('opacity', '1');
        $('#driveContent .drive-item').removeClass('drag-hover');
    });

    $(document).on('dragover', '#driveContent .drive-item[data-type="Folder"]', function (e) {
        if (DragItemUID === null) return;
        e.preventDefault();
        e.stopPropagation();
        var targetUID = parseInt($(this).data('uid'), 10);
        if (targetUID !== DragItemUID) $(this).addClass('drag-hover');
    });

    $(document).on('dragleave', '#driveContent .drive-item[data-type="Folder"]', function () {
        $(this).removeClass('drag-hover');
    });

    $(document).on('drop', '#driveContent .drive-item[data-type="Folder"]', function (e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).removeClass('drag-hover');
        if (DragItemUID === null) return;
        var targetUID  = parseInt($(this).data('uid'), 10);
        var targetItem = ItemData[targetUID];
        if (!targetItem || targetUID === DragItemUID) return;

        ajaxLoading(0);
        $.ajax({
            url: '/settings/mydrive/moveItem',
            method: 'POST',
            data: { DriveItemUID: DragItemUID, NewParentUID: targetUID, ViewUserUID: ViewUserUID, [CsrfName]: CsrfToken },
            success: function (resp) {
                ajaxLoading(1);
                if (resp.Error) { showToastNotification(resp.Message, 'error'); return; }
                showToastNotification('Moved to "' + (targetItem.name || '') + '".', 'success');
                loadItems(CurrentFolderUID);
            },
            error: function () { ajaxLoading(1); showToastNotification('Move failed.', 'error'); }
        });
        DragItemUID = null;
    });

    // ── Event listeners ────────────────────────────────────────────────────────

    // Breadcrumb click
    $(document).on('click', '#driveBreadcrumb a[data-uid]', function () {
        ajaxLoading(0);
        loadItems(parseInt($(this).data('uid'), 10));
    });

    // Grid: double-click folder to open
    $(document).on('dblclick', '#driveContent .drive-grid .drive-item[data-type="Folder"]', function (e) {
        if ($(e.target).closest('[data-da]').length) return;
        ajaxLoading(0);
        loadItems(parseInt($(this).data('uid'), 10));
    });

    // Grid: click file to preview
    $(document).on('click', '#driveContent .drive-grid .drive-item[data-type="File"]', function (e) {
        if ($(e.target).closest('[data-da]').length) return;
        openPreview(parseInt($(this).data('uid'), 10));
    });

    // List: click folder row to navigate
    $(document).on('click', '#driveContent .drive-list-tbl tbody tr[data-type="Folder"]', function (e) {
        if ($(e.target).closest('[data-da]').length) return;
        ajaxLoading(0);
        loadItems(parseInt($(this).data('uid'), 10));
    });

    // List: click file row to preview
    $(document).on('click', '#driveContent .drive-list-tbl tbody tr[data-type="File"]', function (e) {
        if ($(e.target).closest('[data-da]').length) return;
        openPreview(parseInt($(this).data('uid'), 10));
    });

    // Action buttons [data-da]
    $(document).on('click', '[data-da]', function (e) {
        e.stopPropagation();
        var action = $(this).data('da');
        var uid    = parseInt($(this).data('uid'), 10);
        var item   = ItemData[uid] || {};

        if (action === 'preview')  { openPreview(uid); return; }
        if (action === 'download') { window.location.href = '/settings/mydrive/downloadFile/' + uid; return; }
        if (action === 'rename')   { openRename(uid, item.name || ''); return; }
        if (action === 'move')     { openMoveModal(uid, item.name || ''); return; }
        if (action === 'delete')   { confirmDelete(uid, item.name || '', item.type || 'File'); return; }
    });

    // New folder button
    $('#driveNewFolderBtn').on('click', function () {
        $('#newFolderName').val('');
        (_bsNewFolder = _bsNewFolder || _modal('driveNewFolderModal')).show();
        setTimeout(function () { $('#newFolderName').focus(); }, 300);
    });

    // New folder — Enter key + button
    $('#driveNewFolderModal').on('keydown', function (e) { if (e.key === 'Enter') saveNewFolder(); });
    $('#driveCreateFolderBtn').on('click', saveNewFolder);

    // Rename — Enter key + button
    $('#driveRenameModal').on('keydown', function (e) { if (e.key === 'Enter') saveRename(); });
    $('#driveRenameSaveBtn').on('click', saveRename);

    // File upload via input
    $('#driveFileInput').on('change', function () {
        if (this.files.length) uploadFiles(this.files);
    });

    // Refresh current folder
    $('#driveRefreshBtn').on('click', function () {
        loadItems(CurrentFolderUID);
    });

    // View toggle — re-render cached items; no server round-trip needed
    $('#driveViewToggle').on('click', function () {
        ViewMode = (ViewMode === 'grid') ? 'list' : 'grid';
        $('#driveViewIcon').attr('class', ViewMode === 'list' ? 'bx bx-grid-alt' : 'bx bx-list-ul');
        renderItems(CurrentItems);
    });

    // Super admin user picker
    $('#driveUserPicker').on('change', function () {
        ViewUserUID      = parseInt($(this).val(), 10) || DriveConfig.currentUserUID;
        CurrentFolderUID = 0;
        loadItems(0);
    });

    // Move modal — drill into subfolder
    $(document).on('click', '#moveFolderList .move-folder-item', function () {
        var uid = parseInt($(this).data('mfuid'), 10);
        _moveFolderStack.push(_moveFolderUID);
        _loadMoveFolder(uid);
    });

    // Move modal — back
    $('#moveFolderBackBtn').on('click', function () {
        var prev = _moveFolderStack.pop();
        _loadMoveFolder(prev !== undefined ? prev : 0);
    });

    // Move modal — confirm
    $('#driveMoveHereBtn').on('click', executeMoveHere);

    // ── Init ───────────────────────────────────────────────────────────────────

    loadItems(0);

})();
