<?php

// //////////
// validation
// //////////

if(!($pkid > 0 and $pkid < 65000)) {
	echo "FAIL: Character ID {$pkid} is invalid";
	return false;
}


// //////
// insert
// //////

post_data("t_characters_equipment", "id", "character_id", Array(Array("melee", "equipment_id"), "quality", "repair", "is_equipped", "is_deleted"));
post_data("t_characters_equipment", "id", "character_id", Array(Array("offhand", "equipment_id"), "quality", "repair", "is_equipped", "is_deleted"));
post_data("t_characters_equipment", "id", "character_id", Array(Array("ranged", "equipment_id"), "quality", "repair", "is_equipped", "is_deleted"));
post_data("t_characters_equipment", "id", "character_id", Array(Array("armour", "equipment_id"), "quality", "repair", "is_equipped", "is_deleted"));
post_data("t_characters_equipment", "id", "character_id", Array(Array("shield", "equipment_id"), "quality", "repair", "is_equipped", "is_deleted"));
post_data("t_characters_equipment", "id", "character_id", Array(Array("othercombat", "equipment_id"), "quality", "repair", "is_equipped", "is_deleted"));
post_data("t_characters_equipment", "id", "character_id", Array(Array("other", "equipment_id"), "quality", "repair", "is_equipped", "is_deleted"));


// //////
// select
// //////

$combatGear = $otherGear = "";
$view->total_value = $view->total_weight = 0;
$view->characterTreasure[other][0] = 
$view->characterTreasure[othercombat][0] = 
$view->characterTreasure[armour][0] = 
$view->characterTreasure[shield][0] = 
$view->characterTreasure[melee][0] = 
$view->characterTreasure[offhand][0] = 
$view->characterTreasure[ranged][0] = array();

