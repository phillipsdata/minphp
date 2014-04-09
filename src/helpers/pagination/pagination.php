<?php
/**
 * Provides helper methods for dealing with Page Navigation content.
 *
 * The default implementation assumes the following styles:
 *
 * div.pagination { width: 100%; margin: 0 auto; text-align: center; }
 * div.pagination ul, div.pagination li { display: inline; margin: 0; padding: 0; }
 * div.pagination li.current { font-weight: bold; }
 * 
 * @package minPHP
 * @subpackage minPHP.helpers.pagination
 */
Loader::load(HELPERDIR . "html" . DS . "html.php");

class Pagination extends Html {
	/**
	 * @var string The string to use as the end of line character
	 */
	private $eol = "\n";
	
	/**
	 * @var boolean Whether or not to return output from various pagination methods
	 */
	private $return_output = false;
	
	/**
	 * @var array Format settings
	 */
	private $settings;
	
	/**
	 * @var array All get parameters for this request
	 */
	private $get;
	
	/**
	 * Sets default settings
	 *
	 * @param array $get The GET parameters for the current request
	 * @param array $format Format settings to overwrite default settings with (optional)
	 */
	public function __construct(array $get=array(), array $format=array()) {
		// Load the language for the pagination
		Language::loadLang("pagination", null, dirname(__FILE__) . DS . "language" . DS);
		
		$this->setGet($get);
		
		$this->settings = array(
			// Wrapper to surround the link set
			'wrapper' => array(
				'tag' => "div",
				'attributes' => array('class'=>"pagination")
			),
			'navigation' => array(
				// First page link
				'first' => array(
					'tag' => "li",
					'name' =>  Language::_("Pagination.first_link", true),
					'attributes' => array(),
					'link_attributes' => array(),
					'show' => "if_needed", // options: if_needed, never, always
					'disabled' => "disabled" // class to use if show and not needed
				),
				// Last page link
				'last' => array(
					'tag' => "li",
					'name' =>  Language::_("Pagination.last_link", true),
					'attributes' => array(),
					'link_attributes' => array(),
					'show' => "if_needed", // options: if_needed, never, always
					'disabled' => "disabled" // class to use if show and not needed
				),
				// Next page link
				'next' => array(
					'tag' => "li",
					'name' => Language::_("Pagination.next_link", true),
					'attributes' => array(),
					'link_attributes' => array(),
					'show' => "if_needed", // options: if_needed, never, always
					'disabled' => "disabled" // class to use if show and not needed
				),
				// Previous page link
				'prev' => array(
					'tag' => "li",
					'name' =>  Language::_("Pagination.prev_link", true),
					'attributes' => array(),
					'link_attributes' => array(),
					'show' => "if_needed", // options: if_needed, never, always
					'disabled' => "disabled" // class to use if show and not needed
				),
				// Surround for the set of links
				'surround' => array(
					'tag' => "ul",
					'attributes' => array(),
					'link_attributes' => array()
				),
				// The currently active link
				'current' => array(
					'tag' => "li",
					'attributes' => array('class'=>"current"),
					'link_attributes' => array(),
					'link' => false		// disable linking
				),
				// All numeric links
				'numerical' => array(
					'tag' => "li",
					'attributes' => array(),
					'link_attributes' => array()
				)
			),
			'merge_get' => true, 		// merge get params from URI with those set in 'params'
			'show' => "if_needed",		// options: if_needed, never, always
			'pages_to_show' => 5,		// max number of numerical pages shown in the pagination
			'total_pages' => 0, 		// total number of pages (used instead of total results/result_per_page settings)
			'total_results' => 0,		// total number of results in the pagination set
			'results_per_page' => 1,	// number of result items per page
			'uri' => "",
			'uri_labels' => array(		// tags that will be substituted with their appropriate value
				'page' => "page",
				'per_page' => "per_page"
			),
			'params' => array()			// key => value pairs of additional uri query parameters (if set, overrides $get params)
		);
			
		$this->settings = $this->mergeArrays($this->settings, $format);
	}
	
	/**
	 * Extends one array using another to overwrite existing values. Recursively merges
	 * data.
	 *
	 * @param array $arr1 The array (default) to be merged into
	 * @param array $arr2 The array to merge into $arr1
	 * @return array The merged arrays
	 */
	private function mergeArrays(array $arr1, array $arr2) {

		foreach($arr2 as $key => $value) {
		  if (array_key_exists($key, $arr1) && is_array($value))
			$arr1[$key] = $this->mergeArrays($arr1[$key], $arr2[$key]);
		  else
			$arr1[$key] = $value;
		}	  
		return $arr1;
	}
	
	/**
	 * Set all GET parameters for this pagination instance
	 *
	 * @param array $get An array of GET parameters
	 */
	public function setGet(array $get) {
		// Remove all numeric indexed get parameters, only want key/value pairs
		foreach ($get as $key => $value) {
			// Ensure that the key is both numeric and an integer
			if ((string)(int)$key == $key)
				unset($get[$key]);
		}
		$this->get = $get;
	}
	
