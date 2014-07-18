<?php
Loader::load(COMPONENTDIR . "record" . DS . "record.php");
Configure::load("session");

/**
 * A database powered Session driver. Requires the Record component
 *
 * @package minPHP
 * @subpage minPHP.components.session
 */
class Session {
	private $Record;
	private $ttl;
	private $tbl;
	private $tblid;
	private $tblexpire;
	private $tblvalue;
	private $csid;
	private $sid;
	
	private static $instances = 0;

	/**
	 * Initialize the Session
	 */
	public function __construct() {
		$this->Record = new Record();
		$this->Record->setFetchMode(PDO::FETCH_OBJ);
		
		$this->sessionSet(
			Configure::get("Session.ttl"),
			Configure::get("Session.tbl"),
			Configure::get("Session.tbl_id"),
			Configure::get("Session.tbl_exp"),
			Configure::get("Session.tbl_val"),
			Configure::get("Session.session_name"),
			Configure::get("Session.session_httponly")
		);
	}
	
	/**
	 * Clean up any loose ends
	 */
	public function __destruct() {
		// Write and close the session (if not already handled)
		if (--Session::$instances == 0)
			session_write_close();
	}

	/**
	 *  Return the session ID
	 *  
	 *  @return string The session ID
	 */
	public function getSid() {
		return $this->sid;
	}

	/**
	 * Read Session information for the given index
	 *
	 * @param string $name The name of the index to read
	 * @return mixed The value stored in $name of the session, or an empty string.
	 */
	public function read($name) {
		if (isset($_SESSION[$name]))
			return $_SESSION[$name];
		return "";
	}

	/**
	 * Writes the given session information to the given index
	 *
	 * @param string $name The index to write to
	 * @param mixed $value The value to write
	 */
	public function write($name, $value) {
		$_SESSION[$name] = $value;
	}
	
	/**
	 * Unsets the value of a given session variable, or the entire session array
	 * of all values
	 *
	 * @param string $name The session variable to unset
	 */
	public function clear($name=null) {
		if ($name)
			unset($_SESSION[$name]);
		else {
			foreach ($_SESSION as $key => $value)
				unset($_SESSION[$key]);
		}
	}
	
	/**
	 * Set the session cookie
	 *
	 * @param string $path The path for this cookie, default is the current URI
	 * @param string $domain The domain that the cookie is available to, default is the current domain
	 * @param boolean $secure Whether or not the cookie should be transmitted over a secure connection from the client
	 * @param boolean $httponly Whether or not the cookie should be flagged for HTTP only
	 */
	public function setSessionCookie($path="", $domain="", $secure=false, $httponly=false) {
		if (version_compare(phpversion(), "5.2.0", ">="))
			setcookie(Configure::get("Session.cookie_name"), $this->getSid(), time()+Configure::get("Session.cookie_ttl"), $path, $domain, $secure, $httponly);
		else
			setcookie(Configure::get("Session.cookie_name"), $this->getSid(), time()+Configure::get("Session.cookie_ttl"), $path, $domain, $secure);
	}
	
	/**
	 * Updates the session cookie expiration date so that it remains active without expiring
	 *
	 * @param string $path The path for this cookie, default is the current URI
	 * @param string $domain The domain that the cookie is available to, default is the current domain
	 * @param boolean $secure Whether or not the cookie should be transmitted over a secure connection from the client
	 * @param boolean $httponly Whether or not the cookie should be flagged for HTTP only
	 */
	public function keepAliveSessionCookie($path="", $domain="", $secure=false, $httponly=false) {
		if (isset($_COOKIE[Configure::get("Session.cookie_name")]))
			$this->setSessionCookie($path, $domain, $secure, $httponly);
	}
	
	/**
	 * Deletes the session cookie
	 *
	 * @param string $path The path for this cookie, default is the current URI
	 * @param string $domain The domain that the cookie is available to, default is the current domain
	 * @param boolean $secure Whether or not the cookie should be transmitted over a secure connection from the client
	 */
	public function clearSessionCookie($path="", $domain="", $secure=false) {
		if (isset($_COOKIE[Configure::get("Session.cookie_name")]))
			setcookie(Configure::get("Session.cookie_name"), "", time()-Configure::get("Session.cookie_ttl"), $path, $domain, $secure);
	}

