<?php
/**
 * Handles mapping of URIs from one type to another
 *
 * @package minPHP
 * @subpackage minPHP.lib
 */
final class Router {
	/**
	 * @var array A 2-dimensional array containg the original URIs and their mapped counter parts
	 */
	protected static $routes;
	
	/**
	 * Protected constructor to prevent instance creation
	 */
	protected function __construct() {
		
	}
	
	/**
	 * Sets a route from $orig_uri to $mapped_uri
	 * 
	 * @param string $orig_uri The original URI to map from
	 * @param string $mapped_uri The destination URI to map to
	 */
	public static function route($orig_uri, $mapped_uri) {
		if (strlen($orig_uri) == 0 || strlen($mapped_uri) == 0)
			throw new Exception("Illegal URI specified in Router::route()");
			
		self::$routes['orig'][] = "/" . self::escape($orig_uri) . "/i";
		self::$routes['mapped'][] = self::escape($mapped_uri);
	}
	
	/**
	 * Maps the requested URI to the proper re-mapped URI, if available
	 *
	 * @param string $request
	 * @return string The new URI, or the requested URI if no mapping exists for this request
	 */
	public static function match($request) {
		if (is_array(self::$routes['orig']) && is_array(self::$routes['mapped']))
			return self::unescape(preg_replace(self::$routes['orig'], self::$routes['mapped'], $request, 1));
		return $request;
	}
	
	/**
	 * Escapes a URI, making it safe for preg (regex) functions
	 * 
	 * @param string $uri The URI to be escaped
	 * @return string the escaped $uri string
	 */
	public static function escape($uri) {
		return addcslashes($uri, "/\\");
	}
	
	/**
	 * Unescapes a URI that has been escaped with Router::escape()
	 *
	 * @param string $uri The URI to be unescaped
	 * @return string the unescaped $uri string
	 */
	public static function unescape($uri) {
		return stripcslashes($uri);
	}
	
	/**
	 * Converts a directory string into a properly formed URI
	 */
	public static function makeURI($dir) {
		return str_replace("\\", "/", $dir);
	}
	
	/**
	 * Parses the given URI into an array of its components
	 *
	 * @param string $uri The URI to parse
	 * @return array The URI broken into its many parts
	 */
	public static function parseURI($uri) {
		return explode("/", str_replace("?", "/?", $uri));
	}
	
	/**
	 * Filters out any part of the web root from the uri path
	 *
	 * @param string $uri The URI to filter
	 * @return string The filtered URI
	 */
	public static function filterURI($uri) {
		return preg_replace("/^(" . self::escape(WEBDIR) . "|" . self::escape(dirname(WEBDIR)) . "|\/)/i", "", $uri, 1);
	}
	
	/**
	 * Uses PHP's ReflectionClass to test the given object for the given method's callability.
	 * Only public, non-abstract, non-constructor/destructors are considered callable.
	 *
	 * @param Object $obj The object we're searching
	 * @param string $method The name of the method we're looking for in $obj
	 * @param string $inherits_from The class that $obj must inherit from, null otherwise.
	 * @return boolean true if the method is callable, false otherwise.
	 */
	public static function isCallable($obj, $method, $inherits_from="Controller") {

		if (is_object($obj)) {
			$ref = new ReflectionClass($obj);
			if ($ref->isAbstract())
				return false;
				
			try {
				$meth = $ref->getMethod($method);
				$declared_class = $meth->getDeclaringClass();
				
				// Methods that must be declared public due to bug in PHP < 5.2 [#37632], but are not
				// publically callable. Note: this bug does not affect protected methods declared as final.
				$public_protected = array("preaction", "postaction");
				
				// A method may be required to inherit from the given class
				if (($inherits_from != null && ($declared_class->getName() != $inherits_from && !$declared_class->isSubclassOf($inherits_from)))
					|| !$meth->isPublic() || $meth->isProtected() || $meth->isConstructor()
					|| $meth->isDestructor() || $meth->isAbstract() || in_array(strtolower($method), (array)$public_protected))
					return false;
				return true;
			}
			catch (ReflectionException $e) {
				return false;
			}
		}
		
		return false;
	}
	
	/**
	 * Finds the controller and action and all get parameters that the given URI routes to
	 *
	 * @param string $request_uri A URI to parse
	 * @return array An array containing the following indexes:
	 * 	- controller The name of the controller this URI maps to
	 * 	- action The action method this URI maps to
	 * 	- get An array of get parameters this URI maps to
	 * 	- uri An array of URI parts
	 * 	- uri_str A string representation of the URI containing the controller requested (if no passed in the URI)
	 */
	public static function routesTo($request_uri) {
		$plugin = null;
		$controller = Configure::get("System.default_controller");
		$action = null;
		$get = array();
		$uri = array();
		$uri_str = null;
		
		// Filter the URI, removing any part of the web root
		$filtered_uri = Router::filterURI($request_uri);
		
		// Handle routing. Routes are defined in config/routes.php
		if (Loader::load(CONFIGDIR . "routes.php"))
			$filtered_uri = Router::match($filtered_uri);
			
		// Parse the URI into its many parts
		$temp = Router::parseURI($filtered_uri);
		
		$uri = array();
		$uri_str = "";
		if (is_array($temp)) {
			foreach ($temp as $key => $value) {
				if ($value != "") {
					$uri[] = $value;
					$uri_str .= $value . "/";
				}
			}
		}
		
		// If the controller was not passed in the URI add it to the URI string
		if (!isset($uri[0]))
			$uri_str = $controller . "/" . $uri_str;
		
		$i=0;                 
		if (isset($uri[$i][0]) && $uri[$i][0] != "?")
			$controller = Loader::fromCamelCase($uri[$i++]);

		if (is_dir(PLUGINDIR . $controller . DS)) {
			$plugin = $controller;
			if (isset($uri[$i][0]) && $uri[$i][0] != "?")
				$controller = $uri[$i++];
			else
				$controller = Configure::get("System.default_controller");
		}
		
		// Determine the action (if any) to call in the controller
		if (isset($uri[$i][0]) && $uri[$i][0] != "?")
			$action = $uri[$i++];
		
		// Assign all remaining parameters as GET params, if a param is of the form key:value then
		// add it as an associative element.
		$num_uri = count($uri);
		for ($j=0; $i<$num_uri; $i++) {
			// If this param begins with a question mark (?) it is handled separately, below.
			if (substr($uri[$i], 0, 1) == "?")
				continue;

			$uri[$i] = rawurldecode($uri[$i]);
			
			if (($loc = strpos($uri[$i], ":")) !== false) {
				$key = substr($uri[$i], 0, $loc);
				$value = substr($uri[$i], $loc+1);
				$get[$key] = $value;
			}
			else
				$get[$j++] = $uri[$i];
		}
		
		// Assign get params passed using query string (e.g. ?)
		foreach ($_GET as $key=>$value)
			$get[$key] = $value;
		
		return array('plugin'=>$plugin, 'controller'=>$controller, 'action'=>$action, 'get'=>$get, 'uri'=>$uri, 'uri_str'=>$uri_str);
	}
}
?>