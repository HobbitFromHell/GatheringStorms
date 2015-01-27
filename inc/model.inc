<?php

// set primary key id from get data
if (isset($_GET[id])) {
	$pkid = sanitize($_GET[id]);
}

/**
 *	An extention of the Mysqli class 
 *
 *	@package Database
 *	@author Myron Wyles
 */
class DataConnector extends Mysqli
{
	/*
	 * singleton instance toggle
	 * @var boolean
	 */
	private static $instance = false;
	private static $res;

	private function __construct()
	{
		parent::__construct("localhost", "dm", "XYZE7ht39hJqw534", "hfh");
	}

	public static function selectQuery($query = null)
	{
		if(self::$instance === false) {
			self::$instance = new DataConnector;
		}
		if($query) {
			self::$res = self::$instance->query($query);
		}
		return self::$res->fetch_assoc();
	}

	public static function updateQuery($query = null)
	{
		if(self::$instance === false) {
			self::$instance = new DataConnector;
		}
		if($query) {
			self::$res = self::$instance->query($query);
		}
		return self::$instance->insert_id;
	}
}

class DataCollector
{
/*
	private $properties = array();

	public function __get($varKey)
	{
		if (array_key_exists($varKey, $this->properties)) {
			return $this->properties[$varKey];
		}
	}

	public function __set($varKey, $varValue)
	{
		$this->properties[$varKey] = $varValue;
	}
*/
	public function setList($string, $list_array)
	{
		$ret = array();
		$ret[value] = $string;
		$ret['list'] = $list_array;
		return $ret;
	}

	public function getEnum($paramTable, $paramColumn, $paramValue)
	{
		$ret = array();
		$varDataType = "";
		$varEnumSet = DataConnector::selectQuery("
			SELECT COLUMN_TYPE, COLUMN_DEFAULT, IS_NULLABLE
			  FROM INFORMATION_SCHEMA.COLUMNS
			 WHERE TABLE_NAME = '{$paramTable}'
			   AND COLUMN_NAME = '{$paramColumn}'
		");
		if(is_array($paramValue)) { // look for a set, which can select multiples
			if(substr($varEnumSet[COLUMN_TYPE], 0, 3) != "set") { // invalid set data type
				return "error";
			}
			$varDataType = "set";
		}
		else { // look for an enum, which can select only one
			if(substr($varEnumSet[COLUMN_TYPE], 0, 4) != "enum") { // invalid enum data type
				return "error";
			}
			$varDataType = "enum";
		}
		// set value
		if($paramValue) {
			$ret['value'] = $paramValue;
		}
		else {
			$ret['value'] = $varEnumSet[COLUMN_DEFAULT];
		}
		// set list options
		$ret['list'] = explode(",", str_replace("'", "", substr($varEnumSet[COLUMN_TYPE], strlen($varDataType) + 1, -1)));
		asort($ret['list']);
		if($varDataType == "enum") {
			array_unshift($ret['list'], "");
		}
		return $ret;
	}
}

function sanitize($paramValue)
{
	return str_replace("'", "''", stripslashes(htmlentities(strip_tags($paramValue))));
}

/**
 *	post_data - transforms post data into sql updates, and sends to the database
 *
 *	@author Myron Wyles
 *
 *	paramTable   table name
 *	paramKey     primary key of the table, usually "id"
 *                      - ignored when adding a new record, when identical to paramID, and when blank
 *                      - set to id number embedded in post parameters
 *	paramID      relational id: "id" in same table, "character_id", "location_id", etc. everywhere else (matches $pkid)
 *                      - always set to $postID
 *	paramDataArray   array of column names to update; may contain an array with translation for post name, column name
 */
function post_data($paramTable, $paramKey, $paramID, $paramDataArray)
{
// DEBUG
// foreach($_POST as $varKey => $varValue) echo "{$varKey} = {$varValue}<br>";

	// post data means an update or insert
	if (isset($_POST[id])) {
		$postID = sanitize($_POST[id]);
		$postArray = Array();
		foreach($_POST as $varKey => $varValue) {
			// find post parameters with tailing numeric portion, which is the primary key (negative value = insert new record)
			preg_match("/^([a-z_]+)([-\d]+)$/", $varKey, $varMatches);
			if(isset($varMatches[2])) { // tailing numeric portion found
				$postAddNewID = $varMatches[2]; // set primary key value
			}
			else {
				$varMatches[1] = $varKey;
				$postAddNewID = 0;
			}
			for($i = 0; $i < count($paramDataArray); $i++) {
				if(is_string($paramDataArray[$i])) {
					$varTmp = $paramDataArray[$i];
					$paramDataArray[$i] = array($varTmp, $varTmp);
				}
			}
			foreach($paramDataArray as $varField) {
// DEBUG
// echo("{$varMatches[1]} == {$varField[0]}?");
				if($varMatches[1] == $varField[0]) {
// DEBUG
// echo("<b> Yes! {$varField[0]} = {$varValue}</b> and count = ");
// echo(count($postArray[$postAddNewID])+1 . "/" . count($paramDataArray) . "<br>");
					$postArray[$postAddNewID][$varField[0]] = sanitize($varValue);
					if(count($postArray[$postAddNewID]) == count($paramDataArray)) { // the expected number of params
						// if the id is negative, add a new record
						if($postAddNewID > 0 or $postArray[$postAddNewID][$paramDataArray[0][0]]) {
							$varQuery = "INSERT INTO `{$paramTable}` (`{$paramID}`";
							if($postAddNewID > 0 && $paramKey && $paramID != $paramKey) {
								$varQuery .= ", `{$paramKey}`";
							}
							foreach($paramDataArray as $varField2) {
								$varQuery .= ", `{$varField2[1]}`";
							}
							$varQuery .= ") VALUES ('{$postID}'";
							if($postAddNewID > 0 && $paramKey && $paramID != $paramKey) {
								$varQuery .= ", {$postAddNewID}";
							}
							foreach($paramDataArray as $varField2) {
								$varQuery .= ", '{$postArray[$postAddNewID][$varField2[0]]}'";
							}
							$varQuery .= ") ON DUPLICATE KEY UPDATE ";
							foreach($paramDataArray as $varField2) {
								if($paramDataArray[0] != $varField2) {
									$varQuery .= ", ";
								}
								$varQuery .= "`{$varField2[1]}` = '{$postArray[$postAddNewID][$varField2[0]]}'";
							}
// DEBUG
// echo($varQuery);
							DataConnector::updateQuery($varQuery);
// echo("DONE");
						}
						// unset to avoid collisions during multiple inserts
						unset($postArray[$postAddNewID]);
					}
				}
			}
		}
		return true;
	}
	return false;
}

?>
