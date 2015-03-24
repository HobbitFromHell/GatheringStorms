<?php

include "../inc/header.php";

if(isset($pkid)) {

	// location id available: present details/edit page

?>
	<table>
		<tr>
			<td class="statBlockSingleWide statBlockSpacer">

				<div id="mainSection"></div>

			</td>
			<td class="statBlockSingleWide statBlockSpacer">

				<h6 class="statBlockSection">Inhabitants</h6>
				<div id="inhabitantSection"></div>

				<h6 class="statBlockSection">Organizations</h6>
				<div id="organizationSection"></div>

				<h6 class="statBlockSection">History</h6>
				<div id="historySection"></div>

			</td>
		</tr>
	</table>
	<table>
		<tr>
			<td class="statBlockDoubleWide statBlockSpacer">

				<h6 class="statBlockSection">Regional Map</h6>
				<div id="mapSection"></div>

				<h6 class="statBlockSection">Local Maps</h6>
				<div id="localmapSection"></div>

				<h6 class="statBlockSection">Encounters</h6>
				<div id="encounterSection"></div>

			</td>
		</tr>
	</table>
<?php

}
else {

	// no location id: present list page

/* BIG MAP
	$varMinX = $varMinY = -50;
	$varMaxX = $varMaxY = 50;

	echo("<table class=\"orgTable\" style=\"margin-left:40px\">");
	for($varY = $varMinY; $varY < $varMaxY + 1; ++ $varY) {
		echo("<tr>");
		for($varX = $varMinX; $varX < $varMaxX + 1; ++ $varX) {
			echo("<td class=\"orgTD\" style=\"background-color:");
			if($varBigMap[$varX][$varY]['important']) {
				echo("red;");
			}
			else {
				if(isset($varBigMap[$varX][$varY])) {
					$tmpColour = terrainColour($varBigMap[$varX][$varY]['terrain'], $varBigMap[$varX][$varY]['growth']);
					echo("#{$tmpColour};");
				}
				else {
					echo("#999;");
				}
			}
			echo("\"><a href=\"/location/?id={$varX}x{$varY}\">");
			echo("<img src=\"/img/map-road0.gif\" width=\"8\" height=\"8\">");
			echo("</a></td>");
		}
		echo("</tr>");
	}
	echo("</table>");
*/
?>
	<br>
	<br>
	<form action="/location/" method="GET">
	<!-- navigation -->
	<table class="searchControlBar">
		<tr>
			<td align="left" width="40px"><?php if($getOffset > 0) echo("<input type=\"button\" value=\"<\" onClick=\"document.getElementById('page_start').value = " . max(($getOffset - $getLimit), 0) . "; this.form.submit();\">"); ?></td>
			<td align="center">
				Location:<input type="text" name="loc" id="loc" value="<?php echo($getLocKeyword); ?>">
				Region:<input type="text" name="region" id="region" value="<?php echo($getRegionKeyword); ?>">
				<br>
				<input type="hidden" name="page_start" id="page_start" value="<?php echo($getOffset); ?>">
				Number of results per page:<select name="page_count">
					<option value="30"<?php if($getLimit == 30) echo(" selected"); ?>>30</option>
					<option value="60"<?php if($getLimit == 60) echo(" selected"); ?>>60</option>
					<option value="90"<?php if($getLimit == 90) echo(" selected"); ?>>90</option>
					<option value="120"<?php if($getLimit == 120) echo(" selected"); ?>>120</option>
				</select>
				<input type="submit" value="Search">
				<input type="submit" value="Clear" onClick="document.getElementById('region').value='';document.getElementById('loc').value='';">
			</td>
			<td align="right" width="40px"><?php if($view->locationListMore > 0) echo("<input type=\"button\" value=\">\" onClick=\"document.getElementById('page_start').value = " . max(($getOffset + $getLimit), 0) . "; this.form.submit();\">"); ?></td>
		</tr>
	</table>
	<!-- list -->
	<table class="statBlockDoubleWide statBlockSpacer">
		<tr>
			<td class="statBlockSingleWide">
<?php if($view->locationListLeft[0]) echo(buildLocList($view->locationListLeft));?>
			</td>
			<td class="statBlockSingleWide">
<?php if($view->locationListRight[0]) echo(buildLocList($view->locationListRight));?>
			</td>
		</tr>
	</table>
	<!-- navigation -->
	<table class="searchControlBar">
		<tr>
			<td align="left" width="40px"><?php if($getOffset > 0) echo("<input type=\"button\" value=\"<\" onClick=\"document.getElementById('page_start').value = " . max(($getOffset - $getLimit), 0) . "; this.form.submit();\">"); ?></td>
			<td></td>
			<td align="right" width="40px"><?php if($view->locationListMore > 0) echo("<input type=\"button\" value=\">\" onClick=\"document.getElementById('page_start').value = " . max(($getOffset + $getLimit), 0) . "; this.form.submit();\">"); ?></td>
		</tr>
	</table>
	</form>
<?php
/*
	echo("<table width=\"100%\"><tr>\n");
	echo("{$varPrevNext}\n</tr>\n<tr>\n");
	while($varRecord) {
		echo("<td class=\"listTD\"><a href=\"/location/?id={$varRecord['id']}\"><b>{$varRecord['name']}</b></a></td>\n");
		echo("<td class=\"listTD\">CR&nbsp;" . cr_display($varRecord['cr']) . "</td>\n");
		echo("<td class=\"listTD\">{$varRecord['region']} {$varRecord['alignment']}</td>\n");
		if(++$varCounter % 3 == 0) {
			echo("</tr><tr>\n");
		}
		else {
			echo("<td> </td>\n");
		}
		$varRecord = DataConnector::selectQuery();
	}
	echo("</tr>\n<tr>\n{$varPrevNext}\n</tr>");
	echo("</table>\n");
*/
}
include "../inc/footer.php";

?>
