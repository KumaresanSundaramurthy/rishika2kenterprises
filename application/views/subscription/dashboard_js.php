<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<script>
$(function() {

    var _svcUrl   = '<?= rtrim(getenv('SERVICES_PUBLIC_URL') ?: getenv('SERVICES_BASE_URL') ?: '', '/') . '/' ?>';
    var _jwtToken = '<?= addslashes($this->pageData['JwtToken'] ?? '') ?>';
    var _orgUID   = <?= (int)($this->pageData['JwtData']->Org->OrgUID ?? 0) ?>;

    /**
     * Build Authorization header for NestJS calls.
     * @returns {object}
     */
    function _authHeaders() {
        return { Authorization: 'Bearer ' + _jwtToken };
    }

    /**
     * Format an ISO date string for display using the user's preferred date format.
     * @param {string} val  YYYY-MM-DD or ISO datetime
     * @returns {string}
     */
    function _fmtDate(val) {
        if (!val) return '—';
        var d = new Date(String(val).slice(0, 10) + 'T00:00:00Z');
        if (isNaN(d.getTime())) return String(val);
        var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
        return d.getUTCDate() + ' ' + months[d.getUTCMonth()] + ' ' + d.getUTCFullYear();
    }

    /**
     * HTML-escape a string.
     * @param {string} str
     * @returns {string}
     */
    function _esc(str) {
        return String(str ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    /* ═══════════════════════════════════════════════════════════════
       DASHBOARD DATA LOAD — 3 parallel calls on page ready
    ═══════════════════════════════════════════════════════════════ */

    var _reqPlan   = $.ajax({ url: _svcUrl + 'api/subscription/myPlan',   headers: _authHeaders() });
    var _reqOrders = $.ajax({ url: _svcUrl + 'api/subscription/myOrders', headers: _authHeaders() });
    var _reqPlans  = $.ajax({ url: _svcUrl + 'api/subscription/myPlans',  headers: _authHeaders() });

    $.when(_reqPlan, _reqOrders, _reqPlans).then(
        function(planRes, ordersRes, plansRes) {
            var sub     = (planRes[0]   && !planRes[0].Error)   ? planRes[0].Data   : null;
            var orders  = (ordersRes[0] && !ordersRes[0].Error) ? ordersRes[0].Data : [];
            var plans   = (plansRes[0]  && !plansRes[0].Error)  ? plansRes[0].Data  : [];

            _renderSubscriptionCard(sub);
            _renderBillingHistory(orders);
            _renderPlanOptions(plans, sub ? (sub.SectorPlanUID || 0) : 0);
        },
        function() {
            _renderSubscriptionCard(null);
            _renderBillingHistory([]);
            _renderPlanOptions([], 0);
        }
    );

    /* ── Render helpers ──────────────────────────────────────────── */

    /**
     * Render the current plan card from subscription data.
     * @param {object|null} sub  Subscription row from NestJS (null = no active plan)
     * @returns {void}
     */
    function _renderSubscriptionCard(sub) {
        if (!sub) {
            $('#planName').text('No Active Plan');
            $('#planSub').text('Subscribe to a plan to get started.');
            $('#statusPill')
                .text('None')
                .css({ background: '#f1f5f9', color: '#475569' })
                .show();
            return;
        }

        var status = String(sub.Status || 'Active');

        /* Status pill */
        var pillStyles = {
            'Active'    : { bg: '#dcfce7', color: '#15803d' },
            'Trial'     : { bg: '#dbeafe', color: '#1d4ed8' },
            'Expired'   : { bg: '#fee2e2', color: '#dc2626' },
            'Suspended' : { bg: '#ffedd5', color: '#c2410c' },
        };
        var pill = pillStyles[status] || { bg: '#f1f5f9', color: '#475569' };
        $('#statusPill').text(status).css({ background: pill.bg, color: pill.color }).show();

        /* Plan band gradient */
        var bandGrads = {
            'Active'    : 'linear-gradient(90deg,#22c55e,#16a34a)',
            'Trial'     : 'linear-gradient(90deg,#3b82f6,#2563eb)',
            'Expired'   : 'linear-gradient(90deg,#ef4444,#dc2626)',
            'Suspended' : 'linear-gradient(90deg,#f97316,#ea580c)',
        };
        $('#planBand').css('background', bandGrads[status] || bandGrads['Active']);

        /* Plan icon tint */
        var iconColors = { 'Active':'#22c55e','Trial':'#3b82f6','Expired':'#ef4444','Suspended':'#f97316' };
        var iconColor  = iconColors[status] || '#696cff';
        $('#planIcon').css('background', 'rgba(' + (status === 'Active' ? '34,197,94' : status === 'Trial' ? '59,130,246' : status === 'Expired' ? '239,68,68' : '249,115,22') + ',.12)');
        $('#planIcon i').css('color', iconColor);

        /* Plan name + sub-line */
        var cycle = sub.BillingCycle || '';
        $('#planName').text(sub.PlanName || 'Subscription');
        $('#planSub').text(cycle ? cycle + ' Plan' : 'Subscription Plan');

        /* Metrics */
        var startDate = String(sub.StartDate || '').slice(0, 10);
        var endDate   = String(sub.EndDate   || '').slice(0, 10);

        $('#metricStart').text(_fmtDate(startDate));
        $('#metricEnd').text(_fmtDate(endDate));

        /* Days remaining — use server DATEDIFF (timezone-safe); JS fallback normalises to UTC dates */
        var daysLeft = 0;
        var totalDays = 0;
        if (sub.DaysRemaining != null) {
            daysLeft = Math.max(0, parseInt(sub.DaysRemaining, 10));
        } else if (endDate) {
            var todayUtc = new Date(Date.UTC(new Date().getUTCFullYear(), new Date().getUTCMonth(), new Date().getUTCDate()));
            var endUtc   = new Date(endDate + 'T00:00:00Z');
            daysLeft = Math.max(0, Math.round((endUtc - todayUtc) / 86400000));
        }
        if (startDate && endDate) {
            var s = new Date(startDate + 'T00:00:00Z');
            var e = new Date(endDate + 'T00:00:00Z');
            totalDays = Math.max(1, Math.round((e - s) / 86400000));
        }

        var $metricDays = $('#metricDays').text(daysLeft === 0 ? 'Expired' : daysLeft + ' days');
        $metricDays.toggleClass('danger', daysLeft <= 7 && status !== 'Trial');

        /* Duration label */
        var durationLabel = cycle || (totalDays >= 360 ? 'Yearly' : totalDays >= 25 ? 'Monthly' : totalDays + ' days');
        $('#metricDuration').text(durationLabel);

        /* Progress bar */
        if (totalDays > 0 && startDate && endDate && status !== 'Expired') {
            var pct = Math.min(100, Math.round(((totalDays - daysLeft) / totalDays) * 100));
            var progressColor = pct >= 90 ? 'linear-gradient(90deg,#ef4444,#dc2626)'
                              : pct >= 70 ? 'linear-gradient(90deg,#f97316,#ea580c)'
                              : 'linear-gradient(90deg,#22c55e,#16a34a)';
            $('#progressFill').css({ width: pct + '%', background: progressColor });
            $('#progressLabel').text(pct + '% used');
            $('#progressWrap').removeClass('d-none');
        }

        /* Show renew button when expiring soon or expired */
        if (status === 'Expired' || daysLeft <= 14) {
            $('#btnRenewPlan').removeClass('d-none');
        }
    }

    /**
     * Render the billing history table.
     * @param {Array} orders  Array of order rows from NestJS
     * @returns {void}
     */
    function _renderBillingHistory(orders) {
        if (!orders || orders.length === 0) {
            $('#billingHistoryWrap').html(
                '<div class="sdb-empty-state">' +
                '<div class="sdb-empty-icon"><i class="bx bx-receipt"></i></div>' +
                '<div style="font-weight:600;margin-bottom:.3rem;">No billing history</div>' +
                '<div style="font-size:.82rem;color:var(--bs-secondary-color,#888);">Your invoices will appear here once you have a subscription.</div>' +
                '</div>'
            );
            return;
        }

        var typePillClass = {
            'New'       : 'sdb-type-new',
            'Renewal'   : 'sdb-type-renewal',
            'Upgrade'   : 'sdb-type-upgrade',
            'Downgrade' : 'sdb-type-downgrade',
            'Manual'    : 'sdb-type-manual',
            'Trial'     : 'sdb-type-trial',
        };

        var rows = '';
        $.each(orders, function(i, o) {
            var typeClass = typePillClass[o.RenewalType] || 'sdb-type-manual';
            var paidBadge = o.Status === 'Paid'
                ? '<span style="font-size:.7rem;font-weight:700;color:#15803d;">&#10003; Paid</span>'
                : '<span style="font-size:.7rem;font-weight:700;color:#c2410c;">Pending</span>';

            var dlBtn    = '';
            var regenBtn = '';
            if (o.InvoiceUID) {
                dlBtn    = '<button class="btn btn-sm btn-outline-secondary btn-invoice-dl" style="padding:.2rem .5rem;font-size:.75rem;" data-invoice-uid="' + _esc(o.InvoiceUID) + '" data-invoice-number="' + _esc(o.InvoiceNumber || '') + '" title="Download PDF"><i class="bx bx-download"></i></button>';
                regenBtn = '<button class="btn btn-sm btn-outline-secondary btn-invoice-regen" style="padding:.2rem .5rem;font-size:.75rem;" data-invoice-uid="' + _esc(o.InvoiceUID) + '" title="Regenerate PDF"><i class="bx bx-refresh"></i></button>';
            }

            var amount = o.NetAmount != null ? '&#8377;&nbsp;' + Number(o.NetAmount).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '—';
            var invCell = '<div style="display:flex;align-items:center;gap:8px;white-space:nowrap;">' +
                            '<span>' + (o.InvoiceNumber ? _esc(o.InvoiceNumber) : '—') + '</span>' +
                            (dlBtn + regenBtn) +
                          '</div>';

            rows += '<tr>' +
                '<td>' + _fmtDate(o.OrderDate) + '</td>' +
                '<td>' + _esc(o.PlanName || '—') + '</td>' +
                '<td><span class="sdb-type-pill ' + typeClass + '">' + _esc(o.RenewalType || '—') + '</span></td>' +
                '<td>' + amount + '</td>' +
                '<td>' + paidBadge + '</td>' +
                '<td>' + invCell + '</td>' +
            '</tr>';
        });

        $('#billingHistoryWrap').html(
            '<div style="overflow-x:auto;">' +
            '<table class="sdb-table">' +
            '<thead><tr>' +
            '<th>Date</th><th>Plan</th><th>Type</th><th>Amount</th><th>Status</th><th>Invoice</th>' +
            '</tr></thead>' +
            '<tbody>' + rows + '</tbody>' +
            '</table>' +
            '</div>'
        );
    }

    /**
     * Populate the Change Plan select with available plans.
     * @param {Array}  plans       Array of available plan rows
     * @param {number} currentUID  Current SectorPlanUID to mark as selected
     * @returns {void}
     */
    function _renderPlanOptions(plans, currentUID) {
        var $sel = $('#selPlanChange').empty().append('<option value="">-- Select a plan --</option>');
        if (!plans || plans.length === 0) {
            $sel.append('<option value="" disabled>No plans available</option>');
            return;
        }
        $.each(plans, function(i, p) {
            var label   = _esc(p.PlanName || 'Plan') + ' (' + _esc(p.BillingCycle || '') + ')';
            var price   = p.BasePrice != null ? p.BasePrice : 0;
            var $opt    = $('<option>', { value: p.SectorPlanUID, text: label })
                .data('price', price)
                .prop('selected', p.SectorPlanUID == currentUID);
            $sel.append($opt);
        });
        /* Auto-fill amount for initial selection */
        $('#inpPlanAmount').val($sel.find(':selected').data('price') || 0);
    }

    /**
     * Re-fetch the billing history from NestJS and re-render the table (no page reload).
     * @returns {void}
     */
    function _refreshOrders() {
        $.ajax({
            url     : _svcUrl + 'api/subscription/myOrders',
            headers : _authHeaders(),
            success : function(res) {
                _renderBillingHistory((res && !res.Error) ? res.Data : []);
            }
        });
    }

    /* ═══════════════════════════════════════════════════════════════
       EXISTING EVENT HANDLERS (unchanged logic, vanilla JS converted)
    ═══════════════════════════════════════════════════════════════ */

    /* ── Invoice download — blob download requires XHR responseType ── */
    $(document).on('click', '.btn-invoice-dl', function() {
        var $btn       = $(this).prop('disabled', true);
        var invoiceUID = $btn.data('invoice-uid');
        var invoiceNum = $btn.data('invoice-number') || invoiceUID;
        var url        = _svcUrl + 'api/billing/invoice/download?invoiceUID=' + invoiceUID + '&orgUID=' + _orgUID;

        $.ajax({
            url         : url,
            method      : 'GET',
            headers     : _authHeaders(),
            xhrFields   : { responseType: 'blob' },
            success: function(blob, status, xhr) {
                var link  = document.createElement('a');
                link.href = URL.createObjectURL(blob);
                link.download = 'Invoice-' + invoiceNum + '.pdf';
                document.body.appendChild(link);
                link.click();
                link.remove();
                URL.revokeObjectURL(link.href);
            },
            error: function(xhr) {
                var msg = 'Could not download invoice.';
                try { msg = JSON.parse(xhr.responseText).Message || msg; } catch (e) {}
                showToastNotification(msg, 'error');
            },
            complete: function() { $btn.prop('disabled', false); }
        });
    });

    /* ── Invoice regenerate ── */
    $(document).on('click', '.btn-invoice-regen', function() {
        var $btn       = $(this).prop('disabled', true);
        var $icon      = $btn.find('i').removeClass('bx-refresh').addClass('bx-loader-alt bx-spin');
        var invoiceUID = $btn.data('invoice-uid');

        $.ajax({
            url         : _svcUrl + 'api/billing/invoice/regenerate',
            method      : 'POST',
            contentType : 'application/json',
            headers     : _authHeaders(),
            data        : JSON.stringify({ invoiceUID: invoiceUID, orgUID: _orgUID }),
            dataType    : 'json',
            success: function(res) {
                if (res.url) {
                    showToastNotification('Invoice regenerated. You can now download it.', 'success');
                    _refreshOrders();
                } else {
                    showToastNotification('Regeneration failed — invoice data not found.', 'error');
                }
            },
            error: function() {
                showToastNotification('Network error. Please try again.', 'error');
            },
            complete: function() {
                $icon.removeClass('bx-loader-alt bx-spin').addClass('bx-refresh');
                $btn.prop('disabled', false);
            }
        });
    });

    /* ── Change plan: auto-fill amount when plan selected ── */
    $('#selPlanChange').on('change', function() {
        $('#inpPlanAmount').val($(this).find(':selected').data('price') || 0);
    });

    /* ── Show/hide payment mode based on is_paid ── */
    $('#chkIsPaid').on('change', function() {
        $('#payModeWrap').toggle($(this).is(':checked'));
    });

    /* ── Confirm plan change ── */
    $('#btnConfirmPlanChange').on('click', function() {
        var $form = $('#frmChangePlan');
        if (!$form[0].checkValidity()) { $form[0].reportValidity(); return; }
        var $btn = $(this).prop('disabled', true);
        $('#spinPlanChange').removeClass('d-none');
        ajaxLoading(0);
        $.ajax({
            url         : _svcUrl + 'api/subscription/changePlan',
            method      : 'POST',
            contentType : 'application/json',
            headers     : _authHeaders(),
            data        : JSON.stringify({ sectorPlanUID: parseInt($('#selPlanChange').val(), 10) }),
            dataType    : 'json',
            success: function(res) {
                if (res.Status === 'OK') {
                    bootstrap.Modal.getInstance($('#changePlanModal')[0]).hide();
                    showToastNotification(res.Message || 'Plan changed successfully.', 'success');
                    setTimeout(function() { location.reload(); }, 1500);
                } else {
                    showToastNotification(res.Message || 'Failed to change plan.', 'error');
                }
            },
            error: function() {
                showToastNotification('Network error. Please try again.', 'error');
            },
            complete: function() {
                $('#spinPlanChange').addClass('d-none');
                $btn.prop('disabled', false);
                ajaxLoading(1);
            }
        });
    });

    /* ── Renew current plan ── */
    $('#btnRenewPlan').on('click', function() {
        var $btn = $(this);
        Swal.fire({
            title              : 'Renew Plan',
            text               : 'Renew the current plan now?',
            icon               : 'question',
            showCancelButton   : true,
            confirmButtonColor : '#696cff',
            confirmButtonText  : 'Yes, Renew',
        }).then(function(result) {
            if (!result.isConfirmed) return;
            $btn.prop('disabled', true);
            ajaxLoading(0);
            $.ajax({
                url         : _svcUrl + 'api/subscription/renewPlan',
                method      : 'POST',
                contentType : 'application/json',
                headers     : _authHeaders(),
                data        : JSON.stringify({}),
                dataType    : 'json',
                success: function(res) {
                    if (res.Status === 'OK') {
                        showToastNotification(res.Message || 'Renewed successfully.', 'success');
                        setTimeout(function() { location.reload(); }, 1500);
                    } else {
                        showToastNotification(res.Message || 'Renewal failed.', 'error');
                    }
                },
                error: function() {
                    showToastNotification('Network error.', 'error');
                },
                complete: function() {
                    $btn.prop('disabled', false);
                    ajaxLoading(1);
                }
            });
        });
    });

});
</script>
