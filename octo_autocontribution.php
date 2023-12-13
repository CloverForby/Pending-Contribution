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
			'label' => $type['name'],
			'value' => $type['id'],
			'name' => 'finType :: ' . $type['name'],
			'is_active' => $type['is_active'],
		  ],
		]);
	}
}

//this function occurs when after a change to the db has been commited (more info: https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_postCommit/)
function octo_autocontribution_civicrm_postCommit($op, $objectName, $objectId, &$objectRef): void{
	if ($objectName == 'Activity' && $op == 'edit'){
		
		$activityType = $objectRef->activity_type_id;
		$status = $objectRef->status_id;
		$recieveDate = $objectRef->created_date;
		$getActivityType = autocon_getActivityID();
		$getCompleteID = autocon_getCompletedID();
		
		if ($activityType == $getActivityType && $status == $getCompleteID){
			$getContact = civicrm_api4('ActivityContact', 'get', [
			  'where' => [
				['activity_id', '=', $objectId],
				['record_type_id:name', '=', 'Activity Targets'],
			  ],
			]);
			$contactID = $getContact[0]['contact_id'];
			$getFields = civicrm_api4('Activity', 'get', [
			  'select' => [
				'pendingContributionFields.pccfTotalAmt','pendingContributionFields.pccfPayInst','pendingContributionFields.pccfSource','pendingContributionFields.pccfFinType',
			  ],
			  'where' => [
				['activity_type_id', '=', $getActivityType],
				['id', '=', $objectId],
			  ],
			]);
			// Stores each field in variables
			 //Create new array for the new contribution's parameters
			$newContArrays = array(
				'contact_id' => $contactID,
				'total_amount' => $getFields[0]['pendingContributionFields.pccfTotalAmt'],
				'currency:name' => 'SGD', 
				'payment_instrument_id' => $getFields[0]['pendingContributionFields.pccfPayInst'],
				'recieve_date' => $recieveDate,
				'source' =>  $getFields[0]['pendingContributionFields.pccfSource'],
				'contribution_status_id' => 1,
				'financial_type_id' => $getFields[0]['pendingContributionFields.pccfFinType'],
			);
			autocon_createEntity('Contribution', $newContArrays);
		}
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
			'label' => $financialType[0]['name'],
			'value' => $financialType[0]['id'],
			'is_active' => $financialType[0]['is_active'],
			'name' => 'finType :: ' . $financialType[0]['name'],
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
	if($objectName == 'FinancialType'){
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
		}
	}
}
