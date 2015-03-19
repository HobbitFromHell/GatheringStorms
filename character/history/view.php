<?php

// abilities section
$output = new BuildOutput("History");

// id
$output->add("id", $pkid, 0, 0);

$output->addRead(buildHistoryList($view->characterHistory));

echo $output->dump(0);

?>
