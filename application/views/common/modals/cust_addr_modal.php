<div class="modal fade" id="custAddrModal" tabindex="-1" aria-labelledby="custAddrModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header py-3">
                <h5 class="modal-title" id="custAddrModalLabel"><i class="bx bx-map-pin me-2 text-primary"></i>Edit Billing Address</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label" for="camLine1">Line 1 <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="camLine1" placeholder="Street / Door No.">
                </div>
                <div class="mb-3">
                    <label class="form-label" for="camLine2">Line 2</label>
                    <input type="text" class="form-control" id="camLine2" placeholder="Area / Landmark">
                </div>
                <div class="row g-3">
                    <div class="col-6">
                        <label class="form-label" for="camCity">City</label>
                        <input type="text" class="form-control" id="camCity" placeholder="City">
                    </div>
                    <div class="col-6">
                        <label class="form-label" for="camState">State</label>
                        <input type="text" class="form-control" id="camState" placeholder="State">
                    </div>
                </div>
                <div class="mt-3">
                    <label class="form-label" for="camPincode">Pincode</label>
                    <input type="text" class="form-control" id="camPincode" placeholder="Pincode" inputmode="numeric" maxlength="10">
                </div>
                <div id="camError" class="text-danger transtext-small mt-2 d-none"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="btnSaveCustAddr"><i class="bx bx-save me-1"></i>Save Address</button>
            </div>
        </div>
    </div>
</div>
