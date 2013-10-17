<?php
/**
 * TimedNotice
 *
 * @package timednotices
 * @author shea@silverstripe.com.au
 **/
class TimedNotice extends DataObject implements PermissionProvider {

	public static $singular_name 	= 'Timed Notice';
	public static $plural_name	 	= 'Timed Notices';
	
	public static $db = array(
		'Message' 		=> 'Text',
		'MessageType' 	=> 'Varchar',
		'StartTime' 	=> 'SS_DateTime',
		'EndTime' 		=> 'SS_DateTime',
		'CanViewType' 	=> "Enum('LoggedInUsers, OnlyTheseUsers', 'LoggedInUsers')"
	);

	public static $many_many = array(
		'ViewerGroups' 	=> 'Group'
	);

	public static $defaults = array(
		'CanViewType' 	=> 'LoggedInUsers'
	);

	public static $summary_fields = array(
		'StartTime' 	=> "Start Time",
		'EndTime'		=> "End Time",
		'StatusLabel'	=> "Status",
		'MessageType' 	=> "Message Type",
		'Message' 		=> "Message",
	);

	public static $searchable_fields = array(
		'MessageType'
	);

	public static $message_types = array(
		'good',
		'warning',
		'bad'
	);

	public static $status_options = array(
		'Current',
		'Future',
		'Expired'
	);


	public function getCMSFields(){
		$fields = parent::getCMSFields();

		$fields->removeFieldFromTab('Root','ViewerGroups');

		$viewersOptionsSource["LoggedInUsers"] = _t('TimedNotice.ACCESSLOGGEDIN', "Logged-in users");
		$viewersOptionsSource["OnlyTheseUsers"] = _t('TimedNotice.ACCESSONLYTHESE', "Only these people (choose from list)");
		$fields->addFieldToTab('Root.Main', $canViewTypeField = OptionsetField::create(
			"CanViewType", 
			_t('TimedNotice.ACCESSHEADER', "Who should see this notice?"),
			$viewersOptionsSource
		));

		$groupsMap = Group::get()->map('ID', 'Breadcrumbs')->toArray();
		asort($groupsMap);
		$fields->addFieldToTab('Root.Main', $viewerGroupsField = ListboxField::create("ViewerGroups", _t('TimedNotice.VIEWERGROUPS', "Only people in these groups"))
			->setMultiple(true)
			->setSource($groupsMap)
			->setAttribute(
				'data-placeholder', 
				_t('TimedNotice.GroupPlaceholder', 'Click to select group')
		));

		if(class_exists('DisplayLogicCriteria')){
			$viewerGroupsField->displayIf("CanViewType")->isEqualTo("OnlyTheseUsers");
		}
		
		$fields->addFieldToTab('Root.Main', DropdownField::create(
			'MessageType', 
			'Message Type', 
			ArrayLib::valuekey($this->config()->get('message_types'))
		), 'Message');

		$fields->addFieldToTab(
			'Root.Main', 
			ReadonlyField::create(
				'TZNote', 'Note', sprintf(_t('TimedNotice.TZNote', 'Your dates and times should be based on the server timezone: %s'), date_default_timezone_get())),
			'StartTime'
		);

		$start 	= $fields->dataFieldByName('StartTime');
		$end 	= $fields->dataFieldByName('EndTime');
		
		$start->getDateField()->setConfig('showcalendar',true);
		$end->getDateField()->setConfig('showcalendar',true);

		return $fields;
	}


	public function providePermissions(){
		return array(
			'TIMEDNOTICE_EDIT' => array(
				'name' => 'Edit a Timed Notice',
				'category' => 'Timed Notices',
			),
			'TIMEDNOTICE_DELETE' => array(
				'name' => 'Delete a Timed Notice',
				'category' => 'Timed Notices',
			),
			'TIMEDNOTICE_CREATE' => array(
				'name' => 'Create a Timed Notice',
				'category' => 'Timed Notices'
			)
		);
	}


	public function canView($member = null){
		return true;
	}

	public function canEdit($member = null) {
		return Permission::check('ADMIN') || Permission::check('TIMEDNOTICE_EDIT');
	}

	public function canDelete($member = null) {
		return Permission::check('ADMIN') || Permission::check('TIMEDNOTICE_DELETE');
	}

	public function canCreate($member = null) {
		return Permission::check('ADMIN') || Permission::check('TIMEDNOTICE_CREATE');
	}


	public function getStatusLabel(){
		$now = date('Y-m-d H:i:s');
		if($this->StartTime > $now){
			return 'Future';
		}elseif($this->EndTime && $this->EndTime <= $now){
			return 'Expired';
		}else{
			return 'Current';
		}
	}


	public function getCMSValidator(){
		return RequiredFields::create(array(
			'Message',
			'StartTime'
		));
	}
}
