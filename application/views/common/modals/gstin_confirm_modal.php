<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<!-- GSTIN Fetch Confirmation Overlay — shown/hidden by gstin_fetch.js -->
<div id="gstinConfirmOverlay" class="gstin-confirm-overlay">
    <div class="gstin-confirm-dialog">
        <div class="gstin-confirm-content">

            <!-- Header -->
            <div class="gstin-confirm-head">
                <span class="gstin-confirm-icon-box">
                    <i class="bx bx-shield-quarter gstin-confirm-icon"></i>
                </span>
                <div class="gstin-confirm-head-text">
                    <div class="gstin-confirm-title">GSTIN Verified</div>
                    <div class="gstin-confirm-subtitle">Review the details below and confirm to auto-fill the form.</div>
                </div>
            </div>

            <!-- Body -->
            <div class="gstin-confirm-body">
                <div class="gstin-confirm-card">
                    <div id="gstin-confirm-rows"></div>
                </div>
            </div>

            <!-- Footer -->
            <div class="gstin-confirm-foot">
                <button type="button" class="btn btn-outline-secondary btn-sm px-4" id="gstin-confirm-cancel">
                    <i class="bx bx-x me-1"></i>No, Skip
                </button>
                <button type="button" class="btn btn-success btn-sm px-4" id="gstin-confirm-accept">
                    <i class="bx bx-check me-1"></i>Yes, Fill Details
                </button>
            </div>

        </div>
    </div>
</div>
