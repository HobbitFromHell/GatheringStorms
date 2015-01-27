<?php

// cast section
$output = new BuildOutput("Cast");

// id
$output->add("id", $pkid, 0, 0);

// cast
$output->add("character_id", $view->encounterCast, 0, "", "120", 
	array("DELETE", 
		array("is_deleted", 0, 0, 0)
	)
);

$output->addEdit("</form>");
$output->addEdit("<form action=\"/character/add/?id=0\" method=\"POST\" id=\"addNewCastMember\">");
$output->addEdit("<input type=\"hidden\" name=\"name\" value=\"\">");
$output->addEdit("<input type=\"hidden\" name=\"favoured_class\" value=\"COM\">");
$output->addEdit("<input type=\"hidden\" name=\"encounter_id\" value=\"{$pkid}\">");
$output->addEdit("<input type=\"submit\" name=\"create\" value=\"Create New Character\" onClick=\"document.getElementById('addNewCastMember').submit();\">");

// build character list (unset 'list' used for select input)
unset($view->encounterCast['list']);
if($view->encounterCharacters[0]) {
	$output->addRead(buildCharList($view->encounterCharacters));
}
else {
	$output->br();
}

echo $output->dump(1);

?>
