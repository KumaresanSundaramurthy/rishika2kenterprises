<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<!-- Size Form Modal -->
<div class="modal fade" id="sizeModal" tabindex="-1" aria-labelledby="sizeModal" data-bs-backdrop="static" data-bs-keyboard="false" aria-hidden="true" aria-modal="true" role="dialog">
    <div class="modal-dialog modal-dialog-full-height modal-lg" id="sizeModalDialog">
        <div class="modal-content modal-content-hidden h-100 d-flex flex-column">

        <?php $FormAttribute = array('id' => 'sizeForm', 'name' => 'sizeForm', 'class' => '', 'autocomplete' => 'off');
            echo form_open('products/addSizeDetails', $FormAttribute); ?>

            <div class="modal-header bg-white border-bottom d-flex align-items-center justify-content-between px-3 py-2 modal-header-center-sticky trans-theme">
                <div class="d-flex align-items-center gap-3">
                    <div class="modal-doc-icon bg-warning bg-opacity-10">
                        <i class="bx bx-ruler text-warning modal-doc-icon-inner"></i>
                    </div>
                    <div>
                        <h5 class="modal-title mb-0" id="SizeModalTitle">Create Size</h5>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="submit" class="btn btn-sm btn-primary" id="SizeSaveButton"><i class="bx bx-check me-1"></i>Save</button>
                    <button type="button" class="btn btn-sm btn-outline-danger" data-bs-dismiss="modal" aria-label="Close"><i class="bx bx-x me-1"></i>Close</button>
                </div>
            </div>

            <input type="hidden" name="SizeUID" id="SizeUID" value="0" />

            <div class="modal-body modal-body-scrollable flex-grow-1 overflow-auto">
                <div class="card-body p-2 mb-3">

                    <!-- Basic Details -->
                    <div class="card-header modal-header-border-bottom p-1 mb-3">
                        <h5 class="modal-title mb-0">Basic Details</h5>
                    </div>
                    <div class="row">
                        <div class="mb-3 col-sm-8">
                            <label class="form-label" for="SizeName">Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="SizeName" placeholder="e.g. Small, Medium, Large, 10cm × 5cm" name="SizeName" maxlength="100" required />
                        </div>
                        <div class="mb-3 col-sm-4">
                            <label class="form-label" for="SizeCode">Code</label>
                            <input type="text" class="form-control" id="SizeCode" placeholder="e.g. SML" name="SizeCode" maxlength="50" />
                        </div>
                        <div class="mb-3 col-12">
                            <label class="form-label" for="SizeDescription">Description</label>
                            <textarea class="form-control" rows="2" name="Description" id="SizeDescription" placeholder="Description"></textarea>
                        </div>
                    </div>

                    <!-- Dimensions -->
                    <div class="card-header modal-header-border-bottom p-1 mb-3 mt-2">
                        <h5 class="modal-title mb-0">Dimensions</h5>
                    </div>
                    <div class="row">
                        <div class="mb-3 col-sm-4">
                            <label class="form-label" for="SizeLength">Length</label>
                            <input type="number" class="form-control" id="SizeLength" placeholder="0.00" name="Length" min="0" step="0.01" />
                        </div>
                        <div class="mb-3 col-sm-4">
                            <label class="form-label" for="SizeWidth">Width</label>
                            <input type="number" class="form-control" id="SizeWidth" placeholder="0.00" name="Width" min="0" step="0.01" />
                        </div>
                        <div class="mb-3 col-sm-4">
                            <label class="form-label" for="SizeHeight">Height</label>
                            <input type="number" class="form-control" id="SizeHeight" placeholder="0.00" name="Height" min="0" step="0.01" />
                        </div>
                        <div class="mb-3 col-sm-4">
                            <label class="form-label" for="SizeDepth">Depth</label>
                            <input type="number" class="form-control" id="SizeDepth" placeholder="0.00" name="Depth" min="0" step="0.01" />
                        </div>
                        <div class="mb-3 col-sm-4">
                            <label class="form-label" for="SizeDiameter">Diameter</label>
                            <input type="number" class="form-control" id="SizeDiameter" placeholder="0.00" name="Diameter" min="0" step="0.01" />
                        </div>
                        <div class="mb-3 col-sm-4">
                            <label class="form-label" for="SizeThickness">Thickness</label>
                            <input type="number" class="form-control" id="SizeThickness" placeholder="0.00" name="Thickness" min="0" step="0.01" />
                        </div>
                        <div class="mb-3 col-sm-4">
                            <label class="form-label" for="SizeDimensionUOM">Dimension UOM</label>
                            <select class="form-select" id="SizeDimensionUOM" name="DimensionUOM">
                                <option value="">— select —</option>
                                <option value="mm">mm</option>
                                <option value="cm">cm</option>
                                <option value="m">m</option>
                                <option value="in">in</option>
                                <option value="ft">ft</option>
                            </select>
                        </div>
                    </div>

                    <!-- Weight -->
                    <div class="card-header modal-header-border-bottom p-1 mb-3 mt-2">
                        <h5 class="modal-title mb-0">Weight</h5>
                    </div>
                    <div class="row">
                        <div class="mb-3 col-sm-6">
                            <label class="form-label" for="SizeWeight">Weight</label>
                            <input type="number" class="form-control" id="SizeWeight" placeholder="0.000" name="Weight" min="0" step="0.001" />
                        </div>
                        <div class="mb-3 col-sm-6">
                            <label class="form-label" for="SizeWeightUOM">Weight UOM</label>
                            <select class="form-select" id="SizeWeightUOM" name="WeightUOM">
                                <option value="">— select —</option>
                                <option value="kg">kg</option>
                                <option value="g">g</option>
                                <option value="lb">lb</option>
                                <option value="oz">oz</option>
                            </select>
                        </div>
                    </div>

                </div>
            </div>

        <?php echo form_close(); ?>

        </div>
    </div>
</div>
