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
     * @covers Record::select
     */
    public function testSelect()
    {
        $this->assertInstanceOf("Record", $this->Record->select());
    }
    
    /**
     * @covers Record::from
     */
    public function testFrom()
    {
        $this->assertInstanceOf("Record", $this->Record->from("table"));
    }
    
    /**
     * @covers Record::join
     * @covers Record::buildJoin
     * @covers Record::buildConditionals
     * @covers Record::buildConditional
     */
    public function testJoin()
    {
        $this->assertInstanceOf("Record", $this->Record->join("table2", "table1.field", "=", "table2.field"));
    }

    /**
     * @covers Record::leftJoin
     * @covers Record::buildJoin
     * @covers Record::buildConditionals
     * @covers Record::buildConditional
     */
    public function testLeftJoin()
    {
        $this->assertInstanceOf("Record", $this->Record->leftJoin("table2", "table1.field", "=", "table2.field"));
    }

    /**
     * @covers Record::rightJoin
     * @covers Record::buildJoin
     * @covers Record::buildConditionals
     * @covers Record::buildConditional
     */
    public function testRightJoin()
    {
        $this->assertInstanceOf("Record", $this->Record->rightJoin("table2", "table1.field", "=", "table2.field"));
    }

    /**
     * @covers Record::innerJoin
     * @covers Record::buildJoin
     * @covers Record::buildConditionals
     * @covers Record::buildConditional
     */
    public function testInnerJoin()
    {
        $this->assertInstanceOf("Record", $this->Record->innerJoin("table2", "table1.field", "=", "table2.field"));
    }
    
    /**
     * @covers Record::on
     * @covers Record::setConditional
     */
    public function testOn()
    {
        $this->assertInstanceOf("Record", $this->Record->on("table1.field", "=", "table2.field")->innerJoin("table2"));
    }
    
    
    /**
     * @covers Record::orOn
     * @covers Record::setConditional
     */
    public function testOrOn()
    {
        $this->assertInstanceOf("Record", $this->Record->orOn("table1.field", "=", "table2.field")->innerJoin("table2"));
    }
    
    /**
     * @covers Record::where
     * @covers Record::setConditional
     */
    public function testWhere()
    {
        $this->assertInstanceOf("Record", $this->Record->where("table1.field", "=", "table2.field"));
    }

    /**
     * @covers Record::orWhere
     * @covers Record::setConditional
     */
    public function testOrWhere()
    {
        $this->assertInstanceOf("Record", $this->Record->orWhere("table1.field", "=", "table2.field"));
    }
    
    /**
     * @covers Record::duplicate
     * @covers Record::setConditional
     */
    public function testDuplicate()
    {
        $this->assertInstanceOf("Record", $this->Record->duplicate("table1.field", "=", "new value"));
    }
    
    /**
     * @covers Record::like
     * @covers Record::setConditional
     */
    public function testLike()
    {
        $this->assertInstanceOf("Record", $this->Record->like("table1.field", "%value%"));
    }

    /**
     * @covers Record::notLike
     * @covers Record::setConditional
     */
    public function testNotLike()
    {
        $this->assertInstanceOf("Record", $this->Record->notLike("table1.field", "%value%"));
    }

    
    /**
     * @covers Record::orLike
     * @covers Record::setConditional
     */
    public function testOrLike()
    {
        $this->assertInstanceOf("Record", $this->Record->orLike("table1.field", "%value%"));
    }

    /**
     * @covers Record::orNotLike
     * @covers Record::setConditional
     */
    public function testOrNotLike()
    {
        $this->assertInstanceOf("Record", $this->Record->orNotLike("table1.field", "%value%"));
    }
    
    /**
     * @covers Record::having
     * @covers Record::setConditional
     */
    public function testHaving()
    {
        $this->assertInstanceOf("Record", $this->Record->having("table1.field", "=", "table2.field"));
    }

    /**
     * @covers Record::orHaving
     * @covers Record::setConditional
     */
    public function testOrHaving()
    {
        $this->assertInstanceOf("Record", $this->Record->orHaving("table1.field", "=", "table2.field"));
    }
    
    /**
     * @covers Record::group
     */
    public function testGroup()
    {
        $this->assertInstanceOf("Record", $this->Record->group("table1.field"));
        $this->assertInstanceOf("Record", $this->Record->group(array("table1.field", "table1.field2")));
    }
    
    /**
     * @covers Record::order
     */
    public function testOrder()
    {
        $this->assertInstanceOf("Record", $this->Record->order(array('table1.field' => "asc")));
        $this->assertInstanceOf("Record", $this->Record->order(array("table1.field", "table1.field2")));
    }
    
    /**
     * @covers Record::open
     */
    public function testOpen()
    {
        $this->assertInstanceOf("Record", $this->Record->open());
    }
    
    /**
     * @covers Record::close
     */
    public function testClose()
    {
        $this->assertInstanceOf("Record", $this->Record->open()->close());
        $this->assertInstanceOf("Record", $this->Record->open()->where("table1.field", "=", "table2.field")->close("where"));
        $this->assertInstanceOf("Record", $this->Record->open()->on("table1.field", "=", "table2.field")->close("on"));
        $this->assertInstanceOf("Record", $this->Record->open()->having("table1.field", "=", "table2.field")->close("having"));
        $this->assertInstanceOf("Record", $this->Record->open()->duplicate("table1.field", "=", "new value")->close("duplicate"));
    }
    
    /**
     * @covers Record::reset
     */
    public function testReset()
    {
        $record = clone $this->Record;
        $this->Record->where("table1.field", "=", "table2.field");
        $this->assertNotEquals($record, $this->Record);
        $this->Record->reset();
        $this->assertEquals($record, $this->Record);
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
