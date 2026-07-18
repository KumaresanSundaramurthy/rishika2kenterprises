<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<input type="hidden" id="placeOfSupplyCode" name="placeOfSupplyCode" value="<?php echo htmlspecialchars($_posCode ?? '', ENT_QUOTES); ?>" />
<input type="hidden" id="placeOfSupplyName" name="placeOfSupplyName" value="<?php echo htmlspecialchars($_posName ?? '', ENT_QUOTES); ?>" />
