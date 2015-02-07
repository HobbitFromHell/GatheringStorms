<?php

// //////////
// validation
// //////////

if(!($pkid > 0 and $pkid < 65000)) {
	echo "FAIL: Character ID {$pkid} is invalid";
	return false;
}

$view = new DataCollector();

// read post data required to customize equipment
$view->characterTreasure[param][size] = sanitize($_POST[size]);


// //////
// insert
// //////

post_data("t_characters_equipment", "id", "character_id", Array(Array("weapon", "equipment_id"), "quality", "count", "is_equipped", "is_deleted"));
post_data("t_characters_equipment", "id", "character_id", Array(Array("armour", "equipment_id"), "quality", "count", "is_equipped", "is_deleted"));
post_data("t_characters_equipment", "id", "character_id", Array(Array("other", "equipment_id"), "quality", "count", "is_equipped", "is_deleted"));


// //////
// select
// //////

$view->combatGear = $view->otherGear = "";
$view->total_value = $view->total_weight = 0;
$view->characterTreasure[other][0] = 
$view->characterTreasure[weapon][0] = 
$view->characterTreasure[armour][0] = array();
$j = DataConnector::selectQuery("
	 SELECT ce.`id`              AS `id`,
	        ce.`quality`         AS `quality`,
	        ce.`is_equipped`     AS `is_equipped`,
	        ce.`is_deleted`      AS `is_deleted`,
	         e.`id`              AS `equipment_id`,
	         e.`name`            AS `name`,
	         e.`weight`          AS `weight`,
	         e.`cost`            AS `cost`,
	        ce.`count`           AS `count`,
	         e.`armour_category` AS `armour_category`,
	           '0'               AS `armour_ac`,
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

// modify equipment based on repair, qualities, size; then assign to an equipment list
while($j) {
	$is_masterwork = 0;
	$tmpAddedCost = 0;
	$tmpBonus = 0;
	$multiplier = 1;
	$j[damage_mod] = 0;
	$j[to_hit_mod] = 0;

	// patch for missing + sign: remove leading space
	if($j[quality][0] == " ") {
		$j[quality] = substr($j[quality], 1);
	}

	// look for a number in first or second position, indicating magical bonus
	preg_match("/^(\D?)(\d+)/", $j[quality], $matches);
	if($matches[2]) {
		$tmpBonus = $matches[2];
		// if + sign is missing, replace it
		if($matches[1] == " ") {
			$j[quality][0] = "+";
		}
		// if there is nothing preceding the number, add a + sign
		if($matches[1] == "") {
			$j[quality] = "+" . $j[quality];
		}
		// modify equipment based on magical bonus
		$j[damage_mod] = $tmpBonus;
		$j[to_hit_mod] = $tmpBonus;
		$j[armour_hp] += $tmpBonus * 5;
	}
	$j[quality_format] = $j[quality];

	// modify for size of armour and weapons
	if($j[armour_category] or $j[melee_category] or $j[ranged_category]) {
		if($view->characterTreasure[param][size] == "Small") {
			$j[cost] *= 1;
			$j[weight] /= 2;
			$j[armour_hp] = floor($j[armour_hp] / 2);
		}
		if($view->characterTreasure[param][size] == "Large") {
			$j[cost] *= 2;
			$j[weight] *= 2;
			$j[armour_hp] *= 2;
		}
	}

	// modify for broken status
	if(stripos($j[quality], "broken") !== FALSE) {
		$j[cost] *= 0.75;
		$j[damage_mod] -= 2;
		$j[to_hit_mod] -= 2;
		$j[melee_critical] = "x2";
		$j[ranged_critical] = "x2";
		$j[dc] = floor($j[dc] / 2);
		$j[armour_penalty] *= 2;
	}

	// modify for adamantine item (p.154)
	if(stripos($j[quality], "adamantine") !== FALSE or 
	   stripos($j[quality], "dwarven")    !== FALSE) {
		$is_masterwork = 1;
		if($j[melee_category]) {
			// weapon
			$tmpAddedCost += 300000 - 20000;
		}
		else if($j[armour_category]) {
			// Armor made of adamantine has one-third more hit points than normal.
			$j[armour_hp] *= floor(4 / 3);
			if($j[armour_category] == "Light") {
				// Armor made from adamantine grants its wearer damage reduction of 1/— if it’s light armor.
				// house rule: the dr is doubled
				$tmpAddedCost += 500000 - 20000;
				$j[armour_hardness] += 2;
				if($j[armour_dc] < 5) {
					// Light armour that is actually a limited form of medium armour
					$j[armour_hardness] += 2;
				}
			}
			if($j[armour_category] == "Medium") {
				// Armor made from adamantine grants its wearer damage reduction of 2/— if it’s medium armor.
				// house rule: the dr is doubled
				$tmpAddedCost += 1000000 - 20000;
				$j[armour_hardness] += 4;
				if($j[armour_dc] < 5) {
					// Medium armour that is actually a limited form of heavy armour
					$j[armour_hardness] += 2;
				}
			}
			if($j[armour_category] == "Heavy") {
				// Armor made from adamantine grants its wearer damage reduction of 3/— if it’s heavy armor.
				// house rule: the dr is doubled
				$tmpAddedCost += 1500000 - 20000;
				$j[armour_hardness] += 6;
			}
		}
		else {
			// ammunition
			$tmpAddedCost += (6000 - 600);
		}
	}

	// modify for darkwood item (p.154)
	if(stripos($j[quality], "darkwood") !== FALSE) {
		$is_masterwork = 1;
		// The armor check penalty of a darkwood shield is lessened by 2 compared to an ordinary shield of its type.
		if($j[armour_category] = "Shield") {
			$j[armour_penalty] = max(0, $j[armour_penalty] - 1);
		}
		// The price is increased by 10 gp per pound of the original weight.
		$tmpAddedCost += $j[weight] * 1000;
		// Any wooden item made from darkwood weighs only half as much as normal.
		$j[weight] *= 0.5;
	}

	// modify for dragonhide item (p.154)
	if(stripos($j[quality], "dragonhide") !== FALSE) {
		$is_masterwork = 1;
		// Dragonhide armor costs twice as much as masterwork armor of that type.
		$tmpAddedCost += $j[cost] + 20000;
	}

	// modify for cold iron item (p.154)
	if(stripos($j[quality], "iron, cold") !== FALSE or
	   stripos($j[quality], "cold iron") !== FALSE) {
		// Weapons made of cold iron cost twice as much to make as their normal counterparts.
		$tmpAddedCost += $j[cost];
	}

	// modify for mithral item (p.154/155)
	if(stripos($j[quality], "mithral") !== FALSE or 
	   stripos($j[quality], "elven") !== FALSE) {
		$is_masterwork = 1;
		if($j[armour_category]) {
			// **house rule change: treat like adamantine, granting dr 1/--, 2/-- or 3/--
			// Spell failure chances for armors and shields made from mithral are decreased by 10%, maximum Dexterity bonuses are increased by 2, and armor check penalties are decreased by 3 (to a minimum of 0).
			$j[armour_max_dex] += 2;
			$j[armour_penalty] = max(0, $j[armour_penalty] - 2);
			$j[armour_spell_failure] = max(0, $j[armour_spell_failure] - 10);
			if($j[armour_category] == "Shield") {
				$tmpAddedCost += 100000 - 20000;
			}
			if($j[armour_category] == "Light") {
				$tmpAddedCost += 100000 - 20000;
				$j[armour_hardness] += 1;
				if($j[armour_dc] < 5) {
					// Light armour that is actually a limited form of medium armour
					$j[armour_hardness] += 1;
				}
			}
			if($j[armour_category] == "Medium") {
				// Most mithral armors are one category lighter for purposes of movement and other limitations.
				$j[armour_category] = "Light";
				$tmpAddedCost += 400000 - 20000;
				$j[armour_hardness] += 2;
				if($j[armour_dc] < 5) {
					// Medium armour that is actually a limited form of heavy armour
					$j[armour_hardness] += 1;
				}
			}
			if($j[armour_category] == "Heavy") {
				// Most mithral armors are one category lighter for purposes of movement and other limitations.
				$j[armour_category] = "Medium";
				$tmpAddedCost += 900000 - 20000;
				$j[armour_hardness] += 3;
			}
		}
		else {
			$tmpAddedCost += $j[weight] * 50000;
		}
		// An item made from mithral weighs half as much.
		$j[weight] *= 0.5;
	}

	// modify for alchemical silver item (p.155)
	if(stripos($j[quality], "silver") !== FALSE) {
		// A silvered slashing or piercing weapon takes a –1 penalty on the damage roll.
		$j[damage_mod] -= 1;
		if($j[melee_size] == "") {
			$tmpAddedCost += 200;
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

	// modify for masterwork item (p.149 weapon, p.153 armour)
	if(stripos($j[quality], "mwk") !== FALSE or $is_masterwork) {
		if($j[melee_category]) {
			// "Wielding it provides a +1 enhancement bonus on attack** rolls."
			// **house rule change to damage roll from attack roll
			$j[damage_mod] = 1;
			$j[quality_format] = str_ireplace("mwk",
			  "<span title='+1 enhancement bonus on damage rolls'>MWK</span>",
			  $j[quality_format]);
			// **house rule change: all mwk items cost 200 gp extra
			$tmpAddedCost += 20000;
		}
		else if($j[armour_category]) {
			// "...its armor check penalty is lessened by 1."
			// ** house rule change: armour hp also increased by 5
			$j[armour_penalty] = max(0, $j[armour_penalty] - 1);
			$j[armour_hp] += 5;
			$j[quality_format] = str_ireplace("mwk",
			  "<span title='Armour check penalty lessened by 1; +5 enhancement bonus on armour hp'>MWK</span>",
			  $j[quality_format]);
			// **house rule change: all mwk items cost 200 gp extra
			$tmpAddedCost += 20000;
		}
		else {
			// ammunition costs 6 gp extra
			$tmpAddedCost += 600;
		}
	}

	// magic armour and shields
	if($j[armour_category]) {
		if(stripos($j[quality], "glamered") !== FALSE) {
			$tmpAddedCost += 270000;
		}
		if(stripos($j[quality], "greater slick") !== FALSE or
		   stripos($j[quality], "slick, greater") !== FALSE) {
			$tmpAddedCost += 3375000;
		}
		else if(stripos($j[quality], "improved slick") !== FALSE or
		   stripos($j[quality], "slick, improved") !== FALSE) {
			$tmpAddedCost += 1500000;
		}
		else if(stripos($j[quality], "slick") !== FALSE) {
			$tmpAddedCost += 375000;
		}
		if(stripos($j[quality], "greater shadow") !== FALSE or
		   stripos($j[quality], "shadow, greater") !== FALSE) {
			$tmpAddedCost += 3375000;
		}
		else if(stripos($j[quality], "improved shadow") !== FALSE or
		   stripos($j[quality], "shadow, improved") !== FALSE) {
			$tmpAddedCost += 1500000;
		}
		else if(stripos($j[quality], "shadow") !== FALSE) {
			$tmpAddedCost += 375000;
		}
		if(stripos($j[quality], "energy resistance, greater") !== FALSE or
		   stripos($j[quality], "greater energy resistance") !== FALSE) {
			$tmpAddedCost += 6600000;
		}
		else if(stripos($j[quality], "energy resistance, improved") !== FALSE or
		   stripos($j[quality], "improved energy resistance") !== FALSE) {
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
		if(stripos($j[quality], "light fortification") !== FALSE or
		   stripos($j[quality], "fortification, light") !== FALSE) {
			$tmpBonus += 1;
		}
		if(stripos($j[quality], "moderate fortification") !== FALSE or
		   stripos($j[quality], "fortification, moderate") !== FALSE) {
			$tmpBonus += 3;
		}
		if(stripos($j[quality], "heavy fortification") !== FALSE or
		   stripos($j[quality], "fortification, heavy") !== FALSE) {
			$tmpBonus += 5;
		}
		if(stripos($j[quality], "light deflection") !== FALSE or
		   stripos($j[quality], "deflection, light") !== FALSE) {
			$tmpBonus += 1;
			$j[armour_ac] += 1;
		}
		if(stripos($j[quality], "moderate deflection") !== FALSE or
		   stripos($j[quality], "deflection, moderate") !== FALSE) {
			$tmpBonus += 2;
			$j[armour_ac] += 2;
		}
		if(stripos($j[quality], "heavy deflection") !== FALSE or
		   stripos($j[quality], "deflection, heavy") !== FALSE) {
			$tmpBonus += 3;
			$j[armour_ac] += 3;
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

	// magic melee and ranged weapons
	if($j[melee_category] or $j[ranged_category]) {
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

	// convert magical plus to cash value
	if(!$j[armour_category]) {
		if($j[melee_category] or $j[ranged_category]) {
			// weapons cost for magic is double
			$multiplier = 2;
		}
		else {
			// ammo cost for magic is 4%
			$multiplier *= 0.04;
		}
	}
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

	// add cost for enchantments, multiply by number of items
	$j[cost] += $tmpAddedCost;
	$view->total_value += $j[cost] * $j['count'];

	// assign to equipment pool
	$view->characterTreasure[treasure][] = $j;
	// edit
	if($j[is_equipped] == "Armour") {
		$view->characterTreasure[armour][] = $j;
	}
	else if($j[is_equipped] == "Melee" or $j[is_equipped] == "Off-Hand" or $j[is_equipped] == "Ranged") {
		$view->characterTreasure[weapon][] = $j;
	}
	else {
		$view->characterTreasure[other][] = $j;
	}
	// change name of equipment named unique
	if(substr($j[name], 0, 6) == "Unique") {
		$j[name] = "";
	}
	// read-only
	if($j['count'] > 1) {
		// show number of items in name
		$j[name] .= " (" . $j['count'] . ")";
	}
	if($j[is_equipped] == "No") {
		$view->otherGear .= $j[quality_format] . " " . $j[name] . " ; ";
		$view->total_weight += $j[weight] * $j['count'];
	}
	else if($j[is_equipped] == "Mount") {
		$view->mountGear .= $j[quality_format] . " " . $j[name] . " ; ";
		// do not calculate weight for equipment on a mount
	}
	else if($j[is_equipped] == "Stored") {
		$view->storedGear .= $j[quality_format] . " " . $j[name] . " ; ";
		// do not calculate weight for stored equipment
	}
	else {
		$view->combatGear .= $j[quality_format] . " " . $j[name] . " ; ";
		$view->total_weight += $j[weight] * $j['count'];
		if($j[armour_category] == "Shield") {
			$view->characterTreasure[equipped][shield] = $j;
		}
		else if ($j[is_equipped] == "Armour") {
			$view->characterTreasure[equipped][armour] = $j;
		}
		else if ($j[is_equipped] == "Melee") {
			$view->characterTreasure[equipped][melee] = $j;
		}
		else if ($j[is_equipped] == "Off-Hand") {
			$view->characterTreasure[equipped][offhand] = $j;
		}
		else if ($j[is_equipped] == "Ranged") {
			$view->characterTreasure[equipped][ranged] = $j;
		}
	}
	$j = DataConnector::selectQuery();
}
// trim trailing commas
$view->combatGear = substr($view->combatGear, 0, -2);
$view->otherGear = substr($view->otherGear, 0, -2);
$view->mountGear = substr($view->mountGear, 0, -2);
$view->storedGear = substr($view->storedGear, 0, -2);

// built equipment list
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
		if($j[melee_category] != "" or $j[ranged_category] != "") {
			$view->characterTreasure[weapon]['list'][] = $j;
		}
		else if($j[armour_category] != "") {
			$view->characterTreasure[armour]['list'][] = $j;
		}
		else {
			$view->characterTreasure[other]['list'][] = $j;
		}
		$j = DataConnector::selectQuery();
}


// ////////////////////////////
// communicate with parent page
// ////////////////////////////

echo "<script>\n";
$js_array = json_encode($view->characterTreasure[equipped][melee]);
echo "charSheet.melee = {$js_array}\n";
$js_array = json_encode($view->characterTreasure[equipped][offhand]);
echo "charSheet.offhand = {$js_array}\n";
$js_array = json_encode($view->characterTreasure[equipped][ranged]);
echo "charSheet.ranged = {$js_array}\n";
$js_array = json_encode($view->characterTreasure[equipped][armour]);
echo "charSheet.armour = {$js_array}\n";
$js_array = json_encode($view->characterTreasure[equipped][shield]);
echo "charSheet.shield = {$js_array}\n";
echo "buildSection('Feats', 'total_level=' + charSheet.total_level + '&race=' + charSheet.race + '&total_bab=' + charSheet.total_bab + '&str=' + charSheet.str + '&dex=' + charSheet.dex + '&con=' + charSheet.con + '&int=' + charSheet.int + '&wis=' + charSheet.wis + '&cha=' + charSheet.cha)\n";
echo "</script>\n";

?>
