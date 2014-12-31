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
        if (!is_dir(CACHEDIR))
            mkdir(CACHEDIR, 0777, true);
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
        
        mkdir($dir . "sub/path", 0777, true);
        file_put_contents($dir . "sub/path/testfile", "CacheTest::testEmptyCache");
        $this->assertFileExists($dir . "sub/path/testfile");
        Cache::emptyCache("sub/path/");
        $this->assertFileNotExists($dir . "sub/path/testfile");
        rmdir($dir . "sub/path");
        rmdir($dir . "sub");
    }

    /**
     * @covers Cache::clearCache
     * @covers Cache::cacheName
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
     * @covers Cache::cacheName
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
     * @covers Cache::cacheName
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
