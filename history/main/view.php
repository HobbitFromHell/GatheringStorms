<?php

// scene section
$output = new BuildOutput("Main");

// id
$output->add("id", $pkid, 0, 0);

// headline
$output->add("name", $view->historyMain['name'], "<b>", "", "300");
$output->addRead("</b>");
$output->br();

// circa
if($view->historyMain['is_circa'] == "Yes") {
	$output->addRead("c.");
}

// start year
$output->add("start_year", $view->historyMain['start_year'], "", "", "50");
// end year
if($view->historyMain['end_year']) {
	$output->add("end_year", $view->historyMain['end_year'], " - ", "DR to ", "50");
}
else {
	$output->add("end_year", $view->historyMain['end_year'], 0, "DR to ", "50");
}
$output->addRead("DR");
$output->addEdit("DR");
// month
$output->add("month", $view->historyMain['month'], "", "", "75");
// day
if($view->historyMain['day'] > 0) {
	$output->add("day", $view->historyMain['day'], "", "", "30");
}
else {
	$output->add("day", $view->historyMain['day'], 0, "", "30");
}
$output->br();

// region
$output->add("region_id", $view->historyMain['region'], "Region", "Region", "140");
// region2
if($view->historyMain['region_id'] and $view->historyMain['region2_id']) {
	$output->addRead("/ ");
}
$output->add("region2_id", $view->historyMain['region2'], "", "and", "140");
// location
if($view->historyMain['region'] && $view->historyMain['location']) {
	$output->addRead(", {$view->historyMain['location']}");
}
if($view->historyMain['location_id']) $output->addRead(" (");
$output->add("location_id", $view->historyMain['location_id'], "", "Coordinates", "75");
if($view->historyMain['location_id']) $output->addRead(")");
$output->addRead("<br>");

// source
$output->add("source", $view->historyMain['source'], "Source", "Source", "100");
$output->br();

// description
$output->add("description", $view->historyMain['description'], "", "", "textarea");
$output->br();

// is deleted
$output->add("is_deleted", $view->historyMain['is_deleted'], 0, "Delete", "50");
$output->br();

echo $output->dump(1);

?>
