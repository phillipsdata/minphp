<?php
/**
 *
 */
class RouterTest extends PHPUnit_Framework_TestCase
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
     * @covers Router::route
     * @dataProvider routeProvider
     * @expectedException Exception
     */
    public function testRoute($orig_uri, $mapped_uri)
    {
        Router::route($orig_uri, $mapped_uri);
    }
    
    /**
     * Data provider for RouterTest::testRoute
     */
    public function routeProvider()
    {
        return array(
            array("main/", ""),
            array("", ".*")
        );
    }

    /**
     * @covers Router::match
     * @covers Router::route
     */
    public function testMatch()
    {
        $uri = "a/b/c";
        
        // No match
        $this->assertEquals($uri, Router::match($uri));
        
        // Match
        Router::route($uri, "[a-z]/[a-z]/[a-z]");
        $this->assertEquals("[a-z]/[a-z]/[a-z]", Router::match($uri));
        
    }

    /**
     * @covers Router::escape
     */
    public function testEscape()
    {
        $this->assertEquals("\/a\/b\/c", Router::escape("/a/b/c"));
        $this->assertEquals("\/a\/b\/c\/", Router::escape("/a/b/c/"));
    }

    /**
     * @covers Router::unescape
     */
    public function testUnescape()
    {
        $this->assertEquals("/a/b/c", Router::unescape("\/a\/b\/c"));
        $this->assertEquals("/a/b/c/", Router::unescape("\/a\/b\/c\/"));
    }

    /**
     * @covers Router::makeURI
     */
    public function testMakeURI()
    {
        $this->assertEquals("/a/b/c/", Router::makeURI("/a/b/c/"));
        $this->assertEquals("/a/b/c/", Router::makeURI("\\a\\b\\c\\"));
    }

    /**
     * @covers Router::parseURI
     * @todo   Implement testParseURI().
     */
    public function testParseURI()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Router::filterURI
     * @todo   Implement testFilterURI().
     */
    public function testFilterURI()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Router::isCallable
     * @todo   Implement testIsCallable().
     */
    public function testIsCallable()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Router::routesTo
     * @todo   Implement testRoutesTo().
     */
    public function testRoutesTo()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }
}
