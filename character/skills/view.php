<?php

// Skills section
$output = new BuildOutput("Skills");

// id and parameters
$output->add("id", $pkid, 0, 0);
$output->add("class_list", sanitize($_POST[class_list]), 0, 0);

// skills, with rank
$output->add("skill", $view->characterSkills[skill], "Skills", 
	"<b>Skills</b> <span id=\"calcSPDesc\">...</span><br>", "150",
	array("DELETE", "; ", 
		array("total", "", 0, 0), 
		array("rank", 0, "", "40", "", array(0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20)), 
		array("is_deleted", 0, 0, 0)
	)
);

echo $output->dump(1);

?>
