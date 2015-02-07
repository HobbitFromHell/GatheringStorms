<?php

// Treasure section
$output = new BuildOutput("Treasure");

// id and parameters
$output->add("id", $pkid, 0, 0);
$output->add("size", $view->characterTreasure[param][size], 0, 0);
$output->add("total_value", $view->total_value, 0, 0);
$output->add("total_weight", $view->total_weight, 0, 0);

// summary (read-only)
$output->addRead("<b>Total Load</b> " . $view->total_weight . " lbs. <span id=\"calcLoad\"></span><br>", "");
$output->addRead("<b>Total Value</b> " . number_format($view->total_value / 100) . " <span id=\"calcMaxValue\"></span> gp", "");
$output->addRead("<br>");

// combat gear (read-only assembly of all equipped gear)
if($view->combatGear) {
	$output->add("", $view->combatGear, "Combat Gear", 0, 0);
	$output->addRead("<br>");
}

// other gear (read-only assembly of all unequipped gear)
if($view->otherGear) {
	$output->add("", $view->otherGear, "Other Gear", 0, 0);
	$output->addRead("<br>");
}

// mount gear (read-only assembly of all gear on the mount)
if($view->mountGear) {
	$output->add("", $view->mountGear, "Mount Gear", 0, 0);
	$output->addRead("<br>");
}

// stored gear (read-only assembly of all stored gear)
if($view->storedGear) {
	$output->add("", $view->storedGear, "Stored Gear", 0, 0);
	$output->addRead("<br>");
}

// melee weapon (edit only)
$output->add("weapon", $view->characterTreasure[weapon], 0, "<b>Weapons</b><br>", "100",
	array("DELETE", "; ", 
		array("quality", "", "", "90"), 
		array("count", "", "", "30"), 
		array("is_equipped", 0, "", "72", "", array("Melee", "Off-Hand", "Ranged", "No", "Mount", "Storage")), 
		array("is_deleted", 0, 0, 0)
	)
);
$output->addEdit("<br>");

// armour (edit only)
$output->add("armour", $view->characterTreasure[armour], 0, "<b>Armour</b><br>", "100",
	array("DELETE", "; ", 
		array("quality", "", "", "90"), 
		array("count", "", "", "30"), 
		array("is_equipped", 0, "", "72", "", array("Armour", "No", "Mount", "Storage")), 
		array("is_deleted", 0, 0, 0)
	)
);
$output->addEdit("<br>");

// other gear
$output->add("other", $view->characterTreasure[other], 0, "<b>Other</b><br>", "100",
	array("DELETE", "; ", 
		array("quality", "", "", "90"), 
		array("count", "", "", "30"), 
		array("is_equipped", 0, "", "72", "", array("Yes", "No", "Mount", "Storage", "Melee", "Off-Hand", "Ranged", "Armour")), 
		array("is_deleted", 0, 0, 0)
	)
);

echo $output->dump(1);

?>
