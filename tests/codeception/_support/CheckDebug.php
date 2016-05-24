<?php

namespace Codeception\Module;

class CheckDebug extends \Codeception\Module
{


    public function _before(\Codeception\TestCase $test) {

        // I will clean the debug.log before test starts

        // I will change folder where debug.log is located. This setting will be stored until test is finished
        chdir('wp-content');

        // I will clear debug.log
        file_put_contents("debug.log", "");

    }


    public function _after(\Codeception\TestCase $test) {

        // I will load debug.log
        $debug_log = "debug.log";

        // I will clear the cache
        clearstatcache();

        // I am checking if debug.log contains anything
        if(filesize($debug_log)) {
            die("Your Debug File is not empty. I am stopping the tests\n");
        }

    }

}