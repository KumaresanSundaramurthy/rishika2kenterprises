<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<!-- Customer Add / Edit / Clone Modal — shared across all pages -->
<div class="modal fade" id="CustomerFormModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" aria-hidden="true" style="padding:0!important;">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" style="height:100vh;max-height:100vh;margin:0 auto;">
        <div class="modal-content h-100 d-flex flex-column">

            <div class="modal-header bg-white border-bottom d-flex align-items-center justify-content-between px-3 py-2 trans-theme">
                <div class="d-flex align-items-center gap-3">
                    <div class="modal-doc-icon bg-primary bg-opacity-10">
                        <i class="bx bx-user text-primary modal-doc-icon-inner"></i>
                    </div>
                    <div>
                        <h5 class="modal-title mb-0" id="CustomerFormModalTitle">Customer</h5>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-sm btn-primary" id="CustomerFormSaveBtn">
                        <i class="bx bx-check me-1"></i>Save
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger" data-bs-dismiss="modal">
                        <i class="bx bx-x me-1"></i>Close
                    </button>
                </div>
            </div>

            <!-- Body pre-rendered server-side — instant open, zero AJAX -->
            <div class="modal-body p-0 flex-grow-1 overflow-auto" id="CustomerFormModalBody">
                <?php $this->load->view('common/modals/customer_form_body'); ?>
            </div>

        </div>
    </div>
</div>

<?php $this->load->view('common/form/bank_details'); ?>
<?php $this->load->view('common/form/address_form'); ?>
