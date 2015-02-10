<?php
/**
 * Hobbit From Hell common view functions
 *
 * Debugging function preecho.
 * Formatting functions convert raw data for display.
 * Display functions convert an object of data into an
 * on-screen display.
 * @package HFHView
 * @author Myron Wyles
 */

/**
 * Class to generate read-only and edit html
 *
 * Each instance contains the html for a subsection of the 
 * main page. Variables and formatting notes are passed to 
 * it and it generates the underlying code.
 */
class BuildOutput
{
	/*
	 * @var string
	 */
	private $outputRead = "";

	/*
	 * @var string
	 */
	private $outputEdit = "";

	/*
	 * @var string
	 */
	private $sectionName = "";

	/*
	 * The class constructor
	 *
	 * Calls to set the instance section name
	 * @param string (default to empty string)
	 */
	public function __construct($paramName = "")
	{
		$this->setSectionName($paramName);
	}

	/*
	 * Set the section name
	 *
	 * Run automatically on construct, or manually, this sets
	 * the sectionName attribute.
	 * @param string
	 */
	public function setSectionName($paramName)
	{
		$this->sectionName = $paramName;
	}

	/*
	 * Get the section name in lower case
	 *
	 * @return string
	 */
	public function lcSectionName()
	{
		return strtolower($this->sectionName);
	}

