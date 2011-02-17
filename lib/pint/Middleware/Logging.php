<?php

namespace pint\Middleware;

use pint\Middleware\MiddlewareAbstract;

class Logging extends MiddlewareAbstract
{
    /**
     *
     * @param array $env
     * @return mixed 
     */
    function call($env = array())
    {
        echo get_class($this) . ": request for " . $env["REQUEST_URI"] . "\n";
        return $this->app->call($env);
    }
}
