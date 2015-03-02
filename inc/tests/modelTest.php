<?php

class DataConnectorTestCase extends PHPunit_Framework_Testcase
{
	function __construct($param = NULL) {
		parent::__construct($param);
	}

	/*
	 * Test the selectQuery function
	 */
	public function testSelectQuery()
	{
		$testDataConnector = DataConnector::selectQuery("
			 SELECT `id`
			   FROM t_characters
			  WHERE id < 2
		");

		// return data
		$this->assertTrue(isset($testDataConnector));
		$this->assertTrue(is_array($testDataConnector));
		$this->assertTrue(count($testDataConnector) == 1);
		$this->assertTrue(isset($testDataConnector['id']));
		$this->assertTrue($testDataConnector['id'] == 1);
	}

	/*
	 * Test the sanitize function
	 */
	public function testSanitize()
	{
		// strip js function names
		$this->assertFalse(stripos(sanitize("strip JS code like onclick"), "onclick"), "Unsafe JS still slips through.");
		$this->assertFalse(stripos(sanitize("onunload, too"), "onunload"), "Unsafe JS still slips through.");

		// strip tags
		$this->assertFalse(stripos(sanitize("<span>hello!</span>"), "<"), "Tags still slip through.");

		// html entities
		$this->assertFalse(stripos(sanitize("Schön"), "ö"), "HTML entities not replacing special characters.");

		// apostrophy
		$this->assertFalse(stripos(sanitize("What's New?"), "t's"), "Apostrophies are not being doubled.");
	}}

?>
