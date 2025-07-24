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

function changeTimeZomeDateFormat($TimeStamp, $TimeZone) {
	
	$DateTime = new DateTime(date('Y-m-d H:i:s', $TimeStamp));
	$DateTime->setTimezone(new DateTimeZone($TimeZone));
	$DateTime = $DateTime->format('d M Y h:i A');
	
	return $DateTime;

}

function filterByMainMenuUID($data, $mainMenuUID) {
    return array_values(array_filter($data, function($item) use ($mainMenuUID) {
        return $item->MainMenuUID == $mainMenuUID;
    }));
}

function smartDecimal($number) {
    // Convert to float first to remove unnecessary zeros
    $number = (float) $number;

    // Remove trailing zeros while keeping necessary decimal precision
    return rtrim(rtrim(number_format($number, 6, '.', ''), '0'), '.');
	
}

function getModuleUIDByName($modules, $name) {

	$filtered = array_filter($modules, function($module) use ($name) {
		return $module->Name === $name;
	});

	// If found, return the first match's ModuleUID
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