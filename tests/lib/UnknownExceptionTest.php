<?php
/**
 *
 */
class UnknownExceptionTest extends PHPUnit_Framework_TestCase
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
     * @covers UnknownException::setErrorHandler
     * @todo   Implement testSetErrorHandler().
     */
    public function testSetErrorHandler()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers UnknownException::setExceptionHandler
     */
    public function testSetExceptionHandler()
    {
        $message = "Testing Error";
        $e = new Exception($message);
        $this->expectOutputRegex("/" . $message . "/");
        
        UnknownException::setExceptionHandler($e);
    }

    /**
     * @covers UnknownException::setFatalErrorHandler
     * @todo   Implement testSetFatalErrorHandler().
     */
    public function testSetFatalErrorHandler()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }
}
