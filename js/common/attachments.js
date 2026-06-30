/**
 * Shared multi-file attachment zone — Products, Categories, Customers, Vendors,
 * Transactions, Payments, Expenses, Indirect Income.
 *
 * Configuration lives in Global.ModuleAttachmentCfgTbl (DB) and is pushed to
 * the page as window._attachCfgSettings by footer_script.php.  The JS defaults
 * below are overridden at DOM-ready by whatever the DB says, so a developer only
 * needs to UPDATE that table row (and flush the Upstash key) to change any limit.
 *
 * Zone trigger (inline onclick on the zone div):
 *   _attachZoneTrigger('Customer', event)
 *
 * Init on first open:   _attachInit('Customer')
 * Reset between opens:  _attachResetState('Customer')
 * Load existing files:  _attachState['Customer'].existing = arr; _attachRender('Customer');
 * Append to FormData:   (_attachState['Customer'].newFiles||[]).forEach(f => fd.append('CustAttachFiles[]',f,f.name));
 */

// ── Default configuration per slot ───────────────────────────────────────────
// maxFileSizeMB = per-file cap   maxTotalMB = cap across all files combined
// acceptedTypes = 'images' (JPG/PNG/GIF) | 'all' (any file)
var _attachCfg = {
    Product: {
        maxFiles: 5, maxFileSizeMB: 5, maxTotalMB: 5,
        acceptedTypes: 'images', enabled: true,
        inputId:     'prodAttachInput',
        zoneId:      'prodAttachZone',
        listId:      'prodAttachList',
        emptyId:     'prodAttachEmpty',
        labelId:     'prodAttachLabel',
        hintId:      'prodAttachHint',
        iconId:      'prodAttachIcon',
        deleteField: 'prodAttachDeleteUIDs',
    },
    Category: {
        maxFiles: 3, maxFileSizeMB: 3, maxTotalMB: 3,
        acceptedTypes: 'images', enabled: true,
        inputId:     'catgAttachInput',
        zoneId:      'catgAttachZone',
        listId:      'catgAttachList',
        emptyId:     'catgAttachEmpty',
        labelId:     'catgAttachLabel',
        hintId:      'catgAttachHint',
        iconId:      'catgAttachIcon',
        deleteField: 'catgAttachDeleteUIDs',
    },
    Customer: {
        maxFiles: 3, maxFileSizeMB: 3, maxTotalMB: 3,
        acceptedTypes: 'images', enabled: true,
        inputId:     'custAttachInput',
        zoneId:      'custAttachZone',
        listId:      'custAttachList',
        emptyId:     'custAttachEmpty',
        labelId:     'custAttachLabel',
        hintId:      'custAttachHint',
        iconId:      'custAttachIcon',
        deleteField: 'custAttachDeleteUIDs',
    },
    Vendor: {
        maxFiles: 3, maxFileSizeMB: 3, maxTotalMB: 3,
        acceptedTypes: 'images', enabled: true,
        inputId:     'vendAttachInput',
        zoneId:      'vendAttachZone',
        listId:      'vendAttachList',
        emptyId:     'vendAttachEmpty',
        labelId:     'vendAttachLabel',
        hintId:      'vendAttachHint',
        iconId:      'vendAttachIcon',
        deleteField: 'vendAttachDeleteUIDs',
    },
    Transaction: {
        maxFiles: 5, maxFileSizeMB: 3, maxTotalMB: 15,
        acceptedTypes: 'all', enabled: true,
        inputId:     'transAttachInput',
        zoneId:      'transAttachZone',
        listId:      'transAttachList',
        emptyId:     'transAttachEmpty',
        labelId:     'transAttachLabel',
        hintId:      'transAttachHint',
        iconId:      'transAttachIcon',
        deleteField: 'RemovedAttachIDs',  // matches PHP _softDeleteAttachments field name
    },
    Payment: {
        maxFiles: 3, maxFileSizeMB: 3, maxTotalMB: 9,
        acceptedTypes: 'all', enabled: true,
        inputId:     'payAttachInput',
        zoneId:      'payAttachZone',
        listId:      'payAttachList',
        emptyId:     'payAttachEmpty',
        labelId:     'payAttachLabel',
        hintId:      'payAttachHint',
        iconId:      'payAttachIcon',
        deleteField: 'payAttachDeleteUIDs',
    },
    Expense: {
        maxFiles: 5, maxFileSizeMB: 3, maxTotalMB: 15,
        acceptedTypes: 'all', enabled: true,
        inputId:     'expAttachInput',
        zoneId:      'expAttachZone',
        listId:      'expAttachList',
        emptyId:     'expAttachEmpty',
        labelId:     'expAttachLabel',
        hintId:      'expAttachHint',
        iconId:      'expAttachIcon',
        deleteField: 'expAttachDeleteUIDs',
    },
    IndirectIncome: {
        maxFiles: 5, maxFileSizeMB: 3, maxTotalMB: 15,
        acceptedTypes: 'all', enabled: true,
        inputId:     'incAttachInput',
        zoneId:      'incAttachZone',
        listId:      'incAttachList',
        emptyId:     'incAttachEmpty',
        labelId:     'incAttachLabel',
        hintId:      'incAttachHint',
        iconId:      'incAttachIcon',
        deleteField: 'incAttachDeleteUIDs',
    },
};

