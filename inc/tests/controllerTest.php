<?php

class DataCollectorTestCase extends PHPunit_Framework_Testcase
{
	function __construct($param = NULL) {
		parent::__construct($param);
	}

	/*
	 * Test the setList function
	 */
	public function testSetList()
	{
		$testDataCollector = new DataCollector();

		$testArray = Array("1", "2", "3");
		$x = $testDataCollector->setList("1", $testArray);

		// missing required parameter
		$this->assertFalse(FALSE, $testDataCollector->setList());
		$this->assertFalse(FALSE, $testDataCollector->setList("1"));

		// legal call return array
		$this->assertTrue(is_array($x));

		// returned object structure: key 'value' exists
		$this->assertTrue(isset($x['value']));
		// returned object structure: 'value' equals param #1
		$this->assertSame($x['value'], "1");

		// returned object structure: key 'list' exists
		$this->assertTrue(isset($x['list']));
		// returned object structure: 'list' equals param #2
		$this->assertSame($x['list'], $testArray);
		// returned object contents match parameter data
		$this->assertSame($x['list'][0], "1");
		$this->assertSame($x['list'][1], "2");
		$this->assertSame($x['list'][2], "3");
		$this->assertFalse(FALSE, isset($x['list'][3]));
	}

	/*
	 * Test the getEnum function
	 */
	public function testGetEnum()
	{
		$testDataCollector = new DataCollector();

		$x = $testDataCollector->getEnum("t_locations", "government", "Autocracy");
		$y = $testDataCollector->getEnum("t_locations", "qualities", array("Academic"));

		// missing required parameter
		$this->assertFalse(FALSE, $testDataCollector->getEnum());
		$this->assertFalse(FALSE, $testDataCollector->getEnum("t_locations"));
		$this->assertFalse(FALSE, $testDataCollector->getEnum("t_locations", "qualities"));

		// wrong data type (string for set, array for enum)
		$this->assertFalse(FALSE, is_array($testDataCollector->getEnum("t_locations", "government", Array("Autocracy"))));
		$this->assertFalse(FALSE, is_array($testDataCollector->getEnum("t_locations", "qualities", "Academic")));

		// legal call, blank value
		$this->assertTrue(TRUE, is_array($testDataCollector->getEnum("t_locations", "government")));
		$this->assertTrue(TRUE, is_array($testDataCollector->getEnum("t_locations", "qualities", Array())));

		// legal call return array
		$this->assertTrue(TRUE, isset($x));
		$this->assertTrue(TRUE, isset($y));
		$this->assertTrue(TRUE, is_array($x));
		$this->assertTrue(TRUE, is_array($y));
		$this->assertSame($x['value'], "Autocracy");
		$this->assertSame($y['value'], Array("Academic"));
		$this->assertTrue(TRUE, is_array($x['list']));
		$this->assertTrue(TRUE, is_array($y['list']));
	}
}

?>
