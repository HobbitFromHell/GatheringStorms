<?php

// //////////
// validation
// //////////

if(!($pkid > 0 and $pkid < 65000)) {
	echo "FAIL: Character ID {$pkid} is invalid";
	return false;
}


// //////
// insert
// //////

post_data("t_characters_encounters", "id", "character_id", Array("encounter_id", "is_deleted"));


// //////
// select
// //////

$view = new DataCollector;

// at least one record, even an empty one, must be added to the data collector
$view->characterEncounter[] = DataConnector::selectQuery("
	 SELECT  e.`id`           AS `id`,
	        ce.`encounter_id` AS `encounter_id`,
	         s.`name`         AS `story_name`,
	         c.`name`         AS `chapter_name`,
	         e.`name`         AS `name`,
	         e.`name`         AS `enc_name`,
	         e.`trigger_day`  AS `trigger_day`,
	        te.`name`         AS `trigger_name`,
	         e.`is_active`    AS `is_active`,
	         e.`cr`           AS `cr`,
	         e.`cr_min`       AS `cr_min`,
	         e.`chance`       AS `chance`,
	        IFNULL(l.`name`, e.`location_id`) AS `location`
	   FROM `t_characters_encounters` ce
	   JOIN `t_encounters` e
	     ON e.`id` = ce.`encounter_id`
	   JOIN `t_chapters` c
	     ON e.`chapter_id` = c.`id`
	   JOIN `t_stories` s
	     ON c.`story_id` = s.`id`
	   LEFT JOIN `t_locations` l
	     ON l.`id` = e.`location_id`
	   LEFT JOIN `t_encounters` te
	     ON te.`id` = e.`trigger_encounter_id`
	  WHERE ce.`character_id` = {$pkid}
	    AND ce.`is_deleted` != 'Yes'
	  ORDER BY s.`name`, c.`name`, e.`trigger_encounter_id`
");
while($j = DataConnector::selectQuery()) {
	$view->characterEncounter[] = $j;
}

// build encounter list
$j = DataConnector::selectQuery("
	 SELECT e.`id`   AS `id`,
	        e.`name` AS `name`
	   FROM `t_encounters` e
	  WHERE e.`name` > ''
	  ORDER BY e.`name`
");
while($j) {
		$view->characterEncounter['list'][] = $j;
		$j = DataConnector::selectQuery();
}

?>
