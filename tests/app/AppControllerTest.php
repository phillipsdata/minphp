<?php
/**
 *
 */
class AppControllerTest extends PHPUnit_Framework_TestCase
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
     * @covers AppController::__construct
     */
    public function test__construct() {
        $this->assertInstanceOf('AppController', new AppController());
    }
}
