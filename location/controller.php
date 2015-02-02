<?php

// create data collection to share information with the view
$view = new DataCollector;

if($pkid == 0) {

	// no location id: present list page

	if(isset($_GET['page_start'])) {
		$getOffset = sanitize($_GET['page_start']);
	}
	else {
		$getOffset = 0;
	}
	if(isset($_GET['page_count'])) {
		$getLimit = sanitize($_GET['page_count']);
	}
	else {
		$getLimit = 60;
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
			}

			console.log(" ... CHECK")
		}
	</script>
