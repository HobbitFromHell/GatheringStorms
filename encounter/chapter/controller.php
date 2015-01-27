<?php

// //////////
// validation
// //////////

if (!($pkid > 0 and $pkid < 65000)) {
	echo "FAIL: Encounter ID {$pkid} is invalid";
	return false;
}

// //////
// insert
// //////

if(post_data("t_chapters", "id", "id", Array("name", "description", "story_id"))) {
	echo "<script>\n";
	echo "buildSection('Story')\n";
	echo "</script>\n";
	return;
}


// //////
// select
// //////

$view = new DataCollector;
$view->encounterChapter = DataConnector::selectQuery("
	 SELECT c.`id`          AS `id`,
	        c.`name`        AS `name`,
	        c.`description` AS `description`,
	        c.`story_id`    AS `story_id`
	   FROM `t_encounters` e, `t_chapters` c
	  WHERE e.`id` = {$pkid}
	    AND c.`id` = e.`chapter_id`
");

// build story list
$j = DataConnector::selectQuery("
	 SELECT s.`id`          AS `id`,
	        s.`name`        AS `name`
	   FROM `t_stories` s
");
while($j) {
	$view->encounterChapter[story]['list'][] = $j;
	if($view->encounterChapter[story_id] == $j[id]) {
		$view->encounterChapter[story][name] = $j[name];
	}
	$j = DataConnector::selectQuery();
}


// ////////////////////////////
// communicate with parent page
// ////////////////////////////

echo "<script>\n";
echo "buildSection('Scene')\n";
echo "</script>\n";

?>
