<?php

include "../inc/header.php";

if($pkid > 0) {

	// character id available: present details/edit page

?>
	<table>
		<tr>
			<td class="statBlockSingleWide statBlockSpacer">

				<div id="mainSection"></div>

				<h6 class="statBlockSection">Defense</h6>
				<div id="defenseSection"></div>

				<h6 class="statBlockSection">Offense</h6>
				<div id="offenseSection"></div>
				<div id="spellsSection" style="display:none"></div>

				<h6 class="statBlockSection">Statistics</h6>
				<div id="abilitiesSection"></div>
				<div id="featsSection"></div>
				<div id="skillsSection"></div>
				<div id="languagesSection"></div>
				<div id="specialqualitiesSection" style="display:none"></div>

			</td>
			<td class="statBlockSingleWide statBlockSpacer">

				<h6 class="statBlockSection">Treasure</h6>
				<div class="statBlockDetail" id="treasureSection"></div>

				<h6 class="statBlockSection">Description</h6>
				<div id="descriptionSection"></div>

				<h6 class="statBlockSection" style="display:none">Special Abilities</h6>
				<div class="statBlockDetail" id="specialabilitiesSection" style="display:none"></div>

			</td>
		</tr>
	</table>
	<table>
		<tr>
			<td class="statBlockDoubleWide statBlockSpacer">

				<h6 class="statBlockSection">Encounters</h6>
				<div id="encounterSection"></div>

				<h6 class="statBlockSection">Organization</h6>
				<div id="organizationSection"></div>

				<h6 class="statBlockSection">Stat Block (temporary)</h6>
				<div id="statblockSection" style="display:block"></div>

			</td>
		</tr>
	</table>
<?php

}
else {

	// no character id: present list page

?>
	<div class="statBlockSpacer" id="addSection"></div>
	<br>
	<form action="/character/" method="GET">
	<!-- navigation -->
	<table class="searchControlBar">
		<tr>
			<td align="left" width="40px"><?php if($getOffset > 0) echo("<input type=\"button\" value=\"<\" onClick=\"document.getElementById('page_start').value = " . max(($getOffset - $getLimit), 0) . "; this.form.submit();\">"); ?></td>
			<td align="center">
				Name:<input type="text" name="name" id="name" value="<?php echo($getNameKeyword); ?>">
				Location:<input type="text" name="loc" id="loc" value="<?php echo($getLocKeyword); ?>">
				<br>
				<input type="hidden" name="page_start" id="page_start" value="<?php echo($getOffset); ?>">
				Number of results per page:<select name="page_count">
					<option value="30"<?php if($getLimit == 30) echo(" selected"); ?>>30</option>
					<option value="60"<?php if($getLimit == 60) echo(" selected"); ?>>60</option>
					<option value="90"<?php if($getLimit == 90) echo(" selected"); ?>>90</option>
					<option value="120"<?php if($getLimit == 120) echo(" selected"); ?>>120</option>
				</select>
				<input type="submit" value="Search" onClick="document.getElementById('page_start').value = 0;">
				<input type="submit" value="Clear" onClick="document.getElementById('name').value = ''; document.getElementById('loc').value = ''; document.getElementById('page_start').value = 0;">
			</td>
			<td align="right" width="40px"><?php if($view->characterListMore > 0) echo("<input type=\"button\" value=\">\" onClick=\"document.getElementById('page_start').value = " . max(($getOffset + $getLimit), 0) . "; this.form.submit();\">"); ?></td>
		</tr>
	</table>
	<!-- list -->
	<table class="statBlockDoubleWide statBlockSpacer">
		<tr>
			<td class="statBlockDetail">
<?php if($view->characterListLeft[0]) echo(buildCharList($view->characterListLeft));?>
			</td>
			<td class="statBlockDetail">
<?php if($view->characterListRight[0]) echo(buildCharList($view->characterListRight));?>
			</td>
		</tr>
	</table>
	<!-- navigation -->
	<table class="searchControlBar">
		<tr>
			<td align="left" width="40px"><?php if($getOffset > 0) echo("<input type=\"button\" value=\"<\" onClick=\"document.getElementById('page_start').value = " . max(($getOffset - $getLimit), 0) . "; this.form.submit();\">"); ?></td>
			<td></td>
			<td align="right" width="40px"><?php if($view->characterListMore > 0) echo("<input type=\"button\" value=\">\" onClick=\"document.getElementById('page_start').value = " . max(($getOffset + $getLimit), 0) . "; this.form.submit();\">"); ?></td>
		</tr>
	</table>
	</form>
<?php
}

include "../view/footer.php";

?>
