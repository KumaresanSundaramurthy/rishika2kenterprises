<?php defined('BASEPATH') or exit('No direct script access allowed');
/*
 * Shared Record Payment Modal
 *
 * Required variables (set by the calling view before load):
 *   $rpAccentColor  — e.g. '#0d6efd'  (invoice) | '#6f42c1' (purchase)
 *   $rpAccentBg     — e.g. '#e8f0fe'  (invoice) | '#f0ebff' (purchase)
 *   $rpPartyIcon    — e.g. 'bx-user'  (invoice) | 'bx-store' (purchase)
 *   $rpDocLabel     — e.g. 'Invoice'  (invoice) | 'Bill'     (purchase)
 *   $rpTotalIcon    — e.g. 'bx-receipt'(invoice)| 'bx-cart'  (purchase)
 *   $rpNumId        — e.g. 'rpInvNum' (invoice) | 'rpBillNum'(purchase)
 *   $rpDateId       — e.g. 'rpInvDate'(invoice) | 'rpBillDate'(purchase)
 */
$rpAccentColor = $rpAccentColor ?? '#0d6efd';
$rpAccentBg    = $rpAccentBg    ?? '#e8f0fe';
$rpPartyIcon   = $rpPartyIcon   ?? 'bx-user';
$rpDocLabel    = $rpDocLabel    ?? 'Invoice';
$rpTotalIcon   = $rpTotalIcon   ?? 'bx-receipt';
$rpNumId       = $rpNumId       ?? 'rpInvNum';
$rpDateId      = $rpDateId      ?? 'rpInvDate';
$rpBtnLabel    = $rpBtnLabel    ?? 'Record Payment';
?>
<div class="modal fade" id="recordPaymentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content rp-modal-content">

            <button type="button" class="btn-close rp-close-btn" data-bs-dismiss="modal" aria-label="Close"></button>

            <div class="modal-body p-0">

                <!-- Banner -->
                <div class="rp-banner" style="background:<?php echo $rpAccentBg; ?>;border-left-color:<?php echo $rpAccentColor; ?>;">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rp-banner-icon" style="background:<?php echo $rpAccentColor; ?>22;">
                            <i class="bx bx-money-withdraw" style="color:<?php echo $rpAccentColor; ?>;"></i>
                        </div>
                        <div>
                            <div class="rp-banner-title" style="color:<?php echo $rpAccentColor; ?>;">
                                <?php echo htmlspecialchars($rpBtnLabel); ?> &mdash; <span id="<?php echo $rpNumId; ?>">—</span>
                            </div>
                            <div class="rp-banner-meta">
                                <i class="bx <?php echo $rpPartyIcon; ?> me-1"></i><span id="rpPartyName">—</span>
                                <span class="rp-meta-sep">|</span>
                                <i class="bx bx-calendar me-1"></i><span id="<?php echo $rpDateId; ?>">—</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Scrollable body -->
                <div class="rp-scroll-body">

                <!-- Summary cards -->
                <div class="rp-summary-section">
                    <div class="rp-section-header">
                        <i class="bx bx-bar-chart-alt-2 rp-section-icon"></i>
                        <span class="rp-section-label">Payment Summary</span>
                    </div>
                    <div class="row g-2">
                        <div class="col-4">
                            <div class="rp-summary-card" style="border-left-color:<?php echo $rpAccentColor; ?>;">
                                <div class="rp-summary-card-label">
                                    <i class="bx <?php echo $rpTotalIcon; ?> me-1"></i><?php echo $rpDocLabel; ?> Total
                                </div>
                                <div class="rp-summary-card-value" style="color:<?php echo $rpAccentColor; ?>;" id="rpTotalCard">—</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="rp-summary-card rp-summary-card--paid">
                                <div class="rp-summary-card-label">
                                    <i class="bx bx-check-circle me-1"></i>Paid So Far
                                </div>
                                <div class="rp-summary-card-value rp-val-paid" id="rpPaidCard">—</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="rp-summary-card rp-summary-card--due">
                                <div class="rp-summary-card-label">
                                    <i class="bx bx-time me-1"></i>Balance Due
                                </div>
                                <div class="rp-summary-card-value rp-val-due" id="rpBalanceCard">—</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payment form -->
                <div class="rp-form-section">
                    <div class="rp-section-header">
                        <i class="bx bx-edit-alt rp-section-icon rp-section-icon--orange"></i>
                        <span class="rp-section-label rp-section-label--orange">Payment Details</span>
                    </div>
                    <div class="row g-3">

                        <div class="col-5">
                            <label class="rp-field-label"><span class="text-danger">*</span> Amount</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-white fw-semibold" id="rpCurrencySymbol">₹</span>
                                <input type="number" class="form-control" id="rpAmount" step="0.01" min="0.01" placeholder="0.00">
                            </div>
                        </div>

                        <div class="col-7">
                            <label class="rp-field-label">Payment Date</label>
                            <div class="input-group input-group-sm input-group-merge">
                                <span class="input-group-text bg-white"><i class="bx bx-calendar"></i></span>
                                <input type="text" class="form-control" id="rpPaymentDate" placeholder="Today" readonly>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="rp-field-label">Payment Type</label>
                            <div class="d-flex flex-wrap gap-2" id="rpPaymentTypes">
                                <div class="text-muted rp-loading"><i class="bx bx-loader-alt bx-spin"></i> Loading…</div>
                            </div>
                            <input type="hidden" id="rpPaymentTypeUID" value="">
                            <input type="hidden" id="rpIsCash" value="1">
                        </div>

                        <div class="col-12 d-none" id="rpBankRow">
                            <label class="rp-field-label">Bank Account <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm" id="rpBankAccount">
                                <option value="">— Select bank account —</option>
                            </select>
                        </div>

                        <div class="col-6">
                            <label class="rp-field-label">
                                Reference ID <span class="fw-normal text-muted">(Optional)</span>
                            </label>
                            <input type="text" class="form-control form-control-sm" id="rpReferenceNo"
                                placeholder="UTR, Cheque No, UPI Ref…" maxlength="100">
                        </div>

                        <div class="col-6">
                            <label class="rp-field-label">
                                Notes <span class="fw-normal text-muted">(Optional)</span>
                            </label>
                            <textarea class="form-control form-control-sm" id="rpNotes" rows="1"
                                    placeholder="Add a payment note…" maxlength="255"></textarea>
                        </div>

                        <!-- Attachments -->
                        <div class="col-12">
                            <label class="rp-field-label">
                                Attachments <span class="fw-normal text-muted">(max 3 files &bull; 3 MB each)</span>
                            </label>
                            <div id="rpAttachDropzone" class="comm-attach-dropzone dropzone needsclick dz-clickable">
                                <div class="dz-message needsclick">
                                    <i class="bx bx-cloud-upload comm-attach-icon"></i>
                                    <div class="comm-attach-hint">Drag &amp; drop or <span class="text-primary">browse</span></div>
                                    <div class="comm-attach-sub">PDF, JPG, PNG &bull; Max 3 files, 3 MB each</div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                </div><!-- /.rp-scroll-body -->

                <!-- Footer -->
                <div class="rp-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary btn-sm px-4" id="btnSubmitPayment">
                        <i class="bx bx-check me-1"></i> <?php echo htmlspecialchars($rpBtnLabel); ?>
                    </button>
                </div>

                <input type="hidden" id="rpTransUID" value="">

            </div>
        </div>
    </div>
