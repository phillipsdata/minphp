<?php
/**
 *
 */
class ControllerTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Controller
     */
    protected $controller;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->controller = new Controller;
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
    }

    /**
     * @covers Controller::index
     */
    public function testIndex()
    {
        $this->assertNull($this->controller->index());
    }

    /**
     * @covers Controller::preAction
     */
    public function testPreAction()
    {
        $this->assertNull($this->controller->preAction());
    }

    /**
     * @covers Controller::postAction
     */
    public function testPostAction()
    {
        $this->assertNull($this->controller->postAction());
    }
}
