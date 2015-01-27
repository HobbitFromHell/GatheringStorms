<?php

// //////////
// validation
// //////////

if (!($pkid > 0 and $pkid < 65000)) {
	echo "FAIL: Character ID {$pkid} is invalid";
	return false;
}

$view = new DataCollector();

// read post data required to customize option lists
$view->characterFeats[param][race] = sanitize($_POST[race]);
$view->characterFeats[param][total_level] = sanitize($_POST[total_level]);
$view->characterFeats[param][total_bab] = sanitize($_POST[total_bab]);
$view->characterFeats[param][str] = sanitize($_POST[str]);
$view->characterFeats[param][dex] = sanitize($_POST[dex]);
$view->characterFeats[param][con] = sanitize($_POST[con]);
$view->characterFeats[param][int] = sanitize($_POST[int]);
$view->characterFeats[param][wis] = sanitize($_POST[wis]);
$view->characterFeats[param][cha] = sanitize($_POST[cha]);


// //////
// insert
// //////

post_data("t_characters_feats", "id", "character_id", Array(Array("feat", "feat_id"), "detail", "is_deleted"));


// //////
// SELECT
// //////

$view->characterFeats[feat][] = DataConnector::selectQuery("
	 SELECT cf.`id`          AS `id`,
	        cf.`feat_id`     AS `feat_id`,
	         f.`name`        AS `name`,
	        CONCAT(f.`description`, '\n\n', f.`benefit`) AS `description`,
	        cf.`detail`      AS `detail`,
	        cf.`is_deleted`  AS `is_deleted`
	   FROM t_characters_feats cf
	   JOIN t_feats f ON f.`id` = cf.`feat_id`
	  WHERE cf.`character_id` = {$pkid}
	    AND cf.`is_deleted` != 'Yes'
");

while($j = DataConnector::selectQuery()) {
	$view->characterFeats[feat][] = $j;
}

// build feats list
$j = DataConnector::selectQuery("
	 SELECT f.`id`                  AS `id`,
	        f.`name`                AS `name`,
	        f.`is_combat`           AS `is_combat`,
	        f.`is_regional`         AS `is_regional`,
	        f.`is_metamagic`        AS `is_metamagic`,
	        f.`is_creation`         AS `is_creation`,
	        f.`is_skill`            AS `is_skill`,
	        f.`prereq_caster_level` AS `prereq_level`,
	        f.`prereq_race`         AS `prereq_race`,
	        f.`prereq_bab`          AS `prereq_bab`,
	        f.`prereq_str`          AS `prereq_str`,
	        f.`prereq_dex`          AS `prereq_dex`,
	        f.`prereq_int`          AS `prereq_int`,
	        f.`prereq_wis`          AS `prereq_wis`,
	        f.`prereq_cha`          AS `prereq_cha`
	   FROM t_feats f
	  ORDER BY f.`type`, f.`name`
");

// build list category
$view->characterFeats[feat][regional_list][][name] = "---------- Regional Feats ----------";
$view->characterFeats[feat][combat_list][][name] = "---------- Combat Feats ----------";
$view->characterFeats[feat][metamagic_list][][name] = "---------- Metamagic Feats ----------";
$view->characterFeats[feat][creation_list][][name] = "---------- Item Creation Feats ----------";
$view->characterFeats[feat][skill_list][][name] = "---------- Skill Feats ----------";
$view->characterFeats[feat][other_list][][name] = "---------- Other Feats ----------";
$view->characterFeats[feat][unavailable_list][][name] = "---------- Unavailable ----------";

// sort feats into correct category
while($j) {
	// check if prerequisites are reached
	if($j[prereq_level] <= $view->characterFeats[param][total_level]
	   and (!$j[prereq_race] or strpos($j[prereq_race], $view->characterFeats[param][race]) !== FALSE)
	   and $j[prereq_bab] <= $view->characterFeats[param][total_bab]
	   and $j[prereq_str] <= $view->characterFeats[param][str]
	   and $j[prereq_dex] <= $view->characterFeats[param][dex]
	   and $j[prereq_int] <= $view->characterFeats[param][int]
	   and $j[prereq_wis] <= $view->characterFeats[param][wis]
	   and $j[prereq_cha] <= $view->characterFeats[param][cha]) {
		if($j[is_combat] == "Yes") {
			$view->characterFeats[feat][combat_list][] = $j;
		}
		if($j[is_regional] == "Yes") {
			$view->characterFeats[feat][regional_list][] = $j;
		}
		if($j[is_metamagic] == "Yes") {
			$view->characterFeats[feat][metamagic_list][] = $j;
		}
		if($j[is_creation] == "Yes") {
			$view->characterFeats[feat][creation_list][] = $j;
		}
		if($j[is_skill] == "Yes") {
			$view->characterFeats[feat][skill_list][] = $j;
		}
		if($j[is_combat] == "No" and $j[is_regional] == "No" and $j[is_metamagic] == "No" and $j[is_creation] == "No" and $j[is_skill] == "No") {
			$view->characterFeats[feat][other_list][] = $j;
		}
	}
	else {
		$temp = " (";
		if($j[prereq_level] > $view->characterFeats[param][prereq_level]) {
			$temp .= "CL " . $j[prereq_level] . ", ";
		}
		if($j[prereq_bab] > $view->characterFeats[param][total_bab]) {
			$temp .= "BAB " . $j[prereq_bab] . ", ";
		}
		if($j[prereq_str] > $view->characterFeats[param][prereq_str]) {
			$temp .= "Str " . $j[prereq_str] . ", ";
		}
		if($j[prereq_dex] > $view->characterFeats[param][prereq_dex]) {
			$temp .= "Dex " . $j[prereq_dex] . ", ";
		}
		if($j[prereq_int] > $view->characterFeats[param][prereq_int]) {
			$temp .= "Int " . $j[prereq_int] . ", ";
		}
		if($j[prereq_wis] > $view->characterFeats[param][prereq_wis]) {
			$temp .= "Wis " . $j[prereq_wis] . ", ";
		}
		if($j[prereq_cha] > $view->characterFeats[param][prereq_cha]) {
			$temp .= "Cha " . $j[prereq_cha] . ", ";
		}
		if($j[prereq_race] and strpos($j[prereq_race], $view->characterFeats[param][race]) === FALSE) {
			$temp .= "Race, ";
		}
		$j[name] .= substr($temp, 0, -2) . ")";
		$view->characterFeats[feat][unavailable_list][] = $j;
	}
	$j = DataConnector::selectQuery();
}

// build master list from categories
foreach($view->characterFeats[feat][regional_list] as $listItem) {
	$view->characterFeats[feat]['list'][] = $listItem;
}
unset($view->characterFeats[feat][regional_list]);
foreach($view->characterFeats[feat][combat_list] as $listItem) {
	$view->characterFeats[feat]['list'][] = $listItem;
}
unset($view->characterFeats[feat][combat_list]);
foreach($view->characterFeats[feat][metamagic_list] as $listItem) {
	$view->characterFeats[feat]['list'][] = $listItem;
}
unset($view->characterFeats[feat][metamagic_list]);
foreach($view->characterFeats[feat][creation_list] as $listItem) {
	$view->characterFeats[feat]['list'][] = $listItem;
}
unset($view->characterFeats[feat][creation_list]);
foreach($view->characterFeats[feat][skill_list] as $listItem) {
	$view->characterFeats[feat]['list'][] = $listItem;
}
unset($view->characterFeats[feat][skill_list]);
foreach($view->characterFeats[feat][other_list] as $listItem) {
	$view->characterFeats[feat]['list'][] = $listItem;
}
unset($view->characterFeats[feat][other_list]);
foreach($view->characterFeats[feat][unavailable_list] as $listItem) {
	$view->characterFeats[feat]['list'][] = $listItem;
}
unset($view->characterFeats[feat][unavailable_list]);


// ////////////////////////////
// communicate with parent page
// ////////////////////////////

echo "<script>\n";
$varUpload = $view->characterFeats[feat];
unset($varUpload['list']);
$js_array = json_encode($varUpload);
echo "charSheet.feats = {$js_array}\n";
echo "buildSection('Skills', 'class_list=' + JSON.stringify(charSheet.class_level))\n";
echo "</script>\n";

?>
