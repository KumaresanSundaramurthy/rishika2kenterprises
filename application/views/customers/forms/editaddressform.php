<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php if ($AddressType == 1) { ?>

    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Billing Details</h5>
        </div>
        <div class="card-body">
            <div class="row">

                <input type="hidden" name="BillAddressUID" id="BillAddressUID" value="<?php echo $AddressData->CustAddressUID; ?>" />
                <div class="mb-3 col-md-12">
                    <label for="BillAddrLine1" class="form-label">Address Line 1 <span style="color:red">*</span></label>
                    <input class="form-control" type="text" id="BillAddrLine1" name="BillAddrLine1" maxlength="100" placeholder="Address Line 1" value="<?php echo $AddressData->Line1; ?>" required />
                </div>
                <div class="mb-3 col-md-12">
                    <label for="BillAddrLine2" class="form-label">Address Line 2 </label>
                    <input class="form-control" type="text" id="BillAddrLine2" name="BillAddrLine2" maxlength="100" placeholder="Address Line 2" value="<?php echo $AddressData->Line2; ?>" />
                </div>
                <div class="mb-3 col-md-12">
                    <label for="BillAddrPincode" class="form-label">Pincode <span style="color:red">*</span></label>
                    <input class="form-control" type="text" id="BillAddrPincode" name="BillAddrPincode" maxlength="10" placeholder="Pincode" value="<?php echo $AddressData->Pincode; ?>" required />
                </div>
                <div class="mb-3 col-md-6">
                    <label for="BillAddrState" class="form-label">State</label>
                    <select class="select2 form-select" id="BillAddrState" name="BillAddrState">
                        <option label="-- Select State --"></option>
                        <?php if (sizeof($StateData) > 0) {
                            foreach ($StateData as $StData) { ?>

                                <option value="<?php echo $StData['id']; ?>" data-iso2="<?php echo $StData['iso2']; ?>" <?php echo $AddressData->State == $StData['id'] ? 'selected' : ''; ?>><?php echo $StData['name']; ?></option>

                        <?php }
                        } ?>
                    </select>
                </div>
                <div class="mb-3 col-md-6">
                    <label for="BillAddrCity" class="form-label">City</label>
                    <select class="select2 form-select" id="BillAddrCity" name="BillAddrCity">
                        <option label="-- Select City --">Select City</option>
                        <?php if (sizeof($CityData) > 0) {
                            foreach ($CityData as $CtyData) { ?>

                                <option value="<?php echo $CtyData['id']; ?>" <?php echo $AddressData->City == $CtyData['id'] ? 'selected' : ''; ?>><?php echo $CtyData['name']; ?></option>

                        <?php }
                        } ?>
                    </select>
                </div>

            </div>
        </div>
    </div>

<?php } else if ($AddressType == 2) { ?>

    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Shipping Details</h5>
        </div>
        <div class="card-body">
            <div class="row">

                <input type="hidden" name="ShipAddressUID" id="ShipAddressUID" value="<?php echo $AddressData->CustAddressUID; ?>" required />
                <div class="mb-3 col-md-12">
                    <label for="ShipAddrLine1" class="form-label">Address Line 1 <span style="color:red">*</span></label>
                    <input class="form-control" type="text" id="ShipAddrLine1" name="ShipAddrLine1" maxlength="100" placeholder="Address Line 1" value="<?php echo $AddressData->Line1; ?>" required />
                </div>
                <div class="mb-3 col-md-12">
                    <label for="ShipAddrLine2" class="form-label">Address Line 2 </label>
                    <input class="form-control" type="text" id="ShipAddrLine2" name="ShipAddrLine2" maxlength="100" placeholder="Address Line 2" value="<?php echo $AddressData->Line2; ?>" />
                </div>
                <div class="mb-3 col-md-12">
                    <label for="ShipAddrPincode" class="form-label">Pincode <span style="color:red">*</span></label>
                    <input class="form-control" type="text" id="ShipAddrPincode" name="ShipAddrPincode" maxlength="10" placeholder="Pincode" value="<?php echo $AddressData->Pincode; ?>" required />
                </div>
                <div class="mb-3 col-md-6">
                    <label for="ShipAddrState" class="form-label">State</label>
                    <select class="select2 form-select" id="ShipAddrState" name="ShipAddrState">
                        <option label="-- Select State --"></option>
                        <?php if (sizeof($StateData) > 0) {
                            foreach ($StateData as $StData) { ?>

                                <option value="<?php echo $StData['id']; ?>" data-iso2="<?php echo $StData['iso2']; ?>" <?php echo $AddressData->State == $StData['id'] ? 'selected' : ''; ?>><?php echo $StData['name']; ?></option>

                        <?php }
                        } ?>
                    </select>
                </div>
                <div class="mb-3 col-md-6">
                    <label for="ShipAddrCity" class="form-label">City</label>
                    <select class="select2 form-select" id="ShipAddrCity" name="ShipAddrCity">
                        <option label="-- Select City --"></option>
                        <?php if (sizeof($CityData) > 0) {
                            foreach ($CityData as $CtyData) { ?>

                                <option value="<?php echo $CtyData['id']; ?>" <?php echo $AddressData->City == $CtyData['id'] ? 'selected' : ''; ?>><?php echo $CtyData['name']; ?></option>

                        <?php }
                        } ?>
                    </select>
                </div>

            </div>
        </div>
    </div>

<?php } ?>