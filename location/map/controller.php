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

post_data("t_locations", "id", "id", Array("terrain", "growth", "image", "roads", "trails", "rivers"));


// //////
// select
// //////

$view = new DataCollector;

// select from t_locations
$view->locationMap[map] = DataConnector::selectQuery("
	 SELECT l.`regions_id` AS `regions_id`,
	        l.`image`      AS `image`,
	        l.`terrain`    AS `terrain`,
	        l.`growth`     AS `growth`,
	        l.`roads`      AS `roads`,
	        l.`trails`     AS `trails`,
	        l.`rivers`     AS `rivers`
	   FROM t_locations l
	  WHERE l.`id` = '{$pkid}'
");

// set travel cost, visibility and lost dc based on terrain and growth
switch($view->locationMap[map][terrain]) {
	case "plains":              $varTravelCostRoad = 1.00; $varTravelCostTrail = 1.00; $varTravelCostWild = 0.70; $varVisibility = 1440; $varLostDC = 14; break;
	case "gentle hills":        $varTravelCostRoad = 0.90; $varTravelCostTrail = 0.80; $varTravelCostWild = 0.70; $varVisibility = 200; $varLostDC = 10; break;
	case "rugged hills":        $varTravelCostRoad = 0.70; $varTravelCostTrail = 0.60; $varTravelCostWild = 0.50; $varVisibility = 120; $varLostDC = 10; break;
	case "alpine mountain":     $varTravelCostRoad = 0.80; $varTravelCostTrail = 0.70; $varTravelCostWild = 0.50; $varVisibility = 400; $varLostDC = 11; break;
	case "rugged mountain":     $varTravelCostRoad = 0.70; $varTravelCostTrail = 0.60; $varTravelCostWild = 0.40; $varVisibility = 400; $varLostDC = 12; break;
	case "forbidding mountain": $varTravelCostRoad = 0.60; $varTravelCostTrail = 0.50; $varTravelCostWild = 0.30; $varVisibility = 400; $varLostDC = 13; break;
}
switch($view->locationMap[map][growth]) {
	case "tundra":              $varTravelCost2Road = 1.00; $varTravelCost2Trail = 0.90; $varTravelCost2Wild = 0.70; $varVisibility = 720; $varLostDC = 14; break;
	case "badlands":            $varTravelCost2Road = 1.00; $varTravelCost2Trail = 0.80; $varTravelCost2Wild = 0.60; $varVisibility = 720; $varLostDC = 14; break;
	case "broken lands":        $varTravelCost2Road = 1.00; $varTravelCost2Trail = 0.80; $varTravelCost2Wild = 0.60; $varVisibility = 720; $varLostDC = 14; break;
	case "dessert":             $varTravelCost2Road = 1.00; $varTravelCost2Trail = 0.70; $varTravelCost2Wild = 0.50; $varVisibility = 720; $varLostDC = 14; break;

	case "grasslands":          $varTravelCost2Road = 1.00; $varTravelCost2Trail = 1.00; $varTravelCost2Wild = 0.70; $varVisibility = 1440; $varLostDC = 15; break;
	case "shrubland":           $varTravelCost2Road = 1.00; $varTravelCost2Trail = 1.00; $varTravelCost2Wild = 0.70; $varVisibility = 720; $varLostDC = 15; break;
	case "sparse forest":       $varTravelCost2Road = 1.00; $varTravelCost2Trail = 0.90; $varTravelCost2Wild = 0.40; $varVisibility = 180; $varLostDC = 16; break;
	case "dense forest":        $varTravelCost2Road = 1.00; $varTravelCost2Trail = 0.80; $varTravelCost2Wild = 0.30; $varVisibility = 120; $varLostDC = 17; break;
	case "burned forest":       $varTravelCost2Road = 0.90; $varTravelCost2Trail = 0.80; $varTravelCost2Wild = 0.40; $varVisibility = 360; $varLostDC = 15; break;

	case "moor":                $varTravelCost2Road = 1.00; $varTravelCost2Trail = 0.90; $varTravelCost2Wild = 0.50; $varVisibility = 360; $varLostDC = 10; break;
	case "swamp":               $varTravelCost2Road = 1.00; $varTravelCost2Trail = 0.80; $varTravelCost2Wild = 0.40; $varVisibility = 160; $varLostDC = 10; break;
	case "sea":                 $varTravelCost2Road = 0.50; $varTravelCost2Trail = 0.50; $varTravelCost2Wild = 0.50; $varVisibility = 1440; $varLostDC = 15; break;
}

// build terrain and growth lists
$view->locationMap[map][terrain] = $view->getEnum("t_locations", "terrain", $view->locationMap[map][terrain]);
$view->locationMap[map][growth] = $view->getEnum("t_locations", "growth", $view->locationMap[map][growth]);

// strip x and y numeric location values from location_id, form: "[-]#0x[-]#0"
$xloc = stripos($pkid, "x");
$xco = substr($pkid, 0, $xloc);
$yco = substr($pkid, $xloc + 1);
$varHexSize = 7;
$tmpAllCo = "";

// build location list
for($tmpX = $varHexSize * -1; $tmpX <= $varHexSize; $tmpX++) {
	for($tmpY = $varHexSize * -1; $tmpY <= $varHexSize; $tmpY++) {
		$tmpAllCo .= "'" . ($tmpX + $xco) . "x" . ($tmpY + $yco) . "',";
	}
}
$tmpAllCo = substr($tmpAllCo, 0, -1); // string trailing comma
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
	  WHERE l.`id` IN ({$tmpAllCo})
");
while ($j) {
	$tmpXloc = stripos($j[location], "x");
	$tmpXco = substr($j[location], 0, $tmpXloc);
	$tmpYco = substr($j[location], $tmpXloc + 1);
	$view->locationMap[location][$tmpXco][$tmpYco] = $j;
	$j = DataConnector::selectQuery();
}

// select region details
$view->locationMap[regions] = DataConnector::selectQuery("
	 SELECT r.`name`             AS `name`,
	        r.`description`      AS `description`,
	          CONCAT_WS(', ', r.`name`, r2.`name`, r3.`name`, r4.`name`) AS `full_name`
	   FROM t_regions r
	   LEFT JOIN t_regions r2
	          ON r.`parent_region_id` = r2.`id`
	   LEFT JOIN t_regions r3
	          ON r2.`parent_region_id` = r3.`id`
	   LEFT JOIN t_regions r4
	          ON r3.`parent_region_id` = r4.`id`
	  WHERE r.`id` = '{$view->locationMap[map][regions_id]}'
");
if(!$view->locationMap[regions]) {
	$view->locationMap[regions][name] = "";
}
// build region list
$j = DataConnector::selectQuery("
	 SELECT r.`id`               AS `id`,
	        r.`name`             AS `name`
	   FROM t_regions r
	  ORDER BY r.`name`
");
while($j) {
	$view->locationMap[regions]['list'][] = $j;
	$j = DataConnector::selectQuery();
}


// ////////////////////////////
// communicate with parent page
// ////////////////////////////

echo "<script>\n";
echo "buildSection('Organization')\n";
echo "</script>\n";

?>