var _attachState    = {};   // { SlotKey: { newFiles:[], existing:[], toDelete:[] } }
var _attachBlobUrls = {};   // stable blob URL cache per slot

// ── Apply JWT config at DOM-ready ─────────────────────────────────────────────
// AttachCfg is loaded once at login and lives in the JWT/Redis session.
// JwtData is already a global set by footer_script.php on every page.
$(function () {
    if (typeof JwtData === 'undefined' || !JwtData || !JwtData.AttachCfg) return;
    Object.keys(JwtData.AttachCfg).forEach(function (key) {
        if (!_attachCfg[key]) return;
        var s = JwtData.AttachCfg[key];
        if (s.IsEnabled      !== undefined) _attachCfg[key].enabled       = !!s.IsEnabled;
        if (s.MaxFiles)                     _attachCfg[key].maxFiles       = parseInt(s.MaxFiles, 10);
        if (s.MaxFileSizeMB  !== undefined) _attachCfg[key].maxFileSizeMB  = parseFloat(s.MaxFileSizeMB);
        if (s.MaxTotalSizeMB)               _attachCfg[key].maxTotalMB     = parseFloat(s.MaxTotalSizeMB);
        if (s.AllowMultiple  !== undefined) _attachCfg[key].allowMultiple  = !!s.AllowMultiple;
        if (s.AcceptedTypes)                _attachCfg[key].acceptedTypes  = s.AcceptedTypes;
    });
});

// ── Helpers ───────────────────────────────────────────────────────────────────

function _attachAllowedMimes(entityType) {
    var cfg = _attachCfg[entityType];
    if (!cfg || cfg.acceptedTypes === 'all') return null; // null = accept everything
    return ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
}

function _attachTypeHint(entityType) {
    var cfg = _attachCfg[entityType];
    return (cfg && cfg.acceptedTypes === 'all') ? 'Images, PDF or any file' : 'JPG, GIF or PNG';
}

function _attachIsImage(mimeOrName) {
    if (!mimeOrName) return false;
    var s = mimeOrName.toLowerCase();
    return s.indexOf('image/') === 0 || /\.(jpg|jpeg|png|gif|webp|bmp|svg)$/.test(s);
}

// ── Reset state only (never touches listeners) ────────────────────────────────
function _attachResetState(entityType) {
    _attachResetBlobUrls(entityType);
    _attachState[entityType] = { newFiles: [], existing: [], toDelete: [] };
    var cfg = _attachCfg[entityType];
    if (!cfg) return;
    var df = document.getElementById(cfg.deleteField);
    if (df) df.value = '';
    var list  = document.getElementById(cfg.listId);
    var empty = document.getElementById(cfg.emptyId);
    var label = document.getElementById(cfg.labelId);
    var hint  = document.getElementById(cfg.hintId);
    var icon  = document.getElementById(cfg.iconId);
    if (list)  { list.innerHTML = ''; list.style.display = 'none'; }
    if (empty) empty.style.display = '';
    if (label) label.textContent = 'Drag & drop files here';
    if (hint)  hint.textContent  = _attachTypeHint(entityType) + ' · Max ' + cfg.maxFiles + ' · ' + cfg.maxTotalMB + ' MB total';
    if (icon)  { icon.className = 'bx bx-upload'; icon.style.color = '#9ca3af'; }
}

