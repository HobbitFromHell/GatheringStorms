<?php

function preecho($string)
{
	echo "<br><pre><br>";
	print_r ($string);
	echo "</pre><br>";
}

function cr_display($param_cr)
{
	$var_output = $param_cr;
	switch($param_cr) {
		case "0": $var_output = "1/2"; break;
		case "-1": $var_output = "1/3"; break;
		case "-2": $var_output = "1/4"; break;
	}
	return $var_output;
}

function build_org($paramOrganizationID, $paramMember, $paramOrganizationList)
{
	global $pkid;

	echo("<table class=\"orgTable\"><tr class=\"orgTR\">");
	if($pkid == $paramMember) {
		// current character (highlight)
		echo("<td class=\"orgThisMember\" colspan=\"99\">");
	}
	else {
		// other character (link)
		echo("<td class=\"orgMember\" colspan=\"99\">");
		echo("<a class=\"noHighlight\" href=\"/character/?id={$paramOrganizationList[organization][$paramOrganizationID][members][$paramMember][id]}\" id=\"o{$paramOrganizationID}m{$paramMember}\">");
	}
	echo("{$paramOrganizationList[organization][$paramOrganizationID][members][$paramMember][title]}<br>");
	echo("{$paramOrganizationList[organization][$paramOrganizationID][members][$paramMember][name]}<br>");
	$var_cr = cr_display($paramOrganizationList[organization][$paramOrganizationID][members][$paramMember][cr]);
	echo("CR {$var_cr}");
	if($pkid != $paramMember) {
		echo("</a>");
	}
	echo("</td></tr><tr class=\"orgTR\"><td class=\"orgTD\"><br>");

	// search for followers
	for($i = 0; $i < count($paramOrganizationList[organization][$paramOrganizationID][master][$paramMember]); $i++) {
		echo("<td class=\"orgTD\">");
		build_org($paramOrganizationID, $paramOrganizationList[organization][$paramOrganizationID][master][$paramMember][$i], $paramOrganizationList);
		echo("</td>");
	}

	// add new follower button
	if($pkid == $paramMember) {
		echo("<td class=\"orgTD\">");
		echo("<table class=\"orgTable\"><tr class=\"orgTR\">");
		echo("<td class=\"orgMember\" colspan=\"99\" style=\"padding-top: 7px;\">");
		echo("<form action=\"add/?id=0\" method=\"POST\" id=\"addNewMemberOrg{$paramOrganizationID}\">");
		echo("<input type=\"hidden\" name=\"organization\" value=\"{$paramOrganizationList[organization][$paramOrganizationID][org_id]}\">");
		echo("<input type=\"hidden\" name=\"master\" value=\"{$paramMember}\">");
		echo("<input type=\"hidden\" name=\"name\" value=\"\">");
		echo("<input type=\"hidden\" name=\"favoured_class\" value=\"COM\">");
		echo("<input type=\"hidden\" name=\"location\" value=\"{$paramOrganizationList[organization][$paramOrganizationID][location]}\">");
		echo("<input type=\"button\" onClick=\"$('#addNewMemberOrg{$paramOrganizationID}').submit();return false;\" value=\"+\"></form>");
		echo("</td></tr></table>");
		echo("</td>");
	}

	echo("</tr></table>");
}

function buildCharList($paramCharList) // array of objects with id, name, optional alignment, gender, race and class
{
	$varOutput = "";
	for($i = 0; $i < count($paramCharList); $i++) {
		// name
		$varOutput .= "<a href=\"/character/?id={$paramCharList[$i][id]}\">{$paramCharList[$i][name]}</a> (";
		// alignment
		if($paramCharList[$i][alignment]) {
			$varOutput .= $paramCharList[$i][alignment] . " ";
		}
		// gender
		if($paramCharList[$i][gender]) {
			$varOutput .= $paramCharList[$i][gender] . " ";
		}
		// race
		if($paramCharList[$i][race]) {
			$varOutput .= $paramCharList[$i][race] . " ";
		}
		// class list
		if($paramCharList[$i]['class']) {
			$varOutput .= $paramCharList[$i]['class'] . " ";
		}
		$varOutput = substr($varOutput, 0, -1); // strip trailing space
		$varOutput .= ")<br>";
	}
	return $varOutput;
}

