<?php
/**
 *
 */
class ConfigureTest extends PHPUnit_Framework_TestCase
{

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
    }

    /**
     * @covers Configure::get
     */
    public function testGet()
    {
        $this->assertNull(Configure::get("testGet"));
        
        Configure::set("testGet", "data");
        
        $this->assertEquals("data", Configure::get("testGet"));
    }

    /**
     * @covers Configure::exists
     */
    public function testExists()
    {
        $this->assertFalse(Configure::exists("testExists"));
        Configure::set("testExists", "data");
        $this->assertTrue(Configure::exists("testExists"));
        
        $this->assertFalse(Configure::exists("ANullKey"));
        Configure::set("ANullKey", null);
        $this->assertTrue(Configure::exists("ANullKey"));
    }

    /**
     * @covers Configure::free
     */
    public function testFree()
    {
        Configure::set("testFree", "data");
        Configure::free("testFree");
        $this->assertFalse(Configure::exists("testFree"));
    }

    /**
     * @covers Configure::set
     */
    public function testSet()
    {
        $this->assertFalse(Configure::exists("testSet"));
        Configure::set("testSet", "data");
        $this->assertEquals("data", Configure::get("testSet"));
        
        Configure::set("testSet", (object)array('a' => 1, 'b' => 2));
        $this->assertObjectHasAttribute('a', Configure::get("testSet"));
        $this->assertObjectHasAttribute('b', Configure::get("testSet"));
    }

    /**
     * @covers Configure::load
     */
    public function testLoad()
    {
        $file_name = "config_test";
        $file_path = realpath(dirname(__FILE__) . "/../") . "/config/";
        
        $this->assertFileExists($file_path . $file_name . ".php");
        
        Configure::load($file_name, $file_path);
        
        $this->assertEquals(1, Configure::get("ConfigTest.a"));
        $this->assertEquals(2, Configure::get("ConfigTest.b"));
    }

    /**
     * @covers Configure::errorReporting
     */
    public function testErrorReporting()
    {
        Configure::errorReporting(-1);
        $this->assertEquals(-1, error_reporting());
        
        Configure::errorReporting(E_ERROR);
        $this->assertEquals(E_ERROR, error_reporting());
    }
}