// ── Bind listeners ONCE per zone (guarded by dataset flag) ────────────────────
function _attachBindListeners(entityType) {
    var cfg = _attachCfg[entityType];
    if (!cfg) return;
    var zone  = document.getElementById(cfg.zoneId);
    var input = document.getElementById(cfg.inputId);
    if (!zone || !input) return;
    if (zone.dataset.attachBound === '1') return;

    // Set accept attribute from config
    if (cfg.acceptedTypes !== 'all') {
        input.setAttribute('accept', 'image/jpeg,image/png,image/gif');
    } else {
        input.removeAttribute('accept');
    }
    // Reflect AllowMultiple
    if (cfg.allowMultiple !== false) {
        input.setAttribute('multiple', 'multiple');
    } else {
        input.removeAttribute('multiple');
    }

    zone.dataset.attachBound = '1';
    zone.addEventListener('dragover',  function (e) { e.preventDefault(); zone.classList.add('drag-over'); });
    zone.addEventListener('dragleave', function ()  { zone.classList.remove('drag-over'); });
    zone.addEventListener('drop', function (e) {
        e.preventDefault();
        zone.classList.remove('drag-over');
        _attachHandleFiles(entityType, e.dataTransfer.files);
    });
    input.addEventListener('change', function () {
        _attachHandleFiles(entityType, this.files);
        this.value = '';
    });
}

// ── Convenience: reset + bind (first-time init) ───────────────────────────────
function _attachInit(entityType) {
    _attachResetState(entityType);
    _attachBindListeners(entityType);
}

// ── Zone click trigger (use in onclick on the zone div) ───────────────────────
function _attachZoneTrigger(entityType, e) {
    var cfg   = _attachCfg[entityType];
    if (!cfg || cfg.enabled === false) return;
    var state = _attachState[entityType] || { newFiles: [], existing: [], toDelete: [] };
    var active = (state.existing || []).filter(function (x) {
        return !(state.toDelete || []).includes(x.AttachUID);
    }).length;
    var total = active + (state.newFiles || []).length;
    if (total >= cfg.maxFiles) {
        showToastNotification('Maximum ' + cfg.maxFiles + ' files allowed.', 'error');
        return;
    }
    if (e && e.target && e.target.closest('.prod-attach-item')) return;
    var input = document.getElementById(cfg.inputId);
    if (input) input.click();
}

// ── Handle dropped / selected files ──────────────────────────────────────────
function _attachHandleFiles(entityType, fileList) {
    var cfg   = _attachCfg[entityType];
    var state = _attachState[entityType];
    if (!cfg || !state || cfg.enabled === false) return;

    var allowed        = _attachAllowedMimes(entityType); // null = all types OK
    var perFileBytes   = (cfg.maxFileSizeMB > 0) ? cfg.maxFileSizeMB * 1024 * 1024 : 0; // 0 = no per-file check
    var totalSizeBytes = cfg.maxTotalMB * 1024 * 1024;

    var active = (state.existing || []).filter(function (x) {
        return !(state.toDelete || []).includes(x.AttachUID);
    }).length;
    var slots = cfg.maxFiles - active - (state.newFiles || []).length;
    if (slots <= 0) {
        showToastNotification('Maximum ' + cfg.maxFiles + ' files allowed.', 'error');
        return;
    }

    var usedSize = (state.newFiles || []).reduce(function (s, f) { return s + (f.size || 0); }, 0)
                 + (state.existing || []).reduce(function (s, f) { return s + (parseInt(f.FileSize, 10) || 0); }, 0);

    for (var i = 0; i < fileList.length && slots > 0; i++) {
        var f = fileList[i];
        if (allowed && !allowed.includes(f.type)) {
            showToastNotification('"' + f.name + '" is not a valid image (JPG/PNG/GIF only).', 'error');
            continue;
        }
        if (perFileBytes > 0 && f.size > perFileBytes) {
            showToastNotification('"' + f.name + '" exceeds the ' + cfg.maxFileSizeMB + ' MB per-file limit.', 'error');
            continue;
        }
        if (usedSize + f.size > totalSizeBytes) {
            showToastNotification('Total size exceeds ' + cfg.maxTotalMB + ' MB limit.', 'error');
            break;
        }
        usedSize += f.size;
        state.newFiles.push(f);
        slots--;
    }
    _attachRender(entityType);
}

