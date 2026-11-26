<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="layout-wrapper layout-horizontal layout-content-navbar">
    <div class="layout-container">
        <?php $this->load->view('common/menu_view'); ?>
        <div class="layout-page">
            <div class="content-wrapper apex-content r2k-form-body">
                <?php $this->load->view('common/apex/page_header', [
                    'pageTitle'       => 'My Subscription',
                    'pageDescription' => 'Current plan status and billing history.',
                ]); ?>

                <div class="container-xxl flex-grow-1 container-p-y pt-2">

<style>
.sdb-plan-card {
    border-radius: 16px;
    border: 1.5px solid var(--bs-border-color, #e8e8e8);
    background: var(--bs-card-bg, #fff);
    overflow: hidden;
    box-shadow: 0 2px 12px rgba(0,0,0,.06);
}
.sdb-plan-band { height: 5px; background: linear-gradient(90deg, #f97316, #ea580c); }
.sdb-plan-body { padding: 1.5rem 1.75rem 1.75rem; }
.sdb-plan-icon {
    width: 52px; height: 52px; border-radius: 14px;
    display: flex; align-items: center; justify-content: center;
    background: rgba(249,115,22,.12); flex-shrink: 0;
}
.sdb-plan-icon i { font-size: 1.5rem; color: #f97316; }
.sdb-plan-name { font-size: 1.15rem; font-weight: 800; line-height: 1.2; }
.sdb-status-pill {
    display: inline-flex; align-items: center;
    font-size: var(--r2k-text-70); font-weight: 700; letter-spacing: .05em; text-transform: uppercase;
    padding: .2rem .65rem; border-radius: 99px;
}
.sdb-plan-sub { font-size: var(--r2k-text-md); color: var(--bs-secondary-color, #6c757d); margin-top: .2rem; }
.sdb-metric-row { display: flex; flex-wrap: wrap; gap: .75rem; margin-top: 1.25rem; }
.sdb-metric {
    flex: 1 1 120px;
    background: var(--bs-body-bg, #f8f9fa);
    border: 1px solid var(--bs-border-color, #e8e8e8);
    border-radius: 10px; padding: .7rem 1rem;
}
.sdb-metric-label { font-size: var(--r2k-text-70); text-transform: uppercase; letter-spacing: .07em; color: var(--bs-secondary-color, #888); font-weight: 600; margin-bottom: .2rem; }
.sdb-metric-value { font-size: .95rem; font-weight: 700; }
.sdb-metric-value.danger { color: #dc2626; }
.sdb-progress-wrap { margin-top: 1.25rem; }
.sdb-progress-labels { display: flex; justify-content: space-between; margin-bottom: .4rem; }
.sdb-progress-labels span { font-size: var(--r2k-text-75); color: var(--bs-secondary-color, #888); }
.sdb-progress-track { height: 8px; border-radius: 99px; background: var(--bs-border-color, #e8e8e8); overflow: hidden; }
.sdb-progress-fill { height: 100%; border-radius: 99px; background: linear-gradient(90deg, #22c55e, #16a34a); width: 0%; transition: width .6s ease; }
.sdb-action-row { display: flex; flex-wrap: wrap; gap: .6rem; margin-top: 1.5rem; padding-top: 1.25rem; border-top: 1.5px solid var(--bs-border-color, #e8e8e8); }
.sdb-action-row .btn { font-size: var(--r2k-text-md); padding: .4rem .9rem; }

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
.sdb-billing-header h6 { font-size: var(--r2k-text-85); font-weight: 700; text-transform: uppercase; letter-spacing: .07em; color: var(--bs-secondary-color, #888); margin: 0; }
.sdb-table { width: 100%; border-collapse: collapse; }
.sdb-table th {
    font-size: var(--r2k-text-sm); text-transform: uppercase; letter-spacing: .06em;
    color: var(--bs-secondary-color, #888); font-weight: 600;
    padding: .6rem 1rem; border-bottom: 1.5px solid var(--bs-border-color, #e8e8e8);
    white-space: nowrap; background: var(--bs-body-bg, #fafafa);
}
.sdb-table td { padding: .75rem 1rem; font-size: .83rem; border-bottom: 1px solid var(--bs-border-color, #f0f0f0); vertical-align: middle; }
.sdb-table tr:last-child td { border-bottom: none; }
.sdb-table tr:hover td { background: var(--bs-body-bg, #fafafa); }
.sdb-type-pill { display: inline-block; font-size: var(--r2k-text-xs); font-weight: 700; text-transform: uppercase; letter-spacing: .05em; padding: .15rem .5rem; border-radius: 99px; }
.sdb-type-new      { background: #dbeafe; color: #1d4ed8; }
.sdb-type-renewal  { background: #dcfce7; color: #15803d; }
.sdb-type-upgrade  { background: #ede9fe; color: #6d28d9; }
.sdb-type-downgrade{ background: #fef9c3; color: #854d0e; }
.sdb-type-manual   { background: #f1f5f9; color: #475569; }
.sdb-type-trial    { background: #f1f5f9; color: #475569; }
.sdb-empty-state { padding: 3.5rem 1.5rem; text-align: center; }
.sdb-empty-icon { width: 60px; height: 60px; border-radius: 16px; background: rgba(105,108,255,.08); display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; }
.sdb-empty-icon i { font-size: 1.6rem; color: #696cff; }

/* Skeleton shimmer */
.sdb-skeleton {
    background: linear-gradient(90deg, var(--bs-border-color,#e8e8e8) 25%, var(--bs-body-bg,#f4f4f4) 50%, var(--bs-border-color,#e8e8e8) 75%);
    background-size: 200% 100%;
    animation: sdb-shimmer 1.4s infinite;
    border-radius: 4px;
    display: inline-block;
    height: 14px;
}
@keyframes sdb-shimmer { to { background-position: -200% 0; } }
</style>

                    <!-- Current Plan Card -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="sdb-plan-card">
                                <div class="sdb-plan-band" id="planBand"></div>
                                <div class="sdb-plan-body">

                                    <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
                                        <div class="d-flex align-items-start gap-3">
                                            <div class="sdb-plan-icon" id="planIcon">
                                                <i class="bx bx-crown"></i>
                                            </div>
                                            <div>
                                                <div class="d-flex align-items-center flex-wrap gap-2 mb-1">
                                                    <span class="sdb-plan-name" id="planName">
                                                        <span class="sdb-skeleton" style="width:130px;">&nbsp;</span>
                                                    </span>
                                                    <span class="sdb-status-pill" id="statusPill" style="display:none;"></span>
                                                </div>
                                                <div class="sdb-plan-sub" id="planSub">
                                                    <span class="sdb-skeleton" style="width:190px;">&nbsp;</span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="sdb-action-row" style="margin-top:0;padding-top:0;border-top:none;">
                                            <button class="btn btn-outline-secondary d-none" id="btnRenewPlan">
                                                <i class="bx bx-refresh me-1"></i>Renew
                                            </button>
                                            <button class="btn btn-outline-primary"
                                                data-bs-toggle="modal" data-bs-target="#changePlanModal">
                                                <i class="bx bx-transfer-alt me-1"></i>Change Plan
                                            </button>
                                            <a href="<?= site_url('subscription/plans') ?>" class="btn btn-primary">
                                                <i class="bx bx-list-check me-1"></i>Browse Plans
                                            </a>
                                        </div>
                                    </div>

                                    <div class="sdb-metric-row">
                                        <div class="sdb-metric">
                                            <div class="sdb-metric-label">Start Date</div>
                                            <div class="sdb-metric-value" id="metricStart">—</div>
                                        </div>
                                        <div class="sdb-metric">
                                            <div class="sdb-metric-label">End Date</div>
                                            <div class="sdb-metric-value" id="metricEnd">—</div>
                                        </div>
                                        <div class="sdb-metric">
                                            <div class="sdb-metric-label">Days Remaining</div>
                                            <div class="sdb-metric-value" id="metricDays">—</div>
                                        </div>
                                        <div class="sdb-metric">
                                            <div class="sdb-metric-label">Plan Duration</div>
                                            <div class="sdb-metric-value" id="metricDuration">—</div>
                                        </div>
                                    </div>

                                    <div class="sdb-progress-wrap d-none" id="progressWrap">
                                        <div class="sdb-progress-labels">
                                            <span>Plan usage</span>
                                            <span id="progressLabel"></span>
                                        </div>
                                        <div class="sdb-progress-track">
                                            <div class="sdb-progress-fill" id="progressFill"></div>
                                        </div>
                                    </div>

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
                                <div id="billingHistoryWrap">
                                    <div class="text-center py-4">
                                        <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                                    </div>
                                </div>
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
                            <option value="">-- Loading plans... --</option>
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
                    <div class="mb-3 d-none" id="payModeWrap">
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
