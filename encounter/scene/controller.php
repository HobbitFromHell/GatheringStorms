<?php

// //////////
// validation
// //////////

if (!($pkid > 0 and $pkid < 65000)) {
	echo "FAIL: Encounter ID {$pkid} is invalid";
	return false;
}

// //////
// insert
// //////

if(post_data("t_encounters", "id", "id", Array("location_id", "chapter_id", Array("scene", "name"), "cr_min", "cr", "is_active", "chance", "completed_day", "trigger_encounter_id", "trigger_day", "description", "stat_block"))) {
	echo "<script>\n";
	echo "buildSection('Story')\n";
	echo "</script>\n";
	return;
}


// //////
// select
// //////

$view = new DataCollector;
$view->encounterScene = DataConnector::selectQuery("
	 SELECT e.`id`                   AS `id`,
	        e.`chapter_id`           AS `chapter_id`,
	        e.`name`                 AS `scene`,
	       te.`name`                 AS `trigger_name`,
	        e.`description`          AS `description`,
	        e.`stat_block`           AS `stat_block`,
	        e.`location_id`          AS `location_id`,
	          IFNULL(l.`name`, e.`location_id`) AS `location`,
	        e.`is_active`            AS `is_active`,
	        e.`cr`                   AS `cr`,
	        e.`cr_min`               AS `cr_min`,
	        e.`chance`               AS `chance`,
	        e.`trigger_encounter_id` AS `trigger_encounter_id`,
	        e.`trigger_day`          AS `trigger_day`,
	        e.`completed_day`        AS `completed_day`
	   FROM `t_encounters` e
	   LEFT JOIN `t_locations` l
	     ON e.`location_id` = l.`id`
	   LEFT JOIN `t_encounters` te
	     ON e.`trigger_encounter_id` = te.`id`
	  WHERE e.`id` = {$pkid}
");

// build is_active list
//	$view->encounterScene[alignment] = $view->getEnum("t_characters", "alignment", $view->encounterScene[alignment]);
$view->encounterScene[is_active] = $view->setList($view->encounterScene[is_active], array("No","Yes"));

// build encounter list
$j = DataConnector::selectQuery("
	 SELECT e.`id`          AS `id`,
	        e.`name`        AS `name`,
	        e.`chapter_id`  AS `chapter_id`
	   FROM `t_encounters` e
	  WHERE e.`chapter_id` = {$view->encounterScene[chapter_id]}
	  ORDER BY e.`name`
");

// add a blank option to remove trigger
$view->encounterScene[trigger]['list'][1][id] = 0;
$view->encounterScene[trigger]['list'][1][name] = "";
$varCurrentOption = 0;
while($j) {
	$view->encounterScene[trigger]['list'][] = $j;
	// mark the triggering encounter
	if($j[id] == $view->encounterScene[trigger_encounter_id]) {
		$varCurrentOption = 1;
	}
	$j = DataConnector::selectQuery();
}
$view->encounterScene[trigger][id] = $view->encounterScene[trigger_encounter_id];
$view->encounterScene[trigger][name] = $view->encounterScene[trigger_name];
// force current value to be an option if it has not shown up already (otherwise it will be stripped if the chapter is different)
if(!$varCurrentOption) {
	$view->encounterScene[trigger]['list'][0][id] = $view->encounterScene[trigger_encounter_id];
	$view->encounterScene[trigger]['list'][0][name] = $view->encounterScene[trigger_name];
}

// build chapter list
$j = DataConnector::selectQuery("
	 SELECT c.`id` AS `id`,
	        CONCAT(s.`name`, ' : ', c.`name`) AS `name`
	   FROM `t_chapters` c, `t_stories` s
	  WHERE c.`story_id` = s.`id`
	  ORDER BY s.`name`, c.`name`
");
while($j) {
	$view->encounterScene[chapter]['list'][] = $j;
	if($view->encounterScene[chapter_id] == $j[id]) {
		$view->encounterScene[chapter][name] = $j[name];
	}
	$j = DataConnector::selectQuery();
}

// build triggers list
$j = DataConnector::selectQuery("
		 SELECT e.`id`        AS `id`,
		        e.`name`      AS `name`
		   FROM `t_encounters` e
		  WHERE e.`trigger_encounter_id` = {$view->encounterScene[id]}
");
while($j) {
	$view->encounterScene[triggers] .= "<a href=\"/encounter/?id={$j[id]}\">{$j[name]}</a>, ";
	$j = DataConnector::selectQuery();
}

// ////////////////////////////
// communicate with parent page
// ////////////////////////////

echo "<script>\n";
echo "buildSection('Cast')\n";
echo "</script>\n";

?>
