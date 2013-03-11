<?php
/**
 * This class is extended by the various controllers, and makes available
 * methods that allow controllers to interact with views, models, components,
 * helpers, and plugins.
 *
 * @package minPHP
 * @subpackage minPHP.lib
 */
class Controller {
	/**
	 * @var object The structure View for this instance
	 */
	public $structure;
	/**
	 * @var string Name of the structure view file (overwritable by the controller)
	 */
	public $structure_view;
	/**
	 * @var object The main View for this instance
	 */
	public $view;
	/**
	 * @var array All parts of the Routed URI 
	 */
	public $uri;
	/**
	 * @var string Requested URI after being Routed
	 */
	public $uri_str;
	/**
	 * @var array All GET parameters
	 */
	public $get;
	/**
	 * @var array All POST data
	 */
	public $post;
	/**
	 * @var array All FILE data
	 */
	public $files;
	/**
	 * @var string Name of the plugin invoked by this request (if any)
	 */
	public $plugin;
	/**
	 * @var string Name of the controller invoked by this request
	 */
	public $controller;
	/**
	 * @var string Action invoked by this request
	 */
	public $action;
	/**
	 * @var boolean Flag whether this is a CLI request
	 */
	public $is_cli;
	/**
	 * @var array Names of all Models this Controller uses
	 */
	protected $uses;
	/**
	 * @var array Names of all Components this Controller uses
	 */
	protected $components;
	/**
	 * @var array Names of all Helpers this Controller and child Views use
	 */
	protected $helpers; // All helpers this instance of this controller may access
	/**
	 * @var boolean Flag used to determine if the view has been rendered. Controller::render() may only be called once
	 */
	private $rendered = false;
	/**
	 * @var mixed Amount of time in seconds to cache the current request, null otherwise
	 */
	private $cache_for = null;

	/**
	 * Constructs a new Controller object
	 */
	public function __construct() {
		$this->structure_view = Configure::get("System.default_structure");
		
		// Initialize the structure view
		$this->structure = new View();
		
		// Initialize the main view
		$this->view = new View();
		
		// Load any preset models
		$this->uses($this->uses);
		
		// Load any preset components
		$this->components($this->components);

		// Load any preset helpers
		$this->helpers($this->helpers);
	}
	
	/**
	 * Load the given models into this controller
	 * 
	 * @param array $models All models to load
	 */
	protected final function uses($models) {
		Loader::loadModels($this, $models);
	}
	
	/**
	 * Load the given components into this controller
	 * 
	 * @param array $components All components to load 
	 */
	protected final function components($components) {
		Loader::loadComponents($this, $components);
	}
	
	/**
	 * Load the given helpers into this controller, making them available to
	 * any implicitly initialized Views, including Controller::$structure
	 * 
	 * @param array $helpers All helpers to load 
	 */
	protected final function helpers($helpers) {
		Loader::loadHelpers($this, $helpers);
	}
	
	/**
	 * The default action method, overwritable.
	 */
	public function index() {
	
	}
	
	/**
	 * Overwritable method called before the index method, or controller specified action.
	 * This method is public to make compatible with PHP 5.1 (due to a bug not fixed until 5.2).
	 * It is, however, not a callable action.
	 */         
	public function preAction() {
	
	}         
	
	/**
	 * Overwritable method called after the index method, or controller specified action
	 * This method is public to make compatible with PHP 5.1 (due to a bug not fixed until 5.2).
	 * It is, however, not a callable action.
	 */
	public function postAction() {
	
	}
	
	/**
	 * Invokes View::set() on $this->view
	 * 
	 * @param mixed $name The name of the variable to set in this view
	 * @param mixed $value The value to assign to the variable set in this view
	 * @see View::set()
	 */
	protected final function set($name, $value=null) {
		$this->view->set($name, $value);
	}
	
	/**
	 * Prints the given template file from the given view.
	 *
	 * This method is only useful for including a static view in another view.
	 * For setting variables in views, or for setting multiple views in a single
	 * Page (e.g. partials) see Controller::partial()
	 *
	 * @param string $file The template file to print
	 * @param string $view The view directory to use (null is default)
	 * @see Controller::partial()
	 */
	protected final function draw($file = null, $view = null) {
		$view = new View($file, $view);
		echo $view->fetch();
	}
	
