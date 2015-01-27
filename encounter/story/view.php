<?php

// story section
$output = new BuildOutput("Story");

// id
$output->add("id", $view->encounterStory[id], 0, 0);

// story
$output->add("", "<b><a href=\"/encounter/?name={$view->encounterStory[name]}\">{$view->encounterStory[name]}</a></b>", "", 0);
$output->add("name", $view->encounterStory[name], 0, "Name", "150");
$output->br();

// description
$output->add("description", $view->encounterStory[description], "</b>", "", "textarea");
$output->br();

echo $output->dump(1);

?>