function buildLocList($paramLocList) // array of objects with id, name, optional cr, alignment, and region
{
	$varOutput = "";
	for($i = 0; $i < count($paramLocList); $i++) {
		// name
		$varOutput .= "<a href=\"/location/?id={$paramLocList[$i][id]}\">{$paramLocList[$i][name]}</a> (";
		// cr
		if($paramLocList[$i][cr]) {
			$varOutput .= "CR " . $paramLocList[$i][cr] . " ";
		}
		// alignment
		if($paramLocList[$i][alignment]) {
			$varOutput .= $paramLocList[$i][alignment] . " ";
		}
		// region
		if($paramLocList[$i][region]) {
			$varOutput .= $paramLocList[$i][region] . " ";
		}
		$varOutput = substr($varOutput, 0, -1); // strip trailing space
		$varOutput .= ")<br>";
	}
	return $varOutput;
}

function buildEncList($paramEncList) // array of objects with story_name, chapter_name, enc_name, optional cr, cr_min, chance, trigger_encounter_id, trigger_name, trigger_day, completed_day
{
	// encounter list should already be sorted by story name, chapter name, trigger_day for proper sorting here
	// fix encounter list so that triggers that refer to encounters not at this location still appear
	foreach($paramEncList as $varEncounter) {
		$varExists[$varEncounter['id']] = 1;
	}
	for($i = 0; $i < count($paramEncList); $i++) {
		if(!$varExists[$paramEncList[$i][trigger_encounter_id]]) {
			// the trigger encounter is not listed here, so set trigger to 0
			$paramEncList[$i][trigger_encounter_id] = '';
		}
	}

	// output
	$varOutput = "<table class=\"orgTable\">";
	$varOutput .= buildEncGroup($paramEncList, 0, 0);
	$varOutput .= "</table>&nbsp;";
	return $varOutput;
}

function buildEncGroup($paramEncList, $paramTriggerID, $paramDepth)
{
	foreach($paramEncList as $varEncounter) {
		if(!$varEncounter[name]) {
			continue;
		}
		if($varEncounter[trigger_encounter_id] != $paramTriggerID) {
			continue;
		}

		$varStory = $varChapter = $varDrawEnc = "";

		if($paramDepth) {
			for($i = 0; $i < $paramDepth; $i++) {
				$varDrawEnc .= " &nbsp; &nbsp; &nbsp;";
			}
			$varDrawEnc .= "\__";
		}
		else {
			if($varEncounter[story_name] != $tmpStory) {
				$tmpStory = $varStory = $varEncounter[story_name];
			}
			if($varEncounter[chapter_name] != $tmpChapter) {
				$tmpChapter = $varChapter = $varEncounter[chapter_name];
			}
		}
		$varDrawEnc .= "<a href=\"/encounter/?id={$varEncounter['id']}\">{$varEncounter[name]}</a>";
		$varDrawEnc .= " &nbsp; &nbsp; CR&nbsp;{$varEncounter[cr_min]}";
		if($varEncounter[cr]) {
			$varDrawEnc .= " / Combat&nbsp;CR&nbsp;{$varEncounter[cr]}";
		}
		if($varEncounter[is_active] == 'Yes') {
			$varDrawEnc = "<b style=\"color: #aa3129;\">" . $varDrawEnc . " ({$varEncounter[chance]}%)</b>";
		}
		else {
			if($varEncounter[completed_day]) {
				$varDrawEnc .= " (complete)";
			}
			else {
				if($varEncounter[trigger_name]) {
					$varDrawEnc .= " (starts";
					if($varEncounter[trigger_day]) {
						$varDrawEnc .= " {$varEncounter[trigger_day]} days";
					}
					$varDrawEnc .= " after {$varEncounter[trigger_name]})";
				}
				else {
					$varDrawEnc .= " (starts on day {$varEncounter[trigger_day]})";
				}
			}
		}
		$varOutput .= drawEncounter($varStory, $varChapter, $varDrawEnc);
		$varOutput .= buildEncGroup($paramEncList, $varEncounter['id'], $paramDepth + 1);
	}
	return $varOutput;
}