	/**
	 * Add to both read-only and edit sections
	 *
	 * This is where the smarts are, using the type of data 
	 * provided to decide what html form elements to produce 
	 * then calling on other functions to write the output.
	 * @param string (post data key; if blank, not sent)
	 * @param string (text INPUT for text/number fields)
	 *     or object (SELECT for ENUM (alignment)and SET
	 *                (qualities) with [value], many-to-one
	 *                (race) with [name], many-to-many (class)
	 *                with [#][name] for each relation)
	 * @param string (optional, read-only label, "()" or "0")
	 * @param string (optional, edit label or "0" to skip)
	 * @param string (optional, size in pxels or "textarea")
	 * @param object (optional, array of: "DELETE", "BREAK",
	 *                other string separator, array for many-
	 *                to-many (id, read label, edit label,
	 *                size, optional default value, optional 
	 *                list array which forces SELECT layout)
	 */
	public function add($paramColumnName, $paramField, $paramKeyRead = "", $paramKeyEdit = "", $paramFieldSize = null, $paramMetadata = null)
	{
		if($paramKeyRead > "" and $paramKeyEdit === "") {
			$paramKeyEdit = $paramKeyRead;
		}
		if(!is_array($paramField)) {
			if($paramColumnName and $paramKeyEdit === 0) {
			// hidden
				$this->addEdit("<input type=\"hidden\" id=\"{$paramColumnName}\" value=\"{$paramField}\">");
			}
			// text
			if ($paramKeyRead !== 0) {
				$tmpFormatField = $paramField;
				if($paramFieldSize == "textbox" or $paramFieldSize == "textarea") {
					$tmpFormatField = str_replace("\r", "", $tmpFormatField);
					$tmpFormatField = str_replace("\n", "<br>", $tmpFormatField);
					$tmpFormatField = str_replace("\"", "''", $tmpFormatField);
					$tmpFormatField .= "&nbsp;"; // force a space to give the text block a mininum size
				}
				$this->addRead("<span id=\"span$paramColumnName\">" . $tmpFormatField . "</span>", $paramKeyRead);
			}
			if ($paramKeyEdit !== 0) {
				// html (do not use as input value)
				if(strrpos($paramField, "<") !== false) {
					$this->addEdit($paramKeyEdit);
					return;
				}
				if($paramFieldSize == "textbox" or $paramFieldSize == "textarea") {
					$this->addEdit("<textarea id=\"{$paramColumnName}\" rows=\"5\" style=\"width:100%\">{$paramField}</textarea>", $paramKeyEdit);
				}
				else {
					$this->addEdit("<input type=\"text\" id=\"{$paramColumnName}\" value=\"{$paramField}\" style=\"width:{$paramFieldSize}pt\">", $paramKeyEdit);
				}
			}
		}
		else if(is_array($paramField)) {
			if(array_key_exists('value', $paramField)) {
				if(is_array($paramField[value])) {
					// multiple select
					$this->addRead(implode(", ", $paramField[value]), $paramKeyRead);
					if ($paramColumnName !== "") {
						$this->addEdit("<select multiple=\"multiple\" id=\"{$paramColumnName}\" style=\"width:{$paramFieldSize}pt\">", $paramKeyEdit);
						foreach ($paramField['list'] AS $list_item) {
							$this->addEdit("<option value=\"{$list_item}\"");
							foreach ($paramField[value] AS $list_selected) {
								if($list_item == $list_selected) {
									$this->addEdit(" selected=\"selected\"");
								}
							}
							$this->addEdit(">{$list_item}</option>");
						}
						$this->addEdit("</select>");
					}
				}
				else {
					// single select
					$this->addRead($paramField[value], $paramKeyRead);
					if ($paramColumnName !== "") {
						$this->addEdit("<select id=\"{$paramColumnName}\" style=\"width:{$paramFieldSize}pt\">", $paramKeyEdit);
						foreach ($paramField['list'] AS $list_item) {
							$this->addEdit("<option value=\"{$list_item}\"");
							if($list_item == $paramField[value]) {
								$this->addEdit(" selected=\"selected\"");
							}
							$this->addEdit(">{$list_item}</option>");
						}
						$this->addEdit("</select>");
					}
				}
			}
			if(array_key_exists('name', $paramField)) {
				// many-to-one DB relationship
				// show description, if it exists
				if($paramField[description]) {
					$this->addRead("<span title=\"" . $paramField[description] . "\">");
				}
				$this->addRead($paramField[name], $paramKeyRead);
				if($paramField[description]) {
					$this->addRead("</span>");
				}
				if ($paramColumnName !== "") {
					$this->addEdit("<select id=\"$paramColumnName\" style=\"width:{$paramFieldSize}pt\">", $paramKeyEdit);
					foreach ($paramField['list'] AS $list_item) {
						$this->addEdit("<option value=\"{$list_item[id]}\"");
						if($list_item[name] == $paramField[name]) {
							$this->addEdit(" selected=\"selected\"");
						}
						$this->addEdit(">{$list_item[name]}</option>");
					}
					$this->addEdit("</select>");
				}
			}
//			if(is_string($paramField[0])) {
//			// select multiple
//				$this->addEdit("MULTI TO DO");
//			}
			if(array_key_exists($i = 0, $paramField)) {
//			if(is_array($paramField[$i = 0])) {
//			if(is_array($paramMetadata)) {
			// many-to-many DB relationship
				if($paramKeyRead !== 0) {
					$this->addRead("", $paramKeyRead);
				}
				$this->addEdit("", $paramKeyEdit);
				$paramField[] = array(id=>0); // append a field with id 0 for "add new" function
				$seperator = "";
				$delete = $break = false;
				while(array_key_exists($i, $paramField)) {
					if($paramKeyRead !== 0) {
						if($i > 0 and $paramField[$i][id] != 0) {
							$this->addRead($seperator);
						}
						if($paramField[$i][description]) {
							$this->addRead("<span title=\"" . $paramField[$i][description] . "\">");
						}
						$this->addRead($paramField[$i][name]);
						if($paramField[$i][description]) {
							$this->addRead("</span>");
						}
					}
					$this->addEdit("<span id=\"div{$paramColumnName}{$paramField[$i][id]}\" class=\"manyToMany\"");
					if($paramField[$i][id] < 1) {
						$this->addEdit(" style=\"display:none\"");
					}
					$this->addEdit(">");
					$this->addEdit("<select id=\"{$paramColumnName}{$paramField[$i][id]}\" style=\"width:{$paramFieldSize}pt\">");
					if($paramField[$i][id] < 1) {
						$this->addEdit("<option value=\"\" selected=\"selected\"></option>");
					}
					else {
						$this->addEdit("<option value=\"{$paramField[$i][name]}\" selected=\"selected\">{$paramField[$i][name]}</option>");
					}
					foreach($paramField['list'] as $list_item) {
						$this->addEdit("<option value=\"" . $list_item[id] . "\"");
						if($list_item[name] == $paramField[$i][name]) {
							$this->addEdit(" selected=\"selected\"");
						}
						$this->addEdit(">" . $list_item[name] . "</option>");
					}
					$this->addEdit("</select>");
					if(is_array($paramMetadata)) {
						foreach($paramMetadata as $item) {
							if(is_array($item)) { // array elements are for display
								// set read label
								if($paramKeyRead === 0) {
									$temp = 0;
								}
								else {
									$temp = $item[1];
								}
								// set default value
								if($item[4]) {
									if($item[5]) {
										$temp2 = "";
										$temp2[value] = $item[4];
										$temp2['list'] = $item[5];
									}
									else {
										$temp2 = $item[4];
									}
								}
								else {
									if($item[5]) {
										$temp2 = "";
										$temp2[value] = $paramField[$i][$item[0]];
										$temp2['list'] = $item[5];
									}
									else {
										$temp2 = $paramField[$i][$item[0]];
									}
								}
								// add each item in the many-to-many relationship
								$this->add($item[0].$paramField[$i][id], $temp2, $temp, $item[2], $item[3]);
							}
							else { // non-array elements are commands; if unrecognized, use as the seperator
								if($item == "DELETE") {
									$delete = true;
								}
								else {
									if($item == "BREAK") {
										$break = true;
									}
									else {
										$seperator = $item;
									}
								}
							}
						}
					}
					if($delete) {
						$this->addEdit("<input type=\"button\" value=\"-\" onClick=\"this.parentNode.style.display='none'; for(var x = 0; x < this.parentNode.childNodes.length; x++) if(this.parentNode.childNodes[x].id && this.parentNode.childNodes[x].id.substr(0, 10) == 'is_deleted') this.parentNode.childNodes[x].value = 'Yes';\">");
					}
					$this->addEdit("&nbsp;</span>");
					if($break and $paramField[$i][id] != 0) {
						$this->addEdit("<br>");
					}
					$i++;
				}
				// add new button
				$this->addEdit("<span id=\"div{$paramColumnName}addnew\"><input type=\"button\" id=\"addnew{$paramColumnName}\" value=\"+\" onClick=\"copyFromTemplate('{$paramColumnName}')\"><span id=\"spacer\"> </span></span>\n");
			}
		}
	}