	/**
	 * Sets the end of line character to use
	 *
	 * @param string $eol The end of line character to use
	 */
	public function setEol($eol) {
		$this->eol = $this->_($eol, true);
	}	
	
	/**
	 * Sets the format settings
	 *
	 * @param array $format The format settings to overwrite
	 */
	public function setSettings($format) {
		if (is_array($format))
			$this->settings = $this->mergeArrays($this->settings, $format);
	}
	
	/**
	 * Returns whether or not pagination should be shown
	 *
	 * @return boolean True if pagination should be shown, false otherwise
	 */
	public function hasPages() {
		$pages = 0;
		if (isset($this->settings['total_pages']) && $this->settings['total_pages'] > 0)
			$pages = $this->settings['total_pages'];
		else
			$pages = ceil($this->settings['total_results'] / $this->settings['results_per_page']);
			
		if ($this->settings['show'] == "never" || ($pages <= 1 && $this->settings['show'] == "if_needed"))
			return false;
		return true;
	}
	
	/**
	 * Builds the content of the pagination and optionally outputs it.
	 *
	 * @return string The HTML for the pagination, void if output enabled
	 */
	public function build() {
		// Set data to return, because we don't want to echo until we have everything built
		$output = $this->return_output;
		$this->setOutput(true);
		
		// Merge get params with param settings if set to
		if ($this->settings['merge_get'])
			$this->settings['params'] = $this->mergeArrays($this->get, (array)$this->settings['params']);

		if (isset($this->settings['total_pages']) && $this->settings['total_pages'] > 0)
			$pages = $this->settings['total_pages'];
		else
			$pages = ceil($this->settings['total_results'] / $this->settings['results_per_page']);
			
		// Ensure nav should be shown
		if (!$this->hasPages())
			return null;
		
		// Set the wrapper tag
		$html = $this->openTag($this->settings['wrapper']);
		
		// Begin with surround tag
		$html .= $this->openTag($this->settings['navigation']['surround']);
		
		$show = $this->settings['pages_to_show'];
		$per_page = (isset($settings['per_page']) && !empty($settings['per_page'])) ? $settings['per_page'] : $this->settings['results_per_page'];
		$current_page = $this->currentPage();
		
		$page_label = $this->settings['uri_labels']['page'];
		$per_page_label = $this->settings['uri_labels']['per_page'];
		$settings[$page_label] = $current_page;
		$settings[$per_page_label] = $per_page;
		
		if ($pages > 0) {
			if ($this->settings['pages_to_show'] > 0) {
				
				$current_page = min(max(1, $current_page), $pages);
				
				$start = $current_page - floor($show/2);
				$end = $current_page + floor($show/2) - ($show%2 == 0 ? 1 : 0);
				
				if ($start < 1) {
					$start = 1;
					$end = min($pages, $show);
				}
				if ($end > $pages) {
					$end = $pages;
					$start = max($end - $show + 1, 1);
				}
				
				$prev = max($current_page - 1, $start);
				$next = min($current_page + 1, $end);
	
				$prev_needed = $current_page > 1;
				$next_needed = $current_page < $pages;
	
				// build first, prev links, merge with disabled settings if not needed but shown
				if ($this->settings['navigation']['first']['show'] == "always" || ($this->settings['navigation']['first']['show'] == "if_needed" && $prev_needed)) {
					if (!$prev_needed)
						$this->settings['navigation']['first']['attributes']['class'] = (isset($this->settings['navigation']['first']['attributes']['class']) ? $this->settings['navigation']['first']['attributes']['class'] : "") . " " . $this->settings['navigation']['first']['disabled'];
					$html .= $this->createNavItem($this->settings['navigation']['first'], 1);
				}
				if ($this->settings['navigation']['prev']['show'] == "always" || ($this->settings['navigation']['prev']['show'] == "if_needed" && $prev_needed)) {
					if (!$prev_needed)
						$this->settings['navigation']['prev']['attributes']['class'] = (isset($this->settings['navigation']['prev']['attributes']['class']) ? $this->settings['navigation']['prev']['attributes']['class'] : "") . " " . $this->settings['navigation']['prev']['disabled'];
					$html .= $this->createNavItem($this->settings['navigation']['prev'], $prev);
				}

				// build page number links
				for ($i=$start; $i<=$end; $i++) {
					if ($current_page == $i)
						$html .= $this->createNavItem($this->settings['navigation']['current'], $i);
					else
						$html .= $this->createNavItem($this->settings['navigation']['numerical'], $i);
				}
				
				// build next, last links, merge with disabled settings if not needed but shown
				if ($this->settings['navigation']['next']['show'] == "always" || ($this->settings['navigation']['next']['show'] == "if_needed" && $next_needed)) {
					if (!$next_needed)
						$this->settings['navigation']['next']['attributes']['class'] = (isset($this->settings['navigation']['next']['attributes']['class']) ? $this->settings['navigation']['next']['attributes']['class'] : "") . " " . $this->settings['navigation']['next']['disabled'];
					$html .= $this->createNavItem($this->settings['navigation']['next'], $next);
				}
				if ($this->settings['navigation']['last']['show'] == "always" || ($this->settings['navigation']['last']['show'] == "if_needed" && $next_needed)) {
					if (!$next_needed)
						$this->settings['navigation']['last']['attributes']['class'] = (isset($this->settings['navigation']['last']['attributes']['class']) ? $this->settings['navigation']['last']['attributes']['class'] : "") . " " . $this->settings['navigation']['last']['disabled'];
					$html .= $this->createNavItem($this->settings['navigation']['last'], $pages);
				}
			}
		}
		
		// Close surround tag
		$html .= $this->closeTag($this->settings['navigation']['surround']);
		
		// Close the wrapper tag
		$html .= $this->closeTag($this->settings['wrapper']);
		
		// Restore the original output type
		$this->setOutput($output);
		return $this->output($html);
	}
	
