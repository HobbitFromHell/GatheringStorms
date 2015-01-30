<?php

// //////////
// validation
// //////////

if(strpos($pkid, "x") < 1) {
	echo "FAIL: Location ID {$pkid} is invalid";
	return false;
}


// //////
// insert
// //////

post_data("t_locations", "id", "id", Array("name", "cr", "alignment", "qualities", "disadvantages", "government", "population", "description", "imports", "exports"));


// //////
// select
// //////

$view = new DataCollector;

$view->locationMain = DataConnector::selectQuery("
	 SELECT l.`name`          AS `name`,
	        l.`cr`            AS `cr`,
	        l.`description`   AS `description`,
	        l.`alignment`     AS `alignment`,
	        l.`population`    AS `population`,
	        l.`government`    AS `government`,
	        l.`qualities`     AS `qualities`,
	        l.`disadvantages` AS `disadvantages`,
	        l.`imports`       AS `imports`,
	        l.`exports`       AS `exports`
	   FROM t_locations l
	  WHERE l.`id` = '{$pkid}'
");
if(!$view->locationMain[cr]) {
	$view->locationMain[cr] = 0;
}

// set settlement variables based on population
if($view->locationMain[population] == 0) {
	$view->locationMain[alignment] = "";
	$view->locationMain[type] = "Uninhabited";
	$view->locationMain[modifiers][base] = 0;
	$view->locationMain[qualities_count] = 0;
	$view->locationMain[danger] = 0;
	$view->locationMain[base_value] = 0;
	$view->locationMain[minor_magic] = 0;
	$view->locationMain[medium_magic] = 0;
	$view->locationMain[major_magic] = 0;
	$view->locationMain[purchase_limit] = 0;
	$view->locationMain[spellcasting] = 0;
}
if($view->locationMain[population] > 0 and $view->locationMain[population] <= 20) {
	$view->locationMain[type] = "Thorp";
	$view->locationMain[modifiers][base] = -4;
	$view->locationMain[qualities_count] = 1;
	$view->locationMain[danger] = -10;
	$view->locationMain[base_value] = 50;
	$view->locationMain[minor_magic] = 2;
	$view->locationMain[medium_magic] = 0;
	$view->locationMain[major_magic] = 0;
	$view->locationMain[purchase_limit] = 500;
	$view->locationMain[spellcasting] = 1;
}
if($view->locationMain[population] > 20 and $view->locationMain[population] <= 60) {
	$view->locationMain[type] = "Hamlet";
	$view->locationMain[modifiers][base] = -2;
	$view->locationMain[qualities_count] = 1;
	$view->locationMain[danger] = -5;
	$view->locationMain[base_value] = 200;
	$view->locationMain[minor_magic] = 3;
	$view->locationMain[medium_magic] = 0;
	$view->locationMain[major_magic] = 0;
	$view->locationMain[purchase_limit] = 1000;
	$view->locationMain[spellcasting] = 2;
}
if($view->locationMain[population] > 60 and $view->locationMain[population] <= 200) {
	$view->locationMain[type] = "Village";
	$view->locationMain[modifiers][base] = -1;
	$view->locationMain[qualities_count] = 2;
	$view->locationMain[danger] = 0;
	$view->locationMain[base_value] = 500;
	$view->locationMain[minor_magic] = 5;
	$view->locationMain[medium_magic] = 2;
	$view->locationMain[major_magic] = 0;
	$view->locationMain[purchase_limit] = 2500;
	$view->locationMain[spellcasting] = 3;
}
if($view->locationMain[population] > 200 and $view->locationMain[population] <= 2000) {
	$view->locationMain[type] = "Small town";
	$view->locationMain[modifiers][base] = 0;
	$view->locationMain[qualities_count] = 2;
	$view->locationMain[danger] = 0;
	$view->locationMain[base_value] = 1000;
	$view->locationMain[minor_magic] = 10;
	$view->locationMain[medium_magic] = 3;
	$view->locationMain[major_magic] = 0;
	$view->locationMain[purchase_limit] = 5000;
	$view->locationMain[spellcasting] = 4;
}
if($view->locationMain[population] > 2000 and $view->locationMain[population] <= 5000) {
	$view->locationMain[type] = "Large town";
	$view->locationMain[modifiers][base] = 0;
	$view->locationMain[qualities_count] = 3;
	$view->locationMain[danger] = 5;
	$view->locationMain[base_value] = 2000;
	$view->locationMain[minor_magic] = 13;
	$view->locationMain[medium_magic] = 5;
	$view->locationMain[major_magic] = 2;
	$view->locationMain[purchase_limit] = 10000;
	$view->locationMain[spellcasting] = 5;
}
if($view->locationMain[population] > 5000 and $view->locationMain[population] <= 10000) {
	$view->locationMain[type] = "Small city";
	$view->locationMain[modifiers][base] = +1;
	$view->locationMain[qualities_count] = 4;
	$view->locationMain[danger] = 5;
	$view->locationMain[base_value] = 4000;
	$view->locationMain[minor_magic] = 20;
	$view->locationMain[medium_magic] = 10;
	$view->locationMain[major_magic] = 3;
	$view->locationMain[purchase_limit] = 25000;
	$view->locationMain[spellcasting] = 6;
}
if($view->locationMain[population] > 10000 and $view->locationMain[population] <= 25000) {
	$view->locationMain[type] = "Large city";
	$view->locationMain[modifiers][base] = +2;
	$view->locationMain[qualities_count] = 5;
	$view->locationMain[danger] = 10;
	$view->locationMain[base_value] = 8000;
	$view->locationMain[minor_magic] = 20;
	$view->locationMain[medium_magic] = 13;
	$view->locationMain[major_magic] = 5;
	$view->locationMain[purchase_limit] = 50000;
	$view->locationMain[spellcasting] = 7;
}
if($view->locationMain[population] > 25000) {
	$view->locationMain[type] = "Metropolis";
	$view->locationMain[modifiers][base] = +4;
	$view->locationMain[qualities_count] = 6;
	$view->locationMain[danger] = 10;
	$view->locationMain[base_value] = 16000;
	$view->locationMain[minor_magic] = 99;
	$view->locationMain[medium_magic] = 20;
	$view->locationMain[major_magic] = 10;
	$view->locationMain[purchase_limit] = 100000;
	$view->locationMain[spellcasting] = 8;
}
if(strpos($view->locationMain[disadvantages], "Plagued") !== false) {
	$view->locationMain[modifiers][base] -= 2;
}
$view->locationMain[modifiers][corruption] = $view->locationMain[modifiers][crime] = $view->locationMain[modifiers][economy] = $view->locationMain[modifiers][law] = $view->locationMain[modifiers][lore] = $view->locationMain[modifiers][society] = $view->locationMain[modifiers][base];

// set settlement variables based on alignment
if(substr($view->locationMain[alignment], 0, 1) == "L") {
	$view->locationMain[modifiers][law] += 1;
}
if(substr($view->locationMain[alignment], 0, 1) == "C") {
	$view->locationMain[modifiers][crime] += 1;
}
if(substr($view->locationMain[alignment], -1, 1) == "G") {
	$view->locationMain[modifiers][society] += 1;
}
if(substr($view->locationMain[alignment], -1, 1) == "E") {
	$view->locationMain[modifiers][corruption] += 1;
}

// set settlement variables based on disadvantages
if(strpos($view->locationMain[disadvantages], "Anarchy") !== false) {
	$view->locationMain[government] = "Anarchy";
	$view->locationMain[modifiers][corruption] += 4;
	$view->locationMain[modifiers][crime] += 4;
	$view->locationMain[modifiers][economy] -= 4;
	$view->locationMain[modifiers][society] -= 4;
	$view->locationMain[modifiers][law] -= 6;
	$view->locationMain[danger] += 20;
}
if(strpos($view->locationMain[disadvantages], "Cursed - Corrupt") !== false) {
	$view->locationMain[modifiers][corruption] -= 4;
}
if(strpos($view->locationMain[disadvantages], "Cursed - Crime") !== false) {
	$view->locationMain[modifiers][crime] -= 4;
}
if(strpos($view->locationMain[disadvantages], "Cursed - Economy") !== false) {
	$view->locationMain[modifiers][economy] -= 4;
}
if(strpos($view->locationMain[disadvantages], "Cursed - Law") !== false) {
	$view->locationMain[modifiers][law] -= 4;
}
if(strpos($view->locationMain[disadvantages], "Cursed - Lore") !== false) {
	$view->locationMain[modifiers][lore] -= 4;
}
if(strpos($view->locationMain[disadvantages], "Cursed - Society") !== false) {
	$view->locationMain[modifiers][society] -= 4;
}
if(strpos($view->locationMain[disadvantages], "Hunted") !== false) {
	$view->locationMain[modifiers][economy] -= 4;
	$view->locationMain[modifiers][law] -= 4;
	$view->locationMain[modifiers][society] -= 4;
	$view->locationMain[danger] += 20;
	$view->locationMain[base_value] *= 0.80;
}
if(strpos($view->locationMain[disadvantages], "Impoverished") !== false) {
	$view->locationMain[modifiers][crime] += 1;
	$view->locationMain[modifiers][corruption] += 1;
	$view->locationMain[base_value] *= 0.50;
	$view->locationMain[minor_magic] *= 0.50;
	$view->locationMain[medium_magic] *= 0.50;
	$view->locationMain[major_magic] *= 0.50;
}
if(strpos($view->locationMain[disadvantages], "Plagued") !== false) {
	$view->locationMain[base_value] *= 0.80;
}
if(strpos($view->locationMain[qualities], "Lack of Spellcasters") !== false) {
	$view->locationMain[spellcasting] -= 2;
}

// set settlement variables based on government
if($view->locationMain[government] == "Council") {
	$view->locationMain[modifiers][society] += 4;
	$view->locationMain[modifiers][law] -= 2;
	$view->locationMain[modifiers][lore] -= 2;
}
if($view->locationMain[government] == "Magical") {
	$view->locationMain[modifiers][lore] += 2;
	$view->locationMain[modifiers][corruption] -= 2;
	$view->locationMain[modifiers][society] -= 2;
	$view->locationMain[spellcasting] += 1;
}
if($view->locationMain[government] == "Overlord") {
	$view->locationMain[modifiers][corruption] += 2;
	$view->locationMain[modifiers][law] += 2;
	$view->locationMain[modifiers][crime] -= 2;
	$view->locationMain[modifiers][society] -= 2;
}
if($view->locationMain[government] == "Syndicate") {
	$view->locationMain[modifiers][corruption] += 2;
	$view->locationMain[modifiers][economy] += 2;
	$view->locationMain[modifiers][crime] += 2;
	$view->locationMain[modifiers][law] -= 6;
}

// set settlement variables based on qualities
if(strpos($view->locationMain[qualities], "Academic") !== false) {
	$view->locationMain[modifiers][lore] += 1;
	$view->locationMain[spellcasting] += 1;
}
if(strpos($view->locationMain[qualities], "Holy Site") !== false) {
	$view->locationMain[modifiers][corruption] -= 2;
	$view->locationMain[spellcasting] += 2;
}
if(strpos($view->locationMain[qualities], "Insular") !== false) {
	$view->locationMain[modifiers][law] += 1;
	$view->locationMain[modifiers][crime] -= 1;
}
if(strpos($view->locationMain[qualities], "Magically Attuned") !== false) {
	$view->locationMain[base_value] *= 1.2;
	$view->locationMain[purchase_limit] *= 1.2;
	$view->locationMain[spellcasting] += 2;
}
if(strpos($view->locationMain[qualities], "Notorious") !== false) {
	$view->locationMain[modifiers][crime] += 1;
	$view->locationMain[modifiers][law] -= 1;
	$view->locationMain[danger] += 10;
	$view->locationMain[base_value] *= 1.30;
	$view->locationMain[purchase_limit] *= 1.50;
}
if(strpos($view->locationMain[qualities], "Pious") !== false) {
	$view->locationMain[spellcasting] += 1;
}
if(strpos($view->locationMain[qualities], "Prosperous") !== false) {
	$view->locationMain[modifiers][economy] += 1;
	$view->locationMain[base_value] *= 1.3;
	$view->locationMain[purchase_limit] *= 1.5;
}
if(strpos($view->locationMain[qualities], "Intolerant") !== false) {
}
if(strpos($view->locationMain[qualities], "Rumormonger") !== false) {
	$view->locationMain[modifiers][lore] += 1;
	$view->locationMain[modifiers][society] -= 1;
}
if(strpos($view->locationMain[qualities], "Strategic") !== false) {
	$view->locationMain[modifiers][economy] += 1;
	$view->locationMain[base_value] *= 1.1;
}
if(strpos($view->locationMain[qualities], "Superstitious") !== false) {
	$view->locationMain[modifiers][crime] -= 4;
	$view->locationMain[modifiers][law] += 2;
	$view->locationMain[modifiers][society] += 2;
	$view->locationMain[spellcasting] -= 2;
}
if(strpos($view->locationMain[qualities], "Attraction") !== false) {
	$view->locationMain[modifiers][economy] += 1;
	$view->locationMain[base_value] *= 1.2;
}

// build lists (from database ENUM, SET, or hard coded here)
$view->locationMain[government] = $view->getEnum("t_locations", "government", $view->locationMain[government]);
$view->locationMain[qualities] = $view->getEnum("t_locations", "qualities", explode(",", $view->locationMain[qualities]));
$view->locationMain[disadvantages] = $view->getEnum("t_locations", "disadvantages", explode(",", $view->locationMain[disadvantages]));
$view->locationMain[alignment] = $view->setList($view->locationMain[alignment], array("LG","NG","CG","LN","N","CN","LE","NE","CE"));


// ////////////////////////////
// communicate with parent page
// ////////////////////////////

echo "<script>\n";
echo "buildSection('Inhabitant')\n";
echo "</script>\n";

?>
