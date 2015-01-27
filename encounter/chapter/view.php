<?php

// story section
$output = new BuildOutput("Chapter");

// id
$output->add("id", $view->encounterChapter[id], 0, 0);

// chapter
$output->add("", "<b><a href=\"/encounter/?name={$view->encounterChapter[name]}\">{$view->encounterChapter[name]}</a></b>", "", 0);
$output->add("name", $view->encounterChapter[name], 0, "Name", "150");
$output->br();

// story
$output->add("story_id", $view->encounterChapter[story], 0, "Story", "150");
$output->addEdit("<br>");

// description
$output->add("description", $view->encounterChapter[description], "</b>", "", "textarea");
$output->br();

echo $output->dump(1);

?>
