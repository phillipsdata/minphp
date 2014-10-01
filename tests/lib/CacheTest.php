<?php
/**
 *
 */
class CacheTest extends PHPUnit_Framework_TestCase
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
        Cache::emptyCache();
    }

    /**
     * @covers Cache::emptyCache
     */
    public function testEmptyCache()
    {
        $dir = CACHEDIR;
        file_put_contents($dir . "testfile", "CacheTest::testEmptyCache");
        $this->assertFileExists($dir . "testfile");
        
        Cache::emptyCache("bad/sub/path/");
        $this->assertFileExists($dir . "testfile");
        
        Cache::emptyCache();
        $this->assertFileNotExists($dir . "testfile");
    }

    /**
     * @covers Cache::clearCache
     */
    public function testClearCache()
    {
        $cache_name = "testfile";
        $cache_contents = "CacheTest::testClearCache";
        $this->assertFalse(Cache::clearCache("bad_file_name"));
        
        Cache::writeCache($cache_name, $cache_contents, 10);
        $this->assertEquals($cache_contents, Cache::fetchCache($cache_name));

        $this->assertTrue(Cache::clearCache($cache_name));
        
        $this->assertFalse(Cache::fetchCache($cache_name));
    }

    /**
     * @covers Cache::writeCache
     */
    public function testWriteCache()
    {
        $cache_name = "testfile";
        $cache_contents = "CacheTest::testWriteCache";
        
        Cache::writeCache($cache_name, $cache_contents, -1);
        $this->assertFalse(Cache::fetchCache($cache_name));
        
        $this->assertTrue(Cache::clearCache($cache_name));
        
        Cache::writeCache($cache_name, $cache_contents, 1);
        $this->assertEquals($cache_contents, Cache::fetchCache($cache_name));

        $this->assertTrue(Cache::clearCache($cache_name));
    }

    /**
     * @covers Cache::fetchCache
     * @todo   Implement testFetchCache().
     */
    public function testFetchCache()
    {
        $cache_name = "testfile";
        $cache_contents = "CacheTest::testFetchCache";
        
        Cache::writeCache($cache_name, $cache_contents, -1);
        $this->assertFalse(Cache::fetchCache($cache_name));
        
        $this->assertTrue(Cache::clearCache($cache_name));
        
        Cache::writeCache($cache_name, $cache_contents, 1);
        $this->assertEquals($cache_contents, Cache::fetchCache($cache_name));

        $this->assertTrue(Cache::clearCache($cache_name));
    }
}
