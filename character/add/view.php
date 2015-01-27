<?php

// add new character section
$output = new BuildOutput("Add");

// id (read only)
$output->add("id", $pkid, 0, 0);

$output->addRead("Add new character" . $varPrompt);
$output->addEdit("Add new character<br>");
// name
$output->add("name", $view->characterAdd[name], "", "Name", "200");
$output->br();
// favoured class
$output->add("favoured_class", $view->characterAdd[favoured_class], "", "Favoured Class", "130");

echo $output->dump(1);

?>
