<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php
    // Fiscal year for server-side hint text in the modal
    $_month   = (int)date('m');
    $_yr      = (int)date('Y');
    $_fyStart = $_month >= 4 ? $_yr : $_yr - 1;
    $_fyShort = str_pad($_fyStart % 100, 2, '0', STR_PAD_LEFT) . '-' . str_pad(($_fyStart + 1) % 100, 2, '0', STR_PAD_LEFT);
    $_fyLong  = $_fyStart . '-' . ($_fyStart + 1);
?>

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
                <div class="container-xxl flex-grow-1 container-p-y pt-2">

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
                                    <i class="bx bx-plus me-1"></i>Add Prefix
                                </button>
                            </div>
                        </div>

                        <!-- Table -->
                        <div class="table-responsive text-nowrap tablecard">
                            <table class="table trans-table MainviewTable" id="PrefixConfigTable">
                                <thead class="r2k-thead">
                                    <tr>
                                        <th class="text-center" style="width:50px;">S.No</th>
                                        <th style="min-width:120px;">Module</th>
                                        <th style="min-width:180px;">Preview</th>
                                        <th style="min-width:80px;">Name</th>
                                        <th style="white-space:normal;min-width:260px;">Configuration</th>
                                        <th class="text-center" style="width:70px;">Default</th>
                                        <th style="min-width:140px;">Last Updated</th>
                                        <th class="text-center" style="width:90px;">Actions</th>
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
            <!-- / Content wrapper -->

            <!-- ============================================================
                 Add / Edit Prefix Configuration Modal
            ============================================================ -->
            <div class="modal fade" id="prefixConfigModal" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content">

                        <div class="modal-header p-0">
                            <div style="width:100%;padding:14px 20px;border-left:4px solid #7c3aed;background:#ede9fe;">
                                <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
                                    <div style="display:flex;align-items:center;gap:12px;">
                                        <div style="background:rgba(124,58,237,.13);border-radius:10px;padding:9px 11px;flex-shrink:0;">
                                            <i class="bx bx-hash" style="font-size:1.7rem;color:#7c3aed;display:block;"></i>
                                        </div>
                                        <div>
                                            <div id="pcModalTitle" style="font-size:1.12rem;font-weight:800;color:#7c3aed;letter-spacing:.2px;line-height:1.2;">Prefix Configuration</div>
                                            <div id="pcModalSubtitle" style="font-size:.77rem;color:#6c757d;margin-top:4px;">Configure how your transaction numbers are generated</div>
                                        </div>
                                    </div>
                                    <div style="display:flex;align-items:center;gap:8px;flex-shrink:0;">
                                        <button type="button" data-bs-dismiss="modal" aria-label="Close"
                                            style="background:rgba(255,255,255,.85);border:none;border-radius:50%;width:28px;height:28px;display:inline-flex;align-items:center;justify-content:center;cursor:pointer;box-shadow:0 1px 4px rgba(0,0,0,.15);padding:0;">
                                            <i class="bx bx-x" style="font-size:1.2rem;color:#555;line-height:1;"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="modal-body" style="max-height:78vh;overflow-y:auto;">

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

                            <!-- ── Step 1: Prefix Name ───────────────────── -->
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

                            <!-- ── Step 2: Financial Year ────────────────── -->
                            <div class="mb-4">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <label class="form-label fw-semibold mb-0">Add financial year to the number?</label>
                                        <div class="form-text">
                                            e.g. INV<span class="pcSepHint">-</span><?php echo $_fyShort; ?><span class="pcSepHint">-</span>001
                                        </div>
                                    </div>
                                    <div class="form-check form-switch ms-3 mb-0">
                                        <input class="form-check-input" type="checkbox" id="pcIncludeFiscalYear" role="switch" value="1">
                                    </div>
                                </div>
                                <div id="pcFiscalYearOptions" class="mt-2 ps-3 border-start border-2 border-primary d-none">
                                    <label class="form-label small fw-semibold text-muted">Year Format</label>
                                    <div class="d-flex flex-wrap gap-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="pcFiscalFormat" id="pcFyShort" value="SHORT" checked>
                                            <label class="form-check-label" for="pcFyShort">
                                                Short <code><?php echo $_fyShort; ?></code>
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="pcFiscalFormat" id="pcFyLong" value="LONG">
                                            <label class="form-check-label" for="pcFyLong">
                                                Full <code><?php echo $_fyLong; ?></code>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- ── Step 3: Company Short Name ────────────── -->
                            <div class="mb-4">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <label class="form-label fw-semibold mb-0">Add company short name to the number?</label>
                                        <div class="form-text">
                                            e.g. INV<span class="pcSepHint">-</span>RK<span class="pcSepHint">-</span>001
                                        </div>
                                    </div>
                                    <div class="form-check form-switch ms-3 mb-0">
                                        <input class="form-check-input" type="checkbox" id="pcIncludeShortName" role="switch" value="1">
                                    </div>
                                </div>
                                <div id="pcShortNameOptions" class="mt-2 ps-3 border-start border-2 border-warning d-none">
                                    <label for="pcShortName" class="form-label small fw-semibold text-muted">Company Short Name</label>
                                    <input type="text"
                                           class="form-control form-control-sm text-uppercase fw-bold"
                                           id="pcShortName"
                                           maxlength="10"
                                           placeholder="e.g. RK, BHARAT"
                                           oninput="this.value=this.value.replace(/[^A-Za-z0-9]/g,'').toUpperCase()" />
                                </div>
                            </div>

                            <!-- ── Step 4: Separator ─────────────────────── -->
                            <div class="mb-4">
                                <label class="form-label fw-semibold">Separator between components</label>
                                <div class="d-flex flex-wrap gap-2" id="pcSepBtnGroup">
                                    <button type="button" class="btn btn-outline-secondary pc-sep-btn active" data-sep="-">Hyphen &nbsp;<code>-</code></button>
                                    <button type="button" class="btn btn-outline-secondary pc-sep-btn"        data-sep="/">Slash &nbsp;<code>/</code></button>
                                    <button type="button" class="btn btn-outline-secondary pc-sep-btn"        data-sep="|">Pipe &nbsp;<code>|</code></button>
                                    <button type="button" class="btn btn-outline-secondary pc-sep-btn"        data-sep="_">Underscore &nbsp;<code>_</code></button>
                                    <button type="button" class="btn btn-outline-secondary pc-sep-btn"        data-sep=".">Dot &nbsp;<code>.</code></button>
                                </div>
                                <input type="hidden" id="pcSeparator" value="-">
                            </div>

                            <!-- ── Step 5: Number Padding ────────────────── -->
                            <div class="mb-4">
                                <label class="form-label fw-semibold">Number format</label>
                                <div class="d-flex flex-wrap gap-2" id="pcPadBtnGroup">
                                    <button type="button" class="btn btn-outline-secondary pc-pad-btn"        data-pad="1">1, 2, 3&hellip;</button>
                                    <button type="button" class="btn btn-outline-secondary pc-pad-btn active" data-pad="3">001, 002&hellip;</button>
                                    <button type="button" class="btn btn-outline-secondary pc-pad-btn"        data-pad="5">00001, 00002&hellip;</button>
                                </div>
                                <input type="hidden" id="pcNumberPadding" value="3">
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

                        <div class="modal-footer py-3" style="background:#f8f9fa;border-top:1px solid #dee2e6;">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary" id="savePrefixConfigBtn">
                                <i class="bx bx-save me-1"></i>Save Prefix
                            </button>
                        </div>

                    </div>
                </div>
            </div>
            <!-- / Add / Edit Prefix Modal -->

            <?php $this->load->view('common/footer_desc'); ?>

        </div><!-- /layout-page -->

    </div>
