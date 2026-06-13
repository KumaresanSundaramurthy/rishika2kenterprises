<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php $this->load->view('common/header'); ?>

<div class="layout-wrapper layout-horizontal layout-content-navbar">
    <div class="layout-container">

        <?php $this->load->view('common/menu_view'); ?>

        <div class="layout-page">

            <div class="content-wrapper apex-content">
                <?php $this->load->view('common/apex/page_header', [
                    'pageIcon'        => 'bx-shield-alt-2',
                    'pageIconBg'      => '#ede9fe',
                    'pageIconColor'   => '#7c3aed',
                    'pageTitle'       => $PageTitle       ?? 'Roles &amp; Permissions',
                    'pageDescription' => $PageDescription ?? '',
                ]); ?>
                <div class="container-xxl flex-grow-1 container-p-y">

                    <div class="row g-3">

                        <!-- ── Left panel: role list ────────────────────────────── -->
                        <div class="col-md-3">
                            <div class="card h-100">
                                <div class="card-header d-flex align-items-center justify-content-between py-2 px-3">
                                    <span class="fw-semibold" style="font-size:.85rem;">
                                        <i class="bx bx-shield-alt-2 me-1 text-primary"></i>Roles
                                    </span>
                                    <div class="d-flex align-items-center gap-2">
                                        <button class="btn btn-outline-secondary btn-sm px-2 py-1" id="btnRefreshTokens"
                                            title="Refresh the Tokens" data-bs-toggle="tooltip" data-bs-placement="top"
                                            style="font-size:.75rem;">
                                            <i class="bx bx-refresh"></i>
                                        </button>
                                        <button class="btn btn-primary btn-sm px-2 py-1" id="btnAddRole" style="font-size:.75rem;">
                                            <i class="bx bx-plus me-1"></i>Add Role
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body p-0">
                                    <ul class="list-group list-group-flush" id="roleListUL">
                                        <?php foreach ($RolesList as $role): ?>
                                        <?php $isDefault = (int)$role->IsDefault === 1; ?>
                                        <li class="list-group-item list-group-item-action d-flex align-items-center justify-content-between px-3 py-2 role-item"
                                            data-roleuid="<?php echo $role->RoleUID; ?>"
                                            data-rolename="<?php echo htmlspecialchars($role->Name); ?>"
                                            data-isdefault="<?php echo $isDefault ? 1 : 0; ?>"
                                            style="cursor:pointer;font-size:.82rem;">
                                            <span class="d-flex align-items-center gap-1 text-truncate" style="min-width:0;">
                                                <?php if ($isDefault): ?>
                                                <i class="bx bx-lock-alt text-warning" style="font-size:.85rem;flex-shrink:0;" title="Default role — cannot be edited or deleted"></i>
                                                <?php else: ?>
                                                <i class="bx bx-user-circle text-secondary" style="font-size:.85rem;flex-shrink:0;"></i>
                                                <?php endif; ?>
                                                <span class="text-truncate"><?php echo htmlspecialchars($role->Name); ?></span>
                                                <span class="badge bg-label-secondary ms-1" style="font-size:.62rem;flex-shrink:0;"><?php echo $role->UserCount; ?></span>
                                            </span>
                                            <?php if (!$isDefault): ?>
                                            <span class="d-flex gap-1 ms-2 flex-shrink-0">
                                                <button class="btn btn-sm p-0 text-primary edit-role-btn"
                                                    data-roleuid="<?php echo $role->RoleUID; ?>"
                                                    data-rolename="<?php echo htmlspecialchars($role->Name); ?>"
                                                    style="line-height:1;" title="Rename">
                                                    <i class="bx bx-pencil" style="font-size:.9rem;"></i>
                                                </button>
                                                <button class="btn btn-sm p-0 text-danger delete-role-btn"
                                                    data-roleuid="<?php echo $role->RoleUID; ?>"
                                                    style="line-height:1;" title="Delete">
                                                    <i class="bx bx-trash" style="font-size:.9rem;"></i>
                                                </button>
                                            </span>
                                            <?php else: ?>
                                            <span class="ms-2 flex-shrink-0" title="System default" style="font-size:.65rem;color:#bbb;">
                                                <i class="bx bx-shield-quarter" style="font-size:.85rem;"></i>
                                            </span>
                                            <?php endif; ?>
                                        </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- ── Right panel: permission matrix ──────────────────── -->
                        <div class="col-md-9">
                            <div class="card">
                                <div class="card-header d-flex align-items-center justify-content-between py-2 px-3">
                                    <span class="fw-semibold" style="font-size:.85rem;" id="permMatrixTitle">
                                        <i class="bx bx-lock-open-alt me-1 text-primary"></i>
                                        Select a role to configure permissions
                                    </span>
                                    <button class="btn btn-success btn-sm px-3 py-1 d-none" id="btnSavePermissions" style="font-size:.75rem;">
                                        <span class="spinner-border spinner-border-sm me-1 d-none" id="savePrmSpinner"></span>
                                        <i class="bx bx-save me-1"></i>Save Permissions
                                    </button>
                                </div>
                                <div class="card-body p-2" id="permMatrixBody">
                                    <div class="text-center text-muted py-5" id="permMatrixEmpty">
                                        <i class="bx bx-shield-x" style="font-size:2.5rem;opacity:.3;"></i>
                                        <p class="mt-2" style="font-size:.82rem;">Select a role from the left to view and edit its permissions.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div><!-- /row -->

                </div>
            </div><!-- /content-wrapper -->

            <?php $this->load->view('common/settings_modal'); ?>
            <?php $this->load->view('common/footer_desc'); ?>
        </div>
    </div>
