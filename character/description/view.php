<?php

// Description section
$output = new BuildOutput("Description");

// id
$output->add("id", $pkid, 0, 0);

// description
$output->add("", "<div class=\"statBlockDetail\">", "", "", "");
$output->add("description", $view->characterDescription['description']['name'], "", "", "textarea");
$output->add("", "</div>", "", "", "");
$output->br();

// race
$output->add("god_id", $view->characterDescription['description']['god'], "Religion", "Religion", "200");
$output->br();

// location
$output->add("location", $view->characterDescription['description']['location'], "Location", "Location", "200");
if($view->characterDescription['description']['loc_name']) {
	$output->addRead("(" . $view->characterDescription['description']['loc_name'] . ")");
}
$output->br();

echo $output->dump(1);

echo drawHexMap($varXCo, $varYCo, $varHexSize, $view->characterDescription['location']);

?>
