<?php
/**
 * Allows statically configured settings to be stored and accessed on a per-use
 * basis. The purpose of this class is to utilize globally set configurations
 * from a single location. Configurations can be set by default in
 * /config/core.php, or may be set at run time from anywhere within your
 * application.
 *
 * @package minPHP
 * @subpackage minPHP.lib
 */
final class Configure {
	/**
	 * @var array All configured settings handled by this class
	 */
	private static $config = array(); 
	
	/**
	 * Protected constructor to prevent instance creation
	 */
	protected function __construct() {
		
	}
	
	/**
	 * Fetches a setting set using Configure::set()
	 *
	 * @param string $name The name of the setting to get
	 * @return mixed The setting specified by $name, or null if $name was not set
	 */
	public static function get($name) {
		if (isset(self::$config[$name]))
			return self::$config[$name];
		return null;
	}
	
	/**
	 * Checks if the setting exists
	 *
	 * @param string $name The name of the setting to check existance
	 * @return boolean true if $name was set, false otherwise
	 */
	public static function exists($name) {
		if (isset(self::$config[$name]))
			return true;
		return false;
	}
	
	/**
	 * Frees the setting given by $name, if it exists. All settings no longer in
	 * use should be freed using this method whenever possible
	 *
	 * @param string $name The name of the setting to free
	 */
	public static function free($name) {
		if (self::exists($name))
			unset(self::$config[$name]);
	}
	
	/**
	 * Adds the given $value to the configuration using the $name given
	 *
	 * @param string $name The name to give this setting. Use Configure::exists()
	 * to check for pre-existing settings with the same name
	 * @param mixed $value The value to set
	 */
	public static function set($name, $value) {
		self::$config[$name] = $value;
	}
	
	/**
	 * Loads the given file and extracts all $config array elements, adding each
	 * to Configure::$config
	 *
	 * @param string $file The file name in CONFIGDIR to load (without extension)
	 * @param string $config_dir The directory from which to load the given config file, defaults to CONFIGDIR
	 */
	public static function load($file, $config_dir=CONFIGDIR) {
		$file .= ".php";
		
		if (file_exists($config_dir . $file))
			include_once $config_dir . $file;
		
		if (isset($config) && is_array($config)) {
			foreach ($config as $name => $value) {
				self::$config[$name] = $value;
			}
		}
		// Free up memory from the loaded file, since we've already pulled it
		// into our namespace
		unset($config);
	}
	
	/**
	 * Overwrites the existing error reporting level
	 *
	 * @param int $level The Level of error reporting to set
	 */
	public static function errorReporting($level) {
		error_reporting($level);
	}
}
?>