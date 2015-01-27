<?php

// //////////
// validation
// //////////

if(strpos($pkid, "x") < 1) {
	echo "FAIL: Location ID {$pkid} is invalid";
	return false;
}


// //////
// select
// //////

$view = new DataCollector;

// find the cr of item #10 on the list, and use in the following select
$view->locationInhabitant[top10] = DataConnector::selectQuery("
	 SELECT pc.`cr`              AS `cr`
	   FROM t_characters pc
	  WHERE pc.`location_id` = '{$pkid}'
	  ORDER BY pc.`cr` DESC
	  LIMIT 1 OFFSET 10
");
if(!$view->locationInhabitant[top10]) {
	$view->locationInhabitant[top10][cr] = 0;
}

$j = DataConnector::selectQuery("
	 SELECT pc.`id`              AS `id`,
	        pc.`name`            AS `name`,
	        pc.`cr`              AS `cr`,
	        pc.`gender`          AS `gender`,
	        pc.`alignment`       AS `alignment`,
	         r.`short`           AS `race`,
	        GROUP_CONCAT(CONCAT(ccc.`character_class_id`, '&nbsp;', ccc.`level`) SEPARATOR ',&nbsp;') AS `class`
	   FROM t_characters pc
	   JOIN t_races r ON pc.`race_id` = r.`id`
	   JOIN t_character_classes_characters ccc
	     ON pc.`id` = ccc.`character_id`
	    AND ccc.`is_deleted` != 'Yes'
	    AND ccc.`level` > 0
	   JOIN t_locations l
	     ON pc.`location_id` = l.`id`
	  WHERE pc.`location_id` = '{$pkid}'
	    AND pc.`cr` >= {$view->locationInhabitant[top10][cr]}
	  GROUP BY pc.`id`
	  ORDER BY pc.`cr` DESC
");
while($j) {
	$view->locationInhabitant[inhabitant][] = $j;
	$j = DataConnector::selectQuery();
}


// ////////////////////////////
// communicate with parent page
// ////////////////////////////

echo "<script>\n";
echo "buildSection('Encounter')\n";
echo "</script>\n";

?>
