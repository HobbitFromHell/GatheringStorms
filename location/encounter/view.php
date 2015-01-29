<?php

// cast section
$output = new BuildOutput("Encounter");

// id
$output->add("id", $pkid, 0, 0);

$output->addEdit("</form>");
$output->addEdit("<form action=\"/encounter/add/?id=0\" method=\"POST\" id=\"addNewLocalEncounter\">");
$output->addEdit("<input type=\"hidden\" name=\"name\" value=\"\">");
$output->addEdit("<input type=\"hidden\" name=\"favoured_class\" value=\"COM\">");
$output->addEdit("<input type=\"hidden\" name=\"chapter\" value=\"0\">");
$output->addEdit("<input type=\"hidden\" name=\"location_id\" value=\"{$pkid}\">");
$output->addEdit("<input type=\"submit\" name=\"create\" value=\"Create New Encounter Here\" onClick=\"$('#addNewLocalEncounter').submit();\">");

// build character list
//unset($view->encounterCast['list']);
if($view->locationEncounter[0]) {
	$output->addRead(buildEncList($view->locationEncounter));
}
else {
	$output->br();
}

echo $output->dump(1);

?>
