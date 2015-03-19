<?php

// //////////
// validation
// //////////

if (!($pkid > 0 and $pkid < 65000)) {
	echo "FAIL: History ID {$pkid} is invalid";
	return false;
}


// //////
// insert
// //////

post_data("t_characters_history", "id", "history_id", Array("character_id", "is_deleted"));


// //////
// select
// //////

$view->historyCharacters[] = DataConnector::selectQuery("
	 SELECT ch.`id`              AS `id`,
	        pc.`id`              AS `character_id`,
	        pc.`name`            AS `name`,
	        pc.`cr`              AS `cr`,
	        pc.`gender`          AS `gender`,
	        pc.`alignment`       AS `alignment`,
	        ch.`is_deleted`      AS `is_deleted`,
	         r.`short`           AS `race`,
	        GROUP_CONCAT(CONCAT(ccc.`character_class_id`, '&nbsp;', ccc.`level`) SEPARATOR ',&nbsp;') AS `class`
	   FROM `t_characters_history` ch
	   JOIN `t_characters` pc
	     ON ch.`character_id` = pc.`id`
	   JOIN t_races r
	     ON pc.`race_id` = r.`id`
	   JOIN t_character_classes_characters ccc
	     ON pc.`id` = ccc.`character_id`
	    AND ccc.`is_deleted` != 'Yes'
	    AND ccc.`level` > 0
	  WHERE ch.`history_id` = '{$pkid}'
	    AND ch.`is_deleted` != 'Yes'
	  GROUP BY pc.`id`
	  ORDER BY pc.`cr` DESC
");
while($j = DataConnector::selectQuery()) {
	$view->historyCharacters[] = $j;
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
	$view->historyCharacters['list'][] = $j;
	$j = DataConnector::selectQuery();
}

// ////////////////////////////
// communicate with parent page
// ////////////////////////////

echo "<script>\n";
echo "buildSection('Organizations')\n";
echo "</script>\n";

?>
