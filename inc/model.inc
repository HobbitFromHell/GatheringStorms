<?php

include "databaseCredentials.inc";

// exception handler
function redirect_on_error($e) {
	ob_end_clean();
	if(is_a($e, "AuthException")) {
		header("Location: /login/php");
	}
	else if(is_a($e, "DataException")) {
		header("Location: /data/php");
	}
	else {
		include("error.html");
	}
}
// set exception handler
set_exception_handler("redirect_on_error");


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
	private static $instance = FALSE;

	/*
	 * Singleton instance
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
		switch($_SERVER['HTTP_HOST']) {
			case "hfh.myronwyles.com":
				$connectorCred = new StagDBCredentials;
				break;
			case "members.shaw.ca":
				$connectorCred = new ProdDBCredentials;
				break;
			default:
				$connectorCred = new DevDBCredentials;
		}

		parent::__construct($connectorCred->name,
		                    $connectorCred->user,
		                    $connectorCred->pass,
		                    $connectorCred->schema
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
	public static function selectQuery($paramQuery = NULL, $paramReturnRecord = TRUE)
	{
		// if singleton not yet instantiated:
		if(self::$instance === FALSE) {
			try {
				self::$instance = new DataConnector;
			}
			catch (DataException $e) {
				throw $e;
			}
		}

		// if query parameter was passed: set query
		if($paramQuery) {
			self::$res = self::$instance->query($paramQuery);
		}

		// if return data requested (i.e. select statement result):
		if($paramReturnRecord) {
			return self::$res->fetch_assoc();
		}
		// otherwise: return insert id
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
	public static function updateQuery($paramQuery = NULL)
	{
		if($paramQuery) {
			return self::selectQuery($paramQuery, FALSE);
		}
	}
}

/**
 * Sanitize data from user or database
 *
 * htmlentities converts special characters to HTML entities
 * strip_tags removes HTML and PHP tags. And finally quote
 * characters are replaced with two quote characters.
 * @param string
 * @return string
 */
function sanitize($paramValue)
{
	$varNewValue = $paramValue;

	$UNSAFE_JS[] = "onabort";
	$UNSAFE_JS[] = "onchange";
	$UNSAFE_JS[] = "onclick";
	$UNSAFE_JS[] = "onerror";
	$UNSAFE_JS[] = "onfocus";
	$UNSAFE_JS[] = "onmouseout";
	$UNSAFE_JS[] = "onmouseover";
	$UNSAFE_JS[] = "onload";
	$UNSAFE_JS[] = "onreset";
	$UNSAFE_JS[] = "onselect";
	$UNSAFE_JS[] = "onsubmit";
	$UNSAFE_JS[] = "onunload";

	foreach($UNSAFE_JS as $rule) {
		$varNewValue = str_ireplace($rule, "", $varNewValue);
	}

	$ret = str_replace("'", "''", htmlentities(strip_tags($varNewValue)));
	return $ret;
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

	// if post data was sent: perform an update or insert
	if (isset($_POST['id'])) {
		// id should always appear
		$postID = sanitize($_POST['id']);
		// array for gathering post data for similar record
		$postArray = Array();

		// for each post parameter
		foreach($_POST as $varKey => $varValue) {

			// if post parameter is an array: convert to string
			if(is_array($varValue)) {
				$varValue = implode(",", $varValue);
			}

			// find post parameters with tailing numeric portion,
			// which is the primary key for the table
			// (negative value = insert new record; zero = ignore)
			preg_match("/^([a-z_]+)[0]?([-\d]+)$/", $varKey, $varMatches);

			// if tailing numeric portion found: set primary key value
			if(isset($varMatches[2])) {
				$postAddNewID = $varMatches[2];
			}
			// otherwise: mark primary key unnecessary
			else {
				$varMatches[1] = $varKey;
				$postAddNewID = 'X';
			}

			// process variable checklist parameter
			for($i = 0; $i < count($paramDataArray); $i++) {
				// if check is not an array:
				if(is_string($paramDataArray[$i])) {
					// convert to array so everything is the same
					$varTmp = $paramDataArray[$i];
					$paramDataArray[$i] = array($varTmp, $varTmp);
				}
			}

			// for each item in checklist parameter
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
						if($postAddNewID > 0 or $postAddNewID == 'X' or ($postAddNewID < 0 and
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
		return TRUE;
	}
	return FALSE;
}

?>
