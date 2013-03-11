<?php
require_once "components" . DIRECTORY_SEPARATOR . "record" . DIRECTORY_SEPARATOR . "record.php";

class RecordTest extends PHPUnit_Framework_TestCase {
	
	private $record;
	
	public function setUp() {
		$this->record = new Record();
	}
	
	public function testSetReturnRecordInstance() {

		$this->assertInstanceOf("Record", $this->record->set("field", "value"));
	}
}
?>