<?php
require_once "components" . DIRECTORY_SEPARATOR . "record" . DIRECTORY_SEPARATOR . "record.php";

class RecordTest extends PHPUnit_Framework_TestCase
{
    
    private $Record;
    
    public function setUp() {
        $this->Record = new Record();
    }
    
    /**
     * @covers Record::keywordValue
     */
    public function testKeywordValue()
    {
        $this->assertEquals("DEFAULT", $this->Record->keywordValue("DEFAULT")->keyword);
        $this->assertEquals("INDEX", $this->Record->keywordValue("INDEX")->keyword);
    }
    
    /**
     * @covers Record::setField
     */
    public function testSetField()
    {
        $this->assertInstanceOf("Record", $this->Record->setField("name", array('type' => "int", 'size' => 10, 'unsigned' => true), true));
        $this->assertInstanceOf("Record", $this->Record->setField("name", null, false));
    }
    
    /**
     * @covers Record::setKey
     */
    public function testSetKey()
    {
        $this->assertNull($this->Record->setKey(array(), "index"));
        $this->assertInstanceOf("Record", $this->Record->setKey(array("id"), "primary", true, "id", true));
        $this->assertInstanceOf("Record", $this->Record->setKey(array("id"), "primary", true, null, false));
    }
    
    /**
     * @covers Record::quoteIdentifier
     * @dataProvider quoteIdentifierProvider
     */
    public function testQuoteIdentifier($identifier, $result)
    {
        $this->assertEquals($result, $this->Record->quoteIdentifier($identifier));
    }
    
    /**
     * Dataprovider for testQuoteIdentifier
     */
    public function quoteIdentifierProvider()
    {
        return array(
            array(array('table', 'field'), '`table`.`field`'),
            array("table.field", '`table`.`field`'),
            array("field", '`field`')
        );
    }
    
    public function testSetReturnRecordInstance()
    {

        $this->assertInstanceOf("Record", $this->Record->set("field", "value"));
    }
}
