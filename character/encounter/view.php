<?php

// encounter section
$output = new BuildOutput("Encounter");

// id
$output->add("id", $pkid, 0, 0);

// encounters
$output->add("encounter_id", $view->characterEncounter, 0, "", "200", 
	array("DELETE", 
		array("is_deleted", 0, 0, 0)
	)
);

$output->addEdit("</form>");
$output->addEdit("<form action=\"/encounter/add/?id=0\" method=\"POST\" id=\"addNewEncounter\">");
$output->addEdit("<input type=\"hidden\" name=\"name\" value=\"\">");
$output->addEdit("<input type=\"hidden\" name=\"chapter\" value=\"0\">");
$output->addEdit("<input type=\"hidden\" name=\"character_id\" value=\"{$pkid}\">");
$output->addEdit("<input type=\"submit\" name=\"create\" value=\"Create New Encounter\" onClick=\"document.getElementById('addNewEncounter').submit();\">");

if($view->characterEncounter[0]) {
	$output->addRead(buildEncList($view->characterEncounter));
}
else {
	$output->br();
}

echo $output->dump(1);

?>
