<?php

// abilities section
$output = new BuildOutput("Organization");

// id
$output->add("id", $pkid, 0, 0);

if(!$view->characterOrganization['organization']['list'][0]) {
	$output->addEdit("No organizations available at this location.");
}
else {
	// organizations, with title and master
	$output->add("organization", $view->characterOrganization['organization'], 0, "", "130", 
		array("DELETE", "; ", "BREAK",
			array("title", "0", "Title", "80"), 
			array("master_id", 0, "Master", "40"), // $view->characterOrganization['organization'][#]['master']['name']
			array("is_deleted", 0, 0, 0)
		)
	);
}

$output->addRead(buildOrgList($view->characterOrganization['organization']));

echo $output->dump(1);

?>
