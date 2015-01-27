<?php

$varPrompt = "";

// ////////
// validate
// ////////

if ($pkid != 0) {
	echo "FAIL: Character ID {$pkid} is invalid";
	return false;
}


// //////
// insert
// //////

// custom set of inserts to add new character
if (isset($_POST[name]) and isset($_POST[favoured_class])) {
	$postName = sanitize($_POST[name]);
	$postFavouredClass = sanitize($_POST[favoured_class]);
	$postOrganization = sanitize($_POST[organization]);
	$postMasterID = sanitize($_POST[master]);
	$postLocationID = sanitize($_POST[location]);
	$postEncounterID = sanitize($_POST[encounter_id]);

	$varNewCharID = DataConnector::updateQuery("
		 INSERT INTO t_characters (`name`, `location_id`)
		 VALUES ('{$postName}', '{$postLocationID}')
	");
	DataConnector::updateQuery("
		 INSERT INTO t_character_classes_characters (`character_id`, `character_class_id`, `level`)
		 VALUES ('{$varNewCharID}', '{$postFavouredClass}', 1)
	");
	if($postOrganization and $postMasterID) {
		DataConnector::updateQuery("
			 INSERT INTO t_characters_organizations (`character_id`, `organization_id`, `master_id`)
			 VALUES ('{$varNewCharID}', {$postOrganization}, {$postMasterID})
		");
	}
	if($postEncounterID > 0) {
		DataConnector::updateQuery("
			 INSERT INTO t_characters_encounters (`character_id`, `encounter_id`)
			 VALUES ('{$varNewCharID}', {$postEncounterID})
		");
	}

	// redirect to view/edit page
	echo("<script>window.location.assign(\"/character/?id={$varNewCharID}\")</script>");
	$varPrompt = "<br><i>Added. If you are not redirect to the detail page, click <a href=\"/character/?id={$varNewCharID}\">here</a> to edit the character.</i>";
}


// //////
// select
// //////

$j = DataConnector::selectQuery("
	 SELECT cc.`id`   AS `id`,
	        cc.`name` AS `name`
	   FROM t_character_classes cc
	  WHERE cc.`is_deleted` != 'Yes'
	  ORDER BY cc.`name`
");
while($j) {
	$view->characterAdd[favoured_class]['list'][] = $j;
	$j = DataConnector::selectQuery();
}
$view->characterAdd[favoured_class][name] = "";

?>
