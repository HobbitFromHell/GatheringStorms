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
	<table>
		<tr>
			<td class="statBlockSingleWide statBlockSpacer">

				<div id="leftListSection"></div>

			</td>
			<td class="statBlockSingleWide statBlockSpacer">

				<div id="rightListSection"></div>

			</td>
		</tr>
	</table>
<?php

}

include "../inc/footer.php";

?>
