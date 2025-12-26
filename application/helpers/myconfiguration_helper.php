<?php defined('BASEPATH') or exit('No direct script access allowed');

function getSiteConfiguration() {

	$EndReturnData = new stdClass();

	$EndReturnData->Name = 'Rishika 2K Enterprises';
	$EndReturnData->ShortName = 'R2K Enterprises';
	$EndReturnData->MenuName = 'R2K Global';
	$EndReturnData->RedisName = 'r2k-enterprises';
	$EndReturnData->FromEmail = 'kumarbtechguru@gmail.com';
	$EndReturnData->ToEmail = 'rishika2kenterprises@gmail.com';
	$EndReturnData->PhoneNumber = +919790831180;
	$EndReturnData->AddPhoneNumber = +919789612478;
	$EndReturnData->WhatsAppPhoneNumber = "whatsapp:+919790831180";
	$EndReturnData->TwilioPhoneNum = "whatsapp:+14842635241";
    
	return $EndReturnData;
	
}

function pageResultCount($pageno, $per_page, $total_rows) {

	$pageno = $pageno == 0 ? 1 : $pageno;

    $start = (int)$pageno == 1 ? $pageno : (($pageno-1)*$per_page)+1;
    $end = ($pageno == ceil($total_rows/ $per_page))? $total_rows : (int)$pageno*$per_page;
	
	if($end>$total_rows){
		$end = $total_rows;
	}
	
	return "Showing ".$start." - ".$end." of ".$total_rows." Results";
	
}

function getAWSConfigurationDetails() {

	// r2kenterprises-kumaresan (base64-encode) value
	$ConfigurationData = new stdClass();
	return $ConfigurationData;

}

function changeTimeZomeDateFormat($TimeStamp, $TimeZone, $FormatType = 1) {
	
	$DateTime = new DateTime(date('Y-m-d H:i:s', $TimeStamp));
	$DateTime->setTimezone(new DateTimeZone($TimeZone));
	if($FormatType == 1) {
		$DateTime = $DateTime->format('d M Y');
	} else if($FormatType == 2) {
		$DateTime = $DateTime->format('d M Y h:i A');
	} else {
		$DateTime = $DateTime->format('d M Y');
	}	
	
	return $DateTime;

}

function filterByMainMenuUID($data, $mainMenuUID) {
    return array_values(array_filter($data, function($item) use ($mainMenuUID) {
        return $item->MainMenuUID == $mainMenuUID;
    }));
}

function smartDecimal($number, $maxDecimals = 6, $digReq = false) {
    // Convert to float first to remove unnecessary zeros
    $number = (float) $number;
    // Format with max decimals
    $formatted = number_format($number, $maxDecimals, '.', '');
    if ($digReq) {
        return $formatted;
    }
    // Otherwise trim unnecessary zeros and decimal point
    return rtrim(rtrim($formatted, '0'), '.');
}

function getModuleUIDByName($modules, $name) {

	$filtered = array_filter($modules, function($module) use ($name) {
		return $module->Name === $name;
	});
    
	if (!empty($filtered)) {
		return array_values($filtered)[0]->ModuleUID;
	}

	return 0;

}

function filterViewDataColumns($originalArray, $WhereField) {
    $filteredArray = array_filter($originalArray, function($item) use ($WhereField) {
        return isset($item->$WhereField) && $item->$WhereField == 1;
    });
    return $filteredArray;
}

function updateAttributeString($attributeString, $MPFilterApplicable) {

    // Start with an empty array
    $attributes = [];

    // Extract attributes using regex
    if (!empty($attributeString)) {
        preg_match_all('/(\w+)\s*=\s*"([^"]*)"/', $attributeString, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $attributes[$match[1]] = $match[2];
        }
    }

    // Handle class attribute
    $classList = [];

    if (isset($attributes['class'])) {
        $classList = preg_split('/\s+/', trim($attributes['class']));
    }

    // Add 'text-end' if not present
    if (in_array('text-end', $classList)) {
        $classList[] = 'text-end';
    }

    // Add additional class if MPFilterApplicable is 1
    if ($MPFilterApplicable == 1) {
        $classList[] = 'filter-applicable';
    }

    // Remove duplicates and rebuild class string
    $attributes['class'] = implode(' ', array_unique($classList));

    // Rebuild the full attribute string
    $newAttributeString = '';
    foreach ($attributes as $key => $value) {
        $newAttributeString .= $key . '="' . htmlspecialchars($value) . '" ';
    }

    return trim($newAttributeString);

}

function getPostValue($post, $key, $Type = 'Array', $default = NULL, $allowEmpty = false) {
    if (!array_key_exists($key, $post)) return $default;
    $value = $post[$key];
    if (!$allowEmpty && ($value === '' || $value === NULL)) return $default;
    if (is_array($value)) {
        if($Type == 'Comma') {
            return !empty($value) ? implode(',', $value) : $default;
        }
    }
    return $value;
}