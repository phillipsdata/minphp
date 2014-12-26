<?php
/**
 * Handles writing to and from the cache
 *
 * @package minPHP
 * @subpackage minPHP.lib
 */
final class Cache {
	
	/**
	 * Empties the entire cache of all files (directories excluded, not recursive)
	 *
	 * @param string $path The path within CACHEDIR to empty
	 */
	public static final function emptyCache($path=null) {
		if (!($dir = @opendir(CACHEDIR . $path)))
			return;

		while ($item = @readdir($dir)) {
			if (is_file(CACHEDIR . $path . $item))
				@unlink(CACHEDIR . $path . $item);
		}
	}
	
	/**
	 * Removes the given cache file from the cache
	 *
	 * @param string $name The name of the cache file to remove (note: the original file name, not the cached name of the file)
	 * @param string $path The path within CACHEDIR to clear a given file from
	 * @return boolean True if removed, false if no such file exists
	 * @see Cache::cacheName()
	 */
	public static final function clearCache($name, $path=null) {
		$cache = self::cacheName($name, $path);
		if (is_file($cache)) {
			@unlink($cache);
			return true;
		}
		return false;
	}
	
	/**
	 * Writes the given data to the cache using the name given
	 *
	 * @param string $name The name of the cache file to create (note: the original file name, not the cached name of the file)
	 * @param string $output The data to write to the cache
	 * @param int $ttl The cache file's time to live
	 * @param string $path The path within CACHEDIR to write the file to
	 * @see Cache::cacheName()
	 */
	public static final function writeCache($name, $output, $ttl, $path=null) {
		$cache = self::cacheName($name, $path);
		
		$cache_dir = dirname($cache);
		if (!file_exists($cache_dir))
			mkdir($cache_dir, Configure::get("Cache.dir_permissions"), true);
		
		// Save output to cache file
		file_put_contents($cache, $output);
		// Set the cache expiration date/time
		touch($cache, time()+$ttl);
	}
	
	/**
	 * Fetches the contents of a cache, if it exists and is valid
	 *
	 * @param string $name The name of the cache file to fetch (note: not the actual name of the file on the file system)
	 * @param string $path The path within CACHEDIR to read the file from
	 * @return string A string containing the file contents if the cache file exists and has not yet expired, false otherwise.
	 * @see Cache::cacheName()
	 */
	public static final function fetchCache($name, $path=null) {
		$cache = self::cacheName($name, $path);
		if (file_exists($cache) && filemtime($cache) > time())
			return file_get_contents($cache);
		else
			return false;
	}
	
	/**
	 * Builds the file name of the cache file based on the name given
	 *
	 * @param string $name The name to use when creating the cache file name
	 * @param string $path The path within CACHEDIR to construct the cache file path in
	 * @return string A fully qualified cache file name
	 */
	private static final function cacheName($name, $path=null) {
		return CACHEDIR . $path . md5(strtolower($name)) . Configure::get("Caching.ext");
	}
}
?>