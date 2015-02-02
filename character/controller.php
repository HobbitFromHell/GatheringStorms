<?php

// create data collection to share information with the view
$view = new DataCollector;

if($pkid == 0) {

	// no character id: get list page data

	if(isset($_GET['page_start'])) {
		$getOffset = sanitize($_GET['page_start']);
	}
	else {
		$getOffset = 0;
	}
	if(isset($_GET['page_count'])) {
		$getLimit = sanitize($_GET['page_count']);
	}
	else {
		$getLimit = 60;
	}
	if(isset($_GET['name'])) {
		$getNameKeyword = sanitize($_GET['name']);
	}
	if(isset($_GET['loc'])) {
		$getLocKeyword = sanitize($_GET['loc']);
	}

	$varRecord = DataConnector::selectQuery("
		 SELECT pc.`id`              AS `id`,
		           IF(pc.`name` = '', '?', pc.`name`) AS `name`,
		        pc.`cr`              AS `cr`,
		        pc.`gender`          AS `gender`,
		         r.`short`           AS `race`,
		           IFNULL(l.`name`, pc.`location_id`) AS `loc_name`,
		        GROUP_CONCAT(CONCAT(ccc.`character_class_id`, '&nbsp;', ccc.`level`) SEPARATOR ',&nbsp;') AS `class`
		   FROM t_characters pc
		   JOIN t_races r ON pc.`race_id` = r.`id`
		   JOIN t_character_classes_characters ccc
		     ON pc.`id` = ccc.`character_id`
		    AND ccc.`is_deleted` != 'Yes'
		    AND ccc.`level` > 0
		   LEFT JOIN t_locations l
		     ON pc.`location_id` = l.`id`
		  WHERE pc.`name` LIKE '%{$getNameKeyword}%'
		    AND (l.`name` LIKE '%{$getLocKeyword}%' OR pc.`location_id` = '{$getLocKeyword}' OR pc.`location_id` IS NULL)
		  GROUP BY pc.`id`
		  ORDER BY pc.`cr` DESC
		  LIMIT {$getLimit} OFFSET {$getOffset}
	");
	$varCounter = 0;
	while ($varRecord) {
		if($varCounter++ < $getLimit / 2) {
			$view->characterListLeft[] = $varRecord;
		}
		else {
			$view->characterListRight[] = $varRecord;
		}
		$varRecord = DataConnector::selectQuery();
	}
	// check if more records are available
	if($varCounter == $getLimit) {
		$view->characterListMore = 1;
	}
}

?>
	<script>
		// variable collection for javascript
		var charSheet = {
			size: "Medium",
			total_classes: Array(),
			class_level: {},
			racial_traits: Array(),
			class_features: Array(),
			skill_list: {},
			skill_trait_bonus: Array(),
			skill_feat_bonus: Array(),
			skill_doubling_bonus: Array(),
			spell_list: {},
			spell_desc_list: {},
			spells: {},
			sa_domains: Array(),
			domain_powers: Array(),

			// calculate bonus of given ability score
			bonus: function (abilityScore, applyLoad)
			{
				if(!applyLoad) applyLoad = 0
				return Math.floor((abilityScore - 10 - charSheet.getValue("loadType") * applyLoad) / 2)
			},

			// calculate size bonus
			sizeBonus: function ()
			{
				if(charSheet.getValue("size") == "Small") {
					return -1
				}
				return 0
			},

			// convert double-average notation to XdX, also adjutsing for size
			dice: function (damage)
			{
				// Table 6-5: Tiny and Large Weapon Damage
				if(charSheet.getValue("size") == "Medium") {
					switch(damage) {
						case 3: return "1d2"
						case 4: return "1d3"
						case 5: return "1d4"
						case 7: return "1d6"
						case 9: return "1d8"
						case 11: return "1d10"
						case 13: return "1d12"
						case 15: return "1d8+3"
						case 17: return "1d8+4"
						case 10: return "2d4"
						case 14: return "2d6"
						case 18: return "2d8"
						case 22: return "2d10"
					}
				}
				if(charSheet.getValue("size") == "Small") {
					switch(damage) {
						case 3: return "1"
						case 4: return "1d2"
						case 5: return "1d3"
						case 7: return "1d4"
						case 9: return "1d6"
						case 11: return "1d8"
						case 13: return "1d10"
						case 10: return "1d6"
						case 14: return "1d10"
						case 18: return "2d6"
						case 22: return "2d8"
					}
				}
				return "about " + damage / 2
			},

			// convert cardinal numbers to ordinal form
			ordinal: function (cardinal)
			{
				switch(cardinal) {
					case "0": return "0"
					case "1": return "1st"
					case "2": return "2nd"
					case "3": return "3rd"
					default:  return (cardinal + "th")
				}
			},

			// build hp and description
			hp_build: function ()
			{
				console.log("hp_build ... ")
				charSheet.hp_desc  = "(" + charSheet.getValue("hp_desc_main")
				charSheet.total_hp = 1 * charSheet.getValue("hp_main")
				if(charSheet.getValue("hp_abilities")) {
					charSheet.hp_desc += " +" + (charSheet.bonus(charSheet.getValue("con")) * 1 * charSheet.getValue("total_level")) + " Con"
					charSheet.total_hp += 1 * charSheet.getValue("hp_abilities") * 1 * charSheet.getValue("total_level")
				}
				if(charSheet.getValue("hp_feats")) {
					charSheet.hp_desc += " +" + (1 * charSheet.getValue("hp_feats")) + " feats"
					charSheet.total_hp += 1 * charSheet.getValue("hp_feats")
				}
				charSheet.hp_desc += ")"
				$('#calcHP').text(charSheet.getValue("total_hp"))
				$('#calcHPDesc').text(charSheet.getValue("hp_desc"))
				console.log(" ... CHECK")
			},

			// build description of skills used and remaining
			sp_build: function ()
			{
				console.log("sp_build ... ")
				charSheet.total_sp = 1 * charSheet.getValue("sp_main")
				charSheet.sp_desc  = "(" + charSheet.getValue("sp_main")
				if(charSheet.getValue("sp_bonus") != 0) {
					charSheet.total_sp += 1 * charSheet.getValue("sp_bonus")
					charSheet.sp_desc  += " +" + charSheet.getValue("sp_bonus") + " favoured class"
				}
				if(charSheet.getValue("sp_abilities")) {
					charSheet.total_sp += 1 * charSheet.getValue("sp_abilities") * charSheet.getValue("total_level")
					charSheet.sp_desc  += " +" + (1 * charSheet.getValue("sp_abilities") * charSheet.getValue("total_level")) + " Int"
				}
				if(charSheet.getValue("sp_feats")) {
					charSheet.total_sp += 1 * charSheet.getValue("sp_feats")
					charSheet.sp_desc  += " +" + charSheet.getValue("sp_feats") + " feats"
				}
				if(charSheet.getValue("sp_traits")) {
					charSheet.total_sp += 1 * charSheet.getValue("sp_traits")
					charSheet.sp_desc  += " +" + charSheet.getValue("sp_traits") + " traits"
				}
				charSheet.sp_desc  = "(" + charSheet.getNumber("total_sp_used") + " / " + charSheet.getValue("total_sp") + ") " + charSheet.getValue("sp_desc")
				charSheet.sp_desc += ")"
				$('#calcSPDesc').text(charSheet.getValue("sp_desc"))
				console.log(" ... CHECK")
			},

			// build description of feats used and remaining
			fp_build: function ()
			{
				console.log("fp_build ... ")
				charSheet.total_fp = Math.ceil(charSheet.getValue("total_level") / 2)
				charSheet.fp_desc  = "(" + charSheet.getValue("total_fp")
				if(charSheet.getValue("fp_traits")) {
					charSheet.total_fp += 1 * charSheet.getValue("fp_traits")
					charSheet.fp_desc  += " +" + charSheet.getValue("fp_traits") + " traits"
				}
				if(charSheet.getValue("fp_features")) {
					charSheet.total_fp += 1 * charSheet.getValue("fp_features")
					charSheet.fp_desc  += " +" + charSheet.getValue("fp_features") + " class features"
				}
				charSheet.fp_desc  = "(" + charSheet.getNumber("total_fp_used") + " / " + charSheet.getNumber("total_fp") + ") " + charSheet.getValue("fp_desc")
				charSheet.fp_desc += ")"
				$('#calcFPDesc').text(charSheet.getValue("fp_desc"))
				console.log(" ... CHECK")
			},

			// build ac, flat-footed ac, and description
			ac_build: function ()
			{
				console.log("function ac_build()")

				charSheet.ac_desc = ""
				charSheet.total_ac = 10
				if(charSheet.getValue("size") != "Medium") {
					charSheet.ac_desc += ", +" + (-1 * charSheet.sizeBonus()) + " size"
					charSheet.total_ac -= charSheet.sizeBonus()
				}
				if(charSheet.getValue("ac_luck_feats")) {
					charSheet.ac_desc += ", +" + charSheet.getValue("ac_luck_feats") + " luck"
					charSheet.total_ac += 1 * charSheet.getValue("ac_luck_feats")
				}
				if(charSheet.getValue("ac_insight_feats")) {
					charSheet.ac_desc += ", +" + charSheet.getValue("ac_insight_feats") + " insight"
					charSheet.total_ac += 1 * charSheet.getValue("ac_insight_feats")
				}
				if(charSheet.getValue("deflect")) {
					charSheet.ac_desc += ", +" + charSheet.getValue("deflect") + " deflection"
					charSheet.total_ac += 1 * charSheet.getValue("deflect")
				}
				if(charSheet.getValue("loadType") < 1 && (!charSheet.getValue("armour"))) {
					if(charSheet.getValue("ac_bonus_monk")) {
						charSheet.ac_desc += ", +" + charSheet.getValue("ac_bonus_monk") + " monk"
						charSheet.total_ac += 1 * charSheet.getValue("ac_bonus_monk")
					}
					if(charSheet.getValue("ac_bonus_wis")) {
						charSheet.ac_desc += ", +" + charSheet.getValue("ac_bonus_wis") + " Wis"
						charSheet.total_ac += 1 * charSheet.getValue("ac_bonus_wis")
					}
				}
				charSheet.total_ffac = 1 * charSheet.getValue("total_ac")
				if(charSheet.getValue("dex") < 10) {
					charSheet.total_ffac += charSheet.bonus(charSheet.getValue("dex"))
				}				

				// below are not included in flat-footed ac
				if(charSheet.bonus(charSheet.getValue("dex"))) {
					temp = Math.min(charSheet.bonus(charSheet.getValue("dex")), 1 * charSheet.getValue("load_max_dex"), 1 * charSheet.getValue("ac_max_dex") + 1 * charSheet.getValue("spd_armour_training"))
					charSheet.ac_desc += ", +" + temp + " Dex"
					charSheet.total_ac += temp
				}

				$tmpDodgeTotal = charSheet.getNumber("sa_two_weapon_defense") + charSheet.getNumber("ac_dodge_feats") + charSheet.getNumber("sa_combat_expertise")
				if($tmpDodgeTotal) {
					charSheet.ac_desc += ", +" + $tmpDodgeTotal + " dodge"
					charSheet.total_ac += $tmpDodgeTotal
				}
				if(charSheet.getValue("ac_desc")) {
					charSheet.ac_desc = "(" + charSheet.getValue("ac_desc").substr(2) + ")"
				}
// TO DO: charSheet.ac_uncanny_dodge ?
				$('#calcAC').text(charSheet.getValue("total_ac"))
				$('#calcFlatFooted').text(charSheet.getValue("total_ffac"))
				$('#calcACDesc').text(charSheet.getValue("ac_desc"))

				console.log(" ... CHECK")
			},

			// build cmd
			cmd_build: function ()
			{
				console.log("function cmd_build()")
				temp = 10 + charSheet.bonus(charSheet.getValue("str"), 1) + charSheet.bonus(charSheet.getValue("dex")) + charSheet.sizeBonus() + 1 * charSheet.getValue("ac_bonus_monk") + 1 * charSheet.getValue("ac_bonus_wis")
				if(charSheet.getValue("sa_defensive_combat_training")) {
					temp += 1 * charSheet.getValue("total_level")
				}
				else {
					temp += 1 * charSheet.getValue("total_bab")
				}
				if(charSheet.getValue("sd_maneuver_training")) {
					temp += Math.ceil(charSheet.getValue("class_level.MNK") / 4)
				}
				$('#calcCMD').text(temp)
				console.log(" ... CHECK")
			},

			// build cmb
			cmb_build: function ()
			{
				console.log("function cmb_build()")
				temp = 10 + 1 * charSheet.getValue("total_bab") + charSheet.sizeBonus() - 1 * charSheet.getValue("sa_combat_expertise")
				if(charSheet.getValue("sa_agile_maneuvers")) {
					temp += 1 * charSheet.bonus(charSheet.getValue("dex"))
				}
				else {
					temp += 1 * charSheet.bonus(charSheet.getValue("str"), 1)
				}
				$('#calcCMB').text(temp)
				console.log(" ... CHECK")
			},

			// build armour description
			armour_build: function ()
			{
				console.log("function armour_build()")

				charSheet.armour_desc = ""
				if(charSheet.getValue("shield")) {
					charSheet.armour_desc += ", "
					charSheet.armour_desc += charSheet.getValue("shield.quality_format") + " " + charSheet.getValue("shield.name") + " "
					charSheet.armour_desc += "(DC +" + (charSheet.getNumber("shield.armour_dc") + charSheet.getNumber("shield.to_hit_mod") + charSheet.getNumber("ac_shield_focus")) + ", "
					charSheet.armour_desc += "hardness " + (charSheet.getNumber("shield.armour_hardness") + charSheet.getNumber("shield.to_hit_mod")) + ", ";
					charSheet.armour_desc += "hp " + (charSheet.getNumber("shield.armour_hp")) + ")"
				}
				else {
					if(charSheet.getValue("ac_improvised_shield") && !charSheet.getValue("offhand")) {
						charSheet.armour_desc += ", improvised shield (DC +" + charSheet.getValue("ac_improvised_shield") + ")"
					}
				}
				if(charSheet.getValue("armour")) {
					charSheet.armour_desc += ", "
					charSheet.armour_desc += charSheet.getValue("armour.quality_format") + " " + charSheet.getValue("armour.name") + " "
					charSheet.armour_desc += "(DC +" + (1 * charSheet.getNumber("armour.armour_dc") + 1 * charSheet.getNumber("armour.to_hit_mod")) + ", "
					charSheet.armour_desc += "hardness " + (charSheet.getNumber("armour.armour_hardness") + charSheet.getNumber("armour.to_hit_mod")) + ", ";
					charSheet.armour_desc += "hp " + (charSheet.getNumber("armour.armour_hp")) + ")"
				}
				if(charSheet.getValue("ac_natural_armour")) {
					charSheet.armour_desc += ", natural armour (DC 20, hardness " + charSheet.getValue("ac_natural_armour") + ")"
				}

				$('#calcArmour').html(charSheet.getValue("armour_desc").substr(2))
				charSheet.ac_build()

				console.log(" ... CHECK")
			},

			// build defensive abilities description
			sd_build: function ()
			{
				console.log("sd_build ... ")
				charSheet.sd_desc = charSheet.save_desc = ""
				if(charSheet.getValue("ac_uncanny_dodge")) {
					if(charSheet.getValue("ac_uncanny_dodge") == 1) {
						charSheet.sd_desc += "; uncanny dodge"
					}
					else {
						charSheet.sd_desc += "; improved uncanny dodge"
					}
				}
				if(charSheet.getValue("sd_trap_sense")) {
					charSheet.sd_desc += ", trap sense +" + charSheet.getValue("sd_trap_sense")
				}
				if(charSheet.getValue("sd_traits")) {
					charSheet.sd_desc += charSheet.getValue("sd_traits")
				}
				if(charSheet.getValue("save_traits")) {
					charSheet.save_desc += charSheet.getValue("save_traits")
				}
				if(charSheet.getValue("save_feats")) {
					charSheet.save_desc += charSheet.getValue("save_feats")
				}
				if(charSheet.getValue("ac_evasion")) {
					if(charSheet.getValue("ac_evasion") == 1) {
						charSheet.sd_desc += ", evasion"
					}
					else {
						charSheet.sd_desc += ", improved evasion"
					}
				}
				charSheet.sd_desc += charSheet.getValue("sd_features") + charSheet.getValue("sd_feats")
				if(charSheet.getValue("sd_desc")) {
					$('#calcSD').html("<b>Defensive Abilities</b> " + charSheet.getValue("sd_desc").substr(2))
				}

				if(charSheet.getValue("save_desc")) {
					$('#calcSaveDesc').text("; " + charSheet.getValue("save_desc").substr(2))
				}
				console.log(" ... CHECK")
			},

			// build damage reduction description
			dr_build: function ()
			{
				console.log("dr_build ... ")
				charSheet.dr_desc = ""
				if(charSheet.getValue("ac_damage_reduction")) {
					charSheet.dr_desc = ", " + charSheet.getValue("ac_damage_reduction") + "/--"
				}
				if(charSheet.getValue("ac_damage_reduction_chaotic")) {
					charSheet.dr_desc = ", " + charSheet.getValue("ac_damage_reduction_chaotic") + "/chaotic"
				}
				if(charSheet.getValue("ac_damage_reduction_evil")) {
					charSheet.dr_desc = ", " + charSheet.getValue("ac_damage_reduction_evil") + "/evil"
				}
				if(charSheet.getValue("ac_damage_reduction_cold_iron")) {
					charSheet.dr_desc = ", " + charSheet.getValue("ac_damage_reduction_cold_iron") + "/cold iron"
				}
				if(charSheet.getValue("ac_damage_reduction_lethal")) {
					charSheet.dr_desc = ", " + charSheet.getValue("ac_damage_reduction_lethal") + "/lethal"
				}
				if(charSheet.getValue("dr_desc")) {
					$('#calcDR').html("<b>DR</b> " + charSheet.getValue("dr_desc").substr(2))
				}
				console.log(" ... CHECK")
			},

			// build immune description
			immune_build: function ()
			{
				console.log("immune_build ... ")
				charSheet.immune_desc = ""
				if(charSheet.getValue("resist_traits")) {
					charSheet.immune_desc += " <b>Resistance</b> " + charSheet.getValue("resist_traits").substr(2)
				}
				if(charSheet.getValue("immune_traits")) {
					charSheet.immune_desc += " <b>Immune</b> " + charSheet.getValue("immune_traits").substr(2)
				}
				if(charSheet.getValue("immune_desc")) {
					$('#calcImmune').html(charSheet.getValue("immune_desc"))
				}
				console.log(" ... CHECK")
			},

			// build speed
			speed_build: function ()
			{
				console.log("function speed_build()")

				charSheet.ac_max_dex = charSheet.load_max_dex = 45
				tmpSpeed = 1 * charSheet.getValue("baseSpeed") + 1 * charSheet.getValue("base_speed_traits")
				temp = 0 // track whether to use base speed or restricted

				// set armour encumberance
				if(charSheet.getValue("armour")) {
					charSheet.ac_max_dex = charSheet.getValue("armour.armour_max_dex")
					if(charSheet.getValue("armour.armour_category") == "Medium" && charSheet.getValue("spd_armour_training") == 0) {
						temp = 1
					}
					if(charSheet.getValue("armour.armour_category") == "Heavy" && charSheet.getValue("spd_armour_training") < 2) {
						temp = 1
					}
				}

				// set load encumberance
				if(charSheet.getValue("loadType") > 0) {
					temp = 1
				}

				// add conditional speed bonuses
				if(charSheet.getValue("loadType") < 4 && (!charSheet.getValue("armour") || charSheet.getValue("armour.armour_category") != "heavy")) {
					tmpSpeed += 1 * charSheet.getValue("base_spd_bonus_med_l_med_a")
				}
				if(charSheet.getValue("loadType") < 1 && (!charSheet.getValue("armour") || charSheet.getValue("armour.armour_category") == "light")) {
					tmpSpeed += 1 * charSheet.getValue("spd_bonus_lgt_l_lgt_a")
				}
				if(charSheet.getValue("loadType") < 1 && (!charSheet.getValue("armour"))) {
					tmpSpeed += 1 * charSheet.getValue("spd_bonus_lgt_l_no_a")
				}

				// set max dex
				switch(charSheet.getValue("loadType")) {
					case 0: break;
					case 1: charSheet.load_max_dex = 5; break;
					case 2: charSheet.load_max_dex = 4; break;
					case 3: charSheet.load_max_dex = 3; break;
					case 4: charSheet.load_max_dex = 2; break;
					case 5: charSheet.load_max_dex = 1; break;
					case 6: charSheet.load_max_dex = 1; break;
					case 7: charSheet.load_max_dex = 0; break;
				}

				if(temp) {
					// reduced speed
					charSheet.spd = charSheet.reducedSpeed(tmpSpeed)
				}
				else {
					// base speed
					charSheet.spd = tmpSpeed
				}
				$('#calcSpd').text(charSheet.getValue("spd") + " ft." + charSheet.getValue("movement"))

				console.log(" ... CHECK")
			},

			// compute reduced speed from base speed
			reducedSpeed: function (speed)
			{
				console.log("reducedSpeed ... ")
				if(charSheet.getValue("noSpeedRestriction")) {
					return speed
				}
				switch(speed) {
					case 120: case 115: return 80;
					case 110: return 75;
					case 100: case 105: return 70;
					case 95: return 65;
					case 90: case 85: return 60;
					case 80: return 55;
					case 70: case 75: return 50;
					case 65: return 45;
					case 60: case 55: return 40;
					case 50: return 35;
					case 40: case 45: return 30;
					case 35: return 25;
					case 25: case 30: return 20;
					case 20: return 15;
					case 15: return 10;
					default: return Math.ceil(2 * speed / 15) * 5;
				}
				console.log(" ... CHECK")
			},

			// build melee description
			melee_build: function ()
			{
				console.log("function melee_build()")

				charSheet.melee_desc = ""
				switch(charSheet.getNumber("class_level.MNK")) {
					case "1": case "2": case "3": unarmedDmg = 7; break;
					case "4": case "5": case "6": case "7": unarmedDmg = 9; break;
					case "8": case "9": case "10": case "11": unarmedDmg = 11; break;
					case "12": case "13": case "14": case "15": unarmedDmg = 14; break;
					case "16": case "17": case "18": case "19": unarmedDmg = 18; break;
					case "20": unarmedDmg = 22; break;
					default: unarmedDmg = 5;
				}
				if(charSheet.getNumber("sa_flurry") > 0) { // flurry of blows
					charSheet.melee_desc += "flurry of blows "
					// attack
					temp = charSheet.attack_helper("Unarmed Strike", "monk,close", -2, 0)
					for(i = 1 * charSheet.getNumber("total_bab") + Math.ceil(charSheet.getNumber("class_level.MNK") / 4), j = "", k = 0; i > 0; i -= 5, k++) {
						j += "/" + (1 * i + temp)
						if(k < 1 * charSheet.getNumber("sa_flurry")) {
							j += "/" + (1 * i + temp)
						}
					}
					charSheet.melee_desc += j.substr(1)
					// damage
					temp = charSheet.damage_helper("Unarmed Strike", "monk,close", 0)
					charSheet.melee_desc += " (" + charSheet.dice(unarmedDmg) + " +" + temp + ")"
				}
				if(charSheet.getValue("melee")) { // melee weapon
					if(charSheet.getValue("melee_desc")) {
						charSheet.melee_desc += " or "
					}
					charSheet.melee_desc += charSheet.getValue("melee.quality_format") + " " + charSheet.getValue("melee.name") + " "
					// attack
					temp = charSheet.attack_helper(charSheet.getValue("melee.name"), charSheet.getValue("melee.melee_group"), charSheet.getNumber("melee.to_hit_mod"), 0)
					for(i = 1 * charSheet.getNumber("total_bab"), j = ""; i > 0; i -= 5) {
						j += "/" + (1 * i + 1 * temp)
					}
					charSheet.melee_desc += j.substr(1)
					// damage
					temp = charSheet.getNumber("melee.damage_mod")
					if(charSheet.getValue("melee.melee_size") == "Two-Handed Melee"
					   || !(charSheet.getValue("shield") || charSheet.getValue("offhand"))) {
					  // +1/2 Str bonus (min +1) for using both hands
						temp += Math.max(1, Math.floor(charSheet.bonus(charSheet.getNumber("str"), 1) / 2))
					}
					temp = charSheet.damage_helper(charSheet.getValue("melee.name"), charSheet.getValue("melee.melee_group"), temp)
					charSheet.melee_desc += " (" + charSheet.dice(charSheet.getNumber("melee.melee_damage")) + " +" + temp + "/" + charSheet.crit_helper(charSheet.getValue("melee.name"), charSheet.getValue("melee.melee_critical")) + ")"
				}
				if(charSheet.getValue("offhand")) { // off-hand melee weapon
					charSheet.melee_desc += ", "
					charSheet.melee_desc += charSheet.getValue("offhand.quality_format") + " " + charSheet.getValue("offhand.name") + " "
					// attack
					temp = charSheet.attack_helper(charSheet.getValue("offhand.name"), charSheet.getValue("offhand.melee_group"), charSheet.getNumber("offhand.to_hit_mod"), 1)
					for(i = 0, j = ""; i < 1 * Math.max(1, charSheet.getNumber("sa_two_weapon")); i++) {
						j += "/" + (temp + 1 * charSheet.getNumber("total_bab") - i * 5)
					}
					charSheet.melee_desc += j.substr(1)
					// damage (half str bonus with off hand)
					tmpDamageMod = charSheet.getNumber("offhand.to_hit_mod") - Math.max(1, Math.floor(charSheet.bonus(charSheet.getNumber("str"), 1) / 2))
					if(charSheet.getValue("sa_double_slice")) { // full str bonus when using double slice feat
						tmpDamageMod = charSheet.getNumber("offhand.to_hit_mod")
					}
					temp = charSheet.damage_helper(charSheet.getValue("offhand.name"), charSheet.getValue("offhand.melee_group"), tmpDamageMod)
					charSheet.melee_desc += " (" + charSheet.dice(charSheet.getNumber("offhand.melee_damage")) + " +" + temp + "/" + charSheet.crit_helper(charSheet.getValue("offhand.name"), charSheet.getValue("offhand.melee_critical")) + ")"
				}
				if(!charSheet.getValue("melee") || charSheet.getValue("sa_unarmed")) { // unarmed strike
					if(charSheet.getValue("melee_desc")) {
						charSheet.melee_desc += " or "
					}
					charSheet.melee_desc += "unarmed strike "
					// attack
					temp = charSheet.attack_helper("Unarmed Strike", "monk,close", 0, 0)
					for(i = 1 * charSheet.getNumber("total_bab"), j = ""; i > 0; i -= 5) {
						j += "/" + (1 * i + temp)
					}
					charSheet.melee_desc += j.substr(1)
					// damage
					temp = charSheet.damage_helper("Unarmed Strike", "monk,close", 0)
					charSheet.melee_desc += " (" + charSheet.dice(unarmedDmg) + " +" + temp + ")"
				}

				$('#calcMelee').html(charSheet.getValue("melee_desc"))

				console.log(" ... CHECK")
			},

			// build ranged description
			ranged_build: function ()
			{
				console.log("ranged_build ... ")
				charSheet.ranged_desc = ""
				if(charSheet.getValue("ranged")) {
					charSheet.ranged_desc += charSheet.getValue("ranged.quality_format") + " " + charSheet.getValue("ranged.name") + " "
					// attack
					temp = charSheet.attack_helper(charSheet.getValue("ranged.name"), charSheet.getValue("ranged.ranged_group"), charSheet.getValue("ranged.to_hit_mod"), 0, 1)
					for(i = 1 * charSheet.getNumber("total_bab"), j = ""; i > 0; i -= 5) {
						if(charSheet.getNumber("sa_rapid_shot") && i == 1 * charSheet.getNumber("total_bab")) {
							j += "/" + (1 * i + temp)
						}
						j += "/" + (1 * i + temp)
						if(charSheet.getValue("ranged.name").indexOf("rossbow") > -1) { // crossbow
							if(charSheet.getValue("ranged.name").indexOf("eavy") > -1 || charSheet.getValue("ranged.name") != charSheet.getValue("sa_rapid_reload")) {
								i = 0
							}
						}
					}
					charSheet.ranged_desc += j.substr(1)
					// damage
					temp = charSheet.damage_helper(charSheet.getValue("ranged.name"), charSheet.getValue("ranged.ranged_group"), charSheet.getValue("ranged.damage_mod"), 1)
					charSheet.ranged_desc += " (" + charSheet.dice(charSheet.getNumber("ranged.ranged_damage"))
					if(temp != 0) {
						charSheet.ranged_desc += " +" + temp
					}
					charSheet.ranged_desc += "/" + charSheet.crit_helper(charSheet.getValue("ranged.name"), charSheet.getValue("ranged.ranged_critical")) + ", " + charSheet.getValue("ranged.ranged_range") + " ft.)"
				}
				if(charSheet.getValue("ranged_desc")) {
					$('#calcRanged').html("<br><b>Ranged</b> " + charSheet.getValue("ranged_desc"))
				}
				console.log(" ... CHECK")
			},

			// weapon group helper matches weapon training groups to the group of the weapon used
			weapon_group_helper: function (group)
			{
				console.log("weapon_group_helper ... ")
				temp = 0
				if(!charSheet.isEmpty(charSheet.getValue("sa_weapon_group"))) {
					for(var weapon in charSheet.getValue("sa_weapon_group")) {
						if(group.indexOf(weapon) != -1) {
							temp = Math.max(temp, charSheet.getValue("sa_weapon_group."+weapon))
						}
					}
				}
				console.log(" ... CHECK")
				return temp
			},

			// attack helper figures combat attack bonuses
			attack_helper: function (weapon, group, bonus, is_offhand, is_ranged)
			{
				console.log("function attack_helper(weapon = " + weapon + ", group = " + group + ", bonus = " + bonus + ", is_offhand? " + is_offhand + ", is_ranged? " + is_ranged + ")")

				tmpAttackBonus = 10 + 1 * charSheet.sizeBonus() + 1 * bonus
				// feats & features
				tmpAttackBonus += 1 * charSheet.weapon_group_helper(group)
				if(charSheet.getValue("sa_focus")) { // weapon focus
					for(i = 0; i < charSheet.getValue("sa_focus").length; i++) {
						if(weapon.indexOf(charSheet.getValue("sa_focus."+i)) > -1) {
							tmpAttackBonus++
						}
					}
				}
				// ranged only modifiers
				if(is_ranged) {
					tmpAttackBonus += 1 * charSheet.bonus(charSheet.getValue("dex")) + 1 * charSheet.getValue("sa_point_blank")
					if(charSheet.getValue("sa_deadly_aim") > 0) { // deadly aim
						tmpAttackBonus -= 1 * charSheet.getValue("sa_deadly_aim")
					}
					if(charSheet.getValue("sa_rapid_shot")) {
						tmpAttackBonus  -= 2
					}
				}
				// melee only modifiers
				if(!is_ranged) {
					tmpAttackBonus -= charSheet.getValue("sa_combat_expertise")
					if(charSheet.getValue("sa_finesse")) { // weapon finesse
						tmpAttackBonus += 1 * charSheet.bonus(charSheet.getValue("dex"))
					}
					else {
						tmpAttackBonus += 1 * charSheet.bonus(charSheet.getValue("str"), 1)
					}
					if(charSheet.getValue("sa_power_attack") > 0) { // power attack
						tmpAttackBonus -= 1 * charSheet.getValue("sa_power_attack")
					}
					if(charSheet.getValue("offhand")) { // two weapon fighting
						if(charSheet.getValue("shield")) { // with a buckler
							tmpAttackBonus--
						}
						if(charSheet.getValue("sa_two_weapon")) { // trained
							if(charSheet.getValue("offhand.melee_size").substr(0, 5) == "Light") {
								tmpAttackBonus -= 2
							}
							else {
								tmpAttackBonus -= 4
							}
						}
						else { // untrained
							if(charSheet.getValue("offhand.melee_size").substr(0, 5) == "Light") {
								if(is_offhand) {
									tmpAttackBonus -= 8
								}
								else {
									tmpAttackBonus -= 4
								}
							}
							else {
								if(is_offhand) {
									tmpAttackBonus -= 10
								}
								else {
									tmpAttackBonus -= 6
								}
							}
						}
					}
				}

				console.log(" = " + tmpAttackBonus)
				return tmpAttackBonus
			},

			// damage helper figures combat damage bonuses
			damage_helper: function (weapon, group, bonus, is_ranged)
			{
				console.log("damage_helper ... ")
				tmpDamageBonus = 1 * bonus
				// feats & features
				tmpDamageBonus += charSheet.weapon_group_helper(group)
				if(charSheet.getValue("sa_spec")) { // weapon specialization
					for(i = 0; i < charSheet.sa_spec.length; i++) {
						if(charSheet.getValue("sa_spec." + i) == weapon) {
							tmpDamageBonus += 2
						}
					}
				}
				// ranged only modifiers
				if(is_ranged) {
					tmpDamageBonus += 1 * charSheet.getValue("sa_point_blank")
					if(charSheet.getValue("sa_deadly_aim") > 0) { // deadly aim
						tmpDamageBonus += 2 * charSheet.getValue("sa_deadly_aim")
					}
					if(charSheet.getValue("ranged.ranged_size") == "Two-Handed Ranged") {
						if((charSheet.getValue("str") - charSheet.getValue("loadType")) < 10 && weapon.indexOf("composite") == -1 && weapon.indexOf("crossbow") == -1) {
							// missile weapons except composite bows and crossbows only add Str penalty
							tmpDamageBonus += charSheet.bonus(charSheet.getValue("str"), 1)
						}
					}
					else {
						// thrown weapons and mighty bows add Str bonus
						tmpDamageBonus += charSheet.bonus(charSheet.getValue("str"), 1)
					}
				}
				// melee only modifiers
				if(!is_ranged) {
					tmpDamageBonus += 1 * charSheet.bonus(charSheet.getValue("str"), 1)
					if(charSheet.getValue("sa_power_attack") > 0) { // power attack
						tmpDamageBonus += 2 * charSheet.getValue("sa_power_attack")
					}
				}
				console.log(" ... CHECK")
				return tmpDamageBonus
			},

			// critical hit helper figures critical range and multiplier
			crit_helper: function (weapon, crit)
			{
				console.log("crit_helper ... ")
				multiplier = 1;
				if(crit.substr(0, 1) == "x") {
					tmpLeft = 20
					tmpRight = "-20/" + crit
				}
				else {
					tmpLeft = crit.substr(0, 2)
					tmpRight = crit.substr(2)
				}
				// feats
				if(charSheet.getValue("sa_critical")) { // improved critical
					for(i = 0; i < charSheet.sa_critical.length; i++) {
						if(charSheet.getValue("sa_critical." + i) == weapon) {
							multiplier++
						}
					}
				}
				console.log(" ... CHECK")
				if(multiplier > 1) {
					return (21 - ((21 - 1 * tmpLeft) * multiplier)) + tmpRight
				}
				else {
					return crit
				}
			},

			// test object is empty
			isEmpty: function (obj)
			{
				for(var prop in obj) {
					if(obj.hasOwnProperty(prop)) {
						return false;
					}
				}
				return true
			},

			// return only real value, default set in parameters, or default to blank string
			getValue: function (paramVarName, paramDefaultType)
			{
				// incorporate encumberance
				var splitObj = paramVarName.split(".")
				for(var i = 0, x = charSheet; i < splitObj.length; i++) {
					if(!x[splitObj[i]]) {
						if(paramDefaultType === 0) {
							return 0
						}
						else {
							if(paramDefaultType) {
								return paramDefaultType
							}
							else {
								return ""
							}
						}
					}
					else {
						x = x[splitObj[i]]
					}
				}
				return x
			},

			// return only numeric value, default to zero
			getNumber: function (paramVarName)
			{ 
				return 1 * charSheet.getValue(paramVarName, 0)
			},

			// set a value by the key / value parameters
			setValue: function (paramVarName, paramValue)
			{
				charSheet[paramVarName] = paramValue;
			},

			// build special attack description
			sa_build: function ()
			{
				console.log("sa_build ... ")
				charSheet.sa_desc = charSheet.getValue("sa_feats")
				charSheet.spell_like_desc = ""
				if(charSheet.getValue("sa_rp")) {
					charSheet.sa_desc += "; "
					if(charSheet.getValue("sa_rage")) {
						if(charSheet.getValue("sa_rage") == 1) {
							charSheet.sa_desc += "greater "
						}
						if(charSheet.getValue("sa_rage") == 2) {
							charSheet.sa_desc += "mighty "
						}
					}
					charSheet.sa_desc += "rage (" + (charSheet.getValue("sa_rp") + 1 * charSheet.getValue("sa_rp_feats")) + " rounds/day)"
					if(charSheet.getValue("sa_rage_powers")) {
						charSheet.sa_desc += "; rage powers (" + charSheet.getValue("sa_rage_powers").substr(2) + ")"
					}
				}
				if(charSheet.getValue("sa_pp")) {
					charSheet.sa_desc += "; bardic performance (" + (charSheet.getValue("sa_pp") + charSheet.getValue("sa_pp_feats")) + " rounds/day, " + charSheet.getValue("sa_bard_perf") + ")"
				}
				if(charSheet.getValue("sa_lohp")) {
					charSheet.sa_desc += "; lay on hands (" + (charSheet.getValue("sa_lohp") + charSheet.getValue("sa_lohp_feats")) + "/day, " + Math.floor(charSheet.getValue("class_level.PAL") / 2) + "d6)"
				}
				if(charSheet.getValue("sa_cp")) {
					charSheet.sa_desc += "; channel " + charSheet.getValue("sa_channel") + " energy " + (1 * charSheet.getValue("sa_cp") + 1 * charSheet.getValue("sa_cp_feats")) + "/day (DC " + Math.floor(10 + 1 * charSheet.getValue("class_level.CLR") / 2 + 1 * charSheet.bonus(charSheet.getValue("cha")) + 1 * charSheet.getValue("sa_channel_dc_feats")) + ", " + Math.floor((1 * charSheet.getValue("class_level.CLR") + 1) / 2) + "d6)"
				}
				if(charSheet.getValue("sa_kp")) {
					charSheet.sa_desc += "; "
					charSheet.sa_desc += "ki pool (" + (charSheet.getValue("sa_kp") + 1 * charSheet.getValue("sa_kp_feats")) + " points, "
					if(charSheet.getValue("class_level.MNK") > 15) {
						charSheet.sa_desc += "adamantine, "
					}
					if(charSheet.getValue("class_level.MNK") > 9) {
						charSheet.sa_desc += "lawful, "
					}
					charSheet.sa_desc += "magic)"
				}
				if(!charSheet.isEmpty(charSheet.getValue("sa_weapon_group"))) {
					temp = ""
					for(var weapon in charSheet.getValue("sa_weapon_group")) {
						temp += ", " + weapon + " +" + charSheet.getValue("sa_weapon_group." + weapon)
					}
					charSheet.sa_desc += "; weapon training (" + temp.substr(2) + ")"
				}
				if(charSheet.getValue("sa_weapon_mastery")) {
					charSheet.sa_desc += "; weapon mastery (" + charSheet.getValue("sa_weapon_mastery") + ")"
				}
				if(charSheet.getValue("sa_traits")) {
					charSheet.sa_desc += charSheet.getValue("sa_traits")
				}
				if(charSheet.getValue("sa_stunning_fist")) {
					charSheet.sa_desc += ", stunning fist (" + charSheet.getValue("sa_stunning_fist") + "/day, DC " + (10 + charSheet.getValue("total_level") / 2 + charSheet.bonus(charSheet.getValue("wis"))) + ")"
				}
				if(charSheet.getValue("sa_quivering_palm")) {
					charSheet.sa_desc += ", quivering palm (1/day)"
				}
				if(charSheet.getValue("sa_quarry") == 1) {
					charSheet.sa_desc += ", quarry"
				}
				if(charSheet.getValue("sa_quarry") > 1) {
					charSheet.sa_desc += ", improved quarry"
				}
				if(!charSheet.isEmpty(charSheet.getValue("sa_favoured_enemy"))) {
					temp = ""
					for(var enemy in charSheet.getValue("sa_favoured_enemy")) {
						temp += ", " + enemy + " +" + (2 * charSheet.getValue("sa_favoured_enemy." + enemy))
					}
					charSheet.sa_desc += "; favoured enemy (" + temp.substr(2) + ")"
				}
				if(charSheet.getValue("sa_features")) {
					charSheet.sa_desc += charSheet.getValue("sa_features")
				}
				if(charSheet.getValue("sa_desc")) {
					charSheet.sa_desc = "<br><b>Special Attacks</b> " + charSheet.getValue("sa_desc").substr(2)
				}
				// spell-like effects
				if(charSheet.getValue("spell_like_traits")) {
					charSheet.spell_like_desc += charSheet.getValue("spell_like_traits")
				}
				if(charSheet.getValue("spell_like_feats")) {
					charSheet.spell_like_desc += charSheet.getValue("spell_like_feats")
				}
				if(charSheet.getValue("spell_like_desc")) {
					charSheet.sa_desc += "<br><b>Spell-Like Abilities</b> " + charSheet.getValue("spell_like_traits").substr(2) + charSheet.getValue("spell_like_feats").substr(2)
				}
				$('#calcSA').html(charSheet.getValue("sa_desc"))
				console.log(" ... CHECK")
			},

			// build special quality description
			sq_build: function ()
			{
				console.log("function sq_build()")

				charSheet.sq_desc = charSheet.getValue("sq_features") + charSheet.getValue("sq_feats")

				// BBN
				if(charSheet.getValue("outputRagePowers")) {
					$('#editRagePowers').html("Rage Powers " + charSheet.getValue("outputRagePowers"))
					$('#spellsSection').show()
				}

				// BRD
				if(charSheet.getValue("sa_versatile_perf")) {
					charSheet.sq_desc += ", versatile performance (" + charSheet.getValue("sa_versatile_perf").substr(2) + ")";
					$('#editVersatilePerf').html("<br>Versatile Performance " + charSheet.getValue("outputVersatilePerf"))
				}
				if(charSheet.getValue("sa_jack_of_all_trades")) {
					charSheet.sq_desc += ", jack-of-all-trades"
					if(charSheet.getValue("sa_jack_of_all_trades") == 1) {
						charSheet.sq_desc += " (use any skill)"
					}
					if(charSheet.getValue("sa_jack_of_all_trades") == 2) {
						charSheet.sq_desc += " (use any skill as class skill)"
					}
					if(charSheet.getValue("sa_jack_of_all_trades") == 3) {
						charSheet.sq_desc += " (take 10 on any skill as class skill)"
					}
				}

				// CLR
				if(charSheet.getValue("outputChannelEnergy")) {
					$('#editChannelEnergy').html("<br>Channel Energy " + charSheet.getValue("outputChannelEnergy"))
				}
				if(charSheet.getValue("outputDomains")) {
					if(charSheet.getValue("sa_domains").length > 0) {
						charSheet.sa_domain_powers = temp = ""
						// loop through each domain
						for(i = 0; i <= charSheet.getValue("sa_domains").length; i++) {
							temp += ", " + charSheet.getValue("sa_domains." + i)
							for(k = 1; k < 1 * charSheet.getValue("total_level"); k++) {
								if(charSheet.getValue("domain_powers." + charSheet.getValue("sa_domains." + i) + "." + k)) {
									// domain powers (based on class feature (Domains), domains selected, and class level
									ktemp = charSheet.getValue("domain_powers." + charSheet.getValue("sa_domains." + i) + "." + k).split(": ")
									charSheet.sa_domain_powers += ", " + ktemp[0]
								}
							}
						}
						$('#calcDomains').html("<br><b>Domains</b> " + temp.substr(2))
// TO DO: deal with domain powers individually, as with class features
						$('#calcDomainPowers').html("<b>Domain Powers</b> " + charSheet.getValue("sa_domain_powers").substr(2))
					}
					$('#editDomains').html("<br>Domains " + charSheet.getValue("outputDomains"))
				}

				// DRD
				if(charSheet.getValue("outputAnimalCompanion")) {
					$('#editAnimalCompanion').html("<br>Animal Companion " + charSheet.getValue("outputAnimalCompanion"))
				}

				// FTR
				if(charSheet.getValue("outputWeaponGroup")) {
					$('#editWeaponGroup').html("<br>Weapon Training " + charSheet.getValue("outputWeaponGroup"))
					$('#spellsSection').show()
				}
				if(charSheet.getValue("outputWeaponMastery")) {
					$('#editWeaponMastery').html("<br>Weapon Mastery " + charSheet.getValue("outputWeaponMastery"))
				}

				// MNK
				if(charSheet.getValue("sa_slow_fall")) {
					if(charSheet.getValue("class_level.MNK") == 20) {
						charSheet.sq_desc += ", slow fall any distance";
					}
					else {
						charSheet.sq_desc += ", slow fall (" + ((charSheet.getValue("sa_slow_fall") + 1) * 10) + " ft.)";
					}
				}

				// PAL
				if(charSheet.getValue("outputDivineBond")) {
					$('#editDivineBond').html("<br>Divine Bond " + charSheet.getValue("outputDivineBond"))
				}
				if(charSheet.getValue("sq_mercies")) {
					charSheet.sq_desc += ", mercies (" + charSheet.getValue("sq_mercies").substr(2) + ")"
					$('#editMercies').html("<br>Mercies " + charSheet.getValue("outputMercies"))
				}

				// RGR
				if(charSheet.getValue("outputFavouredEnemy")) {
					$('#editFavouredEnemy').html("<br>Favoured Enemy " + charSheet.getValue("outputFavouredEnemy"))
					$('#spellsSection').show()
				}
				if(!charSheet.isEmpty(charSheet.getValue("sq_favoured_terrain"))) {
//					charSheet.sq_desc += ", favoured terrain (" + charSheet.getValue("sq_favoured_terrain").substr(2) + ")"
					temp = ""
					for(var terrain in charSheet.getValue("sq_favoured_terrain")) {
						temp += ", " + terrain + " +" + (2 * charSheet.getValue("sq_favoured_terrain." + terrain))
					}
					charSheet.sq_desc += ", favoured terrain (" + temp.substr(2) + ")"
					$('#editFavouredTerrain').html("<br>Favoured Terrain " + charSheet.getValue("outputFavouredTerrain"))
				}
				if(charSheet.getValue("outputCombatStyle")) {
					$('#editCombatStyle').html("<br>Combat Style " + charSheet.getValue("outputCombatStyle"))
				}
				if(charSheet.getValue("outputHuntersBond")) {
					$('#editHuntersBond').html("<br>Hunter's Bond " + charSheet.getValue("outputHuntersBond"))
				}

				// ROG
				if(charSheet.getValue("sq_rogue_talents")) {
					charSheet.sq_desc += ", rogue talents (" + charSheet.getValue("sq_rogue_talents").substr(2) + ")"
					$('#editRogueTalents').html("<br>Rogue Talents " + charSheet.getValue("outputRogueTalents"))
				}

				// SOR
				if(charSheet.getValue("outputBloodline")) {
					if(charSheet.getValue("outputNewArcana")) {
						charSheet.sa_bloodline_notes += ", new arcana (" + charSheet.getValue("sa_new_arcana").substr(2) + ")"
						charSheet.outputBloodline += "<br>New Arcana " + charSheet.getValue("outputNewArcana")
					}
					if(charSheet.getValue("outputSchoolPower")) {
						charSheet.sa_bloodline_notes += ", school power (+2 DC to " + charSheet.getValue("sa_school_power").substr(2) + ")"
						charSheet.outputBloodline += "<br>School Power " + charSheet.getValue("outputSchoolPower")
					}
					if(charSheet.getValue("outputDragonType")) {
						charSheet.sa_bloodline += ", " + charSheet.getValue("sa_dragon_type")
						charSheet.outputBloodline += "<br>Element Type " + charSheet.getValue("outputDragonType")
					}
					if(charSheet.getValue("outputElementType")) {
						charSheet.sa_bloodline += ", " + charSheet.getValue("sa_element_type")
						charSheet.outputBloodline += "<br>Dragon Type " + charSheet.getValue("outputElementType")
					}
					$('#calcBloodline').html("<br><b>Bloodline</b> " + charSheet.getValue("sa_bloodline") + " (" + charSheet.getValue("sa_bloodline_notes").substr(2) + ")")
					$('#editBloodline').html("<br>Bloodline " + charSheet.getValue("outputBloodline"))
				}

				// WIZ
				if(charSheet.getValue("outputArcaneBond")) {
					$('#editArcaneBond').html("<br>Arcane Bond " + charSheet.getValue("outputArcaneBond"))
				}
				if(charSheet.getValue("outputArcaneSchool")) {
					temp = ""
					if(charSheet.getValue("sa_arcane_school") && charSheet.getValue("sa_arcane_school") != "Universalist") {
						for(var school in charSheet.getValue("sa_opposition_schools")) {
							temp += ", " + charSheet.getValue("sa_opposition_schools." + school)
						}
						temp = ", opposition schools (" + temp.substr(2) + ")"
					}
					$('#calcArcaneSchool').html("<b>Arcane School</b> " + charSheet.getValue("sa_arcane_school") + " (" + charSheet.getValue("sa_arcane_school_notes").substr(2) + ")" + temp)
					$('#editArcaneSchool').html("<br>Arcane School " + charSheet.getValue("outputArcaneSchool") + "<br>Opposition Schools " + charSheet.getValue("outputOppositionSchool"))
				}

				if(charSheet.getValue("sq_desc")) {
					$('#calcSQ').html("<b>SQ</b> " + charSheet.getValue("sq_desc").substr(2))
					$('#specialqualitiesSection').show()
				}
				else {
					$('#calcSQ').text(" ")
					$('#specialqualitiesSection').hide()
				}

				console.log(" ... CHECK")
			},

			//
			spells_build: function ()
			{
				for(var tmpClass in charSheet.getValue("spells")) {
					if(tmpClass != "DOMAIN") {
						// calculate concentration score and update title
						tmpConc = charSheet.getValue("spells." + tmpClass + ".concentration") + 1 * charSheet.getValue("conc_feats")
						$('#calc' + tmpClass + 'Known').html("<br><b>" + charSheet.getValue("spells." + tmpClass + ".title") + "</b> (CL " + charSheet.ordinal(charSheet.getValue("class_level." + tmpClass)) + "; concentration " + tmpConc + ")")
						$('#edit' + tmpClass + 'Known').html("<br><b>" + charSheet.getValue("spells." + tmpClass + ".title") + "</b>")

						// update 0-level spells for classes which get them
						if(tmpClass == "BRD" || tmpClass == "CLR" || tmpClass == "DRD" || tmpClass == "SOR") {
							$('#edit0' + tmpClass + 'Known').html("<br>0-level " + charSheet['output0' + tmpClass + 'Known'])
							$('#calc0' + tmpClass + 'Known').html("<br><b>0-level (at will) -</b> " + charSheet['sa_0_' + tmpClass + '_known'].substr(2))
						}
					}

					for(var tmpLevel in charSheet.getValue("spells." + tmpClass)) {
						// do only for level objects (0 - 9)
						if(tmpLevel < 10) {
							tmpCount = charSheet.getValue("spells." + tmpClass + "." + tmpLevel + ".perDay")
							if(charSheet.getValue("sa_domains").length) {
								tmpCount++
							}

							if(tmpClass == "DOMAIN") {
								tmpEdit = "<br>" + charSheet.ordinal(tmpLevel) + "-level domain " + charSheet['output' + tmpLevel + tmpClass + 'Known']
								tmpCalc = charSheet['sa_' + tmpLevel + '_' + tmpClass + '_known']
								$('#edit' + tmpLevel + tmpClass + 'Known').html(tmpEdit)
								$('#calc' + tmpLevel + tmpClass + 'Known').html(tmpCalc)
							}
							else {
								tmpEdit = "<br>" + charSheet.ordinal(tmpLevel) + "-level " + charSheet['output' + tmpLevel + tmpClass + 'Known']
								tmpCalc = "<br><b>" + charSheet.ordinal(tmpLevel) + "-level (" + tmpCount + "/day) -</b> " + charSheet['sa_' + tmpLevel + '_' + tmpClass + '_known'].substr(2)
								$('#edit' + tmpLevel + tmpClass + 'Known').html(tmpEdit)
								$('#calc' + tmpLevel + tmpClass + 'Known').html(tmpCalc)
							}

						}
					}

					// make sections visible
					if(tmpClass == "DRD") {
						tmpClass = "CLR"
					}
					$('#spellsSection').show()
					$('#calc' + tmpClass + 'SpellBlock').show()
				}
			},

			senses_build: function ()
			{
				console.log("senses_build ... ")
				charSheet.senses_desc = ""
				if(charSheet.getValue("senses_traits")) {
					charSheet.senses_desc += charSheet.getValue("senses_traits")
				}
				charSheet.senses_desc += "; "
				$('#calcSenses').text(charSheet.getValue("senses_desc").substr(2) + "Perception " + charSheet.getValue("perception"))
				console.log(" ... CHECK")
			},
		}

		// calculate main section
		function calcMain()
		{
			console.log("function calcMain()")

			charSheet.init_feats = 0
			$('#calcSize').text(charSheet.getValue("size"))
			$('#calcInit').text(10 + charSheet.bonus(charSheet.getValue("dex")) + charSheet.getValue("init_feats"))
			charSheet.cr = $('#cr').val()
			charSheet.total_level = $('#total_level').val()
			charSheet.hp_main = $('#hp_main').val()
			charSheet.hp_desc_main = $('#hp_desc_main').val()
			charSheet.sp_main = $('#sp_main').val()
			charSheet.sp_bonus = $('#sp_bonus').val()
			charSheet.total_bab = $('#total_bab').val()
			charSheet.total_fort = $('#total_fort').val()
			charSheet.total_ref = $('#total_ref').val()
			charSheet.total_will = $('#total_will').val()

			// recalculate sections
			calcSpecialabilities()

			console.log(" ... CHECK")
		}

		// calculate defense section
		function calcDefense()
		{
			console.log("function calcDefense()")

			charSheet.armour_build()
			charSheet.hp_build()
			$('#calcFort').text(10 + 1 * charSheet.getValue("total_fort") + charSheet.bonus(charSheet.getValue("con")) + 1 * charSheet.getValue("fort_traits") + 1 * charSheet.getValue("fort_feats"))
			$('#calcRef').text(10 + 1 * charSheet.getValue("total_ref") + charSheet.bonus(charSheet.getValue("dex")) + 1 * charSheet.getValue("ref_traits") + 1 * charSheet.getValue("ref_feats"))
			$('#calcWill').text(10 + 1 * charSheet.getValue("total_will") + charSheet.bonus(charSheet.getValue("wis")) + 1 * charSheet.getValue("will_traits") + 1 * charSheet.getValue("will_feats"))
			charSheet.sd_build()
			charSheet.dr_build()
			charSheet.immune_build()

			console.log(" ... CHECK")
		}

		// calculate offense section
		function calcOffense()
		{
			console.log("function calcOffense()")

			charSheet.speed_build()
			$('#calcBAB').text(10 + 1 * charSheet.getValue("total_bab"))
			charSheet.cmb_build()
			charSheet.cmd_build()
			// TO DO: CMD affected by deflection?
			charSheet.melee_build()
			charSheet.ranged_build()
			charSheet.sa_build()
			charSheet.spells_build()

			console.log(" ... CHECK")
		}

		// calculate ability score section
		function calcAbilities()
		{
			console.log("function calcAbilities()")
			// strength: encumberance
			//   Bigger and Smaller Creatures: (pg. 170)
			//     Bipeds: Fine ×1/8, Diminutive ×1/4, Tiny ×1/2, Small ×3/4, Medium ×1, Large ×2, Huge ×4, Gargantuan ×8, Colossal ×16
			//     Quadrupeds: Fine ×1/4, Diminutive ×1/2, Tiny ×3/4, Small ×1, Medium ×1-1/2, Large ×3, Huge ×6, Gargantuan ×12, Colossal ×24
			for(i = 1 * charSheet.getValue("str"), multiplier = 1; i > 14; i -= 5, multiplier++) { }
			charSheet.maximumLoad = i * 10
			if(i == 11) {
				charSheet.maximumLoad = 115
			}
			if(i == 12) {
				charSheet.maximumLoad = 130
			}
			if(i == 13) {
				charSheet.maximumLoad = 150
			}
			if(i == 14) {
				charSheet.maximumLoad = 175
			}
			charSheet.maximumLoad *= multiplier
			if(charSheet.getValue("size") == 'Small') {
				charSheet.maximumLoad *= 3/4
			}
//			charSheet.heavyLoad = charSheet.getValue("maximumLoad")
//			charSheet.mediumLoad = Math.floor(charSheet.getValue("maximumLoad") * 2 / 3)
			charSheet.lightLoad = Math.floor(charSheet.getValue("maximumLoad") * 1 / 3)

			// dexterity: ac
			charSheet.ac_build()

			// constitution: hp
			charSheet.hp_abilities = charSheet.bonus(charSheet.getValue("con"))
			charSheet.hp_build()

			// intelligence: sp
			charSheet.sp_abilities = charSheet.bonus(charSheet.getValue("int"))
			charSheet.sp_build()

			// recalculate sections
			calcSkills()
			calcDefense()
			calcOffense()

			console.log(" ... CHECK")
		}

		// calculate feats subsection
		function calcFeats()
		{
			console.log("function calcFeats()")

			// set default values for feats
			charSheet.spd_bonus_lgt_l_lgt_a = 0
			charSheet.carrying_cap_feats = 1
			charSheet.total_fp_used = 0
			charSheet.hp_feats = charSheet.init_feats = 0
			charSheet.ac_dodge_feats = charSheet.ac_luck_feats = charSheet.ac_insight_feats = 0
			charSheet.fort_feats = charSheet.ref_feats = charSheet.will_feats = 0
			charSheet.sa_power_attack = charSheet.sa_combat_expertise = charSheet.sa_two_weapon_defense = charSheet.sa_deadly_aim = 0
			charSheet.sa_finesse = charSheet.sa_rapid_reload = charSheet.sa_agile_maneuvers = 0
			charSheet.sa_defensive_combat_training = charSheet.ac_improvised_shield = 0
			charSheet.sa_point_blank = charSheet.sa_rapid_shot = charSheet.sa_double_slice = 0
			charSheet.sa_critical = []
			charSheet.sa_focus = []
			charSheet.sa_spec = []
			charSheet.master_craftsman_feat = charSheet.skill_focus_feat = charSheet.skill_artist_feat = ""
			charSheet.ac_shield_focus = 0
			charSheet.sa_pp_feats = charSheet.sa_rp_feats = charSheet.sa_lohp_feats = charSheet.sa_kp_feats = 0
			charSheet.sa_mercy_feats = ""
			charSheet.sa_channel_dc_feats = charSheet.sa_cp_feats = 0
			charSheet.conc_feats = charSheet.gold_feats = 0
			charSheet.fp_build()
			charSheet.sq_feats = charSheet.sa_feats = charSheet.sd_feats = ""
			charSheet.spell_like_feats = charSheet.save_feats = ""
			for(i = 0; i < 150; i++) {
				charSheet.skill_feat_bonus[i] = 0
				charSheet.skill_doubling_bonus[i] = 0
			}

			// loop through each feat
			for(i = 0; i < charSheet.getValue("feats").length; i++) {
				if(charSheet.getValue("feats." + i)) {
					charSheet.total_fp_used++
					var featDetail = charSheet.getValue("feats." + i + ".detail")
					switch(charSheet.getValue("feats." + i + ".name")) {
						// regional
						case "Artist": { // You gain a +2 bonus on all Perform checks and on checks with one Craft skill that involves art, such as calligraphy, painting, sculpture, or weaving. In addition, if you have the bardic music ability, you may use it three additional times per day. For example, a 3rd-level bard with this feat could use her bardic music ability six times per day.
							charSheet.skill_trait_bonus[47] += 2
							charSheet.skill_trait_bonus[48] += 2
							charSheet.skill_trait_bonus[49] += 2
							charSheet.skill_trait_bonus[50] += 2
							charSheet.skill_trait_bonus[51] += 2
							charSheet.skill_trait_bonus[52] += 2
							charSheet.skill_trait_bonus[53] += 2
							charSheet.skill_trait_bonus[54] += 2
							charSheet.skill_trait_bonus[52] += 2
							charSheet.skill_artist_feat = charSheet.getValue("feats." + i + ".detail")
							charSheet.sa_pp_feats += 3
							break
						}
						case "Blooded": { // You get a +2 bonus on Initiative and a +2 bonus on all Perception checks.
							charSheet.skill_trait_bonus[46] += 2
							charSheet.init_feats += 2
							$('#calcInit').text(10 + charSheet.bonus(charSheet.getValue("dex")) + charSheet.getValue("init_feats"))
							break
						}
						case "Bloodline of Fire": { // You receive a +4 bonus on saving throws against fire effects. In addition, you cast spells with the fire descriptor at +2 caster level.
							charSheet.save_feats += ", +4 vs. fire"
							// TO DO
							break
						}
						case "Bullheaded": { // You receive a +2 bonus on all Will saves. You cannot become shaken, and you ignore the effects of the shaken condition.
							charSheet.will_feats += 2
							charSheet.immune_traits += ", shaken"
							break
						}
						case "Caravanner": { // You get a +2 bonus on Handle Animal and a +2 bonus on Knowledge (geography) checks.
							charSheet.skill_trait_bonus[32] += 2
							charSheet.skill_trait_bonus[38] += 2
							break
						}
						case "Cosmopolitan": { // You gain a +2 bonus on Bluff, Diplomacy and Sense Motive checks.
							charSheet.skill_trait_bonus[4] += 2
							charSheet.skill_trait_bonus[27] += 2
							charSheet.skill_trait_bonus[88] += 2
							break
						}
						case "Courteous Magocracy": { // You receive a +2 bonus on Diplomacy and Spellcraft checks.
							charSheet.skill_trait_bonus[27] += 2
							charSheet.skill_trait_bonus[90] += 2
							break
						}
						case "Dauntless": { // // You gain +5 hit points.
							charSheet.hp_feats += 5
							break
						}
						case "Daylight Adaptation": { // You no longer suffer circumstance penalties when exposed to bright light.
							// TO DO
							break
						}
						case "Discipline": { // You gain a +2 bonus on Will saves and a +2 bonus on Concentration checks.
							charSheet.will_feats += 2
							charSheet.conc_feats += 2
							break
						}
						case "Education": { // All Knowledge skills are class skills for your current and all your future classes. You may also select two Knowledge skills to develop more fully. You get a +2 bonus on all checks you make with those skills. If you select a Knowledge skill in which you do not yet have ranks, you gain no immediate benefit, since Knowledge skills can be used only with training. But the selection still represents your improved potential for that skill.
							charSheet.skill_education = 1
							charSheet.fp_traits += 0.5
							break
						}
						case "Ethran": { // You gain a +2 bonus on Handle Animal and Survival checks. When dealing with other Rashemis, you gain a +2 bonus on Charisma-based skill and ability checks. Furthermore, you can participate in circle magic (see Circle Magic on page 59 in the FORGOTTEN REALMS Campaign Setting).
							charSheet.skill_trait_bonus[32] += 2
							charSheet.skill_trait_bonus[92] += 2
							charSheet.sq_feats += ", +2 to Cha-based skills dealing with other Rashemis, can participate in circle magic"
							break
						}
						case "Fearless": { // You are immune to fear effects
							charSheet.immune_traits += ", fear"
							break
						}
						case "Foe Hunter": { // You acquire a favored enemy, based on region.
							if(charSheet.sa_favoured_enemy[featDetail]) {
								charSheet.sa_favoured_enemy[featDetail]++
							}
							else {
								charSheet.sa_favoured_enemy[featDetail] = 1
							}
							break
						}
						case "Forester": { // You gain a +1 bonus on Perception and Stealth checks. When you are in forest terrain, this bonus increases to +3.
							charSheet.skill_trait_bonus[46] += 1
							charSheet.skill_trait_bonus[91] += 1
							charSheet.sq_feats += ", +2 Perception and Stealth in forest"
							break
						}
						case "Furious Charge": { // You gain a +4 bonus on the attack roll you make at the end of a charge.
							charSheet.sa_feats += ", +4 charge attack"
							break
						}
						case "Genie Lore": { // You add +1 to the DC of saving throws for any sorcerer spells with the energy type descriptor that you choose: acid, cold, electricity, or fire
							charSheet.sa_feats += ", +1 DC " + featDetail + " spells"
							break
						}
						case "Grim Visage": { // You gain a +2 bonus on Intimidate and Sense Motive checks
							charSheet.skill_trait_bonus[34] += 2
							charSheet.skill_trait_bonus[88] += 2
							break
						}
						case "Harem Trained": { // You receive a +2 bonus on Diplomacy and Perform checks
							charSheet.skill_trait_bonus[27] += 2
							charSheet.skill_trait_bonus[47] += 2
							charSheet.skill_trait_bonus[48] += 2
							charSheet.skill_trait_bonus[49] += 2
							charSheet.skill_trait_bonus[50] += 2
							charSheet.skill_trait_bonus[51] += 2
							charSheet.skill_trait_bonus[52] += 2
							charSheet.skill_trait_bonus[53] += 2
							charSheet.skill_trait_bonus[54] += 2
							break
						}
						case "Horse Nomad": { // You gain Martial Weapon Proficiencies (light lance, scimitar, composite shortbow), and a +3 bonus on all Ride checks
							charSheet.skill_trait_bonus[87] += 3
							break
						}
						case "Jotunbrud": { // You are treated as Large for opposed rolls if that's advantageous to you
							charSheet.sa_feats += ", treat as large for combat maneuvers"
							break
						}
						case "Knifefighter": { // You can use a light weapon to attack your opponent in a grapple with no penalty, and need not win a grapple check to draw a light weapon while grappling. If your base attack bonus is +6 or higher, you can make a full attack with a light weapon while grappling, provided that you already have your weapon drawn.
							charSheet.sa_feats += ", no penalty with light weapon in a grapple"
							break
						}
						case "Luck of Heroes": { // You receive a +1 luck bonus on all saving throws and a +1 luck bonus to AC
							charSheet.fort_feats += 1
							charSheet.ref_feats += 1
							charSheet.will_feats += 1
							charSheet.ac_luck_feats += 1
							break
						}
						case "Magic in the Blood": { // Any racial, spell-like ability that is otherwise usable once per day is now usable three times per day.
							charSheet.sa_feats += ", use racial spell-like abilities 3/day"
							break
						}
						case "Magical Training": { // You can cast three 0-level arcane spells per day as either a sorcerer or wizard
							charSheet.sa_feats += ", cast 0-level arcane spell as " + featDetail + " 3/day"
							break
						}
						case "Mercantile Background": { // You get 75% of the list price when selling goods. Once per month, you can buy any single item at 75% of the offered price. You also receive an extra 300 gp.
							charSheet.gold_feats += 300
							charSheet.sq_feats += ", mercantile background"
							break
						}
						case "Militia": { // You gain proficiency with all martial weapons.
							break
						}
						case "Mind Over Body": { // At 1st level, you may use your Intelligence or Charisma modifier (your choice) to determine your bonus hit points. For all subsequent levels, you use your Constitution modifier, as normal. In addition, you gain +1 hit point every time you learn a metamagic feat. Furthermore, if you can cast arcane spells, you get a +1 insight bonus to Armor Class.
							charSheet.hp_feats += 1 * featDetail - charSheet.bonus(charSheet.getValue("con"))
							if(is_arcane_spellcaster()) {
								charSheet.ac_insight_feats++
							}
							break
						}
						case "Raumathor Heritor": { // You gain a +2 bonus on Knowledge (the Planes) checks and wizard becomes a favored class for you. In addition, three times per day, you can detect evil outsiders.
							charSheet.skill_trait_bonus[43] += 2
							charSheet.spell_like_feats += "; detect evil outsiders (3/day, CL " + charSheet.ordinal(charSheet.getValue("total_level")) + ")"
							break
						}
						case "Resist Poison": { // 
							break
						}
						case "Saddleback": { // Once per round, if you and/or your mount fail a Reflex save you can attempt a Ride check.
							charSheet.sd_feats += ", use Ride check in place of failed Reflex save"
							break
						}
						case "Silver Palm": { // You get a +2 bonus on all Appraise, Bluff, and Sense Motive checks
							charSheet.skill_trait_bonus[3] += 2
							charSheet.skill_trait_bonus[4] += 2
							charSheet.skill_trait_bonus[88] += 2
							break
						}
						case "Smooth Talk": { // You take a -5 penalty if you attempt a Diplomacy check as a full-round action
							break
						}
						case "Snake Blood": { // You gain a +2 bonus on Reflex saving throws and a +2 bonus on Fortitude saves against poison
							charSheet.ref_feats += 2
							charSheet.save_feats += ", +2 vs. poison"
							break
						}
						case "Spellwise": { // You receive a +2 bonus on all Knowledge (arcana) and Spellcraft checks. You also get a +2 bonus on saving throws against illusion spells or effects.
							charSheet.skill_trait_bonus[35] += 2
							charSheet.skill_trait_bonus[90] += 2
							charSheet.save_feats += ", +2 vs. illusion"
							break
						}
						case "Stormheart": { // You gain a +2 bonus on Acrobatics and Profession (sailor) checks. You ignore any hampered movement penalties for fighting on pitching or slippery decks, and you gain a +1 dodge bonus to Armor Class during any fight that takes place on or in a boat or ship.
							charSheet.skill_trait_bonus[1] += 2
							charSheet.skill_trait_bonus[79] += 2
							charSheet.sd_feats += ", unhampered and +1 AC on a boat"
							break
						}
						case "Street Smart": { // You gain a +2 bonus on Diplomacy, Intimidate, and Sense Motive checks
							charSheet.skill_trait_bonus[27] += 2
							charSheet.skill_trait_bonus[34] += 2
							charSheet.skill_trait_bonus[88] += 2
							break
						}
						case "Strong Back": { // Gain 33% additional carry capacity
							charSheet.carrying_cap_feats = Math.max((4/3), charSheet.getValue("carrying_cap_feats"))
							break
						}
						case "Strong Soul": { // You gain a +1 bonus on all Fortitude and Will saves. Against death effects, energy drain, and ability drain attacks, this bonus increases to +3.
							charSheet.fort_feats += 1
							charSheet.will_feats += 1
							charSheet.save_feats += ", +2 vs. death and drain"
							break
						}
						case "Surefooted": { // You gain a +2 bonus on Acrobatics and Climb checks. You also ignore hampered movement penalties for ice and steep slopes (see Movement in Chapter 9 of the Player's Handbook). If a surface is both steep and icy, you treat it as a x2 movement cost instead of x4.
							charSheet.skill_trait_bonus[1] += 2
							charSheet.skill_trait_bonus[5] += 2
							charSheet.sq_feats += ", unhampered by ice and steep slopes"
							break
						}
						case "Survivor": { // You get a +2 bonus on Fortitude saves and a +2 bonus on Survival checks
							charSheet.fort_feats += 2
							charSheet.skill_trait_bonus[92] += 2
							break
						}
						case "Tattoo Focus": { // The saving throw DC for any spell you cast from your specialized school increases by 1. You also gain a +1 bonus on caster level checks made to overcome a creature's spell resistance when you cast spells from that school. In addition, you are capable of participating in Red Wizard circle magic.
							charSheet.sq_feats += ", +1 DC and +1 CL to overcome SR for specialized school spells, can participate in Red Wizard circle magic"
							break
						}
						case "Thug": { // You gain a +2 bonus on initiative checks and a +2 bonus on Appraise and Intimidate checks
							charSheet.skill_trait_bonus[3] += 2
							charSheet.skill_trait_bonus[34] += 2
							charSheet.init_feats += 2
							break
						}
						case "Thunder Twin": { // You gain a +2 bonus on Diplomacy and Intimidate checks
							charSheet.skill_trait_bonus[27] += 2
							charSheet.skill_trait_bonus[34] += 2
							charSheet.sq_feats += ", detect direction of twin sibling"
							break
						}
						case "Tireless": { // You reduce the effects of exhaustion and fatigue, by one step.
							charSheet.immune_traits += ", fatigue"
							charSheet.resist_traits += ", exhaustion"
							break
						}
						case "Treetopper": { // You get a +2 bonus on Acrobatics and Climb checks. You do not lose your Dexterity bonus to AC while climbing, and attackers do not gain any bonuses to attack you while you are climbing.
							charSheet.skill_trait_bonus[1] += 2
							charSheet.skill_trait_bonus[5] += 2
							charSheet.sq_feats += ", no penalty while climbing"
							break
						}
						case "Twin Sword style": { // 
							break
						}

						// combat
						case "Agile Maneuvers": { // * Use your Dex bonus when calculating your CMB
							charSheet.sa_agile_maneuvers = 1
							break
						}
						case "Combat Agility": { // * Trade attack bonus for AC bonus
							charSheet.sa_combat_expertise += 1 * charSheet.getValue("feats." + i + ".detail")
							break
						}
						case "Combat Expertise": { // * Trade attack bonus for AC bonus
							charSheet.sa_combat_expertise += 1 * charSheet.getValue("feats." + i + ".detail")
							break
						}
						case "Deadly Aim": { // * Trade ranged attack bonus for damage
							charSheet.sa_deadly_aim += charSheet.getValue("feats." + i + ".detail")
							break
						}
						case "Defensive Combat Training": { // * Use your total Hit Dice as your base attack bonus for CMD
							charSheet.sa_defensive_combat_training = 1
							break
						}
						case "Dodge": {
							charSheet.ac_dodge_feats += 1
							charSheet.ac_build()
							break
						}
						case "Double Slice": { // * Add your Str bonus to off-hand damage rolls
							charSheet.sa_double_slice = 1
							break
						}
						case "Improved Critical": { // * Double the threat range of one weapon
							charSheet.sa_critical.push(charSheet.getValue("feats." + i + ".detail"))
							break
						}
						case "Improved Initiative": { // * +4 bonus on initiative checks
							charSheet.init_feats += 4
							break
						}
						case "Improved Two-Weapon Fighting": {
							charSheet.sa_two_weapon++
							break
						}
						case "Improved Unarmed Strike": { // * Always considered armed
							charSheet.sa_unarmed = 1
							break
						}
						case "Improvised Shield Mastery": // Improve improvised shield coverage
						case "Improvised Shield": { // Make any item not used as a weapon into a shield
							if (charSheet.getValue("feats." + i + ".detail")) {
								charSheet.ac_improvised_shield += 1
							}
							break
						}
						case "Intimidating Prowess": {  // * Add Str to Intimidate in addition to Cha
							if(charSheet.getValue("str") - charSheet.getValue("loadType") > 11) {
								charSheet.getValue("skill_feat_bonus")[34] += 1 * charSheet.bonus(charSheet.getValue("str"), 1)
							}
							break
						}
						case "Mounted Combat": { //* Ride 1 rank; Avoid attacks on mount with Ride check
							break
						}
						case "Mounted Archery": { //* Mounted Combat; Halve the penalty for ranged attacks while mounted
							break
						}
						case "Point-Blank Shot": { //* +1 attack and damage on targets within 30 feet
							charSheet.sa_point_blank += 1
							break
						}
						case "Power Attack": { //* Trade melee attack bonus for damage
							charSheet.sa_power_attack = 1 * charSheet.getValue("feats." + i + ".detail")
							break
						}
						case "Rapid Reload": { //* Reload crossbow quickly
							charSheet.sa_rapid_reload = charSheet.getValue("feats." + i + ".detail")
							break
						}
						case "Rapid Shot": { //* Make one extra ranged attack
							charSheet.sa_rapid_shot++
							break
						}
						case "Greater Shield Focus": 
						case "Shield Focus": { // Gain a +1 bonus to your AC when using a shield
							charSheet.ac_shield_focus += 1
							break
						}
						case "Simple Weapon Proficiency": break // No penalty on attacks made with simple weapons
						case "Stunning Fist": {
							charSheet.sa_stunning_fist = Math.floor(1 * charSheet.getValue("class_level.MNK") + (1 * charSheet.getValue("total_level") - charSheet.getValue("class_level.MNK")) / 4)
							break
						}
						case "Two-Weapon Defense": { //* Gain +1 shield bonus when fighting with two weapons
							charSheet.sa_two_weapon_defense += 1
							break
						}
						case "Improved Two-Weapon Fighting": //* Gain additional off-hand attack
						case "Greater Two-Weapon Fighting": //* Gain a third off-hand attack
						case "Two-Weapon Fighting": { //* Reduce two-weapon fighting penalties
							charSheet.sa_two_weapon++
							break
						}
						case "Weapon Finesse": { //* — Use Dex instead of Str on attack rolls with light weapons
							charSheet.sa_finesse = 1
							break
						}
						case "Greater Weapon Focus": //* +1 bonus on attack rolls with one weapon
						case "Weapon Focus": { //* +1 bonus on attack rolls with one weapon
							charSheet.sa_focus.push(charSheet.getValue("feats." + i + ".detail"))
							break
						}
						case "Greater Weapon Specialization": //* +2 bonus on damage rolls with one weapon
						case "Weapon Specialization": { //* +2 bonus on damage rolls with one weapon
							charSheet.sa_spec.push(charSheet.getValue("feats." + i + ".detail"))
							break
						}

						// other - skill
						case "Acrobatic": { // +2 bonus on Acrobatics and Fly checks
							charSheet.skill_doubling_bonus[1] += 2
							charSheet.skill_doubling_bonus[31] += 2
							break
						}
						case "Alertness": { // +2 bonus on Perception and Sense Motive checks
							charSheet.skill_doubling_bonus[46] += 2
							charSheet.skill_doubling_bonus[88] += 2
							break
						}
						case "Animal Affinity": { // +2 bonus on Handle Animal and Ride checks
							charSheet.skill_doubling_bonus[32] += 2
							charSheet.skill_doubling_bonus[87] += 2
							break
						}
						case "Athletic": { // +2 bonus on Climb and Swim checks
							charSheet.skill_doubling_bonus[5] += 2
							charSheet.skill_doubling_bonus[93] += 2
							break
						}
						case "Deceitful": { // +2 bonus on Bluff and Disguise checks
							charSheet.skill_doubling_bonus[4] += 2
							charSheet.skill_doubling_bonus[29] += 2
							break
						}
						case "Deft Hands": { // +2 bonus on Disable Device and Sleight of Hand checks
							charSheet.skill_doubling_bonus[28] += 2
							charSheet.skill_doubling_bonus[89] += 2
							break
						}
						case "Magical Aptitude": { // +2 bonus on Spellcraft and Use Magic Device checks
							charSheet.skill_doubling_bonus[90] += 2
							charSheet.skill_doubling_bonus[94] += 2
							break
						}
						case "Master Craftsman": { // 5 ranks in any Craft or Profession skill You can craft magic items without being a spellcaster
							charSheet.master_craftsman_feat = charSheet.getValue("feats." + i + ".detail")
							break
						}
						case "Persuasive": { // — +2 bonus on Diplomacy and Intimidate checks
							charSheet.skill_doubling_bonus[27] += 2
							charSheet.skill_doubling_bonus[34] += 2
							break
						}
						case "Stealthy": { // You get a +2 bonus on all Escape Artist and Stealth skill checks.
							charSheet.skill_doubling_bonus[30] += 2
							charSheet.skill_doubling_bonus[91] += 2
							break
						}
						case "Self-Sufficient": { // — +2 bonus on Heal and Survival checks
							charSheet.skill_doubling_bonus[33] += 2
							charSheet.skill_doubling_bonus[92] += 2
							break
						}
						case "Skill Focus": { // — +3 bonus on one skill (+6 at 10 ranks)
							charSheet.skill_focus_feat = charSheet.getValue("feats." + i + ".detail")
							break
						}

						// other - saving throws
						case "Iron Will": { // +2 bonus on Will saves
							charSheet.will_feats += 2
							break
						}
						case "Lightning Reflexes": { // +2 bonus on Reflex saves
							charSheet.ref_feats += 2
							break
						}

						// other - extra
						case "Extra Channel": {
							charSheet.sa_cp_feats += 2
							break
						}
						case "Extra Ki": { //  Ki pool class feature Increase your ki pool by 2 points
							charSheet.sa_kp_feats += 2
							break
						}
						case "Extra Lay On Hands": { //  Lay on hands class feature Use lay on hands two additional times per day
							charSheet.sa_lohp_feats += 2
							break
						}
						case "Extra Mercy": { //  Mercy class feature Your lay on hands benefits from one additional mercy
							charSheet.sa_mercy_feats += ", " + charSheet.getValue("feats." + i + ".detail")
							break
						}
						case "Extra Performance": { //  Bardic performance class feature Use bardic performance for 6 additional rounds per day
							charSheet.sa_pp_feats += 6
							break
						}
						case "Extra Rage": { //  Rage class feature Use rage for 6 additional rounds per day
							charSheet.sa_rp_feats += 6
							break
						}

						// other - speed
						case "Fleet": { // Your base speed increases by 5 feet
							charSheet.spd_bonus_lgt_l_lgt_a += 5
							break
						}
						case "Great Fortitude": { // +2 on Fortitude saves
							charSheet.fort_feats += 2
							break
						}

						// other
						case "Improved Channel": { // Channel energy class feature +2 bonus on channel energy DC
							charSheet.sa_channel_dc_feats += 2
							break
						}
						case "Toughness": { // — +3 hit points, +1 per Hit Die beyond 3
							charSheet.hp_feats += Math.max(3, charSheet.getValue("total_level"))
							break
						}

						// other - to do
	//				case "Acrobatic Steps": break; // Dex 15, Nimble Moves Ignore 20 feet of difficult terrain when you move
	//				case "Alignment Channel": break; // Channel energy class feature Channel energy can heal or harm outsiders
	//				case "Arcane Armor Training": break; // * Armor Proficiency, Light, caster level 3rd Reduce your arcane spell failure chance by 10%
	//				case "Arcane Armor Mastery": break; // * Arcane Armor Training, Reduce your arcane spell failure chance by 20%
	//				case "Arcane Strike": break; // * Ability to cast arcane spells +1 damage and weapons are considered magic
	//				case "Armor Proficiency, Light": break; //  — No penalties on attack rolls while wearing light armor
	//				case "Armor Proficiency, Medium": break; //  Armor Proficiency, Light No penalties on attack rolls while wearing medium armor
	//				case "Armor Proficiency, Heavy": break; //  Armor Proficiency, Medium No penalties on attack rolls while wearing heavy armor
	//				case "Augment Summoning": break; //  Spell Focus (conjuration) Summoned creatures gain +4 Str and Con
	//				case "Blind-Fight": break; // * — Reroll miss chances for concealment
	//				case "Improved Bull Rush": break; //* Power Attack +2 bonus on bull rush attempts, no attack of opportunity
	//				case "Greater Bull Rush": break; //* Improved Bull Rush, base attack bonus +6 Enemies you bull rush provoke attacks of opportunity
	//				case "Catch Off-Guard": break; // * — No penalties for improvised melee weapons
	//				case "Channel Smite": break; // * Channel energy class feature Channel energy through your attack
	//				case "Cleave": break; //* Power Attack Make an additional attack if the first one hits
	//				case "Great Cleave": break; //* Cleave, base attack bonus +4 Make an additional attack after each attack hits
	//				case "Combat Casting": break; //  — +4 bonus on concentration checks for defensive casting
	//				case "Combat Reflexes": break; // * Make additional attacks of opportunity
	//				case "Command Undead": break; //  Channel negative energy class feature Channel energy can be used to control undead
	//				case "Critical Focus": break; // * Base attack bonus +9 +4 bonus on attack rolls made to confirm critical hits
	//				case "Bleeding Critical": break; // * Critical Focus, base attack bonus +11 Whenever you score a critical hit, the target takes 2d6 bleed
	//				case "Blinding Critical": break; // * Critical Focus, base attack bonus +15 Whenever you score a critical hit, the target is blinded
	//				case "Deafening Critical": break; // * Critical Focus, base attack bonus +13 Whenever you score a critical hit, the target is deafened
	//				case "Sickening Critical": break; // * Critical Focus, base attack bonus +11 Whenever you score a critical hit, the target is sickened
	//				case "Staggering Critical": break; // * Critical Focus, base attack bonus +13 Whenever you score a critical hit, the target is staggered
	//				case "Stunning Critical": break; // * Staggering Critical, base attack bonus +17 Whenever you score a critical hit, the target is stunned
	//				case "Tiring Critical": break; // * Critical Focus, base attack bonus +13 Whenever you score a critical hit, the target is fatigued
	//				case "Exhausting Critical": break; // * Tiring Critical, base attack bonus +15 Whenever you score a critical hit, the target is exhausted
	//				case "Critical Mastery": break; // * Any two critical feats, 14th-level fighter Apply two effects to your critical hits
	//				case "Dazzling Display": break; //* Weapon Focus Intimidate all foes within 30 feet
	//				case "Improved Disarm": break; // * Combat Expertise +2 bonus on disarm attempts, no attack of opportunity
	//				case "Greater Disarm": break; // * Improved Disarm, base attack bonus +6 Disarmed weapons are knocked away from your enemy
	//				case "Deadly Stroke": break; //* Greater Weapon Focus, Shatter Defenses, Deal double damage plus 1 Con bleed base attack bonus +11
	//				case "Deflect Arrows": break; //* Dex 13, Improved Unarmed Strike Avoid one ranged attack per round
	//				case "Diehard": break; //  Endurance Automatically stabilize and remain conscious below 0 hp
	//				case "Disruptive": break; // * 6th-level fighter Increases the DC to cast spells adjacent to you
	//				case "Spellbreaker": break; // * Disruptive, 10th-level fighter Enemies provoke attacks if their spells fail
	//				case "Elemental Channel": break; //  Channel energy class feature Channel energy can harm or heal elementals
	//				case "Endurance": break; //  — +4 bonus on checks to avoid nonlethal damage
	//				case "Eschew Materials": break; //  — Cast spells without material components
	//				case "Exotic Weapon Proficiency": break; // * Base attack bonus +1 No penalty on attacks made with one exotic weapon
	//				case "Far Shot": break; //* Point-Blank Shot Decrease ranged penalties by half
	//				case "Improved Feint": break; // * Combat Expertise Feint as a move action
	//				case "Greater Feint": break; // * Improved Feint, base attack bonus +6 Enemies you feint lose their Dex bonus for 1 round
	//					case "Improved Grapple": break; //* Dex 13, Improved Unarmed Strike +2 bonus on grapple attempts, no attack of opportunity
	//					case "Greater Grapple": break; //* Improved Grapple, base attack bonus +6 Maintain your grapple as a move action
	//					case "Improved Great Fortitude": break; // Great Fortitude Once per day, you may reroll a Fortitude save
	//					case "Improved Counterspell": break; // — Counterspell with spell of the same school
	//					case "Improved Familiar": break; // Ability to acquire a familiar, see feat Gain a more powerful familiar
	//					case "Scorpion Style": break; //* Improved Unarmed Strike Reduce target’s speed to 5 ft.
	//					case "Gorgon’s Fist": break; //* Scorpion Style, base attack bonus +6 Stagger a foe whose speed is reduced
	//					case "Medusa’s Wrath": break; //* Gorgon’s Fist, base attack bonus +11 Make 2 extra attacks against a hindered foe
	//					case "Stunning Fist": break; //* Dex 13, Wis 13, Improved Unarmed Strike, base attack bonus +8 Stun opponent with an unarmed strike
	//					case "Improvised Weapon Mastery": break; //* Catch Off-Guard or Throw Anything, base attack bonus +8 Make an improvised weapon deadly
	//					case "Improved Iron Will": break; // Iron Will Once per day, you may reroll a Will save
	//					case "Leadership": break; // Character level 7th Gain a cohort and followers
	//					case "Lunge": break; //* Base attack bonus +6 Take a –2 penalty to your AC to attack with reach
	//					case "Improved Lightning Reflexes": break; // Lightning Reflexes Once per day, you may reroll a Reflex save
	//					case "Lightning Stance": break; // * Dex 17, Wind Stance, base attack bonus +11 Gain 50% concealment if you move
	//					case "Martial Weapon Proficiency": break; // — No penalty on attacks made with one martial weapon
	//					case "Manyshot": break; //* Dex 17, Rapid Shot, base attack bonus +6 Shoot two arrows simultaneously
	//					case "Moblity": break; // * Dodge +4 AC against attacks of opportunity from movement
	//					case "Natural Spell": break; // Wis 13, wild shape class feature Cast spells while using wild shape
	//					case "Nimble Moves": break; // Dex 13 Ignore 5 feet of difficult terrain when you move
	//					case "Improved Overrun": break; //* Power Attack +2 bonus on overrun attempts, no attack of opportunity
	//					case "Greater Overrun": break; //* Improved Overrun, base attack bonus +6 Enemies you overrun provoke attacks of opportunity
	//					case "Penetrating Strike": break; //* Weapon Focus, 12th-level fighter Your attacks ignore 5 points of damage reduction
	//					case "Greater Penetrating Strike": break; //* Penetrating Strike, 16th-level fighter Your attacks ignore 10 points of damage reduction
	//					case "Pinpoint Targeting": break; //* Improved Precise Shot, base attack bonus +16 No armor or shield bonus on one ranged attack
	//					case "Precise Shot": break; //* Point-Blank Shot No penalty for shooting into melee
	//					case "Improved Precise Shot": break; //* Dex 19, Precise Shot, base attack bonus +11 No cover or concealment chance on ranged attacks
	//					case "Quick Draw": break; //* Base attack bonus +1 Draw weapon as a free action
	//					case "Ride-By Attack": break; //* Mounted Combat Move before and after a charge attack while mounted
	//					case "Run": break; // — Run at 5 times your normal speed
	//					case "Selective Channeling": break; // Cha 13, channel energy class feature Choose whom to affect with channel energy
	//					case "Shatter Defenses": break; //* Dazzling Display, base attack bonus +6 Hindered foes are flat-footed
	//					case "Shield Proficiency": break; // — No penalties on attack rolls when using a shield
	//					case "Tower Shield Proficiency": break; //* Shield Proficiency No penalties on attack rolls when using a tower shield
	//					case "Improved Shield Bash": break; //* Shield Proficiency Keep your shield bonus when shield bashing
	//					case "Shield Slam": break; //* Improved Shield Bash, Two-Weapon Fighting, Free bull rush with a bash attack base attack bonus +6
	//					case "Shield Master": break; //* Shield Slam, base attack bonus +11 No two-weapon penalties when attacking with a shield
	//					case "Greater Shield Focus": break; //* Shield Focus, 8th-level fighter Gain a +1 bonus to your AC when using a shield
	//					case "Shot on the Run": break; //* Dex 13, Mobility, Point-Blank Shot, base attack bonus +4 ake ranged attack at any point during movement
	//					case "Snatch Arrows": break; //* Dex 15, Deflect Arrows Catch one ranged attack per round
	//					case "Spirited Charge": break; //* Ride-By Attack Double damage on a mounted charge
	//					case "Spring Attack": break; // * Mobility, base attack bonus +4 Move before and after melee attack
	//					case "Stand Still": break; // * Combat Reflexes Stop enemies from moving past you
	//					case "Greater Spell Focus": break; // Spell Focus +1 bonus on save DCs for one school
	//					case "Spell Focus": break; // — +1 bonus on save DCs for one school
	//					case "Spell Mastery": break; // 1st-level Wizard Prepare some spells without a spellbook
	//					case "Greater Spell Penetration": break; // Spell Penetration +2 bonus on level checks to beat spell resistance
	//					case "Spell Penetration": break; // — +2 bonus on level checks to beat spell resistance
	//					case "Step Up": break; //* Base attack bonus +1 Take a 5-foot step as an immediate action
	//					case "Strike Back": break; //* Base attack bonus +11 Attack foes that strike you while using reach
	//					case "Improved Sunder": break; //* Power Attack +2 bonus on sunder attempts, no attack of opportunity
	//					case "Greater Sunder": break; //* Improved Sunder, base attack bonus +6 Damage from sunder attempts transfers to your enemy
	//					case "Throw Anything": break; //* — No penalties for improvised ranged weapons
	//					case "Trample": break; //* Mounted Combat Overrun targets while mounted
	//					case "Improved Trip": break; // * Combat Expertise +2 bonus on trip attempts, no attack of opportunity
	//					case "Greater Trip": break; // * Improved Trip, base attack bonus +6 Enemies you trip provoke attacks of opportunity
	//					case "Turn Undead": break; // Channel positive energy class feature Channel energy can be used to make undead flee
	//					case "Two-Weapon Rend": break; //* Double Slice, Improved Two-Weapon Fighting, base attack bonus +11  Rend a foe hit by both your weapons
	//					case "Unseat": break; //* Improved Bull Rush, Mounted Combat Knock opponents from their mounts
	//					case "Vital Strike": break; //* Base attack bonus +6 Deal twice the normal damage on a single attack
	//					case "Improved Vital Strike": break; //* Vital Strike, base attack bonus +11 Deal three times the normal damage on a single attack
	//					case "Greater Vital Strike": break; //* Improved Vital Strike, base attack bonus +16 Deal four times the normal damage on a single attack
	//					case "Whirlwind Attack": break; // * Dex 13, Combat Expertise, Spring Attack, Make one melee attack against all foes within reach base attack bonus +4
	//					case "Wind Stance": break; // * Dex 15, Dodge, base attack bonus +6 Gain 20% concealment if you move

	/*
						case "Brew Potion": break; // Caster level 3rd Create magic potions
						case "Craft Magic Arms and Armor": break; // Caster level 5th Create magic armors, shields, and weapons
						case "Craft Rod": break; // Caster level 9th Create magic rods
						case "Craft Staff": break; // Caster level 11th Create magic staves
						case "Craft Wand": break; // Caster level 5th Create magic wands
						case "Craft Wondrous Item": break; // Caster level 3rd Create magic wondrous items
						case "Forge Ring": break; // Caster level 7th Create magic rings
						case "Scribe Scroll": break; // Caster level 1st Create magic scrolls
						case "Empower Spell": break; // — Increase spell variables by 50%
						case "Enlarge Spell": break; // — Double spell range
						case "Extend Spell": break; // — Double spell duration
						case "Heighten Spell": break; // — Treat spell as a higher level
						case "Maximize Spell": break; // — Maximize spell variables
						case "Quicken Spell": break; // — Cast spell as a swift action
						case "Silent Spell": break; // — Cast spell without verbal components
						case "Still Spell": break; // — Cast spell without somatic components
						case "Widen Spell": break; // — Double spell area
	*/
					}
				}
			}

			// recalculate sections
			$('#calcInit').text(10 + charSheet.bonus(charSheet.getValue("dex")) + charSheet.getValue("init_feats"))
			charSheet.sq_build()
			calcSkills()
			calcDefense()
			calcOffense()

			console.log(" ... CHECK")
		}

		// calculate skills subsection
		function calcSkills()
		{
			console.log("function calcSkills()")

			// set default values for skills
			charSheet.total_sp_used = 0
			charSheet.lp_skills = 0
			charSheet.perception = 10 + 1 * charSheet.bonus(charSheet.getValue("wis")) + charSheet.getNumber("skill_trait_bonus.46") + charSheet.getNumber("skill_feat_bonus.46") + charSheet.getNumber("skill_doubling_bonus.46")

			// loop through each skill
			if(charSheet.getValue("skills.0.name")) {
				for(i = 0; i < charSheet.skills.length; i++) {
					var tmpAbility = charSheet.getValue("skills." + i + ".ability").toLowerCase();
					charSheet.total_sp_used += charSheet.getNumber("skills." + i + ".rank")
					charSheet.skills[i].total = 10 + charSheet.getNumber("skills." + i +".rank") + charSheet.bonus(charSheet.getValue(tmpAbility)) + charSheet.getNumber("skill_trait_bonus." + charSheet.getValue("skills." + i + ".skill_id")) + charSheet.getNumber("skill_feat_bonus." + charSheet.getValue("skills." + i + ".skill_id")) + charSheet.getNumber("skill_doubling_bonus." + charSheet.getValue("skills." + i + ".skill_id"))

					// feat bonuses double at 10 ranks
					if(charSheet.getValue("skills." + i + ".rank") > 9) {
						charSheet.skills[i].total += charSheet.getNumber("skill_doubling_bonus." + charSheet.getNumber("skills." + i + ".skill_id"))
					}

					// +3 to trained class skills
					temp = 0
					for(j = 0; j < charSheet.getValue("total_classes").length; j++) {
						if(charSheet.getValue("skills." + i + ".class").toLowerCase().indexOf(charSheet.getValue("total_classes." + j).toLowerCase()) > -1) {
							temp = 1
						}
						if(charSheet.getValue("skill_education") && charSheet.getValue("skills." + i + ".name").substr(0, 4) == "Know") {
							temp = 1
						}
					}
					if(temp && charSheet.getValue("skills." + i + ".rank") > 0) {
						charSheet.skills[i].total += 3
					}

					// class features
					if(charSheet.getValue("skills." + i + ".name").substr(0, 4) == "Know") { // bardic knowledge
						charSheet.skills[i].total += 1 * charSheet.getValue("skill_bard_know")
					}

					// feats
					if(charSheet.getValue("master_craftsman_feat") && charSheet.getValue("skills." + i + ".name").toLowerCase().indexOf(charSheet.getValue("master_craftsman_feat").toLowerCase) > -1) { // Master Craftsman
						charSheet.skills[i].total += 2
					}
					if(charSheet.getValue("skills." + i + ".name") == charSheet.getValue("skill_focus_feat")) { // Skill Focus
						charSheet.skills[i].total += 3
						if(charSheet.getValue("skills." + i + ".rank") > 9) {
							charSheet.skills[i].total += 3
						}
					}
					if(charSheet.getValue("skills." + i + ".name") == charSheet.getValue("skill_artist_feat")) { // Artist
						charSheet.skills[i].total += 2
					}

					// note important skills
					if(charSheet.getValue("skills." + i + ".name") == "Perception") {
						charSheet.perception = 1 * charSheet.getValue("skills." + i + ".total")
					}
					if(charSheet.getValue("skills." + i + ".name") == "Linguistics") {
						charSheet.lp_skills += 1 * charSheet.getValue("skills." + i + ".rank")
					}

					// armour check penalty from armour and encumbrance applies to str and dex skills
					if(tmpAbility == "str" || tmpAbility == "dex") {
						// calculate skill check penalty due to encumbrance
						var tmpLoadCheckPenalty = charSheet.bonus(charSheet.getValue(tmpAbility)) - charSheet.bonus(charSheet.getValue(tmpAbility) - charSheet.getNumber("loadType"))
						// apply whichever penalty is greater
						if(tmpLoadCheckPenalty > charSheet.getValue("armour.armour_penalty")) {
							charSheet.skills[i].total -= tmpLoadCheckPenalty
						}
						else {
							charSheet.skills[i].total -= charSheet.getValue("armour.armour_penalty")
						}
					}
	
					// update read/edit sections
					$('#spantotal' + charSheet.getValue("skills." + i + ".id")).text(charSheet.getValue("skills." + i + ".total"))
					//$('#total' + charSheet.getValue("skills." + i + ".id")).value = charSheet.getValue("skills." + i + ".total")
				}
			}

			// recalculate sections
			charSheet.sp_build()
			charSheet.senses_build()

			console.log(" ... CHECK")
		}

		// calculate languages subsection
		function calcLanguages()
		{
			console.log("function calcLanguages()")

			// set default values for languages
			charSheet.total_lp_used = 0
			var tmpIlliterateBonus = 0

			for(i = 0; i < charSheet.getValue("languages").length; i++) {
				if(charSheet.getValue("languages." + i + ".name")) {
					if (charSheet.getValue("languages." + i + ".name") == "Illiterate") {
						tmpIlliterateBonus = 1
					}
					else {
						charSheet.total_lp_used++
					}
				}
			}

			// TO DO: add racial trait for racial language
			charSheet.lp_desc  = "(" + charSheet.getNumber("total_lp_used") + " / " + (2 + tmpIlliterateBonus + charSheet.bonus(charSheet.getNumber("int")) + charSheet.getNumber("lp_skills") + charSheet.getNumber("lp_traits") + charSheet.getNumber("lp_features")) + ") "
			charSheet.lp_desc += "(2"
			if(tmpIlliterateBonus) {
				charSheet.lp_desc += " + 1 illiterate allowance"
			}
			if(charSheet.bonus(charSheet.getValue("int"))) {
				charSheet.lp_desc += " +" + charSheet.bonus(charSheet.getValue("int")) + " Int"
			}
			if(charSheet.getValue("lp_skills")) {
				charSheet.lp_desc += " +" + charSheet.getValue("lp_skills") + " Linguistics"
			}
			if(charSheet.getValue("lp_traits")) {
				charSheet.lp_desc += " +" + charSheet.getValue("lp_traits") + " traits"
			}
			if(charSheet.getValue("lp_features")) {
				charSheet.lp_desc += " +" + charSheet.getValue("lp_features") + " features"
			}
			charSheet.lp_desc += ")"

			$('#calcLPDesc').text(charSheet.getValue("lp_desc"))

			console.log(" ... CHECK")
		}

		// calculate special abilities section
		function calcSpecialabilities()
		{
				console.log("calcSpecialabilities ... ")
			// set default values for racial traits
			charSheet.size = "Medium"
			charSheet.baseSpeed = 30
			charSheet.skill_bard_know = 0
			charSheet.base_speed_traits = 0
			charSheet.movement = ""
			charSheet.noSpeedRestriction = charSheet.spd_armour_training = 0
			charSheet.fort_traits = charSheet.ref_traits = charSheet.will_traits = 0
			charSheet.senses_traits = ""
			charSheet.sa_traits = charSheet.spell_like_traits = ""
			charSheet.save_traits = charSheet.sd_traits = charSheet.immune_traits = charSheet.resist_traits = ""
			charSheet.sp_traits = charSheet.fp_traits = charSheet.lp_traits = 0
			charSheet.outputRacialTraits = charSheet.outputSkillOptions = ""
			charSheet.fp_features = charSheet.lp_features = 0
			charSheet.base_spd_bonus_med_l_med_a = 0
			charSheet.spd_bonus_lgt_l_no_a = 0
			charSheet.ac_uncanny_dodge = charSheet.sd_trap_sense = 0
			charSheet.ac_damage_reduction = charSheet.ac_damage_reduction_cold_iron = charSheet.ac_damage_reduction_chaotic = charSheet.ac_damage_reduction_evil = charSheet.ac_damage_reduction_lethal = 0
			charSheet.ac_bonus_wis = charSheet.ac_bonus_monk = charSheet.ac_evasion = 0
			charSheet.ac_natural_armour = 0
			charSheet.sa_rage = charSheet.sa_rp = 0
			charSheet.sa_unarmed = charSheet.sa_flurry = charSheet.sd_maneuver_training = charSheet.sa_quivering_palm = 0
			charSheet.sa_kp = 0
			charSheet.sq_features = charSheet.sd_features = charSheet.sa_features = ""
			charSheet.sa_rage_powers = charSheet.outputRagePowers = ""
			charSheet.sa_pp = 0
			charSheet.sa_jack_of_all_trades = charSheet.sa_slow_fall = 0
			charSheet.sa_bard_perf = charSheet.sa_versatile_perf = charSheet.outputVersatilePerf = charSheet.outputAnimalCompanion = ""
			charSheet.sa_channel = charSheet.outputChannelEnergy = charSheet.outputDomains = ""
			charSheet.sa_domain_powers = charSheet.sa_animal_companion = ""
			charSheet.sa_domains = []
			charSheet.sa_quarry = 0
			charSheet.sa_weapon_group = {}
			charSheet.outputWeaponGroup = charSheet.sa_weapon_mastery = charSheet.outputWeaponMastery = ""
			charSheet.sa_two_weapon = 0
			charSheet.sq_mercies = charSheet.outputMercies = charSheet.outputDivineBond = ""
			charSheet.sa_favoured_enemy = {}
			charSheet.sq_favoured_terrain = {}
			charSheet.outputFavouredEnemy = charSheet.outputFavouredTerrain = ""
			charSheet.outputCombatStyle = charSheet.outputHuntersBond = ""
			charSheet.sq_rogue_talents = charSheet.outputRogueTalents = ""
			charSheet.sa_lohp = 0
			charSheet.sa_bloodline = charSheet.outputBloodline = charSheet.sa_bloodline_notes = charSheet.outputBloodlinePowers = charSheet.outputBloodlineClassSkill = ""
			charSheet.added_class_skills = []
			charSheet.outputArcaneBond = charSheet.outputArcaneSchool = charSheet.outputOppositionSchool = ""
			charSheet.outputSchoolPower1 = charSheet.outputSchoolPower2 = charSheet.outputSchoolPower3 = ""
			charSheet.sa_arcane_school = charSheet.sa_arcane_school_notes = ""
			charSheet.sa_opposition_schools = []
			charSheet.outputNewArcana = charSheet.sa_new_arcana = charSheet.outputSchoolPower = charSheet.sa_school_power = ""
			charSheet.outputDragonType = charSheet.sa_dragon_type = charSheet.outputElementType = charSheet.sa_element_type = ""
			for(i = 0; i < 150; i++) {
				charSheet.skill_trait_bonus[i] = 0
				if(charSheet.getValue("skill_list." +i)) {
					charSheet.outputSkillOptions += "<option value=\"" + i + "\">" + charSheet.getValue("skill_list." + i) + "</option>"
				}
			}

			// loop through each racial trait
			for(i = 0, j = ""; i < charSheet.getValue("racial_traits").length; i++) {
				if(charSheet.getValue("racial_traits." + i)) {
					switch(charSheet.getValue("racial_traits." + i + ".name")) {
				// main
					// size
						case "Small": {
							charSheet.size = "Small"
							break
						}
					// senses
						case "Darkvision (60 ft.)": {
							charSheet.senses_traits += "; darkvision (60 ft.)"
							break
						}
						case "Darkvision (120 ft.)": {
							charSheet.senses_traits += "; darkvision (120 ft.)"
							break
						}
						case "Low-Light Vision": {
							charSheet.senses_traits += "; low-light vision"
							break
						}
						case "Light Sensitivity": {
							charSheet.senses_traits += "; light sensitivity"
							break
						}
				// defense
					// saving throws
						case "Hobbit Luck": {
							charSheet.fort_traits += 1
							charSheet.ref_traits += 1
							charSheet.will_traits += 1
							break
						}
						case "Fortunate": {
							charSheet.fort_traits += 2
							charSheet.ref_traits += 2
							charSheet.will_traits += 2
							break
						}
						case "Defensive Training": {
							charSheet.sd_traits += "; defensive training (+4 dodge bonus to AC vs. giants)"
							break
						}
						case "Illusion Resistance": {
							charSheet.save_traits += ", +2 vs. illusions"
							break
						}
						case "Hardy": {
							charSheet.save_traits += ", +2 vs. poison, spells, and spell-like abilities"
							break
						}
					// sd
						case "Stability": {
							charSheet.sd_traits += ", stability (+4 vs. bull rush and trip)"
							break
						}
					// immune
						case "Elven Immunities": {
							charSheet.immune_traits += ", sleep"
							charSheet.save_traits += ", +2 vs. enchantments"
							break
						}
				// offense
					// speed
						case "Normal Speed": break;
						case "Slow and Steady": {
							charSheet.baseSpeed = 20
							charSheet.noSpeedRestriction = 1
							break
						}
						case "Slow Speed": {
							charSheet.baseSpeed = 20
							break
						}
					// sa
						case "Gnome Magic": {
							if(charSheet.getValue("cha") > 10) {
								charSheet.spell_like_traits += "; dancing lights, ghost sounds, prestidigitation, speak with animals (1/day, CL " + charSheet.ordinal(charSheet.getValue("total_level")) + ")"
							}
							break
						}
						case "Gnome Hatred": {
							charSheet.sa_traits += "; +1 on attack roles against goblin and reptilian humanoids"
							break
						}
						case "Shield Dwarf Hatred": {
							charSheet.sa_traits += "; +1 on attack roles against goblinoid and orc humanoids"
							break
						}
				// statistics
					// feats
						case "Bonus Feat": {
							charSheet.fp_traits += 1
							break
						}
						case "Adaptability": break;
					// skills
						case "Skilled": {
							charSheet.sp_traits = charSheet.getValue("total_level")
							break
						}
						case "Small": { // +4 Stealth
							charSheet.skill_trait_bonus[91] += 4
							break
						}
						case "Greed": break;
						case "Sneaky": {
							charSheet.skill_trait_bonus[91] += 4
							break
						}
						case "Stonecunning": break;
						case "Keen Senses": { // +2 Perception
							charSheet.skill_trait_bonus[46] += 2
							break
						}
						case "Sure-Footed": { // +2 Acrobatics, +2 Climb
							charSheet.skill_trait_bonus[1] += 2
							charSheet.skill_trait_bonus[5] += 2
							break
						}
						case "Svirfneblin Skill": { // +2 Stealth, +2 Craft (alchemy), +2 Perception
							charSheet.skill_trait_bonus[91] += 2
							charSheet.skill_trait_bonus[6] += 2
							charSheet.skill_trait_bonus[46] += 2
							break
						}
						case "Intimidating": { // +2 Intimidate
							charSheet.skill_trait_bonus[34] += 2
							break
						}
						case "Obsessive": {
							charSheet.skill_trait_bonus[charSheet.racial_traits[i].detail] += 2
							charSheet.outputRacialTraits += "<br>Obsession <select id=\"racialtrait" + charSheet.racial_traits[i].id + "\"><option value=\"" + charSheet.racial_traits[i].detail + "\">" + charSheet.skill_list[charSheet.racial_traits[i].detail] + "</option><option value=\"\">--------------------</option>"
//							charSheet.outputRacialTraits += charSheet.outputCraftOptions
//							charSheet.outputRacialTraits += charSheet.outputProfOptions
// TO DO: Add skill options
							charSheet.outputRacialTraits += "</select>"
							break
						}
					// languages
						case "Racial Language": {
							charSheet.lp_traits++
							break
						}
					// sq
						case "Elven Magic": {
							charSheet.sq_features += ", elven magic"
							break
						}
					}
					j += charSheet.racial_traits[i].name + ", "
				}
			}
			j = j.substr(0, j.length - 2)
			charSheet.speed_build()
			$('#calcSize').text(charSheet.size)
			$('#calcRacialTraits').html(j)
			if(charSheet.outputRacialTraits) {
				$('#editRacialTraits').html(charSheet.outputRacialTraits)
			}

			// build spell options
			if(charSheet.spell_list) {
				for(j in charSheet.spell_list) {
					if(charSheet.spell_list[j]) {
						for(i = 0; i < 10; i++) {
							charSheet['sa_' + i + '_' + j + '_known'] = charSheet['output' + i + j + 'Known'] = charSheet['output' + i + j + 'List'] = ""
							for(var key in charSheet.spell_list[j][i]) {
								charSheet['output' + i + j + 'List'] += "<option value=\"" + key + "\">" + charSheet.spell_list[j][i][key] + "</option>"
							}
						}
					}
				}
			}

			// loop through each class feature
			for(i = 0; i < charSheet.getValue("class_features").length; i++) {
				if(charSheet.class_features[i]) {
					cfid = charSheet.getValue("class_features." + i + ".class_feature_id")
					cfDetail = charSheet.getValue("class_features." + i + ".detail")
					tmpSpellClass = ""
					tmpSpellLevel = -2
	
					// handle non-spell class features
					if(tmpSpellLevel == -2) {
						switch(cfid) {
					// ignore
							case "1":   break; // Weapon and Armor Proficiency
							case "10":  break; // Indomitable Will (Ex), BBN
							case "11":  break; // Tireless Rage (Ex), BBN
							case "14":  break; // Spells
							case "30":  break; // Cantrips, BRD // Bards learn a number of cantrips, or 0-level spells. These spells are cast like any other spell, but they do not consume any slots and may be used again.'
							case "53":  break; // Orisons
							case "74":  break; // Bonus Languages, CLR // A cleric's bonus language options include Celestial, Abyssal, and Infernal.
							case "32":  break; // Well-Versed (Ex), BRD
					// defensive
						// ac
							case "108": { // AC Bonus (Ex)
								if(charSheet.getValue("wis") > 11) {
									charSheet.ac_bonus_wis += charSheet.bonus(charSheet.getValue("wis"))
								}
								charSheet.ac_bonus_monk = Math.floor((charSheet.getValue("class_level.MNK")) / 4)
								break
							}
						// cmd
							case "115": { // Maneuver Training (Ex)
								charSheet.sd_maneuver_training++
								break
							}
						// saving throws
							case "85": { // Resist Nature's Lure (Ex)
								charSheet.save_traits += ", +4 vs. fey and plant-targeted effects"
								break
							}
							case "102": { // Bravery (Ex)
								charSheet.save_traits += ", +" + Math.floor((1 * charSheet.getValue("class_level.FTR") + 2) / 4) + " vs. fear"
								break
							}
							case "116": { // Still Mind (Ex)
								charSheet.save_traits += ", +2 vs. enchantments"
								break
							}
							case "135": { // Divine Grace (Su)
								charSheet.sq_features += ", divine grace"
								if(charSheet.getValue("cha") > 11) {
									charSheet.fort_traits += charSheet.bonus(charSheet.getValue("cha"))
									charSheet.ref_traits += charSheet.bonus(charSheet.getValue("cha"))
									charSheet.will_traits += charSheet.bonus(charSheet.getValue("cha"))
								}
								break
							}
						// defensive abilities
							case "5": { // Uncanny Dodge (Ex)
								charSheet.ac_uncanny_dodge += 1
								break
							}
							case "6": { // Trap Sense (Ex)
								charSheet.sd_trap_sense = 0
								if(charSheet.getValue("class_level.BBN")) {
									charSheet.sd_trap_sense += Math.floor(charSheet.getValue("class_level.BBN") / 3)
								}
								if(charSheet.getValue("class_level.ROG")) {
									charSheet.sd_trap_sense += Math.floor(charSheet.getValue("class_level.ROG") / 3)
								}
								break
							}
							case "7": { // Improved Uncanny Dodge (Ex)
								charSheet.ac_uncanny_dodge += 1
								break
							}
							case "113": { // Evasion (Ex)
								charSheet.ac_evasion++
								break
							}
							case "122": { // Improved Evasion (Ex)
								charSheet.ac_evasion++
								break
							}
							case "144": { // Aura of Justice (Su)
								charSheet.sd_features += ", aura of justice (10 ft.)"
								break
							}
							case "145": { // Aura of Faith (Su)
								charSheet.sd_features += ", aura of faith (10 ft.)"
								break
							}
							case "107": { // Camouflage (Ex)
								charSheet.sd_features += ", camouflage"
								break
							}
							case "111": { // Hide in Plain Sight (Ex)
								charSheet.sd_features += ", hide in plain sight"
								break
							}
						// dr
							case "8": { // Damage Reduction (Ex)
								charSheet.ac_damage_reduction += 1
								break
							}
							case "105": { // Armor Mastery (Ex)
								charSheet.ac_damage_reduction += 5
								break
							}
							case "130": { // Perfect Self
								charSheet.ac_damage_reduction_chaotic += 10
								break
							}
							case "147": { // Holy Champion (Su)
								charSheet.ac_damage_reduction_evil = 10
								break
							}
// TO DO :	// At 20th level, a monk becomes a magical creature. He is forevermore treated as an outsider rather than as a humanoid (or whatever the monk's creature type was) for the purpose of spells and magical effects. Additionally, the monk gains damage reduction 10/chaotic, which allows him to ignore the first 10 points of damage from any attack made by a nonchaotic weapon or by any natural attack made by a creature that doesn't have similar damage reduction. Unlike other outsiders, the monk can still be brought back from the dead as if he were a member of his previous creature type.
// TO DO:		// spell resistance
							case "125": { // spell resistance
								charSheet.save_traits += ", " + (10 + 1 * charSheet.getValue("class_level.MNK")) + " spell resistance"
								break
							}
// TO DO: resistance
						// immune
							case "87": { // Venom Immunity (Ex)
								charSheet.immune_traits += ", poison"
								break
							}
							case "120": { // immune to disease
								charSheet.immune_traits += ", disease"
								break
							}
							case "137": { // aura of courage
								charSheet.immune_traits += ", fear"
								charSheet.sd_features += ", aura of courage (10 ft.)"
								break
							}
							case "143": { // aura of resolve
								charSheet.immune_traits += ", charm"
								charSheet.sd_features += ", aura of resolve (10 ft.)"
								break
							}
							case "146": { // aura of righteousness
								charSheet.immune_traits += ", compulsion"
								charSheet.ac_damage_reduction_evil += 5
								charSheet.sd_features += ", aura of righteousness (10 ft.)"
								break
							}
					// offense
						// speed
							case "2": { // Fast Movement (Ex)
								charSheet.sq_features += ", fast movement"
								charSheet.base_spd_bonus_med_l_med_a += 10
								break
							}
							case "114": { // Fast Movement (Ex)
								if(charSheet.getValue("spd_bonus_lgt_l_no_a") == 0) {
									charSheet.sq_features += ", fast movement"
								}
								charSheet.spd_bonus_lgt_l_no_a += 10
								break
							}
						// sa
							case "3": { // rage
								charSheet.sa_rp = 2 * charSheet.getValue("class_level.BBN") + 2 + charSheet.bonus(charSheet.getValue("con"))
								break
							}
							case "4": { // Rage Powers (Ex)
								charSheet.outputRagePowers += dropdown_helper("classfeature" + charSheet.getValue("class_features." + i + ".id"), cfDetail, ['Animal Fury','Clear Mind','Fearless Rage','Guarded Stance','Increased DR','Internal Fortitude','Intimidating Glare','Knockback','Low-Light Vision','Mighty Swing','Moment of Clarity','Night Vision','No Escape','Powerful Blow','Quick Ref lexes','Raging Climber','Raging Leaper','Raging Swimmer','Renewed Vigor','Rolling Dodge','Roused Anger','Scent','Strength Surge','Superstition','Surprise Accuracy','Swift Foot','Terrifying Howl','Unexpected Strike'])
								charSheet.sa_rage_powers += ", " + cfDetail
								break
							}
							case "9": { // Greater Rage (Ex)
								charSheet.sa_rage = 1
								break
							}
							case "12": { // mighty rage
								charSheet.sa_rage = 2
								break
							}
							case "16": { // bardic performance
								charSheet.sa_pp = 2 * charSheet.getValue("class_level.BRD") + 2 + charSheet.bonus(charSheet.getValue("cha"))
								if(charSheet.getValue("class_level.BRD") > 12) {
									charSheet.sa_bard_perf = "swift action; "
								}
								else {
									if(charSheet.getValue("class_level.BRD") > 6) {
										charSheet.sa_bard_perf = "move action; "
									}
									else {
										charSheet.sa_bard_perf = "standard action; "
									}
								}
								break
							}
							case "17": { // countersong
								charSheet.sa_bard_perf += "countersong"
								break
							}
							case "18": { // distraction
								charSheet.sa_bard_perf += ", distraction"
								break
							}
							case "19": { // fascinate
								charSheet.sa_bard_perf += ", fascinate DC " + Math.floor(10 + charSheet.bonus(charSheet.getValue("cha")) + charSheet.getValue("class_level.BRD") / 2)
								break
							}
							case "20": { // inspire courage
								charSheet.sa_bard_perf += ", inspire courage +" + Math.floor(1 + (1 * charSheet.getValue("class_level.BRD") + 1) / 6)
								break
							}
							case "21": { // inspire competence
								charSheet.sa_bard_perf += ", inspire competence +" + Math.floor(1 + (1 * charSheet.getValue("class_level.BRD") + 1) / 4)
								break
							}
							case "22": { // suggestion
								charSheet.sa_bard_perf += ", suggestion DC " + Math.floor(10 + charSheet.bonus(charSheet.getValue("cha")) + charSheet.getValue("class_level.BRD") / 2)
								break
							}
							case "23": { // dirge of doom
								charSheet.sa_bard_perf += ", dirge of doom"
								break
							}
							case "24": { // inspire greatness
								charSheet.sa_bard_perf += ", inspire greatness " + Math.floor((charSheet.getValue("class_level.BRD") - 6) / 3) + " targets"
								break
							}
							case "25": { // soothing performance
								charSheet.sa_bard_perf += ", soothing performance"
								break
							}
							case "26": { // frightening tune
								charSheet.sa_bard_perf += ", frightening tune DC " + Math.floor(10 + charSheet.bonus(charSheet.getValue("cha")) + charSheet.getValue("class_level.BRD") / 2)
								break
							}
							case "27": { // inspire heroics
								charSheet.sa_bard_perf += ", inspire heroics " + Math.floor((charSheet.getValue("class_level.BRD") - 12) / 3) + " targets"
								break
							}
							case "28": { // mass suggestion
								charSheet.sa_bard_perf += ", mass suggestion"
								break
							}
							case "29": { // deadly performance
								charSheet.sa_bard_perf += ", deadly performance DC " + Math.floor(10 + charSheet.bonus(charSheet.getValue("cha")) + charSheet.getValue("class_level.BRD") / 2)
								break
							}
							case "117": { // ki pool
								charSheet.sa_kp = charSheet.getValue("class_level.MNK") / 2 + 1 * charSheet.bonus(charSheet.getValue("wis"))
								break
							}
							case "330": { // bloodline
								charSheet.outputBloodline += dropdown_helper("classfeature" + charSheet.getValue("class_features." + i + ".id"), cfDetail, ['Aberrant','Abyssal','Arcane','Celestial','Destined','Draconic','Elemental','Fey','Infernal','Undead'])
								charSheet.sa_bloodline = cfDetail
								sorLevel = 1 * charSheet.getValue("class_level.SOR")
								if(cfDetail == "Aberrant") {
									charSheet.added_class_skills.push("Knowledge (dungeoneering)")
									charSheet.sa_bloodline_notes += ", +50% duration for polymorph spells"
									charSheet.sa_bloodline_notes += ", acidic ray " + (3 + 1 * charSheet.bonus(charSheet.getValue("cha"))) + "/day (1d6+" + Math.floor(sorLevel / 2) + ")"
									if(sorLevel > 2) {
										charSheet.sa_bloodline_notes += ", long limbs (+" + Math.max(5, Math.floor((sorLevel + 1) / 6) * 5) + " ft. range melee touch attacks)"
									}
									if(sorLevel > 8 && sorLevel < 20) {
										charSheet.sa_bloodline_notes += ", unusual anatomy (" + (Math.floor(sorLevel / 13) * 25 + 25) + "% chance ignore critical hit or sneak attack)"
									}
									if(sorLevel > 14) {
										charSheet.sa_bloodline_notes += ", alien resistance (SR " + (10 + sorLevel)+ ")"
									}
									if(sorLevel == 20) {
										charSheet.sa_bloodline_notes += ", aberrant form"
										charSheet.immune_traits += ", critical hits, sneak attacks"
										charSheet.senses_traits += "; blindsight (60 ft.)"
										charSheet.ac_damage_reduction = Math.max(charSheet.ac_damage_reduction, 5)
									}
								}
								if(cfDetail == "Abyssal") {
									charSheet.added_class_skills.push("Knowledge (planes)")
									charSheet.sa_bloodline_notes += ", summoned creatures gain DR " + Math.max(1, Math.floor(sorLevel / 2)) + "/--"
									charSheet.sa_bloodline_notes += ", "
									if(sorLevel > 10) {
										charSheet.sa_bloodline_notes += "flaming "
									}
									else if(sorLevel > 4) {
										charSheet.sa_bloodline_notes += "magic "
									}
									charSheet.sa_bloodline_notes += " claws " + (3 + 1 * charSheet.bonus(charSheet.getValue("cha"))) + "/day ("
									if(sorLevel < 7) {
										charSheet.sa_bloodline_notes += charSheet.dice("5")
									}
									else {
										charSheet.sa_bloodline_notes += charSheet.dice("7")
									}
									if(charSheet.bonus(charSheet.getValue("str"), 1)) {
										charSheet.sa_bloodline_notes += "+" + charSheet.bonus(charSheet.getValue("str"), 1)
									}
									if(sorLevel > 10) {
										charSheet.sa_bloodline_notes += "+1d6"
									}
									charSheet.sa_bloodline_notes += ")"
									if(sorLevel > 2 && sorLevel < 20) {
										charSheet.sa_bloodline_notes += ", demon resistance"
										if(sorLevel < 9) {
											charSheet.resist_traits += ", electricity 5"
										}
										else {
											charSheet.resist_traits += ", electricity 10"
										}
										if(sorLevel < 9) {
											charSheet.save_traits += ", +2 vs. poison"
										}
										else {
											charSheet.save_traits += ", +4 vs. poison"
										}
									}
									if(sorLevel > 8) {
										charSheet.sa_bloodline_notes += ", strength of the abyss +" + (Math.floor((sorLevel - 1)/ 6) * 2)
									}
									if(sorLevel > 14) {
										charSheet.sa_bloodline_notes += ", added summonings"
									}
									if(sorLevel == 20) {
										charSheet.sa_bloodline_notes += ", demonic might (telepathy 60 ft.)"
										charSheet.immune_traits += ", electricity, poison"
										charSheet.resist_traits += ", acid 10, cold 10, fire 10"
									}
								}
								if(cfDetail == "Arcane") {
									charSheet.added_class_skills.push("Knowledge (arcana)")
									charSheet.sa_bloodline_notes += ", +1 DC for metamagic spells"
									charSheet.sa_bloodline_notes += ", arcane bond"
									if(sorLevel > 2 && sorLevel < 20) {
										charSheet.sa_bloodline_notes += ", metamagic adept " + Math.floor((sorLevel + 1)/ 4) + "/day"
									}
									if(sorLevel > 8) { } // new arcana
									if(sorLevel > 14) { } // school power
									if(sorLevel == 20) {
										charSheet.sa_bloodline_notes += ", arcane apotheosis"
									}
								}
								if(cfDetail == "Celestial") {
									charSheet.added_class_skills.push("Heal")
									charSheet.sa_bloodline_notes += ", summoned creatures gain DR " + Math.max(1, Math.floor(sorLevel / 2)) + "/evil"
									charSheet.sa_bloodline_notes += ", heavenly fire " + (3 + 1 * charSheet.bonus(charSheet.getValue("cha"))) + "/day (1d4+" + Math.floor(sorLevel / 2) + " vs. evil, heal good)"
									if(sorLevel > 2 && sorLevel < 20) {
										charSheet.sa_bloodline_notes += ", celestial resistances"
										if(sorLevel < 9) {
											charSheet.resist_traits += ", acid 5, cold 5"
										}
										else {
											charSheet.resist_traits += ", acid 10, cold 10"
										}
									}
									if(sorLevel > 8 && sorLevel < 20) {
										charSheet.sa_bloodline_notes += ", wings of heaven " + Math.floor(sorLevel / 2) + "minutes/day"
									}
									if(sorLevel > 14) {
										charSheet.sa_bloodline_notes += ", conviction"
									}
									if(sorLevel == 20) {
										charSheet.sa_bloodline_notes += ", wings of heaven (unlimited), ascension"
										charSheet.immune_traits += ", acid, cold, petrification"
										charSheet.resist_traits += ", electricity 10, fire 10"
										charSheet.save_traits += ", +4 vs. poison"
									}
								}
								if(cfDetail == "Destined") {
									charSheet.added_class_skills.push("Knowledge (history)")
									charSheet.sa_bloodline_notes += ", personal spells grant saving throw bonus"
									charSheet.sa_bloodline_notes += ", touch of destiny " + (3 + 1 * charSheet.bonus(charSheet.getValue("cha"))) + "/day (grant +" + Math.floor(sorLevel / 2) + " insight bonus)"
									if(sorLevel > 2) {
										charSheet.sa_bloodline_notes += ", fated (+" + Math.floor((sorLevel + 1) / 4) + " to saving throws and AC during surprise rounds)"
									}
									if(sorLevel > 8) {
										charSheet.sa_bloodline_notes += ", it was meant to be " + Math.floor((sorLevel - 1) / 8) + "/day"
									}
									if(sorLevel > 14) {
										charSheet.sa_bloodline_notes += ", within reach"
									}
									if(sorLevel == 20) {
										charSheet.sa_bloodline_notes += ", destiny realized"
										charSheet.resist_traits += ", critical hits confirm only on 20"
										charSheet.ac_damage_reduction = Math.max(charSheet.getValue("ac_damage_reduction"), 5)
									}
								}
								if(cfDetail == "Draconic") {
									charSheet.added_class_skills.push("Perception")
								}
								if(cfDetail == "Elemental") {
									charSheet.added_class_skills.push("Knowledge (planes)")
								}
								if(cfDetail == "Fey") {
									charSheet.added_class_skills.push("Knowledge (nature)")
									charSheet.sa_bloodline_notes += ", +2 DC for compulsion spells"
									charSheet.sa_bloodline_notes += ", laughing touch " + (3 + 1 * charSheet.bonus(charSheet.getValue("cha"))) + "/day"
									if(sorLevel > 2) {
										charSheet.sa_bloodline_notes += ", woodland stride"
									}
									if(sorLevel > 8) {
										charSheet.sa_bloodline_notes += ", fleeting glance " + sorLevel + " rounds/day"
									}
									if(sorLevel > 14) {
										charSheet.sa_bloodline_notes += ", fey magic"
									}
									if(sorLevel == 20) {
										charSheet.sa_bloodline_notes += ", soul of the fey"
										charSheet.immune_traits += ", poison"
										charSheet.ac_damage_reduction_cold_iron = 10
									}
								}
								if(cfDetail == "Infernal") {
									charSheet.added_class_skills.push("Diplomacy")
									charSheet.sa_bloodline_notes += ", +2 DC for charm spells"
									charSheet.sa_bloodline_notes += ", corrupting touch " + (3 + 1 * charSheet.bonus(charSheet.getValue("cha"))) + "/day (" + Math.max(1, Math.floor(sorLevel / 2)) + " rounds)"
									if(sorLevel > 2 && sorLevel < 20) {
										charSheet.sa_bloodline_notes += ", infernal resistances"
										if(sorLevel < 9) {
											charSheet.resist_traits += ", fire 5"
											charSheet.save_traits += ", +2 vs. poison"
										}
										else {
											charSheet.resist_traits += ", fire 10"
											charSheet.save_traits += ", +4 vs. poison"
										}
									}
									if(sorLevel > 8) {
										charSheet.sa_bloodline_notes += ", hellfire " + Math.max(1, Math.floor((sorLevel - 11) / 3)) + "/day (DC " + (10 + Math.floor(sorLevel / 2) + charSheet.bonus(charSheet.cha)) + ", " + sorLevel + "d6)"
									}
									if(sorLevel > 14) {
										charSheet.sa_bloodline_notes += ", on dark wings"
										charSheet.movement += ", fly 60 ft. (average)"
									}
									if(sorLevel == 20) {
										charSheet.sa_bloodline_notes += ", power of the pit"
										charSheet.immune_traits += ", fire, poison"
										charSheet.resist_traits += ", acid 10, cold 10"
										charSheet.senses_traits += ", perfect darkvision (60 ft.)"
									}
								}
								if(cfDetail == "Undead") {
									charSheet.added_class_skills.push("Knowledge (religion)")
									charSheet.sa_bloodline_notes += ", corporeal undead that were once humanoids are treated as humanoids for spell affects"
									charSheet.sa_bloodline_notes += ", grave touch " + (3 + 1 * charSheet.bonus(charSheet.getValue("cha"))) + "/day (" + Math.max(1, Math.floor(sorLevel / 2)) + " rounds)"
									if(sorLevel > 2 && sorLevel < 20) {
										charSheet.sa_bloodline_notes += ", death's gift"
										if(sorLevel < 9) {
											charSheet.resist_traits += ", cold 5"
											charSheet.ac_damage_reduction_lethal = 5
										}
										else {
											charSheet.resist_traits += ", cold 10"
											charSheet.ac_damage_reduction_lethal = 10
										}
									}
									if(sorLevel > 8) {
										charSheet.sa_bloodline_notes += ", grasp of the dead " + Math.max(1, Math.floor((sorLevel - 11) / 3)) + "/day (DC " + (10 + Math.floor(sorLevel / 2) + charSheet.bonus(charSheet.getValue("cha"))) + ", " + sorLevel + "d6)"
									}
									if(sorLevel > 14) {
										charSheet.sa_bloodline_notes += ", incorporeal form " + sorLevel + " rounds/day"
									}
									if(sorLevel == 20) {
										charSheet.sa_bloodline_notes += ", one of us"
										charSheet.immune_traits += ", cold, nonlethal damage, paralysis, sleep"
										charSheet.ac_damage_reduction = Math.max(charSheet.getValue("ac_damage_reduction"), 5)
										charSheet.save_traits += ", +4 vs. spells and spell-like abilities cast by undead"
									}
								}
								break
							}
							case "412": { // dragon bloodline type
								if(charSheet.sa_bloodline == "Draconic") {
									charSheet.outputDragonType += dropdown_helper("classfeature" + charSheet.class_features[i].id, cfDetail, ['Black','Blue','Green','Red','White','Brass','Bronze','Copper','Gold','Silver'])
									charSheet.sa_dragon_type = cfDetail
									switch(cfDetail) {
										case "Black": case "Green": case "Copper": tmpEnergyType = "acid"; break;
									  case "Blue": case "Bronze": tmpEnergyType = "electricity"; break;
									  case "Red": case "Brass": case "Gold": tmpEnergyType = "fire"; break;
									  case "White": case "Silver": tmpEnergyType = "cold"; break;
									  default: tmpEnergyType = "unknown";
									 }
									charSheet.sa_bloodline_notes += ", +1 damage per die on " + tmpEnergyType + " spells"
									charSheet.sa_bloodline_notes += ", "
									if(sorLevel > 10) {
										charSheet.sa_bloodline_notes += tmpEnergyType + " "
									}
									else {
										if(sorLevel > 4) {
											charSheet.sa_bloodline_notes += "magic "
											charSheet.sa_bloodline_notes += " claws " + (3 + 1 * charSheet.bonus(charSheet.getValue("cha"))) + "/day ("
											if(sorLevel < 7) {
												charSheet.sa_bloodline_notes += charSheet.dice("5")
											}
											else {
												charSheet.sa_bloodline_notes += charSheet.dice("7")
											}
											if(charSheet.bonus(charSheet.getValue("str"), 1)) charSheet.sa_bloodline_notes += "+" + charSheet.bonus(charSheet.getValue("str"), 1)
											if(sorLevel > 10) {
												charSheet.sa_bloodline_notes += "+1d6"
											}
											charSheet.sa_bloodline_notes += ")"
										}
									}
									if(sorLevel > 2 && sorLevel < 20) {
										charSheet.sa_bloodline_notes += ", dragon resistance";
										if(sorLevel < 9) {
											charSheet.resist_traits += ", " + tmpEnergyType + " 5"
										}
										else {
											charSheet.resist_traits += ", " + tmpEnergyType + " 10"
										}
									}
									if(sorLevel > 2) {
										if(sorLevel < 9) {
											charSheet.ac_natural_armour += 1;
										}
										else {
											if(sorLevel < 15) {
												charSheet.ac_natural_armour += 2
											}
											else {
												charSheet.ac_natural_armour += 4
											}
										}
									}
									if(sorLevel > 8) {
										charSheet.sa_bloodline_notes += ", breath weapon " + Math.max(1, Math.floor((sorLevel - 11) / 3)) + "/day (DC " + (10 + Math.floor(sorLevel / 2) + charSheet.bonus(charSheet.getValue("cha"))) + ", " + sorLevel + "d6)"
									}
									if(sorLevel > 14) {
										charSheet.sa_bloodline_notes += ", wings"
										charSheet.movement += ", fly 60 ft. (average)"
									}
									if(sorLevel == 20) {
										charSheet.sa_bloodline_notes += ", power of wyrms"
										charSheet.immune_traits += ", paralysis, sleep, " + tmpEnergyType
										charSheet.senses_traits += "; blindsense (60 ft.)"
									}
								}
								break
							}
							case "424": { // elemental bloodline type
								if(charSheet.sa_bloodline == "Elemental") {
									charSheet.outputElementType += dropdown_helper("classfeature" + charSheet.getValue("class_features." + i + ".id"), cfDetail, ['Air','Earth','Fire','Water'])
									charSheet.sa_element_type = cfDetail
									switch(cfDetail) {
										case "Air": tmpEnergyType = "electricity"; tmpMovement = ", fly 60 ft. (average)"; break;
										case "Earth": tmpEnergyType = "acid"; tmpMovement = ", burrow 30 ft."; break;
										case "Fire": tmpEnergyType = "fire"; tmpMovement = ""; charSheet.base_speed_traits += 30; break;
										case "Water": tmpEnergyType = "cold"; tmpMovement = ", swim 60 ft."; break;
										default: tmpEnergyType = "unknown";
									}
									charSheet.sa_bloodline_notes += ", +1 damage per die on " + tmpEnergyType + " spells"
									charSheet.sa_bloodline_notes += ", elemental ray " + (3 + 1 * charSheet.bonus(charSheet.getValue("cha"))) + "/day (1d6+" + Math.floor(sorLevel / 2) + ")"
									if(sorLevel > 2 && sorLevel < 20) {
										charSheet.sa_bloodline_notes += ", elemental resistance"
										charSheet.resist_traits += ", " + tmpEnergyType + " " + Math.min(2, Math.floor((sorLevel + 3) / 6)) + "0"
									}
									if(sorLevel > 8) {
										charSheet.sa_bloodline_notes += ", elemental blast " + Math.max(1, Math.floor((sorLevel - 11) / 3)) + "/day (DC " + (10 + Math.floor(sorLevel / 2) + charSheet.bonus(charSheet.getValue("cha"))) + ", " + sorLevel + "d6)"
									}
									if(sorLevel > 14) {
										charSheet.sa_bloodline_notes += ", elemental movement"
										charSheet.movement += tmpMovement
									}
									if(sorLevel == 20) {
										charSheet.sa_bloodline_notes += ", elemental body"
										charSheet.immune_traits += ", critical hits, sneak attacks, " + tmpEnergyType
									}
								}
								break
							}
							case "51": { // channel
								charSheet.sa_cp = 3 + 1 * charSheet.bonus(charSheet.getValue("cha"))
								charSheet.outputChannelEnergy += dropdown_helper("classfeature" + charSheet.getValue("class_features." + i + ".id"), cfDetail, Array("positive","negative"))
								charSheet.sa_channel += cfDetail
								break
							}
							case "138": { // channel positive energy
								charSheet.sa_features = ", channel positive energy (DC " + (10 + Math.floor(charSheet.getValue("class_level.PAL") / 2) + 1 * charSheet.bonus(charSheet.getValue("cha"))) + ", " + Math.floor((1 * charSheet.getValue("class_level.PAL") + 1) / 2) + "d6)"
								break
							}
							case "86": { // wild shape
								charSheet.sa_traits += ", wild shape "
								if(charSheet.getValue("class_level.DRD") == 20) {
									charSheet.sa_traits += "at will"
								}
								else {
									charSheet.sa_traits += Math.floor((1 * charSheet.getValue("class_level.DRD") - 2) / 2) + "/day"
								}
								break
							}
							case "104": { // weapon group
								charSheet.outputWeaponGroup += dropdown_helper("classfeature" + charSheet.class_features[i].id, cfDetail, ['Axes','Heavy Blades','Light Blades','Bows','Close','Crossbows','Double','Flails','Hammers','Monk','Natural','Pole Arms','Spears','Thrown'])
								if(charSheet.sa_weapon_group[cfDetail]) {
									charSheet.sa_weapon_group[cfDetail]++
								}
								else {
									charSheet.sa_weapon_group[cfDetail] = 1
								}
								break
							}
							case "106": { // weapon mastery
								charSheet.outputWeaponMastery += "<input type=\"text\" id=\"classfeature" + charSheet.getValue("class_features." + i + ".id") + "\" value=\"" + cfDetail + "\">"
								charSheet.sa_weapon_mastery += ", " + cfDetail
								break
							}
							case "109": { // flurry of blows
								charSheet.sa_flurry++
								break
							}
							case "126": { // quivering palm
								charSheet.sa_quivering_palm++
								break
							}
							case "134": { // smite evil
								charSheet.sa_traits += ", smite evil (" + Math.floor((1 * charSheet.getValue("class_level.PAL") + 2) / 3) + "/day, +"
								if(charSheet.getValue("cha") > 11)
									charSheet.sa_traits += charSheet.bonus(charSheet.getValue("cha")) + " attack and AC, +"
								charSheet.sa_traits += charSheet.getValue("class_level.PAL") + " damage"
								if(charSheet.getValue("class_level.PAL") == 20)
									charSheet.sa_traits += ", banishment"
								charSheet.sa_traits += ")"
								break
							}
							case "100": { // quarry
								charSheet.sa_quarry++
								break
							}
							case "123": { // quarry
								charSheet.sa_quarry++
								break
							}
							case "127": { // master hunter
								charSheet.sa_features += ", master hunter (DC " + (10 + 1 * Math.floor(charSheet.getValue("class_level.RGR") / 2) + 1 * charSheet.bonus(charSheet.getValue("wis"))) + ")"
								break
							}
							case "13": { // favoured enemy
								charSheet.outputFavouredEnemy += dropdown_helper("classfeature" + charSheet.getValue("class_features." + i + ".id"), cfDetail, ['Aberration','Animal','Construct','Dragon','Fey','Humanoid (aquatic)','Humanoid (dwarf)','Humanoid (elf)','Humanoid (giant)','Humanoid (goblinoid)','Humanoid (gnoll)','Humanoid (gnome)','Humanoid (halfling)','Humanoid (human)','Humanoid (orc)','Humanoid (reptilian)','Humanoid (other subtype)','Magical beast','Monstrous humanoid','Ooze','Outsider (air)','Outsider (chaotic)','Outsider (earth)','Outsider (evil)','Outsider (fire)','Outsider (good)','Outsider (lawful)','Outsider (native)','Outsider (water)','Plant','Undead','Vermin'])
								if(charSheet.getValue("sa_favoured_enemy." + cfDetail)) {
									charSheet.sa_favoured_enemy[cfDetail]++
								}
								else {
									charSheet.sa_favoured_enemy[cfDetail] = 1
								}
								break
							}
							case "296": { // sneak attack
								charSheet.sa_features += ", sneak attack +" + Math.floor((charSheet.getNumber("class_level.ROG") + 1) / 2) + "d6"
								break
							}
							case "299": { // master strike
								charSheet.sa_features += ", master strike"
								break
							}
					// spells
						// CLR & DRD
							case "52": { // domain
								if(cfDetail) {
									charSheet.sa_domains.push(cfDetail)
								}
								var tmpDomains = new Array()
								for(var tmpDomain in charSheet.spell_list['DOMAIN'][1]) {
									tmpDomains.push(tmpDomain)
								}
							  charSheet.outputDomains += dropdown_helper("classfeature" + charSheet.getValue("class_features." + i + ".id"), cfDetail, tmpDomains)
							  break
							}
						// PAL
							case "133": { // detect evil
								charSheet.spell_like_traits += "; detect evil (at will, CL " + charSheet.ordinal(charSheet.getValue("class_level.PAL")) + ")"
								break
							}
					// feats
							case "101": charSheet.fp_features++; break;
							case "110": charSheet.fp_features++; break;
							case "112": charSheet.fp_features++; break;
							case "75":  charSheet.fp_features++; break;
							case "331": charSheet.fp_features++; break;
							case "411": charSheet.fp_features++; break;
						// skills
							case "15": { // Bardic Knowledge (Ex)
								charSheet.skill_bard_know = Math.max(1, Math.floor(charSheet.getValue("class_level.BRD") / 2))
								charSheet.sq_features += ", bardic knowledge +" + charSheet.getValue("skill_bard_know")
								break
							}
							case "31": { // Versatile Performance (Ex)
								charSheet.outputVersatilePerf += dropdown_helper("classfeature" + charSheet.getValue("class_features." + i + ".id"), cfDetail, ['Act','Comedy','Dance','Keyboard Instruments','Oratory','Percussion Instruments','Sing','String Instruments','Wind Instruments'])
								charSheet.sa_versatile_perf += ", " + cfDetail
								switch(cfDetail) {
									case "Act": charSheet.sa_versatile_perf += " (Bluff, Disguise)"; break;
									case "Comedy": charSheet.sa_versatile_perf += " (Bluff, Intimidate)"; break;
									case "Dance": charSheet.sa_versatile_perf += " (Acrobatics, Fly)"; break;
									case "Keyboard Instruments": charSheet.sa_versatile_perf += " (Diplomacy, Intimidate)"; break;
									case "Oratory": charSheet.sa_versatile_perf += " (Diplomacy, Sense Motive)"; break;
									case "Percussion Instruments": charSheet.sa_versatile_perf += " (Handle Animal, Intimidate)"; break;
									case "Sing": charSheet.sa_versatile_perf += " (Bluff, Sense Motive)"; break;
									case "String Instruments": charSheet.sa_versatile_perf += " (Bluff, Diplomacy)"; break;
									case "Wind Instruments": charSheet.sa_versatile_perf += " (Diplomacy, Handle Animal)"; break;
								}
								break
							}
							case "33": { // lore master
								charSheet.sq_features += ", lore master " + Math.floor((1 * charSheet.getValue("class_level.BRD") + 1) / 6) + "/day"
								break
							}
							case "81": {
								charSheet.skill_trait_bonus[41] += 2
								charSheet.skill_trait_bonus[92] += 2
								break
							}
					// languages
							case "79": { // bonus languages
								charSheet.lp_features++
								break // A druid's bonus language options include Sylvan, the language of woodland creatures.
							}
							case "128": { // tongue of the sun and moon
								charSheet.sq_features += ", tongue of the sun and moon"
								break
							}
					// sq
							case "34": { // jack-of-all-trades
								charSheet.sa_jack_of_all_trades++
								break
							}
							case "49": { // aura
								charSheet.sq_features += ", aura"
								break
							}
							case "54": { // spontaneous casting
								charSheet.sq_features += ", spontaneous casting"
								break
							}
							case "77": { // hunter's bond
								if(!cfDetail) charSheet.sq_features += ", hunter's bond (" + Math.max(1, charSheet.bonus(charSheet.getValue("wis"))) + " rounds)"
							}
							case "80": { // animal companion
								if(cfDetail) {
									charSheet.sa_animal_companion = cfDetail
									charSheet.sq_features += ", animal companion (" + charSheet.getValue("sa_animal_companion") + ")"
								}
								charSheet.outputAnimalCompanion += "<input type=\"text\" id=\"classfeature" + charSheet.getValue("class_features." + i + ".id") + "\" value=\"" + cfDetail + "\">"
								break
							}
// wild empathy calculated from total level???
							case "82": { // wild empathy
								charSheet.sq_features += ", wild empathy +" + (1 * charSheet.getValue("total_level") + 1 * charSheet.bonus(charSheet.getValue("cha")))
								break
							}
							case "83": { // woodland stride
								charSheet.sq_features += ", woodland stride"
								break
							}
							case "84": { // trackless step
								charSheet.sq_features += ", trackless step"
								break
							}
							case "88": { // a thousand faces
								charSheet.sq_features += ", a thousand faces"
								break
							}
							case "89":
							case "127": { // timeless body
								charSheet.sq_features += ", timeless body"
								break
							}
							case "103": { // armour training
								charSheet.spd_armour_training = Math.floor((1 * charSheet.getValue("class_level.FTR") + 1) / 4)
								charSheet.sq_features += ", armour training +" + charSheet.getValue("spd_armour_training")
								break
							}
							case "118": { // slow fall
								charSheet.sa_slow_fall++
								break
							}
							case "119": { // high jump
								charSheet.sq_features += ", high jump"
								break
							}
							case "121": { // wholeness of body
								charSheet.sq_features += ", wholeness of body"
								break
							}
							case "124": { // abundant step
								charSheet.sq_features += ", abundant step"
								break
							}
							case "129": { // empty body
								charSheet.sq_features += ", empty body"
								break
							}
							case "132": { // aura
								charSheet.sq_features += ", aura"
								break
							}
							case "136": { // lay on hands
								charSheet.sa_lohp = (Math.floor(charSheet.getValue("class_level.PAL") / 2) + charSheet.bonus(charSheet.getValue("cha")))
								break
							}
							case "139": { // mercies
								charSheet.outputMercies += dropdown_helper("classfeature" + charSheet.getValue("class_features." + i + ".id"), cfDetail, ['Fatigued','Shaken','Sickened','Dazed','Diseased','Staggered','Cursed','Exhausted','Frightened','Nauseated','Poisoned','Blinded','Deafened','Paralyzed','Stunned'])
								charSheet.sq_mercies += ", " + cfDetail
								break
							}
							case "142": { // divine bond
								charSheet.outputDivineBond += "<select id=\"classfeature" + charSheet.getValue("class_features." + i + ".id") + "\"><option value=\"" + cfDetail + "\">" + cfDetail + "</option><option value=\"\">--------------------</option><option value=\"weapon\">weapon</option><option value=\"mount\">mount</option></select>"
								charSheet.sq_features += ", divine bond (" + cfDetail
								if(cfDetail.toLowerCase() == "weapon") {
									charSheet.sq_features += " +" + Math.floor((charSheet.getValue("class_level.PAL") - 2) / 3) + ", "
								}
								else {
									if(charSheet.getValue("class_level.PAL") > 10) {
										charSheet.sq_features += ", celestial"
									}
									if(charSheet.getValue("class_level.PAL") > 14) {
										charSheet.sq_features += ", SR " + (1 * charSheet.getValue("class_level.PAL") + 11)
									}
									charSheet.sq_features += ", call "
								} charSheet.sq_features += Math.floor((charSheet.getValue("class_level.PAL") - 1) / 4) + "/day)"
								break
							}
							case "48": { // track
								charSheet.sq_features += ", track +" + Math.max(1, Math.floor(charSheet.getValue("class_level.RGR") / 2))
								break
							}
							case "78": { // swift tracker
								charSheet.sq_features += ", swift tracker"
								break
							}
							case "50": { // combat style
								charSheet.outputCombatStyle += dropdown_helper("classfeature" + charSheet.getValue("class_features." + i + ".id"), cfDetail, ['archery','two-weapon'])
								break
							}
							case "76": { // favoured terrain
								charSheet.outputFavouredTerrain += dropdown_helper("classfeature" + charSheet.getValue("class_features." + i + ".id"), cfDetail, ['Cold','Desert','Forest','Jungle','Mountain','Plains','Swamp','Underground','Urban','Water'])
								if(charSheet.getValue("sq_favoured_terrain." + cfDetail)) {
									charSheet.sq_favoured_terrain[cfDetail]++
								}
								else {
									charSheet.sq_favoured_terrain[cfDetail] = 1
								}
								break
							}
							case "297": { // trap finding
								charSheet.sq_features += ", trapfinding +" + Math.max(1, Math.floor(charSheet.getValue("class_level.ROG") / 2))
								break
							}
							case "298": { // rogue talents
								charSheet.outputRogueTalents += dropdown_helper("classfeature" + charSheet.getValue("class_features." + i + ".id"), cfDetail, ['Bleeding Attack','Combat Trick','Fast Stealth','Finesse Rogue','Ledge Walker','Major Magic','Minor Magic','Quick Disable','Resiliency','Rogue Crawl','Slow Reactions','Stand Up','Surprise Attack','Trap Spotter','Weapon Training','Crippling Strike','Defensive Roll','Dispelling Attack','Improved Evasion','Opportunist','Skill Mastery','Slippery Mind','Feat'])
								charSheet.sq_rogue_talents += ", " + cfDetail
								if(cfDetail.toLowerCase() == "bleeding attack") {
									charSheet.sq_rogue_talents += " +" + Math.floor(charSheet.getValue("class_level.ROG") / 2)
								}
								if(cfDetail.toLowerCase() == "resiliency") {
									charSheet.sq_rogue_talents += " +" + charSheet.getValue("class_level.ROG")
								}
								if(cfDetail.toLowerCase() == "improved evasion") {
									charSheet.ac_evasion++
								}
								if(cfDetail.toLowerCase() == "skill mastery") {
									charSheet.sq_rogue_talents += " x" + (3 + 1 * charSheet.bonus(charSheet.getValue("int")))
								}
								if(cfDetail.toLowerCase() == "combat trick" || cfDetail.toLowerCase() == "finesse rogue" || cfDetail.toLowerCase() == "weapon training" || cfDetail.toLowerCase() == "feat") {
									charSheet.fp_features++
								}
								break
							}
							case "408": { // arcane bond
								if(charSheet.class_level['WIZ'] > 0 || charSheet.class_level['ADP'] > 0 || charSheet.sa_bloodline == "Arcane") {
									charSheet.outputArcaneBond += "<input type=\"text\" id=\"classfeature" + charSheet.getValue("class_features." + i + ".id") + "\" value=\"" + cfDetail + "\">"
									charSheet.sq_features += ", arcane bond (" + cfDetail + ")"
								}
								break
							}
	// TO DO: Arcane School effects on stats
							case "409": { // arcance school
								charSheet.outputArcaneSchool += dropdown_helper("classfeature" + charSheet.getValue("class_features." + i + ".id"), cfDetail, ['Abjuration','Conjuration','Divination','Enchantment','Evocation','Illusion','Necromancy','Transmutation','Universalist'])
								charSheet.sa_arcane_school = cfDetail
								if(cfDetail == "Abjuration") {
									charSheet.sa_arcane_school_notes += ", resistance"; // You gain resistance 5 to an energy type of your choice, chosen when you prepare spells. This resistance can be changed each day. At 11th level, this resistance increases to 10. At 20th level, this resistance changes to immunity to the chosen energy type.
									charSheet.sa_arcane_school_notes += ", protective ward"; // As a standard action, you can create a 10-foot-radius field of protective magic centered on you that lasts for a number of rounds equal to your Intelligence modifier. All allies in this area (including you) receive a +1 def lection bonus to their AC for 1 round. This bonus increases by +1 for every five wizard levels you possess. You can use this ability a number of times per day equal to 3 + your Intelligence modifier.
									charSheet.sa_arcane_school_notes += ", energy absorption"; // At 6th level, you gain an amount of energy absorption equal to 3 times your wizard level per day. Whenever you take energy damage, apply immunity, vulnerability (if any), and resistance first and apply the rest to this absorption, reducing your daily total by that amount. Any damage in excess of your absorption is applied to you normally.
								}
								if(cfDetail == "Conjuration") {
									charSheet.sa_arcane_school_notes += ", summoner's charm"; // Whenever you cast a conjuration (summoning) spell, increase the duration by a number of rounds equal to 1/2 your wizard level (minimum 1). At 20th level, you can change the duration of all summon monster spells to permanent. You can have no more than one summon monster spell made permanent in this way at one time. If you designate another summon monster spell as permanent, the previous spell immediately ends.
									charSheet.sa_arcane_school_notes += ", acid dart"; // As a standard action you can unleash an acid dart targeting any foe within 30 feet as a ranged touch attack. The acid dart deals 1d6 points of acid damage + 1 for every two wizard levels you possess. You can use this ability a number of times per day equal to 3 + your Intelligence modif ier. This attack ignores spell resistance.
									charSheet.sa_arcane_school_notes += ", dimensional steps"; // At 8th level, you can use this ability to teleport up to 30 feet per wizard level per day as a standard action. This teleportation must be used in 5-foot increments and such movement does not provoke an attack of opportunity. You can bring other willing creatures with you, but you must expend an equal amount of distance for each additional creature brought with you.
								}
								if(cfDetail == "Divination") {
									charSheet.sa_arcane_school_notes += ", forewarned"; // You can always act in the surprise round even if you fail to make a Perception roll to notice a foe, but you are still considered f lat-footed until you take an action. In addition, you receive a bonus on initiative checks equal to 1/2 your wizard level (minimum +1). At 20th level, anytime you roll initiative, assume the roll resulted in a natural 20.
									charSheet.sa_arcane_school_notes += ", diviner's fortune"; // When you activate this school power, you can touch any creature as a standard action to give it an insight bonus on all of its attack rolls, skill checks, ability checks, and saving throws equal to 1/2 your wizard level (minimum +1) for 1 round. You can use this ability a number of times per day equal to 3 + your Intelligence modifier.
									charSheet.sa_arcane_school_notes += ", scrying adept"; // At 8th level, you are always aware when you are being observed via magic, as if you had a permanent detect scrying. In addition, whenever you scry on a subject, treat the subject as one step more familiar to you. Very familiar subjects get a –10 penalty on their save to avoid your scrying attempts.
								}
								if(cfDetail == "Enchantment") {
									charSheet.sa_arcane_school_notes += ", enchanting smile"; // You gain a +2 enhancement bonus on Bluff, Diplomacy, and Intimidate skill checks. This bonus increases by +1 for every five wizard levels you possess, up to a maximum of +6 at 20th level. At 20th level, whenever you succeed at a saving throw against a spell of the enchantment school, that spell is ref lected back at its caster, as per spell turning.
									charSheet.sa_arcane_school_notes += ", dazing touch"; // You can cause a living creature to become dazed for 1 round as a melee touch attack. Creatures with more Hit Dice than your wizard level are unaffected. You can use this ability a number of times per day equal to 3 + your Intelligence modifier.
									charSheet.sa_arcane_school_notes += ", aura of despair"; // At 8th level, you can emit a 30-foot aura of despair for a number of rounds per day equal to your wizard level. Enemies within this aura take a –2 penalty on ability checks, attack rolls, damage rolls, saving throws, and skill checks. These rounds do not need to be consecutive.
								}
								if(cfDetail == "Evocation") {
									charSheet.sa_arcane_school_notes += ", intense spells"; // Whenever you cast an evocation spell that deals hit point damage, add 1/2 your wizard level to the damage (minimum +1). This bonus only applies once to a spell, not once per missile or ray, and cannot be split between multiple missiles or rays. This damage is of the same type as the spell. At 20th level, whenever you cast an evocation spell you can roll twice to penetrate a creature’s spell resistance and take the better result.
									charSheet.sa_arcane_school_notes += ", force missile"; // As a standard action you can unleash a force missile that automatically strikes a foe, as magic missile. The force missile deals 1d4 points of damage plus the damage from your intense spells evocation power. This is a force effect. You can use this ability a number of times per day equal to 3 + your Intelligence modifier.
									charSheet.sa_arcane_school_notes += ", elemental wall"; // At 8th level, you can create a wall of energy that lasts for a number of rounds per day equal to your wizard level. These rounds do not need to be consecutive. This wall deals acid, cold, electricity, or fire damage, determined when you create it. The elemental wall otherwise functions like wall of fire.
								}
								if(cfDetail == "Illusion") {
									charSheet.sa_arcane_school_notes += ", extended illusions"; // Any illusion spell you cast with a duration of “concentration” lasts a number of additional rounds equal to 1/2 your wizard level after you stop maintaining concentration (minimum +1 round). At 20th level, you can make one illusion spell with a duration of “concentration” become permanent. You can have no more than one illusion made permanent in this way at one time. If you designate another illusion as permanent, the previous permanent illusion ends.
									charSheet.sa_arcane_school_notes += ", blinding ray"; // As a standard action you can fire a shimmering ray at any foe within 30 feet as a ranged touch attack. The ray causes creatures to be blinded for 1 round. Creatures with more Hit Dice than your wizard level are dazzled for 1 round instead. You can use this ability a number of times per day equal to 3 + your Intelligence modifier.
									charSheet.sa_arcane_school_notes += ", invisibility field"; // At 8th level, you can make yourself invisible as a swift action for a number of rounds per day equal to your wizard level. These rounds do not need to be consecutive. This otherwise functions as greater invisibility.
								}
								if(cfDetail == "Necromancy") {
									charSheet.sa_arcane_school_notes += ", power over undead"; // You receive Command Undead or Turn Undead as a bonus feat. You can channel energy a number of times per day equal to 3 + your Intelligence modifier, but only to use the selected feat. You can take other feats to add to this ability, such as Extra Channel and Improved Channel, but not feats that alter this ability, such as Elemental Channel and Alignment Channel. The DC to save against these feats is equal to 10 + 1/2 your wizard level + your Charisma modifier. At 20th level, undead cannot add their channel resistance to the save against this ability.
									charSheet.sa_arcane_school_notes += ", grave touch"; // As a standard action, you can make a melee touch attack that causes a living creature to become shaken for a number of rounds equal to 1/2 your wizard level (minimum 1). If you touch a shaken creature with this ability, it becomes frightened for 1 round if it has fewer Hit Dice than your wizard level. You can use this ability a number of times per day equal to 3 + your Intelligence modifier.
									charSheet.sa_arcane_school_notes += ", life sight"; // At 8th level, you gain blindsight to a range of 10 feet for a number of rounds per day equal to your wizard level. This ability only allows you to detect living creatures and undead creatures. This sight also tells you whether a creature is living or undead. Constructs and other creatures that are neither living nor undead cannot be seen with this ability. The range of this ability increases by 10 feet at 12th level, and by an additional 10 feet for every four levels beyond 12th.
								}
								if(cfDetail == "Transmutation") {
									charSheet.sa_arcane_school_notes += ", physical enhancement"; // You gain a +1 enhancement bonus to one physical ability score (Strength, Dexterity, or Constitution). This bonus increases by +1 for every five wizard levels you possess to a maximum of +5 at 20th level. You can change this bonus to a new ability score when you prepare spells. At 20th level, this bonus applies to two physical ability scores of your choice.
									charSheet.sa_arcane_school_notes += ", telekinetic fist"; // As a standard action you can strike with a telekinetic fist, targeting any foe within 30 feet as a ranged touch attack. The telekinetic fist deals 1d4 points of bludgeoning damage + 1 for every two wizard levels you possess. You can use this ability a number of times per day equal to 3 + your Intelligence modifier.
									charSheet.sa_arcane_school_notes += ", change shape"; // At 8th level, you can change your shape for a number of rounds per day equal to your wizard level. These rounds do not need to be consecutive. This ability otherwise functions like beast shape II or elemental body I. At 12th level, this ability functions like beast shape III or elemental body II.
								}
								if(cfDetail == "Universalist")  {
									charSheet.sa_arcane_school_notes += ", hand of the apprentice"; // You cause your melee weapon to f ly from your grasp and strike a foe before instantly returning to you. As a standard action, you can make a single attack using a melee weapon at a range of 30 feet. This attack is treated as a ranged attack with a thrown weapon, except that you add your Intelligence modifier on the attack roll instead of your Dexterity modifier (damage still relies on Strength). This ability cannot be used to perform a combat maneuver. You can use this ability a number of times per day equal to 3 + your Intelligence modifier.
									charSheet.sa_arcane_school_notes += ", metamagic mastery"; // At 8th level, you can apply any one metamagic feat that you know to a spell you are about to cast. This does not alter the level of the spell or the casting time. You can use this ability once per day at 8th level and one additional time per day for every two wizard levels you possess beyond 8th. Any time you use this ability to apply a metamagic feat that increases the spell level by more than 1, you must use an additional daily usage for each level above 1 that the feat adds to the spell. Even though this ability does not modify the spell’s actual level, you cannot use this ability to cast a spell whose modified spell level would be above the level of the highest-level spell that you are capable of casting.								                             charSheet.sa_arcane_school_notes += ", energy absorption"; // At 6th level, you gain an amount of energy absorption equal to 3 times your wizard level per day. Whenever you take energy damage, apply immunity, vulnerability (if any), and resistance first and apply the rest to this absorption, reducing your daily total by that amount. Any damage in excess of your absorption is applied to you normally.
								}
								break
							}
							case "410": { // opposition school
								charSheet.outputOppositionSchool += dropdown_helper("classfeature" + charSheet.getValue("class_features." + i + ".id"), cfDetail, ['Abjuration','Conjuration','Divination','Enchantment','Evocation','Illusion','Necromancy','Transmutation','Universalist'])
								charSheet.sa_opposition_schools.push(cfDetail)
								break
							}
						}
					}

					// build out spell list
					temp = charSheet.class_features[i].name.indexOf("-level")
					if(temp > -1) {
						temp += 7
						tmpEnd = charSheet.class_features[i].name.indexOf(" ", temp) // isolate class name
						tmpSpellClass = abbreviate_class_name(charSheet.class_features[i].name.substr(temp, tmpEnd - temp))
						tmpSpellLevel = validate_bonus_spell(i) // only set level if spell is obtainable based on ability score
						if(tmpSpellClass == "BRD" || tmpSpellClass == "SOR") {
							if(charSheet.class_features[i].name.indexOf("Known") == -1) { // just process "known" spells now
								tmpSpellLevel = -1
							}
						}
						if(tmpSpellLevel > -1) {
							if(charSheet.spell_list[tmpSpellClass]) {
								charSheet['output' + tmpSpellLevel + tmpSpellClass + 'Known'] += "<select id=\"spellknown" + charSheet.class_features[i].id + "\"><option value=\"" + cfDetail + "\">" + charSheet.spell_list[tmpSpellClass][tmpSpellLevel][cfDetail] + "</option><option value=\"0\">--------------------</option>"
								if(tmpSpellClass != "DOMAIN") {
									charSheet['output' + tmpSpellLevel + tmpSpellClass + 'Known'] += charSheet['output' + tmpSpellLevel + tmpSpellClass + 'List'] + "</select>"
								}
								if(charSheet.spell_list[tmpSpellClass][tmpSpellLevel][cfDetail]) {
									charSheet['sa_' + tmpSpellLevel + '_' + tmpSpellClass + '_known'] += " ; <span title=\"" + charSheet.spell_desc_list[tmpSpellClass][tmpSpellLevel][cfDetail] + "\">" + charSheet.spell_list[tmpSpellClass][tmpSpellLevel][cfDetail] + "</span>"
								}
							}
						}
					}
	
					// check for duplicates
					charSheet.class_features[i].count = 1
					if(i > 0) {
						for(j = 0; j < i; j++) {
							if(charSheet.class_features[j].count > 0 && charSheet.class_features[i].count > 0 && charSheet.class_features[i].name == charSheet.class_features[j].name) {
								charSheet.class_features[j].count++
								charSheet.class_features[i].count = 0
							}
						}
					}
				}
			}

			// prepare output
			// compress duplicates (e.g. x2, x3)
			// add notes or formatting
			for(i = 0, j = ""; i < charSheet.class_features.length; i++) {
				if(charSheet.class_features[i] && charSheet.class_features[i].count > 0) {
					j += "<span title=\"" + charSheet.class_features[i].description + "\">" + charSheet.class_features[i].name + "</span>"
					if(charSheet.class_features[i].count > 1) {
						j += " x" + charSheet.class_features[i].count
					}
					if(charSheet.class_features[i].name.substr(-14, 14) == "Spells per Day") {
						tmpCount = charSheet.class_features[i].count
						tmpLevel = charSheet.class_features[i].name.substr(0, 1)
						if(charSheet.class_features[i].name.indexOf("Adept") > 0) {
							if(!charSheet.spells.ADP) {
								charSheet.spells.ADP = {}
							}
							charSheet.spells.ADP[tmpLevel] = {}
							charSheet.spells.ADP[tmpLevel].perDay = tmpCount
							charSheet.spells.ADP.concentration = 10 + 1 * charSheet.getValue("class_level.ADP") + 1 * charSheet.bonus(charSheet.getValue("wis"))
							charSheet.spells.ADP.title = "Adept Spells Prepared"
						}
						if(charSheet.class_features[i].name.indexOf("Bard") > 0) {
							if(!charSheet.spells.BRD) {
								charSheet.spells.BRD = {}
							}
							charSheet.spells.BRD[tmpLevel] = {}
							charSheet.spells.BRD[tmpLevel].perDay = tmpCount
							charSheet.spells.BRD.concentration = 10 + 1 * charSheet.getValue("class_level.BRD") + 1 * charSheet.bonus(charSheet.getValue("cha"))
							charSheet.spells.BRD.title = "Bard Spells Known"
						}
						if(charSheet.class_features[i].name.indexOf("Cleric") > 0) {
							if(!charSheet.spells.CLR) {
								charSheet.spells.CLR = {}
							}
							charSheet.spells.CLR[tmpLevel] = {}
							charSheet.spells.CLR[tmpLevel].perDay = tmpCount
							charSheet.spells.CLR.concentration = 10 + 1 * charSheet.getValue("class_level.CLR") + 1 * charSheet.bonus(charSheet.getValue("wis"))
							charSheet.spells.CLR.title = "Cleric Spells Prepared"
						}
						if(charSheet.class_features[i].name.indexOf("Druid") > 0) {
							if(!charSheet.spells.DRD) {
								charSheet.spells.DRD = {}
							}
							charSheet.spells.DRD[tmpLevel] = {}
							charSheet.spells.DRD[tmpLevel].perDay = tmpCount
							charSheet.spells.DRD.concentration = 10 + 1 * charSheet.getValue("class_level.DRD") + 1 * charSheet.bonus(charSheet.getValue("wis"))
							charSheet.spells.DRD.title = "Druid Spells Prepared"
						}
						if(charSheet.class_features[i].name.indexOf("Paladin") > 0) {
							if(!charSheet.spells.PAL) {
								charSheet.spells.PAL = {}
							}
							charSheet.spells.PAL[tmpLevel] = {}
							charSheet.spells.PAL[tmpLevel].perDay = tmpCount
							charSheet.spells.PAL.concentration = 10 + 1 * charSheet.getValue("class_level.PAL") + 1 * charSheet.bonus(charSheet.getValue("cha"))
							charSheet.spells.PAL.title = "Paladin Spells Prepared"
						}
						if(charSheet.class_features[i].name.indexOf("Ranger") > 0) {
							if(!charSheet.spells.RGR) {
								charSheet.spells.RGR = {}
							}
							charSheet.spells.RGR[tmpLevel] = {}
							charSheet.spells.RGR[tmpLevel].perDay = tmpCount
							charSheet.spells.RGR.concentration = 10 + 1 * charSheet.getValue("class_level.RGR") + 1 * charSheet.bonus(charSheet.getValue("wis"))
							charSheet.spells.RGR.title = "Ranger Spells Prepared"
						}
						if(charSheet.class_features[i].name.indexOf("Sorcerer") > 0) {
							if(!charSheet.spells.SOR) {
								charSheet.spells.SOR = {}
							}
							charSheet.spells.SOR[tmpLevel] = {}
							charSheet.spells.SOR[tmpLevel].perDay = tmpCount
							charSheet.spells.SOR.concentration = 10 + 1 * charSheet.getValue("class_level.SOR") + 1 * charSheet.bonus(charSheet.getValue("cha"))
							charSheet.spells.SOR.title = "Sorcerer Spells Known"
						}
						if(charSheet.class_features[i].name.indexOf("Wizard") > 0) {
							if(!charSheet.spells.WIZ) {
								charSheet.spells.WIZ = {}
							}
							charSheet.spells.WIZ[tmpLevel] = {}
							if(charSheet.getValue("sa_arcane_school") && charSheet.getValue("sa_arcane_school") != "Universalist") {
								tmpCount++
							}
							charSheet.spells.WIZ[tmpLevel].perDay = tmpCount
							charSheet.spells.WIZ.concentration = 10 + 1 * charSheet.getValue("class_level.WIZ") + 1 * charSheet.bonus(charSheet.getValue("int"))
							charSheet.spells.WIZ.title = "Wizard Spells Prepared"
						}
					}
					if(charSheet.class_features[i].name.substr(-20, 20) == "Domain Spell per Day" && charSheet.sa_domains.length > 0) {
						tmpCount = charSheet.class_features[i].count
						tmpLevel = charSheet.class_features[i].name.substr(0, 1)
						if(!charSheet.spells['DOMAIN']) {
							charSheet.spells['DOMAIN'] = {}
						}
						charSheet.spells['DOMAIN'][tmpLevel] = {}
						charSheet.spells['DOMAIN'][tmpLevel].perDay = 1
					}
					j += "; "
				}
			}
			$('#calcClassFeatures').html(j)

			// calculate domain spell options based on domains
			if(charSheet.getValue("sa_domains")) {
				for(var j = 1; j < 10; j++) {
					for(var i = 0; i < charSheet.sa_domains.length; i++) {
						charSheet['output' + j + 'DOMAINKnown'] += "<option value=\"" + charSheet.sa_domains[i] + "\">" + charSheet.spell_list['DOMAIN'][j][charSheet.sa_domains[i]] + "</option>"
					}
					charSheet['output' + j + 'DOMAINKnown'] += "</select>"
				}
			}

			// recalculate sections
			charSheet.spells_build()
			charSheet.sq_build()
			calcSkills()
			calcDefense()

			console.log(" ... CHECK")
		}

		// check if the character has high enough primary ability for the spell
		function validate_bonus_spell(varCFNo)
		{
				console.log("validate_bonus_spell ... CHECK")
			var varName = charSheet.class_features[varCFNo].name
			var spellLevel = varName.substr(0, 1)
			if((temp = varName.indexOf("Bonus")) > -1) {
				var tmpAbility = charSheet[varName.substr(temp - 7, 3).toLowerCase()]
				var minAbility = varName.substr(temp - 3, 2)
				if(tmpAbility < minAbility) {
					charSheet.class_features[varCFNo].name = "" // didn't make the cut
					return -1
				}
				charSheet.class_features[varCFNo].name = varName.substr(0, temp - 7) + varName.substr(temp + 6)
			}
			if(spellLevel > charSheet[varName.substr(temp - 7, 3).toLowerCase()] - 10) {
				charSheet.class_features[varCFNo].name = "" // didn't make the cut
				return -1
			}
			return spellLevel
		}

		// calculate number of arcane spellcasting levels
		function is_arcane_spellcaster()
		{
			return (1 * charSheet.getValue("class_level.SOR") + 1 * charSheet.getValue("class_level.WIZ") + 1 * charSheet.getValue("class_level.BRD"))
		}

		// accept class full name and return its abbreviation
		function abbreviate_class_name(varClassName)
		{
				console.log("abbreviate_class_name ... CHECK")
			switch(varClassName) {
				case 'Adept': return 'ADP'
				case 'Alchemist': return 'ALC'
				case 'Antipaladin': return 'APL'
				case 'Aristocrat': return 'ART'
				case 'Barbarian': return 'BBN'
				case 'Bard': return 'BRD'
				case 'Cleric': return 'CLR'
				case 'Commoner': return 'COM'
				case 'Druid': return 'DRD'
				case 'Expert': return 'EXP'
				case 'Fighter': return 'FTR'
				case 'Inquisitor': return 'INQ'
				case 'Magus': return 'MAG'
				case 'Monk': return 'MNK'
				case 'Oracle': return 'ORA'
				case 'Paladin': return 'PAL'
				case 'Ranger': return 'RGR'
				case 'Rogue': return 'ROG'
				case 'Sorcerer': return 'SOR'
				case 'Summoner': return 'SUM'
				case 'Warrior': return 'WAR'
				case 'Witch': return 'WCH'
				case 'Wizard': return 'WIZ'
				default: return varClassName.toUpperCase()
			}
		}

		// create html select and options from array list
		function dropdown_helper(varID, varValue, varList)
		{
				console.log("dropdown_helper ... ")
			temp  = "<select id=\"" + varID + "\">"
			temp += "<option value=\"" + varValue + "\">" + varValue + "</option>"
			temp += "<option value=\"\">--------------------</option>"
			for(var i in varList) {
				temp += "<option value=\"" + varList[i] + "\">" + varList[i] + "</option>"
			}
			temp += "</select>"
				console.log(" ... CHECK")
			return temp
		}

		// calculate treasure section
		function calcTreasure()
 		{
			console.log("function calcTreasure()")

			// gold
			var tmpMaxValue
			charSheet.total_value = $('#total_value').val()
			switch(charSheet.getValue("cr")) {
				case "1/3":
				case "-3": tmpMaxValue = 260; break;
				case "1/2":
				case "-2": tmpMaxValue = 390; break;
				case "1":  tmpMaxValue = 780; break;
				case "2":  tmpMaxValue = 1650; break;
				case "3":  tmpMaxValue = 2400; break;
				case "4":  tmpMaxValue = 3450; break;
				case "5":  tmpMaxValue = 4650; break;
				case "6":  tmpMaxValue = 6000; break;
				case "7":  tmpMaxValue = 7800; break;
				case "8":  tmpMaxValue = 10050; break;
				case "9":  tmpMaxValue = 12750; break;
				case "10": tmpMaxValue = 16350; break;
				case "11": tmpMaxValue = 21000; break;
				case "12": tmpMaxValue = 27000; break;
				case "13": tmpMaxValue = 34800; break;
				case "14": tmpMaxValue = 45000; break;
				case "15": tmpMaxValue = 58500; break;
				case "16": tmpMaxValue = 75000; break;
				case "17": tmpMaxValue = 96000; break;
				case "18": tmpMaxValue = 123000; break;
				case "19": tmpMaxValue = 159000; break;
			}
			tmpMaxValue += charSheet.getValue("gold_feats")
			$('#calcMaxValue').text("/ " + tmpMaxValue)

			// load
			var tmpLoad
			charSheet.total_weight = $('#total_weight').val()
			var tmpCarryingCap = charSheet.getNumber("lightLoad") * charSheet.getNumber("carrying_cap_feats")
			if(charSheet.getNumber("total_weight") <= tmpCarryingCap) {
				tmpLoad = "light load"
				charSheet.loadType = 0
			}
			else {
				charSheet.loadType = Math.ceil((charSheet.getNumber("total_weight") - tmpCarryingCap) / (tmpCarryingCap / 4))
				if(charSheet.getNumber("total_weight") <= tmpCarryingCap * 2) {
					tmpLoad = "medium load"
				}
				else if(charSheet.getNumber("total_weight") <= tmpCarryingCap * 3) {
					tmpLoad = "heavy load"
				}
				else {
					tmpLoad = "extreme load"
				}
				tmpLoad += ": -" + charSheet.getNumber("loadType") + " Str"
			}

			$('#calcLoad').text("/ " + Math.floor(tmpCarryingCap) + " lbs. (" + tmpLoad + ")")


			// recalculate sections
			calcSkills()
			calcOffense()
			calcDefense()

			console.log(" ... CHECK")
		}

		// onload
		function start(pkid)
		{
			console.log("function start(pkid = " + pkid + ")")
			if(!pkid) {
				// ajax call to populate add section
				buildSection('Add')
			}
			else {
				// show loading widgets
				$("#mainSection").html("<h6 class=\"statBlockTitle\">LOADING</h6>" + loadingWidget)
				$("#defenseSection").html(loadingWidget)
				$("#offenseSection").html(loadingWidget)
				$("#abilitiesSection").html(loadingWidget)
				$("#featsSection").html(loadingWidget)
				$("#skillsSection").html(loadingWidget)
				$("#languagesSection").html(loadingWidget)
				$("#specialabilitiesSection").html(loadingWidget)
				$("#treasureSection").html(loadingWidget)
				$("#descriptionSection").html(loadingWidget)
				$("#organizationSection").html(loadingWidget)
				$("#encounterSection").html(loadingWidget)
	
				// ajax call to populate first section, which cascades to populate all sections on the page
				buildSection('Abilities')
			}
			console.log(" ... CHECK")
		}
	</script>
