<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="modal fade" id="priceListModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" aria-hidden="true" aria-modal="true" role="dialog">
    <div class="modal-dialog modal-dialog-full-height modal-xl">
        <div class="modal-content modal-content-hidden h-100 d-flex flex-column">

            <!-- ── Header ───────────────────────────────────────────────── -->
            <div class="modal-header bg-white border-bottom d-flex align-items-center justify-content-between px-3 py-2 modal-header-center-sticky trans-theme">
                <div class="d-flex align-items-center gap-3">
                    <div class="modal-doc-icon bg-primary bg-opacity-10">
                        <i class="bx bx-purchase-tag text-primary modal-doc-icon-inner"></i>
                    </div>
                    <h5 class="modal-title mb-0" id="PLModalTitle">Create Price List</h5>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-sm btn-primary" onclick="savePriceListForm()">
                        <i class="bx bx-check me-1"></i>Save
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger" data-bs-dismiss="modal">
                        <i class="bx bx-x me-1"></i>Close
                    </button>
                </div>
            </div>

            <input type="hidden" id="PLUID" value="0">

            <div class="modal-body modal-body-scrollable flex-grow-1 overflow-auto">
                <div class="card-body p-2 mb-3">

                    <!-- ── 1. Basic Details ──────────────────────────────── -->
                    <div class="card-header modal-header-border-bottom p-1 mb-3">
                        <h5 class="modal-title mb-0">Basic Details</h5>
                    </div>
                    <div class="row">
                        <div class="mb-3 col-md-6">
                            <label class="form-label" for="PLName">Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="PLName"
                                placeholder="e.g. Standard Rate Card 2026, Diwali Scheme" maxlength="150">
                        </div>
                        <div class="mb-3 col-md-3">
                            <label class="form-label" for="PLStatus">Status</label>
                            <select class="form-select" id="PLStatus">
                                <option value="1">Active</option>
                                <option value="0">In-Active</option>
                            </select>
                        </div>
                        <div class="mb-3 col-md-3">
                            <label class="form-label" for="PLPriority">
                                Priority
                                <span class="text-muted fw-normal" style="font-size:.72rem;">higher = applied first</span>
                            </label>
                            <input type="number" class="form-control" id="PLPriority" value="1" min="1" step="1">
                        </div>
                        <div class="mb-3 col-md-6">
                            <label class="form-label" for="PLValidFrom">
                                Valid From
                                <span class="text-muted fw-normal" style="font-size:.72rem;">blank = permanent</span>
                            </label>
                            <input type="text" class="form-control" id="PLValidFrom" placeholder="Select start date" readonly>
                            <input type="hidden" id="PLValidFromRaw">
                        </div>
                        <div class="mb-3 col-md-6">
                            <label class="form-label" for="PLValidTo">
                                Valid To
                                <span class="text-muted fw-normal" style="font-size:.72rem;">blank = no expiry</span>
                            </label>
                            <input type="text" class="form-control" id="PLValidTo" placeholder="Select end date" readonly>
                            <input type="hidden" id="PLValidToRaw">
                        </div>
                        <div class="mb-3 col-12">
                            <label class="form-label" for="PLDescription">Description
                                <span class="text-muted fw-normal" style="font-size:.72rem;">optional</span>
                            </label>
                            <textarea class="form-control" id="PLDescription" rows="2"
                                placeholder="e.g. Standard permanent prices per customer type — replaces manual customer type pricing"></textarea>
                        </div>
                    </div>

                    <!-- ── 2. Assigned To ────────────────────────────────── -->
                    <div class="card-header modal-header-border-bottom p-1 mb-3 mt-2">
                        <h5 class="modal-title mb-0">
                            Assigned To
                            <span class="text-muted fw-normal" style="font-size:.74rem;">— who does this price list apply to?</span>
                        </h5>
                    </div>
                    <div class="row">
                        <div class="mb-2 col-12">
                            <div class="d-flex flex-wrap gap-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="PLAssignedTo"
                                        id="PLAssignAll" value="All" checked>
                                    <label class="form-check-label fw-semibold" for="PLAssignAll">
                                        All Customers
                                        <span class="d-block text-muted fw-normal" style="font-size:.74rem;">Resolves by customer type automatically</span>
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="PLAssignedTo"
                                        id="PLAssignGroups" value="Groups">
                                    <label class="form-check-label fw-semibold" for="PLAssignGroups">
                                        Customer Groups
                                        <span class="d-block text-muted fw-normal" style="font-size:.74rem;">Applies to all customers in selected groups</span>
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="PLAssignedTo"
                                        id="PLAssignSpecific" value="Customers">
                                    <label class="form-check-label fw-semibold" for="PLAssignSpecific">
                                        Specific Customers
                                        <span class="d-block text-muted fw-normal" style="font-size:.74rem;">Negotiated deal for selected customers</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3 col-12 d-none" id="PLGroupsRow">
                            <label class="form-label" for="PLCustomerGroups">Select Groups <span class="text-danger">*</span></label>
                            <select class="form-select select2" id="PLCustomerGroups" multiple
                                data-placeholder="Search and select customer groups…"></select>
                        </div>
                        <div class="mb-3 col-12 d-none" id="PLSpecificCustRow">
                            <label class="form-label" for="PLCustomers">Select Customers <span class="text-danger">*</span></label>
                            <select class="form-select select2" id="PLCustomers" multiple
                                data-placeholder="Search and select customers…"></select>
                        </div>
                    </div>

                    <!-- ── 3. Scope ──────────────────────────────────────── -->
                    <div class="card-header modal-header-border-bottom p-1 mb-3 mt-2">
                        <h5 class="modal-title mb-0">
                            Scope
                            <span class="text-muted fw-normal" style="font-size:.74rem;">— blanket discount on all products, or custom prices per product?</span>
                        </h5>
                    </div>
                    <div class="row">
                        <div class="mb-2 col-12">
                            <div class="d-flex gap-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="PLScope"
                                        id="PLScopeAll" value="All" checked>
                                    <label class="form-check-label fw-semibold" for="PLScopeAll">
                                        All Products
                                        <span class="d-block text-muted fw-normal" style="font-size:.74rem;">One discount that applies to the entire catalog</span>
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="PLScope"
                                        id="PLScopeSpecific" value="Specific">
                                    <label class="form-check-label fw-semibold" for="PLScopeSpecific">
                                        Specific Products
                                        <span class="d-block text-muted fw-normal" style="font-size:.74rem;">Exact price per product, supports qty breaks</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ── 4a. All Products — Global Discount Table ──────── -->
                    <div id="PLGlobalSection">
                        <div class="card-header modal-header-border-bottom p-1 mb-3 mt-2">
                            <h5 class="modal-title mb-0">Discount Settings</h5>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label" for="PLGlobalBasedOn">
                                    Discount Based On <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="PLGlobalBasedOn">
                                    <option value="SellingPrice">Selling Price</option>
                                    <option value="UnitPrice">Unit Price</option>
                                    <option value="PurchasePrice">Purchase Price</option>
                                </select>
                            </div>
                        </div>
                        <!-- Loading state -->
                        <div id="PLGlobalLoading" class="text-center text-muted py-3" style="font-size:.84rem;">
                            <i class="bx bx-loader-alt bx-spin me-1"></i>Loading customer types…
                        </div>
                        <!-- Discount table — built by JS -->
                        <div class="table-responsive d-none" id="PLGlobalTableWrap">
                            <table class="table table-sm table-bordered align-middle mb-2">
                                <thead class="r2k-thead" style="font-size:.76rem;">
                                    <tr>
                                        <th>Customer Type</th>
                                        <th style="width:180px;">Discount Type</th>
                                        <th style="width:160px;">Value <span class="text-danger">*</span></th>
                                    </tr>
                                </thead>
                                <tbody id="PLGlobalBody"></tbody>
                            </table>
                            <div class="form-text">
                                <i class="bx bx-info-circle me-1"></i>
                                Set <strong>0</strong> for customer types that get no discount. This applies to every product in the catalog.
                            </div>
                        </div>
                    </div>

                    <!-- ── 4b. Specific Products — Price Rules Table ──────── -->
                    <div id="PLRulesSection" class="d-none">
                        <div class="card-header modal-header-border-bottom p-1 mb-2 mt-2 d-flex align-items-center justify-content-between">
                            <h5 class="modal-title mb-0">
                                Price Rules
                                <span class="text-muted fw-normal" style="font-size:.74rem;">— exact price per product per customer type</span>
                            </h5>
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="plAddProductBlock()">
                                <i class="bx bx-plus me-1"></i>Add Product
                            </button>
                        </div>
                        <div class="form-text mb-2">
                            <i class="bx bx-info-circle me-1"></i>
                            Add <strong>multiple rows for the same product</strong> with different Min/Max Qty to create
                            quantity break tiers — e.g. 1–9 units: ₹55 | 10–49: ₹48 | 50+: ₹43.
                            All price fields are required.
                        </div>
                        <!-- Loading state -->
                        <div id="PLRulesLoading" class="text-center text-muted py-3 d-none" style="font-size:.84rem;">
                            <i class="bx bx-loader-alt bx-spin me-1"></i>Loading customer types…
                        </div>
                        <!-- Tax-inclusive notice -->
                        <div class="alert alert-info py-2 mb-2 small">
                            <i class="bx bx-info-circle me-1"></i>
                            <strong>Prices are tax-inclusive.</strong> All amounts entered here include tax. For products configured as <em>Without Tax</em> on the transaction page, amounts will differ accordingly.
                        </div>
                        <!-- Product block cards — one card per product, each has qty tier sub-table -->
                        <div id="PLProductBlocksWrap" class="mb-2"></div>
                        <div id="PLRulesEmpty" class="text-center text-muted py-4 mb-2 d-none" style="font-size:.82rem;">
                            <i class="bx bx-info-circle me-1"></i>
                            No product rules yet. Click <strong>+ Add Product</strong> to define prices.
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>
