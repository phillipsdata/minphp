<?php
/**
 * Supplies methods useful in verifying and formatting input data. Provides a
 * number of methods to verify whether the input data is formatted correctly.
 * Supports validating both scalar and array data.
 * 
 * @package minPHP
 * @subpackage minPHP.components.input
 */
class Input {
	/**
	 * @var array All errors violated in the Input::validates() method
	 */
	private $errors = array();
	/**
	 * @var array All rules set in Input::setRules()
	 */
	private $rules = array();
	/**
	 * @var boolean Flag whether or not checking should cease
	 */
	private $end_checks = false;
	/**
	 * @var mixed A set of data that this instance is currently validating
	 */
	private $data = null;
	
	/**
	 * Checks if the given string is a valid email address
	 * 
	 * @param string $str The string to test
	 * @param boolean $check_record True to check DNS/MX record
	 * @retrun boolean True if the email is valid, false otherwise
	 */
	public static function isEmail($str, $check_record=true) {
		// Verify that the address is formatted correctly
		if (isset($str) && preg_match("/^[a-z0-9!#$%\*\/?\|^\{\}`~&'\+=_.-]+@[a-z0-9.-]+\.[a-z0-9]{2,10}$/Di", $str, $check)) {
			// Verify that the domain is valid
			if ($check_record) {
				// Append "." to the host name to prevent DNS server from creating the record
				$host = substr(strstr($check[0], '@'), 1) . ".";
				
				if (function_exists("getmxrr") && !getmxrr($host, $mxhosts)) {
					// This will catch DNSs that are not MX
					if (function_exists("checkdnsrr") && !checkdnsrr($host, "ANY"))
						return false;
				}
			}
			return true;
		}
		
		return false;
	}
	
	/**
	 * Checks if the given string is empty or otherwise not set
	 * 
	 * @param string $str The string to test
	 * @return boolean True if the string is empty or not set, false otherwise
	 */
	public static function isEmpty($str) {
		if (!isset($str) || strlen($str) == 0)
			return true;
		return false;
	}
	
	/**
	 * Tests whether the given string meets the requirements to be considered a password
	 * 
	 * @param string $str The string to test
	 * @param int $min_length The minimum length of the string
	 * @param string $type Types include "any", "any_no_space", "alpha_num", "alpha", "num", and "custom"
	 * @param string $custom_regex Used when $type is set to "custom". Does not use $min_length, any length requirement must be included in the regex
	 * @return boolean True if it meets the given requirements, false otherwise
	 */
	public static function isPassword($str, $min_length=6, $type="any", $custom_regex=null) {
		$regex = "";
		
		switch ($type) {
			default:
			case "any":
				$regex = "/.{" . $min_length . ",}/i";
				break;
			case "any_no_space":
				$regex = "/^[\S]{" . $min_length . ",}$/Di";
				break;
			case "alpha_num":
				$regex = "/^[a-z0-9]{" . $min_length . ",}$/Di";
				break;
			case "alpha":
				$regex = "/^[a-z]{" . $min_length . ",}$/Di";
				break;
			case "num":
				$regex = "/^[0-9]{" . $min_length . ",}$/Di";
				break;
			case "custom":
				$regex = $custom_regex;
				break;
		}
		return preg_match($regex, $str);
	}
	
	/**
	 * Tests whether the given string is considered a valid date suitable to strtotime()
	 *
	 * @param string $str The string to test
	 * @param mixed $min The minimum acceptable date (string) or unix time stamp (int)
	 * @param mixed $min The maximum acceptable date (string) or unix time stamp (int)
	 * @return boolean True if $str is a valid date, false otherwise
	 */
	public static function isDate($str, $min=null, $max=null) {
		if (isset($str)) {
			// Convert to UNIX time
			$time = $str;
			if (!is_numeric($str))
				$time = strtotime($str);
			
			// Ensure valid time
			if ($time === false || $time == -1)
				return false;
			
			// Check range
			if ($min !== null && (!is_numeric($min) ? $min = strtotime($min) : true) && $time < $min)
				return false;
			if ($max !== null && (!is_numeric($max) ? $max = strtotime($max) : true) && $time > $max)
				return false;
			
			return true;
		}
		return false;
	}
	
	/**
	 * Tests wether the given string satisfies the given regular expression
	 *
	 * @param string $str The string to test
	 * @param string $regex The regular expression to satisfy
	 * @return boolean True when the string passes the regex, false otherwise
	 */
	public static function matches($str, $regex) {
		return preg_match($regex, $str);
	}
	
