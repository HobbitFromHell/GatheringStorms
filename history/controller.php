<?php

// create data collection to share information with the view
$view = new DataCollector;
$pageName = "History";

// set primary key id from get data
if(isset($_GET['id'])) {
	$pkid = sanitize($_GET['id']);
}

?>
	<script>
		// onload
		function start(pkid)
		{
			console.log("function start(pkid = " + pkid + ")")

			if(!pkid) {
				// ajax call to populate add section
				buildSection('Add')
				buildSection('leftList')
				buildSection('rightList')
			}
			else {
				// show loading widgets
				$("#mainSection").html(loadingWidget)
				$("#charactersSection").html(loadingWidget)
				$("#organizationsSection").html(loadingWidget)
	
				// ajax calls to populate subsections
				buildSection('Main')
				buildSection('Characters')
				buildSection('Organizations')
			}

			console.log(" ... CHECK")
		}
	</script>
