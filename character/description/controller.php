<?php

// //////////
// validation
// //////////

if (!($pkid > 0 and $pkid < 65000)) {
	echo "FAIL: Character ID {$pkid} is invalid";
	return false;
}


// //////
// insert
// //////

// custom update (post_data function only uses insert on duplicate key update, which does not function here)
if (isset($_POST[id])) {
	// post_data("t_characters", "id", "id", Array("description"));
	$postDescription = sanitize($_POST[description]);
	$postLocationID = sanitize($_POST[location]);
	DataConnector::updateQuery("
		 UPDATE t_characters
		    SET `description` = '{$postDescription}',
		        `location_id` = '{$postLocationID}'
		  WHERE `id` = {$pkid}
	");
}


// //////
// select
// //////

$view->characterDescription[description] = DataConnector::selectQuery("
	 SELECT pc.`description` AS `name`,
	        pc.`location_id` AS `location`,
	         l.`name`        AS `loc_name`
	   FROM t_characters pc
	   LEFT JOIN t_locations l
	     ON pc.`location_id` = l.`id`
	  WHERE pc.`id` = {$pkid}
");

// strip X and Y numeric location values from location_id, form: "[-]#0x[-]#0"
$varXLoc = stripos($view->characterDescription[description][location], "x");
$varXCo = substr($view->characterDescription[description][location], 0, $varXLoc);
$varYCo = substr($view->characterDescription[description][location], $varXLoc + 1);
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
	        l.`rivers`  AS `rivers`
	   FROM t_locations l
	  WHERE l.`id` IN ({$varCoordinates})
");
while ($j) {
	$varTmpXloc = stripos($j[location], "x");
	$varTmpXco = substr($j[location], 0, $varTmpXloc);
	$varTmpYco = substr($j[location], $varTmpXloc + 1);
	$view->characterDescription[location][$varTmpXco][$varTmpYco] = $j;
	$j = DataConnector::selectQuery();
}


// ////////////////////////////
// communicate with parent page
// ////////////////////////////

if (!isset($_POST[id])) { // only during initial rendering should the next subsection be called
	echo "<script>\n";
	echo "buildSection('Organization')\n";
	echo "</script>\n";
}

?>
