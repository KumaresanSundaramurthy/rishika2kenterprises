<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<!-- Remove from Cart confirmation modal — populated by transactions.js -->
<div class="modal fade" id="removeCartModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:400px;">
        <div class="modal-content" style="border:none;border-radius:14px;overflow:hidden;">
            <div class="modal-body p-4">

                <!-- Header -->
                <div class="d-flex align-items-start justify-content-between mb-3">
                    <div class="d-flex align-items-center gap-2">
                        <span style="width:32px;height:32px;background:#fee2e2;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i class="bx bx-trash" style="color:#dc2626;font-size:1rem;"></i>
                        </span>
                        <div>
                            <div style="font-weight:700;font-size:.95rem;color:#111827;">Remove from Cart</div>
                            <div style="font-size:.75rem;color:#9ca3af;">This action cannot be undone.</div>
                        </div>
                    </div>
                    <!-- <button type="button" class="btn-close" data-bs-dismiss="modal" style="font-size:.7rem;"></button> -->
                </div>

                <!-- Product info card -->
                <div style="background:#f9fafb;border-radius:10px;padding:14px 16px;margin-bottom:14px;">
                    <div id="rcm-name" style="font-weight:700;font-size:1rem;color:#111827;margin-bottom:12px;"></div>
                    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;">
                        <div>
                            <div style="font-size:.65rem;color:#9ca3af;text-transform:uppercase;letter-spacing:.4px;margin-bottom:2px;">Qty</div>
                            <div id="rcm-qty" style="font-weight:600;font-size:.88rem;color:#374151;"></div>
                        </div>
                        <div>
                            <div style="font-size:.65rem;color:#9ca3af;text-transform:uppercase;letter-spacing:.4px;margin-bottom:2px;">Unit Price</div>
                            <div id="rcm-price" style="font-weight:600;font-size:.88rem;color:#374151;"></div>
                        </div>
                        <div>
                            <div style="font-size:.65rem;color:#9ca3af;text-transform:uppercase;letter-spacing:.4px;margin-bottom:2px;">Tax</div>
                            <div id="rcm-tax" style="font-weight:600;font-size:.88rem;color:#374151;"></div>
                        </div>
                    </div>
                </div>

                <!-- Net Amount highlight -->
                <div style="background:#fff7ed;border-left:3px solid #fb923c;border-radius:0 8px 8px 0;padding:10px 14px;margin-bottom:20px;">
                    <div style="font-size:.68rem;color:#9a3412;font-weight:600;text-transform:uppercase;letter-spacing:.3px;margin-bottom:2px;">Net Amount</div>
                    <div id="rcm-net" style="font-weight:800;font-size:1.15rem;color:#dc2626;"></div>
                </div>

                <!-- Buttons -->
                <div class="d-flex gap-2 justify-content-end">
                    <button type="button" class="btn btn-outline-secondary btn-sm px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger btn-sm px-4" id="rcm-confirm">
                        <i class="bx bx-trash me-1"></i>Remove
                    </button>
                </div>

            </div>
        </div>
    </div>
</div>
