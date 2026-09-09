<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php $this->load->view('common/header'); ?>

<div class="layout-wrapper layout-horizontal layout-content-navbar">
    <div class="layout-container">

        <?php $this->load->view('common/menu_view'); ?>

        <div class="layout-page">
            <div class="content-wrapper apex-content">
                <?php $this->load->view('common/apex/page_header', [
                    'pageTitle'       => $PageTitle       ?? 'Prefix Configuration',
                    'pageDescription' => $PageDescription ?? '',
                ]); ?>
                <div class="container-xxl flex-grow-1">

                    <div class="card">

                        <!-- Info bar -->
                        <div class="px-3 py-2 border-bottom d-flex align-items-center gap-2 flex-wrap">
                            <span class="badge bg-label-secondary d-inline-flex align-items-center gap-1" style="font-size:.78rem;font-weight:500;">
                                <i class="bx bx-info-circle"></i>
                                Prefixes define how your transaction numbers are formatted
                            </span>
                            <span class="badge bg-label-primary d-inline-flex align-items-center gap-1" style="font-size:.78rem;font-weight:500;" id="pcModuleCountBadge">
                                <?php echo (int)$PrefixModuleCount; ?> module<?php echo $PrefixModuleCount != 1 ? 's' : ''; ?> support prefix numbering
                            </span>
                            <div class="ms-auto">
                                <button class="btn btn-primary btn-sm px-3" id="btnAddPrefixConfig">
                                    <i class="bx bx-plus me-1"></i><?php echo t('btn_add_prefix', 'Add Prefix'); ?>
                                </button>
                            </div>
                        </div>

                        <!-- Table -->
                        <div class="table-responsive text-nowrap tablecard">
                            <table class="table trans-table MainviewTable" id="PrefixConfigTable">
                                <thead class="r2k-thead">
                                    <tr>
                                        <th class="text-center" style="width:50px;"><?php echo t('col_sno', 'S.No'); ?></th>
                                        <th style="min-width:120px;">Module</th>
                                        <th style="min-width:180px;">Preview</th>
                                        <th style="min-width:80px;"><?php echo t('col_name', 'Name'); ?></th>
                                        <th style="white-space:normal;min-width:260px;">Configuration</th>
                                        <th class="text-center" style="width:70px;">Default</th>
                                        <th style="min-width:140px;"><?php echo t('col_last_updated', 'Last Updated'); ?></th>
                                        <th class="text-center" style="width:90px;"><?php echo t('col_actions', 'Actions'); ?></th>
                                    </tr>
                                </thead>
                                <tbody id="PrefixConfigBody" class="r2k-tbody table-border-bottom-0">
                                    <?php echo $ModRowData ?? ''; ?>
                                </tbody>
                            </table>
                        </div>

                    </div><!-- /card -->

                </div>
            </div>

            <!-- ============================================================
                 Add / Edit Prefix Configuration Modal
            ============================================================ -->
            <div class="modal fade" id="prefixConfigModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true" style="padding:0!important;">
                <div class="modal-dialog modal-lg modal-dialog-scrollable" style="height:100vh;max-height:100vh;margin:0 auto;">
                    <div class="modal-content h-100 d-flex flex-column">

                        <div class="modal-header bg-white border-bottom d-flex align-items-center justify-content-between px-3 py-2 trans-theme">
                            <div class="d-flex align-items-center gap-3">
                                <div class="modal-doc-icon bg-primary bg-opacity-10">
                                    <i class="bx bx-hash text-primary modal-doc-icon-inner"></i>
                                </div>
                                <div>
                                    <h5 class="modal-title mb-0" id="pcModalTitle">Add Prefix Configuration</h5>
                                    <div class="text-muted small" id="pcModalSubtitle">Configure how your transaction numbers are generated</div>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <button type="button" class="btn btn-sm btn-primary" id="savePrefixConfigBtn">
                                    <i class="bx bx-check me-1"></i><?php echo t('btn_save', 'Save'); ?>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger" data-bs-dismiss="modal" aria-label="Close">
                                    <i class="bx bx-x me-1"></i><?php echo t('close', 'Close'); ?>
                                </button>
                            </div>
                        </div>

                        <div class="modal-body p-4 flex-grow-1 overflow-auto">

                            <input type="hidden" id="pcPrefixUID" value="0">

                            <!-- ── Module Selection (add mode only) ──────── -->
                            <div id="pcModuleRow" class="mb-4">
                                <label for="pcModuleUID" class="form-label fw-semibold">
                                    Module <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="pcModuleUID">
                                    <option value="">— Select Module —</option>
                                </select>
                                <div class="form-text">Select the transaction type this prefix applies to.</div>
                            </div>

                            <!-- ── Prefix Name ───────────────────────────── -->
                            <div class="mb-4">
                                <label for="pcPrefixName" class="form-label fw-semibold">
                                    Prefix Name <span class="text-danger">*</span>
                                </label>
                                <input type="text"
                                       class="form-control text-uppercase fw-bold"
                                       id="pcPrefixName"
                                       placeholder="e.g. INV, QT, PO"
                                       maxlength="7"
                                       oninput="this.value=this.value.replace(/[^A-Za-z0-9]/g,'').toUpperCase()" />
                                <div class="form-text">2 – 7 alphanumeric characters only.</div>
                            </div>

                            <hr class="my-3">

                            <!-- ── Component Builder ─────────────────────── -->
                            <div class="mb-4">
                                <label class="form-label fw-semibold">
                                    <i class="bx bx-sort-alt-2 me-1 text-muted"></i>Number Components
                                    <small class="text-muted fw-normal ms-1">drag to reorder &middot; toggle optional parts</small>
                                </label>
                                <div id="pcComponentList" class="pc-comp-list"></div>
                            </div>

                            <hr class="my-3">

                            <!-- ── Number Format ─────────────────────────── -->
                            <div class="mb-4">
                                <label class="form-label fw-semibold">Number format</label>
                                <div class="d-flex flex-wrap gap-2" id="pcPadBtnGroup">
                                    <button type="button" class="btn btn-outline-secondary pc-pad-btn"        data-pad="1">1, 2, 3&hellip;</button>
                                    <button type="button" class="btn btn-outline-secondary pc-pad-btn active" data-pad="3">001, 002&hellip;</button>
                                </div>
                                <input type="hidden" id="pcNumberPadding" value="3">
                            </div>

                            <!-- ── Set as Default ───────────────────────── -->
                            <div class="d-flex justify-content-between align-items-start mb-4">
                                <div>
                                    <label class="form-label fw-semibold mb-0">
                                        <i class="bx bxs-star me-1 text-warning"></i>Set as Default
                                    </label>
                                    <div class="form-text mb-0" id="pcDefaultHint">
                                        The default prefix is used for new transactions in this module when no other is selected.
                                    </div>
                                </div>
                                <div class="form-check form-switch ms-3 mb-0">
                                    <input class="form-check-input" type="checkbox" id="pcIsDefault" role="switch">
                                </div>
                            </div>

                            <hr class="my-3">

                            <!-- ── Live Preview ──────────────────────────── -->
                            <div>
                                <label class="form-label fw-semibold">
                                    <i class="bx bx-show me-1"></i>Live Preview
                                </label>
                                <div class="prefix-preview-box p-3 rounded-3 border bg-body-tertiary">
                                    <div class="d-flex align-items-center flex-wrap gap-1 mb-2" id="pcPreviewBlocks">
                                        <!-- Rendered by JS -->
                                    </div>
                                    <div class="d-flex align-items-center gap-2 mt-2">
                                        <span class="text-muted small">Full number:</span>
                                        <code class="fs-6 text-primary fw-bold" id="pcFullPreview"></code>
                                    </div>
                                </div>
                            </div>

                        </div><!-- /modal-body -->

                    </div>
                </div>
            </div>
            <!-- / Add / Edit Prefix Modal -->

            <?php $this->load->view('common/footer_desc'); ?>

        </div><!-- /layout-page -->

    </div>
