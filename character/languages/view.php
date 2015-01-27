<?php

// Languages section
$output = new BuildOutput("Languages");

// id
$output->add("id", $pkid, 0, 0);

// languages
$output->add("language", $view->characterLanguages[language], 
	"Languages", "<b>Languages</b> <span id=\"calcLPDesc\">...</span><br>", "100", 
	array("DELETE", "; ", array("is_deleted", 0, 0, 0))
);

echo $output->dump(1);

?>
