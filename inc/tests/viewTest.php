<?php

class DataCollectorTestCase extends PHPunit_Framework_Testcase
{
	function __construct($param = NULL) {
		parent::__construct($param);
	}

	/*
	 * Test the add function
	 */
	public function testAdd()
	{
		// report section name (assigned in construct)
		$testBuildOutput = new BuildOutput("TestName");
		$this->assertEquals("testname", $testBuildOutput->lcSectionName(), "lcSectionName()");

		// add: basic section structure
		$testBuildOutput->add("column", "value");
		$x = $testBuildOutput->dump();
		$this->assertNotFalse(strpos($x, "id=\"testnameRead\""), "add() read id");
		$this->assertNotFalse(strpos($x, "id=\"testnameReadControl\""), "add() read control id");
		$this->assertNotFalse(strpos($x, "editSection('testname')"), "add() edit section button");
		$this->assertNotFalse(strpos($x, "<span id=\"spancolumn\">value</span>"), "add() read span id");
		$this->assertNotFalse(strpos($x, "id=\"testnameEdit\" class=\"statBlockEdit\" style=\"display:none\""), "add() hidden add new template");
		$this->assertNotFalse(strpos($x, "id=\"testnameForm\" enctype=\"multipart/form-data\""), "add() form");
		$this->assertNotFalse(strpos($x, "saveSection('testname', serializeParams('testname'))"), "add() save button");
		$this->assertNotFalse(strpos($x, "abortSection('testname')"), "add() abort button");

		// add: column and value
		$this->assertNotFalse(strpos($x, "<input type=\"text\" id=\"column\" value=\"value\""), "add() text input");

		// add: value, read label, and edit label
		$testBuildOutput->add("", "value", "Read Label", "Edit Label");
		$x = $testBuildOutput->dump();

		$this->assertNotFalse(strpos($x, "<b class=\"statBlockLabel\">Read Label</b> <span id=\"span\">value</span>"), "add() label");
		$this->assertNotFalse(strpos($x, "Edit Label <input type=\"text\" id=\"\" value=\"value\""), "add() text input");

		// add: value, read label = "()", and edit label = 0
		$testBuildOutput->add("", "value", "()", 0);
		$x = $testBuildOutput->dump();
		$this->assertNotFalse(strpos($x, "(<span id=\"span\">value</span>)"), "add() span-tagged value");
		$this->assertFalse(strpos($x, "<input"), "add() input should not be");

		// add: column, value, read label = 0, and edit label
		$testBuildOutput->add("column", "value", 0, "Edit Label");
		$x = $testBuildOutput->dump();
		$this->assertFalse(strpos($x, "<span id=\"spancolumn\">value</span>"), "span-tagged value should not be");
		$this->assertNotFalse(strpos($x, "Edit Label <input type=\"text\" id=\"column\" value=\"value\""), "add() edit label");

		// add: column, value = object with string 'value' and array 'list' (e.g. enum or manually-set list)
		$testBuildOutput->add("column", Array('value' => "value", 'list' => Array("this", "is", "value")), "", "", "200");
		$x = $testBuildOutput->dump();
		$this->assertNotFalse(strpos($x, "<select id=\"column\" style=\"width:200pt\"> <option value=\"this\" >this</option> <option value=\"is\" >is</option> <option value=\"value\"  selected=\"selected\" >value</option> </select>"), "add() select list");

		// add: column, value = object with array 'value' and array 'list' (e.g. set list (multiple select))
		$testBuildOutput->add("column", Array('value' => Array("is", "value"), 'list' => Array("this", "is", "value")), "", "", "200");
		$x = $testBuildOutput->dump();
		$this->assertNotFalse(strpos($x, "is, value"), "add() multiple select read list");
		$this->assertNotFalse(strpos($x, "<select multiple=\"multiple\" id=\"column\" style=\"width:200pt\"> <option value=\"this\" >this</option> <option value=\"is\"  selected=\"selected\" >is</option> <option value=\"value\"  selected=\"selected\" >value</option> </select>"), "add() multiple select list");

		// add: column, value = object with array 'name' and array 'list' of arrays 'id' and 'name' (e.g. one-to-many db relationship)
		$testBuildOutput->add("column", Array('name' => "name", 'list' => Array(Array('id' => "1", 'name' => "this"), Array('id' => "2", 'name' => "is"), Array('id' => "3", 'name' => "name"))), "", "", "200");
		$x = $testBuildOutput->dump();
		$this->assertNotFalse(strpos($x, "<select id=\"column\" style=\"width:200pt\"> <option value=\"1\" >this</option> <option value=\"2\" >is</option> <option value=\"3\"  selected=\"selected\" >name</option> </select>"), "add() select list");

		// add: column, value = object with array 'name' and array 'list' of arrays of arrays 'id' and 'name' (e.g. many-to-many db relationship)
		$testBuildOutput->add("column", Array(Array('id' => "3", 'name' => "name"), 'list' => Array(Array('id' => "1", 'name' => "this"), Array('id' => "2", 'name' => "is"), Array('id' => "3", 'name' => "name"))), "", "", "200");
		$x = $testBuildOutput->dump();
		$this->assertNotFalse(strpos($x, "<span id=\"divcolumn3\" class=\"manyToMany\" > <select id=\"column3\" style=\"width:200pt\"> <option value=\"name\" selected=\"selected\">name</option> <option value=\"1\" >this</option> <option value=\"2\" >is</option> <option value=\"3\"  selected=\"selected\" >name</option> </select> &nbsp;</span> <span id=\"divcolumn0\" class=\"manyToMany\"  style=\"display:none\" > <select id=\"column0\" style=\"width:200pt\"> <option value=\"\" selected=\"selected\"></option> <option value=\"1\" >this</option> <option value=\"2\" >is</option> <option value=\"3\" >name</option> </select> &nbsp;</span> <span id=\"divcolumnaddnew\"><input type=\"button\" id=\"addnewcolumn\" value=\"+\" onClick=\"copyFromTemplate('column')\"><span id=\"spacer\"> </span></span>"), "add() select list");

		// TO DO: add tests for metadata

		// TO DO: add tests for addRead function

		// TO DO: add tests for addEdit function

		// TO DO: add tests for br function
	}
}

?>
