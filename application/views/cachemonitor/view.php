<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php $this->load->view('common/header'); ?>

<div class="layout-wrapper layout-horizontal layout-content-navbar">
    <div class="layout-container">

        <?php $this->load->view('common/menu_view'); ?>

        <div class="layout-page">
            <div class="content-wrapper">
                <div class="container-xxl flex-grow-1 container-p-y">

                    <!-- Page Header -->
                    <div class="trans-page-header mb-4">
                        <div class="d-flex align-items-center gap-3">
                            <div class="trans-ph-icon" style="background:linear-gradient(135deg,#6366f1,#4f46e5);">
                                <i class="bx bx-data" style="color:#fff;"></i>
                            </div>
                            <div>
                                <h5 class="trans-ph-title mb-0">Cache Monitor</h5>
                                <div class="text-muted" style="font-size:.76rem;">Redis &amp; Upstash cache inspector — developer access only</div>
                            </div>
                        </div>
                    </div>

                    <!-- ── Password Gate (skipped in development environment) ── -->
                    <div id="cm-password-gate" <?php if ($IsDevEnv): ?>class="d-none"<?php endif; ?>>
                        <div class="row justify-content-center">
                            <div class="col-md-5 col-lg-4">
                                <div class="card shadow-sm border-0">
                                    <div class="card-body p-4">
                                        <div class="text-center mb-4">
                                            <div style="width:56px;height:56px;border-radius:50%;background:#eef2ff;display:inline-flex;align-items:center;justify-content:center;margin-bottom:12px;">
                                                <i class="bx bx-lock-alt" style="font-size:26px;color:#6366f1;"></i>
                                            </div>
                                            <h6 class="fw-semibold mb-1">Developer Access Required</h6>
                                            <p class="text-muted mb-0" style="font-size:.82rem;">Enter the developer password to view cache details.</p>
                                        </div>
                                        <div id="cm-pw-error" class="alert alert-danger py-2 d-none" style="font-size:.82rem;"></div>
                                        <div class="mb-3">
                                            <label class="form-label fw-medium" style="font-size:.84rem;">Password</label>
                                            <div class="input-group">
                                                <input type="password" id="cm-pw-input" class="form-control" placeholder="Enter developer password" autocomplete="off">
                                                <button class="btn btn-outline-secondary" type="button" id="cm-pw-toggle" tabindex="-1">
                                                    <i class="bx bx-show" id="cm-pw-eye"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <button class="btn btn-primary w-100 fw-medium" id="cm-pw-submit">
                                            <span id="cm-pw-btn-text">Unlock</span>
                                            <span id="cm-pw-spinner" class="spinner-border spinner-border-sm ms-2 d-none" role="status"></span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ── Cache Tabs ────────────────────────────────────── -->
                    <div id="cm-cache-panel" <?php if (!$IsDevEnv): ?>class="d-none"<?php endif; ?>>

                        <!-- Stats bar -->
                        <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                            <div class="d-flex gap-3">
                                <span class="badge bg-label-primary px-3 py-2" style="font-size:.8rem;">
                                    Redis: <strong id="cm-redis-count">—</strong> keys
                                </span>
                                <span class="badge bg-label-success px-3 py-2" style="font-size:.8rem;">
                                    Upstash: <strong id="cm-upstash-count">—</strong> keys
                                </span>
                            </div>
                            <button class="btn btn-sm btn-outline-secondary" id="cm-refresh-btn">
                                <i class="bx bx-refresh me-1"></i>Refresh
                            </button>
                        </div>

                        <!-- Tabs -->
                        <ul class="nav nav-tabs mb-0" id="cm-tabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active fw-medium" id="cm-redis-tab" data-bs-toggle="tab" data-bs-target="#cm-redis-pane" type="button" role="tab">
                                    <i class="bx bx-server me-1"></i>Redis Cache
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link fw-medium" id="cm-upstash-tab" data-bs-toggle="tab" data-bs-target="#cm-upstash-pane" type="button" role="tab">
                                    <i class="bx bx-cloud me-1"></i>Upstash Cache
                                </button>
                            </li>
                        </ul>

                        <div class="tab-content border border-top-0 rounded-bottom bg-white">

                            <!-- Redis Pane -->
                            <div class="tab-pane fade show active p-3" id="cm-redis-pane" role="tabpanel">
                                <div id="cm-redis-loading" class="text-center py-5">
                                    <div class="spinner-border text-primary" role="status"></div>
                                    <div class="mt-2 text-muted" style="font-size:.83rem;">Loading Redis keys…</div>
                                </div>
                                <div id="cm-redis-empty" class="text-center py-5 d-none">
                                    <i class="bx bx-inbox" style="font-size:2rem;color:#94a3b8;"></i>
                                    <div class="text-muted mt-2" style="font-size:.84rem;">No keys found in Redis.</div>
                                </div>
                                <div id="cm-redis-error" class="alert alert-danger d-none" style="font-size:.83rem;"></div>
                                <div id="cm-redis-table-wrap" class="d-none">
                                    <div class="mb-2">
                                        <input type="text" id="cm-redis-search" class="form-control form-control-sm" placeholder="Filter keys…" style="max-width:320px;">
                                    </div>
                                    <div class="table-responsive" style="max-height:70vh;overflow-y:auto;">
                                        <table class="table table-sm table-hover align-middle mb-0" style="font-size:.81rem;">
                                            <thead class="table-light sticky-top">
                                                <tr>
                                                    <th style="width:32px;">#</th>
                                                    <th style="min-width:220px;">Key</th>
                                                    <th style="width:80px;">Type</th>
                                                    <th style="width:90px;">TTL</th>
                                                    <th style="width:80px;">Size</th>
                                                    <th>Value</th>
                                                    <th style="width:70px;">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody id="cm-redis-tbody"></tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <!-- Upstash Pane -->
                            <div class="tab-pane fade p-3" id="cm-upstash-pane" role="tabpanel">
                                <div id="cm-upstash-loading" class="text-center py-5 d-none">
                                    <div class="spinner-border text-success" role="status"></div>
                                    <div class="mt-2 text-muted" style="font-size:.83rem;">Loading Upstash keys…</div>
                                </div>
                                <div id="cm-upstash-empty" class="text-center py-5 d-none">
                                    <i class="bx bx-inbox" style="font-size:2rem;color:#94a3b8;"></i>
                                    <div class="text-muted mt-2" style="font-size:.84rem;">No keys found in Upstash.</div>
                                </div>
                                <div id="cm-upstash-error" class="alert alert-danger d-none" style="font-size:.83rem;"></div>
                                <div id="cm-upstash-not-configured" class="alert alert-warning d-none" style="font-size:.83rem;">
                                    <i class="bx bx-info-circle me-1"></i>Upstash is not configured (UPSTASH_REDIS_REST_URL / TOKEN missing).
                                </div>
                                <div id="cm-upstash-table-wrap" class="d-none">
                                    <div class="mb-2">
                                        <input type="text" id="cm-upstash-search" class="form-control form-control-sm" placeholder="Filter keys…" style="max-width:320px;">
                                    </div>
                                    <div class="table-responsive" style="max-height:70vh;overflow-y:auto;">
                                        <table class="table table-sm table-hover align-middle mb-0" style="font-size:.81rem;">
                                            <thead class="table-light sticky-top">
                                                <tr>
                                                    <th style="width:32px;">#</th>
                                                    <th style="min-width:220px;">Key</th>
                                                    <th style="width:80px;">Type</th>
                                                    <th style="width:90px;">TTL</th>
                                                    <th style="width:80px;">Size</th>
                                                    <th>Value</th>
                                                    <th style="width:70px;">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody id="cm-upstash-tbody"></tbody>
                                        </table>
                                    </div>
                                </div>
                                <div id="cm-upstash-hint" class="text-center py-4 text-muted" style="font-size:.83rem;">
                                    <i class="bx bx-pointer me-1"></i>Click the Upstash tab to load data.
                                </div>
                            </div>

                        </div><!-- /tab-content -->
                    </div><!-- /cm-cache-panel -->

                </div>
            </div>
        </div>

    </div>
