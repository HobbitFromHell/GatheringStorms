<?php

// scene section
$output = new BuildOutput("Scene");

// id
$output->add("id", $pkid, 0, 0);

// story
$output->add("chapter_id", $view->encounterScene[chapter], 0, "Story/Chapter", "280");
$output->addEdit("<br>");

// scene
$output->add("scene", $view->encounterScene[scene], "<b>", "Scene", "200");
$output->addRead("</b> &nbsp; &nbsp; ");
// cr
$output->add("", $view->encounterScene[cr_min], "CR", 0);
$output->add("cr_min", $view->encounterScene[cr_min], 0, "CR", "30");
if($view->encounterScene[cr]) {
	$output->add("", $view->encounterScene[cr], "Combat CR", 0);
}
$output->add("cr", $view->encounterScene[cr], 0, "-", "30");
$output->br();

// status
if($view->encounterScene[completed_day]) {
	$output->add("", "completed on day " . $view->encounterScene[completed_day], "Status", 0);
}
else {
	if($view->encounterScene[is_active][value] == "Yes") {
		$output->add("", "active", "Status", 0);
		$output->add("", $view->encounterScene[chance] . "%", "Chance", 0);
	}
	else {
		$output->add("", "inactive", "Status", 0);
	}
}
$output->add("is_active", $view->encounterScene[is_active], 0, "Active", "50");
$output->add("chance", $view->encounterScene[chance], 0, "Chance", "40");
$output->add("completed_day", $view->encounterScene[completed_day], 0, "Completed day", "40");
$output->addEdit("<br>");

// trigger
if($view->encounterScene[trigger_day] and !$view->encounterScene[trigger][name]) {
	// by day
	$output->add("", "day " . $view->encounterScene[trigger_day], "Triggered by", 0);
}
if(!$view->encounterScene[trigger_day] and $view->encounterScene[trigger][name]) {
	// by event completion
	$output->add("", "<a href=\"/encounter/?id={$view->encounterScene[trigger][id]}\">" . $view->encounterScene[trigger][name] . "</a>", "Triggered by", 0);
}
if($view->encounterScene[trigger_day] and $view->encounterScene[trigger][name]) {
	// by days after event completion
	$output->add("", $view->encounterScene[trigger_day] . " days after <a href=\"/encounter/?id={$view->encounterScene[trigger][id]}\">" . $view->encounterScene[trigger][name] . "</a>", "Triggered by", 0);
}
$output->add("trigger_encounter_id", $view->encounterScene[trigger], 0, "Triggered by", "230");
$output->add("trigger_day", $view->encounterScene[trigger_day], 0, "day", "30");
$output->br();

// triggers
if($view->encounterScene[triggers]) {
	$output->addRead(substr($view->encounterScene[triggers], 0, -2) . "<br>", "Triggers", 0);
}

// location
$output->add("", "<a href=\"/location/?id={$view->encounterScene[location_id]}\">{$view->encounterScene[location]}</a>", "Location", 0);
$output->add("location_id", $view->encounterScene[location_id], 0, "Location", "100");
$output->br();

// description
$output->add("description", $view->encounterScene[description], "", "", "textarea");
$output->br();
$output->br();
$output->add("stat_block", $view->encounterScene[stat_block], "", "Stat Block", "textarea");
$output->br();

echo $output->dump(1);

?>
