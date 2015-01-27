<?php

// offense section
$output = new BuildOutput("Offense");

// id
$output->add("id", $pkid, 0, 0);

// speed
$output->add("", "<span id='calcSpd'>...</span>", "Spd", 0);
$output->addRead("<br>");

// BAB
$output->add("", "<span id='calcBAB'>...</span>", "Base Atk", 0);
// CMB
$output->add("", "<span id='calcCMB'>...</span>", ", CMB", 0);
$output->addRead("<br>");

// melee
$output->add("", "<span id='calcMelee'>...</span>", "Melee", 0);

// ranged
$output->add("", "<span id='calcRanged'></span>", "", 0);

// special attacks
$output->add("", "<span id='calcSA'></span>", "", 0);

echo $output->dump(0);

?>
