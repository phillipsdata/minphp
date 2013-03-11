<?php
// Define the path to the System Under Test
define("SUT_PATH", dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . "src" . DIRECTORY_SEPARATOR);
// Set the SUT Path as an include path to ease loading
set_include_path(get_include_path() . PATH_SEPARATOR . SUT_PATH);

require_once "lib" . DIRECTORY_SEPARATOR . "init.php";
?>