</div>

<!-- ── Add / Edit Role Modal ─────────────────────────────────────────── -->
<div class="modal fade" id="roleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:420px;">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title mb-0" id="roleModalTitle"><i class="bx bx-shield-alt-2 me-1"></i>Add Role</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body py-3">
                <input type="hidden" id="RoleModalUID" value="0">
                <label class="form-label small fw-semibold mb-1">Role Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control form-control-sm" id="RoleName" maxlength="100" placeholder="e.g. Sales Executive">
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary btn-sm px-4" id="saveRoleBtn">
                    <span class="spinner-border spinner-border-sm me-1 d-none" id="saveRoleSpinner"></span>
                    Save
                </button>
            </div>
        </div>
    </div>
</div>

<?php $this->load->view('common/footer'); ?>

<script>
/* ── Data from PHP ──────────────────────────────────────────────────── */
const _allMainMenus = <?php echo json_encode($AllMainMenus); ?>;
const _allSubMenus  = <?php echo json_encode($AllSubMenus); ?>;

/* ── State ──────────────────────────────────────────────────────────── */
let _activeRoleUID  = 0;
let _permData       = { main: [], sub: [] };

/* ── Helpers ────────────────────────────────────────────────────────── */
function _chk(uid, field, checked, disabled) {
    return `<input type="checkbox" class="form-check-input sm-perm-chk" style="width:.9rem;height:.9rem;cursor:pointer;"
                data-uid="${uid}" data-field="${field}" ${checked ? 'checked' : ''} ${disabled ? 'disabled' : ''}>`;
}

function _rowAllChk(uid, allChecked) {
    return `<input type="checkbox" class="form-check-input sm-row-all" style="width:.9rem;height:.9rem;cursor:pointer;"
                data-uid="${uid}" title="Toggle all" ${allChecked ? 'checked' : ''}>`;
}

function _mmChk(mmUID, field, checked, disabled) {
    return `<input type="checkbox" class="form-check-input mm-perm-chk" style="width:.9rem;height:.9rem;cursor:pointer;"
                data-mmuid="${mmUID}" data-field="${field}" ${checked ? 'checked' : ''} ${disabled ? 'disabled' : ''}>`;
}

function _colHdrChk(field) {
    return `<div class="d-flex flex-column align-items-center gap-1">
                <input type="checkbox" class="form-check-input col-hdr-chk" style="width:.9rem;height:.9rem;cursor:pointer;"
                    data-field="${field}" title="Select all ${field.replace('Can','')}">
            </div>`;
}

function _allChecked4(p) {
    return !!(p.CanView && p.CanCreate && p.CanEdit && p.CanDelete);
}

