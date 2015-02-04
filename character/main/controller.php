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

post_data("t_characters", "id", "id", Array("name", "alignment", "gender", "race_id", "hp_bonus", "sp_bonus"));
post_data("t_character_classes_characters", "id", "character_id", Array(Array("class", "character_class_id"), "level"));


// //////
// select
// //////

$view = new DataCollector;
$view->characterMain = DataConnector::selectQuery("
	 SELECT pc.`id`          AS `id`,
	        pc.`name`        AS `name`,
	        pc.`cr`          AS `cr`,
	        pc.`description` AS `description`,
	        pc.`stat_block`  AS `stat_block`,
	        pc.`alignment`   AS `alignment`,
	        pc.`gender`      AS `gender`,
	        pc.`race_id`     AS `race_id`,
	        pc.`hp_bonus`    AS `hp_bonus`,
	        pc.`sp_bonus`    AS `sp_bonus`
	   FROM t_characters pc
	  WHERE pc.`id` = {$pkid}
");

//	$view->characterMain[alignment] = $view->getEnum("t_characters", "alignment", $view->characterMain[alignment]);
$view->characterMain[alignment] = $view->setList($view->characterMain[alignment], array("","LG","NG","CG","LN","N","CN","LE","NE","CE"));
$view->characterMain[gender] = $view->setList($view->characterMain[gender], array("","Male", "Female"));

// select race details
$view->characterMain[race] = DataConnector::selectQuery("
	 SELECT r.`name`        AS `name`,
	        r.`short`       AS `short`,
	        r.`description` AS `description`
	   FROM t_races r
	  WHERE r.`id` = {$view->characterMain[race_id]}
");

// build race list
$j = DataConnector::selectQuery("
	 SELECT r.`id`          AS `id`,
	        r.`name`        AS `name`
	   FROM t_races r
	  ORDER BY r.`short`, r.`name`
");
while($j) {
	$view->characterMain[race]['list'][] = $j;
	$j = DataConnector::selectQuery();
}

// select racial traits
$j = DataConnector::selectQuery("
	 SELECT rt.`id`                 AS `id`,
	        rt.`name`               AS `name`,
	        rt.`description`        AS `description`,
	        IFNULL(crt.`detail`,'') AS `detail`
	   FROM t_racial_traits rt
	   JOIN t_races_racial_traits rrt
	     ON rt.`id` = rrt.`racial_trait_id`
	    AND rrt.`race_id` = {$view->characterMain[race_id]}
	   LEFT JOIN t_characters_racial_traits crt
	          ON rt.`id` = crt.`racial_trait_id`
	         AND crt.`character_id` = {$pkid}
");
while($j) {
		$view->characterMain[race][racial_traits][] = $j;
		$j = DataConnector::selectQuery();
}

// select class details
$j = DataConnector::selectQuery("
	 SELECT cc.`id`          AS `id`,
	         c.`id`          AS `class_id`,
	         c.`name`        AS `name`,
	        cc.`level`       AS `level`,
	         c.`description` AS `description`,
	         c.`hd`          AS `hd`,
	         c.`sp`          AS `sp`,
	         c.`bab`         AS `bab`,
	         c.`fort`        AS `fort`,
	         c.`ref`         AS `ref`,
	         c.`will`        AS `will`
	   FROM t_character_classes_characters cc
	   JOIN t_character_classes c
	     ON c.`id` = cc.`character_class_id`
	  WHERE cc.`character_id` = {$pkid}
	    AND cc.`is_deleted` != 'Yes'
	    AND cc.`level` > 0
");
while($j) {
	$view->characterMain['class'][] = $j;
	$j = DataConnector::selectQuery();
}

// cummulative class properties
$view->total_level = 0;
$view->class_level = [];
$view->total_classes = [];
$view->domain_list = [];
$view->total_hd = [];
$view->hp_main = 0;
$view->hp_desc_main = "";
$view->sp_main = 0;
$view->total_bab = 0;
$view->total_fort = 0;
$view->total_ref = 0;
$view->total_will = 0;
$view->level_quality = -3;

foreach($view->characterMain['class'] as $varClass) {
	$view->total_level += $varClass[level];
	$view->total_classes[] = $varClass[class_id];
	$view->class_level[$varClass[class_id]] = $varClass[level];
	$view->total_hd[$varClass[hd]] += $varClass[level];
	$view->sp_main += $varClass[sp] * $varClass[level];
	$view->total_bab += floor($varClass[level] * $varClass[bab] / 12);
	$view->total_fort += floor($varClass[level] * $varClass[fort] / 12);
	if($varClass[fort] == 6) {
		$view->total_fort += 2;
	}
	$view->total_ref += floor($varClass[level] * $varClass[ref] / 12);
	if($varClass[ref] == 6) {
		$view->total_ref += 2;
	}
	$view->total_will += floor($varClass[level] * $varClass[will] / 12);
	if($varClass[will] == 6) {
		$view->total_will += 2;
	}
	if($varClass[class_id] != "COM") {
		if(($varClass[class_id] == "ADP" or $varClass[class_id] == "ART" or $varClass[class_id] == "EXP" or $varClass[class_id] == "WAR") and $view->level_quality = -3) {
			$view->level_quality = -2;
		}
		else {
			$view->level_quality = -1;
		}
	}

	// select class features
	$j = DataConnector::selectQuery("
		 SELECT cf.`id`                      AS `class_feature_id`,
		      cccf.`id`                      AS `id`,
		        cf.`name`                    AS `name`,
		           IFNULL(ccccf.`detail`,'') AS `detail`,
		        cf.`description`             AS `description`
		   FROM t_class_features cf
		   JOIN t_character_classes_class_features cccf ON cf.`id` = cccf.`class_feature_id`
		   LEFT JOIN t_characters_character_classes_class_features ccccf
		          ON ccccf.`character_id` = {$pkid}
		         AND ccccf.`character_classes_class_feature_id` = cccf.`id`
		  WHERE cccf.`character_class_id` = '{$varClass[class_id]}'
		    AND cccf.`level` <= {$varClass[level]}
	");
	while($j) {
			$view->characterMain['class'][class_features][] = $j;
			if($j[class_feature_id] == 52) {
				$view->domain_list[] = $j[detail];
			}
			$j = DataConnector::selectQuery();
	}

	// select domain details
	foreach($view->domain_list as $domain) {
		$view->characterMain[domains][$domain] = DataConnector::selectQuery("
			 SELECT d.`power1` AS `1`,
			        d.`power2` AS `2`,
			        d.`power3` AS `3`,
			        d.`power4` AS `4`,
			        d.`power5` AS `5`,
			        d.`power6` AS `6`,
			        d.`power7` AS `7`,
			        d.`power8` AS `8`,
			        d.`power9` AS `9`
			   FROM t_domains d
			  WHERE d.`name` = '{$domain}'
			    AND d.`is_deleted` != 'Yes'
		");
	}
}

// hp description
foreach($view->total_hd as $key => $value) {
	$view->hp_main += ($key * $value / 2);
	$view->hp_desc_main .= " +" . $value . "d" . $key;
}
if($view->characterMain[hp_bonus] > 0) {
	$view->hp_main += $view->characterMain[hp_bonus];
	$view->hp_desc_main .= " +" . $view->characterMain[hp_bonus] . " favoured class";
}

// calculate cr based on total level and level quality (NPC or PC classes)
$varTmpCR = $view->total_level + $view->level_quality;
// check for change from current database state
if($varTmpCR != $view->characterMain[cr]) {
	// update cr in database
	DataConnector::updateQuery("
		 UPDATE IGNORE t_characters pc
		    SET pc.`cr` = {$varTmpCR}
		  WHERE pc.`id` = {$pkid}
	");
}
// format for display
$view->cr = cr_display($varTmpCR);

// build class list
$j = DataConnector::selectQuery("
	 SELECT c.`id`   AS `id`,
	        c.`name` AS `name`
	   FROM t_character_classes c
	  WHERE c.`is_deleted` != 'Yes'
	  ORDER BY c.`name`
");
while($j) {
	$view->characterMain['class']['list'][] = $j;
	$j = DataConnector::selectQuery();
}


// ////////////////////////////
// communicate with parent page
// ////////////////////////////

echo "<script>\n";
echo "charSheet.total_level = {$view->total_level}\n";
echo "charSheet.total_bab = {$view->total_bab}\n";
echo "charSheet.race = \"{$view->characterMain[race][name]}\"\n";
$js_array = json_encode($view->characterMain[race][racial_traits]);
echo "charSheet.racial_traits = {$js_array}\n";
$js_array = json_encode($view->characterMain['class'][class_features]);
echo "charSheet.class_features = {$js_array}\n";
$js_array = json_encode($view->total_classes);
echo "charSheet.total_classes = {$js_array}\n";
$js_array = json_encode($view->class_level);
echo "charSheet.class_level = {$js_array}\n";
$js_array = json_encode($view->characterMain[domains]);
echo "charSheet.domain_powers = {$js_array}\n";
echo "buildSection('Specialabilities')\n";
$view->characterMain[stat_block] = str_replace("\r", "", $view->characterMain[stat_block]);
$view->characterMain[stat_block] = str_replace("\n", "<br>", $view->characterMain[stat_block]);
$view->characterMain[stat_block] = str_replace("\"", "''", $view->characterMain[stat_block]);
echo "$('#statblockSection').html(\"{$view->characterMain[stat_block]}\")\n";
echo "</script>\n";

?>
