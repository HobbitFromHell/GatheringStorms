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

post_data("t_characters_encounters", "id", "encounter_id", Array("character_id", "is_deleted"));


// //////
// select
// //////

$view->encounterCharacters[] = DataConnector::selectQuery("
	 SELECT pc.`id`              AS `id`,
	        pc.`name`            AS `name`,
	        pc.`cr`              AS `cr`,
	        pc.`gender`          AS `gender`,
	        pc.`alignment`       AS `alignment`,
	         r.`short`           AS `race`,
	        GROUP_CONCAT(CONCAT(ccc.`character_class_id`, '&nbsp;', ccc.`level`) SEPARATOR ',&nbsp;') AS `class`
	   FROM t_characters pc
	   JOIN t_races r
	     ON pc.`race_id` = r.`id`
	   JOIN t_character_classes_characters ccc
	     ON pc.`id` = ccc.`character_id`
	    AND ccc.`is_deleted` != 'Yes'
	    AND ccc.`level` > 0
	   JOIN t_characters_encounters ce
	     ON ce.`character_id` = pc.`id`
	  WHERE ce.`encounter_id` = '{$pkid}'
	    AND ce.`is_deleted` != 'Yes'
	  GROUP BY pc.`id`
	  ORDER BY pc.`cr` DESC
");
while($j = DataConnector::selectQuery()) {
	$view->encounterCharacters[] = $j;
}

// at least one record, even an empty one, must be added to the data collector
$view->encounterCast[] = DataConnector::selectQuery("
	 SELECT ce.`id`              AS `id`,
	        pc.`name`            AS `name`,
	        pc.`cr`              AS `cr`,
	        pc.`gender`          AS `gender`,
	        pc.`alignment`       AS `alignment`,
	         r.`short`           AS `race`,
	        GROUP_CONCAT(CONCAT(ccc.`character_class_id`, '&nbsp;', ccc.`level`) SEPARATOR ',&nbsp;') AS `class`
	   FROM t_characters pc
	   JOIN t_races r
	     ON pc.`race_id` = r.`id`
	   JOIN t_character_classes_characters ccc
	     ON pc.`id` = ccc.`character_id`
	    AND ccc.`is_deleted` != 'Yes'
	    AND ccc.`level` > 0
	   JOIN t_characters_encounters ce
	     ON ce.`character_id` = pc.`id`
	  WHERE ce.`encounter_id` = '{$pkid}'
	    AND ce.`is_deleted` != 'Yes'
	  GROUP BY pc.`id`
	  ORDER BY pc.`cr` DESC
");
while($j = DataConnector::selectQuery()) {
	$view->encounterCast[] = $j;
}

// build character list
$j = DataConnector::selectQuery("
	 SELECT pc.`id`   AS `id`,
	        pc.`name` AS `name`
	   FROM `t_characters` pc
	  WHERE pc.`name` > '?'
	  ORDER BY pc.`name`
");
while($j) {
		$view->encounterCast['list'][] = $j;
		$j = DataConnector::selectQuery();
}

// ////////////////////////////
// communicate with parent page
// ////////////////////////////

echo "<script>\n";
echo "buildSection('Map')\n";
echo "</script>\n";

?>
