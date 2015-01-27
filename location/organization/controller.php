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

//	post_data("t_characters_organizations", "id", "character_id", Array(Array("organization", "organization_id"), "title", "master_id", "is_deleted"));


// //////
// select
// //////

$view = new DataCollector;

$view->locationOrganization[organization][] = DataConnector::selectQuery("
	 SELECT o.`id`   AS `id`,
	        o.`name` AS `name`
	   FROM `t_organizations` o
	  WHERE CONCAT(o.`xco`, 'x', o.`yco`) = '{$pkid}'
");
while($j = DataConnector::selectQuery()) {
	$view->locationOrganization[organization][] = $j;
}

// select all members of each organization
if($view->locationOrganization[organization][0]) {
	for($i = 0; $i < count($view->locationOrganization[organization]); $i++) {
		// select all characters within this organization
		$j = DataConnector::selectQuery("
			 SELECT pc.`id`           AS `id`,
			        co.`title`        AS `title`,
			        pc.`name`         AS `name`,
			        pc.`cr`           AS `cr`,
			        co.`master_id`    AS `master_id`
			   FROM    `t_characters_organizations` co
			   JOIN    `t_characters` pc
			     ON co.`character_id` = pc.`id`
			  WHERE co.`organization_id` = '{$view->locationOrganization[organization][$i][id]}'
			    AND co.`is_deleted` != 'Yes'
			  ORDER BY pc.`cr` DESC
		");
		while($j) {
			$view->locationOrganization[organization][$i][members][$j[id]] = $j;
			$view->locationOrganization[organization][$i][master][$j[master_id]][] = $j[id];
			if($view->locationOrganization[organization][$i][master_id] == $j[id]) {
				$view->locationOrganization[organization][$i][master][name] = $j[name];
			}
			$j = DataConnector::selectQuery();
		}
	}
}

// build organization list
$j = DataConnector::selectQuery("
	 SELECT o.`id`   AS `id`,
	        o.`name` AS `name`
	   FROM t_organizations o
	  ORDER BY o.`name`
");
while($j) {
		$view->locationOrganization[organization]['list'][] = $j;
		$j = DataConnector::selectQuery();
}

?>