/* ── Build permission matrix HTML ──────────────────────────────────── */
function _buildMatrix(mainPerms, subPerms) {

    const mmMap = {};
    mainPerms.forEach(p => mmMap[p.MainMenuUID] = p);
    const smMap = {};
    subPerms.forEach(p => smMap[p.SubMenuUID] = p);

    // Recursive renderer — every item gets full controls; IsParent items also render children below
    function _renderSubs(mmUID, parentUID, mmOn, depth) {
        let rows = '';
        const indent = 2.6 + (depth * 1.6);
        const items  = _allSubMenus
            .filter(s => parentUID ? s.ParentSubMenuUID == parentUID : (s.MainMenuUID == mmUID && !s.ParentSubMenuUID))
            .sort((a, b) => (a.Sorting || 0) - (b.Sorting || 0));

        items.forEach(sm => {
            const sp        = smMap[sm.SubMenuUID] || {};
            const smOn      = mmOn && !!(sp.CanView || sp.CanCreate || sp.CanEdit || sp.CanDelete);
            const smAllChk  = smOn && _allChecked4(sp);
            const spdis     = smOn ? '' : 'perm-disabled';
            const smEnabled = smOn ? 'sm-enabled' : '';
            const nameHtml  = sm.IsParent
                ? `<strong style="color:#3a3b6e;">${sm.Name}</strong>`
                : sm.Name;
            const rowBg     = sm.IsParent ? 'background:#f4f4fb;' : '';

            rows += `
            <tr class="sm-row ${smEnabled} ${mmOn ? '' : 'sm-hidden'}" data-mmuid="${mmUID}" data-smuid="${sm.SubMenuUID}">
                <td style="padding-left:${indent}rem; border-left:4px solid ${smOn ? '#26a69a' : 'transparent'}; ${rowBg}">
                    <i class="${sm.Icon || 'bx bx-circle'} me-1" style="font-size:.85rem;color:#26a69a;"></i>
                    ${nameHtml}
                </td>
                <td class="text-center" style="background:#f4f0ff; ${rowBg}"></td>
                <td class="text-center td-sm-enable" style="${rowBg}">
                    <div class="form-check form-switch d-flex justify-content-center m-0">
                        <input class="form-check-input sm-enable-toggle" type="checkbox"
                            data-smuid="${sm.SubMenuUID}" data-mmuid="${mmUID}" ${smOn ? 'checked' : ''}>
                    </div>
                </td>
                <td class="text-center td-row-all" style="${rowBg}">${_rowAllChk(sm.SubMenuUID, smAllChk)}</td>
                <td class="text-center ${spdis}" style="${rowBg}">${_chk(sm.SubMenuUID, 'CanView',   sp.CanView,   !smOn)}</td>
                <td class="text-center ${spdis}" style="${rowBg}">${_chk(sm.SubMenuUID, 'CanCreate', sp.CanCreate, !smOn)}</td>
                <td class="text-center ${spdis}" style="${rowBg}">${_chk(sm.SubMenuUID, 'CanEdit',   sp.CanEdit,   !smOn)}</td>
                <td class="text-center ${spdis}" style="${rowBg}">${_chk(sm.SubMenuUID, 'CanDelete', sp.CanDelete, !smOn)}</td>
            </tr>`;

            // Render children below this item (indented one level deeper)
            if (sm.IsParent) {
                rows += _renderSubs(mmUID, sm.SubMenuUID, mmOn, depth + 1);
            }
        });
        return rows;
    }

    let html = `
    <style>
    /* ── Table base ── */
    .perm-table { font-size:.78rem; border-collapse:collapse; width:100%; }
    .perm-table th, .perm-table td { border:1px solid #dee2e6; padding:5px 8px; vertical-align:middle; }

    /* ── Header ── */
    .perm-table thead th { background:#f0f4ff; font-weight:600; text-align:center; color:#3a3b6e; border-bottom:2px solid #c5cff7; }
    .perm-table thead th:first-child { text-align:left; }
    .perm-table thead th.th-enable-mm { background:#ede9ff; color:#5e35b1; }
    .perm-table thead th.th-enable-sm { background:#e8f5e9; color:#2e7d32; }

    /* ── Main-menu rows ── */
    .perm-table .mm-row td { background:#eef1fb; font-weight:600; color:#2c3575; border-top:2px solid #c5cff7; }
    .perm-table .mm-row td:first-child { border-left:4px solid #696cff; padding-left:1rem; }
    .perm-table .mm-row .td-mm-enable { background:#ede9ff; }
    .mm-enable-toggle { accent-color:#696cff; width:2.2rem !important; height:1.1rem !important; cursor:pointer; }
    .mm-access-note { font-size:.71rem; color:#888; font-weight:400; font-style:italic; }

    /* ── Sub-menu rows ── */
    .perm-table .sm-row td { background:#fafafa; color:#444; }
    .perm-table .sm-row.sm-enabled td:first-child { }
    .perm-table .sm-row.sm-hidden { display:none; }
    .perm-table .sm-row .td-sm-enable { background:#f1f8f5; }
    .sm-enable-toggle { accent-color:#26a69a; width:1.8rem !important; height:.95rem !important; cursor:pointer; }

    /* ── Disabled perm cells ── */
    .perm-table td.perm-disabled { opacity:.35; pointer-events:none; }

    /* ── "All" column ── */
    .perm-table .td-row-all { background:#fafbff; }
    </style>
    <div class="table-responsive">
    <table class="perm-table">
    <thead>
        <tr>
            <th style="min-width:220px; text-align:left;">Module / Page</th>
            <th class="th-enable-mm" style="width:78px;">
                <i class="bx bx-toggle-right me-1" style="font-size:.85rem;"></i>Menu
            </th>
            <th class="th-enable-sm" style="width:68px;">
                <i class="bx bx-toggle-right me-1" style="font-size:.85rem;"></i>Page
            </th>
            <th style="width:50px; text-align:center; background:#f8f9ff;">All</th>
            <th style="width:68px;">${_colHdrChk('CanView')}<span>View</span></th>
            <th style="width:68px;">${_colHdrChk('CanCreate')}<span>Create</span></th>
            <th style="width:68px;">${_colHdrChk('CanEdit')}<span>Edit</span></th>
            <th style="width:68px;">${_colHdrChk('CanDelete')}<span>Delete</span></th>
        </tr>
    </thead>
    <tbody>`;

    _allMainMenus.forEach(mm => {
        const mp       = mmMap[mm.MainMenuUID] || {};
        const mmOn     = !!(mp.CanView || mp.CanCreate || mp.CanEdit || mp.CanDelete);
        const topSubs  = _allSubMenus.filter(s => s.MainMenuUID == mm.MainMenuUID && !s.ParentSubMenuUID);
        const isDirect = mm.IsDirectLink == 1;

        if (isDirect) {
            // No submenus: show inline permission checkboxes directly on the main menu row
            const mmAllChk = mmOn && _allChecked4(mp);
            const mmdis    = mmOn ? '' : 'perm-disabled';

            html += `
            <tr class="mm-row mm-direct-row" data-mmuid="${mm.MainMenuUID}">
                <td>
                    <i class="${mm.Icon || 'bx bx-menu'} me-1" style="color:#696cff;font-size:.95rem;"></i>
                    <strong>${mm.Name}</strong>
                    <span class="badge ms-1" style="font-size:.6rem;background:#ede9ff;color:#696cff;">
                        <i class="bx bx-link-external" style="font-size:.65rem;"></i> Direct
                    </span>
                </td>
                <td class="text-center td-mm-enable">
                    <div class="form-check form-switch d-flex justify-content-center m-0">
                        <input class="form-check-input mm-enable-toggle" type="checkbox"
                            data-mmuid="${mm.MainMenuUID}" data-isdirect="1" ${mmOn ? 'checked' : ''}>
                    </div>
                </td>
                <td class="text-center" style="background:#e8f5e9;">
                    <span class="badge bg-label-success" style="font-size:.6rem;padding:2px 6px;">
                        <i class="bx bx-link me-1"></i>Direct
                    </span>
                </td>
                <td class="text-center td-row-all">
                    <input type="checkbox" class="form-check-input mm-row-all" style="width:.9rem;height:.9rem;cursor:pointer;"
                        data-mmuid="${mm.MainMenuUID}" title="Toggle all"
                        ${mmAllChk ? 'checked' : ''} ${!mmOn ? 'disabled' : ''}>
                </td>
                <td class="text-center ${mmdis}">${_mmChk(mm.MainMenuUID, 'CanView',   mp.CanView,   !mmOn)}</td>
                <td class="text-center ${mmdis}">${_mmChk(mm.MainMenuUID, 'CanCreate', mp.CanCreate, !mmOn)}</td>
                <td class="text-center ${mmdis}">${_mmChk(mm.MainMenuUID, 'CanEdit',   mp.CanEdit,   !mmOn)}</td>
                <td class="text-center ${mmdis}">${_mmChk(mm.MainMenuUID, 'CanDelete', mp.CanDelete, !mmOn)}</td>
            </tr>`;

        } else {
            html += `
            <tr class="mm-row" data-mmuid="${mm.MainMenuUID}">
                <td>
                    <i class="${mm.Icon || 'bx bx-menu'} me-1" style="color:#696cff;font-size:.95rem;"></i>
                    <strong>${mm.Name}</strong>
                    <span class="badge ms-1" style="font-size:.6rem;background:#e0e3ff;color:#696cff;">${topSubs.length}</span>
                </td>
                <td class="text-center td-mm-enable">
                    <div class="form-check form-switch d-flex justify-content-center m-0">
                        <input class="form-check-input mm-enable-toggle" type="checkbox"
                            data-mmuid="${mm.MainMenuUID}" ${mmOn ? 'checked' : ''}>
                    </div>
                </td>
                <td colspan="6" class="mm-access-note ps-2">
                    ${mmOn
                        ? '<span class="badge bg-label-primary" style="font-size:.65rem;"><i class="bx bx-check me-1"></i>Menu enabled — configure page access below</span>'
                        : '<span class="text-muted" style="font-size:.7rem;">Enable menu to configure pages</span>'
                    }
                </td>
            </tr>`;

            html += _renderSubs(mm.MainMenuUID, null, mmOn, 0);
        }
    });

    html += '</tbody></table></div>';
    return html;
}

