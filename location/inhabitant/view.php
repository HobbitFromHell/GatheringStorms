<?php

// cast section
$output = new BuildOutput("Inhabitant");

// id
$output->add("id", $pkid, 0, 0);

$output->addEdit("</form>");
$output->addEdit("<form action=\"/character/add/?id=0\" method=\"POST\" id=\"addNewCastMember\">");
$output->addEdit("<input type=\"hidden\" name=\"name\" value=\"\">");
$output->addEdit("<input type=\"hidden\" name=\"favoured_class\" value=\"COM\">");
$output->addEdit("<input type=\"hidden\" name=\"location\" value=\"{$pkid}\">");
$output->addEdit("<input type=\"submit\" name=\"create\" value=\"Create New Character\" onClick=\"document.getElementById('addNewCastMember').submit();\">");

// build character list
//unset($view->encounterCast['list']);
if($view->locationInhabitant[inhabitant][0]) {
	$output->addRead(buildCharList($view->locationInhabitant[inhabitant]));
}

echo $output->dump(1);

?>
