<?php

namespace rubidium\middleware;

class Logging extends MiddlewareAbstract
{
    function call($env)
    {
        $response = $this->app->call($env);
    }
}
