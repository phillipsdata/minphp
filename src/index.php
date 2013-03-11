<?php
/* SVN FILE: $Id$ */
/**
 * This file transfers control over to the dispatcher which will invoke the
 * appropriate controller. We also handle any exceptions that were not handled
 * elsewhere in the application, so we can end gracefully.
 *
 * @package       minPHP
 * @version       $Revision$
 * @modifiedby    $LastChangedBy$
 * @lastmodified  $Date$
 */

$start = microtime(true);

try {
	include(dirname(__FILE__) . "/lib/init.php");
	
	// Dispatch the Web request
	if (!empty($_SERVER['REQUEST_URI']))
		Dispatcher::dispatch($_SERVER['REQUEST_URI']);
	// Dispatch the CLI request
	else
		Dispatcher::dispatchCli($argv);
}
catch (Exception $e) {
	try {
		// Attempt to raise any error, gracefully
		Dispatcher::raiseError($e);
	}
	catch (Exception $e) {
		if (Configure::get("System.debug"))
			echo $e->getMessage() . " on line <strong>" . $e->getLine() .
				"</strong> in <strong>" . $e->getFile() . "</strong>\n" .
				"<br />Printing Stack Trace:<br />" . nl2br($e->getTraceAsString());
		else
			echo $e->getMessage();
	}
}

$end = microtime(true);

// Display rendering time if benchmarking is enabled
if (Configure::get("System.benchmark"))
	echo "execution time: " . ($end-$start) . " seconds";
?>