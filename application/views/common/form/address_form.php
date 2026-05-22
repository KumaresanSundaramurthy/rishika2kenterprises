<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="modal fade modal-select2-search" id="addEditAddressModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false"
     aria-hidden="true" aria-modal="true" role="dialog" style="background: rgba(0,0,0,0.55); backdrop-filter: blur(2px);">
    <div class="modal-dialog modal-dialog-top modal-md">
        <div class="modal-content">

        <div id="AddEditAddressForm">

            <div class="modal-header bg-white border-bottom d-flex align-items-center justify-content-between px-3 py-2 trans-theme">
                <div class="d-flex align-items-center gap-3">
                    <div class="modal-doc-icon bg-primary bg-opacity-10">
                        <i class="bx bx-map-pin text-primary modal-doc-icon-inner"></i>
                    </div>
                    <div>
                        <h5 class="modal-title mb-0" id="addrModalTitle">Billing Address</h5>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-sm btn-primary" id="AddrSaveBtn">
                        <i class="bx bx-check me-1"></i>Save
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger" data-bs-dismiss="modal" aria-label="Close">
                        <i class="bx bx-x me-1"></i>Close
                    </button>
                </div>
            </div>

            <input type="hidden" id="AddrType" value="1" />
            <input type="hidden" id="AddrUID"  value="0" />

            <div class="modal-body p-4" style="position: relative; overflow: hidden;">

                <!-- Watermark icon -->
                <div style="position:absolute; bottom:-30px; right:-20px; pointer-events:none; z-index:0; line-height:1;">
                    <i class="bx bx-map-pin" style="font-size:190px; color:#4154f1; opacity:0.07;"></i>
                </div>

                <!-- Form fields (above watermark) -->
                <div style="position: relative; z-index: 1;">
                    <div class="mb-3">
                        <label for="ModalAddrLine1" class="form-label">Address Line 1 <span class="text-danger">*</span></label>
                        <input class="form-control" type="text" id="ModalAddrLine1" maxlength="100" placeholder="Address Line 1" autocomplete="off" />
                    </div>
                    <div class="mb-3">
                        <label for="ModalAddrLine2" class="form-label">Address Line 2</label>
                        <input class="form-control" type="text" id="ModalAddrLine2" maxlength="100" placeholder="Address Line 2 (optional)" autocomplete="off" />
                    </div>
                    <div class="mb-3">
                        <label for="ModalAddrPincode" class="form-label">Pincode <span class="text-danger">*</span></label>
                        <input class="form-control" type="text" id="ModalAddrPincode" maxlength="10" placeholder="Pincode" autocomplete="off" />
                    </div>
                    <div class="row">
                        <div class="mb-3 col-md-6">
                            <label for="ModalAddrState" class="form-label">State</label>
                            <select class="select2 form-select" id="ModalAddrState">
                                <option value="">-- Select State --</option>
                            </select>
                        </div>
                        <div class="mb-0 col-md-6">
                            <label for="ModalAddrCity" class="form-label">City</label>
                            <select class="select2 form-select" id="ModalAddrCity">
                                <option value="">-- Select City --</option>
                            </select>
                        </div>
                    </div>
                </div>

            </div>

        </div><!-- /#AddEditAddressForm -->

        </div>
    </div>
</div>
