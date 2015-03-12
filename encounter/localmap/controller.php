<?php

// //////////
// validation
// //////////

if($pkid < 1 or $pkid > 65555) {
	echo "FAIL: Location ID {$pkid} is invalid";
	return false;
}


// //////
// insert
// //////

post_data("t_encounters", "id", "id", Array("map_details"));


// //////
// select
// //////

$view = new DataCollector;

// select from t_locations
$view->encounterLocalmap = DataConnector::selectQuery("
	 SELECT e.`map_details` AS `map_details`
	   FROM t_encounters e
	  WHERE e.`id` = '{$pkid}'
");

if(file_exists("../img/map" . $pkid . ".png")) {
	// png only
	$view->encounterLocalmap['image_tag'] = "<img src=\"img/map" . $pkid . ".png\">";
}
else {
	$view->encounterLocalmap['image_tag'] = "No local map available";
}


// ////////////////////////////
// communicate with parent page
// ////////////////////////////

// echo "<script>\n";
// echo "buildSection('Organization')\n";
// echo "</script>\n";

?>
