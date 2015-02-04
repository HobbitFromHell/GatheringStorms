<?php

// abilities section
$output = new BuildOutput("Organization");

// id
$output->add("id", $pkid, 0, 0);

if(!$view->characterOrganization[organization]['list'][0]) {
	$output->addEdit("No organizations available at this location.");
}
else {
	// organizations, with title and master
	$output->add("organization", $view->characterOrganization[organization], 0, "", "130", 
		array("DELETE", "; ", "BREAK",
			array("title", "0", "Title", "80"), 
			array("master_id", 0, "Master", "40"), // $view->characterOrganization[organization][#][master][name]
			array("is_deleted", 0, 0, 0)
		)
	);
}

echo $output->dump(1);

unset($view->characterOrganization[organization]['list']);

// display all members of each organization
echo("<div>");
if($view->characterOrganization[organization][0]) {
	for($i = 0; $i < count($view->characterOrganization[organization]); $i++) {
		echo("<table class=\"orgTable\"><tr class=\"orgTR\">");
		$varTotalXP = 0;
		for($j = 0; $j < count($view->characterOrganization[organization][$i][master][0]); $j++) { // repeat for each master (master_ID = 0)
			echo("<td class=\"orgTD\">");
			$varTotalXP += buildOrganization($i, $view->characterOrganization[organization][$i][master][0][$j], $view->characterOrganization);
			echo("</td>");
		}
		$varTotalCR = xp2cr($varTotalXP);
		$varTotalGP = number_format(cr2gp(xp2cr($varTotalXP)));
		$varTotalXP = number_format($varTotalXP);
		echo("</tr><tr><div class=\"sup\"><b>{$view->characterOrganization[organization][$i][name]} &nbsp; CR {$varTotalCR}</b> (Total XP: {$varTotalXP}, Total GP: {$varTotalGP})</div></tr></table>");
	}
}
echo("&nbsp;</div>");

?>
