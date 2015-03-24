<?php

// create data collection to share information with the view
$view = new DataCollector;
$pageName = "History";

if($pkid == 0) {

	// no history id: get list page data

	// default values
	$varSearch = "";
	$getOffset = 0;
	$getLimit  = 60;

	if(isset($_GET['page_start'])) {
		$getOffset = sanitize($_GET['page_start']);
	}
	if(isset($_GET['page_count'])) {
		$getLimit = sanitize($_GET['page_count']);
	}
	if(isset($_GET['name'])) {
		$getNameKeyword = sanitize($_GET['name']);
		if($getNameKeyword) {
			$varSearch .= "(pc.`name` LIKE '%{$getNameKeyword}%' OR o.`name` LIKE '%{$getNameKeyword}%') AND ";
		}
	}
	if(isset($_GET['loc'])) {
		$getLocKeyword = sanitize($_GET['loc']);
		if($getLocKeyword) {
			$varSearch .= "(l.`name` LIKE '%{$getLocKeyword}%' OR r1.`name` LIKE '%{$getLocKeyword}%' OR r2.`name` LIKE '%{$getLocKeyword}%' OR h.`location_id` = '%{$getLocKeyword}%') AND ";
		}
	}
	if(isset($_GET['from'])) {
		$getFromKeyword = sanitize($_GET['from']);
		if($getFromKeyword) {
			$varSearch .= "(h.`start_year` >= {$getFromKeyword}) AND ";
		}
	}
	if(isset($_GET['to'])) {
		$getToKeyword = sanitize($_GET['to']);
		if($getToKeyword) {
			$varSearch .= "(h.`start_year` <= {$getToKeyword}) AND ";
		}
	}

	// build history list
	$varRecord = DataConnector::selectQuery("
		 SELECT h.`id`                   AS `id`,
		        h.`name`                 AS `name`,
		        h.`start_year`           AS `start_year`,
		        h.`end_year`             AS `end_year`
		   FROM `t_history` h
		   LEFT JOIN `t_characters_history` pch
		     ON pch.`history_id` = h.`id`
		    AND pch.`is_deleted` != 'Yes'
		   LEFT JOIN `t_characters` pc
		     ON pch.`character_id` = pc.`id`
		   LEFT JOIN `t_history_organizations` ho
		     ON ho.`history_id` = h.`id`
		    AND ho.`is_deleted` != 'Yes'
		   LEFT JOIN `t_organizations` o
		     ON ho.`organization_id` = o.`id`
		   LEFT JOIN `t_locations` l
		     ON l.`id` = h.`location_id`
		   LEFT JOIN `t_regions` r1
		     ON r1.`id` = h.`region_id`
		   LEFT JOIN `t_regions` r2
		     ON r2.`id` = h.`region2_id`
		  WHERE {$varSearch}h.`is_deleted` != 'Yes'
		  GROUP BY h.`id`
		  ORDER BY h.`start_year` DESC, h.`day` DESC
		  LIMIT {$getLimit} OFFSET {$getOffset}
	");

	$varCounter = 0;
	while ($varRecord) {
		if($varCounter++ < $getLimit / 2) {
			$view->historyListLeft[] = $varRecord;
		}
		else {
			$view->historyListRight[] = $varRecord;
		}
		$varRecord = DataConnector::selectQuery();
	}
	// check if more records are available
	if($varCounter == $getLimit) {
		$view->historyListMore = 1;
	}
}

?>
	<script>
		// onload
		function start(pkid)
		{
			console.log("function start(pkid = " + pkid + ")")

			if(!pkid) {
				// ajax call to populate add section
				buildSection('Add')
			}
			else {
				// show loading widgets
				$("#mainSection").html(loadingWidget)
				$("#charactersSection").html(loadingWidget)
				$("#organizationsSection").html(loadingWidget)
	
				// ajax calls to populate subsections
				buildSection('Main')
				buildSection('Characters')
				buildSection('Organizations')
			}

			console.log(" ... CHECK")
		}
	</script>
