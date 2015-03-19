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

// post_data("t_characters_organizations", "id", "character_id", Array(Array("organization", "organization_id"), "title", "master_id", "is_deleted"));


// //////
// select
// //////

$view = new DataCollector;

$j = DataConnector::selectQuery("
	 SELECT  h.`id`          AS `id`,
	 	       h.`name`        AS `name`,
	         h.`source`      AS `source`,
	         h.`start_year`  AS `start_year`,
	         h.`end_year`    AS `end_year`,
	         h.`month`       AS `month`,
	         h.`day`         AS `day`,
	         h.`description` AS `description`,
	         h.`is_circa`    AS `is_circa`
	   FROM    `t_history` h
	   JOIN    `t_characters_history` ch
	     ON ch.`history_id` = h.`id`
	    AND ch.`character_id` = {$pkid}
	    AND ch.`is_deleted` != 'Yes'
	  WHERE h.`is_deleted` != 'Yes'
	  ORDER BY h.`start_year` DESC, h.`month` DESC
	  LIMIT 10
");
while($j) {
	$view->characterHistory[] = $j;
	$j = DataConnector::selectQuery();
}


// ////////////////////////////
// communicate with parent page
// ////////////////////////////

// echo "<script>\n";
// echo "buildSection('Encounter')\n";
// echo "</script>\n";

?>
