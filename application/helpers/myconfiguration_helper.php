<?php defined('BASEPATH') or exit('No direct script access allowed');

function getSiteConfiguration() {

	$EndReturnData = new stdClass();

	$EndReturnData->Name = 'Rishika 2K Enterprises';
	$EndReturnData->ShortName = 'R2K Enterprises';
	$EndReturnData->MenuName = 'R2K Global';
	$EndReturnData->FromEmail = 'kumarbtechguru@gmail.com';
	$EndReturnData->ToEmail = 'rishika2kenterprises@gmail.com';
	$EndReturnData->PhoneNumber = +919790831180;
	$EndReturnData->AddPhoneNumber = +919789612478;
	$EndReturnData->WhatsAppPhoneNumber = "whatsapp:+919790831180";
	$EndReturnData->TwilioPhoneNum = "whatsapp:+14842635241";
    
	return $EndReturnData;
	
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