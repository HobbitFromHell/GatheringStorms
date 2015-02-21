<?php
/**
 * Hobbit From Hell common model functions
 *
 *
 * Controls communication with DB server
 * @package Database
 * @author Myron Wyles
 */

/**
 *	An extention of the Mysqli class
 *
 *	@package Database
 *	@author Myron Wyles
 */
class DataConnector extends Mysqli
{
	/*
	 * Singleton instance extant toggle
	 * @var boolean
	 */
	private static $instance = false;

	/*
	 * Singleton instance extant toggle
	 * @var Mysqli query object
	 */
	private static $res;

	/*
	 * Constructor
	 *
	 * Pass hardcoded DB connection details to parent
	 * constructor.
	 */
	private function __construct()
	{
		parent::__construct("localhost",
		                    "dm",
		                    "XYZE7ht39hJqw534",
		                    "hfh"
		                   );
	}

	/*
	 * handles communication of data with DB server
	 *
	 * @param string (optional, if absent returns next record)
	 * @param boolean (option, default true)
	 * @return array (if a record is available)
	 *      or string (error message or insert_id)
	 */
	public static function selectQuery($paramQuery = null, $paramReturnRecord = true)
	{
		if(self::$instance === false) {
			self::$instance = new DataConnector;
		}
		if($paramQuery) {
			self::$res = self::$instance->query($paramQuery);
		}
		// to do: on error, return error information
		if($paramReturnRecord) {
			return self::$res->fetch_assoc();
		}
		else {
			return self::$instance->insert_id;
		}
	}

	/*
	 * sets up INSERT request for selectQuery()
	 *
	 * @param string
	 * @return same as selectQuery()
	 */
	public static function updateQuery($paramQuery = null)
	{
		if($paramQuery) {
			return self::selectQuery($paramQuery, false);
		}
	}
}

/**
 * Sanitize data from user or database
 *
 * stripslashes removes escaped quotes. htmlentities
 * converts special characters to HTML entities. strip_tags
 * removes HTML and PHP tags. And finally quote characters
 * are replaced with two quote characters.
 * @param string
 * @return string
 */
function sanitize($paramValue)
{
	return str_replace("'", "''", stripslashes(htmlentities(strip_tags($paramValue))));
}

/**
 * Transforms POST data into SQL INSERT statements
 *
 * Compares a controller-defined checklist with available
 * POST data to build a series of INSERT ON DUP UPDATE
 * statements to save to the DB.
 * @param string (table name)
 * @param string (primary key name of table, usually "id";
 *                set to id number embedded in post params;
 *                ignored when adding a new record, same as
 *                $paramID, or blank)
 * @param string (relational ID to main table, such as
 *                "character_id"; matches $pkid; always set
 *                to $postID)
 * @param array (column names; may contain an array with
 *               translation for post name, column name
 * @return boolean (true = POST data, false = none)
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
			if(is_array($varValue)) {
				$varValue = implode(",", $varValue);
			}
			// find post parameters with tailing numeric portion,
			// which is the primary key (negative value = insert new record)
			preg_match("/^([a-z_]+)[0]?([-\d]+)$/", $varKey, $varMatches);
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
					// found matching field in POST data and checklist
					// DEBUG
					// echo("<b> Yes! {$varField[0]} = {$varValue}</b> and count = ");
					$postArray[$postAddNewID][$varField[0]] = sanitize($varValue);
					// DEBUG
					// echo(count($postArray[$postAddNewID]) . "/" . count($paramDataArray) . "<br>");
					if(count($postArray[$postAddNewID]) == count($paramDataArray)) {
						// the expected number of params are collected
						if($postAddNewID > 0 or ($postAddNewID < 0 and
						   isset($postArray[$postAddNewID][$paramDataArray[0][0]]))) {
							// update existing record or add new record
							// written as one INSERT ON DUP UPDATE statement
							// start with primay key name
							$varQuery = "INSERT INTO `{$paramTable}` (`{$paramID}`";
							if($postAddNewID > 0 && $paramKey && $paramID != $paramKey) {
								// if updating, and there is a relational key
								// different than the primay key: add name to query
								$varQuery .= ", `{$paramKey}`";
							}
							foreach($paramDataArray as $varField2) {
								// add each field name in checklist to query
								$varQuery .= ", `{$varField2[1]}`";
							}
							// write values to query,
							// starting with primary key value
							$varQuery .= ") VALUES ('{$postID}'";
							if($postAddNewID > 0 && $paramKey && $paramID != $paramKey) {
								// if updating, and there is a relational key
								// different than the primay key: add value to query
								$varQuery .= ", {$postAddNewID}";
							}
							foreach($paramDataArray as $varField2) {
								// add each field value in checklist to query
								$varQuery .= ", '{$postArray[$postAddNewID][$varField2[0]]}'";
							}
							$varQuery .= ") ON DUPLICATE KEY UPDATE ";
							// write update portion, which is the same
							// information as above, formatted differently
							foreach($paramDataArray as $varField2) {
								if($paramDataArray[0] != $varField2) {
									$varQuery .= ", ";
								}
								$varQuery .= "`{$varField2[1]}` = '{$postArray[$postAddNewID][$varField2[0]]}'";
							}
							// DEBUG
							// echo($varQuery);
							DataConnector::updateQuery($varQuery);
							// DEBUG
							// echo("<br>\n");
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