	/**
	 * Add to only the read-only section
	 *
	 * Prepare for html output to the read-only section some 
	 * text or html, optionally with a label, parenthesis, 
	 * or not displayed at all.
	 * @param string (normal text to output)
	 * @param string/number (either 0 to not display, "()" to surround text with parenthesis, or a label)
	 */
	public function addRead($paramField, $paramKey = "")
	{
		if($paramKey !== 0) {
			if($paramKey == "()") {
				// must contain visible text, not just an html <tag></tag>
				if($paramField and strpos($paramField, "><") === false) {
					$this->outputRead .= "(" . $paramField . ") ";
				}
			}
			else  {
				if($paramKey) {
					$this->outputRead .= "<b class=\"statBlockLabel\">{$paramKey}</b> ";
				}
				$this->outputRead .= $paramField . " ";
			}
		}
	}

	/**
	 * Add to only the edit section
	 *
	 * Prepare for html output to the edit section some html 
	 * form elements, optionally with a label (ignore 
	 * parenthesis).
	 * @param string (normal text to output)
	 * @param string (either a label, or "()" which is ignored)
	 */
	public function addEdit($paramField, $paramKey = "")
	{
		if($paramKey and $paramKey != "()") {
			$this->outputEdit .= "{$paramKey} ";
		}
		$this->outputEdit .= $paramField . " ";
	}

	/**
	 * Add a line break to both read-only and edit sections
	 */
	public function br()
	{
		$this->addRead("<br>");
		$this->addEdit("<br>");
	}