/* ── Load permissions for selected role ─────────────────────────────── */
function loadRolePermissions(roleUID) {

    $('#permMatrixEmpty').addClass('d-none');
    $('#permMatrixBody').html('<div class="text-center py-4"><span class="spinner-border text-primary"></span></div>');
    $('#btnSavePermissions').addClass('d-none');

    var fd = new FormData();
    fd.append('RoleUID', roleUID);
    fd.append([CsrfName], CsrfToken);

    AjaxLoading = 0;

    $.ajax({
        url: '/settings/roles/getRolePermissions',
        type: 'POST',
        data: fd,
        contentType: false,
        processData: false,
        success: function(res) {
            if (res.Error === false) {
                $('#btnSavePermissions').removeClass('d-none');
                _permData = res.Data;
                $('#permMatrixBody').html(_buildMatrix(_permData.main || [], _permData.sub || []));
                _syncHeaderCheckboxes();
                _syncAllRowCheckboxes();
            } else {
                $('#permMatrixBody').html('<div class="alert alert-danger m-3">' + res.Message + '</div>');
            }
        },
        error: function() {
            $('#permMatrixBody').html('<div class="alert alert-danger m-3">Failed to load permissions.</div>');
        },
        complete: function () {
            AjaxLoading = 1;
        }
    });
}

