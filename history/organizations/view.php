<?php

// organizations section
$output = new BuildOutput("Organizations");

// id
$output->add("id", $pkid, 0, 0);

// organizations
$output->add("organization_id", $view->historyOrganizations, 0, "", "120", 
	array("DELETE", 
		array("is_deleted", 0, 0, 0)
	)
);

// build character list (unset 'list' used for select input)
unset($view->historyOrganizations['list']);
if($view->historyOrganizations[0]) {
	$output->addRead(buildOrgList($view->historyOrganizations));
}
else {
	$output->addRead("None.<br>");
}

echo $output->dump(1);

?>
