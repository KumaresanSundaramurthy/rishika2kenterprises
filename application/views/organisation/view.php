<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php $this->load->view('common/header'); ?>

<!-- Layout wrapper -->
<div class="layout-wrapper layout-horizontal basic-form-page layout-content-navbar">
    <div class="layout-container">

        <?php $this->load->view('common/menu_view'); ?>

        <!-- Layout container -->
        <div class="layout-page">

            <!-- Content wrapper -->
            <div class="content-wrapper">

                <div class="container-xxl flex-grow-1 container-p-y">

                    <!-- Org Details -->
                    <?php $FormAttribute = array('id' => 'OrganisationForm', 'name' => 'OrganisationForm', 'class' => '', 'autocomplete' => 'off');
                    echo form_open('organisation/updateOrganisation', $FormAttribute); ?>

                    <input type="hidden" name="OrgUID" id="OrgUID" value="<?php echo isset($EditOrgData->OrgUID) ? $EditOrgData->OrgUID : ''; ?>" />

                    <div class="d-none updateFormAlert alert alert-danger alert-dismissible fade show p-3 m-3 mb-0" role="alert">
                        <span class="alert-message"></span>
                        <button type="button" class="btn-close" aria-label="Close"></button>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header modal-header-center-sticky d-flex justify-content-between align-items-center mb-3">
                            <h5 class="modal-title">Organisation Details</h5>
                            <div class="d-flex align-items-center gap-2" id="mainActionBar">
                                <!-- <button type="button" class="btn btn-label-danger" data-bs-dismiss="modal" aria-label="Close">Close</button> -->
                                <button type="submit" class="btn btn-primary OrgSubBtn me-2">Update</button>
                            </div>
                        </div>
                        <div class="card-body">

                            <div class="row">
                                
                                <div class="col-md-3 d-flex justify-content-center align-items-center">
                                    <div class="dropzone dropzone-main-form needsclick p-3 dz-clickable w-100" id="DropzoneOneBasic">
                                        <div class="dz-message needsclick text-center">
                                            <i class="upload-icon mb-3"></i>
                                            <p class="h4 needsclick mb-2">Drag and drop your logo here</p>
                                            <p class="h6 text-body-secondary fw-normal mb-0">Allowed JPG, GIF or PNG of 1 MB</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-9 d-flex flex-column justify-content-center">
                                    <div class="mb-3 col-md-12">
                                        <label for="Name" class="form-label">Company Name <span style="color:red">*</span></label>
                                        <input class="form-control" type="text" id="Name" name="Name" placeholder="Company Name" value="<?php echo isset($EditOrgData->Name) ? $EditOrgData->Name : ''; ?>" maxlength="100" required />
                                    </div>
                                    <div class="mb-3 col-md-12">
                                        <label for="BrandName" class="form-label">Trade / Brand Name <span style="color:red">*</span></label>
                                        <input class="form-control" type="text" id="BrandName" name="BrandName" required maxlength="100" placeholder="Trade / Brand Name" value="<?php echo isset($EditOrgData->BrandName) ? $EditOrgData->BrandName : ''; ?>" />
                                    </div>
                                    <div class="mb-3 col-md-12">
                                        <label for="Description" class="form-label">Description</label>
                                        <textarea class="form-control" rows="2" name="Description" id="Description" placeholder="Description"><?php echo isset($EditOrgData->ShortDescription) ? $EditOrgData->ShortDescription : ''; ?></textarea>
                                    </div>
                                </div>
                                
                                <div class="mb-3 col-md-6 mt-2">
                                    <label class="form-label" for="MobileNumber">Mobile Number <span style="color:red">*</span></label>
                                    <div class="d-flex gap-2">
                                        <!-- Country is fixed to India — not changeable -->
                                        <input type="hidden" name="CountryCode" id="CountryCode" value="+91" />
                                        <input type="hidden" name="CountryISO2" value="IN" />
                                        <div class="org-country-frozen" title="Country is fixed and cannot be changed">
                                            <span class="org-country-code">🇮🇳 +91</span>
                                            <i class="bx bx-lock-alt org-country-lock"></i>
                                        </div>
                                        <input type="number" id="MobileNumber" name="MobileNumber" class="form-control" placeholder="9790 000 0000" value="<?php echo isset($EditOrgData->MobileNumber) ? $EditOrgData->MobileNumber : ''; ?>" maxLength="20" onkeypress="return (event.charCode !=8 && event.charCode ==0 || (event.charCode >= 48 && event.charCode <= 57))" oninput="this.value=this.value.slice(0,this.maxLength)" pattern="[0-9]*" />
                                    </div>
                                </div>
                                <div class="mb-3 col-md-6 mt-2">
                                    <label for="email" class="form-label">Company Email <span style="color:red">*</span></label>
                                    <input class="form-control" type="email" id="EmailAddress" name="EmailAddress" required disabled maxlength="100" placeholder="Email Address" value="<?php echo isset($EditOrgData->EmailAddress) ? $EditOrgData->EmailAddress : ''; ?>" />
                                </div>
                                <div class="mb-3 col-md-4">
                                    <label for="AlternateNumber" class="form-label">Alternative Contact Number </label>
                                    <input class="form-control" type="number" id="AlternateNumber" name="AlternateNumber" maxLength="20" placeholder="Alternative Contact Number" value="<?php echo isset($EditOrgData->AlternateNumber) ? $EditOrgData->AlternateNumber : ''; ?>" onkeypress="return (event.charCode !=8 && event.charCode ==0 || (event.charCode >= 48 && event.charCode <= 57))" oninput="this.value=this.value.slice(0,this.maxLength)" pattern="[0-9]*" />
                                </div>
                                <div class="mb-3 col-md-4">
                                    <label for="text" class="form-label">GSTIN</label>
                                    <!-- <div class="input-group"> -->
                                        <input type="text" class="form-control" placeholder="GSTIN" aria-label="GSTIN" aria-describedby="GSTIN_Fetch" name="GSTIN" id="GSTIN" value="<?php echo isset($EditOrgData->GSTIN) ? $EditOrgData->GSTIN : ''; ?>" />
                                        <!-- <button class="btn btn-outline-primary" type="button" id="GSTIN_Fetch">Fetch</button> -->
                                    <!-- </div> -->
                                </div>
                                <div class="mb-3 col-md-4">
                                    <label for="PANNumber" class="form-label">PAN Number </label>
                                    <input class="form-control" type="text" id="PANNumber" name="PANNumber" maxlength="10" placeholder="PAN Number" value="<?php echo isset($EditOrgData->PANNumber) ? $EditOrgData->PANNumber : ''; ?>" />
                                </div>
                                <div class="mb-3 col-md-6">
                                    <label class="form-label" for="OrgBussTypeUID">Business Type <span style="color:red">*</span></label>
                                    <select id="OrgBussTypeUID" name="OrgBussTypeUID" class="select2 form-select" required>
                                        <option label="-- Select Business Type --"></option>
                                        <?php if (is_array($OrgBussType) && count($OrgBussType) > 0) {
                                            foreach ($OrgBussType as $BusType) { ?>
                                                <option value="<?php echo $BusType->OrgBussTypeUID; ?>" <?php echo $BusType->OrgBussTypeUID == (isset($EditOrgData->OrgBussTypeUID) ? $EditOrgData->OrgBussTypeUID : '') ? 'selected' : ''; ?>><?php echo $BusType->Name; ?></option>
                                        <?php }
                                        } ?>

                                    </select>
                                </div>
                                <div class="mb-3 col-md-6">
                                    <label class="form-label" for="OrgIndusTypeUID">Industry Type </label>
                                    <select id="OrgIndusTypeUID" name="OrgIndusTypeUID" class="select2 form-select">
                                        <option label="-- Select Industry Type --"></option>
                                        <?php if (is_array($OrgIndusType) && count($OrgIndusType) > 0) {
                                            foreach ($OrgIndusType as $IndType) { ?>
                                                <option value="<?php echo $IndType->OrgIndTypeUID; ?>" <?php echo $IndType->OrgIndTypeUID == (isset($EditOrgData->OrgIndTypeUID) ? $EditOrgData->OrgIndTypeUID : '') ? 'selected' : ''; ?>><?php echo $IndType->Name; ?></option>
                                        <?php }
                                        } ?>

                                    </select>
                                </div>
                                <div class="mb-3 col-md-6">
                                    <label class="form-label" for="OrgBusRegTypeUID">Business Registration Type </label>
                                    <select id="OrgBusRegTypeUID" name="OrgBusRegTypeUID" class="select2 form-select">
                                        <option label="-- Select Business Type --"></option>
                                        <?php if (is_array($OrgBusRegType) && count($OrgBusRegType) > 0) {
                                            foreach ($OrgBusRegType as $BusRegType) { ?>
                                                <option value="<?php echo $BusRegType->OrgBusRegTypeUID; ?>" <?php echo $BusRegType->OrgBusRegTypeUID == (isset($EditOrgData->OrgBusRegTypeUID) ? $EditOrgData->OrgBusRegTypeUID : '') ? 'selected' : ''; ?>><?php echo $BusRegType->Name; ?></option>
                                        <?php }
                                        } ?>

                                    </select>
                                </div>
                                <div class="mb-3 col-md-6">
                                    <label for="TimezoneUID" class="form-label">Timezone</label>
                                    <select class="select2 form-select" id="TimezoneUID" name="TimezoneUID"
                                        data-selected="<?php echo isset($EditOrgData->TimezoneUID) ? (int)$EditOrgData->TimezoneUID : 0; ?>">
                                        <option value="">-- Select Timezone --</option>
                                        <?php if (!empty($EditOrgData->TimezoneUID) && !empty($EditOrgData->TimezoneText)): ?>
                                        <option value="<?php echo (int)$EditOrgData->TimezoneUID; ?>" selected>
                                            <?php echo '(' . htmlspecialchars($EditOrgData->TimezoneGmtOffset) . ') ' . htmlspecialchars($EditOrgData->TimezoneText); ?>
                                        </option>
                                        <?php endif; ?>
                                    </select>
                                </div>
                                <div class="mb-3 col-md-6">
                                    <label for="ShortCode" class="form-label">Short Code <span style="color:red">*</span></label>
                                    <input class="form-control text-uppercase" type="text" id="ShortCode" name="ShortCode" maxlength="3" minlength="3" placeholder="e.g. R2K" value="<?php echo isset($EditOrgData->ShortCode) ? htmlspecialchars(strtoupper($EditOrgData->ShortCode)) : ''; ?>" required pattern="[a-zA-Z0-9]{3}" title="Alphanumeric only, exactly 3 characters" oninput="this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 3)" />
                                    <div class="form-text">Unique identifier used in system keys. Exactly 3 alphanumeric characters.</div>
                                </div>
                                <div class="mb-3 col-md-6">
                                    <label for="Website" class="form-label">Website </label>
                                    <input class="form-control" type="text" id="Website" name="Website" maxlength="255" placeholder="Website" value="<?php echo isset($EditOrgData->Website) ? $EditOrgData->Website : ''; ?>" />
                                </div>
                            </div>

                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            
                            <div class="card mb-3">
                                <div class="card-header modal-header-center-sticky d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="mb-0">Billing Details</h5>
                                    <button type="button" id="CopyToShipping" class="btn btn-sm btn-outline-info">Copy to Shipping</button>
                                </div>
                                <div class="card-body">
                                    <div class="row">

                                        <input type="hidden" name="BillOrgAddressUID" id="BillOrgAddressUID" value="<?php echo isset($BillOrgAddrData->OrgAddressUID) ? $BillOrgAddrData->OrgAddressUID : 0; ?>" required />
                                        <div class="mb-3 col-md-12">
                                            <label for="BillAddrLine1" class="form-label">Address Line 1 <span style="color:red">*</span></label>
                                            <input class="form-control" type="text" id="BillAddrLine1" name="BillAddrLine1" maxlength="100" placeholder="Address Line 1" value="<?php echo isset($BillOrgAddrData->Line1) ? $BillOrgAddrData->Line1 : ''; ?>" required />
                                        </div>
                                        <div class="mb-3 col-md-12">
                                            <label for="BillAddrLine2" class="form-label">Address Line 2 </label>
                                            <input class="form-control" type="text" id="BillAddrLine2" name="BillAddrLine2" maxlength="100" placeholder="Address Line 2" value="<?php echo isset($BillOrgAddrData->Line2) ? $BillOrgAddrData->Line2 : ''; ?>" />
                                        </div>
                                        <div class="mb-3 col-md-12">
                                            <label for="BillAddrPincode" class="form-label">Pincode <span style="color:red">*</span></label>
                                            <input class="form-control" type="text" id="BillAddrPincode" name="BillAddrPincode" maxlength="10" placeholder="Pincode" value="<?php echo isset($BillOrgAddrData->Pincode) ? $BillOrgAddrData->Pincode : ''; ?>" required />
                                        </div>
                                        <div class="mb-3 col-md-6">
                                            <label for="BillAddrState" class="form-label">State</label>
                                            <select class="select2 form-select" id="BillAddrState" name="BillAddrState">
                                                <option value="">-- Select State --</option>
                                                <?php if (!empty($BillOrgAddrData->State) && !empty($BillOrgAddrData->StateText)): ?>
                                                <option value="<?php echo $BillOrgAddrData->State; ?>" selected><?php echo htmlspecialchars($BillOrgAddrData->StateText); ?></option>
                                                <?php endif; ?>
                                            </select>
                                        </div>
                                        <div class="mb-3 col-md-6">
                                            <label for="BillAddrCity" class="form-label">City</label>
                                            <select class="select2 form-select" id="BillAddrCity" name="BillAddrCity">
                                                <option value="">-- Select City --</option>
                                                <?php if (!empty($BillOrgAddrData->City) && !empty($BillOrgAddrData->CityText)): ?>
                                                <option value="<?php echo $BillOrgAddrData->City; ?>" selected><?php echo htmlspecialchars($BillOrgAddrData->CityText); ?></option>
                                                <?php endif; ?>
                                            </select>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">

                            <div class="card mb-3">
                                <div class="card-header modal-header-center-sticky mb-3">
                                    <h5 class="mb-0">Shipping Details</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">

                                        <input type="hidden" name="ShipOrgAddressUID" id="ShipOrgAddressUID" value="<?php echo isset($ShipOrgAddrData->OrgAddressUID) ? $ShipOrgAddrData->OrgAddressUID : 0; ?>" required />
                                        <div class="mb-3 col-md-12">
                                            <label for="ShipAddrLine1" class="form-label">Address Line 1 <span style="color:red">*</span></label>
                                            <input class="form-control" type="text" id="ShipAddrLine1" name="ShipAddrLine1" maxlength="100" placeholder="Address Line 1" value="<?php echo isset($ShipOrgAddrData->Line1) ? $ShipOrgAddrData->Line1 : ''; ?>" required />
                                        </div>
                                        <div class="mb-3 col-md-12">
                                            <label for="ShipAddrLine2" class="form-label">Address Line 2 </label>
                                            <input class="form-control" type="text" id="ShipAddrLine2" name="ShipAddrLine2" maxlength="100" placeholder="Address Line 2" value="<?php echo isset($ShipOrgAddrData->Line2) ? $ShipOrgAddrData->Line2 : ''; ?>" />
                                        </div>
                                        <div class="mb-3 col-md-12">
                                            <label for="ShipAddrPincode" class="form-label">Pincode <span style="color:red">*</span></label>
                                            <input class="form-control" type="text" id="ShipAddrPincode" name="ShipAddrPincode" maxlength="10" placeholder="Pincode" value="<?php echo isset($ShipOrgAddrData->Pincode) ? $ShipOrgAddrData->Pincode : ''; ?>" required />
                                        </div>
                                        <div class="mb-3 col-md-6">
                                            <label for="ShipAddrState" class="form-label">State</label>
                                            <select class="select2 form-select" id="ShipAddrState" name="ShipAddrState">
                                                <option value="">-- Select State --</option>
                                                <?php if (!empty($ShipOrgAddrData->State) && !empty($ShipOrgAddrData->StateText)): ?>
                                                <option value="<?php echo $ShipOrgAddrData->State; ?>" selected><?php echo htmlspecialchars($ShipOrgAddrData->StateText); ?></option>
                                                <?php endif; ?>
                                            </select>
                                        </div>
                                        <div class="mb-3 col-md-6">
                                            <label for="ShipAddrCity" class="form-label">City</label>
                                            <select class="select2 form-select" id="ShipAddrCity" name="ShipAddrCity">
                                                <option value="">-- Select City --</option>
                                                <?php if (!empty($ShipOrgAddrData->City) && !empty($ShipOrgAddrData->CityText)): ?>
                                                <option value="<?php echo $ShipOrgAddrData->City; ?>" selected><?php echo htmlspecialchars($ShipOrgAddrData->CityText); ?></option>
                                                <?php endif; ?>
                                            </select>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="card mb-3" id="fixedActionBar">
                        <div class="card-body p-0">
                            <div class="m-3">
                                <button type="submit" class="btn btn-primary OrgSubBtn me-2 ">Update</button>
                                <!-- <a href="/dashboard" class="btn btn-label-danger">Close</a> -->
                            </div>
                        </div>
                    </div>

                    <!-- Sticky duplicate bar -->
                    <div class="card mb-3" id="stickyActionBar">
                        <div class="card-body p-0">
                            <div class="m-3 text-end">
                            <button type="submit" class="btn btn-primary OrgSubBtn me-2">Update</button>
                            <!-- <a href="/dashboard" class="btn btn-label-danger">Close</a> -->
                            </div>
                        </div>
                    </div>

                <?php echo form_close(); ?>
                    <!-- / Org Details -->

                </div>
            </div>
            <!-- Content wrapper -->

            <?php $this->load->view('common/footer_desc'); ?>

        </div>

    </div>