/* ── Matrix event bindings (all delegated — called once on ready) ────── */
function _bindMatrixEvents() {

    // 1. Column header "Select all column" checkbox — applies to both sub rows and direct-link main rows
    $(document).off('change', '.col-hdr-chk').on('change', '.col-hdr-chk', function() {
        const field   = $(this).data('field');
        const checked = $(this).is(':checked');
        $(`.sm-perm-chk[data-field="${field}"]`).each(function() {
            const $tr = $(this).closest('tr');
            if (!$tr.hasClass('sm-hidden') && !$(this).prop('disabled')) {
                $(this).prop('checked', checked);
            }
        });
        $(`.mm-perm-chk[data-field="${field}"]`).not(':disabled').prop('checked', checked);
        _syncAllRowCheckboxes();
    });

    // 2. Row "All" checkbox — toggle all 4 perm cells for that page row
    $(document).off('change', '.sm-row-all').on('change', '.sm-row-all', function() {
        const uid     = $(this).data('uid');
        const checked = $(this).is(':checked');
        $(`.sm-perm-chk[data-uid="${uid}"]`).not(':disabled').prop('checked', checked);
        _syncHeaderCheckboxes();
    });

    // 2b. Direct-link main menu "All" checkbox
    $(document).off('change', '.mm-row-all').on('change', '.mm-row-all', function() {
        const mmUID   = $(this).data('mmuid');
        const checked = $(this).is(':checked');
        $(`.mm-perm-chk[data-mmuid="${mmUID}"]`).not(':disabled').prop('checked', checked);
        _syncHeaderCheckboxes();
    });

    // 3. Main-menu Enable toggle — direct-link: enable/disable inline checkboxes; normal: show/hide sub rows
    $(document).off('change', '.mm-enable-toggle').on('change', '.mm-enable-toggle', function() {
        const mmUID    = $(this).data('mmuid');
        const enabled  = $(this).is(':checked');
        const isDirect = $(this).data('isdirect') == 1;
        const $mmRow   = $(`.mm-row[data-mmuid="${mmUID}"]`);

        if (isDirect) {
            if (enabled) {
                $mmRow.find('.mm-perm-chk').prop('disabled', false).closest('td').removeClass('perm-disabled');
                $mmRow.find('.mm-row-all').prop('disabled', false);
            } else {
                $mmRow.find('.mm-perm-chk').prop('checked', false).prop('disabled', true).closest('td').addClass('perm-disabled');
                $mmRow.find('.mm-row-all').prop('checked', false).prop('disabled', true);
            }
        } else {
            const $subRows  = $(`.sm-row[data-mmuid="${mmUID}"]`);
            const $noteCell = $mmRow.find('td[colspan="6"]');
            if (enabled) {
                $noteCell.html('<span class="badge bg-label-primary" style="font-size:.65rem;"><i class="bx bx-check me-1"></i>Menu enabled — configure page access below</span>');
                $subRows.removeClass('sm-hidden');
            } else {
                $noteCell.html('<span class="text-muted" style="font-size:.7rem;">Enable menu to configure pages</span>');
                $subRows.addClass('sm-hidden');
                $subRows.find('.sm-perm-chk').prop('checked', false).prop('disabled', true).closest('td').addClass('perm-disabled');
                $subRows.find('.sm-row-all').prop('checked', false);
                $subRows.find('.sm-enable-toggle').prop('checked', false);
                $subRows.removeClass('sm-enabled');
            }
        }

        _syncAllRowCheckboxes();
        _syncHeaderCheckboxes();
    });

    // 4. Page Enable toggle — enable/disable perm cells for that page row only
    $(document).off('change', '.sm-enable-toggle').on('change', '.sm-enable-toggle', function() {
        const smUID   = $(this).data('smuid');
        const enabled = $(this).is(':checked');
        const $smRow  = $(`.sm-row[data-smuid="${smUID}"]`);

        if (enabled) {
            $smRow.addClass('sm-enabled');
            $smRow.find('.sm-perm-chk').prop('disabled', false).closest('td').removeClass('perm-disabled');
            $smRow.find('.sm-row-all').prop('disabled', false).prop('checked', false);
            $smRow.find(`.sm-perm-chk[data-field="CanView"]`).prop('checked', true);
        } else {
            $smRow.removeClass('sm-enabled');
            $smRow.find('.sm-perm-chk').prop('checked', false).prop('disabled', true).closest('td').addClass('perm-disabled');
            $smRow.find('.sm-row-all').prop('checked', false).prop('disabled', true);
        }

        _syncAllRowCheckboxes();
        _syncHeaderCheckboxes();
    });

    // 5. Any individual perm checkbox change → sync row-All + column headers
    $(document).off('change', '.sm-perm-chk').on('change', '.sm-perm-chk', function() {
        const uid = $(this).data('uid');
        _syncRowAllForUID(uid);
        _syncHeaderCheckboxes();
    });

    // 5b. Direct-link main menu individual checkbox change
    $(document).off('change', '.mm-perm-chk').on('change', '.mm-perm-chk', function() {
        const mmUID = $(this).data('mmuid');
        _syncMmRowAllForUID(mmUID);
        _syncHeaderCheckboxes();
    });
}

