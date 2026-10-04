<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="layout-wrapper layout-horizontal layout-content-navbar">
    <div class="layout-container">
        <?php $this->load->view('common/menu_view'); ?>
        <div class="layout-page">
            <div class="content-wrapper apex-content">
                <?php $this->load->view('common/apex/page_header', [
                    'pageTitle'       => 'Complete Payment',
                    'pageDescription' => 'Review your order and pay securely via Razorpay.',
                    'pageBackUrl'     => site_url('subscription/dashboard'),
                ]); ?>

                <div class="container-xxl flex-grow-1 container-p-y pt-2">
                    <div class="row justify-content-center">
                        <div class="col-12 col-md-8 col-lg-5">

                            <?php
                            $_tz     = $JwtData->Org->OrgTimezone ?? 'UTC';
                            $_due    = viewPageDateTimeFormat($order->DueDate ?? null, $_tz, 1);
                            $_net    = (float)($order->NetAmount         ?? 0);
                            $_tax    = (float)($order->TaxAmount         ?? 0);
                            $_taxable= round($_net - $_tax, 2);
                            $_cur    = $JwtData->GenSettings->CurrencySymbol ?? '₹';
                            $rType   = strtolower($order->RenewalType ?? 'renewal');
                            $tClass  = 'sdb-type-' . ($rType ?: 'renewal');
                            ?>

                            <!-- Order summary card -->
                            <div class="card mb-4" style="border-radius:16px; box-shadow:0 2px 12px rgba(0,0,0,.06);">
                                <div class="card-body p-4">

                                    <div class="d-flex align-items-center gap-3 mb-4">
                                        <div style="width:48px;height:48px;border-radius:12px;background:rgba(105,108,255,.1);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                            <i class="bx bx-receipt fs-4" style="color:#696cff;"></i>
                                        </div>
                                        <div>
                                            <div class="fw-bold" style="font-size:1rem;"><?= htmlspecialchars($order->PlanName ?? 'Plan') ?></div>
                                            <div class="text-muted" style="font-size:.8rem;">
                                                <?= htmlspecialchars($order->BillingCycle ?? '') ?>
                                                &nbsp;<span class="sdb-type-pill <?= $tClass ?>"><?= htmlspecialchars($order->RenewalType ?? '') ?></span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="d-flex flex-column gap-2 mb-4" style="font-size:.87rem;">
                                        <div class="d-flex justify-content-between">
                                            <span class="text-muted">Taxable Amount</span>
                                            <span class="font-monospace"><?= $_cur ?><?= number_format($_taxable, 2) ?></span>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <span class="text-muted">GST (18%)</span>
                                            <span class="font-monospace"><?= $_cur ?><?= number_format($_tax, 2) ?></span>
                                        </div>
                                        <hr class="my-1">
                                        <div class="d-flex justify-content-between fw-bold" style="font-size:1rem;">
                                            <span>Total</span>
                                            <span class="font-monospace"><?= $_cur ?><?= number_format($_net, 2) ?></span>
                                        </div>
                                        <div class="d-flex justify-content-between" style="font-size:.8rem;">
                                            <span class="text-muted">Due Date</span>
                                            <span><?= $_due->formatted ?? '—' ?></span>
                                        </div>
                                    </div>

                                    <button class="btn btn-primary w-100" id="btnPayNow" style="font-size:.9rem;padding:.65rem;">
                                        <span id="payBtnText"><i class="bx bx-credit-card me-1"></i>Pay <?= $_cur ?><?= number_format($_net, 2) ?> with Razorpay</span>
                                        <span class="spinner-border spinner-border-sm d-none ms-1" id="paySpinner"></span>
                                    </button>

                                    <p class="text-center text-muted mt-3" style="font-size:.75rem;">
                                        <i class="bx bx-lock-alt me-1"></i>Secured by Razorpay &mdash; your payment details are never stored here.
                                    </p>

                                </div>
                            </div>

                            <div class="text-center">
                                <a href="<?= site_url('subscription/dashboard') ?>" class="text-muted" style="font-size:.82rem;">
                                    <i class="bx bx-arrow-back me-1"></i>Back to Dashboard
                                </a>
                            </div>

                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
