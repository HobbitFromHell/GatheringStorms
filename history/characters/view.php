<?php

// characters section
$output = new BuildOutput("Characters");

// id
$output->add("id", $pkid, 0, 0);

// characters
$output->add("character_id", $view->historyCharacters, 0, "", "120", 
	array("DELETE", 
		array("is_deleted", 0, 0, 0)
	)
);

// build character list (unset 'list' used for select input)
// FIX: switch 'id' to 'character_id' so buildCharList will auto-link properly
unset($view->historyCharacters['list']);
for($i = 0; $i < count($view->historyCharacters); ++ $i) {
	$view->historyCharacters[$i]['id'] = $view->historyCharacters[$i]['character_id'];
}
if($view->historyCharacters[0]) {
	$output->addRead(buildCharList($view->historyCharacters));
}
else {
	$output->addRead("None.<br>");
}

echo $output->dump(1);

?>
