<?php

namespace rubidium\middleware;

class MiddlewareAbstract
{
    protected $app;

    function __construct($app = null)
    {
        $this->app($app);
    }

    function app($app = null)
    {
        if (!is_null($this->app))
        {
            $this->app = $app;
        }

        return $this->app();
    }

    function call(array $env)
    {
        $env["rubidium"]["errors"] []= get_class() . " is missing a call() method.";

        return array(
            500,
            array("Content-Type" => "text/html"),
            "Hi, I'm " . get_class($this) . " and I'm bugging you because you " .
                "didn't implement a proper call() method for me :-X"
        );
    }
}
