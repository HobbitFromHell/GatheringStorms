<?php

// organization section
$output = new BuildOutput("organization");

// id
$output->add("id", $pkid, 0, 0);

// organizations
$output->add("organization", $view->locationOrganization['organization'], 0, "", "130");

$output->addRead(buildOrgList($view->locationOrganization['organization']));

echo $output->dump(1);

?>
