<?php

// //////////
// validation
// //////////

if (!($pkid > 0 and $pkid < 65000)) {
	echo "FAIL: Character ID {$pkid} is invalid";
	return false;
}

$view = new DataCollector();
$view->characterSkills[class_list] = json_decode($_POST[class_list]);


// //////
// insert
// //////

post_data("t_characters_skills", "id", "character_id", Array(Array("skill", "skill_id"), "rank", "total", "is_deleted"));


// ///////////
// SELECT data
// ///////////

$view->characterSkills[skill][] = DataConnector::selectQuery("
	 SELECT cs.`id`         AS `id`,
	         s.`id`         AS `skill_id`,
	         s.`name`       AS `name`,
	        cs.`total`      AS `total`,
	        cs.`rank`       AS `rank`,
	         s.`class`      AS `class`,
	         s.`untrained`  AS `untrained`,
	         s.`ability`    AS `ability`,
	        cs.`is_deleted` AS `is_deleted`
	   FROM t_characters_skills cs
	   JOIN t_skills s ON s.`id` = cs.`skill_id`
	  WHERE cs.`character_id` = {$pkid}
	    AND cs.`is_deleted` != 'Yes'
	  ORDER BY cs.`total` DESC
");
while($j = DataConnector::selectQuery()) {
	$view->characterSkills[skill][] = $j;
}

// build skill list
$j = DataConnector::selectQuery("
	 SELECT s.`id`          AS `id`,
	        s.`name`        AS `name`,
	        s.`class`       AS `class`
	   FROM t_skills s
	  ORDER BY s.`name`
");
$view->characterSkills[skill]['list'][][name] = "---------- Class Skills ----------";
while($j) {
	$j[is_class_skill] = 0;
	foreach($view->characterSkills[class_list] as $class_abbrev => $class_level) {
		if(strpos(strtolower($j['class']), strtolower($class_abbrev)) !== false) {
			$j[is_class_skill] = 1;
		}
	}
	if($j[is_class_skill]) {
		$view->characterSkills[skill]['list'][] = $j;
	}
	else {
		$view->characterSkills[skill][not_class_list][] = $j;
	}
	$j = DataConnector::selectQuery();
}
if($view->characterSkills[skill][not_class_list]) {
	$view->characterSkills[skill]['list'][][name] = "---------- Other Skills ----------";
	foreach($view->characterSkills[skill][not_class_list] as $otherSkill) {
		$view->characterSkills[skill]['list'][] = $otherSkill;
	}
	unset($view->characterSkills[skill][not_class_list]);
}


// ////////////////////////////
// communicate with parent page
// ////////////////////////////

echo "<script>\n";
$js_array = json_encode($view->characterSkills[skill]['list']);
echo "charSheet.skill_list = {$js_array}\n";
$varOutput = $view->characterSkills[skill];
unset($varOutput['list']);
$js_array = json_encode($varOutput);
echo "charSheet.skills = {$js_array}\n";
echo "buildSection('Offense')\n";
echo "</script>\n";

?>
