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

post_data("t_history_organizations", "id", "history_id", Array("organization_id", "is_deleted"));


// //////
// select
// //////

$view->historyOrganizations[] = DataConnector::selectQuery("
	 SELECT ho.`id`              AS `id`,
	         o.`id`              AS `organization_id`,
	         o.`name`            AS `name`,
	         o.`location_id`     AS `location_id`
	   FROM `t_history_organizations` ho
	   JOIN `t_organizations` o
	     ON  o.`id` = ho.`organization_id`
	  WHERE ho.`history_id` = '{$pkid}'
	    AND ho.`is_deleted` != 'Yes'
	  ORDER BY o.`name`
");
while($j = DataConnector::selectQuery()) {
	$view->historyOrganizations[] = $j;
}

// build character list
$j = DataConnector::selectQuery("
	 SELECT o.`id` AS `id`,
	        CONCAT(o.`name`, IF(l.`id` IS NULL, '', CONCAT(' (', IF(l.`name` > '?', l.`name`, o.`location_id`), ')'))) AS `name`
	   FROM `t_organizations` o
	   LEFT JOIN `t_locations` l
	     ON o.`location_id` = l.`id`
	  ORDER BY o.`location_id`, o.`name`
");
while($j) {
		$view->historyOrganizations['list'][] = $j;
		$j = DataConnector::selectQuery();
}

?>
