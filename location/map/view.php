<?php

// maps section
$output = new BuildOutput("Map");

// id
$output->add("id", $pkid, 0, 0);

// region
if($view->locationMap[regions][full_name]) {
	$output->add("", $view->locationMap[regions][full_name], "Region", 0, 0);
	$output->addRead("<br>");
}
$output->add("regions_id", $view->locationMap[regions], 0, "Region", "180");
$output->addEdit("<br>");

// terrain
$output->add("terrain", $view->locationMap[map][terrain], "Terrain", "Terrain", "130");
// growth
$output->add("growth", $view->locationMap[map][growth], ", ", "Growth", "130");
// image
$output->add("image", $view->locationMap[map][image], ", Image", "Image", "130");
$output->br();

// travel cost
$output->add("", number_format(100 / min($varTravelCostRoad, $varTravelCost2Road)) . "% road, " . number_format(100 / min($varTravelCostTrail, $varTravelCost2Trail)) . "% trail, " . number_format(100 / min($varTravelCostWild, $varTravelCost2Wild)) . "% wild", "Travel Cost", 0, 0);
$output->addRead("<br>");

// lost
$output->add("", "Survival check DC " . $varLostDC, "Get Lost", 0, 0);
$output->addRead("<br>");

// visibility
$output->add("", $varVisibility . " ft.", "Visibility", 0, 0);
$output->add("roads", $view->locationMap[map][roads], 0, "Roads", "50");
$output->add("trails", $view->locationMap[map][trails], 0, "Trails", "50");
$output->add("rivers", $view->locationMap[map][rivers], 0, "Rivers", "50");
$output->br();

echo $output->dump(1);

echo drawHexMap($xco, $yco, 7, $view->locationMap[location]);

?>
