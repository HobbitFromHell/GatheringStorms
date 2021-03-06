<?php

include "../inc/header.php";

if($pkid > 0) {

	// encounter id available: present details/edit page

?>
	<table>
		<tr>
			<td class="statBlockSingleWide statBlockSpacer">

				<h6 class="statBlockSection">Story</h6>
				<div id="storySection"></div>

				<h6 class="statBlockSection">Chapter</h6>
				<div id="chapterSection"></div>

				<h6 class="statBlockSection">Scene</h6>
				<div id="sceneSection"></div>

			</td>
			<td class="statBlockSingleWide statBlockSpacer">

				<h6 class="statBlockSection">Cast</h6>
				<div id="castSection"></div>

				<h6 class="statBlockSection">Regional Map</h6>
				<div id="mapSection"></div>

			</td>
		</tr>
	</table>
	<table>
		<tr>
			<td class="statBlockDoubleWide statBlockSpacer">

				<h6 class="statBlockSection">Local Maps</h6>
				<div id="localmapSection"></div>

			</td>
		</tr>
	</table>
<?php

}
else {

	// no encounter id: present list page

?>
	<div class="statBlockSpacer" id="addSection"></div>
	<br>
	<form action="/encounter/" method="GET">
	<!-- navigation -->
	<table class="searchControlBar">
		<tr>
			<td align="left" width="40px"><?php if($getOffset > 0) echo("<input type=\"button\" value=\"<\" onClick=\"document.getElementById('page_start').value = " . max(($getOffset - $getLimit), 0) . "; this.form.submit();\">"); ?></td>
			<td align="center">
				Name:<input type="text" name="name" id="searchname" value="<?php echo($getNameKeyword); ?>">
				Location:<input type="text" name="loc" id="searchloc" value="<?php echo($getLocKeyword); ?>">
				<br>
				<input type="hidden" name="page_start" id="page_start" value="<?php echo($getOffset); ?>">
				Number of results per page:<select name="page_count">
					<option value="30"<?php if($getLimit == 30) echo(" selected"); ?>>30</option>
					<option value="45"<?php if($getLimit == 45) echo(" selected"); ?>>45</option>
					<option value="60"<?php if($getLimit == 60) echo(" selected"); ?>>60</option>
					<option value="75"<?php if($getLimit == 75) echo(" selected"); ?>>75</option>
				</select>
				<input type="submit" value="Search" onClick="document.getElementById('page_start').value = 0;">
				<input type="submit" value="Clear" onClick="document.getElementById('searchname').value = ''; document.getElementById('searchloc').value = ''; document.getElementById('page_start').value = 0;">
			</td>
			<td align="right" width="40px"><?php if($view->encounterListMore > 0) echo("<input type=\"button\" value=\">\" onClick=\"document.getElementById('page_start').value = " . max(($getOffset + $getLimit), 0) . "; this.form.submit();\">"); ?></td>
		</tr>
	</table>
	<!-- list -->
	<table class="statBlockDoubleWide statBlockSpacer">
		<tr>
			<td><?php if($view->encounterList[0]) echo(buildEncList($view->encounterList));?></td>
		</tr>
	</table>
	<!-- navigation -->
	<table class="searchControlBar">
		<tr>
			<td align="left" width="40px"><?php if($getOffset > 0) echo("<input type=\"button\" value=\"<\" onClick=\"document.getElementById('page_start').value = " . max(($getOffset - $getLimit), 0) . "; this.form.submit();\">"); ?></td>
			<td></td>
			<td align="right" width="40px"><?php if($view->encounterListMore > 0) echo("<input type=\"button\" value=\">\" onClick=\"document.getElementById('page_start').value = " . max(($getOffset + $getLimit), 0) . "; this.form.submit();\">"); ?></td>
		</tr>
	</table>
	</form>
<?php
}

include "../inc/footer.php";

?>
