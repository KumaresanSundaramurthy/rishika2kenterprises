<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<!-- Country Picker Modal — reusable; opened via CountryPicker.open() from any page -->
<div class="modal fade" id="countryPickerModal" tabindex="-1" aria-hidden="true" aria-labelledby="countryPickerModalLabel" data-bs-backdrop="false">
    <div class="modal-dialog modal-sm" style="max-width:340px;">
        <div class="modal-content">
            <div class="modal-header py-2 px-3">
                <h6 class="modal-title mb-0" id="countryPickerModalLabel">
                    <i class="bx bx-globe me-1"></i> Select Country
                </h6>
                <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-2">
                <input type="text" id="countryPickerSearch"
                    class="form-control form-control-sm mb-2"
                    placeholder="Search country or code..." autocomplete="off" />
                <div id="countryPickerList" style="max-height:320px; overflow-y:auto;"></div>
            </div>
        </div>
    </div>
</div>
