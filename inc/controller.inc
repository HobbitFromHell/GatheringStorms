<?php
/**
 * Hobbit From Hell common controller functions
 *
 * Controls communication with DB server
 * @package Database
 * @author Myron Wyles
 */

/**
 * Set primary key id from get data
 */
if (isset($_GET[id])) {
	$pkid = sanitize($_GET[id]);
}

/**
 * Stores subsection data
 */
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

	/**
	 * Assign a list of options
	 *
	 * Used for any field with limited, hardcoded options.
	 * Reformats a string to an object with value and list so
	 * the builder can render as a SELECT.
	 * @param string (selected value)
	 * @param array (option list)
	 * return object (value and list together)
	 */
	public function setList($paramValue, $paramList)
	{
		$ret = array();

		$ret[value]  = $paramValue;
		$ret['list'] = $paramList;

		return $ret;
	}

	/**
	 * Assign a list of options
	 *
	 * Used for ENUM and SET data types with dynamic options.
	 * Reformats a string to an object with value and list so
	 * the builder can render as a SELECT.
	 * @param string (table name)
	 * @param string (column name)
	 * @param string (selected ENUM value)
	 *     or array (selected SET values)
	 * return object (value and list together)
	 */
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

		// identify data type
		if(is_array($paramValue)) {
			// selected value is an array:
			// look for a set, which can select multiples
			if(substr($varEnumSet[COLUMN_TYPE], 0, 3) != "set") { // invalid set data type
				return "error";
			}
			$varDataType = "set";
		}
		else {
			// selected value is a string:
			// look for an enum, which can select only one
			if(substr($varEnumSet[COLUMN_TYPE], 0, 4) != "enum") { // invalid enum data type
				return "error";
			}
			$varDataType = "enum";
		}

		// set current value
		if($paramValue) {
			$ret['value'] = $paramValue;
		}
		else {
			$ret['value'] = $varEnumSet[COLUMN_DEFAULT];
		}

		// set list of options
		// explode list from DB on comma, after stripping the ends
		$ret['list'] = explode(",", str_replace("'", "", substr($varEnumSet[COLUMN_TYPE], strlen($varDataType) + 1, -1)));
		// sort ascending
		asort($ret['list']);
		if($varDataType == "enum") {
			// ENUM data type gets a blank option at the beginning
			array_unshift($ret['list'], "");
		}

		return $ret;
	}
}

?>
