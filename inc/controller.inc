<?php

include_once "pathfinder.php";
require_once "model.inc";

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
if (isset($_GET['id'])) {
	$pkid = sanitize($_GET['id']);
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
	public function setList($paramValue = "", $paramList = NULL)
	{
		$ret = array();

		if($paramList == NULL) {
			return FALSE;
		}

		// if $paramList is an array:
		if(is_array($paramList)) {
			// set array as option list
			$ret['list']  = $paramList;
		}
		else {
			return FALSE;
		}
		$ret['value'] = $paramValue;

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
	public function getEnum($paramTable = NULL, $paramColumn = NULL, $paramValue = "")
	{
		if($paramTable == NULL or $paramColumn == NULL) {
			return FALSE;
		}

		$ret = array();
		$varDataType = "";
		$varEnumSet = DataConnector::selectQuery("
			SELECT COLUMN_TYPE, COLUMN_DEFAULT, IS_NULLABLE
			  FROM INFORMATION_SCHEMA.COLUMNS
			 WHERE TABLE_NAME = '{$paramTable}'
			   AND COLUMN_NAME = '{$paramColumn}'
		");

		// identify data type
		// if selected value is an array:
		if(is_array($paramValue)) {
			// look for a set, which can select multiples
			if(substr($varEnumSet['COLUMN_TYPE'], 0, 3) != "set") { // invalid set data type
				return FALSE;
			}
			$varDataType = "set";
		}
		// otherwise, selected value is a string
		else {
			// look for an enum, which can select only one
			if(substr($varEnumSet['COLUMN_TYPE'], 0, 4) != "enum") { // invalid enum data type
				return FALSE;
			}
			$varDataType = "enum";
		}

		// set current value
		if($paramValue) {
			$ret['value'] = $paramValue;
		}
		else {
			$ret['value'] = $varEnumSet['COLUMN_DEFAULT'];
		}

		// set list of options
		// explode list from DB on comma, after stripping the ends
		$ret['list'] = explode(",", str_replace("'", "", substr($varEnumSet['COLUMN_TYPE'], strlen($varDataType) + 1, -1)));

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