</div>

<!-- ── Shared Payment Detail Modal ─────────────────────────────── -->
<!--
  Theme is driven by data-pdt-theme on the modal:
    data-pdt-theme="in"  → blue  (payments received)
    data-pdt-theme="out" → orange (payments out)
  Set by calling view before loading this partial.
-->
<?php
$pdtTheme       = $pdtTheme       ?? 'in';
$pdtPartyLabel  = $pdtPartyLabel  ?? 'Party';
$pdtLinkedLabel = $pdtLinkedLabel ?? 'Linked Document';
?>
<div class="modal fade" id="paymentDetailModal" tabindex="-1" aria-hidden="true"
     data-pdt-theme="<?php echo htmlspecialchars($pdtTheme); ?>">
    <div class="modal-dialog modal-dialog-centered pdt-dialog">
        <div class="modal-content border-0 shadow position-relative">

            <button type="button" class="btn-close pdt-close-btn" data-bs-dismiss="modal" aria-label="Close"></button>

            <!-- Banner -->
            <div class="pdt-banner">
                <div class="d-flex align-items-center gap-3">
                    <div class="pdt-banner-icon">
                        <i class="pdt-banner-icon-el bx <?php echo $pdtTheme === 'out' ? 'bx-money-withdraw' : 'bx-receipt'; ?> fs-4"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="fw-bold text-dark pdt-title" id="pdUniqueNumber">—</div>
                        <div class="text-muted pdt-date" id="pdDateLabel">—</div>
                    </div>
                    <div class="text-end">
                        <div class="fw-bold pdt-amount" id="pdAmount">—</div>
                        <div id="pdModeBadge" class="mt-1"></div>
                    </div>
                </div>
            </div>

            <div class="modal-body px-4 py-3">

                <!-- Party + Linked doc -->
                <div class="pdt-section">
                    <div class="row g-2">
                        <div class="col-6">
                            <div class="pdt-label"><?php echo htmlspecialchars($pdtPartyLabel); ?></div>
                            <div class="fw-semibold pdt-value" id="pdParty">—</div>
                            <div class="pdt-sub" id="pdPartyMobile"></div>
                        </div>
                        <div class="col-6">
                            <div class="pdt-label"><?php echo htmlspecialchars($pdtLinkedLabel); ?></div>
                            <div class="fw-semibold text-primary pdt-value" id="pdTransNumber">—</div>
                        </div>
                    </div>
                </div>

                <!-- Bank Details -->
                <div id="pdBankSection" class="pdt-section" style="display:none;">
                    <div class="pdt-label mb-2">
                        <i class="bx bx-building-house me-1"></i>Bank Details
                    </div>
                    <div class="row g-2">
                        <div class="col-7">
                            <div class="pdt-sub">Bank / Account Name</div>
                            <div class="fw-semibold pdt-value" id="pdBankName">—</div>
                        </div>
                        <div class="col-5">
                            <div class="pdt-sub">Account Number</div>
                            <div class="fw-semibold pdt-value pdt-mono" id="pdAccountNumber">—</div>
                        </div>
                        <div class="col-6" id="pdIfscWrap" style="display:none;">
                            <div class="pdt-sub">IFSC</div>
                            <div class="fw-semibold pdt-value" id="pdIfsc">—</div>
                        </div>
                        <div class="col-6" id="pdBranchWrap" style="display:none;">
                            <div class="pdt-sub">Branch</div>
                            <div class="fw-semibold pdt-value" id="pdBranch">—</div>
                        </div>
                    </div>
                </div>

                <!-- Reference / By / Notes -->
                <div class="row g-3">
                    <div class="col-6">
                        <div class="pdt-label">Reference No</div>
                        <div class="pdt-value" id="pdReference">—</div>
                    </div>
                    <div class="col-6">
                        <div class="pdt-label">Recorded By</div>
                        <div class="pdt-value" id="pdCreatedBy">—</div>
                    </div>
                    <div class="col-12" id="pdNotesWrap" style="display:none;">
                        <div class="pdt-label">Notes</div>
                        <div class="pdt-notes" id="pdNotes"></div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- Payment Details Panel -->
<div id="payDetailPanel" class="pay-detail-panel">
    <div class="pay-detail-panel__header">
        <div class="d-flex justify-content-between align-items-center">
            <span class="pay-detail-panel__title">
                <i class="bx bx-credit-card me-1 text-primary"></i>
                <span id="payPanelTitle">Payments</span>
            </span>
            <button type="button" id="payPanelClose" class="pay-detail-panel__close">
                <i class="bx bx-x"></i>
            </button>
        </div>
    </div>
    <div id="payDetailBody" class="pay-detail-panel__body"></div>
</div>