// ── Render the preview list ───────────────────────────────────────────────────
function _attachRender(entityType) {
    var cfg   = _attachCfg[entityType];
    var state = _attachState[entityType];
    if (!cfg || !state) return;

    var list  = document.getElementById(cfg.listId);
    var label = document.getElementById(cfg.labelId);
    var hint  = document.getElementById(cfg.hintId);
    var icon  = document.getElementById(cfg.iconId);
    if (!list) return;

    list.innerHTML = '';
    var activeEx  = (state.existing || []).filter(function (x) {
        return !(state.toDelete || []).includes(x.AttachUID);
    });
    var total     = activeEx.length + (state.newFiles || []).length;
    var remaining = cfg.maxFiles - total;

    // Update card badge if present (transaction card header)
    var badge = document.getElementById(entityType.toLowerCase().replace(/([a-z])([A-Z])/g, '$1$2').replace(/\s/g, '') + 'AttachBadge');
    if (!badge) badge = document.getElementById('transAttachBadge'); // fallback for Transaction slot
    if (badge) {
        if (total > 0) { badge.textContent = total + ' file' + (total > 1 ? 's' : ''); badge.classList.remove('d-none'); }
        else { badge.classList.add('d-none'); }
    }

    if (total === 0) {
        if (icon)  { icon.className = 'bx bx-upload'; icon.style.color = '#9ca3af'; }
        if (label) label.textContent = 'Drag & drop files here';
        if (hint)  hint.textContent  = _attachTypeHint(entityType) + ' · Max ' + cfg.maxFiles + ' · ' + cfg.maxTotalMB + ' MB total';
        list.style.display = 'none';
        return;
    } else if (remaining > 0) {
        if (icon)  { icon.className = 'bx bx-plus'; icon.style.color = '#6366f1'; }
        if (label) label.textContent = 'Add more files';
        if (hint)  hint.textContent  = remaining + ' slot' + (remaining > 1 ? 's' : '') + ' remaining';
    } else {
        if (icon)  { icon.className = 'bx bx-check-circle'; icon.style.color = '#10b981'; }
        if (label) label.textContent = 'Maximum reached';
        if (hint)  hint.textContent  = cfg.maxFiles + ' of ' + cfg.maxFiles + ' files added';
    }
    list.style.display = '';

    var existingGallery = (state.existing || []).map(function (a) {
        return { url: a.Url || a.FilePath, name: a.FileName };
    });

    if (!_attachBlobUrls[entityType]) _attachBlobUrls[entityType] = [];
    var blobUrls = _attachBlobUrls[entityType];
    (state.newFiles || []).forEach(function (f, i) {
        if (!blobUrls[i]) blobUrls[i] = _attachIsImage(f.type) ? URL.createObjectURL(f) : null;
    });
    blobUrls.length = (state.newFiles || []).length;
    var newGallery = (state.newFiles || []).map(function (f, i) {
        return { url: blobUrls[i], name: f.name };
    });

    // Existing saved attachments
    (state.existing || []).forEach(function (att, exIdx) {
        var deleted = (state.toDelete || []).includes(att.AttachUID);
        var item = document.createElement('div');
        item.className = 'prod-attach-item is-existing' + (deleted ? ' pending-delete' : '');

        var isImg = _attachIsImage(att.FileType || att.FileName);
        if (isImg) {
            var thumb = document.createElement('img');
            thumb.alt = att.FileName || '';
            thumb.title = 'Click to preview';
            thumb.src = att.Url || att.FilePath || '';
            (function (g, i) {
                thumb.addEventListener('click', function (e) { e.stopPropagation(); openImageGallery(g, i); });
            })(existingGallery, exIdx);
            item.appendChild(thumb);
        } else {
            var fileIcon = document.createElement('div');
            fileIcon.className = 'attach-file-icon';
            fileIcon.innerHTML = _attachFileIcon(att.FileType || att.FileName);
            item.appendChild(fileIcon);
        }

        var nm = document.createElement('span');
        nm.className = 'attach-name';
        nm.title = att.FileName || '';
        nm.textContent = att.FileName || '';
        item.appendChild(nm);

        var sz = document.createElement('span');
        sz.className = 'attach-size';
        sz.textContent = _attachFmtSize(att.FileSize || 0);
        item.appendChild(sz);

        var btn = document.createElement('button');
        btn.className = 'attach-remove';
        btn.type = 'button';
        btn.title = deleted ? 'Undo remove' : 'Remove';
        btn.innerHTML = deleted ? '<i class="bx bx-undo"></i>' : '<i class="bx bx-x"></i>';
        if (deleted) {
            (function (et, uid) {
                btn.addEventListener('click', function (e) { e.stopPropagation(); _attachUndoDelete(et, uid); });
            })(entityType, att.AttachUID);
        } else {
            (function (et, uid) {
                btn.addEventListener('click', function (e) { e.stopPropagation(); _attachRemoveExisting(et, uid); });
            })(entityType, att.AttachUID);
        }
        item.appendChild(btn);
        list.appendChild(item);
    });

    // New (unsaved) files
    (state.newFiles || []).forEach(function (file, idx) {
        var item = document.createElement('div');
        item.className = 'prod-attach-item';

        var isImg = _attachIsImage(file.type);
        if (isImg && blobUrls[idx]) {
            var thumb = document.createElement('img');
            thumb.alt = file.name;
            thumb.title = 'Click to preview';
            thumb.src = blobUrls[idx];
            (function (g, i) {
                thumb.addEventListener('click', function (e) { e.stopPropagation(); openImageGallery(g, i); });
            })(newGallery, idx);
            item.appendChild(thumb);
        } else {
            var fileIcon = document.createElement('div');
            fileIcon.className = 'attach-file-icon';
            fileIcon.innerHTML = _attachFileIcon(file.type || file.name);
            item.appendChild(fileIcon);
        }

        var nm = document.createElement('span');
        nm.className = 'attach-name';
        nm.title = file.name;
        nm.textContent = file.name;
        item.appendChild(nm);

        var sz = document.createElement('span');
        sz.className = 'attach-size';
        sz.textContent = _attachFmtSize(file.size);
        item.appendChild(sz);

        var btn = document.createElement('button');
        btn.className = 'attach-remove';
        btn.type = 'button';
        btn.title = 'Remove';
        btn.innerHTML = '<i class="bx bx-x"></i>';
        (function (et, i) {
            btn.addEventListener('click', function (e) { e.stopPropagation(); _attachRemoveNew(et, i); });
        })(entityType, idx);
        item.appendChild(btn);
        list.appendChild(item);
    });
}

