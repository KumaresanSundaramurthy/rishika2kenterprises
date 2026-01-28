<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<!-- Tax Details Modal -->
<div class="modal fade" id="taxDetailsModal" tabindex="-1" aria-labelledby="taxDetailsModal" data-bs-backdrop="static" data-bs-keyboard="false" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header trans-theme justify-content-between align-items-center p-3">
                <h5 class="modal-title fs-5">Tax Breakdown Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table trans-table table-bordered table-sm">
                        <thead class="table-light trans-table-light">
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
                                        <i class="bx bx-cart text-muted text-primary" style="font-size: 2rem;"></i>
                                        <p class="mt-2 mb-0">No items added yet</p>
                                        <small class="text-muted">Click "Add Product" or search above to get started</small>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                        <tfoot class="table-light trans-table-light">
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