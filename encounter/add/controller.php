<?php

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

// custom set of inserts to add new encounter
// minimum of name and chapter needed
if (isset($_POST[name]) and isset($_POST[chapter])) {
	$postName = sanitize($_POST[name]);
	$postChapter = sanitize($_POST[chapter]);
	$postLocationID = sanitize($_POST[location_id]);
	$postCharacterID = sanitize($_POST[character_id]);
	if(!$postLocationID) {
		$postLocationID = "0x0";
	}

	$varNewEncounterID = DataConnector::updateQuery("
		 INSERT INTO t_encounters (`name`, `chapter_id`, `location_id`)
		 VALUES ('{$postName}', {$postChapter}, '{$postLocationID}')
	");

	// if character id is included, link up the new encounter
	if($postCharacterID > 0) {
		$varNewCharacterEncounterID = DataConnector::updateQuery("
			 INSERT INTO t_characters_encounters (`encounter_id`, `character_id`)
			 VALUES ('{$varNewEncounterID}', '{$postCharacterID}')
		");
	}

	// redirect to view/edit page
	echo("<script>window.location.assign(\"/encounter/?id={$varNewEncounterID}\")</script>");
	$varPrompt = "<br><i>Added. If you are not redirect to the detail page, click <a href=\"/encounter/?id={$varNewEncounterID}\">here</a> to edit the encounter.</i>";
}


// //////
// select
// //////

$view = new DataCollector;

// build chapter list
$j = DataConnector::selectQuery("
	 SELECT c.`id` AS `id`,
	          CONCAT(s.`name`, ' : ', c.`name`) AS `name`
	   FROM `t_chapters` c, `t_stories` s
	  WHERE c.`story_id` = s.`id`
	  ORDER BY s.`name`, c.`name`
");
while($j) {
	$view->encounterAdd[chapter]['list'][] = $j;
	$j = DataConnector::selectQuery();
}
$view->encounterAdd[chapter][name] = "";

?>