function drawEncounter ($paramStory, $paramChapter, $paramEncounter)
{
		$varOutput = "<tr><td class=\"storyColumn\"><b>";
		if($paramStory) {
			$varOutput .= "<a href=\"/encounter/?name={$paramStory}\">{$paramStory}</a>";
		}
		$varOutput .= "</b></td><td class=\"chapterColumn\">";
		if($paramChapter) {
			$varOutput .= "<a href=\"/encounter/?name={$paramChapter}\">{$paramChapter}</a>";
		}
		$varOutput .= "</td>";
		$varOutput .= "<td class=\"encounterColumn\">{$paramEncounter}</td>";
		$varOutput .= "</tr>";

		return $varOutput;
}

function drawHexMap ($paramX, $paramY, $paramRad, $paramLocation)
{
	$varContent = "<table class=\"hexTable\">\n";
	if($paramX % 2 === 1) {
		$varContent .= "<br><br>"; // equal out the offset staggering used to create the hex map
	}
	for($varRow = -1 * $paramRad; $varRow <= $paramRad; $varRow++) { // loop through rows (2 * radius + 1)
		$varContent .= "<tr>\n";
		for($varCol = -1 * $paramRad; $varCol <= $paramRad; $varCol++) { // loop through cells in a row (2 * radius + 1)
			// draw only hex parts within the defined radius
			if(abs($varCol) < ($varRow + $paramRad) * 2 + 1 + abs($paramX + $varCol + 1) % 2  and abs($varCol) < ($paramRad - $varRow) * 2 + 2 - abs($paramX + $varCol + 1) % 2) {
				$varContent .= drawHexPart($paramX + $varCol, $paramY + $varRow, // x-co and y-co of map part
			                           abs($paramX + $varCol + 1) % 2, // offset (main column is 1, adjacent columns are 0, etc.)
			                           $paramLocation[$paramX + $varCol][$paramY + $varRow], // hex information
			                           ($varCol + $paramRad)); // css element offset (otherwise you get gaps after each hex)
			}
			else {
				$varContent .= drawHexPart(); // no visible hex
			}
		}
		$varContent .= "</tr>\n";
	}
	$varContent .= "</table>\n";

	return $varContent;
}

