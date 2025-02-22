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