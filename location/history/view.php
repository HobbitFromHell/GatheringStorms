<?php

// organization section
$output = new BuildOutput("history");

// id
$output->add("id", $pkid, 0, 0);

// organizations
//$output->add("organization", $view->locationOrganization['organization'], 0, "", "130");

$output->addRead(buildHistoryList($view->locationHistory));

echo $output->dump(0);

?>
