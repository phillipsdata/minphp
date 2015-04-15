<?php
/**
 * Performs all of the bootstrap operations necessary to begin execution.
 * 
 * Includes all of the core library files as well as sets global constants used
 * throughout the app.
 *
 * @package minPHP
 * @subpackage minPHP.lib
 */
 
/**
 * Sets the default error reporting level (everything).  This value should
 * remain as-is.  If the error level needs to be changed it should be done so
 * using Configure::errorReporting(), but only after Configure has been
 * initialized. Simply uncomment the line in /config/core.php
 */
error_reporting(-1);

/**
 * Sets the version of minPHP in use.  [Major].[Minor].[Revision]
 *
 * @deprecated since 1.0.0
 */
define("MINPHP_VERSION", "1.0.0");

/**
 * Sets the directory separator used throughout the application. DO NOT use this
 * constant when setting URI paths. THE ONLY VALID directory separator in URIs
 * is / (forward-slash).
 */
define("DS", DIRECTORY_SEPARATOR);

/**
 * Sets the root web directory, which is the absolute path to your web directory
 * (e.g. where index.php appears).
 */
define("ROOTWEBDIR", realpath(dirname(__FILE__) . DS . "..") . DS);

/**
 * If you have htaccess running that redirects requests to index.php this must
 * be set to true.  If set to false and no htaccess is present, URIs have the
 * form of /index.php/controller/action/param1/.../paramN
 */
define("HTACCESS", file_exists(ROOTWEBDIR . ".htaccess"));

/**
 * Sets the web directory.  This is the relative path to your web directory, and
 * may include index.php if HTACCESS is set to false.
 */
$script = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : (isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : null);
define("WEBDIR", ($webdir = (!HTACCESS ? $script : (($path = dirname($script)) == "/" || $path == DS ? "" : $path)) . "/") == ROOTWEBDIR ? "/" : $webdir);
unset($script, $webdir, $path);

/**
 * The name of the application directory where all models, views, and
 * controllers are placed.  Must end with a trailing directory separator
 */
define("APPDIR", "app" . DS);

/**
 * Sets the absolute path to the cache directory. Must be writable by the web
 * server to use caching.
 */
define("CACHEDIR", ROOTWEBDIR . "cache" . DS);

/**
 * Absolute path to the lib directory.
 */
define("LIBDIR", ROOTWEBDIR . "lib" . DS);

/**
 * Absolute path to the models directory, where all models are stored.
 */
define("MODELDIR", ROOTWEBDIR . APPDIR . "models" . DS);

/**
 * Absolute path to the views directory, where all views are stored.
 */
define("VIEWDIR", ROOTWEBDIR . APPDIR . "views" . DS);

/**
 * Absolute path to the controllers directory, where all controllers are stored.
 */
define("CONTROLLERDIR", ROOTWEBDIR . APPDIR . "controllers" . DS);

/**
 * Absolute path to the componenets directory, where all components are stored.
 */
define("COMPONENTDIR", ROOTWEBDIR . "components" . DS);

/**
 * Absolute path to the lib config directory, where config files are stored.
 */
define("CONFIGDIR", ROOTWEBDIR . "config" . DS);

/**
 * Absolute path to the helpers directory, where helper files are stored.
 */
define("HELPERDIR", ROOTWEBDIR . "helpers" . DS);

/**
 * Absolute path to the language directory, where all language files are stored.
 */
define("LANGDIR", ROOTWEBDIR . "language" . DS);

/**
 * Absolute path to the plugins directory, where plugins are stored.
 */
define("PLUGINDIR", ROOTWEBDIR . "plugins" . DS);

/**
 * Absolute path to the vendors directory, where vendor libraries are stored.
 */
define("VENDORDIR", dirname(dirname(__DIR__)) . DS . "vendor" . DS);


// Include core libraries
include_once LIBDIR . "autoload.php";
include_once LIBDIR . "stdlib.php";
// Load core configuration
Configure::load("core");