</div>

<?php $this->load->view('common/footer'); ?>

<style>
.prefix-preview-box {
    border-style: dashed !important;
}
</style>

<script>
var CsrfName  = '<?php echo $this->security->get_csrf_token_name(); ?>';
var CsrfToken = '<?php echo $this->security->get_csrf_hash(); ?>';

window.addEventListener('load', function () {
    'use strict';

    var prefixAllModules = <?php echo $PrefixModulesJson ?? '{}'; ?>;

    /* ── Utilities ──────────────────────────────────────────────── */
    function escHtml(s) {
        return String(s)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;')
            .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    /* Indian fiscal year (April 1 start) */
    function getFiscalYear(format) {
        var d     = new Date();
        var month = d.getMonth() + 1;
        var year  = d.getFullYear();
        var fy    = month >= 4 ? year : year - 1;
        if (format === 'LONG') return fy + '-' + (fy + 1);
        return String(fy % 100).padStart(2, '0') + '-' + String((fy + 1) % 100).padStart(2, '0');
    }

    function padNum(n, padding) {
        padding = parseInt(padding, 10);
        return padding > 1 ? String(n).padStart(padding, '0') : String(n);
    }

    /* ── Live Preview ───────────────────────────────────────────── */
    var BLOCK_COLORS = ['bg-primary', 'bg-warning text-dark', 'bg-info text-dark', 'bg-success'];

    function updateLivePreview() {
        var name      = ($('#pcPrefixName').val() || '').trim().toUpperCase();
        var sep       = $('#pcSeparator').val() || '-';
        var incFiscal = $('#pcIncludeFiscalYear').is(':checked');
        var fiscalFmt = $('input[name="pcFiscalFormat"]:checked').val() || 'SHORT';
        var incShort  = $('#pcIncludeShortName').is(':checked');
        var shortName = ($('#pcShortName').val() || '').trim().toUpperCase();
        var padding   = parseInt($('#pcNumberPadding').val(), 10) || 3;

        var parts = [];
        if (name)                   parts.push({ label: name,                      color: BLOCK_COLORS[0] });
        if (incShort && shortName)  parts.push({ label: shortName,                 color: BLOCK_COLORS[1] });
        if (incFiscal)              parts.push({ label: getFiscalYear(fiscalFmt),  color: BLOCK_COLORS[2] });
        parts.push({ label: padNum(1, padding), color: BLOCK_COLORS[3] });

        var html = '';
        $.each(parts, function (i, p) {
            html += '<span class="badge ' + p.color + ' px-3 py-2" style="font-size:.85rem;letter-spacing:.04em">'
                  + escHtml(p.label) + '</span>';
            if (i < parts.length - 1) {
                html += '<span class="fw-bold text-muted mx-1" style="font-size:1.1rem">' + escHtml(sep) + '</span>';
            }
        });
        $('#pcPreviewBlocks').html(html);
        $('#pcFullPreview').text(parts.map(function (p) { return p.label; }).join(sep) || '—');
        $('.pcSepHint').text(sep);
    }

    /* ── Load list ──────────────────────────────────────────────── */
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
                if (resp.Error) {
                    showToastNotification(resp.Message, 'error');
                    return;
                }
                prefixAllModules = resp.Modules || {};
                $('#PrefixConfigBody').html(resp.RecordHtmlData);
                initTooltips();
            },
            error: function () {
                ajaxLoading(1);
                showToastNotification('Failed to load prefix configurations.', 'error');
            }
        });
    }

    function initTooltips() {
        $('[data-bs-toggle="tooltip"]').each(function () {
            var tt = bootstrap.Tooltip.getInstance(this);
            if (tt) tt.dispose();
            new bootstrap.Tooltip(this);
        });
    }

    /* ── Reset modal to blank state ─────────────────────────────── */
    function resetModal() {
        $('#pcPrefixUID').val('0');
        $('#pcModuleUID').val('');
        $('#pcPrefixName').val('');
        $('#pcIncludeFiscalYear').prop('checked', false);
        $('#pcFiscalYearOptions').addClass('d-none');
        $('input[name="pcFiscalFormat"][value="SHORT"]').prop('checked', true);
        $('#pcIncludeShortName').prop('checked', false);
        $('#pcShortNameOptions').addClass('d-none');
        $('#pcShortName').val('');
        $('#pcSepBtnGroup .pc-sep-btn').removeClass('active');
        $('#pcSepBtnGroup .pc-sep-btn[data-sep="-"]').addClass('active');
        $('#pcSeparator').val('-');
        $('#pcPadBtnGroup .pc-pad-btn').removeClass('active');
        $('#pcPadBtnGroup .pc-pad-btn[data-pad="3"]').addClass('active');
        $('#pcNumberPadding').val('3');
        $('#pcModuleRow').show();
        $('#pcModalSubtitle').text('Configure how your transaction numbers are generated');
        updateLivePreview();
    }

    /* ── Open Add ────────────────────────────────────────────────── */
    $('#btnAddPrefixConfig').on('click', function () {
        resetModal();
        $('#pcModalTitle').html('Add Prefix Configuration');

        // Populate module dropdown from cached module map
        var $sel = $('#pcModuleUID').empty().append('<option value="">— Select Module —</option>');
        $.each(prefixAllModules, function (uid, name) {
            $sel.append($('<option></option>').val(uid).text(name));
        });

        $('#prefixConfigModal').modal('show');
    });

    /* ── Open Edit ───────────────────────────────────────────────── */
    $(document).on('click', '.EditPrefixConfig', function () {
        var cfg = $(this).data('config');
        if (!cfg) return;

        resetModal();
        $('#pcPrefixUID').val(cfg.PrefixUID);
        $('#pcModalTitle').html('<i class="bx bx-edit me-2" style="color:#7c3aed;"></i>Edit Prefix — ' + escHtml(cfg.Name));
        $('#pcModalSubtitle').text('Module: ' + (cfg.ModuleName || 'Not assigned'));

        // Module row hidden in edit mode (module cannot change once created)
        $('#pcModuleRow').hide();

        // Populate fields
        $('#pcPrefixName').val(cfg.Name || '');

        if (parseInt(cfg.IncludeFiscalYear, 10) === 1) {
            $('#pcIncludeFiscalYear').prop('checked', true);
            $('#pcFiscalYearOptions').removeClass('d-none');
            $('input[name="pcFiscalFormat"][value="' + (cfg.FiscalYearFormat || 'SHORT') + '"]').prop('checked', true);
        }
        if (parseInt(cfg.IncludeShortName, 10) === 1) {
            $('#pcIncludeShortName').prop('checked', true);
            $('#pcShortNameOptions').removeClass('d-none');
            $('#pcShortName').val(cfg.ShortName || '');
        }

        var sep = cfg.Separator || '-';
        $('#pcSepBtnGroup .pc-sep-btn').removeClass('active');
        $('#pcSepBtnGroup .pc-sep-btn[data-sep="' + sep + '"]').addClass('active');
        $('#pcSeparator').val(sep);

        var pad = String(cfg.NumberPadding || 3);
        $('#pcPadBtnGroup .pc-pad-btn').removeClass('active');
        $('#pcPadBtnGroup .pc-pad-btn[data-pad="' + pad + '"]').addClass('active');
        $('#pcNumberPadding').val(pad);

        updateLivePreview();
        $('#prefixConfigModal').modal('show');
    });

    /* ── Delete ──────────────────────────────────────────────────── */
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
                    if (resp.Error) {
                        Swal.fire({ icon: 'error', title: 'Error', text: resp.Message });
                    } else {
                        loadPrefixConfigList();
                        showToastNotification(resp.Message, 'success');
                    }
                },
                error: function () { showToastNotification('Server error. Please try again.', 'error'); }
            });
        });
    });

    /* ── Set Default ─────────────────────────────────────────────── */
    $(document).on('click', '.SetDefaultPrefixConfig', function () {
        var uid = $(this).data('uid');
        $.ajax({
            url   : '/settings/setDefaultPrefixConfig',
            method: 'POST',
            data  : { prePrefixUID: uid, [CsrfName]: CsrfToken },
            success: function (resp) {
                CsrfToken = resp.NewCsrfToken || CsrfToken;
                if (resp.Error) {
                    showToastNotification(resp.Message, 'error');
                } else {
                    loadPrefixConfigList();
                    showToastNotification(resp.Message, 'success');
                }
            },
            error: function () { showToastNotification('Server error. Please try again.', 'error'); }
        });
    });

    /* ── Separator toggle ────────────────────────────────────────── */
    $('#pcSepBtnGroup').on('click', '.pc-sep-btn', function () {
        $('#pcSepBtnGroup .pc-sep-btn').removeClass('active');
        $(this).addClass('active');
        $('#pcSeparator').val($(this).data('sep'));
        updateLivePreview();
    });

    /* ── Padding toggle ──────────────────────────────────────────── */
    $('#pcPadBtnGroup').on('click', '.pc-pad-btn', function () {
        $('#pcPadBtnGroup .pc-pad-btn').removeClass('active');
        $(this).addClass('active');
        $('#pcNumberPadding').val($(this).data('pad'));
        updateLivePreview();
    });

    /* ── Fiscal year toggle ──────────────────────────────────────── */
    $('#pcIncludeFiscalYear').on('change', function () {
        $('#pcFiscalYearOptions').toggleClass('d-none', !$(this).is(':checked'));
        updateLivePreview();
    });

    /* ── Short name toggle ───────────────────────────────────────── */
    $('#pcIncludeShortName').on('change', function () {
        $('#pcShortNameOptions').toggleClass('d-none', !$(this).is(':checked'));
        updateLivePreview();
    });

    /* ── Live preview on any relevant input ──────────────────────── */
    $('#pcPrefixName, #pcShortName').on('input', updateLivePreview);
    $('input[name="pcFiscalFormat"]').on('change', updateLivePreview);

    /* ── Save ────────────────────────────────────────────────────── */
    $('#savePrefixConfigBtn').on('click', function () {
        var $btn      = $(this);
        var uid       = parseInt($('#pcPrefixUID').val(), 10) || 0;
        var moduleUID = parseInt($('#pcModuleUID').val(), 10) || 0;

        // Client-side validation
        if (!uid && !moduleUID) {
            showToastNotification('Please select a module.', 'error');
            $('#pcModuleUID').focus();
            return;
        }
        var prefixName = ($('#pcPrefixName').val() || '').trim().toUpperCase();
        if (!prefixName || prefixName.length < 2 || prefixName.length > 7) {
            showToastNotification('Prefix name must be 2 – 7 characters.', 'error');
            $('#pcPrefixName').focus();
            return;
        }
        if (!/^[A-Z0-9]+$/.test(prefixName)) {
            showToastNotification('Prefix name must be alphanumeric only (A-Z, 0-9).', 'error');
            $('#pcPrefixName').focus();
            return;
        }
        var incShort  = $('#pcIncludeShortName').is(':checked');
        var shortName = ($('#pcShortName').val() || '').trim();
        if (incShort && !shortName) {
            showToastNotification('Company short name is required when the option is enabled.', 'error');
            $('#pcShortName').focus();
            return;
        }

        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Saving...');

        $.ajax({
            url   : '/settings/savePrefixConfig',
            method: 'POST',
            data  : {
                prePrefixUID      : uid,
                preModuleUID      : moduleUID,
                transPrefixName   : prefixName,
                includeFiscalYear : $('#pcIncludeFiscalYear').is(':checked') ? '1' : '',
                fiscalYearFormat  : $('input[name="pcFiscalFormat"]:checked').val() || 'SHORT',
                includeShortName  : incShort ? '1' : '',
                companyShortName  : shortName,
                prefixSeparator   : $('#pcSeparator').val() || '-',
                numberPadding     : $('#pcNumberPadding').val() || '3',
                [CsrfName]        : CsrfToken,
            },
            success: function (resp) {
                CsrfToken = resp.NewCsrfToken || CsrfToken;
                $btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i>Save Prefix');
                if (resp.Error) {
                    showToastNotification(resp.Message, 'error');
                    return;
                }
                $('#prefixConfigModal').modal('hide');
                prefixAllModules = resp.Modules || prefixAllModules;
                $('#PrefixConfigBody').html(resp.RecordHtmlData);
                initTooltips();
                showToastNotification(resp.Message, 'success');
            },
            error: function () {
                $btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i>Save Prefix');
                showToastNotification('Server error. Please try again.', 'error');
            }
        });
    });

    /* ── Reset modal on close ────────────────────────────────────── */
    $('#prefixConfigModal').on('hidden.bs.modal', resetModal);

    // List pre-rendered server-side — no initial AJAX needed.

});
</script>
