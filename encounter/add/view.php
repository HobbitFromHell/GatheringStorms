<?php

// add new character section
$output = new BuildOutput("Add");

// id
$output->add("id", $pkid, 0, 0);

// prompt
$output->addRead("Add new encounter" . $varPrompt);
$output->addEdit("Add new encounter<br>");

// chapter
$output->add("chapter", $view->encounterAdd[chapter], "", "Chapter", "300");
$output->br();
// name
$output->add("name", $view->encounterAdd[name], "", "Name", "200");
$output->br();

echo $output->dump(1);

?>
