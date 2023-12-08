<?php

require_once 'octo_autocontribution.civix.php';
// phpcs:disable
use CRM_OctoAutocontribution_ExtensionUtil as E;

function autocon_checkIfExists(string $entityName, string $entityType) : bool{
	$result = civicrm_api4(strval($entityType), 'get', [
		  'where' => [
			['name', '=', strval($entityName)],
		  ],
		  'checkPermissions' => FALSE,
	]);
	
	if (isset($result[0]) && $result[0] > 0) {
		return TRUE;
	} else {
		return FALSE;
	};
}

function autocon_createEntity(string $entityType, array $entityValues){
	civicrm_api4(strval($entityType), 'create', ['values' => $entityValues]);
}

function autocon_deleteEntity(string $entityName, string $entityType){
	civicrm_api4(strval($entityType), 'delete', [
		  'where' => [
			['name', '=', strval($entityName)],
		  ],
		  'checkPermissions' => FALSE,
		]);
}
//TODO: check newly created activity and return value INT for id

function autocon_getActivityID() : string{
	$optionValues = civicrm_api4('OptionValue', 'get', [
	  'select' => [
		'value',
	  ],
	  'where' => [
		['name', '=', 'pendingContributionActivity'],
	  ],
	]);
	return  $optionValues[0]["value"];
}

function autocon_getCompletedID() : string{
	$optionValues = civicrm_api4('OptionValue', 'get', [
	  'select' => [
		'value',
	  ],
	  'where' => [
		['option_group_id:name', '=', 'activity_status'],
		['label', '=', 'Completed'],
	  ],
	]);
	return  $optionValues[0]["value"];
}

function autocon_getPaymentMethod() : string{
	$optionGroups = civicrm_api4('OptionGroup', 'get', [
	  'where' => [
		['name', '=', 'payment_instrument'],
	  ],
	]);
	return  $optionGroups[0]["id"];
}