/* ── Sync the "All" checkbox for one sub-menu page row ───────────────── */
function _syncRowAllForUID(uid) {
    const $chks   = $(`.sm-perm-chk[data-uid="${uid}"]`).not(':disabled');
    const total   = $chks.length;
    const checked = $chks.filter(':checked').length;
    const $rowAll = $(`.sm-row-all[data-uid="${uid}"]`);
    $rowAll.prop('checked', total > 0 && checked === total);
    $rowAll.prop('indeterminate', checked > 0 && checked < total);
}

/* ── Sync the "All" checkbox for one direct-link main menu row ────────── */
function _syncMmRowAllForUID(mmUID) {
    const $chks   = $(`.mm-perm-chk[data-mmuid="${mmUID}"]`).not(':disabled');
    const total   = $chks.length;
    const checked = $chks.filter(':checked').length;
    const $rowAll = $(`.mm-row-all[data-mmuid="${mmUID}"]`);
    $rowAll.prop('checked', total > 0 && checked === total);
    $rowAll.prop('indeterminate', checked > 0 && checked < total);
}

/* ── Sync ALL row-level "All" checkboxes ─────────────────────────────── */
function _syncAllRowCheckboxes() {
    _allSubMenus.forEach(sm => _syncRowAllForUID(sm.SubMenuUID));
    _allMainMenus.forEach(mm => {
        if (mm.IsDirectLink == 1) _syncMmRowAllForUID(mm.MainMenuUID);
    });
}

