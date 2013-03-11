<?php
/**
 * Handles the loading of various files and objects
 *
 * @package minPHP
 * @subpackage minPHP.lib
 */
final class Loader {
	
	/**
	 * Protected constructor to prevent instance creation
	 */
	protected function __construct() {
		
	}
	
	/**
	 * Loads models, which may or may not exist within a plugin of the same
	 * name. First looks in the plugin directory, if no match is found, looks
	 * in the models directory.
	 *
	 * @param object $parent The object to which to attach the given models
	 * @param array $models An array of models to load and initialize
	 * @throws Exception
	 */
	public static function loadModels(&$parent, $models) {
		// Assign all models the controller specified by $parent uses
		if (is_array($models)) {
			foreach ($models as $key => $value) {
				if (is_array($value))
					$model = $key;
				else {
					$model = $value;
					$value = array();
				}
				
				$plugin = null;
				if (($c = strpos($model, "."))) {
					$plugin = self::fromCamelCase(substr($model, 0, $c)) . DS;
					$model = substr($model, $c+1);
				}
				
				$model_name_file = self::fromCamelCase($model);
				$model_name = self::toCamelCase($model);
				
				if ($plugin) {
					// Ensure the model exists
					if (!file_exists(PLUGINDIR . $plugin . "models" . DS . $model_name_file . ".php"))
						throw new Exception("<strong>" . $model_name . "</strong> model not found");
					
					// Include the parent Plugin Model, if it exists
					Loader::load(PLUGINDIR . $plugin . substr($plugin, 0, -1) . "_model.php");
					
					// Include the model and its base class
					if (file_exists(PLUGINDIR . $plugin . "models" . DS . $model_name_file . "_base.php"))
						require_once PLUGINDIR . $plugin . "models" . DS . $model_name_file . "_base.php";
						
					require_once PLUGINDIR . $plugin . "models" . DS . $model_name_file . ".php";
				}
				else {
					// Ensure the model exists
					if (!file_exists(MODELDIR . $model_name_file . ".php"))
						throw new Exception("<strong>" . $model_name . "</strong> model not found");
					
					// Include the model and its base class
					if (file_exists(MODELDIR . $model_name_file . "_base.php"))
						require_once MODELDIR . $model_name_file . "_base.php";
						
					require_once MODELDIR . $model_name_file . ".php";
				}
				
				// Instantiate the model
				$parent->$model_name = call_user_func_array(array(new ReflectionClass($model_name), 'newInstance'), $value);
			}
		}
	}
	
	/**
	 * Loads the given components, attaching them to the given parent object.
	 *
	 * @param object $parent The parent to which to attach the given components
	 * @param array $components An array of components and [optionally] their parameters
	 */
	public static function loadComponents(&$parent, $components) {
		self::loadAndInitialize($parent, "component", $components);
	}
	
	/**
	 * Loads the given helpers, attaching them to the given parent object.
	 *
	 * @param object $parent The parent to which to attach the given helpers
	 * @param array $helpers An array of helpers and [optionally] their parameters
	 */
	public static function loadHelpers(&$parent, $helpers) {
		self::loadAndInitialize($parent, "helper", $helpers);
	}
	
	/**
	 * Convert a string to "CamelCase" from "file_case"
	 * 
	 * @param string $str the string to convert
	 * @return string the converted string
	 */
	public static function toCamelCase($str) {
		static $cb_func = null;
		
		if ($cb_func == null)
			$cb_func = create_function('$c', 'return strtoupper($c[1]);');

		if (isset($str[0]))
			$str[0] = strtoupper($str[0]);
			
		return preg_replace_callback('/_([a-z])/', $cb_func, $str);
	}
	
	/**
	 * Convert a string to "file_case" from "CamelCase".
	 * 
	 * @param string $str the string to convert
	 * @return string the converted string
	 */
	public static function fromCamelCase($str) {
		static $cb_func = null;
		
		if ($cb_func == null)
			$cb_func = create_function('$c', 'return "_" . strtolower($c[1]);');

		if (isset($str[0]))
			$str[0] = strtolower($str[0]);
			
		return preg_replace_callback('/([A-Z])/', $cb_func, $str);
	}
	
	/**
	 * Attempts to include the given file, if it exists.
	 *
	 * @param string $file The file to include
	 * @return boolean Returns true if the file exists and could be included, false otherwise
	 */
	public static function load($file) {
		if (file_exists($file)) {
			include_once $file;
			return true;
		}
		return false;
	}
	
	/**
	 * Loads an initializes the named objects of the given type to the given parent object.
	 * Recognized types include "component" and "helper".
	 *
	 * @param object $parent The parent object to attach the named objects
	 * @param string $type The collection the named objects belong to
	 * @param array $objects The named objects to load and initialize
	 * @throws Exception Throw when invoked with unrecognized $type
	 */
	private static function loadAndInitialize(&$parent, $type, $objects) {
		
		switch ($type) {
			case "component":
				$path = COMPONENTDIR;
				break;
			case "helper":
				$path = HELPERDIR;
				break;
			default:
				throw new Exception("Unrecognized load type <strong>" . $type . "</strong> specified");
				break;
		}
		
		if (is_array($objects)) {
			foreach ($objects as $key => $value) {
				
				if (is_array($value))
					$object = $key;
				else {
					$object = $value;
					$value = array();
				}
				
				$plugin = null;
				if (($c = strpos($object, "."))) {
					$plugin = self::fromCamelCase(substr($object, 0, $c)) . DS;
					$object = substr($object, $c+1);
				}
				
				if ($plugin)
					$dir = PLUGINDIR . $plugin . DS . $type . "s" . DS;
				else
					$dir = $path;
				
				$object_name = self::toCamelCase($object);
				
				// Include the object
				$object = self::fromCamelCase($object);
				$object_file = $object . ".php";
				
				// Search for the object in the root object directory
				if (file_exists($dir . $object_file))
					$object = $dir . $object_file;
				// The object may also appear in a subdirectory of the same name
				elseif (file_exists($dir . $object . DS . $object_file))
					$object = $dir . $object . DS . $object_file;
				// If the object can not be found in either location throw an exception
				else
					throw new Exception("<strong>" . $object_name . "</strong> " . $type . " not found");
				
				require_once $object;

				// Initialize the object
				$parent->$object_name = call_user_func_array(array(new ReflectionClass($object_name), 'newInstance'), $value);
				
				if ($type == "helper") {
					// Link this object with the view and structure view associated with this controller
					if (isset($parent->view) && $parent->view instanceof View)
						$parent->view->$object_name =& $parent->$object_name;
					if (isset($parent->structure) && $parent->structure instanceof View)
						$parent->structure->$object_name =& $parent->$object_name;
				}
			}
		}
	}
}
?>