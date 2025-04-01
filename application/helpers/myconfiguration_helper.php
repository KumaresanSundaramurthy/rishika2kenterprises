<?php defined('BASEPATH') or exit('No direct script access allowed');

function getEmailConfiguration() {

	$EndReturnData = new stdClass();

	$EndReturnData->Name = 'Rishika 2K Enterprises';
	$EndReturnData->FromEmail = 'kumarbtechguru@gmail.com';
	$EndReturnData->ToEmail = 'rishika2kenterprises@gmail.com';
	$EndReturnData->PhoneNumber = +919790831180;
	$EndReturnData->AddPhoneNumber = +919789612478;
	$EndReturnData->WhatsAppPhoneNumber = "whatsapp:+919790831180";
	$EndReturnData->TwilioPhoneNum = "whatsapp:+14842635241";
    
	return $EndReturnData;
	
}

function changeTimeZomeDateFormat($TimeStamp, $TimeZone) {
	
	$DateTime = new DateTime(date('Y-m-d H:i:s', $TimeStamp));
	$DateTime->setTimezone(new DateTimeZone($TimeZone));
	$DateTime = $DateTime->format('d M Y h:i A');
	
	return $DateTime;

}