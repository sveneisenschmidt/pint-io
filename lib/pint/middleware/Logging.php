<?php

namespace pint\middleware;

use pint\middleware\MiddlewareAbstract;

class Logging extends MiddlewareAbstract
{
    function call($env)
    {
        echo get_class($this) . ": request for " . $env["REQUEST_URI"] . "\n";
        return $this->app->call($env);
    }
}
