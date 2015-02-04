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

post_data("t_characters_organizations", "id", "character_id", Array(Array("organization", "organization_id"), "title", "master_id", "is_deleted"));


// //////
// select
// //////

$view = new DataCollector;

$view->characterOrganization[organization][] = DataConnector::selectQuery("
	 SELECT co.`id`              AS `id`,
	        co.`organization_id` AS `org_id`,
	        co.`title`           AS `title`,
	        co.`master_id`       AS `master_id`,
	         o.`name`            AS `name`,
	           CONCAT(o.`xco`, 'x', o.`yco`) AS `location`
	   FROM t_characters_organizations co
	   JOIN t_organizations o ON co.`organization_id` = o.`id`
	  WHERE co.`character_id` = {$pkid}
	    AND co.`is_deleted` != 'Yes'
");
while($j = DataConnector::selectQuery()) {
	$view->characterOrganization[organization][] = $j;
}

// select organization details
if($view->characterOrganization[organization][0]) {
	for($i = 0; $i < count($view->characterOrganization[organization]); $i++) {
		// select all characters within this organization
		$j = DataConnector::selectQuery("
			 SELECT co.`character_id` AS `id`,
			        co.`title`        AS `title`,
			        pc.`name`         AS `name`,
			        pc.`cr`           AS `cr`,
			        co.`master_id`    AS `master_id`
			   FROM `t_characters_organizations` co
			   JOIN `t_characters` pc
			     ON co.`character_id` = pc.`id`
			  WHERE co.`organization_id` = {$view->characterOrganization[organization][$i][org_id]}
			    AND co.`is_deleted` != 'Yes'
			  ORDER BY pc.`cr` DESC
		");
		$view->characterOrganization['list'][] = $view->characterOrganization[organization][$i];
		while($j) {
			// add the record to a members list, indexed by character ID
			$view->characterOrganization[organization][$i][members][$j[id]] = $j;
			// add the record to a list of followers' character ID, indexed by master ID (0 = no master)
			$view->characterOrganization[organization][$i][master][$j[master_id]][] = $j[id];
			// snag the master name from the main array
			if($view->characterOrganization[organization][$i][master_id] == $j[id]) {
				$view->characterOrganization[organization][$i][master][name] = $j[name];
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
	   LEFT JOIN t_characters pc
	     ON pc.`id` = {$pkid}
	  WHERE o.`location_id` = pc.`location_id`
	  ORDER BY o.`name`
");
while($j) {
	$view->characterOrganization[organization]['list'][] = $j;
	$j = DataConnector::selectQuery();
}

// if a membership organization is not on the current list of local orgs, add it
foreach($view->characterOrganization[organization] as $varOrg) {
	if($varOrg[org_id] and !array_key_exists($varOrg[org_id], $view->characterOrganization[organization]['list'])) {
		$k[id] = $varOrg[org_id];
		$k[name] = $varOrg[name];
		$view->characterOrganization[organization]['list'][] = $k;
	}
}


// ////////////////////////////
// communicate with parent page
// ////////////////////////////

echo "<script>\n";
echo "buildSection('Encounter')\n";
echo "</script>\n";

?>
