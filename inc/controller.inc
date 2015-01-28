<?php

class BuildOutput
{
	private $outputRead  = "";
	private $outputEdit  = "";
	private $sectionName = "";

	public function __construct($paramName = "")
	{
		$this->setSectionName($paramName);
	}

	public function setSectionName($paramName)
	{
		$this->sectionName = $paramName;
	}

	public function lcSectionName()
	{
		return strtolower($this->sectionName);
	}

	/**
	 *	@parameter $paramColumnName - id used to post data
	 *	@parameter $paramField      - is_string: input type=text (e.g. name)
	 *                              - is_array:  contains [value] means select (e.g. alignment)
	 *                                           contains [name] means many-to-one (e.g. race)
	 *                                           contains [0][name] means many-to-many (e.g. class)
	 *	@parameter $paramKeyRead    - label in read mode: "()" to parethesize, "0" means do not show
	 *	@parameter $paramKeyEdit    - label in edit mode: "0" means do not show
	 *	@parameter $paramFieldSize  - optional size in pt
	 *	@parameter $paramMetadata   - optional is_array: "DELETE" to include (-) button
	 *                                         "BREAK" to follow each series with <br>
	 *                                         other is_string to be a separator string
	 *                                         is_array: id used to post data
	 *                                                   read label (0 means do not show in read mode; () means parenthisize)
	 *                                                   edit label (0 means do not show in edit mode)
	 *                                                   size
	 *                                                   optional default value
	 *                                                   optional array(options, makes select instead of text)
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

	public function addRead($paramField, $paramKey = "")
	{
		if($paramKey !== 0) {
			if($paramKey == "()") {
				if($paramField and strpos($paramField, "><") === false) { // must contain visible text
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

	public function addEdit($paramField, $paramKey = "")
	{
		if($paramKey and $paramKey != "()") {
			$this->outputEdit .= "{$paramKey} ";
		}
		$this->outputEdit .= $paramField . " ";
	}

	public function br()
	{
		$this->addRead("<br>");
		$this->addEdit("<br>");
	}

	public function dump($paramScope = 1)
	{
//		$this->outputRead = "				<input type=\"button\" value=\"R\" onClick=\"reloadSection('{$this->lcSectionName()}')\">";
		$this->outputRead = "
			</div>
			{$this->outputRead}
		</div>
		";
		if($paramScope == 1) {
			$this->outputRead = "
				<img src=\"/img/edit.png\" onClick=\"swapSection('{$this->lcSectionName()}')\">
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
		<form id=\"{$this->lcSectionName()}Form\">
			<div id=\"{$this->lcSectionName()}ReadControl\" class=\"statBlockControl\" style=\"align:right\">
				<img src=\"/img/check.png\" onClick=\"saveSection('{$this->lcSectionName()}', serializeParams('{$this->lcSectionName()}'))\">
				<img src=\"/img/batsu.png\" onClick=\"swapSection('{$this->lcSectionName()}')\">
			</div>
			{$this->outputEdit}
		</form>
		</div>
		";
		return $this->outputRead . $this->outputEdit;
	}
}

?>