	/**
	 * Tests how the given values compare
	 *
	 * @param mixed $a The value to compare
	 * @param string $op The comparison operator: >, <, >=, <=, ==, ===, !=, !==
	 * @param mixed $b The value to compare against
	 * @return boolean True if $a validates $op against $b, false otherwise
	 * @throws Exception Thrown when an unrecognized operator, $op, is given
	 */
	public static function compares($a, $op, $b) {
		switch ($op) {
			case ">":
				return $a > $b;
			case "<":
				return $a < $b;
			case ">=":
				return $a >= $b;
			case "<=":
				return $a <= $b;
			case "==":
				return $a == $b;
			case "===":
				return $a === $b;
			case "!=":
				return $a != $b;
			case "!==":
				return $a !== $b;
			default:
				throw new Exception("Unrecognized operator: " . $op);
		}
	}
	
	/**
	 * Tests that $val is between $min and $max
	 *
	 * @param mixed $val The value to compare
	 * @param mixed $min The lower value to compare against
	 * @param mixed $max The higher value to compare against
	 * @param boolean $inclusive Set to false if $val must be strictly between $min and $max
	 * @return boolean True if $val is between $min and $max, false otherwise
	 */
	public static function between($val, $min, $max, $inclusive=true) {
		if ($inclusive)
			return $val >= $min && $val <= $max;
		return $val > $min && $val < $max;
	}
	
	/**
	 * Test whether $str is at least $length bytes
	 * 
	 * @param string $str The string to check
	 * @param int $length The number of bytes required in $str
	 * @return boolean True if $str is at least $length bytes
	 */
	public static function minLength($str, $length) {
		return strlen($str) >= $length;
	}

	/**
	 * Test whether $str is no more than $length bytes
	 * 
	 * @param string $str The string to check
	 * @param int $length The number of bytes allowed in $str
	 * @return boolean True if $str is no more than $length bytes
	 */	
	public static function maxLength($str, $length) {
		return strlen($str) <= $length;
	}
	
	/**
	 * Test whether $str is between $min_length and $max_length
	 *
	 * @param string $str The string to check
	 * @param int $min_length The number of bytes required in $str
	 * @param int $max_length The number of bytes allowed in $str
	 * @return boolean True if $str is between $min_length and $max_length
	 */
	public static function betweenLength($str, $min_length, $max_length) {
		return self::minLength($str, $min_length) && self::maxLength($str, $max_length);
	}
	
	/**
	 * Set rules, overriding any existing rules set and empting any existing errors
	 *
	 * @param array $rules A multi-deminsional array, where the 1st dimension is the index value of the data given to Input::validates()
	 * @see Input::validates()
	 */
	public function setRules($rules) {
		$this->rules = $rules;
		$this->errors = array();
	}
	
	/**
	 * Invokes Input::validateRule() to process the rule against the given value.
	 * This method formatted for use by array_walk_recusrive to process elements
	 * recusively.
	 *
	 * @param mixed $value The value to evaluate
	 * @param string $key The most immediate key to the given value
	 * @param array $var An array containing the full string index of the rule to evaluate ('index') and the complete rule ('rule')
	 * @param int $max_depth The maximum depth to travel
	 * @param int $cur_depth The current depth traveled
	 * @param array $path A list of all array indexes encountered
	 */
	public function processValidation(&$value, $key, $var, $max_depth=null, $cur_depth=0, $path=array()) {
		
		// Find the key at the current depth
		$index = array_key_exists($cur_depth, $var['raw_index']) ? $var['raw_index'][$cur_depth] : "";
		
		if ($cur_depth >= $max_depth && ($key == $index || is_numeric($key)))
			$this->validateRule($var['index'], $var['rule'], $value, $key, $path);
	}
	
	/**
	 * Validates all set rules using the given data, sets any error messages to Input::$errors
	 * Each ruleset attached to a field can have the following indices: rule, message, negate, last, final, pre_format, post_format
	 *
	 * pre_format and post_format accept a typical PHP callback parameter, post_format will only be called if the rule passes validation
	 * 
	 * @param array $data An array of data such as POST
	 * @return boolean true if all rules pass, false if any rule is broken
	 * @see Input::errors()
	 */
	public function validates(&$data) {
		$this->end_checks = false;
		$this->data = $data;
		
		if (is_array($this->rules) && is_array($data)) {
			// Test each rule
			foreach ($this->rules as $index => $rule) {
				
				// Validate array rules
				if (strpos($index, "[") !== false) {
					$depth = substr_count($index, "[");
					
					$field = array();
					// Turn rule index into array
					parse_str($index, $field);
					
					// Convert $index string into an array where each index
					// represents a depth and each value represents the key
					$raw_index = explode("[", $index);
					foreach ($raw_index as &$key)
						$key = trim($key, "]");
					$depth = count($raw_index)-1;
					
					// Extract the primary index
					$index = key($field);
					
					// Ensure final element of $field is null
					$this->clearLeaves($field);
					
					$val_exists = true;
					// If the value doesn't exist, create it temporarily so the rule can be evaluated									
					if (!$this->pathSet($data, $field)) {
						$orig_data = $data;
						$data = $field; // $field makes a perfect substitute, it's already null
						$val_exists = false;
					}

					// Search recursively through the array for the element to be evaluated and attempt to validate it
					$this->array_walk_recursive($data[$index], array($this, "processValidation"), array("index"=>$index, "raw_index"=>$raw_index, "rule"=>$rule), $depth);
					
					// Destroy the temporary value created in order to validate rules
					if (!$val_exists)
						$data = $orig_data;
				}
				// Validate scalar rules
				else {
					$val_exists = true;
					if (!array_key_exists($index, $data))
						$val_exists = false;
					
					$this->validateRule($index, $rule, $data[$index], $index);
					if (!$val_exists)
						unset($data[$index]);
				}	
				if ($this->end_checks)
					break;
			}
		}
		
		$this->data = null;
		
		if (empty($this->errors))
			return true; // no rule has been broken
		return false; // rules have been broken
	}
	
