<?php defined('BASEPATH') or exit('No direct script access allowed');
$this->load->view('common/transactions/header'); ?>

<div class="layout-wrapper layout-horizontal layout-content-navbar">
    <div class="layout-container">

        <?php $this->load->view('common/menu_view'); ?>

        <div class="layout-page">
            <div class="content-wrapper apex-content">
                <?php $this->load->view('common/apex/page_header', [
                    'pageTitle'       => $PageTitle       ?? 'My Drive',
                    'pageDescription' => $PageDescription ?? '',
                ]); ?>

                <div class="container-xxl flex-grow-1">

                    <style>
                        .drive-toolbar {
                            display: flex;
                            align-items: center;
                            flex-wrap: wrap;
                            gap: 8px;
                            padding: 10px 16px;
                            border-bottom: 1px solid var(--bs-border-color, #e2e8f0);
                        }
                        .drive-breadcrumb {
                            display: flex;
                            align-items: center;
                            gap: 4px;
                            flex: 1;
                            min-width: 0;
                            flex-wrap: wrap;
                        }
                        #driveBreadcrumb a {
                            font-size: .82rem;
                            font-weight: 600;
                            color: #0369a1 !important;
                            cursor: pointer;
                            text-decoration: none !important;
                            white-space: nowrap;
                        }
                        #driveBreadcrumb a:hover { text-decoration: underline !important; }
                        #driveBreadcrumb .bc-sep {
                            color: #94a3b8;
                            font-size: .9rem;
                            padding: 0 3px;
                            line-height: 1;
                        }
                        #driveBreadcrumb .bc-current {
                            font-size: .82rem;
                            font-weight: 600;
                            color: var(--bs-body-color, #1e293b);
                            white-space: nowrap;
                            max-width: 200px;
                            overflow: hidden;
                            text-overflow: ellipsis;
                        }
                        .drive-usage-row {
                            display: flex;
                            align-items: center;
                            gap: 10px;
                            padding: 7px 16px;
                            border-bottom: 1px solid var(--bs-border-color, #e2e8f0);
                        }
                        .drive-usage-track {
                            flex: 1;
                            height: 5px;
                            background: var(--bs-border-color, #e2e8f0);
                            border-radius: 4px;
                            overflow: hidden;
                        }
                        .drive-usage-fill {
                            height: 100%;
                            background: linear-gradient(90deg, #38bdf8, #0369a1);
                            border-radius: 4px;
                            transition: width .4s ease;
                        }
                        .drive-usage-label {
                            font-size: .72rem;
                            color: #64748b;
                            white-space: nowrap;
                            min-width: 120px;
                            text-align: right;
                        }
                        .drive-content {
                            padding: 16px;
                            min-height: 320px;
                            position: relative;
                        }
                        .drive-grid {
                            display: grid;
                            grid-template-columns: repeat(auto-fill, minmax(128px, 1fr));
                            gap: 10px;
                        }
                        .drive-item {
                            border: 1px solid var(--bs-border-color, #e2e8f0);
                            border-radius: 10px;
                            padding: 14px 10px 10px;
                            cursor: pointer;
                            position: relative;
                            transition: box-shadow .15s, border-color .15s;
                            background: var(--bs-body-bg, #fff);
                            text-align: center;
                            user-select: none;
                        }
                        .drive-item:hover {
                            box-shadow: 0 4px 14px rgba(0,0,0,.08);
                            border-color: #bae6fd;
                        }
                        .drive-item.drag-hover {
                            border-color: #0369a1 !important;
                            background: #f0f9ff !important;
                            box-shadow: 0 0 0 3px rgba(3,105,161,.15) !important;
                        }
                        .drive-item-icon { font-size: 2.4rem; line-height: 1; margin-bottom: 8px; }
                        .drive-item-name {
                            font-size: .75rem;
                            font-weight: 600;
                            color: var(--bs-body-color, #374151);
                            word-break: break-word;
                            line-height: 1.35;
                            display: -webkit-box;
                            -webkit-line-clamp: 2;
                            line-clamp: 2;
                            -webkit-box-orient: vertical;
                            overflow: hidden;
                        }
                        .drive-item-meta { font-size: .67rem; color: #94a3b8; margin-top: 4px; }
                        .drive-item-actions {
                            position: absolute;
                            top: 5px; right: 5px;
                            display: flex;
                            gap: 2px;
                            opacity: 0;
                            transition: opacity .15s;
                        }
                        .drive-item:hover .drive-item-actions { opacity: 1; }
                        .drive-act-btn {
                            background: #f1f5f9;
                            border: none;
                            border-radius: 5px;
                            width: 22px; height: 22px;
                            display: flex; align-items: center; justify-content: center;
                            cursor: pointer;
                            color: #64748b;
                            font-size: .7rem;
                            padding: 0;
                            line-height: 1;
                        }
                        .drive-act-btn:hover { background: #e2e8f0; color: #1e293b; }
                        .drive-act-btn.del:hover { background: #fee2e2; color: #dc2626; }
                        .drive-list-tbl { width: 100%; border-collapse: collapse; }
                        .drive-list-tbl th {
                            font-size: .72rem;
                            font-weight: 700;
                            color: #64748b;
                            text-transform: uppercase;
                            letter-spacing: .04em;
                            padding: 8px 10px;
                            border-bottom: 1px solid var(--bs-border-color, #e2e8f0);
                            text-align: left;
                        }
                        .drive-list-tbl td {
                            padding: 7px 10px;
                            font-size: .8rem;
                            color: var(--bs-body-color, #374151);
                            border-bottom: 1px solid var(--bs-border-color, #f1f5f9);
                            vertical-align: middle;
                        }
                        .drive-list-tbl tbody tr:hover td { background: var(--bs-tertiary-bg, #f8fafc); }
                        .drive-list-tbl .row-acts { opacity: 0; transition: opacity .12s; white-space: nowrap; }
                        .drive-list-tbl tbody tr:hover .row-acts { opacity: 1; }
                        .drive-empty {
                            display: flex;
                            flex-direction: column;
                            align-items: center;
                            justify-content: center;
                            padding: 60px 20px;
                            color: #94a3b8;
                            text-align: center;
                        }
                        .drive-empty i { font-size: 3rem; margin-bottom: 12px; }
                        .drive-empty p { font-size: .85rem; margin: 0 0 4px; }
                        .drive-empty p.sub { font-size: .75rem; color: #cbd5e1; }
                        .drive-drop-overlay {
                            position: absolute;
                            inset: 0;
                            border: 2px dashed #0369a1;
                            border-radius: 8px;
                            background: rgba(3,105,161,.06);
                            display: none;
                            align-items: center;
                            justify-content: center;
                            z-index: 20;
                            pointer-events: none;
                        }
                        .drive-drop-overlay.show { display: flex; }
                        .drive-drop-label {
                            font-size: 1rem;
                            font-weight: 700;
                            color: #0369a1;
                            display: flex;
                            align-items: center;
                            gap: 8px;
                            background: rgba(255,255,255,.9);
                            padding: 14px 24px;
                            border-radius: 10px;
                            box-shadow: 0 4px 16px rgba(0,0,0,.08);
                        }
                        .move-folder-item {
                            display: flex;
                            align-items: center;
                            gap: 8px;
                            padding: 8px 14px;
                            border-radius: 6px;
                            cursor: pointer;
                            font-size: .82rem;
                            color: var(--bs-body-color, #374151);
                            transition: background .12s;
                        }
                        .move-folder-item:hover { background: #f0f9ff; }
                        .move-folder-item i { color: #f59e0b; font-size: 1rem; flex-shrink: 0; }
                        .move-folder-item .mf-name { flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
                        .move-folder-item .mf-chev { color: #cbd5e1; font-size: .85rem; }
                        .drive-upload-toast {
                            position: fixed;
                            bottom: 20px; right: 20px;
                            background: var(--bs-body-bg, #fff);
                            border: 1px solid var(--bs-border-color, #e2e8f0);
                            border-radius: 10px;
                            padding: 12px 16px;
                            box-shadow: 0 4px 20px rgba(0,0,0,.12);
                            z-index: 1080;
                            min-width: 250px;
                            display: none;
                        }
                        .drive-upload-toast.show { display: block; }
                        /* Dark mode */
                        [data-bs-theme="dark"] .drive-item, :root[data-theme="dark"] .drive-item { border-color: #334155; }
                        [data-bs-theme="dark"] .drive-act-btn, :root[data-theme="dark"] .drive-act-btn { background: #334155; color: #94a3b8; }
                        [data-bs-theme="dark"] .drive-act-btn:hover, :root[data-theme="dark"] .drive-act-btn:hover { background: #475569; color: #e2e8f0; }
                        [data-bs-theme="dark"] .drive-drop-label, :root[data-theme="dark"] .drive-drop-label { background: rgba(30,41,59,.92); }
                        [data-bs-theme="dark"] .move-folder-item:hover, :root[data-theme="dark"] .move-folder-item:hover { background: #1e3a5f; }
                    </style>

                    <div class="card" id="driveCard">

                        <!-- Toolbar -->
                        <div class="drive-toolbar">
                            <nav class="drive-breadcrumb" id="driveBreadcrumb">
                                <a data-uid="0">My Drive</a>
                            </nav>

                            <?php if (!empty($IsAdmin) && !empty($OrgUsers)): ?>
                            <div class="d-flex align-items-center gap-1" style="flex-shrink:0;">
                                <i class="bx bx-user" style="color:#64748b;font-size:.9rem;"></i>
                                <select id="driveUserPicker" class="form-select form-select-sm" style="min-width:155px;font-size:.78rem;">
                                    <?php foreach ($OrgUsers as $u): ?>
                                    <option value="<?php echo (int)$u->UserUID; ?>"
                                        <?php echo (int)$u->UserUID === (int)($CurrentUserUID ?? 0) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($u->FullName ?? ''); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php endif; ?>

                            <div class="d-flex align-items-center gap-2 ms-auto">
                                <button class="btn btn-sm btn-outline-secondary" id="driveNewFolderBtn">
                                    <i class="bx bx-folder-plus me-1"></i>New Folder
                                </button>
                                <label class="btn btn-sm btn-primary mb-0" style="cursor:pointer;">
                                    <i class="bx bx-upload me-1"></i>Upload
                                    <input type="file" id="driveFileInput" multiple hidden>
                                </label>
                                <button class="btn btn-sm btn-outline-secondary px-2" id="driveRefreshBtn" title="Refresh">
                                    <i class="bx bx-refresh"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-secondary px-2" id="driveViewToggle" title="Toggle grid / list view">
                                    <i class="bx bx-list-ul" id="driveViewIcon"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Usage meter -->
                        <div class="drive-usage-row">
                            <i class="bx bx-data" style="color:#0369a1;font-size:.95rem;flex-shrink:0;"></i>
                            <div class="drive-usage-track">
                                <div class="drive-usage-fill" id="driveUsageFill" style="width:0%;"></div>
                            </div>
                            <span class="drive-usage-label" id="driveUsageLabel">—</span>
                        </div>

                        <!-- Content -->
                        <div class="drive-content" id="driveContent">
                            <div class="drive-empty">
                                <div class="spinner-border text-primary mb-2" style="width:2.2rem;height:2.2rem;" role="status"></div>
                                <p>Loading your drive…</p>
                            </div>
                            <!-- Drop overlay -->
                            <div class="drive-drop-overlay" id="driveDropOverlay">
                                <div class="drive-drop-label">
                                    <i class="bx bx-cloud-upload" style="font-size:1.3rem;"></i>
                                    Drop files here to upload
                                </div>
                            </div>
                        </div>

                    </div><!-- /card -->

                </div>
            </div>
            <?php $this->load->view('common/footer_desc'); ?>
        </div>
    </div>
</div>

<!-- ── Upload progress toast ──────────────────────────────────────────────── -->
<div class="drive-upload-toast" id="driveUploadToast">
    <div class="d-flex justify-content-between align-items-center mb-1">
        <span style="font-size:.8rem;font-weight:600;">Uploading…</span>
        <span style="font-size:.72rem;color:#64748b;" id="uploadPct">0%</span>
    </div>
    <div style="height:4px;background:#e2e8f0;border-radius:3px;overflow:hidden;">
        <div id="uploadBar" style="height:100%;background:linear-gradient(90deg,#38bdf8,#0369a1);width:0%;border-radius:3px;transition:width .15s;"></div>
    </div>
</div>

<!-- ── New Folder Modal ───────────────────────────────────────────────────── -->
<div class="modal fade" id="driveNewFolderModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header py-2 px-3">
                <div class="d-flex align-items-center gap-2">
                    <div style="width:30px;height:30px;border-radius:8px;background:#fef3c7;display:flex;align-items:center;justify-content:center;">
                        <i class="bx bx-folder-plus" style="color:#f59e0b;"></i>
                    </div>
                    <h6 class="modal-title mb-0" style="font-size:.88rem;">New Folder</h6>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body px-3 py-2">
                <label class="form-label mb-1" style="font-size:.74rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.04em;">Folder Name</label>
                <input type="text" class="form-control form-control-sm" id="newFolderName" placeholder="e.g. Documents" maxlength="255" autocomplete="off">
            </div>
            <div class="modal-footer py-2 px-3">
                <button class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-sm btn-primary" id="driveCreateFolderBtn">Create</button>
            </div>
        </div>
    </div>
</div>

<!-- ── Rename Modal ───────────────────────────────────────────────────────── -->
<div class="modal fade" id="driveRenameModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header py-2 px-3">
                <h6 class="modal-title mb-0" style="font-size:.88rem;">Rename</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body px-3 py-2">
                <label class="form-label mb-1" style="font-size:.74rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.04em;">New Name</label>
                <input type="text" class="form-control form-control-sm" id="renameInput" maxlength="255" autocomplete="off">
                <input type="hidden" id="renameUID">
            </div>
            <div class="modal-footer py-2 px-3">
                <button class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-sm btn-primary" id="driveRenameSaveBtn">Rename</button>
            </div>
        </div>
    </div>
</div>

<!-- ── Move To Modal ──────────────────────────────────────────────────────── -->
<div class="modal fade" id="driveMoveModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header py-2 px-3">
                <div>
                    <h6 class="modal-title mb-0" style="font-size:.88rem;">Move to…</h6>
                    <div style="font-size:.72rem;color:#64748b;" id="moveItemLabel"></div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body px-0 py-0">
                <div style="padding:7px 14px;border-bottom:1px solid var(--bs-border-color,#e2e8f0);display:flex;align-items:center;gap:8px;min-height:36px;">
                    <button class="btn btn-sm btn-link p-0 text-primary" id="moveFolderBackBtn" style="font-size:.78rem;display:none;">
                        <i class="bx bx-arrow-back me-1"></i>Back
                    </button>
                    <span style="font-size:.75rem;color:#64748b;" id="moveBcLabel">My Drive (root)</span>
                </div>
                <div style="max-height:270px;overflow-y:auto;padding:4px 0;" id="moveFolderList">
                    <div class="text-center py-3"><span class="spinner-border spinner-border-sm text-primary"></span></div>
                </div>
            </div>
            <div class="modal-footer py-2 px-3">
                <button class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-sm btn-primary" id="driveMoveHereBtn">Move here</button>
            </div>
        </div>
    </div>
</div>


<?php $this->load->view('common/transactions/footer'); ?>

<script>
var DriveConfig = {
    cdnUrl:         '<?php echo addslashes(rtrim($CdnUrl ?? '', '/')); ?>',
    isAdmin:        <?php echo !empty($IsAdmin) ? 'true' : 'false'; ?>,
    currentUserUID: <?php echo (int)($CurrentUserUID ?? 0); ?>,
    quotaBytes:     <?php echo (int)($QuotaBytes ?? 104857600); ?>
};
</script>
<script src="<?php echo _assetV('/js/drive.js'); ?>"></script>
