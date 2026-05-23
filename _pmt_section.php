                            <div id="emPaymentSection" style="display:none;">
                                <div class="card-body">

                                    <!-- Payment Date -->
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold" style="font-size:.85rem;">Payment Date</label>
                                        <input type="date" class="form-control" id="emPmtDate">
                                    </div>

                                    <!-- Payment Type -->
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold" style="font-size:.85rem;">Payment Type</label>
                                        <div class="d-flex flex-wrap gap-2" id="emPmtTypePills">
                                            <?php if (!empty($paymentTypes)): ?>
                                                <?php foreach ($paymentTypes as $pt): ?>
                                                    <button type="button" class="btn btn-sm pmt-pill btn-outline-secondary"
                                                            data-uid="<?php echo (int)$pt->PaymentTypeUID; ?>"
                                                            data-name="<?php echo htmlspecialchars($pt->PaymentTypeName); ?>">
                                                        <?php echo htmlspecialchars($pt->PaymentTypeName); ?>
                                                    </button>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <?php foreach (['UPI','Cash','Card','Net Banking','Cheque','EMI'] as $i => $pn): ?>
                                                    <button type="button" class="btn btn-sm pmt-pill btn-outline-secondary"
                                                            data-uid="<?php echo $i + 1; ?>"
                                                            data-name="<?php echo $pn; ?>">
                                                        <?php echo $pn; ?>
                                                    </button>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>
                                        <input type="hidden" id="emPmtTypeUID" value="">
                                    </div>

                                    <!-- Bank Account (hidden for Cash) -->
                                    <div class="mb-3" id="emBankSection">
                                        <label class="form-label fw-semibold" style="font-size:.85rem;">Bank / Account</label>
                                        <select class="form-select" id="emBankUID">
                                            <option value="">None / Not Applicable</option>
                                            <?php foreach ($bankAccounts as $ba): ?>
                                                <option value="<?php echo (int)$ba->BankAccountUID; ?>">
                                                    <?php echo htmlspecialchars($ba->AccountName); ?>
                                                    <?php echo !empty($ba->BankName) ? ' — ' . htmlspecialchars($ba->BankName) : ''; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <!-- Payment Notes -->
                                    <div class="mb-0">
                                        <label class="form-label fw-semibold" style="font-size:.85rem;">Payment Notes</label>
                                        <textarea class="form-control" id="emPmtNotes" rows="2"
                                                  placeholder="Reference, cheque no., UTR..."></textarea>
                                    </div>

                                </div>
                            </div>