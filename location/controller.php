<?php

// create data collection to share information with the view
$view = new DataCollector;
$pageName = "Locations";

if($pkid == 0) {

	// no location id: present list page

	$getLocKeyword = $getRegionKeyword = "";
	$getOffset = 0;
	$getLimit = 60;

	if(isset($_GET['page_start'])) {
		$getOffset = sanitize($_GET['page_start']);
	}
	if(isset($_GET['page_count'])) {
		$getLimit = sanitize($_GET['page_count']);
	}
	if(isset($_GET['region'])) {
		$getRegionKeyword = sanitize($_GET['region']);
	}
	if(isset($_GET['loc'])) {
		$getLocKeyword = sanitize($_GET['loc']);
	}

	$varRecord = DataConnector::selectQuery("
		 SELECT l.`id`              AS `id`,
		        l.`name`            AS `name`,
		        l.`cr`              AS `cr`,
		        l.`alignment`       AS `alignment`,
		        r.`name`            AS `region`,
		          IFNULL(l.`name`, l.`id`) AS `loc_name`
		   FROM t_locations l
		   LEFT JOIN t_regions r
		     ON l.`regions_id` = r.`id`
		   LEFT JOIN t_regions r2
		     ON r.`parent_region_id` = r2.`id`
		   LEFT JOIN t_regions r3
		     ON r2.`parent_region_id` = r3.`id`
		  WHERE (l.`name` LIKE '%{$getLocKeyword}%' OR l.`id` = '{$getLocKeyword}')
		    AND (r.`name` LIKE '%{$getRegionKeyword}%' OR r2.`name` LIKE '%{$getRegionKeyword}%' OR r3.`name` LIKE '%{$getRegionKeyword}%')
		  ORDER BY l.`cr` DESC
		  LIMIT {$getLimit} OFFSET {$getOffset}
	");
	$varCounter = 0;
	while ($varRecord) {
		if($varCounter++ < $getLimit / 2) {
			$view->locationListLeft[] = $varRecord;
		}
		else {
			$view->locationListRight[] = $varRecord;
		}
		$varRecord = DataConnector::selectQuery();
	}
	// check if more records are available
	if($varCounter == $getLimit) {
		$view->locationListMore = 1;
	}

/* big map
	$varMinX = $varMaxX = $varMinY = $varMaxY = 0;

	$varRecord = DataConnector::selectQuery("
		 SELECT l.`id`      AS `id`,
		        IF(l.`image` IS NULL, 1, IF(l.`image` = '', 0, 1)) AS `important`,
		        l.`terrain` AS `terrain`,
		        l.`growth`  AS `growth`
		   FROM t_locations l
	");

	while($varRecord) {
		if(stripos($varRecord['id'], "x") !== FALSE) {
			$varCoords = split("x", $varRecord['id']);

			$varBigMap[$varCoords[0]][$varCoords[1]]['important'] = $varRecord['important'];
			$varBigMap[$varCoords[0]][$varCoords[1]]['terrain'] = $varRecord['terrain'];
			$varBigMap[$varCoords[0]][$varCoords[1]]['growth'] = $varRecord['growth'];

			if($varCoords[0] < $varMinX) $varMinX = $varCoords[0];
			if($varCoords[0] > $varMaxX) $varMaxX = $varCoords[0];
			if($varCoords[1] < $varMinX) $varMinY = $varCoords[1];
			if($varCoords[1] > $varMaxX) $varMaxY = $varCoords[1];
		}
	
		$varRecord = DataConnector::selectQuery();
	}
*/
}
?>
	<script>
		// onload
		function start(pkid)
		{
			console.log("function start(pkid = " + pkid + ")")

			if(pkid) {
				// show loading widgets
				$("#mainSection").html("<h6 class=\"statBlockTitle\">LOADING</h6>" + loadingWidget)
				$("#organizationsSection").html(loadingWidget)
				$("#inhabitantsSection").html(loadingWidget)
				$("#encountersSection").html(loadingWidget)
				$("#mapSection").html(loadingWidget)
	
				// ajax call to populate first section, which cascades to populate all sections on the page
				buildSection('Main')
				buildSection('History')
			}

			console.log(" ... CHECK")
		}
	</script>