</div>

<?php $this->load->view('common/footer'); ?>

<style>
.cm-val-pre {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    padding: 6px 10px;
    font-size: .76rem;
    font-family: 'Courier New', monospace;
    white-space: pre-wrap;
    word-break: break-all;
    max-height: 160px;
    overflow-y: auto;
    margin: 0;
    color: #334155;
}
.cm-val-pre.expanded { max-height: none; }
.cm-expand-btn {
    font-size: .72rem;
    cursor: pointer;
    color: #6366f1;
    border: none;
    background: none;
    padding: 2px 0;
    display: block;
    margin-top: 3px;
}
.cm-expand-btn:hover { text-decoration: underline; }
.cm-badge-type {
    font-size: .7rem;
    padding: 2px 7px;
    border-radius: 20px;
    font-weight: 600;
    text-transform: uppercase;
}
.cm-ttl-badge {
    font-size: .73rem;
    white-space: nowrap;
}
.sticky-top { position: sticky; top: 0; z-index: 2; }
</style>

<script>
(function () {
    'use strict';

    const BASE = '<?php echo base_url(); ?>';
    const CSRF_NAME  = '<?php echo $this->security->get_csrf_token_name(); ?>';
    const CSRF_HASH  = '<?php echo $this->security->get_csrf_hash(); ?>';
    const IS_DEV_ENV = <?php echo $IsDevEnv ? 'true' : 'false'; ?>;

    let _redisData   = [];
    let _upstashData = [];
    let _upstashLoaded = false;

    // In development, skip the password gate and load immediately
    if (IS_DEV_ENV) {
        loadRedis();
    }

    // ── Password gate ─────────────────────────────────────────────────────────

    $('#cm-pw-toggle').on('click', function () {
        const inp = $('#cm-pw-input');
        const eye = $('#cm-pw-eye');
        if (inp.attr('type') === 'password') {
            inp.attr('type', 'text');
            eye.removeClass('bx-show').addClass('bx-hide');
        } else {
            inp.attr('type', 'password');
            eye.removeClass('bx-hide').addClass('bx-show');
        }
    });

    $('#cm-pw-input').on('keydown', function (e) {
        if (e.key === 'Enter') $('#cm-pw-submit').trigger('click');
    });

    $('#cm-pw-submit').on('click', function () {
        const pw = $('#cm-pw-input').val().trim();
        if (!pw) { showPwError('Please enter the password.'); return; }

        $('#cm-pw-btn-text').text('Verifying…');
        $('#cm-pw-spinner').removeClass('d-none');
        $('#cm-pw-submit').prop('disabled', true);
        $('#cm-pw-error').addClass('d-none');

        $.ajax({
            url: BASE + 'dev/cache/verifyPassword',
            method: 'POST',
            data: { password: pw, [CSRF_NAME]: CSRF_HASH },
            dataType: 'json',
            success: function (res) {
                if (!res.Error) {
                    $('#cm-password-gate').addClass('d-none');
                    $('#cm-cache-panel').removeClass('d-none');
                    loadRedis();
                } else {
                    showPwError(res.Message || 'Invalid password.');
                    resetPwBtn();
                }
            },
            error: function () {
                showPwError('Request failed. Please try again.');
                resetPwBtn();
            }
        });
    });

    function showPwError(msg) {
        $('#cm-pw-error').text(msg).removeClass('d-none');
    }
    function resetPwBtn() {
        $('#cm-pw-btn-text').text('Unlock');
        $('#cm-pw-spinner').addClass('d-none');
        $('#cm-pw-submit').prop('disabled', false);
    }

    // ── Load Redis ────────────────────────────────────────────────────────────

    function loadRedis() {
        $('#cm-redis-loading').removeClass('d-none');
        $('#cm-redis-table-wrap, #cm-redis-empty, #cm-redis-error').addClass('d-none');

        $.ajax({
            url: BASE + 'dev/cache/getRedisData',
            method: 'POST',
            data: { [CSRF_NAME]: CSRF_HASH },
            dataType: 'json',
            success: function (res) {
                $('#cm-redis-loading').addClass('d-none');
                if (!res.Error) {
                    _redisData = res.Data || [];
                    $('#cm-redis-count').text(_redisData.length);
                    if (_redisData.length === 0) {
                        $('#cm-redis-empty').removeClass('d-none');
                    } else {
                        renderTable('redis', _redisData);
                        $('#cm-redis-table-wrap').removeClass('d-none');
                    }
                } else {
                    $('#cm-redis-error').text(res.Message || 'Failed to load Redis data.').removeClass('d-none');
                }
            },
            error: function () {
                $('#cm-redis-loading').addClass('d-none');
                $('#cm-redis-error').text('Request failed.').removeClass('d-none');
            }
        });
    }

    // ── Load Upstash (lazy — on tab click) ────────────────────────────────────

    $('#cm-upstash-tab').on('shown.bs.tab', function () {
        if (_upstashLoaded) return;
        _upstashLoaded = true;
        $('#cm-upstash-hint').addClass('d-none');
        $('#cm-upstash-loading').removeClass('d-none');

        $.ajax({
            url: BASE + 'dev/cache/getUpstashData',
            method: 'POST',
            data: { [CSRF_NAME]: CSRF_HASH },
            dataType: 'json',
            success: function (res) {
                $('#cm-upstash-loading').addClass('d-none');
                if (!res.Error) {
                    if (res.Count === 0 && res.Message && res.Message.includes('not configured')) {
                        $('#cm-upstash-not-configured').removeClass('d-none');
                        $('#cm-upstash-count').text('N/A');
                    } else {
                        _upstashData = res.Data || [];
                        $('#cm-upstash-count').text(_upstashData.length);
                        if (_upstashData.length === 0) {
                            $('#cm-upstash-empty').removeClass('d-none');
                        } else {
                            renderTable('upstash', _upstashData);
                            $('#cm-upstash-table-wrap').removeClass('d-none');
                        }
                    }
                } else {
                    $('#cm-upstash-error').text(res.Message || 'Failed to load Upstash data.').removeClass('d-none');
                }
            },
            error: function () {
                $('#cm-upstash-loading').addClass('d-none');
                $('#cm-upstash-error').text('Request failed.').removeClass('d-none');
            }
        });
    });

    // ── Refresh ───────────────────────────────────────────────────────────────

    $('#cm-refresh-btn').on('click', function () {
        _upstashLoaded = false;
        loadRedis();
        const upstashActive = $('#cm-upstash-tab').hasClass('active');
        if (upstashActive) {
            _upstashLoaded = false;
            $('#cm-upstash-tab').trigger('shown.bs.tab');
        }
    });

    // ── Render table ──────────────────────────────────────────────────────────

    function renderTable(type, data) {
        const tbody = $('#cm-' + type + '-tbody');
        tbody.empty();

        data.forEach(function (item, idx) {
            const keyHtml  = '<code style="font-size:.77rem;word-break:break-all;">' + escHtml(item.key) + '</code>';
            const typeHtml = typeBadge(item.type);
            const ttlHtml  = ttlBadge(item.ttl);
            const sizeHtml = fmtSize(item.size || 0);
            const valHtml  = renderValue(item.value, type + '_' + idx);
            const delHtml  = '<button class="btn btn-sm btn-outline-danger py-0 px-2 cm-del-btn" '
                           + 'data-type="' + type + '" data-key="' + escAttr(item.key) + '" title="Delete key">'
                           + '<i class="bx bx-trash" style="font-size:.85rem;"></i></button>';

            tbody.append(
                '<tr id="cm-row-' + type + '-' + idx + '">' +
                '<td class="text-muted">' + (idx + 1) + '</td>' +
                '<td>' + keyHtml + '</td>' +
                '<td>' + typeHtml + '</td>' +
                '<td>' + ttlHtml + '</td>' +
                '<td>' + sizeHtml + '</td>' +
                '<td>' + valHtml + '</td>' +
                '<td>' + delHtml + '</td>' +
                '</tr>'
            );
        });
    }

    function renderValue(val, uid) {
        if (val === null || val === undefined) {
            return '<span class="text-muted fst-italic" style="font-size:.76rem;">null</span>';
        }
        const formatted = (typeof val === 'object')
            ? JSON.stringify(val, null, 2)
            : String(val);

        const preId  = 'pre_' + uid;
        const btnId  = 'btn_' + uid;
        const isLong = formatted.length > 300;

        return '<pre id="' + preId + '" class="cm-val-pre">' + escHtml(formatted) + '</pre>' +
               (isLong ? '<button id="' + btnId + '" class="cm-expand-btn" onclick="cmToggleExpand(\'' + preId + '\',\'' + btnId + '\')">▼ Show more</button>' : '');
    }

    function typeBadge(type) {
        const map = {
            string:  { bg: '#e0f2fe', color: '#0369a1' },
            hash:    { bg: '#fef9c3', color: '#854d0e' },
            list:    { bg: '#fce7f3', color: '#9d174d' },
            set:     { bg: '#dcfce7', color: '#166534' },
            zset:    { bg: '#ede9fe', color: '#6d28d9' },
            unknown: { bg: '#f1f5f9', color: '#64748b' },
        };
        const s = map[type] || map.unknown;
        return '<span class="cm-badge-type" style="background:' + s.bg + ';color:' + s.color + ';">' + escHtml(type || '—') + '</span>';
    }

    function ttlBadge(ttl) {
        if (ttl === -1) return '<span class="cm-ttl-badge text-success">No expiry</span>';
        if (ttl === -2) return '<span class="cm-ttl-badge text-danger">Gone</span>';
        if (ttl <= 0)   return '<span class="cm-ttl-badge text-danger">Expired</span>';
        return '<span class="cm-ttl-badge text-primary fw-medium">' + fmtTtl(ttl) + '</span>';
    }

    function fmtTtl(secs) {
        if (secs >= 3600) {
            const h = Math.floor(secs / 3600);
            const m = Math.floor((secs % 3600) / 60);
            return h + 'h ' + m + 'm';
        }
        if (secs >= 60) {
            const m = Math.floor(secs / 60);
            const s = secs % 60;
            return m + 'm ' + s + 's';
        }
        return secs + 's';
    }

    function escHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function escAttr(str) {
        return String(str).replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    function fmtSize(bytes) {
        if (!bytes || bytes <= 0) return '<span class="text-muted" style="font-size:.73rem;">—</span>';
        if (bytes < 1024)         return '<span style="font-size:.73rem;">' + bytes + ' B</span>';
        if (bytes < 1024 * 1024)  return '<span style="font-size:.73rem;font-weight:600;color:#0369a1;">' + (bytes / 1024).toFixed(1) + ' KB</span>';
        return '<span style="font-size:.73rem;font-weight:600;color:#b45309;">' + (bytes / (1024 * 1024)).toFixed(2) + ' MB</span>';
    }

    // ── Delete key ────────────────────────────────────────────────────────────

    $(document).on('click', '.cm-del-btn', function () {
        const btn  = $(this);
        const type = btn.data('type');   // 'redis' or 'upstash'
        const key  = btn.data('key');
        const url  = BASE + (type === 'redis' ? 'dev/cache/deleteRedisKey' : 'dev/cache/deleteUpstashKey');

        Swal.fire({
            title: 'Delete cache key?',
            html: '<div style="word-break:break-all;font-size:.82rem;background:#f8fafc;padding:8px 12px;border-radius:6px;font-family:monospace;margin-top:6px;">' + escHtml(key) + '</div>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#dc3545',
        }).then(function (result) {
            if (!result.isConfirmed) return;

            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

            $.ajax({
                url: url,
                method: 'POST',
                data: { key: key, [CSRF_NAME]: CSRF_HASH },
                dataType: 'json',
                success: function (res) {
                    if (!res.Error) {
                        btn.closest('tr').fadeOut(300, function () { $(this).remove(); });
                        // Update key count badge
                        const countEl = $('#cm-' + type + '-count');
                        const cur = parseInt(countEl.text()) || 0;
                        if (cur > 0) countEl.text(cur - 1);
                        // Remove from local data array
                        if (type === 'redis') {
                            _redisData = _redisData.filter(function (i) { return i.key !== key; });
                        } else {
                            _upstashData = _upstashData.filter(function (i) { return i.key !== key; });
                        }
                    } else {
                        btn.prop('disabled', false).html('<i class="bx bx-trash" style="font-size:.85rem;"></i>');
                        Swal.fire({ icon: 'error', title: 'Error', text: res.Message, timer: 3000, showConfirmButton: false });
                    }
                },
                error: function () {
                    btn.prop('disabled', false).html('<i class="bx bx-trash" style="font-size:.85rem;"></i>');
                    Swal.fire({ icon: 'error', title: 'Request failed', text: 'Please try again.', timer: 3000, showConfirmButton: false });
                }
            });
        });
    });

    // ── Search / filter ───────────────────────────────────────────────────────

    $('#cm-redis-search').on('input', function () {
        filterTable('redis', $(this).val().toLowerCase(), _redisData);
    });
    $('#cm-upstash-search').on('input', function () {
        filterTable('upstash', $(this).val().toLowerCase(), _upstashData);
    });

    function filterTable(type, q, data) {
        const filtered = !q ? data : data.filter(function (item) {
            return item.key.toLowerCase().includes(q) ||
                   JSON.stringify(item.value || '').toLowerCase().includes(q);
        });
        renderTable(type, filtered);
        // Keep count badge in sync with full data (not filtered count)
        $('#cm-' + type + '-count').text(data.length);
    }

})();

function cmToggleExpand(preId, btnId) {
    const pre = document.getElementById(preId);
    const btn = document.getElementById(btnId);
    if (!pre) return;
    if (pre.classList.contains('expanded')) {
        pre.classList.remove('expanded');
        btn.textContent = '▼ Show more';
    } else {
        pre.classList.add('expanded');
        btn.textContent = '▲ Show less';
    }
}
</script>
