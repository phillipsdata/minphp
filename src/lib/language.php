<?php
/**
 * Provides a set of static methods to aid in the use of multi-language support.
 * Supports the use of multiple simultaneous languages, including a default
 * (fallback) language. When the definition can not be found in the set of
 * primary keys, the default is used instead.
 *
 * This class makes use of the following Configure class options:
 *
 * Language.default - Defines the default (fallback) language (ISO 639-1/2) e.g. "en_us"
 * Language.allow_pass_through - true/false allows keys without a definition to be passed through
 * 
 * @package minPHP
 * @subpackage minPHP.lib
 */
class Language {

	/**
	 * @var array An associative array containing name of all of the language files loaded
	 * and the language they pertain to
	 */
	private static $lang_files;
	/**
	 * @var array The text for the given language
	 */
	private static $lang_text;
	/**
	 * @var string The current language (ISO 639-1/2) e.g. "en_us"
	 */
	private static $current_language;
	
	
	/**
	 * Alias of Language::getText()
	 * @see Language::getText()
	 *
	 * @param string $lang_key The language key identifier for this requested text
	 * @param boolean $return Whether to return the text or output it
	 * @param mixed $... Values to substitute in the language result. Uses sprintf(). If parameter is an array, only that value is passed to sprintf().
	 */
	public static final function _($lang_key, $return=false) {
		$args = func_get_args();
		return call_user_func_array(array("Language", "getText"), $args);
	}

	/**
	 * Fetches text from the loaded language file.  Will search the preferred
	 * language file first, if not found in there, then will search the default
	 * language file for the $lang_key text.
	 * 
	 * @param string $lang_key The language key identifier for this requested text
	 * @param boolean $return Whether to return the text or output it
	 * @param mixed $... Values to substitute in the language result. Uses sprintf(). If parameter is an array, only that value is passed to sprintf().
	 */
	public static final function getText($lang_key, $return=false) {
		$language = self::$current_language != null ? self::$current_language : Configure::get("Language.default");
		
		$output = "";
		
		// If the text defined exists, use it
		if (isset(self::$lang_text[$language][$lang_key]))
			$output = self::$lang_text[$language][$lang_key];
		// If the text defined did not exist in the set language, look for it
		// in the default language
		elseif (isset(self::$lang_text[Configure::get("Language.default")][$lang_key]))
			$output = self::$lang_text[Configure::get("Language.default")][$lang_key];
		elseif (Configure::get("Language.allow_pass_through"))
			$output = $lang_key;
		
		$argc = func_num_args();
		if ($argc > 2) {
			$args = array_slice(func_get_args(), 2, $argc-1);
			// If printf args are passed as an array use those instead.  This
			// is the case, by default, if Language::_() was used.
			if (is_array($args[0]))
				$args = $args[0];
			array_unshift($args, $output);

			$output = call_user_func_array("sprintf", $args);
		}
		
		if ($return)
			return $output;
		echo $output;
	}
	
	/**
	 * Loads a language file whose properties may then be invoked.
	 * 
	 * @param mixed $lang_file A string as a single language file or array containing a list of language files to load
	 * @param string $language The ISO 639-1/2 language to load the $lang_file for (e.g. en_us), default is "Language.default" config value
	 * @param string $lang_dir The directory from which to load the given language file(s), defaults to LANGDIR
	 */
	public static final function loadLang($lang_file, $language=null, $lang_dir=LANGDIR) {
		if ($language == null)
			$language = self::$current_language;
			
		if (is_array($lang_file)) {
			$num_lang_files = count($lang_file);
			for ($i=0; $i<$num_lang_files; $i++)
				self::loadLang($lang_file[$i], $language, $lang_dir);
			return;
		}
		
		// Check if the language file in this language has already been loaded
		if (isset(self::$lang_files[$lang_dir . $lang_file]) && in_array($language, self::$lang_files[$lang_dir . $lang_file]))
			return;
		
		$load_success = true;
		
		// Fetch $lang from the language file, if it exists
		if (file_exists($lang_dir . $language . DS . $lang_file))
			include_once $lang_dir . $language . DS . $lang_file;
		elseif (file_exists($lang_dir . $language . DS . $lang_file . ".php"))
			include_once $lang_dir . $language . DS . $lang_file . ".php";
		else
			$load_success = false;
			
		if ($load_success) {
			self::$lang_files[$lang_dir . $lang_file][] = $language;
		
			if (isset($lang) && is_array($lang)) {
				
				if (!isset(self::$lang_text[$language]))
					self::$lang_text[$language] = array();
				
				// Set the text for this language
				foreach ($lang as $key => $text)
					self::$lang_text[$language][$key] = $text;
				
				// Load the text for the default language as well so we have that to fall back on
				if ($language != Configure::get("Language.default"))
					self::loadLang($lang_file, Configure::get("Language.default"), $lang_dir);
			}
			// free up memory occupied by the $lang array, since it has already
			// been loaded into the appropriate class variable
			unset($lang);
		}
		// If the language just attemped did not load and this is the was not the
		// default language, then attempt to load the default language
		elseif ($language != Configure::get("Language.default"))
			self::loadLang($lang_file, Configure::get("Language.default"), $lang_dir);
	}
	
	/**
	 * Sets the language to load when not explicitly defined in the requested method
	 *
	 * @param string $language The ISO 639-1/2 language to use (e.g. en_us) for all future requests if not explicitly given to the requested method
	 * @return string The previously set language, null if not previously defined
	 */
	public static final function setLang($language) {
		$prev_lang = self::$current_language;
		
		self::$current_language = $language;
		
		return $prev_lang;
	}	
}
?>