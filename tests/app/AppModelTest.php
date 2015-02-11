<?php
/**
 *
 */
class AppModelTest extends PHPUnit_Framework_TestCase
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
     * @covers AppModel::__construct
     */
    public function test__construct() {
        $this->assertInstanceOf('AppModel', new AppModel());
    }
}
