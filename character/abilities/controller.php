<?php

// ////////
// validate
// ////////

if (!($pkid > 0 and $pkid < 65000)) {
	echo "FAIL: Character ID {$pkid} is invalid";
	return false;
}
/*
if(!(isset($post_str) and isset($post_dex) and isset($post_con) and isset($post_int) and isset($post_wis) and isset($post_cha))) {
	echo "FAIL: Ability score required";
	return false;
}
if ($post_str < 1 or $post_str > 99 or $post_dex < 1 or $post_dex > 99 or $post_con < 0 or $post_con > 99 or $post_int < 1 or $post_int > 99 or $post_wis < 1 or $post_wis > 99 or $post_cha < 1 or $post_cha > 99) {
	echo "FAIL: Ability score is out of range";
	return false;
}
*/


// //////
// insert
// //////

post_data("t_characters", "id", "id", Array("str", "dex", "con", "int", "wis", "cha"));


// //////
// select
// //////

$view = new DataCollector;
$view->characterAbilities = DataConnector::selectQuery("
	 SELECT pc.`str`        AS `str`,
	        pc.`dex`        AS `dex`,
	        pc.`con`        AS `con`,
	        pc.`int`        AS `int`,
	        pc.`wis`        AS `wis`,
	        pc.`cha`        AS `cha`
	   FROM t_characters pc
	  WHERE pc.`id` = {$pkid}
");


// ////////////////////////////
// communicate with parent page
// ////////////////////////////

echo "<script>\n";
echo "charSheet.setValue(\"str\", \"{$view->characterAbilities[str]}\")\n";
echo "charSheet.setValue(\"dex\", \"{$view->characterAbilities[dex]}\")\n";
echo "charSheet.setValue(\"con\", \"{$view->characterAbilities[con]}\")\n";
echo "charSheet.setValue(\"int\", \"{$view->characterAbilities[int]}\")\n";
echo "charSheet.setValue(\"wis\", \"{$view->characterAbilities[wis]}\")\n";
echo "charSheet.setValue(\"cha\", \"{$view->characterAbilities[cha]}\")\n";
echo "buildSection('Main');\n";
echo "</script>\n";

?>
