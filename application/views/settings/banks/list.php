<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php if (empty($DataLists)): ?>
    <div class="card text-center py-5 border-0">
        <div class="card-body">
            <i class="bx bx-bank display-4 text-muted mb-3 d-block"></i>
            <h5 class="text-muted">No bank accounts added yet</h5>
            <p class="text-muted small">Add your bank account details to use them in transactions and generate payment QR codes.</p>
            <button class="btn btn-primary mt-2" id="btnAddBankEmpty">
                <i class="bx bx-plus me-1"></i>Add Bank Account
            </button>
        </div>
    </div>
<?php else: ?>
    <div class="row g-3 p-3">
        <?php foreach ($DataLists as $bank):
            $isDefault = (int)($bank->IsDefault ?? 0) === 1;
            $isCash    = (int)($bank->IsCash ?? 0) === 1;
            $maskLen   = max(0, strlen($bank->AccountNumber ?? '') - 4);
            $maskedAccNo = str_repeat('•', $maskLen) . substr($bank->AccountNumber ?? '', -4);
        ?>
        <div class="col-xl-4 col-md-6" id="bankCard_<?php echo (int)$bank->BankAccountUID; ?>">

            <?php if ($isCash): ?>
            <!-- ── Cash Card ── -->
            <div class="card h-100 <?php echo $isDefault ? 'border-success' : ''; ?>" style="<?php echo $isDefault ? 'border-width:2px;' : ''; ?>">
                <div class="card-body pb-2">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="d-flex align-items-center gap-2">
                            <div style="background:linear-gradient(135deg,#ff9f43,#f7b731);border-radius:8px;width:36px;height:36px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <i class="bx bx-money text-white" style="font-size:1.1rem;"></i>
                            </div>
                            <span class="fw-bold" style="font-size:1rem;">Cash</span>
                        </div>
                        <?php if ($isDefault): ?>
                        <span class="badge bg-label-success" style="font-size:0.7rem;">
                            <i class="bx bx-check-circle me-1"></i>Default
                        </span>
                        <?php endif; ?>
                    </div>
                    <p class="text-muted mb-0" style="font-size:0.78rem;">Cash payments collected directly from customers or paid to vendors.</p>
                </div>
                <div class="card-footer bg-transparent py-2 px-3">
                    <?php if (!$isDefault): ?>
                    <button class="btn btn-sm btn-link text-muted p-0 setDefaultBankBtn"
                            data-uid="<?php echo (int)$bank->BankAccountUID; ?>"
                            title="Set as default">
                        <i class="bx bx-radio-circle me-1"></i>
                        <span style="font-size:0.78rem;">Set as Default</span>
                    </button>
                    <?php else: ?>
                    <span class="text-success small"><i class="bx bx-radio-circle-marked me-1"></i>Default</span>
                    <?php endif; ?>
                </div>
            </div>

            <?php else: ?>
            <!-- ── Bank Card ── -->
            <div class="card h-100 <?php echo $isDefault ? 'border-primary' : ''; ?>" style="<?php echo $isDefault ? 'border-width:2px;' : ''; ?>">
                <div class="card-body pb-2">

                    <div class="d-flex align-items-start justify-content-between mb-3">
                        <div class="d-flex align-items-center gap-2">
                            <div style="background:linear-gradient(135deg,#696cff,#4a4fc4);border-radius:8px;width:36px;height:36px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <i class="bx bx-bank text-white" style="font-size:1.1rem;"></i>
                            </div>
                            <div>
                                <div class="fw-bold text-dark" style="font-size:0.9rem;"><?php echo htmlspecialchars($bank->BankName ?? '—'); ?></div>
                                <?php if (!empty($bank->BranchName)): ?>
                                <div class="text-muted" style="font-size:0.75rem;"><?php echo htmlspecialchars($bank->BranchName); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if ($isDefault): ?>
                        <span class="badge bg-label-primary" style="font-size:0.7rem;">
                            <i class="bx bx-check-circle me-1"></i>Default
                        </span>
                        <?php endif; ?>
                    </div>

                    <div class="mb-2">
                        <div class="text-muted small">Account Holder</div>
                        <div class="fw-semibold" style="font-size:0.875rem;"><?php echo htmlspecialchars($bank->AccountName ?? '—'); ?></div>
                    </div>

                    <div class="mb-2">
                        <div class="text-muted small">Account Number</div>
                        <div class="fw-semibold font-monospace" style="font-size:0.875rem;letter-spacing:0.05em;"><?php echo htmlspecialchars($maskedAccNo); ?></div>
                    </div>

                    <?php if (!empty($bank->IFSC)): ?>
                    <div class="mb-2">
                        <div class="text-muted small">IFSC Code</div>
                        <div class="fw-semibold" style="font-size:0.875rem;"><?php echo htmlspecialchars($bank->IFSC); ?></div>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($bank->UPIId)): ?>
                    <div class="mb-2">
                        <div class="text-muted small">UPI ID</div>
                        <div class="fw-semibold text-success" style="font-size:0.8rem;">
                            <i class="bx bx-qr-scan me-1"></i><?php echo htmlspecialchars($bank->UPIId); ?>
                        </div>
                    </div>
                    <?php endif; ?>

                </div>

                <div class="card-footer bg-transparent py-2 px-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <?php if (!$isDefault): ?>
                            <button class="btn btn-sm btn-link text-muted p-0 setDefaultBankBtn"
                                    data-uid="<?php echo (int)$bank->BankAccountUID; ?>"
                                    title="Set as default">
                                <i class="bx bx-radio-circle me-1"></i>
                                <span style="font-size:0.78rem;">Set as Default</span>
                            </button>
                            <?php else: ?>
                            <span class="text-primary small"><i class="bx bx-radio-circle-marked me-1"></i>Default</span>
                            <?php endif; ?>
                        </div>
                        <div class="d-flex gap-1">
                            <button class="btn btn-sm btn-icon text-primary editBankBtn"
                                    data-uid="<?php echo (int)$bank->BankAccountUID; ?>"
                                    title="Edit">
                                <i class="bx bx-edit"></i>
                            </button>
                            <?php if (!$isDefault): ?>
                            <button class="btn btn-sm btn-icon text-danger deleteBankBtn"
                                    data-uid="<?php echo (int)$bank->BankAccountUID; ?>"
                                    data-name="<?php echo htmlspecialchars(($bank->BankName ?? '') . ' (' . ($bank->AccountName ?? '') . ')'); ?>"
                                    title="Delete">
                                <i class="bx bx-trash"></i>
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

            </div>
            <?php endif; ?>

        </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