/* ── Sync column header checkboxes ──────────────────────────────────── */
function _syncHeaderCheckboxes() {
    ['CanView', 'CanCreate', 'CanEdit', 'CanDelete'].forEach(function(field) {
        let total = 0, checked = 0;
        $(`.sm-perm-chk[data-field="${field}"]`).each(function() {
            const $tr = $(this).closest('tr');
            if (!$tr.hasClass('sm-hidden') && !$(this).prop('disabled')) {
                total++;
                if ($(this).is(':checked')) checked++;
            }
        });
        $(`.mm-perm-chk[data-field="${field}"]`).each(function() {
            if (!$(this).prop('disabled')) {
                total++;
                if ($(this).is(':checked')) checked++;
            }
        });
        const $hdr = $(`.col-hdr-chk[data-field="${field}"]`);
        $hdr.prop('checked', total > 0 && checked === total);
        $hdr.prop('indeterminate', checked > 0 && checked < total);
    });
}

/* ── Collect matrix data into arrays ────────────────────────────────── */
function _collectPermissions() {

    const mainMenus = [];
    const subMenus  = [];

    // Main menus: rows with no submenus save individual checkbox values; normal rows save enabled flag
    _allMainMenus.forEach(mm => {
        const uid      = mm.MainMenuUID;
        const enabled  = $(`.mm-enable-toggle[data-mmuid="${uid}"]`).is(':checked') ? 1 : 0;
        if (mm.IsDirectLink == 1) {
            mainMenus.push({
                MainMenuUID: uid,
                CanView:    enabled && $(`.mm-perm-chk[data-mmuid="${uid}"][data-field="CanView"]`).is(':checked')   ? 1 : 0,
                CanCreate:  enabled && $(`.mm-perm-chk[data-mmuid="${uid}"][data-field="CanCreate"]`).is(':checked') ? 1 : 0,
                CanEdit:    enabled && $(`.mm-perm-chk[data-mmuid="${uid}"][data-field="CanEdit"]`).is(':checked')   ? 1 : 0,
                CanDelete:  enabled && $(`.mm-perm-chk[data-mmuid="${uid}"][data-field="CanDelete"]`).is(':checked') ? 1 : 0,
                Sorting:    mm.Sorting || 0,
            });
        } else {
            mainMenus.push({
                MainMenuUID: uid,
                CanView:    enabled,
                CanCreate:  enabled,
                CanEdit:    enabled,
                CanDelete:  enabled,
                Sorting:    mm.Sorting || 0,
            });
        }
    });

    _allSubMenus.forEach(sm => {
        const uid  = sm.SubMenuUID;
        const smOn = $(`.sm-enable-toggle[data-smuid="${uid}"]`).is(':checked');
        subMenus.push({
            SubMenuUID: uid,
            CanView:   smOn && $(`.sm-perm-chk[data-uid="${uid}"][data-field="CanView"]`).is(':checked')   ? 1 : 0,
            CanCreate: smOn && $(`.sm-perm-chk[data-uid="${uid}"][data-field="CanCreate"]`).is(':checked') ? 1 : 0,
            CanEdit:   smOn && $(`.sm-perm-chk[data-uid="${uid}"][data-field="CanEdit"]`).is(':checked')   ? 1 : 0,
            CanDelete: smOn && $(`.sm-perm-chk[data-uid="${uid}"][data-field="CanDelete"]`).is(':checked') ? 1 : 0,
            Sorting:   sm.Sorting || 0,
        });
    });

    return { mainMenus, subMenus };
}

