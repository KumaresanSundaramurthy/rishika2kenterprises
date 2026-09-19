<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="layout-wrapper layout-horizontal layout-content-navbar">
    <div class="layout-container">
        <?php $this->load->view('common/menu_view'); ?>
        <div class="layout-page">
            <div class="content-wrapper apex-content">
                <?php $this->load->view('common/apex/page_header', [
                    'pageTitle'       => 'My Subscription',
                    'pageDescription' => 'Current plan status and billing history.',
                ]); ?>

                <div class="container-xxl flex-grow-1 container-p-y pt-2">

                    <?php
                    $sub         = $subscription ?? null;
                    $_tz         = $JwtData->Org->OrgTimezone ?? 'UTC';
                    $_startTs    = viewPageDateTimeFormat($sub->StartDate ?? null, $_tz, 2);
                    $_endTs      = viewPageDateTimeFormat($sub->EndDate   ?? null, $_tz, 2);
                    $daysLeft    = $sub ? max(0, (int)($sub->DaysRemaining ?? 0)) : 0;
                    $_ss         = $sub ? ($sub->Status ?? '') : '';
                    $statusLabel = $sub ? htmlspecialchars($_ss ?: 'Unknown') : 'No Subscription';

                    /* Progress bar */
                    $totalDays = $sub ? max(1, (int)($sub->DurationDays ?? 30)) : 30;
                    $usedDays  = max(0, $totalDays - $daysLeft);
                    $pctUsed   = min(100, (int)round($usedDays / $totalDays * 100));

                    /* Tier colors — same logic as plans page */
                    $_planName  = strtolower($sub->PlanName ?? '');
                    if (str_contains($_planName, 'enterprise')) {
                        $_tc = ['g1' => '#1e293b', 'g2' => '#0f172a', 'icon' => '#696cff', 'iconBg' => 'rgba(105,108,255,.12)'];
                    } elseif (str_contains($_planName, 'pro')) {
                        $_tc = ['g1' => '#696cff', 'g2' => '#5254cc', 'icon' => '#696cff', 'iconBg' => 'rgba(105,108,255,.12)'];
                    } else {
                        $_tc = ['g1' => '#f97316', 'g2' => '#ea580c', 'icon' => '#f97316', 'iconBg' => 'rgba(249,115,22,.12)'];
                    }

                    /* Status pill */
                    $statusStyle = 'background:#dcfce7;color:#15803d;';
                    if ($_ss === 'Trial')   $statusStyle = 'background:#dbeafe;color:#1d4ed8;';
                    if ($_ss === 'Expired') $statusStyle = 'background:#fee2e2;color:#b91c1c;';
                    if ($_ss === 'Suspended' || $_ss === 'Cancelled') $statusStyle = 'background:#f1f5f9;color:#475569;';

                    /* Bar color */
                    $barG1 = $daysLeft <= 7 ? '#ef4444' : ($daysLeft <= 14 ? '#f59e0b' : '#22c55e');
                    $barG2 = $daysLeft <= 7 ? '#dc2626' : ($daysLeft <= 14 ? '#d97706' : '#16a34a');
                    ?>

