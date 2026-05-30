<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<!-- Tax Details Modal -->
<div class="modal fade" id="taxDetailsModal" tabindex="-1" aria-labelledby="taxDetailsModal" data-bs-backdrop="static" data-bs-keyboard="false" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="overflow:hidden;">

            <!-- vtm-banner header — matches viewTransModal style -->
            <div class="vtm-banner flex-shrink-0" style="--vtm-color:#0284c7;--vtm-bg:#e0f2fe;--vtm-icon-bg:rgba(2,132,199,.13);">
                <div class="vtm-banner-inner">
                    <div class="vtm-banner-left">
                        <div class="vtm-banner-icon">
                            <i class="bx bx-receipt" style="font-size:1.5rem;color:var(--vtm-color);display:block;"></i>
                        </div>
                        <div>
                            <div class="vtm-doc-number">Tax Breakdown</div>
                            <div class="vtm-doc-meta">GST split by tax rate across all items</div>
                        </div>
                    </div>
                    <div class="vtm-banner-right">
                        <button type="button" class="vtm-close-btn" data-bs-dismiss="modal" aria-label="Close">
                            <i class="bx bx-x"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="modal-body p-3">
                <div class="table-responsive">
                    <table class="table trans-table table-bordered table-sm mb-0">
                        <thead style="background:#e0f2fe;color:#0c4a6e;">
                            <tr>
                                <th>Tax Rate</th>
                                <th>Taxable Amount</th>
                                <th class="taxbrkupCgst">CGST</th>
                                <th class="taxbrkupSgst">SGST</th>
                                <th class="taxbrkupIgst">IGST</th>
                                <th>Total Tax</th>
                                <th>Items</th>
                            </tr>
                        </thead>
                        <tbody id="taxDetailsTableBody">
                            <tr class="text-center text-muted">
                                <td colspan="7">
                                    <div class="py-4">
                                        <i class="bx bx-cart text-primary" style="font-size:2rem;"></i>
                                        <p class="mt-2 mb-0">No items added yet</p>
                                        <small class="text-muted">Add products to see tax breakdown</small>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                        <tfoot style="background:#e0f2fe;color:#0c4a6e;">
                            <tr>
                                <th>Total</th>
                                <th id="totalTaxableAmount">0.00</th>
                                <th class="taxbrkupCgst" id="totalCGST">0.00</th>
                                <th class="taxbrkupSgst" id="totalSGST">0.00</th>
                                <th class="taxbrkupIgst" id="totalIGST">0.00</th>
                                <th id="totalTaxAmount">0.00</th>
                                <th id="totalItemsCount">0</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>
