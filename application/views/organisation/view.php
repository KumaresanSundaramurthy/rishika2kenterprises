<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php $this->load->view('common/header'); ?>

<div class="layout-wrapper layout-horizontal layout-content-navbar">
    <div class="layout-container">

        <?php $this->load->view('common/menu_view'); ?>

        <div class="layout-page">
            <div class="content-wrapper apex-content">
                <?php $this->load->view('common/apex/page_header', [
                    'pageIcon'        => 'bx-buildings',
                    'pageIconBg'      => '#eff6ff',
                    'pageIconColor'   => '#2563eb',
                    'pageTitle'       => $PageTitle       ?? 'Organisation',
                    'pageDescription' => $PageDescription ?? '',
                ]); ?>

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
                        <div class="card-header modal-header-center-sticky d-flex justify-content-between align-items-center p-2">
                            <h5 class="modal-title">Organisation Details</h5>
                            <div class="d-flex align-items-center gap-2" id="mainActionBar">
                                <!-- <button type="button" class="btn btn-label-danger" data-bs-dismiss="modal" aria-label="Close">Close</button> -->
                                <button type="submit" class="btn btn-primary OrgSubBtn me-2">Update</button>
                            </div>
                        </div>
                        <div class="card-body mt-3">

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
                                <div class="mb-3 col-md-6">
                                    <label for="AlternateNumber" class="form-label">Alternative Contact Number </label>
                                    <input class="form-control" type="number" id="AlternateNumber" name="AlternateNumber" maxLength="20" placeholder="Alternative Contact Number" value="<?php echo isset($EditOrgData->AlternateNumber) ? $EditOrgData->AlternateNumber : ''; ?>" onkeypress="return (event.charCode !=8 && event.charCode ==0 || (event.charCode >= 48 && event.charCode <= 57))" oninput="this.value=this.value.slice(0,this.maxLength)" pattern="[0-9]*" />
                                </div>
                                <div class="mb-3 col-md-6">
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

                    <!-- GST & Place of Supply -->
                    <?php
                    $indianGstStates = [
                        '01'=>'Jammu & Kashmir',      '02'=>'Himachal Pradesh',
                        '03'=>'Punjab',               '04'=>'Chandigarh',
                        '05'=>'Uttarakhand',          '06'=>'Haryana',
                        '07'=>'Delhi',                '08'=>'Rajasthan',
                        '09'=>'Uttar Pradesh',        '10'=>'Bihar',
                        '11'=>'Sikkim',               '12'=>'Arunachal Pradesh',
                        '13'=>'Nagaland',             '14'=>'Manipur',
                        '15'=>'Mizoram',              '16'=>'Tripura',
                        '17'=>'Meghalaya',            '18'=>'Assam',
                        '19'=>'West Bengal',          '20'=>'Jharkhand',
                        '21'=>'Odisha',               '22'=>'Chhattisgarh',
                        '23'=>'Madhya Pradesh',       '24'=>'Gujarat',
                        '26'=>'Dadra & Nagar Haveli and Daman & Diu',
                        '27'=>'Maharashtra',          '29'=>'Karnataka',
                        '30'=>'Goa',                  '31'=>'Lakshadweep',
                        '32'=>'Kerala',               '33'=>'Tamil Nadu',
                        '34'=>'Puducherry',           '35'=>'Andaman & Nicobar Islands',
                        '36'=>'Telangana',            '37'=>'Andhra Pradesh',
                        '38'=>'Ladakh',               '97'=>'Other Territory',
                    ];
                    $savedStateCode = isset($EditOrgData->StateCode) ? $EditOrgData->StateCode : '';
                    ?>
                    <div class="card mb-3">
                        <div class="card-header modal-header-center-sticky p-2">
                            <h5 class="mb-0">GST &amp; Place of Supply</h5>
                        </div>
                        <div class="card-body p-3">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Registered State <span class="text-danger">*</span></label>
                                    <select class="form-select" id="OrgStateCode" name="StateCode">
                                        <option value="">— Select State —</option>
                                        <?php foreach ($indianGstStates as $code => $name): ?>
                                        <option value="<?php echo $code; ?>" data-name="<?php echo htmlspecialchars($name, ENT_QUOTES); ?>"
                                            <?php echo $savedStateCode === $code ? 'selected' : ''; ?>>
                                            <?php echo $code; ?> — <?php echo htmlspecialchars($name); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="hidden" id="OrgStateName" name="StateName"
                                        value="<?php echo htmlspecialchars($savedStateCode ? ($indianGstStates[$savedStateCode] ?? '') : '', ENT_QUOTES); ?>" />
                                    <div class="form-text">The state where your GST is registered. Determines CGST+SGST vs IGST on all transactions.</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">GSTIN</label>
                                    <input type="text" class="form-control text-uppercase" id="OrgGSTIN" name="GSTIN"
                                        maxlength="15" placeholder="e.g. 33AAAAA0000A1Z5"
                                        value="<?php echo htmlspecialchars(isset($EditOrgData->GSTIN) ? $EditOrgData->GSTIN : '', ENT_QUOTES); ?>"
                                        oninput="this.value = this.value.toUpperCase()" />
                                    <div class="form-text">Your organisation's 15-digit GST Identification Number.</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ── Billing Address card ──────────────────────────────────────── -->
                    <div class="card mb-3">
                        <div class="card-header modal-header-center-sticky p-3 d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Billing Address</h5>
                        </div>
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="fw-semibold small text-muted text-uppercase">Billing Address</span>
                                    <a href="javascript:void(0)" class="btn btn-sm btn-outline-warning" id="addBillingAddress">
                                        <i class="bx bx-plus-circle"></i>
                                    </a>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-secondary d-none" id="copyToShippingBtn">
                                    <i class="bx bx-copy-alt me-1"></i>Copy to Shipping
                                </button>
                            </div>
                            <div id="appendBillingAddress"></div>
                        </div>
                    </div>

                    <!-- ── Shipping Addresses card ────────────────────────────────────── -->
                    <div class="card mb-3">
                        <div class="card-header modal-header-center-sticky p-3 d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Shipping Addresses</h5>
                            <span class="badge bg-label-secondary" id="orgShipCount">
                                <?php echo count($ShipOrgAddrList ?? []); ?> / <?php echo $MaxShippingAddr; ?>
                            </span>
                        </div>
                        <div class="card-body p-3">
                            <div class="row g-3" id="orgShipContainer">
                                <?php $loop = 0; foreach (($ShipOrgAddrList ?? []) as $sa): ?>
                                <div class="col-md-4 org-ship-card" data-uid="<?php echo (int)$sa->OrgAddressUID; ?>">
                                    <div class="org-ship-bg border rounded p-3 d-flex justify-content-between align-items-start gap-2 h-100">
                                        <div class="small text-muted lh-base">
                                            <?php if ($sa->Line1): ?><div><?php echo htmlspecialchars($sa->Line1); ?></div><?php endif; ?>
                                            <?php if ($sa->Line2): ?><div><?php echo htmlspecialchars($sa->Line2); ?></div><?php endif; ?>
                                            <?php
                                                $loc = implode(', ', array_filter([$sa->CityText ?? '', $sa->StateText ?? '', $sa->Pincode ?? '']));
                                                if ($loc): ?><div><?php echo htmlspecialchars($loc); ?></div><?php endif;
                                            ?>
                                        </div>
                                        <div class="d-flex gap-1 flex-shrink-0">
                                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="_openOrgShipModal(<?php echo $loop; ?>)" title="Edit"><i class="bx bx-edit-alt"></i></button>
                                            <?php if (count($ShipOrgAddrList) > 1): ?>
                                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="_deleteOrgShip(<?php echo $loop; ?>)" title="Delete"><i class="bx bx-trash"></i></button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <?php $loop++; endforeach; ?>
                            </div>
                            <div class="mt-3">
                                <button type="button" class="btn btn-outline-warning btn-sm <?php echo count($ShipOrgAddrList ?? []) >= $MaxShippingAddr ? 'd-none' : ''; ?>" id="btnAddOrgShip">
                                    <i class="bx bx-plus-circle me-1"></i>Add Shipping Address
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Hidden: dummy elements address.js needs (unused on org page, silently no-ops) -->
                    <div class="d-none" id="appendShippingAddress"></div>
                    <button type="button" class="d-none" id="addShippingAddress"></button>
                    <button type="button" class="d-none" id="copyToBillingBtn"></button>


                <?php echo form_close(); ?>
                    <!-- / Org Details -->

                </div>
            </div>

            <?php $this->load->view('common/form/address_form'); ?>
            <?php $this->load->view('common/footer_desc'); ?>

        </div>
    </div>