	/**
	 * Produce complete output for rendering
	 *
	 * Adds editing buttons to the read-only and edit html 
	 * sections, and wraps them with div and form tags.
	 * @param boolean (true = allow edit mode)
	 * @return string (html for page render)
	 */
	public function dump($paramScope = 1)
	{
		// reload subsection button removed
		// $this->outputRead = "				<input type=\"button\" value=\"R\" onClick=\"reloadSection('{$this->lcSectionName()}')\">";
		$this->outputRead = "
			</div>
			{$this->outputRead}
		</div>
		";
		if($paramScope == 1) {
			$this->outputRead = "
				<img src=\"/img/edit.png\" onClick=\"editSection('{$this->lcSectionName()}')\">
			{$this->outputRead}
			";
		}
		$this->outputRead = "
		<div id=\"{$this->lcSectionName()}Read\" class=\"statBlockRead\" style=\"display:block\">
			<div id=\"{$this->lcSectionName()}ReadControl\" class=\"statBlockControl\" style=\"align:right\">
			{$this->outputRead}
		";
		if($paramScope == 0) {
			return $this->outputRead . "<div id=\"{$this->lcSectionName()}Edit\" class=\"statBlockEdit\" style=\"display:none\"></div>";
		}
		$this->outputEdit = "
		<div id=\"{$this->lcSectionName()}Edit\" class=\"statBlockEdit\" style=\"display:none\">
		<form id=\"{$this->lcSectionName()}Form\" enctype=\"multipart/form-data\">
			<div id=\"{$this->lcSectionName()}ReadControl\" class=\"statBlockControl\" style=\"align:right\">
				<img src=\"/img/check.png\" onClick=\"saveSection('{$this->lcSectionName()}', serializeParams('{$this->lcSectionName()}'))\">
				<img src=\"/img/batsu.png\" onClick=\"abortSection('{$this->lcSectionName()}')\">
			</div>
			{$this->outputEdit}
		</form>
		</div>
		";
		return $this->outputRead . $this->outputEdit;
	}
}

/**
 * Prints formatted variable content
 *
 * Outputs to screen with print_r the paramter contents,
 * typically an array or object, surrounded by html
 * formatting.
 * @param any, typically an array or object
 * @return nul
 */
function preecho($string)
{
	echo "<br><pre><br>";
	print_r($string);
	echo "</pre><br>";
}

/**
 * Display function makes CR human-readable
 *
 * Numbers below 1 are converted to fractions, as are used 
 * in Pathfinder.
 * @param number
 * @return string
 */
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

/**
 * Convert CR value to XP value
 *
 * @param number
 * @return number
 */
function cr2xp($paramCR)
{
	$varDoubler = 1;
	while ($paramCR > 2) {
		$paramCR -= 2;
		$varDoubler *= 2;
	}
	switch($paramCR) {
		case -2: return 100;
		case -1: return 135;
		case 0: return 200;
		case 1: return 400 * $varDoubler;
		case 2: return 600 * $varDoubler;
	}
}

/**
 * Convert XP value to CR value
 *
 * @param number
 * @return number
 */
function xp2cr($paramXP)
{
	$varCR = 0;
	if($paramXP < 135) {
		return -2;
	}
	if($paramXP < 200) {
		return -1;
	}
	while($paramXP >= 800) {
		$varCR += 2;
		$paramXP /= 2;
	}
	if($paramXP >= 600) {
		return $varCR + 2;
	}
	if($paramXP >= 400) {
		return $varCR + 1;
	}
	return $varCR;
}

/**
 * Convert CR value to GP value
 *
 * There is no math involved here, so this conversion is a 
 * simple hard-coded switch.
 * @param number
 * @return number
 */
function cr2gp($paramCR)
{
	switch($paramCR) {
		case -2: return 190;
		case -1: return 260;
		case 0: return 390;
		case 1: return 780;
		case 2: return 1650;
		case 3: return 2400;
		case 4: return 3450;
		case 5: return 4650;
		case 6: return 6000;
		case 7: return 7800;
		case 8: return 10050;
		case 9: return 12750;
		case 10: return 16350;
		case 11: return 21000;
		case 12: return 27000;
		case 13: return 34800;
		case 14: return 45000;
		case 15: return 58500;
		case 16: return 75000;
		case 17: return 96000;
		case 18: return 123000;
		case 19: return 159000;
	}
	return 0;
}

