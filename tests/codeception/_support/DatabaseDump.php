<?php

namespace Codeception\Module;

class DatabaseDump extends Cli
{
    public function _before(\Codeception\TestCase $test) {

        // Get The file name of the test

        $filename = $this->test_name = basename($test->getFileName(),'.php');

        // At the end of  every test export a db dump

        $this->runShellCommand('wp db export '.$filename.'_before.sql; cp '.$filename.'_before.sql ../tests/_output/');

        echo "Success: Exported to $filename.sql\n";
    }

    public function _after(\Codeception\TestCase $test) {

        // Get The file name of the test

        $filename = $this->test_name = basename($test->getFileName(),'.php');

        // At the end of  every test export a db dump

        $this->runShellCommand('wp db export '.$filename.'_after.sql; cp '.$filename.'_after.sql ../tests/_output/');

        echo "Success: Exported to $filename.sql\n";
    }

}