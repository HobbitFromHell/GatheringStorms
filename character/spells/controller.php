<?php

// //////////
// validation
// //////////

if (!($pkid > 0 and $pkid < 65000)) {
	echo "FAIL: Character ID {$pkid}is invalid";
	return false;
}

// $post_class_list = sanitize($_POST[class_list]);


// //////
// insert
// //////

post_data("t_characters_character_classes_class_features", "character_classes_class_feature_id", "character_id", Array(Array("classfeature", "detail")));
if(post_data("t_characters_character_classes_class_features", "character_classes_class_feature_id", "character_id", Array(Array("spellknown", "detail")))) {
	echo "<script>buildSection('main')</script>\n";
	return;
}


// //////
// select
// //////

$view = new DataCollector();
$view->characterSpells[class_list] = json_decode($_POST[class_list]);

// build class spell lists
$varIncludeDomain = false;
foreach($view->characterSpells[class_list] as $varClassID => $varClassLevel) {
	if($varClassID == "CLR" or $varClassID == "DRD") {
		// only bother building domain spells lists for classes which can use them
		$varIncludeDomain = true;
	}
	if($varClassID == "BRD" or $varClassID == "CLR" or $varClassID == "DRD" or $varClassID == "SOR" or $varClassID == "WIZ" or $varClassID == "RGR" or $varClassID == "PAL" or $varClassID == "ALC" or $varClassID == "SUM" or $varClassID == "WCH" or $varClassID == "INQ" or $varClassID == "ORA" or $varClassID == "APL" or $varClassID == "MAG" or $varClassID == "ADP") {
		$j = DataConnector::selectQuery("
			 SELECT s.`id`              AS `spell_id`,
			        s.`name`            AS `name`,
			        s.`short_description`     AS `description`,
			        s.`{$varClassID}` AS `level`
			   FROM t_spells s
			  WHERE s.`{$varClassID}` <= {$varClassLevel}
			    AND s.`source` = 'PFRPG Core'
			  ORDER BY s.`name`
		");
		while($j) {
			$view->characterSpells[spell][$varClassID][$j[level]][$j[spell_id]] = $j[name]; // group spell list by class, level
			$view->characterSpells[spell_desc][$varClassID][$j[level]][$j[spell_id]] = $j[description];
			$j = DataConnector::selectQuery();
		}
	}
}

// build domain spell lists, if required
if($varIncludeDomain) {
	for($i = 1; $i < 10; $i++) {
		$j = DataConnector::selectQuery("
			 SELECT s.`id`              AS `spell_id`,
			        s.`name`            AS `name`,
			        s.`description`     AS `description`,
			          LOWER(d.`name`)   AS `domain`,
			          '{$i}'            AS `level`
			   FROM t_domains d
			   JOIN t_spells s ON s.`id` = d.`spell{$i}`
			  ORDER BY s.`name`
		");
		while($j) {
			$view->characterSpells[spell]['DOMAIN'][$j[level]][$j[domain]] = $j[name];
			$view->characterSpells[spell_desc]['DOMAIN'][$j[level]][$j[domain]] = $j[description];
			$j = DataConnector::selectQuery();
		}
	}
}


// ////////////////////////////
// communicate with parent page
// ////////////////////////////

echo "<script>\n";
$js_array = json_encode($view->characterSpells[spell]);
echo "charSheet.spell_list = {$js_array}\n";
$js_array = json_encode($view->characterSpells[spell_desc]);
echo "charSheet.spell_desc_list = {$js_array}\n";
echo "buildSection('Specialqualities')\n";
echo"</script>\n";

?>
