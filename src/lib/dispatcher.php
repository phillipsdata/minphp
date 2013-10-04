<?php
/**
 * This class is invoked by the driver (index.php) and handles dispatching of requests
 * to the proper controller. It extends Controller only so that it can invoke
 * its protected methods.
 *
 * @package minPHP
 * @subpackage minPHP.lib
 */
class Dispatcher extends Controller {
	/**
	 * Dispatch a Command Line Interface request
	 * 
	 * @param array $args All CLI arguments
	 */
	public static function dispatchCli($args) {
		$request_uri = "/";
		
		// Build the request URI based on the command line parameters
		$num_args = count($args);
		for ($i=1; $i<$num_args; $i++)
			$request_uri .= $args[$i] . "/";
		
		self::dispatch($request_uri, true);
	}
	
	/**
	 * Dispatch the request to the proper controller
	 * 
	 * @param string $request_uri The request URI string
	 * @param boolean $is_cli Whether or not this requests is a command line request
	 * @throws Exception thrown when request can not be dispatched or Dispatcher::raiseError can not handle the error
	 */
	public static function dispatch($request_uri, $is_cli=false) {
		
		self::cleanGlobals();
		
		$_post = $_POST;
		$_files = $_FILES;
		
		list($plugin, $controller, $action, $_get, $uri, $uri_str) = array_values(Router::routesTo($request_uri));

		// If caching is enabled, check if this request exists in the cache
		// If so feed it, otherwise continue as normal. Cached pages can only
		// be fed if no post data has been submitted during the request.
		if (Configure::get("Caching.on") && empty($_post)) {
			if (($output = Cache::fetchCache($uri_str))) {
				echo $output;
				return;
			}
		}
		
		// Initialize the AppModel and AppController, so they can be
		// automatically extended
		Loader::load(ROOTWEBDIR . APPDIR . "app_model.php");
		Loader::load(ROOTWEBDIR . APPDIR . "app_controller.php");
		
		$plugin_path = null; // relative path to the plugin directory if it exists
		
		if (!$plugin) {
			if (!Loader::load(CONTROLLERDIR . $controller . ".php"))
				throw new Exception("<strong>" . $controller . "</strong> is not a valid controller", 404);
		}
		else {
			if (file_exists(PLUGINDIR . $plugin . DS . "controllers" . DS . $controller . ".php")) {

				$plugin_path = str_replace(ROOTWEBDIR, "", PLUGINDIR) . $plugin . DS;

				// Load parent plugin model
				Loader::load(PLUGINDIR . $plugin . DS . $plugin . "_model.php");
				
				// Load parent plugin controller
				Loader::load(PLUGINDIR . $plugin . DS . $plugin . "_controller.php");

				// Load the plugin
				Loader::load(PLUGINDIR . $plugin . DS . "controllers" . DS . $controller . ".php");
			}
			else
				throw new Exception("<strong>" . $controller . "</strong> is not a valid controller", 404);
		}
		
		// If the first character of the controller is a number we must prepend the controller
		// with an underscore.
		$contrl = (is_numeric(substr($controller, 0, 1)) ? "_" : "") . Loader::toCamelCase($controller);
		$ctrl = new $contrl($controller, $action, $is_cli);
		
		// Make the POST/GET/FILES available to the controller
		$ctrl->uri = $uri;
		$ctrl->uri_str = $uri_str;
		$ctrl->get = $_get;
		$ctrl->post = $_post;
		$ctrl->files = $_files;
		$ctrl->plugin = $plugin;
		$ctrl->controller = $controller;
		$ctrl->action = $action;
		$ctrl->is_cli = $is_cli;
		
		if ($plugin_path)
			$ctrl->setDefaultViewPath($plugin_path);

		// Handle pre action (overwritten by the controller)
		$ctrl->preAction();
		
		$action_return = null;
		
		// Invoke the desired action, if it exists
		if ($action != null) {
			if (method_exists($ctrl, $action)) {
				// This action can only be called if it is public
				if (Router::isCallable($ctrl, $action))
					$action_return = $ctrl->$action();
				// The method is private and thus is not callable
				else
					throw new Exception("<strong>" . $action . "</strong> is not a callable method in controller <strong>" . $controller . "</strong>", 404);
			}
			else
				throw new Exception("<strong>" . $action . "</strong> is not a valid method in controller <strong>" . $controller . "</strong>", 404);
		}
		// Call the default action
		else
			$action_return = $ctrl->index(); // May be overwritten by the controller
		
		// Handle post action (overwritten by the controller)
		$ctrl->postAction();
		
		// Only render if the action returned void or something other than false and this is not a CLI request
		if ($action_return !== false && (!$is_cli || Configure::get("System.cli_render_views")))
			$ctrl->render();
	}
	
	/**
	 * Print an exception thrown error page
	 *
	 * @param Exception $e An exception thrown
	 * @throws Exception
	 */
	public static function raiseError($e) {
		
		$error_message = null;
		
		if ($e instanceof UnknownException) {
			$error_message = htmlentities($e->getMessage(), ENT_QUOTES, "UTF-8") . " on line <strong>" .
				$e->getLine() . "</strong> in <strong>" . $e->getFile() .
				"</strong>";
		}
		elseif ($e instanceof Exception) {
			if ($e->getCode() == 404 && Configure::get("System.404_forwarding")) {

				// Forward to 404 - page not found.
				header("HTTP/1.0 404 Not Found");
				header("Location: " . WEBDIR . "404/");
				exit();
			}
			elseif (Configure::get("System.debug")) {
				$error_message = htmlentities($e->getMessage(), ENT_QUOTES, "UTF-8") . " on line <strong>" .
					$e->getLine() . "</strong> in <strong>" . $e->getFile() .
					"</strong>\n" . "<br /><br /><strong>Printing Stack Trace:</strong><br /><code>" .
					nl2br($e->getTraceAsString()) . "</code>";
			}
			elseif (error_reporting() !== 0)
				$error_message = htmlentities($e->getMessage(), ENT_QUOTES, "UTF-8");
		}
		
		try {
			$ctrl = new Controller();
			$ctrl->set("error", $error_message);
			$ctrl->render("error", Configure::get("System.error_view"));
		}
		catch (Exception $err) {
			// Throw our original error, since the error can not be handled cleanly
			throw $e;
		}
	}

	/**
	 * Strip slashes from the given string
	 * @param string $str
	 */
	public static function stripSlashes(&$str) {
		$str = stripslashes($str);
	}
	
	/**
	 * Clean all super globals by removing slashes added by 'magic quotes'
	 */
	private static function cleanGlobals() {

		if (function_exists("get_magic_quotes_gpc") && get_magic_quotes_gpc()) {
			array_walk_recursive($_GET, array('Dispatcher', 'stripSlashes'));
			array_walk_recursive($_POST, array('Dispatcher', 'stripSlashes'));
			array_walk_recursive($_COOKIE, array('Dispatcher', 'stripSlashes'));
			array_walk_recursive($_REQUEST, array('Dispatcher', 'stripSlashes'));
		}
	}
}
?>