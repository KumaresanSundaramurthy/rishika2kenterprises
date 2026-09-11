<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="layout-wrapper layout-horizontal layout-content-navbar">
    <div class="layout-container">
        <?php $this->load->view('common/menu_view'); ?>
        <div class="layout-page">
            <div class="content-wrapper apex-content">
                <?php $this->load->view('common/apex/page_header', [
                    'pageTitle'       => 'Subscription',
                    'pageDescription' => 'Manage your subscription plan and billing.',
                ]); ?>

                <div class="container-xxl flex-grow-1 container-p-y pt-2">

                    <?php
                    /* Current subscription status banner */
                    $sub          = $subscription ?? null;
                    $statusClass  = 'bg-label-secondary';
                    $statusLabel  = 'No Subscription';
                    $daysLeft     = 0;
                    if ($sub) {
                        $daysLeft    = max(0, (int)$sub->DaysRemaining);
                        $statusLabel = htmlspecialchars($sub->SubscriptionStatus ?? 'Unknown');
                        if ($sub->SubscriptionStatus === 'Active')  $statusClass = 'bg-label-success';
                        if ($sub->SubscriptionStatus === 'Trial')   $statusClass = 'bg-label-info';
                        if ($sub->SubscriptionStatus === 'Expired') $statusClass = 'bg-label-danger';
                    }
                    ?>

                    <!-- Subscription Summary Card -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3 p-4">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="avatar avatar-lg">
                                            <span class="avatar-initial rounded-circle bg-label-primary">
                                                <i class="bx bx-crown fs-4"></i>
                                            </span>
                                        </div>
                                        <div>
                                            <h5 class="mb-0">
                                                <?= $sub ? htmlspecialchars($sub->PlanName ?? 'Trial') : 'No Active Plan' ?>
                                            </h5>
                                            <small class="text-muted">
                                                <?php if ($sub): ?>
                                                    <?= htmlspecialchars($sub->SectorName ?? '') ?>
                                                <?php else: ?>
                                                    Contact admin to activate a plan
                                                <?php endif; ?>
                                            </small>
                                        </div>
                                    </div>

                                    <div class="d-flex flex-wrap gap-4">
                                        <div class="text-center">
                                            <span class="d-block fw-semibold"><?= $sub ? htmlspecialchars($sub->SubscriptionStartDate ?? '-') : '-' ?></span>
                                            <small class="text-muted">Start Date</small>
                                        </div>
                                        <div class="text-center">
                                            <span class="d-block fw-semibold"><?= $sub ? htmlspecialchars($sub->SubscriptionEndDate ?? '-') : '-' ?></span>
                                            <small class="text-muted">End Date</small>
                                        </div>
                                        <div class="text-center">
                                            <span class="d-block fw-semibold <?= $daysLeft <= 7 ? 'text-danger' : '' ?>">
                                                <?= $sub ? $daysLeft . ' days' : '-' ?>
                                            </span>
                                            <small class="text-muted">Days Remaining</small>
                                        </div>
                                        <div class="text-center">
                                            <span class="badge <?= $statusClass ?> fs-6"><?= $statusLabel ?></span>
                                            <br><small class="text-muted">Status</small>
                                        </div>
                                    </div>

                                    <div class="d-flex gap-2">
                                        <?php if ($sub && $sub->SectorPlanUID): ?>
                                            <button class="btn btn-outline-primary btn-sm" id="btnRenewPlan">
                                                <i class="bx bx-refresh me-1"></i>Renew
                                            </button>
                                        <?php endif; ?>
                                        <?php if (!empty($plans)): ?>
                                            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#changePlanModal">
                                                <i class="bx bx-up-arrow-circle me-1"></i>Change Plan
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Available Plans -->
                    <?php if (!empty($plans)): ?>
                    <div class="row mb-4">
                        <div class="col-12">
                            <h6 class="fw-semibold mb-3">Available Plans</h6>
                        </div>
                        <?php foreach ($plans as $plan): ?>
                        <div class="col-md-4 col-sm-6 mb-3">
                            <div class="card h-100 border <?= ($sub && $sub->SectorPlanUID == $plan->SectorPlanUID) ? 'border-primary' : '' ?>">
                                <div class="card-body text-center p-4">
                                    <?php if ($sub && $sub->SectorPlanUID == $plan->SectorPlanUID): ?>
                                        <span class="badge bg-label-primary mb-2">Current Plan</span><br>
                                    <?php endif; ?>
                                    <h5 class="mb-1"><?= htmlspecialchars($plan->PlanName) ?></h5>
                                    <div class="mb-2">
                                        <span class="fs-3 fw-bold">₹<?= number_format((float)$plan->Price, 2) ?></span>
                                        <small class="text-muted">/ <?= (int)$plan->DurationDays ?> days</small>
                                    </div>
                                    <?php if ((int)$plan->TrialDays > 0): ?>
                                        <p class="text-muted small mb-3"><?= (int)$plan->TrialDays ?>-day free trial</p>
                                    <?php endif; ?>
                                    <?php if (!$sub || $sub->SectorPlanUID != $plan->SectorPlanUID): ?>
                                        <button class="btn btn-outline-primary btn-sm btn-select-plan"
                                            data-uid="<?= (int)$plan->SectorPlanUID ?>"
                                            data-name="<?= htmlspecialchars($plan->PlanName) ?>"
                                            data-price="<?= (float)$plan->Price ?>"
                                            data-bs-toggle="modal"
                                            data-bs-target="#changePlanModal">
                                            Select Plan
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <!-- Order History -->
                    <?php if (!empty($orders)): ?>
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">Billing History</h6>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
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
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($orders as $order): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($order->OrderDate ?? '-') ?></td>
                                                <td><?= htmlspecialchars($order->PlanName ?? 'Trial') ?></td>
                                                <td><?= htmlspecialchars($order->RenewalType ?? '-') ?></td>
                                                <td class="text-end font-monospace">₹<?= number_format((float)($order->NetAmount ?? 0), 2) ?></td>
                                                <td><?= htmlspecialchars($order->DueDate ?? '-') ?></td>
                                                <td><?= $order->PaymentMode ? htmlspecialchars($order->PaymentMode) : '<span class="text-muted">-</span>' ?></td>
                                                <td><?= $order->PaidOn ? htmlspecialchars($order->PaidOn) : '<span class="text-muted">-</span>' ?></td>
                                                <td>
                                                    <?php
                                                    $oBadge = 'bg-label-secondary';
                                                    if ($order->Status === 'Paid')    $oBadge = 'bg-label-success';
                                                    if ($order->Status === 'Waived')  $oBadge = 'bg-label-info';
                                                    if ($order->Status === 'Failed')  $oBadge = 'bg-label-danger';
                                                    if ($order->Status === 'Pending') $oBadge = 'bg-label-warning';
                                                    ?>
                                                    <span class="badge <?= $oBadge ?>"><?= htmlspecialchars($order->Status) ?></span>
                                                    <?php if ($order->Status === 'Pending'): ?>
                                                        <button class="btn btn-xs btn-outline-success ms-1 btn-record-payment"
                                                            data-order="<?= (int)$order->OrderUID ?>"
                                                            data-amount="<?= (float)$order->NetAmount ?>">
                                                            Pay
                                                        </button>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

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

    /* ── Select plan card click → pre-select dropdown ── */
    document.querySelectorAll('.btn-select-plan').forEach(btn => {
        btn.addEventListener('click', function () {
            const uid = this.dataset.uid;
            const sel = document.getElementById('selPlanChange');
            for (const opt of sel.options) {
                if (opt.value == uid) {
                    opt.selected = true;
                    document.getElementById('inpPlanAmount').value = this.dataset.price ?? 0;
                    break;
                }
            }
        });
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
