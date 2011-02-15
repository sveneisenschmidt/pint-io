<?php

namespace rubidium\middleware;

class Logging extends MiddlewareAbstract
{
    function call($env)
    {
        echo get_class($this) . ": request for " . $env["REQUEST_URI"] . "\n";
        return $this->app->call($env);
    }
}
