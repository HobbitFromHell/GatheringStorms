<?php

// //////////
// validation
// //////////

if(strpos($pkid, "x") < 1) {
	echo "FAIL: Location ID {$pkid} is invalid";
	return false;
}


// //////
// insert
// //////

post_data("t_locations", "id", "id", Array("map_details"));


// //////
// select
// //////

$view = new DataCollector;

// select from t_locations
$view->locationLocalmap = DataConnector::selectQuery("
	 SELECT l.`map_details` AS `map_details`
	   FROM t_locations l
	  WHERE l.`id` = '{$pkid}'
");

if(file_exists("../img/map" . $pkid . ".png")) {
	// PNG only
	$view->locationLocalmap[image_tag] = "<b>Local map</b><br><img src=\"img/map" . $pkid . ".png\" width=\"100%\">";
}
else {
	$view->locationLocalmap[image_tag] = "No local map available";
}


// ////////////////////////////
// communicate with parent page
// ////////////////////////////

echo "<script>\n";
echo "buildSection('Organization')\n";
echo "</script>\n";

?>