</div>

<?php $this->load->view('common/footer'); ?>

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

/* ── Address card backgrounds (point 5) ── */
#appendBillingAddress .border { background-color: #eef6ff; }   /* billing: soft blue  */
.org-ship-bg                  { background-color: #f0fdf4; }   /* shipping: soft green */
</style>

<script src="/js/organisation.js"></script>
<script src="/js/common/address.js"></script>

<script>
var OrgCountryISO2 = '<?php echo $JwtData->Org->OrgCISO2 ?? 'IN'; ?>';
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
    // Sync StateName hidden field when registered state changes
    $('#OrgStateCode').on('change', function () {
        var $opt = $(this).find('option:selected');
        $('#OrgStateName').val($opt.data('name') || '');
    });

    // ── Shipping vars declared first (needed by billing init below) ──────
    var _maxShip      = <?php echo (int)($MaxShippingAddr ?? 3); ?>;
    var orgShipList   = <?php
        $shipJs = [];
        foreach (($ShipOrgAddrList ?? []) as $sa) {
            $shipJs[] = [
                'UID'      => (int)$sa->OrgAddressUID,
                'Line1'    => $sa->Line1    ?? '',
                'Line2'    => $sa->Line2    ?? '',
                'Pincode'  => $sa->Pincode  ?? '',
                'StateId'  => $sa->State    ?? '',
                'StateName'=> $sa->StateText?? '',
                'StateISO2'=> '',
                'CityId'   => $sa->City     ?? '',
                'CityName' => $sa->CityText ?? '',
            ];
        }
        echo json_encode($shipJs);
    ?>;
    var orgDelShipUIDs = [];
    var _orgShipMode   = false;
    var _orgShipEditIdx = -1;

    // Override address.js _updateCopyButtons so it doesn't interfere with our logic
    window._updateCopyButtons = function () { _updateOrgCopyButtons(); };

    // Override renderAddrSummary so billing card NEVER gets a Delete button
    // (address.js always adds one — we wrap it to strip it immediately for type 1)
    var _origRenderAddrSummary = renderAddrSummary;
    renderAddrSummary = function (addrType, data) {
        _origRenderAddrSummary(addrType, data);
        if (addrType == 1) {
            $('#appendBillingAddress .deleteAddrBtn').remove();
            _updateOrgCopyButtons();
        }
    };

    // Global hook called by organisation.js after a successful save.
    // Defined here (inside document.ready) so it has closure access to local vars:
    // orgShipList, orgDelShipUIDs, _renderOrgShipCards, billingAddrData (global from address.js)
    window._orgAfterSave = function (response) {
        // Sync billing UID so re-saves UPDATE instead of INSERT
        if (response.BillAddrUID && billingAddrData) {
            billingAddrData.UID = response.BillAddrUID;
        }
        // Replace orgShipList with fresh server data (correct UIDs)
        if (Array.isArray(response.ShipAddresses)) {
            orgShipList = response.ShipAddresses;
            _renderOrgShipCards();
        }
        // Clear deleted-UIDs tracker — they're now gone from DB
        orgDelShipUIDs = [];
        // Rebuild copy-button state after card refresh
        _updateOrgCopyButtons();
    };

    // ── Billing: pre-populate from existing DB data ───────────────────────
    <?php if (!empty($BillOrgAddrData->Line1)): ?>
    billingAddrData = {
        UID      : <?php echo (int)($BillOrgAddrData->OrgAddressUID ?? 0); ?>,
        Line1    : <?php echo json_encode($BillOrgAddrData->Line1    ?? ''); ?>,
        Line2    : <?php echo json_encode($BillOrgAddrData->Line2    ?? ''); ?>,
        Pincode  : <?php echo json_encode($BillOrgAddrData->Pincode  ?? ''); ?>,
        StateId  : <?php echo json_encode($BillOrgAddrData->State    ?? ''); ?>,
        StateName: <?php echo json_encode($BillOrgAddrData->StateText?? ''); ?>,
        StateISO2: '',
        CityId   : <?php echo json_encode($BillOrgAddrData->City     ?? ''); ?>,
        CityName : <?php echo json_encode($BillOrgAddrData->CityText ?? ''); ?>
    };
    renderAddrSummary(1, billingAddrData); // override above strips delete btn automatically
    <?php endif; ?>
    _updateOrgCopyButtons();

    // ── Intercept AddrSaveBtn for shipping — fires BEFORE address.js handler
    $('#AddrSaveBtn').on('click', function (e) {
        if (!_orgShipMode) return; // billing: let address.js handle
        e.stopImmediatePropagation();

        var line1 = $.trim($('#ModalAddrLine1').val());
        if (!line1) { showAlertMessageSwal('error', '', 'Address Line 1 is required.'); return; }
        var pincode = $.trim($('#ModalAddrPincode').val());
        if (!pincode) { showAlertMessageSwal('error', '', 'Pincode is required.'); return; }

        var $state    = $('#ModalAddrState').find('option:selected');
        var $city     = $('#ModalAddrCity').find('option:selected');
        var stateISO2 = $state.data('iso2') || '';

        var data = {
            UID      : (_orgShipEditIdx >= 0) ? (orgShipList[_orgShipEditIdx].UID || 0) : 0,
            Line1    : line1,
            Line2    : $.trim($('#ModalAddrLine2').val()),
            Pincode  : pincode,
            StateId  : $state.val()  || '',
            StateName: ($state.val() && $state.text() !== '-- Select State --') ? $state.text() : '',
            StateISO2: stateISO2,
            CityId   : $city.val()   || '',
            CityName : ($city.val()  && $city.text()  !== '-- Select City --')  ? $city.text()  : ''
        };

        if (_orgShipEditIdx >= 0) {
            orgShipList[_orgShipEditIdx] = data;
        } else {
            orgShipList.push(data);
        }

        _renderOrgShipCards();
        _orgShipMode    = false;
        _orgShipEditIdx = -1;
        $('#addEditAddressModal').modal('hide');
    });

    // Reset mode on modal close
    $('#addEditAddressModal').on('hidden.bs.modal', function () {
        _orgShipMode    = false;
        _orgShipEditIdx = -1;
    });

    // ── Open shipping modal (new or edit) ────────────────────────────────
    function _openOrgShipModal(idx) {
        _orgShipMode    = true;
        _orgShipEditIdx = (idx !== undefined && idx >= 0) ? idx : -1;
        shippingAddrData = (_orgShipEditIdx >= 0) ? orgShipList[_orgShipEditIdx] : null;
        openAddressModal(2);
    }

    // ── Copy-button visibility rules (points 3 & 4) ───────────────────────
    function _updateOrgCopyButtons() {
        // "Copy to Shipping" on billing: only when no shipping addresses exist
        if (billingAddrData && orgShipList.length === 0) {
            $('#copyToShippingBtn').removeClass('d-none');
        } else {
            $('#copyToShippingBtn').addClass('d-none');
        }
        // "Copy to Billing" on shipping: handled inside _renderOrgShipCards
    }

    // ── Delete a shipping card (point 6: min 1 shipping allowed) ─────────
    function _deleteOrgShip(idx) {
        if (orgShipList.length <= 1) return; // safety guard
        if (orgShipList[idx] && orgShipList[idx].UID > 0) {
            orgDelShipUIDs.push(orgShipList[idx].UID);
        }
        orgShipList.splice(idx, 1);
        _renderOrgShipCards();
        _updateOrgCopyButtons();
    }

    // ── Render all shipping cards (points 5 & 6) ──────────────────────────
    function _renderOrgShipCards() {
        var $c     = $('#orgShipContainer');
        var count  = orgShipList.length;
        var canDel = count > 1; // point 6: delete only when more than 1
        $c.empty();
        orgShipList.forEach(function (addr, idx) {
            var lines = [];
            if (addr.Line1) lines.push(addr.Line1);
            if (addr.Line2) lines.push(addr.Line2);
            var loc = [addr.CityName, addr.StateName, addr.Pincode].filter(Boolean).join(', ');
            if (loc) lines.push(loc);
            var txt = lines.map(function (l) { return '<div>' + $('<div>').text(l).html() + '</div>'; }).join('');
            var delBtn = canDel
                ? '<button type="button" class="btn btn-sm btn-outline-danger" onclick="_deleteOrgShip(' + idx + ')" title="Delete"><i class="bx bx-trash"></i></button>'
                : '';
            $c.append(
                '<div class="col-md-4">' +
                  '<div class="org-ship-bg border rounded p-3 d-flex justify-content-between align-items-start gap-2 h-100">' +
                    '<div class="small lh-base">' + txt + '</div>' +
                    '<div class="d-flex gap-1 flex-shrink-0">' +
                      '<button type="button" class="btn btn-sm btn-outline-primary" onclick="_openOrgShipModal(' + idx + ')" title="Edit"><i class="bx bx-edit-alt"></i></button>' +
                      delBtn +
                    '</div>' +
                  '</div>' +
                '</div>'
            );
        });
        $('#orgShipCount').text(count + ' / ' + _maxShip);
        $('#btnAddOrgShip').toggleClass('d-none', count >= _maxShip);
        // Point 4: "Copy to Billing" — show on shipping section only when billing is empty
        // (handled via a dedicated element inside shipping card header — not applicable here)
        _updateOrgCopyButtons();
    }

    // ── Add Shipping Address button ───────────────────────────────────────
    $('#btnAddOrgShip').on('click', function () {
        if (orgShipList.length >= _maxShip) {
            showToastNotification('Maximum ' + _maxShip + ' shipping addresses allowed.', 'error');
            return;
        }
        _openOrgShipModal();
    });

    // Billing save is handled by address.js → renderAddrSummary override strips delete btn

    $('#OrganisationForm').submit(function(e) {
        e.preventDefault();

        $('.updateFormAlert').addClass('d-none');
        var formData = new FormData($('#OrganisationForm')[0]);
        formData.append('imageChange', imageChange);

        // Billing address
        formData.append('BillOrgAddressUID', billingAddrData ? (billingAddrData.UID     || 0)   : 0);
        formData.append('BillAddrLine1',     billingAddrData ? (billingAddrData.Line1    || '')  : '');
        formData.append('BillAddrLine2',     billingAddrData ? (billingAddrData.Line2    || '')  : '');
        formData.append('BillAddrPincode',   billingAddrData ? (billingAddrData.Pincode  || '')  : '');
        formData.append('BillAddrState',     billingAddrData ? (billingAddrData.StateId  || '')  : '');
        formData.append('BillAddrStateText', billingAddrData ? (billingAddrData.StateName|| '')  : '');
        formData.append('BillAddrCity',      billingAddrData ? (billingAddrData.CityId   || '')  : '');
        formData.append('BillAddrCityText',  billingAddrData ? (billingAddrData.CityName || '')  : '');

        // Multiple shipping addresses as JSON
        formData.append('ShipAddresses', JSON.stringify(orgShipList));
        formData.append('DelShipUIDs',   JSON.stringify(orgDelShipUIDs));

        if (myOneDropzone.files.length > 0) {
            const file = myOneDropzone.files[0];
            if (!file.isStored) {
                formData.append('UploadImage', myOneDropzone.files[0]);
            }
        }

        updateOrgForm(formData);

    });


});
</script>