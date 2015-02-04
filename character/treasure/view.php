<?php

// Treasure section
$output = new BuildOutput("Treasure");

// id and parameters
$output->add("id", $pkid, 0, 0);
$output->add("size", $view->characterTreasure[param][size], 0, 0);
$output->add("total_value", $view->total_value, 0, 0);
$output->add("total_weight", $view->total_weight, 0, 0);

// summary
$output->addEdit("<b>Total Load</b> " . $view->total_weight . " lbs. <span id=\"calcLoad\"></span><br>", "");
$output->addEdit("<b>Total Value</b> " . $view->total_value / 100 . " <span id=\"calcMaxValue\"></span> gp", "");

// combat gear (read-only assembly of all equipped gear)
$output->add("", $combatGear, "Combat Gear", 0, 0);
$output->br();

// melee weapon (edit only)
$output->add("melee", $view->characterTreasure[melee], 0, "<b>Melee Weapon</b><br>", "100",
	array("DELETE", "; ", 
		array("quality", "", "", "70"), 
		array("repair", 0,  "", "60", 0, array("Good", "Worn", "Broken")), 
		array("is_equipped", 0, "", "60", "Melee", array("Melee", "Off-Hand", "Ranged", "No")), 
		array("is_deleted", 0, 0, 0)
	)
);
$output->addEdit("<br>");

// off-hand weapon (edit only)
$output->add("offhand", $view->characterTreasure[offhand], 0, "<b>Off-Hand Weapon</b><br>", "100",
	array("DELETE", "; ", 
		array("quality", "", "", "70"), 
		array("repair", 0, "", "60", 0, array("Good", "Worn", "Broken")), 
		array("is_equipped", 0, "", "60", "Off-Hand", array("Melee", "Off-Hand", "Ranged", "No")), 
		array("is_deleted", 0, 0, 0)
	)
);
$output->addEdit("<br>");

// ranged weapon (edit only)
$output->add("ranged", $view->characterTreasure[ranged], 0, "<b>Ranged Weapon</b><br>", "100",
	array("DELETE", "; ", 
		array("quality", "", "", "70"), 
		array("repair", 0, "", "60", 0, array("Good", "Worn", "Broken")), 
		array("is_equipped", 0, "", "60", "Ranged", array("Melee", "Off-Hand", "Ranged", "No")), 
		array("is_deleted", 0, 0, 0)
	)
);
$output->addEdit("<br>");

// armour (edit only)
$output->add("armour", $view->characterTreasure[armour], 0, "<b>Armour</b><br>", "100",
	array("DELETE", "; ", 
		array("quality", "", "", "70"), 
		array("repair", 0, "", "60", 0, array("Good", "Worn", "Broken")), 
		array("is_equipped", 0, "", "60", "Armour", array("Armour", "No")), 
		array("is_deleted", 0, 0, 0)
	)
);
$output->addEdit("<br>");

// shield (edit only)
$output->add("shield", $view->characterTreasure[shield], 0, "<b>Shield</b><br>", "100",
	array("DELETE", "; ", 
		array("quality", "", "", "70"), 
		array("repair", 0, "", "60", 0, array("Good", "Worn", "Broken")), 
		array("is_equipped", 0, "", "60", "Armour", array("Armour", "Off-Hand", "No")), 
		array("is_deleted", 0, 0, 0)
	)
);
$output->addEdit("<br>");

// other combat gear (edit only)
$output->add("othercombat", $view->characterTreasure[othercombat], 0, "<b>Combat Gear</b><br>", "100",
	array("DELETE", "; ", 
		array("quality", "", "", "70"), 
		array("repair", 0, "", "60", 0, array("Good", "Worn", "Broken")), 
		array("is_equipped", 0, "", "60", "Yes", array("Yes", "No")), 
		array("is_deleted", 0, 0, 0)
	)
);
$output->addEdit("<br>");

// other gear
$output->add("other", $view->characterTreasure[other], "Other Gear", "<b>Other Gear</b><br>", "100",
	array("DELETE", "; ", 
		array("quality", "", "", "70"), 
		array("repair", 0, "", "60", 0, array("Good", "Worn", "Broken")), 
		array("is_equipped", 0, "", "60", "No", array("Melee", "Off-Hand", "Ranged", "Armour", "Yes", "No")), 
		array("is_deleted", 0, 0, 0)
	)
);
$output->br();

echo $output->dump(1);

?>
