<?php
/**
 *
 */
class ViewTest extends PHPUnit_Framework_TestCase
{
    protected $view;
    
    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->view = new View();
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
    }
    
    /**
     * @covers View::__construct
     */
    public function test__construct() {
        $this->assertEquals(Configure::get("System.default_view"), $this->view->view);
        $this->assertEquals(Configure::get("System.view_ext"), $this->view->view_ext);
        
        $view = new View("test_file", "test_dir");
        $this->assertEquals("test_dir", $view->view);
        $this->assertEquals("test_file", $view->file);
    }

    /**
     * @covers View::__clone
     */
    public function test__clone()
    {
        $this->view->property = "value";
        $view_clone = clone $this->view;
        $this->assertObjectHasAttribute("property", $view_clone);
    }

    /**
     * @covers View::setDefaultView
     */
    public function testSetDefaultView()
    {
        $path = "the/path/to/";
        $this->view->setDefaultView($path);
        $this->assertEquals($path, $this->view->default_view_path);
        $this->assertEquals($path, $this->view->view_path);
    }

    /**
     * @covers View::setView
     * @covers View::getViewPath
     */
    public function testSetView()
    {
        $view = $this->view->view;
        $file = $this->view->file;
        
        $this->view->setView();
        $this->assertEquals($view, $this->view->view);
        $this->assertEquals($file, $this->view->file);
        
        $this->view->setView("file", "view");
        $this->assertEquals("view", $this->view->view);
        $this->assertEquals("file", $this->view->file);
        
        $this->view->setView("file", "plugin.view");
        $this->assertEquals("view", $this->view->view);
        $this->assertContains("plugin", $this->view->view_path);
    }

    /**
     * @covers View::set
     */
    public function testSet()
    {
        $this->assertTrue(method_exists($this->view, "set"));
    }

    /**
     * @covers View::fetch
     * @covers View::set
     * @todo   Implement testFetch().
     */
    public function testFetch()
    {
        $expected_output = "1. value-\n2. 1,2,3-\n3. 1.2-\n4. -";
        $var1 = array(1, 2, 3);
        $var2 = 1.2;
        $this->view->setDefaultView("../tests/app/");
        $this->view->set("key", "value");
        $this->view->set(compact("var1", "var2"));
        $this->assertEquals($expected_output, $this->view->fetch("view_test"));
        
        $expected_output = "1. value-\n2. 1,2,3-\n3. 1.2-\n4. Hello World!-";
        $partial_view = new View("view_partial");
        $partial_view->setDefaultView("../tests/app/");
        $this->view->set("partial", $partial_view);
        $this->assertEquals($expected_output, $this->view->fetch("view_test"));
    }
}
