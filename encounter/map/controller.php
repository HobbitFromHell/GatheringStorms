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

//post_data("t_stories", "id", "id", Array("name", "description"));


// //////
// select
// //////

$view->encounterMap = DataConnector::selectQuery("
	 SELECT e.`location_id` AS `location_id`,
	        l.`name`        AS `loc_name`
	   FROM t_encounters e
	   LEFT JOIN t_locations l
	     ON e.`location_id` = l.`id`
	  WHERE e.`id` = {$pkid}
");

// strip X and Y numeric location values from location_id, form: "[-]#0x[-]#0"
$varXLoc = stripos($view->encounterMap[location_id], "x");
$varXCo = substr($view->encounterMap[location_id], 0, $varXLoc);
$varYCo = substr($view->encounterMap[location_id], $varXLoc + 1);
$varCoordinates = "";
$varHexSize = 3;

// build location list, based on distance from current coordinates
for($varTmpX = $varHexSize * -1; $varTmpX <= $varHexSize; $varTmpX++) {
	for($varTmpY = $varHexSize * -1; $varTmpY <= $varHexSize; $varTmpY++) {
		$varCoordinates .= "'" . ($varTmpX + $varXCo) . "x" . ($varTmpY + $varYCo) . "',";
	}
}
$varCoordinates = substr($varCoordinates, 0, -1); // string trailing comma

$j = DataConnector::selectQuery("
	 SELECT l.`id`      AS `location`,
	        l.`name`    AS `name`,
	        l.`image`   AS `image`,
	        l.`terrain` AS `terrain`,
	        l.`growth`  AS `growth`,
	        l.`roads`   AS `roads`,
	        l.`trails`  AS `trails`,
	        l.`rivers`  AS `rivers`
	   FROM t_locations l
	  WHERE l.`id` IN ({$varCoordinates})
");
while ($j) {
	$varTmpXloc = stripos($j[location], "x");
	$varTmpXco = substr($j[location], 0, $varTmpXloc);
	$varTmpYco = substr($j[location], $varTmpXloc + 1);
	$view->encounterMap[location][$varTmpXco][$varTmpYco] = $j;
	$j = DataConnector::selectQuery();
}


// ////////////////////////////
// communicate with parent page
// ////////////////////////////

//echo "<script>\n";
//echo "buildSection('Chapter')\n";
//echo "</script>\n";

?>