	/**
	 * Returns the given template file using the supplied params from the given view.
	 *
	 * @param string $file The template file to render
	 * @param array $params An array of parameters to set in the template
	 * @param string $view The view to find the given template file in
	 * @return string The rendered template
	 */
	protected final function partial($file, $params = null, $view = null) {        	
		$partial = clone $this->view;
		
		if (is_array($params)) {
			foreach ($params as $key => $value)
				$partial->set($key, $value);
		}
		return $partial->fetch($file, $view);
	}
	
	/**
	 * Starts caching for the current request
	 *
	 * @param mixed $time The amount of time to cache for, either an integer (seconds) or a proper strtotime string (e.g. "1 hour").
	 * @return boolean True if caching is enabled, false otherwise.
	 */
	protected final function startCaching($time) {
		if (!Configure::get("Caching.on"))
			return false;
		
		if (!is_numeric($time))
			$time = strtotime($time)-time();
		$this->cache_for = $time;
		
		return true;
	}
	
	/**
	 * Stops caching for the current request. If invoked, caching will not be performed for this request.
	 */
	protected final function stopCaching() {
		$this->cache_for = null;
	}
	
	/**
	 * Clears the cache file for the given URI, or for the curren request if no URI is given
	 *
	 * @param mixed $uri The request to clear, if not given or false the current request is cleared
	 */
	protected final function clearCache($uri=false) {
		Cache::clearCache(strtolower($uri ? $uri : $this->uri_str));
	}
	
	/**
	 * Empties the entire cache of all files (directories excluded)
	 */
	protected final function emptyCache() {
		Cache::emptyCache();
	}
	
	/**
	 * Renders the view with its structure (if set).  The view is set into the structure as $content.
	 * This method can only be called once, since it includes the structure when outputting.
	 * To render a partial view use Controller::partial()
	 *
	 * @see Controller::partial()
	 * @param string $file The template file to render
	 * @param string $view The view directory to look in for the template file.
	 */
	protected final function render($file=null, $view=null) {
		if ($this->rendered)
			return;
		
		$template = $this->structure_view;
		
		$this->rendered = true;
		
		// Prepare the structure
		if (strpos($template, DS) > 0) {
			$temp = explode(DS, $template);
			$template = $temp[1];
			$view = $temp[0];
		}
		
		if ($file == null) {
			// Use the view file set for this view (if set)
			if ($this->view->file !== null)
				$file = $this->view->file;
			else {
				// Auto-load the view file. These have the format of:
				// [controller_name]_[method_name] for all non-index methods
				$file = Loader::fromCamelCase(get_class($this)) .
					($this->action != null && $this->action != "index" ? "_" . strtolower($this->action) : "");
			}
		}
		
		// Render view
		$output = $this->view->fetch($file, $view);
		// Render view in structure
		if ($template != null) {
			$this->structure->set("content", $output);
			$output = $this->structure->fetch($template, $view);
		}
		
		// Create the cache file, if set
		if ($this->cache_for != null)
			Cache::writeCache($this->uri_str, $output, $this->cache_for);
			
		// Output the structure containing the view to standard out
		echo $output;
	}
	
	/**
	 * Initiates a header redirect to the given URI/URL. Automatically prepends
	 * WEBDIR to $uri if $uri is relative (e.g. does not start with a '/' and is
	 * not a url)
	 *
	 * @param string $uri The URI or URL to redirect to. Default is WEBDIR
	 */
	protected static final function redirect($uri=WEBDIR) {
		$parts = parse_url($uri);
		$relative = true;
		if (substr($uri, 0, 1) == "/")
			$relative = false;
		// If not scheme is specified, assume http(s)
		if (!isset($parts['scheme'])) {
			$uri = "http" . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != "off" ? "s" : "") .
				"://" . (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME']) .
				($relative ? WEBDIR : "") . $uri;
		}
		
		header("Location: " . $uri);
		exit;
	}
	
	/**
	 * Sets the default view path for this view and its structure view
	 *
	 * @param string $path The view path to replace the current view path
	 */
	protected final function setDefaultViewPath($path) {
		$this->view->setDefaultView($path);
		$this->structure->setDefaultView($path);
	}
}
?>