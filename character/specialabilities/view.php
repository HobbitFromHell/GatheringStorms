<?php

// Special Abilities section
$output = new BuildOutput("Specialabilities");

// id
$output->add("id", $pkid, 0, 0);

// racial traits
$output->add("", "<span id='calcRacialTraits'>...</span>", "Racial Traits", 0);
$output->br();

// class features
$output->add("", "<span id='calcClassFeatures'>...</span>", "Class Features", 0);

echo $output->dump(0);

?>
