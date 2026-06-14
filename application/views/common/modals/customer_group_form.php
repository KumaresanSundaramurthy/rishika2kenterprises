<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<!-- Customer Group Add / Edit Modal -->
<div class="modal fade" id="CustomerGroupFormModal" tabindex="-1"
     data-bs-backdrop="static" data-bs-keyboard="false" aria-hidden="true"
     style="padding:0!important;">
    <div class="modal-dialog modal-xl modal-dialog-scrollable"
         style="height:100vh;max-height:100vh;margin:0 auto;">
        <div class="modal-content h-100 d-flex flex-column">

            <div class="modal-header bg-white border-bottom d-flex align-items-center justify-content-between px-3 py-2 trans-theme">
                <div class="d-flex align-items-center gap-3">
                    <div class="modal-doc-icon bg-opacity-10" style="background:#f3e8ff;">
                        <i class="bx bxs-layer modal-doc-icon-inner" style="color:#9333ea;"></i>
                    </div>
                    <h5 class="modal-title mb-0" id="CGroupModalTitle">Customer Group</h5>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-sm btn-primary" id="CGroupSaveBtn">
                        <i class="bx bx-check me-1"></i><span id="CGroupSaveBtnLabel">Save</span>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger" data-bs-dismiss="modal">
                        <i class="bx bx-x me-1"></i>Close
                    </button>
                </div>
            </div>

            <div class="modal-body p-0 flex-grow-1 overflow-auto" id="CustomerGroupFormModalBody">
                <?php $this->load->view('common/modals/customer_group_form_body'); ?>
            </div>

        </div>
    </div>
</div>
