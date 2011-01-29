<?php

namespace example;

class App {
    function call($env)
    {
        return array(
            200,
            array("Content-Type" => "text/html"),
            "You asked for " . $env["PATH_INFO"]
        );
    }
}
