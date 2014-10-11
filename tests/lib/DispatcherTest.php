<?php
/**
 *
 */
class DispatcherTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Dispatcher
     */
    protected $dispatcher;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->dispatcher = new Dispatcher();
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
    }

    /**
     * @covers Dispatcher::dispatchCli
     * @dataProvider dispatchCliProvider
     */
    public function testDispatchCli(array $args, $expected)
    {
        /* Uncomment this block when Dispatcher::DispatchCli is no longer static
        $dispatcher = $this->getMock("Dispatcher", array("dispatch"));
        $dispatcher->expects($this->once())
            ->method("dispatch")
            ->with($expected, true);
        
        $dispatcher->dispatchCli($args);
        */
        // But for now...
        $this->markTestIncomplete(
            'Can not test static Dispatcher::DispatchCli.'
        );
    }
    
    public function dispatchCliProvider()
    {
        return array(
            array(array("index.php", "a", "b", "c"), "a/b/c/"),
            array(array("index.php", "-a", "--b", "c"), "-a/--b/c/")
        );
    }

    /**
     * @covers Dispatcher::dispatch
     * @todo   Implement testDispatch().
     */
    public function testDispatch()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Dispatcher::raiseError
     * @dataProvider raiseErrorProvider
     */
    public function testRaiseError($e, $type)
    {
        switch ($type) {
            case "output":
                $this->expectOutputRegex("//i", $e->getMessage());
                Dispatcher::raiseError($e);
                break;
            case "header":
                /*
                Dispatcher::raiseError() must be refactored to test with mocks
                Configure::set("System.404_forwarding", true);
                
                Dispatcher::raiseError($e);
                $this->assertTrue(headers_sent());
                */
                break;
            case "exception":
                $exception = null;
                Configure::set("System.error_view", "nonexistentview");
                
                try {
                    Dispatcher::raiseError($e);
                }
                catch (Exception $thrown) {
                    $exception = $thrown;
                }
                
                $this->assertSame($e, $exception);
                break;
        }
    }
    
    public function raiseErrorProvider() {
        return array(
            array(new UnknownException("test error", 1, null, null, 0), "output"),
            array(new Exception("404", 404), "header"),
            array(new Exception("error"), "exception"),
        );
    }

    /**
     * @covers Dispatcher::stripSlashes
     */
    public function testStripSlashes()
    {
        $str = "I'm a clean string.";
        $escaped = addslashes($str);
        
        Dispatcher::stripSlashes($escaped);
        $this->assertEquals($str, $escaped);
    }
}
