<?php

// ////////
// validate
// ////////

if ($pkid != 0) {
	echo "FAIL: History ID {$pkid} is invalid";
	return false;
}


// //////
// insert
// //////

// custom set of inserts to add new historic event
// nothing needed to create db stub
$varNewHistoryID = DataConnector::updateQuery("
	 INSERT INTO t_history (`name`)
	 VALUES ('')
");

// redirect to view/edit page
echo("<script>window.location.assign(\"/history/?id={$varNewHistoryID}\")</script>");
$varPrompt = "<br><i>Added. If you are not redirect to the detail page, click <a href=\"/history/?id={$varNewHistoryID}\">here</a> to edit the encounter.</i>";


// //////
// select
// //////

// nothing to do

?>
