<?php

// create data collection to share information with the view
$view = new DataCollector;
$pageName = "Character Sheet";

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
		// character sheet object
		var charSheet = {
			race:                 '',
			raceType:             '',
			cr:                   0,
			charLevel:            0,
			bonusHP:              0,
			baseSP:               0,
			bonusSP:              0,

			total_classes:        Array(),

			spell_desc_list:      {},
			class_level:          {},
			skill_list:           {},
			spell_list:           {},
			spells:               {},

			// calculate ability score bonus
			bonus: function (paramAbilityScore, paramApplyLoad)
			{
				// if no ability score: return no bonus
				if(!paramAbilityScore) {
					return 0
				}

				if(paramApplyLoad && charSheet.getValue('loadType')) {
					// apply encumbrance penalty
					paramAbilityScore -= charSheet.getValue('loadType')
				}

				// return bonus
				return Math.floor((paramAbilityScore - 10) / 2)
			},

			// calculate size bonus
			sizeBonus: function ()
			{
				switch(charSheet.getTotal('size')) {
					case 'Small':  return -1
					case 'Large':  return 1
					default:       return 0
				}
			},

			// calculate number of arcane spellcasting levels
			buildCasterLevel: function()
			{
				return Math.max(charSheet.getNumber('class_level.BRD'),
				                charSheet.getNumber('class_level.CLR'),
				                charSheet.getNumber('class_level.DRD'),
				                charSheet.getNumber('class_level.PAL') - 3,
				                charSheet.getNumber('class_level.RGR') - 3,
				                charSheet.getNumber('class_level.SOR'),
				                charSheet.getNumber('class_level.WIZ')
				               )
			},

			// convert double-average notation to XdX, also adjutsing for size
			dice: function (paramDblAvgDmg)
			{
				// Table 6-5: Tiny and Large Weapon Damage
				switch(charSheet.getTotal('size')) {
					case 'Medium':
						switch(paramDblAvgDmg) {
							case  3: return '1d2'
							case  4: return '1d3'
							case  5: return '1d4'
							case  7: return '1d6'
							case  9: return '1d8'
							case 10: return '2d4'
							case 11: return '1d10'
							case 13: return '1d12'
							case 14: return '2d6'
							case 15: return '1d8+3'
							case 17: return '1d8+4'
							case 18: return '2d8'
							case 22: return '2d10'
						}
					case 'Small':
						switch(paramDblAvgDmg) {
							case  3: return '1'
							case  4: return '1d2'
							case  5: return '1d3'
							case  7: return '1d4'
							case  9: return '1d6'
							case 10: return '1d6'
							case 11: return '1d8'
							case 13: return '1d10'
							case 14: return '1d10'
							case 18: return '2d6'
							case 22: return '2d8'
						}
				}

				// no match, take best guess
				return 'about ' + paramDblAvgDmg / 2
			},

			// convert cardinal numbers to ordinal form
			ordinal: function (paramCardinal)
			{
				paramCardinal *= 1

				switch(paramCardinal) {
					case 0:   return '0'
					case 1:   return '1st'
					case 2:   return '2nd'
					case 3:   return '3rd'
					default:  return (paramCardinal + 'th')
				}
			},

			// add + sign when positive
			sign: function (paramNumber, paramSkipZero)
			{
				if(paramSkipZero && !paramNumber) {
					return ''
				}
				return (paramNumber < 0) ? paramNumber : '+' + paramNumber
			},

			// format variable based on parameters
			out: function (paramPrefix, paramVariable, paramSuffix, paramMustExist)
			{
				if(paramPrefix === undefined)    paramPrefix = ''
				if(paramVariable === undefined)  paramVariable = ''
				if(paramSuffix === undefined)    paramSuffix = ''
				if(paramMustExist === undefined) paramMustExist = 0

				varRet = paramPrefix + charSheet.trim(paramVariable) + paramSuffix

				// if the variable is zero or blank, and set to mandatory:
				// return nothing, not the prefix nor suffix
				if(paramMustExist && !paramVariable) {
					return ''
				}

				return varRet
			},

			// remove any loose commas, semi-colons and spaces from the string edges
			trim: function (paramString)
			{
				if(paramString && typeof(paramString) === 'string' && paramString.length > 5) {
					if(paramString.trim().substr(0, 1) === ' ' ||
					   paramString.trim().substr(0, 1) === ',' ||
					   paramString.trim().substr(0, 1) === ';') {
						paramString = paramString.trim().substr(1).trim()
					}
					if(paramString.trim().substr(paramString.length - 1, 1) == ' ' ||
					   paramString.trim().substr(paramString.length - 1, 1) == ',' ||
					   paramString.trim().substr(paramString.length - 1, 1) == ';') {
						paramString = paramString.trim().substr(0, -1).trim()
					}
				}

				return paramString
			},

			// update screen with size
			buildSize: function ()
			{
				$('#calcSize').text(charSheet.getTotal('size'))
			},

			// update screen with initiative
			buildInit: function ()
			{
				$('#calcInit').text(10 + charSheet.getTotal('initiative'))
			},

			// update screen with saving throws and bonuses
			buildSaves: function ()
			{
				$('#calcFort').text(10 + charSheet.getTotal('saveFort'))
				$('#calcRef').text(10 + charSheet.getTotal('saveRef'))
				$('#calcWill').text(10 + charSheet.getTotal('saveWill'))
				$('#calcSaveDesc').text(charSheet.out('; ', charSheet.getTotal('save').join(', '), '', 1))
			},

			// update screen with senses and perception
			buildSenses: function ()
			{
				$('#calcSenses').html(charSheet.out('', charSheet.getValue('adjustments.racial_trait.lightSensitivity'), '; ', 1)
				                    + charSheet.getTotal('senses').join('; '))
			},

			// build hit points and description, and update screen
			buildHP: function ()
			{
				// calculate total hp
				tmpHP = charSheet.getTotal('hp')
				      + charSheet.getTotal('hd6')  * 3
				      + charSheet.getTotal('hd8')  * 4
				      + charSheet.getTotal('hd10') * 5
				      + charSheet.getTotal('hd12') * 6

				// calculate hp description
				tmpHPDesc = ''
				tmpHPDesc += charSheet.out(' +', charSheet.getTotal('hd6'), 'd6', 1)
				tmpHPDesc += charSheet.out(' +', charSheet.getTotal('hd8'), 'd8', 1)
				tmpHPDesc += charSheet.out(' +', charSheet.getTotal('hd10'), 'd10', 1)
				tmpHPDesc += charSheet.out(' +', charSheet.getTotal('hd12'), 'd12', 1)
				tmpHPDesc += charSheet.out(' +', charSheet.getValue('adjustments.ability.hp'), ' Con', 1)
				tmpHPDesc += charSheet.out(' +', charSheet.getValue('adjustmnets.feat.hp'), ' feats', 1)
				tmpHPDesc += charSheet.out(' +', charSheet.getValue('bonusHP'), ' favoured class', 1)

				// update screen
				$('#calcHP').text(tmpHP)
				$('#calcHPDesc').html(charSheet.out('(', tmpHPDesc.substr(2), ')', 0)) // substr needed
			},

			// build skill points and description, and update screen
			buildSP: function ()
			{
				// calculate total sp
				tmpSP = charSheet.getTotal('sp')

				// calculate sp description
				tmpSPDesc  = charSheet.getValue('adjustments.class.sp')
				tmpSPDesc += charSheet.out(' +', charSheet.getValue('adjustments.ability.sp'), ' Int', 1)
				tmpSPDesc += charSheet.out(' +', charSheet.getValue('adjustments.feat.sp'), ' feats', 1)
				tmpSPDesc += charSheet.out(' +', charSheet.getValue('adjustments.racial_trait.sp'), ' traits', 1)
				tmpSPDesc += charSheet.out(' +', charSheet.getValue('bonusSP'), ' favoured class', 1)

				// format
				tmpSPDesc = charSheet.getValue('sp_used') + ' / ' + tmpSP + ' (' + tmpSPDesc + ')'

				// update screen
				$('#calcSPDesc').text(tmpSPDesc)
			},

			// build feat points and description, and update screen
			buildFP: function ()
			{
				// calculate total fp
				tmpFP = charSheet.getTotal('fp')

				// calculate fp description
				tmpFPDesc  = charSheet.getValue('adjustments.original.fp')
				tmpFPDesc += charSheet.out(' +', charSheet.getValue('adjustments.racial_trait.fp'), ' traits', 1)
				tmpFPDesc += charSheet.out(' +', charSheet.getValue('adjustments.class.fp'), ' class features', 1)

				// format
				tmpFPDesc = charSheet.getValue('fp_used') + ' / ' + tmpFP + ' (' + tmpFPDesc + ')'

				// update screen
				$('#calcFPDesc').text(tmpFPDesc)
			},

			// build armour class, flat-footed ac and description, and update screen
			buildAC: function ()
			{
				// two-weapon defense (affected by multiple sections)
				tmpTwoWpnDef = 0
				if(charSheet.getValue('offhand')
				&& charSheet.getTotal('twoWeaponDef')) {
					tmpTwoWpnDef ++
				}

				// calculate flat-footed ac
				tmpFlatFootedAC = 10
				                - charSheet.sizeBonus()
				                + charSheet.getTotal('acLuck')
				                + charSheet.getTotal('acInsight')
				                + charSheet.getTotal('acDeflect')
				                + charSheet.getTotal('acWisdom')
				                + charSheet.getTotal('acMonk')

				// split off a copy for touch ac
				tmpTouchAC = tmpFlatFootedAC
				// apply dex penalty only to flat-footed ac
				if(charSheet.getTotal('acDex') < 0) {
					tmpFlatFootedAC += charSheet.getTotal('acDex')
				}
				// calculate touch ac
				tmpTouchAC += charSheet.getTotal('acDex')
				            + charSheet.getTotal('acDodge') + tmpTwoWpnDef

				// calculate ac description
				tmpACDesc = ''
				tmpACDesc += charSheet.out(' +', (-1 * charSheet.sizeBonus()), ' size', 1)
				tmpACDesc += charSheet.out(' +', charSheet.getTotal('acDex'), ' Dex', 1)
				tmpACDesc += charSheet.out(' +', charSheet.getTotal('acDodge') + tmpTwoWpnDef, ' dodge', 1)
				tmpACDesc += charSheet.out(' +', charSheet.getTotal('acLuck'), ' luck', 1)
				tmpACDesc += charSheet.out(' +', charSheet.getTotal('acInsight'), ' insight', 1)
				tmpACDesc += charSheet.out(' +', charSheet.getTotal('acDeflect'), ' deflect', 1)
				tmpACDesc += charSheet.out(' +', charSheet.getTotal('acWisdom'), ' Wis', 1)
				tmpACDesc += charSheet.out(' +', charSheet.getTotal('acMonk'), ' monk', 1)

				// format
				tmpACDesc = charSheet.out('(', tmpACDesc.substr(1), ')', 1) // substr needed

				// update screen
				$('#calcAC').text(tmpTouchAC)
				$('#calcFlatFooted').text(tmpFlatFootedAC)
				$('#calcACDesc').html(tmpACDesc)
			},

			// build combat maneuver defense, and update screen
			buildCMD: function ()
			{
				tmpCMD = charSheet.getTotal('bab')
				       + charSheet.getTotal('cmd')
				       + charSheet.getTotal('acLuck')
				       + charSheet.getTotal('acInsight')
				       + charSheet.getTotal('acDeflect')
				       + charSheet.getTotal('acMonk')
				       + charSheet.getTotal('acWisdom')
				       + charSheet.getTotal('acDodge')

				// update screen
				$('#calcCMD').text(tmpCMD)
			},

			// build combat maneuver bonus, and update screen
			buildCMB: function ()
			{
				tmpCMB = charSheet.getTotal('bab')
				       + charSheet.getTotal('cmb')

				// update screen
				$('#calcCMB').text(tmpCMB)
			},

			// build armour and shield descriptions, and update screen
			buildArmour: function ()
			{
				tmpArDesc = ''

				// check for improved shield bash
				if(!charSheet.getValue('shield') && charSheet.getTotal('impShieldBash')) {
					if(charSheet.getValue('offhand.armour_category') == 'Shield') {
						// copy shield to off-hand weapon
						charSheet.shield = charSheet.getValue('offhand')
						// shield magic does not affect attack or damage
						charSheet.offhand.to_hit_mod = charSheet.offhand.damage_mod = 0;
					}
					else if(charSheet.getValue('melee.armour_category') == 'Shield') {
						// copy shield to melee weapon
						charSheet.shield = charSheet.getValue('melee')
						// shield magic does not affect attack or damage
						charSheet.melee.to_hit_mod = charSheet.melee.damage_mod = 0;
					}
				}

				// calculate shield description
				if(charSheet.getValue('shield')) {
					tmpArDesc += charSheet.out(', ', charSheet.getValue('shield.quality_format').toLowerCase(), ' ')
					tmpArDesc += charSheet.out('', charSheet.getValue('shield.name').toLowerCase(), ' ')
					tmpArDesc += charSheet.out('(DC +', (charSheet.getNumber('shield.armour_dc')
					                                   + charSheet.getNumber('shield.to_hit_mod')
					                                   + charSheet.getTotal('shieldDC')), ', ') // shield focus
					tmpArDesc += charSheet.out('DR ', (charSheet.getNumber('shield.armour_hardness')
					                                 + charSheet.getNumber('shield.to_hit_mod')), ', ')
					tmpArDesc += charSheet.out('hp ', (charSheet.getNumber('shield.armour_hp')), ')')
				}
				else {
					// improvised shield
					tmpArDesc += charSheet.out(', improvised shield (DC +', charSheet.getValue('adjustments.feat.improvisedShield'), ')', 1)
				}

				// calculate armour description
				if(charSheet.getValue('armour')) {
					tmpArDesc += charSheet.out(', ', charSheet.getValue('armour.quality_format').toLowerCase(), ' ')
					tmpArDesc += charSheet.out('', charSheet.getValue('armour.name').toLowerCase(), ' ')
					tmpArDesc += charSheet.out('(DC +' + (charSheet.getNumber('armour.armour_dc')
					                                    + charSheet.getNumber('armour.to_hit_mod')), ', ')
					tmpArDesc += charSheet.out('DR ' + (charSheet.getNumber('armour.armour_hardness')
					                                  + charSheet.getNumber('armour.to_hit_mod')), ', ')
					tmpArDesc += charSheet.out('hp ', charSheet.getNumber('armour.armour_hp'), ')')
				}

				// calculate natural armour description
				tmpArDesc += charSheet.out(', natural armour (DR ', charSheet.getTotal('naturalArmour'), ')', 1)

				// update screen
				$('#calcArmour').html(tmpArDesc.substr(2))
			},

			// build defensive abilities description, and update screen
			buildSD: function ()
			{
				// calculate special deffense
				tmpSDDesc = charSheet.out(', ', charSheet.getTotal('sd').join(', '), '', 1)

				// stackable abilities - just show the latest one

				// uncanny dodge
				if(temp = charSheet.getTotal('uncannyDodge').pop()) {
					tmpSDDesc += ', ' + temp
				}

				// evasion
				if(temp = charSheet.getTotal('evasion').pop()) {
					tmpSDDesc += ', ' + temp
				}

				// update screen
				$('#calcSD').html(charSheet.out('<b>Defensive Abilities</b> ', tmpSDDesc, '', 1))
			},

			// build damage reduction description, and update screen
			buildDR: function ()
			{
				// build damage resistance description
				tmpDRDesc = ''
				tmpDRDesc += charSheet.out(', ', charSheet.getTotal('dr'), '/--', 1)
				tmpDRDesc += charSheet.out(', ', charSheet.getTotal('drChaotic'), '/chaotic', 1)
				tmpDRDesc += charSheet.out(', ', charSheet.getTotal('drColdIron'), '/cold iron', 1)
				tmpDRDesc += charSheet.out(', ', charSheet.getTotal('drEvil'), '/evil', 1)
				tmpDRDesc += charSheet.out(', ', charSheet.getTotal('drLethal'), '/lethal', 1)

				// update screen
				$('#calcDR').html(charSheet.out('<b>DR</b> ', tmpDRDesc.substr(2), '', 1))
			},

			// build immune description, and update screen
			buildImmune: function ()
			{
				$('#calcImmune').html(charSheet.out('<b>Resist</b> ', charSheet.getTotal('resist').join(', '), ' ', 1)
				                    + charSheet.out('<b>Immune</b> ', charSheet.getTotal('immune').join(', '), '', 1))
			},

			// build speed, and update screen
			buildSpeed: function ()
			{
				var varBaseSpeed = charSheet.getTotal('baseSpeed')
				var varArmourMaxDex = varLoadMaxDex = 45 // cap maxiumum dex bonus at 45 (for 100 dex)

				// calculate restriction to movement
				var varIsRestricted = false

				// set armour encumberance
				if(charSheet.getValue('armour')) {

					// get armour max dex
					varArmourMaxDex = charSheet.getValue('armour.armour_max_dex')

					// check if armour type is restrictive
					if(charSheet.getValue('armour.armour_category') == 'Medium' &&
					   charSheet.getTotal('armourTraining') == 0) {
						varIsRestricted = true
					}
					if(charSheet.getValue('armour.armour_category') == 'Heavy' &&
					   charSheet.getTotal('armourTraining') < 2) {
						varIsRestricted = true
					}
				}

				// set load encumbrance
				if(charSheet.getValue('loadType') > 0) {
					varIsRestricted = true
				}

				// conditional speed bonuses

				// if light or medium load:
				if(charSheet.getValue('loadType') < 4) {
					// if no, light or medium armour
					if(!charSheet.getValue('armour') || charSheet.getValue('armour.armour_category') != 'heavy') {
						varBaseSpeed += charSheet.getTotal('speedBaseMEMA')
					}
				}
				// if light load:
				if(charSheet.getValue('loadType') < 1) {
					// if no armour:
					if(!charSheet.getValue('armour')) {
						varBaseSpeed += charSheet.getTotal('speedLENA')
					}
					else {
						// if light armour:
						if(charSheet.getValue('armour.armour_category') == 'light') {
							varBaseSpeed += charSheet.getTotal('speedLELA')
						}
					}
				}

				// calculate encumbrance max dex
				switch(charSheet.getValue('loadType')) {
					case 0:  break
					case 1:  varLoadMaxDex = 5
					         break
					case 2:  varLoadMaxDex = 4
					         break
					case 3:  varLoadMaxDex = 3
					         break
					case 4:  varLoadMaxDex = 2
					         break
					case 5:
					case 6:  varLoadMaxDex = 1
					         break
					default: varLoadMaxDex = 0
				}

				// select base speed or restricted speed
				if(varIsRestricted) {
					// restricted speed
					charSheet.speed = charSheet.reduceSpeed(varBaseSpeed)
				}
				else {
					// base speed
					charSheet.speed = varBaseSpeed
				}

				// update screen
				$('#calcSpd').html(charSheet.out('', charSheet.getValue('speed'), ' ft.', 0)
				                 + charSheet.out(', ', charSheet.getTotal('movement').join(', '), '', 1))
			},

			// compute reduced speed from base speed
			reduceSpeed: function (paramSpeed)
			{
				// if speed cannot be restricted: return full speed
				if(charSheet.getTotal('noSpeedRestrict') > 0) {
					return paramSpeed
				}

				// return basically 2/3 the base speed, rounded to the nearest 5
				switch(paramSpeed) {
					case 120: case 115: return 80
					case 110:           return 75
					case 100: case 105: return 70
					case 95:            return 65
					case 90:  case 85:  return 60
					case 80:            return 55
					case 70:  case 75:  return 50
					case 65:            return 45
					case 60:  case 55:  return 40
					case 50:            return 35
					case 40:  case 45:  return 30
					case 35:            return 25
					case 25:  case 30:  return 20
					case 20:            return 15
					case 15:            return 10
					default:            return Math.ceil(2 * paramSpeed / 3)
				}
			},

			// build melee description, and update screen
			buildMelee: function ()
			{
				var varDescription = ''

				// check for improved shield bash
				if(charSheet.getTotal('impShieldBash')) {
					if(!charSheet.getValue('melee')) {
						// copy shield to melee weapon
						charSheet.melee = charSheet.getValue('shield')
						// shield magic does not affect attack or damage
						charSheet.melee.to_hit_mod = charSheet.melee.damage_mod = 0
					}
					else if(!charSheet.getValue('offhand') && charSheet.getValue('melee.armour_category') != 'Shield') {
						// copy shield to offhand weapon
						charSheet.offhand = charSheet.getValue('shield')
						// shield magic does not affect attack or damage
						charSheet.offhand.to_hit_mod = charSheet.offhand.damage_mod = 0
					}
				}

				// calculate unarmed strike damage for Medium size
				switch(charSheet.getNumber('class_level.MNK')) {
					case '1':
					case '2':
					case '3':  unarmedDmg = 7
					           break
					case '4':
					case '5':
					case '6':
					case '7':  unarmedDmg = 9
					           break
					case '8':
					case '9':
					case '10':
					case '11': unarmedDmg = 11
					           break
					case '12':
					case '13':
					case '14':
					case '15': unarmedDmg = 14
					           break
					case '16':
					case '17':
					case '18':
					case '19': unarmedDmg = 18
					           break
					case '20': unarmedDmg = 22
					           break
					default:   unarmedDmg = 5
				}

				// flurry of blows

				if(charSheet.getTotal('flurryOfBlows') > 0) {
					varDescription += 'flurry of blows '

					// attack
					temp = charSheet.calcAttack('Unarmed Strike', 'monk,close', -2, 0)
					for(i = charSheet.getTotal('bab') + Math.ceil(charSheet.getNumber('class_level.MNK') / 4),
					  tmpDesc = '', k = 0; i > 10; i -= 5, k++) {
						tmpDesc += '/' + (i + temp)
						if(k < charSheet.getTotal('flurryOfBlows')) {
							tmpDesc += '/' + (i + temp)
						}
					}
					varDescription += tmpDesc.substr(1)

					// damage
					varDescription += ' (' + charSheet.dice(unarmedDmg)
					                + charSheet.sign(charSheet.calcDamage('Unarmed Strike', 'monk,close', 0), 1)
					                + ')'
				}

				// main melee weapon

				if(charSheet.getValue('melee')) {
					// if attack method already described: add seperator
					if(varDescription) {
						varDescription += ' or '
					}

					// calculate melee description
					varDescription += charSheet.getValue('melee.quality_format') + ' '
					                + charSheet.getValue('melee.name') + ' '

					// attack
					temp = charSheet.calcAttack(charSheet.getValue('melee.name'),
					                            charSheet.getValue('melee.melee_group'),
					                            charSheet.getNumber('melee.to_hit_mod'), 0)
					for(i = charSheet.getTotal('bab'), tmpDesc = ''; i > 10; i -= 5) {
						tmpDesc += '/' + (i + temp)
					}
					varDescription += tmpDesc.substr(1)

					// damage
					temp = charSheet.getNumber('melee.damage_mod')

					// if weapon is two-handed, or one-handed used with both hands
					if(charSheet.getValue('melee.melee_size') == 'Two-Handed Melee' ||
					  (charSheet.getValue('melee.melee_size') == 'One-Handed Melee' &&
					   !charSheet.getValue('shield') &&
					   !charSheet.getValue('offhand') &&
					   !charSheet.getValue('adjustments.feat.improvisedShield'))) {
					  // add half str bonus (min +1) and power attack bonus
						temp += Math.max(1, Math.floor(charSheet.bonus(charSheet.getTotal('str'), 1) / 2))
						      + charSheet.getTotal('powerAttack') * 1
					}

					// damage bonus calculator
					temp = charSheet.calcDamage(charSheet.getValue('melee.name'), 
					                            charSheet.getValue('melee.melee_group'),
					                            temp)
					varDescription += ' (' + charSheet.dice(charSheet.getNumber('melee.melee_damage'))
					                + charSheet.sign(temp, 1) + '/'
					                + charSheet.calcCritical(charSheet.getValue('melee.name'),
					                                         charSheet.getValue('melee.melee_critical'))
					                + ')'
				}

				// off-hand melee weapon

				if(charSheet.getValue('offhand')) {
					varDescription += ', ' + charSheet.getValue('offhand.quality_format')
					                + ' ' + charSheet.getValue('offhand.name') + ' '

					// attack
					temp = charSheet.calcAttack(charSheet.getValue('offhand.name'),
					                            charSheet.getValue('offhand.melee_group'), 
					                            charSheet.getNumber('offhand.to_hit_mod'), 1)
					for(i = 0, tmpDesc = ''; i < Math.max(1, charSheet.getTotal('twoWeapon')); i++) {
						tmpDesc += '/' + (temp + charSheet.getTotal('bab') - i * 5)
					}
					varDescription += tmpDesc.substr(1)

					// damage (half str bonus with off hand, min -1)
					tmpDamageMod = charSheet.getNumber('offhand.to_hit_mod')

					// half str damage bonus, min -1
					tmpDamageMod -= Math.max(1, Math.floor(charSheet.bonus(charSheet.getTotal('str'), 1) / 2))

					temp = charSheet.calcDamage(charSheet.getValue('offhand.name'),
					                            charSheet.getValue('offhand.melee_group'),
					                            tmpDamageMod)
					varDescription += ' (' + charSheet.dice(charSheet.getNumber('offhand.melee_damage'))
					                + charSheet.sign(temp, 1) + '/'
					                + charSheet.calcCritical(charSheet.getValue('offhand.name'), 
					                                         charSheet.getValue('offhand.melee_critical'))
					                + ')'
				}

				// unarmed strike

				if(!charSheet.getValue('melee') || charSheet.getTotal('unarmed') > 0) {
					// if attack method already described: add seperator
					if(varDescription) {
						varDescription += ' or '
					}

					varDescription += 'unarmed strike '

					// attack
					temp = charSheet.calcAttack('Unarmed Strike', 'monk,close', 0, 0)
					for(i = charSheet.getTotal('bab'), tmpDesc = ''; i > 10; i -= 5) {
						tmpDesc += '/' + (i + temp)
					}
					varDescription += tmpDesc.substr(1)

					// damage
					temp = charSheet.calcDamage('Unarmed Strike', 'monk,close', 0)
					varDescription += ' (' + charSheet.dice(unarmedDmg)
					                + charSheet.sign(temp, 1) + ')'
				}

				// update screen
				$('#calcMelee').html(varDescription.toLowerCase())
			},

			// build ranged description
			buildRanged: function ()
			{
				var varDescription = ''

				// ranged weapon

				if(charSheet.getValue('ranged')) {
					varDescription += charSheet.getValue('ranged.quality_format') +  ' '
					                + charSheet.getValue('ranged.name') + ' '

					// attack
					temp = charSheet.calcAttack(charSheet.getValue('ranged.name'),
					                            charSheet.getValue('ranged.ranged_group'),
					                            charSheet.getNumber('ranged.to_hit_mod'), 0, 1)
					for(i = charSheet.getTotal('bab'), tmpDesc = ''; i > 10; i -= 5) {
						if(charSheet.getNumber('sa_rapid_shot') && i == charSheet.getTotal('bab')) {
							tmpDesc += '/' + (i + temp)
						}
						tmpDesc += '/' + (i + temp)
						// if crossbow:
						if(charSheet.getValue('ranged.name').indexOf('rossbow') > -1) {
							// if heavy, or no rapid reload feat: skip out of loop (only one attack)
							if(charSheet.getValue('ranged.name').indexOf('eavy') > -1
							|| charSheet.getTotal('rapidReload').indexOf(charSheet.getValue('ranged.name')) > -1) {
								i = 0
// TO DO: verify this code ^^^
							}
						}
					}
					varDescription += tmpDesc.substr(1)

					// damage
					temp = charSheet.calcDamage(charSheet.getValue('ranged.name'),
					                            charSheet.getValue('ranged.ranged_group'),
					                            charSheet.getNumber('ranged.damage_mod'), 1)
					varDescription += ' (' + charSheet.dice(charSheet.getNumber('ranged.ranged_damage'))
					                + charSheet.sign(temp, 1) + '/'
					                + charSheet.calcCritical(charSheet.getValue('ranged.name'), 
					                                         charSheet.getValue('ranged.ranged_critical')) + ', '
					                + charSheet.getValue('ranged.ranged_range') + ' ft.)'
				}

				// update screen
				$('#calcRanged').html(charSheet.out('<br><b>Ranged</b> ', varDescription.toLowerCase(), '', 1))
			},

			// build both melee and ranged descriptions
			buildAllAttacks: function ()
			{
				charSheet.buildMelee()
				charSheet.buildRanged()
			},

			// weapon group helper matches weapon training groups to the group of the weapon used
			calcWeaponGroup: function (paramGroup)
			{
				var tmpTotal = 0
				var tmpWeaponGroup = charSheet.getTotal('weaponGroup')

				// weapon training class feature
				if(!charSheet.isEmpty(tmpWeaponGroup)) {
					// loop through each weapon group
					for(var varWeapon in tmpWeaponGroup) {
						// sum totals for each weapon
						if(paramGroup.toLowerCase().indexOf(tmpWeaponGroup[varWeapon].toLowerCase()) != -1) {
							// add a bonus for the weapon group
							tmpTotal += 1
						}
					}
				}

				return tmpTotal
			},

			// attack helper figures combat attack bonuses
			calcAttack: function (paramWeapon, paramGroup, paramBonus, paramIsOffhand, paramIsRanged)
			{
				// calculate attack bonus
				tmpAttackBonus = charSheet.sizeBonus() + paramBonus

				// weapon training group
				tmpAttackBonus += charSheet.calcWeaponGroup(paramGroup)

				// weapon focus
				if(charSheet.getTotal('weaponFocus')) {
					for(i = 0; i < charSheet.getTotal('weaponFocus').length; i++) {
						// if weapon contains weapon focus text: add bonus
						if(paramWeapon.toLowerCase().indexOf(charSheet.getTotal('weaponFocus')[i].toLowerCase()) > -1) {
							tmpAttackBonus++
						}
					}
				}

				// power attack
				tmpAttackBonus -= charSheet.getTotal('powerAttack') * 1

				// ranged only modifiers
				if(paramIsRanged) {
					// ranged attack bonus
					tmpAttackBonus += charSheet.getTotal('rangedAtk')

					// ranged attacks gain dex bonus
					tmpAttackBonus += charSheet.bonus(charSheet.getTotal('dex'))

					// rapid shot
					if(charSheet.getTotal('rapidShot') > 0) {
						tmpAttackBonus  -= 2
					}
				}

				// melee only modifiers
				if(!paramIsRanged) {
					// melee attack bonus
					tmpAttackBonus += charSheet.getTotal('meleeAtk')

					// weapon finesse
					if(charSheet.getTotal('weaponFinesse')) {
						// add dex bonus, modified by encumbrance, to attack
						tmpAttackBonus += charSheet.bonus(charSheet.getTotal('dex'), 1)
					}
					else {
						// add str bonus, modified by encumbrance, to attack
						tmpAttackBonus += charSheet.bonus(charSheet.getTotal('str'), 1)
					}

					// two-weapon fighting
					if(charSheet.getValue('offhand')) {
						// off-hand attack bonus
						tmpAttackBonus += charSheet.getTotal('offhandAtk')

						// if uses a buckler: -1 attack
						if(charSheet.getValue('shield')) {
							tmpAttackBonus--
						}
						// if off-hand weapon is not light: -2 attack
						if(charSheet.getValue('offhand.melee_size').substr(0, 5) != 'Light') {
							tmpAttackBonus -= 2
						}
						// if trained:
						if(charSheet.getTotal('twoWeapon')) {
							tmpAttackBonus -= 2
						}
						// if untrained:
						else {
							tmpAttackBonus -= 4
							if(paramIsOffhand) {
								tmpAttackBonus -= 4
							}
						}
					}
				}

				return tmpAttackBonus
			},

			// damage helper figures combat damage bonuses
			calcDamage: function (paramWeapon, paramGroup, paramBonus, paramIsRanged)
			{
				tmpDamageBonus = paramBonus

				// weapon training group
				tmpDamageBonus += charSheet.calcWeaponGroup(paramGroup)

				// weapon specialization
				if(charSheet.getTotal('weaponSpec')) {
					for(i = 0; i < charSheet.getTotal('weaponSpec').length; i++) {
						// if weapon contains weapon specialization text: add bonus
						if(paramWeapon.toLowerCase().indexOf(charSheet.getTotal('weaponSpec')[i].toLowerCase()) > -1) {
							tmpDamageBonus += 2
						}
					}
				}

				// power attack
				tmpDamageBonus += 2 * charSheet.getTotal('powerAttack') * 1

				// ranged only modifiers
				if(paramIsRanged) {
					// ranged damage bonus
					tmpDamageBonus += charSheet.getTotal('rangedDmg')

					// if size is two-handed: apply strength penalty only
					// (not to crossbows, composite or mighty bows)
					if(charSheet.getValue('ranged.ranged_size') == 'Two-Handed Ranged') {
						if((charSheet.getTotal('str', 1)) < 10 &&
						    paramWeapon.indexOf('omposite') == -1 &&
						    paramWeapon.indexOf('ighty') == -1 &&
						    paramWeapon.indexOf('rossbow') == -1) {
							tmpDamageBonus += charSheet.bonus(charSheet.getTotal('str'), 1)
						}
					}
					else {
						// thrown weapons add str bonus
						tmpDamageBonus += charSheet.bonus(charSheet.getTotal('str'), 1)
					}
				}

				// melee only modifiers
				if(!paramIsRanged) {
					// melee damage bonus
					tmpDamageBonus += charSheet.getTotal('meleeDmg')

					// add strength bonus
					tmpDamageBonus += charSheet.bonus(charSheet.getTotal('str'), 1)

					// two-weapon fighting
					if(charSheet.getValue('offhand')) {
						// off-hand attack bonus
						tmpDamageBonus += charSheet.getTotal('offhandDmg')
					}
				}

				return tmpDamageBonus
			},

			// critical hit helper figures critical range and multiplier
			calcCritical: function (paramWeapon, paramCritical)
			{
				var varRange = 1
				var varWeaponCritList = charSheet.getTotal('weaponCrit')

				// isolate critical range and multiplier
				if(paramCritical.substr(0, 1) == 'x') {
					tmpLeft  = 20
					tmpRight = '-20/' + paramCritical
				}
				else {
					tmpLeft  = 1 * paramCritical.substr(0, 2)
					tmpRight = paramCritical.substr(2)
				}

				// improved critical feat
				if(!charSheet.isEmpty(varWeaponCritList)) {
					// loop through each improved critical
					for(var varWeapon in varWeaponCritList) {
						// check each weapon against the parameter
						if(varWeaponCritList[varWeapon] == paramWeapon) {
							varRange++
						}
					}
				}

				// if critical range is increased:
				if(varRange > 1) {
					return (21 - ((21 - tmpLeft) * varRange)) + tmpRight
				}
				else {
					return paramCritical
				}
			},

			// build special attack description, and update screen
			buildSA: function ()
			{
				var varSADesc = charSheet.getTotal('sa')

				// bbn
				varSADesc += charSheet.out('; ', charSheet.adjustments.class.rageDesc, 
				                           ' ' + charSheet.getTotal('rp') + ' rounds/day', 1)
				varSADesc += charSheet.out('; ', charSheet.adjustments.class.ragePowersDesc, 
				                           ' (' + charSheet.getTotal('ragePowers').join(', ') + ')', 1)

				// brd
				varSADesc += charSheet.out('; ', charSheet.adjustments.class.bardicPerfDesc, 
				                           ' ' + charSheet.getTotal('pp') + ' rounds/day' 
				                           + ' (' + charSheet.getTotal('bardicPerf').join(', ') 
				                           + ')', 1)

				// pal
				varSADesc += charSheet.out('; ', charSheet.adjustments.class.layDesc,
				                           ' ' + charSheet.getTotal('lohp') + '/day ('
				                           + Math.floor(charSheet.getNumber('class_level.PAL') / 2) 
				                           + 'd6)', 1)

				// clr
				varSADesc += charSheet.out('; ', charSheet.adjustments.class.channelDesc,
				                           ', ' + charSheet.getTotal('channel') + ' ' 
				                           + charSheet.getTotal('cp') + '/day (DC ' 
				                           + Math.floor(charSheet.getNumber('class_level.CLR') / 2 
				                                      + charSheet.bonus(charSheet.getTotal('cha')) 
				                                      + charSheet.getTotal('channelDC') 
				                                      + 10)
				                           + ', ' 
				                           + Math.floor((charSheet.getNumber('class_level.CLR') + 1) / 2) 
				                           + 'd6)', 1)

				// mnk
				varSADesc += charSheet.out('; ', charSheet.adjustments.class.kiPoolDesc,
				                           ' ' + charSheet.getTotal('kp') + ' points', 1)

				// ftr
				varSADesc += charSheet.out('; ' + charSheet.adjustments.class.weaponTrainingDesc + ' (',
				                           charSheet.sumList(charSheet.getTotal('weaponGroup')), ')', 1)

				// rgr
				varSADesc += charSheet.out('', charSheet.adjustments.class.quarryDesc, '', 1)
				varSADesc += charSheet.out('' + charSheet.adjustments.class.favouredEnemyDesc + ' (',
				                           charSheet.sumList(charSheet.getTotal('favouredEnemy'), 2), ')', 1)

				// update screen
				varSADesc = charSheet.out('<br><b>Special Attacks</b> ', charSheet.trim(varSADesc), '', 1)
				varSADesc += charSheet.out('<br><b>Spell-Like Abilities</b> ', charSheet.getTotal('spellLike').join(', '), '', 1)
				$('#calcSA').html(varSADesc)
			},

			// reduce a list with repeating entries to a string with a +1 for each entry
			sumList: function(paramList, paramMod)
			{
				// if missing parameters: return
				if(!paramList) return
				if(charSheet.isEmpty(paramList)) return

				var tmpList = {}
				var varRet = []
				var varInc = 1
				if(paramMod == '2') {
					var varInc = 2
				}

				// count duplicates
				for(var j in paramList) {
					if(tmpList[paramList[j]]) {
						tmpList[paramList[j]] += varInc
					}
					else {
						tmpList[paramList[j]] = varInc
					}
				}

				// produce output
				for(j in tmpList) {
					varRet.push(j + ' +' + tmpList[j])
				}

				return varRet.join(', ')
			},

			// build special quality description
			buildSQ: function ()
			{
				// generic sq
				var varSQDesc = charSheet.getTotal('sq').join('; ')

				// brd
				varSQDesc += charSheet.out('; ', charSheet.adjustments.class.versatilePerfDesc,
				                           ' (' + charSheet.getTotal('versatilePerf').join(', ') + ')', 1)

				// clr
				$('#calcDomains').html(charSheet.out('<br><b>', charSheet.adjustments.class.domainsDesc,
				                                     '</b> ' + charSheet.getTotal('domains').join(', ') + '', 1))
				$('#calcDomainPowers').html(charSheet.out(' <b>Domain Powers</b> ', 
				                                          charSheet.getTotal('domainPower').join(', '), '', 1))

				// ftr
				varSQDesc += charSheet.out('; Armour Training +', charSheet.adjustments.class.armourTraining, '', 1)

				// mnk
				if(charSheet.getNumber('class_level.MNK') == 20) {
					varSQDesc += '; ' + charSheet.adjustments.class.slowFallDesc + ' any distance';
				}
				else {
					varSQDesc += charSheet.out('; ', charSheet.adjustments.class.slowFallDesc, 
					                           ' (' + ((charSheet.getTotal('slowFall') + 1) * 10) + ' ft.)', 1)
				}

				// pal
				varSQDesc += charSheet.out('; ', charSheet.adjustments.class.merciesDesc,
				                           ' (' + charSheet.getTotal('mercy').join(', ') + ')', 1)

				// rgr
				varSQDesc += charSheet.out('; ', charSheet.adjustments.class.favouredTerrainDesc,
				                           ' (' + charSheet.sumList(charSheet.getTotal('favouredTerrain'), 2) + ')', 1)

				// rog
				varSQDesc += charSheet.out('; ', charSheet.adjustments.class.rogueTalentsDesc,
				                           ' (' + charSheet.getTotal('rogueTalents').join(', ') + ')', 1)

				// sor
				temp = ''
				temp += charSheet.out(', ', charSheet.getTotal('dragonType'), '', 1)
				temp += charSheet.out(', ', charSheet.getTotal('elementType'), '', 1)
				temp += charSheet.out(' (', charSheet.getTotal('bloodlineNotes').join(', '), ')', 1)
				$('#calcBloodline').html(charSheet.out('<br><b>', charSheet.adjustments.class.bloodlineDesc,
				                                       '</b> ' + charSheet.getTotal('bloodline') + temp, 1))
				$('#editBloodline').html('<br>Bloodline ' + charSheet.edit.bloodline)

				// wiz
				temp = charSheet.out(' (', charSheet.getTotal('schoolNotes').join(', '), ')', 1)
				temp += charSheet.out(' <b>' + charSheet.adjustments.class.oppositionSchoolDesc + '</b> ',
				                      charSheet.getTotal('oppSchool').join(', '), '', 1)
				$('#calcArcaneSchool').html(charSheet.out('<b>', charSheet.adjustments.class.schoolDesc,
				                                          '</b> ' + charSheet.getTotal('arcaneSchool') + temp, 1))
				$('#editArcaneSchool').html(charSheet.out('<br>Arcane School ', charSheet.edit.arcaneSchool, 
				                                          '<br>Opposition Schools '
				                                          + charSheet.edit.oppositionSchools, 1))

				// update screen
				if(varSQDesc) {
					$('#calcSQ').html('<b>SQ</b> ' + charSheet.trim(varSQDesc))
					$('#specialqualitiesSection').show()
				}
				else {
					$('#specialqualitiesSection').hide()
				}
			},

			//
			buildSpells: function ()
			{
				// loop through each class' spell list
				for(var tmpClass in charSheet.getValue('spells')) {
					if(tmpClass != 'DOMAIN') {
						// calculate concentration score
						tmpConc = charSheet.getValue('spells.' + tmpClass + '.concentration')
						        + charSheet.getTotal('concentration')

						// calculate casting level
						tmpCastingLevel = charSheet.getNumber('class_level.' + tmpClass)
						if(tmpClass == 'PAL' || tmpClass == 'RGR') {
							tmpCastingLevel -= 3
						}

						// update title
						$('#calc' + tmpClass + 'Known').html('<br><b>' + charSheet.getValue('spells.' + tmpClass + '.title') 
						                                   + '</b> (CL ' 
						                                   + charSheet.ordinal(tmpCastingLevel) 
						                                   + '; concentration ' + tmpConc + ')')
						$('#edit' + tmpClass + 'Known').html('<br><b>' + charSheet.getValue('spells.' + tmpClass + '.title') + '</b>')

						// update 0-level spells for classes which get them
						if(tmpClass == 'BRD' 
						|| tmpClass == 'CLR' 
						|| tmpClass == 'DRD' 
						|| tmpClass == 'SOR') {
							$('#edit0' + tmpClass + 'Known').html('<br>0-level ' + charSheet.getValue('output0' + tmpClass + 'Known'))
							$('#calc0' + tmpClass + 'Known').html('<br><b>0-level (at will) -</b> ' 
							                                    + charSheet.getValue('sa_0_' + tmpClass + '_known').substr(2))
						}
					}

					// loop through each level
					for(var tmpLevel in charSheet.getValue('spells.' + tmpClass)) {
						// do only for level objects (0 - 9)
						if(tmpLevel < 10) {
							tmpCount = charSheet.getValue('spells.' + tmpClass + '.' + tmpLevel + '.perDay')
							if(charSheet.getTotal('domains').length) {
								tmpCount++
							}

							// if domain spells:
							if(tmpClass == 'DOMAIN') {
								tmpEdit = '<br>' + charSheet.ordinal(tmpLevel) + '-level domain ' 
								        + charSheet.getValue('output' + tmpLevel + tmpClass + 'Known')
								tmpCalc = charSheet.getValue('sa_' + tmpLevel + '_' + tmpClass + '_known')
							}
							// otherwise non-domain spells:
							else {
								tmpEdit = '<br>' + charSheet.ordinal(tmpLevel) + '-level ' 
								        + charSheet.getValue('output' + tmpLevel + tmpClass + 'Known')
								tmpCalc = '<br><b>' + charSheet.ordinal(tmpLevel) + '-level (' + tmpCount + '/day) -</b> ' 
								        + charSheet.getValue('sa_' + tmpLevel + '_' + tmpClass + '_known').substr(2)
							}

							// update screen
							$('#edit' + tmpLevel + tmpClass + 'Known').html(tmpEdit)
							$('#calc' + tmpLevel + tmpClass + 'Known').html(tmpCalc)

						}
					}

					// update screen
					if(tmpClass == 'DRD') {
						tmpClass = 'CLR'
					}
					$('#calc' + tmpClass + 'SpellBlock').show()
					$('#spellsSection').show()
				}
			},

			// return only real value, default set in parameters, or default to blank string
			getValue: function (paramProperty)
			{
				var varSplit = paramProperty.split('.')

				for(var i = 0, varTarget = charSheet; i < varSplit.length; i++) {
					// if paramProperty leads to a non-existant entity
					if(varTarget[varSplit[i]] === null || varTarget[varSplit[i]] === undefined) {
						// property not found
						return
					}
					else {
						// drill down
						varTarget = varTarget[varSplit[i]]
					}
				}

				// paramProperty led to this variable
				return varTarget
			},

			// return the highest priority,  total or concatination of adjustment values
			getTotal: function (paramProperty)
			{
				for(j in charSheet.adjustments) {

					// call getValue for each adjustment object
					varTemp = charSheet.getValue('adjustments.' + j + '.' + paramProperty)

					// for the first loop:
					if(varTotal === undefined) {
						// even if blank, this sets the data type
						if(typeof(varTemp) === 'object') {
							var varTotal = varTemp.slice()
						}
						else {
							var varTotal = varTemp
						}
					}
					else {
						// if not blank / zero
						if(varTemp) {
							// if data type is a number: add together
							if(typeof(varTemp) === 'number') {
								varTotal += 1 * varTemp
							}

							// if data type is a string:
							if(typeof(varTemp) === 'string') {
								// if list: concatenate
								if(varTemp.indexOf(',') > -1 || varTemp.indexOf(';') > -1) {
									varTotal += varTemp
								}
								// otherwise: replace
								else {
									varTotal = varTemp
								}
							}

							// if data type is an object: push together
							if(typeof(varTotal) === 'object') {
								for(var j = 0; j < varTemp.length; j++) {
									varTotal.push(varTemp[j])
								}
							}
						}
					}
				}

				// trim final value
				varTotal = charSheet.trim(varTotal)

				if(varTotal === undefined) {
					return 0
				}
				return varTotal
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

			// return only numeric value, default to zero
			getNumber: function (paramVarName)
			{
				varRet = 1 * charSheet.getValue(paramVarName)
				if(varRet) {
					return varRet
				}
				else {
					return 0
				}
			},

			// set a value by the key / value parameters
			addValue: function (param1, param2, param3, param4, param5)
			{
				// if four-part adjustment (e.g. feat.skill.Perform (act))
				if(param5 !== undefined) {
					// setValue for adjustment
					paramBase = charSheet.adjustments[param1][param2][param3]
					varCheck = 'charSheet.adjustments.' + param1 + '.' + param2 + '.' + param3
					// if leading adjustments does not exist: return fail
					if(!paramBase) {
						return false
					}
					paramKey = param4
					paramValue = param5
				}
				// if three-part adjustment (e.g. feat.skill.Bluff)
				else if(param4 !== undefined) {
					// setValue for adjustment
					paramBase = charSheet.adjustments[param1][param2]
					varCheck = 'charSheet.adjustments.' + param1 + '.' + param2
					// if leading adjustments does not exist: return fail
					if(!paramBase) {
						return false
					}
					paramKey = param3
					paramValue = param4
				}
				// if two-part adjustment (e.g. feat.acDodge)
				else if(param3 !== undefined) {
					// setValue for adjustment
					// if adjustment does not exist: return fail
					if(!charSheet.adjustments[param1]) {
						return false
					}
					paramBase = charSheet.adjustments[param1]
					varCheck = 'charSheet.adjustments.' + param1
					paramKey = param2
					paramValue = param3
				}
				// if one-part adjustment (e.g. charLevel)
				else if(param2 !== undefined) {
					// setValue for main sheet
					paramBase = charSheet
					varCheck = ''
					paramKey = param1
					paramValue = param2
				}
				else {
					// missing parameters
					return false
				}
				if(!paramValue) {
					// missing/blank assignment
					return false
				}

				// if variable does not exist
				if(!paramBase[paramKey]) {
					// initialize
					paramBase[paramKey] = paramValue
					// process change
					if(charSheet.cascadeChange[paramKey]) {
						charSheet[charSheet.cascadeChange[paramKey]]()
					}
				}
				else {
					// number: add together
					if(typeof(paramBase[paramKey]) == 'number') {
						varHistory = paramBase[paramKey]
						paramBase[paramKey] += paramValue
						varCurrent = paramBase[paramKey]
					}

					// string: add together
					if(typeof(paramBase[paramKey]) == 'string') {
						varHistory = paramBase[paramKey]
						paramBase[paramKey] += paramValue
						varCurrent = paramBase[paramKey]
					}

					// object: push on stack
					if(typeof(paramBase[paramKey]) == 'object') {
						varHistory = paramBase[paramKey].length
						paramBase[paramKey].push(paramValue)
						varCurrent = paramBase[paramKey].length
					}

					// process change
					if(charSheet.cascadeChange[paramKey]) {
						console.log('   ' + paramKey + ' is ' + varCurrent + ' was ' + varHistory)
//						charSheet[charSheet.cascadeChange[paramKey]]()
					}
				}

				// return success
				return true
			},

		}

		// adjustments to character sheet by subsection
		function CharSheetAdjustments() {
			// main
			this.cr             = 0
			this.size           = ''
			this.type           = '' // TO DO
			this.initiative     = 0
			this.senses         = []
			// defense
			this.acDex          = 0
			this.acDodge        = 0
			this.acLuck         = 0
			this.acInsight      = 0
			this.acDeflect      = 0
			this.acWisdom       = 0
			this.acMonk         = 0
			this.uncannyDodge   = []
			this.evasion        = []
			this.cmd            = 0
			this.shieldDC       = 0
			this.naturalArmour  = 0
			this.dr             = 0
			this.drColdIron     = 0
			this.drChaotic      = 0
			this.drEvil         = 0
			this.drLethal       = 0
			this.hp             = 0
			this.hd6            = 0
			this.hd8            = 0
			this.hd10           = 0
			this.hd12           = 0
			this.saveFort       = 0
			this.saveRef        = 0
			this.saveWill       = 0
			this.save           = []
			// special defense
			this.resist         = []
			this.immune         = []
			this.sd             = []
			this.slowFall       = 0
			// offense
			this.baseSpeed      = 0
			this.noSpeedRestrict= 0
			this.speedLELA      = 0
			this.speedLENA      = 0
			this.speedBaseMEMA  = 0
			this.movement       = []
			this.armourTraining = 0
			this.bab            = 0
			this.cmb            = 0
			this.meleeAtk       = 0
			this.meleeDmg       = 0
			this.offhandAtk     = 0
			this.offhandDmg     = 0
			this.rangedAtk      = 0
			this.rangedDmg      = 0
			this.rapidReload    = []
			this.weaponFocus    = []
			this.weaponSpec     = []
			this.weaponCrit     = []
			this.weaponGroup    = []
			this.weaponMastery  = ''
			this.powerAttack    = 0
			this.twoWeapon      = 0
			this.impShieldBash  = 0
			this.twoWeaponDef   = 0
			this.unarmed        = 0
			this.flurryOfBlows  = 0
			// special attacks
			this.quiveringPlam  = 0
			this.cp             = 0
			this.channel        = ''
			this.channelDC      = 0
			this.concentration  = 0
			this.pp             = 0
			this.bardicPerf     = []
			this.versatilePerf  = []
			this.rp             = 0
			this.rage           = 0
			this.ragePowers     = []
			this.kp             = 0
			this.domains        = []
			this.domainPower    = []
			this.spellLike      = []
			this.sq             = []
			this.lohp           = 0
			this.mercy          = []
			this.sa             = []
			this.animalCompanion= ''
			this.favouredEnemy  = []
			this.favouredTerrain= []
			this.rogueTalents   = []
			this.bloodline      = ''
			this.bloodlineNotes = []
			this.dragonType     = ''
			this.elementType    = ''
			this.arcaneSchool   = ''
			this.schoolNotes    = []
			this.oppSchool      = []
			// ability scores
			this.str            = 0
			this.dex            = 0
			this.con            = 0
			this.int            = 0
			this.wis            = 0
			this.cha            = 0
			// skills
			this.sp             = 0
			this.classSkill     = []
			this.skill          = []
			this.skillDouble    = []
			// feats
			this.fp             = 0
			// languages
			this.lp             = 0
			// gear
			this.encumbrance    = 0
			this.gold           = 0
/* sample object function
			this.getInfo = function() {
				return this.color + ' ' + this.type + ' apple';
			};
*/
		}
		// adjustments are applied in this order
		charSheet.total = new CharSheetAdjustments()
		charSheet.cascadeChange = {'size':          'buildSize',
			                         'type':          '',
			                         'initiative':    'buildInit',
			                         'senses':        'buildSenses',
			                         'acDex':         'buildAC',
			                         'acDodge':       'buildAC',
			                         'acLuck':        'buildAC',
			                         'acInsight':     'buildAC',
			                         'acDeflect':     'buildAC',
			                         'acWisdom':      'buildAC',
			                         'acMonk':        'buildAC',
			                         'uncannyDodge':  'buildSD',
			                         'evasion':       'buildSD',
			                         'cmd':           'buildCMD',
			                         'shieldDC':      'buildArmour',
			                         'naturalArmour': 'buildArmour',
			                         'dr':            'buildDR',
			                         'drColdIron':    'buildDR',
			                         'drChaotic':     'buildDR',
			                         'drEvil':        'buildDR',
			                         'drLethal':      'buildDR',
			                         'hp':            'buildHP',
			                         'hd6':           'buildHP',
			                         'hd8':           'buildHP',
			                         'hd10':          'buildHP',
			                         'hd12':          'buildHP',
			                         'saveFort':      'buildSaves',
			                         'saveRef':       'buildSaves',
			                         'saveWill':      'buildSaves',
			                         'save':          'buildSaves',
			                         'resist':        'buildImmune',
			                         'immune':        'buildImmune',
			                         'sd':            'buildSD',
			                         'slowFall':      'buildSQ',
			                         'baseSpeed':     'buildSpeed',
			                         'noSpeedRestrict': 'buildSpeed',
			                         'speedLELA':     'buildSpeed',
			                         'speedLENA':     'buildSpeed',
			                         'speedBaseMEMA': 'buildSpeed',
			                         'movement':      'buildSpeed',
			                         'armourTraining': 'buildSpeed',
			                         'cmb':           'buildCMB',
			                         'meleeAtk':      'buildMelee',
			                         'meleeDmg':      'buildMelee',
			                         'offhandAtk':    'buildMelee',
			                         'offhandDmg':    'buildMelee',
			                         'rangedAtk':     'buildRanged',
			                         'rangedDmg':     'buildRanged',
			                         'weaponFocus':   'buildAllAttacks',
			                         'weaponSpec':    'buildAllAttacks',
			                         'weaponCrit':    'buildAllAttacks',
			                         'weaponGroup':   'buildAllAttacks',
			                         'weaponMastery': 'buildAllAttacks',
			                         'twoWeapon':     'buildMelee',
			                         'twoWeaponDef':  'buildAC',
			                         'unarmed':       'buildMelee',
			                         'flurryOfBlows': 'buildMelee',
			                         'quiveringPlam': 'buildSA',
			                         'cp':     '',
			                         'channel':     '',
			                         'channelDC':     '',
			                         'concentration':     '',
			                         'pp':     '',
			                         'bardicPerf':     '',
			                         'versatilePerf':     '',
			                         'rp':     '',
			                         'rage':     '',
			                         'ragePowers':     '',
			                         'kp':     '',
			                         'domains':     '',
			                         'domainPower':     '',
			                         'spellLike':     '',
			                         'sq':     '',
			                         'lohp':     '',
			                         'mercy':     '',
			                         'sa':     '',
			                         'animalCompanion':     '',
			                         'favouredEnemy':     '',
			                         'favouredTerrain':     '',
			                         'rogueTalents':     '',
			                         'bloodline':     '',
			                         'bloodlineNotes':     '',
			                         'dragonType':     '',
			                         'elementType':     '',
			                         'arcaneSchool':     '',
			                         'schoolNotes':     '',
			                         'oppSchool':     '',
			                         'newArcana':     '',
			                         'schoolPower':     '',
			                         'str':     '',
			                         'dex':     '',
			                         'con':     '',
			                         'int':     '',
			                         'wis':     '',
			                         'cha':     '',
			                         'sp':     '',
			                         'classSkill':     '',
			                         'skill':     '',
			                         'skillDouble':     '',
			                         'fp':     '',
			                         'lp':     '',
			                         'encumbrance':     '',
			                         'gold':     '',
			                        }
		// tracking objects
		charSheet.adjustments = {'original':     new CharSheetAdjustments(),
			                       'template':     new CharSheetAdjustments(),
			                       'ability':      new CharSheetAdjustments(),
			                       'racial_trait': new CharSheetAdjustments(),
			                       'class':        new CharSheetAdjustments(),
			                       'feat':         new CharSheetAdjustments(),
			                       'skill':        new CharSheetAdjustments(),
			                       'gear':         new CharSheetAdjustments(),
			                      }
		// edit objects
		charSheet.edit = []

		// calculate main section
		function calcMain()
		{
			console.log('function calcMain()')

			// add feats based on character level
			charSheet.adjustments.original.fp = Math.ceil(charSheet.getNumber('charLevel') / 2)

			// recalculate sections
			calcRacialtraits()
			calcSpecialabilities()

			console.log(' ... CHECK')
		}

		// calculate ability score adjustments
		function calcAbilities()
		{
			console.log('function calcAbilities()')

			// reset ability adjustments
			charSheet.adjustments.ability = new CharSheetAdjustments()

			// encumberance
			// for str above 14: reduce by 5 and double the multiplier
			for(i = 1 * charSheet.getTotal('str'), varMultiplier = 1; i > 14; i -= 5, varMultiplier *= 2) { }
			// calculate base encumbrance for reduced str
			switch (i) {
				case 10: tmpMaxLoad = 100 * varMultiplier
				         break
				case 11: tmpMaxLoad = 115 * varMultiplier
				         break
				case 12: tmpMaxLoad = 130 * varMultiplier
				         break
				case 13: tmpMaxLoad = 150 * varMultiplier
				         break
				case 14: tmpMaxLoad = 175 * varMultiplier
				         break
				default: tmpMaxLoad = i * 10
			}
			// set encumbrance value to one-third the maximum load
			charSheet.addValue('ability', 'encumbrance', tmpMaxLoad / 3)

			// str (with load penalty)
			temp = charSheet.bonus(charSheet.getTotal('str'), 1)
			charSheet.addValue('ability', 'cmd', temp)
			charSheet.addValue('ability', 'cmb', temp)

			// dex (with load penalty)
			temp = charSheet.bonus(charSheet.getTotal('dex'))
			charSheet.addValue('ability', 'acDex', temp)
			charSheet.addValue('ability', 'initiative', temp)
			charSheet.addValue('ability', 'cmd', temp)
			charSheet.addValue('ability', 'saveRef', temp)

			// con
			temp = charSheet.bonus(charSheet.getTotal('con'))
			charSheet.addValue('ability', 'hp', temp * charSheet.getNumber('charLevel'))
			charSheet.addValue('ability', 'saveFort', temp)
			charSheet.addValue('ability', 'rp', temp)

			// int
			temp = charSheet.bonus(charSheet.getTotal('int'))
			charSheet.addValue('ability', 'sp', temp * charSheet.getNumber('charLevel'))
			charSheet.addValue('ability', 'lp', temp)

			// wis
			temp = charSheet.bonus(charSheet.getTotal('wis'))
			charSheet.addValue('ability', 'saveWill', temp)
			charSheet.addValue('ability', 'cp', temp)
			charSheet.addValue('ability', 'kp', temp)

			// cha
			temp = charSheet.bonus(charSheet.getTotal('cha'))
			charSheet.addValue('ability', 'lohp', temp)
			charSheet.addValue('ability', 'pp', temp)

			// if skill list is loaded:
			if(charSheet.getValue('skills.0.name')) {
				// for each skill:
				for(i = 0; i < charSheet.skills.length; i++) {
					// calculate ability score bonus
					charSheet.adjustments.ability.skill[charSheet.getValue('skills.' + i + '.name')]
						= charSheet.bonus(charSheet.getTotal(charSheet.getValue('skills.' + i + '.ability').toLowerCase()))
				}
			}

			// recalculate sections
			calcRacialtraits()
			calcSpecialabilities()
			calcSkills()
			calcDefense()
			calcOffense()

			console.log(' ... CHECK')
		}

		// calculate defense section
		function calcDefense()
		{
			console.log('function calcDefense()')

			charSheet.buildArmour()
			charSheet.buildCMD()
			charSheet.buildHP()
			charSheet.buildSD()
			charSheet.buildDR()
			charSheet.buildImmune()

			console.log(' ... CHECK')
		}

		// calculate offense section
		function calcOffense()
		{
			console.log('function calcOffense()')

			charSheet.buildCMB()
			charSheet.buildMelee()
			charSheet.buildRanged()
			charSheet.buildSA()
			charSheet.buildSpells()

			// update screen
			$('#calcBAB').text(charSheet.getTotal('bab'))

			console.log(' ... CHECK')
		}

		// calculate feats subsection
		function calcFeats()
		{
			console.log('function calcFeats()')

			// reset feat adjustments
			charSheet.adjustments.feat = new CharSheetAdjustments()

			// set default values for feats
			charSheet.fp_used = 0

			// loop through each feat
			for(varFeatLoop = 0; varFeatLoop < charSheet.getValue('feats').length; varFeatLoop++) {
				if(charSheet.getValue('feats.' + varFeatLoop)) {

					varFeatName      = charSheet.getValue('feats.' + varFeatLoop + '.name')
					varFeatDetail    = charSheet.getValue('feats.' + varFeatLoop + '.detail')

					// keep count of feats already selected
					charSheet.fp_used ++

					switch(varFeatName) {

						// regional

						case 'Artist':
							// TO DO: create an 'all' category
							charSheet.addValue('feat', 'skill', 'Perform (act)', 2)
							charSheet.addValue('feat', 'skill', 'Perform (comedy)', 2)
							charSheet.addValue('feat', 'skill', 'Perform (dance)', 2)
							charSheet.addValue('feat', 'skill', 'Perform (keyboard)', 2)
							charSheet.addValue('feat', 'skill', 'Perform (oratory)', 2)
							charSheet.addValue('feat', 'skill', 'Perform (percusison)', 2)
							charSheet.addValue('feat', 'skill', 'Perform (sing)', 2)
							charSheet.addValue('feat', 'skill', 'Perform (string)', 2)
							charSheet.addValue('feat', 'skill', 'Perform (wind)', 2)
							charSheet.addValue('feat', 'skill', 'Craft (' + varFeatDetail + ')', 2)
							charSheet.addValue('feat', 'pp', 3)
							break
						case 'Blooded':
							charSheet.addValue('feat', 'skill', 'Perception', 2)
							charSheet.addValue('feat', 'initiative', 2)
							break
						case 'Bloodline of Fire':
							charSheet.addValue('feat', 'save', '+4 vs. fire')
							charSheet.addValue('feat', 'sa', '+2 CL fire spells')
							break
						case 'Bullheaded':
							charSheet.addValue('feat', 'saveWill', 2)
							charSheet.addValue('feat', 'immune', 'shaken')
							break
						case 'Caravanner':
							charSheet.addValue('feat', 'skill', 'Handle Animal', 2)
							charSheet.addValue('feat', 'skill', 'Knowledge', 'geography', 2)
							break
						case 'Cosmopolitan':
							charSheet.addValue('feat', 'skill', 'Bluff', 2)
							charSheet.addValue('feat', 'skill', 'Diplomacy', 2)
							charSheet.addValue('feat', 'skill', 'Sense Motive', 2)
							break
						case 'Courteous Magocracy':
							charSheet.addValue('feat', 'skill', 'Diplomacy', 2)
							charSheet.addValue('feat', 'skill', 'Spellcraft', 2)
							break
						case 'Dauntless':
							charSheet.addValue('feat', 'hp', 5)
							break
						case 'Daylight Adaptation':
							charSheet.adjustments.racial_trait.lightSensitivity = ''
							break
						case 'Discipline':
							charSheet.addValue('feat', 'saveWill', 2)
							charSheet.addValue('feat', 'concentration', 2)
							break
						case 'Education':
							charSheet.addValue('feat', 'classSkill', 'Knowledge (arcana)', 'Yes')
							charSheet.addValue('feat', 'classSkill', 'Knowledge (dungeoneering)', 'Yes')
							charSheet.addValue('feat', 'classSkill', 'Knowledge (engineering)', 'Yes')
							charSheet.addValue('feat', 'classSkill', 'Knowledge (geography)', 'Yes')
							charSheet.addValue('feat', 'classSkill', 'Knowledge (history)', 'Yes')
							charSheet.addValue('feat', 'classSkill', 'Knowledge (local)', 'Yes')
							charSheet.addValue('feat', 'classSkill', 'Knowledge (nature)', 'Yes')
							charSheet.addValue('feat', 'classSkill', 'Knowledge (nobility)', 'Yes')
							charSheet.addValue('feat', 'classSkill', 'Knowledge (planes)', 'Yes')
							charSheet.addValue('feat', 'classSkill', 'Knowledge (religion)', 'Yes')
							charSheet.addValue('feat', 'skill', 'Knowledge (' + varFeatDetail + ')', 2)
							// workaround: this feat allows two bonus knowledge skills,
							// so do this with two feats. They count as 1/2 a feat each.
							charSheet.addValue('fp_used', -0.5)
							break
						case 'Ethran':
							charSheet.addValue('feat', 'skill', 'Handle Animal', 2)
							charSheet.addValue('feat', 'skill', 'Survival', 2)
							charSheet.addValue('feat', 'sq', '+2 to Cha-based skills dealing with other Rashemis, can participate in circle magic')
							break
						case 'Fearless':
							charSheet.addValue('feat', 'immune', 'fear')
							break
						case 'Foe Hunter':
							charSheet.addValue('feat', 'favouredEnemy', varFeatDetail)
							break
						case 'Forester':
							charSheet.addValue('feat', 'skill', 'Perception', 1)
							charSheet.addValue('feat', 'skill', 'Stealth', 1)
							charSheet.addValue('feat', 'sq', '+2 Perception and Stealth in forest')
							break
						case 'Furious Charge':
							charSheet.addValue('feat', 'sa', '+4 charge attack')
							break
						case 'Genie Lore':
							// You add +1 to the DC of saving throws for any sorcerer spells with the energy type descriptor that you choose: acid, cold, electricity, or fire
							charSheet.addValue('feat', 'sa', '+1 DC ' + varFeatDetail + ' spells')
							break
						case 'Grim Visage':
							charSheet.addValue('feat', 'skill', 'Intimidate', 2)
							charSheet.addValue('feat', 'skill', 'Sense Motive', 2)
							break
						case 'Harem Trained':
							charSheet.addValue('feat', 'skill', 'Perform (act)', 2)
							charSheet.addValue('feat', 'skill', 'Perform (comedy)', 2)
							charSheet.addValue('feat', 'skill', 'Perform (dance)', 2)
							charSheet.addValue('feat', 'skill', 'Perform (keyboard)', 2)
							charSheet.addValue('feat', 'skill', 'Perform (oratory)', 2)
							charSheet.addValue('feat', 'skill', 'Perform (percusison)', 2)
							charSheet.addValue('feat', 'skill', 'Perform (sing)', 2)
							charSheet.addValue('feat', 'skill', 'Perform (string)', 2)
							charSheet.addValue('feat', 'skill', 'Perform (wind)', 2)
							charSheet.addValue('feat', 'skill', 'Diplomacy', 2)
							break
						case 'Horse Nomad':
							// TO DO: You gain Martial Weapon Proficiencies (light lance, scimitar, composite shortbow)
							charSheet.addValue('feat', 'skill', 'Ride', 3)
							break
						case 'Jotunbrud':
							charSheet.addValue('feat', 'sa', 'treat as large for combat maneuvers')
							break
						case 'Knifefighter':
							charSheet.addValue('feat', 'sa', 'no penalty with light weapon in a grapple')
							break
						case 'Luck of Heroes':
							charSheet.addValue('feat', 'saveFort', 1)
							charSheet.addValue('feat', 'saveRef', 1)
							charSheet.addValue('feat', 'saveWill', 1)
							charSheet.addValue('feat', 'acLuck', 1)
							break
						case 'Magic in the Blood':
							charSheet.addValue('feat', 'sa', 'use racial spell-like abilities 3/day')
							break
						case 'Magical Training':
							if(!varFeatDetail) varFeatDetail = 'sor or wiz'
							charSheet.addValue('feat', 'sa', 'cast 0-level arcane spell as ' + varFeatDetail + ' 3/day')
							break
						case 'Mercantile Background':
							charSheet.addValue('feat', 'gold', 300)
							charSheet.addValue('feat', 'sq', 'sell for 75% list value, buy at 75% 1/month')
							break
						case 'Militia':
							// TO DO: You gain proficiency with all martial weapons.
							break
						case 'Mind Over Body':
							charSheet.addValue('feat', 'hp', (1 * varFeatDetail) - charSheet.bonus(charSheet.getTotal('con')))
							// only for arcane spellcasters
							if(charSheet.getNumber('class_level.SOR')
				      || charSheet.getNumber('class_level.WIZ')
				      || charSheet.getNumber('class_level.BRD')) {
								charSheet.addValue('feat', 'acInsight', 1)
							}
							break
						case 'Raumathor Heritor':
							// TO DO: wizard becomes a favored class for you.
							charSheet.addValue('feat', 'skill', 'Knowledge', 'planes', 2)
							charSheet.addValue('feat', 'spellLike', 'detect evil outsiders (3/day, CL ' + charSheet.ordinal(charSheet.getNumber('charLevel')) + ')')
							break
						case 'Resist Poison':
							// TO DO
							break
						case 'Saddleback':
							charSheet.addValue('feat', 'sd', 'use Ride check in place of failed Reflex save')
							break
						case 'Silver Palm':
							charSheet.addValue('feat', 'skill', 'Appraise', 2)
							charSheet.addValue('feat', 'skill', 'Bluff', 2)
							charSheet.addValue('feat', 'skill', 'Sense Motive', 2)
							break
						case 'Smooth Talk':
							// TO DO: You take a -5 penalty if you attempt a Diplomacy check as a full-round action ???
							break
						case 'Snake Blood':
							charSheet.addValue('feat', 'saveRef', 2)
							charSheet.addValue('feat', 'save', '+2 vs. poison')
							break
						case 'Spellwise':
							charSheet.addValue('feat', 'skill', 'Knowledge', 'arcana', 2)
							charSheet.addValue('feat', 'skill', 'Spellcraft', 2)
							charSheet.addValue('feat', 'save', '+2 vs. illusion')
							break
						case 'Stormheart':
							charSheet.addValue('feat', 'skill', 'Acrobatics', 2)
							charSheet.addValue('feat', 'skill', 'Profession', 'sailor', 2)
							charSheet.addValue('feat', 'sd', 'unhampered movement and +1 AC on a boat')
							break
						case 'Street Smart':
							charSheet.addValue('feat', 'skill', 'Diplomacy', 2)
							charSheet.addValue('feat', 'skill', 'Intimidate', 2)
							charSheet.addValue('feat', 'skill', 'Sense Motive', 2)
							break
						case 'Strong Back':
							charSheet.addValue('feat', 'encumbrance', charSheet.getNumber('adjustments.ability.encumbrance') / 3)
							break
						case 'Strong Soul':
							charSheet.addValue('feat', 'saveFort', 1)
							charSheet.addValue('feat', 'saveWill', 1)
							charSheet.addValue('feat', 'save', '+2 vs. death and drain')
							break
						case 'Surefooted':
							charSheet.addValue('feat', 'skill', 'Acrobatics', 2)
							charSheet.addValue('feat', 'skill', 'Climb', 2)
							charSheet.addValue('feat', 'sq', 'unhampered movement on ice and steep slopes')
							break
						case 'Survivor':
							charSheet.addValue('feat', 'saveFort', 2)
							charSheet.addValue('feat', 'skill', 'Survival', 2)
							break
						case 'Tattoo Focus':
							charSheet.addValue('feat', 'sq', '+1 DC and +1 CL to overcome SR for specialized school spells, can participate in Red Wizard circle magic')
							break
						case 'Thug':
							charSheet.addValue('feat', 'skill', 'Appraise', 2)
							charSheet.addValue('feat', 'skill', 'Intimidate', 2)
							charSheet.addValue('feat', 'initiative', 2)
							break
						case 'Thunder Twin':
							charSheet.addValue('feat', 'skill', 'Diplomacy', 2)
							charSheet.addValue('feat', 'skill', 'Intimidate', 2)
							charSheet.addValue('feat', 'sq', 'detect direction of twin sibling')
							break
						case 'Tireless':
							charSheet.addValue('feat', 'immune', 'fatigue')
							charSheet.addValue('feat', 'resist', 'exhaustion')
							break
						case 'Treetopper':
							charSheet.addValue('feat', 'skill', 'Acrobatics', 2)
							charSheet.addValue('feat', 'skill', 'Climb', 2)
							charSheet.addValue('feat', 'sq', 'no penalty while climbing')
							break
						case 'Twin Sword style':
							// TO DO
							break

						// combat

						case 'Agile Maneuvers':
							charSheet.addValue('feat', 'cmb', charSheet.bonus(charSheet.getTotal('dex')) - charSheet.bonus(charSheet.getTotal('str')))
							break
						case 'Combat Agility':
						case 'Combat Expertise':
							charSheet.addValue('feat', 'acDodge', 1 * varFeatDetail)
							charSheet.addValue('feat', 'meleeAtk', -1 * varFeatDetail)
							charSheet.addValue('feat', 'rangedAtk', -1 * varFeatDetail)
							break
						case 'Deadly Aim':
							charSheet.addValue('feat', 'rangedAtk', -1 * varFeatDetail)
							charSheet.addValue('feat', 'rangedDmg', 2 * varFeatDetail)
							break
						case 'Defensive Combat Training':
							charSheet.addValue('feat', 'cmd', charSheet.getNumber('charLevel') - charSheet.getTotal('bab'))
							break
						case 'Dodge':
							charSheet.addValue('feat', 'acDodge', 1)
							break
						case 'Double Slice':
							charSheet.addValue('feat', 'offhandDmg', Math.max(1, Math.floor(charSheet.bonus(charSheet.getTotal('str'), 1) / 2)))
							break
						case 'Improved Critical':
							// TO DO
							charSheet.addValue('feat', 'weaponCrit', varFeatDetail)
							break
						case 'Improved Initiative':
							charSheet.addValue('feat', 'initiative', 4)
							break
						case 'Improved Unarmed Strike':
							charSheet.addValue('feat', 'unarmed', 1)
							break
						case 'Improvised Shield':
						case 'Improvised Shield Mastery':
							// only activate if an item is identified as the shield
							if(varFeatDetail) {
								charSheet.addValue('feat', 'improvisedShield', 1)
								charSheet.addValue('feat', 'acDodge', 1)
							}
							break
						case 'Intimidating Prowess':
							// only if a str bonus exists
							if(charSheet.bonus(charSheet.getTotal('str'), 1) > 0) {
								charSheet.addValue('feat', 'skill.Intimidate', charSheet.bonus(charSheet.getTotal('str'), 1))
							}
							break
						case 'Mounted Combat':
							// TO DO: * Ride 1 rank; Avoid attacks on mount with Ride check
							break
						case 'Mounted Archery':
							// TO DO: * Mounted Combat; Halve the penalty for ranged attacks while mounted
							break
						case 'Point-Blank Shot':
							charSheet.addValue('feat', 'rangedAtk', 1)
							charSheet.addValue('feat', 'rangedDmg', 1)
							break
						case 'Power Attack':
							charSheet.addValue('feat', 'powerAttack', varFeatDetail)
							break
						case 'Rapid Reload':
							charSheet.addValue('feat', 'rapidReload', varFeatDetail)
							break
						case 'Rapid Shot':
							charSheet.addValue('feat', 'rapidShot', 1)
							break
						case 'Greater Shield Focus':
						case 'Shield Focus':
							charSheet.addValue('feat', 'shieldDC', 1)
							break
						case 'Simple Weapon Proficiency':
							// TO DO
							break
						case 'Stunning Fist':
							charSheet.addValue('stunningFistDesc', ', stunning fist (' + Math.floor(charSheet.getNumber('class_level.MNK') + (charSheet.getNumber('charLevel') - charSheet.getNumber('class_level.MNK')) / 4) + '/day, DC ' + (10 + charSheet.getNumber('charLevel') / 2 + charSheet.bonus(charSheet.getTotal('wis'))) + ')')
							break
						case 'Two-Weapon Defense':
							charSheet.addValue('feat', 'twoWeaponDef', 1)
							break
						case 'Improved Two-Weapon Fighting':
						case 'Greater Two-Weapon Fighting':
						case 'Two-Weapon Fighting':
							charSheet.addValue('feat', 'twoWeapon', 1)
							break
						case 'Improved Shield Bash':
							charSheet.addValue('feat', 'impShieldBash', 1)
							break
						case 'Weapon Finesse':
							charSheet.addValue('feat', 'weaponFinesse', 1)
							break
						case 'Greater Weapon Focus':
						case 'Weapon Focus':
							charSheet.addValue('feat', 'weaponFocus', varFeatDetail)
							break
						case 'Greater Weapon Specialization':
						case 'Weapon Specialization':
							// TO DO
							charSheet.addValue('feat', 'weaponSpec', varFeatDetail)
							break

						// other

						// skill
						case 'Acrobatic':
							charSheet.addValue('feat', 'skillDouble', 'Acrobatics', 2)
							charSheet.addValue('feat', 'skillDouble', 'Fly', 2)
							break
						case 'Alertness':
							charSheet.addValue('feat', 'skillDouble', 'Perception', 2)
							charSheet.addValue('feat', 'skillDouble', 'Sense Motive', 2)
							break
						case 'Animal Affinity':
							charSheet.addValue('feat', 'skillDouble', 'Handle Animal', 2)
							charSheet.addValue('feat', 'skillDouble', 'Ride', 2)
							break
						case 'Athletic':
							charSheet.addValue('feat', 'skillDouble', 'Climb', 2)
							charSheet.addValue('feat', 'skillDouble', 'Swim', 2)
							break
						case 'Deceitful':
							charSheet.addValue('feat', 'skillDouble', 'Bluff', 2)
							charSheet.addValue('feat', 'skillDouble', 'Disguise', 2)
							break
						case 'Deft Hands':
							charSheet.addValue('feat', 'skillDouble', 'Disable Device', 2)
							charSheet.addValue('feat', 'skillDouble', 'Sleight of Hand', 2)
							break
						case 'Magical Aptitude':
							charSheet.addValue('feat', 'skillDouble', 'Spellcraft', 2)
							charSheet.addValue('feat', 'skillDouble', 'Use Magic Device', 2)
							break
						case 'Master Craftsman':
							charSheet.addValue('feat', 'sq', 'craft magic items')
							break
						case 'Persuasive':
							charSheet.addValue('feat', 'skillDouble', 'Diplomacy', 2)
							charSheet.addValue('feat', 'skillDouble', 'Intimidate', 2)
							break
						case 'Stealthy':
							charSheet.addValue('feat', 'skillDouble', 'Escape Artist', 2)
							charSheet.addValue('feat', 'skillDouble', 'Stealth', 2)
							break
						case 'Self-Sufficient':
							charSheet.addValue('feat', 'skillDouble', 'Heal', 2)
							charSheet.addValue('feat', 'skillDouble', 'Survival', 2)
							break
						case 'Skill Focus':
							charSheet.addValue('feat', 'skillDouble', varFeatDetail, 3)
							break

						// saving throws
						case 'Iron Will':
							charSheet.addValue('feat', 'saveWill', 2)
							break
						case 'Lightning Reflexes':
							charSheet.addValue('feat', 'saveRef', 2)
							break
						case 'Great Fortitude':
							charSheet.addValue('feat', 'saveFort', 2)
							break

						// extra
						case 'Extra Channel':
							charSheet.addValue('feat', 'cp', 2)
							break
						case 'Extra Ki':
							charSheet.addValue('feat', 'kp', 2)
							break
						case 'Extra Lay On Hands':
							charSheet.addValue('feat', 'lohp', 2)
							break
						case 'Extra Mercy':
							charSheet.addValue('feat', 'mercy', varFeatDetail)
							break
						case 'Extra Performance':
							charSheet.addValue('feat', 'pp', 6)
							break
						case 'Extra Rage':
							charSheet.addValue('feat', 'rp', 6)
							break

						// speed
						case 'Fleet':
							charSheet.addValue('feat', 'speedLELA', 5)
							break

						// miscellany
						case 'Combat Casting':
							charSheet.addValue('feat', 'concentration', 4)
							break
						case 'Improved Channel':
							charSheet.addValue('feat', 'channelDC', 2)
							break
						case 'Toughness':
							charSheet.addValue('feat', 'hp', Math.max(3, charSheet.getNumber('charLevel')))
							break

						// other - to do
	//				case 'Acrobatic Steps': break; // Dex 15, Nimble Moves Ignore 20 feet of difficult terrain when you move
	//				case 'Alignment Channel': break; // Channel energy class feature Channel energy can heal or harm outsiders
	//				case 'Arcane Armor Training': break; // * Armor Proficiency, Light, caster level 3rd Reduce your arcane spell failure chance by 10%
	//				case 'Arcane Armor Mastery': break; // * Arcane Armor Training, Reduce your arcane spell failure chance by 20%
	//				case 'Arcane Strike': break; // * Ability to cast arcane spells +1 damage and weapons are considered magic
	//				case 'Armor Proficiency, Light': break; //  — No penalties on attack rolls while wearing light armor
	//				case 'Armor Proficiency, Medium': break; //  Armor Proficiency, Light No penalties on attack rolls while wearing medium armor
	//				case 'Armor Proficiency, Heavy': break; //  Armor Proficiency, Medium No penalties on attack rolls while wearing heavy armor
	//				case 'Augment Summoning': break; //  Spell Focus (conjuration) Summoned creatures gain +4 Str and Con
	//				case 'Blind-Fight': break; // * — Reroll miss chances for concealment
	//				case 'Improved Bull Rush': break; //* Power Attack +2 bonus on bull rush attempts, no attack of opportunity
	//				case 'Greater Bull Rush': break; //* Improved Bull Rush, base attack bonus +6 Enemies you bull rush provoke attacks of opportunity
	//				case 'Catch Off-Guard': break; // * — No penalties for improvised melee weapons
	//				case 'Channel Smite': break; // * Channel energy class feature Channel energy through your attack
	//				case 'Cleave': break; //* Power Attack Make an additional attack if the first one hits
	//				case 'Great Cleave': break; //* Cleave, base attack bonus +4 Make an additional attack after each attack hits
	//				case 'Combat Reflexes': break; // * Make additional attacks of opportunity
	//				case 'Command Undead': break; //  Channel negative energy class feature Channel energy can be used to control undead
	//				case 'Critical Focus': break; // * Base attack bonus +9: +4 bonus on attack rolls made to confirm critical hits
	//				case 'Bleeding Critical': break; // * Critical Focus, base attack bonus +11 Whenever you score a critical hit, the target takes 2d6 bleed
	//				case 'Blinding Critical': break; // * Critical Focus, base attack bonus +15 Whenever you score a critical hit, the target is blinded
	//				case 'Deafening Critical': break; // * Critical Focus, base attack bonus +13 Whenever you score a critical hit, the target is deafened
	//				case 'Sickening Critical': break; // * Critical Focus, base attack bonus +11 Whenever you score a critical hit, the target is sickened
	//				case 'Staggering Critical': break; // * Critical Focus, base attack bonus +13 Whenever you score a critical hit, the target is staggered
	//				case 'Stunning Critical': break; // * Staggering Critical, base attack bonus +17 Whenever you score a critical hit, the target is stunned
	//				case 'Tiring Critical': break; // * Critical Focus, base attack bonus +13 Whenever you score a critical hit, the target is fatigued
	//				case 'Exhausting Critical': break; // * Tiring Critical, base attack bonus +15 Whenever you score a critical hit, the target is exhausted
	//				case 'Critical Mastery': break; // * Any two critical feats, 14th-level fighter Apply two effects to your critical hits
	//				case 'Dazzling Display': break; //* Weapon Focus Intimidate all foes within 30 feet
	//				case 'Improved Disarm': break; // * Combat Expertise +2 bonus on disarm attempts, no attack of opportunity
	//				case 'Greater Disarm': break; // * Improved Disarm, base attack bonus +6 Disarmed weapons are knocked away from your enemy
	//				case 'Deadly Stroke': break; //* Greater Weapon Focus, Shatter Defenses, Deal double damage plus 1 Con bleed base attack bonus +11
	//				case 'Deflect Arrows': break; //* Dex 13, Improved Unarmed Strike Avoid one ranged attack per round
	//				case 'Diehard': break; //  Endurance Automatically stabilize and remain conscious below 0 hp
	//				case 'Disruptive': break; // * 6th-level fighter Increases the DC to cast spells adjacent to you
	//				case 'Spellbreaker': break; // * Disruptive, 10th-level fighter Enemies provoke attacks if their spells fail
	//				case 'Elemental Channel': break; //  Channel energy class feature Channel energy can harm or heal elementals
	//				case 'Endurance': break; //  — +4 bonus on checks to avoid nonlethal damage
	//				case 'Eschew Materials': break; //  — Cast spells without material components
	//				case 'Exotic Weapon Proficiency': break; // * Base attack bonus +1 No penalty on attacks made with one exotic weapon
	//				case 'Far Shot': break; //* Point-Blank Shot Decrease ranged penalties by half
	//				case 'Improved Feint': break; // * Combat Expertise Feint as a move action
	//				case 'Greater Feint': break; // * Improved Feint, base attack bonus +6 Enemies you feint lose their Dex bonus for 1 round
	//					case 'Improved Grapple': break; //* Dex 13, Improved Unarmed Strike +2 bonus on grapple attempts, no attack of opportunity
	//					case 'Greater Grapple': break; //* Improved Grapple, base attack bonus +6 Maintain your grapple as a move action
	//					case 'Improved Great Fortitude': break; // Great Fortitude Once per day, you may reroll a Fortitude save
	//					case 'Improved Counterspell': break; // — Counterspell with spell of the same school
	//					case 'Improved Familiar': break; // Ability to acquire a familiar, see feat Gain a more powerful familiar
	//					case 'Scorpion Style': break; //* Improved Unarmed Strike Reduce target’s speed to 5 ft.
	//					case 'Gorgon’s Fist': break; //* Scorpion Style, base attack bonus +6 Stagger a foe whose speed is reduced
	//					case 'Medusa’s Wrath': break; //* Gorgon’s Fist, base attack bonus +11 Make 2 extra attacks against a hindered foe
	//					case 'Stunning Fist': break; //* Dex 13, Wis 13, Improved Unarmed Strike, base attack bonus +8 Stun opponent with an unarmed strike
	//					case 'Improvised Weapon Mastery': break; //* Catch Off-Guard or Throw Anything, base attack bonus +8 Make an improvised weapon deadly
	//					case 'Improved Iron Will': break; // Iron Will Once per day, you may reroll a Will save
	//					case 'Leadership': break; // Character level 7th Gain a cohort and followers
	//					case 'Lunge': break; //* Base attack bonus +6 Take a –2 penalty to your AC to attack with reach
	//					case 'Improved Lightning Reflexes': break; // Lightning Reflexes Once per day, you may reroll a Reflex save
	//					case 'Lightning Stance': break; // * Dex 17, Wind Stance, base attack bonus +11 Gain 50% concealment if you move
	//					case 'Martial Weapon Proficiency': break; // — No penalty on attacks made with one martial weapon
	//					case 'Manyshot': break; //* Dex 17, Rapid Shot, base attack bonus +6 Shoot two arrows simultaneously
	//					case 'Moblity': break; // * Dodge +4 AC against attacks of opportunity from movement
	//					case 'Natural Spell': break; // Wis 13, wild shape class feature Cast spells while using wild shape
	//					case 'Nimble Moves': break; // Dex 13 Ignore 5 feet of difficult terrain when you move
	//					case 'Improved Overrun': break; //* Power Attack +2 bonus on overrun attempts, no attack of opportunity
	//					case 'Greater Overrun': break; //* Improved Overrun, base attack bonus +6 Enemies you overrun provoke attacks of opportunity
	//					case 'Penetrating Strike': break; //* Weapon Focus, 12th-level fighter Your attacks ignore 5 points of damage reduction
	//					case 'Greater Penetrating Strike': break; //* Penetrating Strike, 16th-level fighter Your attacks ignore 10 points of damage reduction
	//					case 'Pinpoint Targeting': break; //* Improved Precise Shot, base attack bonus +16 No armor or shield bonus on one ranged attack
	//					case 'Precise Shot': break; //* Point-Blank Shot No penalty for shooting into melee
	//					case 'Improved Precise Shot': break; //* Dex 19, Precise Shot, base attack bonus +11 No cover or concealment chance on ranged attacks
	//					case 'Quick Draw': break; //* Base attack bonus +1 Draw weapon as a free action
	//					case 'Ride-By Attack': break; //* Mounted Combat Move before and after a charge attack while mounted
	//					case 'Run': break; // — Run at 5 times your normal speed
	//					case 'Selective Channeling': break; // Cha 13, channel energy class feature Choose whom to affect with channel energy
	//					case 'Shatter Defenses': break; //* Dazzling Display, base attack bonus +6 Hindered foes are flat-footed
	//					case 'Shield Proficiency': break; // — No penalties on attack rolls when using a shield
	//					case 'Tower Shield Proficiency': break; //* Shield Proficiency No penalties on attack rolls when using a tower shield
	//					case 'Shield Slam': break; //* Improved Shield Bash, Two-Weapon Fighting, Free bull rush with a bash attack base attack bonus +6
	//					case 'Shield Master': break; //* Shield Slam, base attack bonus +11 No two-weapon penalties when attacking with a shield
	//					case 'Greater Shield Focus': break; //* Shield Focus, 8th-level fighter Gain a +1 bonus to your AC when using a shield
	//					case 'Shot on the Run': break; //* Dex 13, Mobility, Point-Blank Shot, base attack bonus +4 ake ranged attack at any point during movement
	//					case 'Snatch Arrows': break; //* Dex 15, Deflect Arrows Catch one ranged attack per round
	//					case 'Spirited Charge': break; //* Ride-By Attack Double damage on a mounted charge
	//					case 'Spring Attack': break; // * Mobility, base attack bonus +4 Move before and after melee attack
	//					case 'Stand Still': break; // * Combat Reflexes Stop enemies from moving past you
	//					case 'Greater Spell Focus': break; // Spell Focus +1 bonus on save DCs for one school
	//					case 'Spell Focus': break; // — +1 bonus on save DCs for one school
	//					case 'Spell Mastery': break; // 1st-level Wizard Prepare some spells without a spellbook
	//					case 'Greater Spell Penetration': break; // Spell Penetration +2 bonus on level checks to beat spell resistance
	//					case 'Spell Penetration': break; // — +2 bonus on level checks to beat spell resistance
	//					case 'Step Up': break; //* Base attack bonus +1 Take a 5-foot step as an immediate action
	//					case 'Strike Back': break; //* Base attack bonus +11 Attack foes that strike you while using reach
	//					case 'Improved Sunder': break; //* Power Attack +2 bonus on sunder attempts, no attack of opportunity
	//					case 'Greater Sunder': break; //* Improved Sunder, base attack bonus +6 Damage from sunder attempts transfers to your enemy
	//					case 'Throw Anything': break; //* — No penalties for improvised ranged weapons
	//					case 'Trample': break; //* Mounted Combat Overrun targets while mounted
	//					case 'Improved Trip': break; // * Combat Expertise +2 bonus on trip attempts, no attack of opportunity
	//					case 'Greater Trip': break; // * Improved Trip, base attack bonus +6 Enemies you trip provoke attacks of opportunity
	//					case 'Turn Undead': break; // Channel positive energy class feature Channel energy can be used to make undead flee
	//					case 'Two-Weapon Rend': break; //* Double Slice, Improved Two-Weapon Fighting, base attack bonus +11  Rend a foe hit by both your weapons
	//					case 'Unseat': break; //* Improved Bull Rush, Mounted Combat Knock opponents from their mounts
	//					case 'Vital Strike': break; //* Base attack bonus +6 Deal twice the normal damage on a single attack
	//					case 'Improved Vital Strike': break; //* Vital Strike, base attack bonus +11 Deal three times the normal damage on a single attack
	//					case 'Greater Vital Strike': break; //* Improved Vital Strike, base attack bonus +16 Deal four times the normal damage on a single attack
	//					case 'Whirlwind Attack': break; // * Dex 13, Combat Expertise, Spring Attack, Make one melee attack against all foes within reach base attack bonus +4
	//					case 'Wind Stance': break; // * Dex 15, Dodge, base attack bonus +6 Gain 20% concealment if you move

	/*
						case 'Brew Potion': break; // Caster level 3rd Create magic potions
						case 'Craft Magic Arms and Armor': break; // Caster level 5th Create magic armors, shields, and weapons
						case 'Craft Rod': break; // Caster level 9th Create magic rods
						case 'Craft Staff': break; // Caster level 11th Create magic staves
						case 'Craft Wand': break; // Caster level 5th Create magic wands
						case 'Craft Wondrous Item': break; // Caster level 3rd Create magic wondrous items
						case 'Forge Ring': break; // Caster level 7th Create magic rings
						case 'Scribe Scroll': break; // Caster level 1st Create magic scrolls
						case 'Empower Spell': break; // — Increase spell variables by 50%
						case 'Enlarge Spell': break; // — Double spell range
						case 'Extend Spell': break; // — Double spell duration
						case 'Heighten Spell': break; // — Treat spell as a higher level
						case 'Maximize Spell': break; // — Maximize spell variables
						case 'Quicken Spell': break; // — Cast spell as a swift action
						case 'Silent Spell': break; // — Cast spell without verbal components
						case 'Still Spell': break; // — Cast spell without somatic components
						case 'Widen Spell': break; // — Double spell area
	*/
					}
				}
			}

			// recalculate sections
			charSheet.buildAC()
			charSheet.buildSQ()
			charSheet.buildFP()
			charSheet.buildSaves()
			calcSkills()
			calcDefense()
			calcOffense()

			console.log(' ... CHECK')
		}

		// calculate skills subsection
		function calcSkills()
		{
			console.log('function calcSkills()')

			// reset skill adjustments
			charSheet.adjustments.skill = new CharSheetAdjustments()

			// set default values for skills
			charSheet.sp_used = 0
			var tmpPerception = 0

			// if skills are loaded: loop through each skill
			if(charSheet.getValue('skills.0.name')) {
				for(varSkillLoop = 0; varSkillLoop < charSheet.skills.length; varSkillLoop++) {

					tmpID           = charSheet.getValue('skills.' + varSkillLoop + '.id')
					tmpSkillName    = charSheet.getValue('skills.' + varSkillLoop + '.name')
					tmpSkillID      = charSheet.getValue('skills.' + varSkillLoop + '.skill_id')
					tmpSkillRanks   = charSheet.getNumber('skills.' + varSkillLoop + '.rank') * 1
					tmpSkillAbility = charSheet.getValue('skills.' + varSkillLoop + '.ability').toLowerCase()
					tmpSkillClasses = charSheet.getValue('skills.' + varSkillLoop + '.class').toLowerCase()

					// keep count of skill ranks already spent
					charSheet.sp_used += tmpSkillRanks

					// calculate ranks
					charSheet.addValue('skill', 'skill', tmpSkillName, tmpSkillRanks)

					// class skills
					for(j = 0; j < charSheet.getValue('total_classes').length; j++) {
						if(tmpSkillClasses.indexOf(charSheet.getValue('total_classes.' + j).toLowerCase()) > -1) {
							charSheet.addValue('skill', 'classSkill', tmpSkillName, 'Yes')
							continue
						}
					}

					// calculate total skill
					tmpSkillTotal = charSheet.getTotal('skill.' + tmpSkillName)
					              + charSheet.getTotal('skillDouble.' + tmpSkillName)
					// feat bonuses double at 10 ranks
					tmpSkillTotal += (tmpSkillRanks > 9) ? charSheet.getTotal('skillDouble.' + tmpSkillName) : 0
					// class skill bonus
					tmpSkillTotal += (charSheet.getTotal('classSkill.' + tmpSkillName) == 'Yes' &&
					                  tmpSkillRanks > 0) ? 3 : 0 

					// TO DO: move to gear section
					// if str- or dex-based skill: armour check penalty applies
					if(tmpSkillAbility == 'str' || tmpSkillAbility == 'dex') {
						// calculate skill check penalty due to encumbrance
						tmpLoadCheckPenalty = charSheet.bonus(charSheet.getTotal(tmpSkillAbility))
						                    - charSheet.bonus(charSheet.getTotal(tmpSkillAbility), 1)
						// apply whichever penalty is greater
						if(!charSheet.getValue('armour.armour_penalty')
						|| tmpLoadCheckPenalty > charSheet.getValue('armour.armour_penalty')) {
							tmpSkillTotal -= tmpLoadCheckPenalty
						}
						else {
							tmpSkillTotal -= charSheet.getValue('armour.armour_penalty')
						}
					}

					// note perception
					if(tmpSkillName == 'Perception') {
						tmpPerception = tmpSkillTotal
					}

					// note linguistics
					if(tmpSkillName == 'Linguistics') {
						charSheet.addValue('skill', 'lp', tmpSkillRanks)
					}

					// update screen
					$('#spantotal' + tmpID).text(10 + tmpSkillTotal)
					$('#total' + tmpID).prop('value', 10 + tmpSkillTotal)
				}
			}

			// guarantee perception is available
			if(!tmpPerception) {
				tmpPerception = charSheet.bonus(charSheet.getTotal('wis'))
			}
			// add perception to senses
			charSheet.addValue('skill', 'senses', 'Perception ' + (10 + tmpPerception))

			// recalculate sections
			charSheet.buildSP()

			console.log(' ... CHECK')
		}

		// calculate languages subsection
		function calcLanguages()
		{
			console.log('function calcLanguages()')

			// set default values for languages
			charSheet.lp_used = 0
			var tmpIlliterateBonus = 0

			for(i = 0; i < charSheet.getValue('languages').length; i++) {
				if(charSheet.getValue('languages.' + i + '.name')) {
					if (charSheet.getValue('languages.' + i + '.name') == 'Illiterate') {
						tmpIlliterateBonus = 1
					}
					else {
						++ charSheet.lp_used
					}
				}
			}

			// calculate language tally and description
			tmpLPDesc  = '(' + charSheet.getNumber('lp_used') + ' / ' 
			             + (2 + tmpIlliterateBonus + charSheet.getTotal('lp'))
			             + ') (2'
			if(tmpIlliterateBonus) {
				tmpLPDesc += ' + 1 illiterate allowance'
			}
			if(charSheet.bonus(charSheet.getTotal('int'))) {
				tmpLPDesc += ' +' + charSheet.bonus(charSheet.getTotal('int')) + ' Int'
			}
			if(charSheet.getValue('adjustments.skill.lp')) {
				tmpLPDesc += ' +' + charSheet.getValue('adjustments.skill.lp') + ' Linguistics'
			}
			if(charSheet.getValue('adjustments.racial_trait.lp')) {
				tmpLPDesc += ' +' + charSheet.getValue('adjustments.racial_trait.lp') + ' traits'
			}
			if(charSheet.getValue('adjustments.class.lp')) {
				tmpLPDesc += ' +' + charSheet.getValue('adjustments.class.lp') + ' features'
			}
			tmpLPDesc += ')'

			$('#calcLPDesc').text(tmpLPDesc)

			console.log(' ... CHECK')
		}

		// calculate racial traits (part of special abilities section)
		function calcRacialtraits()
		{
			// reset racial trait adjustments
			charSheet.adjustments.racial_trait = new CharSheetAdjustments()

			// loop through each racial trait
			if(charSheet.getValue('racial_traits'))
			for(i = 0, varRacialTraits = ''; i < charSheet.getValue('racial_traits').length; i++) {
				if(charSheet.getValue('racial_traits.' + i)) {

					varRacialTraitID          = charSheet.getValue('racial_traits.' + i + '.id')
					varRacialTraitName        = charSheet.getValue('racial_traits.' + i + '.name')
					varRacialTraitDetail      = charSheet.getValue('racial_traits.' + i + '.detail')
					varRacialTraitDescription = charSheet.getValue('racial_traits.' + i + '.description')
					varRacialTraitHTML        = '<span title="' + varRacialTraitDescription + '">' 
					                          + varRacialTraitName + '</span>'

					switch(varRacialTraitName) {

						// main

						// size
						// TO DO: move to later section, so size can still be modified by template, etc.
						case 'Large':
						case 'Medium':
						case 'Small':
							charSheet.addValue('racial_trait', 'size', varRacialTraitName)
							charSheet.addValue('racial_trait', 'skill', 'Stealth', charSheet.sizeBonus() * -4)
							charSheet.addValue('racial_trait', 'skill', 'Fly', charSheet.sizeBonus() * -2)
							charSheet.addValue('racial_trait', 'cmb', charSheet.sizeBonus())
							charSheet.addValue('racial_trait', 'cmd', charSheet.sizeBonus())
							if(varRacialTraitName == 'Small') {
								charSheet.addValue('racial_trait', 'encumbrance', 
								                   charSheet.getValue('adjustments.ability.encumbrance') / -4)
							}
							if(varRacialTraitName == 'Large') {
								charSheet.addValue('racial_trait', 'encumbrance', 
								                   charSheet.getValue('adjustments.ability.encumbrance'))
							}
							break

						// senses
						case 'Darkvision (60 ft.)':
						case 'Darkvision (120 ft.)':
						case 'Low-Light Vision':
							charSheet.addValue('racial_trait', 'senses', varRacialTraitHTML)
							break
						case 'Light Sensitivity':
							charSheet.adjustments.racial_trait.lightSensitivity = varRacialTraitHTML
							break

						// defense

						// saving throws
						case 'Hobbit Luck':
							charSheet.addValue('racial_trait', 'saveFort', 1)
							charSheet.addValue('racial_trait', 'saveRef', 1)
							charSheet.addValue('racial_trait', 'saveWill', 1)
							break
						case 'Fortunate':
							charSheet.addValue('racial_trait', 'saveFort', 2)
							charSheet.addValue('racial_trait', 'saveRef', 2)
							charSheet.addValue('racial_trait', 'saveWill', 2)
							break
						case 'Defensive Training':
							charSheet.addValue('racial_trait', 'sd', 'defensive training (+4 dodge bonus to AC vs. giants)')
							break
						case 'Illusion Resistance':
							charSheet.addValue('racial_trait', 'save', '+2 vs. illusions')
							break
						case 'Hardy':
							charSheet.addValue('racial_trait', 'save', '+2 vs. poison, spells, and spell-like abilities')
							break

						// sd
						case 'Stability':
							charSheet.addValue('racial_trait', 'sd', 'stability (+4 vs. bull rush and trip)')
							break

						// immune
						case 'Elven Immunities':
							charSheet.addValue('feat', 'immune', 'sleep')
							charSheet.addValue('racial_trait', 'save', '+2 vs. enchantments')
							break

						// offense

						// speed
						case 'Normal Speed':
							charSheet.adjustments.racial_trait.baseSpeed = 30
							break
						case 'Slow and Steady':
							charSheet.addValue('racial_trait', 'noSpeedRestrict', 1)
							// continue...
						case 'Slow Speed':
							charSheet.adjustments.racial_trait.baseSpeed = 20
							break
	
						// sa
						case 'Gnome Magic':
							// only if cha is 11 or higher
							if(charSheet.getTotal('cha') > 10) {
								charSheet.addValue('racial_trait', 'spellLike', 'Dancing Lights')
								charSheet.addValue('racial_trait', 'spellLike', 'Ghost Sounds')
								charSheet.addValue('racial_trait', 'spellLike', 'Prestidigitation')
								charSheet.addValue('racial_trait', 'spellLike', 
								                   'Speak with Animals (1/day, CL ' 
								                   + charSheet.ordinal(charSheet.getNumber('charLevel')) + ')')
							}
							break
						case 'Gnome Hatred':
							charSheet.addValue('racial_trait', 'sa', '+1 on attack roles against goblin and reptilian humanoids')
							break
						case 'Shield Dwarf Hatred':
							charSheet.addValue('racial_trait', 'sa', '+1 on attack roles against goblinoid and orc humanoids')
							break

						// feats

						case 'Bonus Feat':
							charSheet.addValue('racial_trait', 'fp', 1)
							break
						case 'Adaptability':
							// TO DO: second favoured class
							break

						// skills

						case 'Skilled':
							charSheet.addValue('racial_trait', 'sp', charSheet.getNumber('charLevel'))
							break
						case 'Keen Senses':
							charSheet.addValue('racial_trait', 'skill', 'Perception', 2)
							break
						case 'Sure-Footed':
							charSheet.addValue('racial_trait', 'skill', 'Acrobatics', 2)
							charSheet.addValue('racial_trait', 'skill', 'Climb', 2)
							break
						case 'Svirfneblin Skill':
							charSheet.addValue('racial_trait', 'skill', 'Stealth', 2)
							charSheet.addValue('racial_trait', 'skill', 'Craft (alchemy)', 2)
							charSheet.addValue('racial_trait', 'skill', 'Perception', 2)
							break
						case 'Intimidating':
							charSheet.addValue('racial_trait', 'skill', 'Intimidate', 2)
							break
						case 'Obsessive':
							charSheet.addValue('racial_trait', 'skill', varRacialTraitDetail, 2)
							charSheet.addValue('racial_trait', 'sq', varRacialTraitHTML + ' (' + varRacialTraitDetail + ')')
							// edit
							$('#editRacialTraits').html('Obsession <input type=\'text\' id=\'racialtrait' + varRacialTraitID + '\' value="' + varRacialTraitDetail + '"><br>')
							break

						// languages

						case 'Racial Language':
							charSheet.addValue('racial_trait', 'lp', 1)
							break

						// sq

						case 'Elven Magic':
						case 'Greed':
						case 'Stonecunning':
							charSheet.addValue('racial_trait', 'sq', varRacialTraitHTML)
							break
					}

					// add to list for racial traits (diagnostic section)
					varRacialTraits += ', ' + varRacialTraitHTML
				}

				// update screen (diagnostic section)
				$('#calcRacialTraits').html(varRacialTraits.substr(2))
			}
		}

		// calculate special abilities section
		function calcSpecialabilities()
		{
			console.log('function calcSpecialabilities()')

			// reset class feature adjustments
			charSheet.adjustments.class = new CharSheetAdjustments()
			charSheet.adjustments.class.sp = charSheet.getValue('baseSP') + charSheet.getValue('bonusSP')
			charSheet.adjustments.class.hp = charSheet.getValue('bonusHP')
			charSheet.adjustments.class.bab = 10 + charSheet.getValue('totalBAB')

			charSheet.edit = []

			// build spell options
			if(charSheet.spell_list) {
				// for each class
				for(j in charSheet.spell_list) {
					if(charSheet.spell_list[j]) {
						// for each level
						for(i = 0; i < 10; i++) {
							// define spell variables
							charSheet['sa_' + i + '_' + j + '_known'] = ''
							charSheet['output' + i + j + 'Known'] = ''
							charSheet['output' + i + j + 'List'] = ''

							// for each spell: add option
							for(var key in charSheet.spell_list[j][i]) {
								charSheet['output' + i + j + 'List'] += '<option value="' + key + '">' 
								                                      + charSheet.spell_list[j][i][key] 
								                                      + '</option>'
							}
						}
					}
				}
			}

			// loop through each class feature (contains class_feature_id, id, name, detail, description)
			for(i = 0; i < charSheet.getValue('class_features').length; i++) {
				if(charSheet.class_features[i]) {

				//varClassFeatureID = charSheet.getValue('class_features.' + i + '.class_feature_id')
					varClassFeatureID = charSheet.getValue('class_features.' + i + '.id')
					varClassFeatureName = charSheet.getValue('class_features.' + i + '.name')
					varClassFeatureDetail = charSheet.getValue('class_features.' + i + '.detail')
					varClassFeatureDescription = charSheet.getValue('class_features.' + i + '.description')

					// calculate class feature HTML description
					if(varClassFeatureDescription) {
						varClassFeatureHTML = '<span title="' + varClassFeatureDescription + '">' 
						                    + varClassFeatureName + '</span>'
					}
					else {
						varClassFeatureHTML = varClassFeatureName
					}

					tmpSpellClass = ''
					tmpSpellLevel = -2

					// handle non-spell class features
					if(tmpSpellLevel == -2) {
						switch(varClassFeatureName) {

							// ignore

							case 'Weapon and Armor Proficiency':
							case 'Indomitable Will':
							case 'Spells':
							case 'Cantrips':
							case 'Orisons':
							case 'Bonus Languages':
							case 'Well-Versed':
								break

							// defensive

							// ac
							case 'AC Bonus':
								// only if a wis bonus exists
								if(charSheet.getTotal('wis') > 11) {
									charSheet.addValue('class', 'acWisdom', charSheet.bonus(charSheet.getTotal('wis')))
								}
								charSheet.addValue('class', 'acMonk', Math.floor((charSheet.getNumber('class_level.MNK')) / 4))
								break

							// cmd
							case 'maneuver training':
								charSheet.addValue('class', 'cmb', charSheet.getNumber('charLevel') - charSheet.getTotal('bab'))
								break

							// saving throws
							case 'Resist Nature\'s Lure':
								charSheet.addValue('class', 'save', '+4 vs. fey and plant-targeted effects')
								break
							case 'Bravery':
								charSheet.addValue('class', 'save', 
								                   '+' + Math.floor((charSheet.getNumber('class_level.FTR') + 2) / 4) 
								                   + ' vs. fear')
								break
							case 'Still Mind':
								charSheet.addValue('class', 'save', '+2 vs. enchantments')
								break
							case 'Divine Grace':
								charSheet.addValue('class', 'sq', varClassFeatureHTML)
								// only if a wis bonus exists
								if(charSheet.getTotal('cha') > 11) {
									charSheet.addValue('class', 'saveFort', charSheet.bonus(charSheet.getTotal('cha')))
									charSheet.addValue('class', 'saveRef', charSheet.bonus(charSheet.getTotal('cha')))
									charSheet.addValue('class', 'saveWill', charSheet.bonus(charSheet.getTotal('cha')))
								}
								break

							// defensive abilities
							case 'Uncanny Dodge':
							case 'Improved Uncanny Dodge':
								charSheet.addValue('class', 'uncannyDodge', varClassFeatureHTML)
								break
							case 'Trap Sense':
								temp = 0
								if(charSheet.getNumber('class_level.BBN')) {
									temp += Math.floor(charSheet.getNumber('class_level.BBN') / 3)
								}
								if(charSheet.getNumber('class_level.ROG')) {
									temp += Math.floor(charSheet.getNumber('class_level.ROG') / 3)
								}
								charSheet.addValue('class', 'sd', varClassFeatureHTML + ' +' + temp)
								break
							case 'Evasion':
							case 'Improved Evasion':
								charSheet.addValue('class', 'evasion', varClassFeatureHTML)
								break
							case 'Aura of Justice':
							case 'Aura of Faith':
								charSheet.addValue('class', 'sd', varClassFeatureHTML + ' (10 ft.)')
								break
							case 'Camouflage':
							case 'Hide in Plain Sight':
								charSheet.addValue('class', 'sd', varClassFeatureHTML)
								break

							// damage reduction
							case 'Damage Reduction':
								charSheet.addValue('class', 'dr', 1)
								break
							case 'Armor Mastery':
								charSheet.addValue('class', 'dr', 5)
								break
							case 'Perfect Self':
								charSheet.addValue('class', 'type', 'Outsider')
								charSheet.addValue('class', 'drChaotic', 10)
								break
							case 'Holy Champion':
								charSheet.addValue('class', 'drEvil', 10)
								break

// TO DO:		// spell resistance

							case 'Spell Resistance':
								charSheet.addValue('class', 'save', 
								                   (10 + charSheet.getNumber('class_level.MNK')) 
								                   + ' ' + varClassFeatureHTML)
								break

// TO DO: resistance

							// immune
							case 'Venom Immunity':
								charSheet.addValue('class', 'immune', 'poison')
								break
							case 'Immune to Disease':
								charSheet.addValue('class', 'immune', 'disease')
								break
							case 'Aura of Courage':
								charSheet.addValue('class', 'immune', 'fear')
								charSheet.addValue('class', 'sd', varClassFeatureHTML + ' (10 ft.)')
								break
							case 'Aura of Resolve':
								charSheet.addValue('class', 'immune', 'charm')
								charSheet.addValue('class', 'sd', varClassFeatureHTML + ' (10 ft.)')
								break
							case 'Aura of Righteousness':
								charSheet.addValue('class', 'immune', 'compulsion')
								charSheet.addValue('class', 'drEvil', 5)
								charSheet.addValue('class', 'sd', varClassFeatureHTML + ' (10 ft.)')
								break

							// offense

							// speed
							case 'Fast Movement':
								charSheet.addValue('class', 'sq', varClassFeatureHTML)
								charSheet.addValue('class', 'speedBaseMEMA', 10)
								break
							case 'Fast Unencumbered Movement':
								if(charSheet.getValue('adjustments.class.speedLENA') == 0) {
									charSheet.addValue('class', 'sq', varClassFeatureHTML)
								}
								charSheet.addValue('class', 'speedLENA', 10)
								break

							// sa - bbn rage
							case 'Rage':
								charSheet.addValue('class', 'rp', 2 * charSheet.getNumber('class_level.BBN') + 2)
							case 'Greater Rage':
							case 'Mighty Rage':
								charSheet.addValue('class', 'rage', 1)
								charSheet.adjustments.class.rageDesc = '; ' + varClassFeatureHTML
								break
							case 'Rage Powers':
								charSheet.addValue('class', 'ragePowers', varClassFeatureDetail)
								charSheet.adjustments.class.ragePowersDesc = varClassFeatureHTML
								tmpRagePowerOptions = ['Animal Fury',
								                       'Guarded Stance',
								                       'Intimidating Glare',
								                       'Knockback',
								                       'Low-Light Vision',
								                       'Moment of Clarity',
								                       'Night Vision',
								                       'No Escape',
								                       'Powerful Blow',
								                       'Quick Reflexes',
								                       'Raging Climber',
								                       'Raging Leaper',
								                       'Raging Swimmer',
								                       'Rolling Dodge',
								                       'Roused Anger',
								                       'Scent',
								                       'Strength Surge',
								                       'Superstition',
								                       'Surprise Accuracy',
								                       'Swift Foot']
								if(charSheet.getNumber('class_level.BBN') > 3) {
									tmpRagePowerOptions.push('--- 4th level ---')
									tmpRagePowerOptions.push('Renewed Vigor')
									if(charSheet.getNumber('class_level.BBN') > 7) {
										tmpRagePowerOptions.push('--- 8th level ---')
										tmpRagePowerOptions.push('Clear Mind')
										tmpRagePowerOptions.push('Increased DR')
										tmpRagePowerOptions.push('Internal Fortitude')
										tmpRagePowerOptions.push('Terrifying Howl')
										tmpRagePowerOptions.push('Unexpected Strike')
										if(charSheet.getNumber('class_level.BBN') > 11) {
											tmpRagePowerOptions.push('--- 12th level ---')
											tmpRagePowerOptions.push('Fearless Rage')
											tmpRagePowerOptions.push('Mighty Swing')
										}
									}
								}
								tempRagePowersMenu = buildDropDown('classfeature' + varClassFeatureID, 
								                                   varClassFeatureDetail, 
								                                   tmpRagePowerOptions)
								if(charSheet.edit.ragePowers) {
									charSheet.edit.ragePowers += tempRagePowersMenu
								}
								else {
									charSheet.edit.ragePowers = tempRagePowersMenu
								}
								break
							case 'Tireless Rage':
								// TO DO
								break

							// sa - brd
							case 'Bardic Performance':
								charSheet.adjustments.class.bardicPerfDesc = varClassFeatureHTML
								charSheet.addValue('class', 'pp', 
								                   2 * charSheet.getNumber('class_level.BRD') + 2 
								                   + charSheet.bonus(charSheet.getTotal('cha')))
								break
							case 'Fascinate':
							case 'Suggestion':
							case 'Frightening Tune':
							case 'Deadly Performance':
								charSheet.addValue('class', 'bardicPerf', 
								                   varClassFeatureHTML + ' (DC ' 
								                   + Math.floor(10 + charSheet.bonus(charSheet.getTotal('cha')) 
								                   + charSheet.getNumber('class_level.BRD') / 2) + ')')
								break
							case 'Inspire Courage':
								charSheet.addValue('class', 'bardicPerf', 
								                   varClassFeatureHTML + ' +' 
								                   + Math.floor(1 + (1 * charSheet.getNumber('class_level.BRD') + 1) / 6))
								break
							case 'Inspire Competence':
								charSheet.addValue('class', 'bardicPerf', 
								                   varClassFeatureHTML + ' +' 
								                   + Math.floor(1 + (1 * charSheet.getNumber('class_level.BRD') + 1) / 4))
								break
							case 'Inspire Greatness':
								charSheet.addValue('class', 'bardicPerf', 
								                   varClassFeatureHTML + ' (' 
								                   + Math.floor((charSheet.getNumber('class_level.BRD') - 6) / 3) 
								                   + ' targets)')
								break
							case 'Inspire Heroics':
								charSheet.addValue('class', 'bardicPerf', 
								                   varClassFeatureHTML + ' (' 
								                   + Math.floor((charSheet.getNumber('class_level.BRD') - 12) / 3) 
								                   + ' targets)')
								break
							case 'Countersong':
							case 'Distraction':
							case 'Dirge of Doom':
							case 'Soothing Performance':
							case 'Mass Suggestion':
								charSheet.addValue('class', 'bardicPerf', varClassFeatureHTML)
								break

							// sa - mnk
							case 'Ki Pool':
								charSheet.adjustments.class.kiPoolDesc = varClassFeatureHTML
								charSheet.addValue('class', 'kp', Math.floor(charSheet.getNumber('class_level.MNK') / 2))
								break

							// sa - bloodline
							case 'Bloodline':
								charSheet.adjustments.class.bloodlineDesc = varClassFeatureHTML
								charSheet.addValue('class', 'bloodline', varClassFeatureDetail)
								// edit
								charSheet.edit.bloodline = buildDropDown('classfeature' + varClassFeatureID, 
								                                         varClassFeatureDetail, 
								                                         ['Aberrant',
								                                          'Abyssal',
								                                          'Arcane',
								                                          'Celestial',
								                                          'Destined',
								                                          'Draconic',
								                                          'Elemental',
								                                          'Fey',
								                                          'Infernal',
								                                          'Undead'
								                                         ])
								// details
								// TO DO: check if the bonus spells known for sorcerers are being added
								varSorLvl = charSheet.getNumber('class_level.SOR')
								if(varClassFeatureDetail == 'Aberrant') {
									// TO DO: skill list should not be divided into class and non-class skills until later
									charSheet.addValue('class', 'classSkill', 'Knowledge (dungeoneering)', 'Yes')
									charSheet.addValue('class', 'bloodlineNotes', 
									                   'Bloodline Arcana (+50% duration for polymorph spells)')
									charSheet.addValue('class', 'bloodlineNotes', 
									                   'Acidic Ray ' + (3 + 1 * charSheet.bonus(charSheet.getTotal('cha'))) 
									                   + '/day (1d6+' + Math.floor(varSorLvl / 2) + ')')
									if(varSorLvl > 2) {
										charSheet.addValue('class', 'bloodlineNotes', 
										                   'Long Limbs (+' + Math.max(5, Math.floor((varSorLvl + 1) / 6) * 5) 
										                   + ' ft. range melee touch attacks)')
									}
									if(varSorLvl > 8 && varSorLvl < 20) {
										charSheet.addValue('class', 'bloodlineNotes', 
										                   'Unusual Anatomy (' + (Math.floor(varSorLvl / 13) * 25 + 25) 
										                   + '% chance ignore critical hit or sneak attack)')
									}
									if(varSorLvl > 14) {
										charSheet.addValue('class', 'bloodlineNotes', 
										                   'Alien Resistance (SR ' + (10 + varSorLvl)+ ')')
									}
									if(varSorLvl == 20) {
										charSheet.addValue('class', 'bloodlineNotes', 'Aberrant Form')
										charSheet.addValue('class', 'immune', 'critical hits')
										charSheet.addValue('class', 'immune', 'sneak attacks')
										charSheet.addValue('class', 'senses', 'Blindsight (60 ft.)')
										charSheet.addValue('class', 'dr', Math.max(charSheet.getTotal('dr'), 5))
									}
								}
								if(varClassFeatureDetail == 'Abyssal') {
									charSheet.addValue('class', 'classSkill', 'Knowledge (planes)', 'Yes')
									charSheet.addValue('class', 'bloodlineNotes', 
									                   'Bloodline Arcana (summoned creatures gain DR ' 
									                   + Math.max(1, Math.floor(varSorLvl / 2)) + '/--)')
									temp = ''
									if(varSorLvl > 10) {
										temp += 'Flaming '
									}
									else if(varSorLvl > 4) {
										temp += 'Magic '
									}
									temp += 'Claws ' + (3 + 1 * charSheet.bonus(charSheet.getTotal('cha'))) + '/day ('
									if(varSorLvl < 7) {
										temp += charSheet.dice('5')
									}
									else {
										temp += charSheet.dice('7')
									}
									if(charSheet.bonus(charSheet.getTotal('str'), 1)) {
										temp += '+' + charSheet.bonus(charSheet.getTotal('str'), 1)
									}
									if(varSorLvl > 10) {
										temp += '+1d6'
									}
									temp += ')'
									charSheet.addValue('class', 'bloodlineNotes', temp)
									if(varSorLvl > 2 && varSorLvl < 20) {
										charSheet.addValue('class', 'bloodlineNotes', 'Demon Resistance')
										if(varSorLvl < 9) {
											charSheet.addValue('class', 'resist', 'electricity 5')
										}
										else {
											charSheet.addValue('class', 'resist', 'electricity 10')
										}
										if(varSorLvl < 9) {
											charSheet.addValue('class', 'save', '+2 vs. poison')
										}
										else {
											charSheet.addValue('class', 'save', '+4 vs. poison')
										}
									}
									if(varSorLvl > 8) {
										charSheet.addValue('class', 'bloodlineNotes', 
										                   'Strength of the Abyss +' + (Math.floor((varSorLvl - 1)/ 6) * 2))
									}
									if(varSorLvl > 14) {
										charSheet.addValue('class', 'bloodlineNotes', 'Added Summonings')
									}
									if(varSorLvl == 20) {
										charSheet.addValue('class', 'bloodlineNotes', 'Demonic Might (telepathy 60 ft.)')
										charSheet.addValue('class', 'immune', 'electricity')
										charSheet.addValue('class', 'immune', 'poison')
										charSheet.addValue('class', 'resist', 'acid 10')
										charSheet.addValue('class', 'resist', 'cold 10')
										charSheet.addValue('class', 'resist', 'fire 10')
									}
								}
								if(varClassFeatureDetail == 'Arcane') {
									charSheet.addValue('class', 'classSkill', 'Knowledge (arcana)', 'Yes')
									charSheet.addValue('class', 'bloodlineNotes', 
									                   'Bloodline Arcana (+1 DC for metamagic spells)')
									charSheet.addValue('class', 'bloodlineNotes', 'Arcane Bond')
									if(varSorLvl > 2 && varSorLvl < 20) {
										charSheet.addValue('class', 'bloodlineNotes', 
										                   'Metamagic Adept ' + Math.floor((varSorLvl + 1)/ 4) + '/day')
									}
									if(varSorLvl > 8) {
										charSheet.addValue('class', 'bloodlineNotes', 
										                   'New Arcana +' + Math.floor((varSorLvl - 5)/ 4))
									}
									if(varSorLvl > 14) {
										charSheet.addValue('class', 'bloodlineNotes', 
										                   'School Power (+2 DC for one arcane school)')
									}
									if(varSorLvl == 20) {
										charSheet.addValue('class', 'bloodlineNotes', 'Arcane Apotheosis')
									}
								}
								if(varClassFeatureDetail == 'Celestial') {
									charSheet.addValue('class', 'classSkill', 'Heal', 'Yes')
									charSheet.addValue('class', 'bloodlineNotes', 
									                   'Bloodline Arcana (summoned creatures gain DR ' 
									                   + Math.max(1, Math.floor(varSorLvl / 2)) + '/evil)')
									charSheet.addValue('class', 'bloodlineNotes', 
									                   'Heavenly Fire ' + (3 + 1 * charSheet.bonus(charSheet.getTotal('cha'))) 
									                   + '/day (1d4+' + Math.floor(varSorLvl / 2) + ' vs. evil, heal good)')
									if(varSorLvl > 2 && varSorLvl < 20) {
										charSheet.addValue('class', 'bloodlineNotes', 'Celestial Resistances')
										if(varSorLvl < 9) {
											charSheet.addValue('class', 'resist', 'acid 5')
											charSheet.addValue('class', 'resist', 'cold 5')
										}
										else {
											charSheet.addValue('class', 'resist', 'acid 10')
											charSheet.addValue('class', 'resist', 'cold 10')
										}
									}
									if(varSorLvl > 8 && varSorLvl < 20) {
										charSheet.addValue('class', 'bloodlineNotes', 
										                   'Wings of Heaven ' + Math.floor(varSorLvl / 2) + 'minutes/day')
									}
									if(varSorLvl > 14) {
										charSheet.addValue('class', 'bloodlineNotes', 'Conviction')
									}
									if(varSorLvl == 20) {
										charSheet.addValue('class', 'bloodlineNotes', 'Wings of Heaven (unlimited), Ascension')
										charSheet.addValue('class', 'immune', 'acid')
										charSheet.addValue('class', 'immune', 'cold')
										charSheet.addValue('class', 'immune', 'petrification')
										charSheet.addValue('class', 'resist', 'electricity 10')
										charSheet.addValue('class', 'resist', 'fire 10')
										charSheet.addValue('class', 'save', '+4 vs. poison')
									}
								}
								if(varClassFeatureDetail == 'Destined') {
									charSheet.addValue('class', 'classSkill', 'Knowledge (history)', 'Yes')
									charSheet.addValue('class', 'bloodlineNotes', 
									                   'Bloodline Arcana (personal spells grant saving throw bonus)')
									charSheet.addValue('class', 'bloodlineNotes', 
									                   'Touch of Destiny ' + (3 + 1 * charSheet.bonus(charSheet.getTotal('cha'))) 
									                   + '/day (grant +' + Math.floor(varSorLvl / 2) + ' insight bonus)')
									if(varSorLvl > 2) {
										charSheet.addValue('class', 'bloodlineNotes', 
										                   'Fated (+' + Math.floor((varSorLvl + 1) / 4) 
										                   + ' to saving throws and AC during surprise rounds)')
									}
									if(varSorLvl > 8) {
										charSheet.addValue('class', 'bloodlineNotes', 
										                   'It Was Meant to Be ' + Math.floor((varSorLvl - 1) / 8) + '/day')
									}
									if(varSorLvl > 14) {
										charSheet.addValue('class', 'bloodlineNotes', 'Within Reach')
									}
									if(varSorLvl == 20) {
										charSheet.addValue('class', 'bloodlineNotes', 'Destiny Realized')
										charSheet.addValue('class', 'resist', 'critical hits (confirm only on 20)')
										charSheet.addValue('class', 'dr', Math.max(charSheet.getTotal('dr'), 5))
									}
								}
								if(varClassFeatureDetail == 'Draconic') {
									charSheet.addValue('class', 'classSkill', 'Perception', 'Yes')
								}
								if(varClassFeatureDetail == 'Elemental') {
									charSheet.addValue('class', 'classSkill', 'Knowledge (planes)', 'Yes')
								}
								if(varClassFeatureDetail == 'Fey') {
									charSheet.addValue('class', 'classSkill', 'Knowledge (nature)', 'Yes')
									charSheet.addValue('class', 'bloodlineNotes', 
									                   'Bloodline Arcana (+2 DC for compulsion spells)')
									charSheet.addValue('class', 'bloodlineNotes', 
									                   'Laughing Touch ' 
									                   + (3 + 1 * charSheet.bonus(charSheet.getTotal('cha'))) + '/day')
									if(varSorLvl > 2) {
										charSheet.addValue('class', 'bloodlineNotes', 'Woodland Stride')
									}
									if(varSorLvl > 8) {
										charSheet.addValue('class', 'bloodlineNotes', 
										                   'Fleeting Glance ' + varSorLvl + ' rounds/day')
									}
									if(varSorLvl > 14) {
										charSheet.addValue('class', 'bloodlineNotes', 'Fey Magic')
									}
									if(varSorLvl == 20) {
										charSheet.addValue('class', 'bloodlineNotes', 'Soul of the Fey')
										charSheet.addValue('class', 'immune', 'posion')
										charSheet.addValue('class', 'drColdIron', 10)
									}
								}
								if(varClassFeatureDetail == 'Infernal') {
									charSheet.addValue('class', 'classSkill', 'Diplomacy', 'Yes')
									charSheet.addValue('class', 'bloodlineNotes', 'Bloodline Arcana (+2 DC for charm spells)')
									charSheet.addValue('class', 'bloodlineNotes', 
									                   'Corrupting Touch ' 
									                   + (3 + 1 * charSheet.bonus(charSheet.getTotal('cha'))) 
									                   + '/day (' + Math.max(1, Math.floor(varSorLvl / 2)) + ' rounds)')
									if(varSorLvl > 2 && varSorLvl < 20) {
										charSheet.addValue('class', 'bloodlineNotes', 'Infernal Resistances')
										if(varSorLvl < 9) {
											charSheet.addValue('class', 'resist', 'fire 5')
											charSheet.addValue('class', 'save', '+2 vs. poison')
										}
										else {
											charSheet.addValue('class', 'resist', 'fire 10')
											charSheet.addValue('class', 'save', '+4 vs. poison')
										}
									}
									if(varSorLvl > 8) {
										charSheet.addValue('class', 'bloodlineNotes', 
										                   'Hellfire ' + Math.max(1, Math.floor((varSorLvl - 11) / 3)) 
										                   + '/day (DC ' + (10 + Math.floor(varSorLvl / 2) 
										                   + charSheet.bonus(charSheet.cha)) + ', ' + varSorLvl + 'd6)')
									}
									if(varSorLvl > 14) {
										charSheet.addValue('class', 'bloodlineNotes', 'On Dark Wings')
										charSheet.addValue('class', 'movement', 'fly 60 ft. (average)')
									}
									if(varSorLvl == 20) {
										charSheet.addValue('class', 'bloodlineNotes', 'Power of the Pit')
										charSheet.addValue('class', 'immune', 'fire')
										charSheet.addValue('class', 'immune', 'poison')
										charSheet.addValue('class', 'resist', 'acid 10')
										charSheet.addValue('class', 'resist', 'cold 10')
										charSheet.addValue('class', 'senses', 'Perfect Darkvision (60 ft.)')
									}
								}
								if(varClassFeatureDetail == 'Undead') {
									charSheet.addValue('class', 'classSkill', 'Knowledge (religion)', 'Yes')
									charSheet.addValue('class', 'bloodlineNotes', 
									                   'Bloodline Arcana (corporeal undead that were once humanoids are treated as humanoids for spell affects)')
									charSheet.addValue('class', 'bloodlineNotes', 
									                   'Grave Touch ' + (3 + 1 * charSheet.bonus(charSheet.getTotal('cha'))) 
									                   + '/day (' + Math.max(1, Math.floor(varSorLvl / 2)) + ' rounds)')
									if(varSorLvl > 2 && varSorLvl < 20) {
										charSheet.addValue('class', 'bloodlineNotes', 'Death\'s Gift')
										if(varSorLvl < 9) {
											charSheet.addValue('class', 'resist', 'cold 5')
											charSheet.addValue('class', 'drLethal', 5)
										}
										else {
											charSheet.addValue('class', 'resist', 'cold 10')
											charSheet.addValue('class', 'drLethal', 10)
										}
									}
									if(varSorLvl > 8) {
										charSheet.addValue('class', 'bloodlineNotes', 
										                   'Grasp of the Dead ' + Math.max(1, Math.floor((varSorLvl - 11) / 3)) 
										                   + '/day (DC ' + (10 + Math.floor(varSorLvl / 2) 
										                   + charSheet.bonus(charSheet.getTotal('cha'))) + ', ' + varSorLvl + 'd6)')
									}
									if(varSorLvl > 14) {
										charSheet.addValue('class', 'bloodlineNotes', 
										                   'Incorporeal Form ' + varSorLvl + ' rounds/day')
									}
									if(varSorLvl == 20) {
										charSheet.addValue('class', 'bloodlineNotes', 'One of Us')
										charSheet.addValue('class', 'immune', 'cold')
										charSheet.addValue('class', 'immune', 'nonlethal damage')
										charSheet.addValue('class', 'immune', 'paralysis')
										charSheet.addValue('class', 'immune', 'sleep')
										charSheet.addValue('class', 'dr', Math.max(charSheet.getValue('dr'), 5))
										charSheet.addValue('class', 'save', '+4 vs. spells and spell-like abilities cast by undead')
									}
								}
								break
							case 'Dragon Type':
								if(charSheet.getTotal('bloodline') == 'Draconic') {
									charSheet.addValue('class', 'dragonType', varClassFeatureDetail)
									// edit
									charSheet.edit.bloodline += '<br>Dragon Type ' 
									                          + buildDropDown('classfeature' + varClassFeatureID, 
									                                          varClassFeatureDetail, 
									                                          ['Black',
									                                           'Blue',
									                                           'Green',
									                                           'Red',
									                                           'White',
									                                           'Brass',
									                                           'Bronze',
									                                           'Copper',
									                                           'Gold',
									                                           'Silver'
									                                          ])
									// detail
									switch(varClassFeatureDetail.toLowerCase()) {
										case 'black':
										case 'green':
										case 'copper': tmpEnergyType = 'acid'
										               break
									  case 'blue':
									  case 'bronze': tmpEnergyType = 'electricity'
									                 break
									  case 'red':
									  case 'brass':
									  case 'gold':   tmpEnergyType = 'fire'
									                 break
									  case 'white':
									  case 'silver': tmpEnergyType = 'cold'
									                 break
									  default:       tmpEnergyType = 'certain'
									 }
									charSheet.addValue('class', 'bloodlineNotes', 
									                   'Bloodline Arcana (+1 damage per die on ' + tmpEnergyType + ' spells)')
									temp = ''
									if(varSorLvl > 4) {
										temp += 'Magic '
									}
									temp += 'Claws ' + (3 + 1 * charSheet.bonus(charSheet.getTotal('cha'))) + '/day ('
									if(varSorLvl < 7) {
										temp += charSheet.dice('5')
									}
									else {
										temp += charSheet.dice('7')
									}
									if(charSheet.bonus(charSheet.getTotal('str'), 1)) {
										temp += '+' + charSheet.bonus(charSheet.getTotal('str'), 1)
									}
									if(varSorLvl > 10) {
										temp += '+1d6 ' + tmpEnergyType
									}
									temp += ')'
									charSheet.addValue('class', 'bloodlineNotes', temp)
									if(varSorLvl > 2 && varSorLvl < 20) {
										charSheet.addValue('class', 'bloodlineNotes', 'Dragon Resistance')
										if(varSorLvl < 9) {
											charSheet.addValue('class', 'resist', tmpEnergyType + ' 5')
										}
										else {
											charSheet.addValue('class', 'resist', tmpEnergyType + ' 10')
										}
									}
									if(varSorLvl > 2) {
										charSheet.addValue('class', 'naturalArmour', 1)
										if(varSorLvl > 8) {
											charSheet.addValue('class', 'naturalArmour', 1)
										}
										if(varSorLvl > 14) {
											charSheet.addValue('class', 'naturalArmour', 2)
										}
									}
									if(varSorLvl > 8) {
										charSheet.addValue('class', 'bloodlineNotes', 
										                   'Breath Weapon ' + Math.max(1, Math.floor((varSorLvl - 11) / 3)) 
										                   + '/day (DC ' + (10 + Math.floor(varSorLvl / 2) 
										                   + charSheet.bonus(charSheet.getTotal('cha'))) + ', ' + varSorLvl + 'd6)')
									}
									if(varSorLvl > 14) {
										charSheet.addValue('class', 'bloodlineNotes', 'Wings')
										charSheet.addValue('class', 'movement', 'fly 60 ft. (average)')
									}
									if(varSorLvl == 20) {
										charSheet.addValue('class', 'bloodlineNotes', 'Power of Wyrms')
										charSheet.addValue('class', 'immune', 'paralysis')
										charSheet.addValue('class', 'immune', 'sleep')
										charSheet.addValue('class', 'immune', tmpEnergyType)
										charSheet.addValue('class', 'senses', 'Blindsense (60 ft.)')
									}
								}
								break
							case 'Element Type':
								if(charSheet.getTotal('bloodline') == 'Elemental') {
									charSheet.addValue('class', 'elementType', varClassFeatureDetail)
									// edit
									charSheet.edit.bloodline += '<br>Element Type ' 
									                          + buildDropDown('classfeature' + varClassFeatureID, 
									                                          varClassFeatureDetail, 
									                                          ['Air',
									                                           'Earth',
									                                           'Fire',
									                                           'Water'
									                                          ])
									charSheet.sa_element_type = varClassFeatureDetail
									switch(varClassFeatureDetail.toLowerCase()) {
										case 'air':   tmpEnergyType = 'electricity'
										              tmpMovement = 'fly 60 ft. (average)'
										              break
										case 'earth': tmpEnergyType = 'acid'
										              tmpMovement = 'burrow 30 ft.'
										              break
										case 'fire':  tmpEnergyType = 'fire'
										              tmpMovement = ''
										              charSheet.addValue('class', 'baseSpeed', 30)
										              break
										case 'water': tmpEnergyType = 'cold'
										              tmpMovement = 'swim 60 ft.'
										              break
										default:      tmpEnergyType = 'certain'
									}
									charSheet.addValue('class', 'bloodlineNotes', 
									                   'Bloodline Arcana (+1 damage per die on ' + tmpEnergyType + ' spells)')
									charSheet.addValue('class', 'bloodlineNotes', 
									                   'Elemental Ray ' + (3 + 1 * charSheet.bonus(charSheet.getTotal('cha'))) 
									                   + '/day (1d6+' + Math.floor(varSorLvl / 2) + ')')
									if(varSorLvl > 2 && varSorLvl < 20) {
										charSheet.addValue('class', 'bloodlineNotes', 'Elemental Resistance')
										charSheet.addValue('class', 'resist', 
										                   tmpEnergyType + ' ' 
										                   + Math.min(2, Math.floor((varSorLvl + 3) / 6)) + '0')
									}
									if(varSorLvl > 8) {
										charSheet.addValue('class', 'bloodlineNotes', 
										                   'Elemental Blast ' + Math.max(1, Math.floor((varSorLvl - 11) / 3)) 
										                   + '/day (DC ' + (10 + Math.floor(varSorLvl / 2) 
										                   + charSheet.bonus(charSheet.getTotal('cha'))) + ', ' + varSorLvl + 'd6)')
									}
									if(varSorLvl > 14) {
										charSheet.addValue('class', 'bloodlineNotes', 'Elemental Movement')
										charSheet.movement += tmpMovement
									}
									if(varSorLvl == 20) {
										charSheet.addValue('class', 'bloodlineNotes', 'Elemental Body')
										charSheet.addValue('class', 'immune', 'critical hits')
										charSheet.addValue('class', 'immune', 'sneak attacks')
										charSheet.addValue('class', 'immune', tmpEnergyType)
									}
								}
								break
							case 'Channel Energy':
								charSheet.adjustments.class.channelDesc = varClassFeatureHTML
								charSheet.addValue('class', 'cp', 3 + charSheet.bonus(charSheet.getTotal('cha')))
								charSheet.addValue('class', 'channel', varClassFeatureDetail)
								// edit
								$('#editChannelEnergy').html(charSheet.out('<br>Channel Energy ', 
								                                           buildDropDown('classfeature' + varClassFeatureID, 
								                                                        varClassFeatureDetail, 
								                                                        ['Positive', 'Negative']), 
								                                           '', 1))
								break
							case 'Channel Positive Energy':
								charSheet.addValue('class', 'sa', 
								                   varClassFeatureHTML + ' (DC ' 
								                   + (10 + Math.floor(charSheet.getNumber('class_level.PAL') / 2) 
								                   + charSheet.bonus(charSheet.getTotal('cha'))) + ', ' 
								                   + Math.floor((charSheet.getNumber('class_level.PAL') + 1) / 2) + 'd6)')
								break
							case 'Wild Shape':
								if(charSheet.getNumber('class_level.DRD') == 20) {
									charSheet.addValue('class', 'sa', varClassFeatureHTML + ' at will')
								}
								else {
									charSheet.addValue('class', 'sa', 
									                   varClassFeatureHTML + ' ' 
									                   + Math.floor((1 * charSheet.getNumber('class_level.DRD') - 2) / 2) + '/day')
								}
								break
							case 'Weapon Training':
								charSheet.adjustments.class.weaponTrainingDesc = varClassFeatureHTML
								charSheet.addValue('class', 'weaponGroup', varClassFeatureDetail)
								// edit
								tmpWeaponGroupMenu = buildDropDown('classfeature' + varClassFeatureID, 
								                                  varClassFeatureDetail, 
								                                  ['Axes',
								                                   'Heavy Blades',
								                                   'Light Blades',
								                                   'Bows',
								                                   'Close',
								                                   'Crossbows',
								                                   'Double',
								                                   'Flails',
								                                   'Hammers',
								                                   'Monk',
								                                   'Natural',
								                                   'Pole Arms',
								                                   'Spears',
								                                   'Thrown'
								                                  ])
								if(charSheet.edit.weaponGroup) {
									charSheet.edit.weaponGroup += tmpWeaponGroupMenu
								}
								else {
									charSheet.edit.weaponGroup = tmpWeaponGroupMenu
									$('#spellsSection').show()
								}
								$('#editWeaponGroup').html('<br>Weapon Training ' + charSheet.edit.weaponGroup)
								break
							case 'Weapon Mastery':
								charSheet.addValue('class', 'sa', varClassFeatureHTML)
								// edit
								$('#editWeaponMastery').html('<br>Weapon Mastery <input type="text" id="classfeature' 
								                             + varClassFeatureID + '" value="' 
								                             + varClassFeatureDetail + '">')
								break
							case 'Flurry of Blows':
								charSheet.addValue('class', 'flurryOfBlows', 1)
								break
							case 'Quivering Palm':
								charSheet.addValue('class', 'sa', varClassFeatureHTML + ' (1/day)')
								break
							case 'Smite Evil':
								temp = varClassFeatureHTML + ' (' 
								     + Math.floor((charSheet.getNumber('class_level.PAL') + 2) / 3) + '/day, +'
								// only for a cha bonus
								if(charSheet.getTotal('cha') > 11)
									temp += charSheet.bonus(charSheet.getTotal('cha')) + ' attack and AC, +'
								temp += charSheet.getNumber('class_level.PAL') + ' damage'
								if(charSheet.getNumber('class_level.PAL') == 20)
									temp += ', banishment'
								temp += ')'
								charSheet.addValue('class', 'sa', temp)
								break
							case 'Quarry':
							case 'Improved Quarry':
								charSheet.adjustments.class.quarryDesc = '; ' + varClassFeatureHTML
								break
							case 'Master Hunter':
								charSheet.addValue('class', 'sa', 
								                   varClassFeatureHTML + ' (DC ' 
								                   + (10 + Math.floor(charSheet.getNumber('class_level.RGR') / 2) 
								                   + charSheet.bonus(charSheet.getTotal('wis'))) + ')')
								break
							case 'Favoured Enemy':
								charSheet.adjustments.class.favouredEnemyDesc = varClassFeatureHTML
								charSheet.addValue('class', 'favouredEnemy', varClassFeatureDetail)
								// edit
								tmpFavouredEnemyMenu = buildDropDown('classfeature' + varClassFeatureID, 
								                                    varClassFeatureDetail, 
								                                    ['Aberration',
								                                     'Animal',
								                                     'Construct',
								                                     'Dragon',
								                                     'Fey',
								                                     'Humanoid (aquatic)',
								                                     'Humanoid (dwarf)',
								                                     'Humanoid (elf)',
								                                     'Humanoid (giant)',
								                                     'Humanoid (goblinoid)',
								                                     'Humanoid (gnoll)',
								                                     'Humanoid (gnome)',
								                                     'Humanoid (halfling)',
								                                     'Humanoid (human)',
								                                     'Humanoid (orc)',
								                                     'Humanoid (reptilian)',
								                                     'Humanoid (other subtype)',
								                                     'Magical beast',
								                                     'Monstrous humanoid',
								                                     'Ooze',
								                                     'Outsider (air)',
								                                     'Outsider (chaotic)',
								                                     'Outsider (earth)',
								                                     'Outsider (evil)',
								                                     'Outsider (fire)',
								                                     'Outsider (good)',
								                                     'Outsider (lawful)',
								                                     'Outsider (native)',
								                                     'Outsider (water)',
								                                     'Plant',
								                                     'Undead',
								                                     'Vermin'
								                                    ])
								if(charSheet.edit.favouredEnemy) {
									charSheet.edit.favouredEnemy += tmpFavouredEnemyMenu
								}
								else {
									charSheet.edit.favouredEnemy = tmpFavouredEnemyMenu
								}
								$('#editFavouredEnemy').html('<br>Favoured Enemy ' + charSheet.edit.favouredEnemy)
								$('#spellsSection').show()
								break
							case 'Sneak Attack':
								charSheet.addValue('class', 'sa', 
								                   varClassFeatureHTML + ' +' 
								                   + Math.floor((charSheet.getNumber('class_level.ROG') + 1) / 2) + 'd6')
								break
							case 'Master Strike':
								charSheet.addValue('class', 'sa', varClassFeatureHTML)
								break

							// spells

							// CLR & DRD
							case 'Domains':
								charSheet.adjustments.class.domainsDesc = varClassFeatureHTML
								// domain powers
								for(k = 1; k < charSheet.getNumber('charLevel'); k++) {
									if(charSheet.domain_powers[varClassFeatureDetail][k]) {
										temp = charSheet.domain_powers[varClassFeatureDetail][k].split(':')
										charSheet.addValue('class', 'domainPower', 
										                   '<span title="' + temp[1] + '">' + temp[0] + '</span>')
									}
								}
								charSheet.addValue('class', 'domains', varClassFeatureDetail)
								// edit
								var tmpDomainOptions = new Array()
								for(var tmpDomain in charSheet.spell_list['DOMAIN'][1]) {
									tmpDomainOptions.push(tmpDomain)
								}
							  tempDomainMenu = buildDropDown('classfeature' + varClassFeatureID, 
							                                 varClassFeatureDetail, 
							                                 tmpDomainOptions)
								if(charSheet.edit.domains === undefined) {
									charSheet.edit.domains = tempDomainMenu
								}
								else {
									charSheet.edit.domains += tempDomainMenu
								}
							  break

							// PAL
							case 'Detect Evil':
								charSheet.addValue('class', 'spellLike', 
								                   varClassFeatureHTML + ' (at will, CL ' 
								                   + charSheet.ordinal(charSheet.getNumber('class_level.PAL')) + ')')
								break

							// feats
							case 'Bonus Feats':
							case 'Unarmed Strike':
							case 'Stunning Fist':
							case 'Endurance':
							case 'Eschew Materials':
							case 'Scribe Scroll':
								charSheet.addValue('class', 'fp', 1)
							  break

							// skills
							case 'Bardic Knowledge':
								temp = Math.max(1, Math.floor(charSheet.getNumber('class_level.BRD') / 2))
								charSheet.addValue('class', 'skill', 'Knowledge (arcana)', temp)
								charSheet.addValue('class', 'skill', 'Knowledge (dungeoneering)', temp)
								charSheet.addValue('class', 'skill', 'Knowledge (engineering)', temp)
								charSheet.addValue('class', 'skill', 'Knowledge (geography)', temp)
								charSheet.addValue('class', 'skill', 'Knowledge (history)', temp)
								charSheet.addValue('class', 'skill', 'Knowledge (local)', temp)
								charSheet.addValue('class', 'skill', 'Knowledge (nature)', temp)
								charSheet.addValue('class', 'skill', 'Knowledge (nobility)', temp)
								charSheet.addValue('class', 'skill', 'Knowledge (planes)', temp)
								charSheet.addValue('class', 'skill', 'Knowledge (religion)', temp)
								charSheet.addValue('class', 'sq', varClassFeatureHTML + ' +' + temp)
								break
							case 'Versatile Performance':
								charSheet.adjustments.class.versatilePerfDesc = varClassFeatureHTML
								charSheet.addValue('edit', 'versatilePerf', '')
								// edit
								tempVersatilePerfMenu = buildDropDown('classfeature' + varClassFeatureID, 
									                                   varClassFeatureDetail, 
									                                   ['Act', 
									                                    'Comedy', 
									                                    'Dance', 
									                                    'Keyboard Instruments', 
									                                    'Oratory', 
									                                    'Percussion Instruments', 
									                                    'Sing', 
									                                    'String Instruments', 
									                                    'Wind Instruments',
									                                   ])
								if(charSheet.edit.versatilePerf) {
									charSheet.edit.versatilePerf += tempVersatilePerfMenu
								}
								else {
									charSheet.edit.versatilePerf = tempVersatilePerfMenu
								}
								switch(varClassFeatureDetail.toLowerCase()) {
									case 'act':                    varClassFeatureDetail += ' (Bluff, Disguise)'
									                               break
									case 'comedy':                 varClassFeatureDetail += ' (Bluff, Intimidate)'
									                               break
									case 'dance':                  varClassFeatureDetail += ' (Acrobatics, Fly)'
									                               break
									case 'keyboard':
									case 'keyboard instruments':   varClassFeatureDetail += ' (Diplomacy, Intimidate)'
									                               break
									case 'oratory':                varClassFeatureDetail += ' (Diplomacy, Sense Motive)'
									                               break
									case 'percussion':
									case 'percussion instruments': varClassFeatureDetail += ' (Handle Animal, Intimidate)'
									                               break
									case 'sing':                   varClassFeatureDetail += ' (Bluff, Sense Motive)'
									                               break
									case 'string':
									case 'string instruments':     varClassFeatureDetail += ' (Bluff, Diplomacy)'
									                               break
									case 'wind':
									case 'wind instruments':       varClassFeatureDetail += ' (Diplomacy, Handle Animal)'
									                               break
								}
								charSheet.addValue('class', 'versatilePerf', varClassFeatureDetail)
								break
							case 'Lore Master':
								charSheet.addValue('class', 'sq', 
								                   varClassFeatureHTML + ' ' 
								                   + Math.floor((charSheet.getNumber('class_level.BRD') + 1) / 6) + '/day')
								break
							case 'Nature Sense':
								charSheet.addValue('feat', 'skill', 'Knowledge', 'nature', 2)
								charSheet.addValue('feat', 'skill', 'Survival', 2)
								break

							// languages

							case 'Bonus Languages':
								charSheet.addValue('class', 'lp', 1)
								// A druid's bonus language options include Sylvan, the language of woodland creatures.
								break

							// sq
							case 'Tongue of the Sun and Moon':
							case 'Jack-of-All-Trades':
							case 'Spontaneous Casting':
							case 'Woodland Stride':
							case 'Trackless Step':
							case 'A Thousand Faces':
							case 'Timeless Body':
							case 'High Jump':
							case 'Wholeness of Body':
							case 'Abundant Step':
							case 'Empty Body':
							case 'Aura':
							case 'Swift Tracker':
								charSheet.addValue('class', 'sq', varClassFeatureHTML)
								break
							case 'Hunter\'s Bond':
								if(!varClassFeatureDetail) {
									charSheet.addValue('class', 'sq', 
									                   varClassFeatureHTML + ' (' 
									                   + Math.max(1, charSheet.bonus(charSheet.getTotal('wis'))) + ' rounds)')
								}
								// continue...
							case 'Nature Bond':
								// continue...
							case 'Animal Companion':
								if(varClassFeatureDetail) { // needed to override ranger hunter's bond
									charSheet.addValue('class', 'animalCompanion', varClassFeatureDetail)
									charSheet.addValue('class', 'sq', 
									                   varClassFeatureHTML + ' (' + varClassFeatureDetail + ')')
								}
								// edit
								$('#editAnimalCompanion').html('Animal Companion <input type="text" id="classfeature'
								                             + varClassFeatureID + '" value="'
								                             + varClassFeatureDetail + '"><br>')
								break
							case 'Wild Empathy':
								temp = charSheet.getNumber('class_level.DRD') + charSheet.getNumber('class_level.RGR')
								charSheet.addValue('class', 'sq', 
								                   varClassFeatureHTML + ' +' 
								                   + (temp + charSheet.bonus(charSheet.getTotal('cha'))))
								break
							case 'Armour Training':
								charSheet.addValue('class', 'armourTraining',
								                   Math.floor((1 * charSheet.getNumber('class_level.FTR') + 1) / 4))
								break
							case 'Slow Fall':
								charSheet.adjustments.class.slowFallDesc = varClassFeatureHTML
								charSheet.addValue('class', 'slowFall', 1)
								break
							case 'Lay on Hands':
								charSheet.adjustments.class.layDesc = '; ' + varClassFeatureHTML
								charSheet.addValue('class', 'lohp', 
								                   Math.floor(charSheet.getNumber('class_level.PAL') / 2 
								                   + charSheet.bonus(charSheet.getTotal('cha'))))
								break
							case 'Mercy':
								charSheet.adjustments.class.merciesDesc = varClassFeatureHTML
								charSheet.addValue('class', 'mercy', varClassFeatureDetail)
								// edit
								// TO DO: List based on class feature level, not current class level
								tmpMerciesOptions = Array('Fatigued', 'Shaken', 'Sickened')
								if(charSheet.getNumber('class_level.PAL') > 5) {
									tmpMerciesOptions.push('Dazed')
									tmpMerciesOptions.push('Diseased')
									tmpMerciesOptions.push('Staggered')
									if(charSheet.getNumber('class_level.PAL') > 8) {
										tmpMerciesOptions.push('Cursed')
										tmpMerciesOptions.push('Exhausted')
										tmpMerciesOptions.push('Frightened')
										tmpMerciesOptions.push('Nauseated')
										tmpMerciesOptions.push('Poisoned')
										if(charSheet.getNumber('class_level.PAL') > 11) {
											tmpMerciesOptions.push('Blinded')
											tmpMerciesOptions.push('Deafened')
											tmpMerciesOptions.push('Paralyzed')
											tmpMerciesOptions.push('Stunned')
										}
									}
								}
								tmpMerciesMenu = buildDropDown('classfeature' + varClassFeatureID, varClassFeatureDetail, tmpMerciesOptions)
								if(charSheet.edit.mercies) {
									charSheet.edit.mercies += tmpMerciesMenu
								}
								else {
									charSheet.edit.mercies = tmpMerciesMenu
								}
								$('#editMercies').html('<br>Mercies ' + charSheet.edit.mercies)
								break
							case 'Divine Bond':
								varDivineBond = varClassFeatureHTML + ' (' + varClassFeatureDetail
								// if bond is with weapon:
								if(varClassFeatureDetail.toLowerCase() == 'weapon') {
									varDivineBond += ' +' + Math.floor((charSheet.getNumber('class_level.PAL') - 2) / 3) + ', '
								}
								// if bond is with mount:
								if(varClassFeatureDetail.toLowerCase() == 'mount') {
									if(charSheet.getNumber('class_level.PAL') > 10) {
										varDivineBond += ', celestial'
									}
									if(charSheet.getNumber('class_level.PAL') > 14) {
										varDivineBond += ', SR ' + (1 * charSheet.getNumber('class_level.PAL') + 11)
									}
									varDivineBond += ', call '
								}
								varDivineBond += Math.floor((charSheet.getNumber('class_level.PAL') - 1) / 4) + '/day)'
								charSheet.addValue('class', 'sq', varDivineBond)
								// edit
								$('#editDivineBond').html('Divine Bond <select id=\'classfeature' + varClassFeatureID + '\'><option value=\'' + varClassFeatureDetail + '\'>' + varClassFeatureDetail + '</option><option value=\'\'>--------------------</option><option value=\'weapon\'>weapon</option><option value=\'mount\'>mount</option></select><br>')
								break
							case 'Track':
								charSheet.addValue('class', 'sq', varClassFeatureHTML + ' +' + Math.max(1, Math.floor(charSheet.getNumber('class_level.RGR') / 2)))
								break
							case 'Combat Style Feat':
								charSheet.addValue('class', 'sq', varClassFeatureHTML + charSheet.out(' (', varClassFeatureDetail, ')', 1))
								// edit
								$('#editCombatStyle').html('Combat Style ' 
								                         + buildDropDown('classfeature' + varClassFeatureID, 
								                                        varClassFeatureDetail, 
								                                        ['archery', 'two-weapon']) + '<br>')
								break

				// rgr
				if(!charSheet.isEmpty(charSheet.getValue('sq_favoured_terrain'))) {
					temp = ''
					for(var terrain in charSheet.getValue('sq_favoured_terrain')) {
						temp += ', ' + terrain + ' +' + (2 * charSheet.getValue('sq_favoured_terrain.' + terrain))
					}
					varSQDesc += ', favoured terrain (' + temp.substr(2) + ')'
				}

							case 'Favoured Terrain':
								charSheet.adjustments.class.favouredTerrainDesc = varClassFeatureHTML
								charSheet.addValue('class', 'favouredTerrain', varClassFeatureDetail)
								// edit
								tmpFavouredTerrainMenu = buildDropDown('classfeature' + varClassFeatureID, 
								                                      varClassFeatureDetail, 
								                                      ['Cold',
								                                       'Desert',
								                                       'Forest',
								                                       'Jungle',
								                                       'Mountain',
								                                       'Plains',
								                                       'Swamp',
								                                       'Underground',
								                                       'Urban',
								                                       'Water'
								                                      ])
								if(charSheet.edit.favouredTerrain) {
									charSheet.edit.favouredTerrain += tmpFavouredTerrainMenu
								}
								else {
									charSheet.edit.favouredTerrain = tmpFavouredTerrainMenu
								}
								$('#editFavouredTerrain').html('Favoured Terrain ' + charSheet.edit.favouredTerrain + '<br>')
								break
							case 'Trap Finding':
								charSheet.addValue('class', 'sq', varClassFeatureHTML + ' +' + Math.max(1, Math.floor(charSheet.getNumber('class_level.ROG') / 2)))
								break
							case 'Rogue Talents':
								charSheet.adjustments.class.rogueTalentsDesc = varClassFeatureHTML
								temp = varClassFeatureDetail
								switch(varClassFeatureDetail.toLowerCase()) {
									case 'bleeding attack':  temp += ' +' + Math.floor(charSheet.getNumber('class_level.ROG') / 2)
									                         break
									case 'resiliency':       temp += ' +' + charSheet.getNumber('class_level.ROG')
									                         break
									case 'improved evasion': charSheet.addValue('feat', 'evasion', 1)
									                         break
									case 'skill mastery':    temp += ' x' + (3 + charSheet.bonus(charSheet.getTotal('int')))
									                         break
									case 'combat trick':
									case 'finesse rogue':
									case 'weapon training':
									case 'feat':             charSheet.addValue('class', 'fp', 1)
									                         break
								}
								charSheet.addValue('class', 'rogueTalents', temp)
								// edit
								tmpRogueTalentsOptions = ['Bleeding Attack',
								                          'Combat Trick',
								                          'Fast Stealth',
								                          'Finesse Rogue',
								                          'Ledge Walker',
								                          'Major Magic',
								                          'Minor Magic',
								                          'Quick Disable',
								                          'Resiliency',
								                          'Rogue Crawl',
								                          'Slow Reactions',
								                          'Stand Up',
								                          'Surprise Attack',
								                          'Trap Spotter',
								                          'Weapon Training',
								                         ]
								if(charSheet.getNumber('class_level.ROG') > 9) {
									tmpRogueTalentsOptions.push('Crippling Strike')
									tmpRogueTalentsOptions.push('Defensive Roll')
									tmpRogueTalentsOptions.push('Dispelling Attack')
									tmpRogueTalentsOptions.push('Improved Evasion')
									tmpRogueTalentsOptions.push('Opportunist')
									tmpRogueTalentsOptions.push('Skill Mastery')
									tmpRogueTalentsOptions.push('Slippery Mind')
									tmpRogueTalentsOptions.push('Feat')
								}
								tmpRogueTalentsMenu = buildDropDown('classfeature' + varClassFeatureID, 
								                                   varClassFeatureDetail, 
								                                   tmpRogueTalentsOptions)
								if(!charSheet.edit.rogueTalents) {
									charSheet.edit.rogueTalents = tmpRogueTalentsMenu
								}
								else {
									charSheet.edit.rogueTalents += tmpRogueTalentsMenu
								}
								$('#editRogueTalents').html('Rogue Talents ' + charSheet.edit.rogueTalents + '<br>')
								break
							case 'Arcane Bond':
								// only if a wizard, adept, or sorcerer with the arcane bloodline
								if(charSheet.getNumber('class_level.WIZ') > 0
								|| charSheet.getNumber('class_level.ADP') > 0 
								|| charSheet.getTotal('bloodline') == 'Arcane') {
									charSheet.addValue('class', 'sq', varClassFeatureHTML + 
									                                  charSheet.out(' (', varClassFeatureDetail, ')', 1))
								}
								// edit
								$('#editArcaneBond').html('Arcane Bond <input type="text" id="classfeature' + varClassFeatureID + '" value="' + varClassFeatureDetail + '"><br>')
								break
							case 'Arcane School':
								charSheet.adjustments.class.schoolDesc = varClassFeatureHTML
								charSheet.addValue('class', 'arcaneSchool', varClassFeatureDetail)
								// add arcane school powers
								// TO DO: Arcane School effects on stats
								var tmpWizLvl = charSheet.getNumber('class_level.WIZ')
								if(varClassFeatureDetail && varClassFeatureDetail != 'Universalist') {
									charSheet.addValue('class', 'schoolNotes', 'School Power (+2 DC to ' + varClassFeatureDetail + ')')
								}
								if(varClassFeatureDetail == 'Abjuration') {
									charSheet.addValue('class', 'schoolNotes', '<span title="You gain resistance 5 to an energy type of your choice, chosen when you prepare spells. This resistance can be changed each day. At 11th level, this resistance increases to 10. At 20th level, this resistance changes to immunity to the chosen energy type.">Resistance</span>')
									charSheet.addValue('class', 'schoolNotes', '<span title="As a standard action, you can create a 10-foot-radius field of protective magic centered on you that lasts for a number of rounds equal to your Intelligence modifier. All allies in this area (including you) receive a +1 deflection bonus to their AC for 1 round. This bonus increases by +1 for every five wizard levels you possess. You can use this ability a number of times per day equal to 3 + your Intelligence modifier.">Protective Ward</span>')
									if(tmpWizLvl > 5) {
										charSheet.addValue('class', 'schoolNotes', '<span title="At 6th level, you gain an amount of energy absorption equal to 3 times your wizard level per day. Whenever you take energy damage, apply immunity, vulnerability (if any), and resistance first and apply the rest to this absorption, reducing your daily total by that amount. Any damage in excess of your absorption is applied to you normally.">Energy Absorption</span>')
									}
								}
								if(varClassFeatureDetail == 'Conjuration') {
									charSheet.addValue('class', 'schoolNotes', '<span title="Whenever you cast a conjuration (summoning) spell, increase the duration by a number of rounds equal to 1/2 your wizard level (minimum 1). At 20th level, you can change the duration of all summon monster spells to permanent. You can have no more than one summon monster spell made permanent in this way at one time. If you designate another summon monster spell as permanent, the previous spell immediately ends.">Summoner\'s Charm</span>')
									charSheet.addValue('class', 'schoolNotes', '<span title="As a standard action you can unleash an acid dart targeting any foe within 30 feet as a ranged touch attack. The acid dart deals 1d6 points of acid damage + 1 for every two wizard levels you possess. You can use this ability a number of times per day equal to 3 + your Intelligence modif ier. This attack ignores spell resistance.">Acid Dart</span>')
									if(tmpWizLvl > 7) {
										charSheet.addValue('class', 'schoolNotes', '<span title="At 8th level, you can use this ability to teleport up to 30 feet per wizard level per day as a standard action. This teleportation must be used in 5-foot increments and such movement does not provoke an attack of opportunity. You can bring other willing creatures with you, but you must expend an equal amount of distance for each additional creature brought with you.">Dimensional Steps</span>')
									}
								}
								if(varClassFeatureDetail == 'Divination') {
									charSheet.addValue('class', 'schoolNotes', '<span title="You can always act in the surprise round even if you fail to make a Perception roll to notice a foe, but you are still considered flat-footed until you take an action. In addition, you receive a bonus on initiative checks equal to 1/2 your wizard level (minimum +1). At 20th level, anytime you roll initiative, assume the roll resulted in a natural 20.">Forewarned</span>')
									charSheet.addValue('class', 'schoolNotes', '<span title="When you activate this school power, you can touch any creature as a standard action to give it an insight bonus on all of its attack rolls, skill checks, ability checks, and saving throws equal to 1/2 your wizard level (minimum +1) for 1 round. You can use this ability a number of times per day equal to 3 + your Intelligence modifier.">Diviner\'s Fortune</span>')
									if(tmpWizLvl > 7) {
										charSheet.addValue('class', 'schoolNotes', '<span title="At 8th level, you are always aware when you are being observed via magic, as if you had a permanent detect scrying. In addition, whenever you scry on a subject, treat the subject as one step more familiar to you. Very familiar subjects get a –10 penalty on their save to avoid your scrying attempts.">Scrying Adept</span>')
									}
								}
								if(varClassFeatureDetail == 'Enchantment') {
									charSheet.addValue('class', 'schoolNotes', '<span title="You gain a +2 enhancement bonus on Bluff, Diplomacy, and Intimidate skill checks. This bonus increases by +1 for every five wizard levels you possess, up to a maximum of +6 at 20th level. At 20th level, whenever you succeed at a saving throw against a spell of the enchantment school, that spell is reflected back at its caster, as per spell turning.">Enchanting Smile</span>')
									charSheet.addValue('class', 'schoolNotes', '<span title="You can cause a living creature to become dazed for 1 round as a melee touch attack. Creatures with more Hit Dice than your wizard level are unaffected. You can use this ability a number of times per day equal to 3 + your Intelligence modifier.">Dazing Touch</span>')
									if(tmpWizLvl > 7) {
										charSheet.addValue('class', 'schoolNotes', '<span title="At 8th level, you can emit a 30-foot aura of despair for a number of rounds per day equal to your wizard level. Enemies within this aura take a –2 penalty on ability checks, attack rolls, damage rolls, saving throws, and skill checks. These rounds do not need to be consecutive.">Aura Of Despair</span>')
									}
								}
								if(varClassFeatureDetail == 'Evocation') {
									charSheet.addValue('class', 'schoolNotes', '<span title="Whenever you cast an evocation spell that deals hit point damage, add 1/2 your wizard level to the damage (minimum +1). This bonus only applies once to a spell, not once per missile or ray, and cannot be split between multiple missiles or rays. This damage is of the same type as the spell. At 20th level, whenever you cast an evocation spell you can roll twice to penetrate a creature’s spell resistance and take the better result.">Intense Spells</span>')
									charSheet.addValue('class', 'schoolNotes', '<span title="As a standard action you can unleash a force missile that automatically strikes a foe, as magic missile. The force missile deals 1d4 points of damage plus the damage from your intense spells evocation power. This is a force effect. You can use this ability a number of times per day equal to 3 + your Intelligence modifier.">Force Missile</span>')
									if(tmpWizLvl > 7) {
										charSheet.addValue('class', 'schoolNotes', '<span title="At 8th level, you can create a wall of energy that lasts for a number of rounds per day equal to your wizard level. These rounds do not need to be consecutive. This wall deals acid, cold, electricity, or fire damage, determined when you create it. The elemental wall otherwise functions like wall of fire.">Elemental Wall</span>')
									}
								}
								if(varClassFeatureDetail == 'Illusion') {
									charSheet.addValue('class', 'schoolNotes', '<span title="Any illusion spell you cast with a duration of “concentration” lasts a number of additional rounds equal to 1/2 your wizard level after you stop maintaining concentration (minimum +1 round). At 20th level, you can make one illusion spell with a duration of “concentration” become permanent. You can have no more than one illusion made permanent in this way at one time. If you designate another illusion as permanent, the previous permanent illusion ends.">Extended Illusions</span>')
									charSheet.addValue('class', 'schoolNotes', '<span title="As a standard action you can fire a shimmering ray at any foe within 30 feet as a ranged touch attack. The ray causes creatures to be blinded for 1 round. Creatures with more Hit Dice than your wizard level are dazzled for 1 round instead. You can use this ability a number of times per day equal to 3 + your Intelligence modifier.">Blinding Ray</span>')
									if(tmpWizLvl > 7) {
										charSheet.addValue('class', 'schoolNotes', '<span title="At 8th level, you can make yourself invisible as a swift action for a number of rounds per day equal to your wizard level. These rounds do not need to be consecutive. This otherwise functions as greater invisibility.">Invisibility Field</span>')
									}
								}
								if(varClassFeatureDetail == 'Necromancy') {
									charSheet.addValue('class', 'schoolNotes', '<span title="You receive Command Undead or Turn Undead as a bonus feat. You can channel energy a number of times per day equal to 3 + your Intelligence modifier, but only to use the selected feat. You can take other feats to add to this ability, such as Extra Channel and Improved Channel, but not feats that alter this ability, such as Elemental Channel and Alignment Channel. The DC to save against these feats is equal to 10 + 1/2 your wizard level + your Charisma modifier. At 20th level, undead cannot add their channel resistance to the save against this ability.">Power over Undead</span>')
									charSheet.addValue('class', 'schoolNotes', '<span title="As a standard action, you can make a melee touch attack that causes a living creature to become shaken for a number of rounds equal to 1/2 your wizard level (minimum 1). If you touch a shaken creature with this ability, it becomes frightened for 1 round if it has fewer Hit Dice than your wizard level. You can use this ability a number of times per day equal to 3 + your Intelligence modifier.">Grave Touch</span>')
									if(tmpWizLvl > 7) {
										charSheet.addValue('class', 'schoolNotes', '<span title="At 8th level, you gain blindsight to a range of 10 feet for a number of rounds per day equal to your wizard level. This ability only allows you to detect living creatures and undead creatures. This sight also tells you whether a creature is living or undead. Constructs and other creatures that are neither living nor undead cannot be seen with this ability. The range of this ability increases by 10 feet at 12th level, and by an additional 10 feet for every four levels beyond 12th.">Life Sight</span>')
									}
								}
								if(varClassFeatureDetail == 'Transmutation') {
									charSheet.addValue('class', 'schoolNotes', '<span title="You gain a +1 enhancement bonus to one physical ability score (Strength, Dexterity, or Constitution). This bonus increases by +1 for every five wizard levels you possess to a maximum of +5 at 20th level. You can change this bonus to a new ability score when you prepare spells. At 20th level, this bonus applies to two physical ability scores of your choice.">Physical Enhancement</span>')
									charSheet.addValue('class', 'schoolNotes', '<span title="As a standard action you can strike with a telekinetic fist, targeting any foe within 30 feet as a ranged touch attack. The telekinetic fist deals 1d4 points of bludgeoning damage + 1 for every two wizard levels you possess. You can use this ability a number of times per day equal to 3 + your Intelligence modifier.">Telekinetic Fist</span>')
									if(tmpWizLvl > 7) {
										charSheet.addValue('class', 'schoolNotes', '<span title="At 8th level, you can change your shape for a number of rounds per day equal to your wizard level. These rounds do not need to be consecutive. This ability otherwise functions like beast shape II or elemental body I. At 12th level, this ability functions like beast shape III or elemental body II.">Change Shape</span>')
									}
								}
								if(varClassFeatureDetail == 'Universalist') {
									charSheet.addValue('class', 'schoolNotes', '<span title="You cause your melee weapon to fly from your grasp and strike a foe before instantly returning to you. As a standard action, you can make a single attack using a melee weapon at a range of 30 feet. This attack is treated as a ranged attack with a thrown weapon, except that you add your Intelligence modifier on the attack roll instead of your Dexterity modifier (damage still relies on Strength). This ability cannot be used to perform a combat maneuver. You can use this ability a number of times per day equal to 3 + your Intelligence modifier.">Hand of the Apprentice</span>')
									if(tmpWizLvl > 7) {
										charSheet.addValue('class', 'schoolNotes', '<span title="At 8th level, you can apply any one metamagic feat that you know to a spell you are about to cast. This does not alter the level of the spell or the casting time. You can use this ability once per day at 8th level and one additional time per day for every two wizard levels you possess beyond 8th. Any time you use this ability to apply a metamagic feat that increases the spell level by more than 1, you must use an additional daily usage for each level above 1 that the feat adds to the spell. Even though this ability does not modify the spell’s actual level, you cannot use this ability to cast a spell whose modified spell level would be above the level of the highest-level spell that you are capable of casting.">Metamagic Mastery</span>')
									}
								}
								// edit
								charSheet.edit.arcaneSchool = buildDropDown('classfeature' + varClassFeatureID, 
								                                           varClassFeatureDetail, 
								                                           ['Abjuration',
								                                            'Conjuration',
								                                            'Divination',
								                                            'Enchantment',
								                                            'Evocation',
								                                            'Illusion',
								                                            'Necromancy',
								                                            'Transmutation',
								                                            'Universalist'
								                                           ])
								break
							case 'Opposition School':
								charSheet.adjustments.class.oppositionSchoolDesc = varClassFeatureHTML
								charSheet.addValue('class', 'oppSchool', varClassFeatureDetail)
								// edit
								tmpOppositionSchoolMenu = buildDropDown('classfeature' + varClassFeatureID, 
								                                       varClassFeatureDetail, 
								                                       ['Abjuration',
								                                        'Conjuration',
								                                        'Divination',
								                                        'Enchantment',
								                                        'Evocation',
								                                        'Illusion',
								                                        'Necromancy',
								                                        'Transmutation',
								                                       ])
								if(charSheet.edit.oppositionSchools) {
									charSheet.edit.oppositionSchools += tmpOppositionSchoolMenu
								}
								else {
									charSheet.edit.oppositionSchools = tmpOppositionSchoolMenu
								}
								break
						}
					}

					// if this class feature is a spell: add to the spell list
					temp = varClassFeatureName.indexOf('-level')
					if(temp > -1) {
						temp += 7
						// isolate class name from feature name
						tmpEnd = charSheet.class_features[i].name.indexOf(' ', temp)
						tmpSpellClass = buildClassAbbrev(charSheet.class_features[i].name.substr(temp, tmpEnd - temp))

						// only set level if spell is obtainable based on ability score
						tmpSpellLevel = ValidateBonusSpell(i)

						// if bard or sorcerer: only process 'known' spells here
						if(tmpSpellClass == 'BRD' || tmpSpellClass == 'SOR') {
							if(charSheet.class_features[i].name.indexOf('Known') == -1) {
								tmpSpellLevel = -1
							}
						}

						// if not an illegal spell level
						if(tmpSpellLevel > -1) {
							if(charSheet.spell_list[tmpSpellClass]) {
								if(charSheet.spell_list[tmpSpellClass][tmpSpellLevel][varClassFeatureDetail]) {
									charSheet['sa_' + tmpSpellLevel + '_' + tmpSpellClass + '_known']
										+= '; <span title="' 
										 + charSheet.spell_desc_list[tmpSpellClass][tmpSpellLevel][varClassFeatureDetail] 
										 + '">' 
										 + charSheet.spell_list[tmpSpellClass][tmpSpellLevel][varClassFeatureDetail] 
										 + '</span>'
								}
								// edit
								charSheet['output' + tmpSpellLevel + tmpSpellClass + 'Known'] 
									+= '<select id="spellknown' + charSheet.class_features[i].id + '">'
									 + '<option value="' + varClassFeatureDetail + '">' 
									 + charSheet.spell_list[tmpSpellClass][tmpSpellLevel][varClassFeatureDetail] 
									 + '</option><option value="0">--------------------</option>'
								if(tmpSpellClass != 'DOMAIN') {
									charSheet['output' + tmpSpellLevel + tmpSpellClass + 'Known']
										+= charSheet.getValue('output' + tmpSpellLevel + tmpSpellClass + 'List') 
										 + '</select>'
								}
							}
						}
					}

					// check for duplicates
					charSheet.class_features[i].count = 1
					if(i > 0) {
						for(j = 0; j < i; j++) {
							if(charSheet.class_features[j].count > 0
							&& charSheet.class_features[i].count > 0
							&& charSheet.class_features[i].name == charSheet.class_features[j].name) {
								charSheet.class_features[j].count++
								charSheet.class_features[i].count = 0
							}
						}
					}
				}
			}

			// edit, after all class features are processed
			if(charSheet.edit.ragePowers) {
				$('#editRagePowers').html('Rage Powers ' + charSheet.edit.ragePowers)
				$('#spellsSection').show()
			}
			if(charSheet.edit.versatilePerf) {
				$('#editVersatilePerf').html('Versatile Performance ' + charSheet.edit.versatilePerf)
			}
			if(charSheet.edit.domains) {
				$('#editDomains').html('<br>Domains ' + charSheet.edit.domains)
			}

			// prepare output
			// compress duplicates (e.g. x2, x3)
			// add notes or formatting
			for(i = 0, j = ''; i < charSheet.class_features.length; i++) {
				if(charSheet.class_features[i] && charSheet.class_features[i].count > 0) {
					j += '<span title=\'' + charSheet.class_features[i].description + '\'>' 
					   + charSheet.class_features[i].name + '</span>'
					if(charSheet.class_features[i].count > 1) {
						j += ' x' + charSheet.class_features[i].count
					}
					if(charSheet.class_features[i].name.substr(-14, 14) == 'Spells per Day') {
						tmpCount = charSheet.class_features[i].count
						tmpLevel = charSheet.class_features[i].name.substr(0, 1)
						if(charSheet.class_features[i].name.indexOf('Adept') > 0) {
							if(!charSheet.spells.ADP) {
								charSheet.spells.ADP = {}
							}
							charSheet.spells.ADP[tmpLevel] = {}
							charSheet.spells.ADP[tmpLevel].perDay = tmpCount
							charSheet.spells.ADP.concentration = 10 + 1 * charSheet.getNumber('class_level.ADP') 
							                                   + charSheet.bonus(charSheet.getTotal('wis'))
							charSheet.spells.ADP.title = 'Adept Spells Prepared'
						}
						if(charSheet.class_features[i].name.indexOf('Bard') > 0) {
							if(!charSheet.spells.BRD) {
								charSheet.spells.BRD = {}
							}
							charSheet.spells.BRD[tmpLevel] = {}
							charSheet.spells.BRD[tmpLevel].perDay = tmpCount
							charSheet.spells.BRD.concentration = 10 + 1 * charSheet.getNumber('class_level.BRD') 
							                                   + charSheet.bonus(charSheet.getTotal('cha'))
							charSheet.spells.BRD.title = 'Bard Spells Known'
						}
						if(charSheet.class_features[i].name.indexOf('Cleric') > 0) {
							if(!charSheet.spells.CLR) {
								charSheet.spells.CLR = {}
							}
							charSheet.spells.CLR[tmpLevel] = {}
							charSheet.spells.CLR[tmpLevel].perDay = tmpCount
							charSheet.spells.CLR.concentration = 10 + 1 * charSheet.getNumber('class_level.CLR') 
							                                   + charSheet.bonus(charSheet.getTotal('wis'))
							charSheet.spells.CLR.title = 'Cleric Spells Prepared'
						}
						if(charSheet.class_features[i].name.indexOf('Druid') > 0) {
							if(!charSheet.spells.DRD) {
								charSheet.spells.DRD = {}
							}
							charSheet.spells.DRD[tmpLevel] = {}
							charSheet.spells.DRD[tmpLevel].perDay = tmpCount
							charSheet.spells.DRD.concentration = 10 + charSheet.getNumber('class_level.DRD') 
							                                   + charSheet.bonus(charSheet.getTotal('wis'))
							charSheet.spells.DRD.title = 'Druid Spells Prepared'
						}
						if(charSheet.class_features[i].name.indexOf('Paladin') > 0) {
							if(!charSheet.spells.PAL) {
								charSheet.spells.PAL = {}
							}
							charSheet.spells.PAL[tmpLevel] = {}
							charSheet.spells.PAL[tmpLevel].perDay = tmpCount
							charSheet.spells.PAL.concentration = 7 + charSheet.getNumber('class_level.PAL') 
							                                   + charSheet.bonus(charSheet.getTotal('cha'))
							charSheet.spells.PAL.title = 'Paladin Spells Prepared'
						}
						if(charSheet.class_features[i].name.indexOf('Ranger') > 0) {
							if(!charSheet.spells.RGR) {
								charSheet.spells.RGR = {}
							}
							charSheet.spells.RGR[tmpLevel] = {}
							charSheet.spells.RGR[tmpLevel].perDay = tmpCount
							charSheet.spells.RGR.concentration = 7 + charSheet.getNumber('class_level.RGR') 
							                                   + charSheet.bonus(charSheet.getTotal('wis'))
							charSheet.spells.RGR.title = 'Ranger Spells Prepared'
						}
						if(charSheet.class_features[i].name.indexOf('Sorcerer') > 0) {
							if(!charSheet.spells.SOR) {
								charSheet.spells.SOR = {}
							}
							charSheet.spells.SOR[tmpLevel] = {}
							charSheet.spells.SOR[tmpLevel].perDay = tmpCount
							charSheet.spells.SOR.concentration = 10 + charSheet.getNumber('class_level.SOR') 
							                                   + charSheet.bonus(charSheet.getTotal('cha'))
							charSheet.spells.SOR.title = 'Sorcerer Spells Known'
						}
						if(charSheet.class_features[i].name.indexOf('Wizard') > 0) {
							if(!charSheet.spells.WIZ) {
								charSheet.spells.WIZ = {}
							}
							charSheet.spells.WIZ[tmpLevel] = {}
							if(charSheet.getValue('sa_arcane_school') 
							&& charSheet.getValue('sa_arcane_school') != 'Universalist') {
								tmpCount++
							}
							charSheet.spells.WIZ[tmpLevel].perDay = tmpCount
							charSheet.spells.WIZ.concentration = 10 + charSheet.getNumber('class_level.WIZ') 
							                                   + charSheet.bonus(charSheet.getTotal('int'))
							charSheet.spells.WIZ.title = 'Wizard Spells Prepared'
						}
					}
					if(charSheet.class_features[i].name.substr(-20, 20) == 'Domain Spell per Day' 
					&& charSheet.getTotal('domains').length > 0) {
						tmpCount = charSheet.class_features[i].count
						tmpLevel = charSheet.class_features[i].name.substr(0, 1)
						if(!charSheet.spells['DOMAIN']) {
							charSheet.spells['DOMAIN'] = {}
						}
						charSheet.spells['DOMAIN'][tmpLevel] = {}
						charSheet.spells['DOMAIN'][tmpLevel].perDay = 1
					}
					j += '; '
				}
			}
			// diagnostic section
			$('#calcClassFeatures').html(j)

			// calculate domain spell options based on domains
			if(charSheet.getTotal('domains')) {
				for(var j = 1; j < 10; j++) {
					for(var i = 0; i < charSheet.getTotal('domains').length; i++) {
						charSheet['output' + j + 'DOMAINKnown'] 
							+= '<option value=\'' + charSheet.getTotal('domains')[i] + '\'>'
						   + charSheet.spell_list['DOMAIN'][j][charSheet.getTotal('domains')[i]] 
						   + '</option>'
					}
					charSheet['output' + j + 'DOMAINKnown'] += '</select>'
				}
			}

			// recalculate sections
			charSheet.buildSpells()
			charSheet.buildSQ()
			calcSkills()
			charSheet.buildSenses()
			calcDefense()

			console.log(' ... CHECK')
		}

		// check if the character has high enough primary ability for the spell
		function ValidateBonusSpell(paramCFNo)
		{
			// get name of class feature in question
			var varName = charSheet.class_features[paramCFNo].name

			// the first character is the spell level
			var spellLevel = varName.substr(0, 1)
			// if this is a bonus spell:
			if((temp = varName.indexOf('Bonus')) > -1) {
				// get the ability score, hidden in the class feature name
				var tmpAbility = charSheet.getTotal(varName.substr(temp - 7, 3).toLowerCase())
				var minAbility = varName.substr(temp - 3, 2)
				if(tmpAbility < minAbility) {
					charSheet.class_features[paramCFNo].name = '' // didn't make the cut
					return -1
				}
				charSheet.class_features[paramCFNo].name = varName.substr(0, temp - 7) + varName.substr(temp + 6)
			}
			if(spellLevel > charSheet[varName.substr(temp - 7, 3).toLowerCase()] - 10) {
				charSheet.class_features[paramCFNo].name = '' // didn't make the cut
				return -1
			}

			return spellLevel
		}

		// accept class full name and return its abbreviation
		function buildClassAbbrev(paramClassName)
		{
			switch(paramClassName.toLowerCase()) {
				case 'adept':       return 'ADP'
				case 'alchemist':   return 'ALC'
				case 'antipaladin': return 'APL'
				case 'aristocrat':  return 'ART'
				case 'barbarian':   return 'BBN'
				case 'bard':        return 'BRD'
				case 'cleric':      return 'CLR'
				case 'commoner':    return 'COM'
				case 'druid':       return 'DRD'
				case 'expert':      return 'EXP'
				case 'fighter':     return 'FTR'
				case 'inquisitor':  return 'INQ'
				case 'magus':       return 'MAG'
				case 'monk':        return 'MNK'
				case 'oracle':      return 'ORA'
				case 'paladin':     return 'PAL'
				case 'ranger':      return 'RGR'
				case 'rogue':       return 'ROG'
				case 'sorcerer':    return 'SOR'
				case 'summoner':    return 'SUM'
				case 'warrior':     return 'WAR'
				case 'witch':       return 'WCH'
				case 'wizard':      return 'WIZ'
				default:            return paramClassName.substr(0).toUpperCase()
			}
		}

		// create html select and options from array list
		function buildDropDown(paramID, paramValue, paramList)
		{
			console.log('function buildDropDown(paramID =' + paramID + ', paramValue = ' + paramValue + ', paramList = ' + typeof(paramList))

			var varRet  = '<select id=\'' + paramID + '\'>'
			varRet += '<option value=\'' + paramValue + '\'>' + paramValue + '</option>'
			varRet += '<option value=\'\'>--------------------</option>'
			for(var i in paramList) {
				varRet += '<option value=\'' + paramList[i] + '\'>' + paramList[i] + '</option>'
			}
			varRet += '</select>'

			console.log(' ... CHECK')
			return varRet
		}

		// calculate treasure section
		function calcTreasure()
 		{
			console.log('function calcTreasure()')

			// reset gear adjustments
			charSheet.adjustments.gear = new CharSheetAdjustments()

			// set deflection values for armour
			// removed - handled in treasure subsection now
			// charSheet.addValue('gear', 'acDeflect', charSheet.getNumber('shield.armour_ac'))
			// charSheet.addValue('gear', 'acDeflect', charSheet.getNumber('armour.armour_ac'))

			// calculate value of gear based on cr
			var tmpMaxValue = 0
			charSheet.total_value = $('#total_value').val()

			switch(charSheet.getTotal('cr')) {
				case -2: tmpMaxValue = 130
				         break
				case -1: tmpMaxValue = 260
				         break
				case 0:  tmpMaxValue = 390
				         break
				case 1:  tmpMaxValue = 780
				         break
				case 2:  tmpMaxValue = 1650
				         break
				case 3:  tmpMaxValue = 2400
				         break
				case 4:  tmpMaxValue = 3450
				         break
				case 5:  tmpMaxValue = 4650
				         break
				case 6:  tmpMaxValue = 6000
				         break
				case 7:  tmpMaxValue = 7800
				         break
				case 8:  tmpMaxValue = 10050
				         break
				case 9:  tmpMaxValue = 12750
				         break
				case 10: tmpMaxValue = 16350
				         break
				case 11: tmpMaxValue = 21000
				         break
				case 12: tmpMaxValue = 27000
				         break
				case 13: tmpMaxValue = 34800
				         break
				case 14: tmpMaxValue = 45000
				         break
				case 15: tmpMaxValue = 58500
				         break
				case 16: tmpMaxValue = 75000
				         break
				case 17: tmpMaxValue = 96000
				         break
				case 18: tmpMaxValue = 123000
				         break
				case 19: tmpMaxValue = 159000
				         break
			}
			// add gold bonus to value of gear
			tmpMaxValue += charSheet.getTotal('gold')

			// load
			var tmpCarryingCap = charSheet.getTotal('encumbrance')
			charSheet.total_weight = $('#total_weight').val()

			// if light load:
			if(charSheet.getNumber('total_weight') <= tmpCarryingCap) {
				tmpLoad = 'light load'
				charSheet.loadType = 0
				// if armoured:
				if(charSheet.getValue('armour')) {
					// no monk bonuses
					charSheet.addValue('gear', 'acMonk', -1 * charSheet.getTotal('acMonk'))
					charSheet.addValue('gear', 'acWisdom', -1 * charSheet.getTotal('acWisdom'))
				}
			}
			// otherwise: medium, heavy or extreme load:
			else {
				// calculate load type
				charSheet.loadType = Math.ceil((charSheet.getNumber('total_weight') - tmpCarryingCap) / (tmpCarryingCap / 4))

				// no monk bonuses
				charSheet.addValue('gear', 'acMonk', -1 * charSheet.getTotal('acMonk'))
				charSheet.addValue('gear', 'acWisdom', -1 * charSheet.getTotal('acWisdom'))
				
				// calculate load description
				if(charSheet.getNumber('total_weight') <= tmpCarryingCap * 2) {
					tmpLoad = 'medium load'
				}
				else if(charSheet.getNumber('total_weight') <= tmpCarryingCap * 3) {
					tmpLoad = 'heavy load'
				}
				else {
					tmpLoad = 'extreme load'
				}
				tmpLoad += ': -' + charSheet.getNumber('loadType') + ' Str'
			}

			// update screen
			$('#calcLoad').text('/ ' + Math.floor(tmpCarryingCap) + ' lbs. (' + tmpLoad + ')')
			$('#calcMaxValue').text('/ ' + tmpMaxValue)

			// recalculate sections
			calcSkills()
			calcOffense()
			calcDefense()
			charSheet.buildSpeed()

			console.log(' ... CHECK')
		}

		// onload
		function start(pkid)
		{
			console.log('function start(pkid = ' + pkid + ')')
			if(!pkid) {
				// ajax call to populate add section
				buildSection('Add')
			}
			else {
				// show loading widgets
				$('#mainSection').html('<h6 class=\'statBlockTitle\'>LOADING</h6>' + loadingWidget)
				$('#defenseSection').html(loadingWidget)
				$('#offenseSection').html(loadingWidget)
				$('#abilitiesSection').html(loadingWidget)
				$('#featsSection').html(loadingWidget)
				$('#skillsSection').html(loadingWidget)
				$('#languagesSection').html(loadingWidget)
				$('#specialabilitiesSection').html(loadingWidget)
				$('#treasureSection').html(loadingWidget)
				$('#descriptionSection').html(loadingWidget)
				$('#organizationSection').html(loadingWidget)
				$('#encounterSection').html(loadingWidget)

				// ajax call to populate first section, which cascades to populate all sections on the page
				buildSection('Clone')
				buildSection('Abilities')
			}
			console.log(' ... CHECK')
		}

		// recalculate changed sections
		function recalc()
		{
			alert('To do: ' + printobj(charSheet.recalc))
		}

	</script>
