<?php
Loader::load(HELPERDIR . "html" . DS . "html.php");

/**
 * Javascript Helper, requires Html Helper
 *
 * Facilitates rendering/loading javascript into a view
 * 
 * @package minPHP
 * @subpackage minPHP.helpers.javascript
 */
class Javascript extends Html {
	/**
	 * @var array A multi-dimensional array of locations and their respective javascript files
	 */
	private $js_files = array();
	/**
	 * @var array An array of inline javascript blocks
	 */
	private $js_inline = array();
	
	/**
	 * @var string The default path to use for javascript files
	 */
	private $default_path;
	
	/**
	 * Constructs a Javascript Helper
	 *
	 * @param string $default_path The default path to use for javascript files
	 */
	public function __construct($default_path=null) {
		$this->setDefaultPath($default_path);
	}
	
	/**
	 * Sets the default path to use for all javascript requests
	 * 
	 * @param string $default_path The default path to use for javascript files
	 * @return string The previous default path
	 */
	public function setDefaultPath($default_path) {
		$temp = $this->default_path;
		$this->default_path = $default_path;
		return $temp;
	}
	
	/**
	 * Return the HTML used to create the script tags and load the set javascript
	 * 
	 * @param string $location The location where the script resides (generally "head" or "body")
	 * @return string The HTML used to load all of the set javascript files
	 */
	public function getFiles($location) {
		$html = "";
		if (isset($this->js_files[$location])) {
			$num_docs = count($this->js_files[$location]);
			for ($i=0; $i<$num_docs; $i++)
				$html .= $this->addCondition("<script type=\"text/javascript\" src=\"" . $this->_($this->js_files[$location][$i]['file'], true) . "\"></script>", $this->js_files[$location][$i]['condition'], $this->js_files[$location][$i]['hidden']) . "\n";
		}
		return $html;
	}
	
	/**
	 * Return the HTML used to create the inline javascript
	 *
	 * @return string The HTML used to load all of the set inline javascript
	 */
	public function getInline() {
		$html = "";
		
		$num_docs = count($this->js_inline);
		
		for ($i=0; $i<$num_docs; $i++)
			$html .= $this->addCondition("<script type=\"text/javascript\">" . $this->js_inline[$i]['data'] . "</script>", $this->js_inline[$i]['condition'], $this->js_inline[$i]['hidden']) . "\n";
		return $html;
	}
	
	/**
	 * Sets the given javascript file into the structure view
	 *
	 * @param string $file The name of the javascript file to load
	 * @param string $location The location to set the given file (genearlly "head" or "body")
	 * @param string $path The path to the javascript file, if null will use the default path set in the constructor
	 * @return Javascript Returns the instance of this object
	 */
	public function setFile($file, $location="head", $path=null, $condition=null, $hidden=true) {
		if ($path == null)
			$path = $this->default_path;
		
		$this->js_files[$location][] = array("file"=>$path . $file, "condition"=>$condition, "hidden"=>$hidden);
		
		return $this;
	}
	
	/**
	 * Sets the given javascript data to be appended to the list of javascript data.
	 *
	 * @param string $data The javascript data to set
	 * @return Javascript Returns the instance of this object
	 */
	public function setInline($data, $condition=null, $hidden=true) {
		$this->js_inline[] = array("data"=>$data, "condition"=>$condition, "hidden"=>$hidden);
		
		return $this;
	}
	
	/**
	 * Unset all files that are currently set
	 *
	 * @return Javascript Returns the instance of this object
	 */
	public function unsetFiles() {
		$this->js_files = array();
		
		return $this;
	}
	
	/**
	 * Unset all inline data that is currently set
	 *
	 * @return Javascript Returns the instance of this object
	 */
	public function unsetInline() {
		$this->js_inline = array();
		
		return $this;
	}
}
?>