$j = DataConnector::selectQuery("
	 SELECT ce.`id`              AS `id`,
	        ce.`quality`         AS `quality`,
	        ce.`repair`          AS `repair`,
	        ce.`is_equipped`     AS `is_equipped`,
	        ce.`is_deleted`      AS `is_deleted`,
	         e.`id`              AS `equipment_id`,
	         e.`name`            AS `name`,
	         e.`weight`          AS `weight`,
	         e.`cost`            AS `cost`,
	         e.`armour_category` AS `armour_category`,
	         e.`ac`              AS `armour_ac`,
	         e.`dc`              AS `armour_dc`,
	         e.`hardness`        AS `armour_hardness`,
	         e.`hp`              AS `armour_hp`,
	         e.`max_dex`         AS `armour_max_dex`,
	         e.`penalty`         AS `armour_penalty`,
	         e.`failure`         AS `armour_spell_failure`,
	         e.`melee_category`  AS `melee_category`,
	         e.`melee_size`      AS `melee_size`,
	         e.`melee_damage`    AS `melee_damage`,
	         e.`melee_damage2`   AS `melee_damage2`,
	         e.`melee_critical`  AS `melee_critical`,
	         e.`melee_type`      AS `melee_type`,
	         e.`melee_special`   AS `melee_special`,
	         e.`melee_group`     AS `melee_group`,
	         e.`ranged_category` AS `ranged_category`,
	         e.`ranged_size`     AS `ranged_size`,
	         e.`ranged_damage`   AS `ranged_damage`,
	         e.`ranged_damage2`  AS `ranged_damage2`,
	         e.`range`           AS `ranged_range`,
	         e.`ranged_critical` AS `ranged_critical`,
	         e.`ranged_type`     AS `ranged_type`,
	         e.`ranged_special`  AS `ranged_special`,
	         e.`ranged_group`    AS `ranged_group`
	   FROM t_characters_equipment ce
	   JOIN t_equipment e ON e.`id` = ce.`equipment_id`
	  WHERE ce.`character_id` = {$pkid}
	    AND ce.`is_deleted` != 'Yes'
");
while($j) {
	$tmpAddedCost = 0;
	$tmpBonus = 0;
	$multiplier = 1;
	$tmpCount = 1;
	$j[damage_mod] = 0;
	$j[to_hit_mod] = 0;

	if($j[quality][0] == " ") {
		$j[quality] = substr($j[quality], 1);
	}
	preg_match("/^(\D?)(\d+)/", $j[quality], $matches); // detect number in first or second position, indicating magical bonus
	if($matches[2]) {
		$tmpBonus = $matches[2];
		if($matches[1] == " ") { // if + sign is missing, replace it
			$j[quality][0] = "+";
		}
		if($matches[1] == "") { // if there is nother preceding the number, add a + sign
			$j[quality] = "+" . $j[quality];
		}
		$j[damage_mod] = $tmpBonus;
		$j[to_hit_mod] = $tmpBonus;
		$j[armour_hp] += $tmpBonus * 5;
	}

	preg_match("/\((\d+)\)/", $j[name], $matches); // detect number in parenthesis, indicating count
	if($matches[1]) {
		$tmpCount = $matches[1];
	}

	$j[quality_format] = $j[quality];

	if($j[repair] == "Worn") { // worn equipment
		$j[cost] *= 0.90;
	}
	if($j[repair] == "Broken") { // broken weapon
		$j[cost] *= 0.75;
		$j[damage_mod] -= 2;
		$j[to_hit_mod] -= 2;
		$j[melee_critical] = "x2";
		$j[ranged_critical] = "x2";
		$j[dc] = floor($j[dc] / 2);
		$j[armour_penalty] *= 2;
	}

	if(stripos($j[quality], "mwk") !== FALSE) { // masterwork item
		$tmpAddedCost += 20000;
		$j[damage_mod] = 1;
		if($j[melee_category]) { // p.149 A masterwork weapon is a finely crafted version of a normal weapon. Wielding it rovides a +1 enhancement bonus on attack** rolls. ** house rule change to damage
			$j[quality_format] = str_ireplace("mwk", "<span title='+1 enhancement bonus on damage rolls'>MWK</span>", $j[quality_format]);
		}
		else if($j[armour_category]) { // p.153 Masterwork versions of armor or shields function like the normal version, except that its armor check penalty is lessened by 1. ** house rule change, armour hp also increased by 5
			$j[armour_penalty] = max(0, $j[armour_penalty] - 1);
			$j[armour_hp] += 5;
			$j[quality_format] = str_ireplace("mwk", "<span title='Armour check penalty lessened by 1; +5 enhancement bonus on armour hp'>MWK</span>", $j[quality_format]);
		}
	}

	if(stripos($j[quality], "adamantine") !== FALSE) {
		if($j[melee_category]) { // p.154 Weapons fashioned from adamantine have a natural ability to bypass hardness when sundering weapons or attacking objects, ignoring hardness less than 20.
			$tmpAddedCost += 300000;
		}
		else if($j[armour_category]) { // p.154 Armor made from adamantine grants its wearer damage reduction of 1/— if it’s light armor, 2/— if it’s medium armor, and 3/— if it’s heavy armor. ** house rule change, double dr + hp
			if($j[armour_category] == "Light") {
				$tmpAddedCost += 500000;
				$j[armour_hardness] += 2;
				$j[armour_hp] += 10;
				if($j[armour_dc] < 5) { // Light armour that is actually a limited form of medium armour
					$j[armour_hardness] += 2;
					$j[armour_hp] += 10;
				}
			}
			if($j[armour_category] == "Medium") {
				$tmpAddedCost += 1000000;
				$j[armour_hardness] += 4;
				$j[armour_hp] += 20;
				if($j[armour_dc] < 5) { // Medium armour that is actually a limited form of heavy armour
					$j[armour_hardness] += 2;
					$j[armour_hp] += 10;
				}
			}
			if($j[armour_category] == "Heavy") {
				$tmpAddedCost += 1500000;
				$j[armour_hardness] += 6;
				$j[armour_hp] += 30;
			}
		}
		else { // miscellaneous
			$tmpAddedCost += 6000 * $tmpCount;
		}
	}

	if(stripos($j[quality], "darkwood") !== FALSE) {
		$tmpAddedCost += $j[weight] * 1000;
		$j[weight] *= 0.5;
	}
	if(stripos($j[quality], "dragonhide") !== FALSE) {
		$tmpAddedCost += ($j[cost] + 20000) * 2;
	}
	if(stripos($j[quality], "iron, cold") !== FALSE or stripos($j[quality], "cold iron") !== FALSE) {
		$tmpAddedCost *= 2;
	}
	if(stripos($j[quality], "mithral") !== FALSE) {
		if($j[armour_category]) { // p.154 Most mithral armors are one category lighter than normal for purposes of movement and other limitations... Spell failure chances for armors and shields made from mithral are decreased by 10%, maximum Dexterity bonuses are increased by 2, and armor check penalties are decreased by 3 (to a minimum of 0)... An item made from mithral weighs half as much as the same item made from other metals. ** houserule change, treat like adamantine
//			$j[armour] = $j[armour_hp] + 15;
			if($j[armour_category] == "Shield") {
				$tmpAddedCost += 100000;
			}
			if($j[armour_category] == "Light") {
				$tmpAddedCost += 100000;
				$j[armour_hardness] += 1;
				$j[armour_hp] += 5;
				if($j[armour_dc] < 5) { // Light armour that is actually a limited form of medium armour
					$j[armour_hardness] += 1;
					$j[armour_hp] += 5;
				}
			}
			if($j[armour_category] == "Medium") {
				$j[armour_category] = "Light";
				$tmpAddedCost += 400000;
				$j[armour_hardness] += 2;
				$j[armour_hp] += 10;
				if($j[armour_dc] < 5) { // Light armour that is actually a limited form of medium armour
					$j[armour_hardness] += 1;
					$j[armour_hp] += 5;
				}
			}
			if($j[armour_category] == "Heavy") {
				$j[armour_category] = "Medium";
				$tmpAddedCost += 900000;
				$j[armour_hardness] += 3;
				$j[armour_hp] += 15;
			}
			$j[armour_max_dex] += 2;
			$j[armour_penalty] = max(0, $j[armour_penalty] - 3);
			$j[armour_spell_failure] = max(0, $j[armour_spell_failure] - 10);
		}
		else { // not armour
			$tmpAddedCost += $j[weight] * 50000;
		}
		$j[weight] *= 0.5;
	}
	if(stripos($j[quality], "silver") !== FALSE) {
		$j[damage_mod] -= 1;
		if($j[melee_size] == "") {
			$tmpAddedCost += 200 * $tmpCount;
		}
		if(substr($j[melee_size], 0, 5) == "Light") {
			$tmpAddedCost += 2000;
		}
		if(substr($j[melee_size], 0, 5) == "One-H") {
			$tmpAddedCost += 9000;
		}
		if(substr($j[melee_size], 0, 5) == "Two-H") {
			$tmpAddedCost += 18000;
		}
	}
	if($j[armour_category]) { // armour & shields
		if(stripos($j[quality], "glamered") !== FALSE) {
			$tmpAddedCost += 270000;
		}
		if(stripos($j[quality], "greater slick") !== FALSE or stripos($j[quality], "slick, greater") !== FALSE) {
			$tmpAddedCost += 3375000;
		}
		else if(stripos($j[quality], "improved slick") !== FALSE or stripos($j[quality], "slick, improved") !== FALSE) {
			$tmpAddedCost += 1500000;
		}
		else if(stripos($j[quality], "slick") !== FALSE) {
			$tmpAddedCost += 375000;
		}
		if(stripos($j[quality], "greater shadow") !== FALSE or stripos($j[quality], "shadow, greater") !== FALSE) {
			$tmpAddedCost += 3375000;
		}
		else if(stripos($j[quality], "improved shadow") !== FALSE or stripos($j[quality], "shadow, improved") !== FALSE) {
			$tmpAddedCost += 1500000;
		}
		else if(stripos($j[quality], "shadow") !== FALSE) {
			$tmpAddedCost += 375000;
		}
		if(stripos($j[quality], "energy resistance, greater") !== FALSE or stripos($j[quality], "greater energy resistance") !== FALSE) {
			$tmpAddedCost += 6600000;
		}
		else if(stripos($j[quality], "energy resistance, improved") !== FALSE or stripos($j[quality], "improved energy resistance") !== FALSE) {
			$tmpAddedCost += 4200000;
		}
		else if(stripos($j[quality], "energy resistance") !== FALSE) {
			$tmpAddedCost += 1800000;
		}
		if(stripos($j[quality], "etherealness") !== FALSE) {
			$tmpAddedCost += 4900000;
		}
		if(stripos($j[quality], "undead controlling") !== FALSE) {
			$tmpAddedCost += 4900000;
		}
		if(stripos($j[quality], "light fortification") !== FALSE or stripos($j[quality], "fortification, light") !== FALSE) {
			$tmpBonus += 1;
		}
		if(stripos($j[quality], "moderate fortification") !== FALSE or stripos($j[quality], "fortification, moderate") !== FALSE) {
			$tmpBonus += 3;
		}
		if(stripos($j[quality], "heavy fortification") !== FALSE or stripos($j[quality], "fortification, heavy") !== FALSE) {
			$tmpBonus += 5;
		}
		if(stripos($j[quality], "spell resistance (13)") !== FALSE) {
			$tmpBonus += 2;
		}
		if(stripos($j[quality], "spell resistance (15)") !== FALSE) {
			$tmpBonus += 3;
		}
		if(stripos($j[quality], "spell resistance (17)") !== FALSE) {
			$tmpBonus += 4;
		}
		if(stripos($j[quality], "spell resistance (19)") !== FALSE) {
			$tmpBonus += 5;
		}
		if(stripos($j[quality], "ghost touch") !== FALSE) {
			$tmpBonus += 3;
		}
		if(stripos($j[quality], "invulnerability") !== FALSE) {
			$tmpBonus += 3;
		}
		if(stripos($j[quality], "wild") !== FALSE) {
			$tmpBonus += 3;
		}
		if(stripos($j[quality], "arrow catching") !== FALSE) {
			$tmpBonus += 1;
		}
		if(stripos($j[quality], "bashing") !== FALSE) {
			$tmpBonus += 1;
		}
		if(stripos($j[quality], "blinding") !== FALSE) {
			$tmpBonus += 1;
		}
		if(stripos($j[quality], "arrow deflection") !== FALSE) {
			$tmpBonus += 2;
		}
		if(stripos($j[quality], "animated") !== FALSE) {
			$tmpBonus += 2;
		}
		if(stripos($j[quality], "reflecting") !== FALSE) {
			$tmpBonus += 5;
		}
	}
	if($j[melee_category] or $j[ranged_category]) { // melee and ranged weapons
		if(stripos($j[quality], "bane") !== FALSE) {
			$tmpBonus += 1;
		}
		if(stripos($j[quality], "defending") !== FALSE) {
			$tmpBonus += 1;
		}
		if(stripos($j[quality], "frost") !== FALSE) {
			$tmpBonus += 1;
		}
		if(stripos($j[quality], "ghost touch") !== FALSE) {
			$tmpBonus += 1;
		}
		if(stripos($j[quality], "keen") !== FALSE) {
			$tmpBonus += 1;
		}
		if(stripos($j[quality], "ki focus") !== FALSE) {
			$tmpBonus += 1;
		}
		if(stripos($j[quality], "merciful") !== FALSE) {
			$tmpBonus += 1;
		}
		if(stripos($j[quality], "mighty cleaving") !== FALSE) {
			$tmpBonus += 1;
		}
		if(stripos($j[quality], "spell storing") !== FALSE) {
			$tmpBonus += 1;
		}
		if(stripos($j[quality], "throwing") !== FALSE) {
			$tmpBonus += 1;
		}
		if(stripos($j[quality], "thundering") !== FALSE) {
			$tmpBonus += 1;
		}
		if(stripos($j[quality], "vicious") !== FALSE) {
			$tmpBonus += 1;
		}
		if(stripos($j[quality], "distance") !== FALSE) {
			$tmpBonus += 1;
		}
		if(stripos($j[quality], "returning") !== FALSE) {
			$tmpBonus += 1;
		}
		if(stripos($j[quality], "seeking") !== FALSE) {
			$tmpBonus += 1;
		}
		if(stripos($j[quality], "anarchic") !== FALSE) {
			$tmpBonus += 2;
		}
		if(stripos($j[quality], "axiomatic") !== FALSE) {
			$tmpBonus += 2;
		}
		if(stripos($j[quality], "disruption") !== FALSE) {
			$tmpBonus += 2;
		}
		if(stripos($j[quality], "flaming burst") !== FALSE) {
			$tmpBonus += 2;
		}
		else if(stripos($j[quality], "flaming") !== FALSE) {
			$tmpBonus += 1;
		}
		if(stripos($j[quality], "icy burst") !== FALSE) {
			$tmpBonus += 2;
		}
		if(stripos($j[quality], "shocking burst") !== FALSE) {
			$tmpBonus += 2;
		}
		else if(stripos($j[quality], "shock") !== FALSE) {
			$tmpBonus += 1;
		}
		if(stripos($j[quality], "unholy") !== FALSE) {
			$tmpBonus += 2;
		}
		else if(stripos($j[quality], "holy") !== FALSE) {
			$tmpBonus += 2;
		}
		if(stripos($j[quality], "wounding") !== FALSE) {
			$tmpBonus += 2;
		}
		if(stripos($j[quality], "speed") !== FALSE) {
			$tmpBonus += 3;
		}
		if(stripos($j[quality], "brilliant energy") !== FALSE) {
			$tmpBonus += 4;
		}
		if(stripos($j[quality], "dancing") !== FALSE) {
			$tmpBonus += 4;
		}
		if(stripos($j[quality], "vorpal") !== FALSE) {
			$tmpBonus += 5;
		}
	}

	// weapons cost double, ammo costs 4%
	if(!$j[armour_category]) {
		if($j[melee_category] or $j[ranged_category]) {
			$multiplier = 2;
		}
		else {
			$multiplier *= 0.04;
		}
	}

	// convert bonuses to cash value
	switch($tmpBonus) {
		case 1:   $tmpAddedCost += $multiplier * 100000; break;
		case 2:   $tmpAddedCost += $multiplier * 400000; break;
		case 3:   $tmpAddedCost += $multiplier * 900000; break;
		case 4:   $tmpAddedCost += $multiplier * 1600000; break;
		case 5:   $tmpAddedCost += $multiplier * 2500000; break;
		case 6:   $tmpAddedCost += $multiplier * 3600000; break;
		case 7:   $tmpAddedCost += $multiplier * 4900000; break;
		case 8:   $tmpAddedCost += $multiplier * 6400000; break;
		case 9:   $tmpAddedCost += $multiplier * 8100000; break;
		case 10:  $tmpAddedCost += $multiplier * 10000000; break;
	}
	$j[cost] += $tmpAddedCost; // added cost for enchantments
	$view->total_value += $j[cost];
	$view->total_weight += $j[weight];

	// assign to equipment pool
	if($j[is_equipped] == 'No') {
		$view->characterTreasure[other][] = $j;
	}
	else {
		$view->characterTreasure[combat][] = $j;
		$combatGear .= $j[quality_format] . " " . $j[name] . " ; ";
	}
	if($j[is_equipped] == "Armour") {
		if($j[armour_category] == "Shield") {
			$view->characterTreasure[shield][] = $j;
		}
		else {
			$view->characterTreasure[armour][] = $j;
		}
	}
	if($j[is_equipped] == "Melee") {
		$view->characterTreasure[melee][] = $j;
	}
	if($j[is_equipped] == "Yes") {
		$view->characterTreasure[othercombat][] = $j;
	}
	if($j[is_equipped] == "Off-Hand") {
		$view->characterTreasure[offhand][] = $j;
	}
	if($j[is_equipped] == "Ranged") {
		$view->characterTreasure[ranged][] = $j;
	}

	$view->characterTreasure[treasure][] = $j;
	$j = DataConnector::selectQuery();
}

$combatGear = substr($combatGear, 0, -2);

// select list from t_equipment
$j = DataConnector::selectQuery("
	 SELECT  e.`id`              AS `id`,
	         e.`name`            AS `name`,
	         e.`armour_category` AS `armour_category`,
	         e.`melee_category`  AS `melee_category`,
	         e.`ranged_category` AS `ranged_category`
	   FROM t_equipment e
	  WHERE e.`id` < 281
	  ORDER BY e.`name`
");
while($j) {
		$view->characterTreasure[other]['list'][] = $j;
		if($j[armour_category] == "Shield") {
			$view->characterTreasure[shield]['list'][] = $j;
		}
		else if($j[armour_category] != "") {
			$view->characterTreasure[armour]['list'][] = $j;
		}
		if($j[melee_category] != "") {
			$view->characterTreasure[melee]['list'][] = $j;
		}
		if($j[ranged_category] != "") {
			$view->characterTreasure[ranged]['list'][] = $j;
		}
		$j = DataConnector::selectQuery();
}
$view->characterTreasure[offhand]['list'] = $view->characterTreasure[melee]['list'];
$view->characterTreasure[othercombat]['list'] = $view->characterTreasure[other]['list'];


// ////////////////////////////
// communicate with parent page
// ////////////////////////////

echo "<script>\n";
$js_array = json_encode($view->characterTreasure[melee][1]);
echo "charSheet.melee = {$js_array}\n";
$js_array = json_encode($view->characterTreasure[offhand][1]);
echo "charSheet.offhand = {$js_array}\n";
$js_array = json_encode($view->characterTreasure[ranged][1]);
echo "charSheet.ranged = {$js_array}\n";
$js_array = json_encode($view->characterTreasure[armour][1]);
echo "charSheet.armour = {$js_array}\n";
$js_array = json_encode($view->characterTreasure[shield][1]);
echo "charSheet.shield = {$js_array}\n";
echo "buildSection('Specialabilities')\n";
echo "</script>\n";

?>
