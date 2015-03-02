<?php

require_once "../inc/model.inc";
require_once "../inc/header.php";

if(isset($_GET['id'])) {
	$varFrom = sanitize($_GET['id']);
	if(isset($_POST['newLocation'])) {
		$varTo = sanitize($_POST['newLocation']);

		// if format of new location is legal:
		if(preg_match("/^-?\d+x-?\d+$/", $varTo)) {
			// proceed with move
			try {
				$varRecord = DataConnector::updateQuery("
					 UPDATE t_locations SET id = '{$varTo}' WHERE id = '{$varFrom}';
					");
			}
			catch(Exception $e) {
				// display error
				echo("Error! Could not update database.");
				return FALSE;
			}

			// update characters
			$x = DataConnector::updateQuery("
				 UPDATE t_characters SET location_id = '{$varTo}' WHERE location_id = '{$varFrom}';
				");

			// update organizations
			$x = DataConnector::updateQuery("
				 UPDATE t_organizations SET location_id = '{$varTo}' WHERE location_id = '{$varFrom}';
				");

			// update encounters
			$x = DataConnector::updateQuery("
				 UPDATE t_encounters SET location_id = '{$varTo}' WHERE location_id = '{$varFrom}';
				");

			// redirect to view/edit page
			echo("<script>window.location.assign(\"/location/?id={$varTo}\")</script>");
			$varPrompt = "<br><i>Moved. If you are not redirect to the new location page, click <a href=\"/location/?id={$varTo}\">here</a> to edit the location.</i>";
		}
		else {
			// display error
			echo("Error! Bad new location format.");
			return FALSE;
		}
	}
}

require_once "../inc/footer.php";
?>