/**
 * Renders an organization listing directly to screen
 *
 * Writes directly to the document and passes back the 
 * accumulated XP to calculate the total org XP and GP.
 * @param number (id for the organization)
 * @param number (id for the member currently being done)
 * @param object (organization data)
 * @return number (accumulated XP for each member so far)
 */
function buildOrganization($paramOrganizationID, $paramMember, $paramOrganizationList)
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
	$varTotalXP += cr2xp($paramOrganizationList[organization][$paramOrganizationID][members][$paramMember][cr]);
	echo("CR {$var_cr}");
	if($pkid != $paramMember) {
		echo("</a>");
	}
	echo("</td></tr><tr class=\"orgTR\"><td class=\"orgTD\"><br>");

	// search for followers
	for($i = 0; $i < count($paramOrganizationList[organization][$paramOrganizationID][master][$paramMember]); $i++) {
		echo("<td class=\"orgTD\">");
		$varTotalXP += buildOrganization($paramOrganizationID, $paramOrganizationList[organization][$paramOrganizationID][master][$paramMember][$i], $paramOrganizationList);
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

	return $varTotalXP;
}

/**
 * Produces a HTML-formatted list of characters
 *
 * Following Pathfinder format, the character "name" (also a
 * link to the detail page requiring "id") is first, 
 * followed optionally by: "alignment", "gender", "race", 
 * and "class", which is a GROUP_CONCAT of class and level.)
 * @param object (list of character data pulled from DB)
 * @return string
 */
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

/**
 * Produces a HTML-formatted list of locations
 *
 * Each member of the list is changed into a HTML-formatted 
 * list giving "name" (also a link to the detail page which 
 * requires "id") followed optionally by "cr", "alignment", 
 * and "region" name - don't use id, it will be senseless.)
 * @param object (list of locations data pulled from DB)
 * @return string
 */
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

/**
 * Reformats encounter list and passes on to renderer
 *
 * The list should already be sorted by story name,
 * chapter name, then trigger_day.
 * @param object (list of encounter data pulled from DB)
 * @return string
 */
function buildEncList($paramEncList)
{
	// build reference list of available id
	foreach($paramEncList as $varEncounter) {
		$varExists[$varEncounter['id']] = 1;
	}
	for($i = 0; $i < count($paramEncList); $i++) {
		if(!$varExists[$paramEncList[$i][trigger_encounter_id]]) {
			// if the trigger encounter is not listed here:
			// set trigger to blank (will appear as stand-alone)
			$paramEncList[$i][trigger_encounter_id] = '';
		}
	}

	// produce output
	$ret  = "<table class=\"orgTable\">";
	$ret .= buildEncGroup($paramEncList, 0, 0);
	$ret .= "</table>&nbsp;";

	return $ret;
}

/**
 * Produces a HTML-formatted list of encounters
 *
 * Each member of the list is changed into HTML-formatted 
 * "story_name", "chapter_name", and "enc_name", followed
 * optionally by "cr", "cr_min", "chance", "trigger_name", 
 * "trigger_day" and "completed_day", arranged by 
 * "trigger_encounter_id".
 * @param object (list of encounter data pulled from DB)
 * @param string
 * @param string
 * @return string
 */
