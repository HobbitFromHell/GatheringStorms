<?php

// organization section
$output = new BuildOutput("organization");

// id
$output->add("id", $pkid, 0, 0);

// organizations
$output->add("organization", $view->locationOrganization[organization], 0, "", "130");

echo $output->dump(1);

unset($view->locationOrganization[organization]['list']);

// display all members of each organization
echo("<div>");
if($view->locationOrganization[organization][0]) {
	for($i = 0; $i < count($view->locationOrganization[organization]); $i++) {
		echo("<table class=\"orgTable\"><tr class=\"orgTR\">");
		$varTotalXP = 0;
		for($j = 0; $j < count($view->locationOrganization[organization][$i][master][0]); $j++) {
			echo("<td class=\"orgTD\">");
			$varTotalXP += buildOrganization($i, $view->locationOrganization[organization][$i][master][0][$j], $view->locationOrganization);
			echo("</td>");
		}
		$varTotalCR = xp2cr($varTotalXP);
		$varTotalGP = number_format(cr2gp(xp2cr($varTotalXP)));
		$varTotalXP = number_format($varTotalXP);
		echo("</tr><tr><div class=\"sup\"><b>{$view->locationOrganization[organization][$i][name]}</b> (Total CR: {$varTotalCR}, Total XP: {$varTotalXP}, Total GP: {$varTotalGP})</div></tr></table></div>");
	}
}
echo("&nbsp;</div>");

?>