function drawHexPart ($paramX, $paramY, $paramOffset, $paramLocation, $paramLeft)
{
	// prepare background colour based on terrain
	$varR = $varG = $varB = 9;
	switch($paramLocation[terrain]) {
		case "plains":              $varR += 0;  $varG += 0;  $varB -= 0;  break;
		case "gentle hills":        $varR += 1;  $varG -= 1;  $varB -= 1;  break;
		case "rugged hills":        $varR += 2;  $varG -= 2;  $varB -= 2;  break;
		case "alpine mountain":     $varR += 3;  $varG -= 3;  $varB -= 3;  break;
		case "rugged mountain":     $varR += 4;  $varG -= 4;  $varB -= 4;  break;
		case "forbidding mountain": $varR += 5;  $varG -= 5;  $varB -= 5;  break;
	}
	switch($paramLocation[growth]) {
		case "tundra":              $varR += 6;  $varG += 6;  $varB += 6;  break;
		case "dessert":             $varR += 6;  $varG += 4;  $varB += 2;  break;
		case "badlands":            $varR += 2;  $varG += 1;  $varB += 0;  break;
		case "broken lands":        $varR += 2;  $varG += 0;  $varB -= 0;  break;

		case "grasslands":          $varR -= 0;  $varG += 6;  $varB -= 0;  break;
		case "shrubland":           $varR -= 2;  $varG += 4;  $varB -= 2;  break;
		case "sparse forest":       $varR -= 4;  $varG += 2;  $varB -= 4;  break;
		case "dense forest":        $varR -= 6;  $varG += 0;  $varB -= 6;  break;
		case "burned forest":       $varR -= 2;  $varG += 0;  $varB -= 0;  break;

		case "farmland":            $varR += 0;  $varG += 9;  $varB += 1;  break;
		case "moor":                $varR -= 2;  $varG += 6;  $varB += 3;  break;
		case "swamp":               $varR -= 6;  $varG += 3;  $varB += 0;  break;
		case "sea":                 $varR -= 3;  $varG -= 3;  $varB += 15; break;
	}
	$varHexColour = dec2hex($varR) . dec2hex($varG) . dec2hex($varB);

	// output hexagon
	if($paramX or $paramX === 0) {
		$paramLeft *= -98;
		// draw left triangle
		$varContent .= "<td class=\"hexLeft hexOffset{$paramOffset}\" style=\"left: {$paramLeft}px; border-right-color: #{$varHexColour}; border-left-color: #{$varHexColour};\"></td>\n";
		// draw centre square
		$paramLeft -= 2;
		$varContent .= "<td class=\"hex hexOffset{$paramOffset}\" style=\"left: {$paramLeft}px; background-color: #{$varHexColour};\"></td>";
		// draw right triangle
		$paramLeft -= 2;
		$varContent .= "<td class=\"hexRight hexOffset{$paramOffset}\" style=\"left: {$paramLeft}px; border-right-color: #{$varHexColour}; border-left-color: #{$varHexColour};\">";
		// draw content
		$paramLeft -= 76;
		$varContent .= "<td class=\"hexContent hexOffset{$paramOffset}\" style=\"left: {$paramLeft}px;\">";
		$varContent .= "<a href=\"/location/?id={$paramX}x{$paramY}\"><div class=\"hexDiv\">";
		// draw map roads
		if($paramLocation[roads]) {
			$varContent .= "<img src=\"/img/map-road{$paramLocation[roads]}.gif\" height=\"100%\" style=\"position: relative; margin-bottom: -66px;\"><br>";
		}
		// draw map trails
		if($paramLocation[trails]) {
			$varContent .= "<img src=\"/img/map-trail{$paramLocation[trails]}.gif\" height=\"100%\" style=\"position: relative; margin-bottom: -66px;\"><br>";
		}
		// draw map rivers
		if($paramLocation[rivers]) {
			$varContent .= "<img src=\"/img/map-river{$paramLocation[rivers]}.gif\" height=\"100%\" style=\"position: relative; margin-bottom: -66px;\"><br>";
		}
		// draw map feature, write label
		if($paramLocation) {
			// add (1) image or (2) location name, if available
			if($paramLocation[image]) {
				$varContent .= "<img src=\"/img/{$paramLocation[image]}\" height=\"100%\" style=\"position: relative; margin-bottom: -66px;\"><br>";
			}
			else if($paramLocation[name]) {
				$varContent .= "{$paramLocation[name]}<br>";
			}
			else {
				$varContent .= "<img src=\"/img/map-road0.gif\" width=\"100%\" style=\"position: relative; margin-bottom: -66px;\"><br>";
			}
		}
		else {
			$varContent .= "<img src=\"/img/map-road0.gif\" width=\"100%\" style=\"position: relative; margin-bottom: -66px;\"><br>";
		}
		$varContent .= "{$paramX}X{$paramY}<br></div></a>";
		$varContent .= "</td>\n";
	}
	else {
		$varContent .= "<td></td><td></td><td></td><td></td>\n";
	}
	$varContent .= "</td>\n";

	return $varContent;
}

function dec2hex($paramDec)
{
	switch($paramDec) {
		case 10: return "A";
		case 11: return "B";
		case 12: return "C";
		case 13: return "D";
		case 14: return "E";
	}
	if($paramDec > 14) {
		return "F";
	}
	return $paramDec;
}

function ordinal_format($paramCardinal)
{
	switch(substr($paramCardinal, -1, 1)) {
		case "1": return $paramCardinal . "st";
		case "2": return $paramCardinal . "nd";
		case "3": return $paramCardinal . "rd";
		default: return $paramCardinal . "th";
	}
}

function sign_format($paramNumber)
{
	if($paramNumber < 0) {
		return $paramNumber;
	}
	else {
		return "+" . $paramNumber;
	}
}

?>
