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
for($i = 0; $i < count($view->locationOrganization[organization]); $i++) {
	echo("<div><b>{$view->locationOrganization[organization][$i][name]}</b><br>");
	echo("<table class=\"orgTable\"><tr class=\"orgTR\">");
	for($j = 0; $j < count($view->locationOrganization[organization][$i][master][0]); $j++) {
		echo("<td class=\"orgTD\">");
		build_org($i, $view->locationOrganization[organization][$i][master][0][$j], $view->locationOrganization);
		echo("</td>");
	}
	echo("</tr></table></div>");
}

?>