</div>

<?php $this->load->view('common/footer'); ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.js"></script>

<script>
var CsrfName  = '<?php echo $this->security->get_csrf_token_name(); ?>';
var CsrfToken = '<?php echo $this->security->get_csrf_hash(); ?>';

window.addEventListener('load', function () {
    'use strict';

    var prefixAllModules = <?php echo $PrefixModulesJson ?? '{}'; ?>;
    var pcSortable       = null;

    /* ── Component state ──────────────────────────────────── */
    var _pc = {
        order:     ['prefix', 'shortname', 'fiscal', 'number'],
        seps:      ['-', '-', '-'],
        active:    { prefix: true, shortname: false, fiscal: false, number: true },
        shortname: '',
        fiscalFmt: 'SHORT'
    };

    /* ── Component definitions ────────────────────────────── */
    var PC_DEFS = {
        prefix:    { label: 'Prefix',      icon: 'bx-hash',      colorCls: 'text-primary', bgCls: 'bg-primary', alwaysOn: true  },
        shortname: { label: 'Short Name',  icon: 'bx-buildings', colorCls: 'text-warning', bgCls: 'bg-warning', alwaysOn: false },
        fiscal:    { label: 'Fiscal Year', icon: 'bx-calendar',  colorCls: 'text-info',    bgCls: 'bg-info',    alwaysOn: false },
        number:    { label: 'Number',      icon: 'bxs-dial-pad', colorCls: 'text-success', bgCls: 'bg-success', alwaysOn: true  }
    };

    var SEP_OPTIONS = [
        { val: '',  lbl: 'None' },
        { val: '-', lbl: '&minus;' },
        { val: '/', lbl: '/' },
        { val: '|', lbl: '|' },
        { val: '_', lbl: '_' },
        { val: '.', lbl: '.' }
    ];

    var BLOCK_COLORS = ['bg-primary', 'bg-warning text-dark', 'bg-info text-dark', 'bg-success'];

    /* ── Utilities ────────────────────────────────────────── */
    function escHtml(s) {
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    /**
     * @param {string} format 'SHORT' | 'LONG'
     * @returns {string}
     */
    function getFiscalYear(format) {
        var d  = new Date();
        var m  = d.getMonth() + 1;
        var y  = d.getFullYear();
        var fy = m >= 4 ? y : y - 1;
        if (format === 'LONG') return fy + '-' + (fy + 1);
        return String(fy % 100).padStart(2, '0') + '-' + String((fy + 1) % 100).padStart(2, '0');
    }

    /**
     * @param {number} n
     * @param {number} padding
     * @returns {string}
     */
    function padNum(n, padding) {
        padding = parseInt(padding, 10);
        return padding > 1 ? String(n).padStart(padding, '0') : String(n);
    }

    /**
     * @param {string} key
     * @returns {string}
     */
    function getCompVal(key) {
        switch (key) {
            case 'prefix':    return ($('#pcPrefixName').val() || 'PREFIX').trim().toUpperCase();
            case 'shortname': return _pc.shortname || '—';
            case 'fiscal':    return getFiscalYear(_pc.fiscalFmt);
            case 'number':    return padNum(1, parseInt($('#pcNumberPadding').val(), 10) || 3);
        }
        return '?';
    }

    /* ── Render component builder ─────────────────────────── */
    function renderComponentList() {
        var html = '';

        _pc.order.forEach(function (key, idx) {
            var def      = PC_DEFS[key];
            var isActive = def.alwaysOn || !!_pc.active[key];
            var cardCls  = 'pc-card' + (isActive ? ' pc-active' : ' pc-inactive');

            html += '<div class="pc-item" data-key="' + key + '">';

            /* Card row */
            html += '<div class="' + cardCls + '">';
            html += '<span class="pc-drag-handle"><i class="bx bx-grid-vertical"></i></span>';
            html += '<div class="pc-comp-icon ' + def.bgCls + ' bg-opacity-10">'
                  + '<i class="bx ' + def.icon + ' ' + def.colorCls + '"></i></div>';
            html += '<div class="pc-comp-info">'
                  + '<div class="pc-comp-name">' + escHtml(def.label) + '</div>'
                  + '<div class="pc-comp-value">' + escHtml(getCompVal(key)) + '</div>'
                  + '</div>';

            if (def.alwaysOn) {
                html += '<span class="badge bg-label-secondary ms-auto" style="font-size:.68rem;">required</span>';
            } else {
                html += '<div class="form-check form-switch mb-0 ms-auto">'
                      + '<input class="form-check-input pc-comp-toggle" type="checkbox" data-key="' + key + '"'
                      + (isActive ? ' checked' : '') + '></div>';
            }
            html += '</div>'; /* /pc-card */

            /* Options row — only for active optional components */
            if (!def.alwaysOn && isActive) {
                html += '<div class="pc-comp-options">';
                if (key === 'shortname') {
                    html += '<label class="form-label small fw-semibold text-muted mb-1">Company Short Name</label>';
                    html += '<input type="text" class="form-control form-control-sm text-uppercase fw-bold" '
                          + 'id="pcShortNameInput" maxlength="10" placeholder="e.g. RK, BHARAT" '
                          + 'value="' + escHtml(_pc.shortname) + '" '
                          + 'oninput="_pc.shortname=this.value.replace(/[^A-Za-z0-9]/g,\'\').toUpperCase();'
                          + 'this.value=_pc.shortname;_pcRefreshVal(\'shortname\');updateLivePreview();">';
                } else if (key === 'fiscal') {
                    var shortChk = _pc.fiscalFmt !== 'LONG' ? ' checked' : '';
                    var longChk  = _pc.fiscalFmt === 'LONG'  ? ' checked' : '';
                    html += '<label class="form-label small fw-semibold text-muted mb-1">Year Format</label>';
                    html += '<div class="d-flex gap-3">'
                          + '<div class="form-check"><input class="form-check-input" type="radio" '
                          + 'name="pcFiscalFmtInner" value="SHORT" id="pcFySI"' + shortChk + '>'
                          + '<label class="form-check-label small" for="pcFySI">Short <code>'
                          + getFiscalYear('SHORT') + '</code></label></div>'
                          + '<div class="form-check"><input class="form-check-input" type="radio" '
                          + 'name="pcFiscalFmtInner" value="LONG" id="pcFyLI"' + longChk + '>'
                          + '<label class="form-check-label small" for="pcFyLI">Full <code>'
                          + getFiscalYear('LONG') + '</code></label></div>'
                          + '</div>';
                }
                html += '</div>'; /* /pc-comp-options */
            }

            html += '</div>'; /* /pc-item */

            /* Separator row — only between two consecutive active components */
            if (idx < _pc.order.length - 1) {
                var nextKey    = _pc.order[idx + 1];
                var nextActive = PC_DEFS[nextKey].alwaysOn || !!_pc.active[nextKey];
                var showSep    = isActive && nextActive;
                var curSep     = _pc.seps[idx] !== undefined ? _pc.seps[idx] : '-';

                html += '<div class="pc-sep-row"' + (showSep ? '' : ' hidden') + ' data-pos="' + idx + '">';
                html += '<div class="pc-sep-line"></div><div class="pc-sep-btns">';
                SEP_OPTIONS.forEach(function (opt) {
                    html += '<button type="button" class="pc-sep-btn' + (curSep === opt.val ? ' active' : '')
                          + '" data-pos="' + idx + '" data-val="' + opt.val + '">' + opt.lbl + '</button>';
                });
                html += '</div><div class="pc-sep-line"></div></div>'; /* /pc-sep-row */
            }
        });

        $('#pcComponentList').html(html);

        /* Init SortableJS */
        if (pcSortable) { try { pcSortable.destroy(); } catch (e) {} }
        var el = document.getElementById('pcComponentList');
        if (el && typeof Sortable !== 'undefined') {
            pcSortable = Sortable.create(el, {
                animation  : 150,
                handle     : '.pc-drag-handle',
                draggable  : '.pc-item',
                ghostClass : 'pc-ghost',
                chosenClass: 'pc-chosen',
                onEnd: function () {
                    var newOrder = [];
                    $('#pcComponentList .pc-item').each(function () { newOrder.push($(this).data('key')); });
                    _pc.order = newOrder;
                    renderComponentList();
                    updateLivePreview();
                }
            });
        }
    }

    /**
     * @param {string} key
     */
    function _pcRefreshVal(key) {
        $('#pcComponentList .pc-item[data-key="' + key + '"] .pc-comp-value').text(getCompVal(key));
    }

    /* ── Live Preview ─────────────────────────────────────── */
    function updateLivePreview() {
        var activeParts = [];
        _pc.order.forEach(function (key, idx) {
            if (PC_DEFS[key].alwaysOn || _pc.active[key]) {
                activeParts.push({ key: key, orderIdx: idx, label: getCompVal(key) });
            }
        });

        var html = '';
        activeParts.forEach(function (part, i) {
            html += '<span class="badge ' + BLOCK_COLORS[i % BLOCK_COLORS.length]
                  + ' px-3 py-2" style="font-size:.85rem;letter-spacing:.04em">'
                  + escHtml(part.label) + '</span>';
            if (i < activeParts.length - 1) {
                var sep = _pc.seps[part.orderIdx];
                if (sep === undefined) sep = '-';
                if (sep === '') {
                    html += '<span class="pc-sep-join">+</span>';
                } else {
                    html += '<span class="fw-bold text-muted mx-1" style="font-size:1.1rem">' + escHtml(sep) + '</span>';
                }
            }
        });
        $('#pcPreviewBlocks').html(html);

        var full = '';
        activeParts.forEach(function (part, i) {
            full += part.label;
            if (i < activeParts.length - 1) {
                var sep = _pc.seps[part.orderIdx];
                full += sep !== undefined ? sep : '-';
            }
        });
        $('#pcFullPreview').text(full || '—');
    }

    /* ── Load list ────────────────────────────────────────── */
    function loadPrefixConfigList() {
        $('#PrefixConfigBody').html(
            '<tr><td colspan="8" class="text-center py-4 text-muted">'
            + '<span class="spinner-border spinner-border-sm me-2"></span>Loading...</td></tr>'
        );
        ajaxLoading(0);
        $.ajax({
            url    : '/settings/getPrefixConfigList',
            method : 'POST',
            data   : { [CsrfName]: CsrfToken },
            success: function (resp) {
                ajaxLoading(1);
                CsrfToken = resp.NewCsrfToken || CsrfToken;
                if (resp.Error) { showToastNotification(resp.Message, 'error'); return; }
                prefixAllModules = resp.Modules || {};
                $('#PrefixConfigBody').html(resp.RecordHtmlData);
                initTooltips();
            },
            error: function () { ajaxLoading(1); showToastNotification('Failed to load prefix configurations.', 'error'); }
        });
    }

    function initTooltips() {
        $('[data-bs-toggle="tooltip"]').each(function () {
            var tt = bootstrap.Tooltip.getInstance(this);
            if (tt) tt.dispose();
            new bootstrap.Tooltip(this);
        });
    }

    /* ── Reset modal ─────────────────────────────────────── */
    function resetModal() {
        _pc = {
            order:     ['prefix', 'shortname', 'fiscal', 'number'],
            seps:      ['-', '-', '-'],
            active:    { prefix: true, shortname: false, fiscal: false, number: true },
            shortname: '',
            fiscalFmt: 'SHORT'
        };
        $('#pcPrefixUID').val('0');
        $('#pcModuleUID').val('');
        $('#pcPrefixName').val('');
        $('#pcPadBtnGroup .pc-pad-btn').removeClass('active');
        $('#pcPadBtnGroup .pc-pad-btn[data-pad="3"]').addClass('active');
        $('#pcNumberPadding').val('3');
        $('#pcIsDefault').prop('checked', false).prop('disabled', false);
        $('#pcDefaultHint').text('The default prefix is used for new transactions in this module when no other is selected.');
        $('#pcModuleRow').show();
        $('#pcModalSubtitle').text('Configure how your transaction numbers are generated');
        renderComponentList();
        updateLivePreview();
    }

    /* ── Open Add ─────────────────────────────────────────── */
    $('#btnAddPrefixConfig').on('click', function () {
        resetModal();
        $('#pcModalTitle').html('Add Prefix Configuration');
        var $sel = $('#pcModuleUID').empty().append('<option value="">— Select Module —</option>');
        $.each(prefixAllModules, function (uid, name) { $sel.append($('<option></option>').val(uid).text(name)); });
        $('#prefixConfigModal').modal('show');
    });

    /* ── Open Edit ────────────────────────────────────────── */
    $(document).on('click', '.EditPrefixConfig', function () {
        var cfg = $(this).data('config');
        if (!cfg) return;

        resetModal();
        $('#pcPrefixUID').val(cfg.PrefixUID);
        $('#pcModalTitle').html('<i class="bx bx-edit me-2" style="color:#7c3aed;"></i>Edit Prefix — ' + escHtml(cfg.Name));
        $('#pcModalSubtitle').text('Module: ' + (cfg.ModuleName || 'Not assigned'));
        $('#pcModuleRow').hide();

        /* Restore _pc from ComponentConfig or derive from old fields */
        if (cfg.ComponentConfig) {
            try { _pc = JSON.parse(cfg.ComponentConfig); } catch (e) {}
        } else {
            var oldSep = cfg.Separator || '-';
            _pc = {
                order:     ['prefix', 'shortname', 'fiscal', 'number'],
                seps:      [oldSep, oldSep, oldSep],
                active:    {
                    prefix:    true,
                    shortname: parseInt(cfg.IncludeShortName, 10) === 1,
                    fiscal:    parseInt(cfg.IncludeFiscalYear, 10) === 1,
                    number:    true
                },
                shortname: cfg.ShortName          || '',
                fiscalFmt: cfg.FiscalYearFormat   || 'SHORT'
            };
        }

        $('#pcPrefixName').val(cfg.Name || '');

        var pad = String(cfg.NumberPadding || 3);
        $('#pcPadBtnGroup .pc-pad-btn').removeClass('active');
        $('#pcPadBtnGroup .pc-pad-btn[data-pad="' + pad + '"]').addClass('active');
        $('#pcNumberPadding').val(pad);

        var isDefault = parseInt(cfg.IsDefault, 10) === 1;
        $('#pcIsDefault').prop('checked', isDefault).prop('disabled', isDefault);
        $('#pcDefaultHint').text(isDefault
            ? 'This is the current default. To reassign, set another prefix as default from the list.'
            : 'The default prefix is used for new transactions in this module when no other is selected.');

        renderComponentList();
        updateLivePreview();
        $('#prefixConfigModal').modal('show');
    });

    /* ── Events: component toggle ─────────────────────────── */
    $(document).on('change', '.pc-comp-toggle', function () {
        var key = $(this).data('key');
        _pc.active[key] = this.checked;
        if (key === 'shortname' && !this.checked) _pc.shortname = '';
        renderComponentList();
        updateLivePreview();
    });

    /* ── Events: separator button ─────────────────────────── */
    $(document).on('click', '.pc-sep-btn', function () {
        var pos = parseInt($(this).data('pos'), 10);
        _pc.seps[pos] = $(this).data('val');
        $(this).closest('.pc-sep-btns').find('.pc-sep-btn').removeClass('active');
        $(this).addClass('active');
        updateLivePreview();
    });

    /* ── Events: fiscal format ────────────────────────────── */
    $(document).on('change', 'input[name="pcFiscalFmtInner"]', function () {
        _pc.fiscalFmt = $(this).val();
        _pcRefreshVal('fiscal');
        updateLivePreview();
    });

    /* ── Events: padding ──────────────────────────────────── */
    $('#pcPadBtnGroup').on('click', '.pc-pad-btn', function () {
        $('#pcPadBtnGroup .pc-pad-btn').removeClass('active');
        $(this).addClass('active');
        $('#pcNumberPadding').val($(this).data('pad'));
        _pcRefreshVal('number');
        updateLivePreview();
    });

    /* ── Events: prefix name input ────────────────────────── */
    $('#pcPrefixName').on('input', function () {
        _pcRefreshVal('prefix');
        updateLivePreview();
    });

    /* ── Delete ───────────────────────────────────────────── */
    $(document).on('click', '.DeletePrefixConfig', function () {
        var uid  = $(this).data('uid');
        var name = $(this).data('name');
        Swal.fire({
            title            : 'Delete prefix "' + escHtml(name) + '"?',
            text             : 'This cannot be undone. Transaction documents already using this prefix keep their numbers.',
            icon             : 'warning',
            showCancelButton : true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonColor: '#6c757d',
        }).then(function (result) {
            if (!result.isConfirmed) return;
            $.ajax({
                url   : '/settings/deletePrefixConfig',
                method: 'POST',
                data  : { prePrefixUID: uid, [CsrfName]: CsrfToken },
                success: function (resp) {
                    CsrfToken = resp.NewCsrfToken || CsrfToken;
                    if (resp.Error) { Swal.fire({ icon: 'error', title: 'Error', text: resp.Message }); }
                    else { loadPrefixConfigList(); showToastNotification(resp.Message, 'success'); }
                },
                error: function () { showToastNotification('Server error. Please try again.', 'error'); }
            });
        });
    });

    /* ── Set Default ──────────────────────────────────────── */
    $(document).on('click', '.SetDefaultPrefixConfig', function () {
        var uid = $(this).data('uid');
        $.ajax({
            url   : '/settings/setDefaultPrefixConfig',
            method: 'POST',
            data  : { prePrefixUID: uid, [CsrfName]: CsrfToken },
            success: function (resp) {
                hideUIBlock();
                CsrfToken = resp.NewCsrfToken || CsrfToken;
                if (resp.Error) { showToastNotification(resp.Message, 'error'); }
                else { loadPrefixConfigList(); showToastNotification(resp.Message, 'success'); }
            },
            error: function () { hideUIBlock(); showToastNotification('Server error. Please try again.', 'error'); }
        });
    });

    /* ── Save ─────────────────────────────────────────────── */
    $('#savePrefixConfigBtn').on('click', function () {
        var $btn   = $(this);
        var uid    = parseInt($('#pcPrefixUID').val(), 10) || 0;
        var modUID = parseInt($('#pcModuleUID').val(), 10) || 0;

        if (!uid && !modUID) {
            showToastNotification('Please select a module.', 'error');
            $('#pcModuleUID').focus(); return;
        }
        var prefixName = ($('#pcPrefixName').val() || '').trim().toUpperCase();
        if (!prefixName || prefixName.length < 2 || prefixName.length > 7) {
            showToastNotification('Prefix name must be 2 – 7 characters.', 'error');
            $('#pcPrefixName').focus(); return;
        }
        if (!/^[A-Z0-9]+$/.test(prefixName)) {
            showToastNotification('Prefix name must be alphanumeric only (A-Z, 0-9).', 'error');
            $('#pcPrefixName').focus(); return;
        }
        if (_pc.active.shortname && !_pc.shortname) {
            showToastNotification('Company short name is required when enabled.', 'error'); return;
        }

        /* Derive backward-compat individual fields from _pc state */
        var firstSep = (_pc.seps[0] !== undefined) ? _pc.seps[0] : '-';

        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Saving...');

        $.ajax({
            url   : '/settings/savePrefixConfig',
            method: 'POST',
            data  : {
                prePrefixUID      : uid,
                preModuleUID      : modUID,
                transPrefixName   : prefixName,
                includeFiscalYear : _pc.active.fiscal    ? '1' : '',
                fiscalYearFormat  : _pc.fiscalFmt        || 'SHORT',
                includeShortName  : _pc.active.shortname ? '1' : '',
                companyShortName  : _pc.shortname        || '',
                prefixSeparator   : firstSep,
                numberPadding     : $('#pcNumberPadding').val() || '3',
                componentConfig   : JSON.stringify(_pc),
                isDefault         : $('#pcIsDefault').is(':checked') ? '1' : '',
                [CsrfName]        : CsrfToken,
            },
            success: function (resp) {
                CsrfToken = resp.NewCsrfToken || CsrfToken;
                $btn.prop('disabled', false).html('<i class="bx bx-check me-1"></i>Save');
                if (resp.Error) { showToastNotification(resp.Message, 'error'); return; }
                $('#prefixConfigModal').modal('hide');
                prefixAllModules = resp.Modules || prefixAllModules;
                $('#PrefixConfigBody').html(resp.RecordHtmlData);
                initTooltips();
                showToastNotification(resp.Message, 'success');
            },
            error: function () {
                $btn.prop('disabled', false).html('<i class="bx bx-check me-1"></i>Save');
                showToastNotification('Server error. Please try again.', 'error');
            }
        });
    });

    /* ── Reset on close ───────────────────────────────────── */
    $('#prefixConfigModal').on('hidden.bs.modal', resetModal);

});
</script>