</div>

<?php $this->load->view('common/footer'); ?>

<script>
/* Suppress Pickr error on this page — pickr.js loads after template-customizer.js window.onload */
if (typeof Pickr === 'undefined') { window.Pickr = function() { return { on: function() { return this; } }; }; }
</script>

<style>
.org-country-frozen {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 0 12px;
    height: 38px;
    background: #f0f0f0;
    border: 1px solid #d0d0d0;
    border-radius: 6px;
    white-space: nowrap;
    cursor: not-allowed;
    user-select: none;
    flex-shrink: 0;
}
.org-country-code { font-size: 0.875rem; font-weight: 600; color: #555; letter-spacing: 0.02em; }
.org-country-lock { font-size: 0.8rem; color: #999; }
</style>

<script src="/js/organisation.js"></script>

<script>
var imageChange = 0;
var defaultImg = '<?php echo isset($EditOrgData->Logo) ? $EditOrgData->Logo : ''; ?>';
$(function() {
    'use strict'

    if (hasValue(defaultImg)) {
        var ImageUrl = CDN_URL + defaultImg;
        commonSetDropzoneImageOne(ImageUrl);
    }
    
    // Timezone: saved value shown on load (from DB join).
    // On first click → check Upstash directly; if miss → AJAX (PHP stores in Upstash).
    // Every subsequent click (same page or after refresh) → Upstash hit, instant load.
    loadSelect2Field('#TimezoneUID', '-- Select Timezone --');
    var _tzLoaded = false;
    $('#TimezoneUID').on('select2:opening', function (e) {
        if (_tzLoaded) return;
        e.preventDefault();
        var $sel     = $(this);
        var selected = parseInt($sel.data('selected')) || 0;

        function _renderTimezones(data) {
            _tzLoaded = true;
            $sel.empty().append('<option value="">-- Select Timezone --</option>');
            $.each(data, function (i, tz) {
                $sel.append(
                    $('<option>').val(tz.TimezoneUID)
                        .attr('data-ccode', tz.CountryCode || '')
                        .text('(' + tz.GmtOffset + ') ' + tz.Timezone)
                );
            });
            if (selected) $sel.val(selected).trigger('change.select2');
            _openAndScroll($sel);
        }

        // 1. Check Upstash directly — instant if already cached
        UpstashService.get(UpstashService.orgKey('loc-timezone-all')).then(function (cached) {
            if (cached && Array.isArray(cached) && cached.length > 0) {
                _renderTimezones(cached);
            } else {
                // 2. Upstash miss — AJAX; PHP queries DB and stores in Upstash
                $.ajax({
                    url: '/globally/getTimezones', method: 'GET',
                    success: function (resp) {
                        if (!resp.Error && resp.Data) _renderTimezones(resp.Data);
                    }
                });
            }
        });
    });
    loadSelect2Field('#BillAddrState', '-- Select State --');
    loadSelect2Field('#ShipAddrState', '-- Select State --');
    loadSelect2Field('#BillAddrCity', '-- Select City --');
    loadSelect2Field('#ShipAddrCity', '-- Select City --');

    $('#CopyToShipping').click(function(e) {
        e.preventDefault();

        var BillLine1 = $('#BillAddrLine1').val();
        if (hasValue(BillLine1)) $('#ShipAddrLine1').val(BillLine1);

        var BillLine2 = $('#BillAddrLine2').val();
        if (hasValue(BillLine2)) $('#ShipAddrLine2').val(BillLine2);

        var BillPincode = $('#BillAddrPincode').val();
        if (hasValue(BillPincode)) $('#ShipAddrPincode').val(BillPincode);

        var BillState = $('#BillAddrState').find('option:selected').val();
        if (hasValue(BillState)) $('#ShipAddrState').val(BillState).trigger('change');

        var BillCity = $('#BillAddrCity').find('option:selected').val();
        if (hasValue(BillCity)) $('#ShipAddrCity').val(BillCity).trigger('change');

    });

    $('#OrganisationForm').submit(function(e) {
        e.preventDefault();

        $('.updateFormAlert').addClass('d-none');
        var formData = new FormData($('#OrganisationForm')[0]);
        formData.append('imageChange', imageChange);

        var $billState = $('#BillAddrState').find('option:selected');
        formData.append('BillAddrStateText', ($billState.val() && $billState.val().trim() !== '') ? $billState.text() : '');

        var $billCity = $('#BillAddrCity').find('option:selected');
        formData.append('BillAddrCityText', ($billCity.val() && $billCity.val().trim() !== '') ? $billCity.text() : '');

        var $shipState = $('#ShipAddrState').find('option:selected');
        formData.append('ShipAddrStateText', ($shipState.val() && $shipState.val().trim() !== '') ? $shipState.text() : '');

        var $shipCity = $('#ShipAddrCity').find('option:selected');
        formData.append('ShipAddrCityText', ($shipCity.val() && $shipCity.val().trim() !== '') ? $shipCity.text() : '');

        if (myOneDropzone.files.length > 0) {
            const file = myOneDropzone.files[0];
            if (!file.isStored) {
                formData.append('UploadImage', myOneDropzone.files[0]);
            }
        }

        updateOrgForm(formData);

    });

    /** Common Sticky Bar */
    var $stickyBar = $('#stickyActionBar');
    var $fixedBar = $('#fixedActionBar');

    function toggleStickyBar() {
        if ($fixedBar.length) {
            var rect = $fixedBar[0].getBoundingClientRect();
            var windowHeight = $(window).height();

            // ✅ Partial visibility check
            var partiallyVisible = rect.bottom > 0 && rect.top < windowHeight;

            if (partiallyVisible) {
                $stickyBar.stop(true, true).fadeOut(150);
            } else {
                $stickyBar.stop(true, true).fadeIn(150);
            }
        }
    }

    $stickyBar.show();
    $(window).on('scroll resize', toggleStickyBar);
    toggleStickyBar();

});
</script>