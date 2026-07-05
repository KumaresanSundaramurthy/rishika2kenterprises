<?php
/**
 * "View All Charges" modal + Add/Edit Charge modal — shared across all transaction forms.
 * Uses the JS global _transAdditionalCharges (already loaded from Upstash).
 */
defined('BASEPATH') or exit('No direct script access allowed');
?>
<script>var _transAcNextSortOrder = <?php echo (int)($AcNextSortOrder ?? 1); ?>;</script>

<!-- ══════════════════════════════════════════════════════════════
     VIEW ALL CHARGES MODAL
══════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="viewAllChargesModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:620px;">
        <div class="modal-content">

            <div class="modal-header p-0">
                <div style="width:100%;padding:14px 20px;border-left:4px solid #0d6efd;background:#e7f1ff;">
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;">
                        <div style="display:flex;align-items:center;gap:12px;">
                            <div style="background:rgba(13,110,253,.13);border-radius:10px;padding:9px 11px;flex-shrink:0;">
                                <i class="bx bx-receipt" style="font-size:1.6rem;color:#0d6efd;display:block;"></i>
                            </div>
                            <div>
                                <div style="font-size:1.05rem;font-weight:800;color:#0d6efd;">All Additional Charges</div>
                                <div style="font-size:.75rem;color:#6c757d;margin-top:3px;">
                                    Highlighted rows are already in this transaction
                                </div>
                            </div>
                        </div>
                        <button type="button" data-bs-dismiss="modal" aria-label="Close"
                            style="background:rgba(255,255,255,.85);border:none;border-radius:50%;width:28px;height:28px;display:inline-flex;align-items:center;justify-content:center;cursor:pointer;box-shadow:0 1px 4px rgba(0,0,0,.15);padding:0;flex-shrink:0;">
                            <i class="bx bx-x" style="font-size:1.2rem;color:#555;line-height:1;"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="modal-body p-0">
                <table class="table table-hover mb-0" id="viewAllChargesTable">
                    <thead class="r2k-thead">
                        <tr>
                            <th style="width:42%;">Charge</th>
                            <th style="width:18%;">Default Tax</th>
                            <th class="text-center" style="width:20%;">Show by Default</th>
                            <th class="text-center" style="width:20%;">Action</th>
                        </tr>
                    </thead>
                    <tbody id="viewAllChargesBody">
                        <!-- Populated by additional_charges.js on modal open -->
                    </tbody>
                </table>
            </div>

            <div class="modal-footer py-2" style="background:#f8f9fa;border-top:1px solid #dee2e6;">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>

        </div>
    </div>
</div>
<!-- / VIEW ALL CHARGES MODAL -->

<?php $this->load->view('common/partials/additional_charge_form_modal'); ?>
