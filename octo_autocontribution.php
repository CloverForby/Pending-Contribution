<?php
//basic functions i created so i don't have to copy and paste all the time v
require_once 'melfunctions.php';
require_once 'arrays.php';
// phpcs:disable
use CRM_OctoAutocontribution_ExtensionUtil as E;
// phpcs:enable

//big array, stores all entitys that need to be created (activity type, custom field, etc) for cleanliness
global $autocon_arrays;
$autocon_arrays = $myArrays;

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function octo_autocontribution_civicrm_config(&$config): void {
  _octo_autocontribution_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function octo_autocontribution_civicrm_install(): void {
  _octo_autocontribution_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */

function octo_autocontribution_civicrm_disable(): void {
	global $autocon_arrays;
	//goes through entire array
	foreach ($autocon_arrays as $entity){
		//checks if entity exists
		$check = autocon_checkIfExists($entity['name'], $entity['entity']);
		if ($check){
			//if exists, deletes entity
			autocon_deleteEntity($entity['name'], $entity['entity']);
		}
	}
}

function octo_autocontribution_civicrm_enable(): void {
	global $autocon_arrays;
	//goes through entire array
	foreach($autocon_arrays as $entity){
		//check if entity exists
		$check = autocon_checkIfExists($entity['name'], $entity['entity']);
		if ($check){
			//if it exists
			echo 'What? It already exists???!';
		} else {
			//if it doesn't exist, the entity is created
			autocon_createEntity($entity['entity'], $entity['params']);
		}
	}
	//get financial types
	$finTypeArray = civicrm_api4('FinancialType', 'get', []);
	foreach($finTypeArray as $type){
		$results = civicrm_api4('OptionValue', 'create', [
		  'values' => [
			'option_group_id.name' => 'pendingcont_financialtype',
			'label' => print_r($type['name'], true),
			'value' => print_r($type['id'], true),
			'name' => 'finType :: ' . print_r($type['name'], true),
			'is_active' => TRUE,
		  ],
		]);
	}
}

//this function occurs when after a change to the db has been commited (more info: https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_postCommit/)
function octo_autocontribution_civicrm_postCommit($op, $objectName, $objectId, &$objectRef): void{
	//will go through an infinite loop without this vv idk what it does
	static $isAlreadyUpdating = false;
	//check if the object being interacted with is called an "Activity" and the operation is edit
	if (!$isAlreadyUpdating && $objectName == 'Activity' && $op == 'edit'){
		$isAlreadyUpdating = true;
		//get the activity_type_id and status of the activity type
		$activityType = $objectRef->activity_type_id;
		$status = $objectRef->status_id;
		//get "pending contribution" activity ID
		$getActivityType = autocon_getActivityID();
		$getCompleteID = autocon_getCompletedID();
		//check if activity type is "Pending Contribution"'s and the activity status was updated to 2 (ID of completed)
		if ($activityType == $getActivityType && $status == $getCompleteID){
			//get contact_id of Activity type (for some reason doesn't list them in the actual activity, but rather in ActivityContact table)
			$getContact = civicrm_api4('ActivityContact', 'get', [
			  'where' => [
				['activity_id', '=', $objectId],
				['record_type_id:name', '=', 'Activity Targets'],
			  ],
			]);
			$contactID = $getContact[0]['contact_id'];
			//Get custom fields of Pending Contribution
			$getFields = civicrm_api4('Activity', 'get', [
			  'select' => [
				'pendingContributionFields.pccfTotalAmt',
				'pendingContributionFields.pccfPayInst',
				'pendingContributionFields.pccfSource',
				'pendingContributionFields.pccfrecieveDate',
				'pendingContributionFields.pccfFinType',
			  ],
			  'where' => [
				['activity_type_id', '=', $getActivityType],
				['id', '=', $objectId],
			  ],
			]);
			// Stores each field in variables
			$totalamnt = $getFields[0]['pendingContributionFields.pccfTotalAmt'];
			$payInst = $getFields[0]['pendingContributionFields.pccfPayInst'];
			$source = $getFields[0]['pendingContributionFields.pccfSource'];
			$recieveDate = $getFields[0]['pendingContributionFields.pccfrecieveDate'];
			$financialType = $getFields[0]['pendingContributionFields.pccfFinType'];
			 //Create new array for the new contribution's parameters
			$newContArrays = array(
				'contact_id' => $contactID,
				'total_amount' => $totalamnt,
				'currency:name' => 'SGD', 
				'payment_instrument_id' => $payInst,
				'receive_date' => $recieveDate,
				'source' => $source,
				'contribution_status_id' => 1,
				'financial_type_id' => $financialType,
			);
			//finally, creates a new contribution using the array established above
			autocon_createEntity('Contribution', $newContArrays);
		}
		$isAlreadyUpdating = false;
	}
	//if financial type was added
	if($objectName == 'FinancialType' && $op == 'create'){
		$financialType = civicrm_api4('FinancialType', 'get', [
		  'where' => [
			['id', '=', $objectId],
		  ],
		]);
		$results = civicrm_api4('OptionValue', 'create', [
		  'values' => [
			'option_group_id.name' => 'pendingcont_financialtype',
			'label' => print_r($financialType[0]['name'], true),
			'value' => print_r($financialType[0]['id'], true),
			'is_active' => print_r($financialType[0]['is_active'], true),
			'name' => 'finType :: ' . print_r($financialType[0]['name'], true),
			'is_active' => TRUE,
		  ],
		]);
	}
	//if financial type is deleted
	if($objectName == 'FinancialType' && $op == 'delete'){
		$financialType = civicrm_api4('FinancialType', 'get', [
		  'where' => [
			['id', '=', $objectId],
		  ],
		]);
		if (!empty($financialType)){
			$results = civicrm_api4('OptionValue', 'delete', [
			  'where' => [
				['option_group_id:name', '=', 'pendingcont_financialtype'],
				['value', '=', $objectId],
			  ],
			]);
			if ($results['values_deleted'] > 0) {
				Civi::log()->debug("OptionValue deleted successfully.");
			} else {
				Civi::log()->debug("OptionValue deletion failed.");
			}
		} else {
			Civi::log()->debug("FinancialType not found.");
		}
	}
	//if financial type was updated
	if(!$isAlreadyUpdating && $objectName == 'FinancialType'){
		$isAlreadyUpdating = true;
		if ($op = 'update'){
			$results = civicrm_api4('OptionValue', 'update', [
					'values' => [
						'label' => $objectRef ->name,
						'is_active' => $objectRef->is_active,
					],
					'where' => [
						['option_group_id:name', '=', 'pendingcont_financialtype'],
						['value', '=', $objectId],
					],
				]);
			$isAlreadyUpdating = false;
		}
		$isAlreadyUpdating = false;
	}
}
