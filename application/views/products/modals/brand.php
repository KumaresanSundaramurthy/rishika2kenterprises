<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<!-- Brand Form Modal -->
<div class="modal fade" id="brandModal" tabindex="-1" aria-labelledby="brandModal" data-bs-backdrop="static" data-bs-keyboard="false" aria-hidden="true" aria-modal="true" role="dialog">
    <div class="modal-dialog modal-dialog-full-height modal-lg" id="brandModalDialog">
        <div class="modal-content modal-content-hidden h-100 d-flex flex-column">

        <?php $FormAttribute = array('id' => 'brandForm', 'name' => 'brandForm', 'class' => '', 'autocomplete' => 'off');
            echo form_open('products/addBrand', $FormAttribute); ?>

            <div class="modal-header bg-white border-bottom d-flex align-items-center justify-content-between px-3 py-2 modal-header-center-sticky trans-theme">
                <div class="d-flex align-items-center gap-3">
                    <div class="modal-doc-icon bg-info bg-opacity-10">
                        <i class="bx bx-purchase-tag text-info modal-doc-icon-inner"></i>
                    </div>
                    <div>
                        <h5 class="modal-title mb-0" id="BrandModalTitle">Create Brand</h5>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="submit" class="btn btn-sm btn-primary" id="BrandSaveButton"><i class="bx bx-check me-1"></i>Save</button>
                    <button type="button" class="btn btn-sm btn-outline-danger" data-bs-dismiss="modal" aria-label="Close"><i class="bx bx-x me-1"></i>Close</button>
                </div>
            </div>

            <input type="hidden" name="BrandUID" id="BrandUID" value="0" />

            <div class="d-none col-lg-12 px-5 mt-3 brandFormAlert" role="alert"></div>

            <div class="modal-body modal-body-scrollable flex-grow-1 overflow-auto">
                <div class="card-body p-2 mb-3">

                    <div class="card-header modal-header-border-bottom p-1 mb-3">
                        <h5 class="modal-title mb-0">Basic Details</h5>
                    </div>
                    <div class="row">
                        <div class="mb-3 col-sm-8">
                            <label class="form-label" for="BrandName">Name <span style="color:red">*</span></label>
                            <input type="text" class="form-control" id="BrandName" placeholder="Enter brand name" name="BrandName" maxlength="150" required />
                        </div>
                        <div class="mb-3 col-sm-4">
                            <label class="form-label" for="BrandCode">Code</label>
                            <input type="text" class="form-control" id="BrandCode" placeholder="e.g. NIKE" name="BrandCode" maxlength="50" />
                        </div>
                        <div class="mb-3 col-12">
                            <label for="BrandDescription" class="form-label">Description</label>
                            <textarea class="form-control" rows="3" name="Description" id="BrandDescription" placeholder="Description"></textarea>
                        </div>
                        <div class="mb-3 col-12">
                            <label class="form-label" style="font-size:.82rem;font-weight:600;color:#374151;">Images <span class="text-muted fw-normal">(max 3 · 3 MB total)</span></label>
                            <div id="brandAttachZone" class="prod-attach-zone" onclick="brandAttachTrigger(event)">
                                <div id="brandAttachEmpty" class="prod-attach-empty">
                                    <i class="bx bx-image-add" id="brandAttachIcon" style="font-size:2rem;color:#9ca3af;display:block;margin-bottom:6px;"></i>
                                    <div id="brandAttachLabel" style="font-size:.78rem;font-weight:600;color:#6b7280;">Drag &amp; drop images</div>
                                    <div id="brandAttachHint" style="font-size:.7rem;color:#9ca3af;margin-top:3px;">JPG, GIF or PNG · Max 3 · 3 MB total</div>
                                </div>
                            </div>
                            <div id="brandAttachList" class="prod-attach-list mt-2" style="display:none;"></div>
                            <input type="file" id="brandAttachInput" multiple accept="image/jpeg,image/png,image/gif" style="display:none;">
                            <input type="hidden" id="brandAttachDeleteUIDs" name="BrandAttachDeleteUIDs" value="">
                        </div>
                    </div>

                </div>
            </div>

        <?php echo form_close(); ?>

        </div>
    </div>
</div>
