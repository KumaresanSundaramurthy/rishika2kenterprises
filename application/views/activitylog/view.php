<?php defined('BASEPATH') or exit('No direct script access allowed');
$this->load->view('common/transactions/header'); ?>

<div class="layout-wrapper layout-horizontal layout-content-navbar">
    <div class="layout-container">

        <?php $this->load->view('common/menu_view'); ?>

        <div class="layout-page">
            <div class="content-wrapper apex-content">
                <?php $this->load->view('common/apex/page_header', [
                    'pageTitle'       => $PageTitle       ?? 'Activity Log',
                    'pageDescription' => $PageDescription ?? 'Full audit trail of all user actions',
                    'pageIcon'        => $PageIcon        ?? 'bx-history',
                    'pageIconBg'      => $PageIconBg      ?? '#ede9fe',
                    'pageIconColor'   => $PageIconColor   ?? '#7c3aed',
                ]); ?>

                <div class="container-xxl flex-grow-1 py-3">

                    <style>
                        .al-sticky-col {
                            position: sticky;
                            right: 0;
                            z-index: 2;
                            background: var(--bs-body-bg, #fff);
                            box-shadow: -2px 0 6px rgba(0,0,0,.06);
                            width: 56px;
                            text-align: center;
                        }
                        [data-bs-theme="dark"] .al-sticky-col,
                        :root[data-theme="dark"] .al-sticky-col { background: var(--bs-body-bg, #1e293b); }
                        thead .al-sticky-col { z-index: 3; }
                    </style>

                    <div class="card">

                        <!-- Filter Row -->
                        <div class="apex-filter-row">
                            <div class="r2k-search-wrap">
                                <i class="bx bx-search r2k-si"></i>
                                <input type="text" id="alSearch" placeholder="Search user, action, ref...">
                            </div>

                            <!-- Module filter -->
                            <?php if (!empty($Modules)): ?>
                            <select id="alModuleFilter" class="form-select form-select-sm" style="max-width:160px;">
                                <option value="">All Modules</option>
                                <?php foreach ($Modules as $mod): ?>
                                    <option value="<?php echo htmlspecialchars($mod); ?>"><?php echo htmlspecialchars($mod); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php endif; ?>

                            <!-- User filter -->
                            <?php if (!empty($OrgUsers) && count($OrgUsers) > 1): ?>
                            <select id="alUserFilter" class="form-select form-select-sm" style="max-width:160px;">
                                <option value="">All Users</option>
                                <?php foreach ($OrgUsers as $u): ?>
                                    <option value="<?php echo (int)$u->UserUID; ?>"><?php echo htmlspecialchars($u->UserName ?? ''); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php endif; ?>

                            <?php $this->load->view('common/transactions/date_filter_btn'); ?>

                            <div class="apex-filter-spacer"></div>
                            <?php $this->load->view('common/partials/export_btn'); ?>
                            <a href="javascript:void(0);" class="apex-icon-btn pageRefresh" title="Refresh"><i class="bx bx-refresh"></i></a>
                        </div>

                        <!-- Table -->
                        <div class="table-responsive">
                            <table class="table table-hover mb-0" id="alTable" style="min-width:1600px;">
                                <thead class="r2k-thead">
                                    <tr>
                                        <th style="width:120px;">Date / Time</th>
                                        <th style="width:180px;">User</th>
                                        <th style="width:130px;">Module</th>
                                        <th style="width:200px;">Action</th>
                                        <th style="width:160px;">Reference</th>
                                        <th style="min-width:200px;">Summary</th>
                                        <th style="width:130px;">Category</th>
                                        <th style="width:100px;">Result</th>
                                        <th style="width:100px;">Type</th>
                                        <th style="width:130px;">IP Address</th>
                                        <th style="width:100px;">Device</th>
                                        <th class="al-sticky-col"></th>
                                    </tr>
                                </thead>
                                <tbody class="r2k-tbody table-border-bottom-0" id="alTableBody">
                                    <?php echo $ModRowData; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="row mx-0 px-3 mt-1 justify-content-between align-items-center apex-pag-sticky" id="alPagination">
                            <?php echo $ModPagination ?: ''; ?>
                        </div>

                    </div><!-- /card -->

                </div>
            </div>
            <?php $this->load->view('common/footer_desc'); ?>
        </div>
    </div>
</div>

<!-- Details Modal -->
<div class="modal fade" id="alDetailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-white border-bottom d-flex align-items-center justify-content-between px-3 py-2">
                <div class="d-flex align-items-center gap-2">
                    <div class="modal-doc-icon" style="background:#ede9fe;">
                        <i class="bx bx-code-alt modal-doc-icon-inner" style="color:#7c3aed;"></i>
                    </div>
                    <h6 class="modal-title mb-0">Action Details</h6>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3" id="alDetailsBody">
                <pre style="font-size:.8rem;background:#f8fafc;border:1px solid #e2e8f0;border-radius:6px;padding:12px;overflow-x:auto;max-height:480px;"></pre>
            </div>
        </div>
    </div>
</div>

<!-- Entity History Panel (reusable — opened programmatically) -->
<div id="alHistoryPanel" style="display:none;position:fixed;z-index:1055;min-width:320px;max-width:420px;background:#fff;border:1px solid #e2e8f0;border-radius:10px;box-shadow:0 8px 32px rgba(0,0,0,.14);overflow:hidden;">
    <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 14px;border-bottom:1px solid #f1f5f9;background:#fafafa;">
        <div style="font-size:.83rem;font-weight:700;color:#1e293b;" id="alHistoryPanelTitle">History</div>
        <button type="button" id="alHistoryPanelClose" style="background:none;border:none;cursor:pointer;color:#94a3b8;font-size:1.1rem;padding:0;line-height:1;"><i class="bx bx-x"></i></button>
    </div>
    <div style="max-height:360px;overflow-y:auto;padding:10px 14px;" id="alHistoryPanelBody">
        <div class="text-center py-3"><span class="spinner-border spinner-border-sm text-primary"></span></div>
    </div>
</div>

<?php $this->load->view('common/transactions/footer'); ?>

<script>
(function () {
    'use strict';

    var Filter   = {};
    var PageNo   = 1;
    var RowLimit = <?php echo (int)($JwtData->GenSettings->RowLimit ?? 25); ?>;

    // Seed with this month (matching PHP default)
    var _now            = new Date();
    var _monthStart     = _now.getFullYear() + '-' + String(_now.getMonth() + 1).padStart(2, '0') + '-01';
    var _today          = _now.toISOString().slice(0, 10);
    Filter.DateFrom = _monthStart;
    Filter.DateTo   = _today;

    // ── Fetch list ───────────────────────────────────────────────────────────

    function getActivityLog() {
        ajaxLoading(0);
        $.ajax({
            url: '/settings/activitylog/getPageDetails', method: 'POST',
            data: { RowLimit: RowLimit, PageNo: PageNo, Filter: JSON.stringify(Filter), [CsrfName]: CsrfToken },
            success: function (resp) {
                ajaxLoading(1);
                if (resp.Error) { showToastNotification(resp.Message || 'Failed to load.', 'error'); return; }
                $('#alTableBody').html(resp.RecordHtmlData);
                $('#alPagination').html(resp.Pagination || '');
                initTooltips();
            },
            error: function () {
                ajaxLoading(1);
                showToastNotification('Failed to load activity log.', 'error');
            }
        });
    }

    // ── Date filter (shared date_filter_btn component) ───────────────────────

    $(document).on('click', '.date-option', function () {
        var range = $(this).data('range') || '';
        if (range === 'custom') return;
        var dates = getDateRange(range);
        Filter.DateFrom = dates.from;
        Filter.DateTo   = dates.to;
        var lbl = $.trim($(this).clone().children('.df-date-preview').remove().end().text());
        if (lbl) $('#dateFilterLabel').text(lbl);
        $('.date-option').removeClass('active');
        $(this).addClass('active');
        PageNo = 1; getActivityLog();
    });

    // ── Search ───────────────────────────────────────────────────────────────

    $('#alSearch').on('input', debounce(function () {
        var val = $.trim($(this).val());
        if (val) Filter.Search = val; else delete Filter.Search;
        PageNo = 1; getActivityLog();
    }, 1200));

    // ── Module filter ────────────────────────────────────────────────────────

    $('#alModuleFilter').on('change', function () {
        var val = $(this).val();
        if (val) Filter.Module = val; else delete Filter.Module;
        PageNo = 1; getActivityLog();
    });

    // ── User filter ──────────────────────────────────────────────────────────

    $('#alUserFilter').on('change', function () {
        var val = $(this).val();
        if (val) Filter.UserUID = val; else delete Filter.UserUID;
        PageNo = 1; getActivityLog();
    });

    // ── Refresh ──────────────────────────────────────────────────────────────

    $(document).on('click', '.pageRefresh', function (e) {
        e.preventDefault(); PageNo = 1; getActivityLog();
    });

    // ── Pagination ───────────────────────────────────────────────────────────

    $(document).on('click', '#alPagination .page-link', function (e) {
        e.preventDefault();
        var match = ($(this).attr('href') || '').match(/\/(\d+)$/);
        if (match) { PageNo = parseInt(match[1]); getActivityLog(); }
    });

    // ── Details modal ────────────────────────────────────────────────────────

    $(document).on('click', '.alDetailsBtn', function () {
        var raw = $(this).data('details') || '';
        var pretty = '';
        try {
            var obj = typeof raw === 'string' ? JSON.parse(raw) : raw;
            pretty = JSON.stringify(obj, null, 2);
        } catch (e) {
            pretty = raw;
        }
        $('#alDetailsBody pre').text(pretty);
        new bootstrap.Modal(document.getElementById('alDetailsModal')).show();
    });

    // ── Entity history panel (for use from other pages via window.alOpenHistory) ──

    var _historyPanel     = document.getElementById('alHistoryPanel');
    var _historyPanelOpen = null;

    window.alOpenHistory = function (entityType, entityUID, label, triggerEl) {
        if (_historyPanelOpen && _historyPanelOpen[0] === entityType && _historyPanelOpen[1] === entityUID && $(_historyPanel).is(':visible')) {
            $(_historyPanel).hide(); _historyPanelOpen = null; return;
        }
        _historyPanelOpen = [entityType, entityUID];
        $('#alHistoryPanelTitle').text((label || entityType) + ' History');
        $('#alHistoryPanelBody').html('<div class="text-center py-3"><span class="spinner-border spinner-border-sm text-primary"></span></div>');

        var $panel = $(_historyPanel);
        if (triggerEl) {
            var rect   = triggerEl.getBoundingClientRect();
            var pw     = 380;
            var left   = rect.left;
            var top    = rect.bottom + 6;
            if (left + pw + 16 > window.innerWidth) left = window.innerWidth - pw - 16;
            $panel.css({ top: top + window.scrollY, left: left });
        }
        $panel.show();

        $.ajax({
            url: '/activitylog/getEntityHistory', method: 'POST',
            data: { EntityType: entityType, EntityUID: entityUID, [CsrfName]: CsrfToken },
            success: function (resp) {
                if (resp.Error || !resp.History || !resp.History.length) {
                    $('#alHistoryPanelBody').html('<p class="text-muted mb-0" style="font-size:.8rem;">No history found.</p>');
                    return;
                }
                var html = '';
                resp.History.forEach(function (h, i) {
                    if (i > 0) html += '<hr style="margin:8px 0;border-color:#f1f5f9;">';
                    var ts = h.CreatedOn ? new Date(h.CreatedOn.replace(' ', 'T')) : null;
                    var dateStr = ts ? ts.toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' }) + ' ' + ts.toLocaleTimeString('en-IN', { hour: '2-digit', minute: '2-digit' }) : '';
                    var actionLower = (h.Action || '').toLowerCase();
                    var dotClr = '#7c3aed';
                    if (actionLower.indexOf('delete') !== -1 || actionLower.indexOf('cancel') !== -1) dotClr = '#dc2626';
                    else if (actionLower.indexOf('create') !== -1 || actionLower.indexOf('add') !== -1) dotClr = '#16a34a';
                    else if (actionLower.indexOf('update') !== -1 || actionLower.indexOf('save') !== -1) dotClr = '#2563eb';

                    html += '<div class="d-flex gap-2">';
                    html += '<div style="margin-top:3px;flex-shrink:0;width:8px;height:8px;border-radius:50%;background:' + dotClr + ';"></div>';
                    html += '<div style="min-width:0;flex:1;">';
                    html += '<div style="font-size:.78rem;font-weight:600;color:#374151;">' + $('<span>').text(h.Action || '').html() + '</div>';
                    if (h.Summary) html += '<div style="font-size:.74rem;color:#64748b;margin-top:1px;">' + $('<span>').text(h.Summary).html() + '</div>';
                    html += '<div style="font-size:.7rem;color:#94a3b8;margin-top:2px;">';
                    if (h.UserName) html += $('<span>').text(h.UserName).html() + ' &middot; ';
                    if (dateStr)    html += dateStr;
                    html += '</div>';
                    html += '</div></div>';
                });
                $('#alHistoryPanelBody').html(html);
            },
            error: function () {
                $('#alHistoryPanelBody').html('<p class="text-danger mb-0" style="font-size:.8rem;">Failed to load history.</p>');
            }
        });
    };

    $('#alHistoryPanelClose').on('click', function () { $(_historyPanel).hide(); _historyPanelOpen = null; });
    $(document).on('click', function (e) {
        if ($(_historyPanel).is(':visible') && !$(e.target).closest('#alHistoryPanel, .alHistoryBtn').length) {
            $(_historyPanel).hide(); _historyPanelOpen = null;
        }
    });
    $(document).on('keydown', function (e) { if (e.key === 'Escape') { $(_historyPanel).hide(); _historyPanelOpen = null; } });

    // ── Export ───────────────────────────────────────────────────────────────
    initExport({
        moduleUID: 700,
        getFilters: function () { return Filter; }
    });

})();
</script>
