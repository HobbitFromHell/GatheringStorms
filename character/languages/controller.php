<?php

// //////////
// validation
// //////////

if (!($pkid > 0 and $pkid < 65000)) {
	echo "FAIL: Character ID {$pkid} is invalid";
	return false;
}

// //////
// insert
// //////

post_data("t_characters_languages", "id", "character_id", Array(Array("language", "language_id"), "is_deleted"));


// //////
// select
// //////

$view->characterLanguages[language][] = DataConnector::selectQuery("
	 SELECT cl.`id`          AS `id`,
	        cl.`language_id` AS `language_id`,
	         l.`name`        AS `name`,
	        cl.`is_deleted`  AS `is_deleted`
	   FROM t_characters_languages cl
	   JOIN t_languages l ON l.`id` = cl.`language_id`
	  WHERE cl.`character_id` = {$pkid}
	    AND cl.`is_deleted` != 'Yes'
	  ORDER BY l.`name`
");
while($j = DataConnector::selectQuery()) {
	$view->characterLanguages[language][] = $j;
}

// build language list
$j = DataConnector::selectQuery("
	 SELECT l.`id`           AS `id`,
	        l.`name`         AS `name`
	   FROM t_languages l
	  ORDER BY l.`name`
");
while($j) {
	$view->characterLanguages[language]['list'][] = $j;
	$j = DataConnector::selectQuery();
}


// ////////////////////////////
// communicate with parent page
// ////////////////////////////

echo "<script>\n";
$varUpload = $view->characterLanguages[language];
unset($varUpload['list']);
$js_array = json_encode($varUpload);
echo "charSheet.languages = {$js_array}\n";
echo "buildSection('Defense')\n";
echo "</script>\n";

?>