<style>
.sdb-plan-card {
    border-radius: 16px;
    border: 1.5px solid var(--bs-border-color, #e8e8e8);
    background: var(--bs-card-bg, #fff);
    overflow: hidden;
    box-shadow: 0 2px 12px rgba(0,0,0,.06);
}
.sdb-plan-band {
    height: 5px;
    background: linear-gradient(90deg, <?= $_tc['g1'] ?>, <?= $_tc['g2'] ?>);
}
.sdb-plan-body { padding: 1.5rem 1.75rem 1.75rem; }
.sdb-plan-icon {
    width: 52px; height: 52px; border-radius: 14px;
    display: flex; align-items: center; justify-content: center;
    background: <?= $_tc['iconBg'] ?>;
    flex-shrink: 0;
}
.sdb-plan-icon i { font-size: 1.5rem; color: <?= $_tc['icon'] ?>; }
.sdb-plan-name { font-size: 1.15rem; font-weight: 800; line-height: 1.2; }
.sdb-status-pill {
    display: inline-flex; align-items: center;
    font-size: .7rem; font-weight: 700; letter-spacing: .05em; text-transform: uppercase;
    padding: .2rem .65rem; border-radius: 99px;
    <?= $statusStyle ?>
}
.sdb-plan-sub { font-size: .8rem; color: var(--bs-secondary-color, #6c757d); margin-top: .2rem; }
.sdb-metric-row { display: flex; flex-wrap: wrap; gap: .75rem; margin-top: 1.25rem; }
.sdb-metric {
    flex: 1 1 120px;
    background: var(--bs-body-bg, #f8f9fa);
    border: 1px solid var(--bs-border-color, #e8e8e8);
    border-radius: 10px;
    padding: .7rem 1rem;
}
.sdb-metric-label { font-size: .7rem; text-transform: uppercase; letter-spacing: .07em; color: var(--bs-secondary-color, #888); font-weight: 600; margin-bottom: .2rem; }
.sdb-metric-value { font-size: .95rem; font-weight: 700; }
.sdb-metric-value.danger { color: #dc2626; }
.sdb-progress-wrap { margin-top: 1.25rem; }
.sdb-progress-labels { display: flex; justify-content: space-between; margin-bottom: .4rem; }
.sdb-progress-labels span { font-size: .75rem; color: var(--bs-secondary-color, #888); }
.sdb-progress-track {
    height: 8px; border-radius: 99px;
    background: var(--bs-border-color, #e8e8e8);
    overflow: hidden;
}
.sdb-progress-fill {
    height: 100%; border-radius: 99px;
    background: linear-gradient(90deg, <?= $barG1 ?>, <?= $barG2 ?>);
    width: <?= $pctUsed ?>%;
    transition: width .6s ease;
}
.sdb-action-row { display: flex; flex-wrap: wrap; gap: .6rem; margin-top: 1.5rem; padding-top: 1.25rem; border-top: 1.5px solid var(--bs-border-color, #e8e8e8); }
.sdb-action-row .btn { font-size: .8rem; padding: .4rem .9rem; }

.sdb-billing-card {
    border-radius: 16px;
    border: 1.5px solid var(--bs-border-color, #e8e8e8);
    background: var(--bs-card-bg, #fff);
    overflow: hidden;
    box-shadow: 0 2px 12px rgba(0,0,0,.06);
}
.sdb-billing-header {
    padding: 1rem 1.5rem;
    border-bottom: 1.5px solid var(--bs-border-color, #e8e8e8);
    display: flex; align-items: center; justify-content: space-between;
}
.sdb-billing-header h6 { font-size: .85rem; font-weight: 700; text-transform: uppercase; letter-spacing: .07em; color: var(--bs-secondary-color, #888); margin: 0; }
.sdb-table { width: 100%; border-collapse: collapse; }
.sdb-table th {
    font-size: .72rem; text-transform: uppercase; letter-spacing: .06em;
    color: var(--bs-secondary-color, #888); font-weight: 600;
    padding: .6rem 1rem; border-bottom: 1.5px solid var(--bs-border-color, #e8e8e8);
    white-space: nowrap; background: var(--bs-body-bg, #fafafa);
}
.sdb-table td { padding: .75rem 1rem; font-size: .83rem; border-bottom: 1px solid var(--bs-border-color, #f0f0f0); vertical-align: middle; }
.sdb-table tr:last-child td { border-bottom: none; }
.sdb-table tr:hover td { background: var(--bs-body-bg, #fafafa); }
.sdb-type-pill {
    display: inline-block; font-size: .68rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em;
    padding: .15rem .5rem; border-radius: 99px;
}
.sdb-type-new      { background: #dbeafe; color: #1d4ed8; }
.sdb-type-renewal  { background: #dcfce7; color: #15803d; }
.sdb-type-upgrade  { background: #ede9fe; color: #6d28d9; }
.sdb-type-downgrade{ background: #fef9c3; color: #854d0e; }
.sdb-type-trial    { background: #f1f5f9; color: #475569; }
.sdb-empty-state { padding: 3.5rem 1.5rem; text-align: center; }
.sdb-empty-icon {
    width: 60px; height: 60px; border-radius: 16px;
    background: rgba(105,108,255,.08); display: flex; align-items: center; justify-content: center;
    margin: 0 auto 1rem;
}
.sdb-empty-icon i { font-size: 1.6rem; color: #696cff; }
</style>

                    <!-- Current Plan Card -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="sdb-plan-card">
                                <div class="sdb-plan-band"></div>
                                <div class="sdb-plan-body">

                                    <!-- Top row: icon + name + actions -->
                                    <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
                                        <div class="d-flex align-items-start gap-3">
                                            <div class="sdb-plan-icon">
                                                <i class="bx bx-crown"></i>
                                            </div>
                                            <div>
                                                <div class="d-flex align-items-center flex-wrap gap-2 mb-1">
                                                    <span class="sdb-plan-name">
                                                        <?= $sub ? htmlspecialchars($sub->PlanName ?? 'Trial Plan') : 'No Active Plan' ?>
                                                    </span>
                                                    <span class="sdb-status-pill"><?= $statusLabel ?></span>
                                                </div>
                                                <div class="sdb-plan-sub">
                                                    <?php if ($sub): ?>
                                                        <?= htmlspecialchars($sub->SectorName ?? '') ?>
                                                        <?php if (!empty($sub->BillingCycle)): ?>
                                                            &middot; <?= htmlspecialchars($sub->BillingCycle) ?>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        Contact admin to activate a plan
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="sdb-action-row" style="margin-top:0; padding-top:0; border-top:none;">
                                            <?php if ($sub && $sub->SectorPlanUID): ?>
                                                <button class="btn btn-outline-secondary" id="btnRenewPlan">
                                                    <i class="bx bx-refresh me-1"></i>Renew
                                                </button>
                                            <?php endif; ?>
                                            <button class="btn btn-outline-primary"
                                                data-bs-toggle="modal" data-bs-target="#changePlanModal">
                                                <i class="bx bx-transfer-alt me-1"></i>Change Plan
                                            </button>
                                            <a href="/subscription/plans" class="btn btn-primary">
                                                <i class="bx bx-list-check me-1"></i>Browse Plans
                                            </a>
                                        </div>
                                    </div>

                                    <!-- Metric tiles -->
                                    <div class="sdb-metric-row">
                                        <div class="sdb-metric">
                                            <div class="sdb-metric-label">Start Date</div>
                                            <div class="sdb-metric-value"><?= $sub ? $_startTs->formatted : '—' ?></div>
                                        </div>
                                        <div class="sdb-metric">
                                            <div class="sdb-metric-label">End Date</div>
                                            <div class="sdb-metric-value"><?= $sub ? $_endTs->formatted : '—' ?></div>
                                        </div>
                                        <div class="sdb-metric">
                                            <div class="sdb-metric-label">Days Remaining</div>
                                            <div class="sdb-metric-value <?= $daysLeft <= 7 ? 'danger' : '' ?>">
                                                <?= $sub ? $daysLeft . ' days' : '—' ?>
                                            </div>
                                        </div>
                                        <div class="sdb-metric">
                                            <div class="sdb-metric-label">Plan Duration</div>
                                            <div class="sdb-metric-value"><?= $sub ? $totalDays . ' days' : '—' ?></div>
                                        </div>
                                    </div>

                                    <!-- Progress bar -->
                                    <?php if ($sub): ?>
                                    <div class="sdb-progress-wrap">
                                        <div class="sdb-progress-labels">
                                            <span>Plan usage</span>
                                            <span><?= $usedDays ?> of <?= $totalDays ?> days used &mdash; <?= $pctUsed ?>%</span>
                                        </div>
                                        <div class="sdb-progress-track">
                                            <div class="sdb-progress-fill"></div>
                                        </div>
                                    </div>
                                    <?php endif; ?>

                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Billing History -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="sdb-billing-card">
                                <div class="sdb-billing-header">
                                    <h6>Billing History</h6>
                                </div>
                                <?php if (!empty($orders)): ?>
                                <div class="table-responsive">
                                    <table class="sdb-table">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Plan</th>
                                                <th>Type</th>
                                                <th class="text-end">Amount</th>
                                                <th>Due Date</th>
                                                <th>Payment Mode</th>
                                                <th>Paid On</th>
                                                <th>Status</th>
                                                <th class="text-center">Invoice</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($orders as $order): ?>
                                            <?php
                                            $oStatus = $order->Status ?? '';
                                            $oBadge  = 'bg-label-secondary';
                                            if ($oStatus === 'Paid')    $oBadge = 'bg-label-success';
                                            if ($oStatus === 'Waived')  $oBadge = 'bg-label-info';
                                            if ($oStatus === 'Failed')  $oBadge = 'bg-label-danger';
                                            if ($oStatus === 'Pending') $oBadge = 'bg-label-warning';
                                            $rType  = strtolower($order->RenewalType ?? '');
                                            $tClass = 'sdb-type-' . ($rType ?: 'new');
                                            ?>
                                            <tr>
                                                <td><?= viewPageDateTimeFormat($order->OrderDate ?? null, $_tz, 2)->formatted ?></td>
                                                <td class="fw-semibold"><?= htmlspecialchars($order->PlanName ?? 'Trial') ?></td>
                                                <td><span class="sdb-type-pill <?= $tClass ?>"><?= htmlspecialchars($order->RenewalType ?? '-') ?></span></td>
                                                <td class="text-end fw-bold font-monospace">₹<?= number_format((float)($order->NetAmount ?? 0), 2) ?></td>
                                                <td><?= viewPageDateTimeFormat($order->DueDate ?? null, $_tz, 1)->formatted ?></td>
                                                <td><?= $order->PaymentMode ? htmlspecialchars($order->PaymentMode) : '<span class="text-muted">—</span>' ?></td>
                                                <td><?= viewPageDateTimeFormat($order->PaidOn ?? null, $_tz, 2)->formatted ?></td>
                                                <td>
                                                    <span class="badge <?= $oBadge ?>"><?= htmlspecialchars($oStatus) ?></span>
                                                    <?php if ($oStatus === 'Pending'): ?>
                                                        <button class="btn btn-xs btn-outline-success ms-1 btn-record-payment"
                                                            data-order="<?= (int)$order->OrderUID ?>"
                                                            data-amount="<?= (float)$order->NetAmount ?>">
                                                            Pay
                                                        </button>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center">
                                                    <?php if (!empty($order->PDFPath) && !empty($order->InvoiceUID)): ?>
                                                    <a href="<?= base_url('subscription/invoice/' . (int)$order->InvoiceUID) ?>"
                                                       title="Download <?= htmlspecialchars($order->InvoiceNumber ?? 'Invoice') ?>"
                                                       class="text-primary">
                                                        <i class="bx bx-download fs-5"></i>
                                                    </a>
                                                    <?php else: ?>
                                                    <span class="text-muted">—</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php else: ?>
                                <div class="sdb-empty-state">
                                    <div class="sdb-empty-icon">
                                        <i class="bx bx-receipt"></i>
                                    </div>
                                    <p class="fw-semibold mb-1">No billing records yet</p>
                                    <p class="text-muted small mb-3">Your invoices and payment history will appear here.</p>
                                    <a href="/subscription/plans" class="btn btn-outline-primary btn-sm">
                                        <i class="bx bx-list-check me-1"></i>View Plans
                                    </a>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                </div><!-- /container -->
            </div>
        </div>
    </div>
</div>

<!-- Change Plan Modal -->
<div class="modal fade" id="changePlanModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Change Plan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="frmChangePlan">
                    <div class="mb-3">
                        <label class="form-label">Plan <span class="text-danger">*</span></label>
                        <select class="form-select" name="sector_plan_uid" id="selPlanChange" required>
                            <option value="">-- Select Plan --</option>
                            <?php foreach ($plans as $plan): ?>
                            <option value="<?= (int)$plan->SectorPlanUID ?>"
                                data-price="<?= (float)$plan->Price ?>"
                                <?= ($sub && $sub->SectorPlanUID == $plan->SectorPlanUID) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($plan->PlanName) ?> — ₹<?= number_format((float)$plan->Price, 2) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Renewal Type <span class="text-danger">*</span></label>
                        <select class="form-select" name="renewal_type" required>
                            <option value="New">New</option>
                            <option value="Upgrade">Upgrade</option>
                            <option value="Downgrade">Downgrade</option>
                            <option value="Renewal">Renewal</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Amount</label>
                        <input type="number" class="form-control" name="amount" id="inpPlanAmount" step="0.01" min="0" value="0">
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col">
                            <label class="form-label">Discount</label>
                            <input type="number" class="form-control" name="discount" step="0.01" min="0" value="0">
                        </div>
                        <div class="col">
                            <label class="form-label">Tax</label>
                            <input type="number" class="form-control" name="tax" step="0.01" min="0" value="0">
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_paid" id="chkIsPaid" value="1">
                            <label class="form-check-label" for="chkIsPaid">Mark as Paid</label>
                        </div>
                    </div>
                    <div class="mb-3" id="payModeWrap" style="display:none;">
                        <label class="form-label">Payment Mode</label>
                        <select class="form-select" name="payment_mode">
                            <option value="">-- Select --</option>
                            <option value="Cash">Cash</option>
                            <option value="Bank Transfer">Bank Transfer</option>
                            <option value="UPI">UPI</option>
                            <option value="Cheque">Cheque</option>
                            <option value="Card">Card</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <input type="text" class="form-control" name="notes" maxlength="255">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="btnConfirmPlanChange">
                    <span class="spinner-border spinner-border-sm d-none me-1" id="spinPlanChange"></span>
                    Confirm Change
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Record Payment Modal -->
<div class="modal fade" id="recordPaymentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Record Payment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="frmRecordPayment">
                    <input type="hidden" name="order_uid" id="hidOrderUID">
                    <div class="mb-3">
                        <label class="form-label">Amount</label>
                        <input type="number" class="form-control" name="amount" id="inpPayAmount" step="0.01" min="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Payment Mode <span class="text-danger">*</span></label>
                        <select class="form-select" name="payment_mode" required>
                            <option value="">-- Select --</option>
                            <option value="Cash">Cash</option>
                            <option value="Bank Transfer">Bank Transfer</option>
                            <option value="UPI">UPI</option>
                            <option value="Cheque">Cheque</option>
                            <option value="Card">Card</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="btnConfirmPayment">
                    <span class="spinner-border spinner-border-sm d-none me-1" id="spinPayment"></span>
                    Record Payment
                </button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    const BASE_URL = '<?= base_url() ?>';

    /* ── Change plan: auto-fill amount when plan selected ── */
    document.getElementById('selPlanChange').addEventListener('change', function () {
        const opt = this.options[this.selectedIndex];
        document.getElementById('inpPlanAmount').value = opt.dataset.price ?? 0;
    });

    /* ── Show/hide payment mode based on is_paid ── */
    document.getElementById('chkIsPaid').addEventListener('change', function () {
        document.getElementById('payModeWrap').style.display = this.checked ? '' : 'none';
    });

    /* ── Confirm plan change ── */
    document.getElementById('btnConfirmPlanChange').addEventListener('click', function () {
        const form    = document.getElementById('frmChangePlan');
        const spinner = document.getElementById('spinPlanChange');
        if (!form.checkValidity()) { form.reportValidity(); return; }

        const data = new FormData(form);
        spinner.classList.remove('d-none');
        this.disabled = true;

        fetch(BASE_URL + 'subscription/changePlan', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: data,
        })
        .then(r => r.json())
        .then(res => {
            spinner.classList.add('d-none');
            this.disabled = false;
            if (res.Status === 'OK') {
                bootstrap.Modal.getInstance(document.getElementById('changePlanModal')).hide();
                toastMsg(res.Message, 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                toastMsg(res.Message || 'Failed to change plan.', 'error');
            }
        })
        .catch(() => {
            spinner.classList.add('d-none');
            this.disabled = false;
            toastMsg('Network error. Please try again.', 'error');
        });
    });

    /* ── Renew current plan ── */
    const btnRenew = document.getElementById('btnRenewPlan');
    if (btnRenew) {
        btnRenew.addEventListener('click', function () {
            if (!confirm('Renew the current plan now?')) return;
            this.disabled = true;

            fetch(BASE_URL + 'subscription/renewPlan', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: new FormData(),
            })
            .then(r => r.json())
            .then(res => {
                this.disabled = false;
                if (res.Status === 'OK') {
                    toastMsg(res.Message, 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    toastMsg(res.Message || 'Renewal failed.', 'error');
                }
            })
            .catch(() => {
                this.disabled = false;
                toastMsg('Network error.', 'error');
            });
        });
    }

    /* ── Record payment: open modal with order details ── */
    document.querySelectorAll('.btn-record-payment').forEach(btn => {
        btn.addEventListener('click', function () {
            document.getElementById('hidOrderUID').value  = this.dataset.order;
            document.getElementById('inpPayAmount').value = this.dataset.amount;
            new bootstrap.Modal(document.getElementById('recordPaymentModal')).show();
        });
    });

    document.getElementById('btnConfirmPayment').addEventListener('click', function () {
        const form    = document.getElementById('frmRecordPayment');
        const spinner = document.getElementById('spinPayment');
        if (!form.checkValidity()) { form.reportValidity(); return; }

        const data = new FormData(form);
        spinner.classList.remove('d-none');
        this.disabled = true;

        fetch(BASE_URL + 'subscription/recordPayment', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: data,
        })
        .then(r => r.json())
        .then(res => {
            spinner.classList.add('d-none');
            this.disabled = false;
            if (res.Status === 'OK') {
                bootstrap.Modal.getInstance(document.getElementById('recordPaymentModal')).hide();
                toastMsg(res.Message, 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                toastMsg(res.Message || 'Payment failed.', 'error');
            }
        })
        .catch(() => {
            spinner.classList.add('d-none');
            this.disabled = false;
            toastMsg('Network error.', 'error');
        });
    });

    function toastMsg(msg, type) {
        if (typeof showToast === 'function') { showToast(msg, type); return; }
        alert(msg);
    }
})();
</script>
