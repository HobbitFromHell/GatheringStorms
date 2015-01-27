<?php

// Special Abilities section
$output = new BuildOutput("Specialqualities");

// id
$output->add("id", $pkid, 0, 0);

// racial traits
$output->add("", "<span id='calcSQ'></span>", "", 0);

// edit racial traits and class features
$output->addEdit("<span id='editRacialTraits'></span>", "");
$output->addEdit("<span id='editClassFeatures'></span>", "");
// edit BRD versatile performance
$output->addEdit("<span id='editVersatilePerf'></span>", "");
// edit DRD animal companion
$output->addEdit("<span id='editAnimalCompanion'></span>", "");
// edit PAL mercies and divine bone
$output->addEdit("<span id='editDivineBond'></span>", "");
$output->addEdit("<span id='editMercies'></span>", "");
// edit RGR combat style, hunter's bond, favoured enemy and terrain
$output->addEdit("<span id='editCombatStyle'></span>", "");
$output->addEdit("<span id='editHuntersBond'></span>", "");
$output->addEdit("<span id='editFavouredTerrain'></span>", "");
// edit ROG rogue talents
$output->addEdit("<span id='editRogueTalents'></span>", "");
// edit WIZ arcane bond
$output->addEdit("<span id='editArcaneBond'></span>", "");

echo $output->dump(1);

?>
