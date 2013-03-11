<?php
class ExpectedErrorTest extends PHPUnit_Framework_TestCase {
    /**
     * @expectedException UnknownException
     */
    public function testFailingInclude() {
        include 'not_existing_file.php';
    }
}
?>