	/**
	 * Sets the given errors into the object, overriding existing errors (if any)
	 *
	 * @param array $errors An array of errors as returned by Input::errors()
	 * @see Input::errors()
	 */
	public function setErrors(array $errors) {
		$this->errors = $errors;
	}
	
	/**
	 * Return all errors
	 *
	 * @return mixed An array of error messages indexed as their field name, boolean false if no errors set
	 */
	public function errors() {
		if (empty($this->errors))
			return false;
		
		return $this->errors;
	}
	
	/**
	 * Format Data from Input::validates() with the given $callback
	 * 
	 * @param mixed $callback A string whose function accepts a single parameter,
	 * or an array whose format is that of a PHP callback with the addition of optional parameters
	 * @param mixed $data The data to be formatted
	 * @param string $key The most immediate key to the given value
	 * @param array $path A list of all array indexes encountered
	 * @param return mixed The result returned by the callback
	 */
	private function formatData($callback, $data, $key, $path) {
		$params = array();
		
		if (is_array($callback)) {
			$method = array_shift($callback);
			$params = $callback;
		}
		else
			$method = $callback;
		
		$this->replaceLinkedParams($params, $path);
		
		// Push $data onto the list of parameters
		array_unshift($params, $data);
		
		return call_user_func_array($method, $params);
	}
	
	/**
	 * Replaces all linked params in rules identified by an array with an index of '_linked'.
	 *
	 * @param array $params An array of paramters to pass to the callback, possibly containing linked params
	 * @param array $path A list of all array indexes encountered
	 */
	private function replaceLinkedParams(&$params, $path) {
		// Find all numeric paths for the current rule path, these are used
		// for substitution in the _linked field for all blank indexes
		foreach ($path as $index) {
			if (is_int($index))
				$numeric_paths[] = $index;
		}
		
		foreach ($params as &$param) {
			// The number of blank array indexes from the _linked rule value
			$blank = 0;
			// If the parameter given is linked, find the value of the linked field
			if (is_array($param) && isset($param['_linked'])) {
				$data_set = $this->data;
				
				$index = $param['_linked'];
				// default param to null, just incase it doesn't exist
				$param = null;
				
				// Construct an array of all index levels for the _linked field
				$raw_index = explode("[", $index);
				foreach ($raw_index as &$index) {
					$index = trim($index, "]");
					
					// If any index is empty, try to substitute it for the current
					// rule's path
					if ($index == "" && array_key_exists($blank, $numeric_paths))
						$index = $numeric_paths[$blank++];

					if (!array_key_exists($index, $data_set))
						break;
					$data_set =& $data_set[$index];
				}

				$param = $data_set;
				unset($data_set);
			}
		}
	}
	
