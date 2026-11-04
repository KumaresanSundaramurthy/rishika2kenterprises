<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php $this->load->view('common/header'); ?>

<div class="layout-wrapper layout-horizontal layout-content-navbar">
    <div class="layout-container">

        <?php $this->load->view('common/menu_view'); ?>

        <div class="layout-page">
            <div class="content-wrapper apex-content">
                <?php $this->load->view('common/apex/page_header', [
                    'pageTitle'       => $PageTitle       ?? 'Message Templates',
                    'pageDescription' => $PageDescription ?? '',
                ]); ?>
                <div class="container-xxl flex-grow-1">

                    <div class="card mb-4">

                        <div class="px-3 py-2 border-bottom d-flex align-items-center">
                            <div class="ms-auto">
                                <button class="btn btn-primary btn-sm" id="btnAddMsgTemplate">
                                    <i class="bx bx-plus me-1"></i><?php echo t('btn_add_template', 'Add Template'); ?>
                                </button>
                            </div>
                        </div>

                        <!-- Table -->
                        <div class="table-responsive">
                            <table class="table trans-table MainviewTable mb-0" id="MsgTemplateTable">
                                <thead class="r2k-thead">
                                    <tr>
                                        <th style="width:130px;"><?php echo t('col_channel', 'Channel'); ?></th>
                                        <th><?php echo t('col_template_preview', 'Template Preview'); ?></th>
                                        <th style="width:130px;"><?php echo t('col_last_updated', 'Last Updated'); ?></th>
                                        <th class="text-center" style="width:90px;"><?php echo t('col_actions', 'Actions'); ?></th>
                                    </tr>
                                </thead>
                                <tbody id="MsgTemplateBody" class="r2k-tbody table-border-bottom-0">
                                    <tr><td colspan="4" class="text-center py-4"><span class="spinner-border spinner-border-sm me-2"></span>Loading...</td></tr>
                                </tbody>
                            </table>
                        </div>

                    </div>

                </div>
            </div>
            <!-- / Content wrapper -->

            <!-- ============================================================
                 Message Template Modal
            ============================================================ -->
            <div class="modal fade" id="msgTemplateModal" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-xl modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header py-3">
                            <h5 class="modal-title" id="msgTemplateModalTitle"><i class="bx bx-message-square-edit me-2 text-primary"></i>Message Template</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <hr class="my-0">
                        <div class="modal-body p-0">
                            <div class="d-flex" style="min-height:520px;">

                                <!-- Left: Form -->
                                <div class="p-4" style="flex:0 0 50%;border-right:1px solid var(--bs-border-color);overflow-y:auto;">
                                    <input type="hidden" id="msgTemplateUID" value="0">

                                    <div class="row g-3">
                                        <div class="col-12">
                                            <label class="form-label fw-semibold">Transaction Type <span class="text-danger">*</span></label>
                                            <select class="form-select" id="msgModuleUID">
                                                <option value="">&mdash; Select &mdash;</option>
                                            </select>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label fw-semibold">Channel <span class="text-danger">*</span></label>
                                            <select class="form-select" id="msgChannel">
                                                <option value="WhatsApp">WhatsApp</option>
                                                <option value="SMS">SMS</option>
                                                <option value="Email">Email</option>
                                            </select>
                                        </div>
                                        <div class="col-12 d-none" id="msgSubjectWrap">
                                            <label class="form-label fw-semibold">Email Subject</label>
                                            <input type="text" class="form-control" id="msgSubject" placeholder="e.g. Payment Receipt - {{DOC_NUMBER}}" maxlength="255">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label fw-semibold">Template Body <span class="text-danger">*</span></label>
                                            <div id="msgBodyEditorWrap">
                                                <div id="msgBodyEditor" style="min-height:200px;font-size:var(--r2k-text-85);"></div>
                                                <input type="hidden" id="msgBody">
                                            </div>
                                            <div id="msgBodyTextareaWrap" class="d-none">
                                                <textarea class="form-control font-monospace" id="msgBodyPlain" rows="10"
                                                    placeholder="Type your message here. Click a token below to insert it."
                                                    style="font-size:var(--r2k-text-82);resize:vertical;"></textarea>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label fw-semibold" style="font-size:var(--r2k-text-78);">Available Tokens <span class="text-muted">(click to insert at cursor)</span></label>
                                            <div id="msgTokenList" class="d-flex flex-wrap gap-1"></div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Right: Live Preview -->
                                <div class="p-4" style="flex:0 0 50%;background:var(--bs-tertiary-bg,#f8f9fa);overflow-y:auto;">
                                    <div class="fw-semibold mb-3" style="font-size:var(--r2k-text-82);color:#888;text-transform:uppercase;letter-spacing:.4px;">
                                        <i class="bx bx-show me-1"></i>Live Preview
                                    </div>
                                    <div id="msgPreviewBox"
                                         style="background:#fff;border:1px solid var(--bs-border-color);border-radius:8px;padding:16px;min-height:200px;font-size:var(--r2k-text-85);line-height:1.6;">
                                        <span class="text-muted fst-italic">Preview will appear here as you type...</span>
                                    </div>
                                    <div class="mt-3 p-3" style="background:#fffde7;border-radius:6px;font-size:.76rem;color:#666;">
                                        <strong>Note:</strong> <code>*bold*</code> formatting works in WhatsApp. Tokens shown with sample data in preview.
                                    </div>
                                </div>

                            </div>
                        </div>
                        <div class="modal-footer py-3">
                            <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal"><?php echo t('btn_cancel', 'Cancel'); ?></button>
                            <button type="button" class="btn btn-primary" id="btnSaveMsgTemplate">
                                <i class="bx bx-save me-1"></i><?php echo t('btn_save', 'Save'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <!-- / Message Template Modal -->

            <?php $this->load->view('common/footer_desc'); ?>

        </div>

    </div>
</div>

<?php $this->load->view('common/footer'); ?>

<link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>

<style>
#msgBodyEditor { border: 1px solid var(--bs-border-color); border-top: none; border-radius: 0 0 6px 6px; background:#fff; }
.ql-toolbar { border: 1px solid var(--bs-border-color) !important; border-radius: 6px 6px 0 0 !important; }
.ql-container { font-size:var(--r2k-text-85) !important; }
</style>

<script>
var CsrfName  = '<?php echo $this->security->get_csrf_token_name(); ?>';
var CsrfToken = '<?php echo $this->security->get_csrf_hash(); ?>';

window.addEventListener('load', function() {
    'use strict';

    var msgTplLoaded     = false;
    var msgTokens        = {};
    var msgModules       = {};
    var quillEditor      = null;
    var _pendingEditBody = null;

    $('#msgTemplateModal').on('shown.bs.modal', function() {
        if (!quillEditor) {
            quillEditor = new Quill('#msgBodyEditor', {
                theme: 'snow',
                modules: {
                    toolbar: [
                        ['bold','italic','underline','strike'],
                        [{ 'color': [] }, { 'background': [] }],
                        [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                        [{ 'align': [] }],
                        ['link'],
                        ['clean']
                    ]
                }
            });
            quillEditor.on('text-change', function() {
                $('#msgBody').val(quillEditor.root.innerHTML);
                updateMsgPreview();
            });
        }
        if (_pendingEditBody !== null) {
            quillEditor.root.innerHTML = _pendingEditBody;
            $('#msgBody').val(_pendingEditBody);
            _pendingEditBody = null;
            updateMsgPreview();
        }
    });

    function getMsgBody() {
        return $('#msgChannel').val() === 'Email' ? (quillEditor ? quillEditor.root.innerHTML : '') : $('#msgBodyPlain').val();
    }

    function setMsgBody(ch, val) {
        if (ch === 'Email') {
            if (quillEditor) {
                quillEditor.root.innerHTML = val || '';
                $('#msgBody').val(val || '');
            } else {
                _pendingEditBody = val || '';
            }
        } else {
            $('#msgBodyPlain').val(val || '');
        }
    }

    function switchBodyEditor(ch) {
        if (ch === 'Email') {
            $('#msgBodyEditorWrap').removeClass('d-none');
            $('#msgBodyTextareaWrap').addClass('d-none');
        } else {
            $('#msgBodyEditorWrap').addClass('d-none');
            $('#msgBodyTextareaWrap').removeClass('d-none');
        }
    }

    /**
     * @param {string} str
     * @returns {string}
     */
    function _esc(str) { return $('<s>').text(String(str)).html(); }

    /**
     * Wraps {{TOKEN}} placeholders in a highlight span.
     * @param {string} text
     * @returns {string}
     */
    function _mtHighlightTokens(text) {
        return text.replace(/\{\{([A-Za-z_]+)\}\}/g, function(match) {
            return '<span class="mt-tpl-token">' + match + '</span>';
        });
    }

    /**
     * @param {Array}  templates
     * @param {Object} modules    - { moduleUID: moduleName }
     * @returns {string}
     */
    function _renderMsgTemplateRows(templates, modules) {
        var channels = [
            { key: 'Email',    icon: 'bx-envelope',     cls: 'mt-tpl-ch-email',    label: 'Email' },
            { key: 'WhatsApp', icon: 'bxl-whatsapp',    cls: 'mt-tpl-ch-whatsapp', label: 'WhatsApp' },
            { key: 'SMS',      icon: 'bx-message-dots', cls: 'mt-tpl-ch-sms',      label: 'SMS' },
        ];

        if (!templates || !templates.length) {
            return '<tr><td colspan="4">' +
                '<div class="mt-tpl-empty-wrap">' +
                '<div class="mt-tpl-empty-icon"><i class="bx bx-message-square-edit"></i></div>' +
                '<div class="mt-tpl-empty-title">No templates configured yet</div>' +
                '<div class="mt-tpl-empty-sub">Click <strong>+ Add Template</strong> to create your first message template.</div>' +
                '</div></td></tr>';
        }

        /* Group by ModuleUID */
        var grouped = {};
        $.each(templates, function(i, row) {
            var uid = String(row.ModuleUID);
            if (!grouped[uid]) grouped[uid] = {};
            grouped[uid][row.Channel] = row;
        });

        var html = '';
        $.each(grouped, function(moduleUID, channelRows) {
            var moduleName   = (modules && modules[moduleUID]) ? modules[moduleUID] : ('Module ' + moduleUID);
            var configuredCt = 0;
            $.each(channels, function(i, ch) { if (channelRows[ch.key]) configuredCt++; });

            /* Module header */
            html += '<tr class="mt-tpl-module-row"><td colspan="4">' +
                '<div class="mt-tpl-module-hdr">' +
                '<div class="mt-tpl-module-icon-wrap"><i class="bx bx-transfer-alt"></i></div>' +
                '<div class="mt-tpl-module-name">' + _esc(moduleName) + '</div>' +
                '<span class="mt-tpl-module-badge">' + configuredCt + '/' + channels.length + ' configured</span>' +
                '</div></td></tr>';

            /* One row per channel */
            $.each(channels, function(i, ch) {
                var row      = channelRows[ch.key] || null;
                var rowClass = 'mt-tpl-ch-row' + (i === channels.length - 1 ? ' mt-tpl-last-ch' : '');

                var channelCell = '<td class="mt-tpl-ch-cell">' +
                    '<div class="mt-tpl-ch-badge ' + ch.cls + '">' +
                    '<i class="bx ' + ch.icon + '"></i>' + ch.label + '</div></td>';

                var previewCell;
                if (row) {
                    var prev = '';
                    if (ch.key === 'Email' && row.Subject) {
                        prev += '<div class="mt-tpl-subject">' + _esc(row.Subject) + '</div>';
                    }
                    var bodyText = row.Body || '';
                    if (ch.key === 'Email') {
                        bodyText = bodyText.replace(/<(br\s*\/?>|\/?(p|div|li|h[1-6])[^>]*)>/gi, '\n')
                                           .replace(/<[^>]+>/g, '');
                    }
                    bodyText = bodyText.trim();
                    if (bodyText.length > 130) {
                        var cut = bodyText.substring(0, 130);
                        if ((cut.match(/\{\{/g) || []).length > (cut.match(/\}\}/g) || []).length) {
                            cut = cut.substring(0, cut.lastIndexOf('{{'));
                        }
                        bodyText = cut.replace(/\s+$/, '') + '…';
                    }
                    prev += '<div class="mt-tpl-body-preview">' + _mtHighlightTokens(_esc(bodyText)) + '</div>';
                    previewCell = '<td class="mt-tpl-preview-cell">' + prev + '</td>';
                } else {
                    previewCell = '<td class="mt-tpl-preview-cell">' +
                        '<div class="mt-tpl-empty-state">' +
                        '<span class="mt-tpl-not-set"><i class="bx bx-minus-circle"></i>Not configured</span>' +
                        '<a href="javascript:void(0);" class="mt-tpl-add-btn AddMsgTemplate"' +
                        ' data-module-uid="' + parseInt(moduleUID, 10) + '"' +
                        ' data-module-name="' + _esc(moduleName) + '"' +
                        ' data-channel="' + ch.key + '">' +
                        '<i class="bx bx-plus"></i>Set up</a>' +
                        '</div></td>';
                }

                var dateCell = row && row.UpdatedOn
                    ? '<td class="mt-tpl-date-cell"><div class="mt-tpl-date">' + _esc(row.UpdatedOn) + '</div>' +
                      '<div class="mt-tpl-by">by ' + _esc(row.UpdatedByName || '—') + '</div></td>'
                    : '<td class="mt-tpl-date-cell"><span class="text-muted">—</span></td>';

                var actionsCell = row
                    ? '<td class="mt-tpl-action-cell"><div class="d-flex align-items-center justify-content-center gap-1">' +
                      '<a href="javascript:void(0);" class="btn btn-icon btn-sm text-warning EditMsgTemplate"' +
                      ' data-uid="' + parseInt(row.TemplateUID, 10) + '" title="Edit"><i class="bx bx-edit"></i></a>' +
                      '<a href="javascript:void(0);" class="btn btn-icon btn-sm text-danger DeleteMsgTemplate"' +
                      ' data-uid="' + parseInt(row.TemplateUID, 10) + '"' +
                      ' data-label="' + _esc(moduleName + ' — ' + ch.key) + '" title="Delete"><i class="bx bx-trash"></i></a>' +
                      '</div></td>'
                    : '<td class="mt-tpl-action-cell"></td>';

                html += '<tr class="' + rowClass + '">' + channelCell + previewCell + dateCell + actionsCell + '</tr>';
            });
        });

        return html;
    }

    function loadMsgTemplates() {
        $('#MsgTemplateBody').html('<tr><td colspan="4" class="text-center py-4"><span class="spinner-border spinner-border-sm me-2"></span>Loading...</td></tr>');
        ajaxLoading(0);
        $.ajax({
            url: global_services_url + 'api/settings/getMsgTemplateList', method: 'GET',
            headers: { Authorization: 'Bearer ' + JwtToken },
            data: { orgId: parseInt(JwtData.Org.OrgUID, 10), userId: parseInt(JwtData.User.UserUID, 10) },
            success: function(resp) {
                ajaxLoading(1);
                if (!resp.Error) {
                    msgTokens    = resp.Tokens  || {};
                    msgModules   = resp.Modules || {};
                    msgTplLoaded = true;
                    $('#MsgTemplateBody').html(_renderMsgTemplateRows(resp.Templates || resp.Data || [], msgModules));
                    buildModuleOptions();
                }
            }, error: function() { ajaxLoading(1); }
        });
    }

    function buildModuleOptions() {
        var html = '<option value="">— Select Transaction Type —</option>';
        $.each(msgModules, function(uid, name) {
            html += '<option value="' + uid + '">' + name + '</option>';
        });
        $('#msgModuleUID').html(html);
    }

    function buildTokenBadges(channel) {
        var html = '';
        $.each(msgTokens, function(token, desc) {
            html += '<span class="badge bg-label-secondary me-1 mb-1 msg-token-badge" '
                  + 'style="cursor:pointer;font-size:var(--r2k-text-sm);" '
                  + 'data-token="' + token + '" title="' + desc + '">' + token + '</span>';
        });
        $('#msgTokenList').html(html);
    }

    $(document).on('click', '.msg-token-badge', function() {
        var token = $(this).data('token');
        var ch    = $('#msgChannel').val();
        if (ch === 'Email' && quillEditor) {
            var range = quillEditor.getSelection(true);
            quillEditor.insertText(range ? range.index : quillEditor.getLength(), token);
            updateMsgPreview();
        } else {
            var ta    = document.getElementById('msgBodyPlain');
            var start = ta.selectionStart;
            var end   = ta.selectionEnd;
            ta.value  = ta.value.substring(0, start) + token + ta.value.substring(end);
            ta.selectionStart = ta.selectionEnd = start + token.length;
            ta.focus();
            updateMsgPreview();
        }
    });

    function updateMsgPreview() {
        var body    = getMsgBody();
        var channel = $('#msgChannel').val();
        var samples = {
            '{{PARTY_NAME}}'     : 'Venkatesh Paalapattu',
            '{{DOC_NUMBER}}'     : 'INV-0042',
            '{{DOC_DATE}}'       : '01 May 2026',
            '{{DOC_TYPE}}'       : 'Invoice',
            '{{AMOUNT}}'         : '₹ 1,200.00',
            '{{CURRENCY}}'       : '₹',
            '{{RECEIPT_NUMBER}}' : 'PREC-3259',
            '{{PAYMENT_MODE}}'   : 'UPI',
            '{{PAYMENT_STATUS}}' : 'Paid',
            '{{RECEIPT_LINK}}'   : 'https://yourdomain.com/receipt/aB3xK9mNpQ',
            '{{ORG_NAME}}'       : 'R2K Automobiles',
            '{{ORG_PHONE}}'      : '9789612478',
            '{{ORG_EMAIL}}'      : 'info@r2k.com',
            '{{ORG_ADDRESS}}'    : 'Chennai, Tamil Nadu',
            '{{ORG_GSTIN}}'      : '33AABCR1234F1Z5',
            '{{VALID_UNTIL}}'    : '15 May 2026',
            '{{BALANCE_AMOUNT}}' : '₹ 500.00',
        };
        var preview = body;
        $.each(samples, function(k, v) { preview = preview.split(k).join(v); });

        if (channel === 'Email') {
            var subj = $('#msgSubject').val();
            $.each(samples, function(k, v) { subj = subj.split(k).join(v); });
            $('#msgPreviewBox').html(
                '<div style="font-size:var(--r2k-text-78);color:#888;margin-bottom:8px;padding-bottom:8px;border-bottom:1px solid #eee;"><strong>Subject:</strong> ' + $('<span>').text(subj).html() + '</div>'
                + '<div style="font-size:var(--r2k-text-85);">' + preview + '</div>'
            );
        } else {
            $('#msgPreviewBox').html('<div style="white-space:pre-wrap;font-size:var(--r2k-text-85);">' + $('<span>').text(preview).html() + '</div>');
        }
    }

    $('#msgBodyPlain, #msgSubject').on('input', updateMsgPreview);

    $('#msgChannel').on('change', function() {
        var ch = $(this).val();
        $('#msgSubjectWrap').toggleClass('d-none', ch !== 'Email');
        switchBodyEditor(ch);
        buildTokenBadges(ch);
        updateMsgPreview();
    });

    $(document).on('click', '#btnAddMsgTemplate', function() {
        resetMsgForm();
        $('#msgTemplateModalTitle').text('Add Message Template');
        $('#msgTemplateModal').modal('show');
    });

    $(document).on('click', '.AddMsgTemplate', function() {
        resetMsgForm();
        $('#msgModuleUID').val($(this).data('module-uid'));
        var ch = $(this).data('channel');
        $('#msgChannel').val(ch).trigger('change');
        $('#msgTemplateModalTitle').text('Add Template — ' + $(this).data('module-name') + ' / ' + ch);
        $('#msgTemplateModal').modal('show');
    });

    $(document).on('click', '.EditMsgTemplate', function() {
        var uid = $(this).data('uid');
        resetMsgForm();
        $('#msgTemplateModalTitle').text('Edit Template');
        $('#msgModuleUID, #msgChannel, #msgSubject').prop('disabled', true);
        $('#msgBodyPlain').prop('disabled', true);
        $('#btnSaveMsgTemplate').prop('disabled', true);
        $('#msgTemplateModal').modal('show');
        ajaxLoading(0);
        $.ajax({
            url   : global_services_url + 'api/settings/getMsgTemplateDetail',
            method: 'GET',
            headers: { Authorization: 'Bearer ' + JwtToken },
            data  : { TemplateUID: uid, orgId: parseInt(JwtData.Org.OrgUID, 10), userId: parseInt(JwtData.User.UserUID, 10) },
            success: function(resp) {
                ajaxLoading(1);
                $('#msgModuleUID, #msgChannel, #msgSubject').prop('disabled', false);
                $('#msgBodyPlain').prop('disabled', false);
                $('#btnSaveMsgTemplate').prop('disabled', false);
                if (resp.Error) {
                    Swal.fire({ icon: 'error', text: resp.Message });
                    $('#msgTemplateModal').modal('hide');
                    return;
                }
                var d = resp.Data;
                $('#msgTemplateUID').val(d.TemplateUID);
                $('#msgModuleUID').val(d.ModuleUID);
                $('#msgChannel').val(d.Channel).trigger('change');
                $('#msgSubject').val(d.Subject || '');
                $('#msgTemplateModalTitle').text('Edit Template — ' + (d.ModuleName || '') + ' / ' + d.Channel);
                if (d.Channel === 'Email') {
                    setMsgBody('Email', d.Body || '');
                } else {
                    $('#msgBodyPlain').val(d.Body || '');
                }
                updateMsgPreview();
            },
            error: function() {
                ajaxLoading(1);
                $('#msgModuleUID, #msgChannel, #msgSubject').prop('disabled', false);
                $('#msgBodyPlain').prop('disabled', false);
                $('#btnSaveMsgTemplate').prop('disabled', false);
                Swal.fire({ icon: 'error', text: 'Failed to load template.' });
                $('#msgTemplateModal').modal('hide');
            }
        });
    });

    function resetMsgForm() {
        _pendingEditBody = null;
        $('#msgTemplateUID').val(0);
        $('#msgModuleUID').val('');
        $('#msgSubject').val('');
        $('#msgBodyPlain').val('');
        if (quillEditor) quillEditor.root.innerHTML = '';
        $('#msgBody').val('');
        $('#msgPreviewBox').html('<span class="text-muted fst-italic">Preview will appear here as you type...</span>');
        $('#msgChannel').val('WhatsApp').trigger('change');
    }

    $('#btnSaveMsgTemplate').on('click', function() {
        var body = getMsgBody();
        if (!body.trim() || body === '<p><br></p>') {
            showToastNotification('Template body is required.', 'error');
            return;
        }
        var $btn = $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Saving...');
        $.ajax({
            url: global_services_url + 'api/settings/saveMsgTemplate', method: 'POST',
            headers: { Authorization: 'Bearer ' + JwtToken },
            data: {
                TemplateUID : $('#msgTemplateUID').val(),
                ModuleUID   : $('#msgModuleUID').val(),
                Channel     : $('#msgChannel').val(),
                Subject     : $('#msgSubject').val(),
                Body        : body,
                orgId       : parseInt(JwtData.Org.OrgUID, 10),
                userId      : parseInt(JwtData.User.UserUID, 10),
            },
            success: function(resp) {
                $btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i>Save Template');
                if (!resp.Error) {
                    $('#msgTemplateModal').modal('hide');
                    showToastNotification(resp.Message, 'success');
                    loadMsgTemplates();
                } else {
                    showToastNotification(resp.Message, 'error');
                }
            },
            error: function() {
                $btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i>Save Template');
                showToastNotification('Server error.', 'error');
            }
        });
    });

    $(document).on('click', '.DeleteMsgTemplate', function() {
        var uid   = $(this).data('uid');
        var label = $(this).data('label');
        Swal.fire({
            title: 'Delete Template?',
            text : 'Delete "' + label + '" template?',
            icon : 'warning', showCancelButton: true,
            confirmButtonText: 'Delete', confirmButtonColor: '#d33'
        }).then(function(r) {
            if (!r.isConfirmed) return;
            ajaxLoading(0);
            $.ajax({
                url: global_services_url + 'api/settings/deleteMsgTemplate', method: 'POST',
                headers: { Authorization: 'Bearer ' + JwtToken },
                data: { TemplateUID: uid, orgId: parseInt(JwtData.Org.OrgUID, 10), userId: parseInt(JwtData.User.UserUID, 10) },
                success: function(resp) {
                    ajaxLoading(1);
                    if (!resp.Error) {
                        loadMsgTemplates();
                        Swal.fire({ icon:'success', text: resp.Message, timer:1500, showConfirmButton:false });
                    } else {
                        Swal.fire({ icon:'error', text: resp.Message });
                    }
                }, error: function() { ajaxLoading(1); }
            });
        });
    });

    loadMsgTemplates();

});
</script>