function buildEncGroup($paramEncList, $paramTriggerID, $paramDepth)
{
	foreach($paramEncList as $varEncounter) {
		// find encounters with the specified trigger id
		if(!$varEncounter[name]) {
			continue;
		}
		if($varEncounter[trigger_encounter_id] != $paramTriggerID) {
			continue;
		}

		$varStory = $varChapter = $varDrawEnc = "";

		// indent number of steps indicated by $paramDepth
		if($paramDepth) {
			for($i = 0; $i < $paramDepth; $i++) {
				$varDrawEnc .= " &nbsp; &nbsp; &nbsp;";
			}
			$varDrawEnc .= "\__";
		}
		else {
			// if no indent: set story and chapter name
			if($varEncounter[story_name] != $tmpStory) {
				$tmpStory = $varStory = $varEncounter[story_name];
			}
			if($varEncounter[chapter_name] != $tmpChapter) {
				$tmpChapter = $varChapter = $varEncounter[chapter_name];
			}
		}

		// render encounter name with link and following optional data
		$varDrawEnc .= "<a href=\"/encounter/?id={$varEncounter['id']}\">{$varEncounter[name]}</a>";
		$varDrawEnc .= " &nbsp; &nbsp; CR&nbsp;{$varEncounter[cr_min]}";
		if($varEncounter[cr]) {
			$varDrawEnc .= " / Combat&nbsp;CR&nbsp;{$varEncounter[cr]}";
		}
		if($varEncounter[is_active] == 'Yes') {
			// if active: show chance
			$varDrawEnc = "<b style=\"color: #aa3129;\">" . $varDrawEnc . " ({$varEncounter[chance]}%)</b>";
		}
		else {
			// if inactive: show whether complete, when it will
			// start, or what encounter will trigger it
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

		// send story name, chapter name, and encounter HTML for final render
		$ret .= drawEncounter($varStory, $varChapter, $varDrawEnc);

		// recursive call to see if any encounters fall under this one
		$ret .= buildEncGroup($paramEncList, $varEncounter['id'], $paramDepth + 1);
	}
	return $ret;
}

/**
 * Produces a table row for an encounter
 * @param string
 * @param string
 * @param string
 * @return string
 */
function drawEncounter ($paramStory, $paramChapter, $paramEncounter)
{
		$ret = "<tr><td class=\"storyColumn\"><b>";
		if($paramStory) {
			// if story name is specified: render it with a link
			$ret .= "<a href=\"/encounter/?name={$paramStory}\">{$paramStory}</a>";
		}
		$ret .= "</b></td><td class=\"chapterColumn\">";
		if($paramChapter) {
			// if chapter name is specified: render it with a link
			$ret .= "<a href=\"/encounter/?name={$paramChapter}\">{$paramChapter}</a>";
		}
		$ret .= "</td>";
		$ret .= "<td class=\"encounterColumn\">{$paramEncounter}</td>";
		$ret .= "</tr>";

		return $ret;
}

/**
 * Produces a HTML-formatted hex map
 *
 * Start off with the framework of the map, a simple table,
 * and call another function to draw the hex
 * @param string
 * @param string
 * @param string
 * @param array of arrays of location objects ([x][y][field])
 * @return string
 */
function drawHexMap ($paramX, $paramY, $paramRad, $paramLocation)
{
	$ret = "<table class=\"hexTable\">\n";

	// equal out the offset staggering used to create the hex map
	if($paramX % 2 != 0) {
		$ret .= "<br><br>";
	}

	// mark the centre of the hex map for highlighting
	$paramLocation[$paramX][$paramY][centre] = 1;

	// loop through rows (2 * radius + 1)
	for($varRow = -1 * $paramRad; $varRow <= $paramRad; $varRow++) {
		$ret .= "<tr>\n";
		// loop through cells in a row (2 * radius + 1)
		for($varCol = -1 * $paramRad; $varCol <= $paramRad; $varCol++) {
			// draw only hex parts within the defined radius
			if(abs($varCol) < ($varRow + $paramRad) * 2 + 1 + abs($paramX + $varCol + 1) % 2 and
			   abs($varCol) < ($paramRad - $varRow) * 2 + 2 - abs($paramX + $varCol + 1) % 2) {
				$ret .= drawHexPart($paramX + $varCol, $paramY + $varRow,
			                      abs($paramX + $varCol + 1) % 2,
			                      $paramLocation[$paramX + $varCol][$paramY + $varRow],
			                      ($varCol + $paramRad));
			}
			else {
				$ret .= drawHexPart(); // no visible hex
			}
		}
		$ret .= "</tr>\n";
	}
	$ret .= "</table>\n";

	return $ret;
}

/**
 * Produces a single hex for use in the hex map
 *
 * Arranges backgound colour based on terrain and growth.
 * Adds multiple layers of images and text.
 * @param string
 * @param string
 * @param string (hex offset, to push some cells 50% down)
 * @param object (hex information)
 * @param string (css element offset, to mitigate gaps after each hex)
 * @return string
 */
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

	// draw hex
	if($paramX or $paramX === 0) {
		$paramLeft *= -98;
		// draw left triangle
		$ret .= "<td class=\"hexLeft hexOffset{$paramOffset}\" style=\"left: {$paramLeft}px; border-right-color: #{$varHexColour}; border-left-color: #{$varHexColour};\"></td>\n";
		// draw centre square
		$paramLeft -= 2;
		$ret .= "<td class=\"hex hexOffset{$paramOffset}\" style=\"left: {$paramLeft}px; background-color: #{$varHexColour};\"></td>";
		// draw right triangle
		$paramLeft -= 2;
		$ret .= "<td class=\"hexRight hexOffset{$paramOffset}\" style=\"left: {$paramLeft}px; border-right-color: #{$varHexColour}; border-left-color: #{$varHexColour};\">";

		// draw content
		$paramLeft -= 76;
		$ret .= "<td class=\"hexContent hexOffset{$paramOffset}\" style=\"left: {$paramLeft}px;\">";
		$ret .= "<a href=\"/location/?id={$paramX}x{$paramY}\"><div class=\"hexDiv\">";

		// draw centre highlight
		if($paramLocation[centre]) {
			$ret .= "<img src=\"/img/map-highlight.gif\" height=\"100%\" style=\"position: relative; margin-bottom: -66px;\"><br>";
		}

		// draw map roads
		if($paramLocation[roads]) {
			$ret .= "<img src=\"/img/map-road{$paramLocation[roads]}.gif\" height=\"100%\" style=\"position: relative; margin-bottom: -66px;\"><br>";
		}

		// draw map trails
		if($paramLocation[trails]) {
			$ret .= "<img src=\"/img/map-trail{$paramLocation[trails]}.gif\" height=\"100%\" style=\"position: relative; margin-bottom: -66px;\"><br>";
		}

		// draw map rivers
		if($paramLocation[rivers]) {
			$ret .= "<img src=\"/img/map-river{$paramLocation[rivers]}.gif\" height=\"100%\" style=\"position: relative; margin-bottom: -66px;\"><br>";
		}

		// draw map feature, write label
		if($paramLocation) {
			// map data for this hex exists
			if($paramLocation[image]) {
				// a terrain feature image is available: draw it
				$ret .= "<img src=\"/img/{$paramLocation[image]}\" height=\"100%\" style=\"position: relative; margin-bottom: -66px;\"><br>";
			}
			else if($paramLocation[name]) {
				// a location name is available: write it
				$ret .= "{$paramLocation[name]}<br>";
			}
			else {
				// neither an image nor a name is available: draw a transparent image
				$ret .= "<img src=\"/img/map-road0.gif\" width=\"100%\" style=\"position: relative; margin-bottom: -66px;\"><br>";
			}
		}
		else {
			// there is no map data for this hex
			$ret .= "<img src=\"/img/map-road0.gif\" width=\"100%\" style=\"position: relative; margin-bottom: -66px;\"><br>";
		}
		// in all cases, write the x y coordniates
		$ret .= "{$paramX}X{$paramY}<br></div></a>";
		$ret .= "</td>\n";
	}
	else {
		// if $paramX is null, this is not a visible area on the circular map
		$ret .= "<td></td><td></td><td></td><td></td>\n";
	}
	$ret .= "</td>\n";

	return $ret;
}

/**
 * Convert from decimal number to hexadecial number
 * @param string
 * @return string
 */
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

/**
 * Convert from cardinal number to ordinal number
 * @param string
 * @return string
 */
function ordinal_format($paramCardinal)
{
	switch(substr($paramCardinal, -1, 1)) {
		case "1": return $paramCardinal . "st";
		case "2": return $paramCardinal . "nd";
		case "3": return $paramCardinal . "rd";
		default: return $paramCardinal . "th";
	}
}

/**
 * Convert from number to number with forced sign
 * @param string
 * @return string
 */
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
