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


// //////
// select
// //////

$view = new DataCollector;

$view->locationHistory[] = DataConnector::selectQuery("
		 SELECT h.`id`                   AS `id`,
		        h.`name`                 AS `name`,
		        h.`start_year`           AS `start_year`,
		        h.`end_year`             AS `end_year`,
		        h.`month`                AS `month`,
		        h.`day`                  AS `day`
		   FROM `t_history` h
		   LEFT JOIN `t_locations` l
		     ON l.`id` = h.`location_id`
		   LEFT JOIN `t_regions` r1
		     ON r1.`id` = h.`region_id`
		   LEFT JOIN `t_regions` r2
		     ON r2.`id` = h.`region2_id`
		  WHERE h.`start_year` < 1371
		    AND h.`is_deleted` != 'Yes'
		    AND h.`location_id` = '{$pkid}'
		  ORDER BY h.`start_year` DESC, h.`day` DESC
		  LIMIT 10
");

while($j = DataConnector::selectQuery()) {
	$view->locationHistory[] = $j;
}

?>
