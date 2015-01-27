<?php

// main section
$output = new BuildOutput("Main");

// id and parameters (for master page to grab)
$output->add("id", $pkid, 0, 0);
$output->add("total_level", $view->total_level, 0, 0);
$output->add("hp_main", $view->hp_main, 0, 0);
$output->add("hp_desc_main", substr($view->hp_desc_main, 2), 0, 0);
$output->add("sp_main", $view->sp_main, 0, 0);
$output->add("total_bab", $view->total_bab, 0, 0);
$output->add("total_fort", $view->total_fort, 0, 0);
$output->add("total_ref", $view->total_ref, 0, 0);
$output->add("total_will", $view->total_will, 0, 0);

// name
$output->addRead("<h6 class=\"statBlockTitle\">");
$output->add("name", $view->characterMain[name], "", "Name", "200");
$output->addRead(" &nbsp; &nbsp; ");
// cr
$output->add("cr", $view->cr, "CR", 0);
$output->addRead("</h6>");

// gender
$output->add("gender", $view->characterMain[gender], "", "Gender", "70");
$output->addEdit("<br>");
// race
$output->add("", $view->characterMain[race][short], "", 0);
// class & level
$output->add("class", $view->characterMain['class'], "", "Class", "90", 
	array(
		array("level", "", "", "40", "", array(0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20))
	)
);
$output->addEdit("<br>");

// favoured class bonus
$output->add("hp_bonus", $view->characterMain[hp_bonus], 0, "Favoured Class HP Bonus", "30");
$output->add("sp_bonus", $view->characterMain[sp_bonus], 0, "SP Bonus", "30");
$output->br();

// alignment
$output->add("alignment", $view->characterMain[alignment], "", "Alignment", "50");
// size
$output->add("", "<span id='calcSize'>...</span>", "", 0);
// race & type
$output->add("race_id", $view->characterMain[race], "", "Race", "130");
$output->addRead(" (humanoid)");
$output->br();

// init
$output->add("", "<span id='calcInit'>...</span>", "Init", 0);
// senses
$output->add("", "<span id='calcSenses'>...</span>", "; Senses", 0);

echo $output->dump(1);

?>
