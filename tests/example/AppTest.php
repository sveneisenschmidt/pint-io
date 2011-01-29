<?php

namespace example\tests;

use \example\App;

class AppTest extends \PHPUnit_Framework_TestCase
{
    function testResponse() {
        $app = new App;
        $this->assertEquals(array(
            200,
            array("Content-Type" => "text/html"),
            "You asked for /hello/world"
        ), $app->call(array("PATH_INFO" => "/hello/world")));
    }
}
