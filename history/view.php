<?php

include "../inc/header.php";

if($pkid > 0) {

	// history id available: present details/edit page

?>
	<table>
		<tr>
			<td class="statBlockSingleWide statBlockSpacer">

				<h6 class="statBlockSection">Historic Entry</h6>
				<div id="mainSection"></div>

			</td>
			<td class="statBlockSingleWide statBlockSpacer">

				<h6 class="statBlockSection">Historic Figures</h6>
				<div id="charactersSection"></div>

				<h6 class="statBlockSection">Historic Groups</h6>
				<div id="organizationsSection"></div>

			</td>
		</tr>
	</table>
<?php

}
else {

	// no history id: present list page

?>
	<div class="statBlockSpacer" id="addSection"></div>
	<br>
	<form action="/history/" method="GET">
	<!-- navigation -->
	<table class="searchControlBar">
		<tr>
			<td align="left" width="40px"><?php if($getOffset > 0) echo("<input type=\"button\" value=\"<\" onClick=\"document.getElementById('page_start').value = " . max(($getOffset - $getLimit), 0) . "; this.form.submit();\">"); ?></td>
			<td align="center">
				Name:<input type="text" name="name" id="name" value="<?php echo($getNameKeyword); ?>">
				Location:<input type="text" name="loc" id="loc" value="<?php echo($getLocKeyword); ?>">
				Year:<input type="text" name="from" id="from" size="5" value="<?php echo($getFromKeyword); ?>">
				-<input type="text" name="to" id="to" size="5" value="<?php echo($getToKeyword); ?>">DR
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
			<td align="right" width="40px"><?php if($view->historyListMore > 0) echo("<input type=\"button\" value=\">\" onClick=\"document.getElementById('page_start').value = " . max(($getOffset + $getLimit), 0) . "; this.form.submit();\">"); ?></td>
		</tr>
	</table>
	<!-- list -->
	<table class="statBlockDoubleWide statBlockSpacer">
		<tr>
			<td class="statBlockSingleWide">
<?php if($view->historyListLeft[0]) echo(buildHistoryList($view->historyListLeft));?>
			</td>
			<td class="statBlockSingleWide">
<?php if($view->historyListRight[0]) echo(buildHistoryList($view->historyListRight));?>
			</td>
		</tr>
	</table>
	<!-- navigation -->
	<table class="searchControlBar">
		<tr>
			<td align="left" width="40px"><?php if($getOffset > 0) echo("<input type=\"button\" value=\"<\" onClick=\"document.getElementById('page_start').value = " . max(($getOffset - $getLimit), 0) . "; this.form.submit();\">"); ?></td>
			<td></td>
			<td align="right" width="40px"><?php if($view->historyListMore > 0) echo("<input type=\"button\" value=\">\" onClick=\"document.getElementById('page_start').value = " . max(($getOffset + $getLimit), 0) . "; this.form.submit();\">"); ?></td>
		</tr>
	</table>
	</form>
<?php
}

include "../inc/footer.php";

?>