	/**
	 * Validate the rule against the given index and value, sets any errors into this object's $errors class variable
	 * 
	 * @param string $index The index set for this rule
	 * @param array $rule the Rule to validate against
	 * @param mixed $value The value given by the data element to which the rule is applied
	 * @param string $key The most immediate key to the given value
	 * @param array $path A list of all array indexes encountered
	 */
	private function validateRule($index, $rule, &$value, $key, $path=array()) {
		// Cast the rule set into an array of rules for this index
		if (isset($rule['rule']))
			$rule = array($rule);

		// Loop through each rule set for this index
		foreach ($rule as $type => $rule_set) {
			
			// Ensure that we are allowed to validate this rule, even if the value is not set
			if (!isset($value) && isset($rule_set['if_set']) && $rule_set['if_set'])
				continue;
			
			if (is_array($rule_set['rule'])) 
				$method = array_shift($rule_set['rule']);
			else {
				$method = $rule_set['rule'];
				$rule_set['rule'] = array();
			}
			
			// Format the data before running the evaluation
			if (isset($rule_set['pre_format']))
				$value = $this->formatData($rule_set['pre_format'], $value, $key, $path);
			
			// Push the $data[$index] value onto the array of parameters to be sent to the method governing the given rule
			array_unshift($rule_set['rule'], $value);
			
			$this->replaceLinkedParams($rule_set['rule'], $path);
			
			// Call the rule given, which may be a callback or a method within the scope of this class
			if (is_string($method)) {
				// If the method doesn't exist in this class, assume it is a global PHP function
				if (method_exists($this, $method))
					$method = array($this, $method);
			}
			
			// Process boolean rules (true / false)
			if (is_bool($method))
				$response = !$method;
			// Process callback rules
			else
				$response = !call_user_func_array($method, $rule_set['rule']);

			// A response is considered an error if it returns false, so by default we negate
			// responses. If the rule set is configured to negate responses then we look for a 'true' response instead
			if ((isset($rule_set['negate']) && $rule_set['negate'] && !$response) || ((!isset($rule_set['negate']) || !$rule_set['negate']) && $response)) {
				// If the rule is apart of a larger array set the full path to avoid overwriting other errors
				$error_key = $index;
				foreach ($path as $path_value)
					$error_key .= "[" . $path_value . "]";
				
				$this->errors[$error_key][$type] = (isset($rule_set['message']) ? $rule_set['message'] : null);
				
				// If this rule is set as the last to evaluate for this field stop checks
				if (isset($rule_set['last']) && $rule_set['last'])
					break;
				// If this rule is set as the final to evaluate for this set of checks stop all checks
				if (isset($rule_set['final']) && $rule_set['final']) {
					$this->end_checks = true;
					break;
				}
			}
			
			// Format the data after running the evaluation
			if (isset($rule_set['post_format']))
				$value = $this->formatData($rule_set['post_format'], $value, $key, $path);
			
		}
	}
	
	/**
	 * Emulates the standard array_walk_recursive function, with the added functionality
	 * of passing array elements through when no further recusion can be made
	 *
	 * @param array $input The input array to recurse through
	 * @param callback $callback The callback function to apply to each member of $input
	 * @param array $params An array of additional parameters to be passed to the callback
	 * @param int $max_depth The maximum permitted depth to recurse
	 * @param int $cur_depth The current depth
	 * @param array $path A list of all array indexes encountered
	 * @return boolean False if the input is no longer an array and therefore can not be recursed through, true otherwise
	 */
	private static function array_walk_recursive(&$input, $callback, $params=null, $max_depth=null, $cur_depth=0, $path=array()) {
		
		if (!is_array($input))
			return false;
		
		// Recursed as far down as permitted
		if ($max_depth > 0 && $cur_depth >= $max_depth)
			return false;
		
		$cur_depth++;
		
		foreach ($input as $key => $value) {
			$cur_key = array_key_exists($cur_depth, $params['raw_index']) ? $params['raw_index'][$cur_depth] : "";
			
			// If the key doesn't match for this current depth, we're not supposed to evaluate it
			// so continue to the next element
			if ($key != $cur_key && $cur_key != "")
				continue;
			
			$path[] = $key;
			
			// Invoke the callback, emulating the array_walk_recursive function
			if (!is_array($input[$key]) || $cur_depth >= $max_depth)
				call_user_func_array($callback, array(&$input[$key], $key, $params, $max_depth, $cur_depth, $path));
			
			if (is_array($input[$key])) {
				// Recurse deeper
				self::array_walk_recursive($input[$key], $callback, $params, $max_depth, $cur_depth, $path);
			}
			
			// Make room for the next index at this level
			array_pop($path);
		}
		
		// Finished all indexes at this depth, bubble back up
		return true;
	}

	/**
	 * Recursively evaluates whether the path defined by $field is defined in $data
	 *
	 * @param mixed $data An array of data, or a scalar value if the array has been fully traversed
	 * @param mixed $field An array defining a path that $data should define, scalar if the array has been fully traversed
	 * @return boolean True if $field is fully defined in $data, false otherwise.
	 */
	private static function pathSet($data, $field) {
		if (is_array($data) && is_array($field)) {
			foreach ($data as $data_key => $data_value) {
				foreach ($field as $field_key => $field_value) {
					if ($field_key == $data_key)
						return self::pathSet($data_value, $field_value);
				}
			}
			return false;
		}
		return true;
	}
	
	/**
	 * Recursively sets all leaf elements of the given array to null
	 *
	 * @param mixed $data An array of data whose leaves to set to null, or a scalar value if the array has been fully traversed
	 */
	private static function clearLeaves(&$data) {
		if (is_array($data)) {
			foreach ($data as $key => $value) {
				self::clearLeaves($data[$key]);
			}
		}
		else
			$data = null;
	}
}
?>