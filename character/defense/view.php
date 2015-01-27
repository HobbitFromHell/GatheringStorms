<?php

// defense section
$output = new BuildOutput("Defense");

// id
$output->add("id", $pkid, 0, 0);

// ac and description
$output->add("", "<span id='calcAC'>...</span>", "AC", 0);
// flat-footed
$output->add("", "<span id='calcFlatFooted'>...</span>", ", flat-footed", 0);
// CMD
$output->add("", "<span id='calcCMD'>...</span>", ", CMD", 0);
$output->add("", "<span id='calcACDesc'>...</span>", "", 0);
$output->br();
// armour description
$output->add("", "<span id='calcArmour'>...</span>", "Armour", 0);
$output->br();

// hp and description
$output->add("", "<span id='calcHP'>...</span>", "hp", 0);
$output->add("", "<span id='calcHPDesc'>...</span>", "", 0);
$output->br();

// saving throws
$output->add("", "<span id='calcFort'>...</span>", "Fort", 0);
$output->add("", "<span id='calcRef'>...</span>", ", Ref", 0);
$output->add("", "<span id='calcWill'>...</span>", ", Will", 0);
$output->add("", "<span id='calcSaveDesc'></span>", "", 0);
$output->br();
// defensive abilities
$output->add("", "<span id='calcSD'></span>", "", 0);
$output->add("", "<span id='calcDR'></span>", "", 0);
$output->add("", "<span id='calcImmune'></span>", "", 0);

echo $output->dump(0);

?>