	/**
	 * Set session handler callback methods and start the session
	 *
	 * @param int $ttl Time to Live (seconds)
	 * @param string $tbl Name of the session table
	 * @param string $tblid Name of the session ID field
	 * @param string $tblexpire Name of the session expire date field
	 * @param string $tblvalue Name of the session value field
	 * @param boolean $httponly Whether or not the cookie should be flagged for HTTP only
	 */
	private function sessionSet($ttl, $tbl, $tblid, $tblexpire, $tblvalue, $session_name, $httponly) {
		$this->ttl = $ttl;
		$this->tbl = $tbl;
		$this->tblid = $tblid;
		$this->tblexpire = $tblexpire;
		$this->tblvalue = $tblvalue;

		if (Session::$instances == 0) {
			
			// Ensure session is HTTP Only
			if (version_compare(phpversion(), "5.2.0", ">=")) {
				$session_params = session_get_cookie_params();
				session_set_cookie_params($session_params['lifetime'], $session_params['path'], $session_params['domain'], $session_params['secure'], $httponly);
				unset($session_params);
			}
			
			session_name($session_name);
			
			session_set_save_handler(
				array(&$this, "sessionOpen"),
				array(&$this, "sessionClose"),
				array(&$this, "sessionSelect"),
				array(&$this, "sessionWrite"),
				array(&$this, "sessionDestroy"),
				array(&$this, "sessionGarbageCollect")
			);
			
			// If a cookie is available, attempt to use that session and reset
			// the ttl to use the cookie ttl, but only if we don't have a current session cookie as well
			if (isset($_COOKIE[Configure::get("Session.cookie_name")]) && !isset($_COOKIE[session_name()])) {
				if ($this->setKeepAlive($_COOKIE[Configure::get("Session.cookie_name")])) {
					$this->setCsid($_COOKIE[Configure::get("Session.cookie_name")]);
					$this->ttl = Configure::get("Session.cookie_ttl");				
				}
			}
			elseif (isset($_COOKIE[Configure::get("Session.cookie_name")]) && isset($_COOKIE[session_name()]) && $_COOKIE[Configure::get("Session.cookie_name")] == $_COOKIE[session_name()]) {
				$this->ttl = Configure::get("Session.cookie_ttl");	
			}
			
			// Start the session
			session_start();
		}
		Session::$instances++;
	}

	/**
	 *  Sets the cookie session ID
	 *
	 *  @param string $csid The cookie session ID
	 */
	private function setCsid($csid) {
		$this->csid = $csid;
	}

	/**
	 *  Reawake the session using the given cookie session id
	 *  
	 *  @param string $cisd The cookie session ID
	 */
	private function setKeepAlive($csid) {
		$row = $this->Record->select($this->tblvalue)->from($this->tbl)->
			where($this->tblid, "=", $csid)->where($this->tblexpire, ">", date("Y-m-d H:i:s"))->fetch();
		
		if ($row) {
			// Set the session ID to that from our cookie so when we start
			// the session, PHP will pick up the old session automatically.
			session_id($csid);
			return true;
		}
		return false;
	}

	/**
	 *  Open the given session. Not implemented, included only for compatibility
	 *
	 *  @param string $session_path The path to the session
	 *  @param string $session_name The name of the session
	 */
	private function sessionOpen($session_path, $session_name) {
	
	}

	/**
	 * Close a session. Not implemented, included only for campaitibility
	 * 
	 * @return boolean True, always
	 */
	private function sessionClose() {
		return true;
	}

	/**
	 * Reads the session data from the database
	 * 
	 * @param int $sid Session ID
	 * @return string
	 */
	private function sessionSelect($sid) {
		//  We need to use the sid set so we can write a cookie if needed
		$this->sid = $sid;

		$row = $this->Record->select($this->tblvalue)->from($this->tbl)->
			where($this->tblid, "=", $this->sid)->
			where($this->tblexpire, ">", date("Y-m-d H:i:s"))->fetch();
		
		if ($row)
			return $row->{$this->tblvalue};

		return null;
	}

	/**
	 * Writes the session data to the database.
	 * If that SID already exists, then the existing data will be updated.
	 *
	 * @param string $sid The session ID
	 * @param string $value The value to write to the session
	 */
	private function sessionWrite($sid, $value) {
		//  We need to use the sid set so we can write a cookie if needed
		$this->sid = $sid;
		
		$expiration = date("Y-m-d H:i:s", time() + $this->ttl);

		$this->Record->duplicate($this->tblexpire, "=", $expiration)->
			duplicate($this->tblvalue, "=", $value)->
			insert($this->tbl, array($this->tblid => $sid, $this->tblexpire => $expiration, $this->tblvalue => $value));
	}

	/**
	 * Deletes all session information for the given session ID
	 *
	 * @param string $sid The session ID
	 */
	private function sessionDestroy($sid) {
		$this->Record->from($this->tbl)->where($this->tblid, "=", $sid)->delete();
	}

	/**
	 * Deletes all sessions that have expired.
	 *
	 * @param int $lifetime TTL of the session
	 */
	private function sessionGarbageCollect($lifetime) {
		$this->Record->from($this->tbl)->
			where($this->tblexpire, "<", date("Y-m-d H:i:s", time() - $lifetime))->delete();
		return $this->Record->affectedRows();
	}
}
?>