<?php

// Feats section
$output = new BuildOutput("Feats");

// id and parameters (must be re-passed when subsection is refreshed)
$output->add("id", $pkid, 0, 0);
$output->add("race", $view->characterFeats[param][race], 0, 0);
$output->add("total_level", $view->characterFeats[param][total_level], 0, 0);
$output->add("total_bab", $view->characterFeats[param][total_bab], 0, 0);
$output->add("str", $view->characterFeats[param][str], 0, 0);
$output->add("dex", $view->characterFeats[param][dex], 0, 0);
$output->add("con", $view->characterFeats[param][con], 0, 0);
$output->add("int", $view->characterFeats[param][int], 0, 0);
$output->add("wis", $view->characterFeats[param][wis], 0, 0);
$output->add("cha", $view->characterFeats[param][cha], 0, 0);

// feats, with detail
$output->add("feat", $view->characterFeats[feat], "Feats", 
	"<b>Feats</b> <span id=\"calcFPDesc\">...</span><br>", "150", 
	array("DELETE", "; ", 
		array("detail", "()", "", "100"), 
		array("is_deleted", 0, 0, 0)
	)
);

echo $output->dump(1);

?>
