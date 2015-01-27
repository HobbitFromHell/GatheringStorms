<?php

// //////////
// validation
// //////////

if(strpos($pkid, "x") < 1) {
	echo "FAIL: Location ID {$pkid} is invalid";
	return false;
}


// //////
// insert
// //////

//post_data("t_characters_encounters", "id", "character_id", Array("encounter_id", "is_deleted"));


// //////
// select
// //////

$view = new DataCollector;

$j = DataConnector::selectQuery("
	 SELECT  s.`name`                 AS `story_name`,
	         c.`name`                 AS `chapter_name`,
	         e.`name`                 AS `name`,
	         e.`id`                   AS `id`,
	         e.`trigger_day`          AS `trigger_day`,
	         e.`trigger_encounter_id` AS `trigger_encounter_id`,
	        te.`name`                 AS `trigger_name`,
	         e.`is_active`            AS `is_active`,
	         e.`cr`                   AS `cr`,
	         e.`cr_min`               AS `cr_min`,
	         e.`chance`               AS `chance`
	   FROM t_encounters e
	   LEFT JOIN t_chapters c
	     ON c.`id` = e.`chapter_id`
	   LEFT JOIN t_stories s
	     ON s.`id` = c.`story_id`
	   LEFT JOIN t_encounters te
	     ON e.`trigger_encounter_id` = te.`id`
	  WHERE e.`location_id` = '{$pkid}'
	  ORDER BY s.`name`, c.`name`, e.`trigger_day`
");
//	    AND e.`is_active` = 'Yes'

while($j) {
	$view->locationEncounter[] = $j;
	$j = DataConnector::selectQuery();
}

echo "<script>\n";
echo "buildSection('Map')\n";
echo "</script>\n";

?>