// ── File icon for non-image files ─────────────────────────────────────────────
function _attachFileIcon(mimeOrName) {
    if (!mimeOrName) return '<i class="bx bx-file" style="font-size:1.4rem;color:#6b7280;"></i>';
    var s = mimeOrName.toLowerCase();
    if (s.indexOf('pdf') !== -1)
        return '<i class="bx bxs-file-pdf" style="font-size:1.4rem;color:#ef4444;"></i>';
    if (s.indexOf('word') !== -1 || s.indexOf('.doc') !== -1)
        return '<i class="bx bxs-file-doc" style="font-size:1.4rem;color:#2563eb;"></i>';
    if (s.indexOf('sheet') !== -1 || s.indexOf('.xls') !== -1)
        return '<i class="bx bxs-spreadsheet" style="font-size:1.4rem;color:#16a34a;"></i>';
    return '<i class="bx bx-file" style="font-size:1.4rem;color:#6b7280;"></i>';
}

// ── Soft-delete / undo helpers ────────────────────────────────────────────────
function _attachRemoveExisting(entityType, attachUID) {
    Swal.fire({
        title: 'Remove this file?', text: 'It will be deleted when you save.',
        icon: 'warning', showCancelButton: true,
        confirmButtonColor: '#ef4444', confirmButtonText: 'Yes, remove',
        cancelButtonColor: '#6b7280',
    }).then(function (r) {
        if (!r.isConfirmed) return;
        var state = _attachState[entityType];
        if (!(state.toDelete || []).includes(attachUID)) state.toDelete.push(attachUID);
        _attachRender(entityType);
        var cfg = _attachCfg[entityType];
        if (cfg) {
            var df = document.getElementById(cfg.deleteField);
            if (df) df.value = state.toDelete.join(',');
        }
    });
}

function _attachUndoDelete(entityType, attachUID) {
    var state = _attachState[entityType];
    state.toDelete = (state.toDelete || []).filter(function (id) { return id !== attachUID; });
    _attachRender(entityType);
    var cfg = _attachCfg[entityType];
    if (cfg) {
        var df = document.getElementById(cfg.deleteField);
        if (df) df.value = state.toDelete.join(',');
    }
}

function _attachRemoveNew(entityType, idx) {
    _attachResetBlobUrls(entityType);
    _attachState[entityType].newFiles.splice(idx, 1);
    _attachRender(entityType);
}

// ── Utilities ─────────────────────────────────────────────────────────────────
function _attachResetBlobUrls(entityType) {
    if (_attachBlobUrls[entityType]) {
        _attachBlobUrls[entityType].forEach(function (u) {
            try { if (u) URL.revokeObjectURL(u); } catch (e) {}
        });
        _attachBlobUrls[entityType] = [];
    }
}

function _attachFmtSize(b) {
    if (!b) return '';
    if (b < 1024) return b + ' B';
    if (b < 1048576) return (b / 1024).toFixed(1) + ' KB';
    return (b / 1048576).toFixed(1) + ' MB';
}
