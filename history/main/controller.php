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

post_data("t_history", "id", "id", Array("source", "name", "start_year", "end_year", "month", "day", "location_id", "region_id", "description", "is_deleted"));


// //////
// select
// //////

$view = new DataCollector;
$view->historyMain = DataConnector::selectQuery("
	 SELECT h.`id`          AS `id`,
	 	      h.`source`      AS `source`,
	 	      h.`name`        AS `name`,
	        h.`is_circa`    AS `is_circa`,
	        h.`start_year`  AS `start_year`,
	        h.`end_year`    AS `end_year`,
	        h.`month`       AS `month`,
	        h.`day`         AS `day`,
	        h.`location_id` AS `location_id`,
	        l.`name`        AS `location`,
	        h.`region_id`   AS `region_id`,
	        h.`description` AS `description`,
	        h.`is_deleted`  AS `is_deleted`
	   FROM   `t_history` h
	   LEFT JOIN `t_locations` l
	     ON l.`id` = h.`location_id`
	  WHERE h.`id` = {$pkid}
");

// build is_active list
$view->historyMain['month'] = $view->getEnum("t_history", "month", $view->historyMain['month']);
$view->historyMain['source'] = $view->getEnum("t_history", "source", $view->historyMain['source']);
$view->historyMain['is_deleted'] = $view->setList($view->historyMain['is_deleted'], array("No","Yes"));

// build region list
// blank option
$tmp = "";
$tmp['id'] = 0;
$tmp['name'] = "";
$view->historyMain['region']['list'][] = $tmp;
$view->historyMain['region']['name'] = "";

$j = DataConnector::selectQuery("
	 SELECT r.`id`          AS `id`,
	        r.`name`        AS `name`
	   FROM `t_regions` r
	  ORDER BY r.`name`
");
while($j) {
	if($j['id'] == $view->historyMain['region_id']) {
		$view->historyMain['region']['name'] = $j['name'];
	}
	$view->historyMain['region']['list'][] = $j;
	$j = DataConnector::selectQuery();
}


// ////////////////////////////
// communicate with parent page
// ////////////////////////////

echo "<script>\n";
echo "buildSection('Characters')\n";
echo "</script>\n";

?>
