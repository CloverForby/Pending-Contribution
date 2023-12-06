<?php 

$myArrays = array(
	//ACTIVITY TYPE
	'pendingContributionActivity' => array(
		'name' => "pendingContributionActivity",
		'entity' => "OptionValue",
		'params' => array(
			'label' => 'Pending Contribution',
			'name' => 'pendingContributionActivity',
			'option_group_id.name' => 'activity_type',
			'is_active' => true,
			//'value' => '1773',
		),
	),
	//CUSTOM FIELD GROUP
	'pendingContributionFields' => array(
		'name' => "pendingContributionFields",
		'entity' => "CustomGroup",
		'params' => array(
			'title' => 'Pending Contribution Fields',
			'name' => 'pendingContributionFields',
			//'extends_entity_column_value' => '1773',
			'extends_entity_column_value:name' => 'pendingContributionActivity',
			'extends' => 'Activity',
			'style' => 'Inline',
			'is_active' => TRUE,
		),
	),
	//CUSTOM FIELD: AMMOUNT
	'pccfTotalAmt' => array(
		'name' => "pccfTotalAmt",
		'entity' => "CustomField",
		'params' => array(
			'custom_group_id:name' => 'pendingContributionFields',
			'name' => 'pccfTotalAmt',
			'label' => 'Total Ammount',
			'html_type' => 'Text',
			'data_type' => 'Money',
			'is_searchable' => TRUE,
			'is_required' => TRUE,
		),
	),
	//CUSTOM FIELD: DATE 
	'pccfrecieveDate' => array(
		'name' => "pccfrecieveDate",
		'entity' => "CustomField",
		'params' => array(
			'custom_group_id:name' => 'pendingContributionFields',
			'name' => 'pccfrecieveDate',
			'label' => 'Recieve Date',
			'html_type' => 'Select Date',
			'data_type' => 'Date',
			'is_searchable' => TRUE,
		),
	),
	//CUSTOM FIELD : SOURCE
	'pccfSource' => array(
		'name' => "pccfSource",
		'entity' => "CustomField",
		'params' => array(
			'custom_group_id:name' => 'pendingContributionFields',
			'name' => 'pccfSource',
			'label' => 'Source',
			'html_type' => 'Text',
			'data_type' => 'String',
			'is_searchable' => TRUE,
		),
	),
	//CUSTOM FIELD: PAYMENT INSTRUMENT (SELECT)
	'pccfPayInst' => array(
		'name' => "pccfPayInst",
		'entity' => "CustomField",
		'params' => array( 
			'custom_group_id:name' => 'pendingContributionFields',
			'option_group_id.name' => 'payment_instrument',
			'name' => 'pccfPayInst',
			'label' => 'Payment Instrument',
			'html_type' => 'Select',
			'data_type' => 'Int',
			'is_searchable' => TRUE,
			'is_required' => TRUE,
		),
	),
	//SELECT GROUP : FINANCIAL TYPE
	'pendingcont_financialtype' => array(
		'name' => "pendingcont_financialtype",
		'entity' => "OptionGroup",
		'params' => array(
			'name' => 'pendingcont_financialtype',
			'title' => 'Pending Contribution Fields :: Financial Type',
			'option_value_fields' => ['name', 'label', 'description'],
			'is_active' => TRUE,
			'data_type' => 'Integer',
		),
	),
	//CUSTOM FIELD: FINANCIAL TYPE (SELECT)
	'pccfFinType' => array(
		'name' => "pccfFinType",
		'entity' => "CustomField",
		'params' => array(
			'custom_group_id:name' => 'pendingContributionFields',
			'option_group_id.name' => 'pendingcont_financialtype',
			'name' => 'pccfFinType',
			'label' => 'Financial Type',
			'html_type' => 'Select',
			'data_type' => 'Int',
			'is_searchable' => TRUE,
			'is_required' => TRUE,
		),
	),
);
