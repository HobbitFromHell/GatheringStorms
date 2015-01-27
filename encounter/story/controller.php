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

post_data("t_stories", "id", "id", Array("name", "description"));


// //////
// select
// //////

$view = new DataCollector;
$view->encounterStory = DataConnector::selectQuery("
	 SELECT s.`id`                   AS `id`,
	        s.`name`                 AS `name`,
	        s.`description`          AS `description`
	   FROM `t_encounters` e, `t_stories` s, `t_chapters` c
	  WHERE e.`id` = {$pkid}
	    AND c.`id` = e.`chapter_id`
	    AND s.`id` = c.`story_id`
");


// ////////////////////////////
// communicate with parent page
// ////////////////////////////

echo "<script>\n";
echo "buildSection('Chapter')\n";
echo "</script>\n";

?>
