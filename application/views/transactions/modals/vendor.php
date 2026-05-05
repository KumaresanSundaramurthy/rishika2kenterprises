<!-- Add Vendor Modal — used from Purchase Bill forms -->
<div class="modal fade" id="transVendorModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" aria-hidden="true" aria-modal="true" role="dialog">
    <div class="modal-dialog modal-dialog-full-height modal-xl">
        <div class="modal-content h-100 d-flex flex-column">

            <div class="modal-header trans-theme d-flex justify-content-between align-items-center p-3">
                <h5 class="modal-title">Create Vendor</h5>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-primary" id="saveTransVendorBtn">Save</button>
                    <button type="button" class="btn btn-label-danger" data-bs-dismiss="modal">Close</button>
                </div>
            </div>

            <!-- Form body loaded dynamically via AJAX -->
            <div class="modal-body modal-body-scrollable flex-grow-1 overflow-auto p-0" id="transVendorModalBody">
                <div class="d-flex justify-content-center align-items-center py-5">
                    <span class="spinner-border text-primary"></span>
                </div>
            </div>

        </div>
    </div>
</div>
