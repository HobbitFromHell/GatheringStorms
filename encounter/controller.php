<?php

// create data collection to share information with the view
$view = new DataCollector;

// set primary key id from get data
if(isset($_GET[id])) {
	$pkid = sanitize($_GET[id]);
}

if($pkid == 0) {
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
		$getLimit = 30;
	}
	if(isset($_GET['name'])) {
		$getNameKeyword = sanitize($_GET['name']);
	}
	if(isset($_GET['loc'])) {
		$getLocKeyword = sanitize($_GET['loc']);
	}

	// build encounter list
	$varRecord = DataConnector::selectQuery("
		 SELECT e.`id`                   AS `id`,
		        s.`name`                 AS `story_name`,
		        c.`name`                 AS `chapter_name`,
		        e.`name`                 AS `name`,
		        e.`cr`                   AS `cr`,
		        e.`cr_min`               AS `cr_min`,
		        e.`chance`               AS `chance`,
		        e.`is_active`            AS `is_active`,
		        e.`trigger_day`          AS `trigger_day`,
		        e.`trigger_encounter_id` AS `trigger_encounter_id`,
		       te.`name`                 AS `trigger_name`,
		          IFNULL(l.`name`, l.`id`) AS `loc_name`
		   FROM t_encounters e
		   JOIN t_chapters c
		     ON e.`chapter_id` = c.`id`
		   JOIN t_stories s
		     ON c.`story_id` = s.`id`
		   LEFT JOIN t_encounters te
		     ON e.`trigger_encounter_id` = te.`id`
		   LEFT JOIN t_locations l
		     ON e.`location_id` = l.`id`
		  WHERE ('{$getLocKeyword}' = '' OR l.`name` LIKE '%{$getLocKeyword}%' OR l.`id` = '{$getLocKeyword}')
		    AND (e.`name` LIKE '%{$getNameKeyword}%' OR c.`name` LIKE '%{$getNameKeyword}%' OR s.`name` LIKE '%{$getNameKeyword}%')
		  ORDER BY s.`name`, c.`name`, e.`trigger_day`, e.`name`
		  LIMIT {$getLimit} OFFSET {$getOffset}
	");
	$varCounter = 0;
	while ($varRecord) {
		$varCounter++;
		$view->encounterList[] = $varRecord;
		$varRecord = DataConnector::selectQuery();
	}
	// check if more records are available
	if($varCounter == $getLimit) {
		$view->encounterListMore = 1;
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
				$("#storySection").html(loadingWidget)
				$("#chapterSection").html(loadingWidget)
				$("#sceneSection").html(loadingWidget)
				$("#castSection").html(loadingWidget)
				$("#mapSection").html(loadingWidget)
	
				// ajax call to populate first section, which cascades to populate all sections on the page
				buildSection('Story')
			}

			console.log(" ... CHECK")
		}
	</script>
