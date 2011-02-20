<?php

namespace example;

class App extends \pint\App\AppAbstract {
    
    
    function call($env) 
    {
        return array(
            200,
            array("Content-Type" => "text/html"),
            "You asked for " . $env["REQUEST_URI"]
        );
    }
    
    
}
