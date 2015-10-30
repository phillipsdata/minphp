<?php
/**
 * Initializes all database profiles, and sets the desired database profile
 * to be the active profile.
 *
 * @package minPHP
 */

// Lazy connecting will only establish a connection to the database if one is
// needed. If disabled, a connection will be attempted as soon as a Model is
// requested and a Database profile exists. Some models may not require a DB
// connection so it is recommended to leave this enabled.
Configure::set("Database.lazy_connecting", true);
Configure::set("Database.fetch_mode", PDO::FETCH_OBJ);
Configure::set("Database.reuse_connection", true);

// Default database profile
$default = array(
	"driver" => "mysql",
	"host"	=> "localhost",
	//"port" => "8889",
	"database" => "minphp",
	"user" => "root",
	"pass" => "root",
	"persistent" => false,
	"charset_query"=>"SET NAMES 'utf8'",
	"options" => array() // an array of PDO specific options for this connection
	);

#
# TODO: define more database profiles here
#


// Assign the desired profile based on the current server name. This gives the
// option of having the same codebase run in separate environments (e.g. dev and
// live servers) without making any changes.
$server = (isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : "");
switch ($server) {
	default:
		Configure::set("Database.profile", $default);
		break;
}

unset($default);
unset($server);