/* ── jQuery ready ────────────────────────────────────────────────────── */
$(function() {
    'use strict';

    // Refresh Tokens button
    $('#btnRefreshTokens').on('click', function() {
        var $btn = $(this).prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i>');
        var fd = new FormData();
        fd.append(CsrfName, CsrfToken);
        $.ajax({
            url: '/settings/roles/refreshTokens',
            type: 'POST',
            data: fd,
            contentType: false,
            processData: false,
            success: function(res) {
                if (res.Error === false) {
                    showToastNotification(res.Message, 'success');
                    setTimeout(function() { location.reload(); }, 800);
                } else {
                    $btn.prop('disabled', false).html('<i class="bx bx-refresh"></i>');
                    showToastNotification(res.Message, 'error');
                }
            },
            error: function() {
                $btn.prop('disabled', false).html('<i class="bx bx-refresh"></i>');
                showToastNotification('Refresh failed. Please try again.', 'error');
            }
        });
    });

    // Bind all delegated matrix events once
    _bindMatrixEvents();

    // Role list click
    $(document).on('click', '.role-item', function() {
        const roleUID  = $(this).data('roleuid');
        const roleName = $(this).data('rolename');
        _activeRoleUID = roleUID;
        $('.role-item').removeClass('active');
        $(this).addClass('active');
        $('#permMatrixTitle').html('<i class="bx bx-lock-open-alt me-1 text-primary"></i>Permissions — <strong>' + roleName + '</strong>');
        loadRolePermissions(roleUID);
    });

    // Add Role button
    $('#btnAddRole').click(function() {
        $('#RoleModalUID').val(0);
        $('#RoleName').val('');
        $('#roleModalTitle').html('<i class="bx bx-shield-alt-2 me-1"></i>Add Role');
        $('#roleModal').modal('show');
    });

    // Edit Role button (custom roles only)
    $(document).on('click', '.edit-role-btn', function(e) {
        e.stopPropagation();
        $('#RoleModalUID').val($(this).data('roleuid'));
        $('#RoleName').val($(this).data('rolename'));
        $('#roleModalTitle').html('<i class="bx bx-edit me-1"></i>Rename Role');
        $('#roleModal').modal('show');
    });

    // Save Role
    $('#saveRoleBtn').click(function() {
        const name = $.trim($('#RoleName').val());
        if (!name) { showToastNotification('Role name is required.', 'error'); return; }

        $('#saveRoleSpinner').removeClass('d-none');
        $(this).prop('disabled', true);

        var fd = new FormData();
        fd.append('RoleUID',  $('#RoleModalUID').val());
        fd.append('RoleName', name);
        fd.append([CsrfName], CsrfToken);

        $.ajax({
            url: '/settings/roles/saveRole',
            type: 'POST',
            data: fd,
            contentType: false,
            processData: false,
            success: function(res) {
                $('#saveRoleSpinner').addClass('d-none');
                $('#saveRoleBtn').prop('disabled', false);
                if (res.Error === false) {
                    showToastNotification(res.Message, 'success');
                    $('#roleModal').modal('hide');
                    setTimeout(() => location.reload(), 800);
                } else {
                    showToastNotification(res.Message, 'error');
                }
            },
            error: function() {
                $('#saveRoleSpinner').addClass('d-none');
                $('#saveRoleBtn').prop('disabled', false);
                showToastNotification('Request failed.', 'error');
            }
        });
    });

    // Save Permissions
    $('#btnSavePermissions').click(function() {
        if (!_activeRoleUID) return;

        const { mainMenus, subMenus } = _collectPermissions();

        $('#savePrmSpinner').removeClass('d-none');
        $(this).prop('disabled', true);

        var fd = new FormData();
        fd.append('RoleUID',   _activeRoleUID);
        fd.append('MainMenus', JSON.stringify(mainMenus));
        fd.append('SubMenus',  JSON.stringify(subMenus));
        fd.append([CsrfName], CsrfToken);

        $.ajax({
            url: '/settings/roles/saveRolePermissions',
            type: 'POST',
            data: fd,
            contentType: false,
            processData: false,
            success: function(res) {
                $('#savePrmSpinner').addClass('d-none');
                $('#btnSavePermissions').prop('disabled', false);
                if (res.Error === false) {
                    showToastNotification(res.Message, 'success');
                } else {
                    showToastNotification(res.Message, 'error');
                }
            },
            error: function() {
                $('#savePrmSpinner').addClass('d-none');
                $('#btnSavePermissions').prop('disabled', false);
                showToastNotification('Request failed.', 'error');
            }
        });
    });

    // Delete Role
    $(document).on('click', '.delete-role-btn', function(e) {
        e.stopPropagation();
        const roleUID = $(this).data('roleuid');
        Swal.fire({
            title: 'Delete this role?',
            text: 'Users assigned to this role must be reassigned first.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Yes, delete',
        }).then(result => {
            if (!result.isConfirmed) return;

            var fd = new FormData();
            fd.append('RoleUID', roleUID);
            fd.append([CsrfName], CsrfToken);

            $.ajax({
                url: '/settings/roles/deleteRole',
                type: 'POST',
                data: fd,
                contentType: false,
                processData: false,
                success: function(res) {
                    if (res.Error === false) {
                        showToastNotification(res.Message, 'success');
                        setTimeout(() => location.reload(), 800);
                    } else {
                        showToastNotification(res.Message, 'error');
                    }
                },
                error: function() { showToastNotification('Request failed.', 'error'); }
            });
        });
    });

});
</script>