	/**
	 * Finds the current page based on the current URI and/or query parameters
	 *
	 * @return int The current page
	 */
	private function currentPage() {
		$page = 1;
		$uri = $this->getUri();
		
		$temp = explode("/", $uri);
		$index = null;
		// Look for the index partition where the page label is located
		foreach ($temp as $i => $value) {
			if ($value == "[" . $this->settings['uri_labels']['page'] . "]") {
				$index = $i;
				$temp = explode("/", $_SERVER['REQUEST_URI']);
				break;
			}
		}
		
		// Parse the page number out of the partition
		if ($index && isset($temp[$index]))
			$page = $temp[$index];
		elseif (isset($this->get[$this->settings['uri_labels']['page']]))
			$page = $this->get[$this->settings['uri_labels']['page']];
		
		return $page;
	}
	
	/**
	 * Creates a page nav item
	 *
	 * @param array $nav_item Navigation settings for a specific nav link
	 * @param string $page The page number or symbol
	 * @return string The HTML for the nav item, void if output enabled
	 */
	private function createNavItem($nav_item, $page) {
		return $this->output($this->openTag($nav_item) . (isset($nav_item['link']) && !$nav_item['link'] ? $page : $this->createLink($nav_item, $page)) . $this->closeTag($nav_item));
	}	
	
	/**
	 * Opens a new tag
	 *
	 * @param array $tag The tag setting to open
	 * @return string The HTML for an open tag, void if output enabled
	 */
	private function openTag($tag) {
		$html = "";
		if (is_array($tag) && isset($tag['tag']))
			$html .= "<" . $this->_($tag['tag'], true) . $this->buildAttributes($tag['attributes']) . ">" . $this->eol;
		return $this->output($html);
	}
	
	/**
	 * Closes a tag
	 *
	 * @param string $tag The tag setting to close
	 * @return string The HTML for a closing tag, void if output enabled
	 */
	private function closeTag($tag) {
		$html = "";
		if (is_array($tag) && isset($tag['tag']))
			$html .= "</" . $this->_($tag['tag'], true) . ">" . $this->eol;
		return $this->output($html);
	}
	
	/**
	 * Creates a new link
	 *
	 * @param array $link The link settings
	 * @param integer $page The page number
	 * @return string The HTML for the <a> tag, void if output enabled
	 */
	private function createLink($link, $page) {
		$html = "";
		if (is_array($link)) {
			$link['link_attributes']['href'] = $this->getPageUri($page);
			$html .= "<a" . $this->buildAttributes($link['link_attributes']) . ">" .
				(isset($link['name']) ? $this->_($link['name'], true) : $this->_($page, true)) .
				"</a>" . $this->eol;
		}
		
		return $this->output($html);
	}
	
	private function getUri() {
		// Build all query params
		$query = null;
		if (is_array($this->settings['params'])) {
			// If settings contain no parameters, use those set by $this->get
			if (empty($this->settings['params']) && is_array($this->get)) {
				foreach ($this->get as $key => $value) {
					if (is_numeric($key))
						continue;
					$this->settings['params'][$key] = $value;
				}
			}
			foreach ($this->settings['params'] as $key => $value) {
				$query .= ($query == null ? "?" : "&") . $key . "=" . $value;
			}
		}

		// Build the URI
		return $this->settings['uri'] . (substr($this->settings['uri'], -1) != "/" ? "/" : "") . $query;
	}
	
	/**
	 * Create the URI for the current page number, replacing any labels as needed.
	 *
	 * @param int $page The page number
	 * @return string The completed URI
	 */
	public function getPageUri($page) {
		$uri = $this->getUri();
		// Replace the page tag with the page value
		return str_replace(array("[" . $this->settings['uri_labels']['page'] . "]", "[" . $this->settings['uri_labels']['per_page'] . "]"), array($page, $this->settings['results_per_page']), $uri);
	}
	
	/**
	 * Set whether to return $output generated by these methods, or to echo it out instead
	 *
	 * @param boolean $return True to return output from these form methods, false to echo results instead 
	 */
	public function setOutput($return) {
		if ($return)
			$this->return_output = true;
		else
			$this->return_output = false;
	}
	
	/**
	 * Handles whether to output or return $html
	 *
	 * @param string $html The HTML to output/return
	 * @return string The HTML given, void if output enabled
	 */	
	private function output($html) {
		if ($this->return_output)
			return $html;
		echo $html;
	}	
}
?>